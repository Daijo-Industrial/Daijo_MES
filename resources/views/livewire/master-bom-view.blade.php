<div class="p-6 bg-gray-50 min-h-screen space-y-6">
    <!-- Header & Statistics -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-md shadow-blue-200">
                    📜
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Master Bill of Materials (BOM)</h1>
                    <p class="text-xs text-gray-500 font-semibold mt-0.5">
                        Resep & Struktur Material Manufaktur Terintegrasi SAP Business One
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <button wire:click="downloadTemplate" class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border border-gray-200" title="Unduh Format Template CSV">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Format Template</span>
            </button>

            @if($totalRecords > 0)
                <button wire:click="$set('showDeleteAllModal', true)" class="px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-rose-200" title="Kosongkan Semua Data Staging BOM">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Reset Data Staging</span>
                </button>
            @endif

            @if($totalRecords > 0)
                <button wire:click="syncToProductionTables" wire:loading.attr="disabled" class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-blue-100 flex items-center gap-2 cursor-pointer" title="Ekstrak data valid ke tabel Header FG & Komponen Multilevel">
                    <span wire:loading.remove wire:target="syncToProductionTables">⚡ VALIDASI KE TABEL PRODUKSI</span>
                    <span wire:loading wire:target="syncToProductionTables" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Memvalidasi...
                    </span>
                </button>
            @endif

            <button wire:click="openUploadModal" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-emerald-100 flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>UPLOAD EXCEL SAP</span>
            </button>
        </div>
    </div>

    <!-- Alert Flash Notifications -->
    @if (session()->has('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-xs animate-in fade-in duration-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">✅</span>
                <p class="text-emerald-900 text-xs font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl shadow-xs animate-in fade-in duration-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">⚠️</span>
                <p class="text-rose-900 text-xs font-bold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- MAIN NAVIGATION TABS: PRODUCTION TABLES vs STAGING SAP -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-2 rounded-2xl border border-gray-100 shadow-2xs">
        <div class="flex items-center gap-2 p-1 bg-gray-100/80 rounded-xl">
            <!-- TAB 1: PRODUCTION TABLES -->
            <button type="button" wire:click="setMainTab('production')" 
                class="px-4 py-2.5 rounded-lg text-xs font-black transition-all cursor-pointer flex items-center gap-2.5 {{ $activeMainTab === 'production' ? 'bg-white text-blue-700 shadow-sm border border-gray-200/60' : 'text-gray-500 hover:text-gray-900' }}">
                <span class="text-sm">⚡</span>
                <div class="text-left">
                    <span class="block leading-tight">Master BOM Produksi (Sah)</span>
                    <span class="text-[10px] font-bold {{ $activeMainTab === 'production' ? 'text-blue-500' : 'text-gray-400' }}">
                        {{ number_format($totalValidFg) }} Finished Goods &bull; {{ number_format($totalValidComp) }} Komponen
                    </span>
                </div>
                @if($totalValidFg > 0)
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeMainTab === 'production' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-600' }}">
                        Siap Pakai
                    </span>
                @endif
            </button>

            <!-- TAB 2: STAGING RAW DATA -->
            <button type="button" wire:click="setMainTab('staging')" 
                class="px-4 py-2.5 rounded-lg text-xs font-black transition-all cursor-pointer flex items-center gap-2.5 {{ $activeMainTab === 'staging' ? 'bg-white text-blue-700 shadow-sm border border-gray-200/60' : 'text-gray-500 hover:text-gray-900' }}">
                <span class="text-sm">📥</span>
                <div class="text-left">
                    <span class="block leading-tight">Staging SAP (Mentah)</span>
                    <span class="text-[10px] font-bold {{ $activeMainTab === 'staging' ? 'text-blue-500' : 'text-gray-400' }}">
                        {{ number_format($totalRecords) }} Baris Extract Excel
                    </span>
                </div>
            </button>
        </div>

        <div class="px-3 text-right hidden sm:block">
            <span class="text-[11px] text-gray-400 font-semibold block">Arsitektur Dual-Tabel:</span>
            <span class="text-[11px] font-mono font-bold text-gray-600">master_bom_fg_headers &amp; master_bom_components</span>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 1: PRODUCTION MASTER BOM (TABEL FG & MULTILEVEL KOMPONEN) -->
    <!-- ============================================================== -->
    @if($activeMainTab === 'production')
        <!-- Production Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-blue-500 tracking-wider">Tabel 1: Master FG</span>
                <div class="text-2xl font-black text-blue-700 mt-1">{{ number_format($totalValidFg) }} <span class="text-xs text-gray-400 font-bold">Produk</span></div>
                <span class="text-[11px] text-blue-600/80 font-medium">Top-Level Finished Goods</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-indigo-500 tracking-wider">Tabel 2: Komponen Multilevel</span>
                <div class="text-2xl font-black text-indigo-700 mt-1">{{ number_format($totalValidComp) }} <span class="text-xs text-gray-400 font-bold">Baris</span></div>
                <span class="text-[11px] text-indigo-600/80 font-medium">Bahan Baku, Chemical &amp; WIP</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-purple-500 tracking-wider">Project SAP Terpetakan</span>
                <div class="text-2xl font-black text-purple-700 mt-1">{{ count($projectList) }} <span class="text-xs text-gray-400 font-bold">Project</span></div>
                <span class="text-[11px] text-purple-600/80 font-medium">SHAD TOP CASE, KULKAS, dll.</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-emerald-500 tracking-wider">Status Validasi</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">100% <span class="text-xs text-emerald-500 font-bold">Valid</span></div>
                <span class="text-[11px] text-emerald-700/80 font-medium">Hirarki bertingkat tersinkron</span>
            </div>
        </div>

        <!-- Filter & Search Strip for FG Table -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">
            <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="fgSearch" placeholder="Cari Kode FG, Deskripsi, Project (SHAD), Customer..." 
                        class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    @if(!empty($fgSearch))
                        <button wire:click="$set('fgSearch', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 font-black text-xs cursor-pointer">✕</button>
                    @endif
                </div>

                <!-- Dropdown Filters -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Project Code Filter -->
                    <select wire:model.live="fgProjectFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-3 py-2 text-gray-700 outline-none max-w-[200px]">
                        <option value="">📁 Semua Project ({{ number_format($totalValidFg) }})</option>
                        @foreach($projectList as $p)
                            <option value="{{ $p->project_code }}">{{ $p->project_code }} ({{ $p->count }} FG)</option>
                        @endforeach
                        <option value="NO_PROJECT">Tanpa Project Code</option>
                    </select>

                    <!-- Family Filter -->
                    <select wire:model.live="fgFamilyFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-3 py-2 text-teal-800 outline-none max-w-[200px]">
                        <option value="">🏷️ Semua Family</option>
                        @foreach($familyList as $f)
                            <option value="{{ $f->family }}">{{ $f->family }} ({{ $f->count }} FG)</option>
                        @endforeach
                        <option value="NO_FAMILY">Tanpa Family</option>
                    </select>

                    <!-- Packaging Filter -->
                    <select wire:model.live="fgPackagingFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-3 py-2 text-gray-700 outline-none">
                        <option value="all">📦 Semua Kemasan</option>
                        <option value="with_pkg">✓ Ada Packaging (Box/Bag)</option>
                        <option value="without_pkg">Tanpa Packaging</option>
                    </select>

                    <!-- Depth Filter -->
                    <select wire:model.live="fgDepthFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-3 py-2 text-gray-700 outline-none">
                        <option value="all">🌳 Semua Level Hirarki</option>
                        <option value="1">Level 1 (Single Level)</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4+">Level 4+ (Hirarki Dalam)</option>
                    </select>

                    <!-- Per Page -->
                    <select wire:model.live="fgPerPage" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-2.5 py-2 text-gray-600 outline-none">
                        <option value="15">15 / hal</option>
                        <option value="30">30 / hal</option>
                        <option value="50">50 / hal</option>
                        <option value="100">100 / hal</option>
                    </select>
                </div>
            </div>

            <!-- Reverse BOM Search (Where-Used) Quick Bar -->
            <div class="pt-2 border-t border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 flex-1 max-w-lg">
                    <span class="text-gray-400 font-bold shrink-0">🔍 Reverse BOM (Where-Used):</span>
                    <div class="relative flex-1">
                        <input type="text" wire:model.live.debounce.300ms="whereUsedSearch" placeholder="Cari bahan/part misal: 301-304127, ABS XR404, BOX PP..."
                            class="w-full pl-3 pr-7 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:bg-white focus:ring-1 focus:ring-purple-500 outline-none">
                        @if(!empty($whereUsedSearch))
                            <button wire:click="$set('whereUsedSearch', '')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 font-bold text-xs">✕</button>
                        @endif
                    </div>
                </div>
                <div class="text-[11px] text-gray-400 font-medium">
                    Menampilkan daftar Finished Goods resmi siap diproduksi
                </div>
            </div>

            <!-- Where-Used Quick Results Box -->
            @if(!empty($whereUsedSearch))
                <div class="p-3 bg-purple-50/70 border border-purple-200 rounded-xl space-y-2 animate-in fade-in duration-150">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-purple-900">
                            Hasil Where-Used untuk <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-purple-200 text-purple-800">"{{ $whereUsedSearch }}"</span>
                        </span>
                        <span class="text-[11px] font-bold text-purple-700">Ditemukan {{ count($whereUsedResults) }} pemakaian</span>
                    </div>

                    @if($whereUsedResults->isEmpty())
                        <div class="text-xs text-purple-700/80 py-1">Tidak ada Finished Goods yang memakai komponen ini.</div>
                    @else
                        <div class="max-h-48 overflow-y-auto space-y-1 text-xs">
                            @foreach($whereUsedResults as $res)
                                <div class="bg-white p-2.5 rounded-xl border border-purple-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:bg-purple-50/50 transition">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black shrink-0 {{ $res->is_wip ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $res->item_type }}
                                        </span>
                                        <span class="font-mono font-bold text-gray-900 shrink-0">{{ $res->component_item }}</span>
                                        <span class="text-gray-500 text-[11px] truncate max-w-xs">{{ $res->component_description }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto flex-wrap">
                                        <span class="text-[11px] text-gray-400 font-semibold">Dipakai di FG:</span>
                                        <button type="button" wire:click="showFgComponents({{ $res->fg_id }})" class="font-mono font-black text-blue-600 hover:text-blue-800 underline cursor-pointer text-xs" title="Lihat resep FG ini">
                                            {{ $res->fgHeader->fg_item_code ?? 'ID #'.$res->fg_id }}
                                        </button>
                                        @if(!empty($res->fgHeader->project_code))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                🏷️ {{ $res->fgHeader->project_code }}
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-0.5 rounded-lg bg-gray-100 font-mono font-black text-gray-800 text-xs border border-gray-200">
                                            {{ rtrim(rtrim(number_format($res->total_qty_per_fg, 4, '.', ''), '0'), '.') }} {{ $res->uom }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- TABLE 1: MASTER FG HEADERS -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <span>📦</span>
                        <span>Tabel 1: Master Finished Goods (FG Headers)</span>
                    </h3>
                    <p class="text-[11px] text-gray-500 font-medium">
                        Klik tombol <span class="font-bold text-blue-700">"📋 Multilevel Komponen"</span> untuk melihat data struktur resep di Tabel 2.
                    </p>
                </div>
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-black border border-blue-100">
                    Total: {{ number_format($fgHeaders->total()) }} Finished Goods
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/80 text-[10px] font-black uppercase text-gray-400 tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">#</th>
                            <th class="py-3 px-4">Project SAP</th>
                            <th class="py-3 px-4">Family</th>
                            <th class="py-3 px-4">Kode FG (Finished Good)</th>
                            <th class="py-3 px-4">Deskripsi Produk</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4 text-center">Struktur WIP</th>
                            <th class="py-3 px-4 text-center">Bahan Baku</th>
                            <th class="py-3 px-4 text-center">Kedalaman</th>
                            <th class="py-3 px-4 text-center">Kemasan</th>
                            <th class="py-3 px-3 text-center">Audit PE</th>
                            <th class="py-3 px-4 text-center">Aksi / Eksplorasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        @forelse($fgHeaders as $index => $fg)
                            <tr class="hover:bg-blue-50/40 transition">
                                <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                    {{ $fgHeaders->firstItem() + $index }}
                                </td>

                                <!-- Project SAP -->
                                <td class="py-3 px-4">
                                    @php
                                        $displayProject = !empty($fg->project_code) ? $fg->project_code : $fg->family;
                                    @endphp
                                    @if(!empty($displayProject))
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200 inline-flex items-center gap-1">
                                            <span>🏷️</span>
                                            <span>{{ $displayProject }}</span>
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- Family -->
                                <td class="py-3 px-4">
                                    @php
                                        $displayFamily = !empty($fg->family) ? $fg->family : $fg->project_code;
                                    @endphp
                                    @if(!empty($displayFamily))
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-teal-50 text-teal-800 border border-teal-200 inline-flex items-center gap-1 font-mono shadow-2xs">
                                            <span>🏷️</span>
                                            <span>{{ $displayFamily }}</span>
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- Kode FG -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-black text-gray-900 text-sm tracking-tight">
                                            {{ $fg->fg_item_code }}
                                        </span>
                                        <button type="button" @click="navigator.clipboard.writeText('{{ $fg->fg_item_code }}')" class="text-gray-300 hover:text-gray-600 transition" title="Salin kode">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Deskripsi FG -->
                                <td class="py-3 px-4 text-gray-700 max-w-xs font-semibold">
                                    {{ $fg->fg_description ?: '-' }}
                                </td>

                                <!-- Customer -->
                                <td class="py-3 px-4">
                                    @if(!empty($fg->customer_name))
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                            {{ $fg->customer_name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- WIP Sub-Assembly Count -->
                                <td class="py-3 px-4 text-center">
                                    @if($fg->total_wip_count > 0)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                            ⚙️ {{ $fg->total_wip_count }} WIP
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-[11px]">0 WIP</span>
                                    @endif
                                </td>

                                <!-- Raw Material Count -->
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        🧪 {{ $fg->total_raw_count }} Bahan
                                    </span>
                                </td>

                                <!-- Max Depth Level -->
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $fg->max_depth_level >= 3 ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600' }}">
                                        Level {{ $fg->max_depth_level }}
                                    </span>
                                </td>

                                <!-- Packaging Indicator -->
                                <td class="py-3 px-4 text-center">
                                    @if($fg->has_packaging)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200 inline-flex items-center gap-1">
                                            <span>📦</span>
                                            <span>Lengkap</span>
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-[10px]">-</span>
                                    @endif
                                </td>

                                <!-- Audit PE -->
                                <td class="py-3 px-3 text-center">
                                    @if($fg->is_verified)
                                        <div class="inline-flex items-center gap-1.5 justify-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-2xs cursor-help" 
                                                  title="Diverifikasi oleh {{ $fg->verified_by_name }} pada {{ $fg->verified_at ? \Carbon\Carbon::parse($fg->verified_at)->format('d/m/Y H:i') : '-' }}">
                                                <span>✓</span>
                                                <span>{{ $fg->verified_at ? \Carbon\Carbon::parse($fg->verified_at)->format('d/m H:i') : '-' }}</span>
                                            </span>
                                            <button type="button" 
                                                    wire:click="verifyBom('{{ $fg->fg_item_code }}', 'Verifikasi Ulang PE')" 
                                                    wire:confirm="Verifikasi ulang BOM {{ $fg->fg_item_code }} sekarang? Log verifikasi lama akan tetap tersimpan di riwayat."
                                                    class="p-1 rounded-lg bg-gray-100 hover:bg-amber-100 text-gray-500 hover:text-amber-800 transition cursor-pointer shadow-2xs text-[10px]"
                                                    title="Verifikasi Ulang PE (Log lama tetap tersimpan)">
                                                🔄
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" 
                                                wire:click="verifyBom('{{ $fg->fg_item_code }}')" 
                                                class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 hover:bg-emerald-600 text-amber-700 hover:text-white border border-amber-200 hover:border-transparent inline-flex items-center gap-1 cursor-pointer transition shadow-2xs"
                                                title="Klik untuk verifikasi audit PE">
                                            <span>⚠️</span>
                                            <span>Verifikasi</span>
                                        </button>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Open Table 2 Modal -->
                                        <button type="button" wire:click="showFgComponents({{ $fg->id }})" 
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer border border-blue-200 hover:border-transparent shadow-2xs" 
                                            title="Buka rincian seluruh komponen multilevel dari tabel master_bom_components">
                                            <span>📋</span>
                                            <span>Multilevel Komponen</span>
                                        </button>

                                        <!-- Open Visual Tree Modal -->
                                        <button type="button" wire:click="openTreeModal('{{ $fg->fg_item_code }}', '{{ addslashes($fg->fg_description ?? '') }}')" 
                                            class="px-2.5 py-1.5 bg-gray-50 hover:bg-emerald-600 text-gray-700 hover:text-white rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer border border-gray-200 hover:border-transparent shadow-2xs" 
                                            title="Buka pohon visual interaktif">
                                            <span>🌳</span>
                                            <span>Pohon Visual</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-12 text-center text-gray-400">
                                    <div class="text-3xl mb-2">🔍</div>
                                    <div class="text-sm font-bold">Tidak ada Finished Goods yang sesuai kriteria filter.</div>
                                    <div class="text-xs text-gray-400 mt-1">Coba sesuaikan pencarian atau reset filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-100">
                {{ $fgHeaders->links() }}
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- VIEW 2: STAGING RAW SAP BOM (UPLOAD & VERIFIKASI MENTAH) -->
    <!-- ============================================================== -->
    @if($activeMainTab === 'staging')
        <!-- Stats Cards Staging -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Formula BOM</span>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($totalParents) }} <span class="text-xs text-gray-400 font-bold">Parent</span></div>
                <span class="text-[11px] text-gray-500 font-medium">Part FG &amp; Sub-Assembly</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Relasi Komponen</span>
                <div class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalRecords) }} <span class="text-xs text-gray-400 font-bold">Baris</span></div>
                <span class="text-[11px] text-gray-500 font-medium">{{ number_format($totalComponents) }} Material unik</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-amber-500 tracking-wider">Item WIP / Semi-FG</span>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($totalWip) }} <span class="text-xs text-gray-400 font-bold">Part</span></div>
                <span class="text-[11px] text-amber-700/80 font-medium">Memiliki komponen turunan</span>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-2xs border border-gray-100">
                <span class="text-[10px] font-black uppercase text-emerald-500 tracking-wider">Basis Konsumsi</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">1 Qty <span class="text-xs text-gray-400 font-bold">Unit</span></div>
                <span class="text-[11px] text-emerald-700/80 font-medium">Per 1 PCS Parent Item</span>
            </div>
        </div>

        <!-- Search & Filters Strip Staging -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Kode Part, Nama Parent, atau Komponen..." 
                    class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                @if(!empty($search))
                    <button wire:click="$set('search', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 font-black text-xs cursor-pointer">✕</button>
                @endif
            </div>

            <!-- Filter Badges & View Mode Switcher -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- View Mode Switcher -->
                <div class="flex items-center p-1 bg-gray-100 rounded-xl mr-1 border border-gray-200">
                    <button type="button" wire:click="$set('viewMode', 'grouped')" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1 {{ $viewMode === 'grouped' ? 'bg-white text-blue-700 shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}" title="Kelompokkan per Part Parent (1 Baris per BOM)">
                        <span>📁 Grouped (Parent)</span>
                    </button>
                    <button type="button" wire:click="$set('viewMode', 'flat')" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1 {{ $viewMode === 'flat' ? 'bg-white text-blue-700 shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}" title="Tampilkan seluruh baris detail komponen">
                        <span>📄 Flat (Baris)</span>
                    </button>
                </div>

                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Tipe:</span>
                <button wire:click="$set('filterType', 'all')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $filterType === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' }}">
                    Semua
                </button>
                <button wire:click="$set('filterType', 'fg')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $filterType === 'fg' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' }}">
                    FG Saja
                </button>
                <button wire:click="$set('filterType', 'wip')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $filterType === 'wip' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 hover:bg-amber-100 text-amber-700' }}">
                    WIP / Sub-Assembly
                </button>
                @if($viewMode === 'flat')
                    <button wire:click="$set('filterType', 'raw')" class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $filterType === 'raw' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}">
                        Raw / Chemical
                    </button>
                @endif

                <div class="h-4 w-px bg-gray-200 mx-1"></div>

                <select wire:model.live="perPage" class="bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold px-2 py-1 text-gray-600 outline-none">
                    <option value="25">25 / hal</option>
                    <option value="50">50 / hal</option>
                    <option value="100">100 / hal</option>
                </select>
            </div>
        </div>

        <!-- STAGING TABLE (GROUPED OR FLAT) -->
        @if($viewMode === 'grouped' && $parents)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 text-[10px] font-black uppercase text-gray-400 tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4 text-center w-12">#</th>
                                <th class="py-3 px-4">Parent Item (Father)</th>
                                <th class="py-3 px-4">Deskripsi Parent</th>
                                <th class="py-3 px-4">Component Item (Child)</th>
                                <th class="py-3 px-4">Deskripsi Komponen</th>
                                <th class="py-3 px-4 text-right">Quantity (per 1 unit)</th>
                                <th class="py-3 px-4 text-center">UoM</th>
                                <th class="py-3 px-4 text-center">Aksi / Pohon</th>
                            </tr>
                        </thead>
                        @forelse($parents as $index => $parent)
                            @php
                                $comps = $componentsByParent->get($parent->parent_item, collect());
                                $firstComp = $comps->first();
                                $otherCount = $comps->count() - 1;
                                $isParentWip = isset($allComponentCodes[$parent->parent_item]);
                                $alpineComponentsJson = $comps->map(function($c) use ($allParentCodes) {
                                    return [
                                        'component_item' => $c->component_item,
                                        'component_description' => $c->component_description ?: '-',
                                        'quantity' => rtrim(rtrim(number_format($c->quantity, 6, '.', ''), '0'), '.'),
                                        'uom' => $c->uom,
                                        'is_wip' => isset($allParentCodes[$c->component_item]),
                                    ];
                                })->toJson();
                            @endphp
                            <tbody x-data="{ 
                                openDropdown: false, 
                                expandedRow: false,
                                selectedIndex: 0,
                                components: {{ $alpineComponentsJson }},
                                get active() {
                                    return this.components[this.selectedIndex] || {
                                        component_item: '{{ $firstComp ? $firstComp->component_item : '-' }}',
                                        component_description: '{{ $firstComp ? ($firstComp->component_description ?: '-') : '-' }}',
                                        quantity: '{{ $firstComp ? rtrim(rtrim(number_format($firstComp->quantity, 6, '.', ''), '0'), '.') : '-' }}',
                                        uom: '{{ $firstComp ? $firstComp->uom : '-' }}',
                                        is_wip: {{ ($firstComp && isset($allParentCodes[$firstComp->component_item])) ? 'true' : 'false' }}
                                    };
                                }
                            }" class="border-b border-gray-100 last:border-b-0">
                                <tr class="hover:bg-blue-50/40 transition">
                                    <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                        {{ $parents->firstItem() + $index }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-black text-gray-900 text-sm">
                                                {{ $parent->parent_item }}
                                            </span>
                                            @if($isParentWip)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                                    WIP
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                                    FG
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs font-semibold">
                                        {{ $parent->parent_description ?: '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono font-bold text-gray-800" x-text="active.component_item">
                                                {{ $firstComp ? $firstComp->component_item : '-' }}
                                            </span>
                                            <template x-if="active.is_wip">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-100 text-amber-800">
                                                    WIP
                                                </span>
                                            </template>
                                            @if($otherCount > 0)
                                                <div class="relative inline-block text-left" @click.outside="openDropdown = false">
                                                    <button type="button" @click="openDropdown = !openDropdown" 
                                                            class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md font-bold text-[10px] border border-blue-200 transition cursor-pointer flex items-center gap-1 shadow-2xs">
                                                        <span>▼ +{{ $otherCount }} Komponen Lainnya</span>
                                                    </button>
                                                    <div x-show="openDropdown" x-cloak 
                                                         x-transition:enter="transition ease-out duration-100"
                                                         x-transition:enter-start="transform opacity-0 scale-95"
                                                         x-transition:enter-end="transform opacity-100 scale-100"
                                                         class="absolute left-0 mt-1 w-80 bg-white rounded-xl shadow-xl border border-gray-200 z-30 p-2 space-y-1">
                                                        <div class="px-2 py-1 text-[10px] font-black uppercase text-gray-400 border-b border-gray-100 flex items-center justify-between">
                                                            <span>Daftar Komponen ({{ $comps->count() }})</span>
                                                            <button type="button" @click="expandedRow = !expandedRow; openDropdown = false;" class="text-blue-600 hover:text-blue-800 underline font-bold lowercase">
                                                                <span x-text="expandedRow ? 'Tutup Tabel' : 'Buka Semua'"></span>
                                                            </button>
                                                        </div>
                                                        <div class="max-h-48 overflow-y-auto space-y-1">
                                                            <template x-for="(comp, cIdx) in components" :key="cIdx">
                                                                <button type="button" @click="selectedIndex = cIdx; openDropdown = false" 
                                                                        :class="selectedIndex === cIdx ? 'bg-blue-50 text-blue-700 font-black' : 'hover:bg-gray-50 text-gray-700'"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs transition flex items-center justify-between gap-2">
                                                                    <div class="truncate">
                                                                        <span class="font-mono font-bold" x-text="comp.component_item"></span>
                                                                        <span class="text-[10px] text-gray-500 block truncate" x-text="comp.component_description"></span>
                                                                    </div>
                                                                    <div class="text-right shrink-0">
                                                                        <span class="font-mono font-bold" x-text="comp.quantity"></span>
                                                                        <span class="text-[10px] text-gray-400" x-text="comp.uom"></span>
                                                                    </div>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 max-w-xs" x-text="active.component_description">
                                        {{ $firstComp ? ($firstComp->component_description ?: '-') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-black text-gray-900" x-text="active.quantity">
                                        {{ $firstComp ? rtrim(rtrim(number_format($firstComp->quantity, 6, '.', ''), '0'), '.') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-gray-500 uppercase font-mono" x-text="active.uom">
                                        {{ $firstComp ? $firstComp->uom : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($otherCount > 0)
                                                <button type="button" @click="expandedRow = !expandedRow" 
                                                        :class="expandedRow ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 border-gray-200'"
                                                        class="px-2 py-1 rounded-lg text-[10px] font-bold border transition flex items-center gap-1 cursor-pointer" title="Buka tabel seluruh komponen">
                                                    <span x-text="expandedRow ? '▲ Lipat' : '📋 Expand'"></span>
                                                </button>
                                            @endif
                                            <button wire:click="openTreeModal('{{ $parent->parent_item }}', '{{ addslashes($parent->parent_description ?? '') }}')" 
                                                    class="px-2.5 py-1 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer border border-blue-200 hover:border-transparent shadow-2xs" 
                                                    title="Lihat Pohon BOM & Simulasi Kebutuhan">
                                                <span>🌳</span>
                                                <span>Pohon BOM</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                @if($otherCount > 0)
                                    <tr x-show="expandedRow" x-cloak class="bg-gray-50/70">
                                        <td colspan="8" class="p-3 pl-12 pr-6">
                                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-2xs">
                                                <div class="px-3 py-1.5 bg-gray-100/70 border-b border-gray-200 flex items-center justify-between text-[11px] font-black text-gray-600">
                                                    <span>Seluruh Komponen untuk: <span class="font-mono text-blue-700">{{ $parent->parent_item }}</span></span>
                                                    <span class="text-gray-400 font-normal">Total: {{ $comps->count() }} Komponen</span>
                                                </div>
                                                <table class="w-full text-left text-xs">
                                                    <thead class="bg-gray-50 text-[9px] font-black uppercase text-gray-400 border-b border-gray-100">
                                                        <tr>
                                                            <th class="py-1.5 px-3">Kode Komponen</th>
                                                            <th class="py-1.5 px-3">Deskripsi Komponen</th>
                                                            <th class="py-1.5 px-3 text-right">Quantity</th>
                                                            <th class="py-1.5 px-3 text-center">UoM</th>
                                                            <th class="py-1.5 px-3 text-center">Tipe</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach($comps as $c)
                                                            <tr class="hover:bg-gray-50 transition">
                                                                <td class="py-1.5 px-3 font-mono font-bold text-gray-800">{{ $c->component_item }}</td>
                                                                <td class="py-1.5 px-3 text-gray-600">{{ $c->component_description ?: '-' }}</td>
                                                                <td class="py-1.5 px-3 text-right font-mono font-black text-gray-900">
                                                                    {{ rtrim(rtrim(number_format($c->quantity, 6, '.', ''), '0'), '.') }}
                                                                </td>
                                                                <td class="py-1.5 px-3 text-center font-bold text-gray-500 uppercase font-mono">{{ $c->uom }}</td>
                                                                <td class="py-1.5 px-3 text-center">
                                                                    @if(isset($allParentCodes[$c->component_item]))
                                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800">WIP</span>
                                                                    @else
                                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">Raw Mat</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-gray-400">
                                        <div class="text-3xl mb-2">📁</div>
                                        <div class="text-sm font-bold">Tidak ada data BOM ditemukan.</div>
                                        <div class="text-xs text-gray-400 mt-1">Gunakan tombol "Upload Excel SAP" untuk mengimpor file BOM.</div>
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $parents->links() }}
                </div>
            </div>
        @elseif($viewMode === 'flat' && $boms)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 text-[10px] font-black uppercase text-gray-400 tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4 text-center w-12">#</th>
                                <th class="py-3 px-4">Parent Item (Father)</th>
                                <th class="py-3 px-4">Deskripsi Parent</th>
                                <th class="py-3 px-4">Component Item (Child)</th>
                                <th class="py-3 px-4">Deskripsi Komponen</th>
                                <th class="py-3 px-4 text-right">Quantity</th>
                                <th class="py-3 px-4 text-center">UoM</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            @forelse($boms as $index => $bom)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <td class="py-3 px-4 text-center text-gray-400 font-mono">
                                        {{ $boms->firstItem() + $index }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono font-black text-gray-900 text-sm">
                                            {{ $bom->parent_item }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs font-semibold">
                                        {{ $bom->parent_description ?: '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono font-bold text-gray-800">
                                            {{ $bom->component_item }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 max-w-xs">
                                        {{ $bom->component_description ?: '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-black text-gray-900">
                                        {{ rtrim(rtrim(number_format($bom->quantity, 6, '.', ''), '0'), '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-gray-500 uppercase font-mono">
                                        {{ $bom->uom }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button wire:click="openTreeModal('{{ $bom->parent_item }}', '{{ addslashes($bom->parent_description ?? '') }}')" 
                                                class="px-2.5 py-1 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer border border-blue-200 hover:border-transparent shadow-2xs">
                                            <span>🌳</span>
                                            <span>Pohon BOM</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-gray-400">
                                        <div class="text-3xl mb-2">📄</div>
                                        <div class="text-sm font-bold">Tidak ada data BOM ditemukan.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $boms->links() }}
                </div>
            </div>
        @endif
    @endif

    <!-- ============================================================== -->
    <!-- MODAL 1: TABEL 2 MULTILEVEL COMPONENTS DRILL-DOWN -->
    <!-- ============================================================== -->
    @if($showComponentModal && $selectedFg)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-200 overflow-y-auto"
             @keydown.escape.window="$wire.closeComponentModal()">
            <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl border border-gray-100 flex flex-col h-[90vh] max-h-[90vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-gray-50/60 shrink-0">
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-600 text-white shadow-2xs">
                                Finished Good
                            </span>
                            @if(!empty($selectedFg->project_code))
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    📁 Project: {{ $selectedFg->project_code }}
                                </span>
                            @endif
                            @if(!empty($selectedFg->family))
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-teal-50 text-teal-800 border border-teal-200">
                                    🏷️ Family: {{ $selectedFg->family }}
                                </span>
                            @endif
                            @if(!empty($selectedFg->customer_name))
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                    Customer: {{ $selectedFg->customer_name }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl font-black text-gray-900 mt-1 font-mono flex items-center gap-2">
                            <span>{{ $selectedFg->fg_item_code }}</span>
                            <span class="text-sm font-sans font-semibold text-gray-500">— {{ $selectedFg->fg_description }}</span>
                        </h2>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <button type="button" wire:click="downloadFgComponentsCsv({{ $selectedFg->id }})" 
                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Download CSV</span>
                        </button>
                        <button type="button" wire:click="closeComponentModal" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm transition cursor-pointer">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Simulation & Sub-Filter Toolbar -->
                <div class="px-4 sm:px-6 py-3 bg-white border-b border-gray-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 shrink-0">
                    <!-- Simulation Multiplier Input -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 whitespace-nowrap">Simulasi Produksi:</span>
                        <div class="relative w-32">
                            <input type="number" min="0.001" step="any" wire:model.live.debounce.300ms="compSimQty" 
                                class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-mono font-black text-blue-700 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <span class="text-xs font-bold text-gray-400 font-mono">{{ $selectedFg->uom ?: 'PCS' }} FG</span>
                    </div>

                    <!-- Filter by Type -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" wire:click="$set('compTypeFilter', 'all')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $compTypeFilter === 'all' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Semua
                        </button>
                        <button type="button" wire:click="$set('compTypeFilter', 'wip')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $compTypeFilter === 'wip' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                            ⚙️ WIP Sub-Assy ({{ $selectedFg->total_wip_count }})
                        </button>
                        <button type="button" wire:click="$set('compTypeFilter', 'raw')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $compTypeFilter === 'raw' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                            🧪 Bahan Baku &amp; Chemical
                        </button>
                        <button type="button" wire:click="$set('compTypeFilter', 'packaging')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $compTypeFilter === 'packaging' ? 'bg-teal-600 text-white shadow-2xs' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' }}">
                            📦 Kemasan / Packaging
                        </button>

                        <div class="h-4 w-px bg-gray-200 mx-1"></div>

                        <!-- Search within components -->
                        <div class="relative w-48">
                            <input type="text" wire:model.live.debounce.250ms="compSearch" placeholder="Cari komponen..." 
                                class="w-full pl-7 pr-3 py-1 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium outline-none focus:bg-white focus:ring-1 focus:ring-blue-500">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- TABLE 2: MULTILEVEL COMPONENTS (master_bom_components) -->
                <div class="p-4 sm:p-6 overflow-y-auto flex-1 min-h-0 overscroll-contain">
                    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-2xs">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 tracking-wider border-b border-gray-200 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-3 text-center w-16">Level</th>
                                    <th class="py-2.5 px-3">Parent Node</th>
                                    <th class="py-2.5 px-3">Kode Komponen</th>
                                    <th class="py-2.5 px-3">Deskripsi Komponen</th>
                                    <th class="py-2.5 px-3 text-center">Tipe Item</th>
                                    <th class="py-2.5 px-3 text-right">Unit Qty</th>
                                    <th class="py-2.5 px-3 text-right">Total Qty / FG</th>
                                    @if($compSimQty != 1.0)
                                        <th class="py-2.5 px-3 text-right text-blue-700 bg-blue-50/50">Total Simulasi (x{{ $compSimQty }})</th>
                                    @endif
                                    <th class="py-2.5 px-3 text-center">Satuan</th>
                                    <th class="py-2.5 px-3">Jalur Hirarki (Lineage)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium">
                                @forelse($modalComponents as $c)
                                    @php
                                        $indentClass = match($c->depth_level) {
                                            1 => 'pl-2',
                                            2 => 'pl-5',
                                            3 => 'pl-8',
                                            4 => 'pl-11',
                                            default => 'pl-14',
                                        };
                                        $calculatedSimQty = $c->total_qty_per_fg * max(0.0001, (float)$compSimQty);
                                    @endphp
                                    <tr class="hover:bg-blue-50/40 transition {{ $c->is_wip ? 'bg-amber-50/20' : '' }}">
                                        <!-- Depth Level -->
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono {{ $c->depth_level === 1 ? 'bg-blue-100 text-blue-800' : ($c->depth_level === 2 ? 'bg-indigo-100 text-indigo-800' : 'bg-purple-100 text-purple-800') }}">
                                                L{{ $c->depth_level }}
                                            </span>
                                        </td>

                                        <!-- Parent Node -->
                                        <td class="py-2.5 px-3 font-mono font-bold text-gray-500 text-[11px]">
                                            {{ $c->parent_item }}
                                        </td>

                                        <!-- Component Code -->
                                        <td class="py-2.5 px-3 {{ $indentClass }}">
                                            <div class="flex items-center gap-1.5">
                                                @if($c->depth_level > 1)
                                                    <span class="text-gray-300 font-mono text-xs">↳</span>
                                                @endif
                                                <span class="font-mono font-black text-gray-900 text-xs">
                                                    {{ $c->component_item }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Component Description -->
                                        <td class="py-2.5 px-3 text-gray-700 max-w-xs font-semibold">
                                            {{ $c->component_description ?: '-' }}
                                        </td>

                                        <!-- Item Type -->
                                        <td class="py-2.5 px-3 text-center">
                                            @if($c->item_type === 'WIP')
                                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                                    ⚙️ WIP SUB-ASSY
                                                </span>
                                            @else
                                                @php
                                                    $typeCategory = \App\Models\MasterBom::formatCategoryFromType($c->item_type);
                                                @endphp
                                                <div x-data="{ openTypeDropdown: false }" class="relative inline-block">
                                                    <button type="button" 
                                                            @click.stop="openTypeDropdown = !openTypeDropdown" 
                                                            class="px-2 py-0.5 rounded-md font-bold text-[9px] border inline-flex items-center gap-1 cursor-pointer hover:opacity-85 shadow-2xs {{ $typeCategory['badge'] }}"
                                                            title="Klik untuk ubah tipe material ini (Khusus PE / Engineering)">
                                                        <span>{{ $typeCategory['icon'] }}</span>
                                                        <span>{{ $typeCategory['label'] }}</span>
                                                        <span class="text-[8px] opacity-70 ml-0.5">▼</span>
                                                    </button>

                                                    <div x-show="openTypeDropdown" 
                                                         @click.outside="openTypeDropdown = false" 
                                                         x-transition
                                                         style="display: none;"
                                                         class="absolute left-0 top-full mt-1 z-50 bg-white rounded-xl shadow-2xl border border-gray-200 p-1.5 min-w-[200px] text-xs space-y-1 text-left">
                                                        <div class="px-2 py-1 text-[10px] font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 flex items-center justify-between">
                                                            <span>Ubah Tipe Material (PE)</span>
                                                            <span class="text-gray-300 text-[9px] font-mono">{{ $c->component_item }}</span>
                                                        </div>
                                                        
                                                        <button type="button" 
                                                                wire:click="updateMaterialType('{{ $c->component_item }}', 'RAW_MATERIAL')"
                                                                @click="openTypeDropdown = false"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 font-bold text-slate-800 text-[11px] cursor-pointer {{ $c->item_type === 'RAW_MATERIAL' ? 'bg-slate-100 font-black' : '' }}">
                                                            <span>🧩</span>
                                                            <span>Material / Part</span>
                                                        </button>

                                                        <button type="button" 
                                                                wire:click="updateMaterialType('{{ $c->component_item }}', 'RESIN')"
                                                                @click="openTypeDropdown = false"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-purple-50 flex items-center gap-2 font-bold text-purple-900 text-[11px] cursor-pointer {{ $c->item_type === 'RESIN' ? 'bg-purple-100 font-black' : '' }}">
                                                            <span>🧬</span>
                                                            <span>Biji Plastik / Resin</span>
                                                        </button>

                                                        <button type="button" 
                                                                wire:click="updateMaterialType('{{ $c->component_item }}', 'CHEMICAL')"
                                                                @click="openTypeDropdown = false"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-cyan-50 flex items-center gap-2 font-bold text-cyan-900 text-[11px] cursor-pointer {{ $c->item_type === 'CHEMICAL' ? 'bg-cyan-100 font-black' : '' }}">
                                                            <span>🧪</span>
                                                            <span>Cat / Chemical</span>
                                                        </button>

                                                        <button type="button" 
                                                                wire:click="updateMaterialType('{{ $c->component_item }}', 'HARDWARE')"
                                                                @click="openTypeDropdown = false"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-blue-50 flex items-center gap-2 font-bold text-blue-900 text-[11px] cursor-pointer {{ $c->item_type === 'HARDWARE' ? 'bg-blue-100 font-black' : '' }}">
                                                            <span>🔩</span>
                                                            <span>Hardware / Fastener</span>
                                                        </button>

                                                        <button type="button" 
                                                                wire:click="updateMaterialType('{{ $c->component_item }}', 'PACKAGING')"
                                                                @click="openTypeDropdown = false"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-emerald-50 flex items-center gap-2 font-bold text-emerald-900 text-[11px] cursor-pointer {{ $c->item_type === 'PACKAGING' ? 'bg-emerald-100 font-black' : '' }}">
                                                            <span>📦</span>
                                                            <span>Packaging / Kemasan</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Unit Qty -->
                                        <td class="py-2.5 px-3 text-right font-mono text-gray-500">
                                            {{ rtrim(rtrim(number_format($c->unit_qty, 6, '.', ''), '0'), '.') }}
                                        </td>

                                        <!-- Total Qty per 1 FG -->
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-gray-900">
                                            {{ rtrim(rtrim(number_format($c->total_qty_per_fg, 6, '.', ''), '0'), '.') }}
                                        </td>

                                        <!-- Simulated Qty -->
                                        @if($compSimQty != 1.0)
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-blue-700 bg-blue-50/50">
                                                {{ rtrim(rtrim(number_format($calculatedSimQty, 6, '.', ''), '0'), '.') }}
                                            </td>
                                        @endif

                                        <!-- UoM -->
                                        <td class="py-2.5 px-3 text-center font-bold text-gray-500 uppercase font-mono">
                                            {{ $c->uom }}
                                        </td>

                                        <!-- Lineage Path -->
                                        <td class="py-2.5 px-3 text-gray-400 font-mono text-[10px] truncate max-w-xs" title="{{ $c->lineage_path }}">
                                            {{ $c->lineage_path }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="py-10 text-center text-gray-400 font-bold">
                                            Tidak ada komponen yang cocok dengan kriteria filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Bottom Bar -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-gray-500 font-medium">
                        💡 Sumber: Tabel Produksi <span class="font-mono font-bold text-gray-700">master_bom_components</span> (Total: {{ $modalComponents->count() }} komponen aktif).
                    </span>
                    <button type="button" wire:click="closeComponentModal" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl text-xs transition cursor-pointer">
                        Tutup Jendela
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL 2: INTERACTIVE VISUAL TREE EXPLORER -->
    <!-- ============================================================== -->
    @if($showTreeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-200 overflow-y-auto"
             @keydown.escape.window="$wire.closeTreeModal()">
            <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl border border-gray-100 flex flex-col h-[90vh] max-h-[90vh] overflow-hidden">
                <!-- Modal Top Bar -->
                <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-gray-50/60 shrink-0">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-800">
                                Parent Item
                            </span>
                            <h2 class="text-xl font-black text-gray-900 font-mono">
                                {{ $selectedParentItem }}
                            </h2>
                            @if(!empty($selectedParentFamily))
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200 font-mono">
                                    🏷️ Family: {{ $selectedParentFamily }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 font-medium mt-1">
                            {{ $selectedParentDesc ?: 'Tidak ada deskripsi' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- PE Audit Verification Widget -->
                        @if($treeIsVerified)
                            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-300 rounded-2xl px-3.5 py-1.5 shadow-2xs">
                                <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs font-black shadow-xs">
                                    ✓
                                </div>
                                <div class="text-left">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-black text-emerald-900 tracking-tight">Audit PE Sah</span>
                                        <span class="text-[9px] bg-emerald-200 text-emerald-950 font-black px-1.5 py-0.2 rounded">VERIFIED</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-700 font-semibold">
                                        {{ $treeVerifiedBy ?: 'PE Engineering' }} &bull; {{ $treeVerifiedAt }}
                                    </div>
                                </div>
                                <button type="button" 
                                        wire:click="unverifyBom('{{ $selectedParentItem }}')" 
                                        wire:confirm="Batalkan status verifikasi audit PE untuk BOM ini? Riwayat log tetap tersimpan."
                                        class="ml-2 text-[10px] text-slate-400 hover:text-rose-600 font-bold underline cursor-pointer"
                                        title="Nonaktifkan status verifikasi aktif (riwayat log tetap aman)">
                                    Batal
                                </button>
                            </div>

                            <!-- Tombol Verifikasi Ulang -->
                            <button type="button" 
                                    wire:click="verifyBom('{{ $selectedParentItem }}', 'Verifikasi Ulang PE')" 
                                    wire:confirm="Verifikasi ulang BOM {{ $selectedParentItem }} sekarang? Log lama akan tetap tersimpan di riwayat."
                                    class="px-3.5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-amber-100 flex items-center gap-1.5 cursor-pointer"
                                    title="Verifikasi ulang BOM ini (Log lama tetap tersimpan)">
                                <span>🔄</span>
                                <span>Verifikasi Ulang</span>
                            </button>
                        @else
                            <button type="button" 
                                    wire:click="verifyBom('{{ $selectedParentItem }}')" 
                                    class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-emerald-100 flex items-center gap-1.5 cursor-pointer">
                                <span>✓</span>
                                <span>Verifikasi Audit (PE)</span>
                            </button>
                        @endif

                        <!-- Riwayat Log Verifikasi Dropdown -->
                        @if(!empty($treeVerificationLogs))
                            <div x-data="{ openLogs: false }" class="relative">
                                <button type="button" 
                                        @click.stop="openLogs = !openLogs" 
                                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                        title="Lihat riwayat seluruh log verifikasi BOM ini">
                                    <span>📜</span>
                                    <span>Riwayat ({{ count($treeVerificationLogs) }})</span>
                                    <span class="text-[9px]">▼</span>
                                </button>

                                <div x-show="openLogs" 
                                     @click.outside="openLogs = false" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     style="display: none;"
                                     class="absolute right-0 top-full mt-2 z-50 bg-white rounded-2xl shadow-2xl border border-gray-200 p-3 min-w-[300px] max-w-sm text-xs space-y-2">
                                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                        <span class="font-black text-gray-900 text-xs flex items-center gap-1.5">
                                            <span>📜</span>
                                            <span>Riwayat Audit Verifikasi</span>
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-bold font-mono">{{ count($treeVerificationLogs) }} Log</span>
                                    </div>

                                    <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
                                        @foreach($treeVerificationLogs as $idx => $log)
                                            <div class="p-2.5 rounded-xl transition {{ $loop->first ? 'bg-emerald-50/80 border border-emerald-200 shadow-2xs' : 'bg-gray-50 border border-gray-100' }}">
                                                <div class="flex items-center justify-between text-[10px]">
                                                    <span class="font-black {{ $loop->first ? 'text-emerald-900' : 'text-gray-700' }}">
                                                        {{ $loop->first ? '⭐ Terkini' : '#' . (count($treeVerificationLogs) - $idx) }} &bull; {{ $log['notes'] }}
                                                    </span>
                                                    <span class="font-mono text-gray-400 text-[9px]">{{ $log['verified_at'] }}</span>
                                                </div>
                                                <div class="text-[11px] font-semibold text-gray-600 mt-1">
                                                    Oleh: <span class="font-bold text-gray-800">{{ $log['verified_by_name'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <button type="button" wire:click="closeTreeModal" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm transition cursor-pointer">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Simulation Input Bar -->
                <div class="px-4 sm:px-6 py-3 bg-blue-50/40 border-b border-blue-100/60 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-600">Simulasi Qty Output:</span>
                        <div class="relative w-36">
                            <input type="number" min="0.001" step="any" wire:model.live.debounce.300ms="simulationQty" wire:change="updateSimulation"
                                class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-mono font-black text-blue-700 outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        </div>
                        <span class="text-xs font-bold text-gray-400">Unit</span>
                    </div>

                    <div class="flex items-center p-1 bg-gray-200/60 rounded-xl">
                        <button type="button" wire:click="$set('treeActiveTab', 'tree')" 
                                class="px-3 py-1 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $treeActiveTab === 'tree' ? 'bg-white text-blue-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900' }}">
                            <span>🌳 Pohon Visual</span>
                        </button>
                        <button type="button" wire:click="$set('treeActiveTab', 'summary')" 
                                class="px-3 py-1 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $treeActiveTab === 'summary' ? 'bg-white text-blue-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900' }}">
                            <span>📊 Ringkasan Kebutuhan Bersih</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Content Body -->
                <div class="p-4 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-4 overscroll-contain">
                    @if($treeActiveTab === 'tree')
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 font-semibold pb-1 border-b border-gray-100">
                                <span class="flex items-center gap-1.5">
                                    <span>💡</span>
                                    <span>Klik tombol panah chevron <span class="font-bold text-amber-700">▶</span> pada part WIP untuk melipat / membuka sub-komponen.</span>
                                </span>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" @click="$dispatch('expand-tree')" 
                                            class="px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 rounded-lg border border-gray-200 text-[10px] font-bold shadow-2xs transition cursor-pointer">
                                        ➕ Buka Semua
                                    </button>
                                    <button type="button" @click="$dispatch('collapse-tree')" 
                                            class="px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 rounded-lg border border-gray-200 text-[10px] font-bold shadow-2xs transition cursor-pointer">
                                        ➖ Lipat Semua
                                    </button>
                                </div>
                            </div>

                            @if(empty($explodedTree))
                                <div class="text-center py-12 text-gray-400 text-xs font-bold bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                    Tidak ada komponen yang terdaftar untuk parent item ini.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($explodedTree as $item)
                                        @include('livewire.partials.bom-tree-node', ['node' => $item])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif($treeActiveTab === 'summary')
                        <div class="space-y-6">
                            <!-- Raw Material & Chemicals Summary Table -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>🧪</span>
                                        <span>Bahan Baku &amp; Chemical yang Harus Ditarik dari Gudang (Store)</span>
                                    </h4>
                                    <span class="text-[11px] font-bold text-gray-400">Total: {{ count($treeSummaryData['materials'] ?? []) }} Material</span>
                                </div>

                                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-2xs">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 tracking-wider border-b border-gray-100">
                                            <tr>
                                                <th class="py-2.5 px-4">Tipe</th>
                                                <th class="py-2.5 px-4">Kode Material</th>
                                                <th class="py-2.5 px-4">Deskripsi Material</th>
                                                <th class="py-2.5 px-4 text-right">Total Kebutuhan Bersih</th>
                                                <th class="py-2.5 px-4 text-center">Satuan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 font-medium">
                                            @forelse($treeSummaryData['materials'] ?? [] as $mat)
                                                <tr class="hover:bg-gray-50/60 transition">
                                                    <td class="py-2.5 px-4">
                                                        <div x-data="{ openTypeDropdown: false }" class="relative inline-block">
                                                            <button type="button" 
                                                                    @click.stop="openTypeDropdown = !openTypeDropdown" 
                                                                    class="px-2 py-0.5 rounded-md font-bold text-[9px] border inline-flex items-center gap-1 cursor-pointer hover:opacity-85 shadow-2xs {{ $mat['category']['badge'] }}"
                                                                    title="Klik untuk ubah tipe material ini (Khusus PE / Engineering)">
                                                                <span>{{ $mat['category']['icon'] }}</span>
                                                                <span>{{ $mat['category']['label'] }}</span>
                                                                <span class="text-[8px] opacity-70 ml-0.5">▼</span>
                                                            </button>

                                                            <div x-show="openTypeDropdown" 
                                                                 @click.outside="openTypeDropdown = false" 
                                                                 x-transition
                                                                 style="display: none;"
                                                                 class="absolute left-0 top-full mt-1 z-50 bg-white rounded-xl shadow-2xl border border-gray-200 p-1.5 min-w-[200px] text-xs space-y-1">
                                                                <div class="px-2 py-1 text-[10px] font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 flex items-center justify-between">
                                                                    <span>Ubah Tipe Material (PE)</span>
                                                                    <span class="text-gray-300 text-[9px] font-mono">{{ $mat['item_code'] }}</span>
                                                                </div>
                                                                
                                                                <button type="button" 
                                                                        wire:click="updateMaterialType('{{ $mat['item_code'] }}', 'RAW_MATERIAL')"
                                                                        @click="openTypeDropdown = false"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 font-bold text-slate-800 text-[11px] cursor-pointer {{ ($mat['category']['type'] ?? '') === 'RAW_MATERIAL' ? 'bg-slate-100 font-black' : '' }}">
                                                                    <span>🧩</span>
                                                                    <span>Material / Part</span>
                                                                </button>

                                                                <button type="button" 
                                                                        wire:click="updateMaterialType('{{ $mat['item_code'] }}', 'RESIN')"
                                                                        @click="openTypeDropdown = false"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-purple-50 flex items-center gap-2 font-bold text-purple-900 text-[11px] cursor-pointer {{ ($mat['category']['type'] ?? '') === 'RESIN' ? 'bg-purple-100 font-black' : '' }}">
                                                                    <span>🧬</span>
                                                                    <span>Biji Plastik / Resin</span>
                                                                </button>

                                                                <button type="button" 
                                                                        wire:click="updateMaterialType('{{ $mat['item_code'] }}', 'CHEMICAL')"
                                                                        @click="openTypeDropdown = false"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-cyan-50 flex items-center gap-2 font-bold text-cyan-900 text-[11px] cursor-pointer {{ ($mat['category']['type'] ?? '') === 'CHEMICAL' ? 'bg-cyan-100 font-black' : '' }}">
                                                                    <span>🧪</span>
                                                                    <span>Cat / Chemical</span>
                                                                </button>

                                                                <button type="button" 
                                                                        wire:click="updateMaterialType('{{ $mat['item_code'] }}', 'HARDWARE')"
                                                                        @click="openTypeDropdown = false"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-blue-50 flex items-center gap-2 font-bold text-blue-900 text-[11px] cursor-pointer {{ ($mat['category']['type'] ?? '') === 'HARDWARE' ? 'bg-blue-100 font-black' : '' }}">
                                                                    <span>🔩</span>
                                                                    <span>Hardware / Fastener</span>
                                                                </button>

                                                                <button type="button" 
                                                                        wire:click="updateMaterialType('{{ $mat['item_code'] }}', 'PACKAGING')"
                                                                        @click="openTypeDropdown = false"
                                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-emerald-50 flex items-center gap-2 font-bold text-emerald-900 text-[11px] cursor-pointer {{ ($mat['category']['type'] ?? '') === 'PACKAGING' ? 'bg-emerald-100 font-black' : '' }}">
                                                                    <span>📦</span>
                                                                    <span>Packaging / Kemasan</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-4 font-mono font-black text-gray-900">
                                                        {{ $mat['item_code'] }}
                                                    </td>
                                                    <td class="py-2.5 px-4 text-gray-600">
                                                        {{ $mat['description'] ?: '-' }}
                                                    </td>
                                                    <td class="py-2.5 px-4 text-right font-mono font-black text-blue-700 text-sm">
                                                        {{ rtrim(rtrim(number_format($mat['total_qty'], 6, '.', ''), '0'), '.') }}
                                                    </td>
                                                    <td class="py-2.5 px-4 text-center font-bold text-gray-500 uppercase font-mono">
                                                        {{ $mat['uom'] }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-6 text-center text-gray-400 font-bold">
                                                        Tidak ada material murni di bawah pohon BOM ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- WIP Parts Summary Table -->
                            @if(!empty($treeSummaryData['wips']))
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-xs font-black text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                                            <span>⚙️</span>
                                            <span>Sub-Assembly / WIP yang Harus Dibuatkan SPK Terlebih Dahulu</span>
                                        </h4>
                                        <span class="text-[11px] font-bold text-amber-600">Total: {{ count($treeSummaryData['wips']) }} Part WIP</span>
                                    </div>

                                    <div class="bg-amber-50/30 rounded-2xl border border-amber-200 overflow-hidden shadow-2xs">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-amber-100/50 text-[10px] font-black uppercase text-amber-900 tracking-wider border-b border-amber-200">
                                                <tr>
                                                    <th class="py-2.5 px-4">Kode Part WIP</th>
                                                    <th class="py-2.5 px-4">Deskripsi Part</th>
                                                    <th class="py-2.5 px-4 text-right">Target Produksi SPK WIP</th>
                                                    <th class="py-2.5 px-4 text-center">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-amber-100 font-medium text-amber-950">
                                                @foreach($treeSummaryData['wips'] as $wip)
                                                    <tr class="hover:bg-amber-100/40 transition">
                                                        <td class="py-2.5 px-4 font-mono font-black text-amber-900">
                                                            {{ $wip['item_code'] }}
                                                        </td>
                                                        <td class="py-2.5 px-4 text-amber-800">
                                                            {{ $wip['description'] ?: '-' }}
                                                        </td>
                                                        <td class="py-2.5 px-4 text-right font-mono font-black text-amber-900 text-sm">
                                                            {{ rtrim(rtrim(number_format($wip['total_qty'], 6, '.', ''), '0'), '.') }}
                                                        </td>
                                                        <td class="py-2.5 px-4 text-center font-bold text-amber-700 uppercase font-mono">
                                                            {{ $wip['uom'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Modal Bottom Bar -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-gray-500 font-medium">
                        💡 Data disinkronkan langsung dari Master BOM SAP (Basis: 1 Qty Parent).
                    </span>
                    <button wire:click="closeTreeModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl text-xs transition cursor-pointer">
                        Tutup Jendela
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL 3: UPLOAD EXCEL SAP -->
    <!-- ============================================================== -->
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/60">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg font-black shadow-md shadow-emerald-200">
                            📥
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-800">Upload Master BOM dari SAP</h3>
                            <p class="text-xs text-gray-500 font-medium">Unggah file Excel (.xlsx, .xls) atau .csv hasil export SAP Business One</p>
                        </div>
                    </div>
                    <button wire:click="closeUploadModal" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm transition">
                        ✕
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    @if($uploadError)
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{{ $uploadError }}</span>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Pilih File Excel / CSV
                        </label>
                        <div class="border-2 border-dashed border-gray-200 hover:border-emerald-500 rounded-2xl p-6 text-center transition cursor-pointer bg-gray-50/50 hover:bg-emerald-50/20 relative">
                            <input type="file" wire:model="bomFile" accept=".xlsx,.xls,.csv,.txt" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                            <div class="space-y-2 pointer-events-none">
                                <div class="text-3xl">📊</div>
                                <div class="text-xs font-bold text-gray-700">
                                    @if($bomFile)
                                        <span class="text-emerald-700 font-black">{{ $bomFile->getClientOriginalName() }}</span> ({{ number_format($bomFile->getSize() / 1024, 1) }} KB)
                                    @else
                                        Klik atau seret file Excel/CSV ke sini
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-400">Mendukung format .xlsx, .xls, .csv maksimal 50MB</div>
                            </div>
                        </div>
                        @error('bomFile') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Metode Penyimpanan Data
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/30">
                                <input type="radio" wire:model="uploadMode" value="update" class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="text-xs font-bold text-gray-800">Perbarui / Tambah</div>
                                    <div class="text-[10px] text-gray-400">Tambahkan ke data yang sudah ada</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-rose-600 has-[:checked]:bg-rose-50/30">
                                <input type="radio" wire:model="uploadMode" value="replace" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <div class="text-xs font-bold text-gray-800">Ganti Semua</div>
                                    <div class="text-[10px] text-rose-500 font-semibold">Kosongkan lalu isi baru</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div wire:loading wire:target="processUpload" class="w-full">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 flex items-center gap-3">
                            <svg class="animate-spin h-5 w-5 text-blue-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <div class="text-xs text-blue-900 font-bold">
                                Sedang memproses dan mengimpor baris data... Harap tunggu sebentar.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button wire:click="closeUploadModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl text-xs transition cursor-pointer">
                        Batal
                    </button>
                    <button wire:click="processUpload" wire:loading.attr="disabled" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs transition shadow-md shadow-emerald-200 flex items-center gap-2 cursor-pointer">
                        <span>🚀 Mulai Impor Data</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL 4: KONFIRMASI HAPUS SEMUA DATA STAGING -->
    <!-- ============================================================== -->
    @if($showDeleteAllModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-gray-100 p-6 space-y-4">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                    ⚠️
                </div>
                <div class="text-center space-y-1">
                    <h3 class="text-base font-black text-gray-900 uppercase">Hapus Semua Data Staging BOM?</h3>
                    <p class="text-xs text-gray-500">
                        Tindakan ini akan mengosongkan seluruh {{ number_format($totalRecords) }} baris data formula BOM mentah dari database staging. Data tabel validasi produksi tidak akan terhapus.
                    </p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button wire:click="$set('showDeleteAllModal', false)" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl text-xs transition cursor-pointer">Batal</button>
                    <button wire:click="deleteAllBoms" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition shadow-md cursor-pointer">Ya, Hapus Semua</button>
                </div>
            </div>
        </div>
    @endif
</div>
