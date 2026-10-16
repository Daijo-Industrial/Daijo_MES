<?php

namespace App\Http\Controllers;

use App\Models\SecondProcessReport;
use App\Models\SpProductionSession;
use App\Services\SecondProcessReportSyncBridge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SpProductionApprovalController extends Controller
{
    /**
     * Display a list of pending and completed session approvals with filters & summary KPIs.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pending');
        $search = $request->get('search');
        $line = $request->get('unit_line');
        $shift = $request->get('shift');
        $date = $request->get('date');

        $query = SpProductionSession::with([
            'workOrder',
            'operator',
            'approvedBy',
            'qcBypassedBy',
            'materials',
            'downtimeEntries',
        ])->where('status', 'completed');

        if ($tab === 'approved') {
            $query->whereNotNull('approved_at')->orderBy('approved_at', 'desc');
        } else {
            // Default to pending
            $query->whereNull('approved_at')->orderBy('finished_at', 'asc');
        }

        // Search by WO number, Part number, Part name, or Operator name
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('workOrder', function ($woQ) use ($search) {
                    $woQ->where('wo_number', 'like', "%{$search}%")
                        ->orWhere('part_number', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%");
                })->orWhereHas('operator', function ($opQ) use ($search) {
                    $opQ->where('name', 'like', "%{$search}%");
                });
            });
        }

        if (! empty($line)) {
            $query->where('unit_line', $line);
        }

        if (! empty($shift)) {
            $query->where('shift', $shift);
        }

        $tz = config('mes.timezone', 'Asia/Jakarta');

        if (! empty($date)) {
            $dateStartUtc = Carbon::parse($date, $tz)->startOfDay()->utc();
            $dateEndUtc = Carbon::parse($date, $tz)->endOfDay()->utc();
            $query->where(function ($q) use ($dateStartUtc, $dateEndUtc) {
                $q->whereBetween('finished_at', [$dateStartUtc, $dateEndUtc])
                    ->orWhereBetween('started_at', [$dateStartUtc, $dateEndUtc]);
            });
        }

        $sessions = $query->paginate(15)->withQueryString();

        // Summary KPI statistics
        $kpiPendingCount = SpProductionSession::where('status', 'completed')->whereNull('approved_at')->count();
        $todayStartUtc = Carbon::now($tz)->startOfDay()->utc();
        $todayEndUtc = Carbon::now($tz)->endOfDay()->utc();
        $kpiApprovedToday = SpProductionSession::whereNotNull('approved_at')
            ->whereBetween('approved_at', [$todayStartUtc, $todayEndUtc])
            ->count();
        $kpiPendingGood = (int) SpProductionSession::where('status', 'completed')->whereNull('approved_at')->sum('total_good');

        $pendingSessions = SpProductionSession::where('status', 'completed')->whereNull('approved_at')->get();
        $kpiAvgYield = $pendingSessions->isNotEmpty()
            ? round($pendingSessions->avg('yield'), 1)
            : 0;

        $spLines = config('mes.sp_lines', []);

        return view('sp_approvals.index', compact(
            'sessions',
            'tab',
            'kpiPendingCount',
            'kpiApprovedToday',
            'kpiPendingGood',
            'kpiAvgYield',
            'spLines',
            'search',
            'line',
            'shift',
            'date'
        ));
    }

    /**
     * Display the specified session for review with full close-out and operational data integration.
     */
    public function show(SpProductionSession $session, SecondProcessReportSyncBridge $bridge)
    {
        // Abort if not completed and not approved
        if ($session->status !== 'completed' && is_null($session->approved_at)) {
            abort(404, 'Only completed or approved sessions can be reviewed.');
        }

        $session->recalculateTotals();

        // Load all necessary relationships including closeout data
        $session->load([
            'workOrder',
            'operator',
            'approvedBy',
            'qcBypassedBy',
            'productionEntries' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'rejectEntries' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'downtimeEntries' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'reworkEntries' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'inputEntries' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'manpowerEntries',
            'materials',
        ]);

        // Reconciled mass balance metrics (aligned with closeout.blade.php)
        $finalScrap = max($session->total_reject, $session->total_scrap);
        $unusedWip = max(0, $session->total_input - ($session->total_good + $finalScrap));
        $directGood = (int) $session->productionEntries->sum('good_qty');

        // Total downtime duration
        $totalDowntimeMinutes = 0;
        foreach ($session->downtimeEntries as $dt) {
            if ($dt->duration_minutes) {
                $totalDowntimeMinutes += $dt->duration_minutes;
            } elseif ($dt->start_time && $dt->resume_time) {
                $start = Carbon::parse($dt->start_time);
                $resume = Carbon::parse($dt->resume_time);
                $totalDowntimeMinutes += $start->diffInMinutes($resume);
            }
        }

        // Defect Pareto / Breakdown summary
        $defectSummary = $session->rejectEntries
            ->groupBy('defect_type')
            ->map(function ($entries, $type) use ($finalScrap) {
                $totalQty = $entries->sum('quantity');
                $causes = $entries->pluck('cause')->filter()->unique()->implode(', ');
                $percentage = $finalScrap > 0 ? round(($totalQty / $finalScrap) * 100, 1) : 0;

                return [
                    'type' => $type,
                    'quantity' => $totalQty,
                    'percentage' => $percentage,
                    'causes' => $causes ?: '-',
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        // 8-Hour Production Progression anchored to shift schedule in config/mes.php
        $hourlyTable = $bridge->calculateHourlyProgression($session);

        // Rework performance summary
        $reworkStats = [
            'input' => (int) $session->total_rework_in,
            'recovered' => (int) $session->total_rework_recovered,
            'scrapped' => (int) $session->total_scrap,
            'pending' => max(0, $session->total_rework_in - ($session->total_rework_recovered + $session->total_scrap)),
            'recovery_rate' => $session->total_rework_in > 0 ? round(($session->total_rework_recovered / $session->total_rework_in) * 100, 1) : 0,
        ];

        // Synced legacy report reference (strictly 1-to-1)
        $syncedReport = $session->secondProcessReport;

        return view('sp_approvals.show', compact(
            'session',
            'finalScrap',
            'unusedWip',
            'directGood',
            'totalDowntimeMinutes',
            'defectSummary',
            'hourlyTable',
            'reworkStats',
            'syncedReport'
        ));
    }

    /**
     * Approve the completed session and sync to legacy SecondProcessReport.
     */
    public function approve(Request $request, SpProductionSession $session, SecondProcessReportSyncBridge $bridge)
    {
        if (Gate::allows('approve-sp-sessions') === false && ! Auth::user()?->hasRole('SUPER-ADMIN')) {
            abort(403, 'Unauthorized action. Only Supervisors and Admins can approve production sessions.');
        }

        if ($session->status !== 'completed' || $session->approved_at !== null) {
            return redirect()->back()->with('error', 'Session cannot be approved.');
        }

        $session->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Trigger Sync Bridge to legacy SecondProcessReport schema
        $bridge->syncSessionToLegacyReport($session);

        return redirect()->route('sp-approvals.index')->with(
            'success',
            "Production report for WO #{$session->workOrder?->wo_number} approved and synchronized successfully."
        );
    }

    /**
     * Reject the completed session and return it to the operator for correction.
     */
    public function reject(Request $request, SpProductionSession $session, SecondProcessReportSyncBridge $bridge)
    {
        if (Gate::allows('approve-sp-sessions') === false && ! Auth::user()?->hasRole('SUPER-ADMIN')) {
            abort(403, 'Unauthorized action. Only Supervisors and Admins can return production sessions.');
        }

        if ($session->status !== 'completed') {
            return redirect()->back()->with('error', 'Session cannot be returned.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $newRemarks = $session->remarks;
        if (! empty($validated['reason'])) {
            $supervisorName = Auth::user()?->name ?? 'Supervisor';
            $rejectionNote = "[Correction Requested by {$supervisorName} on " . now()->format('Y-m-d H:i') . ']: ' . $validated['reason'];
            $newRemarks = $newRemarks ? ($newRemarks . "\n" . $rejectionNote) : $rejectionNote;
        }

        // Return to 'running' so operator can edit on floor / closeout again
        $session->update([
            'status' => 'running',
            'finished_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'remarks' => $newRemarks,
        ]);

        // Revert legacy sync status
        $bridge->handleSessionReversion($session);

        return redirect()->route('sp-approvals.index')->with(
            'warning',
            "Session #{$session->id} (WO #{$session->workOrder?->wo_number}) has been returned to the operator for correction."
        );
    }
}
