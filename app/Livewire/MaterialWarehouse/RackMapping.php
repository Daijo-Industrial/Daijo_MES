<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhIncomingHeader;
use App\Models\MwhWarehouse;
use App\Models\MwhRack;
use App\Models\MwhPosition;
use App\Models\MwhPallet;
use App\Services\MaterialWarehouseService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RackMapping extends Component
{
    public $selectedPositionId;
    public $editPositionCode;
    public $editSlotLabel;
    public $editMaxCapacity;
    public $editStatus;
    public $editLastItemCode;
    
    // Add Material to Slot Form State
    public bool $showAddMaterialForm = false;
    public string $new_item_code = '';
    public string $new_item_description = '';
    public string $new_qty = '';
    public string $new_lot_no = '';
    public string $new_supplier_name = '';
    public string $new_po_number = '';
    public string $new_created_at = '';
    public array $newMaterialSearchResults = [];

    // Public / View-Only State
    public bool $isViewOnly = false;

    // UI & Filter State
    public $selectedWhseId = 'ALL';
    public array $warehouses = [];
    public $showDetail = false;
    public string $searchTerm = '';
    public string $selectedItemFilter = '';
    public string $selectedAreaFilter = 'ALL';
    public bool $showFifoSummaryModal = false;
    public ?string $expandedFifoItemCode = null;

    // Temporary Area Modal & Assignment State
    public bool $showTemporaryModal = false;
    public ?int $selectedTemporaryPalletId = null;
    public ?int $targetPositionIdForTemporary = null;

    protected $queryString = [
        'selectedWhseId' => ['except' => 'ALL'],
        'searchTerm' => ['except' => ''],
        'selectedItemFilter' => ['except' => ''],
        'selectedAreaFilter' => ['except' => 'ALL'],
    ];

    public function mount(?MaterialWarehouseService $mwhService = null): void
    {
        $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            // Seed defaults if empty
            MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        if ($this->selectedWhseId === 'ALL' || empty($this->selectedWhseId)) {
            $this->selectedWhseId = $this->warehouses[0]['id'] ?? 1;
        }

        $mwhService = $mwhService ?? app(MaterialWarehouseService::class);
        $positions = MwhPosition::all();
        foreach ($positions as $pos) {
            $mwhService->updatePositionStatus($pos->id);
        }
    }

    public function toggleFifoItemExpand(string $itemCode): void
    {
        if ($this->expandedFifoItemCode === $itemCode) {
            $this->expandedFifoItemCode = null;
        } else {
            $this->expandedFifoItemCode = $itemCode;
        }
    }

    public function selectPosition($id)
    {
        $this->selectedPositionId = $id;
        $pos = MwhPosition::find($id);
        if ($pos) {
            $this->editPositionCode = $pos->position_code;
            $this->editSlotLabel = $pos->slot_label;
            $this->editMaxCapacity = $pos->max_capacity;
            $this->editStatus = $pos->status;
            $this->editLastItemCode = $pos->last_item_code;
            $this->showDetail = true;
            $this->showAddMaterialForm = false;
            $this->new_created_at = now()->format('Y-m-d');
            $this->reset(['new_item_code', 'new_item_description', 'new_qty', 'new_lot_no', 'new_supplier_name', 'new_po_number', 'newMaterialSearchResults']);
        }
    }

    public function updatedNewItemCode($value): void
    {
        $query = trim($value);
        if (strlen($query) >= 2) {
            $this->newMaterialSearchResults = MasterListMaterial::query()
                ->where('item_code', 'like', '%' . $query . '%')
                ->orWhere('item_description', 'like', '%' . $query . '%')
                ->limit(10)
                ->get(['item_code', 'item_description', 'purchasing_uom'])
                ->toArray();
        } else {
            $this->newMaterialSearchResults = [];
        }
    }

    public function selectNewMaterial(string $itemCode, string $itemDesc): void
    {
        $this->new_item_code            = $itemCode;
        $this->new_item_description     = $itemDesc;
        $this->newMaterialSearchResults = [];
    }

    public function toggleAddMaterialForm(): void
    {
        $this->showAddMaterialForm = !$this->showAddMaterialForm;
        if (!$this->showAddMaterialForm) {
            $this->reset(['new_item_code', 'new_item_description', 'new_qty', 'new_lot_no', 'new_supplier_name', 'new_po_number', 'new_created_at', 'newMaterialSearchResults']);
        } else {
            $this->new_created_at = now()->format('Y-m-d');
        }
    }

    public function storeMaterialToSlot(MaterialWarehouseService $mwhService): void
    {
        $this->validate([
            'selectedPositionId' => 'required|exists:mwh_positions,id',
            'new_item_code'      => 'required|string|exists:master_list_materials,item_code',
            'new_qty'            => 'required|numeric|min:0.01',
            'new_created_at'     => 'required|date',
            'new_lot_no'         => 'nullable|string|max:255',
            'new_supplier_name'  => 'nullable|string|max:255',
            'new_po_number'      => 'nullable|string|max:255',
        ], [
            'new_item_code.required' => 'Part Code material harus diisi.',
            'new_item_code.exists'   => 'Part Code material tidak ada di Master List.',
            'new_qty.required'       => 'Jumlah (KG) harus diisi.',
            'new_created_at.required' => 'Tanggal masuk (FIFO) harus diisi.',
        ]);

        $pos = MwhPosition::with('rack')->find($this->selectedPositionId);
        if (!$pos) return;

        $targetWhseId = $pos->rack?->whse_id ?? (($this->selectedWhseId && $this->selectedWhseId !== 'ALL') ? (int)$this->selectedWhseId : 1);

        try {
            DB::beginTransaction();

            $arrivalDate = date('Y-m-d', strtotime($this->new_created_at));
            $createdAtTimestamp = date('Y-m-d H:i:s', strtotime($this->new_created_at . ' ' . now()->format('H:i:s')));

            $header = MwhIncomingHeader::create([
                'whse_id'       => $targetWhseId,
                'document_no'   => $mwhService->generateDocumentNo(),
                'supplier_name' => trim($this->new_supplier_name) ?: null,
                'po_number'     => trim($this->new_po_number) ?: null,
                'arrival_date'  => $arrivalDate,
                'remarks'       => 'Direct input via Material Warehouse Mapping',
                'created_at'    => $createdAtTimestamp,
            ]);

            $remainingToSplit = (float) $this->new_qty;

            while ($remainingToSplit > 0) {
                $palletQty = min(1000.0, $remainingToSplit);
                $remainingToSplit -= $palletQty;

                $inserted = false;
                $attempts = 0;

                while (!$inserted && $attempts < 5) {
                    $attempts++;
                    $palletId = $mwhService->generatePalletId();

                    try {
                        MwhPallet::create([
                            'whse_id'             => $targetWhseId,
                            'pallet_id'           => $palletId,
                            'incoming_header_id'  => $header->id,
                            'item_code'           => strtoupper(trim($this->new_item_code)),
                            'lot_no'              => trim($this->new_lot_no) ?: null,
                            'initial_qty'         => $palletQty,
                            'current_qty'         => $palletQty,
                            'uom'                 => 'KG',
                            'position_id'         => $pos->id,
                            'initial_position_id' => $pos->id,
                            'status'              => 'STORED',
                            'created_at'          => $createdAtTimestamp,
                        ]);
                        $inserted = true;
                    } catch (\Illuminate\Database\QueryException $qe) {
                        if ($attempts >= 5 || !str_contains($qe->getMessage(), '1062 Duplicate entry')) {
                            throw $qe;
                        }
                        usleep(50000); // 50ms pause before retrying next ID
                    }
                }
            }

            $mwhService->updatePositionStatus($pos->id);

            DB::commit();

            session()->flash('success', "Material {$this->new_item_code} ({$this->new_qty} KG, Tgl FIFO: {$arrivalDate}) berhasil ditambahkan ke slot {$pos->position_code}.");

            $this->reset(['new_item_code', 'new_item_description', 'new_qty', 'new_lot_no', 'new_supplier_name', 'new_po_number', 'new_created_at', 'newMaterialSearchResults', 'showAddMaterialForm']);
        } catch (\Illuminate\Database\QueryException $qe) {
            DB::rollBack();
            if (str_contains($qe->getMessage(), '1062 Duplicate entry')) {
                session()->flash('error', 'Terjadi duplikasi ID Pallet saat menyimpan. Silakan coba klik tombol Simpan sekali lagi.');
            } else {
                session()->flash('error', 'Terjadi kesalahan basis data saat menyimpan material ke slot.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menambahkan material ke slot: ' . $e->getMessage());
        }
    }

    public function toggleQcHoldPallet($palletId, ?string $reason = null): void
    {
        $pallet = MwhPallet::findOrFail($palletId);
        $newStatus = !$pallet->is_qc_hold;
        
        $pallet->update([
            'is_qc_hold' => $newStatus,
            'qc_hold_reason' => $newStatus ? (trim($reason) ?: 'Di-hold oleh QC saat monitoring mapping') : null,
        ]);

        $statusText = $newStatus ? 'di-HOLD (Karantina QC)' : 'di-RELEASE (Bebas QC)';
        session()->flash('success', "Status Pallet {$pallet->pallet_id} berhasil diubah menjadi {$statusText}.");
        
        if ($this->selectedPositionId) {
            $this->selectPosition($this->selectedPositionId);
        }
    }

    public function openTemporaryModal(): void
    {
        $this->showTemporaryModal = true;
        $this->selectedTemporaryPalletId = null;
        $this->targetPositionIdForTemporary = null;
    }

    public function closeTemporaryModal(): void
    {
        $this->showTemporaryModal = false;
        $this->selectedTemporaryPalletId = null;
        $this->targetPositionIdForTemporary = null;
    }

    public function assignPalletToPosition(int $palletId, int $positionId, MaterialWarehouseService $mwhService): void
    {
        try {
            DB::beginTransaction();

            $pos = MwhPosition::with('rack')->findOrFail($positionId);
            $pallet = MwhPallet::findOrFail($palletId);

            $currentStored = MwhPallet::where('position_id', $pos->id)->where('current_qty', '>', 0)->sum('current_qty');
            $availableCap = (float) $pos->max_capacity - (float) $currentStored;

            if ($availableCap < (float) $pallet->current_qty) {
                session()->flash('error', "Kapasitas slot {$pos->position_code} tersisa " . number_format($availableCap, 2) . " KG, tidak muat untuk pallet seberat " . number_format($pallet->current_qty, 2) . " KG.");
                DB::rollBack();
                return;
            }

            $pallet->update([
                'position_id'         => $pos->id,
                'initial_position_id' => $pallet->initial_position_id ?: $pos->id,
            ]);

            $mwhService->updatePositionStatus($pos->id);

            DB::commit();

            session()->flash('success', "Pallet {$pallet->pallet_id} ({$pallet->item_code}) berhasil dimasukkan ke Slot {$pos->position_code}.");

            if ($this->selectedPositionId) {
                $this->selectPosition($this->selectedPositionId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memasukkan pallet ke slot: ' . $e->getMessage());
        }
    }

    public function assignTemporaryPalletToSlot(int $palletId, MaterialWarehouseService $mwhService): void
    {
        if (!$this->selectedPositionId) {
            session()->flash('error', 'Pilih slot rak tujuan terlebih dahulu.');
            return;
        }

        $this->assignPalletToPosition($palletId, (int) $this->selectedPositionId, $mwhService);
    }

    public function deletePalletFromSlot($palletId, MaterialWarehouseService $mwhService): void
    {
        try {
            DB::beginTransaction();

            $pallet = MwhPallet::findOrFail($palletId);
            $posId = $pallet->position_id;
            $palletCode = $pallet->pallet_id;

            // Also delete related outgoing transaction history for this pallet
            \App\Models\MwhOutgoing::where('pallet_id', $palletCode)->delete();
            $pallet->delete();

            if ($posId) {
                $mwhService->updatePositionStatus($posId);
            }

            DB::commit();
            session()->flash('success', "Pallet {$palletCode} berhasil dihapus dari slot.");
            
            if ($this->selectedPositionId) {
                $this->selectPosition($this->selectedPositionId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus pallet dari slot: ' . $e->getMessage());
        }
    }

    public function saveSettings()
    {
        $this->validate([
            'editPositionCode' => [
                'required', 
                Rule::unique('mwh_positions', 'position_code')
                    ->ignore($this->selectedPositionId)
                    ->whereNull('deleted_at')
            ],
            'editSlotLabel' => 'nullable|string',
            'editMaxCapacity' => 'required|numeric|min:0',
            'editStatus' => 'required|in:EMPTY,PARTIAL,FULL',
        ]);

        $pos = MwhPosition::find($this->selectedPositionId);
        if ($pos) {
            $pos->update([
                'position_code' => strtoupper($this->editPositionCode),
                'slot_label' => $this->editSlotLabel,
                'max_capacity' => $this->editMaxCapacity,
                'status' => $this->editStatus,
                'last_item_code' => $this->editLastItemCode ?: null,
            ]);

            session()->flash('success', 'Pengaturan slot ' . $pos->position_code . ' berhasil disimpan.');
            $this->showDetail = false;
        }
    }

    public function resetSlot()
    {
        $pos = MwhPosition::find($this->selectedPositionId);
        if ($pos) {
            try {
                DB::beginTransaction();

                MwhPallet::where('position_id', $pos->id)->update([
                    'position_id' => null,
                    'status' => 'EMPTY',
                ]);

                $pos->update([
                    'status' => 'EMPTY',
                    'last_item_code' => null,
                ]);

                DB::commit();
                session()->flash('success', 'Slot ' . $pos->position_code . ' berhasil direset (kosong).');
                $this->showDetail = false;
            } catch (\Exception $e) {
                DB::rollBack();
                session()->flash('error', 'Gagal mereset slot: ' . $e->getMessage());
            }
        }
    }

    public $newRackCode;
    public $newLevels = 2;
    public $newSlotsPerLevel = 4;
    public $newMaxCapacity = 1000;
    public $showAddRackModal = false;

    public function createNewRack()
    {
        $targetWhseId = ($this->selectedWhseId && $this->selectedWhseId !== 'ALL') 
            ? (int)$this->selectedWhseId 
            : (MwhWarehouse::where('whse_code', 'KBN')->first()?->id ?? 1);

        $this->validate([
            'newRackCode' => [
                'required', 
                Rule::unique('mwh_racks', 'rack_code')
                    ->where(fn($q) => $q->where('whse_id', $targetWhseId)->whereNull('deleted_at'))
            ],
            'newLevels' => 'required|integer|min:1',
            'newSlotsPerLevel' => 'required|integer|min:1',
            'newMaxCapacity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $whse = MwhWarehouse::find($targetWhseId) ?? MwhWarehouse::first();

            $rack = MwhRack::create([
                'whse_id' => $whse->id,
                'rack_code' => strtoupper($this->newRackCode),
            ]);

            for ($l = 1; $l <= $this->newLevels; $l++) {
                for ($s = 1; $s <= $this->newSlotsPerLevel; $s++) {
                    $levelStr = str_pad($l, 2, '0', STR_PAD_LEFT);
                    $slotStr = str_pad($s, 2, '0', STR_PAD_LEFT);
                    
                    MwhPosition::create([
                        'rack_id' => $rack->id,
                        'level_no' => $l,
                        'slot_no' => $s,
                        'position_code' => strtoupper($rack->rack_code) . "-L{$levelStr}-S{$slotStr}",
                        'slot_label' => 'Slot ' . $l . '-' . $s,
                        'max_capacity' => $this->newMaxCapacity,
                    ]);
                }
            }

            DB::commit();
            session()->flash('success', "Rak Material {$rack->rack_code} ({$whse->whse_name}) berhasil dibuat.");
            
            $this->reset(['newRackCode', 'showAddRackModal']);
            $this->newMaxCapacity = 1000;
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal membuat rak material: ' . $e->getMessage());
        }
    }

    public function deleteRack($rackId)
    {
        try {
            DB::beginTransaction();

            $rack = MwhRack::find($rackId);
            if ($rack) {
                $posIds = $rack->positions()->pluck('id');
                MwhPallet::whereIn('position_id', $posIds)->update([
                    'position_id' => null,
                    'status' => 'EMPTY',
                ]);

                $rack->positions()->delete();
                $rack->delete();

                DB::commit();
                session()->flash('success', 'Rak Material ' . $rack->rack_code . ' dan seluruh slotnya berhasil dihapus.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus rak material: ' . $e->getMessage());
        }
    }

    public function selectSearchSuggestion(string $itemCode): void
    {
        $this->selectedItemFilter = $itemCode;
        $this->searchTerm = $itemCode;
    }

    public function render()
    {
        $rackQuery = MwhRack::with(['warehouse', 'positions' => function($query) {
            $query->orderBy('level_no', 'desc')
                  ->orderBy('slot_no', 'asc')
                  ->with(['pallets' => function($pq) {
                      $pq->where('current_qty', '>', 0)->with('material');
                  }]);
        }]);

        if ($this->selectedWhseId && $this->selectedWhseId !== 'ALL') {
            $rackQuery->where('whse_id', $this->selectedWhseId);
        }

        $racks = $rackQuery->get();

        $activeWhseId = ($this->selectedWhseId && $this->selectedWhseId !== 'ALL') ? (int)$this->selectedWhseId : null;

        $availableItemCodesQuery = MwhPallet::query()
            ->where('current_qty', '>', 0);
        
        if ($activeWhseId) {
            $availableItemCodesQuery->where(function($q) use ($activeWhseId) {
                $q->where('whse_id', $activeWhseId)
                  ->orWhereHas('position.rack', fn($rq) => $rq->where('whse_id', $activeWhseId));
            });
        }

        $availableItemCodes = $availableItemCodesQuery
            ->distinct()
            ->orderBy('item_code', 'asc')
            ->pluck('item_code')
            ->toArray();

        $matchingPositionIds = [];
        $queryStr = trim($this->searchTerm);
        $itemFilter = trim($this->selectedItemFilter);

        if (strlen($queryStr) > 0 || strlen($itemFilter) > 0) {
            $matchingPositionIds = MwhPosition::query()
                ->where(function($q) use ($queryStr, $itemFilter, $activeWhseId) {
                    if ($activeWhseId) {
                        $q->whereHas('rack', fn($rq) => $rq->where('whse_id', $activeWhseId));
                    }

                    if (strlen($itemFilter) > 0) {
                        $q->whereHas('pallets', function($pq) use ($itemFilter) {
                            $pq->where('current_qty', '>', 0)->where('item_code', $itemFilter);
                        });
                    }

                    if (strlen($queryStr) > 0) {
                        $q->where(function($sub) use ($queryStr) {
                            $sub->where('position_code', 'like', '%' . $queryStr . '%')
                              ->orWhere('slot_label', 'like', '%' . $queryStr . '%')
                              ->orWhereHas('rack', function($rq) use ($queryStr) {
                                  $rq->where('rack_code', 'like', '%' . $queryStr . '%');
                              })
                              ->orWhereHas('pallets', function($pq) use ($queryStr) {
                                  $pq->where('current_qty', '>', 0)
                                     ->where(function($subQ) use ($queryStr) {
                                         $subQ->where('pallet_id', 'like', '%' . $queryStr . '%')
                                              ->orWhere('item_code', 'like', '%' . $queryStr . '%')
                                              ->orWhere('lot_no', 'like', '%' . $queryStr . '%')
                                              ->orWhereHas('material', function($mq) use ($queryStr) {
                                                  $mq->where('item_description', 'like', '%' . $queryStr . '%');
                                              });
                                     });
                              });
                        });
                    }
                })
                ->pluck('id')
                ->toArray();
        }

        // Live Search Suggestions for Autocomplete Dropdown
        $searchSuggestions = [];
        if (strlen($queryStr) >= 2) {
            $st = '%' . $queryStr . '%';
            $sugQuery = MwhPallet::with('material')
                ->where('current_qty', '>', 0)
                ->where(function($q) use ($st) {
                    $q->where('item_code', 'like', $st)
                      ->orWhere('pallet_id', 'like', $st)
                      ->orWhere('lot_no', 'like', $st)
                      ->orWhereHas('material', function($mq) use ($st) {
                          $mq->where('item_description', 'like', $st);
                      });
                });

            if ($activeWhseId) {
                $sugQuery->where(function($q) use ($activeWhseId) {
                    $q->where('whse_id', $activeWhseId)
                      ->orWhereHas('position.rack', fn($rq) => $rq->where('whse_id', $activeWhseId));
                });
            }

            $searchSuggestions = $sugQuery
                ->limit(10)
                ->get()
                ->map(function($p) {
                    return [
                        'item_code'        => $p->item_code,
                        'item_description' => $p->material ? $p->material->item_description : '-',
                        'pallet_id'        => $p->pallet_id,
                    ];
                })
                ->unique('item_code')
                ->values()
                ->toArray();
        }

        // Special Active Item FIFO Summary Banner (Appears right above slot status legend bar when item is selected/searched)
        $targetItemCode = $itemFilter ?: $queryStr;
        $activeItemSummary = null;

        if (strlen($targetItemCode) > 0) {
            $itemPalletsQuery = MwhPallet::with(['position.rack', 'material', 'incomingHeader'])
                ->where('current_qty', '>', 0)
                ->where(function($q) use ($targetItemCode) {
                    $q->where('item_code', $targetItemCode)
                      ->orWhere('item_code', 'like', '%' . $targetItemCode . '%');
                });

            if ($activeWhseId) {
                $itemPalletsQuery->where(function($q) use ($activeWhseId) {
                    $q->where('whse_id', $activeWhseId)
                      ->orWhereHas('position.rack', fn($rq) => $rq->where('whse_id', $activeWhseId));
                });
            }

            $itemPallets = $itemPalletsQuery->orderBy('created_at', 'asc')->get();

            if ($itemPallets->isNotEmpty()) {
                // Priority 1: Check for exact item_code match with $targetItemCode
                $exactMatches = $itemPallets->where('item_code', $targetItemCode)->values();
                if ($exactMatches->isNotEmpty()) {
                    $matchedItemCode = $targetItemCode;
                    $exactPallets = $exactMatches;
                } else {
                    // Priority 2: Fallback to the first item_code from ordered results (for partial search queries)
                    $matchedItemCode = $itemPallets->first()->item_code;
                    $exactPallets = $itemPallets->where('item_code', $matchedItemCode)->values();
                }

                $firstPallet = $exactPallets->first() ?? $itemPallets->first();

                $activeItemSummary = [
                    'item_code'        => $matchedItemCode,
                    'item_description' => $firstPallet->material ? $firstPallet->material->item_description : '-',
                    'total_qty'        => $exactPallets->sum('current_qty'),
                    'pallet_count'     => $exactPallets->count(),
                    'oldest_date'      => $firstPallet->created_at,
                    'pallets'          => $exactPallets,
                ];
            }
        }

        $selectedPosData = $this->selectedPositionId 
            ? MwhPosition::with([
                'rack.warehouse', 
                'pallets' => function($q) {
                    $q->where('current_qty', '>', 0)
                      ->with(['material', 'incomingHeader']);
                }
            ])->find($this->selectedPositionId) 
            : null;

        $fifoSummaryData = [];
        if ($this->showFifoSummaryModal) {
            $fifoQuery = MwhPallet::with(['position.rack', 'material', 'incomingHeader'])
                ->where('current_qty', '>', 0);

            if ($activeWhseId) {
                $fifoQuery->where(function($q) use ($activeWhseId) {
                    $q->where('whse_id', $activeWhseId)
                      ->orWhereHas('position.rack', fn($rq) => $rq->where('whse_id', $activeWhseId));
                });
            }

            $groupedPallets = $fifoQuery->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('item_code');

            foreach ($groupedPallets as $itemCode => $pallets) {
                $firstPallet = $pallets->first();
                $palletIds = $pallets->pluck('pallet_id')->toArray();

                $outgoings = \App\Models\MwhOutgoing::with(['pallet', 'position'])
                    ->whereIn('pallet_id', $palletIds)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $fifoSummaryData[] = [
                    'item_code'        => $itemCode,
                    'item_description' => $firstPallet->material ? $firstPallet->material->item_description : '-',
                    'total_qty'        => $pallets->sum('current_qty'),
                    'pallet_count'     => $pallets->count(),
                    'oldest_date'      => $firstPallet->created_at,
                    'pallets'          => $pallets,
                    'outgoings'        => $outgoings,
                ];
            }

            usort($fifoSummaryData, function($a, $b) {
                return strcmp($a['item_code'], $b['item_code']);
            });
        }

        $groupedRacks = [];
        $availableAreas = [];

        foreach ($racks as $rack) {
            $areaName = 'LAINNYA';
            if (preg_match('/(?:RAK-)?([A-Za-z]+)/i', $rack->rack_code, $matches)) {
                $areaName = 'AREA ' . strtoupper($matches[1]);
            }

            if (!in_array($areaName, $availableAreas)) {
                $availableAreas[] = $areaName;
            }

            if (empty($this->selectedAreaFilter) || $this->selectedAreaFilter === 'ALL' || $this->selectedAreaFilter === $areaName) {
                $groupedRacks[$areaName][] = $rack;
            }
        }

        sort($availableAreas);
        ksort($groupedRacks);

        $temporaryPallets = MwhPallet::with(['material', 'incomingHeader'])
            ->whereNull('position_id')
            ->where('current_qty', '>', 0)
            ->when($activeWhseId, fn($q) => $q->where('whse_id', $activeWhseId))
            ->orderBy('created_at', 'asc')
            ->get();

        $availablePositionsForWarehouse = MwhPosition::with('rack')
            ->whereIn('status', ['EMPTY', 'PARTIAL'])
            ->when($activeWhseId, fn($q) => $q->whereHas('rack', fn($rq) => $rq->where('whse_id', $activeWhseId)))
            ->orderBy('position_code', 'asc')
            ->get();

        return view('livewire.material-warehouse.rack-mapping', [
            'warehouses'                     => $this->warehouses,
            'racks'                          => $racks,
            'groupedRacks'                   => $groupedRacks,
            'availableAreas'                 => $availableAreas,
            'selectedPosData'                => $selectedPosData,
            'matchingPositionIds'            => $matchingPositionIds,
            'availableItemCodes'             => $availableItemCodes,
            'searchSuggestions'              => $searchSuggestions,
            'activeItemSummary'              => $activeItemSummary,
            'fifoSummaryData'                => $fifoSummaryData,
            'temporaryPallets'               => $temporaryPallets,
            'availablePositionsForWarehouse' => $availablePositionsForWarehouse,
        ]);
    }
}
