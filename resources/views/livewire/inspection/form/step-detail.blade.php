<div class="space-y-6">
    {{-- Period Selector Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">Pilih Periode Inspeksi</h2>
                <p class="text-xs text-gray-500">Inspeksi IPQC dibagi menjadi 4 periode per shift (masing-masing 2 jam).</p>
            </div>
            <button type="button"
                    wire:click="resetStep(true)"
                    wire:confirm="Yakin ingin mereset seluruh data pada Periode {{ $period }}?"
                    class="px-3 py-1.5 border border-red-200 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition">
                Reset Periode {{ $period }}
            </button>
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
                @endphp
                <button type="button"
                        wire:click="selectPeriod({{ $p }})"
                        class="p-3 rounded-lg border text-left transition flex items-center justify-between
                               {{ $isCur ? 'border-indigo-600 bg-indigo-50/80 ring-1 ring-indigo-600' : ($isComplete ? 'border-green-300 bg-green-50/50 hover:bg-green-100/50' : 'border-gray-200 bg-gray-50 hover:bg-gray-100') }}">
                    <div>
                        <span class="block text-sm font-bold {{ $isCur ? 'text-indigo-900' : ($isComplete ? 'text-green-900' : 'text-gray-800') }}">
                            Periode {{ $p }}
                        </span>
                        <span class="block text-[11px] text-gray-500 font-mono">
                            @if ($p === 1) 07:30 - 09:30
                            @elseif ($p === 2) 09:30 - 11:30
                            @elseif ($p === 3) 11:30 - 13:30
                            @else 13:30 - 15:30
                            @endif
                        </span>
                    </div>
                    @if ($isComplete)
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500" title="Data periode lengkap"></span>
                    @elseif ($hasDetail || $hasFirst || $hasSecond || $hasJudgement)
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500" title="Sebagian data terisi"></span>
                    @else
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-300" title="Belum ada data"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Period Time Range Setting --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Jam Mulai</label>
                <input type="time"
                       wire:model.live="start_time"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono min-h-[42px]">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Jam Selesai</label>
                <input type="time"
                       wire:model.live="end_time"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono min-h-[42px]">
            </div>
            <div class="text-xs text-gray-600">
                <span class="block text-gray-400 uppercase text-[10px] font-semibold">Part</span>
                <span class="font-bold text-gray-900 truncate block">{{ $part_number }} ({{ $part_name }})</span>
            </div>
            <div class="text-xs text-gray-600">
                <span class="block text-gray-400 uppercase text-[10px] font-semibold">Shift & Inspector</span>
                <span class="font-bold text-gray-900 block">Shift {{ $shift ?? '-' }} &bull; {{ $inspector }}</span>
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
</div>
