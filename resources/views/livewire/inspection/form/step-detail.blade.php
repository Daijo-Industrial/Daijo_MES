<div class="space-y-6">
    {{-- Period Selector Tabs --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold text-zinc-900">Pilih Periode Inspeksi</h2>
                <p class="text-xs text-zinc-500">Shift dibagi menjadi 4 periode inspeksi (interval 2 jam per periode).</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($period > 1)
                    <button type="button"
                            wire:click="copyFromPreviousPeriod"
                            wire:confirm="Salin format pengukuran (dimensi, SNP, dan parameter) dari Periode {{ $period - 1 }} ke Periode {{ $period }}?"
                            class="px-3 py-1.5 border border-zinc-300 text-xs font-semibold text-zinc-800 bg-white hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        <span>Salin dari Periode {{ $period - 1 }}</span>
                    </button>
                @endif
                <button type="button"
                        wire:click="resetStep(true)"
                        wire:confirm="Yakin ingin mereset seluruh data pada Periode {{ $period }}?"
                        class="px-3 py-1.5 border border-rose-200 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition">
                    Reset Periode {{ $period }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach (range(1, 4) as $p)
                @php
                    $isCur = $period === $p;
                    $pk = 'p' . $p;
                    $bag = session('stepDetailSaved', []);
                    $hasDetail = !empty(data_get($bag, "details.$pk"));
                    $hasFirst = !empty(data_get($bag, "first_inspections.$pk"));
                    $hasSecond = !empty(data_get($bag, "second_inspections.$pk"));
                    $hasJudgement = !empty(data_get($bag, "judgements.$pk"));
                    $isComplete = $hasDetail && $hasFirst && $hasSecond && $hasJudgement;
                    $isPartial = !$isComplete && ($hasDetail || $hasFirst || $hasSecond || $hasJudgement);
                @endphp
                <button type="button"
                        wire:click="selectPeriod({{ $p }})"
                        class="p-3.5 rounded-lg border text-left transition relative overflow-hidden flex flex-col justify-between
                               {{ $isCur ? 'bg-zinc-900 border-zinc-900 text-white shadow-sm ring-1 ring-zinc-900' : ($isComplete ? 'border-emerald-300 bg-emerald-50/70 hover:bg-emerald-100/50 text-zinc-900' : 'border-zinc-200 bg-zinc-50 hover:bg-zinc-100/80 text-zinc-800') }}">
                    <div class="flex items-center justify-between gap-1 w-full mb-1">
                        <span class="block text-sm font-bold {{ $isCur ? 'text-white' : ($isComplete ? 'text-emerald-950' : 'text-zinc-900') }}">
                            Periode {{ $p }}
                        </span>
                        @if ($isComplete)
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded {{ $isCur ? 'bg-zinc-800 text-emerald-400' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                Lengkap
                            </span>
                        @elseif ($isPartial)
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded {{ $isCur ? 'bg-zinc-800 text-amber-400' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                Sebagian
                            </span>
                        @else
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded {{ $isCur ? 'bg-zinc-800 text-zinc-400' : 'bg-zinc-100 text-zinc-500 border border-zinc-200' }}">
                                Kosong
                            </span>
                        @endif
                    </div>
                    <span class="block text-[11px] font-mono {{ $isCur ? 'text-zinc-300' : 'text-zinc-500' }}">
                        @if ($p === 1) 07:30 - 09:30
                        @elseif ($p === 2) 09:30 - 11:30
                        @elseif ($p === 3) 11:30 - 13:30
                        @else 13:30 - 15:30
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Period Time Range Setting --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">Jam Mulai</label>
                <input type="time"
                       wire:model.live="start_time"
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 font-mono min-h-[42px]">
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-700 mb-1">Jam Selesai</label>
                <input type="time"
                       wire:model.live="end_time"
                       class="w-full rounded-lg border-zinc-300 text-sm focus:border-zinc-900 focus:ring-zinc-900 font-mono min-h-[42px]">
            </div>
            <div class="text-xs text-zinc-600">
                <span class="block text-zinc-400 uppercase text-[10px] font-semibold tracking-wider">Part Sedang Berjalan</span>
                <span class="font-bold text-zinc-900 truncate block mt-0.5">{{ $part_number }} ({{ $part_name }})</span>
            </div>
            <div class="text-xs text-zinc-600">
                <span class="block text-zinc-400 uppercase text-[10px] font-semibold tracking-wider">Shift & Inspector</span>
                <span class="font-bold text-zinc-900 block mt-0.5">Shift {{ $shift ?? '-' }} &bull; {{ $inspector }}</span>
            </div>
        </div>
    </div>

    {{-- Subcomponents for this Period --}}
    <div class="space-y-6">
        {{-- 1. First Piece Inspection --}}
        <div>
            @livewire(\App\Livewire\Inspection\InspectionForm\StepFirst::class, [
                'detail_inspection_report_document_number' => $document_number,
            ], key('first-piece-' . $period . '-' . $reloadToken))
        </div>

        {{-- 2. Dimensions Data --}}
        <div>
            @livewire(\App\Livewire\Inspection\InspectionForm\StepDimension::class, [
                'inspection_report_document_number' => $inspection_report_document_number,
            ], key('dimension-' . $period . '-' . $reloadToken))
        </div>

        {{-- 3. Second Inspection, Sampling & Packaging --}}
        <div>
            @livewire(\App\Livewire\Inspection\InspectionForm\StepSecond::class, [
                'detail_inspection_report_document_number' => $document_number,
            ], key('second-insp-' . $period . '-' . $reloadToken))
        </div>

        {{-- 4. Judgement & Verification --}}
        <div>
            @livewire(\App\Livewire\Inspection\InspectionForm\StepJudgement::class, [
                'detail_inspection_report_document_number' => $document_number,
            ], key('judgement-' . $period . '-' . $reloadToken))
        </div>
    </div>

    {{-- Unified Period Action & Step Navigation Footer Bar --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
        <div>
            <button type="button"
                    wire:click="$dispatch('prevStep')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Step 1 (Header)</span>
            </button>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="saveCurrentPeriod"
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 shadow-sm text-sm font-bold rounded-lg text-zinc-900 bg-zinc-100 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <span>Simpan Periode {{ $period }}</span>
            </button>

            <button type="button"
                    wire:click="proceedToStep3"
                    class="inline-flex items-center gap-2 px-6 py-2.5 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                <span>Lanjut ke Step 3 (Masalah & Loss Time)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</div>