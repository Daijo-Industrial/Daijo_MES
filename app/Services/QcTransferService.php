<?php

namespace App\Services;

use App\Models\QcTransferLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QcTransferService extends BaseSapService
{
    protected $endpoint = '/api/inventory_transfer/create';

    /**
     * Process inspection for a single box (production_scanned_data row)
     * and execute SAP inventory transfers atomically.
     */
    public function processSingleBoxInspection(
        int $scannedDataId,
        int $ngQty,
        ?int $userId = null,
        ?string $remarks = null,
        bool $isKbn = false,
        bool $skipSummaryStatusUpdate = false
    ): array {
        return DB::transaction(function () use ($scannedDataId, $ngQty, $userId, $remarks, $isKbn, $skipSummaryStatusUpdate) {
            $scannedData = DB::table('production_scanned_data')->find($scannedDataId);
            if (! $scannedData) {
                return ['success' => false, 'message' => 'Data box tidak ditemukan.'];
            }

            // Check if already inspected (with lockForUpdate for strict concurrency control)
            $existingLog = QcTransferLog::where('scanned_data_id', $scannedDataId)->lockForUpdate()->first();
            if ($existingLog) {
                return ['success' => false, 'message' => "Box label {$scannedData->label} sudah pernah diinspeksi (Log #{$existingLog->id}). Hasil inspeksi final dan tidak dapat diubah."];
            }

            $summary = DB::table('production_summary')->find($scannedData->summary_id);
            if (! $summary || $summary->sap_sent != 1) {
                return ['success' => false, 'message' => 'Production summary belum terkirim/terverifikasi di SAP (sap_sent != 1).'];
            }

            $originalQty = (int) $scannedData->quantity;
            if ($ngQty < 0 || $ngQty > $originalQty) {
                return ['success' => false, 'message' => "Jumlah NG ({$ngQty}) tidak valid. Harus antara 0 sampai {$originalQty}."];
            }

            $okQty = $originalQty - $ngQty;
            $fromWh = strtoupper(trim($scannedData->warehouse ?: $summary->warehouse));

            if ($isKbn || $fromWh === 'FFI') {
                // Mode KBN: OK quantity TIDAK dikirim ke SAP, hanya Reject (NG) yang dikirim ke RJCT
                $okToWh = null;
                $okSapStatus = 1; // Mark OK as completed/skipped
                $ngToWh = $ngQty > 0 ? 'RJCT' : null;
                $ngSapStatus = $ngQty > 0 ? 0 : 1;
            } else {
                // Mode Karawang: OK -> KRFG, NG -> KRRJCT
                $whMap = QcTransferLog::WAREHOUSE_MAP[$fromWh] ?? ['ok' => 'KRFG', 'ng' => 'KRRJCT'];
                $okToWh = $whMap['ok'];
                $okSapStatus = $okQty > 0 ? 0 : 1;
                $ngToWh = $ngQty > 0 ? $whMap['ng'] : null;
                $ngSapStatus = $ngQty > 0 ? 0 : 1;
            }

            // 1. Create QcTransferLog entry
            $log = QcTransferLog::create([
                'production_summary_id' => $summary->id,
                'scanned_data_id' => $scannedData->id,
                'item_code' => $scannedData->item_code,
                'spk_code' => $scannedData->spk_code,
                'label' => $scannedData->label,
                'from_warehouse' => $fromWh,
                'original_qty' => $originalQty,
                'ok_qty' => $okQty,
                'ng_qty' => $ngQty,
                'ok_to_warehouse' => $okToWh,
                'ok_sap_status' => $okSapStatus,
                'ng_to_warehouse' => $ngToWh,
                'ng_sap_status' => $ngSapStatus,
                'inspected_by' => $userId,
                'remarks' => $remarks,
            ]);

            // 2. Push Transfers to SAP
            $transferResult = $this->executeSapTransfers($log);

            // 3. Recalculate summary qc_status (skip if batch processing)
            if (! $skipSummaryStatusUpdate) {
                $this->updateSummaryQcStatus($summary->id);
            }

            $sapSuccess = ($transferResult['ok_success'] ?? true) && ($transferResult['ng_success'] ?? true);
            $msg = $sapSuccess
                ? "Inspeksi box {$scannedData->label} berhasil diproses" . ($isKbn ? ' (Reject dikirim ke RJCT, OK tidak ditransfer).' : ' & terkirim ke SAP.')
                : "Inspeksi box {$scannedData->label} tersimpan, namun SAP Transfer Gagal: " . implode(', ', $transferResult['messages'] ?? []);

            return [
                'success' => $sapSuccess,
                'message' => $msg,
                'log' => $log->fresh(),
                'sap' => $transferResult,
            ];
        });
    }

    /**
     * Process inspection for multiple boxes in a summary at once.
     * $boxNgMap format: [ scanned_data_id => ng_qty, ... ]
     */
    public function processSummaryInspection(int $summaryId, array $boxNgMap, ?int $userId = null, ?string $remarks = null, bool $isKbn = false): array
    {
        $summary = DB::table('production_summary')->find($summaryId);
        if (! $summary || $summary->sap_sent != 1) {
            return ['success' => false, 'message' => 'Production summary tidak valid atau belum receipt ke SAP (sap_sent != 1).'];
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($boxNgMap as $scannedDataId => $ngQty) {
            $res = $this->processSingleBoxInspection((int) $scannedDataId, (int) $ngQty, $userId, $remarks, $isKbn, true);
            if ($res['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
            $results[$scannedDataId] = $res;
        }

        $this->updateSummaryQcStatus($summaryId);

        return [
            'success' => $failCount === 0,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'message' => "Berhasil memproses {$successCount} box" . ($failCount > 0 ? ", {$failCount} gagal/sudah pernah diinspeksi." : '.'),
            'details' => $results,
        ];
    }

    /**
     * Execute SAP Inventory Transfer for OK and NG items of a QcTransferLog
     */
    public function executeSapTransfers(QcTransferLog $log): array
    {
        $okResult = true;
        $ngResult = true;
        $messages = [];

        // 1. Transfer OK items (if ok_qty > 0 and ok_to_warehouse is defined and not yet transferred)
        if ($log->ok_qty > 0 && ! empty($log->ok_to_warehouse) && $log->ok_sap_status != 1) {
            $okResult = $this->sendTransferLineToSap(
                $log,
                'OK',
                $log->ok_qty,
                $log->from_warehouse,
                $log->ok_to_warehouse,
                'ok_sap_status',
                'ok_sap_error',
                'ok_sap_sent_at'
            );
            if (! $okResult['success']) {
                $messages[] = 'OK Transfer Gagal: ' . $okResult['message'];
            }
        }

        // 2. Transfer NG items (if ng_qty > 0 and not yet transferred)
        if ($log->ng_qty > 0 && $log->ng_sap_status != 1) {
            $ngResult = $this->sendTransferLineToSap(
                $log,
                'NG',
                $log->ng_qty,
                $log->from_warehouse,
                $log->ng_to_warehouse,
                'ng_sap_status',
                'ng_sap_error',
                'ng_sap_sent_at'
            );
            if (! $ngResult['success']) {
                $messages[] = 'NG Transfer Gagal: ' . $ngResult['message'];
            }
        }

        return [
            'ok_success' => $okResult === true || (is_array($okResult) && $okResult['success']),
            'ng_success' => $ngResult === true || (is_array($ngResult) && $ngResult['success']),
            'messages' => $messages,
        ];
    }

    /**
     * Send a single Inventory Transfer payload to SAP
     */
    private function sendTransferLineToSap(
        QcTransferLog $log,
        string $type,
        int $qty,
        string $fromWh,
        string $toWh,
        string $statusCol,
        string $errorCol,
        string $timeCol
    ): array {
        // Atomic status lock check: Only lock if status is 0 (Pending) or 2 (Failed).
        // If status is 3 (Processing) or 1 (Success), abort immediately to prevent duplicate SAP calls!
        $locked = QcTransferLog::where('id', $log->id)
            ->whereIn($statusCol, [0, 2])
            ->update([
                $statusCol => 3,
                $errorCol => "Sending {$type} Transfer to SAP...",
            ]);

        if (! $locked) {
            $currentStatus = DB::table('qc_transfer_logs')->where('id', $log->id)->value($statusCol);
            if ($currentStatus == 1) {
                return ['success' => true, 'message' => "Transfer {$type} sudah sukses sebelumnya."];
            }

            return ['success' => false, 'message' => "Transfer {$type} sedang diproses oleh request lain."];
        }

        $payload = [
            'docDate' => now()->format('Y-m-d'),
            'remarks' => "QC {$type} Transfer #{$log->id}: {$qty} pcs {$log->item_code} from {$fromWh} to {$toWh}",
            'lines' => [
                [
                    'itemCode' => $log->item_code,
                    'quantity' => (float) $qty,
                    'fromWarehouse' => $fromWh,
                    'toWarehouse' => $toWh,
                    'u_NUMPR' => (string) $log->spk_code,
                ],
            ],
        ];

        try {
            Log::info("QC {$type} Transfer Payload [Log #{$log->id}]: " . json_encode($payload));
            $response = $this->post($this->endpoint, $payload);
            $statusCode = $response->status();
            $rawBody = $response->body();
            $json = $response->json();

            $success = $response->successful() && isset($json['status']) && $json['status'] === true;
            $errorMsg = $json['message'] ?? ($rawBody ?: "HTTP {$statusCode} error");
            $isDuplicate = (stripos($errorMsg, 'already exist') !== false || stripos($errorMsg, 'duplicate') !== false);

            if ($success || $isDuplicate) {
                $log->update([
                    $statusCol => 1,
                    $errorCol => $isDuplicate ? 'SAP: ' . $errorMsg : null,
                    $timeCol => now(),
                ]);

                $logMsg = "QC {$type} Transfer #{$log->id} SUCCESS (" . ($isDuplicate ? 'Duplicate/Already Exists' : 'Synced') . ')';
                $this->saveApiLog('QC_Stock_Transfer_' . $type, 'POST', $this->endpoint, $payload, $json ?? ['body' => $rawBody], $statusCode, 'success', $logMsg);

                return ['success' => true, 'message' => $logMsg];
            } else {
                $displayError = "SAP Error [{$statusCode}]: " . $errorMsg;
                $log->update([
                    $statusCol => 2,
                    $errorCol => $displayError,
                    $timeCol => now(),
                ]);

                $this->saveApiLog('QC_Stock_Transfer_' . $type, 'POST', $this->endpoint, $payload, $json ?? ['body' => $rawBody], $statusCode, 'failed', $displayError);

                return ['success' => false, 'message' => $displayError];
            }
        } catch (\Throwable $e) {
            $log->update([
                $statusCol => 2,
                $errorCol => 'Exception: ' . $e->getMessage(),
                $timeCol => now(),
            ]);

            Log::error("QC {$type} Transfer Exception [Log #{$log->id}]: " . $e->getMessage());
            $this->saveApiLog('QC_Stock_Transfer_' . $type, 'POST', $this->endpoint, $payload, [], 500, 'failed', 'Exception: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Recalculate and update the qc_status of production_summary record
     * 0 = Belum diinspeksi
     * 1 = Selesai (semua box sudah diinspeksi dan ditransfer)
     * 2 = Partial (sebagian box diinspeksi)
     */
    public function updateSummaryQcStatus(int $summaryId): void
    {
        $totalBoxes = DB::table('production_scanned_data')
            ->where('summary_id', $summaryId)
            ->count();

        if ($totalBoxes === 0) {
            return;
        }

        $inspectedCount = QcTransferLog::where('production_summary_id', $summaryId)->count();

        if ($inspectedCount === 0) {
            $newStatus = 0;
        } elseif ($inspectedCount >= $totalBoxes) {
            $newStatus = 1; // Completed
        } else {
            $newStatus = 2; // Partial
        }

        DB::table('production_summary')
            ->where('id', $summaryId)
            ->update([
                'qc_status' => $newStatus,
                'updated_at' => now(),
            ]);
    }
}
