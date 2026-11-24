<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UserAuthorizationUnitTest extends TestCase
{
    public function test_has_role_matches_case_insensitively(): void
    {
        $user = new User;
        $role = new Role(['name' => 'SUPER-ADMIN']);
        $user->setRelation('role', $role);

        $this->assertTrue($user->hasRole('SUPER-ADMIN'));
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->hasRole('Super-Admin'));
        $this->assertFalse($user->hasRole('OPERATOR'));
    }

    public function test_has_role_access_allows_super_admin_for_any_role(): void
    {
        $superAdmin = new User;
        $role = new Role(['name' => 'SUPER-ADMIN']);
        $superAdmin->setRelation('role', $role);

        $this->assertTrue($superAdmin->hasRoleAccess('OPERATOR'));
        $this->assertTrue($superAdmin->hasRoleAccess('DEPT-HEAD'));
        $this->assertTrue($superAdmin->hasRoleAccess('ANY_CUSTOM_ROLE'));
    }

    public function test_has_role_access_returns_false_when_user_has_no_role(): void
    {
        $user = new User;
        $this->assertFalse($user->hasRoleAccess('OPERATOR'));
    }

    public function test_has_permission_grants_all_to_super_admin(): void
    {
        $superAdmin = new User;
        $role = new Role(['name' => 'SUPER-ADMIN']);
        $superAdmin->setRelation('role', $role);

        $this->assertTrue($superAdmin->hasPermission('any-random-permission'));
        $this->assertTrue($superAdmin->hasPermission('delete-production-data'));
    }

    public function test_has_permission_checks_configured_role_permissions(): void
    {
        $user = new User;
        $role = new Role([
            'name' => 'STAFF',
            'permissions_configured' => true,
        ]);

        $perm = new Permission(['name' => 'second-process-reports']);
        $role->setRelation('permissions', collect([$perm]));
        $user->setRelation('role', $role);

        $this->assertTrue($user->hasPermission('second-process-reports'));
        $this->assertFalse($user->hasPermission('second-process-approvals'));
    }

    public function test_can_sign_grants_super_admin_and_admin_immediately(): void
    {
        $superAdmin = new User;
        $role = new Role(['name' => 'SUPER-ADMIN']);
        $superAdmin->setRelation('role', $role);

        $this->assertTrue($superAdmin->canSign('second_process', 'spv'));

        $admin = new User;
        $adminRole = new Role(['name' => 'ADMIN']);
        $admin->setRelation('role', $adminRole);

        $this->assertTrue($admin->canSign('second_process', 'leader'));
    }
}
