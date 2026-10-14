<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\ScannedData;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class WmsPalletLookupAndSoScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_so_scan_does_not_delete_pallet_detail_with_different_part_no()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Pallet 1 for item '275PVM650A' with label '301'
        $palletA = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-001',
            'part_no' => '275PVM650A',
            'model_name' => 'CASE CTR 5H45',
            'prod_date' => '2026-09-08',
            'delivery_shift' => 1,
            'box_qty' => 1,
            'total_pallet_qty' => 12,
            'status' => 'STORED',
        ]);

        $detailA = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-TEST-001',
            'part_no' => '275PVM650A',
            'model_name' => 'CASE CTR 5H45',
            'spk_no' => '26023496',
            'qty' => 12,
            'warehouse' => 'FG',
            'label' => '301',
        ]);

        // Pallet 2 for item '8002C301.' with label '301'
        $palletB = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-002',
            'part_no' => '8002C301.',
            'model_name' => 'OTHER PART',
            'prod_date' => '2026-09-08',
            'delivery_shift' => 1,
            'box_qty' => 1,
            'total_pallet_qty' => 30,
            'status' => 'STORED',
        ]);

        $detailB = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-TEST-002',
            'part_no' => '8002C301.',
            'model_name' => 'OTHER PART',
            'spk_no' => '26023840',
            'qty' => 30,
            'warehouse' => 'FG',
            'label' => '301',
        ]);

        \App\Models\SpkItemHistory::create([
            'spk_number' => '26023840',
            'item_code' => '8002C301.',
        ]);

        \App\Models\SoData::create([
            'doc_num' => 'SO-1001',
            'customer' => 'TEST CUSTOMER',
            'item_code' => '8002C301.',
            'item_name' => 'OTHER PART',
            'quantity' => 100,
            'sales_uom' => 'PCS',
            'sales_pack' => 'BOX',
            'packaging_quantity' => 10,
            'posting_date' => '2026-09-08',
            'create_date' => '2026-09-08',
            'update_date' => '2026-09-08',
            'update_fulltime' => 20260908120000,
        ]);

        // Now SO scans part '8002C301.' with label '301'
        $response = $this->postJson(route('so.scanBarcode'), [
            'so_number' => 'SO-1001',
            'spk_code' => '26023840',
            'quantity' => 30,
            'warehouse' => 'FG',
            'label' => '301',
        ]);

        // Pallet A's detail ('275PVM650A') MUST NOT be deleted (not soft deleted)
        $this->assertNull($detailA->fresh()->deleted_at);
        $this->assertEquals(1, $palletA->fresh()->box_qty);
        $this->assertEquals('STORED', $palletA->fresh()->status);

        // Pallet B's detail ('8002C301.') MUST be soft deleted
        $this->assertNotNull($detailB->fresh()->deleted_at);
        $this->assertEquals(0, $palletB->fresh()->box_qty);
        $this->assertEquals('OUT', $palletB->fresh()->status);
    }

    public function test_artisan_command_restores_falsely_deleted_pallet_details()
    {
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-RESTORE-001',
            'part_no' => '275PVM650A',
            'model_name' => 'CASE CTR 5H45',
            'prod_date' => '2026-09-08',
            'delivery_shift' => 1,
            'box_qty' => 0,
            'total_pallet_qty' => 0,
            'status' => 'OUT',
        ]);

        $detail = WmsPalletFormDetail::create([
            'pallet_form_id' => 'PLT-RESTORE-001',
            'part_no' => '275PVM650A',
            'model_name' => 'CASE CTR 5H45',
            'spk_no' => '26023496',
            'qty' => 12,
            'warehouse' => 'FG',
            'label' => '301',
        ]);

        // Manually soft delete it (simulating the previous bug)
        $detail->delete();

        // Create an old historical scan from April 2026 with null spk_code (simulating legacy data)
        ScannedData::create([
            'doc_num' => '26006837',
            'item_code' => '275PVM650A',
            'spk_code' => null,
            'quantity' => 12,
            'warehouse' => 'FG',
            'label' => '301',
            'created_at' => '2026-04-16 02:30:22',
        ]);

        // Create another old scan for different SPK from May 2026
        ScannedData::create([
            'doc_num' => '26008154',
            'item_code' => '275PVM650A',
            'spk_code' => '26014376',
            'quantity' => 12,
            'warehouse' => 'FG',
            'label' => '301',
            'created_at' => '2026-05-05 03:58:21',
        ]);

        $this->assertNotNull($detail->fresh()->deleted_at);

        // Run the repair artisan command
        Artisan::call('wms:fix-pallet-details');

        // It MUST be restored because no scanned_data exists for current SPK '26023496' after detail creation
        $this->assertNull($detail->fresh()->deleted_at);
        $this->assertEquals(1, $pallet->fresh()->box_qty);
        $this->assertEquals(12, $pallet->fresh()->total_pallet_qty);
        $this->assertEquals('STORED', $pallet->fresh()->status);
    }

    public function test_manual_scan_processes_spk_quantity_warehouse_and_label_with_trimmed_inputs()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \App\Models\SoData::create([
            'doc_num' => 'SO-MANUAL-01',
            'customer' => 'CUSTOMER MANUAL',
            'item_code' => 'ITEM-MNL-001',
            'item_name' => 'MANUAL TEST ITEM',
            'quantity' => 50,
            'sales_uom' => 'PCS',
            'sales_pack' => 'BOX',
            'packaging_quantity' => 10,
            'posting_date' => '2026-10-14',
            'create_date' => '2026-10-14',
            'update_date' => '2026-10-14',
            'update_fulltime' => 20261014120000,
        ]);

        \App\Models\SpkItemHistory::create([
            'spk_number' => 'SPK-MNL-101',
            'item_code' => 'ITEM-MNL-001',
        ]);

        $response = $this->postJson(route('so.scanBarcode'), [
            'so_number' => 'SO-MANUAL-01',
            'spk_code' => '  SPK-MNL-101  ',
            'quantity' => 10,
            'warehouse' => '  GUA  ',
            'label' => '  BOX-001  ',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('scanned_data', [
            'doc_num' => 'SO-MANUAL-01',
            'item_code' => 'ITEM-MNL-001',
            'spk_code' => 'SPK-MNL-101',
            'quantity' => 10,
            'warehouse' => 'GUA',
            'label' => 'BOX-001',
        ]);
    }

    public function test_manual_scan_resolves_spk_from_spk_master()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \App\Models\SoData::create([
            'doc_num' => 'SO-MANUAL-02',
            'customer' => 'CUSTOMER MANUAL 2',
            'item_code' => 'ITEM-MNL-002',
            'item_name' => 'MANUAL TEST ITEM 2',
            'quantity' => 100,
            'sales_uom' => 'PCS',
            'sales_pack' => 'BOX',
            'packaging_quantity' => 20,
            'posting_date' => '2026-10-14',
            'create_date' => '2026-10-14',
            'update_date' => '2026-10-14',
            'update_fulltime' => 20261014120000,
        ]);

        \App\Models\SpkMaster::create([
            'spk_number' => 'SPK-FROM-MASTER',
            'post_date' => '2026-10-14',
            'due_date' => '2026-10-14',
            'production_status' => 'Released',
            'item_code' => 'ITEM-MNL-002',
            'planned_quantity' => 100,
            'completed_quantity' => 100,
            'warehouse' => 'GUB',
        ]);

        $response = $this->postJson(route('so.scanBarcode'), [
            'so_number' => 'SO-MANUAL-02',
            'spk_code' => 'SPK-FROM-MASTER',
            'quantity' => 20,
            'warehouse' => 'GUB',
            'label' => 'BOX-MST-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('scanned_data', [
            'doc_num' => 'SO-MANUAL-02',
            'item_code' => 'ITEM-MNL-002',
            'spk_code' => 'SPK-FROM-MASTER',
            'quantity' => 20,
            'warehouse' => 'GUB',
            'label' => 'BOX-MST-01',
        ]);
    }
}
