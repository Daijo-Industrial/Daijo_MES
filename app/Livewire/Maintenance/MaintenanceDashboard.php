<?php

namespace App\Livewire\Maintenance;

use App\Models\MaintenanceMachine;
use App\Models\MaintenanceMould;
use Carbon\Carbon;
use Livewire\Component;

class MaintenanceDashboard extends Component
{
    public $showMachineModal = false;

    public $showMouldModal = false;

    public $selectedMachine = null;

    public $selectedMould = null;

    // Separate filters for Machine and Mould
    public $filterMachineType = '';

    public $filterMachinePIC = '';

    public $filterMouldType = '';

    public $filterMouldPIC = '';

    // Global date filters
    public $filterDateFrom = '';

    public $filterDateTo = '';

    public function mount()
    {
        $this->filterDateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->filterDateTo = Carbon::now()->format('Y-m-d');
    }

    // Modal handlers
    public function openMachineModal($id)
    {
        $this->selectedMachine = MaintenanceMachine::find($id);
        $this->showMachineModal = true;
    }

    public function closeMachineModal()
    {
        $this->showMachineModal = false;
        $this->selectedMachine = null;
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

    // Filtered collections with separate filters
    public function getFilteredMachinesProperty()
    {
        $query = MaintenanceMachine::query();

        // Machine specific filters
        if ($this->filterMachineType) {
            $query->where('tipe', $this->filterMachineType);
        }
        if ($this->filterMachinePIC) {
            $query->where('pic', $this->filterMachinePIC);
        }

        // Global date filters
        if ($this->filterDateFrom) {
            $query->where('tanggal', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->where('tanggal', '<=', $this->filterDateTo);
        }

        return $query->latest()->get();
    }

    public function getFilteredMouldsProperty()
    {
        $query = MaintenanceMould::query();

        // Mould specific filters
        if ($this->filterMouldType) {
            $query->where('tipe', $this->filterMouldType);
        }
        if ($this->filterMouldPIC) {
            $query->where('pic', $this->filterMouldPIC);
        }

        // Global date filters
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
        $filteredMachines = $this->filteredMachines;
        $filteredMoulds = $this->filteredMoulds;

        // Summary from filtered
        $machineTotal = $filteredMachines->count();
        $machineFinished = $filteredMachines->where('status', 1)->count();
        $machineOngoing = $machineTotal - $machineFinished;

        $machineAvgTime = $filteredMachines
            ->filter(fn ($m) => $m->lama_pengerjaan)
            ->map(function ($m) {
                // convert "2h 31m" => total minutes
                preg_match('/(?:(\d+)h)? ?(?:(\d+)m)?/', $m->lama_pengerjaan, $matches);
                $hours = $matches[1] ?? 0;
                $minutes = $matches[2] ?? 0;

                return $hours * 60 + $minutes;
            })
            ->avg();

        if ($machineAvgTime) {
            $hours = floor($machineAvgTime / 60);
            $minutes = round($machineAvgTime % 60);
            $machineAvgTime = "{$hours}h {$minutes}m";
        } else {
            $machineAvgTime = '-';
        }

        $mouldTotal = $filteredMoulds->count();
        $mouldFinished = $filteredMoulds->where('status', 1)->count();
        $mouldOngoing = $mouldTotal - $mouldFinished;

        $pics = array_unique(array_merge(
            MaintenanceMachine::pluck('pic')->toArray(),
            MaintenanceMould::pluck('pic')->toArray()
        ));

        return view('livewire.maintenance.maintenance-dashboard', compact(
            'machineTotal',
            'machineFinished',
            'machineOngoing',
            'machineAvgTime',
            'mouldTotal',
            'mouldFinished',
            'mouldOngoing',
            'pics'
        ));
    }
}
