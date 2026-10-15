<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 shadow-sm">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl flex items-center justify-between text-rose-800 shadow-sm">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif

        <!-- Header Banner -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full inline-block"></span>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Kedatangan Material (Incoming)</h1>
                </div>
                <p class="text-xs text-gray-500 mt-1">Form penerimaan bahan baku, pembagian Pallet ID (max 1,000 KG/pallet), dan penempatan slot rak.</p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('mwh.pallets.index') }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span>Daftar Stock Pallet</span>
                </a>
            </div>
        </div>

        <form wire:submit.prevent="saveIncoming" class="space-y-6">

            <!-- Section 1: Header Dokumen -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <div class="flex flex-wrap justify-between items-center border-b border-gray-100 pb-3 gap-2">
                    <h3 class="text-xs font-black text-emerald-800 uppercase tracking-widest">1. Header Dokumen Penerimaan / Retur</h3>
                    
                    <!-- Incoming Type Selector Toggle -->
                    <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-xl text-xs font-bold">
                        <label class="px-3 py-1.5 rounded-lg cursor-pointer transition-all flex items-center space-x-1.5 {{ $incoming_type === 'SUPPLIER' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" wire:model.live="incoming_type" value="SUPPLIER" class="sr-only">
                            <span>📦 Kedatangan Supplier</span>
                        </label>

                        <label class="px-3 py-1.5 rounded-lg cursor-pointer transition-all flex items-center space-x-1.5 {{ $incoming_type === 'RETURN_PRODUCTION' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" wire:model.live="incoming_type" value="RETURN_PRODUCTION" class="sr-only">
                            <span>🔄 Retur Sisa Produksi</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-emerald-800 uppercase tracking-widest mb-1">🏢 Branch / Gudang *</label>
                        <select wire:model.live="whse_id" class="w-full px-3 py-2 bg-emerald-50 border border-emerald-300 rounded-xl text-xs font-bold text-emerald-900 focus:ring-2 focus:ring-emerald-500">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh['id'] }}">{{ $wh['whse_name'] }} ({{ $wh['whse_code'] }})</option>
                            @endforeach
                        </select>
                        @error('whse_id') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">No. Dokumen (Auto)</label>
                        <input type="text" wire:model="document_no" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-600 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Entry *</label>
                        <input type="date" wire:model="arrival_date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500">
                        @error('arrival_date') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if($incoming_type === 'SUPPLIER')
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nama Supplier (Opsional)</label>
                            <input type="text" wire:model="supplier_name" placeholder="Misal: PT. Material Abadi" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">No. PO / Surat Jalan (Opsional)</label>
                            <input type="text" wire:model="po_number" placeholder="Misal: PO-2026-0881" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500">
                        </div>
                    @else
                        <div>
                            <label class="block text-[10px] font-black text-amber-700 uppercase tracking-widest mb-1">Dikembalikan Oleh / Asal Produksi *</label>
                            <input type="text" wire:model="returned_from" placeholder="Misal: Produksi Line 1 / Shift A" class="w-full px-3 py-2 bg-amber-50 border border-amber-300 rounded-xl text-xs font-bold text-amber-900 focus:ring-2 focus:ring-amber-500">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-amber-700 uppercase tracking-widest mb-1">
                                No. Outgoing Ref
                                @if($is_prefilled_from_outgoing)
                                    <span class="text-[9px] text-amber-900 bg-amber-200 px-1.5 py-0.5 rounded font-black ml-1">🔒 TERKUNCI (AUTO)</span>
                                @else
                                    (Opsional)
                                @endif
                            </label>
                            <input type="text" wire:model="original_outgoing_code"
                                   {{ $is_prefilled_from_outgoing ? 'readonly' : '' }}
                                   placeholder="Misal: OUT-20260803-0001"
                                   class="w-full px-3 py-2 bg-amber-50 border border-amber-300 rounded-xl text-xs font-mono font-bold text-amber-900 focus:ring-2 focus:ring-amber-500 {{ $is_prefilled_from_outgoing ? 'bg-amber-100/90 cursor-not-allowed border-amber-400 shadow-inner' : '' }}">
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan Dokumen (Opsional)</label>
                    <input type="text" wire:model="remarks" placeholder="Catatan tambahan kedatangan/retur..." class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Section 2: Multi-Item Detail Table -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-2 gap-2">
                    <h3 class="text-xs font-black text-emerald-800 uppercase tracking-widest">2. Detail Item Material Kedatangan</h3>
                    <div class="flex items-center flex-wrap gap-2">
                        <button type="button" wire:click="setAllToTemporary" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-black transition flex items-center space-x-1 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>⏳ Set Semua ke Temporary</span>
                        </button>
                        <span class="text-[11px] text-gray-400">💡 Qty > 1,000 KG otomatis dipecah menjadi beberapa Pallet ID</span>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach ($items as $index => $row)
                        <div class="p-4 bg-gray-50/70 rounded-2xl border border-gray-200/80 relative space-y-3 {{ ($row['position_id'] ?? '') === 'TEMPORARY' ? 'ring-1 ring-amber-300 bg-amber-50/20' : '' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-black text-gray-500 uppercase tracking-wider">Item #{{ $index + 1 }}</span>
                                    @if (($row['position_id'] ?? '') === 'TEMPORARY')
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 rounded-full text-[10px] font-black">
                                            ⏳ Area Temporary
                                        </span>
                                    @endif
                                </div>
                                @if (count($items) > 1 && !($is_prefilled_from_outgoing && $index === 0))
                                    <button type="button" wire:click="removeItemRow({{ $index }})" class="text-rose-500 hover:text-rose-700 text-xs font-bold flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus Row</span>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
                                <!-- Part Code Autocomplete -->
                                <div class="sm:col-span-2 md:col-span-4 relative">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">
                                        Part Code Material *
                                        @if($is_prefilled_from_outgoing && $index === 0)
                                            <span class="text-[9px] text-slate-800 bg-slate-200 px-1.5 py-0.5 rounded font-black ml-1">🔒 TERKUNCI (AUTO)</span>
                                        @endif
                                    </label>
                                    <input type="text" wire:model.live="items.{{ $index }}.item_code"
                                           {{ ($is_prefilled_from_outgoing && $index === 0) ? 'readonly' : '' }}
                                           placeholder="Cari Part Code / Nama..."
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-emerald-500 {{ ($is_prefilled_from_outgoing && $index === 0) ? 'bg-slate-100 cursor-not-allowed border-slate-300 font-extrabold text-slate-800 shadow-inner' : '' }}">
                                    
                                    @if (empty($is_prefilled_from_outgoing && $index === 0) && !empty($row['searchResults']))
                                        <div class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 z-30 max-h-48 overflow-y-auto">
                                            @foreach ($row['searchResults'] as $result)
                                                <button type="button" wire:click="selectMaterial({{ $index }}, '{{ $result['item_code'] }}', '{{ addslashes($result['item_description'] ?? '') }}')" class="w-full text-left px-3 py-2 hover:bg-emerald-50 transition border-b border-gray-50 flex flex-col">
                                                    <span class="text-xs font-bold font-mono text-gray-900">{{ $result['item_code'] }}</span>
                                                    <span class="text-[10px] text-gray-500 truncate">{{ $result['item_description'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @error("items.{$index}.item_code") <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Part Description Display -->
                                <div class="sm:col-span-2 md:col-span-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Deskripsi Material</label>
                                    <input type="text" value="{{ $row['item_description'] }}" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-xs text-gray-600 truncate cursor-not-allowed">
                                </div>

                                <!-- Lot / Batch No -->
                                <div class="sm:col-span-1 md:col-span-2">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Lot / Batch No</label>
                                    <input type="text" wire:model="items.{{ $index }}.lot_no" placeholder="Misal: LOT-991" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-mono">
                                </div>

                                <!-- Total Qty (KG) -->
                                <div class="sm:col-span-1 md:col-span-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Qty (KG) *</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model="items.{{ $index }}.qty" placeholder="Misal: 1500" class="w-full pl-3 pr-10 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500">
                                        <span class="absolute right-3 top-2 text-xs font-bold text-gray-400">KG</span>
                                    </div>
                                    @error("items.{$index}.qty") <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Slot Rak Assignment -->
                                <div class="sm:col-span-2 md:col-span-12">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Assign Slot Rak Lokasi Penyimpanan *</label>
                                        <button type="button" wire:click="setRowToTemporary({{ $index }})" class="text-[10px] font-black text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2 py-0.5 rounded transition inline-flex items-center space-x-1">
                                            <span>⏳ Taruh di Area Temporary</span>
                                        </button>
                                    </div>
                                    <select wire:model.live="items.{{ $index }}.position_id" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 {{ ($row['position_id'] ?? '') === 'TEMPORARY' ? 'bg-amber-50/70 border-amber-300 text-amber-900 font-black' : '' }}">
                                        <option value="">-- Pilih Slot Rak Material --</option>
                                        <option value="TEMPORARY" class="font-black text-amber-800 bg-amber-50">⏳ [TEMPORARY] Area Temporary / Transit (Tanpa Batas Kapasitas Slot)</option>
                                        @foreach ($positions as $pos)
                                            <option value="{{ $pos->id }}">
                                                {{ $pos->position_code }} ({{ $pos->slot_label ?: 'Slot' }}) — Status: {{ $pos->status }} (Max {{ number_format($pos->max_capacity) }} KG)
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (($row['position_id'] ?? '') === 'TEMPORARY')
                                        <div class="mt-1.5 p-2 bg-amber-50 border border-amber-200 rounded-xl flex items-center space-x-2 text-xs font-bold text-amber-900">
                                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Pallet akan disimpan di <strong>Area Temporary / Transit</strong> (Tanpa limit kapasitas). Pengguna dapat memindahkannya ke slot rak kosong kapan saja kemudian.</span>
                                        </div>
                                    @endif
                                    @error("items.{$index}.position_id") <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-2">
                    <button type="button" wire:click="addItemRow" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-bold text-xs transition flex items-center space-x-2 border border-emerald-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Row Material Lain</span>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="resetForm" class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition">
                    Reset Form
                </button>
                <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-200 transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Simpan Kedatangan & Generate Pallet ID</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Success Modal with Printable Pallet Labels -->
    @if ($showSuccessModal)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden animate-scale-up">
                <div class="p-6 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight">Kedatangan Berhasil Disimpan! 🎉</h3>
                        <p class="text-xs text-emerald-100">Daftar Pallet ID yang telah di-generate (Siap Tempel Label QR Code)</p>
                    </div>
                    <button wire:click="$set('showSuccessModal', false)" class="text-emerald-200 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider">
                                <th class="py-2.5 px-3">Pallet ID</th>
                                <th class="py-2.5 px-3">Part Code</th>
                                <th class="py-2.5 px-3">Qty (KG)</th>
                                <th class="py-2.5 px-3">Slot Rak</th>
                                <th class="py-2.5 px-3 text-right">Cetak Label</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 font-medium">
                            @foreach ($createdPallets as $p)
                                <tr>
                                    <td class="py-3 px-3 font-mono font-bold text-emerald-800">{{ $p['pallet_id'] }}</td>
                                    <td class="py-3 px-3 font-mono text-gray-800">{{ $p['item_code'] }}</td>
                                    <td class="py-3 px-3 font-bold text-gray-900">{{ number_format($p['qty'], 2) }} KG</td>
                                    <td class="py-3 px-3 font-mono text-gray-700">
                                        @if (($p['is_temporary'] ?? false) || ($p['position'] ?? '') === '⏳ TEMPORARY')
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 rounded font-black text-[11px] inline-flex items-center space-x-1">
                                                <span>⏳ TEMPORARY</span>
                                            </span>
                                        @else
                                            <span class="font-bold">{{ $p['position'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="{{ route('mwh.pallet.print', $p['pallet_id']) }}" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold inline-flex items-center space-x-1 shadow-sm transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            <span>Print QR</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="resetForm" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md transition">
                        Selesai & Form Baru
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
