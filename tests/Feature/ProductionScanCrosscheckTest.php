<?php

namespace Tests\Feature;

use App\Livewire\Store\ProductionScanCrosscheck;
use App\Models\DailyItemCode;
use App\Models\ProductionScannedData;
use App\Models\Role;
use App\Models\StoreCrosscheckDailyReport;
use App\Models\User;
use App\Services\StoreCrosscheckService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionScanCrosscheckTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $kbnMachine;
    protected User $krwMachine;
    protected string $date = '2026-10-30';

    protected function setUp(): void
    {
        parent::setUp();

        StoreCrosscheckService::ensureSchema();

        ProductionScannedData::where('label', 'like', 'LABEL-%')
            ->orWhere('label', 'like', 'SCAN-%')
            ->orWhere('label', 'like', 'BATCH-%')
            ->orWhere('label', 'like', 'SNAP-%')
            ->forceDelete();
        StoreCrosscheckDailyReport::where('report_date', $this->date)->delete();

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create([
            'name' => 'Store Officer',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $this->kbnMachine = User::factory()->create([
            'name' => 'MC-05',
            'role_id' => $opRole->id,
            'is_active' => true,
        ]);

        $this->krwMachine = User::factory()->create([
            'name' => 'K01',
            'role_id' => $opRole->id,
            'is_active' => true,
        ]);
    }

    protected function createDic(array $attributes = []): DailyItemCode
    {
        return DailyItemCode::create(array_merge([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-DEFAULT',
            'quantity' => 100,
            'actual_quantity' => 100,
            'schedule_date' => $this->date,
            'start_date' => $this->date,
            'end_date' => $this->date,
            'start_time' => '07:30:00',
            'end_time' => '15:30:00',
            'shift' => 1,
        ], $attributes));
    }

    protected function createScan(array $attributes = []): ProductionScannedData
    {
        return ProductionScannedData::create(array_merge([
            'spk_code' => 'SPK-DEFAULT',
            'warehouse' => 'WH-KBN',
            'quantity' => 50,
            'label' => 'LABEL-' . uniqid(),
            'user' => 'Operator',
            'store_check_status' => 'PENDING',
        ], $attributes));
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date])
            ->assertStatus(200)
            ->assertSee('Crosscheck Scan Produksi ke Store')
            ->assertSee('PLANT KBN')
            ->assertSee('PLANT KARAWANG');
    }

    public function test_plant_separation_kbn_and_karawang()
    {
        $this->actingAs($this->user);

        // KBN DIC & Scan
        $dicKbn = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-KBN-001',
            'quantity' => 100,
            'actual_quantity' => 100,
            'shift' => 1,
        ]);

        $scanKbn = $this->createScan([
            'spk_code' => 'SPK-KBN-1',
            'dic_id' => $dicKbn->id,
            'item_code' => 'ITEM-KBN-001',
            'warehouse' => 'WH-KBN',
            'quantity' => 50,
            'label' => 'LABEL-KBN-01',
            'user' => 'Operator 1',
        ]);

        // Karawang DIC & Scan
        $dicKrw = $this->createDic([
            'user_id' => $this->krwMachine->id,
            'item_code' => 'K-ITEM-KRW-002',
            'quantity' => 200,
            'actual_quantity' => 200,
            'shift' => 2,
        ]);

        $scanKrw = $this->createScan([
            'spk_code' => 'SPK-KRW-1',
            'dic_id' => $dicKrw->id,
            'item_code' => 'K-ITEM-KRW-002',
            'warehouse' => 'WH-KRW',
            'quantity' => 100,
            'label' => 'LABEL-KRW-01',
            'user' => 'Operator Karawang',
        ]);

        // Test KBN Tab
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->assertSee('LABEL-KBN-01')
            ->assertDontSee('LABEL-KRW-01');

        // Test Karawang Tab
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KARAWANG'])
            ->assertSee('LABEL-KRW-01')
            ->assertDontSee('LABEL-KBN-01');

        // Test ALL Tab
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'ALL'])
            ->assertSee('LABEL-KBN-01')
            ->assertSee('LABEL-KRW-01');
    }

    public function test_single_box_checklist_actions()
    {
        $this->actingAs($this->user);

        $dic = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-TEST-001',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $scan = $this->createScan([
            'spk_code' => 'SPK-TEST-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-TEST-001',
            'quantity' => 25,
            'label' => 'LABEL-TEST-01',
            'user' => 'Op 1',
        ]);

        // Mark as FOUND
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->call('markFound', $scan->id);

        $this->assertEquals('FOUND', $scan->fresh()->store_check_status);
        $this->assertEquals($this->user->id, $scan->fresh()->store_checked_by);
        $this->assertNotNull($scan->fresh()->store_checked_at);

        // Mark as NOT_FOUND
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->call('markNotFound', $scan->id);

        $this->assertEquals('NOT_FOUND', $scan->fresh()->store_check_status);

        // Reset to PENDING
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->call('resetStatus', $scan->id);

        $this->assertEquals('PENDING', $scan->fresh()->store_check_status);

        // Save note
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->call('saveNote', $scan->id, 'Dus basah sedikit di pojok');

        $this->assertEquals('Dus basah sedikit di pojok', $scan->fresh()->store_notes);
    }

    public function test_barcode_scanner_verification()
    {
        $this->actingAs($this->user);

        $dic = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-BARCODE-01',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $scan = $this->createScan([
            'spk_code' => 'SPK-BARCODE-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-BARCODE-01',
            'quantity' => 50,
            'label' => 'SCAN-ME-12345',
            'user' => 'Op Scanner',
        ]);

        // Scan barcode
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('barcodeInput', 'SCAN-ME-12345')
            ->call('scanBarcode')
            ->assertDispatched('play-scan-sound', sound: 'success');

        $this->assertEquals('FOUND', $scan->fresh()->store_check_status);

        // Scan barcode that does not exist
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('barcodeInput', 'NOT-FOUND-99999')
            ->call('scanBarcode')
            ->assertDispatched('play-scan-sound', sound: 'error');
    }

    public function test_batch_actions_and_mark_all_pending()
    {
        $this->actingAs($this->user);

        $dic = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-BATCH-01',
            'quantity' => 100,
            'actual_quantity' => 100,
            'shift' => 1,
        ]);

        $scan1 = $this->createScan([
            'spk_code' => 'SPK-BATCH-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-BATCH-01',
            'quantity' => 50,
            'label' => 'BATCH-LABEL-1',
        ]);

        $scan2 = $this->createScan([
            'spk_code' => 'SPK-BATCH-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-BATCH-01',
            'quantity' => 50,
            'label' => 'BATCH-LABEL-2',
        ]);

        // Batch mark selected
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('selectedBoxIds', [(string) $scan1->id, (string) $scan2->id])
            ->call('markSelectedFound');

        $this->assertEquals('FOUND', $scan1->fresh()->store_check_status);
        $this->assertEquals('FOUND', $scan2->fresh()->store_check_status);

        // Reset to pending
        $scan1->update(['store_check_status' => 'PENDING']);
        $scan2->update(['store_check_status' => 'PENDING']);

        // Quick action: markAllPendingAsFound
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->call('markAllPendingAsFound');

        $this->assertEquals('FOUND', $scan1->fresh()->store_check_status);
        $this->assertEquals('FOUND', $scan2->fresh()->store_check_status);
    }

    public function test_save_daily_report_snapshot()
    {
        $this->actingAs($this->user);

        $dic = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-SNAP-01',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $this->createScan([
            'spk_code' => 'SPK-SNAP-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-SNAP-01',
            'quantity' => 50,
            'label' => 'SNAP-LABEL-1',
            'store_check_status' => 'FOUND',
        ]);

        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('dailyNotes', 'Semua barang shift 1 KBN diterima lengkap dalam kondisi baik.')
            ->set('reportStatus', 'FINALIZED')
            ->call('saveDailySnapshot');

        $snapshot = StoreCrosscheckDailyReport::where('report_date', $this->date)
            ->where('plant', 'KBN')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals('FINALIZED', $snapshot->status);
        $this->assertEquals('Semua barang shift 1 KBN diterima lengkap dalam kondisi baik.', $snapshot->notes);
        $this->assertEquals(1, $snapshot->found_boxes);
        $this->assertEquals(50, $snapshot->found_qty);
        $this->assertEquals(100.0, (float) $snapshot->accuracy_rate);
    }

    public function test_printable_view_route()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('wms.production-scan-crosscheck.print', [
            'date' => $this->date,
            'plant' => 'KBN',
        ]));

        $response->assertStatus(200);
        $response->assertSee('LAPORAN CROSSCHECK SCAN PRODUKSI KE STORE');
        $response->assertSee('PT DAIJO INDUSTRIAL');
        $response->assertSee('Diverifikasi Oleh (Checker Store)');
    }

    public function test_filter_by_machine()
    {
        $this->actingAs($this->user);

        $opRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $machine2 = User::factory()->create([
            'name' => 'MC-08',
            'role_id' => $opRole->id,
            'is_active' => true,
        ]);

        $dic1 = $this->createDic([
            'user_id' => $this->kbnMachine->id, // MC-05
            'item_code' => 'ITEM-MC05-01',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $dic2 = $this->createDic([
            'user_id' => $machine2->id, // MC-08
            'item_code' => 'ITEM-MC08-01',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $scan1 = $this->createScan([
            'spk_code' => 'SPK-M1',
            'dic_id' => $dic1->id,
            'item_code' => 'ITEM-MC05-01',
            'quantity' => 50,
            'label' => 'LABEL-MC05-999',
        ]);

        $scan2 = $this->createScan([
            'spk_code' => 'SPK-M2',
            'dic_id' => $dic2->id,
            'item_code' => 'ITEM-MC08-01',
            'quantity' => 50,
            'label' => 'LABEL-MC08-888',
        ]);

        // When machineFilter is ALL, see both
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('machineFilter', 'ALL')
            ->assertSee('LABEL-MC05-999')
            ->assertSee('LABEL-MC08-888');

        // When machineFilter is MC-05, see only MC-05
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('machineFilter', 'MC-05')
            ->assertSee('LABEL-MC05-999')
            ->assertDontSee('LABEL-MC08-888');

        // When machineFilter is MC-08, see only MC-08
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('machineFilter', 'MC-08')
            ->assertSee('LABEL-MC08-888')
            ->assertDontSee('LABEL-MC05-999');
    }

    public function test_custom_verifier_name_for_shared_account()
    {
        $this->actingAs($this->user);

        $dic = $this->createDic([
            'user_id' => $this->kbnMachine->id,
            'item_code' => 'ITEM-VERIF-01',
            'quantity' => 50,
            'actual_quantity' => 50,
            'shift' => 1,
        ]);

        $scan = $this->createScan([
            'spk_code' => 'SPK-VERIF-1',
            'dic_id' => $dic->id,
            'item_code' => 'ITEM-VERIF-01',
            'quantity' => 50,
            'label' => 'LABEL-VERIF-01',
        ]);

        // When verifierName is set to 'Agus (Store)'
        Livewire::test(ProductionScanCrosscheck::class, ['selectedDate' => $this->date, 'plantTab' => 'KBN'])
            ->set('verifierName', 'Agus (Store)')
            ->call('markFound', $scan->id);

        $scan->refresh();
        $this->assertEquals('FOUND', $scan->store_check_status);
        $this->assertEquals('Agus (Store)', $scan->store_checked_by_name);
        $this->assertEquals($this->user->id, $scan->store_checked_by);
        $this->assertEquals('Agus (Store)', session('store_crosscheck_verifier_name'));
    }
}
