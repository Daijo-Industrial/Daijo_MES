<?php

namespace App\Http\Controllers;

use App\Models\MasterBusinessPartner;
use App\Models\SecondProcessReport;
use App\Models\SecondProcessMaterial;
use App\Models\SecondProcessNgRecord;
use App\Models\SecondProcessTrouble;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecondProcessReportAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Date range default: Start of current month to today
        $dateFrom = $request->input('date_from', $now->copy()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', $now->format('Y-m-d'));

        // Base query for report header filtering
        $baseQuery = SecondProcessReport::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo);

        if ($request->filled('unit_line')) {
            $baseQuery->where('unit_line', $request->unit_line);
        }
        if ($request->filled('shift')) {
            $baseQuery->where('shift', $request->shift);
        }
        if ($request->filled('process_prod')) {
            $baseQuery->where('process_prod', $request->process_prod);
        }
        if ($request->filled('status')) {
            $baseQuery->where('status', $request->status);
        }

        // Industry / Sector Filter (Automotive, Electronics, Moulding, General)
        $selectedIndustry = $request->filled('industry') ? strtoupper(trim($request->input('industry'))) : null;
        if ($selectedIndustry && in_array($selectedIndustry, [
            MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
            MasterBusinessPartner::INDUSTRY_ELECTRONICS,
            MasterBusinessPartner::INDUSTRY_MOULDING,
            MasterBusinessPartner::INDUSTRY_GENERAL
        ], true)) {
            $customerNames = MasterBusinessPartner::where('industry', $selectedIndustry)->pluck('bp_name')->all();
            $baseQuery->whereIn('customer', $customerNames);
        }

        // CSV Export if requested
        if ($request->input('export') === 'csv') {
            return $this->exportCsv($baseQuery, $dateFrom, $dateTo);
        }

        if ($request->input('export') === 'materials') {
            return $this->exportMaterialsCsv($baseQuery, $dateFrom, $dateTo, $request->input('material_type'));
        }

        // 1. Summary KPIs
        $summary = (clone $baseQuery)->selectRaw('
            COUNT(*) as total_reports,
            COALESCE(SUM(target_per_hour * 8), 0) as total_target,
            COALESCE(SUM(jumlah_output), 0) as total_output,
            COALESCE(SUM(jumlah_ok), 0) as total_ok,
            COALESCE(SUM(jumlah_ng), 0) as total_ng,
            COALESCE(SUM(jml_input_wip), 0) as total_input_wip,
            COALESCE(SUM(repairan), 0) as total_repairan,
            COALESCE(SUM(jml_ng_lebur), 0) as total_scrap
        ')->first();

        $totalInput = (int) ($summary->total_input_wip + $summary->total_repairan);
        $yieldRate = $summary->total_output > 0
            ? round(($summary->total_ok / $summary->total_output) * 100, 2)
            : 0;

        $avgNgRate = $summary->total_output > 0
            ? round(($summary->total_ng / $summary->total_output) * 100, 2)
            : 0;

        $targetAchievementRate = $summary->total_target > 0
            ? round(($summary->total_output / $summary->total_target) * 100, 1)
            : null;

        $wipVariance = $totalInput - ((int) $summary->total_output + (int) $summary->total_scrap);

        $scrapRate = $totalInput > 0
            ? round(($summary->total_scrap / $totalInput) * 100, 2)
            : 0;

        // 2. Daily Output & NG Trend
        $dailyTrendRaw = (clone $baseQuery)
            ->select(DB::raw("date, SUM(jumlah_ok) as total_ok, SUM(jumlah_ng) as total_ng, SUM(jumlah_output) as total_output"))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dailyTrend = [
            'labels' => [],
            'ok' => [],
            'ng' => [],
            'ng_rate' => [],
        ];

        foreach ($dailyTrendRaw as $row) {
            $dailyTrend['labels'][] = Carbon::parse($row->date)->format('d M');
            $dailyTrend['ok'][] = (int) $row->total_ok;
            $dailyTrend['ng'][] = (int) $row->total_ng;
            $dailyTrend['ng_rate'][] = $row->total_output > 0
                ? round(($row->total_ng / $row->total_output) * 100, 2)
                : 0;
        }

        // 3. Output & NG by Line
        $byLineRaw = (clone $baseQuery)
            ->select(DB::raw("unit_line, SUM(jumlah_ok) as total_ok, SUM(jumlah_ng) as total_ng, SUM(jumlah_output) as total_output"))
            ->groupBy('unit_line')
            ->orderByDesc('total_output')
            ->get();

        $byLine = [
            'labels' => [],
            'ok' => [],
            'ng' => [],
            'ng_rate' => [],
        ];

        foreach ($byLineRaw as $row) {
            $byLine['labels'][] = $row->unit_line ?? 'Unknown';
            $byLine['ok'][] = (int) $row->total_ok;
            $byLine['ng'][] = (int) $row->total_ng;
            $byLine['ng_rate'][] = $row->total_output > 0
                ? round(($row->total_ng / $row->total_output) * 100, 2)
                : 0;
        }

        // Subquery for child table aggregations (avoids memory bloat and MySQL parameter limits)
        $reportIdsSubquery = (clone $baseQuery)->select('id');

        $selectedNgCategory = $request->filled('ng_category') ? trim($request->input('ng_category')) : null;

        // 4. Top NG Defects / Categories (Pareto Chart Data) with NG Remark Category filtering
        $ngRecords = SecondProcessNgRecord::whereIn('report_id', $reportIdsSubquery)
            ->where('total_ng', '>', 0)
            ->get(['id', 'report_id', 'ng_name', 'ng_category', 'total_ng', 'ng_input_item']);

        $categoryTotals = [];
        $defectTotals = [];

        foreach ($ngRecords as $rec) {
            $label = $rec->ng_name ?: ($rec->ng_category ?: 'Uncategorized');
            $allocations = $this->parseNgRemarkAllocations($rec->ng_input_item, (int) $rec->total_ng);

            foreach ($allocations as $cat => $qty) {
                $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $qty;
            }

            if ($selectedNgCategory) {
                $catQty = $allocations[$selectedNgCategory] ?? 0;
                if ($catQty > 0) {
                    $defectTotals[$label] = ($defectTotals[$label] ?? 0) + $catQty;
                }
            } else {
                $qty = (int) $rec->total_ng;
                if ($qty > 0) {
                    $defectTotals[$label] = ($defectTotals[$label] ?? 0) + $qty;
                }
            }
        }

        // Available NG Remark categories (preset first, then any extra discovered)
        $presetCategories = array_keys(config('mes.sp_ng_remark_categories', [
            'NG-INPUT' => 'NG-INPUT',
            'NG-PROSES' => 'NG-PROSES',
        ]));
        $extraCategories = array_diff(array_keys($categoryTotals), $presetCategories);
        sort($extraCategories);
        $ngCategories = array_values(array_unique(array_merge($presetCategories, $extraCategories)));

        // Distribution breakdown across all categories
        $categoryBreakdown = [];
        $totalAllCategoryNg = array_sum($categoryTotals);
        arsort($categoryTotals);
        foreach ($categoryTotals as $cat => $qty) {
            if ($qty > 0) {
                $categoryBreakdown[$cat] = [
                    'qty' => $qty,
                    'percentage' => $totalAllCategoryNg > 0 ? round(($qty / $totalAllCategoryNg) * 100, 1) : 0,
                ];
            }
        }

        // Top 10 Pareto Chart Data
        arsort($defectTotals);
        $topSlice = array_slice($defectTotals, 0, 10, true);
        $totalNgSum = array_sum($topSlice);

        $topNg = [
            'labels' => [],
            'values' => [],
            'cumulative_pct' => [],
        ];

        $runningSum = 0;
        foreach ($topSlice as $label => $val) {
            $runningSum += $val;
            $topNg['labels'][] = $label;
            $topNg['values'][] = (int) $val;
            $topNg['cumulative_pct'][] = $totalNgSum > 0 ? round(($runningSum / $totalNgSum) * 100, 1) : 0;
        }

        // 5. Output by Shift
        $byShiftRaw = (clone $baseQuery)
            ->select(DB::raw("shift, SUM(jumlah_output) as total_output, SUM(jumlah_ok) as total_ok, SUM(jumlah_ng) as total_ng"))
            ->groupBy('shift')
            ->orderBy('shift', 'asc')
            ->get();

        $byShift = [
            'labels' => [],
            'output' => [],
            'ok' => [],
            'ng' => [],
        ];

        foreach ($byShiftRaw as $row) {
            $byShift['labels'][] = 'Shift ' . $row->shift;
            $byShift['output'][] = (int) $row->total_output;
            $byShift['ok'][] = (int) $row->total_ok;
            $byShift['ng'][] = (int) $row->total_ng;
        }

        // 6. Downtime by Category & Total Loss Time
        $totalDowntimeMinutes = (int) SecondProcessTrouble::whereIn('report_id', $reportIdsSubquery)->sum('loss_time_minutes');
        $totalDowntimeHours = round($totalDowntimeMinutes / 60, 1);

        $downtimeRaw = SecondProcessTrouble::whereIn('report_id', $reportIdsSubquery)
            ->select(DB::raw("COALESCE(NULLIF(category, ''), NULLIF(penyebab, ''), 'Other') as cat_name, SUM(loss_time_minutes) as total_minutes"))
            ->groupBy('cat_name')
            ->orderByDesc('total_minutes')
            ->limit(10)
            ->get();

        $downtime = [
            'labels' => [],
            'minutes' => [],
        ];

        foreach ($downtimeRaw as $row) {
            $downtime['labels'][] = $row->cat_name;
            $downtime['minutes'][] = (int) $row->total_minutes;
        }

        // 7a. Top 5 Products by Output Volume
        $topProductsRaw = (clone $baseQuery)
            ->select(DB::raw("part_number, part_name, customer, SUM(jumlah_output) as total_output, SUM(jumlah_ok) as total_ok, SUM(jumlah_ng) as total_ng"))
            ->groupBy('part_number', 'part_name', 'customer')
            ->orderByDesc('total_output')
            ->limit(5)
            ->get();

        // 7b. Top 5 Products by NG Defects (High Risk / Quality Issues)
        $topDefectProductsQuery = (clone $baseQuery);
        if ($selectedNgCategory) {
            $topDefectProductsQuery->whereHas('ngRecords', function ($q) use ($selectedNgCategory) {
                $q->where('ng_input_item', 'LIKE', "%{$selectedNgCategory}%");
            });
        }
        $topDefectProductsRaw = $topDefectProductsQuery
            ->select(DB::raw("part_number, part_name, customer, SUM(jumlah_output) as total_output, SUM(jumlah_ok) as total_ok, SUM(jumlah_ng) as total_ng, SUM(jml_ng_lebur) as total_scrap"))
            ->groupBy('part_number', 'part_name', 'customer')
            ->having('total_ng', '>', 0)
            ->orderByDesc('total_ng')
            ->limit(5)
            ->get();

        // 8. Top Downtime Incidents & Countermeasures
        $topTroubles = SecondProcessTrouble::whereIn('report_id', $reportIdsSubquery)
            ->where(function ($q) {
                $q->whereNotNull('masalah')->where('masalah', '!=', '')
                  ->orWhereNotNull('penyebab')->where('penyebab', '!=', '')
                  ->orWhere('loss_time_minutes', '>', 0);
            })
            ->with(['report' => function ($q) {
                $q->select('id', 'date', 'unit_line', 'shift', 'part_number', 'part_name');
            }])
            ->orderByDesc('loss_time_minutes')
            ->limit(8)
            ->get();

        // 9. Materials Consumption Analytics (Paint & Part)
        $materialsBaseQuery = SecondProcessMaterial::query()
            ->join('second_process_reports', 'second_process_materials.report_id', '=', 'second_process_reports.id')
            ->whereIn('second_process_reports.id', (clone $baseQuery)->select('second_process_reports.id'));

        // A. Paint summary & top consumed paint items
        $topPaints = (clone $materialsBaseQuery)
            ->where('second_process_materials.type', 'paint')
            ->selectRaw('
                second_process_materials.item_name,
                second_process_materials.uom,
                SUM(second_process_materials.qty) as total_qty,
                COUNT(DISTINCT second_process_materials.report_id) as reports_count
            ')
            ->groupBy('second_process_materials.item_name', 'second_process_materials.uom')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $totalPaintQty = (float) (clone $materialsBaseQuery)
            ->where('second_process_materials.type', 'paint')
            ->sum('second_process_materials.qty');

        // Specific Paint Index: Paint Qty per 1,000 OK pieces across painting reports
        $totalOkInPainting = (int) (clone $baseQuery)
            ->where('process_prod', 'Painting')
            ->sum('jumlah_ok');
        $paintIndexPer1000 = ($totalOkInPainting > 0 && $totalPaintQty > 0)
            ? round(($totalPaintQty * 1000) / $totalOkInPainting, 2)
            : 0;

        // B. Part / WIP materials summary
        $topParts = (clone $materialsBaseQuery)
            ->where('second_process_materials.type', 'part')
            ->selectRaw('
                second_process_materials.item_name,
                second_process_materials.uom,
                SUM(second_process_materials.qty) as total_qty,
                COUNT(DISTINCT second_process_materials.report_id) as reports_count
            ')
            ->groupBy('second_process_materials.item_name', 'second_process_materials.uom')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $totalPartQty = (float) (clone $materialsBaseQuery)
            ->where('second_process_materials.type', 'part')
            ->sum('second_process_materials.qty');

        // Target NG Rate from configuration
        $targetNgRate = (float) config('mes.sp_target_ng_rate', 2.0);

        // Dropdown selection lists
        $lines = array_values(config('mes.sp_lines', []));
        $processes = config('mes.sp_processes', ['Painting', 'Buffing', 'Amplas', 'Treatment', 'Packing', 'Rework', 'Repair', 'Assy']);
        $industries = [
            'AUTOMOTIVE' => 'Automotive',
            'ELECTRONICS' => 'Electronics',
            'MOULDING' => 'Moulding',
            'GENERAL' => 'General',
        ];

        return view('second_process.report_analytics', compact(
            'summary',
            'avgNgRate',
            'yieldRate',
            'targetAchievementRate',
            'wipVariance',
            'scrapRate',
            'targetNgRate',
            'totalInput',
            'totalDowntimeMinutes',
            'totalDowntimeHours',
            'dailyTrend',
            'byLine',
            'topNg',
            'byShift',
            'downtime',
            'topProductsRaw',
            'topDefectProductsRaw',
            'topTroubles',
            'topPaints',
            'totalPaintQty',
            'paintIndexPer1000',
            'totalOkInPainting',
            'topParts',
            'totalPartQty',
            'dateFrom',
            'dateTo',
            'lines',
            'processes',
            'selectedNgCategory',
            'ngCategories',
            'categoryBreakdown',
            'selectedIndustry',
            'industries'
        ));
    }

    /**
     * Stream filtered Second Process reports data to CSV.
     */
    protected function exportCsv($query, string $dateFrom, string $dateTo)
    {
        $filename = 'second-process-analytics-' . $dateFrom . '-to-' . $dateTo . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Load Industry mapping for customers
            $industryMap = MasterBusinessPartner::pluck('industry', 'bp_name')->all();

            // CSV Column Headers
            fputcsv($handle, [
                'ID',
                'Date',
                'Unit / Line',
                'Shift',
                'Process',
                'Status',
                'Part Number',
                'Part Name',
                'Customer',
                'Industry',
                'Target / Hour',
                'Shift Target',
                'Input WIP',
                'Repairan',
                'Total Input',
                'Output Qty',
                'OK Qty',
                'NG Qty',
                'Yield (%)',
                'NG Rate (%)',
                'Scrap (Lebur)',
                'WIP Variance',
            ]);

            $query->orderBy('date', 'desc')
                ->orderBy('unit_line', 'asc')
                ->chunk(200, function ($reports) use ($handle, $industryMap) {
                    foreach ($reports as $r) {
                        $shiftTarget = ($r->target_per_hour ?? 0) * 8;
                        $totIn = (int) ($r->jml_input_wip + $r->repairan);
                        $totOut = (int) $r->jumlah_output;
                        $totScrap = (int) $r->jml_ng_lebur;
                        $wipVar = $totIn - ($totOut + $totScrap);
                        $yield = $totOut > 0 ? round(($r->jumlah_ok / $totOut) * 100, 2) : 0;
                        $ngRate = $totOut > 0 ? round(($r->jumlah_ng / $totOut) * 100, 2) : 0;

                        fputcsv($handle, [
                            $r->id,
                            $r->date,
                            $this->sanitizeCsvCell($r->unit_line),
                            $r->shift,
                            $this->sanitizeCsvCell($r->process_prod),
                            $this->sanitizeCsvCell($r->status),
                            $this->sanitizeCsvCell($r->part_number),
                            $this->sanitizeCsvCell($r->part_name),
                            $this->sanitizeCsvCell($r->customer),
                            $this->sanitizeCsvCell($industryMap[$r->customer] ?? 'GENERAL'),
                            $r->target_per_hour,
                            $shiftTarget,
                            $r->jml_input_wip,
                            $r->repairan,
                            $totIn,
                            $totOut,
                            $r->jumlah_ok,
                            $r->jumlah_ng,
                            $yield . '%',
                            $ngRate . '%',
                            $totScrap,
                            $wipVar,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Stream filtered Second Process materials data to CSV (part & paint types).
     */
    protected function exportMaterialsCsv($query, string $dateFrom, string $dateTo, ?string $materialType = null)
    {
        $typeLabel = $materialType ? strtolower($materialType) : 'all';
        $filename = 'second-process-materials-' . $typeLabel . '-' . $dateFrom . '-to-' . $dateTo . '.csv';

        return response()->streamDownload(function () use ($query, $materialType) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Column Headers
            fputcsv($handle, [
                'Report ID',
                'Date',
                'Unit / Line',
                'Shift',
                'Process',
                'Part Number',
                'Part Name',
                'Customer',
                'Report Output Qty',
                'Report OK Qty',
                'Report NG Qty',
                'Report Scrap Qty',
                'Material Type',
                'Material Item Name',
                'Lot Number',
                'Viscosity (s)',
                'Mixing Ratio',
                'Material Qty',
                'UOM',
                'Usage per OK Pc',
                'Consumption per 1000 Pcs',
                'Part Yield Rate (%)',
                'WIP Line Variance',
            ]);

            $materialsQuery = SecondProcessMaterial::query()
                ->join('second_process_reports', 'second_process_materials.report_id', '=', 'second_process_reports.id')
                ->whereIn('second_process_reports.id', (clone $query)->select('second_process_reports.id'))
                ->select([
                    'second_process_materials.*',
                    'second_process_reports.date as report_date',
                    'second_process_reports.unit_line as report_unit_line',
                    'second_process_reports.shift as report_shift',
                    'second_process_reports.process_prod as report_process_prod',
                    'second_process_reports.part_number as report_part_number',
                    'second_process_reports.part_name as report_part_name',
                    'second_process_reports.customer as report_customer',
                    'second_process_reports.jumlah_ok as report_jumlah_ok',
                    'second_process_reports.jumlah_output as report_jumlah_output',
                    'second_process_reports.jumlah_ng as report_jumlah_ng',
                    'second_process_reports.jml_input_wip as report_jml_input_wip',
                    'second_process_reports.repairan as report_repairan',
                    'second_process_reports.jml_ng_lebur as report_jml_ng_lebur',
                ])
                ->orderBy('second_process_reports.date', 'desc')
                ->orderBy('second_process_reports.id', 'desc');

            if (!empty($materialType) && in_array(strtolower($materialType), ['part', 'paint'])) {
                $materialsQuery->where('second_process_materials.type', strtolower($materialType));
            }

            $materialsQuery->chunk(300, function ($rows) use ($handle) {
                foreach ($rows as $r) {
                    $matType = strtolower($r->type ?? '');
                    $okQty = (int) ($r->report_jumlah_ok ?? 0);
                    $matQty = (float) ($r->qty ?? 0);
                    $totIn = (int) (($r->report_jml_input_wip ?? 0) + ($r->report_repairan ?? 0));
                    $totOut = (int) ($r->report_jumlah_output ?? 0);
                    $totScrap = (int) ($r->report_jml_ng_lebur ?? 0);
                    $wipVariance = $totIn - ($totOut + $totScrap);

                    // Calculated Metrics
                    $usagePerOk = ($okQty > 0 && $matQty > 0) ? round($matQty / $okQty, 4) : '-';
                    $usagePer1000 = ($matType === 'paint' && $okQty > 0 && $matQty > 0)
                        ? round(($matQty * 1000) / $okQty, 2)
                        : '-';
                    $partYield = ($matType === 'part' && $matQty > 0 && $okQty > 0)
                        ? round(($okQty / $matQty) * 100, 2) . '%'
                        : '-';

                    fputcsv($handle, [
                        $r->report_id,
                        $r->report_date,
                        $this->sanitizeCsvCell($r->report_unit_line),
                        $r->report_shift,
                        $this->sanitizeCsvCell($r->report_process_prod),
                        $this->sanitizeCsvCell($r->report_part_number),
                        $this->sanitizeCsvCell($r->report_part_name),
                        $this->sanitizeCsvCell($r->report_customer),
                        $r->report_jumlah_output,
                        $r->report_jumlah_ok,
                        $r->report_jumlah_ng,
                        $r->report_jml_ng_lebur,
                        $this->sanitizeCsvCell($r->type),
                        $this->sanitizeCsvCell($r->item_name),
                        $this->sanitizeCsvCell($r->lot_number),
                        $this->sanitizeCsvCell($r->visco),
                        $this->sanitizeCsvCell($r->mixing_ratio),
                        $r->qty,
                        $this->sanitizeCsvCell($r->uom),
                        $usagePerOk,
                        $usagePer1000,
                        $partYield,
                        $matType === 'part' ? $wipVariance : '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Prevent CSV Formula Injection by prefixing formula characters.
     */
    protected function sanitizeCsvCell($value): string
    {
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Parse serialized NG remark item allocations into an associative array of category => qty.
     * Handles formats: "[3] NG-INPUT | [2] NG-PROSES", "[5] NG-INPUT", "NG-INPUT", etc.
     */
    protected function parseNgRemarkAllocations(?string $ngInputItem, int $totalNg): array
    {
        $allocations = [];
        if (empty($ngInputItem)) {
            if ($totalNg > 0) {
                $allocations['UNCATEGORIZED'] = $totalNg;
            }
            return $allocations;
        }

        $parts = explode('|', $ngInputItem);
        $parsedSum = 0;
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $qty = 0;
            $name = $part;
            if (preg_match('/^\[(\d+)\]\s*(.*)$/', $part, $matches)) {
                $qty = (int) $matches[1];
                $name = trim($matches[2]);
            }

            // Normalize category name
            $cleanName = strtoupper(preg_replace('/[\s_]+/', '-', trim($name)));
            if ($cleanName === 'INPUT') {
                $cleanName = 'NG-INPUT';
            } elseif ($cleanName === 'PROSES') {
                $cleanName = 'NG-PROSES';
            }

            if ($cleanName !== '') {
                // If unbracketed legacy format (e.g. single "NG-INPUT"), fallback to totalNg
                if ($qty === 0 && count($parts) === 1) {
                    $qty = $totalNg;
                }
                $allocations[$cleanName] = ($allocations[$cleanName] ?? 0) + $qty;
                $parsedSum += $qty;
            }
        }

        if (empty($allocations) && $totalNg > 0) {
            $allocations['UNCATEGORIZED'] = $totalNg;
        }

        return $allocations;
    }
}
