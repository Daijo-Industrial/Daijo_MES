<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Models\WmsPosition;
use App\Models\WmsWarehouse;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

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

    // Mixed Pallet Handling State
    public bool $isMixedPallet = false;

    public array $mixedItemGroups = [];

    public array $splitItemActions = [];

    public string $mixedAssignMode = 'SPLIT'; // 'SPLIT' or 'FORCE'

    // SO (Sales Order) / Outbound Delivery Modal State
    public bool $showSoModal = false;

    public ?string $selectedPalletId = null;

    public array $soDeliveryData = [];

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
        if (! empty($this->scanRackCode) && strlen($this->scanRackCode) >= 3) {
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

        if (! $pallet) {
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

        $this->scannedPallet = $pallet;
        $this->assignPalletId = $pallet->pallet_id;
        $this->selectedPositionId = $pallet->position_id;
        $this->assignPositionId = $pallet->position_id;
        $this->showBoxDetails = false;

        $distinctPartNos = $pallet->details->pluck('part_no')->filter()->unique()->values();
        $this->isMixedPallet = ($pallet->part_no === 'MIXED') || ($distinctPartNos->count() > 1);
        $this->mixedAssignMode = 'SPLIT';

        // Auto pre-select warehouse from existing pallet position if any, or default to first warehouse
        if ($pallet->position?->rack?->whse_id) {
            $this->filterWarehouseId = (string) $pallet->position->rack->whse_id;
        } elseif (empty($this->filterWarehouseId)) {
            $firstWhse = WmsWarehouse::orderBy('whse_code', 'asc')->first();
            if ($firstWhse) {
                $this->filterWarehouseId = (string) $firstWhse->id;
            }
        }

        if ($this->isMixedPallet) {
            $this->loadMixedItemGroups($wmsService);
        }

        $this->recalculateRecommendation($wmsService);

        $this->dispatch('scan-success');
    }

    /**
     * Load item groups and find existing rack slots for mixed pallet resolution
     */
    public function loadMixedItemGroups(?WmsService $wmsService = null)
    {
        if (! $this->scannedPallet || ! $this->isMixedPallet) {
            $this->mixedItemGroups = [];
            $this->splitItemActions = [];

            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);
        $this->mixedItemGroups = [];
        $this->splitItemActions = [];

        $grouped = $this->scannedPallet->details->groupBy('part_no');

        foreach ($grouped as $pNo => $boxes) {
            $existingRacks = $wmsService->findExistingRacksForItem($pNo, $this->filterWarehouseId ?: null, $this->scannedPallet->pallet_id);
            if ($existingRacks->isEmpty() && ! empty($this->filterWarehouseId)) {
                $existingRacks = $wmsService->findExistingRacksForItem($pNo, null, $this->scannedPallet->pallet_id);
            }
            $firstExisting = $existingRacks->first();
            $hasExisting = $firstExisting !== null;

            $this->mixedItemGroups[$pNo] = [
                'part_no' => $pNo,
                'model_name' => $boxes->first()?->model_name ?: '-',
                'box_qty' => $boxes->count(),
                'total_qty' => (float) $boxes->sum('qty'),
                'has_existing_rack' => $hasExisting,
                'existing_racks' => $existingRacks->map(fn ($r) => [
                    'id' => $r->id,
                    'code' => $r->position_code,
                    'whse' => $r->rack?->warehouse?->whse_code ?? 'WMS',
                    'status' => $r->status,
                    'target_pallet_id' => $r->palletForms->first()?->pallet_id,
                ])->toArray(),
            ];

            if ($hasExisting) {
                $this->splitItemActions[$pNo] = [
                    'action' => 'MERGE',
                    'target_position_id' => $firstExisting->id,
                    'target_pallet_id' => $firstExisting->palletForms->first()?->pallet_id,
                ];
            } else {
                $customerCode = $boxes->first()?->item?->customer?->customer_code;
                $recEmpty = $wmsService->recommendPosition(
                    $customerCode ? [$customerCode] : [],
                    $pNo,
                    $this->filterWarehouseId ?: null
                );

                $this->splitItemActions[$pNo] = [
                    'action' => 'NEW_PALLET',
                    'target_position_id' => $recEmpty?->id,
                    'target_pallet_id' => null,
                ];
            }
        }
    }

    /**
     * Recalculate recommendation scoped to selected warehouse
     */
    public function recalculateRecommendation(?WmsService $wmsService = null)
    {
        if (! $this->scannedPallet) {
            $this->recommendedPositionId = null;

            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);
        $customerCodes = $this->scannedPallet->details->map(fn ($d) => $d->item?->customer?->customer_code)
            ->filter()->unique()->toArray();
        $rec = $wmsService->recommendPosition(
            $customerCodes,
            (string) $this->scannedPallet->part_no,
            $this->filterWarehouseId ?: null
        );
        $this->recommendedPositionId = $rec?->id;
    }

    /**
     * Auto re-filter slots when warehouse selection changes
     */
    public function updatedFilterWarehouseId()
    {
        $this->recalculateRecommendation();

        if ($this->isMixedPallet) {
            $this->loadMixedItemGroups();
        }

        // If currently chosen slot does not belong to the newly selected warehouse, reset it
        if (! empty($this->filterWarehouseId)) {
            $currId = $this->assignPositionId ?: $this->selectedPositionId;
            if ($currId) {
                $pos = WmsPosition::with('rack')->find($currId);
                if ($pos && (string) $pos->rack?->whse_id !== (string) $this->filterWarehouseId) {
                    $this->selectedPositionId = null;
                    $this->assignPositionId = null;
                }
            }
        }
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
            $this->assignPositionId = $pos->id;
            $this->scanRackCode = '';
            $this->dispatch('scan-success');
            session()->flash('info', "Slot rak terpilih: {$pos->position_code}");
        } else {
            session()->flash('error', "Slot rak \"{$code}\" tidak ditemukan.");
            $this->dispatch('scan-error');
        }
    }

    /**
     * Direct scan handler from tablet camera scanner (single roundtrip)
     */
    public function scanPalletDirect(string $palletId, ?WmsService $wmsService = null)
    {
        $this->scanPalletId = trim($palletId);
        $this->scanPallet($wmsService);
    }

    /**
     * Direct rack scan handler from tablet camera scanner (single roundtrip)
     */
    public function scanRackPositionDirect(string $rackCode)
    {
        $this->scanRackCode = trim($rackCode);
        $this->scanRackPosition();
    }

    /**
     * Apply system smart recommendation
     */
    public function applyRecommendation()
    {
        if ($this->recommendedPositionId) {
            $this->selectedPositionId = $this->recommendedPositionId;
            $this->assignPositionId = $this->recommendedPositionId;
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
            if ($this->scannedPallet->position?->rack?->whse_id) {
                $this->filterWarehouseId = (string) $this->scannedPallet->position->rack->whse_id;
            } elseif (empty($this->filterWarehouseId)) {
                $firstWhse = WmsWarehouse::orderBy('whse_code', 'asc')->first();
                if ($firstWhse) {
                    $this->filterWarehouseId = (string) $firstWhse->id;
                }
            }

            $this->recalculateRecommendation($wmsService);
            $this->assignPositionId = $this->scannedPallet->position_id;
            $this->selectedPositionId = $this->scannedPallet->position_id;
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

        if (! $palletId) {
            session()->flash('error', 'Silakan scan atau pilih pallet terlebih dahulu.');

            return;
        }

        $targetPosId = $this->showAssignModal ? $this->assignPositionId : ($this->selectedPositionId ?? $this->assignPositionId);

        try {
            $pallet = WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();

            if ($pallet->total_pallet_qty <= 0 || $pallet->status === 'OUT') {
                session()->flash('error', "Pallet {$pallet->pallet_id} sudah habis / OUT dan tidak perlu di-assign.");
                $this->clearScannedPallet();

                return;
            }

            $oldPositionId = $pallet->position_id;
            $newPositionId = ! empty($targetPosId) ? (int) $targetPosId : null;

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
            $notes = $newPositionId ? 'Assigned by Store via Quick Scan' : 'Set to TEMPORARY (Belum ada tempat) by Store';
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
            session()->flash('error', 'Gagal meng-assign slot rak: ' . $e->getMessage());
        }
    }

    /**
     * Execute split and assign for a single item from the mixed pallet
     */
    public function executeSplitSingleItem(string $partNo, ?WmsService $wmsService = null)
    {
        if (! $this->scannedPallet) {
            session()->flash('error', 'Pallet tidak ditemukan.');

            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);
        $config = $this->splitItemActions[$partNo] ?? null;

        if (empty($config) || empty($config['target_position_id'])) {
            session()->flash('error', "Silakan pilih slot rak tujuan untuk item {$partNo} terlebih dahulu.");

            return;
        }

        try {
            $palletId = $this->scannedPallet->pallet_id;
            $res = $wmsService->splitAndAssignMixedPallet(
                $palletId,
                [$partNo => $config]
            );

            session()->flash('success', $res['message']);

            if (! empty($res['is_fully_resolved'])) {
                $this->clearScannedPallet();
            } else {
                // Refresh remaining items on source pallet
                $this->scannedPallet = WmsPalletForm::with('details')->where('pallet_id', $palletId)->first();
                if ($this->scannedPallet) {
                    $this->loadMixedItemGroups($wmsService);
                } else {
                    $this->clearScannedPallet();
                }
            }

            $this->dispatch('scan-success');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses pemisahan item: ' . $e->getMessage());
        }
    }

    /**
     * Execute split and assign mixed pallet to multiple target racks (batch for all selected items)
     */
    public function executeSplitAndAssign(?WmsService $wmsService = null)
    {
        if (! $this->scannedPallet) {
            session()->flash('error', 'Pallet tidak ditemukan.');

            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);

        // Filter only items that have an assigned target position
        $assignedActions = collect($this->splitItemActions)
            ->filter(fn ($cfg) => ! empty($cfg['target_position_id']))
            ->all();

        if (empty($assignedActions)) {
            session()->flash('error', 'Silakan pilih slot rak tujuan minimal untuk 1 item.');

            return;
        }

        try {
            $palletId = $this->scannedPallet->pallet_id;
            $res = $wmsService->splitAndAssignMixedPallet($palletId, $assignedActions);
            session()->flash('success', $res['message']);

            if (! empty($res['is_fully_resolved'])) {
                $this->clearScannedPallet();
            } else {
                $this->scannedPallet = WmsPalletForm::with('details')->where('pallet_id', $palletId)->first();
                if ($this->scannedPallet) {
                    $this->loadMixedItemGroups($wmsService);
                } else {
                    $this->clearScannedPallet();
                }
            }

            $this->dispatch('scan-success');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses pemisahan pallet: ' . $e->getMessage());
        }
    }

    /**
     * Force assign entire mixed pallet directly to a single rack slot with warning acknowledged
     */
    public function executeForceAssignMixed(?WmsService $wmsService = null)
    {
        if (! $this->scannedPallet) {
            session()->flash('error', 'Pallet tidak ditemukan.');

            return;
        }

        $targetPosId = $this->assignPositionId ?: $this->selectedPositionId;
        if (! $targetPosId) {
            session()->flash('error', 'Silakan pilih slot rak tujuan terlebih dahulu.');

            return;
        }

        $wmsService = $wmsService ?? app(WmsService::class);

        try {
            $res = $wmsService->splitAndAssignMixedPallet($this->scannedPallet->pallet_id, [], (int) $targetPosId);
            session()->flash('success', $res['message']);
            $this->clearScannedPallet();
            $this->dispatch('scan-success');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal meng-assign slot rak: ' . $e->getMessage());
        }
    }

    /**
     * Unassign / Set Pallet to Temporary
     */
    public function unassignScannedPallet(?WmsService $wmsService = null)
    {
        $this->selectedPositionId = null;
        $this->assignPositionId = null;
        $this->saveAssignSlot($wmsService);
    }

    /**
     * Clear active scanned pallet and reset inputs
     */
    public function clearScannedPallet()
    {
        $this->scannedPallet = null;
        $this->scanPalletId = '';
        $this->selectedPositionId = null;
        $this->scanRackCode = '';
        $this->recommendedPositionId = null;
        $this->assignPalletId = null;
        $this->assignPositionId = null;
        $this->showAssignModal = false;
        $this->showBoxDetails = false;
        $this->isMixedPallet = false;
        $this->mixedItemGroups = [];
        $this->splitItemActions = [];
        $this->mixedAssignMode = 'SPLIT';
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
            $wmsService->logTransaction($palletId, 'DELETE_PALLET', $positionId, 'Deleted from Pallet Form Index');

            // Update rack status if it was in a rack
            if ($positionId) {
                $wmsService->updatePositionStatus($positionId);
            }

            DB::commit();

            session()->flash('success', "Pallet $palletId berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus pallet: ' . $e->getMessage());
        }
    }

    public function openSoModal(string $palletId)
    {
        $this->selectedPalletId = $palletId;
        $this->soDeliveryData = $this->loadSoDeliveryData($palletId);
        $this->showSoModal = true;
    }

    public function closeSoModal()
    {
        $this->showSoModal = false;
        $this->selectedPalletId = null;
        $this->soDeliveryData = [];
    }

    public function loadSoDeliveryData(string $palletId): array
    {
        $pallet = WmsPalletForm::withTrashed()
            ->with(['masterItem.customer', 'position.rack.warehouse'])
            ->where('pallet_id', $palletId)
            ->first();

        if (! $pallet) {
            return [];
        }

        $allDetails = \App\Models\WmsPalletFormDetail::withTrashed()
            ->with('item.customer')
            ->where('pallet_form_id', $palletId)
            ->get();

        $shippedDetails = $allDetails->filter(fn ($d) => $d->trashed());
        $shippedQty = (float) $shippedDetails->sum('qty');
        $shippedBoxes = $shippedDetails->count();

        $outLogs = \App\Models\WmsPalletLog::where('pallet_id', $palletId)
            ->where('transaction_type', 'OUT')
            ->orderBy('created_at', 'asc')
            ->get();

        $soGroups = [];
        $matchedLabels = [];

        // STRICT MATCH: Only search SO deliveries for boxes that were ACTUALLY shipped/deleted from this pallet
        if ($shippedDetails->isNotEmpty()) {
            $shippedLabels = $shippedDetails->pluck('label')->filter()->unique()->values()->all();
            $partNos = $allDetails->pluck('part_no')->filter()->unique()->values()->all();
            $spkNos = $shippedDetails->pluck('spk_no')->filter()->unique()->values()->all();

            $scans = collect();
            if (! empty($shippedLabels)) {
                $scans = \App\Models\ScannedData::with('soData')
                    ->whereIn('label', $shippedLabels)
                    ->whereIn('item_code', $partNos)
                    ->when(! empty($spkNos), fn ($q) => $q->whereIn('spk_code', $spkNos))
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            foreach ($scans as $scan) {
                $docNum = $scan->doc_num ?: 'TANPA-SO';
                $matchedLabels[$scan->label] = true;

                if (! isset($soGroups[$docNum])) {
                    $soData = $scan->soData ?? \App\Models\SoData::where('doc_num', $docNum)->first();
                    $soGroups[$docNum] = [
                        'doc_num' => $docNum,
                        'customer' => $soData?->customer ?? $pallet->masterItem?->customer?->customer_name ?? 'Customer',
                        'posting_date' => $soData?->posting_date ? \Carbon\Carbon::parse($soData->posting_date)->format('d/m/Y') : '-',
                        'pallet_shipped_qty' => 0,
                        'pallet_shipped_boxes' => 0,
                        'scan_dates' => [],
                        'boxes' => [],
                    ];
                }

                $boxQty = (float) $scan->quantity;
                $soGroups[$docNum]['pallet_shipped_qty'] += $boxQty;
                $soGroups[$docNum]['pallet_shipped_boxes'] += 1;
                if ($scan->created_at) {
                    $soGroups[$docNum]['scan_dates'][] = $scan->created_at->format('d/m/Y H:i');
                }

                $soGroups[$docNum]['boxes'][] = [
                    'label' => $scan->label,
                    'item_code' => $scan->item_code ?: $pallet->part_no,
                    'spk_code' => $scan->spk_code,
                    'quantity' => $scan->quantity,
                    'warehouse' => $scan->warehouse,
                    'scanned_at' => $scan->created_at ? $scan->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-',
                ];
            }

            // Check if any OUT logs mention DocNum for this pallet
            foreach ($outLogs as $log) {
                if (preg_match('/DocNum:\s*([A-Za-z0-9\-]+)/i', $log->notes, $matches)) {
                    $docNum = $matches[1];
                    $logItem = $pallet->part_no;
                    if (preg_match('/Item:\s*([A-Za-z0-9\.\-]+)/i', $log->notes, $iMatch)) {
                        $logItem = $iMatch[1];
                    }

                    if (in_array($logItem, $partNos) || empty($partNos)) {
                        if (! isset($soGroups[$docNum])) {
                            $soData = \App\Models\SoData::where('doc_num', $docNum)->first();
                            $soGroups[$docNum] = [
                                'doc_num' => $docNum,
                                'customer' => $soData?->customer ?? $pallet->masterItem?->customer?->customer_name ?? 'Customer',
                                'posting_date' => $soData?->posting_date ? \Carbon\Carbon::parse($soData->posting_date)->format('d/m/Y') : '-',
                                'pallet_shipped_qty' => 0,
                                'pallet_shipped_boxes' => 0,
                                'scan_dates' => [$log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-'],
                                'boxes' => [],
                                'log_note' => $log->notes,
                            ];
                        }

                        // If log has box label and qty, capture it if not already in boxes
                        if (preg_match('/Label:\s*([A-Za-z0-9\-]+)/i', $log->notes, $lblMatch)) {
                            $lbl = $lblMatch[1];
                            $matchedLabels[$lbl] = true;
                            $alreadyInBoxes = collect($soGroups[$docNum]['boxes'])->contains('label', $lbl);
                            if (! $alreadyInBoxes) {
                                $logQty = 0;
                                if (preg_match('/Qty:\s*([0-9\.]+)/i', $log->notes, $qMatch)) {
                                    $logQty = (float) $qMatch[1];
                                }
                                $soGroups[$docNum]['pallet_shipped_qty'] += $logQty;
                                $soGroups[$docNum]['pallet_shipped_boxes'] += 1;
                                $soGroups[$docNum]['boxes'][] = [
                                    'label' => $lbl,
                                    'item_code' => $logItem,
                                    'spk_code' => null,
                                    'quantity' => $logQty,
                                    'warehouse' => 'FG',
                                    'scanned_at' => $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-',
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Unmatched soft-deleted boxes
        $unmatchedDeletedBoxes = $shippedDetails->filter(fn ($d) => ! isset($matchedLabels[$d->label]))
            ->map(fn ($d) => [
                'label' => $d->label,
                'part_no' => $d->part_no,
                'spk_no' => $d->spk_no,
                'qty' => $d->qty,
                'warehouse' => $d->warehouse,
                'deleted_at' => $d->deleted_at ? $d->deleted_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-',
            ])
            ->values()
            ->all();

        // Active remaining boxes on pallet
        $activeBoxes = $allDetails->filter(fn ($d) => ! $d->trashed())
            ->map(fn ($d) => [
                'label' => $d->label,
                'part_no' => $d->part_no,
                'spk_no' => $d->spk_no,
                'qty' => $d->qty,
                'warehouse' => $d->warehouse,
            ])
            ->values()
            ->all();

        $totalHistoricalQty = (float) $allDetails->sum('qty');
        $totalHistoricalBoxes = $allDetails->count();
        $currentQty = (float) $pallet->total_pallet_qty;
        $shippedQty = max(0, $totalHistoricalQty - $currentQty);

        $customerName = $pallet->masterItem?->customer?->customer_name
            ?? $allDetails->first()?->item?->customer?->customer_name
            ?? 'No Customer';

        return [
            'pallet_id' => $pallet->pallet_id,
            'part_no' => $pallet->part_no,
            'model_name' => $pallet->model_name,
            'customer' => $customerName,
            'status' => $pallet->status,
            'current_qty' => $currentQty,
            'current_boxes' => $pallet->box_qty,
            'total_historical_qty' => $totalHistoricalQty,
            'total_historical_boxes' => $totalHistoricalBoxes,
            'shipped_qty' => $shippedQty,
            'is_fully_shipped' => ($pallet->status === 'OUT' || $currentQty <= 0),
            'so_groups' => array_values($soGroups),
            'unmatched_deleted_boxes' => $unmatchedDeletedBoxes,
            'active_boxes' => $activeBoxes,
            'logs' => $outLogs->map(fn ($l) => [
                'transaction_type' => $l->transaction_type,
                'notes' => $l->notes,
                'created_at' => $l->created_at ? $l->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-',
                'user' => $l->user?->name ?? 'System',
            ])->all(),
        ];
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
            ->where(function ($q) {
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

        $palletForms = WmsPalletForm::with(['position.rack.warehouse', 'details.item.customer', 'masterItem.customer', 'allDetails'])
            ->where(function ($query) {
                $query->where('pallet_id', 'like', '%' . $this->search . '%')
                    ->orWhere('part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('model_name', 'like', '%' . $this->search . '%')
                    ->orWhere('delivery_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterSlot !== 'ALL', function ($query) {
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
            ->when(! empty($this->filterWarehouseId), function ($q) {
                $q->whereHas('rack', function ($rq) {
                    $rq->where('whse_id', $this->filterWarehouseId);
                });
            })
            ->orderBy('position_code', 'asc');

        $availablePositions = $availablePositionsQuery->get();

        $recommendedPosition = $this->recommendedPositionId
            ? WmsPosition::with(['rack.warehouse', 'customer'])->find($this->recommendedPositionId)
            : null;

        return view('livewire.wms.pallet-form-index', [
            'palletForms' => $palletForms,
            'availablePositions' => $availablePositions,
            'warehouses' => $warehouses,
            'recommendedPosition' => $recommendedPosition,
        ]);
    }
}
