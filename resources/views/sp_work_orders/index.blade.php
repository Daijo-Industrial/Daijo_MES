<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-xl text-gray-800 uppercase tracking-wide">
                    {{ __('Second Process Work Orders') }}
                </h2>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Manage, release, and track production Work Orders & QC Gate status</p>
            </div>
            @can('manage-sp-work-orders')
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('second-process.dashboard') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 shadow-sm transition uppercase tracking-wider">
                        Floor Overview Dashboard
                    </a>
                    <a href="{{ route('sp-work-orders.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                        New Work Order
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto space-y-6">

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-300 text-red-900 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Compact 1-Row Inline Filter Bar --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('sp-work-orders.index') }}" class="flex flex-wrap items-center gap-3 text-xs">
                    {{-- Search Input --}}
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search WO#, Part Number, Name, Customer..."
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold text-gray-800 px-3.5 py-2">
                    </div>

                    {{-- Line Select --}}
                    <div class="w-auto min-w-[130px]">
                        <select name="unit_line" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold text-gray-800 px-3.5 py-2">
                            <option value="">All Lines</option>
                            @foreach($lines as $l)
                                <option value="{{ $l }}" {{ request('unit_line') == $l ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Select --}}
                    <div class="w-auto min-w-[130px]">
                        <select name="status" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold text-gray-800 px-3.5 py-2">
                            <option value="">All Status</option>
                            <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Planned</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="w-auto">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" title="From Date"
                            class="rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold text-gray-800 px-3 py-2">
                    </div>

                    {{-- Date To --}}
                    <div class="w-auto">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" title="To Date"
                            class="rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs font-semibold text-gray-800 px-3 py-2">
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1.5 ml-auto">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-black text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'unit_line', 'status', 'date_from', 'date_to']))
                            <a href="{{ route('sp-work-orders.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 transition uppercase tracking-wider">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table Display Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Work Orders</h3>
                        <p class="text-xs text-gray-500 font-medium">Production planning and real-time execution status</p>
                    </div>
                    <span class="text-xs font-bold bg-white px-3 py-1 rounded-full border border-gray-200 text-gray-600 shadow-sm">
                        {{ $workOrders->total() }} Work Order(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="px-4 py-3">WO Number</th>
                                <th class="px-4 py-3">Planned Date</th>
                                <th class="px-4 py-3">Line</th>
                                <th class="px-4 py-3">Part Information</th>
                                <th class="px-4 py-3 text-right">Target Qty</th>
                                <th class="px-4 py-3 text-center">QC Gate</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($workOrders as $wo)
                                @php
                                    $fpList = $firstPieceMap->get($wo->part_number) ?? collect();
                                    $fp = $fpList->first();
                                    $fpApproved = $fp && $fp->isApproved();
                                    $runningSession = $wo->sessions->where('status', 'running')->first();
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-4 py-3 font-black text-blue-700 whitespace-nowrap">
                                        <a href="{{ route('sp-work-orders.show', $wo->id) }}" class="hover:underline">
                                            {{ $wo->wo_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-800 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($wo->planned_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-800 whitespace-nowrap">
                                        <div>{{ $wo->unit_line }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 leading-tight">{{ $wo->part_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $wo->part_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900 whitespace-nowrap">
                                        {{ number_format($wo->target_qty) }} Pcs
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($wo->status === 'draft')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                                Unreleased
                                            </span>
                                        @elseif($fpApproved)
                                            <a href="{{ route('first-piece-inspections.show', $fp->id) }}" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 uppercase tracking-wider border border-emerald-200 hover:bg-emerald-200 transition">
                                                QC Approved
                                            </a>
                                        @elseif($fp)
                                            <a href="{{ route('first-piece-inspections.show', $fp->id) }}" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 uppercase tracking-wider border border-amber-200 hover:bg-amber-200 transition">
                                                Pending Sign-off
                                            </a>
                                        @else
                                            <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $wo->id, 'part_number' => $wo->part_number, 'part_name' => $wo->part_name, 'model' => $wo->model]) }}" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-800 uppercase tracking-wider border border-red-200 hover:bg-red-200 transition">
                                                Gate Required
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border
                                            {{ $wo->status === 'draft' ? 'bg-slate-100 text-slate-700 border-slate-200' : ($wo->status === 'in_progress' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : ($wo->status === 'planned' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-gray-100 text-gray-700 border-gray-200')) }}">
                                            {{ str_replace('_', ' ', $wo->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($wo->status === 'draft')
                                                <a href="{{ route('sp-work-orders.edit', $wo->id) }}"
                                                    class="px-2.5 py-1 bg-slate-700 hover:bg-slate-800 text-white font-bold text-[11px] rounded-lg transition uppercase tracking-wider">
                                                    Edit
                                                </a>
                                                <form action="{{ route('sp-work-orders.release', $wo->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] rounded-lg transition uppercase tracking-wider">
                                                        Release
                                                    </button>
                                                </form>
                                            @elseif($runningSession)
                                                <a href="{{ route('app.sp-sessions.show', $runningSession->id) }}"
                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition uppercase tracking-wider">
                                                    Open Screen
                                                </a>
                                            @elseif($wo->status === 'planned')
                                                @if($fpApproved)
                                                    <form action="{{ route('sp-sessions.start', $wo->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] rounded-lg transition uppercase tracking-wider">
                                                            Start
                                                        </button>
                                                    </form>
                                                @else
                                                    @can('execute-qc-inspections')
                                                        <a href="{{ route('first-piece-inspections.create', ['work_order_id' => $wo->id, 'part_number' => $wo->part_number, 'part_name' => $wo->part_name, 'model' => $wo->model]) }}"
                                                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] rounded-lg transition uppercase tracking-wider">
                                                            QC Inspection
                                                        </a>
                                                    @endcan
                                                @endif

                                                @if($wo->sessions->count() === 0)
                                                    <form action="{{ route('sp-work-orders.revert-to-draft', $wo->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-2 py-1 bg-gray-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg border border-slate-300 transition uppercase tracking-wider" title="Revert to draft mode">
                                                            Revert
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <a href="{{ route('sp-work-orders.show', $wo->id) }}"
                                                    class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] rounded-lg border border-gray-300 transition uppercase tracking-wider">
                                                    View
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-400 font-medium">
                                        No Work Orders found matching criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($workOrders->hasPages())
                    <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                        {{ $workOrders->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
