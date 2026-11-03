<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-green-500' : 'bg-amber-400' }}"></span>
            <h3 class="text-sm font-bold text-gray-900">2. Dimension Measurements</h3>
            <span class="text-xs text-gray-400">({{ $periodKey }})</span>
        </div>
        <div class="flex items-center gap-2">
            @if ($isSaved)
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                    ✓ Tersimpan
                </span>
            @endif
            <button type="button"
                    wire:click="addDimension"
                    class="px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition flex items-center gap-1">
                + Tambah Dimensi
            </button>
        </div>
    </div>

    <div class="p-5 space-y-4">
        @if (empty($dimensions))
            <div class="text-center py-6 border-2 border-dashed border-gray-200 rounded-lg">
                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="mt-2 text-xs text-gray-500 font-medium">Belum ada baris pengukuran dimensi untuk periode ini.</p>
                <button type="button"
                        wire:click="addDimension"
                        class="mt-2 inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition">
                    + Tambah Pengukuran Pertama
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50 text-center font-semibold text-gray-600">
                        <tr>
                            <th class="px-2 py-2">No</th>
                            <th class="px-3 py-2">Area / Titik</th>
                            <th class="px-2 py-2">Batas Bawah</th>
                            <th class="px-2 py-2">Batas Atas</th>
                            <th class="px-2 py-2">Satuan</th>
                            <th class="px-3 py-2">Nilai Aktual</th>
                            <th class="px-2 py-2">Judgement</th>
                            <th class="px-3 py-2">Remarks</th>
                            <th class="px-2 py-2 w-10">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($dimensions as $i => $row)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-2 py-2 text-center text-gray-500 font-mono">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="dimensions.{{ $i }}.area"
                                           placeholder="Area/titik ukur"
                                           class="w-full text-xs rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('dimensions.'.$i.'.area') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="dimensions.{{ $i }}.lower_limit"
                                           placeholder="Min"
                                           class="w-20 text-xs font-mono text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('dimensions.'.$i.'.lower_limit') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           step="any"
                                           wire:model.blur="dimensions.{{ $i }}.upper_limit"
                                           placeholder="Max"
                                           class="w-20 text-xs font-mono text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('dimensions.'.$i.'.upper_limit') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <select wire:model.blur="dimensions.{{ $i }}.limit_uom"
                                            class="w-16 text-xs text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500">
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
                                           class="w-24 text-xs font-mono font-bold text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('dimensions.'.$i.'.actual_value') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <select wire:model.live="dimensions.{{ $i }}.judgement"
                                            class="text-xs font-bold rounded py-1 px-2 border-gray-300 {{ ($row['judgement'] ?? '') === 'OK' ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                                        <option value="OK">OK</option>
                                        <option value="NG">NG</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="dimensions.{{ $i }}.remarks"
                                           placeholder="{{ ($row['judgement'] ?? '') === 'NG' ? 'Wajib diisi jika NG' : 'Catatan opsional' }}"
                                           class="w-full text-xs rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('dimensions.'.$i.'.remarks') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button"
                                            wire:click="removeDimension({{ $i }})"
                                            class="text-red-500 hover:text-red-700 p-1">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
            <button type="button"
                    wire:click="resetStep"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition">
                Reset Dimensi
            </button>
            <button type="button"
                    wire:click="saveStep"
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                Simpan Dimensi
            </button>
        </div>
    </div>
</div>
