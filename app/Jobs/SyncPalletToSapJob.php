<?php

namespace App\Jobs;

use App\Services\WmsSapSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPalletToSapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $palletId;

    /**
     * Create a new job instance.
     */
    public function __construct($palletId)
    {
        $this->palletId = $palletId;
    }

    /**
     * Execute the job.
     */
    public function handle(WmsSapSyncService $service): void
    {
        Log::info("Syncing Pallet {$this->palletId} to SAP via Inventory Transfer.");
        $service->syncPalletInventoryTransfer($this->palletId);
    }
}
