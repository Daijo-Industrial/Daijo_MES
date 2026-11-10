<?php

namespace App\Services;

use App\Models\MasterListMaterial;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use App\Models\ProductionMaterialRequest;
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
                $rAt = $alreadyReceived->received_at ? $alreadyReceived->received_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : '-';
                throw new InvalidArgumentException("Material ({$alreadyReceived->outgoing_code} / {$alreadyReceived->pallet_id}) SUDAH diverifikasi sebelumnya oleh {$alreadyReceived->received_by_name} pada {$rAt}.");
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
                'used_at' => ! empty($data['used_at']) ? ($data['used_at'] instanceof Carbon ? $data['used_at'] : Carbon::parse($data['used_at'], 'Asia/Jakarta')->setTimezone('UTC')) : now(),
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
                    'received_at' => $out->received_at ? $out->received_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-',
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

    /**
     * Dapatkan informasi stok aktual material di gudang Store (MwhPallet) dan lantai produksi
     */
    public function getActualStoreStock(string $itemCode): array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return [
                'item_code' => '',
                'item_name' => '',
                'store_available_qty' => 0.0,
                'store_pallet_count' => 0,
                'pallets' => [],
                'production_floor_stock' => 0.0,
            ];
        }

        $material = MasterListMaterial::where('item_code', $itemCode)->first();

        // 1. Query pallet aktif di gudang store (current_qty > 0 dan tidak di-QC hold)
        $pallets = MwhPallet::with(['position.rack.warehouse', 'incomingHeader'])
            ->where('item_code', $itemCode)
            ->where('current_qty', '>', 0)
            ->where('is_qc_hold', false)
            ->whereIn('status', ['STORED', 'PARTIAL'])
            ->orderBy('id', 'asc')
            ->get();

        $storeQty = (float) $pallets->sum('current_qty');

        $palletsDetail = $pallets->map(function ($p) {
            $rackName = $p->position?->rack?->rack_code ?: '-';
            $slotCode = $p->position?->position_code ?: '-';
            $whseName = $p->position?->rack?->warehouse?->whse_name ?: '-';

            return [
                'pallet_id' => $p->pallet_id,
                'lot_no' => $p->lot_no ?: '-',
                'current_qty' => (float) $p->current_qty,
                'uom' => $p->uom ?: 'KG',
                'status' => $p->status,
                'location' => "{$whseName} / Rak {$rackName} ({$slotCode})",
                'arrival_date' => $p->incomingHeader?->arrival_date ?: $p->created_at?->format('Y-m-d'),
            ];
        })->toArray();

        // 2. Query sisa stok di lantai produksi
        $receivedQty = (float) MwhOutgoing::where('item_code', $itemCode)
            ->where('received_status', 'RECEIVED')
            ->sum('qty_taken');

        $usedQty = (float) ProductionMaterialUsage::where('item_code', $itemCode)->sum('qty_used');
        $floorStock = max(0, $receivedQty - $usedQty);

        return [
            'item_code' => $itemCode,
            'item_name' => $material?->item_description ?: $itemCode,
            'store_available_qty' => $storeQty,
            'store_pallet_count' => count($palletsDetail),
            'pallets' => $palletsDetail,
            'production_floor_stock' => $floorStock,
        ];
    }

    /**
     * Buat Permintaan Material Baru dari Produksi ke Gudang Store
     */
    public function createMaterialRequest(array $data, int $userId, string $userName): ProductionMaterialRequest
    {
        $itemCode = trim($data['item_code'] ?? '');
        $qty = (float) ($data['qty_requested'] ?? 0);

        if ($itemCode === '') {
            throw new InvalidArgumentException('Kode material wajib dipilih.');
        }

        if ($qty <= 0) {
            throw new InvalidArgumentException('Kuantitas permintaan material minimal 0.01 KG.');
        }

        // Validasi stok aktual di Store: tidak boleh request jika stok kosong atau melebihi stok yang ada
        $storeStock = $this->getActualStoreStock($itemCode);
        $availableStoreStock = (float) ($storeStock['store_available_qty'] ?? 0);

        if ($availableStoreStock <= 0) {
            throw new InvalidArgumentException("Stok material [{$itemCode}] di Gudang Store saat ini KOSONG (0 KG). Permintaan tidak dapat dibuat.");
        }

        if ($qty > $availableStoreStock) {
            throw new InvalidArgumentException("Kuantitas permintaan (" . number_format($qty, 2) . " KG) melebihi stok aktual yang tersedia di Gudang Store (" . number_format($availableStoreStock, 2) . " KG).");
        }

        $material = MasterListMaterial::where('item_code', $itemCode)->first();
        $dateStr = now('Asia/Jakarta')->format('Ymd');

        // Generate unique request_code: REQ-YYYYMMDD-XXXX
        $latest = ProductionMaterialRequest::whereDate('created_at', now('Asia/Jakarta')->toDateString())
            ->latest('id')
            ->first();
        $seq = $latest ? (((int) substr($latest->request_code, -4)) + 1) : 1;
        $requestCode = sprintf('REQ-%s-%04d', $dateStr, $seq);

        return ProductionMaterialRequest::create([
            'request_code' => $requestCode,
            'item_code' => $itemCode,
            'item_name' => $material?->item_description ?: ($data['item_name'] ?? $itemCode),
            'qty_requested' => $qty,
            'uom' => $data['uom'] ?? 'KG',
            'machine_no' => ! empty($data['machine_no']) ? strtoupper(trim($data['machine_no'])) : null,
            'spk_code' => ! empty($data['spk_code']) ? trim($data['spk_code']) : null,
            'urgency' => in_array($data['urgency'] ?? '', ['NORMAL', 'URGENT']) ? $data['urgency'] : 'NORMAL',
            'needed_at' => ! empty($data['needed_at']) ? Carbon::parse($data['needed_at'], 'Asia/Jakarta')->setTimezone('UTC') : null,
            'notes' => $data['notes'] ?? null,
            'status' => 'PENDING',
            'requested_by' => $userId,
            'requested_by_name' => $userName,
        ]);
    }

    /**
     * Batalkan Permintaan Material oleh Orang Produksi (hanya jika masih PENDING)
     */
    public function cancelMaterialRequest(int $requestId, int $userId): ProductionMaterialRequest
    {
        $request = ProductionMaterialRequest::findOrFail($requestId);

        if ($request->status !== 'PENDING') {
            throw new InvalidArgumentException("Permintaan {$request->request_code} tidak dapat dibatalkan karena statusnya sudah {$request->status}.");
        }

        $request->update([
            'status' => 'CANCELLED',
        ]);

        return $request;
    }

    /**
     * Ambil data Permintaan Material (bisa difilter status, search, rentang tanggal)
     */
    public function getMaterialRequests(
        string $status = 'ALL',
        string $search = '',
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        return ProductionMaterialRequest::query()
            ->with(['material', 'outgoing', 'requester', 'processor', 'rejector'])
            ->when($status !== 'ALL', fn ($q) => $q->where('status', $status))
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [
                    Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC'),
                    Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC'),
                ]);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('request_code', 'LIKE', "%{$search}%")
                        ->orWhere('item_code', 'LIKE', "%{$search}%")
                        ->orWhere('item_name', 'LIKE', "%{$search}%")
                        ->orWhere('machine_no', 'LIKE', "%{$search}%")
                        ->orWhere('spk_code', 'LIKE', "%{$search}%")
                        ->orWhere('outgoing_code', 'LIKE', "%{$search}%");
                });
            })
            ->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 WHEN status = 'PROCESSED' THEN 1 ELSE 2 END")
            ->orderByDesc('id');
    }

    /**
     * Tanggapi / Selesaikan Request oleh Store via Outgoing
     */
    public function processMaterialRequest(
        int $requestId,
        int $outgoingId,
        int $userId,
        string $userName
    ): ProductionMaterialRequest {
        return DB::transaction(function () use ($requestId, $outgoingId, $userId, $userName) {
            $request = ProductionMaterialRequest::lockForUpdate()->findOrFail($requestId);
            $outgoing = MwhOutgoing::findOrFail($outgoingId);

            if ($request->status !== 'PENDING') {
                throw new InvalidArgumentException("Permintaan {$request->request_code} sudah berstatus {$request->status}.");
            }

            $request->update([
                'status' => 'PROCESSED',
                'processed_by' => $userId,
                'processed_by_name' => $userName,
                'processed_at' => now(),
                'outgoing_id' => $outgoing->id,
                'outgoing_code' => $outgoing->outgoing_code,
            ]);

            return $request;
        });
    }

    /**
     * Tolak Permintaan Material oleh Orang Store (dengan Alasan Penolakan)
     */
    public function rejectMaterialRequest(
        int $requestId,
        string $reason,
        int $userId,
        string $userName
    ): ProductionMaterialRequest {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi agar tim produksi mengetahui kendalanya.');
        }

        $request = ProductionMaterialRequest::findOrFail($requestId);

        if ($request->status !== 'PENDING') {
            throw new InvalidArgumentException("Permintaan {$request->request_code} tidak dapat ditolak karena sudah berstatus {$request->status}.");
        }

        $request->update([
            'status' => 'REJECTED',
            'rejected_by' => $userId,
            'rejected_by_name' => $userName,
            'rejected_at' => now(),
            'reject_reason' => $reason,
        ]);

        return $request;
    }
}

