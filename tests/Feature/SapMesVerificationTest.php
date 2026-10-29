<?php

namespace Tests\Feature;

use App\Livewire\Sap\SapMesVerification;
use App\Models\Role;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Services\SapVerificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SapMesVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_parses_various_date_formats(): void
    {
        $service = new SapVerificationService();

        $this->assertEquals('2026-10-29', $service->parseSapDate('29.10.26'));
        $this->assertEquals('2026-09-23', $service->parseSapDate('23.09.26'));
        $this->assertEquals('2026-10-12', $service->parseSapDate('12.10.2026'));
        $this->assertEquals('2026-10-12', $service->parseSapDate('12/10/2026'));
        $this->assertEquals('2026-10-29', $service->parseSapDate('2026-10-29'));
        $this->assertNull($service->parseSapDate(''));
    }

    public function test_service_extracts_spk_and_pallet_from_remarks(): void
    {
        $service = new SapVerificationService();

        // SPK from Receipt Remarks (Gambar 1)
        $this->assertEquals('26030500', $service->extractSpkFromRemarks('Created from SPK 26030500'));
        $this->assertEquals('26030464', $service->extractSpkFromRemarks('Created from SPK 26030464'));
        $this->assertEquals('26026755', $service->extractSpkFromRemarks('Created from SPK 26026755'));
        $this->assertNull($service->extractSpkFromRemarks('General Inventory adjustment'));

        // Pallet ID from Inventory Transfer Remarks (Gambar 2)
        $this->assertEquals('PLT-20261012-0004', $service->extractPalletFromRemarks('Inventory Transfer created from PLT-20261012-0004'));
        $this->assertEquals('PLT-20261029-0001', $service->extractPalletFromRemarks('Inventory Transfer created from PLT-20261029-0001'));
        $this->assertEquals('PLT-20260923-0002', $service->extractPalletFromRemarks('Inventory Transfer created from PLT-20260923-0002'));
        $this->assertNull($service->extractPalletFromRemarks('Manual stock move'));
    }

    public function test_service_reconciles_receipt_data_properly(): void
    {
        $service = new SapVerificationService();

        // 1. SPK 26030500: Match (total 24 pcs in MES, 24 pcs in SAP)
        DB::table('production_summary')->insert([
            'spk_code' => '26030500',
            'total_quantity' => 24,
            'warehouse' => 'FFI',
            'created_date' => '2026-10-29',
            'sap_sent' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. SPK 26030464: Quantity Difference (total 50 pcs in MES, 40 pcs in SAP)
        DB::table('production_summary')->insert([
            'spk_code' => '26030464',
            'total_quantity' => 50,
            'warehouse' => 'FFI',
            'created_date' => '2026-10-29',
            'sap_sent' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. SPK 26099999: Missing in SAP (exists in MES for 2026-10-29, but not in SAP rows)
        DB::table('production_summary')->insert([
            'spk_code' => '26099999',
            'total_quantity' => 100,
            'warehouse' => 'FFI',
            'created_date' => '2026-10-29',
            'sap_sent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sapRows = [
            [
                'doc_num' => '26087583',
                'posting_date' => '2026-10-29',
                'spk_code' => '26030500',
                'item_code' => '272S0M660A',
                'description' => 'CASE ASSY-S/BOX LHD 5H45',
                'quantity' => 12.0,
                'warehouse' => 'FFI',
                'remarks' => 'Created from SPK 26030500',
            ],
            [
                'doc_num' => '26087586',
                'posting_date' => '2026-10-29',
                'spk_code' => '26030500',
                'item_code' => '272S0M660A',
                'description' => 'CASE ASSY-S/BOX LHD 5H45',
                'quantity' => 12.0,
                'warehouse' => 'FFI',
                'remarks' => 'Created from SPK 26030500',
            ],
            [
                'doc_num' => '26087584',
                'posting_date' => '2026-10-29',
                'spk_code' => '26030464',
                'item_code' => '273SYM650A',
                'description' => 'MOTOR FLANGE 5H45',
                'quantity' => 40.0,
                'warehouse' => 'FFI',
                'remarks' => 'Created from SPK 26030464',
            ],
            [
                'doc_num' => '26087999',
                'posting_date' => '2026-10-29',
                'spk_code' => '26077777',
                'item_code' => 'UNKNOWN_PART',
                'description' => 'ONLY IN SAP PART',
                'quantity' => 15.0,
                'warehouse' => 'FFI',
                'remarks' => 'Created from SPK 26077777',
            ],
        ];

        $result = $service->reconcileReceiptData($sapRows, '2026-10-29', '2026-10-29');

        $this->assertEquals(4, $result['kpi']['total_items']);
        $this->assertEquals(1, $result['kpi']['match_count']); // 26030500
        $this->assertEquals(1, $result['kpi']['diff_count']);  // 26030464
        $this->assertEquals(1, $result['kpi']['missing_count']); // 26099999
        $this->assertEquals(1, $result['kpi']['only_sap_count']); // 26077777
    }

    public function test_service_reconciles_pallet_data_properly(): void
    {
        $service = new SapVerificationService();

        // 1. PLT-20261012-0004: Match (total 448 in MES, 448 in SAP)
        WmsPalletForm::create([
            'pallet_id' => 'PLT-20261012-0004',
            'part_no' => '273SYM650A',
            'model_name' => 'MOTOR FLANGE 5H45',
            'total_pallet_qty' => 448,
            'box_qty' => 4,
            'prod_date' => '2026-10-12',
            'sap_sync_status' => 1,
        ]);

        // 2. PLT-20261029-0001: Missing in SAP
        WmsPalletForm::create([
            'pallet_id' => 'PLT-20261029-0001',
            'part_no' => '216PNM660A',
            'model_name' => 'COVER EVAP PIPE',
            'total_pallet_qty' => 300,
            'box_qty' => 2,
            'prod_date' => '2026-10-12',
            'sap_sync_status' => 0,
        ]);

        $sapRows = [
            [
                'doc_num' => '26009059',
                'posting_date' => '2026-10-12',
                'pallet_id' => 'PLT-20261012-0004',
                'item_code' => '273SYM650A',
                'description' => 'MOTOR FLANGE 5H45',
                'quantity' => 448.0,
                'from_whse' => 'FFI',
                'to_whse' => 'FG',
                'remarks' => 'Inventory Transfer created from PLT-20261012-0004',
            ],
        ];

        $result = $service->reconcilePalletData($sapRows, '2026-10-12', '2026-10-12');

        $this->assertEquals(2, $result['kpi']['total_items']);
        $this->assertEquals(1, $result['kpi']['match_count']);   // PLT-20261012-0004
        $this->assertEquals(1, $result['kpi']['missing_count']); // PLT-20261029-0001
    }

    public function test_service_saves_and_loads_batch_properly(): void
    {
        $service = new SapVerificationService();

        $reconciled = [
            'kpi' => [
                'total_items' => 1,
                'total_sap_pcs' => 100,
                'total_mes_pcs' => 100,
                'match_count' => 1,
                'diff_count' => 0,
                'missing_count' => 0,
                'only_sap_count' => 0,
            ],
            'comparison' => [
                [
                    'key' => '26030500',
                    'item_code' => '272S0M660A',
                    'description' => 'CASE ASSY',
                    'warehouse' => 'FFI',
                    'sap_qty' => 100.0,
                    'mes_qty' => 100.0,
                    'diff_qty' => 0.0,
                    'status' => 'MATCH',
                    'status_label' => '✓ Masuk SAP (Cocok)',
                    'sap_docs' => ['26087583'],
                    'sap_dates' => ['2026-10-29'],
                    'mes_dates' => ['2026-10-29'],
                    'sap_lines' => [],
                    'mes_lines' => [],
                ],
            ],
        ];

        $batch = $service->saveBatch(
            'RECEIPT',
            'test_receipt.xlsx',
            'sap_verification/test.xlsx',
            null,
            '2026-10-29',
            '2026-10-29',
            $reconciled
        );

        $this->assertDatabaseHas('sap_verification_batches', [
            'id' => $batch->id,
            'type' => 'RECEIPT',
            'file_name' => 'test_receipt.xlsx',
        ]);

        $this->assertDatabaseHas('sap_verification_items', [
            'batch_id' => $batch->id,
            'ref_code' => '26030500',
            'status' => 'MATCH',
        ]);

        // Load batch data back
        $loaded = $service->loadBatchData($batch->id);
        $this->assertNotNull($loaded);
        $this->assertEquals(1, count($loaded['comparison']));
        $this->assertEquals('26030500', $loaded['comparison'][0]['key']);
        $this->assertEquals(100.0, $loaded['comparison'][0]['sap_qty']);
    }

    public function test_livewire_component_renders_and_switches_tabs(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        Livewire::actingAs($user)
            ->test(SapMesVerification::class)
            ->assertSee('Verifikasi Data MES vs SAP')
            ->assertSee('Tab 1: Receipt from Production')
            ->assertSee('Tab 2: WMS Pallet Transfer')
            ->assertSet('activeTab', 'receipt')
            ->call('switchTab', 'pallet')
            ->assertSet('activeTab', 'pallet')
            ->assertSee('Upload File Excel SAP: Pallet Form / Inventory Transfer')
            ->call('switchTab', 'receipt')
            ->assertSet('activeTab', 'receipt');
    }
}
