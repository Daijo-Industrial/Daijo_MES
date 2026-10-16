<?php

namespace App\Livewire\Common;

use App\Models\Branch;
use App\Services\PlantContextService;
use Livewire\Component;

class PlantSwitcher extends Component
{
    public $activeBranchId;

    public $canSwitch = false;

    public $activePlantLabel = '';

    public function mount(PlantContextService $service)
    {
        $this->canSwitch = $service->canSwitchBranch();
        $this->activePlantLabel = $service->getActivePlantLabel();

        if ($service->isAllPlants()) {
            $this->activeBranchId = PlantContextService::ALL_PLANTS;
        } else {
            $this->activeBranchId = $service->getEffectiveBranchId() ?? PlantContextService::ALL_PLANTS;
        }
    }

    public function switchPlant($branchId, PlantContextService $service)
    {
        if (! $this->canSwitch) {
            return;
        }

        $service->setEffectiveBranch($branchId);
        $this->activeBranchId = $branchId;
        $this->activePlantLabel = $service->getActivePlantLabel();

        return redirect(request()->header('Referer', route('dashboard')));
    }

    public function render()
    {
        $branches = Branch::active()->orderBy('name')->get();

        return view('livewire.common.plant-switcher', compact('branches'));
    }
}
