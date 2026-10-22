<?php

namespace Tests\Feature;

use App\Models\BarcodePackagingDetail;
use App\Models\BarcodePackagingMaster;
use App\Models\Customer;
use App\Models\StoreBoxData;
use App\Models\StoreBoxDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagingMenuRevampTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_packaging_dashboard_loads_successfully_with_kpi_and_zero_error(): void
    {
        StoreBoxData::create(['part_no' => 'BOX-TEST-001', 'part_name' => 'Test Box 1']);
        StoreBoxData::create(['part_no' => 'BOX-TEST-002', 'part_name' => 'Test Box 2']);

        $response = $this->actingAs($this->user)->get(route('summaryDashboard'));

        $response->assertOk();
        $response->assertViewIs('barcode.store_summary');
        $response->assertViewHasAll(['summaryData', 'customerBalances', 'kpi', 'latestPositions']);
    }

    public function test_stockall_loads_successfully_without_broken_html(): void
    {
        $response = $this->actingAs($this->user)->get(route('stockallbarcode', ['location' => 'Jakarta']));

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.stockallbarcode');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_latest_item_details_paginates_properly(): void
    {
        StoreBoxData::create(['part_no' => 'BOX-TEST-001', 'part_name' => 'Test Box 1']);

        $response = $this->actingAs($this->user)->get(route('updated.barcode.item.position'));

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.latestbarcodeitem');
        $response->assertViewHas('sortedItems');
    }

    public function test_inandout_page_cleans_drafts_and_loads_customers(): void
    {
        Customer::create(['name' => 'PT YANFENG AUTOMOTIVE']);

        $response = $this->actingAs($this->user)->get(route('inandout.index'));

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.inandoutpage');
        $response->assertSee('PT YANFENG AUTOMOTIVE');
    }

    public function test_barcode_index_loads_with_recent_labels(): void
    {
        StoreBoxData::create(['part_no' => 'BOX-TEST-001', 'part_name' => 'Test Box 1']);

        $response = $this->actingAs($this->user)->get(route('barcodeindex'));

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.indexbarcode');
        $response->assertViewHasAll(['datas', 'recentLabels']);
    }

    public function test_generate_barcode_creates_base64_data_uri_without_disk_error(): void
    {
        $response = $this->actingAs($this->user)->post(route('generatepackagingbarcode'), [
            'partNo' => 'BOX-TEST-001',
            'print_quantity' => 2,
            'remark' => 'Testing zero-disk generation',
        ]);

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.barcode');
        $response->assertViewHas('barcodes');

        $barcodes = $response->viewData('barcodes');
        $this->assertCount(2, $barcodes);
        $this->assertStringStartsWith('data:image/png;base64,', $barcodes[0]['barcodeUrl']);

        $this->assertDatabaseHas('store_box_details', [
            'part_no' => 'BOX-TEST-001',
            'label' => 1,
            'status' => 'active',
        ]);
    }

    public function test_generate_zebra_barcode_creates_base64_data_uri(): void
    {
        $response = $this->actingAs($this->user)->post(route('generate.zebra.barcode'), [
            'partNo' => 'BOX-TEST-001',
            'print_quantity' => 1,
        ]);

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.zebra_label');
        $response->assertViewHas('barcodes');

        $barcodes = $response->viewData('barcodes');
        $this->assertCount(1, $barcodes);
        $this->assertStringStartsWith('data:image/png;base64,', $barcodes[0]['barcodeUrl']);
    }

    public function test_label_history_api_returns_json(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.barcode.label_history', [
            'partNo' => 'BOX-TEST-001',
            'label' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_store_box_master_and_detail_views_work_cleanly(): void
    {
        $box = StoreBoxData::create(['part_no' => 'BOX-CRUD-001', 'part_name' => 'CRUD Test Box']);
        StoreBoxDetail::create(['part_no' => 'BOX-CRUD-001', 'label' => 1, 'status' => 'active']);

        // Box Master Index
        $resMaster = $this->actingAs($this->user)->get(route('barcode.box_master.index'));
        $resMaster->assertOk();
        $resMaster->assertSee('BOX-CRUD-001');

        // Box Detail Index
        $resDetail = $this->actingAs($this->user)->get(route('barcode.box_detail.index'));
        $resDetail->assertOk();
        $resDetail->assertSee('BOX-CRUD-001');
    }

    public function test_generate_zebra_40x15_barcode_creates_twin_labels(): void
    {
        $response = $this->actingAs($this->user)->post(route('generate.zebra.barcode40x15'), [
            'partNo' => 'BOX-TWIN-001/Twin Box Name',
            'print_quantity' => 2,
            'remark' => 'Testing 40x15 twin label',
        ]);

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.zebra_label_40x15');
        $response->assertViewHas('barcodes');

        $barcodes = $response->viewData('barcodes');
        $this->assertCount(2, $barcodes);
        $this->assertStringStartsWith('data:image/png;base64,', $barcodes[0]['barcodeUrl']);
        $this->assertEquals('BOX-TWIN-001', $barcodes[0]['partno']);
        $this->assertEquals('Twin Box Name', $barcodes[0]['partname']);

        // Check that view contains 40x15 styling & labels
        $response->assertSee('84mm 16mm');
        $response->assertSee('BOX-TWIN-001');
        $response->assertSee('#1');
        $response->assertSee('#2');

        $this->assertDatabaseHas('store_box_details', [
            'part_no' => 'BOX-TWIN-001',
            'label' => 1,
            'status' => 'active',
            'remark' => 'Testing 40x15 twin label',
        ]);
        $this->assertDatabaseHas('store_box_details', [
            'part_no' => 'BOX-TWIN-001',
            'label' => 2,
            'status' => 'active',
        ]);
    }

    public function test_reprint_zebra_40x15_format_works(): void
    {
        $box = StoreBoxData::create(['part_no' => 'BOX-REPRINT-001', 'part_name' => 'Reprint Box']);
        $detail = StoreBoxDetail::create(['part_no' => 'BOX-REPRINT-001', 'label' => 1, 'status' => 'active']);

        $response = $this->actingAs($this->user)->post(route('barcode.reprint'), [
            'ids' => [$detail->id],
            'format' => 'zebra40x15',
        ]);

        $response->assertOk();
        $response->assertViewIs('barcodeinandout.zebra_label_40x15');
        $response->assertViewHas('barcodes');
        $response->assertSee('BOX-REPRINT-001');
        $response->assertSee('#1');
    }
}
