<?php

namespace App\Livewire;

use App\Models\MasterZone;
use App\Services\MachineMonitoringService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MachineStatusPublic extends Component
{
    public $zones = [];

    public $currentZoneIndex = 0;

    public $selectedZoneId = null;

    public $currentZoneName = '';

    protected MachineMonitoringService $machineService;

    public function boot(MachineMonitoringService $service)
    {
        $this->machineService = $service;
    }

    public function mount()
    {
        $this->zones = MasterZone::orderBy('zone_name')->get();
        if ($this->zones->count() > 0) {
            $this->selectedZoneId = $this->zones[$this->currentZoneIndex]->id;
            $this->currentZoneName = $this->zones[$this->currentZoneIndex]->zone_name;
        }
    }

    /**
     * Cycle to the next zone
     */
    public function nextZone()
    {
        if ($this->zones->count() === 0) {
            return;
        }

        $this->currentZoneIndex++;

        // Loop back to start if at the end
        if ($this->currentZoneIndex >= $this->zones->count()) {
            $this->currentZoneIndex = 0;
        }

        $this->selectedZoneId = $this->zones[$this->currentZoneIndex]->id;
        $this->currentZoneName = $this->zones[$this->currentZoneIndex]->zone_name;

        // Reset scroll on client side
        $this->dispatch('zone-changed');
    }

    #[Layout('layouts.dashboard')]
    public function render()
    {
        $machines = $this->machineService->getMachineStatuses(
            $this->selectedZoneId ? (string) $this->selectedZoneId : null
        );

        return view('livewire.machine-status-public', [
            'machines' => $machines,
        ]);
    }
}
