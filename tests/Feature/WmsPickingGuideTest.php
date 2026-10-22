<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\SoData;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use App\Services\WmsPickingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPickingGuideTest extends TestCase
{
    protected Role $adminRole;

    protected User $adminUser;

    protected WmsPosition $position1;

    protected WmsPosition $position2;

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

        $this->adminRole = Role::create(['name' => 'ADMIN']);

        $this->adminUser = new User([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->role_id = $this->adminRole->id;
        $this->adminUser->save();

        // Setup master items
        MasterListItem::create([
            'item_code' => 'PART-A',
            'item_name' => 'Part A Name',
            'customer_code' => 'CUST-A',
        ]);
        MasterListItem::create([
            'item_code' => 'PART-B',
            'item_name' => 'Part B Name',
            'customer_code' => 'CUST-A',
        ]);

        // Setup WMS Racks & Positions on level 2 (standard level)
        $whse = WmsWarehouse::create([
            'whse_code' => 'J06',
            'whse_name' => 'Gudang J06',
        ]);

        $rack = WmsRack::create([
            'whse_id' => $whse->id,
            'rack_code' => 'R1',
        ]);

        $this->position1 = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 2,
            'slot_no' => 1,
            'position_code' => 'R1-02-01',
            'customer_code' => 'CUST-A',
            'status' => 'EMPTY',
            'max_capacity' => 10,
        ]);

        $this->position2 = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 2,
            'slot_no' => 2,
            'position_code' => 'R1-02-02',
            'customer_code' => 'CUST-A',
            'status' => 'EMPTY',
            'max_capacity' => 10,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->string('customer_code')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('whse_code');
            $table->string('whse_name');
            $table->timestamps();
        });

        Schema::create('wms_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
        });

        Schema::create('wms_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id');
            $table->integer('level_no');
            $table->integer('slot_no');
            $table->string('customer_code')->nullable();
            $table->string('position_code')->unique();
            $table->string('status')->default('EMPTY');
            $table->string('last_item_code')->nullable();
            $table->integer('max_capacity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_forms', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_id')->unique();
            $table->foreignId('position_id');
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->date('prod_date');
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->double('total_pallet_qty', 15, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->integer('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->timestamp('sap_sync_at')->nullable();
            $table->string('status')->default('STORED');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_form_details', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_form_id');
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->string('spk_no')->nullable();
            $table->double('qty', 15, 2)->default(0.00);
            $table->string('warehouse')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_no_label')->default(false);
            $table->string('no_label_reason')->nullable();
            $table->integer('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->timestamp('sap_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('so_datas', function (Blueprint $table) {
            $table->id();
            $table->string('doc_num');
            $table->string('customer')->nullable();
            $table->string('item_code');
            $table->string('item_name')->nullable();
            $table->double('quantity', 15, 2)->default(0.00);
            $table->boolean('is_done')->default(false);
        });

        Schema::create('wms_picking_headers', function (Blueprint $table) {
            $table->id();
            $table->string('picking_no')->unique();
            $table->string('doc_num')->nullable();
            $table->string('status')->default('PENDING');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_picking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('picking_header_id');
            $table->string('item_code');
            $table->string('model_name')->nullable();
            $table->string('spk_no')->nullable();
            $table->string('label')->nullable();
            $table->string('pallet_id')->nullable();
            $table->string('position_code')->nullable();
            $table->double('qty_to_pick', 15, 2)->default(0.00);
            $table->double('qty_picked', 15, 2)->default(0.00);
            $table->boolean('is_picked')->default(false);
            $table->integer('fifo_seq')->nullable();
            $table->string('status')->default('AVAILABLE');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_picking_service_fifo_logic_and_load_from_doc(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create SO Data
        SoData::create([
            'doc_num' => 'DO-100',
            'customer' => 'CUST-A',
            'item_code' => 'PART-A',
            'item_name' => 'Part A Name',
            'quantity' => 100,
            'is_done' => false,
        ]);

        // 2. Create boxes in Pallet PLT-1 (stored early, oldest timestamp)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-1',
            'position_id' => $this->position1->id,
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'prod_date' => now()->format('Y-m-d'),
            'status' => 'STORED',
        ]);

        $detail1 = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-1',
            'part_no' => 'PART-A',
            'qty' => 60,
            'label' => 'LBL-001',
        ]);
        // Set creation time to 2 hours ago
        $detail1->created_at = now()->subHours(2);
        $detail1->save();

        // 3. Create boxes in Pallet PLT-2 (stored later)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-2',
            'position_id' => $this->position2->id,
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'prod_date' => now()->format('Y-m-d'),
            'status' => 'STORED',
        ]);

        $detail2 = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-2',
            'part_no' => 'PART-A',
            'qty' => 80,
            'label' => 'LBL-002',
        ]);
        // Set creation time to 1 hour ago
        $detail2->created_at = now()->subHour();
        $detail2->save();

        // 4. Test service methods
        $service = new WmsPickingService;

        // Test loading SO
        $soItems = $service->loadItemsFromDocNum('DO-100');
        $this->assertCount(1, $soItems);
        $this->assertEquals('PART-A', $soItems[0]['item_code']);
        $this->assertEquals(100, $soItems[0]['quantity']);

        // Test FIFO routing calculation for Qty 100
        $route = $service->calculateFifoPickingRoute([
            ['item_code' => 'PART-A', 'item_name' => 'Part A Name', 'quantity' => 100],
        ]);

        // We expect 2 instructions because box 1 (60 pcs) + box 2 (40 pcs from total 80 pcs) are allocated
        $this->assertCount(2, $route);

        // Verification order: oldest first (LBL-001)
        $this->assertEquals('LBL-001', $route[0]['label']);
        $this->assertEquals(60, $route[0]['qty_to_pick']);
        $this->assertEquals('R1-02-01', $route[0]['position_code']);

        // Next box (LBL-002)
        $this->assertEquals('LBL-002', $route[1]['label']);
        $this->assertEquals(40, $route[1]['qty_to_pick']);
        $this->assertEquals('R1-02-02', $route[1]['position_code']);

        // 5. Test out-of-stock fallback handling (PART-C does not exist)
        $routeOos = $service->calculateFifoPickingRoute([
            ['item_code' => 'PART-C', 'item_name' => 'Part C Name', 'quantity' => 20],
        ]);

        $this->assertCount(1, $routeOos);
        $this->assertEquals('OUT_OF_STOCK', $routeOos[0]['status']);
        $this->assertEquals('KOSONG', $routeOos[0]['position_code']);
        $this->assertEquals('KOSONG', $routeOos[0]['pallet_id']);
        $this->assertEquals(20, $routeOos[0]['qty_to_pick']);
        // 6. Test database persistence: createPickingList
        $header = $service->createPickingList('DO-100', [
            ['item_code' => 'PART-A', 'item_name' => 'Part A Name', 'quantity' => 100],
        ], $this->adminUser->id);

        $this->assertDatabaseHas('wms_picking_headers', [
            'picking_no' => $header->picking_no,
            'doc_num' => 'DO-100',
            'status' => 'PENDING',
        ]);

        $this->assertCount(2, $header->details);
        $this->assertEquals('LBL-001', $header->details[0]->label);

        // Test toggle pick status
        $firstDetail = $header->details[0];
        $service->togglePickState($firstDetail->id, true);

        $this->assertDatabaseHas('wms_picking_details', [
            'id' => $firstDetail->id,
            'is_picked' => true,
            'qty_picked' => 60,
        ]);

        // Status should be PICKING (since detail 2 is not picked)
        $this->assertEquals('PICKING', $header->fresh()->status);

        // Pick detail 2
        $secondDetail = $header->details[1];
        $service->togglePickState($secondDetail->id, true);

        // All active details picked, status should become COMPLETED
        $this->assertEquals('COMPLETED', $header->fresh()->status);
    }

    public function test_picking_guide_livewire_component(): void
    {
        $this->actingAs($this->adminUser);

        SoData::create([
            'doc_num' => 'DO-200',
            'item_code' => 'PART-B',
            'quantity' => 50,
        ]);

        // Run component test
        $component = Livewire::test(\App\Livewire\Wms\PickingGuide::class)
            ->set('docNumSearch', 'DO-200')
            ->call('loadFromDocNum');

        // Check if loaded items are assigned
        $requestItems = $component->get('requestItems');
        $this->assertCount(1, $requestItems);
        $this->assertEquals('PART-B', $requestItems[0]['item_code']);
        $this->assertEquals(50, $requestItems[0]['quantity']);

        // Create picking sheet (saves to DB and sets activePickingId)
        $component->call('createPickingSheet');
        $activeId = $component->get('activePickingId');

        $this->assertNotNull($activeId);

        $header = $component->get('activePickingHeader');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->details);
        $this->assertEquals('OUT_OF_STOCK', $header->details[0]->status);
    }
}
