<?php

namespace Tests\Feature;

use App\Models\AdjustMachineLog;
use App\Models\AdjustMachineNgDetail;
use App\Models\MachineJob;
use App\Models\OperatorUser;
use App\Models\ProductionNgType;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductionDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdjustMachineNgTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        config(['activitylog.enabled' => false]);

        $this->createTestSchema();
        $this->seedTestData();
    }

    private function createTestSchema(): void
    {
        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operator_user', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('adjust_machine_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('pic');
            $table->string('item_code');
            $table->dateTime('end_time')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_ng_types', function ($table) {
            $table->id();
            $table->string('ng_type');
            $table->timestamps();
        });

        Schema::create('adjust_machine_ng_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('adjust_machine_log_id');
            $table->unsignedBigInteger('ng_type_id');
            $table->integer('ng_quantity')->default(0);
            $table->string('ng_remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('machine_jobs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('dic_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('employee_name')->nullable();
            $table->integer('shift')->nullable()->default(1);
            $table->timestamps();
        });

        Schema::create('daily_item_codes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('item_code');
            $table->date('start_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('is_done')->nullable();
            $table->timestamps();
        });
    }

    private function seedTestData(): void
    {
        Role::create(['name' => 'OPERATOR']);

        OperatorUser::create(['name' => 'Budi Santoso', 'password' => '123', 'position' => 'Adjuster']);
        OperatorUser::create(['name' => 'Haerul Anwar', 'password' => '123', 'position' => 'Adjuster']);

        ProductionNgType::create(['id' => 1, 'ng_type' => 'Short Shot']);
        ProductionNgType::create(['id' => 2, 'ng_type' => 'Flash / Burry']);
        ProductionNgType::create(['id' => 3, 'ng_type' => 'Silver']);
    }

    public function test_start_adjust_machine_persists_ng_details()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $ngItems = [
            ['ng_type_id' => 1, 'ng_quantity' => 5, 'ng_remarks' => 'Setting awal'],
            ['ng_type_id' => 2, 'ng_quantity' => 3, 'ng_remarks' => 'Mould panas'],
        ];

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
            'ng_items' => $ngItems,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('adjust_machine_logs', [
            'user_id' => $machine->id,
            'pic' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
        ]);

        $log = AdjustMachineLog::where('user_id', $machine->id)->first();
        $this->assertNotNull($log);

        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1,
            'ng_quantity' => 5,
            'ng_remarks' => 'Setting awal',
        ]);

        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 2,
            'ng_quantity' => 3,
            'ng_remarks' => 'Mould panas',
        ]);

        $this->assertEquals(8, $log->total_ng);
        $this->assertCount(2, $log->ngDetails);
    }

    public function test_start_adjust_machine_without_ng_items_succeeds()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f_clean@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
        ]);

        $response->assertStatus(200);

        $log = AdjustMachineLog::where('user_id', $machine->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(0, $log->total_ng);
        $this->assertCount(0, $log->ngDetails);
    }

    public function test_get_adjust_machine_details_returns_ng_breakdown()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f_details@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $log = AdjustMachineLog::create([
            'user_id' => $machine->id,
            'pic' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
            'created_at' => Carbon::now()->subHour(),
            'end_time' => Carbon::now(),
            'remark' => 'Testing details',
        ]);

        AdjustMachineNgDetail::create([
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1,
            'ng_quantity' => 12,
            'ng_remarks' => 'Short Shot parah',
        ]);

        $response = $this->actingAs($machine)->getJson(route('adjust.machine.details', $log->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'total_ng' => 12,
                'ng_details' => [
                    [
                        'ng_type_id' => 1,
                        'ng_type_name' => 'Short Shot',
                        'ng_quantity' => 12,
                        'ng_remarks' => 'Short Shot parah',
                    ],
                ],
            ],
        ]);
    }

    public function test_end_adjust_machine_saves_ng_details()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f_end@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $log = AdjustMachineLog::create([
            'user_id' => $machine->id,
            'pic' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
            'created_at' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        $ngItems = [
            ['ng_type_id' => 1, 'ng_quantity' => 15, 'ng_remarks' => 'Reject trial setting'],
            ['ng_type_id' => 3, 'ng_quantity' => 5, 'ng_remarks' => 'Silver setting'],
        ];

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.end'), [
            'remarks' => 'Selesai adjust mould',
            'ng_items' => $ngItems,
        ]);

        $response->assertStatus(200);

        $log->refresh();
        $this->assertNotNull($log->end_time);
        $this->assertEquals('Selesai adjust mould', $log->remark);
        $this->assertEquals(20, $log->total_ng);

        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1,
            'ng_quantity' => 15,
            'ng_remarks' => 'Reject trial setting',
        ]);

        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 3,
            'ng_quantity' => 5,
            'ng_remarks' => 'Silver setting',
        ]);
    }

    public function test_update_adjust_machine_syncs_ng_details()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f_update@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $log = AdjustMachineLog::create([
            'user_id' => $machine->id,
            'pic' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
            'created_at' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
        ]);

        // Old detail
        AdjustMachineNgDetail::create([
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1,
            'ng_quantity' => 5,
            'ng_remarks' => 'Old remark',
        ]);

        // Update with new NG details
        $newNgItems = [
            ['ng_type_id' => 2, 'ng_quantity' => 7, 'ng_remarks' => 'Flash baru'],
            ['ng_type_id' => 3, 'ng_quantity' => 4, 'ng_remarks' => 'Silver baru'],
        ];

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.update', $log->id), [
            'created_at' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'remark' => 'Updated log with NG',
            'ng_items' => $newNgItems,
        ]);

        $response->assertStatus(200);

        // Check old detail is gone
        $this->assertDatabaseMissing('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1,
        ]);

        // Check new details are present
        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 2,
            'ng_quantity' => 7,
            'ng_remarks' => 'Flash baru',
        ]);

        $this->assertDatabaseHas('adjust_machine_ng_details', [
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 3,
            'ng_quantity' => 4,
            'ng_remarks' => 'Silver baru',
        ]);

        $log->refresh();
        $this->assertEquals(11, $log->total_ng);
    }

    public function test_production_dashboard_service_process_ng_breakdown_includes_adjuster_ng()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name' => '0350F',
            'email' => '0350f_dash@daijo.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $log = AdjustMachineLog::create([
            'user_id' => $machine->id,
            'pic' => 'Budi Santoso',
            'item_code' => 'ITEM-01',
            'created_at' => Carbon::now(),
            'end_time' => Carbon::now()->addMinutes(30),
        ]);

        AdjustMachineNgDetail::create([
            'adjust_machine_log_id' => $log->id,
            'ng_type_id' => 1, // Short Shot
            'ng_quantity' => 6,
            'ng_remarks' => 'NG adjust awal',
        ]);

        $log->load('ngDetails.ngType');

        $service = new ProductionDashboardService();

        // Call processNgBreakdown via reflection since it's private/protected
        $refMethod = new \ReflectionMethod(ProductionDashboardService::class, 'processNgBreakdown');
        $refMethod->setAccessible(true);

        $hourlyRemarks = collect([]);
        $adjustLogs = collect([$log]);

        $breakdown = $refMethod->invoke($service, $hourlyRemarks, null, $adjustLogs);

        // Assert Short Shot is in breakdown
        $this->assertArrayHasKey('Short Shot', $breakdown);
        $this->assertEquals(6, $breakdown['Short Shot']['total']);
        $this->assertCount(1, $breakdown['Short Shot']['models']);
        $this->assertEquals('ITEM-01', $breakdown['Short Shot']['models'][0]['item_code']);
        $this->assertStringContainsString('[Adjuster: Budi Santoso]', $breakdown['Short Shot']['models'][0]['records'][0]['remark']);
    }
}
