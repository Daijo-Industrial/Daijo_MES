<div class="space-y-6">
    <!-- Action Alerts -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-md text-sm shadow-sm"
            role="alert">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-md text-sm shadow-sm" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Roles List Panel -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-800">System Roles</h3>
                <p class="text-xs text-slate-500">Manage authorization roles and granular permissions across the platform.</p>
            </div>
            <div class="flex items-center gap-2">
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-role-modal')"
                    class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm hover:bg-slate-800 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Role
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search roles by name..."
                    class="w-full pl-10 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 transition shadow-sm" />
            </div>
        </div>

        <!-- Roles Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                        <th class="px-6 py-4">Role Name</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Assigned Permissions</th>
                        <th class="px-6 py-4 text-center">Active Users</th>
                        <th class="px-6 py-4 text-center">Total Assigned</th>
                        <th class="px-6 py-4">Created Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $role)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold font-mono tracking-wide {{ $role->isProtected() ? 'bg-violet-50 text-violet-800 border border-violet-200' : 'bg-slate-100 text-slate-800 border border-slate-200' }}">
                                        {{ $role->name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if ($role->isProtected())
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Core System
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                                        Custom Role
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if (strtoupper(trim($role->name)) === 'SUPER-ADMIN')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-200" title="Super-Admin inherently retains all system privileges">
                                        <svg class="w-3 h-3 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        All Privileges ({{ $totalAvailablePermissionsCount }})
                                    </span>
                                @else
                                    @php
                                        $assignedCount = $role->permissions_configured
                                            ? $role->permissions->count()
                                            : count(array_filter($groupedPermissions->flatten()->pluck('name')->toArray(), fn($pName) => $role->hasPermission($pName)));
                                    @endphp
                                    <button wire:click="managePermissions({{ $role->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold {{ $assignedCount > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }} transition shadow-xs"
                                        title="Configure Role Permissions">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <span>{{ $assignedCount }} / {{ $totalAvailablePermissionsCount }} permissions</span>
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center align-middle">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $role->active_users_count > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-500 border border-slate-100' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $role->active_users_count > 0 ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    {{ $role->active_users_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center align-middle font-medium text-slate-700">
                                {{ $role->total_users_count }}
                            </td>
                            <td class="px-6 py-4 align-middle text-xs text-slate-500">
                                {{ $role->created_at ? $role->created_at->format('M d, Y') : 'Initial' }}
                            </td>
                            <td class="px-6 py-4 text-right align-middle">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="managePermissions({{ $role->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:text-indigo-900 bg-indigo-50/80 hover:bg-indigo-100 px-2.5 py-1.5 rounded-md transition border border-indigo-200 shadow-sm"
                                        title="Assign or Unassign Permissions">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        Permissions
                                    </button>

                                    <button wire:click="startEditRole({{ $role->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-md transition border border-slate-200 shadow-sm"
                                        title="Edit Role Name">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>

                                    @if ($role->isProtected() || $role->total_users_count > 0)
                                        <button disabled
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 bg-slate-50 px-2.5 py-1.5 rounded-md border border-slate-200 cursor-not-allowed opacity-60"
                                            title="{{ $role->isProtected() ? 'Cannot delete core system role' : 'Cannot delete role with assigned users' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @else
                                        <button wire:click="deleteRole({{ $role->id }})"
                                            onclick="confirm('Are you sure you want to delete role \'{{ $role->name }}\'? This action cannot be undone.') || event.stopImmediatePropagation()"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1.5 rounded-md transition border border-rose-100 shadow-sm"
                                            title="Delete Role">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <p class="text-slate-500 font-medium">No roles found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-slate-100 bg-white overflow-x-auto">
            {{ $roles->links() }}
        </div>
    </div>

    <!-- Manage Permissions Modal -->
    <x-modal name="manage-permissions-modal" maxWidth="4xl" focusable>
        <div class="p-6 max-h-[85vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex justify-between items-start pb-4 border-b border-slate-100 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-800">
                            Manage Permissions: <span class="font-mono text-indigo-600">{{ $managingPermissionsRoleName }}</span>
                        </h3>
                        @if ($managingPermissionsRoleIsProtected)
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-violet-100 text-violet-800 border border-violet-200">Protected Root</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Assign or unassign authorization abilities and module gates for this role.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-slate-400 hover:text-slate-600 transition p-1">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Super Admin Notice -->
            @if ($managingPermissionsRoleIsProtected)
                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><strong>Root Account Notice:</strong> The SUPER-ADMIN role inherently retains full root access to all system gates and modules across the platform.</span>
                </div>
            @endif

            <!-- Search & Quick Action Toolbar -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-4 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.200ms="permissionSearch" type="text"
                        placeholder="Filter permissions by name, group, or keyword..."
                        class="w-full pl-9 pr-3 py-1.5 bg-white border border-slate-200 rounded-md text-xs focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition" />
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0">
                    <span class="text-xs font-semibold text-slate-600 bg-white px-2.5 py-1 rounded border border-slate-200">
                        <strong class="text-indigo-600">{{ count($selectedPermissions) }}</strong> of {{ $totalAvailablePermissionsCount }} selected
                    </span>
                    <button type="button" wire:click="selectAllPermissions"
                        class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 px-2.5 py-1 rounded border border-slate-200 transition shadow-xs">
                        Select All
                    </button>
                    <button type="button" wire:click="deselectAllPermissions"
                        class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 px-2.5 py-1 rounded border border-slate-200 transition shadow-xs">
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Scrollable Permissions Groups Container -->
            <div class="overflow-y-auto flex-1 pr-1 space-y-4 my-2">
                @forelse($groupedPermissions as $group => $perms)
                    @php
                        $groupPermIds = $perms->pluck('id')->map(fn ($id) => (int) $id)->toArray();
                        $selectedInGroupCount = count(array_intersect($groupPermIds, array_map('intval', $selectedPermissions)));
                        $allInGroupSelected = count($groupPermIds) > 0 && $selectedInGroupCount === count($groupPermIds);
                    @endphp
                    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-xs">
                        <!-- Group Header -->
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ $group }}</h4>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $selectedInGroupCount > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $selectedInGroupCount }} / {{ count($groupPermIds) }}
                                </span>
                            </div>
                            <button type="button" wire:click="toggleGroupPermissions('{{ $group }}')"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                                {{ $allInGroupSelected ? 'Deselect Group' : 'Select Group' }}
                            </button>
                        </div>

                        <!-- Group Grid -->
                        <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            @foreach($perms as $perm)
                                @php
                                    $isChecked = in_array((int) $perm->id, array_map('intval', $selectedPermissions));
                                @endphp
                                <label class="relative flex items-start p-2.5 rounded-lg border transition-all cursor-pointer select-none {{ $isChecked ? 'border-indigo-300 bg-indigo-50/40 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox"
                                            wire:model="selectedPermissions"
                                            value="{{ $perm->id }}"
                                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 transition cursor-pointer" />
                                    </div>
                                    <div class="ml-2.5 flex-1 text-left">
                                        <div class="flex items-center justify-between gap-1 flex-wrap">
                                            <span class="text-xs font-bold text-slate-800">{{ $perm->label }}</span>
                                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ $perm->name }}</span>
                                        </div>
                                        @if ($perm->description)
                                            <p class="text-[11px] text-slate-500 mt-0.5 leading-tight">{{ $perm->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <p class="text-sm font-medium">No permissions matched "{{ $permissionSearch }}".</p>
                    </div>
                @endforelse
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 mt-2 border-t border-slate-100 flex justify-between items-center gap-3">
                <span class="text-xs text-slate-500 hidden sm:inline">
                    Changes take effect immediately upon saving.
                </span>
                <div class="flex items-center gap-2 ml-auto">
                    <x-secondary-button x-on:click="$dispatch('close')" type="button">
                        Cancel
                    </x-secondary-button>
                    <button wire:click="saveRolePermissions" type="button" wire:loading.attr="disabled"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center gap-1.5 disabled:opacity-50">
                        <svg wire:loading wire:target="saveRolePermissions" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="saveRolePermissions" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Permissions
                    </button>
                </div>
            </div>
        </div>
    </x-modal>

    <!-- Create Role Modal -->
    <x-modal name="create-role-modal" :show="$errors->has('name')" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Add New Role</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Define a new authorization role for system accounts.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="createRole" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role Name</label>
                    <input wire:model="name" type="text"
                        class="w-full uppercase border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm font-mono"
                        placeholder="e.g. QUALITY-AUDITOR" />
                    @error('name')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')" type="button">
                        Cancel
                    </x-secondary-button>
                    <button type="submit"
                        class="bg-slate-900 hover:bg-slate-950 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Role
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Edit Role Modal -->
    <x-modal name="edit-role-modal" :show="$errors->has('editingRoleName')" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Edit Role</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Update the role designation name.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="updateRole" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role Name</label>
                    <input wire:model="editingRoleName" type="text"
                        class="w-full uppercase border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm font-mono" />
                    @error('editingRoleName')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')" type="button">
                        Cancel
                    </x-secondary-button>
                    <button type="submit"
                        class="bg-slate-950 hover:bg-slate-900 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm">
                        Update Role
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
