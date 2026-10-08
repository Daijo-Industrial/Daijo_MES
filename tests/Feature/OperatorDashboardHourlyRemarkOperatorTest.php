<?php

namespace Tests\Feature;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MachineJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperatorDashboardHourlyRemarkOperatorTest extends TestCase
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
            $table->integer('target')->default(0);
            $table->date('schedule_date')->nullable();
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
            $table->string('pic')->nullable();
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
            $table->timestamps();
        });
    }

    public function test_sync_additional_operators_via_update_employee_name_updates_machine_job_and_backfills_hourly_remarks(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name'     => 'Mesin 01',
            'email'    => 'mesin01@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id'       => $user->id,
            'item_code'     => 'PART-TEST-01',
            'shift'         => 1,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        $machineJob = MachineJob::create([
            'user_id'       => $user->id,
            'item_code'     => 'PART-TEST-01',
            'shift'         => 1,
            'dic_id'        => $dic->id,
            'employee_name' => 'Budi',
        ]);

        // Slot hourly remark yang awalnya dibuat tanpa operator 2 & 3
        $hr = HourlyRemark::create([
            'dic_id'     => $dic->id,
            'start_time' => '07:30:00',
            'end_time'   => '08:30:00',
            'target'     => 100,
            'actual'     => 95,
            'pic'        => 'Budi',
            'pic_2'      => null,
            'pic_3'      => null,
        ]);

        // Kirim updateEmployeeName dengan payload array operators dari syncOperatorsToDB
        $response = $this->postJson(route('updateEmployeeName'), [
            'operators' => ['Budi', 'Siti Aminah', 'Ahmad Dani'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'employee_name' => 'Budi, Siti Aminah, Ahmad Dani',
        ]);

        // MachineJob harus terupdate dengan operator 1, 2, dan 3
        $machineJob->refresh();
        $this->assertEquals('Budi, Siti Aminah, Ahmad Dani', $machineJob->employee_name);

        // HourlyRemark pada DIC aktif harus otomatis ter-backfill pic_2 dan pic_3
        $hr->refresh();
        $this->assertEquals('Budi', $hr->pic);
        $this->assertEquals('Siti Aminah', $hr->pic_2);
        $this->assertEquals('Ahmad Dani', $hr->pic_3);
    }

    public function test_store_hourly_remark_saves_pic_2_and_pic_3_from_request(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name'     => 'Mesin 02',
            'email'    => 'mesin02@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id'              => $user->id,
            'item_code'            => 'PART-TEST-02',
            'shift'                => 1,
            'schedule_date'        => Carbon::today()->toDateString(),
            'temporal_cycle_time'  => '36', // 3600 / 36 = target 100
        ]);

        MachineJob::create([
            'user_id'       => $user->id,
            'item_code'     => 'PART-TEST-02',
            'shift'         => 1,
            'dic_id'        => $dic->id,
            'employee_name' => 'Eko',
        ]);

        $response = $this->postJson(route('hourly-remarks.store'), [
            'start_time' => '08:30',
            'activedic'  => json_encode(['id' => $dic->id, 'item_code' => 'PART-TEST-02']),
            'nik'        => 'Eko',
            'pic_2'      => 'Rian Pratama',
            'pic_3'      => 'Dewi Lestari',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $created = HourlyRemark::where('dic_id', $dic->id)
            ->where('start_time', '08:30:00')
            ->first();

        $this->assertNotNull($created);
        $this->assertEquals('08:30:00', $created->start_time);
        $this->assertEquals('09:30:00', $created->end_time);
        $this->assertEquals('Eko', $created->pic);
        $this->assertEquals('Rian Pratama', $created->pic_2);
        $this->assertEquals('Dewi Lestari', $created->pic_3);
        $this->assertEquals(100, $created->target);
    }

    public function test_store_hourly_remark_falls_back_to_machine_job_assigned_operators_when_missing_in_request(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name'     => 'Mesin 03',
            'email'    => 'mesin03@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        $dic = DailyItemCode::create([
            'user_id'              => $user->id,
            'item_code'            => 'PART-TEST-03',
            'shift'                => 1,
            'schedule_date'        => Carbon::today()->toDateString(),
            'temporal_cycle_time'  => '60', // target 60
        ]);

        // Operator 2 dan 3 sudah tersimpan di MachineJob
        MachineJob::create([
            'user_id'       => $user->id,
            'item_code'     => 'PART-TEST-03',
            'shift'         => 1,
            'dic_id'        => $dic->id,
            'employee_name' => 'Fajar, Gilang Ramadhan, Hesti Purwanti',
        ]);

        // Request add hourly remarks tanpa mengirim field pic_2 dan pic_3
        $response = $this->postJson(route('hourly-remarks.store'), [
            'start_time' => '10:30',
            'activedic'  => json_encode(['id' => $dic->id, 'item_code' => 'PART-TEST-03']),
            'nik'        => 'Fajar',
        ]);

        $response->assertStatus(200);

        $created = HourlyRemark::where('dic_id', $dic->id)
            ->where('start_time', '10:30:00')
            ->first();

        $this->assertNotNull($created);
        $this->assertEquals('Fajar', $created->pic);
        $this->assertEquals('Gilang Ramadhan', $created->pic_2);
        $this->assertEquals('Hesti Purwanti', $created->pic_3);
    }
}
