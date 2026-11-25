<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletLog;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class WmsDashboard extends Component
{
    public string $statusFilter = 'ALL'; // ALL, EMPTY, PARTIAL, FULL

    public string $search = '';

    // Slot Detail Modal State
    public ?int $selectedPositionId = null;

    public bool $showSlotModal = false;

    protected $queryString = [
        'statusFilter' => ['except' => 'ALL'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->syncPositionStatuses();
    }

    public function selectPosition(int $id): void
    {
        $this->selectedPositionId = $id;
        $this->showSlotModal = true;
    }

    public function closeSlotModal(): void
    {
        $this->showSlotModal = false;
        $this->selectedPositionId = null;
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = strtoupper($status);
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'ALL';
        $this->search = '';
    }

    public function assignPalletToSelectedSlot(string $palletId, WmsService $wmsService): void
    {
        if (! $this->selectedPositionId) {
            session()->flash('error', 'Pilih slot rak terlebih dahulu.');

            return;
        }

        try {
            $pos = WmsPosition::find($this->selectedPositionId);
            $pallet = WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();

            if ($pallet->total_pallet_qty <= 0) {
                session()->flash('error', "Pallet {$pallet->pallet_id} sudah kosong (Qty: 0).");

                return;
            }

            $oldPosId = $pallet->position_id;
            $pallet->update([
                'position_id' => $pos->id,
                'assigned_at' => $pallet->assigned_at ?? now(),
            ]);

            if ($oldPosId) {
                $wmsService->updatePositionStatus($oldPosId);
            }
            $wmsService->updatePositionStatus($pos->id);

            $wmsService->logTransaction($pallet->pallet_id, 'ASSIGN_SLOT', $pos->id, auth()->id(), 'Assigned via WMS Dashboard');

            \Illuminate\Support\Facades\Cache::forget('wms_dashboard_metrics');

            session()->flash('success', "Pallet {$pallet->pallet_id} berhasil disimpan di slot {$pos->position_code}.");
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menempatkan pallet: ' . $e->getMessage());
        }
    }

    public function syncPositionStatuses(): void
    {
        // Auto-fix any position marked PARTIAL/FULL that actually has 0 active stored pallets
        $desyncedPositions = WmsPosition::whereIn('status', ['PARTIAL', 'FULL'])
            ->whereDoesntHave('palletForms', function ($q) {
                $q->where('total_pallet_qty', '>', 0);
            })
            ->get();

        foreach ($desyncedPositions as $pos) {
            WmsPalletForm::where('position_id', $pos->id)
                ->where(function ($q) {
                    $q->where('total_pallet_qty', '<=', 0)
                        ->orWhere('status', 'OUT');
                })
                ->update(['position_id' => null]);

            $pos->update([
                'status' => 'EMPTY',
                'last_item_code' => null,
            ]);
        }
    }

    public function render()
    {
        // 1. Overall WMS Slot Counts & General KPIs (Cached for 30s for lightning fast slot inspection)
        $kpis = \Illuminate\Support\Facades\Cache::remember('wms_dashboard_metrics', 30, function () {
            $totalPositionsCount = WmsPosition::count();
            $emptySlotsCount = WmsPosition::where('status', 'EMPTY')->count();
            $partialSlotsCount = WmsPosition::where('status', 'PARTIAL')->count();
            $fullSlotsCount = WmsPosition::where('status', 'FULL')->count();

            $occupiedSlotsCount = $partialSlotsCount + $fullSlotsCount;
            $occupancyRate = $totalPositionsCount > 0 ? round(($occupiedSlotsCount / $totalPositionsCount) * 100, 1) : 0;
            $emptyRate = $totalPositionsCount > 0 ? round(($emptySlotsCount / $totalPositionsCount) * 100, 1) : 0;
            $fullRate = $totalPositionsCount > 0 ? round(($fullSlotsCount / $totalPositionsCount) * 100, 1) : 0;
            $partialRate = $totalPositionsCount > 0 ? round(($partialSlotsCount / $totalPositionsCount) * 100, 1) : 0;

            $storedPalletsQuery = WmsPalletForm::whereNotNull('position_id')->where('total_pallet_qty', '>', 0);
            $totalStoredPallets = (clone $storedPalletsQuery)->count();
            $totalStoredPcs = (float) (clone $storedPalletsQuery)->sum('total_pallet_qty');
            $totalStoredBoxes = (float) (clone $storedPalletsQuery)->sum('box_qty');

            $thirtyDaysAgo = now()->subDays(30);
            $overagedPalletsQuery = WmsPalletForm::with('position')
                ->whereNotNull('position_id')
                ->where('total_pallet_qty', '>', 0)
                ->where(function ($q) use ($thirtyDaysAgo) {
                    $q->where('assigned_at', '<=', $thirtyDaysAgo)
                        ->orWhere(function ($sq) use ($thirtyDaysAgo) {
                            $sq->whereNull('assigned_at')->where('created_at', '<=', $thirtyDaysAgo);
                        });
                });

            $overagedCount = (clone $overagedPalletsQuery)->count();
            $overagedPalletsList = (clone $overagedPalletsQuery)->orderBy('created_at', 'asc')->take(10)->get();

            $assignedPallets = WmsPalletForm::whereNotNull('assigned_at')->whereNotNull('created_at')->take(100)->get();
            $avgLeadTimeMin = $assignedPallets->isNotEmpty()
                ? round($assignedPallets->avg(fn ($p) => $p->created_at->diffInMinutes($p->assigned_at)))
                : 0;

            $avgLeadTimeText = '-';
            if ($avgLeadTimeMin > 0) {
                if ($avgLeadTimeMin < 60) {
                    $avgLeadTimeText = "{$avgLeadTimeMin} Min";
                } else {
                    $h = floor($avgLeadTimeMin / 60);
                    $m = $avgLeadTimeMin % 60;
                    $avgLeadTimeText = "{$h} J " . ($m > 0 ? "{$m} M" : '');
                }
            }

            $oldestPallets = WmsPalletForm::with('position')
                ->whereNotNull('position_id')
                ->where('total_pallet_qty', '>', 0)
                ->orderBy('created_at', 'asc')
                ->take(5)
                ->get();

            $topStoredItems = DB::table('wms_pallet_forms')
                ->whereNotNull('position_id')
                ->where('total_pallet_qty', '>', 0)
                ->whereNull('deleted_at')
                ->select('part_no', 'model_name', DB::raw('SUM(total_pallet_qty) as total_qty'), DB::raw('COUNT(pallet_id) as pallet_count'))
                ->groupBy('part_no', 'model_name')
                ->orderBy('total_qty', 'desc')
                ->take(5)
                ->get();

            $recentLogs = WmsPalletLog::with(['position', 'pallet'])
                ->orderBy('id', 'desc')
                ->take(6)
                ->get();

            return compact(
                'totalPositionsCount', 'emptySlotsCount', 'partialSlotsCount', 'fullSlotsCount',
                'occupancyRate', 'emptyRate', 'fullRate', 'partialRate',
                'totalStoredPallets', 'totalStoredPcs', 'totalStoredBoxes',
                'overagedCount', 'overagedPalletsList', 'avgLeadTimeText',
                'oldestPallets', 'topStoredItems', 'recentLogs'
            );
        });

        // Unassigned Pallets waiting for slot allocation (limited for performance)
        $unassignedCount = WmsPalletForm::whereNull('position_id')
            ->where('total_pallet_qty', '>', 0)
            ->count();
        $unassignedPallets = WmsPalletForm::whereNull('position_id')
            ->where('total_pallet_qty', '>', 0)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // 2. Query Racks with Filtered Positions (Lean eager loading without nested details for slot grid)
        $thirtyDaysAgo = now()->subDays(30);
        $racksQuery = WmsRack::with(['positions' => function ($q) use ($thirtyDaysAgo) {
            if ($this->statusFilter === 'OVERAGED') {
                $q->whereHas('palletForms', function ($pq) use ($thirtyDaysAgo) {
                    $pq->where('total_pallet_qty', '>', 0)
                        ->where(function ($sq) use ($thirtyDaysAgo) {
                            $sq->where('assigned_at', '<=', $thirtyDaysAgo)
                                ->orWhere(function ($ssq) use ($thirtyDaysAgo) {
                                    $ssq->whereNull('assigned_at')->where('created_at', '<=', $thirtyDaysAgo);
                                });
                        });
                });
            } elseif ($this->statusFilter !== 'ALL') {
                $q->where('status', $this->statusFilter);
            }
            if (! empty($this->search)) {
                $st = '%' . trim($this->search) . '%';
                $q->where(function ($subq) use ($st) {
                    $subq->where('position_code', 'like', $st)
                        ->orWhere('last_item_code', 'like', $st)
                        ->orWhereHas('palletForms', function ($pq) use ($st) {
                            $pq->where('pallet_id', 'like', $st)
                                ->orWhere('part_no', 'like', $st)
                                ->orWhere('model_name', 'like', $st);
                        });
                });
            }
            $q->with(['palletForms' => function ($pq) {
                $pq->where('total_pallet_qty', '>', 0)
                    ->select(['pallet_id', 'position_id', 'part_no', 'model_name', 'total_pallet_qty', 'status', 'assigned_at', 'created_at']);
            }])
                ->withCount(['palletForms' => function ($pq) {
                    $pq->where('total_pallet_qty', '>', 0);
                }])
                ->orderBy('level_no', 'desc')
                ->orderBy('slot_no', 'asc');
        }]);

        $racks = $racksQuery->get();

        // 3. Selected Slot Data for Detail Modal (Deep eager-loading ONLY for the selected slot)
        $selectedPosData = $this->selectedPositionId
            ? WmsPosition::with(['rack', 'palletForms' => function ($q) {
                $q->where('total_pallet_qty', '>', 0)->with('details');
            }])->withCount(['palletForms' => function ($q) {
                $q->where('total_pallet_qty', '>', 0);
            }])->find($this->selectedPositionId)
            : null;

        return view('livewire.wms.wms-dashboard', [
            'totalPositions' => $kpis['totalPositionsCount'],
            'emptySlots' => $kpis['emptySlotsCount'],
            'partialSlots' => $kpis['partialSlotsCount'],
            'fullSlots' => $kpis['fullSlotsCount'],
            'occupancyRate' => $kpis['occupancyRate'],
            'emptyRate' => $kpis['emptyRate'],
            'fullRate' => $kpis['fullRate'],
            'partialRate' => $kpis['partialRate'],
            'totalStoredPallets' => $kpis['totalStoredPallets'],
            'totalStoredPcs' => $kpis['totalStoredPcs'],
            'totalStoredBoxes' => $kpis['totalStoredBoxes'],
            'overagedCount' => $kpis['overagedCount'],
            'overagedPalletsList' => $kpis['overagedPalletsList'],
            'avgLeadTimeText' => $kpis['avgLeadTimeText'],
            'unassignedPallets' => $unassignedPallets,
            'unassignedCount' => $unassignedCount,
            'racks' => $racks,
            'oldestPallets' => $kpis['oldestPallets'],
            'topStoredItems' => $kpis['topStoredItems'],
            'recentLogs' => $kpis['recentLogs'],
            'selectedPosData' => $selectedPosData,
        ]);
    }
}
