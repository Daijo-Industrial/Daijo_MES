<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-black text-2xl text-gray-800 leading-tight tracking-tight">
                        {{ $workOrder->wo_number }}
                    </h2>
                    @switch($workOrder->status)
                        @case('draft') <span class="px-3 py-1 text-[10px] font-black rounded-full bg-slate-100 text-slate-700 uppercase tracking-widest border border-slate-200">Draft</span> @break
                        @case('planned') <span class="px-3 py-1 text-[10px] font-black rounded-full bg-blue-100 text-blue-800 uppercase tracking-widest border border-blue-200">Planned</span> @break
                        @case('in_progress') <span class="px-3 py-1 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-widest border border-emerald-200">Running</span> @break
                        @case('completed') <span class="px-3 py-1 text-[10px] font-black rounded-full bg-gray-100 text-gray-700 uppercase tracking-widest border border-gray-200">Completed</span> @break
                        @case('cancelled') <span class="px-3 py-1 text-[10px] font-black rounded-full bg-red-100 text-red-800 uppercase tracking-widest border border-red-200">Cancelled</span> @break
                    @endswitch
                </div>
                <p class="text-xs text-gray-500 mt-1 font-semibold">Created by {{ $workOrder->creator?->name ?? 'System' }} on {{ $workOrder->created_at->format('d M Y, H:i') }}</p>
            </div>

            @php
                $activeSession = $workOrder->sessions->where('status', 'running')->first();
                $completedSessions = $workOrder->sessions->where('status', 'completed');
                $isFpiApproved = isset($firstPiece) && $firstPiece && $firstPiece->isApproved();
            @endphp

            <div class="flex flex-wrap items-center gap-2">
                @if($workOrder->status === 'draft')
                    <a href="{{ route('sp-work-orders.edit', $workOrder->id) }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                        Edit Draft
                    </a>
                    <form action="{{ route('sp-work-orders.release', $workOrder->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black text-xs rounded-xl shadow-md transition uppercase tracking-wider">
                            Release Order
                        </button>
                    </form>
                @elseif($activeSession)
                    <a href="{{ route('app.sp-sessions.show', $activeSession->id) }}"
                       class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-black text-xs rounded-xl shadow-md transition uppercase tracking-wider">
                        Open Operator Screen
                    </a>
                @elseif($workOrder->status === 'planned')
                    @if($isFpiApproved)
                        <form action="{{ route('sp-sessions.start', $workOrder->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black text-xs rounded-xl shadow-md transition uppercase tracking-wider">
                                Start Production
                            </button>
                        </form>
                    @endif

                    @if($workOrder->sessions->count() === 0)
                        <form action="{{ route('sp-work-orders.revert-to-draft', $workOrder->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 bg-gray-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-sm transition uppercase tracking-wider" title="Revert to draft mode to unlock editing">
                                Revert to Draft
                            </button>
                        </form>
                    @endif
                @endif

                <a href="{{ route('sp-work-orders.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 shadow-sm transition uppercase tracking-wider">
                    Work Orders List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto space-y-6">

            @if($workOrder->status === 'draft')
                <div class="bg-slate-100 border border-slate-300 text-slate-800 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm flex justify-between items-center">
                    <div>
                        <span class="font-black text-slate-900">Work Order Draft Mode:</span>
                        <span class="font-medium text-slate-600 ml-1">This job ticket is currently in draft mode and is hidden from shop floor operators.</span>
                    </div>
                    <form action="{{ route('sp-work-orders.release', $workOrder->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl transition uppercase tracking-wider">
                            Release Order to Production
                        </button>
                    </form>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-300 text-red-900 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @php
                // Compute aggregated session metrics
                $totalInputWip = $workOrder->sessions->flatMap->productionEntries->sum('input_qty');
                $totalGood = $workOrder->total_good;
                $totalReject = $workOrder->total_reject;
                $totalProduced = $totalGood + $totalReject;
                $ngRate = $totalProduced > 0 ? round(($totalReject / $totalProduced) * 100, 1) : 0;
                $totalDowntimeMin = $workOrder->sessions->flatMap->downtimeEntries->sum('duration_minutes');
                $activeManpowerCount = $workOrder->sessions->flatMap->manpowers->count();
            @endphp

            {{-- 1. Combined Progress & Sleek Metric Strip Card (Shown when WO has active/completed sessions or is running/completed) --}}
            @if($workOrder->sessions->count() > 0 || in_array($workOrder->status, ['in_progress', 'completed']))
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
                    {{-- Progress Bar Row --}}
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Total Production Target</span>
                                <div class="text-2xl font-black text-gray-900 mt-0.5">
                                    {{ number_format($totalGood) }} <span class="text-xs font-bold text-gray-400">/ {{ number_format($workOrder->target_qty) }} Pcs</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                @if($workOrder->status === 'planned' && $totalGood > 0 && $totalGood < $workOrder->target_qty)
                                    <div class="text-right">
                                        <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest block">Remaining</span>
                                        <div class="text-lg font-black text-amber-700">
                                            {{ number_format($workOrder->target_qty - $totalGood) }} Pcs
                                        </div>
                                    </div>
                                @endif
                                <div class="text-2xl font-black {{ $workOrder->progress_percentage >= 100 ? 'text-emerald-600' : 'text-blue-600' }}">
                                    {{ $workOrder->progress_percentage }}%
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden border border-gray-200">
                            <div class="{{ $workOrder->progress_percentage >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }} h-3 rounded-full transition-all duration-700" style="width: {{ min(100, $workOrder->progress_percentage) }}%"></div>
                        </div>
                    </div>

                    {{-- Compact 1-Row Metric Strip --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-gray-100">
                        <div class="p-3 bg-gray-50/80 rounded-xl border border-gray-100">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Input WIP Received</div>
                            <div class="text-base font-black text-gray-900 mt-0.5">{{ number_format($totalInputWip) }} <span class="text-[10px] font-bold text-gray-500">Pcs</span></div>
                        </div>

                        <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100">
                            <div class="text-[10px] font-black text-emerald-800 uppercase tracking-wider">Good Output</div>
                            <div class="text-base font-black text-emerald-700 mt-0.5">{{ number_format($totalGood) }} <span class="text-[10px] font-bold text-gray-500">Pcs</span></div>
                        </div>

                        <div class="p-3 bg-red-50/50 rounded-xl border border-red-100">
                            <div class="text-[10px] font-black text-red-800 uppercase tracking-wider">Defects & NG Rate</div>
                            <div class="text-base font-black text-red-700 mt-0.5">{{ number_format($totalReject) }} <span class="text-[10px] font-bold text-gray-500">({{ $ngRate }}%)</span></div>
                        </div>

                        <div class="p-3 bg-gray-50/80 rounded-xl border border-gray-100">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Downtime / Line Team</div>
                            <div class="text-base font-black text-gray-900 mt-0.5">{{ $totalDowntimeMin }} <span class="text-[10px] font-bold text-gray-500">Min</span> • {{ $activeManpowerCount }} <span class="text-[10px] font-bold text-gray-500">Op</span></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 2. Main Work Order Specifications & QC Gate Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left 2 Columns: Single Consolidated Specs Card --}}
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4 h-full">
                        <div class="pb-3 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest">Work Order Specifications</h3>
                            <span class="text-xs font-mono font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-100">{{ $workOrder->wo_number }}</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                            {{-- Product Details --}}
                            <div class="space-y-3.5">
                                <div>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Part Number</span>
                                    <span class="font-bold text-blue-700 font-mono text-sm mt-0.5 block">{{ $workOrder->part_number }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Part Name</span>
                                    <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $workOrder->part_name }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Customer</span>
                                        <span class="font-bold text-gray-800 mt-0.5 block">{{ $workOrder->customer }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Model Code</span>
                                        <span class="font-bold text-gray-800 mt-0.5 block">{{ $workOrder->model ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Manufacturing Setup --}}
                            <div class="space-y-3.5 md:border-l md:border-gray-100 md:pl-6">
                                <div>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Process Type</span>
                                    <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $workOrder->process_prod }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Production Line</span>
                                    <span class="font-bold text-gray-800 mt-0.5 block">{{ $workOrder->unit_line }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider block">Planned Date</span>
                                    <span class="font-bold text-gray-800 mt-0.5 block">{{ \Carbon\Carbon::parse($workOrder->planned_date)->format('l, d F Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: QC Gate & Session Actions --}}
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest border-b border-gray-100 pb-3">QC First Piece Gate</h3>

                            @if($workOrder->status === 'draft')
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600 font-medium space-y-2 mt-4">
                                    <div class="flex items-center justify-between">
                                        <span class="font-black uppercase tracking-wider text-[10px] text-slate-400">Gate Status</span>
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700 uppercase">Unreleased Draft</span>
                                    </div>
                                    <p class="font-medium text-xs leading-relaxed text-slate-600">
                                        This Work Order is currently in draft mode. Release to production to enable First Piece Inspection.
                                    </p>
                                </div>
                            @else
                                <div class="p-4 rounded-xl border text-xs space-y-2 mt-4
                                    {{ $isFpiApproved ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : (isset($firstPiece) && $firstPiece ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-red-50 border-red-300 text-red-900') }}">
                                    <div class="flex items-center justify-between">
                                        <span class="font-black uppercase tracking-wider text-[10px] text-gray-500">Gate Status</span>
                                        @if($isFpiApproved)
                                            <span class="px-2.5 py-0.5 text-[10px] font-black rounded-full bg-emerald-200 text-emerald-900 uppercase">QC Approved (OK)</span>
                                        @elseif(isset($firstPiece) && $firstPiece)
                                            <span class="px-2.5 py-0.5 text-[10px] font-black rounded-full bg-amber-200 text-amber-900 uppercase">Pending Signature</span>
                                        @else
                                            <span class="px-2.5 py-0.5 text-[10px] font-black rounded-full bg-red-200 text-red-900 uppercase">Inspection Required</span>
                                        @endif
                                    </div>

                                    <p class="font-medium text-xs leading-relaxed">
                                        @if($isFpiApproved)
                                            Inspected & approved by QC Inspector <span class="font-black">{{ $firstPiece->checked_by ?: 'QC Inspector' }}</span>.
                                        @elseif(isset($firstPiece) && $firstPiece)
                                            Inspection recorded, awaiting QC inspector signature sign-off.
                                        @else
                                            First Piece Inspection must be completed before standard production start.
                                        @endif
                                    </p>

                                    <div class="pt-1">
                                        @if($isFpiApproved)
                                            <a href="{{ route('first-piece-inspections.show', $firstPiece->id) }}" class="inline-block text-xs font-black text-emerald-800 hover:underline uppercase tracking-wider">
                                                View Inspection #{{ $firstPiece->id }}
                                            </a>
                                        @elseif(isset($firstPiece) && $firstPiece)
                                            <a href="{{ route('first-piece-inspections.show', $firstPiece->id) }}" class="inline-block px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg transition uppercase tracking-wider">
                                                Sign QC Approval #{{ $firstPiece->id }}
                                            </a>
                                        @else
                                            <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $workOrder->id, 'part_number' => $workOrder->part_number, 'part_name' => $workOrder->part_name, 'model' => $workOrder->model]) }}"
                                                class="inline-block w-full text-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl transition uppercase tracking-wider">
                                                Perform Inspection
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Line Gateway Shortcut Link --}}
                        <div class="pt-3 border-t border-gray-100">
                            <a href="{{ route('sp-sessions.line-gateway', ['lineSlug' => array_search($workOrder->unit_line, config('mes.sp_lines', [])) ?: 'line-a']) }}"
                               class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs transition uppercase tracking-wider">
                                Open Line Gateway
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Production Sessions Log Table Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-black text-gray-800 text-sm uppercase tracking-widest">Production Sessions Log</h3>
                        <p class="text-xs text-gray-500 font-medium">Recorded shift sessions for this Work Order</p>
                    </div>
                    <span class="text-xs font-bold bg-white px-3 py-1 rounded-full border border-gray-200 text-gray-600 shadow-sm">
                        {{ $workOrder->sessions->count() }} Session(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3">Session ID</th>
                                <th class="px-6 py-3">Operator</th>
                                <th class="px-6 py-3">Time Range</th>
                                <th class="px-6 py-3 text-right">Input WIP</th>
                                <th class="px-6 py-3 text-right">Good Qty</th>
                                <th class="px-6 py-3 text-right">Reject Qty</th>
                                <th class="px-6 py-3 text-right">Yield %</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($workOrder->sessions as $session)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4 font-black text-blue-700 whitespace-nowrap">#SESSION-{{ $session->id }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-800 whitespace-nowrap">{{ $session->operator?->name ?? 'Operator' }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-600 whitespace-nowrap">
                                        {{ $session->started_at ? $session->started_at->format('d M H:i') : '-' }} - {{ $session->finished_at ? $session->finished_at->format('H:i') : 'Now' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-gray-900 whitespace-nowrap">
                                        {{ number_format($session->productionEntries->sum('input_qty')) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-emerald-700 whitespace-nowrap">
                                        {{ number_format($session->total_good) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-red-700 whitespace-nowrap">
                                        {{ number_format($session->total_reject) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-black whitespace-nowrap">
                                        {{ $session->yield }}%
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @if($session->status === 'running')
                                            <span class="px-3 py-1 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-wider border border-emerald-200">Running</span>
                                        @elseif($session->approved_by)
                                            <span class="px-3 py-1 text-[10px] font-black rounded-full bg-blue-100 text-blue-800 uppercase tracking-wider border border-blue-200">Approved</span>
                                        @else
                                            <span class="px-3 py-1 text-[10px] font-black rounded-full bg-gray-100 text-gray-700 uppercase tracking-wider border border-gray-200">Completed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                        @if($session->status === 'running')
                                            <a href="{{ route('app.sp-sessions.show', $session->id) }}"
                                                class="inline-block px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-lg transition uppercase tracking-wider shadow-xs">
                                                Open Operator Screen
                                            </a>
                                        @else
                                            <a href="{{ route('app.sp-sessions.show', $session->id) }}"
                                                class="inline-block px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 transition uppercase tracking-wider">
                                                View Logs
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-400 font-medium">
                                        No production sessions recorded for this Work Order.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
