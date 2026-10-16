<?php

namespace App\Services;

use App\Models\MasterListItem;
use App\Models\SpkBomChangeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpkMasterService extends BaseSapService
{
    public function getAll()
    {
        $route = '/api/sap_production_order/list';

        $rawData = [];

        $response = $this->get($route);
        // dd($response);
        $data = $this->normalizeResponse($response, 'SPK');
        // dd($data);
        // $spkCodes = collect($data)->pluck('SPKNo')->toArray();
        // dd($spkCodes);

        return $this->transformData($data);
    }

    private function transformData(array $data)
    {
        $data = collect($data)->map(function ($item) {
            if (! is_array($item)) {
                return [];
            }

            // Format PostDate
            if (! empty($item['PostDate'])) {
                $item['PostDate'] = Carbon::createFromFormat('d/m/Y', $item['PostDate'])->format('Y-m-d');
            }

            // Format DueDate
            if (! empty($item['DueDate'])) {
                $item['DueDate'] = Carbon::createFromFormat('d/m/Y', $item['DueDate'])->format('Y-m-d');
            }

            // Bersihin PlannedQty & CompletedQty dari titik/koma
            if (isset($item['PlannedQty'])) {
                $item['PlannedQty'] = preg_replace('/[.,]/', '', $item['PlannedQty']);
            }
            if (isset($item['CompletedQty'])) {
                $item['CompletedQty'] = preg_replace('/[.,]/', '', $item['CompletedQty']);
            }

            return $item;
        })->filter();

        return $data->values()->all();
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
        try {
            $spkData = $this->getAll();

            // 1. Fetch current existing SPKs from spk_masters before truncate
            $oldSpks = DB::table('spk_masters')->get()->keyBy('spk_number');

            // 2. Generate a unique batch ID for this sync
            $batchId = 'SYNC-' . now('Asia/Jakarta')->format('YmdHis');

            // 3. Map new SPKs key-by SPKNo
            $newSpkMap = [];
            foreach ($spkData as $row) {
                $spkNo = (string) ($row['SPKNo'] ?? '');
                if ($spkNo !== '') {
                    $newSpkMap[$spkNo] = $row;
                }
            }

            $changeLogs = [];
            $now = now();

            // Check for NEW, QTY_CHANGE, STATUS_CHANGE
            foreach ($newSpkMap as $spkNo => $row) {
                $itemCode = $row['ItemCode'] ?? null;
                $newPlanned = isset($row['PlannedQty']) ? (int) $row['PlannedQty'] : null;
                $newCompleted = isset($row['CompletedQty']) ? (int) $row['CompletedQty'] : null;
                $newStatus = $row['Status'] ?? null;

                if (! isset($oldSpks[$spkNo])) {
                    // NEW SPK
                    $changeLogs[] = [
                        'sync_batch_id' => $batchId,
                        'spk_number' => $spkNo,
                        'item_code' => $itemCode,
                        'change_type' => 'NEW',
                        'old_planned_qty' => null,
                        'new_planned_qty' => $newPlanned,
                        'old_completed_qty' => null,
                        'new_completed_qty' => $newCompleted,
                        'old_status' => null,
                        'new_status' => $newStatus,
                        'details' => json_encode(['note' => 'SPK Baru dirilis dari SAP']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    $old = $oldSpks[$spkNo];
                    $oldPlanned = (int) $old->planned_quantity;
                    $oldCompleted = (int) $old->completed_quantity;
                    $oldStatus = (string) $old->production_status;

                    $isQtyChanged = ($oldPlanned !== $newPlanned) || ($oldCompleted !== $newCompleted);
                    $isStatusChanged = ($oldStatus !== (string) $newStatus);

                    if ($isQtyChanged || $isStatusChanged) {
                        $changeType = $isQtyChanged ? 'QTY_CHANGE' : 'STATUS_CHANGE';
                        $changeLogs[] = [
                            'sync_batch_id' => $batchId,
                            'spk_number' => $spkNo,
                            'item_code' => $itemCode ?: $old->item_code,
                            'change_type' => $changeType,
                            'old_planned_qty' => $oldPlanned,
                            'new_planned_qty' => $newPlanned,
                            'old_completed_qty' => $oldCompleted,
                            'new_completed_qty' => $newCompleted,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'details' => json_encode([
                                'planned_diff' => $newPlanned - $oldPlanned,
                                'completed_diff' => $newCompleted - $oldCompleted,
                            ]),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            // Check for REMOVED SPK (in old, not in new)
            foreach ($oldSpks as $spkNo => $old) {
                if (! isset($newSpkMap[$spkNo])) {
                    $changeLogs[] = [
                        'sync_batch_id' => $batchId,
                        'spk_number' => $spkNo,
                        'item_code' => $old->item_code,
                        'change_type' => 'REMOVED',
                        'old_planned_qty' => (int) $old->planned_quantity,
                        'new_planned_qty' => null,
                        'old_completed_qty' => (int) $old->completed_quantity,
                        'new_completed_qty' => null,
                        'old_status' => $old->production_status,
                        'new_status' => 'CLOSED/REMOVED',
                        'details' => json_encode(['note' => 'SPK tidak ditemukan di sync SAP terbaru']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Insert change logs if any
            if (! empty($changeLogs)) {
                DB::table('spk_change_logs')->insert($changeLogs);
            }

            // Siapkan payload data baru sebelum truncate agar jeda tabel kosong seminimal mungkin
            $newSpkRecords = [];
            foreach ($spkData as $row) {
                $newSpkRecords[] = [
                    'spk_number' => $row['SPKNo'],
                    'post_date' => $row['PostDate'],
                    'due_date' => $row['DueDate'],
                    'production_status' => $row['Status'],
                    'item_code' => $row['ItemCode'],
                    'planned_quantity' => $row['PlannedQty'],
                    'completed_quantity' => $row['CompletedQty'],
                    'warehouse' => $row['Warehouse'],
                ];
            }

            // Guard: Cegah data-loss jika respons SAP kosong
            if (empty($newSpkRecords)) {
                Log::warning('[SPK_SYNC] Data SPK dari SAP kosong. Truncate dibatalkan demi perlindungan data.');
                DB::table('api_logs')->insert([
                    'api_name' => 'SPK_SYNC',
                    'method' => 'GET',
                    'endpoint' => $this->baseUrl . '/api/sap_production_order/list',
                    'status_code' => 200,
                    'status' => 'warning',
                    'message' => 'Data SPK dari SAP kosong. Truncate dibatalkan demi perlindungan data.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Data SPK dari SAP kosong. Truncate dibatalkan demi perlindungan data.',
                    'changes_count' => 0,
                    'batch_id' => $batchId,
                ], 422);
            }

            // Hapus data lama & simpan data baru ke spk_masters secara bulk
            DB::table('spk_masters')->truncate();
            foreach (array_chunk($newSpkRecords, 500) as $chunk) {
                DB::table('spk_masters')->insert($chunk);
            }

            // Simpan log sukses
            DB::table('api_logs')->insert([
                'api_name' => 'SPK_SYNC',
                'method' => 'GET',
                'endpoint' => $this->baseUrl . '/api/sap_production_order/list',
                'status_code' => 200,
                'status' => 'success',
                'message' => 'Data SPK berhasil disinkronkan. Terdeteksi ' . count($changeLogs) . ' perubahan.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data SPK berhasil disinkronkan.',
                'changes_count' => count($changeLogs),
                'batch_id' => $batchId,
            ]);
        } catch (\Exception $e) {
            DB::table('api_logs')->insert([
                'api_name' => 'SPK_SYNC',
                'method' => 'GET',
                'endpoint' => $this->baseUrl . '/api/sap_production_order/list',
                'status_code' => 500,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Gagal sinkron data SPK', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Kirim permintaan update rincian material (BOM lines) pada SPK / Production Order ke SAP
     *
     * Endpoint: POST /api/sap_production_order/update
     * Payload:
     * {
     *   "spk_code": "250012345",
     *   "lines": [
     *     { "item_code": "RM-STEEL-001", "plan_qty": 250 },
     *     { "item_code": "RM-PAINT-020", "base_qty": 0.15, "plan_qty": 15, "warehouse": "WH-RM02" },
     *     { "item_code": "RM-PAINT-010", "delete": true }
     *   ]
     * }
     */
    public function updateProductionOrderLines(string $spkCode, array $lines, ?int $userId = null, ?string $userName = null): array
    {
        $spkCode = trim($spkCode);
        if ($spkCode === '' || empty($lines)) {
            throw new \InvalidArgumentException('SPK code and lines payload cannot be empty.');
        }

        $cleanLines = [];
        foreach ($lines as $line) {
            $itemCode = trim($line['item_code'] ?? '');
            if ($itemCode === '') {
                continue;
            }

            $entry = [
                'item_code' => $itemCode,
            ];

            if (! empty($line['delete'])) {
                $entry['delete'] = true;
            } else {
                if (isset($line['base_qty']) && $line['base_qty'] !== null && $line['base_qty'] !== '') {
                    $entry['base_qty'] = (float) $line['base_qty'];
                }
                if (isset($line['plan_qty']) && $line['plan_qty'] !== null && $line['plan_qty'] !== '') {
                    $entry['plan_qty'] = (float) $line['plan_qty'];
                }
                if (! empty($line['warehouse'])) {
                    $entry['warehouse'] = trim($line['warehouse']);
                }
            }

            // Metainfo opsional untuk tracking audit internal (tidak dikirim ke SAP)
            if (isset($line['old_plan_qty'])) {
                $entry['_old_plan_qty'] = (float) $line['old_plan_qty'];
            }
            if (! empty($line['action_type'])) {
                $entry['_action_type'] = $line['action_type'];
            }
            if (! empty($line['replaced_item_code'])) {
                $entry['_replaced_item_code'] = $line['replaced_item_code'];
            }

            $cleanLines[] = $entry;
        }

        if (empty($cleanLines)) {
            throw new \InvalidArgumentException('No valid lines provided for update.');
        }

        // Payload murni untuk SAP (filter keluar underscore keys)
        $sapLines = array_map(function ($line) {
            return array_filter($line, function ($key) {
                return ! str_starts_with($key, '_');
            }, ARRAY_FILTER_USE_KEY);
        }, $cleanLines);

        $endpoint = '/api/sap_production_order/update';
        $payload = [
            'spk_code' => $spkCode,
            'lines' => $sapLines,
        ];

        $statusCode = null;
        $responseBody = null;
        $success = false;
        $message = '';
        $resultLines = [];

        try {
            $response = $this->post($endpoint, $payload);
            $statusCode = $response ? $response->status() : null;
            $responseBody = $response ? ($response->json() ?: $response->body()) : null;

            if ($response && $response->successful()) {
                $success = true;
                $message = is_array($responseBody) && isset($responseBody['message'])
                    ? $responseBody['message']
                    : "SPK {$spkCode} updated successfully.";
                $resultLines = is_array($responseBody) && isset($responseBody['lines'])
                    ? $responseBody['lines']
                    : [];
            } else {
                $message = is_array($responseBody) && isset($responseBody['message'])
                    ? $responseBody['message']
                    : "Gagal update SPK {$spkCode} di SAP: " . (is_string($responseBody) ? $responseBody : json_encode($responseBody));
            }
        } catch (\Exception $e) {
            $statusCode = $statusCode ?: 500;
            $message = $e->getMessage();
            $responseBody = ['error' => $e->getMessage()];
            $success = false;
        }

        // 1. Simpan ke central api_logs
        $this->saveApiLog(
            'sap_production_order_update',
            'POST',
            $endpoint,
            $payload,
            $responseBody,
            $statusCode ?: 500,
            $success ? 'SUCCESS' : 'FAILED',
            $message
        );

        // 2. Simpan per-baris ke audit log spk_bom_change_logs
        $this->recordBomChangeLogs($spkCode, $cleanLines, $resultLines, $payload, $responseBody, $success, $message, $userId, $userName);

        if (! $success) {
            throw new \Exception($message);
        }

        return [
            'status' => true,
            'message' => $message,
            'lines' => $resultLines,
        ];
    }

    /**
     * Catat rincian perubahan ke tabel spk_bom_change_logs
     */
    protected function recordBomChangeLogs(
        string $spkCode,
        array $cleanLines,
        array $resultLines,
        array $payload,
        mixed $responseBody,
        bool $success,
        string $generalMessage,
        ?int $userId,
        ?string $userName
    ): void {
        $resultByItem = collect($resultLines)->keyBy('item_code')->toArray();

        foreach ($cleanLines as $line) {
            $itemCode = $line['item_code'];
            $resLine = $resultByItem[$itemCode] ?? null;
            $action = $resLine['action'] ?? null;

            $actionType = $line['_action_type'] ?? null;
            if (! $actionType) {
                if (! empty($line['delete'])) {
                    $actionType = 'DELETE_MATERIAL';
                } elseif ($action === 'added' || isset($line['base_qty'])) {
                    $actionType = 'ADD_MATERIAL';
                } else {
                    $actionType = 'UPDATE_QTY';
                }
            }

            $itemName = MasterListItem::where('item_code', $itemCode)->value('item_name');

            SpkBomChangeLog::create([
                'spk_number' => $spkCode,
                'action_type' => $actionType,
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'replaced_item_code' => $line['_replaced_item_code'] ?? null,
                'base_qty' => $line['base_qty'] ?? null,
                'plan_qty' => $line['plan_qty'] ?? null,
                'old_plan_qty' => $line['_old_plan_qty'] ?? null,
                'warehouse' => $line['warehouse'] ?? null,
                'status' => $success ? 'SUCCESS' : 'FAILED',
                'message' => $generalMessage,
                'payload' => $line,
                'response' => $resLine ?: $responseBody,
                'created_by' => $userId,
                'created_by_name' => $userName,
            ]);
        }
    }

    /**
     * Helper cepat untuk Update Plan Qty (dan opsional Base Qty)
     */
    public function updateMaterialQty(string $spkCode, string $itemCode, float $newPlanQty, ?float $baseQty = null, ?float $oldPlanQty = null, ?int $userId = null, ?string $userName = null): array
    {
        $line = [
            'item_code' => $itemCode,
            'plan_qty' => $newPlanQty,
            '_old_plan_qty' => $oldPlanQty,
            '_action_type' => 'UPDATE_QTY',
        ];
        if ($baseQty !== null) {
            $line['base_qty'] = $baseQty;
        }

        return $this->updateProductionOrderLines($spkCode, [$line], $userId, $userName);
    }

    /**
     * Helper cepat untuk Tambah Material Baru
     */
    public function addNewMaterial(string $spkCode, string $itemCode, float $planQty, ?float $baseQty = null, ?string $warehouse = null, ?int $userId = null, ?string $userName = null): array
    {
        $line = [
            'item_code' => $itemCode,
            'plan_qty' => $planQty,
            '_action_type' => 'ADD_MATERIAL',
        ];
        if ($baseQty !== null) {
            $line['base_qty'] = $baseQty;
        }
        if ($warehouse !== null && trim($warehouse) !== '') {
            $line['warehouse'] = trim($warehouse);
        }

        return $this->updateProductionOrderLines($spkCode, [$line], $userId, $userName);
    }

    /**
     * Helper cepat untuk Delete Material
     */
    public function deleteMaterial(string $spkCode, string $itemCode, ?int $userId = null, ?string $userName = null): array
    {
        $line = [
            'item_code' => $itemCode,
            'delete' => true,
            '_action_type' => 'DELETE_MATERIAL',
        ];

        return $this->updateProductionOrderLines($spkCode, [$line], $userId, $userName);
    }

    /**
     * Helper cepat untuk Ganti Material (Delete Lama + Add Baru)
     */
    public function replaceMaterial(string $spkCode, string $oldItemCode, string $newItemCode, float $newPlanQty, ?float $baseQty = null, ?string $warehouse = null, ?int $userId = null, ?string $userName = null): array
    {
        $lines = [
            [
                'item_code' => $oldItemCode,
                'delete' => true,
                '_action_type' => 'REPLACE_MATERIAL',
                '_replaced_item_code' => $newItemCode,
            ],
            [
                'item_code' => $newItemCode,
                'plan_qty' => $newPlanQty,
                'base_qty' => $baseQty,
                'warehouse' => $warehouse,
                '_action_type' => 'REPLACE_MATERIAL',
                '_replaced_item_code' => $oldItemCode,
            ],
        ];

        return $this->updateProductionOrderLines($spkCode, $lines, $userId, $userName);
    }
}
