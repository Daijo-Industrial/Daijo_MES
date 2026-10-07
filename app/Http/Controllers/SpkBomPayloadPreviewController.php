<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use Illuminate\Http\Request;

class SpkBomPayloadPreviewController extends Controller
{
    public function show(Request $request)
    {
        $previewData = session('sap_payload_preview');

        // Jika tidak ada di session, coba ambil dari query spk atau log terakhir
        if (!$previewData) {
            $spkQuery = $request->query('spk');
            $apiLog = null;

            if ($spkQuery) {
                $apiLog = ApiLog::where('api_name', 'sap_production_order_update')
                    ->where(function ($q) use ($spkQuery) {
                        $q->where('request_payload->spk_code', $spkQuery)
                          ->orWhere('message', 'like', "%{$spkQuery}%");
                    })
                    ->latest()
                    ->first();
            }

            if (!$apiLog) {
                $apiLog = ApiLog::where('api_name', 'sap_production_order_update')
                    ->latest()
                    ->first();
            }

            if ($apiLog) {
                $previewData = [
                    'spk_code'     => $apiLog->request_payload['spk_code'] ?? ($spkQuery ?: 'N/A'),
                    'endpoint'     => $apiLog->endpoint ?: (rtrim(config('services.sap.base_url', 'http://localhost:9000'), '/') . '/api/sap_production_order/update'),
                    'method'       => $apiLog->method ?: 'POST',
                    'payload'      => $apiLog->request_payload ?: [],
                    'payload_json' => preg_replace_callback('/:\s*([0-9]+\.?[0-9]*[eE][-+]?[0-9]+)/', function ($m) {
                        return ': ' . rtrim(rtrim(sprintf('%.8f', (float) $m[1]), '0'), '.');
                    }, json_encode($apiLog->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                    'result'       => $apiLog->response_payload,
                    'error'        => $apiLog->status === 'FAILED' ? $apiLog->message : null,
                    'status'       => $apiLog->status ?: 'COMPLETED',
                    'timestamp'    => $apiLog->created_at ? $apiLog->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                ];
            } else {
                // Contoh dummy jika belum pernah ada request
                $samplePayload = [
                    'spk_code' => $spkQuery ?: '250012345',
                    'lines' => [
                        [
                            'item_code' => 'RM-STEEL-001',
                            'plan_qty'  => 250,
                        ],
                        [
                            'item_code' => 'RM-PAINT-020',
                            'base_qty'  => 0.15,
                            'plan_qty'  => 15,
                            'warehouse' => 'WH-RM02',
                        ],
                        [
                            'item_code' => 'RM-PAINT-010',
                            'delete'    => true,
                        ],
                    ],
                ];

                $previewData = [
                    'spk_code'     => $samplePayload['spk_code'],
                    'endpoint'     => rtrim(config('services.sap.base_url', 'http://localhost:9000'), '/') . '/api/sap_production_order/update',
                    'method'       => 'POST',
                    'payload'      => $samplePayload,
                    'payload_json' => json_encode($samplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    'result'       => null,
                    'error'        => null,
                    'status'       => 'DRAFT / EXAMPLE',
                    'timestamp'    => now()->format('Y-m-d H:i:s'),
                ];
            }
        }

        return view('spk-bom.payload-preview', compact('previewData'));
    }
}
