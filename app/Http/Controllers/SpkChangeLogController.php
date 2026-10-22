<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\SpkChangeLog;
use App\Models\SpkMaster;
use App\Services\SpkMasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpkChangeLogController extends Controller
{
    public function index(Request $request)
    {
        // 1. Fetch Change Logs
        $logQuery = SpkChangeLog::with('masterItem')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $logQuery->where(function ($q) use ($search) {
                $q->where('spk_number', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('change_type')) {
            if ($request->change_type === 'PLANNED_QTY_CHANGE') {
                $logQuery->where('change_type', 'QTY_CHANGE')
                    ->whereNotNull('old_planned_qty')
                    ->whereNotNull('new_planned_qty')
                    ->whereColumn('old_planned_qty', '!=', 'new_planned_qty');
            } elseif ($request->change_type === 'COMPLETED_QTY_CHANGE') {
                $logQuery->where('change_type', 'QTY_CHANGE')
                    ->whereNotNull('old_completed_qty')
                    ->whereNotNull('new_completed_qty')
                    ->whereColumn('old_completed_qty', '!=', 'new_completed_qty');
            } else {
                $logQuery->where('change_type', $request->change_type);
            }
        }

        if ($request->filled('batch_id')) {
            $logQuery->where('sync_batch_id', $request->batch_id);
        }

        if ($request->filled('date')) {
            $logQuery->whereDate('created_at', $request->date);
        }

        $logs = $logQuery->paginate(30, ['*'], 'log_page')->withQueryString();

        // 2. Fetch Master SPK List
        $masterQuery = SpkMaster::with('masterItem');
        if ($request->filled('search')) {
            $search = trim($request->search);
            $masterQuery->where(function ($q) use ($search) {
                $q->where('spk_number', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%")
                    ->orWhere('production_status', 'like', "%{$search}%");
            });
        }
        $masterSpks = $masterQuery->paginate(25, ['*'], 'master_page')->withQueryString();

        // 3. Batches for filter
        $batches = SpkChangeLog::select('sync_batch_id', DB::raw('MAX(created_at) as created_at'))
            ->groupBy('sync_batch_id')
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        // 4. Last Sync info from ApiLog or SpkChangeLog
        $lastApiLog = ApiLog::where('api_name', 'SPK_SYNC')->latest()->first();
        $lastSyncTime = $lastApiLog ? $lastApiLog->created_at : SpkChangeLog::latest()->value('created_at');
        $lastSyncStatus = $lastApiLog ? $lastApiLog->status : 'N/A';
        $lastSyncMessage = $lastApiLog ? $lastApiLog->message : 'Belum ada log sync';

        // 5. Summary Statistics
        $stats = [
            'total_master_spk' => SpkMaster::count(),
            'total_changes' => SpkChangeLog::count(),
            'total_new' => SpkChangeLog::where('change_type', 'NEW')->count(),
            'total_qty_change' => SpkChangeLog::where('change_type', 'QTY_CHANGE')->count(),
            'total_qty_planned' => SpkChangeLog::where('change_type', 'QTY_CHANGE')
                ->whereNotNull('old_planned_qty')
                ->whereNotNull('new_planned_qty')
                ->whereColumn('old_planned_qty', '!=', 'new_planned_qty')
                ->count(),
            'total_qty_completed' => SpkChangeLog::where('change_type', 'QTY_CHANGE')
                ->whereNotNull('old_completed_qty')
                ->whereNotNull('new_completed_qty')
                ->whereColumn('old_completed_qty', '!=', 'new_completed_qty')
                ->count(),
            'total_removed' => SpkChangeLog::where('change_type', 'REMOVED')->count(),
            'last_sync' => $lastSyncTime,
            'last_sync_status' => $lastSyncStatus,
            'last_sync_message' => $lastSyncMessage,
        ];

        return view('spk.changes-index', compact('logs', 'masterSpks', 'batches', 'stats'));
    }

    public function getHistory($spkNumber)
    {
        $history = SpkChangeLog::with('masterItem')
            ->where('spk_number', $spkNumber)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'spk_number' => $spkNumber,
            'history' => $history,
        ]);
    }

    public function triggerSync(SpkMasterService $spkService, Request $request)
    {
        try {
            $result = $spkService->SyncData();

            if ($request->ajax()) {
                return $result;
            }

            return redirect()->route('spk.changes.index')->with('success', 'Sinkronisasi SPK berhasil dijalankan!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->route('spk.changes.index')->with('error', 'Gagal sinkron SPK: ' . $e->getMessage());
        }
    }
}
