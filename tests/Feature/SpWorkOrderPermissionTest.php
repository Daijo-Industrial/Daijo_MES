<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpWorkOrderPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $authorizedUser;
    protected $unauthorizedUser;
    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $perm = Permission::firstOrCreate(
            ['name' => 'second-process-work-orders'],
            ['label' => 'Second Process Work Orders', 'group' => 'Second Process']
        );

        $authRole = Role::firstOrCreate(['name' => 'LEADER']);
        $authRole->permissions()->syncWithoutDetaching([$perm->id]);
        $this->authorizedUser = User::factory()->create([
            'role_id' => $authRole->id,
            'branch_id' => null,
        ]);

        $unauthRole = Role::firstOrCreate(['name' => 'OPERATOR']);
        $this->unauthorizedUser = User::factory()->create([
            'role_id' => $unauthRole->id,
            'branch_id' => null,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->superAdmin = User::factory()->create([
            'role_id' => $adminRole->id,
            'branch_id' => null,
        ]);
    }

    public function test_unauthorized_user_is_forbidden_from_accessing_work_orders()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('sp-work-orders.index'));
        $response->assertStatus(403);

        $createResponse = $this->get(route('sp-work-orders.create'));
        $createResponse->assertStatus(403);
    }

    public function test_authorized_user_can_access_index_and_sees_create_button()
    {
        $this->actingAs($this->authorizedUser);

        $response = $this->get(route('sp-work-orders.index'));
        $response->assertStatus(200);
        $response->assertSee(route('sp-work-orders.create'));
        $response->assertSee('New Work Order');
    }

    public function test_super_admin_bypasses_permission_and_can_access_work_orders()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('sp-work-orders.index'));
        $response->assertStatus(200);
        $response->assertSee('New Work Order');
    }

    public function test_draft_work_order_shows_edit_and_release_buttons_to_authorized_user()
    {
        $this->actingAs($this->authorizedUser);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-TEST-PERM-DRAFT',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-TEST-01',
            'part_name' => 'Test Part',
            'customer' => 'Customer A',
            'target_qty' => 100,
            'status' => 'draft',
        ]);

        $response = $this->get(route('sp-work-orders.index'));
        $response->assertStatus(200);
        $response->assertSee(route('sp-work-orders.edit', $wo->id));
        $response->assertSee(route('sp-work-orders.release', $wo->id));

        $showResponse = $this->get(route('sp-work-orders.show', $wo->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Edit Draft');
        $showResponse->assertSee('Release Order');
    }

    public function test_planned_work_order_hides_edit_button_and_shows_revert_button()
    {
        $this->actingAs($this->authorizedUser);

        $wo = SpWorkOrder::create([
            'wo_number' => 'WO-TEST-PERM-PLANNED',
            'planned_date' => now()->toDateString(),
            'unit_line' => 'Line A',
            'process_prod' => 'Assembly',
            'part_number' => 'PN-TEST-02',
            'part_name' => 'Test Part 2',
            'customer' => 'Customer A',
            'target_qty' => 100,
            'status' => 'planned',
        ]);

        $response = $this->get(route('sp-work-orders.index'));
        $response->assertStatus(200);
        $response->assertDontSee(route('sp-work-orders.edit', $wo->id));
        $response->assertSee(route('sp-work-orders.revert-to-draft', $wo->id));

        $showResponse = $this->get(route('sp-work-orders.show', $wo->id));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('Edit Draft');
        $showResponse->assertSee('Revert to Draft');
    }
}
