<div class="p-6 bg-gray-50 min-h-screen" 
     x-data="palletFormCreator({
         initialPlant: '{{ $plant }}',
         initialScans: @js($scanned_items)
     })"
     x-init="init()"
     x-on:scan-success.window="onScanSuccess($event)"
     x-on:scan-error.window="onScanError($event)"
     @if($showSuccessModal && $sapSyncStatus === 'pending') wire:poll.2s="checkSapSyncStatus" @endif>
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <div class="flex items-center gap-2.5 mb-1 flex-wrap" wire:ignore>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $isDelivery ? 'Generate Pallet Form (Delivery)' : 'Generate Pallet Form' }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black border"
                          :class="plant === 'KARAWANG' ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                          x-text="'PLANT ' + plant">
                    </span>
                </div>
                <p class="text-gray-500 text-sm" wire:ignore>
                    Scan box dan lengkapi detail palet. Alur Transfer SAP: 
                    <strong class="font-mono font-bold"
                            :class="plant === 'KARAWANG' ? 'text-blue-700' : 'text-amber-700'"
                            x-text="plant === 'KARAWANG' ? 'KRFFI ➔ KRFG' : 'FFI ➔ FG'">
                    </strong>
                </p>
            </div>
            <div class="flex items-center space-x-2 flex-wrap">
                <a href="{{ route('wms.pallet-form.daily-recap') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold flex items-center transition-all shadow-md shadow-emerald-100">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    REKAP HARIAN DELIVERY
                </a>
                <a href="{{ route('wms.outbound') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold flex items-center transition-all shadow-lg shadow-red-100">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    OUTBOUND SCAN
                </a>
                <a href="{{ route('wms.mapping') }}" class="px-4 py-2 bg-slate-800 hover:bg-black text-white rounded-xl text-sm font-semibold flex items-center transition-all">
                    <svg class="w-4 h-4 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    WAREHOUSE MAPPING
                </a>
                <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-semibold">Gudang J06</span>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl flex items-start space-x-3 mb-4">
                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-green-700 font-bold">{{ session('success') }}</p>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl flex items-start space-x-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-red-700 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <div x-show="localScanError" x-cloak class="bg-red-100 border-l-4 border-red-500 p-4 rounded-xl flex items-center justify-between shadow-sm mb-4">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-red-800 font-bold text-sm" x-text="localScanError"></p>
            </div>
            <button type="button" @click="localScanError = ''" class="text-red-600 hover:text-red-800 font-black text-xs px-2.5 py-1 bg-white rounded-lg border border-red-200">
                ✕ TUTUP
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: Header Form --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Pallet Details
                    </h2>

                    {{-- Plant Selection (KBN vs Karawang) --}}
                    <div class="mb-5 p-3.5 bg-gray-50 border border-gray-200 rounded-2xl" wire:ignore>
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Pilih Plant / Alur Gudang</span>
                            <span class="text-[10px] font-black"
                                  :class="plant === 'KARAWANG' ? 'text-blue-600' : 'text-amber-600'"
                                  x-text="plant === 'KARAWANG' ? 'KARAWANG (KRFFI ➔ KRFG)' : 'KBN (FFI ➔ FG)'">
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="setPlant('KBN')"
                                    class="py-2.5 px-3 rounded-xl text-xs font-black transition-all flex flex-col items-center justify-center border cursor-pointer"
                                    :class="plant === 'KBN' ? 'bg-amber-500 text-white border-amber-600 shadow-md shadow-amber-200 ring-2 ring-amber-300' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 hover:border-gray-300'">
                                <span class="flex items-center gap-1.5 text-sm font-extrabold">
                                    <span>🏭</span>
                                    <span>Plant KBN</span>
                                </span>
                                <span class="text-[10px] font-bold opacity-90 mt-0.5 font-mono">FFI ➔ FG</span>
                            </button>
                            <button type="button"
                                    @click="setPlant('KARAWANG')"
                                    class="py-2.5 px-3 rounded-xl text-xs font-black transition-all flex flex-col items-center justify-center border cursor-pointer"
                                    :class="plant === 'KARAWANG' ? 'bg-blue-600 text-white border-blue-700 shadow-md shadow-blue-200 ring-2 ring-blue-300' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 hover:border-gray-300'">
                                <span class="flex items-center gap-1.5 text-sm font-extrabold">
                                    <span>🏭</span>
                                    <span>Plant Karawang</span>
                                </span>
                                <span class="text-[10px] font-bold opacity-90 mt-0.5 font-mono">KRFFI ➔ KRFG</span>
                            </button>
                        </div>
                        <div class="mt-2.5 text-[11px] leading-snug p-2.5 rounded-xl border flex items-center gap-2 font-medium"
                             :class="plant === 'KARAWANG' ? 'text-blue-900 bg-blue-50/80 border-blue-200' : 'text-amber-900 bg-amber-50/80 border-amber-200'">
                            <span class="text-base flex-shrink-0" x-text="plant === 'KARAWANG' ? '🔵' : '🟡'"></span>
                            <span x-show="plant === 'KARAWANG'">
                                <strong>Plant Karawang aktif:</strong> Box akan disimpan ke gudang <strong>KRFG</strong> dan transfer SAP otomatis diproses dari <strong>KRFFI ➔ KRFG</strong>.
                            </span>
                            <span x-show="plant === 'KBN'">
                                <strong>Plant KBN aktif:</strong> Box akan disimpan ke gudang <strong>FG</strong> dan transfer SAP otomatis diproses dari <strong>FFI ➔ FG</strong>.
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Prod Date</label>
                                <input type="date" wire:model="prod_date"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                @error('prod_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Shift</label>
                                <select wire:model="delivery_shift" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    <option value="">Pilih Shift</option>
                                    <option value="1">Shift 1 (07:30 - 15:30)</option>
                                    <option value="2">Shift 2 (15:30 - 23:30)</option>
                                    <option value="3">Shift 3 (23:30 - 07:30)</option>
                                </select>
                                @error('delivery_shift') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Lot No. / MO</label>
                            <input type="text" wire:model="lot_no" placeholder="Opsional"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Delivery Name</label>
                            <input type="text" wire:model="delivery_name" placeholder="Nama pengirim..."
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            @error('delivery_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Remarks</label>
                            <textarea wire:model="remarks" placeholder="Catatan tambahan (opsional)..." rows="2"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                        </div>

                        {{-- Auto-calculated totals --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 text-center">
                                <div class="text-xs text-blue-500 font-semibold uppercase mb-1">Total Box</div>
                                <div class="text-2xl font-black text-blue-800">{{ $total_box }}</div>
                            </div>
                            <div class="p-3 bg-green-50 rounded-xl border border-green-100 text-center">
                                <div class="text-xs text-green-500 font-semibold uppercase mb-1">Total Qty</div>
                                <div class="text-2xl font-black text-green-800">{{ number_format($total_pallet_qty, 0) }}</div>
                            </div>
                        </div>

                        {{-- Multi-item indicator --}}
                        @php
                            $uniqueParts = collect($scanned_items)->pluck('part_no')->filter()->unique()->values();
                        @endphp
                        @if($uniqueParts->count() > 1)
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                <div class="text-xs font-bold text-amber-700 uppercase mb-1">⚡ Multi-Item Pallet</div>
                                <div class="text-xs text-amber-600">{{ $uniqueParts->implode(', ') }}</div>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-100" x-data="{ isSubmitting: false }">
                            <button 
                                @click="if (isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true; setTimeout(() => isSubmitting = false, 4000);"
                                wire:click.throttle.2000ms="generateForm(plant)" 
                                type="button"
                                wire:loading.attr="disabled"
                                wire:target="generateForm"
                                :disabled="isSubmitting || @js($isProcessing)"
                                class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 transition-all active:scale-95 flex items-center justify-center disabled:bg-gray-400 disabled:shadow-none text-sm uppercase">
                                <span wire:loading.remove wire:target="generateForm">GENERATE PALLET FORM</span>
                                <span wire:loading wire:target="generateForm">PROCESSING...</span>
                                <svg wire:loading.remove wire:target="generateForm" class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Excel Scan Sheet --}}
            <div class="lg:col-span-2 space-y-6" wire:ignore>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                📊 Excel Scan Sheet
                            </h2>
                            <p class="text-xs text-gray-400">Scan label barcode pada baris aktif. Nilai SPK, Qty, dan Whse otomatis menurun ke baris baru.</p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="addNoLabelRow()" type="button"
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold rounded-xl transition-all shadow-md">
                                ➕ TANPA LABEL (MANUAL)
                            </button>
                            <button @click="initScans([])" type="button"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                                🔄 RESET SHEET
                            </button>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-y-auto overflow-x-auto max-h-[350px] custom-scrollbar">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-wider sticky top-0 border-b border-gray-200 z-10">
                                <tr>
                                    <th class="px-3 py-3 w-12 text-center border-r border-gray-200">No</th>
                                    <th class="px-3 py-3 w-48 border-r border-gray-200">SPK Code</th>
                                    <th class="px-3 py-3 w-56 border-r border-gray-200">Part No & Model</th>
                                    <th class="px-3 py-3 w-28 text-right border-r border-gray-200">Qty</th>
                                    <th class="px-3 py-3 w-28 text-center border-r border-gray-200">Whse</th>
                                    <th class="px-3 py-3 w-64 border-r border-gray-200">Label Barcode (Scan Here)</th>
                                    <th class="px-3 py-3 text-right">Status & Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(item, idx) in localScans" :key="item.cid">
                                    <tr class="hover:bg-blue-50/10 transition-colors border-b border-gray-100"
                                        :class="{
                                            'bg-blue-50/20 animate-pulse border-l-4 border-blue-400': item.status === 'syncing',
                                            'bg-green-50/10 border-l-4 border-green-500': item.status === 'success',
                                            'bg-red-50/20 border-l-4 border-red-500': item.status === 'error',
                                            'bg-yellow-50/20 border-l-4 border-amber-400': item.status === 'new'
                                        }">
                                        {{-- Row Number --}}
                                        <td class="px-3 py-2 text-center text-gray-400 font-mono text-xs border-r border-gray-100 bg-gray-50/50" x-text="idx + 1"></td>

                                        {{-- SPK Code --}}
                                        <td class="px-3 py-2 border-r border-gray-100">
                                            <input type="text"
                                                   :id="'spk_input_' + idx"
                                                   x-model="item.spk_no"
                                                   @input="onSpkInput(item)"
                                                   @keydown.tab="handleSpkTab($event, item)"
                                                   @keydown.enter.prevent="handleSpkEnter(item)"
                                                   :disabled="item.status !== 'new'"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   placeholder="SPK Code"
                                                   class="w-full px-2 py-1 border border-gray-200 rounded-lg outline-none text-xs font-bold text-gray-700 bg-gray-50/30 focus:bg-white disabled:bg-gray-100/50 disabled:border-transparent transition-all uppercase">
                                        </td>

                                        {{-- Part No & Model --}}
                                        <td class="px-3 py-2 border-r border-gray-100 text-xs">
                                            <div class="flex flex-col justify-center min-h-[32px]">
                                                <span class="font-black text-gray-700 leading-none" x-text="item.part_no || '—'"></span>
                                                <span class="text-gray-400 text-[10px] truncate max-w-[200px] mt-0.5" x-text="item.model_name || '—'"></span>
                                            </div>
                                        </td>

                                        {{-- Quantity --}}
                                        <td class="px-3 py-2 border-r border-gray-100 text-right">
                                            <input type="number"
                                                   :id="'qty_input_' + idx"
                                                   :value="item.qty"
                                                   @change="updateQty(item, $event.target.value)"
                                                   @keydown.enter.prevent="handleQtyEnter(item)"
                                                   :disabled="item.status === 'syncing'"
                                                   autocomplete="off"
                                                   class="w-20 px-2 py-1 text-right border border-gray-200 rounded-lg outline-none text-xs font-bold text-gray-700 bg-gray-50/30 focus:bg-white disabled:bg-gray-100/50 transition-all">
                                        </td>

                                        {{-- Warehouse --}}
                                        <td class="px-3 py-2 border-r border-gray-100 text-center">
                                            <input type="text"
                                                   :id="'whse_input_' + idx"
                                                   x-model="item.warehouse"
                                                   @change="updateWhse(item, $event.target.value)"
                                                   @keydown.enter.prevent="handleWhseEnter(item)"
                                                   :disabled="item.status === 'syncing'"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   placeholder="Whse"
                                                   class="w-16 px-1 py-1 text-center border border-gray-200 rounded-lg outline-none text-xs font-bold text-gray-700 bg-gray-50/30 focus:bg-white disabled:bg-gray-100/50 disabled:border-transparent transition-all uppercase">
                                        </td>

                                        {{-- Label Barcode --}}
                                        <td class="px-3 py-2 border-r border-gray-100">
                                            <div x-show="item.status === 'new'">
                                                <input type="text"
                                                       :id="'label_input_' + idx"
                                                       x-model="item.label"
                                                       @input="onLabelInput(item)"
                                                       @change="onLabelChange(item)"
                                                       @keydown.tab="handleLabelKey($event, item)"
                                                       @keydown.enter="handleLabelKey($event, item)"
                                                       autocomplete="off"
                                                       autocorrect="off"
                                                       autocapitalize="off"
                                                       spellcheck="false"
                                                       placeholder="Scan barcode label..."
                                                       class="w-full px-2 py-1.5 border border-blue-300 rounded-lg outline-none text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:bg-white bg-blue-50/30 transition-all uppercase">
                                            </div>
                                            <div x-show="item.status !== 'new'">
                                                <div class="flex items-center space-x-1.5">
                                                    <template x-if="item.label === null">
                                                        <span class="text-orange-600 font-bold italic text-xs">🚫 No Label</span>
                                                    </template>
                                                    <template x-if="item.label !== null">
                                                        <span class="font-mono font-bold text-gray-700" x-text="item.label"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Status & Actions --}}
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <template x-if="item.status === 'success'">
                                                    <span class="text-green-600 font-bold text-xs">✅ OK</span>
                                                </template>

                                                {{-- Error --}}
                                                <template x-if="item.status === 'error'">
                                                    <div class="flex flex-col items-end group relative cursor-help">
                                                        <span class="text-red-600 font-black text-xs">⚠️ ERR</span>
                                                        <span class="text-red-500 text-[9px] italic leading-none max-w-[120px] truncate" x-text="item.error"></span>
                                                    </div>
                                                </template>

                                                {{-- New Row Indicator --}}
                                                <template x-if="item.status === 'new'">
                                                    <span class="text-amber-500 font-bold text-xs animate-pulse">📝 EDITING</span>
                                                </template>

                                                {{-- Delete --}}
                                                <template x-if="item.status !== 'new'">
                                                    <button @click="removeScan(item)" type="button"
                                                            class="p-1.5 bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-600 rounded-lg transition-colors border border-transparent">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile View of Scanned Items --}}
                    <div class="block md:hidden space-y-4 max-h-[400px] overflow-y-auto p-2 border-t border-gray-100">
                        <template x-for="(item, idx) in localScans" :key="item.cid">
                            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3 relative transition-all"
                                :class="{
                                    'border-blue-400 ring-2 ring-blue-100': item.status === 'syncing',
                                    'border-green-400': item.status === 'success',
                                    'border-red-400': item.status === 'error',
                                    'border-amber-400 bg-amber-50/5': item.status === 'new'
                                }">
                                <div class="flex justify-between items-center">
                                    <span class="px-2 py-0.5 bg-gray-100 rounded-lg text-xs font-black text-gray-500 font-mono" x-text="'No. ' + (idx + 1)"></span>
                                    <div class="flex space-x-2">
                                        <template x-if="item.status === 'new' || item.status === 'error'">
                                            <button type="button" @click="removeLocalScan(item.cid)" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-lg transition-all">
                                                HAPUS
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase">SPK Code</label>
                                    <input type="text"
                                           :id="'spk_input_mobile_' + idx"
                                           x-model="item.spk_no"
                                           @input="onSpkInput(item)"
                                           @keydown.tab="handleSpkTab($event, item)"
                                           @keydown.enter.prevent="handleSpkEnter(item)"
                                           :disabled="item.status !== 'new'"
                                           autocomplete="off"
                                           autocorrect="off"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           placeholder="SPK Code"
                                           class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 disabled:bg-gray-100 transition-all uppercase">
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase block">Part & Model</span>
                                        <span class="font-black text-gray-800 block" x-text="item.part_no || '—'"></span>
                                        <span class="text-[10px] text-gray-400 block truncate max-w-[150px]" x-text="item.model_name || '—'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase block">Qty</span>
                                            <input type="number"
                                                   :value="item.qty"
                                                   @change="updateQty(item, $event.target.value)"
                                                   @keydown.enter.prevent="handleQtyEnter(item)"
                                                   :disabled="item.status === 'syncing'"
                                                   autocomplete="off"
                                                   class="w-full px-2 py-1 border border-gray-200 rounded-lg outline-none text-xs font-bold text-gray-700">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase block">Whse</span>
                                            <div class="mt-1">
                                                <input type="text"
                                                       :id="'whse_input_mobile_' + idx"
                                                       x-model="item.warehouse"
                                                       @change="updateWhse(item, $event.target.value)"
                                                       @keydown.enter.prevent="handleWhseEnter(item)"
                                                       :disabled="item.status === 'syncing'"
                                                       autocomplete="off"
                                                       autocorrect="off"
                                                       autocapitalize="off"
                                                       spellcheck="false"
                                                       placeholder="Whse"
                                                       class="w-full px-1 py-1 text-center border border-gray-200 rounded-lg outline-none text-xs font-bold text-gray-700 uppercase">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase">Label Barcode</label>
                                    <div x-show="item.status === 'new'">
                                        <input type="text"
                                               :id="'label_input_mobile_' + idx"
                                               x-model="item.label"
                                               @input="onLabelInput(item, $event)"
                                               @change="onLabelChange(item)"
                                               @keydown.tab="handleLabelKey($event, item)"
                                               @keydown.enter="handleLabelKey($event, item)"
                                               autocomplete="off"
                                               autocorrect="off"
                                               autocapitalize="off"
                                               spellcheck="false"
                                               placeholder="Scan barcode label..."
                                               class="w-full px-2 py-1.5 border border-blue-300 rounded-lg text-xs font-mono font-bold uppercase focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div x-show="item.status !== 'new'">
                                        <div class="flex items-center space-x-1.5">
                                            <template x-if="item.label === null">
                                                <span class="text-orange-600 font-bold italic text-xs">🚫 No Label</span>
                                            </template>
                                            <template x-if="item.label !== null">
                                                <span class="font-mono font-bold text-xs text-gray-700" x-text="item.label"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Summary Table Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                📊 Scan Summary
                            </h2>
                            <p class="text-xs text-gray-400">Total akumulasi scan per SPK dan Gudang.</p>
                        </div>
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-black uppercase tracking-wider rounded-xl border border-blue-100">
                            Grouped by SPK
                        </span>
                    </div>

                    <div class="overflow-y-auto overflow-x-auto max-h-[300px] custom-scrollbar">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-wider sticky top-0 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 border-r border-gray-200">SPK Code</th>
                                    <th class="px-4 py-3 border-r border-gray-200">Part No & Model</th>
                                    <th class="px-4 py-3 text-right border-r border-gray-200">Total Qty</th>
                                    <th class="px-4 py-3 text-center border-r border-gray-200">Whse</th>
                                    <th class="px-4 py-3 text-center border-r border-gray-200">Total Boxes</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="summary in getSummaryData()" :key="summary.spk_no + '_' + summary.warehouse">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-mono font-bold text-gray-800 border-r border-gray-100" x-text="summary.spk_no"></td>
                                        <td class="px-4 py-3 border-r border-gray-100">
                                            <div class="flex flex-col justify-center">
                                                <span class="font-black text-gray-700 text-xs" x-text="summary.part_no || '—'"></span>
                                                <span class="text-gray-400 text-[10px] mt-0.5 truncate max-w-[200px]" x-text="summary.model_name || '—'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-black text-blue-600 text-right border-r border-gray-100" x-text="summary.qty"></td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-600 border-r border-gray-100" x-text="summary.warehouse"></td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-600 border-r border-gray-100" x-text="summary.boxes"></td>
                                        <td class="px-4 py-3 text-right">
                                            <button @click="removeSummaryGroup(summary)" type="button"
                                                class="px-2 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 text-xs font-bold rounded-xl transition-all border border-red-100 flex items-center justify-center space-x-1 ml-auto shadow-sm active:scale-95">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Hapus Grup</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="getSummaryData().length === 0">
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic text-xs bg-gray-50/50">
                                            Belum ada data scan yang terdaftar.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

    <script>
        function palletFormCreator(config) {
            return {
                plant: config.initialPlant,
                currentWarehouse: (config.initialPlant === 'KARAWANG') ? 'KRFG' : 'FG',
                localScans: [],
                localScanError: '',
                _scanTimer: null,

                init() {
                    this.initScans(config.initialScans || []);
                    this.focusActiveSpk();
                    this.$watch('$wire.scanned_items', (newItems) => {
                        if (!newItems || newItems.length === 0) {
                            this.initScans([]);
                            this.focusActiveSpk();
                            return;
                        }

                        // Auto-sync part_no, model_name, and status directly from Livewire array
                        newItems.forEach(serverItem => {
                            let local = this.localScans.find(l => l.cid === serverItem.cid);
                            if (local) {
                                if (serverItem.part_no) local.part_no = serverItem.part_no;
                                if (serverItem.model_name) local.model_name = serverItem.model_name;
                                if (serverItem.qty) local.qty = serverItem.qty;
                                if (serverItem.warehouse) local.warehouse = serverItem.warehouse;
                                local.status = 'success';
                            }
                        });
                    });
                },

                getWire() {
                    return this.$wire || (window.Livewire ? window.Livewire.find('{{ $_instance->getId() }}') : null);
                },

                setPlant(newPlant) {
                    this.plant = newPlant;
                    this.currentWarehouse = (newPlant === 'KARAWANG') ? 'KRFG' : 'FG';
                    const wire = this.getWire();
                    if (wire) wire.setPlant(newPlant);
                },

                initScans(items) {
                    this.localScans = (items || []).map(item => ({
                        cid: item.cid || 'c_' + Math.random().toString(36).substr(2, 5),
                        label: item.label,
                        spk_no: item.spk_no,
                        part_no: item.part_no,
                        model_name: item.model_name,
                        qty: item.qty,
                        warehouse: item.warehouse || '',
                        status: 'success',
                        error: null
                    }));
                    this._addEmptyRow();
                },

                focusActiveSpk() {
                    const doFocus = () => {
                        let activeIdx = this.localScans.map(r => r.status).lastIndexOf('new');
                        if (activeIdx < 0) {
                            activeIdx = this.localScans.length - 1;
                        }
                        if (activeIdx >= 0) {
                            const isMobile = window.innerWidth < 768;
                            const el = isMobile 
                                ? (document.getElementById('spk_input_mobile_' + activeIdx) || document.getElementById('spk_input_' + activeIdx))
                                : (document.getElementById('spk_input_' + activeIdx) || document.getElementById('spk_input_mobile_' + activeIdx));
                            if (el && !el.disabled) {
                                el.focus();
                                el.select();
                                return true;
                            }
                        }
                        return false;
                    };

                    if (doFocus()) return;

                    this.$nextTick(() => {
                        if (doFocus()) return;
                        setTimeout(doFocus, 25);
                        setTimeout(doFocus, 75);
                        setTimeout(doFocus, 150);
                        setTimeout(doFocus, 300);
                    });
                },

                focusActiveLabel() {
                    const doFocus = () => {
                        let activeIdx = this.localScans.map(r => r.status).lastIndexOf('new');
                        if (activeIdx < 0) {
                            activeIdx = this.localScans.length - 1;
                        }
                        if (activeIdx >= 0) {
                            const isMobile = window.innerWidth < 768;
                            const el = isMobile 
                                ? (document.getElementById('label_input_mobile_' + activeIdx) || document.getElementById('label_input_' + activeIdx))
                                : (document.getElementById('label_input_' + activeIdx) || document.getElementById('label_input_mobile_' + activeIdx));
                            if (el && !el.disabled) {
                                el.focus();
                                el.select();
                                return true;
                            }
                        }
                        return false;
                    };

                    if (doFocus()) return;

                    this.$nextTick(() => {
                        if (doFocus()) return;
                        setTimeout(doFocus, 25);
                        setTimeout(doFocus, 75);
                        setTimeout(doFocus, 150);
                        setTimeout(doFocus, 300);
                    });
                },

                _getRow(itemOrIdx) {
                    if (itemOrIdx === null || itemOrIdx === undefined) return null;
                    if (typeof itemOrIdx === 'object') return itemOrIdx;
                    return this.localScans[itemOrIdx] || null;
                },

                _getRowIdx(itemOrIdx) {
                    if (typeof itemOrIdx === 'number') return itemOrIdx;
                    if (typeof itemOrIdx === 'object' && itemOrIdx !== null) {
                        return this.localScans.findIndex(r => r.cid === itemOrIdx.cid);
                    }
                    return -1;
                },

                _addEmptyRow() {
                    let lastRow = null;
                    for (let i = this.localScans.length - 1; i >= 0; i--) {
                        if (this.localScans[i].status !== 'new' && this.localScans[i].spk_no) {
                            lastRow = this.localScans[i];
                            break;
                        }
                    }

                    let nextSpk = lastRow ? lastRow.spk_no : '';
                    let nextQty = lastRow ? lastRow.qty : '';
                    let nextWhse = lastRow ? lastRow.warehouse : (this.currentWarehouse || '');

                    this.localScans.push({
                        cid: 'c_active_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                        label: '',
                        spk_no: nextSpk,
                        qty: nextQty,
                        warehouse: nextWhse,
                        part_no: '',
                        model_name: '',
                        status: 'new',
                        error: null
                    });

                    // Fokus selalu kembali ke SPK Code baris baru
                    this.focusActiveSpk();
                },

                _resetActiveRow(itemOrIdx) {
                    let row = this._getRow(itemOrIdx);
                    if (row && row.status === 'new') {
                        row.label = '';
                        row.spk_no = '';
                        row.qty = '';
                        row.warehouse = '';
                    }
                    this.focusActiveSpk();
                },

                handleCompositeBarcode(val, itemOrIdx) {
                    if (!val || !val.includes('\t')) return false;
                    let parts = val.split('\t').map(s => s.trim());
                    // Format Barcode: [SPK] \t [Qty] \t [Warehouse] \t [Label] (\t)
                    if (parts.length >= 4 && parts[0]) {
                        let row = this._getRow(itemOrIdx);
                        if (!row || row.status !== 'new') return false;

                        row.spk_no = parts[0];
                        row.qty = parseFloat(parts[1]) || 0;
                        row.warehouse = parts[2] || '';
                        row.label = parts[3] || '';

                        // Sinkronkan plant secara otomatis dari warehouse barcode
                        if (row.warehouse) {
                            let whUpper = row.warehouse.toUpperCase();
                            if (whUpper.startsWith('KR') || whUpper.includes('KARAWANG')) {
                                if (this.plant !== 'KARAWANG') {
                                    this.setPlant('KARAWANG');
                                }
                            } else if (whUpper === 'FG' || whUpper === 'FFI') {
                                if (this.plant !== 'KBN') {
                                    this.setPlant('KBN');
                                }
                            }
                        }

                        this.$nextTick(() => {
                            this.commitScan(row);
                        });
                        return true;
                    }
                    return false;
                },

                onSpkInput(itemOrIdx) {
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;
                    let val = row.spk_no || '';

                    if (val.includes('\t')) {
                        if (this.handleCompositeBarcode(val, row)) {
                            return;
                        }
                        let parts = val.split('\t').map(s => s.trim());
                        if (parts.length >= 2 && parts[0]) {
                            row.spk_no = parts[0];
                            if (parts[1]) row.qty = parseFloat(parts[1]) || row.qty;
                            let idx = this._getRowIdx(itemOrIdx);
                            let next = (idx >= 0) ? (document.getElementById('whse_input_' + idx) || document.getElementById('whse_input_mobile_' + idx)) : null;
                            if (next) { next.focus(); next.select(); }
                            return;
                        }
                    }
                },

                handleSpkTab(e, itemOrIdx) {
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;
                    if (row.spk_no && row.spk_no.includes('\t')) {
                        e.preventDefault();
                        this.handleCompositeBarcode(row.spk_no, row);
                    }
                },

                handleSpkEnter(itemOrIdx) {
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;
                    if (this.handleCompositeBarcode(row.spk_no, row)) {
                        return;
                    }
                    if (row.spk_no && row.label) {
                        this.commitScan(row);
                        return;
                    }
                    let idx = this._getRowIdx(itemOrIdx);
                    let el = (idx >= 0) ? (document.getElementById('qty_input_' + idx) || document.getElementById('qty_input_mobile_' + idx)) : null;
                    if (el) { el.focus(); el.select(); }
                },

                handleQtyEnter(itemOrIdx) {
                    let idx = this._getRowIdx(itemOrIdx);
                    let el = (idx >= 0) ? (document.getElementById('whse_input_' + idx) || document.getElementById('whse_input_mobile_' + idx)) : null;
                    if (el) { el.focus(); el.select(); }
                },

                handleWhseEnter(itemOrIdx) {
                    let idx = this._getRowIdx(itemOrIdx);
                    let el = (idx >= 0) ? (document.getElementById('label_input_' + idx) || document.getElementById('label_input_mobile_' + idx)) : null;
                    if (el) { el.focus(); el.select(); }
                },

                handleLabelKey(e, itemOrIdx) {
                    e.preventDefault();
                    clearTimeout(this._scanTimer);
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;

                    let idx = this._getRowIdx(itemOrIdx);
                    let inputEl = e.target || (idx >= 0 ? (document.getElementById('label_input_' + idx) || document.getElementById('label_input_mobile_' + idx)) : null);
                    let currentVal = inputEl ? inputEl.value : row.label;
                    if (currentVal) {
                        row.label = currentVal.trim();
                    }

                    this.commitScan(row);
                },

                onLabelChange(itemOrIdx) {
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;
                    let idx = this._getRowIdx(itemOrIdx);
                    let inputEl = (idx >= 0) ? (document.getElementById('label_input_' + idx) || document.getElementById('label_input_mobile_' + idx)) : null;
                    if (inputEl && inputEl.value) {
                        row.label = inputEl.value.trim();
                    }
                    if (row.label) {
                        this.commitScan(row);
                    }
                },

                onLabelInput(itemOrIdx, e) {
                    clearTimeout(this._scanTimer);
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;

                    let idx = this._getRowIdx(itemOrIdx);
                    let inputEl = (e && e.target) || (idx >= 0 ? (document.getElementById('label_input_' + idx) || document.getElementById('label_input_mobile_' + idx)) : null);
                    let val = (inputEl ? inputEl.value : row.label) || '';

                    if (val.includes('\t')) {
                        if (this.handleCompositeBarcode(val, row)) {
                            return;
                        }
                        // Trailing tab pada label barcode
                        row.label = val.replace(/\t/g, '').trim();
                        this.$nextTick(() => {
                            this.commitScan(row);
                        });
                        return;
                    }

                    let trimmed = val.trim();
                    if (trimmed.length >= 1) {
                        this._scanTimer = setTimeout(() => {
                            this.commitScan(row);
                        }, 500);
                    }
                },

                commitScan(itemOrIdx) {
                    clearTimeout(this._scanTimer);
                    let row = this._getRow(itemOrIdx);
                    if (!row || row.status !== 'new') return;

                    let idx = this._getRowIdx(itemOrIdx);
                    let labelInputEl = (idx >= 0) ? (document.getElementById('label_input_' + idx) || document.getElementById('label_input_mobile_' + idx)) : null;
                    let spkInputEl = (idx >= 0) ? (document.getElementById('spk_input_' + idx) || document.getElementById('spk_input_mobile_' + idx)) : null;
                    let qtyInputEl = (idx >= 0) ? (document.getElementById('qty_input_' + idx) || document.getElementById('qty_input_mobile_' + idx)) : null;
                    let whseInputEl = (idx >= 0) ? (document.getElementById('whse_input_' + idx) || document.getElementById('whse_input_mobile_' + idx)) : null;

                    let labelVal = (row.label || (labelInputEl ? labelInputEl.value : '') || '').replace(/\t/g, '').trim();
                    let spkVal = (row.spk_no || (spkInputEl ? spkInputEl.value : '') || '').replace(/\t/g, '').trim();
                    let qtyVal = parseFloat(row.qty !== '' ? row.qty : (qtyInputEl ? qtyInputEl.value : 0)) || 0;
                    let whseVal = (row.warehouse || (whseInputEl ? whseInputEl.value : '') || '').replace(/\t/g, '').trim();

                    if (!labelVal || !spkVal) return;

                    // Client-side duplicate check
                    let isDuplicate = this.localScans.some(r => r.cid !== row.cid && r.label === labelVal && r.spk_no === spkVal);
                    if (isDuplicate) {
                        this.localScanError = 'Label [' + labelVal + '] dengan SPK ini sudah terdaftar.';
                        if (typeof window.playTone === 'function') {
                            window.playTone(100, 400, 'square', 0.6);
                            setTimeout(() => window.playTone(100, 400, 'square', 0.6), 500);
                        }
                        this._resetActiveRow(row);
                        return;
                    }

                    this.localScanError = '';
                    row.status = 'syncing';
                    row.label = labelVal;
                    row.spk_no = spkVal;
                    row.qty = qtyVal;
                    row.warehouse = whseVal;

                    // 1. Tambah baris baru di bawah (mewariskan SPK, Qty, Whse)
                    this._addEmptyRow();

                    // 2. Kirim ke Livewire di background
                    const wire = this.getWire();
                    if (wire) wire.addItem(labelVal, spkVal, qtyVal, whseVal, row.cid);
                },

                removeScan(item) {
                    this.localScans = this.localScans.filter(i => i.cid !== item.cid);
                    const wire = this.getWire();
                    if (wire) wire.removeItemByCid(item.cid);
                    this.focusActiveSpk();
                },

                removeLocalScan(cid) {
                    let item = this.localScans.find(i => i.cid === cid);
                    if (item) this.removeScan(item);
                },

                updateQty(item, newQty) {
                    item.qty = parseFloat(newQty) || 0;
                    const wire = this.getWire();
                    if (wire) wire.updateQtyByCid(item.cid, newQty);
                },

                updateWhse(item, newWhse) {
                    item.warehouse = newWhse;
                    const wire = this.getWire();
                    if (wire) wire.updateWhseByCid(item.cid, newWhse);
                },

                updateLabel(item, newLabel) {
                    item.label = newLabel;
                    const wire = this.getWire();
                    if (wire) wire.updateLabelByCid(item.cid, newLabel);
                },

                addNoLabelRow() {
                    let lastRow = this.localScans[this.localScans.length - 1];
                    let spk = lastRow ? lastRow.spk_no : '';
                    let qty = lastRow ? lastRow.qty : 50;
                    let whse = this.currentWarehouse;
                    
                    const wire = this.getWire();
                    if (wire) {
                        wire.set('label_mode', 'NO_LABEL');
                        wire.set('scan_qty', qty);
                        wire.set('scan_box_count', 1);
                        wire.set('no_label_reason', 'Box Lama');
                    }
                    
                    let cid = 'c_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    
                    this.localScans.splice(this.localScans.length - 1, 0, {
                        cid: cid,
                        label: null,
                        spk_no: spk,
                        part_no: 'Loading...',
                        model_name: 'Syncing...',
                        qty: qty,
                        warehouse: whse,
                        status: 'syncing',
                        error: null
                    });
                    
                    if (wire) wire.addItem('', '', qty, whse, cid);
                    this.focusActiveSpk();
                },

                getSummaryData() {
                    let groups = {};
                    this.localScans.forEach(row => {
                        if (row.status === 'new') return;
                        let key = (row.spk_no || '').trim() + '||' + (row.warehouse || '').trim();
                        if (!groups[key]) {
                            groups[key] = {
                                spk_no: row.spk_no,
                                warehouse: row.warehouse,
                                part_no: row.part_no,
                                model_name: row.model_name,
                                qty: 0,
                                boxes: 0
                            };
                        }
                        groups[key].qty += parseFloat(row.qty) || 0;
                        groups[key].boxes += 1;
                        if (row.part_no && row.part_no !== 'Loading...') {
                            groups[key].part_no = row.part_no;
                        }
                        if (row.model_name && row.model_name !== 'Syncing...') {
                            groups[key].model_name = row.model_name;
                        }
                    });
                    return Object.values(groups);
                },

                removeSummaryGroup(summary) {
                    let toRemove = this.localScans.filter(i => 
                        (i.spk_no || '').trim() === summary.spk_no && 
                        (i.warehouse || '').trim() === summary.warehouse && 
                        i.status !== 'new'
                    );
                    
                    this.localScans = this.localScans.filter(i => {
                        let match = (i.spk_no || '').trim() === summary.spk_no && 
                                    (i.warehouse || '').trim() === summary.warehouse && 
                                    i.status !== 'new';
                        return !match;
                    });
                    
                    const wire = this.getWire();
                    if (wire) {
                        toRemove.forEach(i => {
                            wire.removeItemByCid(i.cid);
                        });
                    }
                    this.focusActiveSpk();
                },

                _extractEventData(event) {
                    let d = event ? event.detail : null;
                    if (Array.isArray(d) && d.length > 0) return d[0];
                    if (d && typeof d === 'object') {
                        if (d.cid !== undefined || d.message !== undefined) return d;
                        if (d[0] && typeof d[0] === 'object') return d[0];
                    }
                    return d || {};
                },

                onScanSuccess(event) {
                    let data = this._extractEventData(event);
                    let targetCid = data.cid;
                    let item = targetCid ? this.localScans.find(i => i.cid === targetCid) : null;
                    if (!item) {
                        item = this.localScans.find(i => i.status === 'syncing');
                    }
                    if (item) {
                        item.status = 'success';
                        if (data.part_no) item.part_no = data.part_no;
                        if (data.model_name) item.model_name = data.model_name;
                    }
                    // Kalau baris aktif sudah ada SPK-nya (inherit), fokus langsung ke label
                    let activeRow = this.localScans.find(r => r.status === 'new');
                    if (activeRow && activeRow.spk_no) {
                        this.focusActiveLabel();
                    } else {
                        this.focusActiveSpk();
                    }
                },

                onScanError(event) {
                    let data = this._extractEventData(event);
                    let targetCid = data.cid;
                    let errItem = targetCid ? this.localScans.find(i => i.cid === targetCid) : null;
                    if (!errItem) {
                        errItem = this.localScans.find(i => i.status === 'syncing');
                    }
                    if (errItem) {
                        this.localScans = this.localScans.filter(i => i.cid !== errItem.cid);
                    }
                    this.localScanError = data.message || 'Scan error';
                    if (typeof window.playTone === 'function') {
                        window.playTone(100, 400, 'square', 0.6);
                        setTimeout(() => window.playTone(100, 400, 'square', 0.6), 500);
                    }
                    this.focusActiveSpk();
                }
            };
        }
        window.palletFormCreator = palletFormCreator;

        document.addEventListener('livewire:init', () => {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
            window.playTone = function(frequency, duration, type = 'sine', volume = 0.1) {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.type = type;
                oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
                
                gainNode.gain.setValueAtTime(volume, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration/1000);

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                oscillator.start();
                oscillator.stop(audioCtx.currentTime + duration/1000);
            }

            Livewire.on('scan-success', () => {
                window.playTone(1200, 200, 'sawtooth', 0.4); 
                setTimeout(() => window.playTone(1500, 100, 'sawtooth', 0.3), 50); 
            });

            Livewire.on('scan-error', () => {
                window.playTone(100, 400, 'square', 0.6); 
                setTimeout(() => window.playTone(100, 400, 'square', 0.6), 500);
            });
        });
    </script>

    <style>
        .bg-blue-600 input:focus, .bg-orange-500 input:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.35);
        }
    </style>

    {{-- Success Modal --}}
    @if($showSuccessModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-300">
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-in zoom-in-95 duration-300 flex flex-col max-h-[90vh]">
                <div class="p-8 text-center flex-1 overflow-y-auto custom-scrollbar">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter italic">PALLET CREATED!</h3>
                    <p class="text-gray-500 mb-6 text-sm">Pallet ID: <span class="font-black text-blue-600 tracking-tight">{{ $lastGeneratedPalletId }}</span></p>
                    
                    {{-- SAP Sync Status Section --}}
                    <div class="mb-8 p-4 rounded-2xl border-2 {{ $sapSyncStatus === 'pending' ? 'bg-blue-50 border-blue-100' : (empty($failedSapItems) ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100') }} transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-black uppercase tracking-widest {{ $sapSyncStatus === 'pending' ? 'text-blue-500' : (empty($failedSapItems) ? 'text-green-600' : 'text-red-600') }}">
                                SAP Integration Status
                            </span>
                            @if($sapSyncStatus === 'pending')
                                <div class="flex items-center space-x-1">
                                    <div class="w-1 h-1 bg-blue-400 rounded-full animate-bounce"></div>
                                    <div class="w-1 h-1 bg-blue-400 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                    <div class="w-1 h-1 bg-blue-400 rounded-full animate-bounce [animation-delay:-0.5s]"></div>
                                </div>
                            @endif
                        </div>

                        @if($sapSyncStatus === 'pending')
                            <p class="text-xs font-bold text-blue-700 italic">Sinkronisasi ke SAP sedang berjalan di background...</p>
                        @elseif(empty($failedSapItems))
                            <p class="text-xs font-bold text-green-700">✅ Berhasil! Semua data terkirim ke SAP tanpa kendala.</p>
                        @else
                            <div class="space-y-3">
                                <p class="text-xs font-black text-red-700 uppercase tracking-tighter">⚠️ Ada {{ count($failedSapItems) }} SPK yang gagal terkirim ke SAP:</p>
                                <div class="overflow-hidden border border-red-200 rounded-xl">
                                    <table class="w-full text-left text-[10px]">
                                        <thead class="bg-red-100 text-red-700 font-black uppercase tracking-widest">
                                            <tr>
                                                <th class="px-3 py-2">SPK No</th>
                                                <th class="px-3 py-2">Error Message</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-100 bg-white">
                                            @foreach($failedSapItems as $failed)
                                                <tr>
                                                    <td class="px-3 py-2 font-black text-gray-800">{{ $failed['spk_no'] }}</td>
                                                    <td class="px-3 py-2 font-bold text-red-600 italic break-words">{{ $failed['sap_error_msg'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button wire:click="retrySapSync" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-red-200">
                                    RETRY SAP SYNC
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ route('wms.pallet-form.print', $lastGeneratedPalletId) }}" target="_blank" 
                            class="block py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 transition-all active:scale-95 text-sm uppercase">
                            Print Label
                        </a>
                        <button wire:click="resetWholeForm" type="button"
                            class="block py-4 bg-slate-800 hover:bg-black text-white font-bold rounded-2xl transition-all active:scale-95 text-sm uppercase">
                            Next Scan
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 px-8 py-4 text-center">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">SAP Batch Processing v2.0</span>
                </div>
            </div>
        </div>
    @endif
</div>
