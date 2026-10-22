<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">User & Role Management</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Control access rights, assign user accounts, and configure system roles.</p>
                    </div>
                    <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 py-1 px-3 rounded-full border border-indigo-100">Super-Admin System Portal</span>
                </div>

                @php
                    $tab = $currentTab ?? request()->query('tab', 'users');
                @endphp

                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6 space-x-4">
                    @if (auth()->user()?->can('manage-users-roles'))
                        <a href="{{ route('admin.user-role-manager', ['tab' => 'users']) }}" wire:navigate
                            class="pb-3 px-2 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'users' || empty($tab) ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            User Accounts
                        </a>

                        <a href="{{ route('admin.user-role-manager', ['tab' => 'roles']) }}" wire:navigate
                            class="pb-3 px-2 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $tab === 'roles' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Roles Management
                        </a>
                    @endif
                </div>

                @if ($tab === 'roles' && auth()->user()?->can('manage-users-roles'))
                    <livewire:admin.role-manager />
                @elseif (auth()->user()?->can('manage-users-roles'))
                    <livewire:admin.user-role-manager />
                @else
                    <div class="text-sm text-gray-500 py-8 text-center">
                        You do not have permission to view this section.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
