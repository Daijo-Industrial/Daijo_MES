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
            'inspection_date' => '2026-10-29',
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
            ->assertSee('PART-999-ABC');
    }

    public function test_filter_by_search_keyword(): void
    {
        InspectionReport::create([
            'document_number' => 'INSP-MATCH-0001',
            'customer' => 'Client A',
            'inspection_date' => '2026-10-29',
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
            'inspection_date' => '2026-10-29',
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

        // 1. Initially both reports are visible (preset all)
        $test = Livewire::actingAs($this->user)
            ->test(InspectionIndex::class)
            ->call('loadData')
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

        // 4. Test Quick Date Presets
        $test->call('setPreset', 'this_month')
            ->call('applyFilters')
            ->assertSet('preset', 'this_month')
            ->call('removeFilter', 'date')
            ->assertSet('preset', 'all');
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
}
