<?php

namespace App\Services;

use App\Models\SoData;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPickingDetail;
use App\Models\WmsPickingHeader;
use Illuminate\Support\Facades\DB;

class WmsPickingService
{
    /**
     * Load order item details from a Sales Order / DO Doc Num in so_datas.
     */
    public function loadItemsFromDocNum(string $docNum): array
    {
        return SoData::where('doc_num', $docNum)
            ->select('item_code', 'item_name', 'quantity')
            ->get()
            ->map(fn ($row) => [
                'item_code' => $row->item_code,
                'item_name' => $row->item_name,
                'quantity' => (float) $row->quantity,
            ])
            ->toArray();
    }

    /**
     * Calculate WMS Pallet picking routes enforcing FIFO sequence.
     *
     * @param array $requestedItems Format: [['item_code' => '...', 'item_name' => '...', 'quantity' => 100], ...]
     */
    public function calculateFifoPickingRoute(array $requestedItems): array
    {
        $instructions = [];
        $unallocated = [];

        foreach ($requestedItems as $req) {
            $itemCode = trim($req['item_code']);
            $needed = (float) $req['quantity'];

            if (empty($itemCode) || $needed <= 0) {
                continue;
            }

            // Find all stored detail items for this Part No, sorted oldest first (FIFO)
            $boxes = WmsPalletFormDetail::where('part_no', $itemCode)
                ->whereHas('header', function ($q) {
                    $q->where('status', 'STORED');
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $allocatedQty = 0;
            $fifoSeq = 1;

            foreach ($boxes as $box) {
                if ($needed <= 0) {
                    break;
                }

                $qtyInBox = (float) $box->qty;
                $pickQty = min($qtyInBox, $needed);

                $instructions[] = [
                    'item_code' => $itemCode,
                    'model_name' => $box->model_name ?: ($req['item_name'] ?? 'N/A'),
                    'spk_no' => $box->spk_no,
                    'label' => $box->label,
                    'pallet_id' => $box->pallet_form_id,
                    'position_code' => $box->header && $box->header->position ? $box->header->position->position_code : 'N/A',
                    'qty_to_pick' => $pickQty,
                    'created_at' => $box->created_at ? $box->created_at->format('Y-m-d H:i:s') : 'N/A',
                    'fifo_seq' => $fifoSeq++,
                    'status' => 'AVAILABLE',
                    'notes' => '',
                ];

                $needed -= $pickQty;
                $allocatedQty += $pickQty;
            }

            // If we still need more stock, add a deficient row
            if ($needed > 0) {
                $unallocated[] = [
                    'item_code' => $itemCode,
                    'model_name' => $req['item_name'] ?? 'N/A',
                    'spk_no' => 'N/A',
                    'label' => 'N/A',
                    'pallet_id' => 'KOSONG',
                    'position_code' => 'KOSONG',
                    'qty_to_pick' => $needed,
                    'created_at' => '',
                    'fifo_seq' => null,
                    'status' => $allocatedQty > 0 ? 'STOCK_SHORTAGE' : 'OUT_OF_STOCK',
                    'notes' => $allocatedQty > 0
                        ? "Kekurangan {$needed} pcs di gudang."
                        : 'Barang tidak ditemukan di rak gudang.',
                ];
            }
        }

        // Sort allocated instructions by Position Code -> Pallet ID alphabetically to optimize picking route
        usort($instructions, function ($a, $b) {
            $posCompare = strcmp($a['position_code'], $b['position_code']);
            if ($posCompare !== 0) {
                return $posCompare;
            }

            return strcmp($a['pallet_id'], $b['pallet_id']);
        });

        // Append missing/deficient rows at the bottom
        return array_merge($instructions, $unallocated);
    }

    /**
     * Create and save a new picking document based on FIFO routing.
     */
    public function createPickingList(?string $docNum, array $requestedItems, ?int $userId): WmsPickingHeader
    {
        // 1. Calculate FIFO route first
        $route = $this->calculateFifoPickingRoute($requestedItems);

        // 2. Generate unique Picking No: PCK-Ymd-XXXX
        $todayStr = now()->format('Ymd');
        $countToday = WmsPickingHeader::whereDate('created_at', now()->toDateString())->count();
        $seq = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
        $pickingNo = "PCK-{$todayStr}-{$seq}";

        return DB::transaction(function () use ($pickingNo, $docNum, $userId, $route) {
            // 3. Create header
            $header = WmsPickingHeader::create([
                'picking_no' => $pickingNo,
                'doc_num' => $docNum ?: null,
                'status' => 'PENDING',
                'created_by' => $userId,
            ]);

            // 4. Save detail records
            foreach ($route as $inst) {
                WmsPickingDetail::create([
                    'picking_header_id' => $header->id,
                    'item_code' => $inst['item_code'],
                    'model_name' => $inst['model_name'],
                    'spk_no' => $inst['spk_no'] !== 'N/A' ? $inst['spk_no'] : null,
                    'label' => $inst['label'] !== 'N/A' ? $inst['label'] : null,
                    'pallet_id' => $inst['pallet_id'] !== 'KOSONG' ? $inst['pallet_id'] : null,
                    'position_code' => $inst['position_code'] !== 'KOSONG' ? $inst['position_code'] : null,
                    'qty_to_pick' => $inst['qty_to_pick'],
                    'qty_picked' => 0.00,
                    'is_picked' => false,
                    'fifo_seq' => $inst['fifo_seq'],
                    'status' => $inst['status'],
                    'notes' => $inst['notes'] ?: null,
                ]);
            }

            return $header;
        });
    }

    /**
     * Toggle the picked checkbox and update database values.
     */
    public function togglePickState(int $detailId, bool $isPicked): WmsPickingDetail
    {
        return DB::transaction(function () use ($detailId, $isPicked) {
            $detail = WmsPickingDetail::findOrFail($detailId);
            $detail->update([
                'is_picked' => $isPicked,
                'qty_picked' => $isPicked ? $detail->qty_to_pick : 0.00,
            ]);

            // Recalculate and update the parent header status
            $header = $detail->header;

            // Look for details that are AVAILABLE/SHORTAGE but not yet picked.
            // Ignore OUT_OF_STOCK details as they cannot be physically picked.
            $pendingCount = $header->details()
                ->where('status', '!=', 'OUT_OF_STOCK')
                ->where('is_picked', false)
                ->count();

            $newStatus = $pendingCount === 0 ? 'COMPLETED' : 'PICKING';

            $header->update(['status' => $newStatus]);

            return $detail;
        });
    }
}
