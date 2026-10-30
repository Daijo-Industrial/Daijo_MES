<?php

namespace App\Livewire\Store;

use App\Exports\ProductionScanCrosscheckExport;
use App\Models\ProductionScannedData;
use App\Models\StoreCrosscheckDailyReport;
use App\Services\StoreCrosscheckService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ProductionScanCrosscheck extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filters
    public string $selectedDate = '';
    public string $plantTab = 'KBN'; // 'KBN', 'KARAWANG', 'ALL'
    public string $shiftFilter = 'ALL'; // 'ALL', '1', '2', '3'
    public string $machineFilter = 'ALL'; // 'ALL', or machine name (e.g. '1', 'K01')
    public string $statusFilter = 'ALL'; // 'ALL', 'PENDING', 'FOUND', 'NOT_FOUND'
    public string $search = '';
    public int $perPage = 50;

    // Verifikator / PIC Name (Khususnya jika memakai shared account seperti store01)
    public string $verifierName = '';

    // Barcode Quick Scanner (Disimpan untuk backward-compatibility/fitur masa depan)
    public string $barcodeInput = '';
    public ?array $scanFeedback = null;
    public bool $audioEnabled = true;

    // Batch Selection
    public array $selectedBoxIds = [];
    public bool $selectAll = false;

    // Snapshot Daily Report Modal
    public bool $showSnapshotModal = false;
    public string $dailyNotes = '';
    public string $reportStatus = 'OPEN'; // 'OPEN', 'FINALIZED'
    public ?string $lastSavedSnapshotAt = null;

    // Inline Notes state
    public array $notes = [];

    // Flash message
    public ?string $flashSuccess = null;
    public ?string $flashError = null;

    protected $queryString = [
        'selectedDate' => ['except' => ''],
        'plantTab' => ['except' => 'KBN'],
        'shiftFilter' => ['except' => 'ALL'],
        'machineFilter' => ['except' => 'ALL'],
        'statusFilter' => ['except' => 'ALL'],
        'search' => ['except' => ''],
    ];

    public function mount(StoreCrosscheckService $service)
    {
        StoreCrosscheckService::ensureSchema();

        if (empty($this->selectedDate)) {
            $this->selectedDate = $service->getCurrentProductionDate();
        }

        // Ambil nama verifikator yang sebelumnya tersimpan di session browser
        $savedVerifier = session('store_crosscheck_verifier_name');
        if (empty($savedVerifier)) {
            $authName = auth()->user()?->name ?? '';
            $isGeneric = in_array(strtolower(trim($authName)), ['store01', 'store02', 'store_karawang', 'store', 'operator']);
            $savedVerifier = $isGeneric ? '' : $authName;
        }
        $this->verifierName = $savedVerifier;

        $this->loadSavedSnapshot();
    }

    public function updatedVerifierName()
    {
        session(['store_crosscheck_verifier_name' => trim($this->verifierName)]);
    }

    public function updatedSelectedDate()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
        $this->scanFeedback = null;
        $this->loadSavedSnapshot();
    }

    public function updatedPlantTab()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
        $this->scanFeedback = null;
        $this->machineFilter = 'ALL'; // Reset pilihan mesin saat berganti plant
        $this->loadSavedSnapshot();
    }

    public function updatedShiftFilter()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
    }

    public function updatedMachineFilter()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedBoxIds = [];
        $this->selectAll = false;
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    /**
     * Dapatkan nama verifikator yang berlaku (input user atau fallback login)
     */
    public function getEffectiveVerifierName(): string
    {
        $clean = trim($this->verifierName);
        if (! empty($clean)) {
            return $clean;
        }

        return auth()->user()?->name ?: 'Store Operator';
    }

    public function setToday(StoreCrosscheckService $service)
    {
        $this->selectedDate = $service->getCurrentProductionDate();
        $this->updatedSelectedDate();
    }

    public function setYesterday()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->updatedSelectedDate();
    }

    public function setTomorrow()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->updatedSelectedDate();
    }

    /**
     * Handler untuk barcode scanner fisik dari Store
     */
    public function scanBarcode(StoreCrosscheckService $service)
    {
        $this->flashSuccess = null;
        $this->flashError = null;

        $trimmed = trim($this->barcodeInput);
        if (empty($trimmed)) {
            return;
        }

        $result = $service->verifyBarcode(
            $trimmed,
            $this->selectedDate,
            $this->plantTab,
            auth()->id(),
            auth()->user()?->name
        );

        $this->scanFeedback = [
            'success' => $result['success'],
            'status' => $result['status'],
            'message' => $result['message'],
            'timestamp' => now()->format('H:i:s'),
            'item_id' => $result['item']->id ?? null,
        ];

        // Dispatch browser event untuk Web Audio API Synthesizer (Zero-dependency sound)
        if ($this->audioEnabled) {
            $soundType = $result['success']
                ? ($result['status'] === 'already_found' ? 'warning' : 'success')
                : 'error';

            $this->dispatch('play-scan-sound', sound: $soundType);
        }

        $this->barcodeInput = '';
    }

    /**
     * Tandai 1 box sebagai FOUND (Ada di Store)
     */
    public function markFound(int $id, StoreCrosscheckService $service)
    {
        $verifier = $this->getEffectiveVerifierName();
        $service->updateStatus([$id], 'FOUND', auth()->id(), $verifier);
        $this->flashSuccess = "Box #{$id} berhasil ditandai: ADA DI STORE (oleh: {$verifier}).";

        if ($this->audioEnabled) {
            $this->dispatch('play-scan-sound', sound: 'success');
        }
    }

    /**
     * Tandai 1 box sebagai NOT_FOUND (Tidak Ditemukan)
     */
    public function markNotFound(int $id, StoreCrosscheckService $service)
    {
        $verifier = $this->getEffectiveVerifierName();
        $service->updateStatus([$id], 'NOT_FOUND', auth()->id(), $verifier);
        $this->flashError = "Box #{$id} ditandai: TIDAK DITEMUKAN / HILANG (oleh: {$verifier}).";

        if ($this->audioEnabled) {
            $this->dispatch('play-scan-sound', sound: 'error');
        }
    }

    /**
     * Reset 1 box kembali ke PENDING
     */
    public function resetStatus(int $id, StoreCrosscheckService $service)
    {
        $service->updateStatus([$id], 'PENDING');
        $this->flashSuccess = "Status Box #{$id} dikembalikan ke Belum Dicek.";
    }

    /**
     * Simpan catatan store per box
     */
    public function saveNote(int $id, string $noteText, StoreCrosscheckService $service)
    {
        $service->saveNote($id, $noteText);
        $this->flashSuccess = "Catatan untuk box #{$id} berhasil disimpan.";
    }

    /**
     * Checkbox select all pada halaman aktif
     */
    public function updatedSelectAll($value)
    {
        if ($value) {
            $service = app(StoreCrosscheckService::class);
            $query = $service->getBaseQuery(
                $this->selectedDate,
                $this->plantTab,
                $this->shiftFilter,
                $this->statusFilter,
                $this->search,
                $this->machineFilter
            );

            // Ambil ID pada page ini
            $this->selectedBoxIds = $query->paginate($this->perPage)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedBoxIds = [];
        }
    }

    /**
     * Batch: Tandai yang dicentang sebagai ADA DI STORE
     */
    public function markSelectedFound(StoreCrosscheckService $service)
    {
        if (empty($this->selectedBoxIds)) {
            $this->flashError = 'Pilih setidaknya 1 box terlebih dahulu!';
            return;
        }

        $verifier = $this->getEffectiveVerifierName();
        $count = $service->updateStatus($this->selectedBoxIds, 'FOUND', auth()->id(), $verifier);
        $this->flashSuccess = "{$count} box berhasil ditandai: ADA DI STORE (oleh: {$verifier}).";
        $this->selectedBoxIds = [];
        $this->selectAll = false;

        if ($this->audioEnabled) {
            $this->dispatch('play-scan-sound', sound: 'success');
        }
    }

    /**
     * Batch: Tandai yang dicentang sebagai TIDAK DITEMUKAN
     */
    public function markSelectedNotFound(StoreCrosscheckService $service)
    {
        if (empty($this->selectedBoxIds)) {
            $this->flashError = 'Pilih setidaknya 1 box terlebih dahulu!';
            return;
        }

        $verifier = $this->getEffectiveVerifierName();
        $count = $service->updateStatus($this->selectedBoxIds, 'NOT_FOUND', auth()->id(), $verifier);
        $this->flashError = "{$count} box ditandai: TIDAK DITEMUKAN (oleh: {$verifier}).";
        $this->selectedBoxIds = [];
        $this->selectAll = false;

        if ($this->audioEnabled) {
            $this->dispatch('play-scan-sound', sound: 'error');
        }
    }

    /**
     * Tindakan Cepat: Tandai SEMUA yang masih PENDING pada filter tanggal, plant & mesin ini sebagai ADA
     */
    public function markAllPendingAsFound(StoreCrosscheckService $service)
    {
        $query = $service->getBaseQuery($this->selectedDate, $this->plantTab, $this->shiftFilter, 'PENDING', '', $this->machineFilter);
        $pendingIds = $query->pluck('id')->toArray();

        if (empty($pendingIds)) {
            $this->flashSuccess = 'Semua box sudah diverifikasi! Tidak ada box yang tertunda.';
            return;
        }

        $verifier = $this->getEffectiveVerifierName();
        $count = $service->updateStatus($pendingIds, 'FOUND', auth()->id(), $verifier);
        $this->flashSuccess = "Berhasil: {$count} box sisa otomatis ditandai ADA DI STORE (oleh: {$verifier})!";

        if ($this->audioEnabled) {
            $this->dispatch('play-scan-sound', sound: 'success');
        }
    }

    /**
     * Buka modal snapshot laporan harian
     */
    public function openSnapshotModal()
    {
        $this->loadSavedSnapshot();
        $this->showSnapshotModal = true;
    }

    /**
     * Muat data snapshot laporan harian yang tersimpan jika ada
     */
    protected function loadSavedSnapshot()
    {
        $effectivePlant = in_array($this->plantTab, ['KBN', 'KARAWANG']) ? $this->plantTab : 'KBN';

        $saved = StoreCrosscheckDailyReport::where('report_date', $this->selectedDate)
            ->where('plant', $effectivePlant)
            ->first();

        if ($saved) {
            $this->dailyNotes = $saved->notes ?? '';
            $this->reportStatus = $saved->status ?? 'OPEN';
            $this->lastSavedSnapshotAt = $saved->updated_at ? $saved->updated_at->format('d M Y H:i') : null;
        } else {
            $this->dailyNotes = '';
            $this->reportStatus = 'OPEN';
            $this->lastSavedSnapshotAt = null;
        }
    }

    /**
     * Simpan / Perbarui Snapshot Laporan Harian
     */
    public function saveDailySnapshot(StoreCrosscheckService $service)
    {
        $effectivePlant = in_array($this->plantTab, ['KBN', 'KARAWANG']) ? $this->plantTab : 'KBN';

        $report = $service->saveDailyReportSnapshot(
            $this->selectedDate,
            $effectivePlant,
            $this->shiftFilter,
            $this->dailyNotes,
            auth()->id(),
            $this->getEffectiveVerifierName(),
            $this->reportStatus
        );

        $this->lastSavedSnapshotAt = $report->updated_at ? $report->updated_at->format('d M Y H:i') : null;
        $this->showSnapshotModal = false;
        $this->flashSuccess = "Laporan harian Plant {$effectivePlant} tanggal {$this->selectedDate} berhasil disimpan!";
    }

    /**
     * Export Excel (.xlsx)
     */
    public function exportExcel()
    {
        $plantTitle = $this->plantTab === 'ALL' ? 'ALL_PLANT' : $this->plantTab;
        $machineTitle = $this->machineFilter === 'ALL' ? '' : "_{$this->machineFilter}";
        $filename = "Crosscheck_Store_{$plantTitle}{$machineTitle}_{$this->selectedDate}.xlsx";

        return Excel::download(
            new ProductionScanCrosscheckExport(
                $this->selectedDate,
                $this->plantTab,
                $this->shiftFilter,
                $this->statusFilter,
                $this->search,
                $this->machineFilter
            ),
            $filename
        );
    }

    public function render(StoreCrosscheckService $service)
    {
        $metrics = $service->getSummaryMetrics($this->selectedDate, $this->plantTab);
        $machines = $service->getAvailableMachines($this->selectedDate, $this->plantTab);

        $query = $service->getBaseQuery(
            $this->selectedDate,
            $this->plantTab,
            $this->shiftFilter,
            $this->statusFilter,
            $this->search,
            $this->machineFilter
        );

        $items = $query->paginate($this->perPage);

        // Pre-fill notes array for input binding
        foreach ($items as $item) {
            if (! isset($this->notes[$item->id])) {
                $this->notes[$item->id] = $item->store_notes ?? '';
            }
        }

        return view('livewire.store.production-scan-crosscheck', [
            'items' => $items,
            'metrics' => $metrics,
            'machines' => $machines,
            'currentProdDate' => $service->getCurrentProductionDate(),
        ])->layout('layouts.app');
    }
}
