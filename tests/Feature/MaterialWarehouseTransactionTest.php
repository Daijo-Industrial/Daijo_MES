<?php

namespace Tests\Feature;

use App\Livewire\MaterialWarehouse\MaterialIncomingCreator;
use App\Livewire\MaterialWarehouse\MaterialOutgoingCreator;
use App\Livewire\MaterialWarehouse\MaterialPalletIndex;
use App\Livewire\MaterialWarehouse\MaterialQrLookup;
use App\Livewire\MaterialWarehouse\MaterialStockCard;
use App\Models\MasterListMaterial;
use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Models\MwhRack;
use App\Models\MwhWarehouse;
use App\Models\User;
use App\Services\MaterialWarehouseService;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialWarehouseTransactionTest extends TestCase
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

        if (!Schema::hasTable('mwh_warehouses')) {
            Schema::create('mwh_warehouses', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('whse_code')->unique();
                $table->string('whse_name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mwh_racks')) {
            Schema::create('mwh_racks', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('whse_id');
                $table->string('rack_code');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mwh_positions')) {
            Schema::create('mwh_positions', function (\Illuminate\Database\Schema\Blueprint $table) {
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
        }

        if (!Schema::hasTable('master_list_materials')) {
            Schema::create('master_list_materials', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('item_code', 100)->unique();
                $table->text('item_description')->nullable();
                $table->string('preferred_supplier', 100)->nullable();
                $table->string('purchasing_uom', 50)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mwh_incoming_headers')) {
            Schema::create('mwh_incoming_headers', function (\Illuminate\Database\Schema\Blueprint $table) {
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
        }

        if (!Schema::hasTable('mwh_pallets')) {
            Schema::create('mwh_pallets', function (\Illuminate\Database\Schema\Blueprint $table) {
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
                $table->foreignId('initial_position_id')->nullable();
                $table->enum('status', ['STORED', 'PARTIAL', 'EMPTY'])->default('STORED');
                $table->boolean('is_qc_hold')->default(false);
                $table->text('qc_hold_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('mwh_outgoings')) {
            Schema::create('mwh_outgoings', function (\Illuminate\Database\Schema\Blueprint $table) {
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

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->foreignId('role_id')->nullable();
                $table->string('password')->default('password');
                $table->timestamps();
            });
        }
    }

    public function test_incoming_splits_qty_over_1000kg_into_multiple_pallets()
    {
        $wh = MwhWarehouse::firstOrCreate(['whse_code' => 'MTR-01'], ['whse_name' => 'Gudang Material']);
        $rack = MwhRack::firstOrCreate(['whse_id' => $wh->id, 'rack_code' => 'RAK-A1']);
        $pos = MwhPosition::firstOrCreate([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-A1-L01-S01',
        ], ['max_capacity' => 2000]);

        $mat = MasterListMaterial::firstOrCreate([
            'item_code' => '180-CN890-FL',
        ], [
            'item_description' => 'SPK CORD 22AWG 3M TR TUBE W-B',
            'purchasing_uom'   => 'PCS',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(MaterialIncomingCreator::class)
            ->set('supplier_name', 'PT Material Supplier')
            ->set('po_number', 'PO-998811')
            ->set('arrival_date', '2026-07-22')
            ->set('items.0.item_code', '180-CN890-FL')
            ->set('items.0.qty', '2500') // 2500 KG -> should split into 1000, 1000, 500
            ->set('items.0.position_id', $pos->id)
            ->call('saveIncoming')
            ->assertHasNoErrors();

        // 3 pallets should be created
        $this->assertEquals(3, MwhPallet::where('item_code', '180-CN890-FL')->count());

        $pallets = MwhPallet::where('item_code', '180-CN890-FL')->orderBy('initial_qty', 'desc')->get();
        $this->assertEquals(1000.00, $pallets[0]->initial_qty);
        $this->assertEquals(1000.00, $pallets[1]->initial_qty);
        $this->assertEquals(500.00, $pallets[2]->initial_qty);
    }

    public function test_partial_outgoing_picking_reduces_pallet_qty_and_updates_status()
    {
        $mwhService = app(MaterialWarehouseService::class);

        $wh = MwhWarehouse::firstOrCreate(['whse_code' => 'MTR-01'], ['whse_name' => 'Gudang Material']);
        $rack = MwhRack::firstOrCreate(['whse_id' => $wh->id, 'rack_code' => 'RAK-A1']);
        $pos = MwhPosition::firstOrCreate([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 2,
            'position_code' => 'RAK-A1-L01-S02',
        ], ['max_capacity' => 1000]);

        $pallet = MwhPallet::create([
            'pallet_id'   => 'MPLT-TEST-001',
            'item_code'   => '180-CN890-FL',
            'initial_qty' => 800.00,
            'current_qty' => 800.00,
            'uom'         => 'KG',
            'position_id' => $pos->id,
            'status'      => 'STORED',
        ]);

        $mwhService->updatePositionStatus($pos->id);

        // Pick 300 KG (Partial picking)
        $mwhService->processOutgoingPicking('MPLT-TEST-001', 300.00, '2026-07-22', 'Mesin 01', 'Pemakaian Moulding');

        $pallet->refresh();
        $this->assertEquals(500.00, $pallet->current_qty);
        $this->assertEquals('PARTIAL', $pallet->status);

        $this->assertDatabaseHas('mwh_outgoings', [
            'pallet_id' => 'MPLT-TEST-001',
            'qty_taken' => 300.00,
            'issued_to' => 'Mesin 01',
        ]);
    }

    public function test_can_render_material_warehouse_components()
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(MaterialIncomingCreator::class)->assertStatus(200);
        Livewire::test(MaterialPalletIndex::class)->assertStatus(200);
        Livewire::test(MaterialOutgoingCreator::class)->assertStatus(200);
        Livewire::test(MaterialQrLookup::class)->assertStatus(200);
        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)->assertStatus(200);
    }

    public function test_rack_mapping_search_highlights_matching_slot_and_shows_detail()
    {
        $wh = MwhWarehouse::firstOrCreate(['whse_code' => 'MTR-01'], ['whse_name' => 'Gudang Material']);
        $rack = MwhRack::firstOrCreate(['whse_id' => $wh->id, 'rack_code' => 'RAK-B1']);
        $pos = MwhPosition::firstOrCreate([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-B1-L01-S01',
        ], ['max_capacity' => 1000]);

        $pallet = MwhPallet::create([
            'pallet_id'   => 'MPLT-SEARCH-999',
            'item_code'   => 'SEARCH-ITEM-01',
            'initial_qty' => 750.00,
            'current_qty' => 750.00,
            'position_id' => $pos->id,
            'status'      => 'STORED',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->set('searchTerm', 'SEARCH-ITEM-01')
            ->assertViewHas('matchingPositionIds', function ($ids) use ($pos) {
                return in_array($pos->id, $ids);
            })
            ->call('selectPosition', $pos->id)
            ->assertSet('selectedPositionId', $pos->id)
            ->assertSee('MPLT-SEARCH-999')
            ->assertSee('SEARCH-ITEM-01');
    }

    public function test_rack_mapping_can_store_material_directly_into_selected_slot()
    {
        $wh = MwhWarehouse::firstOrCreate(['whse_code' => 'MTR-01'], ['whse_name' => 'Gudang Material']);
        $rack = MwhRack::firstOrCreate(['whse_id' => $wh->id, 'rack_code' => 'RAK-C1']);
        $pos = MwhPosition::firstOrCreate([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-C1-L01-S01',
        ], ['max_capacity' => 1000]);

        MasterListMaterial::firstOrCreate([
            'item_code' => 'MAT-DIRECT-01',
        ], [
            'item_description' => 'Direct Test Material',
            'purchasing_uom'   => 'KG',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->call('selectPosition', $pos->id)
            ->set('new_item_code', 'MAT-DIRECT-01')
            ->set('new_qty', '650')
            ->set('new_created_at', '2026-06-15')
            ->set('new_lot_no', 'LOT-DIRECT-99')
            ->call('storeMaterialToSlot')
            ->assertHasNoErrors();

        $pallet = MwhPallet::where('position_id', $pos->id)->where('item_code', 'MAT-DIRECT-01')->first();
        $this->assertNotNull($pallet);
        $this->assertEquals(650.00, $pallet->current_qty);
        $this->assertEquals('LOT-DIRECT-99', $pallet->lot_no);
        $this->assertEquals('2026-06-15', $pallet->created_at->format('Y-m-d'));

        $pos->refresh();
        $this->assertEquals('PARTIAL', $pos->status);

        // Create outgoing transaction for this test pallet
        app(MaterialWarehouseService::class)->processOutgoingPicking($pallet->pallet_id, 100.00, '2026-06-16', 'Mesin 02', 'Test Outgoing');

        $this->assertDatabaseHas('mwh_outgoings', ['pallet_id' => $pallet->pallet_id, 'deleted_at' => null]);

        // Delete pallet from slot
        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->call('selectPosition', $pos->id)
            ->call('deletePalletFromSlot', $pallet->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('mwh_pallets', ['id' => $pallet->id]);
        $this->assertSoftDeleted('mwh_outgoings', ['pallet_id' => $pallet->pallet_id]);
    }

    public function test_public_pallet_lookup_accessible_without_authentication()
    {
        $pallet = MwhPallet::create([
            'pallet_id'   => 'MPLT-PUBLIC-777',
            'item_code'   => 'PUBLIC-ITEM-01',
            'initial_qty' => 500.00,
            'current_qty' => 500.00,
            'status'      => 'STORED',
        ]);

        // Unsigned request should be rejected with 403 Forbidden
        $this->get('/public/material-pallet/MPLT-PUBLIC-777')->assertStatus(403);

        // Signed URL request should succeed without login
        $signedUrl = \Illuminate\Support\Facades\URL::signedRoute('mwh.public-pallet-lookup', ['palletId' => 'MPLT-PUBLIC-777']);
        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('MPLT-PUBLIC-777');
        $response->assertSee('PUBLIC-ITEM-01');
        $response->assertSee('500.00');
    }

    public function test_generating_code_after_soft_delete_prevents_duplicate_entry()
    {
        $mwhService = app(MaterialWarehouseService::class);

        $p1Code = $mwhService->generatePalletId();
        $p1 = MwhPallet::create([
            'pallet_id'   => $p1Code,
            'item_code'   => 'TEST-ITEM-DUP',
            'initial_qty' => 100,
            'current_qty' => 100,
            'status'      => 'STORED',
        ]);

        // Soft delete p1
        $p1->delete();
        $this->assertSoftDeleted('mwh_pallets', ['id' => $p1->id]);

        // Generate next pallet ID
        $p2Code = $mwhService->generatePalletId();
        $this->assertNotEquals($p1Code, $p2Code);

        // Creating p2 must succeed without Duplicate Entry exception
        $p2 = MwhPallet::create([
            'pallet_id'   => $p2Code,
            'item_code'   => 'TEST-ITEM-DUP',
            'initial_qty' => 200,
            'current_qty' => 200,
            'status'      => 'STORED',
        ]);

        $this->assertDatabaseHas('mwh_pallets', ['pallet_id' => $p2Code, 'deleted_at' => null]);
    }

    public function test_public_material_warehouse_mapping_accessible_without_authentication()
    {
        $response = $this->get('/public/material-warehouse/mapping');
        $response->assertStatus(200);
        $response->assertSee('Public View-Only');
        $response->assertSee('MATERIAL WAREHOUSE RACK MAPPING');
    }

    public function test_qc_hold_prevents_outgoing_picking_and_can_be_toggled()
    {
        $mwhService = app(MaterialWarehouseService::class);

        $pallet = MwhPallet::create([
            'pallet_id'      => 'MPLT-QCHOLD-001',
            'item_code'      => 'MAT-QCHOLD-99',
            'initial_qty'    => 500.00,
            'current_qty'    => 500.00,
            'status'         => 'STORED',
            'is_qc_hold'     => true,
            'qc_hold_reason' => 'Warna tidak sesuai spesifikasi QC',
        ]);

        // Attempting outgoing picking on QC HOLD pallet must throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pallet MPLT-QCHOLD-001 sedang di-HOLD oleh QC');

        $mwhService->processOutgoingPicking('MPLT-QCHOLD-001', 100.00, '2026-07-28');
    }

    public function test_material_stock_card_renders_and_calculates_movements_correctly()
    {
        $mat = MasterListMaterial::firstOrCreate([
            'item_code' => 'MAT-STOCKCARD-01',
        ], [
            'item_description' => 'RESIN ABS NATURAL HI-IMPACT',
            'purchasing_uom'   => 'KG',
        ]);

        $pallet = MwhPallet::create([
            'pallet_id'   => 'MPLT-SC-001',
            'item_code'   => 'MAT-STOCKCARD-01',
            'initial_qty' => 1000.00,
            'current_qty' => 750.00,
            'uom'         => 'KG',
            'status'      => 'PARTIAL',
        ]);

        \App\Models\MwhOutgoing::create([
            'outgoing_code' => 'OUT-SC-001',
            'pallet_id'     => 'MPLT-SC-001',
            'item_code'     => 'MAT-STOCKCARD-01',
            'qty_taken'     => 250.00,
            'uom'           => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to'     => 'PRODUKSI MOLDING 01',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'StockCard User',
            'email'    => 'stockcard_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\MaterialWarehouse\MaterialStockCard::class)
            ->set('selectedItemCode', 'MAT-STOCKCARD-01')
            ->assertSee('MAT-STOCKCARD-01')
            ->assertSee('RESIN ABS NATURAL HI-IMPACT')
            ->assertSee('MPLT-SC-001')
            ->assertSee('OUT-SC-001');
    }

    public function test_branch_separation_between_kbn_and_karawang()
    {
        $kbn = MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
        $krw = MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);

        $rackKbn = MwhRack::create(['whse_id' => $kbn->id, 'rack_code' => 'RAK-KBN-01']);
        $posKbn = MwhPosition::create([
            'rack_id'       => $rackKbn->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-KBN-01-L01-S01',
            'max_capacity'  => 1000,
        ]);

        $rackKrw = MwhRack::create(['whse_id' => $krw->id, 'rack_code' => 'RAK-KRW-01']);
        $posKrw = MwhPosition::create([
            'rack_id'       => $rackKrw->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-KRW-01-L01-S01',
            'max_capacity'  => 1000,
        ]);

        $mat = MasterListMaterial::firstOrCreate([
            'item_code' => 'MAT-BRANCH-TEST',
        ], [
            'item_description' => 'TEST RESIN FOR BRANCH SEPARATION',
            'purchasing_uom'   => 'KG',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Branch User',
            'email'    => 'branch_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        // 1. Store incoming in Karawang
        Livewire::test(MaterialIncomingCreator::class)
            ->set('whse_id', $krw->id)
            ->set('arrival_date', '2026-09-01')
            ->set('items.0.item_code', 'MAT-BRANCH-TEST')
            ->set('items.0.qty', '500')
            ->set('items.0.position_id', $posKrw->id)
            ->call('saveIncoming')
            ->assertHasNoErrors();

        $krwPallet = MwhPallet::where('item_code', 'MAT-BRANCH-TEST')->where('whse_id', $krw->id)->first();
        $this->assertNotNull($krwPallet);
        $this->assertEquals(500.00, $krwPallet->current_qty);

        // 2. FIFO recommendation in Karawang must return this pallet, while KBN must NOT return it
        $mwhService = app(MaterialWarehouseService::class);
        $krwFifo = $mwhService->getFifoRecommendations('MAT-BRANCH-TEST', $krw->id);
        $kbnFifo = $mwhService->getFifoRecommendations('MAT-BRANCH-TEST', $kbn->id);

        $this->assertCount(1, $krwFifo);
        $this->assertCount(0, $kbnFifo);

        // 3. Rack mapping with Karawang filter sees Karawang rack, KBN sees KBN rack
        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->set('selectedWhseId', $krw->id)
            ->assertSee('RAK-KRW-01')
            ->assertDontSee('RAK-KBN-01');

        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->set('selectedWhseId', $kbn->id)
            ->assertSee('RAK-KBN-01')
            ->assertDontSee('RAK-KRW-01');
    }

    public function test_direct_input_and_consumed_pallets_maintain_slot_visibility_in_stock_card()
    {
        $mwhService = app(MaterialWarehouseService::class);

        $wh = MwhWarehouse::firstOrCreate(['whse_code' => 'MTR-01'], ['whse_name' => 'Gudang Material']);
        $rack = MwhRack::firstOrCreate(['whse_id' => $wh->id, 'rack_code' => 'RAK-DIR']);
        $pos = MwhPosition::firstOrCreate([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-DIR-L01-S01',
        ], ['max_capacity' => 1000]);

        MasterListMaterial::firstOrCreate([
            'item_code' => '400-TBP7JA127BLU',
        ], [
            'item_description' => 'ABS CLEAR BLUE 244 TBP7JA127',
            'purchasing_uom'   => 'KG',
        ]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Store User',
            'email'    => 'store_' . uniqid() . '@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        // 1. Direct input via Rack Mapping into slot RAK-DIR-L01-S01
        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->call('selectPosition', $pos->id)
            ->set('new_item_code', '400-TBP7JA127BLU')
            ->set('new_qty', '1000')
            ->set('new_lot_no', 'LOT-TOYOINK-01')
            ->set('new_supplier_name', 'PT. TOYOINK')
            ->set('new_created_at', '2026-08-19')
            ->call('storeMaterialToSlot')
            ->assertHasNoErrors();

        $pallet = MwhPallet::where('item_code', '400-TBP7JA127BLU')->first();
        $this->assertNotNull($pallet);
        $this->assertEquals($pos->id, $pallet->position_id);
        $this->assertEquals($pos->id, $pallet->initial_position_id);

        // 2. Perform Outgoing picking taking ALL 1000 KG (Pallet becomes EMPTY)
        $mwhService->processOutgoingPicking($pallet->pallet_id, 1000.00, '2026-08-19', 'Produksi Line 1', 'Direct usage');

        $pallet->refresh();
        $this->assertEquals(0, $pallet->current_qty);
        $this->assertNull($pallet->position_id); // position_id is null to free physical slot
        $this->assertEquals($pos->id, $pallet->initial_position_id); // initial_position_id remains intact!

        // 3. Test Material Stock Card shows RAK-DIR-L01-S01 for both INCOMING and OUTGOING without showing UNASSIGNED
        Livewire::test(MaterialStockCard::class)
            ->set('selectedItemCode', '400-TBP7JA127BLU')
            ->set('fromDate', '2026-08-01')
            ->set('toDate', '2026-08-31')
            ->assertSee('RAK-DIR-L01-S01')
            ->assertDontSee('UNASSIGNED');

        // 4. Test legacy pallet without initial_position_id fallback to outgoings history
        $legacyPallet = MwhPallet::create([
            'whse_id'             => $wh->id,
            'pallet_id'           => 'MPLT-LEGACY-001',
            'item_code'           => '400-TBP7JA127BLU',
            'initial_qty'         => 500,
            'current_qty'         => 0,
            'position_id'         => null,
            'initial_position_id' => null,
            'status'              => 'EMPTY',
            'created_at'          => '2026-08-20 08:00:00',
        ]);

        \App\Models\MwhOutgoing::create([
            'whse_id'       => $wh->id,
            'outgoing_code' => 'OUT-LEGACY-001',
            'pallet_id'     => 'MPLT-LEGACY-001',
            'position_id'   => $pos->id,
            'item_code'     => '400-TBP7JA127BLU',
            'qty_taken'     => 500,
            'outgoing_date' => '2026-08-20',
        ]);

        Livewire::test(MaterialStockCard::class)
            ->set('selectedItemCode', '400-TBP7JA127BLU')
            ->set('fromDate', '2026-08-01')
            ->set('toDate', '2026-08-31')
            ->assertSee('MPLT-LEGACY-001')
            ->assertSee('RAK-DIR-L01-S01')
            ->assertDontSee('UNASSIGNED');
    }

    public function test_incoming_material_can_be_placed_in_temporary_without_slot_limits(): void
    {
        $kbnWh = MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
        $krwWh = MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);

        MasterListMaterial::firstOrCreate(
            ['item_code' => 'RAW-TMP-PP'],
            ['item_description' => 'Polypropylene Temporary Resin Test', 'purchasing_uom' => 'KG']
        );

        // 1. Incoming ke Temporary di KBN (2500 KG -> dipecah 3 pallet: 1000, 1000, 500 KG)
        Livewire::test(MaterialIncomingCreator::class)
            ->set('whse_id', $kbnWh->id)
            ->set('incoming_type', 'SUPPLIER')
            ->set('supplier_name', 'PT. Supplier KBN')
            ->set('arrival_date', '2026-10-15')
            ->set('items', [
                [
                    'item_code'        => 'RAW-TMP-PP',
                    'item_description' => 'Polypropylene Temporary Resin Test',
                    'lot_no'           => 'LOT-KBN-01',
                    'qty'              => '2500',
                    'position_id'      => 'TEMPORARY',
                    'searchResults'    => [],
                ]
            ])
            ->call('saveIncoming')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $kbnPallets = MwhPallet::where('whse_id', $kbnWh->id)->where('item_code', 'RAW-TMP-PP')->get();
        $this->assertCount(3, $kbnPallets);
        $this->assertEquals(2500.0, $kbnPallets->sum('current_qty'));
        foreach ($kbnPallets as $p) {
            $this->assertNull($p->position_id);
            $this->assertNull($p->initial_position_id);
            $this->assertEquals('STORED', $p->status);
            $this->assertTrue($p->isTemporary());
        }

        // 2. Incoming ke Temporary di Karawang (1500 KG -> dipecah 2 pallet: 1000, 500 KG)
        Livewire::test(MaterialIncomingCreator::class)
            ->set('whse_id', $krwWh->id)
            ->set('incoming_type', 'SUPPLIER')
            ->set('supplier_name', 'PT. Supplier Karawang')
            ->set('arrival_date', '2026-10-15')
            ->set('items', [
                [
                    'item_code'        => 'RAW-TMP-PP',
                    'item_description' => 'Polypropylene Temporary Resin Test',
                    'lot_no'           => 'LOT-KRW-01',
                    'qty'              => '1500',
                    'position_id'      => 'TEMPORARY',
                    'searchResults'    => [],
                ]
            ])
            ->call('saveIncoming')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $krwPallets = MwhPallet::where('whse_id', $krwWh->id)->where('item_code', 'RAW-TMP-PP')->get();
        $this->assertCount(2, $krwPallets);
        $this->assertEquals(1500.0, $krwPallets->sum('current_qty'));
        foreach ($krwPallets as $p) {
            $this->assertNull($p->position_id);
            $this->assertNull($p->initial_position_id);
            $this->assertEquals('STORED', $p->status);
            $this->assertTrue($p->isTemporary());
        }
    }

    public function test_temporary_pallet_can_be_filtered_and_relocated_to_slot(): void
    {
        $kbnWh = MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);

        $rack = MwhRack::create(['whse_id' => $kbnWh->id, 'rack_code' => 'RAK-TMP-A']);
        $slot = MwhPosition::create([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-TMP-A-L01-S01',
            'status'        => 'EMPTY',
            'max_capacity'  => 1000,
        ]);

        $pallet = MwhPallet::create([
            'whse_id'             => $kbnWh->id,
            'pallet_id'           => 'MPLT-TMP-RELOC-1',
            'item_code'           => 'RAW-TMP-PP',
            'initial_qty'         => 800,
            'current_qty'         => 800,
            'position_id'         => null,
            'initial_position_id' => null,
            'status'              => 'STORED',
        ]);

        $this->assertTrue($pallet->isTemporary());

        // Test Filter TEMPORARY in MaterialPalletIndex
        Livewire::test(MaterialPalletIndex::class)
            ->set('statusFilter', 'TEMPORARY')
            ->assertSee('MPLT-TMP-RELOC-1')
            ->assertSee('TEMPORARY')
            ->assertSee('Masuk Slot')
            ->call('openRelocateModal', $pallet->id)
            ->assertSet('isFromTemporary', true)
            ->set('newPositionId', $slot->id)
            ->call('saveRelocation')
            ->assertHasNoErrors();

        $pallet->refresh();
        $slot->refresh();

        $this->assertEquals($slot->id, $pallet->position_id);
        $this->assertEquals($slot->id, $pallet->initial_position_id);
        $this->assertFalse($pallet->isTemporary());
        $this->assertEquals('PARTIAL', $slot->status);
        $this->assertEquals('RAW-TMP-PP', $slot->last_item_code);
    }

    public function test_rack_mapping_can_view_temporary_pallets_and_assign_to_slot(): void
    {
        $krwWh = MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);

        $rack = MwhRack::create(['whse_id' => $krwWh->id, 'rack_code' => 'RAK-MAP-KRW']);
        $slot = MwhPosition::create([
            'rack_id'       => $rack->id,
            'level_no'      => 1,
            'slot_no'       => 1,
            'position_code' => 'RAK-MAP-KRW-L01-S01',
            'status'        => 'EMPTY',
            'max_capacity'  => 1000,
        ]);

        $tempPallet = MwhPallet::create([
            'whse_id'             => $krwWh->id,
            'pallet_id'           => 'MPLT-MAP-TEMP-99',
            'item_code'           => 'RAW-TMP-PP',
            'initial_qty'         => 600,
            'current_qty'         => 600,
            'position_id'         => null,
            'initial_position_id' => null,
            'status'              => 'STORED',
        ]);

        Livewire::test(\App\Livewire\MaterialWarehouse\RackMapping::class)
            ->set('selectedWhseId', $krwWh->id)
            ->assertSee('MATERIAL DI AREA TEMPORARY (BELUM ADA SLOT)')
            ->call('selectPosition', $slot->id)
            ->assertSee('Masukkan dari Area Temporary')
            ->assertSee('MPLT-MAP-TEMP-99')
            ->call('assignTemporaryPalletToSlot', $tempPallet->id)
            ->assertHasNoErrors();

        $tempPallet->refresh();
        $slot->refresh();

        $this->assertEquals($slot->id, $tempPallet->position_id);
        $this->assertEquals('PARTIAL', $slot->status);
    }
}


