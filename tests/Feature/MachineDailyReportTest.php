<?php

namespace Tests\Feature;

use App\Models\DailyItemCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MachineDailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected Role $operatorRole;

    protected Role $ppicRole;

    protected Role $adminRole;

    protected User $operatorUser;

    protected User $ppicUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $this->operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $this->ppicRole = Role::firstOrCreate(['name' => 'PPIC']);
        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);

        // Setup Users
        $this->operatorUser = User::factory()->create([
            'name' => 'MCH-01',
            'username' => 'mch01',
            'role_id' => $this->operatorRole->id,
            'is_active' => true,
        ]);

        $this->ppicUser = User::factory()->create([
            'name' => 'Staff PPIC',
            'username' => 'ppic',
            'role_id' => $this->ppicRole->id,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Super Administrator',
            'username' => 'admin',
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test guest redirects to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/operator/daily-report')->assertRedirect('/login');
        $this->get('/ppic/machine-daily-report')->assertRedirect('/login');
    }

    /**
     * Test operator access control.
     */
    public function test_operator_can_access_own_daily_report_but_not_ppic_view(): void
    {
        $this->actingAs($this->operatorUser);

        // Operator can access `/operator/daily-report`
        $this->get('/operator/daily-report')
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Report\MachineDailyReport::class);

        // Operator gets 403 on `/ppic/machine-daily-report`
        $this->get('/ppic/machine-daily-report')->assertForbidden();
    }

    /**
     * Test PPIC access control.
     */
    public function test_ppic_can_access_ppic_view_but_not_operator_view(): void
    {
        $this->actingAs($this->ppicUser);

        // PPIC can access `/ppic/machine-daily-report`
        $this->get('/ppic/machine-daily-report')
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Report\MachineDailyReport::class);

        // PPIC gets 403 on `/operator/daily-report`
        $this->get('/operator/daily-report')->assertForbidden();
    }

    /**
     * Test Admin access control.
     */
    public function test_admin_can_access_both_views(): void
    {
        $this->actingAs($this->adminUser);

        $this->get('/operator/daily-report')->assertOk();
        $this->get('/ppic/machine-daily-report')->assertOk();
    }

    /**
     * Test Livewire component mounts and filters properly.
     */
    public function test_component_renders_correct_data_for_selected_machine_and_date(): void
    {
        $this->actingAs($this->adminUser);

        $plan = DailyItemCode::create([
            'user_id' => $this->operatorUser->id,
            'item_code' => 'ITM-TEST-001',
            'quantity' => 500,
            'actual_quantity' => 0,
            'final_quantity' => 0,
            'loss_package_quantity' => 0,
            'shift' => 1,
            'schedule_date' => '2026-07-17',
            'start_date' => '2026-07-17',
            'start_time' => '07:30:00',
            'end_date' => '2026-07-17',
            'end_time' => '15:30:00',
        ]);

        \App\Models\HourlyRemark::create([
            'dic_id' => $plan->id,
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'target' => 60,
            'actual' => 50,
            'actual_production' => 50,
            'pic' => 'mch01',
        ]);

        Livewire::test(\App\Livewire\Report\MachineDailyReport::class)
            ->set('selectedDate', '2026-07-17')
            ->set('selectedMachineId', $this->operatorUser->id)
            ->assertOk()
            ->assertSee('ITM-TEST-001')
            ->assertSee('500');
    }
}
