<?php

namespace Tests\Feature;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MachineJob;
use App\Models\OperatorUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOperatorManualHourlyRemarksTest extends TestCase
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

        $this->createTestSchema();
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('daily_item_codes', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('item_code')->nullable();
            $table->integer('shift')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('actual_quantity')->default(0);
            $table->integer('final_quantity')->nullable();
            $table->integer('loss_package_quantity')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('temporal_cycle_time')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('is_done')->nullable();
            $table->timestamps();
        });

        Schema::create('machine_jobs', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('item_code')->nullable();
            $table->integer('shift')->nullable();
            $table->string('employee_name')->nullable();
            $table->unsignedBigInteger('dic_id')->nullable();
            $table->timestamps();
        });

        Schema::create('hourly_remarks', function ($table) {
            $table->id();
            $table->foreignId('dic_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('target')->default(0);
            $table->integer('actual')->default(0);
            $table->integer('actual_production')->nullable();
            $table->integer('NG')->nullable();
            $table->text('remark')->nullable();
            $table->boolean('is_achieve')->default(0);
            $table->string('pic');
            $table->string('pic_2')->nullable();
            $table->string('pic_3')->nullable();
            $table->timestamps();
        });

        Schema::create('production_scanned_data', function ($table) {
            $table->id();
            $table->foreignId('dic_id');
            $table->string('spk_code')->nullable();
            $table->string('item_code')->nullable();
            $table->string('warehouse')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('label')->nullable();
            $table->string('user')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();
        });

        Schema::create('operator_user', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('password')->default('123');
            $table->string('profile_picture')->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('sap_inventory_fg', function ($table) {
            $table->id();
            $table->string('item_code')->nullable();
            $table->float('cycle_time')->nullable();
            $table->timestamps();
        });
    }

    public function test_adminoperator_view_renders_successfully_for_operator(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        OperatorUser::create(['name' => 'Siti Aminah', 'position' => 'Operator']);
        OperatorUser::create(['name' => 'Budi Santoso', 'position' => 'Operator']);

        DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-84796-17AA0',
            'shift' => 3,
            'start_date' => Carbon::yesterday()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'start_time' => '23:30',
            'end_time' => '07:30',
            'quantity' => 800,
        ]);

        $response = $this->get(route('adminoperator'));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Produksi Operator');
        $response->assertSee('401-84796-17AA0');
        $response->assertSee('Shift 3');
        $response->assertSee('Tambah Data Jam Manual');
        $response->assertSee('Generate Semua Jam Shift 3');
    }

    public function test_operator_can_create_manual_hourly_remark_without_scan(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-84796-17AA0',
            'shift' => 3,
            'temporal_cycle_time' => '30', // target 120
            'start_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->post(route('hourly-remarks.store'), [
            'dic_id' => $dic->id,
            'start_time' => '23:30',
            'end_time' => '00:30',
            'actual_production' => 110,
            'target' => 120,
            'pic' => 'Siti Aminah',
            'remark' => 'Produksi shift 3 manual susulan',
            'NG' => 2,
        ]);

        $response->assertRedirect();

        $created = HourlyRemark::where('dic_id', $dic->id)
            ->where('start_time', '23:30:00')
            ->first();

        $this->assertNotNull($created);
        $this->assertEquals('23:30:00', $created->start_time);
        $this->assertEquals('00:30:00', $created->end_time);
        $this->assertEquals(110, $created->actual_production);
        $this->assertEquals(0, $created->actual); // tanpa scan
        $this->assertEquals(120, $created->target);
        $this->assertEquals('Siti Aminah', $created->pic);
        $this->assertEquals('Produksi shift 3 manual susulan', $created->remark);
        $this->assertEquals(2, $created->NG);
    }

    public function test_operator_can_generate_all_8_slots_for_shift_3_at_once(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-84796-17AA0',
            'shift' => 3,
            'temporal_cycle_time' => '30', // target 120
            'start_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->postJson(route('adminoperator.generate-shift-remarks'), [
            'dic_id' => $dic->id,
            'pic' => 'Budi Santoso',
            'remark' => 'Input Shift 3 Susulan',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'created_count' => 8,
        ]);

        $this->assertCount(8, HourlyRemark::where('dic_id', $dic->id)->get());

        $firstSlot = HourlyRemark::where('dic_id', $dic->id)->where('start_time', '23:30:00')->first();
        $this->assertNotNull($firstSlot);
        $this->assertEquals('00:30:00', $firstSlot->end_time);
        $this->assertEquals('Budi Santoso', $firstSlot->pic);
        $this->assertEquals(120, $firstSlot->target);
        $this->assertEquals(0, $firstSlot->actual);

        $lastSlot = HourlyRemark::where('dic_id', $dic->id)->where('start_time', '06:30:00')->first();
        $this->assertNotNull($lastSlot);
        $this->assertEquals('07:30:00', $lastSlot->end_time);
    }

    public function test_create_daily_item_from_admin_can_auto_generate_slots(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('dicadmin.store'), [
            'user_id' => $user->id,
            'item_code' => '401-84796-17AA0',
            'shift' => 3,
            'start_schedule_date' => Carbon::yesterday()->toDateString(),
            'end_schedule_date' => Carbon::today()->toDateString(),
            'start_time' => '23:30',
            'end_time' => '07:30',
            'quantity' => 800,
            'auto_generate_slots' => true,
            'pic' => 'Haerul Anwar',
        ]);

        $response->assertRedirect();

        $dic = DailyItemCode::where('item_code', '401-84796-17AA0')->where('shift', 3)->first();
        $this->assertNotNull($dic);

        // 8 hourly remarks harus otomatis terbuat
        $this->assertCount(8, HourlyRemark::where('dic_id', $dic->id)->get());
    }

    public function test_existing_activedic_store_from_operator_dashboard_remains_backward_compatible(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => 'LEGACY-PART',
            'shift' => 1,
            'temporal_cycle_time' => '60',
        ]);

        $response = $this->postJson(route('hourly-remarks.store'), [
            'start_time' => '07:30',
            'activedic' => json_encode(['id' => $dic->id, 'item_code' => 'LEGACY-PART']),
            'nik' => 'Operator Legacy',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $created = HourlyRemark::where('dic_id', $dic->id)->where('start_time', '07:30:00')->first();
        $this->assertNotNull($created);
        $this->assertEquals('Operator Legacy', $created->pic);
    }

    public function test_target_automatically_multiplies_by_pair_or_cavity(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        // Master item with pair
        \App\Models\MasterListItem::create([
            'item_code' => '401-7405A825',
            'item_name' => 'PROTECTOR LH (SEMI)',
            'pair' => '401-7405A826',
            'cavity' => 1,
        ]);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-7405A825',
            'shift' => 3,
            'temporal_cycle_time' => '50', // 3600 / 50 = 72. With pair -> 144!
            'start_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->post(route('hourly-remarks.store'), [
            'dic_id' => $dic->id,
            'start_time' => '06:30',
            'end_time' => '07:30',
            'actual_production' => 144,
            'pic' => 'U Fahmi Palahudin',
        ]);

        $response->assertRedirect();

        $slot = HourlyRemark::where('dic_id', $dic->id)->where('start_time', '06:30:00')->first();
        $this->assertNotNull($slot);
        // Target harus 144 (72 * 2 karena pair), bukan 72!
        $this->assertEquals(144, $slot->target);
        $this->assertEquals(1, $slot->is_achieve);
    }

    public function test_update_remark_can_update_target_and_is_achieve(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-TEST',
            'shift' => 3,
        ]);

        $slot = HourlyRemark::create([
            'dic_id' => $dic->id,
            'start_time' => '06:30:00',
            'end_time' => '07:30:00',
            'target' => 72,
            'actual_production' => 144,
            'pic' => 'Operator A',
        ]);

        $response = $this->postJson("/hourly-remarks/{$slot->id}/update-remark", [
            'target' => 144,
            'actual_production' => 144,
            'remark' => 'Fixed target to 144',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $slot->refresh();
        $this->assertEquals(144, $slot->target);
        $this->assertEquals('Fixed target to 144', $slot->remark);
        $this->assertEquals(1, $slot->is_achieve);
    }

    public function test_admin_operator_can_create_daily_item_with_cycle_time_and_cavity(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('dicadmin.store'), [
            'user_id' => $user->id,
            'item_code' => '401-7405A825',
            'shift' => 3,
            'start_schedule_date' => Carbon::today()->toDateString(),
            'end_schedule_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '23:30',
            'end_time' => '07:30',
            'quantity' => 1000,
            'temporal_cycle_time' => 50,
            'temporal_cavity' => 2,
            'auto_generate_slots' => 1,
            'pic' => 'U Fahmi Palahudin',
        ]);

        $response->assertRedirect();

        $dic = DailyItemCode::where('item_code', '401-7405A825')->where('shift', 3)->first();
        $this->assertNotNull($dic);
        $this->assertEquals(50, $dic->temporal_cycle_time);
        $this->assertEquals(2, $dic->temporal_cavity);

        // 8 slot jam harus otomatis terbuat dengan target = 144 (3600 / 50 * 2)
        $remarks = HourlyRemark::where('dic_id', $dic->id)->get();
        $this->assertCount(8, $remarks);
        foreach ($remarks as $remark) {
            $this->assertEquals(144, $remark->target);
        }
    }

    public function test_admin_operator_can_update_cycle_time_and_cavity_and_recalculate_targets(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => '0850d@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-TEST-ITEM',
            'shift' => 1,
            'temporal_cycle_time' => 60,
            'temporal_cavity' => 1,
        ]);

        $remark1 = HourlyRemark::create([
            'dic_id' => $dic->id,
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'target' => 60, // 3600 / 60 * 1
            'actual_production' => 120,
            'pic' => 'Operator A',
        ]);

        // Update cycle time jadi 50 dan cavity jadi 2 -> Target harus jadi 144 (3600 / 50 * 2)
        $response = $this->put(route('daily-item-codes.updateCycleTimeCavity', $dic->id), [
            'temporal_cycle_time' => 50,
            'temporal_cavity' => 2,
        ]);

        $response->assertRedirect();

        $dic->refresh();
        $this->assertEquals(50, $dic->temporal_cycle_time);
        $this->assertEquals(2, $dic->temporal_cavity);

        $remark1->refresh();
        $this->assertEquals(144, $remark1->target);
    }

    public function test_updating_cavity_to_one_on_paired_item_produces_single_cavity_target(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Mesin 0850D',
            'email' => 'mesin_paired@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id' => $user->id,
            'item_code' => '401-62040-C000P',
            'shift' => 1,
            'temporal_cycle_time' => 95,
            'temporal_cavity' => 2,
        ]);

        $remark = HourlyRemark::create([
            'dic_id' => $dic->id,
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'target' => 74,
            'actual_production' => 37,
            'pic' => 'Egi Gustian',
        ]);

        // When user changes cavity to 1:
        $response = $this->put(route('daily-item-codes.updateCycleTimeCavity', $dic->id), [
            'temporal_cycle_time' => 95,
            'temporal_cavity' => 1,
        ]);

        $response->assertRedirect();

        $dic->refresh();
        $this->assertEquals(1, $dic->temporal_cavity);
        $this->assertEquals(1, $dic->effective_cavity);

        $remark->refresh();
        $this->assertEquals(37, $remark->target);
        $this->assertEquals(1, $remark->is_achieve);
    }
}
