<!-- Main Form Card -->
<div class="bg-white shadow-xl rounded-lg overflow-hidden mb-6 border border-gray-200">

    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-6">
        <div class="flex flex-wrap justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">PT. DAIJO INDUSTRIAL</h1>
                <p class="text-sm font-semibold opacity-90 mt-1">Second Process Departement</p>
            </div>
            <div
                class="text-right text-xs md:text-sm space-y-1 bg-white/10 p-3 rounded-lg backdrop-blur-sm mt-4 md:mt-0">
                <div><span class="font-bold">No. Dokumen:</span> DI-F-P/PR/07/SP-001</div>
                <div><span class="font-bold">Tgl. Dikeluarkan:</span> 04 Januari 2023</div>
                <div><span class="font-bold">Mulai berlaku:</span> 08 Desember 2025</div>
                <div><span class="font-bold">Revisi / Halaman:</span> 2 / 1 of 1</div>
            </div>
        </div>
        <div class="text-center mt-6">
            <h2 class="text-2xl font-bold uppercase tracking-wider">
                {{ $report->exists ? 'Edit Laporan Produksi Harian' : 'Laporan Produksi Harian' }}</h2>
        </div>
    </div>

@php
    $tabErrorKeys = [
        'setup' => ['date', 'unit_line', 'shift', 'process_prod', 'status', 'output_destination', 'part_number', 'part_name', 'model', 'customer', 'manpower'],
        'materials' => ['materials'],
        'production' => ['target_per_hour', 'jml_input_wip', 'repairan', 'hourly', 'ngs', 'ng_remarks', 'sisa_input', 'sisa_input_remark'],
        'handover' => ['next_production_schedule', 'absent_employees', 'production_notes', 'troubles', 'created_by_name', 'leader_name', 'pqc_name', 'acknowledged_by_name'],
    ];

    $tabErrorCounts = [
        'setup' => 0,
        'materials' => 0,
        'production' => 0,
        'handover' => 0,
    ];

    $errorsByTab = [
        'setup' => [],
        'materials' => [],
        'production' => [],
        'handover' => [],
    ];

    if ($errors->any()) {
        foreach ($errors->messages() as $field => $messages) {
            $assignedTab = 'setup';
            foreach ($tabErrorKeys as $tab => $keys) {
                foreach ($keys as $key) {
                    if ($field === $key || \Illuminate\Support\Str::startsWith($field, $key . '.') || \Illuminate\Support\Str::startsWith($field, $key . '[')) {
                        $assignedTab = $tab;
                        break 2;
                    }
                }
            }
            $tabErrorCounts[$assignedTab] += count($messages);
            foreach ($messages as $msg) {
                $errorsByTab[$assignedTab][] = [
                    'field' => $field,
                    'message' => $msg,
                ];
            }
        }
    }

    $firstErrorTab = null;
    if ($errors->any()) {
        foreach (['setup', 'materials', 'production', 'handover'] as $tName) {
            if ($tabErrorCounts[$tName] > 0) {
                $firstErrorTab = $tName;
                break;
            }
        }
    }
@endphp

    <!-- Option 2: Sticky Tabbed Navigation -->
    <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
        <nav class="-mb-px flex flex-wrap justify-between sm:justify-start gap-2 md:gap-6" aria-label="Tabs"
            id="form-tabs-navigation">
            <button type="button" data-tab="setup"
                class="tab-btn active border-blue-600 text-blue-600 whitespace-nowrap py-3 px-3 border-b-2 font-bold text-sm flex items-center transition-all {{ $tabErrorCounts['setup'] > 0 ? 'text-red-600 !border-red-500' : '' }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                1. Setup & Manpower
                @if ($tabErrorCounts['setup'] > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-extrabold rounded-full bg-red-600 text-white shadow-sm animate-pulse" title="{{ $tabErrorCounts['setup'] }} error(s)">
                        {{ $tabErrorCounts['setup'] }}
                    </span>
                @endif
            </button>
            <button type="button" data-tab="materials"
                class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-3 border-b-2 font-bold text-sm flex items-center transition-all {{ $tabErrorCounts['materials'] > 0 ? 'text-red-600 !border-red-500' : '' }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                    </path>
                </svg>
                2. Materials
                @if ($tabErrorCounts['materials'] > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-extrabold rounded-full bg-red-600 text-white shadow-sm animate-pulse" title="{{ $tabErrorCounts['materials'] }} error(s)">
                        {{ $tabErrorCounts['materials'] }}
                    </span>
                @endif
            </button>
            <button type="button" data-tab="production"
                class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-3 border-b-2 font-bold text-sm flex items-center transition-all {{ $tabErrorCounts['production'] > 0 ? 'text-red-600 !border-red-500' : '' }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                3. Production Logs & NG
                @if ($tabErrorCounts['production'] > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-extrabold rounded-full bg-red-600 text-white shadow-sm animate-pulse" title="{{ $tabErrorCounts['production'] }} error(s)">
                        {{ $tabErrorCounts['production'] }}
                    </span>
                @endif
            </button>
            <button type="button" data-tab="handover"
                class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-3 border-b-2 font-bold text-sm flex items-center transition-all {{ $tabErrorCounts['handover'] > 0 ? 'text-red-600 !border-red-500' : '' }}">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                4. Handover & Signs
                @if ($tabErrorCounts['handover'] > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-extrabold rounded-full bg-red-600 text-white shadow-sm animate-pulse" title="{{ $tabErrorCounts['handover'] }} error(s)">
                        {{ $tabErrorCounts['handover'] }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Form Content Body -->
    <div class="p-6">

        <!-- Top Error Summary Banner -->
        @if ($errors->any())
            <div id="form-error-summary" class="mb-6 bg-red-50 border-l-4 border-red-600 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-red-900">
                                Terdapat {{ $errors->count() }} kesalahan input yang perlu diperbaiki:
                            </h3>
                            <span class="text-[11px] text-red-700 font-semibold bg-red-100 px-2 py-0.5 rounded">
                                Klik tautan di bawah untuk langsung menuju kolom
                            </span>
                        </div>
                        <div class="mt-3 space-y-3 text-xs">
                            @php
                                $tabLabels = [
                                    'setup' => 'Tab 1: Setup & Manpower',
                                    'materials' => 'Tab 2: Materials',
                                    'production' => 'Tab 3: Production Logs & NG',
                                    'handover' => 'Tab 4: Handover & Signs',
                                ];
                            @endphp
                            @foreach ($errorsByTab as $tabKey => $tabErrs)
                                @if (count($tabErrs) > 0)
                                    <div class="bg-white/90 rounded border border-red-200 p-2.5 shadow-xs">
                                        <div class="flex items-center justify-between font-bold text-red-900 border-b border-red-100 pb-1.5 mb-1.5">
                                            <span class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                                {{ $tabLabels[$tabKey] }} ({{ count($tabErrs) }} error)
                                            </span>
                                            <button type="button" onclick="switchTabAndFocus('{{ $tabKey }}')"
                                                class="text-[11px] bg-red-100 hover:bg-red-200 text-red-800 font-bold px-2 py-0.5 rounded transition inline-flex items-center gap-1">
                                                Buka Tab &rarr;
                                            </button>
                                        </div>
                                        <ul class="space-y-1 pl-1">
                                            @foreach ($tabErrs as $errItem)
                                                <li class="flex items-start gap-1.5 text-red-800">
                                                    <span class="text-red-500 font-bold">&bull;</span>
                                                    <button type="button" onclick="switchTabAndFocus('{{ $tabKey }}', '{{ $errItem['field'] }}')"
                                                        class="text-left underline hover:text-red-950 font-medium cursor-pointer focus:outline-none">
                                                        {{ $errItem['message'] }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Validation Warnings Banner (Chronological Validation) -->
        <div id="totals-validation-message" class="mb-6"></div>

        <!-- TAB 1: SETUP & MANPOWER -->
        <div id="tab-content-setup" class="tab-pane space-y-8">

            <!-- Header Logistics Section -->
            <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Shift Logistics & Setup
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ old('date', $report->date ?? date('Y-m-d')) }}"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('date') border-red-500 ring-1 ring-red-500 @enderror"
                            required>
                        @error('date')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Unit /
                            Line</label>
                        <select name="unit_line"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('unit_line') border-red-500 ring-1 ring-red-500 @enderror"
                            required>
                            <option value="">-- Select Unit / Line --</option>
                            @php
                                $unitLineOptions = array_values(config('mes.sp_lines'));
                                $currentUnitLine = old('unit_line', $report->unit_line);
                            @endphp
                            @foreach ($unitLineOptions as $opt)
                                <option value="{{ $opt }}" {{ $currentUnitLine == $opt ? 'selected' : '' }}>
                                    {{ $opt }}</option>
                            @endforeach
                            @if ($currentUnitLine && !in_array($currentUnitLine, $unitLineOptions))
                                <option value="{{ $currentUnitLine }}" selected>{{ $currentUnitLine }}</option>
                            @endif
                        </select>
                        @error('unit_line')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Shift</label>
                        <select name="shift"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('shift') border-red-500 ring-1 ring-red-500 @enderror"
                            required>
                            <option value="1" {{ old('shift', $report->shift) == '1' ? 'selected' : '' }}>Shift 1
                            </option>
                            <option value="2" {{ old('shift', $report->shift) == '2' ? 'selected' : '' }}>Shift 2
                            </option>
                            <option value="3" {{ old('shift', $report->shift) == '3' ? 'selected' : '' }}>Shift 3
                            </option>
                        </select>
                        @error('shift')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Proses
                            Prod</label>
                        <select name="process_prod" id="process_prod"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('process_prod') border-red-500 ring-1 ring-red-500 @enderror"
                            required>
                            @php
                                $spProcesses = config('mes.sp_processes');
                                $currentProcess = old('process_prod', $report->process_prod ?? 'Painting');
                            @endphp
                            @foreach ($spProcesses as $proc)
                                <option value="{{ $proc }}" {{ $currentProcess == $proc ? 'selected' : '' }}>
                                    {{ $proc }}</option>
                            @endforeach
                            @if ($currentProcess && !in_array($currentProcess, $spProcesses))
                                <option value="{{ $currentProcess }}" selected>{{ $currentProcess }}</option>
                            @endif
                        </select>
                        @error('process_prod')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <input type="hidden" name="status" id="status-field"
                        value="{{ old('status', $report->status) }}">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tujuan
                            Output</label>
                        <select name="output_destination"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('output_destination') border-red-500 ring-1 ring-red-500 @enderror">
                            <option value=""
                                {{ empty(old('output_destination', $report->output_destination)) ? 'selected' : '' }}>
                                -- Select Next Step --</option>
                            @php
                                $spDestinations = config('mes.sp_output_destinations');
                                $currentDest = old('output_destination', $report->output_destination);
                            @endphp
                            @foreach ($spDestinations as $destKey => $destLabel)
                                <option value="{{ $destKey }}" {{ $currentDest == $destKey ? 'selected' : '' }}>
                                    {{ $destLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('output_destination')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Part
                            Number</label>
                        <div class="relative">
                            <input type="text" name="part_number" id="part_number"
                                value="{{ old('part_number', $report->part_number) }}"
                                placeholder="Search Part Number..."
                                class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('part_number') border-red-500 ring-1 ring-red-500 @enderror"
                                required autocomplete="off">
                            <div id="part-number-dropdown"
                                class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded shadow-lg z-50 hidden">
                            </div>
                        </div>
                        @error('part_number')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Part
                            Name</label>
                        <input type="text" name="part_name" value="{{ old('part_name', $report->part_name) }}"
                            placeholder="Auto-filled from Part Number"
                            class="w-full rounded border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 text-sm"
                            >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Model</label>
                        <input type="text" name="model" value="{{ old('model', $report->model) }}"
                            placeholder="Model"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                            >
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Customer</label>
                            <span class="text-[10px] text-gray-400 font-medium">Nama Resmi / N/A</span>
                        </div>
                        <div class="relative">
                            <input type="text" name="customer" id="customer"
                                value="{{ old('customer', $report->customer) }}" placeholder="Search Customer atau N/A"
                                class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm @error('customer') border-red-500 ring-1 ring-red-500 @enderror"
                                autocomplete="off">
                            <div id="customer-dropdown"
                                class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded shadow-lg z-50 hidden">
                            </div>
                        </div>
                        @error('customer')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Manpower Section -->
            <div class="space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        Manpower (MP)
                    </h3>
                    <button type="button" onclick="addManpowerRow()"
                        class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-1.5 px-4 rounded text-xs transition shadow-sm">
                        + Add Manpower
                    </button>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="manpower-table">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 w-12">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 w-1/3">Role</th>
                                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-600">Name</th>
                                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 w-16">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="manpower-tbody">
                                @forelse($report->manpowers->sortBy('no') as $index => $mp)
                                    <tr class="manpower-row">
                                        <td class="px-4 py-2 text-center font-bold text-gray-500 mp-no">
                                            {{ $loop->iteration }}</td>
                                        <td class="px-4 py-2">
                                            <input type="hidden" name="manpower[{{ $index }}][no]"
                                                class="mp-no-input" value="{{ $loop->iteration }}">
                                            @php
                                                $spRoles = config('mes.sp_manpower_roles');
                                                $isCustom = !array_key_exists($mp->role, $spRoles);
                                            @endphp
                                            <select
                                                class="w-full text-xs rounded border-gray-300 py-1 mb-1 role-select"
                                                onchange="toggleCustomRole(this, {{ $index }})">
                                                @foreach($spRoles as $roleKey => $roleLabel)
                                                    <option value="{{ $roleKey }}" {{ $mp->role == $roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                                @endforeach
                                                <option value="__custom__" {{ $isCustom ? 'selected' : '' }}>Other (custom)...</option>
                                            </select>
                                            <input type="text" name="manpower[{{ $index }}][role]"
                                                value="{{ $mp->role }}"
                                                class="w-full text-xs rounded border-gray-300 py-1 role-input {{ $isCustom ? '' : 'hidden' }}"
                                                placeholder="Type custom role..." {{ $isCustom ? '' : 'readonly' }}>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="text" name="manpower[{{ $index }}][name]"
                                                value="{{ $mp->name }}" placeholder="Operator Name"
                                                class="w-full text-xs rounded border-gray-300 py-1">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" onclick="removeManpowerRow(this)"
                                                class="text-red-500 hover:text-red-700 p-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- Initial empty row if no data -->
                                    <tr class="manpower-row">
                                        <td class="px-4 py-2 text-center font-bold text-gray-500 mp-no">1</td>
                                        <td class="px-4 py-2">
                                            <input type="hidden" name="manpower[0][no]" class="mp-no-input"
                                                value="1">
                                            <select
                                                class="w-full text-xs rounded border-gray-300 py-1 mb-1 role-select"
                                                onchange="toggleCustomRole(this, 0)">
                                                @foreach(config('mes.sp_manpower_roles') as $roleKey => $roleLabel)
                                                    <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                                                @endforeach
                                                <option value="__custom__">Other (custom)...</option>
                                            </select>
                                            <input type="text" name="manpower[0][role]" value="loading"
                                                class="w-full text-xs rounded border-gray-300 py-1 role-input hidden"
                                                placeholder="Type custom role..." readonly>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="text" name="manpower[0][name]"
                                                placeholder="Operator Name"
                                                class="w-full text-xs rounded border-gray-300 py-1">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" onclick="removeManpowerRow(this)"
                                                class="text-red-500 hover:text-red-700 p-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Navigation Tab 1 -->
            <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                <button type="button" onclick="switchTab('materials')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                    Next: Materials &rarr;
                </button>
            </div>
        </div> <!-- END TAB 1 -->

        <!-- TAB 2: MATERIALS -->
        <div id="tab-content-materials" class="tab-pane hidden space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @php
                    $materialGlobalIndex = 0;
                    $paintMaterials = $report->materials ?: collect();
                    if ($paintMaterials->isEmpty() && !$report->exists) {
                        $defaultPaints = config('mes.sp_default_paint_materials');
                        foreach ($defaultPaints as $pName) {
                            $paintMaterials->push((object)[
                                'item_name' => $pName,
                                'lot_number' => '',
                                'visco' => '',
                                'mixing_ratio' => '',
                                'qty' => '',
                            ]);
                        }
                    }
                @endphp

                @php
                    $isPaintApplicable = in_array(old('process_prod', $report->process_prod ?? 'Painting'), ['Painting', 'Repair']);
                @endphp

                <!-- Item Paint Table -->
                <div id="paint-materials-card" class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm flex flex-col justify-between transition-all duration-200 {{ !$isPaintApplicable ? 'opacity-60 bg-gray-50' : '' }}">
                    <div>
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-gray-700">Item Paint (Viscosity & Mixing Ratio)</h4>
                                <span id="paint-process-badge" class="{{ $isPaintApplicable ? 'hidden' : '' }} text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                                    Hanya untuk Proses Painting & Repair
                                </span>
                            </div>
                            <span class="text-[10px] text-gray-500">Filled during prep stage</span>
                        </div>
                        <div id="paint-disabled-notice" class="{{ $isPaintApplicable ? 'hidden' : '' }} p-2.5 bg-amber-50 border-b border-amber-200 text-amber-800 text-xs flex items-center justify-between">
                            <span>Item Paint dinonaktifkan karena Proses Prod bukan <strong>Painting</strong> atau <strong>Repair</strong>.</span>
                            <button type="button" onclick="switchTab('setup')" class="text-blue-700 underline font-bold ml-2">Ubah di Tab 1 &rarr;</button>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-gray-600">Item Paint</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-1/5">Lot Number</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-16">Visco</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-20">Mixing Ratio</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-20">Qty</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-20">UOM</th>
                                    <th class="px-1 py-2 text-center font-bold text-gray-600 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="paint-materials-tbody" class="divide-y divide-gray-200">
                                @foreach ($paintMaterials as $mat)
                                    @php $currIdx = $materialGlobalIndex++; @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="hidden" name="materials[{{ $currIdx }}][type]" value="paint" {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                            <input type="text" name="materials[{{ $currIdx }}][item_name]"
                                                value="{{ old('materials.' . $currIdx . '.item_name', $mat->item_name ?? '') }}"
                                                placeholder="Paint Item Name"
                                                class="w-full text-xs rounded border-gray-300 py-1 font-semibold {{ $errors->has('materials.' . $currIdx . '.item_name') ? 'border-red-500 ring-1 ring-red-500' : '' }} {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                            @if ($errors->has('materials.' . $currIdx . '.item_name'))
                                                <p class="text-[10px] text-red-600 font-bold mt-0.5">{{ $errors->first('materials.' . $currIdx . '.item_name') }}</p>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][lot_number]"
                                                value="{{ old('materials.' . $currIdx . '.lot_number', $mat->lot_number ?? '') }}"
                                                placeholder="Lot"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][visco]"
                                                value="{{ old('materials.' . $currIdx . '.visco', $mat->visco ?? '') }}"
                                                placeholder="Visco"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][mixing_ratio]"
                                                value="{{ old('materials.' . $currIdx . '.mixing_ratio', $mat->mixing_ratio ?? '') }}"
                                                placeholder="Ratio (e.g. 1:1.5)"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="any" name="materials[{{ $currIdx }}][qty]"
                                                value="{{ old('materials.' . $currIdx . '.qty', $mat->qty ?? '') }}"
                                                placeholder="Qty"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ $errors->has('materials.' . $currIdx . '.qty') ? 'border-red-500 ring-1 ring-red-500' : '' }} {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][uom]"
                                                value="{{ old('materials.' . $currIdx . '.uom', $mat->uom ?? '') }}"
                                                placeholder="UOM"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ !$isPaintApplicable ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                {{ !$isPaintApplicable ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-1 py-2 text-center">
                                            <button type="button" onclick="removeMaterialRow(this)"
                                                class="text-red-500 hover:text-red-700 font-bold px-1.5 py-0.5 text-sm rounded hover:bg-red-50 transition {{ !$isPaintApplicable ? 'cursor-not-allowed opacity-50' : '' }}"
                                                title="Remove Row" {{ !$isPaintApplicable ? 'disabled' : '' }}>&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        <button type="button" id="add-paint-item-btn" onclick="addPaintMaterialRow()"
                            class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 py-1 px-2 rounded hover:bg-blue-50 transition {{ !$isPaintApplicable ? 'cursor-not-allowed opacity-50' : '' }}"
                            {{ !$isPaintApplicable ? 'disabled' : '' }}>
                            + Add Paint Item
                        </button>
                    </div>
                </div>

                @php
                    $partMaterials = $report->materials ? $report->materials->where('type', 'part')->values() : collect();
                    if ($partMaterials->isEmpty() && !$report->exists) {
                        $defaultParts = config('mes.sp_default_part_materials');
                        foreach ($defaultParts as $pName) {
                            $partMaterials->push((object)[
                                'item_name' => $pName,
                                'lot_number' => '',
                                'qty' => '',
                                'uom' => '',
                            ]);
                        }
                    }
                @endphp

                <!-- Item Parts Table -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <h4 class="text-sm font-bold text-gray-700">Item Parts / WIP Lots</h4>
                            <span class="text-[10px] text-gray-500">Lot values from Plastic/FG IQC</span>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-gray-600">Item Parts</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-1/3">Lot Number</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-24">Qty</th>
                                    <th class="px-2 py-2 text-left font-bold text-gray-600 w-24">UOM</th>
                                    <th class="px-1 py-2 text-center font-bold text-gray-600 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="part-materials-tbody" class="divide-y divide-gray-200">
                                @foreach ($partMaterials as $mat)
                                    @php $currIdx = $materialGlobalIndex++; @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="hidden" name="materials[{{ $currIdx }}][type]" value="part">
                                            <input type="text" name="materials[{{ $currIdx }}][item_name]"
                                                value="{{ old('materials.' . $currIdx . '.item_name', $mat->item_name ?? '') }}"
                                                placeholder="Part / WIP Item Name"
                                                class="w-full text-xs rounded border-gray-300 py-1 font-semibold {{ $errors->has('materials.' . $currIdx . '.item_name') ? 'border-red-500 ring-1 ring-red-500' : '' }}">
                                            @if ($errors->has('materials.' . $currIdx . '.item_name'))
                                                <p class="text-[10px] text-red-600 font-bold mt-0.5">{{ $errors->first('materials.' . $currIdx . '.item_name') }}</p>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][lot_number]"
                                                value="{{ old('materials.' . $currIdx . '.lot_number', $mat->lot_number ?? '') }}"
                                                placeholder="Lot"
                                                class="w-full text-xs rounded border-gray-300 py-1">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="any" name="materials[{{ $currIdx }}][qty]"
                                                value="{{ old('materials.' . $currIdx . '.qty', $mat->qty ?? '') }}"
                                                placeholder="Qty"
                                                class="w-full text-xs rounded border-gray-300 py-1 {{ $errors->has('materials.' . $currIdx . '.qty') ? 'border-red-500 ring-1 ring-red-500' : '' }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="materials[{{ $currIdx }}][uom]"
                                                value="{{ old('materials.' . $currIdx . '.uom', $mat->uom ?? '') }}"
                                                placeholder="UOM"
                                                class="w-full text-xs rounded border-gray-300 py-1">
                                        </td>
                                        <td class="px-1 py-2 text-center">
                                            <button type="button" onclick="removeMaterialRow(this)"
                                                class="text-red-500 hover:text-red-700 font-bold px-1.5 py-0.5 text-sm rounded hover:bg-red-50 transition"
                                                title="Remove Row">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- Live Breakdown Summary Bar -->
                    <div class="px-4 py-2.5 bg-slate-50 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="text-gray-500 font-medium">
                            Breakdown Summary:
                        </div>
                        <div class="flex items-center gap-4 font-mono font-bold">
                            <span class="text-blue-700">WIP: <span id="summary-wip-qty">0</span> Pcs</span>
                            <span class="text-amber-700">Repairan: <span id="summary-repairan-qty">0</span> Pcs</span>
                            <span class="text-slate-800 border-l border-gray-300 pl-4">Total: <span id="summary-total-input-qty">0</span> Pcs</span>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        <button type="button" onclick="addPartMaterialRow()"
                            class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 py-1 px-2 rounded hover:bg-blue-50 transition">
                            + Add Part / WIP Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Tab 2 -->
            <div class="flex justify-between pt-4 border-t border-gray-200 mt-6">
                <button type="button" onclick="switchTab('setup')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded shadow transition">
                    &larr; Back to Setup
                </button>
                <button type="button" onclick="switchTab('production')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                    Next: Production Logs &rarr;
                </button>
            </div>
        </div> <!-- END TAB 2 -->

        <!-- TAB 3: PRODUCTION & NG LOGS -->
        <div id="tab-content-production" class="tab-pane hidden space-y-8">

            <!-- Unified Production & Reconciliation Command Bar (Responsive & Direct) -->
            <div id="reconcile-summary-card" class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5 overflow-hidden transition-all duration-200">
                <input type="hidden" name="sisa_input" id="sisa_input" value="{{ old('sisa_input', $report->sisa_input ?? 0) }}">
                
                <!-- Hidden inputs preserved for form persistence and JS calculations -->
                <input type="hidden" name="jml_input_wip" id="jml_input_wip" value="{{ $report->jml_input_wip }}">
                <input type="hidden" name="repairan" id="repairan" value="{{ $report->repairan }}">
                <input type="hidden" name="jumlah_ok" id="jumlah_ok" value="{{ $report->jumlah_ok }}">
                <input type="hidden" name="jumlah_ng" id="jumlah_ng" value="{{ $report->jumlah_ng }}">
                <input type="hidden" name="jumlah_output" id="jumlah_output" value="{{ $report->jumlah_output }}">
                <input type="hidden" name="ng_prosentase" id="ng_prosentase" value="{{ $report->ng_prosentase }}">

                @php
                    $calcTotalReportInput = ($report->jml_input_wip ?? 0) + ($report->repairan ?? 0);
                    $calcTotalReportOutput = ($report->jumlah_ok ?? 0) + ($report->jumlah_ng ?? 0) + ($report->jml_ng_lebur ?? 0);
                    $calcSisaInput = $calcTotalReportInput - $calcTotalReportOutput;
                @endphp

                <!-- Tier 1: Read-Only KPI Monitoring (Responsive 3-Col / 1-Col Grid) -->
                <div class="p-3 sm:p-4 bg-slate-50/80 border-b border-gray-200/80 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 items-stretch">
                    
                    <!-- Card 1: Input Material -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Input Material</span>
                            <button type="button" onclick="switchTab('materials')" class="text-xs text-blue-600 hover:text-blue-800 font-bold underline cursor-pointer" title="Periksa Item Parts di Tab 2">Tab 2 &rarr;</button>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="text-2xl sm:text-3xl font-black font-mono text-gray-900 leading-none">
                                <span id="reconcile-total-input">{{ number_format($calcTotalReportInput) }}</span> <span class="text-xs font-semibold text-gray-500">pcs</span>
                            </div>
                            <div class="text-right text-xs font-mono font-semibold text-gray-600 space-y-0.5 border-l border-gray-100 pl-2.5">
                                <div>WIP: <strong id="reconcile-wip-qty" class="text-blue-700">{{ number_format($report->jml_input_wip ?? 0) }}</strong></div>
                                <div>Rep: <strong id="reconcile-repairan-qty" class="text-amber-700">{{ number_format($report->repairan ?? 0) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Total Output -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Output</span>
                            <span class="text-xs font-bold text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-full border border-emerald-200">
                                <span id="ng_prosentase_display">{{ number_format($report->ng_prosentase ?? 0, 2) }}</span>% NG
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="text-2xl sm:text-3xl font-black font-mono text-gray-900 leading-none">
                                <span id="reconcile-total-output">{{ number_format($calcTotalReportOutput) }}</span> <span class="text-xs font-semibold text-gray-500">pcs</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-mono font-bold border-l border-gray-100 pl-2.5">
                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200" title="Total OK">OK:<span id="reconcile-ok-qty" class="ml-0.5">{{ number_format($report->jumlah_ok ?? 0) }}</span></span>
                                <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-200" title="Total NG">NG:<span id="reconcile-ng-qty" class="ml-0.5">{{ number_format($report->jumlah_ng ?? 0) }}</span></span>
                                <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200" title="Scrap lebur">Scrap:<span id="reconcile-scrap-qty" class="ml-0.5">{{ number_format($report->jml_ng_lebur ?? 0) }}</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Sisa Material & Balance -->
                    <div id="reconcile-balance-pill" class="p-3.5 sm:p-4 rounded-xl border flex flex-col justify-between space-y-2 transition-colors duration-200 {{ $calcTotalReportInput < $calcTotalReportOutput ? 'bg-red-50 border-red-300 text-red-900' : ($calcSisaInput > 0 ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900') }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider opacity-80">Sisa Material</span>
                            <span id="reconcile-status-badge" class="text-xs font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider border {{ $calcTotalReportInput < $calcTotalReportOutput ? 'bg-red-200 text-red-900 border-red-300' : ($calcSisaInput > 0 ? 'bg-amber-200 text-amber-900 border-amber-300' : 'bg-emerald-200 text-emerald-900 border-emerald-300') }}">
                                {{ $calcTotalReportInput < $calcTotalReportOutput ? '⛔ Defisit' : ($calcSisaInput > 0 ? '⚠️ Ada Sisa' : '✓ Ideal') }}
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="text-2xl sm:text-3xl font-black font-mono leading-none">
                                <span id="reconcile-sisa-qty">{{ number_format(abs($calcSisaInput)) }}</span> <span class="text-xs font-semibold opacity-75">pcs</span>
                            </div>
                            <div id="reconcile-inline-note" class="text-xs font-semibold text-right leading-tight max-w-[190px]">
                                @if ($calcTotalReportInput < $calcTotalReportOutput)
                                    Output melebihi Input! Submission diblokir.
                                @elseif ($calcSisaInput > 0)
                                    Ada sisa input. Remark wajib diisi.
                                @else
                                    Material seimbang (Ideal).
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deficit Alert Banner (Responsive Flex Layout) -->
                <div id="reconcile-state-deficit" class="{{ $calcTotalReportInput >= $calcTotalReportOutput ? 'hidden' : '' }} px-4 py-2.5 bg-red-100 border-b border-red-300 text-xs sm:text-sm text-red-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-red-700">⛔ Peringatan Defisit:</span>
                        <span>Output melebihi Input sebesar <strong class="reconcile-deficit-amount font-mono text-red-950 font-black">{{ number_format(max(0, $calcTotalReportOutput - $calcTotalReportInput)) }}</strong> pcs.</span>
                    </div>
                    <button type="button" onclick="switchTab('materials')" class="text-xs sm:text-sm font-bold text-red-900 underline hover:text-red-950 whitespace-nowrap">
                        Sesuaikan di Tab 2 (Materials) &rarr;
                    </button>
                </div>

                <!-- Tier 2: Interactive Inputs Strip (Touch-Friendly Responsive Grid) -->
                <div class="p-3.5 sm:p-4 bg-white border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-5">
                        <!-- Target Produksi / Jam -->
                        <div>
                            <label for="target_per_hour" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                                Target Produksi / Jam
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <input type="number" name="target_per_hour" id="target_per_hour"
                                    value="{{ $report->target_per_hour }}"
                                    placeholder="0"
                                    class="w-full text-sm sm:text-base font-bold font-mono py-2.5 pl-3.5 pr-20 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs sm:text-sm text-gray-400 font-semibold">
                                    pcs / jam
                                </div>
                            </div>
                        </div>

                        <!-- Scrap / NG Lebur -->
                        <div>
                            <label for="jml_ng_lebur" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                                <span>Kuantitas Scrap (Lebur)</span>
                                <span class="text-xs text-amber-700 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">+ ke Total Output</span>
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <input type="number" name="jml_ng_lebur" id="jml_ng_lebur"
                                    value="{{ $report->jml_ng_lebur }}"
                                    placeholder="0"
                                    class="w-full text-sm sm:text-base font-bold font-mono py-2.5 pl-3.5 pr-14 rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 bg-white"
                                    title="Part rusak lebur/scrap. Otomatis menambah Total Output.">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs sm:text-sm text-gray-400 font-semibold">
                                    pcs
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tier 3: Sisa Input Remark (Expands conditionally when Sisa > 0) -->
                @php
                    $hasRemainingInput = old('sisa_input_remark', $report->sisa_input_remark ?? '') !== ''
                        || ($calcTotalReportInput > $calcTotalReportOutput);
                @endphp
                <div id="reconcile-remark-container" class="{{ !$hasRemainingInput ? 'hidden' : '' }} px-4 py-3 bg-amber-50/90 border-t border-amber-200">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="sisa_input_remark" class="text-xs sm:text-sm font-bold text-amber-900 flex items-center gap-1.5">
                            <span>Alasan / Remark Sisa Input</span>
                            <span class="text-red-600 font-black text-xs">* (Wajib diisi jika ada sisa)</span>
                        </label>
                        <span class="text-xs text-amber-700 font-medium">Jelaskan penanganan sisa WIP</span>
                    </div>
                    <textarea name="sisa_input_remark" id="sisa_input_remark" rows="2"
                        placeholder="Contoh: Sisa 20 pcs belum selesai di-spray karena waktu shift habis, dilanjutkan shift berikutnya."
                        class="w-full text-xs sm:text-sm rounded-lg border-amber-300 focus:border-amber-500 focus:ring-amber-500 bg-white py-2 px-3 {{ $errors->has('sisa_input_remark') ? 'border-red-500 ring-1 ring-red-500' : '' }}">{{ old('sisa_input_remark', $report->sisa_input_remark ?? '') }}</textarea>
                    @if ($errors->has('sisa_input_remark'))
                        <p class="text-xs text-red-600 font-bold mt-1">{{ $errors->first('sisa_input_remark') }}</p>
                    @endif
                </div>
            </div>

            @php
                $currentHoursCount = max(1, $report->hourlyProductions->count());
                $defaultNgs = $report->ngRecords->isNotEmpty()
                    ? $report->ngRecords->pluck('ng_name')->unique()->toArray()
                    : config('mes.sp_default_ng_types');
            @endphp
            <!-- Unified Production & NG Log Grid -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm flex flex-col">
                    <div
                        class="bg-gradient-to-r from-blue-700 to-indigo-800 px-5 py-3 border-b border-gray-200 flex justify-between items-center text-white">
                        <h4 class="text-sm font-bold flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2m0 0h2a2 2 0 002-2v-3a2 2 0 110-4m0 0V5a2 2 0 012-2h2a2 2 0 012 2v3m0 0a2 2 0 110 4m0 0v3a2 2 0 01-2 2h-2m-4-3H9m4 0h2m-4 0v2m0-4V7">
                                </path>
                            </svg>
                            Production & NG Hourly Spreadsheet
                        </h4>
                        <div class="flex space-x-2">
                            <button type="button" id="remove-hour-btn"
                                class="bg-red-800 hover:bg-red-900 text-white font-bold text-xs px-3 py-1.5 rounded shadow-sm border border-red-900 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 12H4"></path>
                                </svg> Hour
                            </button>
                            <button type="button" id="add-hour-btn"
                                class="bg-green-600 hover:bg-green-500 text-white font-bold text-xs px-3 py-1.5 rounded shadow-sm border border-green-700 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg> Hour
                            </button>
                            <button type="button" id="add-ng-type-btn"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs px-3 py-1.5 rounded shadow-sm border border-indigo-700 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg> NG Type
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <!-- ponytail: apply minimum width to all spreadsheet number inputs cleanly via CSS -->
                        <style>
                            #unified-production-table tbody input[type="number"] {
                                min-width: 80px;
                            }

                            #ng-remark-modal::backdrop {
                                background-color: rgba(0, 0, 0, 0.5);
                                backdrop-filter: blur(4px);
                            }
                        </style>
                        <!-- ponytail: simplified unified production sheet -->
                        <table class="min-w-full divide-y divide-gray-200 text-sm table-fixed"
                            id="unified-production-table">
                            <thead class="bg-gray-50 text-gray-500 font-semibold text-xs tracking-wider uppercase">
                                <tr id="production-header-row">
                                    <th
                                        class="px-3 py-3 w-16 text-center sticky left-0 bg-gray-50 z-20 shadow-[1px_0_0_0_#e5e7eb]">
                                        Hour</th>
                                    <th
                                        class="px-3 py-2.5 w-28 text-center bg-green-50 text-green-800 font-bold border-l border-green-100">
                                        <div class="leading-tight text-xs sm:text-sm">OK Qty</div>
                                        <span class="inline-block text-[10px] font-bold text-green-800 bg-green-200/70 px-1.5 py-0.5 rounded mt-0.5 uppercase tracking-wider">Input</span>
                                    </th>
                                    <th
                                        class="px-3 py-2.5 w-28 text-center bg-green-50/60 text-green-700 border-r border-green-100">
                                        <div class="leading-tight text-xs sm:text-sm">Accum OK</div>
                                        <span class="inline-block text-[10px] font-bold text-gray-500 bg-gray-200/70 px-1.5 py-0.5 rounded mt-0.5 uppercase tracking-wider">Auto</span>
                                    </th>
                                    <th
                                        class="px-3 py-2.5 w-24 text-center bg-red-50/60 text-red-800 font-bold border-r border-red-100">
                                        <div class="leading-tight text-xs sm:text-sm">Total NG</div>
                                        <span class="inline-block text-[10px] font-bold text-gray-500 bg-gray-200/70 px-1.5 py-0.5 rounded mt-0.5 uppercase tracking-wider">Auto</span>
                                    </th>
                                    @foreach ($defaultNgs as $index => $ng)
                                        @php
                                            $ngRecord = $report->ngRecords->where('ng_name', $ng)->first();
                                            $rawItem = old("ngs.{$index}.ng_input_item", $ngRecord ? $ngRecord->ng_input_item : '');
                                            $rawQty = old("ngs.{$index}.ng_input_qty", $ngRecord ? $ngRecord->ng_input_qty : '');
                                            $colTotalNg = (int) old("ngs.{$index}.total_ng", $ngRecord ? $ngRecord->total_ng : 0);
                                            $hasRemark = !empty($rawItem) || ($rawQty !== null && $rawQty !== '');
                                            $remQty = ($rawQty !== null && $rawQty !== '') ? (int) $rawQty : 0;

                                            if ($colTotalNg === 0) {
                                                $btnClass = 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-700 font-semibold';
                                                $labelText = 'Add Remark';
                                            } elseif (!$hasRemark || $remQty === 0) {
                                                $btnClass = 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100 font-bold animate-pulse';
                                                $labelText = "Remark Needed (0/{$colTotalNg})";
                                            } elseif ($remQty !== $colTotalNg) {
                                                $btnClass = 'bg-amber-50 border-amber-300 text-amber-800 hover:bg-amber-100 font-bold';
                                                $labelText = "Needs Update ({$remQty}/{$colTotalNg})";
                                            } else {
                                                $btnClass = 'bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100 font-bold';
                                                $prettified = preg_replace(
                                                    '/\[(\d+)\]\s*([^\|]+)/',
                                                    '$1x $2',
                                                    $rawItem,
                                                );
                                                $prettified = str_replace(' | ', ', ', $prettified);
                                                $labelText = "{$prettified} ({$remQty})";
                                            }
                                        @endphp
                                        <th class="group px-3 py-2 w-32 text-center ng-type-header relative select-none border-b border-gray-200"
                                            data-ng="{{ $ng }}">
                                            <div class="flex flex-col space-y-1">
                                                <div class="flex items-center justify-center space-x-1">
                                                    <span
                                                        class="font-bold text-xs uppercase">{{ $ng }}</span>
                                                    <button type="button"
                                                        class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity delete-ng-col-btn text-[10px] font-bold"
                                                        data-ng="{{ $ng }}"
                                                        title="Delete {{ $ng }}">&times;</button>
                                                </div>
                                                <button type="button"
                                                    class="ng-remark-btn mt-1 flex items-center justify-between w-full px-2 py-1.5 text-[10px] rounded border transition-all select-none {{ $btnClass }}"
                                                    data-ng-name="{{ $ng }}"
                                                    data-ng-index="{{ $index }}">
                                                    <span
                                                        class="truncate remark-preview-label">{{ $labelText }}</span>
                                                    <svg class="w-3 h-3 ml-1 text-gray-400 flex-shrink-0"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <input type="hidden" name="ngs[{{ $index }}][ng_input_item]"
                                                    value="{{ $rawItem }}"
                                                    class="ng-input-item-hidden">
                                                <input type="hidden" name="ngs[{{ $index }}][ng_input_qty]"
                                                    value="{{ $rawQty }}"
                                                    class="ng-input-qty-hidden">
                                                <input type="hidden" name="ngs[{{ $index }}][ng_name]"
                                                    value="{{ $ng }}">
                                                <input type="hidden" name="ngs[{{ $index }}][total_ng]"
                                                    id="ng-total-hidden-{{ $index }}"
                                                    value="{{ $colTotalNg }}">
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white" id="production-tbody">
                                @for ($hour = 1; $hour <= $currentHoursCount; $hour++)
                                    @php
                                        $hourlyMatch = $report->hourlyProductions->where('hour_ke', $hour)->first();
                                    @endphp
                                    <tr class="production-row hover:bg-blue-50/50 transition-colors duration-100"
                                        data-hour="{{ $hour }}">
                                        <!-- Hour sticky left -->
                                        <td
                                            class="px-3 py-2 text-center font-bold text-gray-600 bg-gray-50 sticky left-0 z-10 shadow-[1px_0_0_0_#e5e7eb]">
                                            {{ $hour }}</td>

                                        <!-- OK Qty -->
                                        <td class="px-2 py-1.5 bg-green-50/30 border-l border-green-100">
                                            <input type="number" name="hourly[{{ $hour }}][hour_ke]"
                                                value="{{ $hour }}" class="hidden">
                                            <input type="number" inputmode="numeric"
                                                name="hourly[{{ $hour }}][ok_qty]"
                                                value="{{ $hourlyMatch ? $hourlyMatch->ok_qty : '' }}"
                                                placeholder="-"
                                                class="w-full text-center text-sm font-bold text-green-800 rounded-md border-gray-200 focus:border-green-500 focus:ring-green-500 py-2 hourly-ok-input shadow-inner bg-white">
                                        </td>

                                        <!-- Accum OK (Readonly) -->
                                        <td class="px-2 py-1.5 bg-green-50/30 border-r border-green-100">
                                            <input type="number" name="hourly[{{ $hour }}][acumulasi_qty]"
                                                value="{{ $hourlyMatch ? $hourlyMatch->acumulasi_qty : '' }}"
                                                placeholder="0"
                                                class="w-full text-center text-sm font-extrabold text-green-700 bg-transparent border-transparent py-2 hourly-accum-input"
                                                readonly>
                                        </td>

                                        <!-- Total NG (Readonly) -->
                                        <td class="px-2 py-1.5 bg-red-50/30 border-r border-red-100">
                                            <input type="number" name="hourly[{{ $hour }}][ng_qty]"
                                                value="{{ $hourlyMatch ? $hourlyMatch->ng_qty : '' }}"
                                                placeholder="0"
                                                class="w-full text-center text-sm font-extrabold text-red-700 bg-transparent border-transparent py-2 hourly-ng-total-input"
                                                readonly>
                                        </td>

                                        <!-- NG Type Inputs -->
                                        @foreach ($defaultNgs as $index => $ng)
                                            @php
                                                $ngRecord = $report->ngRecords->where('ng_name', $ng)->first();
                                                $ngDetail = $ngRecord
                                                    ? $ngRecord->hourlyDetails->where('hour_ke', $hour)->first()
                                                    : null;
                                                $ngVal = old("ngs.{$index}.hours.{$hour}", $ngDetail ? $ngDetail->qty : '');
                                            @endphp
                                            <td class="px-1.5 py-1.5 ng-cell transition-colors duration-100"
                                                data-ng="{{ $ng }}">
                                                <input type="number" inputmode="numeric"
                                                    name="ngs[{{ $index }}][hours][{{ $hour }}]"
                                                    value="{{ $ngVal }}"
                                                    class="w-full text-center text-sm rounded-md border-transparent hover:border-gray-300 focus:border-red-500 focus:ring-red-500 py-2 bg-transparent hover:bg-white focus:bg-white transition-all ng-hourly-input {{ $ngVal && $ngVal > 0 ? 'bg-red-50 text-red-700 font-bold shadow-inner' : '' }}"
                                                    data-hour="{{ $hour }}"
                                                    data-ng-index="{{ $index }}" placeholder="-">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Navigation Tab 3 -->
            <div class="flex justify-between pt-4 border-t border-gray-200 mt-6">
                <button type="button" onclick="switchTab('materials')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded shadow transition">
                    &larr; Back to Materials
                </button>
                <button type="button" onclick="switchTab('handover')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                    Next: Handover &rarr;
                </button>
            </div>
        </div> <!-- END TAB 3 -->

        <!-- TAB 4: HANDOVER & SIGNATURES -->
        <div id="tab-content-handover" class="tab-pane hidden space-y-8">

            <!-- Troubles Section -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b pb-2 gap-2">
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Trouble / Downtime Report</h3>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Total Downtime:</span>
                            <span id="total-downtime-badge" class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">
                                <span id="total-downtime-minutes">0</span> Mins (<span id="total-downtime-hours">0.0</span> hrs)
                            </span>
                        </div>
                        <button type="button" onclick="addTroubleRow()"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition cursor-pointer">
                            + Tambah Masalah
                        </button>
                    </div>
                </div>

                <div class="bg-slate-50 md:bg-white rounded-xl border border-gray-200 overflow-x-auto shadow-sm">
                    <table class="w-full block md:table text-sm">
                        <thead class="hidden md:table-header-group bg-gray-50 font-bold text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2.5 text-center w-12">#</th>
                                <th class="px-3 py-2.5 text-left w-3/12 min-w-[180px]">Masalah</th>
                                <th class="px-3 py-2.5 text-left w-2/12 min-w-[160px]">Kategori</th>
                                <th class="px-3 py-2.5 text-left w-4/12 min-w-[180px]">Penanganan</th>
                                <th class="px-3 py-2.5 text-right w-1/12 min-w-[140px]">Loss Time</th>
                                <th class="px-2 py-2.5 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="troubles-tbody" class="block md:table-row-group p-3 md:p-0 space-y-3 md:space-y-0 md:divide-y md:divide-gray-200">
                            @php
                                $categoriesList = config('mes.sp_trouble_categories');
                            @endphp

                            {{-- Empty State Row --}}
                            <tr id="troubles-empty-row" class="block md:table-row" @if ($report->troubles->count() > 0) style="display: none;" @endif>
                                <td colspan="6" class="block md:table-cell px-4 py-4 text-center text-xs text-gray-400 italic">
                                    Tidak ada kendala / trouble yang dicatat.
                                </td>
                            </tr>

                            {{-- Render existing troubles --}}
                            @foreach ($report->troubles as $index => $trouble)
                                @php
                                    $currentCat = $trouble->penyebab ?: ($trouble->category ?: 'Mesin');
                                @endphp
                                <tr class="trouble-row block md:table-row bg-white rounded-xl md:rounded-none border border-gray-200 md:border-0 p-4 md:p-0 shadow-xs md:shadow-none hover:bg-slate-50/70 transition">
                                    <td class="block md:table-cell p-0 pb-2.5 md:px-3 md:py-3 align-top text-xs font-bold text-gray-500 border-b md:border-b-0 border-gray-100 mb-3 md:mb-0">
                                        <div class="flex items-center justify-between md:justify-center">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-bold text-xs md:bg-transparent md:border-0 md:p-0 md:text-gray-500">
                                                <span class="md:hidden">Masalah #</span><span class="trouble-row-num">{{ $loop->iteration }}</span>
                                            </span>
                                            <button type="button" onclick="removeTroubleRow(this)"
                                                class="md:hidden text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                    <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                                        <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                                            Masalah <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="troubles[{{ $index }}][masalah]" rows="2"
                                            class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 focus:border-blue-500 focus:ring-blue-500 transition shadow-xs {{ $errors->has('troubles.' . $index . '.masalah') ? 'border-red-500 ring-1 ring-red-500' : '' }}"
                                            placeholder="Jelaskan kendala / masalah yang terjadi...">{{ old('troubles.' . $index . '.masalah', $trouble->masalah) }}</textarea>
                                        @if ($errors->has('troubles.' . $index . '.masalah'))
                                            <p class="text-[10px] text-red-600 font-bold mt-0.5">{{ $errors->first('troubles.' . $index . '.masalah') }}</p>
                                        @endif
                                    </td>
                                    <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                                        <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                                            Kategori <span class="text-red-500">*</span>
                                        </label>
                                        <select name="troubles[{{ $index }}][penyebab]"
                                            class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 font-medium focus:border-blue-500 focus:ring-blue-500 transition shadow-xs">
                                            @foreach ($categoriesList as $catKey => $catLabel)
                                                <option value="{{ $catKey }}" {{ strcasecmp($currentCat, $catKey) === 0 ? 'selected' : '' }}>
                                                    {{ $catLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                                        <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                                            Penanganan
                                        </label>
                                        <textarea name="troubles[{{ $index }}][penanganan]" rows="2"
                                            class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 focus:border-blue-500 focus:ring-blue-500 transition shadow-xs"
                                            placeholder="Tindakan penanganan / perbaikan...">{{ old('troubles.' . $index . '.penanganan', $trouble->penanganan) }}</textarea>
                                    </td>
                                    <td class="block md:table-cell p-0 md:px-3 md:py-3 align-top">
                                        <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                                            Loss Time (Menit)
                                        </label>
                                        <div class="relative rounded-lg shadow-xs">
                                            <input type="number"
                                                name="troubles[{{ $index }}][loss_time_minutes]"
                                                value="{{ old('troubles.' . $index . '.loss_time_minutes', $trouble->loss_time_minutes ?: '') }}"
                                                min="0"
                                                max="1440"
                                                placeholder="0"
                                                class="trouble-loss-minutes w-full text-xs md:text-sm rounded-lg border-gray-300 py-1.5 md:py-2 pr-12 text-right font-mono font-bold focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('troubles.' . $index . '.loss_time_minutes') ? 'border-red-500 ring-1 ring-red-500' : '' }}">
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                <span class="text-xs font-bold text-gray-400 uppercase">min</span>
                                            </div>
                                        </div>
                                        @if ($errors->has('troubles.' . $index . '.loss_time_minutes'))
                                            <p class="text-[10px] text-red-600 font-bold mt-0.5">{{ $errors->first('troubles.' . $index . '.loss_time_minutes') }}</p>
                                        @endif
                                    </td>
                                    <td class="hidden md:table-cell px-2 py-3 align-top text-center">
                                        <button type="button" onclick="removeTroubleRow(this)"
                                            class="text-red-500 hover:text-red-700 font-bold p-1 rounded-lg hover:bg-red-50 transition cursor-pointer text-base leading-none"
                                            title="Hapus masalah ini">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="block md:table-footer-group bg-gray-50 border-t border-gray-200">
                            <tr class="flex justify-between items-center md:table-row p-3 md:p-0">
                                <td colspan="4" class="inline-block md:table-cell text-xs font-bold text-gray-600 uppercase tracking-wider md:px-4 md:py-2.5 md:text-right">
                                    Total Loss Time:
                                </td>
                                <td class="inline-block md:table-cell font-mono font-black text-xs text-amber-900 md:px-4 md:py-2.5 md:text-right">
                                    <span id="footer-total-downtime-minutes">0</span> mins
                                </td>
                                <td class="hidden md:table-cell"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="p-3 bg-gray-50/70 border-t border-gray-200 flex justify-between items-center">
                        <button type="button" onclick="addTroubleRow()"
                            class="w-full sm:w-auto px-3 py-1.5 rounded-lg text-xs font-bold bg-white hover:bg-amber-50 text-amber-700 border border-amber-300 shadow-2xs transition cursor-pointer">
                            + Tambah Masalah
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes, Attendance, & Schedule Section -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-5 rounded-lg border border-gray-200 text-sm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan
                            Produksi</label>
                        <textarea name="production_notes" rows="4"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs"
                            placeholder="General production notes...">{{ $report->production_notes }}</textarea>
                    </div>
                    <div class="mt-3">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan /
                            Remarks NG</label>
                        <textarea name="ng_remarks" rows="2"
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs"
                            placeholder="Remarks for NG causes...">{{ $report->ng_remarks }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Karyawan
                            Tidak Hadir</label>
                        <input type="text" name="absent_employees" value="{{ $report->absent_employees }}"
                            placeholder="Absent employees list..."
                            class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs">
                    </div>
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jadwal Produksi
                        Selanjutnya</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @for ($i = 0; $i < 4; $i++)
                            @php
                                $schVal = $report->next_production_schedule[$i] ?? '';
                                if (empty($schVal) && is_string($report->next_production_schedule)) {
                                    $schedules = explode("\n", $report->next_production_schedule);
                                    foreach ($schedules as $s) {
                                        if (strpos($s, $i + 1 . ': ') === 0) {
                                            $schVal = substr($s, 3);
                                        }
                                    }
                                }
                            @endphp
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold text-gray-500 w-4">{{ $i + 1 }}.</span>
                                <input type="text" name="next_production_schedule[]" value="{{ $schVal }}"
                                    placeholder="Next schedule item"
                                    class="w-full text-xs rounded border-gray-300 py-1 schedule-input">
                            </div>
                        @endfor
                    </div>

                    <!-- Signature / Approvals placeholder -->
                    <div class="pt-4 border-t border-gray-200">
                        <div
                            class="p-3 bg-gray-100 border border-gray-200 rounded text-center text-xs text-gray-500 font-semibold">
                            Signatures will be digitally recorded upon report submission and role-based
                            approval.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tab 4 -->
            <div class="flex justify-between pt-4 border-t border-gray-200 mt-6">
                <button type="button" onclick="switchTab('production')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded shadow transition">
                    &larr; Back to Production
                </button>
                <div>
                    <button type="button" onclick="submitProductionReport()" id="submit-btn"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-md transition">
                        Submit Production Report
                    </button>
                </div>
            </div>
        </div> <!-- END TAB 4 -->


    </div>

    <!-- Sticky Footer (For Global Save Draft utility) -->
    <div class="bg-gray-100 px-6 py-4 flex justify-between items-center border-t border-gray-200">
        <a href="{{ $report->exists ? route('second-process-reports.show', $report->id) : route('second-process-reports.index') }}"
            class="text-gray-600 hover:text-gray-800 text-sm font-semibold transition">Cancel</a>
        <button type="button" onclick="saveAsDraft()"
            class="bg-gray-600 hover:bg-gray-700 text-white text-xs font-bold py-1.5 px-4 rounded shadow transition">
            Save Draft
        </button>
    </div>

</div>

<!-- Defect Remark Drilldown Dialog -->
<dialog id="ng-remark-modal"
    class="rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden backdrop:bg-black/50 backdrop:backdrop-blur-sm">
    <div class="flex flex-col bg-white">
        <div
            class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-4 text-white flex justify-between items-center">
            <h3 class="font-bold text-lg" id="modal-ng-title">Defect Detail</h3>
            <button type="button"
                class="text-white hover:text-gray-200 text-xl font-bold close-modal-btn">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <!-- Balance Tracker Banner -->
            <div id="modal-balance-banner" class="p-3 rounded-lg border bg-gray-50 border-gray-200 text-xs flex items-center justify-between transition-colors">
                <div>
                    <span class="text-gray-500 font-semibold">Target Defect:</span>
                    <span id="modal-target-ng" class="font-bold text-gray-900 ml-1">0</span> <span class="text-[10px] text-gray-400">pcs</span>
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="text-gray-500 font-semibold">Allocated:</span>
                    <span id="modal-allocated-ng" class="font-bold text-gray-900 ml-1">0</span> <span class="text-[10px] text-gray-400">pcs</span>
                </div>
                <div id="modal-balance-badge" class="px-2 py-0.5 rounded font-extrabold text-[11px] bg-gray-200 text-gray-700">
                    Remaining: 0
                </div>
            </div>

            <div id="modal-rows-container" class="space-y-3 max-h-60 overflow-y-auto">
                <!-- Dynamic rows injected here -->
            </div>
            <button type="button" id="add-modal-row-btn"
                class="w-full py-1.5 border border-dashed border-blue-400 hover:border-blue-600 text-blue-600 hover:text-blue-800 text-xs font-bold rounded flex items-center justify-center transition select-none">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg> Add Detail Row
            </button>
            <div id="modal-validation-hint" class="text-[11px] text-amber-700 font-semibold hidden text-center bg-amber-50 p-2 rounded border border-amber-200">
                Total kuantitas alokasi harus sama persis dengan target defect.
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-2 border-t border-gray-100">
            <button type="button"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold rounded transition close-modal-btn">Cancel</button>
            <button type="button" id="save-modal-remark-btn"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded shadow transition">Save
                Details</button>
        </div>
    </div>
</dialog>

<!-- Script calculations & interactions -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('production-report-form');
        const submitBtn = document.getElementById('submit-btn');

        // 1. Double Submission Protection
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg> Saving...`;
        });

        // Tab Names Map for User Feedback
        const tabNamesMap = {
            'setup': '1. Setup & Manpower',
            'materials': '2. Materials',
            'production': '3. Production Logs & NG',
            'handover': '4. Handover & Signs'
        };

        // Tab Navigation Logic
        window.switchTab = function(tabId) {
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.add('hidden');
            });
            // Show requested tab pane
            const targetPane = document.getElementById('tab-content-' + tabId);
            if (targetPane) {
                targetPane.classList.remove('hidden');
            }

            // Update tab buttons style
            document.querySelectorAll('.tab-btn').forEach(btn => {
                const isActive = btn.getAttribute('data-tab') === tabId;
                if (isActive) {
                    btn.classList.add('border-blue-600', 'text-blue-600', 'active');
                    btn.classList.remove('border-transparent', 'text-gray-500');
                } else {
                    btn.classList.remove('border-blue-600', 'text-blue-600', 'active');
                    btn.classList.add('border-transparent', 'text-gray-500');
                }
            });

            // Highlight active warnings on tab shift
            if (typeof validateTotals === 'function') {
                validateTotals();
            }

            // Scroll tabs into view smoothly
            const tabNav = document.getElementById('form-tabs-navigation');
            if (tabNav) {
                tabNav.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        };

        // Switch to Tab and focus invalid field
        window.switchTabAndFocus = function(tabId, fieldName) {
            window.switchTab(tabId);
            if (fieldName) {
                setTimeout(() => {
                    let el = document.querySelector(`[name="${fieldName}"]`);
                    if (!el) {
                        // Support Laravel dot notation to bracket notation: materials.0.item_name -> materials[0][item_name]
                        const bracketName = fieldName.replace(/\.([^\.]+)/g, '[$1]');
                        el = document.querySelector(`[name="${bracketName}"]`);
                    }
                    if (!el) {
                        el = document.getElementById(fieldName);
                    }
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        try { el.focus(); } catch (e) {}
                        el.classList.add('ring-2', 'ring-red-500');
                    }
                }, 100);
            }
        };

        // Auto-switch to first error tab on redirect
        @if ($errors->any() && !empty($firstErrorTab))
            document.addEventListener('DOMContentLoaded', function() {
                window.switchTab('{{ $firstErrorTab }}');
                const errBanner = document.getElementById('form-error-summary');
                if (errBanner) {
                    setTimeout(() => {
                        errBanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 150);
                }
            });
        @endif

        // Direct tab button event listeners
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                switchTab(this.getAttribute('data-tab'));
            });
        });

        // Save Draft helper function
        window.saveAsDraft = function() {
            const statusField = document.getElementById('status-field');
            if (statusField) {
                statusField.value = 'draft';
            }

            // Highlight checking of required inputs
            const formInputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;
            let firstInvalid = null;

            formInputs.forEach(input => {
                if (!input.value) {
                    valid = false;
                    input.classList.add('border-red-500');
                    if (!firstInvalid) firstInvalid = input;
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!valid && firstInvalid) {
                const pane = firstInvalid.closest('.tab-pane');
                const tabId = pane ? pane.id.replace('tab-content-', '') : 'setup';
                switchTab(tabId);
                firstInvalid.focus();
                alert(`Harap lengkapi kolom yang wajib diisi pada ${tabNamesMap[tabId] || tabId} untuk menyimpan draft.`);
                return;
            }

            form.submit();
        };

        // Submit Production Report helper function
        window.submitProductionReport = function() {
            const statusField = document.getElementById('status-field');
            if (statusField) {
                statusField.value = 'submitted';
            }

            // 1. Highlight checking of inputs before submit
            const formInputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;
            let firstInvalid = null;

            formInputs.forEach(input => {
                if (!input.value) {
                    valid = false;
                    input.classList.add('border-red-500');
                    if (!firstInvalid) firstInvalid = input;
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!valid && firstInvalid) {
                const pane = firstInvalid.closest('.tab-pane');
                const tabId = pane ? pane.id.replace('tab-content-', '') : 'setup';
                switchTab(tabId);
                firstInvalid.focus();
                alert(`Harap lengkapi kolom yang wajib diisi pada ${tabNamesMap[tabId] || tabId} untuk mengirim laporan.`);
                return;
            }

            // 2. Strict check on Defect (NG) Remarks when Total NG > 0
            const missingRemarkNgs = [];
            activeNgs.forEach((ng, index) => {
                const hiddenTotal = document.getElementById(`ng-total-hidden-${index}`);
                const colTotal = hiddenTotal ? (parseInt(hiddenTotal.value) || 0) : 0;

                if (colTotal > 0) {
                    const th = document.querySelector(`th.ng-type-header[data-ng="${ng}"]`) || (hiddenTotal ? hiddenTotal.closest('th') : null);
                    const itemHidden = th ? th.querySelector('.ng-input-item-hidden') : null;
                    const qtyHidden = th ? th.querySelector('.ng-input-qty-hidden') : null;

                    const itemVal = itemHidden ? itemHidden.value.trim() : '';
                    const qtyVal = qtyHidden && qtyHidden.value !== '' ? parseInt(qtyHidden.value) : 0;

                    if (!itemVal || qtyVal !== colTotal) {
                        missingRemarkNgs.push({
                            name: ng,
                            total: colTotal,
                            allocated: qtyVal,
                            th: th
                        });
                    }
                }
            });

            if (missingRemarkNgs.length > 0) {
                switchTab('production');

                missingRemarkNgs.forEach(item => {
                    if (item.th) {
                        item.th.classList.add('ring-2', 'ring-red-500', 'bg-red-50');
                        setTimeout(() => {
                            item.th.classList.remove('ring-2', 'ring-red-500', 'bg-red-50');
                        }, 6000);
                    }
                });

                const tableEl = document.getElementById('unified-production-table');
                if (tableEl) {
                    tableEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                const ngNamesList = missingRemarkNgs.map(item => `• ${item.name}: dialokasikan ${item.allocated} dari ${item.total} pcs`).join('\n');
                alert(`Pengiriman laporan belum dapat diproses!\n\nTerdapat defect dengan nilai NG > 0 yang belum memiliki remark lengkap atau kuantitas alokasi belum sesuai:\n\n${ngNamesList}\n\nHarap klik tombol 'Add Remark' pada kolom header terkait dan sesuaikan detail defect.`);
                return;
            }

            // 3. Strict Material Input vs Production Output Reconciliation (Output = OK + NG + Scrap)
            const wipVal = parseInt(document.getElementById('jml_input_wip')?.value) || 0;
            const repVal = parseInt(document.getElementById('repairan')?.value) || 0;
            const totalInputVal = wipVal + repVal;

            const okVal = parseInt(document.getElementById('jumlah_ok')?.value) || 0;
            const ngVal = parseInt(document.getElementById('jumlah_ng')?.value) || 0;
            const scrapVal = parseInt(document.getElementById('jml_ng_lebur')?.value) || 0;
            const totalOutputVal = okVal + ngVal + scrapVal;

            const sisaInputVal = totalInputVal - totalOutputVal;

            if (totalInputVal < totalOutputVal) {
                switchTab('production');
                const reconcileCard = document.getElementById('reconcile-summary-card');
                if (reconcileCard) {
                    reconcileCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    reconcileCard.classList.add('ring-4', 'ring-red-500');
                    setTimeout(() => reconcileCard.classList.remove('ring-4', 'ring-red-500'), 6000);
                }
                const deficit = totalOutputVal - totalInputVal;
                alert(`Pengiriman laporan DITOLAK!\n\nTotal Output (${totalOutputVal.toLocaleString()} pcs) MELEBIHI Total Input (${totalInputVal.toLocaleString()} pcs) sebesar ${deficit.toLocaleString()} pcs.\n\nKuantitas output tidak boleh melebihi material masuk. Silakan periksa kembali WIP / Repairan di Tab 2 atau input hasil produksi di Tab 3.`);
                return;
            }

            if (sisaInputVal > 0) {
                const remarkInput = document.getElementById('sisa_input_remark');
                const remarkVal = remarkInput ? remarkInput.value.trim() : '';

                if (!remarkVal) {
                    switchTab('production');
                    const reconcileCard = document.getElementById('reconcile-summary-card');
                    if (reconcileCard) {
                        reconcileCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (remarkInput) {
                        remarkInput.classList.add('ring-2', 'ring-red-500', 'border-red-500');
                        remarkInput.focus();
                        setTimeout(() => remarkInput.classList.remove('ring-2', 'ring-red-500', 'border-red-500'), 6000);
                    }
                    alert(`Pengiriman laporan belum dapat diproses!\n\nTerdapat SISA INPUT sebanyak ${sisaInputVal.toLocaleString()} pcs (Total Input: ${totalInputVal.toLocaleString()} pcs, Total Output: ${totalOutputVal.toLocaleString()} pcs).\n\nAlasan/remark sisa input wajib diisi sebelum laporan disubmit. Silakan isi kolom 'Alasan / Remark Sisa Input' di Tab 3.`);
                    return;
                }
            }

            // Set loading state on submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> Submitting...`;
            }

            form.submit();
        };

        // 2. Autocomplete helper
        function setupAutocomplete(inputId, dropdownId, url, onSelect) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            let debounceTimer;

            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = input.value.trim();

                if (query.length < 2) {
                    dropdown.innerHTML = '';
                    dropdown.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`${url}?query=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            dropdown.innerHTML = '';
                            if (data.length === 0) {
                                dropdown.classList.add('hidden');
                                return;
                            }

                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className =
                                    'px-4 py-2 hover:bg-blue-50 cursor-pointer text-xs border-b border-gray-100 last:border-b-0 text-gray-800 transition';

                                if (item.item_code) {
                                    div.innerHTML =
                                        `<span class="font-bold text-blue-700">${item.item_code}</span> - <span class="text-gray-500">${item.item_description || ''}</span>`;
                                } else if (item.display_label) {
                                    div.textContent = item.display_label;
                                } else if (item.name) {
                                    div.textContent = item.name;
                                }

                                div.addEventListener('click', () => {
                                    onSelect(item);
                                    dropdown.classList.add('hidden');
                                });
                                dropdown.appendChild(div);
                            });
                            dropdown.classList.remove('hidden');
                        })
                        .catch(err => console.error(err));
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (e.target !== input && e.target !== dropdown) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // Initialize Autocompletes
        setupAutocomplete('part_number', 'part-number-dropdown',
            '{{ route('second-process-reports.search-items') }}',
            function(item) {
                document.getElementById('part_number').value = item.item_code;
                document.querySelector('input[name="part_name"]').value = item.item_name || item
                    .item_description || '';
                if (item.project_code) {
                    document.querySelector('input[name="model"]').value = item.project_code;
                }
                document.getElementById('customer').value = item.customer_name || '';
            });
        setupAutocomplete('customer', 'customer-dropdown',
            '{{ route('second-process-reports.search-customers') }}',
            function(item) {
                document.getElementById('customer').value = item.customer_name || item.name || '';
            });

        // 3. Dynamic Hour Management (Unified Production Table Sync)
        const addHourBtn = document.getElementById('add-hour-btn');
        const removeHourBtn = document.getElementById('remove-hour-btn');
        const addNgTypeBtn = document.getElementById('add-ng-type-btn');

        // ponytail: active NG types list stored in state
        let activeNgs = {!! json_encode($defaultNgs) !!};

        addHourBtn.addEventListener('click', function() {
            const currentHours = document.querySelectorAll('.production-row').length;
            if (currentHours >= 8) {
                const proceed = confirm(
                    `Peringatan: Jumlah waktu kerja telah melebihi 8 jam. Apakah Anda yakin ingin menambah jam ke-${currentHours + 1}?`
                );
                if (!proceed) return;
            }

            const newHour = currentHours + 1;

            // ponytail: dynamically render cells based on current activeNgs state
            let ngCells = '';
            activeNgs.forEach((ng, index) => {
                ngCells += `
                    <td class="px-1.5 py-1.5 ng-cell transition-colors duration-100" data-ng="${ng}">
                        <input type="number" inputmode="numeric" name="ngs[${index}][hours][${newHour}]" class="w-full text-center text-sm rounded-md border-transparent hover:border-gray-300 focus:border-red-500 focus:ring-red-500 py-2 bg-transparent hover:bg-white focus:bg-white transition-all ng-hourly-input" data-hour="${newHour}" data-ng-index="${index}" placeholder="-">
                    </td>
                `;
            });

            const newRow = `
    <tr class="production-row hover:bg-blue-50/50 transition-colors duration-100" data-hour="${newHour}">
        <td class="px-3 py-2 text-center font-bold text-gray-600 bg-gray-50 sticky left-0 z-10 shadow-[1px_0_0_0_#e5e7eb]">${newHour}</td>
        <td class="px-2 py-1.5 bg-green-50/30 border-l border-green-100">
            <input type="number" name="hourly[${newHour}][hour_ke]" value="${newHour}" class="hidden">
            <input type="number" inputmode="numeric" name="hourly[${newHour}][ok_qty]" placeholder="-" class="w-full text-center text-sm font-bold text-green-800 rounded-md border-gray-200 focus:border-green-500 focus:ring-green-500 py-2 hourly-ok-input shadow-inner bg-white">
        </td>
        <td class="px-2 py-1.5 bg-green-50/30 border-r border-green-100">
            <input type="number" name="hourly[${newHour}][acumulasi_qty]" placeholder="0" class="w-full text-center text-sm font-extrabold text-green-700 bg-transparent border-transparent py-2 hourly-accum-input" readonly>
        </td>
        <td class="px-2 py-1.5 bg-red-50/30 border-r border-red-100">
            <input type="number" name="hourly[${newHour}][ng_qty]" placeholder="0" class="w-full text-center text-sm font-extrabold text-red-700 bg-transparent border-transparent py-2 hourly-ng-total-input" readonly>
        </td>
        ${ngCells}
    </tr>`;
            document.getElementById('production-tbody').insertAdjacentHTML('beforeend', newRow);

            calculateHourlyAccumulation();
            calculateNgTotals();
        });

        removeHourBtn.addEventListener('click', function() {
            const currentHours = document.querySelectorAll('.production-row').length;
            if (currentHours <= 1) {
                alert('At least 1 hour of production is required.');
                return;
            }

            document.querySelector(`.production-row[data-hour="${currentHours}"]`).remove();

            calculateHourlyAccumulation();
            calculateNgTotals();
        });

        // ponytail: dynamic NG Type Add & Delete
        addNgTypeBtn.addEventListener('click', function() {
            const name = prompt("Masukkan nama Tipe NG Baru (contoh: PAINT RUN, BUBBLE):");
            if (!name) return;
            const uppercaseName = name.trim().toUpperCase();
            if (uppercaseName === '') return;
            if (activeNgs.includes(uppercaseName)) {
                alert('Tipe NG tersebut sudah ada!');
                return;
            }

            activeNgs.push(uppercaseName);
            const newNgIndex = activeNgs.length - 1;

            // 1. Add column to table header
            const headerRow = document.getElementById('production-header-row');
            const th = document.createElement('th');
            th.className =
                'group px-3 py-2 w-32 text-center ng-type-header relative select-none border-b border-gray-200';
            th.dataset.ng = uppercaseName;
            th.innerHTML = `
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center justify-center space-x-1">
                        <span class="font-bold text-xs uppercase">${uppercaseName}</span>
                        <button type="button" class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity delete-ng-col-btn text-[10px] font-bold" data-ng="${uppercaseName}" title="Delete ${uppercaseName}">&times;</button>
                    </div>
                    <button type="button" 
                        class="ng-remark-btn mt-1 flex items-center justify-between w-full px-2 py-1.5 text-[10px] font-semibold bg-gray-50 border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-700 rounded transition-all select-none"
                        data-ng-name="${uppercaseName}"
                        data-ng-index="${newNgIndex}">
                        <span class="truncate remark-preview-label">Add Remark</span>
                        <svg class="w-3 h-3 ml-1 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <input type="hidden" name="ngs[${newNgIndex}][ng_input_item]" value="" class="ng-input-item-hidden">
                    <input type="hidden" name="ngs[${newNgIndex}][ng_input_qty]" value="" class="ng-input-qty-hidden">
                    <input type="hidden" name="ngs[${newNgIndex}][ng_name]" value="${uppercaseName}">
                    <input type="hidden" name="ngs[${newNgIndex}][total_ng]" id="ng-total-hidden-${newNgIndex}" value="0">
                </div>
            `;
            headerRow.appendChild(th);

            // 2. Add columns to rows
            const rows = document.querySelectorAll('.production-row');
            rows.forEach(row => {
                const hour = row.dataset.hour;
                const td = document.createElement('td');
                td.className = 'px-1.5 py-1.5 ng-cell transition-colors duration-100';
                td.dataset.ng = uppercaseName;
                td.innerHTML = `
                    <input type="number" inputmode="numeric" name="ngs[${newNgIndex}][hours][${hour}]" class="w-full text-center text-sm rounded-md border-transparent hover:border-gray-300 focus:border-red-500 focus:ring-red-500 py-2 bg-transparent hover:bg-white focus:bg-white transition-all ng-hourly-input" data-hour="${hour}" data-ng-index="${newNgIndex}" placeholder="-">
                `;
                row.appendChild(td);
            });

            calculateNgTotals();
        });

        // Event delegation for deleting NG columns
        document.getElementById('production-header-row').addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-ng-col-btn');
            if (deleteBtn) {
                const ngName = deleteBtn.dataset.ng;
                deleteNgType(ngName);
            }
        });


        function deleteNgType(ngName) {
            const countWithValues = Array.from(document.querySelectorAll(
                    `.ng-cell[data-ng="${ngName}"] input.ng-hourly-input`))
                .reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);

            const confirmMsg = countWithValues > 0 ?
                `Tipe NG "${ngName}" memiliki total input sebanyak ${countWithValues}. Apakah Anda yakin ingin menghapus tipe NG ini beserta seluruh datanya?` :
                `Apakah Anda yakin ingin menghapus Tipe NG "${ngName}"?`;

            if (!confirm(confirmMsg)) return;

            // 1. Remove from activeNgs array
            const index = activeNgs.indexOf(ngName);
            if (index > -1) {
                activeNgs.splice(index, 1);
            }

            // 2. Remove header element
            const th = document.querySelector(`.ng-type-header[data-ng="${ngName}"]`);
            if (th) th.remove();

            // 3. Remove cells from rows
            document.querySelectorAll(`.ng-cell[data-ng="${ngName}"]`).forEach(td => td.remove());

            // 5. Re-index and calculate
            reindexNgs();
            calculateNgTotals();
        }

        function reindexNgs() {
            activeNgs.forEach((ng, newIndex) => {
                document.querySelectorAll(`.ng-cell[data-ng="${ng}"] input.ng-hourly-input`).forEach(
                    input => {
                        const hour = input.dataset.hour;
                        input.name = `ngs[${newIndex}][hours][${hour}]`;
                        input.dataset.ngIndex = newIndex;
                    });

                const th = document.querySelector(`.ng-type-header[data-ng="${ng}"]`);
                if (th) {
                    const nameInput = th.querySelector('input[name$="[ng_name]"]');
                    if (nameInput) nameInput.name = `ngs[${newIndex}][ng_name]`;

                    const totalHidden = th.querySelector('input[id^="ng-total-hidden-"]');
                    if (totalHidden) {
                        totalHidden.name = `ngs[${newIndex}][total_ng]`;
                        totalHidden.id = `ng-total-hidden-${newIndex}`;
                    }

                    const itemInput = th.querySelector('input[name$="[ng_input_item]"]');
                    if (itemInput) itemInput.name = `ngs[${newIndex}][ng_input_item]`;

                    const qtyInput = th.querySelector('input[name$="[ng_input_qty]"]');
                    if (qtyInput) qtyInput.name = `ngs[${newIndex}][ng_input_qty]`;

                    const remarkBtn = th.querySelector('.ng-remark-btn');
                    if (remarkBtn) remarkBtn.setAttribute('data-ng-index', newIndex);
                }
            });
        }

        // 4. Calculations
        const totalOkField = document.getElementById('jumlah_ok');
        const totalNgField = document.getElementById('jumlah_ng');
        const totalOutputField = document.getElementById('jumlah_output');
        const ngPercentageField = document.getElementById('ng_prosentase');

        // ponytail: simple column and row summation
        function calculateHourlyAccumulation() {
            const rows = document.querySelectorAll('.production-row');
            let accumulated = 0;
            rows.forEach(row => {
                const okInput = row.querySelector('.hourly-ok-input');
                const accumInput = row.querySelector('.hourly-accum-input');
                const val = parseInt(okInput.value) || 0;
                accumulated += val;
                if (accumInput) {
                    accumInput.value = accumulated > 0 ? accumulated : '';
                }
            });
            totalOkField.value = accumulated;
            calculateSummaryTotals();
        }

        function calculateNgTotals() {
            const rows = document.querySelectorAll('.production-row');
            let overallNg = 0;

            // Sum by row (hourly total)
            rows.forEach(row => {
                const rowInputs = row.querySelectorAll('.ng-hourly-input');
                const rowTotalField = row.querySelector('.hourly-ng-total-input');
                let rowTotal = 0;
                rowInputs.forEach(input => {
                    rowTotal += parseInt(input.value) || 0;
                });
                if (rowTotalField) {
                    rowTotalField.value = rowTotal > 0 ? rowTotal : '';
                }
                overallNg += rowTotal;
            });

            // Sum by column (NG type total) to update hidden fields & remark button state
            activeNgs.forEach((ng, index) => {
                const colInputs = document.querySelectorAll(
                    `.ng-hourly-input[data-ng-index="${index}"]`);
                let colTotal = 0;
                colInputs.forEach(input => {
                    colTotal += parseInt(input.value) || 0;
                });
                const hiddenTotal = document.getElementById(`ng-total-hidden-${index}`);
                if (hiddenTotal) {
                    hiddenTotal.value = colTotal;
                }
                const th = document.querySelector(`th.ng-type-header[data-ng="${ng}"]`) || (hiddenTotal ? hiddenTotal.closest('th') : null);
                if (th && typeof updateNgRemarkButtonState === 'function') {
                    updateNgRemarkButtonState(th, colTotal);
                }
            });

            totalNgField.value = overallNg;
            calculateSummaryTotals();
        }

        function calculateSummaryTotals() {
            const totalOk = parseInt(totalOkField.value) || 0;
            const totalNg = parseInt(totalNgField.value) || 0;
            const ngLeburField = document.getElementById('jml_ng_lebur');
            const totalScrap = ngLeburField ? (parseInt(ngLeburField.value) || 0) : 0;
            const totalOutput = totalOk + totalNg + totalScrap;

            totalOutputField.value = totalOutput;

            if (totalOutput > 0) {
                const pct = (totalNg / totalOutput) * 100;
                ngPercentageField.value = pct.toFixed(2);
            } else {
                ngPercentageField.value = '0.00';
            }

            const ngPctDisplay = document.getElementById('ng_prosentase_display');
            if (ngPctDisplay) {
                ngPctDisplay.textContent = ngPercentageField.value;
            }

            validateTotals();
            updateMaterialReconciliation();
        }

        function validateTotals() {
            const totalOk = parseInt(totalOkField.value) || 0;
            const totalNg = parseInt(totalNgField.value) || 0;
            const ngLeburField = document.getElementById('jml_ng_lebur');
            const totalScrap = ngLeburField ? (parseInt(ngLeburField.value) || 0) : 0;
            const expectedOutput = totalOk + totalNg + totalScrap;
            const inputOutput = parseInt(totalOutputField.value) || 0;
            const validationMessage = document.getElementById('totals-validation-message');

            if (inputOutput > 0 && (expectedOutput !== inputOutput)) {
                validationMessage.innerHTML = `
        <div class="p-3 bg-yellow-50 text-yellow-800 border border-yellow-200 rounded text-xs flex items-center shadow-sm">
            <svg class="w-4 h-4 mr-2 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span><strong>Peringatan Validasi:</strong> Total OK (${totalOk}) + Total NG (${totalNg}) + Scrap (${totalScrap}) = ${expectedOutput}, tidak sama dengan Jumlah Output (${inputOutput}). Silakan periksa kembali input hasil produksi Anda.</span>
        </div>
    `;
            } else {
                validationMessage.innerHTML = '';
            }
        }

        function updateMaterialReconciliation() {
            const wipField = document.getElementById('jml_input_wip');
            const repField = document.getElementById('repairan');
            const totalOkField = document.getElementById('jumlah_ok');
            const totalNgField = document.getElementById('jumlah_ng');
            const ngLeburField = document.getElementById('jml_ng_lebur');
            const sisaInputField = document.getElementById('sisa_input');

            const wip = wipField ? (parseInt(wipField.value) || 0) : 0;
            const rep = repField ? (parseInt(repField.value) || 0) : 0;
            const totalInput = wip + rep;

            const totalOk = totalOkField ? (parseInt(totalOkField.value) || 0) : 0;
            const totalNg = totalNgField ? (parseInt(totalNgField.value) || 0) : 0;
            const scrap = ngLeburField ? (parseInt(ngLeburField.value) || 0) : 0;
            const totalOutput = totalOk + totalNg + scrap;

            const sisa = totalInput - totalOutput;

            if (sisaInputField) {
                sisaInputField.value = sisa;
            }

            // Update badge / text numbers
            const totalInputLabel = document.getElementById('reconcile-total-input');
            const totalOutputLabel = document.getElementById('reconcile-total-output');
            const sisaLabel = document.getElementById('reconcile-sisa-qty');
            const wipLabel = document.getElementById('reconcile-wip-qty');
            const repLabel = document.getElementById('reconcile-repairan-qty');
            const okLabel = document.getElementById('reconcile-ok-qty');
            const ngLabel = document.getElementById('reconcile-ng-qty');
            const scrapLabel = document.getElementById('reconcile-scrap-qty');
            const balancePill = document.getElementById('reconcile-balance-pill');
            const statusBadge = document.getElementById('reconcile-status-badge');
            const inlineNote = document.getElementById('reconcile-inline-note');

            if (totalInputLabel) totalInputLabel.textContent = totalInput.toLocaleString();
            if (totalOutputLabel) totalOutputLabel.textContent = totalOutput.toLocaleString();
            if (sisaLabel) sisaLabel.textContent = Math.abs(sisa).toLocaleString();
            if (wipLabel) wipLabel.textContent = wip.toLocaleString();
            if (repLabel) repLabel.textContent = rep.toLocaleString();
            if (okLabel) okLabel.textContent = totalOk.toLocaleString();
            if (ngLabel) ngLabel.textContent = totalNg.toLocaleString();
            if (scrapLabel) scrapLabel.textContent = scrap.toLocaleString();

            // Update balance pill styling preserving grid layout classes
            const pillBaseClasses = 'p-3.5 sm:p-4 rounded-xl border flex flex-col justify-between space-y-2 transition-colors duration-200 ';
            if (balancePill) {
                balancePill.className = pillBaseClasses +
                    (totalInput < totalOutput ? 'bg-red-50 border-red-300 text-red-900' :
                    (sisa > 0 ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900'));
            }

            // Update status badge & inline note
            if (statusBadge) {
                if (totalInput < totalOutput) {
                    statusBadge.className = 'text-xs font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider border bg-red-200 text-red-900 border-red-300';
                    statusBadge.textContent = '⛔ Defisit';
                } else if (sisa > 0) {
                    statusBadge.className = 'text-xs font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider border bg-amber-200 text-amber-900 border-amber-300';
                    statusBadge.textContent = '⚠️ Ada Sisa';
                } else {
                    statusBadge.className = 'text-xs font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider border bg-emerald-200 text-emerald-900 border-emerald-300';
                    statusBadge.textContent = '✓ Ideal';
                }
            }

            if (inlineNote) {
                if (totalInput < totalOutput) {
                    inlineNote.textContent = 'Output melebihi Input! Submission diblokir.';
                } else if (sisa > 0) {
                    inlineNote.textContent = 'Ada sisa input. Remark wajib diisi.';
                } else {
                    inlineNote.textContent = 'Material seimbang (Ideal).';
                }
            }

            // Dynamic State Indicators (Deficit banner & Remark container)
            const stateDeficit = document.getElementById('reconcile-state-deficit');
            const remarkContainer = document.getElementById('reconcile-remark-container');

            if (totalInput < totalOutput) {
                if (stateDeficit) {
                    stateDeficit.classList.remove('hidden');
                    const defAmount = totalOutput - totalInput;
                    const defSpan = stateDeficit.querySelector('.reconcile-deficit-amount');
                    if (defSpan) defSpan.textContent = defAmount.toLocaleString();
                }
                if (remarkContainer) {
                    remarkContainer.classList.add('hidden');
                }
            } else if (sisa > 0) {
                if (stateDeficit) {
                    stateDeficit.classList.add('hidden');
                }
                if (remarkContainer) {
                    remarkContainer.classList.remove('hidden');
                }
            } else {
                if (stateDeficit) {
                    stateDeficit.classList.add('hidden');
                }
                const remarkInput = document.getElementById('sisa_input_remark');
                if (remarkContainer && (!remarkInput || !remarkInput.value.trim())) {
                    remarkContainer.classList.add('hidden');
                }
            }
        }
        window.updateMaterialReconciliation = updateMaterialReconciliation;

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('hourly-ok-input')) {
                calculateHourlyAccumulation();
            }
            if (e.target.classList.contains('ng-hourly-input')) {
                calculateNgTotals();
                if (e.target.value && e.target.value > 0) {
                    e.target.classList.add('bg-red-50', 'text-red-700', 'font-bold', 'shadow-inner');
                } else {
                    e.target.classList.remove('bg-red-50', 'text-red-700', 'font-bold', 'shadow-inner');
                }
            }
            if (e.target.id === 'jml_ng_lebur') {
                calculateSummaryTotals();
            }
        });
        document.addEventListener('change', function(e) {
            if (e.target.id === 'jml_ng_lebur') {
                calculateSummaryTotals();
            }
        });

        // Grid Navigation and Crosshair Highlight for Unified Table
        const prodTbody = document.getElementById('production-tbody');
        if (prodTbody) {
            prodTbody.addEventListener('keydown', function(e) {
                if (!e.target.classList.contains('ng-hourly-input') && !e.target.classList.contains(
                        'hourly-ok-input')) return;

                const currentTd = e.target.closest('td');
                const currentRow = e.target.closest('tr');
                let nextInput = null;

                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevRow = currentRow.previousElementSibling;
                    if (prevRow) {
                        const colIndex = Array.from(currentTd.parentNode.children).indexOf(currentTd);
                        const targetTd = prevRow.children[colIndex];
                        if (targetTd) nextInput = targetTd.querySelector('input:not([type="hidden"])');
                    }
                } else if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    e.preventDefault();
                    const nextRow = currentRow.nextElementSibling;
                    if (nextRow) {
                        const colIndex = Array.from(currentTd.parentNode.children).indexOf(currentTd);
                        const targetTd = nextRow.children[colIndex];
                        if (targetTd) nextInput = targetTd.querySelector('input:not([type="hidden"])');
                    }
                } else if (e.key === 'ArrowLeft' && e.target.selectionStart === 0) {
                    let prevTd = currentTd.previousElementSibling;
                    while (prevTd && (prevTd.querySelector('input[readonly]') || !prevTd.querySelector(
                            'input'))) {
                        prevTd = prevTd.previousElementSibling;
                    }
                    if (prevTd) {
                        e.preventDefault();
                        nextInput = prevTd.querySelector('input');
                    }
                } else if (e.key === 'ArrowRight' && e.target.selectionStart === e.target.value
                    .length) {
                    let nextTd = currentTd.nextElementSibling;
                    while (nextTd && (nextTd.querySelector('input[readonly]') || !nextTd.querySelector(
                            'input'))) {
                        nextTd = nextTd.nextElementSibling;
                    }
                    if (nextTd) {
                        e.preventDefault();
                        nextInput = nextTd.querySelector('input');
                    }
                }

                if (nextInput) {
                    nextInput.focus();
                    nextInput.select();
                }
            });

            prodTbody.addEventListener('focusin', function(e) {
                const isNg = e.target.classList.contains('ng-hourly-input');
                const isOk = e.target.classList.contains('hourly-ok-input');
                if (!isNg && !isOk) return;

                const currentTd = e.target.closest('td');
                const colIndex = Array.from(currentTd.parentNode.children).indexOf(currentTd);

                // Highlight header
                const headers = document.querySelectorAll('#production-header-row th');
                headers.forEach(th => th.classList.remove('bg-red-100', 'text-red-800', 'border-b-2',
                    'border-red-500'));

                const header = headers[colIndex];
                if (header && isNg) {
                    header.classList.add('bg-red-100', 'text-red-800', 'border-b-2', 'border-red-500');
                }

                // Highlight column cells
                document.querySelectorAll(`.production-row`).forEach(row => {
                    const cell = row.children[colIndex];
                    if (cell && isNg) cell.classList.add('bg-red-50');
                });

                e.target.closest('tr').classList.add('bg-blue-100/50');
            });

            prodTbody.addEventListener('focusout', function(e) {
                const isNg = e.target.classList.contains('ng-hourly-input');
                const isOk = e.target.classList.contains('hourly-ok-input');
                if (!isNg && !isOk) return;

                const currentTd = e.target.closest('td');
                const colIndex = Array.from(currentTd.parentNode.children).indexOf(currentTd);

                const headers = document.querySelectorAll('#production-header-row th');
                const header = headers[colIndex];
                if (header) {
                    header.classList.remove('bg-red-100', 'text-red-800', 'border-b-2',
                        'border-red-500');
                }

                document.querySelectorAll(`.production-row`).forEach(row => {
                    const cell = row.children[colIndex];
                    if (cell) cell.classList.remove('bg-red-50');
                });

                e.target.closest('tr').classList.remove('bg-blue-100/50');
            });
        }

        // Defect Remark Preset Categories & State Handling
        const ngPresetCategories = {!! json_encode(config('mes.sp_ng_remark_categories')) !!};
        const remarkModal = document.getElementById('ng-remark-modal');
        const modalTitle = document.getElementById('modal-ng-title');
        const saveModalBtn = document.getElementById('save-modal-remark-btn');
        let activeRemarkButton = null;

        // Centralized Remark Button UI State Handler
        function updateNgRemarkButtonState(th, colTotal) {
            if (!th) return;
            const btn = th.querySelector('.ng-remark-btn');
            if (!btn) return;
            const previewLabel = btn.querySelector('.remark-preview-label');
            const itemHidden = th.querySelector('.ng-input-item-hidden');
            const qtyHidden = th.querySelector('.ng-input-qty-hidden');

            const itemVal = itemHidden ? itemHidden.value.trim() : '';
            const qtyVal = qtyHidden && qtyHidden.value !== '' ? parseInt(qtyHidden.value) : 0;

            // Reset dynamic classes
            btn.classList.remove(
                'bg-gray-50', 'border-gray-200', 'text-gray-500', 'hover:bg-blue-50', 'hover:text-blue-700', 'font-semibold',
                'bg-blue-50', 'border-blue-200', 'text-blue-700', 'hover:bg-blue-100', 'font-bold',
                'bg-red-50', 'border-red-300', 'text-red-700', 'hover:bg-red-100', 'animate-pulse',
                'bg-amber-50', 'border-amber-300', 'text-amber-800', 'hover:bg-amber-100'
            );

            if (colTotal === 0) {
                btn.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-500', 'hover:bg-blue-50', 'hover:text-blue-700', 'font-semibold');
                if (previewLabel) previewLabel.textContent = 'Add Remark';
            } else if (!itemVal || qtyVal === 0) {
                // Total NG > 0, but no remark allocated
                btn.classList.add('bg-red-50', 'border-red-300', 'text-red-700', 'hover:bg-red-100', 'font-bold', 'animate-pulse');
                if (previewLabel) previewLabel.textContent = `Remark Needed (0/${colTotal})`;
            } else if (qtyVal !== colTotal) {
                // Total NG > 0, but remark qty mismatch
                btn.classList.add('bg-amber-50', 'border-amber-300', 'text-amber-800', 'hover:bg-amber-100', 'font-bold');
                if (previewLabel) previewLabel.textContent = `Needs Update (${qtyVal}/${colTotal})`;
            } else {
                // Total NG > 0 and perfectly matched!
                btn.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700', 'hover:bg-blue-100', 'font-bold');
                let pretty = itemVal
                    .replace(/\[(\d+)\]\s*([^\|]+)/g, '$1x $2')
                    .replace(/\s*\|\s*/g, ', ');
                if (previewLabel) previewLabel.textContent = `${pretty} (${qtyVal})`;
            }
        }

        // Live Balance Tracker inside Modal
        function updateModalBalance() {
            if (!activeRemarkButton) return;
            const colIndex = activeRemarkButton.getAttribute('data-ng-index');
            const hiddenTotal = document.getElementById(`ng-total-hidden-${colIndex}`);
            const targetNg = hiddenTotal ? (parseInt(hiddenTotal.value) || 0) : 0;

            const rows = document.querySelectorAll('.modal-row');
            let allocated = 0;

            rows.forEach(row => {
                const qtyInput = row.querySelector('.modal-row-qty');
                const qtyVal = qtyInput && qtyInput.value !== '' ? (parseInt(qtyInput.value) || 0) : 0;
                allocated += qtyVal;
            });

            const targetEl = document.getElementById('modal-target-ng');
            const allocatedEl = document.getElementById('modal-allocated-ng');
            const badgeEl = document.getElementById('modal-balance-badge');
            const bannerEl = document.getElementById('modal-balance-banner');
            const hintEl = document.getElementById('modal-validation-hint');

            if (targetEl) targetEl.textContent = targetNg;
            if (allocatedEl) allocatedEl.textContent = allocated;

            const remaining = targetNg - allocated;

            if (targetNg === 0) {
                if (badgeEl) {
                    badgeEl.className = 'px-2 py-0.5 rounded font-extrabold text-[11px] bg-gray-100 text-gray-700';
                    badgeEl.textContent = 'No Defect (0)';
                }
                if (bannerEl) bannerEl.className = 'p-3 rounded-lg border bg-gray-50 border-gray-200 text-xs flex items-center justify-between transition-colors';
                saveModalBtn.disabled = false;
                saveModalBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                if (hintEl) hintEl.classList.add('hidden');
            } else if (remaining === 0 && allocated > 0) {
                if (badgeEl) {
                    badgeEl.className = 'px-2.5 py-0.5 rounded-full font-extrabold text-[11px] bg-emerald-100 text-emerald-800 border border-emerald-300';
                    badgeEl.textContent = 'Matched (0 remaining)';
                }
                if (bannerEl) bannerEl.className = 'p-3 rounded-lg border bg-emerald-50/50 border-emerald-200 text-xs flex items-center justify-between transition-colors';
                saveModalBtn.disabled = false;
                saveModalBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                if (hintEl) hintEl.classList.add('hidden');
            } else if (remaining > 0) {
                if (badgeEl) {
                    badgeEl.className = 'px-2.5 py-0.5 rounded-full font-extrabold text-[11px] bg-amber-100 text-amber-800 border border-amber-300';
                    badgeEl.textContent = `Remaining: ${remaining} pcs`;
                }
                if (bannerEl) bannerEl.className = 'p-3 rounded-lg border bg-amber-50/50 border-amber-200 text-xs flex items-center justify-between transition-colors';
                saveModalBtn.disabled = true;
                saveModalBtn.classList.add('opacity-50', 'cursor-not-allowed');
                if (hintEl) {
                    hintEl.textContent = `Harap alokasikan sisa ${remaining} pcs defect agar total sesuai target (${targetNg} pcs).`;
                    hintEl.classList.remove('hidden');
                }
            } else {
                const over = Math.abs(remaining);
                if (badgeEl) {
                    badgeEl.className = 'px-2.5 py-0.5 rounded-full font-extrabold text-[11px] bg-red-100 text-red-800 border border-red-300';
                    badgeEl.textContent = `Exceeds Target: +${over} pcs`;
                }
                if (bannerEl) bannerEl.className = 'p-3 rounded-lg border bg-red-50/50 border-red-200 text-xs flex items-center justify-between transition-colors';
                saveModalBtn.disabled = true;
                saveModalBtn.classList.add('opacity-50', 'cursor-not-allowed');
                if (hintEl) {
                    hintEl.textContent = `Kuantitas melebihi target defect sebesar ${over} pcs. Harap kurangi alokasi.`;
                    hintEl.classList.remove('hidden');
                }
            }
        }

        // Add row to Defect Detail modal
        function addModalRow(item = '', qty = '') {
            const container = document.getElementById('modal-rows-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2 modal-row';

            const trimmedItem = (item || '').trim();
            const normalizedUpper = trimmedItem.replace(/[\s_]+/g, '-').toUpperCase();

            let isPreset = false;
            let selectedType = '__custom__';
            let customVal = trimmedItem;

            if (normalizedUpper === 'NG-INPUT' || normalizedUpper === 'INPUT') {
                selectedType = 'NG-INPUT';
                isPreset = true;
                customVal = '';
            } else if (normalizedUpper === 'NG-PROSES' || normalizedUpper === 'PROSES') {
                selectedType = 'NG-PROSES';
                isPreset = true;
                customVal = '';
            }

            let optionsHtml = '';
            for (const [key, label] of Object.entries(ngPresetCategories)) {
                const sel = selectedType === key ? 'selected' : '';
                optionsHtml += `<option value="${key}" ${sel}>${label}</option>`;
            }
            optionsHtml += `<option value="__custom__" ${!isPreset ? 'selected' : ''}>Custom...</option>`;

            div.innerHTML = `
                <select class="w-36 rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 px-2 modal-row-type">
                    ${optionsHtml}
                </select>
                <input type="text" class="flex-1 rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 px-2 modal-row-item-custom ${isPreset ? 'hidden' : ''}" placeholder="Kategori custom..." value="${escapeHtml(customVal)}">
                <input type="number" min="1" class="w-20 rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 px-2 modal-row-qty text-center font-bold" placeholder="Qty" value="${qty}">
                <button type="button" class="text-red-500 hover:text-red-700 font-bold delete-row-btn text-base px-1" title="Delete row">&times;</button>
            `;
            container.appendChild(div);

            const select = div.querySelector('.modal-row-type');
            const customInput = div.querySelector('.modal-row-item-custom');
            const qtyInput = div.querySelector('.modal-row-qty');

            select.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    customInput.classList.remove('hidden');
                    customInput.focus();
                } else {
                    customInput.classList.add('hidden');
                    customInput.value = '';
                }
                updateModalBalance();
            });

            customInput.addEventListener('input', updateModalBalance);
            qtyInput.addEventListener('input', updateModalBalance);

            updateModalBalance();
        }

        // Helper to escape HTML tags in strings
        function escapeHtml(str) {
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Manage row removal using event delegation
        document.getElementById('modal-rows-container').addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-row-btn');
            if (deleteBtn) {
                const row = deleteBtn.closest('.modal-row');
                const allRows = document.querySelectorAll('.modal-row');
                if (allRows.length > 1) {
                    row.remove();
                } else {
                    const sel = row.querySelector('.modal-row-type');
                    const custom = row.querySelector('.modal-row-item-custom');
                    const qty = row.querySelector('.modal-row-qty');
                    if (sel) sel.value = 'NG-INPUT';
                    if (custom) { custom.value = ''; custom.classList.add('hidden'); }
                    if (qty) qty.value = '';
                }
                updateModalBalance();
            }
        });

        // Add row button listener
        document.getElementById('add-modal-row-btn').addEventListener('click', function() {
            addModalRow('', '');
        });

        // Click event using event delegation for dynamic headers
        document.getElementById('production-header-row').addEventListener('click', function(e) {
            const btn = e.target.closest('.ng-remark-btn');
            if (!btn) return;

            activeRemarkButton = btn;
            const ngName = btn.getAttribute('data-ng-name');
            const th = btn.closest('th');

            const itemHidden = th.querySelector('.ng-input-item-hidden');
            const qtyHidden = th.querySelector('.ng-input-qty-hidden');

            modalTitle.textContent = `Defect Detail: ${ngName}`;

            // Clear existing rows
            document.getElementById('modal-rows-container').innerHTML = '';

            const rawItem = itemHidden.value.trim();
            const rawQty = qtyHidden.value.trim();

            if (rawItem) {
                // Parse structured items like [8] X | [3] Y
                const parts = rawItem.split(' | ');
                let parsedAny = false;

                parts.forEach(part => {
                    const match = part.match(/^\[(\d+)\]\s*(.*)$/);
                    if (match) {
                        addModalRow(match[2], match[1]);
                        parsedAny = true;
                    }
                });

                if (!parsedAny) {
                    addModalRow(rawItem, rawQty);
                }
            } else {
                // Default to one empty row
                addModalRow('', '');
            }

            updateModalBalance();
            remarkModal.showModal();
        });

        // Close handlers
        const closeModal = () => {
            remarkModal.close();
            activeRemarkButton = null;
        };

        remarkModal.querySelectorAll('.close-modal-btn').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        remarkModal.addEventListener('click', function(e) {
            if (e.target === remarkModal) {
                closeModal();
            }
        });

        // Save details with Uppercase Normalization & Consolidation
        saveModalBtn.addEventListener('click', function() {
            if (!activeRemarkButton) return;

            const th = activeRemarkButton.closest('th');
            const itemHidden = th.querySelector('.ng-input-item-hidden');
            const qtyHidden = th.querySelector('.ng-input-qty-hidden');

            const rows = document.querySelectorAll('.modal-row');
            const categoryMap = {}; // Normalized UPPERCASE category -> total qty

            rows.forEach(row => {
                const typeVal = row.querySelector('.modal-row-type').value;
                const customVal = row.querySelector('.modal-row-item-custom').value.trim();
                const qtyVal = parseInt(row.querySelector('.modal-row-qty').value) || 0;

                let categoryName = '';
                if (typeVal === '__custom__') {
                    categoryName = customVal.replace(/[\s_]+/g, '-').toUpperCase();
                } else {
                    categoryName = typeVal.replace(/[\s_]+/g, '-').toUpperCase();
                }

                if (categoryName && qtyVal > 0) {
                    if (!categoryMap[categoryName]) {
                        categoryMap[categoryName] = 0;
                    }
                    categoryMap[categoryName] += qtyVal;
                }
            });

            let serializedParts = [];
            let totalQty = 0;
            for (const [catName, catQty] of Object.entries(categoryMap)) {
                totalQty += catQty;
                serializedParts.push(`[${catQty}] ${catName}`);
            }

            const finalItemVal = serializedParts.join(' | ');

            // Save values back to hidden inputs
            itemHidden.value = finalItemVal;
            qtyHidden.value = totalQty > 0 ? totalQty : '';

            // Update button UI state
            const hiddenTotal = document.getElementById(`ng-total-hidden-${activeRemarkButton.getAttribute('data-ng-index')}`);
            const colTotal = hiddenTotal ? (parseInt(hiddenTotal.value) || 0) : 0;
            updateNgRemarkButtonState(th, colTotal);

            closeModal();
        });

        // Initial Calculations
        calculateHourlyAccumulation();
        calculateNgTotals();
    });
</script>

<!-- Manpower Script -->
<script>
    let manpowerIndex = document.querySelectorAll('.manpower-row').length;

    function toggleCustomRole(select, index) {
        const input = select.nextElementSibling;
        if (select.value === '__custom__') {
            input.classList.remove('hidden');
            input.removeAttribute('readonly');
            input.value = ''; // clear previous value
            input.focus();
        } else {
            input.classList.add('hidden');
            input.setAttribute('readonly', 'readonly');
            input.value = select.value;
        }
    }

    function addManpowerRow() {
        const tbody = document.getElementById('manpower-tbody');
        const rows = tbody.querySelectorAll('.manpower-row');
        const nextNo = rows.length + 1;

        const tr = document.createElement('tr');
        tr.className = 'manpower-row';
        tr.innerHTML = `
            <td class="px-4 py-2 text-center font-bold text-gray-500 mp-no">${nextNo}</td>
            <td class="px-4 py-2">
                <input type="hidden" name="manpower[${manpowerIndex}][no]" class="mp-no-input" value="${nextNo}">
                <select class="w-full text-xs rounded border-gray-300 py-1 mb-1 role-select" onchange="toggleCustomRole(this, ${manpowerIndex})">
                    @foreach(config('mes.sp_manpower_roles') as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                    @endforeach
                    <option value="__custom__">Other (custom)...</option>
                </select>
                <input type="text" name="manpower[${manpowerIndex}][role]" value="loading" class="w-full text-xs rounded border-gray-300 py-1 role-input hidden" placeholder="Type custom role..." readonly>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="manpower[${manpowerIndex}][name]" placeholder="Operator Name" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="removeManpowerRow(this)" class="text-red-500 hover:text-red-700 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        manpowerIndex++;
        updateManpowerNumbers();
    }

    function removeManpowerRow(btn) {
        const tr = btn.closest('tr');
        tr.remove();
        updateManpowerNumbers();
    }

    function updateManpowerNumbers() {
        const rows = document.querySelectorAll('.manpower-row');
        rows.forEach((row, index) => {
            const no = index + 1;
            row.querySelector('.mp-no').textContent = no;
            row.querySelector('.mp-no-input').value = no;
        });
    }

    // IPQC Measurement Toggles
    document.querySelectorAll('.meas-toggle-cb').forEach(cb => {
        cb.addEventListener('change', function() {
            const key = this.dataset.key;
            const cols = document.querySelectorAll('.meas-col-' + key);
            cols.forEach(col => {
                if (this.checked) {
                    col.classList.remove('hidden');
                } else {
                    col.classList.add('hidden');
                }
            });
        });
    });

    // IPQC Reject Rate Auto Calculation
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('ipqc-sample-input') || e.target.classList.contains(
                'ipqc-rejsample-input')) {
            const tr = e.target.closest('tr');
            if (tr) {
                const sample = parseFloat(tr.querySelector('.ipqc-sample-input')?.value || 0);
                const rejSample = parseFloat(tr.querySelector('.ipqc-rejsample-input')?.value || 0);
                const rejRateCell = tr.querySelector('.ipqc-rejrate-cell');
                if (rejRateCell) {
                    const rate = sample > 0 ? ((rejSample / sample) * 100).toFixed(2) : 0;
                    rejRateCell.textContent = rate + '%';
                }
            }
        }
    });

    // First Piece Live Gate Check
    function checkFirstPieceGate() {
        const partInput = document.querySelector('input[name="part_number"]');
        const dateInput = document.querySelector('input[name="date"]');
        const partNameInput = document.querySelector('input[name="part_name"]');
        const modelInput = document.querySelector('input[name="model"]');

        const banner = document.getElementById('first-piece-gate-banner');
        const icon = document.getElementById('first-piece-gate-icon');
        const text = document.getElementById('first-piece-gate-text');
        const action = document.getElementById('first-piece-gate-action');

        if (!partInput || !dateInput || !text) return;

        const partNumber = partInput.value.trim();
        const date = dateInput.value.trim();
        const partName = partNameInput ? encodeURIComponent(partNameInput.value.trim()) : '';
        const model = modelInput ? encodeURIComponent(modelInput.value.trim()) : '';

        if (!partNumber || !date) {
            text.innerHTML =
                '<span class="text-gray-500">Please enter Date and Part Number in Tab 1 to check First Piece gate status.</span>';
            action.innerHTML = '';
            return;
        }

        fetch(
                `/first-piece-inspections/check-approval?part_number=${encodeURIComponent(partNumber)}&date=${encodeURIComponent(date)}`
            )
            .then(res => res.json())
            .then(data => {
                if (data.approved) {
                    banner.className =
                        'bg-green-50 border border-green-300 p-4 rounded-lg flex flex-col md:flex-row justify-between items-center gap-4';
                    icon.className = 'p-2 rounded-full bg-green-200 text-green-700';
                    text.innerHTML =
                        `<span class="text-green-800 font-bold">✅ APPROVED</span> — Checked by <strong>${data.inspection.checked_by || 'QC'}</strong> on ${data.inspection.checked_at || ''}`;
                    action.innerHTML =
                        `<a href="/first-piece-inspections/${data.inspection.id}" target="_blank" class="bg-green-700 hover:bg-green-800 text-white text-xs font-bold py-1.5 px-4 rounded shadow transition inline-block">View First Piece Inspection &rarr;</a>`;
                } else {
                    banner.className =
                        'bg-amber-50 border border-amber-300 p-4 rounded-lg flex flex-col md:flex-row justify-between items-center gap-4';
                    icon.className = 'p-2 rounded-full bg-amber-200 text-amber-700';
                    text.innerHTML =
                        `<span class="text-amber-800 font-bold">⚠️ NOT YET APPROVED</span> — First Piece Inspection for part <strong>${partNumber}</strong> on ${date} is pending or missing.`;
                    action.innerHTML =
                        `<a href="/first-piece-inspections/create?part_number=${encodeURIComponent(partNumber)}&date=${encodeURIComponent(date)}&part_name=${partName}&model=${model}" target="_blank" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-1.5 px-4 rounded shadow transition inline-block">+ Create First Piece Inspection &rarr;</a>`;
                }
            })
            .catch(err => {
                console.error('Error checking First Piece gate:', err);
            });
    }

    const partInputEl = document.querySelector('input[name="part_number"]');
    const dateInputEl = document.querySelector('input[name="date"]');
    if (partInputEl) partInputEl.addEventListener('change', checkFirstPieceGate);
    if (dateInputEl) dateInputEl.addEventListener('change', checkFirstPieceGate);

    // Initial check on load
    setTimeout(checkFirstPieceGate, 300);

    // Tab 2 Materials Dynamic Rows Logic
    let materialRowIndex = {{ $materialGlobalIndex ?? 100 }};

    window.addPaintMaterialRow = function() {
        const processSelect = document.getElementById('process_prod');
        if (processSelect && !['Painting', 'Repair'].includes(processSelect.value)) {
            return;
        }
        const tbody = document.getElementById('paint-materials-tbody');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2">
                <input type="hidden" name="materials[${materialRowIndex}][type]" value="paint">
                <input type="text" name="materials[${materialRowIndex}][item_name]" value="" placeholder="Paint Item Name" class="w-full text-xs rounded border-gray-300 py-1 font-semibold">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][lot_number]" value="" placeholder="Lot" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][visco]" value="" placeholder="Visco" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][mixing_ratio]" value="" placeholder="Ratio (e.g. 1:1.5)" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="number" step="any" name="materials[${materialRowIndex}][qty]" value="" placeholder="Qty" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][uom]" value="" placeholder="UOM" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-1 py-2 text-center">
                <button type="button" onclick="removeMaterialRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1.5 py-0.5 text-sm rounded hover:bg-red-50 transition" title="Remove Row">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
        materialRowIndex++;
    };

    function togglePaintMaterialsByProcess() {
        const processSelect = document.getElementById('process_prod');
        const isPaintApplicable = processSelect ? ['Painting', 'Repair'].includes(processSelect.value) : true;

        const paintCard = document.getElementById('paint-materials-card');
        const paintBadge = document.getElementById('paint-process-badge');
        const paintNotice = document.getElementById('paint-disabled-notice');
        const addPaintBtn = document.getElementById('add-paint-item-btn');
        const paintTbody = document.getElementById('paint-materials-tbody');

        if (!paintCard) return;

        if (isPaintApplicable) {
            paintCard.classList.remove('opacity-60', 'bg-gray-50');
            if (paintBadge) paintBadge.classList.add('hidden');
            if (paintNotice) paintNotice.classList.add('hidden');
            if (addPaintBtn) {
                addPaintBtn.disabled = false;
                addPaintBtn.classList.remove('cursor-not-allowed', 'opacity-50');
            }
            if (paintTbody) {
                paintTbody.querySelectorAll('input, button').forEach(function(el) {
                    el.disabled = false;
                    el.classList.remove('bg-gray-100', 'cursor-not-allowed');
                });
            }
        } else {
            paintCard.classList.add('opacity-60', 'bg-gray-50');
            if (paintBadge) paintBadge.classList.remove('hidden');
            if (paintNotice) paintNotice.classList.remove('hidden');
            if (addPaintBtn) {
                addPaintBtn.disabled = true;
                addPaintBtn.classList.add('cursor-not-allowed', 'opacity-50');
            }
            if (paintTbody) {
                paintTbody.querySelectorAll('input, button').forEach(function(el) {
                    el.disabled = true;
                    el.classList.add('bg-gray-100', 'cursor-not-allowed');
                });
            }
        }
    }
    window.togglePaintMaterialsByProcess = togglePaintMaterialsByProcess;

    window.addPartMaterialRow = function() {
        const tbody = document.getElementById('part-materials-tbody');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2">
                <input type="hidden" name="materials[${materialRowIndex}][type]" value="part">
                <input type="text" name="materials[${materialRowIndex}][item_name]" value="" placeholder="Part / WIP Item Name" class="w-full text-xs rounded border-gray-300 py-1 font-semibold">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][lot_number]" value="" placeholder="Lot" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="number" step="any" name="materials[${materialRowIndex}][qty]" value="" placeholder="Qty" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-2 py-2">
                <input type="text" name="materials[${materialRowIndex}][uom]" value="" placeholder="UOM" class="w-full text-xs rounded border-gray-300 py-1">
            </td>
            <td class="px-1 py-2 text-center">
                <button type="button" onclick="removeMaterialRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1.5 py-0.5 text-sm rounded hover:bg-red-50 transition" title="Remove Row">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
        materialRowIndex++;
        updatePartMaterialsBreakdown();
    };

    window.removeMaterialRow = function(btn) {
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
            updatePartMaterialsBreakdown();
        }
    };

    function updatePartMaterialsBreakdown() {
        let totalWip = 0;
        let totalRepairan = 0;

        const tbody = document.getElementById('part-materials-tbody');
        if (tbody) {
            tbody.querySelectorAll('tr').forEach(function(row) {
                const nameInput = row.querySelector('input[name*="[item_name]"]');
                const qtyInput = row.querySelector('input[name*="[qty]"]');

                const name = (nameInput ? nameInput.value : '').trim().toLowerCase();
                const qty = parseFloat(qtyInput ? qtyInput.value : 0);

                if (!isNaN(qty) && qty > 0) {
                    if (name.includes('repair')) {
                        totalRepairan += qty;
                    } else {
                        totalWip += qty;
                    }
                }
            });
        }

        const wipField = document.getElementById('jml_input_wip');
        const repField = document.getElementById('repairan');
        const sumWipBadge = document.getElementById('summary-wip-qty');
        const sumRepBadge = document.getElementById('summary-repairan-qty');
        const sumTotalBadge = document.getElementById('summary-total-input-qty');

        if (wipField) wipField.value = totalWip;
        if (repField) repField.value = totalRepairan;
        if (sumWipBadge) sumWipBadge.textContent = totalWip.toLocaleString();
        if (sumRepBadge) sumRepBadge.textContent = totalRepairan.toLocaleString();
        if (sumTotalBadge) sumTotalBadge.textContent = (totalWip + totalRepairan).toLocaleString();

        if (typeof updateMaterialReconciliation === 'function') {
            updateMaterialReconciliation();
        }
    }
    window.updatePartMaterialsBreakdown = updatePartMaterialsBreakdown;

    // Helper to safely escape HTML
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    window.escapeHtml = escapeHtml;

    // Trouble / Downtime Problem-Approach Repeater
    let troubleRowIndex = Math.max(document.querySelectorAll('#troubles-tbody tr.trouble-row').length, {{ $report->troubles->count() }});

    window.addTroubleRow = function(masalah = '', category = 'Mesin', penanganan = '', lossTimeMinutes = '') {
        // Defensive check against event objects passed by inline event handlers
        if (typeof masalah !== 'string') masalah = '';
        if (typeof category !== 'string') category = 'Mesin';
        if (typeof penanganan !== 'string') penanganan = '';
        if (typeof lossTimeMinutes !== 'string' && typeof lossTimeMinutes !== 'number') lossTimeMinutes = '';

        const tbody = document.getElementById('troubles-tbody');
        if (!tbody) {
            console.error('troubles-tbody not found');
            return;
        }

        const emptyRow = document.getElementById('troubles-empty-row');
        if (emptyRow) {
            emptyRow.style.display = 'none';
            emptyRow.classList.add('hidden');
        }

        const tr = document.createElement('tr');
        tr.className = 'trouble-row block md:table-row bg-white rounded-xl md:rounded-none border border-gray-200 md:border-0 p-4 md:p-0 shadow-xs md:shadow-none hover:bg-slate-50/70 transition';
        tr.innerHTML = `
            <td class="block md:table-cell p-0 pb-2.5 md:px-3 md:py-3 align-top text-xs font-bold text-gray-500 border-b md:border-b-0 border-gray-100 mb-3 md:mb-0">
                <div class="flex items-center justify-between md:justify-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-bold text-xs md:bg-transparent md:border-0 md:p-0 md:text-gray-500">
                        <span class="md:hidden">Masalah #</span><span class="trouble-row-num"></span>
                    </span>
                    <button type="button" onclick="removeTroubleRow(this)"
                        class="md:hidden text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 transition cursor-pointer">
                        Hapus
                    </button>
                </div>
            </td>
            <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                    Masalah <span class="text-red-500">*</span>
                </label>
                <textarea name="troubles[${troubleRowIndex}][masalah]" rows="2"
                    class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 focus:border-blue-500 focus:ring-blue-500 transition shadow-xs"
                    placeholder="Jelaskan kendala / masalah yang terjadi...">${escapeHtml(masalah)}</textarea>
            </td>
            <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                    Kategori <span class="text-red-500">*</span>
                </label>
                <select name="troubles[${troubleRowIndex}][penyebab]"
                    class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 font-medium focus:border-blue-500 focus:ring-blue-500 transition shadow-xs">
                    @foreach (config('mes.sp_trouble_categories') as $catKey => $catLabel)
                        <option value="{{ $catKey }}" ${category === '{{ $catKey }}' ? 'selected' : ''}>{{ $catLabel }}</option>
                    @endforeach
                </select>
            </td>
            <td class="block md:table-cell p-0 mb-3 md:mb-0 md:px-3 md:py-3 align-top">
                <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                    Penanganan
                </label>
                <textarea name="troubles[${troubleRowIndex}][penanganan]" rows="2"
                    class="w-full rounded-lg border-gray-300 text-xs md:text-sm py-1.5 md:py-2 focus:border-blue-500 focus:ring-blue-500 transition shadow-xs"
                    placeholder="Tindakan penanganan / perbaikan...">${escapeHtml(penanganan)}</textarea>
            </td>
            <td class="block md:table-cell p-0 md:px-3 md:py-3 align-top">
                <label class="block md:hidden text-[11px] font-bold text-gray-600 uppercase mb-1">
                    Loss Time (Menit)
                </label>
                <div class="relative rounded-lg shadow-xs">
                    <input type="number"
                        name="troubles[${troubleRowIndex}][loss_time_minutes]"
                        value="${lossTimeMinutes}"
                        min="0"
                        max="1440"
                        placeholder="0"
                        class="trouble-loss-minutes w-full text-xs md:text-sm rounded-lg border-gray-300 py-1.5 md:py-2 pr-12 text-right font-mono font-bold focus:border-blue-500 focus:ring-blue-500">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="text-xs font-bold text-gray-400 uppercase">min</span>
                    </div>
                </div>
            </td>
            <td class="hidden md:table-cell px-2 py-3 align-top text-center">
                <button type="button" onclick="removeTroubleRow(this)"
                    class="text-red-500 hover:text-red-700 font-bold p-1 rounded-lg hover:bg-red-50 transition cursor-pointer text-base leading-none"
                    title="Hapus masalah ini">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
        troubleRowIndex++;

        const input = tr.querySelector('.trouble-loss-minutes');
        if (input) {
            input.addEventListener('input', updateTotalDowntime);
            input.addEventListener('change', updateTotalDowntime);
        }

        reindexTroubleRows();
        updateTotalDowntime();
    };

    window.removeTroubleRow = function(btn) {
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
            reindexTroubleRows();
            updateTotalDowntime();
        }
    };

    function reindexTroubleRows() {
        const rows = document.querySelectorAll('#troubles-tbody tr.trouble-row');
        const emptyRow = document.getElementById('troubles-empty-row');
        if (emptyRow) {
            if (rows.length === 0) {
                emptyRow.style.display = '';
                emptyRow.classList.remove('hidden');
            } else {
                emptyRow.style.display = 'none';
                emptyRow.classList.add('hidden');
            }
        }
        rows.forEach(function(row, i) {
            const numCols = row.querySelectorAll('.trouble-row-num');
            numCols.forEach(function(numCol) {
                numCol.textContent = i + 1;
            });
        });
    }

    // Trouble / Downtime Real-Time Recalculation
    function updateTotalDowntime() {
        let totalMins = 0;
        document.querySelectorAll('.trouble-loss-minutes').forEach(function(input) {
            const val = parseInt(input.value, 10);
            if (!isNaN(val) && val > 0) {
                totalMins += val;
            }
        });
        const totalHours = (totalMins / 60).toFixed(1);

        const badgeMin = document.getElementById('total-downtime-minutes');
        const badgeHr = document.getElementById('total-downtime-hours');
        const footerMin = document.getElementById('footer-total-downtime-minutes');

        if (badgeMin) badgeMin.textContent = totalMins;
        if (badgeHr) badgeHr.textContent = totalHours;
        if (footerMin) footerMin.textContent = totalMins;
    }

    document.querySelectorAll('.trouble-loss-minutes').forEach(function(input) {
        input.addEventListener('input', updateTotalDowntime);
        input.addEventListener('change', updateTotalDowntime);
    });
    reindexTroubleRows();
    updateTotalDowntime();

    // Part Materials (WIP & Repairan) Real-Time Breakdown Recalculation
    const partMaterialsTbody = document.getElementById('part-materials-tbody');
    if (partMaterialsTbody) {
        partMaterialsTbody.addEventListener('input', function(e) {
            if (e.target && e.target.matches('input[name*="[qty]"], input[name*="[item_name]"]')) {
                updatePartMaterialsBreakdown();
            }
        });
        partMaterialsTbody.addEventListener('change', function(e) {
            if (e.target && e.target.matches('input[name*="[qty]"], input[name*="[item_name]"]')) {
                updatePartMaterialsBreakdown();
            }
        });
    }
    updatePartMaterialsBreakdown();
    if (typeof updateMaterialReconciliation === 'function') {
        updateMaterialReconciliation();
    }

    // Item Paint Conditional Toggling based on Proses Prod
    const processProdEl = document.getElementById('process_prod');
    if (processProdEl) {
        processProdEl.addEventListener('change', togglePaintMaterialsByProcess);
    }
    togglePaintMaterialsByProcess();
</script>
