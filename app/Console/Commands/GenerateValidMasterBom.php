<?php

namespace App\Console\Commands;

use App\Services\MasterBomSyncService;
use Illuminate\Console\Command;

class GenerateValidMasterBom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bom:generate-valid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan data staging master_boms dan simpan ke 2 tabel produksi (Header FG & Detail Multilevel WIP/Material)';

    /**
     * Execute the console command.
     */
    public function handle(MasterBomSyncService $service): int
    {
        $this->info('Memulai pembersihan & sinkronisasi Master BOM ke 2 tabel produksi...');

        $startTime = microtime(true);
        $result = $service->syncAll();
        $duration = round(microtime(true) - $startTime, 2);

        if ($result['status'] === 'empty') {
            $this->warn($result['message']);

            return Command::FAILURE;
        }

        $this->info("✅ {$result['message']} (Waktu: {$duration} detik)");
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Finished Goods (FG Headers)', number_format($result['fgs_count'])],
                ['Total Struktur Komponen Multilevel', number_format($result['components_count'])],
                ['Total Ranting WIP / Sub-Assembly', number_format($result['wips_count'])],
                ['Total Bahan Baku / Packaging / Chemical', number_format($result['raws_count'])],
                ['Anomali Dummy SAP yang Dilewati', implode(', ', $result['anomalies']) ?: 'Tidak ada'],
            ]
        );

        return Command::SUCCESS;
    }
}
