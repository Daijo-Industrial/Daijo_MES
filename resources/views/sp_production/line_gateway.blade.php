<x-operator-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center" x-data="{ menuOpen: false }">
            <div class="flex items-center gap-3">
                <h2 class="font-black text-2xl text-slate-900 uppercase tracking-wide">
                    {{ $line }}
                </h2>
                <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>S{{ $shift }}</span>
                    <span class="text-slate-300">•</span>
                    <span class="font-mono text-slate-700">{{ \Carbon\Carbon::now(config('mes.timezone', 'Asia/Jakarta'))->format('d M Y') }}</span>
                </div>
            </div>

            {{-- Supervisor Quick Menu Dropdown (Collapsed) --}}
            <div class="relative">
                <button type="button" @click="menuOpen = !menuOpen" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-sm rounded-xl transition border border-slate-200"
                        title="Supervisor Controls & Shift Switcher">
                    ⋯
                </button>

                <div x-show="menuOpen" 
                     @click.away="menuOpen = false" 
                     x-transition 
                     class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 p-2 z-50 space-y-1 text-xs"
                     x-cloak>
                    @php
                        $spShiftOptions = collect(config('mes.sp_shifts', []))->mapWithKeys(fn($s, $k) => [(string)$k => $s['name']])->put('all', 'All Shifts');
                    @endphp
                    @foreach($spShiftOptions as $sKey => $sLabel)
                        <a href="{{ route('sp-sessions.line-gateway', ['lineSlug' => $lineSlug, 'shift' => $sKey]) }}"
                           class="block px-3 py-1.5 rounded-lg transition {{ (string)$selectedShift === (string)$sKey ? 'bg-blue-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700 font-medium' }}">
                            {{ $sLabel }} {{ (string)$currentShift === (string)$sKey ? '(Current)' : '' }}
                        </a>
                    @endforeach

                    <div class="border-t border-slate-100 my-1"></div>
                    <a href="{{ route('second-process.dashboard', ['shift' => $selectedShift]) }}" class="block px-3 py-1.5 rounded-lg hover:bg-slate-100 text-slate-800 font-bold">
                        Overview Dashboard
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-5" x-data="{ 
            init() { 
                setInterval(() => { 
                    if(!document.hidden) window.location.reload(); 
                }, 15000);
            } 
        }">

        <div class="space-y-4">
            @forelse($workOrders as $wo)
                @php
                    $runningSession = $wo->sessions->where('status', 'running')->first();
                    $completedSession = $wo->sessions->where('status', 'completed')->first();
                    $fp = $firstPieceMap->get($wo->part_number);
                    $fpApproved = $fp && $fp->isApproved();
                @endphp

                @if($runningSession)
                    {{-- STATE 1: RUNNING SESSION (ACTIVE) --}}
                    <div class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-emerald-500 shadow-sm overflow-hidden transition hover:shadow-md">
                        <div class="bg-slate-50/80 px-6 py-2.5 border-b border-slate-100 flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="font-black text-emerald-950 uppercase tracking-wide">Running since {{ $runningSession->started_at?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                            <div class="space-y-3 flex-1">
                                <div>
                                    <div class="flex items-baseline gap-2 text-xl font-black text-slate-900">
                                        <a href="{{ route('sp-work-orders.show', $wo->id) }}" class="hover:text-blue-600 transition">{{ $wo->wo_number }}</a>
                                        <span class="text-slate-400 font-normal">·</span>
                                        <span class="text-sm font-bold text-slate-600">{{ $wo->customer ?? 'Internal' }}</span>
                                    </div>
                                    <div class="text-base font-bold text-slate-800 mt-0.5">
                                        {{ $wo->part_name }} <span class="font-mono text-xs text-slate-500">({{ $wo->part_number }})</span>
                                    </div>
                                </div>

                                @php
                                    $availWip = max(0, ($runningSession->total_input ?? 0) - (($runningSession->total_good ?? 0) + ($runningSession->total_reject ?? 0)));
                                @endphp
                                <div class="flex items-center gap-3 text-xs text-slate-600 font-semibold flex-wrap">
                                    <span>Target: <strong class="text-slate-950 font-black">{{ number_format($wo->target_qty) }} Pcs</strong></span>
                                    <span>Output: <strong class="text-emerald-700 font-black">{{ number_format($runningSession->total_good ?? 0) }} Pcs</strong></span>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black {{ $availWip > 0 ? 'bg-blue-50 text-blue-900 border border-blue-200' : 'bg-amber-100 text-amber-900 border border-amber-300 animate-pulse' }}">
                                        Avail WIP: {{ number_format($availWip) }} Pcs
                                    </span>
                                </div>

                                @if($runningSession->is_qc_bypassed)
                                    <div class="flex items-center justify-between gap-2 px-3 py-1.5 bg-purple-50 border border-purple-200 rounded-xl text-xs font-semibold text-purple-950">
                                        <span class="truncate"><strong class="font-black uppercase text-purple-700">QC Bypass:</strong> {{ $runningSession->qc_bypass_reason ?? 'Supervisor Verbal Approval' }}</span>
                                        <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $wo->id, 'part_number' => $wo->part_number, 'part_name' => $wo->part_name, 'model' => $wo->model]) }}" 
                                           class="px-2.5 py-1 bg-purple-700 hover:bg-purple-800 text-white font-black text-[10px] rounded-lg shadow-sm transition uppercase tracking-wider whitespace-nowrap">
                                            + Log QC
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('app.sp-sessions.show', $runningSession->id) }}"
                               class="w-full lg:w-auto text-center bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-black py-4 px-8 rounded-xl shadow-md text-sm transition uppercase tracking-wider min-h-[48px] flex items-center justify-center">
                                Resume Production Screen
                            </a>
                        </div>
                    </div>

                @elseif($fpApproved)
                    {{-- STATE 2: READY TO START (QC APPROVED) --}}
                    <div class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-blue-500 shadow-sm overflow-hidden transition hover:shadow-md">
                        <div class="bg-slate-50/80 px-6 py-2.5 border-b border-slate-100 flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900 uppercase tracking-wider border border-blue-200">
                                    ✓ QC Approved
                                </span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                            <div class="space-y-2 flex-1">
                                <div>
                                    <div class="flex items-baseline gap-2 text-xl font-black text-slate-900">
                                        <a href="{{ route('sp-work-orders.show', $wo->id) }}" class="hover:text-blue-600 transition">{{ $wo->wo_number }}</a>
                                        <span class="text-slate-400 font-normal">·</span>
                                        <span class="text-sm font-bold text-slate-600">{{ $wo->customer ?? 'Internal' }}</span>
                                    </div>
                                    <div class="text-base font-bold text-slate-800 mt-0.5">
                                        {{ $wo->part_name }} <span class="font-mono text-xs text-slate-500">({{ $wo->part_number }})</span>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-600 font-semibold">
                                    Target: <strong class="text-slate-950 font-black">{{ number_format($wo->target_qty) }} Pcs</strong>
                                </div>
                                @php $cumulativeGood = $wo->sessions->sum('total_good'); @endphp
                                @if($cumulativeGood > 0)
                                    <div class="text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 inline-block">
                                        Previously produced: <strong class="font-black">{{ number_format($cumulativeGood) }} / {{ number_format($wo->target_qty) }} Pcs</strong> — Remaining: <strong class="font-black text-amber-900">{{ number_format(max(0, $wo->target_qty - $cumulativeGood)) }} Pcs</strong>
                                    </div>
                                @endif
                            </div>

                            <form action="{{ route('sp-sessions.start', $wo->id) }}" method="POST" class="w-full lg:w-auto">
                                @csrf
                                <button type="submit"
                                        class="w-full lg:w-auto text-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black py-4 px-8 rounded-xl shadow-md text-sm transition uppercase tracking-wider min-h-[48px] flex items-center justify-center">
                                    Start Production
                                </button>
                            </form>
                        </div>
                    </div>

                @else
                    {{-- STATE 3: QC GATE PENDING --}}
                    <div class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-amber-500 shadow-sm overflow-hidden transition hover:shadow-md">
                        <div class="bg-amber-50/60 px-6 py-2.5 border-b border-amber-100 flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 uppercase tracking-wider border border-amber-200">
                                    ⏳ Awaiting First Piece QC
                                </span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                            <div class="space-y-2 flex-1">
                                <div>
                                    <div class="flex items-baseline gap-2 text-xl font-black text-slate-900">
                                        <a href="{{ route('sp-work-orders.show', $wo->id) }}" class="hover:text-amber-700 transition">{{ $wo->wo_number }}</a>
                                        <span class="text-slate-400 font-normal">·</span>
                                        <span class="text-sm font-bold text-slate-600">{{ $wo->customer ?? 'Internal' }}</span>
                                    </div>
                                    <div class="text-base font-bold text-slate-800 mt-0.5">
                                        {{ $wo->part_name }} <span class="font-mono text-xs text-slate-500">({{ $wo->part_number }})</span>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-600 font-semibold">
                                    Target: <strong class="text-slate-950 font-black">{{ number_format($wo->target_qty) }} Pcs</strong>
                                </div>
                            </div>

                            <div class="w-full lg:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2" x-data="{ showBypassModal: false, selectedReason: '' }">
                                @can('execute-qc-inspections')
                                    <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $wo->id, 'part_number' => $wo->part_number, 'part_name' => $wo->part_name, 'model' => $wo->model]) }}"
                                       class="text-center bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-black py-3.5 px-6 rounded-xl shadow-sm text-xs transition uppercase tracking-wider min-h-[44px] flex items-center justify-center">
                                        Perform Inspection
                                    </a>
                                @endcan

                                <button type="button" @click="showBypassModal = true"
                                        class="text-center bg-slate-50 hover:bg-amber-50 border border-slate-300 hover:border-amber-300 text-amber-950 font-bold py-3.5 px-4 rounded-xl text-xs transition uppercase tracking-wider min-h-[44px] flex items-center justify-center">
                                    Emergency Start (Bypass QC)
                                </button>

                                {{-- Alpine.js Modal for QC Bypass Reason --}}
                                <template x-teleport="body">
                                    <div x-show="showBypassModal" 
                                         x-transition.opacity
                                         class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
                                         @keydown.escape.window="showBypassModal = false"
                                         x-cloak>
                                        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-4"
                                             @click.away="showBypassModal = false">
                                            <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                                                <div>
                                                    <h3 class="text-base font-black text-amber-950 uppercase tracking-wide flex items-center gap-2">
                                                        <span>⚠️ Emergency Start (QC Gate Bypass)</span>
                                                    </h3>
                                                    <p class="text-xs text-slate-500 font-medium mt-0.5">WO: {{ $wo->wo_number }} — {{ $wo->part_name }}</p>
                                                </div>
                                                <button type="button" @click="showBypassModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                                            </div>

                                            <form action="{{ route('sp-sessions.start', $wo->id) }}" method="POST" class="space-y-4">
                                                @csrf
                                                <input type="hidden" name="bypass_qc" value="1">

                                                <div>
                                                    <label class="block text-xs font-black text-slate-700 uppercase mb-1">
                                                        Select Quick Suggestion Reason:
                                                    </label>
                                                    <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto p-2 bg-slate-50 rounded-xl border border-slate-200">
                                                        @foreach($quickBypassReasons as $preset)
                                                            <button type="button" 
                                                                    @click="selectedReason = @js($preset)"
                                                                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-white hover:bg-amber-100 border border-slate-300 text-slate-800 hover:border-amber-400 transition text-left">
                                                                + {{ $preset }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-black text-slate-700 uppercase mb-1">
                                                        Mandatory Bypass Reason <span class="text-red-500">*</span>
                                                    </label>
                                                    <textarea name="bypass_reason" 
                                                              x-model="selectedReason"
                                                              rows="3" 
                                                              required
                                                              minlength="3"
                                                              placeholder="Type or select a reason above (e.g. Urgent Delivery Run - Pre-approved by Supervisor)..."
                                                              class="w-full rounded-xl border-slate-300 text-xs font-medium focus:ring-amber-500 focus:border-amber-500 p-3 bg-slate-50/50"></textarea>
                                                </div>

                                                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900 font-medium">
                                                    <strong>QC Audit Notice:</strong> This bypass event will be logged with your user ID and timestamp, and flagged for supervisor review.
                                                </div>

                                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                                    <button type="button" @click="showBypassModal = false"
                                                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl uppercase tracking-wider">
                                                        Cancel
                                                    </button>
                                                    <button type="submit"
                                                            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl shadow-md uppercase tracking-wider">
                                                        Confirm & Start Production
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif

            @empty
                {{-- Empty State: No Work Orders Assigned --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                    <h4 class="text-base font-black text-slate-800 uppercase tracking-wide">No Work Orders Assigned</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Please contact your line supervisor or production planner.</p>
                </div>
            @endforelse
        </div>

    </div>
</x-operator-layout>
