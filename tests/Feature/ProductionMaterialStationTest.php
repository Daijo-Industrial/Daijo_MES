<?php

namespace Tests\Feature;

use App\Livewire\Production\ProductionMaterialStation;
use App\Models\MasterListMaterial;
use App\Models\MasterMachine;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use App\Models\ProductionMaterialUsage;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductionMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionMaterialStationTest extends TestCase
{
    use RefreshDatabase;

    protected User $productionUser;
    protected \App\Models\MwhWarehouse $warehouse;

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

        $this->warehouse = \App\Models\MwhWarehouse::withoutGlobalScopes()->first() ?? \App\Models\MwhWarehouse::withoutGlobalScopes()->create([
            'whse_code' => 'MWH-TEST-01',
            'whse_name' => 'Gudang Material Utama',
        ]);

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->productionUser = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Operator Material Produksi',
        ]);
    }

    public function test_production_user_can_access_material_station_page(): void
    {
        $this->actingAs($this->productionUser)
            ->get(route('production.material-station'))
            ->assertOk()
            ->assertSee('Quick Scanner Penerimaan Material')
            ->assertSee('Verifikasi & Tracking Material Produksi', false);
    }

    public function test_service_can_verify_incoming_store_request(): void
    {
        $material = MasterListMaterial::create([
            'item_code' => '401-PS-S76H',
            'item_description' => 'HIPS STYROLUTION S76H',
            'purchasing_uom' => 'KG',
        ]);

        $outgoing = MwhOutgoing::create([
            'whse_id' => $this->warehouse->id,
            'outgoing_code' => 'OUT-20261106-0032',
            'pallet_id' => 'MPLT-20261104-0014',
            'item_code' => '401-PS-S76H',
            'qty_taken' => 25.00,
            'uom' => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to' => 'Produksi',
            'remarks' => 'Pengambilan Material',
            'received_status' => 'PENDING',
        ]);

        $service = app(ProductionMaterialService::class);

        // Verifikasi receipt
        $verified = $service->verifyReceipt(
            $outgoing->id,
            $this->productionUser->id,
            $this->productionUser->name,
            'Diterima kondisi baik'
        );

        $this->assertEquals('RECEIVED', $verified->received_status);
        $this->assertEquals($this->productionUser->id, $verified->received_by);
        $this->assertEquals('Diterima kondisi baik', $verified->received_notes);
        $this->assertNotNull($verified->received_at);
    }

    public function test_quick_scan_barcode_verifies_receipt(): void
    {
        $outgoing = MwhOutgoing::create([
            'whse_id' => $this->warehouse->id,
            'outgoing_code' => 'OUT-20261106-0031',
            'pallet_id' => 'MPLT-20261102-0021',
            'item_code' => '401-POM-M90LV',
            'qty_taken' => 50.00,
            'uom' => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to' => 'Produksi',
            'received_status' => 'PENDING',
        ]);

        $service = app(ProductionMaterialService::class);
        $verified = $service->verifyReceiptByBarcode(
            'OUT-20261106-0031',
            $this->productionUser->id,
            $this->productionUser->name
        );

        $this->assertEquals('RECEIVED', $verified->received_status);
    }

    public function test_cannot_log_usage_if_outgoing_not_verified(): void
    {
        $outgoing = MwhOutgoing::create([
            'whse_id' => $this->warehouse->id,
            'outgoing_code' => 'OUT-UNVERIFIED',
            'pallet_id' => 'MPLT-999',
            'item_code' => '401-TEST',
            'qty_taken' => 100.00,
            'uom' => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to' => 'Produksi',
            'received_status' => 'PENDING',
        ]);

        $service = app(ProductionMaterialService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('belum diverifikasi');

        $service->logMaterialUsage([
            'outgoing_id' => $outgoing->id,
            'machine_no' => 'M-05',
            'qty_used' => 20.00,
            'purpose' => 'PRODUKSI',
        ], $this->productionUser->id, $this->productionUser->name);
    }

    public function test_logging_usage_reduces_floor_stock_and_prevents_overuse(): void
    {
        $outgoing = MwhOutgoing::create([
            'whse_id' => $this->warehouse->id,
            'outgoing_code' => 'OUT-OK',
            'pallet_id' => 'MPLT-100',
            'item_code' => '401-PS-S76H',
            'qty_taken' => 50.00,
            'uom' => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to' => 'Produksi',
            'received_status' => 'RECEIVED',
            'received_at' => now(),
            'received_by' => $this->productionUser->id,
        ]);

        $service = app(ProductionMaterialService::class);

        // 1. Pakai 20 KG
        $usage1 = $service->logMaterialUsage([
            'outgoing_id' => $outgoing->id,
            'machine_no' => 'M-08',
            'spk_code' => '26034536',
            'qty_used' => 20.00,
            'purpose' => 'PRODUKSI',
        ], $this->productionUser->id, $this->productionUser->name);

        $this->assertDatabaseHas('production_material_usages', [
            'outgoing_id' => $outgoing->id,
            'machine_no' => 'M-08',
            'qty_used' => 20.00,
        ]);

        // Cek summary stok lantai produksi: Sisa harus 30 KG
        $summary = $service->getFloorStockSummary();
        $psSummary = collect($summary)->firstWhere('item_code', '401-PS-S76H');
        $this->assertEquals(50.00, $psSummary['total_received']);
        $this->assertEquals(20.00, $psSummary['total_used']);
        $this->assertEquals(30.00, $psSummary['current_stock']);

        // 2. Coba pakai 35 KG (melebihi sisa 30 KG) -> Harus Exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('melebihi sisa stok');

        $service->logMaterialUsage([
            'outgoing_id' => $outgoing->id,
            'machine_no' => 'M-08',
            'qty_used' => 35.00,
            'purpose' => 'PRODUKSI',
        ], $this->productionUser->id, $this->productionUser->name);
    }

    public function test_livewire_component_actions(): void
    {
        $outgoing = MwhOutgoing::create([
            'whse_id' => $this->warehouse->id,
            'outgoing_code' => 'OUT-LIVEWIRE',
            'pallet_id' => 'MPLT-LIVEWIRE',
            'item_code' => '401-POM-M90LV',
            'qty_taken' => 100.00,
            'uom' => 'KG',
            'outgoing_date' => now()->toDateString(),
            'issued_to' => 'Produksi',
            'received_status' => 'PENDING',
        ]);

        MasterMachine::create([
            'name' => 'M-01',
            'plant' => 'KBN',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->productionUser)
            ->test(ProductionMaterialStation::class)
            // 1. Verifikasi modal
            ->call('openVerifyModal', $outgoing->id)
            ->set('verificationNotes', 'OK diterima utuh')
            ->call('confirmVerification')
            ->assertDispatched('push-notification')
            // Cek status berubah ke RECEIVED
            ->assertSet('showVerifyModal', false);

        $this->assertDatabaseHas('mwh_outgoings', [
            'id' => $outgoing->id,
            'received_status' => 'RECEIVED',
            'received_notes' => 'OK diterima utuh',
        ]);

        // 2. Simpan Pemakaian Material ke Mesin M-01
        Livewire::actingAs($this->productionUser)
            ->test(ProductionMaterialStation::class)
            ->call('selectBatchForUsage', $outgoing->id)
            ->set('machineNo', 'M-01')
            ->set('qtyUsed', 40.00)
            ->set('purpose', 'PRODUKSI')
            ->set('spkCode', 'SPK-2026')
            ->call('saveMaterialUsage')
            ->assertDispatched('push-notification')
            ->assertSet('activeTab', 'stock');

        $this->assertDatabaseHas('production_material_usages', [
            'outgoing_id' => $outgoing->id,
            'machine_no' => 'M-01',
            'qty_used' => 40.00,
        ]);
    }
}
