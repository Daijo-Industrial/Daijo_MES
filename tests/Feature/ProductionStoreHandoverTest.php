<?php

namespace Tests\Feature;

use App\Livewire\ProductionStoreHandoverIndex;
use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\ProductionScannedData;
use App\Models\ProductionStoreHandover;
use App\Models\ProductionStoreHandoverLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Services\ProductionStoreHandoverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionStoreHandoverTest extends TestCase
{
    protected Role $adminRole;
    protected User $user;
    protected ProductionStoreHandoverService $service;

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

        $this->adminRole = Role::create(['name' => 'ADMIN']);

        $this->user = new User([
            'name' => 'Store Officer',
            'email' => 'store@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->role_id = $this->adminRole->id;
        $this->user->save();

        $this->service = app(ProductionStoreHandoverService::class);
    }

    protected function createTestSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
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

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->string('item_name')->nullable();
            $table->string('description')->nullable();
            $table->string('customer_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('daily_item_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->date('schedule_date');
            $table->tinyInteger('shift')->default(1);
            $table->string('item_code', 100);
            $table->integer('actual_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('hourly_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dic_id');
            $table->integer('actual_production')->default(0);
            $table->timestamps();
        });

        Schema::create('production_scanned_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dic_id')->nullable();
            $table->string('item_code', 100);
            $table->string('label')->unique();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('production_store_handovers', function (Blueprint $table) {
            $table->id();
            $table->date('production_date')->index();
            $table->tinyInteger('shift')->default(1)->index();
            $table->string('machine_name', 50)->default('UNKNOWN')->index();
            $table->string('plant', 20)->default('KBN')->index();
            $table->string('item_code', 100)->index();
            $table->string('item_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->integer('operator_actual_qty')->default(0);
            $table->integer('scanned_qty')->default(0);
            $table->integer('scanned_boxes')->default(0);
            $table->integer('store_received_boxes')->default(0);
            $table->integer('store_received_qty')->default(0);
            $table->string('status', 30)->default('PENDING')->index();
            $table->foreignId('last_verified_by')->nullable();
            $table->string('last_verified_by_name')->nullable();
            $table->dateTime('last_verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['production_date', 'shift', 'item_code', 'machine_name'], 'prod_store_handover_unique');
        });

        Schema::create('production_store_handover_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_id');
            $table->string('pallet_id', 100)->nullable();
            $table->integer('received_boxes')->default(0);
            $table->integer('received_qty')->default(0);
            $table->foreignId('verified_by')->nullable();
            $table->string('verified_by_name')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_racks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('wms_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->nullable();
            $table->string('code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('wms_pallet_forms', function (Blueprint $table) {
            $table->string('pallet_id')->primary();
            $table->foreignId('position_id')->nullable();
            $table->string('part_no');
            $table->string('model_name')->nullable();
            $table->date('prod_date')->nullable();
            $table->string('lot_no')->nullable();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_shift')->nullable();
            $table->integer('box_qty')->default(0);
            $table->decimal('total_pallet_qty', 15, 2)->default(0);
            $table->string('status')->default('STORED');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_pallet_form_details', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_form_id');
            $table->string('part_no');
            $table->string('spk_no')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_sync_daily_production_aggregates_operator_and_scanned_data()
    {
        $date = '2026-10-29';
        $shift = 1;
        $itemCode = 'PART-ABC-01';

        MasterListItem::create([
            'item_code' => $itemCode,
            'item_name' => 'Cover Handle Front',
            'customer_code' => 'CUST-HONDA',
        ]);

        $dic = DailyItemCode::create([
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => $itemCode,
            'actual_quantity' => 200,
        ]);

        HourlyRemark::create([
            'dic_id' => $dic->id,
            'actual_production' => 523, // Operator input actual
        ]);

        // 10 boxes @ 50 pcs scanned
        for ($i = 1; $i <= 10; $i++) {
            ProductionScannedData::create([
                'dic_id' => $dic->id,
                'item_code' => $itemCode,
                'label' => "BOX-LABEL-{$i}",
                'quantity' => 50,
            ]);
        }

        $this->service->syncDailyProduction($date, $shift);

        $handover = ProductionStoreHandover::where('production_date', $date)
            ->where('shift', $shift)
            ->where('item_code', $itemCode)
            ->first();

        $this->assertNotNull($handover);
        $this->assertEquals(523, $handover->operator_actual_qty);
        $this->assertEquals(10, $handover->scanned_boxes);
        $this->assertEquals(500, $handover->scanned_qty);
        $this->assertEquals(0, $handover->store_received_boxes);
        $this->assertEquals(10, $handover->in_transit_boxes);
        $this->assertEquals(500, $handover->in_transit_qty);
        $this->assertEquals('PENDING', $handover->status);
    }

    public function test_store_receives_in_progressive_batches()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-XYZ-99';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Bracket Mount',
            'customer_name' => 'CUST-YAMAHA',
            'operator_actual_qty' => 500,
            'scanned_boxes' => 10,
            'scanned_qty' => 500,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        // Trip 1: Store receives 3 boxes (150 pcs)
        $success = $this->service->receiveBatch($handover->id, 3, 150, 'Trip 1 - Trolley A', $this->user);
        $this->assertTrue($success);

        $handover->refresh();
        $this->assertEquals(3, $handover->store_received_boxes);
        $this->assertEquals(150, $handover->store_received_qty);
        $this->assertEquals(7, $handover->in_transit_boxes);
        $this->assertEquals(350, $handover->in_transit_qty);
        $this->assertEquals('PARTIAL', $handover->status);

        $log = ProductionStoreHandoverLog::where('handover_id', $handover->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(3, $log->received_boxes);
        $this->assertEquals('Trip 1 - Trolley A', $log->remarks);
        $this->assertEquals('Store Officer', $log->verified_by_name);

        // Trip 2: Store receives remaining 7 boxes (350 pcs)
        $this->service->receiveBatch($handover->id, 7, 350, 'Trip 2 - Final', $this->user);
        $handover->refresh();

        $this->assertEquals(10, $handover->store_received_boxes);
        $this->assertEquals(500, $handover->store_received_qty);
        $this->assertEquals(0, $handover->in_transit_boxes);
        $this->assertEquals('COMPLETED', $handover->status);
    }

    public function test_livewire_component_interactivity()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-LW-01';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Housing Upper',
            'customer_name' => 'CUST-SUZUKI',
            'operator_actual_qty' => 100,
            'scanned_boxes' => 5,
            'scanned_qty' => 250,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->user);

        Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->set('shiftFilter', '1')
            ->assertSee('PART-LW-01')
            ->assertSee('Housing Upper')
            ->call('receiveAllInTransit', $handover->id)
            ->assertDispatched('notify');

        $handover->refresh();
        $this->assertEquals(5, $handover->store_received_boxes);
        $this->assertEquals(250, $handover->store_received_qty);
        $this->assertEquals('COMPLETED', $handover->status);
    }

    public function test_custom_receive_calculates_qty_and_allows_loss_pack_manual_edit()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-LOSS-01';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 2,
            'item_code' => $itemCode,
            'item_name' => 'Loss Pack Test Item',
            'customer_name' => 'CUST-TEST',
            'operator_actual_qty' => 740,
            'scanned_boxes' => 37,
            'scanned_qty' => 740, // 20 pcs per box
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->user);

        // Open modal -> automatically calculates 37 boxes * 20 = 740
        $test = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->call('openReceiveModal', $handover->id);

        $test->assertSet('qtyPerBox', 20)
            ->assertSet('modalBoxes', 37)
            ->assertSet('modalQty', 740);

        // Change boxes to 10 -> automatically calculates 10 * 20 = 200
        $test->set('modalBoxes', 10)
            ->assertSet('modalQty', 200);

        // User manually edits modalQty for loss pack item on last box (e.g. 195 pcs)
        $test->set('modalQty', 195)
            ->call('submitCustomReceive')
            ->assertDispatched('notify');

        $handover->refresh();
        $this->assertEquals(10, $handover->store_received_boxes);
        $this->assertEquals(195, $handover->store_received_qty); // Correctly saved 195 pcs loss pack!
        $this->assertEquals(27, $handover->in_transit_boxes);
        $this->assertEquals('PARTIAL', $handover->status);
    }

    public function test_karawang_machines_prefixed_with_k_are_separated_from_kbn()
    {
        $date = '2026-10-29';
        $shift = 1;
        $itemCode = 'PART-DUAL-01';

        // Mesin 1: Plant KBN (misal 150T-1)
        $kbnMachine = User::create([
            'name' => '150T-1',
            'email' => '150t1@test.com',
            'password' => bcrypt('password'),
        ]);

        // Mesin 2: Plant Karawang (misal K01)
        $krwMachine = User::create([
            'name' => 'K01',
            'email' => 'k01@test.com',
            'password' => bcrypt('password'),
        ]);

        $dicKbn = DailyItemCode::create([
            'user_id' => $kbnMachine->id,
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => $itemCode,
            'actual_quantity' => 100,
        ]);

        $dicKrw = DailyItemCode::create([
            'user_id' => $krwMachine->id,
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => $itemCode,
            'actual_quantity' => 200,
        ]);

        // Box scan KBN
        ProductionScannedData::create([
            'dic_id' => $dicKbn->id,
            'item_code' => $itemCode,
            'label' => 'BOX-KBN-01',
            'quantity' => 100,
        ]);

        // Box scan Karawang
        ProductionScannedData::create([
            'dic_id' => $dicKrw->id,
            'item_code' => $itemCode,
            'label' => 'BOX-KRW-01',
            'quantity' => 200,
        ]);

        $this->service->syncDailyProduction($date, $shift);

        // Harus ada 2 record terpisah meskipun item_code dan shift sama
        $handovers = ProductionStoreHandover::where('production_date', $date)
            ->where('shift', $shift)
            ->where('item_code', $itemCode)
            ->get();

        $this->assertCount(2, $handovers);

        $kbnHandover = $handovers->firstWhere('machine_name', '150T-1');
        $krwHandover = $handovers->firstWhere('machine_name', 'K01');

        $this->assertNotNull($kbnHandover);
        $this->assertNotNull($krwHandover);

        $this->assertEquals('KBN', $kbnHandover->plant);
        $this->assertFalse($kbnHandover->is_karawang);
        $this->assertEquals(100, $kbnHandover->operator_actual_qty);
        $this->assertEquals(1, $kbnHandover->scanned_boxes);
        $this->assertEquals(100, $kbnHandover->scanned_qty);

        $this->assertEquals('KARAWANG', $krwHandover->plant);
        $this->assertTrue($krwHandover->is_karawang);
        $this->assertEquals(200, $krwHandover->operator_actual_qty);
        $this->assertEquals(1, $krwHandover->scanned_boxes);
        $this->assertEquals(200, $krwHandover->scanned_qty);

        // Test filter plant KARAWANG di getSummary
        $krwSummary = $this->service->getSummary($date, $shift, 'KARAWANG');
        $this->assertEquals(1, $krwSummary['stats']['total_items']);
        $this->assertEquals('K01', $krwSummary['items']->first()->machine_name);

        // Test filter plant KBN di getSummary
        $kbnSummary = $this->service->getSummary($date, $shift, 'KBN');
        $this->assertEquals(1, $kbnSummary['stats']['total_items']);
        $this->assertEquals('150T-1', $kbnSummary['items']->first()->machine_name);
    }

    public function test_items_without_machine_and_zero_actual_are_not_shown_or_saved()
    {
        $date = '2026-10-30';
        $shift = 1;

        $machine = User::create([
            'name' => '150T-2',
            'email' => '150t2@test.com',
            'password' => bcrypt('password'),
        ]);

        // Item 1: Tidak ada mesin DAN actual 0 -> HARUS DI-EXCLUDE
        DailyItemCode::create([
            'user_id' => null,
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => 'ITEM-GHOST-0',
            'actual_quantity' => 0,
        ]);

        // Item 2: Ada mesin tapi actual 0 -> TETAP DI-SHOW (karena mesin terdaftar)
        DailyItemCode::create([
            'user_id' => $machine->id,
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => 'ITEM-VALID-MACHINE',
            'actual_quantity' => 0,
        ]);

        // Item 3: Tidak ada mesin tapi actual > 0 -> TETAP DI-SHOW (karena ada output fisik)
        $dicActual = DailyItemCode::create([
            'user_id' => null,
            'schedule_date' => $date,
            'shift' => $shift,
            'item_code' => 'ITEM-ACTUAL-NO-MACHINE',
            'actual_quantity' => 50,
        ]);

        $this->service->syncDailyProduction($date, $shift);

        // ITEM-GHOST-0 tidak boleh ada di database
        $this->assertFalse(
            ProductionStoreHandover::where('production_date', $date)
                ->where('item_code', 'ITEM-GHOST-0')
                ->exists()
        );

        // ITEM-VALID-MACHINE dan ITEM-ACTUAL-NO-MACHINE harus tersimpan
        $this->assertTrue(
            ProductionStoreHandover::where('production_date', $date)
                ->where('item_code', 'ITEM-VALID-MACHINE')
                ->exists()
        );
        $this->assertTrue(
            ProductionStoreHandover::where('production_date', $date)
                ->where('item_code', 'ITEM-ACTUAL-NO-MACHINE')
                ->exists()
        );

        // Di getSummary(), total_items harus 2 (ITEM-GHOST-0 tidak muncul)
        $summary = $this->service->getSummary($date, $shift);
        $this->assertEquals(2, $summary['stats']['total_items']);

        $itemCodes = $summary['items']->pluck('item_code')->toArray();
        $this->assertNotContains('ITEM-GHOST-0', $itemCodes);
        $this->assertContains('ITEM-VALID-MACHINE', $itemCodes);
        $this->assertContains('ITEM-ACTUAL-NO-MACHINE', $itemCodes);
    }

    public function test_production_users_cannot_see_receive_buttons_or_execute_store_receipt()
    {
        $date = '2026-10-31';
        $itemCode = 'PART-AUTH-01';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'machine_name' => '150T-1',
            'plant' => 'KBN',
            'item_code' => $itemCode,
            'item_name' => 'Role Guard Test Item',
            'customer_name' => 'CUST-TEST',
            'operator_actual_qty' => 100,
            'scanned_boxes' => 5,
            'scanned_qty' => 250,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        // 1. Buat User Produksi (Role: PRODUCTION)
        $prodRole = Role::create(['name' => 'PRODUCTION']);
        $prodUser = User::create([
            'name' => 'Operator / Leader Produksi',
            'email' => 'prod@test.com',
            'password' => bcrypt('password'),
            'role_id' => $prodRole->id,
        ]);

        $this->actingAs($prodUser);

        // User Produksi mengakses halaman
        $test = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date);

        // Verifikasi: User Produksi TIDAK melihat tombol terima dan scanner store
        $test->assertDontSee('Terima 5 Box')
            ->assertDontSee('+ Custom')
            ->assertDontSee('Quick Scanner Store')
            ->assertSee('5 Box Di Jalan')
            ->assertSee('Status Serah Terima');

        // Jika user produksi mencoba memanggil receiveAllInTransit via Livewire
        $test->call('receiveAllInTransit', $handover->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error');

        // Pastikan record tidak berubah
        $handover->refresh();
        $this->assertEquals(0, $handover->store_received_boxes);
        $this->assertEquals('PENDING', $handover->status);

        // 2. Sekarang login sebagai Store / Admin (Role: STORE)
        $storeRole = Role::create(['name' => 'STORE']);
        $storeUser = User::create([
            'name' => 'Petugas Store',
            'email' => 'store_officer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $storeRole->id,
        ]);

        $this->actingAs($storeUser);

        $storeTest = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date);

        // Verifikasi: Petugas Store MELIHAT tombol terima dan scanner store
        $storeTest->assertSee('Terima 5 Box')
            ->assertSee('+ Custom')
            ->assertSee('Quick Scanner Store')
            ->assertSee('Aksi Store');

        // Petugas Store sukses melakukan penerimaan
        $storeTest->call('receiveAllInTransit', $handover->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $handover->refresh();
        $this->assertEquals(5, $handover->store_received_boxes);
        $this->assertEquals('COMPLETED', $handover->status);
    }

    public function test_receive_pallet_verifies_entire_pallet_form_and_prevents_duplicate_receipt()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-PALLET-01';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Door Panel Inner',
            'customer_name' => 'CUST-TOYOTA',
            'operator_actual_qty' => 1000,
            'scanned_boxes' => 20,
            'scanned_qty' => 1000,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        // Buat Pallet Form WMS berisi 10 box (500 pcs)
        $palletId = 'PLT-20261029-0001';
        $pallet = WmsPalletForm::create([
            'pallet_id' => $palletId,
            'part_no' => $itemCode,
            'model_name' => 'Door Panel Inner',
            'prod_date' => $date,
            'delivery_name' => 'Delivery 1',
            'delivery_shift' => '1',
            'box_qty' => 10,
            'total_pallet_qty' => 500,
            'status' => 'STORED',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            WmsPalletFormDetail::create([
                'pallet_form_id' => $palletId,
                'part_no' => $itemCode,
                'label' => "BOX-PLT-{$i}",
                'spk_no' => 'SPK-001',
                'qty' => 50,
            ]);
        }

        // Store menerima Pallet Form pertama
        $res = $this->service->receivePallet($handover->id, $palletId, $this->user);
        $this->assertTrue($res['success']);
        $this->assertEquals(10, $res['boxes']);
        $this->assertEquals(500, $res['qty']);

        $handover->refresh();
        $this->assertEquals(10, $handover->store_received_boxes);
        $this->assertEquals(500, $handover->store_received_qty);
        $this->assertEquals(10, $handover->in_transit_boxes);
        $this->assertEquals('PARTIAL', $handover->status);

        // Verifikasi log mencatat pallet_id
        $log = ProductionStoreHandoverLog::where('handover_id', $handover->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals($palletId, $log->pallet_id);
        $this->assertEquals(10, $log->received_boxes);
        $this->assertEquals(500, $log->received_qty);

        // Uji Anti-Duplikasi: Percobaan menerima pallet yang sama harus ditolak
        $duplicateRes = $this->service->receivePallet($handover->id, $palletId, $this->user);
        $this->assertFalse($duplicateRes['success']);
        $this->assertStringContainsString('sudah pernah diverifikasi', $duplicateRes['message']);

        // Handover tidak boleh berubah
        $handover->refresh();
        $this->assertEquals(10, $handover->store_received_boxes);
    }

    public function test_scan_barcode_pallet_id_in_livewire_verifies_entire_pallet()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-SCAN-PLT-01';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Fender Left',
            'customer_name' => 'CUST-HONDA',
            'operator_actual_qty' => 600,
            'scanned_boxes' => 12,
            'scanned_qty' => 600,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        $palletId = 'PLT-20261029-0099';
        WmsPalletForm::create([
            'pallet_id' => $palletId,
            'part_no' => $itemCode,
            'prod_date' => $date,
            'delivery_name' => 'Trip Pagi',
            'delivery_shift' => '1',
            'box_qty' => 12,
            'total_pallet_qty' => 600,
            'status' => 'STORED',
        ]);

        $this->actingAs($this->user);

        // Scan barcode Pallet ID di quick scanner
        Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->set('barcodeInput', $palletId)
            ->call('scanBarcodeReceive')
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $handover->refresh();
        $this->assertEquals(12, $handover->store_received_boxes);
        $this->assertEquals(600, $handover->store_received_qty);
        $this->assertEquals(0, $handover->in_transit_boxes);
        $this->assertEquals('COMPLETED', $handover->status);
    }

    public function test_pallets_modal_interaction_and_receive_all_unreceived_pallets()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-MODAL-PLT';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Console Box',
            'customer_name' => 'CUST-DAIHATSU',
            'operator_actual_qty' => 800,
            'scanned_boxes' => 16,
            'scanned_qty' => 800,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        // Pallet 1: 8 box (400 pcs)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-20261029-0101',
            'part_no' => $itemCode,
            'prod_date' => $date,
            'delivery_name' => 'Delivery 1',
            'delivery_shift' => '1',
            'box_qty' => 8,
            'total_pallet_qty' => 400,
            'status' => 'STORED',
        ]);

        // Pallet 2: 8 box (400 pcs)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-20261029-0102',
            'part_no' => $itemCode,
            'prod_date' => $date,
            'delivery_name' => 'Delivery 2',
            'delivery_shift' => '1',
            'box_qty' => 8,
            'total_pallet_qty' => 400,
            'status' => 'STORED',
        ]);

        $this->actingAs($this->user);

        // Buka modal Pallet Form
        $test = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->call('openPalletsModal', $handover->id);

        $test->assertSet('showPalletsModal', true)
            ->assertCount('palletFormsList', 2)
            ->assertSee('PLT-20261029-0101')
            ->assertSee('PLT-20261029-0102');

        // Terima seluruh pallet in-transit via tombol di modal
        $test->call('receiveAllPalletsAction')
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $handover->refresh();
        $this->assertEquals(16, $handover->store_received_boxes);
        $this->assertEquals(800, $handover->store_received_qty);
        $this->assertEquals('COMPLETED', $handover->status);

        // Verifikasi list di modal sekarang menunjukkan keduanya sudah diterima
        $palletList = $test->get('palletFormsList');
        $this->assertTrue($palletList[0]['is_received']);
        $this->assertTrue($palletList[1]['is_received']);
    }

    public function test_store_sees_pallet_verification_button_and_empty_state_when_no_pallets_published()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-EMPTY-PLT';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 3,
            'item_code' => $itemCode,
            'item_name' => 'Fender Trim',
            'customer_name' => 'CUST-HONDA',
            'operator_actual_qty' => 0,
            'scanned_boxes' => 22,
            'scanned_qty' => 440,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->user);

        // Buka modal Pallet Form ketika belum ada pallet form terbit
        $test = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->call('openPalletsModal', $handover->id);

        $test->assertSet('showPalletsModal', true)
            ->assertCount('palletFormsList', 0)
            ->assertSee('Belum Ada Pallet Form WMS Diterbitkan')
            ->assertSee('Terima Manual Box Pecahan (22 Box)');
    }

    public function test_store_user_defaults_to_queue_tab_and_can_receive_pallet_with_single_click()
    {
        $date = '2026-10-29';
        $itemCode = 'PART-QUEUE-TEST';

        $handover = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $itemCode,
            'item_name' => 'Grille Front Lower',
            'customer_name' => 'CUST-SUZUKI',
            'operator_actual_qty' => 500,
            'scanned_boxes' => 10,
            'scanned_qty' => 500,
            'store_received_boxes' => 0,
            'store_received_qty' => 0,
            'status' => 'PENDING',
        ]);

        $palletId = 'PLT-QUEUE-001';
        $utcTime = \Carbon\Carbon::parse('2026-10-29 02:48:00', 'UTC');
        WmsPalletForm::create([
            'pallet_id' => $palletId,
            'part_no' => $itemCode,
            'model_name' => 'Grille Front Lower',
            'prod_date' => $date,
            'delivery_name' => 'Ferry Operator',
            'delivery_shift' => '1',
            'box_qty' => 10,
            'total_pallet_qty' => 500,
            'status' => 'STORED',
            'created_at' => $utcTime,
        ]);

        $this->actingAs($this->user);

        // Store user mounts -> defaults to 'queue' tab
        $test = Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->assertSet('activeTab', 'queue')
            ->assertSee($palletId)
            ->assertSee('Grille Front Lower')
            ->assertSee('09:48 WIB') // 02:48 UTC dikonversi dengan tepat ke 09:48 WIB
            ->assertSee('TERIMA PALLET INI (1-KLIK)');

        // 1-Click Receive from Queue
        $test->call('receivePalletFromQueue', $palletId)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $handover->refresh();
        $this->assertEquals(10, $handover->store_received_boxes);
        $this->assertEquals(500, $handover->store_received_qty);
        $this->assertEquals('COMPLETED', $handover->status);

        // Log anti-duplikasi tercatat
        $this->assertDatabaseHas('production_store_handover_logs', [
            'handover_id' => $handover->id,
            'pallet_id' => $palletId,
            'received_boxes' => 10,
            'received_qty' => 500,
        ]);

        // Jika diklik lagi, terlindungi dari duplikasi
        $test->call('receivePalletFromQueue', $palletId)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'warning');
    }

    public function test_store_can_receive_all_pending_queue_at_once()
    {
        $date = '2026-10-29';
        $item1 = 'PART-Q1';
        $item2 = 'PART-Q2';

        $h1 = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $item1,
            'item_name' => 'Part 1',
            'operator_actual_qty' => 100,
            'scanned_boxes' => 2,
            'scanned_qty' => 100,
            'status' => 'PENDING',
        ]);

        $h2 = ProductionStoreHandover::create([
            'production_date' => $date,
            'shift' => 1,
            'item_code' => $item2,
            'item_name' => 'Part 2',
            'operator_actual_qty' => 200,
            'scanned_boxes' => 4,
            'scanned_qty' => 200,
            'status' => 'PENDING',
        ]);

        WmsPalletForm::create([
            'pallet_id' => 'PLT-BULK-01',
            'part_no' => $item1,
            'prod_date' => $date,
            'delivery_shift' => '1',
            'box_qty' => 2,
            'total_pallet_qty' => 100,
            'status' => 'STORED',
        ]);

        WmsPalletForm::create([
            'pallet_id' => 'PLT-BULK-02',
            'part_no' => $item2,
            'prod_date' => $date,
            'delivery_shift' => '1',
            'box_qty' => 4,
            'total_pallet_qty' => 200,
            'status' => 'STORED',
        ]);

        $this->actingAs($this->user);

        Livewire::test(ProductionStoreHandoverIndex::class)
            ->set('selectedDate', $date)
            ->call('receiveAllPendingQueue')
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $h1->refresh();
        $h2->refresh();
        $this->assertEquals('COMPLETED', $h1->status);
        $this->assertEquals('COMPLETED', $h2->status);
    }
}

