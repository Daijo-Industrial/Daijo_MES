<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    {{-- Card Header --}}
    <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ count($problems) > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
            <h3 class="text-sm font-bold text-zinc-900">5. Masalah & Loss Time Periode</h3>
            <span class="text-xs text-zinc-500 font-mono font-semibold bg-white px-2 py-0.5 rounded border border-zinc-200">
                {{ $start_time }} - {{ $end_time }} WIB
            </span>
        </div>

        <div class="flex items-center gap-2">
            @if (empty($problems))
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Normal (0 Masalah)</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded border border-amber-200">
                    <span>{{ count($problems) }} Masalah Tercatat</span>
                </span>
            @endif

            <button type="button"
                    wire:click="addProblem"
                    class="px-3.5 py-2 text-xs font-bold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1.5 min-h-[40px]">
                <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Masalah</span>
            </button>
        </div>
    </div>

    <div class="p-5 space-y-4">
        @if (empty($problems))
            {{-- Default Normal Production State --}}
            <div class="text-center py-8 px-4 border-2 border-dashed border-emerald-200 bg-emerald-50/30 rounded-xl space-y-3">
                <div class="w-10 h-10 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-zinc-900">Produksi Normal Tanpa Kendala</h4>
                    <p class="text-xs text-zinc-500 max-w-md mx-auto mt-1">
                        Tidak ada kendala yang dicatat pada rentang jam <strong class="text-zinc-700 font-mono">{{ $start_time }} - {{ $end_time }} WIB</strong>. Jika terjadi trouble mesin, mold, atau material, klik tombol di bawah untuk mencatat kejadian.
                    </p>
                </div>
                <button type="button"
                        wire:click="addProblem"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-lg text-zinc-900 bg-white hover:bg-zinc-50 border border-zinc-300 shadow-sm transition">
                    <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Catat Kendala / Loss Time</span>
                </button>
            </div>
        @else
            {{-- List of Problem Cards --}}
            <div class="space-y-3">
                @foreach ($problems as $i => $prob)
                    <div class="p-4 rounded-xl border border-zinc-200 bg-zinc-50/60 hover:bg-zinc-50 transition space-y-3" wire:key="prob-{{ $prob['row_key'] ?? $i }}">
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-200/70 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold font-mono px-2 py-0.5 rounded bg-zinc-200 text-zinc-800">
                                    #{{ $i + 1 }}
                                </span>
                                <span class="text-xs font-bold text-zinc-800 uppercase tracking-wide">
                                    Kendala Produksi
                                </span>
                            </div>
                            <button type="button"
                                    wire:click="removeProblem({{ $i }})"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-800 p-1 rounded hover:bg-rose-50 transition"
                                    title="Hapus baris masalah">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            {{-- Tipe Masalah --}}
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                    Tipe Masalah <span class="text-rose-600">*</span>
                                </label>
                                <select wire:model.live="problems.{{ $i }}.type"
                                        class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[42px] font-semibold @error('problems.'.$i.'.type') border-rose-300 bg-rose-50 @enderror">
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                                @error('problems.'.$i.'.type')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Jam Kejadian --}}
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                    Jam Kejadian (WIB) <span class="text-rose-600">*</span>
                                </label>
                                <input type="time"
                                       wire:model.live="problems.{{ $i }}.time"
                                       class="w-full text-xs rounded-lg border-zinc-300 font-mono font-bold py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[42px] @error('problems.'.$i.'.time') border-rose-300 bg-rose-50 @enderror">
                                <p class="text-[11px] text-zinc-400 mt-1 font-mono">Rentang periode: {{ $start_time }} - {{ $end_time }}</p>
                                @error('problems.'.$i.'.time')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Loss Time (Menit) --}}
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                    Loss Time (Menit) <span class="text-rose-600">*</span>
                                </label>
                                <input type="number"
                                       min="1"
                                       wire:model.live="problems.{{ $i }}.cycle_time"
                                       placeholder="Durasi loss time..."
                                       class="w-full text-xs rounded-lg border-zinc-300 font-mono font-bold py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[42px] @error('problems.'.$i.'.cycle_time') border-rose-300 bg-rose-50 @enderror">
                                @error('problems.'.$i.'.cycle_time')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Remarks / Keterangan --}}
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                    Deskripsi Trouble & Penanganan (Remarks)
                                </label>
                                <input type="text"
                                       wire:model.live="problems.{{ $i }}.remarks"
                                       placeholder="Catatan penyebab trouble, tindakan perbaikan, atau catatan operator..."
                                       class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[42px] @error('problems.'.$i.'.remarks') border-rose-300 bg-rose-50 @enderror">
                                @error('problems.'.$i.'.remarks')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Period Loss Time Summary Bar --}}
            @php
                $totalLossTime = collect($problems)->sum(fn ($p) => (int) ($p['cycle_time'] ?? 0));
            @endphp
            <div class="bg-zinc-100 p-3.5 rounded-xl border border-zinc-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="text-zinc-600 font-medium">
                    Total Kendala: <strong class="text-zinc-900 font-bold">{{ count($problems) }} baris</strong>
                </span>
                <span class="text-zinc-600 font-medium font-mono">
                    Akumulasi Loss Time: <strong class="text-zinc-900 font-bold text-sm">{{ $totalLossTime }} menit</strong>
                </span>
            </div>
        @endif
    </div>
</div>
