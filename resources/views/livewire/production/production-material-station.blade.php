<div class="space-y-6" style="font-family: 'IBM Plex Sans', sans-serif;">
    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-black text-2xl shadow-md shadow-amber-500/20">
                📦
            </div>
            <div>
                <div class="text-[11px] font-bold tracking-widest text-gray-400 uppercase">Station Produksi &bull; Material Flow</div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">Verifikasi & Tracking Material Produksi</h1>
                <p class="text-xs text-gray-500 mt-0.5">Konfirmasi penerimaan material dari Store dan catat pemakaian mesin untuk tracking stok lantai produksi.</p>
            </div>
        </div>

        {{-- Quick KPI Pills --}}
        <div class="flex items-center gap-2 flex-wrap">
            <div class="bg-blue-50 border border-blue-200 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-900 flex items-center gap-2">
                <span>🏭 Stok Produksi:</span>
                <span class="font-mono font-black text-blue-700 text-sm">{{ number_format($currentFloorStockKg, 2) }} KG</span>
            </div>
            @if($pendingStoreCount > 0)
                <div class="bg-amber-50 border border-amber-300 px-3.5 py-2 rounded-xl text-xs font-bold text-amber-900 flex items-center gap-2 animate-pulse">
                    <span>⚠️ Menunggu Verifikasi:</span>
                    <span class="font-mono font-black text-amber-700 text-sm">{{ $pendingStoreCount }} Batch</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Main Navigation Tabs --}}
    <div class="bg-white p-2 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-2 overflow-x-auto">
        <button type="button" wire:click="switchTab('receive')"
                class="flex-1 min-w-[200px] py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 {{ $activeTab === 'receive' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
            <span>📥</span>
            <span>1. Penerimaan dari Store</span>
            @if($pendingStoreCount > 0)
                <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] font-mono {{ $activeTab === 'receive' ? 'bg-white text-amber-600 font-bold' : 'bg-amber-100 text-amber-800' }}">
                    {{ $pendingStoreCount }}
                </span>
            @endif
        </button>

        <button type="button" wire:click="switchTab('usage')"
                class="flex-1 min-w-[200px] py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 {{ $activeTab === 'usage' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
            <span>⚙️</span>
            <span>2. Input Pemakaian Mesin</span>
        </button>

        <button type="button" wire:click="switchTab('stock')"
                class="flex-1 min-w-[200px] py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 {{ $activeTab === 'stock' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
            <span>📊</span>
            <span>3. Stok & Riwayat Pemakaian</span>
        </button>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 1: PENERIMAAN MATERIAL DARI STORE (VERIFIKASI)       --}}
    {{-- ======================================================== --}}
    @if($activeTab === 'receive')
        <div class="space-y-4">
            {{-- Quick Barcode Scanner Strip --}}
            <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 rounded-2xl border border-amber-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        📷
                    </div>
                    <div>
                        <div class="text-xs font-black text-gray-900">Quick Scanner Penerimaan Material</div>
                        <div class="text-[11px] text-gray-500">Scan barcode Outgoing (OUT-...) atau Pallet (MPLT-...) lalu tekan Enter untuk verifikasi instan.</div>
                    </div>
                </div>

                <div class="w-full md:w-96">
                    <div class="relative">
                        <input type="text"
                               wire:model="barcodeInput"
                               wire:keydown.enter.prevent="quickScanReceive"
                               placeholder="Scan Barcode / Tekan Enter..."
                               class="w-full text-xs font-mono font-bold py-2.5 pl-9 pr-20 border-2 border-amber-300 rounded-xl bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition shadow-inner">
                        <span class="absolute left-3 top-3 text-gray-400 text-xs">🔍</span>
                        <button type="button"
                                wire:click="quickScanReceive"
                                class="absolute right-1.5 top-1.5 px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold rounded-lg transition shadow-xs">
                            Verifikasi
                        </button>
                    </div>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                {{-- Status Filter Pills --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button type="button" wire:click="$set('statusFilterIncoming', 'PENDING')"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilterIncoming === 'PENDING' ? 'bg-amber-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        <span>🟡 Menunggu Verifikasi</span>
                        @if($pendingStoreCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $statusFilterIncoming === 'PENDING' ? 'bg-white text-amber-600' : 'bg-amber-200 text-amber-800' }}">
                                {{ $pendingStoreCount }}
                            </span>
                        @endif
                    </button>

                    <button type="button" wire:click="$set('statusFilterIncoming', 'RECEIVED')"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilterIncoming === 'RECEIVED' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        <span>🟢 Sudah Diterima</span>
                    </button>

                    <button type="button" wire:click="$set('statusFilterIncoming', 'ALL')"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $statusFilterIncoming === 'ALL' ? 'bg-gray-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        <span>Semua Status</span>
                    </button>
                </div>

                {{-- Search & Date Filters --}}
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <div class="flex items-center gap-1 text-xs">
                        <input type="date" wire:model.live="startDateIncoming" class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50">
                        <span class="text-gray-400">-</span>
                        <input type="date" wire:model.live="endDateIncoming" class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50">
                    </div>

                    <div class="relative w-full sm:w-56">
                        <input type="text" wire:model.live.debounce.250ms="searchIncoming"
                               placeholder="Cari Kode, Material, Pallet..."
                               class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-amber-500 transition">
                        <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>
                </div>
            </div>

            {{-- Table Incoming Material --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4">Tgl & Jam Store</th>
                                <th class="py-3 px-4">Kode Outgoing</th>
                                <th class="py-3 px-4">Pallet & Rak Asal</th>
                                <th class="py-3 px-4">Material (Kode & Nama)</th>
                                <th class="py-3 px-4 text-right">Qty Keluar</th>
                                <th class="py-3 px-4 text-center">Status Verifikasi</th>
                                <th class="py-3 px-4">PIC Penerima</th>
                                <th class="py-3 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($incomingRequests as $out)
                                <tr class="hover:bg-amber-50/20 transition-colors {{ $out->received_status === 'PENDING' ? 'bg-amber-50/10' : '' }}">
                                    {{-- Tanggal Outgoing --}}
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-800">{{ $out->created_at ? $out->created_at->format('d M Y') : $out->outgoing_date->format('d M Y') }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $out->created_at ? $out->created_at->format('H:i') . ' WIB' : '-' }}</div>
                                    </td>

                                    {{-- Outgoing Code --}}
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-[11px]">{{ $out->outgoing_code }}</span>
                                    </td>

                                    {{-- Pallet & Slot Rak --}}
                                    <td class="py-3 px-4">
                                        <div class="font-mono text-xs text-gray-800 font-semibold">{{ $out->pallet_id }}</div>
                                        <div class="text-[10px] text-gray-500 mt-0.5">
                                            Rak: <span class="font-mono font-bold text-indigo-600">{{ $out->position?->rack?->rack_name ?: '-' }}</span>
                                            ({{ $out->position?->position_code ?: '-' }})
                                        </div>
                                    </td>

                                    {{-- Material --}}
                                    <td class="py-3 px-4">
                                        <div class="font-mono font-bold text-gray-900">{{ $out->item_code }}</div>
                                        <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ $out->material?->item_description ?: '—' }}</div>
                                    </td>

                                    {{-- Qty Taken --}}
                                    <td class="py-3 px-4 text-right font-mono font-black text-rose-600 text-sm">
                                        {{ number_format($out->qty_taken, 2) }} <span class="text-[10px] text-gray-400 font-normal">{{ $out->uom ?: 'KG' }}</span>
                                    </td>

                                    {{-- Status Verifikasi --}}
                                    <td class="py-3 px-4 text-center">
                                        @if($out->received_status === 'RECEIVED')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <span>✓ Diterima</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                                <span>⏳ Menunggu</span>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- PIC Penerima --}}
                                    <td class="py-3 px-4">
                                        @if($out->received_status === 'RECEIVED')
                                            <div class="font-bold text-gray-800">{{ $out->received_by_name ?: '-' }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $out->received_at ? $out->received_at->format('d/m H:i') : '-' }}</div>
                                            @if($out->received_notes)
                                                <div class="text-[10px] text-gray-500 italic truncate max-w-xs mt-0.5">"{{ $out->received_notes }}"</div>
                                            @endif
                                        @else
                                            <span class="text-gray-400 italic text-[11px]">Belum diverifikasi</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="py-3 px-4 text-center">
                                        @if($out->received_status === 'PENDING')
                                            <button type="button" wire:click="openVerifyModal({{ $out->id }})"
                                                    class="w-full px-3 py-1.5 text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition shadow-xs flex items-center justify-center gap-1">
                                                <span>✓</span>
                                                <span>Verifikasi</span>
                                            </button>
                                        @else
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" wire:click="selectBatchForUsage({{ $out->id }})"
                                                        class="px-2.5 py-1 text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition"
                                                        title="Catat pemakaian material ini ke mesin">
                                                    ⚙️ Pakai
                                                </button>
                                                <button type="button" wire:click="revertReceipt({{ $out->id }})"
                                                        wire:confirm="Batalkan penerimaan material ini kembali ke status Pending?"
                                                        class="px-2 py-1 text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition"
                                                        title="Batalkan status diterima">
                                                    ↺
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-gray-400">
                                        <div class="text-2xl mb-1">📭</div>
                                        <div class="font-bold text-sm">Tidak ada data kiriman material dari Store.</div>
                                        <div class="text-xs mt-0.5">Semua material tujuan produksi sudah terverifikasi atau tidak ada data yang cocok dengan filter.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($incomingRequests->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $incomingRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- TAB 2: INPUT PEMAKAIAN MATERIAL (LOG USAGE)              --}}
    {{-- ======================================================== --}}
    @if($activeTab === 'usage')
        <div class="max-w-4xl mx-auto space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-black text-gray-900 flex items-center gap-2">
                        <span>⚙️</span>
                        <span>Catat Pemakaian Material di Lantai Produksi</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Input kuantitas material yang akan digunakan ke mesin injection / proses produksi agar sisa stok lantai produksi terpotong otomatis.
                    </p>
                </div>

                <form wire:submit.prevent="saveMaterialUsage" class="space-y-4">
                    {{-- 1. Pilih Batch Material yang Tersedia di Lantai Produksi (Searchable Combobox) --}}
                    <div x-data="{
                        open: false,
                        search: '',
                        highlightIndex: 0,
                        selectedId: @entangle('selectedBatchId').live,
                        batches: @js($availableBatches),
                        get filteredBatches() {
                            if (!this.search || this.search.trim() === '') {
                                return this.batches;
                            }
                            const term = this.search.toLowerCase().trim();
                            return this.batches.filter(b => {
                                const code = (b.item_code || '').toLowerCase();
                                const name = (b.item_name || '').toLowerCase();
                                const pallet = (b.pallet_id || '').toLowerCase();
                                const outgoing = (b.outgoing_code || '').toLowerCase();
                                return code.includes(term) || name.includes(term) || pallet.includes(term) || outgoing.includes(term);
                            });
                        },
                        selectBatch(batch) {
                            if (!batch) return;
                            this.selectedId = batch.outgoing_id;
                            this.search = '[' + batch.item_code + '] ' + batch.item_name;
                            this.open = false;
                            $wire.set('selectedBatchId', batch.outgoing_id);
                        },
                        clearSelection() {
                            this.selectedId = null;
                            this.search = '';
                            this.highlightIndex = 0;
                            this.open = true;
                            $wire.set('selectedBatchId', null);
                            this.$nextTick(() => { this.$refs.searchInput.focus(); });
                        },
                        toggleDropdown() {
                            this.open = !this.open;
                            if (this.open) {
                                this.$nextTick(() => { this.$refs.searchInput.focus(); });
                            }
                        },
                        init() {
                            this.syncLabel();
                            $watch('selectedId', () => { this.syncLabel(); });
                            $watch('batches', () => { this.syncLabel(); });
                        },
                        syncLabel() {
                            if (!this.selectedId) {
                                return;
                            }
                            const found = this.batches.find(b => b.outgoing_id == this.selectedId);
                            if (found) {
                                this.search = '[' + found.item_code + '] ' + found.item_name;
                            }
                        }
                    }"
                    class="relative"
                    @click.outside="open = false"
                    @keydown.escape="open = false">
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider flex items-center justify-between">
                            <span>Pilih Batch Material (Stok Produksi Tersedia) <span class="text-rose-500">*</span></span>
                            <span class="text-[10px] text-gray-400 font-normal normal-case">Bisa diketik: cari kode, nama material, no pallet, atau ref outgoing</span>
                        </label>

                        {{-- Search Input with Icons --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-sm">
                                🔍
                            </div>
                            <input type="text"
                                   x-ref="searchInput"
                                   x-model="search"
                                   @focus="open = true; $refs.searchInput.select()"
                                   @input="open = true; highlightIndex = 0"
                                   @keydown.down.prevent="open = true; highlightIndex = Math.min(highlightIndex + 1, Math.max(0, filteredBatches.length - 1))"
                                   @keydown.up.prevent="open = true; highlightIndex = Math.max(highlightIndex - 1, 0)"
                                   @keydown.enter.prevent="if (open && filteredBatches.length > 0) { selectBatch(filteredBatches[highlightIndex]); }"
                                   placeholder="Ketik untuk mencari batch material (cth: ABS, YRSNABZ, MPLT-...)"
                                   autocomplete="off"
                                   class="w-full text-xs font-bold py-2.5 pl-9 pr-16 border rounded-xl transition shadow-xs focus:ring-2 focus:ring-indigo-500/20 outline-none"
                                   :class="selectedId ? 'border-indigo-300 bg-indigo-50/20 text-indigo-950 font-mono' : 'border-gray-200 bg-white text-gray-900'">

                            {{-- Action buttons inside input --}}
                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                                <button type="button"
                                        x-show="search.length > 0 || selectedId"
                                        @click="clearSelection()"
                                        class="p-1 text-gray-400 hover:text-rose-500 rounded-md transition text-xs font-bold"
                                        title="Hapus pilihan">
                                    ✕
                                </button>
                                <button type="button"
                                        @click="toggleDropdown()"
                                        class="p-1 text-gray-400 hover:text-indigo-600 rounded-md transition text-xs"
                                        title="Buka / tutup daftar batch">
                                    <span :class="open ? 'rotate-180' : ''" class="inline-block transition-transform duration-200">▾</span>
                                </button>
                            </div>
                        </div>

                        {{-- Floating Dropdown Suggestion List --}}
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute z-50 left-0 right-0 mt-1 max-h-72 overflow-y-auto bg-white rounded-2xl shadow-xl border border-gray-200 divide-y divide-gray-100">

                            {{-- Header Info / Count --}}
                            <div class="px-3.5 py-2 bg-gray-50/80 text-[10px] font-bold text-gray-400 uppercase tracking-wider flex items-center justify-between sticky top-0 backdrop-blur-xs z-10 border-b border-gray-100">
                                <span>Batch Tersedia di Lantai Produksi</span>
                                <span class="font-mono" x-text="filteredBatches.length + ' item ditemukan'"></span>
                            </div>

                            {{-- Item Suggestions --}}
                            <template x-for="(b, idx) in filteredBatches" :key="b.outgoing_id">
                                <button type="button"
                                        @click="selectBatch(b)"
                                        @mouseenter="highlightIndex = idx"
                                        class="w-full text-left px-3.5 py-2.5 transition flex items-center justify-between gap-3 cursor-pointer group"
                                        :class="{
                                            'bg-indigo-600 text-white': highlightIndex === idx,
                                            'bg-indigo-50/50': selectedId == b.outgoing_id && highlightIndex !== idx,
                                            'hover:bg-indigo-50/60': highlightIndex !== idx
                                        }">
                                    <div class="truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-black text-xs"
                                                  :class="highlightIndex === idx ? 'text-white' : 'text-indigo-950 group-hover:text-indigo-700'"
                                                  x-text="'[' + b.item_code + ']'"></span>
                                            <span class="text-xs font-semibold truncate"
                                                  :class="highlightIndex === idx ? 'text-indigo-100' : 'text-gray-800'"
                                                  x-text="b.item_name"></span>
                                        </div>
                                        <div class="text-[10px] font-mono mt-0.5 flex items-center gap-2"
                                             :class="highlightIndex === idx ? 'text-indigo-200' : 'text-gray-400'">
                                            <span x-text="'Pallet: ' + b.pallet_id"></span>
                                            <span>&bull;</span>
                                            <span x-text="'Ref: ' + b.outgoing_code"></span>
                                            <span>&bull;</span>
                                            <span x-text="'Masuk: ' + (b.received_at || '-')"></span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-mono font-black border"
                                              :class="highlightIndex === idx
                                                  ? 'bg-white/20 text-white border-white/30'
                                                  : 'bg-emerald-50 text-emerald-700 border-emerald-200 group-hover:bg-emerald-100'"
                                              x-text="'Sisa: ' + Number(b.remaining_qty).toFixed(2) + ' ' + b.uom"></span>
                                    </div>
                                </button>
                            </template>

                            {{-- Empty State when filter doesn't match --}}
                            <div x-show="filteredBatches.length === 0" class="p-6 text-center text-gray-400">
                                <div class="text-xl mb-1">🔍</div>
                                <div class="font-bold text-xs text-gray-600">Tidak ada batch material yang cocok</div>
                                <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Kata kunci: &quot;' + search + '&quot;'"></div>
                            </div>
                        </div>

                        @error('selectedBatchId') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Highlight Info Batch Terpilih --}}
                    @if($selectedBatchInfo)
                        <div class="bg-gradient-to-r from-indigo-50/80 to-blue-50/60 p-4 rounded-xl border border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="font-mono font-black text-indigo-950 text-sm">{{ $selectedBatchInfo['item_code'] }}</div>
                                <div class="text-indigo-800 font-semibold text-xs mt-0.5">{{ $selectedBatchInfo['item_name'] }}</div>
                                <div class="text-[11px] text-gray-500 font-mono mt-1">
                                    Ref: {{ $selectedBatchInfo['outgoing_code'] }} &bull; Pallet: {{ $selectedBatchInfo['pallet_id'] }} &bull; Masuk: {{ $selectedBatchInfo['received_at'] }}
                                </div>
                            </div>
                            <div class="text-right bg-white px-4 py-2 rounded-xl border border-indigo-200 shadow-xs shrink-0">
                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Sisa Stok di Produksi</div>
                                <div class="text-xl font-black font-mono text-indigo-600">
                                    {{ number_format($selectedBatchInfo['remaining_qty'], 2) }} <span class="text-xs font-normal text-gray-500">{{ $selectedBatchInfo['uom'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 2. Detail Pemakaian (Mesin, SPK, Qty, Tujuan) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nomor Mesin (Searchable & Typeable) --}}
                        <div x-data="{
                            open: false,
                            machines: @js($machines),
                            get filteredMachines() {
                                const term = ($wire.machineNo || '').toLowerCase().trim();
                                if (!term) return this.machines;
                                return this.machines.filter(m => m.toLowerCase().includes(term));
                            },
                            selectMachine(m) {
                                $wire.set('machineNo', m);
                                this.open = false;
                            }
                        }"
                        class="relative"
                        @click.outside="open = false"
                        @keydown.escape="open = false">
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider flex items-center justify-between">
                                <span>Nomor Mesin <span class="text-rose-500">*</span></span>
                                <span class="text-[10px] text-gray-400 font-normal normal-case">Ketik atau pilih</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       wire:model.live.debounce.150ms="machineNo"
                                       @focus="open = true"
                                       @input="open = true"
                                       placeholder="Pilih atau Ketik No. Mesin (cth: M-05)..."
                                       class="w-full text-xs font-bold py-2.5 pl-3 pr-8 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition uppercase font-mono">
                                <button type="button"
                                        @click="open = !open"
                                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-indigo-600 text-xs cursor-pointer">
                                    <span :class="open ? 'rotate-180' : ''" class="inline-block transition-transform duration-200">▾</span>
                                </button>
                            </div>

                            {{-- Floating Machine List --}}
                            <div x-show="open && filteredMachines.length > 0"
                                 class="absolute z-40 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white rounded-xl shadow-xl border border-gray-200 divide-y divide-gray-100">
                                <template x-for="m in filteredMachines" :key="m">
                                    <button type="button"
                                            @click="selectMachine(m)"
                                            class="w-full text-left px-3.5 py-2 text-xs font-mono font-bold text-gray-800 hover:bg-indigo-50 hover:text-indigo-700 transition flex items-center justify-between cursor-pointer">
                                        <span x-text="m"></span>
                                        <span class="text-[10px] text-gray-400 font-sans font-normal">Pilih ↵</span>
                                    </button>
                                </template>
                            </div>
                            @error('machineNo') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        {{-- Nomor SPK (Opsional) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                                Nomor SPK <span class="text-gray-400 font-normal">(Opsional)</span>
                            </label>
                            <input type="text"
                                   wire:model="spkCode"
                                   placeholder="Nomor SPK (cth: 26034536)..."
                                   class="w-full text-xs font-mono font-bold py-2.5 px-3 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition">
                            @error('spkCode') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        {{-- Kuantitas Pemakaian (KG) --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                                Kuantitas Pemakaian (KG) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       step="0.01"
                                       wire:model="qtyUsed"
                                       placeholder="Kuantitas (kg)..."
                                       max="{{ $selectedBatchInfo ? $selectedBatchInfo['remaining_qty'] : '' }}"
                                       class="w-full text-sm font-mono font-black py-2.5 pl-3 pr-12 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition">
                                <span class="absolute right-3 top-3 text-xs font-bold text-gray-400">KG</span>
                            </div>
                            @if($selectedBatchInfo)
                                <div class="text-[10px] text-gray-400 mt-1 flex justify-between items-center">
                                    <span>Maksimal: {{ number_format($selectedBatchInfo['remaining_qty'], 2) }} KG</span>
                                    <button type="button" wire:click="$set('qtyUsed', {{ $selectedBatchInfo['remaining_qty'] }})"
                                            class="text-indigo-600 font-bold hover:underline">
                                        Gunakan Semua
                                    </button>
                                </div>
                            @endif
                            @error('qtyUsed') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tujuan Pemakaian --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                                Tujuan Pemakaian <span class="text-rose-500">*</span>
                            </label>
                            <select wire:model="purpose"
                                    class="w-full text-xs font-bold py-2.5 px-3 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition">
                                <option value="PRODUKSI">🏭 Running Produksi</option>
                                <option value="PURGING">🧹 Purging Mesin</option>
                                <option value="TRIAL">🔬 Trial Mould / Material Baru</option>
                                <option value="REJECT">❌ Scrap / Reject Bahan</option>
                            </select>
                            @error('purpose') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Tanggal & Jam Penggunaan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                                Waktu Pemakaian
                            </label>
                            <input type="datetime-local"
                                   wire:model="usedAt"
                                   class="w-full text-xs font-bold py-2.5 px-3 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition">
                        </div>

                        {{-- Catatan --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                                Catatan / Keterangan
                            </label>
                            <input type="text"
                                   wire:model="usageRemarks"
                                   placeholder="Catatan tambahan (opsional)..."
                                   class="w-full text-xs py-2.5 px-3 border border-gray-200 rounded-xl bg-white focus:border-indigo-500 transition">
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" wire:click="switchTab('receive')"
                                class="px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                            Kembali
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                            <span>💾</span>
                            <span>Simpan Log Pemakaian</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- TAB 3: STOK LANTAI PRODUKSI & RIWAYAT PEMAKAIAN          --}}
    {{-- ======================================================== --}}
    @if($activeTab === 'stock')
        <div class="space-y-6">
            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-emerald-500">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Material Diterima (Produksi)</div>
                    <div class="text-2xl font-black font-mono text-emerald-700 mt-1">
                        {{ number_format($totalReceivedKg, 2) }} <span class="text-xs font-normal text-gray-400">KG</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-indigo-500">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Material Digunakan</div>
                    <div class="text-2xl font-black font-mono text-indigo-700 mt-1">
                        {{ number_format($totalUsedKg, 2) }} <span class="text-xs font-normal text-gray-400">KG</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-blue-500">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sisa Stok di Lantai Produksi</div>
                    <div class="text-2xl font-black font-mono text-blue-700 mt-1">
                        {{ number_format($currentFloorStockKg, 2) }} <span class="text-xs font-normal text-gray-400">KG</span>
                    </div>
                </div>
            </div>

            {{-- 1. Tabel Ringkasan Stok Material di Lantai Produksi --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 flex items-center gap-1.5">
                            <span>📦</span>
                            <span>Saldo Stok Material di Lantai Produksi (Floor Stock)</span>
                        </h3>
                        <p class="text-xs text-gray-500">Akumulasi penerimaan dari Store dikurangi pemakaian ke mesin produksi.</p>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <input type="text" wire:model.live.debounce.250ms="searchStock"
                               placeholder="Cari material..."
                               class="w-full text-xs py-1.5 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-500 transition">
                        <span class="absolute left-2.5 top-2 text-gray-400 text-xs">🔍</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="py-2.5 px-4">Kode Material</th>
                                <th class="py-2.5 px-4">Nama Material</th>
                                <th class="py-2.5 px-4 text-center">Batch Diterima</th>
                                <th class="py-2.5 px-4 text-right">Total Terima</th>
                                <th class="py-2.5 px-4 text-right">Total Digunakan</th>
                                <th class="py-2.5 px-4 text-right">Sisa Stok Produksi</th>
                                <th class="py-2.5 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($floorStockSummary as $st)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-2.5 px-4 font-mono font-bold text-gray-900">{{ $st['item_code'] }}</td>
                                    <td class="py-2.5 px-4 text-gray-800">{{ $st['item_name'] }}</td>
                                    <td class="py-2.5 px-4 text-center font-bold text-gray-600">{{ $st['batch_count'] }} batch</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold text-gray-900">{{ number_format($st['total_received'], 2) }} KG</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format($st['total_used'], 2) }} KG</td>
                                    <td class="py-2.5 px-4 text-right font-mono font-black text-sm {{ $st['current_stock'] > 0 ? 'text-blue-700' : 'text-gray-400' }}">
                                        {{ number_format($st['current_stock'], 2) }} KG
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        @if($st['status'] === 'AVAILABLE')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Tersedia</span>
                                        @elseif($st['status'] === 'LOW_STOCK')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Menipis (&lt;50 KG)</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">Habis</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-6 text-gray-400 italic">
                                        Belum ada material yang diverifikasi diterima di lantai produksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Tabel Riwayat Log Pemakaian Material --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 flex items-center gap-1.5">
                            <span>📜</span>
                            <span>Riwayat Pemakaian Material di Mesin</span>
                        </h3>
                        <p class="text-xs text-gray-500">Log pencatatan material yang ditarik ke mesin atau digunakan untuk purging/trial.</p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" wire:click="exportUsageCsv"
                                class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs">
                            <span>📥</span>
                            <span>Ekspor CSV</span>
                        </button>
                    </div>
                </div>

                {{-- History Filters --}}
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs">
                    <div>
                        <input type="text" wire:model.live.debounce.250ms="historySearch"
                               placeholder="Cari material, mesin, SPK..."
                               class="w-full text-xs py-1.5 px-3 border border-gray-200 rounded-xl bg-gray-50">
                    </div>
                    <div>
                        <select wire:model.live="historyMachine" class="w-full text-xs py-1.5 px-3 border border-gray-200 rounded-xl bg-gray-50">
                            <option value="">Semua Mesin</option>
                            @foreach($machines as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select wire:model.live="historyPurpose" class="w-full text-xs py-1.5 px-3 border border-gray-200 rounded-xl bg-gray-50">
                            <option value="ALL">Semua Tujuan</option>
                            <option value="PRODUKSI">Produksi</option>
                            <option value="PURGING">Purging</option>
                            <option value="TRIAL">Trial</option>
                            <option value="REJECT">Reject</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="date" wire:model.live="historyStartDate" class="w-1/2 text-xs py-1.5 px-2 border border-gray-200 rounded-xl bg-gray-50">
                        <input type="date" wire:model.live="historyEndDate" class="w-1/2 text-xs py-1.5 px-2 border border-gray-200 rounded-xl bg-gray-50">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="py-2.5 px-4">Waktu</th>
                                <th class="py-2.5 px-4 text-center">Mesin</th>
                                <th class="py-2.5 px-4">No. SPK</th>
                                <th class="py-2.5 px-4">Material</th>
                                <th class="py-2.5 px-4 text-right">Qty Terpakai</th>
                                <th class="py-2.5 px-4 text-center">Tujuan</th>
                                <th class="py-2.5 px-4">PIC Pemakai</th>
                                <th class="py-2.5 px-4">Keterangan</th>
                                <th class="py-2.5 px-4 text-center w-16">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($usageHistory as $uh)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="py-2.5 px-4 font-mono">
                                        <div class="font-bold text-gray-800">{{ $uh->used_at ? $uh->used_at->format('d/m/Y') : '-' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $uh->used_at ? $uh->used_at->format('H:i') : '' }}</div>
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold font-mono text-[11px]">
                                            {{ $uh->machine_no ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 font-mono font-bold text-gray-700">
                                        {{ $uh->spk_code ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4">
                                        <div class="font-mono font-bold text-gray-900">{{ $uh->item_code }}</div>
                                        <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $uh->item_name ?: ($uh->outgoing?->material?->item_description ?: '-') }}</div>
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono font-black text-indigo-700">
                                        {{ number_format($uh->qty_used, 2) }} <span class="text-[10px] text-gray-400 font-normal">KG</span>
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $uh->purpose === 'PURGING' ? 'bg-orange-100 text-orange-800' : ($uh->purpose === 'TRIAL' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ $uh->purpose }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 font-bold text-gray-700">
                                        {{ $uh->used_by_name ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-gray-500 text-[11px] max-w-xs truncate">
                                        {{ $uh->remarks ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <button type="button" wire:click="deleteUsage({{ $uh->id }})"
                                                wire:confirm="Hapus log pemakaian ini? Kuantitas stok material produksi akan dikembalikan."
                                                class="text-rose-500 hover:text-rose-700 font-bold text-[11px] transition p-1 hover:bg-rose-50 rounded"
                                                title="Hapus log & kembalikan stok">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-6 text-gray-400 italic">
                                        Belum ada riwayat pemakaian material.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($usageHistory->hasPages())
                    <div class="pt-3 border-t border-gray-100">
                        {{ $usageHistory->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- MODAL VERIFIKASI PENERIMAAN MANUAL                       --}}
    {{-- ======================================================== --}}
    @if($showVerifyModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="font-black text-gray-900 text-sm flex items-center gap-1.5">
                        <span>📥</span>
                        <span>Verifikasi Penerimaan Material</span>
                    </h3>
                    <button type="button" wire:click="closeVerifyModal" class="text-gray-400 hover:text-gray-600 font-bold">✕</button>
                </div>

                <p class="text-xs text-gray-600">
                    Konfirmasi bahwa fisik material telah diterima dengan baik di lantai produksi. Kuantitas material akan otomatis ditambahkan ke saldo stok lantai produksi.
                </p>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Penerimaan (Opsional)</label>
                    <textarea wire:model="verificationNotes"
                              rows="2"
                              placeholder="Misal: Kondisi karung baik, ditaruh di staging area mesin..."
                              class="w-full text-xs p-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-amber-500 transition"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeVerifyModal"
                            class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="button" wire:click="confirmVerification"
                            class="px-5 py-2 text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition shadow-sm">
                        ✓ Konfirmasi Terima
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

