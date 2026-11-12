<div class="border border-zinc-200 rounded-lg p-4 bg-zinc-50/70 space-y-3">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold text-zinc-800 uppercase tracking-wider flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
            Sampling Check (Appearance & Qty)
        </h4>
        <button type="button"
                wire:click="addSample"
                class="px-2.5 py-1 text-xs font-semibold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Sampel Box</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 text-xs bg-white rounded-lg border border-zinc-200">
            <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                <tr>
                    <th class="px-2 py-2">No</th>
                    <th class="px-3 py-2 text-left">Label Box</th>
                    <th class="px-3 py-2">Jumlah Sample</th>
                    <th class="px-2 py-2">Appearance</th>
                    <th class="px-2 py-2">NG Qty</th>
                    <th class="px-3 py-2 text-left">Remarks NG</th>
                    <th class="px-2 py-2 w-8">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($samples as $i => $row)
                    @php $isNg = ($row['appearance'] ?? '') === 'NG'; @endphp
                    <tr class="hover:bg-zinc-50/50 transition">
                        <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="samples.{{ $i }}.box_label"
                                   placeholder="Contoh: BOX-01 / Lot #1"
                                   class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('samples.'.$i.'.box_label') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="number"
                                   wire:model.blur="samples.{{ $i }}.quantity"
                                   placeholder="Qty pcs"
                                   class="w-24 text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('samples.'.$i.'.quantity') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <select wire:model.live="samples.{{ $i }}.appearance"
                                    class="text-xs font-bold rounded py-1.5 px-2 border-zinc-300 {{ ($row['appearance'] ?? '') === 'OK' ? 'text-emerald-800 bg-emerald-50' : 'text-rose-800 bg-rose-50' }}">
                                <option value="OK">OK</option>
                                <option value="NG">NG</option>
                            </select>
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="number"
                                   wire:model.blur="samples.{{ $i }}.ng_quantity"
                                   placeholder="0"
                                   {{ !$isNg ? 'disabled' : '' }}
                                   class="w-20 text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 {{ !$isNg ? 'bg-zinc-100 text-zinc-400' : '' }} @error('samples.'.$i.'.ng_quantity') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="samples.{{ $i }}.remarks"
                                   placeholder="{{ $isNg ? 'Keterangan NG' : '-' }}"
                                   {{ !$isNg ? 'disabled' : '' }}
                                   class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 {{ !$isNg ? 'bg-zinc-100 text-zinc-400' : '' }} @error('samples.'.$i.'.remarks') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <button type="button"
                                    wire:click="removeSample({{ $i }})"
                                    class="text-rose-500 hover:text-rose-700 p-1 font-bold">
                                ✕
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>