<?php

namespace Tests\Feature;

use App\Models\FirstPieceInspection;
use App\Models\Role;
use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondProcessDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->user = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    public function test_second_process_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('second-process.dashboard'));
        $response->assertRedirect('/login');
    }

    public function test_second_process_dashboard_loads_with_kpis_and_line_status(): void
    {
        // 1. Create a Work Order
        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-SP-20260731-0001',
            'planned_date' => now()->format('Y-m-d'),
            'unit_line' => 'Line A',
            'process_prod' => 'Painting',
            'part_number' => '401-41019967',
            'part_name' => 'Molding Side REF',
            'customer' => 'PT YAMAHA',
            'target_qty' => 1000,
            'status' => 'in_progress',
        ]);

        // 2. Create QC First Piece Inspection
        FirstPieceInspection::create([
            'date' => now()->format('Y-m-d'),
            'model' => 'KS PE',
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'overall_judgement' => 'OK',
            'checked_by' => 'QC Inspector 1',
            'checked_at' => now(),
        ]);

        // 3. Create Production Session
        $session = SpProductionSession::create([
            'work_order_id' => $wo->id,
            'operator_id' => $this->user->id,
            'unit_line' => 'Line A',
            'shift' => 1,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // 4. Access dashboard
        $response = $this->actingAs($this->user)->get(route('second-process.dashboard'));

        $response->assertOk();
        $response->assertSee('Second Process Operator & Shop Floor Dashboard');
        $response->assertSee('Line A');
        $response->assertSee('WO-SP-20260731-0001');
        $response->assertSee('Operator Screen');
    }
}
