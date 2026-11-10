<?php

namespace App\Services;

use App\Models\MasterBom;
use App\Models\MasterListItem;
use Illuminate\Support\Facades\DB;

class PoReminderService
{
    /**
     * Hitung Kebutuhan Material (PO Reminder) berdasarkan Delivery Schedule dan Master BOM
     *
     * @param string $startDate Tanggal awal delivery schedule (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir delivery schedule (YYYY-MM-DD)
     * @param string|null $search Filter pencarian kode/nama item FG atau material
     * @param string|null $categoryFilter Filter kategori material (RESIN, PACKAGING, CHEMICAL, HARDWARE, RAW_MATERIAL, WIP)
     * @return array
     */
    public function calculate(string $startDate, string $endDate, ?string $search = null, ?string $categoryFilter = null): array
    {
        // 1. Ambil data delivery schedule yang ter-group per item_code
        $schedQuery = DB::table('sap_delsched')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->select(
                'item_code',
                DB::raw('SUM(delivery_qty) as total_delivery_qty'),
                DB::raw('COUNT(*) as delivery_count'),
                DB::raw('MIN(delivery_date) as first_delivery_date'),
                DB::raw('MAX(delivery_date) as last_delivery_date'),
                DB::raw('GROUP_CONCAT(DISTINCT so_number SEPARATOR ", ") as so_numbers')
            )
            ->groupBy('item_code')
            ->orderBy('item_code', 'asc');

        $schedules = $schedQuery->get();

        if ($schedules->isEmpty()) {
            return [
                'has_data' => false,
                'kpi' => [
                    'total_fg_items' => 0,
                    'total_delivery_qty' => 0,
                    'total_stock_fg' => 0,
                    'total_net_deficit' => 0,
                    'total_materials_gross' => 0,
                    'total_materials_net' => 0,
                    'missing_bom_count' => 0,
                ],
                'fg_items' => [],
                'view1_materials' => [], // Gross
                'view2_materials' => [], // Net
            ];
        }

        // 1b. Ambil rincian jadwal delivery per tanggal untuk breakdown tanggal kirim
        $dailyScheds = DB::table('sap_delsched')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->select('item_code', 'delivery_date', 'delivery_qty', 'so_number')
            ->orderBy('delivery_date', 'asc')
            ->get();

        $dailyByItem = [];
        foreach ($dailyScheds as $ds) {
            $c = $ds->item_code;
            $cClean = rtrim($c, '.');
            $row = [
                'delivery_date' => $ds->delivery_date,
                'delivery_qty' => (float) $ds->delivery_qty,
                'so_number' => $ds->so_number ?: '—',
            ];
            $dailyByItem[$c][] = $row;
            if ($cClean !== $c) {
                $dailyByItem[$cClean][] = $row;
            }
        }

        $allItemCodes = $schedules->pluck('item_code')->unique()->values()->toArray();

        // Siapkan variant kode (dengan titik & tanpa titik di belakang)
        $lookupCodes = [];
        foreach ($allItemCodes as $c) {
            $cClean = rtrim($c, '.');
            $lookupCodes[] = $c;
            $lookupCodes[] = $cClean;
            $lookupCodes[] = $cClean . '.';
        }
        $lookupCodes = array_values(array_unique($lookupCodes));

        // 2. Query stock aktual FG dari sap_inventory_fg (Snapshot saat ini, tidak disum)
        $inventoryRows = DB::table('sap_inventory_fg')
            ->whereIn('item_code', $lookupCodes)
            ->get(['item_code', 'item_name', 'stock'])
            ->keyBy('item_code');

        // 3. Query MasterListItem untuk nama part & customer delivery
        $masterItems = MasterListItem::with('customer')
            ->whereIn('item_code', $lookupCodes)
            ->get()
            ->keyBy('item_code');

        // 4. Preload SEMUA Master BOM aktif ke dalam in-memory adjacency map untuk high performance explosion
        $boms = DB::table('master_boms')
            ->where('is_active', 1)
            ->select('parent_item', 'component_item', 'component_description', 'quantity', 'uom', 'family')
            ->get();

        $bomMap = [];
        $wipCodes = [];
        foreach ($boms as $b) {
            $bomMap[$b->parent_item][] = $b;
            $wipCodes[$b->parent_item] = true;
        }

        // 5. Fungsi rekursif untuk meledakkan (Explode) BOM
        $explodeBom = function (string $code, float $multiplier, array $visited = []) use (&$explodeBom, $bomMap, $wipCodes): array {
            if ($multiplier <= 0) {
                return [];
            }

            $clean = rtrim($code, '.');
            $components = $bomMap[$code] ?? $bomMap[$clean] ?? $bomMap[$clean . '.'] ?? [];

            $result = [];

            foreach ($components as $comp) {
                $compCode = $comp->component_item;
                $unitQty = (float) $comp->quantity;
                $neededQty = $unitQty * $multiplier;
                $isWip = isset($wipCodes[$compCode]) && ! in_array($compCode, $visited, true);
                $category = MasterBom::determineCategory($compCode, $comp->component_description ?? '', $isWip);

                if ($isWip) {
                    // Item ini adalah WIP / Sub-Assembly: rekam WIP dan ledakkan anak-anaknya ke material paling bawah
                    $result[] = [
                        'item_code' => $compCode,
                        'description' => $comp->component_description ?: $compCode,
                        'unit_qty' => $unitQty,
                        'needed_qty' => $neededQty,
                        'uom' => $comp->uom ?: 'PCS',
                        'is_wip' => true,
                        'category' => $category,
                        'level_path' => implode(' > ', array_merge($visited, [$code, $compCode])),
                    ];

                    $childResults = $explodeBom($compCode, $neededQty, array_merge($visited, [$compCode]));
                    foreach ($childResults as $child) {
                        $result[] = $child;
                    }
                } else {
                    // Leaf Component (Bahan Baku / Raw Material / Resin / Chemical / Packaging / Hardware)
                    $result[] = [
                        'item_code' => $compCode,
                        'description' => $comp->component_description ?: $compCode,
                        'unit_qty' => $unitQty,
                        'needed_qty' => $neededQty,
                        'uom' => $comp->uom ?: 'PCS',
                        'is_wip' => false,
                        'category' => $category,
                        'level_path' => implode(' > ', array_merge($visited, [$code, $compCode])),
                    ];
                }
            }

            return $result;
        };

        // 6. Loop data Delivery Schedule dan kalkulasi untuk tiap Finished Good
        $fgItems = [];
        $view1MaterialMap = []; // Gross: Berdasarkan Total Delivery
        $view2MaterialMap = []; // Net: Berdasarkan Total Delivery - Stok Aktual FG

        $totalDeliverySum = 0;
        $totalStockSum = 0;
        $totalNetDeficitSum = 0;
        $missingBomCount = 0;

        foreach ($schedules as $sched) {
            $code = $sched->item_code;
            $cleanCode = rtrim($code, '.');

            // Resolusi master item & nama customer
            $master = $masterItems->get($code) ?? $masterItems->get($cleanCode) ?? $masterItems->get($cleanCode . '.');
            $customerName = $master?->customer?->customer_name ?: '—';

            // Resolusi stock aktual FG dari sap_inventory_fg
            $inv = $inventoryRows->get($code) ?? $inventoryRows->get($cleanCode) ?? $inventoryRows->get($cleanCode . '.');
            $stockActual = (int) ($inv?->stock ?? 0);
            $itemName = $master?->item_name ?: ($inv?->item_name ?: $code);

            $deliveryQty = (float) $sched->total_delivery_qty;
            $netDeficit = max(0, $deliveryQty - $stockActual);

            $totalDeliverySum += $deliveryQty;
            $totalStockSum += $stockActual;
            $totalNetDeficitSum += $netDeficit;

            // Cek apakah BOM tersedia
            $hasBom = isset($bomMap[$code]) || isset($bomMap[$cleanCode]) || isset($bomMap[$cleanCode . '.']);
            if (! $hasBom) {
                $missingBomCount++;
            }

            // Explode View 1 (Gross: multiplier = total_delivery_qty)
            $explodedGross = $explodeBom($code, $deliveryQty);

            // Explode View 2 (Net: multiplier = netDeficit)
            $explodedNet = $explodeBom($code, $netDeficit);

            $itemDailySchedules = $dailyByItem[$code] ?? $dailyByItem[$cleanCode] ?? [];
            $netRatio = $deliveryQty > 0 ? ($netDeficit / $deliveryQty) : 0;

            // Agregasi View 1 (Gross) per Material
            foreach ($explodedGross as $m) {
                $matCode = $m['item_code'];
                if (! isset($view1MaterialMap[$matCode])) {
                    $view1MaterialMap[$matCode] = [
                        'item_code' => $matCode,
                        'description' => $m['description'],
                        'uom' => $m['uom'],
                        'is_wip' => $m['is_wip'],
                        'category' => $m['category'],
                        'total_needed_qty' => 0.0,
                        'fgs_count' => 0,
                        'used_by_fgs' => [],
                        'daily_timeline' => [],
                    ];
                }
                $view1MaterialMap[$matCode]['total_needed_qty'] += $m['needed_qty'];

                if (! isset($view1MaterialMap[$matCode]['used_by_fgs'][$code])) {
                    $schedulesBreakdown = [];
                    foreach ($itemDailySchedules as $ds) {
                        $matQtyForDate = $ds['delivery_qty'] * $m['unit_qty'];
                        $schedulesBreakdown[] = [
                            'delivery_date' => $ds['delivery_date'],
                            'delivery_qty' => $ds['delivery_qty'],
                            'material_qty' => $matQtyForDate,
                            'so_number' => $ds['so_number'],
                        ];

                        $dDate = $ds['delivery_date'];
                        if (! isset($view1MaterialMap[$matCode]['daily_timeline'][$dDate])) {
                            $view1MaterialMap[$matCode]['daily_timeline'][$dDate] = [
                                'date' => $dDate,
                                'total_material_qty' => 0.0,
                                'fgs_summary' => [],
                            ];
                        }
                        $view1MaterialMap[$matCode]['daily_timeline'][$dDate]['total_material_qty'] += $matQtyForDate;
                        $view1MaterialMap[$matCode]['daily_timeline'][$dDate]['fgs_summary'][$code] = [
                            'fg_code' => $code,
                            'fg_name' => $itemName,
                            'fg_qty' => $ds['delivery_qty'],
                            'material_qty' => ($view1MaterialMap[$matCode]['daily_timeline'][$dDate]['fgs_summary'][$code]['material_qty'] ?? 0) + $matQtyForDate,
                        ];
                    }

                    $view1MaterialMap[$matCode]['used_by_fgs'][$code] = [
                        'fg_code' => $code,
                        'fg_name' => $itemName,
                        'customer_name' => $customerName,
                        'fg_delivery_qty' => $deliveryQty,
                        'unit_qty' => $m['unit_qty'],
                        'material_qty' => $m['needed_qty'],
                        'delivery_count' => count($itemDailySchedules),
                        'schedules' => $schedulesBreakdown,
                    ];
                } else {
                    $view1MaterialMap[$matCode]['used_by_fgs'][$code]['material_qty'] += $m['needed_qty'];
                }
            }

            // Agregasi View 2 (Net) per Material
            foreach ($explodedNet as $m) {
                $matCode = $m['item_code'];
                if (! isset($view2MaterialMap[$matCode])) {
                    $view2MaterialMap[$matCode] = [
                        'item_code' => $matCode,
                        'description' => $m['description'],
                        'uom' => $m['uom'],
                        'is_wip' => $m['is_wip'],
                        'category' => $m['category'],
                        'total_needed_qty' => 0.0,
                        'fgs_count' => 0,
                        'used_by_fgs' => [],
                        'daily_timeline' => [],
                    ];
                }
                $view2MaterialMap[$matCode]['total_needed_qty'] += $m['needed_qty'];

                if (! isset($view2MaterialMap[$matCode]['used_by_fgs'][$code])) {
                    $netSchedulesBreakdown = [];
                    foreach ($itemDailySchedules as $ds) {
                        $netMatQty = $ds['delivery_qty'] * $netRatio * $m['unit_qty'];
                        $netSchedulesBreakdown[] = [
                            'delivery_date' => $ds['delivery_date'],
                            'delivery_qty' => $ds['delivery_qty'],
                            'material_qty' => $netMatQty,
                            'so_number' => $ds['so_number'],
                        ];

                        $dDate = $ds['delivery_date'];
                        if (! isset($view2MaterialMap[$matCode]['daily_timeline'][$dDate])) {
                            $view2MaterialMap[$matCode]['daily_timeline'][$dDate] = [
                                'date' => $dDate,
                                'total_material_qty' => 0.0,
                                'fgs_summary' => [],
                            ];
                        }
                        $view2MaterialMap[$matCode]['daily_timeline'][$dDate]['total_material_qty'] += $netMatQty;
                        $view2MaterialMap[$matCode]['daily_timeline'][$dDate]['fgs_summary'][$code] = [
                            'fg_code' => $code,
                            'fg_name' => $itemName,
                            'fg_qty' => $ds['delivery_qty'],
                            'material_qty' => ($view2MaterialMap[$matCode]['daily_timeline'][$dDate]['fgs_summary'][$code]['material_qty'] ?? 0) + $netMatQty,
                        ];
                    }

                    $view2MaterialMap[$matCode]['used_by_fgs'][$code] = [
                        'fg_code' => $code,
                        'fg_name' => $itemName,
                        'customer_name' => $customerName,
                        'fg_net_needed' => $netDeficit,
                        'fg_delivery_qty' => $deliveryQty,
                        'unit_qty' => $m['unit_qty'],
                        'material_qty' => $m['needed_qty'],
                        'delivery_count' => count($itemDailySchedules),
                        'schedules' => $netSchedulesBreakdown,
                    ];
                } else {
                    $view2MaterialMap[$matCode]['used_by_fgs'][$code]['material_qty'] += $m['needed_qty'];
                }
            }

            $fgItems[] = [
                'item_code' => $code,
                'item_name' => $itemName,
                'customer_name' => $customerName,
                'delivery_qty' => $deliveryQty,
                'stock_actual' => $stockActual,
                'net_deficit' => $netDeficit,
                'has_stock_shortage' => $netDeficit > 0,
                'delivery_count' => (int) $sched->delivery_count,
                'first_delivery' => $sched->first_delivery_date,
                'last_delivery' => $sched->last_delivery_date,
                'so_numbers' => $sched->so_numbers,
                'has_bom' => $hasBom,
                'daily_schedules' => $itemDailySchedules,
                'exploded_gross' => $explodedGross,
                'exploded_net' => $explodedNet,
            ];
        }

        // Hitung fgs_count & susun daily_timeline berurutan per tanggal
        foreach ($view1MaterialMap as &$m) {
            $m['fgs_count'] = count($m['used_by_fgs']);
            $m['used_by_fgs'] = array_values($m['used_by_fgs']);
            $timeline = array_values($m['daily_timeline'] ?? []);
            foreach ($timeline as &$tl) {
                $tl['fgs_summary'] = array_values($tl['fgs_summary'] ?? []);
            }
            unset($tl);
            usort($timeline, fn ($a, $b) => strcmp($a['date'], $b['date']));
            $m['daily_timeline'] = $timeline;
        }
        unset($m);

        foreach ($view2MaterialMap as &$m) {
            $m['fgs_count'] = count($m['used_by_fgs']);
            $m['used_by_fgs'] = array_values($m['used_by_fgs']);
            $timeline = array_values($m['daily_timeline'] ?? []);
            foreach ($timeline as &$tl) {
                $tl['fgs_summary'] = array_values($tl['fgs_summary'] ?? []);
            }
            unset($tl);
            usort($timeline, fn ($a, $b) => strcmp($a['date'], $b['date']));
            $m['daily_timeline'] = $timeline;
        }
        unset($m);

        // Sorting material dari kebutuhan terbesar
        $view1Materials = array_values($view1MaterialMap);
        usort($view1Materials, fn ($a, $b) => $b['total_needed_qty'] <=> $a['total_needed_qty']);

        $view2Materials = array_values($view2MaterialMap);
        usort($view2Materials, fn ($a, $b) => $b['total_needed_qty'] <=> $a['total_needed_qty']);

        // Filter pencarian jika ada
        if (! empty($search)) {
            $term = strtolower(trim($search));
            $fgItems = array_values(array_filter($fgItems, function ($fg) use ($term) {
                return str_contains(strtolower($fg['item_code']), $term)
                    || str_contains(strtolower($fg['item_name']), $term)
                    || str_contains(strtolower($fg['customer_name']), $term);
            }));

            $view1Materials = array_values(array_filter($view1Materials, function ($m) use ($term) {
                return str_contains(strtolower($m['item_code']), $term)
                    || str_contains(strtolower($m['description']), $term);
            }));

            $view2Materials = array_values(array_filter($view2Materials, function ($m) use ($term) {
                return str_contains(strtolower($m['item_code']), $term)
                    || str_contains(strtolower($m['description']), $term);
            }));
        }

        // Filter kategori material jika dipilih
        if (! empty($categoryFilter) && $categoryFilter !== 'ALL') {
            $cat = strtoupper(trim($categoryFilter));
            $view1Materials = array_values(array_filter($view1Materials, fn ($m) => ($m['category']['type'] ?? '') === $cat));
            $view2Materials = array_values(array_filter($view2Materials, fn ($m) => ($m['category']['type'] ?? '') === $cat));
        }

        return [
            'has_data' => true,
            'kpi' => [
                'total_fg_items' => count($fgItems),
                'total_delivery_qty' => $totalDeliverySum,
                'total_stock_fg' => $totalStockSum,
                'total_net_deficit' => $totalNetDeficitSum,
                'total_materials_gross' => count($view1Materials),
                'total_materials_net' => count($view2Materials),
                'missing_bom_count' => $missingBomCount,
            ],
            'fg_items' => $fgItems,
            'view1_materials' => $view1Materials,
            'view2_materials' => $view2Materials,
        ];
    }
}

