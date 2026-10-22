<?php

namespace Tests\Feature;

use App\Models\ProductionScannedData;
use App\Models\ProductionSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateProductionSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Gunakan sqlite in-memory
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
            $table->string('api_name');
            $table->string('method');
            $table->string('endpoint');
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test that GenerateProductionSummary groups data in 10-minute intervals.
     */
    public function test_summary_groups_by_ten_minute_interval(): void
    {
        // Scan 1: 09:04:53
        DB::table('production_scanned_data')->insert([
            'id' => 1,
            'spk_code' => '26017339',
            'warehouse' => 'FFI',
            'quantity' => 30,
            'item_code' => '8002C294',
            'label' => '70',
            'processed' => false,
            'created_at' => '2026-07-16 09:04:53',
            'updated_at' => '2026-07-16 09:04:53',
        ]);

        // Scan 2: 09:13:27 (different 10-min interval: 09:10 block)
        DB::table('production_scanned_data')->insert([
            'id' => 2,
            'spk_code' => '26017339',
            'warehouse' => 'FFI',
            'quantity' => 30,
            'item_code' => '8002C294',
            'label' => '71',
            'processed' => false,
            'created_at' => '2026-07-16 09:13:27',
            'updated_at' => '2026-07-16 09:13:27',
        ]);

        // Scan 3: 09:45:58 (different 10-min interval: 09:40 block)
        DB::table('production_scanned_data')->insert([
            'id' => 3,
            'spk_code' => '26017339',
            'warehouse' => 'FFI',
            'quantity' => 30,
            'item_code' => '8002C294',
            'label' => '72',
            'processed' => false,
            'created_at' => '2026-07-16 09:45:58',
            'updated_at' => '2026-07-16 09:45:58',
        ]);

        // Scan 4: 09:08:12 (same 10-min interval as Scan 1: 09:00 block)
        DB::table('production_scanned_data')->insert([
            'id' => 4,
            'spk_code' => '26017339',
            'warehouse' => 'FFI',
            'quantity' => 30,
            'item_code' => '8002C294',
            'label' => '73',
            'processed' => false,
            'created_at' => '2026-07-16 09:08:12',
            'updated_at' => '2026-07-16 09:08:12',
        ]);

        // Run the artisan command
        $this->artisan('summary:generate')->assertExitCode(0);

        // We expect:
        // - 3 summaries should be created (09:00 block, 09:10 block, 09:40 block)
        // - Scan 1 and Scan 4 should share the same summary_id (09:00 block, total qty = 60)
        // - Scan 2 should have its own summary_id (09:10 block, total qty = 30)
        // - Scan 3 should have its own summary_id (09:40 block, total qty = 30)

        $this->assertEquals(3, ProductionSummary::count());

        $scan1Fresh = ProductionScannedData::find(1);
        $scan2Fresh = ProductionScannedData::find(2);
        $scan3Fresh = ProductionScannedData::find(3);
        $scan4Fresh = ProductionScannedData::find(4);

        // Check summary links
        $this->assertNotNull($scan1Fresh->summary_id);
        $this->assertNotNull($scan2Fresh->summary_id);
        $this->assertNotNull($scan3Fresh->summary_id);
        $this->assertNotNull($scan4Fresh->summary_id);

        $this->assertEquals($scan1Fresh->summary_id, $scan4Fresh->summary_id);
        $this->assertNotEquals($scan1Fresh->summary_id, $scan2Fresh->summary_id);
        $this->assertNotEquals($scan1Fresh->summary_id, $scan3Fresh->summary_id);
        $this->assertNotEquals($scan2Fresh->summary_id, $scan3Fresh->summary_id);

        // Verify quantities
        $summary0900 = ProductionSummary::find($scan1Fresh->summary_id);
        $this->assertEquals(60, $summary0900->total_quantity);

        $summary0910 = ProductionSummary::find($scan2Fresh->summary_id);
        $this->assertEquals(30, $summary0910->total_quantity);

        $summary0940 = ProductionSummary::find($scan3Fresh->summary_id);
        $this->assertEquals(30, $summary0940->total_quantity);
    }

    /**
     * Test that GenerateProductionSummary skips duplicate spk_code + label records.
     */
    public function test_skips_duplicate_spk_label_records(): void
    {
        // Insert record 1
        DB::table('production_scanned_data')->insert([
            'id' => 10,
            'spk_code' => 'SPK-DUP',
            'warehouse' => 'FFI',
            'quantity' => 10,
            'item_code' => '8002C294',
            'label' => 'DUP-LABEL',
            'processed' => false,
            'created_at' => '2026-07-16 09:00:00',
            'updated_at' => '2026-07-16 09:00:00',
        ]);

        // Insert record 2 (duplicate label)
        DB::table('production_scanned_data')->insert([
            'id' => 11,
            'spk_code' => 'SPK-DUP',
            'warehouse' => 'FFI',
            'quantity' => 10,
            'item_code' => '8002C294',
            'label' => 'DUP-LABEL',
            'processed' => false,
            'created_at' => '2026-07-16 09:05:00',
            'updated_at' => '2026-07-16 09:05:00',
        ]);

        $this->artisan('summary:generate')->assertExitCode(0);

        // Verify only 1 summary is created
        $this->assertEquals(1, ProductionSummary::count());
        $summary = ProductionSummary::first();
        $this->assertEquals(10, $summary->total_quantity);

        $rec10 = ProductionScannedData::find(10);
        $rec11 = ProductionScannedData::find(11);

        $this->assertEquals(1, $rec10->processed);
        $this->assertEquals($summary->id, $rec10->summary_id);

        $this->assertEquals(0, $rec11->processed);
        $this->assertNull($rec11->summary_id);
    }

    /**
     * Test that GenerateProductionSummary does not regenerate already summarized records.
     */
    public function test_does_not_regenerate_already_summarized_records(): void
    {
        // Insert record that already has summary_id
        DB::table('production_scanned_data')->insert([
            'id' => 20,
            'spk_code' => 'SPK-ALREADY',
            'warehouse' => 'FFI',
            'quantity' => 15,
            'item_code' => '8002C294',
            'label' => 'LABEL-A',
            'processed' => false,
            'summary_id' => 999,
            'created_at' => '2026-07-16 09:00:00',
            'updated_at' => '2026-07-16 09:00:00',
        ]);

        $this->artisan('summary:generate')->assertExitCode(0);

        // Verify NO new summary was created
        $this->assertEquals(0, ProductionSummary::count());

        // Verify processed is still false
        $rec = ProductionScannedData::find(20);
        $this->assertEquals(0, $rec->processed);
    }
}
