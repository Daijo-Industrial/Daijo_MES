<?php

namespace Tests\Feature;

use App\Livewire\PoReminderIndex;
use App\Models\MasterBom;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use App\Services\PoReminderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PoReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function ($table) {
                $table->timestamp('last_activity_at')->nullable();
            });
        }

        if (! Schema::hasTable('sap_delsched')) {
            Schema::create('sap_delsched', function (Blueprint $table) {
                $table->string('item_code');
                $table->date('delivery_date');
                $table->integer('delivery_qty');
                $table->string('so_number')->nullable();
            });
        }

        if (! Schema::hasTable('sap_inventory_fg')) {
            Schema::create('sap_inventory_fg', function (Blueprint $table) {
                $table->string('item_code')->primary();
                $table->string('item_name')->nullable();
                $table->integer('stock')->default(0);
            });
        }

        if (! Schema::hasTable('master_boms')) {
            Schema::create('master_boms', function (Blueprint $table) {
                $table->id();
                $table->string('sap_line_id')->nullable();
                $table->string('parent_item');
                $table->string('parent_description')->nullable();
                $table->string('component_item');
                $table->string('component_description')->nullable();
                $table->decimal('quantity', 16, 6)->default(1);
                $table->string('uom')->default('PCS');
                $table->string('family')->nullable();
                $table->string('family_2')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_list_items')) {
            Schema::create('master_list_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_code')->unique();
                $table->string('item_name')->nullable();
                $table->string('customer_code')->nullable();
                $table->string('tipe_mesin')->nullable();
                $table->integer('standart_packaging_list')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('spk_masters')) {
            Schema::create('spk_masters', function (Blueprint $table) {
                $table->id();
                $table->string('spk_number')->unique();
                $table->date('post_date')->nullable();
                $table->date('due_date')->nullable();
                $table->string('production_status')->default('R');
                $table->string('item_code');
                $table->integer('planned_quantity')->default(0);
                $table->integer('completed_quantity')->default(0);
                $table->string('warehouse')->nullable();
            });
        }

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'User MES Testing',
        ]);
    }

    public function test_service_calculates_gross_and_net_material_requirements(): void
    {
        // 1. Setup Finish Good Item 1: Part-A
        // Delivery: 2 schedule rows in date range (100 + 150 = 250 pcs), 1 row out of range (50 pcs)
        DB::table('sap_delsched')->insert([
            ['item_code' => 'PART-A', 'delivery_date' => '2026-11-05', 'delivery_qty' => 100, 'so_number' => 'SO-01'],
            ['item_code' => 'PART-A', 'delivery_date' => '2026-11-20', 'delivery_qty' => 150, 'so_number' => 'SO-02'],
            ['item_code' => 'PART-A', 'delivery_date' => '2026-12-10', 'delivery_qty' => 50, 'so_number' => 'SO-03'], // Outside range
        ]);

        // Stock FG Part-A = 50 pcs (tidak boleh dijumlahkan per schedule row!)
        DB::table('sap_inventory_fg')->insert([
            'item_code' => 'PART-A',
            'item_name' => 'Part A Automotive Cover',
            'stock' => 50,
        ]);

        // BOM for Part-A:
        // - Resin: 401-ABS-BLACK (unit: 0.5 KG)
        // - Packaging: 600-BOX-01 (unit: 0.05 PCS)
        MasterBom::create([
            'parent_item' => 'PART-A',
            'component_item' => '401-ABS-BLACK',
            'component_description' => 'ABS RESIN BLACK',
            'quantity' => 0.5,
            'uom' => 'KG',
            'is_active' => true,
        ]);
        MasterBom::create([
            'parent_item' => 'PART-A',
            'component_item' => '600-BOX-01',
            'component_description' => 'BOX KARTON 50x50',
            'quantity' => 0.05,
            'uom' => 'PCS',
            'is_active' => true,
        ]);

        // 2. Setup Finish Good Item 2: Part-B
        // Delivery: 100 pcs, Stock = 120 pcs (Stok FG cukup -> Net Deficit = 0)
        DB::table('sap_delsched')->insert([
            ['item_code' => 'PART-B', 'delivery_date' => '2026-11-15', 'delivery_qty' => 100, 'so_number' => 'SO-04'],
        ]);
        DB::table('sap_inventory_fg')->insert([
            'item_code' => 'PART-B',
            'item_name' => 'Part B Bezel',
            'stock' => 120,
        ]);
        MasterBom::create([
            'parent_item' => 'PART-B',
            'component_item' => '401-ABS-BLACK', // Shared resin with Part-A
            'component_description' => 'ABS RESIN BLACK',
            'quantity' => 0.2,
            'uom' => 'KG',
            'is_active' => true,
        ]);

        $service = app(PoReminderService::class);
        $result = $service->calculate('2026-11-01', '2026-11-30');

        $this->assertTrue($result['has_data']);
        $this->assertEquals(2, $result['kpi']['total_fg_items']);
        $this->assertEquals(350, $result['kpi']['total_delivery_qty']); // 250 (Part-A) + 100 (Part-B)
        $this->assertEquals(170, $result['kpi']['total_stock_fg']); // 50 + 120
        $this->assertEquals(200, $result['kpi']['total_net_deficit']); // Part-A: 250 - 50 = 200, Part-B: max(0, 100-120) = 0

        // ── VERIFIKASI VIEW 1 (GROSS REQUIREMENT) ──
        // Mengabaikan stok FG:
        // Part-A target = 250 -> 401-ABS-BLACK = 250 * 0.5 = 125 KG, 600-BOX-01 = 250 * 0.05 = 12.5 PCS
        // Part-B target = 100 -> 401-ABS-BLACK = 100 * 0.2 = 20 KG
        // Total 401-ABS-BLACK (Gross) = 125 + 20 = 145 KG
        $grossResin = collect($result['view1_materials'])->firstWhere('item_code', '401-ABS-BLACK');
        $this->assertNotNull($grossResin);
        $this->assertEquals(145.0, $grossResin['total_needed_qty']);
        $this->assertEquals(2, $grossResin['fgs_count']);
        // Verifikasi timeline dan jadwal delivery per tanggal
        $this->assertNotEmpty($grossResin['daily_timeline']);
        $this->assertCount(3, $grossResin['daily_timeline']); // 2026-11-05 (Part-A), 2026-11-15 (Part-B), 2026-11-20 (Part-A)
        $this->assertNotEmpty($grossResin['used_by_fgs'][0]['schedules']);

        $grossBox = collect($result['view1_materials'])->firstWhere('item_code', '600-BOX-01');
        $this->assertNotNull($grossBox);
        $this->assertEquals(12.5, $grossBox['total_needed_qty']);
        $this->assertNotEmpty($grossBox['daily_timeline']);

        // ── VERIFIKASI VIEW 2 (NET DEFICIT REQUIREMENT) ──
        // Mengikutsertakan stok FG:
        // Part-A deficit = 200 -> 401-ABS-BLACK = 200 * 0.5 = 100 KG, 600-BOX-01 = 200 * 0.05 = 10 PCS
        // Part-B deficit = 0 (stok 120 >= delivery 100) -> 0 material dibutuhkan
        // Total 401-ABS-BLACK (Net) = 100 KG
        $netResin = collect($result['view2_materials'])->firstWhere('item_code', '401-ABS-BLACK');
        $this->assertNotNull($netResin);
        $this->assertEquals(100.0, $netResin['total_needed_qty']);

        $netBox = collect($result['view2_materials'])->firstWhere('item_code', '600-BOX-01');
        $this->assertNotNull($netBox);
        $this->assertEquals(10.0, $netBox['total_needed_qty']);
    }

    public function test_service_handles_multi_level_wip_bom_explosion(): void
    {
        // FG: ASSY-01 (Delivery: 10)
        // -> WIP-01 (unit: 1)
        //    -> RESIN-01 (unit: 2 KG)
        // -> SCREW-01 (unit: 4 PCS)
        DB::table('sap_delsched')->insert([
            ['item_code' => 'ASSY-01', 'delivery_date' => '2026-11-10', 'delivery_qty' => 10, 'so_number' => 'SO-WIP'],
        ]);

        DB::table('sap_inventory_fg')->insert([
            'item_code' => 'ASSY-01',
            'item_name' => 'Assembly Finished Good',
            'stock' => 0,
        ]);

        // Level 1: ASSY-01 -> WIP-01 & SCREW-01
        MasterBom::create([
            'parent_item' => 'ASSY-01',
            'component_item' => 'WIP-01',
            'component_description' => 'Sub-Assembly Frame',
            'quantity' => 1,
            'uom' => 'PCS',
            'is_active' => true,
        ]);
        MasterBom::create([
            'parent_item' => 'ASSY-01',
            'component_item' => '300-SCREW-M3',
            'component_description' => 'SCREW M3x10',
            'quantity' => 4,
            'uom' => 'PCS',
            'is_active' => true,
        ]);

        // Level 2: WIP-01 -> RESIN-01
        MasterBom::create([
            'parent_item' => 'WIP-01',
            'component_item' => '400-RESIN-PP',
            'component_description' => 'PP RESIN NATURAL',
            'quantity' => 2.5,
            'uom' => 'KG',
            'is_active' => true,
        ]);

        $service = app(PoReminderService::class);
        $result = $service->calculate('2026-11-01', '2026-11-30');

        // Kebutuhan material terbawah:
        // RESIN-PP: 10 * 1 * 2.5 = 25 KG
        // SCREW-M3: 10 * 4 = 40 PCS
        $resin = collect($result['view1_materials'])->firstWhere('item_code', '400-RESIN-PP');
        $this->assertNotNull($resin);
        $this->assertEquals(25.0, $resin['total_needed_qty']);

        $screw = collect($result['view1_materials'])->firstWhere('item_code', '300-SCREW-M3');
        $this->assertNotNull($screw);
        $this->assertEquals(40.0, $screw['total_needed_qty']);
    }

    public function test_livewire_po_reminder_page_renders_and_interacts(): void
    {
        DB::table('sap_delsched')->insert([
            ['item_code' => 'FG-SAMPLE', 'delivery_date' => '2026-11-12', 'delivery_qty' => 500, 'so_number' => 'SO-99'],
        ]);

        DB::table('sap_inventory_fg')->insert([
            'item_code' => 'FG-SAMPLE',
            'item_name' => 'Sample Product FG',
            'stock' => 100,
        ]);

        MasterBom::create([
            'parent_item' => 'FG-SAMPLE',
            'component_item' => '401-ABS-TEST',
            'component_description' => 'ABS RAW RESIN',
            'quantity' => 1.2,
            'uom' => 'KG',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PoReminderIndex::class)
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2026-11-30')
            ->assertSee('PO Reminder (Kebutuhan Material Pengiriman)', false)
            ->assertSee('FG-SAMPLE')
            ->assertSee('401-ABS-TEST')
            ->set('activeView', 'gross')
            ->assertSet('activeView', 'gross')
            ->set('displayMode', 'fgs')
            ->assertSet('displayMode', 'fgs')
            ->call('showMaterialDetail', '401-ABS-TEST')
            ->assertSet('activeMaterialDetail.item_code', '401-ABS-TEST')
            ->assertSee('Timeline per Tanggal Delivery')
            ->assertSee('Rincian per Finish Good (FG)')
            ->call('closeMaterialDetail')
            ->assertSet('activeMaterialDetail', null);
    }

    public function test_user_can_export_po_reminder_to_excel(): void
    {
        DB::table('sap_delsched')->insert([
            ['item_code' => 'FG-EXCEL-01', 'delivery_date' => '2026-11-18', 'delivery_qty' => 200, 'so_number' => 'SO-EXCEL'],
        ]);

        DB::table('sap_inventory_fg')->insert([
            'item_code' => 'FG-EXCEL-01',
            'item_name' => 'Excel Export Sample FG',
            'stock' => 50,
        ]);

        MasterBom::create([
            'parent_item' => 'FG-EXCEL-01',
            'component_item' => '401-RESIN-EXCEL',
            'component_description' => 'EXCEL RESIN',
            'quantity' => 0.8,
            'uom' => 'KG',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PoReminderIndex::class)
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2026-11-30')
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_service_maps_spk_availability_and_links_to_spk_changes(): void
    {
        // 1. Setup Finish Good 1 with 2 SPKs (1 Released, 1 Planned)
        DB::table('sap_delsched')->insert([
            ['item_code' => 'FG-SPK-01', 'delivery_date' => '2026-11-10', 'delivery_qty' => 1000, 'so_number' => 'SO-SPK-01'],
            ['item_code' => 'FG-NO-SPK', 'delivery_date' => '2026-11-10', 'delivery_qty' => 500, 'so_number' => 'SO-SPK-02'],
        ]);

        MasterBom::create([
            'parent_item' => 'FG-SPK-01',
            'component_item' => '401-SHARED-WIP',
            'component_description' => 'SHARED WIP MATERIAL',
            'quantity' => 1,
            'uom' => 'PCS',
            'is_active' => true,
        ]);
        MasterBom::create([
            'parent_item' => 'FG-NO-SPK',
            'component_item' => '401-SHARED-WIP',
            'component_description' => 'SHARED WIP MATERIAL',
            'quantity' => 2,
            'uom' => 'PCS',
            'is_active' => true,
        ]);

        // Insert SPKs for FG-SPK-01:
        // SPK 1: planned 400, completed 100 -> available 300
        // SPK 2: planned 200, completed 0 -> available 200
        // Total available = 500
        DB::table('spk_masters')->insert([
            [
                'spk_number' => '26039901',
                'item_code' => 'FG-SPK-01',
                'production_status' => 'R',
                'planned_quantity' => 400,
                'completed_quantity' => 100,
                'warehouse' => 'FFI',
                'post_date' => '2026-11-01',
                'due_date' => '2026-11-20',
            ],
            [
                'spk_number' => '26039902',
                'item_code' => 'FG-SPK-01',
                'production_status' => 'P',
                'planned_quantity' => 200,
                'completed_quantity' => 0,
                'warehouse' => 'FFI',
                'post_date' => '2026-11-05',
                'due_date' => '2026-11-25',
            ],
        ]);

        $service = app(PoReminderService::class);
        $result = $service->calculate('2026-11-01', '2026-11-30');

        $fg1 = collect($result['fg_items'])->firstWhere('item_code', 'FG-SPK-01');
        $this->assertNotNull($fg1);
        $this->assertTrue($fg1['spk_info']['has_spk']);
        $this->assertEquals(2, $fg1['spk_info']['spk_count']);
        $this->assertEquals(500, $fg1['spk_info']['available_qty']);
        $this->assertContains('26039901', $fg1['spk_info']['spk_numbers']);

        $fg2 = collect($result['fg_items'])->firstWhere('item_code', 'FG-NO-SPK');
        $this->assertNotNull($fg2);
        $this->assertFalse($fg2['spk_info']['has_spk']);
        $this->assertEquals(0, $fg2['spk_info']['available_qty']);

        // Verifikasi dalam modal detail material
        $mat = collect($result['view1_materials'])->firstWhere('item_code', '401-SHARED-WIP');
        $this->assertNotNull($mat);
        $fgInMat = collect($mat['used_by_fgs'])->firstWhere('fg_code', 'FG-SPK-01');
        $this->assertNotNull($fgInMat);
        $this->assertTrue($fgInMat['spk_info']['has_spk']);
        $this->assertEquals(500, $fgInMat['spk_info']['available_qty']);

        // Verifikasi rendering Livewire
        Livewire::actingAs($this->user)
            ->test(PoReminderIndex::class)
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2026-11-30')
            ->set('displayMode', 'fgs')
            ->assertSee('Kurang 500 pcs')
            ->assertSee('Belum Ada SPK')
            ->call('showMaterialDetail', '401-SHARED-WIP')
            ->assertSee('26039901')
            ->assertSee('26039902')
            ->assertSee('500 pcs')
            ->assertSee('Kurang 500 pcs');
    }
}

