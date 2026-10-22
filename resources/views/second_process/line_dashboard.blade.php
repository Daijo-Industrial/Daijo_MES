<x-operator-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-black text-2xl text-slate-900 uppercase tracking-wide flex items-center gap-2">
                        <span>{{ $line }}</span>
                        <span class="text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">Historical Line Analytics</span>
                    </h2>
                </div>
                <p class="text-xs font-semibold text-slate-500 mt-1">Line performance analysis, defect Pareto, downtime breakdown, and historical session logs</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('second-process.dashboard') }}" class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                    Overview Dashboard
                </a>
                @php $currentSlug = array_search($line, config('mes.sp_lines', [])) ?: \Illuminate\Support\Str::slug($line); @endphp
                <a href="{{ route('sp-sessions.line-gateway', ['lineSlug' => $currentSlug]) }}" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs rounded-xl border border-blue-200 shadow-sm transition uppercase tracking-wider">
                    Operator Gateway
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">

        {{-- Filter & Historical Line Control Bar --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
            <form action="{{ route('second-process.line-dashboard', $line) }}" method="GET" class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                {{-- Date Presets --}}
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                    <a href="{{ route('second-process.line-dashboard', ['line' => $line, 'preset' => 'today', 'shift' => $shift]) }}"
                       class="px-3 py-1 rounded-lg font-bold transition {{ ($preset ?? 'today') === 'today' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Today
                    </a>
                    <a href="{{ route('second-process.line-dashboard', ['line' => $line, 'preset' => 'yesterday', 'shift' => $shift]) }}"
                       class="px-3 py-1 rounded-lg font-bold transition {{ ($preset ?? '') === 'yesterday' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Yesterday
                    </a>
                    <a href="{{ route('second-process.line-dashboard', ['line' => $line, 'preset' => 'last_7_days', 'shift' => $shift]) }}"
                       class="px-3 py-1 rounded-lg font-bold transition {{ ($preset ?? '') === 'last_7_days' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Last 7 Days
                    </a>
                    <a href="{{ route('second-process.line-dashboard', ['line' => $line, 'preset' => 'this_month', 'shift' => $shift]) }}"
                       class="px-3 py-1 rounded-lg font-bold transition {{ ($preset ?? '') === 'this_month' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        This Month
                    </a>
                </div>

                {{-- Date Range Inputs (Start & End) --}}
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <label class="font-black text-xs uppercase text-slate-500">From:</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-xl border-slate-300 text-xs font-bold py-1.5 focus:ring-blue-500" onchange="this.form.submit()">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label class="font-black text-xs uppercase text-slate-500">To:</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-xl border-slate-300 text-xs font-bold py-1.5 focus:ring-blue-500" onchange="this.form.submit()">
                    </div>
                </div>

                {{-- Shift Select --}}
                <div class="flex items-center gap-1.5">
                    <label class="font-black text-xs uppercase text-slate-500">Shift:</label>
                    <select name="shift" class="rounded-xl border-slate-300 text-xs font-bold py-1.5 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="all" {{ (string)$shift === 'all' ? 'selected' : '' }}>All Shifts</option>
                        @foreach(config('mes.sp_shifts', config('mes.shifts', [])) as $sId => $sConf)
                            <option value="{{ $sId }}" {{ (string)$shift === (string)$sId ? 'selected' : '' }}>{{ $sConf['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                <span>📅 Window: 
                    <strong class="text-slate-900 font-black">
                        @if($dateFrom === $dateTo)
                            {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                        @endif
                    </strong>
                </span>
            </div>
        </div>

        {{-- Top KPI Metric Cards (6 Grid) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {{-- KPI 1: Good Output --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Good Output</div>
                <div class="text-2xl font-black text-emerald-600">{{ number_format($totalGood) }}</div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">Pcs produced</div>
            </div>

            {{-- KPI 2: Defect Output --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Defects (NG)</div>
                <div class="text-2xl font-black text-red-600">{{ number_format($totalReject) }}</div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">Pcs rejected</div>
            </div>

            {{-- KPI 3: NG Rate --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">NG Rate</div>
                <div class="text-2xl font-black {{ $ngRate > 2 ? 'text-red-600' : 'text-emerald-600' }}">{{ $ngRate }}%</div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">Target: &le; 2.0%</div>
            </div>

            {{-- KPI 4: Yield --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Process Yield</div>
                <div class="text-2xl font-black text-blue-600">{{ $yieldPct }}%</div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">Good / Total output</div>
            </div>

            {{-- KPI 5: Input WIP --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Total Input Received</div>
                <div class="text-2xl font-black text-gray-900">{{ number_format($totalInput) }}</div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">Pcs from molding/WIP</div>
            </div>

            {{-- KPI 6: Downtime Minutes --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Downtime Loss</div>
                <div class="text-2xl font-black {{ $totalDowntimeMinutes > 30 ? 'text-amber-600' : 'text-gray-900' }}">{{ $totalDowntimeMinutes }} <span class="text-xs text-gray-400 font-normal">min</span></div>
                <div class="text-[10px] text-gray-400 font-semibold mt-1">{{ $downtimeBreakdown->sum('occurrences') }} stop event(s)</div>
            </div>
        </div>

        {{-- Charts Row: Hourly Output Bar Chart & Defect Pareto --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Chart 1: Hourly Output --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Hourly Production Output</h3>
                        <p class="text-xs text-gray-400 font-medium">Good vs Reject distribution by hour</p>
                    </div>
                    <span class="text-[10px] font-black uppercase text-gray-400 bg-gray-50 border px-2 py-0.5 rounded-lg">Shift {{ $shift }}</span>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="chartHourlyOutput"></canvas>
                </div>
            </div>

            {{-- Chart 2: Defect Pareto --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Defect Breakdown (Pareto)</h3>
                        <p class="text-xs text-gray-400 font-medium">Rejects grouped by defect classification</p>
                    </div>
                    <span class="text-[10px] font-black uppercase text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-lg">Total: {{ number_format($totalReject) }} Pcs</span>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="chartDefectPareto"></canvas>
                </div>
            </div>
        </div>

        {{-- Downtime & Rework Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Downtime Log Table (2 Cols) --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Downtime Loss Log</h3>
                            <p class="text-xs text-gray-400 font-medium">Stop events recorded during shift</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-200">
                            Total: {{ $totalDowntimeMinutes }} Min
                        </span>
                    </div>

                    {{-- Downtime Categories Badges --}}
                    @if($downtimeBreakdown->count() > 0)
                        <div class="p-4 bg-amber-50/40 border-b border-amber-100/50 flex flex-wrap gap-2">
                            @foreach($downtimeBreakdown as $dt)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-white border border-amber-200 text-amber-950 shadow-2xs">
                                    <span>{{ $dt->reason }}:</span>
                                    <strong class="text-amber-700 font-black">{{ $dt->total_minutes }} min</strong>
                                    <span class="text-[10px] text-gray-400">({{ $dt->occurrences }}x)</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    <th class="px-6 py-3">Reason</th>
                                    <th class="px-6 py-3">Start Time</th>
                                    <th class="px-6 py-3">Resume Time</th>
                                    <th class="px-6 py-3 text-right">Duration</th>
                                    <th class="px-6 py-3">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                @forelse($downtimeLog as $dtEntry)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-3 font-bold text-gray-900">{{ $dtEntry->reason }}</td>
                                        <td class="px-6 py-3 text-gray-600 font-mono">{{ $dtEntry->start_time?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i') ?? '-' }}</td>
                                        <td class="px-6 py-3 text-gray-600 font-mono">{{ $dtEntry->resume_time?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i') ?? '-' }}</td>
                                        <td class="px-6 py-3 text-right font-black text-amber-700">{{ $dtEntry->duration_minutes }} min</td>
                                        <td class="px-6 py-3 text-gray-500 italic max-w-xs truncate">{{ $dtEntry->remarks ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium text-xs">No downtime events recorded for this shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Rework Summary Card (1 Col) --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5 flex flex-col justify-between">
                <div>
                    <div class="border-b border-gray-100 pb-3 mb-4">
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Rework Recovery</h3>
                        <p class="text-xs text-gray-400 font-medium">Defects processed for recovery</p>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-blue-50/60 p-4 rounded-xl border border-blue-100 flex justify-between items-center">
                            <div>
                                <div class="text-[10px] font-black text-blue-800 uppercase">Input to Rework</div>
                                <div class="text-xs text-blue-600 font-semibold">Parts sent for rework</div>
                            </div>
                            <div class="text-xl font-black text-blue-950">{{ number_format($totalReworkIn) }} <span class="text-xs text-blue-700">Pcs</span></div>
                        </div>

                        <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-100 flex justify-between items-center">
                            <div>
                                <div class="text-[10px] font-black text-emerald-800 uppercase">Successfully Recovered</div>
                                <div class="text-xs text-emerald-600 font-semibold">Saved & passed QC</div>
                            </div>
                            <div class="text-xl font-black text-emerald-950">{{ number_format($totalReworkRecovered) }} <span class="text-xs text-emerald-700">Pcs</span></div>
                        </div>

                        <div class="bg-red-50/60 p-4 rounded-xl border border-red-100 flex justify-between items-center">
                            <div>
                                <div class="text-[10px] font-black text-red-800 uppercase">Scrapped / Irrecoverable</div>
                                <div class="text-xs text-red-600 font-semibold">Final scrap count</div>
                            </div>
                            <div class="text-xl font-black text-red-950">{{ number_format($totalScrap) }} <span class="text-xs text-red-700">Pcs</span></div>
                        </div>
                    </div>
                </div>

                @php
                    $reworkRecoveryRate = $totalReworkIn > 0 ? round(($totalReworkRecovered / $totalReworkIn) * 100, 1) : 0;
                @endphp
                <div class="pt-4 border-t border-gray-100 text-center">
                    <div class="text-[10px] font-black text-gray-400 uppercase mb-1">Rework Recovery Rate</div>
                    <div class="text-lg font-black text-gray-800">{{ $reworkRecoveryRate }}%</div>
                </div>
            </div>
        </div>

        {{-- Manpower Roster & Session History Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Manpower Roster --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Shift Manpower Team</h3>
                        <p class="text-xs text-gray-400 font-medium">Assigned operators and line leaders</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-200">
                        {{ $manpower->count() }} Members
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3">Operator Name</th>
                                <th class="px-6 py-3">Employee No.</th>
                                <th class="px-6 py-3">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($manpower as $mp)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-3 font-bold text-gray-900">{{ $mp->operator_name }}</td>
                                    <td class="px-6 py-3 text-gray-500 font-mono">{{ $mp->employee_no ?? '-' }}</td>
                                    <td class="px-6 py-3 font-semibold text-blue-700">{{ $mp->role }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-400 font-medium text-xs">No team members explicitly logged for this session.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Line Session History --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Recent Session History</h3>
                        <p class="text-xs text-gray-400 font-medium">Last 10 sessions on {{ $line }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3">Date / Shift</th>
                                <th class="px-6 py-3">WO Number</th>
                                <th class="px-6 py-3 text-right">Good Output</th>
                                <th class="px-6 py-3 text-right">Reject</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($sessionHistory as $sHist)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-3 font-bold text-gray-800">
                                        <div>{{ $sHist->started_at?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('d M Y, H:i') }}</div>
                                        <div class="text-[10px] text-gray-400">Shift {{ $sHist->shift }}</div>
                                    </td>
                                    <td class="px-6 py-3 font-black text-blue-700">
                                        <a href="{{ route('sp-work-orders.show', $sHist->work_order_id) }}" class="hover:underline">
                                            {{ $sHist->workOrder->wo_number ?? '-' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-right font-black text-emerald-600">{{ number_format($sHist->total_good) }}</td>
                                    <td class="px-6 py-3 text-right font-black text-red-600">{{ number_format($sHist->total_reject) }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border
                                            {{ $sHist->status === 'running' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                            {{ $sHist->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium text-xs">No prior session history found for this line.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Chart.js Initialization --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }

            const hourlyData = @json($hourlyOutput);
            const defectData = @json($defectPareto);

            // 1. Hourly Output Stacked Bar Chart
            const hourlyLabels = Object.keys(hourlyData);
            const hourlyGood = hourlyLabels.map(h => hourlyData[h].good);
            const hourlyReject = hourlyLabels.map(h => hourlyData[h].reject);

            new window.Chart(document.getElementById('chartHourlyOutput'), {
                type: 'bar',
                data: {
                    labels: hourlyLabels.length ? hourlyLabels : ['No Data'],
                    datasets: [
                        {
                            label: 'Good Output',
                            data: hourlyGood.length ? hourlyGood : [0],
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                        },
                        {
                            label: 'Rejects (NG)',
                            data: hourlyReject.length ? hourlyReject : [0],
                            backgroundColor: '#ef4444',
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, grid: { color: '#f3f4f6' } }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { weight: 'bold', size: 11 } } }
                    }
                }
            });

            // 2. Defect Pareto Horizontal Bar Chart
            const defectLabels = defectData.map(d => d.defect_type);
            const defectCounts = defectData.map(d => parseInt(d.total));

            new window.Chart(document.getElementById('chartDefectPareto'), {
                type: 'bar',
                data: {
                    labels: defectLabels.length ? defectLabels : ['No Defects'],
                    datasets: [{
                        label: 'Defect Qty',
                        data: defectCounts.length ? defectCounts : [0],
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                        y: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</x-operator-layout>
