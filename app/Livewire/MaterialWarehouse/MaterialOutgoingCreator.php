<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use App\Services\MaterialWarehouseService;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialOutgoingCreator extends Component
{
    use WithPagination;

    // Form Picking Fields
    public ?int $whse_id = null;

    public array $warehouses = [];

    public string $selected_item_code = '';

    public string $selected_item_description = '';

    public string $selected_pallet_id = '';

    public ?MwhPallet $selectedPallet = null;

    public string $qty_taken = '';

    public string $outgoing_date = '';

    public string $issued_to = '';

    public string $remarks = '';

    // Autocomplete & FIFO State
    public array $materialSearchResults = [];

    public array $fifoRecommendations = [];

    protected $queryString = ['selected_item_code', 'whse_id'];

    public function mount(): void
    {
        $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        if (! $this->whse_id) {
            $this->whse_id = $this->warehouses[0]['id'] ?? 1;
        }

        $this->outgoing_date = now()->format('Y-m-d');

        if ($this->selected_item_code) {
            $this->selectMaterial($this->selected_item_code);
        }
    }

    public function updatedWhseId(): void
    {
        $this->reset(['selected_pallet_id', 'selectedPallet']);
        if ($this->selected_item_code) {
            $this->selectMaterial($this->selected_item_code);
        }
    }

    public function updatedSelectedItemCode($value, MaterialWarehouseService $mwhService): void
    {
        $query = trim($value);
        if (strlen($query) >= 2) {
            $this->materialSearchResults = MasterListMaterial::query()
                ->where('item_code', 'like', '%' . $query . '%')
                ->orWhere('item_description', 'like', '%' . $query . '%')
                ->limit(10)
                ->get(['item_code', 'item_description', 'purchasing_uom'])
                ->toArray();
        } else {
            $this->materialSearchResults = [];
        }
    }

    public function selectMaterial(string $itemCode, ?MaterialWarehouseService $mwhService = null): void
    {
        $mwhService = $mwhService ?: app(MaterialWarehouseService::class);

        $mat = MasterListMaterial::where('item_code', $itemCode)->first();
        if ($mat) {
            $this->selected_item_code = $mat->item_code;
            $this->selected_item_description = $mat->item_description ?? '';
            $this->materialSearchResults = [];

            // Get FIFO recommendations filtered by selected warehouse
            $this->fifoRecommendations = $mwhService->getFifoRecommendations($mat->item_code, $this->whse_id)->toArray();
        }
    }

    public function updatedSelectedPalletId($value): void
    {
        $input = trim($value);
        if (filter_var($input, FILTER_VALIDATE_URL) || strpos($input, 'pallet_id=') !== false) {
            $parsedUrl = parse_url($input);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['pallet_id'])) {
                    $input = $queryParams['pallet_id'];
                    $this->selected_pallet_id = $input;
                }
            }
        }
        $this->selectPalletForPicking($input);
    }

    public function selectPalletForPicking(string $palletId): void
    {
        $input = trim($palletId);
        if (filter_var($input, FILTER_VALIDATE_URL) || strpos($input, 'pallet_id=') !== false) {
            $parsedUrl = parse_url($input);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['pallet_id'])) {
                    $input = $queryParams['pallet_id'];
                }
            }
        }

        $pallet = MwhPallet::with(['position.rack', 'material', 'incomingHeader'])
            ->where('pallet_id', strtoupper($input))
            ->first();

        if ($pallet) {
            $this->selected_pallet_id = $pallet->pallet_id;
            $this->selectedPallet = $pallet;
            $this->selected_item_code = $pallet->item_code;
            $this->selected_item_description = $pallet->material ? $pallet->material->item_description : '';
        }
    }

    public function processPicking(MaterialWarehouseService $mwhService): void
    {
        $this->validate([
            'selected_pallet_id' => 'required|string|exists:mwh_pallets,pallet_id',
            'qty_taken' => 'required|numeric|min:0.01',
            'outgoing_date' => 'required|date',
            'issued_to' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ], [
            'selected_pallet_id.required' => 'Pilih Pallet ID material yang akan diambil.',
            'qty_taken.required' => 'Jumlah KG yang diambil harus diisi.',
            'qty_taken.min' => 'Jumlah KG harus lebih besar dari 0.',
        ]);

        try {
            $outgoing = $mwhService->processOutgoingPicking(
                $this->selected_pallet_id,
                (float) $this->qty_taken,
                $this->outgoing_date,
                trim($this->issued_to) ?: null,
                trim($this->remarks) ?: null
            );

            session()->flash('success', "Material {$outgoing->qty_taken} KG berhasil diambil dari Pallet {$outgoing->pallet_id}. Kode Outgoing: {$outgoing->outgoing_code}");

            // Reset form
            $this->reset(['selected_pallet_id', 'selectedPallet', 'qty_taken', 'issued_to', 'remarks']);
            if ($this->selected_item_code) {
                $this->selectMaterial($this->selected_item_code, $mwhService);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses pengeluaran material: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = MwhOutgoing::with(['position.rack', 'material', 'pallet', 'warehouse']);

        if ($this->whse_id) {
            $query->where(function ($q) {
                $q->where('whse_id', $this->whse_id)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $this->whse_id));
            });
        }

        $outgoings = $query->orderBy('outgoing_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.material-warehouse.material-outgoing-creator', [
            'warehouses' => $this->warehouses,
            'outgoings' => $outgoings,
        ]);
    }
}
