<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-zinc-900">2. Dimension Measurements</h3>
            <span class="text-xs text-zinc-400 font-mono">({{ $periodKey }})</span>
        </div>
        <div class="flex items-center gap-2">
            @if ($isSaved)
                <span class="inline-flex items-center text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200">
                    Tersimpan
                </span>
            @endif
            <button type="button"
                    wire:click="addDimension"
                    class="px-3 py-1.5 text-xs font-semibold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Dimensi</span>
            </button>
        </div>
    </div>

    <div class="p-5 space-y-4">
        @if (empty($dimensions))
            <div class="text-center py-6 border-2 border-dashed border-zinc-200 rounded-lg">
                <svg class="mx-auto h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="mt-2 text-xs text-zinc-500 font-medium">Belum ada baris pengukuran dimensi untuk periode ini.</p>
                <button type="button"
                        wire:click="addDimension"
                        class="mt-2 inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md text-zinc-900 bg-zinc-100 hover:bg-zinc-200 border border-zinc-300 transition">
                    + Tambah Pengukuran Pertama
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-xs">
                    <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                        <tr>
                            <th class="px-2 py-2">No</th>
                            <th class="px-3 py-2 text-left">Area / Titik</th>
                            <th class="px-2 py-2">Batas Bawah</th>
                            <th class="px-2 py-2">Batas Atas</th>
                            <th class="px-2 py-2">Satuan</th>
                            <th class="px-3 py-2">Nilai Aktual</th>
                            <th class="px-2 py-2">Judgement</th>
                            <th class="px-3 py-2 text-left">Remarks</th>
                            <th class="px-2 py-2 w-10">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach ($dimensions as $i => $row)
                            <tr class="hover:bg-zinc-50/50 transition">
                                <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="dimensions.{{ $i }}.area"
                                           placeholder="Area/titik ukur"
                                           class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('dimensions.'.$i.'.area') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="dimensions.{{ $i }}.lower_limit"
                                           placeholder="Min"
                                           class="w-20 text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('dimensions.'.$i.'.lower_limit') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="dimensions.{{ $i }}.upper_limit"
                                           placeholder="Max"
                                           class="w-20 text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('dimensions.'.$i.'.upper_limit') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <select wire:model.blur="dimensions.{{ $i }}.limit_uom"
                                            class="w-16 text-xs text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 font-semibold">
                                        <option value="mm">mm</option>
                                        <option value="cm">cm</option>
                                        <option value="μm">μm</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="dimensions.{{ $i }}.actual_value"
                                           placeholder="Aktual"
                                           class="w-24 text-xs font-mono font-bold text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('dimensions.'.$i.'.actual_value') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <select wire:model.live="dimensions.{{ $i }}.judgement"
                                            class="text-xs font-bold rounded py-1.5 px-2 border-zinc-300 {{ ($row['judgement'] ?? '') === 'OK' ? 'text-emerald-800 bg-emerald-50' : 'text-rose-800 bg-rose-50' }}">
                                        <option value="OK">OK</option>
                                        <option value="NG">NG</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="dimensions.{{ $i }}.remarks"
                                           placeholder="{{ ($row['judgement'] ?? '') === 'NG' ? 'Wajib diisi jika NG' : 'Catatan opsional' }}"
                                           class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('dimensions.'.$i.'.remarks') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button"
                                            wire:click="removeDimension({{ $i }})"
                                            class="text-rose-500 hover:text-rose-700 p-1 font-bold">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>