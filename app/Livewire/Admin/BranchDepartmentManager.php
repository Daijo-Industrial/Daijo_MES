<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class BranchDepartmentManager extends Component
{
    public $activeSubTab = 'branches'; // 'branches', 'departments', or 'plant-departments'

    protected $queryString = [
        'activeSubTab' => ['except' => 'branches'],
    ];

    // Branch Form properties
    public $branchId = null;

    public $branchCode = '';

    public $branchName = '';

    public $branchAddress = '';

    public $branchIsMain = false;

    public $branchIsActive = true;

    public $isEditingBranch = false;

    // Department Form properties
    public $departmentId = null;

    public $departmentCode = '';

    public $departmentName = '';

    public $departmentDescription = '';

    public $departmentIsActive = true;

    public $isEditingDepartment = false;

    private function authorizeAccess(): void
    {
        if (Gate::denies('manage-branches-departments') && Gate::denies('manage-branches-departments') && Gate::denies('manage-users-roles')) {
            abort(403, 'Unauthorized Action.');
        }
    }

    public function mount($initialTab = null)
    {
        $this->authorizeAccess();
        if ($initialTab && in_array($initialTab, ['branches', 'departments', 'plant-departments'])) {
            $this->activeSubTab = $initialTab;
        }
    }

    public function openBranchModal($branchId = null)
    {
        $this->resetErrorBag();
        if ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $this->branchId = $branch->id;
            $this->branchCode = $branch->code;
            $this->branchName = $branch->name;
            $this->branchAddress = $branch->address;
            $this->branchIsMain = (bool) $branch->is_main;
            $this->branchIsActive = (bool) $branch->is_active;
            $this->isEditingBranch = true;
        } else {
            $this->reset(['branchId', 'branchCode', 'branchName', 'branchAddress', 'branchIsMain']);
            $this->branchIsActive = true;
            $this->isEditingBranch = false;
        }

        $this->dispatch('open-modal', 'branch-modal');
    }

    public function saveBranch()
    {
        $this->authorizeAccess();

        $rules = [
            'branchCode' => 'required|string|max:50|unique:branches,code,' . ($this->branchId ?: 'NULL') . ',id',
            'branchName' => 'required|string|max:255',
            'branchAddress' => 'nullable|string|max:255',
            'branchIsMain' => 'boolean',
            'branchIsActive' => 'boolean',
        ];

        $this->validate($rules);

        if ($this->branchIsMain) {
            // Only one main plant allowed
            Branch::where('is_main', true)->update(['is_main' => false]);
        }

        Branch::updateOrCreate(
            ['id' => $this->branchId],
            [
                'code' => strtoupper(trim($this->branchCode)),
                'name' => trim($this->branchName),
                'address' => trim($this->branchAddress) ?: null,
                'is_main' => $this->branchIsMain,
                'is_active' => $this->branchIsActive,
            ]
        );

        session()->flash('message', 'Branch saved successfully.');
        $this->dispatch('close-modal', 'branch-modal');
    }

    public function toggleBranchActive($branchId)
    {
        $this->authorizeAccess();

        $branch = Branch::findOrFail($branchId);
        $branch->is_active = ! $branch->is_active;
        $branch->save();

        session()->flash('message', "Branch {$branch->name} status updated.");
    }

    public function openDepartmentModal($departmentId = null)
    {
        $this->resetErrorBag();
        if ($departmentId) {
            $dept = Department::findOrFail($departmentId);
            $this->departmentId = $dept->id;
            $this->departmentCode = $dept->code;
            $this->departmentName = $dept->name;
            $this->departmentDescription = $dept->description;
            $this->departmentIsActive = (bool) $dept->is_active;
            $this->isEditingDepartment = true;
        } else {
            $this->reset(['departmentId', 'departmentCode', 'departmentName', 'departmentDescription']);
            $this->departmentIsActive = true;
            $this->isEditingDepartment = false;
        }

        $this->dispatch('open-modal', 'department-modal');
    }

    public function saveDepartment()
    {
        $this->authorizeAccess();

        $rules = [
            'departmentCode' => 'required|string|max:50|unique:departments,code,' . ($this->departmentId ?: 'NULL') . ',id',
            'departmentName' => 'required|string|max:255',
            'departmentDescription' => 'nullable|string|max:500',
            'departmentIsActive' => 'boolean',
        ];

        $this->validate($rules);

        Department::updateOrCreate(
            ['id' => $this->departmentId],
            [
                'code' => strtoupper(trim($this->departmentCode)),
                'name' => trim($this->departmentName),
                'description' => trim($this->departmentDescription) ?: null,
                'is_active' => $this->departmentIsActive,
            ]
        );

        session()->flash('message', 'Department saved successfully.');
        $this->dispatch('close-modal', 'department-modal');
    }

    public function toggleDepartmentActive($departmentId)
    {
        $this->authorizeAccess();

        $dept = Department::findOrFail($departmentId);
        $dept->is_active = ! $dept->is_active;
        $dept->save();

        session()->flash('message', "Department {$dept->name} status updated.");
    }

    public function toggleDepartmentForBranch($branchId, $departmentId): void
    {
        $this->authorizeAccess();

        $branch = Branch::findOrFail($branchId);
        $branch->departments()->toggle($departmentId);

        $dept = Department::find($departmentId);
        $deptName = $dept ? $dept->name : "Department #{$departmentId}";
        session()->flash('message', "Mapping departemen '{$deptName}' untuk {$branch->name} berhasil diperbarui.");
    }

    public function render()
    {
        $branches = Branch::with(['departments', 'users'])
            ->withCount('users')
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();
        $departments = Department::withCount('users')->orderBy('name')->get();

        return view('livewire.admin.branch-department-manager', compact('branches', 'departments'));
    }
}
