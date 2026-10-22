<?php

namespace Tests\Feature;

use App\Models\FirstPieceInspection;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstPieceInspectionGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_start_production_session_without_first_piece_inspection()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-FPI-GATE-01',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-FPI-TEST',
            'part_name' => 'Widget FPI',
            'customer' => 'Toyota',
            'target_qty' => 500,
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        // Attempt start without First Piece Inspection
        $response = $this->from(route('sp-work-orders.show', $workOrder->id))
            ->post(route('sp-sessions.start', $workOrder->id));

        $response->assertRedirect(route('sp-work-orders.show', $workOrder->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('sp_production_sessions', [
            'work_order_id' => $workOrder->id,
        ]);
    }

    public function test_can_start_production_session_when_first_piece_inspection_is_qc_approved()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-FPI-GATE-02',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-FPI-APPROVED',
            'part_name' => 'Widget OK',
            'customer' => 'Toyota',
            'target_qty' => 500,
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        // Create QC-approved First Piece Inspection
        FirstPieceInspection::create([
            'date' => now()->format('Y-m-d'),
            'model' => 'Model X',
            'part_name' => 'Widget OK',
            'part_number' => 'PN-FPI-APPROVED',
            'overall_judgement' => 'OK',
            'checked_by' => 'Inspector QC',
            'checked_at' => now(),
        ]);

        // Attempt start with approved First Piece Inspection
        $response = $this->post(route('sp-sessions.start', $workOrder->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('sp_production_sessions', [
            'work_order_id' => $workOrder->id,
            'status' => 'running',
        ]);
    }
}
