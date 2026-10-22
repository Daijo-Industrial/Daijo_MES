<?php

namespace Tests\Feature;

use App\Models\FirstPieceInspection;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstPieceInspectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_prefills_work_order_parameters()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-TEST-99',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-WO-123',
            'part_name' => 'Widget Cover',
            'customer' => 'Toyota',
            'target_qty' => 500,
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('first-piece-inspections.create', [
            'work_order_id' => $workOrder->id,
            'part_number' => 'PN-WO-123',
            'part_name' => 'Widget Cover',
            'model' => 'Model-Alpha',
        ]));

        $response->assertOk();
        $response->assertSee('PN-WO-123');
        $response->assertSee('Widget Cover');
        $response->assertSee('Model-Alpha');
        $response->assertSee('Linked Work Order:');
    }

    public function test_store_with_auto_approve_sets_qc_approval_and_redirects_to_work_order()
    {
        $user = User::factory()->create(['name' => 'QC Inspector John']);
        $this->actingAs($user);

        $workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-FPI-FAST-01',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-FPI-FAST',
            'part_name' => 'Fast Part',
            'customer' => 'Toyota',
            'target_qty' => 500,
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        $postData = [
            'work_order_id' => $workOrder->id,
            'date' => now()->format('Y-m-d'),
            'model' => 'Model-Alpha',
            'part_name' => 'Fast Part',
            'part_number' => 'PN-FPI-FAST',
            'overall_judgement' => 'OK',
            'auto_approve' => '1',
            'check_results' => [
                ['check_point' => 'Dirty Spray', 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'],
            ],
        ];

        $response = $this->post(route('first-piece-inspections.store'), $postData);

        $response->assertRedirect(route('first-piece-inspections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('first_piece_inspections', [
            'work_order_id' => $workOrder->id,
            'part_number' => 'PN-FPI-FAST',
            'overall_judgement' => 'OK',
            'checked_by' => 'QC Inspector John',
        ]);

        $inspection = FirstPieceInspection::where('part_number', 'PN-FPI-FAST')->first();
        $this->assertNotNull($inspection->checked_at);
        $this->assertTrue($inspection->isApproved());
    }
}
