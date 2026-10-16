<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModularRoutesVerificationTest extends TestCase
{
    /**
     * Test that all critical named routes across all domain modules exist.
     */
    public function test_all_critical_named_routes_exist(): void
    {
        $expectedRoutes = [
            // Auth & Core
            'login',
            'register',
            'profile',

            // Second Process Domain
            'second-process-reports.index',
            'second-process-reports.create',
            'second-process-reports.store',
            'second-process-reports.edit',
            'second-process-reports.update',
            'second-process-reports.destroy',
            'second-process-reports.sign',
            'second-process-reports.reject',
            'second-process-reports.search-items',
            'second-process-reports.search-customers',
            'second-process.dashboard',
            'second-process.line-dashboard',
            'second-process.report-analytics',
            'first-piece-inspections.index',
            'ipqc-inspections.index',
            'sp-approvals.index',
            'sp-work-orders.index',
            'sp-sessions.start',

            // WMS & Warehouse Domain
            'wms.dashboard',
            'wms.mapping',
            'wms.pallet-form.index',
            'wms.pallet-form.create-delivery',
            'wms.pallet-form.daily-recap',
            'wms.pallet-form.picking-guide',
            'wms.pallet-form.print',
            'wms.sap-sync-monitor',
            'mwh.mapping',
            'mwh.pallets.index',
            'mwh.fifo-dashboard',
            'mwh.daily-audit',
            'so.tracking',
            'so.index',
            'so.process',

            // Production & Dashboards Domain
            'production-dashboard',
            'machine.dashboard',
            'mould.dashboard',
            'machine.monitor',
            'spk.changes.index',
            'spk.bom-changes.index',
            'master-bom.index',
            'master-list-item',
            'production-summary-monitor',
            'receipt-production-logs',
            'workshop.index',

            // Reports Domain
            'report.daily',
            'report.daily.export',
            'report.weekly',
            'report.weekly.export',
            'report.monthly',
            'report.monthly.export',
            'report.machine-active-hours',
            'production.report',
            'asakai.index',
            'asakai.create',
            'asakai.detail',

            // Barcode & Packaging Domain
            'barcode.custom.form',
            'barcode.custom.logs',
            'barcode.alc.index',
            'all.label.yanfeng',
            'barcode.box_master.index',
            'barcode.history.index',

            // Admin & Settings Domain
            'dailyitemcodes.set-status',
            'ngtypes.index',
            'notification_recipients.index',
            'waiting_purchase_orders.index',
            'setting.holiday-schedule.index',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Expected route [{$routeName}] is not registered in Route collection."
            );
        }
    }

    /**
     * Test that critical security middleware groups are strictly attached to protected routes.
     */
    public function test_route_middleware_guards_are_preserved(): void
    {
        // 1. Auth middleware on core protected routes
        $authGuardedRoutes = [
            'profile',
            'second-process-reports.index',
            'wms.mapping',
            'wms.pallet-form.create-delivery',
            'mwh.mapping',
        ];

        foreach ($authGuardedRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] should exist.");
            $middleware = $route->gatherMiddleware();
            $this->assertTrue(
                in_array('auth', $middleware),
                "Route [{$routeName}] must have 'auth' middleware attached."
            );
        }

        // 2. Second Process Plant Access middleware
        $spPlantRoutes = [
            'second-process-reports.index',
            'sp-approvals.index',
            'sp-work-orders.index',
        ];

        foreach ($spPlantRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] should exist.");
            $middleware = $route->gatherMiddleware();
            $this->assertTrue(
                in_array('sp.plant-access', $middleware),
                "Route [{$routeName}] must have 'sp.plant-access' middleware attached."
            );
        }

        // 3. Store Access middleware
        $storeAccessRoutes = [
            'wms.mapping',
            'wms.pallet-form.create-delivery',
            'mwh.mapping',
        ];

        foreach ($storeAccessRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] should exist.");
            $middleware = $route->gatherMiddleware();
            $this->assertTrue(
                in_array('store.access', $middleware),
                "Route [{$routeName}] must have 'store.access' middleware attached."
            );
        }
    }

    /**
     * Test that unauthenticated requests to protected routes redirect to login.
     */
    public function test_unauthenticated_requests_redirect_to_login(): void
    {
        $protectedUrls = [
            '/profile',
            '/second-process-reports',
            '/wms/mapping',
            '/wms/pallet-form/create-delivery',
            '/material-warehouse/mapping',
        ];

        foreach ($protectedUrls as $url) {
            $response = $this->get($url);
            $response->assertStatus(302);
            $response->assertRedirect('/login');
        }
    }

    /**
     * Test public accessibility of public endpoints.
     */
    public function test_public_routes_remain_accessible(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/')->assertStatus(302); // Redirects to login
    }
}
