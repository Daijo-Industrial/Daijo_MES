<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
    <!-- Top Header & Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-400 mb-1">
                <span>Store Out & Delivery</span>
                <span>/</span>
                <span class="text-blue-600 font-bold">Tracking SPK & Label DO</span>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-500/20">
                    🔍
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Tracking SPK & Label DO</h1>
                    <p class="text-xs text-slate-500">Lacak nomor SPK dan barcode label box yang telah di-scan berada di dokumen DO / SO mana.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="exportCsv" type="button" 
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export CSV</span>
            </button>

            <a href="{{ route('so.index') }}" 
               class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Data SO Index</span>
            </a>

            <a href="{{ route('pegawai.scan') }}" 
               class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Scan SO</span>
            </a>
        </div>
    </div>

    <!-- Direct SPK & Label Manual Lookup Strip -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 rounded-3xl text-white shadow-xl border border-slate-700/60 space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-white/10 pb-3">
            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-400 font-black text-sm">
                    🎯
                </span>
                <div>
                    <h2 class="text-sm font-black tracking-wide uppercase text-white">Lacak DO Berdasarkan SPK & Label Box</h2>
                    <p class="text-xs text-slate-300">Masukkan nomor SPK dan/atau nomor Label box untuk langsung mengetahui box tersebut berada di DO mana.</p>
                </div>
            </div>
            @if ($spkSearch || $labelSearch || $search)
                <button wire:click="resetFilters" type="button" class="text-xs text-rose-300 hover:text-white font-bold flex items-center space-x-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Bersihkan Pencarian</span>
                </button>
            @endif
        </div>

        <!-- 2 Manual Inputs: SPK & Label -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <!-- Input SPK -->
            <div class="md:col-span-5 space-y-1">
                <label class="text-[11px] font-black uppercase tracking-wider text-blue-300 flex items-center space-x-1">
                    <span>1. Nomor SPK (Input Manual)</span>
                    <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <input type="text" 
                           wire:model.live.debounce.300ms="spkSearch" 
                           wire:keydown.enter="searchBySpkAndLabel"
                           placeholder="Contoh: 26023496 atau SPK-..." 
                           class="w-full px-4 py-3 bg-white/10 hover:bg-white/15 focus:bg-white text-white focus:text-slate-900 border border-white/20 focus:border-blue-400 rounded-2xl text-sm font-mono font-bold tracking-wider placeholder-slate-400 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none" />
                    @if ($spkSearch)
                        <button wire:click="$set('spkSearch', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white focus:text-slate-800 text-lg font-bold">&times;</button>
                    @endif
                </div>
            </div>

            <!-- Input Label Box -->
            <div class="md:col-span-5 space-y-1">
                <label class="text-[11px] font-black uppercase tracking-wider text-blue-300 flex items-center space-x-1">
                    <span>2. Nomor Label Box (Input Manual)</span>
                </label>
                <div class="relative">
                    <input type="text" 
                           wire:model.live.debounce.300ms="labelSearch" 
                           wire:keydown.enter="searchBySpkAndLabel"
                           placeholder="Contoh: 301 atau BX-001..." 
                           class="w-full px-4 py-3 bg-white/10 hover:bg-white/15 focus:bg-white text-white focus:text-slate-900 border border-white/20 focus:border-blue-400 rounded-2xl text-sm font-mono font-bold tracking-wider placeholder-slate-400 focus:ring-4 focus:ring-blue-500/20 transition-all outline-none" />
                    @if ($labelSearch)
                        <button wire:click="$set('labelSearch', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white focus:text-slate-800 text-lg font-bold">&times;</button>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex items-center gap-2">
                <button wire:click="searchBySpkAndLabel" type="button" wire:loading.attr="disabled"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-500 active:scale-95 disabled:opacity-70 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center justify-center space-x-1.5 cursor-pointer">
                    <span wire:loading.remove class="flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Lacak DO</span>
                    </span>
                    <span wire:loading class="flex items-center space-x-1.5">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Mencari...</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- Scanner Option Strip -->
        <div class="pt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs text-slate-300 border-t border-white/10">
            <div class="flex items-center space-x-1.5 text-slate-400">
                <span>💡 Punya barcode scanner fisik?</span>
                <span class="text-slate-300">Scan langsung ke kolom scanner di bawah:</span>
            </div>
            <div class="w-full sm:w-80">
                <input type="text" 
                       wire:model="barcodeScanInput" 
                       wire:keydown.enter="processBarcodeScan"
                       placeholder="⚡ Scan Barcode Gun stiker disini..." 
                       class="w-full px-3 py-1.5 bg-black/30 text-white border border-white/20 focus:border-blue-400 rounded-xl text-xs font-mono font-bold focus:bg-white focus:text-slate-900 outline-none transition-all placeholder-slate-500" />
            </div>
        </div>

        @if ($lastScannedFeedback)
            <div class="p-2.5 bg-emerald-950/60 border border-emerald-500/40 rounded-xl flex items-center justify-between text-xs text-emerald-300">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="font-bold font-mono">{{ $lastScannedFeedback }}</span>
                </div>
                <button wire:click="$set('lastScannedFeedback', null)" class="text-emerald-400 hover:text-white">&times;</button>
            </div>
        @endif
    </div>

    <!-- Instant DO Summary Cards (Grouped by Target DO) -->
    @if ($isSearching)
        @if ($matchingDos->isNotEmpty())
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-blue-50 border-2 border-emerald-500/80 p-5 sm:p-6 rounded-3xl shadow-md space-y-4 animate-in fade-in">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-sm shadow-xs">
                            📋
                        </span>
                        <div>
                            <h3 class="text-sm font-black uppercase text-emerald-950 tracking-wider">
                                DOKUMEN DO YANG MEMUAT SPK / BOX INI
                            </h3>
                            <p class="text-xs text-emerald-700">
                                Box dengan SPK <strong>{{ $spkSearch ?: ($search ?: 'Semua') }}</strong> 
                                @if ($labelSearch) &bull; Label <strong>{{ $labelSearch }}</strong> @endif 
                                terdaftar pada <strong>{{ $stats->unique_do_count }} Dokumen DO</strong>:
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-3 py-1 bg-emerald-600 text-white rounded-full text-xs font-black shadow-xs">
                            {{ $stats->unique_do_count }} Dokumen DO Terkait
                        </span>
                        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-black shadow-xs">
                            Total {{ number_format($stats->total_boxes) }} Box ({{ number_format($stats->total_qty) }} pcs)
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($matchingDos as $do)
                        <div class="bg-white p-5 rounded-2xl border border-emerald-200/90 shadow-sm space-y-3.5 flex flex-col justify-between hover:border-emerald-400 transition-all">
                            <div class="space-y-2.5">
                                <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Nomor Dokumen DO / SO:</span>
                                        <span class="text-lg font-black font-mono text-blue-600 hover:text-blue-800">
                                            {{ $do->doc_num }}
                                        </span>
                                    </div>
                                    <div>
                                        @if ($do->is_done)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                ✓ SELESAI (DONE)
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-200">
                                                ⏳ IN PROGRESS
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase">Customer</span>
                                        <span class="font-bold text-slate-800 line-clamp-1" title="{{ $do->customer }}">{{ $do->customer ?: 'N/A' }}</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <span class="text-[10px] text-slate-400 font-bold block uppercase">Box SPK Ini</span>
                                            <span class="text-sm font-black font-mono text-slate-800">{{ number_format($do->box_count) }} <span class="text-[10px] font-normal text-slate-400">box</span></span>
                                        </div>
                                        <div class="bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-100">
                                            <span class="text-[10px] text-emerald-600 font-bold block uppercase">Qty SPK Ini</span>
                                            <span class="text-sm font-black font-mono text-emerald-700">{{ number_format($do->total_qty) }} <span class="text-[10px] font-normal text-emerald-600">pcs</span></span>
                                        </div>
                                    </div>

                                    @if ($do->last_scanned_at)
                                        <div class="pt-1 text-[10px] text-slate-400 flex items-center justify-between">
                                            <span>Scan Terakhir:</span>
                                            <span class="font-bold text-slate-600">{{ \Carbon\Carbon::parse($do->last_scanned_at)->format('d/m/Y H:i') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                                <a href="{{ route('so.process', $do->doc_num) }}" target="_blank" 
                                   class="flex-grow py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1 shadow-xs">
                                    <span>Buka Dokumen DO ({{ $do->doc_num }})</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <button type="button" wire:click="openDocDetail('{{ $do->doc_num }}')" 
                                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                                    Detail DO
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($stats->unique_do_count > $matchingDos->count())
                    <div class="p-3 bg-white/80 rounded-2xl border border-emerald-200/60 text-xs text-emerald-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span>ℹ️ Menampilkan <strong>{{ $matchingDos->count() }}</strong> dari total <strong>{{ $stats->unique_do_count }}</strong> Dokumen DO terkait.</span>
                        <span class="text-slate-500 font-medium">Rincian stiker/label setiap box lengkap dapat dilihat pada tabel di bawah.</span>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-amber-50 border-2 border-amber-300 p-5 rounded-3xl flex items-center justify-between gap-4 animate-in fade-in">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="text-sm font-black text-amber-900 uppercase">BOX BELUM DITEMUKAN DI DOKUMEN DO MANA PUN</h4>
                        <p class="text-xs text-amber-800">
                            Tidak ditemukan riwayat scan DO untuk SPK: <strong>{{ $spkSearch ?: '-' }}</strong> 
                            @if ($labelSearch) &bull; Label: <strong>{{ $labelSearch }}</strong> @endif.
                            Pastikan nomor sudah sesuai atau cek apakah box tersebut sudah di-scan di menu Scan SO.
                        </p>
                    </div>
                </div>
                <button wire:click="resetFilters" class="px-3.5 py-1.5 bg-amber-200 hover:bg-amber-300 text-amber-900 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer">
                    Reset Pencarian
                </button>
            </div>
        @endif
    @endif

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Boxes -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-xs flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-2xl flex-shrink-0">
                📦
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Box Ter-scan</span>
                <span class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">
                    {{ number_format($stats->total_boxes ?? 0) }}
                </span>
                <span class="text-[11px] text-slate-400 font-bold ml-1">Box / CTN</span>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-xs flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-2xl flex-shrink-0">
                🔢
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Qty Ter-scan</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-700 tracking-tight">
                    {{ number_format($stats->total_qty ?? 0) }}
                </span>
                <span class="text-[11px] text-slate-400 font-bold ml-1">pcs</span>
            </div>
        </div>

        <!-- Unique SPK -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-xs flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-2xl flex-shrink-0">
                🏷️
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">SPK Terlacak</span>
                <span class="text-xl sm:text-2xl font-black text-purple-700 tracking-tight">
                    {{ number_format($stats->unique_spk_count ?? 0) }}
                </span>
                <span class="text-[11px] text-slate-400 font-bold ml-1">SPK Unik</span>
            </div>
        </div>

        <!-- Unique DO -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-xs flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-2xl flex-shrink-0">
                📄
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Dokumen DO Terkait</span>
                <span class="text-xl sm:text-2xl font-black text-amber-700 tracking-tight">
                    {{ number_format($stats->unique_do_count ?? 0) }}
                </span>
                <span class="text-[11px] text-slate-400 font-bold ml-1">Nomor DO</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <!-- Universal Search Input -->
            <div class="flex-grow max-w-xl relative">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cari SPK / Label Box / No DO / Part No / Customer..." 
                       class="w-full pl-10 pr-10 py-2.5 bg-slate-50 focus:bg-white border border-slate-200 focus:border-blue-500 rounded-2xl text-xs font-bold text-slate-800 placeholder-slate-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none" />
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if ($search)
                    <button wire:click="$set('search', '')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
                @endif
            </div>

            <!-- Fast Status Filter -->
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs font-bold">
                    <button wire:click="$set('statusFilter', 'ALL')" 
                            class="px-3 py-1.5 rounded-xl transition-all {{ $statusFilter === 'ALL' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Semua DO
                    </button>
                    <button wire:click="$set('statusFilter', 'DONE')" 
                            class="px-3 py-1.5 rounded-xl transition-all {{ $statusFilter === 'DONE' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        ✓ Selesai (Done)
                    </button>
                    <button wire:click="$set('statusFilter', 'PROGRESS')" 
                            class="px-3 py-1.5 rounded-xl transition-all {{ $statusFilter === 'PROGRESS' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        ⏳ In Progress
                    </button>
                </div>

                <!-- Toggle Advanced Filter Button -->
                <button wire:click="toggleAdvancedFilter" type="button" 
                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter Detail</span>
                    @if ($showAdvancedFilter)
                        <span class="text-xs">▲</span>
                    @else
                        <span class="text-xs">▼</span>
                    @endif
                </button>

                <!-- Reset Button -->
                <button wire:click="resetFilters" type="button" 
                        class="px-3 py-2 bg-slate-50 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer" 
                        title="Reset semua filter">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Collapsible Advanced Filters -->
        @if ($showAdvancedFilter)
            <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 animate-in fade-in">
                <!-- SPK Filter -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Nomor SPK</label>
                    <input type="text" wire:model.live.debounce.300ms="spkSearch" placeholder="Filter SPK..." 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- Label Box Filter -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Label Box</label>
                    <input type="text" wire:model.live.debounce.300ms="labelSearch" placeholder="Filter Label..." 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- DO / SO Number Filter -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Nomor DO / SO</label>
                    <input type="text" wire:model.live.debounce.300ms="docNumSearch" placeholder="Filter No DO..." 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- Part Number Filter -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Part No (Item Code)</label>
                    <input type="text" wire:model.live.debounce.300ms="itemCodeSearch" placeholder="Filter Part No..." 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- Tanggal Dari -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Scan Dari Tgl</label>
                    <input type="date" wire:model.live="dateFrom" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- Tanggal Sampai -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Scan Sampai Tgl</label>
                    <input type="date" wire:model.live="dateTo" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>
        @endif
    </div>

    <!-- Main Data Table Section -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Hasil Pelacakan Box Scan DO</h3>
                <span class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold text-xs">
                    {{ $records ? $records->total() : 0 }} Data
                </span>
            </div>

            <div class="flex items-center space-x-2 self-end sm:self-auto text-xs text-slate-500 font-semibold">
                <label>Tampilkan:</label>
                <select wire:model.live="perPage" class="px-2.5 py-1 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                    <option value="15">15 Baris</option>
                    <option value="25">25 Baris</option>
                    <option value="50">50 Baris</option>
                    <option value="100">100 Baris</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-600 border-b border-slate-200 uppercase font-black tracking-wider text-[10px]">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('doc_num')">
                            <div class="flex items-center space-x-1">
                                <span>Nomor DO / SO</span>
                                @if ($sortField === 'doc_num')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('customer')">
                            <div class="flex items-center space-x-1">
                                <span>Customer</span>
                                @if ($sortField === 'customer')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4">Status DO</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('spk_code')">
                            <div class="flex items-center space-x-1">
                                <span>Nomor SPK</span>
                                @if ($sortField === 'spk_code')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('label')">
                            <div class="flex items-center space-x-1">
                                <span>Label Box</span>
                                @if ($sortField === 'label')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('item_code')">
                            <div class="flex items-center space-x-1">
                                <span>Part No & Nama Item</span>
                                @if ($sortField === 'item_code')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 text-right cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('quantity')">
                            <div class="flex items-center justify-end space-x-1">
                                <span>Qty (pcs)</span>
                                @if ($sortField === 'quantity')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 text-center">Gudang</th>
                        <th class="py-3 px-4 cursor-pointer hover:bg-slate-200/60 transition" wire:click="sortBy('scanned_at')">
                            <div class="flex items-center space-x-1">
                                <span>Waktu Scan</span>
                                @if ($sortField === 'scanned_at')
                                    <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @if (!$hasActiveSearch)
                        <tr>
                            <td colspan="11" class="py-16 text-center text-slate-400">
                                <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-3xl mx-auto mb-3 shadow-xs">
                                    🎯
                                </div>
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-1">Menunggu Input Nomor SPK / Label Box</h4>
                                <p class="text-xs text-slate-500 max-w-md mx-auto">
                                    Silakan masukkan <strong>Nomor SPK</strong> dan/atau <strong>Nomor Label Box</strong> pada kolom di atas, lalu tekan <strong>Enter</strong> atau klik tombol <strong>Lacak DO</strong> untuk melacak dokumen DO.
                                </p>
                            </td>
                        </tr>
                    @else
                        @forelse ($records as $index => $row)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="py-3 px-4 text-slate-400 font-bold">
                                    {{ $records->firstItem() + $index }}
                                </td>

                                <!-- Nomor DO / SO -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <a href="{{ route('so.process', $row->doc_num) }}" 
                                           target="_blank"
                                           class="font-mono font-black text-blue-600 hover:text-blue-800 hover:underline flex items-center space-x-1" 
                                           title="Buka proses scan DO {{ $row->doc_num }}">
                                            <span>{{ $row->doc_num }}</span>
                                            <svg class="w-3 h-3 text-slate-400 hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded-lg text-[11px] font-bold border border-slate-200/60 whitespace-nowrap block max-w-xs truncate">
                                        {{ $row->customer ?: 'N/A' }}
                                    </span>
                                </td>

                                <!-- Status DO -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($row->is_done)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ✓ SELESAI
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                                            ⏳ PROGRESS
                                        </span>
                                    @endif
                                </td>

                                <!-- Nomor SPK -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($row->spk_code)
                                        <span class="font-mono font-black text-slate-900 bg-purple-50 text-purple-900 border border-purple-200 px-2.5 py-1 rounded-lg inline-block">
                                            {{ $row->spk_code }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>

                                <!-- Label Box -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-slate-800 bg-slate-100 border border-slate-300 px-2 py-1 rounded-md inline-block">
                                        {{ $row->label ?: '-' }}
                                    </span>
                                </td>

                                <!-- Part No & Item Description -->
                                <td class="py-3 px-4">
                                    <div class="font-mono font-black text-slate-900 text-xs">
                                        {{ $row->item_code }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 max-w-sm truncate" title="{{ $row->item_name }}">
                                        {{ $row->item_name ?: '-' }}
                                    </div>
                                </td>

                                <!-- Quantity -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <span class="font-mono font-black text-emerald-700 text-sm">
                                        {{ number_format($row->quantity) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-bold ml-0.5">pcs</span>
                                </td>

                                <!-- Warehouse -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-bold">
                                        {{ $row->warehouse ?: '-' }}
                                    </span>
                                </td>

                                <!-- Waktu Scan -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-[11px]">
                                        {{ $row->scanned_at ? \Carbon\Carbon::parse($row->scanned_at)->format('d/m/Y H:i:s') : '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $row->scanned_at ? \Carbon\Carbon::parse($row->scanned_at)->diffForHumans() : '' }}
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button type="button" 
                                            wire:click="openDocDetail('{{ $row->doc_num }}')" 
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center space-x-1 mx-auto cursor-pointer shadow-2xs"
                                            title="Lihat seluruh box dalam DO ini">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Detail DO</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-16 text-center text-slate-400">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-2xl">
                                        🔎
                                    </div>
                                    <p class="text-sm font-bold text-slate-600 mb-1">Tidak ada data scan ditemukan!</p>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                        Gunakan kolom pencarian di atas untuk memasukkan nomor SPK, label box, atau nomor DO lainnya.
                                    </p>
                                    @if ($search || $spkSearch || $labelSearch || $docNumSearch || $itemCodeSearch || $statusFilter !== 'ALL')
                                        <button wire:click="resetFilters" class="mt-4 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl text-xs font-bold transition">
                                            Reset Filter Pencarian
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if ($records)
            <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/50">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    <!-- DO Detail Interactive Modal -->
    @if ($showDocDetailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in" wire:keydown.escape="closeDocDetail">
            <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">
                            📋
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-300 text-[9px] font-black rounded uppercase tracking-wider border border-blue-400/30">Rincian Dokumen DO</span>
                                <h3 class="text-lg font-black tracking-tight font-mono">{{ $docDetailSummary['doc_num'] ?? '-' }}</h3>
                            </div>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Customer: <strong>{{ $docDetailSummary['customer'] ?? '-' }}</strong> &bull; Tgl: {{ $docDetailSummary['posting_date'] ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <a href="{{ route('so.process', $docDetailSummary['doc_num']) }}" 
                           target="_blank"
                           class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1">
                            <span>Buka Scan DO</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <button wire:click="closeDocDetail" class="text-slate-400 hover:text-white p-1 text-2xl font-bold cursor-pointer">
                            &times;
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 overflow-y-auto">
                    <!-- Progress Strip -->
                    @php
                        $targetTotal = $docDetailSummary['total_so_qty'] ?? 0;
                        $scannedTotal = $docDetailSummary['total_scanned_qty'] ?? 0;
                        $pct = $targetTotal > 0 ? min(100, round(($scannedTotal / $targetTotal) * 100)) : 0;
                    @endphp
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                            <span>Progress Scan DO:</span>
                            <span>{{ number_format($scannedTotal) }} / {{ number_format($targetTotal) }} pcs ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }} transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 font-semibold pt-1">
                            <span>Total Box Ter-scan: <strong>{{ number_format($docDetailSummary['total_scanned_boxes'] ?? 0) }} Box</strong></span>
                            <span>Jumlah SPK Unik: <strong>{{ number_format($docDetailSummary['unique_spk_count'] ?? 0) }} SPK</strong></span>
                        </div>
                    </div>

                    <!-- Items Table Breakdown -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden">
                        <div class="bg-slate-100 px-4 py-2.5 text-xs font-black uppercase text-slate-600 tracking-wider">
                            Daftar Part Number dalam DO Ini
                        </div>
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-[10px] text-slate-500 uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-4">Part No</th>
                                    <th class="py-2.5 px-4">Nama Item</th>
                                    <th class="py-2.5 px-4 text-right">Target SO</th>
                                    <th class="py-2.5 px-4 text-right">Ter-scan</th>
                                    <th class="py-2.5 px-4 text-center">Status Item</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($docDetailItems as $dItem)
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2.5 px-4 font-mono font-bold">{{ $dItem['item_code'] }}</td>
                                        <td class="py-2.5 px-4 text-slate-500">{{ $dItem['item_name'] }}</td>
                                        <td class="py-2.5 px-4 text-right font-mono font-bold">{{ number_format($dItem['target_qty']) }} pcs</td>
                                        <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-700">
                                            {{ number_format($dItem['scanned_qty']) }} pcs ({{ $dItem['scanned_boxes'] }} Box)
                                        </td>
                                        <td class="py-2.5 px-4 text-center">
                                            @if ($dItem['is_finished'])
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">
                                                    ✓ LENGKAP
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800">
                                                    ⏳ KURANG
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button wire:click="closeDocDetail" type="button" 
                            class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl font-bold text-xs transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
