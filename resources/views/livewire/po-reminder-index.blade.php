<div class="p-6 bg-gray-50 min-h-screen space-y-6" style="font-family: 'IBM Plex Sans', sans-serif;">
    {{-- 1. Header Banner & Action --}}
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 bg-amber-500 rounded-full inline-block animate-pulse"></span>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">PO Reminder (Kebutuhan Material Pengiriman)</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Kalkulasi kebutuhan bahan baku & kemasan dari jadwal delivery schedule (delsched) dan Master BOM, untuk panduan pembuatan Purchase Order (PO).
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <button wire:click="exportExcel"
                    wire:loading.attr="disabled"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-xs transition flex items-center space-x-2 cursor-pointer disabled:opacity-50">
                <span wire:loading.remove wire:target="exportExcel">📥</span>
                <span wire:loading wire:target="exportExcel" class="inline-block animate-spin">⏳</span>
                <span>Export Excel (.xlsx)</span>
            </button>
        </div>
    </div>

    {{-- 2. Filter & Preset Bar --}}
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            {{-- Rentang Tanggal Delivery --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-xl border border-gray-200">
                    <span class="text-xs font-bold text-gray-500">📅 Delivery:</span>
                    <input type="date"
                           wire:model.live.debounce.300ms="startDate"
                           class="text-xs font-mono font-bold bg-white px-2.5 py-1 rounded-lg border border-gray-200 focus:border-sky-500 focus:outline-none">
                    <span class="text-xs font-bold text-gray-400">s/d</span>
                    <input type="date"
                           wire:model.live.debounce.300ms="endDate"
                           class="text-xs font-mono font-bold bg-white px-2.5 py-1 rounded-lg border border-gray-200 focus:border-sky-500 focus:outline-none">
                </div>

                {{-- Preset Buttons --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" wire:click="setPreset('this_month')"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 transition cursor-pointer">
                        Bulan Ini
                    </button>
                    <button type="button" wire:click="setPreset('next_month')"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 transition cursor-pointer">
                        Bulan Depan
                    </button>
                    <button type="button" wire:click="setPreset('7_days')"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 transition cursor-pointer">
                        7 Hari
                    </button>
                    <button type="button" wire:click="setPreset('14_days')"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 transition cursor-pointer">
                        14 Hari
                    </button>
                    <button type="button" wire:click="setPreset('30_days')"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 transition cursor-pointer">
                        30 Hari
                    </button>
                </div>
            </div>

            {{-- Pencarian --}}
            <div class="w-full lg:w-72">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs">🔍</span>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Cari part / material / customer..."
                           class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-sky-500 transition">
                </div>
            </div>
        </div>

        {{-- Filter Kategori Material Pills --}}
        <div class="flex items-center gap-2 flex-wrap pt-2 border-t border-gray-100 text-xs">
            <span class="text-xs font-bold text-gray-500 mr-1">Kategori:</span>
            @php
                $categories = [
                    'ALL' => 'Semua Kategori',
                    'RESIN' => '🧬 Biji Plastik / Resin',
                    'PACKAGING' => '📦 Packaging / Kemasan',
                    'CHEMICAL' => '🧪 Cat & Chemical',
                    'HARDWARE' => '🔩 Hardware & Baut',
                    'RAW_MATERIAL' => '🧩 Material / Part',
                    'WIP' => '⚙️ WIP / Sub-Assy',
                ];
            @endphp
            @foreach($categories as $key => $label)
                <button type="button"
                        wire:click="$set('categoryFilter', '{{ $key }}')"
                        class="px-3 py-1 rounded-xl text-xs font-bold transition cursor-pointer {{ $categoryFilter === $key ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- 3. THE 2 VIEWS SWITCHER (CORE FEATURE) --}}
    <div class="bg-gradient-to-r from-slate-900 via-gray-900 to-slate-800 p-3 rounded-2xl shadow-md border border-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {{-- VIEW 2: INCLUDE STOK AKTUAL FG (RECOMMENDED FOR PO) --}}
            <button type="button"
                    wire:click="$set('activeView', 'net')"
                    class="p-4 rounded-xl text-left transition relative cursor-pointer {{ $activeView === 'net' ? 'bg-white text-gray-900 shadow-lg ring-2 ring-amber-400' : 'bg-gray-800/60 text-gray-300 hover:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider {{ $activeView === 'net' ? 'text-amber-600' : 'text-amber-400' }} flex items-center gap-1.5">
                        <span>⚡</span>
                        <span>VIEW 2: INCLUDE STOK AKTUAL FG (NET DEFICIT)</span>
                    </span>
                    @if($activeView === 'net')
                        <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                            ✓ Aktif Digunakan
                        </span>
                    @endif
                </div>
                <div class="text-sm font-black mt-1">
                    Kekurangan FG = Total Delivery - Stok Aktual FG
                </div>
                <p class="text-[11px] mt-1 {{ $activeView === 'net' ? 'text-gray-500' : 'text-gray-400' }}">
                    Direkomendasikan untuk PO Purchasing. Hanya menghitung kebutuhan bahan untuk Finish Good yang stoknya belum mencukupi jadwal pengiriman.
                </p>
            </button>

            {{-- VIEW 1: ABAIKAN STOK AKTUAL FG (GROSS REQUIREMENT) --}}
            <button type="button"
                    wire:click="$set('activeView', 'gross')"
                    class="p-4 rounded-xl text-left transition relative cursor-pointer {{ $activeView === 'gross' ? 'bg-white text-gray-900 shadow-lg ring-2 ring-sky-400' : 'bg-gray-800/60 text-gray-300 hover:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider {{ $activeView === 'gross' ? 'text-sky-600' : 'text-sky-400' }} flex items-center gap-1.5">
                        <span>📦</span>
                        <span>VIEW 1: ABAIKAN STOK AKTUAL FG (GROSS REQUIREMENT)</span>
                    </span>
                    @if($activeView === 'gross')
                        <span class="text-[10px] font-bold bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full">
                            ✓ Aktif Digunakan
                        </span>
                    @endif
                </div>
                <div class="text-sm font-black mt-1">
                    Target FG = Seluruh Total Delivery Qty (Kotor)
                </div>
                <p class="text-[11px] mt-1 {{ $activeView === 'gross' ? 'text-gray-500' : 'text-gray-400' }}">
                    Menghitung total kebutuhan material untuk seluruh quantity delivery tanpa memperhitungkan stok gudang FG yang ada saat ini.
                </p>
            </button>
        </div>
    </div>

    {{-- 4. KPI SUMMARY CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total FG Items --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider">
                <span>Part Terjadwal</span>
                <span>📋</span>
            </div>
            <div class="mt-2 text-2xl font-mono font-black text-gray-800">
                {{ number_format($kpi['total_fg_items'] ?? 0) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Kode Finish Good unik
            </div>
        </div>

        {{-- Total Qty Delivery --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider">
                <span>Total Delivery Qty</span>
                <span>🚚</span>
            </div>
            <div class="mt-2 text-2xl font-mono font-black text-sky-600">
                {{ number_format($kpi['total_delivery_qty'] ?? 0) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Pcs terjadwal di delsched
            </div>
        </div>

        {{-- Total Stock FG --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider">
                <span>Stok Aktual FG</span>
                <span>🏭</span>
            </div>
            <div class="mt-2 text-2xl font-mono font-black text-indigo-600">
                {{ number_format($kpi['total_stock_fg'] ?? 0) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Stok gudang FG saat ini
            </div>
        </div>

        {{-- Total Kekurangan FG (Net Deficit) --}}
        <div class="bg-white p-4 rounded-2xl border {{ ($kpi['total_net_deficit'] ?? 0) > 0 ? 'border-amber-200 bg-amber-50/20' : 'border-gray-100' }} shadow-xs">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider">
                <span>Kekurangan FG (Net)</span>
                <span>⚠️</span>
            </div>
            <div class="mt-2 text-2xl font-mono font-black {{ ($kpi['total_net_deficit'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ number_format($kpi['total_net_deficit'] ?? 0) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                {{ ($kpi['total_net_deficit'] ?? 0) > 0 ? 'Pcs FG yang harus diproduksi' : 'Stok FG mencukupi semua' }}
            </div>
        </div>

        {{-- Total Jenis Material Dibutuhkan --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider">
                <span>Material PO</span>
                <span>🧬</span>
            </div>
            <div class="mt-2 text-2xl font-mono font-black text-purple-600">
                {{ number_format($activeView === 'net' ? ($kpi['total_materials_net'] ?? 0) : ($kpi['total_materials_gross'] ?? 0)) }}
            </div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                Jenis material dibutuhkan
            </div>
        </div>
    </div>

    {{-- Alert jika ada item yang belum memiliki BOM --}}
    @if(($kpi['missing_bom_count'] ?? 0) > 0)
        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between text-xs text-amber-900">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>
                    Terdapat <strong>{{ $kpi['missing_bom_count'] }} Finish Good</strong> yang belum memiliki Master BOM di sistem. Kebutuhan material untuk item tersebut belum dapat dihitung otomatis.
                </span>
            </div>
            <a href="{{ route('master-bom.index') }}" class="font-bold text-amber-800 underline hover:text-amber-950">
                Buka Master BOM →
            </a>
        </div>
    @endif

    {{-- 5. Sub-Navigation: Rekap Material vs Rincian per FG --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 pt-4">
            <div class="flex space-x-6">
                <button type="button"
                        wire:click="$set('displayMode', 'materials')"
                        class="pb-3 text-xs font-black transition relative cursor-pointer flex items-center space-x-2 {{ $displayMode === 'materials' ? 'text-gray-900 border-b-2 border-amber-500' : 'text-gray-400 hover:text-gray-600' }}">
                    <span>🧬</span>
                    <span>Rekap Kebutuhan Material (PO Reminder)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $displayMode === 'materials' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500' }}">
                        {{ $totalMaterialsCount }}
                    </span>
                </button>

                <button type="button"
                        wire:click="$set('displayMode', 'fgs')"
                        class="pb-3 text-xs font-black transition relative cursor-pointer flex items-center space-x-2 {{ $displayMode === 'fgs' ? 'text-gray-900 border-b-2 border-amber-500' : 'text-gray-400 hover:text-gray-600' }}">
                    <span>🏭</span>
                    <span>Rincian per Finish Good (Jadwal Delivery)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $displayMode === 'fgs' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500' }}">
                        {{ $totalFgsCount }}
                    </span>
                </button>
            </div>

            <div class="pb-3 flex items-center gap-2 text-xs">
                <span class="text-gray-400 font-bold">Baris:</span>
                <select wire:model.live="perPage" class="text-xs font-bold border border-gray-200 rounded-lg px-2 py-1 bg-white">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        {{-- TABEL 1: REKAP MATERIAL (PO REMINDER) --}}
        @if($displayMode === 'materials')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Kode Material</th>
                            <th class="py-3.5 px-4">Deskripsi Material</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4 text-right">Total Kebutuhan</th>
                            <th class="py-3.5 px-4 text-center">UoM</th>
                            <th class="py-3.5 px-4 text-center">Jumlah FG</th>
                            <th class="py-3.5 px-4">Daftar Part / FG yang Membutuhkan</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paginatedMaterials as $idx => $mat)
                            <tr class="hover:bg-amber-50/20 transition">
                                <td class="py-3.5 px-4 text-center font-mono text-gray-400">
                                    {{ $paginatedMaterials->firstItem() + $idx }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    {{ $mat['item_code'] }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-700">
                                    <div class="font-bold">{{ $mat['description'] }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $mat['category']['badge'] ?? 'bg-gray-100 text-gray-800' }}">
                                        <span>{{ $mat['category']['icon'] ?? '🧩' }}</span>
                                        <span>{{ $mat['category']['label'] ?? 'Material' }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="text-sm font-mono font-black text-gray-900">
                                        {{ number_format($mat['total_needed_qty'], 2) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-gray-500">
                                    {{ $mat['uom'] }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-gray-100 text-gray-700">
                                        {{ $mat['fgs_count'] ?? 0 }} FG
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        @foreach(array_slice($mat['used_by_fgs'] ?? [], 0, 3) as $f)
                                            <span class="inline-block px-2 py-0.5 rounded-lg text-[10px] font-mono bg-sky-50 text-sky-800 border border-sky-200">
                                                {{ $f['fg_code'] }}: <strong>{{ number_format($f['material_qty'], 1) }}</strong>
                                            </span>
                                        @endforeach
                                        @if(count($mat['used_by_fgs'] ?? []) > 3)
                                            <span class="text-[10px] font-bold text-gray-400 self-center">
                                                +{{ count($mat['used_by_fgs']) - 3 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button"
                                            wire:click="showMaterialDetail('{{ $mat['item_code'] }}')"
                                            class="px-2.5 py-1 text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-lg transition cursor-pointer">
                                        Rincian 🔍
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-gray-400">
                                    <span class="text-2xl block mb-2">📭</span>
                                    <span>Tidak ada kebutuhan material yang ditemukan pada rentang tanggal ini.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginatedMaterials->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $paginatedMaterials->links() }}
                </div>
            @endif
        @endif

        {{-- TABEL 2: RINCIAN PER FINISH GOOD --}}
        @if($displayMode === 'fgs')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Kode Part / FG</th>
                            <th class="py-3.5 px-4">Nama Part & Customer</th>
                            <th class="py-3.5 px-4 text-right">Total Delivery Qty</th>
                            <th class="py-3.5 px-4 text-right">Stok Aktual FG</th>
                            <th class="py-3.5 px-4 text-right">Kekurangan FG (Net)</th>
                            <th class="py-3.5 px-4 text-center">Status Stok</th>
                            <th class="py-3.5 px-4 text-center">Status BOM</th>
                            <th class="py-3.5 px-4 text-center">Status SPK & Qty Avail</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paginatedFgs as $idx => $fg)
                            <tr class="hover:bg-amber-50/20 transition">
                                <td class="py-3.5 px-4 text-center font-mono text-gray-400">
                                    {{ $paginatedFgs->firstItem() + $idx }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $fg['item_code'] }}</span>
                                        <a href="{{ route('spk.changes.index', ['search' => $fg['item_code']]) }}" 
                                           target="_blank" 
                                           class="text-[10px] text-blue-500 hover:text-blue-700 font-bold hover:underline"
                                           title="Cari di SPK Changes">
                                            ↗
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-gray-700">
                                    <div class="font-bold text-gray-900">{{ $fg['item_name'] }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $fg['customer_name'] }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-sky-700 text-sm">
                                    {{ number_format($fg['delivery_qty']) }}
                                    <span class="text-[10px] text-gray-400 block font-normal">({{ $fg['delivery_count'] }}x kirim)</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-indigo-700 text-sm">
                                    {{ number_format($fg['stock_actual']) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-sm {{ $fg['net_deficit'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                    {{ number_format($fg['net_deficit']) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($fg['net_deficit'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                            <span>⚠️</span>
                                            <span>Kurang {{ number_format($fg['net_deficit']) }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            <span>✓</span>
                                            <span>Stok Cukup</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($fg['has_bom'])
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Ada BOM
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            ✕ Belum Ada
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php
                                        $targetFgRow = $activeView === 'net' ? $fg['net_deficit'] : $fg['delivery_qty'];
                                        $spkAvailRow = $fg['spk_info']['available_qty'] ?? 0;
                                        $hasSpkRow = $fg['spk_info']['has_spk'] ?? false;
                                        $isSpkShortRow = $spkAvailRow < $targetFgRow;
                                        $spkDeficitRow = max(0, $targetFgRow - $spkAvailRow);
                                    @endphp
                                    @if($hasSpkRow)
                                        @if($isSpkShortRow)
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                                    <span>⚠️</span>
                                                    <span>Kurang {{ number_format($spkDeficitRow) }} pcs</span>
                                                </span>
                                                <div class="text-[10px] text-gray-500 font-mono">
                                                    SPK: <strong class="text-rose-700">{{ number_format($spkAvailRow) }}</strong> / {{ number_format($targetFgRow) }} pcs
                                                </div>
                                                <a href="{{ route('spk.changes.index', ['search' => $fg['item_code']]) }}" 
                                                   target="_blank" 
                                                   class="text-[10px] text-blue-600 hover:text-blue-800 font-bold hover:underline flex items-center gap-0.5">
                                                    <span>SPK Changes</span>
                                                    <span>↗</span>
                                                </a>
                                            </div>
                                        @else
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                    <span>✓</span>
                                                    <span>SPK Cukup</span>
                                                </span>
                                                <div class="text-[10px] text-emerald-700 font-mono font-semibold">
                                                    {{ number_format($spkAvailRow) }} pcs avail ({{ $fg['spk_info']['spk_count'] }} SPK)
                                                </div>
                                                <a href="{{ route('spk.changes.index', ['search' => $fg['item_code']]) }}" 
                                                   target="_blank" 
                                                   class="text-[10px] text-blue-600 hover:text-blue-800 font-bold hover:underline flex items-center gap-0.5">
                                                    <span>SPK Changes</span>
                                                    <span>↗</span>
                                                </a>
                                            </div>
                                        @endif
                                    @else
                                        <div class="inline-flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span>⚠️ Belum Ada SPK</span>
                                            </span>
                                            <span class="text-[10px] text-rose-600 font-mono font-bold">
                                                Kurang {{ number_format($targetFgRow) }} pcs
                                            </span>
                                            <a href="{{ route('spk.changes.index', ['search' => $fg['item_code']]) }}" 
                                               target="_blank" 
                                               class="text-[10px] text-gray-400 hover:text-blue-600 hover:underline">
                                                Cek SPK ↗
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($fg['has_bom'])
                                        <button type="button"
                                                wire:click="showFgDetail('{{ $fg['item_code'] }}')"
                                                class="px-2.5 py-1 text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-lg transition cursor-pointer">
                                            BOM 🔍
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-gray-400">
                                    <span class="text-2xl block mb-2">📭</span>
                                    <span>Tidak ada jadwal delivery untuk kriteria ini.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginatedFgs->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $paginatedFgs->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- MODAL DETAIL MATERIAL (LIST FG & TIMELINE TANGGAL DELIVERY) --}}
    @if($activeMaterialDetail)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]"
                 x-data="{ modalTab: 'timeline', expandedFg: null }">
                
                {{-- Modal Header --}}
                <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gradient-to-r from-slate-50 to-sky-50/40">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-base font-black text-gray-900">{{ $activeMaterialDetail['item_code'] }}</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $activeMaterialDetail['category']['badge'] ?? 'bg-gray-100 text-gray-800' }}">
                                <span>{{ $activeMaterialDetail['category']['icon'] ?? '🧩' }}</span>
                                <span>{{ $activeMaterialDetail['category']['label'] ?? 'Material' }}</span>
                            </span>
                        </div>
                        <div class="text-xs font-bold text-gray-700 mt-1">{{ $activeMaterialDetail['description'] }}</div>

                        {{-- Quick Stats / Badges --}}
                        <div class="flex items-center gap-3 mt-3 flex-wrap text-xs">
                            <div class="px-3 py-1 rounded-lg bg-white border border-amber-200 shadow-xs flex items-center gap-1.5">
                                <span class="text-gray-500 font-medium">Total Kebutuhan ({{ $activeView === 'net' ? 'Net Deficit' : 'Gross Total' }}):</span>
                                <strong class="font-mono font-black text-amber-700 text-sm">
                                    {{ number_format($activeMaterialDetail['total_needed_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                                </strong>
                            </div>
                            <div class="px-2.5 py-1 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 font-semibold flex items-center gap-1">
                                <span>📅</span>
                                <span>{{ count($activeMaterialDetail['daily_timeline'] ?? []) }} Hari Pengiriman</span>
                            </div>
                            <div class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-800 font-semibold flex items-center gap-1">
                                <span>🏭</span>
                                <span>{{ count($activeMaterialDetail['used_by_fgs'] ?? []) }} Part FG</span>
                            </div>
                            @if($activeMaterialDetail['spk_info']['has_spk'] ?? false)
                                <div class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold flex items-center gap-1.5">
                                    <span>📋</span>
                                    <span>SPK Material: <strong>{{ number_format($activeMaterialDetail['spk_info']['available_qty']) }}</strong> {{ $activeMaterialDetail['uom'] }} avail ({{ $activeMaterialDetail['spk_info']['spk_count'] }} SPK)</span>
                                    <a href="{{ route('spk.changes.index', ['search' => $activeMaterialDetail['item_code']]) }}" target="_blank" class="text-blue-600 hover:underline font-bold text-xs" title="Cari di SPK Changes">↗</a>
                                </div>
                            @endif
                            @if(count($activeMaterialDetail['daily_timeline'] ?? []) > 0)
                                <div class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-medium text-[11px] flex items-center gap-1">
                                    <span>📊</span>
                                    <span>Rata-rata ~{{ number_format($activeMaterialDetail['total_needed_qty'] / count($activeMaterialDetail['daily_timeline']), 1) }} {{ $activeMaterialDetail['uom'] }} / hari</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="closeMaterialDetail" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer p-1">
                        ✕
                    </button>
                </div>

                {{-- Modal Sub-Tab Navigation --}}
                <div class="px-5 pt-2.5 pb-0 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="modalTab = 'timeline'"
                                :class="modalTab === 'timeline' ? 'bg-white text-sky-700 font-black border-t-2 border-l border-r border-t-sky-600 border-gray-200 shadow-xs' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                                class="px-4 py-2 text-xs rounded-t-lg transition flex items-center gap-1.5 cursor-pointer">
                            <span>📅</span>
                            <span>Timeline per Tanggal Delivery</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-sky-100 text-sky-800 font-mono">
                                {{ count($activeMaterialDetail['daily_timeline'] ?? []) }}
                            </span>
                        </button>
                        <button type="button"
                                @click="modalTab = 'fgs'"
                                :class="modalTab === 'fgs' ? 'bg-white text-indigo-700 font-black border-t-2 border-l border-r border-t-indigo-600 border-gray-200 shadow-xs' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                                class="px-4 py-2 text-xs rounded-t-lg transition flex items-center gap-1.5 cursor-pointer">
                            <span>🏭</span>
                            <span>Rincian per Finish Good (FG)</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-100 text-indigo-800 font-mono">
                                {{ count($activeMaterialDetail['used_by_fgs'] ?? []) }}
                            </span>
                        </button>
                    </div>
                    <div class="text-[11px] text-gray-500 hidden sm:block">
                        Mode Hitung: <span class="font-bold text-gray-800">{{ $activeView === 'net' ? 'Net (Delivery - Stok FG)' : 'Gross (Total Delivery Sched)' }}</span>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="p-5 overflow-y-auto flex-1">
                    {{-- TAB 1: TIMELINE PER TANGGAL DELIVERY --}}
                    <div x-show="modalTab === 'timeline'" x-cloak>
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase text-gray-600 tracking-wider flex items-center gap-1.5">
                                <span>📅</span>
                                <span>Jadwal Harian Kebutuhan Material (Urut Tanggal Delivery)</span>
                            </h4>
                            <span class="text-[11px] text-gray-400">
                                Menjelaskan rincian tanggal kirim mengapa kebutuhan total mencapai {{ number_format($activeMaterialDetail['total_needed_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                            </span>
                        </div>

                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-xs text-left">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                                        <th class="py-2.5 px-3 text-center w-12">No</th>
                                        <th class="py-2.5 px-3">Tanggal Delivery</th>
                                        <th class="py-2.5 px-3">Part FG yang Dikirim pada Tanggal Ini</th>
                                        <th class="py-2.5 px-3 text-right">Kebutuhan Hari Ini</th>
                                        <th class="py-2.5 px-3 text-right">Akumulasi Kebutuhan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php
                                        $runningTotal = 0.0;
                                    @endphp
                                    @forelse($activeMaterialDetail['daily_timeline'] ?? [] as $tIdx => $tRow)
                                        @php
                                            $runningTotal += $tRow['total_material_qty'];
                                            $dCarbon = \Carbon\Carbon::parse($tRow['date']);
                                        @endphp
                                        <tr class="hover:bg-sky-50/30 transition">
                                            <td class="py-2.5 px-3 text-center font-mono text-gray-400">
                                                {{ $tIdx + 1 }}
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <div class="font-mono font-bold text-gray-900">{{ $tRow['date'] }}</div>
                                                <div class="text-[11px] text-gray-400 font-medium">
                                                    {{ $dCarbon->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($tRow['fgs_summary'] ?? [] as $fgItem)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] bg-slate-100 text-slate-800 border border-slate-200">
                                                            <strong class="font-mono">{{ $fgItem['fg_code'] }}</strong>:
                                                            <span class="font-bold text-sky-700">{{ number_format($fgItem['fg_qty']) }} FG</span>
                                                            <span class="text-gray-400">→</span>
                                                            <span class="font-bold text-amber-700 font-mono">{{ number_format($fgItem['material_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-amber-700 text-sm">
                                                {{ number_format($tRow['total_material_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-600">
                                                {{ number_format($runningTotal, 2) }} {{ $activeMaterialDetail['uom'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-gray-400">
                                                Tidak ada rincian tanggal pengiriman.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if(count($activeMaterialDetail['daily_timeline'] ?? []) > 0)
                                    <tfoot>
                                        <tr class="bg-gray-100/80 font-bold border-t-2 border-gray-300 text-gray-900">
                                            <td colspan="3" class="py-2.5 px-3 text-right uppercase text-[10px] tracking-wider">
                                                TOTAL KEBUTUHAN SELURUH TANGGAL:
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-amber-800 text-sm">
                                                {{ number_format($runningTotal, 2) }} {{ $activeMaterialDetail['uom'] }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono text-xs text-gray-500">
                                                100% Terjadwal
                                            </td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- TAB 2: RINCIAN PER FINISH GOOD --}}
                    <div x-show="modalTab === 'fgs'" x-cloak>
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase text-gray-600 tracking-wider flex items-center gap-1.5">
                                <span>🏭</span>
                                <span>Rincian per Finish Good & Jadwal Kirimnya ({{ count($activeMaterialDetail['used_by_fgs'] ?? []) }} FG)</span>
                            </h4>
                            <span class="text-[11px] text-gray-400">
                                Klik tombol ▾ untuk membuka tanggal delivery per masing-masing FG
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($activeMaterialDetail['used_by_fgs'] ?? [] as $fgIdx => $f)
                                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs bg-white">
                                    {{-- FG Summary Bar --}}
                                    <div class="p-3.5 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between cursor-pointer hover:bg-gray-100/60 transition"
                                         @click="expandedFg = expandedFg === {{ $fgIdx }} ? null : {{ $fgIdx }}">
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono font-black text-gray-900 text-sm">{{ $f['fg_code'] }}</span>
                                            <div>
                                                <div class="font-bold text-gray-800 text-xs">{{ $f['fg_name'] }}</div>
                                                <div class="text-[11px] text-gray-400">{{ $f['customer_name'] ?? '—' }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-4 text-xs">
                                            {{-- Status SPK & Sisa Qty --}}
                                            @php
                                                $targetFgItem = $activeView === 'net' ? ($f['fg_net_needed'] ?? 0) : ($f['fg_delivery_qty'] ?? 0);
                                                $spkAvailItem = $f['spk_info']['available_qty'] ?? 0;
                                                $hasSpkItem = $f['spk_info']['has_spk'] ?? false;
                                                $isSpkShortItem = $spkAvailItem < $targetFgItem;
                                                $spkDeficitItem = max(0, $targetFgItem - $spkAvailItem);
                                            @endphp
                                            <div class="text-right">
                                                <div class="text-[10px] uppercase font-bold flex items-center justify-end gap-1 {{ (!$hasSpkItem || $isSpkShortItem) ? 'text-rose-600' : 'text-emerald-700' }}">
                                                    <span>Status SPK</span>
                                                    <a href="{{ route('spk.changes.index', ['search' => $f['fg_code']]) }}" target="_blank" @click.stop class="hover:underline text-blue-600 font-bold" title="Cari di SPK Changes">↗</a>
                                                </div>
                                                @if($hasSpkItem)
                                                    @if($isSpkShortItem)
                                                        <div class="font-mono font-black text-rose-600 text-xs">
                                                            {{ number_format($spkAvailItem) }} pcs
                                                            <span class="text-[10px] text-gray-400 font-normal">({{ $f['spk_info']['spk_count'] }} SPK)</span>
                                                        </div>
                                                        <div class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 mt-0.5">
                                                            <span>⚠️ Kurang {{ number_format($spkDeficitItem) }} pcs</span>
                                                        </div>
                                                    @else
                                                        <div class="font-mono font-black text-emerald-700 text-xs">
                                                            {{ number_format($spkAvailItem) }} pcs
                                                            <span class="text-[10px] text-gray-400 font-normal">({{ $f['spk_info']['spk_count'] }} SPK)</span>
                                                        </div>
                                                        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">
                                                            ✓ SPK Cukup
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                        Belum Ada SPK
                                                    </span>
                                                    <div class="text-[10px] text-rose-600 font-semibold mt-0.5">
                                                        Kurang {{ number_format($targetFgItem) }} pcs
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="text-right">
                                                <div class="text-[10px] uppercase text-gray-400 font-bold">Target FG</div>
                                                <div class="font-mono font-bold text-gray-800">
                                                    {{ number_format($activeView === 'net' ? ($f['fg_net_needed'] ?? 0) : ($f['fg_delivery_qty'] ?? 0)) }} pcs
                                                    <span class="text-[10px] text-gray-400 font-normal">({{ $f['delivery_count'] ?? count($f['schedules'] ?? []) }}x kirim)</span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-[10px] uppercase text-gray-400 font-bold">Unit BOM</div>
                                                <div class="font-mono text-gray-600">{{ $f['unit_qty'] }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-[10px] uppercase text-amber-600 font-bold">Kebutuhan Material</div>
                                                <div class="font-mono font-black text-amber-700 text-sm">
                                                    {{ number_format($f['material_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                                                </div>
                                            </div>
                                            <button type="button"
                                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200/60 transition">
                                                <span x-text="expandedFg === {{ $fgIdx }} ? '▲' : '▼'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- FG Specific Details Accordion --}}
                                    <div x-show="expandedFg === {{ $fgIdx }}" x-collapse x-cloak class="p-4 bg-slate-50/50 border-t border-gray-100 space-y-4">
                                        
                                        {{-- 1. DAFTAR SPK AKTIF UNTUK FG INI --}}
                                        <div class="bg-white rounded-xl p-3.5 border border-gray-200 shadow-xs">
                                            <div class="flex items-center justify-between mb-2.5 flex-wrap gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-black uppercase text-gray-700 tracking-wider flex items-center gap-1.5">
                                                        <span>📋</span>
                                                        <span>Ketersediaan SPK Produksi ({{ $f['fg_code'] }}):</span>
                                                    </span>
                                                    @if($hasSpkItem)
                                                        @if($isSpkShortItem)
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300 flex items-center gap-1">
                                                                <span>⚠️</span>
                                                                <span>{{ $f['spk_info']['spk_count'] }} SPK (Avail: {{ number_format($spkAvailItem) }} pcs)</span>
                                                                <span class="mx-0.5">•</span>
                                                                <span class="uppercase">Kurang {{ number_format($spkDeficitItem) }} pcs dari target ({{ number_format($targetFgItem) }} pcs)</span>
                                                            </span>
                                                        @else
                                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1">
                                                                <span>✓</span>
                                                                <span>{{ $f['spk_info']['spk_count'] }} SPK Ditemukan | Sisa Available: <strong>{{ number_format($spkAvailItem) }} pcs</strong> (Cukup untuk Target {{ number_format($targetFgItem) }} pcs)</span>
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                            ⚠️ Belum Ada SPK (Kebutuhan: {{ number_format($targetFgItem) }} pcs)
                                                        </span>
                                                    @endif
                                                </div>

                                                <a href="{{ route('spk.changes.index', ['search' => $f['fg_code']]) }}" 
                                                   target="_blank" 
                                                   class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 hover:underline">
                                                    <span>Cek Riwayat di SPK Changes</span>
                                                    <span>↗</span>
                                                </a>
                                            </div>

                                            @if($f['spk_info']['has_spk'] ?? false)
                                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                                    <table class="w-full text-xs text-left">
                                                        <thead>
                                                            <tr class="bg-gray-100 text-gray-600 uppercase font-bold text-[10px] border-b border-gray-200">
                                                                <th class="py-1.5 px-2.5">No SPK</th>
                                                                <th class="py-1.5 px-2.5 text-center">Status</th>
                                                                <th class="py-1.5 px-2.5 text-right">Planned Qty</th>
                                                                <th class="py-1.5 px-2.5 text-right">Completed Qty</th>
                                                                <th class="py-1.5 px-2.5 text-right">Sisa (Available Qty)</th>
                                                                <th class="py-1.5 px-2.5 text-center">Gudang</th>
                                                                <th class="py-1.5 px-2.5 text-center">Due Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            @foreach($f['spk_info']['spks'] as $spk)
                                                                <tr class="hover:bg-slate-50 transition">
                                                                    <td class="py-1.5 px-2.5 font-mono font-bold text-blue-700">
                                                                        <a href="{{ route('spk.changes.index', ['search' => $spk['spk_number']]) }}" target="_blank" class="hover:underline flex items-center gap-1" title="Lihat detail SPK">
                                                                            <span>{{ $spk['spk_number'] }}</span>
                                                                            <span class="text-[10px] text-blue-400">↗</span>
                                                                        </a>
                                                                    </td>
                                                                    <td class="py-1.5 px-2.5 text-center">
                                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $spk['status'] === 'R' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                                                            {{ $spk['status_label'] }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="py-1.5 px-2.5 text-right font-mono font-semibold">{{ number_format($spk['planned_qty']) }}</td>
                                                                    <td class="py-1.5 px-2.5 text-right font-mono text-gray-500">{{ number_format($spk['completed_qty']) }}</td>
                                                                    <td class="py-1.5 px-2.5 text-right font-mono font-black {{ $spk['available_qty'] > 0 ? 'text-emerald-700' : 'text-gray-400' }}">
                                                                        {{ number_format($spk['available_qty']) }} pcs
                                                                    </td>
                                                                    <td class="py-1.5 px-2.5 text-center font-mono text-gray-500">{{ $spk['warehouse'] ?: '—' }}</td>
                                                                    <td class="py-1.5 px-2.5 text-center font-mono text-gray-500">{{ $spk['due_date'] ?: '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="p-3 text-center text-xs text-rose-700 bg-rose-50/50 rounded-lg border border-dashed border-rose-200">
                                                    ⚠️ Belum ada SPK terdaftar di sistem untuk part ini. Silakan koordinasikan dengan PPIC / buat SPK baru.
                                                </div>
                                            @endif
                                        </div>

                                        {{-- 2. JADWAL DELIVERY HARIAN --}}
                                        <div class="bg-white rounded-xl p-3.5 border border-gray-200 shadow-xs">
                                            <div class="text-[11px] font-bold text-gray-700 mb-2 uppercase tracking-wide flex items-center gap-1.5">
                                                <span>📅</span>
                                                <span>Jadwal Delivery Harian untuk Part FG {{ $f['fg_code'] }}:</span>
                                            </div>
                                            <table class="w-full text-xs text-left">
                                                <thead>
                                                    <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-100">
                                                        <th class="py-1.5 px-2.5 text-center w-10">No</th>
                                                        <th class="py-1.5 px-2.5">Tanggal Delivery</th>
                                                        <th class="py-1.5 px-2.5">No SO / Dokumen</th>
                                                        <th class="py-1.5 px-2.5 text-right">Target FG (Pcs)</th>
                                                        <th class="py-1.5 px-2.5 text-right">Kebutuhan Material</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @forelse($f['schedules'] ?? [] as $sIdx => $sc)
                                                        <tr class="hover:bg-amber-50/20">
                                                            <td class="py-1.5 px-2.5 text-center font-mono text-gray-400">{{ $sIdx + 1 }}</td>
                                                            <td class="py-1.5 px-2.5 font-mono font-bold text-gray-900">{{ $sc['delivery_date'] }}</td>
                                                            <td class="py-1.5 px-2.5 font-mono text-gray-600">{{ $sc['so_number'] ?: '—' }}</td>
                                                            <td class="py-1.5 px-2.5 text-right font-mono font-bold text-sky-700">
                                                                {{ number_format($sc['delivery_qty']) }}
                                                            </td>
                                                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-amber-700">
                                                                {{ number_format($sc['material_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="py-3 text-center text-gray-400">Tidak ada rincian tanggal kirim.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs text-gray-400">
                        Tekan tombol Tutup untuk kembali ke daftar rekap material.
                    </span>
                    <button type="button" wire:click="closeMaterialDetail" class="px-5 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 cursor-pointer shadow-xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL FG (BREAKDOWN BOM MATERIAL & JADWAL DELIVERY FG) --}}
    @if($activeFgDetail)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]"
                 x-data="{ fgModalTab: 'bom' }">
                
                {{-- Header --}}
                <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gradient-to-r from-slate-50 to-indigo-50/40">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-base font-black text-gray-900">{{ $activeFgDetail['item_code'] }}</span>
                            <span class="text-xs font-bold text-gray-500">| {{ $activeFgDetail['customer_name'] }}</span>
                        </div>
                        <div class="text-xs font-bold text-gray-700 mt-1">{{ $activeFgDetail['item_name'] }}</div>
                        <div class="flex items-center gap-3 mt-3 flex-wrap text-xs font-mono">
                            <span class="px-2.5 py-1 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 font-semibold">
                                Delivery Sched: <strong>{{ number_format($activeFgDetail['delivery_qty']) }}</strong> pcs ({{ $activeFgDetail['delivery_count'] ?? count($activeFgDetail['daily_schedules'] ?? []) }}x)
                            </span>
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-800 font-semibold">
                                Stok FG Aktual: <strong>{{ number_format($activeFgDetail['stock_actual']) }}</strong> pcs
                            </span>
                            <span class="px-2.5 py-1 rounded-lg border {{ $activeFgDetail['net_deficit'] > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' }} font-bold">
                                Kekurangan (Net): <strong>{{ number_format($activeFgDetail['net_deficit']) }}</strong> pcs
                            </span>
                            @php
                                $targetFgModal = $activeView === 'net' ? $activeFgDetail['net_deficit'] : $activeFgDetail['delivery_qty'];
                                $spkAvailModal = $activeFgDetail['spk_info']['available_qty'] ?? 0;
                                $hasSpkModal = $activeFgDetail['spk_info']['has_spk'] ?? false;
                                $isSpkShortModal = $spkAvailModal < $targetFgModal;
                                $spkDeficitModal = max(0, $targetFgModal - $spkAvailModal);
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg border {{ (!$hasSpkModal || $isSpkShortModal) ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' }} font-bold flex items-center gap-1">
                                <span>📋 SPK:</span>
                                @if($hasSpkModal)
                                    <strong>{{ number_format($spkAvailModal) }} pcs avail</strong>
                                    <span class="text-[10px] text-gray-500 font-normal">({{ $activeFgDetail['spk_info']['spk_count'] }} SPK)</span>
                                    @if($isSpkShortModal)
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-rose-200 text-rose-900 font-black">
                                            Kurang {{ number_format($spkDeficitModal) }} pcs
                                        </span>
                                    @else
                                        <span class="ml-1 text-[10px] text-emerald-700 font-bold">✓ Cukup</span>
                                    @endif
                                @else
                                    <span>Belum Ada SPK (Kurang {{ number_format($targetFgModal) }} pcs)</span>
                                @endif
                                <a href="{{ route('spk.changes.index', ['search' => $activeFgDetail['item_code']]) }}" target="_blank" class="text-blue-600 hover:underline text-[11px] font-bold" title="Cari di SPK Changes">↗</a>
                            </span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeFgDetail" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer p-1">
                        ✕
                    </button>
                </div>

                {{-- Sub-Tab Navigation --}}
                <div class="px-5 pt-2.5 pb-0 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="fgModalTab = 'bom'"
                                :class="fgModalTab === 'bom' ? 'bg-white text-indigo-700 font-black border-t-2 border-l border-r border-t-indigo-600 border-gray-200 shadow-xs' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                                class="px-4 py-2 text-xs rounded-t-lg transition flex items-center gap-1.5 cursor-pointer">
                            <span>🧩</span>
                            <span>Komponen & Material BOM</span>
                        </button>
                        <button type="button"
                                @click="fgModalTab = 'schedules'"
                                :class="fgModalTab === 'schedules' ? 'bg-white text-sky-700 font-black border-t-2 border-l border-r border-t-sky-600 border-gray-200 shadow-xs' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                                class="px-4 py-2 text-xs rounded-t-lg transition flex items-center gap-1.5 cursor-pointer">
                            <span>📅</span>
                            <span>Jadwal Delivery Harian</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-sky-100 text-sky-800 font-mono">
                                {{ count($activeFgDetail['daily_schedules'] ?? []) }}
                            </span>
                        </button>
                        <button type="button"
                                @click="fgModalTab = 'spks'"
                                :class="fgModalTab === 'spks' ? 'bg-white text-emerald-700 font-black border-t-2 border-l border-r border-t-emerald-600 border-gray-200 shadow-xs' : 'text-gray-500 hover:text-gray-800 font-semibold'"
                                class="px-4 py-2 text-xs rounded-t-lg transition flex items-center gap-1.5 cursor-pointer">
                            <span>📋</span>
                            <span>Daftar SPK Tersedia</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono {{ ($activeFgDetail['spk_info']['has_spk'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $activeFgDetail['spk_info']['spk_count'] ?? 0 }}
                            </span>
                        </button>
                    </div>
                    <div class="text-[11px] text-gray-500 hidden sm:block">
                        Mode Hitung: <strong class="text-gray-800">{{ $activeView === 'net' ? 'Net Deficit' : 'Gross Delivery' }}</strong>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-5 overflow-y-auto flex-1">
                    {{-- TAB 1: BOM --}}
                    <div x-show="fgModalTab === 'bom'" x-cloak>
                        <h4 class="text-xs font-black uppercase text-gray-500 mb-3 tracking-wider">
                            Rincian Kebutuhan Material (BOM Explosion)
                        </h4>
                        @php
                            $explodedList = $activeView === 'net' ? ($activeFgDetail['exploded_net'] ?? []) : ($activeFgDetail['exploded_gross'] ?? []);
                        @endphp
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-xs text-left">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                                        <th class="py-2.5 px-3">Kode Material</th>
                                        <th class="py-2.5 px-3">Deskripsi</th>
                                        <th class="py-2.5 px-3">Kategori</th>
                                        <th class="py-2.5 px-3 text-right">Unit Qty BOM</th>
                                        <th class="py-2.5 px-3 text-right">Kebutuhan Total</th>
                                        <th class="py-2.5 px-3 text-center">UoM</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($explodedList as $c)
                                        <tr class="hover:bg-indigo-50/20">
                                            <td class="py-2.5 px-3 font-mono font-bold text-gray-900">{{ $c['item_code'] }}</td>
                                            <td class="py-2.5 px-3 text-gray-700">{{ $c['description'] }}</td>
                                            <td class="py-2.5 px-3">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $c['category']['badge'] ?? 'bg-gray-100 text-gray-800' }}">
                                                    <span>{{ $c['category']['icon'] ?? '🧩' }}</span>
                                                    <span>{{ $c['category']['label'] ?? 'Material' }}</span>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono text-gray-500">{{ $c['unit_qty'] }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-amber-700">
                                                {{ number_format($c['needed_qty'], 2) }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-mono font-bold text-gray-400">{{ $c['uom'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-gray-400">
                                                Tidak ada kebutuhan material (stok FG mencukupi).
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 2: SCHEDULES --}}
                    <div x-show="fgModalTab === 'schedules'" x-cloak>
                        <h4 class="text-xs font-black uppercase text-gray-500 mb-3 tracking-wider">
                            Rincian Tanggal Delivery Harian Part Ini ({{ count($activeFgDetail['daily_schedules'] ?? []) }} Tanggal)
                        </h4>
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-xs text-left">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                                        <th class="py-2.5 px-3 text-center w-12">No</th>
                                        <th class="py-2.5 px-3">Tanggal Delivery</th>
                                        <th class="py-2.5 px-3">No SO / Dokumen</th>
                                        <th class="py-2.5 px-3 text-right">Target Delivery Qty (Pcs)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php $totSched = 0; @endphp
                                    @forelse($activeFgDetail['daily_schedules'] ?? [] as $sIdx => $ds)
                                        @php
                                            $totSched += $ds['delivery_qty'];
                                            $dCarbon = \Carbon\Carbon::parse($ds['delivery_date']);
                                        @endphp
                                        <tr class="hover:bg-sky-50/20">
                                            <td class="py-2.5 px-3 text-center font-mono text-gray-400">{{ $sIdx + 1 }}</td>
                                            <td class="py-2.5 px-3">
                                                <span class="font-mono font-bold text-gray-900">{{ $ds['delivery_date'] }}</span>
                                                <span class="text-[11px] text-gray-400 block">
                                                    {{ $dCarbon->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-gray-600">{{ $ds['so_number'] ?: '—' }}</td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black text-sky-700">
                                                {{ number_format($ds['delivery_qty']) }} pcs
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-gray-400">Tidak ada jadwal delivery harian.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-100/80 font-bold border-t-2 border-gray-300">
                                        <td colspan="3" class="py-2.5 px-3 text-right uppercase text-[10px] tracking-wider">Total Delivery Sched:</td>
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-sky-800">{{ number_format($totSched) }} pcs</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 3: SPK TERSINGKRON --}}
                    <div x-show="fgModalTab === 'spks'" x-cloak>
                        <div class="mb-3 flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-xs font-black uppercase text-gray-700 tracking-wider flex items-center gap-1.5">
                                <span>📋</span>
                                <span>Daftar SPK Terdaftar untuk Part {{ $activeFgDetail['item_code'] }}:</span>
                            </h4>
                            <a href="{{ route('spk.changes.index', ['search' => $activeFgDetail['item_code']]) }}" 
                               target="_blank" 
                               class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 hover:underline">
                                <span>Buka Riwayat Perubahan di SPK Changes</span>
                                <span>↗</span>
                            </a>
                        </div>

                        @if($activeFgDetail['spk_info']['has_spk'] ?? false)
                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                                <table class="w-full text-xs text-left">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                                            <th class="py-2.5 px-3">No SPK</th>
                                            <th class="py-2.5 px-3 text-center">Status</th>
                                            <th class="py-2.5 px-3 text-right">Planned Qty</th>
                                            <th class="py-2.5 px-3 text-right">Completed Qty</th>
                                            <th class="py-2.5 px-3 text-right">Sisa (Available Qty)</th>
                                            <th class="py-2.5 px-3 text-center">Gudang</th>
                                            <th class="py-2.5 px-3 text-center">Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($activeFgDetail['spk_info']['spks'] as $spk)
                                            <tr class="hover:bg-emerald-50/20">
                                                <td class="py-2.5 px-3 font-mono font-bold text-blue-700">
                                                    <a href="{{ route('spk.changes.index', ['search' => $spk['spk_number']]) }}" target="_blank" class="hover:underline flex items-center gap-1" title="Lihat detail SPK">
                                                        <span>{{ $spk['spk_number'] }}</span>
                                                        <span class="text-[10px] text-blue-400">↗</span>
                                                    </a>
                                                </td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $spk['status'] === 'R' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                                        {{ $spk['status_label'] }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-800">{{ number_format($spk['planned_qty']) }}</td>
                                                <td class="py-2.5 px-3 text-right font-mono text-gray-500">{{ number_format($spk['completed_qty']) }}</td>
                                                <td class="py-2.5 px-3 text-right font-mono font-black {{ $spk['available_qty'] > 0 ? 'text-emerald-700' : 'text-gray-400' }}">
                                                    {{ number_format($spk['available_qty']) }} pcs
                                                </td>
                                                <td class="py-2.5 px-3 text-center font-mono text-gray-500">{{ $spk['warehouse'] ?: '—' }}</td>
                                                <td class="py-2.5 px-3 text-center font-mono text-gray-500">{{ $spk['due_date'] ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-100/80 font-bold border-t-2 border-gray-300">
                                            <td colspan="4" class="py-2.5 px-3 text-right uppercase text-[10px] tracking-wider">TOTAL AVAILABLE DARI SELURUH SPK:</td>
                                            <td class="py-2.5 px-3 text-right font-mono font-black {{ $isSpkShortModal ? 'text-rose-600' : 'text-emerald-700' }} text-sm">
                                                {{ number_format($spkAvailModal) }} pcs
                                                @if($isSpkShortModal)
                                                    <div class="text-[10px] font-bold text-rose-600">
                                                        ⚠️ Kurang {{ number_format($spkDeficitModal) }} pcs dari target ({{ number_format($targetFgModal) }} pcs)
                                                    </div>
                                                @endif
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="p-6 text-center text-xs text-rose-700 bg-rose-50 rounded-xl border border-rose-200">
                                <span class="text-2xl block mb-1">⚠️</span>
                                <div class="font-bold text-sm">Belum Ada SPK untuk Part Ini</div>
                                <p class="text-gray-500 mt-1">Belum ditemukan SPK berstatus Released / Planned di master SPK SAP untuk item {{ $activeFgDetail['item_code'] }}.</p>
                                <div class="mt-3">
                                    <a href="{{ route('spk.changes.index') }}" target="_blank" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition inline-block">
                                        Buka SPK Changes ↗
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeFgDetail" class="px-5 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 cursor-pointer shadow-xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

