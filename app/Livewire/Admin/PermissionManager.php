<?php

namespace App\Livewire\Admin;

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupFilter = '';

    public ?int $editingPermissionId = null;

    public string $editingName = '';

    public string $editingLabel = '';

    public string $editingGroup = '';

    public string $editingDescription = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'groupFilter' => ['except' => ''],
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

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    public function startEdit(int $permissionId): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $permission = Permission::findOrFail($permissionId);

        $this->editingPermissionId = $permission->id;
        $this->editingName = $permission->name;
        $this->editingLabel = $permission->label;
        $this->editingGroup = $permission->group;
        $this->editingDescription = (string) $permission->description;

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'edit-permission-modal');
    }

    public function updatePermission(): void
    {
        if (Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }

        $this->validate([
            'editingLabel' => 'required|string|max:255',
            'editingGroup' => 'required|string|max:100',
            'editingDescription' => 'nullable|string|max:1000',
        ]);

        $permission = Permission::findOrFail($this->editingPermissionId);

        // Intentionally only update label, group, and description.
        // The permission 'name' is immutable because system gates and policies depend on it.
        $permission->update([
            'label' => trim($this->editingLabel),
            'group' => trim($this->editingGroup),
            'description' => trim($this->editingDescription) ?: null,
        ]);

        session()->flash('message', "Permission '{$permission->label}' updated successfully.");

        $this->reset(['editingPermissionId', 'editingName', 'editingLabel', 'editingGroup', 'editingDescription']);
        $this->dispatch('close-modal', 'edit-permission-modal');
    }

    public function render()
    {
        $query = Permission::withCount('roles');

        if (! empty(trim($this->search))) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('label', 'like', $term)
                    ->orWhere('group', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty(trim($this->groupFilter))) {
            $query->where('group', $this->groupFilter);
        }

        $permissions = $query->orderBy('group')->orderBy('label')->paginate(12);

        $availableGroups = Permission::select('group')->distinct()->orderBy('group')->pluck('group')->toArray();

        return view('livewire.admin.permission-manager', compact('permissions', 'availableGroups'));
    }
}

