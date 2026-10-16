<?php

namespace App\Livewire\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class RoleManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public ?int $editingRoleId = null;

    public string $editingRoleName = '';

    // Permission Management Properties
    public ?int $managingPermissionsRoleId = null;

    public string $managingPermissionsRoleName = '';

    public bool $managingPermissionsRoleIsProtected = false;

    public array $selectedPermissions = [];

    public string $permissionSearch = '';

    public array $newRolePermissions = [];

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createRole(): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $this->name = strtoupper(trim($this->name));

        $this->validate([
            'name' => 'required|string|max:50|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $this->name,
        ]);

        if (! empty($this->newRolePermissions)) {
            $permIds = array_map('intval', array_filter($this->newRolePermissions, 'is_numeric'));
            $role->syncPermissions($permIds);
        }

        session()->flash('message', "Role '{$this->name}' created successfully.");
        $this->reset(['name', 'newRolePermissions']);
        $this->resetErrorBag();
        $this->dispatch('close-modal', 'create-role-modal');
    }

    public function startEditRole(int $roleId): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $role = Role::findOrFail($roleId);
        $this->editingRoleId = $role->id;
        $this->editingRoleName = $role->name;
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'edit-role-modal');
    }

    public function updateRole(): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $this->editingRoleName = strtoupper(trim($this->editingRoleName));

        $this->validate([
            'editingRoleName' => 'required|string|max:50|unique:roles,name,' . $this->editingRoleId,
        ], [
            'editingRoleName.required' => 'The role name field is required.',
            'editingRoleName.unique' => 'The role name has already been taken.',
            'editingRoleName.max' => 'The role name must not be greater than 50 characters.',
        ]);

        $role = Role::findOrFail($this->editingRoleId);
        $oldName = $role->name;
        $role->update([
            'name' => $this->editingRoleName,
        ]);

        session()->flash('message', "Role '{$oldName}' updated to '{$role->name}' successfully.");
        $this->reset(['editingRoleId', 'editingRoleName']);
        $this->resetErrorBag();
        $this->dispatch('close-modal', 'edit-role-modal');
    }

    public function deleteRole(int $roleId): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $role = Role::withCount('users')->findOrFail($roleId);

        if ($role->isProtected()) {
            session()->flash('error', "Cannot delete protected system role '{$role->name}'.");

            return;
        }

        if ($role->users_count > 0) {
            session()->flash('error', "Cannot delete role '{$role->name}' because {$role->users_count} user(s) are currently assigned to it.");

            return;
        }

        $roleName = $role->name;
        $role->delete();

        session()->flash('message', "Role '{$roleName}' deleted successfully.");
    }

    public function managePermissions(int $roleId): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $role = Role::with('permissions')->findOrFail($roleId);
        $this->managingPermissionsRoleId = $role->id;
        $this->managingPermissionsRoleName = $role->name;
        $this->managingPermissionsRoleIsProtected = $role->isProtected() && strtoupper(trim($role->name)) === 'SUPER-ADMIN';
        $this->permissionSearch = '';

        if (strtoupper(trim($role->name)) === 'SUPER-ADMIN') {
            $this->selectedPermissions = Permission::pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } elseif ($role->permissions_configured) {
            $this->selectedPermissions = $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } else {
            // Preload defaults for unconfigured role to avoid accidental wipeout
            $allPerms = Permission::all();
            $defaults = [];
            foreach ($allPerms as $perm) {
                if ($role->hasPermission($perm->name)) {
                    $defaults[] = (int) $perm->id;
                }
            }
            $this->selectedPermissions = $defaults;
        }

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'manage-permissions-modal');
    }

    public function saveRolePermissions(): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        if (! $this->managingPermissionsRoleId) {
            return;
        }

        $role = Role::findOrFail($this->managingPermissionsRoleId);
        $permissionIds = array_map('intval', array_filter($this->selectedPermissions, 'is_numeric'));

        $role->syncPermissions($permissionIds);

        session()->flash('message', "Permissions for role '{$role->name}' updated successfully (" . count($permissionIds) . ' permissions assigned).');
        $this->dispatch('close-modal', 'manage-permissions-modal');
        $this->reset(['managingPermissionsRoleId', 'managingPermissionsRoleName', 'selectedPermissions']);
    }

    public function selectAllPermissions(): void
    {
        $permsQuery = Permission::query();
        if (! empty(trim($this->permissionSearch))) {
            $term = '%' . trim($this->permissionSearch) . '%';
            $permsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('label', 'like', $term)
                    ->orWhere('group', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
            $ids = $permsQuery->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $ids)));
        } else {
            $this->selectedPermissions = Permission::pluck('id')->map(fn ($id) => (int) $id)->toArray();
        }
    }

    public function deselectAllPermissions(): void
    {
        if (! empty(trim($this->permissionSearch))) {
            $term = '%' . trim($this->permissionSearch) . '%';
            $ids = Permission::where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('label', 'like', $term)
                    ->orWhere('group', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })->pluck('id')->map(fn ($id) => (int) $id)->toArray();

            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $ids));
        } else {
            $this->selectedPermissions = [];
        }
    }

    public function toggleGroupPermissions(string $group): void
    {
        $groupPermissionIds = Permission::where('group', $group)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $allInGroupSelected = empty(array_diff($groupPermissionIds, $this->selectedPermissions));

        if ($allInGroupSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupPermissionIds));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $groupPermissionIds)));
        }
    }

    public function render()
    {
        $query = Role::with(['permissions'])->withCount([
            'users as active_users_count' => function ($q) {
                $q->withoutTrashed()->where('is_active', true);
            },
            'users as total_users_count' => function ($q) {
                $q->withoutTrashed();
            },
        ]);

        if (! empty(trim($this->search))) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        $roles = $query->orderBy('name')->paginate(10);

        $permissionsQuery = Permission::orderBy('group')->orderBy('label');
        if (! empty(trim($this->permissionSearch))) {
            $term = '%' . trim($this->permissionSearch) . '%';
            $permissionsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('label', 'like', $term)
                    ->orWhere('group', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }
        $groupedPermissions = $permissionsQuery->get()->groupBy('group');
        $totalAvailablePermissionsCount = Permission::count();

        return view('livewire.admin.role-manager', compact('roles', 'groupedPermissions', 'totalAvailablePermissionsCount'));
    }
}
