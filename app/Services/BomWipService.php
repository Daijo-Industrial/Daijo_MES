<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BomWipService extends BaseSapService
{
    public function getBomWip($startDate = '2025-12-01', $itemGroupCode = '103')
    {
        $response = $this->get('/api/sap_bom_wip/list', [
            'startDate' => $startDate,
            'itemGroupCode' => $itemGroupCode,
        ]);

        $data = $this->normalizeResponse($response, 'BOM WIP');

        $result = [];

        foreach ($data as $item) {
            $result[] = $item;
        }

        return $result;

    }

    public function getSemi($startDate = '2025-12-01', $itemGroupCode = '103')
    {
        $response = $this->get('/api/sap_bom_wip_semi/list', [
            'startDate' => $startDate,
            'itemGroupCode' => $itemGroupCode,
        ]);

        $data = $this->normalizeResponse($response, 'BOM WIP');

        $result = [];

        foreach ($data as $item) {
            $result[] = $item;
        }

        return $result;

    }

    public function getSemiSemi($startDate = '2025-12-01', $itemGroupCode = '103')
    {
        $response = $this->get('/api/sap_bom_wip_semi_semi/list', [
            'startDate' => $startDate,
            'itemGroupCode' => $itemGroupCode,
        ]);

        $data = $this->normalizeResponse($response, 'BOM WIP');

        $result = [];

        foreach ($data as $item) {
            $result[] = $item;
        }

        return $result;

    }

    public function getAllCombined($startDate, $itemGroupCode = '103')
    {
        $bom = $this->getBomWip($startDate, $itemGroupCode);
        $semi = $this->getSemi($startDate, $itemGroupCode);
        $semiSemi = $this->getSemiSemi($startDate, $itemGroupCode);

        return array_merge($bom, $semi, $semiSemi);
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

        $bomWip = $this->getAllCombined($startDate); // udah array
        // dd($bomWip);

        DB::table('sap_bom_wip')->truncate();

        foreach ($bomWip as $row) {
            DB::table('sap_bom_wip')->insert([
                'fg_code' => $row['FGCode'],
                'semi_first' => $row['SemiCode'],
                'qty_first' => isset($row['QuantityBOM1']) ? (int) $row['QuantityBOM1'] : 0,
                'semi_second' => $row['SemiSemiCode'],
                'qty_second' => isset($row['QuantityBOM2']) ? (int) $row['QuantityBOM2'] : 0,
                'semi_third' => $row['WIPCode'],
                'qty_third' => isset($row['QuantityBOM3']) ? (int) $row['QuantityBOM3'] : 0,
                'level' => $row['Level'],
                'item_group' => $row['ItmsGrpCod'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data BOM WIP berhasil disinkronkan',
            'data' => [
                'combined' => $bomWip,
            ],
        ]);

    }
}
