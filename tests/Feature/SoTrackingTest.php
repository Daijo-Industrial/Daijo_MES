<?php

namespace Tests\Feature;

use App\Livewire\Store\SoTracking;
use App\Models\ScannedData;
use App\Models\SoData;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SoTrackingTest extends TestCase
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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
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

        Schema::create('scanned_data', function (Blueprint $table) {
            $table->id();
            $table->string('doc_num');
            $table->string('item_code');
            $table->string('spk_code')->nullable();
            $table->integer('quantity');
            $table->string('warehouse')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('so_datas', function (Blueprint $table) {
            $table->id();
            $table->string('doc_num');
            $table->string('customer')->nullable();
            $table->date('posting_date')->nullable();
            $table->string('item_code');
            $table->string('item_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('sales_uom')->nullable();
            $table->integer('packaging_quantity')->default(0);
            $table->string('sales_pack')->nullable();
            $table->boolean('is_finish')->default(false);
            $table->boolean('is_done')->default(false);
            $table->dateTime('create_date')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('update_fulltime')->nullable();
        });
    }

    public function test_can_render_so_tracking_page(): void
    {
        $role = \App\Models\Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name'     => 'Store Admin',
            'email'    => 'store@daijo.co.id',
            'password' => bcrypt('password'),
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('so.tracking'))
            ->assertStatus(200)
            ->assertSee('Tracking SPK & Label DO');
    }

    public function test_can_search_by_spk_code_and_locate_do(): void
    {
        // 1. Setup SO Header
        SoData::create([
            'doc_num'      => 'DO-2026-001',
            'customer'     => 'PT YAMAHA INDONESIA',
            'posting_date' => '2026-10-15',
            'item_code'    => 'PART-YMH-01',
            'item_name'    => 'COVER HANDLE FRONT',
            'quantity'     => 100,
            'is_done'      => 1,
        ]);

        // 2. Setup Scanned Box
        ScannedData::create([
            'doc_num'    => 'DO-2026-001',
            'item_code'  => 'PART-YMH-01',
            'spk_code'   => 'SPK-998877',
            'label'      => 'BOX-LBL-001',
            'quantity'   => 25,
            'warehouse'  => 'FG',
            'created_at' => Carbon::parse('2026-10-15 08:30:00'),
        ]);

        // Box on another DO
        ScannedData::create([
            'doc_num'    => 'DO-2026-002',
            'item_code'  => 'PART-HND-02',
            'spk_code'   => 'SPK-112233',
            'label'      => 'BOX-LBL-002',
            'quantity'   => 50,
            'warehouse'  => 'FG',
            'created_at' => Carbon::parse('2026-10-15 09:00:00'),
        ]);

        Livewire::test(SoTracking::class)
            ->set('search', 'SPK-998877')
            ->assertSee('DO-2026-001')
            ->assertSee('PT YAMAHA INDONESIA')
            ->assertSee('BOX-LBL-001')
            ->assertDontSee('DO-2026-002');
    }

    public function test_can_search_by_label_box(): void
    {
        SoData::create([
            'doc_num'      => 'DO-HONDA-88',
            'customer'     => 'PT AHM HONDA',
            'posting_date' => '2026-10-14',
            'item_code'    => 'PART-HND-X',
            'item_name'    => 'FENDER REAR AHM',
            'quantity'     => 200,
            'is_done'      => 0,
        ]);

        ScannedData::create([
            'doc_num'    => 'DO-HONDA-88',
            'item_code'  => 'PART-HND-X',
            'spk_code'   => 'SPK-AHM-4455',
            'label'      => 'BOX-SPECIAL-999',
            'quantity'   => 20,
            'warehouse'  => 'FG',
            'created_at' => Carbon::parse('2026-10-14 10:00:00'),
        ]);

        Livewire::test(SoTracking::class)
            ->set('labelSearch', 'BOX-SPECIAL-999')
            ->assertSee('DO-HONDA-88')
            ->assertSee('PT AHM HONDA')
            ->assertSee('SPK-AHM-4455')
            ->assertSee('BOX-SPECIAL-999');
    }

    public function test_can_input_spk_and_label_manually_and_pinpoint_target_do(): void
    {
        SoData::create([
            'doc_num'      => 'DO-TARGET-102458',
            'customer'     => 'PT YAMAHA INDONESIA MOTOR',
            'posting_date' => '2026-10-15',
            'item_code'    => '275PVM650A',
            'item_name'    => 'CASE CTR 5H45',
            'quantity'     => 100,
            'is_done'      => 1,
        ]);

        ScannedData::create([
            'doc_num'    => 'DO-TARGET-102458',
            'item_code'  => '275PVM650A',
            'spk_code'   => '26023496',
            'label'      => '301',
            'quantity'   => 12,
            'warehouse'  => 'FG',
            'created_at' => Carbon::parse('2026-10-15 09:14:00'),
        ]);

        Livewire::test(SoTracking::class)
            ->set('spkSearch', '26023496')
            ->set('labelSearch', '301')
            ->call('searchBySpkAndLabel')
            ->assertSee('DOKUMEN DO YANG MEMUAT SPK / BOX INI')
            ->assertSee('DO-TARGET-102458')
            ->assertSee('PT YAMAHA INDONESIA MOTOR')
            ->assertSee('275PVM650A')
            ->assertSee('CASE CTR 5H45')
            ->assertSee('12 pcs');
    }

    public function test_can_process_tab_separated_barcode_scan_input(): void
    {
        ScannedData::create([
            'doc_num'    => 'DO-SUZUKI-01',
            'item_code'  => 'SZK-PART-12',
            'spk_code'   => 'SPK-SZK-8800',
            'label'      => 'LBL-SZK-55',
            'quantity'   => 30,
            'warehouse'  => 'FG',
            'created_at' => now(),
        ]);

        // Simulating physical QR scan with tab delimiter: PartNo \t SPK \t Qty \t Label
        $rawScan = "SZK-PART-12\tSPK-SZK-8800\t30\tLBL-SZK-55";

        Livewire::test(SoTracking::class)
            ->set('barcodeScanInput', $rawScan)
            ->call('processBarcodeScan')
            ->assertSet('itemCodeSearch', 'SZK-PART-12')
            ->assertSet('spkSearch', 'SPK-SZK-8800')
            ->assertSet('labelSearch', 'LBL-SZK-55')
            ->assertSee('DO-SUZUKI-01');
    }

    public function test_can_open_and_view_do_detail_modal(): void
    {
        SoData::create([
            'doc_num'      => 'DO-MODAL-TEST',
            'customer'     => 'PT SHAD TOP CASE',
            'posting_date' => '2026-10-15',
            'item_code'    => 'D0B29100',
            'item_name'    => 'BASE PLATE SHAD',
            'quantity'     => 100,
            'is_done'      => 1,
        ]);

        ScannedData::create([
            'doc_num'    => 'DO-MODAL-TEST',
            'item_code'  => 'D0B29100',
            'spk_code'   => 'SPK-SHAD-01',
            'label'      => 'SHAD-BX-01',
            'quantity'   => 100,
            'warehouse'  => 'FG',
            'created_at' => now(),
        ]);

        Livewire::test(SoTracking::class)
            ->call('openDocDetail', 'DO-MODAL-TEST')
            ->assertSet('showDocDetailModal', true)
            ->assertSet('selectedDocNum', 'DO-MODAL-TEST')
            ->assertSee('PT SHAD TOP CASE')
            ->assertSee('BASE PLATE SHAD')
            ->call('closeDocDetail')
            ->assertSet('showDocDetailModal', false);
    }

    public function test_can_export_csv(): void
    {
        ScannedData::create([
            'doc_num'    => 'DO-EXP-01',
            'item_code'  => 'EXP-ITEM-01',
            'spk_code'   => 'SPK-EXP-01',
            'label'      => 'EXP-LBL-01',
            'quantity'   => 15,
            'warehouse'  => 'FG',
            'created_at' => now(),
        ]);

        Livewire::test(SoTracking::class)
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_kpi_stats_query_does_not_contain_non_aggregated_columns_in_select(): void
    {
        $component = new SoTracking();
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('buildFilteredQuery');
        $method->setAccessible(true);

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $method->invoke($component);
        $statsQuery = $query->selectRaw('
            COUNT(*) as total_boxes,
            COALESCE(SUM(scanned_data.quantity), 0) as total_qty,
            COUNT(DISTINCT scanned_data.spk_code) as unique_spk_count,
            COUNT(DISTINCT scanned_data.doc_num) as unique_do_count
        ');

        $sql = $statsQuery->toSql();

        // Must NOT select non-aggregate columns like scanned_data.id or scanned_data.doc_num directly before aggregates
        $this->assertStringNotContainsString('select "scanned_data"."id"', strtolower($sql));
        $this->assertStringNotContainsString('select `scanned_data`.`id`', strtolower($sql));
        $this->assertStringContainsString('count(*)', strtolower($sql));
    }

    public function test_page_renders_standby_state_without_running_query_when_no_search_entered(): void
    {
        Livewire::test(SoTracking::class)
            ->assertSee('Menunggu Input Nomor SPK / Label Box')
            ->assertViewHas('hasActiveSearch', false)
            ->assertViewHas('records', null);
    }

    public function test_matching_dos_supports_pagination_when_spk_spans_multiple_dos(): void
    {
        // Setup 8 distinct DO documents for the same SPK
        for ($i = 1; $i <= 8; $i++) {
            $doc = "DO-MULTI-00{$i}";
            SoData::create([
                'doc_num'      => $doc,
                'customer'     => 'PT MULTI CUSTOMER',
                'posting_date' => '2026-10-15',
                'item_code'    => 'MULTI-PART-01',
                'item_name'    => 'PART MULTI',
                'quantity'     => 10,
                'is_done'      => 1,
            ]);

            ScannedData::create([
                'doc_num'    => $doc,
                'item_code'  => 'MULTI-PART-01',
                'spk_code'   => 'SPK-MULTI-999',
                'label'      => "LBL-00{$i}",
                'quantity'   => 10,
                'warehouse'  => 'FG',
                'created_at' => Carbon::parse("2026-10-15 08:0{$i}:00"),
            ]);
        }

        Livewire::test(SoTracking::class)
            ->set('spkSearch', 'SPK-MULTI-999')
            ->call('searchBySpkAndLabel')
            ->assertViewHas('matchingDos', function ($dos) {
                return $dos->total() === 8 
                    && $dos->count() === 6
                    && $dos->currentPage() === 1
                    && $dos->pluck('doc_num')->contains('DO-MULTI-008')
                    && !$dos->pluck('doc_num')->contains('DO-MULTI-001');
            })
            ->call('nextPage', 'doPage')
            ->assertViewHas('matchingDos', function ($dos) {
                return $dos->total() === 8 
                    && $dos->count() === 2
                    && $dos->currentPage() === 2
                    && $dos->pluck('doc_num')->contains('DO-MULTI-001');
            });
    }
}



