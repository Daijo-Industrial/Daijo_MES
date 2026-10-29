<?php

namespace App\Services;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\ProductionScannedData;
use App\Models\ProductionStoreHandover;
use App\Models\ProductionStoreHandoverLog;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
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
    public function receiveBatch(int $handoverId, int $boxes, ?int $qty = null, ?string $remarks = null, $user = null, ?string $palletId = null): bool
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

        DB::transaction(function () use ($handover, $boxes, $qty, $remarks, $userId, $userName, $palletId) {
            // 1. Buat log riwayat penerimaan per trip
            ProductionStoreHandoverLog::create([
                'handover_id' => $handover->id,
                'pallet_id' => $palletId,
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
     * Verifikasi penerimaan berdasarkan Pallet Form WMS (1 scan / klik = seluruh box pallet terverifikasi)
     */
    public function receivePallet(int $handoverId, string $palletId, $user): array
    {
        $handover = ProductionStoreHandover::find($handoverId);
        if (! $handover) {
            return [
                'success' => false,
                'message' => 'Data serah terima tidak ditemukan.',
            ];
        }

        $pallet = WmsPalletForm::with(['details'])->where('pallet_id', $palletId)->first();
        if (! $pallet) {
            return [
                'success' => false,
                'message' => "Pallet Form [{$palletId}] tidak ditemukan di sistem WMS.",
            ];
        }

        // Anti-duplikasi (Idempotency Guard): Pastikan Pallet ID ini belum pernah diterima
        $existingLog = ProductionStoreHandoverLog::where('pallet_id', $palletId)->first();
        if ($existingLog) {
            $formattedTime = $existingLog->created_at ? $existingLog->created_at->format('H:i d/m/Y') : '-';
            return [
                'success' => false,
                'message' => "Pallet [{$palletId}] sudah pernah diverifikasi oleh {$existingLog->verified_by_name} pada {$formattedTime}.",
            ];
        }

        // Hitung box dan pcs untuk item handover ini
        if ($pallet->part_no === 'MIXED') {
            $itemDetails = $pallet->details->where('part_no', $handover->item_code);
            $boxes = $itemDetails->count();
            $qty = (int) $itemDetails->sum('qty');
            if ($boxes <= 0) {
                return [
                    'success' => false,
                    'message' => "Item [{$handover->item_code}] tidak ditemukan dalam Pallet [{$palletId}].",
                ];
            }
        } else {
            $boxes = (int) $pallet->box_qty;
            $qty = (int) $pallet->total_pallet_qty;
        }

        $userName = $user ? ($user->name ?? 'User Store') : 'Staff Store';
        $userId = $user ? ($user->id ?? null) : null;
        $deliveryRemark = $pallet->delivery_name ? " ({$pallet->delivery_name})" : '';

        DB::transaction(function () use ($handover, $palletId, $deliveryRemark, $boxes, $qty, $userId, $userName) {
            // 1. Buat log serah terima dengan mengikat pallet_id
            ProductionStoreHandoverLog::create([
                'handover_id' => $handover->id,
                'pallet_id' => $palletId,
                'received_boxes' => $boxes,
                'received_qty' => $qty,
                'verified_by' => $userId,
                'verified_by_name' => $userName,
                'remarks' => "Pallet Form: {$palletId}{$deliveryRemark}",
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
            ]);
        });

        return [
            'success' => true,
            'boxes' => $boxes,
            'qty' => $qty,
            'pallet_id' => $palletId,
            'message' => "Berhasil memverifikasi Pallet [{$palletId}] ({$boxes} box / {$qty} pcs).",
        ];
    }

    /**
     * Mengambil daftar Pallet Form WMS yang terkait dengan part, tanggal, dan shift ini
     */
    public function getRelatedPalletForms(string $date, ?int $shift, string $itemCode): array
    {
        [$startTime, $endTime] = WmsDeliveryRecapService::getTimeWindow($date);

        $query = WmsPalletForm::with(['position.rack.warehouse', 'details'])
            ->where(function ($q) use ($date, $startTime, $endTime) {
                $q->where('prod_date', $date)
                  ->orWhereBetween('created_at', [$startTime, $endTime]);
            })
            ->where(function ($q) use ($itemCode) {
                $q->where('part_no', $itemCode)
                  ->orWhereHas('details', function ($d) use ($itemCode) {
                      $d->where('part_no', $itemCode);
                  });
            });

        $pallets = $query->orderBy('created_at', 'asc')->get();

        // Prioritaskan pallet dengan shift yang cocok di urutan teratas tanpa menyembunyikan pallet lainnya
        if (! empty($shift)) {
            $pallets = $pallets->sortByDesc(function ($p) use ($shift) {
                return ((int) $p->delivery_shift === (int) $shift) ? 1 : 0;
            })->values();
        }

        $receivedLogs = ProductionStoreHandoverLog::whereIn('pallet_id', $pallets->pluck('pallet_id'))
            ->get()
            ->keyBy('pallet_id');

        return $pallets->map(function ($pallet) use ($receivedLogs, $itemCode) {
            $log = $receivedLogs->get($pallet->pallet_id);
            $isReceived = $log !== null;

            if ($pallet->part_no === 'MIXED') {
                $itemDetails = $pallet->details->where('part_no', $itemCode);
                $boxCount = $itemDetails->count();
                $qtyPcs = (int) $itemDetails->sum('qty');
            } else {
                $boxCount = (int) $pallet->box_qty;
                $qtyPcs = (int) $pallet->total_pallet_qty;
            }

            $rackLocation = null;
            if ($pallet->position && $pallet->position->rack) {
                $rackLocation = $pallet->position->rack->name . '-' . $pallet->position->code;
            }

            return [
                'pallet_id' => $pallet->pallet_id,
                'part_no' => $pallet->part_no,
                'model_name' => $pallet->model_name,
                'delivery_name' => $pallet->delivery_name ?: 'Delivery',
                'delivery_shift' => $pallet->delivery_shift,
                'created_at' => $pallet->created_at,
                'created_time_formatted' => $pallet->created_at ? $pallet->created_at->format('H:i') : '-',
                'box_count' => $boxCount,
                'qty_pcs' => $qtyPcs,
                'is_received' => $isReceived,
                'received_by_name' => $log?->verified_by_name,
                'received_at' => $log?->created_at,
                'received_at_formatted' => $log?->created_at ? $log->created_at->format('H:i') : null,
                'position_id' => $pallet->position_id,
                'rack_location' => $rackLocation,
                'is_putaway' => ! empty($pallet->position_id),
                'details' => $pallet->details->map(fn ($d) => [
                    'label' => $d->label,
                    'spk_no' => $d->spk_no,
                    'qty' => (int) $d->qty,
                ])->toArray(),
            ];
        })->toArray();
    }

    /**
     * Dapatkan seluruh antrean Pallet Form WMS dalam window cutoff tanggal produksi
     * untuk tampilan cepat penerimaan Store (Card Feed tanpa popup modal)
     */
    public function getPalletsQueue(string $date, ?int $shift = null, ?string $search = null): array
    {
        [$startTime, $endTime] = WmsDeliveryRecapService::getTimeWindow($date);

        $query = WmsPalletForm::with(['position.rack.warehouse', 'details.item'])
            ->where(function ($q) use ($date, $startTime, $endTime) {
                $q->where('prod_date', $date)
                  ->orWhereBetween('created_at', [$startTime, $endTime]);
            });

        if (! empty($shift)) {
            $query->where(function ($q) use ($shift) {
                $q->where('delivery_shift', (string) $shift)
                  ->orWhere('delivery_shift', $shift);
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pallet_id', 'like', "%{$search}%")
                  ->orWhere('part_no', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('delivery_name', 'like', "%{$search}%")
                  ->orWhereHas('details', function ($d) use ($search) {
                      $d->where('part_no', 'like', "%{$search}%")
                        ->orWhere('model_name', 'like', "%{$search}%");
                  });
            });
        }

        $pallets = $query->orderBy('created_at', 'desc')->get();

        $receivedLogs = ProductionStoreHandoverLog::whereIn('pallet_id', $pallets->pluck('pallet_id'))
            ->get()
            ->keyBy('pallet_id');

        $pending = [];
        $received = [];

        foreach ($pallets as $pallet) {
            $log = $receivedLogs->get($pallet->pallet_id);
            $isReceived = $log !== null;

            $rackLocation = null;
            if ($pallet->position && $pallet->position->rack) {
                $rackLocation = $pallet->position->rack->name . '-' . $pallet->position->code;
            }

            $itemData = [
                'pallet_id' => $pallet->pallet_id,
                'part_no' => $pallet->part_no,
                'model_name' => $pallet->model_name,
                'delivery_name' => $pallet->delivery_name ?: 'Pengiriman Produksi',
                'delivery_shift' => $pallet->delivery_shift,
                'created_at' => $pallet->created_at,
                'created_time_formatted' => $pallet->created_at ? $pallet->created_at->format('H:i') : '-',
                'box_count' => (int) $pallet->box_qty,
                'qty_pcs' => (int) $pallet->total_pallet_qty,
                'is_received' => $isReceived,
                'received_by_name' => $log?->verified_by_name,
                'received_at' => $log?->created_at,
                'received_at_formatted' => $log?->created_at ? $log->created_at->format('H:i') : null,
                'is_putaway' => ! empty($pallet->position_id),
                'rack_location' => $rackLocation,
                'details' => $pallet->details->map(fn ($d) => [
                    'label' => $d->label,
                    'spk_no' => $d->spk_no,
                    'qty' => (int) $d->qty,
                ])->toArray(),
            ];

            if ($isReceived) {
                $received[] = $itemData;
            } else {
                $pending[] = $itemData;
            }
        }

        return [
            'pending' => $pending,
            'received' => $received,
            'total_pending_count' => count($pending),
            'total_received_count' => count($received),
        ];
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

        [$startTime, $endTime] = WmsDeliveryRecapService::getTimeWindow($date);
        $windowPallets = WmsPalletForm::with(['details'])->where(function ($q) use ($date, $startTime, $endTime) {
            $q->where('prod_date', $date)
              ->orWhereBetween('created_at', [$startTime, $endTime]);
        })->get();

        $receivedPalletMap = ProductionStoreHandoverLog::whereIn('pallet_id', $windowPallets->pluck('pallet_id'))
            ->pluck('pallet_id')
            ->flip()
            ->toArray();

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
            'total_pallets_count' => $windowPallets->count(),
            'received_pallets_count' => count($receivedPalletMap),
        ];

        $itemsQuery = (clone $baseQuery)->with(['logs'])
            ->orderBy('item_code')
            ->orderBy('machine_name')
            ->orderBy('shift');

        $items = $perPage ? $itemsQuery->paginate($perPage) : $itemsQuery->get();

        // Attach metric Pallet Form ke masing-masing item (mencocokkan part number tunggal maupun detail mixed pallet)
        $itemsCollection = $items instanceof \Illuminate\Pagination\LengthAwarePaginator ? $items->getCollection() : $items;
        foreach ($itemsCollection as $item) {
            $matchingPallets = $windowPallets->filter(function ($p) use ($item) {
                if ($p->part_no === $item->item_code) {
                    return true;
                }
                if ($p->part_no === 'MIXED' || empty($p->part_no)) {
                    return $p->details->contains('part_no', $item->item_code);
                }
                return false;
            });

            $item->pallet_forms_count = $matchingPallets->count();
            $item->received_pallet_forms_count = $matchingPallets->filter(fn ($p) => isset($receivedPalletMap[$p->pallet_id]))->count();
        }

        return [
            'items' => $items,
            'stats' => $stats,
        ];
    }
}
