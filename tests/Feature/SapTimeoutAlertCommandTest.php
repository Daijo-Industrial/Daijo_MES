<?php

namespace Tests\Feature;

use App\Mail\SapTimeoutAlertMail;
use App\Models\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SapTimeoutAlertCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('sap_timeout_last_notified_id');

        // Pastikan tabel api_logs ada di test environment (khusus sqlite test)
        if (! Schema::hasTable('api_logs')) {
            Schema::create('api_logs', function ($table) {
                $table->id();
                $table->string('api_name')->nullable();
                $table->string('method')->nullable();
                $table->text('endpoint')->nullable();
                $table->longText('request_payload')->nullable();
                $table->longText('response_payload')->nullable();
                $table->integer('status_code')->nullable();
                $table->string('status')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_alert_timeout_command_sends_email_when_timeouts_exist(): void
    {
        Mail::fake();

        ApiLog::create([
            'api_name' => 'receipt_production',
            'method' => 'POST',
            'endpoint' => '/api/receipt_production/create',
            'status_code' => 500,
            'status' => 'failed',
            'message' => 'cURL error 28: Operation timed out after 30002 milliseconds with 0 bytes received for http://192.168.6.149:9001/auth/token',
            'created_at' => now(),
        ]);

        $exitCode = Artisan::call('sap:alert-timeout', [
            '--minutes' => 30,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        Mail::assertSent(SapTimeoutAlertMail::class, function ($mail) {
            $hasAndreas = $mail->hasTo('andreas@daijo.co.id');
            $hasTimo = $mail->hasTo('timo@daijo.co.id');
            $hasThomas = $mail->hasTo('thomasilver88@gmail.com');
            $correctCount = ($mail->summary['total_timeouts'] ?? 0) === 1;
            $isNotTest = $mail->isTest === false;

            return $hasAndreas && $hasTimo && $hasThomas && $correctCount && $isNotTest;
        });
    }

    public function test_alert_timeout_command_does_not_send_email_when_no_timeouts(): void
    {
        Mail::fake();

        ApiLog::create([
            'api_name' => 'receipt_production',
            'method' => 'POST',
            'endpoint' => '/api/receipt_production/create',
            'status_code' => 200,
            'status' => 'success',
            'message' => 'SPK 250001 sent to SAP successfully',
            'created_at' => now(),
        ]);

        $exitCode = Artisan::call('sap:alert-timeout', [
            '--minutes' => 30,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        Mail::assertNothingSent();
    }

    public function test_alert_timeout_command_test_mode_sends_email_immediately(): void
    {
        Mail::fake();

        $exitCode = Artisan::call('sap:alert-timeout', [
            '--test' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        Mail::assertSent(SapTimeoutAlertMail::class, function ($mail) {
            return $mail->hasTo('andreas@daijo.co.id')
                && $mail->hasTo('timo@daijo.co.id')
                && $mail->hasTo('thomasilver88@gmail.com')
                && $mail->isTest === true;
        });
    }

    public function test_alert_timeout_command_deduplicates_with_cache(): void
    {
        Mail::fake();

        $log = ApiLog::create([
            'api_name' => 'spk_sync',
            'method' => 'GET',
            'endpoint' => '/api/sap_production_order/list',
            'status_code' => 500,
            'status' => 'failed',
            'message' => 'cURL error 28: Operation timed out after 120000 milliseconds',
            'created_at' => now(),
        ]);

        // Run pertama: harus kirim email
        Artisan::call('sap:alert-timeout', ['--minutes' => 30]);
        Mail::assertSent(SapTimeoutAlertMail::class, 1);

        // Run kedua: ID log sudah di-cache, tidak boleh kirim email lagi
        Artisan::call('sap:alert-timeout', ['--minutes' => 30]);
        Mail::assertSent(SapTimeoutAlertMail::class, 1); // tetap 1, tidak bertambah

        // Run ketiga dengan --force: harus kirim ulang
        Artisan::call('sap:alert-timeout', ['--minutes' => 30, '--force' => true]);
        Mail::assertSent(SapTimeoutAlertMail::class, 2);
    }

    public function test_sap_timeout_alert_mail_renders_properly(): void
    {
        $summary = [
            'total_timeouts' => 2,
            'first_occurred' => '27 Oct 2026 12:00:00',
            'last_occurred' => '27 Oct 2026 12:15:00',
            'minutes_window' => 30,
            'endpoints_breakdown' => [
                [
                    'api_name' => 'receipt_production',
                    'endpoint' => '/api/receipt_production/create',
                    'count' => 2,
                ],
            ],
        ];

        $dummyLogs = collect([
            (object) [
                'id' => 101,
                'created_at' => now(),
                'api_name' => 'receipt_production',
                'endpoint' => '/api/receipt_production/create',
                'message' => 'cURL error 28: Operation timed out after 30002 milliseconds',
            ],
        ]);

        $mailable = new SapTimeoutAlertMail($summary, $dummyLogs, false);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Peringatan Integrasi SAP: Operation Timed Out', $rendered);
        $this->assertStringContainsString('/api/receipt_production/create', $rendered);
        $this->assertStringContainsString('cURL error 28: Operation timed out', $rendered);
        $this->assertStringContainsString('2x', $rendered);
    }

    public function test_receipt_production_service_triggers_email_on_timeout(): void
    {
        Mail::fake();
        Cache::forget('sap_receipt_timeout_alert_cooldown');

        $service = new \App\Services\ReceiptProductionService();
        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250001'],
            [],
            500,
            'failed',
            'cURL error 28: Operation timed out after 30002 milliseconds with 0 bytes received for http://192.168.6.149:9001/auth/token'
        );

        Mail::assertSent(SapTimeoutAlertMail::class, function ($mail) {
            return $mail->hasTo('andreas@daijo.co.id')
                && $mail->hasTo('timo@daijo.co.id')
                && $mail->hasTo('thomasilver88@gmail.com')
                && $mail->isTest === false
                && ($mail->summary['total_timeouts'] ?? 0) === 1;
        });
    }

    public function test_receipt_production_service_throttles_with_5_minute_cooldown(): void
    {
        Mail::fake();
        Cache::forget('sap_receipt_timeout_alert_cooldown');

        $service = new \App\Services\ReceiptProductionService();

        // Panggilan pertama (timeout): harus kirim email
        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250001'],
            [],
            500,
            'failed',
            'cURL error 28: Operation timed out'
        );

        Mail::assertSent(SapTimeoutAlertMail::class, 1);

        // Panggilan kedua berturut-turut dalam 5 menit: harus ter-throttle (cooldown)
        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250002'],
            [],
            500,
            'failed',
            'cURL error 28: Operation timed out'
        );

        Mail::assertSent(SapTimeoutAlertMail::class, 1);

        // Setelah 5 menit lewat (simulasi cooldown expired), jika timeout lagi, harus kirim email lagi
        $this->travel(6)->minutes();

        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250003'],
            [],
            500,
            'failed',
            'cURL error 28: Operation timed out'
        );

        Mail::assertSent(SapTimeoutAlertMail::class, 2);
    }

    public function test_receipt_production_service_ignores_non_timeout_failures(): void
    {
        Mail::fake();
        Cache::forget('sap_receipt_timeout_alert_cooldown');

        $service = new \App\Services\ReceiptProductionService();
        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250001'],
            ['error' => 'Item code not found in SAP'],
            400,
            'failed',
            'SPK 250001 failed: Item code not found in SAP'
        );

        Mail::assertNothingSent();
    }

    public function test_telegram_alert_service_sends_request(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
        ]);

        $result = \App\Services\TelegramAlertService::sendMessage('Pesan uji coba');

        $this->assertTrue($result);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] == '-1003908455113'
                && (int) ($request['message_thread_id'] ?? 0) === 3
                && str_contains($request['text'], 'Pesan uji coba');
        });
    }

    public function test_receipt_production_service_triggers_telegram_alert_on_timeout(): void
    {
        Mail::fake();
        \Illuminate\Support\Facades\Http::fake([
            'https://api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
        ]);
        Cache::forget('sap_receipt_timeout_alert_cooldown');

        $service = new \App\Services\ReceiptProductionService();
        $service->saveApiLog(
            'receipt_production',
            'POST',
            '/api/receipt_production/create',
            ['DocNum' => '250001'],
            [],
            500,
            'failed',
            'cURL error 28: Operation timed out after 30002 milliseconds'
        );

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] == '-1003908455113'
                && (int) ($request['message_thread_id'] ?? 0) === 3
                && str_contains($request['text'], 'SAP API TIMEOUT')
                && str_contains($request['text'], '/api/receipt_production/create');
        });
    }
}
