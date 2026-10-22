<?php

namespace Tests\Feature;

use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpWorkOrderDraftTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_work_order_as_draft()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('sp-work-orders.store'), [
            'action' => 'draft',
            'wo_number' => 'WO-DRAFT-001',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-DRAFT-01',
            'part_name' => 'Draft Part',
        ]);

        $wo = SpWorkOrder::where('wo_number', 'WO-DRAFT-001')->first();
        $this->assertNotNull($wo);
        $this->assertEquals('draft', $wo->status);
        $response->assertRedirect(route('sp-work-orders.show', $wo->id));
    }

    public function test_draft_work_orders_are_hidden_from_line_gateway()
    {
        $this->actingAs($this->user);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-DRAFT-HIDDEN',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-DRAFT-HIDDEN',
            'part_name' => 'Hidden Part',
            'customer' => 'Customer A',
            'target_qty' => 500,
            'status' => 'draft',
        ]);

        $response = $this->get(route('sp-sessions.line-gateway', ['lineSlug' => 'line-a']));
        $response->assertStatus(200);
        $response->assertDontSee('WO-DRAFT-HIDDEN');
    }

    public function test_can_edit_draft_work_order_but_not_planned_work_order()
    {
        $this->actingAs($this->user);

        $draftWo = SpWorkOrder::create([
            'wo_number' => 'WO-DRAFT-EDIT',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-EDIT-01',
            'part_name' => 'Part Name Original',
            'customer' => 'Customer A',
            'target_qty' => 100,
            'status' => 'draft',
        ]);

        // Edit draft WO -> Should succeed
        $response = $this->get(route('sp-work-orders.edit', $draftWo->id));
        $response->assertStatus(200);

        // Update draft WO
        $response = $this->put(route('sp-work-orders.update', $draftWo->id), [
            'action' => 'draft',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-EDIT-01',
            'part_name' => 'Part Name Updated',
            'customer' => 'Customer A',
            'target_qty' => 200,
        ]);
        $response->assertRedirect(route('sp-work-orders.show', $draftWo->id));
        $this->assertDatabaseHas('sp_work_orders', [
            'id' => $draftWo->id,
            'part_name' => 'Part Name Updated',
            'status' => 'draft',
        ]);

        // Release WO
        $draftWo->update(['status' => 'planned']);

        // Try edit planned WO -> Should be blocked and redirected with error
        $response = $this->get(route('sp-work-orders.edit', $draftWo->id));
        $response->assertRedirect(route('sp-work-orders.show', $draftWo->id));
        $response->assertSessionHas('error');
    }

    public function test_can_release_draft_work_order_to_production()
    {
        $this->actingAs($this->user);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-DRAFT-RELEASE',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-RELEASE-01',
            'part_name' => 'Release Part',
            'customer' => 'Customer B',
            'target_qty' => 500,
            'status' => 'draft',
        ]);

        $response = $this->post(route('sp-work-orders.release', $wo->id));
        $response->assertRedirect(route('sp-work-orders.show', $wo->id));

        $wo->refresh();
        $this->assertEquals('planned', $wo->status);

        // Verify now visible on Line Gateway
        $gatewayResponse = $this->get(route('sp-sessions.line-gateway', ['lineSlug' => 'line-a']));
        $gatewayResponse->assertSee('WO-DRAFT-RELEASE');
    }

    public function test_can_revert_planned_work_order_to_draft_if_no_sessions_exist()
    {
        $this->actingAs($this->user);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-PLANNED-REVERT',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-REVERT-01',
            'part_name' => 'Revert Part',
            'customer' => 'Customer C',
            'target_qty' => 300,
            'status' => 'planned',
        ]);

        // Revert to draft
        $response = $this->post(route('sp-work-orders.revert-to-draft', $wo->id));
        $response->assertRedirect(route('sp-work-orders.show', $wo->id));

        $wo->refresh();
        $this->assertEquals('draft', $wo->status);

        // Add a session
        $wo->update(['status' => 'planned']);
        SpProductionSession::create([
            'work_order_id' => $wo->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line A',
            'shift' => '1',
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Attempt revert when session exists -> Should be blocked
        $response = $this->post(route('sp-work-orders.revert-to-draft', $wo->id));
        $response->assertRedirect(route('sp-work-orders.show', $wo->id));
        $response->assertSessionHas('error');
        $this->assertEquals('planned', $wo->fresh()->status);
    }

    public function test_finishing_session_returns_work_order_to_planned_if_target_not_met()
    {
        $this->actingAs($this->user);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-TARGET-TEST',
            'customer' => 'Customer A',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-TARGET-01',
            'part_name' => 'Target Test Part',
            'target_qty' => 100,
            'status' => 'in_progress',
        ]);

        $session = SpProductionSession::create([
            'work_order_id' => $wo->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line A',
            'shift' => '1',
            'status' => 'running',
            'started_at' => now(),
            'total_good' => 30,
        ]);

        // Submit closeout when target (100) is NOT met (30)
        $response = $this->post(route('app.sp-sessions.submit-closeout', $session->id), [
            'output_destination' => 'fg',
        ]);
        $response->assertRedirect();

        $wo->refresh();
        $this->assertEquals('planned', $wo->status, 'WO should revert to planned status because target was not met');

        // Start a 2nd session and complete remaining target
        $session2 = SpProductionSession::create([
            'work_order_id' => $wo->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line A',
            'shift' => '1',
            'status' => 'running',
            'started_at' => now(),
            'total_good' => 70,
        ]);

        // Submit closeout when cumulative target (30 + 70 = 100) is met
        $response2 = $this->post(route('app.sp-sessions.submit-closeout', $session2->id), [
            'output_destination' => 'fg',
        ]);
        $response2->assertRedirect();

        $wo->refresh();
        $this->assertEquals('completed', $wo->status, 'WO should become completed because cumulative target was fulfilled');
    }
}
