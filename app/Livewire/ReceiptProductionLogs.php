<?php

namespace App\Livewire;

use App\Jobs\PushSingleReceiptProductionJob;
use App\Models\ProductionScannedData;
use App\Models\ProductionSummary;
use App\Services\ReceiptProductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class ReceiptProductionLogs extends Component
{
    use WithPagination;

    public string $filterDate = '';

    public string $filterMonth = '';

    public string $filterMode = 'daily'; // 'daily' or 'monthly'

    public string $filterSpk = '';

    public string $filterStatus = '';

    public int $perPage = 50;

    public string $filterItemCode = '';

    public string $filterWarehouse = '';

    public array $expandedRows = [];

    public array $rowDetails = [];

    public array $pushingRows = []; // Track which rows are currently pushing

    public array $pushResults = []; // Store push results

    public array $selectedLogs = [];

    public bool $selectAll = false;

    public ?array $activeErrorModal = null;

    public ?array $actionMessage = null;

    public function mount(): void
    {
        Carbon::setLocale('id');
        $this->filterDate = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $this->filterMonth = now()->timezone('Asia/Jakarta')->format('Y-m');
    }

    private function clearStatsCache(): void
    {
        $key = $this->filterMode === 'monthly' ? $this->filterMonth : $this->filterDate;
        cache()->forget("receipt_stats_{$key}_all");
        cache()->forget("receipt_stats_{$key}_FFI");
        cache()->forget("receipt_stats_{$key}_KRFFI");
        cache()->forget("receipt_stats_{$key}");
    }

    public function updatingFilterMode(): void
    {
        $this->resetPage();
        $this->clearStatsCache();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatingFilterMonth(): void
    {
        $this->resetPage();
        $this->clearStatsCache();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    /**
     * Apply date filter based on current mode (daily or monthly)
     */
    private function applyDateFilter($query, string $column = 'production_summary.created_date')
    {
        if ($this->filterMode === 'monthly' && $this->filterMonth) {
            $start = Carbon::createFromFormat('Y-m', $this->filterMonth)->startOfMonth()->toDateString();
            $end = Carbon::createFromFormat('Y-m', $this->filterMonth)->endOfMonth()->toDateString();

            return $query->whereBetween($column, [$start, $end]);
        }

        if ($this->filterMode === 'daily' && $this->filterDate) {
            return $query->where($column, $this->filterDate);
        }

        return $query;
    }

    public function updatingFilterItemCode()
    {
        $this->resetPage();
        $this->clearStatsCache();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatingFilterSpk()
    {
        $this->resetPage();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatingFilterWarehouse()
    {
        $this->resetPage();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatingFilterDate()
    {
        $this->resetPage();
        $this->clearStatsCache();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
        $this->clearStatsCache();
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatedPage(): void
    {
        $this->selectedLogs = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedLogs = collect($this->logs->items())
                ->filter(fn ($row) => in_array($row->sap_sent, [0, 2, 3]))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedLogs = [];
        }
    }

    public function markAsIgnored(int $id): void
    {
        DB::table('production_summary')
            ->where('id', $id)
            ->update([
                'sap_sent' => 99,
                'updated_at' => now(),
            ]);

        if ($this->activeErrorModal && ($this->activeErrorModal['summary_id'] ?? null) === $id) {
            $this->activeErrorModal = null;
        }

        $this->clearStatsCache();
        $this->dispatch('push-notification', ['status' => 'success', 'message' => 'SPK berhasil diabaikan']);
    }

    public function markAsPending(int $id): void
    {
        DB::table('production_summary')
            ->where('id', $id)
            ->update([
                'sap_sent' => 0,
                'sap_sent_at' => null,
                'updated_at' => now(),
            ]);

        if ($this->activeErrorModal && ($this->activeErrorModal['summary_id'] ?? null) === $id) {
            $this->activeErrorModal = null;
        }

        $this->clearStatsCache();
        $this->dispatch('push-notification', ['status' => 'success', 'message' => 'SPK direset ke pending']);
    }

    public function markAsSuccess(int $id): void
    {
        DB::table('production_summary')
            ->where('id', $id)
            ->update([
                'sap_sent' => 1,
                'sap_sent_at' => now(),
                'updated_at' => now(),
            ]);

        if ($this->activeErrorModal && ($this->activeErrorModal['summary_id'] ?? null) === $id) {
            $this->activeErrorModal = null;
        }

        $this->clearStatsCache();
        $this->dispatch('push-notification', ['status' => 'success', 'message' => 'SPK berhasil ditandai sebagai sukses']);
    }

    public function ignoreAllFiltered(): void
    {
        if ($this->filterStatus === 'sent' || $this->filterStatus === 'ignored') {
            $this->dispatch('push-notification', ['status' => 'warning', 'message' => 'Tidak ada data pending terfilter untuk diabaikan']);

            return;
        }

        $query = DB::table('production_summary')
            ->whereIn('warehouse', ['FFI', 'KRFFI'])
            ->whereIn('production_summary.sap_sent', [0, 2, 3])
            ->when(
                $this->filterWarehouse,
                fn ($q) => $q->where('warehouse', $this->filterWarehouse)
            )
            ->leftJoin(
                DB::raw('(SELECT spk_code, MIN(item_code) as item_code
                          FROM production_scanned_data
                          GROUP BY spk_code) as psd'),
                'production_summary.spk_code',
                '=',
                'psd.spk_code'
            )
            ->when(
                $this->filterDate || $this->filterMonth,
                fn ($q) => $this->applyDateFilter($q, 'production_summary.created_date')
            )
            ->when(
                $this->filterSpk,
                fn ($q) => $q->where('production_summary.spk_code', 'like', "%{$this->filterSpk}%")
            )
            ->when(
                $this->filterItemCode,
                fn ($q) => $q->where('psd.item_code', 'like', "%{$this->filterItemCode}%")
            );

        $ids = $query->pluck('production_summary.id')->toArray();

        if (empty($ids)) {
            $this->dispatch('push-notification', ['status' => 'warning', 'message' => 'Tidak ada data pending terfilter untuk diabaikan']);

            return;
        }

        DB::table('production_summary')
            ->whereIn('id', $ids)
            ->update([
                'sap_sent' => 99,
                'updated_at' => now(),
            ]);

        $this->clearStatsCache();

        $this->dispatch('push-notification', ['status' => 'success', 'message' => count($ids) . ' SPK berhasil diabaikan']);
    }

    public function ignoreSelected(): void
    {
        if (empty($this->selectedLogs)) {
            $this->dispatch('push-notification', ['status' => 'warning', 'message' => 'Tidak ada data terpilih untuk diabaikan']);

            return;
        }

        $count = count($this->selectedLogs);
        $totalQty = (int) DB::table('production_summary')->whereIn('id', $this->selectedLogs)->sum('total_quantity');

        DB::table('production_summary')
            ->whereIn('id', $this->selectedLogs)
            ->update([
                'sap_sent' => 99,
                'updated_at' => now(),
            ]);

        $this->selectedLogs = [];
        $this->selectAll = false;

        $this->clearStatsCache();

        $formattedQty = number_format($totalQty);
        $this->dispatch('push-notification', ['status' => 'success', 'message' => "{$count} SPK ({$formattedQty} pcs) berhasil diabaikan"]);
    }

    /**
     * Manual push single SPK to SAP (Direct Live Execution)
     */
    public function pushToSapManual(int $summaryId, ?ReceiptProductionService $service = null): void
    {
        $service = $service ?? app(ReceiptProductionService::class);

        try {
            // Cek apakah SPK ada dan bisa dikirim
            $summary = DB::table('production_summary')
                ->where('id', $summaryId)
                ->first();

            if (! $summary) {
                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => 'SPK tidak ditemukan',
                ]);

                return;
            }

            // Cari scanned data
            $scannedData = DB::table('production_scanned_data')
                ->where('spk_code', $summary->spk_code)
                ->first();

            if (! $scannedData) {
                DB::table('production_summary')
                    ->where('id', $summaryId)
                    ->update(['sap_sent' => 3, 'updated_at' => now()]);

                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => "Scanned data tidak ditemukan untuk SPK {$summary->spk_code}",
                ]);

                return;
            }

            // Eksekusi langsung ke SAP (synchronous) tanpa harus antre di queue worker
            $response = $service->pushSingleRecord($summary, $scannedData, false);

            if ($this->activeErrorModal && ($this->activeErrorModal['summary_id'] ?? null) === $summaryId) {
                $this->activeErrorModal = null;
            }

            $this->clearStatsCache();

            if ($response['success']) {
                $this->dispatch('push-notification', [
                    'status' => 'success',
                    'message' => "SPK {$summary->spk_code} berhasil dikirim ke SAP",
                ]);
            } else {
                $errMsg = $response['message'] ?? 'Gagal dikirim ke SAP';
                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => "SPK {$summary->spk_code} gagal: {$errMsg}",
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Manual direct push error', [
                'summary_id' => $summaryId,
                'error' => $e->getMessage(),
            ]);

            DB::table('production_summary')
                ->where('id', $summaryId)
                ->where('sap_sent', 2)
                ->update(['sap_sent' => 3, 'updated_at' => now()]);

            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Gagal mengirim: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Manual push multiple pending SPKs (Batch)
     */
    public function pushPendingBatchToSap(): void
    {
        try {
            $this->dispatch('batch-push-start');

            // Ambil semua pending records sesuai filter
            // Exclude status 2 (sedang diproses) dari batch push
            $summaries = DB::table('production_summary')
                ->whereIn('warehouse', ['FFI', 'KRFFI'])
                ->when(
                    $this->filterWarehouse,
                    fn ($q) => $q->where('warehouse', $this->filterWarehouse)
                )
                ->whereIn('sap_sent', [0, 3]) // Hanya pending atau failed
                ->when(
                    $this->filterDate || $this->filterMonth,
                    fn ($q) => $this->applyDateFilter($q, 'created_date')
                )
                ->when(
                    $this->filterSpk,
                    fn ($q) => $q->where('spk_code', 'like', "%{$this->filterSpk}%")
                )
                ->get();

            if ($summaries->isEmpty()) {
                $this->dispatch('push-notification', [
                    'status' => 'warning',
                    'message' => 'Tidak ada SPK pending untuk dikirim',
                ]);

                return;
            }

            $dispatchedCount = 0;

            foreach ($summaries as $summary) {
                // Lock record di UI thread segera
                $locked = DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->whereIn('sap_sent', [0, 3])
                    ->update(['sap_sent' => 2, 'updated_at' => now()]);

                if ($locked) {
                    PushSingleReceiptProductionJob::dispatch($summary->id);
                    $dispatchedCount++;
                }
            }

            $this->clearStatsCache();

            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => "Batch push dikirim ke background queue: {$dispatchedCount} SPK sedang diproses",
            ]);

            $this->dispatch('sap-push-success');

        } catch (\Throwable $e) {
            Log::error('Batch push error', ['error' => $e->getMessage()]);
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Error batch push: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Push selected items to SAP (Background Queue)
     */
    public function pushSelectedToSap(): void
    {
        if (empty($this->selectedLogs)) {
            $this->dispatch('push-notification', [
                'status' => 'warning',
                'message' => 'Tidak ada SPK terpilih untuk dikirim',
            ]);

            return;
        }

        try {
            $this->dispatch('batch-push-start');

            $summaries = DB::table('production_summary')
                ->whereIn('id', $this->selectedLogs)
                ->whereIn('sap_sent', [0, 3]) // Hanya pending atau failed
                ->get();

            if ($summaries->isEmpty()) {
                $this->dispatch('push-notification', [
                    'status' => 'warning',
                    'message' => 'Tidak ada SPK pending/gagal terpilih yang bisa dikirim',
                ]);

                return;
            }

            $dispatchedCount = 0;
            $dispatchedQty = 0;

            foreach ($summaries as $summary) {
                // Lock record di UI thread segera agar status berubah jadi "Processing"
                $locked = DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->whereIn('sap_sent', [0, 3])
                    ->update(['sap_sent' => 2, 'updated_at' => now()]);

                if ($locked) {
                    PushSingleReceiptProductionJob::dispatch($summary->id);
                    $dispatchedCount++;
                    $dispatchedQty += (int) $summary->total_quantity;
                }
            }

            $this->selectedLogs = [];
            $this->selectAll = false;

            $this->clearStatsCache();

            $formattedQty = number_format($dispatchedQty);
            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => "Bulk push terpilih: {$dispatchedCount} SPK ({$formattedQty} pcs) dikirim ke background queue",
            ]);

            $this->dispatch('sap-push-success');

        } catch (\Throwable $e) {
            Log::error('Push selected error', ['error' => $e->getMessage()]);
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate production summary khusus untuk rentang shift 07:30 s/d 07:30 besoknya (hanya processed = 0 & summary_id is null).
     */
    public function regenerateSummaryForDate(?string $date = null): void
    {
        $targetDate = $date ?: ($this->filterMode === 'daily' ? $this->filterDate : null);

        if (! $targetDate) {
            $this->dispatch('push-notification', [
                'status' => 'warning',
                'message' => 'Pilih mode Harian dan tentukan tanggal yang ingin di-generate summary.',
            ]);

            return;
        }

        $start = Carbon::parse($targetDate)->setTime(7, 30, 0)->toDateTimeString();
        $end = Carbon::parse($targetDate)->addDay()->setTime(7, 30, 0)->toDateTimeString();

        DB::beginTransaction();
        try {
            // Ambil data scan yang processed = 0 dan summary_id IS NULL dalam rentang 07:30 s/d 07:30 besok
            $unprocessedData = ProductionScannedData::where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->whereIn('warehouse', ['FFI', 'KRFFI'])
                ->where('processed', false)
                ->whereNull('summary_id')
                ->lockForUpdate()
                ->get();

            if ($unprocessedData->isEmpty()) {
                DB::commit();
                $this->clearStatsCache();
                $msg = "Tidak ada data scan baru (processed: 0 & summary_id: null) untuk tanggal {$targetDate} (07:30 s/d 07:30 besok).";
                $this->actionMessage = ['status' => 'warning', 'message' => $msg];
                $this->dispatch('push-notification', [
                    'status' => 'warning',
                    'message' => $msg,
                ]);

                return;
            }

            // Deduplicate: jika ada record dengan spk_code + label yang sama, ambil yang pertama
            $unprocessedData = $unprocessedData->unique(function ($item) {
                return $item->spk_code . '||' . $item->label;
            });

            // Group per SPK + Warehouse + 10-Minute interval block
            $summaries = $unprocessedData->groupBy(function ($item) {
                $createdAt = Carbon::parse($item->created_at);
                $minuteBlock = floor($createdAt->minute / 10) * 10;
                $timeWindow = $createdAt->copy()->minute($minuteBlock)->second(0)->format('H:i');

                return $item->spk_code . '||' . $item->warehouse . '||' . $createdAt->toDateString() . '||' . $timeWindow;
            });

            $processedIds = [];
            $generatedCount = 0;
            $totalQty = 0;

            foreach ($summaries as $groupKey => $group) {
                $total_quantity = $group->sum('quantity');
                $first = $group->first();

                $summary = ProductionSummary::create([
                    'spk_code' => $first->spk_code,
                    'total_quantity' => $total_quantity,
                    'warehouse' => $first->warehouse,
                    'label' => 'all',
                    'created_date' => $targetDate,
                    'sap_sent' => 0,
                    'sap_sent_at' => null,
                ]);

                $groupIds = $group->pluck('id')->toArray();
                $processedIds = array_merge($processedIds, $groupIds);

                ProductionScannedData::whereIn('id', $groupIds)
                    ->update([
                        'processed' => true,
                        'summary_id' => $summary->id,
                    ]);

                $generatedCount++;
                $totalQty += $total_quantity;
            }

            if (Schema::hasTable('api_logs')) {
                DB::table('api_logs')->insert([
                    'api_name' => 'PRODUCTION_SUMMARY',
                    'method' => 'INTERNAL',
                    'endpoint' => 'livewire:receipt-production-logs:regenerate',
                    'request_payload' => json_encode([
                        'target_date' => $targetDate,
                        'start_time' => $start,
                        'end_time' => $end,
                        'processed_ids' => $processedIds,
                    ], JSON_PRETTY_PRINT),
                    'response_payload' => json_encode([
                        'generated_count' => $generatedCount,
                        'total_quantity' => $totalQty,
                        'target_date' => $targetDate,
                    ], JSON_PRETTY_PRINT),
                    'status_code' => 200,
                    'status' => 'success',
                    'message' => "Generated {$generatedCount} summaries (Total: {$totalQty} pcs) for {$targetDate}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $this->clearStatsCache();
            $this->resetPage();

            $formattedQty = number_format($totalQty);
            $msg = "Berhasil generate {$generatedCount} summary (Total: {$formattedQty} pcs) untuk tanggal {$targetDate}!";
            $this->actionMessage = ['status' => 'success', 'message' => $msg];
            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => $msg,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error generating production summary', [
                'target_date' => $targetDate,
                'error' => $e->getMessage(),
            ]);

            $msg = 'Gagal generate summary: ' . $e->getMessage();
            $this->actionMessage = ['status' => 'error', 'message' => $msg];
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $msg,
            ]);
        }
    }

    /**
     * Push single scanned detail item (jika perlu)
     */
    public function pushDetailToSap(int $detailId, int $summaryId): void
    {
        try {
            $detail = DB::table('production_scanned_data')
                ->where('id', $detailId)
                ->first();

            if (! $detail) {
                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => 'Detail tidak ditemukan',
                ]);

                return;
            }

            // Get summary for context
            $summary = DB::table('production_summary')
                ->where('id', $summaryId)
                ->first();

            $service = app(ReceiptProductionService::class);
            $response = $service->pushSingleRecord($summary, $detail);

            if ($response['success']) {
                $this->dispatch('push-notification', [
                    'status' => 'success',
                    'message' => 'Detail berhasil dikirim ke SAP',
                ]);
                $this->dispatch('sap-push-success');
            } else {
                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => $response['message'] ?? 'Gagal mengirim detail',
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Detail push error', ['error' => $e->getMessage()]);
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function baseQuery()
    {
        return DB::table('production_summary')
            ->whereIn('warehouse', ['FFI', 'KRFFI'])
            ->when(
                $this->filterWarehouse,
                fn ($q) => $q->where('warehouse', $this->filterWarehouse)
            );
    }

    public function getLogsProperty()
    {
        $paginatedLogs = $this->baseQuery()
            ->select(
                'production_summary.id',
                'production_summary.spk_code',
                'production_summary.total_quantity',
                'production_summary.warehouse',
                'production_summary.label',
                'production_summary.sap_sent',
                'production_summary.sap_sent_at',
                'production_summary.created_date',
                'production_summary.created_at'
            )
            ->when(
                $this->filterDate || $this->filterMonth,
                fn ($q) => $this->applyDateFilter($q, 'production_summary.created_date')
            )
            ->when(
                $this->filterSpk,
                fn ($q) => $q->where('production_summary.spk_code', 'like', "%{$this->filterSpk}%")
            )
            ->when($this->filterItemCode, function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('production_scanned_data')
                        ->whereColumn('production_scanned_data.spk_code', 'production_summary.spk_code')
                        ->where('production_scanned_data.item_code', 'like', "%{$this->filterItemCode}%");
                });
            })
            ->when(
                $this->filterStatus === 'sent',
                fn ($q) => $q->where('production_summary.sap_sent', 1)
            )
            ->when(
                $this->filterStatus === 'pending',
                fn ($q) =>
                // Pending = belum terkirim (0), stuck/processing (2), atau gagal/timeout (3)
                $q->whereIn('production_summary.sap_sent', [0, 2, 3])
            )
            ->when(
                $this->filterStatus === 'ignored',
                fn ($q) => $q->where('production_summary.sap_sent', 99)
            )

            ->orderBy('production_summary.created_date', 'desc')
            ->orderBy('production_summary.id', 'desc')
            ->paginate($this->perPage);

        // Lazy load item_code untuk 50 baris yang sedang ditampilkan
        $summaryIds = $paginatedLogs->pluck('id')->toArray();
        $itemCodesMap = [];
        if (! empty($summaryIds)) {
            $itemCodesMap = DB::table('production_scanned_data')
                ->whereIn('summary_id', $summaryIds)
                ->groupBy('summary_id')
                ->select('summary_id', DB::raw('MIN(item_code) as item_code'))
                ->pluck('item_code', 'summary_id')
                ->toArray();
        }

        // Lazy load error explanation dari api_logs untuk baris yang belum sukses (failed/pending)
        $failedSpks = collect($paginatedLogs->items())
            ->whereIn('sap_sent', [0, 2, 3])
            ->pluck('spk_code')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $errorMap = [];
        if (! empty($failedSpks) && \Illuminate\Support\Facades\Schema::hasTable('api_logs')) {
            $errorLogs = DB::table('api_logs')
                ->where('api_name', 'receipt_production')
                ->where('status', 'failed')
                ->where('message', 'not like', '%No unprocessed%')
                ->where(function ($q) use ($failedSpks) {
                    foreach ($failedSpks as $spk) {
                        $q->orWhere('message', 'like', "%{$spk}%");
                    }
                })
                ->orderBy('id', 'desc')
                ->take(100)
                ->get(['id', 'message', 'status_code', 'response_payload', 'created_at']);

            foreach ($errorLogs as $el) {
                foreach ($failedSpks as $spk) {
                    if (! isset($errorMap[$spk]) && str_contains($el->message, $spk)) {
                        $parsed = \App\Services\SapErrorParser::parse($el->message, $el->status_code, $spk);
                        $parsed['log_id'] = $el->id;
                        $parsed['logged_at'] = $el->created_at ? Carbon::parse($el->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i:s') : null;
                        $errorMap[$spk] = $parsed;
                    }
                }
            }
        }

        foreach ($paginatedLogs->items() as $item) {
            $item->item_code = $itemCodesMap[$item->id] ?? '—';
            $item->error_summary = $errorMap[$item->spk_code] ?? null;
        }

        return $paginatedLogs;
    }

    public function showErrorDetail(string $spkCode, int $summaryId): void
    {
        $summary = DB::table('production_summary')->where('id', $summaryId)->first();
        if (! $summary) {
            return;
        }

        $log = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('api_logs')) {
            $log = DB::table('api_logs')
                ->where('api_name', 'receipt_production')
                ->where('status', 'failed')
                ->where('message', 'like', "%{$spkCode}%")
                ->latest('id')
                ->first();
        }

        $parsed = \App\Services\SapErrorParser::parse($log?->message ?? null, $log?->status_code ?? null, $spkCode);

        $itemCode = null;
        if (Schema::hasTable('production_scanned_data')) {
            $itemCode = DB::table('production_scanned_data')->where('summary_id', $summary->id)->value('item_code');
        } elseif (Schema::hasTable('scanned_data')) {
            $itemCode = DB::table('scanned_data')->where('summary_id', $summary->id)->value('item_code');
        }

        if (! $itemCode && Schema::hasTable('spk_masters')) {
            $itemCode = DB::table('spk_masters')->where('spk_code', $spkCode)->value('item_code');
        }

        $this->activeErrorModal = [
            'summary_id' => $summary->id,
            'spk_code' => $spkCode,
            'item_code' => $itemCode ?: '—',
            'warehouse' => $summary->warehouse,
            'total_quantity' => $summary->total_quantity,
            'created_date' => $summary->created_date,
            'sap_sent' => $summary->sap_sent,
            'error' => $parsed,
            'log_id' => $log?->id,
            'logged_at' => $log?->created_at ? Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i:s') : null,
        ];
    }

    public function closeErrorModal(): void
    {
        $this->activeErrorModal = null;
    }

    public function getFilteredTotalQtyProperty()
    {
        return $this->baseQuery()
            ->when(
                $this->filterDate || $this->filterMonth,
                fn ($q) => $this->applyDateFilter($q, 'production_summary.created_date')
            )
            ->when(
                $this->filterSpk,
                fn ($q) => $q->where('production_summary.spk_code', 'like', "%{$this->filterSpk}%")
            )
            ->when($this->filterItemCode, function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('production_scanned_data')
                        ->whereColumn('production_scanned_data.spk_code', 'production_summary.spk_code')
                        ->where('production_scanned_data.item_code', 'like', "%{$this->filterItemCode}%");
                });
            })
            ->when($this->filterStatus === 'sent', fn ($q) => $q->where('production_summary.sap_sent', 1))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereIn('production_summary.sap_sent', [0, 2, 3]))
            ->when($this->filterStatus === 'ignored', fn ($q) => $q->where('production_summary.sap_sent', 99))
            ->sum('production_summary.total_quantity');
    }

    public function getSelectedSummaryProperty(): array
    {
        if (empty($this->selectedLogs)) {
            return [
                'count' => 0,
                'total_qty' => 0,
            ];
        }

        $result = DB::table('production_summary')
            ->whereIn('id', $this->selectedLogs)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_quantity), 0) as total_qty')
            ->first();

        return [
            'count' => (int) ($result->count ?? 0),
            'total_qty' => (int) ($result->total_qty ?? 0),
        ];
    }

    public function getStatsProperty()
    {
        $dateKey = $this->filterMode === 'monthly' ? $this->filterMonth : $this->filterDate;
        $cacheKey = "receipt_stats_{$dateKey}_" . ($this->filterWarehouse ?: 'all');

        return cache()->remember(
            $cacheKey,
            now()->addSeconds(30),
            function () {
                $base = DB::table('production_summary')
                    ->whereIn('warehouse', ['FFI', 'KRFFI'])
                    ->when(
                        $this->filterWarehouse,
                        fn ($q) => $q->where('warehouse', $this->filterWarehouse)
                    )
                    ->when(
                        $this->filterDate || $this->filterMonth,
                        fn ($q) => $this->applyDateFilter($q, 'created_date')
                    );

                $result = (clone $base)
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN sap_sent = 1  THEN 1 ELSE 0 END) as sent,
                        SUM(CASE WHEN sap_sent IN (0, 2, 3) THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN sap_sent = 99 THEN 1 ELSE 0 END) as ignored,
                        SUM(CASE WHEN sap_sent = 2  THEN 1 ELSE 0 END) as processing,
                        SUM(total_quantity) as total_qty
                    ')
                    ->first();

                return [
                    'total' => $result->total ?? 0,
                    'sent' => $result->sent ?? 0,
                    'pending' => $result->pending ?? 0,
                    'ignored' => $result->ignored ?? 0,
                    'processing' => $result->processing ?? 0,
                    'total_qty' => $result->total_qty ?? 0,
                ];
            }
        );
    }

    public function toggleDetail(int $summaryId): void
    {
        if (isset($this->expandedRows[$summaryId])) {
            unset($this->expandedRows[$summaryId]);
            unset($this->rowDetails[$summaryId]);

            return;
        }

        $this->expandedRows[$summaryId] = true;

        $this->rowDetails[$summaryId] = DB::table('production_scanned_data')
            ->where('summary_id', $summaryId)
            ->select('id', 'spk_code', 'item_code', 'quantity', 'label', 'user', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'spk_code' => $d->spk_code,
                'item_code' => $d->item_code,
                'quantity' => $d->quantity,
                'label' => $d->label,
                'user' => $d->user,
                'created_at' => Carbon::parse($d->created_at)->timezone('Asia/Jakarta')->format('H:i:s'),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.receipt-production-logs');
    }
}
