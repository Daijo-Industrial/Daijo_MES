<div class="p-4 sm:p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-5">

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-2xl flex items-center justify-between text-emerald-900 shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">✅</span>
                    <span class="text-xs sm:text-sm font-bold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm font-bold p-1">✕</button>
            </div>
        @endif

        @if (session()->has('info'))
            <div class="p-4 bg-blue-50 border-l-4 border-blue-500 rounded-r-2xl flex items-center justify-between text-blue-900 shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">ℹ️</span>
                    <span class="text-xs sm:text-sm font-bold">{{ session('info') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700 text-sm font-bold p-1">✕</button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-2xl flex items-center justify-between text-rose-900 shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center space-x-3">
                    <span class="text-xl">⚠️</span>
                    <span class="text-xs sm:text-sm font-bold">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm font-bold p-1">✕</button>
            </div>
        @endif

        <!-- Main Header & Action Bar -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-md uppercase tracking-wider">Store Material</span>
                    <span class="text-slate-300">|</span>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                        <span>📋</span>
                        <span>Audit Harian Material</span>
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Verifikasi fisik stok rak gudang bahan baku vs data sistem, pencatatan actual barang & quantity, serta rekap alasan selisih.
                </p>
            </div>

            <!-- Top Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                <button 
                    type="button" 
                    wire:click="fillAllActualFromSystem" 
                    class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs active:scale-95"
                    title="Isi otomatis semua data actual sesuai data sistem"
                >
                    <span>⚡</span>
                    <span>Isi Cepat Sesuai Sistem</span>
                </button>

                <button 
                    type="button" 
                    wire:click="clearAllActual" 
                    class="px-3 py-2 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-600 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-2xs"
                    title="Kosongkan semua isian actual"
                >
                    <span>🔄</span>
                    <span>Reset</span>
                </button>

                <button 
                    type="button" 
                    wire:click="openHistoryModal" 
                    class="px-3.5 py-2 bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                >
                    <span>📜</span>
                    <span>Riwayat Audit</span>
                </button>

                <button 
                    type="button" 
                    wire:click="saveAudit" 
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-200 transition flex items-center gap-2 active:scale-95"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>SIMPAN HASIL AUDIT</span>
                </button>
            </div>
        </div>

        <!-- Branch Selector & Audit Parameters Card -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200/80 space-y-4">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                <!-- Branch Selector Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider pr-1 flex items-center gap-1">
                        <span>🏭</span> PILIH GUDANG:
                    </span>
                    @foreach($warehouses as $wh)
                        <button 
                            type="button"
                            wire:click="$set('selectedWhseId', {{ $wh['id'] }})"
                            style="{{ $selectedWhseId == $wh['id'] ? 'background-color: #059669 !important; color: #ffffff !important;' : 'background-color: #f1f5f9; color: #334155;' }}"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs {{ $selectedWhseId == $wh['id'] ? 'bg-emerald-600 text-white ring-2 ring-emerald-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                        >
                            <span>{{ $wh['whse_name'] }}</span>
                            <span class="px-1 py-0.2 rounded text-[10px] font-mono font-bold {{ $selectedWhseId == $wh['id'] ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-200 text-slate-600' }}">
                                {{ $wh['whse_code'] }}
                            </span>
                        </button>
                    @endforeach
                    <button 
                        type="button"
                        wire:click="$set('selectedWhseId', 'ALL')"
                        style="{{ $selectedWhseId === 'ALL' ? 'background-color: #1e293b !important; color: #ffffff !important;' : 'background-color: #f1f5f9; color: #334155;' }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs {{ $selectedWhseId === 'ALL' ? 'bg-slate-800 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                    >
                        🌐 Semua Branch
                    </button>
                </div>

                <!-- Audit Code Badge -->
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Kode Audit:</span>
                    <span class="font-mono text-xs font-black text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 inline-block">
                        {{ $auditCode }}
                    </span>
                </div>
            </div>

            <!-- Parameters Strip: Date, Auditor, Notes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">📅 Tanggal Audit</label>
                    <input 
                        type="date" 
                        wire:model.live="auditDate" 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">👤 Nama Auditor / PIC</label>
                    <input 
                        type="text" 
                        wire:model="auditorName" 
                        placeholder="Nama Auditor..."
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 mb-1">📝 Catatan Audit</label>
                    <input 
                        type="text" 
                        wire:model="notes" 
                        placeholder="Contoh: Audit stok shift pagi, pengecekan berkala rak A & B..."
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                </div>
            </div>
        </div>

        <!-- Real-Time Metrics Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <!-- Card 1: Total Pallet/Slot -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Item Diaudit</div>
                <div class="text-2xl sm:text-3xl font-black text-slate-800 mt-1">
                    {{ $totalAll }}
                    <span class="text-xs font-semibold text-slate-400">slot/pallet</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-1">Bahan baku tersimpan di rak</div>
            </div>

            <!-- Card 2: Sudah Diisi -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Progress Terisi</div>
                <div class="text-2xl sm:text-3xl font-black text-indigo-700 mt-1">
                    {{ $totalFilled }}
                    <span class="text-xs font-semibold text-slate-400">/ {{ $totalAll }}</span>
                </div>
                <div class="text-[10px] text-indigo-500 font-bold mt-1">
                    {{ $totalAll > 0 ? round(($totalFilled / $totalAll) * 100) : 0 }}% selesai diperiksa
                </div>
            </div>

            <!-- Card 3: Sesuai (Match) -->
            <div class="bg-emerald-50/60 p-4 rounded-2xl shadow-sm border border-emerald-200">
                <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">✓ Sesuai (Match)</div>
                <div class="text-2xl sm:text-3xl font-black text-emerald-800 mt-1">
                    {{ $totalMatch }}
                    <span class="text-xs font-semibold text-emerald-600">item</span>
                </div>
                <div class="text-[10px] text-emerald-600 font-semibold mt-1">Fisik match 100% dengan sistem</div>
            </div>

            <!-- Card 4: Selisih (Discrepancy) -->
            <div class="bg-amber-50/70 p-4 rounded-2xl shadow-sm border border-amber-200">
                <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">⚠️ Ada Selisih</div>
                <div class="text-2xl sm:text-3xl font-black text-amber-900 mt-1">
                    {{ $totalDiscrepancy }}
                    <span class="text-xs font-semibold text-amber-700">item</span>
                </div>
                <div class="text-[10px] text-amber-700 font-semibold mt-1">Memerlukan alasan perbedaan</div>
            </div>
        </div>

        <!-- Filter Bar & Search Controls -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Search Box -->
                <div class="relative w-full sm:w-64">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchTerm" 
                        placeholder="Cari Rak, Part No, Pallet, Lot..." 
                        class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    @if($searchTerm)
                        <button type="button" wire:click="$set('searchTerm', '')" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                    @endif
                </div>

                <!-- Area / Blok Rak Filter -->
                <div class="w-full sm:w-44">
                    <select 
                        wire:model.live="selectedAreaFilter" 
                        class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                        <option value="ALL">-- Semua Area Rak --</option>
                        @foreach($availableAreas as $area)
                            <option value="{{ $area }}">Rak {{ $area }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-48">
                    <select 
                        wire:model.live="statusFilter" 
                        class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-500 transition"
                    >
                        <option value="ALL">-- Semua Status --</option>
                        <option value="DISCREPANCY_ONLY">⚠️ Hanya Ada Selisih</option>
                        <option value="MATCH_ONLY">✓ Hanya Yang Sesuai</option>
                        <option value="PENDING_ONLY">⏳ Hanya Yang Belum Diisi</option>
                    </select>
                </div>
            </div>

            <div class="text-xs font-bold text-slate-500 text-right">
                Menampilkan <span class="text-slate-800 font-black">{{ count($displayItems) }}</span> baris
            </div>
        </div>

        <!-- Main Audit Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <th class="py-3 px-3 w-12 text-center">No</th>
                            <th class="py-3 px-3 w-36">Rak / Posisi</th>
                            <th class="py-3 px-3 min-w-[220px]">Detail Data Sistem</th>
                            <th class="py-3 px-3 w-32 text-right">Qty Sistem</th>
                            <th class="py-3 px-3 w-48 bg-emerald-50/70 border-l border-emerald-200/80 text-emerald-900">
                                Actual Barang (Part No)
                            </th>
                            <th class="py-3 px-3 w-36 bg-emerald-50/70 text-emerald-900 text-right">
                                Actual Qty
                            </th>
                            <th class="py-3 px-3 min-w-[240px] bg-amber-50/60 border-l border-amber-200/80 text-amber-900">
                                Alasan Jika Ada Perbedaan Data
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($displayItems as $key => $item)
                            @php
                                $sysItem   = trim($item['system_item_code'] ?? '');
                                $actItem   = trim($item['actual_item_code'] ?? '');
                                $sysQty    = (float) ($item['system_qty'] ?? 0);
                                $actQty    = $item['actual_qty'] !== null && $item['actual_qty'] !== '' ? (float) $item['actual_qty'] : null;
                                $hasActual = ($actItem !== '' || $actQty !== null);

                                $itemMatches = ($actItem === '' || strcasecmp($actItem, $sysItem) === 0);
                                $qtyMatches  = ($actQty !== null && abs($actQty - $sysQty) < 0.001);
                                $isMatch     = $hasActual && $itemMatches && $qtyMatches;
                                $isDiff      = $hasActual && (!$itemMatches || !$qtyMatches);
                                $diffQty     = $actQty !== null ? ($actQty - $sysQty) : 0;
                            @endphp

                            <tr class="hover:bg-slate-50/80 transition-colors {{ $isDiff ? 'bg-amber-50/40 border-l-4 border-l-amber-500' : ($isMatch ? 'bg-emerald-50/20' : '') }}" wire:key="row-{{ $key }}">
                                <!-- No -->
                                <td class="py-3 px-3 text-center text-slate-400 font-mono font-bold">
                                    {{ $loop->iteration }}
                                </td>

                                <!-- Rak / Posisi -->
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-black text-slate-900 text-xs px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200">
                                            {{ $item['position_code'] ?? 'N/A' }}
                                        </span>
                                    </div>
                                    @if(!empty($item['rack_code']))
                                        <div class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                            Rak {{ $item['rack_code'] }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Detail Data Sistem -->
                                <td class="py-3 px-3">
                                    <div class="font-mono font-black text-slate-800 text-xs flex items-center gap-1.5">
                                        <span>{{ $item['system_item_code'] }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-600 line-clamp-1 mt-0.5 font-normal">
                                        {{ $item['system_item_name'] ?? '-' }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-[10px] text-slate-400">
                                        @if(!empty($item['pallet_id']))
                                            <span class="font-mono bg-slate-100 px-1.5 py-0.2 rounded font-bold text-slate-600">
                                                ID: {{ $item['pallet_id'] }}
                                            </span>
                                        @endif
                                        @if(!empty($item['system_lot_no']))
                                            <span class="font-mono bg-slate-100 px-1.5 py-0.2 rounded font-semibold text-slate-500">
                                                Lot: {{ $item['system_lot_no'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Qty Sistem -->
                                <td class="py-3 px-3 text-right">
                                    <span class="font-mono font-black text-slate-800 text-sm">
                                        {{ number_format($sysQty, 2) }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-bold block">
                                        {{ $item['uom'] ?? 'KG' }}
                                    </span>
                                </td>

                                <!-- Actual Barang (INPUT) -->
                                <td class="py-2.5 px-3 bg-emerald-50/30 border-l border-emerald-100">
                                    <div class="flex items-center gap-1">
                                        <input 
                                            type="text" 
                                            wire:model.blur="items.{{ $key }}.actual_item_code"
                                            placeholder="{{ $item['system_item_code'] }}"
                                            class="w-full px-2.5 py-1.5 bg-white border {{ !$itemMatches ? 'border-amber-400 ring-2 ring-amber-200' : 'border-slate-300' }} rounded-lg text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 transition"
                                        >
                                        <button 
                                            type="button" 
                                            wire:click="fillRowFromSystem('{{ $key }}')" 
                                            class="p-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-md text-[10px] font-bold shadow-2xs whitespace-nowrap"
                                            title="Samakan baris ini dengan sistem"
                                        >
                                            =
                                        </button>
                                    </div>
                                    @if(!$itemMatches && $actItem !== '')
                                        <div class="text-[10px] text-amber-700 font-bold mt-0.5 flex items-center gap-1">
                                            <span>⚠️ Part No Beda</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Actual Qty (INPUT) -->
                                <td class="py-2.5 px-3 bg-emerald-50/30 text-right">
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        wire:model.blur="items.{{ $key }}.actual_qty"
                                        placeholder="{{ number_format($sysQty, 2) }}"
                                        class="w-full px-2.5 py-1.5 bg-white border {{ $isDiff ? 'border-amber-400 ring-2 ring-amber-200' : ($isMatch ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-300') }} rounded-lg text-xs font-black text-right text-slate-800 focus:ring-2 focus:ring-emerald-500 transition"
                                    >
                                    <!-- Dynamic Difference Badge -->
                                    <div class="mt-1 flex justify-end">
                                        @if($isMatch)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ✓ MATCH
                                            </span>
                                        @elseif($isDiff)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                                ⚠️ {{ $diffQty >= 0 ? '+' : '' }}{{ number_format($diffQty, 2) }}
                                            </span>
                                        @else
                                            <span class="text-[9px] text-slate-400 font-semibold">
                                                Belum diisi
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Alasan Jika Ada Perbedaan Data (INPUT) -->
                                <td class="py-2.5 px-3 bg-amber-50/20 border-l border-amber-100">
                                    <input 
                                        type="text" 
                                        wire:model.blur="items.{{ $key }}.discrepancy_reason"
                                        placeholder="{{ $isDiff ? 'Tuliskan alasan selisih stok / part code...' : 'Alasan jika berbeda...' }}"
                                        class="w-full px-2.5 py-1.5 bg-white border {{ $isDiff && empty($item['discrepancy_reason']) ? 'border-rose-400 bg-rose-50/40 placeholder-rose-400' : 'border-slate-300' }} rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 transition"
                                    >
                                    @if($isDiff && empty($item['discrepancy_reason']))
                                        <div class="text-[10px] text-rose-600 font-bold mt-0.5 flex items-center gap-0.5">
                                            <span>⚠️ Harap cantumkan alasan selisih</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="text-4xl mb-2">📦</div>
                                    <div class="font-bold text-sm text-slate-600">Tidak ada data pallet material yang ditemukan</div>
                                    <p class="text-xs text-slate-400 mt-1">Pastikan filter area rak atau kata kunci pencarian sesuai.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Bottom Action Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200/80 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div class="text-xs text-slate-500 font-medium">
                    Audit Harian Store Material &bull; Tanggal {{ Carbon\Carbon::parse($auditDate)->translatedFormat('d F Y') }}
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="saveAudit" 
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-200 transition flex items-center gap-2 active:scale-95"
                    >
                        <span>💾</span>
                        <span>SIMPAN HASIL AUDIT HARIAN</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- History Audit Modal -->
    @if($showHistoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 animate-in fade-in duration-150">
            <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col border border-slate-200" @click.away="$wire.closeHistoryModal()">
                <!-- Modal Header -->
                <div class="p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex justify-between items-center">
                    <div class="flex items-center space-x-2.5">
                        <span class="text-xl">📜</span>
                        <div>
                            <h3 class="text-lg font-black tracking-tight">Riwayat Audit Harian Material</h3>
                            <p class="text-xs text-slate-300">Daftar rekap audit yang pernah disimpan di sistem</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeHistoryModal" class="text-slate-400 hover:text-white font-black text-xl p-1">✕</button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto space-y-3 divide-y divide-slate-100 flex-grow">
                    @forelse($auditHistoryList as $hist)
                        <div class="pt-3 first:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-black text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                        {{ $hist->audit_code }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-600">
                                        📅 {{ Carbon\Carbon::parse($hist->audit_date)->translatedFormat('d M Y') }}
                                    </span>
                                    @if($hist->warehouse)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                            {{ $hist->warehouse->whse_name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-1">
                                    Auditor: <span class="font-semibold text-slate-700">{{ $hist->auditor_name }}</span>
                                    @if($hist->notes)
                                        &bull; <span class="italic text-slate-400">"{{ $hist->notes }}"</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <div class="text-right text-xs">
                                    <span class="text-emerald-700 font-bold">✓ {{ $hist->matched_items }} Sesuai</span>
                                    <span class="text-slate-300">|</span>
                                    <span class="text-amber-700 font-bold">⚠️ {{ $hist->discrepant_items }} Selisih</span>
                                    <span class="text-slate-400 block text-[10px]">Total {{ $hist->total_items }} item</span>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="$set('auditDate', '{{ $hist->audit_date->toDateString() }}'); closeHistoryModal()" 
                                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition"
                                >
                                    Buka Data
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-sm">
                            Belum ada riwayat audit yang tersimpan.
                        </div>
                    @endforelse
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" wire:click="closeHistoryModal" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
