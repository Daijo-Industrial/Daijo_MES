<?php

namespace Tests\Feature\Inspection;

use App\Livewire\Inspection\InspectionIndex;
use App\Models\Inspection\InspectionReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionIndexTest extends TestCase
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
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('inspection-reports.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_index_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('inspection-reports.index'));
        $response->assertOk();
        $response->assertSee('Plastic Injection IPQC Inspection Reports');
    }

    public function test_displays_inspection_reports_in_table(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-20261029-0001-TEST',
            'customer' => 'PT Daijo Industrial',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-999-ABC',
            'part_name' => 'WIDGET ALPHA',
            'weight' => 25.4,
            'weight_uom' => 'g',
            'material' => 'ABS',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => 'CAV-1',
            'machine_number' => 'INJ-01',
            'shift' => 1,
            'operator' => 'Budi',
            'inspector' => 'Siti',
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
            ->assertSee('INSP-20261029-0001-TEST')
            ->assertSee('PT Daijo Industrial')
            ->assertSee('PART-999-ABC')
            ->assertSee('INJ-01');
    }

    public function test_filter_by_search_keyword(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-MATCH-0001',
            'customer' => 'Client A',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-MATCH-123',
            'part_name' => 'Matched Part',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'WHITE',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Insp1',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-OTHER-0002',
            'customer' => 'Client B',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-OTHER-456',
            'part_name' => 'Other Part',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'WHITE',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op2',
            'inspector' => 'Insp2',
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
            ->set('search', 'MATCH')
            ->assertSee('INSP-MATCH-0001')
            ->assertDontSee('INSP-OTHER-0002');
    }

    public function test_csv_export_returns_streamed_response(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-EXPORT-001',
            'customer' => 'Export Client',
            'inspection_date' => '2026-10-29',
            'part_number' => 'EXPORT-PART-01',
            'part_name' => 'Export Item',
            'weight' => 12.5,
            'weight_uom' => 'g',
            'material' => 'POM',
            'color' => 'RED',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M2',
            'shift' => 2,
            'operator' => 'Op3',
            'inspector' => 'Insp3',
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_filter_bar_progressive_disclosure_and_batch_apply(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-SHIFT-1',
            'customer' => 'Customer A',
            'inspection_date' => '2026-11-01',
            'part_number' => 'PART-A',
            'part_name' => 'Part A',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Insp1',
            'status' => 'submitted',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-SHIFT-2',
            'customer' => 'Customer B',
            'inspection_date' => '2026-11-01',
            'part_number' => 'PART-B',
            'part_name' => 'Part B',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 2,
            'operator' => 'Op2',
            'inspector' => 'Insp2',
            'status' => 'draft',
        ]);

        // 1. Initially both current-month reports are visible (default preset: this_month)
        $test = Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
            ->assertSet('preset', 'this_month')
            ->assertSee('INSP-SHIFT-1')
            ->assertSee('INSP-SHIFT-2');

        // 2. Modify draftFilters (shift = 1) and apply batch
        $test->set('draftFilters.shift', '1')
            ->call('applyFilters')
            ->assertSee('INSP-SHIFT-1')
            ->assertDontSee('INSP-SHIFT-2');

        // 3. Remove shift filter pill
        $test->call('removeFilter', 'shift')
            ->assertSee('INSP-SHIFT-1')
            ->assertSee('INSP-SHIFT-2');

        // 4. Test Quick Date Presets & Reset back to this_month
        $test->call('setPreset', 'all')
            ->call('applyFilters')
            ->assertSet('preset', 'all')
            ->call('clearFilters')
            ->assertSet('preset', 'this_month')
            ->call('removeFilter', 'date')
            ->assertSet('preset', 'all');
    }

    public function test_default_preset_is_this_month_and_filters_out_previous_month(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-CURR-MONTH',
            'customer' => 'Customer A',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-CURR',
            'part_name' => 'Current Month Part',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'MC-01',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Insp1',
            'status' => 'submitted',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-PREV-MONTH',
            'customer' => 'Customer A',
            'inspection_date' => now()->subMonthNoOverflow()->toDateString(),
            'part_number' => 'PART-PREV',
            'part_name' => 'Previous Month Part',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'MC-01',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Insp1',
            'status' => 'submitted',
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
            ->assertSet('preset', 'this_month')
            ->assertSee('INSP-CURR-MONTH')
            ->assertDontSee('INSP-PREV-MONTH');

        $test->call('setPreset', 'last_month')
            ->call('applyFilters')
            ->assertSet('preset', 'last_month')
            ->assertSee('INSP-PREV-MONTH')
            ->assertDontSee('INSP-CURR-MONTH');
    }

    public function test_filter_by_machine_number_inspector_and_customer_dropdowns(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-FILTER-1',
            'customer' => 'PT Alpha',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-1',
            'part_name' => 'Part 1',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'INJ-01',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Andi',
            'status' => 'submitted',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-FILTER-2',
            'customer' => 'PT Beta',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-2',
            'part_name' => 'Part 2',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'INJ-02',
            'shift' => 1,
            'operator' => 'Op2',
            'inspector' => 'Budi',
            'status' => 'draft',
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData');

        $test->set('draftFilters.machine_number', 'INJ-01')
            ->call('applyFilters')
            ->assertSee('INSP-FILTER-1')
            ->assertDontSee('INSP-FILTER-2');

        $test->call('removeFilter', 'machine_number')
            ->set('draftFilters.inspector', 'Budi')
            ->call('applyFilters')
            ->assertSee('INSP-FILTER-2')
            ->assertDontSee('INSP-FILTER-1');

        $test->call('removeFilter', 'inspector')
            ->set('draftFilters.customer', 'PT Alpha')
            ->call('applyFilters')
            ->assertSee('INSP-FILTER-1')
            ->assertDontSee('INSP-FILTER-2');
    }

    public function test_global_search_matches_machine_number_and_inspector(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-SRCH-1',
            'customer' => 'PT Alpha',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-1',
            'part_name' => 'Part 1',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'INJ-99',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'Siti',
            'status' => 'submitted',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-SRCH-2',
            'customer' => 'PT Beta',
            'inspection_date' => now()->toDateString(),
            'part_number' => 'PART-2',
            'part_name' => 'Part 2',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'INJ-02',
            'shift' => 1,
            'operator' => 'Op2',
            'inspector' => 'Rina',
            'status' => 'submitted',
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
            ->set('search', 'INJ-99')
            ->assertSee('INSP-SRCH-1')
            ->assertDontSee('INSP-SRCH-2')
            ->set('search', 'Rina')
            ->assertSee('INSP-SRCH-2')
            ->assertDontSee('INSP-SRCH-1');
    }

    public function test_create_report_button_redirects_directly_when_no_draft_exists(): void
    {
        session()->flush();

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('handleCreateClick')
            ->assertSet('showDraftModal', false)
            ->assertRedirect(route('inspection-reports.create'));
    }

    public function test_create_report_button_opens_modal_when_pending_draft_exists(): void
    {
        $docNum = 'INSP-MODAL-TEST-001';
        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'customer' => 'PT Astra Daihatsu Motor',
                'inspection_date' => '2026-11-17',
                'part_number' => 'PART-MODAL-001',
                'machine_number' => 'MC-01',
                'shift' => 1,
                'operator' => 'Budi',
                'inspector' => 'Rini',
            ],
            'lastStepVisited' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('handleCreateClick')
            ->assertSet('showDraftModal', true)
            ->assertSee('Draft Ditemukan')
            ->assertSee($docNum)
            ->assertSee('Lanjutkan Draft')
            ->assertSee('Mulai Laporan Baru');
    }

    public function test_modal_start_new_report_clears_session_and_redirects_with_new_flag(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-CLEAR-001',
                'part_number' => 'PART-CLEAR',
            ],
            'lastStepVisited' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->set('showDraftModal', true)
            ->call('startNewReport')
            ->assertSet('showDraftModal', false)
            ->assertRedirect(route('inspection-reports.create', ['new' => 1]));

        $this->assertNull(session('stepHeaderSaved'));
    }

    public function test_modal_continue_draft_redirects_with_continue_flag(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-CONT-001',
                'part_number' => 'PART-CONT',
            ],
            'lastStepVisited' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->set('showDraftModal', true)
            ->call('continueDraft')
            ->assertSet('showDraftModal', false)
            ->assertRedirect(route('inspection-reports.create', ['continue' => 1]));
    }

    public function test_user_can_delete_draft_report_and_its_children(): void
    {
        $docNum = 'INSP-DRAFT-DEL-001';
        $report = InspectionReport::create([
            'document_number' => $docNum,
            'customer' => 'Customer Draft',
            'inspection_date' => '2026-11-18',
            'part_number' => 'PART-DRAFT-DEL',
            'part_name' => 'Draft Part to Delete',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'QC-Insp',
            'status' => 'draft',
        ]);

        $detail = \App\Models\Inspection\DetailInspectionReport::create([
            'inspection_report_document_number' => $docNum,
            'document_number' => 'DIR-' . $docNum . '-1',
            'period' => 1,
            'start_datetime' => now(),
            'end_datetime' => now()->addHour(),
        ]);

        \App\Models\Inspection\FirstInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'appearance' => 'OK',
            'weight' => 10.0,
            'weight_uom' => 'g',
        ]);

        $second = \App\Models\Inspection\SecondInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'document_number' => 'SEC-' . $detail->document_number,
        ]);

        \App\Models\Inspection\InspectionPackaging::create([
            'second_inspection_document_number' => $second->document_number,
            'snp' => 100,
            'box_label' => 'LABEL',
            'judgement' => 'OK',
        ]);

        \App\Models\Inspection\InspectionDimension::create([
            'inspection_report_document_number' => $docNum,
            'actual_value' => 5.25,
        ]);

        \App\Models\Inspection\InspectionProblem::create([
            'inspection_report_document_number' => $docNum,
            'type' => 'Setting',
            'time' => '10:00:00',
            'cycle_time' => 30,
        ]);

        \App\Models\Inspection\InspectionQuantity::create([
            'inspection_report_document_number' => $docNum,
            'output_quantity' => 100,
            'pass_quantity' => 95,
            'pass_rate' => 95.0,
            'reject_quantity' => 5,
            'reject_rate' => 5.0,
            'sampling_quantity' => 5,
            'ng_sample_quantity' => 0,
            'ng_sample_rate' => 0.0,
        ]);

        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'part_number' => 'PART-DRAFT-DEL',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('deleteDraft', $report->id)
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('inspection_reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('detail_inspection_reports', ['document_number' => $detail->document_number]);
        $this->assertDatabaseMissing('first_inspections', ['detail_inspection_report_document_number' => $detail->document_number]);
        $this->assertDatabaseMissing('second_inspections', ['document_number' => $second->document_number]);
        $this->assertDatabaseMissing('inspection_packagings', ['second_inspection_document_number' => $second->document_number]);
        $this->assertDatabaseMissing('inspection_dimensions', ['inspection_report_document_number' => $docNum]);
        $this->assertDatabaseMissing('inspection_problems', ['inspection_report_document_number' => $docNum]);
        $this->assertDatabaseMissing('inspection_quantities', ['inspection_report_document_number' => $docNum]);
        $this->assertNull(session('stepHeaderSaved'));
    }

    public function test_user_cannot_delete_submitted_report(): void
    {
        $report = InspectionReport::create([
            'document_number' => 'INSP-SUBMITTED-001',
            'customer' => 'Customer Submitted',
            'inspection_date' => '2026-11-18',
            'part_number' => 'PART-SUBMITTED',
            'part_name' => 'Submitted Part',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'QC-Insp',
            'status' => 'submitted',
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('deleteDraft', $report->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('inspection_reports', ['id' => $report->id]);
    }

    public function test_unauthorized_user_cannot_access_inspection_reports(): void
    {
        $operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $operator = User::factory()->create([
            'role_id' => $operatorRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($operator)->get(route('inspection-reports.index'));
        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_draft_report(): void
    {
        $operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $operator = User::factory()->create([
            'role_id' => $operatorRole->id,
            'email_verified_at' => now(),
        ]);

        $report = InspectionReport::create([
            'document_number' => 'INSP-UNAUTH-DEL',
            'customer' => 'Customer A',
            'inspection_date' => '2026-11-18',
            'part_number' => 'PART-A',
            'part_name' => 'Part A',
            'weight' => 10.0,
            'weight_uom' => 'g',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'operator' => 'Op1',
            'inspector' => 'QC-Insp',
            'status' => 'draft',
        ]);

        Livewire::actingAs($operator)
            ->test(InspectionIndex::class)
            ->call('deleteDraft', $report->id)
            ->assertForbidden();

        $this->assertDatabaseHas('inspection_reports', ['id' => $report->id]);
    }
}
