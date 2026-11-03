<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-green-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-gray-900">4. Judgement & Output Periode</h3>
            <span class="text-xs text-gray-400">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                ✓ Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Pass Quantity (pcs) <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       wire:model.blur="pass_quantity"
                       placeholder="Jumlah OK / Pass"
                       class="w-full rounded-lg border-gray-300 font-mono font-bold text-green-700 text-sm focus:border-green-500 focus:ring-green-500 min-h-[42px] @error('pass_quantity') border-red-300 bg-red-50 @enderror">
                @error('pass_quantity')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Reject Quantity (pcs) <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       wire:model.blur="reject_quantity"
                       placeholder="Jumlah NG / Reject"
                       class="w-full rounded-lg border-gray-300 font-mono font-bold text-red-700 text-sm focus:border-red-500 focus:ring-red-500 min-h-[42px] @error('reject_quantity') border-red-300 bg-red-50 @enderror">
                @error('reject_quantity')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @php
                $total = (int)($pass_quantity ?? 0) + (int)($reject_quantity ?? 0);
            @endphp
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col justify-center">
                <span class="text-[11px] font-medium text-gray-500 uppercase">Total Output Periode</span>
                <span class="text-xl font-bold font-mono text-gray-900">{{ number_format($total) }} pcs</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
            <button type="button"
                    wire:click="resetStep"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition">
                Reset Judgement
            </button>
            <button type="button"
                    wire:click="saveStep"
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                Simpan Judgement
            </button>
        </div>
    </div>
</div>
