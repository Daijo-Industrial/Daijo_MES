<?php

namespace Tests\Unit;

use App\Livewire\Admin\RoleManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleManagerMatrixTest extends TestCase
{
    public function test_role_manager_view_mode_toggle_and_initialization()
    {
        // Mock authorized user and gate
        $user = new User();
        $user->id = 1;
        $this->actingAs($user);
        Gate::define('manage-users-roles', fn () => true);

        $component = new RoleManager();
        $this->assertEquals('table', $component->viewMode);

        $component->setViewMode('matrix');
        $this->assertEquals('matrix', $component->viewMode);

        $component->setViewMode('table');
        $this->assertEquals('table', $component->viewMode);

        // Invalid mode fallback to table
        $component->setViewMode('invalid_mode');
        $this->assertEquals('table', $component->viewMode);

        // Mount with initial matrix mode
        $matrixComponent = new RoleManager();
        $matrixComponent->mount('matrix');
        $this->assertEquals('matrix', $matrixComponent->viewMode);
    }

    public function test_super_admin_role_cannot_be_modified_in_matrix()
    {
        Gate::before(fn () => true);

        $superAdminRole = new Role();
        $superAdminRole->id = 1;
        $superAdminRole->name = 'SUPER-ADMIN';

        $this->assertTrue($superAdminRole->isProtected());
        $this->assertTrue($superAdminRole->defaultPermissionFallback('second-process-reports'));
        $this->assertTrue($superAdminRole->defaultPermissionFallback('manage-users-roles'));
    }

    public function test_standardized_roles_matrix_state_evaluation()
    {
        $checker = new Role(['name' => 'CHECKER']);
        $leader = new Role(['name' => 'LEADER']);
        $quality = new Role(['name' => 'QUALITY']);
        $superAdmin = new Role(['name' => 'SUPER-ADMIN']);

        // Checker has reports & dashboard, not work-orders
        $this->assertTrue($checker->hasPermission('second-process-reports'));
        $this->assertTrue($checker->hasPermission('second-process-dashboard'));
        $this->assertFalse($checker->hasPermission('second-process-work-orders'));

        // Leader has work-orders, reports, approvals, dashboard
        $this->assertTrue($leader->hasPermission('second-process-reports'));
        $this->assertTrue($leader->hasPermission('second-process-work-orders'));
        $this->assertTrue($leader->hasPermission('second-process-approvals'));

        // Quality has first-piece & ipqc
        $this->assertTrue($quality->hasPermission('second-process-first-piece'));
        $this->assertTrue($quality->hasPermission('second-process-ipqc'));

        // Super-admin has all permissions
        $this->assertTrue($superAdmin->hasPermission('second-process-reports'));
        $this->assertTrue($superAdmin->hasPermission('manage-users-roles'));
        $this->assertTrue($superAdmin->hasPermission('any-custom-permission'));
    }

    public function test_role_manager_blade_view_has_single_root_element()
    {
        $roles = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $viewData = [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'viewMode' => 'table',
            'search' => '',
            'name' => '',
            'editingRoleId' => null,
            'editingRoleName' => '',
            'permissionSearch' => '',
            'matrixSearch' => '',
            'matrixGroupFilter' => '',
            'selectedPermissions' => [],
            'managingPermissionsRoleName' => 'TEST',
            'managingPermissionsRoleIsProtected' => false,
            'roles' => $roles,
            'groupedPermissions' => collect(),
            'totalAvailablePermissionsCount' => 0,
            'matrixRoles' => collect(),
            'matrixGroupedPermissions' => collect(),
            'matrixState' => [],
            'rolePermissionCounts' => [],
            'availableGroups' => [],
        ];

        // 1. Table View Root Count
        $html = view('livewire.admin.role-manager', $viewData)->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')->item(0);
        $tableRoots = 0;
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName !== 'script') {
                $tableRoots++;
            }
        }
        $this->assertEquals(1, $tableRoots, 'Table mode must have exactly 1 root element');

        // 2. Matrix View Root Count
        $viewData['viewMode'] = 'matrix';
        $html2 = view('livewire.admin.role-manager', $viewData)->render();
        $dom2 = new \DOMDocument();
        @$dom2->loadHTML($html2);
        $body2 = $dom2->getElementsByTagName('body')->item(0);
        $matrixRoots = 0;
        foreach ($body2->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName !== 'script') {
                $matrixRoots++;
            }
        }
        $this->assertEquals(1, $matrixRoots, 'Matrix mode must have exactly 1 root element');
    }

    public function test_user_role_manager_blade_view_has_single_root_element()
    {
        $users = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $viewData = [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'search' => '',
            'showDeprecated' => false,
            'username' => '',
            'name' => '',
            'email' => '',
            'password' => '',
            'role_id' => '',
            'branch_id' => null,
            'department_id' => null,
            'zone_id' => null,
            'selectedUserId' => null,
            'selectedUserName' => 'TEST USER',
            'newPassword' => '',
            'users' => $users,
            'roles' => collect(),
            'branches' => collect(),
            'departments' => collect(),
            'zones' => collect(),
        ];

        $html = view('livewire.admin.user-role-manager', $viewData)->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')->item(0);
        $roots = 0;
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName !== 'script') {
                $roots++;
            }
        }
        $this->assertEquals(1, $roots, 'user-role-manager view must have exactly 1 root element');
    }

    public function test_permission_manager_blade_view_has_single_root_element()
    {
        $permissions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $viewData = [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'search' => '',
            'groupFilter' => '',
            'editingPermissionId' => null,
            'editingName' => 'test-permission',
            'editingLabel' => 'Test Permission',
            'editingGroup' => 'General',
            'editingDescription' => 'Test description',
            'permissions' => $permissions,
            'availableGroups' => ['General', 'Second Process'],
        ];

        $html = view('livewire.admin.permission-manager', $viewData)->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')->item(0);
        $roots = 0;
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName !== 'script') {
                $roots++;
            }
        }
        $this->assertEquals(1, $roots, 'permission-manager view must have exactly 1 root element');
    }

    public function test_permission_manager_has_no_create_or_delete_capabilities()
    {
        $reflection = new \ReflectionClass(\App\Livewire\Admin\PermissionManager::class);

        // Assert that create and delete methods do not exist
        $this->assertFalse($reflection->hasMethod('createPermission'));
        $this->assertFalse($reflection->hasMethod('addPermission'));
        $this->assertFalse($reflection->hasMethod('deletePermission'));
        $this->assertFalse($reflection->hasMethod('destroy'));

        // Assert update method exists
        $this->assertTrue($reflection->hasMethod('updatePermission'));
    }
}
