<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">
        
        @if ($isViewOnly)
            <!-- Public View-Only Banner -->
            <div class="bg-gradient-to-r from-emerald-800 via-teal-800 to-emerald-900 text-white p-4 sm:p-5 rounded-2xl shadow-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20">
                        <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-emerald-400/20 text-emerald-200 text-[9px] font-black rounded-md border border-emerald-400/30 uppercase tracking-widest">Public View-Only</span>
                            <h1 class="text-lg font-black tracking-tight text-white">MATERIAL WAREHOUSE RACK MAPPING</h1>
                        </div>
                        <p class="text-xs text-emerald-200 mt-0.5">Monitoring realtime lokasi rak bahan baku, pencarian item, & summary prioritas FIFO</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <button wire:click="$set('showFifoSummaryModal', true)" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl text-xs font-black transition-all shadow-md flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>📊 SUMMARY STOCK & FIFO</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Branch / Warehouse Switcher Tabs -->
        <div class="bg-white p-3.5 rounded-2xl shadow-xs border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider pl-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    PILIH BRANCH:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($warehouses as $wh)
                        <button 
                            wire:click="$set('selectedWhseId', {{ $wh['id'] }})"
                            style="{{ $selectedWhseId == $wh['id'] ? 'background-color: #059669 !important; color: #ffffff !important;' : 'background-color: #f3f4f6; color: #374151;' }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs {{ $selectedWhseId == $wh['id'] ? 'bg-emerald-600 text-white ring-2 ring-emerald-400' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}"
                        >
                            <span>🏭</span>
                            <span style="{{ $selectedWhseId == $wh['id'] ? 'color: #ffffff !important;' : 'color: #1f2937;' }}">{{ $wh['whse_name'] }}</span>
                            <span 
                                style="{{ $selectedWhseId == $wh['id'] ? 'background-color: #047857 !important; color: #d1fae5 !important;' : 'background-color: #e5e7eb; color: #4b5563;' }}"
                                class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $selectedWhseId == $wh['id'] ? 'bg-emerald-700 text-emerald-100' : 'bg-gray-200 text-gray-600' }}"
                            >
                                {{ $wh['whse_code'] }}
                            </span>
                        </button>
                    @endforeach
                    <button 
                        wire:click="$set('selectedWhseId', 'ALL')"
                        style="{{ $selectedWhseId === 'ALL' ? 'background-color: #1f2937 !important; color: #ffffff !important;' : 'background-color: #f3f4f6; color: #4b5563;' }}"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $selectedWhseId === 'ALL' ? 'bg-gray-800 text-white ring-2 ring-gray-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' }}"
                    >
                        🌐 Semua Branch
                    </button>
                </div>
            </div>

            <div class="text-xs text-gray-400 font-medium pr-2 hidden md:block">
                Total: {{ count($racks) }} Rak Terdaftar
            </div>
        </div>

        <!-- Header, Search Bar & Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full uppercase tracking-wider">Raw Material Storage</span>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Material Warehouse Mapping</h1>
                </div>
                <p class="text-gray-500 text-sm mt-1">Monitoring & Tata Letak Rak Bahan Baku / Material</p>
            </div>

            <!-- Search Bar, Dropdown Filter & Actions -->
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <!-- Dropdown Filter Area / Blok Rak (A, B, C...) -->
                <div class="sm:w-44">
                    <select wire:model.live="selectedAreaFilter" class="w-full py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white transition text-gray-800">
                        <option value="ALL">-- Semua Area Rak --</option>
                        @foreach ($availableAreas as $area)
                            <option value="{{ $area }}">{{ $area }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Search Item Material Yang Ada -->
                <div class="sm:w-60">
                    <select wire:model.live="selectedItemFilter" class="w-full py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white transition text-gray-800">
                        <option value="">-- Semua Material Item --</option>
                        @foreach ($availableItemCodes as $itemCode)
                            <option value="{{ $itemCode }}">{{ $itemCode }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Text Search Input with 1s Debounce & Autocomplete Dropdown -->
                <div class="relative flex-grow sm:w-64">
                    <input type="text" 
                           wire:model.live.debounce.1000ms="searchTerm" 
                           placeholder="Cari Part Code, Pallet ID, Lot, Rak..." 
                           class="w-full pl-10 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                    <div class="absolute left-3 top-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    @if ($searchTerm)
                        <button wire:click="$set('searchTerm', '')" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600 p-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif

                    <!-- Autocomplete Search Suggestions Dropdown List -->
                    @if (!empty($searchSuggestions))
                        <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-2xl shadow-xl z-50 overflow-hidden divide-y divide-gray-100 max-h-60 overflow-y-auto animate-in fade-in duration-150">
                            <div class="px-3 py-1.5 bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Rekomendasi Hasil Pencarian
                            </div>
                            @foreach ($searchSuggestions as $sug)
                                <div wire:click="selectSearchSuggestion('{{ $sug['item_code'] }}')" class="p-2.5 hover:bg-emerald-50 cursor-pointer flex justify-between items-center transition">
                                    <div>
                                        <div class="font-mono font-bold text-xs text-emerald-900">{{ $sug['item_code'] }}</div>
                                        <div class="text-[11px] text-gray-500 truncate max-w-[180px]">{{ $sug['item_description'] }}</div>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $sug['pallet_id'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Audit Harian Button -->
                <a href="{{ route('mwh.daily-audit') }}" style="background-color: #047857 !important; color: #ffffff !important;" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 whitespace-nowrap active:scale-95" title="Buka Form Audit Harian Material">
                    <span>📋</span>
                    <span>AUDIT HARIAN</span>
                </a>

                <!-- FIFO Summary Button (In Main Header) -->
                @if (!$isViewOnly)
                    <button wire:click="$set('showFifoSummaryModal', true)" style="background-color: #0f766e !important; color: #ffffff !important;" class="px-4 py-2.5 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>SUMMARY FIFO</span>
                    </button>
                @endif

                <!-- Add Rack Button (Hidden in View-Only) -->
                @if (!$isViewOnly)
                    <button wire:click="$set('showAddRackModal', true)" style="background-color: #059669 !important; color: #ffffff !important;" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>ADD RACK</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Area Temporary / Transit Alert Banner -->
        @if (!empty($temporaryPallets) && $temporaryPallets->isNotEmpty())
            <div class="bg-gradient-to-r from-amber-500 via-amber-600 to-amber-700 text-white p-4 sm:p-5 rounded-2xl shadow-md border border-amber-400 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 animate-in fade-in">
                <div class="flex items-center space-x-3.5">
                    <div class="w-11 h-11 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/30 text-xl flex-shrink-0">
                        ⏳
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-black/20 text-amber-100 text-[9px] font-black rounded-md uppercase tracking-widest border border-white/20">Area Transit</span>
                            <h2 class="text-base font-black tracking-tight">MATERIAL DI AREA TEMPORARY (BELUM ADA SLOT)</h2>
                        </div>
                        <p class="text-xs text-amber-100 mt-0.5">
                            Terdapat <strong class="text-white underline font-bold">{{ $temporaryPallets->count() }} Pallet</strong> (Total <strong class="text-white font-bold">{{ number_format($temporaryPallets->sum('current_qty'), 2) }} KG</strong>) yang siap dipindahkan ke slot rak kosong.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end md:self-auto flex-wrap">
                    @if (!$isViewOnly)
                        <button type="button" wire:click="openTemporaryModal" class="px-4 py-2.5 bg-white hover:bg-amber-50 text-amber-900 rounded-xl text-xs font-black transition-all shadow-md flex items-center gap-1.5 whitespace-nowrap active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            <span>📥 ASSIGN LANGSUNG KE SLOT RAK</span>
                        </button>
                    @endif
                    <a href="{{ route('mwh.pallets.index', ['statusFilter' => 'TEMPORARY', 'whse_id' => $selectedWhseId]) }}" class="px-3.5 py-2.5 bg-amber-800/60 hover:bg-amber-800 text-white rounded-xl text-xs font-bold transition-all border border-amber-300/40 flex items-center gap-1.5 whitespace-nowrap" title="Buka Daftar Tabel Lengkap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Tabel Pallet</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Special Active Item FIFO Summary Banner (Appears right above slot status legend bar when item is typed/selected) -->
        @if ($activeItemSummary)
            <div class="bg-gradient-to-r from-emerald-950 via-teal-950 to-slate-900 text-white p-5 rounded-2xl shadow-lg border border-emerald-700/50 space-y-4 animate-in fade-in duration-200">
                <div class="flex flex-wrap justify-between items-start sm:items-center gap-3 border-b border-emerald-700/50 pb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-emerald-500/20 text-emerald-300 rounded-xl flex items-center justify-center font-mono font-black border border-emerald-400/30 text-lg shadow-inner">
                            📦
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-[9px] font-black uppercase tracking-widest bg-emerald-500/30 text-emerald-200 px-2 py-0.5 rounded border border-emerald-400/30">Ringkasan Stok FIFO Material</span>
                                <span class="font-mono text-base font-black text-emerald-300">{{ $activeItemSummary['item_code'] }}</span>
                            </div>
                            <p class="text-xs text-slate-300 font-medium mt-0.5">{{ $activeItemSummary['item_description'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-300 tracking-wider block">Total Stok</span>
                            <span class="text-xl font-black font-mono text-white">{{ number_format($activeItemSummary['total_qty'], 2) }} KG</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-300 tracking-wider block">Total Pallet</span>
                            <span class="text-xl font-black font-mono text-emerald-300">{{ $activeItemSummary['pallet_count'] }} Pallet</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-[10px] font-black uppercase tracking-widest text-emerald-300 flex items-center justify-between">
                        <span>Urutan Pengambilan Pallet (FIFO - Terlama Dulu)</span>
                        <span class="text-[9px] text-emerald-400 font-normal">Klik item untuk membuka detail slot</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                        @foreach ($activeItemSummary['pallets'] as $idx => $pal)
                            <div wire:click="selectPosition({{ $pal->position_id }})" class="p-3 bg-emerald-950/70 hover:bg-emerald-800/80 rounded-xl border border-emerald-700/60 transition cursor-pointer space-y-1 shadow-xs hover:border-emerald-400">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-mono font-black text-emerald-300">#{{ $idx + 1 }} {{ $pal->pallet_id }}</span>
                                    <span class="px-2 py-0.5 bg-emerald-400/20 text-emerald-200 text-[10px] font-bold rounded-md font-mono border border-emerald-400/30">
                                        {{ $pal->position ? $pal->position->position_code : 'Unassigned' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-baseline text-xs pt-0.5">
                                    <span class="font-mono font-black text-white text-sm">{{ number_format($pal->current_qty, 2) }} KG</span>
                                    <span class="text-[10px] text-slate-300 font-mono">{{ $pal->created_at ? $pal->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : '-' }} WIB</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Legend & Search Result Counter -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white px-5 py-3 rounded-xl border border-gray-100 shadow-xs">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status Slot:</span>
                <div class="flex items-center px-2.5 py-1 bg-gray-50 rounded-lg border border-gray-200 text-[10px] font-bold text-gray-500">
                    <span class="w-2 h-2 bg-gray-300 rounded-full mr-2"></span> EMPTY
                </div>
                <div class="flex items-center px-2.5 py-1 bg-amber-50 rounded-lg border border-amber-200 text-[10px] font-bold text-amber-700">
                    <span class="w-2 h-2 bg-amber-400 rounded-full mr-2"></span> PARTIAL
                </div>
                <div class="flex items-center px-2.5 py-1 bg-emerald-50 rounded-lg border border-emerald-200 text-[10px] font-bold text-emerald-700">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span> FULL
                </div>
                <div class="flex items-center px-2.5 py-1 bg-yellow-100 rounded-lg border border-yellow-400 text-[10px] font-black text-amber-900 shadow-xs">
                    <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-ping"></span> MATCH SEARCH
                </div>
            </div>

            @if ($searchTerm)
                <div class="text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200">
                    Ditemukan <strong class="text-emerald-900 font-black">{{ count($matchingPositionIds) }}</strong> slot yang sesuai dengan "<span class="italic">{{ $searchTerm }}</span>"
                </div>
            @endif
        </div>

        <!-- Session Alert -->
        @if (session()->has('success'))
            <div class="bg-emerald-100 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-emerald-800 text-sm font-bold uppercase tracking-widest italic">{{ session('success') }}</p>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-red-700 text-sm font-bold uppercase tracking-widest italic">{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Grid Container Grouped by Area -->
            <div class="flex-grow space-y-8" id="mapping-grid">
                @forelse($groupedRacks as $areaName => $areaRacks)
                    <div class="space-y-4">
                        <!-- Area Section Header -->
                        <div class="flex items-center justify-between bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-sm border border-slate-800">
                            <div class="flex items-center space-x-3">
                                <span class="w-3 h-3 bg-emerald-400 rounded-full"></span>
                                <h2 class="text-base font-black tracking-wider uppercase font-mono text-emerald-300">🏢 {{ $areaName }}</h2>
                            </div>
                            <span class="text-xs font-bold font-mono bg-emerald-950 text-emerald-200 px-3 py-1 rounded-xl border border-emerald-800">
                                {{ count($areaRacks) }} RAK
                            </span>
                        </div>

                        <!-- Racks in this Area -->
                        <div class="flex flex-wrap gap-6 items-start">
                            @foreach($areaRacks as $rack)
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4 w-full md:w-[calc(50%-12px)] xl:w-[calc(33.333%-16px)]">
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                                        <div>
                                            <h3 class="text-lg font-black text-emerald-700 italic tracking-tighter uppercase">Rack {{ $rack->rack_code }}</h3>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-full">{{ count($rack->positions) }} Slots</span>
                                            @if (!$isViewOnly)
                                                <button wire:click="deleteRack({{ $rack->id }})" onclick="return confirm('Anda yakin ingin menghapus rak material ini beserta seluruh isinya?')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Hapus Rak">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Vertical Level Columns -->
                                    <div class="flex flex-row gap-4 overflow-x-auto pb-2">
                                        @foreach($rack->positions->groupBy('level_no')->sortKeys() as $level => $positions)
                                            <div class="flex flex-col gap-2 flex-1 min-w-[75px]">
                                                <div class="text-[9px] font-black text-emerald-600 text-center uppercase tracking-tighter border-b border-emerald-100 mb-1 pb-1">
                                                    LVL {{ $level }}
                                                </div>
                                                <div class="space-y-2">
                                                    @foreach($positions as $pos)
                                                        @php
                                                            $isMatched = in_array($pos->id, $matchingPositionIds);
                                                            $isSelected = $selectedPositionId == $pos->id;
                                                            $activePallets = $pos->pallets ? $pos->pallets->where('current_qty', '>', 0) : collect();
                                                            $totalQty = (float) $activePallets->sum('current_qty');
                                                            $maxCap = (float) ($pos->max_capacity > 0 ? $pos->max_capacity : 1000.0);

                                                            if (round($totalQty, 2) >= round($maxCap, 2)) {
                                                                $effectiveStatus = 'FULL';
                                                            } elseif (round($totalQty, 2) > 0) {
                                                                $effectiveStatus = 'PARTIAL';
                                                            } else {
                                                                $effectiveStatus = 'EMPTY';
                                                            }

                                                            $statusColor = 'bg-gray-100 hover:bg-gray-200 border-gray-200 text-gray-700';
                                                            if ($effectiveStatus === 'PARTIAL') $statusColor = 'bg-amber-100 hover:bg-amber-200 border-amber-300 text-amber-900';
                                                            if ($effectiveStatus === 'FULL') $statusColor = 'bg-emerald-100 hover:bg-emerald-200 border-emerald-300 text-emerald-900';
                                                             
                                                            if ($isMatched && $effectiveStatus !== 'EMPTY') {
                                                                $statusColor = 'bg-yellow-200 hover:bg-yellow-300 border-amber-500 text-amber-950 font-black shadow-md ring-4 ring-yellow-400/80 animate-pulse';
                                                            } elseif ($isMatched && $effectiveStatus === 'EMPTY') {
                                                                $statusColor = 'bg-gray-100 border-amber-400 text-gray-700 ring-2 ring-amber-400/60';
                                                            }

                                                            if ($isSelected) {
                                                                $statusColor .= ' ring-4 ring-emerald-500 shadow-lg scale-105';
                                                            }

                                                            $palletCount = $pos->pallets ? count($pos->pallets) : 0;
                                                        @endphp

                                                        <button 
                                                            wire:click="selectPosition({{ $pos->id }})"
                                                            class="w-full text-left p-2 rounded-xl border transition-all duration-150 relative flex flex-col justify-between min-h-[60px] cursor-pointer {{ $statusColor }}"
                                                        >
                                                            <div class="flex justify-between items-start w-full">
                                                                <span class="font-mono font-black text-[10px] tracking-tighter">{{ $pos->position_code }}</span>
                                                                <span class="text-[8px] font-bold px-1 rounded bg-black/5 uppercase">
                                                                    {{ $effectiveStatus }}
                                                                </span>
                                                            </div>

                                                            @if($palletCount > 0)
                                                                <div class="text-[7px] font-extrabold text-emerald-900 bg-white/90 px-1 py-0.5 rounded leading-none w-full truncate text-center shadow-2xs">
                                                                    {{ $palletCount }} Pallet ({{ number_format($pos->pallets->sum('current_qty'), 0) }} KG)
                                                                </div>
                                                            @elseif($pos->last_item_code)
                                                                <div class="text-[7px] font-extrabold text-emerald-700 bg-emerald-50/80 px-1 py-0.5 rounded leading-none w-full truncate text-center">
                                                                    {{ $pos->last_item_code }}
                                                                </div>
                                                            @else
                                                                <div class="text-[7px] font-medium text-gray-400 leading-none">
                                                                    Kosong
                                                                </div>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="w-full bg-white p-12 rounded-2xl border border-gray-100 text-center space-y-4">
                        <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <h3 class="text-gray-600 font-bold text-base">Belum Ada Rak Material</h3>
                        <p class="text-gray-400 text-xs max-w-sm mx-auto">Klik tombol "+ ADD RACK" di atas untuk menambahkan master lokasi rak baru.</p>
                    </div>
                @endforelse
            </div>

            <!-- Detail Sidebar -->
            <div id="slot-detail-panel" class="w-full lg:w-96 flex-shrink-0">
                @if($showDetail && $selectedPosData)
                    <div x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden sticky top-6 animate-in slide-in-from-right duration-300">
                        <!-- Header Slot Info -->
                        @php
                            $totalQtyInSlot = $selectedPosData->pallets ? $selectedPosData->pallets->sum('current_qty') : 0;
                            $maxCap = max(1, $selectedPosData->max_capacity);
                            $capPct = round(($totalQtyInSlot / $maxCap) * 100);
                            $isOverCap = $totalQtyInSlot > $maxCap;
                            $barWidth = min(100, $capPct);

                            // Dynamic Header Gradient matching slot status
                            $headerBg = 'from-slate-700 to-slate-800'; // EMPTY (Gray)
                            if ($isOverCap) {
                                $headerBg = 'from-rose-700 to-red-800'; // OVER CAPACITY (Red)
                            } elseif ($selectedPosData->status === 'FULL') {
                                $headerBg = 'from-emerald-600 to-teal-700'; // FULL (Green)
                            } elseif ($selectedPosData->status === 'PARTIAL') {
                                $headerBg = 'from-amber-500 to-yellow-600'; // PARTIAL (Yellow)
                            }
                        @endphp

                        <div class="bg-gradient-to-r {{ $headerBg }} p-6 text-white relative">
                            <button wire:click="$set('showDetail', false)" class="absolute top-4 right-4 text-white/70 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>

                            <div class="relative z-10 space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded">
                                        Rak {{ $selectedPosData->rack ? $selectedPosData->rack->rack_code : '-' }}
                                    </span>
                                    @if ($isOverCap)
                                        <span class="text-[10px] font-black uppercase tracking-widest bg-rose-900 text-white px-2 py-0.5 rounded animate-pulse">
                                            ⚠️ OVER CAPACITY ({{ $capPct }}%)
                                        </span>
                                    @else
                                        <span class="text-[10px] font-black uppercase tracking-widest bg-white/20 px-2 py-0.5 rounded">
                                            SLOT RAK: {{ $selectedPosData->status }} (Kapasitas {{ $capPct }}%)
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-2xl font-black italic uppercase tracking-tighter">{{ $selectedPosData->position_code }}</h3>
                                <p class="text-white/80 text-xs font-medium">Level {{ $selectedPosData->level_no }} &bull; Slot {{ $selectedPosData->slot_no }} ({{ $selectedPosData->slot_label ?: 'Slot' }})</p>

                                <!-- Capacity Progress Bar -->
                                <div class="pt-3 space-y-1">
                                    <div class="flex justify-between text-[11px] font-bold">
                                        <span>Kapasitas Terisi:</span>
                                        <span>{{ number_format($totalQtyInSlot, 2) }} / {{ number_format($maxCap) }} KG ({{ $capPct }}%)</span>
                                    </div>
                                    <div class="w-full bg-black/30 rounded-full h-2 overflow-hidden">
                                        <div class="{{ $isOverCap ? 'bg-amber-300 animate-pulse' : ($selectedPosData->status === 'PARTIAL' ? 'bg-amber-300' : ($selectedPosData->status === 'FULL' ? 'bg-emerald-300' : 'bg-slate-300')) }} h-full rounded-full transition-all duration-500" style="width: {{ $barWidth }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-6 max-h-[calc(100vh-220px)] overflow-y-auto">
                            <!-- Section Temporary Assign: Tampil di Bagian Atas Drawer jika slot belum FULL -->
                            @if (!$isViewOnly && $selectedPosData->status !== 'FULL' && !empty($temporaryPallets) && $temporaryPallets->isNotEmpty())
                                @php
                                    $currentSlotStored = $selectedPosData->pallets ? $selectedPosData->pallets->sum('current_qty') : 0;
                                    $slotRemainingCap = max(0, (float)$selectedPosData->max_capacity - (float)$currentSlotStored);
                                @endphp
                                <div class="p-3.5 bg-amber-50/70 border border-amber-300 rounded-2xl space-y-2.5 shadow-xs">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-2.5 h-2.5 bg-amber-500 rounded-full inline-block animate-pulse"></span>
                                            <h4 class="text-xs font-black text-amber-900 uppercase tracking-widest">
                                                Masukkan dari Area Temporary
                                            </h4>
                                        </div>
                                        <span class="text-[10px] font-black text-amber-900 bg-amber-200 border border-amber-400 px-2 py-0.5 rounded-full">
                                            Sisa: {{ number_format($slotRemainingCap, 2) }} KG
                                        </span>
                                    </div>

                                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                        @foreach ($temporaryPallets as $tempPallet)
                                            @php
                                                $fitsInSlot = $slotRemainingCap >= (float)$tempPallet->current_qty;
                                            @endphp
                                            <div class="p-2.5 rounded-xl border {{ $fitsInSlot ? 'bg-white border-amber-300 shadow-2xs' : 'bg-gray-100 border-gray-200 opacity-60' }} flex items-center justify-between gap-2">
                                                <div>
                                                    <div class="flex items-center space-x-1.5 font-mono text-xs font-bold text-gray-900">
                                                        <span>{{ $tempPallet->pallet_id }}</span>
                                                        <span class="text-amber-800 font-black">({{ $tempPallet->item_code }})</span>
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 font-bold">
                                                        Qty: <strong class="text-emerald-700">{{ number_format($tempPallet->current_qty, 2) }} KG</strong>
                                                        @if ($tempPallet->lot_no) &bull; Lot: {{ $tempPallet->lot_no }} @endif
                                                    </div>
                                                </div>

                                                @if ($fitsInSlot)
                                                    <button type="button" 
                                                            wire:click="assignTemporaryPalletToSlot({{ $tempPallet->id }})" 
                                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-xs flex-shrink-0 cursor-pointer">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                        <span>+ Masuk Slot</span>
                                                    </button>
                                                @else
                                                    <span class="text-[10px] text-rose-500 font-bold flex-shrink-0" title="Kapasitas slot tersisa tidak cukup untuk pallet ini">
                                                        Over Kapasitas
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Section 1: Daftar Pallet ID yang tersimpan di Slot ini -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                    <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest flex items-center space-x-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span>Isi Material Dalam Slot Ini</span>
                                    </h4>
                                    <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold">
                                        {{ count($selectedPosData->pallets ?: []) }} Pallet
                                    </span>
                                </div>

                                @if ($selectedPosData->pallets && count($selectedPosData->pallets) > 0)
                                    <div class="space-y-3">
                                        @foreach ($selectedPosData->pallets as $pal)
                                            <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 hover:border-emerald-300 transition space-y-2">
                                                <div class="flex justify-between items-center">
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="font-mono font-black text-xs text-emerald-800">{{ $pal->pallet_id }}</span>
                                                        @if ($pal->is_qc_hold)
                                                            <span class="px-1.5 py-0.5 bg-rose-600 text-white rounded text-[9px] font-black animate-pulse">HOLD</span>
                                                        @endif
                                                    </div>
                                                    @if ($pal->is_qc_hold)
                                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded-full text-[9px] font-black border border-rose-300">
                                                            ⛔ QC HOLD
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 {{ $pal->status === 'STORED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} rounded-full text-[9px] font-bold">
                                                            {{ $pal->status }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($pal->is_qc_hold && $pal->qc_hold_reason)
                                                    <div class="p-1.5 bg-rose-50 border border-rose-200 rounded-lg text-[10px] text-rose-700 font-medium">
                                                        <strong>Alasan QC Hold:</strong> {{ $pal->qc_hold_reason }}
                                                    </div>
                                                @endif

                                                <div class="space-y-1">
                                                    <div class="font-mono font-bold text-xs text-gray-900">{{ $pal->item_code }}</div>
                                                    <div class="text-[11px] text-gray-600 leading-tight">{{ $pal->material ? $pal->material->item_description : '-' }}</div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-200/60 text-[11px]">
                                                    <div>
                                                        <span class="text-gray-400 text-[9px] uppercase tracking-wider block">Sisa Stok:</span>
                                                        <strong class="text-emerald-700 font-bold text-xs">{{ number_format($pal->current_qty, 2) }} KG</strong>
                                                        <span class="text-gray-400 text-[10px]"> (Awal: {{ number_format($pal->initial_qty, 2) }} KG)</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-400 text-[9px] uppercase tracking-wider block">Lot / Supplier:</span>
                                                        <span class="font-mono text-gray-800 font-bold">{{ $pal->lot_no ?: '-' }}</span>
                                                        <div class="text-[10px] text-gray-500 truncate">{{ $pal->incomingHeader ? ($pal->incomingHeader->supplier_name ?: '-') : '-' }}</div>
                                                    </div>
                                                </div>

                                                <div class="text-[10px] text-gray-500 flex justify-between items-center font-mono pt-1">
                                                    <span>Tgl Kedatangan: <strong class="text-gray-800">{{ $pal->incomingHeader && $pal->incomingHeader->arrival_date ? $pal->incomingHeader->arrival_date->format('d M Y') : ($pal->created_at ? $pal->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-') }}</strong></span>
                                                </div>

                                                <div class="pt-1 flex justify-end gap-1.5 flex-wrap">
                                                    @if (!$isViewOnly)
                                                        <button wire:click="toggleQcHoldPallet({{ $pal->id }})" class="px-2 py-1 {{ $pal->is_qc_hold ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white' }} rounded-lg text-[10px] font-bold transition flex items-center space-x-1" title="Toggle QC Hold Status">
                                                            <span>{{ $pal->is_qc_hold ? 'Release QC' : 'QC Hold' }}</span>
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('mwh.pallet.print', $pal->pallet_id) }}" target="_blank" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition flex items-center space-x-1" title="Cetak Label QR">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                        <span>Print QR</span>
                                                    </a>
                                                    @if (!$pal->is_qc_hold)
                                                        <a href="{{ route('mwh.outgoing.create', ['selected_item_code' => $pal->item_code]) }}" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[10px] font-bold transition flex items-center space-x-1" title="Picking Outgoing">
                                                            <span>Picking</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-6 text-center bg-gray-50 border border-dashed border-gray-200 rounded-2xl text-gray-400 space-y-1">
                                        <svg class="w-8 h-8 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        <p class="text-xs font-bold text-gray-500">Slot ini kosong</p>
                                        <p class="text-[10px] text-gray-400">Belum ada pallet material yang disimpan di slot ini.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Section 2: Form Edit Configuration Slot (Collapsible) -->
                            <div class="border-t border-gray-100 pt-4 space-y-3" x-data="{ showEditSlot: false }">
                                <button @click="showEditSlot = !showEditSlot" type="button" class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl border border-gray-200 transition group cursor-pointer">
                                    <h4 class="text-xs font-black text-gray-800 uppercase tracking-widest flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-emerald-600 group-hover:rotate-45 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Pengaturan & Edit Slot</span>
                                    </h4>
                                    <div class="flex items-center space-x-1.5 text-gray-500 font-mono text-[10px]">
                                        <span class="font-bold uppercase text-emerald-700" x-text="showEditSlot ? 'Tutup' : 'Klik Buka'"></span>
                                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': showEditSlot }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </button>

                                <div x-show="showEditSlot" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-4 pt-2">
                                    <!-- Input Material ke Slot -->
                                    @if (!$isViewOnly)
                                        <div class="pb-2 border-b border-gray-100">
                                            <button type="button" wire:click="toggleAddMaterialForm" class="w-full py-2 px-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                <span>{{ $showAddMaterialForm ? 'Tutup Form Input Material' : '+ Input Material Ke Slot Ini' }}</span>
                                            </button>

                                            @if ($showAddMaterialForm)
                                                <form wire:submit.prevent="storeMaterialToSlot" class="mt-3 p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-3 animate-in fade-in duration-200">
                                                    <div class="flex justify-between items-center border-b border-emerald-200/60 pb-1.5">
                                                        <span class="text-[10px] font-black text-emerald-900 uppercase tracking-widest">Input Material ke Slot {{ $selectedPosData->position_code }}</span>
                                                        <span class="text-[9px] text-emerald-700 font-bold">Penyesuaian Actual</span>
                                                    </div>

                                                    <!-- Part Code Autocomplete -->
                                                    <div class="relative">
                                                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Part Code Material *</label>
                                                        <input type="text" wire:model.live="new_item_code" placeholder="Cari Part Code / Nama..." class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-emerald-500">
                                                        
                                                        @if (!empty($newMaterialSearchResults))
                                                            <div class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 z-30 max-h-40 overflow-y-auto">
                                                                @foreach ($newMaterialSearchResults as $res)
                                                                    <button type="button" wire:click="selectNewMaterial('{{ $res['item_code'] }}', '{{ addslashes($res['item_description'] ?? '') }}')" class="w-full text-left px-3 py-1.5 hover:bg-emerald-50 transition border-b border-gray-50 flex flex-col">
                                                                        <span class="text-xs font-bold font-mono text-gray-900">{{ $res['item_code'] }}</span>
                                                                        <span class="text-[9px] text-gray-500 truncate">{{ $res['item_description'] }}</span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @error('new_item_code') <span class="text-[9px] text-rose-500 font-bold block mt-0.5">{{ $message }}</span> @enderror
                                                    </div>

                                                    @if ($new_item_description)
                                                        <div class="p-2 bg-white/80 rounded-lg text-[10px] text-gray-700 border border-emerald-100 truncate">
                                                            <span class="font-bold">Deskripsi:</span> {{ $new_item_description }}
                                                        </div>
                                                    @endif

                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Qty (KG) *</label>
                                                            <input type="number" step="0.01" wire:model="new_qty" placeholder="Ex: 500" class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500">
                                                            @error('new_qty') <span class="text-[9px] text-rose-500 font-bold block mt-0.5">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Tanggal Masuk (FIFO) *</label>
                                                            <input type="date" wire:model="new_created_at" class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500">
                                                            @error('new_created_at') <span class="text-[9px] text-rose-500 font-bold block mt-0.5">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Lot / Batch No</label>
                                                            <input type="text" wire:model="new_lot_no" placeholder="Ex: LOT-01" class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Supplier (Opsional)</label>
                                                            <input type="text" wire:model="new_supplier_name" placeholder="Nama Supplier" class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-end space-x-2 pt-1">
                                                        <button type="button" wire:click="toggleAddMaterialForm" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 rounded-lg font-bold text-[10px]">
                                                            Batal
                                                        </button>
                                                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[10px] shadow-sm transition">
                                                            + Simpan Material ke Slot
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Form Edit Slot -->
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Kode Slot / Position Code</label>
                                        <input type="text" wire:model="editPositionCode" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold focus:bg-white focus:border-emerald-500 outline-none uppercase transition-all">
                                        @error('editPositionCode') <span class="text-[10px] text-red-500 font-semibold">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Label Custom / Display Name</label>
                                        <input type="text" wire:model="editSlotLabel" placeholder="Misal: RESIN-A1, BAGGING-01" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold focus:bg-white focus:border-emerald-500 outline-none transition-all">
                                        @error('editSlotLabel') <span class="text-[10px] text-red-500 font-semibold">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status Slot</label>
                                            <select wire:model="editStatus" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:bg-white focus:border-emerald-500 outline-none transition-all">
                                                <option value="EMPTY">EMPTY</option>
                                                <option value="PARTIAL">PARTIAL</option>
                                                <option value="FULL">FULL</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Max Capacity (KG)</label>
                                            <input type="number" step="0.01" wire:model="editMaxCapacity" placeholder="Ex: 1000" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:bg-white focus:border-emerald-500 outline-none transition-all">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Last Item Code (Quick Note)</label>
                                        <input type="text" wire:model="editLastItemCode" placeholder="Kode Material (Opsional)" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold focus:bg-white focus:border-emerald-500 outline-none uppercase transition-all">
                                    </div>

                                    <div class="pt-2 flex gap-2">
                                        <button wire:click="saveSettings" class="flex-grow py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-[10px] uppercase tracking-widest shadow-md transition-all active:scale-95">
                                            Simpan Perubahan
                                        </button>
                                        @if (!$isViewOnly)
                                            <button wire:click="resetSlot" onclick="return confirm('Reset status slot ini menjadi EMPTY dan kosongkan pallet?')" class="p-2.5 bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 border border-gray-200 rounded-xl transition-all" title="Reset / Hapus Slot">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Action Hapus Pallet dari Slot (Pindah ke Pengaturan & Edit Slot) -->
                                    @if ($selectedPosData->pallets && count($selectedPosData->pallets) > 0 && !$isViewOnly)
                                        <div class="pt-3 border-t border-gray-100 space-y-2">
                                            <label class="block text-[10px] font-black text-rose-500 uppercase tracking-widest">Hapus Unit Pallet Dari Slot Ini</label>
                                            <div class="space-y-1.5">
                                                @foreach ($selectedPosData->pallets as $pal)
                                                    <div class="flex items-center justify-between p-2.5 bg-rose-50/70 border border-rose-200/80 rounded-xl">
                                                        <div>
                                                            <div class="font-mono font-bold text-xs text-rose-900">{{ $pal->pallet_id }}</div>
                                                            <div class="text-[10px] text-rose-700 font-mono">{{ $pal->item_code }} ({{ number_format($pal->current_qty, 0) }} KG)</div>
                                                        </div>
                                                        <button type="button" onclick="confirm('Hapus Pallet {{ $pal->pallet_id }} dari slot ini?') || event.stopImmediatePropagation()" wire:click="deletePalletFromSlot({{ $pal->id }})" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold transition flex items-center space-x-1 shadow-2xs">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                            <span>Hapus Pallet</span>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>       </div>
                        </div>
                    </div>
                @else
                    <div class="h-full flex items-center justify-center border-4 border-dashed border-gray-200 rounded-[3rem] p-12 grayscale opacity-40 min-h-[350px]">
                        <div class="text-center space-y-3">
                            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                            <p class="text-gray-400 font-bold text-xs uppercase tracking-[0.3em]">Pilih Slot Rak</p>
                            <p class="text-gray-400 text-[10px] italic">Klik salah satu slot pada rak material <br> untuk melihat detail pallet & mengedit slot</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Modal Add Rack -->
    @if($showAddRackModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden border border-gray-100 animate-in zoom-in-95 duration-200">
                <div class="bg-emerald-600 p-6 text-white text-center">
                    <h3 class="text-xl font-black italic uppercase tracking-tighter">Add Material Rack</h3>
                    <p class="text-emerald-100 text-xs font-medium">Buat tata letak rak material baru</p>
                </div>
                
                <div class="p-6 space-y-5">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[9px] font-black uppercase text-gray-400 tracking-widest mb-1">Kode Rak Material</label>
                            <input type="text" wire:model="newRackCode" placeholder="Ex: MTR-A, RAK-01" 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-emerald-500 outline-none font-black text-xl text-center uppercase tracking-widest transition-all">
                            @error('newRackCode') <span class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[9px] font-black uppercase text-gray-400 tracking-widest mb-1 text-center">Levels</label>
                                <input type="number" wire:model="newLevels" min="1"
                                    class="w-full px-2 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-center text-base focus:bg-white focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black uppercase text-gray-400 tracking-widest mb-1 text-center">Slots/LVL</label>
                                <input type="number" wire:model="newSlotsPerLevel" min="1"
                                    class="w-full px-2 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-center text-base focus:bg-white focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black uppercase text-gray-400 tracking-widest mb-1 text-center">Max (KG)</label>
                                <input type="number" step="0.01" wire:model="newMaxCapacity" min="1"
                                    class="w-full px-2 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-center text-base focus:bg-white focus:border-emerald-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button wire:click="$set('showAddRackModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-black rounded-xl uppercase text-[10px] tracking-widest transition-all">Batal</button>
                        <button wire:click="createNewRack" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl uppercase text-[10px] tracking-widest shadow-md transition-all">BUAT RAK</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Summary FIFO Material -->
    @if($showFifoSummaryModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-gray-900/70 backdrop-blur-md animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden border border-gray-100 flex flex-col animate-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-emerald-800 to-teal-900 p-5 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md">
                            <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight text-white uppercase">SUMMARY STOCK & PRIORITAS FIFO</h3>
                            <p class="text-xs text-emerald-200">Total Material Aktif: {{ count($fifoSummaryData) }} Item &bull; Diurutkan berdasarkan FIFO (Terlama Dulu)</p>
                        </div>
                    </div>
                    <button wire:click="$set('showFifoSummaryModal', false)" class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable List) -->
                <div class="p-6 overflow-y-auto space-y-4 flex-grow bg-slate-50">
                    @forelse ($fifoSummaryData as $item)
                        @php
                            $isExpanded = ($expandedFifoItemCode === $item['item_code']);
                        @endphp
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition-all duration-200">
                            <!-- Summary Bar Header (Clickable Dropdown Accordion) -->
                            <div wire:click="toggleFifoItemExpand('{{ $item['item_code'] }}')" class="p-4 bg-white hover:bg-emerald-50/50 cursor-pointer flex flex-wrap sm:flex-nowrap justify-between items-center gap-3 transition">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono font-black text-slate-900 text-base">{{ $item['item_code'] }}</span>
                                        <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">{{ $item['pallet_count'] }} Pallet</span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-semibold">{{ $item['item_description'] }}</div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Stok</div>
                                        <div class="text-lg font-black text-emerald-700 font-mono">{{ number_format($item['total_qty'], 2) }} KG</div>
                                    </div>

                                    <div class="text-right hidden sm:block">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Prioritas FIFO (Masuk Pertama)</div>
                                        <div class="text-xs font-mono font-bold text-slate-800">{{ $item['oldest_date'] ? $item['oldest_date']->timezone('Asia/Jakarta')->format('d M Y H:i') : '-' }} WIB</div>
                                    </div>

                                    <div class="p-2 bg-slate-100 text-slate-600 rounded-xl">
                                        <svg class="w-4 h-4 transform transition-transform duration-200 {{ $isExpanded ? 'rotate-180 text-emerald-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Expandable Dropdown Details (Pallet List + Outgoing History) -->
                            @if ($isExpanded)
                                <div class="p-5 bg-slate-100/70 border-t border-slate-200/80 space-y-5 animate-in slide-in-from-top-2 duration-200">
                                    
                                    <!-- 1. Pallet List (FIFO Order) -->
                                    <div class="space-y-2">
                                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                            <span>Urutan Pallet (FIFO - Terlama Dulu)</span>
                                        </h4>

                                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                                    <tr>
                                                        <th class="py-2.5 px-3">Pallet ID</th>
                                                        <th class="py-2.5 px-3">Slot Rak</th>
                                                        <th class="py-2.5 px-3">Lot / Batch</th>
                                                        <th class="py-2.5 px-3 text-right">Sisa Qty (KG)</th>
                                                        <th class="py-2.5 px-3">Tgl Masuk (FIFO)</th>
                                                        <th class="py-2.5 px-3 text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach ($item['pallets'] as $idx => $pal)
                                                        <tr class="hover:bg-emerald-50/40">
                                                            <td class="py-2.5 px-3 font-mono font-bold text-emerald-900">
                                                                <span class="text-[10px] text-slate-400 font-bold mr-1">#{{ $idx + 1 }}</span>
                                                                {{ $pal->pallet_id }}
                                                            </td>
                                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-800">
                                                                {{ $pal->position ? $pal->position->position_code : 'Unassigned' }}
                                                            </td>
                                                            <td class="py-2.5 px-3 font-mono text-slate-600">{{ $pal->lot_no ?: '-' }}</td>
                                                            <td class="py-2.5 px-3 font-mono font-black text-emerald-700 text-right">{{ number_format($pal->current_qty, 2) }} KG</td>
                                                            <td class="py-2.5 px-3 font-mono text-slate-600">{{ $pal->created_at ? $pal->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : '-' }} WIB</td>
                                                            <td class="py-2.5 px-3 text-center">
                                                                <span class="px-2 py-0.5 text-[9px] font-black rounded-full {{ $pal->status === 'STORED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                                    {{ $pal->status }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- 2. Outgoing History -->
                                    <div class="space-y-2">
                                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                                            <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                                            <span>History Pengeluaran Material (Outgoings)</span>
                                        </h4>

                                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                                    <tr>
                                                        <th class="py-2.5 px-3">Kode Outgoing</th>
                                                        <th class="py-2.5 px-3">Pallet ID</th>
                                                        <th class="py-2.5 px-3 text-right">Qty Keluar (KG)</th>
                                                        <th class="py-2.5 px-3">Tujuan / Issued To</th>
                                                        <th class="py-2.5 px-3">Waktu Outgoing</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @forelse ($item['outgoings'] as $out)
                                                        <tr class="hover:bg-rose-50/30">
                                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900">{{ $out->outgoing_code }}</td>
                                                            <td class="py-2.5 px-3 font-mono text-emerald-800 font-bold">{{ $out->pallet_id }}</td>
                                                            <td class="py-2.5 px-3 font-mono font-black text-rose-600 text-right">-{{ number_format($out->qty_taken, 2) }} KG</td>
                                                            <td class="py-2.5 px-3 text-slate-700 font-medium">{{ $out->issued_to ?: '-' }}</td>
                                                            <td class="py-2.5 px-3 font-mono text-slate-600">{{ $out->created_at ? $out->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : ($out->outgoing_date ? $out->outgoing_date->format('d M Y') : '-') }} WIB</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="py-4 text-center text-slate-400 italic text-xs">Belum ada riwayat pengeluaran untuk item material ini.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 font-bold text-xs uppercase tracking-widest italic space-y-2">
                            <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p>Tidak ada stok material aktif di gudang saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Temporary Material Assignment Modal (Langsung di Halaman Mapping) -->
    @if ($showTemporaryModal)
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden animate-scale-up border border-amber-200">
                <div class="p-6 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/30 text-xl">
                            ⏳
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight">Tempatkan Material dari Area Temporary ke Slot Rak</h3>
                            <p class="text-xs text-amber-100">
                                Pilih slot rak kosong atau parsial untuk memindahkan pallet dari Temporary langsung di halaman Denah ini.
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeTemporaryModal" class="text-amber-200 hover:text-white transition p-1 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    @if (session()->has('error'))
                        <div class="p-3.5 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl flex items-center justify-between text-rose-800 text-xs font-bold">
                            <span>{{ session('error') }}</span>
                            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
                        </div>
                    @endif

                    @if (empty($temporaryPallets) || $temporaryPallets->isEmpty())
                        <div class="py-12 text-center text-gray-400 space-y-2">
                            <span class="text-3xl">🎉</span>
                            <p class="font-bold text-sm text-gray-600">Tidak ada material di Area Temporary!</p>
                            <p class="text-xs text-gray-400">Semua pallet di gudang ini sudah memiliki slot rak.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($temporaryPallets as $tPal)
                                <div class="p-4 bg-amber-50/40 rounded-2xl border border-amber-200 hover:border-amber-400 transition-all space-y-3" x-data="{ targetSlotId: '' }">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-black text-sm text-amber-900 bg-amber-100 px-2.5 py-1 rounded-lg border border-amber-300">
                                                {{ $tPal->pallet_id }}
                                            </span>
                                            <div>
                                                <div class="font-mono font-black text-xs text-gray-900">{{ $tPal->item_code }}</div>
                                                <div class="text-[11px] text-gray-500 truncate max-w-sm">{{ $tPal->material ? $tPal->material->item_description : '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 self-end sm:self-auto">
                                            <div class="text-right">
                                                <span class="text-[10px] text-gray-400 uppercase font-black tracking-wider block">Qty Pallet</span>
                                                <span class="font-mono font-black text-emerald-700 text-sm">{{ number_format($tPal->current_qty, 2) }} KG</span>
                                            </div>
                                            @if ($tPal->lot_no)
                                                <div class="text-right border-l border-amber-200 pl-3">
                                                    <span class="text-[10px] text-gray-400 uppercase font-black tracking-wider block">Lot No</span>
                                                    <span class="font-mono font-bold text-gray-700 text-xs">{{ $tPal->lot_no }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Quick Slot Selector Strip -->
                                    <div class="pt-2 border-t border-amber-200/60 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                                        <div class="flex-grow flex items-center space-x-2">
                                            <label class="text-[10px] font-black uppercase text-amber-900 tracking-wider whitespace-nowrap">Slot Tujuan:</label>
                                            <select x-model="targetSlotId" class="w-full px-3 py-1.5 bg-white border border-amber-300 rounded-xl text-xs font-mono font-bold text-gray-800 focus:ring-2 focus:ring-amber-500">
                                                <option value="">-- Pilih Slot Rak Tujuan --</option>
                                                @foreach ($availablePositionsForWarehouse as $posOption)
                                                    <option value="{{ $posOption->id }}">
                                                        {{ $posOption->position_code }} ({{ $posOption->slot_label ?: 'Slot' }}) &mdash; {{ $posOption->status }} (Max {{ number_format($posOption->max_capacity) }} KG)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex items-center space-x-2 self-end sm:self-auto">
                                            <button type="button" 
                                                    :disabled="!targetSlotId"
                                                    :class="targetSlotId ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                                    @click="if (targetSlotId) { $wire.assignPalletToPosition({{ $tPal->id }}, parseInt(targetSlotId)); }"
                                                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>✓ Masukkan ke Slot</span>
                                            </button>

                                            <button type="button" 
                                                    :disabled="!targetSlotId"
                                                    @click="if (targetSlotId) { $wire.closeTemporaryModal(); $wire.selectPosition(parseInt(targetSlotId)); }"
                                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer"
                                                    title="Lihat posisi slot ini di denah">
                                                <span>🗺️ Cek Denah</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-gray-500 font-medium">💡 Anda juga bisa mengklik langsung slot kosong di denah rak untuk memasukkan material temporary.</span>
                    <button wire:click="closeTemporaryModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold text-xs transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
