<div class="py-6 px-4 sm:px-6 lg:px-8 space-y-5 max-w-7xl mx-auto">
    <!-- Clean Executive Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                <svg class="w-5 h-5 text-slate-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">User Activity &amp; Audit Logs</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Retention: 365 Days
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">System audit trail &middot; Inactive user governance</p>
            </div>
        </div>

        <!-- Segmented Navigation Controls -->
        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 w-full md:w-auto overflow-x-auto">
            <button wire:click="setTab('activities')"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap {{ $activeTab === 'activities' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Activities
            </button>

            <button wire:click="setTab('inactive')"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap {{ $activeTab === 'inactive' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span class="w-2 h-2 rounded-full {{ $inactiveTotalCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
                Inactive Users
                @if ($inactiveTotalCount > 0)
                    <span class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                        {{ $inactiveTotalCount }}
                    </span>
                @endif
            </button>

            <button wire:click="setTab('auth')"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap {{ $activeTab === 'auth' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Security &amp; Auth
            </button>

            <button wire:click="setTab('api')"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap {{ $activeTab === 'api' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                APIs &amp; Webhooks
            </button>
        </div>
    </div>

    <!-- Toast Notifications -->
    @if (session()->has('message'))
        <div class="px-4 py-3 bg-emerald-50 border border-emerald-200/80 text-emerald-800 rounded-xl text-xs font-medium shadow-xs flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="px-4 py-3 bg-rose-50 border border-rose-200/80 text-rose-800 rounded-xl text-xs font-medium shadow-xs flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 1: ACTIVITIES AUDIT TRAIL              -->
    <!-- ========================================== -->
    @if ($activeTab === 'activities')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Sleek Filter Bar -->
            <div class="p-3.5 bg-slate-50/60 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2 flex-grow">
                    <!-- Search Input -->
                    <div class="relative min-w-[200px] sm:min-w-[260px]">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search activities, users, IDs..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-white placeholder-slate-400 focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs" />
                    </div>

                    <!-- Channel Pill Dropdown -->
                    <select wire:model.live="logNameFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                        <option value="">Domain: All</option>
                        @foreach ($logChannels->filter(fn($c) => !in_array($c, ['api', 'webhook'])) as $channel)
                            <option value="{{ $channel }}">{{ strtoupper($channel) }}</option>
                        @endforeach
                    </select>

                    <!-- Event Type Dropdown -->
                    <select wire:model.live="eventFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                        <option value="">Event: All</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                    </select>

                    <!-- User Filter Dropdown -->
                    <select wire:model.live="causerFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs max-w-[180px] truncate">
                        <option value="">User: All</option>
                        @foreach ($usersList as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>

                    <!-- Compact Dates -->
                    <div class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-500 bg-white px-2.5 py-1 rounded-xl border border-slate-200 shadow-2xs">
                        <input type="date" wire:model.live="dateFrom" class="p-0 border-0 text-[11px] text-slate-700 focus:ring-0" />
                        <span class="text-slate-300">&rarr;</span>
                        <input type="date" wire:model.live="dateTo" class="p-0 border-0 text-[11px] text-slate-700 focus:ring-0" />
                    </div>
                </div>

                @if ($search || $logNameFilter || $eventFilter || $causerFilter || $dateFrom || $dateTo)
                    <button wire:click="clearActivityFilters" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-200/50">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Domain</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($activities as $act)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Time -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-800">{{ $act->created_at->diffForHumans(null, true) }} ago</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $act->created_at->format('d M H:i:s') }}</span>
                                </td>

                                <!-- User -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($act->causer)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] flex items-center justify-center flex-shrink-0 border border-slate-200">
                                                {{ strtoupper(substr($act->causer->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800 leading-tight">{{ $act->causer->name }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $act->causer->username ?: $act->causer->email }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                            System
                                        </span>
                                    @endif
                                </td>

                                <!-- Domain -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-mono text-[11px] font-semibold text-slate-600 uppercase">
                                        {{ $act->log_name }}
                                    </span>
                                </td>

                                <!-- Action Event -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @php
                                        $dotColor = match($act->event) {
                                            'created' => 'bg-emerald-500',
                                            'updated' => 'bg-amber-500',
                                            'deleted' => 'bg-rose-500',
                                            default => 'bg-slate-400'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                        {{ ucfirst($act->event ?: 'Action') }}
                                    </span>
                                </td>

                                <!-- Description -->
                                <td class="py-3 px-4 max-w-sm truncate">
                                    <span class="font-medium text-slate-800" title="{{ $act->description }}">{{ $act->description }}</span>
                                    @if ($act->subject_type)
                                        <span class="text-[10px] text-slate-400 font-mono ml-1">
                                            ({{ class_basename($act->subject_type) }} #{{ $act->subject_id }})
                                        </span>
                                    @endif
                                </td>

                                <!-- Details -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if (!empty($act->properties) && count($act->properties) > 0)
                                        <button wire:click="viewDetails({{ $act->id }})"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition hover:underline">
                                            View Changes &rarr;
                                        </button>
                                    @else
                                        <span class="text-slate-300">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    No activity logs found matching the filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3.5 border-t border-slate-100 bg-white">
                {{ $activities->links() }}
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 2: INACTIVE USERS (>30 DAYS)           -->
    <!-- ========================================== -->
    @if ($activeTab === 'inactive')
        <div class="space-y-4">
            <!-- Minimalist KPI Summary Strip -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-amber-200/70 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Inactive &gt; 30 Days</div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 mt-0.5">{{ $inactiveTotalCount }}</div>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        Eligible for deactivation
                    </span>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-rose-200/70 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Inactive &gt; 60 Days</div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-rose-600 mt-0.5">{{ $inactive60DaysCount }}</div>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                        High priority
                    </span>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Never Logged In</div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-700 mt-0.5">{{ $neverActiveCount }}</div>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                        Zero activity
                    </span>
                </div>
            </div>

            <!-- Inactive Table Panel -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Filters Bar -->
                <div class="p-3.5 bg-slate-50/60 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex flex-wrap items-center gap-2 flex-grow">
                        <div class="relative min-w-[200px] sm:min-w-[260px]">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" wire:model.live.debounce.300ms="inactiveSearch" placeholder="Search inactive users..."
                                class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-white placeholder-slate-400 focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs" />
                        </div>

                        <select wire:model.live="inactiveRoleFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                            <option value="">Role: All</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="inactiveBranchFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                            <option value="">Plant: All</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="inactiveDepartmentFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                            <option value="">Dept: All</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bulk Action CTA -->
                    @if (count($selectedUsers) > 0)
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-600 bg-slate-200/70 px-2.5 py-1 rounded-lg">
                                {{ count($selectedUsers) }} selected
                            </span>
                            <button wire:click="confirmBulkDeactivate"
                                class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Deactivate Selected
                            </button>
                        </div>
                    @elseif ($inactiveSearch || $inactiveRoleFilter || $inactiveBranchFilter || $inactiveDepartmentFilter)
                        <button wire:click="clearInactiveFilters" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-200/50">
                            Clear
                        </button>
                    @endif
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 w-10 text-center">
                                    <input type="checkbox" wire:model.live="selectAll" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                                </th>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Role &amp; Plant</th>
                                <th class="py-3 px-4">Last Activity</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($inactiveUsers as $user)
                                <tr class="hover:bg-slate-50/70 transition-colors {{ in_array((string)$user->id, $selectedUsers) ? 'bg-amber-50/40' : '' }}">
                                    <td class="py-3 px-4 text-center">
                                        <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user->id }}"
                                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold text-[11px] flex items-center justify-center flex-shrink-0 border border-slate-200">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                                <div class="text-[11px] text-slate-400 font-mono">{{ $user->username ?: $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800">{{ $user->role?->name ?: 'No Role' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $user->branch?->name ?: 'All Plants' }} &bull; {{ $user->department?->name ?: 'No Dept' }}</div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if ($user->last_activity_at)
                                            <span class="font-medium text-slate-800">{{ $user->last_activity_at->diffForHumans(null, true) }} ago</span>
                                            <span class="block text-[10px] text-slate-400 font-mono">{{ $user->last_activity_at->format('d M Y') }}</span>
                                        @else
                                            <span class="text-slate-400 italic">Never logged in</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if ($user->last_activity_at)
                                            @php
                                                $days = (int) $user->last_activity_at->diffInDays(now());
                                            @endphp
                                            @if ($days >= 60)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    ⛔ {{ $days }}d idle
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    ⚠️ {{ $days }}d idle
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                💤 Never
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <button wire:click="confirmDeactivate({{ $user->id }})"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition hover:underline">
                                            Deactivate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        ✓ All active users have had activity in the last 30 days.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-3.5 border-t border-slate-100 bg-white">
                    {{ $inactiveUsers->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 3: AUTH & SECURITY LOGS               -->
    <!-- ========================================== -->
    @if ($activeTab === 'auth')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-3.5 bg-slate-50/60 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2 flex-grow">
                    <div class="relative min-w-[200px] sm:min-w-[260px]">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="authSearch" placeholder="Search IP, email, user..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-white placeholder-slate-400 focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs" />
                    </div>

                    <select wire:model.live="authEventFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                        <option value="">Event: All</option>
                        <option value="logged in">Login Success</option>
                        <option value="logged out">Logout</option>
                        <option value="Failed login">Failed Login</option>
                        <option value="lockout">Lockout</option>
                    </select>
                </div>

                @if ($authSearch || $authEventFilter)
                    <button wire:click="clearAuthFilters" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-200/50">
                        Clear
                    </button>
                @endif
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">Event</th>
                            <th class="py-3 px-4">User / Identifier</th>
                            <th class="py-3 px-4">Client IP</th>
                            <th class="py-3 px-4">Device / Client</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($authLogs as $log)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-800">{{ $log->created_at->diffForHumans(null, true) }} ago</span>
                                    <span class="block text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('d M H:i:s') }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if (str_contains($log->description, 'logged in'))
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Login Success
                                        </span>
                                    @elseif (str_contains($log->description, 'logged out'))
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                            Logout
                                        </span>
                                    @elseif (str_contains($log->description, 'Failed login'))
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Failed Login
                                        </span>
                                    @elseif (str_contains($log->description, 'lockout'))
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Lockout
                                        </span>
                                    @else
                                        <span class="text-slate-600">{{ $log->description }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($log->causer)
                                        <div class="font-semibold text-slate-900 leading-tight">{{ $log->causer->name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $log->causer->email }}</div>
                                    @else
                                        <span class="font-mono text-slate-800">
                                            {{ $log->properties['credentials']['email'] ?? $log->properties['credentials']['username'] ?? $log->description }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-700">
                                    {{ $log->properties['ip'] ?? '&mdash;' }}
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate text-[11px] text-slate-400 font-mono" title="{{ $log->properties['user_agent'] ?? '' }}">
                                    {{ $log->properties['user_agent'] ?? '&mdash;' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    No authentication logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3.5 border-t border-slate-100 bg-white">
                {{ $authLogs->links() }}
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 4: INBOUND APIS & WEBHOOKS             -->
    <!-- ========================================== -->
    @if ($activeTab === 'api')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-3.5 bg-slate-50/60 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2 flex-grow">
                    <div class="relative min-w-[200px] sm:min-w-[260px]">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="apiSearch" placeholder="Search endpoint, IP..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-slate-200 bg-white placeholder-slate-400 focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-2xs" />
                    </div>

                    <select wire:model.live="apiMethodFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                        <option value="">Method: All</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>

                    <select wire:model.live="apiStatusFilter" class="py-1.5 pl-3 pr-7 text-xs rounded-xl border-slate-200 bg-white font-medium text-slate-700 shadow-2xs">
                        <option value="">Status: All</option>
                        <option value="2xx">2xx Success</option>
                        <option value="4xx">4xx Client Error</option>
                        <option value="5xx">5xx Server Error</option>
                    </select>
                </div>

                @if ($apiSearch || $apiMethodFilter || $apiStatusFilter)
                    <button wire:click="clearApiFilters" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-200/50">
                        Clear
                    </button>
                @endif
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">Endpoint</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Latency</th>
                            <th class="py-3 px-4">Caller IP</th>
                            <th class="py-3 px-4 text-right">Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($apiLogs as $log)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-800">{{ $log->created_at->diffForHumans(null, true) }} ago</span>
                                    <span class="block text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('d M H:i:s') }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-mono">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 mr-1.5">
                                        {{ $log->properties['method'] ?? 'REQ' }}
                                    </span>
                                    <span class="font-semibold text-slate-900">{{ $log->properties['endpoint'] ?? $log->description }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @php
                                        $code = (int) ($log->properties['status_code'] ?? 200);
                                        $badge = match(true) {
                                            $code >= 200 && $code < 300 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            $code >= 400 && $code < 500 => 'bg-amber-50 text-amber-700 border-amber-200',
                                            $code >= 500 => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }}">
                                        {{ $code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-500">
                                    {{ $log->properties['duration_ms'] ?? 0 }}ms
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-700">
                                    {{ $log->properties['ip'] ?? '&mdash;' }}
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button wire:click="viewDetails({{ $log->id }})"
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition hover:underline">
                                        View Payload &rarr;
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    No inbound API or webhook logs recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3.5 border-t border-slate-100 bg-white">
                {{ $apiLogs->links() }}
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- MODERN MODAL: ACTIVITY / PAYLOAD DETAILS   -->
    <!-- ========================================== -->
    @if ($showDetailModal && $selectedActivity)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-xl border border-slate-200/90 overflow-hidden" @click.away="$wire.closeDetailModal()">
                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">Event Details &middot; #{{ $selectedActivity->id }}</h3>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 uppercase">
                                {{ $selectedActivity->log_name }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $selectedActivity->description }} &bull; {{ $selectedActivity->created_at->format('d M Y H:i:s') }}</p>
                    </div>
                    <button wire:click="closeDetailModal" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Sub-Navigation Pills -->
                @php
                    $hasDiff = isset($selectedActivity->properties['attributes']) || isset($selectedActivity->properties['old']);
                @endphp
                @if ($hasDiff)
                    <div class="px-5 pt-3 pb-0 border-b border-slate-100 flex gap-2">
                        <button wire:click="setModalTab('diff')"
                            class="pb-2 text-xs font-semibold border-b-2 transition-all {{ $detailModalTab === 'diff' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                            Field Changes
                        </button>
                        <button wire:click="setModalTab('json')"
                            class="pb-2 text-xs font-semibold border-b-2 transition-all {{ $detailModalTab === 'json' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                            Raw JSON
                        </button>
                    </div>
                @endif

                <!-- Modal Content -->
                <div class="p-5 max-h-[65vh] overflow-y-auto space-y-3">
                    @if ($detailModalTab === 'diff' && $hasDiff)
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden text-xs">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                    <tr>
                                        <th class="py-2 px-3">Field</th>
                                        <th class="py-2 px-3 text-rose-700">Old Value</th>
                                        <th class="py-2 px-3 text-emerald-700">New Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @php
                                        $newAttrs = $selectedActivity->properties['attributes'] ?? [];
                                        $oldAttrs = $selectedActivity->properties['old'] ?? [];
                                        $allKeys = array_unique(array_merge(array_keys($newAttrs), array_keys($oldAttrs)));
                                    @endphp
                                    @foreach ($allKeys as $key)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-2 px-3 font-semibold text-slate-700 font-mono text-[11px]">{{ $key }}</td>
                                            <td class="py-2 px-3 text-rose-900 font-mono text-[11px] break-all">
                                                {{ is_array($oldAttrs[$key] ?? null) ? json_encode($oldAttrs[$key]) : ($oldAttrs[$key] ?? '&mdash;') }}
                                            </td>
                                            <td class="py-2 px-3 text-emerald-900 font-mono text-[11px] font-bold break-all">
                                                {{ is_array($newAttrs[$key] ?? null) ? json_encode($newAttrs[$key]) : ($newAttrs[$key] ?? '&mdash;') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div x-data="{ copied: false }" class="relative">
                            <button @click="navigator.clipboard.writeText($refs.jsonPayload.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                                class="absolute right-3 top-3 text-[11px] font-semibold text-slate-400 hover:text-white bg-slate-800 px-2 py-1 rounded-md transition">
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied" class="text-emerald-400">Copied!</span>
                            </button>
                            <pre x-ref="jsonPayload" class="bg-slate-950 text-slate-300 p-4 rounded-xl text-[11px] font-mono overflow-x-auto leading-relaxed">{{ json_encode($selectedActivity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                    <button wire:click="closeDetailModal" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- DIRECT MODAL: CONFIRM DEACTIVATE USER     -->
    <!-- ========================================== -->
    @if ($showDeactivateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full shadow-xl border border-slate-200/90 overflow-hidden" @click.away="$set('showDeactivateModal', false)">
                <div class="p-5 text-center space-y-2.5">
                    <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Deactivate User Account?</h3>
                    <p class="text-xs text-slate-500 leading-normal">
                        Deactivating <strong class="text-slate-800">{{ $userToDeactivateName }}</strong> will prevent them from logging in. You can reactivate them anytime in User Management.
                    </p>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-2">
                    <button wire:click="$set('showDeactivateModal', false)" class="px-3 py-1.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button wire:click="deactivateUser" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        Deactivate
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- DIRECT MODAL: CONFIRM BULK DEACTIVATE      -->
    <!-- ========================================== -->
    @if ($showBulkDeactivateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full shadow-xl border border-slate-200/90 overflow-hidden" @click.away="$set('showBulkDeactivateModal', false)">
                <div class="p-5 text-center space-y-2.5">
                    <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Deactivate {{ count($selectedUsers) }} Selected Users?</h3>
                    <p class="text-xs text-slate-500 leading-normal">
                        All selected accounts will be marked inactive and unable to access the system until manually reactivated.
                    </p>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-2">
                    <button wire:click="$set('showBulkDeactivateModal', false)" class="px-3 py-1.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button wire:click="bulkDeactivate" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        Confirm Deactivation
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
