<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhIncomingHeader;
use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Services\MaterialWarehouseService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MaterialIncomingCreator extends Component
{
    // Header Fields
    public ?int $whse_id = null;

    public array $warehouses = [];

    public string $document_no = '';

    public string $incoming_type = 'SUPPLIER'; // SUPPLIER vs RETURN_PRODUCTION

    public string $supplier_name = '';

    public string $returned_from = '';

    public string $po_number = '';

    public string $original_outgoing_code = '';

    public bool $is_prefilled_from_outgoing = false;

    public string $arrival_date = '';

    public string $remarks = '';

    // Item Rows (Multi-line)
    public array $items = [];

    // Success State
    public bool $showSuccessModal = false;

    public array $createdPallets = [];

    public function mount(MaterialWarehouseService $mwhService): void
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

        $this->arrival_date = now()->format('Y-m-d');
        $this->document_no = $mwhService->generateDocumentNo();

        // Handle pre-filled query parameters from Outgoing History for Retur Produksi
        $reqType = request()->query('type');
        $reqOutgoingCode = request()->query('outgoing_code');
        $reqReturnedFrom = request()->query('returned_from');
        $reqItemCode = request()->query('item_code');
        $reqWhseId = request()->query('whse_id');

        if ($reqWhseId) {
            $this->whse_id = (int) $reqWhseId;
        }

        if ($reqType === 'RETURN_PRODUCTION') {
            $this->incoming_type = 'RETURN_PRODUCTION';
        }

        if ($reqOutgoingCode) {
            $this->original_outgoing_code = trim($reqOutgoingCode);
            $this->is_prefilled_from_outgoing = true;
        }

        if ($reqReturnedFrom) {
            $this->returned_from = trim($reqReturnedFrom);
        }

        // Add 1 initial row
        $this->addItemRow();

        if ($reqItemCode) {
            $mat = MasterListMaterial::where('item_code', trim($reqItemCode))->first();
            if ($mat) {
                $this->items[0]['item_code'] = $mat->item_code;
                $this->items[0]['item_description'] = $mat->item_description ?? '';
            }
        }
    }

    public function updatedWhseId(): void
    {
        // Reset selected position_id in all item rows when branch changes
        foreach ($this->items as &$item) {
            $item['position_id'] = '';
        }
    }

    public function addItemRow(): void
    {
        $this->items[] = [
            'item_code' => '',
            'item_description' => '',
            'lot_no' => '',
            'qty' => '',
            'position_id' => '',
            'searchResults' => [],
        ];
    }

    public function removeItemRow(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function updatedItems($value, $key): void
    {
        // Live search for item_code autocomplete
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'item_code') {
            $index = (int) $parts[0];
            $query = trim($value);

            if (strlen($query) >= 2) {
                $this->items[$index]['searchResults'] = MasterListMaterial::query()
                    ->where('item_code', 'like', '%' . $query . '%')
                    ->orWhere('item_description', 'like', '%' . $query . '%')
                    ->limit(10)
                    ->get(['item_code', 'item_description', 'purchasing_uom'])
                    ->toArray();
            } else {
                $this->items[$index]['searchResults'] = [];
            }
        }
    }

    public function selectMaterial(int $index, string $itemCode, string $itemDesc): void
    {
        $this->items[$index]['item_code'] = $itemCode;
        $this->items[$index]['item_description'] = $itemDesc;
        $this->items[$index]['searchResults'] = [];
    }

    public function setRowToTemporary(int $index): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['position_id'] = 'TEMPORARY';
        }
    }

    public function setAllToTemporary(): void
    {
        foreach ($this->items as $index => $row) {
            $this->items[$index]['position_id'] = 'TEMPORARY';
        }
    }

    public function saveIncoming(MaterialWarehouseService $mwhService): void
    {
        $this->validate([
            'whse_id' => 'required|exists:mwh_warehouses,id',
            'incoming_type' => 'required|in:SUPPLIER,RETURN_PRODUCTION',
            'arrival_date' => 'required|date',
            'supplier_name' => 'nullable|string|max:255',
            'returned_from' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'original_outgoing_code' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string|exists:master_list_materials,item_code',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.position_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== 'TEMPORARY' && ! MwhPosition::where('id', $value)->exists()) {
                        $fail('Pilih slot rak lokasi penyimpanan atau Area Temporary.');
                    }
                },
            ],
        ], [
            'items.*.item_code.required' => 'Part Code material harus diisi.',
            'items.*.item_code.exists' => 'Part Code material tidak ada di Master List.',
            'items.*.qty.required' => 'Jumlah (KG) harus diisi.',
            'items.*.position_id.required' => 'Pilih slot rak lokasi penyimpanan atau Area Temporary.',
        ]);

        try {
            DB::beginTransaction();

            $header = MwhIncomingHeader::create([
                'whse_id' => $this->whse_id,
                'document_no' => $mwhService->generateDocumentNo(),
                'incoming_type' => $this->incoming_type,
                'supplier_name' => $this->incoming_type === 'SUPPLIER' ? (trim($this->supplier_name) ?: null) : null,
                'returned_from' => $this->incoming_type === 'RETURN_PRODUCTION' ? (trim($this->returned_from) ?: null) : null,
                'po_number' => trim($this->po_number) ?: null,
                'original_outgoing_code' => trim($this->original_outgoing_code) ?: null,
                'arrival_date' => $this->arrival_date,
                'remarks' => trim($this->remarks) ?: null,
            ]);

            $this->createdPallets = [];

            foreach ($this->items as $row) {
                $totalQty = (float) $row['qty'];
                $itemCode = strtoupper(trim($row['item_code']));
                $lotNo = trim($row['lot_no']) ?: null;
                $isTemporary = ($row['position_id'] === 'TEMPORARY');
                $initialPosId = $isTemporary ? null : (int) $row['position_id'];

                $remainingToSplit = $totalQty;

                while ($remainingToSplit > 0) {
                    $palletQty = min(1000.0, $remainingToSplit);
                    $remainingToSplit -= $palletQty;

                    $targetPosId = null;

                    if (! $isTemporary) {
                        // Determine target slot position for this specific pallet
                        $targetPosId = $initialPosId;

                        $targetPos = MwhPosition::find($targetPosId);
                        $currentStored = MwhPallet::where('position_id', $targetPosId)->where('current_qty', '>', 0)->sum('current_qty');
                        $availableCap = $targetPos ? ($targetPos->max_capacity - $currentStored) : 0;

                        // If selected target slot does not have enough capacity for this pallet, find next available empty/partial slot in same warehouse
                        if ($availableCap < $palletQty) {
                            $nextPos = MwhPosition::whereHas('rack', fn ($q) => $q->where('whse_id', $this->whse_id))
                                ->whereIn('status', ['EMPTY', 'PARTIAL'])
                                ->where('id', '!=', $targetPosId)
                                ->get()
                                ->first(function ($p) use ($palletQty) {
                                    $stored = MwhPallet::where('position_id', $p->id)->where('current_qty', '>', 0)->sum('current_qty');

                                    return ($p->max_capacity - $stored) >= $palletQty;
                                });

                            if ($nextPos) {
                                $targetPosId = $nextPos->id;
                            }
                        }
                    }

                    $inserted = false;
                    $attempts = 0;

                    while (! $inserted && $attempts < 5) {
                        $attempts++;
                        $palletId = $mwhService->generatePalletId();

                        try {
                            $pallet = MwhPallet::create([
                                'whse_id' => $this->whse_id,
                                'pallet_id' => $palletId,
                                'incoming_header_id' => $header->id,
                                'item_code' => $itemCode,
                                'lot_no' => $lotNo,
                                'initial_qty' => $palletQty,
                                'current_qty' => $palletQty,
                                'uom' => 'KG',
                                'position_id' => $targetPosId,
                                'initial_position_id' => $targetPosId,
                                'status' => 'STORED',
                            ]);
                            $inserted = true;
                        } catch (\Illuminate\Database\QueryException $qe) {
                            if ($attempts >= 5 || ! str_contains($qe->getMessage(), '1062 Duplicate entry')) {
                                throw $qe;
                            }
                            usleep(50000);
                        }
                    }

                    if ($targetPosId) {
                        $mwhService->updatePositionStatus($targetPosId);
                    }

                    $this->createdPallets[] = [
                        'pallet_id' => $pallet->pallet_id,
                        'item_code' => $pallet->item_code,
                        'qty' => $pallet->current_qty,
                        'position' => $pallet->position ? $pallet->position->position_code : '⏳ TEMPORARY',
                        'is_temporary' => $isTemporary,
                    ];
                }
            }

            DB::commit();

            $this->showSuccessModal = true;
            $typeLabel = $this->incoming_type === 'RETURN_PRODUCTION' ? 'Retur Sisa Material Produksi' : 'Kedatangan Supplier';
            session()->flash('success', "Transaksi Penerimaan Material ({$typeLabel}) & Palletizing berhasil disimpan.");
        } catch (\Illuminate\Database\QueryException $qe) {
            DB::rollBack();
            if (str_contains($qe->getMessage(), '1062 Duplicate entry')) {
                session()->flash('error', 'Terjadi duplikasi ID Pallet saat menyimpan. Silakan coba klik Simpan sekali lagi.');
            } else {
                session()->flash('error', 'Terjadi kesalahan basis data saat menyimpan transaksi kedatangan.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan transaksi kedatangan: ' . $e->getMessage());
        }
    }

    public function resetForm(MaterialWarehouseService $mwhService): void
    {
        $this->reset(['incoming_type', 'supplier_name', 'returned_from', 'po_number', 'original_outgoing_code', 'is_prefilled_from_outgoing', 'remarks', 'items', 'createdPallets', 'showSuccessModal']);
        $this->incoming_type = 'SUPPLIER';
        $this->arrival_date = now()->format('Y-m-d');
        $this->document_no = $mwhService->generateDocumentNo();
        $this->addItemRow();
    }

    public function render()
    {
        $positionsQuery = MwhPosition::with('rack')
            ->whereIn('status', ['EMPTY', 'PARTIAL']);

        if ($this->whse_id) {
            $positionsQuery->whereHas('rack', fn ($q) => $q->where('whse_id', $this->whse_id));
        }

        $positions = $positionsQuery->orderBy('position_code', 'asc')->get();

        return view('livewire.material-warehouse.material-incoming-creator', [
            'warehouses' => $this->warehouses,
            'positions' => $positions,
        ]);
    }
}
