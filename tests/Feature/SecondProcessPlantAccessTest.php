<?php

namespace Tests\Feature;

use App\Livewire\Admin\BranchDepartmentManager;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecondProcessPlantAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected Role $spRole;

    protected Role $ppicRole;

    protected Role $prodRole;

    protected Branch $jakartaBranch;

    protected Branch $karawangBranch;

    protected Department $spDept;

    protected Department $ppicDept;

    protected Department $prodDept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->spRole = Role::firstOrCreate(['name' => 'SECONDPROCESS']);
        $this->ppicRole = Role::firstOrCreate(['name' => 'PPIC']);
        $this->prodRole = Role::firstOrCreate(['name' => 'PRODUCTION']);

        $this->jakartaBranch = Branch::firstOrCreate(
            ['code' => 'JKT'],
            [
                'name' => 'Jakarta Plant',
                'address' => 'KBN Cakung',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $this->karawangBranch = Branch::firstOrCreate(
            ['code' => 'KRW'],
            [
                'name' => 'Karawang Plant',
                'address' => 'KIIC Karawang',
                'is_main' => false,
                'is_active' => true,
            ]
        );

        $this->spDept = Department::firstOrCreate(
            ['code' => 'SP'],
            [
                'name' => 'Second Process',
                'is_active' => true,
            ]
        );

        $this->ppicDept = Department::firstOrCreate(
            ['code' => 'PPIC'],
            [
                'name' => 'PPIC',
                'is_active' => true,
            ]
        );

        $this->prodDept = Department::firstOrCreate(
            ['code' => 'PROD'],
            [
                'name' => 'Production',
                'is_active' => true,
            ]
        );

        // Jakarta has SP, PPIC, PROD
        $this->jakartaBranch->departments()->syncWithoutDetaching([$this->spDept->id, $this->ppicDept->id, $this->prodDept->id]);

        // Karawang has PPIC, PROD (NO SP)
        $this->karawangBranch->departments()->syncWithoutDetaching([$this->ppicDept->id, $this->prodDept->id]);
    }

    /**
     * SUPER-ADMIN has unrestricted access as-is.
     */
    public function test_super_admin_has_unrestricted_access_to_sp_routes(): void
    {
        $superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
            'branch_id' => $this->karawangBranch->id, // Even if assigned to Karawang
        ]);

        $response = $this->actingAs($superAdmin)->get(route('sp-work-orders.index'));
        $response->assertOk();

        $response = $this->actingAs($superAdmin)->get(route('second-process-reports.index'));
        $response->assertOk();
    }

    /**
     * Second Process staff at Jakarta Plant can access Work Orders and Reports.
     */
    public function test_second_process_user_in_jakarta_can_access_sp_ops_and_reports(): void
    {
        $spUser = User::factory()->create([
            'role_id' => $this->spRole->id,
            'branch_id' => $this->jakartaBranch->id,
            'department_id' => $this->spDept->id,
        ]);

        $response = $this->actingAs($spUser)->get(route('sp-work-orders.index'));
        $response->assertOk();

        $response = $this->actingAs($spUser)->get(route('second-process-reports.index'));
        $response->assertOk();
    }

    /**
     * PPIC user at Jakarta Plant has cross-department access to Reports & Analytics.
     */
    public function test_ppic_user_in_jakarta_can_access_sp_reports_cross_department(): void
    {
        $ppicUser = User::factory()->create([
            'role_id' => $this->ppicRole->id,
            'branch_id' => $this->jakartaBranch->id,
            'department_id' => $this->ppicDept->id,
        ]);

        // Access to reports is allowed for PPIC
        $response = $this->actingAs($ppicUser)->get(route('second-process-reports.index'));
        $response->assertOk();

        // Access to report analytics is allowed for PPIC
        $response = $this->actingAs($ppicUser)->get(route('second-process.report-analytics'));
        $response->assertOk();
    }

    /**
     * PPIC user at Jakarta Plant is forbidden from shop floor ops (Work Orders).
     */
    public function test_ppic_user_in_jakarta_is_forbidden_from_sp_work_orders(): void
    {
        $ppicUser = User::factory()->create([
            'role_id' => $this->ppicRole->id,
            'branch_id' => $this->jakartaBranch->id,
            'department_id' => $this->ppicDept->id,
        ]);

        $response = $this->actingAs($ppicUser)->get(route('sp-work-orders.index'));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * User at Karawang Plant is forbidden from all Second Process routes because SP is not operated at Karawang.
     */
    public function test_karawang_user_is_forbidden_from_all_sp_routes(): void
    {
        $krwUser = User::factory()->create([
            'role_id' => $this->spRole->id, // Even if role is SP
            'branch_id' => $this->karawangBranch->id,
            'department_id' => $this->spDept->id,
        ]);

        $response = $this->actingAs($krwUser)->get(route('sp-work-orders.index'));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $response = $this->actingAs($krwUser)->get(route('second-process-reports.index'));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Legacy user with null department and Admin role retains access (zero regression).
     */
    public function test_legacy_user_with_null_department_and_admin_role_retains_access(): void
    {
        $legacyAdmin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'branch_id' => null,
            'department_id' => null,
        ]);

        $response = $this->actingAs($legacyAdmin)->get(route('second-process-reports.index'));
        $response->assertOk();

        $response = $this->actingAs($legacyAdmin)->get(route('sp-work-orders.index'));
        $response->assertOk();
    }

    /**
     * JSON requests in forbidden context receive a clean 403 JSON response.
     */
    public function test_json_request_in_karawang_receives_json_403(): void
    {
        $krwUser = User::factory()->create([
            'role_id' => $this->prodRole->id,
            'branch_id' => $this->karawangBranch->id,
            'department_id' => $this->prodDept->id,
        ]);

        $response = $this->actingAs($krwUser)
            ->getJson(route('second-process-reports.index'));

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    }

    /**
     * SUPER-ADMIN can toggle department mapping for a branch via Livewire.
     */
    public function test_super_admin_can_toggle_department_for_branch_via_livewire(): void
    {
        $superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
        ]);

        $this->assertTrue($this->jakartaBranch->departments()->where('departments.id', $this->spDept->id)->exists());

        // Toggle SP off for Jakarta
        Livewire::actingAs($superAdmin)
            ->test(BranchDepartmentManager::class)
            ->call('toggleDepartmentForBranch', $this->jakartaBranch->id, $this->spDept->id);

        $this->assertFalse($this->jakartaBranch->fresh()->departments()->where('departments.id', $this->spDept->id)->exists());

        // Toggle SP on for Karawang
        Livewire::actingAs($superAdmin)
            ->test(BranchDepartmentManager::class)
            ->call('toggleDepartmentForBranch', $this->karawangBranch->id, $this->spDept->id);

        $this->assertTrue($this->karawangBranch->fresh()->departments()->where('departments.id', $this->spDept->id)->exists());
    }
}
