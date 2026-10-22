<?php

namespace App\Console\Commands;

use App\Services\SpkMasterService;
use Illuminate\Console\Command;

class SyncSpkData extends Command
{
    protected $signature = 'spk:sync';

    protected $description = 'Sync SPK data from SAP';

    protected $spkService;

    public function __construct(SpkMasterService $spkService)
    {
        parent::__construct();
        $this->spkService = $spkService;
    }

    public function handle()
    {
        $this->info('Starting SPK sync from SAP...');
        $response = $this->spkService->SyncData();

        $statusCode = $response->getStatusCode();
        $payload = $response->getData(true);

        if ($statusCode === 200 && ($payload['success'] ?? false)) {
            $this->info('✓ ' . ($payload['message'] ?? 'SPK sync completed successfully.'));
            if (isset($payload['changes_count'])) {
                $this->info("  Perubahan terdeteksi: {$payload['changes_count']}");
            }

            return Command::SUCCESS;
        }

        $error = $payload['error'] ?? $payload['message'] ?? 'Unknown error occurred';
        $this->error("✗ SPK Sync Gagal [Status {$statusCode}]: {$error}");

        return Command::FAILURE;
    }
}
