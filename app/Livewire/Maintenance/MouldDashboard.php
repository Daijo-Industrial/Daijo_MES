<?php

namespace App\Livewire\Maintenance;

use App\Models\MaintenanceMould;
use Carbon\Carbon;
use Livewire\Component;

class MouldDashboard extends Component
{
    public $showMouldModal = false;

    public $selectedMould = null;

    public $filterType = '';

    public $filterPIC = '';

    public $filterPartNo = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    public function mount()
    {
        $this->filterDateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->filterDateTo = Carbon::now()->format('Y-m-d');
    }

    public function openMouldModal($id)
    {
        $this->selectedMould = MaintenanceMould::find($id);
        $this->showMouldModal = true;
    }

    public function closeMouldModal()
    {
        $this->showMouldModal = false;
        $this->selectedMould = null;
    }

    public function getFilteredMouldsProperty()
    {
        $query = MaintenanceMould::query();

        if ($this->filterType) {
            $query->where('tipe', $this->filterType);
        }
        if ($this->filterPIC) {
            $query->where('pic', $this->filterPIC);
        }
        if ($this->filterPartNo) {
            $query->where('part_no', 'LIKE', '%' . $this->filterPartNo . '%');
        }
        if ($this->filterDateFrom) {
            $query->where('tanggal', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->where('tanggal', '<=', $this->filterDateTo);
        }

        return $query->latest()->get();
    }

    public function render()
    {
        $moulds = $this->filteredMoulds;

        $total = $moulds->count();
        $finished = $moulds->where('status', 1)->count();
        $ongoing = $total - $finished;

        $mouldTotal = $moulds->count();
        $mouldFinished = $moulds->where('status', 1)->count();
        $mouldOngoing = $mouldTotal - $mouldFinished;

        $pics = array_unique(array_merge(
            MaintenanceMould::pluck('pic')->toArray()
        ));

        $partNoList = MaintenanceMould::pluck('part_no')->unique()->filter()->sort()->values()->toArray();

        return view('livewire.maintenance.mould-dashboard', compact(
            'moulds',
            'total',
            'finished',
            'pics',
            'ongoing',
            'mouldTotal',
            'mouldFinished',
            'mouldOngoing',
            'partNoList'
        ))->layout('layouts.dashboard');
    }
}
