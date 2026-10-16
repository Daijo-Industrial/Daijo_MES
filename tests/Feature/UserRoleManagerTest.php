<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserRoleManager;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserRoleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected User $superAdmin;

    protected User $admin;

    protected User $operatorUser;

    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or retrieve roles according to hierarchy
        $this->superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $operatorRole = Role::firstOrCreate(['name' => 'OPERATOR']);

        // Create Super Admin User
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'username' => 'superadmin',
            'password' => Hash::make('password123'),
            'role_id' => $this->superAdminRole->id,
            'is_active' => true,
        ]);

        // Create normal Admin User
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);

        // Create non-admin operator user
        $this->operatorUser = User::create([
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'username' => 'operator',
            'password' => Hash::make('password123'),
            'role_id' => $operatorRole->id,
            'is_active' => true,
        ]);

        // Create target user whose password we will change
        $this->targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@test.com',
            'username' => 'target',
            'password' => Hash::make('oldpassword'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_non_superadmin_cannot_access_user_role_manager(): void
    {
        $this->actingAs($this->operatorUser);

        Livewire::test(UserRoleManager::class)
            ->assertStatus(403);
    }

    public function test_superadmin_can_access_user_role_manager(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserRoleManager::class)
            ->assertStatus(200);
    }

    public function test_superadmin_can_select_user_for_password_change(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserRoleManager::class)
            ->call('selectUserForPasswordChange', $this->targetUser->id)
            ->assertSet('selectedUserId', $this->targetUser->id)
            ->assertSet('selectedUserName', $this->targetUser->name)
            ->assertSet('newPassword', '')
            ->assertDispatched('open-modal', 'force-change-password-modal');
    }

    public function test_superadmin_can_force_change_user_password_successfully(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserRoleManager::class)
            ->call('selectUserForPasswordChange', $this->targetUser->id)
            ->set('newPassword', 'securenewpassword123')
            ->call('forceChangePassword')
            ->assertHasNoErrors()
            ->assertSet('selectedUserId', null)
            ->assertSet('selectedUserName', '')
            ->assertSet('newPassword', '')
            ->assertDispatched('close-modal', 'force-change-password-modal');

        $this->assertTrue(Hash::check('securenewpassword123', $this->targetUser->refresh()->password));
    }

    public function test_force_change_password_requires_minimum_eight_characters(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserRoleManager::class)
            ->call('selectUserForPasswordChange', $this->targetUser->id)
            ->set('newPassword', 'short')
            ->call('forceChangePassword')
            ->assertHasErrors(['newPassword' => 'min']);

        // Assert password has not changed
        $this->assertTrue(Hash::check('oldpassword', $this->targetUser->refresh()->password));
    }

    public function test_non_superadmin_cannot_trigger_password_change_actions(): void
    {
        // Start as superadmin to successfully mount the component
        $this->actingAs($this->superAdmin);

        $component = Livewire::test(UserRoleManager::class);

        // Switch to non-admin operator before calling the action
        $this->actingAs($this->operatorUser);

        $component->call('selectUserForPasswordChange', $this->targetUser->id)
            ->assertStatus(403);
    }
}
