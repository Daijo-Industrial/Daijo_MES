<?php

namespace Tests\Feature\Inspection;

use App\Livewire\Inspection\InspectionForm;
use App\Livewire\Inspection\InspectionForm\FinalSubmit;
use App\Livewire\Inspection\InspectionForm\StepDetail;
use App\Livewire\Inspection\InspectionForm\StepDimension;
use App\Livewire\Inspection\InspectionForm\StepFirst;
use App\Livewire\Inspection\InspectionForm\StepHeader;
use App\Livewire\Inspection\InspectionForm\StepJudgement;
use App\Livewire\Inspection\InspectionForm\StepPackaging;
use App\Livewire\Inspection\InspectionForm\StepPeriodProblem;
use App\Livewire\Inspection\InspectionForm\StepProblem;
use App\Livewire\Inspection\InspectionForm\StepSampling;
use App\Livewire\Inspection\InspectionForm\StepSecond;
use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\Inspection\InspectionReport;
use App\Models\MachineJob;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use App\Models\OperatorUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionFormTest extends TestCase
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

    public function test_guest_cannot_access_create_form(): void
    {
        $response = $this->get(route('inspection-reports.create'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('inspection-reports.create'));
        $response->assertOk();
        $response->assertSee('Form Input Plastic Injection IPQC');
        $response->assertSee('Header & Part Info');
    }

    public function test_step_header_validates_and_saves_to_session(): void
    {
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('customer', 'PT Honda Prospect Motor')
            ->set('inspection_date', '2026-10-29')
            ->set('part_number', 'PART-WIZARD-01')
            ->set('part_name', 'BUMPER CLIP')
            ->set('weight', 12.5)
            ->set('weight_uom', 'g')
            ->set('material', 'PP')
            ->set('color', 'BLACK')
            ->set('tool_number_or_cav_number', 'CAV-1')
            ->set('machine_number', 'INJ-04')
            ->set('shift', 1)
            ->set('operator', 'Wahyu')
            ->set('inspector', 'Rini')
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertDispatched('stepHeaderSaved')
            ->assertDispatched('nextStep');

        $this->assertNotEmpty(session('stepHeaderSaved'));
        $this->assertEquals('PT Honda Prospect Motor', session('stepHeaderSaved.customer'));
        $this->assertEquals('PART-WIZARD-01', session('stepHeaderSaved.part_number'));
    }

    public function test_end_to_end_inspection_report_submission_persists_to_database(): void
    {
        $docNum = 'INSP-20261029-E2E-999';
        $detailDocNum = 'DETAIL-20261029-P1-999';
        $secondDocNum = 'SECOND-20261029-P1-999';

        // 1. Simulate Session state as filled across steps
        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'customer' => 'PT Astra Daihatsu Motor',
                'inspection_date' => '2026-10-29',
                'part_number' => 'PART-E2E-001',
                'part_name' => 'KNOB SHIFT',
                'weight' => 30.5,
                'weight_uom' => 'g',
                'material' => 'ABS',
                'color' => 'BLACK',
                'tool_number_or_cav_number' => '1',
                'machine_number' => 'MC-02',
                'shift' => 1,
                'operator' => 'Slamet',
                'inspector' => 'Dewi',
            ],
            'stepDetailSaved' => [
                'period' => 1,
                'details' => [
                    'p1' => [
                        'inspection_report_document_number' => $docNum,
                        'document_number' => $detailDocNum,
                        'period' => 1,
                        'start_datetime' => '2026-10-29 07:30:00',
                        'end_datetime' => '2026-10-29 09:30:00',
                    ],
                ],
                'first_inspections' => [
                    'p1' => [
                        'detail_inspection_report_document_number' => $detailDocNum,
                        'appearance' => 'OK',
                        'weight' => 30.5,
                        'weight_uom' => 'g',
                        'fitting_test' => 'OK',
                    ],
                ],
                'dimensions' => [
                    'p1' => [
                        [
                            'row_key' => 'row-1',
                            'inspection_report_document_number' => $docNum,
                            'lower_limit' => 10.0,
                            'upper_limit' => 10.5,
                            'limit_uom' => 'mm',
                            'actual_value' => 10.2,
                            'judgement' => 'OK',
                            'area' => 'Diameter',
                            'start_datetime' => '2026-10-29 07:30:00',
                            'end_datetime' => '2026-10-29 09:30:00',
                        ],
                    ],
                ],
                'second_inspections' => [
                    'p1' => [
                        'detail_inspection_report_document_number' => $detailDocNum,
                        'document_number' => $secondDocNum,
                        'lot_size_quantity' => 1000,
                        'skipLotSize' => false,
                    ],
                ],
                'samples' => [
                    'p1' => [
                        [
                            'row_key' => 'sample-1',
                            'second_inspection_document_number' => $secondDocNum,
                            'quantity' => 32,
                            'box_label' => 'BOX-1',
                            'appearance' => 'OK',
                        ],
                    ],
                ],
                'packagings' => [
                    'p1' => [
                        [
                            'row_key' => 'pack-1',
                            'second_inspection_document_number' => $secondDocNum,
                            'snp' => 100,
                            'box_label' => 'BOX-1',
                            'judgement' => 'OK',
                        ],
                    ],
                ],
                'judgements' => [
                    'p1' => [
                        'detail_inspection_report_document_number' => $detailDocNum,
                        'pass_quantity' => 995,
                        'reject_quantity' => 5,
                    ],
                ],
            ],
            'stepProblemSaved' => [
                [
                    'row_key' => 'prob-1',
                    'inspection_report_document_number' => $docNum,
                    'time' => '08:15',
                    'cycle_time' => 50,
                    'type' => 'MACHINE PROBLEM',
                    'remarks' => 'Normal condition',
                ],
            ],
        ]);

        // 2. Submit via FinalSubmit
        Livewire::actingAs($this->user)
            ->test(FinalSubmit::class)
            ->assertSee('KNOB SHIFT')
            ->assertSee('PART-E2E-001')
            ->call('submit')
            ->assertRedirect(route('inspection-reports.index'));

        // 3. Verify Database Records
        $this->assertDatabaseHas('inspection_reports', [
            'document_number' => $docNum,
            'customer' => 'PT Astra Daihatsu Motor',
            'part_number' => 'PART-E2E-001',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('detail_inspection_reports', [
            'document_number' => $detailDocNum,
            'period' => 1,
        ]);

        $this->assertDatabaseHas('first_inspections', [
            'detail_inspection_report_document_number' => $detailDocNum,
            'appearance' => 'OK',
        ]);

        $this->assertDatabaseHas('inspection_dimensions', [
            'inspection_report_document_number' => $docNum,
            'area' => 'Diameter',
        ]);

        $this->assertDatabaseHas('second_inspections', [
            'document_number' => $secondDocNum,
            'lot_size_quantity' => 1000,
        ]);

        $this->assertDatabaseHas('inspection_samplings', [
            'second_inspection_document_number' => $secondDocNum,
            'quantity' => 32,
        ]);

        $this->assertDatabaseHas('inspection_packagings', [
            'second_inspection_document_number' => $secondDocNum,
            'snp' => 100,
        ]);

        $this->assertDatabaseHas('inspection_judgements', [
            'detail_inspection_report_document_number' => $detailDocNum,
            'pass_quantity' => 995,
            'reject_quantity' => 5,
        ]);

        $this->assertDatabaseHas('inspection_problems', [
            'inspection_report_document_number' => $docNum,
            'type' => 'MACHINE PROBLEM',
        ]);

        $this->assertDatabaseHas('inspection_quantities', [
            'inspection_report_document_number' => $docNum,
            'output_quantity' => 1000,
            'pass_quantity' => 995,
            'reject_quantity' => 5,
        ]);

        // 4. Verify session cleanup
        $this->assertNull(session('stepHeaderSaved'));
        $this->assertNull(session('stepDetailSaved'));
        $this->assertNull(session('stepProblemSaved'));
    }

    public function test_step_header_resolves_single_daily_item_code_and_operator_from_hourly_remark(): void
    {
        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machineUser = User::factory()->create([
            'name' => 'INJ-04',
            'role_id' => $opRole->id,
        ]);

        OperatorUser::create([
            'name' => 'AHMAD',
            'password' => 'secret123',
            'department' => '390',
            'position' => 'Operator',
        ]);

        $dic = DailyItemCode::create([
            'user_id' => $machineUser->id,
            'item_code' => 'PART-WIZARD-01',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => '2026-10-29',
        ]);

        HourlyRemark::create([
            'dic_id' => $dic->id,
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'target' => 50,
            'actual' => 50,
            'pic' => 'AHMAD',
        ]);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('inspection_date', '2026-10-29')
            ->set('shift', 1)
            ->set('part_number', 'PART-WIZARD-01')
            ->call('resolveDailyItemCode')
            ->assertSet('dic_id', $dic->id)
            ->assertSet('machine_number', 'INJ-04')
            ->assertSet('operator', 'AHMAD');
    }

    public function test_step_header_resolves_operator_from_machine_job_when_no_hourly_remark(): void
    {
        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machineUser = User::factory()->create([
            'name' => 'INJ-04',
            'role_id' => $opRole->id,
        ]);

        OperatorUser::create([
            'name' => 'SITI',
            'password' => 'secret123',
            'department' => '390',
            'position' => 'Operator',
        ]);

        $dic = DailyItemCode::create([
            'user_id' => $machineUser->id,
            'item_code' => 'PART-WIZARD-01',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => '2026-10-29',
        ]);

        MachineJob::create([
            'user_id' => $machineUser->id,
            'dic_id' => $dic->id,
            'employee_name' => 'SITI, BUDI',
        ]);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('inspection_date', '2026-10-29')
            ->set('shift', 1)
            ->set('part_number', 'PART-WIZARD-01')
            ->call('resolveDailyItemCode')
            ->assertSet('dic_id', $dic->id)
            ->assertSet('machine_number', 'INJ-04')
            ->assertSet('operator', 'SITI');
    }

    public function test_step_header_handles_multiple_dics_with_selection(): void
    {
        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machineUser1 = User::factory()->create(['name' => 'INJ-01', 'role_id' => $opRole->id]);
        $machineUser2 = User::factory()->create(['name' => 'INJ-02', 'role_id' => $opRole->id]);

        $dic1 = DailyItemCode::create([
            'user_id' => $machineUser1->id,
            'item_code' => 'PART-WIZARD-01',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => '2026-10-29',
        ]);

        $dic2 = DailyItemCode::create([
            'user_id' => $machineUser2->id,
            'item_code' => 'PART-WIZARD-01',
            'quantity' => 300,
            'shift' => 1,
            'schedule_date' => '2026-10-29',
        ]);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('inspection_date', '2026-10-29')
            ->set('shift', 1)
            ->set('part_number', 'PART-WIZARD-01')
            ->call('resolveDailyItemCode')
            ->assertCount('availableDics', 2)
            ->assertSet('dic_id', null)
            ->call('selectDic', $dic2->id)
            ->assertSet('dic_id', $dic2->id)
            ->assertSet('machine_number', 'INJ-02');
    }

    public function test_step_header_resolves_scanned_operator_from_machine_job_immediately(): void
    {
        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machineUser = User::factory()->create([
            'name' => 'INJ-05',
            'role_id' => $opRole->id,
        ]);

        OperatorUser::create([
            'name' => 'JOKO',
            'password' => 'secret123',
            'department' => '390',
            'position' => 'Operator',
        ]);

        $dic = DailyItemCode::create([
            'user_id' => $machineUser->id,
            'item_code' => 'PART-WIZARD-01',
            'quantity' => 1000,
            'shift' => 2,
            'schedule_date' => '2026-10-30',
        ]);

        // Simulates operator picking DIC and scanning identity in DashboardController
        MachineJob::create([
            'user_id' => $machineUser->id,
            'dic_id' => $dic->id,
            'shift' => 2,
            'item_code' => 'PART-WIZARD-01',
            'employee_name' => 'JOKO',
        ]);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('inspection_date', '2026-10-30')
            ->set('shift', 2)
            ->set('part_number', 'PART-WIZARD-01')
            ->call('resolveDailyItemCode')
            ->assertSet('dic_id', $dic->id)
            ->assertSet('machine_number', 'INJ-05')
            ->assertSet('operator', 'JOKO');
    }

    public function test_step_header_validates_machine_number_is_mandatory_and_in_valid_machines(): void
    {
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('customer', 'PT Honda Prospect Motor')
            ->set('inspection_date', '2026-10-29')
            ->set('part_number', 'PART-WIZARD-01')
            ->set('part_name', 'BUMPER CLIP')
            ->set('weight', 12.5)
            ->set('weight_uom', 'g')
            ->set('material', 'PP')
            ->set('color', 'BLACK')
            ->set('tool_number_or_cav_number', 'CAV-1')
            ->set('machine_number', null)
            ->set('shift', 1)
            ->set('operator', 'Wahyu')
            ->set('inspector', 'Rini')
            ->call('saveStep')
            ->assertHasErrors(['machine_number' => 'required']);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('customer', 'PT Honda Prospect Motor')
            ->set('inspection_date', '2026-10-29')
            ->set('part_number', 'PART-WIZARD-01')
            ->set('part_name', 'BUMPER CLIP')
            ->set('weight', 12.5)
            ->set('weight_uom', 'g')
            ->set('material', 'PP')
            ->set('color', 'BLACK')
            ->set('tool_number_or_cav_number', 'CAV-1')
            ->set('machine_number', 'MESIN-TIDAK-VALID-XYZ')
            ->set('shift', 1)
            ->set('operator', 'Wahyu')
            ->set('inspector', 'Rini')
            ->call('saveStep')
            ->assertHasErrors(['machine_number']);
    }

    public function test_step_header_auto_uppercases_material_and_color(): void
    {
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('material', 'polypropylene')
            ->assertSet('material', 'POLYPROPYLENE')
            ->set('color', 'matte black')
            ->assertSet('color', 'MATTE BLACK');
    }

    public function test_step_header_resets_auto_fill_badge_when_operator_is_edited_manually(): void
    {
        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machineUser = User::factory()->create([
            'name' => 'INJ-06',
            'role_id' => $opRole->id,
        ]);

        $dic = DailyItemCode::create([
            'user_id' => $machineUser->id,
            'item_code' => 'PART-OP-TEST',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => '2026-10-31',
        ]);

        MachineJob::create([
            'user_id' => $machineUser->id,
            'dic_id' => $dic->id,
            'shift' => 1,
            'item_code' => 'PART-OP-TEST',
            'employee_name' => 'AUTO-OPERATOR-1',
        ]);

        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('inspection_date', '2026-10-31')
            ->set('shift', 1)
            ->set('part_number', 'PART-OP-TEST')
            ->call('resolveDailyItemCode')
            ->assertSet('operator', 'AUTO-OPERATOR-1')
            ->assertSet('is_operator_auto_filled', true)
            ->assertSee('Auto-fill DIC #' . $dic->id)
            ->set('operator', 'MANUAL-OPERATOR-2')
            ->assertSet('is_operator_auto_filled', false)
            ->assertDontSee('Auto-fill DIC #' . $dic->id);
    }

    public function test_step_header_operator_suggestions_only_from_operator_users_table(): void
    {
        OperatorUser::create([
            'name' => 'OPERATOR-SUGGESTION-VALID',
            'password' => 'secret123',
            'department' => '390',
            'position' => 'Operator',
        ]);

        InspectionReport::create([
            'report_number' => 'INSP-HISTORICAL-999',
            'customer' => 'TEST CUST',
            'inspection_date' => '2026-10-01',
            'part_number' => 'PART-XYZ',
            'part_name' => 'XYZ',
            'material' => 'PP',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'INJ-01',
            'shift' => 1,
            'operator' => 'OPERATOR-OLD-HISTORICAL',
            'inspector' => 'INSPECTOR-1',
            'status' => 'draft',
        ]);

        $test = Livewire::actingAs($this->user)->test(StepHeader::class);
        $suggestions = $test->viewData('operatorSuggestions');

        $this->assertContains('OPERATOR-SUGGESTION-VALID', $suggestions);
        $this->assertNotContains('OPERATOR-OLD-HISTORICAL', $suggestions);
    }

    public function test_step_header_hides_customer_input_when_part_has_master_customer(): void
    {
        MasterCustomerDelivery::create([
            'customer_code' => 'CUST-HONDA',
            'customer_name' => 'HONDA PROSPECT MOTOR',
        ]);

        MasterListItem::create([
            'item_code' => 'PART-WITH-CUST',
            'item_name' => 'FRONT BUMPER',
            'customer_code' => 'CUST-HONDA',
        ]);

        MasterListItem::create([
            'item_code' => 'PART-WITHOUT-CUST',
            'item_name' => 'GENERIC CLIP',
            'customer_code' => null,
        ]);

        // When part has master customer
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->call('selectPartItem', 'PART-WITH-CUST')
            ->assertSet('has_master_customer', true)
            ->assertSet('customer', 'HONDA PROSPECT MOTOR')
            ->assertDontSee('PILIH / KETIK CUSTOMER...');

        // When part does not have master customer
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->call('selectPartItem', 'PART-WITHOUT-CUST')
            ->assertSet('has_master_customer', false)
            ->assertSet('customer', null)
            ->assertSee('PILIH / KETIK CUSTOMER...');
    }

    public function test_step_detail_renders_without_array_offset_on_null_error(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-TEST-NULL-01',
                'customer' => 'PT ASTRA',
                'inspection_date' => '2026-11-01',
                'part_number' => 'PART-TEST-01',
                'part_name' => 'TEST PART',
                'shift' => 1,
                'operator' => 'OP-1',
                'inspector' => 'INSP-1',
            ],
            // Simulate saved_periods flag set without subcomponent data filled (or partial)
            'stepDetailSaved' => [
                'saved_periods' => ['p1' => true],
                'details' => [
                    'p1' => [
                        'document_number' => 'DETAIL-TEST-01',
                        'inspection_report_document_number' => 'INSP-TEST-NULL-01',
                        'period' => 1,
                    ],
                ],
                'first_inspections' => null,
                'dimensions' => null,
                'second_inspections' => null,
                'samples' => null,
                'packagings' => null,
                'judgements' => null,
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(StepDetail::class)
            ->assertOk()
            ->assertSee('Periode 1')
            ->assertSee('Selesai Tersimpan')
            ->assertSee('1. First Piece')
            ->assertSee('Edit Periode 1');
    }

    public function test_step_detail_renders_period_hub_and_navigates_to_sub_wizard(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-TEST-UNIFIED-01',
                'customer' => 'PT HONDA',
                'inspection_date' => '2026-11-02',
                'part_number' => 'PART-UNIFIED-01',
                'part_name' => 'UNIFIED PART',
                'shift' => 1,
                'operator' => 'OP-UNIFIED',
                'inspector' => 'INSP-UNIFIED',
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(StepDetail::class)
            ->assertOk()
            ->assertSet('isWizardActive', false)
            ->assertSee('Pilih Periode Inspeksi Shift 1')
            ->assertSee('Periode 1')
            ->assertSee('Periode 2')
            ->assertSee('Periode 3')
            ->assertSee('Periode 4')
            ->assertSee('Mulai Inspeksi Periode 1')
            ->assertSee('Kembali ke Step 1 (Header)')
            // Start inspecting Period 1
            ->call('startPeriod', 1)
            ->assertSet('isWizardActive', true)
            ->assertSet('period', 1)
            ->assertSet('subStep', 1)
            ->assertSee('Inspeksi Periode 1')
            ->assertSee('Simpan Draft & Kembali ke Hub')
            ->assertSee('1. First Piece')
            ->assertSee('2. Dimensi')
            ->assertSee('3. Second & Sampling')
            ->assertSee('4. Judgement & Output')
            ->assertSee('Lanjut ke 2. Dimensi')
            // Return to Hub
            ->call('exitToHub')
            ->assertSet('isWizardActive', false)
            ->assertSee('Pilih Periode Inspeksi Shift 1');
    }

    public function test_step_detail_sub_wizard_sequential_flow_and_completion(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-TEST-WIZARD-01',
                'customer' => 'PT DAIJO',
                'inspection_date' => '2026-11-03',
                'part_number' => 'PART-WIZARD-01',
                'part_name' => 'WIZARD PART',
                'shift' => 1,
                'operator' => 'OP-WIZARD',
                'inspector' => 'INSP-WIZARD',
            ],
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(StepDetail::class)
            ->assertOk()
            ->call('startPeriod', 1)
            ->assertSet('isWizardActive', true)
            ->assertSet('subStep', 1)
            // Step 1 -> Step 2
            ->call('nextSubStep')
            ->assertSet('subStep', 2)
            ->assertSee('Lanjut ke 3. Second & Sampling')
            // Step 2 -> Step 3
            ->call('nextSubStep')
            ->assertSet('subStep', 3)
            ->assertSee('Lanjut ke 4. Judgement & Output')
            // Step 3 -> Step 4
            ->call('nextSubStep')
            ->assertSet('subStep', 4)
            ->assertSee('Simpan Periode 1 & Selesai')
            // Finish Period
            ->call('finishPeriod')
            ->assertSet('isWizardActive', false)
            ->assertDispatched('stepDetailSaved')
            ->assertSee('Selesai Tersimpan')
            ->assertSee('Edit Periode 1')
            // Proceed to Step 3 should dispatch nextStep
            ->call('proceedToStep3')
            ->assertDispatched('nextStep');

        $savedPeriods = session('stepDetailSaved.saved_periods', []);
        $this->assertTrue(! empty($savedPeriods['p1']));
    }

    public function test_inspection_form_stepper_is_non_clickable_and_shows_only_titles(): void
    {
        Livewire::actingAs($this->user)
            ->test(InspectionForm::class)
            ->assertOk()
            ->assertSee('Header & Part Info')
            ->assertSee('Detail Periode')
            ->assertSee('Masalah & Loss Time')
            ->assertSee('Review & Submit')
            ->assertDontSee('Customer, Part, Mesin & Shift')
            ->assertDontSee('First, Dimensi, Sampling & Hasil')
            ->assertDontSee('Daftar kendala selama shift')
            ->assertDontSee('Verifikasi dan simpan laporan')
            ->assertDontSee('wire:click="setStep');
    }

    public function test_inspection_form_accurately_unifies_filled_steps_based_on_saved_data(): void
    {
        // 1. Initially nothing is saved
        session()->forget(['stepHeaderSaved', 'stepDetailSaved', 'stepProblemSaved']);

        $test = Livewire::actingAs($this->user)->test(InspectionForm::class);
        $this->assertFalse($test->get('filledSteps')[1]);
        $this->assertFalse($test->get('filledSteps')[2]);
        $this->assertFalse($test->get('filledSteps')[3]);

        // 2. Only Step 1 Header is saved
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-STEP-001',
                'customer' => 'PT ASTRA',
                'inspection_date' => '2026-11-03',
                'part_number' => 'PART-001',
                'part_name' => 'BUMPER',
                'machine_number' => 'INJ-01',
                'shift' => 1,
                'operator' => 'OP-1',
                'inspector' => 'INSP-1',
            ],
            // Step 2 visited with detail metadata but NO saved period
            'stepDetailSaved' => [
                'details' => [
                    'p1' => [
                        'document_number' => 'DETAIL-001',
                        'period' => 1,
                    ],
                ],
            ],
        ]);

        $test = Livewire::actingAs($this->user)->test(InspectionForm::class);
        $this->assertTrue($test->get('filledSteps')[1]);
        $this->assertFalse($test->get('filledSteps')[2]);

        // 3. Period 1 is saved
        session([
            'stepDetailSaved' => [
                'saved_periods' => ['p1' => true],
                'details' => [
                    'p1' => [
                        'document_number' => 'DETAIL-001',
                        'period' => 1,
                    ],
                ],
            ],
        ]);

        $test = Livewire::actingAs($this->user)->test(InspectionForm::class);
        $this->assertTrue($test->get('filledSteps')[2]);

        // 4. Step 3 Problem is saved
        session([
            'stepProblemSaved' => [
                ['time' => '08:00', 'type' => 'Setting Mold', 'cycle_time' => 30, 'remarks' => 'Normal'],
            ],
        ]);

        $test = Livewire::actingAs($this->user)->test(InspectionForm::class);
        $this->assertTrue($test->get('filledSteps')[3]);
    }

    public function test_step_header_persists_draft_to_database(): void
    {
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('customer', 'PT HONDA')
            ->set('part_number', 'PART-DRAFT-001')
            ->set('part_name', 'DRAFT PART')
            ->set('weight', 25.5)
            ->set('weight_uom', 'g')
            ->set('material', 'PP')
            ->set('color', 'BLACK')
            ->set('tool_number_or_cav_number', 'CAV-01')
            ->set('machine_number', 'INJ-01')
            ->set('shift', 1)
            ->set('operator', 'OP-DRAFT')
            ->set('inspector', 'INSP-DRAFT')
            ->call('saveStep')
            ->assertDispatched('stepHeaderSaved');

        $this->assertDatabaseHas('inspection_reports', [
            'part_number' => 'PART-DRAFT-001',
            'status' => 'draft',
        ]);
    }

    public function test_inspection_form_resumes_draft_from_database(): void
    {
        $report = InspectionReport::create([
            'document_number' => 'INSP-RESUME-001',
            'customer' => 'PT HONDA',
            'inspection_date' => '2026-11-13',
            'part_number' => 'PART-RESUME-001',
            'part_name' => 'RESUME PART',
            'weight' => 30.0,
            'weight_uom' => 'g',
            'material' => 'ABS',
            'color' => 'WHITE',
            'tool_number_or_cav_number' => 'CAV-02',
            'machine_number' => 'INJ-02',
            'shift' => 1,
            'operator' => 'OP-RESUME',
            'inspector' => 'INSP-RESUME',
            'status' => 'draft',
        ]);

        DetailInspectionReport::create([
            'inspection_report_document_number' => 'INSP-RESUME-001',
            'document_number' => 'DETAIL-RESUME-P1',
            'period' => 1,
            'start_datetime' => '2026-11-13 07:30:00',
            'end_datetime' => '2026-11-13 09:30:00',
        ]);

        FirstInspection::create([
            'detail_inspection_report_document_number' => 'DETAIL-RESUME-P1',
            'appearance' => 'OK',
            'weight' => 30.0,
            'weight_uom' => 'g',
            'fitting_test' => 'OK',
        ]);

        SecondInspection::create([
            'detail_inspection_report_document_number' => 'DETAIL-RESUME-P1',
            'document_number' => 'SEC-RESUME-P1',
            'lot_size_quantity' => 500,
        ]);

        InspectionJudgement::create([
            'detail_inspection_report_document_number' => 'DETAIL-RESUME-P1',
            'pass_quantity' => 495,
            'reject_quantity' => 5,
        ]);

        // Clear session to simulate brand new browser session / crash recovery
        session()->flush();

        Livewire::actingAs($this->user)
            ->test(InspectionForm::class, ['report' => $report->id])
            ->assertOk()
            ->assertSet('inspection_report_document_number', 'INSP-RESUME-001');

        $this->assertEquals('INSP-RESUME-001', session('stepHeaderSaved.document_number'));
        $this->assertEquals('PART-RESUME-001', session('stepHeaderSaved.part_number'));
        $this->assertTrue(session('stepDetailSaved.saved_periods.p1'));
    }

    public function test_step_header_can_resave_and_proceed_after_page_reload(): void
    {
        // 1. Initial save of StepHeader
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->set('customer', 'PT HONDA')
            ->set('part_number', 'PART-DRAFT-001')
            ->set('part_name', 'DRAFT PART')
            ->set('weight', 25.5)
            ->set('weight_uom', 'g')
            ->set('material', 'PP')
            ->set('color', 'BLACK')
            ->set('tool_number_or_cav_number', 'CAV-01')
            ->set('machine_number', 'INJ-01')
            ->set('shift', 1)
            ->set('operator', 'OP-DRAFT')
            ->set('inspector', 'INSP-DRAFT')
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertDispatched('stepHeaderSaved')
            ->assertDispatched('nextStep');

        $docNum = session('stepHeaderSaved.document_number');
        $this->assertNotEmpty($docNum);

        // 2. Simulate page reload where user mounts StepHeader again with the existing document_number in session
        Livewire::actingAs($this->user)
            ->test(StepHeader::class)
            ->assertSet('document_number', $docNum)
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertDispatched('stepHeaderSaved')
            ->assertDispatched('nextStep');

        // 3. Verify InspectionForm advances to Step 2
        $form = Livewire::actingAs($this->user)
            ->test(InspectionForm::class);
        $this->assertTrue($form->get('filledSteps')[1]);
    }

    public function test_accidentally_adding_and_removing_dimension_does_not_cause_data_truncation(): void
    {
        $docNum = 'INSP-DIM-TEST-001';
        session(['stepHeaderSaved' => ['document_number' => $docNum, 'shift' => 1]]);
        session(['stepDetailSaved' => ['period' => 1]]);

        // 1. Mutator protection: setting empty string must convert to null
        $dim = new \App\Models\Inspection\InspectionDimension();
        $dim->lower_limit = '';
        $dim->upper_limit = '';
        $dim->actual_value = '';
        $this->assertNull($dim->lower_limit);
        $this->assertNull($dim->upper_limit);
        $this->assertNull($dim->actual_value);

        // 2. Test StepDimension add and remove
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Inspection\InspectionForm\StepDimension::class, [
                'inspection_report_document_number' => $docNum,
            ])
            ->call('addDimension')
            ->assertCount('dimensions', 1)
            ->call('removeDimension', 0)
            ->assertCount('dimensions', 0);

        // Verify session was updated to empty array
        $this->assertSame([], session('stepDetailSaved.dimensions.p1'));

        // 3. Persisting period to database must not throw Data truncated warning
        $stepDetail = new \App\Livewire\Inspection\InspectionForm\StepDetail();
        $stepDetail->inspection_report_document_number = $docNum;
        $stepDetail->period = 1;
        $stepDetail->persistPeriodToDatabase(1);

        $this->assertDatabaseMissing('inspection_dimensions', [
            'inspection_report_document_number' => $docNum,
        ]);
    }

    public function test_pillar_5_period_problems_persists_and_recaps_in_step_3(): void
    {
        $docNum = 'INSP-P5-TEST-001';
        $detailDocNum = 'DETAIL-P5-TEST-001';

        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'shift' => 1,
                'inspector' => 'Inspector Test',
                'part_number' => 'PART-TEST-P5',
                'part_name' => 'BRACKET TEST',
                'machine_number' => 'INJ-01',
            ],
            'stepDetailSaved' => [
                'period' => 1,
                'details' => [
                    'p1' => [
                        'document_number' => $detailDocNum,
                        'inspection_report_document_number' => $docNum,
                        'period' => 1,
                        'start_datetime' => '2026-11-17 07:30:00',
                        'end_datetime' => '2026-11-17 09:30:00',
                    ],
                ],
            ],
        ]);

        // 1. Mount StepPeriodProblem and add a problem
        Livewire::actingAs($this->user)
            ->test(StepPeriodProblem::class, [
                'inspection_report_document_number' => $docNum,
                'period' => 1,
                'start_time' => '07:30',
                'end_time' => '09:30',
            ])
            ->call('addProblem')
            ->assertCount('problems', 1)
            ->set('problems.0.type', 'MACHINE PROBLEM')
            ->set('problems.0.time', '08:15')
            ->set('problems.0.cycle_time', 20)
            ->set('problems.0.remarks', 'Nozzle tersumbat material asing')
            ->call('autoSave');

        $this->assertNotEmpty(session('stepDetailSaved.problems.p1'));
        $this->assertEquals('MACHINE PROBLEM', session('stepDetailSaved.problems.p1.0.type'));
        $this->assertEquals(20, session('stepDetailSaved.problems.p1.0.cycle_time'));

        // 2. StepDetail subStepsSummary must include 5th pillar
        $stepDetail = Livewire::actingAs($this->user)
            ->test(StepDetail::class, [
                'inspection_report_document_number' => $docNum,
            ]);

        $subSteps = $stepDetail->get('subStepsSummary');
        $this->assertArrayHasKey(5, $subSteps);
        $this->assertEquals('Masalah & Loss Time', $subSteps[5]['title']);
        $this->assertTrue($subSteps[5]['isSaved']);
        $this->assertStringContainsString('1 Masalah (20 mnt)', $subSteps[5]['summary']);

        // 3. Persist period to database
        $stepDetailComponent = new StepDetail();
        $stepDetailComponent->inspection_report_document_number = $docNum;
        $stepDetailComponent->document_number = $detailDocNum;
        $stepDetailComponent->period = 1;
        $stepDetailComponent->persistPeriodToDatabase(1);

        // Verify problem is saved to inspection_problems table
        $this->assertDatabaseHas('inspection_problems', [
            'inspection_report_document_number' => $docNum,
            'type' => 'MACHINE PROBLEM',
            'time' => '08:15',
            'cycle_time' => 20,
            'remarks' => 'Nozzle tersumbat material asing',
        ]);

        // Verify stepProblemSaved session is synchronized
        $this->assertNotEmpty(session('stepProblemSaved'));
        $this->assertEquals('MACHINE PROBLEM', session('stepProblemSaved.0.type'));

        // 4. Step 3 (StepProblem) renders recap with 1 problem in period 1
        Livewire::actingAs($this->user)
            ->test(StepProblem::class, [
                'inspection_report_document_number' => $docNum,
            ])
            ->assertSee('Rekap Kendala Per Periode Inspeksi')
            ->assertSee('Periode 1')
            ->assertSee('Nozzle tersumbat material asing')
            ->call('saveStep')
            ->assertDispatched('stepProblemSaved')
            ->assertDispatched('nextStep');
    }

    public function test_entering_blank_period_does_not_accidentally_save_or_mark_pillar_5_as_saved(): void
    {
        $docNum = 'INSP-BLANK-TEST-001';

        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'shift' => 1,
                'inspector' => 'Inspector Test',
                'part_number' => 'PART-TEST-BLANK',
                'part_name' => 'BLANK PART',
                'machine_number' => 'INJ-01',
            ],
            'stepDetailSaved' => [
                'period' => 1,
            ],
        ]);

        // 1. Mount StepDetail and enter period 1
        $component = Livewire::actingAs($this->user)
            ->test(StepDetail::class, [
                'inspection_report_document_number' => $docNum,
            ])
            ->call('startPeriod', 1);

        // 2. Pillar 5 must NOT default to saved when user just enters the period with no changes
        $subSteps = $component->get('subStepsSummary');
        $this->assertFalse($subSteps[5]['isSaved']);
        $this->assertEquals('Belum dikonfirmasi', $subSteps[5]['summary']);

        // 3. Exiting to hub without entering data must NOT mark period as saved
        $component->call('exitToHub');
        $this->assertFalse($component->instance()->isPeriodSaved(1));
        $this->assertFalse($component->instance()->hasPeriodRealData(1));

        // 4. Attempting to save empty period must be blocked
        $component->call('startPeriod', 1);
        $component->call('saveCurrentPeriod')
            ->assertDispatched('toast', message: 'Periode 1 belum memiliki data inspeksi yang diisi. Harap masukkan hasil inspeksi sebelum menyimpan periode.');

        $this->assertFalse($component->instance()->isPeriodSaved(1));

        // Database must remain clean with NO ghost detail records
        $this->assertDatabaseMissing('detail_inspection_reports', [
            'inspection_report_document_number' => $docNum,
            'period' => 1,
        ]);
    }

    public function test_step_header_one_at_a_time_substep_navigation_and_dic_selection(): void
    {
        $part = MasterListItem::firstOrCreate([
            'item_code' => 'PART-HEADER-TEST-001',
        ], [
            'item_name' => 'BEZEL UPPER TEST',
            'part_weight' => 25.5,
            'color' => 'BLACK',
            'cavity' => 2,
        ]);

        $dic = \App\Models\DailyItemCode::create([
            'schedule_date' => now()->format('Y-m-d'),
            'item_code' => $part->item_code,
            'item_name' => $part->item_name,
            'shift' => 1,
            'total_target' => 500,
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(StepHeader::class);

        // 1. Initial subStep is 1 and dic_active_shift defaults to current active shift
        $this->assertEquals(1, $test->get('subStep'));
        $this->assertGreaterThanOrEqual(1, $test->get('dic_active_shift'));
        $this->assertLessThanOrEqual(3, $test->get('dic_active_shift'));

        // 2. Next subStep blocked if part is not selected
        $test->set('part_number', '')
            ->call('nextSubStep')
            ->assertHasErrors(['part_number'])
            ->assertSet('subStep', 1);

        // 3. Select DIC from list populates part, shift, and date, auto-advancing to subStep 2
        $test->call('selectDicFromList', $dic->id);
        $this->assertEquals($part->item_code, $test->get('part_number'));
        $this->assertEquals(1, $test->get('shift'));
        $this->assertEquals($dic->id, $test->get('dic_id'));
        $this->assertEquals(2, $test->get('subStep'));

        // 4. In subStep 2, left rail summary accurately reflects state
        $summaries = $test->get('subStepsSummary');
        $this->assertTrue($summaries[1]['isSaved']);
        $this->assertStringContainsString($part->item_code, $summaries[1]['summary']);

        // 5. Prev subStep navigates back to 1 and defaults to Stage 3
        $test->call('prevSubStep')
            ->assertSet('subStep', 1)
            ->assertSet('subStep1Stage', 3);
    }

    public function test_step_header_one_card_at_a_time_stages_and_auto_advance(): void
    {
        $machineUser = User::factory()->create(['name' => 'MC-TEST-99']);
        $part = MasterListItem::factory()->create([
            'item_code' => 'PART-STAGE-TEST',
            'item_name' => 'TEST STAGE PART',
        ]);
        $dic = DailyItemCode::factory()->create([
            'item_code' => $part->item_code,
            'user_id' => $machineUser->id,
            'shift' => 1,
            'schedule_date' => '2026-11-17',
        ]);

        $test = Livewire::actingAs($this->user)
            ->test(StepHeader::class);

        // Stage 1 initially
        $this->assertEquals(1, $test->get('subStep1Stage'));

        // Advance to Stage 2 (Filter Mesin)
        $test->call('setSubStep1Stage', 2);
        $this->assertEquals(2, $test->get('subStep1Stage'));

        // Go back to Stage 1
        $test->call('prevSubStep1Stage');
        $this->assertEquals(1, $test->get('subStep1Stage'));

        // Advance again to Stage 2
        $test->call('nextSubStep1Stage');
        $this->assertEquals(2, $test->get('subStep1Stage'));

        // Select specific machine and auto-advance to Stage 3
        $test->call('selectMachineAndAdvance', $machineUser->id);
        $this->assertEquals(3, $test->get('subStep1Stage'));
        $this->assertEquals($machineUser->id, $test->get('dic_filter_user_id'));
        $this->assertEquals('MC-TEST-99', $test->get('machine_number'));

        // Skip filter and advance to Stage 3
        $test->call('setSubStep1Stage', 2);
        $test->call('skipMachineFilterAndAdvance');
        $this->assertEquals(3, $test->get('subStep1Stage'));
        $this->assertNull($test->get('dic_filter_user_id'));

        // In Stage 3, selecting DIC auto-advances directly to Sub-step 2
        $test->call('selectDicFromList', $dic->id);
        $this->assertEquals(2, $test->get('subStep'));
        $this->assertEquals($part->item_code, $test->get('part_number'));
    }

    public function test_user_can_reset_form_via_start_new_report(): void
    {
        $docNum = 'INSP-DRAFT-RESET-001';
        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'part_number' => 'PART-RESET-TEST',
            ],
            'lastStepVisited' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(InspectionForm::class)
            ->call('startNewReport')
            ->assertSet('currentStep', 1)
            ->assertSet('inspection_report_document_number', null)
            ->assertDispatched('toast');

        $this->assertNull(session('stepHeaderSaved'));
        $this->assertNull(session('lastStepVisited'));
    }

    public function test_create_form_continues_draft_when_continue_param_is_passed(): void
    {
        $docNum = 'INSP-DRAFT-CONT-001';
        session([
            'stepHeaderSaved' => [
                'document_number' => $docNum,
                'customer' => 'PT Honda Prospect Motor',
                'part_number' => 'PART-CONT-TEST',
                'machine_number' => 'MC-02',
                'shift' => 2,
                'operator' => 'Wahyu',
                'inspector' => 'Rini',
            ],
            'lastStepVisited' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->withQueryParams(['continue' => 1])
            ->test(InspectionForm::class)
            ->assertSet('currentStep', 2)
            ->assertSet('inspection_report_document_number', $docNum);
    }

    public function test_create_form_starts_fresh_when_new_param_is_passed(): void
    {
        session([
            'stepHeaderSaved' => [
                'document_number' => 'INSP-PARAM-NEW-001',
                'part_number' => 'PART-PARAM-TEST',
            ],
            'lastStepVisited' => 2,
        ]);

        $this->actingAs($this->user)
            ->get(route('inspection-reports.create', ['new' => 1]))
            ->assertOk()
            ->assertSee('Header & Part Info');

        $this->assertNull(session('stepHeaderSaved'));
    }
}
