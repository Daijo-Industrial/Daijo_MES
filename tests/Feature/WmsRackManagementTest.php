<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\WmsWarehouse;
use App\Models\WmsRack;
use App\Models\WmsPosition;
use App\Models\WmsPalletForm;
use App\Livewire\Wms\RackMapping;
use Livewire\Livewire;

class WmsRackManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->warehouse = WmsWarehouse::create([
            'whse_code' => 'J06',
            'whse_name' => 'Monitoring Hunian Rak Gudang J06 (Highly Marelli)',
        ]);
    }

    public function test_rack_and_warehouse_management()
    {
        $this->actingAs($this->user);

        // 1. Warehouse update
        Livewire::test(RackMapping::class)
            ->set('whseCode', 'J08')
            ->set('whseName', 'Gudang Finished Goods J08')
            ->call('saveWarehouse')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wms_warehouses', ['whse_code' => 'J08']);

        // 2. Create rack & add slot, remove slot, re-add slot
        $rack = WmsRack::create(['whse_id' => $this->warehouse->id, 'rack_code' => '004']);
        WmsPosition::create(['rack_id' => $rack->id, 'level_no' => 1, 'slot_no' => 1, 'position_code' => 'J06-HM-004-L1S1']);

        Livewire::test(RackMapping::class)
            ->call('addSlotToRack', $rack->id)
            ->call('removeSlotFromRack', $rack->id)
            ->call('addSlotToRack', $rack->id)
            ->call('addLevelToRack', $rack->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wms_positions', ['position_code' => 'J06-HM-004-L1S2']);
        $this->assertDatabaseHas('wms_positions', ['position_code' => 'J06-HM-004-L2S1']);
    }

    public function test_dynamic_rack_orientation_and_zone_customization()
    {
        $this->actingAs($this->user);

        // 1. Create a dynamic rack with horizontal orientation & logistic zone
        Livewire::test(RackMapping::class, ['whseId' => $this->warehouse->id])
            ->set('newRackCode', 'F41')
            ->set('newOrientation', 'HORIZONTAL')
            ->set('newZone', 'LOGISTIC')
            ->set('newLevels', 3)
            ->set('newSlotsPerLevel', 6)
            ->set('newMaxCapacity', 2)
            ->call('createNewRack')
            ->assertHasNoErrors();

        $rack = WmsRack::where('rack_code', 'F41')->firstOrFail();
        $this->assertEquals('HORIZONTAL', $rack->orientation);
        $this->assertEquals('LOGISTIC', $rack->effective_zone);
        $this->assertCount(18, $rack->positions);

        // 2. Toggle orientation to VERTICAL dynamically
        Livewire::test(RackMapping::class, ['whseId' => $this->warehouse->id])
            ->call('toggleRackOrientation', $rack->id)
            ->assertHasNoErrors();

        $rack->refresh();
        $this->assertEquals('VERTICAL', $rack->orientation);

        // 3. Update zone to STORE dynamically
        Livewire::test(RackMapping::class, ['whseId' => $this->warehouse->id])
            ->call('updateRackZone', $rack->id, 'STORE')
            ->assertHasNoErrors();

        $rack->refresh();
        $this->assertEquals('STORE', $rack->effective_zone);

        // 4. Test Auto-Arrange Racks
        Livewire::test(RackMapping::class, ['whseId' => $this->warehouse->id])
            ->call('autoArrangeRacks')
            ->assertHasNoErrors();
    }
}
