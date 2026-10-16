<?php

namespace Tests\Feature;

use App\Jobs\SyncPalletToSapJob;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\SpkItemHistory;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPalletSortingTest extends TestCase
{
    protected Role $adminRole;

    protected User $adminUser;

    protected WmsPosition $position1;

    protected WmsPosition $position2;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

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

        SpkItemHistory::create([
            'spk_number' => 'SPK-A',
            'item_code' => 'PART-A',
        ]);
        SpkItemHistory::create([
            'spk_number' => 'SPK-B',
            'item_code' => 'PART-B',
        ]);

        // Setup WMS Racks
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

        WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 2,
            'slot_no' => 3,
            'position_code' => 'R1-02-03',
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

        Schema::create('spk_item_histories', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number');
            $table->string('item_code');
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

    public function test_pallet_sorting_can_move_boxes_and_apply_changes(): void
    {
        Queue::fake();
        $this->actingAs($this->adminUser);

        // 1. Create a mixed Pallet: PLT-A has Box A (SPK-A) and Box B (SPK-B)
        $palletA = WmsPalletForm::create([
            'pallet_id' => 'PLT-A',
            'position_id' => $this->position1->id,
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'prod_date' => now()->format('Y-m-d'),
            'box_qty' => 2,
            'total_pallet_qty' => 100,
            'status' => 'STORED',
        ]);

        $detailA1 = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-A',
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'spk_no' => 'SPK-A',
            'qty' => 40,
            'warehouse' => 'FG',
            'label' => 'LBL-001',
        ]);

        $detailA2 = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-A',
            'part_no' => 'PART-B',
            'model_name' => 'Part B Name',
            'spk_no' => 'SPK-B',
            'qty' => 60,
            'warehouse' => 'FG',
            'label' => 'LBL-002',
        ]);

        // 2. Create another Pallet: PLT-B has Box C (SPK-A)
        $palletB = WmsPalletForm::create([
            'pallet_id' => 'PLT-B',
            'position_id' => $this->position2->id,
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'prod_date' => now()->format('Y-m-d'),
            'box_qty' => 1,
            'total_pallet_qty' => 50,
            'status' => 'STORED',
        ]);

        $detailB1 = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-B',
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'spk_no' => 'SPK-A',
            'qty' => 50,
            'warehouse' => 'FG',
            'label' => 'LBL-003',
        ]);

        // Run Livewire component
        $component = Livewire::test(\App\Livewire\Wms\PalletSorting::class)
            // Load Pallet A and B to workspace
            ->set('palletSearchInput', 'PLT-A')
            ->call('addPallet')
            ->set('palletSearchInput', 'PLT-B')
            ->call('addPallet');

        // Check if both pallets are loaded in memory
        $workspace = $component->get('workspacePallets');
        $this->assertCount(2, $workspace);

        // Find LBL-002 (SPK-B from PLT-A)
        $lbl002Cid = null;
        foreach ($workspace[0]['boxes'] as $box) {
            if ($box['label'] === 'LBL-002') {
                $lbl002Cid = $box['cid'];
                break;
            }
        }
        $this->assertNotNull($lbl002Cid);

        // Add a new target pallet
        $component->call('addNewTargetPallet');
        $workspace = $component->get('workspacePallets');
        $this->assertCount(3, $workspace);
        $newPalletId = $workspace[2]['pallet_id'];

        // Move LBL-002 to the new target pallet
        $component->call('moveBox', $lbl002Cid, $newPalletId);

        // Apply sorting
        $component->call('applySorting');

        // Verify Database Changes
        // PLT-A should now only have 1 box (SPK-A) and is homogeneous (PART-A)
        $this->assertDatabaseHas('wms_pallet_forms', [
            'pallet_id' => 'PLT-A',
            'part_no' => 'PART-A',
            'box_qty' => 1,
            'total_pallet_qty' => 40,
        ]);

        // The moved box (LBL-002) should be assigned to the new pallet
        $this->assertDatabaseHas('wms_pallet_form_details', [
            'label' => 'LBL-002',
            'pallet_form_id' => $newPalletId,
        ]);

        // The new pallet should exist with 1 box and correct summary
        $this->assertDatabaseHas('wms_pallet_forms', [
            'pallet_id' => $newPalletId,
            'part_no' => 'PART-B',
            'box_qty' => 1,
            'total_pallet_qty' => 60,
        ]);

        // Assert SAP sync jobs were dispatched for all 3 pallets
        Queue::assertPushed(SyncPalletToSapJob::class, 3);
    }

    public function test_pallet_sorting_can_change_and_swap_positions(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Setup Pallet A on Position 1
        WmsPalletForm::create([
            'pallet_id' => 'PLT-A',
            'position_id' => $this->position1->id,
            'part_no' => 'PART-A',
            'model_name' => 'Part A Name',
            'prod_date' => now()->format('Y-m-d'),
            'box_qty' => 1,
            'total_pallet_qty' => 50,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-A',
            'part_no' => 'PART-A',
            'qty' => 50,
            'label' => 'LBL-001',
        ]);

        // 2. Setup Pallet B on Position 2
        WmsPalletForm::create([
            'pallet_id' => 'PLT-B',
            'position_id' => $this->position2->id,
            'part_no' => 'PART-B',
            'model_name' => 'Part B Name',
            'prod_date' => now()->format('Y-m-d'),
            'box_qty' => 1,
            'total_pallet_qty' => 60,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-B',
            'part_no' => 'PART-B',
            'qty' => 60,
            'label' => 'LBL-002',
        ]);

        // Run Livewire component
        $component = Livewire::test(\App\Livewire\Wms\PalletSorting::class)
            ->set('palletSearchInput', 'PLT-A')
            ->call('addPallet')
            ->set('palletSearchInput', 'PLT-B')
            ->call('addPallet');

        // Verify loaded positions in workspace
        $workspace = $component->get('workspacePallets');
        $this->assertEquals($this->position1->id, $workspace[0]['position_id']);
        $this->assertEquals($this->position2->id, $workspace[1]['position_id']);

        // Change Pallet A to Position 2 (occupied by Pallet B). This should trigger a swap!
        $component->call('changePalletPosition', 'PLT-A', $this->position2->id);

        $workspace = $component->get('workspacePallets');
        // Pallet A should now be on Position 2
        $this->assertEquals($this->position2->id, $workspace[0]['position_id']);
        // Pallet B should now be swapped to Position 1
        $this->assertEquals($this->position1->id, $workspace[1]['position_id']);

        // Apply changes
        $component->call('applySorting');

        // Assert database reflects updated positions
        $this->assertDatabaseHas('wms_pallet_forms', [
            'pallet_id' => 'PLT-A',
            'position_id' => $this->position2->id,
        ]);
        $this->assertDatabaseHas('wms_pallet_forms', [
            'pallet_id' => 'PLT-B',
            'position_id' => $this->position1->id,
        ]);
    }
}
