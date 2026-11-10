<?php

namespace App\Livewire\Production;

use App\Models\MasterMachine;
use App\Models\ProductionMaterialUsage;
use App\Services\ProductionMaterialService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;

class ProductionMaterialStation extends Component
{
    use WithPagination;

    public string $activeTab = 'receive'; // 'receive', 'usage', 'stock'

    // Tab 1: Penerimaan Material Store
    public string $barcodeInput = '';
    public string $searchIncoming = '';
    public string $statusFilterIncoming = 'PENDING'; // 'ALL', 'PENDING', 'RECEIVED'
    public string $startDateIncoming = '';
    public string $endDateIncoming = '';
    public ?int $selectedOutgoingId = null;
    public string $verificationNotes = '';
    public bool $showVerifyModal = false;

    // Tab 2: Input Pemakaian Material
    public ?int $selectedBatchId = null;
    public string $machineNo = '';
    public string $spkCode = '';
    public ?float $qtyUsed = null;
    public string $purpose = 'PRODUKSI'; // 'PRODUKSI', 'PURGING', 'TRIAL', 'REJECT'
    public string $usedAt = '';
    public string $usageRemarks = '';

    // Tab 3: Stok Lantai Produksi & Riwayat
    public string $searchStock = '';
    public string $historySearch = '';
    public string $historyMachine = '';
    public string $historyPurpose = 'ALL';
    public string $historyStartDate = '';
    public string $historyEndDate = '';

    protected function getService(): ProductionMaterialService
    {
        return app(ProductionMaterialService::class);
    }

    public function mount(): void
    {
        $this->usedAt = now('Asia/Jakarta')->format('Y-m-d\TH:i');
        $this->startDateIncoming = now('Asia/Jakarta')->subDays(7)->toDateString();
        $this->endDateIncoming = now('Asia/Jakarta')->toDateString();
        $this->historyStartDate = now('Asia/Jakarta')->startOfMonth()->toDateString();
        $this->historyEndDate = now('Asia/Jakarta')->toDateString();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['receive', 'usage', 'stock'])) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function updatingSearchIncoming(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilterIncoming(): void
    {
        $this->resetPage();
    }

    // ==========================================
    // TAB 1 ACTIONS: VERIFIKASI PENERIMAAN
    // ==========================================

    /**
     * Verifikasi cepat via Barcode Scanner
     */
    public function quickScanReceive(): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) {
            return;
        }

        try {
            $service = $this->getService();
            $outgoing = $service->verifyReceiptByBarcode($code, auth()->id() ?? 1, auth()->user()?->name ?? 'Operator Produksi');

            $this->barcodeInput = '';
            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => "✓ Berhasil verifikasi: {$outgoing->outgoing_code} ({$outgoing->item_code} - {$outgoing->qty_taken} KG) telah diterima di produksi.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openVerifyModal(int $id): void
    {
        $this->selectedOutgoingId = $id;
        $this->verificationNotes = '';
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal(): void
    {
        $this->selectedOutgoingId = null;
        $this->verificationNotes = '';
        $this->showVerifyModal = false;
    }

    public function confirmVerification(): void
    {
        if (! $this->selectedOutgoingId) {
            return;
        }

        try {
            $service = $this->getService();
            $outgoing = $service->verifyReceipt(
                $this->selectedOutgoingId,
                auth()->id() ?? 1,
                auth()->user()?->name ?? 'Operator Produksi',
                $this->verificationNotes
            );

            $this->closeVerifyModal();
            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => "✓ Material {$outgoing->outgoing_code} ({$outgoing->qty_taken} KG) resmi diterima di lantai produksi.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function revertReceipt(int $id): void
    {
        try {
            $service = $this->getService();
            $outgoing = $service->revertReceipt($id);

            $this->dispatch('push-notification', [
                'status' => 'warning',
                'message' => "Penerimaan material {$outgoing->outgoing_code} telah dibatalkan ke status Pending.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // ==========================================
    // TAB 2 ACTIONS: INPUT PEMAKAIAN MATERIAL
    // ==========================================

    public function selectBatchForUsage(int $outgoingId): void
    {
        $this->selectedBatchId = $outgoingId;
        $this->activeTab = 'usage';
    }

    public function saveMaterialUsage(): void
    {
        $this->validate([
            'selectedBatchId' => 'required|integer',
            'qtyUsed' => 'required|numeric|min:0.01',
            'machineNo' => 'required|string|max:50',
            'purpose' => 'required|string',
        ], [
            'selectedBatchId.required' => 'Wajib memilih batch material yang akan digunakan.',
            'qtyUsed.required' => 'Kuantitas pemakaian wajib diisi.',
            'qtyUsed.min' => 'Kuantitas pemakaian minimal 0.01 KG.',
            'machineNo.required' => 'Nomor mesin wajib dipilih / diisi.',
            'purpose.required' => 'Tujuan pemakaian wajib dipilih.',
        ]);

        try {
            $service = $this->getService();
            $usage = $service->logMaterialUsage([
                'outgoing_id' => $this->selectedBatchId,
                'machine_no' => strtoupper(trim($this->machineNo)),
                'spk_code' => ! empty($this->spkCode) ? trim($this->spkCode) : null,
                'qty_used' => $this->qtyUsed,
                'purpose' => $this->purpose,
                'used_at' => $this->usedAt ?: now(),
                'remarks' => $this->usageRemarks,
            ], auth()->id() ?? 1, auth()->user()?->name ?? 'Material Man Produksi');

            $this->reset(['selectedBatchId', 'qtyUsed', 'machineNo', 'spkCode', 'usageRemarks']);
            $this->purpose = 'PRODUKSI';
            $this->usedAt = now('Asia/Jakarta')->format('Y-m-d\TH:i');

            $this->dispatch('push-notification', [
                'status' => 'success',
                'message' => "✓ Pemakaian material {$usage->item_code} sebanyak {$usage->qty_used} KG di Mesin {$usage->machine_no} berhasil dicatat.",
            ]);

            $this->activeTab = 'stock';
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // ==========================================
    // TAB 3 ACTIONS: RIWAYAT & STOK
    // ==========================================

    public function deleteUsage(int $id): void
    {
        try {
            $service = $this->getService();
            $service->deleteMaterialUsage($id);

            $this->dispatch('push-notification', [
                'status' => 'info',
                'message' => 'Log pemakaian material berhasil dihapus dan stok dikembalikan.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function exportUsageCsv()
    {
        $usages = ProductionMaterialUsage::query()
            ->with(['outgoing.material'])
            ->when($this->historySearch, function ($q) {
                $s = $this->historySearch;
                $q->where(function ($sub) use ($s) {
                    $sub->where('item_code', 'LIKE', "%{$s}%")
                        ->orWhere('pallet_id', 'LIKE', "%{$s}%")
                        ->orWhere('spk_code', 'LIKE', "%{$s}%")
                        ->orWhere('item_name', 'LIKE', "%{$s}%");
                });
            })
            ->when($this->historyMachine, fn ($q) => $q->where('machine_no', $this->historyMachine))
            ->when($this->historyPurpose !== 'ALL', fn ($q) => $q->where('purpose', $this->historyPurpose))
            ->when($this->historyStartDate && $this->historyEndDate, function ($q) {
                $q->whereBetween('used_at', [
                    Carbon::parse($this->historyStartDate)->startOfDay(),
                    Carbon::parse($this->historyEndDate)->endOfDay(),
                ]);
            })
            ->orderByDesc('used_at')
            ->get();

        $filename = 'log_pemakaian_material_produksi_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($usages) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'ID',
                'Waktu Pemakaian',
                'Mesin',
                'No. SPK',
                'Kode Material',
                'Nama Material',
                'Pallet Asal',
                'Qty Digunakan (KG)',
                'Tujuan',
                'PIC Pemakai',
                'Keterangan',
            ]);

            foreach ($usages as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->used_at ? $u->used_at->format('Y-m-d H:i') : '-',
                    $u->machine_no ?: '-',
                    $u->spk_code ?: '-',
                    $u->item_code,
                    $u->item_name ?: ($u->outgoing?->material?->item_description ?: '-'),
                    $u->pallet_id ?: '-',
                    number_format((float) $u->qty_used, 2, '.', ''),
                    $u->purpose,
                    $u->used_by_name ?: '-',
                    $u->remarks ?: '-',
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = $this->getService();

        // 1. Data Tab 1 (Incoming Store Requests)
        $incomingRequests = $service->getIncomingStoreRequests(
            $this->searchIncoming,
            $this->statusFilterIncoming,
            $this->startDateIncoming,
            $this->endDateIncoming
        )->paginate(15);

        // 2. Data Tab 2 (Available Batches for Usage)
        $availableBatches = $service->getAvailableBatchesForUsage();
        $selectedBatchInfo = collect($availableBatches)->firstWhere('outgoing_id', $this->selectedBatchId);

        // 3. Data Tab 3 (Floor Stock Summary & Usage History)
        $floorStockSummary = $service->getFloorStockSummary($this->searchStock);

        $usageHistory = ProductionMaterialUsage::query()
            ->with(['outgoing.material'])
            ->when($this->historySearch, function ($q) {
                $s = $this->historySearch;
                $q->where(function ($sub) use ($s) {
                    $sub->where('item_code', 'LIKE', "%{$s}%")
                        ->orWhere('pallet_id', 'LIKE', "%{$s}%")
                        ->orWhere('spk_code', 'LIKE', "%{$s}%")
                        ->orWhere('item_name', 'LIKE', "%{$s}%");
                });
            })
            ->when($this->historyMachine, fn ($q) => $q->where('machine_no', $this->historyMachine))
            ->when($this->historyPurpose !== 'ALL', fn ($q) => $q->where('purpose', $this->historyPurpose))
            ->when($this->historyStartDate && $this->historyEndDate, function ($q) {
                $q->whereBetween('used_at', [
                    Carbon::parse($this->historyStartDate)->startOfDay(),
                    Carbon::parse($this->historyEndDate)->endOfDay(),
                ]);
            })
            ->orderByDesc('used_at')
            ->paginate(15, ['*'], 'history_page');

        // Mesin aktif untuk dropdown
        $machines = MasterMachine::active()->orderBy('sort_order')->orderBy('name')->pluck('name');

        // KPI Metrics
        $totalReceivedKg = collect($floorStockSummary)->sum('total_received');
        $totalUsedKg = collect($floorStockSummary)->sum('total_used');
        $currentFloorStockKg = collect($floorStockSummary)->sum('current_stock');
        $pendingStoreCount = $service->getIncomingStoreRequests('', 'PENDING')->count();

        return view('livewire.production.production-material-station', [
            'incomingRequests' => $incomingRequests,
            'availableBatches' => $availableBatches,
            'selectedBatchInfo' => $selectedBatchInfo,
            'floorStockSummary' => $floorStockSummary,
            'usageHistory' => $usageHistory,
            'machines' => $machines,
            'totalReceivedKg' => $totalReceivedKg,
            'totalUsedKg' => $totalUsedKg,
            'currentFloorStockKg' => $currentFloorStockKg,
            'pendingStoreCount' => $pendingStoreCount,
        ]);
    }
}

