<x-app-layout>
    @php
        $totalModules = collect($menuGroups ?? [])->sum(fn($g) => count($g['children'] ?? []));
        $totalGroups = count($menuGroups ?? []);
        $activePlantLabel = app(\App\Services\PlantContextService::class)->getActivePlantLabel();
    @endphp

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6"
         x-data="{
             search: '',
             isMatch(text) {
                 if (!this.search.trim()) return true;
                 return text.toLowerCase().includes(this.search.trim().toLowerCase());
             },
             groupMatches(labels) {
                 if (!this.search.trim()) return true;
                 const q = this.search.trim().toLowerCase();
                 return labels.some(l => l.toLowerCase().includes(q));
             },
             clearSearch() {
                 this.search = '';
                 $refs.searchInput.focus();
             }
         }"
         @keydown.window.prevent.slash="$refs.searchInput.focus()"
         @keydown.escape="search = ''">

        {{-- Top Context Header Banner --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Application Workspace
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $user->role?->name ?? 'User' }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Welcome back, <span class="font-semibold text-slate-800">{{ $user->name }}</span> ({{ $user->username ?? $user->email }}). Select a module below to launch.
                    </p>
                </div>

                {{-- Status & Location Badges --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-slate-700 font-medium">
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Active Plant</span>
                        <span class="font-semibold text-slate-800">{{ $activePlantLabel }}</span>
                    </div>
                    @if ($user->department)
                        <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-slate-700 font-medium">
                            <span class="text-slate-400 uppercase text-[10px] font-bold block">Department</span>
                            <span class="font-semibold text-slate-800">{{ $user->department->name }}</span>
                        </div>
                    @endif
                    <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-slate-700 font-medium">
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Access Scope</span>
                        <span class="font-semibold text-slate-800">{{ $totalModules }} {{ Str::plural('Module', $totalModules) }}</span>
                    </div>
                </div>
            </div>

            {{-- Real-time Search Input Bar --}}
            <div class="mt-5 pt-4 border-t border-slate-100">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        x-ref="searchInput"
                        x-model="search"
                        type="text"
                        placeholder="Search modules, reports, or settings... (Press '/' to focus)"
                        class="block w-full pl-10 pr-20 py-2.5 text-sm bg-slate-50 hover:bg-white focus:bg-white rounded-md border border-slate-300 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 transition-colors shadow-none text-slate-800 placeholder-slate-400"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center space-x-1.5">
                        <button
                            type="button"
                            x-show="search.length > 0"
                            @click="clearSearch()"
                            x-cloak
                            class="text-xs text-slate-400 hover:text-slate-600 px-1.5 py-0.5 rounded border border-slate-200 bg-white">
                            Clear
                        </button>
                        <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 bg-slate-100 border border-slate-200 rounded">
                            /
                        </kbd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Categorized Modules Domain Grid --}}
        @if (empty($menuGroups) || $totalModules === 0)
            <div class="bg-white rounded-lg border border-slate-200 p-8 text-center max-w-lg mx-auto shadow-sm">
                <h3 class="text-base font-bold text-slate-800">No Modules Assigned</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Your account does not currently have permissions assigned to access MES modules. Please contact your supervisor or system administrator to request system access.
                </p>
            </div>
        @else
            <div class="space-y-8">
                @foreach ($menuGroups as $group)
                    @php
                        $childLabels = collect($group['children'])->pluck('label')->push($group['label'])->toJson();
                    @endphp
                    <section
                        x-show="groupMatches({{ $childLabels }})"
                        x-transition.opacity
                        class="space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-200">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center space-x-2">
                                <span>{{ $group['label'] }}</span>
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ count($group['children']) }}
                                </span>
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach ($group['children'] as $child)
                                @php
                                    $hasRoute = Route::has($child['name']);
                                    $targetUrl = $hasRoute ? route($child['name'], $child['params'] ?? []) : '#';
                                    $searchPayload = addslashes($child['label'] . ' ' . $group['label'] . ' ' . $child['name']);
                                @endphp
                                <a
                                    href="{{ $targetUrl }}"
                                    x-show="isMatch('{{ $searchPayload }}')"
                                    x-transition.opacity
                                    wire:navigate
                                    class="group relative flex flex-col justify-between p-3.5 bg-white rounded-lg border border-slate-200 hover:border-slate-400 hover:shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1 {{ !$hasRoute ? 'opacity-60 pointer-events-none' : '' }}">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-slate-600 transition-colors">
                                                {{ $group['label'] }}
                                            </span>
                                            @if (!$hasRoute)
                                                <span class="text-[9px] px-1 py-0.2 bg-amber-50 text-amber-700 border border-amber-200 rounded">Draft</span>
                                            @endif
                                        </div>
                                        <h3 class="text-sm font-semibold text-slate-800 group-hover:text-slate-900 transition-colors mt-1 leading-snug">
                                            {{ $child['label'] }}
                                        </h3>
                                    </div>

                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 group-hover:text-slate-800">
                                        <span class="text-[11px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                            Open Screen
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
