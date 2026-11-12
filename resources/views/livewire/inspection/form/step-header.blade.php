<div class="space-y-6" x-data="{ openPartDropdown: @entangle('show_part_dropdown') }">
    {{-- Header Status Bar --}}
    <div class="flex items-center justify-between">
        @if ($isSaved)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Tersimpan di Sesi
                @if ($savedAt)
                    <span class="text-emerald-700/80 font-normal">({{ \Illuminate\Support\Carbon::parse($savedAt)->format('H:i:s') }})</span>
                @endif
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Data belum disimpan ke sesi
            </span>
        @endif

        <span class="text-xs font-mono text-zinc-500">
            No Dokumen: <strong class="text-zinc-900">{{ $document_number }}</strong>
        </span>
    </div>

    {{-- 1. General & Logistics --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                1. General Information
            </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Tanggal Inspeksi <span class="text-rose-600">*</span>
                </label>
                <input type="date"
                       wire:model.blur="inspection_date"
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('inspection_date') border-rose-300 bg-rose-50/50 @enderror">
                @error('inspection_date')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Customer <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       list="customerList"
                       wire:model.blur="customer"
                       placeholder="Pilih atau ketik Customer..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('customer') border-rose-300 bg-rose-50/50 @enderror">
                <datalist id="customerList">
                    @foreach ($customerSuggestions as $cust)
                        <option value="{{ $cust }}"></option>
                    @endforeach
                </datalist>
                @error('customer')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Shift Produksi <span class="text-rose-600">*</span>
                </label>
                <select wire:model.live="shift"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('shift') border-rose-300 bg-rose-50/50 @enderror">
                    <option value="">-- Pilih Shift --</option>
                    <option value="1">Shift 1 (07:30 - 15:30)</option>
                    <option value="2">Shift 2 (15:30 - 23:30)</option>
                    <option value="3">Shift 3 (23:30 - 07:30)</option>
                </select>
                @error('shift')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- 2. Part Information & Search Dropdown --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                2. Part Details & Spesifikasi
            </h2>
            <span class="text-xs text-zinc-500 font-mono">Pilih dari Master List Item</span>
        </div>

        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Searchable Part Number with Auto-complete Popover --}}
            <div class="relative sm:col-span-2 lg:col-span-1" @click.away="openPartDropdown = false">
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Cari & Pilih Part Number <span class="text-rose-600">*</span>
                </label>
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.250ms="part_search"
                           @focus="openPartDropdown = true"
                           placeholder="Ketik kode part atau nama..."
                           class="w-full rounded-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 pr-9 min-h-[42px] @error('part_number') border-rose-300 bg-rose-50/50 @enderror">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Hidden input keeping part_number validated --}}
                <input type="hidden" wire:model="part_number">

                {{-- Dropdown Results --}}
                @if ($show_part_dropdown && !empty($searchResults))
                    <div class="absolute left-0 right-0 mt-1 bg-white border border-zinc-300 rounded-lg shadow-xl max-h-60 overflow-y-auto z-30 divide-y divide-zinc-100"
                         x-show="openPartDropdown"
                         x-transition.opacity>
                        @forelse ($searchResults as $item)
                            @php
                                $custName = $item->customer?->customer_name ?: ($item->businessPartner?->bp_name ?: 'N/A');
                            @endphp
                            <button type="button"
                                    wire:click="selectPartItem('{{ $item->item_code }}')"
                                    class="w-full px-3.5 py-2.5 text-left hover:bg-zinc-100 transition flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="block text-xs font-mono font-bold text-zinc-900 truncate">{{ $item->item_code }}</span>
                                    <span class="block text-[11px] text-zinc-600 truncate">{{ $item->item_name }}</span>
                                </div>
                                <span class="text-[10px] font-semibold text-zinc-500 uppercase bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200 flex-shrink-0">
                                    {{ $custName }}
                                </span>
                            </button>
                        @empty
                            <div class="p-3 text-xs text-zinc-500 text-center">
                                Tidak ada part yang cocok.
                            </div>
                        @endforelse
                    </div>
                @endif

                @error('part_number')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Part Name <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="part_name"
                       placeholder="Nama komponen / part..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('part_name') border-rose-300 bg-rose-50/50 @enderror">
                @error('part_name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Material <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="material"
                       placeholder="Contoh: ABS, PP, POM..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('material') border-rose-300 bg-rose-50/50 @enderror">
                @error('material')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Color <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="color"
                       placeholder="Contoh: BLACK, NATURAL, GREY..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('color') border-rose-300 bg-rose-50/50 @enderror">
                @error('color')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Berat Part (Weight) <span class="text-rose-600">*</span>
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="number"
                           step="any"
                           wire:model.blur="weight"
                           placeholder="0.00"
                           class="flex-1 rounded-none rounded-l-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('weight') border-rose-300 bg-rose-50/50 @enderror">
                    <select wire:model.blur="weight_uom"
                            class="inline-flex items-center rounded-none rounded-r-lg border-l-0 border-zinc-300 bg-zinc-100 text-zinc-800 text-xs font-semibold px-3 focus:border-zinc-900 focus:ring-zinc-900">
                        <option value="g">Gram (g)</option>
                        <option value="kg">Kilogram (kg)</option>
                    </select>
                </div>
                @error('weight')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Tool / Cavity Number <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="tool_number_or_cav_number"
                       placeholder="Contoh: 1, 2, Cav 1-4..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('tool_number_or_cav_number') border-rose-300 bg-rose-50/50 @enderror">
                @error('tool_number_or_cav_number')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- 3. Machine & Manpower --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200">
            <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                3. Mesin & Personil
            </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Nomor Mesin (Line/Machine) <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="machine_number"
                       placeholder="Contoh: M-01, INJ-05..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('machine_number') border-rose-300 bg-rose-50/50 @enderror">
                @error('machine_number')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Nama Operator <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       wire:model.blur="operator"
                       placeholder="Nama operator..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('operator') border-rose-300 bg-rose-50/50 @enderror">
                @error('operator')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Nama Inspector (QC) <span class="text-rose-600">*</span>
                </label>
                <select wire:model.blur="inspector"
                        class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('inspector') border-rose-300 bg-rose-50/50 @enderror">
                    <option value="">-- Pilih Inspector --</option>
                    @foreach ($inspectorOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('inspector')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Bottom Navigation Action Bar --}}
    <div class="flex items-center justify-end pt-2">
        <button type="button"
                wire:click="saveStep"
                class="inline-flex items-center gap-2 px-6 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
            <span>Simpan & Lanjut ke Detail Periode</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </div>
</div>