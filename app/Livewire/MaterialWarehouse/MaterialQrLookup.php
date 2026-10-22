<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MwhPallet;
use Livewire\Component;

class MaterialQrLookup extends Component
{
    public string $pallet_id = '';

    public ?MwhPallet $palletData = null;

    public bool $searched = false;

    protected $queryString = ['pallet_id'];

    public function mount(): void
    {
        if ($this->pallet_id) {
            $this->lookupPallet();
        }
    }

    public function lookupPallet(): void
    {
        $input = trim($this->pallet_id);

        // If scanned input is a URL (e.g. from native phone camera)
        if (filter_var($input, FILTER_VALIDATE_URL) || strpos($input, 'pallet_id=') !== false) {
            $parsedUrl = parse_url($input);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['pallet_id'])) {
                    $input = $queryParams['pallet_id'];
                }
            }
        }

        $code = strtoupper(trim($input));
        $this->searched = true;

        if ($code) {
            $this->palletData = MwhPallet::with([
                'warehouse',
                'position.rack.warehouse',
                'material',
                'incomingHeader',
                'outgoings.position.rack',
            ])->where('pallet_id', $code)->first();
        } else {
            $this->palletData = null;
        }
    }

    public function render()
    {
        return view('livewire.material-warehouse.material-qr-lookup');
    }
}
