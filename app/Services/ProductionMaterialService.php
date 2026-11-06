<?php

namespace App\Services;

use App\Models\MasterListMaterial;
use App\Models\MwhOutgoing;
use App\Models\ProductionMaterialUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductionMaterialService
{
    /**
     * Ambil data request / kiriman material dari Store ke Produksi
     */
    public function getIncomingStoreRequests(
        string $search = '',
        string $status = 'ALL',
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        return MwhOutgoing::query()
            ->with(['material', 'pallet', 'position.rack.warehouse'])
            ->where(function ($q) {
                $q->where('issued_to', 'LIKE', '%Produksi%')
                  ->orWhereNull('issued_to')
                  ->orWhere('issued_to', '');
            })
            ->when($status !== 'ALL', function ($q) use ($status) {
                $q->where('received_status', $status);
            })
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('outgoing_date', [$startDate, $endDate]);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('outgoing_code', 'LIKE', "%{$search}%")
                        ->orWhere('pallet_id', 'LIKE', "%{$search}%")
                        ->orWhere('item_code', 'LIKE', "%{$search}%")
                        ->orWhereHas('material', function ($m) use ($search) {
                            $m->where('item_description', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE WHEN received_status = 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('id');
    }

    /**
     * Verifikasi penerimaan material di lantai produksi
     */
    public function verifyReceipt(int $outgoingId, int $userId, string $userName, ?string $notes = null): MwhOutgoing
    {
        return DB::transaction(function () use ($outgoingId, $userId, $userName, $notes) {
            $outgoing = MwhOutgoing::lockForUpdate()->findOrFail($outgoingId);

            if ($outgoing->received_status === 'RECEIVED') {
                throw new InvalidArgumentException("Material dengan kode {$outgoing->outgoing_code} sudah pernah diverifikasi sebelumnya.");
            }

            $outgoing->update([
                'received_status' => 'RECEIVED',
                'received_by' => $userId,
                'received_by_name' => $userName,
                'received_at' => now(),
                'received_notes' => $notes,
            ]);

            return $outgoing;
        });
    }

    /**
     * Verifikasi cepat via scan barcode / QR (outgoing_code atau pallet_id)
     */
    public function verifyReceiptByBarcode(string $code, int $userId, string $userName): MwhOutgoing
    {
        $code = trim($code);
        $outgoing = MwhOutgoing::where(function ($q) use ($code) {
            $q->where('outgoing_code', $code)
              ->orWhere('pallet_id', $code);
        })
        ->where('received_status', 'PENDING')
        ->latest('id')
        ->first();

        if (! $outgoing) {
            $alreadyReceived = MwhOutgoing::where(function ($q) use ($code) {
                $q->where('outgoing_code', $code)
                  ->orWhere('pallet_id', $code);
            })->where('received_status', 'RECEIVED')->first();

            if ($alreadyReceived) {
                throw new InvalidArgumentException("Material ({$alreadyReceived->outgoing_code} / {$alreadyReceived->pallet_id}) SUDAH diverifikasi sebelumnya oleh {$alreadyReceived->received_by_name} pada {$alreadyReceived->received_at->format('d/m/Y H:i')}.");
            }

            throw new InvalidArgumentException("Data material dengan kode/pallet '{$code}' tidak ditemukan dalam daftar kiriman pending dari store.");
        }

        return $this->verifyReceipt($outgoing->id, $userId, $userName, 'Verifikasi cepat via barcode scanner');
    }

    /**
     * Batalkan verifikasi penerimaan (hanya jika belum ada log pemakaian)
     */
    public function revertReceipt(int $outgoingId): MwhOutgoing
    {
        return DB::transaction(function () use ($outgoingId) {
            $outgoing = MwhOutgoing::lockForUpdate()->findOrFail($outgoingId);

            $hasUsage = ProductionMaterialUsage::where('outgoing_id', $outgoingId)->exists();
            if ($hasUsage) {
                throw new InvalidArgumentException("Penerimaan material {$outgoing->outgoing_code} tidak dapat dibatalkan karena sudah ada riwayat pemakaian di produksi.");
            }

            $outgoing->update([
                'received_status' => 'PENDING',
                'received_by' => null,
                'received_by_name' => null,
                'received_at' => null,
                'received_notes' => null,
            ]);

            return $outgoing;
        });
    }

    /**
     * Catat pemakaian material di lantai produksi (Log Usage)
     */
    public function logMaterialUsage(array $data, int $userId, string $userName): ProductionMaterialUsage
    {
        return DB::transaction(function () use ($data, $userId, $userName) {
            $outgoingId = $data['outgoing_id'] ?? null;
            $qtyUsed = (float) ($data['qty_used'] ?? 0);

            if ($qtyUsed <= 0) {
                throw new InvalidArgumentException('Kuantitas pemakaian harus lebih besar dari 0.');
            }

            $outgoing = null;
            $itemCode = $data['item_code'] ?? null;
            $palletId = $data['pallet_id'] ?? null;
            $itemName = null;

            if ($outgoingId) {
                $outgoing = MwhOutgoing::with('material')->lockForUpdate()->findOrFail($outgoingId);

                if ($outgoing->received_status !== 'RECEIVED') {
                    throw new InvalidArgumentException("Material {$outgoing->outgoing_code} belum diverifikasi penerimaannya di produksi.");
                }

                $totalUsedForThisOutgoing = (float) ProductionMaterialUsage::where('outgoing_id', $outgoingId)->sum('qty_used');
                $remainingQty = (float) $outgoing->qty_taken - $totalUsedForThisOutgoing;

                if ($qtyUsed > ($remainingQty + 0.001)) {
                    throw new InvalidArgumentException("Kuantitas pemakaian ({$qtyUsed} KG) melebihi sisa stok pada batch ini ({$remainingQty} KG).");
                }

                $itemCode = $outgoing->item_code;
                $palletId = $outgoing->pallet_id;
                $itemName = $outgoing->material?->item_description;
            } elseif ($itemCode) {
                $mat = MasterListMaterial::where('item_code', $itemCode)->first();
                $itemName = $mat?->item_description;
            }

            return ProductionMaterialUsage::create([
                'outgoing_id' => $outgoingId,
                'item_code' => $itemCode,
                'item_name' => $itemName ?: ($data['item_name'] ?? null),
                'pallet_id' => $palletId,
                'machine_no' => $data['machine_no'] ?? null,
                'spk_code' => $data['spk_code'] ?? null,
                'qty_used' => $qtyUsed,
                'uom' => $data['uom'] ?? ($outgoing->uom ?? 'KG'),
                'purpose' => $data['purpose'] ?? 'PRODUKSI',
                'used_at' => ! empty($data['used_at']) ? Carbon::parse($data['used_at']) : now(),
                'used_by' => $userId,
                'used_by_name' => $userName,
                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }

    /**
     * Batalkan / Hapus log pemakaian material
     */
    public function deleteMaterialUsage(int $usageId): void
    {
        $usage = ProductionMaterialUsage::findOrFail($usageId);
        $usage->delete();
    }

    /**
     * Dapatkan daftar batch material yang sudah diterima dan masih ada sisa stok di produksi
     */
    public function getAvailableBatchesForUsage(): array
    {
        $receivedOutgoings = MwhOutgoing::with('material')
            ->where('received_status', 'RECEIVED')
            ->get();

        $batches = [];

        foreach ($receivedOutgoings as $out) {
            $totalUsed = (float) ProductionMaterialUsage::where('outgoing_id', $out->id)->sum('qty_used');
            $remaining = (float) $out->qty_taken - $totalUsed;

            if ($remaining > 0.01) {
                $batches[] = [
                    'outgoing_id' => $out->id,
                    'outgoing_code' => $out->outgoing_code,
                    'pallet_id' => $out->pallet_id,
                    'item_code' => $out->item_code,
                    'item_name' => $out->material?->item_description ?: $out->item_code,
                    'qty_received' => (float) $out->qty_taken,
                    'qty_used' => $totalUsed,
                    'remaining_qty' => $remaining,
                    'uom' => $out->uom ?? 'KG',
                    'received_at' => $out->received_at ? $out->received_at->format('d/m/Y H:i') : '-',
                ];
            }
        }

        return $batches;
    }

    /**
     * Ringkasan stok material di lantai produksi (Floor Stock per Item Code)
     */
    public function getFloorStockSummary(string $search = ''): array
    {
        // 1. Total received per item_code
        $receivedQuery = MwhOutgoing::where('received_status', 'RECEIVED')
            ->select('item_code', DB::raw('SUM(qty_taken) as total_received'), DB::raw('COUNT(id) as batch_count'))
            ->groupBy('item_code');

        $receivedMap = $receivedQuery->get()->keyBy('item_code');

        // 2. Total used per item_code
        $usedQuery = ProductionMaterialUsage::select('item_code', DB::raw('SUM(qty_used) as total_used'), DB::raw('COUNT(id) as usage_count'))
            ->groupBy('item_code');

        $usedMap = $usedQuery->get()->keyBy('item_code');

        // 3. Gabungkan semua item_code
        $allItemCodes = array_unique(array_merge($receivedMap->keys()->toArray(), $usedMap->keys()->toArray()));

        $materials = MasterListMaterial::whereIn('item_code', $allItemCodes)->get()->keyBy('item_code');

        $summary = [];

        foreach ($allItemCodes as $code) {
            $mat = $materials->get($code);
            $desc = $mat?->item_description ?: '—';

            if ($search) {
                $sLower = strtolower($search);
                if (! str_contains(strtolower($code), $sLower) && ! str_contains(strtolower($desc), $sLower)) {
                    continue;
                }
            }

            $recQty = (float) ($receivedMap->get($code)?->total_received ?? 0);
            $recBatches = (int) ($receivedMap->get($code)?->batch_count ?? 0);
            $useQty = (float) ($usedMap->get($code)?->total_used ?? 0);
            $useCount = (int) ($usedMap->get($code)?->usage_count ?? 0);
            $stock = max(0, $recQty - $useQty);

            $summary[] = [
                'item_code' => $code,
                'item_name' => $desc,
                'total_received' => $recQty,
                'batch_count' => $recBatches,
                'total_used' => $useQty,
                'usage_count' => $useCount,
                'current_stock' => $stock,
                'uom' => 'KG',
                'status' => $stock > 0 ? ($stock < 50 ? 'LOW_STOCK' : 'AVAILABLE') : 'EMPTY',
            ];
        }

        usort($summary, fn ($a, $b) => $b['current_stock'] <=> $a['current_stock']);

        return $summary;
    }
}

