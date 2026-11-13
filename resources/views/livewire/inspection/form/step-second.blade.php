<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-zinc-900">3. Second Inspection (Lot Size, Sampling & Packaging)</h3>
            <span class="text-xs text-zinc-400 font-mono">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200">
                Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-6">
        {{-- Lot Size Parameters --}}
        <div class="bg-zinc-50 p-4 rounded-xl border border-zinc-200 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label class="text-xs font-semibold text-zinc-700">Skip Lot Size?</label>
                    <div class="inline-flex rounded-lg border border-zinc-300 p-0.5 bg-zinc-100 shadow-sm">
                        <button type="button"
                                wire:click="setSkipLotSize(false)"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition {{ !$skipLotSize ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900' }}">
                            Tidak (Gunakan Lot)
                        </button>
                        <button type="button"
                                wire:click="setSkipLotSize(true)"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition {{ $skipLotSize ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900' }}">
                            Ya (Lewati Lot Size)
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-xs">
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Lot Size Quantity (pcs) @if (!$skipLotSize)<span class="text-rose-600">*</span>@else<span class="text-zinc-400 font-normal">(Dilewati)</span>@endif
                </label>
                @if ($skipLotSize)
                    <input type="text"
                           disabled
                           value=""
                           placeholder="Lot size dilewati (opsional)"
                           class="w-full rounded-lg border-zinc-200 bg-zinc-100 text-zinc-400 cursor-not-allowed font-mono text-sm min-h-[42px]">
                @else
                    <input type="number"
                           wire:model.blur="lot_size_quantity"
                           placeholder="Jumlah lot..."
                           class="w-full rounded-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('lot_size_quantity') border-rose-300 bg-rose-50 @enderror">
                    @error('lot_size_quantity')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
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