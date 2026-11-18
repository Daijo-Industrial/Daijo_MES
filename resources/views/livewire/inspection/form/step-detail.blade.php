<div class="space-y-6 pb-20">
    @if (! $isWizardActive)
        {{-- LEVEL 1: PERIOD HUB (Landing Page for Period Selection & Recaps) --}}

        {{-- Minimized Operational Context Bar --}}
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
                <div class="flex items-center gap-1.5">
                    <span class="text-zinc-400 font-medium">Mesin:</span>
                    <span class="font-semibold text-zinc-900">{{ $machine_number ?? '-' }}</span>
                </div>
            </div>
            <div class="text-[11px] text-zinc-500 font-mono">
                Shift {{ $shift ?? 1 }} &bull; Total 4 Periode
            </div>
        </div>

        {{-- Period Hub Grid Section --}}
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-3">
                <h2 class="text-sm font-bold text-zinc-900 uppercase tracking-wide">Pilih Periode Inspeksi Shift {{ $shift ?? 1 }}</h2>
                @if ($hasAnySavedPeriod)
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Data Periode Tersedia
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200">
                        Belum Ada Periode Selesai
                    </span>
                @endif
            </div>

            {{-- 4 Period Cards Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach (range(1, 4) as $p)
                    @php
                        $pData = $periodsSummary[$p];
                        $isSaved = $pData['isSaved'];
                        $isRecommended = $p === $recommendedPeriod;
                    @endphp

                    @if ($isSaved)
                        {{-- COMPLETED PERIOD CARD --}}
                        <div class="border border-emerald-300 bg-white ring-1 ring-emerald-100 shadow-sm rounded-xl p-4 flex flex-col justify-between space-y-3">
                            <div class="space-y-3">
                                {{-- Card Header --}}
                                <div class="flex items-center justify-between gap-2 border-b border-emerald-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-zinc-900">Periode {{ $p }}</span>
                                        <span class="text-xs font-mono text-zinc-500 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                            {{ $pData['time_range'] }} WIB
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Selesai Tersimpan</span>
                                    </span>
                                </div>

                                {{-- 4 Pillars Ultra-Minimal Recap --}}
                                <div class="bg-zinc-50/70 border border-zinc-200/80 rounded-lg p-2.5 space-y-1.5 text-xs">
                                    {{-- Pillar 1: First Piece --}}
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-zinc-500 font-medium text-[11px]">1. First Piece</span>
                                        <span class="font-mono text-zinc-900 text-[11px] text-right truncate">
                                            App:
                                            @if ($pData['first_appearance'] === 'OK')
                                                <strong class="text-emerald-700">OK</strong>
                                            @elseif ($pData['first_appearance'] === 'NG')
                                                <strong class="text-rose-700">NG</strong>
                                            @else
                                                <span class="text-zinc-400">-</span>
                                            @endif
                                            &bull; Berat: <strong class="text-zinc-800">{{ $pData['first_weight'] ? $pData['first_weight'] . ($pData['first_weight_uom'] ?: 'g') : '-' }}</strong>
                                            &bull; Fit: <span class="text-zinc-800">{{ $pData['first_fitting'] ?: '-' }}</span>
                                        </span>
                                    </div>

                                    {{-- Pillar 2: Dimensi --}}
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-zinc-200/50">
                                        <span class="text-zinc-500 font-medium text-[11px]">2. Dimensi</span>
                                        <span class="font-mono text-zinc-900 text-[11px] text-right">
                                            {{ $pData['dim_total'] }} Titik Ukur
                                            (<span class="text-emerald-700 font-semibold">{{ $pData['dim_ok'] }} OK</span>@if ($pData['dim_ng'] > 0), <span class="text-rose-700 font-semibold">{{ $pData['dim_ng'] }} NG</span>@endif)
                                        </span>
                                    </div>

                                    {{-- Pillar 3: Second & Sampling --}}
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-zinc-200/50">
                                        <span class="text-zinc-500 font-medium text-[11px]">3. Second & Sampling</span>
                                        <span class="font-mono text-zinc-900 text-[11px] text-right truncate">
                                            @if (!$pData['second_skip_lot'] && $pData['second_lot_size'])
                                                Lot: {{ number_format((int)$pData['second_lot_size']) }} &bull;
                                            @endif
                                            {{ $pData['sample_box_count'] }} Box Sample (NG: <span class="{{ $pData['sample_ng_total'] > 0 ? 'text-rose-700 font-bold' : 'text-zinc-800' }}">{{ $pData['sample_ng_total'] }}</span>)
                                            &bull; SNP: {{ $pData['packaging_snp'] ? number_format((int)$pData['packaging_snp']) . ' pcs' : '-' }}
                                        </span>
                                    </div>

                                    {{-- Pillar 4: Judgement & Output --}}
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-zinc-200/50">
                                        <span class="text-zinc-500 font-medium text-[11px]">4. Judgement & Output</span>
                                        <span class="font-mono text-zinc-900 text-[11px] text-right">
                                            Pass: <strong class="text-emerald-700">{{ number_format($pData['pass_quantity']) }}</strong>
                                            &bull; Reject: <strong class="{{ $pData['reject_quantity'] > 0 ? 'text-rose-700' : 'text-zinc-700' }}">{{ number_format($pData['reject_quantity']) }}</strong>
                                            &bull; Total: <strong class="text-zinc-900">{{ number_format($pData['total_output']) }} pcs</strong>
                                        </span>
                                    </div>

                                    {{-- Pillar 5: Masalah & Loss Time --}}
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-zinc-200/50">
                                        <span class="text-zinc-500 font-medium text-[11px]">5. Masalah</span>
                                        <span class="font-mono text-[11px] text-right">
                                            @if (! empty($pData['problem_count']) && $pData['problem_count'] > 0)
                                                <strong class="text-rose-700">{{ $pData['problem_count'] }} Masalah</strong>
                                                (<span class="text-zinc-800">{{ $pData['problem_loss_time'] }} mnt</span>)
                                            @else
                                                <span class="text-emerald-700 font-semibold">Normal (0 Masalah)</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                                <button type="button"
                                        wire:click="resetPeriod({{ $p }})"
                                        wire:confirm="Yakin ingin mereset seluruh data inspeksi pada Periode {{ $p }}?"
                                        class="px-2.5 py-1.5 border border-rose-200 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-md transition">
                                    <span>Reset</span>
                                </button>
                                <button type="button"
                                        wire:click="editPeriod({{ $p }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-zinc-300 hover:bg-zinc-50 text-xs font-bold text-zinc-800 rounded-md shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>Edit Periode {{ $p }}</span>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- UNCOMPLETED PERIOD CARD --}}
                        <div class="border border-zinc-200 bg-white hover:border-zinc-300 shadow-sm rounded-xl p-4 flex flex-col justify-between space-y-4">
                            {{-- Card Header --}}
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-zinc-900">Periode {{ $p }}</span>
                                    <span class="text-xs font-mono text-zinc-500 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                        {{ $pData['time_range'] }} WIB
                                    </span>
                                </div>
                                @if ($isRecommended)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-900 text-white">
                                        Shift Saat Ini
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-600 border border-zinc-200">
                                        Belum Diisi
                                    </span>
                                @endif
                            </div>

                            <button type="button"
                                    wire:click="startPeriod({{ $p }})"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white font-bold rounded-lg text-xs shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                                <span>Mulai Inspeksi Periode {{ $p }}</span>
                            </button>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Hub Floating Bottom Navigation Dock --}}
        <div class="fixed bottom-0 left-0 right-0 lg:left-64 z-30 bg-white/95 backdrop-blur-md border-t border-zinc-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] py-3 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <div>
                    <button type="button"
                            wire:click="$dispatch('prevStep')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 border border-zinc-300 text-xs sm:text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-xs transition">
                        <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kembali ke Step 1 (Header)</span>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    @if ($hasAnySavedPeriod)
                        <button type="button"
                                wire:click="proceedToStep3"
                                class="inline-flex items-center gap-2 px-5 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                            <span>Lanjut ke Step 3 (Masalah & Loss Time)</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    @else
                        <button type="button"
                                disabled
                                title="Selesaikan dan simpan minimal 1 periode sebelum melanjutkan ke Step 3"
                                class="inline-flex items-center gap-2 px-5 py-2 border border-zinc-300 shadow-sm text-xs sm:text-sm font-bold rounded-lg text-zinc-400 bg-zinc-100 cursor-not-allowed">
                            <span>Lanjut ke Step 3 (Masalah & Loss Time)</span>
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>

    @else
        {{-- LEVEL 2: FOCUSED SUB-WIZARD (Inspecting 1 Subcomponent at a time for Selected Period) --}}

        {{-- Ultra-Compact Single-Row Bar (Zero Layout Shift) --}}
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs relative">
            {{-- Left: Navigation, Title & Interactive Time Popover --}}
            <div class="flex items-center gap-3">
                <button type="button"
                        wire:click="exitToHub"
                        class="inline-flex items-center gap-1 px-2 py-1 border border-zinc-200 hover:border-zinc-300 text-xs font-semibold rounded-md text-zinc-700 bg-zinc-50 hover:bg-zinc-100 transition shadow-xs"
                        title="Kembali ke Hub Periode">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Hub</span>
                </button>

                <div class="h-4 w-px bg-zinc-200"></div>

                <span class="font-bold text-zinc-900 text-sm tracking-tight whitespace-nowrap">
                    Periode {{ $period }}
                </span>

                {{-- Time Popover Dropdown --}}
                <div class="relative" x-data="{ openTime: @json($errors->has('start_time') || $errors->has('end_time')) }">
                    <button type="button"
                            @click="openTime = !openTime"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-mono font-bold rounded-lg border transition shadow-xs {{ $errors->has('start_time') || $errors->has('end_time') ? 'border-rose-300 bg-rose-50 text-rose-800' : 'border-zinc-200 bg-zinc-50 hover:bg-zinc-100 text-zinc-800 hover:border-zinc-300' }}"
                            title="Klik untuk mengubah jam inspeksi periode">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $start_time ?: '-' }} - {{ $end_time ?: '-' }} WIB</span>
                        <svg class="w-3 h-3 text-zinc-400 transition" :class="{ 'rotate-180': openTime }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Floating Popover Box (Zero Layout Shift) --}}
                    <div x-show="openTime"
                         @click.outside="openTime = false"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 mt-2 z-30 w-72 bg-white rounded-xl border border-zinc-200 shadow-xl p-3.5 space-y-3">
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                            <span class="text-xs font-bold text-zinc-900">Ubah Jam Periode {{ $period }}</span>
                            <button type="button"
                                    @click="openTime = false"
                                    class="text-xs font-semibold text-zinc-400 hover:text-zinc-800 p-0.5">
                                ✕
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 mb-1">
                                    Jam Mulai <span class="text-rose-600">*</span>
                                </label>
                                <input type="time"
                                       wire:model.live="start_time"
                                       class="w-full rounded-md border-zinc-300 text-xs focus:border-zinc-900 focus:ring-zinc-900 font-mono py-1.5 @error('start_time') border-rose-300 bg-rose-50/50 @enderror">
                                @error('start_time')
                                    <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 mb-1">
                                    Jam Selesai <span class="text-rose-600">*</span>
                                </label>
                                <input type="time"
                                       wire:model.live="end_time"
                                       class="w-full rounded-md border-zinc-300 text-xs focus:border-zinc-900 focus:ring-zinc-900 font-mono py-1.5 @error('end_time') border-rose-300 bg-rose-50/50 @enderror">
                                @error('end_time')
                                    <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="pt-1 flex justify-end">
                            <button type="button"
                                    @click="openTime = false"
                                    class="px-3 py-1 bg-zinc-900 hover:bg-zinc-800 text-white text-[11px] font-bold rounded-md shadow-xs transition">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Middle: Monospaced Context Badges --}}
            <div class="hidden lg:flex items-center gap-1.5 text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded border border-zinc-200 bg-zinc-100 text-zinc-700 font-semibold" title="Shift Kerja">
                    Shift {{ $shift ?? '-' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded border border-zinc-200 bg-zinc-100 text-zinc-900 font-mono font-bold" title="Part: {{ $part_number ?? '-' }} ({{ $part_name ?? '-' }})">
                    {{ $part_number ?? '-' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded border border-zinc-200 bg-zinc-100 text-zinc-800 font-semibold" title="Nomor Mesin">
                    {{ $machine_number ?? '-' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded border border-zinc-200 bg-zinc-100 text-zinc-700 font-medium truncate max-w-[140px]" title="Inspector: {{ $inspector ?? '-' }}">
                    {{ $inspector ?? '-' }}
                </span>
            </div>

            {{-- Right: Actions Toolbar --}}
            <div class="flex items-center gap-2">
                @if ($period > 1)
                    <button type="button"
                            wire:click="copyFromPreviousPeriod"
                            wire:confirm="Salin format pengukuran dari Periode {{ $period - 1 }} ke Periode {{ $period }}?"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-zinc-300 text-xs font-semibold text-zinc-700 bg-white hover:bg-zinc-50 rounded-lg shadow-xs transition"
                            title="Salin template pengukuran dari Periode {{ $period - 1 }}">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        <span>Salin P{{ $period - 1 }}</span>
                    </button>
                @endif

                <button type="button"
                        wire:click="exitToHub"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-zinc-300 text-xs font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-xs transition">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <span class="hidden sm:inline">Simpan Draft &</span> Kembali ke Hub
                </button>
            </div>
        </div>

        {{-- LEVEL 2 MAIN BODY: 2-COLUMN LAYOUT (Sticky Left Rail + Right Form Panel) --}}
        <div class="lg:flex lg:items-start lg:gap-6">
            {{-- Sticky Left Vertical Rail --}}
            <aside class="lg:w-72 flex-shrink-0 lg:sticky lg:top-4 mb-6 lg:mb-0">
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3 space-y-2">
                    <div class="px-2 py-1 border-b border-zinc-100 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Tahapan Periode {{ $period }}</span>
                        <span class="text-[11px] font-mono text-zinc-500 font-semibold">{{ $subStep }}/5</span>
                    </div>

                    {{-- Pillar Buttons List (Horizontal scroll on mobile, vertical stack on desktop) --}}
                    <div class="flex lg:flex-col overflow-x-auto lg:overflow-visible gap-2 pb-1 lg:pb-0">
                        @foreach ($this->subStepsSummary as $sNum => $sInfo)
                            @php
                                $isActive = $subStep === $sNum;
                                $isCompleted = $sInfo['isSaved'];
                            @endphp
                            <button type="button"
                                    wire:click="goToSubStep({{ $sNum }})"
                                    class="w-full min-w-[220px] lg:min-w-0 p-3 rounded-lg border text-left transition relative flex flex-col justify-between gap-1.5 min-h-[56px]
                                           {{ $isActive ? 'bg-zinc-900 border-zinc-900 text-white shadow-md ring-1 ring-zinc-900' : ($isCompleted ? 'border-emerald-300 bg-emerald-50/60 hover:bg-emerald-100/60 text-zinc-900' : 'border-zinc-200 bg-zinc-50/70 hover:bg-zinc-100/80 text-zinc-700') }}">
                                <div class="flex items-center justify-between gap-2 w-full">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-xs font-bold font-mono px-1.5 py-0.5 rounded {{ $isActive ? 'bg-zinc-800 text-white' : ($isCompleted ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700') }}">
                                            {{ $sNum }}
                                        </span>
                                        <span class="text-xs font-bold truncate {{ $isActive ? 'text-white' : 'text-zinc-900' }}">
                                            {{ $sInfo['title'] }}
                                        </span>
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if ($isCompleted)
                                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full {{ $isActive ? 'bg-emerald-400 text-zinc-900' : 'bg-emerald-600 text-white' }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                        @elseif ($isActive)
                                            <span class="w-2.5 h-2.5 rounded-full bg-white ring-2 ring-zinc-900"></span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-zinc-300"></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-[11px] font-mono truncate w-full {{ $isActive ? 'text-zinc-300' : ($isCompleted ? 'text-emerald-800 font-medium' : 'text-zinc-400') }}">
                                    {{ $sInfo['summary'] }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </aside>

            {{-- Right Active Form & Bottom Navigation Panel --}}
            <main class="flex-1 min-w-0 space-y-6">
                {{-- Single Focused Subcomponent --}}
                <div class="space-y-6">
                    @if ($subStep === 1)
                        <div>
                            @livewire(\App\Livewire\Inspection\InspectionForm\StepFirst::class, [
                                'detail_inspection_report_document_number' => $document_number,
                            ], key('first-piece-' . $period . '-' . $reloadToken))
                        </div>
                    @elseif ($subStep === 2)
                        <div>
                            @livewire(\App\Livewire\Inspection\InspectionForm\StepDimension::class, [
                                'inspection_report_document_number' => $inspection_report_document_number,
                            ], key('dimension-' . $period . '-' . $reloadToken))
                        </div>
                    @elseif ($subStep === 3)
                        <div>
                            @livewire(\App\Livewire\Inspection\InspectionForm\StepSecond::class, [
                                'detail_inspection_report_document_number' => $document_number,
                            ], key('second-insp-' . $period . '-' . $reloadToken))
                        </div>
                    @elseif ($subStep === 4)
                        <div>
                            @livewire(\App\Livewire\Inspection\InspectionForm\StepJudgement::class, [
                                'detail_inspection_report_document_number' => $document_number,
                            ], key('judgement-' . $period . '-' . $reloadToken))
                        </div>
                    @elseif ($subStep === 5)
                        <div>
                            @livewire(\App\Livewire\Inspection\InspectionForm\StepPeriodProblem::class, [
                                'inspection_report_document_number' => $inspection_report_document_number,
                                'period' => $period,
                                'start_time' => $start_time,
                                'end_time' => $end_time,
                            ], key('period-problem-' . $period . '-' . $reloadToken))
                        </div>
                    @endif
                </div>

                {{-- Sub-Wizard Floating Bottom Navigation Dock --}}
                <div class="fixed bottom-0 left-0 right-0 lg:left-64 z-30 bg-white/95 backdrop-blur-md border-t border-zinc-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] py-3 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                        <div>
                            @if ($subStep === 1)
                                <button type="button"
                                        wire:click="prevSubStep"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-zinc-300 text-xs sm:text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-xs transition">
                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    <span>Kembali ke Hub</span>
                                </button>
                            @else
                                @php
                                    $subStepLabels = [
                                        2 => '1. First Piece',
                                        3 => '2. Dimensi',
                                        4 => '3. Second & Sampling',
                                        5 => '4. Judgement & Output',
                                    ];
                                @endphp
                                <button type="button"
                                        wire:click="prevSubStep"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-zinc-300 text-xs sm:text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-xs transition">
                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    <span>Kembali ke {{ $subStepLabels[$subStep] ?? 'Sebelumnya' }}</span>
                                </button>
                            @endif
                        </div>

                        {{-- Middle: Progress Context (Visible on tablet & desktop) --}}
                        <div class="hidden md:flex items-center gap-2 text-xs font-mono text-zinc-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200 font-bold text-zinc-800">
                                Periode {{ $period }}
                            </span>
                            <span>•</span>
                            <span class="text-zinc-600">
                                Tahap <strong class="text-zinc-900 font-bold">{{ $subStep }}/5</strong>: {{ $this->subStepsSummary[$subStep]['title'] ?? '' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($subStep < 5)
                                @php
                                    $nextStepLabels = [
                                        1 => 'Lanjut ke 2. Dimensi',
                                        2 => 'Lanjut ke 3. Second & Sampling',
                                        3 => 'Lanjut ke 4. Judgement & Output',
                                        4 => 'Lanjut ke 5. Masalah & Loss Time',
                                    ];
                                @endphp
                                <button type="button"
                                        wire:click="nextSubStep"
                                        class="inline-flex items-center gap-2 px-5 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                                    <span>{{ $nextStepLabels[$subStep] ?? 'Lanjut' }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>
                            @else
                                <button type="button"
                                        wire:click="finishPeriod"
                                        class="inline-flex items-center gap-2 px-5 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-bold rounded-lg text-white bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Simpan Periode {{ $period }} & Selesai</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    @endif
</div>
