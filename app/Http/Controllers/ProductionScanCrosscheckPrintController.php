<?php

namespace App\Http\Controllers;

use App\Models\StoreCrosscheckDailyReport;
use App\Services\StoreCrosscheckService;
use Illuminate\Http\Request;

class ProductionScanCrosscheckPrintController extends Controller
{
    public function print(Request $request, StoreCrosscheckService $service)
    {
        $selectedDate = $request->query('date', $service->getCurrentProductionDate());
        $plant = strtoupper($request->query('plant', 'KBN'));
        $shift = $request->query('shift', 'ALL');
        $status = $request->query('status', 'ALL');
        $machine = $request->query('machine', 'ALL');

        $metrics = $service->getSummaryMetrics($selectedDate, $plant);

        $items = $service->getBaseQuery($selectedDate, $plant, $shift, $status, '', $machine)
            ->orderBy('production_scanned_data.created_at', 'asc')
            ->get();

        $snapshot = StoreCrosscheckDailyReport::with('verifier')
            ->where('report_date', $selectedDate)
            ->where('plant', in_array($plant, ['KBN', 'KARAWANG']) ? $plant : 'KBN')
            ->first();

        [$startTime, $endTime] = $service->getTimeWindow($selectedDate);

        return view('store.production_scan_crosscheck_print', [
            'selectedDate' => $selectedDate,
            'plant' => $plant,
            'shift' => $shift,
            'status' => $status,
            'metrics' => $metrics,
            'items' => $items,
            'snapshot' => $snapshot,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]);
    }
}
