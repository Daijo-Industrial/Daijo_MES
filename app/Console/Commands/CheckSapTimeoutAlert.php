<?php

namespace App\Console\Commands;

use App\Mail\SapTimeoutAlertMail;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckSapTimeoutAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:alert-timeout 
                            {--minutes=5 : Rentang waktu pengecekan log dalam menit (default: 5)} 
                            {--force : Abaikan cooldown/cache last notified ID dan tetap kirim jika ada timeout} 
                            {--test : Kirim email tes langsung ke penerima untuk verifikasi} 
                            {--email=* : Alamat email penerima tambahan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa log timeout SAP di api_logs dan kirim notifikasi email ke penanggung jawab jika terdeteksi error.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Tentukan daftar penerima email
        $configuredRecipients = config('services.sap.timeout_alert_recipients', [
            'andreas@daijo.co.id',
            'timo@daijo.co.id',
        ]);

        if (empty($configuredRecipients)) {
            $configuredRecipients = ['andreas@daijo.co.id', 'timo@daijo.co.id'];
        }

        $extraEmails = (array) $this->option('email');
        $recipients = array_values(array_unique(array_merge($configuredRecipients, $extraEmails)));

        // 2. Mode Uji Coba (--test)
        if ($this->option('test')) {
            $this->info('🧪 Menjalankan dalam TEST MODE. Mengirim email uji coba...');

            $dummySummary = [
                'total_timeouts' => 1,
                'first_occurred' => now('Asia/Jakarta')->format('d M Y H:i:s'),
                'last_occurred' => now('Asia/Jakarta')->format('d M Y H:i:s'),
                'minutes_window' => 30,
                'endpoints_breakdown' => [
                    [
                        'api_name' => 'receipt_production',
                        'endpoint' => '/api/receipt_production/create',
                        'count' => 1,
                    ],
                ],
            ];

            $dummyLog = (object) [
                'id' => 999999,
                'created_at' => now(),
                'api_name' => 'receipt_production',
                'endpoint' => '/api/receipt_production/create',
                'message' => 'cURL error 28: Operation timed out after 30002 milliseconds with 0 bytes received for http://192.168.6.149:9001/auth/token',
            ];

            try {
                Mail::to($recipients)->send(new SapTimeoutAlertMail($dummySummary, collect([$dummyLog]), true));
                $this->info('✅ Email uji coba berhasil dikirim ke: ' . implode(', ', $recipients));

                // Kirim notifikasi uji coba ke Telegram
                $testTeleMsg = "🧪 *[TEST] NOTIFIKASI UJI COBA BOT TELEGRAM DAIJO MES*\n\n"
                    . "Pengujian integrasi bot Telegram via `php artisan sap:alert-timeout --test` *BERHASIL*.\n"
                    . "Sistem siap mengirimkan alert jika terjadi timeout pada API SAP.\n\n"
                    . "⏱️ *Waktu Uji:* " . now('Asia/Jakarta')->format('d M Y H:i:s') . " WIB";
                $teleSent = \App\Services\TelegramAlertService::sendMessage($testTeleMsg);
                if ($teleSent) {
                    $this->info('✅ Notifikasi Telegram uji coba berhasil dikirim ke grup.');
                } else {
                    $this->warn('⚠️ Notifikasi Telegram uji coba gagal terkirim (cek storage/logs/laravel.log).');
                }

                return 0;
            } catch (\Throwable $e) {
                $this->error('❌ Gagal mengirim email uji coba: ' . $e->getMessage());
                Log::error('Gagal mengirim test email sap:alert-timeout: ' . $e->getMessage());

                return 1;
            }
        }

        // 3. Normal Mode: Query log timeout dari api_logs
        $minutes = max(1, (int) $this->option('minutes') ?: 5);
        $since = now()->subMinutes($minutes);

        $query = ApiLog::query()
            ->where('created_at', '>=', $since)
            ->where(function ($q) {
                $q->where('message', 'like', '%timed out%')
                    ->orWhere('message', 'like', '%cURL error 28%')
                    ->orWhere('message', 'like', '%timeout%')
                    ->orWhere('message', 'like', '%TIMEOUT%');
            });

        // Cegah spam: jangan kirim ulang ID log yang sudah pernah dinotifikasikan
        $lastNotifiedId = (int) Cache::get('sap_timeout_last_notified_id', 0);
        if (! $this->option('force') && $lastNotifiedId > 0) {
            $query->where('id', '>', $lastNotifiedId);
        }

        $timeoutLogs = $query->orderBy('id', 'desc')->get();

        if ($timeoutLogs->isEmpty()) {
            $this->info("ℹ️ Tidak ada error timeout SAP baru dalam {$minutes} menit terakhir.");

            return 0;
        }

        // 4. Bangun ringkasan statistik
        $totalTimeouts = $timeoutLogs->count();
        $firstOccurred = Carbon::parse($timeoutLogs->min('created_at'))->timezone('Asia/Jakarta')->format('d M Y H:i:s');
        $lastOccurred = Carbon::parse($timeoutLogs->max('created_at'))->timezone('Asia/Jakarta')->format('d M Y H:i:s');

        $endpointsBreakdown = $timeoutLogs->groupBy(function ($log) {
            return ($log->api_name ?: 'Unknown') . '|||' . ($log->endpoint ?: '-');
        })->map(function ($group) {
            return [
                'api_name' => $group->first()->api_name ?: 'Unknown',
                'endpoint' => $group->first()->endpoint ?: '-',
                'count' => $group->count(),
            ];
        })->values()->toArray();

        $summary = [
            'total_timeouts' => $totalTimeouts,
            'first_occurred' => $firstOccurred,
            'last_occurred' => $lastOccurred,
            'minutes_window' => $minutes,
            'endpoints_breakdown' => $endpointsBreakdown,
        ];

        $recentLogs = $timeoutLogs->take(10);

        // 5. Kirim email alert & telegram alert
        try {
            Mail::to($recipients)->send(new SapTimeoutAlertMail($summary, $recentLogs, false));

            // Kirim ringkasan ke Telegram
            $firstLog = $timeoutLogs->first();
            \App\Services\TelegramAlertService::sendSapTimeoutAlert(
                $firstLog->endpoint ?? '/api/receipt_production/create',
                $firstLog->message ?? 'cURL error 28: Operation timed out',
                $firstLog->id ?? null
            );

            // Perbarui cache ID log terakhir agar tidak terkirim ganda
            $maxId = (int) $timeoutLogs->max('id');
            Cache::forever('sap_timeout_last_notified_id', $maxId);

            $this->info("⚠️ Terdeteksi {$totalTimeouts} error timeout SAP.");
            $this->info('✅ Alert email & Telegram berhasil dikirim.');

            Log::warning("[SAP-TIMEOUT-ALERT] Alert email & Telegram dikirim ({$totalTimeouts} timeouts terdeteksi).");

            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Gagal mengirim email alert: ' . $e->getMessage());
            Log::error('Gagal mengirim email alert sap:alert-timeout: ' . $e->getMessage());

            return 1;
        }
    }
}
