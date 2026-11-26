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
            ->assertSee('Detail Pengiriman dan Sales Order (SO)');

        // Close modal
        $component->call('closeSoModal');
        $component->assertSet('showSoModal', false);
    }
}
