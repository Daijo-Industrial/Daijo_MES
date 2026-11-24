<?php

use App\Livewire\Actions\Logout;
use App\Models\MachineJob;
use App\Services\NavigationService;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $user = auth()->user();
        if ($user) {
            if ($user->role?->name === 'WORKSHOP') {
                $user->update(['username' => null]);
            } elseif ($user->role?->name === 'OPERATOR') {
                MachineJob::where('user_id', $user->id)->update(['employee_name' => null]);
            }
        }

        $logout();

        $this->redirect('/login', navigate: true);
    }

    /**
     * Get filtered navigation menu groups for current authenticated user.
     */
    public function getMenuGroups(): array
    {
        return app(NavigationService::class)->getMenuGroups(auth()->user(), false);
    }
}; ?>

<aside
    class="bg-white w-64 h-screen border-r border-gray-200
           fixed inset-y-0 left-0 flex flex-col justify-between
           transform transition-transform duration-200 ease-in-out
           z-30
           lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" x-cloak>
    {{-- TOP --}}
    <div class="px-6 py-4 flex-grow overflow-y-auto border-scrollbar">
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" wire:navigate>
                <x-application-logo class="block fill-current text-gray-800 w-16 h-16" />
            </a>
            <span class="ms-5 font-semibold text-xl">
                Daijo MES
            </span>

            {{-- Close button mobile --}}
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Plant Context / Switcher --}}
        <div class="mt-3 pt-3 pb-1 border-t border-gray-100">
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5 px-0.5">
                Plant / Branch
            </div>
            <livewire:common.plant-switcher />
        </div>

        <!-- Navigation Links -->
        <div class="space-y-2 mt-4">
            <!-- Core Dashboard Link -->
            <livewire:sidebar-link href="{{ route('dashboard') }}" label="Dashboard"
                :active="request()->routeIs('dashboard') || request()->routeIs('production-dashboard') || request()->routeIs('production-yearly-dashboard')" wire:navigate />

            <!-- Operator Daily Report (Shop floor machine operator interface) -->
            @if (auth()->user()?->can('view-operator-links') || auth()->user()?->hasRole('OPERATOR'))
                <livewire:sidebar-link href="{{ route('operator.daily-report') }}" label="Laporan Harian Mesin"
                    :active="request()->routeIs('operator.daily-report')" wire:navigate />
            @endif

            <!-- High-level System Administration Links -->
            @if (auth()->user()?->can('manage-users-roles'))
                <livewire:sidebar-link href="{{ route('admin.access-management') }}" label="Access Management"
                    :active="request()->routeIs('admin.access-management') || request()->routeIs('admin.user-role-manager')" wire:navigate />
                <livewire:sidebar-link href="{{ route('admin.activity-logs') }}" label="User Activity & Audit Logs"
                    :active="request()->routeIs('admin.activity-logs')" wire:navigate />
            @endif

            @if (auth()->user()?->can('manage-branches-departments'))
                <livewire:sidebar-link href="{{ route('admin.branches-departments') }}" label="Branches & Departments"
                    :active="request()->routeIs('admin.branches-departments') || request()->routeIs('admin.plant-departments')" wire:navigate />
            @endif

            <!-- Grouped Dropdown Navigation (Domain-driven & single source of truth) -->
            @foreach ($this->getMenuGroups() as $group)
                <livewire:parent-dropdown
                    :key="$group['label']"
                    :label="$group['label']"
                    :initiallyOpen="$group['initiallyOpen'] ?? false"
                    :childRoutes="$group['children']"
                />
            @endforeach
        </div>
    </div>

    {{-- BOTTOM USER FOOTER --}}
    <div class="p-4 border-t border-gray-200 bg-gray-50/80">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2.5 overflow-hidden">
                <div
                    class="w-9 h-9 rounded-xl bg-slate-800 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 2)) }}
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-gray-900 truncate leading-tight">
                        {{ auth()->user()?->name ?? 'Guest' }}
                    </div>
                    <div class="text-[10px] text-gray-500 font-semibold uppercase truncate">
                        {{ auth()->user()?->role?->name ?? 'User' }}
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-1 shrink-0">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate
                        class="p-2 text-gray-500 hover:text-blue-600 hover:bg-white rounded-lg transition"
                        title="Profile">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>

                    <button wire:click="logout"
                        class="px-2.5 py-1.5 text-rose-600 hover:text-white hover:bg-rose-600 bg-rose-50 border border-rose-200/80 rounded-xl transition flex items-center space-x-1 text-xs font-bold shadow-xs active:scale-95"
                        title="Log Out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                @else
                    <a href="{{ route('login') }}"
                        class="px-3 py-1.5 text-indigo-600 hover:text-white hover:bg-indigo-600 bg-indigo-50 border border-indigo-200/80 rounded-xl transition flex items-center space-x-1 text-xs font-bold shadow-xs active:scale-95"
                        title="Log In">
                        <span>Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</aside>
