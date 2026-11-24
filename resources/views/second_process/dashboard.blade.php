<x-operator-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-800 uppercase tracking-wide">
                    {{ __('Second Process Operator & Shop Floor Dashboard') }}
                </h2>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Real-time line status, First Piece QC gate tracking, and active session management</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                    Main MES
                </a>
                <a href="{{ route('second-process.report-analytics') }}" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl border border-blue-200 shadow-sm transition uppercase tracking-wider">
                    Analytics Dashboard
                </a>
                <a href="{{ route('sp-work-orders.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 shadow-sm transition uppercase tracking-wider">
                    Work Orders List
                </a>
                <a href="{{ route('first-piece-inspections.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 shadow-sm transition uppercase tracking-wider">
                    First Piece Inspections
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6" x-data="{ 
            activeTab: '{{ request('tab', 'all') }}',
            init() { 
                setInterval(() => { 
                    if(!document.hidden) window.location.reload(); 
                }, 30000);
            } 
        }">

        {{-- Live Floor Status Bar --}}
        <div class="bg-white px-5 py-3.5 rounded-2xl border border-gray-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Floor Monitor</span>
                </span>
                <span class="text-xs font-bold text-slate-800">
                    {{ $currentShiftConfig['name'] ?? "Shift {$shift}" }}
                    <span class="text-slate-400 font-normal">({{ $currentShiftConfig['start'] ?? '07:30' }} - {{ $currentShiftConfig['end'] ?? '15:30' }})</span>
                </span>
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="font-medium">Auto-refresh: <strong class="text-slate-700 font-bold">30s</strong></span>
                <span class="text-slate-300">•</span>
                <a href="{{ route('second-process.report-analytics') }}" class="font-semibold text-blue-600 hover:text-blue-800 transition">
                    Audit Shift History
                </a>
            </div>
        </div>

        {{-- Top KPI Metric Cards (Compact & Minimized) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
            {{-- KPI Tab 1: Active Production Lines --}}
            <button type="button" @click="activeTab = (activeTab === 'running' ? 'all' : 'running')"
                :class="activeTab === 'running' ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-400 shadow-xs' : 'border-slate-200 bg-white hover:border-emerald-300 shadow-xs'"
                class="px-3.5 py-2.5 rounded-xl border text-left transition cursor-pointer select-none flex items-center justify-between gap-2 w-full">
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Running Lines</div>
                    <div class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                        {{ $runningSessionsCount }} <span class="text-xs font-semibold text-slate-400">/ {{ count($lines) }}</span>
                    </div>
                </div>
                <span class="w-2.5 h-2.5 rounded-full {{ $runningSessionsCount > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' }} shrink-0"></span>
            </button>

            {{-- KPI Tab 2: Queued Work Orders --}}
            <button type="button" @click="activeTab = (activeTab === 'ready' ? 'all' : 'ready')"
                :class="activeTab === 'ready' ? 'border-blue-500 bg-blue-50/80 ring-2 ring-blue-400 shadow-xs' : 'border-slate-200 bg-white hover:border-blue-300 shadow-xs'"
                class="px-3.5 py-2.5 rounded-xl border text-left transition cursor-pointer select-none flex items-center justify-between gap-2 w-full">
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Queued Orders</div>
                    <div class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                        {{ $pendingWoCount }} <span class="text-xs font-semibold text-slate-400">Orders</span>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 shrink-0">
                    Queue
                </span>
            </button>

            {{-- KPI Tab 3: First Piece Inspections --}}
            <button type="button" @click="activeTab = (activeTab === 'qc_gate' ? 'all' : 'qc_gate')"
                :class="activeTab === 'qc_gate' ? 'border-amber-500 bg-amber-50/80 ring-2 ring-amber-400 shadow-xs' : 'border-slate-200 bg-white hover:border-amber-300 shadow-xs'"
                class="px-3.5 py-2.5 rounded-xl border text-left transition cursor-pointer select-none flex items-center justify-between gap-2 w-full">
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">QC Gate</div>
                    <div class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                        {{ $pendingQcGateCount }} <span class="text-xs font-semibold text-slate-400">Pending</span>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">
                    {{ $approvedFirstPieceCount }} Appr
                </span>
            </button>

            {{-- KPI Tab 4: Shift Total Output --}}
            <button type="button" @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'border-slate-300 bg-slate-50 ring-1 ring-slate-300 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300 shadow-xs'"
                class="px-3.5 py-2.5 rounded-xl border text-left transition cursor-pointer select-none flex items-center justify-between gap-2 w-full">
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate">Shift Output</div>
                    <div class="text-base sm:text-lg font-bold text-slate-900 leading-tight font-mono">
                        {{ number_format($totalShiftGood) }} <span class="text-xs font-normal text-slate-500">Pcs</span>
                    </div>
                </div>
                <span class="text-xs font-mono font-bold {{ $overallNgRate > 2 ? 'text-rose-600' : 'text-emerald-600' }} shrink-0">
                    {{ $overallNgRate }}% NG
                </span>
            </button>
        </div>

        {{-- Active Tab Filter Banner --}}
        <div x-show="activeTab !== 'all'" x-transition class="bg-blue-50 border border-blue-200 p-3 rounded-xl flex items-center justify-between text-xs text-blue-900">
            <div class="flex items-center gap-2 font-bold">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <span>Filtered Grid View: <strong class="uppercase text-blue-950" x-text="activeTab === 'running' ? 'Active Running Lines' : (activeTab === 'ready' ? 'Queued / Ready Work Orders' : (activeTab === 'qc_gate' ? 'QC Gate Pending Lines' : activeTab))"></strong></span>
            </div>
            <button type="button" @click="activeTab = 'all'" class="px-3 py-1 bg-white hover:bg-blue-100 text-blue-800 font-semibold rounded-lg border border-blue-200 transition uppercase tracking-wider text-[10px] cursor-pointer">
                Show All Lines
            </button>
        </div>

        {{-- Shop Floor Line Status Grid --}}
        <div>
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-widest">Shop Floor Lines Overview</h3>
                <p class="text-xs text-gray-500 font-medium">Real-time compact action cards for second process manufacturing lines</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($lines as $lineName)
                    @php
                        $report = $reports->get($lineName);
                        $assignedWo = $workOrdersByLine->get($lineName);
                        $firstPiece = null;
                        if ($assignedWo) {
                            $firstPiece = $firstPieceMap->get($assignedWo->part_number);
                        }
                        $spLines = config('mes.sp_lines', []);
                        $lineSlug = array_search($lineName, $spLines) ?: \Illuminate\Support\Str::slug($lineName);
                        $gatewayUrl = route('sp-sessions.line-gateway', ['lineSlug' => $lineSlug, 'shift' => $shift]);
                        $analyticsUrl = route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]);
                    @endphp

                    @if($report)
                        @php
                            $isRunning = $report->status === 'running';
                            $wo = $assignedWo ?? $report->workOrder;
                            $woGood = $wo ? $wo->total_good : ($report->total_good ?? 0);
                            $woReject = $wo ? $wo->total_reject : ($report->total_reject ?? 0);
                            $woTotal = $woGood + $woReject;
                            $woNgRate = $woTotal > 0 ? round(($woReject / $woTotal) * 100, 1) : 0;
                            $targetQty = $wo ? $wo->target_qty : 0;
                            $pct = $wo ? $wo->progress_percentage : 0;
                            $sessionCount = $wo && $wo->relationLoaded('sessions') ? $wo->sessions->count() : 1;
                        @endphp
                        {{-- STATE 1: RUNNING / FINISHED SESSION --}}
                        <div x-show="activeTab === 'all' || (activeTab === 'running' && '{{ $report->status }}' === 'running')" x-transition 
                             class="bg-white rounded-2xl border {{ $isRunning ? 'border-emerald-200 border-l-4 border-l-emerald-500' : 'border-slate-200' }} shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-slate-300">
                            
                            {{-- Card Header --}}
                            <div class="{{ $isRunning ? 'bg-emerald-50/60 border-emerald-100' : 'bg-slate-50 border-slate-200' }} px-4 py-2.5 border-b flex justify-between items-center">
                                <div class="flex items-center gap-2 min-w-0">
                                    <h4 class="font-bold {{ $isRunning ? 'text-emerald-950' : 'text-slate-800' }} uppercase tracking-wider text-xs truncate">
                                        {{ $lineName }}
                                    </h4>
                                    @if($isRunning)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-200 text-emerald-900 uppercase tracking-wider animate-pulse border border-emerald-300 shrink-0">
                                            Running
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-slate-200 text-slate-700 uppercase tracking-wider border border-slate-300 shrink-0">
                                            Finished
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <a href="{{ $analyticsUrl }}" title="Line Analytics" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Analytics">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </a>
                                    <a href="{{ $gatewayUrl }}" title="Line Gateway" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Gateway">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-4 space-y-3 flex-1">
                                <div>
                                    <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                        <span class="text-blue-700 font-mono">{{ $report->workOrder->wo_number ?? '-' }}</span>
                                        @if($report->is_qc_bypassed)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-amber-100 text-amber-900 border border-amber-200" title="{{ $report->qc_bypass_reason }}">QC Bypassed</span>
                                        @endif
                                    </div>
                                    <div class="font-bold text-xs text-slate-800 truncate mt-0.5" title="{{ $report->workOrder->part_name ?? '-' }}">
                                        {{ $report->workOrder->part_name ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $report->workOrder->part_number ?? '-' }}</div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500 mb-1">
                                        <span>Progress ({{ $pct }}%)</span>
                                        <span class="font-mono">{{ number_format($woGood) }} / {{ number_format($targetQty) }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 border border-slate-200 overflow-hidden">
                                        <div class="{{ $isRunning ? 'bg-emerald-500' : 'bg-slate-400' }} h-1.5 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-1 pt-2 border-t border-slate-100 text-center">
                                    <div>
                                        <div class="text-[9px] font-semibold text-slate-400 uppercase">Good</div>
                                        <div class="font-bold text-xs text-emerald-600 font-mono">{{ number_format($woGood) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[9px] font-semibold text-slate-400 uppercase">Defect</div>
                                        <div class="font-bold text-xs text-rose-600 font-mono">{{ number_format($woReject) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[9px] font-semibold text-slate-400 uppercase">NG Rate</div>
                                        <div class="font-bold text-xs {{ $woNgRate > 2 ? 'text-rose-600' : 'text-emerald-600' }} font-mono">{{ $woNgRate }}%</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Single Full-Width Action Button --}}
                            <div class="p-3 bg-slate-50 border-t border-slate-100">
                                @if($isRunning)
                                    <a href="{{ route('app.sp-sessions.show', $report->id) }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                        Buka Sesi Operator
                                    </a>
                                @else
                                    <a href="{{ route('app.sp-sessions.show', $report->id) }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                        Lihat Detail Sesi
                                    </a>
                                @endif
                            </div>
                        </div>

                    @elseif($assignedWo && $assignedWo->status === 'completed')
                        {{-- STATE 2: COMPLETED WORK ORDER --}}
                        <div x-show="activeTab === 'all'" x-transition class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between transition opacity-85 hover:opacity-100 hover:shadow-md">
                            {{-- Card Header --}}
                            <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex justify-between items-center">
                                <div class="flex items-center gap-2 min-w-0">
                                    <h4 class="font-bold text-slate-800 uppercase tracking-wider text-xs truncate">{{ $lineName }}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-slate-200 text-slate-700 uppercase tracking-wider border border-slate-300 shrink-0">
                                        Completed
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <a href="{{ $analyticsUrl }}" title="Line Analytics" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Analytics">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </a>
                                    <a href="{{ $gatewayUrl }}" title="Line Gateway" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Gateway">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-4 space-y-3 flex-1">
                                <div>
                                    <div class="text-[10px] font-semibold text-blue-700 font-mono">{{ $assignedWo->wo_number }}</div>
                                    <div class="font-bold text-xs text-slate-800 truncate mt-0.5" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $assignedWo->part_number }}</div>
                                </div>

                                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center text-xs">
                                    <div>
                                        <div class="text-[9px] font-semibold text-slate-400 uppercase">Target</div>
                                        <div class="font-bold text-slate-900 font-mono">{{ number_format($assignedWo->target_qty) }} Pcs</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[9px] font-semibold text-slate-400 uppercase">Produced</div>
                                        <div class="font-bold text-emerald-600 font-mono">{{ number_format($assignedWo->total_good) }} Pcs</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Single Full-Width Action Button --}}
                            <div class="p-3 bg-slate-50 border-t border-slate-100">
                                <a href="{{ route('sp-work-orders.show', $assignedWo->id) }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-slate-700 hover:bg-slate-800 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                    Lihat Detail Order
                                </a>
                            </div>
                        </div>

                    @elseif($assignedWo)
                        @php
                            $isApproved = $firstPiece && $firstPiece->isApproved();
                        @endphp

                        @if($isApproved)
                            {{-- STATE 3: READY TO START (QC APPROVED) --}}
                            <div x-show="activeTab === 'all' || activeTab === 'ready'" x-transition class="bg-white rounded-2xl border border-blue-200 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-blue-300">
                                {{-- Card Header --}}
                                <div class="bg-blue-50/70 px-4 py-2.5 border-b border-blue-100 flex justify-between items-center">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <h4 class="font-bold text-blue-950 uppercase tracking-wider text-xs truncate">{{ $lineName }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-blue-200 text-blue-900 uppercase tracking-wider border border-blue-300 shrink-0">
                                            QC Approved
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <a href="{{ $analyticsUrl }}" title="Line Analytics" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Analytics">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                        </a>
                                        <a href="{{ $gatewayUrl }}" title="Line Gateway" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Gateway">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    </div>
                                </div>

                                {{-- Card Body --}}
                                <div class="p-4 space-y-3 flex-1">
                                    <div>
                                        <div class="text-[10px] font-semibold text-blue-700 font-mono">{{ $assignedWo->wo_number }}</div>
                                        <div class="font-bold text-xs text-slate-800 truncate mt-0.5" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono">{{ $assignedWo->part_number }}</div>
                                    </div>

                                    <div class="p-2.5 bg-blue-50/60 rounded-xl border border-blue-100 flex justify-between items-center text-xs">
                                        <span class="text-[10px] font-semibold text-blue-800 uppercase">Target Order</span>
                                        <span class="font-bold text-blue-950 font-mono">{{ number_format($assignedWo->target_qty) }} Pcs</span>
                                    </div>
                                </div>

                                {{-- Single Full-Width Action Button --}}
                                <div class="p-3 bg-slate-50 border-t border-slate-100">
                                    <form action="{{ route('sp-sessions.start', $assignedWo->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full min-h-[40px] flex items-center justify-center text-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider cursor-pointer">
                                            Mulai Produksi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            {{-- STATE 4: GATE BLOCKED (PENDING QC FIRST PIECE) --}}
                            <div x-show="activeTab === 'all' || activeTab === 'qc_gate' || activeTab === 'ready'" x-transition class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-amber-300">
                                {{-- Card Header --}}
                                <div class="bg-amber-50/70 px-4 py-2.5 border-b border-amber-100 flex justify-between items-center">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <h4 class="font-bold text-amber-950 uppercase tracking-wider text-xs truncate">{{ $lineName }}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-amber-200 text-amber-900 uppercase tracking-wider border border-amber-300 shrink-0">
                                            QC Gate Pending
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <a href="{{ $analyticsUrl }}" title="Line Analytics" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Analytics">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </a>
                                    <a href="{{ $gatewayUrl }}" title="Line Gateway" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Gateway">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-4 space-y-3 flex-1">
                                <div>
                                    <div class="text-[10px] font-semibold text-amber-800 font-mono">{{ $assignedWo->wo_number }}</div>
                                    <div class="font-bold text-xs text-slate-800 truncate mt-0.5" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $assignedWo->part_number }}</div>
                                </div>

                                <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-xs font-medium text-amber-900">
                                    First Piece QC diperlukan sebelum produksi dapat dimulai.
                                </div>
                            </div>

                            {{-- Single Full-Width Action Button --}}
                            <div class="p-3 bg-slate-50 border-t border-slate-100">
                                @can('execute-qc-inspections')
                                    <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $assignedWo->id, 'part_number' => $assignedWo->part_number, 'part_name' => $assignedWo->part_name, 'model' => $assignedWo->model]) }}"
                                        class="w-full min-h-[40px] flex items-center justify-center text-center bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                        Input First Piece QC
                                    </a>
                                @else
                                    <a href="{{ $gatewayUrl }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                        Buka Line Gateway
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                @else
                    {{-- STATE 5: IDLE LINE --}}
                    <div x-show="activeTab === 'all' || activeTab === 'idle'" x-transition class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-slate-300">
                        {{-- Card Header --}}
                        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-100 flex justify-between items-center">
                            <div class="flex items-center gap-2 min-w-0">
                                <h4 class="font-bold text-slate-600 uppercase tracking-wider text-xs truncate">{{ $lineName }}</h4>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold bg-slate-100 text-slate-500 uppercase tracking-wider border border-slate-200 shrink-0">
                                    Idle
                                </span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <a href="{{ $analyticsUrl }}" title="Line Analytics" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Analytics">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </a>
                                <a href="{{ $gatewayUrl }}" title="Line Gateway" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition" aria-label="Line Gateway">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                </a>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4 flex flex-col items-center justify-center text-center py-8 flex-1">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-0.5">Line Tersedia</p>
                            <p class="text-[11px] text-slate-400 font-medium">Belum ada work order aktif</p>
                        </div>

                        {{-- Single Full-Width Action Button --}}
                        <div class="p-3 bg-slate-50 border-t border-slate-100">
                            @can('create', App\Models\SpWorkOrder::class)
                                <a href="{{ route('sp-work-orders.create', ['unit_line' => $lineName]) }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                    Buat Work Order
                                </a>
                            @else
                                <a href="{{ $gatewayUrl }}" class="w-full min-h-[40px] flex items-center justify-center text-center bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white font-semibold px-4 rounded-xl shadow-xs text-xs transition uppercase tracking-wider">
                                    Buka Line Gateway
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif
            @endforeach

            @if($runningSessionsCount === 0)
                <div x-show="activeTab === 'running'" class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-200 shadow-sm space-y-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl font-bold">
                        0
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Sesi Produksi yang Sedang Berjalan</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Saat ini belum ada line produksi yang aktif berjalan di shift ini.</p>
                    </div>
                    <div class="pt-1">
                        <button type="button" @click="activeTab = 'all'" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition uppercase tracking-wider cursor-pointer">
                            Tampilkan Semua Line
                        </button>
                    </div>
                </div>
            @endif

            @if($pendingWoCount === 0)
                <div x-show="activeTab === 'ready'" class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-200 shadow-sm space-y-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl font-bold">
                        0
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Work Order yang Mengantre</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Saat ini tidak ada work order terencana yang menunggu untuk diproduksi.</p>
                    </div>
                    <div class="pt-1 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('sp-work-orders.index') }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs transition uppercase tracking-wider">
                            Daftar Work Order
                        </a>
                        <button type="button" @click="activeTab = 'all'" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition uppercase tracking-wider cursor-pointer">
                            Tampilkan Semua Line
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
</x-operator-layout>
