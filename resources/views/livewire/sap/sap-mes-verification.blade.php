<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black shadow-md shadow-blue-500/20 text-lg">
                    🔍
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">Verifikasi Data MES vs SAP</h1>
                    <p class="text-xs sm:text-sm text-gray-500">Audit & Rekonsiliasi Laporan Hasil Ekspor SAP terhadap Database Produksi MES</p>
                </div>
            </div>
        </div>

        {{-- Navigation shortcuts --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('receipt-production-logs') }}" wire:navigate
               class="px-3.5 py-2 text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition inline-flex items-center gap-1.5 shadow-xs">
                <span>📦</span>
                <span>Monitor Receipt SPK</span>
            </a>
            <a href="{{ route('wms.sap-sync-monitor-delivery') }}" wire:navigate
               class="px-3.5 py-2 text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition inline-flex items-center gap-1.5 shadow-xs">
                <span>🚚</span>
                <span>SAP Sync Pallet</span>
            </a>
        </div>
    </div>

    {{-- Informational Banner: Pure Audit Notice --}}
    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex items-start gap-3 text-xs text-indigo-900">
        <span class="text-base shrink-0">💡</span>
        <div class="leading-relaxed">
            <strong class="font-bold">Laporan Verifikasi Independen (Read-Only):</strong>
            Fitur ini murni digunakan untuk mengidentifikasi dan membandingkan data produksi MES dengan file laporan yang diekspor dari SAP (mendeteksi SPK/Pallet yang sudah masuk, selisih quantity, dan yang belum masuk ke SAP).
            <span class="text-indigo-700 font-medium">Proses ini tidak mengubah status data transaksi di database MES.</span>
        </div>
    </div>

    {{-- Tab Selector --}}
    <div class="bg-white p-2 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-2">
        <button type="button" wire:click="switchTab('receipt')"
                class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 {{ $activeTab === 'receipt' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
            <span>📦</span>
            <span>Tab 1: Receipt from Production (ke FFI / KRFFI)</span>
            @if(!empty($receiptComparison))
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono {{ $activeTab === 'receipt' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">
                    {{ count($receiptComparison) }}
                </span>
            @endif
        </button>

        <button type="button" wire:click="switchTab('pallet')"
                class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 {{ $activeTab === 'pallet' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'text-gray-600 hover:bg-gray-50' }}">
            <span>🚚</span>
            <span>Tab 2: WMS Pallet Transfer (FFI → FG)</span>
            @if(!empty($palletComparison))
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono {{ $activeTab === 'pallet' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">
                    {{ count($palletComparison) }}
                </span>
            @endif
        </button>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 1: RECEIPT FROM PRODUCTION CONTENT                   --}}
    {{-- ======================================================== --}}
    @if($activeTab === 'receipt')
        {{-- Upload & Filter Card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                <div>
                    <h2 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>📤</span>
                        <span>Upload File Excel SAP: Receipt from Production</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        File ekspor dari SAP berisi kolom Document Number, Posting Date, Item No, Quantity, Warehouse (FFI/KRFFI), dan Remarks (Created from SPK ...).
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($recentReceiptBatches->isNotEmpty())
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-gray-400">📜 Riwayat:</span>
                            <select wire:change="loadReceiptBatch($event.target.value)"
                                    class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50 font-bold text-gray-700 max-w-xs truncate">
                                <option value="">-- Pilih Riwayat Batch --</option>
                                @foreach($recentReceiptBatches as $rb)
                                    <option value="{{ $rb->id }}" {{ $receiptBatchId == $rb->id ? 'selected' : '' }}>
                                        #{{ $rb->id }} - {{ $rb->created_at->format('d/m/Y H:i') }} ({{ $rb->file_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(!empty($receiptComparison))
                        <button type="button" wire:click="clearReceipt"
                                class="px-3 py-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 rounded-lg transition self-start sm:self-auto">
                            ✕ Reset Tampilan
                        </button>
                    @endif
                </div>
            </div>

            @if($receiptBatchId)
                <div class="bg-blue-50/80 border border-blue-200 rounded-xl px-4 py-2 flex items-center justify-between gap-2 text-xs text-blue-900">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💾</span>
                        <span>
                            <strong class="font-bold">Tersimpan di Database:</strong> Batch #{{ $receiptBatchId }} ({{ $receiptFileName }}) &bull; Diunggah: {{ $receiptBatchCreatedAt }}
                        </span>
                    </div>
                    <button type="button" wire:click="deleteBatch({{ $receiptBatchId }})" wire:confirm="Hapus batch riwayat ini dari database?"
                            class="text-rose-600 hover:text-rose-800 font-bold text-[11px] px-2 py-1 bg-white rounded-lg border border-rose-200 transition">
                        🗑️ Hapus Batch
                    </button>
                </div>
            @endif

            {{-- Upload Form --}}
            <form wire:submit.prevent="uploadReceipt" class="space-y-4">
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                    <div class="flex-1">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                            Pilih File Excel / CSV dari SAP
                        </label>
                        <input type="file" wire:model="receiptFile" accept=".xlsx,.xls,.csv"
                               class="block w-full text-xs text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-200 rounded-xl bg-gray-50/50 p-1">
                        @error('receiptFile')
                            <span class="text-rose-500 text-xs font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-0 md:pt-5">
                        <button type="submit" wire:loading.attr="disabled"
                                class="w-full md:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-black rounded-xl transition shadow-md shadow-blue-500/20 disabled:opacity-50 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="receiptFile,uploadReceipt">🔍 Proses & Verifikasi Data</span>
                            <span wire:loading wire:target="receiptFile,uploadReceipt" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menganalisis File...</span>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Uploaded File Info & Recalculate Date Range --}}
                @if(!empty($receiptRawRows))
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-bold text-gray-800">File Aktif: {{ $receiptFileName }}</span>
                            <span class="text-gray-400">|</span>
                            <span class="text-gray-600">{{ count($receiptRawRows) }} baris data SAP</span>
                        </div>

                        {{-- Date filter override --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-gray-500 font-medium">Rentang Audit MES:</span>
                            <input type="date" wire:model="receiptStartDate" class="px-2.5 py-1 text-xs border border-gray-300 rounded-lg bg-white">
                            <span class="text-gray-400">s/d</span>
                            <input type="date" wire:model="receiptEndDate" class="px-2.5 py-1 text-xs border border-gray-300 rounded-lg bg-white">
                            <button type="button" wire:click="recalculateReceipt"
                                    class="px-3 py-1 bg-white hover:bg-gray-100 border border-gray-300 text-gray-800 rounded-lg font-bold transition shadow-xs">
                                🔄 Terapkan Rentang
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- Verification Results --}}
        @if(!empty($receiptComparison))
            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                {{-- 1. Total Data --}}
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Item / SPK</div>
                    <div class="text-2xl font-black text-gray-900 mt-1 font-mono">
                        {{ number_format($receiptKpi['total_items'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-0.5">
                        SAP: {{ number_format($receiptKpi['total_sap_pcs'] ?? 0) }} pcs
                    </div>
                </div>

                {{-- 2. Match --}}
                <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-100 shadow-sm cursor-pointer hover:border-emerald-300 transition"
                     wire:click="$set('receiptStatusFilter', 'MATCH')">
                    <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider flex items-center justify-between">
                        <span>✓ Sesuai (Match)</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-2xl font-black text-emerald-900 mt-1 font-mono">
                        {{ number_format($receiptKpi['match_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">
                        Qty SAP & MES identik
                    </div>
                </div>

                {{-- 3. Selisih Qty --}}
                <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-100 shadow-sm cursor-pointer hover:border-amber-300 transition"
                     wire:click="$set('receiptStatusFilter', 'QTY_DIFF')">
                    <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center justify-between">
                        <span>⚠️ Selisih Qty</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-2xl font-black text-amber-900 mt-1 font-mono">
                        {{ number_format($receiptKpi['diff_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-amber-700 mt-0.5">
                        Ada di SAP tapi Qty beda
                    </div>
                </div>

                {{-- 4. Belum Masuk SAP --}}
                <div class="bg-rose-50/60 p-4 rounded-2xl border border-rose-100 shadow-sm cursor-pointer hover:border-rose-300 transition"
                     wire:click="$set('receiptStatusFilter', 'MISSING_IN_SAP')">
                    <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider flex items-center justify-between">
                        <span>❌ Belum Masuk SAP</span>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="text-2xl font-black text-rose-900 mt-1 font-mono">
                        {{ number_format($receiptKpi['missing_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-rose-700 mt-0.5">
                        Di MES ada, di SAP belum
                    </div>
                </div>

                {{-- 5. Hanya di SAP --}}
                <div class="bg-purple-50/60 p-4 rounded-2xl border border-purple-100 shadow-sm cursor-pointer hover:border-purple-300 transition"
                     wire:click="$set('receiptStatusFilter', 'ONLY_IN_SAP')">
                    <div class="text-[11px] font-bold text-purple-800 uppercase tracking-wider flex items-center justify-between">
                        <span>❓ Hanya di SAP</span>
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    </div>
                    <div class="text-2xl font-black text-purple-900 mt-1 font-mono">
                        {{ number_format($receiptKpi['only_sap_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-purple-700 mt-0.5">
                        Tidak ada di MES
                    </div>
                </div>
            </div>

            {{-- Table Filter Strip & Export --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                {{-- Status Pills --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button type="button" wire:click="$set('receiptStatusFilter', 'ALL')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $receiptStatusFilter === 'ALL' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Semua ({{ count($receiptComparison) }})
                    </button>
                    <button type="button" wire:click="$set('receiptStatusFilter', 'MATCH')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $receiptStatusFilter === 'MATCH' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                        ✓ Cocok ({{ $receiptKpi['match_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('receiptStatusFilter', 'QTY_DIFF')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $receiptStatusFilter === 'QTY_DIFF' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                        ⚠️ Selisih ({{ $receiptKpi['diff_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('receiptStatusFilter', 'MISSING_IN_SAP')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $receiptStatusFilter === 'MISSING_IN_SAP' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                        ❌ Belum Masuk ({{ $receiptKpi['missing_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('receiptStatusFilter', 'ONLY_IN_SAP')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $receiptStatusFilter === 'ONLY_IN_SAP' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}">
                        ❓ Hanya SAP ({{ $receiptKpi['only_sap_count'] ?? 0 }})
                    </button>
                </div>

                {{-- Search & Export --}}
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-64">
                        <input type="text" wire:model.live.debounce.250ms="receiptSearch"
                               placeholder="Cari SPK, Part, Doc SAP..."
                               class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-blue-500 transition">
                        <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>

                    <button type="button" wire:click="exportReceiptCsv"
                            class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shrink-0 shadow-xs">
                        <span>📥</span>
                        <span>Ekspor CSV</span>
                    </button>
                </div>
            </div>

            {{-- Comparison Table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4 w-36">Status</th>
                                <th class="py-3 px-4">No. SPK</th>
                                <th class="py-3 px-4">Item Code & Deskripsi</th>
                                <th class="py-3 px-4 text-center">Gudang</th>
                                <th class="py-3 px-4 text-right">Qty SAP</th>
                                <th class="py-3 px-4 text-right">Qty MES</th>
                                <th class="py-3 px-4 text-right">Selisih</th>
                                <th class="py-3 px-4">Dokumen SAP</th>
                                <th class="py-3 px-4 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($receiptList as $item)
                                <tr class="hover:bg-blue-50/30 transition-colors {{ $expandedKey === $item['key'] ? 'bg-blue-50/40' : '' }}">
                                    {{-- Status Badge --}}
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $item['status_badge'] }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </td>

                                    {{-- SPK Code --}}
                                    <td class="py-3 px-4 font-mono font-black text-gray-900 text-sm">
                                        {{ $item['spk_code'] ?: '-' }}
                                    </td>

                                    {{-- Item Code & Description --}}
                                    <td class="py-3 px-4">
                                        <div class="font-mono font-bold text-gray-800">{{ $item['item_code'] }}</div>
                                        <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $item['description'] }}</div>
                                    </td>

                                    {{-- Warehouse --}}
                                    <td class="py-3 px-4 text-center font-bold text-gray-700">
                                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-[11px]">
                                            {{ $item['warehouse'] }}
                                        </span>
                                    </td>

                                    {{-- Qty SAP --}}
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                        {{ number_format($item['sap_qty']) }}
                                    </td>

                                    {{-- Qty MES --}}
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                        {{ number_format($item['mes_qty']) }}
                                    </td>

                                    {{-- Diff Qty --}}
                                    <td class="py-3 px-4 text-right font-mono font-bold">
                                        @if(abs($item['diff_qty']) < 0.001)
                                            <span class="text-emerald-600">0</span>
                                        @elseif($item['diff_qty'] > 0)
                                            <span class="text-amber-600">+{{ number_format($item['diff_qty']) }}</span>
                                        @else
                                            <span class="text-rose-600">{{ number_format($item['diff_qty']) }}</span>
                                        @endif
                                    </td>

                                    {{-- SAP Docs --}}
                                    <td class="py-3 px-4 text-[11px] text-gray-600 max-w-xs truncate">
                                        @if(!empty($item['sap_docs']))
                                            <span class="font-mono">{{ implode(', ', array_slice($item['sap_docs'], 0, 3)) }}</span>
                                            @if(count($item['sap_docs']) > 3)
                                                <span class="text-gray-400 font-bold">+{{ count($item['sap_docs']) - 3 }} lainnya</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 italic">Belum ada di SAP</span>
                                        @endif
                                    </td>

                                    {{-- Action --}}
                                    <td class="py-3 px-4 text-center">
                                        <button type="button" wire:click="toggleExpand('{{ $item['key'] }}')"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition {{ $expandedKey === $item['key'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200' }}">
                                            {{ $expandedKey === $item['key'] ? 'Tutup ▲' : 'Detail ▼' }}
                                        </button>
                                    </td>
                                </tr>

                                {{-- Drill-down Accordion --}}
                                @if($expandedKey === $item['key'])
                                    <tr class="bg-gray-50/70 border-b border-blue-100">
                                        <td colspan="9" class="p-4 space-y-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {{-- SAP Lines Breakdown --}}
                                                <div class="bg-white p-3.5 rounded-xl border border-gray-200">
                                                    <div class="text-xs font-black text-gray-800 mb-2 flex items-center justify-between">
                                                        <span>📋 Baris Transaksi di SAP ({{ count($item['sap_lines']) }})</span>
                                                        <span class="font-mono text-blue-600 font-bold">Total: {{ number_format($item['sap_qty']) }} pcs</span>
                                                    </div>
                                                    @if(!empty($item['sap_lines']))
                                                        <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 text-[11px]">
                                                            @foreach($item['sap_lines'] as $sLine)
                                                                <div class="py-1.5 flex items-center justify-between">
                                                                    <div>
                                                                        <span class="font-mono font-bold text-gray-800">Doc #{{ $sLine['doc_num'] }}</span>
                                                                        <span class="text-gray-400 ml-1">({{ $sLine['posting_date'] ?: '-' }})</span>
                                                                    </div>
                                                                    <div class="font-mono font-bold text-gray-900">
                                                                        {{ number_format($sLine['quantity']) }} pcs
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-gray-400 text-xs italic py-2">Tidak ditemukan di file SAP.</p>
                                                    @endif
                                                </div>

                                                {{-- MES Summaries Breakdown --}}
                                                <div class="bg-white p-3.5 rounded-xl border border-gray-200">
                                                    <div class="text-xs font-black text-gray-800 mb-2 flex items-center justify-between">
                                                        <span>🏭 Baris Summary di MES ({{ count($item['mes_lines']) }})</span>
                                                        <span class="font-mono text-blue-600 font-bold">Total: {{ number_format($item['mes_qty']) }} pcs</span>
                                                    </div>
                                                    @if(!empty($item['mes_lines']))
                                                        <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 text-[11px]">
                                                            @foreach($item['mes_lines'] as $mLine)
                                                                <div class="py-1.5 flex items-center justify-between">
                                                                    <div>
                                                                        <span class="font-bold text-gray-700">Tanggal: {{ $mLine->created_date ?? '-' }}</span>
                                                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] {{ ($mLine->sap_sent ?? 0) == 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                                            Status MES: {{ ($mLine->sap_sent ?? 0) == 1 ? 'Sukses' : 'Pending/Gagal' }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="font-mono font-bold text-gray-900">
                                                                        {{ number_format($mLine->total_quantity ?? 0) }} pcs
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-gray-400 text-xs italic py-2">Tidak ditemukan di database MES.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-10 text-gray-400">
                                        <p class="font-bold text-sm">Tidak ada data yang sesuai kriteria filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(empty($receiptRawRows))
            {{-- Empty State --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 space-y-3 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 mx-auto flex items-center justify-center text-3xl">
                    📦
                </div>
                <h3 class="text-base font-bold text-gray-800">Belum Ada File Excel SAP yang Diproses</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    Silakan upload file Excel hasil export dari SAP (Receipt from Production ke FFI) di atas untuk memulai proses verifikasi otomatis terhadap database MES.
                </p>
            </div>
        @endif
    @endif

    {{-- ======================================================== --}}
    {{-- TAB 2: WMS PALLET TRANSFER (FFI -> FG) CONTENT           --}}
    {{-- ======================================================== --}}
    @if($activeTab === 'pallet')
        {{-- Upload & Filter Card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                <div>
                    <h2 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>📤</span>
                        <span>Upload File Excel SAP: Pallet Form / Inventory Transfer (FFI → FG)</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        File ekspor dari SAP berisi kolom Document Number, Posting Date, Remarks (Inventory Transfer created from PLT-...), Item Code, Quantity, dan Warehouse Code (FG).
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($recentPalletBatches->isNotEmpty())
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-gray-400">📜 Riwayat:</span>
                            <select wire:change="loadPalletBatch($event.target.value)"
                                    class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50 font-bold text-gray-700 max-w-xs truncate">
                                <option value="">-- Pilih Riwayat Batch --</option>
                                @foreach($recentPalletBatches as $pb)
                                    <option value="{{ $pb->id }}" {{ $palletBatchId == $pb->id ? 'selected' : '' }}>
                                        #{{ $pb->id }} - {{ $pb->created_at->format('d/m/Y H:i') }} ({{ $pb->file_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(!empty($palletComparison))
                        <button type="button" wire:click="clearPallet"
                                class="px-3 py-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 rounded-lg transition self-start sm:self-auto">
                            ✕ Reset Tampilan
                        </button>
                    @endif
                </div>
            </div>

            @if($palletBatchId)
                <div class="bg-emerald-50/80 border border-emerald-200 rounded-xl px-4 py-2 flex items-center justify-between gap-2 text-xs text-emerald-900">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💾</span>
                        <span>
                            <strong class="font-bold">Tersimpan di Database:</strong> Batch #{{ $palletBatchId }} ({{ $palletFileName }}) &bull; Diunggah: {{ $palletBatchCreatedAt }}
                        </span>
                    </div>
                    <button type="button" wire:click="deleteBatch({{ $palletBatchId }})" wire:confirm="Hapus batch riwayat ini dari database?"
                            class="text-rose-600 hover:text-rose-800 font-bold text-[11px] px-2 py-1 bg-white rounded-lg border border-rose-200 transition">
                        🗑️ Hapus Batch
                    </button>
                </div>
            @endif

            {{-- Upload Form --}}
            <form wire:submit.prevent="uploadPallet" class="space-y-4">
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                    <div class="flex-1">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                            Pilih File Excel / CSV Inventory Transfer dari SAP
                        </label>
                        <input type="file" wire:model="palletFile" accept=".xlsx,.xls,.csv"
                               class="block w-full text-xs text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-gray-200 rounded-xl bg-gray-50/50 p-1">
                        @error('palletFile')
                            <span class="text-rose-500 text-xs font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-0 md:pt-5">
                        <button type="submit" wire:loading.attr="disabled"
                                class="w-full md:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-black rounded-xl transition shadow-md shadow-emerald-500/20 disabled:opacity-50 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="palletFile,uploadPallet">🔍 Proses & Verifikasi Pallet</span>
                            <span wire:loading wire:target="palletFile,uploadPallet" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menganalisis File...</span>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Uploaded File Info & Recalculate Date Range --}}
                @if(!empty($palletRawRows))
                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-bold text-gray-800">File Aktif: {{ $palletFileName }}</span>
                            <span class="text-gray-400">|</span>
                            <span class="text-gray-600">{{ count($palletRawRows) }} baris data SAP</span>
                        </div>

                        {{-- Date filter override --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-gray-500 font-medium">Rentang Audit Pallet MES:</span>
                            <input type="date" wire:model="palletStartDate" class="px-2.5 py-1 text-xs border border-gray-300 rounded-lg bg-white">
                            <span class="text-gray-400">s/d</span>
                            <input type="date" wire:model="palletEndDate" class="px-2.5 py-1 text-xs border border-gray-300 rounded-lg bg-white">
                            <button type="button" wire:click="recalculatePallet"
                                    class="px-3 py-1 bg-white hover:bg-gray-100 border border-gray-300 text-gray-800 rounded-lg font-bold transition shadow-xs">
                                🔄 Terapkan Rentang
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- Pallet Results --}}
        @if(!empty($palletComparison))
            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Pallet (PLT)</div>
                    <div class="text-2xl font-black text-gray-900 mt-1 font-mono">
                        {{ number_format($palletKpi['total_items'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-0.5">
                        SAP: {{ number_format($palletKpi['total_sap_pcs'] ?? 0) }} pcs
                    </div>
                </div>

                <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-100 shadow-sm cursor-pointer hover:border-emerald-300 transition"
                     wire:click="$set('palletStatusFilter', 'MATCH')">
                    <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider flex items-center justify-between">
                        <span>✓ Sesuai (Match)</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-2xl font-black text-emerald-900 mt-1 font-mono">
                        {{ number_format($palletKpi['match_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">
                        Qty Pallet identik
                    </div>
                </div>

                <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-100 shadow-sm cursor-pointer hover:border-amber-300 transition"
                     wire:click="$set('palletStatusFilter', 'QTY_DIFF')">
                    <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center justify-between">
                        <span>⚠️ Selisih Qty</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-2xl font-black text-amber-900 mt-1 font-mono">
                        {{ number_format($palletKpi['diff_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-amber-700 mt-0.5">
                        Ada di SAP tapi Qty beda
                    </div>
                </div>

                <div class="bg-rose-50/60 p-4 rounded-2xl border border-rose-100 shadow-sm cursor-pointer hover:border-rose-300 transition"
                     wire:click="$set('palletStatusFilter', 'MISSING_IN_SAP')">
                    <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider flex items-center justify-between">
                        <span>❌ Belum Masuk SAP</span>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="text-2xl font-black text-rose-900 mt-1 font-mono">
                        {{ number_format($palletKpi['missing_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-rose-700 mt-0.5">
                        Di MES ada, di SAP belum
                    </div>
                </div>

                <div class="bg-purple-50/60 p-4 rounded-2xl border border-purple-100 shadow-sm cursor-pointer hover:border-purple-300 transition"
                     wire:click="$set('palletStatusFilter', 'ONLY_IN_SAP')">
                    <div class="text-[11px] font-bold text-purple-800 uppercase tracking-wider flex items-center justify-between">
                        <span>❓ Hanya di SAP</span>
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    </div>
                    <div class="text-2xl font-black text-purple-900 mt-1 font-mono">
                        {{ number_format($palletKpi['only_sap_count'] ?? 0) }}
                    </div>
                    <div class="text-[11px] text-purple-700 mt-0.5">
                        Tidak ada di MES
                    </div>
                </div>
            </div>

            {{-- Table Filter Strip & Export --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                    <button type="button" wire:click="$set('palletStatusFilter', 'ALL')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $palletStatusFilter === 'ALL' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Semua ({{ count($palletComparison) }})
                    </button>
                    <button type="button" wire:click="$set('palletStatusFilter', 'MATCH')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $palletStatusFilter === 'MATCH' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                        ✓ Cocok ({{ $palletKpi['match_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('palletStatusFilter', 'QTY_DIFF')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $palletStatusFilter === 'QTY_DIFF' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                        ⚠️ Selisih ({{ $palletKpi['diff_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('palletStatusFilter', 'MISSING_IN_SAP')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $palletStatusFilter === 'MISSING_IN_SAP' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                        ❌ Belum Masuk ({{ $palletKpi['missing_count'] ?? 0 }})
                    </button>
                    <button type="button" wire:click="$set('palletStatusFilter', 'ONLY_IN_SAP')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $palletStatusFilter === 'ONLY_IN_SAP' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}">
                        ❓ Hanya SAP ({{ $palletKpi['only_sap_count'] ?? 0 }})
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-64">
                        <input type="text" wire:model.live.debounce.250ms="palletSearch"
                               placeholder="Cari Pallet, Part, Doc SAP..."
                               class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-emerald-500 transition">
                        <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                    </div>

                    <button type="button" wire:click="exportPalletCsv"
                            class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shrink-0 shadow-xs">
                        <span>📥</span>
                        <span>Ekspor CSV</span>
                    </button>
                </div>
            </div>

            {{-- Pallet Comparison Table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4 w-36">Status</th>
                                <th class="py-3 px-4">No. Pallet (PLT)</th>
                                <th class="py-3 px-4">Item Code & Deskripsi</th>
                                <th class="py-3 px-4 text-center">Gudang</th>
                                <th class="py-3 px-4 text-right">Qty SAP</th>
                                <th class="py-3 px-4 text-right">Qty MES</th>
                                <th class="py-3 px-4 text-right">Selisih</th>
                                <th class="py-3 px-4">Dokumen SAP</th>
                                <th class="py-3 px-4 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($palletList as $item)
                                <tr class="hover:bg-emerald-50/30 transition-colors {{ $expandedKey === $item['key'] ? 'bg-emerald-50/40' : '' }}">
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $item['status_badge'] }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 font-mono font-black text-gray-900 text-sm">
                                        {{ $item['pallet_id'] ?: '-' }}
                                    </td>

                                    <td class="py-3 px-4">
                                        <div class="font-mono font-bold text-gray-800">{{ $item['item_code'] }}</div>
                                        <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $item['description'] }}</div>
                                    </td>

                                    <td class="py-3 px-4 text-center font-bold text-gray-700">
                                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-[11px]">
                                            {{ $item['from_whse'] }} → {{ $item['to_whse'] }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                        {{ number_format($item['sap_qty']) }}
                                    </td>

                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                        {{ number_format($item['mes_qty']) }}
                                    </td>

                                    <td class="py-3 px-4 text-right font-mono font-bold">
                                        @if(abs($item['diff_qty']) < 0.001)
                                            <span class="text-emerald-600">0</span>
                                        @elseif($item['diff_qty'] > 0)
                                            <span class="text-amber-600">+{{ number_format($item['diff_qty']) }}</span>
                                        @else
                                            <span class="text-rose-600">{{ number_format($item['diff_qty']) }}</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-4 text-[11px] text-gray-600 max-w-xs truncate">
                                        @if(!empty($item['sap_docs']))
                                            <span class="font-mono">{{ implode(', ', array_slice($item['sap_docs'], 0, 3)) }}</span>
                                            @if(count($item['sap_docs']) > 3)
                                                <span class="text-gray-400 font-bold">+{{ count($item['sap_docs']) - 3 }} lainnya</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400 italic">Belum ada di SAP</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-4 text-center">
                                        <button type="button" wire:click="toggleExpand('{{ $item['key'] }}')"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition {{ $expandedKey === $item['key'] ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200' }}">
                                            {{ $expandedKey === $item['key'] ? 'Tutup ▲' : 'Detail ▼' }}
                                        </button>
                                    </td>
                                </tr>

                                {{-- Drill-down Accordion --}}
                                @if($expandedKey === $item['key'])
                                    <tr class="bg-gray-50/70 border-b border-emerald-100">
                                        <td colspan="9" class="p-4 space-y-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {{-- SAP Lines Breakdown --}}
                                                <div class="bg-white p-3.5 rounded-xl border border-gray-200">
                                                    <div class="text-xs font-black text-gray-800 mb-2 flex items-center justify-between">
                                                        <span>📋 Baris Transfer di SAP ({{ count($item['sap_lines']) }})</span>
                                                        <span class="font-mono text-emerald-600 font-bold">Total: {{ number_format($item['sap_qty']) }} pcs</span>
                                                    </div>
                                                    @if(!empty($item['sap_lines']))
                                                        <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 text-[11px]">
                                                            @foreach($item['sap_lines'] as $sLine)
                                                                <div class="py-1.5 flex items-center justify-between">
                                                                    <div>
                                                                        <span class="font-mono font-bold text-gray-800">Doc #{{ $sLine['doc_num'] }}</span>
                                                                        <span class="text-gray-500 ml-1">[{{ $sLine['item_code'] }}]</span>
                                                                        <span class="text-gray-400 ml-1">({{ $sLine['posting_date'] ?: '-' }})</span>
                                                                    </div>
                                                                    <div class="font-mono font-bold text-gray-900">
                                                                        {{ number_format($sLine['quantity']) }} pcs
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-gray-400 text-xs italic py-2">Tidak ditemukan di file SAP.</p>
                                                    @endif
                                                </div>

                                                {{-- MES Pallet Details Breakdown --}}
                                                <div class="bg-white p-3.5 rounded-xl border border-gray-200">
                                                    <div class="text-xs font-black text-gray-800 mb-2 flex items-center justify-between">
                                                        <span>📦 Detail Box di MES Pallet ({{ count($item['mes_details']) }})</span>
                                                        <span class="font-mono text-emerald-600 font-bold">Total: {{ number_format($item['mes_qty']) }} pcs</span>
                                                    </div>
                                                    @if(!empty($item['mes_details']))
                                                        <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 text-[11px]">
                                                            @foreach($item['mes_details'] as $mDetail)
                                                                <div class="py-1.5 flex items-center justify-between">
                                                                    <div>
                                                                        <span class="font-bold text-gray-800">SPK #{{ $mDetail->spk_no ?? '-' }}</span>
                                                                        <span class="text-gray-500 ml-1">[{{ $mDetail->part_no ?? '-' }}]</span>
                                                                    </div>
                                                                    <div class="font-mono font-bold text-gray-900">
                                                                        {{ number_format($mDetail->qty ?? 0) }} pcs
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-gray-400 text-xs italic py-2">Tidak ada data detail box di MES.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-10 text-gray-400">
                                        <p class="font-bold text-sm">Tidak ada data pallet yang sesuai kriteria filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(empty($palletRawRows))
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 space-y-3 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-500 mx-auto flex items-center justify-center text-3xl">
                    🚚
                </div>
                <h3 class="text-base font-bold text-gray-800">Belum Ada File Excel SAP Pallet yang Diproses</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    Silakan upload file Excel hasil export Inventory Transfer dari SAP (Pallet Transfer ke FG) di atas untuk memulai verifikasi otomatis terhadap data pallet di MES.
                </p>
            </div>
        @endif
    @endif
</div>
