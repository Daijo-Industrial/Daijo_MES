<?php

namespace Tests\Feature;

use App\Exports\MouldChecklistExport;
use App\Livewire\Maintenance\MasterMould\Index as MasterMouldIndex;
use App\Livewire\Maintenance\MouldChecklist\Form as MouldChecklistForm;
use App\Livewire\Maintenance\MouldChecklist\Index as MouldChecklistIndex;
use App\Livewire\Maintenance\MouldChecklist\Show as MouldChecklistShow;
use App\Models\MasterMould;
use App\Models\MouldChecklist;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MouldChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;
    protected Role $adminRole;
    protected Role $leaderRole;
    protected Role $operatorRole;
    protected Role $mouldLeaderRole;

    protected User $superAdminUser;
    protected User $adminUser;
    protected User $leaderUser;
    protected User $operatorUser;
    protected User $mouldLeaderUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::create(['name' => 'SUPER-ADMIN']);
        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->leaderRole = Role::create(['name' => 'LEADER']);
        $this->operatorRole = Role::create(['name' => 'OPERATOR']);
        $this->mouldLeaderRole = Role::create(['name' => 'MOULD-MAINTENANCE-LEADER']);

        $this->superAdminUser = User::factory()->create(['name' => 'Super Admin User', 'role_id' => $this->superAdminRole->id]);
        $this->adminUser = User::factory()->create(['name' => 'Admin User', 'role_id' => $this->adminRole->id]);
        $this->leaderUser = User::factory()->create(['name' => 'Leader User', 'role_id' => $this->leaderRole->id]);
        $this->operatorUser = User::factory()->create(['name' => 'Operator User', 'role_id' => $this->operatorRole->id]);
        $this->mouldLeaderUser = User::factory()->create(['name' => 'Mould Leader User', 'role_id' => $this->mouldLeaderRole->id]);
    }

    public function test_master_mould_can_be_created_and_managed(): void
    {
        // 1. Non-superadmin cannot see upload/add buttons and is forbidden from calling modals or mutating moulds
        $this->actingAs($this->adminUser);
        Livewire::test(MasterMouldIndex::class)
            ->assertDontSee('Upload Excel')
            ->assertDontSee('Tambah Mould')
            ->assertDontSee('Aksi')
            ->call('openAddModal')
            ->assertForbidden();

        Livewire::test(MasterMouldIndex::class)
            ->call('openUploadModal')
            ->assertForbidden();

        Livewire::test(MasterMouldIndex::class)
            ->call('saveMould')
            ->assertForbidden();

        // 2. Super-admin can see buttons, create, and manage mould
        $this->actingAs($this->superAdminUser);

        Livewire::test(MasterMouldIndex::class)
            ->assertSee('Upload Excel')
            ->assertSee('Tambah Mould')
            ->call('openAddModal')
            ->assertSet('showFormModal', true)
            ->set('mould_no', 'IK-MM-005-010')
            ->set('part_no', '401-76934B001P')
            ->set('part_name', 'GARNISH RR PLR RH 5H45')
            ->set('model', '5H45')
            ->set('customer', 'IKUYO')
            ->set('machine_number', '1300T')
            ->set('material', 'PP-KF06 LD')
            ->call('saveMould')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('master_moulds', [
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'customer' => 'IKUYO',
        ]);

        // Test Livewire Master Mould Manager
        Livewire::test(MasterMouldIndex::class)
            ->set('search', 'IK-MM-005')
            ->assertSee('IK-MM-005-010')
            ->assertSee('401-76934B001P');
    }

    public function test_technician_can_create_mould_checklist_draft_with_16_items(): void
    {
        $this->actingAs($this->operatorUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'model' => '5H45',
            'customer' => 'IKUYO',
            'machine_number' => '1300T',
        ]);

        $test = Livewire::test(MouldChecklistForm::class)
            ->call('selectMould', $mould->id)
            ->assertSet('mould_no', 'IK-MM-005-010')
            ->assertSet('part_no', '401-76934B001P')
            ->assertSet('customer', 'IKUYO')
            ->assertSet('machine_number', '1300T')
            ->assertSee('Master Mould Terverifikasi')
            ->assertSee('Ganti Mould')
            ->set('technician_name', 'Maulana + Sorie')
            ->set('start_time', '14:00')
            ->set('end_time', '20:00')
            ->call('saveDraft');

        $test->assertRedirect();

        $checklist = MouldChecklist::first();
        $this->assertNotNull($checklist);
        $this->assertEquals('DRAFT', $checklist->status);
        $this->assertEquals('IK-MM-005-010', $checklist->mould_no);
        $this->assertEquals('Maulana + Sorie', $checklist->technician_name);
        $this->assertEquals('14:00', $checklist->start_time);
        $this->assertEquals('20:00', $checklist->end_time);
    }

    public function test_mould_checklist_form_supports_clear_and_manual_fallback(): void
    {
        $this->actingAs($this->operatorUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
        ]);

        Livewire::test(MouldChecklistForm::class)
            ->assertSee('Pilih Mould dari Master Mould')
            ->assertSee('Mould belum terdaftar? Input Manual')
            ->call('selectMould', $mould->id)
            ->assertSee('Master Mould Terverifikasi')
            ->assertSee('Ganti Mould')
            ->call('clearSelectedMould')
            ->assertSet('mould_no', '')
            ->assertSee('Pilih Mould dari Master Mould')
            ->call('enableManualMould')
            ->assertSet('isManualMould', true)
            ->assertSee('Mode Input Manual (Mould Belum Terdaftar)')
            ->set('customer', 'MANUAL CUST')
            ->set('mould_no', 'MAN-001')
            ->set('part_no', 'PART-MAN-01')
            ->set('part_name', 'Manual Part')
            ->set('technician_name', 'Teknisi Manual')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mould_checklists', [
            'customer' => 'MANUAL CUST',
            'mould_no' => 'MAN-001',
            'part_no' => 'PART-MAN-01',
        ]);

        $checklist = MouldChecklist::where('mould_no', 'MAN-001')->first();
        $this->assertNotNull($checklist);
        $this->assertNull($checklist->mould_id);
        $this->assertDatabaseMissing('master_moulds', ['mould_no' => 'MAN-001']);
        $this->assertEquals(16, $checklist->details()->count());

        // Verify specific point check name
        $coolingDetail = $checklist->details()->where('item_no', 1)->first();
        $this->assertEquals('COOLING CIRCULATION', $coolingDetail->point_check);
    }

    public function test_technician_can_submit_and_sign_issued_by(): void
    {
        $this->actingAs($this->operatorUser);

        $mould = MasterMould::create([
            'mould_no' => 'MLD-TEST-01',
            'part_no' => 'PART-TEST-01',
            'part_name' => 'Test Bracket Part',
            'customer' => 'PT IFSP',
            'machine_number' => 'MC-01',
        ]);

        Livewire::test(MouldChecklistForm::class)
            ->call('selectMould', $mould->id)
            ->set('technician_name', 'Operator User')
            ->call('submitAndSign')
            ->assertRedirect();

        $checklist = MouldChecklist::first();
        $this->assertEquals('SUBMITTED', $checklist->status);
        $this->assertEquals($this->operatorUser->id, $checklist->issued_by_id);
        $this->assertEquals('Operator User', $checklist->issued_name);
        $this->assertNotNull($checklist->issued_at);
        $this->assertNull($checklist->checked_at);
    }

    public function test_leader_or_supervisor_can_approve_checklist(): void
    {
        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'MLD-TEST-02',
            'part_no' => 'PART-02',
            'part_name' => 'Test Cover',
            'customer' => 'PT IFSP',
            'technician_name' => 'Teknisi 1',
            'status' => 'SUBMITTED',
            'issued_by_id' => $this->operatorUser->id,
            'issued_name' => 'Teknisi 1',
            'issued_at' => now(),
        ]);

        $this->actingAs($this->leaderUser);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('approveAndSign')
            ->assertHasNoErrors();

        $checklist->refresh();
        $this->assertEquals('APPROVED', $checklist->status);
        $this->assertEquals($this->leaderUser->id, $checklist->checked_by_id);
        $this->assertEquals('Leader User', $checklist->checked_name);
        $this->assertNotNull($checklist->checked_at);
    }

    public function test_leader_can_reject_checklist_back_to_draft(): void
    {
        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'MLD-TEST-03',
            'part_no' => 'PART-03',
            'part_name' => 'Test Panel',
            'customer' => 'PT IFSP',
            'technician_name' => 'Teknisi 2',
            'status' => 'SUBMITTED',
            'issued_by_id' => $this->operatorUser->id,
            'issued_name' => 'Teknisi 2',
            'issued_at' => now(),
        ]);

        $this->actingAs($this->leaderUser);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('openRejectModal')
            ->set('rejectionNote', 'Pelumasan ejector pin belum lengkap.')
            ->call('confirmReject')
            ->assertHasNoErrors();

        $checklist->refresh();
        $this->assertEquals('REJECTED', $checklist->status);
        $this->assertEquals('Pelumasan ejector pin belum lengkap.', $checklist->rejection_note);
    }

    public function test_unauthorized_user_cannot_sign_check_by(): void
    {
        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'MLD-TEST-04',
            'part_no' => 'PART-04',
            'part_name' => 'Test Frame',
            'customer' => 'PT IFSP',
            'technician_name' => 'Teknisi 3',
            'status' => 'SUBMITTED',
        ]);

        // Operator role is not mapped to checked_by in config/roles.php
        $this->actingAs($this->operatorUser);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('approveAndSign');

        $checklist->refresh();
        $this->assertEquals('SUBMITTED', $checklist->status);
        $this->assertNull($checklist->checked_at);
    }

    public function test_mould_checklist_index_and_excel_export(): void
    {
        Excel::fake();

        MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi A',
            'status' => 'APPROVED',
        ]);

        // 1. Non-superadmin cannot see export button and is forbidden from calling exportExcel
        $this->actingAs($this->adminUser);
        Livewire::test(MouldChecklistIndex::class)
            ->assertDontSee('Export Excel')
            ->call('exportExcel')
            ->assertForbidden();

        // 2. Superadmin can see button and download Excel
        $this->actingAs($this->superAdminUser);
        Livewire::test(MouldChecklistIndex::class)
            ->assertSee('Export Excel')
            ->call('exportExcel');

        Excel::assertDownloaded('Mould_Checklist_' . now()->format('Ymd_His') . '.xlsx', function (MouldChecklistExport $export) {
            return true;
        });
    }

    public function test_1_to_1_print_view_renders_successfully(): void
    {
        $this->actingAs($this->adminUser);

        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'model' => '5H45',
            'customer' => 'IKUYO',
            'machine_number' => '1300T',
            'technician_name' => 'Maulana + Sorie',
            'start_time' => '14:00',
            'end_time' => '20:00',
            'status' => 'APPROVED',
            'issued_name' => 'Maulana',
            'issued_at' => now(),
            'checked_name' => 'Supervisor A',
            'checked_at' => now(),
        ]);

        // Seed 16 items
        foreach (MouldChecklist::STANDARD_CHECK_POINTS as $no => $item) {
            $checklist->details()->create([
                'item_no' => $no,
                'point_check' => $item['name'],
                'cleaning_status' => $item['cleaning'],
                'lubricate_status' => $item['lubricate'],
            ]);
        }

        $response = $this->get(route('mould-checklist.show', $checklist->id));
        $response->assertOk();
        $response->assertSee('DI-F-P/PD/05/MTM-043');
        $response->assertSee('LIST CHECK SHEET');
        $response->assertSee('MOULD MAINTENANCE');
        $response->assertSee('IK-MM-005-010');
        $response->assertSee('GARNISH RR PLR RH 5H45');
        $response->assertSee('Maulana + Sorie');
        $response->assertSee('COOLING CIRCULATION');
        $response->assertSee('EJECTOR PIN');
        $response->assertSee('14:00 s/d 20:00');
    }

    public function test_mould_maintenance_leader_auto_signs_check_by_on_creation(): void
    {
        $this->actingAs($this->mouldLeaderUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
        ]);

        $test = Livewire::test(MouldChecklistForm::class)
            ->assertSet('auto_sign_check_by', true)
            ->assertSee('Otomatis tandatangani')
            ->assertSee('Check By')
            ->call('selectMould', $mould->id)
            ->set('technician_name', 'Leader Teknisi')
            ->call('submitAndSign');

        $test->assertRedirect();

        $checklist = MouldChecklist::first();
        $this->assertNotNull($checklist);
        $this->assertEquals('APPROVED', $checklist->status);
        $this->assertEquals($this->mouldLeaderUser->id, $checklist->issued_by_id);
        $this->assertEquals($this->mouldLeaderUser->name, $checklist->issued_name);
        $this->assertNotNull($checklist->issued_at);
        $this->assertEquals($this->mouldLeaderUser->id, $checklist->checked_by_id);
        $this->assertEquals($this->mouldLeaderUser->name, $checklist->checked_name);
        $this->assertNotNull($checklist->checked_at);
    }

    public function test_mould_maintenance_leader_can_uncheck_auto_sign(): void
    {
        $this->actingAs($this->mouldLeaderUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
        ]);

        $test = Livewire::test(MouldChecklistForm::class)
            ->set('auto_sign_check_by', false)
            ->call('selectMould', $mould->id)
            ->set('technician_name', 'Leader Teknisi')
            ->call('submitAndSign');

        $test->assertRedirect();

        $checklist = MouldChecklist::first();
        $this->assertNotNull($checklist);
        $this->assertEquals('SUBMITTED', $checklist->status);
        $this->assertEquals($this->mouldLeaderUser->id, $checklist->issued_by_id);
        $this->assertNull($checklist->checked_by_id);
        $this->assertNull($checklist->checked_at);
    }

    public function test_non_leader_cannot_auto_sign_check_by(): void
    {
        $this->actingAs($this->adminUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
        ]);

        $test = Livewire::test(MouldChecklistForm::class)
            ->assertSet('auto_sign_check_by', false)
            ->assertDontSee('Otomatis tandatangani')
            ->set('auto_sign_check_by', true) // Even if component property is somehow altered
            ->call('selectMould', $mould->id)
            ->set('technician_name', 'Admin Teknisi')
            ->call('submitAndSign');

        $test->assertRedirect();

        $checklist = MouldChecklist::first();
        $this->assertNotNull($checklist);
        // Admin role is not MOULD-MAINTENANCE-LEADER, so auto-sign checked_by is ignored
        $this->assertEquals('SUBMITTED', $checklist->status);
        $this->assertEquals($this->adminUser->id, $checklist->issued_by_id);
        $this->assertNull($checklist->checked_by_id);
    }

    public function test_mould_maintenance_leader_can_approve_submitted_checklist_in_show(): void
    {
        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi B',
            'status' => 'SUBMITTED',
            'issued_by_id' => $this->operatorUser->id,
            'issued_name' => $this->operatorUser->name,
            'issued_at' => now(),
        ]);

        $this->actingAs($this->mouldLeaderUser);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('approveAndSign')
            ->assertHasNoErrors();

        $checklist->refresh();
        $this->assertEquals('APPROVED', $checklist->status);
        $this->assertEquals($this->mouldLeaderUser->id, $checklist->checked_by_id);
        $this->assertEquals($this->mouldLeaderUser->name, $checklist->checked_name);
        $this->assertNotNull($checklist->checked_at);
    }

    public function test_user_can_delete_draft_checklist(): void
    {
        $this->actingAs($this->operatorUser);

        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi A',
            'status' => 'DRAFT',
            'created_by' => $this->operatorUser->id,
        ]);

        $checklist->details()->create([
            'item_no' => 1,
            'point_check' => 'COOLING CIRCULATION',
            'cleaning_status' => 'OK',
            'lubricate_status' => 'OK',
        ]);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('deleteChecklist')
            ->assertRedirect(route('mould-checklist.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mould_checklists', ['id' => $checklist->id]);
        $this->assertDatabaseMissing('mould_checklist_details', ['mould_checklist_id' => $checklist->id]);
    }

    public function test_non_admin_cannot_delete_submitted_or_approved_checklist(): void
    {
        $this->actingAs($this->operatorUser);

        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi A',
            'status' => 'SUBMITTED',
            'created_by' => $this->operatorUser->id,
        ]);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('deleteChecklist')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('mould_checklists', ['id' => $checklist->id]);
    }

    public function test_admin_can_delete_checklist_in_any_status(): void
    {
        $this->actingAs($this->adminUser);

        $checklist = MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi A',
            'status' => 'APPROVED',
            'created_by' => $this->operatorUser->id,
        ]);

        Livewire::test(MouldChecklistShow::class, ['id' => $checklist->id])
            ->call('deleteChecklist')
            ->assertRedirect(route('mould-checklist.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mould_checklists', ['id' => $checklist->id]);
    }
}
