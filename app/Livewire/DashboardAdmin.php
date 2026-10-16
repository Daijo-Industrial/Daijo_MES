<?php

namespace App\Livewire;

use App\Models\Production\PRD_BillOfMaterialParent;
use App\Models\Production\PRD_MaterialLog;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardAdmin extends Component
{
    use WithPagination;

    public $childStatus = ''; // Filter for Child Status

    public $selectedProcess = ''; // Filter for Parent Process

    public $selectedParent = ''; // New filter for selecting specific BOMs

    public $availableProcesses = []; // Store unique process names for dropdown

    public $availableParents = []; // Store available parents for dropdown

    public $completedItems = 0;

    public $overallCompletionPercentage = 0;

    public $totalPendingChildren = 0;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->availableProcesses = cache()->remember('availableProcesses', 60, function () {
            return PRD_MaterialLog::distinct()->pluck('process_name')->toArray();
        });

        $this->availableParents = cache()->remember('availableParents', 60, function () {
            return PRD_BillOfMaterialParent::pluck('code', 'id')->toArray();
        });
    }

    public function calculateStatistics($childs, $materialLogs)
    {
        $this->totalPendingChildren = $childs
            ->filter(function ($child) {
                return $child->action_type !== 'stockfinish' && ($child->action_type !== 'buyfinish' || $child->status !== 'Finished');
            })
            ->count();

        $this->completedItems = $childs->filter(fn ($child) => in_array($child->action_type, ['stockfinish']) || $child->status === 'Finished')->count();

        $this->overallCompletionPercentage = $childs->count() > 0 ? ($this->completedItems / $childs->count()) * 100 : 0;
    }

    public function applyFilters()
    {
        $this->resetPage(); // Reset pagination when filter changes
    }

    public function calculateParentCompletion($parent)
    {
        $finishedChildren = $parent->children
            ->filter(function ($child) {
                $processCount = $child->materialProcess->count();
                $finishedCount = $child->materialProcess->where('status', 2)->count();

                if (($child->action_type === 'buyfinish' && $child->status === 'Finished') || $child->action_type === 'stockfinish') {
                    return true;
                } elseif (in_array($child->action_type, ['stockprocess', 'buyprocess'])) {
                    return $processCount > 0 && $finishedCount === $processCount;
                }

                return false;
            })
            ->count();

        return $parent->children->count() > 0 ? ($finishedChildren / $parent->children->count()) * 100 : 0;
    }

    public function render()
    {
        $query = PRD_BillOfMaterialParent::with([
            'children' => function ($query) {
                $query->select(['id', 'parent_id', 'item_code', 'item_description', 'quantity', 'measure', 'action_type', 'status']);

                // Apply childStatus filter inside the children query
                if (! empty($this->childStatus)) {
                    if ($this->childStatus == 'Not Started') {
                        $query->where('status', '!=', 'Finished');
                    } elseif ($this->childStatus == 'Finished') {
                        $query->where('status', 'Finished');
                    }
                }

                // Apply selectedProcess filter to ensure only children with a process are shown
                if (! empty($this->selectedProcess)) {
                    $query->whereHas('materialProcess', function ($q) {
                        $q->where('process_name', $this->selectedProcess);
                    });
                }
            },
            'children.materialProcess' => function ($query) {
                $query->select(['id', 'child_id', 'process_name', 'status']);

                // Apply selectedProcess filter inside the materialProcess query
                if (! empty($this->selectedProcess)) {
                    $query->where('process_name', $this->selectedProcess);
                }
            },
        ]);

        // If a parent has no matching children, it will still be included.
        // To remove parents without filtered children, use whereHas()
        if (! empty($this->childStatus)) {
            $query->whereHas('children', function ($q) {
                if ($this->childStatus == 'Not Started') {
                    $q->where('status', '!=', 'Finished');
                } elseif ($this->childStatus == 'Finished') {
                    $q->where('status', 'Finished');
                }
            });
        }

        if (! empty($this->selectedProcess)) {
            $query->whereHas('children.materialProcess', function ($q) {
                $q->where('process_name', $this->selectedProcess);
            });
        }

        $parents = $query->paginate(1); // Increase per-page to reduce queries

        // Preload Children and Material Logs to Avoid Multiple Queries
        $parentCollection = collect($parents->items());

        $childs = $parentCollection->flatMap->children;
        $materialLogs = $childs->flatMap->materialProcess;

        $this->calculateStatistics($childs, $materialLogs);

        return view('livewire.dashboard-admin', [
            'parentCodes' => cache()->remember('parent_codes', 60, function () {
                return PRD_BillOfMaterialParent::orderBy('id')->pluck('code')->toArray();
            }),
            'parents' => $parents,
            'childs' => $childs,
            'materialLogs' => $materialLogs,
            'completedItems' => $this->completedItems,
            'overallCompletionPercentage' => $this->overallCompletionPercentage,
            'totalPendingChildren' => $this->totalPendingChildren,
            'availableProcesses' => $this->availableProcesses,
            'availableParents' => $this->availableParents,
        ]);
    }
}
