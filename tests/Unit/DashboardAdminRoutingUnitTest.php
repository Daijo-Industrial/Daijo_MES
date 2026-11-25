<?php

namespace Tests\Unit;

use App\Services\NavigationService;
use Tests\TestCase;

class DashboardAdminRoutingUnitTest extends TestCase
{
    public function test_dashboard_admin_view_renders_production_dashboard(): void
    {
        $content = file_get_contents(resource_path('views/dashboards/dashboard-admin.blade.php'));

        $this->assertStringContainsString('livewire:production-dashboard', $content);
        $this->assertStringNotContainsString('livewire:dashboard-admin', $content);
    }

    public function test_generic_dashboard_view_renders_daijo_logo_for_non_admins(): void
    {
        $content = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringContainsString('x-application-logo', $content);
        $this->assertStringContainsString('PT Daijo Industrial', $content);
        $this->assertStringContainsString('Manufacturing Execution System', $content);
    }

    public function test_sidebar_and_navigation_include_production_dashboard(): void
    {
        $navService = app(NavigationService::class);
        $schema = $navService->navigationSchema();
        $dashboardGroup = collect($schema)->firstWhere('label', 'Dashboard All');

        $this->assertNotNull($dashboardGroup);
        $this->assertNotNull(collect($dashboardGroup['children'])->firstWhere('name', 'production-dashboard'));
        $this->assertNotNull(collect($dashboardGroup['children'])->firstWhere('name', 'production-yearly-dashboard'));

        $sidebarContent = file_get_contents(resource_path('views/livewire/layout/sidebar.blade.php'));
        $this->assertStringContainsString("request()->routeIs('production-dashboard')", $sidebarContent);
        $this->assertStringContainsString("request()->routeIs('production-yearly-dashboard')", $sidebarContent);
    }
}
