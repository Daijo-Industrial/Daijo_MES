<div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50 space-y-3">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full {{ $isSaved ? 'bg-green-500' : 'bg-gray-400' }}"></span>
            Sampling Check (Appearance & Qty)
        </h4>
        <button type="button"
                wire:click="addSample"
                class="px-2 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded transition">
            + Tambah Sampel Box
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs bg-white rounded-lg border border-gray-200">
            <thead class="bg-gray-50 text-center font-semibold text-gray-600">
                <tr>
                    <th class="px-2 py-2">No</th>
                    <th class="px-3 py-2">Label Box</th>
                    <th class="px-3 py-2">Jumlah Sample</th>
                    <th class="px-2 py-2">Appearance</th>
                    <th class="px-2 py-2">NG Qty</th>
                    <th class="px-3 py-2">Remarks NG</th>
                    <th class="px-2 py-2 w-8">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($samples as $i => $row)
                    @php $isNg = ($row['appearance'] ?? '') === 'NG'; @endphp
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-2 py-2 text-center text-gray-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="samples.{{ $i }}.box_label"
                                   placeholder="Contoh: BOX-01 / Lot #1"
                                   class="w-full text-xs rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('samples.'.$i.'.box_label') border-red-300 bg-red-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="number"
                                   wire:model.blur="samples.{{ $i }}.quantity"
                                   placeholder="Qty pcs"
                                   class="w-24 text-xs font-mono text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 @error('samples.'.$i.'.quantity') border-red-300 bg-red-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <select wire:model.live="samples.{{ $i }}.appearance"
                                    class="text-xs font-bold rounded py-1 px-2 border-gray-300 {{ ($row['appearance'] ?? '') === 'OK' ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                                <option value="OK">OK</option>
                                <option value="NG">NG</option>
                            </select>
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="number"
                                   wire:model.blur="samples.{{ $i }}.ng_quantity"
                                   placeholder="0"
                                   {{ !$isNg ? 'disabled' : '' }}
                                   class="w-20 text-xs font-mono text-center rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 {{ !$isNg ? 'bg-gray-100 text-gray-400' : '' }} @error('samples.'.$i.'.ng_quantity') border-red-300 bg-red-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="samples.{{ $i }}.remarks"
                                   placeholder="{{ $isNg ? 'Keterangan NG' : '-' }}"
                                   {{ !$isNg ? 'disabled' : '' }}
                                   class="w-full text-xs rounded border-gray-300 py-1 focus:ring-indigo-500 focus:border-indigo-500 {{ !$isNg ? 'bg-gray-100 text-gray-400' : '' }} @error('samples.'.$i.'.remarks') border-red-300 bg-red-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <button type="button"
                                    wire:click="removeSample({{ $i }})"
                                    class="text-red-500 hover:text-red-700 p-1">
                                ✕
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button"
                wire:click="resetStep"
                class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded transition">
            Reset Sampling
        </button>
        <button type="button"
                wire:click="saveStep"
                class="px-3 py-1 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded shadow-sm transition">
            Simpan Sampling
        </button>
    </div>
</div>
