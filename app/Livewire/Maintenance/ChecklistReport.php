<?php

namespace App\Livewire\Maintenance;

use App\Models\MaintenanceCheckHeader;
use App\Models\MaintenanceCheckItem;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ChecklistReport extends Component
{
    use WithPagination;

    public $selectedDate;

    public $selectedMachineId = '';

    public $selectedStatus = '';

    public $search = '';

    public $showDetailModal = false;

    public $selectedHeader = null;

    public function mount()
    {
        $this->selectedDate = Carbon::now('Asia/Jakarta')
            ->subHours(7)
            ->subMinutes(30)
            ->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedDate()
    {
        $this->resetPage();
    }

    public function updatingSelectedMachineId()
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus()
    {
        $this->resetPage();
    }

    public function openDetailModal($headerId)
    {
        $this->selectedHeader = MaintenanceCheckHeader::with(['machine', 'details.item'])
            ->find($headerId);

        if ($this->selectedHeader) {
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedHeader = null;
    }

    public function render()
    {
        $machines = User::where('role_id', 4)
            ->orderBy('name')
            ->get();

        // Query headers for selected date
        $headersQuery = MaintenanceCheckHeader::query()
            ->where('date', $this->selectedDate)
            ->with(['machine', 'details.item']);

        if ($this->selectedMachineId) {
            $headersQuery->where('machine_id', $this->selectedMachineId);
        }

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $headersQuery->where(function ($q) use ($searchTerm) {
                $q->where('prepared_by', 'LIKE', $searchTerm)
                    ->orWhere('approved_by', 'LIKE', $searchTerm)
                    ->orWhereHas('machine', function ($mq) use ($searchTerm) {
                        $mq->where('name', 'LIKE', $searchTerm);
                    });
            });
        }

        $submittedHeaders = $headersQuery->get()->keyBy('machine_id');

        // Combine all active machines with their submission state
        $reportRows = collect();

        foreach ($machines as $machine) {
            // Apply machine filter if selected
            if ($this->selectedMachineId && $machine->id != $this->selectedMachineId) {
                continue;
            }

            // Apply search filter on machine name if typed
            if ($this->search && ! str_contains(strtolower($machine->name), strtolower($this->search)) && ! isset($submittedHeaders[$machine->id])) {
                continue;
            }

            $header = $submittedHeaders->get($machine->id);

            $hasNg = false;
            $okCount = 0;
            $ngCount = 0;
            $skipCount = 0;

            if ($header) {
                foreach ($header->details as $d) {
                    if ($d->value === '-') {
                        $skipCount++;
                    } elseif ($d->value === 'NG' || ! $d->is_normal) {
                        $hasNg = true;
                        $ngCount++;
                    } elseif ($d->value === 'OK') {
                        $okCount++;
                    } else {
                        $skipCount++;
                    }
                }
            }

            $statusKey = 'PENDING';
            if ($header) {
                $statusKey = $hasNg ? 'HAS_NG' : 'COMPLETED';
            }

            // Filter by status if selected
            if ($this->selectedStatus && $this->selectedStatus !== $statusKey) {
                continue;
            }

            $reportRows->push([
                'machine' => $machine,
                'header' => $header,
                'status' => $statusKey,
                'ok_count' => $okCount,
                'ng_count' => $ngCount,
                'skip_count' => $skipCount,
            ]);
        }

        // Summary metrics
        $totalMachines = $machines->count();
        $totalFilled = $submittedHeaders->count();
        $totalPending = $totalMachines - $totalFilled;
        $totalNgHeaders = $submittedHeaders->filter(function ($h) {
            return $h->details->contains(fn ($d) => $d->value === 'NG' || ! $d->is_normal);
        })->count();

        $allItems = MaintenanceCheckItem::orderBy('sort_order')->get();

        return view('livewire.maintenance.checklist-report', compact(
            'machines',
            'reportRows',
            'totalMachines',
            'totalFilled',
            'totalPending',
            'totalNgHeaders',
            'allItems'
        ))->layout('layouts.app');
    }
}
