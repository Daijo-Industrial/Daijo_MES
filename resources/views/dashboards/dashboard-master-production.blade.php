<x-dashboard-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Production Dashboard
        </h2>
    </x-slot>

    <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-slate-800 tracking-tight mb-3">Daily Production</h1>
            <div class="w-16 h-0.5 bg-gradient-to-r from-slate-600 to-slate-400 mx-auto rounded-full"></div>
        </div>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <form method="GET" action="{{ route('djoni.dashboard') }}">
                <div class="flex flex-wrap gap-4 items-end">

                <div class="w-full sm:max-w-xs mb-6">
                    <label for="item_code" class="block text-sm font-medium text-gray-700 mb-1">Select Item Code</label>
                    <select id="item_code" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Item Code --</option>
                        @php
                            $distinctItemCodes = collect($structuredData)
                                ->flatMap(fn($d) => collect($d['daily_item_code'])->pluck('item_code'))
                                ->unique()
                                ->values();
                        @endphp
                        @foreach ($distinctItemCodes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- AJAX Output -->
                <div id="machine-info" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                    <!-- Cards will be inserted here -->
                </div>


                    <!-- Filter by Machine -->
                    <div class="w-full sm:w-auto">
                        <label for="machine_name" class="block text-sm font-medium text-gray-700">Filter by Machine</label>
                        <select name="machine_name" id="machine_name" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Machine</option>
                            @foreach ($machines as $machine)
                                <option value="{{ $machine }}" {{ request('machine_name') == $machine ? 'selected' : '' }}>
                                    {{ $machine }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Select Date -->
                    <div class="w-full sm:w-auto">
                        <label for="date" class="block text-sm font-medium text-gray-700">Select Date</label>
                        <input type="date" id="date" name="date" value="{{ request('date', now()->toDateString()) }}"
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md">
                    </div>

                    <!-- Apply Filter Button -->
                    <div class="w-full sm:w-auto">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Apply Filter</button>
                    </div>

                    <!-- Mould Change Alert Button -->
                    <div class="w-full sm:w-auto">
                        @if(isset($longMouldChanges) && count($longMouldChanges) > 0)
                            <button type="button" id="showLongMouldModal" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded flex items-center space-x-2 font-bold shadow-md transition-all duration-200">
                                <span>⚠️ Mould Change &gt; 20m</span>
                                <span class="bg-white text-rose-700 px-2 py-0.5 rounded-full text-xs font-extrabold">{{ count($longMouldChanges) }}</span>
                            </button>
                        @else
                            <button type="button" disabled class="bg-gray-300 text-gray-500 px-4 py-2 rounded flex items-center space-x-2 font-medium cursor-not-allowed">
                                <span>Mould Change &gt; 20m</span>
                                <span class="bg-gray-400 text-white px-2 py-0.5 rounded-full text-xs font-bold">0</span>
                            </button>
                        @endif
                    </div>

                    <!-- Actual Produksi 0 Alert Button -->
                    <div class="w-full sm:w-auto">
                        @if(isset($zeroActualRemarks) && count($zeroActualRemarks) > 0)
                            <button type="button" id="showZeroActualModal" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded flex items-center space-x-2 font-bold shadow-md transition-all duration-200">
                                <span>📉 Actual Produksi 0</span>
                                <span class="bg-white text-amber-700 px-2 py-0.5 rounded-full text-xs font-extrabold">{{ count($zeroActualRemarks) }}</span>
                            </button>
                        @else
                            <button type="button" disabled class="bg-gray-300 text-gray-500 px-4 py-2 rounded flex items-center space-x-2 font-medium cursor-not-allowed">
                                <span>Actual Produksi 0</span>
                                <span class="bg-gray-400 text-white px-2 py-0.5 rounded-full text-xs font-bold">0</span>
                            </button>
                        @endif
                    </div>
                </div>
            </form>
                @if (request('machine_name')) <!-- Only show tables if a machine is selected -->
                
                @foreach($structuredData as $machineName => $data)
                    @php
                        $zoneName = collect($data['pengawas'])->pluck('zone_name')->first();
                        $machineUser = \App\Models\User::where('name', $machineName)->first();
                    @endphp
                    <div class="border p-4 rounded-xl mb-6 shadow-md bg-white">
                        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                            <h2 class="text-xl font-bold">
                                Machine: {{ $machineName }} 
                                @if($zoneName)
                                    || ZONA {{ $zoneName }}
                                @endif
                            </h2>

                            @if($machineUser)
                                <button 
                                    type="button" 
                                    onclick="openMasterMaintChecklistModal({{ $machineUser->id }}, '{{ $machineName }}', '{{ $selectedDate }}')" 
                                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5"
                                >
                                    <span>🛠️ Cek Maintenance Checklist</span>
                                </button>
                            @endif
                        </div>
                         <h1>
                            <!-- Daily Percentage : {{ $data['average_achievement'] ?? 0 }} % -->
                            Efficiency: {{ $data['machine_efficiency'] ?? 0 }}%
    |                       Jam Aktif: {{ $data['total_jam_aktif'] ?? 0 }} jam
    |                       Prod: {{ $data['total_prod_menit'] ?? 0 }} menit
                        </h1>

                        <!-- @if(isset($data['pengawas']))
                            <div class="grid md:grid-cols-3 gap-4">
                                @foreach($data['pengawas'] as $shift => $pengawasData)
                                    <div class="flex items-center space-x-3 border p-3 rounded-lg shadow-sm">
                                        <img src="{{ $pengawasData['profile_path'] }}" alt="Pengawas Profile" class="w-12 h-12 rounded-full border">
                                        <div>
                                            <p class="text-xs text-gray-400">Shift {{ $shift }} - Zona {{ $pengawasData['zone_name'] }}</p>
                                            <p class="font-semibold text-sm">{{ $pengawasData['name'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Optional: You can show logs or other data next --}} -->
                    </div>
                @endforeach


                    @foreach ($structuredData as $userName => $data)
                        <div class="mb-8 border-b pb-4">
                            <!-- <h3 class="text-2xl font-bold text-gray-700">{{ $userName }}</h3> -->


                        <!-- Repair Machine Logs -->
                        @php
                            $selectedDate = request('date', now()->toDateString());
                            $selectedMachine = request('machine_name');
                            $allLogs = $data['repair_machine_logs'] ?? [];
                            
                            // Filter berdasarkan mesin saja, karena tanggal/shift sudah difilter di controller secara presisi
                            $filteredRepairLogs = collect($allLogs)->filter(function ($log) use ($selectedMachine) {
                                return !$selectedMachine || $log['machine_name'] === $selectedMachine;
                            });
                        @endphp

                        @if(isset($filteredRepairLogs) && count($filteredRepairLogs) > 0)
                            <div class="mt-8 mb-6">
                                <h4 class="text-md font-bold text-slate-700 mb-3 flex items-center space-x-2">
                                    <span>🔧 Repair Machine Logs</span>
                                    <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">{{ count($filteredRepairLogs) }} Data</span>
                                </h4>
                                <div class="overflow-hidden border border-slate-100 rounded-xl shadow-sm bg-white">
                                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Operator & Machine</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Start Time</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">End Time</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Duration</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Problem</th>
                                                <th scope="col" class="px-4 py-3 text-left font-bold text-slate-500 uppercase tracking-wider">Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($filteredRepairLogs as $repairLog)
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center space-x-2.5">
                                                            <img src="{{ $repairLog['pic_profile_path'] }}" alt="{{ $repairLog['pic'] }}" class="w-8 h-8 rounded-full border border-slate-200 object-cover shadow-sm">
                                                            <div>
                                                                <div class="font-bold text-slate-800">{{ $repairLog['pic'] }}</div>
                                                                <div class="text-[10px] text-indigo-600 font-semibold uppercase">Machine: {{ $repairLog['machine_name'] }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        @if(!$repairLog['is_completed'])
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                                Ongoing
                                                            </span>
                                                        @elseif(($repairLog['status'] ?? '') === 'problem')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-rose-500"></span>
                                                                Problem (&gt;30m)
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span>
                                                                Safe (&le;30m)
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 font-medium whitespace-nowrap">
                                                        {{ \Carbon\Carbon::parse($repairLog['start_time'])->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 font-medium whitespace-nowrap">
                                                        @if($repairLog['end_time'])
                                                            {{ \Carbon\Carbon::parse($repairLog['end_time'])->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') }}
                                                        @else
                                                            <span class="text-slate-400 italic font-normal">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                                        @if(!$repairLog['is_completed'])
                                                            <span class="text-amber-600 font-medium">{{ $repairLog['actual_time'] }} min (elapsed)</span>
                                                        @else
                                                            <span class="{{ ($repairLog['status'] ?? '') === 'problem' ? 'text-rose-600' : 'text-slate-700' }}">{{ $repairLog['actual_time'] }} min</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700 font-medium max-w-[200px] truncate" title="{{ $repairLog['problem'] }}">
                                                        {{ $repairLog['problem'] }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 italic max-w-[200px] truncate" title="{{ $repairLog['remark'] ?: 'No remark' }}">
                                                        {{ $repairLog['remark'] ?: '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mt-8 mb-6 p-4 border border-dashed border-slate-200 rounded-xl bg-slate-50/50 text-center text-slate-400 text-xs italic">
                                No Repair Machine Logs Available for {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                            </div>
                        @endif

                            <!-- Mould Change Log -->
                            <h4 class="text-lg font-semibold mt-4">Mould Change Summary</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-blue-200 p-4 rounded-lg">
                                    <p class="text-xl font-semibold">{{ count($data['mould_change_log']) }}</p>
                                    <p>Total Mould Changes</p>
                                </div>
                                @php
                                    $totalDowntime = array_sum(array_column(array_merge($data['mould_change_log'], $data['adjust_machine_logs']), 'actual_time'));
                                @endphp

                                <div class="bg-green-200 p-4 rounded-lg">
                                    <p class="text-xl font-semibold">{{ $totalDowntime }} min</p>
                                    <p>Total Downtime</p>
                                </div>
                            
                            </div>

                           <!-- Button to show details -->
                            <button id="showDetails"
                                class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                View Details
                            </button>

                            <!-- Modal -->
                            <div id="detailModal"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">

                                <!-- Modal Box -->
                                <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 max-h-[90vh] flex flex-col">

                                    <!-- Header -->
                                    <div class="p-6 border-b">
                                        <h2 class="text-lg font-bold">
                                            Mould Change & Adjusting Machine Details
                                        </h2>
                                    </div>

                                    <!-- Scrollable Content -->
                                    <div class="p-6 overflow-y-auto flex-1">
                                        <div class="overflow-x-auto">
                                            <table class="w-full border-collapse border border-gray-300 text-sm">
                                                <thead class="bg-gray-200 sticky top-0 z-10">
                                                    <tr>
                                                        <th class="border px-4 py-2">Machine</th>
                                                        <th class="border px-4 py-2">Item Code</th>
                                                        <th class="border px-4 py-2">Start Time</th>
                                                        <th class="border px-4 py-2">End Time</th>
                                                        <th class="border px-4 py-2">Predicted Time (min)</th>
                                                        <th class="border px-4 py-2">Actual Time (min)</th>
                                                        <th class="border px-4 py-2">PIC</th>
                                                        <th class="border px-4 py-2">Remark</th>
                                                        <th class="border px-4 py-2">Status</th>
                                                        <th class="border px-4 py-2">Type</th>
                                                        <th class="border px-4 py-2">Foto</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $combinedLogs = array_merge(
                                                            $data['mould_change_log'],
                                                            $data['adjust_machine_logs']
                                                        );

                                                        usort($combinedLogs, fn ($a, $b) =>
                                                            strcmp($a['item_code'], $b['item_code'])
                                                        );
                                                    @endphp

                                                    @foreach ($combinedLogs as $log)
                                                        <tr class="{{ ($log['status'] ?? '') === 'problem'
                                                            ? 'bg-red-200'
                                                            : 'bg-green-200' }}">
                                                            <td class="border px-4 py-2">{{ $log['machine_name'] }}</td>
                                                            <td class="border px-4 py-2">{{ $log['item_code'] }}</td>
                                                            <td class="border px-4 py-2">
                                                                {{ \Carbon\Carbon::parse($log['start_time'])
                                                                    ->setTimezone('Asia/Jakarta')
                                                                    ->format('Y-m-d H:i') }}
                                                            </td>
                                                            <td class="border px-4 py-2">
                                                                {{ \Carbon\Carbon::parse($log['end_time'])
                                                                    ->setTimezone('Asia/Jakarta')
                                                                    ->format('Y-m-d H:i') }}
                                                            </td>
                                                            <td class="border px-4 py-2">
                                                                {{ $log['predicted_time'] }} Min
                                                            </td>
                                                            <td class="border px-4 py-2">
                                                                {{ $log['actual_time'] }} Min
                                                            </td>
                                                            <td class="border px-4 py-2">{{ $log['pic'] }}</td>
                                                            <td class="border px-4 py-2">{{ $log['remark'] }}</td>
                                                            <td class="border px-4 py-2 font-bold">
                                                                {{ ucfirst($log['status'] ?? 'N/A') }}
                                                            </td>
                                                            <td class="border px-4 py-2 font-bold">
                                                                {{ in_array($log, $data['mould_change_log'], true)
                                                                    ? 'Mould Change'
                                                                    : 'Adjusting Machine' }}
                                                            </td>
                                                            <td class="border px-4 py-2">
                                                                <img src="{{ asset($log['pic_profile_path']) }}"
                                                                    alt="{{ $log['pic'] }}"
                                                                    class="w-8 h-8 rounded-full object-cover">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="p-4 border-t flex justify-end">
                                        <button id="closeModal"
                                            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                            Close
                                        </button>
                                    </div>

                                </div>
                            </div>



                            

                            <div id="photoModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                                <div class="bg-white p-6 rounded-lg shadow-lg relative">
                                    <!-- Close Button -->
                                    <button id="closePhotoModal" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full">
                                        &times;
                                    </button>
                                    
                                    <!-- Image -->
                                    <img id="modalPhoto" src="" alt="Mould Change Photo" class="max-w-full max-h-screen cursor-pointer transition-transform transform hover:scale-125">
                                </div>
                            </div>

                            <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-900 rounded shadow-sm mb-4">
                                <h3 class="text-lg font-bold mb-2">📝 Catatan Penting</h3>

                                @php
                                    $remarks = collect($data['daily_item_code'])->filter(fn($dic) => !empty($dic['remark']));
                                @endphp

                                @if ($remarks->isNotEmpty())
                                    <ul class="list-disc list-inside space-y-2 text-sm">
                                        @foreach ($remarks as $dic)
                                            <li>
                                                <strong>Shift {{ $dic['shift'] }} - {{ $dic['item_code'] }}:</strong><br>
                                                {!! nl2br(e($dic['remark'])) !!}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="italic text-sm text-gray-600">Tidak ada remark hari ini.</p>
                                @endif
                            </div>

                            <!-- Hourly Remarks -->
                            @if(count($data['hourly_remarks']) > 0)
                                <h4 class="text-lg font-bold text-slate-800 mt-8 mb-4 flex items-center space-x-2">
                                    <span class="w-2.5 h-5 bg-indigo-600 rounded-sm"></span>
                                    <span>Hourly Remarks</span>
                                </h4>
                                <div class="rounded-2xl border border-slate-200 shadow-sm bg-white mt-2 relative">
                                    <table class="w-full text-sm text-left text-slate-600 border-collapse">
                                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase text-xs tracking-wider rounded-t-2xl">
                                            <tr>
                                                <th class="px-4 py-3.5">PIC</th>
                                                <th class="px-4 py-3.5">Item Code</th>
                                                <th class="px-4 py-3.5">Dibuat Jam</th>
                                                <th class="px-4 py-3.5 text-center">Time Range</th>
                                                <th class="px-4 py-3.5 text-center">Shift</th>
                                                <th class="px-4 py-3.5 text-center">Target</th>
                                                <th class="px-4 py-3.5 text-center">Actual Scan</th>
                                                <th class="px-4 py-3.5 text-center">Actual Production</th>
                                                <th class="px-4 py-3.5 text-center">NG</th>
                                                <th class="px-4 py-3.5 text-center">Status</th>
                                                <th class="px-4 py-3.5">Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                        @php
                                            $currentShift = null;
                                            $totalActual = 0;
                                        @endphp

                                        @foreach ($data['hourly_remarks'] as $index => $remark)
                                            @php
                                                $pct = 0;
                                                $actualProd = $remark['actual_production'] ?? 0;
                                                if ($remark['target'] > 0) {
                                                    $pct = round(($actualProd / $remark['target']) * 100, 1);
                                                }

                                                // Penentuan warna status dinamis
                                                if ($pct >= 100) {
                                                    $borderIndicator = 'border-l-4 border-l-emerald-500';
                                                    $badgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                                                    $textClass = 'text-emerald-600';
                                                    $statusText = '✓ Achieved';
                                                } elseif ($pct >= 50) {
                                                    $borderIndicator = 'border-l-4 border-l-amber-500';
                                                    $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                                                    $textClass = 'text-amber-600';
                                                    $statusText = '✗ Not Achieved';
                                                } else {
                                                    $borderIndicator = 'border-l-4 border-l-rose-500';
                                                    $badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200';
                                                    $textClass = 'text-rose-600';
                                                    $statusText = '✗ Not Achieved';
                                                }

                                                // Cek jika ganti shift
                                                if ($currentShift !== $remark['shift']) {
                                                    $currentShift = $remark['shift'];
                                                    $totalActual = 0;
                                                }

                                                $totalActual += $actualProd;
                                            @endphp

                                            <tr class="hover:bg-slate-50/70 transition-colors {{ $borderIndicator }}">
                                                <td class="px-4 py-3.5 relative">
                                                    @if(($remark['pics_count'] ?? 1) > 1)
                                                        <div class="relative inline-block pic-dropdown-container">
                                                            <button type="button" 
                                                                class="pic-dropdown-btn flex items-center space-x-2 text-left p-1 -m-1 rounded-lg hover:bg-indigo-50/80 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all cursor-pointer group"
                                                                title="Klik untuk melihat {{ $remark['pics_count'] }} PIC">
                                                                
                                                                <!-- Avatar Stack -->
                                                                <div class="flex -space-x-2 overflow-hidden flex-shrink-0">
                                                                    @foreach(array_slice($remark['pics'], 0, 2) as $p)
                                                                        <img src="{{ $p['profile_path'] }}" alt="{{ $p['name'] }}" class="inline-block w-7 h-7 rounded-full ring-2 ring-white object-cover shadow-xs">
                                                                    @endforeach
                                                                </div>

                                                                <!-- Primary Name & Badge -->
                                                                <div class="flex items-center space-x-1.5">
                                                                    <span class="font-semibold text-slate-800 text-xs sm:text-sm group-hover:text-indigo-700 transition-colors">{{ $remark['pic'] }}</span>
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-700 border border-indigo-200 group-hover:bg-indigo-200 transition-colors shadow-2xs">
                                                                        +{{ count($remark['pics']) - 1 }}
                                                                        <svg class="w-3 h-3 ml-0.5 transform transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                                        </svg>
                                                                    </span>
                                                                </div>
                                                            </button>

                                                            <!-- Dropdown Menu -->
                                                            <div class="pic-dropdown-menu hidden absolute left-0 {{ $loop->remaining < 2 && count($data['hourly_remarks']) > 2 ? 'bottom-full mb-2' : 'top-full mt-2' }} z-50 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 p-2.5 transition-all transform origin-top-left">
                                                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 px-1">
                                                                    <div class="flex items-center space-x-1.5">
                                                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Daftar PIC ({{ $remark['pics_count'] }})</span>
                                                                    </div>
                                                                    <span class="text-[10px] text-slate-400 font-medium">Shift {{ $remark['shift'] }}</span>
                                                                </div>
                                                                <div class="space-y-1.5">
                                                                    @foreach($remark['pics'] as $p)
                                                                        <div class="flex items-center space-x-2.5 p-1.5 rounded-lg hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                                                                            <img src="{{ $p['profile_path'] }}" alt="{{ $p['name'] }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-xs flex-shrink-0">
                                                                            <div class="min-w-0 flex-1">
                                                                                <div class="text-xs font-bold text-slate-800 truncate">{{ $p['name'] }}</div>
                                                                                <div class="text-[10px] font-semibold text-indigo-600">{{ $p['role'] }}</div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center space-x-2.5">
                                                            <img src="{{ $remark['pic_profile_path'] }}" alt="PIC Profile" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">
                                                            <span class="font-medium text-slate-800 text-xs sm:text-sm">{{ $remark['pic'] }}</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-700">{{ $remark['item_code'] }}</td>
                                                <td class="px-4 py-3.5 text-xs text-slate-500">
                                                    {{ \Carbon\Carbon::parse($remark['updated_at'])->format('d M, H:i') }}
                                                </td>
                                                <td class="px-4 py-3.5 text-center font-medium text-slate-700">{{ $remark['time_range'] }}</td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                                        {{ $remark['shift'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-center font-mono text-slate-500">{{ $remark['target'] }}</td>
                                                <td class="px-4 py-3.5 text-center font-bold font-mono text-slate-700">{{ $remark['actual'] }}</td>
                                                <td class="px-4 py-3.5 text-center font-extrabold font-mono text-indigo-600">{{ $remark['actual_production'] ?? 0 }}</td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <div class="inline-flex items-center space-x-1.5">
                                                        <span class="font-bold font-mono {{ ($remark['ng'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                                            {{ $remark['ng'] ?? 0 }}
                                                        </span>
                                                        @if(($remark['ng'] ?? 0) > 0)
                                                            <button 
                                                                class="bg-rose-50 hover:bg-rose-100 text-rose-700 text-[10px] px-1.5 py-0.5 rounded font-semibold border border-rose-200 transition"
                                                                onclick='showNgDetails(@json($remark["ng_details"]))'
                                                            >
                                                                Details
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shadow-sm {{ $badgeClass }}">
                                                        {{ $statusText }}
                                                    </span>

                                                    <div class="text-[11px] font-bold mt-1.5 {{ $textClass }}">
                                                        {{ $pct }}%
                                                    </div>

                                                    @if(!empty($remark['is_multi_item']))
                                                    <div style="font-size:9px;" class="text-slate-400 mt-1">
                                                        combined: {{ min(round($remark['combined_actual_seconds'] / 60, 1), 60) }}m / 60m
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3.5 text-slate-600 italic whitespace-normal break-words min-w-[200px]">{{ $remark['remark'] }}</td>
                                            </tr>

                                            <div id="ngDetailModal" class="fixed inset-0 hidden bg-black/40 justify-center items-center z-[100]">
                                                <div class="bg-white p-6 rounded-2xl shadow-xl w-96 max-w-full mx-4 border border-slate-100">
                                                    <h2 class="text-lg font-bold text-slate-800 mb-3 flex items-center space-x-2">
                                                        <span class="text-rose-500">⚠️</span>
                                                        <span>NG Details</span>
                                                    </h2>
                                                    <div id="ngDetailList" class="space-y-2 max-h-64 overflow-y-auto pr-1"></div>
                                                    <div class="mt-5 text-right">
                                                        <button onclick="closeNgDetailModal()" class="px-5 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-xl text-sm font-semibold transition">
                                                            Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            @php
                                                $nextShift = $data['hourly_remarks'][$index + 1]['shift'] ?? null;
                                                $isLastInShift = $nextShift !== $currentShift;
                                            @endphp

                                            @if ($isLastInShift)
                                                <tr class="bg-indigo-50/50 text-indigo-950 font-bold text-sm">
                                                    <td colspan="7" class="px-4 py-3 text-right">Total Actual (Shift {{ $currentShift }})</td>
                                                    <td class="px-4 py-3 text-center text-indigo-700 font-extrabold font-mono">{{ $totalActual }}</td>
                                                    <td colspan="3" class="px-4 py-3"></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <h4 class="text-lg font-semibold mt-6 mb-4">Hourly Remarks</h4>
                                <p class="text-gray-500">No Hourly Remarks Available</p>
                            @endif
                            
                            <!-- Hourly Production -->
                            <!-- <h4 class="text-lg font-semibold mt-6">Hourly Production</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 mt-2">
                                        <thead class="bg-gray-200">
                                            <tr>
                                                <th class="border px-4 py-2">Hour</th>
                                                <th class="border px-4 py-2">Item Code</th>
                                                <th class="border px-4 py-2">Total Quantity</th>
                                                <th class="border px-4 py-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $groupedHourly = [];
                                        @endphp

                                        @foreach ($data['hourly_production'] as $record)
                                            @php
                                                $hour = $record['hour'];
                                                $itemCode = $record['item_code'];
                                                $users = $record['users'];
                                                $totalQuantity = array_sum(array_column($users, 'quantity'));

                                                $groupedHourly[$hour][$itemCode] = [
                                                    'total_quantity' => $totalQuantity,
                                                    'users' => $users
                                                ];
                                            @endphp
                                        @endforeach

                                        @foreach ($groupedHourly as $hour => $itemGroups)
                                            @foreach ($itemGroups as $itemCode => $dataSet)
                                                <tr>
                                                    <td class="border px-4 py-2 font-bold">{{ $hour }}</td>
                                                    <td class="border px-4 py-2">{{ $itemCode }}</td>
                                                    <td class="border px-4 py-2 font-bold">{{ $dataSet['total_quantity'] }}</td>
                                                    <td class="border px-4 py-2 text-center">
                                                        <button class="toggle-hourly-details bg-blue-500 text-white px-3 py-1 rounded-md" data-hour="{{ $hour }}" data-item="{{ $itemCode }}">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="hourly-details hidden" id="details-{{ $hour }}-{{ $itemCode }}">
                                                    <td colspan="4">
                                                        <table class="w-full border-collapse border border-gray-300 mt-2">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th class="border px-4 py-2">User</th>
                                                                    <th class="border px-4 py-2">Quantity</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($dataSet['users'] as $user => $userData)
                                                                    <tr>
                                                                        <td class="border px-4 py-2 flex items-center">
                                                                            <img src="{{ $userData['user_profile_path'] }}" alt="{{ $user }}" class="w-10 h-10 rounded-full mr-2">
                                                                            {{ $user }}
                                                                        </td>
                                                                        <td class="border px-4 py-2">{{ $userData['quantity'] }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div> -->

                            <!-- Daily Item Code -->
                            <h4 class="text-lg font-semibold mt-6">Daily Item Code</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 mt-2">
                                        <thead class="bg-gray-200">
                                            <tr>
                                                <th class="border px-4 py-2">Item Code</th>
                                                <th class="border px-4 py-2">Item Name</th>
                                                <th class="border px-4 py-2">Shift</th>
                                                <th class="border px-4 py-2">Planned Quantity</th>
                                                <th class="border px-4 py-2">Quantity Produksi</th>
                                                <th class="border px-4 py-2">Cycle Time</th>
                                                <th class="border px-4 py-2">SAP Cycle Time</th>
                                                <th class="border px-4 py-2">Start Time</th>
                                                <th class="border px-4 py-2">End Time</th>
                                                <th class="border px-4 py-2">Berat Purging</th>
                                                <th class="border px-4 py-2">Delivery Schedule</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data['daily_item_code'] as $dailyItem)
                                                @php
                                                    $rowColor = ($dailyItem['total_scanned_quantity'] < $dailyItem['quantity']) 
                                                        ? 'bg-red-300' 
                                                        : 'bg-green-300';
                                                @endphp
                                                <tr class="{{ $rowColor }}">
                                                    <td class="border px-4 py-2">{{ $dailyItem['item_code'] }}</td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['item_name'] }}</td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['shift'] }}</td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['quantity'] }}</td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['total_scanned_quantity'] }}</td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['cycle_time_seconds'] }} Detik </td>
                                                    <td class="border px-4 py-2">{{ $dailyItem['sap_cycle_time'] }} Detik </td>
                                                    <td class="border px-4 py-2">
                                                    {{ $dailyItem['start_date'] }} {{ \Carbon\Carbon::parse($dailyItem['start_time'])->subHours(7)->format('H:i') }}
                                                    </td>
                                                    <td class="border px-4 py-2">
                                                    {{ $dailyItem['end_date'] }} {{ \Carbon\Carbon::parse($dailyItem['end_time'])->subHours(7)->format('H:i') }}
                                                    </td>
                                                    <td class="border px-4 py-2 text-center font-semibold text-orange-600">
                                                        {{ !is_null($dailyItem['resin_usage']) ? $dailyItem['resin_usage'] . ' KG' : '-' }}
                                                    </td>
                                                   <td class="border px-4 py-2 text-center">
                                                        <button 
                                                            class="btn-delivery-schedule bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                                            data-item-code="{{ $dailyItem['item_code'] }}"
                                                            data-item-name="{{ $dailyItem['item_name'] }}"
                                                            data-delsched="{{ json_encode($dailyItem['delsched'] ?? []) }}">
                                                            <i class="fas fa-calendar-alt mr-1"></i> Schedule
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>


                                <div id="deliveryScheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
                                        <div class="flex justify-between items-center mb-4 border-b pb-3">
                                            <h3 class="text-xl font-semibold text-gray-800">
                                                <i class="fas fa-truck mr-2"></i>Delivery Schedule - <span id="modalItemCode"></span>
                                            </h3>
                                            <button 
                                                id="closeDeliverySchedule" 
                                                class="text-gray-600 hover:text-gray-800 text-2xl font-bold leading-none">
                                                &times;
                                            </button>
                                        </div>
                                        
                                        <div class="overflow-x-auto">
                                            <table class="w-full border-collapse border border-gray-300">
                                                <thead class="bg-gray-200">
                                                    <tr>
                                                        <th class="border px-4 py-2">Delivery Date</th>
                                                        <th class="border px-4 py-2">Item Code</th>
                                                        <th class="border px-4 py-2">Item Name</th>
                                                        <th class="border px-4 py-2">Outstanding</th>
                                                        <th class="border px-4 py-2">Packaging Code</th>
                                                        <th class="border px-4 py-2">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="deliveryScheduleBody">
                                                    <!-- Data will be populated by JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-4 text-right border-t pt-3">
                                            <button 
                                                id="closeDeliveryScheduleBtn" 
                                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                                                <i class="fas fa-times mr-1"></i> Close
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            <!-- Scanned Data -->
                            <h4 class="text-lg font-semibold mt-6">Scanned Data</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 mt-2">
                                        <thead class="bg-gray-200">
                                            <tr>
                                                <th class="border px-4 py-2">Item Code</th>
                                                <th class="border px-4 py-2">SPK Code</th>
                                                <th class="border px-4 py-2">Accumulated Quantity</th>
                                                <th class="border px-4 py-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $spkSummary = []; // Store accumulated quantity per item_code and spk_code
                                            $spkDetails = []; // Store details per item_code and spk_code
                                        @endphp

                                        @foreach ($data['daily_item_code'] as $dailyItem)
                                            @foreach ($dailyItem['scanned_data'] as $scan)
                                                @php
                                                    // Gunakan item_code dari scanned_data, bukan dari dailyItem
                                                    $itemCode = $scan['item_code'];
                                                    $spkCode = $scan['spk_code'];
                                                    $spkKey = $itemCode . '-' . $spkCode; // Unique key per item + SPK

                                                    // Inisialisasi jika belum ada
                                                    if (!isset($spkSummary[$spkKey])) {
                                                        $spkSummary[$spkKey] = [
                                                            'item_code' => $itemCode,
                                                            'spk_code' => $spkCode,
                                                            'accumulated_quantity' => 0,
                                                        ];
                                                        $spkDetails[$spkKey] = [];
                                                    }

                                                    // Tambah kuantitas
                                                    $spkSummary[$spkKey]['accumulated_quantity'] += $scan['quantity'];

                                                    // Simpan detail
                                                    $spkDetails[$spkKey][] = [
                                                        'warehouse'   => $scan['warehouse'],
                                                        'quantity'    => $scan['quantity'],
                                                        'label'       => $scan['label'],
                                                        'user'        => $scan['user'],
                                                        'scanned_at'  => $scan['scanned_at'],
                                                    ];
                                                @endphp
                                            @endforeach
                                        @endforeach


                                            @foreach ($spkSummary as $spkKey => $summary)
                                                <tr>
                                                    <td class="border px-4 py-2 font-bold">{{ $summary['item_code'] }}</td>
                                                    <td class="border px-4 py-2 font-bold">{{ $summary['spk_code'] }}</td>
                                                    <td class="border px-4 py-2 font-bold">{{ $summary['accumulated_quantity'] }}</td>
                                                    <td class="border px-4 py-2 text-center">
                                                        <button class="toggle-details bg-blue-500 text-white px-3 py-1 rounded-md" data-spk="{{ $spkKey }}">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="spk-details hidden" id="details-{{ $spkKey }}">
                                                    <td colspan="4">
                                                        <table class="w-full border-collapse border border-gray-300 mt-2">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th class="border px-4 py-2">Warehouse</th>
                                                                    <th class="border px-4 py-2">Quantity</th>
                                                                    <th class="border px-4 py-2">Label</th>
                                                                    <th class="border px-4 py-2">User</th>
                                                                    <th class="border px-4 py-2">Scanned At</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($spkDetails[$spkKey] as $detail)
                                                                    <tr>
                                                                        <td class="border px-4 py-2">{{ $detail['warehouse'] }}</td>
                                                                        <td class="border px-4 py-2">{{ $detail['quantity'] }}</td>
                                                                        <td class="border px-4 py-2">{{ $detail['label'] }}</td>
                                                                        <td class="border px-4 py-2">{{ $detail['user'] }}</td>
                                                                        <td class="border px-4 py-2">{{ $detail['scanned_at'] }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-gray-500">Please select a machine to view the data.</p> <!-- Message when no machine is selected -->
                @endif

            </div>
        </div>
    </div>

    <!-- Modal for long mould changes (>20m) -->
    <div id="longMouldModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">

        <!-- Modal Box -->
        <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="p-6 border-b bg-gradient-to-r from-red-600 to-rose-700 text-white rounded-t-lg">
                <h2 class="text-lg font-bold flex items-center space-x-2">
                    <span>⚠️ Mould Changes Exceeding 20 Minutes ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})</span>
                </h2>
            </div>

            <!-- Scrollable Content -->
            <div class="p-6 overflow-y-auto flex-1">
                @if(isset($longMouldChanges) && count($longMouldChanges) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-300 text-sm">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="border px-4 py-2 text-left">Machine</th>
                                    <th class="border px-4 py-2 text-left">Item Code</th>
                                    <th class="border px-4 py-2 text-left">Start Time</th>
                                    <th class="border px-4 py-2 text-left">End Time</th>
                                    <th class="border px-4 py-2 text-center">Predicted (min)</th>
                                    <th class="border px-4 py-2 text-center">Actual (min)</th>
                                    <th class="border px-4 py-2 text-left">PIC</th>
                                    <th class="border px-4 py-2 text-left">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($longMouldChanges as $log)
                                    <tr class="bg-red-50 hover:bg-red-100 transition-colors">
                                        <td class="border px-4 py-2 font-semibold text-slate-800">{{ $log['machine_name'] }}</td>
                                        <td class="border px-4 py-2 font-mono text-xs">{{ $log['item_code'] }}</td>
                                        <td class="border px-4 py-2 text-slate-600">
                                            {{ \Carbon\Carbon::parse($log['start_time'])
                                                ->setTimezone('Asia/Jakarta')
                                                ->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="border px-4 py-2 text-slate-600">
                                            {{ \Carbon\Carbon::parse($log['end_time'])
                                                ->setTimezone('Asia/Jakarta')
                                                ->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="border px-4 py-2 text-center text-slate-500">
                                            {{ $log['predicted_time'] }} Min
                                        </td>
                                        <td class="border px-4 py-2 text-center font-bold text-red-600">
                                            {{ $log['actual_time'] }} Min
                                        </td>
                                        <td class="border px-4 py-2">
                                            <div class="flex items-center space-x-2">
                                                <img src="{{ asset($log['pic_profile_path']) }}"
                                                     alt="{{ $log['pic'] }}"
                                                     class="w-6 h-6 rounded-full object-cover">
                                                <span>{{ $log['pic'] }}</span>
                                            </div>
                                        </td>
                                        <td class="border px-4 py-2 text-slate-700 italic">{{ $log['remark'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">No mould changes exceeding 20 minutes found for this day.</p>
                @endif
            </div>

            <!-- Footer -->
            <div class="p-4 border-t flex justify-end">
                <button id="closeLongMouldModal"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Close
                </button>
            </div>

        </div>
    </div>

    <!-- Modal for Zero Actual Production Hourly Remarks -->
    <div id="zeroActualModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">

        <!-- Modal Box -->
        <div class="bg-white rounded-2xl shadow-2xl w-11/12 md:w-3/4 max-h-[90vh] flex flex-col border border-gray-100 overflow-hidden transform transition-all duration-300 scale-100">

            <!-- Header -->
            <div class="p-6 border-b bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold flex items-center space-x-2">
                        <span>📉 Hourly Remarks (Actual Produksi = 0)</span>
                    </h2>
                    <p class="text-amber-100 text-xs mt-1">Jadwal Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</p>
                </div>
                <button id="closeZeroActualBtnTop" class="text-white hover:text-amber-200 transition-colors text-2xl font-semibold">&times;</button>
            </div>

            <!-- Scrollable Content -->
            <div class="p-6 overflow-y-auto flex-1 bg-slate-50">
                @if(isset($zeroActualRemarks) && count($zeroActualRemarks) > 0)
                    <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm bg-white">
                        <table class="w-full text-sm text-gray-700">
                            <thead class="bg-slate-100 border-b border-gray-200 text-slate-700 font-semibold uppercase text-xs tracking-wider sticky top-0 z-10">
                                <tr>
                                    <th class="px-5 py-3 text-center">Shift</th>
                                    <th class="px-5 py-3 text-left">Machine</th>
                                    <th class="px-5 py-3 text-left">Item Code</th>
                                    <th class="px-5 py-3 text-left">Item Name</th>
                                    <th class="px-5 py-3 text-center">Time Range</th>
                                    <th class="px-5 py-3 text-center">Target</th>
                                    <th class="px-5 py-3 text-center">Actual Production</th>
                                    <th class="px-5 py-3 text-center">NG</th>
                                    <th class="px-5 py-3 text-left">PIC</th>
                                    <th class="px-5 py-3 text-left">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($zeroActualRemarks as $remark)
                                    <tr class="hover:bg-amber-50/40 transition-colors">
                                        <td class="px-5 py-4 text-center">
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                Shift {{ $remark['shift'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 font-semibold text-slate-800">{{ $remark['machine_name'] }}</td>
                                        <td class="px-5 py-4 font-mono text-xs text-indigo-600 font-semibold">{{ $remark['item_code'] }}</td>
                                        <td class="px-5 py-4 text-slate-600 max-w-xs truncate" title="{{ $remark['item_name'] }}">{{ $remark['item_name'] }}</td>
                                        <td class="px-5 py-4 text-center font-medium text-slate-600 bg-slate-50/50">{{ $remark['time_range'] }}</td>
                                        <td class="px-5 py-4 text-center text-slate-500 font-mono">{{ $remark['target'] }}</td>
                                        <td class="px-5 py-4 text-center font-bold text-slate-700 font-mono">{{ $remark['actual_production'] }}</td>
                                        <td class="px-5 py-4 text-center font-bold text-rose-600 font-mono">{{ $remark['ng'] }}</td>
                                        <td class="px-5 py-4 relative">
                                            @if(($remark['pics_count'] ?? 1) > 1)
                                                <div class="relative inline-block pic-dropdown-container">
                                                    <button type="button" 
                                                        class="pic-dropdown-btn flex items-center space-x-2 text-left p-1 -m-1 rounded-lg hover:bg-indigo-50/80 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all cursor-pointer group"
                                                        title="Klik untuk melihat {{ $remark['pics_count'] }} PIC">
                                                        <div class="flex -space-x-2 overflow-hidden flex-shrink-0">
                                                            @foreach(array_slice($remark['pics'], 0, 2) as $p)
                                                                <img src="{{ asset($p['profile_path']) }}" alt="{{ $p['name'] }}" class="inline-block w-6 h-6 rounded-full ring-2 ring-white object-cover shadow-xs">
                                                            @endforeach
                                                        </div>
                                                        <div class="flex items-center space-x-1.5">
                                                            <span class="font-medium text-slate-700 text-xs group-hover:text-indigo-700 transition-colors">{{ $remark['pic'] }}</span>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-700 border border-indigo-200 group-hover:bg-indigo-200 transition-colors shadow-2xs">
                                                                +{{ count($remark['pics']) - 1 }}
                                                                <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </span>
                                                        </div>
                                                    </button>
                                                    <div class="pic-dropdown-menu hidden absolute left-0 top-full mt-2 z-50 w-60 bg-white rounded-xl shadow-2xl border border-slate-200 p-2.5 transition-all">
                                                        <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-100 px-1">
                                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Daftar PIC ({{ $remark['pics_count'] }})</span>
                                                        </div>
                                                        <div class="space-y-1">
                                                            @foreach($remark['pics'] as $p)
                                                                <div class="flex items-center space-x-2 p-1 rounded-lg hover:bg-slate-50">
                                                                    <img src="{{ asset($p['profile_path']) }}" alt="{{ $p['name'] }}" class="w-7 h-7 rounded-full object-cover border border-slate-200">
                                                                    <div class="min-w-0 flex-1">
                                                                        <div class="text-xs font-bold text-slate-800 truncate">{{ $p['name'] }}</div>
                                                                        <div class="text-[10px] text-indigo-600 font-semibold">{{ $p['role'] }}</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex items-center space-x-2">
                                                    <img src="{{ asset($remark['pic_profile_path']) }}"
                                                         alt="{{ $remark['pic'] }}"
                                                         class="w-7 h-7 rounded-full object-cover border border-gray-200 shadow-sm">
                                                    <span class="font-medium text-slate-700 text-xs">{{ $remark['pic'] }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-slate-600 italic whitespace-normal break-words min-w-[200px]">{{ $remark['remark'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                        <span class="text-4xl">🎉</span>
                        <p class="text-gray-500 font-medium mt-2">Tidak ada hourly remark dengan actual produksi 0 pada hari ini.</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="p-4 border-t bg-slate-50 flex justify-end">
                <button id="closeZeroActualModal"
                    class="bg-slate-600 hover:bg-slate-700 text-white px-5 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
                    Close
                </button>
            </div>

        </div>
    </div>


    <script type="module">
    $(document).ready(function() {
        // Handle delivery schedule button click
        $('.btn-delivery-schedule').click(function() {
            const itemCode = $(this).data('item-code');
            const itemName = $(this).data('item-name');
            const delSched = $(this).data('delsched');
            
            // Set modal title
            $('#modalItemCode').text(itemCode);
            
            // Populate table
            const tbody = $('#deliveryScheduleBody');
            tbody.empty();
            
            if (delSched && delSched.length > 0) {
                delSched.forEach(function(sched) {
                    const statusClass = sched.status === 'completed' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800';
                    const row = `
                        <tr class="hover:bg-gray-50">
                            <td class="border px-4 py-2">${sched.delivery_date || '-'}</td>
                            <td class="border px-4 py-2">${sched.item_code || '-'}</td>
                            <td class="border px-4 py-2">${sched.item_name || '-'}</td>
                            <td class="border px-4 py-2 text-right">${sched.outstanding || '-'}</td>
                            <td class="border px-4 py-2">${sched.packaging_code || '-'}</td>
                            <td class="border px-4 py-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                                    ${sched.status || '-'}
                                </span>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.html(`
                    <tr>
                        <td colspan="6" class="border px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No delivery schedule available</p>
                        </td>
                    </tr>
                `);
            }
            
            // Show modal
            $('#deliveryScheduleModal').removeClass('hidden');
        });
        
        // Close modal handlers
        $('#closeDeliverySchedule, #closeDeliveryScheduleBtn').click(function() {
            $('#deliveryScheduleModal').addClass('hidden');
        });
        
        // Close when clicking outside modal
        $('#deliveryScheduleModal').click(function(e) {
            if (e.target.id === 'deliveryScheduleModal') {
                $(this).addClass('hidden');
            }
        });
        
        // Close on ESC key
        $(document).keydown(function(e) {
            if (e.key === 'Escape') {
                $('#deliveryScheduleModal').addClass('hidden');
            }
        });
    });
</script>


    <!-- <script type=module>
        $(document).ready(function() {
        $('#showDetails').click(function() {
            $('#detailModal').removeClass('hidden'); // Show modal
        });

        $('#closeModal').click(function() {
            $('#detailModal').addClass('hidden'); // Hide modal
        });
    }); -->



    <!-- JS -->
<script>
    const showBtn = document.getElementById('showDetails');
    const modal = document.getElementById('detailModal');
    const closeBtn = document.getElementById('closeModal');

    if (showBtn) {
        showBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // lock background scroll
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        });
    }

    if (modal) {
        // Click backdrop to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    }

    // Long Mould Changes Modal JS
    const showLongMouldBtn = document.getElementById('showLongMouldModal');
    const longMouldModal = document.getElementById('longMouldModal');
    const closeLongMouldBtn = document.getElementById('closeLongMouldModal');

    if (showLongMouldBtn && longMouldModal) {
        showLongMouldBtn.addEventListener('click', () => {
            longMouldModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        });

        if (closeLongMouldBtn) {
            closeLongMouldBtn.addEventListener('click', () => {
                longMouldModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }

        longMouldModal.addEventListener('click', (e) => {
            if (e.target === longMouldModal) {
                longMouldModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    }

    // Zero Actual Production Modal JS
    const showZeroActualBtn = document.getElementById('showZeroActualModal');
    const zeroActualModal = document.getElementById('zeroActualModal');
    const closeZeroActualBtn = document.getElementById('closeZeroActualModal');
    const closeZeroActualBtnTop = document.getElementById('closeZeroActualBtnTop');

    if (showZeroActualBtn && zeroActualModal) {
        showZeroActualBtn.addEventListener('click', () => {
            zeroActualModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        });

        const closeZeroActual = () => {
            zeroActualModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        if (closeZeroActualBtn) {
            closeZeroActualBtn.addEventListener('click', closeZeroActual);
        }
        if (closeZeroActualBtnTop) {
            closeZeroActualBtnTop.addEventListener('click', closeZeroActual);
        }

        zeroActualModal.addEventListener('click', (e) => {
            if (e.target === zeroActualModal) {
                closeZeroActual();
            }
        });
    }

    // ESC to close all modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (modal) modal.classList.add('hidden');
            if (longMouldModal) longMouldModal.classList.add('hidden');
            if (zeroActualModal) zeroActualModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".toggle-details").forEach(button => {
            button.addEventListener("click", function () {
                let spkCode = this.getAttribute("data-spk");
                let detailsRow = document.getElementById("details-" + spkCode);
                detailsRow.classList.toggle("hidden");
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".toggle-hourly-details").forEach(button => {
            button.addEventListener("click", function () {
                let hour = this.getAttribute("data-hour");
                let detailsRow = document.getElementById("details-" + hour);
                detailsRow.classList.toggle("hidden");
            });
        });
    });


    document.addEventListener("DOMContentLoaded", function () {
    const photoModal = document.getElementById("photoModal");
    const modalPhoto = document.getElementById("modalPhoto");
    const closePhotoModal = document.getElementById("closePhotoModal");

    document.querySelectorAll(".show-photo").forEach(button => {
        button.addEventListener("click", function () {
            let photoUrl = this.getAttribute("data-photo");
            if (photoUrl) {
                modalPhoto.src = photoUrl;
                photoModal.classList.remove("hidden");
            }
        });
    });

    // Close when clicking the close button
    closePhotoModal.addEventListener("click", function () {
        photoModal.classList.add("hidden");
    });

    // Close when clicking outside the modal
    photoModal.addEventListener("click", function (event) {
        if (event.target === photoModal) {
            photoModal.classList.add("hidden");
        }
    });

    // Clicking the image zooms in
    modalPhoto.addEventListener("click", function () {
        this.classList.toggle("scale-150");
    });
});


    document.querySelectorAll('.toggle-hourly-details').forEach(button => {
        button.addEventListener('click', () => {
            const hour = button.dataset.hour;
            const item = button.dataset.item;
            const row = document.getElementById(`details-${hour}-${item}`);
            if (row) {
                row.classList.toggle('hidden');
            }
        });
    });


    document.getElementById('item_code').addEventListener('change', function() {
        const itemCode = this.value;
        const machineInfoDiv = document.getElementById('machine-info');

        if (!itemCode) {
            machineInfoDiv.innerHTML = '';
            return;
        }

        fetch(`/get-machines-by-item?item_code=${itemCode}`)
            .then(res => res.json())
            .then(data => {
                machineInfoDiv.innerHTML = ''; // Kosongkan dulu

                if (data.length === 0) {
                    machineInfoDiv.innerHTML = '<p class="text-gray-500">No machines found for this item code.</p>';
                    return;
                }

                data.forEach(row => {
                    const card = document.createElement('div');
                    card.className = "bg-white shadow rounded-lg p-4 border border-gray-200 cursor-pointer hover:bg-gray-100 transition";

                    card.innerHTML = `
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">${row.machine}</h3>
                        <p class="text-sm text-gray-600">Date: <span class="font-medium">${row.date}</span></p>
                    `;

                    // 👇 Tambahkan event redirect on click
                    card.addEventListener('click', () => {
                        const url = new URL("{{ route('djoni.dashboard') }}", window.location.origin);
                        url.searchParams.set('machine_name', row.machine);
                        url.searchParams.set('date', row.date);
                        window.location.href = url.toString();
                    });

                    machineInfoDiv.appendChild(card);
                });
            });
    });


    function showNgDetails(ngDetails) {
        let container = document.getElementById("ngDetailList");
        let html = "";

        if (!ngDetails || ngDetails.length === 0) {
            html = `<p class="text-gray-500">No NG recorded.</p>`;
        } else {
            ngDetails.forEach(ng => {
                html += `
                    <div class="border p-2 rounded bg-gray-100">
                        <div class="font-semibold text-sm">${ng.ng_type}</div>
                        <div class="text-xs">Qty: ${ng.ng_quantity}</div>
                        <div class="text-xs text-gray-600">${ng.ng_remarks ?? ''}</div>
                    </div>
                `;
            });
        }

        container.innerHTML = html;

        document.getElementById("ngDetailModal").classList.remove("hidden");
        document.getElementById("ngDetailModal").classList.add("flex");
    }

    function closeNgDetailModal() {
        document.getElementById("ngDetailModal").classList.remove("flex");
        document.getElementById("ngDetailModal").classList.add("hidden");
    }

</script>

<!-- Modal Maintenance Checklist for Master Production Dashboard -->
<div id="masterMaintChecklistModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div id="masterMaintBackdrop" onclick="closeMasterMaintChecklistModal()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100">
            <!-- Header Modal -->
            <div class="bg-slate-900 text-white px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xl">
                        🛠️
                    </div>
                    <div>
                        <h3 class="text-base font-black tracking-tight uppercase italic text-white flex items-center gap-2">
                            Checklist Predictive Maintenance
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            Mesin: <span id="masterMaintMachineName" class="text-white font-bold">-</span> | Tanggal Laporan: <span id="masterMaintDisplayDate" class="text-indigo-300 font-bold">-</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeMasterMaintChecklistModal()" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-slate-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body Modal -->
            <div id="masterMaintModalBody" class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                <div class="py-12 text-center text-gray-400 font-bold text-xs flex flex-col items-center gap-2">
                    <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Mengambil data checklist...</span>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                <button type="button" onclick="closeMasterMaintChecklistModal()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-xl transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openMasterMaintChecklistModal(machineId, machineName, selectedDate) {
    const modal = document.getElementById('masterMaintChecklistModal');
    const machineNameEl = document.getElementById('masterMaintMachineName');
    const displayDateEl = document.getElementById('masterMaintDisplayDate');
    const bodyEl = document.getElementById('masterMaintModalBody');

    machineNameEl.textContent = machineName;
    displayDateEl.textContent = selectedDate;
    modal.classList.remove('hidden');

    bodyEl.innerHTML = `
        <div class="py-12 text-center text-gray-400 font-bold text-xs flex flex-col items-center gap-2">
            <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span>Mengambil data checklist Predictive Maintenance...</span>
        </div>
    `;

    fetch(`/maintenance-checklist/today/${machineId}?date=${selectedDate}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                bodyEl.innerHTML = `<div class="p-4 text-red-600 text-xs font-bold text-center">Gagal memuat data checklist.</div>`;
                return;
            }

            displayDateEl.textContent = data.display_date;

            if (!data.is_filled || !data.header) {
                bodyEl.innerHTML = `
                    <div class="py-12 px-6 text-center bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
                        <div class="text-3xl">⏳</div>
                        <h4 class="text-sm font-black text-amber-800 uppercase">Belum Ada Data Checklist</h4>
                        <p class="text-xs text-amber-700 font-medium max-w-md mx-auto">
                            Checklist Predictive Maintenance untuk mesin <span class="font-bold text-amber-900">${machineName}</span> pada tanggal <span class="font-bold text-amber-900">${data.display_date}</span> belum diisi oleh tim Maintenance.
                        </p>
                    </div>
                `;
                return;
            }

            const header = data.header;
            const detailsMap = {};
            if (header.details) {
                header.details.forEach(d => { detailsMap[d.item_id] = d; });
            }

            let html = `
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200/80 mb-4">
                    <div>
                        <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Prepared BY (PIC Maintenance)</span>
                        <span class="font-black text-gray-900 uppercase text-sm block">${header.prepared_by || '-'}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Approved BY (Atasan)</span>
                        <span class="font-black text-slate-800 uppercase text-sm block">${header.approved_by || '-'}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Jam Pengecekan</span>
                        <span class="font-black text-indigo-600 text-sm block">${header.check_time || '-'}</span>
                    </div>
                </div>
            `;

            const periods = ['Daily', 'Weekly', 'Two weeks'];
            const periodBadges = {
                'Daily': 'bg-blue-100 text-blue-800 border-blue-200',
                'Weekly': 'bg-purple-100 text-purple-800 border-purple-200',
                'Two weeks': 'bg-orange-100 text-orange-800 border-orange-200',
            };

            periods.forEach(p => {
                const groupItems = data.items.filter(i => i.period === p);
                if (groupItems.length === 0) return;

                html += `
                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-2xs mb-4">
                        <div class="bg-gray-100/80 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black border ${periodBadges[p] || 'bg-gray-100'}">${p}</span>
                                <span>Pengecekan ${p} (${groupItems.length} Item)</span>
                            </span>
                        </div>
                        <div class="divide-y divide-gray-100 text-xs">
                `;

                groupItems.forEach(item => {
                    const d = detailsMap[item.id];
                    const val = d ? d.value : '-';
                    const isNormal = d ? d.is_normal : true;

                    let badgeHtml = '';
                    if (val === 'OK') {
                        badgeHtml = `<span class="px-3.5 py-1 rounded-xl bg-emerald-600 text-white font-black text-xs inline-block">✓ OK</span>`;
                    } else if (val === '-') {
                        badgeHtml = `<span class="px-3.5 py-1 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs inline-block border border-slate-200">- Lewati</span>`;
                    } else if (!isNormal || val === 'NG') {
                        badgeHtml = `<span class="px-3.5 py-1 rounded-xl bg-red-600 text-white font-black text-xs inline-block animate-pulse shadow-sm">⚠️ ${val} ${item.unit || ''} (ABNORMAL)</span>`;
                    } else {
                        badgeHtml = `<span class="px-3.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-black text-xs inline-block">✓ ${val} ${item.unit || ''} (Normal)</span>`;
                    }

                    html += `
                        <div class="p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-gray-50/50 transition-colors ${val !== '-' && (!isNormal || val === 'NG') ? 'bg-red-50/50' : ''}">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-gray-400 text-[10px] w-5">${item.sort_order}.</span>
                                    <span class="font-bold text-gray-800 text-xs">${item.item_name}</span>
                                </div>
                                <div class="ml-7 text-[10px] text-gray-400 font-medium">
                                    Standar: <span class="text-gray-600 font-semibold">${item.standard}</span> | Kriteria: <span class="text-gray-600 font-semibold">${item.kriteria}</span>
                                </div>
                            </div>
                            <div class="ml-7 md:ml-0">
                                ${badgeHtml}
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            });

            bodyEl.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            bodyEl.innerHTML = `<div class="p-4 text-red-600 text-xs font-bold text-center">Terjadi kesalahan server saat mengambil data.</div>`;
        });
}

function closeMasterMaintChecklistModal() {
    const modal = document.getElementById('masterMaintChecklistModal');
    if (modal) modal.classList.add('hidden');
}

// Global click handler to toggle PIC dropdowns in Hourly Remarks
document.addEventListener('click', function (e) {
    const toggleBtn = e.target.closest('.pic-dropdown-btn');
    const container = e.target.closest('.pic-dropdown-container');

    if (toggleBtn) {
        e.preventDefault();
        e.stopPropagation();
        const parent = toggleBtn.closest('.pic-dropdown-container');
        const menu = parent ? parent.querySelector('.pic-dropdown-menu') : null;
        if (!menu) return;

        const isOpen = !menu.classList.contains('hidden');

        // Close all open dropdowns first
        document.querySelectorAll('.pic-dropdown-menu').forEach(m => m.classList.add('hidden'));

        // Toggle current dropdown
        if (!isOpen) {
            menu.classList.remove('hidden');
        }
    } else if (!container) {
        // Clicked outside any PIC dropdown container, close all
        document.querySelectorAll('.pic-dropdown-menu').forEach(m => m.classList.add('hidden'));
    }
});
</script>
</x-dashboard-layout>
