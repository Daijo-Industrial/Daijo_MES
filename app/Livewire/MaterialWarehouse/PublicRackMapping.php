<?php

namespace App\Livewire\MaterialWarehouse;

use Livewire\Attributes\Layout;

class PublicRackMapping extends RackMapping
{
    public function mount(?\App\Services\MaterialWarehouseService $mwhService = null): void
    {
        parent::mount($mwhService);
        $this->isViewOnly = true;
    }

    #[Layout('layouts.dashboard')]
    public function render()
    {
        return parent::render();
    }
}
