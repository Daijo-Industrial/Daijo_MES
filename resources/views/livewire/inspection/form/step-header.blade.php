<div class="space-y-6">
    {{-- Header Status Bar --}}
    <div class="flex items-center justify-between">
        @if ($isSaved)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Tersimpan di Sesi
                @if ($savedAt)
                    <span class="text-green-700/80 font-normal">({{ \Illuminate\Support\Carbon::parse($savedAt)->format('H:i:s') }})</span>
                @endif
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Data belum disimpan ke sesi
            </span>
        @endif

        <span class="text-xs font-mono text-gray-500">
            No Dokumen: <strong class="text-gray-900">{{ $document_number }}</strong>
        </span>
    </div>

    {{-- 1. General & Logistics --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                1. General Information
            </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Tanggal Inspeksi <span class="text-red-500">*</span>
                </label>
                <input type="date"
                       wire:model.blur="inspection_date"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('inspection_date') border-red-300 bg-red-50/50 @enderror">
                @error('inspection_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Customer <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       list="customerList"
                       wire:model.blur="customer"
                       placeholder="Pilih atau ketik Customer..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('customer') border-red-300 bg-red-50/50 @enderror">
                <datalist id="customerList">
                    @foreach ($customerSuggestions as $cust)
                        <option value="{{ $cust }}"></option>
                    @endforeach
                </datalist>
                @error('customer')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Shift Produksi <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="shift"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('shift') border-red-300 bg-red-50/50 @enderror">
                    <option value="">-- Pilih Shift --</option>
                    <option value="1">Shift 1 (07:30 - 15:30)</option>
                    <option value="2">Shift 2 (15:30 - 23:30)</option>
                    <option value="3">Shift 3 (23:30 - 07:30)</option>
                </select>
                @error('shift')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- 2. Part Information --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                2. Part Details
            </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Number <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="part_number"
                       placeholder="Contoh: 12345-ABC"
                       class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('part_number') border-red-300 bg-red-50/50 @enderror">
                @error('part_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Name <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="part_name"
                       placeholder="Nama komponen / part..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('part_name') border-red-300 bg-red-50/50 @enderror">
                @error('part_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Material <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="material"
                       placeholder="Contoh: ABS, PP, POM..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('material') border-red-300 bg-red-50/50 @enderror">
                @error('material')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Color <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="color"
                       placeholder="Contoh: BLACK, NATURAL, GREY..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('color') border-red-300 bg-red-50/50 @enderror">
                @error('color')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Berat Part (Weight) <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="number"
                           step="any"
                           wire:model.blur="weight"
                           placeholder="0.00"
                           class="flex-1 rounded-none rounded-l-lg border-gray-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('weight') border-red-300 bg-red-50/50 @enderror">
                    <select wire:model.blur="weight_uom"
                            class="inline-flex items-center rounded-none rounded-r-lg border-l-0 border-gray-300 bg-gray-50 text-gray-700 text-xs font-semibold px-3 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="g">Gram (g)</option>
                        <option value="kg">Kilogram (kg)</option>
                    </select>
                </div>
                @error('weight')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Tool / Cavity Number <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="tool_number_or_cav_number"
                       placeholder="Contoh: 1, 2, Cav 1-4..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('tool_number_or_cav_number') border-red-300 bg-red-50/50 @enderror">
                @error('tool_number_or_cav_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- 3. Machine & Manpower --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                3. Mesin & Personil
            </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Nomor Mesin (Line/Machine) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="machine_number"
                       placeholder="Contoh: M-01, INJ-05..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('machine_number') border-red-300 bg-red-50/50 @enderror">
                @error('machine_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Nama Operator <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="operator"
                       placeholder="Nama operator..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('operator') border-red-300 bg-red-50/50 @enderror">
                @error('operator')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Nama Inspector (QC) <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       wire:model.blur="inspector"
                       placeholder="Nama inspector IPQC..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('inspector') border-red-300 bg-red-50/50 @enderror">
                @error('inspector')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Action Button --}}
    <div class="flex justify-end pt-2">
        <button type="button"
                wire:click="saveStep"
                class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
            Simpan & Lanjut ke Detail Periode &rarr;
        </button>
    </div>
</div>
