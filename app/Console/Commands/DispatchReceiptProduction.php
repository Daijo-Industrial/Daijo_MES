<?php

namespace App\Console\Commands;

use App\Jobs\PushAllReceiptProductionJob;
use Illuminate\Console\Command;

class DispatchReceiptProduction extends Command
{
    protected $signature = 'sap:dispatch-receipt';

    protected $description = 'Dispatch all unprocessed production scanned data to SAP';

    public function handle()
    {
        \Log::info('[SCHEDULE] sap:dispatch-receipt triggered at ' . now());

        PushAllReceiptProductionJob::dispatch();

        $this->info('Dispatched PushAllReceiptProductionJob to queue.');
        \Log::info('[SCHEDULE] Job dispatched to queue.');
    }
}
