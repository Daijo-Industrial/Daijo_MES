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
use App\Livewire\Inspection\InspectionForm\StepProblem;
use App\Livewire\Inspection\InspectionForm\StepSampling;
use App\Livewire\Inspection\InspectionForm\StepSecond;
use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MachineJob;
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
                    'type' => 'NO PROBLEM',
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
            'type' => 'NO PROBLEM',
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
}
