<?php

namespace Tests\Feature;

use App\Livewire\Admin\BranchDepartmentManager;
use App\Livewire\Admin\UserRoleManager;
use App\Models\Branch;
use App\Models\DailyItemCode;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\PlantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BranchDepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $operatorRole;

    protected User $superAdmin;

    protected User $standardUser;

    protected Branch $jktBranch;

    protected Branch $krwBranch;

    protected Department $prodDept;

    protected Department $qcDept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        $this->jktBranch = Branch::create([
            'code' => 'JKT',
            'name' => 'Jakarta Plant',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->krwBranch = Branch::create([
            'code' => 'KRW',
            'name' => 'Karawang Plant',
            'is_main' => false,
            'is_active' => true,
        ]);

        $this->prodDept = Department::create([
            'code' => 'PROD',
            'name' => 'Production',
            'is_active' => true,
        ]);

        $this->qcDept = Department::create([
            'code' => 'QC',
            'name' => 'Quality Control',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make('secret123'),
            'role_id' => $this->superAdminRole->id,
            'branch_id' => $this->jktBranch->id,
            'department_id' => $this->prodDept->id,
            'is_active' => true,
        ]);

        $this->standardUser = User::create([
            'name' => 'Standard Operator',
            'email' => 'operator@example.com',
            'username' => 'opuser',
            'password' => Hash::make('secret123'),
            'role_id' => $this->operatorRole->id,
            'branch_id' => $this->krwBranch->id,
            'department_id' => $this->qcDept->id,
            'is_active' => true,
        ]);
    }

    public function test_branch_and_department_relationships_on_user(): void
    {
        $this->assertEquals('Jakarta Plant', $this->superAdmin->branch->name);
        $this->assertEquals('Production', $this->superAdmin->department->name);

        $this->assertEquals('Karawang Plant', $this->standardUser->branch->name);
        $this->assertEquals('Quality Control', $this->standardUser->department->name);
    }

    public function test_plant_context_service_switching_rules(): void
    {
        $service = app(PlantContextService::class);

        // Standard user cannot switch and always returns assigned branch
        $this->assertFalse($service->canSwitchBranch($this->standardUser));
        $this->assertEquals($this->krwBranch->id, $service->getEffectiveBranchId($this->standardUser));
        $this->assertFalse($service->setEffectiveBranch($this->jktBranch->id, $this->standardUser));

        // Super Admin can switch
        $this->assertTrue($service->canSwitchBranch($this->superAdmin));

        // Switch to Karawang
        $this->actingAs($this->superAdmin);
        $this->assertTrue($service->setEffectiveBranch($this->krwBranch->id, $this->superAdmin));
        $this->assertEquals($this->krwBranch->id, $service->getEffectiveBranchId($this->superAdmin));
        $this->assertFalse($service->isAllPlants($this->superAdmin));

        // Switch to All Plants
        $this->assertTrue($service->setEffectiveBranch('ALL', $this->superAdmin));
        $this->assertNull($service->getEffectiveBranchId($this->superAdmin));
        $this->assertTrue($service->isAllPlants($this->superAdmin));
        $this->assertEquals('All Plants', $service->getActivePlantLabel($this->superAdmin));
    }

    public function test_belongs_to_branch_trait_scoping_and_legacy_compatibility(): void
    {
        $service = app(PlantContextService::class);

        // 1. Legacy record without branch_id (NULL)
        $legacyDic = DailyItemCode::create([
            'user_id' => $this->superAdmin->id,
            'item_code' => 'LEGACY-PART-01',
            'quantity' => 100,
            'actual_quantity' => 100,
            'shift' => 1,
            'start_date' => '2026-09-10',
            'start_time' => '07:30:00',
            'end_date' => '2026-09-10',
            'end_time' => '15:30:00',
            'branch_id' => null,
        ]);

        // 2. Explicit Karawang record
        $krwDic = DailyItemCode::create([
            'user_id' => $this->standardUser->id,
            'item_code' => 'KRW-PART-01',
            'quantity' => 100,
            'actual_quantity' => 100,
            'shift' => 1,
            'start_date' => '2026-09-10',
            'start_time' => '07:30:00',
            'end_date' => '2026-09-10',
            'end_time' => '15:30:00',
            'branch_id' => $this->krwBranch->id,
        ]);

        // 3. Explicit Jakarta record
        $jktDic = DailyItemCode::create([
            'user_id' => $this->superAdmin->id,
            'item_code' => 'JKT-PART-01',
            'quantity' => 100,
            'actual_quantity' => 100,
            'shift' => 1,
            'start_date' => '2026-09-10',
            'start_time' => '07:30:00',
            'end_date' => '2026-09-10',
            'end_time' => '15:30:00',
            'branch_id' => $this->jktBranch->id,
        ]);

        // When standard user (locked to Karawang) queries:
        $this->actingAs($this->standardUser);
        $results = DailyItemCode::pluck('item_code')->toArray();
        // Must see legacy (NULL) and Karawang records, but NOT Jakarta records
        $this->assertContains('LEGACY-PART-01', $results);
        $this->assertContains('KRW-PART-01', $results);
        $this->assertNotContains('JKT-PART-01', $results);

        // When superAdmin sets All Plants:
        $this->actingAs($this->superAdmin);
        $service->setEffectiveBranch('ALL', $this->superAdmin);
        $allResults = DailyItemCode::pluck('item_code')->toArray();
        $this->assertContains('LEGACY-PART-01', $allResults);
        $this->assertContains('KRW-PART-01', $allResults);
        $this->assertContains('JKT-PART-01', $allResults);
    }

    public function test_user_role_manager_can_create_and_reassign_branch_and_department(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserRoleManager::class)
            ->set('name', 'New Staff User')
            ->set('email', 'newstaff@test.com')
            ->set('username', 'newstaff')
            ->set('password', 'secret1234')
            ->set('role_id', $this->operatorRole->id)
            ->set('branch_id', $this->krwBranch->id)
            ->set('department_id', $this->prodDept->id)
            ->call('createUser');

        $created = User::where('email', 'newstaff@test.com')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->krwBranch->id, $created->branch_id);
        $this->assertEquals($this->prodDept->id, $created->department_id);

        // Now test inline changeBranch and changeDepartment
        Livewire::test(UserRoleManager::class)
            ->call('changeBranch', $created->id, $this->jktBranch->id)
            ->call('changeDepartment', $created->id, $this->qcDept->id);

        $created->refresh();
        $this->assertEquals($this->jktBranch->id, $created->branch_id);
        $this->assertEquals($this->qcDept->id, $created->department_id);
    }

    public function test_branch_department_manager_crud(): void
    {
        $this->actingAs($this->superAdmin);

        // Test creating branch
        Livewire::test(BranchDepartmentManager::class)
            ->call('openBranchModal')
            ->set('branchCode', 'SBY')
            ->set('branchName', 'Surabaya Plant')
            ->set('branchAddress', 'Rungkut Industri')
            ->call('saveBranch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('branches', ['code' => 'SBY', 'name' => 'Surabaya Plant']);

        // Test creating department
        Livewire::test(BranchDepartmentManager::class)
            ->call('openDepartmentModal')
            ->set('departmentCode', 'ENG')
            ->set('departmentName', 'Engineering')
            ->set('departmentDescription', 'Product Engineering and Design')
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', ['code' => 'ENG', 'name' => 'Engineering']);
    }

    public function test_split_gates_authorization_and_access_control(): void
    {
        // 1. Super Admin has permissions
        $this->actingAs($this->superAdmin);
        $this->assertTrue(Gate::allows('manage-users-roles'));
        $this->assertTrue(Gate::allows('manage-branches-departments'));

        $this->get(route('admin.user-role-manager'))->assertOk();
        $this->get(route('admin.user-role-manager', ['tab' => 'roles']))->assertOk();
        $this->get(route('admin.branches-departments'))
            ->assertOk()
            ->assertViewIs('admin.branches_departments.index')
            ->assertSeeLivewire('admin.branch-department-manager');

        // 2. Standard User is denied access
        $this->actingAs($this->standardUser);
        $this->assertFalse(Gate::allows('manage-users-roles'));
        $this->assertFalse(Gate::allows('manage-branches-departments'));

        $this->get(route('admin.user-role-manager'))->assertForbidden();
        $this->get(route('admin.user-role-manager', ['tab' => 'roles']))->assertForbidden();
        $this->get(route('admin.branches-departments'))->assertForbidden();
    }
}
