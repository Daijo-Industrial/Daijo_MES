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
                        {{ $this->getPeriodTimeRange($p) }}
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

    @if ($savedSummary['isLocked'])
        {{-- READ-ONLY FORMATTED PERIOD VIEW (Saved in Session) --}}
        <div class="space-y-6">
            {{-- Saved Status & Unlock Action Banner --}}
            <div class="bg-emerald-50 border border-emerald-300 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-3 w-3 relative">
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-emerald-950">Periode {{ $period }} Telah Tersimpan di Sesi</span>
                            <span class="text-xs font-mono font-semibold text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded border border-emerald-300">
                                {{ $start_time }} - {{ $end_time }}
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800/80 mt-0.5">
                            Shift {{ $shift ?? '-' }} &bull; Inspector: <strong class="text-emerald-950">{{ $inspector }}</strong> &bull; Part: <strong class="text-emerald-950">{{ $part_number }}</strong>
                        </p>
                    </div>
                </div>

                <button type="button"
                        wire:click="unlockPeriod({{ $period }})"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-emerald-300 hover:bg-emerald-100 text-xs font-bold text-emerald-950 rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Buka Kunci / Edit Periode {{ $period }}</span>
                </button>
            </div>

            {{-- 1. First Piece Inspection Summary --}}
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2.5">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">1. First Piece Inspection</h3>
                    <span class="text-[11px] font-mono text-zinc-400">Periode {{ $period }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Appearance</span>
                        @php $app = $savedSummary['first']['appearance'] ?? null; @endphp
                        @if ($app === 'OK')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 mt-1">OK</span>
                        @elseif ($app === 'NG')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 mt-1">NG</span>
                        @else
                            <span class="text-zinc-400 block mt-1">-</span>
                        @endif
                    </div>
                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Weight</span>
                        <span class="font-mono font-bold text-zinc-900 block mt-1">
                            {{ $savedSummary['first']['weight'] ?? '-' }} {{ $savedSummary['first']['weight_uom'] ?? 'g' }}
                        </span>
                    </div>
                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Fitting Test</span>
                        <span class="text-zinc-800 font-medium block mt-1">
                            {{ $savedSummary['first']['fitting_test'] ?: '-' }}
                        </span>
                    </div>
                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Remarks</span>
                        <span class="text-zinc-800 font-medium block mt-1">
                            {{ $savedSummary['first']['remarks'] ?: '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 2. Dimension Measurements Summary --}}
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2.5">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">2. Dimension Measurements</h3>
                    <span class="text-[11px] font-mono text-zinc-500">{{ count($savedSummary['dimensions']) }} Titik Terukur</span>
                </div>
                @if (!empty($savedSummary['dimensions']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-xs">
                            <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                                <tr>
                                    <th class="px-2 py-2">No</th>
                                    <th class="px-3 py-2 text-left">Area / Titik</th>
                                    <th class="px-2 py-2">Batas Bawah</th>
                                    <th class="px-2 py-2">Batas Atas</th>
                                    <th class="px-2 py-2">Satuan</th>
                                    <th class="px-3 py-2">Nilai Aktual</th>
                                    <th class="px-2 py-2">Judgement</th>
                                    <th class="px-3 py-2 text-left">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($savedSummary['dimensions'] as $idx => $dim)
                                    <tr class="hover:bg-zinc-50/50">
                                        <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-medium text-zinc-900">{{ $dim['area'] ?: '-' }}</td>
                                        <td class="px-2 py-2 text-center font-mono">{{ $dim['lower_limit'] ?? '-' }}</td>
                                        <td class="px-2 py-2 text-center font-mono">{{ $dim['upper_limit'] ?? '-' }}</td>
                                        <td class="px-2 py-2 text-center font-semibold">{{ $dim['limit_uom'] ?? 'mm' }}</td>
                                        <td class="px-3 py-2 text-center font-mono font-bold text-zinc-900">{{ $dim['actual_value'] ?? '-' }}</td>
                                        <td class="px-2 py-2 text-center">
                                            @if (($dim['judgement'] ?? '') === 'OK')
                                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">OK</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">NG</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-zinc-600">{{ $dim['remarks'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-zinc-500 italic py-2">Tidak ada data pengukuran dimensi tercatat untuk periode ini.</p>
                @endif
            </div>

            {{-- 3. Second Inspection (Lot Size, Sampling & Packaging) Summary --}}
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2.5">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">3. Second Inspection (Lot Size, Sampling & Packaging)</h3>
                    @if (!empty($savedSummary['second']['skipLotSize']))
                        <span class="text-xs font-semibold px-2 py-0.5 bg-zinc-100 text-zinc-600 rounded border border-zinc-200">
                            Lot Size: Dilewati (Tanpa Lot Size)
                        </span>
                    @else
                        <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-50 text-emerald-800 rounded border border-emerald-200 font-mono">
                            Lot Size: {{ number_format((int)($savedSummary['second']['lot_size_quantity'] ?? 0)) }} pcs
                        </span>
                    @endif
                </div>

                {{-- Sampling Check Summary --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-semibold text-zinc-700 uppercase tracking-wider">Sampling Check</h4>
                    @if (!empty($savedSummary['samples']))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-xs border border-zinc-200 rounded-lg">
                                <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                                    <tr>
                                        <th class="px-2 py-2">No</th>
                                        <th class="px-3 py-2 text-left">Label Box</th>
                                        <th class="px-3 py-2">Jumlah Sample</th>
                                        <th class="px-2 py-2">Appearance</th>
                                        <th class="px-2 py-2">NG Qty</th>
                                        <th class="px-3 py-2 text-left">Remarks NG</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach ($savedSummary['samples'] as $idx => $s)
                                        <tr class="hover:bg-zinc-50/50">
                                            <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 font-medium text-zinc-900">{{ $s['box_label'] ?: '-' }}</td>
                                            <td class="px-3 py-2 text-center font-mono font-semibold">{{ number_format((int)($s['quantity'] ?? 0)) }} pcs</td>
                                            <td class="px-2 py-2 text-center">
                                                @if (($s['appearance'] ?? '') === 'OK')
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">OK</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">NG</span>
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 text-center font-mono font-bold {{ (int)($s['ng_quantity'] ?? 0) > 0 ? 'text-rose-600' : 'text-zinc-400' }}">
                                                {{ (int)($s['ng_quantity'] ?? 0) > 0 ? number_format((int)$s['ng_quantity']) : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-zinc-600">{{ $s['remarks'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 italic">Tidak ada sampel box tercatat.</p>
                    @endif
                </div>

                {{-- Packaging Check Summary --}}
                <div class="space-y-2 pt-2 border-t border-zinc-100">
                    <h4 class="text-xs font-semibold text-zinc-700 uppercase tracking-wider">Packaging & SNP Verification</h4>
                    @if (!empty($savedSummary['packagings']))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-xs border border-zinc-200 rounded-lg">
                                <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                                    <tr>
                                        <th class="px-2 py-2">No</th>
                                        <th class="px-3 py-2 text-left">Label Box / No Box</th>
                                        <th class="px-3 py-2 text-center">SNP (Standard Packaging Qty)</th>
                                        <th class="px-2 py-2 text-center">Judgement</th>
                                        <th class="px-3 py-2 text-left">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach ($savedSummary['packagings'] as $idx => $pkg)
                                        <tr class="hover:bg-zinc-50/50">
                                            <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-3 py-2 font-medium text-zinc-900">{{ $pkg['box_label'] ?: '-' }}</td>
                                            <td class="px-3 py-2 text-center font-mono font-semibold">{{ number_format((int)($pkg['snp'] ?? 0)) }} pcs/box</td>
                                            <td class="px-2 py-2 text-center">
                                                @if (($pkg['judgement'] ?? '') === 'OK')
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">OK</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">NG</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-zinc-600">{{ $pkg['remarks'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 italic">Tidak ada packaging tercatat.</p>
                    @endif
                </div>
            </div>

            {{-- 4. Judgement & Output Summary --}}
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2.5">
                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">4. Judgement & Output Periode</h3>
                    <span class="text-[11px] font-mono text-zinc-400">Total Periode {{ $period }}</span>
                </div>
                @php
                    $pass = (int)($savedSummary['judgement']['pass_quantity'] ?? 0);
                    $rej = (int)($savedSummary['judgement']['reject_quantity'] ?? 0);
                    $tot = $pass + $rej;
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl">
                        <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Pass Quantity (OK)</span>
                        <span class="text-2xl font-mono font-bold text-emerald-900 block mt-1">{{ number_format($pass) }} pcs</span>
                    </div>
                    <div class="bg-rose-50 border border-rose-200 p-4 rounded-xl">
                        <span class="text-xs font-semibold text-rose-800 uppercase tracking-wider">Reject Quantity (NG)</span>
                        <span class="text-2xl font-mono font-bold text-rose-900 block mt-1">{{ number_format($rej) }} pcs</span>
                    </div>
                    <div class="bg-zinc-50 border border-zinc-300 p-4 rounded-xl">
                        <span class="text-xs font-semibold text-zinc-700 uppercase tracking-wider">Total Output Periode</span>
                        <span class="text-2xl font-mono font-bold text-zinc-900 block mt-1">{{ number_format($tot) }} pcs</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Subcomponents for this Period (Editable Active Mode) --}}
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
    @endif

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
            @if ($savedSummary['isLocked'])
                <button type="button"
                        wire:click="unlockPeriod({{ $period }})"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 shadow-sm text-sm font-bold rounded-lg text-zinc-900 bg-white hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                    <svg class="w-4 h-4 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Buka Kunci / Edit Periode {{ $period }}</span>
                </button>
            @else
                @if ($savedSummary['isSaved'])
                    <button type="button"
                            wire:click="lockPeriod({{ $period }})"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                        <span>Kunci / Tampilkan Ringkasan</span>
                    </button>
                @endif
                <button type="button"
                        wire:click="saveCurrentPeriod"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-zinc-300 shadow-sm text-sm font-bold rounded-lg text-zinc-900 bg-zinc-100 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition">
                    <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span>Simpan Periode {{ $period }}</span>
                </button>
            @endif

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
