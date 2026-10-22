<?php

namespace App\Jobs;

use App\Services\ReceiptProductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushAllReceiptProductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Handle the job.
     */
    public function handle(ReceiptProductionService $service)
    {
        Log::channel('single')->info('[JOB] PushAllReceiptProductionJob STARTED at ' . now());

        try {
            // Langsung call, jangan check return value
            $service->pushAllUnprocessed();
            Log::channel('single')->info('[JOB] PushAllReceiptProductionJob DONE ✅');
        } catch (Throwable $e) {
            Log::channel('single')->error('[JOB] CRASHED ❌: ' . $e->getMessage());
            throw $e;
        }

        Log::channel('single')->info('[JOB] PushAllReceiptProductionJob FINISHED at ' . now());
    }
}
