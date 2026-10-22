<?php

namespace App\Jobs;

use App\Services\ReceiptProductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushSingleReceiptProductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $summaryId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $summaryId)
    {
        $this->summaryId = $summaryId;
    }

    /**
     * Execute the job.
     */
    public function handle(ReceiptProductionService $service)
    {
        Log::channel('single')->info("[JOB] PushSingleReceiptProductionJob STARTED for Summary ID: {$this->summaryId} at " . now());

        try {
            $summary = DB::table('production_summary')
                ->where('id', $this->summaryId)
                ->first();

            if ($summary->sap_sent == 1) {
                Log::channel('single')->info("[JOB] Summary ID {$this->summaryId} skipped: Already successfully sent to SAP (sap_sent = 1).");

                return;
            }

            if ($summary->sap_sent == 99) {
                Log::channel('single')->info("[JOB] Summary ID {$this->summaryId} skipped: Marked as ignored (sap_sent = 99).");

                return;
            }

            // If not in processing status (2), attempt atomic lock from 0/3 to 2
            if ($summary->sap_sent != 2) {
                $locked = DB::table('production_summary')
                    ->where('id', $this->summaryId)
                    ->whereIn('sap_sent', [0, 3])
                    ->update(['sap_sent' => 2, 'updated_at' => now()]);

                if (! $locked) {
                    Log::channel('single')->warning("[JOB] Summary ID {$this->summaryId} skipped: Locked or processed by another worker.");

                    return;
                }
            }

            // Cari scanned data
            $scannedData = DB::table('production_scanned_data')
                ->where('spk_code', $summary->spk_code)
                ->first();

            if (! $scannedData) {
                Log::channel('single')->warning("[JOB] Scanned data not found for SPK: {$summary->spk_code}");
                // Mark as Failed (3)
                DB::table('production_summary')
                    ->where('id', $this->summaryId)
                    ->update(['sap_sent' => 3, 'updated_at' => now()]);

                return;
            }

            // Push to SAP
            $response = $service->pushSingleRecord($summary, $scannedData, true);

            if ($response['success']) {
                Log::channel('single')->info("[JOB] PushSingleReceiptProductionJob SUCCESS for Summary ID: {$this->summaryId}");
            } else {
                Log::channel('single')->error("[JOB] PushSingleReceiptProductionJob FAILED for Summary ID: {$this->summaryId} - " . ($response['message'] ?? ''));
            }
        } catch (Throwable $e) {
            Log::channel('single')->error("[JOB] PushSingleReceiptProductionJob CRASHED ❌ for Summary ID: {$this->summaryId}: " . $e->getMessage());

            // Mark as Failed (3) if we crashed so it doesn't get stuck in Processing
            DB::table('production_summary')
                ->where('id', $this->summaryId)
                ->where('sap_sent', 2)
                ->update(['sap_sent' => 3, 'updated_at' => now()]);

            throw $e;
        }
    }
}
