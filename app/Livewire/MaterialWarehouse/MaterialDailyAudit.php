<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhDailyAudit;
use App\Models\MwhDailyAuditItem;
use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Models\MwhWarehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MaterialDailyAudit extends Component
{
    // Filter & Meta State
    public $selectedWhseId = 'ALL';
    public string $auditDate = '';
    public string $auditorName = '';
    public string $notes = '';
    public string $searchTerm = '';
    public string $selectedAreaFilter = 'ALL';
    public string $statusFilter = 'ALL'; // ALL, DISCREPANCY_ONLY, MATCH_ONLY, PENDING_ONLY

    // Available Master Options
    public array $warehouses = [];
    public array $availableAreas = [];

    // Working Audit Items (keyed by pallet_id or pos_{position_id})
    public array $items = [];

    // Audit State
    public ?int $existingAuditId = null;
    public string $auditCode = '';
    public bool $isSaved = false;

    // History Modal State
    public bool $showHistoryModal = false;
    public ?int $viewingAuditId = null;

    protected $queryString = [
        'selectedWhseId' => ['except' => 'ALL'],
        'auditDate' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        if (empty($this->auditDate)) {
            $this->auditDate = Carbon::now()->toDateString();
        }

        $this->auditorName = auth()->user()->name ?? 'Auditor Store Material';

        $this->loadAuditData();
    }

    public function updatedSelectedWhseId(): void
    {
        $this->loadAuditData();
    }

    public function updatedAuditDate(): void
    {
        $this->loadAuditData();
    }

    /**
     * Load existing audit if already created today, or pull active system pallets.
     */
    public function loadAuditData(): void
    {
        // 1. Check if audit record exists for this date & warehouse
        $query = MwhDailyAudit::with('items')
            ->whereDate('audit_date', $this->auditDate);

        if ($this->selectedWhseId !== 'ALL') {
            $query->where('whse_id', $this->selectedWhseId);
        }

        $existingAudit = $query->latest('id')->first();

        if ($existingAudit && $existingAudit->items->isNotEmpty()) {
            $this->existingAuditId = $existingAudit->id;
            $this->auditCode       = $existingAudit->audit_code;
            $this->auditorName     = $existingAudit->auditor_name ?: (auth()->user()->name ?? 'Auditor Store Material');
            $this->notes           = $existingAudit->notes ?? '';
            $this->isSaved         = true;

            $itemsData = [];
            foreach ($existingAudit->items as $auditItem) {
                $key = $auditItem->pallet_id ?: ('pos_' . $auditItem->position_id);
                $itemsData[$key] = [
                    'id'                 => $auditItem->id,
                    'pallet_id'          => $auditItem->pallet_id,
                    'position_id'        => $auditItem->position_id,
                    'position_code'      => $auditItem->position_code,
                    'rack_code'          => $auditItem->rack_code,
                    'system_item_code'   => $auditItem->system_item_code,
                    'system_item_name'   => $auditItem->system_item_name,
                    'system_lot_no'      => $auditItem->system_lot_no,
                    'system_qty'         => (float) $auditItem->system_qty,
                    'uom'                => $auditItem->uom,
                    'actual_item_code'   => $auditItem->actual_item_code ?? '',
                    'actual_qty'         => $auditItem->actual_qty !== null ? (float) $auditItem->actual_qty : null,
                    'discrepancy_reason' => $auditItem->discrepancy_reason ?? '',
                ];
            }
            $this->items = $itemsData;
        } else {
            // 2. Initialize from active system pallets currently in rack positions
            $this->existingAuditId = null;
            $this->auditCode       = 'AUD-MWH-' . Carbon::parse($this->auditDate)->format('Ymd') . '-' . ($this->selectedWhseId !== 'ALL' ? $this->selectedWhseId : 'ALL');
            $this->isSaved         = false;

            $palletQuery = MwhPallet::with(['position.rack.warehouse', 'material'])
                ->whereNotNull('position_id')
                ->where('current_qty', '>', 0);

            if ($this->selectedWhseId !== 'ALL') {
                $palletQuery->where(function ($q) {
                    $q->where('whse_id', $this->selectedWhseId)
                      ->orWhereHas('position.rack', fn($rq) => $rq->where('whse_id', $this->selectedWhseId));
                });
            }

            $pallets = $palletQuery->get()->sortBy(function ($p) {
                return $p->position->position_code ?? $p->pallet_id;
            });

            $itemsData = [];
            foreach ($pallets as $p) {
                $pos = $p->position;
                $key = $p->pallet_id;
                $itemsData[$key] = [
                    'id'                 => null,
                    'pallet_id'          => $p->pallet_id,
                    'position_id'        => $p->position_id,
                    'position_code'      => $pos ? $pos->position_code : 'N/A',
                    'rack_code'          => $pos && $pos->rack ? $pos->rack->rack_code : '',
                    'system_item_code'   => $p->item_code,
                    'system_item_name'   => $p->material->description ?? $p->item_code,
                    'system_lot_no'      => $p->lot_no,
                    'system_qty'         => (float) $p->current_qty,
                    'uom'                => $p->uom ?: 'KG',
                    'actual_item_code'   => '',
                    'actual_qty'         => null,
                    'discrepancy_reason' => '',
                ];
            }

            $this->items = $itemsData;
        }

        // Collect available rack areas for filter dropdown
        $this->availableAreas = collect($this->items)
            ->pluck('rack_code')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * 1-Click: Fill all actual item & qty to match system values.
     * Auditor only needs to edit rows that differ.
     */
    public function fillAllActualFromSystem(): void
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key]['actual_item_code'] = $item['system_item_code'];
            $this->items[$key]['actual_qty']       = $item['system_qty'];
            $this->items[$key]['discrepancy_reason'] = '';
        }

        session()->flash('info', 'Semua data actual berhasil diisi sesuai sistem. Silakan sesuaikan baris yang memiliki selisih fisik.');
    }

    /**
     * 1-Click: Fill a specific row with system values.
     */
    public function fillRowFromSystem(string $key): void
    {
        if (isset($this->items[$key])) {
            $this->items[$key]['actual_item_code'] = $this->items[$key]['system_item_code'];
            $this->items[$key]['actual_qty']       = $this->items[$key]['system_qty'];
            $this->items[$key]['discrepancy_reason'] = '';
        }
    }

    /**
     * Clear all actual inputs.
     */
    public function clearAllActual(): void
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key]['actual_item_code'] = '';
            $this->items[$key]['actual_qty']       = null;
            $this->items[$key]['discrepancy_reason'] = '';
        }
    }

    /**
     * Save or update audit results.
     */
    public function saveAudit(): void
    {
        if (empty($this->items)) {
            session()->flash('error', 'Tidak ada data item untuk diaudit.');
            return;
        }

        DB::beginTransaction();
        try {
            $totalCount      = count($this->items);
            $matchedCount    = 0;
            $discrepantCount = 0;

            foreach ($this->items as $item) {
                $sysItem = trim($item['system_item_code'] ?? '');
                $actItem = trim($item['actual_item_code'] ?? '');
                $sysQty  = (float) ($item['system_qty'] ?? 0);
                $actQty  = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;

                $hasActual = ($actItem !== '' || $actQty !== null);
                if ($hasActual) {
                    $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                    $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);

                    if ($itemMatches && $qtyMatches) {
                        $matchedCount++;
                    } else {
                        $discrepantCount++;
                    }
                }
            }

            $whseId = $this->selectedWhseId !== 'ALL' ? (int) $this->selectedWhseId : null;

            // Generate clean unique audit code
            $auditCode = $this->auditCode ?: ('AUD-MWH-' . Carbon::parse($this->auditDate)->format('Ymd') . '-' . ($whseId ?: 'ALL'));

            $audit = MwhDailyAudit::updateOrCreate(
                [
                    'audit_date' => $this->auditDate,
                    'whse_id'    => $whseId,
                ],
                [
                    'audit_code'       => $auditCode,
                    'auditor_id'       => auth()->id(),
                    'auditor_name'     => $this->auditorName ?: (auth()->user()->name ?? 'Auditor Store Material'),
                    'total_items'      => $totalCount,
                    'matched_items'    => $matchedCount,
                    'discrepant_items' => $discrepantCount,
                    'status'           => 'COMPLETED',
                    'notes'            => trim($this->notes),
                ]
            );

            // Re-sync audit items
            $audit->items()->delete();

            foreach ($this->items as $item) {
                $sysItem = trim($item['system_item_code'] ?? '');
                $actItem = trim($item['actual_item_code'] ?? '');
                $sysQty  = (float) ($item['system_qty'] ?? 0);
                $actQty  = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;
                $diffQty = $actQty !== null ? ($actQty - $sysQty) : 0;

                $status = 'PENDING';
                if ($actItem !== '' || $actQty !== null) {
                    $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                    $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);
                    $status = ($itemMatches && $qtyMatches) ? 'MATCH' : 'DISCREPANCY';
                }

                MwhDailyAuditItem::create([
                    'daily_audit_id'     => $audit->id,
                    'pallet_id'          => $item['pallet_id'] ?? null,
                    'position_id'        => $item['position_id'] ?? null,
                    'position_code'      => $item['position_code'] ?? 'N/A',
                    'rack_code'          => $item['rack_code'] ?? null,
                    'system_item_code'   => $item['system_item_code'] ?? null,
                    'system_item_name'   => $item['system_item_name'] ?? null,
                    'system_lot_no'      => $item['system_lot_no'] ?? null,
                    'system_qty'         => $sysQty,
                    'uom'                => $item['uom'] ?? 'KG',
                    'actual_item_code'   => $actItem ?: null,
                    'actual_qty'         => $actQty,
                    'diff_qty'           => $diffQty,
                    'discrepancy_reason' => trim($item['discrepancy_reason'] ?? '') ?: null,
                    'status'             => $status,
                ]);
            }

            DB::commit();

            $this->existingAuditId = $audit->id;
            $this->isSaved         = true;

            session()->flash('success', "✅ Audit Harian tanggal {$this->auditDate} berhasil disimpan! ({$matchedCount} sesuai, {$discrepantCount} selisih dari {$totalCount} item).");
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan audit: ' . $e->getMessage());
        }
    }

    public function openHistoryModal(): void
    {
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal(): void
    {
        $this->showHistoryModal = false;
        $this->viewingAuditId   = null;
    }

    public function render()
    {
        // Filter items for display
        $filteredItems = collect($this->items);

        if (!empty($this->searchTerm)) {
            $s = strtolower(trim($this->searchTerm));
            $filteredItems = $filteredItems->filter(function ($item) use ($s) {
                return str_contains(strtolower($item['position_code'] ?? ''), $s) ||
                       str_contains(strtolower($item['rack_code'] ?? ''), $s) ||
                       str_contains(strtolower($item['pallet_id'] ?? ''), $s) ||
                       str_contains(strtolower($item['system_item_code'] ?? ''), $s) ||
                       str_contains(strtolower($item['system_item_name'] ?? ''), $s) ||
                       str_contains(strtolower($item['system_lot_no'] ?? ''), $s) ||
                       str_contains(strtolower($item['actual_item_code'] ?? ''), $s) ||
                       str_contains(strtolower($item['discrepancy_reason'] ?? ''), $s);
            });
        }

        if ($this->selectedAreaFilter !== 'ALL') {
            $filteredItems = $filteredItems->filter(function ($item) {
                return ($item['rack_code'] ?? '') === $this->selectedAreaFilter;
            });
        }

        if ($this->statusFilter === 'DISCREPANCY_ONLY') {
            $filteredItems = $filteredItems->filter(function ($item) {
                $sysItem = trim($item['system_item_code'] ?? '');
                $actItem = trim($item['actual_item_code'] ?? '');
                $sysQty  = (float) ($item['system_qty'] ?? 0);
                $actQty  = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;

                if ($actItem === '' && $actQty === null) {
                    return false;
                }
                $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);
                return !$itemMatches || !$qtyMatches;
            });
        } elseif ($this->statusFilter === 'MATCH_ONLY') {
            $filteredItems = $filteredItems->filter(function ($item) {
                $sysItem = trim($item['system_item_code'] ?? '');
                $actItem = trim($item['actual_item_code'] ?? '');
                $sysQty  = (float) ($item['system_qty'] ?? 0);
                $actQty  = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;

                if ($actItem === '' && $actQty === null) {
                    return false;
                }
                $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);
                return $itemMatches && $qtyMatches;
            });
        } elseif ($this->statusFilter === 'PENDING_ONLY') {
            $filteredItems = $filteredItems->filter(function ($item) {
                return empty(trim($item['actual_item_code'] ?? '')) && ($item['actual_qty'] === null || $item['actual_qty'] === '');
            });
        }

        // Summary counts
        $totalAll = count($this->items);
        $totalFilled = 0;
        $totalMatch = 0;
        $totalDiscrepancy = 0;

        foreach ($this->items as $item) {
            $sysItem = trim($item['system_item_code'] ?? '');
            $actItem = trim($item['actual_item_code'] ?? '');
            $sysQty  = (float) ($item['system_qty'] ?? 0);
            $actQty  = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;

            if ($actItem !== '' || $actQty !== null) {
                $totalFilled++;
                $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);
                if ($itemMatches && $qtyMatches) {
                    $totalMatch++;
                } else {
                    $totalDiscrepancy++;
                }
            }
        }

        $auditHistoryList = [];
        if ($this->showHistoryModal) {
            $auditHistoryList = MwhDailyAudit::with(['warehouse', 'auditor'])
                ->orderBy('audit_date', 'desc')
                ->limit(30)
                ->get();
        }

        $viewingAudit = null;
        if ($this->viewingAuditId) {
            $viewingAudit = MwhDailyAudit::with(['warehouse', 'auditor', 'items'])->find($this->viewingAuditId);
        }

        return view('livewire.material-warehouse.material-daily-audit', [
            'displayItems'      => $filteredItems,
            'totalAll'          => $totalAll,
            'totalFilled'       => $totalFilled,
            'totalMatch'        => $totalMatch,
            'totalDiscrepancy'  => $totalDiscrepancy,
            'auditHistoryList'  => $auditHistoryList,
            'viewingAudit'      => $viewingAudit,
        ]);
    }
}
