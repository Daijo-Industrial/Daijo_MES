<x-app-layout>
    @php $tz = config('mes.timezone', 'Asia/Jakarta'); @endphp
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 font-bold mb-1">
                    <a href="{{ route('sp-approvals.index') }}" class="hover:text-blue-600 transition flex items-center gap-1">
                        <span>← Back to Approvals</span>
                    </a>
                    <span>/</span>
                    <span class="text-slate-800">Session #{{ $session->id }}</span>
                </div>
                <h2 class="font-black text-2xl text-slate-900 tracking-tight">
                    WO #{{ $session->workOrder->wo_number ?? 'N/A' }} • {{ $session->workOrder->part_name ?? 'Part' }}
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    {{ $session->unit_line }} • Shift {{ $session->shift }} • {{ $session->started_at?->setTimezone($tz)->format('d M Y') }} ({{ $session->started_at?->setTimezone($tz)->format('H:i') }} – {{ $session->finished_at?->setTimezone($tz)->format('H:i') ?: 'Running' }})
                </p>
            </div>
            
            <div class="flex items-center gap-2.5">
                @if($session->approved_at)
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3.5 py-1.5 rounded-xl flex items-center gap-2 text-xs font-bold">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        <span>Approved on {{ $session->approved_at->setTimezone($tz)->format('M d, Y H:i') }}</span>
                    </div>
                    @if(!empty($syncedReport))
                        <a href="{{ route('second-process-reports.show', $syncedReport->id) }}" target="_blank"
                           class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 active:bg-slate-900 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                            <span>Legacy Report</span>
                            <span>↗</span>
                        </a>
                    @endif
                @else
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-3.5 py-1.5 rounded-xl flex items-center gap-2 text-xs font-bold">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        <span>Pending Approval</span>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Alerts --}}
            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-900 text-xs font-bold shadow-xs">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-950 text-xs font-bold shadow-xs flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Emergency QC Bypass Warning Banner --}}
            @if($session->is_qc_bypassed)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start justify-between gap-4 flex-wrap text-xs">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-amber-200 text-amber-900 text-[10px] font-black uppercase rounded">Bypassed</span>
                            <h4 class="font-bold text-amber-950 uppercase tracking-wide">Emergency QC Gate Bypassed</h4>
                        </div>
                        <p class="text-amber-900 font-medium mt-1">
                            Session authorized without completed First Piece Inspection. Reason: <strong class="font-mono">{{ $session->qc_bypass_reason }}</strong>
                        </p>
                    </div>
                    @if($session->qcBypassedBy)
                        <div class="text-right text-amber-900 shrink-0">
                            <div>Authorized by: <strong class="text-amber-950 font-bold">{{ $session->qcBypassedBy->name }}</strong></div>
                            <div class="font-mono text-[10px] text-amber-700 mt-0.5">{{ $session->qc_bypassed_at?->setTimezone($tz)->format('Y-m-d H:i') }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- SECTION 1: Shift Mass Balance & Scorecard (2-Tier Scorecard) --}}
            @php $wo = $session->workOrder; @endphp
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex justify-between items-center flex-wrap gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Shift Mass Balance & Scorecard</h3>
                        <p class="text-[11px] text-slate-500">Reconciled operational metrics verified against shop floor stream logs</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg">
                            {{ $session->unit_line }} — Shift {{ $session->shift }}
                        </span>
                        <span class="font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                            Lead: {{ $session->operator->name ?? 'N/A' }}
                        </span>
                    </div>
                </div>

                {{-- Tier 1: Primary KPIs (4 Columns) --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {{-- Good Output --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Good Output</span>
                        <span class="text-xl font-black text-emerald-600 leading-tight block mt-0.5">{{ number_format($session->total_good) }} Pcs</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">
                            {{ number_format($directGood) }} direct • {{ number_format($session->total_rework_recovered) }} rework
                        </span>
                    </div>

                    {{-- Final Scrap --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Final Scrap</span>
                        <span class="text-xl font-black text-rose-600 leading-tight block mt-0.5">{{ number_format($finalScrap) }} Pcs</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">
                            {{ number_format($session->total_scrap) }} bench • {{ number_format(max(0, $finalScrap - $session->total_scrap)) }} line
                        </span>
                    </div>

                    {{-- Yield Rate --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Yield Rate</span>
                        <span class="text-xl font-black text-slate-900 leading-tight block mt-0.5">{{ number_format($session->yield, 1) }}%</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">
                            {{ number_format($session->total_good) }} / {{ number_format($session->total_good + $finalScrap) }} processed
                        </span>
                    </div>

                    {{-- Downtime --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Downtime</span>
                        <span class="text-xl font-black text-slate-900 leading-tight block mt-0.5">{{ number_format($totalDowntimeMinutes) }} Mins</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">
                            {{ $session->downtimeEntries->count() }} stoppage(s)
                        </span>
                    </div>
                </div>

                {{-- Tier 2: Secondary Mass Balance Context (3 Columns) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1 border-t border-slate-100">
                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50/60 rounded-lg text-xs">
                        <span class="text-slate-500 font-medium">WO Target:</span>
                        <span class="font-bold text-slate-800">{{ number_format($wo->target_qty ?? 0) }} Pcs</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50/60 rounded-lg text-xs">
                        <span class="text-slate-500 font-medium">Total Input:</span>
                        <span class="font-bold text-slate-800">
                            {{ number_format($session->total_input) }} Pcs
                            <span class="text-[10px] text-slate-500 font-normal">({{ number_format($session->inputEntries->where('source', '!=', 'reworkable')->sum('quantity')) }} WIP, {{ number_format($session->inputEntries->where('source', 'reworkable')->sum('quantity')) }} Rep)</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50/60 rounded-lg text-xs">
                        <span class="text-slate-500 font-medium">Leftover WIP:</span>
                        <span class="font-bold text-slate-800">{{ number_format($unusedWip) }} Pcs</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ tab: 'materials' }">
                
                {{-- MAIN CONTENT AREA: 4 Focused Tabs --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- Tab Navigation Bar --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-2 flex items-center gap-1.5 overflow-x-auto">
                            {{-- Tab 1: Materials & Lots --}}
                            <button type="button" @click="tab = 'materials'"
                                    :class="tab === 'materials' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0 flex items-center gap-1.5">
                                <span>Materials & Lots</span>
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-200 text-slate-700">
                                    {{ $session->materials->count() }}
                                </span>
                            </button>

                            {{-- Tab 2: Defects & Rework --}}
                            <button type="button" @click="tab = 'defects'"
                                    :class="tab === 'defects' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0 flex items-center gap-1.5">
                                <span>Defects & Rework</span>
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-200 text-slate-700">
                                    {{ $session->rejectEntries->count() }}
                                </span>
                            </button>

                            {{-- Tab 3: Downtime & Stoppages --}}
                            <button type="button" @click="tab = 'downtime'"
                                    :class="tab === 'downtime' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0 flex items-center gap-1.5">
                                <span>Downtime & Stoppages</span>
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-200 text-slate-700">
                                    {{ $session->downtimeEntries->count() }}
                                </span>
                            </button>

                            {{-- Tab 4: Progression & Handover --}}
                            <button type="button" @click="tab = 'progression'"
                                    :class="tab === 'progression' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex-shrink-0 flex items-center gap-1.5">
                                <span>Progression & Handover</span>
                                @if($session->production_notes || $session->ng_remarks || $session->absent_employees || !empty($session->next_production_schedule))
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                @endif
                            </button>
                        </div>

                        {{-- TAB 1: Material Consumption & Lot Tracking --}}
                        <div x-show="tab === 'materials'" class="p-5 space-y-6">
                            {{-- Paint Materials --}}
                            <div>
                                <div class="flex justify-between items-center mb-2.5">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Paint & Chemical Consumption</h4>
                                        <p class="text-[11px] text-slate-500">Viscosity, mixing ratio, lot numbers and quantities consumed</p>
                                    </div>
                                    <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ $session->materials->where('type', 'paint')->count() }} Item(s)
                                    </span>
                                </div>
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Item Name</th>
                                                <th class="px-3 py-2">Lot Number</th>
                                                <th class="px-3 py-2">Viscosity</th>
                                                <th class="px-3 py-2">Mixing Ratio</th>
                                                <th class="px-3 py-2 text-right">Quantity</th>
                                                <th class="px-3 py-2">UOM</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse($session->materials->where('type', 'paint') as $mat)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-3 py-2 font-bold text-slate-900">{{ $mat->item_name }}</td>
                                                    <td class="px-3 py-2 font-mono text-slate-700">{{ $mat->lot_number ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $mat->visco ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $mat->mixing_ratio ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-bold text-slate-800 font-mono">{{ $mat->qty !== null ? number_format($mat->qty, 2) : '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-500">{{ $mat->uom ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-5 text-center text-slate-400">
                                                        No paint chemicals recorded for this session.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Item Parts / WIP Lots --}}
                            <div class="pt-3 border-t border-slate-100">
                                <div class="flex justify-between items-center mb-2.5">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Item Parts & WIP / Repairan Lots</h4>
                                        <p class="text-[11px] text-slate-500">Lot traceability mapped directly from floor input entries</p>
                                    </div>
                                    <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ $session->materials->where('type', 'part')->count() }} Lot Record(s)
                                    </span>
                                </div>
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Item Name</th>
                                                <th class="px-3 py-2">Lot / Pallet Number</th>
                                                <th class="px-3 py-2 text-right">Quantity</th>
                                                <th class="px-3 py-2">UOM</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse($session->materials->where('type', 'part') as $mat)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-3 py-2 font-bold text-slate-900">
                                                        {{ $mat->item_name }}
                                                    </td>
                                                    <td class="px-3 py-2 font-mono text-slate-800">{{ $mat->lot_number ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-bold text-slate-900 font-mono">
                                                        {{ $mat->qty !== null ? number_format($mat->qty) : '-' }}
                                                    </td>
                                                    <td class="px-3 py-2 text-slate-500">{{ $mat->uom ?: 'Pcs' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-5 text-center text-slate-400">
                                                        No part / WIP lots recorded for this session.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 2: Defects & Rework --}}
                        <div x-show="tab === 'defects'" style="display: none;" class="p-5 space-y-6">
                            {{-- Pareto Defect Table --}}
                            <div>
                                <div class="flex justify-between items-center mb-2.5">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Defect Pareto Breakdown</h4>
                                        <p class="text-[11px] text-slate-500">Grouped scrap volume and documented causes</p>
                                    </div>
                                    <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-lg">
                                        Total Scrap: {{ number_format($finalScrap) }} Pcs
                                    </span>
                                </div>
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Defect Type</th>
                                                <th class="px-3 py-2 text-right">Quantity</th>
                                                <th class="px-3 py-2 text-right">% of NG</th>
                                                <th class="px-3 py-2">Observed Causes / Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse($defectSummary as $def)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-3 py-2 font-bold text-slate-900">{{ $def['type'] }}</td>
                                                    <td class="px-3 py-2 text-right font-bold text-rose-600 font-mono">{{ number_format($def['quantity']) }} Pcs</td>
                                                    <td class="px-3 py-2 text-right font-bold text-slate-600 font-mono">{{ $def['percentage'] }}%</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $def['causes'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-5 text-center text-slate-400">
                                                        No defects logged for this session.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Bench Rework Performance Card --}}
                            <div class="pt-3 border-t border-slate-100">
                                <div class="flex justify-between items-center mb-2.5">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rework Bench Audit</h4>
                                        <p class="text-[11px] text-slate-500">Inspection recovery efficiency and scrapped balances</p>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                        Recovery Rate: {{ $reworkStats['recovery_rate'] }}%
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Total Issued</span>
                                        <span class="text-base font-bold text-slate-900 font-mono">{{ number_format($reworkStats['input']) }} Pcs</span>
                                    </div>
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Recovered OK</span>
                                        <span class="text-base font-bold text-emerald-600 font-mono">+{{ number_format($reworkStats['recovered']) }} Pcs</span>
                                    </div>
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Scrapped on Bench</span>
                                        <span class="text-base font-bold text-rose-600 font-mono">{{ number_format($reworkStats['scrapped']) }} Pcs</span>
                                    </div>
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                        <span class="block text-[10px] font-bold text-slate-500 uppercase">Pending on Bench</span>
                                        <span class="text-base font-bold text-slate-700 font-mono">{{ number_format($reworkStats['pending']) }} Pcs</span>
                                    </div>
                                </div>
                            </div>

                            @if($session->ng_remarks)
                                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                                    <span class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Quality / NG Notes</span>
                                    <p class="text-xs text-slate-800 whitespace-pre-line">{{ $session->ng_remarks }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- TAB 3: Downtime & Stoppages --}}
                        <div x-show="tab === 'downtime'" style="display: none;" class="p-5 space-y-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Line Stoppages & Problem Countermeasures</h4>
                                    <p class="text-[11px] text-slate-500">Root causes classified under 5M (Man, Mesin, Part, PPS, Lingkungan)</p>
                                </div>
                                <span class="text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg">
                                    Total: {{ $totalDowntimeMinutes }} Mins
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                        <tr>
                                            <th class="px-3 py-2">Time Range</th>
                                            <th class="px-3 py-2">Duration</th>
                                            <th class="px-3 py-2">5M Category</th>
                                            <th class="px-3 py-2">Problem / Masalah</th>
                                            <th class="px-3 py-2">Countermeasure / Penanganan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @forelse($session->downtimeEntries as $dt)
                                            @php
                                                $dur = $dt->duration_minutes;
                                                if (!$dur && $dt->start_time && $dt->resume_time) {
                                                    $dur = \Carbon\Carbon::parse($dt->start_time)->diffInMinutes(\Carbon\Carbon::parse($dt->resume_time));
                                                }
                                            @endphp
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="px-3 py-2 font-mono text-slate-600 whitespace-nowrap">
                                                    {{ $dt->start_time?->setTimezone($tz)->format('H:i') ?: '-' }} – {{ $dt->resume_time?->setTimezone($tz)->format('H:i') ?: '-' }}
                                                </td>
                                                <td class="px-3 py-2 font-bold text-slate-900 font-mono whitespace-nowrap">
                                                    {{ $dur ?: 0 }} min
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                                        {{ $dt->category ?: 'Downtime' }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 font-bold text-slate-800">
                                                    {{ $dt->reason }}
                                                    @if($dt->remarks)
                                                        <span class="text-slate-400 font-normal block text-[11px] mt-0.5">Note: {{ $dt->remarks }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-slate-700">
                                                    {{ $dt->countermeasure ?: '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                                    No downtime or stoppage events recorded during this shift.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- TAB 4: Progression & Handover --}}
                        <div x-show="tab === 'progression'" style="display: none;" class="p-5 space-y-6">
                            {{-- 8-Hour Curve --}}
                            <div>
                                <div class="flex justify-between items-center mb-2.5">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">8-Hour Production Progression</h4>
                                        <p class="text-[11px] text-slate-500">Hourly output and defect accumulation synchronized with reporting</p>
                                    </div>
                                    <span class="text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                        Target / Hr: {{ (int) ceil(($wo->target_qty ?? 0) / 8) }} Pcs
                                    </span>
                                </div>

                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Hour</th>
                                                <th class="px-3 py-2 text-right">Good Output (OK)</th>
                                                <th class="px-3 py-2 text-right">Defects (NG)</th>
                                                <th class="px-3 py-2 text-right">Running Total (OK)</th>
                                                <th class="px-3 py-2">Pace Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @php $hourlyTarget = (int) ceil(($wo->target_qty ?? 0) / 8); @endphp
                                            @foreach($hourlyTable as $row)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-3 py-2 font-bold text-slate-800">
                                                        Hour {{ $row['hour'] }}
                                                        <span class="text-[11px] font-normal text-slate-400 ml-1">({{ $row['time_range'] }})</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-bold text-emerald-600 font-mono">
                                                        {{ number_format($row['ok']) }} Pcs
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-bold text-rose-600 font-mono">
                                                        {{ number_format($row['ng']) }} Pcs
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-bold text-slate-800 font-mono">
                                                        {{ number_format($row['accumulation']) }} Pcs
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if($row['ok'] >= $hourlyTarget && $hourlyTarget > 0)
                                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">
                                                                On Target
                                                            </span>
                                                        @elseif($row['ok'] > 0)
                                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">
                                                                Below Target
                                                            </span>
                                                        @else
                                                            <span class="text-slate-300 text-xs">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Handover & Notes Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <span class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Production Notes & Handover</span>
                                    <p class="text-xs text-slate-800 whitespace-pre-line">{{ $session->production_notes ?: ($session->remarks ?: 'No production notes recorded.') }}</p>
                                </div>

                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                                    <span class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Absent Team Members</span>
                                    <p class="text-xs text-slate-800">{{ $session->absent_employees ?: 'None (Full attendance)' }}</p>
                                </div>
                            </div>

                            {{-- Output Destination --}}
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center text-xs">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Output Destination</span>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $session->output_destination ?: '-' }}</div>
                                </div>
                            </div>

                            {{-- Next Production Schedule --}}
                            <div class="pt-3 border-t border-slate-100">
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Next Production Schedule (Target)</h4>
                                @php
                                    $schedList = is_array($session->next_production_schedule)
                                        ? array_values(array_filter($session->next_production_schedule, fn($i) => !empty($i)))
                                        : [];
                                @endphp
                                @if(!empty($schedList))
                                    @if(is_array($schedList[0]))
                                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                                    <tr>
                                                        <th class="px-3 py-2">Part Number</th>
                                                        <th class="px-3 py-2">Line</th>
                                                        <th class="px-3 py-2">Shift</th>
                                                        <th class="px-3 py-2 text-right">Target Plan</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                    @foreach($schedList as $sched)
                                                        <tr>
                                                            <td class="px-3 py-2 font-mono font-bold text-slate-900">{{ $sched['part_number'] ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-slate-700">{{ $sched['unit_line'] ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-slate-700">{{ $sched['shift'] ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-right font-mono font-bold text-slate-900">{{ number_format($sched['target_qty'] ?? 0) }} Pcs</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 divide-y divide-slate-100 text-xs">
                                            @foreach($schedList as $sIdx => $schedItem)
                                                <div class="py-1.5 flex items-center gap-2">
                                                    <span class="w-5 text-slate-400 font-bold text-right">{{ $sIdx + 1 }}.</span>
                                                    <span class="font-medium text-slate-800">{{ $schedItem }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 text-center text-xs text-slate-400">
                                        No next production schedule configured.
                                    </div>
                                @endif
                            </div>

                            {{-- Line Team Roster --}}
                            <div class="pt-3 border-t border-slate-100">
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Line Crew & Manpower</h4>
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Role / Position</th>
                                                <th class="px-3 py-2">Operator Name</th>
                                                <th class="px-3 py-2">Employee NIK</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse($session->manpowerEntries as $mp)
                                                <tr>
                                                    <td class="px-3 py-2 font-bold text-slate-800 uppercase">{{ $mp->role }}</td>
                                                    <td class="px-3 py-2 text-slate-900">{{ $mp->operator_name }}</td>
                                                    <td class="px-3 py-2 font-mono text-slate-600">{{ $mp->employee_no ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-3 text-center text-slate-400">
                                                        Lead operator only ({{ $session->operator->name ?? 'N/A' }}).
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

                {{-- RIGHT COLUMN: Supervisor Decision & Authorization Panel --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden sticky top-6">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Official Sign-off</span>
                            <h3 class="font-bold text-sm text-slate-900">Supervisor Decision</h3>
                        </div>

                        <div class="p-5 space-y-5">
                            @if(is_null($session->approved_at))
                                {{-- Audit Checklist --}}
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5 text-xs">
                                    <span class="font-bold text-slate-800 uppercase tracking-wider block text-[10px]">Verification Checklist</span>
                                    <div class="flex items-center gap-2 text-slate-700">
                                        <span class="text-emerald-600 font-bold">✓</span>
                                        <span>Mass Balance: {{ number_format($session->total_good) }} OK + {{ number_format($finalScrap) }} Scrap</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-700">
                                        <span class="{{ $session->materials->count() > 0 ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">✓</span>
                                        <span>{{ $session->materials->count() }} Materials & Lots logged</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-700">
                                        <span class="{{ $session->downtimeEntries->count() > 0 ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">✓</span>
                                        <span>{{ $session->downtimeEntries->count() }} Downtime events recorded</span>
                                    </div>
                                </div>

                                {{-- Action 1: Approve Form --}}
                                <form action="{{ route('sp-approvals.approve', $session->id) }}" method="POST"
                                      onsubmit="return confirm('Authorize and sign off on this shift production report?')">
                                    @csrf
                                    <button type="submit"
                                            class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-xs transition uppercase tracking-wider text-xs cursor-pointer flex items-center justify-center gap-2">
                                        <span>✓ APPROVE & SYNC REPORT</span>
                                    </button>
                                </form>

                                {{-- Action 2: Return for Correction Trigger --}}
                                <div>
                                    <button type="button" onclick="document.getElementById('modalReturn').showModal()"
                                            class="w-full bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-bold py-2.5 px-4 rounded-xl transition uppercase tracking-wider text-xs cursor-pointer">
                                        Return for Correction
                                    </button>
                                </div>

                            @else
                                {{-- Approved State Banner --}}
                                <div class="text-center py-2 space-y-2">
                                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-sm">Report Authorized</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Signed by <strong class="text-slate-800">{{ $session->approvedBy->name ?? 'User #' . $session->approved_by }}</strong>
                                        </p>
                                        <span class="text-[11px] font-mono text-slate-400 block mt-0.5">
                                            {{ $session->approved_at?->setTimezone($tz)->format('M d, Y H:i') }}
                                        </span>
                                    </div>

                                    @if(!empty($syncedReport))
                                        <div class="pt-2">
                                            <a href="{{ route('second-process-reports.show', $syncedReport->id) }}" target="_blank"
                                               class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-lg text-xs flex items-center justify-center gap-1.5 transition">
                                                <span>View Synced Legacy Report #{{ $syncedReport->id }}</span>
                                                <span>↗</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Audit Signatures Timeline --}}
                            <div class="border-t border-slate-100 pt-3 space-y-2 text-xs">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Shift Audit Trail (WIB / UTC+7)</span>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Lead Operator:</span>
                                    <strong class="text-slate-800">{{ $session->operator->name ?? 'N/A' }}</strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Shift Started:</span>
                                    <span class="text-slate-700 font-mono">{{ $session->started_at?->setTimezone($tz)->format('Y-m-d H:i') ?: '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Shift Completed:</span>
                                    <span class="text-slate-700 font-mono">{{ $session->finished_at?->setTimezone($tz)->format('Y-m-d H:i') ?: '-' }}</span>
                                </div>
                                @if($session->approved_at)
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500">Authorized by:</span>
                                        <strong class="text-emerald-700">{{ $session->approvedBy->name ?? 'N/A' }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL: Return for Correction --}}
    <dialog id="modalReturn" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-slate-900/60 bg-transparent">
        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-rose-600 px-5 py-4 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-base font-bold tracking-tight">Return Session for Correction</h3>
                    <p class="text-xs text-rose-100">Specify what the operator needs to adjust on the floor or in close-out</p>
                </div>
                <button type="button" onclick="document.getElementById('modalReturn').close()" class="opacity-80 hover:opacity-100 text-2xl font-bold leading-none cursor-pointer">&times;</button>
            </div>
            <form action="{{ route('sp-approvals.reject', $session->id) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Reason / Correction Notes *</label>
                    <textarea name="reason" rows="4" required placeholder="e.g. Paint viscosity missing, lot number for WIP 2 incorrect, please verify scrap quantity..."
                              class="w-full text-xs rounded-xl border-slate-300 p-3 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 transition"></textarea>
                </div>
                <p class="text-[11px] text-slate-500">
                    Returning this session will change its status back to <strong>running</strong> so the operator can edit entries and resubmit.
                </p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalReturn').close()"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs uppercase tracking-wider transition cursor-pointer">
                        Return to Operator
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>
