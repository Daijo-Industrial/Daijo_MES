<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        $schedule->command('summary:generate')->everyTenMinutes()->withoutOverlapping();
        // PASTIKAN PRODUCTION SUMMARY SUDAH DI 1 dulu semua sebelum RUN
        $schedule->command('sap:dispatch-receipt')->everyTenMinutes()->withoutOverlapping()->appendOutputTo(storage_path('logs/sap_dispatch.log'));

        // Push quality data shift 1 setiap hari jam 07:35 WIB
        // $schedule->command('quality:push-shift1')
        //     ->dailyAt('07:35')
        //     ->timezone('Asia/Jakarta')
        //     ->onOneServer();

        // // Push quality data shift 2 setiap hari jam 15:35 WIB
        // $schedule->command('quality:push-shift2')
        //     ->dailyAt('15:35')
        //     ->timezone('Asia/Jakarta')
        //     ->onOneServer();

        // // Push quality data shift 3 setiap hari jam 22:35 WIB
        // $schedule->command('quality:push-shift3')
        //     ->dailyAt('22:35')
        //     ->timezone('Asia/Jakarta')
        //     ->onOneServer();

        //START HARI SENIN 10 menit setelah update

        $schedule->command('delsched:run')
            ->dailyAt('08:10')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('delsched:run')
            ->dailyAt('14:10')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('delivery:send')
            ->dailyAt('09:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('delivery:send')
            ->dailyAt('15:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('sync:delivery-data')->dailyAt('08:15')->timezone('Asia/Jakarta');
        $schedule->command('sync:delivery-data')->dailyAt('14:00')->timezone('Asia/Jakarta');
        $schedule->command('sync:delivery-data')->dailyAt('16:30')->timezone('Asia/Jakarta');
        // $schedule->command('app:send-daily-waiting-purchase-orders')->dailyAt('01:00'); // Adjust time as needed
        // $schedule->command('report:send-outstanding')
        //     ->dailyAt('09:00')
        //     ->timezone('Asia/Jakarta'); // or your preferred timezone

        // Sinkronisasi data SPK dari SAP setiap 10 menit (selaras dengan sap:dispatch-receipt)
        $schedule->command('spk:sync')
            ->everyTenMinutes()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/spk_sync.log'));

        // Otomatis backup database MySQL setiap hari jam 21:00 WIB (9 malam, menyimpan 14 hari terakhir)
        $schedule->command('db:backup --keep-days=14')
            ->dailyAt('21:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/db_backup.log'));

        // Otomatis bersihkan data api_logs yang lebih tua dari 3 bulan (dijalankan setiap hari jam 02:00 WIB)
        $schedule->command('model:prune', [
            '--model' => [\App\Models\ApiLog::class],
        ])
            ->dailyAt('02:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        // ISO 27001 & GDPR: Otomatis bersihkan activity_log yang lebih tua dari 365 hari (dijalankan setiap hari jam 01:00 WIB)
        $schedule->command('activitylog:clean')
            ->dailyAt('01:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
