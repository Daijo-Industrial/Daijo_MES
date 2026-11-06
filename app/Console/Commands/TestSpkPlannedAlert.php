<?php

namespace App\Console\Commands;

use App\Services\SpkMasterService;
use Illuminate\Console\Command;

class TestSpkPlannedAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spk:test-planned-alert
                            {--email=* : Alamat email penerima tambahan untuk pengujian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email uji coba notifikasi perubahan planned quantity SPK ke Intan & Sisca';

    /**
     * Execute the console command.
     */
    public function handle(SpkMasterService $spkService)
    {
        $this->info('🧪 Menyiapkan data uji coba notifikasi Planned Qty SPK...');

        $extraEmails = (array) $this->option('email');
        $configuredRecipients = config('services.sap.spk_planned_change_recipients', ['intan@daijo.co.id', 'sisca@daijo.co.id']);
        $recipients = ! empty($extraEmails) ? $extraEmails : $configuredRecipients;

        $batchId = 'TEST-SYNC-' . now('Asia/Jakarta')->format('YmdHis');

        $dummyChanges = [
            [
                'spk_number' => '26030890',
                'item_code' => '1360A153.',
                'old_planned_qty' => 500,
                'new_planned_qty' => 450,
                'old_status' => 'R',
                'new_status' => 'R',
                'created_at' => now(),
            ],
            [
                'spk_number' => '26031204',
                'item_code' => 'CFTA-A705QBKZ',
                'old_planned_qty' => 3000,
                'new_planned_qty' => 3300,
                'old_status' => 'R',
                'new_status' => 'R',
                'created_at' => now(),
            ],
        ];

        $this->info('Mengirim email ke: ' . implode(', ', $recipients));

        $spkService->notifyPlannedQuantityChanges($dummyChanges, $batchId, true, $recipients);

        $this->info('✓ Perintah kirim email uji coba selesai dieksekusi. Silakan periksa inbox penerima.');

        return Command::SUCCESS;
    }
}

