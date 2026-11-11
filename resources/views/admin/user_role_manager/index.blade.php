<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Top Command Bar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-xl border border-zinc-200 shadow-2xs">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900">Access Management</h1>
                    <p class="text-xs text-zinc-500 mt-0.5">Manage users, system roles, and access permissions.</p>
                </div>

                @php
                    $tab = $currentTab ?? request()->query('tab', 'users');
                    $routeTarget = request()->routeIs('admin.access-management') ? 'admin.access-management' : 'admin.user-role-manager';
                @endphp

                <!-- Segmented Switcher Controls -->
                <nav class="inline-flex p-1 bg-zinc-100 rounded-lg border border-zinc-200 shrink-0 self-stretch sm:self-auto" aria-label="Access Control Tabs">
                    @if (auth()->user()?->can('manage-users-roles'))
                        <a href="{{ route($routeTarget, ['tab' => 'users']) }}" wire:navigate
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all {{ $tab === 'users' || empty($tab) ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Users
                        </a>

                        <a href="{{ route($routeTarget, ['tab' => 'roles']) }}" wire:navigate
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all {{ $tab === 'roles' ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Roles
                        </a>

                        <a href="{{ route($routeTarget, ['tab' => 'permissions']) }}" wire:navigate
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all {{ $tab === 'permissions' ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Permissions
                        </a>

                        <a href="{{ route($routeTarget, ['tab' => 'matrix']) }}" wire:navigate
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all {{ $tab === 'matrix' ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Permission Matrix
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Tab View Mounts -->
            @if ($tab === 'matrix' && auth()->user()?->can('manage-users-roles'))
                <livewire:admin.role-manager :initialViewMode="'matrix'" wire:key="role-matrix-view" />
            @elseif ($tab === 'roles' && auth()->user()?->can('manage-users-roles'))
                <livewire:admin.role-manager :initialViewMode="'table'" wire:key="role-table-view" />
            @elseif ($tab === 'permissions' && auth()->user()?->can('manage-users-roles'))
                <livewire:admin.permission-manager wire:key="permission-manager-view" />
            @elseif (auth()->user()?->can('manage-users-roles'))
                <livewire:admin.user-role-manager wire:key="user-role-view" />
            @else
                <div class="bg-white p-12 text-center rounded-xl border border-zinc-200 shadow-2xs">
                    <p class="text-sm text-zinc-500">Access denied. You do not have permission to view this page.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
