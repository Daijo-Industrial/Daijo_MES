<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncWmsAllLayouts extends Command
{
    protected $signature = 'wms:sync-all-layouts';

    protected $description = 'Sync all physical layouts and rack coordinates for all warehouses (GUB, GUA, J06)';

    public function handle()
    {
        $this->info('====================================================');
        $this->info('  Memulai Sinkronisasi Seluruh Layout Gudang WMS    ');
        $this->info('====================================================');

        $this->info("\n1. Sinkronisasi Gudang Utama B (GUB)...");
        $this->call('wms:sync-gub-layout');

        $this->info("\n2. Sinkronisasi Gudang Utama A (GUA)...");
        $this->call('wms:sync-gua-layout');

        $this->info("\n3. Sinkronisasi Gudang 06 (J06)...");
        $this->call('wms:sync-j06-layout');

        $this->info("\n====================================================");
        $this->info('  Semua Layout Gudang Berhasil Disinkronkan!        ');
        $this->info('====================================================');

        return Command::SUCCESS;
    }
}
