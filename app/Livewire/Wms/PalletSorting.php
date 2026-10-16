<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PalletSorting extends Component
{
    // Search fields
    public string $palletSearchInput = '';

    public string $boxScanInput = '';

    // Workspace state
    public array $workspacePallets = []; // Holds arrays representing pallets in the workspace

    // UI states
    public ?string $highlightedBoxCid = null;

    public string $selectedBoxCid = '';

    public string $selectedBoxLabel = '';

    public string $selectedBoxSourcePalletId = '';

    public function mount(): void
    {
        // Start with an empty workspace
        $this->workspacePallets = [];
    }

    /**
     * Add a pallet to the workspace lanes.
     */
    public function addPallet(): void
    {
        $palletId = trim($this->palletSearchInput);
        $this->palletSearchInput = '';

        if (empty($palletId)) {
            session()->flash('error', 'Pallet ID tidak boleh kosong.');

            return;
        }

        // Check if already in workspace
        foreach ($this->workspacePallets as $wp) {
            if ($wp['pallet_id'] === $palletId) {
                session()->flash('error', "Pallet {$palletId} sudah ada di dalam workspace.");

                return;
            }
        }

        $pallet = WmsPalletForm::with(['details' => function ($q) {
            $q->whereNull('deleted_at');
        }, 'position'])->where('pallet_id', $palletId)->first();

        if (! $pallet) {
            session()->flash('error', "Pallet ID \"{$palletId}\" tidak ditemukan.");

            return;
        }

        $boxes = [];
        foreach ($pallet->details as $detail) {
            $boxes[] = [
                'id' => $detail->id,
                'cid' => 'c_' . uniqid(),
                'spk_no' => $detail->spk_no,
                'part_no' => $detail->part_no,
                'model_name' => $detail->model_name,
                'qty' => (float) $detail->qty,
                'warehouse' => $detail->warehouse,
                'label' => $detail->label,
                'is_no_label' => (bool) $detail->is_no_label,
                'no_label_reason' => $detail->no_label_reason,
            ];
        }

        $this->workspacePallets[] = [
            'pallet_id' => $pallet->pallet_id,
            'is_new' => false,
            'position_id' => $pallet->position_id,
            'position_code' => $pallet->position ? $pallet->position->position_code : 'N/A',
            'prod_date' => $pallet->prod_date,
            'lot_no' => $pallet->lot_no,
            'delivery_name' => $pallet->delivery_name,
            'delivery_shift' => $pallet->delivery_shift,
            'remarks' => $pallet->remarks,
            'boxes' => $boxes,
        ];

        session()->flash('success', "Pallet {$palletId} berhasil dimuat.");
    }

    /**
     * Add a brand new empty target pallet lane.
     */
    public function addNewTargetPallet(WmsService $wmsService): void
    {
        $newPalletId = $wmsService->generatePalletId();

        $this->workspacePallets[] = [
            'pallet_id' => $newPalletId,
            'is_new' => true,
            'position_id' => null,
            'position_code' => 'Belum Ditentukan',
            'prod_date' => now()->format('Y-m-d'),
            'lot_no' => '',
            'delivery_name' => auth()->user()->name ?? 'Sorting Operator',
            'delivery_shift' => '1',
            'remarks' => 'Hasil Sorting/Konsolidasi',
            'boxes' => [],
        ];

        session()->flash('success', "Pallet Target Baru \"{$newPalletId}\" dibuat.");
    }

    /**
     * Remove a pallet lane from workspace without saving.
     */
    public function removePalletFromWorkspace(string $palletId): void
    {
        $this->workspacePallets = array_values(array_filter($this->workspacePallets, function ($wp) use ($palletId) {
            return $wp['pallet_id'] !== $palletId;
        }));
    }

    /**
     * Move a box from its current pallet to a target pallet in memory.
     */
    public function moveBox(string $boxCid, string $targetPalletId): void
    {
        $boxToMove = null;
        $sourcePalletIdx = -1;
        $boxIdx = -1;

        // Find the box and its source pallet
        foreach ($this->workspacePallets as $pIdx => $pallet) {
            foreach ($pallet['boxes'] as $bIdx => $box) {
                if ($box['cid'] === $boxCid) {
                    $boxToMove = $box;
                    $sourcePalletIdx = $pIdx;
                    $boxIdx = $bIdx;
                    break 2;
                }
            }
        }

        if (! $boxToMove) {
            return;
        }

        // Remove from source
        array_splice($this->workspacePallets[$sourcePalletIdx]['boxes'], $boxIdx, 1);

        // Add to target
        foreach ($this->workspacePallets as &$pallet) {
            if ($pallet['pallet_id'] === $targetPalletId) {
                $pallet['boxes'][] = $boxToMove;
                break;
            }
        }

        $this->selectedBoxCid = '';
        $this->selectedBoxLabel = '';
        $this->selectedBoxSourcePalletId = '';
    }

    /**
     * Move all boxes of a specific SPK from a source pallet to a target pallet.
     */
    public function moveAllBoxesBySpk(string $sourcePalletId, string $spkNo, string $targetPalletId): void
    {
        $boxesToMove = [];
        $sourceIdx = -1;

        // Find source
        foreach ($this->workspacePallets as $pIdx => $pallet) {
            if ($pallet['pallet_id'] === $sourcePalletId) {
                $sourceIdx = $pIdx;
                break;
            }
        }

        if ($sourceIdx === -1) {
            return;
        }

        // Extract matching boxes
        $remainingBoxes = [];
        foreach ($this->workspacePallets[$sourceIdx]['boxes'] as $box) {
            if ($box['spk_no'] === $spkNo) {
                $boxesToMove[] = $box;
            } else {
                $remainingBoxes[] = $box;
            }
        }

        $this->workspacePallets[$sourceIdx]['boxes'] = $remainingBoxes;

        // Add to target
        foreach ($this->workspacePallets as &$pallet) {
            if ($pallet['pallet_id'] === $targetPalletId) {
                $pallet['boxes'] = array_merge($pallet['boxes'], $boxesToMove);
                break;
            }
        }
    }

    /**
     * Scan box label barcode to quickly highlight and select it for moving.
     */
    public function scanBox(): void
    {
        $label = trim($this->boxScanInput);
        $this->boxScanInput = '';

        if (empty($label)) {
            return;
        }

        // Find in workspace
        foreach ($this->workspacePallets as $pallet) {
            foreach ($pallet['boxes'] as $box) {
                if ($box['label'] === $label) {
                    $this->highlightedBoxCid = $box['cid'];
                    $this->selectedBoxCid = $box['cid'];
                    $this->selectedBoxLabel = $box['label'];
                    $this->selectedBoxSourcePalletId = $pallet['pallet_id'];
                    $this->dispatch('box-selected');

                    return;
                }
            }
        }

        session()->flash('error', "Box dengan label \"{$label}\" tidak ditemukan di workspace.");
    }

    /**
     * Select a box manually by clicking.
     */
    public function selectBoxManual(string $cid, string $label, string $sourcePalletId): void
    {
        $this->selectedBoxCid = $cid;
        $this->selectedBoxLabel = $label;
        $this->selectedBoxSourcePalletId = $sourcePalletId;
    }

    /**
     * Get all rack positions.
     */
    public function getAvailablePositionsProperty()
    {
        return WmsPosition::orderBy('position_code')->get();
    }

    /**
     * Change or swap rack position of a pallet.
     */
    public function changePalletPosition(string $palletId, $positionId): void
    {
        $position = WmsPosition::find($positionId);
        if (! $position) {
            return;
        }

        // Find old position code of target pallet
        $oldPositionId = null;
        $oldPositionCode = '';
        foreach ($this->workspacePallets as $pallet) {
            if ($pallet['pallet_id'] === $palletId) {
                $oldPositionId = $pallet['position_id'];
                $oldPositionCode = $pallet['position_code'];
                break;
            }
        }

        // Check if another pallet in workspace is already using the target position
        foreach ($this->workspacePallets as &$pallet) {
            if ($pallet['pallet_id'] !== $palletId && $pallet['position_id'] == $positionId) {
                // Swap it: assign the old position of the target pallet to this conflicting pallet
                $pallet['position_id'] = $oldPositionId;
                $pallet['position_code'] = $oldPositionCode;
                session()->flash('success', "Posini ditukar: Posisi {$position->position_code} kini untuk {$palletId}, dan posisi " . ($oldPositionCode ?: 'kosong') . " kini untuk {$pallet['pallet_id']}.");
                break;
            }
        }

        // Assign the new position to the target pallet
        foreach ($this->workspacePallets as &$pallet) {
            if ($pallet['pallet_id'] === $palletId) {
                $pallet['position_id'] = $position->id;
                $pallet['position_code'] = $position->position_code;
                break;
            }
        }
    }

    /**
     * Reset selection.
     */
    public function clearSelection(): void
    {
        $this->selectedBoxCid = '';
        $this->selectedBoxLabel = '';
        $this->selectedBoxSourcePalletId = '';
    }

    /**
     * Save all sorting movements to the database and re-sync SAP.
     */
    public function applySorting(WmsService $wmsService)
    {
        if (empty($this->workspacePallets)) {
            session()->flash('error', 'Workspace kosong. Silakan muat pallet terlebih dahulu.');

            return;
        }

        try {
            DB::beginTransaction();

            $syncedPalletIds = [];

            foreach ($this->workspacePallets as $pallet) {
                $palletId = $pallet['pallet_id'];
                $isNew = (bool) $pallet['is_new'];
                $boxesCount = count($pallet['boxes']);

                // 1. Check if pallet is completely empty
                if ($boxesCount === 0) {
                    if (! $isNew) {
                        // Soft delete / clean up existing empty pallet
                        $pModel = WmsPalletForm::find($palletId);
                        if ($pModel) {
                            $posId = $pModel->position_id;

                            // Delete details
                            WmsPalletFormDetail::where('pallet_form_id', $palletId)->delete();
                            // Delete header
                            $pModel->delete();

                            // Log Transaction OUT
                            $wmsService->logTransaction($palletId, 'OUT', $posId, 'Pallet emptied during sorting');

                            // Update rack level/capacity
                            if ($posId) {
                                $wmsService->updatePositionStatus($posId);
                            }
                        }
                    }
                    continue;
                }

                // 2. Compute header parameters based on boxes
                $qtySum = array_sum(array_column($pallet['boxes'], 'qty'));
                $allPartNos = array_unique(array_filter(array_column($pallet['boxes'], 'part_no')));
                $isMixed = count($allPartNos) > 1;
                $headerPartNo = $isMixed ? 'MIXED' : ($allPartNos[0] ?? null);

                // Get model name from first box
                $firstBox = $pallet['boxes'][0];
                $headerModelName = $isMixed ? 'MULTI-ITEM' : ($firstBox['model_name'] ?? null);

                $positionId = $pallet['position_id'];

                // 3. Position recommendation for new pallets
                if ($isNew) {
                    $customerCodes = array_map(function ($box) {
                        $item = \App\Models\MasterListItem::where('item_code', $box['part_no'])->first();

                        return $item ? $item->customer_code : '';
                    }, $pallet['boxes']);

                    $posRecommendation = $wmsService->recommendPosition($customerCodes, $headerPartNo ?: '');
                    if (! $posRecommendation) {
                        throw new \Exception("Gagal merekomendasikan posisi rak untuk Pallet Baru {$palletId}. Warehouse Penuh.");
                    }
                    $positionId = $posRecommendation->id;
                }

                // 4. Update or Create Pallet Header
                if ($isNew) {
                    WmsPalletForm::create([
                        'pallet_id' => $palletId,
                        'position_id' => $positionId,
                        'assigned_at' => $positionId ? now() : null,
                        'part_no' => $headerPartNo,
                        'model_name' => $headerModelName,
                        'prod_date' => $pallet['prod_date'],
                        'lot_no' => $pallet['lot_no'],
                        'delivery_name' => $pallet['delivery_name'],
                        'delivery_shift' => $pallet['delivery_shift'],
                        'box_qty' => $boxesCount,
                        'total_pallet_qty' => $qtySum,
                        'remarks' => $pallet['remarks'],
                        'status' => 'STORED',
                        'sap_sync_status' => 0,
                    ]);

                    // Log IN Transaction
                    $wmsService->logTransaction($palletId, 'IN', $positionId, 'Pallet created during sorting');
                } else {
                    $existingP = WmsPalletForm::where('pallet_id', $palletId)->first();
                    WmsPalletForm::where('pallet_id', $palletId)->update([
                        'position_id' => $positionId,
                        'assigned_at' => $positionId ? ($existingP?->assigned_at ?? now()) : null,
                        'part_no' => $headerPartNo,
                        'model_name' => $headerModelName,
                        'box_qty' => $boxesCount,
                        'total_pallet_qty' => $qtySum,
                        'sap_sync_status' => 0, // Reset to re-sync updated pallet
                        'sap_error_msg' => null,
                    ]);
                }

                // 5. Update Details (Boxes)
                foreach ($pallet['boxes'] as $box) {
                    if (isset($box['id']) && $box['id']) {
                        // Move existing box to this pallet
                        WmsPalletFormDetail::where('id', $box['id'])->update([
                            'pallet_form_id' => $palletId,
                            'sap_sync_status' => 0, // Mark for re-sync
                            'sap_error_msg' => null,
                        ]);
                    } else {
                        // Create brand new box if manually added
                        WmsPalletFormDetail::create([
                            'pallet_form_id' => $palletId,
                            'part_no' => $box['part_no'],
                            'model_name' => $box['model_name'],
                            'spk_no' => $box['spk_no'],
                            'qty' => $box['qty'],
                            'warehouse' => $box['warehouse'],
                            'label' => $box['label'],
                            'is_no_label' => $box['is_no_label'],
                            'no_label_reason' => $box['no_label_reason'],
                            'sap_sync_status' => 0,
                        ]);
                    }
                }

                // 6. Update Position Tracking
                $pos = WmsPosition::find($positionId);
                if ($pos) {
                    $pos->update(['last_item_code' => $headerPartNo]);
                    $wmsService->updatePositionStatus($pos->id);
                }

                $syncedPalletIds[] = $palletId;
            }

            DB::commit();

            // Dispatch SAP sync job for all updated pallets
            foreach ($syncedPalletIds as $pId) {
                \App\Jobs\SyncPalletToSapJob::dispatch($pId);
            }

            session()->flash('success', 'Konsolidasi / Sorting pallet berhasil diterapkan!');
            $this->workspacePallets = [];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menerapkan sorting: ' . $e->getMessage());
            session()->flash('error', 'Gagal menerapkan sorting: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.wms.pallet-sorting')
            ->layout('layouts.app');
    }
}
