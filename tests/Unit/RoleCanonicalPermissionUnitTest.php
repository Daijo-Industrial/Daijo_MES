<?php

namespace Tests\Unit;

use App\Models\Role;
use PHPUnit\Framework\TestCase;

class RoleCanonicalPermissionUnitTest extends TestCase
{
    public function test_canonicalize_permission_name_normalizes_legacy_aliases(): void
    {
        $this->assertEquals('second-process-work-orders', Role::canonicalizePermissionName('view-second-process-work-orders'));
        $this->assertEquals('second-process-work-orders', Role::canonicalizePermissionName('manage-sp-work-orders'));
        $this->assertEquals('second-process-reports', Role::canonicalizePermissionName('view-second-process-reports'));
        $this->assertEquals('second-process-reports', Role::canonicalizePermissionName('manage-second-process-reports'));
        $this->assertEquals('second-process-approvals', Role::canonicalizePermissionName('view-second-process-approvals'));
        $this->assertEquals('second-process-approvals', Role::canonicalizePermissionName('approve-sp-sessions'));
        $this->assertEquals('second-process-dashboard', Role::canonicalizePermissionName('view-second-process-dashboard'));
        $this->assertEquals('second-process-dashboard', Role::canonicalizePermissionName('view-second-process-ops'));

        // Unknown or custom permission passes through unchanged
        $this->assertEquals('custom-feature-xyz', Role::canonicalizePermissionName('custom-feature-xyz'));
    }

    public function test_is_protected_protects_super_admin_and_admin(): void
    {
        $superAdmin = new Role(['name' => 'SUPER-ADMIN']);
        $this->assertTrue($superAdmin->isProtected());

        $admin = new Role(['name' => 'ADMIN']);
        $this->assertTrue($admin->isProtected());

        $operator = new Role(['name' => 'OPERATOR']);
        $this->assertFalse($operator->isProtected());

        $leader = new Role(['name' => 'LEADER']);
        $this->assertFalse($leader->isProtected());
    }
}
