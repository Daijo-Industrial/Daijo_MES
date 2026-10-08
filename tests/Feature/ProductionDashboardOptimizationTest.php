<?php

namespace Tests\Feature;

use App\Livewire\ProductionDashboard;
use App\Models\AdjustMachineLog;
use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\MouldChangeLog;
use App\Models\ProductionNgDetail;
use App\Models\ProductionNgType;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductionDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionDashboardOptimizationTest extends TestCase
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

        Schema::create('master_list_items', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->decimal('cycle_time', 8, 2)->nullable();
            $table->decimal('setup_time_minute', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('daily_item_codes', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('item_code');
            $table->date('start_date');
            $table->date('schedule_date')->nullable();
            $table->integer('shift')->default(1);
            $table->decimal('resin_usage', 10, 2)->nullable();
            $table->decimal('temporal_cycle_time', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hourly_remarks', function ($table) {
            $table->id();
            $table->foreignId('dic_id');
            $table->string('start_time')->default('08:00');
            $table->integer('target')->default(100);
            $table->integer('actual_production')->default(90);
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_ng_types', function ($table) {
            $table->id();
            $table->string('ng_type');
            $table->timestamps();
        });

        Schema::create('production_ng_details', function ($table) {
            $table->id();
            $table->foreignId('hourly_remark_id');
            $table->foreignId('ng_type_id');
            $table->integer('ng_quantity')->default(0);
            $table->string('ng_remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('adjust_machine_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('item_code')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('pic')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mould_change_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('item_code')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('pic')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repair_machine_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('item_code')->nullable();
            $table->string('problem')->nullable();
            $table->dateTime('finish_repair')->nullable();
            $table->string('pic')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_production_dashboard_single_query_pipeline()
    {
        $role = Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $machine1 = User::create(['name' => 'K0450A', 'email' => 'k450@example.com', 'password' => 'secret']);
        $machine2 = User::create(['name' => 'K0650A', 'email' => 'k650@example.com', 'password' => 'secret']);

        MasterListItem::create([
            'item_code'         => 'PART-001',
            'cycle_time'        => 30.0,
            'setup_time_minute' => 25.0,
        ]);

        // Machine 1 running on 2026-08-15 Shift 1
        $dic1 = DailyItemCode::create([
            'user_id'    => $machine1->id,
            'item_code'  => 'PART-001',
            'start_date' => '2026-08-15',
            'shift'      => 1,
        ]);
        $hr1 = HourlyRemark::create([
            'dic_id'            => $dic1->id,
            'start_time'        => '08:00',
            'target'            => 120,
            'actual_production' => 100,
            'remark'            => 'Minor nozzle clog',
        ]);
        $ngType = ProductionNgType::create(['ng_type' => 'SCRATCH']);
        ProductionNgDetail::create([
            'hourly_remark_id' => $hr1->id,
            'ng_type_id'       => $ngType->id,
            'ng_quantity'      => 10,
        ]);

        // Machine 2 running on 2026-08-15 Shift 1 (NO adjust log on Machine 2)
        $dic2 = DailyItemCode::create([
            'user_id'    => $machine2->id,
            'item_code'  => 'PART-001',
            'start_date' => '2026-08-15',
            'shift'      => 1,
        ]);
        $hr2 = HourlyRemark::create([
            'dic_id'            => $dic2->id,
            'start_time'        => '09:00',
            'target'            => 100,
            'actual_production' => 90,
            'remark'            => 'Normal run',
        ]);
        ProductionNgDetail::create([
            'hourly_remark_id' => $hr2->id,
            'ng_type_id'       => $ngType->id,
            'ng_quantity'      => 15,
        ]);

        // Adjuster logged on Machine 1 only for Shift 1 (08:15 WIB = 01:15 UTC)
        $adjLog = AdjustMachineLog::create([
            'user_id'    => $machine1->id,
            'item_code'  => 'PART-001',
            'pic'        => 'Budi (Adjuster)',
            'end_time'   => '2026-08-15 01:35:00',
        ]);
        $adjLog->created_at = '2026-08-15 01:15:00';
        $adjLog->save();

        $service = app(ProductionDashboardService::class);
        $start = Carbon::create(2026, 8, 1)->startOfMonth();
        $end = Carbon::create(2026, 8, 1)->endOfMonth();

        $allData = $service->getAllDashboardData($start, $end, null, null, 'karawang');

        // Verify summary
        $this->assertEquals(220, $allData['summary']['total_target']);
        $this->assertEquals(190, $allData['summary']['total_actual']);
        $this->assertEquals(25, $allData['summary']['total_ng']);

        // Verify Shift 1 includes Budi as the Adjuster
        $shift1 = $allData['shift_personnel_analysis']['shifts'][1];
        $this->assertContains('Budi (Adjuster)', $shift1['adjusters']);
        $this->assertEquals(25, $shift1['total_ng']); // 10 from K0450A + 15 from K0650A

        // Verify Adjuster NG Trend attributes all 25 NG to Budi
        $adjusterTrend = $allData['adjuster_ng_trend'];
        $this->assertTrue($adjusterTrend['has_data']);
        $this->assertEquals('Budi (Adjuster)', $adjusterTrend['adjuster_summaries'][0]['name']);
        $this->assertEquals(25, $adjusterTrend['adjuster_summaries'][0]['total_ng']);

        // Test Livewire component integration
        $this->actingAs($user);
        Livewire::test(ProductionDashboard::class)
            ->set('plant', 'karawang')
            ->set('year', 2026)
            ->set('month', 8)
            ->assertSet('summary.total_ng', 25)
            ->assertSet('summary.total_actual', 190);

        // Test filtering by Machine 1 (K0450A) which had the adjust log
        $machine1Data = $service->getAllDashboardData($start, $end, null, (string)$machine1->id, 'karawang');
        $this->assertEquals(10, $machine1Data['summary']['total_ng']);
        $this->assertEquals(100, $machine1Data['summary']['total_actual']);
        $this->assertContains('Budi (Adjuster)', $machine1Data['shift_personnel_analysis']['shifts'][1]['adjusters']);

        // Test filtering by Machine 2 (K0650A) which had no direct adjust log
        $machine2Data = $service->getAllDashboardData($start, $end, null, (string)$machine2->id, 'karawang');
        $this->assertEquals(15, $machine2Data['summary']['total_ng']);
        $this->assertEquals(90, $machine2Data['summary']['total_actual']);
        $this->assertEmpty($machine2Data['shift_personnel_analysis']['shifts'][1]['adjusters']);
    }

    public function test_production_dashboard_daily_view()
    {
        $user = User::create([
            'name'     => 'Admin User 2',
            'email'    => 'admin2@example.com',
            'password' => bcrypt('password'),
        ]);

        $machine = User::create(['name' => 'K0450A', 'email' => 'k450_2@example.com', 'password' => 'secret']);
        $dic = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'PART-DAILY',
            'start_date' => '2026-08-15',
            'shift'      => 1,
        ]);
        HourlyRemark::create([
            'dic_id'            => $dic->id,
            'start_time'        => '08:00',
            'target'            => 100,
            'actual_production' => 95,
        ]);

        $service = app(ProductionDashboardService::class);
        $date = Carbon::parse('2026-08-15');
        $dailyData = $service->getAllDashboardData($date->copy()->startOfDay(), $date->copy()->endOfDay());

        $this->assertCount(24, $dailyData['chart_data']);
        $this->assertEquals(100, $dailyData['summary']['total_target']);
        $this->assertEquals(95, $dailyData['summary']['total_actual']);
    }

    public function test_adjuster_and_mould_change_utc_to_wib_shift_assignment()
    {
        $machine = User::create(['name' => 'K0450A', 'email' => 'k0450a@example.com', 'password' => 'secret']);
        $otherMachine = User::create(['name' => 'K0650A', 'email' => 'k0650a@example.com', 'password' => 'secret']);

        MasterListItem::create([
            'item_code'         => 'K-847F1-I6RA0',
            'cycle_time'        => 30.0,
            'setup_time_minute' => 20.0,
        ]);

        // Adjust log 1: Rudi Siswanto at 10:32 WIB (03:32 UTC) -> Shift 1 (07:30 - 15:30)
        $adj1 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'K-847F1-I6RA0',
            'pic'       => 'Rudi Siswanto',
            'end_time'  => '2026-09-03 03:42:00',
        ]);
        $adj1->created_at = '2026-09-03 03:32:00';
        $adj1->save();

        // Adjust log 2: Haerul Anwar at 19:03 WIB (12:03 UTC) -> Shift 2 (15:30 - 23:30)
        $adj2 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'K-84715-I7AA0NNB',
            'pic'       => 'Haerul Anwar',
            'end_time'  => '2026-09-03 12:10:00',
        ]);
        $adj2->created_at = '2026-09-03 12:03:00';
        $adj2->save();

        // Adjust log 3: Rodi Khayrudin at 23:40 WIB (16:40 UTC) -> Shift 3 (23:30 - 07:30)
        $adj3 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'K-84780-I7000NNB-PE',
            'pic'       => 'Rodi Khayrudin',
            'end_time'  => '2026-09-03 16:52:00',
        ]);
        $adj3->created_at = '2026-09-03 16:40:00';
        $adj3->save();

        // Mould change log 1: Wahyu Eko Prawito at 16:35 WIB (09:35 UTC) -> Shift 2
        $mould1 = MouldChangeLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'K-847F1-I6RA0',
            'pic'       => 'Wahyu Eko Prawito',
            'end_time'  => '2026-09-03 09:50:00',
        ]);
        $mould1->created_at = '2026-09-03 09:35:00';
        $mould1->save();

        // Mould change log 2: Wahyu Eko Prawito at 23:15 WIB (16:15 UTC) -> Shift 2
        $mould2 = MouldChangeLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'K-84715-I7AA0NNB',
            'pic'       => 'Wahyu Eko Prawito',
            'end_time'  => '2026-09-03 16:30:00',
        ]);
        $mould2->created_at = '2026-09-03 16:15:00';
        $mould2->save();

        // An adjust log on ANOTHER machine: Other Person at 10:00 WIB (03:00 UTC) on K0650A
        $adjOther = AdjustMachineLog::create([
            'user_id'   => $otherMachine->id,
            'item_code' => 'OTHER-ITEM',
            'pic'       => 'Other Person',
            'end_time'  => '2026-09-03 03:30:00',
        ]);
        $adjOther->created_at = '2026-09-03 03:00:00';
        $adjOther->save();

        $service = app(ProductionDashboardService::class);
        $selectedDate = Carbon::parse('2026-09-03');

        // Query filtered strictly to Machine K0450A
        $data = $service->getAllDashboardData($selectedDate, $selectedDate, null, (string)$machine->id, 'karawang');
        $shifts = $data['shift_personnel_analysis']['shifts'];

        // Shift 1: Only Rudi Siswanto (10:32 WIB)
        $this->assertEquals(['Rudi Siswanto'], $shifts[1]['adjusters']);
        $this->assertEmpty($shifts[1]['mould_changers']);
        $this->assertEquals(1, $shifts[1]['adjust_count']);
        $this->assertEquals(0, $shifts[1]['mould_change_count']);

        // Shift 2: Haerul Anwar (19:03 WIB) and Wahyu Eko Prawito (16:35 & 23:15 WIB)
        $this->assertEquals(['Haerul Anwar'], $shifts[2]['adjusters']);
        $this->assertEquals(['Wahyu Eko Prawito'], $shifts[2]['mould_changers']);
        $this->assertEquals(1, $shifts[2]['adjust_count']);
        $this->assertEquals(2, $shifts[2]['mould_change_count']);

        // Shift 3: Rodi Khayrudin (23:40 WIB)
        $this->assertEquals(['Rodi Khayrudin'], $shifts[3]['adjusters']);
        $this->assertEmpty($shifts[3]['mould_changers']);
        $this->assertEquals(1, $shifts[3]['adjust_count']);
        $this->assertEquals(0, $shifts[3]['mould_change_count']);

        // Assert 'Other Person' from K0650A is NOT present when filtered to K0450A
        $this->assertNotContains('Other Person', $shifts[1]['adjusters']);
    }

    public function test_half_day_shift_schedule_categorization_and_adjuster_ng_trend(): void
    {
        $service = app(ProductionDashboardService::class);

        // 1. Test getProductionDateAndShift directly
        $timeS1 = Carbon::parse('2026-09-12 10:00:00', 'Asia/Jakarta');
        $timeS2 = Carbon::parse('2026-09-12 14:00:00', 'Asia/Jakarta');
        $timeS3 = Carbon::parse('2026-09-12 18:00:00', 'Asia/Jakarta');

        // Under normal schedule:
        // 10:00 -> Shift 1
        // 14:00 -> Shift 1
        // 18:00 -> Shift 2
        $this->assertEquals(1, ProductionDashboardService::getProductionDateAndShift($timeS1, false)['shift']);
        $this->assertEquals(1, ProductionDashboardService::getProductionDateAndShift($timeS2, false)['shift']);
        $this->assertEquals(2, ProductionDashboardService::getProductionDateAndShift($timeS3, false)['shift']);

        // Under half-day schedule:
        // Shift 1: 07:30 - 12:30 -> 10:00 is Shift 1
        // Shift 2: 12:30 - 17:30 -> 14:00 is Shift 2
        // Shift 3: 17:30 - 22:30 -> 18:00 is Shift 3
        $this->assertEquals(1, ProductionDashboardService::getProductionDateAndShift($timeS1, true)['shift']);
        $this->assertEquals(2, ProductionDashboardService::getProductionDateAndShift($timeS2, true)['shift']);
        $this->assertEquals(3, ProductionDashboardService::getProductionDateAndShift($timeS3, true)['shift']);

        // 2. Integration test: DICs + Adjust Logs on half-day
        $machine = User::firstOrCreate(
            ['name' => 'K0450A'],
            ['email' => 'k0450a_half@example.com', 'password' => 'secret']
        );
        $date = Carbon::parse('2026-09-12');

        // Shift 1 DIC (10 NG)
        $dic1 = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'ITEM-HALF-DAY',
            'start_date' => '2026-09-12',
            'shift'      => 1,
            'target'     => 1000,
        ]);
        $hr1 = HourlyRemark::create([
            'dic_id'            => $dic1->id,
            'target'            => 1000,
            'actual_production' => 900,
        ]);
        $ngType = ProductionNgType::firstOrCreate(['ng_type' => 'SCRATCH']);
        ProductionNgDetail::create([
            'hourly_remark_id' => $hr1->id,
            'ng_type_id'       => $ngType->id,
            'ng_quantity'      => 10,
        ]);

        // Shift 2 DIC (20 NG)
        $dic2 = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'ITEM-HALF-DAY',
            'start_date' => '2026-09-12',
            'shift'      => 2,
            'target'     => 1000,
        ]);
        $hr2 = HourlyRemark::create([
            'dic_id'            => $dic2->id,
            'target'            => 1000,
            'actual_production' => 900,
        ]);
        ProductionNgDetail::create([
            'hourly_remark_id' => $hr2->id,
            'ng_type_id'       => $ngType->id,
            'ng_quantity'      => 20,
        ]);

        // Shift 3 DIC (30 NG)
        $dic3 = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'ITEM-HALF-DAY',
            'start_date' => '2026-09-12',
            'shift'      => 3,
            'target'     => 1000,
        ]);
        $hr3 = HourlyRemark::create([
            'dic_id'            => $dic3->id,
            'target'            => 1000,
            'actual_production' => 900,
        ]);
        ProductionNgDetail::create([
            'hourly_remark_id' => $hr3->id,
            'ng_type_id'       => $ngType->id,
            'ng_quantity'      => 30,
        ]);

        // Adjust log at 10:00 WIB (03:00 UTC) -> S1: Adjuster Alpha
        $adj1 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-HALF-DAY',
            'pic'       => 'Adjuster Alpha',
        ]);
        $adj1->created_at = '2026-09-12 03:00:00';
        $adj1->save();

        // Adjust log at 14:00 WIB (07:00 UTC) -> S2: Adjuster Beta
        $adj2 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-HALF-DAY',
            'pic'       => 'Adjuster Beta',
        ]);
        $adj2->created_at = '2026-09-12 07:00:00';
        $adj2->save();

        // Adjust log at 18:00 WIB (11:00 UTC) -> S3: Adjuster Gamma
        $adj3 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-HALF-DAY',
            'pic'       => 'Adjuster Gamma',
        ]);
        $adj3->created_at = '2026-09-12 11:00:00';
        $adj3->save();

        // Test with $isHalfDay = true
        $dataHalfDay = $service->getAllDashboardData($date, $date, null, (string)$machine->id, 'karawang', true);

        $shiftHalfDay = $dataHalfDay['shift_personnel_analysis']['shifts'];
        $this->assertEquals(['Adjuster Alpha'], $shiftHalfDay[1]['adjusters']);
        $this->assertEquals('07:30 - 12:30', $shiftHalfDay[1]['time_range']);

        $this->assertEquals(['Adjuster Beta'], $shiftHalfDay[2]['adjusters']);
        $this->assertEquals('12:30 - 17:30', $shiftHalfDay[2]['time_range']);

        $this->assertEquals(['Adjuster Gamma'], $shiftHalfDay[3]['adjusters']);
        $this->assertEquals('17:30 - 22:30', $shiftHalfDay[3]['time_range']);

        // Check Adjuster NG Trend: all 3 adjusters get their shift's NG (10, 20, 30)
        $adjSummaries = collect($dataHalfDay['adjuster_ng_trend']['adjuster_summaries'])->keyBy('name');
        $this->assertEquals(10, $adjSummaries['Adjuster Alpha']['total_ng']);
        $this->assertEquals(20, $adjSummaries['Adjuster Beta']['total_ng']);
        $this->assertEquals(30, $adjSummaries['Adjuster Gamma']['total_ng']);

        // Add second adjuster to Shift 1 with 35 NG to test exact division & balance (35 / 2 = 18 + 17 = 35)
        $hr1->ngDetails()->first()->update(['ng_quantity' => 35]);
        $adj1b = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-HALF-DAY',
            'pic'       => 'Adjuster Alpha 2',
        ]);
        $adj1b->created_at = '2026-09-12 04:00:00';
        $adj1b->save();

        $dataBalanced = $service->getAllDashboardData($date, $date, null, (string)$machine->id, 'karawang', true);
        $balancedSummaries = collect($dataBalanced['adjuster_ng_trend']['adjuster_summaries'])->keyBy('name');

        $alpha1Ng = $balancedSummaries['Adjuster Alpha']['total_ng'];
        $alpha2Ng = $balancedSummaries['Adjuster Alpha 2']['total_ng'];
        $this->assertEquals(35, $alpha1Ng + $alpha2Ng);
        $this->assertTrue(($alpha1Ng === 18 && $alpha2Ng === 17) || ($alpha1Ng === 17 && $alpha2Ng === 18));

        // Test Livewire toggle (defaults to false, manual toggle)
        Livewire::test(ProductionDashboard::class)
            ->set('viewType', 'daily')
            ->set('selectedDate', '2026-09-12') // Saturday
            ->set('machineUserId', (string)$machine->id)
            ->assertSet('isHalfDay', false) // Not auto-enabled! Can be full day
            ->set('isHalfDay', true) // Manual toggle
            ->assertSet('isHalfDay', true)
            ->assertSee('Setengah Hari');
    }

    public function test_weekly_view_manual_weekend_half_day_schedule_and_livewire()
    {
        $service = app(ProductionDashboardService::class);
        $machine = User::create(['name' => 'K0450A', 'email' => 'k450_weekly@example.com', 'password' => 'secret']);

        MasterListItem::create([
            'item_code'         => 'ITEM-WEEKLY',
            'cycle_time'        => 30.0,
            'setup_time_minute' => 20.0,
        ]);

        // 1. Wednesday 2026-09-09 (Weekday): Log at 14:00 WIB (07:00 UTC)
        // Weekday Shift 1 is 07:30 - 15:30 -> Always Shift 1!
        $adjWed = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-WEEKLY',
            'pic'       => 'Adjuster Weekday',
        ]);
        $adjWed->created_at = '2026-09-09 07:00:00';
        $adjWed->save();

        // 2. Saturday 2026-09-12 (Weekend): Log at 14:00 WIB (07:00 UTC)
        // When Saturday half-day is enabled: Shift 1 is 07:30 - 12:30, Shift 2 is 12:30 - 17:30 -> Shift 2!
        // When Saturday is full day: Shift 1 is 07:30 - 15:30 -> Shift 1!
        $adjSat2 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-WEEKLY',
            'pic'       => 'Adjuster Weekend S2',
        ]);
        $adjSat2->created_at = '2026-09-12 07:00:00';
        $adjSat2->save();

        // 3. Saturday 2026-09-12 (Weekend): Log at 18:00 WIB (11:00 UTC)
        // When Saturday half-day is enabled: Shift 3 is 17:30 - 22:30 -> Shift 3!
        $adjSat3 = AdjustMachineLog::create([
            'user_id'   => $machine->id,
            'item_code' => 'ITEM-WEEKLY',
            'pic'       => 'Adjuster Weekend S3',
        ]);
        $adjSat3->created_at = '2026-09-12 11:00:00';
        $adjSat3->save();

        $startOfWeek = Carbon::parse('2026-09-07')->startOfDay();
        $endOfWeek = Carbon::parse('2026-09-13')->endOfDay();

        // Case A: Full-day Saturday (default: saturday => false)
        $dataFullDay = $service->getAllDashboardData($startOfWeek, $endOfWeek, null, (string)$machine->id, 'karawang', [
            'saturday' => false,
            'sunday'   => false,
        ]);
        $shiftsFullDay = $dataFullDay['shift_personnel_analysis']['shifts'];
        // On normal schedule, 14:00 WIB falls in Shift 1 (07:30 - 15:30)
        $this->assertContains('Adjuster Weekend S2', $shiftsFullDay[1]['adjusters']);

        // Case B: Saturday marked half-day (saturday => true)
        $dataHalfDay = $service->getAllDashboardData($startOfWeek, $endOfWeek, null, (string)$machine->id, 'karawang', [
            'saturday' => true,
            'sunday'   => false,
        ]);
        $shiftsHalfDay = $dataHalfDay['shift_personnel_analysis']['shifts'];
        // On half-day schedule, 14:00 WIB falls in Shift 2 (12:30 - 17:30)
        $this->assertContains('Adjuster Weekend S2', $shiftsHalfDay[2]['adjusters']);
        // And 18:00 WIB falls in Shift 3 (17:30 - 22:30)
        $this->assertContains('Adjuster Weekend S3', $shiftsHalfDay[3]['adjusters']);
        // Wednesday remains Shift 1
        $this->assertContains('Adjuster Weekday', $shiftsHalfDay[1]['adjusters']);

        // Case C: Livewire component weekly toggle
        Livewire::test(ProductionDashboard::class)
            ->set('viewType', 'weekly')
            ->assertSet('isSaturdayHalfDay', false)
            ->assertSet('isSundayHalfDay', false)
            ->set('isSaturdayHalfDay', true)
            ->assertSet('isSaturdayHalfDay', true)
            ->assertSee('Sabtu')
            ->assertSee('Minggu');
    }

    public function test_ng_breakdown_contains_models_breakdown_and_remarks(): void
    {
        $role = Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin NG',
            'email'    => 'admin_ng@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);
        $machine = User::create(['name' => 'K0450A', 'email' => 'k450_ng@example.com', 'password' => 'secret']);

        MasterListItem::create([
            'item_code'  => 'PART-ALPHA',
            'item_name'  => 'BEZEL FRONT PANEL',
            'cycle_time' => 30,
        ]);

        MasterListItem::create([
            'item_code'  => 'PART-BETA',
            'item_name'  => 'COVER BOTTOM HOUSING',
            'cycle_time' => 45,
        ]);

        $ngTypeBlackdot = ProductionNgType::create(['ng_type' => 'BLACKDOT']);

        // Daily item code 1: PART-ALPHA
        $dic1 = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'PART-ALPHA',
            'start_date' => '2026-10-06',
            'shift'      => 1,
        ]);

        $hourly1 = HourlyRemark::create([
            'dic_id'            => $dic1->id,
            'start_time'        => '08:00',
            'target'            => 100,
            'actual_production' => 85,
            'remark'            => 'Bahan kotor',
        ]);

        ProductionNgDetail::create([
            'hourly_remark_id' => $hourly1->id,
            'ng_type_id'       => $ngTypeBlackdot->id,
            'ng_quantity'      => 15,
            'ng_remarks'       => 'Bintik hitam di sisi kanan',
        ]);

        // Daily item code 2: PART-BETA
        $dic2 = DailyItemCode::create([
            'user_id'    => $machine->id,
            'item_code'  => 'PART-BETA',
            'start_date' => '2026-10-06',
            'shift'      => 2,
        ]);

        $hourly2 = HourlyRemark::create([
            'dic_id'            => $dic2->id,
            'start_time'        => '16:00',
            'target'            => 120,
            'actual_production' => 110,
            'remark'            => 'Suhu nozzle tinggi',
        ]);

        ProductionNgDetail::create([
            'hourly_remark_id' => $hourly2->id,
            'ng_type_id'       => $ngTypeBlackdot->id,
            'ng_quantity'      => 10,
            'ng_remarks'       => null,
        ]);

        $service = app(ProductionDashboardService::class);
        $startDate = Carbon::parse('2026-10-06')->startOfDay();
        $endDate = Carbon::parse('2026-10-06')->endOfDay();

        $allData = $service->getAllDashboardData($startDate, $endDate, null, (string)$machine->id, 'karawang');
        $ngBreakdown = $allData['ng_breakdown'];

        $this->assertNotEmpty($ngBreakdown);
        $blackdot = collect($ngBreakdown)->firstWhere('name', 'BLACKDOT');
        $this->assertNotNull($blackdot);
        $this->assertEquals(25, $blackdot['total']);
        $this->assertEquals(2, $blackdot['models_count']);

        // Verifikasi model breakdown
        $this->assertCount(2, $blackdot['models']);
        $this->assertEquals('PART-ALPHA', $blackdot['models'][0]['item_code']);
        $this->assertEquals('BEZEL FRONT PANEL', $blackdot['models'][0]['item_name']);
        $this->assertEquals(15, $blackdot['models'][0]['total']);

        $this->assertEquals('PART-BETA', $blackdot['models'][1]['item_code']);
        $this->assertEquals('COVER BOTTOM HOUSING', $blackdot['models'][1]['item_name']);
        $this->assertEquals(10, $blackdot['models'][1]['total']);

        // Verifikasi remark pada PART-ALPHA
        $alphaRecord = $blackdot['models'][0]['records'][0];
        $this->assertStringContainsString('Bintik hitam di sisi kanan', $alphaRecord['remark']);
        $this->assertEquals(15, $alphaRecord['quantity']);
        $this->assertEquals('K0450A', $alphaRecord['machine']);

        // Verifikasi remark pada PART-BETA (fallback ke hourly remark)
        $betaRecord = $blackdot['models'][1]['records'][0];
        $this->assertEquals('Suhu nozzle tinggi', $betaRecord['remark']);
        $this->assertEquals(10, $betaRecord['quantity']);

        // Verifikasi tampilan Livewire Dashboard
        $this->actingAs($user);
        Livewire::test(ProductionDashboard::class)
            ->set('viewType', 'daily')
            ->set('selectedDate', '2026-10-06')
            ->set('plant', 'karawang')
            ->assertSee('BLACKDOT')
            ->assertSee('2 Model')
            ->assertSee('PART-ALPHA')
            ->assertSee('BEZEL FRONT PANEL')
            ->assertSee('Remark Produksi')
            ->assertSee('Remark NG')
            ->assertSee('Bintik hitam di sisi kanan')
            ->assertSee('PART-BETA')
            ->assertSee('Suhu nozzle tinggi');
    }

    public function test_repair_machine_logs_integration_in_shift_performance_analysis()
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Admin Tester',
            'email'    => 'admin_repair@example.com',
            'role_id'  => $role->id,
            'password' => bcrypt('password'),
        ]);

        $machine = User::create(['name' => 'K0450A', 'email' => 'k0450a_rep@example.com', 'password' => 'secret']);

        // Repair log at 10:00 WIB (03:00 UTC) -> Shift 1, duration 45 minutes
        \App\Models\RepairMachineLog::create([
            'user_id'       => $machine->id,
            'item_code'     => 'PART-REP-01',
            'pic'           => 'Mamat Maintenance',
            'problem'       => 'Pemanas heater mati',
            'remark'        => 'Ganti thermocouple',
            'created_at'    => Carbon::parse('2026-10-06 03:00:00', 'UTC'),
            'finish_repair' => Carbon::parse('2026-10-06 03:45:00', 'UTC'),
        ]);

        $service = app(ProductionDashboardService::class);
        $date = Carbon::parse('2026-10-06');
        $data = $service->getAllDashboardData($date->copy()->startOfDay(), $date->copy()->endOfDay(), null, null, 'karawang');

        $shiftAnalysis = $data['shift_personnel_analysis'];
        $this->assertEquals(1, $shiftAnalysis['total_repair_count']);
        $this->assertEquals(45.0, $shiftAnalysis['total_repair_time_minutes']);

        // Shift 1 checks
        $shift1 = $shiftAnalysis['shifts'][1];
        $this->assertEquals(1, $shift1['repair_count']);
        $this->assertEquals(45.0, $shift1['repair_duration_minutes']);
        $this->assertEquals(['Mamat Maintenance'], $shift1['repairers']);
        $this->assertEquals('Mamat Maintenance', $shift1['repairers_str']);

        // Livewire view check
        $this->actingAs($user);
        Livewire::test(ProductionDashboard::class)
            ->set('viewType', 'daily')
            ->set('selectedDate', '2026-10-06')
            ->set('plant', 'karawang')
            ->assertSee('Shift Performance: Adjuster, Change Mould, Repair & NG Tracking')
            ->assertSee('Total Repair:')
            ->assertSee('Mamat Maintenance')
            ->assertSee('Repair Machine')
            ->assertSee('Pemanas heater mati');
    }
}

