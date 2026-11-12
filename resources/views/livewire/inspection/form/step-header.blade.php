<div class="space-y-6" x-data="{ openPartDropdown: @entangle('show_part_dropdown'), openCustomerDropdown: @entangle('show_customer_dropdown'), openMachineDropdown: @entangle('show_machine_dropdown') }">
    {{-- Header Status Bar --}}
    <div class="flex items-center justify-between">
        @if ($isSaved)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Tersimpan di Sesi
                @if ($savedAt)
                    <span class="text-emerald-700/80 font-normal">({{ \Illuminate\Support\Carbon::parse($savedAt)->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i:s \W\I\B') }})</span>
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

    {{-- Main 2-Column Split Form Container --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm relative">
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-zinc-200">
            {{-- KOLOM KIRI: 1. Part & Spesifikasi (Input Terisi Otomatis) --}}
            <div class="p-6 space-y-5 relative z-20">
                <div class="pb-3 border-b border-zinc-100">
                    <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                        1. Part & Spesifikasi
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Pilih part untuk memuat master data, customer, mesin, & spesifikasi.</p>
                </div>

                {{-- Dedicated Search Toolbar (Terpisah dari field input yang disimpan ke backend) --}}
                <div class="relative pb-3 border-b border-zinc-100" @click.away="openPartDropdown = false">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-zinc-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Cari Part Number / Part Name (Master List)</span>
                        </label>
                        @if (!empty($part_number) && !$isSearchingAgain)
                            <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                Part Terpilih
                            </span>
                        @endif
                    </div>

                    @if (!empty($part_number) && !$isSearchingAgain)
                        {{-- Last Selected Option Display (Instead of Search Idle Input) --}}
                        <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-zinc-900 text-white flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-sm text-zinc-900 truncate">{{ $part_number }}</span>
                                        @if (!empty($customer))
                                            <span class="text-[10px] font-semibold text-zinc-600 uppercase bg-zinc-200/70 px-1.5 py-0.5 rounded border border-zinc-300 flex-shrink-0">
                                                {{ $customer }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-zinc-600 truncate mt-0.5">{{ $part_name }}</p>
                                </div>
                            </div>
                            <button type="button"
                                    wire:click="startSearchAgain"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 hover:text-zinc-900 rounded-lg shadow-sm transition flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Ganti Part</span>
                            </button>
                        </div>
                    @else
                        {{-- Active Search Input --}}
                        <div class="relative">
                            <input type="text"
                                   wire:model.live.debounce.250ms="part_search"
                                   @focus="openPartDropdown = true"
                                   placeholder="Ketik kode part (misal: 12345-ABC) atau nama part..."
                                   class="w-full rounded-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 pl-10 pr-20 min-h-[44px]">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-zinc-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                @if (!empty($part_search))
                                    <button type="button"
                                            wire:click="clearPartSelection"
                                            class="p-1 text-zinc-400 hover:text-zinc-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                                @if (!empty($part_number) && $isSearchingAgain)
                                    <button type="button"
                                            wire:click="cancelSearchAgain"
                                            class="px-2 py-1 text-xs font-semibold text-zinc-600 hover:text-zinc-900 bg-zinc-100 hover:bg-zinc-200 rounded border border-zinc-200 transition">
                                        Batal
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Dropdown Search Results --}}
                        @if ($show_part_dropdown && !empty($searchResults))
                            <div class="absolute left-0 right-0 mt-1 bg-white border border-zinc-300 rounded-lg shadow-xl max-h-64 overflow-y-auto z-50 divide-y divide-zinc-100"
                                 x-show="openPartDropdown"
                                 x-transition.opacity>
                                @forelse ($searchResults as $item)
                                    @php
                                        $custName = $item->customer?->customer_name ?: ($item->businessPartner?->bp_name ?: 'N/A');
                                    @endphp
                                    <button type="button"
                                            wire:click="selectPartItem('{{ $item->item_code }}')"
                                            class="w-full px-4 py-2.5 text-left hover:bg-zinc-100 transition flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <span class="block text-xs font-mono font-bold text-zinc-900 truncate">{{ $item->item_code }}</span>
                                            <span class="block text-[11px] text-zinc-600 truncate">{{ $item->item_name }}</span>
                                        </div>
                                        <span class="text-[10px] font-semibold text-zinc-600 uppercase bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200 flex-shrink-0">
                                            {{ $custName }}
                                        </span>
                                    </button>
                                @empty
                                    <div class="p-3 text-xs text-zinc-500 text-center">
                                        Tidak ada part yang cocok di Master List.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Landing State: Jika part belum dipilih --}}
                @if (empty($part_number))
                    <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50/60 p-6 text-center space-y-2">
                        <div class="w-8 h-8 rounded-full bg-zinc-100 border border-zinc-200 text-zinc-400 mx-auto flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-zinc-700">Formulir Part Terkunci</p>
                        <p class="text-xs text-zinc-500">Gunakan fitur pencarian di atas untuk memilih part dari master data. Field Customer, Mesin, Part Number, dan Part Name dibatasi ke data master resmi.</p>
                        @error('part_number')
                            <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    {{-- Form Fields Terisi Otomatis dengan Restriksi Input --}}
                    <div class="space-y-4 pt-1">
                        {{-- Customer (Restricted to Master Customer Options) --}}
                        <div class="relative" @click.away="openCustomerDropdown = false">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-zinc-700">
                                    Customer <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[10px] font-medium text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">Master Customer</span>
                            </div>
                            <div class="relative">
                                <input type="text"
                                       wire:model.live.debounce.150ms="customer_search"
                                       @focus="openCustomerDropdown = true"
                                       placeholder="Pilih Customer dari master..."
                                       class="w-full rounded-lg border-zinc-300 font-semibold text-sm focus:border-zinc-900 focus:ring-zinc-900 pr-9 min-h-[42px] @error('customer') border-rose-300 bg-rose-50/50 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-zinc-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>

                            <input type="hidden" wire:model="customer">

                            @if ($show_customer_dropdown && !empty($customerOptions))
                                <div class="absolute left-0 right-0 mt-1 bg-white border border-zinc-300 rounded-lg shadow-xl max-h-56 overflow-y-auto z-50 divide-y divide-zinc-100"
                                     x-show="openCustomerDropdown"
                                     x-transition.opacity>
                                    @forelse ($customerOptions as $custName)
                                        <button type="button"
                                                wire:click="selectCustomer('{{ addslashes($custName) }}')"
                                                class="w-full px-3.5 py-2 text-left hover:bg-zinc-100 transition flex items-center justify-between text-xs font-semibold text-zinc-900">
                                            <span>{{ $custName }}</span>
                                            @if (strtoupper($customer) === strtoupper($custName))
                                                <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Terpilih</span>
                                            @endif
                                        </button>
                                    @empty
                                        <div class="p-3 text-xs text-zinc-500 text-center">
                                            Tidak ada customer yang cocok. Pilih dari daftar master customer.
                                        </div>
                                    @endforelse
                                </div>
                            @endif

                            @error('customer')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nomor Mesin (Restricted to Master Machine / Operator Options) --}}
                        <div class="relative" @click.away="openMachineDropdown = false">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-zinc-700">
                                    Nomor Mesin (Line/Machine) <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[10px] font-medium text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">Master Mesin</span>
                            </div>
                            <div class="relative">
                                <input type="text"
                                       wire:model.live.debounce.150ms="machine_search"
                                       @focus="openMachineDropdown = true"
                                       placeholder="Pilih Mesin dari master..."
                                       class="w-full rounded-lg border-zinc-300 uppercase font-semibold text-sm focus:border-zinc-900 focus:ring-zinc-900 pr-9 min-h-[42px] @error('machine_number') border-rose-300 bg-rose-50/50 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-zinc-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>

                            <input type="hidden" wire:model="machine_number">

                            @if ($show_machine_dropdown && !empty($machineOptions))
                                <div class="absolute left-0 right-0 mt-1 bg-white border border-zinc-300 rounded-lg shadow-xl max-h-56 overflow-y-auto z-50 divide-y divide-zinc-100"
                                     x-show="openMachineDropdown"
                                     x-transition.opacity>
                                    @forelse ($machineOptions as $machineName)
                                        <button type="button"
                                                wire:click="selectMachine('{{ $machineName }}')"
                                                class="w-full px-3.5 py-2 text-left hover:bg-zinc-100 transition flex items-center justify-between text-xs font-mono font-bold text-zinc-900">
                                            <span>{{ $machineName }}</span>
                                            @if (strtoupper($machine_number) === strtoupper($machineName))
                                                <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Aktif</span>
                                            @endif
                                        </button>
                                    @empty
                                        <div class="p-3 text-xs text-zinc-500 text-center">
                                            Tidak ada mesin yang cocok. Pilih dari daftar master mesin.
                                        </div>
                                    @endforelse
                                </div>
                            @endif

                            @error('machine_number')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Dedicated Input: Part Number (Auto-fills from Search, Restricted / Readonly) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-zinc-700">
                                    Part Number <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[10px] font-medium text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">Terkunci (Master)</span>
                            </div>
                            <input type="text"
                                   wire:model="part_number"
                                   readonly
                                   tabindex="-1"
                                   placeholder="Terisi otomatis dari fitur cari part..."
                                   class="w-full rounded-lg border-zinc-200 bg-zinc-100/80 font-mono font-bold text-sm text-zinc-800 focus:border-zinc-300 focus:ring-0 min-h-[42px] cursor-not-allowed select-all @error('part_number') border-rose-300 bg-rose-50/50 @enderror">
                            @error('part_number')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Dedicated Input: Part Name (Auto-fills from Search, Restricted / Readonly) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-zinc-700">
                                    Part Name <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[10px] font-medium text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">Terkunci (Master)</span>
                            </div>
                            <input type="text"
                                   wire:model="part_name"
                                   readonly
                                   tabindex="-1"
                                   placeholder="Terisi otomatis dari fitur cari part..."
                                   class="w-full rounded-lg border-zinc-200 bg-zinc-100/80 font-semibold text-sm text-zinc-800 focus:border-zinc-300 focus:ring-0 min-h-[42px] cursor-not-allowed select-all @error('part_name') border-rose-300 bg-rose-50/50 @enderror">
                            @error('part_name')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Material --}}
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

                        {{-- Color --}}
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

                        {{-- Berat Part (Weight) --}}
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

                        {{-- Tool / Cavity Number --}}
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
                @endif
            </div>

            {{-- KOLOM KANAN: 2. Konteks Operasional & Personil --}}
            <div class="p-6 space-y-5 relative z-10">
                <div class="pb-3 border-b border-zinc-100">
                    <h2 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                        2. Konteks Operasional & Personil
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Tanggal inspeksi, shift operasional, operator, dan inspector QC.</p>
                </div>

                {{-- Tanggal Inspeksi --}}
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

                {{-- Shift Produksi (Dynamic from mes.shifts) --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">
                        Shift Produksi <span class="text-rose-600">*</span>
                    </label>
                    <select wire:model.live="shift"
                            class="w-full rounded-lg border-zinc-300 text-sm font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('shift') border-rose-300 bg-rose-50/50 @enderror">
                        <option value="">-- Pilih Shift --</option>
                        @foreach ($shiftsConfig as $shiftId => $shiftData)
                            <option value="{{ $shiftId }}">{{ $shiftData['name'] ?? ('Shift ' . $shiftId) }} ({{ $shiftData['start'] }} - {{ $shiftData['end'] }})</option>
                        @endforeach
                    </select>
                    @error('shift')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 border-t border-zinc-100 space-y-4">
                    {{-- Nama Operator (Auto-Uppercase + Suggestions) --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">
                            Nama Operator <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               wire:model.blur="operator"
                               list="operatorSuggestionsList"
                               placeholder="KETIK ATAU PILIH NAMA OPERATOR..."
                               class="w-full rounded-lg border-zinc-300 text-sm uppercase font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('operator') border-rose-300 bg-rose-50/50 @enderror">
                        <datalist id="operatorSuggestionsList">
                            @foreach ($operatorSuggestions as $name)
                                <option value="{{ $name }}"></option>
                            @endforeach
                        </datalist>
                        @error('operator')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Inspector (QC) (Auto-Uppercase + Suggestions) --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1">
                            Nama Inspector (QC) <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               wire:model.blur="inspector"
                               list="inspectorSuggestionsList"
                               placeholder="KETIK ATAU PILIH NAMA INSPECTOR..."
                               class="w-full rounded-lg border-zinc-300 text-sm uppercase font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('inspector') border-rose-300 bg-rose-50/50 @enderror">
                        <datalist id="inspectorSuggestionsList">
                            @foreach ($inspectorSuggestions as $name)
                                <option value="{{ $name }}"></option>
                            @endforeach
                        </datalist>
                        @error('inspector')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Navigation Action Bar --}}
        <div class="p-4 bg-zinc-50 border-t border-zinc-200 flex items-center justify-end rounded-b-xl">
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
</div>
