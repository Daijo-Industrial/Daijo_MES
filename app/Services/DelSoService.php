<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelSoService extends BaseSapService
{
    public function getDelSo($startDate = '2025-12-01')
    {
        $response = $this->get('/api/sap_del_so/list', [
            'startDate' => $startDate,
        ]);

        $data = $this->normalizeResponse($response, 'DELSO');

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
        $startDate = '2025-12-01';

        $delSo = $this->getDelSo($startDate); // udah array

        DB::table('sap_delso')->truncate();

        foreach ($delSo as $row) {

            if (! is_array($row)) {
                Log::warning('DelSoService: Row bukan array', ['row' => $row]);
                continue;
            }

            $quantity = isset($row['Quantity']) ? (int) $row['Quantity'] : 0;
            $delquantity = isset($row['DeliveryQty']) ? (int) $row['DeliveryQty'] : 0;

            DB::table('sap_delso')->insert([
                'doc_num' => $row['DocNum'] ?? null,
                'doc_status' => $row['DocStatus'] ?? null,
                'item_no' => $row['ItemCode'] ?? null,
                'quantity' => $quantity,
                'delivered_qty' => $delquantity,
                'line_num' => $row['LineNum'] ?? null,
                'row_status' => $row['LineStatus'] ?? null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data Delso berhasil disinkronkan',
            'data' => [
                'delsched' => $delSo,
            ],
        ]);

    }
}
