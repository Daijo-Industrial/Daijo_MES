<div class="space-y-6">
    <!-- Notifications -->
    @if (session()->has('message'))
        <div class="p-3.5 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 rounded-md text-xs font-medium flex items-center justify-between shadow-2xs"
            role="alert">
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3.5 bg-rose-50 border-l-4 border-rose-600 text-rose-900 rounded-md text-xs font-medium flex items-center justify-between shadow-2xs"
            role="alert">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($viewMode === 'table')
        <!-- Roles List Panel -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden flex flex-col justify-between">
            <div class="p-5 border-b border-zinc-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-zinc-900">Roles</h2>
                        <span class="text-xs bg-zinc-100 text-zinc-700 px-2 py-0.5 rounded font-semibold">{{ $roles->total() }} total</span>
                    </div>
                    <p class="text-xs text-zinc-500 mt-0.5">Manage system roles and their assigned permissions.</p>
                </div>
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-role-modal')"
                    class="bg-zinc-900 text-white hover:bg-black px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition flex items-center gap-1.5 border border-zinc-800">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Role</span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="px-5 py-3 bg-zinc-50 border-b border-zinc-200 flex items-center">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search roles by name..."
                        class="w-full pl-9 bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 transition shadow-2xs" />
                </div>
            </div>

            <!-- Roles Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-zinc-50 text-zinc-600 font-semibold text-xs border-b border-zinc-200">
                            <th class="px-5 py-3.5">Role Name</th>
                            <th class="px-5 py-3.5">Type</th>
                            <th class="px-5 py-3.5">Permissions</th>
                            <th class="px-5 py-3.5 text-center">Active Users</th>
                            <th class="px-5 py-3.5 text-center">Total Users</th>
                            <th class="px-5 py-3.5">Created Date</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @forelse($roles as $role)
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="px-5 py-3.5 align-middle">
                                    <span class="font-bold text-zinc-900 text-xs">
                                        {{ $role->name }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 align-middle">
                                    @if ($role->isProtected())
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-zinc-800 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                            <svg class="w-3 h-3 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            System
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-[11px] font-semibold text-zinc-600 bg-zinc-50 px-2 py-0.5 rounded border border-zinc-200">
                                            Custom
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 align-middle">
                                    @if (strtoupper(trim($role->name)) === 'SUPER-ADMIN')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-800 bg-zinc-100 px-2.5 py-1 rounded border border-zinc-200" title="Super-Admin has all permissions">
                                            <svg class="w-3.5 h-3.5 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            All Permissions ({{ $totalAvailablePermissionsCount }})
                                        </span>
                                    @else
                                        @php
                                            $assignedCount = $role->permissions_configured
                                                ? $role->permissions->count()
                                                : count(array_filter($groupedPermissions->flatten()->pluck('name')->toArray(), fn($pName) => $role->hasPermission($pName)));
                                        @endphp
                                        <button wire:click="managePermissions({{ $role->id }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-semibold transition border shadow-2xs {{ $assignedCount > 0 ? 'bg-zinc-900 text-white hover:bg-black border-zinc-800' : 'bg-white text-zinc-600 hover:bg-zinc-100 border-zinc-300' }}"
                                            title="Configure Role Permissions">
                                            <svg class="w-3.5 h-3.5 {{ $assignedCount > 0 ? 'text-emerald-400' : 'text-zinc-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span>{{ $assignedCount }} / {{ $totalAvailablePermissionsCount }} permissions</span>
                                        </button>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center align-middle font-semibold text-xs {{ $role->active_users_count > 0 ? 'text-emerald-700' : 'text-zinc-400' }}">
                                    {{ $role->active_users_count }}
                                </td>
                                <td class="px-5 py-3.5 text-center align-middle font-semibold text-xs text-zinc-700">
                                    {{ $role->total_users_count }}
                                </td>
                                <td class="px-5 py-3.5 align-middle text-xs text-zinc-500">
                                    {{ $role->created_at ? $role->created_at->format('M d, Y') : 'Default' }}
                                </td>
                                <td class="px-5 py-3.5 text-right align-middle">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click="managePermissions({{ $role->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs"
                                            title="Configure permissions">
                                            <svg class="w-3 h-3 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            Permissions
                                        </button>

                                        <button wire:click="startEditRole({{ $role->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2 py-1 rounded border border-zinc-300 transition shadow-2xs"
                                            title="Edit role name">
                                            <svg class="w-3 h-3 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>

                                        @if ($role->isProtected() || $role->total_users_count > 0)
                                            <button disabled
                                                class="inline-flex items-center text-xs text-zinc-400 bg-zinc-50 px-2 py-1 rounded border border-zinc-200 cursor-not-allowed opacity-50"
                                                title="{{ $role->isProtected() ? 'System roles cannot be deleted' : 'Roles with assigned users cannot be deleted' }}">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <button wire:click="deleteRole({{ $role->id }})"
                                                onclick="confirm('Are you sure you want to delete role \'{{ $role->name }}\'? This action cannot be undone.') || event.stopImmediatePropagation()"
                                                class="inline-flex items-center text-xs font-semibold text-rose-700 hover:text-rose-900 bg-white hover:bg-rose-50 px-2 py-1 rounded border border-rose-300 transition shadow-2xs"
                                                title="Delete Role">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-zinc-400 text-xs">
                                        <svg class="w-8 h-8 mb-2 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <span>No roles found.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-zinc-200 bg-white overflow-x-auto">
                {{ $roles->links() }}
            </div>
        </div>
    @else
        <!-- Permission Matrix Panel (Read-Only) -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden flex flex-col">
            <div class="p-5 border-b border-zinc-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-zinc-900">Permission Matrix</h2>
                        <span class="text-xs bg-zinc-100 text-zinc-700 px-2 py-0.5 rounded font-semibold">Read-Only</span>
                    </div>
                    <p class="text-xs text-zinc-500 mt-0.5">Overview of permissions assigned to each role.</p>
                </div>
                <!-- Legend -->
                <div class="flex items-center gap-3 text-xs shrink-0 bg-zinc-50 p-2 rounded-lg border border-zinc-200">
                    <div class="flex items-center gap-1.5 font-semibold text-zinc-800">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-zinc-900 text-emerald-400 border border-zinc-900">
                            <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span>Granted</span>
                    </div>
                    <div class="flex items-center gap-1.5 font-semibold text-zinc-800">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-white text-zinc-300 border border-zinc-300">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                        </span>
                        <span class="text-zinc-500">Denied</span>
                    </div>
                    <div class="flex items-center gap-1.5 font-semibold text-zinc-800">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-zinc-100 text-zinc-700 border border-zinc-300">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <span>Super-Admin</span>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="px-5 py-3 bg-zinc-50 border-b border-zinc-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.250ms="matrixSearch" type="text"
                        placeholder="Search permissions by name, group, or keyword..."
                        class="w-full pl-9 bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 transition shadow-2xs" />
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="matrixGroupFilter"
                        class="bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs font-semibold text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs">
                        <option value="">All Groups ({{ count($availableGroups) }})</option>
                        @foreach($availableGroups as $grp)
                            <option value="{{ $grp }}">{{ $grp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Matrix 2D Grid (Read-Only) -->
            <div class="overflow-x-auto overflow-y-auto max-h-[720px] border-b border-zinc-200 relative">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="sticky top-0 z-20 bg-zinc-900 text-zinc-100 text-xs border-b border-zinc-800 shadow-xs">
                        <tr>
                            <!-- Top-Left Corner Header -->
                            <th scope="col" class="sticky left-0 z-30 bg-zinc-900 px-4 py-3 min-w-[280px] max-w-[320px] border-r border-zinc-800 font-semibold text-zinc-200">
                                <span>Permission</span>
                            </th>
                            <!-- Role Column Headers -->
                            @foreach($matrixRoles as $r)
                                @php
                                    $isRoot = strtoupper(trim($r->name)) === 'SUPER-ADMIN';
                                    $assignedCount = $rolePermissionCounts[$r->id] ?? 0;
                                @endphp
                                <th scope="col" class="px-2.5 py-3 text-center min-w-[110px] border-r border-zinc-800 select-none">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold tracking-wide {{ $isRoot ? 'bg-zinc-800 text-emerald-400 border border-zinc-700' : 'bg-zinc-800 text-zinc-200 border border-zinc-700' }}">
                                            {{ $r->name }}
                                        </span>
                                        <span class="text-[11px] font-medium {{ $assignedCount > 0 ? 'text-zinc-300' : 'text-zinc-500' }}">
                                            {{ $isRoot ? 'All' : $assignedCount }} / {{ $totalAvailablePermissionsCount }}
                                        </span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">
                        @forelse($matrixGroupedPermissions as $group => $perms)
                            <!-- Group Header Row -->
                            <tr class="bg-zinc-100 font-semibold border-t border-b border-zinc-300">
                                <td class="sticky left-0 z-10 bg-zinc-100 px-4 py-2 text-xs text-zinc-900 border-r border-zinc-300 flex items-center justify-between">
                                    <span class="font-bold">{{ $group }}</span>
                                    <span class="text-[11px] bg-zinc-200 text-zinc-700 px-1.5 py-0.5 rounded font-medium">
                                        {{ count($perms) }} permissions
                                    </span>
                                </td>
                                @foreach($matrixRoles as $r)
                                    <td class="px-1 py-1 text-center align-middle border-r border-zinc-200 bg-zinc-50/60 text-[11px] text-zinc-400 select-none">
                                        —
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Permission Rows -->
                            @foreach($perms as $perm)
                                <tr class="hover:bg-zinc-50/80 transition-colors">
                                    <!-- Left Sticky Column -->
                                    <td class="sticky left-0 z-10 bg-white px-4 py-2.5 border-r border-zinc-200 align-middle">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-semibold text-zinc-900">{{ $perm->label }}</span>
                                            <span class="text-[11px] font-mono text-zinc-500">{{ $perm->name }}</span>
                                            @if ($perm->description)
                                                <span class="text-[11px] text-zinc-400 line-clamp-1 mt-0.5" title="{{ $perm->description }}">
                                                    {{ $perm->description }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Role Columns -->
                                    @foreach($matrixRoles as $r)
                                        @php
                                            $isRoot = strtoupper(trim($r->name)) === 'SUPER-ADMIN';
                                            $hasPerm = $matrixState[$r->id][$perm->id] ?? false;
                                        @endphp
                                        <td class="px-2 py-1.5 text-center align-middle border-r border-zinc-200 select-none">
                                            @if ($isRoot)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-zinc-100 text-zinc-500 border border-zinc-300"
                                                    title="Super-Admin has all permissions">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </span>
                                            @elseif ($hasPerm)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-zinc-900 text-emerald-400 border border-zinc-900 shadow-2xs"
                                                    title="Granted to {{ $r->name }}">
                                                    <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white text-zinc-200 border border-zinc-200"
                                                    title="Denied for {{ $r->name }}">
                                                    <svg class="w-3 h-3 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ count($matrixRoles) + 1 }}" class="px-5 py-12 text-center text-zinc-500 text-xs">
                                    No permissions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Manage Permissions Modal -->
    <x-modal name="manage-permissions-modal" maxWidth="4xl" focusable>
        <div class="p-6 max-h-[85vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex justify-between items-start pb-4 border-b border-zinc-200 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-zinc-900">
                            Edit Permissions: <span class="font-semibold text-zinc-700">{{ $managingPermissionsRoleName }}</span>
                        </h3>
                        @if ($managingPermissionsRoleIsProtected)
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 border border-zinc-300">System Role</span>
                        @endif
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Select the permissions assigned to this role.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition p-1">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Root Notice -->
            @if ($managingPermissionsRoleIsProtected)
                <div class="mb-4 p-3 bg-zinc-100 border border-zinc-300 rounded-md text-xs text-zinc-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-zinc-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span><strong>System Notice:</strong> The SUPER-ADMIN role always retains full access to all permissions.</span>
                </div>
            @endif

            <!-- Search & Quick Action Toolbar -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mb-4 bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.200ms="permissionSearch" type="text"
                        placeholder="Search permissions..."
                        class="w-full pl-9 pr-3 py-1.5 bg-white border border-zinc-300 rounded-md text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition" />
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0">
                    <span class="text-xs font-semibold text-zinc-700 bg-white px-2.5 py-1 rounded border border-zinc-300">
                        <strong class="text-zinc-900">{{ count($selectedPermissions) }}</strong> of {{ $totalAvailablePermissionsCount }} selected
                    </span>
                    <button type="button" wire:click="selectAllPermissions"
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs">
                        Select All
                    </button>
                    <button type="button" wire:click="deselectAllPermissions"
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs">
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Scrollable Permissions Groups Container -->
            <div class="overflow-y-auto flex-1 pr-1 space-y-3.5 my-2">
                @forelse($groupedPermissions as $group => $perms)
                    @php
                        $groupPermIds = $perms->pluck('id')->map(fn ($id) => (int) $id)->toArray();
                        $selectedInGroupCount = count(array_intersect($groupPermIds, array_map('intval', $selectedPermissions)));
                        $allInGroupSelected = count($groupPermIds) > 0 && $selectedInGroupCount === count($groupPermIds);
                    @endphp
                    <div class="border border-zinc-200 rounded-lg overflow-hidden bg-white shadow-2xs">
                        <!-- Group Header -->
                        <div class="bg-zinc-100 px-4 py-2 border-b border-zinc-200 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs font-bold text-zinc-800">{{ $group }}</h4>
                                <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded {{ $selectedInGroupCount > 0 ? 'bg-zinc-900 text-white' : 'bg-zinc-200 text-zinc-600' }}">
                                    {{ $selectedInGroupCount }} / {{ count($groupPermIds) }}
                                </span>
                            </div>
                            <button type="button" wire:click="toggleGroupPermissions('{{ $group }}')"
                                class="text-xs font-semibold text-zinc-700 hover:text-black underline">
                                {{ $allInGroupSelected ? 'Deselect Group' : 'Select Group' }}
                            </button>
                        </div>

                        <!-- Group Grid -->
                        <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($perms as $perm)
                                @php
                                    $isChecked = in_array((int) $perm->id, array_map('intval', $selectedPermissions));
                                @endphp
                                <label class="relative flex items-start p-2.5 rounded border transition-all cursor-pointer select-none {{ $isChecked ? 'border-zinc-900 bg-zinc-50' : 'border-zinc-200 bg-white hover:bg-zinc-50' }}">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox"
                                            wire:model="selectedPermissions"
                                            value="{{ $perm->id }}"
                                            class="w-4 h-4 text-zinc-900 rounded border-zinc-300 focus:ring-zinc-900 transition cursor-pointer" />
                                    </div>
                                    <div class="ml-2.5 flex-1 text-left">
                                        <div class="flex items-center justify-between gap-1 flex-wrap">
                                            <span class="text-xs font-bold text-zinc-900">{{ $perm->label }}</span>
                                            <span class="text-[10px] font-mono px-1 py-0.5 rounded bg-zinc-100 text-zinc-600 border border-zinc-200">{{ $perm->name }}</span>
                                        </div>
                                        @if ($perm->description)
                                            <p class="text-[11px] text-zinc-500 mt-0.5 leading-tight">{{ $perm->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-zinc-400 text-xs">
                        <span>No permissions found.</span>
                    </div>
                @endforelse
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 mt-2 border-t border-zinc-200 flex justify-between items-center gap-3">
                <span class="text-xs text-zinc-500 hidden sm:inline">
                    Changes take effect immediately upon saving.
                </span>
                <div class="flex items-center gap-2 ml-auto">
                    <button x-on:click="$dispatch('close')" type="button"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 transition shadow-2xs">
                        Cancel
                    </button>
                    <button wire:click="saveRolePermissions" type="button" wire:loading.attr="disabled"
                        class="bg-zinc-900 hover:bg-black text-white font-semibold text-xs py-1.5 px-4 rounded-md transition shadow-2xs flex items-center gap-1.5 disabled:opacity-50 border border-zinc-800">
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
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-zinc-200">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Add Role</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Create a new system role.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="createRole" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Role Name</label>
                    <input wire:model="name" type="text"
                        class="w-full uppercase border border-zinc-300 rounded-md px-3 py-2 text-xs font-mono font-bold text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs"
                        placeholder="e.g. QUALITY-AUDITOR" />
                    @error('name')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 pt-3 border-t border-zinc-200 flex justify-end gap-2">
                    <button x-on:click="$dispatch('close')" type="button"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-zinc-900 hover:bg-black text-white font-semibold text-xs py-1.5 px-4 rounded-md transition shadow-2xs flex items-center gap-1.5 border border-zinc-800">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Save Role
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Edit Role Modal -->
    <x-modal name="edit-role-modal" :show="$errors->has('editingRoleName')" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-zinc-200">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Edit Role</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Update the role name.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="updateRole" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Role Name</label>
                    <input wire:model="editingRoleName" type="text"
                        class="w-full uppercase border border-zinc-300 rounded-md px-3 py-2 text-xs font-mono font-bold text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs" />
                    @error('editingRoleName')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 pt-3 border-t border-zinc-200 flex justify-end gap-2">
                    <button x-on:click="$dispatch('close')" type="button"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-zinc-900 hover:bg-black text-white font-semibold text-xs py-1.5 px-4 rounded-md transition shadow-2xs border border-zinc-800">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
