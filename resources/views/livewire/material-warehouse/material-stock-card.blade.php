<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-black rounded-lg uppercase tracking-wider">
                        KARTU STOK MATERIAL
                    </span>
                    <span class="text-xs text-slate-400 font-medium">| Audit & History Audit Trail</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 mt-1">Material Stock Card</h1>
                <p class="text-xs text-slate-500 font-medium">Tracking pergerakan masuk (Incoming) & keluar (Outgoing) per jenis material secara terpusat.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                <a href="{{ route('mwh.fifo-dashboard.public') }}" target="_blank" class="no-print px-4 py-2.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:from-emerald-500 hover:to-cyan-600 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-2 group">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-200"></span>
                    </span>
                    <svg class="w-4 h-4 text-emerald-100 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>DIRECTOR FIFO DASHBOARD</span>
                    <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded font-mono font-bold uppercase tracking-wider">NO LOGIN</span>
                </a>
                <button onclick="window.print()" class="no-print px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    CETAK KARTU STOK
                </button>
            </div>
        </div>

        <!-- Branch / Warehouse Switcher Bar -->
        <div class="bg-white p-3.5 rounded-2xl shadow-xs border border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    PILIH BRANCH:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($warehouses as $wh)
                        <button 
                            wire:click="$set('whse_id', {{ $wh['id'] }})"
                            style="{{ $whse_id == $wh['id'] ? 'background-color: #059669 !important; color: #ffffff !important;' : 'background-color: #f1f5f9; color: #334155;' }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs {{ $whse_id == $wh['id'] ? 'bg-emerald-600 text-white ring-2 ring-emerald-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                        >
                            <span>🏭</span>
                            <span style="{{ $whse_id == $wh['id'] ? 'color: #ffffff !important;' : 'color: #1e293b;' }}">{{ $wh['whse_name'] }}</span>
                            <span 
                                style="{{ $whse_id == $wh['id'] ? 'background-color: #047857 !important; color: #d1fae5 !important;' : 'background-color: #e2e8f0; color: #475569;' }}"
                                class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $whse_id == $wh['id'] ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-200 text-slate-600' }}"
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

        <!-- Material Selection & Filters Bar -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <!-- Material Dropdown Selector (5 Cols) -->
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                        📦 Pilih Jenis Material:
                    </label>
                    <select wire:model.live="selectedItemCode" class="w-full py-2.5 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                        <option value="">-- Semua Material (All Transactions) --</option>
                        @foreach ($materials as $mat)
                            <option value="{{ $mat->item_code }}">
                                {{ $mat->item_code }} — {{ $mat->item_description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Transaction Type Filter (2 Cols) -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                        Jenis Transaksi:
                    </label>
                    <select wire:model.live="filterType" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="ALL">Semua (IN & OUT)</option>
                        <option value="INCOMING">📥 Masuk (Incoming)</option>
                        <option value="OUTGOING">📤 Keluar (Outgoing)</option>
                    </select>
                </div>

                <!-- Keyword Search (3 Cols) -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                        Cari Ref / Pallet / Supplier:
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari ID Pallet, Lot, PO, atau Tujuan..." 
                        class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>

                <!-- Reset Button (2 Cols) -->
                <div class="md:col-span-2">
                    <button wire:click="resetFilters" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Date Range Filter Row -->
            <div class="flex flex-wrap items-center gap-4 pt-2 border-t border-slate-100 text-xs">
                <span class="font-bold text-slate-500 uppercase tracking-wider">📅 Filter Tanggal:</span>
                <div class="flex items-center space-x-2">
                    <span class="text-slate-400 font-semibold">Dari:</span>
                    <input type="date" wire:model.live="fromDate" class="py-1.5 px-3 bg-slate-50 border border-slate-200 rounded-lg font-medium text-slate-800 outline-none">
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-slate-400 font-semibold">Sampai:</span>
                    <input type="date" wire:model.live="toDate" class="py-1.5 px-3 bg-slate-50 border border-slate-200 rounded-lg font-medium text-slate-800 outline-none">
                </div>
                @if($fromDate || $toDate || $search || $filterType !== 'ALL')
                    <span class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold">
                        Filter Aktif
                    </span>
                @endif
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Current Stock Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">SISA STOK AKHIR (LIVE)</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">
                        {{ number_format($summary['current_stock'], 2) }} <span class="text-xs text-slate-500 font-bold">{{ $summary['uom'] }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Tersedia di Rak Gudang</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>

            <!-- Active Pallets Count Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">PALLET AKTIF</div>
                    <div class="text-2xl font-black text-blue-600 mt-1">
                        {{ number_format($summary['active_pallets']) }} <span class="text-xs text-slate-500 font-bold">Pallet</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Pallet dengan Stok > 0</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>

            <!-- Total Incoming Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">TOTAL MASUK (INCOMING)</div>
                    <div class="text-2xl font-black text-indigo-600 mt-1">
                        {{ number_format($summary['total_incoming'], 2) }} <span class="text-xs text-slate-500 font-bold">{{ $summary['uom'] }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Akumulasi Kedatangan</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </div>
            </div>

            <!-- Total Outgoing Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">TOTAL KELUAR (OUTGOING)</div>
                    <div class="text-2xl font-black text-rose-600 mt-1">
                        {{ number_format($summary['total_outgoing'], 2) }} <span class="text-xs text-slate-500 font-bold">{{ $summary['uom'] }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">Akumulasi Pengambilan</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </div>
            </div>
        </div>

        <!-- Selected Material Header Info Box -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-6 rounded-2xl shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="text-[10px] font-black uppercase tracking-widest text-emerald-400">DETAIL MATERIAL DIHIMPUN</div>
                <h2 class="text-xl font-black tracking-tight mt-0.5">
                    {{ $selectedMaterial ? $selectedMaterial->item_code : 'SEMUA MATERIAL (ALL MATERIALS)' }}
                </h2>
                <p class="text-sm text-slate-300 font-medium mt-0.5">
                    {{ $selectedMaterial ? $selectedMaterial->item_description : 'Riwayat Mutasi & Pergerakan Seluruh Jenis Material' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-300">
                <div class="bg-slate-800/80 px-3.5 py-2 rounded-xl border border-slate-700">
                    <span class="text-slate-400 font-normal">Satuan (UOM):</span>
                    <strong class="text-white ml-1 font-bold">{{ $summary['uom'] }}</strong>
                </div>
                @if($selectedMaterial)
                    <div class="bg-slate-800/80 px-3.5 py-2 rounded-xl border border-slate-700">
                        <span class="text-slate-400 font-normal">Supplier Utama:</span>
                        <strong class="text-white ml-1 font-bold">{{ $selectedMaterial->preferred_supplier ?: 'N/A' }}</strong>
                    </div>
                @endif
            </div>
        </div>

            <!-- Movement History Table (Kartu Stok) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3 bg-slate-50/50">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">📋 Mutasi Stok Material (Stock Card Table)</h3>
                        <span class="px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] font-bold rounded-full">
                            {{ $totalMovementsCount }} Total Transaksi
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Sort Direction Toggle -->
                        <button wire:click="toggleSortDirection" class="py-1.5 px-3 bg-white border border-slate-200 hover:bg-slate-100 rounded-xl text-xs font-bold text-slate-800 shadow-sm transition-all flex items-center space-x-1.5">
                            <span class="text-slate-400">🕒 Urutan:</span>
                            @if($sortDirection === 'DESC')
                                <span class="text-emerald-700 font-extrabold flex items-center">
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

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th wire:click="toggleSortDirection" class="px-4 py-3.5 cursor-pointer hover:bg-slate-100/80 transition-colors select-none">
                                    <div class="flex items-center space-x-1">
                                        <span>Tanggal & Jam</span>
                                        @if($sortDirection === 'DESC')
                                            <span class="text-emerald-600 font-black">⬇️</span>
                                        @else
                                            <span class="text-blue-600 font-black">⬆️</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-4 py-3.5">Jenis</th>
                                <th class="px-4 py-3.5">Material (Kode & Nama)</th>
                                <th class="px-4 py-3.5">Kode Ref / Pallet</th>
                                <th class="px-4 py-3.5">Supplier / Tujuan</th>
                                <th class="px-4 py-3.5">Slot Rak</th>
                                <th class="px-4 py-3.5 text-right text-emerald-700">Masuk (+)</th>
                                <th class="px-4 py-3.5 text-right text-rose-700">Keluar (-)</th>
                                <th class="px-4 py-3.5 text-right text-amber-700" title="Sisa stok fisik aktual di Pallet / Slot Rak ini">Sisa Stok Pallet</th>
                                <th class="px-4 py-3.5 text-right text-slate-900" title="Total stok material ini di seluruh gudang">Total Stok Material</th>
                                <th class="px-4 py-3.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse ($movements as $m)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    
                                    <!-- Tanggal & Jam -->
                                    <td class="px-4 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                        {{ $m['date_formatted'] }}
                                    </td>

                                    <!-- Jenis Transaksi -->
                                    <td class="px-4 py-3.5">
                                        @if($m['type'] === 'OPENING_BALANCE')
                                            <span class="px-2 py-1 bg-amber-100 text-amber-800 font-black text-[10px] rounded-lg border border-amber-200 inline-flex items-center">
                                                📦 SALDO AWAL
                                            </span>
                                        @elseif($m['type'] === 'RETURN_PRODUCTION')
                                            <span class="px-2 py-1 bg-amber-100 text-amber-900 font-black text-[10px] rounded-lg border border-amber-300 inline-flex items-center shadow-xs">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                RETUR PRODUKSI
                                            </span>
                                        @elseif($m['type'] === 'INCOMING')
                                            <span class="px-2 py-1 bg-emerald-100 text-emerald-800 font-black text-[10px] rounded-lg border border-emerald-200 inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                                INCOMING (SUPPLIER)
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-rose-100 text-rose-800 font-black text-[10px] rounded-lg border border-rose-200 inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                                OUTGOING
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Material (Kode & Nama) -->
                                    <td class="px-4 py-3.5">
                                        <div class="font-extrabold text-slate-900 font-mono text-[11px]">{{ $m['item_code'] }}</div>
                                        <div class="text-[10px] text-slate-500 font-semibold truncate max-w-[160px]" title="{{ $m['item_description'] }}">
                                            {{ $m['item_description'] }}
                                        </div>
                                    </td>

                                    <!-- Kode Ref / Pallet -->
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-slate-900 font-mono text-[11px]">{{ $m['ref_code'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">{{ $m['sub_ref'] }}</div>
                                    </td>

                                    <!-- Supplier / Tujuan -->
                                    <td class="px-4 py-3.5 font-medium text-slate-700">
                                        {{ $m['source_destination'] }}
                                    </td>

                                    <!-- Slot Rak -->
                                    <td class="px-4 py-3.5">
                                        @if($m['slot_code'] !== 'UNASSIGNED' && $m['slot_code'] !== '-')
                                            <span class="px-2 py-0.5 bg-slate-800 text-white font-mono font-bold text-[10px] rounded uppercase">
                                                {{ $m['slot_code'] }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-mono text-[10px] italic">UNASSIGNED</span>
                                        @endif
                                    </td>

                                    <!-- Masuk (+) -->
                                    <td class="px-4 py-3.5 text-right font-black text-emerald-600">
                                        @if($m['qty_in'] > 0)
                                            +{{ number_format($m['qty_in'], 2) }}
                                        @else
                                            <span class="text-slate-300 font-normal">-</span>
                                        @endif
                                    </td>

                                    <!-- Keluar (-) -->
                                    <td class="px-4 py-3.5 text-right font-black text-rose-600">
                                        @if($m['qty_out'] > 0)
                                            -{{ number_format($m['qty_out'], 2) }}
                                        @else
                                            <span class="text-slate-300 font-normal">-</span>
                                        @endif
                                    </td>

                                    <!-- Sisa Stok Pallet (Aktual di Pallet ini) -->
                                    <td class="px-4 py-3.5 text-right font-black bg-amber-50/40">
                                        @if($m['type'] === 'OPENING_BALANCE')
                                            <span class="text-slate-300 font-normal">-</span>
                                        @elseif($m['pallet_qty'] <= 0)
                                            <span class="text-slate-400 font-semibold">0.00</span> <span class="text-[10px] text-slate-400 font-normal">{{ $m['uom'] }}</span>
                                        @else
                                            <span class="text-amber-700 font-black">{{ number_format($m['pallet_qty'], 2) }}</span> <span class="text-[10px] text-amber-600 font-semibold">{{ $m['uom'] }}</span>
                                        @endif
                                    </td>

                                    <!-- Total Stok Material (Seluruh Gudang) -->
                                    <td class="px-4 py-3.5 text-right font-black text-slate-900 bg-slate-50/50">
                                        {{ number_format($m['balance'], 2) }} <span class="text-[10px] text-slate-400 font-semibold">{{ $m['uom'] }}</span>
                                    </td>

                                    <!-- Keterangan -->
                                    <td class="px-5 py-3.5 text-slate-500 font-medium max-w-xs truncate" title="{{ $m['remarks'] }}">
                                        {{ $m['remarks'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center text-slate-400 italic">
                                        Belum ada riwayat pergerakan (mutasi stok) untuk kriteria filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>

    </div>
</div>
