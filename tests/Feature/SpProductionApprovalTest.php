<?php

namespace Tests\Feature;

use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SpProductionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected SpWorkOrder $workOrder;

    protected SpProductionSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Supervisor Test',
        ]);

        $this->workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-TEST-APP-001',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line 1',
            'process_prod' => 'Painting',
            'part_number' => 'PN-APP-1234',
            'part_name' => 'Door Panel Assembly',
            'model' => 'Sedan-X',
            'customer' => 'Daijo Corp',
            'target_qty' => 1000,
            'status' => 'released',
            'created_by' => $this->user->id,
        ]);

        $this->session = SpProductionSession::create([
            'work_order_id' => $this->workOrder->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line 1',
            'shift' => '1',
            'status' => 'completed',
            'started_at' => now()->subHours(8),
            'finished_at' => now(),
            'total_input' => 500,
            'total_good' => 450,
            'total_reject' => 50,
            'total_rework_in' => 20,
            'total_rework_recovered' => 15,
            'total_scrap' => 5,
        ]);
    }

    public function test_index_displays_pending_sessions_and_summary_kpis(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('sp-approvals.index'));

        $response->assertStatus(200);
        $response->assertSee('WO-TEST-APP-001');
        $response->assertSee('Door Panel Assembly');
        $response->assertSee('PN-APP-1234');
        $response->assertSee('Line 1');
        $response->assertSee('Shift 1');
        // Check KPI section presence
        $response->assertSee('Pending Approval');
        $response->assertSee('Approved Today');
    }

    public function test_index_can_filter_by_line_shift_and_search(): void
    {
        $this->actingAs($this->user);

        $woOther = SpWorkOrder::create([
            'wo_number' => 'WO-OTHER-999',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line 2',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-OTHER-999',
            'part_name' => 'Console Box',
            'model' => 'SUV-Y',
            'customer' => 'Customer B',
            'target_qty' => 500,
            'status' => 'released',
            'created_by' => $this->user->id,
        ]);

        SpProductionSession::create([
            'work_order_id' => $woOther->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line 2',
            'shift' => '2',
            'status' => 'completed',
            'started_at' => now()->subHours(8),
            'finished_at' => now(),
            'total_input' => 200,
            'total_good' => 190,
            'total_reject' => 10,
        ]);

        // Filter for Line 1 and Shift 1
        $response = $this->get(route('sp-approvals.index', [
            'unit_line' => 'Line 1',
            'shift' => '1',
            'search' => 'WO-TEST-APP-001',
        ]));

        $response->assertStatus(200);
        $response->assertSee('WO-TEST-APP-001');
        $response->assertDontSee('WO-OTHER-999');
    }

    public function test_index_displays_approved_sessions_tab(): void
    {
        $this->actingAs($this->user);

        $this->session->update([
            'approved_at' => now(),
            'approved_by' => $this->user->id,
        ]);

        $response = $this->get(route('sp-approvals.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('WO-TEST-APP-001');
        $response->assertSee('Approved');
    }

    public function test_show_renders_detailed_shift_report_with_tabs(): void
    {
        $this->actingAs($this->user);

        // Add sample reject, downtime, and material entries
        $this->session->rejectEntries()->create([
            'defect_type' => 'Dust Particle',
            'quantity' => 30,
            'cause' => 'Dirty spray gun',
        ]);

        $this->session->downtimeEntries()->create([
            'reason' => 'Nozzle Clogged',
            'start_time' => '09:00',
            'resume_time' => '09:30',
            'remarks' => 'Cleaned nozzle and filters',
        ]);

        $this->session->materials()->create([
            'type' => 'paint',
            'item_name' => 'Clear Coat Primer',
            'lot_number' => 'LOT-CC-2026',
            'visco' => '14s',
            'mixing_ratio' => '4:1',
            'qty' => 5,
            'uom' => 'KG',
        ]);

        $this->session->materials()->create([
            'type' => 'part',
            'item_name' => 'WIP Substrate Base',
            'lot_number' => 'LOT-BASE-88',
            'qty' => 500,
            'uom' => 'PCS',
        ]);

        $response = $this->get(route('sp-approvals.show', $this->session->id));

        $response->assertStatus(200);
        // Scorecard items
        $response->assertSee('WO-TEST-APP-001');
        $response->assertSee('Shift Mass Balance');
        $response->assertSee('Clear Coat Primer');
        $response->assertSee('LOT-CC-2026');
        $response->assertSee('WIP Substrate Base');
        $response->assertSee('Dust Particle');
        $response->assertSee('Nozzle Clogged');
        // Supervisor controls
        $response->assertSee('Supervisor Decision');
        $response->assertSee('APPROVE & SYNC REPORT', false);
        $response->assertSee('Return for Correction');
        $response->assertSee('Shift Audit Trail (WIB / UTC+7)');
        $response->assertSee('8-Hour Production Progression');
        $response->assertSee('07:30 - 08:30');
    }

    public function test_supervisor_can_approve_session_and_sync_to_legacy_report(): void
    {
        Gate::define('approve-sp-sessions', fn () => true);
        $this->actingAs($this->user);

        $response = $this->post(route('sp-approvals.approve', $this->session->id));

        $response->assertRedirect(route('sp-approvals.index'));
        $response->assertSessionHas('success');

        $this->session->refresh();
        $this->assertNotNull($this->session->approved_at);
        $this->assertEquals($this->user->id, $this->session->approved_by);

        // Verify synced into legacy second_process_reports table
        $this->assertDatabaseHas('second_process_reports', [
            'part_number' => 'PN-APP-1234',
            'unit_line' => 'Line 1',
            'shift' => '1',
            'status' => 'Approved',
        ]);
    }

    public function test_supervisor_can_reject_session_back_to_running_with_reason(): void
    {
        Gate::define('approve-sp-sessions', fn () => true);
        $this->actingAs($this->user);

        $reason = 'Final scrap count does not tally with WIP bins. Please recount.';
        $response = $this->post(route('sp-approvals.reject', $this->session->id), [
            'reason' => $reason,
        ]);

        $response->assertRedirect(route('sp-approvals.index'));
        $response->assertSessionHas('warning');

        $this->session->refresh();
        $this->assertEquals('running', $this->session->status);
        $this->assertNull($this->session->finished_at);
        $this->assertNull($this->session->approved_at);
        $this->assertStringContainsString($reason, $this->session->remarks);
        $this->assertStringContainsString('Correction Requested by Supervisor Test', $this->session->remarks);
    }

    public function test_unauthorized_user_cannot_approve_or_reject_session(): void
    {
        Gate::define('approve-sp-sessions', fn () => false);
        $this->actingAs($this->user);

        $responseApprove = $this->post(route('sp-approvals.approve', $this->session->id));
        $responseApprove->assertStatus(403);

        $responseReject = $this->post(route('sp-approvals.reject', $this->session->id), [
            'reason' => 'Some reason',
        ]);
        $responseReject->assertStatus(403);
    }

    public function test_timestamps_are_rendered_in_wib(): void
    {
        $this->actingAs($this->user);

        $nowWib = \Carbon\Carbon::now(config('mes.timezone', 'Asia/Jakarta'));
        $this->session->update([
            'started_at' => $nowWib->copy()->subHours(4)->utc(),
            'finished_at' => $nowWib->copy()->utc(),
        ]);

        $response = $this->get(route('sp-approvals.show', $this->session->id));

        $response->assertStatus(200);
        $response->assertSee('Shift Audit Trail (WIB / UTC+7)');
        $response->assertSee($nowWib->format('Y-m-d H:i'));
        $response->assertSee($nowWib->copy()->subHours(4)->format('Y-m-d H:i'));

        // Verify database stores UTC
        $rawSession = \Illuminate\Support\Facades\DB::table('sp_production_sessions')->where('id', $this->session->id)->first();
        $this->assertEquals(
            $nowWib->copy()->utc()->format('Y-m-d H:i:s'),
            $rawSession->finished_at
        );
    }
}
