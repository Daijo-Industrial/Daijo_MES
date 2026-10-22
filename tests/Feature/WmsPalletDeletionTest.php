<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPalletDeletionTest extends TestCase
{
    protected Role $adminRole;

    protected User $adminUser;

    protected WmsPosition $position;

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

        $this->actingAs($this->adminUser);

        // Create warehouse, rack, position
        $whse = WmsWarehouse::create([
            'whse_name' => 'Warehouse J06',
            'whse_code' => 'J06',
        ]);

        $rack = WmsRack::create([
            'whse_id' => $whse->id,
            'rack_code' => 'R01',
        ]);

        $this->position = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'R01-L01-S01',
            'max_capacity' => 1,
            'status' => 'EMPTY',
        ]);

        MasterListItem::create([
            'item_code' => 'PART-001',
            'item_name' => 'Part 001 Name',
        ]);
    }

    protected function createTestSchema(): void
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
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('customer_code')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('whse_name')->nullable();
            $table->string('whse_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->nullable();
            $table->integer('level_no')->default(1);
            $table->integer('slot_no')->default(1);
            $table->string('position_code');
            $table->integer('max_capacity')->default(1);
            $table->string('customer_code')->nullable();
            $table->string('last_item_code')->nullable();
            $table->string('status')->default('EMPTY');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_forms', function (Blueprint $table) {
            $table->string('pallet_id')->primary();
            $table->foreignId('position_id')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->date('prod_date')->nullable();
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->decimal('total_pallet_qty', 15, 2)->default(0);
            $table->string('status')->default('STORED');
            $table->text('remarks')->nullable();
            $table->tinyInteger('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->dateTime('sap_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_form_details', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_form_id');
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->string('spk_no')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->string('warehouse')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_no_label')->default(false);
            $table->text('no_label_reason')->nullable();
            $table->tinyInteger('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->dateTime('sap_sync_at')->nullable();
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

    public function test_rack_mapping_can_delete_pallet_and_updates_slot_status()
    {
        // 1. Create a pallet assigned to position
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-20260907-0001',
            'position_id' => $this->position->id,
            'part_no' => 'PART-001',
            'total_pallet_qty' => 100,
            'box_qty' => 2,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'PART-001',
            'spk_no' => 'SPK-001',
            'qty' => 50,
        ]);
        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'PART-001',
            'spk_no' => 'SPK-002',
            'qty' => 50,
        ]);

        $this->position->update(['status' => 'FULL']);

        // 2. Call deletePallet on RackMapping Livewire component
        Livewire::test(\App\Livewire\Wms\RackMapping::class)
            ->set('selectedPositionId', $this->position->id)
            ->call('deletePallet', 'PLT-20260907-0001');

        // 3. Verify pallet is soft-deleted
        $this->assertSoftDeleted('wms_pallet_forms', [
            'pallet_id' => 'PLT-20260907-0001',
        ]);

        // 4. Verify pallet details are deleted
        $this->assertSoftDeleted('wms_pallet_form_details', [
            'pallet_form_id' => 'PLT-20260907-0001',
            'spk_no' => 'SPK-001',
        ]);

        // 5. Verify position status is now EMPTY
        $this->assertEquals('EMPTY', $this->position->fresh()->status);

        // 6. Verify log is recorded
        $this->assertDatabaseHas('wms_pallet_logs', [
            'pallet_id' => 'PLT-20260907-0001',
            'transaction_type' => 'DELETE_PALLET',
        ]);
    }

    public function test_rack_mapping_can_unassign_pallet_from_slot()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-20260907-0002',
            'position_id' => $this->position->id,
            'assigned_at' => now(),
            'part_no' => 'PART-001',
            'total_pallet_qty' => 50,
            'box_qty' => 1,
            'status' => 'STORED',
        ]);

        $this->position->update(['status' => 'FULL']);

        Livewire::test(\App\Livewire\Wms\RackMapping::class)
            ->set('selectedPositionId', $this->position->id)
            ->call('unassignPalletFromSlot', 'PLT-20260907-0002');

        $freshPallet = $pallet->fresh();
        $this->assertNull($freshPallet->position_id);
        $this->assertNull($freshPallet->assigned_at);
        $this->assertEquals('EMPTY', $this->position->fresh()->status);

        $this->assertDatabaseHas('wms_pallet_logs', [
            'pallet_id' => 'PLT-20260907-0002',
            'transaction_type' => 'UNASSIGN_SLOT',
        ]);
    }

    public function test_rack_mapping_reset_slot_unassigns_all_pallets_in_slot()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-20260907-0003',
            'position_id' => $this->position->id,
            'assigned_at' => now(),
            'part_no' => 'PART-001',
            'total_pallet_qty' => 80,
            'box_qty' => 2,
            'status' => 'STORED',
        ]);

        $this->position->update(['status' => 'FULL', 'last_item_code' => 'PART-001']);

        Livewire::test(\App\Livewire\Wms\RackMapping::class)
            ->set('selectedPositionId', $this->position->id)
            ->call('resetSlot');

        $this->assertNull($pallet->fresh()->position_id);
        $this->assertEquals('EMPTY', $this->position->fresh()->status);
        $this->assertNull($this->position->fresh()->last_item_code);

        $this->assertDatabaseHas('wms_pallet_logs', [
            'pallet_id' => 'PLT-20260907-0003',
            'transaction_type' => 'UNASSIGN_SLOT',
        ]);
    }

    public function test_pallet_form_index_can_delete_pallet()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-20260907-0004',
            'position_id' => $this->position->id,
            'part_no' => 'PART-001',
            'total_pallet_qty' => 60,
            'box_qty' => 1,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'PART-001',
            'spk_no' => 'SPK-004',
            'qty' => 60,
        ]);

        $this->position->update(['status' => 'FULL']);

        Livewire::test(\App\Livewire\Wms\PalletFormIndex::class)
            ->call('deletePallet', 'PLT-20260907-0004');

        $this->assertSoftDeleted('wms_pallet_forms', [
            'pallet_id' => 'PLT-20260907-0004',
        ]);
        $this->assertSoftDeleted('wms_pallet_form_details', [
            'pallet_form_id' => 'PLT-20260907-0004',
        ]);
        $this->assertEquals('EMPTY', $this->position->fresh()->status);
    }

    public function test_pallet_form_lookup_can_delete_pallet()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-20260907-0005',
            'position_id' => $this->position->id,
            'part_no' => 'PART-001',
            'total_pallet_qty' => 40,
            'box_qty' => 1,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'PART-001',
            'spk_no' => 'SPK-005',
            'qty' => 40,
        ]);

        $this->position->update(['status' => 'FULL']);

        Livewire::test(\App\Livewire\Wms\PalletFormLookup::class)
            ->set('pallet_id', 'PLT-20260907-0005')
            ->call('deletePallet');

        $this->assertSoftDeleted('wms_pallet_forms', [
            'pallet_id' => 'PLT-20260907-0005',
        ]);
        $this->assertSoftDeleted('wms_pallet_form_details', [
            'pallet_form_id' => 'PLT-20260907-0005',
        ]);
        $this->assertEquals('EMPTY', $this->position->fresh()->status);
    }
}
