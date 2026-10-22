<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class UserActivityDashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'activities'; // activities | inactive | auth | api

    // Common search
    public string $search = '';

    // Filters: Tab 1 (Activities)
    public string $logNameFilter = '';

    public string $causerFilter = '';

    public string $eventFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    // Filters: Tab 2 (Inactive Users)
    public string $inactiveSearch = '';

    public string $inactiveRoleFilter = '';

    public string $inactiveBranchFilter = '';

    public string $inactiveDepartmentFilter = '';

    public array $selectedUsers = [];

    public bool $selectAll = false;

    // Filters: Tab 3 (Auth & Security)
    public string $authSearch = '';

    public string $authEventFilter = ''; // login | logout | failed | lockout

    // Filters: Tab 4 (Inbound API & Webhooks)
    public string $apiSearch = '';

    public string $apiStatusFilter = ''; // 2xx | 4xx | 5xx

    public string $apiMethodFilter = '';

    // Modals
    public ?int $selectedActivityId = null;

    public ?Activity $selectedActivity = null;

    public bool $showDetailModal = false;

    public string $detailModalTab = 'diff'; // diff | json

    public ?int $userToDeactivateId = null;

    public ?string $userToDeactivateName = null;

    public bool $showDeactivateModal = false;

    public bool $showBulkDeactivateModal = false;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'activeTab' => ['except' => 'activities'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (Gate::denies('manage-users-roles') && ! auth()->user()?->hasRole('SUPER-ADMIN')) {
            abort(403, 'Unauthorized Action. Super-Admin access required.');
        }

        $tab = request()->query('tab');
        if (in_array($tab, ['activities', 'inactive', 'auth', 'api'])) {
            $this->activeTab = $tab;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->reset(['selectedUsers', 'selectAll']);
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedUsers = $this->getInactiveUsersQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogNameFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCauserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInactiveSearch(): void
    {
        $this->resetPage();
    }

    public function updatingInactiveRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAuthSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAuthEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingApiSearch(): void
    {
        $this->resetPage();
    }

    public function clearActivityFilters(): void
    {
        $this->reset(['search', 'logNameFilter', 'causerFilter', 'eventFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function clearInactiveFilters(): void
    {
        $this->reset(['inactiveSearch', 'inactiveRoleFilter', 'inactiveBranchFilter', 'inactiveDepartmentFilter', 'selectedUsers', 'selectAll']);
        $this->resetPage();
    }

    public function clearAuthFilters(): void
    {
        $this->reset(['authSearch', 'authEventFilter']);
        $this->resetPage();
    }

    public function clearApiFilters(): void
    {
        $this->reset(['apiSearch', 'apiMethodFilter', 'apiStatusFilter']);
        $this->resetPage();
    }

    public function setModalTab(string $tab): void
    {
        $this->detailModalTab = in_array($tab, ['diff', 'json']) ? $tab : 'diff';
    }

    public function viewDetails(int $id): void
    {
        $this->selectedActivityId = $id;
        $this->selectedActivity = Activity::with(['causer', 'subject'])->find($id);
        $this->detailModalTab = (isset($this->selectedActivity->properties['attributes']) || isset($this->selectedActivity->properties['old'])) ? 'diff' : 'json';
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedActivity = null;
        $this->selectedActivityId = null;
    }

    public function confirmDeactivate(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');

            return;
        }

        $this->userToDeactivateId = $user->id;
        $this->userToDeactivateName = $user->name . ' (' . ($user->username ?: $user->email) . ')';
        $this->showDeactivateModal = true;
    }

    public function deactivateUser(): void
    {
        if (! $this->userToDeactivateId) {
            return;
        }

        $user = User::findOrFail($this->userToDeactivateId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            $this->showDeactivateModal = false;

            return;
        }

        $user->is_active = false;
        $user->save();

        activity('user_management')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'action' => 'manual_inactivation',
                'reason' => 'Account inactive for more than 30 days',
                'last_activity_at' => $user->last_activity_at?->toIso8601String() ?: 'never',
            ])
            ->log("Deactivated inactive user '{$user->name}' (ID: {$user->id})");

        session()->flash('message', "User '{$user->name}' has been successfully deactivated.");

        $this->showDeactivateModal = false;
        $this->userToDeactivateId = null;
        $this->userToDeactivateName = null;
    }

    public function confirmBulkDeactivate(): void
    {
        // Exclude current authenticated user
        $this->selectedUsers = array_diff($this->selectedUsers, [(string) auth()->id()]);

        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Please select at least one inactive user to deactivate.');

            return;
        }

        $this->showBulkDeactivateModal = true;
    }

    public function bulkDeactivate(): void
    {
        $this->selectedUsers = array_diff($this->selectedUsers, [(string) auth()->id()]);

        if (empty($this->selectedUsers)) {
            $this->showBulkDeactivateModal = false;

            return;
        }

        $users = User::whereIn('id', $this->selectedUsers)->get();
        $count = 0;

        foreach ($users as $user) {
            $user->is_active = false;
            $user->save();

            activity('user_management')
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'bulk_manual_inactivation',
                    'reason' => 'Account inactive for more than 30 days',
                    'last_activity_at' => $user->last_activity_at?->toIso8601String() ?: 'never',
                ])
                ->log("Bulk deactivated inactive user '{$user->name}' (ID: {$user->id})");

            $count++;
        }

        session()->flash('message', "Successfully deactivated {$count} inactive user(s).");

        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->showBulkDeactivateModal = false;
    }

    protected function getInactiveUsersQuery()
    {
        $cutoff = Carbon::now()->subDays(30);

        $query = User::query()
            ->withoutTrashed()
            ->where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $cutoff);
            })
            ->whereDoesntHave('role', fn ($q) => $q->where('name', 'SUPER-ADMIN'));

        if (! empty($this->inactiveSearch)) {
            $search = $this->inactiveSearch;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($this->inactiveRoleFilter)) {
            $query->where('role_id', $this->inactiveRoleFilter);
        }

        if (! empty($this->inactiveBranchFilter)) {
            $query->where('branch_id', $this->inactiveBranchFilter);
        }

        if (! empty($this->inactiveDepartmentFilter)) {
            $query->where('department_id', $this->inactiveDepartmentFilter);
        }

        return $query;
    }

    public function render()
    {
        $cutoff30Days = Carbon::now()->subDays(30);
        $cutoff60Days = Carbon::now()->subDays(60);

        // KPI Counts for Inactive Tab
        $inactiveTotalCount = User::withoutTrashed()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $cutoff30Days))
            ->whereDoesntHave('role', fn ($q) => $q->where('name', 'SUPER-ADMIN'))
            ->count();

        $inactive60DaysCount = User::withoutTrashed()
            ->where('is_active', true)
            ->where('last_activity_at', '<', $cutoff60Days)
            ->whereDoesntHave('role', fn ($q) => $q->where('name', 'SUPER-ADMIN'))
            ->count();

        $neverActiveCount = User::withoutTrashed()
            ->where('is_active', true)
            ->whereNull('last_activity_at')
            ->whereDoesntHave('role', fn ($q) => $q->where('name', 'SUPER-ADMIN'))
            ->count();

        // Data for current tab
        $activities = null;
        $inactiveUsers = null;
        $authLogs = null;
        $apiLogs = null;

        if ($this->activeTab === 'activities') {
            $query = Activity::with(['causer', 'subject'])
                ->whereNotIn('log_name', ['api', 'webhook'])
                ->latest();

            if (! empty($this->search)) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            }

            if (! empty($this->logNameFilter)) {
                $query->where('log_name', $this->logNameFilter);
            }

            if (! empty($this->eventFilter)) {
                $query->where('event', $this->eventFilter);
            }

            if (! empty($this->causerFilter)) {
                $query->where('causer_id', $this->causerFilter)->where('causer_type', User::class);
            }

            if (! empty($this->dateFrom)) {
                $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
            }

            if (! empty($this->dateTo)) {
                $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
            }

            $activities = $query->paginate(20, ['*'], 'activityPage');
        } elseif ($this->activeTab === 'inactive') {
            $inactiveUsers = $this->getInactiveUsersQuery()
                ->with(['role', 'branch', 'department'])
                ->orderByRaw('last_activity_at IS NULL DESC, last_activity_at ASC')
                ->paginate(15, ['*'], 'inactivePage');
        } elseif ($this->activeTab === 'auth') {
            $query = Activity::with('causer')
                ->where('log_name', 'auth')
                ->latest();

            if (! empty($this->authSearch)) {
                $search = $this->authSearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('properties->ip', 'like', "%{$search}%")
                        ->orWhere('properties->credentials->email', 'like', "%{$search}%")
                        ->orWhere('properties->credentials->username', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if (! empty($this->authEventFilter)) {
                $query->where('description', 'like', "%{$this->authEventFilter}%");
            }

            $authLogs = $query->paginate(20, ['*'], 'authPage');
        } elseif ($this->activeTab === 'api') {
            $query = Activity::whereIn('log_name', ['api', 'webhook'])->latest();

            if (! empty($this->apiSearch)) {
                $search = $this->apiSearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('properties->endpoint', 'like', "%{$search}%")
                        ->orWhere('properties->ip', 'like', "%{$search}%");
                });
            }

            if (! empty($this->apiMethodFilter)) {
                $query->where('properties->method', strtoupper($this->apiMethodFilter));
            }

            if (! empty($this->apiStatusFilter)) {
                if ($this->apiStatusFilter === '2xx') {
                    $query->whereBetween('properties->status_code', [200, 299]);
                } elseif ($this->apiStatusFilter === '4xx') {
                    $query->whereBetween('properties->status_code', [400, 499]);
                } elseif ($this->apiStatusFilter === '5xx') {
                    $query->whereBetween('properties->status_code', [500, 599]);
                }
            }

            $apiLogs = $query->paginate(20, ['*'], 'apiPage');
        }

        $roles = Role::orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $usersList = User::select('id', 'name', 'username')->orderBy('name')->get();

        $logChannels = Activity::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name');

        return view('livewire.admin.user-activity-dashboard', [
            'activities' => $activities,
            'inactiveUsers' => $inactiveUsers,
            'authLogs' => $authLogs,
            'apiLogs' => $apiLogs,
            'roles' => $roles,
            'branches' => $branches,
            'departments' => $departments,
            'usersList' => $usersList,
            'logChannels' => $logChannels,
            'inactiveTotalCount' => $inactiveTotalCount,
            'inactive60DaysCount' => $inactive60DaysCount,
            'neverActiveCount' => $neverActiveCount,
        ])->layout('layouts.dashboard');
    }
}
