<?php

namespace Tests\Feature;

use App\Livewire\MaterialWarehouse\MaterialFifoDirectorDashboard;
use App\Models\MasterListMaterial;
use App\Models\MwhIncomingHeader;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Models\MwhRack;
use App\Models\MwhWarehouse;
use App\Services\MaterialFifoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialFifoDirectorDashboardTest extends TestCase
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
        Schema::dropIfExists('mwh_outgoings');
        Schema::dropIfExists('mwh_pallets');
        Schema::dropIfExists('mwh_incoming_headers');
        Schema::dropIfExists('mwh_positions');
        Schema::dropIfExists('mwh_racks');
        Schema::dropIfExists('master_list_materials');
        Schema::dropIfExists('mwh_warehouses');

        Schema::create('mwh_warehouses', function ($table) {
            $table->id();
            $table->string('whse_code')->unique();
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_racks', function ($table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_positions', function ($table) {
            $table->id();
            $table->foreignId('rack_id');
            $table->integer('level_no');
            $table->integer('slot_no');
            $table->string('position_code')->unique();
            $table->string('slot_label')->nullable();
            $table->string('status')->default('EMPTY');
            $table->string('last_item_code')->nullable();
            $table->integer('max_capacity')->default(1000);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_list_materials', function ($table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->text('item_description')->nullable();
            $table->string('preferred_supplier', 100)->nullable();
            $table->string('purchasing_uom', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_incoming_headers', function ($table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('document_no')->unique();
            $table->string('incoming_type')->default('SUPPLIER');
            $table->string('supplier_name')->nullable();
            $table->string('returned_from')->nullable();
            $table->string('po_number')->nullable();
            $table->string('original_outgoing_code')->nullable();
            $table->date('arrival_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_pallets', function ($table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('pallet_id')->unique();
            $table->foreignId('incoming_header_id')->nullable();
            $table->string('item_code');
            $table->string('lot_no')->nullable();
            $table->decimal('initial_qty', 12, 2)->default(0);
            $table->decimal('current_qty', 12, 2)->default(0);
            $table->string('uom', 20)->default('KG');
            $table->foreignId('position_id')->nullable();
            $table->enum('status', ['STORED', 'PARTIAL', 'EMPTY'])->default('STORED');
            $table->boolean('is_qc_hold')->default(false);
            $table->text('qc_hold_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_outgoings', function ($table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('outgoing_code')->unique();
            $table->string('pallet_id');
            $table->foreignId('position_id')->nullable();
            $table->string('item_code');
            $table->decimal('qty_taken', 12, 2)->default(0);
            $table->string('uom', 20)->default('KG');
            $table->date('outgoing_date');
            $table->string('issued_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedStandardTestData(): array
    {
        $warehouse = MwhWarehouse::create([
            'whse_code' => 'MWH-01',
            'whse_name' => 'Raw Material Main Warehouse',
        ]);

        $rack = MwhRack::create([
            'whse_id' => $warehouse->id,
            'rack_code' => 'RACK-A',
        ]);

        $pos1 = MwhPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'MWH01-A-01-01',
            'status' => 'OCCUPIED',
        ]);

        $pos2 = MwhPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 2,
            'position_code' => 'MWH01-A-01-02',
            'status' => 'OCCUPIED',
        ]);

        $material = MasterListMaterial::create([
            'item_code' => 'RESIN-PP-001',
            'item_description' => 'Polypropylene Natural Grade A',
            'purchasing_uom' => 'KG',
        ]);

        return compact('warehouse', 'rack', 'pos1', 'pos2', 'material');
    }

    public function test_public_user_can_access_fifo_dashboard_without_login()
    {
        $this->seedStandardTestData();

        $response1 = $this->get('/material-warehouse/fifo-dashboard');
        $response1->assertStatus(200);

        $response2 = $this->get('/material-warehouse/director-dashboard');
        $response2->assertStatus(200);

        $response3 = $this->get('/public/material-warehouse/fifo-dashboard');
        $response3->assertStatus(200);
    }

    public function test_fifo_service_detects_violations_when_newer_lot_is_picked_first()
    {
        $data = $this->seedStandardTestData();

        // 1. Older Arrival: Aug 01, 2026
        $incOld = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-2026-08-001',
            'arrival_date' => '2026-08-01',
        ]);

        $palletOld = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-OLD-001',
            'incoming_header_id' => $incOld->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-AUG-01',
            'initial_qty' => 1000,
            'current_qty' => 1000,
            'position_id' => $data['pos1']->id,
            'status' => 'STORED',
            'created_at' => Carbon::parse('2026-08-01 08:00:00'),
        ]);

        // 2. Newer Arrival: Aug 10, 2026
        $incNew = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-2026-08-002',
            'arrival_date' => '2026-08-10',
        ]);

        $palletNew = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-NEW-002',
            'incoming_header_id' => $incNew->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-AUG-10',
            'initial_qty' => 500,
            'current_qty' => 200,
            'position_id' => $data['pos2']->id,
            'status' => 'PARTIAL',
            'created_at' => Carbon::parse('2026-08-10 09:00:00'),
        ]);

        // 3. Outgoing performed on Aug 15 from NEWER pallet PLT-NEW-002 instead of OLDER PLT-OLD-001
        $outgoing = MwhOutgoing::create([
            'whse_id' => $data['warehouse']->id,
            'outgoing_code' => 'OUT-2026-08-001',
            'pallet_id' => $palletNew->pallet_id,
            'position_id' => $data['pos2']->id,
            'item_code' => 'RESIN-PP-001',
            'qty_taken' => 300,
            'outgoing_date' => '2026-08-15',
            'issued_to' => 'PRODUCTION-LINE-1',
            'created_at' => Carbon::parse('2026-08-15 10:00:00'),
        ]);

        $service = new MaterialFifoService;
        $deviations = $service->detectFifoDeviations('ALL', '2026-08-01', '2026-08-31');

        $this->assertCount(1, $deviations);
        $violation = $deviations[0];

        $this->assertEquals('PLT-NEW-002', $violation['picked_pallet_id']);
        $this->assertEquals('LOT-AUG-10', $violation['picked_lot_no']);
        $this->assertEquals('10/08/2026', $violation['picked_arrival_date']);
        $this->assertEquals(9, $violation['delta_days']); // 2026-08-10 vs 2026-08-01 = 9 days diff
        $this->assertEquals('PLT-OLD-001', $violation['skipped_pallet_id']);
        $this->assertEquals('MEDIUM', $violation['severity']);

        // Check KPI
        $kpis = $service->getFifoKpis('ALL', '2026-08-01', '2026-08-31');
        $this->assertEquals(1, $kpis['total_outgoings_count']);
        $this->assertEquals(1, $kpis['deviation_count']);
        $this->assertEquals(0, $kpis['compliance_rate']); // 0 of 1 is compliant -> 0%
    }

    public function test_fifo_service_ignores_qc_hold_pallets_from_deviations()
    {
        $data = $this->seedStandardTestData();

        // Older arrival is on QC Hold
        $incOld = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-2026-08-001',
            'arrival_date' => '2026-08-01',
        ]);

        $palletOld = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-OLD-001',
            'incoming_header_id' => $incOld->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-AUG-01',
            'initial_qty' => 1000,
            'current_qty' => 1000,
            'position_id' => $data['pos1']->id,
            'status' => 'STORED',
            'is_qc_hold' => true, // QC HOLD!
            'qc_hold_reason' => 'Moisture content high',
            'created_at' => Carbon::parse('2026-08-01 08:00:00'),
        ]);

        // Newer arrival
        $incNew = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-2026-08-002',
            'arrival_date' => '2026-08-10',
        ]);

        $palletNew = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-NEW-002',
            'incoming_header_id' => $incNew->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-AUG-10',
            'initial_qty' => 500,
            'current_qty' => 200,
            'position_id' => $data['pos2']->id,
            'status' => 'PARTIAL',
            'created_at' => Carbon::parse('2026-08-10 09:00:00'),
        ]);

        // Outgoing from PLT-NEW-002
        MwhOutgoing::create([
            'whse_id' => $data['warehouse']->id,
            'outgoing_code' => 'OUT-2026-08-001',
            'pallet_id' => $palletNew->pallet_id,
            'position_id' => $data['pos2']->id,
            'item_code' => 'RESIN-PP-001',
            'qty_taken' => 300,
            'outgoing_date' => '2026-08-15',
            'issued_to' => 'PRODUCTION-LINE-1',
            'created_at' => Carbon::parse('2026-08-15 10:00:00'),
        ]);

        $service = new MaterialFifoService;
        $deviations = $service->detectFifoDeviations('ALL', '2026-08-01', '2026-08-31');

        // QC hold items are excused, so NO deviation!
        $this->assertCount(0, $deviations);

        $kpis = $service->getFifoKpis('ALL', '2026-08-01', '2026-08-31');
        $this->assertEquals(100, $kpis['compliance_rate']);
        $this->assertEquals(1, $kpis['qc_hold_pallets_count']);
    }

    public function test_fifo_service_inventory_aging_summary()
    {
        $data = $this->seedStandardTestData();

        // 1. Fresh pallet (10 days old)
        MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-FRESH',
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-FRESH',
            'initial_qty' => 100,
            'current_qty' => 100,
            'status' => 'STORED',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // 2. Medium pallet (45 days old)
        MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-MED',
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-MED',
            'initial_qty' => 200,
            'current_qty' => 200,
            'status' => 'STORED',
            'created_at' => Carbon::now()->subDays(45),
        ]);

        // 3. Old pallet (120 days old)
        MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-OLD',
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-OLD',
            'initial_qty' => 300,
            'current_qty' => 300,
            'status' => 'STORED',
            'created_at' => Carbon::now()->subDays(120),
        ]);

        $service = new MaterialFifoService;
        $aging = $service->getInventoryAgingSummary('ALL');

        $this->assertEquals(100, $aging['tiers']['fresh']['qty']);
        $this->assertEquals(200, $aging['tiers']['normal']['qty']);
        $this->assertEquals(0, $aging['tiers']['warning']['qty']);
        $this->assertEquals(300, $aging['tiers']['critical']['qty']);
        $this->assertEquals(600, $aging['total_qty']);
        $this->assertCount(3, $aging['oldest_pallets']);
    }

    public function test_fifo_priority_queue()
    {
        $data = $this->seedStandardTestData();

        MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-LOT-B',
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-B',
            'initial_qty' => 100,
            'current_qty' => 100,
            'status' => 'STORED',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-LOT-A',
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-A',
            'initial_qty' => 200,
            'current_qty' => 200,
            'status' => 'STORED',
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $service = new MaterialFifoService;
        $queue = $service->getFifoPriorityQueue('ALL');

        $this->assertNotEmpty($queue);
        $firstItem = $queue[0];
        $this->assertEquals('RESIN-PP-001', $firstItem['item_code']);
        $this->assertEquals('PLT-LOT-A', $firstItem['rank_1']['pallet_id']);
        $this->assertEquals('LOT-A', $firstItem['rank_1']['lot_no']);
        $this->assertCount(1, $firstItem['next_in_line']);
        $this->assertEquals('PLT-LOT-B', $firstItem['next_in_line'][0]['pallet_id']);
    }

    public function test_fifo_service_does_not_flag_older_pallet_if_already_depleted_before_outgoing()
    {
        $data = $this->seedStandardTestData();

        // 1. Older Pallet arrived on Aug 01, 2026 (1000 KG)
        $incOld = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-OLD-01',
            'arrival_date' => '2026-08-01',
        ]);

        $palletOld = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-OLD-DEPLETED',
            'incoming_header_id' => $incOld->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-OLD-01',
            'initial_qty' => 1000,
            'current_qty' => 0, // fully consumed!
            'position_id' => null,
            'status' => 'EMPTY',
            'created_at' => Carbon::parse('2026-08-01 08:00:00'),
            'updated_at' => Carbon::parse('2026-08-20 12:00:00'), // updated later (e.g. audit or migration)
        ]);

        // Outgoing that consumed the older pallet happened on Aug 05, 2026
        MwhOutgoing::create([
            'whse_id' => $data['warehouse']->id,
            'outgoing_code' => 'OUT-OLD-01',
            'pallet_id' => $palletOld->pallet_id,
            'item_code' => 'RESIN-PP-001',
            'qty_taken' => 1000,
            'outgoing_date' => '2026-08-05',
            'created_at' => Carbon::parse('2026-08-05 10:00:00'),
        ]);

        // 2. Newer Pallet arrived on Aug 10, 2026 (500 KG)
        $incNew = MwhIncomingHeader::create([
            'whse_id' => $data['warehouse']->id,
            'document_no' => 'INC-NEW-02',
            'arrival_date' => '2026-08-10',
        ]);

        $palletNew = MwhPallet::create([
            'whse_id' => $data['warehouse']->id,
            'pallet_id' => 'PLT-NEW-02',
            'incoming_header_id' => $incNew->id,
            'item_code' => 'RESIN-PP-001',
            'lot_no' => 'LOT-NEW-02',
            'initial_qty' => 500,
            'current_qty' => 200,
            'position_id' => $data['pos2']->id,
            'status' => 'PARTIAL',
            'created_at' => Carbon::parse('2026-08-10 09:00:00'),
        ]);

        // 3. Outgoing from newer pallet on Aug 15, 2026
        MwhOutgoing::create([
            'whse_id' => $data['warehouse']->id,
            'outgoing_code' => 'OUT-NEW-02',
            'pallet_id' => $palletNew->pallet_id,
            'item_code' => 'RESIN-PP-001',
            'qty_taken' => 300,
            'outgoing_date' => '2026-08-15',
            'created_at' => Carbon::parse('2026-08-15 10:00:00'),
        ]);

        $service = new MaterialFifoService;
        $deviations = $service->detectFifoDeviations('ALL', '2026-08-10', '2026-08-20');

        // Since PLT-OLD-DEPLETED was ALREADY fully consumed on Aug 05 (before Aug 15),
        // OUT-NEW-02 did NOT bypass it. So violations must be 0!
        $this->assertCount(0, $deviations);
    }

    public function test_livewire_fifo_dashboard_component_renders_and_interacts()
    {
        $data = $this->seedStandardTestData();

        Livewire::test(MaterialFifoDirectorDashboard::class)
            ->assertStatus(200)
            ->assertSee('FIFO Compliance Rate')
            ->assertSee('Material FIFO Executive Dashboard')
            ->set('whse_id', $data['warehouse']->id)
            ->assertSet('whse_id', $data['warehouse']->id)
            ->set('activeTab', 'deviations')
            ->assertSet('activeTab', 'deviations')
            ->set('activeTab', 'queue')
            ->assertSet('activeTab', 'queue')
            ->set('activeTab', 'aging')
            ->assertSet('activeTab', 'aging')
            ->set('activeTab', 'overview')
            ->assertSet('activeTab', 'overview')
            ->set('preset', 'today')
            ->assertSet('preset', 'today')
            ->set('preset', '7_days')
            ->assertSet('preset', '7_days')
            ->set('preset', '30_days')
            ->assertSet('preset', '30_days')
            ->set('preset', 'this_month')
            ->assertSet('preset', 'this_month');
    }
}
