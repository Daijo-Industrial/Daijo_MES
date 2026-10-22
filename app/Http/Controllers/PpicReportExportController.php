<?php

namespace App\Http\Controllers;

use App\Exports\PpicDailyProductionExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PpicReportExportController extends Controller
{
    /**
     * Export PPIC Daily Production Report in Excel Format
     */
    public function exportDailyProduction(Request $request)
    {
        $date = $request->input('date', Carbon::today('Asia/Jakarta')->format('Y-m-d'));
        $machineId = $request->input('machine_id');

        $formattedDate = Carbon::parse($date)->format('Ymd');
        $fileName = "Laporan_Produksi_Harian_PPIC_{$formattedDate}.xlsx";

        // Export ALL machines for the selected date as requested by PPIC
        return Excel::download(
            new PpicDailyProductionExport($date, null),
            $fileName
        );
    }
}
