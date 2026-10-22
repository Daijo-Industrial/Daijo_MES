<?php

namespace Tests\Feature;

use App\Livewire\Ppic\PpicListUpMachine;
use App\Models\DailyItemCode;
use App\Models\MasterListItem;
use App\Models\MasterListMaterial;
use App\Models\PpicListUp;
use App\Models\Role;
use App\Models\SpkMaster;
use App\Models\User;
use App\Services\PpicListUpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PpicListUpMachineTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles & operator user exists
        $operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        $user = User::firstOrCreate(
            ['username' => '0350F'],
            [
                'name' => '350F',
                'email' => '350F@test.com',
                'password' => bcrypt('password'),
                'role_id' => $operatorRole->id,
            ]
        );
        $user->role_id = $operatorRole->id;
        $user->save();
    }

    public function test_it_resolves_part_details_and_material_type()
    {
        $partNo = 'TEST-PART-01';

        MasterListItem::updateOrCreate(
            ['item_code' => $partNo],
            [
                'item_name' => 'BRACKET TEST 5H45',
                'tipe_mesin' => 'INJECTION',
                'standart_packaging_list' => 100,
                'cavity' => 2,
                'cycle_time' => 50,
            ]
        );

        // SPKs: create an older one and a newer one
        SpkMaster::create([
            'spk_number' => 'SPK-OLD-001',
            'item_code' => $partNo,
            'post_date' => '2026-09-01',
            'due_date' => '2026-09-10',
            'planned_quantity' => 1000,
            'completed_quantity' => 0,
            'warehouse' => 'GUB',
            'production_status' => 'R',
        ]);
        SpkMaster::create([
            'spk_number' => 'SPK-NEW-002',
            'item_code' => $partNo,
            'post_date' => '2026-09-20',
            'due_date' => '2026-09-30',
            'planned_quantity' => 1000,
            'completed_quantity' => 0,
            'warehouse' => 'GUB',
            'production_status' => 'R',
        ]);

        // Master List Material
        MasterListMaterial::updateOrCreate(
            ['item_code' => '405-PBT7300E-TEST'],
            ['item_description' => 'PBT DURANEX RAW']
        );

        // Master BOM FG Header & Component
        DB::table('master_bom_components')->where('parent_item', $partNo)->delete();
        $fg = \App\Models\MasterBomFgHeader::updateOrCreate(
            ['fg_item_code' => $partNo],
            [
                'fg_description' => 'BRACKET TEST 5H45',
                'total_wip_count' => 0,
                'total_raw_count' => 1,
                'max_depth_level' => 1,
                'has_packaging' => false,
            ]
        );

        DB::table('master_bom_components')->insert([
            'fg_id' => $fg->id,
            'parent_item' => $partNo,
            'component_item' => '405-PBT7300E-TEST',
            'component_description' => 'PBT DURANEX RAW',
            'depth_level' => 1,
            'item_type' => 'RAW_MATERIAL',
            'is_wip' => 0,
            'unit_qty' => 0.113,
            'total_qty_per_fg' => 0.113,
            'uom' => 'KG',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PpicListUpService::class);
        $details = $service->resolvePartDetails($partNo);

        $this->assertEquals('BRACKET TEST 5H45', $details['description']);
        $this->assertEquals(2, $details['cavity']);
        $this->assertEquals(50, $details['cycle_time']);
        $this->assertEquals(72, $details['target_per_hour']); // 3600 / 50 = 72
        $this->assertEquals('SPK-OLD-001', $details['spk_no']);
        $this->assertEquals('405-PBT7300E-TEST', $details['material_type']);
    }

    public function test_it_generates_daily_item_codes_evenly_per_active_shift()
    {
        $date = '2026-10-05';
        $partNo = 'TEST-PART-RUN';

        $machineUser = User::where('name', '350F')->first();

        MasterListItem::updateOrCreate(
            ['item_code' => $partNo],
            [
                'item_name' => 'PART RUN A',
                'tipe_mesin' => 'INJECTION',
                'standart_packaging_list' => 100,
                'cavity' => 1,
                'cycle_time' => 60,
            ]
        );

        $listUp = PpicListUp::create([
            'date' => $date,
            'zone' => 'ZONE A',
            'status' => 'DRAFT',
        ]);

        // Item 1: Operator I: 1, II: 1, III: 1, Qty: 300 -> each shift gets 100
        $listUp->items()->create([
            'machine_id' => $machineUser->id,
            'machine_name' => '350F',
            'part_no' => $partNo,
            'description' => 'PART RUN A',
            'cavity' => 1,
            'cycle_time' => 60,
            'target_per_hour' => 60,
            'operator_shift_1' => 1,
            'operator_shift_2' => 1,
            'operator_shift_3' => 1,
            'qty_to_run' => 300,
            'reason' => 'TRIAL RUN',
        ]);

        $service = app(PpicListUpService::class);
        $result = $service->generateDailyItemCodes($listUp);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['generated_count']);

        // Check Shift 1, 2, 3 in daily_item_codes
        $dics = DailyItemCode::where('schedule_date', $date)
            ->where('item_code', $partNo)
            ->get();

        $this->assertCount(3, $dics);
        foreach ($dics as $dic) {
            $this->assertEquals(100, $dic->quantity);
            $this->assertEquals('TRIAL RUN', $dic->remark);
            $this->assertNull($dic->is_done);
        }

        // Machine job must be synced with dic_id
        $mJob = \App\Models\MachineJob::where('user_id', $machineUser->id)->first();
        $this->assertNotNull($mJob);
        $this->assertEquals($partNo, $mJob->item_code);
        $this->assertNotNull($mJob->dic_id);
    }

    public function test_livewire_component_interactivity()
    {
        $admin = User::first() ?: User::factory()->create();

        Livewire::actingAs($admin)
            ->test(PpicListUpMachine::class, ['date' => '2026-10-05'])
            ->assertStatus(200)
            ->assertSee('List Up Production Machine')
            ->set('rows.0.part_no', 'TEST-PART-01')
            ->set('rows.0.operator_shift_1', 2)
            ->call('onPartNoInput', 0)
            ->call('saveDraft')
            ->assertSee('berhasil disimpan')
            ->set('rows.0.qty_to_run', '')
            ->set('rows.0.operator_shift_1', '')
            ->assertStatus(200)
            ->assertSee('Total Qty Produksi');
    }

    public function test_spk_dropdown_and_modal_search()
    {
        $admin = User::first() ?: User::factory()->create();

        $partNo = 'TEST-SPK-SELECT';
        MasterListItem::updateOrCreate(
            ['item_code' => $partNo],
            [
                'item_name' => 'BRACKET SPK',
                'tipe_mesin' => 'INJECTION',
                'standart_packaging_list' => 100,
                'cavity' => 2,
                'cycle_time' => 30,
            ]
        );

        SpkMaster::create([
            'spk_number' => 'SPK-AUTO-01',
            'item_code' => $partNo,
            'post_date' => '2026-09-01',
            'due_date' => '2026-09-30',
            'planned_quantity' => 500,
            'completed_quantity' => 100,
            'warehouse' => 'GUB',
            'production_status' => 'R',
        ]);

        SpkMaster::create([
            'spk_number' => 'SPK-AUTO-02',
            'item_code' => $partNo,
            'post_date' => '2026-09-10',
            'due_date' => '2026-09-30',
            'planned_quantity' => 1200,
            'completed_quantity' => 0,
            'warehouse' => 'GUB',
            'production_status' => 'R',
        ]);

        Livewire::actingAs($admin)
            ->test(PpicListUpMachine::class, ['date' => '2026-10-05'])
            ->set('rows.0.part_no', $partNo)
            ->call('onPartNoInput', 0)
            // It should auto-fill spk_no with oldest open SPK
            ->assertSet('rows.0.spk_no', 'SPK-AUTO-01')
            // available_spks should contain both SPKs
            ->assertCount('rows.0.available_spks', 2)
            // Switch to manual mode
            ->call('toggleManualSpk', 0)
            ->assertSet('rows.0.is_manual_spk', true)
            // Switch back to dropdown mode
            ->call('toggleManualSpk', 0)
            ->assertSet('rows.0.is_manual_spk', false)
            // Open SPK Search modal
            ->call('openSpkSearch', 0)
            ->assertSet('showSpkSearchModal', true)
            ->assertSee('SPK-AUTO-01')
            ->assertSee('SPK-AUTO-02')
            // Select second SPK
            ->call('selectSpk', 'SPK-AUTO-02', 1200, $partNo)
            ->assertSet('rows.0.spk_no', 'SPK-AUTO-02')
            ->assertSet('showSpkSearchModal', false);
    }

    public function test_it_shows_warning_when_part_has_no_spk()
    {
        $admin = User::first() ?: User::factory()->create();

        $partNo = 'TEST-PART-NO-SPK';
        MasterListItem::updateOrCreate(
            ['item_code' => $partNo],
            [
                'item_name' => 'NO SPK PART',
                'tipe_mesin' => 'INJECTION',
                'standart_packaging_list' => 50,
                'cavity' => 1,
                'cycle_time' => 45,
            ]
        );

        Livewire::actingAs($admin)
            ->test(PpicListUpMachine::class, ['date' => '2026-10-05'])
            ->set('rows.0.part_no', $partNo)
            ->call('onPartNoInput', 0)
            ->assertSet('rows.0.spk_no', '')
            ->assertCount('rows.0.available_spks', 0)
            ->assertSee('Tidak ada SPK');
    }

    public function test_history_modal_and_locked_status_behavior()
    {
        $admin = User::first() ?: User::factory()->create();

        $listUp = PpicListUp::create([
            'date' => '2026-10-10',
            'status' => 'GENERATED',
            'notes' => 'Catatan final',
        ]);

        Livewire::actingAs($admin)
            ->test(PpicListUpMachine::class, ['date' => '2026-10-10'])
            ->assertSet('isLocked', true)
            ->assertSet('status', 'GENERATED')
            ->assertSee('List Up Sudah Difinalisasi')
            // Cannot save draft while locked
            ->call('saveDraft')
            ->assertSee('terkunci dari perubahan')
            // Unlock with reopenDraft
            ->call('reopenDraft')
            ->assertSet('isLocked', false)
            ->assertSet('status', 'DRAFT')
            // Open History Modal
            ->call('openHistory')
            ->assertSet('showHistoryModal', true)
            ->assertSee('Riwayat List Up Mesin Harian')
            // Switch date via history
            ->call('selectHistoryDate', '2026-10-11')
            ->assertSet('selectedDate', '2026-10-11')
            ->assertSet('showHistoryModal', false);
    }

    public function test_re_generate_cleans_up_obsolete_daily_item_codes()
    {
        $date = '2026-10-20';
        $machineUser = User::where('name', '350F')->first();

        $listUp = PpicListUp::create([
            'date' => $date,
            'status' => 'DRAFT',
        ]);

        // 1. Initial Generation: Item A assigned Shift 1, 2, 3 (Qty 900 -> 300 per shift)
        $itemA = $listUp->items()->create([
            'machine_id' => $machineUser->id,
            'machine_name' => '350F',
            'part_no' => 'PART-A',
            'operator_shift_1' => 1,
            'operator_shift_2' => 1,
            'operator_shift_3' => 1,
            'qty_to_run' => 900,
        ]);

        $service = app(PpicListUpService::class);
        $service->generateDailyItemCodes($listUp);

        $initialDics = DailyItemCode::where('schedule_date', $date)->where('user_id', $machineUser->id)->get();
        $this->assertCount(3, $initialDics);
        $this->assertTrue($initialDics->every(fn ($d) => $d->item_code === 'PART-A'));

        // 2. Revision: Item A now only runs Shift 1 (200 pcs), Item B runs Shift 2 (200 pcs), Item C runs Shift 3 (500 pcs)
        $itemA->update([
            'operator_shift_1' => 1,
            'operator_shift_2' => 0,
            'operator_shift_3' => 0,
            'qty_to_run' => 200,
        ]);

        $listUp->items()->create([
            'machine_id' => $machineUser->id,
            'machine_name' => '350F',
            'part_no' => 'PART-B',
            'operator_shift_1' => 0,
            'operator_shift_2' => 1,
            'operator_shift_3' => 0,
            'qty_to_run' => 200,
        ]);

        $listUp->items()->create([
            'machine_id' => $machineUser->id,
            'machine_name' => '350F',
            'part_no' => 'PART-C',
            'operator_shift_1' => 0,
            'operator_shift_2' => 0,
            'operator_shift_3' => 1,
            'qty_to_run' => 500,
        ]);

        // Re-generate
        $service->generateDailyItemCodes($listUp);

        $revisedDics = DailyItemCode::where('schedule_date', $date)
            ->where('user_id', $machineUser->id)
            ->orderBy('shift')
            ->get();

        // Must strictly have 3 items matching the new configuration, NOT 5 (old shift 2 & 3 for Part A must be deleted)
        $this->assertCount(3, $revisedDics);
        $this->assertEquals('PART-A', $revisedDics[0]->item_code);
        $this->assertEquals(1, $revisedDics[0]->shift);
        $this->assertEquals(200, $revisedDics[0]->quantity);

        $this->assertEquals('PART-B', $revisedDics[1]->item_code);
        $this->assertEquals(2, $revisedDics[1]->shift);
        $this->assertEquals(200, $revisedDics[1]->quantity);

        $this->assertEquals('PART-C', $revisedDics[2]->item_code);
        $this->assertEquals(3, $revisedDics[2]->shift);
        $this->assertEquals(500, $revisedDics[2]->quantity);
    }
}
