<?php

namespace App\Livewire\Maintenance\MouldChecklist;

use App\Exports\MouldChecklistExport;
use App\Models\MasterMould;
use App\Models\MouldChecklist;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterCustomer = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->filterDateFrom = Carbon::now()->subDays(14)->format('Y-m-d');
        $this->filterDateTo = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterCustomer()
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo()
    {
        $this->resetPage();
    }

    public function exportExcel()
    {
        $fileName = 'Mould_Checklist_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new MouldChecklistExport(
                $this->filterDateFrom,
                $this->filterDateTo,
                $this->filterStatus,
                $this->filterCustomer,
                $this->search
            ),
            $fileName
        );
    }

    public function deleteChecklist($id)
    {
        $checklist = MouldChecklist::findOrFail($id);

        $user = auth()->user();
        $canDelete = $user && ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN') || $checklist->status === 'DRAFT');

        if (! $canDelete) {
            session()->flash('error', 'Hanya dokumen berstatus DRAFT atau Admin yang dapat dihapus.');
            return;
        }

        $checklist->delete();
        session()->flash('success', 'Checklist mould berhasil dihapus.');
    }

    public function render()
    {
        $query = MouldChecklist::with(['details', 'issuedBy', 'checkedBy'])->latest('check_date')->latest('id');

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('mould_no', 'LIKE', $term)
                    ->orWhere('part_no', 'LIKE', $term)
                    ->orWhere('part_name', 'LIKE', $term)
                    ->orWhere('technician_name', 'LIKE', $term)
                    ->orWhere('machine_number', 'LIKE', $term);
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCustomer) {
            $query->where('customer', $this->filterCustomer);
        }

        if ($this->filterDateFrom) {
            $query->where('check_date', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->where('check_date', '<=', $this->filterDateTo);
        }

        $checklists = $query->paginate(15);

        // KPI calculations
        $baseKpiQuery = MouldChecklist::query();
        if ($this->filterDateFrom) {
            $baseKpiQuery->where('check_date', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $baseKpiQuery->where('check_date', '<=', $this->filterDateTo);
        }

        $totalChecklists = (clone $baseKpiQuery)->count();
        $totalSubmitted = (clone $baseKpiQuery)->where('status', 'SUBMITTED')->count();
        $totalApproved = (clone $baseKpiQuery)->where('status', 'APPROVED')->count();
        $totalDraft = (clone $baseKpiQuery)->where('status', 'DRAFT')->count();

        $customers = MasterMould::select('customer')
            ->distinct()
            ->orderBy('customer')
            ->pluck('customer')
            ->filter()
            ->values();

        return view('livewire.maintenance.mould-checklist.index', [
            'checklists' => $checklists,
            'totalChecklists' => $totalChecklists,
            'totalSubmitted' => $totalSubmitted,
            'totalApproved' => $totalApproved,
            'totalDraft' => $totalDraft,
            'customers' => $customers,
        ])->layout('layouts.app');
    }
}
