<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                <h2 class="text-2xl font-bold">Second Process Daily Production Reports</h2>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('second-process.report-analytics', array_filter([
                        'preset' => $activePreset ?? request('preset'),
                        'date_from' => request('date_from'),
                        'date_to' => request('date_to'),
                        'unit_line' => request('unit_line'),
                        'shift' => request('shift'),
                        'process_prod' => request('process_prod'),
                        'industry' => request('industry'),
                        'customer' => request('customer'),
                        'status' => request('status'),
                    ], fn($v) => !is_null($v) && $v !== '')) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded shadow transition text-sm flex items-center gap-1.5">
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
            <form id="report-filter-form" method="GET" action="{{ route('second-process-reports.index') }}" class="mb-4"
                onsubmit="showLoadingState()"
                x-data="{ showCustomDates: {{ ($activePreset ?? '') === 'custom' ? 'true' : 'false' }} }">
                <input type="hidden" name="preset" id="filter-preset-input" value="{{ request('preset') }}">
                <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-4">
                    {{-- Quick Date Presets Row --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-gray-500 uppercase flex items-center gap-1 mr-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Quick Presets:
                            </span>
                            <button type="button" onclick="setDatePreset('today')"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border {{ ($activePreset ?? '') === 'today' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                Today
                            </button>
                            <button type="button" onclick="setDatePreset('yesterday')"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border {{ ($activePreset ?? '') === 'yesterday' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                Yesterday
                            </button>
                            <button type="button" onclick="setDatePreset('this_week')"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border {{ ($activePreset ?? '') === 'this_week' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                This Week
                            </button>
                            <button type="button" onclick="setDatePreset('this_month')"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border {{ ($activePreset ?? '') === 'this_month' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                This Month
                            </button>
                            <button type="button" onclick="setDatePreset('last_month')"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border {{ ($activePreset ?? '') === 'last_month' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                Last Month
                            </button>
                            {{-- Custom Date Range Quick Pill --}}
                            <button type="button"
                                @click="showCustomDates = !showCustomDates; if (showCustomDates) { document.getElementById('filter-preset-input').value = 'custom'; $nextTick(() => document.getElementById('filter-date-from')?.focus()); }"
                                :class="showCustomDates ? 'bg-blue-600 text-white border-blue-600 shadow-xs ring-2 ring-blue-300 font-bold' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900'"
                                class="px-2.5 py-1 text-xs rounded-md font-semibold transition border flex items-center gap-1 {{ ($activePreset ?? '') === 'custom' ? 'bg-blue-600 text-white border-blue-600 shadow-xs ring-2 ring-blue-300 font-bold' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 hover:text-gray-900' }}">
                                Custom Range
                            </button>

                            {{-- Direct inline date inputs: zero extra banners or verbose text --}}
                            <div x-show="showCustomDates" {{ ($activePreset ?? '') !== 'custom' ? 'x-cloak style=display:none;' : '' }}
                                class="inline-flex items-center gap-1.5 ml-1 transition-all">
                                <input type="date" name="date_from" id="filter-date-from"
                                    value="{{ ($activePreset ?? '') === 'custom' || (!request()->filled('preset') && request()->filled('date_from')) ? request('date_from') : '' }}"
                                    onchange="applyCustomDateIfComplete()"
                                    class="px-2 py-0.5 border-gray-300 bg-white rounded text-xs focus:ring-blue-500 focus:border-blue-500 font-medium">
                                <span class="text-xs text-gray-400 font-semibold">–</span>
                                <input type="date" name="date_to" id="filter-date-to"
                                    value="{{ ($activePreset ?? '') === 'custom' || (!request()->filled('preset') && request()->filled('date_to')) ? request('date_to') : '' }}"
                                    onchange="applyCustomDateIfComplete()"
                                    class="px-2 py-0.5 border-gray-300 bg-white rounded text-xs focus:ring-blue-500 focus:border-blue-500 font-medium">
                            </div>

                            @if(request('preset') !== 'all' && (request()->filled('date_from') || request()->filled('date_to') || request()->filled('preset') || ($activePreset ?? '') !== null))
                                <button type="button" onclick="clearDatePreset()"
                                    class="px-2.5 py-1 text-xs rounded-md font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 border border-dashed border-gray-300 transition ml-1"
                                    title="Clear date filters">
                                    ✕ All Dates
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Single Line Filters Row --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 xl:grid-cols-12 gap-2.5 items-end">
                        {{-- Unit / Line --}}
                        <div class="col-span-1 xl:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Unit / Line</label>
                            <select name="unit_line" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">All Lines</option>
                                @foreach(config('mes.sp_lines', []) as $slug => $lineName)
                                    <option value="{{ $lineName }}" {{ request('unit_line') == $lineName || request('unit_line') == $slug ? 'selected' : '' }}>
                                        {{ $lineName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Shift --}}
                        <div class="col-span-1 xl:col-span-1">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Shift</label>
                            <select name="shift" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">All Shifts</option>
                                <option value="1" {{ request('shift') == '1' ? 'selected' : '' }}>Shift 1</option>
                                <option value="2" {{ request('shift') == '2' ? 'selected' : '' }}>Shift 2</option>
                                <option value="3" {{ request('shift') == '3' ? 'selected' : '' }}>Shift 3</option>
                            </select>
                        </div>

                        {{-- Process --}}
                        <div class="col-span-1 xl:col-span-1">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Process</label>
                            <select name="process_prod" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">All Process</option>
                                @foreach(config('mes.sp_processes', ['Painting','Buffing','Amplas','Treatment','Packing','Rework','Repair','Assy']) as $proc)
                                    <option value="{{ $proc }}" {{ request('process_prod') == $proc ? 'selected' : '' }}>{{ $proc }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sektor Industri (BEFORE CUSTOMER) --}}
                        <div class="col-span-1 xl:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Sektor Industri</label>
                            <select name="industry" id="filter-industry" onchange="syncCustomerOptionsByIndustry(this.value)" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">Semua Sektor</option>
                                @foreach($industries as $key => $label)
                                    <option value="{{ $key }}" {{ ($selectedIndustry ?? request('industry')) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer --}}
                        <div class="col-span-2 xl:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Customer</label>
                            <select name="customer" id="filter-customer" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">All Customers</option>
                                @foreach($customers as $c)
                                    @php
                                        $cInd = $industryMap[$c] ?? 'GENERAL';
                                    @endphp
                                    <option value="{{ $c }}" data-industry="{{ $cInd }}" {{ request('customer') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-span-1 xl:col-span-1">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                <option value="">All Status</option>
                                @foreach(['draft','submitted','pqc_approved','leader_approved','acknowledged'] as $s)
                                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search keyword --}}
                        <div class="col-span-1 xl:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Model, part, cust..." 
                                class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5">
                        </div>

                        {{-- Action buttons --}}
                        <div class="col-span-2 xl:col-span-1 flex items-center gap-1.5">
                            <button type="submit" id="filter-submit-btn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-2.5 rounded text-sm transition text-center shadow-xs">
                                Filter
                            </button>
                            <a href="{{ route('second-process-reports.index') }}" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-2.5 rounded text-sm transition">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="relative bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                {{-- Top loading progress bar --}}
                <div id="filter-progress-bar" class="hidden absolute top-0 left-0 right-0 h-1 bg-blue-100 overflow-hidden z-30">
                    <div class="w-full h-full bg-blue-600 animate-pulse origin-left"></div>
                </div>

                {{-- Loading backdrop overlay --}}
                <div id="filter-loading-overlay" class="hidden absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-20 transition-opacity">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-white shadow-lg border border-gray-200 text-xs font-bold text-gray-700">
                        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memuat data laporan...</span>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2.5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-2.5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Line/Shift</th>
                                <th class="px-2.5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Process</th>
                                <th class="px-2.5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Part Name / Model</th>
                                <th class="px-2.5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-2 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Output</th>
                                <th class="px-2 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">OK</th>
                                <th class="px-2 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">NG %</th>
                                <th class="px-2 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Input</th>
                                <th class="px-2 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-2.5 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($reports as $report)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-2.5 py-2 whitespace-nowrap text-xs text-gray-700 font-medium">{{ \Carbon\Carbon::parse($report->date)->format('d M Y') }}</td>
                                    <td class="px-2.5 py-2 whitespace-nowrap text-xs">
                                        <div class="font-semibold text-gray-900">{{ $report->unit_line }}</div>
                                        <div class="text-gray-500 text-[11px]">Shift {{ $report->shift }}</div>
                                    </td>
                                    <td class="px-2.5 py-2 whitespace-nowrap text-xs font-medium text-gray-800">{{ $report->process_prod }}</td>
                                    <td class="px-2.5 py-2 text-xs">
                                        <div class="font-bold text-gray-900 truncate max-w-[150px] lg:max-w-[210px]" title="{{ $report->part_name }}">{{ $report->part_name ?: '-' }}</div>
                                        <div class="text-gray-500 text-[11px] truncate max-w-[150px] lg:max-w-[210px]" title="{{ $report->part_number }} {{ $report->model ? '('.$report->model.')' : '' }}">
                                            <span class="font-medium text-gray-700">{{ $report->part_number }}</span>
                                            @if($report->model)
                                                <span class="text-gray-400">({{ $report->model }})</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-2.5 py-2 text-xs max-w-[120px] lg:max-w-[150px]">
                                        <div class="truncate font-medium text-gray-800" title="{{ $report->customer }}">{{ $report->customer }}</div>
                                        @if(isset($industryMap[$report->customer]))
                                            <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">{{ $industryMap[$report->customer] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right font-semibold text-gray-900">{{ number_format($report->jumlah_output) }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right font-bold text-green-600">{{ number_format($report->jumlah_ok) }}</td>
                                    
                                    @php
                                        $ngClass = 'text-green-600';
                                        if ($report->ng_prosentase >= 3) $ngClass = 'text-red-600 font-bold';
                                        elseif ($report->ng_prosentase >= 1) $ngClass = 'text-yellow-600 font-semibold';
                                    @endphp
                                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right {{ $ngClass }}">
                                        {{ $report->ng_prosentase }}%
                                    </td>
                                    
                                    {{-- Sisa Input --}}
                                    <td class="px-2 py-2 whitespace-nowrap text-xs text-right">
                                        @php
                                            $sisa = $report->sisa_input;
                                        @endphp
                                        @if($sisa > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="{{ $report->sisa_input_remark ? 'Remark: ' . $report->sisa_input_remark : 'Sisa Material' }}">
                                                +{{ number_format($sisa) }}
                                            </span>
                                        @elseif($sisa < 0)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-red-50 text-red-700 border border-red-200" title="Defisit Output > Input">
                                                {{ number_format($sisa) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-medium">0</span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-2 py-2 whitespace-nowrap text-center">
                                        @switch($report->status)
                                            @case('draft')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase tracking-wide">Draft</span>
                                                @break
                                            @case('submitted')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-700 uppercase tracking-wide">Submitted</span>
                                                @break
                                            @case('pqc_approved')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-800 uppercase tracking-wide">PQC</span>
                                                @break
                                            @case('leader_approved')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-orange-100 text-orange-800 uppercase tracking-wide">Leader</span>
                                                @break
                                            @case('acknowledged')
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-green-100 text-green-800 uppercase tracking-wide">Done</span>
                                                @break
                                            @default
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-100 text-gray-700 uppercase tracking-wide">{{ $report->status }}</span>
                                        @endswitch
                                    </td>
                                    
                                    <td class="px-2.5 py-2 whitespace-nowrap text-right text-xs font-semibold">
                                        <a href="{{ route('second-process-reports.show', $report->id) }}" class="text-blue-600 hover:text-blue-900 mr-2">View</a>
                                        @can('update', $report)
                                            <a href="{{ route('second-process-reports.edit', $report->id) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
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
                                    <td colspan="11" class="px-6 py-8 text-center text-gray-500">
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

    <script>
        function showLoadingState() {
            const overlay = document.getElementById('filter-loading-overlay');
            const progressBar = document.getElementById('filter-progress-bar');
            if (overlay) overlay.classList.remove('hidden');
            if (progressBar) progressBar.classList.remove('hidden');
            const submitBtn = document.getElementById('filter-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('report-filter-form');
            if (form) {
                form.addEventListener('submit', function() {
                    showLoadingState();
                    const fromInput = document.getElementById('filter-date-from');
                    const toInput = document.getElementById('filter-date-to');
                    const presetInput = document.getElementById('filter-preset-input');
                    if (presetInput && (fromInput?.value || toInput?.value)) {
                        if (!['today', 'yesterday', 'this_week', 'this_month', 'last_month'].includes(presetInput.value)) {
                            presetInput.value = 'custom';
                        }
                    }
                });
            }

            const indSelect = document.getElementById('filter-industry');
            if (indSelect && indSelect.value) {
                syncCustomerOptionsByIndustry(indSelect.value);
            }
        });

        function syncCustomerOptionsByIndustry(industry) {
            const custSelect = document.getElementById('filter-customer');
            if (!custSelect) return;
            const options = custSelect.querySelectorAll('option');
            let hasCurrentMatch = false;
            const targetInd = industry ? industry.trim().toUpperCase() : '';

            options.forEach(opt => {
                if (!opt.value) {
                    opt.hidden = false;
                    return;
                }
                const optIndustry = (opt.getAttribute('data-industry') || '').trim().toUpperCase();
                const match = !targetInd || optIndustry === targetInd;
                opt.hidden = !match;
                if (opt.selected && match) {
                    hasCurrentMatch = true;
                }
            });

            if (!hasCurrentMatch && targetInd && custSelect.value !== '') {
                custSelect.value = '';
            }
        }

        let isSubmittingDate = false;
        function applyCustomDateIfComplete() {
            if (isSubmittingDate) return;

            const fromInput = document.getElementById('filter-date-from');
            const toInput = document.getElementById('filter-date-to');
            const presetInput = document.getElementById('filter-preset-input');
            const form = document.getElementById('report-filter-form');

            if (presetInput) {
                presetInput.value = 'custom';
            }

            const fromVal = fromInput ? fromInput.value : '';
            const toVal = toInput ? toInput.value : '';

            // If only 'from' is filled and 'to' is empty, smoothly move focus to 'to'
            if (fromVal && !toVal) {
                if (toInput) {
                    toInput.focus();
                    try {
                        if (typeof toInput.showPicker === 'function') {
                            toInput.showPicker();
                        }
                    } catch (e) {}
                }
                return;
            }

            // If only 'to' is filled and 'from' is empty, smoothly move focus to 'from'
            if (!fromVal && toVal) {
                if (fromInput) {
                    fromInput.focus();
                    try {
                        if (typeof fromInput.showPicker === 'function') {
                            fromInput.showPicker();
                        }
                    } catch (e) {}
                }
                return;
            }

            // When both are filled
            if (fromVal && toVal && form) {
                // If end date is earlier than start date, reset end date and focus it
                if (fromVal > toVal) {
                    toInput.value = '';
                    toInput.focus();
                    try {
                        if (typeof toInput.showPicker === 'function') {
                            toInput.showPicker();
                        }
                    } catch (e) {}
                    return;
                }

                isSubmittingDate = true;
                showLoadingState();
                form.submit();
            }
        }

        function setDatePreset(preset) {
            const presets = {
                today: {
                    from: '{{ $today }}',
                    to: '{{ $today }}'
                },
                yesterday: {
                    from: '{{ $yesterday }}',
                    to: '{{ $yesterday }}'
                },
                this_week: {
                    from: '{{ $thisWeekStart }}',
                    to: '{{ $today }}'
                },
                this_month: {
                    from: '{{ $thisMonthStart }}',
                    to: '{{ $today }}'
                },
                last_month: {
                    from: '{{ $lastMonthStart }}',
                    to: '{{ $lastMonthEnd }}'
                }
            };

            if (presets[preset]) {
                const form = document.getElementById('report-filter-form');
                if (form) {
                    showLoadingState();
                    const fromInput = form.querySelector('input[name="date_from"]');
                    const toInput = form.querySelector('input[name="date_to"]');
                    const presetInput = document.getElementById('filter-preset-input');
                    
                    if (fromInput) fromInput.value = presets[preset].from;
                    if (toInput) toInput.value = presets[preset].to;
                    if (presetInput) presetInput.value = preset;
                    form.submit();
                }
            }
        }

        function clearDatePreset() {
            const form = document.getElementById('report-filter-form');
            if (form) {
                showLoadingState();
                const fromInput = form.querySelector('input[name="date_from"]');
                const toInput = form.querySelector('input[name="date_to"]');
                const presetInput = document.getElementById('filter-preset-input');
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
                if (presetInput) presetInput.value = 'all';
                form.submit();
            }
        }
    </script>
</x-app-layout>
