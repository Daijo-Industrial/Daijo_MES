<?php

namespace App\Http\Controllers;

use App\Models\FirstPieceInspection;
use App\Models\SpDowntimeEntry;
use App\Models\SpProductionEntry;
use App\Models\SpProductionSession;
use App\Models\SpRejectEntry;
use App\Models\SpSessionManpower;
use App\Models\SpWorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SecondProcessDashboardController extends Controller
{
    /**
     * Display the real-time floor dashboard.
     */
    public function index(Request $request)
    {
        // 1. Lock to today date and active shift
        $now = Carbon::now('Asia/Jakarta');
        $date = $now->format('Y-m-d');
        $currentShift = $this->getCurrentShift($now);
        $shift = $request->input('shift', $currentShift);

        // Define all valid lines from config
        $lines = array_values(config('mes.sp_lines', []));

        // 2. Fetch sessions for today's date/shift + ALL currently running sessions
        $sessions = SpProductionSession::with(['workOrder', 'productionEntries', 'manpowerEntries'])
            ->where(function ($query) use ($date, $shift) {
                $query->where(function ($q) use ($date, $shift) {
                    $q->whereDate('started_at', $date);
                    if ((string) $shift !== 'all') {
                        $q->where('shift', $shift);
                    }
                })->orWhere('status', 'running');
            })
            ->get();

        // Key by unit_line, prioritizing running sessions over finished ones
        $reports = $sessions->sortBy(fn ($s) => $s->status === 'running' ? 2 : 1)->keyBy('unit_line');

        // 3. Fetch planned/active Work Orders for today (excluding unreleased drafts, prioritizing active over completed)
        $activeWorkOrders = SpWorkOrder::with(['sessions'])
            ->where(function ($query) use ($date) {
                $query->whereDate('planned_date', $date)
                    ->where(function ($query) {
                        $query->orWhereIn('status', ['planned', 'in_progress', 'completed']);
                    });
            })
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 1 WHEN status = 'planned' THEN 2 ELSE 3 END")
            ->orderBy('id', 'desc')
            ->get();

        $workOrdersByLine = $activeWorkOrders->keyBy('unit_line');

        // 4. Fetch First Piece Inspections for active part numbers (latest per part, date-agnostic)
        $activePartNumbers = $activeWorkOrders->pluck('part_number')->filter()->unique();
        $firstPieceInspections = FirstPieceInspection::whereIn('part_number', $activePartNumbers)
            ->latest('id')
            ->get()
            ->unique('part_number');
        $firstPieceMap = $firstPieceInspections->keyBy('part_number');

        // 5. Calculate real-time Shift KPIs & Pending QC Gate Count
        $runningSessionsCount = $sessions->where('status', 'running')->count();
        $pendingWoCount = $activeWorkOrders->where('status', 'planned')->count();
        $totalShiftGood = $sessions->sum('total_good');
        $totalShiftReject = $sessions->sum('total_reject');
        $totalShiftTotal = $totalShiftGood + $totalShiftReject;
        $overallNgRate = $totalShiftTotal > 0 ? round(($totalShiftReject / $totalShiftTotal) * 100, 1) : 0;

        $pendingQcGateCount = 0;
        $approvedFirstPieceCount = 0;
        foreach ($activeWorkOrders as $wo) {
            $fp = $firstPieceMap->get($wo->part_number);
            if ($fp && $fp->isApproved()) {
                $approvedFirstPieceCount++;
            } else {
                $pendingQcGateCount++;
            }
        }

        return view('second_process.dashboard', compact(
            'lines',
            'reports',
            'workOrdersByLine',
            'activeWorkOrders',
            'firstPieceMap',
            'date',
            'shift',
            'currentShift',
            'runningSessionsCount',
            'pendingWoCount',
            'totalShiftGood',
            'totalShiftReject',
            'overallNgRate',
            'approvedFirstPieceCount',
            'pendingQcGateCount'
        ));
    }

    /**
     * Display a dedicated single-line shop floor dashboard.
     */
    public function lineDashboard(Request $request, string $line)
    {
        $now = Carbon::now('Asia/Jakarta');
        $preset = $request->input('preset');

        if ($preset === 'yesterday') {
            $dateFrom = $now->copy()->subDay()->format('Y-m-d');
            $dateTo = $dateFrom;
        } elseif ($preset === 'last_7_days') {
            $dateFrom = $now->copy()->subDays(6)->format('Y-m-d');
            $dateTo = $now->format('Y-m-d');
        } elseif ($preset === 'this_month') {
            $dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $dateTo = $now->format('Y-m-d');
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->input('date_from', $now->format('Y-m-d'));
            $dateTo = $request->input('date_to', $dateFrom);
            $preset = 'custom';
        } else {
            $dateFrom = $request->input('date', $now->format('Y-m-d'));
            $dateTo = $dateFrom;
            $preset = 'today';
        }

        $shift = $request->input('shift', 'all');

        // Resolve slug -> display name from config (e.g. 'amplas' -> 'Area Amplas/Treatment')
        $spLines = config('mes.sp_lines', []);
        if (isset($spLines[$line])) {
            $line = $spLines[$line];
        } else {
            foreach ($spLines as $slug => $displayName) {
                if (strcasecmp($slug, $line) === 0 || strcasecmp($displayName, $line) === 0) {
                    $line = $displayName;
                    break;
                }
            }
        }

        $defaultLines = collect($spLines)->values();

        $dbLines = SpProductionSession::select('unit_line')
            ->distinct()->pluck('unit_line')
            ->merge(SpWorkOrder::select('unit_line')->distinct()->pluck('unit_line'));

        $knownLines = $defaultLines->merge($dbLines)->filter()->unique()->sort()->values();

        // 2. Query historical sessions matching the filter window
        $query = SpProductionSession::with([
            'workOrder', 'operator', 'productionEntries', 'rejectEntries',
            'reworkEntries', 'downtimeEntries', 'inputEntries', 'manpowerEntries',
        ])
            ->where('unit_line', $line)
            ->whereDate('started_at', '>=', $dateFrom)
            ->whereDate('started_at', '<=', $dateTo);

        if ((string) $shift !== 'all') {
            $query->where('shift', (int) $shift);
        }

        $sessions = $query->orderByDesc('started_at')->get();
        $sessionIds = $sessions->pluck('id');

        // === KPIs ===
        $totalGood = $sessions->sum('total_good');
        $totalReject = $sessions->sum('total_reject');
        $totalInput = $sessions->sum('total_input');
        $totalOutput = $totalGood + $totalReject;
        $yieldPct = $totalOutput > 0 ? round(($totalGood / $totalOutput) * 100, 1) : 0;
        $ngRate = $totalOutput > 0 ? round(($totalReject / $totalOutput) * 100, 1) : 0;
        $totalDowntimeMinutes = $sessions->flatMap->downtimeEntries->sum('duration_minutes');
        $totalReworkIn = $sessions->sum('total_rework_in');
        $totalReworkRecovered = $sessions->sum('total_rework_recovered');
        $totalScrap = $sessions->sum('total_scrap');
        $targetQty = $sessions->sum(fn ($s) => $s->workOrder?->target_qty ?? 0);

        // === Hourly Output (grouped by H:00 in Asia/Jakarta timezone) ===
        $hourlyRaw = SpProductionEntry::whereIn('session_id', $sessionIds)
            ->get()
            ->groupBy(fn ($e) => $e->recorded_at ? $e->recorded_at->setTimezone('Asia/Jakarta')->format('H:00') : '00:00');

        $hourlyOutput = [];
        foreach ($hourlyRaw as $hour => $entries) {
            $hourlyOutput[$hour] = [
                'good' => $entries->sum('good_qty'),
                'reject' => $entries->sum('reject_qty'),
            ];
        }
        ksort($hourlyOutput);

        // === Defect Pareto ===
        $defectPareto = SpRejectEntry::whereIn('session_id', $sessionIds)
            ->selectRaw('defect_type, SUM(quantity) as total')
            ->groupBy('defect_type')
            ->orderByDesc('total')
            ->get();

        // === Downtime Breakdown ===
        $downtimeBreakdown = SpDowntimeEntry::whereIn('session_id', $sessionIds)
            ->selectRaw('reason, SUM(duration_minutes) as total_minutes, COUNT(*) as occurrences')
            ->groupBy('reason')
            ->orderByDesc('total_minutes')
            ->get();

        // === Downtime Log (individual entries) ===
        $downtimeLog = SpDowntimeEntry::whereIn('session_id', $sessionIds)
            ->orderByDesc('start_time')
            ->get();

        // === Manpower Roster ===
        $manpower = SpSessionManpower::whereIn('session_id', $sessionIds)->get();

        // === Session History (last 20 sessions matching filter) ===
        $sessionHistory = $sessions->take(20);
        $date = $dateFrom; // For backwards compatibility

        return view('second_process.line_dashboard', compact(
            'line',
            'date',
            'dateFrom',
            'dateTo',
            'shift',
            'preset',
            'knownLines',
            'sessions',
            'totalGood',
            'totalReject',
            'totalInput',
            'yieldPct',
            'ngRate',
            'totalDowntimeMinutes',
            'totalReworkIn',
            'totalReworkRecovered',
            'totalScrap',
            'targetQty',
            'hourlyOutput',
            'defectPareto',
            'downtimeBreakdown',
            'downtimeLog',
            'manpower',
            'sessionHistory'
        ));
    }

    /**
     * Determine current shift based on configured schedule.
     */
    private function getCurrentShift(Carbon $now)
    {
        $shifts = config('mes.sp_shifts', config('mes.shifts', []));
        $currentTime = $now->format('H:i');

        foreach ($shifts as $shiftId => $schedule) {
            $start = $schedule['start'];
            $end = $schedule['end'];

            if ($start > $end) {
                // Crosses midnight (e.g. 23:30 to 07:30)
                if ($currentTime >= $start || $currentTime <= $end) {
                    return $shiftId;
                }
            } else {
                if ($currentTime >= $start && $currentTime <= $end) {
                    return $shiftId;
                }
            }
        }

        return 1; // Default to 1 if outside any schedule
    }
}
