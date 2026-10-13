<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Models\WmsPosition;
use App\Models\WmsWarehouse;
use App\Services\WmsService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PalletFormIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filterSlot = 'ALL'; // ALL, UNASSIGNED, ASSIGNED

    // Quick Barcode Scanner & Verification State (Store Putaway)
    public string $scanPalletId = '';
    public ?WmsPalletForm $scannedPallet = null;
    public ?int $selectedPositionId = null;
    public ?string $scanRackCode = '';
    public ?int $recommendedPositionId = null;
    public string $filterWarehouseId = '';
    public bool $showBoxDetails = false;

    // Assign Slot Modal State (Store - Backward Compatible)
    public bool $showAssignModal = false;
    public ?string $assignPalletId = null;
    public ?int $assignPositionId = null;

    protected $queryString = ['search', 'filterSlot'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterSlot()
    {
        $this->resetPage();
    }

    /**
     * Auto-trigger scan when barcode is entered/scanned without needing Enter key
     */
    public function updatedScanPalletId($value)
    {
        $this->scanPalletId = trim($value ?? '');
        if (empty($this->scanPalletId)) {
            $this->scannedPallet = null;
            return;
        }

        // Automatically scan if barcode has reasonable length (>= 4 chars)
        if (strlen($this->scanPalletId) >= 4) {
            $this->scanPallet();
        }
    }

    /**
     * Auto-trigger rack position lookup when rack barcode is scanned
     */
    public function updatedScanRackCode($value)
    {
        $this->scanRackCode = trim($value ?? '');
        if (!empty($this->scanRackCode) && strlen($this->scanRackCode) >= 3) {
            $this->scanRackPosition();
        }
    }

    /**
     * Scan / Look up Pallet barcode for verification and rack assignment
     */
    public function scanPallet(?WmsService $wmsService = null)
    {
        $this->scanPalletId = trim($this->scanPalletId);
        if (empty($this->scanPalletId)) {
            $this->scannedPallet = null;
            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);

        $pallet = WmsPalletForm::with([
            'position.rack.warehouse',
            'details.item.customer',
        ])->where('pallet_id', $this->scanPalletId)->first();

        if (!$pallet) {
            session()->flash('error', "Pallet ID \"{$this->scanPalletId}\" tidak ditemukan di sistem.");
            $this->scannedPallet = null;
            $this->dispatch('scan-error');
            return;
        }

        if ($pallet->total_pallet_qty <= 0 || $pallet->status === 'OUT') {
            session()->flash('error', "Pallet {$pallet->pallet_id} sudah OUT / stok habis (0 pcs) dan tidak dapat di-assign ke rak.");
            $this->scannedPallet = null;
            $this->dispatch('scan-error');
            return;
        }

        $this->scannedPallet          = $pallet;
        $this->assignPalletId         = $pallet->pallet_id;
        $this->selectedPositionId     = $pallet->position_id;
        $this->assignPositionId       = $pallet->position_id;
        $this->showBoxDetails         = false;

        // Smart Recommendation calculation
        $customerCodes = $pallet->details->map(fn($d) => $d->item?->customer?->customer_code)
            ->filter()->unique()->toArray();
        $rec = $wmsService->recommendPosition($customerCodes, (string) $pallet->part_no);
        $this->recommendedPositionId = $rec?->id;

        $this->dispatch('scan-success');
    }

    /**
     * Quick Barcode Scanner for Rack / Slot Position
     */
    public function scanRackPosition()
    {
        $code = trim($this->scanRackCode ?? '');
        if (empty($code)) {
            return;
        }

        $pos = WmsPosition::where('position_code', $code)
            ->orWhere('position_code', 'like', "%{$code}%")
            ->first();

        if ($pos) {
            $this->selectedPositionId = $pos->id;
            $this->assignPositionId   = $pos->id;
            $this->scanRackCode       = '';
            $this->dispatch('scan-success');
            session()->flash('info', "Slot rak terpilih: {$pos->position_code}");
        } else {
            session()->flash('error', "Slot rak \"{$code}\" tidak ditemukan.");
            $this->dispatch('scan-error');
        }
    }

    /**
     * Apply system smart recommendation
     */
    public function applyRecommendation()
    {
        if ($this->recommendedPositionId) {
            $this->selectedPositionId = $this->recommendedPositionId;
            $this->assignPositionId   = $this->recommendedPositionId;
            $this->dispatch('scan-success');
        }
    }

    /**
     * Open assign modal / load verification view from table row
     */
    public function openAssignModal($palletId, ?WmsService $wmsService = null)
    {
        $this->scanPalletId = $palletId;
        $this->scanPallet($wmsService);

        if ($this->scannedPallet) {
            $this->showAssignModal = true;
        }
    }

    /**
     * Save Pallet Assignment to Rack (Store)
     */
    public function saveAssignSlot(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        $palletId = $this->scannedPallet?->pallet_id ?? $this->assignPalletId;

        if (!$palletId) {
            session()->flash('error', "Silakan scan atau pilih pallet terlebih dahulu.");
            return;
        }

        $targetPosId = $this->selectedPositionId ?? $this->assignPositionId;

        try {
            $pallet = WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();

            if ($pallet->total_pallet_qty <= 0 || $pallet->status === 'OUT') {
                session()->flash('error', "Pallet {$pallet->pallet_id} sudah habis / OUT dan tidak perlu di-assign.");
                $this->clearScannedPallet();
                return;
            }

            $oldPositionId = $pallet->position_id;
            $newPositionId = !empty($targetPosId) ? (int) $targetPosId : null;

            $pallet->update([
                'position_id' => $newPositionId,
                'assigned_at' => $newPositionId ? ($pallet->assigned_at ?? now()) : null,
            ]);

            // Update old & new position tracking
            if ($oldPositionId && $oldPositionId !== $newPositionId) {
                $wmsService->updatePositionStatus($oldPositionId);
            }
            if ($newPositionId) {
                $wmsService->updatePositionStatus($newPositionId);
            }

            // Log Store transaction
            $action = $newPositionId ? 'ASSIGN_SLOT' : 'UNASSIGN_SLOT';
            $notes  = $newPositionId ? "Assigned by Store via Quick Scan" : "Set to TEMPORARY (Belum ada tempat) by Store";
            $wmsService->logTransaction($pallet->pallet_id, $action, $newPositionId, $notes);

            if ($newPositionId) {
                $newPos = WmsPosition::find($newPositionId);
                session()->flash('success', "✓ Pallet {$pallet->pallet_id} ({$pallet->part_no}) berhasil di-assign ke slot rak {$newPos->position_code} oleh Store.");
            } else {
                session()->flash('success', "✓ Pallet {$pallet->pallet_id} berhasil di-set ke TEMPORARY (Belum ada tempat).");
            }

            $this->clearScannedPallet();
            $this->dispatch('scan-success');
            $this->dispatch('focus-scan-input');
        } catch (\Exception $e) {
            session()->flash('error', "Gagal meng-assign slot rak: " . $e->getMessage());
        }
    }

    /**
     * Unassign / Set Pallet to Temporary
     */
    public function unassignScannedPallet(?WmsService $wmsService = null)
    {
        $this->selectedPositionId = null;
        $this->assignPositionId   = null;
        $this->saveAssignSlot($wmsService);
    }

    /**
     * Clear active scanned pallet and reset inputs
     */
    public function clearScannedPallet()
    {
        $this->scannedPallet          = null;
        $this->scanPalletId           = '';
        $this->selectedPositionId     = null;
        $this->scanRackCode           = '';
        $this->recommendedPositionId = null;
        $this->assignPalletId         = null;
        $this->assignPositionId       = null;
        $this->showAssignModal        = false;
        $this->showBoxDetails         = false;
        $this->dispatch('focus-scan-input');
    }

    public function deletePallet($palletId, ?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        try {
            DB::beginTransaction();

            $pallet = WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();
            $positionId = $pallet->position_id;

            // Delete all details
            $pallet->details()->delete();

            // Delete the pallet header
            $pallet->delete();

            // Log transaction
            $wmsService->logTransaction($palletId, 'DELETE_PALLET', $positionId, "Deleted from Pallet Form Index");

            // Update rack status if it was in a rack
            if ($positionId) {
                $wmsService->updatePositionStatus($positionId);
            }

            DB::commit();

            session()->flash('success', "Pallet $palletId berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', "Gagal menghapus pallet: " . $e->getMessage());
        }
    }

    public function render(WmsService $wmsService)
    {
        // Self-healing 1: Backfill assigned_at for any pallet with position_id but assigned_at IS NULL
        $unassignedAtPallets = WmsPalletForm::whereNotNull('position_id')
            ->whereNull('assigned_at')
            ->get();

        foreach ($unassignedAtPallets as $p) {
            $firstLog = \App\Models\WmsPalletLog::where('pallet_id', $p->pallet_id)
                ->whereIn('transaction_type', ['ASSIGN_SLOT', 'IN'])
                ->whereNotNull('position_id')
                ->orderBy('created_at', 'asc')
                ->first();

            $assignedTime = $firstLog ? $firstLog->created_at : $p->created_at;
            $p->update(['assigned_at' => $assignedTime]);
        }

        // Self-healing 2: Ensure any pallet with total_pallet_qty <= 0 or status == OUT has position_id cleared
        $stalePallets = WmsPalletForm::whereNotNull('position_id')
            ->where(function($q) {
                $q->where('total_pallet_qty', '<=', 0)
                  ->orWhere('status', 'OUT');
            })
            ->get();

        if ($stalePallets->isNotEmpty()) {
            $affectedPosIds = $stalePallets->pluck('position_id')->filter()->unique();
            WmsPalletForm::whereIn('pallet_id', $stalePallets->pluck('pallet_id'))
                ->update(['position_id' => null, 'assigned_at' => null]);

            foreach ($affectedPosIds as $posId) {
                $wmsService->updatePositionStatus($posId);
            }
        }

        $palletForms = WmsPalletForm::with(['position.rack.warehouse', 'details.item.customer'])
            ->where(function($query) {
                $query->where('pallet_id', 'like', '%' . $this->search . '%')
                      ->orWhere('part_no', 'like', '%' . $this->search . '%')
                      ->orWhere('model_name', 'like', '%' . $this->search . '%')
                      ->orWhere('delivery_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterSlot !== 'ALL', function($query) {
                if ($this->filterSlot === 'UNASSIGNED') {
                    $query->whereNull('position_id')
                          ->where('total_pallet_qty', '>', 0)
                          ->where('status', '!=', 'OUT');
                } elseif ($this->filterSlot === 'ASSIGNED') {
                    $query->whereNotNull('position_id')
                          ->where('total_pallet_qty', '>', 0)
                          ->where('status', '!=', 'OUT');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $warehouses = WmsWarehouse::orderBy('whse_code', 'asc')->get();

        $availablePositionsQuery = WmsPosition::with(['rack.warehouse', 'customer'])
            ->when(!empty($this->filterWarehouseId), function($q) {
                $q->whereHas('rack', function($rq) {
                    $rq->where('whse_id', $this->filterWarehouseId);
                });
            })
            ->orderBy('position_code', 'asc');

        $availablePositions = $availablePositionsQuery->get();

        $recommendedPosition = $this->recommendedPositionId 
            ? WmsPosition::with(['rack.warehouse', 'customer'])->find($this->recommendedPositionId)
            : null;

        return view('livewire.wms.pallet-form-index', [
            'palletForms'          => $palletForms,
            'availablePositions'   => $availablePositions,
            'warehouses'           => $warehouses,
            'recommendedPosition'  => $recommendedPosition,
        ]);
    }
}

