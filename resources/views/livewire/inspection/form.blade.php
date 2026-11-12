<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    {{-- Breadcrumb & Heading --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('inspection-reports.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-zinc-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar Laporan
            </a>
            <h1 class="text-2xl font-bold text-zinc-900 mt-2 flex items-center gap-2.5">
                <span class="p-2 rounded-lg bg-zinc-900 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                Form Input Plastic Injection IPQC
            </h1>
        </div>

        @if ($inspection_report_document_number)
            <div class="flex items-center gap-2 bg-zinc-100 border border-zinc-300 px-3.5 py-1.5 rounded-lg">
                <span class="text-xs text-zinc-500 font-medium uppercase tracking-wider">No. Dokumen:</span>
                <span class="text-xs font-mono font-bold text-zinc-900">{{ $inspection_report_document_number }}</span>
            </div>
        @endif
    </div>

    @php
        $stepLabels = [
            1 => ['title' => 'Header & Part Info', 'desc' => 'Customer, Part, Mesin & Shift'],
            2 => ['title' => 'Detail Periode (1-4)', 'desc' => 'First, Dimensi, Sampling & Hasil'],
            3 => ['title' => 'Masalah & Loss Time', 'desc' => 'Daftar kendala selama shift'],
            4 => ['title' => 'Review & Submit', 'desc' => 'Verifikasi dan simpan laporan'],
        ];
    @endphp

    {{-- Stepper Progress Bar --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach ($stepLabels as $i => $s)
                @php
                    $isCurrent = $currentStep === $i;
                    $isFilled = $filledSteps[$i] ?? false;
                @endphp
                <button type="button"
                        wire:click="setStep({{ $i }})"
                        class="flex items-center gap-3 p-3 rounded-lg border text-left transition relative overflow-hidden
                               {{ $isCurrent ? 'bg-zinc-900 border-zinc-900 text-white shadow-sm ring-1 ring-zinc-900' : ($isFilled ? 'bg-emerald-50/70 border-emerald-300 hover:bg-emerald-100/50 text-emerald-950' : 'bg-zinc-50 border-zinc-200 hover:bg-zinc-100/80 text-zinc-800') }}">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs
                                {{ $isCurrent ? 'bg-zinc-800 text-white ring-1 ring-zinc-700' : ($isFilled ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-700') }}">
                        @if ($isFilled && !$isCurrent)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold {{ $isCurrent ? 'text-white' : ($isFilled ? 'text-emerald-950' : 'text-zinc-900') }}">
                            {{ $s['title'] }}
                        </span>
                        <span class="block text-[11px] {{ $isCurrent ? 'text-zinc-300' : 'text-zinc-500' }} truncate">
                            {{ $s['desc'] }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Active Step Component Container --}}
    <div class="mt-4">
        @if ($currentStep === 1)
            @livewire(\App\Livewire\Inspection\InspectionForm\StepHeader::class, key('step-header'))
        @elseif ($currentStep === 2)
            @livewire(\App\Livewire\Inspection\InspectionForm\StepDetail::class, ['inspection_report_document_number' => $inspection_report_document_number], key('step-detail'))
        @elseif ($currentStep === 3)
            @livewire(\App\Livewire\Inspection\InspectionForm\StepProblem::class, ['inspection_report_document_number' => $inspection_report_document_number], key('step-problem'))
        @elseif ($currentStep === 4)
            @livewire(\App\Livewire\Inspection\InspectionForm\FinalSubmit::class, key('final-submit'))
        @endif
    </div>

    {{-- Alpine Toast Notification Stack --}}
    <div x-data="{
            toasts: [],
            add(t) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: t.message, type: t.type || 'success' });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
         }"
         x-on:toast.window="add($event.detail)"
         class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-3"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="pointer-events-auto flex items-center justify-between p-3.5 rounded-lg shadow-lg text-sm font-medium text-white border"
                 :class="{
                    'bg-emerald-700 border-emerald-800': t.type === 'success',
                    'bg-rose-700 border-rose-800': t.type === 'error',
                    'bg-amber-600 border-amber-700': t.type === 'warning',
                    'bg-zinc-800 border-zinc-900': t.type === 'info'
                 }">
                <div class="flex items-center gap-2">
                    <span x-text="t.message"></span>
                </div>
                <button type="button" @click="remove(t.id)" class="text-white/80 hover:text-white ml-3 text-xs font-bold">
                    ✕
                </button>
            </div>
        </template>
    </div>
</div>