<div class="space-y-6" x-data="{ openPartDropdown: @entangle('show_part_dropdown') }">
    {{-- Header Status Bar --}}
    <div class="flex items-center justify-between">
        @if ($isSaved)
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Tersimpan di Sesi</span>
                @if ($savedAt)
                    <span class="text-emerald-700/80 font-normal">({{ \Illuminate\Support\Carbon::parse($savedAt)->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i:s \W\I\B') }})</span>
                @endif
            </span>
        @else
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-medium bg-amber-50 text-amber-800 border border-amber-200">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Data belum disimpan ke sesi</span>
            </span>
        @endif
    </div>

    {{-- Main 2-Column Split Form Container --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm relative">
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-zinc-200">
            {{-- KOLOM KIRI: 1. Part & Spesifikasi (Top-to-Down) --}}
            <div class="p-6 space-y-5 relative z-20">
                <div class="pb-3 border-b border-zinc-100">
                    <h2 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-900"></span>
                        1. Part & Spesifikasi
                    </h2>
                    <p class="text-sm text-zinc-500 mt-0.5">Pilih part untuk memuat master data, customer, & spesifikasi.</p>
                </div>

                {{-- Dedicated Search Toolbar (Terpisah dari field input yang disimpan ke backend) --}}
                <div class="relative pb-3 border-b border-zinc-100" @click.away="openPartDropdown = false">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-zinc-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Cari Part Number / Part Name (Master List)</span>
                        </label>
                        @if (!empty($part_number) && !$isSearchingAgain)
                            <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                                Part Terpilih
                            </span>
                        @endif
                    </div>

                    @if (!empty($part_number) && !$isSearchingAgain)
                        {{-- Last Selected Option Display (Instead of Search Idle Input) --}}
                        <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-zinc-900 text-white flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-sm text-zinc-900 truncate">{{ $part_number }}</span>
                                        @if (!empty($customer))
                                            <span class="text-xs font-semibold text-zinc-700 uppercase bg-zinc-200/80 px-2 py-0.5 rounded border border-zinc-300 flex-shrink-0">
                                                {{ $customer }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-600 truncate mt-0.5">{{ $part_name }}</p>
                                </div>
                            </div>
                            <button type="button"
                                    wire:click="startSearchAgain"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 hover:text-zinc-900 rounded-lg shadow-sm transition flex-shrink-0">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                   class="w-full rounded-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 pl-10 pr-24 min-h-[44px]">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-zinc-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1.5">
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
                                            class="px-2.5 py-1 text-sm font-semibold text-zinc-700 hover:text-zinc-900 bg-zinc-100 hover:bg-zinc-200 rounded-md border border-zinc-200 transition">
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
                                            class="w-full px-4 py-3 text-left hover:bg-zinc-100 transition flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <span class="block text-sm font-mono font-bold text-zinc-900 truncate">{{ $item->item_code }}</span>
                                            <span class="block text-sm text-zinc-600 truncate">{{ $item->item_name }}</span>
                                        </div>
                                        <span class="text-xs font-semibold text-zinc-600 uppercase bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200 flex-shrink-0">
                                            {{ $custName }}
                                        </span>
                                    </button>
                                @empty
                                    <div class="p-3 text-sm text-zinc-500 text-center">
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
                        <div class="w-9 h-9 rounded-full bg-zinc-100 border border-zinc-200 text-zinc-400 mx-auto flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <p class="text-base font-semibold text-zinc-800">Formulir Part Terkunci</p>
                        <p class="text-sm text-zinc-500">Pilih part melalui pencarian di atas untuk memuat data spesifikasi.</p>
                        @error('part_number')
                            <p class="text-sm text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    {{-- Form Spesifikasi Part --}}
                    <div class="p-3.5 bg-zinc-50/80 border border-zinc-200 rounded-lg space-y-3.5 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            @if (! $has_master_customer)
                                {{-- Customer (Hanya tampil jika part master belum memiliki data customer) --}}
                                <div class="sm:col-span-2 min-w-0">
                                    <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                        Customer <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model.blur="customer"
                                           list="customerSuggestionsList"
                                           placeholder="PILIH / KETIK CUSTOMER..."
                                           class="w-full min-w-0 rounded-lg border-zinc-300 bg-white text-zinc-900 font-semibold text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('customer') border-rose-300 bg-rose-50/50 @enderror">
                                    <datalist id="customerSuggestionsList">
                                        @foreach ($customerOptions as $custName)
                                            <option value="{{ $custName }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('customer')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Material (Auto-filled dari master, dapat diedit) --}}
                            <div class="min-w-0">
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Material <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="material"
                                       placeholder="Contoh: PP, ABS, PC..."
                                       class="w-full min-w-0 rounded-lg border-zinc-300 bg-white text-zinc-900 font-semibold text-sm uppercase focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('material') border-rose-300 bg-rose-50/50 @enderror">
                                @error('material')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Color (Auto-filled dari master, dapat diedit) --}}
                            <div class="min-w-0">
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Color <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="color"
                                       placeholder="Contoh: BLACK, GREY..."
                                       class="w-full min-w-0 rounded-lg border-zinc-300 bg-white text-zinc-900 font-semibold text-sm uppercase focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('color') border-rose-300 bg-rose-50/50 @enderror">
                                @error('color')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Berat Part (Weight) & UOM (Auto-filled dari master, dapat diedit) --}}
                            <div class="min-w-0">
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Berat Part <span class="text-rose-600">*</span>
                                </label>
                                <div class="flex rounded-lg shadow-sm w-full min-w-0">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="weight"
                                           placeholder="0.00"
                                           class="w-full min-w-0 flex-1 rounded-none rounded-l-lg border-zinc-300 bg-white text-zinc-900 font-mono font-bold text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('weight') border-rose-300 bg-rose-50/50 @enderror">
                                    <select wire:model.blur="weight_uom"
                                            class="inline-flex items-center rounded-none rounded-r-lg border-l-0 border-zinc-300 bg-zinc-100 text-zinc-800 text-sm font-semibold pl-2.5 pr-7 py-2 w-20 shrink-0 focus:border-zinc-900 focus:ring-zinc-900">
                                        <option value="g">g</option>
                                        <option value="kg">kg</option>
                                    </select>
                                </div>
                                @error('weight')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tool / Cavity Number (Auto-filled dari master, dapat diedit) --}}
                            <div class="min-w-0">
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Tool / Cavity Number <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="tool_number_or_cav_number"
                                       placeholder="Contoh: 1, 2, Cav 1-4..."
                                       class="w-full min-w-0 rounded-lg border-zinc-300 bg-white text-zinc-900 font-semibold text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('tool_number_or_cav_number') border-rose-300 bg-rose-50/50 @enderror">
                                @error('tool_number_or_cav_number')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- KOLOM KANAN: 2. Konteks Operasional & Personil (Top-to-Down) --}}
            <div class="p-6 space-y-5 relative z-10">
                <div class="pb-3 border-b border-zinc-100">
                    <h2 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-900"></span>
                        2. Konteks Operasional & Personil
                    </h2>
                    <p class="text-sm text-zinc-500 mt-0.5">Jadwal inspeksi otomatis, operator, dan inspector QC.</p>
                </div>

                {{-- Minimized View: Tanggal Inspeksi, Shift Produksi, & Nomor Mesin (Auto-Filled, Editable) --}}
                <div x-data="{ editSchedule: @json($errors->has('inspection_date') || $errors->has('shift') || $errors->has('machine_number')) }"
                     class="border border-zinc-200 rounded-lg bg-zinc-50/70 p-3.5 transition">
                    {{-- Minimized State: Single Compact Line --}}
                    <div x-show="!editSchedule" class="flex items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm">
                            {{-- Tanggal --}}
                            <div class="flex items-center gap-1.5 text-zinc-800">
                                <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-zinc-500 font-medium">Tanggal:</span>
                                <span class="font-bold text-zinc-900 font-mono">{{ $inspection_date ? \Illuminate\Support\Carbon::parse($inspection_date)->format('d/m/Y') : '-' }}</span>
                            </div>

                            <span class="text-zinc-300">|</span>

                            {{-- Shift --}}
                            <div class="flex items-center gap-1.5 text-zinc-800">
                                <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-zinc-500 font-medium">Shift:</span>
                                <span class="font-bold text-zinc-900">
                                    {{ $shiftsConfig[$shift]['name'] ?? ('Shift ' . ($shift ?: '-')) }}
                                    @if (!empty($shiftsConfig[$shift]))
                                        <span class="text-zinc-500 font-normal">({{ $shiftsConfig[$shift]['start'] }} - {{ $shiftsConfig[$shift]['end'] }})</span>
                                    @endif
                                </span>
                            </div>

                            <span class="text-zinc-300">|</span>

                            {{-- Nomor Mesin --}}
                            <div class="flex items-center gap-1.5 text-zinc-800">
                                <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="text-zinc-500 font-medium">Mesin:</span>
                                @if (!empty($machine_number))
                                    <span class="font-bold text-zinc-900 font-mono">{{ $machine_number }}</span>
                                    @if ($dic_id)
                                        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                            DIC #{{ $dic_id }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-rose-600 font-semibold italic text-xs">Belum Dipilih *</span>
                                @endif
                            </div>
                        </div>

                        <button type="button"
                                @click="editSchedule = true"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-md shadow-sm transition flex-shrink-0">
                            <span>Ubah</span>
                        </button>
                    </div>

                    {{-- Expanded State: Form Edit Inputs (Tanggal, Shift, & Mesin) --}}
                    <div x-show="editSchedule" x-cloak class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200">
                            <span class="text-sm font-bold text-zinc-800">Ubah Tanggal, Shift, & Mesin Produksi</span>
                            <button type="button"
                                    @click="editSchedule = false"
                                    class="text-sm font-semibold text-zinc-600 hover:text-zinc-900 underline">
                                Selesai
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            {{-- Tanggal Inspeksi --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Tanggal Inspeksi <span class="text-rose-600">*</span>
                                </label>
                                <input type="date"
                                       wire:model.blur="inspection_date"
                                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[40px] @error('inspection_date') border-rose-300 bg-rose-50/50 @enderror">
                                @error('inspection_date')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Shift Produksi --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1">
                                    Shift Produksi <span class="text-rose-600">*</span>
                                </label>
                                <select wire:model.live="shift"
                                        class="w-full rounded-lg border-zinc-300 text-sm font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[40px] @error('shift') border-rose-300 bg-rose-50/50 @enderror">
                                    <option value="">-- Pilih Shift --</option>
                                    @foreach ($shiftsConfig as $shiftId => $shiftData)
                                        <option value="{{ $shiftId }}">{{ $shiftData['name'] ?? ('Shift ' . $shiftId) }} ({{ $shiftData['start'] }} - {{ $shiftData['end'] }})</option>
                                    @endforeach
                                </select>
                                @error('shift')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Nomor Mesin (Line/Machine) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-semibold text-zinc-700">
                                        Nomor Mesin <span class="text-rose-600">*</span>
                                    </label>
                                    @if ($dic_id && !empty($machine_number))
                                        <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                            DIC #{{ $dic_id }}
                                        </span>
                                    @endif
                                </div>
                                <select wire:model.live="machine_number"
                                        class="w-full rounded-lg border-zinc-300 font-mono text-sm uppercase font-bold focus:border-zinc-900 focus:ring-zinc-900 min-h-[40px] @error('machine_number') border-rose-300 bg-rose-50/50 @enderror">
                                    <option value="">-- Pilih Mesin --</option>
                                    @foreach ($machineOptions as $mName)
                                        <option value="{{ $mName }}" @selected(strtoupper((string) $machine_number) === strtoupper($mName))>{{ $mName }}</option>
                                    @endforeach
                                </select>
                                @error('machine_number')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Koneksi DIC & Pemilihan Mesin Produksi --}}
                @if (!empty($availableDics) && count($availableDics) > 1)
                    {{-- Multi-Line Selector (Jika part dijadwalkan pada beberapa mesin secara paralel) --}}
                    <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-zinc-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span>Pilih Mesin Produksi (DIC Aktif)</span>
                            </span>
                            <span class="text-xs font-semibold text-zinc-700 bg-white px-2 py-0.5 rounded border border-zinc-200">
                                {{ count($availableDics) }} Jalur Aktif
                            </span>
                        </div>
                        <p class="text-sm text-zinc-500">Part ini sedang terjadwal di beberapa mesin. Klik mesin yang sedang Anda inspeksi:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                            @foreach ($availableDics as $dicItem)
                                @php $isSelected = ($dic_id === $dicItem['id']); @endphp
                                <button type="button"
                                        wire:click="selectDic({{ $dicItem['id'] }})"
                                        class="p-2.5 rounded-lg border text-left transition flex items-center justify-between gap-2 {{ $isSelected ? 'bg-zinc-900 border-zinc-900 text-white shadow-sm' : 'bg-white border-zinc-300 hover:border-zinc-400 text-zinc-800' }}">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 font-bold text-sm truncate">
                                            <span>{{ $dicItem['machine_name'] }}</span>
                                            <span class="{{ $isSelected ? 'text-zinc-300' : 'text-zinc-500' }} font-normal text-xs">(#{{ $dicItem['id'] }})</span>
                                        </div>
                                        <div class="text-xs {{ $isSelected ? 'text-zinc-300' : 'text-zinc-600' }} truncate mt-0.5">
                                            Op: {{ $dicItem['operator'] ?: 'Belum Login' }}
                                        </div>
                                    </div>
                                    @if ($isSelected)
                                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($dic_id)
                    {{-- DIC Connected Status Bar --}}
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span class="font-semibold text-emerald-900 truncate">{{ $dicStatusMessage }}</span>
                        </div>
                        <button type="button"
                                wire:click="detachDic"
                                class="text-xs font-semibold text-emerald-800 hover:text-emerald-950 underline flex-shrink-0">
                            Lepas Koneksi
                        </button>
                    </div>
                @elseif (!empty($dicStatusMessage) && !empty($part_number))
                    {{-- Notice when no active DIC found for this date/shift/part --}}
                    <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-lg flex items-center gap-2 text-sm text-zinc-600">
                        <svg class="w-4 h-4 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $dicStatusMessage }}</span>
                    </div>
                @endif

                <div class="space-y-4 pt-1">
                    {{-- Nama Operator (Auto-Uppercase + Suggestions) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-semibold text-zinc-700">
                                Nama Operator <span class="text-rose-600">*</span>
                            </label>
                            @if ($is_operator_auto_filled && !empty($dic_id) && !empty($operator) && $operator === $dic_operator)
                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Auto-fill DIC #{{ $dic_id }}
                                </span>
                            @endif
                        </div>
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
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Inspector (QC) (Auto-Uppercase + Suggestions) --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1">
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
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
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
