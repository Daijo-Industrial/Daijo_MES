<?php

namespace Tests\Feature;

use App\Livewire\ReceiptProductionLogs;
use App\Services\SapErrorParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptProductionLogsSapErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->createTestSchema();
    }

    private function createTestSchema(): void
    {
        Schema::create('production_scanned_data', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->string('warehouse');
            $table->integer('quantity');
            $table->string('item_code')->nullable();
            $table->string('label')->nullable();
            $table->string('user')->nullable();
            $table->boolean('processed')->default(false);
            $table->unsignedBigInteger('summary_id')->nullable();
            $table->timestamps();
        });

        Schema::create('production_summary', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->integer('total_quantity');
            $table->string('warehouse');
            $table->string('label')->nullable();
            $table->date('created_date');
            $table->integer('sap_sent')->default(0);
            $table->timestamp('sap_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_name')->nullable();
            $table->string('method')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('status')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('message')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('spk_masters', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code')->unique();
            $table->string('item_code')->nullable();
            $table->timestamps();
        });
    }

    public function test_sap_error_parser_categorizes_inventory_negative_correctly(): void
    {
        $raw = 'SPK SPK-202610-001 failed: {"status":"error","message":"[-5002] Make sure that the consumed quantity of the component item would not cause the item\'s inventory to fall below zero in warehouse. Line: 2"}';

        $parsed = SapErrorParser::parse($raw, 500, 'SPK-202610-001');

        $this->assertEquals('stock_deficit', $parsed['category']);
        $this->assertEquals('Stok Komponen Minus', $parsed['badge_label']);
        $this->assertEquals('2', $parsed['bom_line']);
        $this->assertStringContainsString('SAP menolak penerimaan hasil produksi karena stok material/komponen', $parsed['user_message']);
        $this->assertStringContainsString('Goods Issue', $parsed['action_recommendation']);
    }

    public function test_sap_error_parser_extracts_production_order_bom_line_over_ign1_receipt_line(): void
    {
        $raw = 'SPK 26030494 failed: {"status":false,"message":"Error for SPK 26030494: [-5002] Make sure that the consumed quantity of the component item would not cause the item\'s inventory to fall below zero [IGN1.WhsCode][line: 1] , \'Production Order no: 26030494 Line: 2\'"}';

        $parsed = SapErrorParser::parse($raw, 200, '26030494');

        $this->assertEquals('stock_deficit', $parsed['category']);
        $this->assertEquals('2', $parsed['bom_line']);
        $this->assertStringContainsString('pada baris komponen 2', $parsed['user_message']);
    }

    public function test_sap_error_parser_categorizes_connection_timeout(): void
    {
        $raw = 'SPK SPK-202610-002 failed: cURL error 28: Connection timed out after 60000 milliseconds';

        $parsed = SapErrorParser::parse($raw, 504, 'SPK-202610-002');

        $this->assertEquals('timeout', $parsed['category']);
        $this->assertEquals('Koneksi Timeout', $parsed['badge_label']);
        $this->assertStringContainsString('tidak merespons dalam batas waktu timeout', $parsed['user_message']);
        $this->assertStringContainsString('Kirim SAP', $parsed['action_recommendation']);
    }

    public function test_sap_error_parser_categorizes_di_api_com_crash(): void
    {
        $raw = 'SPK SPK-202610-003 failed: Failed to authenticate to SAP: Unable to cast COM object of type \'SAPbobsCOM.CompanyClass\'';

        $parsed = SapErrorParser::parse($raw, 500, 'SPK-202610-003');

        $this->assertEquals('gateway_auth', $parsed['category']);
        $this->assertEquals('Gateway SAP Bermasalah', $parsed['badge_label']);
        $this->assertStringContainsString('DI-API / Daijo API Gateway', $parsed['user_message']);
    }

    public function test_sap_error_parser_categorizes_duplicate_entry(): void
    {
        $raw = 'SPK SPK-202610-004 failed: Document receipt for SPK-202610-004 already exists in SAP';

        $parsed = SapErrorParser::parse($raw, 409, 'SPK-202610-004');

        $this->assertEquals('duplicate', $parsed['category']);
        $this->assertEquals('Sudah di SAP (Duplikat)', $parsed['badge_label']);
        $this->assertStringContainsString('sudah berhasil terbentuk di SAP sebelumnya', $parsed['user_message']);
        $this->assertStringContainsString('Tandai Sukses', $parsed['action_recommendation']);
    }

    public function test_livewire_attaches_parsed_error_summary_to_failed_spk_row(): void
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        $summaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-FAIL-001',
            'total_quantity' => 250,
            'warehouse' => 'FFI',
            'created_date' => $today,
            'sap_sent' => 3, // Failed
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-FAIL-001',
            'warehouse' => 'FFI',
            'quantity' => 250,
            'item_code' => 'PART-XYZ-01',
            'summary_id' => $summaryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_logs')->insert([
            'api_name' => 'receipt_production',
            'status' => 'failed',
            'status_code' => 500,
            'message' => 'SPK SPK-FAIL-001 failed: [-5002] Make sure that the consumed quantity of the component item would not cause the item\'s inventory to fall below zero. Line: 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $test = Livewire::test(ReceiptProductionLogs::class)
            ->set('filterStatus', 'all');

        $test->assertSee('Stok Komponen Minus')
            ->assertSee('SAP menolak penerimaan hasil produksi karena stok material/komponen');
    }

    public function test_livewire_show_error_detail_opens_modal_and_close_resets_it(): void
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        $summaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-MODAL-001',
            'total_quantity' => 500,
            'warehouse' => 'FFI',
            'created_date' => $today,
            'sap_sent' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-MODAL-001',
            'warehouse' => 'FFI',
            'quantity' => 500,
            'item_code' => 'PART-ABC-02',
            'summary_id' => $summaryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_logs')->insert([
            'api_name' => 'receipt_production',
            'status' => 'failed',
            'status_code' => 504,
            'message' => 'SPK SPK-MODAL-001 failed: cURL error 28: Operation timed out after 60000ms',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::test(ReceiptProductionLogs::class)
            ->call('showErrorDetail', 'SPK-MODAL-001', $summaryId);

        $component->assertSet('activeErrorModal.spk_code', 'SPK-MODAL-001')
            ->assertSet('activeErrorModal.item_code', 'PART-ABC-02')
            ->assertSet('activeErrorModal.error.category', 'timeout')
            ->assertSee('Diagnosa Error SAP — SPK SPK-MODAL-001')
            ->assertSee('Koneksi ke Server SAP Timeout')
            ->assertSee('Kirim Ulang Sekarang');

        // Close modal
        $component->call('closeErrorModal')
            ->assertSet('activeErrorModal', null);
    }

    public function test_sap_post_uses_fail_fast_timeout_settings(): void
    {
        $this->assertEquals(60, config('services.sap.post_timeout'));
        $this->assertEquals(10, config('services.sap.connect_timeout'));

        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/receipt_production/create' => Http::response(['status' => true, 'message' => 'Success'], 200),
        ]);

        $service = app(\App\Services\ReceiptProductionService::class);
        $summary = (object) [
            'id' => 9991,
            'spk_code' => 'SPK-FAST-01',
            'warehouse' => 'FFI',
            'total_quantity' => 100,
        ];
        $scannedData = (object) [
            'item_code' => 'PART-FAST',
        ];

        DB::table('production_summary')->insert([
            'id' => 9991,
            'spk_code' => 'SPK-FAST-01',
            'warehouse' => 'FFI',
            'total_quantity' => 100,
            'sap_sent' => 0,
            'created_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $service->pushSingleRecord($summary, $scannedData);
        $this->assertTrue($res['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://192.168.6.149:9001/api/receipt_production/create';
        });
    }

    public function test_push_to_sap_manual_executes_live_without_queue(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token_direct'], 200),
            '*/api/receipt_production/create' => Http::response(['status' => true, 'message' => 'Created in SAP'], 200),
        ]);

        $summaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-LIVE-001',
            'warehouse' => 'KRFFI',
            'total_quantity' => 50,
            'sap_sent' => 0,
            'created_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-LIVE-001',
            'item_code' => 'PART-LIVE-99',
            'warehouse' => 'KRFFI',
            'quantity' => 50,
            'summary_id' => $summaryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(ReceiptProductionLogs::class)
            ->call('pushToSapManual', $summaryId)
            ->assertDispatched('push-notification');

        // Verifikasi di database langsung jadi sap_sent = 1
        $this->assertEquals(1, DB::table('production_summary')->where('id', $summaryId)->value('sap_sent'));

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/receipt_production/create')) {
                $data = $request->data();
                return isset($data[0]['DocDate'])
                    && $data[0]['DocDate'] === now()->toDateString()
                    && isset($data[0]['doc_date'])
                    && $data[0]['doc_date'] === now()->toDateString()
                    && isset($data[0]['remarks'])
                    && str_contains($data[0]['remarks'], 'Created from SPK');
            }
            return true;
        });
    }
}
