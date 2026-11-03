<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-amber-50/60 border-b border-amber-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-amber-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Step 3: Shift Problems & Loss Time Log
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        Shift {{ $shift ?? '-' }}
                    </span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Catat seluruh kendala proses, mesin, mold, atau material yang terjadi selama shift.</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="addProblem"
                        class="px-3 py-1.5 text-xs font-semibold text-amber-900 bg-amber-100 hover:bg-amber-200 rounded-lg transition flex items-center gap-1.5">
                    + Tambah Kendala / Problem
                </button>
            </div>
        </div>

        <div class="p-5 space-y-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50 text-center font-semibold text-gray-600">
                        <tr>
                            <th class="px-2 py-2.5 w-12">No</th>
                            <th class="px-3 py-2.5 w-28">Jam</th>
                            <th class="px-4 py-2.5 w-48">Tipe Problem</th>
                            <th class="px-3 py-2.5 w-32">Cycle Time (s)</th>
                            <th class="px-4 py-2.5">Remarks / Tindakan Korektif</th>
                            <th class="px-2 py-2.5 w-12">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($problems as $i => $row)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-2 py-2 text-center text-gray-400 font-mono">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="time"
                                           wire:model.blur="problems.{{ $i }}.time"
                                           class="w-full text-xs rounded border-gray-300 font-mono py-1.5 focus:ring-amber-500 focus:border-amber-500 @error('problems.'.$i.'.time') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <select wire:model.blur="problems.{{ $i }}.type"
                                            class="w-full text-xs rounded border-gray-300 font-semibold py-1.5 focus:ring-amber-500 focus:border-amber-500 @error('problems.'.$i.'.type') border-red-300 bg-red-50 @enderror">
                                        @foreach ($types as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           wire:model.blur="problems.{{ $i }}.cycle_time"
                                           placeholder="Detik"
                                           class="w-full text-xs font-mono text-center rounded border-gray-300 py-1.5 focus:ring-amber-500 focus:border-amber-500 @error('problems.'.$i.'.cycle_time') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="problems.{{ $i }}.remarks"
                                           placeholder="Jelaskan kendala & tindakan perbaikan..."
                                           class="w-full text-xs rounded border-gray-300 py-1.5 focus:ring-amber-500 focus:border-amber-500 @error('problems.'.$i.'.remarks') border-red-300 bg-red-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button"
                                            wire:click="removeProblem({{ $i }})"
                                            class="text-red-500 hover:text-red-700 p-1 font-bold">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-xs text-gray-400">
                                    Tidak ada problem. Klik tombol di atas untuk menambah baris problem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <button type="button"
                        wire:click="resetStep"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg transition">
                    Reset Daftar Problem
                </button>
                <button type="button"
                        wire:click="saveStep"
                        class="px-6 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                    Simpan & Lanjut ke Review Final &rarr;
                </button>
            </div>
        </div>
    </div>
</div>
