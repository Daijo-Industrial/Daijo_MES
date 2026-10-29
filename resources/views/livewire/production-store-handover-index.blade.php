<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-[1600px] w-full mx-auto space-y-6">

        {{-- 1. Top Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Kontrol Produksi & Rekonsiliasi Store</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Cutoff: 07:30 - 07:29 WIB
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                        Sinkronisasi Real-Time
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">
                    Monitoring data harian produksi (actual operator mesin & scan barcode box) dan pencatatan serah terima bertahap ke bagian Store.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" wire:click="refreshData" wire:loading.attr="disabled"
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-xl text-sm font-bold flex items-center transition-all shadow-sm">
                    <svg wire:loading.remove wire:target="refreshData" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg wire:loading wire:target="refreshData" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>REFRESH DATA</span>
                </button>
            </div>
        </div>

        {{-- 2. Filter Bar --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                {{-- Date Navigation --}}
                <div class="md:col-span-4 flex items-center gap-1.5">
                    <button type="button" wire:click="setYesterday" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition whitespace-nowrap" 
                            title="Hari Sebelumnya">
                        ◀ Kemarin
                    </button>
                    <div class="relative flex-1">
                        <input type="date" wire:model.live="selectedDate"
                               class="w-full px-3 py-2 border border-blue-300 rounded-lg text-sm font-bold text-gray-800 bg-blue-50/40 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="button" wire:click="setToday" 
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs font-bold text-gray-700 transition whitespace-nowrap"
                            title="Hari Produksi Ini">
                        Hari Ini
                    </button>
                    <button type="button" wire:click="setTomorrow" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition whitespace-nowrap"
                            title="Hari Berikutnya">
                        Besok ▶
                    </button>
                </div>

                {{-- Plant Filter (KBN vs Karawang) --}}
                <div class="md:col-span-2">
                    <select wire:model.live="plantFilter" 
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">🏭 Semua Plant</option>
                        <option value="KBN">🏢 Plant KBN</option>
                        <option value="KARAWANG">🏭 Plant Karawang (Mesin K)</option>
                    </select>
                </div>

                {{-- Shift Filter --}}
                <div class="md:col-span-2">
                    <select wire:model.live="shiftFilter" 
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">Semua Shift</option>
                        <option value="1">Shift 1 (Pagi)</option>
                        <option value="2">Shift 2 (Siang)</option>
                        <option value="3">Shift 3 (Malam)</option>
                    </select>
                </div>

                {{-- Search Box --}}
                <div class="md:col-span-4">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search" 
                               placeholder="Cari part number, mesin, customer..."
                               class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-xs text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. KPI Metrics Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
            {{-- Total Items --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Item Diproduksi</div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-gray-800">{{ number_format($stats['total_items']) }}</span>
                    <span class="text-xs font-semibold text-gray-500">Model / Part</span>
                </div>
                <div class="mt-1 text-[11px] text-gray-400">{{ $stats['completed_items_count'] }} item selesai serah terima</div>
            </div>

            {{-- Actual Operator --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-blue-100 bg-gradient-to-br from-white to-blue-50/30 flex flex-col justify-between">
                <div class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Actual Operator</span>
                    <span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono font-normal">Mesin</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-blue-900">{{ number_format($stats['total_actual_qty']) }}</span>
                    <span class="text-xs font-semibold text-blue-700">Pcs</span>
                </div>
                <div class="mt-1 text-[11px] text-blue-600/80">Input langsung operator mesin</div>
            </div>

            {{-- Scan Produksi --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/30 flex flex-col justify-between">
                <div class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Scan Produksi</span>
                    <span class="text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded font-mono font-normal">Barcode</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-indigo-900">{{ number_format($stats['total_scanned_boxes']) }}</span>
                    <span class="text-xs font-semibold text-indigo-700">Box</span>
                </div>
                <div class="mt-1 text-[11px] text-indigo-600/80">{{ number_format($stats['total_scanned_qty']) }} pcs ter-label</div>
            </div>

            {{-- Diterima Store --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100 bg-gradient-to-br from-white to-emerald-50/30 flex flex-col justify-between">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Diterima Store</span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-mono font-normal">Store</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-900">{{ number_format($stats['total_received_boxes']) }}</span>
                    <span class="text-xs font-semibold text-emerald-700">Box</span>
                </div>
                <div class="mt-1 text-[11px] text-emerald-600/80 flex items-center justify-between">
                    <span>{{ number_format($stats['total_received_qty']) }} pcs terverifikasi</span>
                    @if(isset($stats['total_pallets_count']) && $stats['total_pallets_count'] > 0)
                        <span class="font-bold text-emerald-800 bg-emerald-100 px-1.5 py-0.2 rounded font-mono text-[10px]" title="Pallet Form WMS Diterima">
                            {{ $stats['received_pallets_count'] }}/{{ $stats['total_pallets_count'] }} PLT
                        </span>
                    @endif
                </div>
            </div>

            {{-- In-Transit / Belum Diterima --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-amber-100 bg-gradient-to-br from-white to-amber-50/30 flex flex-col justify-between col-span-2 lg:col-span-1">
                <div class="text-xs font-bold text-amber-700 uppercase tracking-wider flex items-center justify-between">
                    <span>In-Transit / Sisa</span>
                    <span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-mono font-normal">Running</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-amber-900">{{ number_format($stats['total_in_transit_boxes']) }}</span>
                    <span class="text-xs font-semibold text-amber-700">Box</span>
                </div>
                <div class="mt-1 text-[11px] text-amber-600/80">{{ number_format($stats['total_in_transit_qty']) }} pcs belum sampai Store</div>
            </div>
        </div>

        {{-- 4. Operational Context Note (SNP Characteristic) --}}
        <div class="bg-blue-50/60 border border-blue-200/70 p-3.5 rounded-xl flex items-center gap-3">
            <div class="text-blue-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-xs text-blue-900 leading-relaxed">
                <span class="font-bold">Info Operasional:</span> Angka <span class="font-semibold text-blue-800">Actual Operator</span> (output mesin per jam) dan <span class="font-semibold text-indigo-800">Scan Produksi</span> (barcode box) wajar berbeda sewaktu-waktu tergantung sisa pieces yang belum mencapai 1 box utuh (SNP / Standard Packaging).
                @if($this->canManageStoreReceipt)
                    <span class="block mt-1 font-semibold text-emerald-800">Peran Anda (Store / Admin): Silakan verifikasi penerimaan barang fisik yang datang berdasarkan Pallet Form WMS menggunakan tombol <strong>Verifikasi Pallet</strong> di bawah (tanpa perlu scan barcode).</span>
                @else
                    <span class="block mt-1 font-semibold text-blue-800">Peran Anda (Produksi): Memonitor kesesuaian output actual mesin dan scan label box. Verifikasi penerimaan fisik barang ke Store dilakukan secara bertahap oleh staf Store.</span>
                @endif
            </div>
        </div>

        {{-- 5. Main Handover Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Daftar Item & Status Serah Terima</h2>
                    <p class="text-xs text-gray-500">Tanggal Produksi: <span class="font-semibold text-gray-700">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</span></p>
                </div>
                <div class="text-xs text-gray-500 font-semibold">
                    Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} item
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-600 uppercase font-black tracking-wider text-[11px]">
                            <th class="py-3 px-2 w-10 text-center whitespace-nowrap">No</th>
                            <th class="py-3 px-3 min-w-[170px]">Part Number & Model</th>
                            <th class="py-3 px-3 text-center min-w-[105px] whitespace-nowrap">
                                Mesin
                                <div class="text-[9px] font-normal text-gray-400 lowercase">(plant)</div>
                            </th>
                            <th class="py-3 px-3 min-w-[120px] max-w-[160px]">Customer</th>
                            <th class="py-3 px-2 text-center min-w-[85px] whitespace-nowrap">Shift</th>
                            <th class="py-3 px-3 text-right bg-blue-50/40 min-w-[100px] whitespace-nowrap">
                                Actual Mesin
                                <div class="text-[9px] font-normal text-blue-600 lowercase">(operator input)</div>
                            </th>
                            <th class="py-3 px-3 text-right bg-indigo-50/40 min-w-[110px] whitespace-nowrap">
                                Scan Produksi
                                <div class="text-[9px] font-normal text-indigo-600 lowercase">(box & pcs)</div>
                            </th>
                            <th class="py-3 px-3 text-right bg-emerald-50/40 min-w-[120px] whitespace-nowrap">
                                Diterima Store
                                <div class="text-[9px] font-normal text-emerald-600 lowercase">(terverifikasi)</div>
                            </th>
                            <th class="py-3 px-3 text-right bg-amber-50/40 min-w-[100px] whitespace-nowrap">
                                In-Transit
                                <div class="text-[9px] font-normal text-amber-600 lowercase">(belum diterima)</div>
                            </th>
                            <th class="py-3 px-3 text-center min-w-[110px] whitespace-nowrap">Status</th>
                            <th class="py-3 px-3 text-center min-w-[175px] whitespace-nowrap sticky right-0 z-20 bg-gray-50 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] border-l border-gray-200">
                                @if($this->canManageStoreReceipt)
                                    Aksi Store
                                @else
                                    Status Serah Terima
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                        @forelse($items as $index => $item)
                            <tr class="group hover:bg-gray-50/80 transition-colors">
                                <td class="py-3 px-2 text-center text-gray-400 font-bold whitespace-nowrap">
                                    {{ $items->firstItem() ? ($items->firstItem() + $index) : ($index + 1) }}
                                </td>
                                
                                {{-- Part No & Model --}}
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900 text-sm font-mono">{{ $item->item_code }}</div>
                                    <div class="text-gray-500 text-[11px] truncate max-w-xs" title="{{ $item->item_name }}">
                                        {{ $item->item_name ?: '-' }}
                                    </div>
                                </td>

                                {{-- Mesin & Plant --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="font-mono font-bold text-gray-900 text-xs">{{ $item->machine_name }}</div>
                                    @if($item->is_karawang)
                                        <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Karawang
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900 border border-blue-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            KBN
                                        </span>
                                    @endif
                                </td>

                                {{-- Customer --}}
                                <td class="py-3 px-3 min-w-[120px] max-w-[160px]">
                                    <div class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 text-gray-800 border border-gray-200 leading-snug">
                                        {{ $item->customer_name ?: 'N/A' }}
                                    </div>
                                </td>

                                {{-- Shift (Solid pill, never wrapped) --}}
                                <td class="py-3 px-2 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-black tracking-wide whitespace-nowrap shadow-xs
                                        {{ $item->shift == 1 ? 'bg-amber-100 text-amber-900 border border-amber-300' : ($item->shift == 2 ? 'bg-blue-100 text-blue-900 border border-blue-300' : 'bg-purple-100 text-purple-900 border border-purple-300') }}">
                                        Shift {{ $item->shift }}
                                    </span>
                                </td>

                                {{-- Actual Mesin (Operator) --}}
                                <td class="py-3 px-3 text-right bg-blue-50/20 font-mono font-bold text-blue-900 text-sm whitespace-nowrap">
                                    {{ number_format($item->operator_actual_qty) }} <span class="text-[10px] text-gray-400 font-normal">pcs</span>
                                </td>

                                {{-- Scan Produksi --}}
                                <td class="py-3 px-3 text-right bg-indigo-50/20 whitespace-nowrap">
                                    <div class="font-mono font-bold text-indigo-900 text-sm">
                                        {{ number_format($item->scanned_boxes) }} <span class="text-[10px] text-gray-400 font-normal">box</span>
                                    </div>
                                    <div class="text-[11px] text-indigo-700 font-mono">
                                        {{ number_format($item->scanned_qty) }} <span class="text-[9px] text-gray-400 font-normal">pcs</span>
                                    </div>
                                </td>

                                {{-- Diterima Store --}}
                                <td class="py-3 px-3 text-right bg-emerald-50/20 whitespace-nowrap">
                                    <div class="font-mono font-bold text-emerald-900 text-sm">
                                        {{ number_format($item->store_received_boxes) }} <span class="text-[10px] text-gray-400 font-normal">box</span>
                                    </div>
                                    <div class="text-[11px] text-emerald-700 font-mono">
                                        {{ number_format($item->store_received_qty) }} <span class="text-[9px] text-gray-400 font-normal">pcs</span>
                                    </div>
                                    @if($item->scanned_boxes > 0)
                                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-300" 
                                                 style="width: {{ $item->progress_percent }}%"></div>
                                        </div>
                                    @endif
                                </td>

                                {{-- In-Transit --}}
                                <td class="py-3 px-3 text-right bg-amber-50/20 whitespace-nowrap">
                                    @if($item->in_transit_boxes > 0)
                                        <div class="font-mono font-bold text-amber-900 text-sm">
                                            {{ number_format($item->in_transit_boxes) }} <span class="text-[10px] text-gray-400 font-normal">box</span>
                                        </div>
                                        <div class="text-[11px] text-amber-700 font-mono">
                                            {{ number_format($item->in_transit_qty) }} <span class="text-[9px] text-gray-400 font-normal">pcs</span>
                                        </div>
                                    @else
                                        <span class="text-emerald-600 font-bold text-xs flex items-center justify-end gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            Nihil
                                        </span>
                                    @endif
                                </td>

                                {{-- Status (Clean inline pill with SVG icon, no wrap) --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if($item->status === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 whitespace-nowrap shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Lengkap
                                        </span>
                                    @elseif($item->status === 'PARTIAL')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 whitespace-nowrap shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Bertahap
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-300 whitespace-nowrap shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Menunggu
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi Store / Status Serah Terima (Sticky Right Column: Never Cut Off!) --}}
                                <td class="py-2.5 px-3 text-center whitespace-nowrap sticky right-0 z-10 bg-white group-hover:bg-gray-50 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] border-l border-gray-200">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        @if($this->canManageStoreReceipt)
                                            {{-- STORE RECEIPT ACTIONS (SELALU BERBASIS VERIFIKASI PALLET WMS) --}}
                                            @if(isset($item->pallet_forms_count) && $item->pallet_forms_count > 0)
                                                @php
                                                    $unreceivedPallets = max(0, $item->pallet_forms_count - $item->received_pallet_forms_count);
                                                @endphp
                                                @if($unreceivedPallets > 0)
                                                    {{-- Tombol Utama: Verifikasi Pallet Datang --}}
                                                    <button type="button" 
                                                            wire:click="openPalletsModal({{ $item->id }})" 
                                                            class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5"
                                                            title="Buka untuk verifikasi fisik pallet yang baru datang">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                        <span>Verifikasi Pallet ({{ $unreceivedPallets }} Datang)</span>
                                                    </button>
                                                @else
                                                    {{-- Seluruh Pallet sudah diverifikasi --}}
                                                    <button type="button" 
                                                            wire:click="openPalletsModal({{ $item->id }})" 
                                                            class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200 rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1"
                                                            title="Seluruh pallet WMS sudah diverifikasi (klik untuk melihat rincian)">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        <span>✓ {{ $item->pallet_forms_count }} Pallet Terverifikasi</span>
                                                    </button>
                                                @endif
                                            @else
                                                {{-- Belum terdeteksi Pallet Form terbit untuk part ini --}}
                                                @if($item->in_transit_boxes > 0)
                                                    <button type="button" 
                                                            wire:click="openPalletsModal({{ $item->id }})" 
                                                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5"
                                                            title="Cek fisik pallet yang datang untuk item ini">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                        <span>Cek Pallet ({{ $item->in_transit_boxes }} Box Di Jalan)</span>
                                                    </button>
                                                @else
                                                    <span class="text-[11px] text-gray-400 italic">Menunggu Pallet</span>
                                                @endif
                                            @endif

                                            {{-- Tombol Kecil Opsional: Terima Manual Pecahan / Loss Pack (Hanya jika ada box in transit) --}}
                                            @if($item->in_transit_boxes > 0)
                                                <button type="button" 
                                                        wire:click="openReceiveModal({{ $item->id }})" 
                                                        class="px-2 py-1 bg-white hover:bg-gray-100 text-gray-600 border border-gray-300 rounded-lg text-[11px] font-medium transition shadow-xs"
                                                        title="Terima manual jika ada sisa box pecahan / loss pack tanpa Pallet Form">
                                                    <span>+ Pecahan</span>
                                                </button>
                                            @endif
                                        @else
                                            {{-- TAMPILAN VIEW-ONLY UNTUK TIM PRODUKSI --}}
                                            @if(isset($item->pallet_forms_count) && $item->pallet_forms_count > 0)
                                                @php
                                                    $unreceivedPallets = max(0, $item->pallet_forms_count - $item->received_pallet_forms_count);
                                                @endphp
                                                <button type="button" 
                                                        wire:click="openPalletsModal({{ $item->id }})" 
                                                        class="px-2 py-1 rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1 {{ $unreceivedPallets == 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}"
                                                        title="Lihat status pallet WMS">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <span>{{ $item->pallet_forms_count }} Pallet</span>
                                                    @if($unreceivedPallets > 0)
                                                        <span class="text-[10px] bg-amber-200/70 text-amber-900 px-1 py-0.2 rounded font-mono">{{ $unreceivedPallets }} Di Jalan</span>
                                                    @else
                                                        <span class="text-[10px] text-emerald-600 font-bold">✓ Lengkap</span>
                                                    @endif
                                                </button>
                                            @else
                                                @if($item->in_transit_boxes > 0)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        {{ $item->in_transit_boxes }} Box Di Jalan
                                                    </span>
                                                @elseif($item->scanned_boxes > 0 && $item->in_transit_boxes == 0)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        Diterima Penuh
                                                    </span>
                                                @else
                                                    <span class="text-[11px] text-gray-400 italic">Belum scan</span>
                                                @endif
                                            @endif
                                        @endif

                                        {{-- History Logs Modal (Dapat diakses oleh Store & Produksi untuk transparansi audit) --}}
                                        @if($item->logs && $item->logs->count() > 0)
                                            <button type="button" 
                                                    wire:click="openLogsModal({{ $item->id }})" 
                                                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1"
                                                    title="Lihat riwayat jam dan petugas Store yang memverifikasi">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span>{{ $item->logs->count() }} Trip</span>
                                            </button>
                                        @endif
                                        @if($item->scanned_boxes == 0 && $item->in_transit_boxes == 0)
                                            <span class="text-[11px] text-gray-400 italic">Belum scan</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="text-sm font-semibold text-gray-600">Tidak ada data jadwal produksi untuk filter ini.</div>
                                        <div class="text-xs text-gray-400">Coba ubah tanggal, filter shift, atau klik tombol <strong>Refresh Data</strong> di atas.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 6. Pagination & Per Page Selector --}}
            <div class="p-4 bg-white border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" 
                            class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>item per halaman</span>
                </div>

                <div>
                    {{ $items->links() }}
                </div>
            </div>
        </div>

    </div>

    {{-- ======================================================== --}}
    {{-- MODAL 1: CUSTOM BATCH RECEIVE STORE --}}
    {{-- ======================================================== --}}
    @if($showReceiveModal && $selectedHandover)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-gray-100 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="p-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black tracking-tight">Verifikasi Manual Box Pecahan (Tanpa Pallet Form)</h3>
                        <p class="text-xs text-blue-100 font-mono mt-0.5">
                            Penerimaan sisa box lepas &bull; {{ $selectedHandover->item_code }} &bull; Mesin {{ $selectedHandover->machine_name }} ({{ $selectedHandover->is_karawang ? 'Karawang' : 'KBN' }})
                        </p>
                    </div>
                    <button type="button" wire:click="closeReceiveModal" class="text-white/80 hover:text-white text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="submitCustomReceive">
                    <div class="p-5 space-y-4 text-xs">
                        <div class="bg-blue-50 p-3 rounded-xl border border-blue-100 flex items-center justify-between">
                            <div>
                                <div class="text-gray-500 text-[11px]">Sisa In-Transit Saat Ini:</div>
                                <div class="text-sm font-bold text-blue-900 font-mono">
                                    {{ $selectedHandover->in_transit_boxes }} Box ({{ number_format($selectedHandover->in_transit_qty) }} Pcs)
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-200/60 text-blue-800">
                                Shift {{ $selectedHandover->shift }}
                            </span>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-gray-700">Jumlah Box yang Diterima Trip Ini:</label>
                                @if($qtyPerBox > 0)
                                    <span class="text-[11px] font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        SNP: {{ $qtyPerBox }} pcs/box
                                    </span>
                                @endif
                            </div>
                            <input type="number" min="1" max="{{ max(1, $selectedHandover->in_transit_boxes) }}"
                                   wire:model.live.debounce.250ms="modalBoxes"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-gray-400">Masukkan jumlah box fisik yang baru saja diturunkan / diserahkan.</span>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-gray-700">Total Quantity (Pcs):</label>
                                <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                    Bisa diedit (Loss Pack)
                                </span>
                            </div>
                            <input type="number" min="1"
                                   wire:model="modalQty"
                                   class="w-full px-3 py-2 border border-blue-300 bg-blue-50/20 rounded-lg text-sm font-mono font-black text-blue-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[10px] text-gray-400">Dihitung otomatis (Box &times; SNP). Dapat diedit manual jika ada box pecahan / loss pack.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Catatan / Keterangan Trip (Opsional):</label>
                            <input type="text" wire:model="modalRemarks"
                                   placeholder="Contoh: Trip 1 - Forklift Budi / Trolley A"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeReceiveModal"
                                class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                            ✓ Konfirmasi Terima Box Pecahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- MODAL 2: AUDIT TRAIL / LOG RIWAYAT TRIP STORE --}}
    {{-- ======================================================== --}}
    @if($showLogsModal && $selectedHandover)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="p-5 bg-slate-800 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black tracking-tight">Riwayat Penerimaan Store</h3>
                        <p class="text-xs text-slate-300 font-mono mt-0.5">
                            {{ $selectedHandover->item_code }} &bull; Mesin {{ $selectedHandover->machine_name }} ({{ $selectedHandover->is_karawang ? 'Karawang' : 'KBN' }} &bull; Shift {{ $selectedHandover->shift }})
                        </p>
                    </div>
                    <button type="button" wire:click="closeLogsModal" class="text-white/80 hover:text-white text-xl font-bold">
                        &times;
                    </button>
                </div>

                <div class="p-5 max-h-[60vh] overflow-y-auto space-y-3">
                    @forelse($activeLogs as $log)
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-between gap-3 text-xs">
                            <div class="space-y-0.5">
                                <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                    <span class="text-emerald-700 font-mono text-sm">+{{ $log->received_boxes }} Box</span>
                                    <span class="text-gray-400">({{ number_format($log->received_qty) }} Pcs)</span>
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Oleh: <span class="font-semibold text-gray-700">{{ $log->verified_by_name ?: 'User Store' }}</span>
                                    &bull; 
                                    <span>{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                                </div>
                                @if($log->remarks)
                                    <div class="text-[11px] text-blue-700 font-medium mt-1 bg-blue-50 px-2 py-0.5 rounded inline-block">
                                        💬 {{ $log->remarks }}
                                    </div>
                                @endif
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold text-[10px] rounded-full whitespace-nowrap">
                                Sukses
                            </span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400 text-xs">
                            Belum ada riwayat penerimaan untuk item ini.
                        </div>
                    @endforelse
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs">
                    <div class="text-gray-500">
                        Total Diterima: <span class="font-bold text-emerald-800 font-mono">{{ $selectedHandover->store_received_boxes }} Box ({{ number_format($selectedHandover->store_received_qty) }} Pcs)</span>
                    </div>
                    <button type="button" wire:click="closeLogsModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl text-xs font-bold text-gray-800 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- MODAL 3: DAFTAR PALLET FORM WMS SERAH TERIMA --}}
    {{-- ======================================================== --}}
    @if($showPalletsModal && $palletsHandover)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                {{-- Header Modal --}}
                <div class="p-5 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 text-white flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-blue-500/20 text-blue-300 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </span>
                            <h3 class="text-base font-black tracking-tight">Verifikasi Pallet WMS Masuk Store</h3>
                        </div>
                        <p class="text-xs text-blue-200 font-mono mt-1">
                            Verifikasi fisik kedatangan pallet &bull; {{ $palletsHandover->item_code }} &bull; {{ $palletsHandover->item_name ?: 'No Description' }} &bull; Mesin {{ $palletsHandover->machine_name }} &bull; Shift {{ $palletsHandover->shift }}
                        </p>
                    </div>
                    <button type="button" wire:click="closePalletsModal" class="text-white/70 hover:text-white text-2xl font-bold leading-none p-1">
                        &times;
                    </button>
                </div>

                {{-- Action & Summary Bar --}}
                <div class="p-4 bg-slate-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500">Total Pallet:</span>
                            <span class="font-bold text-gray-900 font-mono text-sm">{{ count($palletFormsList) }}</span>
                        </div>
                        <span class="text-gray-300">|</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500">Terverifikasi:</span>
                            <span class="font-bold text-emerald-700 font-mono text-sm">
                                {{ count(array_filter($palletFormsList, fn($p) => $p['is_received'])) }}
                            </span>
                        </div>
                        <span class="text-gray-300">|</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500">Belum Verifikasi:</span>
                            <span class="font-bold text-amber-700 font-mono text-sm">
                                {{ count(array_filter($palletFormsList, fn($p) => !$p['is_received'])) }}
                            </span>
                        </div>
                    </div>

                    @if($this->canManageStoreReceipt)
                        @php
                            $unreceivedCount = count(array_filter($palletFormsList, fn($p) => !$p['is_received']));
                        @endphp
                        @if($unreceivedCount > 0)
                            <button type="button" 
                                    wire:click="receiveAllPalletsAction" 
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5 self-start sm:self-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>✓ Verifikasi Semua Pallet Datang ({{ $unreceivedCount }})</span>
                            </button>
                        @endif
                    @endif
                </div>

                {{-- List of Pallet Forms --}}
                <div class="p-5 max-h-[60vh] overflow-y-auto space-y-3.5">
                    @forelse($palletFormsList as $pallet)
                        <div class="p-4 rounded-xl border {{ $pallet['is_received'] ? 'bg-emerald-50/30 border-emerald-200' : 'bg-white border-gray-200 hover:border-indigo-300' }} shadow-xs transition space-y-2.5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-black text-sm text-gray-900 tracking-wide bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $pallet['pallet_id'] }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                        {{ $pallet['delivery_name'] }}
                                    </span>
                                    @if(!empty($pallet['delivery_shift']))
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-purple-100 text-purple-800">
                                            Shift {{ $pallet['delivery_shift'] }}
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-gray-400">
                                        Dibuat: {{ $pallet['created_time_formatted'] }} WIB
                                    </span>
                                </div>

                                {{-- Status Serah Terima / Action Button --}}
                                <div class="flex items-center gap-2">
                                    @if($pallet['is_received'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span>✓ Terverifikasi ({{ $pallet['received_by_name'] ?: 'Store' }} &bull; {{ $pallet['received_at_formatted'] }})</span>
                                        </span>
                                    @else
                                        @if($this->canManageStoreReceipt)
                                            <button type="button" 
                                                    wire:click="receivePalletAction('{{ $pallet['pallet_id'] }}')" 
                                                    wire:loading.attr="disabled"
                                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>✓ Verifikasi Pallet Ini</span>
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>In-Transit ke Store</span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-gray-100 text-xs">
                                <div>
                                    <span class="text-gray-400 text-[11px] block">Jumlah Fisik:</span>
                                    <span class="font-mono font-bold text-gray-900">
                                        {{ $pallet['box_count'] }} Box <span class="text-gray-400 font-normal">({{ number_format($pallet['qty_pcs']) }} Pcs)</span>
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-400 text-[11px] block">Lokasi Rak WMS:</span>
                                    @if($pallet['is_putaway'])
                                        <span class="font-bold text-emerald-700 font-mono flex items-center gap-1">
                                            <span>🟢 Rak {{ $pallet['rack_location'] }}</span>
                                        </span>
                                    @else
                                        <span class="text-amber-700 font-medium">
                                            🟡 Belum Putaway (In-Transit)
                                        </span>
                                    @endif
                                </div>

                                <div class="col-span-2 sm:col-span-1">
                                    <span class="text-gray-400 text-[11px] block">Model:</span>
                                    <span class="text-gray-700 font-medium truncate block">
                                        {{ $pallet['model_name'] ?: '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Collapsible Box details inside this pallet --}}
                            @if(!empty($pallet['details']))
                                <details class="pt-1.5 text-xs group">
                                    <summary class="cursor-pointer font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 select-none">
                                        <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span>Rincian {{ count($pallet['details']) }} Box di Pallet ini</span>
                                    </summary>
                                    <div class="mt-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200 overflow-x-auto">
                                        <table class="w-full text-[11px] font-mono">
                                            <thead>
                                                <tr class="text-left text-gray-400 border-b border-gray-200">
                                                    <th class="pb-1">No</th>
                                                    <th class="pb-1">Barcode Label</th>
                                                    <th class="pb-1">No SPK</th>
                                                    <th class="pb-1 text-right">Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($pallet['details'] as $idx => $detail)
                                                    <tr>
                                                        <td class="py-1 text-gray-400">{{ $idx + 1 }}</td>
                                                        <td class="py-1 font-bold text-gray-800">{{ $detail['label'] ?: '-' }}</td>
                                                        <td class="py-1 text-gray-600">{{ $detail['spk_no'] ?: '-' }}</td>
                                                        <td class="py-1 text-right font-bold text-indigo-700">{{ number_format($detail['qty']) }} pcs</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-400 space-y-3">
                            <div class="w-14 h-14 mx-auto bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center border border-amber-200">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="text-sm font-bold text-gray-800">Belum Ada Pallet Form WMS Diterbitkan</div>
                            <div class="text-xs text-gray-500 max-w-md mx-auto leading-relaxed">
                                Bagian produksi belum menerbitkan Pallet Form WMS di sistem untuk item <span class="font-bold text-gray-700 font-mono">{{ $palletsHandover->item_code }}</span>.
                                Pastikan tim produksi sudah selesai packing dan mencetak Pallet Form WMS di menu <em>WMS Pallet Form Creator</em>.
                            </div>
                            @if($this->canManageStoreReceipt && $palletsHandover->in_transit_boxes > 0)
                                <div class="pt-2">
                                    <button type="button" 
                                            wire:click="openReceiveModal({{ $palletsHandover->id }})" 
                                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        <span>Terima Manual Box Pecahan ({{ $palletsHandover->in_transit_boxes }} Box)</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- Footer Modal --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                    <button type="button" wire:click="closePalletsModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl text-xs font-bold text-gray-800 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
