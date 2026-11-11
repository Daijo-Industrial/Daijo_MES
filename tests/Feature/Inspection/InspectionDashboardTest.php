<?php

namespace Tests\Feature\Inspection;

use App\Livewire\Inspection\InspectionDashboard;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'QC']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        $report1 = InspectionReport::create([
            'document_number' => 'INSP-DASH-001',
            'customer' => 'Customer Alpha',
            'inspection_date' => '2026-10-20',
            'part_number' => 'PART-DASH-1',
            'part_name' => 'BEZEL METER',
            'weight' => 20.0,
            'weight_uom' => 'g',
            'material' => 'ABS',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Insp1',
        ]);

        InspectionQuantity::create([
            'inspection_report_document_number' => $report1->document_number,
            'output_quantity' => 1000,
            'pass_quantity' => 980,
            'reject_quantity' => 20,
            'sampling_quantity' => 50,
            'ng_sample_quantity' => 1,
            'pass_rate' => 98.0,
            'reject_rate' => 2.0,
            'ng_sample_rate' => 2.0,
        ]);

        InspectionProblem::create([
            'inspection_report_document_number' => $report1->document_number,
            'type' => 'MACHINE PROBLEM',
            'time' => '10:00',
            'cycle_time' => 55,
            'remarks' => 'Temperature drop',
        ]);

        InspectionDimension::create([
            'inspection_report_document_number' => $report1->document_number,
            'lower_limit' => 5.0,
            'upper_limit' => 5.2,
            'limit_uom' => 'mm',
            'actual_value' => 5.4,
            'judgement' => 'NG',
            'area' => 'Clip Boss A',
            'remarks' => 'Oversize',
        ]);

        $report2 = InspectionReport::create([
            'document_number' => 'INSP-DASH-002',
            'customer' => 'Customer Beta',
            'inspection_date' => '2026-10-21',
            'part_number' => 'PART-DASH-2',
            'part_name' => 'LENS INNER',
            'weight' => 15.0,
            'weight_uom' => 'g',
            'material' => 'PMMA',
            'color' => 'CLEAR',
            'tool_number_or_cav_number' => '2',
            'machine_number' => 'M2',
            'shift' => 2,
            'operator' => 'Op2',
            'inspector' => 'Insp2',
        ]);

        InspectionQuantity::create([
            'inspection_report_document_number' => $report2->document_number,
            'output_quantity' => 2000,
            'pass_quantity' => 1950,
            'reject_quantity' => 50,
            'sampling_quantity' => 100,
            'ng_sample_quantity' => 2,
            'pass_rate' => 97.5,
            'reject_rate' => 2.5,
            'ng_sample_rate' => 2.0,
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('inspection-reports.dashboard'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('inspection-reports.dashboard'));
        $response->assertOk();
        $response->assertSee('Plastic Injection IPQC Quality Dashboard');
        $response->assertSee('Total Laporan');
    }

    public function test_dashboard_computes_kpi_and_charts(): void
    {
        Livewire::actingAs($this->user)
            ->test(InspectionDashboard::class)
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-31')
            ->assertSet('kpi.total_reports', 2)
            ->assertSet('kpi.total_output', 3000)
            ->assertSet('kpi.total_pass', 2930)
            ->assertSet('kpi.total_reject', 70)
            ->assertDispatched('charts-ready')
            ->assertSee('Customer Alpha')
            ->assertSee('Customer Beta')
            ->assertSee('MACHINE PROBLEM')
            ->assertSee('Clip Boss A');
    }

    public function test_dashboard_filters_by_customer(): void
    {
        Livewire::actingAs($this->user)
            ->test(InspectionDashboard::class)
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-31')
            ->set('customer', 'Customer Alpha')
            ->assertSet('kpi.total_reports', 1)
            ->assertSet('kpi.total_output', 1000)
            ->assertSet('kpi.total_pass', 980)
            ->assertSet('kpi.total_reject', 20);
    }
}
