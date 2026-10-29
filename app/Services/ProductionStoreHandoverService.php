<?php

namespace App\Services;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\ProductionScannedData;
use App\Models\ProductionStoreHandover;
use App\Models\ProductionStoreHandoverLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionStoreHandoverService
{
    /**
     * Dapatkan tanggal produksi saat ini berdasarkan aturan cutoff (07:30 WIB)
     */
    public static function getCurrentProductionDate(): string
    {
        return WmsDeliveryRecapService::getCurrentProductionDate();
    }

    /**
     * Sinkronisasi data produksi harian (Actual Mesin & Scan Barcode) ke tabel handover
     */
    public function syncDailyProduction(string $date, ?int $shift = null): void
    {
        // 1. Ambil seluruh DIC pada tanggal schedule_date tersebut dengan relasi user (machine)
        $dicQuery = DailyItemCode::with(['user'])->where('schedule_date', $date);

        if (! empty($shift)) {
            $dicQuery->where('shift', $shift);
        }

        $dics = $dicQuery->get();

        // 2. Group berdasarkan item_code, shift, dan machine_name (mesin K Karawang dipisah)
        $grouped = $dics->groupBy(function ($item) {
            $machineName = strtoupper(trim($item->user?->name ?: 'UNKNOWN'));
            return ($item->item_code ?: 'UNKNOWN') . '|||' . ($item->shift ?: 1) . '|||' . $machineName;
        });

        foreach ($grouped as $key => $dicList) {
            [$itemCode, $itemShift, $machineName] = explode('|||', $key);
            $itemShift = (int) $itemShift;
            $dicIds = $dicList->pluck('id')->toArray();

            $isKarawang = str_starts_with($machineName, 'K');
            $plant = $isKarawang ? 'KARAWANG' : 'KBN';

            // Hitung Actual Input Operator dari HourlyRemark (dengan fallback ke daily_item_codes)
            $hourlyActual = HourlyRemark::whereIn('dic_id', $dicIds)->sum('actual_production');
            $dicActual = $dicList->sum('actual_quantity');
            $totalActual = max($hourlyActual, $dicActual);

            // Hitung Scan Barcode Box & Pcs dari production_scanned_data untuk DIC mesin terkait
            $scanData = ProductionScannedData::whereIn('dic_id', $dicIds)
                ->selectRaw('COUNT(*) as total_boxes, COALESCE(SUM(quantity), 0) as total_qty')
                ->first();

            $scannedBoxes = (int) ($scanData->total_boxes ?? 0);
            $scannedQty = (int) ($scanData->total_qty ?? 0);

            // Dapatkan Master List Item untuk deskripsi & customer
            $masterItem = MasterListItem::where('item_code', $itemCode)->first();
            $itemName = $masterItem ? ($masterItem->item_name ?: $masterItem->description) : null;
            $customerName = $masterItem?->customer?->customer_name ?: ($masterItem?->customer_code ?: 'N/A');

            // Aturan: Untuk yang tidak ada mesinnya dan actual mesin 0 (serta belum scan box), tidak usah disimpan / ditampilkan
            $isNoMachine = empty($machineName) || in_array($machineName, ['UNKNOWN', 'N/A', '-']);
            if ($isNoMachine && $totalActual <= 0 && $scannedBoxes <= 0) {
                // Hapus jika sebelumnya pernah tersimpan agar data bersih
                ProductionStoreHandover::where('production_date', $date)
                    ->where('shift', $itemShift)
                    ->where('item_code', $itemCode)
                    ->where(function ($q) {
                        $q->whereIn('machine_name', ['UNKNOWN', 'N/A', '-', ''])
                          ->orWhereNull('machine_name');
                    })
                    ->where('store_received_boxes', '<=', 0)
                    ->delete();
                continue;
            }

            // Simpan / update record handover per mesin
            $handover = ProductionStoreHandover::firstOrNew([
                'production_date' => $date,
                'shift' => $itemShift,
                'item_code' => $itemCode,
                'machine_name' => $machineName,
            ]);

            $handover->plant = $plant;
            $handover->item_name = $itemName ?: $handover->item_name;
            $handover->customer_name = $customerName ?: $handover->customer_name;
            $handover->operator_actual_qty = $totalActual;
            $handover->scanned_qty = $scannedQty;
            $handover->scanned_boxes = $scannedBoxes;

            // Update status
            if ($handover->store_received_boxes >= $scannedBoxes && $scannedBoxes > 0) {
                $handover->status = 'COMPLETED';
            } elseif ($handover->store_received_boxes > 0) {
                $handover->status = 'PARTIAL';
            } else {
                $handover->status = 'PENDING';
            }

            $handover->save();
        }
    }

    /**
     * Store melakukan verifikasi penerimaan barang secara bertahap / running (per trip)
     */
    public function receiveBatch(int $handoverId, int $boxes, ?int $qty = null, ?string $remarks = null, $user = null): bool
    {
        $handover = ProductionStoreHandover::find($handoverId);
        if (! $handover) {
            return false;
        }

        if ($boxes <= 0) {
            return false;
        }

        // Taksir qty jika tidak diisi secara spesifik (berdasarkan rata-rata SNP per box)
        if ($qty === null || $qty <= 0) {
            $avgPcsPerBox = $handover->scanned_boxes > 0
                ? (int) round($handover->scanned_qty / $handover->scanned_boxes)
                : 0;
            $qty = $avgPcsPerBox * $boxes;
        }

        $userName = $user ? ($user->name ?? 'User Store') : 'Staff Store';
        $userId = $user ? ($user->id ?? null) : null;

        DB::transaction(function () use ($handover, $boxes, $qty, $remarks, $userId, $userName) {
            // 1. Buat log riwayat penerimaan per trip
            ProductionStoreHandoverLog::create([
                'handover_id' => $handover->id,
                'received_boxes' => $boxes,
                'received_qty' => $qty,
                'verified_by' => $userId,
                'verified_by_name' => $userName,
                'remarks' => $remarks ?: ('Penerimaan trip ' . now('Asia/Jakarta')->format('H:i')),
            ]);

            // 2. Akumulasikan ke master handover
            $newBoxes = $handover->store_received_boxes + $boxes;
            $newQty = $handover->store_received_qty + $qty;

            $status = 'PARTIAL';
            if ($newBoxes >= $handover->scanned_boxes && $handover->scanned_boxes > 0) {
                $status = 'COMPLETED';
            }

            $handover->update([
                'store_received_boxes' => $newBoxes,
                'store_received_qty' => $newQty,
                'status' => $status,
                'last_verified_by' => $userId,
                'last_verified_by_name' => $userName,
                'last_verified_at' => now(),
                'remarks' => $remarks ?: $handover->remarks,
            ]);
        });

        return true;
    }

    /**
     * Dapatkan data ringkasan untuk halaman kontrol
     */
    public function getSummary(string $date, ?int $shift = null, ?string $plant = null, ?string $search = null, ?int $perPage = 25)
    {
        // Pastikan sinkronisasi awal berjalan
        $this->syncDailyProduction($date, $shift);

        $baseQuery = ProductionStoreHandover::where('production_date', $date);

        // Filter: jangan tampilkan yang tidak ada mesinnya DAN actual mesin 0 (serta tidak ada box scan)
        $baseQuery->where(function ($q) {
            $q->where(function ($sub) {
                $sub->whereNotNull('machine_name')
                    ->whereNotIn('machine_name', ['UNKNOWN', 'N/A', '-', '']);
            })->orWhere('operator_actual_qty', '>', 0)
              ->orWhere('scanned_boxes', '>', 0);
        });

        if (! empty($shift)) {
            $baseQuery->where('shift', $shift);
        }

        if (! empty($plant) && $plant !== 'ALL') {
            if ($plant === 'KARAWANG') {
                $baseQuery->where(function ($q) {
                    $q->where('plant', 'KARAWANG')
                      ->orWhere('machine_name', 'like', 'K%');
                });
            } elseif ($plant === 'KBN') {
                $baseQuery->where(function ($q) {
                    $q->where('plant', 'KBN')
                      ->where('machine_name', 'not like', 'K%');
                });
            }
        }

        if (! empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('machine_name', 'like', "%{$search}%");
            });
        }

        // Stats dihitung dari seluruh data yang terfilter (sebelum paginasi)
        $allItems = (clone $baseQuery)->get();

        $stats = [
            'total_items' => $allItems->count(),
            'total_actual_qty' => $allItems->sum('operator_actual_qty'),
            'total_scanned_boxes' => $allItems->sum('scanned_boxes'),
            'total_scanned_qty' => $allItems->sum('scanned_qty'),
            'total_received_boxes' => $allItems->sum('store_received_boxes'),
            'total_received_qty' => $allItems->sum('store_received_qty'),
            'total_in_transit_boxes' => $allItems->sum(fn ($i) => $i->in_transit_boxes),
            'total_in_transit_qty' => $allItems->sum(fn ($i) => $i->in_transit_qty),
            'completed_items_count' => $allItems->where('status', 'COMPLETED')->count(),
        ];

        $itemsQuery = (clone $baseQuery)->with(['logs'])
            ->orderBy('item_code')
            ->orderBy('machine_name')
            ->orderBy('shift');

        $items = $perPage ? $itemsQuery->paginate($perPage) : $itemsQuery->get();

        return [
            'items' => $items,
            'stats' => $stats,
        ];
    }
}
