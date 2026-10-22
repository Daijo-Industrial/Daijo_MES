<?php

namespace Tests\Feature;

use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\FirstInspection;
use App\Models\Inspection\InspectionDefect;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionJudgement;
use App\Models\Inspection\InspectionPackaging;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use App\Models\Inspection\InspectionSampling;
use App\Models\Inspection\SecondInspection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_inspection_report_and_its_related_models(): void
    {
        // 1. Header
        $report = InspectionReport::create([
            'document_number' => 'INSP-20261019-100000-ABCD',
            'customer' => 'PT Astra Honda Motor',
            'inspection_date' => '2026-10-19',
            'part_number' => '401-41019967',
            'part_name' => 'COVER HANDLE FR',
            'weight' => 145.5,
            'weight_uom' => 'GRAM',
            'material' => 'PP RESIN',
            'color' => 'BLACK',
            'tool_number_or_cav_number' => 'CAV-01',
            'machine_number' => 'MC-01',
            'shift' => 1,
            'operator' => 'Budi',
            'inspector' => 'Siti',
            'inspector_autograph' => 'siti_sign.png',
            'leader_autograph' => 'leader_sign.png',
            'head_autograph' => 'head_sign.png',
        ]);

        $this->assertDatabaseHas('inspection_reports', [
            'document_number' => 'INSP-20261019-100000-ABCD',
            'customer' => 'PT Astra Honda Motor',
            'part_number' => '401-41019967',
            'inspector' => 'Siti',
        ]);

        $this->assertInstanceOf(Carbon::class, $report->inspection_date);
        $this->assertEquals('2026-10-19', $report->inspection_date->format('Y-m-d'));

        // 2. DetailInspectionReport (Period 1)
        $detail = DetailInspectionReport::create([
            'inspection_report_document_number' => $report->document_number,
            'document_number' => 'DIR-20261019-P1-0001',
            'period' => 1,
            'start_datetime' => '2026-10-19 08:00:00',
            'end_datetime' => '2026-10-19 10:00:00',
        ]);

        $this->assertDatabaseHas('detail_inspection_reports', [
            'document_number' => 'DIR-20261019-P1-0001',
            'period' => 1,
        ]);
        $this->assertEquals($report->document_number, $detail->inspectionReport->document_number);
        $this->assertCount(1, $report->detailInspectionReports);

        // 3. First Inspection
        $first = FirstInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'appearance' => 'OK',
            'remarks' => 'No flash or sink mark',
            'weight' => 145.4,
            'weight_uom' => 'GRAM',
            'fitting_test' => 'OK',
        ]);

        $this->assertDatabaseHas('first_inspections', [
            'detail_inspection_report_document_number' => $detail->document_number,
            'appearance' => 'OK',
        ]);
        $this->assertEquals($detail->document_number, $first->detailInspectionReport->document_number);
        $this->assertCount(1, $detail->firstInspections);

        // 4. Second Inspection
        $second = SecondInspection::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'document_number' => 'SEC-20261019-0001',
            'lot_size_quantity' => 500,
        ]);

        $this->assertDatabaseHas('second_inspections', [
            'document_number' => 'SEC-20261019-0001',
            'lot_size_quantity' => 500,
        ]);
        $this->assertEquals($detail->document_number, $second->detailInspectionReport->document_number);
        $this->assertCount(1, $detail->secondInspections);

        // 5. Sampling
        $sampling = InspectionSampling::create([
            'second_inspection_document_number' => $second->document_number,
            'quantity' => 20,
            'box_label' => 'BOX-01',
            'appearance' => 'OK',
            'ng_quantity' => 0,
            'remarks' => 'Clean',
        ]);

        $this->assertDatabaseHas('inspection_samplings', [
            'second_inspection_document_number' => $second->document_number,
            'quantity' => 20,
        ]);
        $this->assertEquals($second->document_number, $sampling->secondInspection->document_number);
        $this->assertCount(1, $second->samplingData);

        // 6. Packaging
        $packaging = InspectionPackaging::create([
            'second_inspection_document_number' => $second->document_number,
            'snp' => 50,
            'box_label' => 'BOX-01',
            'judgement' => 'OK',
            'remarks' => 'Standard polybag',
        ]);

        $this->assertDatabaseHas('inspection_packagings', [
            'second_inspection_document_number' => $second->document_number,
            'snp' => 50,
        ]);
        $this->assertEquals($second->document_number, $packaging->secondInspection->document_number);
        $this->assertCount(1, $second->packagingData);

        // 7. Judgement
        $judgement = InspectionJudgement::create([
            'detail_inspection_report_document_number' => $detail->document_number,
            'pass_quantity' => 500,
            'reject_quantity' => 0,
        ]);

        $this->assertDatabaseHas('inspection_judgements', [
            'detail_inspection_report_document_number' => $detail->document_number,
            'pass_quantity' => 500,
        ]);
        $this->assertEquals($detail->document_number, $judgement->detailInspectionReport->document_number);
        $this->assertCount(1, $detail->judgementData);

        // 8. Dimensions
        $dimension = InspectionDimension::create([
            'inspection_report_document_number' => $report->document_number,
            'lower_limit' => 10.0,
            'upper_limit' => 10.5,
            'limit_uom' => 'MM',
            'actual_value' => '10.250',
            'start_datetime' => '2026-10-19 08:15:00',
            'end_datetime' => '2026-10-19 08:30:00',
            'judgement' => 'OK',
            'area' => 'Diameter Outer',
            'remarks' => 'Sample 1',
        ]);

        $this->assertDatabaseHas('inspection_dimensions', [
            'inspection_report_document_number' => $report->document_number,
            'actual_value' => '10.250',
        ]);
        $this->assertEquals($report->document_number, $dimension->inspectionReport->document_number);
        $this->assertCount(1, $report->dimensionData);

        // 9. Problem
        $problem = InspectionProblem::create([
            'inspection_report_document_number' => $report->document_number,
            'type' => 'Mould Temperature High',
            'time' => '09:15:00',
            'cycle_time' => 35,
            'remarks' => 'Adjusted water chiller valve',
        ]);

        $this->assertDatabaseHas('inspection_problems', [
            'inspection_report_document_number' => $report->document_number,
            'type' => 'Mould Temperature High',
        ]);
        $this->assertEquals($report->document_number, $problem->inspectionReport->document_number);
        $this->assertCount(1, $report->problemData);

        // 10. Quantity Summary
        $quantity = InspectionQuantity::create([
            'inspection_report_document_number' => $report->document_number,
            'output_quantity' => 2000,
            'pass_quantity' => 1990,
            'pass_rate' => 99.5,
            'reject_quantity' => 10,
            'reject_rate' => 0.5,
            'sampling_quantity' => 80,
            'ng_sample_quantity' => 0,
            'ng_sample_rate' => 0.0,
        ]);

        $this->assertDatabaseHas('inspection_quantities', [
            'inspection_report_document_number' => $report->document_number,
            'output_quantity' => 2000,
        ]);
        $this->assertEquals($report->document_number, $quantity->inspectionReport->document_number);
        $this->assertNotNull($report->quantityData);
        $this->assertEquals(2000, $report->quantityData->output_quantity);

        // 11. Defect Catalog
        $defect = InspectionDefect::create([
            'name' => 'Silver Streak',
            'department' => 'INJECTION',
        ]);

        $this->assertDatabaseHas('inspection_defects', [
            'name' => 'Silver Streak',
            'department' => 'INJECTION',
        ]);
    }
}
