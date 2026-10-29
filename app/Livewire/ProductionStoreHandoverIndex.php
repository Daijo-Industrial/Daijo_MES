<?php

namespace App\Livewire;

use App\Models\ProductionScannedData;
use App\Models\ProductionStoreHandover;
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
        $allowedRoles = ['ADMIN', 'SUPER-ADMIN', 'SUPERADMIN', 'STORE', 'WAREHOUSE'];

        return in_array($roleName, $allowedRoles, true)
            || (method_exists($user, 'hasRoleAccess') && ($user->hasRoleAccess('STORE') || $user->hasRoleAccess('ADMIN')))
            || $user->can('view-store-links');
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

        // Cari box di production_scanned_data beserta user mesin
        $box = ProductionScannedData::with(['ParentDailyItemCode.user'])->where('label', $barcode)->first();

        if (! $box) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Barcode [{$barcode}] tidak ditemukan di data scan produksi.",
            ]);
            return;
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

    public function render(ProductionStoreHandoverService $service)
    {
        $shift = (!empty($this->shiftFilter) && $this->shiftFilter !== 'ALL') ? (int) $this->shiftFilter : null;
        $data = $service->getSummary($this->selectedDate, $shift, $this->plantFilter, $this->search, $this->perPage);

        return view('livewire.production-store-handover-index', [
            'items' => $data['items'],
            'stats' => $data['stats'],
        ])->layout('layouts.app', ['title' => 'Kontrol & Rekonsiliasi Harian Produksi - Store']);
    }
}
