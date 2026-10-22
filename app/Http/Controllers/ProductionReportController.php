<?php

namespace App\Http\Controllers;

use App\Models\DailyItemCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductionReportController extends Controller
{
    public function index(Request $request)
    {
        // User bisa pilih tanggal, default hari ini
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        // $date = '2025-10-30';
        // Ambil semua data DailyItemCodes + relasinya
        $dailyData = DailyItemCode::with([
            'hourlyRemarks',
            'hourlyRemarks.ngDetails',
            'hourlyRemarks.ngDetails.ngType',
            'user',
            'masterItem',
            'masterFg',
        ])
            ->whereDate('start_date', $date)
            ->whereHas('hourlyRemarks')
            ->get();

        // Group berdasarkan item agar tidak dobel walaupun beda shift
        $grouped = $dailyData->groupBy('masterItem.item_code');

        $result = [];

        foreach ($grouped as $itemCode => $records) {
            $first = $records->first();

            $itemSummary = [
                'item_code' => $itemCode,
                'item_name' => optional($first->masterItem)->item_name,
                'cycletime' => $first->temporal_cycle_time ?? '-',
                'sap_cycletime' => optional($first->masterFg)->cycle_time
                                        ? $first->masterFg->cycle_time * 60
                                        : null,
                'cavity' => optional($first->masterItem)->cavity,
                'machine' => optional($first->user)->name,
                'shifts' => [
                    1 => null,
                    2 => null,
                    3 => null,
                ],
                'total_actual' => 0,
                'total_ng' => 0,
                'reject_rate' => 0,
                'pair' => null,
            ];

            // Cek kolom pair di masterItem
            if (! empty(optional($first->masterItem)->pair)) {
                $itemSummary['pair'] = optional($first->masterItem)->pair;
            }

            foreach ($records as $record) {

                $shift = (int) $record->shift;

                $shiftActual = $record->hourlyRemarks->sum('actual_production');
                $shiftNG = $record->hourlyRemarks->sum('NG');

                // Ambil detail NG per type
                $ngDetails = [];
                foreach ($record->hourlyRemarks as $remark) {
                    foreach ($remark->ngDetails as $detail) {
                        $ngTypeName = optional($detail->ngType)->ng_type ?? 'Unknown';
                        if (! isset($ngDetails[$ngTypeName])) {
                            $ngDetails[$ngTypeName] = 0;
                        }
                        $ngDetails[$ngTypeName] += $detail->ng_quantity; // asumsi ada kolom quantity
                    }
                }

                $itemSummary['shifts'][$shift] = [
                    'user' => optional($record->user)->name,
                    'total_actual' => $shiftActual,
                    'total_ng' => $shiftNG,
                    'remarks' => $record->hourlyRemarks->pluck('remark')->filter()->values(),
                    'ng_details' => $ngDetails, // <-- simpan detail NG per type
                ];

                $itemSummary['total_actual'] += $shiftActual;
                $itemSummary['total_ng'] += $shiftNG;
            }

            if ($itemSummary['total_actual'] + $itemSummary['total_ng'] > 0) {
                $itemSummary['reject_rate'] =
                    round(($itemSummary['total_ng'] / ($itemSummary['total_actual'] + $itemSummary['total_ng'])) * 100, 2);
            } else {
                $itemSummary['reject_rate'] = 0;
            }

            $result[] = $itemSummary;
        }
        $result = collect($result)->sortBy('machine')->values()->all();

        // dd($result);
        return view('reports-production-daily', [
            'date' => $date,
            'data' => $result,
        ]);
    }
}
