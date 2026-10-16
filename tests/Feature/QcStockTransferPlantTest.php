<?php

namespace Tests\Feature;

use App\Livewire\Qc\QcStockTransfer;
use App\Livewire\Qc\QcStockTransferKbn;
use App\Models\QcTransferLog;
use App\Models\Role;
use App\Models\User;
use App\Services\QcTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class QcStockTransferPlantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'fake_test_token'], 200),
            '*/api/inventory_transfer/create' => Http::response(['status' => true, 'message' => 'Success'], 200),
        ]);
    }

    private function getQualityUser()
    {
        $role = Role::firstOrCreate(['name' => 'QUALITY']);

        return User::firstOrCreate(
            ['email' => 'qc_test@daijo.co.id'],
            [
                'name' => 'QC Tester',
                'username' => 'qctester',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );
    }

    public function test_kbn_inspection_only_transfers_ng_to_rjct_and_skips_ok()
    {
        $user = $this->getQualityUser();
        $uniq = uniqid();

        // 1. Create a dummy production summary for KBN (warehouse = FFI)
        $summaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-KBN-' . $uniq,
            'total_quantity' => 100,
            'warehouse' => 'FFI',
            'label' => 'LBL-KBN-' . $uniq,
            'sap_sent' => 1,
            'qc_status' => 0,
            'created_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $boxId = DB::table('production_scanned_data')->insertGetId([
            'summary_id' => $summaryId,
            'dic_id' => 1,
            'spk_code' => 'SPK-KBN-' . $uniq,
            'item_code' => 'ITEM-KBN-001',
            'quantity' => 100,
            'label' => 'BOX-KBN-' . $uniq,
            'warehouse' => 'FFI',
            'user' => 'Operator 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(QcTransferService::class);

        // Process inspection with 15 NG, 85 OK (isKbn = true)
        $result = $service->processSingleBoxInspection($boxId, 15, $user->id, 'Testing KBN NG', true);

        $this->assertTrue($result['success']);

        $log = QcTransferLog::where('scanned_data_id', $boxId)->first();
        $this->assertNotNull($log);
        $this->assertEquals(85, $log->ok_qty);
        $this->assertEquals(15, $log->ng_qty);

        // Verify KBN rules: OK must NOT have target warehouse, ok_sap_status must be 1 (skipped), NG must be RJCT
        $this->assertNull($log->ok_to_warehouse);
        $this->assertEquals(1, $log->ok_sap_status);
        $this->assertEquals('RJCT', $log->ng_to_warehouse);
        $this->assertEquals(1, $log->ng_sap_status); // Success from mock HTTP
    }

    public function test_karawang_inspection_transfers_both_ok_and_ng()
    {
        $user = $this->getQualityUser();
        $uniq = uniqid();

        // Create a dummy production summary for Karawang (warehouse = KRFFI)
        $summaryId = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-KRW-' . $uniq,
            'total_quantity' => 100,
            'warehouse' => 'KRFFI',
            'label' => 'LBL-KRW-' . $uniq,
            'sap_sent' => 1,
            'qc_status' => 0,
            'created_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $boxId = DB::table('production_scanned_data')->insertGetId([
            'summary_id' => $summaryId,
            'dic_id' => 1,
            'spk_code' => 'SPK-KRW-' . $uniq,
            'item_code' => 'ITEM-KRW-001',
            'quantity' => 100,
            'label' => 'BOX-KRW-' . $uniq,
            'warehouse' => 'KRFFI',
            'user' => 'Operator 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(QcTransferService::class);

        // Process inspection with 10 NG, 90 OK (isKbn = false)
        $result = $service->processSingleBoxInspection($boxId, 10, $user->id, 'Testing KRW', false);

        $this->assertTrue($result['success']);

        $log = QcTransferLog::where('scanned_data_id', $boxId)->first();
        $this->assertNotNull($log);
        $this->assertEquals(90, $log->ok_qty);
        $this->assertEquals(10, $log->ng_qty);

        // Verify Karawang rules: OK goes to KRFG, NG goes to KRRJCT
        $this->assertEquals('KRFG', $log->ok_to_warehouse);
        $this->assertEquals(1, $log->ok_sap_status);
        $this->assertEquals('KRRJCT', $log->ng_to_warehouse);
        $this->assertEquals(1, $log->ng_sap_status);
    }

    public function test_livewire_kbn_and_karawang_components_render()
    {
        $user = $this->getQualityUser();

        $this->actingAs($user);

        Livewire::test(QcStockTransfer::class)
            ->assertSet('plant', 'karawang')
            ->assertSee('QC Stock Transfer - Karawang');

        Livewire::test(QcStockTransferKbn::class)
            ->assertSet('plant', 'kbn')
            ->assertSee('QC Stock Transfer - KBN');
    }
}
