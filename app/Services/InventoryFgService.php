<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryFgService extends BaseSapService
{
    public function getInventoryFg($startDate = '2025-12-01')
    {
        $routes = [
            '/api/sap_inventory_fg/list',
            '/api/sap_inventory_fg_semi/list',
            '/api/sap_inventory_fg_semi_wip/list',
            '/api/sap_inventory_fg_semi_semi_wip/list',
        ];

        $allData = [];

        foreach ($routes as $route) {
            $response = $this->get($route, [
                'startDate' => $startDate,
            ]);

            // Nama sumber data biar kelihatan asalnya
            $label = basename(dirname($route)) ?: basename($route);

            $data = $this->normalizeResponse($response, "Inventory Fg {$label}");

            foreach ($data as $item) {
                $allData[] = $item;
            }
        }

        return $this->transformData($allData);

    }

    private function normalizeResponse($response, $tag = 'SAP')
    {
        if (! is_array($response)) {
            Log::warning("[{$tag}] Response bukan array", ['response' => $response]);

            return [];
        }

        if (array_key_exists('data', $response)) {
            return is_array($response['data']) ? $response['data'] : [];
        }

        return $response;
    }

    private function transformData(array $data)
    {
        // Step 1: Format QuantityBOM, InStock, ItemGroup
        $data = collect($data)->map(function ($item) {
            if (! is_array($item)) {
                return [];
            }

            $item['StandardTime'] = number_format(floatval($item['StandardTime'] ?? 0), 5, '.', '');
            $item['U_SAFETYSTOCK'] = number_format(floatval($item['U_SAFETYSTOCK'] ?? 0), 0, '.', '');
            $item['U_DAILYLIMIT'] = number_format(floatval($item['U_DAILYLIMIT'] ?? 0), 0, '.', '');
            $item['OnHand'] = number_format(floatval($item['OnHand'] ?? 0), 0, '.', '');
            $item['ONORDER'] = number_format(floatval($item['ONORDER'] ?? 0), 0, '.', '');

            return $item;
        })->filter();

        return $data->values()->all();
    }

    public function SyncData()
    {
        $startDate = '2025-12-01';

        $inventoryFg = $this->getInventoryFg($startDate); // udah array
        // dd($inventoryFg);

        $inventoryFg = array_map(function ($item) {
            if (isset($item['Code']) && ! isset($item['ItemCode'])) {
                $item['ItemCode'] = $item['Code'];
                unset($item['Code']); // hapus key lama
            }

            return $item;
        }, $inventoryFg);

        $inventoryFg = array_map(function ($item) {
            if (isset($item['ItemGroup']) && ! isset($item['ItmsGrpCod'])) {
                $item['ItmsGrpCod'] = $item['ItemGroup'];
                unset($item['ItemGroup']); // hapus key lama
            }

            return $item;
        }, $inventoryFg);

        DB::table('sap_inventory_fg')->truncate();
        $data = array_map(function ($item) {
            return [
                'item_code' => $item['ItemCode'],
                'item_name' => $item['ItemName'],
                'item_group' => $item['ItmsGrpCod'],
                'day_set_pps' => $item['U_DAYSETPPS'],
                'setup_time' => $item['U_STPTIME'],
                'cycle_time' => $item['StandardTime'],
                'cavity' => $item['U_KVT'],
                'safety_stock' => $item['U_SAFETYSTOCK'],
                'daily_limit' => $item['U_DAILYLIMIT'],
                'stock' => $item['OnHand'],
                'total_spk' => $item['ONORDER'],
                'production_min_qty' => $item['U_PROD_MIN_QTY'],
                'standar_packing' => $item['SALPACKUN'],
                'pair' => $item['U_PAIR'],
                'man_power' => $item['ManPower'],
                'warehouse' => $item['TOWH'],
                'process_owner' => $item['ProcessOwner'],
                'owner_code' => $item['OwnerCode'],
                'special_condition' => $item['U_Special_Code'],
                'fg_code_1' => $item['U_FG_CODE_1'],
                'fg_code_2' => $item['U_FG_CODE_2'],
                'wip_code' => $item['U_WIP_CODE_1'],
                'material_percentage' => $item['U_MATERIAL_PERCENT'],
                'continue_production' => $item['U_CONTINUE_PROD'],
                'family' => $item['U_FAMILY'],
                'material_group' => $item['U_MATGROUPING'],
                'old_mould' => $item['U_old_mould'],
                'packaging' => $item['U_carton'],
                'bom_level' => $item['BOM_LEVEL'],
            ];
        }, $inventoryFg);
        // dd($data);

        DB::table('sap_inventory_fg')->truncate();
        DB::table('sap_inventory_fg')->upsert(
            $data,
            ['item_code'], // Key for detecting duplicates
            [ // Columns to update
                'item_name', 'day_set_pps', 'setup_time', 'cycle_time', 'cavity', 'safety_stock',
                'daily_limit', 'stock', 'total_spk', 'pair', 'man_power', 'warehouse',
                'process_owner', 'owner_code', 'special_condition', 'fg_code_1', 'fg_code_2',
                'wip_code', 'material_percentage', 'continue_production', 'family',
                'material_group', 'packaging', 'bom_level',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data Inventory Fg berhasil disinkronkan',
            'data' => [
                'inventoryfg' => $inventoryFg,
            ],
        ]);

    }
}
