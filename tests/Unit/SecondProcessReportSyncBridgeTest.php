<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use App\Models\User;
use App\Policies\SecondProcessReportPolicy;
use App\Services\SecondProcessReportSyncBridge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SecondProcessReportSyncBridgeTest extends TestCase
{
    private function createMockUser(string $roleName, array $permissions = []): User
    {
        $user = new User();
        $user->id = rand(100, 999);
        $user->name = "Test {$roleName}";

        $role = new Role();
        $role->name = $roleName;
        $user->setRelation('role', $role);

        Gate::before(function ($u, $ability) use ($user, $roleName, $permissions) {
            if ($u->id === $user->id) {
                if ($roleName === 'SUPER-ADMIN' || $roleName === 'ADMIN') {
                    return true;
                }
                if (in_array($ability, $permissions, true)) {
                    return true;
                }
            }
        });

        return $user;
    }

    public function test_build_report_data_normalizes_status_to_acknowledged_and_calculates_sisa_input()
    {
        $bridge = new SecondProcessReportSyncBridge();

        $wo = new SpWorkOrder([
            'wo_number' => 'WO-202611-001',
            'part_number' => 'PART-100',
            'part_name' => 'Panel Front',
            'customer' => 'DAIKIN',
            'model' => 'MODEL-X',
            'unit_line' => 'Line 2',
            'shift' => '1',
            'process_prod' => 'Painting',
            'target_qty' => 800,
        ]);

        $session = new SpProductionSession([
            'shift' => '1',
            'unit_line' => 'Line 2',
            'total_input' => 1000,
            'total_good' => 850,
            'total_reject' => 50,
            'total_scrap' => 10,
            'yield' => 94.44,
            'remarks' => 'Sisa 90 pcs WIP tersimpan di buffer',
            'started_at' => Carbon::parse('2026-11-23 07:30:00'),
            'finished_at' => Carbon::parse('2026-11-23 15:30:00'),
            'approved_at' => Carbon::parse('2026-11-23 16:00:00'),
        ]);
        $session->id = 42;

        $session->setRelation('workOrder', $wo);
        $session->setRelation('inputEntries', collect());

        $reportData = $bridge->buildReportData($session);

        // Lifecycle status must be 'acknowledged' (matching legacy status domain)
        $this->assertEquals('acknowledged', $reportData['status']);
        $this->assertEquals(42, $reportData['sp_production_session_id']);
        $this->assertEquals('PART-100', $reportData['part_number']);
        $this->assertEquals('Panel Front', $reportData['part_name']);
        $this->assertEquals('DAIKIN', $reportData['customer']);

        // Production numbers
        $this->assertEquals(900, $reportData['jumlah_output']);
        $this->assertEquals(850, $reportData['jumlah_ok']);
        $this->assertEquals(50, $reportData['jumlah_ng']);
        $this->assertEquals(10, $reportData['jml_ng_lebur']);

        // Material Reconciliation: Total Input (1000) - Total Output (850 + 50 + 10 = 910) = 90
        $this->assertEquals(90, $reportData['sisa_input']);
        $this->assertEquals('Sisa 90 pcs WIP tersimpan di buffer', $reportData['sisa_input_remark']);
    }

    public function test_build_report_data_with_zero_sisa_sets_null_remark()
    {
        $bridge = new SecondProcessReportSyncBridge();

        $wo = new SpWorkOrder([
            'wo_number' => 'WO-202611-002',
            'part_number' => 'PART-200',
            'target_qty' => 500,
        ]);

        $session = new SpProductionSession([
            'total_input' => 500,
            'total_good' => 480,
            'total_reject' => 20,
            'total_scrap' => 0,
            'yield' => 96.0,
            'remarks' => 'Completed smoothly',
        ]);
        $session->id = 43;

        $session->setRelation('workOrder', $wo);
        $session->setRelation('inputEntries', collect());

        $reportData = $bridge->buildReportData($session);

        // 500 input - 500 output = 0 sisa
        $this->assertEquals(0, $reportData['sisa_input']);
        $this->assertNull($reportData['sisa_input_remark']);
    }

    public function test_second_process_report_policy_locks_synced_reports_for_non_admins()
    {
        $policy = new SecondProcessReportPolicy();

        $admin = $this->createMockUser('ADMIN', ['second-process-reports']);
        $leader = $this->createMockUser('LEADER', ['second-process-reports']);
        $unauthorized = $this->createMockUser('OPERATOR', []);

        $syncedReport = new SecondProcessReport([
            'sp_production_session_id' => 42,
            'status' => 'acknowledged',
        ]);
        $syncedReport->id = 101;

        $manualDraftReport = new SecondProcessReport([
            'sp_production_session_id' => null,
            'status' => 'draft',
        ]);
        $manualDraftReport->id = 102;

        // Synced report: Admin can edit, Leader is denied
        $this->assertTrue($policy->update($admin, $syncedReport)->allowed());
        $this->assertTrue($policy->update($leader, $syncedReport)->denied());
        $this->assertStringContainsString('Sesi Tablet Digital', $policy->update($leader, $syncedReport)->message());

        // Manual draft report: Leader can edit, Unauthorized is denied
        $this->assertTrue($policy->update($leader, $manualDraftReport)->allowed());
        $this->assertTrue($policy->update($unauthorized, $manualDraftReport)->denied());
    }

    public function test_second_process_report_defines_session_relationship()
    {
        $report = new SecondProcessReport();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $report->session());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $report->spProductionSession());
    }
}
