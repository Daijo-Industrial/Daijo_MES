<?php

namespace Tests\Feature;

use App\Models\DailyItemCode;
use App\Models\MachineJob;
use App\Models\ProductionScannedData;
use App\Models\Role;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductionDayDashboardPalletTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_scanned_data_maps_pallet_id_and_store_status_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Machine user
        $machineUser = User::factory()->create(['name' => 'TEST-MC-01']);
        $machineJob = MachineJob::create(['user_id' => $machineUser->id]);

        $today = Carbon::now()->toDateString();

        $dailyItem = DailyItemCode::create([
            'user_id' => $machineUser->id,
            'schedule_date' => $today,
            'item_code' => 'TEST-ITEM-01',
            'quantity' => 100,
            'actual_quantity' => 24,
            'shift' => 1,
            'start_date' => $today,
            'start_time' => '08:00:00',
            'end_date' => $today,
            'end_time' => '16:00:00',
        ]);

        // Scanned Box 1: linked to Pallet 1 (Stored in Rack)
        $scan1 = ProductionScannedData::create([
            'spk_code' => 'SPK-999001',
            'dic_id' => $dailyItem->id,
            'item_code' => 'TEST-ITEM-01',
            'warehouse' => 'FG',
            'quantity' => 12,
            'label' => '1',
            'user' => 'Operator A',
        ]);

        // Scanned Box 2: linked to Pallet 2 (Waiting rack / position null)
        $scan2 = ProductionScannedData::create([
            'spk_code' => 'SPK-999001',
            'dic_id' => $dailyItem->id,
            'item_code' => 'TEST-ITEM-01',
            'warehouse' => 'FG',
            'quantity' => 12,
            'label' => '2',
            'user' => 'Operator B',
        ]);

        // Scanned Box 3: not in pallet
        $scan3 = ProductionScannedData::create([
            'spk_code' => 'SPK-999001',
            'dic_id' => $dailyItem->id,
            'item_code' => 'TEST-ITEM-01',
            'warehouse' => 'FG',
            'quantity' => 12,
            'label' => '3',
            'user' => 'Operator C',
        ]);

        // Create WMS Warehouse, Rack, & Position
        $whse = \App\Models\WmsWarehouse::create([
            'whse_code' => 'GDB',
            'whse_name' => 'Gudang Utama B',
        ]);

        $rack = \App\Models\WmsRack::create([
            'whse_id' => $whse->id,
            'rack_code' => 'F23',
        ]);

        $position = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'RAK-TEST-01',
            'status' => 'FULL',
            'max_capacity' => 1,
        ]);

        // Create Pallet 1 (Assigned to Rack)
        $pallet1 = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-001',
            'position_id' => $position->id,
            'assigned_at' => now(),
            'part_no' => 'TEST-ITEM-01',
            'model_name' => 'TEST MODEL 1',
            'prod_date' => $today,
            'plant' => 'KBN',
            'box_qty' => 1,
            'total_pallet_qty' => 12,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-TEST-001',
            'part_no' => 'TEST-ITEM-01',
            'spk_no' => 'SPK-999001',
            'label' => '1',
            'qty' => 12,
            'warehouse' => 'FG',
        ]);

        // Create Pallet 2 (Delivery created, awaiting rack)
        $pallet2 = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-002',
            'position_id' => null,
            'assigned_at' => null,
            'part_no' => 'TEST-ITEM-01',
            'model_name' => 'TEST MODEL 1',
            'prod_date' => $today,
            'plant' => 'KBN',
            'box_qty' => 1,
            'total_pallet_qty' => 12,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-TEST-002',
            'part_no' => 'TEST-ITEM-01',
            'spk_no' => 'SPK-999001',
            'label' => '2',
            'qty' => 12,
            'warehouse' => 'FG',
        ]);

        // Call controller index
        $response = $this->get(route('djoni.dashboard', [
            'date' => $today,
            'machine_name' => 'TEST-MC-01',
        ]));

        $response->assertStatus(200);
        $response->assertSee('PLT-TEST-001');
        $response->assertSee('Di Rak RAK-TEST-01');
        $response->assertSee('PLT-TEST-002');
        $response->assertSee('Terkirim (Menunggu Rak)');
        $response->assertSee('Belum di-Pallet');
        $response->assertSee('Belum Dikirim');
    }
}
