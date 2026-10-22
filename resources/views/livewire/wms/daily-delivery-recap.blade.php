<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- 1. Top Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Rekap Harian Delivery FG</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Cutoff: 07:30 - 07:29 WIB
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">
                    Ringkasan seluruh pengiriman Finished Goods dalam 1 hari produksi (Shift 1, 2, dan 3 digabung).
                </p>
                <div class="text-xs text-blue-600 font-semibold mt-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Periode Aktif: {{ $recap['time_window_label'] }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('wms.pallet-form.create-delivery') }}" 
                   class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold flex items-center transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    + SCAN PALLET DELIVERY
                </a>

                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-xl text-sm font-bold flex items-center transition-all shadow-sm">
                    <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg wire:loading wire:target="exportExcel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>EXPORT EXCEL</span>
                </button>
            </div>
        </div>

        {{-- 2. Filter & Date Navigator Bar --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                {{-- Date Navigation --}}
                <div class="md:col-span-5 flex items-center gap-1.5">
                    <button type="button" wire:click="setYesterday" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition" 
                            title="Hari Sebelumnya">
                        ◀ Kemarin
                    </button>
                    <div class="relative flex-1">
                        <input type="date" wire:model.live="selectedDate"
                               class="w-full px-3 py-2 border border-blue-300 rounded-lg text-sm font-bold text-gray-800 bg-blue-50/40 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="button" wire:click="setToday" 
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs font-bold text-gray-700 transition"
                            title="Hari Produksi Ini">
                        Hari Ini
                    </button>
                    <button type="button" wire:click="setTomorrow" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition"
                            title="Hari Berikutnya">
                        Besok ▶
                    </button>
                </div>

                {{-- Shift Filter --}}
                <div class="md:col-span-2">
                    <select wire:model.live="shiftFilter" 
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">Semua Shift (1, 2, 3)</option>
                        <option value="1">Shift 1 (07:30 - 15:30)</option>
                        <option value="2">Shift 2 (15:30 - 23:30)</option>
                        <option value="3">Shift 3 (23:30 - 07:30)</option>
                    </select>
                </div>

                {{-- Delivery Name Filter --}}
                <div class="md:col-span-2">
                    <select wire:model.live="deliveryFilter" 
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">Semua Pengirim / Delivery</option>
                        @foreach($recap['available_deliveries'] as $delName)
                            <option value="{{ $delName }}">{{ $delName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Box --}}
                <div class="md:col-span-3">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search" 
                               placeholder="Cari part no, spk, pallet..."
                               class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-xs text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. KPI Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
            {{-- Total Qty Pcs --}}
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Delivery Qty</div>
                <div class="text-2xl font-black text-blue-700 font-mono">
                    {{ number_format($recap['kpis']['total_qty']) }} <span class="text-xs font-medium text-gray-400">pcs</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Akumulasi seluruh box</div>
            </div>

            {{-- Total Box --}}
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Box Ter-Scan</div>
                <div class="text-2xl font-black text-emerald-700 font-mono">
                    {{ number_format($recap['kpis']['total_boxes']) }} <span class="text-xs font-medium text-gray-400">box</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Total kardus / kemasan</div>
            </div>

            {{-- Total Pallet --}}
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Pallet Dibuat</div>
                <div class="text-2xl font-black text-purple-700 font-mono">
                    {{ number_format($recap['kpis']['total_pallets']) }} <span class="text-xs font-medium text-gray-400">pallet</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Pallet form terdaftar</div>
            </div>

            {{-- Total Model / Item Code --}}
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Item Code / Model</div>
                <div class="text-2xl font-black text-indigo-700 font-mono">
                    {{ number_format($recap['kpis']['total_models']) }} <span class="text-xs font-medium text-gray-400">item</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Varian produk dikirim</div>
            </div>

            {{-- Total Deliveries --}}
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs col-span-2 md:col-span-1">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pengirim / Driver</div>
                <div class="text-2xl font-black text-amber-700 font-mono">
                    {{ number_format($recap['kpis']['total_deliveries']) }} <span class="text-xs font-medium text-gray-400">pengirim</span>
                </div>
                <div class="text-[11px] text-gray-400 mt-1">Armada / delivery terdata</div>
            </div>
        </div>

        {{-- Mini Breakdown Bar per Shift --}}
        <div class="bg-white p-3.5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between gap-3 flex-wrap text-xs">
            <span class="font-bold text-gray-500 uppercase text-[11px]">Rincian Shift Produksi:</span>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 font-medium">
                    <span class="font-bold">Shift 1:</span>
                    <span class="font-mono font-bold">{{ number_format($recap['kpis']['shifts'][1]['qty']) }} pcs</span>
                    <span class="text-amber-600 font-normal">({{ $recap['kpis']['shifts'][1]['boxes'] }} box, {{ $recap['kpis']['shifts'][1]['pallets'] }} plt)</span>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 font-medium">
                    <span class="font-bold">Shift 2:</span>
                    <span class="font-mono font-bold">{{ number_format($recap['kpis']['shifts'][2]['qty']) }} pcs</span>
                    <span class="text-emerald-600 font-normal">({{ $recap['kpis']['shifts'][2]['boxes'] }} box, {{ $recap['kpis']['shifts'][2]['pallets'] }} plt)</span>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-900 font-medium">
                    <span class="font-bold">Shift 3:</span>
                    <span class="font-mono font-bold">{{ number_format($recap['kpis']['shifts'][3]['qty']) }} pcs</span>
                    <span class="text-indigo-600 font-normal">({{ $recap['kpis']['shifts'][3]['boxes'] }} box, {{ $recap['kpis']['shifts'][3]['pallets'] }} plt)</span>
                </div>
            </div>
        </div>

        {{-- 4. View Tabs --}}
        <div class="flex items-center gap-2 border-b border-gray-200">
            <button type="button" wire:click="$set('activeTab', 'items')" 
                    class="pb-2.5 px-4 font-bold text-sm transition relative {{ $activeTab === 'items' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                <span>📊 Rekap per Item Code ({{ count($recap['items']) }})</span>
            </button>
            <button type="button" wire:click="$set('activeTab', 'pallets')" 
                    class="pb-2.5 px-4 font-bold text-sm transition relative {{ $activeTab === 'pallets' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                <span>🏗️ Daftar Pallet ({{ count($recap['pallets_list']) }})</span>
            </button>
            <button type="button" wire:click="$set('activeTab', 'box_logs')" 
                    class="pb-2.5 px-4 font-bold text-sm transition relative {{ $activeTab === 'box_logs' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                <span>📦 Log Scan Box ({{ count($recap['box_logs']) }})</span>
            </button>
        </div>

        {{-- 5. Content Tabs --}}

        {{-- TAB 1: REKAP PER ITEM CODE (DEFAULT) --}}
        @if($activeTab === 'items')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <div class="text-xs text-gray-500 font-medium">
                        Menampilkan <span class="font-bold text-gray-800">{{ count($recap['items']) }}</span> item code. Klik tombol <span class="font-bold text-blue-600">▼ Lihat Detail</span> untuk melihat rincian pallet dan box.
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-700 uppercase font-bold text-[11px] border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">#</th>
                                <th class="px-4 py-3 min-w-[200px]">Item Code &amp; Nama Part</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3 text-right">Total Qty</th>
                                <th class="px-4 py-3 text-center">Total Box</th>
                                <th class="px-4 py-3 text-center">Jumlah Pallet</th>
                                <th class="px-4 py-3 min-w-[160px]">Rincian Shift</th>
                                <th class="px-4 py-3 min-w-[140px]">Daftar SPK</th>
                                <th class="px-4 py-3 min-w-[130px]">Pengirim</th>
                                <th class="px-4 py-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        @forelse($recap['items'] as $index => $item)
                            <tbody x-data="{ expanded: false }" class="divide-y divide-gray-100 border-b border-gray-100">
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3 text-center font-bold text-gray-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-black text-gray-900 text-xs">{{ $item['part_no'] }}</div>
                                        <div class="text-[11px] text-gray-600 font-medium line-clamp-1" title="{{ $item['item_name'] }}">
                                            {{ $item['item_name'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $item['customer_name'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <span class="font-mono font-black text-blue-700 text-sm">
                                            {{ number_format($item['total_qty']) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 block font-normal">pcs</span>
                                        @if(($item['qty_out'] ?? 0) > 0)
                                            @if(($item['qty_in_warehouse'] ?? 0) == 0)
                                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700">
                                                    Semua Keluar
                                                </span>
                                            @else
                                                <span class="text-[9px] text-red-600 font-semibold block mt-0.5" title="Sudah keluar via SO Scan">
                                                    Keluar: {{ number_format($item['qty_out']) }} pcs
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-mono font-bold text-gray-800">
                                        {{ number_format($item['total_boxes']) }} <span class="text-[10px] text-gray-400 font-normal">box</span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 font-mono">
                                            {{ $item['pallets_count'] }} Pallet
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="space-y-0.5 text-[10px]">
                                            @if($item['shift_breakdown'][1]['qty'] > 0)
                                                <div class="text-amber-800">
                                                    <span class="font-bold">S1:</span> {{ number_format($item['shift_breakdown'][1]['qty']) }} pcs ({{ $item['shift_breakdown'][1]['boxes'] }} box)
                                                </div>
                                            @endif
                                            @if($item['shift_breakdown'][2]['qty'] > 0)
                                                <div class="text-emerald-800">
                                                    <span class="font-bold">S2:</span> {{ number_format($item['shift_breakdown'][2]['qty']) }} pcs ({{ $item['shift_breakdown'][2]['boxes'] }} box)
                                                </div>
                                            @endif
                                            @if($item['shift_breakdown'][3]['qty'] > 0)
                                                <div class="text-indigo-800">
                                                    <span class="font-bold">S3:</span> {{ number_format($item['shift_breakdown'][3]['qty']) }} pcs ({{ $item['shift_breakdown'][3]['boxes'] }} box)
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 font-mono text-[10px]">
                                        <div class="max-w-xs truncate" title="{{ implode(', ', $item['spk_list']) }}">
                                            {{ !empty($item['spk_list']) ? implode(', ', $item['spk_list']) : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 text-[11px]">
                                        <div class="max-w-xs truncate" title="{{ implode(', ', $item['delivery_names']) }}">
                                            {{ !empty($item['delivery_names']) ? implode(', ', $item['delivery_names']) : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <button type="button" @click="expanded = !expanded" 
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 mx-auto cursor-pointer"
                                                :class="expanded ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                                            <span x-text="expanded ? 'Tutup ▲' : 'Rincian ▼'"></span>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Sub-table: Expandable Pallet & Box Details --}}
                                <tr x-show="expanded" x-cloak class="bg-blue-50/25">
                                    <td colspan="10" class="px-6 py-4">
                                        <div class="space-y-3">
                                            <div class="text-xs font-bold text-gray-700 flex items-center justify-between">
                                                <span>📦 Rincian Pallet yang Memuat [{{ $item['part_no'] }}]:</span>
                                                <span class="text-gray-400 font-normal">Total {{ count($item['pallets']) }} Pallet</span>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($item['pallets'] as $pItem)
                                                    <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs space-y-2">
                                                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                                            <div>
                                                                <span class="font-mono font-black text-xs text-blue-700">{{ $pItem['pallet_id'] }}</span>
                                                                <span class="inline-block ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold {{ $pItem['delivery_shift'] == 1 ? 'bg-amber-100 text-amber-800' : ($pItem['delivery_shift'] == 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800') }}">
                                                                    Shift {{ $pItem['delivery_shift'] }}
                                                                </span>
                                                            </div>
                                                            <div class="text-right">
                                                                @if(!empty($pItem['is_out']))
                                                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700" title="Waktu Keluar: {{ $pItem['out_time'] ?? '-' }}">
                                                                        🔴 Keluar {{ $pItem['out_time'] ? '(' . $pItem['out_time'] . ')' : '' }}
                                                                    </span>
                                                                @elseif(!empty($pItem['is_partial_out']))
                                                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                                        🟡 Sebagian Keluar
                                                                    </span>
                                                                @else
                                                                    <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                                        🟢 Di Gudang
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="text-xs space-y-1">
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Pengirim:</span>
                                                                <span class="font-bold text-gray-800">{{ $pItem['delivery_name'] }}</span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Lot / MO:</span>
                                                                <span class="font-mono text-gray-700">{{ $pItem['lot_no'] }}</span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-500">Slot Rak:</span>
                                                                <span class="font-mono font-bold {{ $pItem['slot'] === 'TEMPORARY' ? 'text-amber-600' : ($pItem['slot'] === 'KELUAR (SO)' ? 'text-red-600' : 'text-emerald-700') }}">
                                                                    {{ $pItem['slot'] }}
                                                                </span>
                                                            </div>
                                                            <div class="flex justify-between pt-1 border-t border-gray-50">
                                                                <span class="text-gray-700 font-bold">Qty Part Ini:</span>
                                                                <span class="font-mono font-black text-blue-700">
                                                                    {{ number_format($pItem['item_qty']) }} pcs ({{ $pItem['box_count'] }} box)
                                                                </span>
                                                            </div>
                                                        </div>

                                                        {{-- Box items breakdown inside this pallet --}}
                                                        <div class="pt-2 border-t border-gray-100">
                                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Daftar Box Ter-scan:</div>
                                                            <div class="space-y-1 max-h-32 overflow-y-auto pr-1">
                                                                @foreach($pItem['boxes'] as $bIdx => $bEntry)
                                                                    <div class="flex items-center justify-between text-[10px] px-2 py-0.5 rounded font-mono {{ !empty($bEntry['is_out']) ? 'bg-red-50 text-red-800 border border-red-100' : 'bg-gray-50 text-gray-800' }}">
                                                                        <span class="truncate max-w-[120px]" title="{{ $bEntry['label'] }}">
                                                                            {{ $bEntry['label'] }}
                                                                        </span>
                                                                        <div class="flex items-center gap-1.5 text-right whitespace-nowrap">
                                                                            <span class="font-bold">{{ number_format($bEntry['qty']) }} pcs</span>
                                                                            @if(!empty($bEntry['is_out']))
                                                                                <span class="text-[9px] px-1 py-0.2 rounded bg-red-200 text-red-800 font-sans font-bold" title="Waktu Keluar: {{ $bEntry['out_time'] ?? '-' }}">
                                                                                    Keluar {{ $bEntry['out_time'] ?? '' }}
                                                                                </span>
                                                                            @else
                                                                                <span class="text-[9px] px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 font-sans font-bold">
                                                                                    Gudang
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-400 italic">
                                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        Tidak ada data delivery yang ditemukan untuk periode produksi {{ $recap['time_window_label'] }}.
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: REKAP PER PALLET --}}
        @if($activeTab === 'pallets')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <div class="text-xs text-gray-500 font-medium">
                        Menampilkan <span class="font-bold text-gray-800">{{ count($recap['pallets_list']) }}</span> pallet form yang dibuat dalam hari produksi ini.
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-700 uppercase font-bold text-[11px] border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 w-12 text-center">#</th>
                                <th class="px-4 py-3 min-w-[130px]">Pallet ID</th>
                                <th class="px-4 py-3">Waktu Scan Masuk</th>
                                <th class="px-4 py-3">Shift</th>
                                <th class="px-4 py-3 min-w-[140px]">Pengirim / Delivery</th>
                                <th class="px-4 py-3">Lot No</th>
                                <th class="px-4 py-3">Slot Rak</th>
                                <th class="px-4 py-3">Status &amp; Tgl Keluar</th>
                                <th class="px-4 py-3 min-w-[200px]">Item yang Dimuat</th>
                                <th class="px-4 py-3 text-center">Total Box</th>
                                <th class="px-4 py-3 text-right">Total Qty</th>
                                <th class="px-4 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recap['pallets_list'] as $idx => $p)
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3 text-center font-bold text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-3 font-mono font-black text-blue-700">
                                        <a href="{{ route('wms.pallet-form.lookup', ['pallet_id' => $p['pallet_id']]) }}" class="hover:underline" target="_blank">
                                            {{ $p['pallet_id'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        <div>{{ $p['created_at_time'] }} WIB</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $p['delivery_shift'] == 1 ? 'bg-amber-100 text-amber-800' : ($p['delivery_shift'] == 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800') }}">
                                            Shift {{ $p['delivery_shift'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $p['delivery_name'] }}</td>
                                    <td class="px-4 py-3 font-mono text-gray-600">{{ $p['lot_no'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono font-bold text-xs {{ $p['slot'] === 'TEMPORARY' ? 'text-amber-600' : ($p['slot'] === 'KELUAR (SO)' ? 'text-red-600' : 'text-emerald-700') }}">
                                            {{ $p['slot'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if(!empty($p['is_out']))
                                            <div>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                                    🔴 Keluar
                                                </span>
                                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $p['out_time'] ?? '-' }}</div>
                                            </div>
                                        @elseif(!empty($p['is_partial_out']))
                                            <div>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    🟡 Sebagian Keluar
                                                </span>
                                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">Sisa {{ $p['boxes_in_warehouse'] }} box</div>
                                            </div>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                🟢 Di Gudang
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            @foreach($p['items'] as $pSub)
                                                <div class="text-[11px] flex items-center justify-between gap-2">
                                                    <span class="font-mono font-bold text-gray-800">{{ $pSub['part_no'] }}</span>
                                                    <span class="font-mono text-gray-600">{{ number_format($pSub['qty']) }} pcs ({{ $pSub['boxes'] }} box)</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-gray-800">{{ $p['total_box'] }} box</td>
                                    <td class="px-4 py-3 text-right font-mono font-black text-blue-700 text-sm">
                                        {{ number_format($p['total_qty']) }} <span class="text-[10px] text-gray-400 font-normal">pcs</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-[11px] max-w-xs truncate" title="{{ $p['remarks'] }}">
                                        {{ $p['remarks'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-6 py-12 text-center text-gray-400 italic">
                                        Tidak ada pallet form yang ditemukan untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 3: LOG SCAN BOX LENGKAP --}}
        @if($activeTab === 'box_logs')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <div class="text-xs text-gray-500 font-medium">
                        Menampilkan riwayat seluruh scan <span class="font-bold text-gray-800">{{ count($recap['box_logs']) }}</span> box kardus secara terperinci.
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-700 uppercase font-bold text-[11px] border-b border-gray-200 sticky top-0">
                            <tr>
                                <th class="px-3 py-2.5 w-10 text-center">#</th>
                                <th class="px-3 py-2.5">Waktu Scan Masuk</th>
                                <th class="px-3 py-2.5">Shift</th>
                                <th class="px-3 py-2.5">Pallet ID</th>
                                <th class="px-3 py-2.5">Status &amp; Tgl Keluar</th>
                                <th class="px-3 py-2.5">Pengirim</th>
                                <th class="px-3 py-2.5">Lot No</th>
                                <th class="px-3 py-2.5">Slot Rak</th>
                                <th class="px-3 py-2.5">Item Code (Part No)</th>
                                <th class="px-3 py-2.5 min-w-[160px]">Nama Part</th>
                                <th class="px-3 py-2.5">Customer</th>
                                <th class="px-3 py-2.5">SPK No</th>
                                <th class="px-3 py-2.5 font-mono">No. Label</th>
                                <th class="px-3 py-2.5 text-right font-mono">Qty (Pcs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($recap['box_logs'] as $bIdx => $bLog)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-3 py-2 text-center text-gray-400 font-bold">{{ $bIdx + 1 }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-600 font-mono text-[11px]">{{ $bLog['scan_time'] }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold {{ $bLog['shift'] == 1 ? 'bg-amber-100 text-amber-800' : ($bLog['shift'] == 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800') }}">
                                            S{{ $bLog['shift'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 font-mono font-bold text-blue-700 whitespace-nowrap">{{ $bLog['pallet_id'] }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if(!empty($bLog['is_out']))
                                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700" title="Waktu Keluar: {{ $bLog['out_time'] }}">
                                                🔴 Keluar ({{ $bLog['out_time'] }})
                                            </span>
                                        @else
                                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                🟢 Di Gudang
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-800">{{ $bLog['delivery_name'] }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-600">{{ $bLog['lot_no'] }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-700 font-bold">{{ $bLog['slot'] }}</td>
                                    <td class="px-3 py-2 font-mono font-bold text-gray-900">{{ $bLog['part_no'] }}</td>
                                    <td class="px-3 py-2 text-gray-600 truncate max-w-xs" title="{{ $bLog['item_name'] }}">{{ $bLog['item_name'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $bLog['customer_name'] }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-700">{{ $bLog['spk_no'] }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-600">{{ $bLog['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-black text-blue-700">{{ number_format($bLog['qty']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="px-6 py-12 text-center text-gray-400 italic">
                                        Tidak ada log scan box yang ditemukan untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</div>
