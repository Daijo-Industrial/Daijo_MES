<?php

namespace Tests\Feature;

use App\Models\SecondProcessManpower;
use App\Models\SecondProcessReport;
use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SpProductionManpowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_add_and_remove_line_manpower()
    {
        Gate::define('approve-sp-sessions', fn () => true);

        $user = User::factory()->create();
        $this->actingAs($user);

        $workOrder = SpWorkOrder::create([
            'wo_number' => 'WO-SP-MANPOWER-01',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-MP-100',
            'part_name' => 'Widget B',
            'customer' => 'Daihatsu',
            'target_qty' => 500,
            'status' => 'released',
            'created_by' => $user->id,
        ]);

        $session = SpProductionSession::create([
            'work_order_id' => $workOrder->id,
            'operator_id' => $user->id,
            'unit_line' => 'Line A',
            'shift' => '1',
            'status' => 'running',
            'started_at' => now()->subHours(8),
            'total_input' => 100,
            'total_good' => 100,
            'total_reject' => 0,
        ]);

        // Verify floor screen renders manpower options from config('mes.sp_manpower_roles')
        $responseScreen = $this->get(route('app.sp-sessions.show', $session->id));
        $responseScreen->assertOk();
        $responseScreen->assertSee('Loading / Input');
        $responseScreen->assertSee('Sprayer');
        $responseScreen->assertSee('Packing');
        $responseScreen->assertSee('Other (custom)...');

        // Add Standard Manpower (e.g. Sprayer)
        $response1 = $this->post(route('app.sp-sessions.add-manpower', $session->id), [
            'operator_name' => 'John Doe',
            'employee_no' => 'EMP-1001',
            'role' => 'sprayer',
        ]);
        $response1->assertRedirect();

        // Add Infeed & Outfeed Manpower (loading & packing)
        $this->post(route('app.sp-sessions.add-manpower', $session->id), [
            'operator_name' => 'Alice Loading',
            'employee_no' => 'EMP-1002',
            'role' => 'loading',
        ]);
        $this->post(route('app.sp-sessions.add-manpower', $session->id), [
            'operator_name' => 'Bob Packing',
            'employee_no' => 'EMP-1003',
            'role' => 'packing',
        ]);

        // Add Custom Manpower Role
        $response2 = $this->post(route('app.sp-sessions.add-manpower', $session->id), [
            'operator_name' => 'Charlie Custom',
            'employee_no' => 'EMP-1004',
            'role' => 'Buffing Specialist',
        ]);
        $response2->assertRedirect();

        $this->assertDatabaseHas('sp_session_manpowers', [
            'session_id' => $session->id,
            'operator_name' => 'John Doe',
            'role' => 'sprayer',
        ]);
        $this->assertDatabaseHas('sp_session_manpowers', [
            'session_id' => $session->id,
            'operator_name' => 'Alice Loading',
            'role' => 'loading',
        ]);
        $this->assertDatabaseHas('sp_session_manpowers', [
            'session_id' => $session->id,
            'operator_name' => 'Bob Packing',
            'role' => 'packing',
        ]);
        $this->assertDatabaseHas('sp_session_manpowers', [
            'session_id' => $session->id,
            'operator_name' => 'Charlie Custom',
            'role' => 'Buffing Specialist',
        ]);

        // Complete session
        $session->update(['status' => 'completed', 'finished_at' => now()]);
        $session->refresh();

        // Approve session and verify sync bridge creates SecondProcessManpower
        $response = $this->post(route('sp-approvals.approve', $session->id));
        $response->assertRedirect(route('sp-approvals.index'));

        $legacyReport = SecondProcessReport::where('part_number', 'PN-MP-100')->first();
        $this->assertNotNull($legacyReport);

        $this->assertDatabaseHas('second_process_manpowers', [
            'report_id' => $legacyReport->id,
            'name' => 'John Doe',
            'role' => 'sprayer',
        ]);
        $this->assertDatabaseHas('second_process_manpowers', [
            'report_id' => $legacyReport->id,
            'name' => 'Alice Loading',
            'role' => 'loading',
        ]);
        $this->assertDatabaseHas('second_process_manpowers', [
            'report_id' => $legacyReport->id,
            'name' => 'Bob Packing',
            'role' => 'packing',
        ]);
        $this->assertDatabaseHas('second_process_manpowers', [
            'report_id' => $legacyReport->id,
            'name' => 'Charlie Custom',
            'role' => 'Buffing Specialist',
        ]);
    }
}
