<?php

namespace Tests\Feature;

use App\Livewire\ReceiptProductionDailySummary;
use App\Livewire\ReceiptProductionLogs;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptProductionLogsDailySummaryTest extends TestCase
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
        Schema::create('production_summary', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->integer('total_quantity');
            $table->string('warehouse');
            $table->string('label')->nullable();
            $table->date('created_date');
            $table->integer('sap_sent')->default(0);
            $table->timestamp('sap_sent_at')->nullable();
            $table->integer('qc_status')->default(0);
            $table->timestamps();
        });

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

        Schema::create('spk_masters', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number')->unique();
            $table->string('item_code')->nullable();
            $table->string('warehouse')->nullable();
            $table->timestamps();
        });
    }

    public function test_receipt_production_logs_has_daily_summary_link(): void
    {
        Livewire::test(ReceiptProductionLogs::class)
            ->set('filterDate', '2026-11-13')
            ->assertSeeHtml(route('receipt-production.daily-summary', ['date' => '2026-11-13']));
    }

    public function test_daily_summary_mounts_with_default_date_or_query_param(): void
    {
        Livewire::withQueryParams(['date' => '2026-11-10'])
            ->test(ReceiptProductionDailySummary::class)
            ->assertSet('date', '2026-11-10');
    }

    public function test_daily_summary_aggregates_total_qty_ids_and_status_by_spk(): void
    {
        $targetDate = '2026-11-13';

        // SPK 1: 2 Batches (14 pcs @ 10:50, 14 pcs @ 11:01), both sent
        $id1 = DB::table('production_summary')->insertGetId([
            'spk_code' => '26036036',
            'total_quantity' => 14,
            'warehouse' => 'FFI',
            'created_date' => $targetDate,
            'sap_sent' => 1,
            'sap_sent_at' => '2026-11-13 03:50:00', // 10:50 WIB
            'created_at' => '2026-11-13 03:50:00',
        ]);

        $id2 = DB::table('production_summary')->insertGetId([
            'spk_code' => '26036036',
            'total_quantity' => 14,
            'warehouse' => 'FFI',
            'created_date' => $targetDate,
            'sap_sent' => 1,
            'sap_sent_at' => '2026-11-13 04:01:00', // 11:01 WIB
            'created_at' => '2026-11-13 04:01:00',
        ]);

        // SPK 2: 1 Batch (40 pcs @ 11:02), sent
        $id3 = DB::table('production_summary')->insertGetId([
            'spk_code' => '26031252',
            'total_quantity' => 40,
            'warehouse' => 'KRFFI',
            'created_date' => $targetDate,
            'sap_sent' => 1,
            'sap_sent_at' => '2026-11-13 04:02:00', // 11:02 WIB
            'created_at' => '2026-11-13 04:02:00',
        ]);

        // SPK 3: 1 Batch (6 pcs @ 11:00), failed
        $id4 = DB::table('production_summary')->insertGetId([
            'spk_code' => '26030453',
            'total_quantity' => 6,
            'warehouse' => 'KRFFI',
            'created_date' => $targetDate,
            'sap_sent' => 3,
            'sap_sent_at' => null,
            'created_at' => '2026-11-13 04:00:00', // 11:00 WIB
        ]);

        // Item code mapping
        DB::table('spk_masters')->insert([
            ['spk_number' => '26036036', 'item_code' => 'R1220910.'],
            ['spk_number' => '26031252', 'item_code' => 'K-84716-17AA0VNB'],
            ['spk_number' => '26030453', 'item_code' => 'K-27128M662A'],
        ]);

        $component = Livewire::test(ReceiptProductionDailySummary::class, ['date' => $targetDate]);

        $summaryData = $component->get('summaryData');

        // Check stats
        $this->assertEquals(3, $summaryData['stats']['total_spk']);
        $this->assertEquals(74, $summaryData['stats']['total_qty']); // 14 + 14 + 40 + 6
        $this->assertEquals(68, $summaryData['stats']['sent_qty']); // 28 + 40
        $this->assertEquals(6, $summaryData['stats']['pending_qty']); // 6 failed

        // Check SPK 26036036 aggregated
        $spk1 = collect($summaryData['by_spk'])->firstWhere('spk_code', '26036036');
        $this->assertNotNull($spk1);
        $this->assertEquals(28, $spk1['total_quantity']);
        $this->assertEquals(2, $spk1['batch_count']);
        $this->assertEquals([$id1, $id2], $spk1['ids']);
        $this->assertEquals('R1220910.', $spk1['item_code']);
        $this->assertEquals('FFI', $spk1['warehouse']);
        $this->assertEquals('sent', $spk1['status']);
        $this->assertEquals('Terkirim', $spk1['status_label']);

        // Check SPK 26031252
        $spk2 = collect($summaryData['by_spk'])->firstWhere('spk_code', '26031252');
        $this->assertNotNull($spk2);
        $this->assertEquals(40, $spk2['total_quantity']);
        $this->assertEquals(1, $spk2['batch_count']);
        $this->assertEquals([$id3], $spk2['ids']);
        $this->assertEquals('KRFFI', $spk2['warehouse']);
        $this->assertEquals('sent', $spk2['status']);

        // Check SPK 26030453
        $spk3 = collect($summaryData['by_spk'])->firstWhere('spk_code', '26030453');
        $this->assertNotNull($spk3);
        $this->assertEquals(6, $spk3['total_quantity']);
        $this->assertEquals('failed', $spk3['status']);
        $this->assertEquals('Gagal', $spk3['status_label']);

        // Check view mode "id"
        $this->assertCount(4, $summaryData['by_id']);
    }

    public function test_daily_summary_search_and_filters(): void
    {
        $targetDate = '2026-11-13';

        DB::table('production_summary')->insert([
            ['id' => 101, 'spk_code' => 'SPK-ALPHA', 'total_quantity' => 50, 'warehouse' => 'FFI', 'created_date' => $targetDate, 'sap_sent' => 1, 'created_at' => now()],
            ['id' => 102, 'spk_code' => 'SPK-BETA', 'total_quantity' => 30, 'warehouse' => 'KRFFI', 'created_date' => $targetDate, 'sap_sent' => 0, 'created_at' => now()],
        ]);

        DB::table('spk_masters')->insert([
            ['spk_number' => 'SPK-ALPHA', 'item_code' => 'ITEM-AAA'],
            ['spk_number' => 'SPK-BETA', 'item_code' => 'ITEM-BBB'],
        ]);

        $component = Livewire::test(ReceiptProductionDailySummary::class, ['date' => $targetDate]);

        // Filter by warehouse KRFFI
        $component->set('warehouse', 'KRFFI');
        $summary = $component->get('summaryData');
        $this->assertCount(1, $summary['by_spk']);
        $this->assertEquals('SPK-BETA', $summary['by_spk'][0]['spk_code']);

        // Reset warehouse, filter by search 'ALPHA'
        $component->set('warehouse', '')
            ->set('search', 'ALPHA');
        $summary = $component->get('summaryData');
        $this->assertCount(1, $summary['by_spk']);
        $this->assertEquals('SPK-ALPHA', $summary['by_spk'][0]['spk_code']);

        // Search by ID '#102'
        $component->set('search', '102');
        $summary = $component->get('summaryData');
        $this->assertCount(1, $summary['by_spk']);
        $this->assertEquals('SPK-BETA', $summary['by_spk'][0]['spk_code']);

        // Filter by status 'sent'
        $component->set('search', '')
            ->set('status', 'sent');
        $summary = $component->get('summaryData');
        $this->assertCount(1, $summary['by_spk']);
        $this->assertEquals('SPK-ALPHA', $summary['by_spk'][0]['spk_code']);
    }
}
