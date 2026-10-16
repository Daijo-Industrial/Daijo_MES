<?php

namespace App\Livewire\Maintenance;

use App\Models\MaintenanceMachine;
use Carbon\Carbon;
use Livewire\Component;

class MachineDashboard extends Component
{
    public $showMachineModal = false;

    public $selectedMachine = null;

    public $filterType = '';

    public $filterPIC = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    public $filterMesin = '';

    public function mount()
    {
        $this->filterDateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->filterDateTo = Carbon::now()->format('Y-m-d');
    }

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

    public function getFilteredMachinesProperty()
    {
        $query = MaintenanceMachine::query();

        if ($this->filterType) {
            $query->where('tipe', $this->filterType);
        }
        if ($this->filterPIC) {
            $query->where('pic', $this->filterPIC);
        }
        if ($this->filterMesin) {
            $query->where('mesin', 'LIKE', '%' . $this->filterMesin . '%');
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
        $machines = $this->filteredMachines;

        $total = $machines->count();
        $finished = $machines->where('status', 1)->count();
        $ongoing = $total - $finished;

        $machineTotal = $machines->count();
        $machineFinished = $machines->where('status', 1)->count();
        $machineOngoing = $machineTotal - $machineFinished;

        $pics = array_unique(array_merge(
            MaintenanceMachine::pluck('pic')->toArray(),
        ));
        $mesinList = MaintenanceMachine::pluck('mesin')->unique()->filter()->sort()->values()->toArray();

        return view('livewire.maintenance.machine-dashboard', compact(
            'machines',
            'total',
            'finished',
            'pics',
            'ongoing',
            'machineTotal',
            'machineFinished',
            'machineOngoing',
            'mesinList'
        ))->layout('layouts.dashboard');

    }
}
