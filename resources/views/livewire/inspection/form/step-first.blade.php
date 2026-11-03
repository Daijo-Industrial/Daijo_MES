<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-green-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-gray-900">1. First Piece Inspection</h3>
            <span class="text-xs text-gray-400">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                ✓ Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Appearance --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Appearance <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <label class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 border rounded-lg cursor-pointer text-xs font-bold transition
                                  {{ $appearance === 'OK' ? 'bg-green-50 border-green-500 text-green-700 ring-1 ring-green-500' : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        <input type="radio" wire:model.live="appearance" value="OK" class="sr-only">
                        <span>✓ OK</span>
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 border rounded-lg cursor-pointer text-xs font-bold transition
                                  {{ $appearance === 'NG' ? 'bg-red-50 border-red-500 text-red-700 ring-1 ring-red-500' : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        <input type="radio" wire:model.live="appearance" value="NG" class="sr-only">
                        <span>✗ NG</span>
                    </label>
                </div>
                @error('appearance')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Weight --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Weight <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="number"
                           step="any"
                           wire:model.blur="weight"
                           placeholder="0.00"
                           class="flex-1 rounded-none rounded-l-lg border-gray-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('weight') border-red-300 bg-red-50/50 @enderror">
                    <span class="inline-flex items-center rounded-none rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-xs px-3">
                        {{ $weight_uom }}
                    </span>
                </div>
                @error('weight')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fitting Test --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Fitting Test (Optional)
                </label>
                <input type="text"
                       wire:model.blur="fitting_test"
                       placeholder="Hasil fitting..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px]">
                @error('fitting_test')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remarks --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Remarks @if($appearance === 'NG')<span class="text-red-500">* (Wajib bila NG)</span>@endif
                </label>
                <input type="text"
                       wire:model.blur="remarks"
                       placeholder="Catatan defect / remark..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('remarks') border-red-300 bg-red-50/50 @enderror">
                @error('remarks')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
            <button type="button"
                    wire:click="resetStep"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition">
                Reset
            </button>
            <button type="button"
                    wire:click="saveStep"
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                Simpan First Piece
            </button>
        </div>
    </div>
</div>
