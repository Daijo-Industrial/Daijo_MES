<?php

namespace Tests\Feature;

use App\Livewire\Wms\PalletFormIndex;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPalletCameraScanTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_scan_pallet_direct_and_rack_direct_methods_work_atomically()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::now()->toDateString();

        $whse = WmsWarehouse::create([
            'whse_code' => 'GUA',
            'whse_name' => 'Gudang Utama A',
        ]);

        $rack = WmsRack::create([
            'whse_id' => $whse->id,
            'rack_code' => 'F01',
        ]);

        $pos = WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'F01-L01-S01',
            'status' => 'EMPTY',
            'max_capacity' => 1,
        ]);

        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-CAM-001',
            'part_no' => 'PART-CAM-01',
            'model_name' => 'CAM TEST MODEL',
            'prod_date' => $today,
            'plant' => 'KBN',
            'box_qty' => 2,
            'total_pallet_qty' => 24,
            'status' => 'STORED',
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-CAM-001',
            'part_no' => 'PART-CAM-01',
            'spk_no' => 'SPK-CAM-01',
            'label' => '1',
            'qty' => 12,
            'warehouse' => 'FG',
        ]);

        // Test Livewire scanPalletDirect atomic execution
        Livewire::test(PalletFormIndex::class)
            ->call('scanPalletDirect', 'PLT-CAM-001')
            ->assertSet('scanPalletId', 'PLT-CAM-001')
            ->assertSet('scannedPallet.pallet_id', 'PLT-CAM-001')
            ->assertDispatched('scan-success')
            ->call('scanRackPositionDirect', 'F01-L01-S01')
            ->assertSet('selectedPositionId', $pos->id)
            ->assertDispatched('scan-success');
    }
}
