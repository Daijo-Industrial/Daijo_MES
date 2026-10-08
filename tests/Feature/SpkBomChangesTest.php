<?php

namespace Tests\Feature;

use App\Livewire\SpkBomChangesView;
use App\Models\MasterBom;
use App\Models\MasterBomComponent;
use App\Models\MasterBomFgHeader;
use App\Models\MasterListMaterial;
use App\Models\Role;
use App\Models\SpkMaster;
use App\Models\User;
use App\Models\SpkBomChangeLog;
use App\Services\SpkBomService;
use App\Services\SpkMasterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SpkBomChangesTest extends TestCase
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
        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('branches', function ($table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('departments', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_list_items', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->timestamps();
        });

        Schema::create('master_list_materials', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->text('item_description')->nullable();
            $table->string('preferred_supplier')->nullable();
            $table->string('purchasing_uom')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spk_masters', function ($table) {
            $table->id();
            $table->string('spk_number')->unique();
            $table->string('item_code');
            $table->decimal('planned_quantity', 14, 2)->default(0);
            $table->decimal('completed_quantity', 14, 2)->default(0);
            $table->string('production_status')->nullable();
            $table->date('post_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('master_boms', function ($table) {
            $table->id();
            $table->integer('sap_line_id')->nullable();
            $table->string('parent_item');
            $table->string('parent_description')->nullable();
            $table->string('component_item');
            $table->string('component_description')->nullable();
            $table->decimal('quantity', 16, 6)->default(1);
            $table->string('uom', 20)->default('PCS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('master_bom_fg_headers', function ($table) {
            $table->id();
            $table->string('fg_item_code')->unique();
            $table->string('fg_description')->nullable();
            $table->string('project_code')->nullable();
            $table->string('family')->nullable();
            $table->string('customer_name')->nullable();
            $table->integer('total_wip_count')->default(0);
            $table->integer('total_raw_count')->default(0);
            $table->integer('max_depth_level')->default(1);
            $table->boolean('has_packaging')->default(false);
            $table->timestamps();
        });

        Schema::create('master_bom_components', function ($table) {
            $table->id();
            $table->foreignId('fg_id');
            $table->string('parent_item');
            $table->string('component_item');
            $table->string('component_description')->nullable();
            $table->integer('depth_level')->default(1);
            $table->string('item_type', 30)->default('RAW_MATERIAL');
            $table->boolean('is_wip')->default(false);
            $table->decimal('unit_qty', 16, 6)->default(1);
            $table->decimal('total_qty_per_fg', 16, 6)->default(1);
            $table->string('uom', 20)->default('PCS');
            $table->text('lineage_path')->nullable();
            $table->timestamps();
        });

        Schema::create('master_bom_material_overrides', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('material_type', 30);
            $table->timestamps();
        });

        Schema::create('api_logs', function ($table) {
            $table->id();
            $table->string('api_name')->nullable();
            $table->string('method')->nullable();
            $table->string('endpoint')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('spk_bom_change_logs', function ($table) {
            $table->id();
            $table->string('spk_number', 50)->index();
            $table->string('action_type', 30);
            $table->string('item_code', 100)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('replaced_item_code', 100)->nullable();
            $table->decimal('base_qty', 16, 6)->nullable();
            $table->decimal('plan_qty', 16, 4)->nullable();
            $table->decimal('old_plan_qty', 16, 4)->nullable();
            $table->string('warehouse', 50)->nullable();
            $table->string('status', 20)->default('SUCCESS');
            $table->string('message', 255)->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->timestamps();
        });
    }

    public function test_spk_bom_service_extracts_leaf_materials_and_excludes_wip(): void
    {
        // 1. Setup FG Header
        $fg = MasterBomFgHeader::create([
            'fg_item_code'    => 'D0B29100.',
            'fg_description'  => 'TOP CASE SH29',
            'project_code'    => 'SHAD TOP CASE',
            'has_packaging'   => true,
            'total_wip_count' => 1,
            'total_raw_count' => 3,
        ]);

        // 2. Setup Components in master_bom_components:
        // - WIP Component (HARUS DI-EXCLUDE dari Leaf Material): is_wip = true
        MasterBomComponent::create([
            'fg_id'                 => $fg->id,
            'parent_item'           => 'D0B29100.',
            'component_item'        => '401-D1B29BT-A',
            'component_description' => 'BASE SH29 (SUB-ASSEMBLY WIP)',
            'depth_level'           => 1,
            'item_type'             => 'WIP',
            'is_wip'                => true,
            'unit_qty'              => 1.0,
            'total_qty_per_fg'      => 1.0,
            'uom'                   => 'PCS',
        ]);

        // - Leaf Component 1: Packaging Box (is_wip = false)
        MasterBomComponent::create([
            'fg_id'                 => $fg->id,
            'parent_item'           => 'D0B29100.',
            'component_item'        => '600-501146',
            'component_description' => 'BOX TOP CASE SH29',
            'depth_level'           => 1,
            'item_type'             => 'PACKAGING',
            'is_wip'                => false,
            'unit_qty'              => 1.0,
            'total_qty_per_fg'      => 1.0,
            'uom'                   => 'PCS',
        ]);

        // - Leaf Component 2: Resin PP (is_wip = false, child of WIP BASE)
        MasterBomComponent::create([
            'fg_id'                 => $fg->id,
            'parent_item'           => '401-D1B29BT-A',
            'component_item'        => 'RESIN-PP-BLK',
            'component_description' => 'BIJI PLASTIK PP BLACK',
            'depth_level'           => 2,
            'item_type'             => 'RESIN',
            'is_wip'                => false,
            'unit_qty'              => 0.450000,
            'total_qty_per_fg'      => 0.450000,
            'uom'                   => 'KG',
        ]);

        // - Leaf Component 3: Baut / Screw (is_wip = false)
        MasterBomComponent::create([
            'fg_id'                 => $fg->id,
            'parent_item'           => 'D0B29100.',
            'component_item'        => 'FAST-M4X10',
            'component_description' => 'SCREW M4X10 ZINC',
            'depth_level'           => 1,
            'item_type'             => 'HARDWARE',
            'is_wip'                => false,
            'unit_qty'              => 4.000000,
            'total_qty_per_fg'      => 4.000000,
            'uom'                   => 'PCS',
        ]);

        $service = new SpkBomService();
        $plannedTarget = 500.0; // 500 pcs FG
        $result = $service->getLeafMaterials('D0B29100.', $plannedTarget);

        $this->assertEquals('production', $result['source']);
        $this->assertEquals('D0B29100.', $result['item_code']);
        $this->assertEquals(500.0, $result['planned_qty']);

        // Pastikan hanya 3 komponen non-WIP yang diambil (WIP 401-D1B29BT-A harus TIDAK ADA)
        $this->assertCount(3, $result['materials']);

        $itemCodes = array_column($result['materials'], 'component_item');
        $this->assertNotContains('401-D1B29BT-A', $itemCodes, 'WIP component must be strictly excluded');
        $this->assertContains('600-501146', $itemCodes);
        $this->assertContains('RESIN-PP-BLK', $itemCodes);
        $this->assertContains('FAST-M4X10', $itemCodes);

        // Verifikasi perhitungan planned material quantity = unit_qty * planned_qty
        $resin = collect($result['materials'])->firstWhere('component_item', 'RESIN-PP-BLK');
        $this->assertEquals(0.45, $resin['unit_qty']);
        $this->assertEquals(0.45 * 500.0, $resin['planned_material_qty']); // 225.0 KG

        $screw = collect($result['materials'])->firstWhere('component_item', 'FAST-M4X10');
        $this->assertEquals(4.0, $screw['unit_qty']);
        $this->assertEquals(4.0 * 500.0, $screw['planned_material_qty']); // 2000.0 PCS
    }

    public function test_livewire_spk_bom_changes_view_renders_and_toggles_accordion(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Admin',
            'email'    => 'andreas@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user);

        // Create SPK Master
        $spk = SpkMaster::create([
            'spk_number'        => 'SPK-2026-001',
            'item_code'         => 'PART-TEST-01',
            'planned_quantity'  => 1000,
            'completed_quantity'=> 250,
            'production_status' => 'P',
            'post_date'         => '2026-10-01',
        ]);

        // Create Master BOM Staging fallback
        MasterBom::create([
            'parent_item'           => 'PART-TEST-01',
            'parent_description'    => 'PART TEST STAGING',
            'component_item'        => 'RAW-TEST-A',
            'component_description' => 'RESIN POLYPROPYLENE',
            'quantity'              => 0.25,
            'uom'                   => 'KG',
        ]);

        Livewire::test(SpkBomChangesView::class)
            ->assertSee('SPK BOM Changes')
            ->assertSee('SPK-2026-001')
            ->assertSee('PART-TEST-01')
            ->assertSee('1,000')
            ->call('toggleSpk', 'SPK-2026-001', 'PART-TEST-01', 1000)
            ->assertSee('RAW-TEST-A')
            ->assertSee('RESIN POLYPROPYLENE')
            ->assertSee('0.25') // unit qty
            ->assertSee('250') // planned material qty = 0.25 * 1000
            ->call('toggleSpk', 'SPK-2026-001', 'PART-TEST-01', 1000)
            ->assertDontSee('RAW-TEST-A');
    }

    public function test_livewire_search_and_filter_functionality(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Admin',
            'email'    => 'andreas2@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        SpkMaster::create([
            'spk_number'        => 'SPK-ALPHA-01',
            'item_code'         => 'FG-ALPHA',
            'planned_quantity'  => 100,
            'production_status' => 'P',
        ]);

        SpkMaster::create([
            'spk_number'        => 'SPK-BETA-02',
            'item_code'         => 'FG-BETA',
            'planned_quantity'  => 200,
            'production_status' => 'P',
        ]);

        // Search by SPK Number
        Livewire::test(SpkBomChangesView::class)
            ->set('search', 'ALPHA')
            ->assertSee('SPK-ALPHA-01')
            ->assertDontSee('SPK-BETA-02');

        // Search by Item Code
        Livewire::test(SpkBomChangesView::class)
            ->set('search', 'FG-BETA')
            ->assertSee('SPK-BETA-02')
            ->assertDontSee('SPK-ALPHA-01');
    }

    public function test_livewire_modal_open_and_close(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Admin',
            'email'    => 'andreas3@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        $spk = SpkMaster::create([
            'spk_number'        => 'SPK-MODAL-01',
            'item_code'         => 'FG-MODAL',
            'planned_quantity'  => 500,
            'production_status' => 'P',
        ]);

        MasterBom::create([
            'parent_item'           => 'FG-MODAL',
            'component_item'        => 'RAW-BOLT-1',
            'component_description' => 'BOLT HEX M6',
            'quantity'              => 2,
            'uom'                   => 'PCS',
        ]);

        Livewire::test(SpkBomChangesView::class)
            ->assertSet('showModal', false)
            ->call('openModal', 'SPK-MODAL-01', 'FG-MODAL', 500, 'TEST PART', 'P')
            ->assertSet('showModal', true)
            ->assertSee('RAW-BOLT-1')
            ->assertSee('BOLT HEX M6')
            ->assertSee('1,000') // 2 * 500 planned material qty
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_update_production_order_lines_api_request_and_logging(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'dummy_token_123'], 200),
            '*/api/sap_production_order/update' => Http::response([
                'status'  => true,
                'message' => 'SPK 250012345 updated successfully.',
                'lines'   => [
                    ['item_code' => 'RM-STEEL-001', 'action' => 'updated'],
                    ['item_code' => 'RM-PAINT-020', 'action' => 'added'],
                    ['item_code' => 'RM-PAINT-010', 'action' => 'deleted'],
                ],
            ], 200),
        ]);

        $service = new SpkMasterService();

        // Kirim payload persis seperti spesifikasi user
        $lines = [
            [
                'item_code' => 'RM-STEEL-001',
                'plan_qty'  => 250,
            ],
            [
                'item_code' => 'RM-PAINT-020',
                'base_qty'  => 0.15,
                'plan_qty'  => 15,
                'warehouse' => 'WH-RM02',
            ],
            [
                'item_code' => 'RM-PAINT-010',
                'delete'    => true,
            ],
        ];

        $result = $service->updateProductionOrderLines('250012345', $lines, 1, 'Andreas');

        $this->assertTrue($result['status']);
        $this->assertEquals('SPK 250012345 updated successfully.', $result['message']);
        $this->assertCount(3, $result['lines']);

        // Verifikasi HTTP request payload dikirim dengan benar ke SAP
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/sap_production_order/update')) {
                $body = $request->data();
                return $body['spk_code'] === '250012345'
                    && count($body['lines']) === 3
                    && $body['lines'][0]['item_code'] === 'RM-STEEL-001'
                    && $body['lines'][0]['plan_qty'] == 250
                    && $body['lines'][1]['item_code'] === 'RM-PAINT-020'
                    && $body['lines'][1]['base_qty'] == 0.15
                    && $body['lines'][1]['plan_qty'] == 15
                    && $body['lines'][1]['warehouse'] === 'WH-RM02'
                    && $body['lines'][2]['item_code'] === 'RM-PAINT-010'
                    && $body['lines'][2]['delete'] === true;
            }
            return true;
        });

        // Verifikasi tersimpan ke spk_bom_change_logs
        $logs = SpkBomChangeLog::where('spk_number', '250012345')->get();
        $this->assertCount(3, $logs);

        $steelLog = $logs->firstWhere('item_code', 'RM-STEEL-001');
        $this->assertEquals('UPDATE_QTY', $steelLog->action_type);
        $this->assertEquals(250, $steelLog->plan_qty);
        $this->assertEquals('SUCCESS', $steelLog->status);
        $this->assertEquals('Andreas', $steelLog->created_by_name);

        $paint20Log = $logs->firstWhere('item_code', 'RM-PAINT-020');
        $this->assertEquals('ADD_MATERIAL', $paint20Log->action_type);
        $this->assertEquals(15, $paint20Log->plan_qty);
        $this->assertEquals(0.15, $paint20Log->base_qty);
        $this->assertEquals('WH-RM02', $paint20Log->warehouse);

        $paint10Log = $logs->firstWhere('item_code', 'RM-PAINT-010');
        $this->assertEquals('DELETE_MATERIAL', $paint10Log->action_type);
        $this->assertEquals('SUCCESS', $paint10Log->status);
    }

    public function test_livewire_actions_and_history_modal(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Admin',
            'email'    => 'andreas_action@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'dummy_token_123'], 200),
            '*/api/sap_production_order/update' => Http::response([
                'status'  => true,
                'message' => 'Updated ok',
                'lines'   => [
                    ['item_code' => 'MAT-TEST-01', 'action' => 'updated'],
                ],
            ], 200),
        ]);

        SpkMaster::create([
            'spk_number'        => 'SPK-ACT-001',
            'item_code'         => 'FG-ACT-01',
            'planned_quantity'  => 500,
            'production_status' => 'P',
        ]);

        MasterBom::create([
            'parent_item'           => 'FG-ACT-01',
            'component_item'        => 'MAT-TEST-01',
            'component_description' => 'TEST MATERIAL',
            'quantity'              => 1,
            'uom'                   => 'PCS',
        ]);

        // Test Livewire submitEditQty (Staged in Edit Mode)
        Livewire::test(SpkBomChangesView::class)
            ->call('openEditQtyModal', 'SPK-ACT-001', 'MAT-TEST-01', 'TEST MATERIAL', 500, 1)
            ->assertSet('showEditModal', true)
            ->set('editPlanQty', 650)
            ->call('submitEditQty')
            ->assertSet('showEditModal', false)
            ->assertSet('editingSpk', 'SPK-ACT-001')
            ->assertSee('disimpan ke draft')
            // Belum dikirim ke SAP sebelum submitBatchChanges dipanggil
            ->call('submitBatchChanges')
            ->assertSet('editingSpk', null)
            ->assertSee('Berhasil mengirim 1 perubahan material');

        $this->assertDatabaseHas('spk_bom_change_logs', [
            'spk_number' => 'SPK-ACT-001',
            'item_code'  => 'MAT-TEST-01',
            'plan_qty'   => 650,
            'status'     => 'SUCCESS',
        ]);

        // Test History Modal
        Livewire::test(SpkBomChangesView::class)
            ->call('openHistoryModal', 'SPK-ACT-001')
            ->assertSet('showHistoryModal', true)
            ->assertSee('SPK-ACT-001')
            ->assertSee('MAT-TEST-01')
            ->assertSee('650.00');
    }

    public function test_add_material_auto_calculates_plan_qty_from_base_qty_and_provides_suggestions(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Admin',
            'email'    => 'andreas_sug@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'dummy_token_123'], 200),
            '*/api/sap_production_order/update' => Http::response([
                'status'  => true,
                'message' => 'Material added ok',
                'lines'   => [
                    ['item_code' => 'RM-PAINT-020', 'action' => 'added'],
                ],
            ], 200),
        ]);

        \App\Models\MasterListItem::create([
            'item_code' => 'RM-PAINT-020',
            'item_name' => 'CAT BLACK GLOSS 020',
        ]);

        SpkMaster::create([
            'spk_number'        => 'SPK-ADD-999',
            'item_code'         => 'FG-ADD-999',
            'planned_quantity'  => 1000,
            'production_status' => 'P',
        ]);

        Livewire::test(SpkBomChangesView::class)
            ->call('openAddMaterialModal', 'SPK-ADD-999', 1000)
            ->assertSet('showAddModal', true)
            ->assertSet('addSpkPlannedQty', 1000.0)
            // Test ketik kode item memicu autocomplete suggestions
            ->set('addItemCode', 'PAINT')
            ->assertSet('showAddDropdown', true)
            ->call('selectAddMaterial', 'RM-PAINT-020', 'CAT BLACK GLOSS 020')
            ->assertSet('addItemCode', 'RM-PAINT-020')
            ->assertSet('addItemName', 'CAT BLACK GLOSS 020')
            // Test input base_qty otomatis menghitung plan_qty = 0.15 * 1000 = 150
            ->set('addBaseQty', 0.15)
            ->assertSet('addPlanQty', 150.0)
            // Submit form to draft stage
            ->call('submitAddMaterial')
            ->assertSet('showAddModal', false)
            ->assertSet('editingSpk', 'SPK-ADD-999')
            ->assertSee('ditambahkan ke draft edit')
            // Kirim batch ke SAP
            ->call('submitBatchChanges')
            ->assertSet('editingSpk', null)
            ->assertSee('Berhasil mengirim 1 perubahan material');

        // Pastikan terkirim ke SAP dengan plan_qty 150 dan base_qty 0.15
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/sap_production_order/update')) {
                $body = $request->data();
                return $body['spk_code'] === 'SPK-ADD-999'
                    && $body['lines'][0]['item_code'] === 'RM-PAINT-020'
                    && $body['lines'][0]['base_qty'] == 0.15
                    && $body['lines'][0]['plan_qty'] == 150;
            }
            return true;
        });

        $this->assertDatabaseHas('spk_bom_change_logs', [
            'spk_number' => 'SPK-ADD-999',
            'item_code'  => 'RM-PAINT-020',
            'base_qty'   => 0.15,
            'plan_qty'   => 150,
            'status'     => 'SUCCESS',
        ]);
    }

    public function test_batch_edit_mode_stages_multiple_actions_and_sends_single_payload(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Operator',
            'email'    => 'andreas_op@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'dummy_token_123'], 200),
            '*/api/sap_production_order/update' => Http::response([
                'status'  => true,
                'message' => 'Batch updated ok',
                'lines'   => [
                    ['item_code' => 'MAT-EXIST-01', 'action' => 'updated'],
                    ['item_code' => 'MAT-EXIST-02', 'action' => 'deleted'],
                    ['item_code' => 'MAT-NEW-03',   'action' => 'added'],
                ],
            ], 200),
        ]);

        SpkMaster::create([
            'spk_number'        => 'SPK-BATCH-777',
            'item_code'         => 'FG-BATCH-777',
            'planned_quantity'  => 100,
            'production_status' => 'P',
        ]);

        MasterBom::create([
            'parent_item'           => 'FG-BATCH-777',
            'component_item'        => 'MAT-EXIST-01',
            'component_description' => 'MAT 1',
            'quantity'              => 2,
            'uom'                   => 'PCS',
        ]);

        MasterBom::create([
            'parent_item'           => 'FG-BATCH-777',
            'component_item'        => 'MAT-EXIST-02',
            'component_description' => 'MAT 2',
            'quantity'              => 1,
            'uom'                   => 'PCS',
        ]);

        $test = Livewire::test(SpkBomChangesView::class)
            // 1. Masuk Mode Edit
            ->call('startEditMode', 'SPK-BATCH-777')
            ->assertSet('editingSpk', 'SPK-BATCH-777')
            ->assertSet('stagedLines', [])

            // 2. Edit Material 1 (Ubah Qty ke 250)
            ->call('openEditQtyModal', 'SPK-BATCH-777', 'MAT-EXIST-01', 'MAT 1', 200, 2, 100)
            ->set('editPlanQty', 250)
            ->call('submitEditQty')
            ->assertCount('stagedLines', 1)

            // 3. Delete Material 2 (Tandai Hapus)
            ->call('openDeleteModal', 'SPK-BATCH-777', 'MAT-EXIST-02', 'MAT 2')
            ->call('submitDeleteMaterial')
            ->assertCount('stagedLines', 2)

            // 4. Tambah Material 3 (Baru)
            ->call('openAddMaterialModal', 'SPK-BATCH-777', 100)
            ->set('addItemCode', 'MAT-NEW-03')
            ->set('addBaseQty', 0.5)
            ->call('submitAddMaterial')
            ->assertCount('stagedLines', 3);

        // Pastikan sampai tahap ini BELUM ada HTTP request ke SAP
        Http::assertNothingSent();

        // 5. Kirim semua perubahan sekaligus (Single batch payload)
        $test->call('submitBatchChanges')
            ->assertSet('editingSpk', null)
            ->assertSet('stagedLines', [])
            ->assertSee('Berhasil mengirim 3 perubahan material');

        // Verifikasi bahwa hanya 1 HTTP request update yang dikirim, dan berisi ketiga lines
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/sap_production_order/update')) {
                $body = $request->data();
                $lines = $body['lines'] ?? [];

                $hasEdit = false;
                $hasDel = false;
                $hasAdd = false;

                foreach ($lines as $l) {
                    if ($l['item_code'] === 'MAT-EXIST-01' && $l['plan_qty'] == 250) {
                        $hasEdit = true;
                    }
                    if ($l['item_code'] === 'MAT-EXIST-02' && !empty($l['delete'])) {
                        $hasDel = true;
                    }
                    if ($l['item_code'] === 'MAT-NEW-03' && $l['plan_qty'] == 50) {
                        $hasAdd = true;
                    }
                }

                return $body['spk_code'] === 'SPK-BATCH-777'
                    && count($lines) === 3
                    && $hasEdit
                    && $hasDel
                    && $hasAdd;
            }
            return true;
        });

        // Verifikasi 3 log tercatat di database
        $this->assertEquals(3, SpkBomChangeLog::where('spk_number', 'SPK-BATCH-777')->count());
    }

    public function test_unstage_item_and_cancel_edit_mode(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Cancel',
            'email'    => 'andreas_cancel@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        SpkMaster::create([
            'spk_number'        => 'SPK-CANCEL-001',
            'item_code'         => 'FG-CANCEL-01',
            'planned_quantity'  => 50,
            'production_status' => 'P',
        ]);

        Livewire::test(SpkBomChangesView::class)
            ->call('startEditMode', 'SPK-CANCEL-001')
            ->call('openAddMaterialModal', 'SPK-CANCEL-001', 50)
            ->set('addItemCode', 'MAT-TEMP-01')
            ->set('addBaseQty', 1)
            ->call('submitAddMaterial')
            ->assertCount('stagedLines', 1)
            // Test unstageItem: hapus MAT-TEMP-01 dari draft
            ->call('unstageItem', 'MAT-TEMP-01')
            ->assertCount('stagedLines', 0)
            ->assertSee('telah dibatalkan dari draft')
            // Tambah lagi lalu batalkan edit mode
            ->call('openAddMaterialModal', 'SPK-CANCEL-001', 50)
            ->set('addItemCode', 'MAT-TEMP-02')
            ->set('addBaseQty', 2)
            ->call('submitAddMaterial')
            ->assertCount('stagedLines', 1)
            // Test cancelEditMode: bersihkan staged lines dan keluar dari mode edit
            ->call('cancelEditMode')
            ->assertSet('editingSpk', null)
            ->assertCount('stagedLines', 0)
            ->assertSee('Mode edit dibatalkan');
    }

    public function test_base_qty_allows_decimal_input_like_zero_dot(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Decimal',
            'email'    => 'andreas_dec@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        SpkMaster::create([
            'spk_number'        => 'SPK-DEC-001',
            'item_code'         => 'FG-DEC-01',
            'planned_quantity'  => 678,
            'production_status' => 'P',
        ]);

        Livewire::test(SpkBomChangesView::class)
            ->call('openAddMaterialModal', 'SPK-DEC-001', 678)
            ->set('addItemCode', 'RM-PAINT-01')
            // 1. Operator mengetik '0.' (tidak boleh error atau reset)
            ->set('addBaseQty', '0.')
            ->assertSet('addBaseQty', '0.')
            ->assertSet('addPlanQty', null)
            // 2. Operator melanjutkan mengetik '0.15'
            ->set('addBaseQty', '0.15')
            ->assertSet('addPlanQty', 101.7) // 0.15 * 678 = 101.7
            // 3. Submit ke draft
            ->call('submitAddMaterial')
            ->assertSet('showAddModal', false)
            ->assertSet('stagedLines.RM-PAINT-01.base_qty', 0.15)
            ->assertSet('stagedLines.RM-PAINT-01.plan_qty', 101.7);
    }

    public function test_edit_modal_locks_plan_qty_and_calculates_from_base_qty(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas BaseQty',
            'email'    => 'andreas_base@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        SpkMaster::create([
            'spk_number'        => 'SPK-BASE-001',
            'item_code'         => 'FG-BASE-01',
            'planned_quantity'  => 1000,
            'production_status' => 'P',
        ]);

        Livewire::test(SpkBomChangesView::class)
            // Buka modal edit untuk material (Plan Qty = 50, Base Qty = 0.05, Target SPK = 1000)
            ->call('openEditQtyModal', 'SPK-BASE-001', '601-6678-2', 'PAD IMPRABOARD', 50, 0.05, 1000)
            ->assertSet('showEditModal', true)
            ->assertSet('editBaseQty', '0.05')
            ->assertSet('editPlanQty', '50')
            // User mengubah Base Qty ke 0.08
            ->set('editBaseQty', '0.08')
            ->assertSet('editPlanQty', '80') // 0.08 * 1000 = 80
            // User memasukkan nilai desimal kecil tanpa notasi ilmiah (misal 0.000009)
            ->set('editBaseQty', '0.000009')
            ->assertSet('editPlanQty', '0.009') // 0.000009 * 1000 = 0.009
            // Submit ke draft
            ->call('submitEditQty')
            ->assertSet('showEditModal', false)
            ->assertSet('stagedLines.601-6678-2.base_qty', 0.000009)
            ->assertSet('stagedLines.601-6678-2.plan_qty', 0.009)
            ->assertSet('stagedLines.601-6678-2.base_qty_changed', true);
    }

    public function test_tambah_material_button_only_visible_in_edit_mode(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Toggle',
            'email'    => 'andreas_toggle@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        SpkMaster::create([
            'spk_number'        => 'SPK-TOGGLE-001',
            'item_code'         => 'FG-TOGGLE-01',
            'planned_quantity'  => 100,
            'production_status' => 'P',
        ]);

        $fg = MasterBomFgHeader::create([
            'fg_item_code'    => 'FG-TOGGLE-01',
            'fg_description'  => 'TOGGLE ITEM FG',
            'project_code'    => 'PROJECT-TOGGLE',
            'customer_name'   => 'CUSTOMER TOGGLE',
            'total_wip_count' => 0,
            'total_raw_count' => 1,
            'max_depth_level' => 1,
            'has_packaging'   => false,
        ]);

        MasterBomComponent::create([
            'fg_id'                 => $fg->id,
            'parent_item'           => 'FG-TOGGLE-01',
            'component_item'        => 'RM-TOGGLE-PART',
            'component_description' => 'TOGGLE RAW PART',
            'depth_level'           => 1,
            'item_type'             => 'RAW_MATERIAL',
            'is_wip'                => false,
            'unit_qty'              => 2.0,
            'total_qty_per_fg'      => 2.0,
            'uom'                   => 'PCS',
        ]);

        $test = Livewire::test(SpkBomChangesView::class)
            ->call('toggleSpk', 'SPK-TOGGLE-001', 'FG-TOGGLE-01', 100.0);

        // 1. Sebelum masuk mode edit:
        // Harus ada tombol 'Masuk Mode Edit Resep'
        $test->assertSee('Masuk Mode Edit Resep');
        // Tombol '+ Tambah Material' TIDAK boleh muncul
        $test->assertDontSee('+</span>' . "\n" . '                                                        <span>Tambah Material', false);
        // Kolom Aksi dan tombol aksi baris TIDAK boleh muncul
        $test->assertDontSee('Aksi (Draft / SAP)');
        $test->assertDontSee('openEditQtyModal');
        $test->assertDontSee('openDeleteModal');

        // 2. Masuk mode edit:
        $test->call('startEditMode', 'SPK-TOGGLE-001');
        // 'Masuk Mode Edit Resep' hilang
        $test->assertDontSee('Masuk Mode Edit Resep');
        // 'Tambah Material' sekarang HARUS muncul
        $test->assertSee('Tambah Material');
        $test->assertSee('MODE EDIT RESEP AKTIF');
        // Kolom Aksi sekarang HARUS muncul
        $test->assertSee('Aksi (Draft / SAP)');

        // 3. Batalkan mode edit:
        $test->call('cancelEditMode');
        // 'Masuk Mode Edit Resep' muncul kembali
        $test->assertSee('Masuk Mode Edit Resep');
        $test->assertDontSee('MODE EDIT RESEP AKTIF');
        $test->assertDontSee('Aksi (Draft / SAP)');
    }

    public function test_menu_is_accessible_for_pe_and_ppic_roles(): void
    {
        $peRole = Role::create(['name' => 'PE']);
        $ppicRole = Role::create(['name' => 'PPIC']);

        $peUser = User::create([
            'name'     => 'User PE',
            'email'    => 'pe@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $peRole->id,
        ]);

        $ppicUser = User::create([
            'name'     => 'User PPIC',
            'email'    => 'ppic@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $ppicRole->id,
        ]);

        // Verifikasi Gate authorization
        $this->assertTrue($peUser->can('view-pe-links'));
        $this->assertTrue($ppicUser->can('view-ppic-links'));

        // Akses route SPK BOM Changes oleh PE
        $responsePe = $this->actingAs($peUser)->get(route('spk.bom-changes.index'));
        $responsePe->assertStatus(200);

        // Akses route SPK BOM Changes oleh PPIC
        $responsePpic = $this->actingAs($ppicUser)->get(route('spk.bom-changes.index'));
        $responsePpic->assertStatus(200);
    }

    public function test_dropdown_suggestions_are_fetched_from_master_list_material(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Material',
            'email'    => 'andreas_mat@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        // Buat data di master_list_materials
        MasterListMaterial::create([
            'item_code'        => 'RAW-RESIN-PP-01',
            'item_description' => 'BIJI PLASTIK RESIN PP HITAM',
            'purchasing_uom'   => 'KG',
        ]);

        MasterListMaterial::create([
            'item_code'        => 'RAW-PAINT-RED-02',
            'item_description' => 'CAT MERAH GLOSS AUTOMOTIVE',
            'purchasing_uom'   => 'LT',
        ]);

        SpkMaster::create([
            'spk_number'        => 'SPK-SUGGEST-001',
            'item_code'         => 'FG-SUGGEST-01',
            'planned_quantity'  => 500,
            'production_status' => 'P',
        ]);

        $component = Livewire::test(SpkBomChangesView::class)
            ->call('openAddMaterialModal', 'SPK-SUGGEST-001', 500)
            ->set('addItemCode', 'RAW-RESIN');

        // Verifikasi suggestion memuat item dari master_list_materials
        $suggestions = $component->get('addItemSuggestions');
        $this->assertNotEmpty($suggestions);
        $this->assertEquals('RAW-RESIN-PP-01', $suggestions[0]['item_code']);
        $this->assertEquals('BIJI PLASTIK RESIN PP HITAM', $suggestions[0]['item_name']);
        $this->assertEquals('KG', $suggestions[0]['uom']);

        // Verifikasi auto-fill nama material
        $component->call('selectAddMaterial', 'RAW-RESIN-PP-01', 'BIJI PLASTIK RESIN PP HITAM');
        $component->assertSet('addItemCode', 'RAW-RESIN-PP-01');
        $component->assertSet('addItemName', 'BIJI PLASTIK RESIN PP HITAM');

        // Verifikasi untuk modal Replace Material juga mengambil dari master_list_materials
        $replaceComp = Livewire::test(SpkBomChangesView::class)
            ->call('openReplaceModal', 'SPK-SUGGEST-001', 'OLD-ITEM', 'Old Material', 500)
            ->set('replaceNewItemCode', 'RAW-PAINT');

        $replaceSuggestions = $replaceComp->get('replaceItemSuggestions');
        $this->assertNotEmpty($replaceSuggestions);
        $this->assertEquals('RAW-PAINT-RED-02', $replaceSuggestions[0]['item_code']);
        $this->assertEquals('CAT MERAH GLOSS AUTOMOTIVE', $replaceSuggestions[0]['item_name']);
        $this->assertEquals('LT', $replaceSuggestions[0]['uom']);
    }

    public function test_preview_payload_page_renders_json(): void
    {
        $peRole = Role::firstOrCreate(['name' => 'PE']);
        $peUser = User::create([
            'name'     => 'PE Tester',
            'email'    => 'pe_preview@daijo.com',
            'password' => bcrypt('password'),
            'role_id'  => $peRole->id,
        ]);

        $payloadData = [
            'spk_code'     => '250099999',
            'endpoint'     => 'http://localhost:9000/api/sap_production_order/update',
            'method'       => 'POST',
            'payload'      => [
                'spk_code' => '250099999',
                'lines'    => [
                    ['item_code' => 'RM-STEEL-001', 'plan_qty' => 250],
                    ['item_code' => 'RM-PAINT-020', 'base_qty' => 0.15, 'plan_qty' => 15, 'warehouse' => 'WH-RM02'],
                    ['item_code' => 'RM-PAINT-010', 'delete' => true],
                ],
            ],
            'payload_json' => json_encode([
                'spk_code' => '250099999',
                'lines'    => [
                    ['item_code' => 'RM-STEEL-001', 'plan_qty' => 250],
                ],
            ], JSON_PRETTY_PRINT),
            'result'       => ['status' => true, 'message' => 'Success test'],
            'error'        => null,
            'status'       => 'SUCCESS',
            'timestamp'    => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($peUser)
            ->withSession(['sap_payload_preview' => $payloadData])
            ->get(route('spk.bom-changes.preview-payload'));

        $response->assertStatus(200);
        $response->assertSee('250099999');
        $response->assertSee('/api/sap_production_order/update');
        $response->assertSee('RM-STEEL-001');
    }

    public function test_released_spk_is_excluded_from_view_and_cannot_be_edited(): void
    {
        $role = Role::create(['name' => 'SUPERADMIN']);
        $user = User::create([
            'name'     => 'Andreas Guard',
            'email'    => 'andreas_guard@daijo.co.id',
            'password' => bcrypt('secret'),
            'role_id'  => $role->id,
        ]);
        $this->actingAs($user);

        // SPK Planned -> Harus terlihat di list
        SpkMaster::create([
            'spk_number'        => 'SPK-PLANNED-01',
            'item_code'         => 'FG-PLANNED',
            'planned_quantity'  => 100,
            'production_status' => 'P',
        ]);

        // SPK Released -> TIDAK boleh terlihat di list
        SpkMaster::create([
            'spk_number'        => 'SPK-RELEASED-02',
            'item_code'         => 'FG-RELEASED',
            'planned_quantity'  => 200,
            'production_status' => 'R',
        ]);

        // SPK Closed -> TIDAK boleh terlihat di list
        SpkMaster::create([
            'spk_number'        => 'SPK-CLOSED-03',
            'item_code'         => 'FG-CLOSED',
            'planned_quantity'  => 300,
            'production_status' => 'C',
        ]);

        $test = Livewire::test(SpkBomChangesView::class)
            ->assertSee('SPK-PLANNED-01')
            ->assertDontSee('SPK-RELEASED-02')
            ->assertDontSee('SPK-CLOSED-03');

        // Mencoba masuk mode edit untuk SPK Released harus ditolak
        $test->call('startEditMode', 'SPK-RELEASED-02')
            ->assertSet('editingSpk', null)
            ->assertSee('tidak boleh diubah resep materialnya');

        // Mencoba buka modal edit/tambah/hapus/replace untuk SPK Released juga harus ditolak
        $test->call('openEditQtyModal', 'SPK-RELEASED-02', 'RM-01', 'RM Test', 10, 1)
            ->assertSet('showEditModal', false)
            ->assertSee('tidak boleh diubah resep materialnya');

        $test->call('openAddMaterialModal', 'SPK-RELEASED-02', 200)
            ->assertSet('showAddModal', false)
            ->assertSee('tidak boleh diubah resep materialnya');

        $test->call('openDeleteModal', 'SPK-RELEASED-02', 'RM-01', 'RM Test')
            ->assertSet('showDeleteModal', false)
            ->assertSee('tidak boleh diubah resep materialnya');

        $test->call('openReplaceModal', 'SPK-RELEASED-02', 'RM-01', 'RM Test', 200)
            ->assertSet('showReplaceModal', false)
            ->assertSee('tidak boleh diubah resep materialnya');

        // Mencoba submit langsung tanpa modal juga harus ditolak
        $test->set('editSpkNumber', 'SPK-RELEASED-02')
            ->set('editItemCode', 'RM-01')
            ->set('editBaseQty', 1)
            ->call('submitEditQty')
            ->assertSee('tidak boleh diubah');

        $test->set('addSpkNumber', 'SPK-RELEASED-02')
            ->set('addItemCode', 'RM-NEW')
            ->set('addBaseQty', 1)
            ->call('submitAddMaterial')
            ->assertSee('tidak boleh diubah');

        $test->set('replaceSpkNumber', 'SPK-RELEASED-02')
            ->set('replaceOldItemCode', 'RM-01')
            ->set('replaceNewItemCode', 'RM-NEW')
            ->set('replaceBaseQty', 1)
            ->call('submitReplaceMaterial')
            ->assertSee('tidak boleh diubah');
    }
}



