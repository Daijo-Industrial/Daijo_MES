<?php

namespace Tests\Feature;

use App\Livewire\MaterialWarehouse\MaterialOutgoingCreator;
use App\Livewire\MaterialWarehouse\MaterialRequestManager;
use App\Livewire\Production\ProductionMaterialStation;
use App\Models\MasterListMaterial;
use App\Models\MwhPallet;
use App\Models\MwhWarehouse;
use App\Models\ProductionMaterialRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\MaterialWarehouseService;
use App\Services\ProductionMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionMaterialRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MwhWarehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function ($table) {
                $table->timestamp('last_activity_at')->nullable();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function ($table) {
                $table->id();
                $table->string('name')->default('Head Office');
                $table->string('branch_code')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mwh_warehouses')) {
            Schema::create('mwh_warehouses', function ($table) {
                $table->id();
                $table->string('whse_code');
                $table->string('whse_name');
                $table->timestamps();
            });
        }

        $this->warehouse = MwhWarehouse::withoutGlobalScopes()->first() ?? MwhWarehouse::withoutGlobalScopes()->create([
            'whse_code' => 'MWH-KBN-01',
            'whse_name' => 'Gudang Material KBN',
        ]);

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'User MES Testing',
        ]);
    }

    public function test_service_returns_actual_store_stock_and_floor_stock(): void
    {
        MasterListMaterial::create([
            'item_code' => '401-ABS-RED',
            'item_description' => 'ABS RED COMPOUND',
            'purchasing_uom' => 'KG',
        ]);

        // Pallet 1 di Store: 500 KG (Active STORED)
        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-STORE-01',
            'item_code' => '401-ABS-RED',
            'initial_qty' => 500.00,
            'current_qty' => 500.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => false,
        ]);

        // Pallet 2 di Store: 250 KG (Active PARTIAL)
        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-STORE-02',
            'item_code' => '401-ABS-RED',
            'initial_qty' => 500.00,
            'current_qty' => 250.00,
            'uom' => 'KG',
            'status' => 'PARTIAL',
            'is_qc_hold' => false,
        ]);

        // Pallet 3: 100 KG tapi QC Hold -> Tidak boleh dihitung ke available
        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-STORE-03',
            'item_code' => '401-ABS-RED',
            'initial_qty' => 100.00,
            'current_qty' => 100.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => true,
        ]);

        $service = app(ProductionMaterialService::class);
        $stock = $service->getActualStoreStock('401-ABS-RED');

        $this->assertEquals('401-ABS-RED', $stock['item_code']);
        $this->assertEquals(750.00, $stock['store_available_qty']); // 500 + 250
        $this->assertEquals(2, $stock['store_pallet_count']);
        $this->assertEquals(0.00, $stock['production_floor_stock']);
    }

    public function test_production_user_can_create_and_cancel_material_request(): void
    {
        MasterListMaterial::create([
            'item_code' => '401-PP-BLACK',
            'item_description' => 'PP BLACK COMPOUND',
            'purchasing_uom' => 'KG',
        ]);

        // Seed pallet di store agar stok tersedia
        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-PP-001',
            'item_code' => '401-PP-BLACK',
            'initial_qty' => 500.00,
            'current_qty' => 500.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => false,
        ]);

        $service = app(ProductionMaterialService::class);

        $req = $service->createMaterialRequest([
            'item_code' => '401-PP-BLACK',
            'qty_requested' => 300.00,
            'machine_no' => 'M-09',
            'spk_code' => 'SPK-7788',
            'urgency' => 'URGENT',
            'notes' => 'Tolong dipercepat untuk mesin 9',
        ], $this->user->id, $this->user->name);

        $this->assertEquals('PENDING', $req->status);
        $this->assertEquals(300.00, $req->qty_requested);
        $this->assertEquals('URGENT', $req->urgency);
        $this->assertStringStartsWith('REQ-', $req->request_code);

        // Batal request
        $cancelled = $service->cancelMaterialRequest($req->id, $this->user->id);
        $this->assertEquals('CANCELLED', $cancelled->status);
    }

    public function test_store_user_can_reject_request_with_reason(): void
    {
        MasterListMaterial::create([
            'item_code' => '401-POM-NATURAL',
            'item_description' => 'POM NATURAL',
            'purchasing_uom' => 'KG',
        ]);

        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-POM-001',
            'item_code' => '401-POM-NATURAL',
            'initial_qty' => 500.00,
            'current_qty' => 500.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => false,
        ]);

        $service = app(ProductionMaterialService::class);

        $req = $service->createMaterialRequest([
            'item_code' => '401-POM-NATURAL',
            'qty_requested' => 150.00,
            'machine_no' => 'M-02',
        ], $this->user->id, 'Operator Produksi');

        // Reject via Livewire MaterialRequestManager
        Livewire::actingAs($this->user)
            ->test(MaterialRequestManager::class)
            ->call('openRejectModal', $req->id)
            ->set('rejectReason', 'Stok di rak sedang diaudit fisik oleh QC')
            ->call('confirmReject')
            ->assertDispatched('push-notification');

        $req->refresh();
        $this->assertEquals('REJECTED', $req->status);
        $this->assertEquals('Stok di rak sedang diaudit fisik oleh QC', $req->reject_reason);
        $this->assertNotNull($req->rejected_at);
        $this->assertEquals($this->user->id, $req->rejected_by);
    }

    public function test_store_user_can_fulfill_request_via_outgoing_picking(): void
    {
        MasterListMaterial::create([
            'item_code' => '401-PC-CLEAR',
            'item_description' => 'PC CLEAR RESIN',
            'purchasing_uom' => 'KG',
        ]);

        $pallet = MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-PC-001',
            'item_code' => '401-PC-CLEAR',
            'initial_qty' => 500.00,
            'current_qty' => 500.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => false,
        ]);

        $service = app(ProductionMaterialService::class);

        // Produksi buat request 200 KG
        $req = $service->createMaterialRequest([
            'item_code' => '401-PC-CLEAR',
            'qty_requested' => 200.00,
            'machine_no' => 'M-12',
            'spk_code' => 'SPK-9900',
        ], $this->user->id, 'Operator Produksi');

        // Store buka OutgoingCreator dengan parameter request_id
        Livewire::actingAs($this->user)
            ->withQueryParams(['request_id' => $req->id])
            ->test(MaterialOutgoingCreator::class)
            ->assertSet('selected_item_code', '401-PC-CLEAR')
            ->assertSet('qty_taken', '200.00')
            ->set('selected_pallet_id', 'MPLT-PC-001')
            ->call('processPicking');

        $req->refresh();
        $this->assertEquals('PROCESSED', $req->status);
        $this->assertNotNull($req->outgoing_id);
        $this->assertNotNull($req->outgoing_code);
        $this->assertNotNull($req->processed_at);

        // Pallet di Store terpotong: 500 - 200 = 300 KG
        $pallet->refresh();
        $this->assertEquals(300.00, $pallet->current_qty);
    }

    public function test_production_material_station_livewire_request_tab(): void
    {
        MasterListMaterial::create([
            'item_code' => '401-HIPS-WHITE',
            'item_description' => 'HIPS WHITE COMPOUND',
            'purchasing_uom' => 'KG',
        ]);

        // 1. Uji jika stok di store KOSONG (0 KG) -> GAGAL / tidak boleh tembus
        Livewire::actingAs($this->user)
            ->test(ProductionMaterialStation::class)
            ->call('switchTab', 'request')
            ->assertSet('activeTab', 'request')
            ->call('selectMaterialForRequest', '401-HIPS-WHITE')
            ->set('reqQty', 75.00)
            ->call('saveMaterialRequest')
            ->assertHasErrors(['reqItemCode']);

        $this->assertDatabaseMissing('production_material_requests', [
            'item_code' => '401-HIPS-WHITE',
        ]);

        // 2. Sekarang buat pallet di store (stok 200 KG)
        MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'MPLT-HIPS-001',
            'item_code' => '401-HIPS-WHITE',
            'initial_qty' => 200.00,
            'current_qty' => 200.00,
            'uom' => 'KG',
            'status' => 'STORED',
            'is_qc_hold' => false,
        ]);

        // 3. Uji jika kuantitas melebihi stok store (minta 300 KG padahal stok 200 KG) -> GAGAL
        Livewire::actingAs($this->user)
            ->test(ProductionMaterialStation::class)
            ->call('switchTab', 'request')
            ->call('selectMaterialForRequest', '401-HIPS-WHITE')
            ->set('reqQty', 300.00)
            ->call('saveMaterialRequest')
            ->assertHasErrors(['reqQty']);

        // 4. Uji jika mesin kosong (OPSIONAL) dan qty valid (75 KG) -> BERHASIL
        Livewire::actingAs($this->user)
            ->test(ProductionMaterialStation::class)
            ->call('switchTab', 'request')
            ->call('selectMaterialForRequest', '401-HIPS-WHITE')
            ->set('reqQty', 75.00)
            ->set('reqMachineNo', '') // Mesin dikosongkan (opsional)
            ->set('reqUrgency', 'NORMAL')
            ->call('saveMaterialRequest')
            ->assertHasNoErrors()
            ->assertDispatched('push-notification');

        $this->assertDatabaseHas('production_material_requests', [
            'item_code' => '401-HIPS-WHITE',
            'qty_requested' => 75.00,
            'machine_no' => null,
            'status' => 'PENDING',
        ]);
    }
}
