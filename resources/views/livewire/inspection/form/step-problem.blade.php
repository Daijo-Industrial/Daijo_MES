<div class="space-y-6">
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                    Step 3: Shift Problems & Loss Time Log
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-200">
                        Shift {{ $shift ?? '-' }}
                    </span>
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">Catat seluruh kendala proses, mesin, mold, atau material yang terjadi selama shift.</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="setNoProblemPreset"
                        class="px-3 py-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 rounded-lg shadow-sm transition">
                    Set Normal (Tanpa Kendala)
                </button>
                <button type="button"
                        wire:click="addProblem"
                        class="px-3 py-1.5 text-xs font-semibold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Kendala</span>
                </button>
            </div>
        </div>

        <div class="p-5 space-y-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-xs">
                    <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                        <tr>
                            <th class="px-2 py-2.5 w-12">No</th>
                            <th class="px-3 py-2.5 w-28">Jam</th>
                            <th class="px-4 py-2.5 w-48 text-left">Tipe Problem</th>
                            <th class="px-3 py-2.5 w-32">Cycle Time (s)</th>
                            <th class="px-4 py-2.5 text-left">Remarks / Tindakan Korektif</th>
                            <th class="px-2 py-2.5 w-12">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @forelse ($problems as $i => $row)
                            <tr class="hover:bg-zinc-50/50 transition">
                                <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="time"
                                           wire:model.blur="problems.{{ $i }}.time"
                                           class="w-full text-xs rounded border-zinc-300 font-mono py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$i.'.time') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <select wire:model.blur="problems.{{ $i }}.type"
                                            class="w-full text-xs rounded border-zinc-300 font-semibold py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$i.'.type') border-rose-300 bg-rose-50 @enderror">
                                        @foreach ($types as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number"
                                           wire:model.blur="problems.{{ $i }}.cycle_time"
                                           placeholder="Detik"
                                           class="w-full text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$i.'.cycle_time') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text"
                                           wire:model.blur="problems.{{ $i }}.remarks"
                                           placeholder="Jelaskan kendala & tindakan perbaikan..."
                                           class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$i.'.remarks') border-rose-300 bg-rose-50 @enderror">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <button type="button"
                                            wire:click="removeProblem({{ $i }})"
                                            class="text-rose-500 hover:text-rose-700 p-1 font-bold">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-400">
                                    Tidak ada problem. Klik tombol di atas untuk menambah baris problem atau terapkan preset normal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Bottom Navigation Action Bar --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
        <div>
            <button type="button"
                    wire:click="$dispatch('prevStep')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Step 2 (Detail Periode)</span>
            </button>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="resetStep"
                    class="px-4 py-2.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition">
                Reset Problem
            </button>
            <button type="button"
                    wire:click="saveStep"
                    class="inline-flex items-center gap-2 px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                <span>Simpan & Lanjut ke Review Final</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</div>