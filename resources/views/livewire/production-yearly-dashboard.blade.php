<div class="p-4 sm:p-5 w-full space-y-3 font-sans text-slate-800 dark:text-slate-100">

    <!-- 1. Executive Header Bar & Global Filters (Sticky Top) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 p-3.5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Title & Scope -->
            <div class="flex items-center gap-3">
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                            Executive Production Dashboard (Tahunan)
                        </h1>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Dewan Direksi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Evaluasi tahunan kinerja output produksi, pencapaian target, dan mutu pabrik
                    </p>
                </div>
            </div>

            <!-- Integrated Filter Controls & Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Year Selector -->
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Tahun:</label>
                    <select wire:model.live="year"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs focus:ring-1 focus:ring-blue-500">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Plant Selector -->
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Plant:</label>
                    <select wire:model.live="plant"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Plant</option>
                        <option value="karawang">Karawang</option>
                        <option value="kbn">KBN</option>
                    </select>
                </div>

                <!-- Machine Selector -->
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Mesin:</label>
                    <select wire:model.live="machineUserId"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs focus:ring-1 focus:ring-blue-500 max-w-[150px]">
                        <option value="">Semua Mesin</option>
                        @foreach ($machines as $m)
                            <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($plant || $machineUserId || $year != now()->year)
                    <button type="button" wire:click="resetFilters" title="Reset Filter"
                            class="px-2 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 transition">
                        Reset
                    </button>
                @endif

                <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

                <!-- Link to App Workspace -->
                <a href="{{ route('dashboard') }}" title="Kembali ke Workspace Aplikasi"
                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>App</span>
                </a>

                <!-- Link to Monthly -->
                <a href="{{ route('production-dashboard') }}" title="Lihat Dashboard Bulanan"
                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Bulanan</span>
                </a>

                <!-- Refresh -->
                <button type="button" wire:click="refreshData" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition disabled:opacity-50">
                    <svg wire:loading.remove wire:target="refreshData" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg wire:loading wire:target="refreshData" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Refresh</span>
                </button>

                <!-- Print -->
                <button type="button" onclick="window.print()" title="Cetak Laporan"
                        class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Row 1: Executive KPI Strip & Strategic Ticker -->
    <div class="w-full space-y-2">
        <!-- 4 Core Metric Cards (Full Width Grid) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            <!-- 1. Total Target -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400 block">Total Target Tahunan</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-mono mt-0.5">
                        {{ number_format($summary['total_target']) }}
                        <span class="text-xs font-normal text-slate-400 font-sans">Pcs</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ $summary['year'] }}
                    </span>
                </div>
            </div>

            <!-- 2. Total Actual Output -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400 block">Total Output Aktual</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-blue-600 dark:text-blue-400 font-mono mt-0.5">
                        {{ number_format($summary['total_actual']) }}
                        <span class="text-xs font-normal text-slate-400 font-sans">Pcs</span>
                    </div>
                </div>
                <div class="text-right">
                    @php $achv = $summary['overall_achievement_rate']; @endphp
                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold {{ $achv >= 90 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : ($achv >= 80 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300') }}">
                        {{ $achv >= 90 ? 'Tercapai' : ($achv >= 80 ? 'Optimal' : 'Evaluasi') }}
                    </span>
                </div>
            </div>

            <!-- 3. Rasio Pencapaian -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">Rasio Pencapaian (% Achievement)</span>
                    <span class="text-base font-extrabold font-mono {{ $achv >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($achv >= 80 ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600 dark:text-rose-400') }}">
                        {{ $achv }}%
                    </span>
                </div>
                <div class="mt-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $achv >= 90 ? 'bg-emerald-500' : ($achv >= 80 ? 'bg-blue-500' : 'bg-rose-500') }}" style="width: {{ min(100, $achv) }}%"></div>
                </div>
            </div>

            <!-- 4. Tingkat Cacat -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400 block">Tingkat Cacat (NG Rate Tahunan)</span>
                    <div class="text-xl sm:text-2xl font-extrabold font-mono mt-0.5 {{ $summary['overall_ng_rate'] <= 2.5 ? 'text-slate-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $summary['overall_ng_rate'] }}%
                        <span class="text-xs font-normal text-slate-400 font-sans">({{ number_format($summary['total_ng']) }} pcs)</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold {{ $summary['overall_ng_rate'] <= 2.5 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300' }}">
                        {{ $summary['overall_ng_rate'] <= 2.5 ? 'Aman' : 'Tinggi' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Strategic Highlights Ticker Bar -->
        <div class="bg-slate-900 text-white rounded-xl px-4 py-2 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs w-full">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block animate-pulse"></span>
                <span class="text-slate-400">Status Kesehatan:</span>
                <span class="font-bold text-emerald-300">{{ $summary['health_status'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400">Bulan Puncak Output:</span>
                <span class="font-bold text-white">{{ $summary['best_month_name'] }}</span>
                <span class="text-slate-400">({{ number_format($summary['best_month_output']) }} pcs)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400">Mesin Beroperasi:</span>
                <span class="font-bold text-white">{{ $summary['total_machines'] }} Unit Mesin</span>
            </div>
            @if (!empty($topMachines[0]))
                <div class="flex items-center gap-2">
                    <span class="text-slate-400">Top Performer:</span>
                    <span class="font-bold text-blue-300">{{ $topMachines[0]['machine_name'] }}</span>
                    <span class="text-slate-400">({{ $topMachines[0]['achievement_rate'] }}% / {{ number_format($topMachines[0]['actual']) }} pcs)</span>
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Dual-Column Cockpit Layout (50% - 50% Full-Width Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 w-full items-start">

        <!-- COLUMN 1 (LEFT): Perkembangan Kinerja Bulanan (12 Bulan / Jan - Des) -->
        <div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">
                        Perkembangan Kinerja Bulanan (12 Bulan / Jan - Des {{ $summary['year'] }})
                    </h2>
                    <p class="text-[11px] text-slate-500">Perbandingan target vs realisasi aktual dan rasio cacat per bulan</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="w-2.5 h-2.5 bg-slate-300 dark:bg-slate-600 rounded-xs inline-block"></span>
                        <span>Target</span>
                    </span>
                    <span class="flex items-center gap-1 font-medium">
                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-xs inline-block"></span>
                        <span>≥90% (Tercapai)</span>
                    </span>
                    <span class="flex items-center gap-1 font-medium">
                        <span class="w-2.5 h-2.5 bg-blue-500 rounded-xs inline-block"></span>
                        <span>80-89% (Kurang)</span>
                    </span>
                    <span class="flex items-center gap-1 font-medium">
                        <span class="w-2.5 h-2.5 bg-rose-500 rounded-xs inline-block"></span>
                        <span>&lt;80% (Kritis)</span>
                    </span>
                </div>
            </div>

            <!-- Visual Bar Comparison Strip (Compact 12-Month Bar) -->
            @php
                $maxMonthVal = 1;
                foreach ($monthlyMetrics as $m) {
                    if ($m['target'] > $maxMonthVal) $maxMonthVal = $m['target'];
                    if ($m['actual'] > $maxMonthVal) $maxMonthVal = $m['actual'];
                }
            @endphp
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2.5 border border-slate-100 dark:border-slate-800">
                <div class="grid grid-cols-12 gap-1.5 items-end h-16 w-full px-1">
                    @foreach ($monthlyMetrics as $mm)
                        @php
                            $targetH = ($mm['target'] / $maxMonthVal) * 100;
                            $actualH = ($mm['actual'] / $maxMonthVal) * 100;
                            $isCurMonth = ($summary['year'] == now()->year && $mm['month_number'] == now()->month);
                            $ach = $mm['achievement_rate'];
                            if ($mm['actual'] == 0 && $mm['target'] == 0) {
                                $barColor = 'bg-slate-200 dark:bg-slate-700';
                            } elseif ($ach >= 90.0) {
                                $barColor = 'bg-emerald-500';
                            } elseif ($ach >= 80.0) {
                                $barColor = 'bg-blue-500';
                            } else {
                                $barColor = 'bg-rose-500';
                            }
                        @endphp
                        <div class="flex flex-col items-center h-full justify-end" title="{{ $mm['month_name'] }}: Target {{ number_format($mm['target']) }} | Aktual {{ number_format($mm['actual']) }} ({{ $mm['achievement_rate'] }}%)">
                            <div class="flex items-end gap-1 h-11 w-full justify-center">
                                <div class="w-2 bg-slate-300 dark:bg-slate-600 rounded-t-xs" style="height: {{ max(6, $targetH) }}%"></div>
                                <div class="w-2 {{ $barColor }} rounded-t-xs" style="height: {{ max(6, $actualH) }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold mt-1 text-center truncate w-full {{ $isCurMonth ? 'text-blue-600 font-extrabold' : 'text-slate-500' }}">
                                {{ $mm['month_short'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Complete 12-Month Table (Full Width & Zero Horizontal Scroll) -->
            <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                <table class="w-full table-fixed text-xs divide-y divide-slate-200 dark:divide-slate-700 text-left">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            <th class="py-2 px-2 w-[16%]">Bulan</th>
                            <th class="py-2 px-1.5 text-right w-[15%]">Target</th>
                            <th class="py-2 px-1.5 text-right w-[15%]">Aktual</th>
                            <th class="py-2 px-1.5 text-right w-[14%]">Gap</th>
                            <th class="py-2 px-1 text-center w-[13%]">% Achv</th>
                            <th class="py-2 px-1.5 text-right w-[14%]">NG (Pcs)</th>
                            <th class="py-2 px-1.5 text-center w-[13%]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach ($monthlyMetrics as $mm)
                            @php
                                $gap = $mm['actual'] - $mm['target'];
                                $isCur = ($summary['year'] == now()->year && $mm['month_number'] == now()->month);
                                $ach = $mm['achievement_rate'];
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition {{ $isCur ? 'bg-blue-50/50 dark:bg-blue-950/20 font-semibold' : '' }}">
                                <td class="py-1.5 px-2 font-medium text-slate-800 dark:text-white truncate">
                                    {{ $mm['month_name'] }}
                                </td>
                                <td class="py-1.5 px-1.5 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ number_format($mm['target']) }}
                                </td>
                                <td class="py-1.5 px-1.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ number_format($mm['actual']) }}
                                </td>
                                <td class="py-1.5 px-1.5 text-right font-mono {{ $gap >= 0 ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold' }}">
                                    {{ $gap > 0 ? '+' : '' }}{{ number_format($gap) }}
                                </td>
                                <td class="py-1.5 px-1 text-center font-mono font-bold {{ $ach >= 90.0 ? 'text-emerald-600 dark:text-emerald-400' : ($ach >= 80.0 ? 'text-blue-600 dark:text-blue-400' : ($mm['actual'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400')) }}">
                                    {{ $ach }}%
                                </td>
                                <td class="py-1.5 px-1.5 text-right font-mono {{ $mm['ng_pcs'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
                                    {{ number_format($mm['ng_pcs']) }}
                                    <span class="text-[10px] text-slate-400 font-sans block">({{ $mm['ng_rate'] }}%)</span>
                                </td>
                                <td class="py-1.5 px-1.5 text-center">
                                    @if ($mm['status'] === 'Tercapai')
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                                            Tercapai
                                        </span>
                                    @elseif ($mm['status'] === 'Kurang')
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            Kurang
                                        </span>
                                    @elseif ($mm['status'] === 'Kritis')
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300">
                                            Kritis
                                        </span>
                                    @elseif ($mm['status'] === 'Tidak Ada Jadwal')
                                        <span class="text-[10px] text-slate-400">-</span>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">{{ $mm['status'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COLUMN 2 (RIGHT): Top 5 Defect QA + Machine Breakdown Leaderboard -->
        <div class="w-full space-y-4">

            <!-- Card A: Analisis 5 Kategori Defect Terbesar (Top 5 QA Pareto) -->
            <div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 p-4 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">
                            Analisis 5 Kategori Defect Terbesar (Top 5 QA)
                        </h2>
                        <p class="text-[11px] text-slate-500">Pareto masalah mutu part cetak sepanjang tahun {{ $summary['year'] }}</p>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                        Quality Focus
                    </span>
                </div>

                @if (!empty($topDefects))
                    <div class="space-y-2">
                        @foreach ($topDefects as $td)
                            <div class="p-2 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2 min-w-0 w-[55%]">
                                    <span class="w-5 h-5 rounded text-xs font-extrabold flex items-center justify-center shrink-0 {{ $td['rank'] <= 2 ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                                        {{ $td['rank'] }}
                                    </span>
                                    <span class="font-bold text-slate-800 dark:text-white truncate" title="{{ $td['type_name'] }}">
                                        {{ $td['type_name'] }}
                                    </span>
                                </div>
                                <div class="w-[45%] flex items-center justify-end gap-3 text-right">
                                    <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ number_format($td['quantity']) }} pcs
                                    </span>
                                    <span class="text-rose-600 dark:text-rose-400 font-extrabold font-mono text-xs w-11 text-right">
                                        {{ $td['percentage'] }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center text-xs text-slate-400">
                        Tidak ada data reject yang tercatat pada tahun ini.
                    </div>
                @endif
            </div>

            <!-- Card B: Matriks Produktivitas & Efisiensi Mesin (Breakdown Per Mesin) -->
            <div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 p-4 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">
                            Matriks Produktivitas & Efisiensi Mesin
                        </h2>
                        <p class="text-[11px] text-slate-500">Pecah per unit mesin (Total {{ count($machineMetrics) }} unit)</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        Leaderboard
                    </span>
                </div>

                <!-- Scrollable Table Container (Fixed height to prevent vertical page stretching) -->
                <div class="overflow-y-auto max-h-[290px] rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="w-full table-fixed text-xs divide-y divide-slate-200 dark:divide-slate-700 text-left">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                <th class="py-2 px-2.5 w-[30%]">Mesin</th>
                                <th class="py-2 px-2 text-right w-[22%]">Target</th>
                                <th class="py-2 px-2 text-right w-[22%]">Aktual</th>
                                <th class="py-2 px-1 text-center w-[14%]">% Achv</th>
                                <th class="py-2 px-2 text-center w-[12%]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @forelse ($machineMetrics as $mm)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-1.5 px-2.5 font-bold text-slate-800 dark:text-white truncate">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $mm['machine_name'] }}</span>
                                            <span class="text-[9px] px-1 py-0.2 rounded font-semibold uppercase {{ $mm['plant'] === 'Karawang' ? 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                                {{ $mm['plant'] === 'Karawang' ? 'KRW' : 'KBN' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-2 text-right font-mono text-slate-500">
                                        {{ number_format($mm['target']) }}
                                    </td>
                                    <td class="py-1.5 px-2 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ number_format($mm['actual']) }}
                                    </td>
                                    <td class="py-1.5 px-1 text-center font-mono font-bold {{ $mm['achievement_rate'] >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($mm['achievement_rate'] >= 80 ? 'text-blue-600 dark:text-blue-400' : ($mm['actual'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400')) }}">
                                        {{ $mm['achievement_rate'] }}%
                                    </td>
                                    <td class="py-1.5 px-2 text-center">
                                        @if ($mm['status'] === 'Optimal')
                                            <span class="inline-flex px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                                                Optimal
                                            </span>
                                        @elseif ($mm['status'] === 'Stabil')
                                            <span class="inline-flex px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                Stabil
                                            </span>
                                        @else
                                            <span class="inline-flex px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300">
                                                Evaluasi
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">
                                        Tidak ada data mesin pada periode ini.
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
