<?php

namespace Tests\Unit;

use App\Models\FirstPieceInspection;
use App\Models\IpqcInspection;
use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\SpWorkOrder;
use App\Models\User;
use App\Policies\FirstPieceInspectionPolicy;
use App\Policies\IpqcInspectionPolicy;
use App\Policies\SecondProcessReportPolicy;
use App\Policies\SpWorkOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SecondProcessAuthorizationPolicyTest extends TestCase
{
    private function createMockUser(string $roleName, array $permissions = []): User
    {
        $user = new User();
        $user->id = rand(100, 999);
        $user->name = "Test {$roleName}";

        $role = new Role();
        $role->name = $roleName;
        $user->setRelation('role', $role);

        // Bind mock permissions to Gate for test isolation
        Gate::before(function ($u, $ability) use ($user, $roleName, $permissions) {
            if ($u->id === $user->id) {
                if ($roleName === 'SUPER-ADMIN') {
                    return true;
                }
                if (in_array($ability, $permissions, true)) {
                    return true;
                }
            }
        });

        return $user;
    }

    public function test_sp_work_order_policy_allows_authorized_users_and_denies_unauthorized()
    {
        $policy = new SpWorkOrderPolicy();

        $authorizedUser = $this->createMockUser('LEADER', ['second-process-work-orders']);
        $unauthorizedUser = $this->createMockUser('OPERATOR', []);

        $draftWo = new SpWorkOrder(['status' => 'draft']);
        $plannedWo = new SpWorkOrder(['status' => 'planned']);

        // viewAny & create
        $this->assertTrue($policy->viewAny($authorizedUser));
        $this->assertFalse($policy->viewAny($unauthorizedUser));
        $this->assertTrue($policy->create($authorizedUser));
        $this->assertFalse($policy->create($unauthorizedUser));

        // update: draft allowed, planned denied
        $this->assertTrue($policy->update($authorizedUser, $draftWo)->allowed());
        $this->assertTrue($policy->update($authorizedUser, $plannedWo)->denied());
        $this->assertTrue($policy->update($unauthorizedUser, $draftWo)->denied());

        // release: draft allowed
        $this->assertTrue($policy->release($authorizedUser, $draftWo)->allowed());
        $this->assertTrue($policy->release($authorizedUser, $plannedWo)->denied());
    }

    public function test_first_piece_inspection_policy_enforces_qc_signatures()
    {
        $policy = new FirstPieceInspectionPolicy();

        $qcUser = $this->createMockUser('QUALITY', ['second-process-first-piece']);
        $operatorUser = $this->createMockUser('OPERATOR', ['second-process-first-piece']);
        $unauthorizedUser = $this->createMockUser('STORE', []);

        $inspection = new FirstPieceInspection(['checked_at' => null]);

        $this->assertTrue($policy->viewAny($qcUser));
        $this->assertFalse($policy->viewAny($unauthorizedUser));

        // Operator can sign 'prepared', but cannot sign 'checked'
        $this->assertTrue($policy->sign($operatorUser, $inspection, 'prepared')->allowed());
        $this->assertTrue($policy->sign($operatorUser, $inspection, 'checked')->denied());

        // Quality can sign 'checked'
        $this->assertTrue($policy->sign($qcUser, $inspection, 'checked')->allowed());

        // Approval requires prior checked_at
        $this->assertTrue($policy->sign($qcUser, $inspection, 'approved')->denied());
        $inspection->checked_at = now();
        $this->assertTrue($policy->sign($qcUser, $inspection, 'approved')->allowed());
    }

    public function test_ipqc_inspection_policy_guards_completed_inspections()
    {
        $policy = new IpqcInspectionPolicy();

        $qcUser = $this->createMockUser('QUALITY', ['second-process-ipqc']);
        $unauthorizedUser = $this->createMockUser('OPERATOR', []);

        $ongoing = new IpqcInspection(['status' => 'ongoing']);
        $completed = new IpqcInspection(['status' => 'completed']);

        $this->assertTrue($policy->viewAny($qcUser));
        $this->assertFalse($policy->viewAny($unauthorizedUser));

        $this->assertTrue($policy->update($qcUser, $ongoing)->allowed());
        $this->assertTrue($policy->update($qcUser, $completed)->denied());
    }

    public function test_second_process_report_policy_requires_feature_permission()
    {
        $policy = new SecondProcessReportPolicy();

        $reportUser = $this->createMockUser('LEADER', ['second-process-reports']);
        $unauthorizedUser = $this->createMockUser('OPERATOR', []);

        $draftReport = new SecondProcessReport(['status' => 'draft']);

        $this->assertTrue($policy->viewAny($reportUser));
        $this->assertFalse($policy->viewAny($unauthorizedUser));

        $this->assertTrue($policy->create($reportUser));
        $this->assertFalse($policy->create($unauthorizedUser));

        $this->assertTrue($policy->update($reportUser, $draftReport)->allowed());
        $this->assertTrue($policy->update($unauthorizedUser, $draftReport)->denied());
    }
}

