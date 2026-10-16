<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RejectService extends BaseSapService
{
    public function getReject($itemGroupCodes = '102,103', $warehouseCodes = 'RJCT,RJCTEX')
    {
        $response = $this->get('/api/sap_reject/list', [
            'itemGroupCodes' => $itemGroupCodes,
            'warehouseCodes' => $warehouseCodes,
        ]);

        $data = $this->normalizeResponse($response, 'REJECT');

        $result = [];

        foreach ($data as $item) {
            $result[] = $item;
        }

        return $result;

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
        $itemGroupCodes = '102,103';
        $warehouseCodes = 'RJCT,RJCTEX';

        $Reject = $this->getReject($itemGroupCodes, $warehouseCodes); // udah array

        DB::table('sap_reject')->truncate();

        $data = array_map(function ($item) {
            return [
                'item_no' => $item['ItemCode'],
                'item_description' => $item['ItemName'],
                'item_group' => $item['ItemGroupCode'],
                'in_stock' => (int) ($item['TotalReject'] ?? 0),
            ];
        }, $Reject);

        DB::table('sap_reject')->truncate();
        DB::table('sap_reject')->insert($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Reject berhasil disinkronkan',
            'data' => [
                'reject' => $Reject,
            ],
        ]);

    }
}
