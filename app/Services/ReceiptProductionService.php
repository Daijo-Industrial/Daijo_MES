<?php

namespace App\Services;

use App\Mail\SapTimeoutAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReceiptProductionService extends BaseSapService
{
    protected $endpoint = '/api/receipt_production/create';

    public function pushAllUnprocessed()
    {
        // Ambil dari production_summary yang belum dikirim ke SAP
        // Exclude sap_sent=2 (sedang diproses worker lain) dan sap_sent=1 (sudah sukses)
        $records = DB::table('production_summary')
            ->where('sap_sent', 0)
            ->whereIn('warehouse', ['FFI', 'KRFFI'])
            ->get();

        \Log::info('Scheduler jalan, records count: ' . $records->count());

        if ($records->isEmpty()) {
            try {
                $this->saveApiLog(
                    'receipt_production',
                    'POST',
                    $this->endpoint,
                    [],
                    [],
                    204,
                    'failed',
                    'No unprocessed records found'
                );
                \Log::info('SaveApiLog sukses dibuat dari scheduler.');

                return;
            } catch (\Throwable $e) {
                \Log::error('Gagal saveApiLog: ' . $e->getMessage());
            }
        }

        Log::info('SAP Push START', ['summary_count' => $records->count()]);

        foreach ($records as $summary) {
            // === ATOMIC LOCK: Tandai sebagai PROCESSING (2) sebelum tembak SAP ===
            // Hanya update jika status masih 0 (Pending). Jika return 0 rows,
            // berarti worker/scheduler lain sudah mengambilnya — skip.
            $locked = DB::table('production_summary')
                ->where('id', $summary->id)
                ->where('sap_sent', 0)
                ->update(['sap_sent' => 2, 'updated_at' => now()]);

            if (! $locked) {
                Log::warning("[SAP Push] SPK {$summary->spk_code} SKIPPED - sudah diambil worker lain atau sudah diproses.");
                continue;
            }

            // Cari item_code dari production_scanned_data menggunakan spk_code
            $scannedData = DB::table('production_scanned_data')
                ->where('spk_code', $summary->spk_code)
                ->first();

            if (! $scannedData) {
                Log::warning('Scanned data not found for SPK', ['spk_code' => $summary->spk_code]);

                // Mark as Failed (3) instead of reverting to 0
                DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->where('sap_sent', 2)
                    ->update(['sap_sent' => 3, 'updated_at' => now()]);

                $this->saveApiLog(
                    'receipt_production',
                    'POST',
                    $this->endpoint,
                    [],
                    [],
                    404,
                    'failed',
                    'SPK ' . $summary->spk_code . ' - scanned data not found'
                );
                continue;
            }

            $payload = [
                [
                    'summary_id' => $summary->id,
                    'spk_code' => $summary->spk_code,
                    'item_code' => $scannedData->item_code,
                    'warehouse' => $summary->warehouse,
                    'quantity' => $summary->total_quantity,
                    'label' => (string) $summary->id,
                ],
            ];

            try {
                $response = $this->post($this->endpoint, $payload);

                $rawBody = $response->body();
                $json = $response->json();
                $status = $response->successful() && isset($json['status']) && $json['status'] === true;
                $errorMsg = $json['message'] ?? ($rawBody ?: 'SAP HTTP ' . $response->status() . ' error');
                $isDuplicate = (stripos($errorMsg, 'already exist') !== false || stripos($errorMsg, 'duplicate') !== false || stripos($errorMsg, 'sudah ada') !== false);

                if ($status || $isDuplicate) {
                    // Update dari status PROCESSING (2) ke SUCCESS (1)
                    DB::table('production_summary')
                        ->where('id', $summary->id)
                        ->update([
                            'sap_sent' => 1,
                            'sap_sent_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $logMsg = $isDuplicate ? "SPK {$summary->spk_code} marked as success (Duplicate/Already Exists)" : 'SPK ' . $summary->spk_code . ' sent to SAP successfully';
                    Log::info('SAP Push SUCCESS', [
                        'summary_id' => $summary->id,
                        'spk_code' => $summary->spk_code,
                        'payload' => $payload,
                        'response' => $json,
                        'is_duplicate' => $isDuplicate,
                    ]);

                    $this->saveApiLog(
                        'receipt_production',
                        'POST',
                        $this->endpoint,
                        $payload,
                        $json,
                        $response->status(),
                        'success',
                        $logMsg
                    );
                } else {
                    Log::error('SAP Push FAILED', [
                        'summary_id' => $summary->id,
                        'spk_code' => $summary->spk_code,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'json' => $json,
                    ]);

                    // Mark as Failed (3) instead of reverting to 0
                    DB::table('production_summary')
                        ->where('id', $summary->id)
                        ->where('sap_sent', 2)
                        ->update(['sap_sent' => 3, 'updated_at' => now()]);

                    $this->saveApiLog(
                        'receipt_production',
                        'POST',
                        $this->endpoint,
                        $payload,
                        $json,
                        $response->status(),
                        'failed',
                        'SPK ' . $summary->spk_code . ' failed: ' . $response->body()
                    );
                }
            } catch (\Throwable $e) {
                Log::error('SAP Push EXCEPTION', [
                    'summary_id' => $summary->id,
                    'spk_code' => $summary->spk_code,
                    'error' => $e->getMessage(),
                ]);

                // Mark as Failed (3) instead of reverting to 0
                DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->where('sap_sent', 2)
                    ->update(['sap_sent' => 3, 'updated_at' => now()]);

                $this->saveApiLog(
                    'receipt_production',
                    'POST',
                    $this->endpoint,
                    $payload,
                    [],
                    500,
                    'failed',
                    'SPK ' . $summary->spk_code . ' exception: ' . $e->getMessage()
                );
            }
        }
    }

    public function pushSingleRecord($summary, $scannedData, $alreadyLocked = false)
    {
        if (! $alreadyLocked) {
            // === ATOMIC LOCK untuk manual push ===
            // Boleh push jika status 0 (Pending) ATAU 3 (Failed - retry)
            $locked = DB::table('production_summary')
                ->where('id', $summary->id)
                ->whereIn('sap_sent', [0, 3])
                ->update(['sap_sent' => 2, 'updated_at' => now()]);

            if (! $locked) {
                $current = DB::table('production_summary')->where('id', $summary->id)->value('sap_sent');
                $statusMap = [1 => 'sudah terkirim ke SAP', 2 => 'sedang diproses', 3 => 'gagal (sedang di-retry)', 99 => 'diabaikan'];
                $reason = $statusMap[$current] ?? 'status tidak diketahui (code: ' . $current . ')';
                Log::warning("[SAP Push Manual] SPK {$summary->spk_code} SKIPPED - {$reason}.");

                return [
                    'success' => false,
                    'message' => "Tidak bisa dikirim: {$reason}.",
                ];
            }
        }

        // Double check in DB right before POSTing to SAP to prevent race condition
        $currentStatus = DB::table('production_summary')->where('id', $summary->id)->value('sap_sent');
        if ($currentStatus == 1) {
            Log::info("[SAP Push Single] SPK {$summary->spk_code} skipped: Already sent to SAP (sap_sent = 1).");

            return ['success' => true, 'message' => 'Sudah terkirim ke SAP'];
        }

        try {
            $payload = [
                [
                    'summary_id' => $summary->id,
                    'spk_code' => $summary->spk_code,
                    'item_code' => $scannedData->item_code,
                    'warehouse' => $summary->warehouse,
                    'quantity' => $summary->total_quantity,
                    'label' => (string) $summary->id,
                ],
            ];

            $response = $this->post($this->endpoint, $payload);
            $rawBody = $response->body();
            $json = $response->json();
            $status = $response->successful() && isset($json['status']) && $json['status'] === true;
            $errorMsg = $json['message'] ?? ($rawBody ?: 'SAP HTTP ' . $response->status() . ' error');
            $isDuplicate = (stripos($errorMsg, 'already exist') !== false || stripos($errorMsg, 'duplicate') !== false || stripos($errorMsg, 'sudah ada') !== false);

            if ($status || $isDuplicate) {
                // Update dari PROCESSING (2) ke SUCCESS (1)
                DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->update([
                        'sap_sent' => 1,
                        'sap_sent_at' => now(),
                        'updated_at' => now(),
                    ]);

                $logMsg = $isDuplicate ? "SPK {$summary->spk_code} marked as success (Duplicate/Already Exists)" : 'SPK ' . $summary->spk_code . ' sent to SAP successfully (Manual)';
                Log::info('SAP Push SUCCESS (Manual)', [
                    'summary_id' => $summary->id,
                    'spk_code' => $summary->spk_code,
                    'payload' => $payload,
                    'response' => $json,
                    'is_duplicate' => $isDuplicate,
                ]);

                $this->saveApiLog(
                    'receipt_production',
                    'POST',
                    $this->endpoint,
                    $payload,
                    $json,
                    $response->status(),
                    'success',
                    $logMsg
                );

                return [
                    'success' => true,
                    'message' => 'Berhasil dikirim ke SAP',
                    'response' => $json,
                ];
            } else {
                Log::error('SAP Push FAILED (Manual)', [
                    'summary_id' => $summary->id,
                    'spk_code' => $summary->spk_code,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $json,
                ]);

                // Mark as Failed (3) instead of reverting to 0
                DB::table('production_summary')
                    ->where('id', $summary->id)
                    ->where('sap_sent', 2)
                    ->update(['sap_sent' => 3, 'updated_at' => now()]);

                $this->saveApiLog(
                    'receipt_production',
                    'POST',
                    $this->endpoint,
                    $payload,
                    $json,
                    $response->status(),
                    'failed',
                    'SPK ' . $summary->spk_code . ' failed: ' . $response->body()
                );

                return [
                    'success' => false,
                    'message' => $response->body() ?? 'SAP returned error status',
                    'response' => $json,
                ];
            }

        } catch (\Throwable $e) {
            Log::error('SAP Push EXCEPTION (Manual)', [
                'summary_id' => $summary->id ?? null,
                'spk_code' => $summary->spk_code ?? null,
                'error' => $e->getMessage(),
            ]);

            // Mark as Failed (3) instead of reverting to 0
            DB::table('production_summary')
                ->where('id', $summary->id)
                ->where('sap_sent', 2)
                ->update(['sap_sent' => 3, 'updated_at' => now()]);

            $this->saveApiLog(
                'receipt_production',
                'POST',
                $this->endpoint,
                $payload ?? [],
                [],
                500,
                'failed',
                'SPK ' . ($summary->spk_code ?? 'unknown') . ' exception: ' . $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    public function saveApiLog($apiName, $method, $endpoint, $request, $response, $statusCode, $status, $message)
    {
        $logId = DB::table('api_logs')->insertGetId([
            'api_name' => $apiName,
            'method' => $method,
            'endpoint' => $endpoint,
            'request_payload' => is_string($request) ? $request : json_encode($request, JSON_PRETTY_PRINT),
            'response_payload' => is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT),
            'status_code' => $statusCode,
            'status' => $status,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($status === 'failed' && $this->isTimeoutError($message)) {
            $this->triggerTimeoutAlert($logId, $endpoint, $message, $request);
        }

        return $logId;
    }

    /**
     * Deteksi apakah pesan error merupakan masalah timeout koneksi ke SAP.
     */
    protected function isTimeoutError(?string $message): bool
    {
        if (empty($message)) {
            return false;
        }

        $lower = strtolower($message);

        return str_contains($lower, 'timed out')
            || str_contains($lower, 'curl error 28')
            || str_contains($lower, 'timeout');
    }

    /**
     * Kirim email peringatan timeout secara langsung (real-time) dengan proteksi cooldown 5 menit.
     */
    protected function triggerTimeoutAlert(int $logId, string $endpoint, string $errorMessage, $requestPayload): void
    {
        $cooldownKey = 'sap_receipt_timeout_alert_cooldown';

        if (Cache::has($cooldownKey)) {
            Log::info("SAP Receipt Production timeout alert dilewati: cooldown aktif.");
            return;
        }

        // Set cooldown 5 menit untuk mencegah spam puluhan email saat server SAP down
        Cache::put($cooldownKey, true, now()->addMinutes(5));

        // 1. Kirim notifikasi Email
        try {
            $recipients = config('services.sap.timeout_alert_recipients', [
                'andreas@daijo.co.id',
                'timo@daijo.co.id',
                'thomasilver88@gmail.com',
            ]);

            if (! empty($recipients)) {
                $logObj = (object) [
                    'id' => $logId,
                    'api_name' => 'receipt_production',
                    'endpoint' => $endpoint,
                    'message' => $errorMessage,
                    'created_at' => now(),
                ];

                $summary = [
                    'total_timeouts' => 1,
                    'first_occurred' => now('Asia/Jakarta')->format('d M Y H:i:s'),
                    'last_occurred' => now('Asia/Jakarta')->format('d M Y H:i:s'),
                    'minutes_window' => 1,
                    'endpoints_breakdown' => [
                        [
                            'api_name' => 'receipt_production',
                            'endpoint' => $endpoint,
                            'count' => 1,
                        ],
                    ],
                ];

                Mail::to($recipients)->send(new SapTimeoutAlertMail($summary, collect([$logObj]), false));

                Log::warning("[SAP-TIMEOUT-ALERT] Alert email real-time terkirim ke: " . implode(', ', $recipients));
            }
        } catch (\Throwable $mailEx) {
            Log::error("Gagal mengirim email alert SAP Timeout: " . $mailEx->getMessage());
        }

        // 2. Kirim notifikasi Telegram ke Grup
        try {
            \App\Services\TelegramAlertService::sendSapTimeoutAlert($endpoint, $errorMessage, $logId);
        } catch (\Throwable $teleEx) {
            Log::error("Gagal mengirim Telegram alert: " . $teleEx->getMessage());
        }
    }
}
