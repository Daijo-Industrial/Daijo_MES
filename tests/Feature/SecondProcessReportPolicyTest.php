<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SecondProcessReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $supervisorUser;
    protected User $leaderUser;
    protected User $checkerUser;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $supervisorRole = Role::firstOrCreate(['name' => 'SUPERVISOR']);
        $leaderRole = Role::firstOrCreate(['name' => 'LEADER']);
        $checkerRole = Role::firstOrCreate(['name' => 'CHECKER']);
        $operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id, 'name' => 'Admin User']);
        $this->supervisorUser = User::factory()->create(['role_id' => $supervisorRole->id, 'name' => 'Supervisor Dave']);
        $this->leaderUser = User::factory()->create(['role_id' => $leaderRole->id, 'name' => 'Leader Alice']);
        $this->checkerUser = User::factory()->create(['role_id' => $checkerRole->id, 'name' => 'Checker Bob']);
        $this->operatorUser = User::factory()->create(['role_id' => $operatorRole->id, 'name' => 'Operator Charlie']);
    }

    public function test_policy_update_ability(): void
    {
        $draftReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'PART-POL-01',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
            'created_by_name' => 'Operator Charlie',
        ]);

        $submittedReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'PART-POL-02',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
            'created_by_name' => 'Operator Charlie',
        ]);

        // In draft: Admin, Creator (Operator Charlie), Checker, Leader can update
        $this->assertTrue($this->adminUser->can('update', $draftReport));
        $this->assertTrue($this->operatorUser->can('update', $draftReport));
        $this->assertTrue($this->checkerUser->can('update', $draftReport));
        $this->assertTrue($this->leaderUser->can('update', $draftReport));

        // Another operator cannot update
        $otherOperator = User::factory()->create([
            'role_id' => $this->operatorUser->role_id,
            'name' => 'Other Operator',
        ]);
        $this->assertFalse($otherOperator->can('update', $draftReport));

        // When submitted: NO ONE can update (must be rejected to draft first)
        $this->assertFalse($this->adminUser->can('update', $submittedReport));
        $this->assertFalse($this->operatorUser->can('update', $submittedReport));
        $this->assertFalse($this->leaderUser->can('update', $submittedReport));
    }

    public function test_policy_delete_ability(): void
    {
        $report = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'PART-POL-03',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);

        // Admin and Supervisor can delete
        $this->assertTrue($this->adminUser->can('delete', $report));
        $this->assertTrue($this->supervisorUser->can('delete', $report));

        // Checker, Leader, Operator cannot delete
        $this->assertFalse($this->checkerUser->can('delete', $report));
        $this->assertFalse($this->leaderUser->can('delete', $report));
        $this->assertFalse($this->operatorUser->can('delete', $report));
    }

    public function test_policy_sign_ability(): void
    {
        $draftReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'PART-POL-04',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);

        // Checker slot on draft
        $this->assertTrue($this->checkerUser->can('sign', [$draftReport, 'checker']));
        $this->assertTrue($this->adminUser->can('sign', [$draftReport, 'checker']));
        // Leader and Supervisor cannot sign draft reports
        $this->assertFalse($this->leaderUser->can('sign', [$draftReport, 'leader']));
        $this->assertFalse($this->supervisorUser->can('sign', [$draftReport, 'acknowledged']));

        // Leader slot on submitted report
        $submittedReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'PART-POL-04-SUB',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);
        $this->assertTrue($this->leaderUser->can('sign', [$submittedReport, 'leader']));
        $this->assertTrue($this->adminUser->can('sign', [$submittedReport, 'leader']));
        $this->assertFalse($this->checkerUser->can('sign', [$submittedReport, 'leader']));
        $this->assertFalse($this->supervisorUser->can('sign', [$submittedReport, 'acknowledged']));

        // Supervisor slot on leader_approved report
        $leaderReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'leader_approved',
            'part_number' => 'PART-POL-04-LEAD',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);
        $this->assertTrue($this->supervisorUser->can('sign', [$leaderReport, 'acknowledged']));
        $this->assertTrue($this->adminUser->can('sign', [$leaderReport, 'acknowledged']));
        $this->assertFalse($this->leaderUser->can('sign', [$leaderReport, 'acknowledged']));
    }

    public function test_policy_reject_ability(): void
    {
        $report = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'submitted',
            'part_number' => 'PART-POL-05',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);

        // Leader, Supervisor, Admin can reject submitted reports
        $this->assertTrue($this->leaderUser->can('reject', $report));
        $this->assertTrue($this->supervisorUser->can('reject', $report));
        $this->assertTrue($this->adminUser->can('reject', $report));

        // Checker and Operator cannot reject
        $this->assertFalse($this->checkerUser->can('reject', $report));
        $this->assertFalse($this->operatorUser->can('reject', $report));

        // Draft report cannot be rejected
        $draftReport = SecondProcessReport::create([
            'date' => '2026-07-07',
            'unit_line' => 'Painting Line A',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'PART-POL-05-DRAFT',
            'part_name' => 'Door Trim',
            'model' => 'Sedan 2026',
            'customer' => 'Toyota Motor Corp',
        ]);
        $this->assertFalse($this->adminUser->can('reject', $draftReport));
        $this->assertFalse($this->leaderUser->can('reject', $draftReport));
    }
}
