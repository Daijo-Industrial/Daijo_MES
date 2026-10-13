<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pallet Form History & Store Putaway</h1>
                <p class="text-gray-500">Scan barcode palet untuk verifikasi isi, rincian SPK, dan penempatan ke slot rak FG.</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('wms.pallet-form.lookup') }}" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-lg shadow-amber-100 transition-all flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    PALLET LOOKUP
                </a>
                <a href="{{ route('wms.pallet-form.create-delivery') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 transition-all flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    CREATE NEW PALLET
                </a>
            </div>
        </div>

        <!-- Flash Message Alerts -->
        @if (session()->has('success'))
            <div class="bg-emerald-500 text-white p-4 rounded-2xl shadow-md flex items-center justify-between animate-in fade-in duration-300">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✓</span>
                    <p class="text-sm font-bold tracking-wide">{{ session('success') }}</p>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white/80 hover:text-white font-bold text-lg">&times;</button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-rose-500 text-white p-4 rounded-2xl shadow-md flex items-center justify-between animate-in fade-in duration-300">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <p class="text-sm font-bold tracking-wide">{{ session('error') }}</p>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white/80 hover:text-white font-bold text-lg">&times;</button>
            </div>
        @endif

        @if (session()->has('info'))
            <div class="bg-blue-600 text-white p-4 rounded-2xl shadow-md flex items-center justify-between animate-in fade-in duration-300">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">ℹ️</span>
                    <p class="text-sm font-bold tracking-wide">{{ session('info') }}</p>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-white/80 hover:text-white font-bold text-lg">&times;</button>
            </div>
        @endif

        <!-- ================= STORE QUICK SCAN & PUTAWAY CARD ================= -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-slate-700/80 relative overflow-hidden space-y-6">
            
            <!-- Card Header & Scanner Strip -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-slate-700/60 pb-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/20 border border-blue-400/30 text-blue-400 flex items-center justify-center text-2xl shadow-inner shrink-0">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">
                                Store Quick Scan & Verifikasi Putaway
                            </h2>
                            <span class="px-2.5 py-0.5 bg-blue-500/20 border border-blue-400/40 text-blue-300 text-[10px] font-black rounded-full uppercase tracking-wider">
                                Mode Store / FG
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">
                            Scan barcode ID Pallet untuk verifikasi isi, kuantitas box, SPK, dan langsung menempatkan ke slot rak tujuan.
                        </p>
                    </div>
                </div>

                <!-- Scanner Controls & Tablet Camera Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:w-auto">
                    <!-- Tablet Camera Scanner Button -->
                    <button type="button" 
                            onclick="openTabletCameraScanner('pallet')"
                            class="px-4 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-2xl text-xs font-black transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/50 cursor-pointer shrink-0 border border-emerald-400/40">
                        <span class="text-base">📷</span>
                        <span>Buka Kamera Tab</span>
                    </button>

                    <!-- Livewire Scanner Input Box -->
                    <div class="w-full sm:w-[380px] lg:w-[420px] relative" x-data>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 text-slate-400 text-lg z-10">
                                ⚡
                            </span>
                            <input type="text" 
                                   wire:model.live.debounce.300ms="scanPalletId" 
                                   wire:keydown.enter="scanPallet"
                                   id="quick_scan_pallet_input"
                                   autofocus
                                   placeholder="Scan Barcode Pallet (PLT-...)"
                                   style="background-color: #ffffff !important; color: #0f172a !important;"
                                   class="w-full pl-11 pr-24 py-3.5 bg-white border-2 border-blue-400 focus:border-blue-600 rounded-2xl text-slate-900 font-mono font-black text-sm tracking-wide focus:ring-4 focus:ring-blue-100 outline-none transition placeholder:text-gray-400 shadow-sm">
                            
                            <div class="absolute right-2 flex items-center gap-1.5 z-10">
                                <div wire:loading wire:target="scanPalletId, scanPallet" class="pr-1">
                                    <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                                @if(!empty($scanPalletId))
                                    <button type="button" wire:click="clearScannedPallet" class="p-1.5 text-gray-400 hover:text-rose-600 font-bold text-sm" title="Clear input">
                                        ✕
                                    </button>
                                @endif
                                <button type="button" wire:click="scanPallet" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black transition shadow-xs cursor-pointer">
                                    CARI
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pallet Verification Panel (Active Scanned Pallet) -->
            @if($scannedPallet)
                <div class="bg-white text-slate-900 rounded-2xl p-5 sm:p-6 shadow-2xl border-2 border-blue-400 animate-in fade-in slide-in-from-top-4 duration-300 space-y-5">
                    
                    <!-- Panel Top Bar: Pallet ID, Badges, and Action Close -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-xs font-black uppercase text-gray-400 tracking-wider">Hasil Scan Verifikasi:</span>
                            <div class="text-xl font-black font-mono text-blue-600 bg-blue-50 px-3 py-1 rounded-xl border border-blue-200">
                                {{ $scannedPallet->pallet_id }}
                            </div>
                            @if($scannedPallet->status === 'OUT')
                                <span class="px-2.5 py-1 bg-red-100 text-red-800 text-xs font-black rounded-lg">OUT</span>
                            @elseif(!$scannedPallet->position_id)
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-300 text-xs font-black rounded-lg animate-pulse">
                                    ⏳ UNASSIGNED (BELUM ADA TEMPAT)
                                </span>
                            @else
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-black rounded-lg">
                                    ✓ STORED DI RAK {{ $scannedPallet->position?->position_code }}
                                </span>
                            @endif

                            @if($scannedPallet->is_overaged)
                                <span class="px-2.5 py-1 bg-rose-600 text-white text-[10px] font-black rounded-lg uppercase tracking-wider animate-pulse">
                                    ⚠️ {{ $scannedPallet->age_days }} HARI DI GUDANG
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button wire:click="clearScannedPallet" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>✕ Tutup / Scan Pallet Lain</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3-Column Verification & Placement Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                        
                        <!-- Column 1: Item & Delivery Details (4 cols) -->
                        <div class="lg:col-span-4 bg-slate-50 p-4 rounded-2xl border border-gray-200 space-y-3.5">
                            <div>
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider block mb-0.5">Item & Model</span>
                                <div class="text-base font-black text-gray-900 leading-tight">
                                    {{ $scannedPallet->part_no }}
                                </div>
                                <div class="text-xs text-gray-600 font-semibold mt-0.5">
                                    {{ $scannedPallet->model_name ?: 'No Model Description' }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200/80 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block">Customer</span>
                                    @php
                                        $scannedCusts = $scannedPallet->details->map(fn($d) => $d->item?->customer)->filter()->unique('customer_code');
                                    @endphp
                                    @if($scannedCusts->isNotEmpty())
                                        @foreach($scannedCusts as $c)
                                            <span class="font-bold text-gray-800 block truncate" title="{{ $c->customer_name }}">{{ $c->customer_name }}</span>
                                        @endforeach
                                    @else
                                        <span class="font-bold text-gray-400 italic">N/A</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block">No. Lot</span>
                                    <span class="font-mono font-bold text-gray-800">#{{ $scannedPallet->lot_no ?: '-' }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200/80 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block">Tanggal Prod</span>
                                    <span class="font-bold text-gray-700">{{ $scannedPallet->prod_date ? \Carbon\Carbon::parse($scannedPallet->prod_date)->format('d/m/Y') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block">Delivery / Shift</span>
                                    <span class="font-bold text-indigo-700 truncate block">{{ $scannedPallet->delivery_name ?: 'Delivery' }} (S{{ $scannedPallet->delivery_shift ?: '-' }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Quantity & Lead Time Summary (3 cols) -->
                        <div class="lg:col-span-3 bg-blue-50/60 p-4 rounded-2xl border border-blue-200 space-y-3.5">
                            <span class="text-[10px] font-black uppercase text-blue-900 tracking-wider block">Kuantitas & Box</span>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-baseline bg-white p-2.5 rounded-xl border border-blue-100 shadow-3xs">
                                    <span class="text-xs font-bold text-gray-500">Total Qty:</span>
                                    <span class="text-xl font-black text-blue-700 font-mono">{{ number_format($scannedPallet->total_pallet_qty, 0) }} <span class="text-xs font-bold text-gray-500">PCS</span></span>
                                </div>

                                <div class="flex justify-between items-baseline bg-white p-2.5 rounded-xl border border-blue-100 shadow-3xs">
                                    <span class="text-xs font-bold text-gray-500">Jumlah Box:</span>
                                    <span class="text-base font-black text-gray-800 font-mono">{{ $scannedPallet->box_qty }} <span class="text-xs font-bold text-gray-500">BOX</span></span>
                                </div>

                                <div class="flex justify-between items-baseline bg-indigo-50 p-2.5 rounded-xl border border-indigo-100 shadow-3xs text-xs">
                                    <span class="font-bold text-indigo-900">Lead Time:</span>
                                    <span class="font-black text-indigo-800 font-mono">⏱️ {{ $scannedPallet->putaway_lead_time }}</span>
                                </div>
                            </div>

                            <button type="button" wire:click="$toggle('showBoxDetails')" class="w-full py-2 bg-white hover:bg-blue-100 text-blue-700 border border-blue-300 rounded-xl text-xs font-black transition flex items-center justify-center gap-1.5 shadow-3xs cursor-pointer">
                                <span>📦 {{ $showBoxDetails ? 'Sembunyikan Rincian Box' : 'Lihat Rincian Box (' . $scannedPallet->details->count() . ' Box)' }}</span>
                                <span>{{ $showBoxDetails ? '▲' : '▼' }}</span>
                            </button>
                        </div>

                        <!-- Column 3: Destination Placement & Smart Recommendation (5 cols) -->
                        <div class="lg:col-span-5 bg-gradient-to-br from-gray-50 to-slate-100 p-4 rounded-2xl border-2 border-slate-300 space-y-3.5 shadow-2xs">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                <span class="text-xs font-black uppercase text-gray-900 tracking-tight flex items-center gap-1">
                                    <span>🎯</span>
                                    <span>Penempatan Slot Rak (Store)</span>
                                </span>
                                @if($scannedPallet->position)
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-black px-2 py-0.5 rounded-md border border-emerald-300">
                                        Saat ini: {{ $scannedPallet->position->position_code }}
                                    </span>
                                @else
                                    <span class="text-[10px] bg-amber-100 text-amber-900 font-black px-2 py-0.5 rounded-md border border-amber-300">
                                        Saat ini: Temporary
                                    </span>
                                @endif
                            </div>

                            <!-- Smart Recommendation Pill -->
                            @if($recommendedPosition)
                                <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl flex items-center justify-between gap-2 shadow-3xs">
                                    <div class="text-xs text-emerald-950">
                                        <span class="font-black text-[11px] block text-emerald-800 uppercase flex items-center gap-1">
                                            <span>💡</span> Rekomendasi Sistem:
                                        </span>
                                        <span class="font-black font-mono text-sm text-emerald-900">{{ $recommendedPosition->position_code }}</span>
                                        <span class="text-[10px] text-emerald-700">({{ $recommendedPosition->rack?->warehouse?->whse_code ?? 'WMS' }} - Status: {{ $recommendedPosition->status }})</span>
                                    </div>
                                    <button type="button" wire:click="applyRecommendation" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black transition shadow-xs shrink-0 cursor-pointer">
                                        ⚡ Gunakan
                                    </button>
                                </div>
                            @endif

                            <!-- Direct Barcode Scanner for Rack / Slot -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Scan Barcode Rak (Opsional):</label>
                                    <span class="text-[9px] text-gray-400">Otomatis membaca</span>
                                </div>
                                <div class="flex gap-2">
                                    <input type="text" 
                                           wire:model.live.debounce.300ms="scanRackCode" 
                                           wire:keydown.enter="scanRackPosition"
                                           placeholder="Scan Barcode Rak (misal: GUB-F25-L1-01)..."
                                           style="background-color: #ffffff !important; color: #0f172a !important;"
                                           class="w-full px-3 py-2 bg-white border-2 border-gray-300 focus:border-blue-500 rounded-xl text-xs font-mono font-black text-slate-900 focus:ring-2 focus:ring-blue-100 outline-none shadow-3xs placeholder:text-gray-400">
                                    <button type="button" 
                                            onclick="openTabletCameraScanner('rack')"
                                            class="px-2.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shrink-0 cursor-pointer flex items-center gap-1"
                                            title="Buka Kamera untuk scan barcode slot rak">
                                        <span>📷</span>
                                    </button>
                                    <button type="button" wire:click="scanRackPosition" class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold shrink-0 cursor-pointer">
                                        PILIH
                                    </button>
                                </div>
                            </div>

                            <!-- Warehouse & Slot Selector Dropdown -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Pilih Slot Rak Manual:</label>
                                    <!-- Warehouse Filter Pill -->
                                    <select wire:model.live="filterWarehouseId" class="text-[10px] bg-white border border-gray-300 rounded-lg px-2 py-0.5 font-bold text-gray-700 outline-none">
                                        <option value="">Semua Gudang</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->whse_code }} - {{ $wh->whse_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <select wire:model="selectedPositionId" class="w-full p-2.5 bg-white border-2 border-blue-400 rounded-xl text-xs font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none shadow-3xs">
                                    <option value="">⏳ TEMPORARY (Belum ada tempat / Unassigned)</option>
                                    @foreach ($availablePositions as $pos)
                                        <option value="{{ $pos->id }}">
                                            📍 {{ $pos->position_code }} [{{ $pos->rack?->warehouse?->whse_code ?? 'WMS' }}] (Status: {{ $pos->status }}, Cust: {{ $pos->customer_code ?: 'ALL' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Placement Action Buttons -->
                            <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                                <button type="button" 
                                        wire:click="saveAssignSlot" 
                                        class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition shadow-md flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>✓ SIMPAN & ASSIGN RAK</span>
                                </button>

                                <button type="button" 
                                        wire:click="unassignScannedPallet" 
                                        class="px-3 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black transition shadow-xs cursor-pointer"
                                        title="Set status pallet menjadi Temporary (Belum ada tempat)">
                                    <span>⏳ Temporary</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Box Breakdown Table -->
                    @if($showBoxDetails)
                        <div class="pt-4 border-t border-gray-200 space-y-2 animate-in fade-in duration-200">
                            <div class="flex justify-between items-center">
                                <h4 class="text-xs font-black text-gray-800 uppercase tracking-tight flex items-center gap-1.5">
                                    <span>📋</span> Rincian Box & SPK ({{ $scannedPallet->details->count() }} Box)
                                </h4>
                                <span class="text-[10px] text-gray-400 font-semibold">Pastikan data barcode box fisik sesuai dengan rincian berikut:</span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-gray-200 max-h-60 overflow-y-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-[10px] sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2">No</th>
                                            <th class="px-3 py-2">Barcode Box / Label</th>
                                            <th class="px-3 py-2">Part No / Model</th>
                                            <th class="px-3 py-2">No. SPK</th>
                                            <th class="px-3 py-2 text-right">Qty (Pcs)</th>
                                            <th class="px-3 py-2">Gudang</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($scannedPallet->details as $idx => $d)
                                            <tr class="hover:bg-blue-50/50">
                                                <td class="px-3 py-1.5 font-bold text-gray-400">{{ $idx + 1 }}</td>
                                                <td class="px-3 py-1.5 font-mono font-bold text-blue-700">{{ $d->label ?: ($d->is_no_label ? 'NO LABEL' : '-') }}</td>
                                                <td class="px-3 py-1.5 font-semibold text-gray-800">{{ $d->part_no }} <span class="text-gray-400">({{ $d->model_name }})</span></td>
                                                <td class="px-3 py-1.5 font-mono text-gray-600">{{ $d->spk_no ?: '-' }}</td>
                                                <td class="px-3 py-1.5 font-mono font-black text-right text-gray-900">{{ number_format($d->qty, 0) }}</td>
                                                <td class="px-3 py-1.5 text-gray-500 font-bold text-[10px]">{{ $d->warehouse ?: 'FG' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-3 py-4 text-center text-gray-400 italic">Tidak ada rincian box.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            @endif

        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari ID Palet, Part No, atau Delivery..." 
                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm font-semibold">
            </div>

            <div class="flex items-center space-x-3 w-full md:w-auto">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Filter Slot:</label>
                <select wire:model.live="filterSlot" class="py-2.5 px-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="ALL">Semua Pallet</option>
                    <option value="UNASSIGNED">⚠️ Belum Assign Slot (Delivery)</option>
                    <option value="ASSIGNED">Sudah Assign Slot</option>
                </select>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Pallet ID</th>
                            <th class="px-6 py-4">Delivery & Masuk Rak</th>
                            <th class="px-6 py-4">Part No / Model</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Qty</th>
                            <th class="px-6 py-4">Putaway Lead Time</th>
                            <th class="px-6 py-4">Rack Position (Store)</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($palletForms as $form)
                            <tr class="hover:bg-gray-50 transition-colors {{ $form->is_overaged ? 'bg-rose-50/40' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="font-bold text-gray-800 font-mono">{{ $form->pallet_id }}</div>
                                        @if($form->status === 'OUT')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-bold rounded">OUT</span>
                                        @elseif($form->is_overaged)
                                            <span class="px-2 py-0.5 bg-rose-600 text-white text-[10px] font-black rounded uppercase tracking-wider animate-pulse" title="Tersimpan selama {{ $form->age_days }} Hari di Gudang">
                                                ⚠️ {{ $form->age_days }} HARI DI GUDANG
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-green-100 text-green-800 text-[10px] font-bold rounded">STORED</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400">#{{ $form->lot_no ?: 'No Lot' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 space-y-0.5">
                                    <div class="font-bold text-gray-800">Scan: {{ $form->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</div>
                                    <div class="text-[11px] text-indigo-700 font-semibold">
                                        Rak: {{ $form->assigned_at ? $form->assigned_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum Assign' }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ $form->delivery_name ?: 'Delivery' }} (Shift {{ $form->delivery_shift ?: '-' }})</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800">{{ $form->part_no }}</div>
                                    <div class="text-xs text-gray-500">{{ $form->model_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $customers = $form->details->map(fn($d) => $d->item?->customer)->filter()->unique('customer_code');
                                    @endphp
                                    @if($customers->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($customers as $cust)
                                                <div class="text-xs font-bold text-gray-800" title="{{ $cust->customer_code }}">
                                                    {{ $cust->customer_name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">No Customer</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-blue-600">{{ number_format($form->total_pallet_qty, 0) }} pcs</div>
                                    <div class="text-xs text-gray-400">{{ $form->box_qty }} Boxes</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-800 font-black rounded-lg inline-block border border-indigo-200">
                                        ⏱️ {{ $form->putaway_lead_time }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                     @if($form->position && $form->total_pallet_qty > 0 && $form->status !== 'OUT')
                                         <div class="flex items-center space-x-2">
                                             <span class="px-3 py-1 bg-gray-800 text-white text-xs font-bold rounded-lg uppercase tracking-tight">
                                                 {{ $form->position->position_code }}
                                             </span>
                                             <button wire:click="openAssignModal('{{ $form->pallet_id }}')" class="text-gray-400 hover:text-blue-600 p-1" title="Ubah Slot Rak">
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                             </button>
                                         </div>
                                     @elseif($form->total_pallet_qty > 0 && $form->status !== 'OUT')
                                         <div class="flex items-center space-x-2">
                                             <span class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black rounded-lg uppercase tracking-tight">
                                                 ⏳ TEMPORARY (BELUM ADA TEMPAT)
                                             </span>
                                             <button wire:click="openAssignModal('{{ $form->pallet_id }}')" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-lg shadow-sm transition-all cursor-pointer">
                                                 Assign Slot
                                             </button>
                                         </div>
                                     @else
                                         <span class="px-2.5 py-1 bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold rounded-lg uppercase tracking-tight">
                                             📦 STOK HABIS (0 QTY)
                                         </span>
                                     @endif
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end space-x-2">
                                    <a href="{{ route('wms.pallet-form.print', ['id' => $form->pallet_id]) }}" target="_blank"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-lg text-xs font-bold transition-all">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        REPRINT
                                    </a>
                                    <button wire:click="deletePallet('{{ $form->pallet_id }}')" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus pallet {{ $form->pallet_id }}? Data ini tidak bisa dikembalikan."
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg text-xs font-bold transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        DELETE
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-20 text-center text-gray-400 italic">
                                    Belum ada data pallet form yang tersimpan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($palletForms->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    {{ $palletForms->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Store Assign Slot Modal (Fallback Modal) -->
    @if ($showAssignModal && $scannedPallet)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight flex items-center gap-1.5">
                        <span>🎯</span> Assign Slot Rak FG (Store)
                    </h3>
                    <button wire:click="$set('showAssignModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="space-y-4">
                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-900 space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-bold">Pallet ID:</span>
                            <strong class="font-mono text-blue-950 font-black text-sm">{{ $scannedPallet->pallet_id }}</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-bold">Part No / Item:</span>
                            <strong class="text-gray-900 font-black">{{ $scannedPallet->part_no }} ({{ number_format($scannedPallet->total_pallet_qty) }} pcs)</strong>
                        </div>
                    </div>

                    @if($recommendedPosition)
                        <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl flex items-center justify-between gap-2">
                            <div class="text-xs">
                                <span class="font-bold text-emerald-800 text-[10px] block">💡 REKOMENDASI SISTEM:</span>
                                <span class="font-black font-mono text-emerald-900">{{ $recommendedPosition->position_code }}</span>
                            </div>
                            <button type="button" wire:click="applyRecommendation" class="px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold">
                                Pakai
                            </button>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Pilih Slot Rak WMS:</label>
                        <select wire:model="assignPositionId" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">⏳ TEMPORARY (Belum ada tempat / Unassigned)</option>
                            @foreach ($availablePositions as $pos)
                                <option value="{{ $pos->id }}">
                                    📍 {{ $pos->position_code }} ({{ $pos->rack?->warehouse?->whse_code ?? 'WMS' }} - Cust: {{ $pos->customer_code ?: 'ALL' }}, Status: {{ $pos->status }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-gray-100">
                    <button wire:click="$set('showAssignModal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs cursor-pointer">Batal</button>
                    <button wire:click="saveAssignSlot" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md cursor-pointer">Simpan Assignment</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Tablet Camera Scanner Modal Viewport -->
    <div id="tabCameraModal" class="hidden fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border-2 border-blue-500/80 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl space-y-4 p-5 sm:p-6 text-white animate-in fade-in zoom-in-95 duration-200">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl border border-emerald-400/30">
                        📷
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase text-white tracking-wide" id="camModalTitle">Kamera Barcode / QR Scanner</h3>
                        <p class="text-[11px] text-slate-400">Arahkan kamera tablet ke barcode pada lembar palet atau rak</p>
                    </div>
                </div>
                <button type="button" onclick="closeTabletCameraScanner()" class="text-slate-400 hover:text-white font-bold text-2xl cursor-pointer p-1">&times;</button>
            </div>

            <!-- Video Viewport with Aiming Reticle & Animated Laser Sweep -->
            <div class="relative bg-black rounded-2xl overflow-hidden aspect-4/3 flex items-center justify-center border-2 border-slate-700 shadow-inner">
                <video id="tabCameraVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                
                <!-- Aiming Reticle & Animated Laser Line Overlay -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-4/5 h-4/5 border-2 border-emerald-400/70 rounded-2xl relative shadow-[0_0_20px_rgba(52,211,153,0.3)]">
                        <!-- Red Laser Scanning Beam -->
                        <div class="absolute inset-x-2 top-1/2 h-0.5 bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,1)] animate-pulse"></div>
                        <!-- Corner Markers -->
                        <div class="absolute -top-1 -left-1 w-5 h-5 border-t-4 border-l-4 border-emerald-400 rounded-tl"></div>
                        <div class="absolute -top-1 -right-1 w-5 h-5 border-t-4 border-r-4 border-emerald-400 rounded-tr"></div>
                        <div class="absolute -bottom-1 -left-1 w-5 h-5 border-b-4 border-l-4 border-emerald-400 rounded-bl"></div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 border-b-4 border-r-4 border-emerald-400 rounded-br"></div>
                    </div>
                </div>

                <!-- Camera Loading Spinner -->
                <div id="camLoadingIndicator" class="absolute inset-0 bg-slate-950/80 flex flex-col items-center justify-center space-y-2.5">
                    <div class="w-9 h-9 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-bold text-slate-300">Menghubungkan ke kamera tablet...</span>
                </div>
            </div>

            <!-- Bottom Camera Controls -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-slate-800 text-xs">
                <span id="camStatusText" class="text-slate-300 font-bold truncate">🎥 Menunggu pembacaan barcode...</span>
                <div class="flex gap-2 shrink-0">
                    <button type="button" onclick="switchTabletCamera()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold flex items-center justify-center gap-1.5 border border-slate-700 cursor-pointer">
                        <span>🔄 Ganti Kamera</span>
                    </button>
                    <button type="button" onclick="closeTabletCameraScanner()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold cursor-pointer">
                        ✕ Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ZXing MultiFormat Barcode & QR Code Reader -->
    <script src="https://unpkg.com/@zxing/browser@latest"></script>

    <!-- Scanner & Camera Controller Script -->
    <script>
        let zxingReader = null;
        let currentCameraTarget = 'pallet'; // 'pallet' or 'rack'
        let selectedCameraDeviceId = null;
        let availableVideoDevices = [];

        async function openTabletCameraScanner(target = 'pallet') {
            currentCameraTarget = target;
            const modal = document.getElementById('tabCameraModal');
            const videoElem = document.getElementById('tabCameraVideo');
            const loadingElem = document.getElementById('camLoadingIndicator');
            const titleElem = document.getElementById('camModalTitle');
            const statusElem = document.getElementById('camStatusText');

            if (!modal || !videoElem) return;

            if (titleElem) {
                titleElem.innerText = target === 'pallet' 
                    ? '📷 Scan Barcode Pallet (Tablet Camera)' 
                    : '📷 Scan Barcode Slot Rak (Tablet Camera)';
            }

            modal.classList.remove('hidden');
            if (loadingElem) loadingElem.classList.remove('hidden');
            if (statusElem) statusElem.innerText = 'Mengakses kamera tablet...';

            try {
                if (typeof ZXingBrowser === 'undefined') {
                    alert('Library scanner sedang dimuat, silakan klik lagi dalam 2 detik.');
                    closeTabletCameraScanner();
                    return;
                }

                zxingReader = new ZXingBrowser.BrowserMultiFormatReader();
                
                // Get all video input devices (front/rear cameras)
                const videoDevices = await ZXingBrowser.BrowserCodeReader.listVideoInputDevices();
                availableVideoDevices = videoDevices || [];

                if (availableVideoDevices.length === 0) {
                    alert('Tidak ada kamera yang terdeteksi pada perangkat ini.');
                    closeTabletCameraScanner();
                    return;
                }

                // Prioritize back/rear camera for tablets & phones
                if (!selectedCameraDeviceId) {
                    const backCam = availableVideoDevices.find(d => 
                        d.label.toLowerCase().includes('back') || 
                        d.label.toLowerCase().includes('rear') || 
                        d.label.toLowerCase().includes('belakang') ||
                        d.label.toLowerCase().includes('environment')
                    );
                    selectedCameraDeviceId = backCam ? backCam.deviceId : availableVideoDevices[availableVideoDevices.length - 1].deviceId;
                }

                if (loadingElem) loadingElem.classList.add('hidden');
                if (statusElem) statusElem.innerText = '🎥 Kamera aktif. Arahkan ke barcode...';

                await zxingReader.decodeFromVideoDevice(selectedCameraDeviceId, videoElem, (result, error) => {
                    if (result) {
                        const text = result.getText().trim();
                        if (text) {
                            if (statusElem) statusElem.innerText = '✓ Terbaca: ' + text;
                            
                            // Audio beep
                            window.dispatchEvent(new CustomEvent('scan-success'));

                            // Pass to Livewire
                            if (currentCameraTarget === 'pallet') {
                                @this.set('scanPalletId', text);
                                @this.scanPallet();
                            } else {
                                @this.set('scanRackCode', text);
                                @this.scanRackPosition();
                            }

                            closeTabletCameraScanner();
                        }
                    }
                });
            } catch(err) {
                if (statusElem) statusElem.innerText = 'Gagal mengakses kamera: ' + err.message;
                alert('Gagal membuka kamera: ' + err.message + '\nPastikan izin akses kamera di browser tablet sudah di-Allow.');
                closeTabletCameraScanner();
            }
        }

        function closeTabletCameraScanner() {
            const modal = document.getElementById('tabCameraModal');
            const videoElem = document.getElementById('tabCameraVideo');
            if (zxingReader) {
                try {
                    if (videoElem && videoElem.srcObject) {
                        videoElem.srcObject.getTracks().forEach(track => track.stop());
                        videoElem.srcObject = null;
                    }
                } catch(e) {}
                zxingReader = null;
            }
            if (modal) modal.classList.add('hidden');
        }

        async function switchTabletCamera() {
            if (availableVideoDevices.length <= 1) {
                alert('Hanya ada 1 kamera yang terdeteksi di perangkat ini.');
                return;
            }

            const currentIndex = availableVideoDevices.findIndex(d => d.deviceId === selectedCameraDeviceId);
            const nextIndex = (currentIndex + 1) % availableVideoDevices.length;
            selectedCameraDeviceId = availableVideoDevices[nextIndex].deviceId;

            closeTabletCameraScanner();
            setTimeout(() => {
                openTabletCameraScanner(currentCameraTarget);
            }, 200);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const playSound = (freq, duration, type = 'sine') => {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = type;
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + duration);
                } catch(e) {}
            };

            window.addEventListener('scan-success', () => {
                playSound(880, 0.12, 'sine'); // High pleasing beep
            });

            window.addEventListener('scan-error', () => {
                playSound(300, 0.15, 'sawtooth');
                setTimeout(() => playSound(220, 0.2, 'sawtooth'), 120); // Low error double beep
            });

            window.addEventListener('focus-scan-input', () => {
                setTimeout(() => {
                    const el = document.getElementById('quick_scan_pallet_input');
                    if (el) {
                        el.focus();
                        el.select();
                    }
                }, 100);
            });
        });
    </script>
</div>

