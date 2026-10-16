<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Services\MaterialWarehouseService;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialPalletIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'ALL';

    public $whse_id = 'ALL';

    public array $warehouses = [];

    public int $perPage = 25;

    // Relocation Modal State
    public bool $showRelocateModal = false;

    public ?int $relocatingPalletId = null;

    public ?string $relocatingPalletCode = null;

    public ?int $newPositionId = null;

    public bool $isFromTemporary = false;

    // QC Hold Modal State
    public bool $showQcHoldModal = false;

    public ?int $qcPalletId = null;

    public ?string $qcPalletCode = null;

    public bool $isQcHold = false;

    public string $qcHoldReason = '';

    protected $queryString = ['search', 'statusFilter', 'whse_id'];

    public function mount(): void
    {
        $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingWhseId(): void
    {
        $this->resetPage();
    }

    public function openQcHoldModal(int $id): void
    {
        $pallet = MwhPallet::findOrFail($id);
        $this->qcPalletId = $pallet->id;
        $this->qcPalletCode = $pallet->pallet_id;
        $this->isQcHold = (bool) $pallet->is_qc_hold;
        $this->qcHoldReason = $pallet->qc_hold_reason ?? '';
        $this->showQcHoldModal = true;
    }

    public function saveQcHold(): void
    {
        $pallet = MwhPallet::findOrFail($this->qcPalletId);

        $pallet->update([
            'is_qc_hold' => $this->isQcHold,
            'qc_hold_reason' => $this->isQcHold ? trim($this->qcHoldReason) : null,
        ]);

        $statusText = $this->isQcHold ? 'di-HOLD QC' : 'di-RELEASE (OK)';
        session()->flash('success', "Status QC Pallet {$pallet->pallet_id} berhasil diperbarui menjadi {$statusText}.");

        $this->showQcHoldModal = false;
    }

    public function toggleQcHoldDirect(int $id): void
    {
        $pallet = MwhPallet::findOrFail($id);
        $newHold = ! $pallet->is_qc_hold;
        $pallet->update([
            'is_qc_hold' => $newHold,
            'qc_hold_reason' => $newHold ? ($pallet->qc_hold_reason ?: 'QC Hold by User') : null,
        ]);

        $statusText = $newHold ? 'di-HOLD QC' : 'di-RELEASE (OK)';
        session()->flash('success', "Status QC Pallet {$pallet->pallet_id} berhasil diperbarui menjadi {$statusText}.");
    }

    public function openRelocateModal(int $id): void
    {
        $pallet = MwhPallet::findOrFail($id);
        $this->relocatingPalletId = $pallet->id;
        $this->relocatingPalletCode = $pallet->pallet_id;
        $this->newPositionId = $pallet->position_id;
        $this->isFromTemporary = is_null($pallet->position_id);
        $this->showRelocateModal = true;
    }

    public function saveRelocation(MaterialWarehouseService $mwhService): void
    {
        $this->validate([
            'newPositionId' => 'required|exists:mwh_positions,id',
        ], [
            'newPositionId.required' => 'Pilih slot rak tujuan penempatan.',
            'newPositionId.exists' => 'Slot rak tujuan tidak valid.',
        ]);

        $pallet = MwhPallet::findOrFail($this->relocatingPalletId);
        $oldPositionId = $pallet->position_id;

        if ($oldPositionId !== (int) $this->newPositionId) {
            $newPos = MwhPosition::findOrFail($this->newPositionId);

            // Cek ketersediaan kapasitas di slot tujuan
            $currentStored = MwhPallet::where('position_id', $newPos->id)->where('current_qty', '>', 0)->sum('current_qty');
            $availableCap = $newPos->max_capacity - $currentStored;

            if ($availableCap < $pallet->current_qty) {
                session()->flash('error', "Kapasitas slot {$newPos->position_code} tersisa " . number_format($availableCap, 2) . ' KG, tidak muat untuk pallet seberat ' . number_format($pallet->current_qty, 2) . ' KG.');

                return;
            }

            $pallet->update([
                'position_id' => $this->newPositionId,
                'initial_position_id' => $pallet->initial_position_id ?: $this->newPositionId,
            ]);

            // Update status of old and new rack slots
            if ($oldPositionId) {
                $mwhService->updatePositionStatus($oldPositionId);
            }
            $mwhService->updatePositionStatus($this->newPositionId);

            $actionText = $oldPositionId
                ? "direlokasi ke slot {$newPos->position_code}"
                : "berhasil dimasukkan dari Temporary ke slot {$newPos->position_code}";
            session()->flash('success', "Pallet {$pallet->pallet_id} {$actionText}.");
        }

        $this->showRelocateModal = false;
    }

    public function deletePallet(int $id, MaterialWarehouseService $mwhService): void
    {
        $pallet = MwhPallet::find($id);
        if ($pallet) {
            $positionId = $pallet->position_id;
            $palletCode = $pallet->pallet_id;

            // Also delete related outgoing transaction history for this test pallet
            \App\Models\MwhOutgoing::where('pallet_id', $palletCode)->delete();
            $pallet->delete();

            if ($positionId) {
                $mwhService->updatePositionStatus($positionId);
            }

            session()->flash('success', "Pallet {$palletCode} beserta history transaksinya berhasil dihapus.");
        }
    }

    public function render()
    {
        $pallets = MwhPallet::with(['position.rack', 'initialPosition.rack', 'outgoings.position', 'material', 'incomingHeader'])
            ->when($this->search, function ($query) {
                $s = '%' . trim($this->search) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('pallet_id', 'like', $s)
                        ->orWhere('item_code', 'like', $s)
                        ->orWhere('lot_no', 'like', $s)
                        ->orWhere('qc_hold_reason', 'like', $s)
                        ->orWhereHas('position', function ($posQ) use ($s) {
                            $posQ->where('position_code', 'like', $s);
                        })
                        ->orWhereHas('initialPosition', function ($posQ) use ($s) {
                            $posQ->where('position_code', 'like', $s);
                        });
                });
            })
            ->when($this->statusFilter !== 'ALL', function ($query) {
                if ($this->statusFilter === 'PARTIAL') {
                    $query->where(function ($q) {
                        $q->where('status', 'PARTIAL')
                            ->orWhereRaw('current_qty < initial_qty AND current_qty > 0')
                            ->orWhereHas('position', function ($pq) {
                                $pq->where('status', 'PARTIAL');
                            });
                    });
                } elseif ($this->statusFilter === 'STORED') {
                    $query->where(function ($q) {
                        $q->where('status', 'STORED')
                            ->whereRaw('current_qty >= initial_qty AND current_qty > 0');
                    });
                } elseif ($this->statusFilter === 'EMPTY') {
                    $query->where(function ($q) {
                        $q->where('status', 'EMPTY')
                            ->orWhere('current_qty', '<=', 0);
                    });
                } elseif ($this->statusFilter === 'QC_HOLD') {
                    $query->where('is_qc_hold', true);
                } elseif ($this->statusFilter === 'TEMPORARY') {
                    $query->whereNull('mwh_pallets.position_id')
                        ->where('mwh_pallets.current_qty', '>', 0);
                }
            })
            ->when($this->whse_id && $this->whse_id !== 'ALL', function ($query) {
                $whseId = (int) $this->whse_id;
                $query->where(function ($q) use ($whseId) {
                    $q->where('mwh_pallets.whse_id', $whseId)
                        ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId));
                });
            })
            ->leftJoin('mwh_incoming_headers', 'mwh_pallets.incoming_header_id', '=', 'mwh_incoming_headers.id')
            ->select('mwh_pallets.*')
            ->orderByRaw('COALESCE(mwh_incoming_headers.arrival_date, DATE(mwh_pallets.created_at)) DESC')
            ->orderBy('mwh_pallets.id', 'desc')
            ->paginate($this->perPage);

        $targetWhseId = null;
        if ($this->relocatingPalletId) {
            $relocatingPallet = MwhPallet::with('position.rack')->find($this->relocatingPalletId);
            $targetWhseId = $relocatingPallet ? ($relocatingPallet->whse_id ?: $relocatingPallet->position?->rack?->whse_id) : null;
        } elseif ($this->whse_id !== 'ALL' && $this->whse_id) {
            $targetWhseId = (int) $this->whse_id;
        }

        $availablePositionsQuery = MwhPosition::with('rack')
            ->whereIn('status', ['EMPTY', 'PARTIAL']);

        if ($targetWhseId) {
            $availablePositionsQuery->whereHas('rack', fn ($q) => $q->where('whse_id', $targetWhseId));
        }

        $availablePositions = $availablePositionsQuery->orderBy('position_code', 'asc')->get();

        return view('livewire.material-warehouse.material-pallet-index', [
            'warehouses' => $this->warehouses,
            'pallets' => $pallets,
            'availablePositions' => $availablePositions,
        ]);
    }
}
