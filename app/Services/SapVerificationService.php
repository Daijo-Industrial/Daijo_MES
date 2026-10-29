<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SapVerificationService
{
    /**
     * Parse flexible date representations from SAP Excel exports (e.g. '29.10.26', '29/10/2026', Excel serial number, etc.)
     */
    public function parseSapDate($val): ?string
    {
        if (empty($val)) {
            return null;
        }

        if (is_numeric($val) && $val > 20000 && $val < 60000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $val))->format('Y-m-d');
            } catch (\Throwable $e) {
                // fallback
            }
        }

        $str = trim((string) $val);

        // Pattern dd.mm.yy (e.g. 29.10.26 -> 2026-10-29)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $str, $m)) {
            $year = 2000 + (int) $m[3];
            return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
        }

        // Pattern dd.mm.yyyy (e.g. 29.10.2026 -> 2026-10-29)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Pattern dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Pattern dd/mm/yy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $str, $m)) {
            $year = 2000 + (int) $m[3];
            return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Clean numeric quantity from string/cell value
     */
    public function cleanQuantity($val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        $str = trim((string) $val);
        // Remove thousand separators like '1,416.00'
        $cleaned = str_replace(',', '', $str);

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Extract SPK number from SAP Remarks field (e.g. 'Created from SPK 26030500' -> '26030500')
     */
    public function extractSpkFromRemarks(?string $remarks): ?string
    {
        if (empty($remarks)) {
            return null;
        }

        // Match 'Created from SPK 26030500' or 'SPK 26030500' or 'SPK: 26030500'
        if (preg_match('/(?:SPK\s*[:#-]?\s*|Created from SPK\s*)([A-Za-z0-9\-]+)/i', $remarks, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: look for 7-9 digit SPK code typically starting with '26' or digits
        if (preg_match('/\b(26\d{6,7})\b/', $remarks, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract Pallet ID from SAP Remarks field (e.g. 'Inventory Transfer created from PLT-20261012-0004' -> 'PLT-20261012-0004')
     */
    public function extractPalletFromRemarks(?string $remarks): ?string
    {
        if (empty($remarks)) {
            return null;
        }

        if (preg_match('/(PLT-[A-Za-z0-9\-]+)/i', $remarks, $matches)) {
            return strtoupper(trim($matches[1]));
        }

        return null;
    }

    /**
     * Read an uploaded spreadsheet into raw 2D array of rows
     */
    public function readSpreadsheetRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            // Skip completely empty rows
            $hasContent = false;
            foreach ($rowData as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Parse File 1: Receipt from Production (FFI / KRFFI)
     */
    public function parseReceiptFile(string $filePath): array
    {
        $rows = $this->readSpreadsheetRows($filePath);
        if (empty($rows)) {
            return ['rows' => [], 'min_date' => null, 'max_date' => null, 'total_sap_qty' => 0];
        }

        // Find header row (search first 5 rows for 'Document Number' or 'Item No' or 'Remarks')
        $headerIndex = 0;
        $colMap = [
            'doc_num' => null,
            'post_date' => null,
            'value_date' => null,
            'item_code' => null,
            'description' => null,
            'quantity' => null,
            'warehouse' => null,
            'remarks' => null,
        ];

        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];
            foreach ($row as $colIdx => $headerVal) {
                $cleaned = strtolower(trim((string) $headerVal));
                if (str_contains($cleaned, 'document') && str_contains($cleaned, 'number') || $cleaned === 'docnum') {
                    $colMap['doc_num'] = $colIdx;
                } elseif (str_contains($cleaned, 'posting') && str_contains($cleaned, 'date') || $cleaned === 'docdate') {
                    $colMap['post_date'] = $colIdx;
                } elseif (str_contains($cleaned, 'value') && str_contains($cleaned, 'date')) {
                    $colMap['value_date'] = $colIdx;
                } elseif (str_contains($cleaned, 'item') && (str_contains($cleaned, 'no') || str_contains($cleaned, 'code'))) {
                    $colMap['item_code'] = $colIdx;
                } elseif (str_contains($cleaned, 'description')) {
                    $colMap['description'] = $colIdx;
                } elseif (str_contains($cleaned, 'quantity') || $cleaned === 'qty') {
                    $colMap['quantity'] = $colIdx;
                } elseif (str_contains($cleaned, 'warehouse')) {
                    $colMap['warehouse'] = $colIdx;
                } elseif (str_contains($cleaned, 'remark') || str_contains($cleaned, 'comment')) {
                    $colMap['remarks'] = $colIdx;
                }
            }

            if ($colMap['doc_num'] !== null && $colMap['remarks'] !== null) {
                $headerIndex = $i;
                break;
            }
        }

        // Fallback default column indexes if header not recognized (based on Image 1)
        // A:#, B:Document Number, C:Posting Date, D:Value Date, E:Item No., F:Item Description, G:Quantity, H:Warehouse Code, I:Remarks
        if ($colMap['doc_num'] === null) {
            $colMap = [
                'doc_num' => 1,
                'post_date' => 2,
                'value_date' => 3,
                'item_code' => 4,
                'description' => 5,
                'quantity' => 6,
                'warehouse' => 7,
                'remarks' => 8,
            ];
            $headerIndex = 0;
        }

        $parsedRows = [];
        $dates = [];
        $totalSapQty = 0;

        for ($r = $headerIndex + 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            $docNum = trim((string) ($row[$colMap['doc_num']] ?? ''));
            $remarks = trim((string) ($row[$colMap['remarks']] ?? ''));
            $itemCode = trim((string) ($row[$colMap['item_code']] ?? ''));
            $desc = trim((string) ($row[$colMap['description']] ?? ''));
            $qty = $this->cleanQuantity($row[$colMap['quantity']] ?? 0);
            $whse = trim((string) ($row[$colMap['warehouse']] ?? 'FFI'));
            $postDate = $this->parseSapDate($row[$colMap['post_date']] ?? null);

            // Skip header repeated or empty row
            if (empty($docNum) && empty($remarks)) {
                continue;
            }
            if (strtolower($docNum) === 'document number' || strtolower($remarks) === 'remarks') {
                continue;
            }

            $spk = $this->extractSpkFromRemarks($remarks);

            if ($postDate) {
                $dates[] = $postDate;
            }
            $totalSapQty += $qty;

            $parsedRows[] = [
                'doc_num' => $docNum,
                'posting_date' => $postDate,
                'spk_code' => $spk,
                'item_code' => $itemCode,
                'description' => $desc,
                'quantity' => $qty,
                'warehouse' => $whse,
                'remarks' => $remarks,
            ];
        }

        sort($dates);
        $minDate = ! empty($dates) ? $dates[0] : null;
        $maxDate = ! empty($dates) ? end($dates) : null;

        return [
            'rows' => $parsedRows,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'total_sap_qty' => $totalSapQty,
            'total_rows' => count($parsedRows),
        ];
    }

    /**
     * Reconcile Receipt from Production (SAP rows vs MES production_summary)
     */
    public function reconcileReceiptData(array $sapRows, ?string $filterStartDate = null, ?string $filterEndDate = null): array
    {
        // 1. Group SAP rows by SPK
        $sapBySpk = [];
        foreach ($sapRows as $r) {
            $spk = $r['spk_code'] ?: 'NO_SPK_' . $r['doc_num'];
            if (! isset($sapBySpk[$spk])) {
                $sapBySpk[$spk] = [
                    'spk_code' => $r['spk_code'],
                    'item_code' => $r['item_code'],
                    'description' => $r['description'],
                    'warehouse' => $r['warehouse'],
                    'sap_qty' => 0,
                    'sap_docs' => [],
                    'sap_dates' => [],
                    'sap_lines' => [],
                ];
            }

            $sapBySpk[$spk]['sap_qty'] += $r['quantity'];
            if (! in_array($r['doc_num'], $sapBySpk[$spk]['sap_docs'])) {
                $sapBySpk[$spk]['sap_docs'][] = $r['doc_num'];
            }
            if ($r['posting_date'] && ! in_array($r['posting_date'], $sapBySpk[$spk]['sap_dates'])) {
                $sapBySpk[$spk]['sap_dates'][] = $r['posting_date'];
            }
            $sapBySpk[$spk]['sap_lines'][] = $r;
        }

        // 2. Query MES data for all identified SPKs
        $allSpkCodes = array_values(array_filter(array_keys($sapBySpk), fn ($k) => ! str_starts_with($k, 'NO_SPK_')));

        // MES Summary query for SPKs present in SAP
        $mesSummaries = DB::table('production_summary')
            ->whereIn('spk_code', $allSpkCodes)
            ->select('spk_code', 'warehouse', 'created_date', 'total_quantity', 'sap_sent', 'sap_sent_at', 'id')
            ->get()
            ->groupBy('spk_code');

        // Also query MES SPKs within the specified date range to find SPKs that are NOT in SAP yet (Missing in SAP)
        $mesMissingQuery = DB::table('production_summary')
            ->whereIn('warehouse', ['FFI', 'KRFFI']);

        if ($filterStartDate && $filterEndDate) {
            $mesMissingQuery->whereBetween('created_date', [$filterStartDate, $filterEndDate]);
        } elseif ($filterStartDate) {
            $mesMissingQuery->where('created_date', '>=', $filterStartDate);
        }

        $mesInRange = $mesMissingQuery
            ->select('spk_code', 'warehouse', 'created_date', 'total_quantity', 'sap_sent', 'sap_sent_at', 'id')
            ->get()
            ->groupBy('spk_code');

        // Get SPK master info (item_code, planned_quantity, completed_quantity)
        $combinedSpks = array_unique(array_merge($allSpkCodes, $mesInRange->keys()->toArray()));
        $spkMasters = DB::table('spk_masters')
            ->whereIn('spk_number', $combinedSpks)
            ->select('spk_number', 'item_code', 'planned_quantity', 'completed_quantity', 'warehouse')
            ->get()
            ->keyBy('spk_number');

        // Scanned data item codes map fallback
        $scannedItemCodes = DB::table('production_scanned_data')
            ->whereIn('spk_code', $combinedSpks)
            ->groupBy('spk_code')
            ->select('spk_code', DB::raw('MIN(item_code) as item_code'))
            ->pluck('item_code', 'spk_code')
            ->toArray();

        // 3. Build Comparison Rows
        $comparison = [];
        $processedSpks = [];

        // A. Process all SAP entries
        foreach ($sapBySpk as $spkKey => $sapData) {
            $spk = $sapData['spk_code'];
            $processedSpks[$spkKey] = true;

            $mesRows = $spk ? ($mesSummaries->get($spk) ?? collect()) : collect();
            $mesQty = (float) $mesRows->sum('total_quantity');
            $sapQty = (float) $sapData['sap_qty'];
            $diff = $mesQty - $sapQty;

            $spkMaster = $spk ? $spkMasters->get($spk) : null;
            $itemCode = $sapData['item_code'] ?: ($spkMaster->item_code ?? ($scannedItemCodes[$spk] ?? '—'));
            $mesWarehouse = $mesRows->first()?->warehouse ?? ($spkMaster->warehouse ?? $sapData['warehouse']);

            $mesSapSent = $mesRows->pluck('sap_sent')->unique()->toArray();
            $mesDates = $mesRows->pluck('created_date')->unique()->values()->toArray();

            // Status judgement
            if ($spk === null || $mesRows->isEmpty()) {
                $status = 'ONLY_IN_SAP';
                $statusLabel = 'Hanya di SAP';
                $statusBadge = 'bg-purple-100 text-purple-800 border-purple-200';
            } elseif (abs($diff) < 0.001) {
                $status = 'MATCH';
                $statusLabel = '✓ Masuk SAP (Cocok)';
                $statusBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            } else {
                $status = 'QTY_DIFF';
                $statusLabel = $diff > 0 ? "Selisih (+{$diff} di MES)" : "Selisih ({$diff} di MES)";
                $statusBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            }

            $comparison[] = [
                'type' => 'RECEIPT',
                'key' => $spk ?: $spkKey,
                'spk_code' => $spk,
                'item_code' => $itemCode,
                'description' => $sapData['description'],
                'warehouse' => $mesWarehouse,
                'sap_qty' => $sapQty,
                'mes_qty' => $mesQty,
                'diff_qty' => $diff,
                'sap_docs' => $sapData['sap_docs'],
                'sap_dates' => $sapData['sap_dates'],
                'mes_dates' => $mesDates,
                'mes_sap_sent' => $mesSapSent,
                'mes_records_count' => $mesRows->count(),
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'sap_lines' => $sapData['sap_lines'],
                'mes_lines' => $mesRows->toArray(),
            ];
        }

        // B. Process MES entries that are NOT in the SAP file (Belum Masuk SAP)
        foreach ($mesInRange as $mesSpk => $mesRows) {
            if (isset($processedSpks[$mesSpk])) {
                continue;
            }

            $mesQty = (float) $mesRows->sum('total_quantity');
            $spkMaster = $spkMasters->get($mesSpk);
            $itemCode = $spkMaster->item_code ?? ($scannedItemCodes[$mesSpk] ?? '—');
            $mesWarehouse = $mesRows->first()?->warehouse ?? ($spkMaster->warehouse ?? 'FFI');
            $mesSapSent = $mesRows->pluck('sap_sent')->unique()->toArray();
            $mesDates = $mesRows->pluck('created_date')->unique()->values()->toArray();

            $comparison[] = [
                'type' => 'RECEIPT',
                'key' => $mesSpk,
                'spk_code' => $mesSpk,
                'item_code' => $itemCode,
                'description' => '—',
                'warehouse' => $mesWarehouse,
                'sap_qty' => 0.0,
                'mes_qty' => $mesQty,
                'diff_qty' => $mesQty,
                'sap_docs' => [],
                'sap_dates' => [],
                'mes_dates' => $mesDates,
                'mes_sap_sent' => $mesSapSent,
                'mes_records_count' => $mesRows->count(),
                'status' => 'MISSING_IN_SAP',
                'status_label' => '❌ Belum Masuk SAP',
                'status_badge' => 'bg-rose-100 text-rose-800 border-rose-300',
                'sap_lines' => [],
                'mes_lines' => $mesRows->toArray(),
            ];
        }

        // Summary KPI counts
        $totalSapDocs = count(array_unique(array_merge(...array_map(fn ($c) => $c['sap_docs'], $comparison))));
        $totalSapPcs = array_sum(array_map(fn ($c) => $c['sap_qty'], $comparison));
        $totalMesPcs = array_sum(array_map(fn ($c) => $c['mes_qty'], $comparison));

        $countMatch = count(array_filter($comparison, fn ($c) => $c['status'] === 'MATCH'));
        $countDiff = count(array_filter($comparison, fn ($c) => $c['status'] === 'QTY_DIFF'));
        $countMissing = count(array_filter($comparison, fn ($c) => $c['status'] === 'MISSING_IN_SAP'));
        $countOnlySap = count(array_filter($comparison, fn ($c) => $c['status'] === 'ONLY_IN_SAP'));

        return [
            'comparison' => $comparison,
            'kpi' => [
                'total_items' => count($comparison),
                'total_sap_docs' => $totalSapDocs,
                'total_sap_pcs' => $totalSapPcs,
                'total_mes_pcs' => $totalMesPcs,
                'match_count' => $countMatch,
                'diff_count' => $countDiff,
                'missing_count' => $countMissing,
                'only_sap_count' => $countOnlySap,
            ],
        ];
    }

    /**
     * Parse File 2: Pallet Form / Inventory Transfer (FFI -> FG)
     */
    public function parsePalletFile(string $filePath): array
    {
        $rows = $this->readSpreadsheetRows($filePath);
        if (empty($rows)) {
            return ['rows' => [], 'min_date' => null, 'max_date' => null, 'total_sap_qty' => 0];
        }

        $headerIndex = 0;
        $colMap = [
            'doc_num' => null,
            'post_date' => null,
            'remarks' => null,
            'item_code' => null,
            'description' => null,
            'quantity' => null,
            'from_whse' => null,
            'to_whse' => null,
        ];

        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $row = $rows[$i];
            foreach ($row as $colIdx => $headerVal) {
                $cleaned = strtolower(trim((string) $headerVal));
                if (str_contains($cleaned, 'document') && str_contains($cleaned, 'number') || $cleaned === 'docnum') {
                    $colMap['doc_num'] = $colIdx;
                } elseif (str_contains($cleaned, 'posting') && str_contains($cleaned, 'date') || $cleaned === 'docdate') {
                    $colMap['post_date'] = $colIdx;
                } elseif (str_contains($cleaned, 'remark') || str_contains($cleaned, 'comment')) {
                    $colMap['remarks'] = $colIdx;
                } elseif (str_contains($cleaned, 'item') && (str_contains($cleaned, 'code') || str_contains($cleaned, 'no'))) {
                    $colMap['item_code'] = $colIdx;
                } elseif (str_contains($cleaned, 'description')) {
                    $colMap['description'] = $colIdx;
                } elseif (str_contains($cleaned, 'quantity') || $cleaned === 'qty') {
                    $colMap['quantity'] = $colIdx;
                } elseif (str_contains($cleaned, 'filter') && str_contains($cleaned, 'warehouse')) {
                    $colMap['from_whse'] = $colIdx;
                } elseif (str_contains($cleaned, 'warehouse') && ! str_contains($cleaned, 'filter')) {
                    $colMap['to_whse'] = $colIdx;
                }
            }

            if ($colMap['doc_num'] !== null && $colMap['remarks'] !== null) {
                $headerIndex = $i;
                break;
            }
        }

        // Fallback default column indexes based on Image 2:
        // A:#, B:Document Number, C:Posting Date, D:Remarks, E:Item Code, F:Item Description, G:Quantity, H:Filter Warehouse Code, I:Warehouse Code
        if ($colMap['doc_num'] === null) {
            $colMap = [
                'doc_num' => 1,
                'post_date' => 2,
                'remarks' => 3,
                'item_code' => 4,
                'description' => 5,
                'quantity' => 6,
                'from_whse' => 7,
                'to_whse' => 8,
            ];
            $headerIndex = 0;
        }

        $parsedRows = [];
        $dates = [];
        $totalSapQty = 0;

        for ($r = $headerIndex + 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            $docNum = trim((string) ($row[$colMap['doc_num']] ?? ''));
            $remarks = trim((string) ($row[$colMap['remarks']] ?? ''));
            $itemCode = trim((string) ($row[$colMap['item_code']] ?? ''));
            $desc = trim((string) ($row[$colMap['description']] ?? ''));
            $qty = $this->cleanQuantity($row[$colMap['quantity']] ?? 0);
            $fromWhse = trim((string) ($row[$colMap['from_whse']] ?? 'FFI'));
            $toWhse = trim((string) ($row[$colMap['to_whse']] ?? 'FG'));
            $postDate = $this->parseSapDate($row[$colMap['post_date']] ?? null);

            if (empty($docNum) && empty($remarks)) {
                continue;
            }
            if (strtolower($docNum) === 'document number' || strtolower($remarks) === 'remarks') {
                continue;
            }

            $palletId = $this->extractPalletFromRemarks($remarks);

            if ($postDate) {
                $dates[] = $postDate;
            }
            $totalSapQty += $qty;

            $parsedRows[] = [
                'doc_num' => $docNum,
                'posting_date' => $postDate,
                'pallet_id' => $palletId,
                'item_code' => $itemCode,
                'description' => $desc,
                'quantity' => $qty,
                'from_whse' => $fromWhse,
                'to_whse' => $toWhse,
                'remarks' => $remarks,
            ];
        }

        sort($dates);
        $minDate = ! empty($dates) ? $dates[0] : null;
        $maxDate = ! empty($dates) ? end($dates) : null;

        return [
            'rows' => $parsedRows,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'total_sap_qty' => $totalSapQty,
            'total_rows' => count($parsedRows),
        ];
    }

    /**
     * Reconcile Pallet Form Data (SAP rows vs MES wms_pallet_forms)
     */
    public function reconcilePalletData(array $sapRows, ?string $filterStartDate = null, ?string $filterEndDate = null): array
    {
        // 1. Group SAP rows by Pallet ID
        $sapByPallet = [];
        foreach ($sapRows as $r) {
            $palletId = $r['pallet_id'] ?: 'NO_PLT_' . $r['doc_num'];
            if (! isset($sapByPallet[$palletId])) {
                $sapByPallet[$palletId] = [
                    'pallet_id' => $r['pallet_id'],
                    'item_code' => $r['item_code'],
                    'description' => $r['description'],
                    'from_whse' => $r['from_whse'],
                    'to_whse' => $r['to_whse'],
                    'sap_qty' => 0,
                    'sap_docs' => [],
                    'sap_dates' => [],
                    'items_list' => [],
                    'sap_lines' => [],
                ];
            }

            $sapByPallet[$palletId]['sap_qty'] += $r['quantity'];
            if (! in_array($r['doc_num'], $sapByPallet[$palletId]['sap_docs'])) {
                $sapByPallet[$palletId]['sap_docs'][] = $r['doc_num'];
            }
            if ($r['posting_date'] && ! in_array($r['posting_date'], $sapByPallet[$palletId]['sap_dates'])) {
                $sapByPallet[$palletId]['sap_dates'][] = $r['posting_date'];
            }
            if (! in_array($r['item_code'], $sapByPallet[$palletId]['items_list']) && ! empty($r['item_code'])) {
                $sapByPallet[$palletId]['items_list'][] = $r['item_code'];
            }
            $sapByPallet[$palletId]['sap_lines'][] = $r;
        }

        // 2. Query MES wms_pallet_forms for these pallet IDs
        $allPalletIds = array_values(array_filter(array_keys($sapByPallet), fn ($k) => ! str_starts_with($k, 'NO_PLT_')));

        $mesPallets = DB::table('wms_pallet_forms')
            ->whereIn('pallet_id', $allPalletIds)
            ->select('pallet_id', 'part_no', 'model_name', 'total_pallet_qty', 'box_qty', 'prod_date', 'sap_sync_status', 'sap_sync_at', 'created_at')
            ->get()
            ->keyBy('pallet_id');

        // Details for items
        $mesDetails = DB::table('wms_pallet_form_details')
            ->whereIn('pallet_form_id', $allPalletIds)
            ->select('pallet_form_id', 'part_no', 'model_name', 'spk_no', 'qty')
            ->get()
            ->groupBy('pallet_form_id');

        // Also query MES pallets within specified date range to find Pallets that are missing from SAP file
        $mesMissingQuery = DB::table('wms_pallet_forms');
        if ($filterStartDate && $filterEndDate) {
            $mesMissingQuery->whereBetween('prod_date', [$filterStartDate, $filterEndDate]);
        } elseif ($filterStartDate) {
            $mesMissingQuery->where('prod_date', '>=', $filterStartDate);
        }

        $mesInRange = $mesMissingQuery
            ->select('pallet_id', 'part_no', 'model_name', 'total_pallet_qty', 'box_qty', 'prod_date', 'sap_sync_status', 'sap_sync_at', 'created_at')
            ->get()
            ->keyBy('pallet_id');

        $comparison = [];
        $processedPallets = [];

        // A. Process pallets present in SAP
        foreach ($sapByPallet as $palletKey => $sapData) {
            $palletId = $sapData['pallet_id'];
            $processedPallets[$palletKey] = true;

            $mesPallet = $palletId ? $mesPallets->get($palletId) : null;
            $mesPalletDetails = $palletId ? ($mesDetails->get($palletId) ?? collect()) : collect();

            $mesQty = $mesPallet ? (float) $mesPallet->total_pallet_qty : 0.0;
            $sapQty = (float) $sapData['sap_qty'];
            $diff = $mesQty - $sapQty;

            $itemCode = ! empty($sapData['items_list'])
                ? implode(', ', $sapData['items_list'])
                : ($mesPallet->part_no ?? '—');
            $desc = $sapData['description'] ?: ($mesPallet->model_name ?? '—');

            if ($mesPallet === null) {
                $status = 'ONLY_IN_SAP';
                $statusLabel = 'Hanya di SAP';
                $statusBadge = 'bg-purple-100 text-purple-800 border-purple-200';
            } elseif (abs($diff) < 0.001) {
                $status = 'MATCH';
                $statusLabel = '✓ Masuk SAP (Cocok)';
                $statusBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            } else {
                $status = 'QTY_DIFF';
                $statusLabel = $diff > 0 ? "Selisih (+{$diff} di MES)" : "Selisih ({$diff} di MES)";
                $statusBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            }

            $comparison[] = [
                'type' => 'PALLET',
                'key' => $palletId ?: $palletKey,
                'pallet_id' => $palletId,
                'item_code' => $itemCode,
                'description' => $desc,
                'from_whse' => $sapData['from_whse'] ?: 'FFI',
                'to_whse' => $sapData['to_whse'] ?: 'FG',
                'sap_qty' => $sapQty,
                'mes_qty' => $mesQty,
                'diff_qty' => $diff,
                'sap_docs' => $sapData['sap_docs'],
                'sap_dates' => $sapData['sap_dates'],
                'mes_prod_date' => $mesPallet?->prod_date ? Carbon::parse($mesPallet->prod_date)->format('Y-m-d') : null,
                'mes_sync_status' => $mesPallet?->sap_sync_status,
                'mes_box_qty' => $mesPallet?->box_qty ?? 0,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'sap_lines' => $sapData['sap_lines'],
                'mes_details' => $mesPalletDetails->toArray(),
            ];
        }

        // B. Process MES pallets that are NOT in SAP file (Belum Masuk SAP)
        foreach ($mesInRange as $mesPalletId => $mesPallet) {
            if (isset($processedPallets[$mesPalletId])) {
                continue;
            }

            $mesQty = (float) $mesPallet->total_pallet_qty;
            $mesProdDate = $mesPallet->prod_date ? Carbon::parse($mesPallet->prod_date)->format('Y-m-d') : null;

            $comparison[] = [
                'type' => 'PALLET',
                'key' => $mesPalletId,
                'pallet_id' => $mesPalletId,
                'item_code' => $mesPallet->part_no ?: '—',
                'description' => $mesPallet->model_name ?: '—',
                'from_whse' => 'FFI',
                'to_whse' => 'FG',
                'sap_qty' => 0.0,
                'mes_qty' => $mesQty,
                'diff_qty' => $mesQty,
                'sap_docs' => [],
                'sap_dates' => [],
                'mes_prod_date' => $mesProdDate,
                'mes_sync_status' => $mesPallet->sap_sync_status,
                'mes_box_qty' => $mesPallet->box_qty ?? 0,
                'status' => 'MISSING_IN_SAP',
                'status_label' => '❌ Belum Masuk SAP',
                'status_badge' => 'bg-rose-100 text-rose-800 border-rose-300',
                'sap_lines' => [],
                'mes_details' => [],
            ];
        }

        $totalSapDocs = count(array_unique(array_merge(...array_map(fn ($c) => $c['sap_docs'], $comparison))));
        $totalSapPcs = array_sum(array_map(fn ($c) => $c['sap_qty'], $comparison));
        $totalMesPcs = array_sum(array_map(fn ($c) => $c['mes_qty'], $comparison));

        $countMatch = count(array_filter($comparison, fn ($c) => $c['status'] === 'MATCH'));
        $countDiff = count(array_filter($comparison, fn ($c) => $c['status'] === 'QTY_DIFF'));
        $countMissing = count(array_filter($comparison, fn ($c) => $c['status'] === 'MISSING_IN_SAP'));
        $countOnlySap = count(array_filter($comparison, fn ($c) => $c['status'] === 'ONLY_IN_SAP'));

        return [
            'comparison' => $comparison,
            'kpi' => [
                'total_items' => count($comparison),
                'total_sap_docs' => $totalSapDocs,
                'total_sap_pcs' => $totalSapPcs,
                'total_mes_pcs' => $totalMesPcs,
                'match_count' => $countMatch,
                'diff_count' => $countDiff,
                'missing_count' => $countMissing,
                'only_sap_count' => $countOnlySap,
            ],
        ];
    }

    /**
     * Persist reconciliation batch & items to database
     */
    public function saveBatch(
        string $type,
        string $fileName,
        ?string $filePath,
        ?int $userId,
        ?string $startDate,
        ?string $endDate,
        array $reconciled
    ): \App\Models\SapVerificationBatch {
        $kpi = $reconciled['kpi'] ?? [];
        $comparison = $reconciled['comparison'] ?? [];

        return DB::transaction(function () use ($type, $fileName, $filePath, $userId, $startDate, $endDate, $kpi, $comparison) {
            $batch = \App\Models\SapVerificationBatch::create([
                'type' => $type,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'uploaded_by' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_items' => $kpi['total_items'] ?? count($comparison),
                'total_sap_qty' => $kpi['total_sap_pcs'] ?? 0,
                'total_mes_qty' => $kpi['total_mes_pcs'] ?? 0,
                'match_count' => $kpi['match_count'] ?? 0,
                'diff_count' => $kpi['diff_count'] ?? 0,
                'missing_count' => $kpi['missing_count'] ?? 0,
                'only_sap_count' => $kpi['only_sap_count'] ?? 0,
            ]);

            $now = now();
            $records = [];
            foreach ($comparison as $c) {
                $records[] = [
                    'batch_id' => $batch->id,
                    'ref_code' => (string) ($c['key'] ?? ''),
                    'item_code' => $c['item_code'] ?? null,
                    'description' => $c['description'] ?? null,
                    'warehouse' => $c['warehouse'] ?? ($c['from_whse'] ?? null),
                    'sap_qty' => $c['sap_qty'] ?? 0,
                    'mes_qty' => $c['mes_qty'] ?? 0,
                    'diff_qty' => $c['diff_qty'] ?? 0,
                    'status' => $c['status'] ?? 'MATCH',
                    'status_label' => $c['status_label'] ?? '',
                    'sap_docs' => json_encode($c['sap_docs'] ?? []),
                    'sap_dates' => json_encode($c['sap_dates'] ?? []),
                    'mes_dates' => json_encode($c['mes_dates'] ?? []),
                    'raw_data' => json_encode([
                        'sap_lines' => $c['sap_lines'] ?? [],
                        'mes_lines' => $c['mes_lines'] ?? [],
                        'mes_details' => $c['mes_details'] ?? [],
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($records, 200) as $chunk) {
                \App\Models\SapVerificationItem::insert($chunk);
            }

            return $batch;
        });
    }

    /**
     * Load batch and convert items into comparison format
     */
    public function loadBatchData(int $batchId): ?array
    {
        $batch = \App\Models\SapVerificationBatch::with('items')->find($batchId);
        if (! $batch) {
            return null;
        }

        $comparison = [];
        foreach ($batch->items as $item) {
            $rawData = $item->raw_data ?: [];

            $statusBadge = match ($item->status) {
                'MATCH' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'QTY_DIFF' => 'bg-amber-100 text-amber-800 border-amber-300',
                'MISSING_IN_SAP' => 'bg-rose-100 text-rose-800 border-rose-300',
                'ONLY_IN_SAP' => 'bg-purple-100 text-purple-800 border-purple-200',
                default => 'bg-gray-100 text-gray-800 border-gray-200',
            };

            $comparison[] = [
                'type' => $batch->type,
                'key' => $item->ref_code,
                'spk_code' => $batch->type === 'RECEIPT' ? $item->ref_code : null,
                'pallet_id' => $batch->type === 'PALLET' ? $item->ref_code : null,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'warehouse' => $item->warehouse,
                'from_whse' => $item->warehouse,
                'to_whse' => 'FG',
                'sap_qty' => (float) $item->sap_qty,
                'mes_qty' => (float) $item->mes_qty,
                'diff_qty' => (float) $item->diff_qty,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'status_badge' => $statusBadge,
                'sap_docs' => $item->sap_docs ?: [],
                'sap_dates' => $item->sap_dates ?: [],
                'mes_dates' => $item->mes_dates ?: [],
                'mes_prod_date' => ! empty($item->mes_dates) ? $item->mes_dates[0] : null,
                'sap_lines' => $rawData['sap_lines'] ?? [],
                'mes_lines' => $rawData['mes_lines'] ?? [],
                'mes_details' => $rawData['mes_details'] ?? [],
            ];
        }

        $kpi = [
            'total_items' => $batch->total_items,
            'total_sap_docs' => count(array_unique(array_merge(...array_map(fn ($c) => $c['sap_docs'], $comparison)))),
            'total_sap_pcs' => (float) $batch->total_sap_qty,
            'total_mes_pcs' => (float) $batch->total_mes_qty,
            'match_count' => $batch->match_count,
            'diff_count' => $batch->diff_count,
            'missing_count' => $batch->missing_count,
            'only_sap_count' => $batch->only_sap_count,
        ];

        return [
            'batch' => $batch,
            'comparison' => $comparison,
            'kpi' => $kpi,
        ];
    }
}
