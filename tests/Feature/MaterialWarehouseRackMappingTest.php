<?php

namespace Tests\Feature;

use App\Livewire\MaterialWarehouse\RackMapping;
use App\Models\MwhPosition;
use App\Models\MwhRack;
use App\Models\MwhWarehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialWarehouseRackMappingTest extends TestCase
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
        \Illuminate\Support\Facades\Schema::create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('branches', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_warehouses', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('whse_code')->unique();
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_racks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_positions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id');
            $table->integer('level_no');
            $table->integer('slot_no');
            $table->string('position_code')->unique();
            $table->string('slot_label')->nullable();
            $table->string('status')->default('EMPTY');
            $table->string('last_item_code')->nullable();
            $table->integer('max_capacity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('master_list_materials', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_description')->nullable();
            $table->string('purchasing_uom')->default('KG');
            $table->string('preferred_supplier')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_incoming_headers', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('document_no');
            $table->string('incoming_type')->default('SUPPLIER');
            $table->string('supplier_name')->nullable();
            $table->string('returned_from')->nullable();
            $table->string('po_number')->nullable();
            $table->string('original_outgoing_code')->nullable();
            $table->date('arrival_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_pallets', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('pallet_id')->unique();
            $table->foreignId('incoming_header_id')->nullable();
            $table->string('item_code');
            $table->string('lot_no')->nullable();
            $table->decimal('initial_qty', 10, 2)->default(0);
            $table->decimal('current_qty', 10, 2)->default(0);
            $table->string('uom')->default('KG');
            $table->foreignId('position_id')->nullable();
            $table->foreignId('initial_position_id')->nullable();
            $table->string('status')->default('STORED');
            $table->boolean('is_qc_hold')->default(false);
            $table->string('qc_hold_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \Illuminate\Support\Facades\Schema::create('mwh_outgoings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id')->nullable();
            $table->string('outgoing_code');
            $table->string('pallet_id');
            $table->foreignId('position_id')->nullable();
            $table->string('item_code');
            $table->decimal('qty_taken', 10, 2);
            $table->string('uom')->default('KG');
            $table->date('outgoing_date')->nullable();
            $table->string('issued_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_can_render_material_warehouse_rack_mapping()
    {
        $role = \App\Models\Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('mwh.mapping'))
            ->assertStatus(200);
    }

    public function test_can_create_material_rack_and_positions()
    {
        Livewire::test(RackMapping::class)
            ->set('newRackCode', 'MTR-01')
            ->set('newLevels', 2)
            ->set('newSlotsPerLevel', 3)
            ->set('newMaxCapacity', 5)
            ->call('createNewRack');

        $this->assertDatabaseHas('mwh_racks', [
            'rack_code' => 'MTR-01',
        ]);

        $this->assertEquals(6, MwhPosition::count());
    }

    public function test_can_update_slot_settings()
    {
        $whse = MwhWarehouse::create(['whse_code' => 'MTR-TEST', 'whse_name' => 'Test Whse']);
        $rack = MwhRack::create(['whse_id' => $whse->id, 'rack_code' => 'RAK-A']);
        $pos = MwhPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'RAK-A-L01-S01',
            'slot_label' => 'Slot 1',
            'max_capacity' => 1,
        ]);

        Livewire::test(RackMapping::class)
            ->call('selectPosition', $pos->id)
            ->set('editPositionCode', 'RESIN-A1')
            ->set('editSlotLabel', 'Gudang Resin Utama')
            ->set('editMaxCapacity', 10)
            ->set('editStatus', 'PARTIAL')
            ->set('editLastItemCode', 'MAT-POLY-001')
            ->call('saveSettings');

        $pos->refresh();
        $this->assertEquals('RESIN-A1', $pos->position_code);
        $this->assertEquals('Gudang Resin Utama', $pos->slot_label);
        $this->assertEquals(10, $pos->max_capacity);
        $this->assertEquals('PARTIAL', $pos->status);
        $this->assertEquals('MAT-POLY-001', $pos->last_item_code);
    }

    public function test_can_assign_temporary_pallet_to_slot_from_mapping()
    {
        $whse = MwhWarehouse::create(['whse_code' => 'KBN', 'whse_name' => 'KBN Material Whse']);
        $rack = MwhRack::create(['whse_id' => $whse->id, 'rack_code' => 'R-01']);
        $pos = MwhPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'R-01-L01-S01',
            'slot_label' => 'Slot 1',
            'max_capacity' => 1000,
            'status' => 'EMPTY',
        ]);

        $pallet = \App\Models\MwhPallet::create([
            'whse_id' => $whse->id,
            'pallet_id' => 'PLT-TEMP-001',
            'item_code' => 'RESIN-ABS-01',
            'initial_qty' => 500,
            'current_qty' => 500,
            'status' => 'STORED',
            'position_id' => null, // temporary
        ]);

        $this->assertNull($pallet->position_id);

        Livewire::test(RackMapping::class)
            ->call('assignPalletToPosition', $pallet->id, $pos->id);

        $pallet->refresh();
        $pos->refresh();

        $this->assertEquals($pos->id, $pallet->position_id);
        $this->assertEquals('PARTIAL', $pos->status);
        $this->assertEquals('RESIN-ABS-01', $pos->last_item_code);
    }
}
