<?php

namespace Tests\Feature;

use App\Livewire\Wms\PalletFormIndex;
use App\Livewire\Wms\SapSyncMonitor;
use App\Models\MasterCustomer;
use App\Models\MasterListItem;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use App\Services\WmsService;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPalletMixedSplitAndMonitorTest extends TestCase
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
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('master_customers', function ($table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('master_list_items', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('customer_code')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_warehouses', function ($table) {
            $table->id();
            $table->string('whse_code')->unique();
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_racks', function ($table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_positions', function ($table) {
            $table->id();
            $table->foreignId('rack_id');
            $table->string('position_code')->unique();
            $table->integer('level_no')->default(1);
            $table->string('customer_code')->nullable();
            $table->string('last_item_code')->nullable();
            $table->string('status')->default('EMPTY');
            $table->integer('max_capacity')->default(2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_forms', function ($table) {
            $table->string('pallet_id')->primary();
            $table->string('plant')->nullable();
            $table->foreignId('position_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->string('part_no');
            $table->string('model_name')->nullable();
            $table->date('prod_date')->nullable();
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->decimal('total_pallet_qty', 15, 2)->default(0);
            $table->string('status')->default('STORED');
            $table->boolean('is_split')->default(false);
            $table->string('split_from_pallet_id')->nullable();
            $table->json('split_into')->nullable();
            $table->json('merged_from')->nullable();
            $table->timestamp('split_at')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('sap_sync_status')->default(0);
            $table->text('sap_error_msg')->nullable();
            $table->timestamp('sap_sync_at')->nullable();
            $table->decimal('sap_sync_duration', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_form_details', function ($table) {
            $table->id();
            $table->string('pallet_form_id');
            $table->string('spk_no')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
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

        Schema::create('wms_pallet_logs', function ($table) {
            $table->id();
            $table->string('pallet_id');
            $table->string('transaction_type');
            $table->foreignId('position_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_wms_service_finds_existing_racks_for_item(): void
    {
        $wh = WmsWarehouse::create(['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']);
        $rack = WmsRack::create(['whse_id' => $wh->id, 'rack_code' => 'F01']);
        $pos = WmsPosition::create([
            'rack_id' => $rack->id,
            'position_code' => 'GUB-F01-01',
            'status' => 'PARTIAL',
            'max_capacity' => 2,
        ]);

        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-001',
            'part_no' => 'PART-A',
            'position_id' => $pos->id,
            'status' => 'STORED',
            'total_pallet_qty' => 500,
            'box_qty' => 10,
        ]);

        $service = app(WmsService::class);
        $found = $service->findExistingRacksForItem('PART-A');

        $this->assertCount(1, $found);
        $this->assertEquals('GUB-F01-01', $found->first()->position_code);
        $this->assertEquals('PLT-TEST-001', $found->first()->palletForms->first()->pallet_id);

        // Should return empty for another part
        $this->assertEmpty($service->findExistingRacksForItem('PART-B'));

        // Should ALSO find positions even when status is FULL (max_capacity = 1)
        $pos->update(['status' => 'FULL', 'max_capacity' => 1]);
        $foundFull = $service->findExistingRacksForItem('PART-A');
        $this->assertCount(1, $foundFull);
        $this->assertEquals('GUB-F01-01', $foundFull->first()->position_code);
    }

    public function test_wms_service_splits_mixed_pallet_into_existing_rack_and_new_pallet(): void
    {
        $wh = WmsWarehouse::create(['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']);
        $rack1 = WmsRack::create(['whse_id' => $wh->id, 'rack_code' => 'F01']);
        $pos1 = WmsPosition::create([
            'rack_id' => $rack1->id,
            'position_code' => 'GUB-F01-01',
            'status' => 'PARTIAL',
            'max_capacity' => 2,
        ]);

        $rack2 = WmsRack::create(['whse_id' => $wh->id, 'rack_code' => 'F02']);
        $pos2 = WmsPosition::create([
            'rack_id' => $rack2->id,
            'position_code' => 'GUB-F02-01',
            'status' => 'EMPTY',
            'max_capacity' => 2,
        ]);

        // Existing pallet carrying PART-A at pos1
        $existingPalletA = WmsPalletForm::create([
            'pallet_id' => 'PLT-EXISTING-A',
            'part_no' => 'PART-A',
            'position_id' => $pos1->id,
            'status' => 'STORED',
            'total_pallet_qty' => 400,
            'box_qty' => 10,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            WmsPalletFormDetail::create([
                'pallet_form_id' => $existingPalletA->pallet_id,
                'part_no' => 'PART-A',
                'model_name' => 'Model A',
                'spk_no' => 'SPK-EXIST-' . $i,
                'qty' => 40,
                'label' => 'BOX-EXIST-' . $i,
            ]);
        }

        // Mixed pallet to be resolved
        $mixedPallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-MIXED-999',
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'position_id' => null,
            'status' => 'STORED',
            'total_pallet_qty' => 800,
            'box_qty' => 20,
        ]);

        // 10 boxes of PART-A (total 300 pcs)
        for ($i = 1; $i <= 10; $i++) {
            WmsPalletFormDetail::create([
                'pallet_form_id' => $mixedPallet->pallet_id,
                'part_no' => 'PART-A',
                'model_name' => 'Model A',
                'spk_no' => 'SPK-A-' . $i,
                'qty' => 30,
                'label' => 'BOX-A-' . $i,
            ]);
        }

        // 10 boxes of PART-B (total 500 pcs)
        for ($i = 1; $i <= 10; $i++) {
            WmsPalletFormDetail::create([
                'pallet_form_id' => $mixedPallet->pallet_id,
                'part_no' => 'PART-B',
                'model_name' => 'Model B',
                'spk_no' => 'SPK-B-' . $i,
                'qty' => 50,
                'label' => 'BOX-B-' . $i,
            ]);
        }

        $service = app(WmsService::class);

        // Action plan: PART-A merges into existing pallet at pos1; PART-B creates new pallet at pos2
        $itemAssignments = [
            'PART-A' => [
                'action' => 'MERGE',
                'target_position_id' => $pos1->id,
                'target_pallet_id' => $existingPalletA->pallet_id,
            ],
            'PART-B' => [
                'action' => 'NEW_PALLET',
                'target_position_id' => $pos2->id,
                'target_pallet_id' => null,
            ],
        ];

        $res = $service->splitAndAssignMixedPallet($mixedPallet->pallet_id, $itemAssignments);

        $this->assertTrue($res['status']);
        $this->assertTrue($res['is_fully_resolved']);

        // Check existingPalletA received PART-A boxes
        $existingPalletA->refresh();
        $this->assertEquals(700, $existingPalletA->total_pallet_qty); // 400 + 300
        $this->assertEquals(20, $existingPalletA->box_qty); // 10 + 10

        // Check new pallet created for PART-B
        $newPalletB = WmsPalletForm::where('part_no', 'PART-B')->first();
        $this->assertNotNull($newPalletB);
        $this->assertEquals(500, $newPalletB->total_pallet_qty);
        $this->assertEquals(10, $newPalletB->box_qty);
        $this->assertEquals($pos2->id, $newPalletB->position_id);
        $this->assertEquals('PLT-MIXED-999', $newPalletB->split_from_pallet_id);

        // Check source mixedPallet is now RESOLVED (0 pcs / 0 box)
        $mixedPallet->refresh();
        $this->assertEquals(0, $mixedPallet->total_pallet_qty);
        $this->assertEquals(0, $mixedPallet->box_qty);
        $this->assertNull($mixedPallet->position_id);
        $this->assertEquals('RESOLVED', $mixedPallet->status);
        $this->assertTrue($mixedPallet->is_split);
        $this->assertCount(2, $mixedPallet->split_into);
    }

    public function test_pallet_form_index_detects_mixed_pallet_and_renders_warning(): void
    {
        $wh = WmsWarehouse::create(['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']);
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-MIX-101',
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'status' => 'STORED',
            'total_pallet_qty' => 100,
            'box_qty' => 2,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'ITEM-X',
            'qty' => 50,
        ]);
        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => 'ITEM-Y',
            'qty' => 50,
        ]);

        Livewire::test(PalletFormIndex::class)
            ->call('openAssignModal', 'PLT-MIX-101')
            ->assertSet('isMixedPallet', true)
            ->assertSet('mixedAssignMode', 'SPLIT')
            ->assertSee('Peringatan: Pallet Berisi Multi-Item (Mixed)')
            ->assertSee('Pisahkan Item (Rekomendasi)')
            ->assertSee('Tetap Assign 1 Rak (Bypass)')
            ->assertSee('ITEM-X')
            ->assertSee('ITEM-Y');
    }

    public function test_sap_sync_monitor_renders_split_button_and_modal(): void
    {
        $splitInfo = [
            [
                'part_no' => 'ITEM-A',
                'qty' => 200,
                'box_qty' => 5,
                'action' => 'MERGE',
                'target_pallet_id' => 'PLT-TARGET-1',
                'target_position_code' => 'GUB-F01-01',
            ],
            [
                'part_no' => 'ITEM-B',
                'qty' => 300,
                'box_qty' => 5,
                'action' => 'NEW_PALLET',
                'target_pallet_id' => 'PLT-NEW-2',
                'target_position_code' => 'GUB-F02-01',
            ],
        ];

        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-SPLIT-888',
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'status' => 'RESOLVED',
            'total_pallet_qty' => 0,
            'box_qty' => 0,
            'is_split' => true,
            'split_into' => $splitInfo,
            'split_at' => now(),
        ]);

        Livewire::test(SapSyncMonitor::class)
            ->assertSee('PLT-SPLIT-888')
            ->assertSee('SPLIT')
            ->call('showSplitModal', 'PLT-SPLIT-888')
            ->assertSet('showSplitInfoModal', true)
            ->assertSee('Riwayat Split Pallet')
            ->assertSee('PLT-SPLIT-888')
            ->assertSee('ITEM-A')
            ->assertSee('PLT-TARGET-1')
            ->assertSee('GUB-F01-01')
            ->assertSee('ITEM-B')
            ->assertSee('PLT-NEW-2')
            ->assertSee('GUB-F02-01')
            ->call('closeSplitModal')
            ->assertSet('showSplitInfoModal', false);
    }

    public function test_pallet_form_index_can_split_single_item_leaving_rest_in_mixed_pallet(): void
    {
        $wh = WmsWarehouse::create(['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']);
        $rack1 = WmsRack::create(['whse_id' => $wh->id, 'rack_code' => 'F01']);
        $pos1 = WmsPosition::create([
            'rack_id' => $rack1->id,
            'position_code' => 'GUB-F01-01',
            'status' => 'FULL',
            'max_capacity' => 1,
        ]);

        // Existing pallet for PART-C
        $existingPalletC = WmsPalletForm::create([
            'pallet_id' => 'PLT-EXISTING-C',
            'part_no' => 'PART-C',
            'position_id' => $pos1->id,
            'status' => 'STORED',
            'total_pallet_qty' => 50,
            'box_qty' => 5,
        ]);

        WmsPalletFormDetail::create([
            'pallet_form_id' => $existingPalletC->pallet_id,
            'part_no' => 'PART-C',
            'model_name' => 'Model C',
            'qty' => 50,
            'label' => 'BOX-C-0',
        ]);

        // Source mixed pallet
        $mixedPallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-MIXED-SINGLE',
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'position_id' => null,
            'status' => 'STORED',
            'total_pallet_qty' => 50,
            'box_qty' => 5,
        ]);

        // 2 boxes PART-A, 2 boxes PART-B, 1 box PART-C
        WmsPalletFormDetail::create(['pallet_form_id' => $mixedPallet->pallet_id, 'part_no' => 'PART-A', 'qty' => 10, 'label' => 'BOX-A-1']);
        WmsPalletFormDetail::create(['pallet_form_id' => $mixedPallet->pallet_id, 'part_no' => 'PART-A', 'qty' => 10, 'label' => 'BOX-A-2']);
        WmsPalletFormDetail::create(['pallet_form_id' => $mixedPallet->pallet_id, 'part_no' => 'PART-B', 'qty' => 10, 'label' => 'BOX-B-1']);
        WmsPalletFormDetail::create(['pallet_form_id' => $mixedPallet->pallet_id, 'part_no' => 'PART-B', 'qty' => 10, 'label' => 'BOX-B-2']);
        WmsPalletFormDetail::create(['pallet_form_id' => $mixedPallet->pallet_id, 'part_no' => 'PART-C', 'qty' => 10, 'label' => 'BOX-C-1']);

        // Test in Livewire
        $component = Livewire::test(PalletFormIndex::class)
            ->call('openAssignModal', 'PLT-MIXED-SINGLE');

        $component->assertSet('isMixedPallet', true);
        $groups = $component->get('mixedItemGroups');
        $this->assertCount(3, $groups);
        $this->assertTrue($groups['PART-C']['has_existing_rack']);

        // Execute single item split for PART-C only
        $component->call('executeSplitSingleItem', 'PART-C');

        // Check PART-C merged into existing pallet
        $existingPalletC->refresh();
        $this->assertEquals(60, $existingPalletC->total_pallet_qty);
        $this->assertEquals(2, $existingPalletC->box_qty);

        // Check source pallet still has PART-A and PART-B (40 pcs, 4 boxes)
        $mixedPallet->refresh();
        $this->assertEquals(40, $mixedPallet->total_pallet_qty);
        $this->assertEquals(4, $mixedPallet->box_qty);
        $this->assertTrue($mixedPallet->is_split);

        // Component should still have modal open with remaining 2 items
        $remainingGroups = $component->get('mixedItemGroups');
        $this->assertCount(2, $remainingGroups);
        $this->assertArrayHasKey('PART-A', $remainingGroups);
        $this->assertArrayHasKey('PART-B', $remainingGroups);
        $this->assertArrayNotHasKey('PART-C', $remainingGroups);
    }

    public function test_sap_sync_monitor_displays_split_and_gabungan_badges_and_modals(): void
    {
        $wh = WmsWarehouse::create(['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']);
        $rack = WmsRack::create(['whse_id' => $wh->id, 'rack_code' => 'F23']);
        $pos1 = WmsPosition::create([
            'rack_id' => $rack->id,
            'position_code' => 'F23-L02-S01',
            'status' => 'OCCUPIED',
            'max_capacity' => 1,
        ]);

        // Destination pallet: PLT-TARGET-0004
        $targetPallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-TARGET-0004',
            'part_no' => '31000295473',
            'model_name' => 'BODY HANDLE PANEL',
            'position_id' => $pos1->id,
            'status' => 'STORED',
            'total_pallet_qty' => 24,
            'box_qty' => 2,
            'merged_from' => [
                [
                    'source_pallet_id' => 'PLT-SOURCE-0003',
                    'part_no' => '31000295473',
                    'qty' => 12,
                    'box_qty' => 1,
                    'merged_at' => now()->toDateTimeString(),
                ],
            ],
        ]);

        // Source pallet: PLT-SOURCE-0003
        $sourcePallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-SOURCE-0003',
            'part_no' => 'MIXED',
            'model_name' => 'MULTI-ITEM',
            'status' => 'STORED',
            'total_pallet_qty' => 48,
            'box_qty' => 4,
            'is_split' => true,
            'split_at' => now(),
            'split_into' => [
                [
                    'part_no' => '31000295473',
                    'qty' => 12,
                    'box_qty' => 1,
                    'action' => 'MERGE',
                    'target_pallet_id' => 'PLT-TARGET-0004',
                    'target_position_code' => 'F23-L02-S01',
                ],
            ],
        ]);

        // Test Livewire component
        $component = Livewire::test(\App\Livewire\Wms\SapSyncMonitor::class);

        // Assert rendered buttons
        $component->assertSee('PLT-SOURCE-0003')
            ->assertSee('SPLIT')
            ->assertSee('PLT-TARGET-0004')
            ->assertSee('GABUNGAN');

        // Test open Split Modal
        $component->call('showSplitModal', 'PLT-SOURCE-0003')
            ->assertSet('showSplitInfoModal', true)
            ->assertSee('Riwayat Split Pallet')
            ->assertSee('PLT-TARGET-0004')
            ->assertSee('F23-L02-S01');

        $component->call('closeSplitModal')
            ->assertSet('showSplitInfoModal', false);

        // Test open Merge Modal
        $component->call('showMergeModal', 'PLT-TARGET-0004')
            ->assertSet('showMergeInfoModal', true)
            ->assertSee('Riwayat Gabungan Box (Konsolidasi)')
            ->assertSee('PLT-SOURCE-0003')
            ->assertSee('+1 Box');

        $component->call('closeMergeModal')
            ->assertSet('showMergeInfoModal', false);
    }
}
