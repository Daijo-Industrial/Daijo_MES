<?php

namespace Tests\Feature;

use App\Livewire\MaterialWarehouse\MaterialDailyAudit;
use App\Models\MasterListMaterial;
use App\Models\MwhDailyAudit;
use App\Models\MwhPallet;
use App\Models\MwhPosition;
use App\Models\MwhRack;
use App\Models\MwhWarehouse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialDailyAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private MwhWarehouse $warehouse;

    private MwhRack $rack;

    private MwhPosition $pos1;

    private MwhPosition $pos2;

    private MwhPallet $pallet1;

    private MwhPallet $pallet2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Test Auditor',
        ]);

        $this->warehouse = MwhWarehouse::firstOrCreate(
            ['whse_code' => 'KBN'],
            ['whse_name' => 'Gudang Material KBN']
        );

        $this->rack = MwhRack::create([
            'whse_id' => $this->warehouse->id,
            'rack_code' => 'A1',
        ]);

        $this->pos1 = MwhPosition::create([
            'rack_id' => $this->rack->id,
            'level_no' => 1,
            'slot_no' => 1,
            'position_code' => 'A-01-01',
            'status' => 'STORED',
        ]);

        $this->pos2 = MwhPosition::create([
            'rack_id' => $this->rack->id,
            'level_no' => 1,
            'slot_no' => 2,
            'position_code' => 'A-01-02',
            'status' => 'STORED',
        ]);

        MasterListMaterial::firstOrCreate(
            ['item_code' => 'MAT-RESIN-01'],
            ['item_description' => 'Resin ABS Natural', 'purchasing_uom' => 'KG']
        );

        MasterListMaterial::firstOrCreate(
            ['item_code' => 'MAT-RESIN-02'],
            ['item_description' => 'Resin PP Black', 'purchasing_uom' => 'KG']
        );

        $this->pallet1 = MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'PAL-M-20261013-001',
            'item_code' => 'MAT-RESIN-01',
            'lot_no' => 'LOT-001',
            'initial_qty' => 500,
            'current_qty' => 500,
            'uom' => 'KG',
            'position_id' => $this->pos1->id,
            'status' => 'STORED',
        ]);

        $this->pallet2 = MwhPallet::create([
            'whse_id' => $this->warehouse->id,
            'pallet_id' => 'PAL-M-20261013-002',
            'item_code' => 'MAT-RESIN-02',
            'lot_no' => 'LOT-002',
            'initial_qty' => 300,
            'current_qty' => 250,
            'uom' => 'KG',
            'position_id' => $this->pos2->id,
            'status' => 'PARTIAL',
        ]);
    }

    public function test_daily_audit_page_can_be_accessed(): void
    {
        $response = $this->actingAs($this->user)->get(route('mwh.daily-audit'));

        $response->assertStatus(200);
        $response->assertSee('Audit Harian Material');
        $response->assertSee('A-01-01');
        $response->assertSee('A-01-02');
        $response->assertSee('MAT-RESIN-01');
        $response->assertSee('MAT-RESIN-02');
        $response->assertSee('Resin ABS Natural');
        $response->assertSee('Resin PP Black');
    }

    public function test_rack_mapping_and_pallet_index_contain_audit_harian_button(): void
    {
        $mappingResponse = $this->actingAs($this->user)->get(route('mwh.mapping'));
        $mappingResponse->assertStatus(200);
        $mappingResponse->assertSee(route('mwh.daily-audit'));
        $mappingResponse->assertSee('AUDIT HARIAN');

        $palletResponse = $this->actingAs($this->user)->get(route('mwh.pallets.index'));
        $palletResponse->assertStatus(200);
        $palletResponse->assertSee(route('mwh.daily-audit'));
        $palletResponse->assertSee('Audit Harian');
    }

    public function test_fill_all_actual_from_system_shortcut(): void
    {
        Livewire::actingAs($this->user)
            ->test(MaterialDailyAudit::class)
            ->assertCount('items', 2)
            ->call('fillAllActualFromSystem')
            ->assertSet('items.PAL-M-20261013-001.actual_item_code', 'MAT-RESIN-01')
            ->assertSet('items.PAL-M-20261013-001.actual_qty', 500)
            ->assertSet('items.PAL-M-20261013-002.actual_item_code', 'MAT-RESIN-02')
            ->assertSet('items.PAL-M-20261013-002.actual_qty', 250);
    }

    public function test_saving_audit_records_matches_and_discrepancies(): void
    {
        Livewire::actingAs($this->user)
            ->test(MaterialDailyAudit::class, [
                'auditDate' => '2026-10-13',
                'selectedWhseId' => $this->warehouse->id,
            ])
            // Row 1 matches perfectly
            ->set('items.PAL-M-20261013-001.actual_item_code', 'MAT-RESIN-01')
            ->set('items.PAL-M-20261013-001.actual_qty', 500)
            // Row 2 has discrepancy: actual qty is 240 instead of 250, with reason
            ->set('items.PAL-M-20261013-002.actual_item_code', 'MAT-RESIN-02')
            ->set('items.PAL-M-20261013-002.actual_qty', 240)
            ->set('items.PAL-M-20261013-002.discrepancy_reason', 'Selisih 10 KG terpakai sampel QC belum di-record')
            ->call('saveAudit')
            ->assertHasNoErrors()
            ->assertSee('Audit Harian tanggal 2026-10-13 berhasil disimpan');

        $this->assertDatabaseHas('mwh_daily_audits', [
            'audit_date' => '2026-10-13',
            'whse_id' => $this->warehouse->id,
            'total_items' => 2,
            'matched_items' => 1,
            'discrepant_items' => 1,
        ]);

        $this->assertDatabaseHas('mwh_daily_audit_items', [
            'pallet_id' => 'PAL-M-20261013-001',
            'position_code' => 'A-01-01',
            'system_qty' => 500,
            'actual_qty' => 500,
            'status' => 'MATCH',
        ]);

        $this->assertDatabaseHas('mwh_daily_audit_items', [
            'pallet_id' => 'PAL-M-20261013-002',
            'position_code' => 'A-01-02',
            'system_qty' => 250,
            'actual_qty' => 240,
            'diff_qty' => -10,
            'discrepancy_reason' => 'Selisih 10 KG terpakai sampel QC belum di-record',
            'status' => 'DISCREPANCY',
        ]);
    }

    public function test_history_modal_can_be_opened(): void
    {
        MwhDailyAudit::create([
            'audit_code' => 'AUD-MWH-20261012-KBN',
            'audit_date' => '2026-10-12',
            'whse_id' => $this->warehouse->id,
            'auditor_name' => 'Auditor Kemarin',
            'total_items' => 5,
            'matched_items' => 5,
            'discrepant_items' => 0,
            'status' => 'COMPLETED',
        ]);

        Livewire::actingAs($this->user)
            ->test(MaterialDailyAudit::class)
            ->call('openHistoryModal')
            ->assertSet('showHistoryModal', true)
            ->assertSee('AUD-MWH-20261012-KBN')
            ->assertSee('Auditor Kemarin');
    }
}
