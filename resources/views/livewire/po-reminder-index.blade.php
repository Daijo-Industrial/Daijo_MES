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
                                    {{ $fg['item_code'] }}
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

    {{-- MODAL DETAIL MATERIAL (LIST FG YANG MENGGUNAKAN MATERIAL INI) --}}
    @if($activeMaterialDetail)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gray-50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-base font-black text-gray-900">{{ $activeMaterialDetail['item_code'] }}</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $activeMaterialDetail['category']['badge'] ?? 'bg-gray-100 text-gray-800' }}">
                                <span>{{ $activeMaterialDetail['category']['icon'] ?? '🧩' }}</span>
                                <span>{{ $activeMaterialDetail['category']['label'] ?? 'Material' }}</span>
                            </span>
                        </div>
                        <div class="text-xs font-bold text-gray-600 mt-1">{{ $activeMaterialDetail['description'] }}</div>
                        <div class="text-xs text-gray-500 mt-2">
                            Total Kebutuhan ({{ $activeView === 'net' ? 'Net: Delivery - Stok' : 'Gross: Total Delivery' }}):
                            <strong class="font-mono text-sm text-gray-900">{{ number_format($activeMaterialDetail['total_needed_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}</strong>
                        </div>
                    </div>
                    <button type="button" wire:click="closeMaterialDetail" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="p-5 max-h-96 overflow-y-auto">
                    <h4 class="text-xs font-black uppercase text-gray-500 mb-3 tracking-wider">
                        Daftar Finish Good yang Membutuhkan Material Ini ({{ count($activeMaterialDetail['used_by_fgs'] ?? []) }} FG)
                    </h4>
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-100">
                                <th class="py-2 px-3">Kode FG</th>
                                <th class="py-2 px-3">Nama Part</th>
                                <th class="py-2 px-3 text-right">Target FG (Pcs)</th>
                                <th class="py-2 px-3 text-right">Unit Qty BOM</th>
                                <th class="py-2 px-3 text-right">Kebutuhan Material</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($activeMaterialDetail['used_by_fgs'] ?? [] as $f)
                                <tr>
                                    <td class="py-2.5 px-3 font-mono font-bold text-gray-900">{{ $f['fg_code'] }}</td>
                                    <td class="py-2.5 px-3 text-gray-700">{{ $f['fg_name'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-800">
                                        {{ number_format($activeView === 'net' ? ($f['fg_net_needed'] ?? 0) : ($f['fg_delivery_qty'] ?? 0)) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-gray-500">{{ $f['unit_qty'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-black text-amber-600">
                                        {{ number_format($f['material_qty'], 2) }} {{ $activeMaterialDetail['uom'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeMaterialDetail" class="px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL FG (BREAKDOWN BOM MATERIAL UNTUK FG INI) --}}
    @if($activeFgDetail)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gray-50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-base font-black text-gray-900">{{ $activeFgDetail['item_code'] }}</span>
                            <span class="text-xs font-bold text-gray-500">| {{ $activeFgDetail['customer_name'] }}</span>
                        </div>
                        <div class="text-xs font-bold text-gray-700 mt-1">{{ $activeFgDetail['item_name'] }}</div>
                        <div class="flex items-center gap-4 mt-2 text-xs font-mono">
                            <span>Delivery: <strong>{{ number_format($activeFgDetail['delivery_qty']) }}</strong></span>
                            <span>Stok FG: <strong class="text-indigo-600">{{ number_format($activeFgDetail['stock_actual']) }}</strong></span>
                            <span>Kekurangan (Net): <strong class="text-amber-600">{{ number_format($activeFgDetail['net_deficit']) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeFgDetail" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="p-5 max-h-96 overflow-y-auto">
                    <h4 class="text-xs font-black uppercase text-gray-500 mb-3 tracking-wider">
                        Komponen & Material Dibutuhkan (Mode: {{ $activeView === 'net' ? 'Net Deficit' : 'Gross Delivery' }})
                    </h4>
                    @php
                        $explodedList = $activeView === 'net' ? ($activeFgDetail['exploded_net'] ?? []) : ($activeFgDetail['exploded_gross'] ?? []);
                    @endphp
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-100">
                                <th class="py-2 px-3">Kode Material</th>
                                <th class="py-2 px-3">Deskripsi</th>
                                <th class="py-2 px-3">Kategori</th>
                                <th class="py-2 px-3 text-right">Unit Qty BOM</th>
                                <th class="py-2 px-3 text-right">Kebutuhan Qty</th>
                                <th class="py-2 px-3 text-center">UoM</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($explodedList as $c)
                                <tr>
                                    <td class="py-2.5 px-3 font-mono font-bold text-gray-900">{{ $c['item_code'] }}</td>
                                    <td class="py-2.5 px-3 text-gray-700">{{ $c['description'] }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $c['category']['badge'] ?? 'bg-gray-100 text-gray-800' }}">
                                            <span>{{ $c['category']['icon'] ?? '🧩' }}</span>
                                            <span>{{ $c['category']['label'] ?? 'Material' }}</span>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-gray-500">{{ $c['unit_qty'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-black text-gray-900">
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

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeFgDetail" class="px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

