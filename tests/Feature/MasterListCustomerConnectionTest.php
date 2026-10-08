<?php

namespace Tests\Feature;

use App\Http\Controllers\SecondProcessReportController;
use App\Livewire\Admin\MasterListManager;
use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterItemLog;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

class MasterListCustomerConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $this->adminRole->id,
        ]);
    }

    protected function createItem(array $attributes = []): MasterListItem
    {
        return MasterListItem::create(array_merge([
            'item_code' => 'ITEM-' . uniqid(),
            'item_name' => 'Sample Part',
            'tipe_mesin' => 'INJECTION',
            'standart_packaging_list' => 100,
            'setup_time_minute' => '10',
            'pair' => '0',
            'cavity' => 1,
            'cycle_time' => 30.0,
            'customer_code' => null,
            'family' => '0',
            'description_in_foreign_lang' => '0',
            'color' => '0',
            'half_code_1' => '0',
            'half_code_2' => '0',
            'position' => '0',
        ], $attributes));
    }

    public function test_master_list_item_business_partner_relationship(): void
    {
        $bp = MasterBusinessPartner::create([
            'bp_code' => 'CUST-001',
            'bp_name' => 'PT ASTRA HONDA MOTOR',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'is_active' => true,
        ]);

        $item = $this->createItem([
            'item_code' => 'PART-TEST-001',
            'item_name' => 'Bracket Front',
            'customer_code' => 'CUST-001',
        ]);

        $this->assertNotNull($item->businessPartner);
        $this->assertEquals('PT ASTRA HONDA MOTOR', $item->businessPartner->bp_name);
    }

    public function test_master_list_manager_connection_filters_and_counts(): void
    {
        $bp = MasterBusinessPartner::create([
            'bp_code' => 'CUST-001',
            'bp_name' => 'PT DENSO INDONESIA',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'is_active' => true,
        ]);

        // 1 connected item
        $this->createItem([
            'item_code' => 'CONN-001',
            'item_name' => 'Connected Part',
            'customer_code' => 'CUST-001',
        ]);

        // 2 unassigned items
        $this->createItem([
            'item_code' => 'UNASS-001',
            'item_name' => 'Unassigned Part 1',
            'customer_code' => null,
        ]);

        $this->createItem([
            'item_code' => 'UNASS-002',
            'item_name' => 'Unassigned Part 2',
            'customer_code' => '0',
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListManager::class)
            ->assertViewHas('counts', function ($counts) {
                return $counts['ALL'] === 3 &&
                       $counts['CONNECTED'] === 1 &&
                       $counts['UNASSIGNED'] === 2;
            })
            // Test CONNECTED filter
            ->set('filterConnection', 'CONNECTED')
            ->assertSee('CONN-001')
            ->assertDontSee('UNASS-001')
            ->assertDontSee('UNASS-002')
            // Test UNASSIGNED filter
            ->set('filterConnection', 'UNASSIGNED')
            ->assertSee('UNASS-001')
            ->assertSee('UNASS-002')
            ->assertDontSee('CONN-001');
    }

    public function test_master_list_inline_customer_edit(): void
    {
        $bp = MasterBusinessPartner::create([
            'bp_code' => 'CUST-002',
            'bp_name' => 'PT YAMAHA MOTOR',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'is_active' => true,
        ]);

        $item = $this->createItem([
            'item_code' => 'INLINE-001',
            'item_name' => 'Inline Part',
            'customer_code' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListManager::class)
            ->call('startEdit', $item->id, 'customer_code')
            ->set('editingValue', 'CUST-002')
            ->call('saveEdit');

        $item->refresh();
        $this->assertEquals('CUST-002', $item->customer_code);

        // Verify audit log
        $this->assertDatabaseHas('master_item_logs', [
            'item_code' => 'INLINE-001',
            'action' => 'inline_edit',
        ]);
    }

    public function test_second_process_search_items_resolves_customer_name_from_business_partner(): void
    {
        $bp = MasterBusinessPartner::create([
            'bp_code' => 'CUST-004',
            'bp_name' => 'PT TOYOTA MOTOR MFG',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'is_active' => true,
        ]);

        $item = $this->createItem([
            'item_code' => 'SP-PART-001',
            'item_name' => 'Front Bumper Support',
            'customer_code' => 'CUST-004',
        ]);

        $controller = new SecondProcessReportController();
        $request = new Request(['q' => 'SP-PART-001']);

        $response = $controller->searchItems($request);
        $data = $response->getData(true);

        $this->assertNotEmpty($data);
        $this->assertEquals('SP-PART-001', $data[0]['item_code']);
        $this->assertEquals('PT TOYOTA MOTOR MFG', $data[0]['customer_name']);
        $this->assertNotEquals('N/A', $data[0]['customer_name']);
    }
}
