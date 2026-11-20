<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\User;
use Tests\TestCase;

class SecondProcessReportUnitTest extends TestCase
{
    public function test_is_user_authorized_to_sign_returns_false_for_null_user(): void
    {
        $this->assertFalse(SecondProcessReport::isUserAuthorizedToSign(null, 'leader'));
    }

    public function test_is_user_authorized_to_sign_allows_super_admin(): void
    {
        $superAdmin = new User();
        $role = new Role(['name' => 'SUPER-ADMIN']);
        $superAdmin->setRelation('role', $role);

        $this->assertTrue(SecondProcessReport::isUserAuthorizedToSign($superAdmin, 'leader'));
        $this->assertTrue(SecondProcessReport::isUserAuthorizedToSign($superAdmin, 'pqc'));
        $this->assertTrue(SecondProcessReport::isUserAuthorizedToSign($superAdmin, 'spv'));
    }

    public function test_is_user_authorized_to_sign_respects_role_mapping(): void
    {
        $checker = new User();
        $roleChecker = new Role(['name' => 'CHECKER']);
        $checker->setRelation('role', $roleChecker);

        // Checker can sign as checker/created_by but not as spv/manager
        $this->assertTrue(SecondProcessReport::isUserAuthorizedToSign($checker, 'checker'));
        $this->assertFalse(SecondProcessReport::isUserAuthorizedToSign($checker, 'spv'));
    }
}

