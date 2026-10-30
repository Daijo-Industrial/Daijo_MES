<?php

namespace App\Services;

use App\Models\DailyItemCode;
use App\Models\MasterListItem;
use App\Models\ProductionScannedData;
use App\Models\StoreCrosscheckDailyReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreCrosscheckService
{
    /**
     * Memastikan skema tabel dan kolom crosscheck store sudah siap (idempotent & defensive)
     */
    public static function ensureSchema(): void
    {
        try {
            if (Schema::hasTable('production_scanned_data')) {
                if (! Schema::hasColumn('production_scanned_data', 'store_check_status')) {
                    Schema::table('production_scanned_data', function (Blueprint $table) {
                        $table->string('store_check_status', 30)->default('PENDING')->after('summary_id');
                        $table->unsignedBigInteger('store_checked_by')->nullable()->after('store_check_status');
                        $table->string('store_checked_by_name', 255)->nullable()->after('store_checked_by');
                        $table->timestamp('store_checked_at')->nullable()->after('store_checked_by_name');
                        $table->text('store_notes')->nullable()->after('store_checked_at');
                    });
                }
            }

            if (! Schema::hasTable('store_crosscheck_daily_reports')) {
                Schema::create('store_crosscheck_daily_reports', function (Blueprint $table) {
                    $table->id();
                    $table->date('report_date');
                    $table->string('plant', 20)->default('KBN');
                    $table->string('shift', 10)->default('ALL');
                    $table->integer('total_boxes')->default(0);
                    $table->integer('total_qty')->default(0);
                    $table->integer('found_boxes')->default(0);
                    $table->integer('found_qty')->default(0);
                    $table->integer('missing_boxes')->default(0);
                    $table->integer('missing_qty')->default(0);
                    $table->integer('pending_boxes')->default(0);
                    $table->integer('pending_qty')->default(0);
                    $table->decimal('accuracy_rate', 5, 2)->default(0.00);
                    $table->string('status', 30)->default('OPEN');
                    $table->unsignedBigInteger('verified_by')->nullable();
                    $table->string('verified_by_name', 255)->nullable();
                    $table->timestamp('verified_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();

                    $table->unique(['report_date', 'plant', 'shift'], 'uniq_report_date_plant_shift');
                });
            }
        } catch (\Throwable $e) {
            // Log or ignore if already running concurrently
        }
    }

    /**
     * Dapatkan tanggal produksi saat ini (Cutoff 07:30 WIB)
     */
    public function getCurrentProductionDate(): string
    {
        return WmsDeliveryRecapService::getCurrentProductionDate();
    }

    /**
     * Dapatkan rentang waktu [start, end] cutoff 07:30 WIB
     */
    public function getTimeWindow(string $selectedDate): array
    {
        return WmsDeliveryRecapService::getTimeWindow($selectedDate);
    }

    /**
     * Query dasar untuk data scan produksi dengan filter tanggal, plant, shift, status, search, dan mesin
     */
    public function getBaseQuery(
        string $selectedDate,
        string $plant = 'ALL',
        string $shift = 'ALL',
        string $status = 'ALL',
        string $search = '',
        string $machine = 'ALL'
    ): Builder {
        self::ensureSchema();

        [$startTime, $endTime] = $this->getTimeWindow($selectedDate);

        $query = ProductionScannedData::with([
            'dailyItemCode.user',
            'dailyItemCode.masterItem.customer',
            'masterItem.customer',
            'storeChecker',
        ])
        ->where(function ($q) use ($startTime, $endTime, $selectedDate) {
            $q->whereBetween('production_scanned_data.created_at', [$startTime, $endTime])
              ->orWhereHas('dailyItemCode', fn ($dic) => $dic->where('schedule_date', $selectedDate));
        });

        // 1. Filter Plant: KBN vs KARAWANG
        if (strtoupper($plant) === 'KARAWANG') {
            $query->where(function ($q) {
                $q->where('production_scanned_data.item_code', 'LIKE', 'K-%')
                  ->orWhere('production_scanned_data.warehouse', 'LIKE', '%KRW%')
                  ->orWhere('production_scanned_data.warehouse', 'LIKE', '%KARAWANG%')
                  ->orWhereHas('dailyItemCode.user', function ($uq) {
                      $uq->where('name', 'LIKE', 'K%');
                  });
            });
        } elseif (strtoupper($plant) === 'KBN') {
            $query->where(function ($q) {
                $q->where('production_scanned_data.item_code', 'NOT LIKE', 'K-%')
                  ->where(function ($wq) {
                      $wq->whereNull('production_scanned_data.warehouse')
                         ->orWhere(function ($wq2) {
                             $wq2->where('production_scanned_data.warehouse', 'NOT LIKE', '%KRW%')
                                 ->where('production_scanned_data.warehouse', 'NOT LIKE', '%KARAWANG%');
                         });
                  })
                  ->where(function ($uq) {
                      $uq->whereDoesntHave('dailyItemCode.user')
                         ->orWhereHas('dailyItemCode.user', function ($uq2) {
                             $uq2->where('name', 'NOT LIKE', 'K%');
                         });
                  });
            });
        }

        // 2. Filter Shift: 1, 2, 3
        if ($shift !== 'ALL' && in_array($shift, ['1', '2', '3'])) {
            $targetShift = (int) $shift;
            $query->where(function ($q) use ($targetShift, $selectedDate) {
                $q->whereHas('dailyItemCode', fn ($dic) => $dic->where('shift', $targetShift))
                  ->orWhere(function ($q2) use ($targetShift, $selectedDate) {
                      $q2->whereDoesntHave('dailyItemCode');
                      $appTz = config('app.timezone', 'UTC');

                      if ($targetShift === 1) {
                          $s1Start = Carbon::parse($selectedDate . ' 07:30:00', 'Asia/Jakarta')->setTimezone($appTz);
                          $s1End = Carbon::parse($selectedDate . ' 15:29:59', 'Asia/Jakarta')->setTimezone($appTz);
                          $q2->whereBetween('production_scanned_data.created_at', [$s1Start, $s1End]);
                      } elseif ($targetShift === 2) {
                          $s2Start = Carbon::parse($selectedDate . ' 15:30:00', 'Asia/Jakarta')->setTimezone($appTz);
                          $s2End = Carbon::parse($selectedDate . ' 23:29:59', 'Asia/Jakarta')->setTimezone($appTz);
                          $q2->whereBetween('production_scanned_data.created_at', [$s2Start, $s2End]);
                      } else {
                          $s3Start = Carbon::parse($selectedDate . ' 23:30:00', 'Asia/Jakarta')->setTimezone($appTz);
                          $s3End = Carbon::parse($selectedDate . ' 07:29:59', 'Asia/Jakarta')->addDay()->setTimezone($appTz);
                          $q2->whereBetween('production_scanned_data.created_at', [$s3Start, $s3End]);
                      }
                  });
            });
        }

        // 3. Filter Status Checklist Store: PENDING, FOUND, NOT_FOUND
        if ($status === 'FOUND') {
            $query->where('production_scanned_data.store_check_status', 'FOUND');
        } elseif ($status === 'NOT_FOUND') {
            $query->where('production_scanned_data.store_check_status', 'NOT_FOUND');
        } elseif ($status === 'PENDING') {
            $query->where(function ($q) {
                $q->whereNull('production_scanned_data.store_check_status')
                  ->orWhere('production_scanned_data.store_check_status', 'PENDING');
            });
        }

        // 4. Filter Pencarian Cepat (Part No, Item Name, Label, SPK, Operator, Catatan)
        if (! empty(trim($search))) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('production_scanned_data.label', 'LIKE', $term)
                  ->orWhere('production_scanned_data.item_code', 'LIKE', $term)
                  ->orWhere('production_scanned_data.spk_code', 'LIKE', $term)
                  ->orWhere('production_scanned_data.user', 'LIKE', $term)
                  ->orWhere('production_scanned_data.store_notes', 'LIKE', $term)
                  ->orWhere('production_scanned_data.store_checked_by_name', 'LIKE', $term)
                  ->orWhereHas('masterItem', fn ($mq) => $mq->where('item_name', 'LIKE', $term)->orWhere('description', 'LIKE', $term))
                  ->orWhereHas('dailyItemCode.user', fn ($uq) => $uq->where('name', 'LIKE', $term));
            });
        }

        // 5. Filter Mesin Spesifik
        if ($machine !== 'ALL' && ! empty(trim($machine))) {
            $targetMachine = trim($machine);
            $query->whereHas('dailyItemCode.user', function ($uq) use ($targetMachine) {
                $uq->where('name', $targetMachine);
            });
        }

        return $query->orderBy('production_scanned_data.created_at', 'desc')->orderBy('production_scanned_data.id', 'desc');
    }

    /**
     * Ambil daftar mesin aktif untuk tanggal dan plant terpilih
     */
    public function getAvailableMachines(string $selectedDate, string $plant = 'ALL'): array
    {
        [$startTime, $endTime] = $this->getTimeWindow($selectedDate);

        $query = DailyItemCode::with('user')
            ->where(function ($q) use ($startTime, $endTime, $selectedDate) {
                $q->where('schedule_date', $selectedDate)
                  ->orWhereBetween('created_at', [$startTime, $endTime]);
            })
            ->whereHas('user');

        if (strtoupper($plant) === 'KARAWANG') {
            $query->where(function ($q) {
                $q->where('item_code', 'LIKE', 'K-%')
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'LIKE', 'K%'));
            });
        } elseif (strtoupper($plant) === 'KBN') {
            $query->where(function ($q) {
                $q->where('item_code', 'NOT LIKE', 'K-%')
                  ->whereHas('user', fn ($uq) => $uq->where('name', 'NOT LIKE', 'K%'));
            });
        }

        $names = $query->get()->pluck('user.name')->filter()->unique()->sort()->values()->toArray();

        return array_values($names);
    }

    /**
     * Hitung ringkasan statistik KPI (Total, Ditemukan, Belum Dicek, Tidak Ada)
     */
    public function getSummaryMetrics(string $selectedDate, string $plant = 'ALL'): array
    {
        self::ensureSchema();

        // Query seluruh data tanpa filter status untuk kalkulasi KPI akurat
        $query = $this->getBaseQuery($selectedDate, $plant, 'ALL', 'ALL', '');
        $records = $query->get(['id', 'item_code', 'quantity', 'warehouse', 'dic_id', 'store_check_status', 'created_at']);

        $totalBoxes = $records->count();
        $totalQty = (int) $records->sum('quantity');

        $foundRecords = $records->where('store_check_status', 'FOUND');
        $foundBoxes = $foundRecords->count();
        $foundQty = (int) $foundRecords->sum('quantity');

        $notFoundRecords = $records->where('store_check_status', 'NOT_FOUND');
        $missingBoxes = $notFoundRecords->count();
        $missingQty = (int) $notFoundRecords->sum('quantity');

        $pendingBoxes = max(0, $totalBoxes - ($foundBoxes + $missingBoxes));
        $pendingQty = max(0, $totalQty - ($foundQty + $missingQty));

        $accuracyRate = $totalBoxes > 0 ? round(($foundBoxes / $totalBoxes) * 100, 1) : 0.0;
        $progressRate = $totalBoxes > 0 ? round((($foundBoxes + $missingBoxes) / $totalBoxes) * 100, 1) : 0.0;

        // Hitung juga statistik KBN vs Karawang terpisah untuk tab badges
        $kbnQuery = $this->getBaseQuery($selectedDate, 'KBN', 'ALL', 'ALL', '');
        $kbnRecords = $kbnQuery->get(['id', 'quantity', 'store_check_status']);
        $kbnBoxes = $kbnRecords->count();
        $kbnFound = $kbnRecords->where('store_check_status', 'FOUND')->count();
        $kbnMissing = $kbnRecords->where('store_check_status', 'NOT_FOUND')->count();
        $kbnPending = max(0, $kbnBoxes - ($kbnFound + $kbnMissing));

        $krwQuery = $this->getBaseQuery($selectedDate, 'KARAWANG', 'ALL', 'ALL', '');
        $krwRecords = $krwQuery->get(['id', 'quantity', 'store_check_status']);
        $krwBoxes = $krwRecords->count();
        $krwFound = $krwRecords->where('store_check_status', 'FOUND')->count();
        $krwMissing = $krwRecords->where('store_check_status', 'NOT_FOUND')->count();
        $krwPending = max(0, $krwBoxes - ($krwFound + $krwMissing));

        return [
            'total_boxes' => $totalBoxes,
            'total_qty' => $totalQty,
            'found_boxes' => $foundBoxes,
            'found_qty' => $foundQty,
            'missing_boxes' => $missingBoxes,
            'missing_qty' => $missingQty,
            'pending_boxes' => $pendingBoxes,
            'pending_qty' => $pendingQty,
            'accuracy_rate' => $accuracyRate,
            'progress_rate' => $progressRate,
            'kbn' => [
                'total_boxes' => $kbnBoxes,
                'found_boxes' => $kbnFound,
                'missing_boxes' => $kbnMissing,
                'pending_boxes' => $kbnPending,
            ],
            'karawang' => [
                'total_boxes' => $krwBoxes,
                'found_boxes' => $krwFound,
                'missing_boxes' => $krwMissing,
                'pending_boxes' => $krwPending,
            ],
        ];
    }

    /**
     * Verifikasi fisik box melalui scanner barcode secara instan
     */
    public function verifyBarcode(
        string $barcode,
        string $selectedDate,
        string $plant = 'ALL',
        ?int $userId = null,
        ?string $userName = null
    ): array {
        self::ensureSchema();

        $trimmed = trim($barcode);
        if (empty($trimmed)) {
            return [
                'success' => false,
                'status' => 'empty',
                'message' => 'Barcode tidak boleh kosong.',
            ];
        }

        // Cari record dalam rentang produksi hari tersebut
        $query = $this->getBaseQuery($selectedDate, $plant, 'ALL', 'ALL', '')
            ->where('production_scanned_data.label', $trimmed);

        $match = $query->first();

        if (! $match) {
            // Coba cari juga tanpa filter plant untuk memberikan pesan error yang jelas jika salah plant
            $crossPlantMatch = $this->getBaseQuery($selectedDate, 'ALL', 'ALL', 'ALL', '')
                ->where('production_scanned_data.label', $trimmed)
                ->first();

            if ($crossPlantMatch) {
                $itemPlant = $crossPlantMatch->plant;
                return [
                    'success' => false,
                    'status' => 'wrong_plant',
                    'message' => "Label [{$trimmed}] terdaftar di Plant {$itemPlant}, bukan pada tab yang sedang aktif ({$plant})!",
                    'item' => $crossPlantMatch,
                ];
            }

            return [
                'success' => false,
                'status' => 'not_found',
                'message' => "Label [{$trimmed}] tidak ditemukan dalam scan produksi tanggal {$selectedDate}!",
            ];
        }

        $alreadyFound = ($match->store_check_status === 'FOUND');

        // Update status menjadi FOUND
        $match->update([
            'store_check_status' => 'FOUND',
            'store_checked_by' => $userId,
            'store_checked_by_name' => $userName ?: 'Store Operator',
            'store_checked_at' => now(),
        ]);

        return [
            'success' => true,
            'status' => $alreadyFound ? 'already_found' : 'verified',
            'message' => $alreadyFound
                ? "Label [{$trimmed}] sudah diverifikasi sebelumnya ({$match->item_code} - {$match->quantity} pcs)."
                : "BERHASIL: Label [{$trimmed}] diverifikasi ADA DI STORE! ({$match->item_code} - {$match->quantity} pcs)",
            'item' => $match->fresh(['masterItem', 'dailyItemCode.user']),
        ];
    }

    /**
     * Tandai record secara massal (FOUND, NOT_FOUND, PENDING)
     */
    public function updateStatus(
        array $ids,
        string $status,
        ?int $userId = null,
        ?string $userName = null,
        ?string $note = null
    ): int {
        self::ensureSchema();

        if (empty($ids)) {
            return 0;
        }

        $validStatuses = ['PENDING', 'FOUND', 'NOT_FOUND'];
        $cleanStatus = in_array(strtoupper($status), $validStatuses) ? strtoupper($status) : 'PENDING';

        $payload = [
            'store_check_status' => $cleanStatus,
            'store_checked_by' => $cleanStatus === 'PENDING' ? null : $userId,
            'store_checked_by_name' => $cleanStatus === 'PENDING' ? null : ($userName ?: 'Store Operator'),
            'store_checked_at' => $cleanStatus === 'PENDING' ? null : now(),
        ];

        if ($note !== null) {
            $payload['store_notes'] = $note;
        }

        return ProductionScannedData::whereIn('id', $ids)->update($payload);
    }

    /**
     * Simpan catatan store pada satu item box
     */
    public function saveNote(int $id, string $note): bool
    {
        self::ensureSchema();

        $record = ProductionScannedData::find($id);
        if ($record) {
            $record->update(['store_notes' => trim($note)]);
            return true;
        }

        return false;
    }

    /**
     * Simpan / Tutup snapshot rekap harian crosscheck store
     */
    public function saveDailyReportSnapshot(
        string $selectedDate,
        string $plant,
        string $shift = 'ALL',
        ?string $notes = null,
        ?int $userId = null,
        ?string $userName = null,
        string $status = 'OPEN'
    ): StoreCrosscheckDailyReport {
        self::ensureSchema();

        $metrics = $this->getSummaryMetrics($selectedDate, $plant);

        $report = StoreCrosscheckDailyReport::updateOrCreate(
            [
                'report_date' => $selectedDate,
                'plant' => strtoupper($plant),
                'shift' => strtoupper($shift),
            ],
            [
                'total_boxes' => $metrics['total_boxes'],
                'total_qty' => $metrics['total_qty'],
                'found_boxes' => $metrics['found_boxes'],
                'found_qty' => $metrics['found_qty'],
                'missing_boxes' => $metrics['missing_boxes'],
                'missing_qty' => $metrics['missing_qty'],
                'pending_boxes' => $metrics['pending_boxes'],
                'pending_qty' => $metrics['pending_qty'],
                'accuracy_rate' => $metrics['accuracy_rate'],
                'status' => strtoupper($status),
                'verified_by' => $userId,
                'verified_by_name' => $userName ?: 'Store PIC',
                'verified_at' => now(),
                'notes' => $notes,
            ]
        );

        return $report;
    }

    /**
     * Ambil riwayat snapshot laporan harian yang tersimpan
     */
    public function getDailyReportSnapshots(string $selectedDate): array
    {
        self::ensureSchema();

        return StoreCrosscheckDailyReport::with('verifier')
            ->where('report_date', $selectedDate)
            ->orderBy('plant', 'asc')
            ->get()
            ->keyBy('plant')
            ->toArray();
    }
}
