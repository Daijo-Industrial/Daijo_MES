<?php

namespace Tests\Feature;

use App\Http\Middleware\LogApiWebhooks;
use App\Http\Middleware\UpdateUserActivity;
use App\Livewire\Admin\UserActivityDashboard;
use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UserActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;
    protected Role $operatorRole;
    protected User $superAdmin;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        $this->superAdmin = User::create([
            'name'       => 'Super Admin',
            'email'      => 'superadmin@daijo.com',
            'username'   => 'superadmin',
            'password'   => Hash::make('password123'),
            'role_id'    => $this->superAdminRole->id,
            'is_active'  => true,
        ]);

        $this->operatorUser = User::create([
            'name'       => 'Operator Test',
            'email'      => 'operator@daijo.com',
            'username'   => 'operator',
            'password'   => Hash::make('password123'),
            'role_id'    => $this->operatorRole->id,
            'is_active'  => true,
        ]);
    }

    public function test_non_superadmin_cannot_access_user_activity_dashboard(): void
    {
        $this->actingAs($this->operatorUser);

        Livewire::test(UserActivityDashboard::class)
            ->assertStatus(403);
    }

    public function test_superadmin_can_access_user_activity_dashboard(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserActivityDashboard::class)
            ->assertStatus(200)
            ->assertSee('User Activity', escape: false)
            ->assertSee('Retention: 365 Days');
    }

    public function test_login_event_creates_activity_log_and_updates_last_activity_at(): void
    {
        $this->assertNull($this->operatorUser->last_activity_at);

        event(new Login('web', $this->operatorUser, false));

        $this->operatorUser->refresh();
        $this->assertNotNull($this->operatorUser->last_activity_at);

        $activity = Activity::where('log_name', 'auth')
            ->where('description', 'like', '%User logged in%')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($this->operatorUser->id, $activity->causer_id);
    }

    public function test_failed_login_creates_activity_log_without_exposing_password(): void
    {
        event(new Failed('web', null, [
            'email'    => 'unknown@daijo.com',
            'password' => 'SuperSecret123!',
        ]));

        $activity = Activity::where('log_name', 'auth')
            ->where('description', 'like', '%Failed login attempt%')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('unknown@daijo.com', $activity->properties['credentials']['email'] ?? null);

        // Verify password is NOT present in properties JSON
        $json = json_encode($activity->properties);
        $this->assertStringNotContainsString('SuperSecret123!', $json);
        $this->assertArrayNotHasKey('password', $activity->properties['credentials'] ?? []);
    }

    public function test_logout_event_creates_activity_log(): void
    {
        event(new Logout('web', $this->operatorUser));

        $activity = Activity::where('log_name', 'auth')
            ->where('description', 'like', '%User logged out%')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($this->operatorUser->id, $activity->causer_id);
    }

    public function test_user_activity_middleware_throttles_last_activity_at_updates(): void
    {
        $this->actingAs($this->operatorUser);
        Cache::flush();

        $middleware = new UpdateUserActivity();
        $request = Request::create('/dashboard', 'GET');

        // First request: should update last_activity_at and set cache
        $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->operatorUser->refresh();
        $firstTimestamp = $this->operatorUser->last_activity_at;
        $this->assertNotNull($firstTimestamp);
        $this->assertTrue(Cache::has('user_activity_ping_' . $this->operatorUser->id));

        // Advance carbon time by 1 minute (within the 5-minute throttle)
        Carbon::setTestNow(now()->addMinute());

        $middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->operatorUser->refresh();
        $this->assertEquals($firstTimestamp->toIso8601String(), $this->operatorUser->last_activity_at->toIso8601String());

        // Clear Carbon test now
        Carbon::setTestNow();
    }

    public function test_inactive_users_query_detects_users_with_no_activity_for_over_30_days(): void
    {
        // 1. User active yesterday (not inactive)
        $activeUser = User::create([
            'name'             => 'Active User',
            'email'            => 'active@daijo.com',
            'username'         => 'activeuser',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => now()->subDay(),
        ]);

        // 2. User inactive 35 days ago
        $inactive35d = User::create([
            'name'             => 'Inactive 35d',
            'email'            => 'inactive35@daijo.com',
            'username'         => 'inactive35',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => now()->subDays(35),
        ]);

        // 3. User with null last_activity_at (never logged in)
        $neverActive = User::create([
            'name'             => 'Never Active',
            'email'            => 'never@daijo.com',
            'username'         => 'neveractive',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => null,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(UserActivityDashboard::class)
            ->call('setTab', 'inactive')
            ->assertSee('Inactive 35d')
            ->assertSee('Never Active')
            ->assertDontSee('Active User');
    }

    public function test_superadmin_can_manually_deactivate_inactive_user(): void
    {
        $inactiveUser = User::create([
            'name'             => 'Target Inactive',
            'email'            => 'targetinactive@daijo.com',
            'username'         => 'targetinactive',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => now()->subDays(40),
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(UserActivityDashboard::class)
            ->call('setTab', 'inactive')
            ->call('confirmDeactivate', $inactiveUser->id)
            ->assertSet('showDeactivateModal', true)
            ->call('deactivateUser')
            ->assertSet('showDeactivateModal', false);

        $inactiveUser->refresh();
        $this->assertFalse($inactiveUser->is_active);

        // Verify audit log entry was created
        $audit = Activity::where('log_name', 'user_management')
            ->where('subject_id', $inactiveUser->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($this->superAdmin->id, $audit->causer_id);
        $this->assertStringContainsString('Deactivated inactive user', $audit->description);
    }

    public function test_superadmin_can_bulk_deactivate_inactive_users(): void
    {
        $user1 = User::create([
            'name'             => 'Bulk User 1',
            'email'            => 'bulk1@daijo.com',
            'username'         => 'bulk1',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => now()->subDays(35),
        ]);

        $user2 = User::create([
            'name'             => 'Bulk User 2',
            'email'            => 'bulk2@daijo.com',
            'username'         => 'bulk2',
            'password'         => Hash::make('password'),
            'role_id'          => $this->operatorRole->id,
            'is_active'        => true,
            'last_activity_at' => now()->subDays(45),
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(UserActivityDashboard::class)
            ->call('setTab', 'inactive')
            ->set('selectedUsers', [(string)$user1->id, (string)$user2->id])
            ->call('confirmBulkDeactivate')
            ->assertSet('showBulkDeactivateModal', true)
            ->call('bulkDeactivate')
            ->assertSet('showBulkDeactivateModal', false);

        $this->assertFalse($user1->refresh()->is_active);
        $this->assertFalse($user2->refresh()->is_active);
    }

    public function test_model_mutations_create_activity_logs_without_sensitive_attributes(): void
    {
        $this->actingAs($this->superAdmin);

        $user = User::create([
            'name'       => 'Audit Model User',
            'email'      => 'audituser@daijo.com',
            'username'   => 'audituser',
            'password'   => Hash::make('sensitive_password_123'),
            'role_id'    => $this->operatorRole->id,
            'is_active'  => true,
        ]);

        $userActivity = Activity::where('subject_id', $user->id)
            ->where('log_name', 'user')
            ->first();

        $this->assertNotNull($userActivity);
        $propertiesJson = json_encode($userActivity->properties);
        $this->assertStringNotContainsString('sensitive_password_123', $propertiesJson);

        // Test SecondProcessReport audit logging
        $report = SecondProcessReport::create([
            'date'            => '2026-10-16',
            'unit_line'       => 'Line 1',
            'shift'           => 1,
            'process_prod'    => 'Painting',
            'status'          => 'draft',
            'part_number'     => 'PART-TEST-001',
            'customer'        => 'N/A',
            'jumlah_ok'       => 100,
            'jumlah_ng'       => 5,
            'created_by_name' => 'Operator Test',
        ]);

        $reportActivity = Activity::where('subject_id', $report->id)
            ->where('log_name', 'second_process')
            ->first();

        $this->assertNotNull($reportActivity);
        $this->assertEquals('second_process', $reportActivity->log_name);
    }

    public function test_inbound_api_logger_middleware_records_activity_log(): void
    {
        $middleware = new LogApiWebhooks();
        $request = Request::create('/api/sap/do', 'POST', [
            'doc_num' => 'DO-2026-001',
            'token'   => 'secret_api_token_123',
        ]);

        $middleware->handle($request, function () {
            return new Response('{"status":"success"}', 200);
        });

        $apiActivity = Activity::where('log_name', 'api')
            ->where('description', 'like', '%/api/sap/do%')
            ->first();

        $this->assertNotNull($apiActivity);
        $this->assertEquals(200, $apiActivity->properties['status_code']);
        $this->assertEquals('/api/sap/do', $apiActivity->properties['endpoint']);

        // Verify token is masked/excluded
        $payloadJson = json_encode($apiActivity->properties['payload']);
        $this->assertStringNotContainsString('secret_api_token_123', $payloadJson);
    }
}
