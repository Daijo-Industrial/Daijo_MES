<?php

namespace Tests\Unit;

use App\Livewire\ProductionYearlyDashboard;
use App\Services\NavigationService;
use App\Services\PlantContextService;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class ProductionYearlyDashboardUnitTest extends TestCase
{
    public function test_navigation_schema_includes_yearly_production_dashboard(): void
    {
        $mock = Mockery::mock(PlantContextService::class);
        $mock->shouldReceive('supportsSecondProcess')->andReturn(true)->byDefault();
        $navService = new NavigationService($mock);

        $schema = $navService->navigationSchema();
        $dashboardGroup = collect($schema)->firstWhere('label', 'Dashboard All');
        $this->assertNotNull($dashboardGroup);

        $yearlyItem = collect($dashboardGroup['children'] ?? [])->firstWhere('name', 'production-yearly-dashboard');
        $this->assertNotNull($yearlyItem);
        $this->assertSame('Yearly Production Dashboard (BOD)', $yearlyItem['label']);
        $this->assertContains('view-production-links', $yearlyItem['can']);

        $monthlyItem = collect($dashboardGroup['children'] ?? [])->firstWhere('name', 'production-dashboard');
        $this->assertNotNull($monthlyItem);
        $this->assertSame('Production Dashboard', $monthlyItem['label']);
    }

    public function test_route_production_yearly_dashboard_is_registered(): void
    {
        $this->assertTrue(Route::has('production-yearly-dashboard'));
        $this->assertSame(url('/production-yearly-dashboard'), route('production-yearly-dashboard'));

        // Assert uses layouts.dashboard (clean fullscreen without sidebar, like production-dashboard)
        $ref = new \ReflectionClass(ProductionYearlyDashboard::class);
        $attributes = $ref->getAttributes(\Livewire\Attributes\Layout::class);
        $this->assertNotEmpty($attributes);
        $this->assertSame('layouts.dashboard', $attributes[0]->getArguments()[0]);
    }

    public function test_machine_filter_partitioning_by_plant(): void
    {
        $component = new class extends ProductionYearlyDashboard
        {
            public function setAllMachinesForTest(array $machines): void
            {
                $this->allMachines = $machines;
            }

            public function testFilter(): void
            {
                // call protected/private via reflection or wrapper
                $ref = new \ReflectionMethod(ProductionYearlyDashboard::class, 'updateFilteredMachines');
                $ref->setAccessible(true);
                $ref->invoke($this);
            }
        };

        $dummyMachines = [
            ['id' => 1, 'name' => 'K0150A', 'plant' => 'karawang'],
            ['id' => 2, 'name' => 'K2800A', 'plant' => 'karawang'],
            ['id' => 3, 'name' => 'MC-05', 'plant' => 'kbn'],
            ['id' => 4, 'name' => 'MC-10', 'plant' => 'kbn'],
        ];

        $component->setAllMachinesForTest($dummyMachines);

        // Test Karawang filter
        $component->plant = 'karawang';
        $component->testFilter();
        $this->assertCount(2, $component->machines);
        $this->assertEquals(['K0150A', 'K2800A'], array_column($component->machines, 'name'));

        // Test KBN filter
        $component->plant = 'kbn';
        $component->testFilter();
        $this->assertCount(2, $component->machines);
        $this->assertEquals(['MC-05', 'MC-10'], array_column($component->machines, 'name'));

        // Test All Plants filter
        $component->plant = '';
        $component->testFilter();
        $this->assertCount(4, $component->machines);
    }

    public function test_yearly_view_structure_meets_bod_executive_requirements(): void
    {
        $viewContent = file_get_contents(resource_path('views/livewire/production-yearly-dashboard.blade.php'));

        // 1. Executive Header & BOD labels
        $this->assertStringContainsString('Executive Production Dashboard (Tahunan)', $viewContent);
        $this->assertStringContainsString('Dewan Direksi', $viewContent);

        // 2. 4 Hero KPI Cards
        $this->assertStringContainsString('Total Target Tahunan', $viewContent);
        $this->assertStringContainsString('Total Output Aktual', $viewContent);
        $this->assertStringContainsString('Rasio Pencapaian (% Achievement)', $viewContent);
        $this->assertStringContainsString('Tingkat Cacat (NG Rate Tahunan)', $viewContent);

        // 3. 12-Month Performance Trend & Machine Matrix
        $this->assertStringContainsString('Perkembangan Kinerja Bulanan', $viewContent);
        $this->assertStringContainsString('Matriks Produktivitas & Efisiensi Mesin', $viewContent);
        $this->assertStringContainsString('Analisis 5 Kategori Defect Terbesar', $viewContent);

        // 4. Shop-floor & Enterprise Invariant Compliance
        // No decorative emojis: ✓, ⏳, ⛔, 📋, 📝, 🔧
        $this->assertDoesNotMatchRegularExpression('/[✓⏳⛔📋📝🔧]/u', $viewContent);
        // Table layout fixed for desktop zero horizontal scroll
        $this->assertStringContainsString('table-fixed', $viewContent);

        // 5. User BOD Color & Threshold Criteria
        // Left bar Target: neutral slate; Right bar Actual: >=90% emerald, 80-89% blue, <80% rose
        $this->assertStringContainsString('bg-slate-300 dark:bg-slate-600', $viewContent);
        $this->assertStringContainsString('bg-emerald-500', $viewContent);
        $this->assertStringContainsString('bg-blue-500', $viewContent);
        $this->assertStringContainsString('bg-rose-500', $viewContent);
        $this->assertStringContainsString('≥90% (Tercapai)', $viewContent);
        $this->assertStringContainsString('80-89% (Kurang)', $viewContent);
        $this->assertStringContainsString('&lt;80% (Kritis)', $viewContent);
    }

    public function test_monthly_dashboard_contains_bod_yearly_switcher(): void
    {
        $content = file_get_contents(resource_path('views/livewire/Production-dashboard.blade.php'));

        $this->assertStringContainsString("route('production-yearly-dashboard')", $content);
        $this->assertStringContainsString('Yearly Dashboard (BOD)', $content);
    }
}
