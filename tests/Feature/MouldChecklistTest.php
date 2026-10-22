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

    protected Role $adminRole;
    protected Role $leaderRole;
    protected Role $operatorRole;

    protected User $adminUser;
    protected User $leaderUser;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->leaderRole = Role::create(['name' => 'LEADER']);
        $this->operatorRole = Role::create(['name' => 'OPERATOR']);

        $this->adminUser = User::factory()->create(['name' => 'Admin User', 'role_id' => $this->adminRole->id]);
        $this->leaderUser = User::factory()->create(['name' => 'Leader User', 'role_id' => $this->leaderRole->id]);
        $this->operatorUser = User::factory()->create(['name' => 'Operator User', 'role_id' => $this->operatorRole->id]);
    }

    public function test_master_mould_can_be_created_and_managed(): void
    {
        $this->actingAs($this->adminUser);

        $mould = MasterMould::create([
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'model' => '5H45',
            'customer' => 'IKUYO',
            'machine_number' => '1300T',
            'material' => 'PP-KF06 LD',
            'is_active' => true,
        ]);

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

        $this->actingAs($this->adminUser);

        MouldChecklist::create([
            'check_date' => now()->format('Y-m-d'),
            'mould_no' => 'IK-MM-005-010',
            'part_no' => '401-76934B001P',
            'part_name' => 'GARNISH RR PLR RH 5H45',
            'customer' => 'IKUYO',
            'technician_name' => 'Teknisi A',
            'status' => 'APPROVED',
        ]);

        Livewire::test(MouldChecklistIndex::class)
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
}
