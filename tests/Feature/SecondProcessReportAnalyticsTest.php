<?php

namespace Tests\Feature;

use App\Models\MasterBusinessPartner;
use App\Models\Role;
use App\Models\SecondProcessNgRecord;
use App\Models\SecondProcessReport;
use App\Models\SecondProcessTrouble;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondProcessReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->user = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    public function test_analytics_requires_authentication(): void
    {
        $response = $this->get(route('second-process.report-analytics'));
        $response->assertRedirect('/login');
    }

    public function test_analytics_page_renders_with_kpis_and_filters(): void
    {
        $today = now()->format('Y-m-d');

        // Create Report 1
        $report1 = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'PART-001',
            'part_name' => 'Side Cover Left',
            'customer' => 'Customer A',
            'jml_input_wip' => 1000,
            'repairan' => 50,
            'jumlah_output' => 950,
            'jumlah_ok' => 900,
            'jumlah_ng' => 50,
            'jml_ng_lebur' => 10,
        ]);

        // Create Report 2
        $report2 = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 2',
            'shift' => 2,
            'process_prod' => 'Painting',
            'status' => 'pqc_approved',
            'part_number' => 'PART-002',
            'part_name' => 'Side Cover Right',
            'customer' => 'Customer B',
            'jml_input_wip' => 500,
            'repairan' => 20,
            'jumlah_output' => 500,
            'jumlah_ok' => 480,
            'jumlah_ng' => 20,
            'jml_ng_lebur' => 5,
        ]);

        // Add troubles to Report 1
        SecondProcessTrouble::create([
            'report_id' => $report1->id,
            'category' => 'Mesin',
            'penyebab' => 'Mesin',
            'masalah' => 'Nozzle clogged',
            'loss_time_minutes' => 30,
            'penanganan' => 'Cleaned nozzle tip',
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $response->assertViewIs('second_process.report_analytics');

        // Verify KPI totals passed to view
        $summary = $response->viewData('summary');
        $this->assertEquals(2, $summary->total_reports);
        $this->assertEquals(1450, $summary->total_output);
        $this->assertEquals(1380, $summary->total_ok);
        $this->assertEquals(70, $summary->total_ng);
        $this->assertEquals(1500, $summary->total_input_wip);
        $this->assertEquals(70, $summary->total_repairan);
        $this->assertEquals(15, $summary->total_scrap);

        $this->assertEquals(1570, $response->viewData('totalInput'));
        $this->assertEquals(95.17, $response->viewData('yieldRate')); // 1380 / 1450 * 100
        $this->assertEquals(4.83, $response->viewData('avgNgRate')); // 70 / 1450 * 100
        $this->assertEquals(105, $response->viewData('wipVariance')); // 1570 - (1450 + 15)
        $this->assertEquals(0.96, $response->viewData('scrapRate')); // 15 / 1570 * 100
        $this->assertEquals(2.0, $response->viewData('targetNgRate'));
        $this->assertNull($response->viewData('targetAchievementRate'));
        $this->assertEquals(30, $response->viewData('totalDowntimeMinutes'));
        $this->assertEquals(0.5, $response->viewData('totalDowntimeHours'));

        // Verify HTML contents
        $response->assertSee('Total Input WIP');
        $response->assertSee('Total Repairan');
        $response->assertSee('Total Scrap (Lebur)');
        $response->assertSee('WIP Reconciliation');
        $response->assertSee('Export CSV');
        $response->assertSee('Print Report');
        $response->assertSee('Top Defects / High Risk');
        $response->assertSee('Top Downtime Incidents');
        $response->assertSee('Nozzle clogged');
        $response->assertSee('Cleaned nozzle tip');
    }

    public function test_analytics_filters_by_line_shift_process_and_status(): void
    {
        $today = now()->format('Y-m-d');

        // Target report matching all criteria
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'MATCH-001',
            'part_name' => 'Matching Part',
            'customer' => 'Customer A',
            'jml_input_wip' => 600,
            'repairan' => 10,
            'jumlah_output' => 600,
            'jumlah_ok' => 590,
            'jumlah_ng' => 10,
            'jml_ng_lebur' => 2,
        ]);

        // Non-matching reports
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 2',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'DIFF-LINE',
            'part_name' => 'Different Line',
            'customer' => 'Customer A',
            'jumlah_output' => 200,
            'jumlah_ok' => 200,
            'jumlah_ng' => 0,
        ]);

        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 2,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'DIFF-SHIFT',
            'part_name' => 'Different Shift',
            'customer' => 'Customer A',
            'jumlah_output' => 300,
            'jumlah_ok' => 300,
            'jumlah_ng' => 0,
        ]);

        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Buffing',
            'status' => 'submitted',
            'part_number' => 'DIFF-PROC',
            'part_name' => 'Different Process',
            'customer' => 'Customer A',
            'jumlah_output' => 150,
            'jumlah_ok' => 150,
            'jumlah_ng' => 0,
        ]);

        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'DIFF-STATUS',
            'part_name' => 'Draft Status',
            'customer' => 'Customer A',
            'jumlah_output' => 100,
            'jumlah_ok' => 100,
            'jumlah_ng' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
        ]));

        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertEquals(1, $summary->total_reports);
        $this->assertEquals(600, $summary->total_output);
        $this->assertEquals(590, $summary->total_ok);
        $this->assertEquals(10, $summary->total_ng);

        $response->assertSee('MATCH-001');
        $response->assertDontSee('DIFF-LINE');
        $response->assertDontSee('DIFF-SHIFT');
        $response->assertDontSee('DIFF-PROC');
        $response->assertDontSee('DIFF-STATUS');
    }

    public function test_analytics_aggregates_downtime_with_category_and_penyebab_fallback(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'acknowledged',
            'part_number' => 'DOWN-001',
            'part_name' => 'Downtime Test Part',
            'customer' => 'Customer A',
            'jumlah_output' => 500,
            'jumlah_ok' => 450,
            'jumlah_ng' => 50,
        ]);

        // Trouble 1: standard category (category set, penyebab set)
        SecondProcessTrouble::create([
            'report_id' => $report->id,
            'category' => 'Mesin',
            'penyebab' => 'Mesin',
            'masalah' => 'Sensor conveyor fault',
            'loss_time_minutes' => 45,
            'penanganan' => 'Sensor alignment & calibration',
        ]);

        // Trouble 2: legacy record where category is null/empty, but penyebab holds the legacy category 'Man'
        SecondProcessTrouble::create([
            'report_id' => $report->id,
            'category' => null,
            'penyebab' => 'Man',
            'masalah' => 'New operator setup',
            'loss_time_minutes' => 30,
            'penanganan' => 'Supervisor guidance provided',
        ]);

        // Trouble 3: neither category nor valid penyebab (empty strings) -> fallback to 'Other'
        SecondProcessTrouble::create([
            'report_id' => $report->id,
            'category' => '',
            'penyebab' => '',
            'masalah' => 'Unexpected stoppage',
            'loss_time_minutes' => 15,
            'penanganan' => 'Line restarted',
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $this->assertEquals(90, $response->viewData('totalDowntimeMinutes'));
        $this->assertEquals(1.5, $response->viewData('totalDowntimeHours'));

        $downtime = $response->viewData('downtime');
        $this->assertContains('Mesin', $downtime['labels']);
        $this->assertContains('Man', $downtime['labels']);
        $this->assertContains('Other', $downtime['labels']);

        $topTroubles = $response->viewData('topTroubles');
        $this->assertCount(3, $topTroubles);
        $this->assertEquals('Sensor conveyor fault', $topTroubles->first()->masalah);
        $this->assertEquals(45, $topTroubles->first()->loss_time_minutes);

        $response->assertSee('Sensor conveyor fault');
        $response->assertSee('Sensor alignment &amp; calibration', false);
        $response->assertSee('Supervisor guidance provided');
    }

    public function test_analytics_calculates_target_achievement_and_wip_variance(): void
    {
        $today = now()->format('Y-m-d');

        // Target: 100/hr * 8 hrs = 800 units planned
        // Output: 720 units (Achievement = 720 / 800 = 90.0%)
        // Input: 800 WIP + 50 Repairan = 850 total fed
        // Output: 720 (700 OK + 20 NG), Scrap: 10
        // Variance: 850 - (720 + 10) = 120 units
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'ACHIEVE-01',
            'part_name' => 'Achievement Test Part',
            'customer' => 'Customer Plan',
            'target_per_hour' => 100,
            'jml_input_wip' => 800,
            'repairan' => 50,
            'jumlah_output' => 720,
            'jumlah_ok' => 700,
            'jumlah_ng' => 20,
            'jml_ng_lebur' => 10,
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertEquals(800, $summary->total_target);
        $this->assertEquals(90.0, $response->viewData('targetAchievementRate'));
        $this->assertEquals(120, $response->viewData('wipVariance'));
        $this->assertEquals(1.18, $response->viewData('scrapRate')); // 10 / 850 * 100 = 1.18%

        $topDefects = $response->viewData('topDefectProductsRaw');
        $this->assertCount(1, $topDefects);
        $this->assertEquals('ACHIEVE-01', $topDefects->first()->part_number);
        $this->assertEquals(20, $topDefects->first()->total_ng);

        $response->assertSee('90% Plan');
        $response->assertSee('+120');
        $response->assertSee('In-Line WIP');
    }

    public function test_analytics_can_export_csv(): void
    {
        $today = now()->format('Y-m-d');

        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'EXPORT-001',
            'part_name' => 'Export Part',
            'customer' => 'Customer CSV',
            'target_per_hour' => 50,
            'jml_input_wip' => 400,
            'repairan' => 20,
            'jumlah_output' => 380,
            'jumlah_ok' => 370,
            'jumlah_ng' => 10,
            'jml_ng_lebur' => 5,
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'export' => 'csv',
        ]));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename='));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Part Number', $content);
        $this->assertStringContainsString('Shift Target', $content);
        $this->assertStringContainsString('WIP Variance', $content);
        $this->assertStringContainsString('EXPORT-001', $content);
        $this->assertStringContainsString('Export Part', $content);
        $this->assertStringContainsString('Customer CSV', $content);
    }

    public function test_analytics_can_export_materials_csv_all(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'MAT-EXPORT-001',
            'part_name' => 'Painted Cover',
            'customer' => 'Customer Mat',
            'jml_input_wip' => 500,
            'repairan' => 0,
            'jumlah_output' => 500,
            'jumlah_ok' => 480,
            'jumlah_ng' => 20,
            'jml_ng_lebur' => 0,
        ]);

        $report->materials()->create([
            'type' => 'paint',
            'item_name' => 'Paint Primer Grey',
            'lot_number' => 'LOT-P-01',
            'visco' => '14s',
            'mixing_ratio' => '1:1',
            'qty' => 6.0,
            'uom' => 'Ltr',
        ]);

        $report->materials()->create([
            'type' => 'part',
            'item_name' => 'Cover Raw WIP',
            'lot_number' => 'LOT-WIP-01',
            'qty' => 500,
            'uom' => 'Pcs',
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'export' => 'materials',
        ]));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'second-process-materials-all-'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Material Type', $content);
        $this->assertStringContainsString('Material Item Name', $content);
        $this->assertStringContainsString('Consumption per 1000 Pcs', $content);
        $this->assertStringContainsString('Part Yield Rate (%)', $content);
        $this->assertStringContainsString('Paint Primer Grey', $content);
        $this->assertStringContainsString('Cover Raw WIP', $content);
        $this->assertStringContainsString('MAT-EXPORT-001', $content);
        // Paint calculation: (6.0 * 1000) / 480 = 12.5
        $this->assertStringContainsString('12.5', $content);
        // Part yield: (480 / 500) * 100 = 96%
        $this->assertStringContainsString('96%', $content);
    }

    public function test_analytics_can_export_materials_csv_filtered_by_type(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'MAT-FILTER-001',
            'part_name' => 'Filter Cover',
            'customer' => 'Customer Filter',
            'jml_input_wip' => 200,
            'repairan' => 0,
            'jumlah_output' => 200,
            'jumlah_ok' => 190,
            'jumlah_ng' => 10,
        ]);

        $report->materials()->create([
            'type' => 'paint',
            'item_name' => 'Basecoat Metallic',
            'lot_number' => 'LOT-P-MET',
            'qty' => 3.5,
            'uom' => 'Ltr',
        ]);

        $report->materials()->create([
            'type' => 'part',
            'item_name' => 'Plastic Shell WIP',
            'lot_number' => 'LOT-SHELL',
            'qty' => 200,
            'uom' => 'Pcs',
        ]);

        // 1. Filter by paint
        $paintResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'export' => 'materials',
            'material_type' => 'paint',
        ]));
        $paintContent = $paintResponse->streamedContent();
        $this->assertStringContainsString('Basecoat Metallic', $paintContent);
        $this->assertStringNotContainsString('Plastic Shell WIP', $paintContent);

        // 2. Filter by part
        $partResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'export' => 'materials',
            'material_type' => 'part',
        ]));
        $partContent = $partResponse->streamedContent();
        $this->assertStringContainsString('Plastic Shell WIP', $partContent);
        $this->assertStringNotContainsString('Basecoat Metallic', $partContent);
    }

    public function test_analytics_dashboard_renders_materials_kpis(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'MAT-DASH-001',
            'part_name' => 'Dashboard Part',
            'customer' => 'Customer Dash',
            'jml_input_wip' => 300,
            'repairan' => 0,
            'jumlah_output' => 300,
            'jumlah_ok' => 290,
            'jumlah_ng' => 10,
        ]);

        $report->materials()->create([
            'type' => 'paint',
            'item_name' => 'Clear Coat Glossy',
            'lot_number' => 'LOT-CC-01',
            'qty' => 2.9,
            'uom' => 'Ltr',
        ]);

        $report->materials()->create([
            'type' => 'part',
            'item_name' => 'Sub Assy Frame',
            'lot_number' => 'LOT-FR-01',
            'qty' => 300,
            'uom' => 'Pcs',
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $this->assertNotNull($response->viewData('topPaints'));
        $this->assertNotNull($response->viewData('topParts'));
        $this->assertEquals(2.9, $response->viewData('totalPaintQty'));
        $this->assertEquals(10.0, $response->viewData('paintIndexPer1000')); // (2.9 * 1000) / 290 = 10.0
        $this->assertEquals(300, $response->viewData('totalPartQty'));

        // HTML assertion
        $response->assertSee('Materials Consumption & Efficiency Analytics');
        $response->assertSee('Clear Coat Glossy');
        $response->assertSee('Sub Assy Frame');
        $response->assertSee('Export Paint CSV');
        $response->assertSee('Export Parts CSV');
    }

    public function test_analytics_collects_ng_categories_and_distribution_breakdown(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'NG-BREAK-01',
            'part_name' => 'Breakdown Test Part',
            'customer' => 'Customer NG',
            'jumlah_output' => 100,
            'jumlah_ok' => 91,
            'jumlah_ng' => 9,
        ]);

        SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'BINTIK',
            'total_ng' => 5,
            'ng_input_item' => '[3] NG-INPUT | [2] NG-PROSES',
            'ng_input_qty' => 5,
        ]);

        SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'SCRATCH',
            'total_ng' => 4,
            'ng_input_item' => '[1] NG-INPUT | [3] NG-PROSES',
            'ng_input_qty' => 4,
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();

        $ngCategories = $response->viewData('ngCategories');
        $this->assertContains('NG-INPUT', $ngCategories);
        $this->assertContains('NG-PROSES', $ngCategories);

        $categoryBreakdown = $response->viewData('categoryBreakdown');
        $this->assertArrayHasKey('NG-INPUT', $categoryBreakdown);
        $this->assertArrayHasKey('NG-PROSES', $categoryBreakdown);
        $this->assertEquals(4, $categoryBreakdown['NG-INPUT']['qty']); // 3 + 1
        $this->assertEquals(5, $categoryBreakdown['NG-PROSES']['qty']); // 2 + 3
        $this->assertEquals(44.4, $categoryBreakdown['NG-INPUT']['percentage']);
        $this->assertEquals(55.6, $categoryBreakdown['NG-PROSES']['percentage']);

        // Assert Pareto defaults across all categories
        $topNg = $response->viewData('topNg');
        $this->assertEquals(['BINTIK', 'SCRATCH'], $topNg['labels']);
        $this->assertEquals([5, 4], $topNg['values']);

        // View asserts
        $response->assertSee('Top NG Defects (Pareto Analysis)');
        $response->assertSee('Distribution:');
        $response->assertSee('NG Category');
    }

    public function test_analytics_filters_pareto_chart_by_ng_category(): void
    {
        $today = now()->format('Y-m-d');

        $report = SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'NG-FILTER-01',
            'part_name' => 'Filter Test Part',
            'customer' => 'Customer NG',
            'jumlah_output' => 200,
            'jumlah_ok' => 189,
            'jumlah_ng' => 11,
        ]);

        SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'BINTIK',
            'total_ng' => 7,
            'ng_input_item' => '[5] NG-INPUT | [2] NG-PROSES',
            'ng_input_qty' => 7,
        ]);

        SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'SCRATCH',
            'total_ng' => 4,
            'ng_input_item' => '[1] NG-INPUT | [3] NG-PROSES',
            'ng_input_qty' => 4,
        ]);

        // 1. Filter by NG-INPUT
        $inputResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'ng_category' => 'NG-INPUT',
        ]));

        $inputResponse->assertOk();
        $this->assertEquals('NG-INPUT', $inputResponse->viewData('selectedNgCategory'));
        $inputTopNg = $inputResponse->viewData('topNg');
        $this->assertEquals(['BINTIK', 'SCRATCH'], $inputTopNg['labels']);
        $this->assertEquals([5, 1], $inputTopNg['values']);
        $this->assertEquals([83.3, 100.0], $inputTopNg['cumulative_pct']);

        // 2. Filter by NG-PROSES
        $prosesResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'ng_category' => 'NG-PROSES',
        ]));

        $prosesResponse->assertOk();
        $this->assertEquals('NG-PROSES', $prosesResponse->viewData('selectedNgCategory'));
        $prosesTopNg = $prosesResponse->viewData('topNg');
        // SCRATCH has 3, BINTIK has 2 -> SCRATCH is #1 in NG-PROSES
        $this->assertEquals(['SCRATCH', 'BINTIK'], $prosesTopNg['labels']);
        $this->assertEquals([3, 2], $prosesTopNg['values']);
        $this->assertEquals([60.0, 100.0], $prosesTopNg['cumulative_pct']);
    }

    public function test_analytics_filters_by_customer_industry(): void
    {
        $today = now()->format('Y-m-d');

        MasterBusinessPartner::create([
            'bp_code' => 'D0000001',
            'bp_name' => 'ASTRA HONDA MOTOR PT.',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
        ]);

        MasterBusinessPartner::create([
            'bp_code' => 'CT000001',
            'bp_name' => 'TOSHIBA',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_ELECTRONICS,
        ]);

        MasterBusinessPartner::create([
            'bp_code' => 'M0000001',
            'bp_name' => 'HONDA LOCK INDONESIA PT.',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'MOULD',
            'industry' => MasterBusinessPartner::INDUSTRY_MOULDING,
        ]);

        // Automotive report
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'AUTO-01',
            'part_name' => 'Fender',
            'customer' => 'ASTRA HONDA MOTOR PT.',
            'target_per_hour' => 100,
            'jumlah_output' => 800,
            'jumlah_ok' => 750,
            'jumlah_ng' => 50,
        ]);

        // Electronics report
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 2',
            'shift' => 1,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'ELEC-01',
            'part_name' => 'TV Bezel',
            'customer' => 'TOSHIBA',
            'target_per_hour' => 50,
            'jumlah_output' => 400,
            'jumlah_ok' => 390,
            'jumlah_ng' => 10,
        ]);

        // Moulding report
        SecondProcessReport::create([
            'date' => $today,
            'unit_line' => 'Line 1',
            'shift' => 2,
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'MOLD-01',
            'part_name' => 'Lock Bezel',
            'customer' => 'HONDA LOCK INDONESIA PT.',
            'target_per_hour' => 60,
            'jumlah_output' => 300,
            'jumlah_ok' => 295,
            'jumlah_ng' => 5,
        ]);

        // 1. Filter by Automotive
        $autoResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'industry' => 'AUTOMOTIVE',
        ]));

        $autoResponse->assertOk();
        $this->assertEquals('AUTOMOTIVE', $autoResponse->viewData('selectedIndustry'));
        $this->assertEquals(1, $autoResponse->viewData('summary')->total_reports);
        $this->assertEquals(800, $autoResponse->viewData('summary')->total_output);
        $this->assertEquals(750, $autoResponse->viewData('summary')->total_ok);

        // 2. Filter by Electronics
        $elecResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'industry' => 'ELECTRONICS',
        ]));

        $elecResponse->assertOk();
        $this->assertEquals('ELECTRONICS', $elecResponse->viewData('selectedIndustry'));
        $this->assertEquals(1, $elecResponse->viewData('summary')->total_reports);
        $this->assertEquals(400, $elecResponse->viewData('summary')->total_output);
        $this->assertEquals(390, $elecResponse->viewData('summary')->total_ok);

        // 3. Filter by Moulding
        $mouldResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'industry' => 'MOULDING',
        ]));

        $mouldResponse->assertOk();
        $this->assertEquals('MOULDING', $mouldResponse->viewData('selectedIndustry'));
        $this->assertEquals(1, $mouldResponse->viewData('summary')->total_reports);
        $this->assertEquals(300, $mouldResponse->viewData('summary')->total_output);
        $this->assertEquals(295, $mouldResponse->viewData('summary')->total_ok);

        // 3. Export CSV with Industry filter
        $csvResponse = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'date_from' => $today,
            'date_to' => $today,
            'industry' => 'AUTOMOTIVE',
            'export' => 'csv',
        ]));

        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('ASTRA HONDA MOTOR PT.', $csvContent);
        $this->assertStringContainsString('AUTOMOTIVE', $csvContent);
        $this->assertStringNotContainsString('TOSHIBA', $csvContent);
    }
}
