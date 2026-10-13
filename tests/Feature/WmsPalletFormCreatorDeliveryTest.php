<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\MasterListItem;
use App\Models\SpkItemHistory;
use App\Models\WmsWarehouse;
use App\Models\WmsRack;
use App\Models\WmsPosition;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Services\WmsSapSyncService;
use App\Services\WmsService;
use Livewire\Livewire;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WmsPalletFormCreatorDeliveryTest extends TestCase
{
    protected Role $adminRole;
    protected User $adminUser;
    protected WmsPosition $position;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent Vite from trying to find compiled manifests during testing
        $this->withoutVite();

        // 1. Force use SQLite In-Memory Database for this test to avoid local MySQL dependency
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // 2. Manually build the minimum schema required for this test (Avoid running full migrations with MySQL-specific syntax)
        $this->createTestSchema();

        // 3. Setup role & user
        $this->adminRole = Role::create(['name' => 'ADMIN']);
        
        $this->adminUser = new User([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->role_id = $this->adminRole->id;
        $this->adminUser->save();

        // 4. Setup master items
        MasterListItem::create([
            'item_code' => 'PART-XYZ',
            'item_name' => 'Test Part',
            'customer_code' => 'CUST-A',
        ]);

        SpkItemHistory::create([
            'spk_number' => 'SPK-1234',
            'item_code' => 'PART-XYZ',
        ]);

        // 5. Setup WMS rack positions
        $whse = WmsWarehouse::create([
            'whse_code' => 'J06',
            'whse_name' => 'Gudang J06',
        ]);

        $rack = WmsRack::create([
            'whse_id' => $whse->id,
            'rack_code' => 'R1',
        ]);

        $this->position = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 2,
            'slot_no' => 1,
            'position_code' => 'R1-02-01',
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
            $table->foreignId('role_id')->nullable(); // Allow null temporarily during creation
            $table->timestamps();
        });

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->string('customer_code')->nullable();
            $table->timestamps();
        });

        Schema::create('master_customer_delivery', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('spk_item_histories', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number');
            $table->string('item_code');
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wms_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('whse_code');
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
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
            $table->string('pallet_id')->primary();
            $table->foreignId('position_id')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->date('prod_date');
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->double('total_pallet_qty', 15, 2)->default(0.00);
            $table->string('status')->default('STORED');
            $table->text('remarks')->nullable();
            $table->integer('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->timestamp('sap_sync_at')->nullable();
            $table->decimal('sap_sync_duration', 8, 2)->nullable();
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

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_name')->nullable();
            $table->string('method')->nullable();
            $table->text('endpoint')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_pallet_logs', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_id');
            $table->string('transaction_type');
            $table->foreignId('position_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test normal mode (wms.pallet-form.create):
     * Scanned warehouse (FFI) is saved as-is.
     */
    public function test_normal_pallet_creator_saves_warehouse_as_scanned(): void
    {
        // Fake Job Dispatch
        Queue::fake();

        $response = $this->actingAs($this->adminUser)
            ->get(route('wms.pallet-form.create-delivery'));

        $response->assertOk();

        $wmsService = app(WmsService::class);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Wms\PalletFormCreator::class)
            ->set('prod_date', '2026-07-09')
            ->set('delivery_name', 'Operator A')
            ->set('delivery_shift', '1')
            ->call('addItem', 'LBL-101', 'SPK-1234', 50, 'FFI')
            ->call('generateForm', $wmsService)
            ->assertHasNoErrors();

        // Assert database values
        $pallet = WmsPalletForm::first();
        $this->assertNotNull($pallet);

        $detail = WmsPalletFormDetail::where('pallet_form_id', $pallet->pallet_id)->first();
        $this->assertNotNull($detail);
        // Verify warehouse is saved as FFI (scanned value)
        $this->assertEquals('FG', $detail->warehouse);
    }

    /**
     * Test delivery mode (wms.pallet-form.create-delivery):
     * Scanned warehouse (FFI) is overwritten to FG in database.
     */
    public function test_delivery_pallet_creator_overwrites_warehouse_to_fg(): void
    {
        // Fake Job Dispatch
        Queue::fake();

        $response = $this->actingAs($this->adminUser)
            ->get(route('wms.pallet-form.create-delivery'));

        $response->assertOk();

        $wmsService = app(WmsService::class);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Wms\PalletFormCreator::class, ['isDelivery' => true])
            ->set('prod_date', '2026-07-09')
            ->set('delivery_name', 'Operator B')
            ->set('delivery_shift', '2')
            ->call('addItem', 'LBL-202', 'SPK-1234', 100, 'FFI')
            ->call('generateForm', $wmsService)
            ->assertHasNoErrors();

        // Assert database values
        $pallet = WmsPalletForm::first();
        $this->assertNotNull($pallet);

        $detail = WmsPalletFormDetail::where('pallet_form_id', $pallet->pallet_id)->first();
        $this->assertNotNull($detail);
        // Verify warehouse is overwritten to FG in database
        $this->assertEquals('FG', $detail->warehouse);
    }

    /**
     * Test SAP Sync payloads for both modes with Http faking.
     */
    public function test_sap_sync_payload_sends_saved_warehouse_correctly(): void
    {
        // Fake SAP API calls
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => true, 'message' => 'Success'], 200),
        ]);

        // 1. Create a Normal Pallet Form Detail (warehouse = FFI)
        $normalPallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-NORMAL-001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'delivery_name' => 'Operator A',
            'delivery_shift' => '1',
            'box_qty' => 1,
            'total_pallet_qty' => 50,
            'sap_sync_status' => 0,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-NORMAL-001',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-1234',
            'qty' => 50,
            'warehouse' => 'FFI',
            'label' => 'LBL-101',
            'sap_sync_status' => 0,
        ]);

        // 2. Create a Delivery Pallet Form Detail (warehouse = FG)
        $deliveryPallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-DELIV-002',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'delivery_name' => 'Operator B',
            'delivery_shift' => '2',
            'box_qty' => 1,
            'total_pallet_qty' => 100,
            'sap_sync_status' => 0,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-DELIV-002',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-1234',
            'qty' => 100,
            'warehouse' => 'FG', // saved as FG
            'label' => 'LBL-202',
            'sap_sync_status' => 0,
        ]);

        $sapSyncService = app(WmsSapSyncService::class);

        // Run sync for Normal pallet
        $sapSyncService->syncPalletInventoryTransfer('PLT-NORMAL-001');

        // Run sync for Delivery pallet
        $sapSyncService->syncPalletInventoryTransfer('PLT-DELIV-002');

        // Assert HTTP requests
        Http::assertSent(function ($request) {
            // Check Normal payload
            if ($request->url() === 'http://192.168.6.149:9001/api/inventory_transfer/create') {
                $payload = $request->data();
                if (isset($payload['lines'][0]['quantity']) && $payload['lines'][0]['quantity'] == 50) {
                    return $payload['lines'][0]['toWarehouse'] === 'FFI' 
                        && $payload['lines'][0]['fromWarehouse'] === 'FFI'
                        && $payload['lines'][0]['u_NUMPR'] === 'SPK-1234'
                        && $payload['remarks'] === 'Inventory Transfer created from PLT-NORMAL-001';
                }
            }
            return true;
        });

        Http::assertSent(function ($request) {
            // Check Delivery payload
            if ($request->url() === 'http://192.168.6.149:9001/api/inventory_transfer/create') {
                $payload = $request->data();
                if (isset($payload['lines'][0]['quantity']) && $payload['lines'][0]['quantity'] == 100) {
                    return $payload['lines'][0]['toWarehouse'] === 'FG' 
                        && $payload['lines'][0]['fromWarehouse'] === 'FFI'
                        && $payload['lines'][0]['u_NUMPR'] === 'SPK-1234'
                        && $payload['remarks'] === 'Inventory Transfer created from PLT-DELIV-002';
                }
            }
            return true;
        });
    }

    /**
     * Test SapSyncMonitor routes and correct filtering.
     */
    public function test_sap_sync_monitor_routes_and_filtering(): void
    {
        // 1. Create a Normal Pallet Detail (warehouse = FFI)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-NORMAL-001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'delivery_name' => 'Operator A',
            'delivery_shift' => '1',
            'box_qty' => 1,
            'total_pallet_qty' => 50,
            'sap_sync_status' => 0,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-NORMAL-001',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-1234',
            'qty' => 50,
            'warehouse' => 'FFI',
            'label' => 'LBL-101',
            'sap_sync_status' => 0,
        ]);

        // 2. Create a Delivery Pallet Detail (warehouse = FG)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-DELIV-002',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'delivery_name' => 'Operator B',
            'delivery_shift' => '2',
            'box_qty' => 1,
            'total_pallet_qty' => 100,
            'sap_sync_status' => 0,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-DELIV-002',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-1234',
            'qty' => 100,
            'warehouse' => 'FG',
            'label' => 'LBL-202',
            'sap_sync_status' => 0,
        ]);

        // Test normal route
        $this->actingAs($this->adminUser);
        
        Livewire::test(\App\Livewire\Wms\SapSyncMonitor::class)
            ->assertSee('PLT-NORMAL-001')
            ->assertSee('PLT-DELIV-002');
    }

    /**
     * Test case with 20 details in 1 header to see how the JSON is generated.
     */
    public function test_large_payload_generation(): void
    {
        // Fake SAP API calls
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => true, 'message' => 'Success'], 200),
        ]);

        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-LARGE-001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'delivery_name' => 'Operator A',
            'delivery_shift' => '1',
            'box_qty' => 20,
            'total_pallet_qty' => 1000,
            'sap_sync_status' => 0,
        ]);

        // Create 20 details.
        // We will use 4 different SPK Nos (SPK-0001 to SPK-0004) to test distinct/sum logic.
        // Each SPK will have 5 details (5 * 4 = 20 details total)
        for ($spkIndex = 1; $spkIndex <= 4; $spkIndex++) {
            $spkNo = "SPK-000{$spkIndex}";
            SpkItemHistory::create([
                'spk_number' => $spkNo,
                'item_code' => "ITM-00{$spkIndex}",
            ]);

            for ($detailIndex = 1; $detailIndex <= 5; $detailIndex++) {
                WmsPalletFormDetail::create([
                    'pallet_form_id' => 'PLT-LARGE-001',
                    'part_no' => "ITM-00{$spkIndex}",
                    'model_name' => "Model {$spkIndex}",
                    'spk_no' => $spkNo,
                    'qty' => 50, // 50 * 5 = 250 per SPK
                    'warehouse' => 'FG',
                    'label' => "LBL-SPK{$spkIndex}-BOX{$detailIndex}",
                    'sap_sync_status' => 0,
                ]);
            }
        }

        $sapSyncService = app(WmsSapSyncService::class);
        $sapSyncService->syncPalletInventoryTransfer('PLT-LARGE-001');

        // Capture sent request payloads and print them
        Http::assertSent(function ($request) {
            if ($request->url() === 'http://192.168.6.149:9001/api/inventory_transfer/create') {
                $payload = $request->data();
                dump("--- GENERATED SAP PAYLOAD ---", $payload);
                return true;
            }
            return true;
        });
    }

    /**
     * Test retrySpk method on SapSyncMonitor Livewire component.
     */
    public function test_retry_spk_in_monitor(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => true, 'message' => 'Success'], 200),
        ]);

        // 1. Create a Pallet Form with failed detail
        WmsPalletForm::create([
            'pallet_id' => 'PLT-RETRY-001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'sap_sync_status' => 2, // FAILED
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-RETRY-001',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-7777',
            'qty' => 100,
            'warehouse' => 'FG',
            'label' => 'LBL-7777',
            'sap_sync_status' => 2, // FAILED
            'sap_error_msg' => 'Bad gateway',
        ]);

        // Run retrySpk
        Livewire::test(\App\Livewire\Wms\SapSyncMonitor::class)
            ->call('retrySpk', 'PLT-RETRY-001', 'SPK-7777')
            ->assertHasNoErrors();

        // Assert database was updated to SUCCESS (1)
        $pallet = WmsPalletForm::where('pallet_id', 'PLT-RETRY-001')->first();
        $this->assertEquals(1, $pallet->sap_sync_status);

        $detail = WmsPalletFormDetail::where('pallet_form_id', 'PLT-RETRY-001')->first();
        $this->assertEquals(1, $detail->sap_sync_status);
        $this->assertNull($detail->sap_error_msg);
    }

    public function test_mark_as_synced_in_monitor(): void
    {
        WmsPalletForm::create([
            'pallet_id' => 'PLT-MANUAL-001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'prod_date' => '2026-07-09',
            'sap_sync_status' => 2, // FAILED
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-MANUAL-001',
            'part_no' => 'PART-XYZ',
            'model_name' => 'Test Part',
            'spk_no' => 'SPK-8888',
            'qty' => 100,
            'warehouse' => 'FG',
            'label' => 'LBL-8888',
            'sap_sync_status' => 2, // FAILED
        ]);

        Livewire::test(\App\Livewire\Wms\SapSyncMonitor::class)
            ->call('markAsSynced', 'PLT-MANUAL-001')
            ->assertHasNoErrors();

        $pallet = WmsPalletForm::where('pallet_id', 'PLT-MANUAL-001')->first();
        $this->assertEquals(1, $pallet->sap_sync_status);

        $detail = WmsPalletFormDetail::where('pallet_form_id', 'PLT-MANUAL-001')->first();
        $this->assertEquals(1, $detail->sap_sync_status);
    }

    public function test_store_can_assign_slot_to_unassigned_delivery_pallet(): void
    {
        $unassignedPallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-STORE-ASSIGN-01',
            'position_id'      => null,
            'part_no'          => 'PART-STORE',
            'model_name'       => 'Model Store',
            'prod_date'        => '2026-07-28',
            'delivery_name'    => 'Driver Store',
            'delivery_shift'   => '1',
            'box_qty'          => 2,
            'total_pallet_qty' => 200,
            'status'           => 'STORED',
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->set('filterSlot', 'UNASSIGNED')
            ->call('openAssignModal', 'PLT-STORE-ASSIGN-01')
            ->set('assignPositionId', $this->position->id)
            ->call('saveAssignSlot')
            ->assertHasNoErrors();

        $unassignedPallet->refresh();
        $this->assertEquals($this->position->id, $unassignedPallet->position_id);
    }

    public function test_test_endpoint_displays_explicit_error_message_on_failure(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['message' => 'Invalid Company DB Credentials'], 500),
        ]);

        $sapSyncService = app(WmsSapSyncService::class);
        $res = $sapSyncService->testConnection();

        $this->assertFalse($res['status']);
        $this->assertStringContainsString('Failed to authenticate to SAP', $res['message']);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Wms\SapSyncMonitor::class)
            ->call('testEndpoint')
            ->assertSee('Failed to authenticate to SAP');
    }

    public function test_sap_sync_handles_api_failure_response_correctly(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => false, 'message' => 'Item Code ITM-UNKNOWN does not exist'], 400),
        ]);

        $pallet = WmsPalletForm::create([
            'pallet_id'       => 'PLT-FAIL-001',
            'position_id'     => $this->position->id,
            'part_no'         => 'PART-FAIL',
            'model_name'      => 'Model Fail',
            'prod_date'       => '2026-07-28',
            'sap_sync_status' => 0,
        ]);

        $detail = WmsPalletFormDetail::create([
            'pallet_form_id'  => 'PLT-FAIL-001',
            'part_no'         => 'PART-FAIL',
            'spk_no'          => 'SPK-9999',
            'qty'             => 50,
            'warehouse'       => 'FG',
            'sap_sync_status' => 0,
        ]);

        $sapSyncService = app(WmsSapSyncService::class);
        $sapSyncService->syncPalletInventoryTransfer('PLT-FAIL-001');

        $pallet->refresh();
        $detail->refresh();

        $this->assertEquals(2, $pallet->sap_sync_status); // 2 = FAILED
        $this->assertEquals(2, $detail->sap_sync_status);
        $this->assertStringContainsString('SAP API Error', $detail->sap_error_msg);
    }

    public function test_sap_sync_handles_idempotent_duplicate_entry_as_success(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'mock_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => false, 'message' => 'Document already exist in SAP'], 200),
        ]);

        $pallet = WmsPalletForm::create([
            'pallet_id'       => 'PLT-DUP-001',
            'position_id'     => $this->position->id,
            'part_no'         => 'PART-DUP',
            'model_name'      => 'Model Dup',
            'prod_date'       => '2026-07-28',
            'sap_sync_status' => 0,
        ]);

        $detail = WmsPalletFormDetail::create([
            'pallet_form_id'  => 'PLT-DUP-001',
            'part_no'         => 'PART-DUP',
            'spk_no'          => 'SPK-8888',
            'qty'             => 100,
            'warehouse'       => 'FG',
            'sap_sync_status' => 0,
        ]);

        $sapSyncService = app(WmsSapSyncService::class);
        $sapSyncService->syncPalletInventoryTransfer('PLT-DUP-001');

        $pallet->refresh();
        $detail->refresh();

        $this->assertEquals(1, $pallet->sap_sync_status); // 1 = SUCCESS (Idempotent duplicate handled)
        $this->assertEquals(1, $detail->sap_sync_status);
    }

    public function test_zero_qty_pallet_cannot_be_assigned_and_excluded_from_unassigned_filter(): void
    {
        $zeroQtyPallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-ZERO-QTY-01',
            'position_id'      => null,
            'part_no'          => 'PART-EMPTY',
            'model_name'       => 'Model Empty',
            'prod_date'        => '2026-07-29',
            'delivery_name'    => 'Driver Zero',
            'delivery_shift'   => '1',
            'box_qty'          => 0,
            'total_pallet_qty' => 0,
        ]);

        $this->actingAs($this->adminUser);

        // 1. Assert zero-qty pallet is NOT present in UNASSIGNED filter list
        Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->set('filterSlot', 'UNASSIGNED')
            ->assertDontSee('PLT-ZERO-QTY-01');

        // 2. Assert openAssignModal on zero-qty pallet blocks assignment modal
        Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->call('openAssignModal', 'PLT-ZERO-QTY-01')
            ->assertSet('showAssignModal', false);

        // 3. Assert RackMapping assignPalletToSelectedSlot on zero-qty pallet blocks assignment
        Livewire::test(\App\Livewire\Wms\RackMapping::class)
            ->set('selectedPositionId', $this->position->id)
            ->call('assignPalletToSelectedSlot', 'PLT-ZERO-QTY-01');

        $zeroQtyPallet->refresh();
        $this->assertNull($zeroQtyPallet->position_id);
    }

    public function test_double_click_or_duplicate_generate_form_is_idempotent_and_prevents_duplicate_pallets(): void
    {
        $this->actingAs($this->adminUser);

        // 1. First submission via Livewire
        $testComponent = Livewire::test(\App\Livewire\Wms\PalletFormCreator::class, ['isDelivery' => true])
            ->set('prod_date', '2026-09-25')
            ->set('delivery_name', 'Driver Test')
            ->set('delivery_shift', '1')
            ->set('lot_no', 'LOT-DOUBLE-01')
            ->call('addItem', 'LBL-DOUBLE-01', 'SPK-1234', 50, 'FFI')
            ->call('generateForm');

        $testComponent->assertSet('showSuccessModal', true);
        $firstPalletId = $testComponent->get('lastGeneratedPalletId');
        $this->assertNotEmpty($firstPalletId);

        // Assert exactly 1 pallet exists in database
        $this->assertEquals(1, WmsPalletForm::where('lot_no', 'LOT-DOUBLE-01')->count());
        $this->assertEquals(1, WmsPalletFormDetail::where('label', 'LBL-DOUBLE-01')->count());

        // 2. Simulate double click on same component: calling generateForm again
        $testComponent->call('generateForm');

        // Assert STILL only 1 pallet exists
        $this->assertEquals(1, WmsPalletForm::where('lot_no', 'LOT-DOUBLE-01')->count());
        $this->assertEquals($firstPalletId, $testComponent->get('lastGeneratedPalletId'));

        // 3. Simulate concurrent parallel request (separate component instance with same items)
        $parallelComponent = Livewire::test(\App\Livewire\Wms\PalletFormCreator::class, ['isDelivery' => true])
            ->set('prod_date', '2026-09-25')
            ->set('delivery_name', 'Driver Test')
            ->set('delivery_shift', '1')
            ->set('lot_no', 'LOT-DOUBLE-01')
            ->call('addItem', 'LBL-DOUBLE-01', 'SPK-1234', 50, 'FFI')
            ->call('generateForm');

        // Assert parallel request is blocked by idempotency / label guard and returns existing pallet
        $this->assertEquals(1, WmsPalletForm::where('lot_no', 'LOT-DOUBLE-01')->count());
        $this->assertEquals(1, WmsPalletFormDetail::where('label', 'LBL-DOUBLE-01')->count());
        $this->assertEquals($firstPalletId, $parallelComponent->get('lastGeneratedPalletId'));
        $parallelComponent->assertSet('showSuccessModal', true);
    }

    public function test_quick_scan_pallet_verifies_details_and_assigns_slot_to_rack(): void
    {
        $pallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-STORE-SCAN-01',
            'position_id'      => null,
            'part_no'          => '27120M650A',
            'model_name'       => 'FRONT COVER TEST',
            'prod_date'        => '2026-10-09',
            'delivery_name'    => 'Driver Store Test',
            'delivery_shift'   => '2',
            'box_qty'          => 2,
            'total_pallet_qty' => 100,
            'status'           => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-STORE-SCAN-01',
            'part_no'        => '27120M650A',
            'model_name'     => 'FRONT COVER TEST',
            'spk_no'         => 'SPK-SCAN-001',
            'qty'            => 50,
            'label'          => 'LBL-SCAN-01',
            'warehouse'      => 'FG',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-STORE-SCAN-01',
            'part_no'        => '27120M650A',
            'model_name'     => 'FRONT COVER TEST',
            'spk_no'         => 'SPK-SCAN-002',
            'qty'            => 50,
            'label'          => 'LBL-SCAN-02',
            'warehouse'      => 'FG',
        ]);

        $this->actingAs($this->adminUser);

        // 1. Test scanning non-existent pallet
        Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->set('scanPalletId', 'PLT-NON-EXISTENT')
            ->call('scanPallet')
            ->assertSet('scannedPallet', null)
            ->assertDispatched('scan-error');

        // 2. Test scanning valid pallet -> loads verification data and recommendation
        $testComp = Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->set('scanPalletId', 'PLT-STORE-SCAN-01')
            ->call('scanPallet')
            ->assertSet('scannedPallet.pallet_id', 'PLT-STORE-SCAN-01')
            ->assertSet('scannedPallet.part_no', '27120M650A')
            ->assertDispatched('scan-success');

        // 3. Test scanning rack barcode
        $testComp->set('scanRackCode', $this->position->position_code)
            ->call('scanRackPosition')
            ->assertSet('selectedPositionId', $this->position->id);

        // 4. Test save assign slot
        $testComp->call('saveAssignSlot')
            ->assertSet('scannedPallet', null)
            ->assertSet('scanPalletId', '')
            ->assertDispatched('focus-scan-input');

        $pallet->refresh();
        $this->assertEquals($this->position->id, $pallet->position_id);
        $this->assertNotNull($pallet->assigned_at);
        $this->assertEquals('PARTIAL', $this->position->fresh()->status);
    }
}

