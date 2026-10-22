<?php

namespace Tests\Feature;

use App\Livewire\Admin\RoleManager;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RoleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected Role $operatorRole;

    protected User $superAdmin;

    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'username' => 'superadmin',
            'password' => Hash::make('password123'),
            'role_id' => $this->superAdminRole->id,
            'is_active' => true,
        ]);

        $this->operatorUser = User::create([
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'username' => 'operator',
            'password' => Hash::make('password123'),
            'role_id' => $this->operatorRole->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_role_manager(): void
    {
        $this->actingAs($this->operatorUser);

        Livewire::test(RoleManager::class)
            ->assertStatus(403);
    }

    public function test_superadmin_can_access_role_manager(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->assertStatus(200)
            ->assertSee('System Roles')
            ->assertSee('SUPER-ADMIN')
            ->assertSee('ADMIN')
            ->assertSee('OPERATOR');
    }

    public function test_can_create_new_role(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->set('name', 'AUDITOR')
            ->call('createRole')
            ->assertHasNoErrors()
            ->assertSet('name', '')
            ->assertDispatched('close-modal', 'create-role-modal');

        $this->assertDatabaseHas('roles', [
            'name' => 'AUDITOR',
        ]);
    }

    public function test_cannot_create_duplicate_role_name(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->set('name', 'OPERATOR')
            ->call('createRole')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_cannot_create_role_with_empty_name(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->set('name', '')
            ->call('createRole')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_can_open_edit_modal_and_update_role(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'TESTER']);

        Livewire::test(RoleManager::class)
            ->call('startEditRole', $customRole->id)
            ->assertSet('editingRoleId', $customRole->id)
            ->assertSet('editingRoleName', 'TESTER')
            ->assertDispatched('open-modal', 'edit-role-modal')
            ->set('editingRoleName', 'QA-TESTER')
            ->call('updateRole')
            ->assertHasNoErrors()
            ->assertSet('editingRoleId', null)
            ->assertSet('editingRoleName', '')
            ->assertDispatched('close-modal', 'edit-role-modal');

        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id,
            'name' => 'QA-TESTER',
        ]);
    }

    public function test_cannot_update_role_to_existing_duplicate_name(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'SECURITY']);

        Livewire::test(RoleManager::class)
            ->call('startEditRole', $customRole->id)
            ->set('editingRoleName', 'OPERATOR')
            ->call('updateRole')
            ->assertHasErrors(['editingRoleName' => 'unique']);
    }

    public function test_can_delete_unused_custom_role(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'TEMPORARY']);

        Livewire::test(RoleManager::class)
            ->call('deleteRole', $customRole->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', [
            'id' => $customRole->id,
        ]);
    }

    public function test_cannot_delete_protected_roles(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->call('deleteRole', $this->superAdminRole->id);

        $this->assertDatabaseHas('roles', [
            'id' => $this->superAdminRole->id,
        ]);

        Livewire::test(RoleManager::class)
            ->call('deleteRole', $this->adminRole->id);

        $this->assertDatabaseHas('roles', [
            'id' => $this->adminRole->id,
        ]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(RoleManager::class)
            ->call('deleteRole', $this->operatorRole->id);

        $this->assertDatabaseHas('roles', [
            'id' => $this->operatorRole->id,
        ]);
    }

    public function test_search_filters_roles_by_name(): void
    {
        $this->actingAs($this->superAdmin);

        Role::create(['name' => 'INSPECTOR']);

        Livewire::test(RoleManager::class)
            ->set('search', 'INSPEC')
            ->assertSee('INSPECTOR')
            ->assertDontSee('OPERATOR');
    }

    public function test_superadmin_can_open_manage_permissions_modal(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'QC-INSPECTOR']);

        Livewire::test(RoleManager::class)
            ->call('managePermissions', $customRole->id)
            ->assertSet('managingPermissionsRoleId', $customRole->id)
            ->assertSet('managingPermissionsRoleName', 'QC-INSPECTOR')
            ->assertDispatched('open-modal', 'manage-permissions-modal');
    }

    public function test_superadmin_can_assign_permissions_to_role(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'WAREHOUSE-CLERK']);
        $warehousePerm = \App\Models\Permission::where('name', 'view-warehouse-links')->firstOrFail();
        $storePerm = \App\Models\Permission::where('name', 'view-store-links')->firstOrFail();

        Livewire::test(RoleManager::class)
            ->call('managePermissions', $customRole->id)
            ->set('selectedPermissions', [$warehousePerm->id, $storePerm->id])
            ->call('saveRolePermissions')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'manage-permissions-modal');

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $customRole->id,
            'permission_id' => $warehousePerm->id,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $customRole->id,
            'permission_id' => $storePerm->id,
        ]);

        $customRole->refresh();
        $this->assertTrue($customRole->hasPermission('view-warehouse-links'));
        $this->assertTrue($customRole->hasPermission('view-store-links'));
        $this->assertFalse($customRole->hasPermission('view-operator-links'));
    }

    public function test_superadmin_can_unassign_permission_from_role(): void
    {
        $this->actingAs($this->superAdmin);

        $customRole = Role::create(['name' => 'AUDIT-LEAD']);
        $qcPerm = \App\Models\Permission::where('name', 'execute-qc-inspections')->firstOrFail();
        $spPerm = \App\Models\Permission::where('name', 'second-process-work-orders')->firstOrFail();

        $customRole->syncPermissions([$qcPerm->id, $spPerm->id]);
        $this->assertTrue($customRole->fresh()->hasPermission('execute-qc-inspections'));

        // Unassign execute-qc-inspections
        Livewire::test(RoleManager::class)
            ->call('managePermissions', $customRole->id)
            ->set('selectedPermissions', [$spPerm->id])
            ->call('saveRolePermissions')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $customRole->id,
            'permission_id' => $qcPerm->id,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $customRole->id,
            'permission_id' => $spPerm->id,
        ]);

        $customRole->refresh();
        $this->assertFalse($customRole->hasPermission('execute-qc-inspections'));
        $this->assertTrue($customRole->hasPermission('second-process-work-orders'));
    }

    public function test_assigned_permission_grants_gate_access_and_unassigned_denies_gate_access(): void
    {
        $customRole = Role::create(['name' => 'DISPATCHER']);
        $warehousePerm = \App\Models\Permission::where('name', 'view-warehouse-links')->firstOrFail();

        $user = User::create([
            'name' => 'Dispatcher User',
            'email' => 'dispatcher@test.com',
            'username' => 'dispatcher',
            'password' => Hash::make('password123'),
            'role_id' => $customRole->id,
            'is_active' => true,
        ]);

        // Initially no permissions assigned
        $customRole->syncPermissions([]);
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($user->fresh())->allows('view-warehouse-links'));

        // Assign permission
        $customRole->syncPermissions([$warehousePerm->id]);
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($user->fresh())->allows('view-warehouse-links'));

        // Unassign permission
        $customRole->syncPermissions([]);
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($user->fresh())->allows('view-warehouse-links'));
    }

    public function test_select_all_and_deselect_all_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $totalPermsCount = \App\Models\Permission::count();

        $component = Livewire::test(RoleManager::class)
            ->call('selectAllPermissions');

        $selected = $component->get('selectedPermissions');
        $this->assertCount($totalPermsCount, $selected);

        $component->call('deselectAllPermissions');
        $this->assertEmpty($component->get('selectedPermissions'));
    }

    public function test_toggle_group_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $warehousePermCount = \App\Models\Permission::where('group', 'Warehouse & Inventory')->count();

        $component = Livewire::test(RoleManager::class)
            ->call('toggleGroupPermissions', 'Warehouse & Inventory');

        $this->assertCount($warehousePermCount, $component->get('selectedPermissions'));

        // Toggle again should deselect
        $component->call('toggleGroupPermissions', 'Warehouse & Inventory');
        $this->assertEmpty($component->get('selectedPermissions'));
    }

    public function test_granular_second_process_permissions_isolation(): void
    {
        $customRole = Role::create(['name' => 'SP-ANALYST']);
        $analyticsPerm = \App\Models\Permission::where('name', 'second-process-analytics')->firstOrFail();
        $workOrdersPerm = \App\Models\Permission::where('name', 'second-process-work-orders')->firstOrFail();
        $reportsPerm = \App\Models\Permission::where('name', 'second-process-reports')->firstOrFail();

        $user = User::create([
            'name' => 'SP Analyst User',
            'email' => 'spanalyst@test.com',
            'username' => 'spanalyst',
            'password' => Hash::make('password123'),
            'role_id' => $customRole->id,
            'is_active' => true,
        ]);

        // Assign ONLY analytics
        $customRole->syncPermissions([$analyticsPerm->id]);
        $refreshedUser = $user->fresh();

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-analytics'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-reports'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-work-orders'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-dashboard'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-first-piece'));

        // Assign ONLY reports: unlocks both navigation gate and feature access
        $customRole->syncPermissions([$reportsPerm->id]);
        $refreshedUser = $user->fresh();

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-reports'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('view-second-process-reports'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('manage-second-process-reports'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-work-orders'));

        // Switch to ONLY work orders
        $customRole->syncPermissions([$workOrdersPerm->id]);
        $refreshedUser = $user->fresh();

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-analytics'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-reports'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('second-process-work-orders'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($refreshedUser)->allows('manage-sp-work-orders'));
    }
}
