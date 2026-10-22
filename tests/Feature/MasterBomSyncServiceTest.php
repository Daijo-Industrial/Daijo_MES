<?php

namespace Tests\Feature;

use App\Models\MasterBom;
use App\Models\MasterBomComponent;
use App\Models\MasterBomFgHeader;
use App\Services\MasterBomSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterBomSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_sync_generates_valid_headers_and_cleanses_anomalies(): void
    {
        // 1. Buat Anomali SAP: 'SHAD TOP CASE' yang berisikan produk-produk FG
        MasterBom::create([
            'parent_item' => 'SHAD TOP CASE',
            'parent_description' => 'PLASTIC PART SHAD TOP CASE',
            'component_item' => 'D0B29100.',
            'component_description' => 'TOP CASE SH29',
            'quantity' => 1.0,
            'uom' => 'UNIT',
        ]);

        MasterBom::create([
            'parent_item' => 'SHAD TOP CASE',
            'parent_description' => 'PLASTIC PART SHAD TOP CASE',
            'component_item' => 'D0B46200KO',
            'component_description' => 'TOP CASE SH46 KOREA',
            'quantity' => 1.0,
            'uom' => 'UNIT',
        ]);

        // 2. Buat Resep Real FG: D0B29100.
        // Komponen Kemasan (Packaging)
        MasterBom::create([
            'parent_item' => 'D0B29100.',
            'parent_description' => 'TOP CASE SH29',
            'component_item' => '600-501146',
            'component_description' => 'BOX TOP CASE SH29 (390X390X330MM)',
            'quantity' => 1.0,
            'uom' => 'PCS',
        ]);

        // Komponen Level 1 (WIP Base)
        MasterBom::create([
            'parent_item' => 'D0B29100.',
            'parent_description' => 'TOP CASE SH29',
            'component_item' => '401-D1B29BT-A',
            'component_description' => 'BASE SH29',
            'quantity' => 1.0,
            'uom' => 'PCS',
        ]);

        // 3. Buat Resep Level 2 (WIP Lock Lever di dalam WIP Base)
        MasterBom::create([
            'parent_item' => '401-D1B29BT-A',
            'parent_description' => 'BASE SH29',
            'component_item' => '401-D1B29MA',
            'component_description' => 'LOCK LEVER SET SH29',
            'quantity' => 2.0,
            'uom' => 'PCS',
        ]);

        // 4. Buat Resep Level 3 (Bahan Baku Resin ABS di dalam Lock Lever)
        MasterBom::create([
            'parent_item' => '401-D1B29MA',
            'parent_description' => 'LOCK LEVER SET SH29',
            'component_item' => '400-XR404-BK',
            'component_description' => 'ABS XR404 RESIN',
            'quantity' => 0.05,
            'uom' => 'KG',
        ]);

        // Jalankan Sync Service
        $service = new MasterBomSyncService;
        $result = $service->syncAll();

        $this->assertEquals('success', $result['status']);
        $this->assertContains('SHAD TOP CASE', $result['anomalies']);

        // Verifikasi 1: Anomali 'SHAD TOP CASE' TIDAK BOLEH masuk ke master_bom_fg_headers
        $this->assertDatabaseMissing('master_bom_fg_headers', [
            'fg_item_code' => 'SHAD TOP CASE',
        ]);

        // Verifikasi 2: D0B29100. resmi diakui sebagai FG Header
        $fg = MasterBomFgHeader::where('fg_item_code', 'D0B29100.')->first();
        $this->assertNotNull($fg);
        $this->assertEquals('TOP CASE SH29', $fg->fg_description);
        $this->assertEquals('SHAD TOP CASE', $fg->project_code);
        $this->assertEquals('SHAD', $fg->customer_name);
        $this->assertTrue($fg->has_packaging);
        $this->assertEquals(2, $fg->total_wip_count); // 401-D1B29BT-A dan 401-D1B29MA
        $this->assertEquals(2, $fg->total_raw_count); // 600-501146 (Box) dan 400-XR404-BK (Resin)
        $this->assertEquals(3, $fg->max_depth_level); // Lv1 Base -> Lv2 Lever -> Lv3 Resin

        // Verifikasi 3: Struktur Multilevel di master_bom_components
        // Cek Level 1 Box
        $box = MasterBomComponent::where('fg_id', $fg->id)->where('component_item', '600-501146')->first();
        $this->assertNotNull($box);
        $this->assertEquals(1, $box->depth_level);
        $this->assertFalse($box->is_wip);
        $this->assertEquals('PACKAGING', $box->item_type);
        $this->assertEquals(1.0, $box->unit_qty);
        $this->assertEquals(1.0, $box->total_qty_per_fg);

        // Cek Level 2 Lock Lever (unit_qty = 2, total_qty_per_fg = 2)
        $lever = MasterBomComponent::where('fg_id', $fg->id)->where('component_item', '401-D1B29MA')->first();
        $this->assertNotNull($lever);
        $this->assertEquals(2, $lever->depth_level);
        $this->assertTrue($lever->is_wip);
        $this->assertEquals('401-D1B29BT-A', $lever->parent_item);
        $this->assertEquals(2.0, $lever->total_qty_per_fg);

        // Cek Level 3 Resin ABS (unit_qty = 0.05, total_qty_per_fg = 1 base * 2 lever * 0.05 resin = 0.10 KG)
        $resin = MasterBomComponent::where('fg_id', $fg->id)->where('component_item', '400-XR404-BK')->first();
        $this->assertNotNull($resin);
        $this->assertEquals(3, $resin->depth_level);
        $this->assertFalse($resin->is_wip);
        $this->assertEquals('RESIN', $resin->item_type);
        $this->assertEquals(0.05, $resin->unit_qty);
        $this->assertEquals(0.10, $resin->total_qty_per_fg);
        $this->assertEquals('D0B29100. > 401-D1B29BT-A > 401-D1B29MA > 400-XR404-BK', $resin->lineage_path);
    }
}
