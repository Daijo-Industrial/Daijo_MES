<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryMtrService extends BaseSapService
{
    public function getInventoryMtr($startDate = '2025-12-01')
    {
        $routes = [
            '/api/sap_inventory_mtr/list',
            '/api/sap_inventory_mtr_semi/list',
            '/api/sap_inventory_mtr_semi_wip/list',
            '/api/sap_inventory_mtr_semi_semi_wip/list',
        ];

        $allData = [];

        foreach ($routes as $route) {
            $response = $this->get($route, [
                'startDate' => $startDate,
            ]);

            // Nama sumber data biar kelihatan asalnya
            $label = basename(dirname($route)) ?: basename($route);

            $data = $this->normalizeResponse($response, "Inventory Mtr {$label}");

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

            $item['Quantity'] = number_format(floatval($item['Quantity'] ?? 0), 5, '.', '');
            $item['OnHand'] = number_format(floatval($item['OnHand'] ?? 0), 5, '.', '');

            return $item;
        })->filter();

        return $data->values()->all();
    }

    public function SyncData()
    {
        $startDate = '2025-12-01';

        $inventoryMtr = $this->getInventoryMtr($startDate); // udah array

        $inventoryMtr = array_map(function ($item) {
            if (isset($item['Father']) && ! isset($item['PartNo'])) {
                $item['PartNo'] = $item['Father'];
                unset($item['Father']); // hapus key lama
            }

            return $item;
        }, $inventoryMtr);

        DB::table('sap_inventory_mtr')->truncate();
        $data = array_map(function ($item) {
            return [
                'fg_code' => $item['PartNo'] ?? null,
                'material_code' => $item['Code'] ?? null,
                'material_name' => $item['ItemName'] ?? null,
                'bom_quantity' => $item['Quantity'] ?? null,
                'in_stock' => $item['OnHand'] ?? null,
                'item_group' => $item['ItemGroup'] ?? null,
                'vendor_code' => $item['VendorCode'] ?? null,
                'vendor_name' => $item['VendorName'] ?? null,
            ];
        }, $inventoryMtr);

        DB::table('sap_inventory_mtr')->truncate();
        DB::table('sap_inventory_mtr')->insert($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Inventory Mtr berhasil disinkronkan',
            'data' => [
                'inventorymtr' => $inventoryMtr,
            ],
        ]);

    }
}
