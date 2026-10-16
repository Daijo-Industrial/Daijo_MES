<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelSchedService extends BaseSapService
{
    public function getDelSched($startDate = '2026-01-01')
    {
        $response = $this->get('/api/sap_del_sched/list', [
            'startDate' => $startDate,
        ]);

        $data = $this->normalizeResponse($response, 'DELSCHED');

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
        $startDate = '2026-01-01';

        $delSched = $this->getDelSched($startDate); // udah array

        DB::table('sap_delsched')->truncate();

        foreach ($delSched as $row) {
            $deliveryDate = \Carbon\Carbon::createFromFormat('d/m/Y', $row['DeliveryDate'])
                ->format('Y-m-d');

            $quantity = isset($row['DeliveryQty']) ? (int) $row['DeliveryQty'] : 0;

            DB::table('sap_delsched')->insert([
                'item_code' => $row['PartNo'],
                'delivery_date' => $deliveryDate,
                'delivery_qty' => $quantity,
                'so_number' => $row['SONum'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data Delsched berhasil disinkronkan',
            'data' => [
                'delsched' => $delSched,
            ],
        ]);

    }
}
