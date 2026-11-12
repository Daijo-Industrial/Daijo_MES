<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-zinc-900">1. First Piece Inspection</h3>
            <span class="text-xs text-zinc-400 font-mono">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200">
                Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Appearance --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Appearance <span class="text-rose-600">*</span>
                </label>
                <div class="flex gap-2">
                    <label class="flex-1 flex items-center justify-center px-3 py-2 border rounded-lg cursor-pointer text-xs font-bold transition
                                  {{ $appearance === 'OK' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-1 ring-emerald-500' : 'bg-zinc-50 border-zinc-300 text-zinc-700 hover:bg-zinc-100' }}">
                        <input type="radio" wire:model.live="appearance" value="OK" class="sr-only">
                        <span>OK</span>
                    </label>
                    <label class="flex-1 flex items-center justify-center px-3 py-2 border rounded-lg cursor-pointer text-xs font-bold transition
                                  {{ $appearance === 'NG' ? 'bg-rose-50 border-rose-500 text-rose-800 ring-1 ring-rose-500' : 'bg-zinc-50 border-zinc-300 text-zinc-700 hover:bg-zinc-100' }}">
                        <input type="radio" wire:model.live="appearance" value="NG" class="sr-only">
                        <span>NG</span>
                    </label>
                </div>
                @error('appearance')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Weight --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Weight <span class="text-rose-600">*</span>
                </label>
                <div class="flex rounded-lg shadow-sm">
                    <input type="number"
                           step="any"
                           wire:model.blur="weight"
                           placeholder="0.00"
                           class="flex-1 rounded-none rounded-l-lg border-zinc-300 font-mono text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('weight') border-rose-300 bg-rose-50/50 @enderror">
                    <span class="inline-flex items-center rounded-none rounded-r-lg border border-l-0 border-zinc-300 bg-zinc-100 text-zinc-600 text-xs px-3 font-semibold">
                        {{ $weight_uom }}
                    </span>
                </div>
                @error('weight')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fitting Test --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Fitting Test (Optional)
                </label>
                <input type="text"
                       wire:model.blur="fitting_test"
                       placeholder="Hasil fitting..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px]">
                @error('fitting_test')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remarks --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Remarks @if($appearance === 'NG')<span class="text-rose-600">* (Wajib jika NG)</span>@endif
                </label>
                <input type="text"
                       wire:model.blur="remarks"
                       placeholder="Catatan defect / remark..."
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('remarks') border-rose-300 bg-rose-50/50 @enderror">
                @error('remarks')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>