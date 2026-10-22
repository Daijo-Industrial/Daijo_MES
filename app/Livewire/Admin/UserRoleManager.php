<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\Department;
use App\Models\MasterZone;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserRoleManager extends Component
{
    use WithPagination;

    public $search = '';

    public $showDeprecated = false;

    // Form fields for adding user
    public $username = '';

    public $name = '';

    public $email = '';

    public $password = '';

    public $role_id = '';

    public $branch_id = null;

    public $department_id = null;

    public $zone_id = null;

    public $selectedUserId = null;

    public $selectedUserName = '';

    public $newPassword = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'showDeprecated' => ['except' => false],
    ];

    protected $rules = [
        'username' => 'nullable|string|max:255|unique:users,username',
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:6',
        'role_id' => 'required|exists:roles,id',
        'branch_id' => 'nullable|exists:branches,id',
        'department_id' => 'nullable|exists:departments,id',
        'zone_id' => 'nullable|exists:master_zones,id',
    ];

    public function mount()
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingShowDeprecated()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $this->validate();

        User::create([
            'username' => empty(trim($this->username)) ? null : trim($this->username),
            'name' => trim($this->name),
            'email' => trim($this->email),
            'password' => Hash::make($this->password),
            'role_id' => $this->role_id,
            'branch_id' => $this->branch_id ?: null,
            'department_id' => $this->department_id ?: null,
            'zone_id' => $this->zone_id ?: null,
            'is_active' => true,
        ]);

        session()->flash('message', 'User created successfully.');
        $this->reset(['username', 'name', 'email', 'password', 'role_id', 'branch_id', 'department_id', 'zone_id']);
    }

    public function toggleActivation($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        session()->flash('message', 'User status updated to ' . ($user->is_active ? 'Active' : 'Inactive') . '.');
    }

    public function changeRole($userId, $roleId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        // Prevent changing own role (to prevent accidental lockout)
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot change your own role.');

            return;
        }

        $user->role_id = $roleId;
        $user->save();

        session()->flash('message', 'User role updated successfully.');
    }

    public function changeBranch($userId, $branchId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->branch_id = ! empty($branchId) ? $branchId : null;
        $user->save();

        session()->flash('message', 'User branch updated successfully.');
    }

    public function changeDepartment($userId, $departmentId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->department_id = ! empty($departmentId) ? $departmentId : null;
        $user->save();

        session()->flash('message', 'User department updated successfully.');
    }

    public function deprecateUser($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user->delete();
        session()->flash('message', 'User has been deprecated/soft-deleted.');
    }

    public function restoreUser($userId)
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();
        session()->flash('message', 'User has been restored.');
    }

    public function selectUserForPasswordChange($userId)
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $user = User::withTrashed()->findOrFail($userId);
        $this->selectedUserId = $user->id;
        $this->selectedUserName = $user->name;
        $this->newPassword = '';
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'force-change-password-modal');
    }

    public function forceChangePassword()
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $this->validate([
            'newPassword' => 'required|string|min:8',
        ]);

        $user = User::withTrashed()->findOrFail($this->selectedUserId);
        $user->password = Hash::make($this->newPassword);
        $user->save();

        session()->flash('message', "Password for user {$user->name} has been updated successfully.");

        $this->reset(['selectedUserId', 'selectedUserName', 'newPassword']);
        $this->dispatch('close-modal', 'force-change-password-modal');
    }

    public function render()
    {
        $query = User::with([
            'role',
            'branch',
            'department',
            'zone' => function ($q) {
                $q->select('*', 'zone_name as name');
            },
        ]);

        if ($this->showDeprecated) {
            $query->onlyTrashed();
        } else {
            $query->withoutTrashed();
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('username', 'like', '%' . $this->search . '%');
            });
        }

        $users = $query->paginate(10);
        $roles = Role::orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();
        $departments = Department::active()->orderBy('name')->get();

        $zones = MasterZone::select('*', 'zone_name as name')->orderBy('zone_name')->get();

        return view('livewire.admin.user-role-manager', compact('users', 'roles', 'zones', 'branches', 'departments'));
    }
}
