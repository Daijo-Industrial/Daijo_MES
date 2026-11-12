<div class="space-y-6" x-data="{ activePreviewPeriod: 1, showProblemPreview: true }">
    {{-- Header Banner --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-zinc-900"></span>
                    Step 4: Review Data & Final Submit
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">Periksa seluruh data sebelum disimpan secara permanen ke database MES.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold bg-zinc-100 text-zinc-800 px-3.5 py-1.5 rounded-lg border border-zinc-200">
                    {{ $headerData['document_number'] ?? '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Holes / Completeness Warning --}}
    @if ($hasHoles)
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-xs font-bold text-amber-900">Perhatian: Ada Periode Yang Belum Lengkap</h3>
                    <div class="mt-1 text-xs text-amber-800">
                        Sistem hanya akan menyimpan periode yang seluruh checklist-nya telah terisi lengkap. Periode yang belum lengkap akan otomatis dilewati saat submit.
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 1. General & Part Metadata Recap --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
        <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-3">Informasi Umum & Part</h3>
        <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 text-xs">
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Tanggal</dt>
                <dd class="font-bold text-zinc-900 mt-0.5">{{ $headerData['inspection_date'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Customer</dt>
                <dd class="font-bold text-zinc-900 mt-0.5 truncate">{{ $headerData['customer'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Shift</dt>
                <dd class="font-bold text-zinc-900 mt-0.5">Shift {{ $headerData['shift'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Part Number</dt>
                <dd class="font-bold text-zinc-900 mt-0.5 font-mono truncate">{{ $headerData['part_number'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Part Name</dt>
                <dd class="font-bold text-zinc-900 mt-0.5 truncate">{{ $headerData['part_name'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Mesin</dt>
                <dd class="font-bold text-zinc-900 mt-0.5">{{ $headerData['machine_number'] ?? '-' }}</dd>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <dt class="text-zinc-400 font-medium">Inspector</dt>
                <dd class="font-bold text-zinc-900 mt-0.5 truncate">{{ $headerData['inspector'] ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- 2. Quantity & Rate Summary --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
        <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Akumulasi Output & Tingkat Kualitas Shift</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-center mb-5">
            <div class="bg-zinc-100 p-3 rounded-lg border border-zinc-200">
                <span class="text-[11px] font-semibold text-zinc-700 uppercase tracking-wider block mb-1">Total Output</span>
                <span class="text-2xl font-bold font-mono text-zinc-900">{{ number_format($quantityData['total_output'] ?? 0) }}</span>
            </div>
            <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-200">
                <span class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider block mb-1">Total Pass</span>
                <span class="text-2xl font-bold font-mono text-emerald-900">{{ number_format($quantityData['total_pass'] ?? 0) }}</span>
            </div>
            <div class="bg-rose-50 p-3 rounded-lg border border-rose-200">
                <span class="text-[11px] font-semibold text-rose-800 uppercase tracking-wider block mb-1">Total Reject</span>
                <span class="text-2xl font-bold font-mono text-rose-900">{{ number_format($quantityData['total_reject'] ?? 0) }}</span>
            </div>
            <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <span class="text-[11px] font-semibold text-zinc-700 uppercase tracking-wider block mb-1">Total Sample</span>
                <span class="text-2xl font-bold font-mono text-zinc-900">{{ number_format($quantityData['total_sample'] ?? 0) }}</span>
            </div>
            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200">
                <span class="text-[11px] font-semibold text-amber-800 uppercase tracking-wider block mb-1">Total NG Sample</span>
                <span class="text-2xl font-bold font-mono text-amber-900">{{ number_format($quantityData['total_ng_sample'] ?? 0) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-zinc-50 p-3.5 rounded-lg border border-zinc-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-zinc-700">Pass Rate</span>
                    <span class="text-emerald-800 font-mono font-bold">{{ number_format($quantityData['pass_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-zinc-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['pass_rate'] ?? 0)) }}%"></div>
                </div>
            </div>

            <div class="bg-zinc-50 p-3.5 rounded-lg border border-zinc-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-zinc-700">Reject Rate</span>
                    <span class="text-rose-800 font-mono font-bold">{{ number_format($quantityData['reject_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-zinc-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-rose-600 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['reject_rate'] ?? 0)) }}%"></div>
                </div>
            </div>

            <div class="bg-zinc-50 p-3.5 rounded-lg border border-zinc-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-zinc-700">NG Sample Rate</span>
                    <span class="text-amber-800 font-mono font-bold">{{ number_format($quantityData['ng_sample_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-zinc-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-amber-600 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['ng_sample_rate'] ?? 0)) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Collapsible Period & Problem Inspection Breakdown --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-3">
            <div>
                <h3 class="text-sm font-bold text-zinc-900">Periksa Rincian Periode & Problem</h3>
                <p class="text-xs text-zinc-500">Klik tab periode untuk melihat rincian pengukuran sebelum submit permanen.</p>
            </div>
            <div class="flex items-center gap-1.5">
                @foreach (range(1, 4) as $p)
                    @php $pk = 'p' . $p; $hasData = !empty($detailData[$pk]) || !empty($firstData[$pk]); @endphp
                    <button type="button"
                            @click="activePreviewPeriod = {{ $p }}"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg border transition"
                            :class="activePreviewPeriod === {{ $p }} ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-zinc-50 text-zinc-700 border-zinc-200 hover:bg-zinc-100'">
                        Periode {{ $p }}
                    </button>
                @endforeach
            </div>
        </div>

        @foreach (range(1, 4) as $p)
            @php
                $pk = 'p' . $p;
                $pFirst = $firstData[$pk] ?? null;
                $pDims = $dimensionData[$pk] ?? [];
                $pSecond = $secondData[$pk] ?? null;
                $pJudge = $judgementData[$pk] ?? null;
            @endphp
            <div x-show="activePreviewPeriod === {{ $p }}" x-transition.opacity class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 font-semibold block uppercase tracking-wider text-[10px]">First Piece Appearance</span>
                        <span class="font-bold mt-1 inline-block px-2 py-0.5 rounded {{ ($pFirst['appearance'] ?? '') === 'OK' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $pFirst['appearance'] ?? 'Belum terisi' }}
                        </span>
                        <span class="block text-zinc-600 mt-1">Weight: <strong>{{ $pFirst['weight'] ?? '-' }} {{ $pFirst['weight_uom'] ?? 'g' }}</strong></span>
                    </div>

                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 font-semibold block uppercase tracking-wider text-[10px]">Lot Size (Output)</span>
                        <span class="text-base font-bold font-mono text-zinc-900 mt-1 block">
                            {{ isset($pSecond['lot_size_quantity']) ? number_format($pSecond['lot_size_quantity']) . ' pcs' : 'Skipped / None' }}
                        </span>
                    </div>

                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 font-semibold block uppercase tracking-wider text-[10px]">Pass Qty (Periode {{ $p }})</span>
                        <span class="text-base font-bold font-mono text-emerald-800 mt-1 block">
                            {{ isset($pJudge['pass_quantity']) ? number_format($pJudge['pass_quantity']) . ' pcs' : '-' }}
                        </span>
                    </div>

                    <div class="bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                        <span class="text-zinc-500 font-semibold block uppercase tracking-wider text-[10px]">Reject Qty (Periode {{ $p }})</span>
                        <span class="text-base font-bold font-mono text-rose-800 mt-1 block">
                            {{ isset($pJudge['reject_quantity']) ? number_format($pJudge['reject_quantity']) . ' pcs' : '-' }}
                        </span>
                    </div>
                </div>

                {{-- Dimension Table Preview --}}
                @if (!empty($pDims))
                    <div class="border border-zinc-200 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-zinc-200 text-xs">
                            <thead class="bg-zinc-50 font-semibold text-zinc-600 text-center">
                                <tr>
                                    <th class="px-3 py-2 text-left">Titik Dimensi</th>
                                    <th class="px-2 py-2">Batas Min</th>
                                    <th class="px-2 py-2">Batas Max</th>
                                    <th class="px-2 py-2">Satuan</th>
                                    <th class="px-3 py-2">Aktual</th>
                                    <th class="px-2 py-2">Judgement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white text-center">
                                @foreach ($pDims as $dim)
                                    <tr>
                                        <td class="px-3 py-1.5 text-left font-medium text-zinc-800">{{ $dim['area'] ?? '-' }}</td>
                                        <td class="px-2 py-1.5 font-mono text-zinc-600">{{ $dim['lower_limit'] ?? '-' }}</td>
                                        <td class="px-2 py-1.5 font-mono text-zinc-600">{{ $dim['upper_limit'] ?? '-' }}</td>
                                        <td class="px-2 py-1.5 font-mono text-zinc-600">{{ $dim['limit_uom'] ?? 'mm' }}</td>
                                        <td class="px-3 py-1.5 font-mono font-bold text-zinc-900">{{ $dim['actual_value'] ?? '-' }}</td>
                                        <td class="px-2 py-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ ($dim['judgement'] ?? '') === 'OK' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                {{ $dim['judgement'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 bg-zinc-50 rounded-lg border border-dashed border-zinc-200 text-xs text-zinc-500">
                        Tidak ada pengukuran dimensi pada Periode {{ $p }}.
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Final Submit Actions --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <button type="button"
                    wire:click="$dispatch('prevStep')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-zinc-300 text-sm font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Step 3 (Masalah)</span>
            </button>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    wire:confirm="Konfirmasi: Simpan laporan inspeksi ini secara permanen ke database?"
                    class="inline-flex items-center gap-2 px-6 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 transition disabled:opacity-50">
                <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Simpan Laporan IPQC</span>
                </span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Menyimpan ke Database...</span>
                </span>
            </button>
        </div>
    </div>
</div>