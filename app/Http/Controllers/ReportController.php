<?php

namespace App\Http\Controllers;

use App\Exports\DailyReportExport;
use App\Exports\MonthlyReportExport;
use App\Exports\WeeklyReportExport;
use App\Models\Asakai;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // ============================================
    // DAILY REPORT EXPORT
    // ============================================
    public function exportDaily(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $customer = $request->get('customer', '');
        $part = $request->get('part', '');
        $shift = $request->get('shift', '');
        $type = $request->get('type', 'excel');

        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions'])
            ->daily($date)
            ->when($customer, fn ($q) => $q->where('customer', 'like', "%{$customer}%"))
            ->when($part, fn ($q) => $q->where('part_no', 'like', "%{$part}%"))
            ->when($shift, fn ($q) => $q->where('lot_shift', $shift))
            ->get();

        $filename = 'Daily_Report_' . Carbon::parse($date)->format('d-M-Y');

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('exports.daily-report-pdf', [
                'asakais' => $asakais,
                'date' => Carbon::parse($date)->format('d M Y'),
                'total' => $asakais->count(),
                'totalQuantity' => $asakais->sum('quantity'),
            ]);

            return $pdf->download($filename . '.pdf');
        }

        // Excel Export
        return Excel::download(new DailyReportExport($asakais, $date), $filename . '.xlsx');
    }

    // ============================================
    // WEEKLY REPORT EXPORT
    // ============================================
    public function exportWeekly(Request $request)
    {
        $weekStart = $request->get('week_start', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $weekEnd = $request->get('week_end', Carbon::now()->endOfWeek()->format('Y-m-d'));
        $customer = $request->get('customer', '');
        $shift = $request->get('shift', '');
        $type = $request->get('type', 'excel');

        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions'])
            ->weekly(Carbon::parse($weekStart), Carbon::parse($weekEnd))
            ->when($customer, fn ($q) => $q->where('customer', 'like', "%{$customer}%"))
            ->when($shift, fn ($q) => $q->where('lot_shift', $shift))
            ->get();

        $filename = 'Weekly_Report_' . Carbon::parse($weekStart)->format('d-M') . '_to_' . Carbon::parse($weekEnd)->format('d-M-Y');

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('exports.weekly-report-pdf', [
                'asakais' => $asakais,
                'weekStart' => Carbon::parse($weekStart)->format('d M Y'),
                'weekEnd' => Carbon::parse($weekEnd)->format('d M Y'),
                'total' => $asakais->count(),
            ]);

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new WeeklyReportExport($asakais, $weekStart, $weekEnd), $filename . '.xlsx');
    }

    // ============================================
    // MONTHLY REPORT EXPORT
    // ============================================
    public function exportMonthly(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $customer = $request->get('customer', '');
        $type = $request->get('type', 'excel');

        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions'])
            ->monthly($year, $month)
            ->when($customer, fn ($q) => $q->where('customer', 'like', "%{$customer}%"))
            ->get();

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F_Y');
        $filename = 'Monthly_Report_' . $monthName;

        if ($type === 'pdf') {
            $byCustomer = $asakais->groupBy('customer')->map(fn ($group) => [
                'count' => $group->count(),
                'quantity' => $group->sum('quantity'),
            ]);

            $pdf = Pdf::loadView('exports.monthly-report-pdf', [
                'asakais' => $asakais,
                'monthName' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
                'total' => $asakais->count(),
                'totalQuantity' => $asakais->sum('quantity'),
                'byCustomer' => $byCustomer,
            ]);

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new MonthlyReportExport($asakais, $year, $month), $filename . '.xlsx');
    }
}
