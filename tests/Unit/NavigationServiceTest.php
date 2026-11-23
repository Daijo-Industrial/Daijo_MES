<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Services\NavigationService;
use App\Services\PlantContextService;
use Mockery;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    private NavigationService $service;
    private $plantContextMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plantContextMock = Mockery::mock(PlantContextService::class);
        $this->plantContextMock->shouldReceive('supportsSecondProcess')->andReturn(true)->byDefault();
        $this->service = new NavigationService($this->plantContextMock);
    }

    public function test_get_menu_groups_returns_empty_array_for_null_user(): void
    {
        $this->assertSame([], $this->service->getMenuGroups(null));
    }

    public function test_user_can_access_evaluates_when_callbacks(): void
    {
        $user = new User();

        $allowedItem = ['when' => fn () => true];
        $deniedItem = ['when' => fn () => false];

        $this->assertTrue($this->service->userCanAccess($user, $allowedItem));
        $this->assertFalse($this->service->userCanAccess($user, $deniedItem));
    }

    public function test_user_can_access_respects_roles(): void
    {
        $user = new User();
        $role = new Role();
        $role->name = 'ADMIN';
        $user->setRelation('role', $role);

        $adminItem = ['roles' => ['ADMIN', 'SUPER-ADMIN']];
        $storeOnlyItem = ['roles' => ['STORE', 'WAREHOUSE']];

        $this->assertTrue($this->service->userCanAccess($user, $adminItem));
        $this->assertFalse($this->service->userCanAccess($user, $storeOnlyItem));
    }

    public function test_navigation_schema_includes_moulding_bom_dashboard(): void
    {
        $schema = $this->service->navigationSchema();

        $dashboardGroup = collect($schema)->firstWhere('label', 'Dashboard All');
        $this->assertNotNull($dashboardGroup);

        $mouldingBomItem = collect($dashboardGroup['children'] ?? [])->firstWhere('name', 'dashboard.moulding-bom');
        $this->assertNotNull($mouldingBomItem);
        $this->assertSame('Dashboard Moulding BOM Progress', $mouldingBomItem['label']);
    }

    public function test_get_menu_groups_includes_standalone_when_requested(): void
    {
        $user = new User();
        $role = new Role();
        $role->name = 'SUPER-ADMIN';
        $user->setRelation('role', $role);

        // Sidebar mode (standalone excluded)
        $sidebarGroups = $this->service->getMenuGroups($user, false);
        $this->assertNull(collect($sidebarGroups)->firstWhere('label', 'System Administration'));

        // Dashboard Hub mode (standalone included)
        $hubGroups = $this->service->getMenuGroups($user, true);
        $this->assertNotNull(collect($hubGroups)->firstWhere('label', 'System Administration'));
    }

    public function test_plant_context_filters_second_process_when_unsupported(): void
    {
        $mock = Mockery::mock(PlantContextService::class);
        $mock->shouldReceive('supportsSecondProcess')->andReturn(false);

        $service = new NavigationService($mock);

        $user = new User();
        $role = new Role();
        $role->name = 'ADMIN';
        $user->setRelation('role', $role);

        $groups = $service->getMenuGroups($user, false);
        $secondProcessGroup = collect($groups)->firstWhere('label', 'Second Process');

        $this->assertNull($secondProcessGroup);
    }
}
