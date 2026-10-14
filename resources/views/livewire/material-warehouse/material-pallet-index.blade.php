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

        <!-- Main Header -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full inline-block"></span>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Stock & Pallet Material</h1>
                </div>
                <p class="text-xs text-gray-500 mt-1">Daftar unit Pallet ID yang tersimpan di rak gudang material beserta status sisa stok.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('mwh.daily-audit') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs shadow-md shadow-slate-200 transition flex items-center space-x-2">
                    <span>📋</span>
                    <span>Audit Harian</span>
                </a>
                <a href="{{ route('mwh.incoming.create') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md shadow-emerald-200 transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Input Kedatangan Baru</span>
                </a>
            </div>
        </div>

        <!-- Branch / Warehouse Switcher Bar -->
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
                            wire:click="$set('whse_id', {{ $wh['id'] }})"
                            style="{{ $whse_id == $wh['id'] ? 'background-color: #059669 !important; color: #ffffff !important;' : 'background-color: #f3f4f6; color: #374151;' }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs {{ $whse_id == $wh['id'] ? 'bg-emerald-600 text-white ring-2 ring-emerald-400' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}"
                        >
                            <span>🏭</span>
                            <span style="{{ $whse_id == $wh['id'] ? 'color: #ffffff !important;' : 'color: #1f2937;' }}">{{ $wh['whse_name'] }}</span>
                            <span 
                                style="{{ $whse_id == $wh['id'] ? 'background-color: #047857 !important; color: #d1fae5 !important;' : 'background-color: #e5e7eb; color: #4b5563;' }}"
                                class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $whse_id == $wh['id'] ? 'bg-emerald-700 text-emerald-100' : 'bg-gray-200 text-gray-600' }}"
                            >
                                {{ $wh['whse_code'] }}
                            </span>
                        </button>
                    @endforeach
                    <button 
                        wire:click="$set('whse_id', 'ALL')"
                        style="{{ $whse_id === 'ALL' ? 'background-color: #1f2937 !important; color: #ffffff !important;' : 'background-color: #f3f4f6; color: #4b5563;' }}"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $whse_id === 'ALL' ? 'bg-gray-800 text-white ring-2 ring-gray-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' }}"
                    >
                        🌐 Semua Branch
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Pallet ID, Part Code, Lot No, Slot Rak..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                <div class="absolute left-3 top-3 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end text-xs font-semibold">
                <div class="flex items-center space-x-2">
                    <span class="text-gray-500">Status:</span>
                    <select wire:model.live="statusFilter" class="bg-gray-50 border border-gray-200 rounded-xl text-xs px-3 py-1.5 focus:ring-2 focus:ring-emerald-500 font-bold">
                        <option value="ALL">Semua Status</option>
                        <option value="STORED">STORED (Utuh)</option>
                        <option value="PARTIAL">PARTIAL (Slot / Terambil)</option>
                        <option value="EMPTY">EMPTY (Habis)</option>
                        <option value="QC_HOLD">⛔ QC HOLD</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-gray-500">Tampilkan:</span>
                    <select wire:model.live="perPage" class="bg-gray-50 border border-gray-200 rounded-xl text-xs px-3 py-1.5 focus:ring-2 focus:ring-emerald-500">
                        <option value="25">25 per halaman</option>
                        <option value="50">50 per halaman</option>
                        <option value="100">100 per halaman</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Pallet Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Pallet ID</th>
                            <th class="py-3.5 px-4">Part Code & Deskripsi</th>
                            <th class="py-3.5 px-4">Lot No / Supplier</th>
                            <th class="py-3.5 px-4">Initial Qty</th>
                            <th class="py-3.5 px-4">Tgl Kedatangan</th>
                            <th class="py-3.5 px-4">Sisa Qty</th>
                            <th class="py-3.5 px-4">Slot Rak</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
                        @forelse ($pallets as $p)
                            <tr class="hover:bg-emerald-50/40 transition {{ $p->is_qc_hold ? 'bg-rose-50/30' : '' }}">
                                <td class="py-3.5 px-4 font-mono font-black text-emerald-800">
                                    <div class="flex items-center space-x-1.5">
                                        <span>{{ $p->pallet_id }}</span>
                                        @if ($p->is_qc_hold)
                                            <span class="px-1.5 py-0.5 bg-rose-600 text-white rounded text-[9px] font-black animate-pulse" title="QC Hold: {{ $p->qc_hold_reason ?: 'QC Hold' }}">HOLD</span>
                                        @endif
                                    </div>
                                    @if ($p->is_qc_hold && $p->qc_hold_reason)
                                        <div class="text-[10px] text-rose-600 font-bold italic truncate max-w-xs mt-0.5">Reason: {{ $p->qc_hold_reason }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-gray-900">{{ $p->item_code }}</div>
                                    <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ $p->material ? $p->material->item_description : '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div><span class="font-mono text-gray-800">{{ $p->lot_no ?: '-' }}</span></div>
                                    <div class="text-[10px] text-gray-400">{{ $p->incomingHeader ? $p->incomingHeader->supplier_name : '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-700">
                                    {{ number_format($p->initial_qty, 2) }} KG
                                </td>
                                <td class="py-3.5 px-4 text-gray-500 font-mono text-[11px]">
                                    {{ $p->incomingHeader && $p->incomingHeader->arrival_date ? $p->incomingHeader->arrival_date->format('d M Y') : ($p->created_at ? $p->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-') }}
                                </td>
                                <td class="py-3.5 px-4 font-black text-gray-900 text-sm">
                                    {{ number_format($p->current_qty, 2) }} KG
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                                    @if ($p->position)
                                        <div class="flex items-center space-x-1.5">
                                            <span class="inline-block px-2.5 py-1 bg-gray-100 text-gray-800 rounded-md text-[11px] font-mono font-bold">
                                                {{ $p->position->position_code }}
                                            </span>
                                            @if ($p->position->status === 'PARTIAL')
                                                <span class="px-1.5 py-0.5 bg-amber-100 text-amber-900 text-[9px] font-black rounded border border-amber-300">PARTIAL SLOT</span>
                                            @elseif ($p->position->status === 'FULL')
                                                <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-black rounded border border-emerald-300">FULL SLOT</span>
                                            @endif
                                        </div>
                                    @elseif ($p->initialPosition)
                                        <div class="flex items-center space-x-1.5">
                                            <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-[11px] font-mono font-bold">
                                                {{ $p->initialPosition->position_code }}
                                            </span>
                                            <span class="px-1.5 py-0.5 bg-slate-200 text-slate-600 text-[9px] font-bold rounded">SLOT AWAL</span>
                                        </div>
                                    @elseif ($p->outgoings->isNotEmpty() && $p->outgoings->first()->position)
                                        <div class="flex items-center space-x-1.5">
                                            <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-[11px] font-mono font-bold">
                                                {{ $p->outgoings->first()->position->position_code }}
                                            </span>
                                            <span class="px-1.5 py-0.5 bg-slate-200 text-slate-600 text-[9px] font-bold rounded">SLOT AWAL</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">Unassigned</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($p->is_qc_hold)
                                        <span class="px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full font-black text-[10px] border border-rose-300 shadow-sm inline-flex items-center space-x-1">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>⛔ QC HOLD</span>
                                        </span>
                                    @elseif ($p->status === 'PARTIAL')
                                        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 rounded-full font-black text-[10px] border border-amber-300">PARTIAL (Terambil)</span>
                                    @elseif ($p->position && $p->position->status === 'PARTIAL')
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-800 rounded-full font-black text-[10px] border border-amber-200">PARTIAL (Slot Space)</span>
                                    @elseif ($p->status === 'STORED')
                                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-black text-[10px]">STORED (Utuh)</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full font-black text-[10px]">EMPTY (Habis)</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5">
                                    <button wire:click="openQcHoldModal({{ $p->id }})" class="p-1.5 {{ $p->is_qc_hold ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }} rounded-lg inline-block transition" title="QC Hold / Release / Edit Remark">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </button>
                                    <a href="{{ route('mwh.pallet.print', $p->pallet_id) }}" target="_blank" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg inline-block transition" title="Print QR Label">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                    <button wire:click="openRelocateModal({{ $p->id }})" class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg inline-block transition" title="Pindah Slot Rak">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </button>
                                    <button onclick="confirm('Hapus Pallet {{ $p->pallet_id }}?') || event.stopImmediatePropagation()" wire:click="deletePallet({{ $p->id }})" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg inline-block transition" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p class="font-bold">Belum ada data stok pallet material</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-100">
                {{ $pallets->links() }}
            </div>
        </div>
    </div>

    <!-- QC Hold Modal -->
    @if ($showQcHoldModal)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-up border border-rose-100">
                <div class="p-6 bg-gradient-to-r from-rose-900 to-amber-900 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight flex items-center space-x-2">
                            <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Kontrol QC Hold & Remark</span>
                        </h3>
                        <p class="text-xs text-rose-200">Pallet ID: <strong class="font-mono text-white">{{ $qcPalletCode }}</strong></p>
                    </div>
                    <button wire:click="$set('showQcHoldModal', false)" class="text-rose-300 hover:text-white transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <form wire:submit.prevent="saveQcHold" class="p-6 space-y-4">
                    <div class="p-4 bg-rose-50 rounded-2xl border border-rose-200 space-y-3">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="isQcHold" class="w-5 h-5 text-rose-600 rounded border-gray-300 focus:ring-rose-500">
                            <div>
                                <span class="text-sm font-black text-rose-900 block">Status QC HOLD (Kunci Pallet)</span>
                                <span class="text-[11px] text-rose-700 block">Jika di-centang, pallet tidak dapat dipilih untuk pengeluaran (outgoing).</span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Alasan Hold / QC Remark *</label>
                        <textarea wire:model="qcHoldReason" rows="3" placeholder="Contoh: Menunggu approval inspek QC, kontaminasi warna, lot bermasalah..." class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showQcHoldModal', false)" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan Status QC</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Relocation Modal -->
    @if ($showRelocateModal)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-up">
                <div class="p-6 bg-gray-900 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight">Relokasi Pallet</h3>
                        <p class="text-xs text-gray-400">Pindahkan Pallet <strong class="text-emerald-400 font-mono">{{ $relocatingPalletCode }}</strong> ke Slot Rak Baru</p>
                    </div>
                    <button wire:click="$set('showRelocateModal', false)" class="text-gray-400 hover:text-white transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <form wire:submit.prevent="saveRelocation" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Pilih Slot Rak Tujuan Baru *</label>
                        <select wire:model="newPositionId" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Slot Rak Baru --</option>
                            @foreach ($availablePositions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->position_code }} ({{ $pos->slot_label ?: 'Slot' }}) — Status: {{ $pos->status }}</option>
                            @endforeach
                        </select>
                        @error('newPositionId') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showRelocateModal', false)" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md transition">Simpan Relokasi</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
