<?php

namespace App\Livewire\Sap;

use App\Models\SapVerificationBatch;
use App\Services\SapVerificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SapMesVerification extends Component
{
    use WithFileUploads;

    public string $activeTab = 'receipt'; // 'receipt' or 'pallet'

    // Tab 1: Receipt from Production Upload & State
    public $receiptFile;
    public ?int $receiptBatchId = null;
    public ?string $receiptFileName = null;
    public ?string $receiptBatchCreatedAt = null;
    public array $receiptRawRows = [];
    public ?string $receiptMinDate = null;
    public ?string $receiptMaxDate = null;
    public string $receiptStartDate = '';
    public string $receiptEndDate = '';
    public string $receiptSearch = '';
    public string $receiptStatusFilter = 'ALL'; // ALL, MATCH, QTY_DIFF, MISSING_IN_SAP, ONLY_IN_SAP
    public array $receiptComparison = [];
    public array $receiptKpi = [];

    // Tab 2: Pallet Form / Inventory Transfer Upload & State
    public $palletFile;
    public ?int $palletBatchId = null;
    public ?string $palletFileName = null;
    public ?string $palletBatchCreatedAt = null;
    public array $palletRawRows = [];
    public ?string $palletMinDate = null;
    public ?string $palletMaxDate = null;
    public string $palletStartDate = '';
    public string $palletEndDate = '';
    public string $palletSearch = '';
    public string $palletStatusFilter = 'ALL'; // ALL, MATCH, QTY_DIFF, MISSING_IN_SAP, ONLY_IN_SAP
    public array $palletComparison = [];
    public array $palletKpi = [];

    // Modal / Accordion state
    public ?string $expandedKey = null;

    protected function getVerificationService(): SapVerificationService
    {
        return app(SapVerificationService::class);
    }

    public function mount(): void
    {
        // Otomatis muat batch verifikasi terakhir dari database jika sudah pernah diupload sebelumnya
        try {
            $latestReceipt = SapVerificationBatch::where('type', 'RECEIPT')->latest()->first();
            if ($latestReceipt) {
                $this->loadReceiptBatch($latestReceipt->id, false);
            }

            $latestPallet = SapVerificationBatch::where('type', 'PALLET')->latest()->first();
            if ($latestPallet) {
                $this->loadPalletBatch($latestPallet->id, false);
            }
        } catch (\Throwable $e) {
            // Jika tabel belum di-migrate, abaikan error cold start
            Log::warning('Could not load latest verification batch: ' . $e->getMessage());
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['receipt', 'pallet'])) {
            $this->activeTab = $tab;
            $this->expandedKey = null;
        }
    }

    public function toggleExpand(string $key): void
    {
        $this->expandedKey = ($this->expandedKey === $key) ? null : $key;
    }

    /**
     * Load specific receipt batch from database
     */
    public function loadReceiptBatch(int $batchId, bool $notify = true): void
    {
        $service = $this->getVerificationService();
        $data = $service->loadBatchData($batchId);
        if (! $data) {
            if ($notify) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Batch verifikasi tidak ditemukan.']);
            }
            return;
        }

        $batch = $data['batch'];
        $this->receiptBatchId = $batch->id;
        $this->receiptFileName = $batch->file_name;
        $this->receiptBatchCreatedAt = $batch->created_at ? $batch->created_at->format('d M Y H:i') : null;
        $this->receiptStartDate = $batch->start_date ? $batch->start_date->format('Y-m-d') : '';
        $this->receiptEndDate = $batch->end_date ? $batch->end_date->format('Y-m-d') : '';
        $this->receiptComparison = $data['comparison'];
        $this->receiptKpi = $data['kpi'];
        $this->receiptStatusFilter = 'ALL';
        $this->receiptSearch = '';
        $this->expandedKey = null;

        if ($notify) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Riwayat verifikasi batch #{$batch->id} ({$batch->file_name}) berhasil dimuat.",
            ]);
        }
    }

    /**
     * Load specific pallet batch from database
     */
    public function loadPalletBatch(int $batchId, bool $notify = true): void
    {
        $service = $this->getVerificationService();
        $data = $service->loadBatchData($batchId);
        if (! $data) {
            if ($notify) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Batch verifikasi tidak ditemukan.']);
            }
            return;
        }

        $batch = $data['batch'];
        $this->palletBatchId = $batch->id;
        $this->palletFileName = $batch->file_name;
        $this->palletBatchCreatedAt = $batch->created_at ? $batch->created_at->format('d M Y H:i') : null;
        $this->palletStartDate = $batch->start_date ? $batch->start_date->format('Y-m-d') : '';
        $this->palletEndDate = $batch->end_date ? $batch->end_date->format('Y-m-d') : '';
        $this->palletComparison = $data['comparison'];
        $this->palletKpi = $data['kpi'];
        $this->palletStatusFilter = 'ALL';
        $this->palletSearch = '';
        $this->expandedKey = null;

        if ($notify) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Riwayat verifikasi batch #{$batch->id} ({$batch->file_name}) berhasil dimuat.",
            ]);
        }
    }

    /**
     * Process File 1 (Receipt from Production)
     */
    public function uploadReceipt(): void
    {
        $this->validate([
            'receiptFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:30720', // max 30MB
        ], [
            'receiptFile.required' => 'Silakan pilih file Excel / CSV dari SAP.',
            'receiptFile.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV.',
            'receiptFile.max' => 'Ukuran file maksimal 30MB.',
        ]);

        try {
            $service = $this->getVerificationService();
            $path = $this->receiptFile->getRealPath();
            $originalName = $this->receiptFile->getClientOriginalName();

            // Simpan salinan file fisik ke storage/app/sap_verification/
            $savedPath = $this->receiptFile->storeAs(
                'sap_verification',
                'receipt_' . now()->format('Ymd_His') . '_' . $originalName
            );

            $parsed = $service->parseReceiptFile($path);
            $this->receiptRawRows = $parsed['rows'];
            $this->receiptMinDate = $parsed['min_date'];
            $this->receiptMaxDate = $parsed['max_date'];

            // Set default date range to auto-detected dates or current month
            $this->receiptStartDate = $parsed['min_date'] ?? now()->startOfMonth()->toDateString();
            $this->receiptEndDate = $parsed['max_date'] ?? now()->toDateString();

            // Run reconciliation
            $reconciled = $service->reconcileReceiptData(
                $this->receiptRawRows,
                $this->receiptStartDate,
                $this->receiptEndDate
            );

            // Simpan ke database tabel sap_verification_batches & sap_verification_items
            $batch = $service->saveBatch(
                'RECEIPT',
                $originalName,
                $savedPath,
                auth()->id(),
                $this->receiptStartDate,
                $this->receiptEndDate,
                $reconciled
            );

            $this->receiptBatchId = $batch->id;
            $this->receiptFileName = $originalName;
            $this->receiptBatchCreatedAt = $batch->created_at->format('d M Y H:i');
            $this->receiptComparison = $reconciled['comparison'];
            $this->receiptKpi = $reconciled['kpi'];
            $this->receiptStatusFilter = 'ALL';
            $this->receiptSearch = '';
            $this->expandedKey = null;
            $this->receiptFile = null;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "File SAP Receipt berhasil diproses dan disimpan ke database (Batch #{$batch->id}).",
            ]);
        } catch (\Throwable $e) {
            Log::error('Receipt verification failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal membaca/menyimpan file Excel SAP: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Re-run receipt reconciliation when user changes date range filter
     */
    public function recalculateReceipt(): void
    {
        if (empty($this->receiptRawRows)) {
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Silakan upload file baru untuk rentang tanggal berbeda.']);
            return;
        }

        $service = $this->getVerificationService();
        $reconciled = $service->reconcileReceiptData(
            $this->receiptRawRows,
            $this->receiptStartDate,
            $this->receiptEndDate
        );

        // Update database batch
        if ($this->receiptBatchId) {
            $service->saveBatch(
                'RECEIPT',
                $this->receiptFileName ?? 'Receipt_Recheck',
                null,
                auth()->id(),
                $this->receiptStartDate,
                $this->receiptEndDate,
                $reconciled
            );
        }

        $this->receiptComparison = $reconciled['comparison'];
        $this->receiptKpi = $reconciled['kpi'];
        $this->expandedKey = null;

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Rentang tanggal rekonsiliasi Receipt diperbarui.',
        ]);
    }

    public function clearReceipt(): void
    {
        $this->receiptFile = null;
        $this->receiptBatchId = null;
        $this->receiptFileName = null;
        $this->receiptBatchCreatedAt = null;
        $this->receiptRawRows = [];
        $this->receiptComparison = [];
        $this->receiptKpi = [];
        $this->receiptStartDate = '';
        $this->receiptEndDate = '';
        $this->receiptSearch = '';
        $this->receiptStatusFilter = 'ALL';
        $this->expandedKey = null;
    }

    /**
     * Process File 2 (Pallet Form / Inventory Transfer)
     */
    public function uploadPallet(): void
    {
        $this->validate([
            'palletFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:30720',
        ], [
            'palletFile.required' => 'Silakan pilih file Excel / CSV dari SAP.',
            'palletFile.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV.',
            'palletFile.max' => 'Ukuran file maksimal 30MB.',
        ]);

        try {
            $service = $this->getVerificationService();
            $path = $this->palletFile->getRealPath();
            $originalName = $this->palletFile->getClientOriginalName();

            // Simpan salinan file fisik ke storage/app/sap_verification/
            $savedPath = $this->palletFile->storeAs(
                'sap_verification',
                'pallet_' . now()->format('Ymd_His') . '_' . $originalName
            );

            $parsed = $service->parsePalletFile($path);
            $this->palletRawRows = $parsed['rows'];
            $this->palletMinDate = $parsed['min_date'];
            $this->palletMaxDate = $parsed['max_date'];

            $this->palletStartDate = $parsed['min_date'] ?? now()->startOfMonth()->toDateString();
            $this->palletEndDate = $parsed['max_date'] ?? now()->toDateString();

            $reconciled = $service->reconcilePalletData(
                $this->palletRawRows,
                $this->palletStartDate,
                $this->palletEndDate
            );

            // Simpan ke database tabel sap_verification_batches & sap_verification_items
            $batch = $service->saveBatch(
                'PALLET',
                $originalName,
                $savedPath,
                auth()->id(),
                $this->palletStartDate,
                $this->palletEndDate,
                $reconciled
            );

            $this->palletBatchId = $batch->id;
            $this->palletFileName = $originalName;
            $this->palletBatchCreatedAt = $batch->created_at->format('d M Y H:i');
            $this->palletComparison = $reconciled['comparison'];
            $this->palletKpi = $reconciled['kpi'];
            $this->palletStatusFilter = 'ALL';
            $this->palletSearch = '';
            $this->expandedKey = null;
            $this->palletFile = null;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "File SAP Pallet Transfer berhasil diproses dan disimpan ke database (Batch #{$batch->id}).",
            ]);
        } catch (\Throwable $e) {
            Log::error('Pallet verification failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal membaca/menyimpan file Excel SAP Pallet: ' . $e->getMessage(),
            ]);
        }
    }

    public function recalculatePallet(): void
    {
        if (empty($this->palletRawRows)) {
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Silakan upload file baru untuk rentang tanggal berbeda.']);
            return;
        }

        $service = $this->getVerificationService();
        $reconciled = $service->reconcilePalletData(
            $this->palletRawRows,
            $this->palletStartDate,
            $this->palletEndDate
        );

        if ($this->palletBatchId) {
            $service->saveBatch(
                'PALLET',
                $this->palletFileName ?? 'Pallet_Recheck',
                null,
                auth()->id(),
                $this->palletStartDate,
                $this->palletEndDate,
                $reconciled
            );
        }

        $this->palletComparison = $reconciled['comparison'];
        $this->palletKpi = $reconciled['kpi'];
        $this->expandedKey = null;

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Rentang tanggal rekonsiliasi Pallet diperbarui.',
        ]);
    }

    public function clearPallet(): void
    {
        $this->palletFile = null;
        $this->palletBatchId = null;
        $this->palletFileName = null;
        $this->palletBatchCreatedAt = null;
        $this->palletRawRows = [];
        $this->palletComparison = [];
        $this->palletKpi = [];
        $this->palletStartDate = '';
        $this->palletEndDate = '';
        $this->palletSearch = '';
        $this->palletStatusFilter = 'ALL';
        $this->expandedKey = null;
    }

    public function deleteBatch(int $batchId): void
    {
        $batch = SapVerificationBatch::find($batchId);
        if ($batch) {
            $type = $batch->type;
            if ($batch->file_path && Storage::exists($batch->file_path)) {
                Storage::delete($batch->file_path);
            }
            $batch->delete();

            if ($type === 'RECEIPT' && $this->receiptBatchId === $batchId) {
                $this->clearReceipt();
            } elseif ($type === 'PALLET' && $this->palletBatchId === $batchId) {
                $this->clearPallet();
            }

            $this->dispatch('notify', ['type' => 'info', 'message' => "Batch verifikasi #{$batchId} berhasil dihapus."]);
        }
    }

    /**
     * Filter items for Receipt tab
     */
    public function getFilteredReceiptListProperty(): array
    {
        if (empty($this->receiptComparison)) {
            return [];
        }

        $term = strtolower(trim($this->receiptSearch));
        $filterStatus = $this->receiptStatusFilter;

        return array_values(array_filter($this->receiptComparison, function ($item) use ($term, $filterStatus) {
            if ($filterStatus !== 'ALL' && $item['status'] !== $filterStatus) {
                return false;
            }

            if (! empty($term)) {
                $spkMatch = str_contains(strtolower((string) $item['spk_code']), $term);
                $itemMatch = str_contains(strtolower((string) $item['item_code']), $term);
                $descMatch = str_contains(strtolower((string) $item['description']), $term);
                $docsMatch = str_contains(strtolower(implode(',', $item['sap_docs'] ?? [])), $term);

                if (! ($spkMatch || $itemMatch || $descMatch || $docsMatch)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Filter items for Pallet tab
     */
    public function getFilteredPalletListProperty(): array
    {
        if (empty($this->palletComparison)) {
            return [];
        }

        $term = strtolower(trim($this->palletSearch));
        $filterStatus = $this->palletStatusFilter;

        return array_values(array_filter($this->palletComparison, function ($item) use ($term, $filterStatus) {
            if ($filterStatus !== 'ALL' && $item['status'] !== $filterStatus) {
                return false;
            }

            if (! empty($term)) {
                $pltMatch = str_contains(strtolower((string) $item['pallet_id']), $term);
                $itemMatch = str_contains(strtolower((string) $item['item_code']), $term);
                $descMatch = str_contains(strtolower((string) $item['description']), $term);
                $docsMatch = str_contains(strtolower(implode(',', $item['sap_docs'] ?? [])), $term);

                if (! ($pltMatch || $itemMatch || $descMatch || $docsMatch)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Get recent receipt batches
     */
    public function getRecentReceiptBatchesProperty()
    {
        try {
            return SapVerificationBatch::where('type', 'RECEIPT')
                ->latest()
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Get recent pallet batches
     */
    public function getRecentPalletBatchesProperty()
    {
        try {
            return SapVerificationBatch::where('type', 'PALLET')
                ->latest()
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Export Receipt Reconciliation to CSV
     */
    public function exportReceiptCsv()
    {
        $items = $this->filteredReceiptList;
        if (empty($items)) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Tidak ada data untuk diekspor.']);
            return;
        }

        $filename = 'Reconciliation_Receipt_SAP_MES_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Status Verifikasi',
                'No. SPK',
                'Item Code',
                'Item Description',
                'Warehouse',
                'Qty SAP (pcs)',
                'Qty MES (pcs)',
                'Selisih MES - SAP (pcs)',
                'Dokumen SAP',
                'Tanggal Posting SAP',
                'Tanggal Produksi MES',
            ]);

            foreach ($items as $row) {
                fputcsv($handle, [
                    $row['status_label'],
                    $row['spk_code'] ?: '-',
                    $row['item_code'],
                    $row['description'],
                    $row['warehouse'],
                    $row['sap_qty'],
                    $row['mes_qty'],
                    $row['diff_qty'],
                    implode('; ', $row['sap_docs'] ?? []),
                    implode('; ', $row['sap_dates'] ?? []),
                    implode('; ', $row['mes_dates'] ?? []),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export Pallet Reconciliation to CSV
     */
    public function exportPalletCsv()
    {
        $items = $this->filteredPalletList;
        if (empty($items)) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Tidak ada data untuk diekspor.']);
            return;
        }

        $filename = 'Reconciliation_Pallet_SAP_MES_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Status Verifikasi',
                'No. Pallet (PLT)',
                'Item Code',
                'Item Description',
                'Gudang Asal',
                'Gudang Tujuan',
                'Qty SAP (pcs)',
                'Qty MES (pcs)',
                'Selisih MES - SAP (pcs)',
                'Dokumen Transfer SAP',
                'Tanggal Posting SAP',
                'Tanggal Produksi MES',
            ]);

            foreach ($items as $row) {
                fputcsv($handle, [
                    $row['status_label'],
                    $row['pallet_id'] ?: '-',
                    $row['item_code'],
                    $row['description'],
                    $row['from_whse'],
                    $row['to_whse'],
                    $row['sap_qty'],
                    $row['mes_qty'],
                    $row['diff_qty'],
                    implode('; ', $row['sap_docs'] ?? []),
                    implode('; ', $row['sap_dates'] ?? []),
                    $row['mes_prod_date'] ?: '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        return view('livewire.sap.sap-mes-verification', [
            'receiptList' => $this->filteredReceiptList,
            'palletList' => $this->filteredPalletList,
            'recentReceiptBatches' => $this->recentReceiptBatches,
            'recentPalletBatches' => $this->recentPalletBatches,
        ])->layout('layouts.app', ['title' => 'Verifikasi Data MES vs SAP (Rekonsiliasi Excel)']);
    }
}
