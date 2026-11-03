<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondProcessPartCycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_find_covering_report_matches_by_output_and_input(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $tomorrow = Carbon::now('Asia/Jakarta')->addDay()->toDateString();

        // 1. Origin report with sisa_input = 50
        $report1 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-TEST-ABC',
            'part_name' => 'Widget ABC',
            'customer' => 'PT Astra',
            'jml_input_wip' => 500,
            'repairan' => 0,
            'jumlah_output' => 450,
            'jumlah_ok' => 440,
            'jumlah_ng' => 10,
            'sisa_input' => 50,
            'status' => 'submitted',
        ]);

        // Before any subsequent report exists, status is open
        $this->assertNull($report1->findCoveringReport());
        $statusOpen = $report1->reconciliation_status;
        $this->assertEquals('open', $statusOpen['type']);

        // 2. Next shift report with matching output = 50
        $report2 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-TEST-ABC',
            'part_name' => 'Widget ABC',
            'customer' => 'PT Astra',
            'jml_input_wip' => 50,
            'repairan' => 0,
            'jumlah_output' => 50,
            'jumlah_ok' => 50,
            'jumlah_ng' => 0,
            'sisa_input' => 0,
            'status' => 'submitted',
        ]);

        // Report 1 now detects Report 2 as covering report
        $covering = $report1->findCoveringReport();
        $this->assertNotNull($covering);
        $this->assertEquals($report2->id, $covering->id);

        $statusReconciled = $report1->reconciliation_status;
        $this->assertEquals('reconciled', $statusReconciled['type']);
        $this->assertEquals($report2->id, $statusReconciled['linked_report_id']);

        // Report 2 detects Report 1 as predecessor sisa source
        $preceding = $report2->findPrecedingSisaReport();
        $this->assertNotNull($preceding);
        $this->assertEquals($report1->id, $preceding->id);

        $statusAbsorber = $report2->reconciliation_status;
        $this->assertEquals('absorber', $statusAbsorber['type']);
        $this->assertEquals($report1->id, $statusAbsorber['linked_report_id']);
    }

    public function test_find_covering_report_ignores_different_part_or_line(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $report1 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-X',
            'jml_input_wip' => 100,
            'jumlah_output' => 70,
            'sisa_input' => 30,
            'status' => 'submitted',
        ]);

        // Subsequent report with same quantity but DIFFERENT line
        SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 2', // Different line
            'process_prod' => 'Painting',
            'part_number' => 'PART-X',
            'jml_input_wip' => 30,
            'jumlah_output' => 30,
            'sisa_input' => 0,
            'status' => 'submitted',
        ]);

        $this->assertNull($report1->findCoveringReport());
    }

    public function test_index_page_displays_timeline_button_and_reconciliation_badge(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $report1 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-BADGE',
            'customer' => 'PT Astra',
            'jml_input_wip' => 100,
            'jumlah_output' => 70,
            'sisa_input' => 30,
            'status' => 'submitted',
        ]);

        $report2 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-BADGE',
            'customer' => 'PT Astra',
            'jml_input_wip' => 30,
            'jumlah_output' => 30,
            'sisa_input' => 0,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user)->get(route('second-process-reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Timeline Siklus Part');
        $response->assertSee('Ditutup #' . $report2->id);
        $response->assertSee('Dari #' . $report1->id);
    }

    public function test_part_cycle_page_renders_chronological_timeline(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $tomorrow = Carbon::now('Asia/Jakarta')->addDay()->toDateString();

        // Stage 1: Amplas
        SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Amplas',
            'part_number' => 'PART-CYCLE-99',
            'part_name' => 'Housing Mirror',
            'customer' => 'PT Daihatsu',
            'model' => 'D26A',
            'jml_input_wip' => 200,
            'jumlah_ok' => 200,
            'jumlah_ng' => 0,
            'jumlah_output' => 200,
            'sisa_input' => 0,
            'status' => 'acknowledged',
        ]);

        // Stage 2: Painting
        SecondProcessReport::create([
            'date' => $tomorrow,
            'shift' => 1,
            'unit_line' => 'Line 2',
            'process_prod' => 'Painting',
            'part_number' => 'PART-CYCLE-99',
            'part_name' => 'Housing Mirror',
            'customer' => 'PT Daihatsu',
            'model' => 'D26A',
            'jml_input_wip' => 200,
            'jumlah_ok' => 180,
            'jumlah_ng' => 5,
            'jumlah_output' => 185,
            'sisa_input' => 15,
            'status' => 'acknowledged',
        ]);

        // Load without part selected
        $responseEmpty = $this->actingAs($this->user)->get(route('second-process-reports.part-cycle'));
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertSee('Silakan Pilih Part Number');

        // Load with selected part
        $responsePart = $this->actingAs($this->user)->get(route('second-process-reports.part-cycle', [
            'part_number' => 'PART-CYCLE-99',
            'preset' => 'all',
        ]));

        $responsePart->assertStatus(200);
        $responsePart->assertSee('PART-CYCLE-99');
        $responsePart->assertSee('Housing Mirror');
        $responsePart->assertSee('PT Daihatsu');
        $responsePart->assertSee('Amplas');
        $responsePart->assertSee('Painting');
        $responsePart->assertSee('Total Input WIP');
        $responsePart->assertSee('Total Output OK');
        $responsePart->assertSee('Urutan Kronologis Siklus Proses');
    }

    public function test_show_page_displays_reconciliation_status_and_links(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // 1. Origin report with sisa_input = 25 (Open)
        $report1 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-SHOW-RECON',
            'part_name' => 'Cover Handle',
            'customer' => 'PT Astra',
            'jml_input_wip' => 100,
            'jumlah_output' => 75,
            'jumlah_ok' => 75,
            'jumlah_ng' => 0,
            'sisa_input' => 25,
            'status' => 'submitted',
        ]);

        $res1 = $this->actingAs($this->user)->get(route('second-process-reports.show', $report1->id));
        $res1->assertStatus(200);
        $res1->assertSee('Status Carryover Sisa (Terbuka)');
        $res1->assertSee('Siklus Part');

        // 2. Next shift report absorbing the 25 sisa
        $report2 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-SHOW-RECON',
            'part_name' => 'Cover Handle',
            'customer' => 'PT Astra',
            'jml_input_wip' => 25,
            'jumlah_output' => 25,
            'jumlah_ok' => 25,
            'jumlah_ng' => 0,
            'sisa_input' => 0,
            'status' => 'submitted',
        ]);

        // Report 1 now shows reconciled status
        $res1After = $this->actingAs($this->user)->get(route('second-process-reports.show', $report1->id));
        $res1After->assertStatus(200);
        $res1After->assertSee('Status Carryover Sisa (Tertutup)');
        $res1After->assertSee("Laporan #{$report2->id}");

        // Report 2 shows absorber status
        $res2 = $this->actingAs($this->user)->get(route('second-process-reports.show', $report2->id));
        $res2->assertStatus(200);
        $res2->assertSee('Status Carryover Sisa (Penutup Sisa Sebelumnya)');
        $res2->assertSee("Laporan Asal #{$report1->id}");
    }

    public function test_analytics_calculates_wip_reconciliation_using_open_sisa(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Report 1: sisa 40, reconciled by Report 2
        $rep1 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-ANALYTICS-RECON',
            'customer' => 'PT Astra',
            'jml_input_wip' => 100,
            'jumlah_output' => 60,
            'jumlah_ok' => 60,
            'jumlah_ng' => 0,
            'sisa_input' => 40,
            'status' => 'acknowledged',
        ]);

        $rep2 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-ANALYTICS-RECON',
            'customer' => 'PT Astra',
            'jml_input_wip' => 40,
            'jumlah_output' => 40,
            'jumlah_ok' => 40,
            'jumlah_ng' => 0,
            'sisa_input' => 0,
            'status' => 'acknowledged',
        ]);

        // Report 3: sisa 15, open (not covered)
        $rep3 = SecondProcessReport::create([
            'date' => $today,
            'shift' => 3,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-ANALYTICS-RECON',
            'customer' => 'PT Astra',
            'jml_input_wip' => 50,
            'jumlah_output' => 35,
            'jumlah_ok' => 35,
            'jumlah_ng' => 0,
            'sisa_input' => 15,
            'status' => 'acknowledged',
        ]);

        $res = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'part_number' => 'PART-ANALYTICS-RECON',
            'preset' => 'all',
        ]));

        $res->assertStatus(200);
        // wipVariance must only be the Open Sisa (15), NOT the naive double-counted 55!
        $res->assertViewHas('wipVariance', 15);
        $res->assertViewHas('openSisaVariance', 15);
        $res->assertViewHas('reconciledSisaTotal', 40);
        $res->assertSee('WIP Reconciliation (Open Sisa)');
        $res->assertSee('40 pcs');
    }

    public function test_sisa_filter_scopes_correctly_for_open_and_reconciled(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // 1. Report A with sisa = 30 (Reconciled by Report B)
        $repA = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-SCOPE-TEST',
            'customer' => 'PT Astra',
            'jml_input_wip' => 100,
            'jumlah_output' => 70,
            'jumlah_ok' => 70,
            'jumlah_ng' => 0,
            'sisa_input' => 30,
            'status' => 'submitted',
        ]);

        $repB = SecondProcessReport::create([
            'date' => $today,
            'shift' => 2,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-SCOPE-TEST',
            'customer' => 'PT Astra',
            'jml_input_wip' => 30,
            'jumlah_output' => 30,
            'jumlah_ok' => 30,
            'jumlah_ng' => 0,
            'sisa_input' => 0,
            'status' => 'submitted',
        ]);

        // 2. Report C with sisa = 12 (Open - no covering)
        $repC = SecondProcessReport::create([
            'date' => $today,
            'shift' => 3,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-SCOPE-TEST',
            'customer' => 'PT Astra',
            'jml_input_wip' => 50,
            'jumlah_output' => 38,
            'jumlah_ok' => 38,
            'jumlah_ng' => 0,
            'sisa_input' => 12,
            'status' => 'submitted',
        ]);

        // Test scope open
        $openReports = SecondProcessReport::where('part_number', 'PART-SCOPE-TEST')->applySisaFilter('open')->pluck('id')->all();
        $this->assertEquals([$repC->id], $openReports);

        // Test scope reconciled
        $reconciledReports = SecondProcessReport::where('part_number', 'PART-SCOPE-TEST')->applySisaFilter('reconciled')->pluck('id')->all();
        $this->assertEquals([$repA->id], $reconciledReports);

        // Test scope has_sisa
        $hasSisaReports = SecondProcessReport::where('part_number', 'PART-SCOPE-TEST')->applySisaFilter('has_sisa')->pluck('id')->all();
        $this->assertCount(2, $hasSisaReports);
        $this->assertContains($repA->id, $hasSisaReports);
        $this->assertContains($repC->id, $hasSisaReports);

        // Test scope ideal
        $idealReports = SecondProcessReport::where('part_number', 'PART-SCOPE-TEST')->applySisaFilter('ideal')->pluck('id')->all();
        $this->assertEquals([$repB->id], $idealReports);
    }

    public function test_quick_filter_sisa_navigation_between_analytics_and_index_vice_versa(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $rep = SecondProcessReport::create([
            'date' => $today,
            'shift' => 1,
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-NAV-TEST',
            'customer' => 'PT Astra',
            'jml_input_wip' => 100,
            'jumlah_output' => 80,
            'jumlah_ok' => 80,
            'jumlah_ng' => 0,
            'sisa_input' => 20,
            'status' => 'submitted',
        ]);

        // 1. Visit Index with sisa_filter=open -> Analytics Dashboard link must contain sisa_filter=open
        $indexRes = $this->actingAs($this->user)->get(route('second-process-reports.index', [
            'sisa_filter' => 'open',
            'preset' => 'all',
        ]));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('sisa_filter=open');
        $indexRes->assertSee('PART-NAV-TEST');

        // 2. Visit Analytics with sisa_filter=open -> Drilldown link to index must contain sisa_filter=open
        $analyticsRes = $this->actingAs($this->user)->get(route('second-process.report-analytics', [
            'sisa_filter' => 'open',
            'preset' => 'all',
        ]));
        $analyticsRes->assertStatus(200);
        $analyticsRes->assertSee('sisa_filter=open');
        $analyticsRes->assertSee('Tabel Laporan');
    }
}
