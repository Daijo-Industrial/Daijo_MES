<x-app-layout>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 11px; }
            .max-w-7xl { max-width: 100% !important; padding: 0 !important; }
            .shadow-sm, .shadow { box-shadow: none !important; }
            .border { border-color: #e2e8f0 !important; }
            .break-inside-avoid { break-inside: avoid; }
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Second Process Daily Reports Analytics') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Historical performance analysis, defect breakdown, material reconciliation, and line comparison
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 no-print">
                <button type="button" onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-3.5 rounded shadow-sm text-xs transition flex items-center gap-1.5 border border-gray-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Report
                </button>
                <a href="{{ route('second-process-reports.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3.5 rounded shadow-sm text-xs transition flex items-center gap-1">
                    Back to Reports List
                </a>
                <a href="{{ route('second-process.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3.5 rounded shadow-sm text-xs transition flex items-center gap-1">
                    Floor Live Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filter Bar --}}
            <form id="analytics-filter-form" method="GET" action="{{ route('second-process.report-analytics') }}" class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 no-print space-y-4">
                {{-- Row 1: Quick Range Presets & Action Buttons --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-xs font-bold text-gray-500 uppercase mr-1">Quick Range:</span>
                        <button type="button" onclick="setDatePreset('{{ now('Asia/Jakarta')->format('Y-m-d') }}', '{{ now('Asia/Jakarta')->format('Y-m-d') }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-700 transition">
                            Today
                        </button>
                        <button type="button" onclick="setDatePreset('{{ now('Asia/Jakarta')->startOfWeek()->format('Y-m-d') }}', '{{ now('Asia/Jakarta')->endOfWeek()->format('Y-m-d') }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-700 transition">
                            This Week
                        </button>
                        <button type="button" onclick="setDatePreset('{{ now('Asia/Jakarta')->startOfMonth()->format('Y-m-d') }}', '{{ now('Asia/Jakarta')->format('Y-m-d') }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-700 transition">
                            This Month
                        </button>
                        <button type="button" onclick="setDatePreset('{{ now('Asia/Jakarta')->copy()->subMonth()->startOfMonth()->format('Y-m-d') }}', '{{ now('Asia/Jakarta')->copy()->subMonth()->endOfMonth()->format('Y-m-d') }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-700 transition">
                            Last Month
                        </button>
                    </div>

                    {{-- Actions (Apply, Reset, CSV Export) --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded-lg text-xs shadow transition">
                            Apply Filter
                        </button>
                        <a href="{{ route('second-process.report-analytics') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3 rounded-lg text-xs border border-gray-300 transition">
                            Reset
                        </a>
                        {{-- Multi-Option CSV Export Dropdown --}}
                        <div class="relative" x-data="{ openExport: false }">
                            <button type="button" @click="openExport = !openExport" @click.away="openExport = false"
                                class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs shadow transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Export CSV</span>
                                <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="openExport" x-cloak
                                class="absolute right-0 mt-1.5 w-64 bg-white rounded-lg shadow-xl border border-gray-200 py-1.5 z-50 text-xs text-gray-700">
                                <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                    Report Summary
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv', 'material_type' => null]) }}"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 text-gray-800 transition">
                                    <div>
                                        <div class="font-bold">Reports Summary CSV</div>
                                        <div class="text-[10px] text-gray-500">Output, OK, NG, Target, Yield</div>
                                    </div>
                                </a>
                                <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-t border-b border-gray-100 mt-1">
                                    Materials Breakdown
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['export' => 'materials', 'material_type' => null]) }}"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 text-gray-800 transition">
                                    <div>
                                        <div class="font-bold">All Materials CSV</div>
                                        <div class="text-[10px] text-gray-500">Parts & Paints itemized rows</div>
                                    </div>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['export' => 'materials', 'material_type' => 'paint']) }}"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 text-gray-800 transition">
                                    <div>
                                        <div class="font-bold">Paint & Chemicals Only</div>
                                        <div class="text-[10px] text-gray-500">Viscosity, ratio, usage per 1k pcs</div>
                                    </div>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['export' => 'materials', 'material_type' => 'part']) }}"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 text-gray-800 transition">
                                    <div>
                                        <div class="font-bold">Part / WIP Only</div>
                                        <div class="text-[10px] text-gray-500">Input WIP, part yield, line variance</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Filter Form Inputs --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                    {{-- Date From --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">From Date</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    {{-- Date To --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">To Date</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    {{-- Unit / Line --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Unit / Line</label>
                        <select name="unit_line" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Lines</option>
                            @foreach($lines as $l)
                                <option value="{{ $l }}" {{ request('unit_line') == $l ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Shift --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Shift</label>
                        <select name="shift" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Shifts</option>
                            <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                            <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                            <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                        </select>
                    </div>
                    {{-- Process --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Process</label>
                        <select name="process_prod" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Processes</option>
                            @foreach($processes as $p)
                                <option value="{{ $p }}" {{ request('process_prod') == $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Sektor / Industry --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Sektor / Industri</label>
                        <select name="industry" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Sektor</option>
                            @foreach($industries as $key => $label)
                                <option value="{{ $key }}" {{ ($selectedIndustry ?? request('industry')) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Status --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            @foreach(['draft', 'submitted', 'pqc_approved', 'leader_approved', 'acknowledged'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- NG Remark Category --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">NG Category</label>
                        <select name="ng_category" onchange="filterByNgCategory(this.value)" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            @foreach($ngCategories as $cat)
                                <option value="{{ $cat }}" {{ ($selectedNgCategory ?? request('ng_category')) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            {{-- Summary KPI Cards --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Production & Quality Performance</h3>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Card 1: Total Reports --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Reports</div>
                            <div class="text-3xl font-black text-gray-900 mt-1">{{ number_format($summary->total_reports) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Submitted & recorded</div>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl no-print">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                    </div>

                    {{-- Card 2: Target vs Actual (Production Output) --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Production vs Target</div>
                            <div class="text-3xl font-black text-blue-600 mt-1 flex items-baseline gap-2">
                                {{ number_format($summary->total_output) }}
                                @if($targetAchievementRate !== null)
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $targetAchievementRate >= 100 ? 'bg-emerald-100 text-emerald-800' : ($targetAchievementRate >= 85 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $targetAchievementRate }}% Plan
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                        No Target Set
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">
                                Target: <span class="font-semibold text-gray-600">{{ number_format($summary->total_target) }}</span> units
                            </div>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl no-print">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                    </div>

                    {{-- Card 3: Total OK & Yield Rate --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total OK Qty</div>
                            <div class="text-3xl font-black text-green-600 mt-1 flex items-baseline gap-2">
                                {{ number_format($summary->total_ok) }}
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-800">
                                    {{ $yieldRate }}% Yield
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Good quality output</div>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-xl no-print">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>

                    {{-- Card 4: Total NG & NG Rate --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total NG & Rate</div>
                            <div class="text-3xl font-black text-red-600 mt-1 flex items-baseline gap-2">
                                {{ number_format($summary->total_ng) }}
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $avgNgRate >= 3 ? 'bg-red-100 text-red-800' : ($avgNgRate >= $targetNgRate ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $avgNgRate }}%
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Defect count (Target: &le;{{ $targetNgRate }}%)</div>
                        </div>
                        <div class="p-3 bg-red-50 text-red-600 rounded-xl no-print">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                    </div>
                </div>

                {{-- Summary KPI Cards: Row 2 - Material Balance, Scrap & Loss Time --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-2">Material Balance, Reconciliation & Availability</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    {{-- Total Input WIP --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Input WIP</div>
                            <div class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($summary->total_input_wip) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Raw WIP fed into line</div>
                        </div>
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl no-print">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                    </div>

                    {{-- Total Repairan --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Repairan</div>
                            <div class="text-2xl font-black text-purple-600 mt-1">{{ number_format($summary->total_repairan) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Rework / repair fed</div>
                        </div>
                        <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl no-print">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                    </div>

                    {{-- Total Scrap --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Scrap (Lebur)</div>
                            <div class="text-2xl font-black text-rose-600 mt-1 flex items-baseline gap-1.5">
                                {{ number_format($summary->total_scrap) }}
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800">
                                    {{ $scrapRate }}%
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Scrap / input ratio</div>
                        </div>
                        <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl no-print">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </div>
                    </div>

                    {{-- WIP Reconciliation / Variance --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">WIP Reconciliation</div>
                            <div class="text-2xl font-black mt-1 flex items-baseline gap-2">
                                @if($wipVariance === 0)
                                    <span class="text-emerald-600">0</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                        Balanced
                                    </span>
                                @elseif($wipVariance > 0)
                                    <span class="text-amber-600">+{{ number_format($wipVariance) }}</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800" title="Parts fed but not yet completed/scrapped">
                                        In-Line WIP
                                    </span>
                                @else
                                    <span class="text-rose-600">{{ number_format($wipVariance) }}</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800" title="Output exceeds fed WIP">
                                        Excess Output
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Inflow vs (Output + Scrap)</div>
                        </div>
                        <div class="p-2.5 bg-gray-100 text-gray-600 rounded-xl no-print">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        </div>
                    </div>

                    {{-- Total Downtime --}}
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between break-inside-avoid">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total Downtime</div>
                            <div class="text-2xl font-black text-slate-700 mt-1 flex items-baseline gap-1.5">
                                {{ number_format($totalDowntimeMinutes) }}<span class="text-sm font-bold text-slate-500">m</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                    {{ $totalDowntimeHours }} hrs
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Total recorded loss time</div>
                        </div>
                        <div class="p-2.5 bg-slate-100 text-slate-700 rounded-xl no-print">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Row 1: Daily Production Trend & Daily NG Rate --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Chart 1: Daily Output Stacked --}}
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Daily Production Volume (OK vs NG)</h3>
                        <span class="text-xs text-gray-400">Stacked Bar</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="chartDailyTrend"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Daily NG Rate Trend --}}
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Daily NG Rate Trend (%)</h3>
                        <span class="text-xs text-gray-400">Target Line: {{ $targetNgRate }}%</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="chartDailyNgRate"></canvas>
                    </div>
                </div>
            </div>

            {{-- Charts Row 2: Output by Line & Top NG Categories (Pareto) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Chart 3: Line Output Comparison --}}
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Output & Quality by Line / Area</h3>
                        <span class="text-xs text-gray-400">Grouped Bar</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="chartByLine"></canvas>
                    </div>
                </div>

                {{-- Chart 4: NG Defects Pareto --}}
                <div id="top-ng-card" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm scroll-mt-24">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-800 text-sm">Top NG Defects (Pareto Analysis)</h3>
                                @if(!empty($selectedNgCategory))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                        {{ $selectedNgCategory }}
                                        <a href="{{ request()->fullUrlWithQuery(['ng_category' => null]) }}#top-ng-card" class="hover:text-purple-900 ml-0.5 font-black leading-none" title="Clear category filter">&times;</a>
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400">
                                @if(!empty($selectedNgCategory))
                                    Defect Qty &amp; Cumulative % for <strong>{{ $selectedNgCategory }}</strong>
                                @else
                                    Defect Qty &amp; Cumulative % across all categories
                                @endif
                            </span>
                        </div>
                        {{-- Category Filter Select on Card --}}
                        <div class="flex items-center gap-1.5 no-print">
                            <label for="pareto-ng-category" class="text-xs font-semibold text-gray-500 whitespace-nowrap">Filter:</label>
                            <select id="pareto-ng-category" class="text-xs border-gray-300 rounded-lg py-1 px-2 focus:ring-purple-500 focus:border-purple-500 bg-white shadow-sm"
                                onchange="filterByNgCategory(this.value)">
                                <option value="">All Categories</option>
                                @foreach($ngCategories as $cat)
                                    <option value="{{ $cat }}" {{ ($selectedNgCategory ?? request('ng_category')) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Category Breakdown Summary Badges --}}
                    @if(!empty($categoryBreakdown))
                        <div class="flex flex-wrap items-center gap-1.5 mb-3 no-print">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mr-0.5">Distribution:</span>
                            <a href="{{ request()->fullUrlWithQuery(['ng_category' => null]) }}#top-ng-card"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold transition {{ empty($selectedNgCategory) ? 'bg-gray-800 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}"
                                title="Show all defect categories">
                                <span>All:</span>
                                <span>{{ number_format(array_sum(array_column($categoryBreakdown, 'qty'))) }}</span>
                            </a>
                            @foreach($categoryBreakdown as $catName => $catData)
                                <a href="{{ request()->fullUrlWithQuery(['ng_category' => $selectedNgCategory == $catName ? null : $catName]) }}#top-ng-card"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold transition {{ $selectedNgCategory == $catName ? 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}"
                                    title="Filter by {{ $catName }}">
                                    <span>{{ $catName }}:</span>
                                    <span>{{ number_format($catData['qty']) }}</span>
                                    <span class="text-[10px] {{ $selectedNgCategory == $catName ? 'text-purple-200' : 'text-gray-400' }}">({{ $catData['percentage'] }}%)</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="relative h-72">
                        <canvas id="chartTopNg"></canvas>
                    </div>
                </div>
            </div>

            {{-- Charts Row 3: Output by Shift & Downtime by Category --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Chart 5: Output by Shift --}}
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Production Share by Shift</h3>
                        <span class="text-xs text-gray-400">Doughnut</span>
                    </div>
                    <div class="relative h-72 flex justify-center">
                        <canvas id="chartByShift"></canvas>
                    </div>
                </div>

                {{-- Chart 6: Downtime by Category --}}
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Process Downtime (Loss Minutes)</h3>
                        <span class="text-xs text-gray-400">Horizontal Bar</span>
                    </div>
                    <div class="relative h-72">
                        <canvas id="chartDowntime"></canvas>
                    </div>
                </div>
            </div>

            {{-- Section 4: Top Production & Defect Parts Table (Dual Tab) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden break-inside-avoid" x-data="{ rankingTab: 'volume' }">
                <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm" x-text="rankingTab === 'volume' ? 'Top Production Parts by Volume' : 'Top Defect / High-Risk Parts (Quality Alert)'"></h3>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="rankingTab === 'volume' ? 'Ranked by total production volume in selected range' : 'Ranked by highest reject count (NG Qty) to prioritize corrective actions'"></p>
                    </div>
                    <div class="flex items-center bg-gray-100 p-1 rounded-lg border border-gray-200 no-print">
                        <button type="button" @click="rankingTab = 'volume'"
                            :class="rankingTab === 'volume' ? 'bg-white text-blue-600 font-bold shadow-sm' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                            class="px-3 py-1 rounded-md text-xs transition flex items-center gap-1">
                            <span>Top Volume</span>
                        </button>
                        <button type="button" @click="rankingTab = 'defects'"
                            :class="rankingTab === 'defects' ? 'bg-white text-red-600 font-bold shadow-sm' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                            class="px-3 py-1 rounded-md text-xs transition flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            <span>Top Defects / High Risk</span>
                        </button>
                    </div>
                </div>

                {{-- Tab 1: Top by Volume Table --}}
                <div class="overflow-x-auto" x-show="rankingTab === 'volume'">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Rank</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Part Number</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Part Name</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Customer</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Output</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">OK Qty</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">NG Qty</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">NG Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($topProductsRaw as $index => $prod)
                                @php
                                    $pNgRate = $prod->total_output > 0 ? round(($prod->total_ng / $prod->total_output) * 100, 2) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-sm font-bold text-gray-400">#{{ $index + 1 }}</td>
                                    <td class="px-5 py-3 text-sm font-bold text-blue-600">{{ $prod->part_number }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-800">{{ $prod->part_name }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-500">{{ $prod->customer }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-black text-gray-900">{{ number_format($prod->total_output) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-bold text-green-600">{{ number_format($prod->total_ok) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-bold text-red-600">{{ number_format($prod->total_ng) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-bold {{ $pNgRate >= 3 ? 'text-red-500' : ($pNgRate >= $targetNgRate ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ $pNgRate }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-gray-400 text-sm">
                                        No production volume data found for the selected filter range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tab 2: Top by Defects Table --}}
                <div class="overflow-x-auto" x-show="rankingTab === 'defects'" style="display: none;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-red-50/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-red-700 uppercase">Rank</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Part Number</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Part Name</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Customer</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Output</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">OK Qty</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-red-700 uppercase">Defects (NG)</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-red-700 uppercase">NG Rate (%)</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Scrap (Lebur)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($topDefectProductsRaw as $idx => $defProd)
                                @php
                                    $dNgRate = $defProd->total_output > 0 ? round(($defProd->total_ng / $defProd->total_output) * 100, 2) : 0;
                                @endphp
                                <tr class="hover:bg-red-50/30 transition">
                                    <td class="px-5 py-3 text-sm font-bold text-red-500">#{{ $idx + 1 }}</td>
                                    <td class="px-5 py-3 text-sm font-bold text-blue-600">{{ $defProd->part_number }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-800">{{ $defProd->part_name }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-500">{{ $defProd->customer }}</td>
                                    <td class="px-5 py-3 text-sm text-right text-gray-700 font-semibold">{{ number_format($defProd->total_output) }}</td>
                                    <td class="px-5 py-3 text-sm text-right text-green-600 font-semibold">{{ number_format($defProd->total_ok) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-black text-red-600">{{ number_format($defProd->total_ng) }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-black {{ $dNgRate >= 3 ? 'text-red-600' : 'text-amber-600' }}">
                                        {{ $dNgRate }}%
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right text-rose-600 font-bold">{{ number_format($defProd->total_scrap) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-8 text-center text-gray-400 text-sm">
                                        No quality defect records found for the selected filter range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Section 5: Top Downtime Incidents & Countermeasures Table --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Top Downtime Incidents & Countermeasures</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Highest loss-time occurrences with registered solutions</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">
                        Total: {{ number_format($totalDowntimeMinutes) }} mins ({{ $totalDowntimeHours }} hrs)
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">#</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date & Line</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Part Information</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Category</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Problem (Masalah)</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase">Loss Time</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Countermeasure (Penanganan)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($topTroubles as $idx => $trouble)
                                @php
                                    $cat = $trouble->category ?: ($trouble->penyebab ?: 'Other');
                                    $badgeColor = match(strtolower($cat)) {
                                        'mesin' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'man', 'manpower' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'material', 'part' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'metode', 'method' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                        default => 'bg-gray-100 text-gray-800 border-gray-200'
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-sm font-bold text-gray-400">#{{ $idx + 1 }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-800 whitespace-nowrap">
                                        @if($trouble->report)
                                            <div class="font-semibold">{{ \Carbon\Carbon::parse($trouble->report->date)->format('d M Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $trouble->report->unit_line }} (Shift {{ $trouble->report->shift }})</div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm">
                                        @if($trouble->report)
                                            <div class="font-bold text-blue-600">{{ $trouble->report->part_number }}</div>
                                            <div class="text-xs text-gray-600 truncate max-w-xs">{{ $trouble->report->part_name }}</div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-sm whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $badgeColor }}">
                                            {{ $cat }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm font-medium text-gray-900 max-w-xs break-words">
                                        {{ $trouble->masalah ?: ($trouble->penyebab ?: '-') }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-black text-red-600 whitespace-nowrap">
                                        {{ $trouble->loss_time_minutes ?? 0 }} <span class="text-xs font-normal text-gray-500">mins</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-700 max-w-md break-words">
                                        {{ $trouble->penanganan ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-400 text-sm">
                                        No downtime incidents recorded for the selected filter range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Section 6: Materials Consumption & Efficiency Analytics --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden break-inside-avoid">
                <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                            <span>Materials Consumption &amp; Efficiency Analytics</span>
                            <span class="text-[10px] bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded border border-indigo-200">Chemicals &amp; WIP</span>
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">Summary of paint/chemical consumables and component part utilization across filtered reports</p>
                    </div>
                    <div class="flex items-center gap-2 no-print">
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'materials', 'material_type' => 'paint']) }}"
                            class="text-xs text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 font-bold py-1 px-2.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-xs">
                            <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export Paint CSV</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'materials', 'material_type' => 'part']) }}"
                            class="text-xs text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 font-bold py-1 px-2.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-xs">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export Parts CSV</span>
                        </a>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6 bg-gray-50/40">
                    {{-- Left Card: Paint & Chemical Consumables --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b pb-3 mb-4">
                                <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                                    <span>Paint & Chemicals Usage</span>
                                </h4>
                                <span class="text-[11px] font-semibold text-gray-500">
                                    Process: Painting ({{ number_format($totalOkInPainting) }} Pcs OK)
                                </span>
                            </div>

                            {{-- Paint Stat KPIs --}}
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-purple-50/60 border border-purple-100 rounded-lg p-3">
                                    <div class="text-[11px] font-semibold text-purple-700">Total Paint Consumed</div>
                                    <div class="text-xl font-extrabold text-purple-900 mt-1">
                                        {{ number_format($totalPaintQty, 2) }} <span class="text-xs font-normal text-purple-600">Qty</span>
                                    </div>
                                </div>
                                <div class="bg-indigo-50/60 border border-indigo-100 rounded-lg p-3">
                                    <div class="text-[11px] font-semibold text-indigo-700">Specific Usage Index</div>
                                    <div class="text-xl font-extrabold text-indigo-900 mt-1">
                                        {{ $paintIndexPer1000 > 0 ? number_format($paintIndexPer1000, 2) : '-' }}
                                        <span class="text-xs font-normal text-indigo-600">Qty / 1k OK</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Top Paint Items Table --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-bold text-gray-500 uppercase">Item Paint</th>
                                            <th class="px-2 py-2 text-center font-bold text-gray-500 uppercase">UOM</th>
                                            <th class="px-2 py-2 text-center font-bold text-gray-500 uppercase">Batches</th>
                                            <th class="px-3 py-2 text-right font-bold text-gray-500 uppercase">Total Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($topPaints as $paint)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 font-semibold text-gray-800">{{ $paint->item_name }}</td>
                                                <td class="px-2 py-2 text-center text-gray-500 font-mono">{{ $paint->uom ?: '-' }}</td>
                                                <td class="px-2 py-2 text-center text-gray-600 font-mono">{{ $paint->reports_count }}</td>
                                                <td class="px-3 py-2 text-right font-black text-purple-900 font-mono">{{ number_format($paint->total_qty, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-3 py-6 text-center text-gray-400">
                                                    No paint materials recorded in this period.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Right Card: Component Parts & WIP Reconciliation --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b pb-3 mb-4">
                                <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                    <span>Component Parts & WIP Breakdown</span>
                                </h4>
                                <span class="text-[11px] font-semibold text-gray-500">
                                    Total Input: {{ number_format($totalInput) }} Pcs
                                </span>
                            </div>

                            {{-- Part Stat KPIs --}}
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-blue-50/60 border border-blue-100 rounded-lg p-3">
                                    <div class="text-[11px] font-semibold text-blue-700">Part Yield Rate</div>
                                    <div class="text-xl font-extrabold text-blue-900 mt-1">
                                        {{ $yieldRate }}%
                                    </div>
                                </div>
                                <div class="bg-amber-50/60 border border-amber-100 rounded-lg p-3">
                                    <div class="text-[11px] font-semibold text-amber-700">WIP Line Variance</div>
                                    <div class="text-xl font-extrabold {{ $wipVariance == 0 ? 'text-green-700' : 'text-amber-900' }} mt-1">
                                        {{ $wipVariance > 0 ? '+' . number_format($wipVariance) : number_format($wipVariance) }}
                                        <span class="text-xs font-normal text-gray-500">Pcs</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Top Part Items Table --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-bold text-gray-500 uppercase">Part / WIP Item</th>
                                            <th class="px-2 py-2 text-center font-bold text-gray-500 uppercase">UOM</th>
                                            <th class="px-2 py-2 text-center font-bold text-gray-500 uppercase">Reports</th>
                                            <th class="px-3 py-2 text-right font-bold text-gray-500 uppercase">Total Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($topParts as $part)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 font-semibold text-gray-800">{{ $part->item_name }}</td>
                                                <td class="px-2 py-2 text-center text-gray-500 font-mono">{{ $part->uom ?: 'Pcs' }}</td>
                                                <td class="px-2 py-2 text-center text-gray-600 font-mono">{{ $part->reports_count }}</td>
                                                <td class="px-3 py-2 text-right font-black text-blue-900 font-mono">{{ number_format($part->total_qty) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-3 py-6 text-center text-gray-400">
                                                    No part materials recorded in this period.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Chart JS Initialization --}}
    <script>
        function setDatePreset(from, to) {
            const fromInput = document.querySelector('input[name="date_from"]');
            const toInput = document.querySelector('input[name="date_to"]');
            if (fromInput && toInput) {
                fromInput.value = from;
                toInput.value = to;
                const form = document.getElementById('analytics-filter-form');
                if (form) form.submit();
            }
        }
        function filterByNgCategory(val) {
            const mainSelect = document.querySelector('select[name="ng_category"]');
            if (mainSelect) {
                mainSelect.value = val;
            }
            const form = document.getElementById('analytics-filter-form');
            if (form) {
                form.action = "{{ route('second-process.report-analytics') }}#top-ng-card";
                form.submit();
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-center view to Top NG Defects (Pareto Analysis) card if category filter was changed or applied
            if (window.location.hash === '#top-ng-card' || @json(!empty($selectedNgCategory))) {
                const card = document.getElementById('top-ng-card');
                if (card) {
                    setTimeout(function() {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 150);
                }
            }

            if (typeof window.Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }

            const dailyTrendData = @json($dailyTrend);
            const byLineData = @json($byLine);
            const topNgData = @json($topNg);
            const byShiftData = @json($byShift);
            const downtimeData = @json($downtime);

            // Chart 1: Daily Production Stacked Bar
            new window.Chart(document.getElementById('chartDailyTrend'), {
                type: 'bar',
                data: {
                    labels: dailyTrendData.labels,
                    datasets: [
                        {
                            label: 'OK Qty',
                            data: dailyTrendData.ok,
                            backgroundColor: '#22c55e',
                            borderRadius: 4,
                        },
                        {
                            label: 'NG Qty',
                            data: dailyTrendData.ng,
                            backgroundColor: '#ef4444',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            const targetNgRate = {{ $targetNgRate }};

            // Chart 2: Daily NG Rate Line
            new window.Chart(document.getElementById('chartDailyNgRate'), {
                type: 'line',
                data: {
                    labels: dailyTrendData.labels,
                    datasets: [
                        {
                            label: 'NG Rate (%)',
                            data: dailyTrendData.ng_rate,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#ef4444'
                        },
                        {
                            label: 'Target Limit (' + targetNgRate + '%)',
                            data: new Array(dailyTrendData.labels.length).fill(targetNgRate),
                            borderColor: '#f59e0b',
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => v + '%' } }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            // Chart 3: Line Output Comparison
            new window.Chart(document.getElementById('chartByLine'), {
                type: 'bar',
                data: {
                    labels: byLineData.labels,
                    datasets: [
                        {
                            label: 'OK Qty',
                            data: byLineData.ok,
                            backgroundColor: '#3b82f6',
                            borderRadius: 4,
                        },
                        {
                            label: 'NG Qty',
                            data: byLineData.ng,
                            backgroundColor: '#f97316',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            // Chart 4: Top NG Defects Pareto (Bar + Cumulative Line)
            new window.Chart(document.getElementById('chartTopNg'), {
                type: 'bar',
                data: {
                    labels: topNgData.labels.length ? topNgData.labels : ['No Defects'],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Cumulative %',
                            data: topNgData.cumulative_pct.length ? topNgData.cumulative_pct : [0],
                            borderColor: '#8b5cf6',
                            backgroundColor: 'transparent',
                            yAxisID: 'y1',
                            tension: 0.2,
                            pointRadius: 4
                        },
                        {
                            type: 'bar',
                            label: 'Defect Qty',
                            data: topNgData.values.length ? topNgData.values : [0],
                            backgroundColor: '#ec4899',
                            borderRadius: 4,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: { display: true, text: 'Defect Qty' }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            grid: { drawOnChartArea: false },
                            ticks: { callback: v => v + '%' },
                            title: { display: true, text: 'Cumulative %' }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            // Chart 5: Output by Shift Doughnut
            new window.Chart(document.getElementById('chartByShift'), {
                type: 'doughnut',
                data: {
                    labels: byShiftData.labels.length ? byShiftData.labels : ['Shift 1', 'Shift 2', 'Shift 3'],
                    datasets: [{
                        data: byShiftData.output.length ? byShiftData.output : [0, 0, 0],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Chart 6: Downtime by Category Horizontal Bar
            new window.Chart(document.getElementById('chartDowntime'), {
                type: 'bar',
                data: {
                    labels: downtimeData.labels.length ? downtimeData.labels : ['No Downtime'],
                    datasets: [{
                        label: 'Downtime (Minutes)',
                        data: downtimeData.minutes.length ? downtimeData.minutes : [0],
                        backgroundColor: '#64748b',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, title: { display: true, text: 'Minutes' } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</x-app-layout>
