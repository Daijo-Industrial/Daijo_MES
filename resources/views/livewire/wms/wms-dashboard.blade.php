<div class="p-6 bg-slate-50 min-h-screen space-y-6" wire:poll.15s>
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Top Banner Header -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative overflow-hidden border border-slate-800">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex items-center space-x-4 relative z-10">
                <div class="w-12 h-12 bg-indigo-500/20 rounded-2xl flex items-center justify-center border border-indigo-400/30 text-indigo-300 text-2xl shadow-inner shrink-0">
                    🏢
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 bg-indigo-500/20 text-indigo-300 text-[10px] font-black rounded-md border border-indigo-400/30 uppercase tracking-widest">
                            WMS Real-Time Availability
                        </span>
                        <span class="text-xs text-slate-400 font-medium">&bull; Main FG Warehouse</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Dashboard Ketersediaan Rak (WMS)</h1>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">
                        Monitoring kapasitas rak, status ketersediaan slot (Full, Partial, Kosong), serta rotasi FIFO pallet
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 relative z-10">
                <a href="{{ route('wms.mapping') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl transition-all shadow-md flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>Warehouse Mapping Map</span>
                </a>

                <a href="{{ route('wms.pallet-form.create-delivery') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition-all shadow-lg flex items-center space-x-1.5 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Scan Delivery FG</span>
                </a>
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

        <!-- Primary KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">

            <!-- 1. Occupancy Rate Gauge -->
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-3 relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">OKUPANSI RAK</div>
                        <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $occupancyRate }}%</div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 text-base">
                        📊
                    </div>
                </div>
                <!-- Progress Bar -->
                <div class="space-y-1">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex">
                        <div class="bg-emerald-500 h-2" style="width: {{ $emptyRate }}%" title="Empty {{ $emptyRate }}%"></div>
                        <div class="bg-amber-400 h-2" style="width: {{ $partialRate }}%" title="Partial {{ $partialRate }}%"></div>
                        <div class="bg-rose-500 h-2" style="width: {{ $fullRate }}%" title="Full {{ $fullRate }}%"></div>
                    </div>
                    <div class="flex justify-between text-[9px] text-slate-400 font-bold">
                        <span>Tot: {{ $totalPositions }}</span>
                        <span>Isi: {{ $totalPositions - $emptySlots }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Slot Kosong (EMPTY) -->
            <div wire:click="setStatusFilter('EMPTY')" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2 cursor-pointer hover:border-emerald-300 transition-all group">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-emerald-700 tracking-wider flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                            <span>KOSONG</span>
                        </div>
                        <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($emptySlots) }}</div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 font-black text-xs group-hover:scale-110 transition-transform">
                        {{ $emptyRate }}%
                    </div>
                </div>
                <div class="text-[9px] text-slate-400 font-medium">Slot Siap Diisi Pallet</div>
            </div>

            <!-- 3. Slot Terisi Sebagian (PARTIAL) -->
            <div wire:click="setStatusFilter('PARTIAL')" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2 cursor-pointer hover:border-amber-300 transition-all group">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-amber-700 tracking-wider flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                            <span>SEBAGIAN</span>
                        </div>
                        <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($partialSlots) }}</div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 font-black text-xs group-hover:scale-110 transition-transform">
                        {{ $partialRate }}%
                    </div>
                </div>
                <div class="text-[9px] text-slate-400 font-medium">Masih Punya Kapasitas</div>
            </div>

            <!-- 4. Slot Penuh (FULL) -->
            <div wire:click="setStatusFilter('FULL')" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2 cursor-pointer hover:border-rose-300 transition-all group">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-rose-700 tracking-wider flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                            <span>PENUH</span>
                        </div>
                        <div class="text-2xl font-black text-rose-600 mt-1">{{ number_format($fullSlots) }}</div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 font-black text-xs group-hover:scale-110 transition-transform">
                        {{ $fullRate }}%
                    </div>
                </div>
                <div class="text-[9px] text-slate-400 font-medium">Kapasitas Maksimal</div>
            </div>

            <!-- 5. Total Stored Pallets & Qty -->
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 space-y-2">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">PALLET RAK</div>
                        <div class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($totalStoredPallets) }} <span class="text-xs text-slate-400 font-bold">Plt</span></div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 text-base">
                        📦
                    </div>
                </div>
                <div class="text-[9px] text-slate-500 font-bold truncate">
                    {{ number_format($totalStoredPcs) }} Pcs / {{ number_format($totalStoredBoxes) }} Box
                </div>
            </div>

            <!-- 6. Warning Stok > 30 Hari & Avg Lead Time -->
            <div wire:click="setStatusFilter('OVERAGED')" class="bg-gradient-to-br from-rose-50 to-orange-50 p-4 rounded-3xl shadow-sm border border-rose-200 space-y-2 cursor-pointer hover:border-rose-400 transition-all group">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-black uppercase text-rose-800 tracking-wider flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-rose-600 inline-block animate-ping"></span>
                            <span>STOK > 30 HARI</span>
                        </div>
                        <div class="text-2xl font-black text-rose-700 mt-1">{{ number_format($overagedCount) }} <span class="text-xs text-rose-500 font-bold">Pallet</span></div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-rose-100 border border-rose-300 flex items-center justify-center text-rose-700 font-black text-xs group-hover:scale-110 transition-transform">
                        ⚠️
                    </div>
                </div>
                <div class="text-[9px] text-rose-700 font-bold truncate">
                    ⏱️ Avg Putaway: {{ $avgLeadTimeText }}
                </div>
            </div>

        </div>

        <!-- Filter & Control Panel -->
        <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            
            <!-- Quick Preset Filter Buttons -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Filter Availability:</span>
                
                <button wire:click="setStatusFilter('ALL')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $statusFilter === 'ALL' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                    Semua Slot ({{ $totalPositions }})
                </button>

                <button wire:click="setStatusFilter('EMPTY')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $statusFilter === 'EMPTY' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                    🟢 Kosong ({{ $emptySlots }})
                </button>

                <button wire:click="setStatusFilter('PARTIAL')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $statusFilter === 'PARTIAL' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                    🟡 Sebagian ({{ $partialSlots }})
                </button>

                <button wire:click="setStatusFilter('FULL')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $statusFilter === 'FULL' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' }}">
                    🔴 Penuh ({{ $fullSlots }})
                </button>

                <button wire:click="setStatusFilter('OVERAGED')" class="px-3 py-1.5 rounded-xl font-bold transition-all border {{ $statusFilter === 'OVERAGED' ? 'bg-rose-800 text-white border-rose-800 shadow-xs' : 'bg-rose-100 text-rose-900 border-rose-300 hover:bg-rose-200' }}">
                    ⚠️ Stok > 30 Hari ({{ $overagedCount }})
                </button>
            </div>

            <!-- Search Bar & Reset -->
            <div class="flex items-center space-x-2 w-full md:w-auto">
                <div class="relative w-full md:w-72">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Slot / Part Code / Pallet..."
                           class="w-full py-2 pl-9 pr-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-xs text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                @if($statusFilter !== 'ALL' || !empty($search))
                    <button wire:click="resetFilters" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-xs transition-all shrink-0">
                        Reset
                    </button>
                @endif
            </div>
        </div>

        <!-- Racks Grid Display Section -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide flex items-center space-x-2">
                    <span>🗺️ Matriks Rak Ketersediaan Slot</span>
                    <span class="px-2.5 py-0.5 bg-slate-200 text-slate-700 text-[10px] font-black rounded-full">{{ $racks->count() }} Unit Rak</span>
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($racks as $rack)
                    @php
                        $positions = $rack->positions;
                        $rackTotal = $positions->count();
                        $rackEmpty = $positions->where('status', 'EMPTY')->count();
                        $rackPartial = $positions->where('status', 'PARTIAL')->count();
                        $rackFull = $positions->where('status', 'FULL')->count();
                    @endphp
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col justify-between">
                        
                        <!-- Rack Header -->
                        <div class="px-5 py-3.5 bg-slate-900 text-white flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 bg-indigo-400 rounded-full inline-block"></span>
                                <h4 class="font-black text-sm font-mono tracking-tight text-white">RAK {{ $rack->rack_code }}</h4>
                            </div>
                            <div class="flex items-center space-x-1.5 text-[10px] font-bold">
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 rounded-md border border-emerald-400/30">🟢 {{ $rackEmpty }}</span>
                                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 rounded-md border border-amber-400/30">🟡 {{ $rackPartial }}</span>
                                <span class="px-2 py-0.5 bg-rose-500/20 text-rose-300 rounded-md border border-rose-400/30">🔴 {{ $rackFull }}</span>
                            </div>
                        </div>

                        <!-- Slots Grid for this Rack -->
                        <div class="p-4 space-y-3 flex-1">
                            @if($positions->isEmpty())
                                <div class="text-center py-6 text-slate-400 text-xs italic">
                                    Tidak ada slot rak yang sesuai dengan kriteria filter.
                                </div>
                            @else
                                <!-- Group positions by level_no -->
                                @php
                                    $levelsGroup = $positions->groupBy('level_no');
                                @endphp

                                @foreach ($levelsGroup as $levelNo => $levelPositions)
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">LEVEL {{ str_pad($levelNo, 2, '0', STR_PAD_LEFT) }}</div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            @foreach ($levelPositions as $pos)
                                                @php
                                                    $isFull = $pos->status === 'FULL';
                                                    $isPartial = $pos->status === 'PARTIAL';
                                                    $isEmpty = $pos->status === 'EMPTY';
                                                    
                                                    $bgClass = $isEmpty 
                                                        ? 'bg-emerald-50/90 border-emerald-200 text-emerald-900 hover:bg-emerald-100' 
                                                        : ($isPartial 
                                                            ? 'bg-amber-50/90 border-amber-300 text-amber-900 hover:bg-amber-100' 
                                                            : 'bg-rose-50/90 border-rose-300 text-rose-900 hover:bg-rose-100');
                                                @endphp
                                                <button wire:click="selectPosition({{ $pos->id }})" class="p-2.5 rounded-2xl border transition-all text-left space-y-1 shadow-2xs group relative {{ $bgClass }}">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-mono font-black text-[11px] block truncate" title="{{ $pos->position_code }}">
                                                            S{{ str_pad($pos->slot_no, 2, '0', STR_PAD_LEFT) }}
                                                        </span>
                                                        <span class="w-2 h-2 rounded-full {{ $isEmpty ? 'bg-emerald-500' : ($isPartial ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                                    </div>

                                                    <div class="text-[9px] font-bold truncate">
                                                        @if($isEmpty)
                                                            <span class="text-emerald-700 font-extrabold uppercase">EMPTY</span>
                                                        @else
                                                            <span class="text-slate-800 font-mono block truncate" title="{{ $pos->last_item_code }}">
                                                                {{ $pos->last_item_code ?: 'Pallet Stored' }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="text-[9px] text-slate-500 flex justify-between font-semibold">
                                                        <span>Pallet: {{ $pos->pallet_forms_count }}</span>
                                                        <span>Cap: {{ $pos->max_capacity }}</span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-white p-12 rounded-3xl text-center text-slate-400 italic">
                        Tidak ada rak gudang yang ditemukan untuk kriteria filter ini.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Extra Widgets & Analytics Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Widget 1: FIFO Aging Alert (Pallet Terlama) -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center space-x-1.5">
                        <span>⏳ FIFO Alert: Pallet Terlama</span>
                    </h3>
                    <span class="text-[10px] text-slate-400 font-bold">Berdasarkan Tgl Scan</span>
                </div>

                <div class="space-y-3">
                    @forelse ($oldestPallets as $op)
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between text-xs hover:bg-slate-100/80 transition-colors">
                            <div class="space-y-0.5">
                                <div class="font-extrabold font-mono text-slate-900 text-[11px]">{{ $op->pallet_id }}</div>
                                <div class="text-[10px] font-bold text-slate-600 truncate max-w-[170px]">{{ $op->part_no }} — {{ $op->model_name }}</div>
                                <div class="text-[10px] text-slate-400">
                                    📍 {{ $op->position ? $op->position->position_code : 'Unassigned' }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="px-2 py-0.5 bg-rose-100 text-rose-800 font-bold text-[10px] rounded-md block">
                                    {{ $op->created_at ? $op->created_at->diffForHumans() : '-' }}
                                </span>
                                <span class="text-[10px] font-black text-slate-700 block mt-1">
                                    {{ number_format($op->total_pallet_qty) }} Pcs
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs italic">Belum ada data pallet tersimpan.</div>
                    @endforelse
                </div>
            </div>

            <!-- Widget 2: Distribusi Part Code Terbanyak -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center space-x-1.5">
                        <span>📦 Item Paling Banyak di Rak</span>
                    </h3>
                    <span class="text-[10px] text-slate-400 font-bold">Top 5 Item</span>
                </div>

                <div class="space-y-3">
                    @forelse ($topStoredItems as $item)
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-extrabold font-mono text-slate-900 text-[11px]">{{ $item->part_no }}</span>
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 font-black text-[10px] rounded-md">
                                    {{ number_format($item->total_qty) }} Pcs
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] text-slate-500 font-medium">
                                <span class="truncate max-w-[180px]">{{ $item->model_name ?: 'Model -' }}</span>
                                <span>{{ $item->pallet_count }} Pallet</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs italic">Belum ada data stok tersimpan.</div>
                    @endforelse
                </div>
            </div>

            <!-- Widget 3: Live Movement Audit Log Stream -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center space-x-1.5">
                        <span>📜 Audit Log Aktivitas Rak</span>
                    </h3>
                    <a href="{{ route('wms.logs') }}" class="text-[10px] font-bold text-indigo-600 hover:underline">Semua Log &rarr;</a>
                </div>

                <div class="space-y-3">
                    @forelse ($recentLogs as $log)
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-bold font-mono text-slate-800 text-[11px]">{{ $log->pallet_id }}</span>
                                <span class="px-2 py-0.5 bg-slate-800 text-white font-black text-[9px] rounded uppercase">
                                    {{ $log->action }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] text-slate-500 font-medium">
                                <span>📍 {{ $log->position ? $log->position->position_code : 'Slot -' }}</span>
                                <span>{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs italic">Belum ada catatan aktivitas log.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- Slot Detail & Assign Modal -->
    @if ($showSlotModal && $selectedPosData)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-slate-100 animate-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="bg-slate-900 text-white p-6 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-300 font-mono font-bold text-lg border border-indigo-400/30">
                            📍
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase text-indigo-300 tracking-widest block">Informasi Slot Rak</span>
                            <h3 class="text-lg font-black font-mono tracking-tight text-white">{{ $selectedPosData->position_code }}</h3>
                        </div>
                    </div>
                    <button wire:click="closeSlotModal" class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Slot Status & Capacity Card -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">STATUS SLOT</span>
                            @if($selectedPosData->status === 'EMPTY')
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-extrabold text-[11px] rounded-lg inline-block">🟢 EMPTY (KOSONG)</span>
                            @elseif($selectedPosData->status === 'PARTIAL')
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-extrabold text-[11px] rounded-lg inline-block">🟡 PARTIAL (SEBAGIAN)</span>
                            @else
                                <span class="px-2.5 py-1 bg-rose-100 text-rose-800 font-extrabold text-[11px] rounded-lg inline-block">🔴 FULL (PENUH)</span>
                            @endif
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">TERISI / KAPASITAS</span>
                            <span class="font-black text-sm text-slate-900 font-mono block">{{ $selectedPosData->pallet_forms_count }} / {{ $selectedPosData->max_capacity }} Pallet</span>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">LAST ITEM CODE</span>
                            <span class="font-mono font-extrabold text-slate-800 text-xs block truncate" title="{{ $selectedPosData->last_item_code }}">
                                {{ $selectedPosData->last_item_code ?: '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Pallets currently in this slot -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wide">📦 Pallet tersimpan di slot ini:</h4>
                        </div>

                                @forelse ($selectedPosData->palletForms as $p)
                            <div class="p-4 bg-slate-50 rounded-2xl border {{ $p->is_overaged ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200/80' }} space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2.5 py-0.5 bg-slate-900 text-white font-mono font-bold text-[11px] rounded-lg">
                                                {{ $p->pallet_id }}
                                            </span>
                                            @if($p->is_overaged)
                                                <span class="px-2 py-0.5 bg-rose-600 text-white font-black text-[9px] rounded-md uppercase tracking-wider animate-pulse">
                                                    ⚠️ {{ $p->age_days }} HARI DI GUDANG
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 font-bold text-[9px] rounded-md">
                                                    {{ $p->age_days }} Hari
                                                </span>
                                            @endif
                                        </div>
                                        <div class="font-extrabold text-slate-900 mt-1.5 font-mono">{{ $p->part_no }}</div>
                                        <div class="text-[11px] text-slate-600 font-medium">{{ $p->model_name }}</div>
                                    </div>
                                    <div class="text-right space-y-1">
                                        <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 font-black text-[11px] rounded-lg block">
                                            {{ number_format($p->total_pallet_qty) }} Pcs
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold block">Box: {{ $p->box_qty }}</span>
                                    </div>
                                </div>

                                <!-- Lead Time & Inbound Timestamp Info -->
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/80 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[10px]">
                                    <div>
                                        <span class="text-slate-400 font-bold block uppercase">Scan Delivery:</span>
                                        <span class="font-bold text-slate-800">{{ $p->created_at ? $p->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold block uppercase">Masuk Rak:</span>
                                        <span class="font-bold text-slate-800">{{ $p->assigned_at ? $p->assigned_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Pending' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold block uppercase">Putaway Lead Time:</span>
                                        <span class="font-black text-indigo-700">{{ $p->putaway_lead_time }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold block uppercase">Umur di Gudang:</span>
                                        <span class="font-black {{ $p->is_overaged ? 'text-rose-600' : 'text-slate-700' }}">{{ $p->age_days }} Hari</span>
                                    </div>
                                </div>

                                @if($p->details->isNotEmpty())
                                    <div class="pt-2 border-t border-slate-200/60 space-y-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Breakdown Mixed Items:</span>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            @foreach ($p->details as $d)
                                                <div class="p-1.5 bg-white rounded-xl border border-slate-200 text-[10px] flex justify-between">
                                                    <span class="font-mono font-bold text-slate-800 truncate" title="{{ $d->part_no }}">{{ $d->part_no }}</span>
                                                    <span class="font-bold text-slate-600">{{ number_format($d->qty) }} Pcs</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-6 text-center text-slate-400 italic bg-slate-50 rounded-2xl border border-slate-100">
                                Slot ini saat ini kosong (tidak ada pallet tersimpan).
                            </div>
                        @endforelse
                    </div>

                    <!-- Unassigned Pallet Placement Widget -->
                    @if($unassignedPallets->isNotEmpty())
                        <div class="pt-3 border-t border-slate-100 space-y-3">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center space-x-1.5">
                                <span>⚡ Tempatkan Pallet Unassigned ke Slot Ini:</span>
                            </h4>

                            <div class="max-h-40 overflow-y-auto space-y-2 pr-1">
                                @foreach ($unassignedPallets as $unp)
                                    <div class="p-2.5 bg-amber-50/80 border border-amber-200 rounded-2xl flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-mono font-extrabold text-amber-900 text-[11px]">{{ $unp->pallet_id }}</span>
                                            <span class="text-[10px] text-amber-800 font-semibold block truncate max-w-[200px]">{{ $unp->part_no }} — {{ number_format($unp->total_pallet_qty) }} Pcs</span>
                                        </div>
                                        <button wire:click="assignPalletToSelectedSlot('{{ $unp->pallet_id }}')" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] rounded-xl transition-all shadow-xs shrink-0">
                                            Assign ke Slot Ini
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Modal Footer -->
                <div class="p-5 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button wire:click="closeSlotModal" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs transition-all">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
