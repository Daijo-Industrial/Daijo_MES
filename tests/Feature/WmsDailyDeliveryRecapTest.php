<?php

namespace Tests\Feature;

use App\Exports\DailyDeliveryRecapExport;
use App\Livewire\Wms\DailyDeliveryRecap;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use App\Services\WmsDeliveryRecapService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class WmsDailyDeliveryRecapTest extends TestCase
{
    protected Role $adminRole;
    protected User $adminUser;

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

        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->adminUser = User::create([
            'name'     => 'Store Admin',
            'email'    => 'store@test.com',
            'password' => bcrypt('password'),
            'role_id'  => $this->adminRole->id,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_customer_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('customer_name');
            $table->timestamps();
        });

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->string('customer_code')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('whse_code');
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id');
            $table->integer('level_no');
            $table->integer('slot_no');
            $table->string('customer_code')->nullable();
            $table->string('position_code')->unique();
            $table->string('status')->default('EMPTY');
            $table->string('last_item_code')->nullable();
            $table->integer('max_capacity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_forms', function (Blueprint $table) {
            $table->string('pallet_id')->primary();
            $table->foreignId('position_id')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->date('prod_date');
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->double('total_pallet_qty', 15, 2)->default(0.00);
            $table->string('status')->default('STORED');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_form_details', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_form_id');
            $table->string('part_no')->nullable();
            $table->string('model_name')->nullable();
            $table->string('spk_no')->nullable();
            $table->double('qty', 15, 2)->default(0.00);
            $table->string('warehouse')->nullable();
            $table->string('label')->nullable();
            $table->boolean('is_no_label')->default(false);
            $table->string('no_label_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_production_day_cutoff_window_0730_to_0729()
    {
        // Setup Master Item
        MasterListItem::create([
            'item_code' => 'PART-A',
            'item_name' => 'Cover Front Panel',
        ]);

        // Pallet 1: Dibuat pada 2026-10-01 07:30:00 (Awal hari produksi 2026-10-01) -> MASUK
        $p1 = WmsPalletForm::create([
            'pallet_id'        => 'PLT-01',
            'prod_date'        => '2026-10-01',
            'delivery_name'    => 'Pak Joko',
            'delivery_shift'   => '1',
            'box_qty'          => 2,
            'total_pallet_qty' => 100,
        ]);
        $p1->created_at = Carbon::parse('2026-10-01 07:30:00');
        $p1->save();

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-01',
            'part_no'        => 'PART-A',
            'model_name'     => 'Cover Front Panel',
            'qty'            => 100,
            'label'          => 'LBL-01',
            'created_at'     => Carbon::parse('2026-10-01 07:30:00'),
        ]);

        // Pallet 2: Dibuat pada 2026-10-02 02:15:00 (Shift 3 malam pergantian tanggal) -> MASUK ke hari produksi 2026-10-01!
        $p2 = WmsPalletForm::create([
            'pallet_id'        => 'PLT-02',
            'prod_date'        => '2026-10-01',
            'delivery_name'    => 'Pak Joko',
            'delivery_shift'   => '3',
            'box_qty'          => 1,
            'total_pallet_qty' => 50,
        ]);
        $p2->created_at = Carbon::parse('2026-10-02 02:15:00');
        $p2->save();

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-02',
            'part_no'        => 'PART-A',
            'model_name'     => 'Cover Front Panel',
            'qty'            => 50,
            'label'          => 'LBL-02',
            'created_at'     => Carbon::parse('2026-10-02 02:15:00'),
        ]);

        // Pallet 3: Dibuat pada 2026-10-02 07:29:59 (Detik terakhir hari produksi 2026-10-01) -> MASUK!
        $p3 = WmsPalletForm::create([
            'pallet_id'        => 'PLT-03',
            'prod_date'        => '2026-10-01',
            'delivery_name'    => 'Pak Budi',
            'delivery_shift'   => '3',
            'box_qty'          => 1,
            'total_pallet_qty' => 25,
        ]);
        $p3->created_at = Carbon::parse('2026-10-02 07:29:59');
        $p3->save();

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-03',
            'part_no'        => 'PART-A',
            'model_name'     => 'Cover Front Panel',
            'qty'            => 25,
            'label'          => 'LBL-03',
            'created_at'     => Carbon::parse('2026-10-02 07:29:59'),
        ]);

        // Pallet 4: Dibuat tepat 2026-10-02 07:30:00 (Sudah masuk hari produksi 2026-10-02) -> TIDAK MASUK ke 2026-10-01
        $p4 = WmsPalletForm::create([
            'pallet_id'        => 'PLT-04',
            'prod_date'        => '2026-10-02',
            'delivery_name'    => 'Pak Joko',
            'delivery_shift'   => '1',
            'box_qty'          => 1,
            'total_pallet_qty' => 999,
        ]);
        $p4->created_at = Carbon::parse('2026-10-02 07:30:00');
        $p4->save();

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-04',
            'part_no'        => 'PART-A',
            'model_name'     => 'Cover Front Panel',
            'qty'            => 999,
            'label'          => 'LBL-04',
            'created_at'     => Carbon::parse('2026-10-02 07:30:00'),
        ]);

        // Eksekusi Service untuk tanggal 2026-10-01
        $service = app(WmsDeliveryRecapService::class);
        $recap = $service->getDailyRecap('2026-10-01');

        // Total Qty harus 100 + 50 + 25 = 175 (PLT-04 sebesar 999 tidak boleh masuk)
        $this->assertEquals(175, $recap['kpis']['total_qty']);
        $this->assertEquals(3, $recap['kpis']['total_boxes']);
        $this->assertEquals(3, $recap['kpis']['total_pallets']);

        // Grouping item
        $this->assertCount(1, $recap['items']);
        $itemA = $recap['items'][0];
        $this->assertEquals('PART-A', $itemA['part_no']);
        $this->assertEquals(175, $itemA['total_qty']);
        $this->assertEquals(3, $itemA['total_boxes']);
        $this->assertEquals(3, $itemA['pallets_count']);

        // Verifikasi Shift breakdown (Shift 1 = 100, Shift 3 = 75)
        $this->assertEquals(100, $itemA['shift_breakdown'][1]['qty']);
        $this->assertEquals(75, $itemA['shift_breakdown'][3]['qty']);
        $this->assertEquals(0, $itemA['shift_breakdown'][2]['qty']);

        // Verifikasi bahwa PLT-04 masuk saat dicek untuk tanggal 2026-10-02
        $recapDay2 = $service->getDailyRecap('2026-10-02');
        $this->assertEquals(999, $recapDay2['kpis']['total_qty']);
    }

    public function test_grouping_by_item_code_with_multiple_models()
    {
        MasterListItem::create(['item_code' => 'MODEL-X', 'item_name' => 'Door Panel Outer']);
        MasterListItem::create(['item_code' => 'MODEL-Y', 'item_name' => 'Door Panel Inner']);

        $pallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-MIX-01',
            'prod_date'        => '2026-10-06',
            'delivery_name'    => 'Vendor Logistik',
            'delivery_shift'   => '2',
            'box_qty'          => 3,
            'total_pallet_qty' => 300,
        ]);
        $pallet->created_at = Carbon::parse('2026-10-06 16:00:00');
        $pallet->save();

        // 2 box MODEL-X (total 200 pcs)
        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-MIX-01',
            'part_no'        => 'MODEL-X',
            'model_name'     => 'Door Panel Outer',
            'spk_no'         => 'SPK-9001',
            'qty'            => 100,
            'label'          => 'LBL-X1',
        ]);
        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-MIX-01',
            'part_no'        => 'MODEL-X',
            'model_name'     => 'Door Panel Outer',
            'spk_no'         => 'SPK-9001',
            'qty'            => 100,
            'label'          => 'LBL-X2',
        ]);

        // 1 box MODEL-Y (total 100 pcs)
        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-MIX-01',
            'part_no'        => 'MODEL-Y',
            'model_name'     => 'Door Panel Inner',
            'spk_no'         => 'SPK-9002',
            'qty'            => 100,
            'label'          => 'LBL-Y1',
        ]);

        $service = app(WmsDeliveryRecapService::class);
        $recap = $service->getDailyRecap('2026-10-06');

        $this->assertEquals(300, $recap['kpis']['total_qty']);
        $this->assertEquals(3, $recap['kpis']['total_boxes']);
        $this->assertEquals(2, $recap['kpis']['total_models']);

        // Model X diurutan pertama karena total_qty lebih besar (200 pcs vs 100 pcs)
        $this->assertEquals('MODEL-X', $recap['items'][0]['part_no']);
        $this->assertEquals(200, $recap['items'][0]['total_qty']);
        $this->assertEquals(2, $recap['items'][0]['total_boxes']);

        $this->assertEquals('MODEL-Y', $recap['items'][1]['part_no']);
        $this->assertEquals(100, $recap['items'][1]['total_qty']);
        $this->assertEquals(1, $recap['items'][1]['total_boxes']);
    }

    public function test_livewire_daily_delivery_recap_rendering_and_interaction()
    {
        $this->actingAs($this->adminUser);

        MasterListItem::create(['item_code' => 'PART-LIVE', 'item_name' => 'Front Bumper']);

        $pallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-LIVE-01',
            'prod_date'        => '2026-10-06',
            'delivery_name'    => 'Driver Supri',
            'delivery_shift'   => '1',
            'box_qty'          => 1,
            'total_pallet_qty' => 50,
        ]);
        $pallet->created_at = Carbon::parse('2026-10-06 10:00:00');
        $pallet->save();

        WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-LIVE-01',
            'part_no'        => 'PART-LIVE',
            'model_name'     => 'Front Bumper',
            'spk_no'         => 'SPK-8888',
            'qty'            => 50,
            'label'          => 'LBL-8888',
        ]);

        Livewire::test(DailyDeliveryRecap::class, ['selectedDate' => '2026-10-06'])
            ->assertSee('Rekap Harian Delivery FG')
            ->assertSee('PART-LIVE')
            ->assertSee('Front Bumper')
            ->assertSee('Driver Supri')
            ->assertSee('50')
            ->assertSee('SPK-8888')
            ->call('setYesterday')
            ->assertSet('selectedDate', '2026-10-05')
            ->call('setTomorrow')
            ->assertSet('selectedDate', '2026-10-06');
    }

    public function test_excel_export_returns_download()
    {
        Excel::fake();

        $this->actingAs($this->adminUser);

        Livewire::test(DailyDeliveryRecap::class, ['selectedDate' => '2026-10-06'])
            ->call('exportExcel');

        Excel::assertDownloaded('Rekap_Delivery_20261006.xlsx', function (DailyDeliveryRecapExport $export) {
            $sheets = $export->sheets();
            return count($sheets) === 2;
        });
    }

    public function test_shift_schedule_accuracy_0730_1530_2330()
    {
        // Shift 1: 07:30 - 15:30
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 07:30:00')));
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 07:31:00')));
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 15:29:59')));

        // Shift 2: 15:30 - 23:30
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 15:30:00')));
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 15:44:00')));
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 21:41:00')));
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 21:52:00')));
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 23:25:00')));
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 23:29:59')));

        // Shift 3: 23:30 - 07:30 next day
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-06 23:30:00')));
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-07 01:46:00')));
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-07 02:54:00')));
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-07 04:09:00')));
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime(Carbon::parse('2026-10-07 07:29:59')));
    }

    public function test_recap_preserves_data_when_items_are_dispatched_and_soft_deleted()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id'        => 'PLT-DISPATCH-01',
            'prod_date'        => '2026-10-06',
            'delivery_name'    => 'Driver Barri',
            'delivery_shift'   => 1,
            'lot_no'           => 'LOT-DISPATCH',
            'box_qty'          => 2,
            'total_pallet_qty' => 100,
            'status'           => 'STORED',
            'created_at'       => Carbon::parse('2026-10-06 09:00:00'),
        ]);

        $detail1 = WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no'        => 'PART-OUTBOUND',
            'model_name'     => 'Handle Door Outer',
            'spk_no'         => 'SPK-OUT-01',
            'qty'            => 50,
            'label'          => 'LBL-OUT-01',
            'created_at'     => Carbon::parse('2026-10-06 09:05:00'),
        ]);

        $detail2 = WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no'        => 'PART-OUTBOUND',
            'model_name'     => 'Handle Door Outer',
            'spk_no'         => 'SPK-OUT-01',
            'qty'            => 50,
            'label'          => 'LBL-OUT-02',
            'created_at'     => Carbon::parse('2026-10-06 09:06:00'),
        ]);

        // Simulate Store Out scan: detail 1 is dispatched out (soft deleted at 14:15)
        $detail1->delete(); // sets deleted_at

        $service = app(WmsDeliveryRecapService::class);
        $recap = $service->getDailyRecap('2026-10-06');

        // Total delivery intake on that day remains 100 pcs (2 boxes) and DOES NOT disappear!
        $this->assertEquals(100, $recap['kpis']['total_qty']);
        $this->assertEquals(2, $recap['kpis']['total_boxes']);
        $this->assertEquals(1, $recap['kpis']['total_pallets']);

        $itemSummary = collect($recap['items'])->firstWhere('part_no', 'PART-OUTBOUND');
        $this->assertNotNull($itemSummary);
        $this->assertEquals(100, $itemSummary['total_qty']);
        $this->assertEquals(50, $itemSummary['qty_in_warehouse']);
        $this->assertEquals(50, $itemSummary['qty_out']);

        // Check box logs
        $boxLog1 = collect($recap['box_logs'])->firstWhere('label', 'LBL-OUT-01');
        $this->assertTrue($boxLog1['is_out']);
        $this->assertEquals('KELUAR', $boxLog1['status']);
        $this->assertNotEmpty($boxLog1['out_time']);

        $boxLog2 = collect($recap['box_logs'])->firstWhere('label', 'LBL-OUT-02');
        $this->assertFalse($boxLog2['is_out']);
        $this->assertEquals('DI GUDANG', $boxLog2['status']);
    }
}
