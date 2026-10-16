<?php

namespace Tests\Feature;

use App\Livewire\MasterBomView;
use App\Models\MasterBom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterBomTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_master_bom_page_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(MasterBomView::class)
            ->assertStatus(200)
            ->assertSee('Master Bill of Materials');
    }

    public function test_bom_tree_explosion_calculates_multi_level()
    {
        // Level 0 -> Level 1 (WIP) and Chemical
        MasterBom::create([
            'parent_item' => '1-31000266345',
            'parent_description' => 'PRINT HANDLE PANEL VP6915',
            'component_item' => '0-31000266065',
            'component_description' => 'HANDLE-PANEL (WHITE)',
            'quantity' => 1.0,
            'uom' => 'PCS',
        ]);

        MasterBom::create([
            'parent_item' => '1-31000266345',
            'parent_description' => 'PRINT HANDLE PANEL VP6915',
            'component_item' => '440-DPPU-1010ABS',
            'component_description' => 'PAINT METALLIC SILVER ABS',
            'quantity' => 0.00097,
            'uom' => 'KG',
        ]);

        // Level 1 (WIP) -> Level 2 (Raw Material)
        MasterBom::create([
            'parent_item' => '0-31000266065',
            'parent_description' => 'HANDLE-PANEL (WHITE)',
            'component_item' => '400-XR404-BK',
            'component_description' => 'ABS XR404 RESIN',
            'quantity' => 0.07945,
            'uom' => 'KG',
        ]);

        // Explode for 500 PCS of FG
        $tree = MasterBom::explodeTree('1-31000266345', 500);

        $this->assertCount(2, $tree);

        // Find WIP node
        $wipNode = collect($tree)->firstWhere('component_item', '0-31000266065');
        $this->assertTrue($wipNode['is_wip']);
        $this->assertEquals(500, $wipNode['total_qty']);
        $this->assertCount(1, $wipNode['children']);

        // Check child of WIP (ABS resin): 500 * 0.07945 = 39.725 KG
        $resinChild = $wipNode['children'][0];
        $this->assertEquals('400-XR404-BK', $resinChild['component_item']);
        $this->assertEquals(39.725, $resinChild['total_qty']);
    }

    public function test_production_tab_renders_fg_headers_and_components_modal()
    {
        $this->actingAs($this->user);

        // Create FG Header
        $fg = \App\Models\MasterBomFgHeader::create([
            'fg_item_code' => 'D0B29100.',
            'fg_description' => 'TOP CASE SH29',
            'project_code' => 'SHAD TOP CASE',
            'customer_name' => 'SHAD',
            'uom' => 'PCS',
            'total_wip_count' => 2,
            'total_raw_count' => 5,
            'max_depth_level' => 3,
            'has_packaging' => true,
            'is_active' => true,
        ]);

        // Create Multilevel Component
        \App\Models\MasterBomComponent::create([
            'fg_id' => $fg->id,
            'parent_item' => 'D0B29100.',
            'component_item' => '301-304127',
            'component_description' => 'SCREW 3.5X13 DIN 7982',
            'depth_level' => 1,
            'item_type' => 'RAW_MATERIAL',
            'is_wip' => false,
            'unit_qty' => 9.0,
            'total_qty_per_fg' => 9.0,
            'uom' => 'PCS',
            'lineage_path' => 'D0B29100. > 301-304127',
        ]);

        Livewire::test(MasterBomView::class)
            ->set('activeMainTab', 'production')
            ->assertSee('D0B29100.')
            ->assertSee('SHAD TOP CASE')
            ->assertSee('TOP CASE SH29')
            // Test opening components modal for this FG
            ->call('showFgComponents', $fg->id)
            ->assertSet('showComponentModal', true)
            ->assertSee('301-304127')
            ->assertSee('SCREW 3.5X13 DIN 7982')
            // Test Where-Used search
            ->set('whereUsedSearch', '301-304127')
            ->assertSee('Hasil Where-Used untuk');
    }
}
