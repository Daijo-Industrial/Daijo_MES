<?php

namespace App\Livewire;

use App\Models\MasterZone;
use App\Services\MachineMonitoringService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class MachineStatusMonitor extends Component
{
    use WithPagination;

    public $selectedZone = '';

    public $search = '';

    public $interactive = true;

    // Drill-down properties
    public $selectedMachineId = null;

    public $machineDetails = null;

    // Auto-refresh properties
    public $refreshInterval = 30; // Seconds

    protected MachineMonitoringService $machineService;

    /**
     * Dependency injection in Livewire 3
     */
    public function boot(MachineMonitoringService $service)
    {
        $this->machineService = $service;
    }

    public function selectMachine($id)
    {
        if (! $this->interactive) {
            return;
        }

        $this->selectedMachineId = $id;
        $this->machineDetails = $this->machineService->getMachineDetailedHistory($id);
    }

    public function closeDetail()
    {
        $this->selectedMachineId = null;
        $this->machineDetails = null;
    }

    /**
     * Get data for rendering
     */
    #[Layout('layouts.dashboard')]
    public function render()
    {
        $machines = $this->machineService->getMachineStatuses(
            $this->selectedZone ?: null,
            $this->search ?: null
        );

        $zones = MasterZone::all();

        return view('livewire.machine-status-monitor', [
            'machines' => $machines,
            'zones' => $zones,
        ]);
    }
}
