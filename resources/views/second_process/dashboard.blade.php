<x-operator-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-xl text-gray-800 uppercase tracking-wide">
                    {{ __('Second Process Operator & Shop Floor Dashboard') }}
                </h2>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Real-time line status, First Piece QC gate tracking, and active session management</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                    Main MES
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
            activeTab: 'all',
            detailedMode: localStorage.getItem('sp_dashboard_detailed_mode') === 'true',
            toggleDetailedMode() {
                this.detailedMode = !this.detailedMode;
                localStorage.setItem('sp_dashboard_detailed_mode', this.detailedMode);
            },
            init() { 
                setInterval(() => { 
                    if(!document.hidden) window.location.reload(); 
                }, 30000);
            } 
        }">

        {{-- Filter Control Bar --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
            <form action="{{ route('second-process.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2">
                    <label class="font-black text-xs uppercase text-gray-500">Shift:</label>
                    <select name="shift" class="rounded-xl border-gray-300 text-xs font-bold py-1.5 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="all" {{ (string)$shift === 'all' ? 'selected' : '' }}>All Today's Shifts</option>
                        @foreach(config('mes.sp_shifts', config('mes.shifts', [])) as $sId => $sConf)
                            <option value="{{ $sId }}" {{ (string)$shift === (string)$sId ? 'selected' : '' }}>{{ $sConf['name'] }} ({{ $sConf['start'] }} - {{ $sConf['end'] }})</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button type="submit" class="bg-gray-100 px-3 py-1 text-xs font-bold rounded-xl border border-gray-300">Filter</button></noscript>
            </form>

            <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                <button type="button" @click="toggleDetailedMode()"
                    :class="detailedMode ? 'bg-blue-600 text-white shadow-sm font-black' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 font-bold'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 uppercase tracking-wider">
                    <span x-text="detailedMode ? 'Compact View' : 'Detailed View'"></span>
                </button>
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Floor View</span>
                </div>
            </div>
        </div>

        {{-- Default Compact Metric Strip (shows when !detailedMode) --}}
        <div x-show="!detailedMode" x-transition class="bg-gray-900 text-white px-5 py-3 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs font-bold shadow-md">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" @click="activeTab = (activeTab === 'running' ? 'all' : 'running')" 
                        :class="activeTab === 'running' ? 'bg-emerald-500 text-white font-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" 
                        class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Running: {{ $runningSessionsCount }}/{{ count($lines) }}</span>
                    @php $bypassedCount = $reports->filter(fn($r) => $r->status === 'running' && $r->is_qc_bypassed)->count(); @endphp
                    @if($bypassedCount > 0)
                        <span class="text-purple-300 font-mono text-[10px]">({{ $bypassedCount }} Bypassed)</span>
                    @endif
                </button>

                <button type="button" @click="activeTab = (activeTab === 'ready' ? 'all' : 'ready')" 
                        :class="activeTab === 'ready' ? 'bg-blue-600 text-white font-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" 
                        class="px-3 py-1.5 rounded-xl transition">
                    <span>Queued: {{ $pendingWoCount }}</span>
                </button>

                <button type="button" @click="activeTab = (activeTab === 'qc_gate' ? 'all' : 'qc_gate')" 
                        :class="activeTab === 'qc_gate' ? 'bg-amber-600 text-white font-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" 
                        class="px-3 py-1.5 rounded-xl transition">
                    <span>QC Gate: {{ $pendingQcGateCount }} Pending</span>
                </button>

                <div class="text-gray-300 border-l border-gray-700 pl-3">
                    <span>Output: <strong class="text-white font-black font-mono">{{ number_format($totalShiftGood) }} Pcs</strong></span>
                    <span class="ml-2 font-mono font-bold {{ $overallNgRate > 2 ? 'text-red-400' : 'text-emerald-400' }}">({{ $overallNgRate }}% NG)</span>
                </div>
            </div>

            <button type="button" @click="toggleDetailedMode()" class="text-[11px] font-black text-gray-400 hover:text-white uppercase tracking-wider flex items-center gap-1">
                Expand Detailed Cards ↗
            </button>
        </div>

        {{-- Top KPI Metric Cards (Detailed Expanded View - shows when detailedMode is ON) --}}
        <div x-show="detailedMode" x-transition class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
            {{-- KPI Tab 1: Active Production Lines --}}
            <button type="button" @click="activeTab = (activeTab === 'running' ? 'all' : 'running')"
                :class="activeTab === 'running' ? 'border-emerald-500 bg-emerald-50/70 ring-2 ring-emerald-400 shadow-md' : 'border-gray-200 bg-white hover:border-emerald-300 shadow-sm'"
                class="p-3.5 sm:p-4 md:p-5 rounded-2xl border text-left transition cursor-pointer select-none flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 w-full">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-wider mb-0.5 sm:mb-1">Active Production Lines</div>
                    <div class="text-lg sm:text-xl md:text-2xl font-black text-gray-900">{{ $runningSessionsCount }} <span class="text-xs text-gray-400 font-bold">/ {{ count($lines) }} Lines</span></div>
                </div>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-black bg-emerald-100 text-emerald-800 uppercase tracking-widest border border-emerald-200">
                    Running
                </span>
            </button>

            {{-- KPI Tab 2: Queued Work Orders --}}
            <button type="button" @click="activeTab = (activeTab === 'ready' ? 'all' : 'ready')"
                :class="activeTab === 'ready' ? 'border-blue-500 bg-blue-50/70 ring-2 ring-blue-400 shadow-md' : 'border-gray-200 bg-white hover:border-blue-300 shadow-sm'"
                class="p-3.5 sm:p-4 md:p-5 rounded-2xl border text-left transition cursor-pointer select-none flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 w-full">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-wider mb-0.5 sm:mb-1">Queued Work Orders</div>
                    <div class="text-lg sm:text-xl md:text-2xl font-black text-gray-900">{{ $pendingWoCount }} <span class="text-xs text-gray-400 font-bold">Orders</span></div>
                </div>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-black bg-blue-100 text-blue-800 uppercase tracking-widest border border-blue-200">
                    Pending
                </span>
            </button>

            {{-- KPI Tab 3: First Piece Inspections --}}
            <button type="button" @click="activeTab = (activeTab === 'qc_gate' ? 'all' : 'qc_gate')"
                :class="activeTab === 'qc_gate' ? 'border-amber-500 bg-amber-50/70 ring-2 ring-amber-400 shadow-md' : 'border-gray-200 bg-white hover:border-amber-300 shadow-sm'"
                class="p-3.5 sm:p-4 md:p-5 rounded-2xl border text-left transition cursor-pointer select-none flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 w-full">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-wider mb-0.5 sm:mb-1">First Piece QC Gate</div>
                    <div class="text-lg sm:text-xl md:text-2xl font-black text-gray-900">{{ $pendingQcGateCount }} <span class="text-xs text-gray-400 font-bold">Pending QC</span></div>
                </div>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-black bg-amber-100 text-amber-800 uppercase tracking-widest border border-amber-200">
                    {{ $approvedFirstPieceCount }} Approved
                </span>
            </button>

            {{-- KPI Tab 4: Shift Total Output --}}
            <button type="button" @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'border-gray-300 bg-gray-50/80 ring-1 ring-gray-300 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 shadow-sm'"
                class="p-3.5 sm:p-4 md:p-5 rounded-2xl border text-left transition cursor-pointer select-none flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 w-full">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-wider mb-0.5 sm:mb-1">Shift Total Output</div>
                    <div class="text-lg sm:text-xl md:text-2xl font-black text-gray-900">{{ number_format($totalShiftGood) }} <span class="text-xs text-gray-400 font-bold">Pcs</span></div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-wider mb-0.5 sm:mb-1">NG Rate</div>
                    <div class="text-xs sm:text-sm font-black {{ $overallNgRate > 2 ? 'text-red-600' : 'text-emerald-600' }}">{{ $overallNgRate }}%</div>
                </div>
            </button>
        </div>

        {{-- Active Tab Filter Banner --}}
        <div x-show="activeTab !== 'all'" x-transition class="bg-blue-50 border border-blue-200 p-3 rounded-xl flex items-center justify-between text-xs text-blue-900">
            <div class="flex items-center gap-2 font-bold">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <span>Filtered Grid View: <strong class="uppercase text-blue-950" x-text="activeTab === 'running' ? 'Active Running Lines' : (activeTab === 'ready' ? 'Queued / Ready Work Orders' : (activeTab === 'qc_gate' ? 'QC Gate Pending Lines' : activeTab))"></strong></span>
            </div>
            <button type="button" @click="activeTab = 'all'" class="px-3 py-1 bg-white hover:bg-blue-100 text-blue-800 font-black rounded-lg border border-blue-200 transition uppercase tracking-wider text-[10px]">
                Show All Lines
            </button>
        </div>

        {{-- Shop Floor Line Status Grid --}}
        <div>
            <div class="mb-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Shop Floor Lines Overview</h3>
                <p class="text-xs text-gray-500 font-medium">Click any line card to enter its Line Gateway screen</p>
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
                             class="bg-white rounded-2xl border {{ $isRunning ? ($report->is_qc_bypassed ? 'border-l-4 border-l-purple-500 border-slate-200' : 'border-l-4 border-l-emerald-500 border-slate-200') : 'border-slate-200' }} shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-slate-300">
                            {{-- Clickable Header & Content Body (Line Gateway Trigger) --}}
                            <a href="{{ $gatewayUrl }}" class="block group">
                                <div class="{{ $isRunning ? 'bg-emerald-50 border-emerald-100 group-hover:bg-emerald-100/70' : 'bg-slate-100 border-slate-200 group-hover:bg-slate-200/70' }} px-4 py-2.5 border-b flex justify-between items-center transition">
                                    <h4 class="font-black {{ $isRunning ? 'text-emerald-950' : 'text-slate-800' }} uppercase tracking-wider text-sm flex items-center gap-1.5">
                                        {{ $lineName }}
                                    </h4>
                                    <div class="flex items-center gap-1.5">
                                        @if($report->is_qc_bypassed)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-purple-100 text-purple-900 uppercase tracking-wider border border-purple-300">
                                                QC Bypassed
                                            </span>
                                        @endif
                                        @if($isRunning)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-200 text-emerald-900 uppercase tracking-widest animate-pulse border border-emerald-300">
                                                Running
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700 uppercase tracking-widest border border-slate-300">
                                                Finished
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Standard Expanded Content (shows when detailedMode is ON) --}}
                                <div x-show="detailedMode" class="p-5 space-y-4">
                                    <div>
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Work Order & Part</div>
                                        <div class="font-black text-xs text-blue-700">{{ $report->workOrder->wo_number ?? '-' }}</div>
                                        <div class="font-bold text-sm text-gray-800 truncate" title="{{ $report->workOrder->part_name ?? '-' }}">{{ $report->workOrder->part_name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500 font-mono font-medium">{{ $report->workOrder->part_number ?? '-' }}</div>
                                    </div>

                                    @if($report->is_qc_bypassed && $report->qc_bypass_reason)
                                        <div class="flex items-center justify-between gap-2 px-3 py-1.5 bg-purple-50 border border-purple-200 rounded-xl text-xs font-bold text-purple-950">
                                            <span class="truncate" title="{{ $report->qc_bypass_reason }}"><strong class="font-black uppercase text-purple-700">QC Bypass:</strong> {{ $report->qc_bypass_reason }}</span>
                                            <a href="{{ route('first-piece-inspections.create', [
                                                    'work_order_id' => $report->work_order_id,
                                                    'part_number' => $report->workOrder->part_number ?? '',
                                                    'part_name' => $report->workOrder->part_name ?? '',
                                                    'model' => $report->workOrder->model ?? ''
                                                ]) }}" 
                                               class="px-2.5 py-1 bg-purple-700 hover:bg-purple-800 text-white font-black text-[10px] rounded-lg shadow-sm transition uppercase tracking-wider whitespace-nowrap">
                                                + First Piece
                                            </a>
                                        </div>
                                    @endif

                                    <div>
                                        <div class="flex justify-between items-center text-[10px] font-black text-gray-500 uppercase mb-1">
                                            <span>Progress</span>
                                            <span>{{ number_format($woGood) }} / {{ number_format($targetQty) }} Pcs ({{ $pct }}%)</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden border border-gray-200">
                                            <div class="{{ $isRunning ? 'bg-emerald-500' : 'bg-slate-400' }} h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        @if($sessionCount > 1)
                                            <div class="text-[10px] font-bold text-gray-400 text-right mt-1">
                                                Cumulative across {{ $sessionCount }} sessions
                                            </div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 text-center">
                                        <div>
                                            <div class="text-[9px] font-black text-gray-400 uppercase">Total Good</div>
                                            <div class="font-black text-sm text-emerald-600">{{ number_format($woGood) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[9px] font-black text-gray-400 uppercase">Total Defects</div>
                                            <div class="font-black text-sm text-red-600">{{ number_format($woReject) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[9px] font-black text-gray-400 uppercase">NG Rate</div>
                                            <div class="font-black text-sm {{ $woNgRate > 2 ? 'text-red-600' : 'text-emerald-600' }}">{{ $woNgRate }}%</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Default Compact High-Density Content (shows when !detailedMode) --}}
                                <div x-show="!detailedMode" class="p-3.5 space-y-2 text-xs">
                                    <div class="flex justify-between items-center font-bold text-gray-800">
                                        <span class="text-blue-700 font-black font-mono text-xs">{{ $report->workOrder->wo_number ?? '-' }}</span>
                                        <span class="truncate ml-2 text-xs font-semibold text-gray-700" title="{{ $report->workOrder->part_name ?? '-' }}">{{ $report->workOrder->part_name ?? '-' }}</span>
                                    </div>

                                    @if($report->is_qc_bypassed)
                                        <div class="flex items-center justify-between gap-1.5 px-2.5 py-1 bg-purple-50 border border-purple-200 rounded-lg text-[10px] font-bold text-purple-900">
                                            <span class="truncate" title="{{ $report->qc_bypass_reason }}"><strong class="font-black uppercase text-purple-700">Bypass:</strong> {{ $report->qc_bypass_reason ?? 'QC Bypassed' }}</span>
                                            <a href="{{ route('first-piece-inspections.create', [
                                                    'work_order_id' => $report->work_order_id,
                                                    'part_number' => $report->workOrder->part_number ?? '',
                                                    'part_name' => $report->workOrder->part_name ?? '',
                                                    'model' => $report->workOrder->model ?? ''
                                                ]) }}" 
                                               class="px-2 py-0.5 bg-purple-700 hover:bg-purple-800 text-white font-black text-[9px] rounded shadow-sm transition uppercase tracking-wider whitespace-nowrap">
                                                + Log QC
                                            </a>
                                        </div>
                                    @endif

                                    <div>
                                        <div class="flex justify-between items-center text-[10px] font-black text-gray-500 mb-0.5">
                                            <span>Progress ({{ $pct }}%)</span>
                                            <span>{{ number_format($woGood) }} / {{ number_format($targetQty) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 border border-gray-200 overflow-hidden">
                                            <div class="{{ $isRunning ? 'bg-emerald-500' : 'bg-slate-400' }} h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center pt-1.5 border-t border-gray-100 text-[10px] font-bold">
                                        <span>Good: <strong class="text-emerald-600 font-black">{{ number_format($woGood) }}</strong></span>
                                        <span>Defects: <strong class="text-red-600 font-black">{{ number_format($woReject) }}</strong></span>
                                        <span>NG: <strong class="{{ $woNgRate > 2 ? 'text-red-600' : 'text-emerald-600' }} font-black">{{ $woNgRate }}%</strong></span>
                                    </div>
                                </div>
                            </a>

                            {{-- Bottom Actions --}}
                            <div :class="detailedMode ? 'p-3.5' : 'p-2.5'" class="bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                <a href="{{ route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-1/3 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-2 rounded-xl text-xs transition uppercase tracking-wider">
                                    Analytics
                                </a>
                                <a href="{{ route('app.sp-sessions.show', $report->id) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-2/3 text-center {{ $isRunning ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800' : 'bg-slate-700 hover:bg-slate-800 active:bg-slate-900' }} text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                    {{ $isRunning ? 'Operator Screen' : 'View Session' }}
                                </a>
                            </div>
                        </div>

                    @elseif($assignedWo && $assignedWo->status === 'completed')
                        {{-- STATE 4: COMPLETED WORK ORDER --}}
                        <div x-show="activeTab === 'all'" x-transition class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between transition opacity-85 hover:opacity-100 hover:shadow-md">
                            <a href="{{ $gatewayUrl }}" class="block group">
                                <div class="bg-slate-100 px-4 py-2.5 border-b border-slate-200 flex justify-between items-center group-hover:bg-slate-200/70 transition">
                                    <h4 class="font-black text-slate-800 uppercase tracking-wider text-sm">{{ $lineName }}</h4>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700 uppercase tracking-widest border border-slate-300">
                                        Completed
                                    </span>
                                </div>

                                <div x-show="detailedMode" class="p-5 space-y-3">
                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Completed Work Order</div>
                                        <div class="font-black text-xs text-blue-700">{{ $assignedWo->wo_number }}</div>
                                        <div class="font-bold text-sm text-slate-800 truncate" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                        <div class="text-xs text-slate-500 font-mono font-medium">{{ $assignedWo->part_number }}</div>
                                    </div>

                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase">Target</div>
                                            <div class="text-sm font-black text-slate-900">{{ number_format($assignedWo->target_qty) }} Pcs</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] font-black text-slate-400 uppercase">Produced</div>
                                            <div class="text-sm font-black text-emerald-600">{{ number_format($assignedWo->total_good) }} Pcs</div>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="!detailedMode" class="p-3.5 space-y-2 text-xs">
                                    <div class="flex justify-between items-center font-bold text-slate-800">
                                        <span class="text-blue-700 font-black font-mono text-xs">{{ $assignedWo->wo_number }}</span>
                                        <span class="truncate ml-2 text-xs font-semibold text-slate-700" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-1.5 border-t border-slate-100 text-[10px] font-bold">
                                        <span>Produced: <strong class="text-emerald-600 font-black">{{ number_format($assignedWo->total_good) }} / {{ number_format($assignedWo->target_qty) }} Pcs</strong></span>
                                    </div>
                                </div>
                            </a>

                            <div :class="detailedMode ? 'p-3.5' : 'p-2.5'" class="bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                <a href="{{ route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-1/3 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-2 rounded-xl text-xs transition uppercase tracking-wider">
                                    Analytics
                                </a>
                                <a href="{{ route('sp-work-orders.show', $assignedWo->id) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-2/3 text-center bg-slate-700 hover:bg-slate-800 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                    View Order
                                </a>
                            </div>
                        </div>

                    @elseif($assignedWo)
                        @php
                            $isApproved = $firstPiece && $firstPiece->isApproved();
                        @endphp

                        @if($isApproved)
                            {{-- STATE 2: READY TO START (QC APPROVED) --}}
                            <div x-show="activeTab === 'all' || activeTab === 'ready'" x-transition class="bg-white rounded-2xl border border-blue-300 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-blue-400">
                                <a href="{{ $gatewayUrl }}" class="block group">
                                    <div class="bg-blue-50 px-4 py-2.5 border-b border-blue-100 flex justify-between items-center group-hover:bg-blue-100/70 transition">
                                        <h4 class="font-black text-blue-950 uppercase tracking-wider text-sm">{{ $lineName }}</h4>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-200 text-blue-900 uppercase tracking-widest border border-blue-300">
                                            QC Approved
                                        </span>
                                    </div>

                                    {{-- Standard Expanded Content --}}
                                    <div x-show="detailedMode" class="p-5 space-y-3">
                                        <div>
                                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Assigned Work Order</div>
                                            <div class="font-black text-xs text-blue-700">{{ $assignedWo->wo_number }}</div>
                                            <div class="font-bold text-sm text-gray-800 truncate" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                            <div class="text-xs text-gray-500 font-mono font-medium">{{ $assignedWo->part_number }}</div>
                                        </div>

                                        <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                                            <div class="text-[10px] font-black text-blue-800 uppercase">Target Quantity</div>
                                            <div class="text-base font-black text-blue-950">{{ number_format($assignedWo->target_qty) }} Pcs</div>
                                        </div>
                                    </div>

                                    {{-- Default Compact High-Density Content --}}
                                    <div x-show="!detailedMode" class="p-3.5 space-y-2 text-xs">
                                        <div class="flex justify-between items-center font-bold text-gray-800">
                                            <span class="text-blue-700 font-black font-mono text-xs">{{ $assignedWo->wo_number }}</span>
                                            <span class="truncate ml-2 text-xs font-semibold text-gray-700" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</span>
                                        </div>
                                        <div class="px-2.5 py-1 bg-blue-50/70 text-blue-900 text-xs font-black rounded-lg border border-blue-100 text-center">
                                            Target: {{ number_format($assignedWo->target_qty) }} Pcs
                                        </div>
                                    </div>
                                </a>

                                <div :class="detailedMode ? 'p-3.5' : 'p-2.5'" class="bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                    <a href="{{ route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-1/3 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-2 rounded-xl text-xs transition uppercase tracking-wider">
                                        Analytics
                                    </a>
                                    <form action="{{ route('sp-sessions.start', $assignedWo->id) }}" method="POST" class="w-2/3">
                                        @csrf
                                        <button type="submit" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-full text-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                            Start Production
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            {{-- STATE 3: GATE BLOCKED (PENDING QC FIRST PIECE) --}}
                            <div x-show="activeTab === 'all' || activeTab === 'qc_gate'" x-transition class="bg-white rounded-2xl border border-amber-300 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-amber-400">
                                <a href="{{ $gatewayUrl }}" class="block group">
                                    <div class="bg-amber-50 px-4 py-2.5 border-b border-amber-100 flex justify-between items-center group-hover:bg-amber-100/70 transition">
                                        <h4 class="font-black text-amber-950 uppercase tracking-wider text-sm">{{ $lineName }}</h4>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-200 text-amber-900 uppercase tracking-widest border border-amber-300">
                                            QC Gate Pending
                                        </span>
                                    </div>

                                    {{-- Standard Expanded Content --}}
                                    <div x-show="detailedMode" class="p-5 space-y-3">
                                        <div>
                                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Assigned Work Order</div>
                                            <div class="font-black text-xs text-amber-800">{{ $assignedWo->wo_number }}</div>
                                            <div class="font-bold text-sm text-gray-800 truncate" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</div>
                                            <div class="text-xs text-gray-500 font-mono font-medium">{{ $assignedWo->part_number }}</div>
                                        </div>

                                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs font-semibold text-amber-900">
                                            First Piece Inspection is required before starting production session.
                                        </div>
                                    </div>

                                    {{-- Default Compact High-Density Content --}}
                                    <div x-show="!detailedMode" class="p-3.5 space-y-2 text-xs">
                                        <div class="flex justify-between items-center font-bold text-gray-800">
                                            <span class="text-amber-800 font-black font-mono text-xs">{{ $assignedWo->wo_number }}</span>
                                            <span class="truncate ml-2 text-xs font-semibold text-gray-700" title="{{ $assignedWo->part_name }}">{{ $assignedWo->part_name }}</span>
                                        </div>
                                        <div class="px-2.5 py-1 bg-amber-50 rounded-lg border border-amber-200 text-[11px] font-bold text-amber-900 text-center flex items-center justify-center gap-1">
                                            ⚠️ First Piece Inspection Required
                                        </div>
                                    </div>
                                </a>

                                <div :class="detailedMode ? 'p-3.5' : 'p-2.5'" class="bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                    <a href="{{ route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-1/3 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-2 rounded-xl text-xs transition uppercase tracking-wider">
                                        Analytics
                                    </a>
                                    @can('execute-qc-inspections')
                                        <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $assignedWo->id, 'part_number' => $assignedWo->part_number, 'part_name' => $assignedWo->part_name, 'model' => $assignedWo->model]) }}"
                                            :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="block w-2/3 text-center bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                            First Piece Inspection
                                        </a>
                                    @else
                                        <a href="{{ $gatewayUrl }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="block w-2/3 text-center bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                            Open Gateway
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        @endif

                    @else
                        {{-- STATE 4: IDLE LINE --}}
                        <div x-show="activeTab === 'all' || activeTab === 'idle'" x-transition class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col justify-between transition hover:shadow-md hover:border-gray-300">
                            <a href="{{ $gatewayUrl }}" class="block group">
                                <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-100 flex justify-between items-center group-hover:bg-gray-100/80 transition">
                                    <h4 class="font-black text-gray-600 uppercase tracking-wider text-sm">{{ $lineName }}</h4>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-500 uppercase tracking-widest border border-gray-200">
                                        Idle
                                    </span>
                                </div>

                                {{-- Standard Expanded Content --}}
                                <div x-show="detailedMode" class="p-5 flex flex-col items-center justify-center text-center py-10">
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-wider mb-1">No Active Production</p>
                                    <p class="text-[11px] text-gray-500 font-medium">Line is available for assignment</p>
                                </div>

                                {{-- Default Compact High-Density Content --}}
                                <div x-show="!detailedMode" class="py-2.5 px-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                    Line Available
                                </div>
                            </a>

                            <div :class="detailedMode ? 'p-3.5' : 'p-2.5'" class="bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                <a href="{{ route('second-process.line-dashboard', ['line' => $lineSlug, 'date' => $date, 'shift' => $shift]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="w-1/3 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-2 rounded-xl text-xs transition uppercase tracking-wider">
                                    Analytics
                                </a>
                                @can('manage-sp-work-orders')
                                    <a href="{{ route('sp-work-orders.create', ['unit_line' => $lineName]) }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="block w-2/3 text-center bg-gray-700 hover:bg-gray-800 active:bg-gray-900 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                        Create Work Order
                                    </a>
                                @else
                                    <a href="{{ $gatewayUrl }}" :class="detailedMode ? 'py-2.5' : 'py-1.5'" class="block w-2/3 text-center bg-gray-700 hover:bg-gray-800 active:bg-gray-900 text-white font-black px-4 rounded-xl shadow-sm text-xs transition uppercase tracking-wider">
                                        Open Gateway
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

    </div>
</x-operator-layout>
