<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    {{-- Breadcrumb & Heading --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('inspection-reports.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar Laporan
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Form Input Molding IPQC
            </h1>
        </div>

        @if ($inspection_report_document_number)
            <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg">
                <span class="text-xs text-indigo-600 font-medium">Doc No:</span>
                <span class="text-xs font-mono font-bold text-indigo-900">{{ $inspection_report_document_number }}</span>
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
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach ($stepLabels as $i => $s)
                @php
                    $isCurrent = $currentStep === $i;
                    $isFilled = $filledSteps[$i] ?? false;
                @endphp
                <button type="button"
                        wire:click="setStep({{ $i }})"
                        class="flex items-center gap-3 p-3 rounded-lg border text-left transition relative overflow-hidden
                               {{ $isCurrent ? 'bg-indigo-50/70 border-indigo-500 shadow-sm ring-1 ring-indigo-500' : ($isFilled ? 'bg-green-50/50 border-green-300 hover:bg-green-50' : 'bg-gray-50 border-gray-200 hover:bg-gray-100/70 opacity-80') }}">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                                {{ $isCurrent ? 'bg-indigo-600 text-white' : ($isFilled ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600') }}">
                        @if ($isFilled && !$isCurrent)
                            ✓
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-semibold {{ $isCurrent ? 'text-indigo-900' : ($isFilled ? 'text-green-900' : 'text-gray-700') }}">
                            {{ $s['title'] }}
                        </span>
                        <span class="block text-[11px] text-gray-500 truncate">
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
                 class="pointer-events-auto flex items-center justify-between p-3.5 rounded-lg shadow-lg text-sm font-medium text-white"
                 :class="{
                    'bg-green-600': t.type === 'success',
                    'bg-red-600': t.type === 'error',
                    'bg-amber-600': t.type === 'warning',
                    'bg-indigo-600': t.type === 'info'
                 }">
                <div class="flex items-center gap-2">
                    <span x-text="t.message"></span>
                </div>
                <button type="button" @click="remove(t.id)" class="text-white/80 hover:text-white ml-3">
                    ✕
                </button>
            </div>
        </template>
    </div>
</div>
