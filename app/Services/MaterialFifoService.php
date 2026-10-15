<?php

namespace App\Services;

use App\Models\MasterListMaterial;
use App\Models\MwhIncomingHeader;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use App\Models\MwhWarehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialFifoService
{
    /**
     * Get High-Level Executive FIFO KPIs
     */
    public function getFifoKpis($whseId = null, ?string $fromDate = null, ?string $toDate = null, ?array $preloadedDeviations = null): array
    {
        $whseId = ($whseId && is_numeric($whseId)) ? (int) $whseId : null;

        // 1. Outgoings in period
        $outgoingQuery = MwhOutgoing::query();
        if ($whseId) {
            $outgoingQuery->where('mwh_outgoings.whse_id', $whseId);
        }
        if ($fromDate) {
            $outgoingQuery->whereDate('mwh_outgoings.outgoing_date', '>=', $fromDate);
        }
        if ($toDate) {
            $outgoingQuery->whereDate('mwh_outgoings.outgoing_date', '<=', $toDate);
        }

        $totalOutgoingsCount = (int) $outgoingQuery->count();
        $totalOutgoingQty    = (float) $outgoingQuery->sum('mwh_outgoings.qty_taken');

        // 2. FIFO Deviations
        $deviations = $preloadedDeviations !== null 
            ? $preloadedDeviations 
            : $this->detectFifoDeviations($whseId, $fromDate, $toDate, 1000);
        $deviationCount = count($deviations);

        $compliantCount = max(0, $totalOutgoingsCount - $deviationCount);
        $complianceRate = $totalOutgoingsCount > 0 
            ? round(($compliantCount / $totalOutgoingsCount) * 100, 1) 
            : 100.0;

        // 3. Current active warehouse inventory
        $palletQuery = MwhPallet::where('mwh_pallets.current_qty', '>', 0)
            ->whereIn('mwh_pallets.status', ['STORED', 'PARTIAL']);

        if ($whseId) {
            $palletQuery->where('mwh_pallets.whse_id', $whseId);
        }

        $totalActivePallets = (int) $palletQuery->count();
        $totalActiveStockKg = (float) $palletQuery->sum('mwh_pallets.current_qty');

        // 4. QC Hold inventory
        $qcHoldQuery = MwhPallet::where('mwh_pallets.current_qty', '>', 0)
            ->where('mwh_pallets.is_qc_hold', true);
        if ($whseId) {
            $qcHoldQuery->where('mwh_pallets.whse_id', $whseId);
        }
        $qcHoldPalletsCount = (int) $qcHoldQuery->count();
        $qcHoldQtyKg        = (float) $qcHoldQuery->sum('mwh_pallets.current_qty');

        // 5. Overaged active inventory (>60 days) - Database-agnostic fast SQL
        $sixtyDaysAgo = now()->subDays(60)->format('Y-m-d');
        $overagedQuery = MwhPallet::query()
            ->leftJoin('mwh_incoming_headers', 'mwh_pallets.incoming_header_id', '=', 'mwh_incoming_headers.id')
            ->where('mwh_pallets.current_qty', '>', 0)
            ->whereIn('mwh_pallets.status', ['STORED', 'PARTIAL'])
            ->where(function ($q) use ($sixtyDaysAgo) {
                $q->where('mwh_incoming_headers.arrival_date', '<=', $sixtyDaysAgo)
                  ->orWhere(function ($sub) use ($sixtyDaysAgo) {
                      $sub->whereNull('mwh_incoming_headers.arrival_date')
                          ->whereDate('mwh_pallets.created_at', '<=', $sixtyDaysAgo);
                  });
            });

        if ($whseId) {
            $overagedQuery->where('mwh_pallets.whse_id', $whseId);
        }
        $overagedStockKg = (float) ($overagedQuery->sum('mwh_pallets.current_qty') ?? 0);
        $overagedPalletsCount = (int) ($overagedQuery->count() ?? 0);

        // 6. Average Days to Consume (Turnaround Lead Time) - Fast single query
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $diffSql = $isSqlite
            ? 'AVG(julianday(mwh_outgoings.outgoing_date) - julianday(COALESCE(mwh_incoming_headers.arrival_date, DATE(mwh_pallets.created_at)))) as avg_lead'
            : 'AVG(DATEDIFF(mwh_outgoings.outgoing_date, COALESCE(mwh_incoming_headers.arrival_date, DATE(mwh_pallets.created_at)))) as avg_lead';

        $rawAvgLead = (clone $outgoingQuery)
            ->join('mwh_pallets', 'mwh_outgoings.pallet_id', '=', 'mwh_pallets.pallet_id')
            ->leftJoin('mwh_incoming_headers', 'mwh_pallets.incoming_header_id', '=', 'mwh_incoming_headers.id')
            ->selectRaw($diffSql)
            ->value('avg_lead');

        $avgLeadDays = $rawAvgLead !== null ? max(0, round((float) $rawAvgLead, 1)) : 0.0;

        // Grade status
        $grade = 'EXCELLENT';
        $gradeColor = 'emerald';
        if ($complianceRate < 85) {
            $grade = 'CRITICAL';
            $gradeColor = 'rose';
        } elseif ($complianceRate < 95) {
            $grade = 'NEEDS ATTENTION';
            $gradeColor = 'amber';
        }

        return [
            'compliance_rate'        => $complianceRate,
            'grade'                  => $grade,
            'grade_color'            => $gradeColor,
            'total_outgoings_count'  => $totalOutgoingsCount,
            'total_outgoing_qty'     => $totalOutgoingQty,
            'compliant_count'        => $compliantCount,
            'deviation_count'        => $deviationCount,
            'total_active_pallets'   => $totalActivePallets,
            'total_active_stock_kg'  => $totalActiveStockKg,
            'overaged_stock_kg'      => $overagedStockKg,
            'overaged_pallets_count' => $overagedPalletsCount,
            'qc_hold_pallets_count'  => $qcHoldPalletsCount,
            'qc_hold_qty_kg'         => $qcHoldQtyKg,
            'avg_lead_days'          => $avgLeadDays,
        ];
    }

    /**
     * Detect Outgoings where a newer lot was picked while older stock was available (FIFO Deviation)
     */
    public function detectFifoDeviations($whseId = null, ?string $fromDate = null, ?string $toDate = null, int $limit = 50): array
    {
        $whseId = ($whseId && is_numeric($whseId)) ? (int) $whseId : null;

        $outgoingsQuery = MwhOutgoing::with([
            'pallet.incomingHeader',
            'pallet.position.rack',
            'material',
            'warehouse',
            'position.rack'
        ])->orderBy('mwh_outgoings.outgoing_date', 'desc')
          ->orderBy('mwh_outgoings.id', 'desc');

        if ($whseId) {
            $outgoingsQuery->where('mwh_outgoings.whse_id', $whseId);
        }
        if ($fromDate) {
            $outgoingsQuery->whereDate('mwh_outgoings.outgoing_date', '>=', $fromDate);
        }
        if ($toDate) {
            $outgoingsQuery->whereDate('mwh_outgoings.outgoing_date', '<=', $toDate);
        }

        $outgoings = $outgoingsQuery->get();
        if ($outgoings->isEmpty()) {
            return [];
        }

        // Bulk-load all candidate pallets with their outgoings in ONE query (no N+1 loop queries)
        $itemCodes = $outgoings->pluck('item_code')->unique()->values();
        $candidatePalletsGrouped = MwhPallet::with(['incomingHeader', 'position.rack', 'initialPosition.rack', 'outgoings'])
            ->whereIn('item_code', $itemCodes)
            ->where('is_qc_hold', false)
            ->when($whseId, fn($q) => $q->where('whse_id', $whseId))
            ->get()
            ->groupBy('item_code');

        $deviations = [];

        foreach ($outgoings as $out) {
            $pickedPallet = $out->pallet;
            if (!$pickedPallet) {
                continue;
            }

            $pickedArrivalDate = $pickedPallet->incomingHeader?->arrival_date
                ? Carbon::parse($pickedPallet->incomingHeader->arrival_date)->format('Y-m-d')
                : $pickedPallet->created_at->format('Y-m-d');

            $outgoingTimestamp = Carbon::parse($out->outgoing_date);
            if ($out->created_at) {
                $outgoingTimestamp->setTimeFrom($out->created_at);
            }

            // Retrieve preloaded potential older pallets for this item code from memory
            $potentialOlderPallets = $candidatePalletsGrouped->get($out->item_code, collect())
                ->where('pallet_id', '!=', $pickedPallet->pallet_id);

            $skippedOlderPallets = [];

            foreach ($potentialOlderPallets as $older) {
                $olderArrival = $older->incomingHeader?->arrival_date
                    ? Carbon::parse($older->incomingHeader->arrival_date)->format('Y-m-d')
                    : $older->created_at->format('Y-m-d');

                // Must be strictly older than picked lot
                if ($olderArrival >= $pickedArrivalDate) {
                    continue;
                }

                // Calculate historical stock of $older at the moment of this outgoing
                $initialQty = (float) $older->initial_qty > 0 
                    ? (float) $older->initial_qty 
                    : ((float) $older->current_qty + (float) $older->outgoings->sum('qty_taken'));

                $consumedBeforeOrAt = (float) $older->outgoings->filter(function ($o) use ($out, $outgoingTimestamp) {
                    if ($o->id === $out->id) {
                        return false;
                    }
                    $oDate = Carbon::parse($o->outgoing_date);
                    if ($o->created_at) {
                        $oDate->setTimeFrom($o->created_at);
                    }
                    if ($oDate->lt($outgoingTimestamp)) {
                        return true;
                    }
                    if ($oDate->eq($outgoingTimestamp) && $o->id < $out->id) {
                        return true;
                    }
                    return false;
                })->sum('qty_taken');

                $stockAtTime = max(0, $initialQty - $consumedBeforeOrAt);

                // If older pallet was ALREADY EMPTY at the time of this outgoing, it could not have been bypassed!
                if ($stockAtTime <= 0) {
                    continue;
                }

                $skippedOlderPallets[] = [
                    'pallet'        => $older,
                    'arrival_date'  => $olderArrival,
                    'stock_at_time' => $stockAtTime,
                ];
            }

            if (!empty($skippedOlderPallets)) {
                // Find the oldest skipped pallet (sorted by arrival_date ascending)
                usort($skippedOlderPallets, function ($a, $b) {
                    return strcmp($a['arrival_date'], $b['arrival_date']);
                });

                $oldestEntry   = $skippedOlderPallets[0];
                $oldestSkipped = $oldestEntry['pallet'];
                $oldestArrival = $oldestEntry['arrival_date'];
                $stockAtTime   = $oldestEntry['stock_at_time'];

                $deltaDays = Carbon::parse($oldestArrival)->diffInDays(Carbon::parse($pickedArrivalDate));

                // Only count as significant deviation if delta is >= 1 day
                if ($deltaDays >= 1) {
                    $deviations[] = [
                        'outgoing_id'           => $out->id,
                        'outgoing_code'         => $out->outgoing_code,
                        'outgoing_date'         => $out->outgoing_date ? Carbon::parse($out->outgoing_date)->format('d/m/Y') : '-',
                        'item_code'             => $out->item_code,
                        'item_description'      => $out->material?->item_description ?: 'Material ' . $out->item_code,
                        'qty_taken'             => (float) $out->qty_taken,
                        'uom'                   => $out->uom ?: 'KG',
                        'issued_to'             => $out->issued_to ?: 'Production',
                        'warehouse_name'        => $out->warehouse?->whse_name ?: 'Main Warehouse',
                        'picked_pallet_id'      => $pickedPallet->pallet_id,
                        'picked_lot_no'         => $pickedPallet->lot_no ?: '-',
                        'picked_arrival_date'   => Carbon::parse($pickedArrivalDate)->format('d/m/Y'),
                        'picked_position_code'  => $out->position?->position_code ?: ($pickedPallet->position?->position_code ?: 'Non-Rak'),
                        'skipped_pallet_id'     => $oldestSkipped->pallet_id,
                        'skipped_lot_no'        => $oldestSkipped->lot_no ?: '-',
                        'skipped_arrival_date'  => Carbon::parse($oldestArrival)->format('d/m/Y'),
                        'skipped_position_code' => $oldestSkipped->position?->position_code ?: ($oldestSkipped->initialPosition?->position_code ?: 'Non-Rak'),
                        'skipped_current_qty'   => (float) $oldestSkipped->current_qty,
                        'skipped_stock_at_time' => (float) $stockAtTime,
                        'delta_days'            => (int) $deltaDays,
                        'severity'              => $deltaDays >= 30 ? 'HIGH' : ($deltaDays >= 7 ? 'MEDIUM' : 'LOW'),
                    ];
                }
            }
        }

        return array_slice($deviations, 0, $limit);
    }

    /**
     * Get Inventory Aging Distribution
     */
    public function getInventoryAgingSummary($whseId = null): array
    {
        $whseId = ($whseId && is_numeric($whseId)) ? (int) $whseId : null;

        $palletsQuery = MwhPallet::with(['incomingHeader', 'position.rack', 'material'])
            ->where('mwh_pallets.current_qty', '>', 0)
            ->whereIn('mwh_pallets.status', ['STORED', 'PARTIAL']);

        if ($whseId) {
            $palletsQuery->where('mwh_pallets.whse_id', $whseId);
        }

        $pallets = $palletsQuery->get();
        $totalQty = (float) $pallets->sum('current_qty');

        $tierFresh    = ['count' => 0, 'qty' => 0.0, 'label' => '0 - 30 Hari (Fresh)', 'color' => 'emerald', 'pallets' => []];
        $tierNormal   = ['count' => 0, 'qty' => 0.0, 'label' => '31 - 60 Hari (Normal)', 'color' => 'blue', 'pallets' => []];
        $tierWarning  = ['count' => 0, 'qty' => 0.0, 'label' => '61 - 90 Hari (Warning)', 'color' => 'amber', 'pallets' => []];
        $tierCritical = ['count' => 0, 'qty' => 0.0, 'label' => '> 90 Hari (Stagnant)', 'color' => 'rose', 'pallets' => []];

        $now = now();
        $palletAgeList = [];

        foreach ($pallets as $p) {
            $refDate = $p->incomingHeader?->arrival_date
                ? Carbon::parse($p->incomingHeader->arrival_date)
                : $p->created_at;

            $ageDays = max(0, (int) $refDate->diffInDays($now));
            $qty = (float) $p->current_qty;

            $palletItem = [
                'pallet_id'        => $p->pallet_id,
                'item_code'        => $p->item_code,
                'item_description' => $p->material?->item_description ?: $p->item_code,
                'lot_no'           => $p->lot_no ?: '-',
                'position_code'    => $p->position?->position_code ?: 'Non-Rak',
                'current_qty'      => $qty,
                'uom'              => $p->uom ?: 'KG',
                'age_days'         => $ageDays,
                'arrival_date'     => $refDate->format('d/m/Y'),
                'is_qc_hold'       => (bool) $p->is_qc_hold,
            ];

            $palletAgeList[] = $palletItem;

            if ($ageDays <= 30) {
                $tierFresh['count']++;
                $tierFresh['qty'] += $qty;
            } elseif ($ageDays <= 60) {
                $tierNormal['count']++;
                $tierNormal['qty'] += $qty;
            } elseif ($ageDays <= 90) {
                $tierWarning['count']++;
                $tierWarning['qty'] += $qty;
            } else {
                $tierCritical['count']++;
                $tierCritical['qty'] += $qty;
            }
        }

        $tierFresh['percent']    = $totalQty > 0 ? round(($tierFresh['qty'] / $totalQty) * 100, 1) : 0;
        $tierNormal['percent']   = $totalQty > 0 ? round(($tierNormal['qty'] / $totalQty) * 100, 1) : 0;
        $tierWarning['percent']  = $totalQty > 0 ? round(($tierWarning['qty'] / $totalQty) * 100, 1) : 0;
        $tierCritical['percent'] = $totalQty > 0 ? round(($tierCritical['qty'] / $totalQty) * 100, 1) : 0;

        // Top 5 oldest stagnant pallets
        usort($palletAgeList, fn($a, $b) => $b['age_days'] <=> $a['age_days']);
        $oldestPallets = array_slice($palletAgeList, 0, 8);

        return [
            'total_qty'      => $totalQty,
            'total_pallets'  => count($pallets),
            'tiers'          => [
                'fresh'    => $tierFresh,
                'normal'   => $tierNormal,
                'warning'  => $tierWarning,
                'critical' => $tierCritical,
            ],
            'oldest_pallets' => $oldestPallets,
        ];
    }

    /**
     * Get Live FIFO Priority Queue (What lot MUST be picked next for each active material)
     */
    public function getFifoPriorityQueue($whseId = null, string $search = '', int $limit = 20): array
    {
        $whseId = ($whseId && is_numeric($whseId)) ? (int) $whseId : null;

        $palletsQuery = MwhPallet::with(['incomingHeader', 'position.rack', 'material', 'warehouse'])
            ->where('mwh_pallets.current_qty', '>', 0)
            ->whereIn('mwh_pallets.status', ['STORED', 'PARTIAL']);

        if ($whseId) {
            $palletsQuery->where('mwh_pallets.whse_id', $whseId);
        }

        if (!empty(trim($search))) {
            $term = '%' . trim($search) . '%';
            $palletsQuery->where(function ($q) use ($term) {
                $q->where('mwh_pallets.item_code', 'like', $term)
                  ->orWhere('mwh_pallets.lot_no', 'like', $term)
                  ->orWhere('mwh_pallets.pallet_id', 'like', $term)
                  ->orWhereHas('material', fn($mq) => $mq->where('item_description', 'like', $term));
            });
        }

        $pallets = $palletsQuery->get();
        $grouped = $pallets->groupBy('item_code');
        $now = now();
        $queue = [];

        foreach ($grouped as $itemCode => $items) {
            // Sort by arrival date ASC (Oldest first)
            $sorted = $items->sortBy(function ($p) {
                return $p->incomingHeader?->arrival_date ?? $p->created_at->format('Y-m-d');
            })->values();

            $first = $sorted->first();
            $firstRefDate = $first->incomingHeader?->arrival_date
                ? Carbon::parse($first->incomingHeader->arrival_date)
                : $first->created_at;
            $ageDays = max(0, (int) $firstRefDate->diffInDays($now));

            $materialName = $first->material?->item_description ?: 'Material ' . $itemCode;
            $totalStock = (float) $sorted->sum('current_qty');
            $palletCount = $sorted->count();

            // Next in line (Rank 2 & Rank 3)
            $nextPallets = $sorted->slice(1, 2)->map(function ($p) use ($now) {
                $ref = $p->incomingHeader?->arrival_date ? Carbon::parse($p->incomingHeader->arrival_date) : $p->created_at;
                return [
                    'pallet_id'     => $p->pallet_id,
                    'lot_no'        => $p->lot_no ?: '-',
                    'qty'           => (float) $p->current_qty,
                    'position_code' => $p->position?->position_code ?: 'Non-Rak',
                    'age_days'      => max(0, (int) $ref->diffInDays($now)),
                ];
            })->values()->toArray();

            $statusTier = 'FRESH';
            $statusColor = 'emerald';
            if ($ageDays > 90) {
                $statusTier = 'CRITICAL / STAGNANT';
                $statusColor = 'rose';
            } elseif ($ageDays > 60) {
                $statusTier = 'WARNING';
                $statusColor = 'amber';
            } elseif ($ageDays > 30) {
                $statusTier = 'NORMAL';
                $statusColor = 'blue';
            }

            $queue[] = [
                'item_code'        => $itemCode,
                'item_description' => $materialName,
                'total_stock_kg'   => $totalStock,
                'pallet_count'     => $palletCount,
                'uom'              => $first->uom ?: 'KG',
                'status_tier'      => $statusTier,
                'status_color'     => $statusColor,
                'rank_1'           => [
                    'pallet_id'     => $first->pallet_id,
                    'lot_no'        => $first->lot_no ?: '-',
                    'arrival_date'  => $firstRefDate->format('d/m/Y'),
                    'age_days'      => $ageDays,
                    'qty'           => (float) $first->current_qty,
                    'position_code' => $first->position?->position_code ?: 'Non-Rak',
                    'is_qc_hold'    => (bool) $first->is_qc_hold,
                ],
                'next_in_line'     => $nextPallets,
            ];
        }

        // Sort queue by oldest Rank 1 age descending (items that have been sitting the longest at the top)
        usort($queue, fn($a, $b) => $b['rank_1']['age_days'] <=> $a['rank_1']['age_days']);

        return array_slice($queue, 0, $limit);
    }

    /**
     * Get Daily Material Flow Throughput (Incoming vs Outgoing) for Chart.js
     */
    public function getThroughputTrend($whseId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $whseId = ($whseId && is_numeric($whseId)) ? (int) $whseId : null;

        $start = $fromDate ? Carbon::parse($fromDate) : now()->subDays(14);
        $end   = $toDate   ? Carbon::parse($toDate)   : now();

        if ($start->diffInDays($end) > 60) {
            $start = (clone $end)->subDays(60);
        }

        $dates = [];
        $curr = clone $start;
        while ($curr <= $end) {
            $dates[] = $curr->format('Y-m-d');
            $curr->addDay();
        }

        // Incoming volumes
        $incomingQuery = MwhPallet::query();
        if ($whseId) {
            $incomingQuery->where('mwh_pallets.whse_id', $whseId);
        }
        $incomings = $incomingQuery->whereDate('mwh_pallets.created_at', '>=', $start->format('Y-m-d'))
            ->whereDate('mwh_pallets.created_at', '<=', $end->format('Y-m-d'))
            ->selectRaw('DATE(mwh_pallets.created_at) as date_val, SUM(mwh_pallets.initial_qty) as total_in')
            ->groupBy('date_val')
            ->pluck('total_in', 'date_val')
            ->toArray();

        // Outgoing volumes
        $outgoingQuery = MwhOutgoing::query();
        if ($whseId) {
            $outgoingQuery->where('mwh_outgoings.whse_id', $whseId);
        }
        $outgoings = $outgoingQuery->whereDate('mwh_outgoings.outgoing_date', '>=', $start->format('Y-m-d'))
            ->whereDate('mwh_outgoings.outgoing_date', '<=', $end->format('Y-m-d'))
            ->selectRaw('DATE(mwh_outgoings.outgoing_date) as date_val, SUM(mwh_outgoings.qty_taken) as total_out')
            ->groupBy('date_val')
            ->pluck('total_out', 'date_val')
            ->toArray();

        $labels = [];
        $inData = [];
        $outData = [];

        foreach ($dates as $d) {
            $labels[]  = Carbon::parse($d)->format('d M');
            $inData[]  = round((float) ($incomings[$d] ?? 0), 1);
            $outData[] = round((float) ($outgoings[$d] ?? 0), 1);
        }

        return [
            'labels'   => $labels,
            'incoming' => $inData,
            'outgoing' => $outData,
        ];
    }
}
