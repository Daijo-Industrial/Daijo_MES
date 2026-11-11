<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Page Header --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 gap-4 border-b border-slate-200">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Second Process Reports</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Laporan produksi harian proses sekunder, kendali mutu, dan rekonsiliasi material WIP.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('second-process-reports.part-cycle') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition"
                       title="Lihat riwayat siklus proses per part number">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Timeline Siklus Part</span>
                    </a>
                    <a href="{{ route('second-process.report-analytics', array_filter([
                        'preset' => $activePreset ?? request('preset'),
                        'date_from' => request('date_from'),
                        'date_to' => request('date_to'),
                        'unit_line' => request('unit_line'),
                        'shift' => request('shift'),
                        'process_prod' => request('process_prod'),
                        'industry' => request('industry'),
                        'customer' => request('customer'),
                        'status' => request('status'),
                        'search' => request('search'),
                        'sisa_filter' => request('sisa_filter'),
                    ], fn($v) => !is_null($v) && $v !== '')) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition"
                       title="Buka Dashboard Analitik dengan filter aktif yang sama">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Analytics Dashboard</span>
                    </a>
                    <a href="{{ route('second-process.defect-manager') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition"
                       title="Kelola Katalog Defect & Standarisasi Alias">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>Defect Catalog</span>
                    </a>
                    <a href="{{ route('second-process-reports.create') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-2xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Report</span>
                    </a>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-900 text-xs font-semibold shadow-2xs" role="alert">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Summary KPI Cards --}}
            <div id="report-summary-cards" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Reports</span>
                    <div class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($summary->total_reports) }}</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total Output</span>
                    <div class="text-2xl font-bold text-blue-600 tabular-nums">
                        {{ number_format($summary->total_output) }}
                        <span class="text-xs font-normal text-slate-400">pcs</span>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total OK</span>
                    <div class="text-2xl font-bold text-emerald-600 tabular-nums">
                        {{ number_format($summary->total_ok) }}
                        <span class="text-xs font-normal text-slate-400">pcs</span>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Total NG</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-rose-600 tabular-nums">{{ number_format($summary->total_ng) }}</span>
                        @if($summary->total_output > 0)
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 tabular-nums">
                                {{ round(($summary->total_ng / $summary->total_output) * 100, 2) }}%
                            </span>
                        @endif
                    </div>
                </div>
                {{-- Sisa Input (Open) Card --}}
                <div class="col-span-2 sm:col-span-1 bg-white border rounded-xl p-3.5 shadow-2xs {{ request('sisa_filter') && request('sisa_filter') !== 'ideal' ? 'border-amber-400 bg-amber-50/20 ring-1 ring-amber-300' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Sisa Input (Open)</span>
                        @if(request('sisa_filter') && request('sisa_filter') !== 'ideal')
                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-100 text-amber-800 uppercase">
                                {{ request('sisa_filter') }}
                            </span>
                        @endif
                    </div>
                    <div class="text-2xl font-bold tabular-nums {{ ($summary->total_sisa_input ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                        {{ number_format($summary->total_sisa_input ?? 0) }}
                        <span class="text-xs font-normal text-slate-400">pcs</span>
                    </div>
                </div>
            </div>

            {{-- Filter Bar with Progressive Disclosure (Minimized / Expanded) --}}
            <form id="report-filter-form" method="GET" action="{{ route('second-process-reports.index') }}"
                  @submit.prevent="liveApplyFilters()"
                  x-data="{
                      filtersOpen: {{ ($activePreset ?? '') === 'custom' || request()->anyFilled(['unit_line', 'shift', 'process_prod', 'industry', 'customer', 'status', 'sisa_filter', 'search']) ? 'true' : 'false' }},
                      showCustomDates: {{ ($activePreset ?? '') === 'custom' ? 'true' : 'false' }},
                      activePreset: '{{ $activePreset ?? (request('preset') === 'all' ? 'all' : 'this_month') }}',
                      setPreset(preset) {
                          setDatePreset(preset);
                      }
                  }">
                <input type="hidden" name="preset" id="filter-preset-input" value="{{ $activePreset ?? request('preset', 'this_month') }}">

                <div class="bg-white shadow-2xs border border-slate-200 rounded-xl p-3.5 transition-all">
                    {{-- Minimized Mode Strip: Active Filters & Toggle Button --}}
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        {{-- Left: Active Filter Pills --}}
                        <div id="report-active-filter-pills" class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1 mr-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filter Aktif:
                            </span>

                            {{-- 1. Date / Preset Active Pill (Default First-Landing is 'This Month') --}}
                            @php
                                $presetLabels = [
                                    'today' => 'Today',
                                    'yesterday' => 'Yesterday',
                                    'this_week' => 'This Week',
                                    'this_month' => 'This Month',
                                    'last_month' => 'Last Month',
                                    'custom' => 'Custom Range',
                                    'all' => 'All Dates',
                                ];
                                $currentPresetKey = $activePreset ?? request('preset', 'this_month');
                                $currentPresetLabel = $presetLabels[$currentPresetKey] ?? 'This Month';

                                $dateDisplay = '';
                                if ($currentPresetKey === 'all') {
                                    $dateDisplay = 'Semua Tanggal';
                                } elseif ($dateFrom && $dateTo) {
                                    if ($dateFrom === $dateTo) {
                                        $dateDisplay = \Carbon\Carbon::parse($dateFrom)->format('d M Y');
                                    } else {
                                        $dateDisplay = \Carbon\Carbon::parse($dateFrom)->format('d M') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
                                    }
                                }
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>{{ $currentPresetLabel }}@if($dateDisplay && $currentPresetKey !== 'all') <span class="font-normal text-blue-600">({{ $dateDisplay }})</span>@endif</span>
                            </span>

                            {{-- 2. Search Keyword Active Pill --}}
                            @if(request()->filled('search'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Cari:</span> "{{ request('search') }}"
                                    <button type="button" onclick="removeFilter('search')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus pencarian">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 3. Unit / Line Active Pill --}}
                            @if(request()->filled('unit_line'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Line:</span> {{ request('unit_line') }}
                                    <button type="button" onclick="removeFilter('unit_line')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter line">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 4. Shift Active Pill --}}
                            @if(request()->filled('shift'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Shift:</span> Shift {{ request('shift') }}
                                    <button type="button" onclick="removeFilter('shift')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter shift">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 5. Process Active Pill --}}
                            @if(request()->filled('process_prod'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Process:</span> {{ request('process_prod') }}
                                    <button type="button" onclick="removeFilter('process_prod')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter process">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 6. Sector Active Pill --}}
                            @if(request()->filled('industry'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Sektor:</span> {{ $industries[request('industry')] ?? request('industry') }}
                                    <button type="button" onclick="removeFilter('industry')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter sektor">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 7. Customer Active Pill --}}
                            @if(request()->filled('customer'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Customer:</span> {{ request('customer') }}
                                    <button type="button" onclick="removeFilter('customer')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter customer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 8. Status Active Pill --}}
                            @if(request()->filled('status'))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    <span class="text-slate-400 font-normal">Status:</span> {{ ucwords(str_replace('_', ' ', request('status'))) }}
                                    <button type="button" onclick="removeFilter('status')" class="text-slate-400 hover:text-slate-600 ml-0.5" title="Hapus filter status">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif

                            {{-- 9. Sisa Active Pill --}}
                            @if(request()->filled('sisa_filter'))
                                @php
                                    $sisaLabels = ['open' => 'Open', 'reconciled' => 'Tertutup', 'has_sisa' => 'Ada Sisa', 'ideal' => 'Ideal', 'deficit' => 'Defisit'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="text-amber-600 font-normal">Sisa:</span> {{ $sisaLabels[request('sisa_filter')] ?? request('sisa_filter') }}
                                    <button type="button" onclick="removeFilter('sisa_filter')" class="text-amber-600 hover:text-amber-800 ml-0.5" title="Hapus filter sisa">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            @endif
                        </div>

                        {{-- Right: Reset All (if modified) & Toggle Open/Close Button --}}
                        <div class="flex items-center gap-2 ml-auto">
                            @if(request()->anyFilled(['search', 'unit_line', 'shift', 'process_prod', 'industry', 'customer', 'status', 'sisa_filter']) || (request('preset') !== 'this_month' && request()->filled('preset')) || request()->filled('date_from') || request()->filled('date_to'))
                                <a href="{{ route('second-process-reports.index') }}"
                                   class="px-2.5 py-1 text-xs rounded-lg font-medium text-slate-500 hover:text-rose-600 hover:bg-rose-50 border border-dashed border-slate-300 transition"
                                   title="Reset semua filter ke default">
                                    Reset All
                                </a>
                            @endif

                            <button type="button" @click="filtersOpen = !filtersOpen"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition shadow-2xs"
                                    :class="filtersOpen ? 'bg-slate-100 text-slate-900 border-slate-300 ring-2 ring-slate-200' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300'">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                <span x-text="filtersOpen ? 'Tutup Filter' : 'Ubah Filter'">Ubah Filter</span>
                                <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Expanded Mode: Toggled-On Filter Controls --}}
                    <div x-show="filtersOpen" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="pt-3 mt-3 border-t border-slate-100 space-y-3">
                        {{-- Quick Date Presets Row --}}
                        @php
                            $currentActivePreset = $activePreset ?? (request('preset') === 'all' ? 'all' : request('preset', 'this_month'));
                            $presetBaseClass = 'px-2.5 py-1 text-xs rounded-lg transition border cursor-pointer select-none';
                            $presetActiveClass = 'bg-blue-600 text-white border-blue-600 shadow-xs ring-2 ring-blue-300 font-bold';
                            $presetInactiveClass = 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 hover:text-slate-900 font-medium';
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
                            <div id="quick-presets-container" class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-slate-500 uppercase flex items-center gap-1 mr-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Quick Presets:
                                </span>
                                <button type="button"
                                    data-preset="today"
                                    onclick="setDatePreset('today')"
                                    class="{{ $presetBaseClass }} {{ $currentActivePreset === 'today' ? $presetActiveClass : $presetInactiveClass }}">
                                    Today
                                </button>
                                <button type="button"
                                    data-preset="yesterday"
                                    onclick="setDatePreset('yesterday')"
                                    class="{{ $presetBaseClass }} {{ $currentActivePreset === 'yesterday' ? $presetActiveClass : $presetInactiveClass }}">
                                    Yesterday
                                </button>
                                <button type="button"
                                    data-preset="this_week"
                                    onclick="setDatePreset('this_week')"
                                    class="{{ $presetBaseClass }} {{ $currentActivePreset === 'this_week' ? $presetActiveClass : $presetInactiveClass }}">
                                    This Week
                                </button>
                                <button type="button"
                                    data-preset="this_month"
                                    onclick="setDatePreset('this_month')"
                                    class="{{ $presetBaseClass }} {{ $currentActivePreset === 'this_month' ? $presetActiveClass : $presetInactiveClass }}">
                                    This Month
                                </button>
                                <button type="button"
                                    data-preset="last_month"
                                    onclick="setDatePreset('last_month')"
                                    class="{{ $presetBaseClass }} {{ $currentActivePreset === 'last_month' ? $presetActiveClass : $presetInactiveClass }}">
                                    Last Month
                                </button>

                                {{-- Custom Date Range Button --}}
                                <button type="button"
                                    data-preset="custom"
                                    onclick="setDatePreset('custom')"
                                    class="{{ $presetBaseClass }} flex items-center gap-1 {{ $currentActivePreset === 'custom' ? $presetActiveClass : $presetInactiveClass }}">
                                    Custom Range
                                </button>

                                {{-- Custom date inputs --}}
                                <div id="custom-date-inputs-container"
                                    x-show="showCustomDates"
                                    style="{{ $currentActivePreset === 'custom' ? '' : 'display: none;' }}"
                                    class="inline-flex items-center gap-1.5 ml-1 transition-all">
                                    <input type="date" name="date_from" id="filter-date-from"
                                        value="{{ $currentActivePreset === 'custom' || (!request()->filled('preset') && request()->filled('date_from')) ? ($dateFrom ?? request('date_from', '')) : '' }}"
                                        onchange="applyCustomDateIfComplete()"
                                        class="px-2 py-0.5 border-slate-300 bg-white rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500 font-medium">
                                    <span class="text-xs text-slate-400 font-semibold">–</span>
                                    <input type="date" name="date_to" id="filter-date-to"
                                        value="{{ $currentActivePreset === 'custom' || (!request()->filled('preset') && request()->filled('date_to')) ? ($dateTo ?? request('date_to', '')) : '' }}"
                                        onchange="applyCustomDateIfComplete()"
                                        class="px-2 py-0.5 border-slate-300 bg-white rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500 font-medium">
                                </div>

                                {{-- All Dates Button --}}
                                <button type="button"
                                    data-preset="all"
                                    onclick="setDatePreset('all')"
                                    class="{{ $presetBaseClass }} ml-1 {{ $currentActivePreset === 'all' ? $presetActiveClass : $presetInactiveClass }}"
                                    title="Reset rentang tanggal ke semua">
                                    All Dates
                                </button>
                            </div>
                        </div>

                        {{-- Search & Operational Filters Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-12 gap-2.5 items-end">
                            {{-- Search Keyword Input --}}
                            <div class="sm:col-span-2 md:col-span-3 lg:col-span-4 xl:col-span-3"
                                 x-data="{ searchQuery: '{{ addslashes(request('search', '')) }}' }">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Cari Laporan</label>
                                <div class="relative">
                                    <input type="text" name="search" id="filter-search-input"
                                           x-model="searchQuery"
                                           @keydown.enter.prevent="liveApplyFilters()"
                                           placeholder="Part No, Nama Part, Model, Customer..."
                                           class="w-full text-xs font-medium rounded-lg border-slate-300 py-1.5 pl-8 pr-7 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <button type="button" x-show="searchQuery.length > 0" x-cloak
                                            @click="searchQuery = ''; document.getElementById('filter-search-input').value = '';"
                                            class="absolute right-2 top-2 text-slate-400 hover:text-slate-600" title="Hapus pencarian">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Unit / Line --}}
                            <div class="xl:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Unit / Line</label>
                                <select name="unit_line" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All Lines</option>
                                    @foreach(config('mes.sp_lines', []) as $slug => $lineName)
                                        <option value="{{ $lineName }}" {{ request('unit_line') == $lineName || request('unit_line') == $slug ? 'selected' : '' }}>
                                            {{ $lineName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Shift --}}
                            <div class="xl:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Shift</label>
                                <select name="shift" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All</option>
                                    <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                                    <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                                    <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                                </select>
                            </div>

                            {{-- Process --}}
                            <div class="xl:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Process</label>
                                <select name="process_prod" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All</option>
                                    @foreach(config('mes.sp_processes', ['Painting','Buffing','Amplas','Treatment','Packing','Rework','Repair','Assy']) as $proc)
                                        <option value="{{ $proc }}" {{ request('process_prod') == $proc ? 'selected' : '' }}>{{ $proc }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sektor Industri --}}
                            <div class="xl:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sektor</label>
                                <select name="industry" id="filter-industry" onchange="syncCustomerOptionsByIndustry(this.value)" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">Semua</option>
                                    @foreach($industries as $key => $label)
                                        <option value="{{ $key }}" {{ ($selectedIndustry ?? request('industry')) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Customer --}}
                            <div class="xl:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Customer</label>
                                <select name="customer" id="filter-customer" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $c)
                                        @php
                                            $cInd = $industryMap[$c] ?? 'GENERAL';
                                        @endphp
                                        <option value="{{ $c }}" data-industry="{{ $cInd }}" {{ request('customer') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Status --}}
                            <div class="xl:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                                <select name="status" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All</option>
                                    @foreach(['draft','submitted','pqc_approved','leader_approved','acknowledged'] as $s)
                                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sisa Filter (Clean, No-Emoji) --}}
                            <div class="xl:col-span-1">
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sisa</label>
                                <select name="sisa_filter" class="w-full border-slate-300 rounded-lg text-xs font-medium focus:ring-blue-500 focus:border-blue-500 py-1.5 bg-slate-50 focus:bg-white transition">
                                    <option value="">All</option>
                                    <option value="open" {{ request('sisa_filter') === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="reconciled" {{ request('sisa_filter') === 'reconciled' ? 'selected' : '' }}>Tertutup</option>
                                    <option value="has_sisa" {{ request('sisa_filter') === 'has_sisa' ? 'selected' : '' }}>Ada Sisa</option>
                                    <option value="ideal" {{ request('sisa_filter') === 'ideal' ? 'selected' : '' }}>Ideal</option>
                                    <option value="deficit" {{ request('sisa_filter') === 'deficit' ? 'selected' : '' }}>Defisit</option>
                                </select>
                            </div>
                        </div>

                        {{-- Action Bar: Single Batch Submission for All Filters --}}
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 mt-2">
                            <a href="{{ route('second-process-reports.index') }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition">
                                Reset
                            </a>
                            <button type="submit" id="filter-submit-btn"
                                    class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-2xs transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span>Apply Filter</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Datatable & Pagination Container --}}
            <div id="report-table-wrapper" class="space-y-4">
                <div class="relative bg-white shadow-2xs rounded-xl border border-slate-200 overflow-hidden">
                {{-- Top loading progress bar --}}
                <div id="filter-progress-bar" class="hidden absolute top-0 left-0 right-0 h-1 bg-blue-100 overflow-hidden z-30">
                    <div class="w-full h-full bg-blue-600 animate-pulse origin-left"></div>
                </div>

                {{-- Loading backdrop overlay --}}
                <div id="filter-loading-overlay" class="hidden absolute inset-0 bg-white/75 backdrop-blur-[1px] flex items-center justify-center z-20 transition-opacity">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white shadow-lg border border-slate-200 text-xs font-bold text-slate-700">
                        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memuat data laporan...</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    {{-- Datatable: Natural compact column widths without screen stretching --}}
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-3 whitespace-nowrap text-[11px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-3 py-3 whitespace-nowrap text-[11px] font-bold text-slate-500 uppercase tracking-wider">Line/Shift</th>
                                <th class="px-3 py-3 whitespace-nowrap text-[11px] font-bold text-slate-500 uppercase tracking-wider">Process</th>
                                <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Part Name / Model</th>
                                <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-3 py-3 whitespace-nowrap text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Output</th>
                                <th class="px-3 py-3 whitespace-nowrap text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">OK</th>
                                <th class="px-3 py-3 whitespace-nowrap text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">NG %</th>
                                <th class="px-3 py-3 whitespace-nowrap text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sisa Input</th>
                                <th class="px-3 py-3 whitespace-nowrap text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-3 whitespace-nowrap text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($reports as $report)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    {{-- Date --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-slate-700 font-medium">
                                        {{ \Carbon\Carbon::parse($report->date)->format('d M Y') }}
                                    </td>

                                    {{-- Line / Shift --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs">
                                        <div class="font-bold text-slate-900 truncate max-w-[120px]" title="{{ $report->unit_line }}">{{ $report->unit_line }}</div>
                                        <div class="text-[11px] text-slate-500">Shift {{ $report->shift }}</div>
                                    </td>

                                    {{-- Process --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs">
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700" title="{{ $report->process_prod }}">
                                            {{ $report->process_prod }}
                                        </span>
                                    </td>

                                    {{-- Part Name / Model --}}
                                    <td class="px-3 py-2.5 text-xs max-w-[200px] lg:max-w-[260px]">
                                        <div class="font-bold text-slate-900 truncate" title="{{ $report->part_name ?: '-' }}">
                                            {{ $report->part_name ?: '-' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 truncate" title="{{ $report->part_number }} {{ $report->model ? '('.$report->model.')' : '' }}">
                                            <span class="font-mono text-slate-700">{{ $report->part_number }}</span>
                                            @if($report->model)
                                                <span class="text-slate-400">({{ $report->model }})</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Customer & Industry --}}
                                    <td class="px-3 py-2.5 text-xs max-w-[140px] lg:max-w-[180px]">
                                        <div class="font-medium text-slate-800 truncate" title="{{ $report->customer }}">{{ $report->customer }}</div>
                                        @if(isset($industryMap[$report->customer]))
                                            <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{{ $industryMap[$report->customer] }}</div>
                                        @endif
                                    </td>

                                    {{-- Output --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-right font-mono font-semibold text-slate-900 tabular-nums">
                                        {{ number_format($report->jumlah_output) }}
                                    </td>

                                    {{-- OK --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-right font-mono font-bold text-emerald-600 tabular-nums">
                                        {{ number_format($report->jumlah_ok) }}
                                    </td>

                                    {{-- NG % --}}
                                    @php
                                        $ngClass = 'text-slate-600';
                                        if ($report->ng_prosentase >= 3) {
                                             $ngClass = 'text-rose-600 font-bold';
                                        } elseif ($report->ng_prosentase >= 1) {
                                             $ngClass = 'text-amber-600 font-semibold';
                                        }
                                    @endphp
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-right font-mono tabular-nums {{ $ngClass }}">
                                        {{ $report->ng_prosentase }}%
                                    </td>

                                    {{-- Sisa Input & Auto-Reconciliation (No-Emoji) --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-right">
                                        @php
                                            $sisa = (int) $report->sisa_input;
                                            $recon = $report->reconciliation_status;
                                        @endphp
                                        @if($sisa > 0)
                                            <div class="flex flex-col items-end gap-0.5">
                                                @if($recon['type'] === 'reconciled')
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200" title="{{ $recon['message'] }}">
                                                        +{{ number_format($sisa) }}
                                                    </span>
                                                    <a href="{{ route('second-process-reports.show', $recon['linked_report_id']) }}" class="text-[10px] font-semibold text-emerald-700 hover:text-emerald-900 underline block" title="{{ $recon['message'] }}">
                                                        Ditutup #{{ $recon['linked_report_id'] }}
                                                    </a>
                                                @else
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="{{ $report->sisa_input_remark ? 'Remark: ' . $report->sisa_input_remark : 'Sisa Material' }}">
                                                        +{{ number_format($sisa) }}
                                                    </span>
                                                    <span class="text-[10px] font-medium text-amber-700" title="{{ $recon['message'] }}">
                                                        Terbuka
                                                    </span>
                                                @endif
                                            </div>
                                        @elseif($sisa < 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Defisit Output > Input">
                                                {{ number_format($sisa) }}
                                            </span>
                                        @else
                                            @if($recon['type'] === 'absorber')
                                                <div class="flex flex-col items-end gap-0.5">
                                                    <span class="text-slate-400 font-mono">0</span>
                                                    <a href="{{ route('second-process-reports.show', $recon['linked_report_id']) }}" class="text-[10px] font-semibold text-blue-600 hover:text-blue-800 underline block" title="{{ $recon['message'] }}">
                                                        Dari #{{ $recon['linked_report_id'] }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-slate-400 font-mono">0</span>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Status Badge (Clean, No-Emoji) --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-center">
                                        @switch($report->status)
                                            @case('draft')
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wide">Draft</span>
                                                @break
                                            @case('submitted')
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">Submitted</span>
                                                @break
                                            @case('pqc_approved')
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wide">PQC</span>
                                                @break
                                            @case('leader_approved')
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-orange-50 text-orange-800 border border-orange-200 uppercase tracking-wide">Leader</span>
                                                @break
                                            @case('acknowledged')
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wide">Done</span>
                                                @break
                                            @default
                                                <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wide">{{ $report->status }}</span>
                                        @endswitch
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-3 py-2.5 whitespace-nowrap text-right text-xs font-semibold">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <a href="{{ route('second-process-reports.show', $report->id) }}" class="text-blue-600 hover:text-blue-800 transition">View</a>
                                            @can('update', $report)
                                                <a href="{{ route('second-process-reports.edit', $report->id) }}" class="text-amber-600 hover:text-amber-800 transition">Edit</a>
                                            @endcan
                                            @can('delete', $report)
                                                <button type="button" onclick="document.getElementById('delete-dialog-{{ $report->id }}').showModal()" class="text-rose-600 hover:text-rose-800 transition">
                                                    Delete
                                                </button>

                                                {{-- Delete Dialog Modal --}}
                                                <dialog id="delete-dialog-{{ $report->id }}" class="rounded-xl p-5 shadow-2xl border border-slate-200 w-full max-w-sm backdrop:bg-slate-900/40">
                                                    <form action="{{ route('second-process-reports.destroy', $report->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <h3 class="text-sm font-bold text-slate-900 mb-1.5 text-left">Confirm Delete</h3>
                                                        <p class="text-xs text-slate-500 mb-4 text-left whitespace-normal">Are you sure you want to delete report #{{ $report->id }}? This action cannot be undone.</p>
                                                        <div class="flex justify-end gap-2">
                                                            <button type="button" onclick="document.getElementById('delete-dialog-{{ $report->id }}').close()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-2xs transition">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </form>
                                                </dialog>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-10 h-10 text-slate-300 mb-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-slate-600">No reports found matching your criteria.</span>
                                            @if(request()->anyFilled(['search', 'unit_line', 'shift', 'process_prod', 'industry', 'customer', 'status', 'sisa_filter', 'date_from', 'date_to']) || request('preset') === 'custom')
                                                <a href="{{ route('second-process-reports.index') }}" class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-800 underline">
                                                    Reset all filters
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
            </div>
        </div>
    </div>

    <script>
        let liveAbortController = null;

        function showLoadingState() {
            const overlay = document.getElementById('filter-loading-overlay');
            const progressBar = document.getElementById('filter-progress-bar');
            if (overlay) overlay.classList.remove('hidden');
            if (progressBar) progressBar.classList.remove('hidden');
            const submitBtn = document.getElementById('filter-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            }
        }

        function hideLoadingState() {
            const overlay = document.getElementById('filter-loading-overlay');
            const progressBar = document.getElementById('filter-progress-bar');
            if (overlay) overlay.classList.add('hidden');
            if (progressBar) progressBar.classList.add('hidden');
            const submitBtn = document.getElementById('filter-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        async function liveApplyFilters() {
            const form = document.getElementById('report-filter-form');
            if (!form) return;

            if (liveAbortController) {
                liveAbortController.abort();
            }
            liveAbortController = new AbortController();

            showLoadingState();

            const formData = new FormData(form);
            const searchParams = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value !== null && value.toString().trim() !== '') {
                    searchParams.append(key, value.toString().trim());
                }
            }

            const queryString = searchParams.toString();
            const targetUrl = form.action + (queryString ? '?' + queryString : '');

            try {
                const response = await fetch(targetUrl, {
                    signal: liveAbortController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    form.submit();
                    return;
                }

                const htmlText = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');

                // 1. Swap table container & pagination
                const newTable = doc.getElementById('report-table-wrapper');
                const currentTable = document.getElementById('report-table-wrapper');
                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }

                // 2. Swap summary KPI cards
                const newSummary = doc.getElementById('report-summary-cards');
                const currentSummary = document.getElementById('report-summary-cards');
                if (newSummary && currentSummary) {
                    currentSummary.innerHTML = newSummary.innerHTML;
                }

                // 3. Swap active filter pills in minimized strip
                const newPills = doc.getElementById('report-active-filter-pills');
                const currentPills = document.getElementById('report-active-filter-pills');
                if (newPills && currentPills) {
                    currentPills.innerHTML = newPills.innerHTML;
                }

                // 4. Update browser URL history without full reload
                window.history.replaceState({}, '', targetUrl);

                hideLoadingState();

            } catch (err) {
                if (err.name === 'AbortError') {
                    return;
                }
                console.error('Filter request error, falling back to full submit:', err);
                form.submit();
            }
        }

        function removeFilter(fieldName) {
            const form = document.getElementById('report-filter-form');
            if (!form) return;

            if (fieldName === 'search') {
                const input = document.getElementById('filter-search-input');
                if (input) input.value = '';
            } else if (fieldName === 'industry') {
                const ind = document.getElementById('filter-industry');
                if (ind) {
                    ind.value = '';
                    syncCustomerOptionsByIndustry('');
                }
            } else {
                const el = form.querySelector(`[name="${fieldName}"]`);
                if (el) el.value = '';
            }
            liveApplyFilters();
        }

        document.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('#report-table-wrapper nav a');
            if (paginationLink && paginationLink.href) {
                e.preventDefault();
                liveFetchUrl(paginationLink.href);
            }
        });

        async function liveFetchUrl(url) {
            showLoadingState();

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) {
                    window.location.href = url;
                    return;
                }
                const htmlText = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');

                const newTable = doc.getElementById('report-table-wrapper');
                const currentTable = document.getElementById('report-table-wrapper');
                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }

                window.history.replaceState({}, '', url);

                hideLoadingState();

                document.getElementById('report-table-wrapper')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (e) {
                window.location.href = url;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                localStorage.removeItem('sp_report_filters_open');
            } catch (e) {}

            const form = document.getElementById('report-filter-form');
            if (form) {
                form.addEventListener('submit', function() {
                    showLoadingState();
                    const fromInput = document.getElementById('filter-date-from');
                    const toInput = document.getElementById('filter-date-to');
                    const presetInput = document.getElementById('filter-preset-input');
                    if (presetInput && (fromInput?.value || toInput?.value)) {
                        if (!['today', 'yesterday', 'this_week', 'this_month', 'last_month'].includes(presetInput.value)) {
                            presetInput.value = 'custom';
                        }
                    }
                });
            }

            const indSelect = document.getElementById('filter-industry');
            if (indSelect && indSelect.value) {
                syncCustomerOptionsByIndustry(indSelect.value);
            }
        });

        function syncCustomerOptionsByIndustry(industry) {
            const custSelect = document.getElementById('filter-customer');
            if (!custSelect) return;
            const options = custSelect.querySelectorAll('option');
            let hasCurrentMatch = false;
            const targetInd = industry ? industry.trim().toUpperCase() : '';

            options.forEach(opt => {
                if (!opt.value) {
                    opt.hidden = false;
                    return;
                }
                const optIndustry = (opt.getAttribute('data-industry') || '').trim().toUpperCase();
                const match = !targetInd || optIndustry === targetInd;
                opt.hidden = !match;
                if (opt.selected && match) {
                    hasCurrentMatch = true;
                }
            });

            if (!hasCurrentMatch && targetInd && custSelect.value !== '') {
                custSelect.value = '';
            }
        }

        function handleDateInputChange() {
            setDatePreset('custom');

            const fromInput = document.getElementById('filter-date-from');
            const toInput = document.getElementById('filter-date-to');
            const fromVal = fromInput ? fromInput.value : '';
            const toVal = toInput ? toInput.value : '';

            // If only 'from' is filled and 'to' is empty, move focus to 'to'
            if (fromVal && !toVal && toInput) {
                toInput.focus();
                try {
                    if (typeof toInput.showPicker === 'function') toInput.showPicker();
                } catch (e) {}
                return;
            }

            // If only 'to' is filled and 'from' is empty, move focus to 'from'
            if (!fromVal && toVal && fromInput) {
                fromInput.focus();
                try {
                    if (typeof fromInput.showPicker === 'function') fromInput.showPicker();
                } catch (e) {}
                return;
            }

            // If end date earlier than start date, reset end date
            if (fromVal && toVal && fromVal > toVal && toInput) {
                toInput.value = '';
                toInput.focus();
            }
        }

        function applyCustomDateIfComplete() {
            handleDateInputChange();
        }

        function setDatePreset(preset) {
            const activeClasses = ['bg-blue-600', 'text-white', 'border-blue-600', 'shadow-xs', 'ring-2', 'ring-blue-300', 'font-bold'];
            const inactiveClasses = ['bg-slate-50', 'text-slate-700', 'border-slate-200', 'hover:bg-slate-100', 'hover:text-slate-900', 'font-medium'];

            // 1. Update quick preset buttons styling
            const container = document.getElementById('quick-presets-container');
            if (container) {
                const buttons = container.querySelectorAll('button[data-preset]');
                buttons.forEach(btn => {
                    const btnPreset = btn.getAttribute('data-preset');
                    if (btnPreset === preset) {
                        btn.classList.remove(...inactiveClasses);
                        btn.classList.add(...activeClasses);
                    } else {
                        btn.classList.remove(...activeClasses);
                        btn.classList.add(...inactiveClasses);
                    }
                });
            }

            // 2. Update hidden preset input
            const presetInput = document.getElementById('filter-preset-input');
            if (presetInput) {
                presetInput.value = preset;
            }

            // 3. Custom Date Range inputs visibility & clear logic
            const customContainer = document.getElementById('custom-date-inputs-container');
            const fromInput = document.getElementById('filter-date-from');
            const toInput = document.getElementById('filter-date-to');

            if (preset === 'custom') {
                if (customContainer) {
                    customContainer.style.display = 'inline-flex';
                }
                if (fromInput) {
                    setTimeout(() => fromInput.focus(), 50);
                }
            } else {
                if (customContainer) {
                    customContainer.style.display = 'none';
                }
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            }

            // 4. Sync Alpine state if present
            const form = document.getElementById('report-filter-form');
            if (form && window.Alpine) {
                try {
                    const alpineData = form._x_dataStack ? form._x_dataStack[0] : Alpine.$data(form);
                    if (alpineData) {
                        alpineData.activePreset = preset;
                        alpineData.showCustomDates = (preset === 'custom');
                    }
                } catch (e) {}
            }
        }

        function clearDatePreset() {
            setDatePreset('all');
        }
    </script>
</x-app-layout>
