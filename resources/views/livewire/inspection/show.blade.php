@php
    /** @var \App\Models\Inspection\InspectionReport $r */
    $r = $report ?? $inspectionReport;
    $periods = $r->detailInspectionReports->pluck('period')->sort()->values();
    $defaultPeriod = $periods->first() ?? 1;
@endphp

<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6"
     x-data="{
        activePeriod: {{ $defaultPeriod }},
        sections: {
            @foreach (range(1, 4) as $p)
                p{{ $p }}_first: true,
                p{{ $p }}_dimension: true,
                p{{ $p }}_second: true,
                p{{ $p }}_judgement: true,
            @endforeach
        },
        openAll(p) {
            ['first', 'dimension', 'second', 'judgement'].forEach(k => {
                this.sections['p' + p + '_' + k] = true;
            });
        },
        closeAll(p) {
            ['first', 'dimension', 'second', 'judgement'].forEach(k => {
                this.sections['p' + p + '_' + k] = false;
            });
        },
        openAllGlobal() {
            Object.keys(this.sections).forEach(k => this.sections[k] = true);
        },
        closeAllGlobal() {
            Object.keys(this.sections).forEach(k => this.sections[k] = false);
        }
     }">

    {{-- Breadcrumb & Actions Bar (Hidden on print) --}}
    <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
        <div>
            @php $qs = request()->getQueryString(); @endphp
            <a href="{{ route('inspection-reports.index') }}@if ($qs)?{{ $qs }}@endif"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 transition"
               onclick="if (history.length > 1) { history.back(); return false; }">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar Laporan
            </a>
            <div class="mt-2 flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Plastic Injection IPQC Inspection Report
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 tracking-wide font-mono">
                    {{ $r->document_number }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="openAllGlobal()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-3.5 h-3.5 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                Buka Semua
            </button>
            <button type="button"
                    @click="closeAllGlobal()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-3.5 h-3.5 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
                Tutup Semua
            </button>
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center px-3 py-2 border border-indigo-600 shadow-sm text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak / Print
            </button>
        </div>
    </div>

    {{-- Headline Metadata Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
            $cards = [
                ['label' => 'Tanggal Inspeksi', 'value' => $r->inspection_date ? \Illuminate\Support\Carbon::parse($r->inspection_date)->format('d M Y') : '-', 'icon' => 'calendar'],
                ['label' => 'Shift', 'value' => 'Shift ' . ($r->shift ?? '-'), 'icon' => 'sun'],
                ['label' => 'Customer', 'value' => $r->customer ?: 'N/A', 'icon' => 'office'],
                ['label' => 'Mesin', 'value' => $r->machine_number ?: '-', 'icon' => 'cog'],
                ['label' => 'Operator', 'value' => $r->operator ?: '-', 'icon' => 'user'],
                ['label' => 'Inspector', 'value' => $r->inspector ?: '-', 'icon' => 'badge-check'],
            ];
        @endphp

        @foreach ($cards as $c)
            <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
                <span class="block text-[11px] font-medium text-gray-400 uppercase tracking-wider mb-1">
                    {{ $c['label'] }}
                </span>
                <span class="block text-sm font-semibold text-gray-900 truncate" title="{{ $c['value'] }}">
                    {{ $c['value'] }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Part Details Card --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-indigo-50/60 border-b border-indigo-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-indigo-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Part Information
            </h2>
        </div>
        <div class="p-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-xs">
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Part Number</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900 font-mono text-sm">{{ $r->part_number ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Part Name</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $r->part_name ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Material</dt>
                    <dd class="mt-0.5 font-medium text-gray-800">{{ $r->material ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Color</dt>
                    <dd class="mt-0.5 font-medium text-gray-800">{{ $r->color ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Part Weight</dt>
                    <dd class="mt-0.5 font-medium text-gray-800 font-mono">
                        {{ $r->weight !== null ? rtrim(rtrim(number_format((float) $r->weight, 2), '0'), '.') : '-' }}
                        <span class="text-gray-500 font-sans text-xs">{{ $r->weight_uom }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-400 uppercase">Tool / Cavity</dt>
                    <dd class="mt-0.5 font-medium text-gray-800">{{ $r->tool_number_or_cav_number ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Period Navigation Pills --}}
    @php
        $filledPeriods = $r->detailInspectionReports->pluck('period')->all();
    @endphp

    <div class="space-y-4">
        <div class="flex items-center gap-2 border-b border-gray-200 pb-2 print:hidden">
            @foreach (range(1, 4) as $p)
                @php $hasData = in_array($p, $filledPeriods, true); @endphp
                <button type="button"
                        @click="activePeriod = {{ $p }}"
                        :class="activePeriod === {{ $p }} ? 'bg-indigo-600 text-white shadow-sm font-semibold' : '{{ $hasData ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' : 'bg-gray-50 text-gray-400 border border-gray-200 opacity-60' }}'"
                        class="px-4 py-2 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
                    <span>Period {{ $p }}</span>
                    @if ($hasData)
                        <span class="w-2 h-2 rounded-full"
                              :class="activePeriod === {{ $p }} ? 'bg-white' : 'bg-green-500'"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Period Panes --}}
        @foreach (range(1, 4) as $p)
            @php
                $d = $r->detailInspectionReports->firstWhere('period', $p);
                $hasData = !is_null($d);
            @endphp

            <div x-show="activePeriod === {{ $p }}"
                 class="print:block"
                 :class="{ 'print:mb-8': true }">

                @if (!$hasData)
                    <div class="rounded-lg border-2 border-dashed border-gray-200 p-8 text-center bg-gray-50/50 print:hidden">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Data Period {{ $p }}</h3>
                        <p class="mt-1 text-xs text-gray-500">Tidak ada hasil inspeksi yang diinput pada periode ini.</p>
                    </div>
                @else
                    @php
                        $start = $d->start_datetime ? \Illuminate\Support\Carbon::parse($d->start_datetime) : null;
                        $end = $d->end_datetime ? \Illuminate\Support\Carbon::parse($d->end_datetime) : null;
                        $dur = '-';
                        if ($start && $end) {
                            $hours = $start->diffInHours($end);
                            $mins = $start->diffInMinutes($end) % 60;
                            $dur = ($hours ? $hours . ' jam ' : '') . $mins . ' menit';
                        }
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
                            <span class="font-bold text-gray-900 text-sm">Period {{ $p }}</span>
                            @if ($start && $end)
                                <span class="text-gray-300">|</span>
                                <span>{{ $start->format('d M Y') }}</span>
                                <span class="inline-flex items-center gap-1 font-mono bg-white px-2 py-0.5 rounded border border-gray-200">
                                    {{ $start->format('H:i') }} &rarr; {{ $end->format('H:i') }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-medium">
                                    ⏱ {{ $dur }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 print:hidden">
                            <button type="button"
                                    @click="openAll({{ $p }})"
                                    class="px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded transition">
                                Buka Bagian P{{ $p }}
                            </button>
                            <button type="button"
                                    @click="closeAll({{ $p }})"
                                    class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-white hover:bg-gray-100 border border-gray-200 rounded transition">
                                Tutup Bagian P{{ $p }}
                            </button>
                        </div>
                    </div>

                    {{-- Accordion Sections --}}
                    <div class="space-y-3">
                        {{-- First Inspection --}}
                        <x-inspection-accordion-section id="first{{ $p }}" title="1. First Piece Inspection" sectionKey="p{{ $p }}_first">
                            @include('livewire.inspection.partials.first-inspection-table', [
                                'rows' => $d->firstInspections,
                            ])
                        </x-inspection-accordion-section>

                        {{-- Dimension Data --}}
                        <x-inspection-accordion-section id="dimension{{ $p }}" title="2. Dimension Data" sectionKey="p{{ $p }}_dimension">
                            @include('livewire.inspection.partials.dimension-table', [
                                'rows' => $r->dimensionData,
                            ])
                        </x-inspection-accordion-section>

                        {{-- Second Inspection & Sub-tables --}}
                        <x-inspection-accordion-section id="second{{ $p }}" title="3. Second Inspection & Sampling" sectionKey="p{{ $p }}_second">
                            @include('livewire.inspection.partials.second-inspection-table', [
                                'second' => $d->secondInspections,
                            ])
                        </x-inspection-accordion-section>

                        {{-- Judgement --}}
                        <x-inspection-accordion-section id="judgement{{ $p }}" title="4. Judgement & Verification" sectionKey="p{{ $p }}_judgement">
                            @include('livewire.inspection.partials.judgement-table', [
                                'judgement' => $d->judgementData,
                            ])
                        </x-inspection-accordion-section>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Shift Problems Section --}}
    @php
        $problemRows = $r->problemData ?? collect();
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-amber-50/60 border-b border-amber-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-amber-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Shift Problems & Loss Time Log
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                    Shift {{ $r->shift }}
                </span>
            </h2>
        </div>
        <div>
            @include('livewire.inspection.partials.problems', ['rows' => $problemRows])
        </div>
    </div>

    {{-- Shift Quantities & Rates --}}
    @php
        $qty = $r->quantityData;
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-emerald-50/60 border-b border-emerald-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-emerald-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Ringkasan Quantity & Rate
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                    Shift {{ $r->shift }}
                </span>
            </h2>
        </div>
        <div class="p-4 space-y-6">
            @if ($qty)
                {{-- Metric Tiles --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-center">
                    <div class="bg-indigo-50/60 rounded-lg p-3 border border-indigo-100">
                        <span class="text-[11px] font-medium text-indigo-700 uppercase block mb-1">Total Output</span>
                        <span class="text-2xl font-bold text-indigo-900 font-mono">{{ number_format($qty->output_quantity ?? 0) }}</span>
                    </div>
                    <div class="bg-green-50/60 rounded-lg p-3 border border-green-100">
                        <span class="text-[11px] font-medium text-green-700 uppercase block mb-1">Total Pass</span>
                        <span class="text-2xl font-bold text-green-900 font-mono">{{ number_format($qty->pass_quantity ?? 0) }}</span>
                    </div>
                    <div class="bg-red-50/60 rounded-lg p-3 border border-red-100">
                        <span class="text-[11px] font-medium text-red-700 uppercase block mb-1">Total Reject</span>
                        <span class="text-2xl font-bold text-red-900 font-mono">{{ number_format($qty->reject_quantity ?? 0) }}</span>
                    </div>
                    <div class="bg-blue-50/60 rounded-lg p-3 border border-blue-100">
                        <span class="text-[11px] font-medium text-blue-700 uppercase block mb-1">Total Sampling</span>
                        <span class="text-2xl font-bold text-blue-900 font-mono">{{ number_format($qty->sampling_quantity ?? 0) }}</span>
                    </div>
                    <div class="bg-amber-50/60 rounded-lg p-3 border border-amber-100">
                        <span class="text-[11px] font-medium text-amber-700 uppercase block mb-1">Total NG Sample</span>
                        <span class="text-2xl font-bold text-amber-900 font-mono">{{ number_format($qty->ng_sample_quantity ?? 0) }}</span>
                    </div>
                </div>

                {{-- Rates Progress Bars --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    @php
                        $passRate = (float) ($qty->pass_rate ?? 0);
                        $rejectRate = (float) ($qty->reject_rate ?? 0);
                        $ngSampleRate = (float) ($qty->ng_sample_rate ?? 0);
                    @endphp

                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center text-xs font-medium text-gray-700 mb-1.5">
                            <span>Pass Rate</span>
                            <span class="font-bold text-green-700 font-mono">{{ number_format($passRate, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $passRate)) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center text-xs font-medium text-gray-700 mb-1.5">
                            <span>Reject Rate</span>
                            <span class="font-bold text-red-700 font-mono">{{ number_format($rejectRate, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-red-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $rejectRate)) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center text-xs font-medium text-gray-700 mb-1.5">
                            <span>NG Sample Rate</span>
                            <span class="font-bold text-amber-700 font-mono">{{ number_format($ngSampleRate, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $ngSampleRate)) }}%"></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-xs text-gray-400">
                    Tidak ada ringkasan quantity untuk shift ini.
                </div>
            @endif
        </div>
    </div>

    {{-- Sign-off Signatures / Autographs --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 print:border-gray-400">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Pengesahan / Signatures</h3>
        <div class="grid grid-cols-3 gap-6 text-center">
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                <span class="text-xs font-medium text-gray-500 block mb-2">Inspector</span>
                <div class="h-16 flex items-center justify-center font-serif italic text-gray-700 text-lg">
                    {{ $r->inspector_autograph ?: ($r->inspector ?: '—') }}
                </div>
                <div class="border-t border-gray-300 pt-1 text-xs text-gray-600 font-medium">
                    {{ $r->inspector ?: 'Inspector' }}
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                <span class="text-xs font-medium text-gray-500 block mb-2">Leader</span>
                <div class="h-16 flex items-center justify-center font-serif italic text-gray-700 text-lg">
                    {{ $r->leader_autograph ?: '—' }}
                </div>
                <div class="border-t border-gray-300 pt-1 text-xs text-gray-600 font-medium">
                    Leader / Sub-Leader
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                <span class="text-xs font-medium text-gray-500 block mb-2">Section Head / SPV</span>
                <div class="h-16 flex items-center justify-center font-serif italic text-gray-700 text-lg">
                    {{ $r->head_autograph ?: '—' }}
                </div>
                <div class="border-t border-gray-300 pt-1 text-xs text-gray-600 font-medium">
                    Section Head
                </div>
            </div>
        </div>
    </div>
</div>
