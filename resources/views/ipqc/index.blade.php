<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold">IPQC Inspections</h2>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('ipqc-inspections.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                        + New IPQC Inspection
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-sm mb-6" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('ipqc-inspections.index') }}" class="mb-6">
                <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-5">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
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
                        {{-- Part Number --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Part Number</label>
                            <input type="text" name="part_number" value="{{ request('part_number') }}" placeholder="Part Number"
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
                        {{-- Status --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        {{-- Search keyword --}}
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search..." 
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        {{-- Action buttons --}}
                        <div class="flex items-end gap-2">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 rounded text-sm transition">
                                Filter
                            </button>
                            <a href="{{ route('ipqc-inspections.index') }}" class="w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded text-sm transition">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Part Number</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Part Name</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Shift</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit/Line</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Judgement</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Records</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($inspections as $inspection)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">{{ \Carbon\Carbon::parse($inspection->date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $inspection->part_number }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $inspection->part_name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $inspection->customer }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">Shift {{ $inspection->shift }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $inspection->unit_line }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($inspection->status === 'ongoing')
                                            <span class="px-2 py-1 text-[10px] font-bold rounded bg-blue-100 text-blue-700 uppercase tracking-wide">Ongoing</span>
                                        @else
                                            <span class="px-2 py-1 text-[10px] font-bold rounded bg-green-100 text-green-700 uppercase tracking-wide">Completed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($inspection->overall_judgement === 'OK')
                                            <span class="px-2 py-1 text-[10px] font-bold rounded bg-green-100 text-green-700 uppercase tracking-wide">OK</span>
                                        @elseif($inspection->overall_judgement === 'NG')
                                            <span class="px-2 py-1 text-[10px] font-bold rounded bg-red-100 text-red-700 uppercase tracking-wide">NG</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">{{ $inspection->records_count ?? $inspection->records->count() }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('ipqc-inspections.show', $inspection->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                        <a href="{{ route('ipqc-inspections.edit', $inspection->id) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-sm">No IPQC inspections found.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $inspections->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
