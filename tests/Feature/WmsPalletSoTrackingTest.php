<?php

namespace Tests\Feature;

use App\Livewire\Wms\PalletFormIndex;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use App\Models\ScannedData;
use App\Models\SoData;
use App\Models\User;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPalletLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class WmsPalletSoTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_out_pallet_renders_completed_delivery_status_and_customer_fallback(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = MasterCustomerDelivery::firstOrCreate(
            ['customer_code' => 'TVS-MTR'],
            ['customer_name' => 'TVS MOTOR COMPANY INDONESIA PT.']
        );

        $item = MasterListItem::firstOrCreate(
            ['item_code' => 'R1220910-TEST'],
            [
                'item_name' => 'COVER MAIN PIPE CENTER FRONT',
                'customer_code' => $customer->customer_code,
            ]
        );

        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-SO-001',
            'part_no' => $item->item_code,
            'model_name' => $item->item_name,
            'prod_date' => Carbon::now()->toDateString(),
            'plant' => 'KBN',
            'box_qty' => 0,
            'total_pallet_qty' => 0,
            'status' => 'OUT',
        ]);

        $detail = WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => $item->item_code,
            'model_name' => $item->item_name,
            'spk_no' => 'SPK-TEST-99',
            'qty' => 50,
            'warehouse' => 'FG',
            'label' => 'BOX-TEST-LBL-001',
        ]);

        // Soft delete detail as if scanned out for delivery
        $detail->delete();

        // Create SO Data and ScannedData
        $so = SoData::firstOrCreate(
            ['doc_num' => 'SO-2026-999'],
            [
                'customer' => $customer->customer_name,
                'posting_date' => Carbon::now()->toDateString(),
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'quantity' => 100,
                'sales_uom' => 'PCS',
                'packaging_quantity' => 10,
                'sales_pack' => 'BOX',
                'is_finish' => 1,
                'is_done' => 1,
                'create_date' => Carbon::now()->toDateString(),
                'update_date' => Carbon::now()->toDateString(),
                'update_fulltime' => 12345,
            ]
        );

        ScannedData::create([
            'doc_num' => $so->doc_num,
            'item_code' => $item->item_code,
            'spk_code' => 'SPK-TEST-99',
            'quantity' => 50,
            'warehouse' => 'FG',
            'label' => 'BOX-TEST-LBL-001',
        ]);

        WmsPalletLog::create([
            'pallet_id' => $pallet->pallet_id,
            'transaction_type' => 'OUT',
            'notes' => "Full Outbound via SO Scan (DocNum: {$so->doc_num}, Item: {$item->item_code}, Label: BOX-TEST-LBL-001, Qty: 50 pcs)",
        ]);

        $component = Livewire::test(PalletFormIndex::class)
            ->set('search', 'PLT-TEST-SO-001');

        // Verify view renders customer fallback and delivery status
        $component->assertSee('TVS MOTOR COMPANY INDONESIA PT.')
            ->assertSee('DIKIRIM')
            ->assertSee('SELESAI DIKIRIM (0 QTY)')
            ->assertSee('Detail SO');

        // Open SO Modal
        $component->call('openSoModal', $pallet->pallet_id);
        $component->assertSet('showSoModal', true);

        $soDeliveryData = $component->get('soDeliveryData');
        $this->assertEquals($pallet->pallet_id, $soDeliveryData['pallet_id']);
        $this->assertTrue($soDeliveryData['is_fully_shipped']);
        $this->assertEquals(50, $soDeliveryData['shipped_qty']);
        $this->assertCount(1, $soDeliveryData['so_groups']);
        $this->assertEquals('SO-2026-999', $soDeliveryData['so_groups'][0]['doc_num']);
        $this->assertEquals(50, $soDeliveryData['so_groups'][0]['pallet_shipped_qty']);
        $this->assertCount(1, $soDeliveryData['so_groups'][0]['boxes']);
        $this->assertEquals('BOX-TEST-LBL-001', $soDeliveryData['so_groups'][0]['boxes'][0]['label']);

        // Verify modal content renders in HTML
        $component->assertSee('SO: SO-2026-999')
            ->assertSee('BOX-TEST-LBL-001')
            ->assertSee('Detail Pengiriman Barang ke Sales Order (SO)');

        // Close modal
        $component->call('closeSoModal');
        $component->assertSet('showSoModal', false);
    }

    public function test_partially_shipped_temporary_pallet_shows_button_and_tracks_both_shipped_and_remaining_boxes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = MasterCustomerDelivery::firstOrCreate(
            ['customer_code' => 'TVS-MTR-2'],
            ['customer_name' => 'TVS MOTOR COMPANY INDONESIA PT.']
        );

        $item = MasterListItem::firstOrCreate(
            ['item_code' => 'PART-TEMP-001'],
            [
                'item_name' => 'TEMPORARY ITEM TEST',
                'customer_code' => $customer->customer_code,
            ]
        );

        // Pallet without rack position (TEMPORARY), with 1 remaining active box
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEMP-PARTIAL-001',
            'position_id' => null,
            'part_no' => $item->item_code,
            'model_name' => $item->item_name,
            'prod_date' => Carbon::now()->toDateString(),
            'plant' => 'KBN',
            'box_qty' => 1,
            'total_pallet_qty' => 25,
            'status' => 'STORED',
        ]);

        // Box 1: Shipped via SO
        $boxShipped = WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => $item->item_code,
            'model_name' => $item->item_name,
            'spk_no' => 'SPK-TEMP-01',
            'qty' => 25,
            'warehouse' => 'FG',
            'label' => 'BOX-SHIPPED-001',
        ]);
        $boxShipped->delete(); // Soft-deleted upon shipment

        // Box 2: Still active on the pallet
        $boxRemaining = WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => $item->item_code,
            'model_name' => $item->item_name,
            'spk_no' => 'SPK-TEMP-01',
            'qty' => 25,
            'warehouse' => 'FG',
            'label' => 'BOX-REMAINING-002',
        ]);

        $so = SoData::firstOrCreate(
            ['doc_num' => 'SO-TEMP-777'],
            [
                'customer' => $customer->customer_name,
                'posting_date' => Carbon::now()->toDateString(),
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'quantity' => 50,
                'sales_uom' => 'PCS',
                'packaging_quantity' => 25,
                'sales_pack' => 'BOX',
                'is_finish' => 0,
                'is_done' => 0,
                'create_date' => Carbon::now()->toDateString(),
                'update_date' => Carbon::now()->toDateString(),
                'update_fulltime' => 12345,
            ]
        );

        ScannedData::create([
            'doc_num' => $so->doc_num,
            'item_code' => $item->item_code,
            'spk_code' => 'SPK-TEMP-01',
            'quantity' => 25,
            'warehouse' => 'FG',
            'label' => 'BOX-SHIPPED-001',
        ]);

        $component = Livewire::test(PalletFormIndex::class)
            ->set('search', 'PLT-TEMP-PARTIAL-001');

        // Pallet is TEMPORARY, but partially shipped: should show TEMPORARY, Assign Slot, and Terkirim Sebagian (SO) button
        $component->assertSee('TEMPORARY (BELUM ADA TEMPAT)')
            ->assertSee('Assign Slot')
            ->assertSee('Terkirim Sebagian (SO)');

        // Open modal
        $component->call('openSoModal', $pallet->pallet_id);
        $component->assertSet('showSoModal', true);

        // Check data in modal
        $data = $component->get('soDeliveryData');
        $this->assertFalse($data['is_fully_shipped']);
        $this->assertEquals(25, $data['shipped_qty']);
        $this->assertEquals(25, $data['current_qty']);
        $this->assertCount(1, $data['active_boxes']);
        $this->assertEquals('BOX-REMAINING-002', $data['active_boxes'][0]['label']);

        // Check HTML renders both shipped SO section and remaining boxes section
        $component->assertSee('SO: SO-TEMP-777')
            ->assertSee('BOX-SHIPPED-001')
            ->assertSee('Sisa Box di Pallet (Belum Dikirim / Ready)')
            ->assertSee('BOX-REMAINING-002');
    }

    public function test_unshipped_pallet_does_not_match_so_even_if_box_label_exists_in_historical_scans_for_other_parts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = MasterCustomerDelivery::firstOrCreate(
            ['customer_code' => 'IKUYO-01'],
            ['customer_name' => 'IKUYO INDONESIA PT']
        );

        $itemPallet = MasterListItem::firstOrCreate(
            ['item_code' => '62040W060P-TEST'],
            [
                'item_name' => 'BRKT FR BMPR CTR ICE 4L45W 26MY',
                'customer_code' => $customer->customer_code,
            ]
        );

        $otherItem = MasterListItem::firstOrCreate(
            ['item_code' => '62040B000P-TEST'],
            [
                'item_name' => 'OTHER BUMPER PART',
                'customer_code' => $customer->customer_code,
            ]
        );

        // Pallet with 8 active boxes (NOT trashed / NOT shipped)
        $pallet = WmsPalletForm::create([
            'pallet_id' => 'PLT-TEST-UNSHIPPED-0070',
            'position_id' => null,
            'part_no' => $itemPallet->item_code,
            'model_name' => $itemPallet->item_name,
            'prod_date' => Carbon::now()->toDateString(),
            'plant' => 'KBN',
            'box_qty' => 8,
            'total_pallet_qty' => 16,
            'status' => 'STORED',
        ]);

        // Box with generic label '2202', still active in pallet
        WmsPalletFormDetail::create([
            'pallet_form_id' => $pallet->pallet_id,
            'part_no' => $itemPallet->item_code,
            'model_name' => $itemPallet->item_name,
            'spk_no' => 'SPK-CURRENT-01',
            'qty' => 2,
            'warehouse' => 'FG',
            'label' => '2202',
        ]);

        // In history, there was a scan with label '2202', BUT for $otherItem under another SO
        $soOld = SoData::firstOrCreate(
            ['doc_num' => 'SO-OLD-HISTORICAL-999'],
            [
                'customer' => $customer->customer_name,
                'posting_date' => Carbon::now()->subMonths(2)->toDateString(),
                'item_code' => $otherItem->item_code,
                'item_name' => $otherItem->item_name,
                'quantity' => 100,
                'sales_uom' => 'PCS',
                'packaging_quantity' => 2,
                'sales_pack' => 'BOX',
                'is_finish' => 1,
                'is_done' => 1,
                'create_date' => Carbon::now()->toDateString(),
                'update_date' => Carbon::now()->toDateString(),
                'update_fulltime' => 12345,
            ]
        );

        ScannedData::create([
            'doc_num' => $soOld->doc_num,
            'item_code' => $otherItem->item_code,
            'spk_code' => 'SPK-OTHER-OLD',
            'quantity' => 2,
            'warehouse' => 'FG',
            'label' => '2202',
        ]);

        $component = Livewire::test(PalletFormIndex::class)
            ->call('openSoModal', $pallet->pallet_id);

        $component->assertSet('showSoModal', true);

        $data = $component->get('soDeliveryData');
        $this->assertEquals(0, $data['shipped_qty']);
        $this->assertEquals(16, $data['current_qty']);
        // Must NOT match the historical scan from another part!
        $this->assertEmpty($data['so_groups']);

        // Assert clean UI rendering
        $component->assertSee('BELUM ADA PENGIRIMAN (READY 16 PCS)')
            ->assertSee('Belum ada barang yang dikirim dari palet ini.')
            ->assertDontSee('SO-OLD-HISTORICAL-999');
    }
}
