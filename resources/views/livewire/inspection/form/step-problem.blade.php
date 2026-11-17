<div class="space-y-6">
    {{-- Operational Context & Shift Overview Bar --}}
    <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-4 text-zinc-700">
            <div class="flex items-center gap-1.5">
                <span class="text-zinc-400 font-medium">Shift:</span>
                <span class="font-bold text-zinc-900 bg-white px-2 py-0.5 rounded border border-zinc-200">Shift {{ $shift ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-zinc-400 font-medium">Inspector:</span>
                <span class="font-semibold text-zinc-900">{{ $inspector ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-zinc-400 font-medium">Part:</span>
                <span class="font-bold text-zinc-900 font-mono">{{ $part_number ?? '-' }}</span>
                <span class="text-zinc-500 font-medium truncate max-w-xs">({{ $part_name ?? '-' }})</span>
            </div>
        </div>
        <div class="text-[11px] text-zinc-500 font-mono">
            Step 3 &bull; Rekap Kendala Shift
        </div>
    </div>

    {{-- Shift KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Total Kendala --}}
        <div class="bg-white border rounded-xl p-4 shadow-sm {{ $totalProblems > 0 ? 'border-amber-200 bg-amber-50/20' : 'border-zinc-200' }}">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Total Kendala Shift</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono {{ $totalProblems > 0 ? 'text-amber-800' : 'text-zinc-900' }}">
                    {{ $totalProblems }}
                </span>
                <span class="text-xs text-zinc-500">kejadian</span>
            </div>
        </div>

        {{-- Total Loss Time --}}
        <div class="bg-white border rounded-xl p-4 shadow-sm {{ $totalLossTime > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-zinc-200' }}">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Total Loss Time</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono {{ $totalLossTime > 0 ? 'text-rose-700' : 'text-zinc-900' }}">
                    {{ $totalLossTime }}
                </span>
                <span class="text-xs text-zinc-500">menit</span>
            </div>
        </div>

        {{-- Status Shift --}}
        <div class="bg-white border rounded-xl p-4 shadow-sm {{ $totalProblems === 0 ? 'border-emerald-200 bg-emerald-50/20' : 'border-amber-200' }}">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block">Status Produksi Shift</span>
            <div class="mt-2">
                @if ($totalProblems === 0)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Normal (Tanpa Kendala)</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        <span>Ada Kendala Terdeteksi</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Container: Grouped Problem Records by Period --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-zinc-900 uppercase tracking-wide">
                    Rekap Kendala Per Periode Inspeksi
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Data kendala terdistribusi otomatis sesuai jam kejadian pada setiap periode.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="addProblem"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Kendala</span>
                </button>
            </div>
        </div>

        <div class="p-5 space-y-6">
            @foreach ($periodRecap as $pNum => $pData)
                <div class="border border-zinc-200 rounded-xl overflow-hidden">
                    {{-- Period Group Header --}}
                    <div class="bg-zinc-100/70 px-4 py-2.5 border-b border-zinc-200 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-zinc-900">Periode {{ $pNum }}</span>
                            <span class="text-xs font-mono text-zinc-600 bg-white px-2 py-0.5 rounded border border-zinc-200">
                                {{ $pData['time_range'] }} WIB
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            @if (count($pData['problems']) > 0)
                                <span class="font-bold text-rose-700 font-mono bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                    {{ count($pData['problems']) }} Kendala &bull; {{ $pData['loss_time'] }} mnt
                                </span>
                            @else
                                <span class="font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Normal (0 Kendala)
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Period Problems Table or Empty State --}}
                    @if (count($pData['problems']) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs divide-y divide-zinc-200">
                                <thead class="bg-zinc-50/50 text-zinc-600 font-semibold text-center">
                                    <tr>
                                        <th class="px-2 py-2 w-12">No</th>
                                        <th class="px-3 py-2 w-28">Jam</th>
                                        <th class="px-3 py-2 w-48 text-left">Tipe Problem</th>
                                        <th class="px-3 py-2 w-32">Loss (mnt)</th>
                                        <th class="px-3 py-2 text-left">Remarks / Tindakan</th>
                                        <th class="px-2 py-2 w-12">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 bg-white">
                                    @foreach ($pData['problems'] as $subIdx => $item)
                                        @php $idx = $item['index']; @endphp
                                        <tr class="hover:bg-zinc-50/50 transition">
                                            <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $subIdx + 1 }}</td>
                                            <td class="px-2 py-1.5">
                                                <input type="time"
                                                       wire:model.blur="problems.{{ $idx }}.time"
                                                       class="w-full text-xs rounded border-zinc-300 font-mono py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$idx.'.time') border-rose-300 bg-rose-50 @enderror">
                                            </td>
                                            <td class="px-2 py-1.5">
                                                <select wire:model.blur="problems.{{ $idx }}.type"
                                                        class="w-full text-xs rounded border-zinc-300 font-semibold py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$idx.'.type') border-rose-300 bg-rose-50 @enderror">
                                                    @foreach ($types as $t)
                                                        <option value="{{ $t }}">{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-1.5">
                                                <input type="number"
                                                       wire:model.blur="problems.{{ $idx }}.cycle_time"
                                                       placeholder="Menit"
                                                       class="w-full text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$idx.'.cycle_time') border-rose-300 bg-rose-50 @enderror">
                                            </td>
                                            <td class="px-2 py-1.5">
                                                <input type="text"
                                                       wire:model.blur="problems.{{ $idx }}.remarks"
                                                       placeholder="Jelaskan kendala & tindakan perbaikan..."
                                                       class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('problems.'.$idx.'.remarks') border-rose-300 bg-rose-50 @enderror">
                                            </td>
                                            <td class="px-2 py-1.5 text-center">
                                                <button type="button"
                                                        wire:click="removeProblem({{ $idx }})"
                                                        class="text-rose-500 hover:text-rose-700 p-1 font-bold text-sm"
                                                        title="Hapus baris kendala">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-4 py-3 bg-white text-xs text-zinc-500 flex items-center justify-between">
                            <span class="text-zinc-400 italic">Tidak ada kendala pada periode ini.</span>
                            <span class="text-emerald-700 font-medium">Kondisi Normal</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step 3 Floating Bottom Navigation Dock --}}
    <div class="fixed bottom-0 left-0 right-0 lg:left-64 z-30 bg-white/95 backdrop-blur-md border-t border-zinc-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] py-3 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div>
                <button type="button"
                        wire:click="$dispatch('prevStep')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-zinc-300 text-xs sm:text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-xs transition">
                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Step 2 (Detail Periode)</span>
                </button>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        wire:click="resetStep"
                        wire:confirm="Yakin ingin mereset seluruh daftar kendala shift ini? Seluruh data kendala yang sudah dicatat akan dihapus."
                        class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition">
                    Reset Kendala
                </button>
                <button type="button"
                        wire:click="saveStep"
                        class="inline-flex items-center gap-2 px-5 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                    <span>Simpan & Lanjut ke Review Final</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
