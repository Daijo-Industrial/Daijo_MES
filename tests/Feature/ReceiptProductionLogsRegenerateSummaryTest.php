<?php

namespace Tests\Feature;

use App\Livewire\ReceiptProductionLogs;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptProductionLogsRegenerateSummaryTest extends TestCase
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
    }

    public function test_regenerate_summary_processes_0730_to_0730_next_day_window(): void
    {
        $targetDate = '2026-11-09';

        // Scan 1: Inside window (2026-11-09 08:00:00)
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-NOV09-A',
            'warehouse' => 'FFI',
            'quantity' => 100,
            'item_code' => 'ITEM-A',
            'label' => 'BOX-1',
            'processed' => false,
            'summary_id' => null,
            'created_at' => '2026-11-09 08:00:00',
            'updated_at' => '2026-11-09 08:00:00',
        ]);

        // Scan 2: Inside window - Shift 3 after midnight (2026-11-10 02:30:00)
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-NOV09-B',
            'warehouse' => 'KRFFI',
            'quantity' => 250,
            'item_code' => 'ITEM-B',
            'label' => 'BOX-2',
            'processed' => false,
            'summary_id' => null,
            'created_at' => '2026-11-10 02:30:00',
            'updated_at' => '2026-11-10 02:30:00',
        ]);

        // Scan 3: Before 07:30 cutoff (2026-11-09 07:15:00) -> Should NOT be included
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-OLD',
            'warehouse' => 'FFI',
            'quantity' => 50,
            'item_code' => 'ITEM-OLD',
            'label' => 'BOX-OLD',
            'processed' => false,
            'summary_id' => null,
            'created_at' => '2026-11-09 07:15:00',
            'updated_at' => '2026-11-09 07:15:00',
        ]);

        // Scan 4: After 07:30 next day (2026-11-10 07:45:00) -> Should NOT be included
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-NEXT',
            'warehouse' => 'FFI',
            'quantity' => 75,
            'item_code' => 'ITEM-NEXT',
            'label' => 'BOX-NEXT',
            'processed' => false,
            'summary_id' => null,
            'created_at' => '2026-11-10 07:45:00',
            'updated_at' => '2026-11-10 07:45:00',
        ]);

        Livewire::test(ReceiptProductionLogs::class)
            ->set('filterMode', 'daily')
            ->set('filterDate', $targetDate)
            ->call('regenerateSummaryForDate')
            ->assertDispatched('push-notification');

        // Check that summaries are created for SPK-NOV09-A and SPK-NOV09-B
        $summaries = DB::table('production_summary')->where('created_date', $targetDate)->get();
        $this->assertCount(2, $summaries);

        $spkA = $summaries->firstWhere('spk_code', 'SPK-NOV09-A');
        $this->assertNotNull($spkA);
        $this->assertEquals(100, $spkA->total_quantity);
        $this->assertEquals('FFI', $spkA->warehouse);
        $this->assertEquals($targetDate, $spkA->created_date);
        $this->assertEquals(0, $spkA->sap_sent);

        $spkB = $summaries->firstWhere('spk_code', 'SPK-NOV09-B');
        $this->assertNotNull($spkB);
        $this->assertEquals(250, $spkB->total_quantity);
        $this->assertEquals('KRFFI', $spkB->warehouse);
        $this->assertEquals($targetDate, $spkB->created_date);
        $this->assertEquals(0, $spkB->sap_sent);

        // Verify that scans outside window were NOT processed
        $unprocessedScans = DB::table('production_scanned_data')
            ->whereIn('spk_code', ['SPK-OLD', 'SPK-NEXT'])
            ->whereNull('summary_id')
            ->count();
        $this->assertEquals(2, $unprocessedScans);
    }

    public function test_regenerate_summary_does_not_touch_already_sent_sap_summaries(): void
    {
        $targetDate = '2026-11-09';

        // Existing summary that already succeeded to SAP (sap_sent = 1)
        $sentSummaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-SENT',
            'total_quantity' => 500,
            'warehouse' => 'FFI',
            'label' => 'all',
            'created_date' => $targetDate,
            'sap_sent' => 1,
            'sap_sent_at' => now(),
            'created_at' => '2026-11-09 09:00:00',
            'updated_at' => '2026-11-09 09:00:00',
        ]);

        // Scanned data already linked to sent summary
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-SENT',
            'warehouse' => 'FFI',
            'quantity' => 500,
            'item_code' => 'ITEM-SENT',
            'label' => 'BOX-SENT',
            'processed' => true,
            'summary_id' => $sentSummaryId,
            'created_at' => '2026-11-09 08:30:00',
            'updated_at' => '2026-11-09 08:30:00',
        ]);

        // New un-summarized scan on the same date
        DB::table('production_scanned_data')->insert([
            'spk_code' => 'SPK-NEW',
            'warehouse' => 'FFI',
            'quantity' => 300,
            'item_code' => 'ITEM-NEW',
            'label' => 'BOX-NEW',
            'processed' => false,
            'summary_id' => null,
            'created_at' => '2026-11-09 10:00:00',
            'updated_at' => '2026-11-09 10:00:00',
        ]);

        Livewire::test(ReceiptProductionLogs::class)
            ->set('filterMode', 'daily')
            ->set('filterDate', $targetDate)
            ->call('regenerateSummaryForDate');

        // Verify sent summary was NOT deleted or modified
        $this->assertDatabaseHas('production_summary', [
            'id' => $sentSummaryId,
            'spk_code' => 'SPK-SENT',
            'sap_sent' => 1,
        ]);

        // Verify new summary was created
        $this->assertDatabaseHas('production_summary', [
            'spk_code' => 'SPK-NEW',
            'total_quantity' => 300,
            'sap_sent' => 0,
        ]);
    }
}
