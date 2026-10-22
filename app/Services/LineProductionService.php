<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LineProductionService extends BaseSapService
{
    public function getLineProduction($startDate = '2025-12-01')
    {
        $routes = [
            '/api/sap_lineproduction/list',
            '/api/sap_lineproduction_semi/list',
            '/api/sap_lineproduction_semi_wip/list',
            '/api/sap_lineproduction_semi_semi_wip/list',
        ];

        $allData = [];

        foreach ($routes as $route) {
            $response = $this->get($route, [
                'startDate' => $startDate,
            ]);

            $data = $this->normalizeResponse($response, 'Line Production');
            // foreach ($data as &$item) {
            //     $item['item_code'] = $item['item_code'] ?? $item['Code'] ?? null;
            //     unset($item['Code']); // hapus 'Code' biar gak dobel
            //     $item['line_production'] = $item['line_production'] ?? $item['LineProduction'] ?? null;
            //     unset($item['LineProduction']); // hapus 'Code' biar gak dobel
            //     $item['priority'] = $item['priority'] ?? $item['Priority'] ?? null;
            //     unset($item['Priority']); // hapus 'Code' biar gak dobel
            // }
            $allData = array_merge($allData, $data);
        }

        return $allData;

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

    public function SyncData()
    {
        $startDate = '2025-12-01';

        $LineProduction = $this->getLineProduction($startDate); // udah array
        // dd($LineProduction);
        // dd($LineProduction);

        $LineProduction = array_map(function ($item) {
            if (isset($item['Code']) && ! isset($item['ItemCode'])) {
                $item['ItemCode'] = $item['Code'];
                unset($item['Code']); // hapus key lama
            }

            return $item;
        }, $LineProduction);

        DB::table('sap_lineproduction')->truncate();
        $data = array_map(function ($item) {
            return [
                'item_code' => $item['ItemCode'],
                'line_production' => $item['LineProduction'],
                'priority' => $item['Priority'],
            ];
        }, $LineProduction);

        DB::table('sap_lineproduction')->truncate();
        DB::table('sap_lineproduction')->insert($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Line Production berhasil disinkronkan',
            'data' => [
                'lineproduction' => $LineProduction,
            ],
        ]);

    }
}
