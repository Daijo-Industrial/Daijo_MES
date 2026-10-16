<?php

namespace Tests\Feature;

use App\Livewire\MasterListItemView;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterListItemViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected MasterCustomerDelivery $customerA;

    protected MasterCustomerDelivery $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'ADMIN']);
        $this->user = User::factory()->create(['role_id' => $role->id]);

        $this->customerA = MasterCustomerDelivery::create([
            'customer_code' => 'CUST-A',
            'customer_name' => 'Customer Alpha',
        ]);

        $this->customerB = MasterCustomerDelivery::create([
            'customer_code' => 'CUST-B',
            'customer_name' => 'Customer Beta',
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
        ], $attributes));
    }

    public function test_renders_master_list_item_and_counts(): void
    {
        $this->createItem([
            'item_code' => 'ITEM-CONN',
            'item_name' => 'Connected Part',
            'customer_code' => 'CUST-A',
        ]);

        $this->createItem([
            'item_code' => 'ITEM-UNASSIGNED',
            'item_name' => 'Unassigned Part',
            'customer_code' => '0',
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListItemView::class)
            ->assertStatus(200)
            ->assertSee('TOTAL: 2')
            ->assertSee('TERHUBUNG: 1')
            ->assertSee('BELUM TERHUBUNG: 1')
            ->assertSee('ITEM-CONN')
            ->assertSee('Customer Alpha')
            ->assertSee('ITEM-UNASSIGNED')
            ->assertSee('Belum Terhubung (N/A)');
    }

    public function test_filtering_by_connection_status(): void
    {
        $this->createItem([
            'item_code' => 'ITEM-CONN',
            'item_name' => 'Connected Part',
            'customer_code' => 'CUST-A',
        ]);

        $this->createItem([
            'item_code' => 'ITEM-UNASSIGNED',
            'item_name' => 'Unassigned Part',
            'customer_code' => '0',
        ]);

        // Filter connected
        Livewire::actingAs($this->user)
            ->test(MasterListItemView::class)
            ->set('filterConnection', 'connected')
            ->assertSee('ITEM-CONN')
            ->assertDontSee('ITEM-UNASSIGNED');

        // Filter unassigned
        Livewire::actingAs($this->user)
            ->test(MasterListItemView::class)
            ->set('filterConnection', 'unassigned')
            ->assertSee('ITEM-UNASSIGNED')
            ->assertDontSee('ITEM-CONN');
    }

    public function test_can_update_item_customer_mapping(): void
    {
        $item = $this->createItem([
            'item_code' => 'ITEM-MAP-01',
            'item_name' => 'Disconnected Widget',
            'customer_code' => '0',
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListItemView::class)
            ->call('startEdit', $item->id)
            ->set('editForm.customer_code', 'CUST-B')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSee('Item updated successfully.');

        $this->assertDatabaseHas('master_list_items', [
            'id' => $item->id,
            'customer_code' => 'CUST-B',
        ]);
    }

    public function test_setting_empty_or_zero_customer_code_normalizes_to_null(): void
    {
        $item = $this->createItem([
            'item_code' => 'ITEM-CLEAR-01',
            'item_name' => 'Mapped Widget',
            'customer_code' => 'CUST-A',
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListItemView::class)
            ->call('startEdit', $item->id)
            ->set('editForm.customer_code', '')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('master_list_items', [
            'id' => $item->id,
            'customer_code' => null,
        ]);
    }
}
