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
                    <span class="w-3 h-3 bg-amber-500 rounded-full inline-block"></span>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Pengambilan Material (Outgoing / Outbound)</h1>
                </div>
                <p class="text-xs text-gray-500 mt-1">Form pemakaian material dengan rekomendasi FIFO (First In First Out) dan pengambilan sebagian (Partial Picking).</p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('mwh.qr-lookup') }}" class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Scan QR Camera</span>
                </a>
            </div>
        </div>

        <!-- Branch / Warehouse Switcher Bar -->
        <div class="bg-white p-3.5 rounded-2xl shadow-xs border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider pl-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    PILIH BRANCH OUTGOING:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($warehouses as $wh)
                        <button 
                            wire:click="$set('whse_id', {{ $wh['id'] }})"
                            style="{{ $whse_id == $wh['id'] ? 'background-color: #d97706 !important; color: #ffffff !important;' : 'background-color: #f3f4f6; color: #374151;' }}"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs {{ $whse_id == $wh['id'] ? 'bg-amber-600 text-white ring-2 ring-amber-400' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}"
                        >
                            <span>🏭</span>
                            <span style="{{ $whse_id == $wh['id'] ? 'color: #ffffff !important;' : 'color: #1f2937;' }}">{{ $wh['whse_name'] }}</span>
                            <span 
                                style="{{ $whse_id == $wh['id'] ? 'background-color: #b45309 !important; color: #fef3c7 !important;' : 'background-color: #e5e7eb; color: #4b5563;' }}"
                                class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $whse_id == $wh['id'] ? 'bg-amber-700 text-amber-100' : 'bg-gray-200 text-gray-600' }}"
                            >
                                {{ $wh['whse_code'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="text-xs text-gray-400 font-medium pr-2">
                Memfilter rekomendasi FIFO pallet dari lokasi branch terpilih.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

            <!-- Left Panel: Search Part Code & FIFO Recommendations -->
            <div class="md:col-span-5 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <h3 class="text-xs font-black text-amber-800 uppercase tracking-widest border-b border-gray-100 pb-2">1. Cari Material & Rekomendasi FIFO</h3>

                    <div class="relative">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cari Part Code / Nama Material</label>
                        <input type="text" wire:model.live="selected_item_code" placeholder="Misal: 180-CN890-FL..." class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-amber-500">
                        
                        @if (!empty($materialSearchResults))
                            <div class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 z-30 max-h-48 overflow-y-auto">
                                @foreach ($materialSearchResults as $res)
                                    <button type="button" wire:click="selectMaterial('{{ $res['item_code'] }}')" class="w-full text-left px-3 py-2 hover:bg-amber-50 transition border-b border-gray-50 flex flex-col">
                                        <span class="text-xs font-bold font-mono text-gray-900">{{ $res['item_code'] }}</span>
                                        <span class="text-[10px] text-gray-500 truncate">{{ $res['item_description'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($selected_item_description)
                        <div class="p-3 bg-amber-50/60 border border-amber-200/80 rounded-xl text-xs text-amber-900 font-medium">
                            <span class="font-bold block text-[11px]">Material Terpilih:</span>
                            <span class="font-mono font-bold">{{ $selected_item_code }}</span> — {{ $selected_item_description }}
                        </div>
                    @endif
                </div>

                <!-- FIFO Pallet Recommendations Panel -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h3 class="text-xs font-black text-emerald-800 uppercase tracking-widest flex items-center space-x-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Rekomendasi Pallet ID (FIFO)</span>
                        </h3>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">Terlama Masuk Duluan</span>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        @forelse ($fifoRecommendations as $fIndex => $f)
                            <div class="p-3.5 rounded-xl border {{ $selected_pallet_id === $f['pallet_id'] ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-400' : 'bg-gray-50 border-gray-200 hover:border-emerald-300' }} transition space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 bg-emerald-600 text-white rounded-full flex items-center justify-center text-[10px] font-black">#{{ $fIndex + 1 }}</span>
                                        <span class="font-mono font-black text-xs text-gray-900">{{ $f['pallet_id'] }}</span>
                                    </div>
                                    <span class="text-xs font-black text-emerald-700">{{ number_format($f['current_qty'], 2) }} KG</span>
                                </div>

                                <div class="text-[11px] text-gray-600 flex justify-between items-center font-mono">
                                    <span>Slot Rak: <strong>{{ $f['position']['position_code'] ?? '⏳ TEMPORARY' }}</strong></span>
                                    <span class="text-[10px] text-emerald-800 font-bold">Tgl Kedatangan: {{ isset($f['incoming_header']['arrival_date']) && $f['incoming_header']['arrival_date'] ? date('d M Y', strtotime($f['incoming_header']['arrival_date'])) : date('d M Y', strtotime($f['created_at'])) }}</span>
                                </div>

                                @if (!empty($f['is_qc_hold']))
                                    <div class="p-2 bg-rose-50 border border-rose-200 rounded-lg text-xs font-bold text-rose-800 flex items-center justify-between">
                                        <span class="flex items-center space-x-1">
                                            <span>⛔ PALLET DI-HOLD QC</span>
                                        </span>
                                        <span class="text-[10px] text-rose-600 font-medium italic">{{ $f['qc_hold_reason'] ?: 'Tanpa Alasan' }}</span>
                                    </div>
                                    <button type="button" disabled class="w-full py-1.5 px-3 bg-gray-300 text-gray-500 rounded-lg text-xs font-bold cursor-not-allowed flex items-center justify-center space-x-1">
                                        <span>Tidak Dapat Dipicking (QC Hold)</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="selectPalletForPicking('{{ $f['pallet_id'] }}')" class="w-full py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center space-x-1 shadow-sm">
                                        <span>Pilih Pallet Ini untuk Picking</span>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="p-6 text-center text-gray-400">
                                <p class="text-xs font-bold">
                                    @if ($selected_item_code)
                                        Tidak ada stok Pallet ID aktif untuk {{ $selected_item_code }}.
                                    @else
                                        Silakan cari Part Code material di atas untuk melihat rekomendasi FIFO.
                                    @endif
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Panel: Form Pengambilan Material Outgoing -->
            <div class="md:col-span-7 space-y-6">
                <form wire:submit.prevent="processPicking" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <h3 class="text-xs font-black text-amber-800 uppercase tracking-widest border-b border-gray-100 pb-2">2. Form Pengambilan Material Outgoing</h3>

                    @if ($selectedPallet)
                        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-amber-900 font-mono">PALLET TERPILIH: {{ $selectedPallet->pallet_id }}</span>
                                <span class="px-2.5 py-0.5 bg-amber-200 text-amber-900 rounded-full text-[10px] font-bold">Slot: {{ $selectedPallet->position ? $selectedPallet->position->position_code : '⏳ TEMPORARY (Area Transit)' }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div><span class="text-gray-500">Part Code:</span> <strong class="font-mono">{{ $selectedPallet->item_code }}</strong></div>
                                <div><span class="text-gray-500">Sisa Stok Pallet:</span> <strong class="text-emerald-700 font-bold text-sm">{{ number_format($selectedPallet->current_qty, 2) }} KG</strong></div>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Pallet ID Target *</label>
                            <input type="text" wire:model.live.debounce.300ms="selected_pallet_id" placeholder="Ketik atau Scan Pallet ID (MPLT-XXXXX)..." class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 uppercase focus:ring-2 focus:ring-amber-500">
                            @error('selected_pallet_id') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Jumlah Diambil (KG) *</label>
                                <div class="relative">
                                    <input type="number" step="0.01" wire:model="qty_taken" placeholder="Misal: 250" class="w-full pl-3 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-900 focus:ring-2 focus:ring-amber-500">
                                    <span class="absolute right-3 top-2.5 text-xs font-bold text-gray-400">KG</span>
                                </div>
                                @error('qty_taken') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Keluar *</label>
                                <input type="date" wire:model="outgoing_date" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:ring-2 focus:ring-amber-500">
                                @error('outgoing_date') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tujuan Divisi / Mesin / SPK (Opsional)</label>
                                <input type="text" wire:model="issued_to" placeholder="Misal: Mesin Moulding 05" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan Outgoing (Opsional)</label>
                                <input type="text" wire:model="remarks" placeholder="Catatan pemakaian..." class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 flex justify-end">
                        <button type="submit" class="w-full md:w-auto px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs shadow-lg shadow-amber-200 transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Proses Pengambilan Material (Outbound)</span>
                        </button>
                    </div>
                </form>

                <!-- History Outgoing Terbaru Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest">History Transaksi Outgoing Terbaru</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider">
                                    <th class="py-3 px-4">Kode Outgoing</th>
                                    <th class="py-3 px-4">Pallet ID</th>
                                    <th class="py-3 px-4">Part Code</th>
                                    <th class="py-3 px-4">Qty Keluar</th>
                                    <th class="py-3 px-4">Tujuan</th>
                                    <th class="py-3 px-4">Tgl Keluar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
                                @forelse ($outgoings as $out)
                                    <tr class="hover:bg-amber-50/40 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-gray-900">{{ $out->outgoing_code }}</td>
                                        <td class="py-3 px-4 font-mono text-emerald-800 font-bold">{{ $out->pallet_id }}</td>
                                        <td class="py-3 px-4 font-mono text-gray-800">{{ $out->item_code }}</td>
                                        <td class="py-3 px-4 font-black text-rose-600">-{{ number_format($out->qty_taken, 2) }} KG</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $out->issued_to ?: '-' }}</td>
                                        <td class="py-3 px-4 font-mono text-gray-500 text-[11px]">{{ $out->outgoing_date ? $out->outgoing_date->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-gray-400">Belum ada history transaksi outgoing.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 border-t border-gray-100">
                        {{ $outgoings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
