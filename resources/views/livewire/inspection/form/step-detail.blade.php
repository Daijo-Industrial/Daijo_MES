<div class="space-y-6">
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

        {{-- Hub Bottom Navigation Bar --}}
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <button type="button"
                        wire:click="$dispatch('prevStep')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
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
                            class="inline-flex items-center gap-2 px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                        <span>Lanjut ke Step 3 (Masalah & Loss Time)</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                @else
                    <button type="button"
                            disabled
                            title="Selesaikan dan simpan minimal 1 periode sebelum melanjutkan ke Step 3"
                            class="inline-flex items-center gap-2 px-6 py-2.5 border border-zinc-300 shadow-sm text-sm font-bold rounded-lg text-zinc-400 bg-zinc-100 cursor-not-allowed">
                        <span>Lanjut ke Step 3 (Masalah & Loss Time)</span>
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

    @else
        {{-- LEVEL 2: FOCUSED SUB-WIZARD (Inspecting 1 Subcomponent at a time for Selected Period) --}}

        {{-- Wizard Top Bar --}}
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Step 2 &bull; Detail Periode</span>
                        <span class="text-zinc-300">/</span>
                        <span class="text-sm font-bold text-zinc-900">Inspeksi Periode {{ $period }}</span>
                        <span class="text-xs font-mono font-bold text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                            {{ $start_time }} - {{ $end_time }} WIB
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">
                        Shift {{ $shift ?? '-' }} &bull; Inspector: <strong class="text-zinc-800">{{ $inspector }}</strong> &bull; Part: <strong class="text-zinc-800 font-mono">{{ $part_number }}</strong> &bull; Mesin: <strong class="text-zinc-800">{{ $machine_number }}</strong>
                    </p>
                </div>

                <button type="button"
                        wire:click="exitToHub"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-zinc-300 text-xs font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 shadow-sm transition">
                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <span>Simpan Draft & Kembali ke Hub</span>
                </button>
            </div>

            {{-- Minimized Time Range Toolbar & Edit Option --}}
            <div x-data="{ editTime: @json($errors->has('start_time') || $errors->has('end_time')) }"
                 class="border-t border-zinc-100 pt-2.5">
                <div x-show="!editTime" class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 text-zinc-700 bg-zinc-50 px-2.5 py-1 rounded-md border border-zinc-200">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-zinc-500 font-medium">Jam Periode:</span>
                            <span class="font-bold text-zinc-900 font-mono">{{ $start_time ?: '-' }} - {{ $end_time ?: '-' }} WIB</span>
                        </div>
                        <button type="button"
                                @click="editTime = true"
                                class="inline-flex items-center px-2 py-1 text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-50 rounded-md shadow-sm transition">
                            Ubah Jam
                        </button>
                    </div>

                    @if ($period > 1)
                        <button type="button"
                                wire:click="copyFromPreviousPeriod"
                                wire:confirm="Salin format pengukuran dari Periode {{ $period - 1 }} ke Periode {{ $period }}?"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 border border-zinc-300 text-xs font-semibold text-zinc-700 bg-white hover:bg-zinc-50 rounded-md shadow-sm transition">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                            </svg>
                            <span>Salin dari Periode {{ $period - 1 }}</span>
                        </button>
                    @endif
                </div>

                {{-- Expanded Time Inputs --}}
                <div x-show="editTime" x-cloak class="p-3 bg-zinc-50 rounded-lg border border-zinc-200 space-y-3 mt-2">
                    <div class="flex items-center justify-between pb-2 border-b border-zinc-200">
                        <span class="text-xs font-bold text-zinc-800">Ubah Jam Inspeksi Periode {{ $period }}</span>
                        <button type="button"
                                @click="editTime = false"
                                class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 underline">
                            Selesai
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                Jam Mulai <span class="text-rose-600">*</span>
                            </label>
                            <input type="time"
                                   wire:model.live="start_time"
                                   class="w-full rounded-lg border-zinc-300 text-xs focus:border-zinc-900 focus:ring-zinc-900 font-mono min-h-[38px] @error('start_time') border-rose-300 bg-rose-50/50 @enderror">
                            @error('start_time')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 mb-1">
                                Jam Selesai <span class="text-rose-600">*</span>
                            </label>
                            <input type="time"
                                   wire:model.live="end_time"
                                   class="w-full rounded-lg border-zinc-300 text-xs focus:border-zinc-900 focus:ring-zinc-900 font-mono min-h-[38px] @error('end_time') border-rose-300 bg-rose-50/50 @enderror">
                            @error('end_time')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- LEVEL 2 MAIN BODY: 2-COLUMN LAYOUT (Sticky Left Rail + Right Form Panel) --}}
        <div class="lg:flex lg:items-start lg:gap-6">
            {{-- Sticky Left Vertical Rail --}}
            <aside class="lg:w-72 flex-shrink-0 lg:sticky lg:top-4 mb-6 lg:mb-0">
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3 space-y-2">
                    <div class="px-2 py-1 border-b border-zinc-100 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Tahapan Periode {{ $period }}</span>
                        <span class="text-[11px] font-mono text-zinc-500 font-semibold">{{ $subStep }}/4</span>
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
                    @endif
                </div>

                {{-- Sub-Wizard Bottom Navigation Bar --}}
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        @if ($subStep === 1)
                            <button type="button"
                                    wire:click="prevSubStep"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Kembali ke Hub Periode</span>
                            </button>
                        @else
                            @php
                                $subStepLabels = [
                                    2 => '1. First Piece',
                                    3 => '2. Dimensi',
                                    4 => '3. Second & Sampling',
                                ];
                            @endphp
                            <button type="button"
                                    wire:click="prevSubStep"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Kembali ke {{ $subStepLabels[$subStep] ?? 'Sebelumnya' }}</span>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($subStep < 4)
                            @php
                                $nextStepLabels = [
                                    1 => 'Lanjut ke 2. Dimensi',
                                    2 => 'Lanjut ke 3. Second & Sampling',
                                    3 => 'Lanjut ke 4. Judgement & Output',
                                ];
                            @endphp
                            <button type="button"
                                    wire:click="nextSubStep"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                                <span>{{ $nextStepLabels[$subStep] ?? 'Lanjut' }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        @else
                            <button type="button"
                                    wire:click="finishPeriod"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Simpan Periode {{ $period }} & Selesai</span>
                            </button>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    @endif
</div>
