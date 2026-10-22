<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelActualService extends BaseSapService
{
    public function getDelActual($startDate = '2025-12-01')
    {
        $response = $this->get('/api/sap_del_actual/list', [
            'startDate' => $startDate,
        ]);

        $data = $this->normalizeResponse($response, 'DELACTUAL');

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

        $delActual = $this->getDelActual($startDate); // udah array

        DB::table('sap_delactual')->truncate();

        foreach ($delActual as $row) {
            $deliveryDate = \Carbon\Carbon::createFromFormat('d/m/Y', $row['DeliveryDate'])
                ->format('Y-m-d');

            $quantity = isset($row['Quantity']) ? (int) $row['Quantity'] : 0;

            DB::table('sap_delactual')->insert([
                'item_no' => $row['ItemCode'],
                'delivery_date' => $deliveryDate,
                'item_name' => $row['Description'],
                'quantity' => $quantity,
                'so_num' => $row['DocNum'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data BOM WIP berhasil disinkronkan',
            'data' => [
                'delactual' => $delActual,
            ],
        ]);

    }
}
