<div class="p-6 bg-gray-50 min-h-screen space-y-6">
    <!-- Flash Notification Feedback -->
    @if($flashSuccess)
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 shadow-xs animate-in fade-in slide-in-from-top-2 duration-200">
            <div class="flex items-start gap-3">
                <span class="text-emerald-600 text-lg">✅</span>
                <div>
                    <div class="text-xs font-black text-emerald-900">Perubahan Berhasil Dikirim ke SAP</div>
                    <div class="text-xs text-emerald-700 font-semibold mt-0.5">{{ $flashSuccess }}</div>
                    <div class="mt-2">
                        <a href="{{ route('spk.bom-changes.preview-payload') }}" target="_blank" 
                           class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition shadow-2xs">
                            <span>📦</span>
                            <span>Buka Page Payload JSON (Tab Baru) &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
            <button type="button" wire:click="$set('flashSuccess', null)" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold">✕</button>
        </div>
    @endif

    @if($flashError)
        <div class="p-4 bg-red-50 border border-red-200 rounded-2xl flex items-start justify-between gap-3 shadow-xs animate-in fade-in slide-in-from-top-2 duration-200">
            <div class="flex items-start gap-3">
                <span class="text-red-600 text-lg">⛔</span>
                <div>
                    <div class="text-xs font-black text-red-900">Gagal Memproses Permintaan ke SAP</div>
                    <div class="text-xs text-red-700 font-semibold mt-0.5">{{ $flashError }}</div>
                    <div class="mt-2">
                        <a href="{{ route('spk.bom-changes.preview-payload') }}" target="_blank" 
                           class="inline-flex items-center gap-1 px-3 py-1 bg-red-700 hover:bg-red-800 text-white rounded-lg text-xs font-bold transition shadow-2xs">
                            <span>📦</span>
                            <span>Buka Page Payload JSON yang Dicoba &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
            <button type="button" wire:click="$set('flashError', null)" class="text-red-500 hover:text-red-800 text-sm font-bold">✕</button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-md shadow-indigo-200">
                    📋
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>SPK BOM Changes</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-black bg-amber-100 text-amber-900 border border-amber-300">
                            🟡 Khusus SPK Planned (P)
                        </span>
                    </h1>
                    <p class="text-xs text-gray-500 font-semibold mt-1">
                        Daftar SPK berstatus Planned (P) &amp; formula leaf BOM non-WIP. SPK yang sudah Released (R) dikunci dan tidak dapat diubah.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" wire:click="openHistoryModal(null)" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition inline-flex items-center gap-1.5 shadow-sm shadow-indigo-200 cursor-pointer">
                <span>📜</span>
                <span>Log Riwayat Perubahan Material</span>
                @if($totalBomChanges > 0)
                    <span class="ml-1 px-1.5 py-0.2 bg-white/20 rounded-full text-[10px]">{{ $totalBomChanges }}</span>
                @endif
            </button>
            <a href="{{ route('master-bom.index') }}" class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>📦</span>
                <span>Master BOM</span>
            </a>
            <a href="{{ route('spk.changes.index') }}" class="px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl transition inline-flex items-center gap-1.5 border border-indigo-200 shadow-2xs">
                <span>🔄</span>
                <span>Audit Sync SPK</span>
            </a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-2xs border border-amber-100 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-black text-xl">
                🟡
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Planned (P)</span>
                <span class="text-2xl font-black text-amber-700">{{ number_format($totalPlanned) }}</span>
                <span class="text-[10px] text-gray-400 font-medium block">Dapat diubah resepnya</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-2xs border border-emerald-100 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-xl">
                ✓
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Planned Ada BOM</span>
                <span class="text-2xl font-black text-emerald-600">{{ number_format($totalPlannedWithBom) }}</span>
                <span class="text-[10px] text-emerald-600/80 font-medium block">Resep tersedia</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-2xs border border-rose-100 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-xl">
                ⚠️
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Planned Tanpa BOM</span>
                <span class="text-2xl font-black text-rose-600">{{ number_format($totalPlannedWithoutBom) }}</span>
                <span class="text-[10px] text-rose-600/80 font-medium block">Belum ada formula</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-2xs border border-purple-100 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-black text-xl">
                ⚡
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Planned Volume</span>
                <span class="text-2xl font-black text-purple-700">{{ number_format($totalPlannedPcs) }} <span class="text-xs text-gray-400 font-bold">Pcs</span></span>
                <span class="text-[10px] text-purple-600/80 font-medium block">Akumulasi SPK</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-2xs border border-blue-100 flex items-center gap-4 cursor-pointer hover:bg-blue-50/30 transition" wire:click="openHistoryModal(null)" title="Lihat semua riwayat perubahan">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-xl">
                📜
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Log Perubahan</span>
                <span class="text-2xl font-black text-blue-700">{{ number_format($totalBomChanges) }}</span>
                <span class="text-[10px] text-blue-600/80 font-semibold block flex items-center gap-0.5">
                    <span>Lihat riwayat</span>
                    <span>→</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div class="flex items-center gap-3 flex-1 flex-wrap">
            <!-- Search Bar -->
            <div class="relative flex-1 min-w-[240px]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. SPK, Item Code, Part Name..."
                    class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">🔍</span>
                @if(!empty($search))
                    <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs font-bold">✕</button>
                @endif
            </div>

            <!-- Status SPK Indicator (Fixed Planned Only) -->
            <div class="px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl text-xs font-bold text-amber-800 flex items-center gap-1.5 shrink-0 shadow-2xs">
                <span>🟡</span>
                <span>Status: Planned (P) Saja</span>
            </div>

            <!-- Ketersediaan BOM Filter -->
            <select wire:model.live="bomFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-3 py-2 text-gray-700 outline-none">
                <option value="all">Semua Status BOM</option>
                <option value="with_bom">✓ Hanya yang Punya BOM</option>
                <option value="without_bom">⚠️ Belum Ada BOM</option>
            </select>

            <!-- Per Page -->
            <select wire:model.live="perPage" class="bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-2.5 py-2 text-gray-600 outline-none">
                <option value="15">15 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
        </div>

        <!-- Accordion Batch Actions -->
        @php
            $currentPageItems = $spks->map(fn($s) => [
                'spk_number'  => $s->spk_number,
                'item_code'   => $s->item_code,
                'planned_qty' => (float)$s->planned_quantity,
            ])->toArray();
        @endphp
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" wire:click="expandAll({{ json_encode($currentPageItems) }})"
                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-extrabold transition cursor-pointer border border-indigo-200 shadow-2xs flex items-center gap-1">
                <span>▼</span>
                <span>Buka Semua</span>
            </button>
            <button type="button" wire:click="collapseAll"
                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-extrabold transition cursor-pointer shadow-2xs flex items-center gap-1">
                <span>▲</span>
                <span>Tutup Semua</span>
            </button>
        </div>
    </div>

    <!-- Main Table: SPK Master with Expandable Leaf BOM -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between text-xs font-black text-gray-700">
            <div class="flex items-center gap-2">
                <span>📦</span>
                <span>Daftar SPK Master Aktif &amp; Komponen Bahan Baku Bersih (Non-WIP)</span>
            </div>
            <span class="text-gray-500 font-bold">
                Menampilkan {{ number_format($spks->total()) }} SPK
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-100/80 text-[10px] font-black uppercase text-gray-500 tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 text-center w-10">#</th>
                        <th class="py-3 px-4">No. SPK</th>
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Deskripsi Produk (Part Name)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Planned Qty</th>
                        <th class="py-3 px-4 text-right">Completed Qty</th>
                        <th class="py-3 px-4 text-center">Due Date</th>
                        <th class="py-3 px-4 text-center">Riwayat Perubahan</th>
                        <th class="py-3 px-4 text-center">Status BOM</th>
                        <th class="py-3 px-4 text-center w-36">Aksi / Rincian BOM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium">
                    @forelse($spks as $index => $spk)
                        @php
                            $isOpen = isset($openSpks[$spk->spk_number]);
                            $bomData = $spkBomCache[$spk->spk_number] ?? null;
                            $hasBom = !empty($spk->fgHeader) || \App\Models\MasterBom::where('parent_item', $spk->item_code)->exists();
                            $plannedQty = (float) $spk->planned_quantity;
                            $changeCount = $spk->bom_change_logs_count ?? 0;
                        @endphp

                        <!-- SPK Master Row -->
                        <tr class="hover:bg-indigo-50/30 transition {{ $isOpen ? 'bg-indigo-50/20' : '' }}">
                            <td class="py-3.5 px-3 text-center text-gray-400 font-mono text-[11px]">
                                {{ $spks->firstItem() + $index }}
                            </td>

                            <!-- No. SPK -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" 
                                        wire:click="toggleSpk('{{ $spk->spk_number }}', '{{ $spk->item_code }}', {{ $plannedQty }})" 
                                        class="font-mono font-black text-indigo-700 hover:text-indigo-900 hover:underline cursor-pointer text-xs">
                                        {{ $spk->spk_number }}
                                    </button>
                                </div>
                            </td>

                            <!-- Item Code -->
                            <td class="py-3.5 px-4 font-mono font-black text-gray-900">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $spk->item_code }}</span>
                                    <button type="button" @click="navigator.clipboard.writeText('{{ $spk->item_code }}')" class="text-gray-300 hover:text-gray-600 transition" title="Salin kode">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Part Name / Deskripsi -->
                            <td class="py-3.5 px-4 text-gray-700 max-w-xs font-semibold">
                                {{ optional($spk->masterItem)->item_name ?: (optional($spk->fgHeader)->fg_description ?: '-') }}
                            </td>

                            <!-- Status SPK -->
                            <td class="py-3.5 px-4 text-center">
                                @if($spk->production_status === 'R')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        🟢 Released (R)
                                    </span>
                                @elseif($spk->production_status === 'P')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                        🟡 Planned (P)
                                    </span>
                                @elseif($spk->production_status === 'C')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-700 border border-gray-200">
                                        ⚪ Closed (C)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                        {{ $spk->production_status ?: '-' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Planned Qty -->
                            <td class="py-3.5 px-4 text-right font-black text-indigo-700 text-sm">
                                {{ number_format($spk->planned_quantity) }}
                                <span class="text-[10px] text-gray-400 font-semibold">pcs</span>
                            </td>

                            <!-- Completed Qty -->
                            <td class="py-3.5 px-4 text-right font-bold text-gray-700">
                                {{ number_format($spk->completed_quantity) }}
                                <span class="text-[10px] text-gray-400 font-normal">pcs</span>
                            </td>

                            <!-- Due Date -->
                            <td class="py-3.5 px-4 text-center font-semibold text-gray-500 text-[11px]">
                                {{ $spk->due_date ? \Carbon\Carbon::parse($spk->due_date)->format('d/m/Y') : '-' }}
                            </td>

                            <!-- Riwayat Perubahan Badge / Button -->
                            <td class="py-3.5 px-4 text-center">
                                @if($changeCount > 0)
                                    <button type="button" wire:click="openHistoryModal('{{ $spk->spk_number }}')"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 inline-flex items-center gap-1 shadow-2xs transition cursor-pointer"
                                        title="Klik untuk melihat riwayat perubahan">
                                        <span>📜</span>
                                        <span>{{ $changeCount }} Perubahan</span>
                                    </button>
                                @else
                                    <span class="text-[10px] text-gray-400 font-semibold">Belum ada</span>
                                @endif
                            </td>

                            <!-- Status BOM Indicator -->
                            <td class="py-3.5 px-4 text-center">
                                @if($hasBom)
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 shadow-2xs">
                                        <span>✓</span>
                                        <span>BOM Tersedia</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                                        <span>⚠️</span>
                                        <span>Belum Ada BOM</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Action Toggle -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                        wire:click="toggleSpk('{{ $spk->spk_number }}', '{{ $spk->item_code }}', {{ $plannedQty }})"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs {{ $isOpen ? 'bg-indigo-600 text-white' : 'bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-transparent' }}"
                                        title="{{ $isOpen ? 'Tutup rincian BOM' : 'Buka rincian leaf BOM (non-WIP)' }}">
                                        <span>{{ $isOpen ? '▲' : '▼' }}</span>
                                        <span>{{ $isOpen ? 'Tutup' : 'Buka BOM' }}</span>
                                    </button>

                                    <button type="button"
                                        wire:click="openModal('{{ $spk->spk_number }}', '{{ $spk->item_code }}', {{ $plannedQty }}, '{{ addslashes(optional($spk->masterItem)->item_name ?: '') }}', '{{ $spk->production_status }}')"
                                        class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition cursor-pointer shadow-2xs"
                                        title="Buka Popup Modal">
                                        🔍
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- ACCORDION CONTENT: LEAF BOM MATERIAL LIST (NON-WIP) -->
                        @if($isOpen)
                            <tr class="bg-gray-50/90 border-y-2 border-indigo-100">
                                <td colspan="11" class="p-4 sm:p-6">
                                    <div class="bg-white rounded-xl p-5 shadow-xs border border-indigo-100 space-y-4">
                                        @php
                                            $isEditingThisSpk = ($this->editingSpk === $spk->spk_number);
                                        @endphp

                                        <!-- Header Accordion Info & Action Bar -->
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                                            <div class="flex items-center gap-3">
                                                <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center font-black text-sm">
                                                    🌿
                                                </span>
                                                <div>
                                                    <div class="text-xs font-black text-gray-800 flex items-center gap-2">
                                                        <span>Resep Leaf Material SPK:</span>
                                                        <span class="font-mono bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded border border-indigo-200">{{ $spk->spk_number }}</span>
                                                        <span class="font-mono font-bold text-gray-900">[{{ $spk->item_code }}]</span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 font-semibold mt-0.5">
                                                        {{ optional($spk->masterItem)->item_name ?: '-' }}
                                                        @if(!empty($bomData['project_code']))
                                                            <span class="ml-2 px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-bold">🏷️ Project: {{ $bomData['project_code'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 flex-wrap">
                                                @php
                                                    $isSpkPlanned = in_array($spk->production_status, ['P', 'Planned', 'PLANNED'], true);
                                                @endphp
                                                @if($isSpkPlanned)
                                                    @if(!$isEditingThisSpk)
                                                        <!-- Action: Masuk Mode Edit Resep -->
                                                        <button type="button" wire:click="startEditMode('{{ $spk->spk_number }}')"
                                                            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                                            <span>✏️</span>
                                                            <span>Masuk Mode Edit Resep</span>
                                                        </button>
                                                    @else
                                                        <!-- Action: Tambah Material Baru ke SPK (HANYA MUNCUL DI DALAM MODE EDIT) -->
                                                        <button type="button" wire:click="openAddMaterialModal('{{ $spk->spk_number }}', {{ $plannedQty }})"
                                                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                                            <span>+</span>
                                                            <span>Tambah Material</span>
                                                        </button>
                                                    @endif
                                                @else
                                                    <!-- SPK Bukan Planned (Terkunci) -->
                                                    <span class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-lg text-xs font-bold border border-gray-200 flex items-center gap-1 shadow-2xs cursor-not-allowed" title="SPK sudah Released/Closed dan tidak boleh diubah">
                                                        <span>🔒</span>
                                                        <span>Terkunci ({{ $spk->production_status }})</span>
                                                    </span>
                                                @endif

                                                <!-- Action: Lihat Riwayat SPK ini -->
                                                <button type="button" wire:click="openHistoryModal('{{ $spk->spk_number }}')"
                                                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer">
                                                    <span>📜</span>
                                                    <span>Riwayat ({{ $changeCount }})</span>
                                                </button>

                                                <div class="text-right pl-2 border-l border-gray-200">
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Planned Target:</span>
                                                    <span class="font-mono font-black text-indigo-700 text-xs">{{ number_format($plannedQty) }} PCS</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- BANNER MODE EDIT AKTIF (BATCH STAGING) -->
                                        @if($isEditingThisSpk)
                                            <div class="p-3.5 bg-amber-50 border-2 border-amber-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs animate-in fade-in duration-200">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-9 h-9 rounded-lg bg-amber-200 text-amber-950 flex items-center justify-center text-lg font-black shrink-0">
                                                        ✏️
                                                    </span>
                                                    <div>
                                                        <div class="text-xs font-black text-amber-950 flex items-center gap-2">
                                                            <span>MODE EDIT RESEP AKTIF</span>
                                                            <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[10px] font-black border border-amber-300">
                                                                {{ count($stagedLines) }} Perubahan di Draft
                                                            </span>
                                                        </div>
                                                        <div class="text-[11px] text-amber-800 font-medium mt-0.5">
                                                            Perubahan Anda tersimpan sementara di draft. Klik <strong>Selesai &amp; Kirim ke SAP</strong> untuk mengirim seluruh perubahan dalam satu payload.
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <button type="button" wire:click="openAddMaterialModal('{{ $spk->spk_number }}', {{ $plannedQty }})"
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer">
                                                        <span>+</span>
                                                        <span>Tambah Material</span>
                                                    </button>
                                                    <button type="button" wire:click="cancelEditMode" 
                                                        class="px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs">
                                                        ✕ Batal Edit
                                                    </button>
                                                    <button type="button" wire:click="previewDraftPayload"
                                                        @if(empty($stagedLines)) disabled @endif
                                                        class="px-3 py-1.5 {{ empty($stagedLines) ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' : 'bg-slate-800 hover:bg-slate-900 text-white cursor-pointer shadow-sm' }} rounded-lg text-xs font-bold transition flex items-center gap-1">
                                                        <span>👁️</span>
                                                        <span>Preview JSON</span>
                                                    </button>
                                                    <button type="button" wire:click="submitBatchChanges" 
                                                        @if(empty($stagedLines)) disabled @endif
                                                        class="px-4 py-1.5 {{ empty($stagedLines) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-md shadow-emerald-200' }} rounded-lg text-xs font-black transition flex items-center gap-1.5">
                                                        <span wire:loading.remove wire:target="submitBatchChanges">🚀 Selesai &amp; Kirim ke SAP ({{ count($stagedLines) }})</span>
                                                        <span wire:loading wire:target="submitBatchChanges">Mengirim ke SAP...</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Leaf BOM Table -->
                                        @if(empty($bomData['materials']) && (!$isEditingThisSpk || empty($stagedLines)))
                                            <div class="py-8 text-center text-amber-600 bg-amber-50/50 rounded-xl border border-amber-200">
                                                <div class="text-2xl mb-1">⚠️</div>
                                                <div class="text-xs font-bold">Belum ada formula BOM terdaftar untuk item code <span class="font-mono font-black">{{ $spk->item_code }}</span>.</div>
                                                <div class="text-[11px] text-amber-600/80 mt-1">
                                                    @if(!$isEditingThisSpk)
                                                        Klik tombol <strong>✏️ Masuk Mode Edit Resep</strong> di atas untuk menambahkan material ke SPK ini.
                                                    @else
                                                        Anda dapat menambahkan material langsung dengan tombol <strong>+ Tambah Material</strong> di atas.
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs text-left border border-gray-100 rounded-lg overflow-hidden">
                                                    <thead class="bg-gray-50 text-[10px] font-black uppercase text-gray-500 tracking-wider border-b border-gray-100">
                                                        <tr>
                                                            <th class="py-2.5 px-3 text-center w-8">#</th>
                                                            <th class="py-2.5 px-4">Item Code Material</th>
                                                            <th class="py-2.5 px-4">Deskripsi Material</th>
                                                            <th class="py-2.5 px-3 text-center">Kategori / Status</th>
                                                            <th class="py-2.5 px-4 text-right">Unit Qty</th>
                                                            <th class="py-2.5 px-4 text-right bg-indigo-50/60 font-black text-indigo-900">Planned Material Quantity</th>
                                                            <th class="py-2.5 px-3 text-center">Satuan</th>
                                                            @if($isEditingThisSpk)
                                                                <th class="py-2.5 px-4 text-center w-36">Aksi (Draft / SAP)</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 font-medium">
                                                        <!-- 1. Baris Material Asli (Existing BOM) -->
                                                        @foreach($bomData['materials'] ?? [] as $mIndex => $mat)
                                                            @php
                                                                $matCode = $mat['component_item'];
                                                                $isStaged = $isEditingThisSpk && isset($stagedLines[$matCode]);
                                                                $stagedItem = $isStaged ? $stagedLines[$matCode] : null;
                                                                $isDeleted = $isStaged && !empty($stagedItem['delete']);
                                                                $isUpdated = $isStaged && empty($stagedItem['delete']) && ($stagedItem['action_type'] === 'UPDATE_QTY' || isset($stagedItem['plan_qty']));
                                                            @endphp

                                                            <tr class="transition {{ $isDeleted ? 'bg-red-50/60 border-l-4 border-red-500 opacity-75' : ($isUpdated ? 'bg-amber-50/70 border-l-4 border-amber-500' : 'hover:bg-gray-50') }}">
                                                                <td class="py-2.5 px-3 text-center text-gray-400 font-mono text-[11px]">
                                                                    {{ $mIndex + 1 }}
                                                                </td>

                                                                <!-- Item Code Material -->
                                                                <td class="py-2.5 px-4">
                                                                    <div class="flex items-center gap-1.5">
                                                                        <span class="font-mono font-bold {{ $isDeleted ? 'line-through text-red-900' : 'text-gray-900' }}">{{ $mat['component_item'] }}</span>
                                                                        <button type="button" @click="navigator.clipboard.writeText('{{ $mat['component_item'] }}')" class="text-gray-300 hover:text-gray-600" title="Salin kode material">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                                        </button>
                                                                    </div>
                                                                </td>

                                                                <!-- Deskripsi Material -->
                                                                <td class="py-2.5 px-4 {{ $isDeleted ? 'line-through text-red-800' : 'text-gray-700' }}">
                                                                    {{ $mat['component_description'] }}
                                                                </td>

                                                                <!-- Kategori / Status -->
                                                                <td class="py-2.5 px-3 text-center">
                                                                    @if($isDeleted)
                                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-red-100 text-red-800 border border-red-200">
                                                                            🗑️ Dihapus (Draft)
                                                                        </span>
                                                                    @elseif($isUpdated)
                                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                                                            ✏️ Diubah (Draft)
                                                                        </span>
                                                                    @else
                                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black border inline-flex items-center gap-1 {{ $mat['category_badge'] }}">
                                                                            <span>{{ $mat['category_icon'] }}</span>
                                                                            <span>{{ $mat['category_label'] }}</span>
                                                                        </span>
                                                                    @endif
                                                                </td>

                                                                <!-- Unit Qty -->
                                                                <td class="py-2.5 px-4 text-right font-mono font-bold text-gray-800">
                                                                    @if($isUpdated && isset($stagedItem['base_qty']) && $stagedItem['base_qty'] !== null)
                                                                        <div class="flex flex-col items-end">
                                                                            <span class="line-through text-gray-400 text-[10px]">{{ rtrim(rtrim(number_format($mat['unit_qty'], 6, '.', ''), '0'), '.') }}</span>
                                                                            <span class="font-mono font-black text-amber-900 text-xs">{{ rtrim(rtrim(number_format($stagedItem['base_qty'], 6, '.', ''), '0'), '.') }}</span>
                                                                        </div>
                                                                    @else
                                                                        <span class="{{ $isDeleted ? 'line-through text-red-400' : '' }}">
                                                                            {{ rtrim(rtrim(number_format($mat['unit_qty'], 6, '.', ''), '0'), '.') }}
                                                                        </span>
                                                                    @endif
                                                                </td>

                                                                <!-- Planned Material Quantity -->
                                                                <td class="py-2.5 px-4 text-right font-mono text-sm {{ $isDeleted ? 'bg-red-50/40 text-red-600 line-through' : ($isUpdated ? 'bg-amber-100/50 text-amber-900 font-black' : 'bg-indigo-50/40 font-black text-indigo-700') }}">
                                                                    @if($isUpdated)
                                                                        <div class="flex flex-col items-end">
                                                                            <span class="line-through text-gray-400 text-[10px]">{{ number_format($mat['planned_material_qty'], 4) }}</span>
                                                                            <span class="font-black text-amber-900 text-sm">{{ number_format($stagedItem['plan_qty'], 4) }}</span>
                                                                        </div>
                                                                    @else
                                                                        {{ number_format($mat['planned_material_qty'], 4) }}
                                                                    @endif
                                                                </td>

                                                                <!-- UoM -->
                                                                <td class="py-2.5 px-3 text-center font-mono font-bold text-gray-600 text-xs">
                                                                    {{ $mat['uom'] }}
                                                                </td>

                                                                <!-- Actions: Draft Undo vs Edit, Ganti, Hapus (HANYA MUNCUL DI DALAM MODE EDIT) -->
                                                                @if($isEditingThisSpk)
                                                                    <td class="py-2.5 px-4 text-center">
                                                                        @if($isDeleted)
                                                                            <button type="button" wire:click="unstageItem('{{ $matCode }}')"
                                                                                class="px-2.5 py-1 rounded bg-white hover:bg-red-100 text-red-700 border border-red-300 text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                                                                title="Batalkan penghapusan material ini dari draft">
                                                                                ↺ Batal Hapus
                                                                            </button>
                                                                        @elseif($isUpdated)
                                                                            <div class="inline-flex items-center gap-1">
                                                                                <button type="button" 
                                                                                    wire:click="openEditQtyModal('{{ $spk->spk_number }}', '{{ $mat['component_item'] }}', '{{ addslashes($mat['component_description']) }}', {{ (float)$stagedItem['plan_qty'] }}, {{ (float)($stagedItem['base_qty'] ?? $mat['unit_qty']) }}, {{ $plannedQty }})"
                                                                                    class="p-1 rounded bg-amber-200 hover:bg-amber-300 text-amber-900 transition shadow-2xs"
                                                                                    title="Edit ulang quantity draft">
                                                                                    ✏️
                                                                                </button>
                                                                                <button type="button" wire:click="unstageItem('{{ $matCode }}')"
                                                                                    class="px-2 py-1 rounded bg-white hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                                                                    title="Kembalikan quantity ke semula">
                                                                                    ↺ Undo
                                                                                </button>
                                                                            </div>
                                                                        @else
                                                                            <div class="inline-flex items-center gap-1">
                                                                                <!-- 1. Edit Plan Qty -->
                                                                                <button type="button" 
                                                                                    wire:click="openEditQtyModal('{{ $spk->spk_number }}', '{{ $mat['component_item'] }}', '{{ addslashes($mat['component_description']) }}', {{ (float)$mat['planned_material_qty'] }}, {{ (float)$mat['unit_qty'] }}, {{ $plannedQty }})"
                                                                                    class="p-1 rounded bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white transition shadow-2xs cursor-pointer"
                                                                                    title="Ubah Quantity Material (Simpan ke Draft)">
                                                                                    ✏️
                                                                                </button>

                                                                                <!-- 2. Ganti Material (Replace) -->
                                                                                <button type="button"
                                                                                    wire:click="openReplaceModal('{{ $spk->spk_number }}', '{{ $mat['component_item'] }}', '{{ addslashes($mat['component_description']) }}', {{ $plannedQty }})"
                                                                                    class="p-1 rounded bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white transition shadow-2xs cursor-pointer"
                                                                                    title="Ganti material ini dengan material lain (Simpan ke Draft)">
                                                                                    🔄
                                                                                </button>

                                                                                <!-- 3. Hapus Material (Delete) -->
                                                                                <button type="button"
                                                                                    wire:click="openDeleteModal('{{ $spk->spk_number }}', '{{ $mat['component_item'] }}', '{{ addslashes($mat['component_description']) }}')"
                                                                                    class="p-1 rounded bg-red-50 hover:bg-red-600 text-red-700 hover:text-white transition shadow-2xs cursor-pointer"
                                                                                    title="Tandai material ini untuk dihapus (Simpan ke Draft)">
                                                                                    🗑️
                                                                                </button>
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach

                                                        <!-- 2. Baris Material Baru Ditambahkan (Staged New Lines) -->
                                                        @if($isEditingThisSpk)
                                                            @foreach($stagedLines as $sCode => $sItem)
                                                                @if(!empty($sItem['is_new']))
                                                                    <tr class="bg-emerald-50/70 border-l-4 border-emerald-500 hover:bg-emerald-50 transition">
                                                                        <td class="py-2.5 px-3 text-center text-emerald-700 font-bold text-xs">
                                                                            ➕
                                                                        </td>
                                                                        <td class="py-2.5 px-4">
                                                                            <span class="font-mono font-black text-emerald-950">{{ $sItem['item_code'] }}</span>
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-gray-800 font-semibold">
                                                                            {{ $sItem['item_name'] ?: '-' }}
                                                                            @if(!empty($sItem['replaced_item_code']))
                                                                                <span class="text-[10px] text-emerald-700 font-bold block">(Pengganti: {{ $sItem['replaced_item_code'] }})</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="py-2.5 px-3 text-center">
                                                                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                                                ➕ {{ $sItem['action_type'] === 'REPLACE_MATERIAL' ? 'Pengganti Baru (Draft)' : 'Material Baru (Draft)' }}
                                                                            </span>
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-900">
                                                                            {{ $sItem['base_qty'] !== null ? rtrim(rtrim(number_format($sItem['base_qty'], 6, '.', ''), '0'), '.') : '-' }}
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-right font-mono font-black text-emerald-700 text-sm bg-emerald-100/50">
                                                                            {{ number_format($sItem['plan_qty'], 4) }}
                                                                        </td>
                                                                        <td class="py-2.5 px-3 text-center font-mono font-bold text-gray-600 text-xs">
                                                                            PCS
                                                                        </td>
                                                                        <td class="py-2.5 px-4 text-center">
                                                                            <button type="button" wire:click="unstageItem('{{ $sItem['item_code'] }}')"
                                                                                class="px-2.5 py-1 bg-white hover:bg-emerald-100 text-emerald-700 border border-emerald-300 rounded text-[10px] font-bold shadow-2xs transition cursor-pointer"
                                                                                title="Batalkan penambahan material ini">
                                                                                ✕ Batal Tambah
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Summary Footer -->
                                            <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-500 font-semibold gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full {{ $isEditingThisSpk ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500' }}"></span>
                                                    @if($isEditingThisSpk)
                                                        <span class="text-amber-800 font-bold">Mode Edit Aktif: Semua perubahan tersimpan sementara di draft. Klik 'Selesai &amp; Kirim ke SAP' untuk mengirimkan sekaligus.</span>
                                                    @else
                                                        <span>Menampilkan {{ count($bomData['materials'] ?? []) }} jenis bahan baku non-WIP. Klik 'Masuk Mode Edit Resep' untuk mengubah resep SPK.</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-400">
                                                    Rumus: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-700 font-bold">Planned Material Qty = Unit Qty × Planned SPK</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-gray-400">
                                <div class="text-3xl mb-2">🔍</div>
                                <div class="text-sm font-bold">Tidak ada data SPK yang sesuai kriteria pencarian.</div>
                                <div class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau reset filter status.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            {{ $spks->links() }}
        </div>
    </div>

    <!-- MODAL 1: EDIT PLAN QTY MATERIAL -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-blue-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">✏️</span>
                        <div>
                            <h3 class="text-base font-black">Ubah Quantity Material SPK</h3>
                            <div class="text-xs text-blue-100 font-semibold mt-0.5">Simpan perubahan quantity ke draft edit</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAllModals" class="text-blue-100 hover:text-white font-bold text-lg cursor-pointer">✕</button>
                </div>

                <form wire:submit.prevent="submitEditQty" class="p-6 space-y-4">
                    <div class="bg-gray-50 p-4 rounded-xl space-y-2 border border-gray-100">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">No. SPK:</span>
                            <span class="font-mono font-black text-gray-800">{{ $editSpkNumber }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Item Code:</span>
                            <span class="font-mono font-black text-indigo-700">{{ $editItemCode }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Deskripsi:</span>
                            <span class="font-medium text-gray-700 text-right">{{ $editItemDescription }}</span>
                        </div>
                        <div class="flex justify-between text-xs pt-1 border-t border-gray-200">
                            <span class="text-gray-500 font-bold">Target Produksi SPK:</span>
                            <span class="font-mono font-bold text-blue-700">{{ number_format($editSpkPlannedQty) }} PCS</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500 font-bold">Planned Qty Saat Ini:</span>
                            <span class="font-mono font-bold text-gray-700">{{ number_format((float)$editOldPlanQty, 4) }}</span>
                        </div>
                    </div>

                    <div x-data="{
                            base: @entangle('editBaseQty'),
                            target: {{ (float) $editSpkPlannedQty }},
                            plan: @entangle('editPlanQty'),
                            calcPlan() {
                                let clean = String(this.base || '').replace(',', '.').trim();
                                let v = parseFloat(clean);
                                if (!isNaN(v) && v >= 0 && this.target > 0) {
                                    let res = v * this.target;
                                    this.plan = (Math.round(res * 10000) / 10000).toFixed(4).replace(/\.?0+$/, '');
                                    $wire.set('editPlanQty', this.plan);
                                }
                            }
                         }"
                         class="space-y-4">

                        <!-- 1. BASE QTY (EDITABLE) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-black text-gray-800 uppercase tracking-wider">
                                    Base Qty (Unit Qty per 1 FG) <span class="text-red-500">*</span>
                                </label>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    ✏️ Dapat Diedit
                                </span>
                            </div>
                            <input type="text" 
                                inputmode="decimal" 
                                x-model="base"
                                @input="calcPlan()"
                                wire:model.blur="editBaseQty" 
                                placeholder="Masukkan Base Qty baru (contoh: 0.15 atau 0.000009)..."
                                class="w-full px-3.5 py-2.5 bg-white border-2 border-blue-400 focus:border-blue-600 rounded-xl text-base font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none shadow-xs">
                            @error('editBaseQty') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                            <div class="text-[11px] text-gray-500 mt-1">
                                💡 Kebutuhan pemakaian material untuk <strong>1 unit FG</strong>.
                            </div>
                        </div>

                        <!-- 2. PLANNED QUANTITY (LOCKED / READONLY) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-black text-gray-700 uppercase tracking-wider">
                                    Planned Material Quantity (Kebutuhan Total SPK)
                                </label>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-200 text-gray-700 border border-gray-300">
                                    🔒 Terkunci (Otomatis Dihitung)
                                </span>
                            </div>
                            <div class="relative">
                                <input type="text" 
                                    readonly 
                                    tabindex="-1"
                                    :value="plan"
                                    wire:model="editPlanQty" 
                                    class="w-full px-3.5 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 cursor-not-allowed select-none outline-none">
                                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-400 font-bold text-xs">
                                    TOTAL
                                </div>
                            </div>
                            @error('editPlanQty') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                            <div class="text-[11px] text-gray-500 mt-1 flex items-center gap-1 font-medium">
                                <span>🔒 Terkunci: Dihitung otomatis dari <strong>Base Qty</strong> × <strong>Target SPK ({{ number_format($editSpkPlannedQty) }} PCS)</strong>.</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200 text-[11px] text-blue-800">
                        ℹ️ <strong>Mode Edit Resep:</strong> Perubahan disimpan sementara di draft. Klik <strong>'Selesai &amp; Kirim ke SAP'</strong> di halaman utama untuk mengirim seluruh perubahan sekaligus.
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeAllModals" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl transition shadow-md shadow-blue-200 flex items-center gap-1.5 cursor-pointer">
                            <span wire:loading.remove wire:target="submitEditQty">✓ Simpan ke Draft</span>
                            <span wire:loading wire:target="submitEditQty">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: TAMBAH MATERIAL BARU -->
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-emerald-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">➕</span>
                        <div>
                            <h3 class="text-base font-black">Tambah Material Baru ke SPK</h3>
                            <div class="text-xs text-emerald-100 font-semibold mt-0.5">Kirim material baru ke detail Production Order SAP</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAllModals" class="text-emerald-100 hover:text-white font-bold text-lg cursor-pointer">✕</button>
                </div>

                <form wire:submit.prevent="submitAddMaterial" class="p-6 space-y-4">
                    <div class="bg-emerald-50/70 p-3.5 rounded-2xl flex justify-between items-center border border-emerald-200">
                        <div>
                            <span class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider block">No. SPK</span>
                            <span class="font-mono font-black text-gray-900 text-sm">{{ $addSpkNumber }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider block">Target Produksi SPK</span>
                            <span class="font-mono font-black text-emerald-700 text-sm">{{ number_format($addSpkPlannedQty) }} <span class="text-xs font-semibold text-gray-500">PCS</span></span>
                        </div>
                    </div>

                    <!-- Input Item Code with Autocomplete Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                            <span>Kode Item Material (Item Code) <span class="text-red-500">*</span></span>
                            @if(!empty($addItemName))
                                <span class="text-[11px] text-emerald-600 font-bold normal-case truncate max-w-[200px]" title="{{ $addItemName }}">
                                    ✓ {{ $addItemName }}
                                </span>
                            @endif
                        </label>

                        <div class="relative">
                            <input type="text" 
                                wire:model.live.debounce.250ms="addItemCode" 
                                @focus="open = true" 
                                @input="open = true"
                                placeholder="Ketik kode atau nama material..."
                                class="w-full pl-3.5 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none uppercase font-mono">
                            @if(!empty($addItemCode))
                                <button type="button" wire:click="$set('addItemCode', ''); $set('addItemName', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs font-bold cursor-pointer">
                                    ✕
                                </button>
                            @endif
                        </div>

                        <!-- Dropdown Suggestion List -->
                        @if($showAddDropdown && !empty($this->addItemSuggestions))
                            <div x-show="open" class="absolute z-60 left-0 right-0 mt-1 max-h-52 overflow-y-auto bg-white rounded-2xl shadow-xl border border-gray-200 divide-y divide-gray-100">
                                @foreach($this->addItemSuggestions as $sug)
                                    <button type="button" 
                                        wire:click="selectAddMaterial('{{ $sug['item_code'] }}', '{{ addslashes($sug['item_name'] ?? '') }}')"
                                        @click="open = false"
                                        class="w-full text-left px-3.5 py-2.5 hover:bg-emerald-50/70 transition flex items-center justify-between gap-2 cursor-pointer group">
                                        <div class="truncate">
                                            <div class="font-mono font-black text-xs text-gray-900 group-hover:text-emerald-700">{{ $sug['item_code'] }}</div>
                                            <div class="text-[11px] text-gray-500 truncate font-medium">{{ $sug['item_name'] ?: '-' }}</div>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-bold group-hover:text-emerald-600 shrink-0">Pilih ↵</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @error('addItemCode') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Input Base Qty & Auto-Calculated Plan Qty Strip -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                         x-data="{
                            base: @entangle('addBaseQty'),
                            target: {{ (float) $addSpkPlannedQty }},
                            get planCalculated() {
                                let v = parseFloat(String(this.base || '').replace(',', '.'));
                                return (!isNaN(v) && v > 0) ? (v * this.target).toFixed(4) : '-';
                            }
                         }">
                        <!-- 1. BASE QTY (INPUT UTAMA OLEH USER) -->
                        <div>
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                                <span>Base Qty (Per Unit FG)</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                inputmode="decimal" 
                                x-model="base"
                                wire:model.blur="addBaseQty" 
                                placeholder="Contoh: 0.15"
                                class="w-full px-3.5 py-2.5 bg-white border-2 border-emerald-500 rounded-xl text-sm font-black text-gray-900 focus:ring-2 focus:ring-emerald-400 outline-none font-mono shadow-xs">
                            <span class="text-[10px] text-gray-500 font-semibold mt-1 block">
                                Kebutuhan per 1 unit FG
                            </span>
                            @error('addBaseQty') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- 2. PLAN QTY (OTOMATIS DIHITUNG DARI SPK) -->
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1 flex items-center justify-between">
                                <span>Plan Qty (Otomatis)</span>
                                <span class="text-[9px] px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded font-bold">Auto SPK</span>
                            </label>
                            <div class="px-3.5 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm font-mono font-black text-gray-800 flex items-center justify-between">
                                <span class="font-mono font-black text-gray-800 text-sm" x-text="planCalculated"></span>
                                <span class="text-[11px] text-gray-400 font-semibold">PCS/KG</span>
                            </div>
                            <span class="text-[10px] text-emerald-700 font-bold mt-1 block">
                                = Base Qty × {{ number_format($addSpkPlannedQty) }} SPK
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1">
                            Warehouse / Gudang (Opsional)
                        </label>
                        <input type="text" wire:model="addWarehouse" placeholder="Contoh: WH-RM02 atau GUA"
                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none uppercase font-mono">
                    </div>

                    <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200 text-[11px] text-emerald-800">
                        ℹ️ <strong>Mode Edit Resep:</strong> Material baru akan dicatat dalam draft dan dikirim ke SAP saat Anda menekan tombol <strong>'Selesai &amp; Kirim ke SAP'</strong>.
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeAllModals" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl transition shadow-md shadow-emerald-200 flex items-center gap-1.5 cursor-pointer">
                            <span wire:loading.remove wire:target="submitAddMaterial">✓ Tambah ke Draft</span>
                            <span wire:loading wire:target="submitAddMaterial">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: HAPUS MATERIAL (DELETE: TRUE) -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-red-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">🗑️</span>
                        <div>
                            <h3 class="text-base font-black">Hapus Material dari SPK</h3>
                            <div class="text-xs text-red-100 font-semibold mt-0.5">Tandai material untuk dihapus di draft</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAllModals" class="text-red-100 hover:text-white font-bold text-lg cursor-pointer">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="text-center py-2">
                        <div class="w-14 h-14 bg-red-50 text-red-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-3">
                            ⚠️
                        </div>
                        <p class="text-sm font-bold text-gray-800">
                            Apakah Anda yakin ingin menandai material berikut untuk dihapus dari resep SPK ini?
                        </p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl space-y-2 border border-gray-100">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">No. SPK:</span>
                            <span class="font-mono font-black text-gray-800">{{ $deleteSpkNumber }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Item Code:</span>
                            <span class="font-mono font-black text-red-700">{{ $deleteItemCode }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Deskripsi:</span>
                            <span class="font-medium text-gray-700 text-right">{{ $deleteItemDescription }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-[11px] text-red-800">
                        🛑 <strong>Mode Edit Resep:</strong> Material ini akan ditandai hapus (<code class="font-bold font-mono">delete: true</code>) di draft dan dikirim ke SAP saat Anda menekan tombol <strong>'Selesai &amp; Kirim ke SAP'</strong>.
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeAllModals" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" wire:click="submitDeleteMaterial" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-xl transition shadow-md shadow-red-200 flex items-center gap-1.5 cursor-pointer">
                            <span wire:loading.remove wire:target="submitDeleteMaterial">🗑️ Tandai Hapus di Draft</span>
                            <span wire:loading wire:target="submitDeleteMaterial">Menandai...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 4: GANTI MATERIAL (REPLACE DENGAN DROPDOWN & AUTO PLAN QTY) -->
    @if($showReplaceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-amber-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">🔄</span>
                        <div>
                            <h3 class="text-base font-black">Ganti Material (Replace)</h3>
                            <div class="text-xs text-amber-100 font-semibold mt-0.5">Simpan penggantian material ke draft edit</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAllModals" class="text-amber-100 hover:text-white font-bold text-lg cursor-pointer">✕</button>
                </div>

                <form wire:submit.prevent="submitReplaceMaterial" class="p-6 space-y-4">
                    <!-- Material Lama -->
                    <div class="bg-red-50/70 p-4 rounded-xl space-y-1.5 border border-red-200">
                        <span class="text-[10px] font-black text-red-700 uppercase tracking-wider block">1. Material Lama yang Akan Dihapus (delete: true):</span>
                        <div class="flex justify-between text-xs">
                            <span class="font-mono font-black text-red-900">{{ $replaceOldItemCode }}</span>
                            <span class="text-gray-600 font-semibold">{{ $replaceOldItemDescription }}</span>
                        </div>
                    </div>

                    <!-- Material Pengganti Baru -->
                    <div class="space-y-3 pt-1">
                        <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block">2. Material Baru Pengganti:</span>

                        <!-- Input Item Code with Autocomplete Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                                <span>Kode Material Baru (New Item Code) <span class="text-red-500">*</span></span>
                                @if(!empty($replaceNewItemName))
                                    <span class="text-[11px] text-emerald-600 font-bold normal-case truncate max-w-[200px]" title="{{ $replaceNewItemName }}">
                                        ✓ {{ $replaceNewItemName }}
                                    </span>
                                @endif
                            </label>

                            <div class="relative">
                                <input type="text" 
                                    wire:model.live.debounce.250ms="replaceNewItemCode" 
                                    @focus="open = true" 
                                    @input="open = true"
                                    placeholder="Ketik kode atau nama material..."
                                    class="w-full pl-3.5 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:ring-2 focus:ring-amber-500 outline-none uppercase font-mono">
                                @if(!empty($replaceNewItemCode))
                                    <button type="button" wire:click="$set('replaceNewItemCode', ''); $set('replaceNewItemName', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs font-bold cursor-pointer">
                                        ✕
                                    </button>
                                @endif
                            </div>

                            @if($showReplaceDropdown && !empty($this->replaceItemSuggestions))
                                <div x-show="open" class="absolute z-60 left-0 right-0 mt-1 max-h-52 overflow-y-auto bg-white rounded-2xl shadow-xl border border-gray-200 divide-y divide-gray-100">
                                    @foreach($this->replaceItemSuggestions as $sug)
                                        <button type="button" 
                                            wire:click="selectReplaceMaterial('{{ $sug['item_code'] }}', '{{ addslashes($sug['item_name'] ?? '') }}')"
                                            @click="open = false"
                                            class="w-full text-left px-3.5 py-2.5 hover:bg-amber-50/70 transition flex items-center justify-between gap-2 cursor-pointer group">
                                            <div class="truncate">
                                                <div class="font-mono font-black text-xs text-gray-900 group-hover:text-amber-700">{{ $sug['item_code'] }}</div>
                                                <div class="text-[11px] text-gray-500 truncate font-medium">{{ $sug['item_name'] ?: '-' }}</div>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-bold group-hover:text-amber-600 shrink-0">Pilih ↵</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @error('replaceNewItemCode') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Grid Base Qty & Auto Plan Qty -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                             x-data="{
                                base: @entangle('replaceBaseQty'),
                                target: {{ (float) $replaceSpkPlannedQty }},
                                get planCalculated() {
                                    let v = parseFloat(String(this.base || '').replace(',', '.'));
                                    return (!isNaN(v) && v > 0) ? (v * this.target).toFixed(4) : '-';
                                }
                             }">
                            <div>
                                <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>Base Qty (Per Unit FG)</span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                    inputmode="decimal" 
                                    x-model="base"
                                    wire:model.blur="replaceBaseQty" 
                                    placeholder="Contoh: 0.15"
                                    class="w-full px-3.5 py-2.5 bg-white border-2 border-amber-500 rounded-xl text-sm font-black text-gray-900 focus:ring-2 focus:ring-amber-400 outline-none font-mono shadow-xs">
                                <span class="text-[10px] text-gray-500 font-semibold mt-1 block">Kebutuhan per unit</span>
                                @error('replaceBaseQty') <span class="text-[11px] text-red-600 font-bold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1 flex items-center justify-between">
                                    <span>Plan Qty (Otomatis)</span>
                                    <span class="text-[9px] px-1.5 py-0.2 bg-amber-100 text-amber-800 rounded font-bold">Auto SPK</span>
                                </label>
                                <div class="px-3.5 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-sm font-mono font-black text-gray-800 flex items-center justify-between">
                                    <span class="font-mono font-black text-gray-800 text-sm" x-text="planCalculated"></span>
                                    <span class="text-[11px] text-gray-400 font-semibold">PCS/KG</span>
                                </div>
                                <span class="text-[10px] text-amber-700 font-bold mt-1 block">
                                    = Base Qty × {{ number_format($replaceSpkPlannedQty) }} SPK
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-1">
                                Warehouse (Opsional)
                            </label>
                            <input type="text" wire:model="replaceWarehouse" placeholder="Contoh: WH-RM02"
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:ring-2 focus:ring-amber-500 outline-none uppercase font-mono">
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800">
                        ℹ️ <strong>Mode Edit Resep:</strong> Penggantian material (<code class="font-mono">delete: true</code> item lama &amp; material baru) disimpan ke draft dan dikirim ke SAP saat Anda menekan tombol <strong>'Selesai &amp; Kirim ke SAP'</strong>.
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeAllModals" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl transition shadow-md shadow-amber-200 flex items-center gap-1.5 cursor-pointer">
                            <span wire:loading.remove wire:target="submitReplaceMaterial">🔄 Ganti di Draft</span>
                            <span wire:loading wire:target="submitReplaceMaterial">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 5: RIWAYAT PERUBAHAN MATERIAL (TIMELINE AUDIT LOG) -->
    @if($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-indigo-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">
                            📜
                        </div>
                        <div>
                            <h3 class="text-base font-black">Riwayat Audit Perubahan Material SPK</h3>
                            <div class="text-xs text-indigo-100 font-semibold mt-0.5">
                                @if($historySpkNumber)
                                    Filter khusus SPK: <strong class="font-mono text-white">{{ $historySpkNumber }}</strong>
                                @else
                                    Seluruh riwayat perubahan material di sistem
                                @endif
                            </div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeAllModals" class="text-indigo-100 hover:text-white font-bold text-xl">✕</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    @if(empty($historyLogs))
                        <div class="py-12 text-center text-gray-400">
                            <div class="text-3xl mb-2">📜</div>
                            <div class="text-sm font-bold">Belum ada riwayat perubahan material yang tercatat.</div>
                            <div class="text-xs text-gray-400 mt-1">Setiap penambahan, pengeditan, atau penghapusan material via API SAP akan otomatis tercatat di sini.</div>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($historyLogs as $log)
                                <div class="bg-gray-50 border border-gray-100 hover:border-indigo-200 rounded-2xl p-4 transition space-y-2">
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <div class="flex items-center gap-2">
                                            @if($log['action_type'] === 'ADD_MATERIAL')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    ➕ TAMBAH MATERIAL
                                                </span>
                                            @elseif($log['action_type'] === 'UPDATE_QTY')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                                    ✏️ UBAH QUANTITY
                                                </span>
                                            @elseif($log['action_type'] === 'DELETE_MATERIAL')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-800 border border-red-200">
                                                    🗑️ HAPUS MATERIAL
                                                </span>
                                            @elseif($log['action_type'] === 'REPLACE_MATERIAL')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                                    🔄 GANTI MATERIAL
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                                    {{ $log['action_type'] }}
                                                </span>
                                            @endif

                                            <span class="font-mono font-black text-gray-800 text-xs">SPK: {{ $log['spk_number'] }}</span>
                                        </div>

                                        <div class="flex items-center gap-2 text-[11px] text-gray-500 font-semibold">
                                            <span>👤 {{ $log['created_by_name'] ?: 'System' }}</span>
                                            <span>•</span>
                                            <span title="{{ $log['created_at'] }}">
                                                📅 {{ \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y H:i:s') }}
                                                ({{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }})
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Detail Baris Item -->
                                    <div class="bg-white p-3 rounded-xl border border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs">
                                        <div>
                                            <div class="font-mono font-bold text-gray-900 flex items-center gap-2">
                                                <span>{{ $log['item_code'] }}</span>
                                                @if(!empty($log['item_name']))
                                                    <span class="text-gray-500 font-normal">({{ $log['item_name'] }})</span>
                                                @endif
                                            </div>
                                            @if(!empty($log['replaced_item_code']))
                                                <div class="text-[11px] text-amber-700 font-semibold mt-0.5">
                                                    Replaced with / from: <code class="font-mono font-bold">{{ $log['replaced_item_code'] }}</code>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="text-right flex items-center gap-3">
                                            @if($log['old_plan_qty'] !== null && $log['plan_qty'] !== null)
                                                <div class="text-xs">
                                                    <span class="text-gray-400 font-mono line-through">{{ number_format($log['old_plan_qty'], 2) }}</span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="font-mono font-black text-indigo-700">{{ number_format($log['plan_qty'], 2) }}</span>
                                                </div>
                                            @elseif($log['plan_qty'] !== null)
                                                <div class="font-mono font-black text-emerald-700 text-xs">
                                                    Qty: {{ number_format($log['plan_qty'], 2) }}
                                                </div>
                                            @endif

                                            @if(!empty($log['warehouse']))
                                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-[10px] font-mono font-bold">
                                                    WH: {{ $log['warehouse'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Status Response SAP -->
                                    <div class="flex items-center justify-between text-[11px] pt-1">
                                        <div class="flex items-center gap-1.5">
                                            @if($log['status'] === 'SUCCESS')
                                                <span class="text-emerald-600 font-bold">✓ SAP Success:</span>
                                            @else
                                                <span class="text-red-600 font-bold">⛔ SAP Failed:</span>
                                            @endif
                                            <span class="text-gray-600 font-medium">{{ $log['message'] ?: 'Update processed' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeAllModals" class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white font-bold text-xs rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 6: DRILL-DOWN PREVIEW -->
    @if($showModal && !empty($modalSpk))
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
                <div class="p-5 bg-indigo-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">
                            📋
                        </div>
                        <div>
                            <h3 class="text-base font-black tracking-tight">Rincian Kebutuhan Bahan SPK</h3>
                            <div class="text-xs text-indigo-100 font-medium mt-0.5 flex items-center gap-2">
                                <span>No. SPK: <strong class="font-mono text-white">{{ $modalSpk['spk_number'] }}</strong></span>
                                <span>•</span>
                                <span>Item Code: <strong class="font-mono text-white">{{ $modalSpk['item_code'] }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-indigo-100 hover:text-white font-bold text-xl">✕</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Part Name:</span>
                            <span class="text-sm font-black text-gray-800">{{ $modalSpk['part_name'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Planned Target:</span>
                            <span class="text-sm font-mono font-black text-indigo-700">{{ number_format($modalSpk['planned_qty']) }} PCS</span>
                        </div>
                    </div>

                    @if(!empty($modalBom['materials']))
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50 text-[10px] font-black uppercase text-gray-500 border-b border-gray-100">
                                    <tr>
                                        <th class="py-2.5 px-3 text-center">#</th>
                                        <th class="py-2.5 px-4">Item Code</th>
                                        <th class="py-2.5 px-4">Deskripsi</th>
                                        <th class="py-2.5 px-3 text-center">Kategori</th>
                                        <th class="py-2.5 px-4 text-right">Unit Qty</th>
                                        <th class="py-2.5 px-4 text-right bg-indigo-50/50">Planned Qty</th>
                                        <th class="py-2.5 px-3 text-center">UoM</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($modalBom['materials'] as $idx => $m)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2.5 px-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="py-2.5 px-4 font-mono font-bold">{{ $m['component_item'] }}</td>
                                            <td class="py-2.5 px-4">{{ $m['component_description'] }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-black border inline-flex items-center gap-1 {{ $m['category_badge'] }}">
                                                    <span>{{ $m['category_icon'] }}</span>
                                                    <span>{{ $m['category_label'] }}</span>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-4 text-right font-mono">{{ rtrim(rtrim(number_format($m['unit_qty'], 6, '.', ''), '0'), '.') }}</td>
                                            <td class="py-2.5 px-4 text-right font-mono font-black text-indigo-700 bg-indigo-50/30">{{ number_format($m['planned_material_qty'], 4) }}</td>
                                            <td class="py-2.5 px-3 text-center font-mono">{{ $m['uom'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeModal" class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white font-bold text-xs rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-payload-preview', (event) => {
                const targetUrl = event.url || event[0]?.url;
                if (targetUrl) {
                    window.open(targetUrl, '_blank');
                }
            });
        });
    </script>
</div>
