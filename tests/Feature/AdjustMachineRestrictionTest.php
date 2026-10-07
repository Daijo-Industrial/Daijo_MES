<?php

namespace Tests\Feature;

use App\Models\AdjustMachineLog;
use App\Models\DailyItemCode;
use App\Models\MachineJob;
use App\Models\OperatorUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdjustMachineRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

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
        });

        Schema::create('repair_machine_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('pic');
            $table->string('item_code')->nullable();
            $table->string('problem')->nullable();
            $table->dateTime('finish_repair')->nullable();
            $table->text('remark')->nullable();
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
        $role = Role::create(['name' => 'OPERATOR']);

        // Karawang adjusters
        OperatorUser::create(['name' => 'Haerul Anwar', 'password' => '123', 'position' => 'Adjuster']);
        OperatorUser::create(['name' => 'Rudi Siswanto', 'password' => '123', 'position' => 'Adjuster']);
        OperatorUser::create(['name' => 'Rodi Khayrudin', 'password' => '123', 'position' => 'Adjuster']);
        OperatorUser::create(['name' => 'Agung Setyawan', 'password' => '123', 'position' => 'Adjuster']);

        // KBN adjusters
        OperatorUser::create(['name' => 'Budi Santoso', 'password' => '123', 'position' => 'Adjuster']);
        OperatorUser::create(['name' => 'Joko Widodo', 'password' => '123', 'position' => 'Adjuster']);

        // Maintenance operators
        OperatorUser::create(['name' => 'Mamat Maintenance', 'password' => '123', 'position' => 'Maintenance']);
        OperatorUser::create(['name' => 'Ujang Maintenance', 'password' => '123', 'position' => 'Maintenance']);
    }

    public function test_karawang_machine_rejects_kbn_adjuster_on_start()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => 'K01',
            'email'    => 'k01@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name'  => 'Budi Santoso',
            'item_code' => 'ITEM-01',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('Mesin Karawang', $response->json('error'));
    }

    public function test_karawang_machine_accepts_karawang_adjuster_on_start()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => 'K02',
            'email'    => 'k02@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name'  => 'Haerul Anwar',
            'item_code' => 'ITEM-01',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('adjust_machine_logs', [
            'user_id'   => $machine->id,
            'pic'       => 'Haerul Anwar',
            'item_code' => 'ITEM-01',
        ]);
    }

    public function test_kbn_machine_rejects_karawang_adjuster_on_start()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => '0350F',
            'email'    => '0350f@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name'  => 'Rodi Khayrudin',
            'item_code' => 'ITEM-01',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('tidak diizinkan melakukan adjust pada mesin KBN', $response->json('error'));
    }

    public function test_kbn_machine_accepts_kbn_adjuster_on_start()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => '0650F',
            'email'    => '0650f@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        MachineJob::create(['user_id' => $machine->id, 'item_code' => 'ITEM-01']);

        $response = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name'  => 'Budi Santoso',
            'item_code' => 'ITEM-01',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('adjust_machine_logs', [
            'user_id'   => $machine->id,
            'pic'       => 'Budi Santoso',
            'item_code' => 'ITEM-01',
        ]);
    }

    public function test_adjusters_list_filtering_for_karawang_and_kbn()
    {
        $karawangAdjusters = [
            'Haerul Anwar',
            'Rudi Siswanto',
            'Rodi Khayrudin',
            'Agung Setyawan',
        ];

        // 1. Karawang Machine (starts with 'K')
        $krwMachineName = 'K12';
        $isKrw = str_starts_with(strtoupper(trim($krwMachineName)), 'K');
        $this->assertTrue($isKrw);

        $krwAdjusters = OperatorUser::whereIn('name', $karawangAdjusters)
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->toArray();

        $this->assertEquals([
            'Agung Setyawan',
            'Haerul Anwar',
            'Rodi Khayrudin',
            'Rudi Siswanto',
        ], $krwAdjusters);

        // 2. KBN Machine (does NOT start with 'K')
        $kbnMachineName = '0350F';
        $isKbn = str_starts_with(strtoupper(trim($kbnMachineName)), 'K');
        $this->assertFalse($isKbn);

        $kbnAdjusters = OperatorUser::where('position', 'Adjuster')
            ->whereNotIn('name', $karawangAdjusters)
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->toArray();

        $this->assertEquals([
            'Budi Santoso',
            'Joko Widodo',
        ], $kbnAdjusters);
    }

    public function test_start_and_end_adjust_machine_does_not_reset_active_job()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => '0350F',
            'email'    => '0350f_job@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        $machineJob = MachineJob::create([
            'user_id'   => $machine->id,
            'item_code' => 'ACTIVE-ITEM-01',
            'shift'     => 1,
            'dic_id'    => 99,
        ]);

        // 1. Start Adjust Machine
        $responseStart = $this->actingAs($machine)->postJson(route('adjust.machine.start'), [
            'pic_name'  => 'Budi Santoso',
            'item_code' => 'ACTIVE-ITEM-01',
        ]);

        $responseStart->assertStatus(200);

        // Assert MachineJob is NOT reset on start
        $machineJob->refresh();
        $this->assertEquals('ACTIVE-ITEM-01', $machineJob->item_code);
        $this->assertEquals(1, $machineJob->shift);
        $this->assertEquals(99, $machineJob->dic_id);

        // 2. End Adjust Machine
        $responseEnd = $this->actingAs($machine)->postJson(route('adjust.machine.end'), [
            'remarks' => 'Selesai perbaikan/adjusting setting suhu',
        ]);

        $responseEnd->assertStatus(200);
        $responseEnd->assertJson(['message' => 'Adjust Machine completed']);

        // Assert MachineJob is STILL NOT reset on end
        $machineJob->refresh();
        $this->assertEquals('ACTIVE-ITEM-01', $machineJob->item_code);
        $this->assertEquals(1, $machineJob->shift);
        $this->assertEquals(99, $machineJob->dic_id);
    }

    public function test_start_repair_machine_with_maintenance_operator_and_item_code()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => '0350F',
            'email'    => '0350f_repair@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        $response = $this->actingAs($machine)->postJson(route('repair.machine.start'), [
            'pic_name'  => 'Mamat Maintenance',
            'item_code' => 'REPAIR-ITEM-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Repair Machine started']);

        $this->assertDatabaseHas('repair_machine_logs', [
            'user_id'   => $machine->id,
            'pic'       => 'Mamat Maintenance',
            'item_code' => 'REPAIR-ITEM-01',
        ]);
    }

    public function test_end_repair_machine_does_not_reset_active_job()
    {
        $role = Role::where('name', 'OPERATOR')->first();
        $machine = User::create([
            'name'     => '0350F',
            'email'    => '0350f_repair_end@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        $machineJob = MachineJob::create([
            'user_id'       => $machine->id,
            'item_code'     => 'ACTIVE-ITEM-REPAIR-01',
            'employee_name' => 'Operator Tetap',
            'shift'         => 2,
            'dic_id'        => 88,
        ]);

        // Start Repair
        $this->actingAs($machine)->postJson(route('repair.machine.start'), [
            'pic_name'  => 'Mamat Maintenance',
            'item_code' => 'ACTIVE-ITEM-REPAIR-01',
        ]);

        // End Repair
        $responseEnd = $this->actingAs($machine)->postJson(route('repair.machine.end'), [
            'problem' => 'Motor servo macet',
            'remarks' => 'Sudah diganti bearing dan pelumasan',
        ]);

        $responseEnd->assertStatus(200);
        $responseEnd->assertJson(['message' => 'Repair Machine completed']);

        // Assert MachineJob is STILL NOT reset on end repair
        $machineJob->refresh();
        $this->assertEquals('ACTIVE-ITEM-REPAIR-01', $machineJob->item_code);
        $this->assertEquals(2, $machineJob->shift);
        $this->assertEquals(88, $machineJob->dic_id);
    }
}

