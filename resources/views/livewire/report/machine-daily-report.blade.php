<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic">
                    Laporan <span class="text-blue-600">Produksi</span> Harian
                </h1>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">
                    PT. DAIJO INDUSTRIAL - PLASTIC INJECTION DEPARTMENT
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Selector --}}
                <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider mb-1">Tanggal Laporan</span>
                    <input type="date" wire:model.live="selectedDate" class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-700 focus:ring-blue-500 focus:border-blue-500 min-w-[150px]">
                </div>

                {{-- Machine Selector (Only for PPIC/Admin View) --}}
                @if($isPpicView)
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider mb-1">Pilih Mesin</span>
                        <select wire:model.live="selectedMachineId" class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-700 focus:ring-blue-500 focus:border-blue-500 min-w-[180px]">
                            <option value="">-- Pilih Mesin --</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->id }}">{{ $machine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider mb-1">Mesin Aktif</span>
                        <div class="bg-blue-50 text-blue-700 border border-blue-100 font-black uppercase text-xs tracking-widest rounded-xl px-6 py-3.5">
                            {{ $selectedMachine?->name ?? '-' }}
                        </div>
                    </div>
                @endif

                {{-- Item Code Selector Dropdown --}}
                <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider mb-1">Filter Item Code</span>
                    <select wire:model.live="selectedItemCode" class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-700 focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                        <option value="">-- Semua Item --</option>
                        @foreach($availableItems as $code => $name)
                            <option value="{{ $code }}">{{ $code }} ({{ Str::limit($name, 20) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Export Excel Button --}}
                <div class="flex flex-col justify-end">
                    <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider mb-1">&nbsp;</span>
                    <a href="{{ route('ppic.machine-daily-report.export', ['date' => $selectedDate, 'machine_id' => $selectedMachineId]) }}" 
                       target="_blank"
                       class="inline-flex items-center space-x-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Export Excel (Format PPIC)</span>
                    </a>
                </div>
            </div>
        </div>

        @if(!$selectedMachineId)
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 text-center text-sm font-bold text-yellow-800 shadow-sm">
                Silakan pilih mesin terlebih dahulu untuk menampilkan laporan produksi.
            </div>
        @else
            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                @foreach([
                    ['label' => 'Total Target (Pcs)', 'value' => number_format($totals['planned_target']), 'text_color' => 'text-blue-600'],
                    ['label' => 'Total OK (Pcs)', 'value' => number_format($totals['actual_ok']), 'text_color' => 'text-green-600'],
                    ['label' => 'Total NG (Pcs)', 'value' => number_format($totals['actual_ng']), 'text_color' => 'text-red-600'],
                    ['label' => 'Total Produksi (Pcs)', 'value' => number_format($totals['total_produced']), 'text_color' => 'text-orange-600'],
                ] as $stat)
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $stat['label'] }}</span>
                        <span class="text-2xl font-black {{ $stat['text_color'] }} tracking-tighter">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Operator Names Info Box --}}
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-8">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Operator Shift Kerja</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach([
                        1 => ['title' => 'SHIFT I', 'border' => 'border-amber-500', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'border_pill' => 'border-amber-200/60', 'desc' => 'Pagi (07:30 - 15:30)'],
                        2 => ['title' => 'SHIFT II', 'border' => 'border-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'border_pill' => 'border-blue-200/60', 'desc' => 'Siang (15:30 - 23:30)'],
                        3 => ['title' => 'SHIFT III', 'border' => 'border-indigo-600', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-800', 'border_pill' => 'border-indigo-200/60', 'desc' => 'Malam (23:30 - 07:30)'],
                    ] as $shiftNum => $style)
                        <div class="bg-white p-5 rounded-2xl border-l-4 {{ $style['border'] }} border border-gray-100 shadow-sm flex flex-col justify-between gap-3 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between">
                                <span class="inline-block px-3 py-1 {{ $style['bg'] }} {{ $style['text'] }} border {{ $style['border_pill'] }} font-black text-[9px] tracking-widest rounded-full uppercase">
                                    {{ $style['title'] }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-bold tracking-tight">{{ $style['desc'] }}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1">
                                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100/50">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="truncate">
                                    <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider block">Operator PIC</span>
                                    <span class="text-xs font-bold text-gray-700 block truncate" title="{{ $operatorNames[$shiftNum] }}">
                                        {{ $operatorNames[$shiftNum] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 1. Daily Production Plan --}}
            <div x-data="{ openPlan: true }" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div @click="openPlan = !openPlan" class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between cursor-pointer select-none hover:bg-gray-100/80 transition-colors">
                    <h2 class="text-xs font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
                        <span>📋 Rencana Kerja Harian (Daily Plan)</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider" x-text="openPlan ? 'Tutup' : 'Buka'"></span>
                        <svg :class="openPlan ? 'rotate-180' : ''" class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <div x-show="openPlan" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest">Shift</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest">Item Code</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest">Part Name</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Cycle Time</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Lot Material</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Lot Accessories</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Target</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Scanned (OK)</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Actual Qty</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Achievement</th>
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($dailyPlans as $plan)
                                @php
                                    $scannedOk = $plan->scannedData->sum('quantity');
                                    $hourlySum = (int) $plan->hourlyRemarks->sum('actual_production');
                                    $actualQty = $hourlySum > 0
                                        ? $hourlySum
                                        : ((!empty($plan->actual_quantity) && $plan->actual_quantity > 0) ? (int)$plan->actual_quantity : 0);
                                    $achievePercent = $plan->quantity > 0 ? round(($scannedOk / $plan->quantity) * 100) : 0;
                                    
                                    $cycleTime = (!empty($plan->temporal_cycle_time) && $plan->temporal_cycle_time > 0)
                                        ? $plan->temporal_cycle_time
                                        : ($plan->masterItem?->cycle_time ?? null);

                                    // Extract distinct item codes (Pair items: Kanan & Kiri) like Excel Export
                                    $scannedItemCodes = $plan->scannedData->pluck('item_code')->filter()->unique()->toArray();
                                    $distinctItemCodes = array_values(array_unique(array_filter(array_merge([$plan->item_code], $scannedItemCodes))));
                                    $isPair = count($distinctItemCodes) > 1;
                                @endphp
                                <tbody x-data="{ showPairDetail: false }" class="divide-y divide-gray-100">
                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                        <td class="py-4 px-6 text-xs font-black text-gray-700">Shift {{ $plan->shift }}</td>
                                        <td class="py-4 px-6 text-xs font-bold text-gray-800">
                                            <div>{{ $plan->item_code }}</div>
                                            @if($isPair)
                                                <button @click="showPairDetail = !showPairDetail" type="button" class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 text-[10px] font-black rounded-md transition-colors cursor-pointer shadow-2xs">
                                                    <span x-show="!showPairDetail" class="flex items-center gap-1">
                                                        🔽 Detail Pair ({{ count($distinctItemCodes) }})
                                                    </span>
                                                    <span x-show="showPairDetail" class="flex items-center gap-1">
                                                        🔼 Tutup Detail
                                                    </span>
                                                </button>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-xs font-semibold text-gray-500">{{ optional($plan->masterItem)->item_name ?? '-' }}</td>
                                        <td class="py-4 px-6 text-xs font-bold text-center">
                                            @if($cycleTime)
                                                <span class="inline-block px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 font-extrabold rounded-lg text-xs">
                                                    {{ number_format((float)$cycleTime, 1) }}s
                                                </span>
                                            @else
                                                <span class="text-gray-400 italic text-[11px]">-</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-xs text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                @if(!empty($plan->material_lot))
                                                    <span class="inline-block px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 font-extrabold rounded-lg text-xs tracking-tight uppercase">
                                                        {{ $plan->material_lot }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300 italic text-[11px]">-</span>
                                                @endif
                                                <button wire:click="openLotModal({{ $plan->id }})" title="Set Lot Material" class="p-1 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-md transition-colors">
                                                    ✏️
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-center">
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                @forelse($plan->accessoryLots as $acc)
                                                    <span class="inline-block px-2 py-0.5 bg-purple-50 text-purple-800 border border-purple-200 font-extrabold rounded-lg text-[10px] tracking-tight">
                                                        {{ $acc->accessory_name }}: {{ $acc->accessory_lot }}
                                                    </span>
                                                @empty
                                                    <span class="text-gray-300 italic text-[11px]">-</span>
                                                @endforelse
                                                <button wire:click="openAccessoryModal({{ $plan->id }})" title="Kelola Lot Accessories" class="p-1 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-md transition-colors">
                                                    ⚙️
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs font-bold text-gray-700 text-center">{{ number_format($plan->quantity) }}</td>
                                        <td class="py-4 px-6 text-xs font-bold text-green-600 text-center">{{ number_format($scannedOk) }}</td>
                                        <td class="py-4 px-6 text-xs font-bold text-blue-600 text-center">{{ number_format($actualQty) }}</td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-24 bg-gray-100 h-2 rounded-full overflow-hidden">
                                                    <div class="bg-green-500 h-full rounded-full" style="width: {{ min($achievePercent, 100) }}%"></div>
                                                </div>
                                                <span class="text-xs font-black text-gray-600">{{ $achievePercent }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            @if($plan->is_done == 1)
                                                <span class="inline-block px-3 py-1 bg-green-50 text-green-700 border border-green-100 font-bold uppercase text-[9px] tracking-widest rounded-lg">Done</span>
                                            @elseif($plan->is_done == 99)
                                                <span class="inline-block px-3 py-1 bg-red-50 text-red-700 border border-red-100 font-bold uppercase text-[9px] tracking-widest rounded-lg">Expired</span>
                                            @else
                                                <span class="inline-block px-3 py-1 bg-orange-50 text-orange-700 border border-orange-100 font-bold uppercase text-[9px] tracking-widest rounded-lg">Active</span>
                                            @endif
                                        </td>
                                    </tr>

                                    @if($isPair)
                                        <tr x-show="showPairDetail" x-transition class="bg-indigo-50/30">
                                            <td colspan="11" class="py-3 px-6">
                                                <div class="bg-white rounded-xl p-3.5 border border-indigo-100 shadow-sm">
                                                    <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-indigo-50">
                                                        <span class="text-xs font-black text-indigo-900 flex items-center gap-1.5 uppercase tracking-wide">
                                                            📦 Detail Rincian Item (Pair / Multiple Item Code Breakdown):
                                                        </span>
                                                        <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                                            {{ count($distinctItemCodes) }} Sub-Item
                                                        </span>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full text-xs text-gray-700 border border-gray-100 rounded-lg">
                                                            <thead>
                                                                <tr class="bg-indigo-50/50 text-indigo-900 uppercase text-[9px] font-black border-b border-indigo-100">
                                                                    <th class="py-2.5 px-4 text-left">Item Code</th>
                                                                    <th class="py-2.5 px-4 text-left">Part Name</th>
                                                                    <th class="py-2.5 px-4 text-center">Cycle Time</th>
                                                                    <th class="py-2.5 px-4 text-center">Scanned (OK)</th>
                                                                    <th class="py-2.5 px-4 text-center">Actual Qty</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100">
                                                                @foreach($distinctItemCodes as $subCode)
                                                                    @php
                                                                        $subMaster = \App\Models\MasterListItem::where('item_code', $subCode)->first();
                                                                        $subPartName = $subMaster?->item_name ?? $subMaster?->part_name ?? ($subCode === $plan->item_code ? (optional($plan->masterItem)->item_name ?? '-') : '-');
                                                                        $subScanned = $plan->scannedData->where('item_code', $subCode)->sum('quantity');
                                                                        
                                                                        $pairCount = count($distinctItemCodes);
                                                                        $subActual = ($actualQty > 0)
                                                                            ? ($pairCount > 1 ? (int) round($actualQty / $pairCount) : $actualQty)
                                                                            : 0;

                                                                        $subCT = $subMaster?->cycle_time ?? ($subCode === $plan->item_code ? $cycleTime : null);
                                                                    @endphp
                                                                    <tr class="hover:bg-indigo-50/20 transition-colors">
                                                                        <td class="py-2.5 px-4 font-black text-gray-800 flex items-center gap-2">
                                                                            <span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span>
                                                                            {{ $subCode }}
                                                                        </td>
                                                                        <td class="py-2.5 px-4 font-bold text-gray-600">{{ $subPartName }}</td>
                                                                        <td class="py-2.5 px-4 text-center font-extrabold text-indigo-700">
                                                                            {{ $subCT ? number_format((float)$subCT, 1).'s' : '-' }}
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-center font-black text-green-600">
                                                                            {{ number_format($subScanned) }}
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-center font-black text-blue-600">
                                                                            {{ number_format($subActual) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-xs font-bold text-gray-400">Tidak ada rencana produksi untuk tanggal ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Detail Jam Produksi & Breakdown NG per Item --}}
            <div x-data="{ openSection: true }" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div @click="openSection = !openSection" class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-2 cursor-pointer select-none hover:bg-gray-100/80 transition-colors">
                    <div>
                        <h2 class="text-xs font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
                            <span>⏱️ Rincian Jam Produksi & Breakdown NG per Item</span>
                        </h2>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-0.5">Detail per jam operasional, target, hasil actual OK, PIC, serta rincian jenis NG</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($selectedItemCode)
                            <div class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1.5 rounded-xl border border-blue-100">
                                Item Filter: <span class="font-black">{{ $selectedItemCode }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1.5 text-gray-500">
                            <span class="text-[10px] font-bold uppercase tracking-wider" x-text="openSection ? 'Tutup' : 'Buka'"></span>
                            <svg :class="openSection ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div x-show="openSection" class="p-6 space-y-6">
                    @forelse($itemDetailRows as $itemGroup)
                        <div x-data="{ openCard: true }" class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-sm">
                            {{-- Header Item --}}
                            @php
                                $totalActual = collect($itemGroup['slots'])->sum('actual');
                                $totalNg = collect($itemGroup['slots'])->sum('ng');
                            @endphp
                            <div @click="openCard = !openCard" class="bg-gray-100/70 px-5 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 cursor-pointer select-none hover:bg-gray-200/60 transition-colors">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="bg-blue-600 text-white font-black text-[10px] uppercase px-3 py-1 rounded-lg">
                                        Shift {{ $itemGroup['shift'] }}
                                    </span>
                                    <div>
                                        <span class="font-black text-sm text-gray-900 tracking-tight">{{ $itemGroup['item_code'] }}</span>
                                        <span class="text-xs font-bold text-gray-500 ml-2">({{ $itemGroup['part_name'] }})</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <span class="px-3 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 font-black text-xs inline-flex items-center gap-1 shadow-2xs">
                                            <span>Total Actual:</span>
                                            <span class="text-blue-800 font-extrabold">{{ number_format($totalActual) }}</span>
                                        </span>
                                        <span class="px-3 py-1 rounded-xl {{ $totalNg > 0 ? 'bg-red-600 text-white font-black animate-pulse shadow-sm' : 'bg-gray-100 text-gray-600 border border-gray-200 font-bold' }} text-xs inline-flex items-center gap-1">
                                            <span>Total NG:</span>
                                            <span class="font-extrabold">{{ number_format($totalNg) }}</span>
                                        </span>
                                    </div>
                                    <div class="text-xs font-bold text-gray-500 border-l border-gray-300 pl-3">
                                        Customer: <span class="text-gray-800 font-bold">{{ $itemGroup['customer'] }}</span>
                                    </div>
                                    <svg :class="openCard ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>

                            {{-- Table Rincian Jam --}}
                            <div x-show="openCard" class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[900px]">
                                    <thead>
                                        <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-wider border-b border-gray-200">
                                            <th class="py-3 px-4 w-[160px]">Jam Operasional</th>
                                            <th class="py-3 px-3 text-center w-[90px]">Target</th>
                                            <th class="py-3 px-3 text-center w-[100px]">Actual (OK)</th>
                                            <th class="py-3 px-3 text-center w-[90px]">Total NG</th>
                                            <th class="py-3 px-4">Rincian Jenis Defect / NG</th>
                                            <th class="py-3 px-4 w-[160px]">Operator PIC</th>
                                            <th class="py-3 px-4">Kendala / Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-xs">
                                        @forelse($itemGroup['slots'] as $slot)
                                            <tr class="hover:bg-blue-50/20 transition-colors">
                                                <td class="py-3 px-4 font-black text-gray-800 tracking-tight">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        {{ $slot['time'] }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 text-center font-bold text-gray-600">
                                                    {{ number_format($slot['target']) }}
                                                </td>
                                                <td class="py-3 px-3 text-center font-black text-blue-600">
                                                    {{ number_format($slot['actual']) }}
                                                </td>
                                                <td class="py-3 px-3 text-center">
                                                    @if($slot['ng'] > 0)
                                                        <span class="bg-red-100 text-red-700 font-black px-2.5 py-1 rounded-full text-xs inline-block">
                                                            {{ $slot['ng'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 font-bold">0</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if(!empty($slot['ng_list']))
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach($slot['ng_list'] as $ngDetail)
                                                                <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-200 font-bold text-[11px] px-2 py-1 rounded-md shadow-2xs">
                                                                    <span>{{ $ngDetail['type'] }}:</span>
                                                                    <span class="font-black text-red-800">{{ $ngDetail['qty'] }} Pcs</span>
                                                                    @if(!empty($ngDetail['remarks']))
                                                                        <span class="text-[10px] text-red-500 font-normal">({{ $ngDetail['remarks'] }})</span>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @elseif($slot['ng'] > 0)
                                                        <span class="text-red-500 font-bold italic text-[11px]">Ada {{ $slot['ng'] }} NG (Tipe belum dirinci)</span>
                                                    @else
                                                        <span class="text-gray-400 text-[11px] font-medium">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 font-bold text-gray-700">
                                                    {{ $slot['pic'] }}
                                                </td>
                                                <td class="py-3 px-4 text-gray-600 font-medium">
                                                    {{ $slot['remark'] ?: '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="py-4 text-center text-xs font-bold text-gray-400 italic">Belum ada rincian jam kerja terinput.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs font-bold text-gray-400">
                            Tidak ada data rincian item untuk filter saat ini.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 3. Defective Analysis --}}
            <div x-data="{ openDefect: true }" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div @click="openDefect = !openDefect" class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between cursor-pointer select-none hover:bg-gray-100/80 transition-colors">
                    <h2 class="text-xs font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
                        <span>📊 Plastic Part Defective Analysis (NG Matrix)</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider" x-text="openDefect ? 'Tutup' : 'Buka'"></span>
                        <svg :class="openDefect ? 'rotate-180' : ''" class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <div x-show="openDefect" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1200px]">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest sticky left-0 bg-gray-50 z-10 w-[120px]">Shift</th>
                                @foreach($ngTypes as $type)
                                    <th class="py-4 px-3 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">{{ $type->ng_type }}</th>
                                @endforeach
                                <th class="py-4 px-6 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center font-bold text-red-600 bg-red-50/50 w-[100px]">Total NG</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach([1, 2, 3] as $shift)
                                @php
                                    $shiftTotal = 0;
                                @endphp
                                <tr class="hover:bg-gray-50/30 transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-700 sticky left-0 bg-white shadow-sm border-r border-gray-100 w-[120px]">Shift {{ $shift }}</td>
                                    @foreach($ngTypes as $type)
                                        @php
                                            $qty = $defectMatrix[$shift][$type->id] ?? 0;
                                            $shiftTotal += $qty;
                                        @endphp
                                        <td class="py-4 px-3 text-xs text-center {{ $qty > 0 ? 'font-black text-red-600 bg-red-50/10' : 'text-gray-300' }}">
                                            {{ $qty > 0 ? number_format($qty) : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="py-4 px-6 text-xs font-black text-red-600 text-center bg-red-50/30">
                                        {{ $shiftTotal > 0 ? number_format($shiftTotal) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. Output Per Jam --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                @foreach([1, 2, 3] as $shift)
                    @php
                        $shiftTargetSum = 0;
                        $shiftActualSum = 0;
                        $shiftNgSum = 0;
                    @endphp
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-4 bg-blue-50/50 border-b border-blue-50 flex items-center justify-between">
                                <h2 class="text-xs font-black text-blue-800 uppercase tracking-widest">Output Shift {{ $shift }}</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 border-b border-gray-100">
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest text-center w-[50px]">Jam</th>
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest">Waktu</th>
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest text-center">Tgt</th>
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest text-center">OK</th>
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest text-center">NG</th>
                                            <th class="py-3 px-4 text-[8px] font-black text-gray-400 uppercase tracking-widest">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($hourlyOutput[$shift] as $hourIdx => $slot)
                                            @php
                                                $shiftTargetSum += $slot['target'];
                                                $shiftActualSum += $slot['actual'];
                                                $shiftNgSum += $slot['ng'];
                                            @endphp
                                            <tr class="hover:bg-gray-50/30 transition-colors">
                                                <td class="py-3 px-4 text-xs font-black text-gray-400 text-center">{{ $hourIdx }}</td>
                                                <td class="py-3 px-4 text-xs font-bold text-gray-700 whitespace-nowrap">{{ $slot['time'] }}</td>
                                                <td class="py-3 px-4 text-xs font-bold text-gray-500 text-center">{{ number_format($slot['target']) }}</td>
                                                <td class="py-3 px-4 text-xs font-black text-green-600 text-center">{{ number_format($slot['actual']) }}</td>
                                                <td class="py-3 px-4 text-xs font-black text-red-500 text-center">{{ number_format($slot['ng']) }}</td>
                                                <td class="py-3 px-4 text-xs text-gray-500 max-w-[120px] truncate" title="{{ $slot['remark'] }}">{{ $slot['remark'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Footer Total per Shift --}}
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <span class="text-[7px] font-black uppercase text-gray-400 tracking-wider block">Total Tgt</span>
                                <span class="text-xs font-black text-gray-700">{{ number_format($shiftTargetSum) }}</span>
                            </div>
                            <div>
                                <span class="text-[7px] font-black uppercase text-green-400 tracking-wider block">Total OK</span>
                                <span class="text-xs font-black text-green-600">{{ number_format($shiftActualSum) }}</span>
                            </div>
                            <div>
                                <span class="text-[7px] font-black uppercase text-red-400 tracking-wider block">Total NG</span>
                                <span class="text-xs font-black text-red-600">{{ number_format($shiftNgSum) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        
    </div>

    {{-- Modal Detail Maintenance Checklist --}}
    @if($showChecklistModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" wire:click="closeChecklistModal"></div>
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
                                    Mesin: <span class="text-white font-bold">{{ $selectedMachine?->name ?? '-' }}</span> | Tanggal Laporan: <span class="text-indigo-300 font-bold">{{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeChecklistModal" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        @if($checklistHeader)
                            <!-- Info Signatures -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200/80">
                                <div>
                                    <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Prepared BY (PIC Maintenance)</span>
                                    <span class="font-black text-gray-900 uppercase text-sm block">{{ $checklistHeader->prepared_by }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Approved BY (Atasan)</span>
                                    <span class="font-black text-slate-800 uppercase text-sm block">{{ $checklistHeader->approved_by ?: '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Jam Pengecekan</span>
                                    <span class="font-black text-indigo-600 text-sm block">{{ $checklistHeader->check_time ?: '-' }}</span>
                                </div>
                            </div>

                            <!-- 17 Items Detail List -->
                            @php
                                $detailsByItem = $checklistHeader->details->keyBy('item_id');
                                $periods = ['Daily', 'Weekly', 'Two weeks'];
                                $periodBadges = [
                                    'Daily' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'Weekly' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'Two weeks' => 'bg-orange-100 text-orange-800 border-orange-200',
                                ];
                            @endphp

                            @foreach($periods as $p)
                                @php
                                    $groupItems = $checklistItems->filter(fn($i) => $i->period === $p);
                                @endphp
                                @if($groupItems->count() > 0)
                                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                                        <div class="bg-gray-100/80 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                            <span class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black border {{ $periodBadges[$p] ?? 'bg-gray-100' }}">{{ $p }}</span>
                                                <span>Pengecekan {{ $p }} ({{ $groupItems->count() }} Item)</span>
                                            </span>
                                        </div>
                                        <div class="divide-y divide-gray-100 text-xs">
                                            @foreach($groupItems as $item)
                                                @php
                                                    $d = $detailsByItem->get($item->id);
                                                    $val = $d ? $d->value : '-';
                                                    $isNormal = $d ? $d->is_normal : true;
                                                @endphp
                                                <div class="p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-gray-50/50 transition-colors {{ $val !== '-' && (!$isNormal || $val === 'NG') ? 'bg-red-50/50' : '' }}">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-black text-gray-400 text-[10px] w-5">{{ $item->sort_order }}.</span>
                                                            <span class="font-bold text-gray-800 text-xs">{{ $item->item_name }}</span>
                                                        </div>
                                                        <div class="ml-7 text-[10px] text-gray-400 font-medium">
                                                            Standar: <span class="text-gray-600 font-semibold">{{ $item->standard }}</span> | Kriteria: <span class="text-gray-600 font-semibold">{{ $item->kriteria }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-7 md:ml-0">
                                                        @if($val === 'OK')
                                                            <span class="px-3.5 py-1 rounded-xl bg-emerald-600 text-white font-black text-xs inline-block">
                                                                ✓ OK
                                                            </span>
                                                        @elseif($val === '-')
                                                            <span class="px-3.5 py-1 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs inline-block border border-slate-200">
                                                                - Lewati
                                                            </span>
                                                        @elseif(!$isNormal || $val === 'NG')
                                                            <span class="px-3.5 py-1 rounded-xl bg-red-600 text-white font-black text-xs inline-block animate-pulse shadow-sm">
                                                                ⚠️ {{ $val }} {{ $item->unit }} (ABNORMAL)
                                                            </span>
                                                        @else
                                                            <span class="px-3.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-black text-xs inline-block">
                                                                ✓ {{ $val }} {{ $item->unit }} (Normal)
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="py-12 px-6 text-center bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
                                <div class="text-3xl">⏳</div>
                                <h4 class="text-sm font-black text-amber-800 uppercase">Belum Ada Data Checklist</h4>
                                <p class="text-xs text-amber-700 font-medium max-w-md mx-auto">
                                    Checklist Predictive Maintenance untuk mesin <span class="font-bold text-amber-900">{{ $selectedMachine?->name ?? 'ini' }}</span> pada tanggal <span class="font-bold text-amber-900">{{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}</span> belum diisi oleh tim Maintenance.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer Modal -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                        <button type="button" wire:click="closeChecklistModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-xl transition-colors">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif

    <!-- Modal Set Lot Material -->
    @if($showLotModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" wire:click="closeLotModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-amber-600 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🏷️</span>
                            <h3 class="text-sm font-black uppercase tracking-wider">Set Kode Lot Material</h3>
                        </div>
                        <button type="button" wire:click="closeLotModal" class="text-amber-100 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveMaterialLot" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-500 mb-1">Kode Lot Material (Alphanumeric)</label>
                            <input type="text" wire:model="editingMaterialLot" placeholder="Contoh: LOT-2026-A123" class="w-full text-sm font-bold uppercase rounded-xl border-gray-200 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-2xs">
                            <p class="text-[11px] text-gray-400 font-medium mt-1">Masukkan kode lot bahan baku material yang digunakan.</p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeLotModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl shadow-xs transition-colors">
                                Simpan Lot Material
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Kelola Lot Accessories -->
    @if($showAccessoryModal && $selectedPlanIdForAcc)
        @php
            $targetPlan = $dailyPlans->firstWhere('id', $selectedPlanIdForAcc);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" wire:click="closeAccessoryModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-purple-900 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⚙️</span>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider">Kelola Lot Accessories</h3>
                                <p class="text-[11px] text-purple-300">Item: {{ $targetPlan?->item_code }} (Shift {{ $targetPlan?->shift }})</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeAccessoryModal" class="text-purple-300 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <!-- Form Tambah Baru -->
                        <form wire:submit.prevent="addAccessoryLot" class="bg-purple-50/60 p-4 rounded-2xl border border-purple-100 space-y-3">
                            <span class="text-xs font-black uppercase text-purple-900 tracking-wider block">Tambah Lot Accessory Baru</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-1">Jenis Accessories</label>
                                    <input type="text" wire:model="newAccName" placeholder="e.g. Screw / Label / Box" class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-1">Kode Lot Accessories</label>
                                    <input type="text" wire:model="newAccLot" placeholder="e.g. LOT-ACC#123/B" class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs">
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-purple-700 hover:bg-purple-800 text-white font-black text-xs rounded-xl shadow-2xs transition-colors">
                                    + Tambah Accessory
                                </button>
                            </div>
                        </form>

                        <!-- List Accessories Terdaftar -->
                        <div>
                            <span class="text-xs font-black uppercase text-gray-600 tracking-wider block mb-2">Daftar Lot Accessories ({{ count($targetPlan?->accessoryLots ?? []) }})</span>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @forelse($targetPlan?->accessoryLots ?? [] as $acc)
                                    <div class="flex items-center justify-between bg-gray-50 px-3.5 py-2.5 rounded-xl border border-gray-200/80 text-xs">
                                        <div>
                                            <span class="font-black text-gray-800">{{ $acc->accessory_name }}:</span>
                                            <span class="font-extrabold text-purple-800 ml-1">{{ $acc->accessory_lot }}</span>
                                        </div>
                                        <button type="button" wire:click="deleteAccessoryLot({{ $acc->id }})" class="text-red-500 hover:text-red-700 font-bold text-xs p-1">
                                            ✕ Hapus
                                        </button>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-gray-400 font-semibold text-xs italic bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                        Belum ada lot accessories yang ditambahkan.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                            <button type="button" wire:click="closeAccessoryModal" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-xl transition-colors">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
