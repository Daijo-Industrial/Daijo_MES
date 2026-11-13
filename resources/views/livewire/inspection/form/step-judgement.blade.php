<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-zinc-900">4. Judgement & Output Periode</h3>
            <span class="text-xs text-zinc-400 font-mono">({{ $periodKey }})</span>
        </div>
        @if ($isSaved)
            <span class="inline-flex items-center text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200">
                Tersimpan
            </span>
        @endif
    </div>

    <div class="p-5 space-y-4">
        {{-- Pass & Reject Inputs (Side-by-Side Comparison) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
            {{-- Pass Quantity --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Pass Quantity (pcs) <span class="text-rose-600">*</span>
                </label>
                <input type="number"
                       wire:model.blur="pass_quantity"
                       placeholder="Jumlah OK / Pass"
                       class="w-full rounded-lg border-zinc-300 font-mono font-bold text-emerald-800 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('pass_quantity') border-rose-300 bg-rose-50 @enderror">
                @error('pass_quantity')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reject Quantity --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                    Reject Quantity (pcs) <span class="text-rose-600">*</span>
                </label>
                <input type="number"
                       wire:model.blur="reject_quantity"
                       placeholder="Jumlah NG / Reject"
                       class="w-full rounded-lg border-zinc-300 font-mono font-bold text-rose-800 text-sm focus:border-zinc-900 focus:ring-zinc-900 min-h-[42px] @error('reject_quantity') border-rose-300 bg-rose-50 @enderror">
                @error('reject_quantity')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Total Output Box --}}
        @php
            $total = (int)($pass_quantity ?? 0) + (int)($reject_quantity ?? 0);
        @endphp
        <div class="bg-zinc-50 p-4 rounded-xl border border-zinc-200 flex items-center justify-between max-w-xl">
            <div>
                <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider block">Total Output Periode</span>
                <span class="text-xs text-zinc-400">Pass Qty + Reject Qty</span>
            </div>
            <span class="text-2xl font-bold font-mono text-zinc-900">{{ number_format($total) }} pcs</span>
        </div>
    </div>
</div>
