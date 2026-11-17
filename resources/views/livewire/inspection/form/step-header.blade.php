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

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 text-sm space-y-1 shadow-sm">
            <div class="font-bold flex items-center gap-1.5 text-rose-800">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Terdapat kesalahan pada input header:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2-COLUMN RESPONSIVE LAYOUT (Sticky Left Rail + Right Active Step Panel) --}}
    <div class="lg:flex lg:items-start lg:gap-6">
        {{-- Sticky Left Rail --}}
        <aside class="lg:w-72 flex-shrink-0 lg:sticky lg:top-4 mb-6 lg:mb-0">
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3 space-y-2">
                <div class="px-2 py-1 border-b border-zinc-100 flex items-center justify-between">
                    <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Tahapan Header</span>
                    <span class="text-[11px] font-mono text-zinc-500 font-semibold">{{ $subStep }}/2</span>
                </div>

                {{-- Sub-Step Buttons List --}}
                <div class="flex lg:flex-col overflow-x-auto lg:overflow-visible gap-2 pb-1 lg:pb-0">
                    @foreach ($this->subStepsSummary as $sNum => $sInfo)
                        @php
                            $isActive = $subStep === $sNum;
                            $isCompleted = $sInfo['isSaved'];
                        @endphp
                        <button type="button"
                                wire:click="goToSubStep({{ $sNum }})"
                                class="w-full min-w-[200px] lg:min-w-0 p-3 rounded-lg border text-left transition relative flex flex-col justify-between gap-1.5 min-h-[56px]
                                       {{ $isActive ? 'bg-zinc-900 border-zinc-900 text-white shadow-md ring-1 ring-zinc-900' : ($isCompleted ? 'border-emerald-300 bg-emerald-50/60 hover:bg-emerald-100/60 text-zinc-900' : 'border-zinc-200 bg-zinc-50/70 hover:bg-zinc-100/80 text-zinc-700') }}">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs font-bold font-mono px-1.5 py-0.5 rounded {{ $isActive ? 'bg-zinc-800 text-white' : ($isCompleted ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700') }}">
                                        {{ $sNum }}
                                    </span>
                                    <span class="text-xs font-bold truncate {{ $isActive ? 'text-white' : 'text-zinc-900' }}">
                                        {{ $sInfo['title'] }}
                                    </span>
                                </div>
                                <div class="flex-shrink-0">
                                    @if ($isCompleted)
                                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full {{ $isActive ? 'bg-emerald-400 text-zinc-900' : 'bg-emerald-600 text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    @elseif ($isActive)
                                        <span class="w-2.5 h-2.5 rounded-full bg-white ring-2 ring-zinc-900"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-zinc-300"></span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-[11px] font-mono truncate w-full {{ $isActive ? 'text-zinc-300' : ($isCompleted ? 'text-emerald-800 font-medium' : 'text-zinc-400') }}">
                                {{ $sInfo['summary'] }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </aside>

        {{-- Right Focused Form Panel --}}
        <main class="flex-1 min-w-0 space-y-6">
            @if ($subStep === 1)
                {{-- TAHAP 1: 1-LANDING CORE INPUTS (Tanggal, Shift, & Mesin) --}}
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-900"></span>
                        1. Konteks Mesin & Jadwal Produksi
                    </h2>

                    {{-- 3 Core Inputs: 1. Tanggal, 2. Shift, 3. Mesin (user_id) --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-1">
                        {{-- 1. Tanggal Produksi --}}
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200 space-y-1.5">
                            <label class="block text-xs font-bold text-zinc-700">
                                Tanggal Produksi <span class="text-rose-600">*</span>
                            </label>
                            <input type="date"
                                   wire:model.live="inspection_date"
                                   class="w-full rounded-md border-zinc-300 text-xs font-mono font-bold text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900 bg-white py-1.5">
                        </div>

                        {{-- 2. Shift Produksi --}}
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200 space-y-1.5">
                            <label class="block text-xs font-bold text-zinc-700">
                                Shift Produksi <span class="text-rose-600">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-1">
                                @foreach ([1, 2, 3] as $s)
                                    @php $isCurrShift = (int) $shift === $s; @endphp
                                    <button type="button"
                                            wire:click="$set('shift', {{ $s }})"
                                            class="py-1.5 px-2 rounded-md text-xs font-bold transition border text-center {{ $isCurrShift ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs' : 'bg-white text-zinc-700 border-zinc-300 hover:bg-zinc-100' }}">
                                        Shift {{ $s }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-zinc-500 font-mono truncate">
                                {{ $shiftsConfig[$shift]['start'] ?? '-' }} - {{ $shiftsConfig[$shift]['end'] ?? '-' }} WIB
                            </p>
                        </div>

                        {{-- 3. Mesin Produksi (user_id with user->name) --}}
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200 space-y-1.5">
                            <label class="block text-xs font-bold text-zinc-700">
                                Mesin Produksi <span class="text-rose-600">*</span>
                            </label>
                            <select wire:model.live="dic_filter_user_id"
                                    class="w-full rounded-md border-zinc-300 text-xs font-mono font-bold text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900 bg-white py-1.5">
                                <option value="">-- Semua Mesin --</option>
                                @foreach ($this->dicMachineUsers as $u)
                                    <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- JADWAL PART DI MESIN TERPILIH --}}
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
                    @php
                        $activeDics = $this->activeDicsForSelectedMachine;
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-2.5">
                        <h3 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                            @if ($dic_filter_user_id && !empty($machine_number))
                                Jadwal Part di Mesin {{ $machine_number }} (Shift {{ $shift }})
                            @else
                                Seluruh Part Terjadwal pada Shift {{ $shift }}
                            @endif
                        </h3>
                        @if (empty($dic_filter_user_id) && count($activeDics) > 4)
                            <div class="relative w-48">
                                <input type="text"
                                       wire:model.live.debounce.300ms="dic_quick_search"
                                       placeholder="Cari part..."
                                       class="w-full text-xs rounded-md border-zinc-300 py-1 pl-2 pr-2 bg-zinc-50">
                            </div>
                        @endif
                    </div>

                    @if (!empty($activeDics))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($activeDics as $d)
                                @php $isSel = $d['is_selected']; @endphp
                                <div wire:click="selectDicFromList({{ $d['id'] }})"
                                     class="p-4 rounded-xl border text-left transition cursor-pointer flex flex-col justify-between gap-3 relative {{ $isSel ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500 shadow-sm' : 'border-zinc-200 bg-white hover:border-zinc-400 hover:shadow-xs' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-bold text-xs bg-zinc-900 text-white px-2 py-0.5 rounded shadow-2xs">
                                                {{ $d['machine'] }}
                                            </span>
                                            <span class="text-[11px] font-semibold text-zinc-600 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                                DIC #{{ $d['id'] }}
                                            </span>
                                        </div>
                                        @if ($isSel)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Part Terpilih</span>
                                            </span>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="font-mono font-bold text-sm text-zinc-900 truncate">
                                            {{ $d['item_code'] }}
                                        </div>
                                        <div class="text-xs text-zinc-600 truncate mt-0.5" title="{{ $d['item_name'] }}">
                                            {{ $d['item_name'] }}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-zinc-100 text-[11px]">
                                        <span class="text-zinc-500 truncate">
                                            Operator: <strong class="text-zinc-800">{{ $d['operator'] ?: 'Belum Login' }}</strong>
                                        </span>
                                        @if ($d['total_target'])
                                            <span class="font-mono text-zinc-400">
                                                Target: {{ number_format($d['total_target']) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 bg-zinc-50 rounded-xl border border-dashed border-zinc-300 text-center space-y-2">
                            <div class="w-8 h-8 rounded-full bg-zinc-100 border border-zinc-200 text-zinc-400 mx-auto flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-zinc-600">
                                @if ($dic_filter_user_id)
                                    Tidak ada jadwal running pada mesin {{ $machine_number }} (Shift {{ $shift }})
                                @else
                                    Tidak ada jadwal running pada Shift {{ $shift }} tanggal ini
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Fallback: Collapsible Manual Search from Master List --}}
                    <div class="pt-2 border-t border-zinc-100">
                        <button type="button"
                                wire:click="$toggle('showManualSearch')"
                                class="w-full flex items-center justify-between p-3 rounded-lg border border-zinc-200 bg-zinc-50 hover:bg-zinc-100 transition text-xs font-semibold text-zinc-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Part tidak ada di jadwal DIC? Cari manual dari Master List</span>
                            </span>
                            <span class="text-zinc-500 text-[11px]">
                                {{ $showManualSearch ? '[Tutup Pencarian]' : '[Buka Pencarian Manual]' }}
                            </span>
                        </button>

                        <div x-show="$wire.showManualSearch" x-cloak class="mt-3 p-4 bg-zinc-50/70 border border-zinc-200 rounded-lg space-y-3">
                            <div class="relative" @click.away="openPartDropdown = false">
                                <label class="block text-xs font-bold text-zinc-700 mb-1">
                                    Cari Part Number / Part Name (Master List)
                                </label>
                                <input type="text"
                                       wire:model.live.debounce.300ms="part_search"
                                       placeholder="Ketik minimal 2 karakter part number atau nama..."
                                       class="w-full rounded-md border-zinc-300 text-xs focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] bg-white">

                                @if ($show_part_dropdown && !empty($searchResults))
                                    <div class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-zinc-200 shadow-lg text-xs divide-y divide-zinc-100">
                                        @foreach ($searchResults as $item)
                                            <button type="button"
                                                    wire:click="selectPartItem('{{ $item->item_code }}')"
                                                    class="w-full text-left p-2.5 hover:bg-zinc-50 flex items-center justify-between gap-2">
                                                <div>
                                                    <div class="font-mono font-bold text-zinc-900">{{ $item->item_code }}</div>
                                                    <div class="text-[11px] text-zinc-500">{{ $item->item_name }}</div>
                                                </div>
                                                @if (!empty($item->customer?->customer_name))
                                                    <span class="text-[10px] font-semibold bg-zinc-100 px-1.5 py-0.5 rounded border border-zinc-200 text-zinc-700">
                                                        {{ $item->customer->customer_name }}
                                                    </span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($subStep === 2)
                {{-- TAHAP 2: KONFIRMASI SPESIFIKASI & PERSONIL --}}
                <div class="space-y-5">
                    {{-- Selected Part & DIC Summary Card --}}
                    <div class="p-4 bg-white rounded-xl border border-zinc-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-zinc-900 text-white flex items-center justify-center flex-shrink-0 font-bold text-xs">
                                P
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-sm text-zinc-900 truncate">{{ $part_number }}</span>
                                    @if ($customer)
                                        <span class="text-[11px] font-semibold text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                            {{ $customer }}
                                        </span>
                                    @endif
                                    @if ($dic_id)
                                        <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            DIC #{{ $dic_id }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-500 truncate mt-0.5">{{ $part_name }}</p>
                            </div>
                        </div>

                        <button type="button"
                                wire:click="goToSubStep(1)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-50 rounded-lg shadow-2xs transition flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Ganti Part / DIC</span>
                        </button>
                    </div>

                    {{-- Form Spesifikasi Part --}}
                    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
                        <div class="border-b border-zinc-100 pb-2.5">
                            <h3 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                                Spesifikasi Teknis Part
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                            @if (! $has_master_customer)
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-zinc-700 mb-1">
                                        Customer <span class="text-rose-600">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model.blur="customer"
                                           list="customerSuggestionsList"
                                           placeholder="PILIH / KETIK CUSTOMER..."
                                           class="w-full rounded-md border-zinc-300 text-xs font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[36px] @error('customer') border-rose-300 bg-rose-50/50 @enderror">
                                    <datalist id="customerSuggestionsList">
                                        @foreach ($customerOptions as $custName)
                                            <option value="{{ $custName }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('customer')
                                        <p class="mt-1 text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Material --}}
                            <div>
                                <label class="block font-semibold text-zinc-700 mb-1">
                                    Material <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="material"
                                       placeholder="PP, ABS, PC..."
                                       class="w-full rounded-md border-zinc-300 text-xs uppercase font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[36px] @error('material') border-rose-300 bg-rose-50/50 @enderror">
                                @error('material')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Color --}}
                            <div>
                                <label class="block font-semibold text-zinc-700 mb-1">
                                    Color <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="color"
                                       placeholder="BLACK, NATURAL..."
                                       class="w-full rounded-md border-zinc-300 text-xs uppercase font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[36px] @error('color') border-rose-300 bg-rose-50/50 @enderror">
                                @error('color')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Weight & UOM --}}
                            <div>
                                <label class="block font-semibold text-zinc-700 mb-1">
                                    Berat Part <span class="text-rose-600">*</span>
                                </label>
                                <div class="flex rounded-md shadow-2xs w-full">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="weight"
                                           placeholder="0.00"
                                           class="w-full flex-1 rounded-none rounded-l-md border-zinc-300 text-xs font-mono font-bold focus:border-zinc-900 focus:ring-zinc-900 min-h-[36px] @error('weight') border-rose-300 bg-rose-50/50 @enderror">
                                    <select wire:model.blur="weight_uom"
                                            class="rounded-none rounded-r-md border-l-0 border-zinc-300 bg-zinc-100 text-xs font-semibold pl-2 pr-6 py-1.5 w-16 focus:border-zinc-900 focus:ring-zinc-900">
                                        <option value="g">g</option>
                                        <option value="kg">kg</option>
                                    </select>
                                </div>
                                @error('weight')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tool / Cavity Number --}}
                            <div>
                                <label class="block font-semibold text-zinc-700 mb-1">
                                    Nomor Tool / Cavity <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="tool_number_or_cav_number"
                                       placeholder="Contoh: 1, 2, CAV 1-4..."
                                       class="w-full rounded-md border-zinc-300 text-xs uppercase font-mono font-bold focus:border-zinc-900 focus:ring-zinc-900 min-h-[36px] @error('tool_number_or_cav_number') border-rose-300 bg-rose-50/50 @enderror">
                                @error('tool_number_or_cav_number')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Form Operasional, Mesin, & Personil --}}
                    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
                        <div class="border-b border-zinc-100 pb-2.5">
                            <h3 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                                Konteks Operasional & Personil
                            </h3>
                        </div>

                        {{-- Schedule & Machine Quick Summary Toolbar (with edit mode) --}}
                        <div x-data="{ editSchedule: @json($errors->has('inspection_date') || $errors->has('shift') || $errors->has('machine_number')) }"
                             class="border border-zinc-200 rounded-lg bg-zinc-50/70 p-3.5 transition text-xs">
                            <div x-show="!editSchedule" class="flex items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                                    <div class="flex items-center gap-1.5 text-zinc-800">
                                        <span class="text-zinc-500">Tanggal:</span>
                                        <span class="font-bold text-zinc-900 font-mono">{{ $inspection_date ? \Illuminate\Support\Carbon::parse($inspection_date)->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <span class="text-zinc-300">|</span>
                                    <div class="flex items-center gap-1.5 text-zinc-800">
                                        <span class="text-zinc-500">Shift:</span>
                                        <span class="font-bold text-zinc-900">{{ $shiftsConfig[$shift]['name'] ?? ('Shift ' . ($shift ?: '-')) }}</span>
                                    </div>
                                    <span class="text-zinc-300">|</span>
                                    <div class="flex items-center gap-1.5 text-zinc-800">
                                        <span class="text-zinc-500">Mesin:</span>
                                        <span class="font-bold font-mono text-zinc-900">{{ $machine_number ?: 'Belum dipilih' }}</span>
                                    </div>
                                </div>
                                <button type="button"
                                        @click="editSchedule = true"
                                        class="px-2.5 py-1 text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-50 rounded-md shadow-2xs">
                                    Ubah
                                </button>
                            </div>

                            {{-- Expanded Edit Schedule Inputs --}}
                            <div x-show="editSchedule" x-cloak class="space-y-3">
                                <div class="flex items-center justify-between pb-1.5 border-b border-zinc-200">
                                    <span class="font-bold text-zinc-800">Ubah Tanggal, Shift, & Mesin</span>
                                    <button type="button" @click="editSchedule = false" class="text-zinc-600 hover:text-zinc-900 underline">
                                        Selesai
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-zinc-700 mb-1">Tanggal Inspeksi</label>
                                        <input type="date" wire:model.blur="inspection_date" class="w-full rounded-md border-zinc-300 text-xs focus:ring-zinc-900 focus:border-zinc-900 py-1.5">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-zinc-700 mb-1">Shift</label>
                                        <select wire:model.live="shift" class="w-full rounded-md border-zinc-300 text-xs font-semibold focus:ring-zinc-900 focus:border-zinc-900 py-1.5">
                                            @foreach ($shiftsConfig as $sId => $sData)
                                                <option value="{{ $sId }}">{{ $sData['name'] ?? ('Shift ' . $sId) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-zinc-700 mb-1">Nomor Mesin</label>
                                        <select wire:model.live="machine_number" class="w-full rounded-md border-zinc-300 text-xs font-mono font-bold focus:ring-zinc-900 focus:border-zinc-900 py-1.5">
                                            <option value="">-- Pilih Mesin --</option>
                                            @foreach ($machineOptions as $mName)
                                                <option value="{{ $mName }}" @selected(strtoupper((string) $machine_number) === strtoupper($mName))>{{ $mName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Multi-Line Selector (Jika part dijadwalkan pada beberapa mesin secara paralel) --}}
                        @if (!empty($availableDics) && count($availableDics) > 1)
                            <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-lg space-y-2 text-xs">
                                <span class="font-bold text-zinc-800">Pilih Mesin Produksi (DIC Aktif):</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($availableDics as $dicItem)
                                        @php $isSelected = ($dic_id === $dicItem['id']); @endphp
                                        <button type="button"
                                                wire:click="selectDic({{ $dicItem['id'] }})"
                                                class="p-2 rounded-md border text-left transition flex items-center justify-between gap-2 {{ $isSelected ? 'bg-zinc-900 border-zinc-900 text-white' : 'bg-white border-zinc-300 text-zinc-800' }}">
                                            <span class="font-bold font-mono">{{ $dicItem['machine_name'] }} (#{{ $dicItem['id'] }})</span>
                                            <span class="text-[10px]">{{ $dicItem['operator'] ?: 'Belum Login' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Personil Inputs: Operator & Inspector --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                            {{-- Operator --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-semibold text-zinc-700">
                                        Nama Operator <span class="text-rose-600">*</span>
                                    </label>
                                    @if ($is_operator_auto_filled && !empty($dic_id) && !empty($operator) && $operator === $dic_operator)
                                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                            Auto DIC #{{ $dic_id }}
                                        </span>
                                    @endif
                                </div>
                                <input type="text"
                                       wire:model.blur="operator"
                                       list="operatorSuggestionsList"
                                       placeholder="KETIK ATAU PILIH NAMA OPERATOR..."
                                       class="w-full rounded-md border-zinc-300 text-xs uppercase font-semibold focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('operator') border-rose-300 bg-rose-50/50 @enderror">
                                <datalist id="operatorSuggestionsList">
                                    @foreach ($operatorSuggestions as $name)
                                        <option value="{{ $name }}"></option>
                                    @endforeach
                                </datalist>
                                @error('operator')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Inspector (QC) --}}
                            <div>
                                <label class="block font-semibold text-zinc-700 mb-1">
                                    Nama Inspector (QC) <span class="text-rose-600">*</span>
                                </label>
                                <input type="text"
                                       wire:model.blur="inspector"
                                       list="inspectorSuggestionsList"
                                       placeholder="KETIK ATAU PILIH NAMA INSPECTOR..."
                                       class="w-full rounded-md border-zinc-300 text-xs uppercase font-bold focus:border-zinc-900 focus:ring-zinc-900 min-h-[38px] @error('inspector') border-rose-300 bg-rose-50/50 @enderror">
                                <datalist id="inspectorSuggestionsList">
                                    @foreach ($inspectorSuggestions as $name)
                                        <option value="{{ $name }}"></option>
                                    @endforeach
                                </datalist>
                                @error('inspector')
                                    <p class="mt-1 text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </main>
    </div>

    {{-- Step 1 Floating Bottom Navigation Dock --}}
    <div class="fixed bottom-0 left-0 right-0 lg:left-64 z-30 bg-white/95 backdrop-blur-md border-t border-zinc-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] py-3 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div>
                @if ($subStep === 2)
                    <button type="button"
                            wire:click="prevSubStep"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-zinc-300 text-xs sm:text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-2xs transition">
                        <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kembali ke 1. Pilih Part & Jadwal</span>
                    </button>
                @endif
            </div>

            {{-- Middle: Progress Context --}}
            <div class="hidden md:flex items-center gap-2 text-xs font-mono text-zinc-500">
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200 font-bold text-zinc-800">
                    Step 1 Header
                </span>
                <span>•</span>
                <span class="text-zinc-600">
                    Tahap <strong class="text-zinc-900 font-bold">{{ $subStep }}/2</strong>: {{ $this->subStepsSummary[$subStep]['title'] ?? '' }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                @if ($subStep === 1)
                    <button type="button"
                            wire:click="nextSubStep"
                            @if (empty($part_number)) disabled @endif
                            class="inline-flex items-center gap-2 px-5 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Lanjut ke 2. Spesifikasi & Personil</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                @else
                    <button type="button"
                            wire:click="saveStep"
                            class="inline-flex items-center gap-2 px-6 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                        <span>Simpan & Lanjut ke Detail Periode</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
