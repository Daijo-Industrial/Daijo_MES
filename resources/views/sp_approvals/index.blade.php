<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-900 tracking-tight">
                    {{ __('Second Process Approvals') }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Review, verify mass balance, and authorize completed production shifts</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-900 text-xs font-semibold">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('warning'))
                <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-amber-900 text-xs font-semibold">
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            {{-- SECTION 1: Clean Summary Metrics Strip --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Pending Approvals --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Pending Approval</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900 leading-none">
                            {{ number_format($kpiPendingCount) }}
                        </span>
                        @if($kpiPendingCount > 0)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                Action Needed
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Approved Today --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Approved Today</span>
                    <div class="text-2xl font-black text-emerald-600 leading-none">
                        {{ number_format($kpiApprovedToday) }}
                    </div>
                </div>

                {{-- Pending Output --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Pending Good Qty</span>
                    <div class="text-2xl font-black text-blue-600 leading-none">
                        {{ number_format($kpiPendingGood) }} <span class="text-xs font-bold text-slate-400">Pcs</span>
                    </div>
                </div>

                {{-- Average Process Yield --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Avg Pending Yield</span>
                    <div class="text-2xl font-black {{ $kpiAvgYield >= 98 ? 'text-emerald-600' : ($kpiAvgYield >= 95 ? 'text-amber-600' : 'text-rose-600') }} leading-none">
                        {{ number_format($kpiAvgYield, 1) }}%
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Filters, Search & Tabs --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-4 space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 pb-4">
                    {{-- Tabs --}}
                    <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                        <a href="{{ route('sp-approvals.index', array_merge(request()->except('page'), ['tab' => 'pending'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $tab === 'pending' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                            <span>Pending</span>
                            @if($kpiPendingCount > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $tab === 'pending' ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $kpiPendingCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('sp-approvals.index', array_merge(request()->except('page'), ['tab' => 'approved'])) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $tab === 'approved' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                            <span>Approved</span>
                        </a>
                    </div>

                    {{-- Search Form --}}
                    <form method="GET" action="{{ route('sp-approvals.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <input type="hidden" name="tab" value="{{ $tab }}">

                        {{-- Keyword Search --}}
                        <div class="relative flex-1 sm:w-56">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search WO, Part #, Operator..."
                                   class="w-full text-xs font-medium rounded-xl border-slate-200 py-2 pl-8 pr-3 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        {{-- Line Filter --}}
                        <select name="unit_line" onchange="this.form.submit()" class="text-xs font-medium rounded-xl border-slate-200 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">All Lines</option>
                            @foreach($spLines as $slug => $lineName)
                                <option value="{{ $lineName }}" {{ $line === $lineName ? 'selected' : '' }}>{{ $lineName }}</option>
                            @endforeach
                        </select>

                        {{-- Shift Filter --}}
                        <select name="shift" onchange="this.form.submit()" class="text-xs font-medium rounded-xl border-slate-200 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">All Shifts</option>
                            <option value="1" {{ $shift == '1' ? 'selected' : '' }}>Shift 1</option>
                            <option value="2" {{ $shift == '2' ? 'selected' : '' }}>Shift 2</option>
                            <option value="3" {{ $shift == '3' ? 'selected' : '' }}>Shift 3</option>
                        </select>

                        {{-- Date Filter --}}
                        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                               class="text-xs font-medium rounded-xl border-slate-200 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition">

                        @if($search || $line || $shift || $date)
                            <a href="{{ route('sp-approvals.index', ['tab' => $tab]) }}" class="px-2 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 transition" title="Clear Filters">
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                {{-- SECTION 3: Sessions List Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Shift & Date</th>
                                <th class="px-4 py-3">Work Order & Part</th>
                                <th class="px-4 py-3">Operator</th>
                                <th class="px-4 py-3">Output / Scrap</th>
                                <th class="px-4 py-3">Yield</th>
                                <th class="px-4 py-3">Audit Flags</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($sessions as $session)
                                @php
                                    $wo = $session->workOrder;
                                    $finalScrap = max($session->total_reject, $session->total_scrap);
                                    $unusedWip = max(0, $session->total_input - ($session->total_good + $finalScrap));
                                    $downtimeMins = $session->downtimeEntries->sum('duration_minutes');
                                    $materialsCount = $session->materials->count();
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    {{-- Shift & Date --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-xs">
                                            {{ $session->unit_line }} <span class="text-slate-300">•</span> Shift {{ $session->shift }}
                                        </div>
                                        @php
                                            $tz = config('mes.timezone', 'Asia/Jakarta');
                                            $sessionDate = optional($session->finished_at ?: $session->started_at)->setTimezone($tz);
                                            $startTime = optional($session->started_at)->setTimezone($tz);
                                            $finishTime = optional($session->finished_at)->setTimezone($tz);
                                        @endphp
                                        <div class="font-mono text-[11px] text-slate-600">
                                            {{ $sessionDate ? $sessionDate->format('Y-m-d') : '-' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium">
                                            {{ $startTime ? $startTime->format('H:i') : '-' }} – {{ $finishTime ? $finishTime->format('H:i') : 'In Progress' }}
                                        </div>
                                    </td>

                                    {{-- Work Order & Part --}}
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 font-mono text-xs">
                                            {{ $wo->wo_number ?? 'N/A' }}
                                        </div>
                                        <div class="font-semibold text-slate-800 text-xs truncate max-w-xs">
                                            {{ $wo->part_name ?? '-' }}
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400 truncate max-w-xs">
                                            {{ $wo->part_number ?? '-' }}
                                        </div>
                                    </td>

                                    {{-- Operator --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800">
                                            {{ $session->operator->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Session #{{ $session->id }}
                                        </div>
                                    </td>

                                    {{-- Output / Scrap --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-xs font-mono font-bold text-slate-900">
                                            {{ number_format($session->total_good) }} <span class="text-slate-400 font-normal">/ {{ number_format($session->total_input) }} In</span>
                                        </div>
                                        <div class="text-[11px] font-mono font-medium text-rose-600">
                                            {{ number_format($finalScrap) }} Scrap
                                            @if($unusedWip > 0)
                                                <span class="text-slate-400 font-normal">• {{ number_format($unusedWip) }} WIP</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Yield --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-mono font-black {{ $session->yield >= 98 ? 'text-emerald-700' : ($session->yield >= 95 ? 'text-amber-600' : 'text-rose-600') }}">
                                                {{ number_format($session->yield, 1) }}%
                                            </span>
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $session->yield >= 98 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($session->yield >= 95 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200') }}">
                                                {{ $session->yield >= 98 ? 'OK' : ($session->yield >= 95 ? 'WARN' : 'NG') }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium">
                                            Target: {{ number_format($wo->target_qty ?? 0) }}
                                        </div>
                                    </td>

                                    {{-- Audit Flags --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            @if($session->is_qc_bypassed)
                                                <span class="inline-flex items-center px-1.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[9px] font-bold">
                                                    QC Bypassed
                                                </span>
                                            @endif

                                            @if($downtimeMins > 0)
                                                <span class="inline-flex items-center text-[10px] font-medium text-slate-600">
                                                    {{ $downtimeMins }}m stop
                                                </span>
                                            @endif

                                            @if(!$session->is_qc_bypassed && $downtimeMins == 0)
                                                <span class="text-slate-300 text-xs">—</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Action --}}
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if(is_null($session->approved_at))
                                            <a href="{{ route('sp-approvals.show', $session->id) }}"
                                               class="inline-flex items-center px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition shadow-2xs cursor-pointer">
                                                Review
                                            </a>
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                                                    Approved
                                                </span>
                                                <a href="{{ route('sp-approvals.show', $session->id) }}"
                                                   class="inline-flex items-center px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition cursor-pointer">
                                                    View
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <div class="font-bold text-lg text-slate-600 mb-1">No production sessions found</div>
                                        <div class="text-xs text-slate-400">There are currently no {{ $tab }} shift reports matching your query.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sessions->hasPages())
                    <div class="pt-2">
                        {{ $sessions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

