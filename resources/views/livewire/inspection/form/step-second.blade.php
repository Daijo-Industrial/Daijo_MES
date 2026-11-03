<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-green-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-gray-900">3. Second Inspection (Lot Size, Sampling & Packaging)</h3>
            <span class="text-xs text-gray-400">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                ✓ Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-6">
        {{-- Lot Size Parameters --}}
        <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label class="text-xs font-semibold text-gray-700">Skip Lot Size?</label>
                    <div class="flex items-center gap-3 text-xs">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" wire:model.live="skipLotSize" :value="false" class="text-indigo-600 focus:ring-indigo-500">
                            <span>Tidak (Gunakan Lot)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" wire:model.live="skipLotSize" :value="true" class="text-indigo-600 focus:ring-indigo-500">
                            <span>Ya (Lewati Lot Size)</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="resetStep"
                            class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition">
                        Reset Lot
                    </button>
                    <button type="button"
                            wire:click="saveStep"
                            class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                        Simpan Lot Size
                    </button>
                </div>
            </div>

            @if (!$skipLotSize)
                <div class="max-w-xs">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Lot Size Quantity (pcs) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           wire:model.blur="lot_size_quantity"
                           placeholder="Jumlah lot..."
                           class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-[42px] @error('lot_size_quantity') border-red-300 bg-red-50 @enderror">
                    @error('lot_size_quantity')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>

        {{-- Embedded Sub-checks: Sampling & Packaging --}}
        <div class="space-y-4">
            <div>
                @livewire(\App\Livewire\Inspection\InspectionForm\StepSampling::class, [
                    'second_inspection_document_number' => $document_number,
                ], key('sampling-' . $periodKey . '-' . $document_number))
            </div>

            <div>
                @livewire(\App\Livewire\Inspection\InspectionForm\StepPackaging::class, [
                    'second_inspection_document_number' => $document_number,
                ], key('packaging-' . $periodKey . '-' . $document_number))
            </div>
        </div>
    </div>
</div>
