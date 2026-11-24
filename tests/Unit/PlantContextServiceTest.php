<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\PlantContextService;
use Tests\TestCase;

class PlantContextServiceTest extends TestCase
{
    private PlantContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlantContextService;
    }

    public function test_can_switch_branch_returns_false_for_guest(): void
    {
        $this->assertFalse($this->service->canSwitchBranch(null));
    }

    public function test_can_switch_branch_returns_true_for_super_admin(): void
    {
        $superAdmin = new User;
        $superAdmin->id = 1;
        $role = new Role;
        $role->name = 'SUPER-ADMIN';
        $superAdmin->setRelation('role', $role);

        $this->assertTrue($this->service->canSwitchBranch($superAdmin));
    }

    public function test_can_switch_branch_returns_false_for_regular_operator(): void
    {
        $operator = new User;
        $operator->id = 2;
        $role = new Role;
        $role->name = 'OPERATOR';
        $operator->setRelation('role', $role);

        $this->assertFalse($this->service->canSwitchBranch($operator));
    }

    public function test_get_effective_branch_id_returns_user_branch_for_regular_user(): void
    {
        $user = new User;
        $user->id = 3;
        $user->branch_id = 5;
        $role = new Role;
        $role->name = 'STAFF';
        $user->setRelation('role', $role);

        $effectiveId = $this->service->getEffectiveBranchId($user);
        $this->assertEquals(5, $effectiveId);
    }

    public function test_get_effective_branch_id_returns_session_override_for_super_admin(): void
    {
        $superAdmin = new User;
        $superAdmin->id = 1;
        $superAdmin->branch_id = 1;
        $role = new Role;
        $role->name = 'SUPER-ADMIN';
        $superAdmin->setRelation('role', $role);

        session(['active_branch_id' => 2]);
        $effectiveId = $this->service->getEffectiveBranchId($superAdmin);
        $this->assertEquals(2, $effectiveId);

        // When active_branch_id is ALL, returns null
        session(['active_branch_id' => PlantContextService::ALL_PLANTS]);
        $allId = $this->service->getEffectiveBranchId($superAdmin);
        $this->assertNull($allId);
    }

    public function test_is_all_plants_evaluates_correctly(): void
    {
        $superAdmin = new User;
        $superAdmin->id = 1;
        $role = new Role;
        $role->name = 'SUPER-ADMIN';
        $superAdmin->setRelation('role', $role);

        session(['active_branch_id' => PlantContextService::ALL_PLANTS]);
        $this->assertTrue($this->service->isAllPlants($superAdmin));

        session(['active_branch_id' => 2]);
        $this->assertFalse($this->service->isAllPlants($superAdmin));
    }

    public function test_get_active_plant_label_handles_guest_and_all_plants(): void
    {
        $this->assertEquals('Guest', $this->service->getActivePlantLabel(null));

        $superAdmin = new User;
        $superAdmin->id = 1;
        $role = new Role;
        $role->name = 'SUPER-ADMIN';
        $superAdmin->setRelation('role', $role);

        session(['active_branch_id' => PlantContextService::ALL_PLANTS]);
        $this->assertEquals('All Plants', $this->service->getActivePlantLabel($superAdmin));
    }
}
