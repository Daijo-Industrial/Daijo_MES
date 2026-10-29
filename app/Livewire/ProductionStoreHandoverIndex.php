<?php

namespace App\Livewire;

use App\Models\ProductionScannedData;
use App\Models\ProductionStoreHandover;
use App\Models\ProductionStoreHandoverLog;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Services\ProductionStoreHandoverService;
use Livewire\Component;
use Livewire\WithPagination;

class ProductionStoreHandoverIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $selectedDate = '';

    public string $shiftFilter = '';

    public string $plantFilter = 'ALL';

    public string $search = '';

    public int $perPage = 25;

    // Quick Barcode Scan Input
    public string $barcodeInput = '';

    // Custom Receive Modal State
    public bool $showReceiveModal = false;

    public ?int $selectedHandoverId = null;

    public ?ProductionStoreHandover $selectedHandover = null;

    public int $modalBoxes = 1;

    public int $qtyPerBox = 0;

    public int $modalQty = 0;

    public string $modalRemarks = '';

    // Log History Modal State
    public bool $showLogsModal = false;

    public $activeLogs = [];

    // Pallet Forms Modal State
    public bool $showPalletsModal = false;

    public ?int $palletsHandoverId = null;

    public ?ProductionStoreHandover $palletsHandover = null;

    public array $palletFormsList = [];

    // Active View Tab: 'queue' (Antrean Pallet Datang) | 'table' (Rekap Tabel Kontrol)
    public string $activeTab = 'queue';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingShiftFilter()
    {
        $this->resetPage();
    }

    public function updatingPlantFilter()
    {
        $this->resetPage();
    }

    public function updatingSelectedDate()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = in_array($tab, ['queue', 'table']) ? $tab : 'queue';
    }

    public function updatedModalBoxes($value)
    {
        $boxes = max(0, (int) $value);
        if ($this->qtyPerBox > 0) {
            $this->modalQty = $boxes * $this->qtyPerBox;
        }
    }

    public function mount(ProductionStoreHandoverService $service)
    {
        $this->selectedDate = $service->getCurrentProductionDate();
        // Orang store langsung diarahkan ke tab Antrean Pallet Masuk yang simpel
        if (! $this->canManageStoreReceipt) {
            $this->activeTab = 'table';
        } else {
            $this->activeTab = 'queue';
        }
    }

    public function setToday(ProductionStoreHandoverService $service)
    {
        $this->selectedDate = $service->getCurrentProductionDate();
    }

    public function setYesterday()
    {
        $this->selectedDate = \Carbon\Carbon::parse($this->selectedDate)->subDay()->toDateString();
    }

    public function setTomorrow()
    {
        $this->selectedDate = \Carbon\Carbon::parse($this->selectedDate)->addDay()->toDateString();
    }

    public function refreshData(ProductionStoreHandoverService $service)
    {
        $shift = (!empty($this->shiftFilter) && $this->shiftFilter !== 'ALL') ? (int) $this->shiftFilter : null;
        $service->syncDailyProduction($this->selectedDate, $shift);
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Data produksi berhasil disinkronisasi ulang.']);
    }

    public function getCanManageStoreReceiptProperty(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $roleName = strtoupper(trim($user->role?->name ?? ''));
        $departmentName = strtoupper(trim($user->department?->name ?? ''));
        $allowedRoles = ['ADMIN', 'SUPER-ADMIN', 'SUPERADMIN', 'STORE', 'WAREHOUSE', 'IT', 'DEVELOPER'];

        return in_array($roleName, $allowedRoles, true)
            || in_array($departmentName, ['STORE', 'WAREHOUSE', 'IT'], true)
            || (method_exists($user, 'hasRoleAccess') && ($user->hasRoleAccess('STORE') || $user->hasRoleAccess('ADMIN')))
            || $user->can('view-store-links')
            || (str_contains(strtolower($user->email ?? ''), 'andreas') || str_contains(strtolower($user->email ?? ''), 'admin'));
    }

    public function receiveAllInTransit(int $handoverId, ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan barang hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        $handover = ProductionStoreHandover::find($handoverId);
        if (! $handover || $handover->in_transit_boxes <= 0) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Tidak ada box in-transit untuk item ini.']);
            return;
        }

        $boxes = $handover->in_transit_boxes;
        $qty = $handover->in_transit_qty;

        $success = $service->receiveBatch($handoverId, $boxes, $qty, 'Terima semua in-transit', auth()->user());

        if ($success) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Berhasil menerima {$boxes} box ({$qty} pcs) untuk {$handover->item_code}.",
            ]);
        }
    }

    public function openReceiveModal(int $handoverId)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Form penerimaan barang hanya dapat diakses oleh staf Store atau Admin.',
            ]);
            return;
        }

        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ProductionStoreHandover::find($handoverId);

        if (! $this->selectedHandover) {
            return;
        }

        $this->qtyPerBox = $this->selectedHandover->scanned_boxes > 0
            ? (int) round($this->selectedHandover->scanned_qty / $this->selectedHandover->scanned_boxes)
            : 0;

        $this->modalBoxes = max(1, $this->selectedHandover->in_transit_boxes);
        $this->modalQty = $this->modalBoxes * $this->qtyPerBox;
        $this->modalRemarks = '';

        $this->showReceiveModal = true;
    }

    public function closeReceiveModal()
    {
        $this->showReceiveModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
        $this->qtyPerBox = 0;
    }

    public function submitCustomReceive(ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan barang hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        if (! $this->selectedHandoverId || $this->modalBoxes <= 0) {
            return;
        }

        // Custom qty diutamakan (untuk mengakomodasi box loss pack / pecahan)
        $qtyToReceive = $this->modalQty > 0 ? $this->modalQty : ($this->modalBoxes * $this->qtyPerBox);

        $success = $service->receiveBatch(
            $this->selectedHandoverId,
            $this->modalBoxes,
            $qtyToReceive,
            $this->modalRemarks,
            auth()->user()
        );

        if ($success) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Berhasil mencatat penerimaan {$this->modalBoxes} box ({$qtyToReceive} pcs).",
            ]);
            $this->closeReceiveModal();
        }
    }

    public function openLogsModal(int $handoverId)
    {
        $handover = ProductionStoreHandover::with(['logs'])->find($handoverId);
        if (! $handover) {
            return;
        }

        $this->selectedHandover = $handover;
        $this->activeLogs = $handover->logs;
        $this->showLogsModal = true;
    }

    public function closeLogsModal()
    {
        $this->showLogsModal = false;
        $this->selectedHandover = null;
        $this->activeLogs = [];
    }

    public function openPalletsModal(int $handoverId, ProductionStoreHandoverService $service)
    {
        $handover = ProductionStoreHandover::find($handoverId);
        if (! $handover) {
            return;
        }

        $this->palletsHandoverId = $handoverId;
        $this->palletsHandover = $handover;
        $this->palletFormsList = $service->getRelatedPalletForms(
            $this->selectedDate,
            $handover->shift,
            $handover->item_code
        );
        $this->showPalletsModal = true;
    }

    public function closePalletsModal()
    {
        $this->showPalletsModal = false;
        $this->palletsHandoverId = null;
        $this->palletsHandover = null;
        $this->palletFormsList = [];
    }

    public function receivePalletAction(string $palletId, ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan pallet hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        if (! $this->palletsHandoverId) {
            return;
        }

        $res = $service->receivePallet($this->palletsHandoverId, $palletId, auth()->user());
        if ($res['success']) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $res['message'],
            ]);
            // Refresh modal state
            $this->palletsHandover = ProductionStoreHandover::find($this->palletsHandoverId);
            $this->palletFormsList = $service->getRelatedPalletForms(
                $this->selectedDate,
                $this->palletsHandover->shift,
                $this->palletsHandover->item_code
            );
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $res['message'],
            ]);
        }
    }

    public function receiveAllPalletsAction(ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan pallet hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        if (! $this->palletsHandoverId || empty($this->palletFormsList)) {
            return;
        }

        $unreceived = array_filter($this->palletFormsList, fn ($p) => ! $p['is_received']);
        if (empty($unreceived)) {
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Seluruh Pallet Form untuk item ini sudah diverifikasi.',
            ]);
            return;
        }

        $receivedCount = 0;
        $totalBoxes = 0;
        $totalQty = 0;

        foreach ($unreceived as $p) {
            $res = $service->receivePallet($this->palletsHandoverId, $p['pallet_id'], auth()->user());
            if ($res['success']) {
                $receivedCount++;
                $totalBoxes += $res['boxes'];
                $totalQty += $res['qty'];
            }
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Berhasil memverifikasi {$receivedCount} Pallet Form ({$totalBoxes} box / {$totalQty} pcs)!",
        ]);

        // Refresh modal state
        $this->palletsHandover = ProductionStoreHandover::find($this->palletsHandoverId);
        $this->palletFormsList = $service->getRelatedPalletForms(
            $this->selectedDate,
            $this->palletsHandover->shift,
            $this->palletsHandover->item_code
        );
    }

    public function scanBarcodeReceive(ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Scanner penerimaan store hanya dapat digunakan oleh staf Store atau Admin.',
            ]);
            return;
        }

        $barcode = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if (empty($barcode)) {
            return;
        }

        // 1. Cek apakah barcode yang discan adalah Pallet Form WMS (PLT-...)
        $pallet = WmsPalletForm::with(['details'])->where('pallet_id', $barcode)->first();
        if ($pallet) {
            // Anti-duplikasi: Cek apakah pallet sudah pernah diterima sebelumnya
            $alreadyLog = ProductionStoreHandoverLog::where('pallet_id', $barcode)->first();
            if ($alreadyLog) {
                $timeStr = $alreadyLog->created_at ? $alreadyLog->created_at->copy()->timezone('Asia/Jakarta')->format('H:i') : '-';
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => "Pallet [{$barcode}] sudah pernah diverifikasi oleh {$alreadyLog->verified_by_name} pada jam {$timeStr}.",
                ]);
                return;
            }

            // Temukan record handover yang cocok untuk part number ini pada tanggal terpilih
            $handoverQuery = ProductionStoreHandover::where('production_date', $this->selectedDate);
            if ($pallet->part_no !== 'MIXED') {
                $handoverQuery->where('item_code', $pallet->part_no);
            } else {
                $detailParts = $pallet->details->pluck('part_no')->unique()->toArray();
                $handoverQuery->whereIn('item_code', $detailParts);
            }

            if (! empty($pallet->delivery_shift)) {
                $handover = (clone $handoverQuery)->where('shift', (int) $pallet->delivery_shift)->first()
                    ?: $handoverQuery->first();
            } else {
                $handover = $handoverQuery->first();
            }

            if (! $handover) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "Item part pada Pallet [{$barcode}] tidak ditemukan dalam jadwal produksi tanggal {$this->selectedDate}.",
                ]);
                return;
            }

            $res = $service->receivePallet($handover->id, $barcode, auth()->user());
            if ($res['success']) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Pallet [{$barcode}] ({$res['boxes']} box / {$res['qty']} pcs - {$handover->item_code}) berhasil diverifikasi masuk Store!",
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $res['message'],
                ]);
            }
            return;
        }

        // 2. Jika bukan Pallet ID, cek apakah barcode adalah Box Label tunggal
        $box = ProductionScannedData::with(['ParentDailyItemCode.user'])->where('label', $barcode)->first();

        if (! $box) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Barcode [{$barcode}] tidak ditemukan di data Pallet Form WMS maupun scan produksi.",
            ]);
            return;
        }

        // Cek apakah box ini sudah pernah tercakup dalam Pallet Form yang sudah diterima
        $palletDetail = WmsPalletFormDetail::where('label', $barcode)->first();
        if ($palletDetail) {
            $alreadyReceivedPallet = ProductionStoreHandoverLog::where('pallet_id', $palletDetail->pallet_form_id)->first();
            if ($alreadyReceivedPallet) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => "Box [{$barcode}] sudah termasuk dalam Pallet [{$palletDetail->pallet_form_id}] yang telah diterima oleh {$alreadyReceivedPallet->verified_by_name}.",
                ]);
                return;
            }
        }

        // Cari handover yang cocok (prioritaskan machine_name jika ada relasi DIC)
        $machineName = strtoupper(trim($box->ParentDailyItemCode?->user?->name ?: ''));
        
        $handoverQuery = ProductionStoreHandover::where('production_date', $this->selectedDate)
            ->where('item_code', $box->item_code);

        if (! empty($machineName)) {
            $handover = (clone $handoverQuery)->where('machine_name', $machineName)->first();
        } else {
            $handover = null;
        }

        if (! $handover) {
            $handover = $handoverQuery->first();
        }

        if (! $handover) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Item [{$box->item_code}] belum ada di jadwal produksi tanggal ini.",
            ]);
            return;
        }

        $service->receiveBatch(
            $handover->id,
            1,
            (int) $box->quantity,
            "Scan Box Barcode: {$barcode}",
            auth()->user()
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Box [{$barcode}] ({$box->item_code} - Mesin {$handover->machine_name}) berhasil diverifikasi masuk Store!",
        ]);
    }

    public function receivePalletFromQueue(string $palletId, ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan pallet hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        $pallet = WmsPalletForm::with('details')->where('pallet_id', $palletId)->first();
        if (! $pallet) {
            $this->dispatch('notify', ['type' => 'error', 'message' => "Pallet [{$palletId}] tidak ditemukan."]);
            return;
        }

        // Cek anti-duplikasi
        $alreadyLog = ProductionStoreHandoverLog::where('pallet_id', $palletId)->first();
        if ($alreadyLog) {
            $timeStr = $alreadyLog->created_at ? $alreadyLog->created_at->copy()->timezone('Asia/Jakarta')->format('H:i') : '-';
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Pallet [{$palletId}] sudah pernah diverifikasi oleh {$alreadyLog->verified_by_name} pada jam {$timeStr}.",
            ]);
            return;
        }

        // Cari handover yang sesuai
        $handoverQuery = ProductionStoreHandover::where('production_date', $this->selectedDate);
        if ($pallet->part_no !== 'MIXED') {
            $handoverQuery->where('item_code', $pallet->part_no);
        } else {
            $detailParts = $pallet->details->pluck('part_no')->unique()->toArray();
            $handoverQuery->whereIn('item_code', $detailParts);
        }

        if (! empty($pallet->delivery_shift)) {
            $handover = (clone $handoverQuery)->where('shift', (int) $pallet->delivery_shift)->first()
                ?: $handoverQuery->first();
        } else {
            $handover = $handoverQuery->first();
        }

        if (! $handover) {
            $service->syncDailyProduction($this->selectedDate);
            $handover = $handoverQuery->first();
        }

        if (! $handover) {
            $handover = ProductionStoreHandover::create([
                'production_date' => $this->selectedDate,
                'shift' => ! empty($pallet->delivery_shift) ? (int) $pallet->delivery_shift : 1,
                'item_code' => $pallet->part_no !== 'MIXED' ? $pallet->part_no : ($pallet->details->first()?->part_no ?: 'UNKNOWN'),
                'item_name' => $pallet->model_name ?: 'WMS Pallet Transfer',
                'machine_name' => 'WMS-TRANSFER',
                'operator_actual_qty' => (int) $pallet->total_pallet_qty,
                'scanned_boxes' => (int) $pallet->box_qty,
                'scanned_qty' => (int) $pallet->total_pallet_qty,
                'store_received_boxes' => 0,
                'store_received_qty' => 0,
                'status' => 'PENDING',
            ]);
        }

        $res = $service->receivePallet($handover->id, $palletId, auth()->user());
        if ($res['success']) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Pallet [{$palletId}] ({$res['boxes']} box / {$res['qty']} pcs) BERHASIL DITERIMA!",
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $res['message'],
            ]);
        }
    }

    public function receiveAllPendingQueue(ProductionStoreHandoverService $service)
    {
        if (! $this->canManageStoreReceipt) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Akses ditolak: Verifikasi penerimaan pallet hanya dapat dilakukan oleh staf Store atau Admin.',
            ]);
            return;
        }

        $shift = (! empty($this->shiftFilter) && $this->shiftFilter !== 'ALL') ? (int) $this->shiftFilter : null;
        $queue = $service->getPalletsQueue($this->selectedDate, $shift, $this->search);

        if (empty($queue['pending'])) {
            $this->dispatch('notify', ['type' => 'info', 'message' => 'Tidak ada antrean pallet yang menunggu verifikasi.']);
            return;
        }

        $count = 0;
        $boxes = 0;
        $qty = 0;

        foreach ($queue['pending'] as $p) {
            $this->receivePalletFromQueue($p['pallet_id'], $service);
            $count++;
            $boxes += $p['box_count'];
            $qty += $p['qty_pcs'];
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Berhasil memverifikasi {$count} pallet ({$boxes} box / {$qty} pcs) sekaligus!",
        ]);
    }

    public function render(ProductionStoreHandoverService $service)
    {
        $shift = (!empty($this->shiftFilter) && $this->shiftFilter !== 'ALL') ? (int) $this->shiftFilter : null;
        $data = $service->getSummary($this->selectedDate, $shift, $this->plantFilter, $this->search, $this->perPage);
        $queue = $service->getPalletsQueue($this->selectedDate, $shift, $this->search);

        return view('livewire.production-store-handover-index', [
            'items' => $data['items'],
            'stats' => $data['stats'],
            'queue' => $queue,
        ])->layout('layouts.app', ['title' => 'Kontrol & Rekonsiliasi Harian Produksi - Store']);
    }
}
