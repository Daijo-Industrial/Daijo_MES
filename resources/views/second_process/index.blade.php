<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                <h2 class="text-2xl font-bold">Second Process Daily Production Reports</h2>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('second-process.report-analytics') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded shadow transition text-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Analytics Dashboard
                    </a>
                    <a href="{{ route('second-process-reports.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                        + New Report
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-sm mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-white border border-gray-200 rounded-lg p-3.5 shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase">Total Reports</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ number_format($summary->total_reports) }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-3.5 shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase">Total Output</div>
                    <div class="text-2xl font-black text-blue-700 mt-1">{{ number_format($summary->total_output) }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-3.5 shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase">Total OK</div>
                    <div class="text-2xl font-black text-green-700 mt-1">{{ number_format($summary->total_ok) }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-3.5 shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase">Total NG</div>
                    <div class="text-2xl font-black text-red-700 mt-1 flex items-end gap-2">
                        {{ number_format($summary->total_ng) }}
                        @if($summary->total_output > 0)
                            <span class="text-sm text-red-500 font-semibold mb-1">
                                ({{ round(($summary->total_ng / $summary->total_output) * 100, 2) }}%)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('second-process-reports.index') }}" class="mb-4">
                <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                        {{-- Date From --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">From Date</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        {{-- Date To --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">To Date</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        {{-- Unit / Line --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Unit/Line</label>
                            <input type="text" name="unit_line" value="{{ request('unit_line') }}" placeholder="Unit/Line"
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        {{-- Shift --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Shift</label>
                            <select name="shift" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Shifts</option>
                                <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                                <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                                <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                            </select>
                        </div>
                        {{-- Process --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Process</label>
                            <select name="process_prod" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Process</option>
                                @foreach(config('mes.sp_processes', ['Painting','Buffing','Amplas','Treatment','Packing','Rework','Repair','Assy']) as $proc)
                                    <option value="{{ $proc }}" {{ request('process_prod') == $proc ? 'selected' : '' }}>{{ $proc }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Status --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                @foreach(['draft','submitted','pqc_approved','leader_approved','acknowledged'] as $s)
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Search keyword --}}
                        <div class="col-span-2 lg:col-span-1">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Model, part, cust..." 
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        {{-- Action buttons --}}
                        <div class="col-span-2 lg:col-span-1 flex items-end gap-2">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 rounded text-sm transition">
                                Filter
                            </button>
                            <a href="{{ route('second-process-reports.index') }}" class="w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded text-sm transition">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto overflow-y-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Line/Shift</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Process</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Model / Part</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-3 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Output</th>
                                <th class="px-3 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">OK</th>
                                <th class="px-3 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">NG %</th>
                                <th class="px-3 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($reports as $report)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm">{{ \Carbon\Carbon::parse($report->date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm">
                                        <div class="font-semibold">{{ $report->unit_line }}</div>
                                        <div class="text-gray-500 text-xs">Shift {{ $report->shift }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm">{{ $report->process_prod }}</td>
                                    <td class="px-3 py-2.5 text-sm max-w-[140px] lg:max-w-[170px]">
                                        <div class="font-semibold truncate" title="{{ $report->model }}">{{ $report->model }}</div>
                                        <div class="text-gray-500 text-xs truncate" title="{{ $report->part_number }}">{{ $report->part_number }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm max-w-[150px] lg:max-w-[180px]">
                                        <div class="truncate font-medium text-gray-800" title="{{ $report->customer }}">{{ $report->customer }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm text-right font-medium">{{ number_format($report->jumlah_output) }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm text-right font-medium text-green-600">{{ number_format($report->jumlah_ok) }}</td>
                                    
                                    @php
                                        $ngClass = 'text-green-600';
                                        if ($report->ng_prosentase >= 3) $ngClass = 'text-red-600 font-bold';
                                        elseif ($report->ng_prosentase >= 1) $ngClass = 'text-yellow-600 font-semibold';
                                    @endphp
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm text-right {{ $ngClass }}">
                                        {{ $report->ng_prosentase }}%
                                    </td>
                                    
                                    <td class="px-3 py-2.5 whitespace-nowrap text-center">
                                        @switch($report->status)
                                            @case('draft')
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase tracking-wide">Draft</span>
                                                @break
                                            @case('submitted')
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-blue-100 text-blue-700 uppercase tracking-wide">Submitted</span>
                                                @break
                                            @case('pqc_approved')
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-yellow-100 text-yellow-800 uppercase tracking-wide">PQC</span>
                                                @break
                                            @case('leader_approved')
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-orange-100 text-orange-800 uppercase tracking-wide">Leader</span>
                                                @break
                                            @case('acknowledged')
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-green-100 text-green-800 uppercase tracking-wide">Done</span>
                                                @break
                                            @default
                                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase tracking-wide">{{ $report->status }}</span>
                                        @endswitch
                                    </td>
                                    
                                    <td class="px-3 py-2.5 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('second-process-reports.show', $report->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                        @can('update', $report)
                                            <a href="{{ route('second-process-reports.edit', $report->id) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                        @endcan
                                        @can('delete', $report)
                                            <button onclick="document.getElementById('delete-dialog-{{ $report->id }}').showModal()" class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>

                                            {{-- Delete Dialog --}}
                                            <dialog id="delete-dialog-{{ $report->id }}" class="rounded-lg p-6 shadow-2xl border border-gray-300 w-full max-w-sm backdrop:bg-gray-900/50">
                                                <form action="{{ route('second-process-reports.destroy', $report->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <h3 class="text-sm font-bold text-gray-900 mb-2 text-left">Confirm Delete</h3>
                                                    <p class="text-xs text-gray-500 mb-4 text-left whitespace-normal">Are you sure you want to delete this report? This action cannot be undone.</p>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" onclick="document.getElementById('delete-dialog-{{ $report->id }}').close()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-xs font-bold transition">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-xs font-bold shadow-sm transition">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </form>
                                            </dialog>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-sm">No reports found matching your criteria.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
