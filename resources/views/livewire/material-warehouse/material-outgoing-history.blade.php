<div class="p-6 bg-slate-50 min-h-screen space-y-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Banner Header -->
        <div class="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative overflow-hidden border border-slate-800">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex items-center space-x-4 relative z-10">
                <div class="w-12 h-12 bg-rose-500/20 rounded-2xl flex items-center justify-center border border-rose-400/30 text-rose-300 text-2xl shadow-inner shrink-0">
                    📤
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 bg-rose-500/20 text-rose-300 text-[10px] font-black rounded-md border border-rose-400/30 uppercase tracking-widest">
                            Material Outgoing Audit Trail
                        </span>
                        <span class="text-xs text-slate-400 font-medium">&bull; Live Database Log</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Riwayat Pengambilan Material (Outgoing History)</h1>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">
                        Log lengkap mutasi pengeluaran bahan baku / material dari gudang ke produksi & lini kerja
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 relative z-10">
                <a href="{{ route('mwh.outgoing.create') }}" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition-all shadow-lg flex items-center space-x-1.5 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Input Pengambilan Baru</span>
                </a>
            </div>
        </div>

        <!-- Branch / Warehouse Switcher Bar -->
        <div class="bg-white p-3.5 rounded-2xl shadow-xs border border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    PILIH BRANCH:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($warehouses as $wh)
                        <button 
                            wire:click="$set('whse_id', {{ $wh['id'] }})"
                            style="{{ $whse_id == $wh['id'] ? 'background-color: #e11d48 !important; color: #ffffff !important;' : 'background-color: #f1f5f9; color: #334155;' }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs {{ $whse_id == $wh['id'] ? 'bg-rose-600 text-white ring-2 ring-rose-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                        >
                            <span>🏭</span>
                            <span style="{{ $whse_id == $wh['id'] ? 'color: #ffffff !important;' : 'color: #1e293b;' }}">{{ $wh['whse_name'] }}</span>
                            <span 
                                style="{{ $whse_id == $wh['id'] ? 'background-color: #be123c !important; color: #ffe4e6 !important;' : 'background-color: #e2e8f0; color: #475569;' }}"
                                class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $whse_id == $wh['id'] ? 'bg-rose-700 text-rose-100' : 'bg-slate-200 text-slate-600' }}"
                            >
                                {{ $wh['whse_code'] }}
                            </span>
                        </button>
                    @endforeach
                    <button 
                        wire:click="$set('whse_id', 'ALL')"
                        style="{{ $whse_id === 'ALL' ? 'background-color: #1e293b !important; color: #ffffff !important;' : 'background-color: #f1f5f9; color: #475569;' }}"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $whse_id === 'ALL' ? 'bg-slate-800 text-white ring-2 ring-slate-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}"
                    >
                        🌐 Semua Branch
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl flex items-center justify-between shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs font-bold">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-xs font-bold">&times;</button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl flex items-center justify-between shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs font-bold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-xs font-bold">&times;</button>
            </div>
        @endif

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Qty Filtered -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">TOTAL KELUAR (FILTERED)</div>
                    <div class="text-2xl font-black text-rose-600 mt-1">
                        {{ number_format($stats['total_qty'], 2) }} <span class="text-xs text-slate-500 font-bold">KG</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Akumulasi Hasil Filter</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">TOTAL TRANSAKSI</div>
                    <div class="text-2xl font-black text-indigo-600 mt-1">
                        {{ number_format($stats['total_count']) }} <span class="text-xs text-slate-500 font-bold">Pengeluaran</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Jumlah Dokumen Outgoing</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>

            <!-- Today's Outgoing Qty -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">PENGAMBILAN HARI INI</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">
                        {{ number_format($stats['today_qty'], 2) }} <span class="text-xs text-slate-500 font-bold">KG</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Hari ini ({{ now()->format('d M Y') }})</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>

            <!-- Unique Materials -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">MATERIAL TERDISTRIBUSI</div>
                    <div class="text-2xl font-black text-amber-600 mt-1">
                        {{ number_format($stats['unique_materials']) }} <span class="text-xs text-slate-500 font-bold">Item</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Variasi Jenis Material</div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
        </div>

        <!-- Filter & Control Panel -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-4">
            
            <!-- Quick Preset Date Filter Buttons -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 text-xs">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">⚡ Preset Periode:</span>
                    <button wire:click="setFilterPreset('today')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $fromDate === now()->format('Y-m-d') && $toDate === now()->format('Y-m-d') ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                        Hari Ini
                    </button>
                    <button wire:click="setFilterPreset('this_week')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $fromDate === now()->startOfWeek()->format('Y-m-d') && $toDate === now()->format('Y-m-d') ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                        Minggu Ini
                    </button>
                    <button wire:click="setFilterPreset('this_month')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $fromDate === now()->startOfMonth()->format('Y-m-d') && $toDate === now()->format('Y-m-d') ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                        Bulan Ini
                    </button>
                    <button wire:click="setFilterPreset('all')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ empty($fromDate) && empty($toDate) ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                        Semua Periode
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <button wire:click="resetFilters" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition-all flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Filters Form Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                
                <!-- Search Input -->
                <div>
                    <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1.5">🔍 Cari Ref / Pallet / User:</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari Outgoing Code, Pallet, User..."
                               class="w-full py-2.5 pl-9 pr-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Material Selector -->
                <div>
                    <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1.5">📦 Jenis Material:</label>
                    <select wire:model.live="selectedItemCode" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none transition-all">
                        <option value="">-- Semua Material --</option>
                        @foreach ($availableMaterials as $mat)
                            <option value="{{ $mat->item_code }}">{{ $mat->item_code }} — {{ $mat->item_description }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Issued To Filter -->
                <div>
                    <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1.5">🏭 Tujuan Pengeluaran:</label>
                    <select wire:model.live="selectedIssuedTo" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none transition-all">
                        <option value="">-- Semua Tujuan --</option>
                        @foreach ($availableIssuedTo as $issued)
                            <option value="{{ $issued }}">{{ $issued }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Inputs -->
                <div>
                    <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1.5">📅 Rentang Tanggal:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" wire:model.live="fromDate" class="w-full py-2.5 px-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 outline-none focus:bg-white focus:ring-2 focus:ring-rose-500">
                        <input type="date" wire:model.live="toDate" class="w-full py-2.5 px-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 outline-none focus:bg-white focus:ring-2 focus:ring-rose-500">
                    </div>
                </div>

            </div>
        </div>

        <!-- Outgoing History Table Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            
            <!-- Table Action Header Bar -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-2">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">📋 Daftar Transaksi Material Outgoing</h3>
                    <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-black rounded-full">
                        {{ $outgoings->total() }} Transaksi Ditemukan
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Sort Direction Toggle -->
                    <button wire:click="toggleSortDirection" class="py-1.5 px-3 bg-white border border-slate-200 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-800 shadow-sm transition-all flex items-center space-x-1.5">
                        <span class="text-slate-400">🕒 Urutan:</span>
                        @if($sortDirection === 'DESC')
                            <span class="text-rose-700 font-extrabold flex items-center">
                                Terbaru &rarr; Terlama <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        @else
                            <span class="text-blue-700 font-extrabold flex items-center">
                                Terlama &rarr; Terbaru <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </span>
                        @endif
                    </button>

                    <!-- Per Page Selector -->
                    <div class="flex items-center space-x-1.5 text-xs">
                        <span class="text-slate-400 font-bold">Baris:</span>
                        <select wire:model.live="perPage" class="py-1.5 px-2.5 bg-white border border-slate-200 rounded-xl font-bold text-slate-800 shadow-sm outline-none">
                            <option value="15">15 / hal</option>
                            <option value="25">25 / hal</option>
                            <option value="50">50 / hal</option>
                            <option value="100">100 / hal</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3.5">No</th>
                            <th wire:click="toggleSortDirection" class="px-5 py-3.5 cursor-pointer hover:bg-slate-100 transition-colors select-none">
                                <div class="flex items-center space-x-1">
                                    <span>Tanggal & Jam Outgoing</span>
                                    @if($sortDirection === 'DESC')
                                        <span class="text-rose-600 font-black">⬇️</span>
                                    @else
                                        <span class="text-blue-600 font-black">⬆️</span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-5 py-3.5">Kode Outgoing Ref</th>
                            <th class="px-5 py-3.5">Material (Kode & Nama)</th>
                            <th class="px-5 py-3.5">Sumber Pallet & Slot</th>
                            <th class="px-5 py-3.5 text-right text-rose-700">Jumlah Keluar</th>
                            <th class="px-5 py-3.5">Tujuan (Issued To)</th>
                            <th class="px-5 py-3.5">Keterangan</th>
                            <th class="px-5 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse ($outgoings as $index => $row)
                            @php
                                $dt = $row->created_at ?: ($row->outgoing_date ? \Illuminate\Support\Carbon::parse($row->outgoing_date) : null);
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Row Number -->
                                <td class="px-5 py-3.5 font-bold text-slate-400">
                                    {{ $outgoings->firstItem() + $index }}
                                </td>

                                <!-- Date & Time -->
                                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $dt ? $dt->timezone('Asia/Jakarta')->format('d M Y H:i') : ($row->outgoing_date ? $row->outgoing_date->format('d M Y') : '-') }}
                                </td>

                                <!-- Outgoing Code -->
                                <td class="px-5 py-3.5">
                                    <span class="px-2.5 py-1 bg-slate-900 text-white font-mono font-bold text-[11px] rounded-lg shadow-xs inline-block">
                                        {{ $row->outgoing_code }}
                                    </span>
                                </td>

                                <!-- Material Code & Description -->
                                <td class="px-5 py-3.5">
                                    <div class="font-extrabold text-slate-900 font-mono text-[11px]">{{ $row->item_code }}</div>
                                    <div class="text-[10px] text-slate-500 font-semibold truncate max-w-[180px]" title="{{ $row->material?->item_description }}">
                                        {{ $row->material?->item_description ?: 'Material ' . $row->item_code }}
                                    </div>
                                </td>

                                <!-- Pallet ID & Slot Position -->
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-800 font-mono text-[11px]">{{ $row->pallet_id }}</div>
                                    <div class="mt-0.5">
                                        @if($row->position)
                                            <span class="px-2 py-0.5 bg-slate-800 text-white font-mono font-bold text-[10px] rounded uppercase">
                                                📍 {{ $row->position->position_code }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-mono text-[10px] italic">UNASSIGNED</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Qty Taken -->
                                <td class="px-5 py-3.5 text-right font-black text-rose-600 bg-rose-50/40 text-sm whitespace-nowrap">
                                    -{{ number_format($row->qty_taken, 2) }} <span class="text-[10px] text-rose-500 font-bold">{{ $row->uom ?: 'KG' }}</span>
                                </td>

                                <!-- Issued To -->
                                <td class="px-5 py-3.5">
                                    @if($row->issued_to)
                                        <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-[11px] rounded-lg inline-block">
                                            🏢 {{ $row->issued_to }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">Produksi</span>
                                    @endif
                                </td>

                                <!-- Remarks -->
                                <td class="px-5 py-3.5 text-slate-500 font-medium max-w-xs truncate" title="{{ $row->remarks }}">
                                    {{ $row->remarks ?: '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <!-- Retur Sisa Button -->
                                        <a href="{{ route('mwh.incoming.create', [
                                            'type'          => 'RETURN_PRODUCTION',
                                            'outgoing_code' => $row->outgoing_code,
                                            'returned_from' => $row->issued_to ?: 'Produksi',
                                            'item_code'     => $row->item_code
                                        ]) }}" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-extrabold text-[10px] rounded-lg transition-all flex items-center space-x-1 shadow-2xs shrink-0" title="Proses Retur Sisa Material ke Gudang">
                                            <span>🔄 Retur Sisa</span>
                                        </a>

                                        <!-- Detail Button -->
                                        <button wire:click="showDetail({{ $row->id }})" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-all" title="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Cancel/Delete Button -->
                                        <button wire:click="confirmDelete({{ $row->id }})" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition-all" title="Batalkan & Kembalikan Stok">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400 italic">
                                    Belum ada data riwayat pengambilan material untuk kriteria filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if($outgoings->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $outgoings->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Detail Outgoing Modal -->
    @if ($showDetailModal && $selectedOutgoing)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden border border-slate-100 animate-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 text-white p-6 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-rose-500/20 rounded-xl flex items-center justify-center text-rose-300 font-mono font-bold text-lg border border-rose-400/30">
                            📤
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase text-rose-300 tracking-widest block">Detail Outgoing Transaction</span>
                            <h3 class="text-lg font-black font-mono tracking-tight text-white">{{ $selectedOutgoing->outgoing_code }}</h3>
                        </div>
                    </div>
                    <button wire:click="closeDetailModal" class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-xs">
                    <!-- Qty & Date Summary Banner -->
                    <div class="bg-rose-50 border border-rose-200/80 rounded-2xl p-4 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">JUMLAH PENGAMBILAN</span>
                            <span class="text-2xl font-black text-rose-700">-{{ number_format($selectedOutgoing->qty_taken, 2) }} {{ $selectedOutgoing->uom ?: 'KG' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">TANGGAL OUTGOING</span>
                            <span class="text-sm font-black text-slate-800">
                                {{ $selectedOutgoing->created_at ? $selectedOutgoing->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : $selectedOutgoing->outgoing_date->format('d M Y') }}
                            </span>
                        </div>
                    </div>

                    <!-- Material Information Card -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-2">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider">📦 SPESIFIKASI MATERIAL</div>
                        <div class="flex justify-between items-center">
                            <span class="font-extrabold text-sm text-slate-900 font-mono">{{ $selectedOutgoing->item_code }}</span>
                            <a href="{{ route('mwh.stock-card.index', ['selectedItemCode' => $selectedOutgoing->item_code]) }}" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                                Lihat Kartu Stok &rarr;
                            </a>
                        </div>
                        <p class="text-slate-600 font-medium">{{ $selectedOutgoing->material?->item_description ?: '-' }}</p>
                    </div>

                    <!-- Pallet & Location Information Card -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">SUMBER PALLET ID</span>
                            <span class="font-mono font-bold text-slate-900 text-xs block">{{ $selectedOutgoing->pallet_id }}</span>
                            <span class="text-[10px] text-slate-500 block">Sisa Stok Pallet Sekarang: <strong>{{ number_format($selectedOutgoing->pallet?->current_qty ?? 0, 2) }} KG</strong></span>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">SLOT RAK GUDANG</span>
                            @if($selectedOutgoing->position)
                                <span class="px-2 py-0.5 bg-slate-800 text-white font-mono font-bold text-[10px] rounded uppercase inline-block">
                                    📍 {{ $selectedOutgoing->position->position_code }}
                                </span>
                            @else
                                <span class="text-slate-400 font-mono text-[10px] italic">UNASSIGNED</span>
                            @endif
                        </div>
                    </div>

                    <!-- Issued To & Remarks -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-semibold">Tujuan Pengeluaran:</span>
                            <span class="font-bold text-slate-900 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                                🏢 {{ $selectedOutgoing->issued_to ?: 'Produksi' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-slate-500 font-semibold shrink-0">Catatan / Remarks:</span>
                            <span class="font-medium text-slate-800 text-right max-w-xs">{{ $selectedOutgoing->remarks ?: '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="p-5 bg-slate-50 border-t border-slate-100 flex flex-wrap justify-between items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('mwh.incoming.create', [
                            'type'          => 'RETURN_PRODUCTION',
                            'outgoing_code' => $selectedOutgoing->outgoing_code,
                            'returned_from' => $selectedOutgoing->issued_to ?: 'Produksi',
                            'item_code'     => $selectedOutgoing->item_code
                        ]) }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs transition-all shadow-md flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>🔄 Proses Retur Sisa Material</span>
                        </a>

                        <button wire:click="confirmDelete({{ $selectedOutgoing->id }})" class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs transition-all flex items-center space-x-1.5" title="Batalkan & Hapusan Outgoing ini">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Batalkan</span>
                        </button>
                    </div>

                    <button wire:click="closeDetailModal" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete / Cancel Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-slate-100 animate-in zoom-in-95 duration-200 p-6 space-y-4">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto text-xl">
                    ⚠️
                </div>
                <div class="text-center space-y-1">
                    <h3 class="text-lg font-extrabold text-slate-900">Batalkan Pengambilan Material?</h3>
                    <p class="text-xs text-slate-500">
                        Tindakan ini akan menghapus transaksi pengeluaran dan <strong>mengembalikan kuantitas KG material yang diambil secara otomatis ke Pallet ID bersangkutan</strong>.
                    </p>
                </div>
                <div class="flex space-x-3 pt-2">
                    <button wire:click="closeDeleteModal" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                        Batal
                    </button>
                    <button wire:click="deleteOutgoing" class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all shadow-md">
                        Ya, Batalkan & Restok
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
