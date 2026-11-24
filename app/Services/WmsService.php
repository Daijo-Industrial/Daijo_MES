<?php

namespace App\Services;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WmsService
{
    /**
     * Generate a unique Pallet ID: PLT-YYYYMMDD-XXXX
     */
    public function generatePalletId()
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'PLT-' . $date . '-';

        $lastPallet = WmsPalletForm::withTrashed()
            ->where('pallet_id', 'LIKE', $prefix . '%')
            ->orderBy('pallet_id', 'desc')
            ->first();

        $lastNum = $lastPallet ? (int) substr($lastPallet->pallet_id, -4) + 1 : 1;

        do {
            $code = $prefix . str_pad($lastNum, 4, '0', STR_PAD_LEFT);
            $exists = WmsPalletForm::withTrashed()->where('pallet_id', $code)->exists();
            if ($exists) {
                $lastNum++;
            }
        } while ($exists);

        return $code;
    }

    /**
     * Get list of priority item codes that MUST be placed on Level 1 or 2.
     */
    private function getPriorityItems(): array
    {
        return [
            '216PNM650A', '216PNM651A', '216PNM660A', '216PNM661A',
            '27120M650A', '27120M652A', '27120M654A', '27120M660A', '27120M662A', '27120M664A',
            '27121M650A', '27122M650A', '27122M652A', '27123M650A',
            '27155M650A', '27155M655A', '27155M660A', '27155M665A',
            '27156M650A', '27156M655A', '27156M660A', '27156M665A',
            '27162M650A', '27162M655A', '27162M660A', '27162M665A',
            '27165M650A', '27165M660A', '27167M650A', '27167M660A',
            '27168M650A', '27168M655A', '27168M660A', '27168M665A',
            '27175M650A', '27175M660A', '27180M650A', '27181M650A', '27181M660A',
            '27185M650A', '27185M651A', '27188M650A', '27188M660A',
            '271SGM650A', '271SVM650A', '271SVM660A',
            '27235M650A', '27235M660A', '27237M650A',
            '27250M650A', '27250M660A', '27253M650A', '27253M650A..', '27253M660A',
            '27276M650A', '272P2M650A', '272RDM650A', '272RDM652A',
            '272S1M650A', '272S2M650A', '272UTM650A', '27314N240A',
            '273PFM650A', '273PNM650A', '273PNM650A..', '273PNM652A', '273PNM652A..',
            '273SYM650A', '274PHN240A', '275PXM650A', '275PXM650A..', '275PXM651A', '275PXM651A..',
            '27750M650A', '27750M651A', '27750M660A', '27750M661A',
            '277SGM650A', '277SGM650A..', '277SGM652A', '277SGM652A..',
        ];
    }

    /**
     * Recommend a position for the given customer codes and primary part no.
     * Supports multi-item pallets (mixed customer codes).
     *
     * @param array $customerCodes All customer codes from scanned items
     * @param string $primaryPartNo First/primary item code (for consolidation)
     * @param string|null $warehouseId Optional warehouse ID to scope slot recommendations
     */
    public function recommendPosition(array $customerCodes, string $primaryPartNo, ?string $warehouseId = null): ?WmsPosition
    {
        $uniqueCustomers = array_unique(array_filter($customerCodes));
        $isMixed = count($uniqueCustomers) > 1;

        // Check if item is in the priority list
        $priorityItems = $this->getPriorityItems();
        $cleanPartNo = str_replace('..', '', $primaryPartNo);
        $isPriority = in_array($primaryPartNo, $priorityItems) || in_array($cleanPartNo, $priorityItems) || in_array($cleanPartNo . '..', $priorityItems);

        $applyWarehouseFilter = function ($query) use ($warehouseId) {
            if (! empty($warehouseId)) {
                $query->whereHas('rack', function ($rq) use ($warehouseId) {
                    $rq->where('whse_id', $warehouseId);
                });
            }
        };

        if (! $isMixed && count($uniqueCustomers) === 1) {
            $customerCode = $uniqueCustomers[0];

            // 1. Try PARTIAL slot with same item for consolidation
            $partialSlotQuery = WmsPosition::withCount('palletForms')
                ->where('customer_code', $customerCode)
                ->where('last_item_code', $primaryPartNo)
                ->where('status', 'PARTIAL');

            $applyWarehouseFilter($partialSlotQuery);

            if ($isPriority) {
                $partialSlotQuery->where('level_no', 1);
            } else {
                $partialSlotQuery->where('level_no', '!=', 1);
            }

            $partialSlot = $partialSlotQuery->orderBy('level_no')->orderBy('id')->get()
                ->filter(fn ($pos) => $pos->pallet_forms_count < $pos->max_capacity)
                ->first();

            if ($partialSlot) {
                return $partialSlot;
            }

            // 2. Try EMPTY slot for the same customer
            $emptyCustomerQuery = WmsPosition::where('customer_code', $customerCode)
                ->where('status', 'EMPTY');

            $applyWarehouseFilter($emptyCustomerQuery);

            if ($isPriority) {
                $emptyCustomerQuery->where('level_no', 1);
            } else {
                $emptyCustomerQuery->where('level_no', '!=', 1);
            }

            $emptyCustomer = $emptyCustomerQuery->orderBy('level_no')->orderBy('id')->first();

            if ($emptyCustomer) {
                return $emptyCustomer;
            }
        }

        // 3. Fallback: any EMPTY slot (for mixed customers or when customer slots are full)
        $fallbackQuery = WmsPosition::where('status', 'EMPTY');
        $applyWarehouseFilter($fallbackQuery);
        if ($isPriority) {
            $fallbackQuery->where('level_no', 1);
        } else {
            $fallbackQuery->where('level_no', '!=', 1);
        }

        return $fallbackQuery->orderBy('level_no')->orderBy('id')->first();
    }

    /**
     * Update the status of a position based on its occupancy and max capacity
     */
    public function updatePositionStatus($positionId)
    {
        // Automatically detach any pallet forms from this position that have total_pallet_qty <= 0 or status OUT
        \App\Models\WmsPalletForm::where('position_id', $positionId)
            ->where(function ($q) {
                $q->where('total_pallet_qty', '<=', 0)
                    ->orWhere('status', 'OUT');
            })
            ->update(['position_id' => null]);

        $pos = WmsPosition::with(['palletForms' => function ($q) {
            $q->where('status', 'STORED')->where('total_pallet_qty', '>', 0);
        }])->find($positionId);

        if (! $pos) {
            return;
        }

        $totalQtySum = (float) $pos->palletForms->sum('total_pallet_qty');
        $palletCount = $pos->palletForms->count();

        if ($palletCount <= 0 || $totalQtySum <= 0) {
            $pos->update(['status' => 'EMPTY', 'last_item_code' => null]);
        } elseif ($palletCount >= $pos->max_capacity) {
            $pos->update(['status' => 'FULL']);
        } else {
            $pos->update(['status' => 'PARTIAL']);
        }
    }

    /**
     * Log a pallet transaction (IN/OUT)
     */
    public function logTransaction($palletId, $type, $positionId = null, $notes = null)
    {
        \App\Models\WmsPalletLog::create([
            'pallet_id' => $palletId,
            'transaction_type' => $type,
            'position_id' => $positionId,
            'user_id' => auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get sorted pick recommendations by Shortest Path to Exit Gate.
     *
     * @param string $partNo Part number to pick
     * @param float $neededQty Quantity needed (0 for all available)
     * @return \Illuminate\Support\Collection
     */
    public function getShortestPathPickLocations(string $partNo, float $neededQty = 0)
    {
        $pallets = WmsPalletForm::with(['position.rack'])
            ->whereNotNull('position_id')
            ->where('status', 'STORED')
            ->where('total_pallet_qty', '>', 0)
            ->where(function ($q) use ($partNo) {
                $q->where('part_no', $partNo)
                    ->orWhereHas('details', function ($dq) use ($partNo) {
                        $dq->where('part_no', $partNo)->where('qty', '>', 0);
                    });
            })
            ->get();

        $sorted = $pallets->sortBy(function ($plt) {
            return $plt->position?->distance_score ?? 99999;
        })->values();

        if ($neededQty <= 0) {
            return $sorted;
        }

        $allocated = collect();
        $accum = 0;
        foreach ($sorted as $p) {
            $allocated->push($p);
            $accum += (float) $p->total_pallet_qty;
            if ($accum >= $neededQty) {
                break;
            }
        }

        return $allocated;
    }

    /**
     * Find active rack positions holding the given part number that have remaining capacity.
     */
    public function findExistingRacksForItem(string $partNo, ?string $warehouseId = null, ?string $excludePalletId = null): \Illuminate\Support\Collection
    {
        $cleanPartNo = trim($partNo);
        $cleanPartNoNoDot = rtrim($cleanPartNo, '.');

        $palletMatcher = function ($pq) use ($cleanPartNo, $cleanPartNoNoDot, $excludePalletId) {
            $pq->where('total_pallet_qty', '>', 0)
                ->where('status', '!=', 'OUT')
                ->whereNull('deleted_at');

            if ($excludePalletId) {
                $pq->where('pallet_id', '!=', $excludePalletId);
            }

            $pq->where(function ($q) use ($cleanPartNo, $cleanPartNoNoDot) {
                $q->whereIn('part_no', [$cleanPartNo, $cleanPartNoNoDot])
                    ->orWhereHas('details', fn ($dq) => $dq->whereIn('part_no', [$cleanPartNo, $cleanPartNoNoDot])->whereNull('deleted_at'));
            });
        };

        $query = WmsPosition::with(['rack.warehouse', 'palletForms' => $palletMatcher])
            ->whereHas('palletForms', $palletMatcher);

        if (! empty($warehouseId)) {
            $query->whereHas('rack', fn ($rq) => $rq->where('whse_id', $warehouseId));
        }

        return $query->orderBy('position_code', 'asc')->get();
    }

    /**
     * Split mixed pallet and assign items to their respective racks (consolidate to existing or new pallet).
     *
     * @param array $itemAssignments Keyed by part_no: ['action' => 'MERGE'|'NEW_PALLET', 'target_position_id' => int, 'target_pallet_id' => string|null]
     * @param int|null $forcePositionId If force assigning entire mixed pallet directly to one position
     * @return array Result summary
     */
    public function splitAndAssignMixedPallet(string $sourcePalletId, array $itemAssignments, ?int $forcePositionId = null): array
    {
        return DB::transaction(function () use ($sourcePalletId, $itemAssignments, $forcePositionId) {
            $sourcePallet = WmsPalletForm::with('details')->where('pallet_id', $sourcePalletId)->firstOrFail();
            $affectedPositionIds = [];

            if ($sourcePallet->position_id) {
                $affectedPositionIds[] = $sourcePallet->position_id;
            }

            // Case A: Force direct assignment of mixed pallet to a single rack slot
            if ($forcePositionId) {
                $oldPos = $sourcePallet->position_id;
                $sourcePallet->update([
                    'position_id' => $forcePositionId,
                    'assigned_at' => $sourcePallet->assigned_at ?? now(),
                ]);

                if ($oldPos && $oldPos !== $forcePositionId) {
                    $this->updatePositionStatus($oldPos);
                }
                $this->updatePositionStatus($forcePositionId);
                $this->logTransaction($sourcePalletId, 'ASSIGN_SLOT', $forcePositionId, 'Force assigned mixed pallet to single slot');

                return [
                    'status' => true,
                    'type' => 'FORCE_ASSIGN',
                    'message' => "Pallet {$sourcePalletId} berhasil di-assign langsung ke slot rak.",
                ];
            }

            // Case B: Split & Consolidate items
            $splitRecords = [];
            $allDetails = $sourcePallet->details;

            foreach ($itemAssignments as $partNo => $config) {
                $action = $config['action'] ?? 'NEW_PALLET';
                $targetPosId = ! empty($config['target_position_id']) ? (int) $config['target_position_id'] : null;
                $targetPos = $targetPosId ? WmsPosition::find($targetPosId) : null;

                $cleanPNo = trim($partNo);
                $itemDetails = $allDetails->filter(fn ($d) => in_array(trim($d->part_no), [$cleanPNo, rtrim($cleanPNo, '.')]));
                if ($itemDetails->isEmpty()) {
                    continue;
                }

                $movedQty = (float) $itemDetails->sum('qty');
                $movedBoxes = $itemDetails->count();

                if ($targetPosId) {
                    $affectedPositionIds[] = $targetPosId;
                }

                if ($action === 'MERGE') {
                    // Consolidate into existing pallet at target position
                    $targetPalletId = $config['target_pallet_id'] ?? null;
                    $targetPallet = null;

                    if ($targetPalletId) {
                        $targetPallet = WmsPalletForm::where('pallet_id', $targetPalletId)->first();
                    }
                    if (! $targetPallet && $targetPosId) {
                        $targetPallet = WmsPalletForm::where('position_id', $targetPosId)
                            ->where('status', '!=', 'OUT')
                            ->where('total_pallet_qty', '>', 0)
                            ->first();
                    }

                    if (! $targetPallet) {
                        throw new \Exception("Pallet tujuan untuk konsolidasi item {$partNo} tidak ditemukan pada slot rak yang dipilih.");
                    }

                    // Move boxes to target pallet
                    $cleanPNo = trim($partNo);
                    WmsPalletFormDetail::where('pallet_form_id', $sourcePalletId)
                        ->where(function ($q) use ($cleanPNo) {
                            $q->whereIn('part_no', [$cleanPNo, rtrim($cleanPNo, '.')]);
                        })
                        ->update(['pallet_form_id' => $targetPallet->pallet_id]);

                    // Recalculate target pallet
                    $newTargetQty = WmsPalletFormDetail::where('pallet_form_id', $targetPallet->pallet_id)->sum('qty');
                    $newTargetBoxes = WmsPalletFormDetail::where('pallet_form_id', $targetPallet->pallet_id)->count();

                    $existingMergedFrom = $targetPallet->merged_from ?: [];
                    $existingMergedFrom[] = [
                        'source_pallet_id' => $sourcePalletId,
                        'part_no' => $partNo,
                        'qty' => $movedQty,
                        'box_qty' => $movedBoxes,
                        'merged_at' => now()->format('Y-m-d H:i:s'),
                    ];

                    $targetPallet->update([
                        'total_pallet_qty' => $newTargetQty,
                        'box_qty' => $newTargetBoxes,
                        'merged_from' => $existingMergedFrom,
                    ]);

                    $this->logTransaction(
                        $targetPallet->pallet_id,
                        'CONSOLIDATE_IN',
                        $targetPallet->position_id,
                        "Menerima {$movedBoxes} box ({$movedQty} pcs) {$partNo} dari split pallet {$sourcePalletId}"
                    );

                    $splitRecords[] = [
                        'part_no' => $partNo,
                        'qty' => $movedQty,
                        'box_qty' => $movedBoxes,
                        'action' => 'MERGE',
                        'target_pallet_id' => $targetPallet->pallet_id,
                        'target_position_id' => $targetPallet->position_id,
                        'target_position_code' => $targetPallet->position?->position_code ?? ($targetPos?->position_code ?? '-'),
                    ];
                } else {
                    // Create NEW pallet for this item
                    $newPalletId = $this->generatePalletId();
                    $firstDetail = $itemDetails->first();

                    $newPallet = WmsPalletForm::create([
                        'pallet_id' => $newPalletId,
                        'plant' => $sourcePallet->plant,
                        'position_id' => $targetPosId,
                        'assigned_at' => $targetPosId ? now() : null,
                        'part_no' => $partNo,
                        'model_name' => $firstDetail?->model_name ?: $sourcePallet->model_name,
                        'prod_date' => $sourcePallet->prod_date,
                        'lot_no' => $sourcePallet->lot_no,
                        'delivery_name' => $sourcePallet->delivery_name,
                        'delivery_shift' => $sourcePallet->delivery_shift,
                        'box_qty' => $movedBoxes,
                        'total_pallet_qty' => $movedQty,
                        'status' => 'STORED',
                        'remarks' => "Dibuat dari hasil split pallet {$sourcePalletId}",
                        'split_from_pallet_id' => $sourcePalletId,
                        'sap_sync_status' => 0,
                    ]);

                    // Move details
                    WmsPalletFormDetail::where('pallet_form_id', $sourcePalletId)
                        ->where(function ($q) use ($cleanPNo) {
                            $q->whereIn('part_no', [$cleanPNo, rtrim($cleanPNo, '.')]);
                        })
                        ->update(['pallet_form_id' => $newPalletId]);

                    $this->logTransaction(
                        $newPalletId,
                        'IN',
                        $targetPosId,
                        "Pallet baru dibuat dari hasil split pallet {$sourcePalletId}"
                    );

                    $splitRecords[] = [
                        'part_no' => $partNo,
                        'qty' => $movedQty,
                        'box_qty' => $movedBoxes,
                        'action' => 'NEW_PALLET',
                        'target_pallet_id' => $newPalletId,
                        'target_position_id' => $targetPosId,
                        'target_position_code' => $targetPos?->position_code ?? 'TEMPORARY',
                    ];
                }
            }

            // Recalculate remaining on source pallet
            $remQty = WmsPalletFormDetail::where('pallet_form_id', $sourcePalletId)->sum('qty');
            $remBoxes = WmsPalletFormDetail::where('pallet_form_id', $sourcePalletId)->count();

            if ($remBoxes <= 0 || $remQty <= 0) {
                // All items moved (Worst Case solved)
                $sourcePallet->update([
                    'total_pallet_qty' => 0,
                    'box_qty' => 0,
                    'position_id' => null,
                    'status' => 'RESOLVED',
                    'is_split' => true,
                    'split_into' => $splitRecords,
                    'split_at' => now(),
                ]);

                $this->logTransaction(
                    $sourcePalletId,
                    'SPLIT_RESOLVED',
                    null,
                    'Seluruh item berhasil di-split dan dipindahkan ke rak/pallet tujuan. Pallet asal selesai (0 pcs).'
                );
            } else {
                // Partial split (some items still remain on source pallet)
                $remPartNos = WmsPalletFormDetail::where('pallet_form_id', $sourcePalletId)
                    ->pluck('part_no')->unique()->values();
                $newHeaderPartNo = $remPartNos->count() > 1 ? 'MIXED' : ($remPartNos[0] ?? $sourcePallet->part_no);

                $sourcePallet->update([
                    'total_pallet_qty' => $remQty,
                    'box_qty' => $remBoxes,
                    'part_no' => $newHeaderPartNo,
                    'is_split' => true,
                    'split_into' => $splitRecords,
                    'split_at' => now(),
                ]);

                $this->logTransaction(
                    $sourcePalletId,
                    'SPLIT_PARTIAL',
                    $sourcePallet->position_id,
                    "Sebagian item di-split dari pallet {$sourcePalletId}"
                );
            }

            // Update status of all affected rack positions
            foreach (array_unique(array_filter($affectedPositionIds)) as $posId) {
                $this->updatePositionStatus($posId);
            }

            return [
                'status' => true,
                'type' => 'SPLIT_SUCCESS',
                'source_pallet_id' => $sourcePalletId,
                'split_records' => $splitRecords,
                'is_fully_resolved' => ($remBoxes <= 0),
                'message' => ($remBoxes <= 0)
                    ? "✓ Pallet {$sourcePalletId} berhasil di-solve! Seluruh item berhasil dipindahkan ke rak masing-masing."
                    : "✓ Sebagian item pada pallet {$sourcePalletId} berhasil dipindahkan.",
            ];
        });
    }
}
