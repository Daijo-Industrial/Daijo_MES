<?php

namespace Tests\Feature\Inspection;

use App\Livewire\Inspection\InspectionShow;
use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\FirstInspection;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionJudgement;
use App\Models\Inspection\InspectionPackaging;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use App\Models\Inspection\InspectionSampling;
use App\Models\Inspection\SecondInspection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionShowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected InspectionReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'QC']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);

        // Create Report Header
        $this->report = InspectionReport::create([
            'document_number' => 'INSP-20261029-SHOW-0001',
            'customer' => 'PT Show Testing Customer',
            'inspection_date' => '2026-10-29',
            'part_number' => 'PART-SHOW-888',
            'part_name' => 'HOUSING BRACKET',
            'weight' => 54.2,
            'weight_uom' => 'g',
            'material' => 'PA66-GF30',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => 'CAV-1/2',
            'machine_number' => 'INJ-12',
            'shift' => 1,
            'operator' => 'Agus',
            'inspector' => 'Dini',
            'inspector_autograph' => 'Dini Sign',
            'leader_autograph' => 'Leader Sign',
            'head_autograph' => 'SPV Sign',
        ]);

        // Period 1 Detail
        $detail = DetailInspectionReport::create([
            'inspection_report_document_number' => $this->report->document_number,
            'document_number' => 'DETAIL-20261029-P1',
            'period' => 1,
            'start_datetime' => '2026-10-29 07:30:00',
            'end_datetime' => '2026-10-29 09:30:00',
        ]);

        // First Piece
        FirstInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'appearance' => 'OK',
            'weight' => 54.2,
            'weight_uom' => 'g',
            'fitting_test' => 'Fit smoothly',
            'remarks' => null,
        ]);

        // Dimensions
        InspectionDimension::create([
            'inspection_report_document_number' => $this->report->document_number,
            'lower_limit' => 12.0,
            'upper_limit' => 12.5,
            'limit_uom' => 'mm',
            'actual_value' => 12.2,
            'judgement' => 'OK',
            'area' => 'Diameter Hole A',
            'remarks' => null,
            'start_datetime' => '2026-10-29 07:30:00',
            'end_datetime' => '2026-10-29 09:30:00',
        ]);

        // Second Inspection
        $second = SecondInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'document_number' => 'SECOND-20261029-P1',
            'lot_size_quantity' => 1500,
        ]);

        // Sampling
        InspectionSampling::create([
            'second_inspection_document_number' => $second->document_number,
            'quantity' => 50,
            'box_label' => 'BOX-01',
            'appearance' => 'OK',
            'ng_quantity' => null,
            'remarks' => null,
        ]);

        // Packaging
        InspectionPackaging::create([
            'second_inspection_document_number' => $second->document_number,
            'snp' => 150,
            'box_label' => 'BOX-01',
            'judgement' => 'OK',
            'remarks' => null,
        ]);

        // Judgement
        InspectionJudgement::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'pass_quantity' => 1490,
            'reject_quantity' => 10,
        ]);

        // Problem
        InspectionProblem::create([
            'inspection_report_document_number' => $this->report->document_number,
            'type' => 'NO PROBLEM',
            'time' => '08:00',
            'cycle_time' => 45,
            'remarks' => 'Smooth operational running',
        ]);

        // Quantity
        InspectionQuantity::create([
            'inspection_report_document_number' => $this->report->document_number,
            'output_quantity' => 1500,
            'pass_quantity' => 1490,
            'reject_quantity' => 10,
            'sampling_quantity' => 50,
            'ng_sample_quantity' => 0,
            'pass_rate' => 99.33,
            'reject_rate' => 0.67,
            'ng_sample_rate' => 0.00,
        ]);
    }

    public function test_guest_cannot_view_report_detail(): void
    {
        $response = $this->get(route('inspection-reports.show', $this->report));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_report_detail(): void
    {
        $response = $this->actingAs($this->user)->get(route('inspection-reports.show', $this->report));
        $response->assertOk();
        $response->assertSee('INSP-20261029-SHOW-0001');
        $response->assertSee('PT Show Testing Customer');
        $response->assertSee('HOUSING BRACKET');
        $response->assertSee('PART-SHOW-888');
        $response->assertSee('Buka Semua');
        $response->assertSee('Tutup Semua');
    }

    public function test_show_renders_all_inspection_sections(): void
    {
        Livewire::actingAs($this->user)
            ->test(InspectionShow::class, ['inspection_report' => $this->report])
            ->assertSee('First Piece Inspection')
            ->assertSee('Dimension Data')
            ->assertSee('Second Inspection')
            ->assertSee('Sampling')
            ->assertSee('Shift Problems')
            ->assertSee('Diameter Hole A')
            ->assertSee('1,490')
            ->assertSee('99.3%')
            ->assertSee('0.7%');
    }
}
