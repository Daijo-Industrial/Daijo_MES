<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Step 4: Review Data & Final Submit
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Periksa seluruh data sebelum disimpan secara permanen ke database.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg border border-indigo-200">
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
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-xs font-bold text-amber-800">Perhatian: Ada Periode Yang Belum Lengkap</h3>
                    <div class="mt-1 text-xs text-amber-700">
                        Sistem hanya akan menyimpan periode yang seluruh checklist-nya telah terisi lengkap. Periode yang belum lengkap akan otomatis dilewati saat submit.
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 1. General & Part Metadata Recap --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Informasi Umum & Part</h3>
        <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 text-xs">
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Tanggal</dt>
                <dd class="font-bold text-gray-900 mt-0.5">{{ $headerData['inspection_date'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Customer</dt>
                <dd class="font-bold text-gray-900 mt-0.5 truncate">{{ $headerData['customer'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Shift</dt>
                <dd class="font-bold text-gray-900 mt-0.5">Shift {{ $headerData['shift'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Part Number</dt>
                <dd class="font-bold text-gray-900 mt-0.5 font-mono truncate">{{ $headerData['part_number'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Part Name</dt>
                <dd class="font-bold text-gray-900 mt-0.5 truncate">{{ $headerData['part_name'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Mesin</dt>
                <dd class="font-bold text-gray-900 mt-0.5">{{ $headerData['machine_number'] ?? '-' }}</dd>
            </div>
            <div class="bg-gray-50 p-2.5 rounded-lg">
                <dt class="text-gray-400 font-medium">Inspector</dt>
                <dd class="font-bold text-gray-900 mt-0.5 truncate">{{ $headerData['inspector'] ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- 2. Quantity & Rate Summary --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Akumulasi Output & Tingkat Kualitas Shift</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-center mb-5">
            <div class="bg-indigo-50/70 p-3 rounded-lg border border-indigo-100">
                <span class="text-[11px] font-medium text-indigo-700 uppercase block mb-1">Total Output</span>
                <span class="text-2xl font-bold font-mono text-indigo-900">{{ number_format($quantityData['total_output'] ?? 0) }}</span>
            </div>
            <div class="bg-green-50/70 p-3 rounded-lg border border-green-100">
                <span class="text-[11px] font-medium text-green-700 uppercase block mb-1">Total Pass</span>
                <span class="text-2xl font-bold font-mono text-green-900">{{ number_format($quantityData['total_pass'] ?? 0) }}</span>
            </div>
            <div class="bg-red-50/70 p-3 rounded-lg border border-red-100">
                <span class="text-[11px] font-medium text-red-700 uppercase block mb-1">Total Reject</span>
                <span class="text-2xl font-bold font-mono text-red-900">{{ number_format($quantityData['total_reject'] ?? 0) }}</span>
            </div>
            <div class="bg-blue-50/70 p-3 rounded-lg border border-blue-100">
                <span class="text-[11px] font-medium text-blue-700 uppercase block mb-1">Total Sample</span>
                <span class="text-2xl font-bold font-mono text-blue-900">{{ number_format($quantityData['total_sample'] ?? 0) }}</span>
            </div>
            <div class="bg-amber-50/70 p-3 rounded-lg border border-amber-100">
                <span class="text-[11px] font-medium text-amber-700 uppercase block mb-1">Total NG Sample</span>
                <span class="text-2xl font-bold font-mono text-amber-900">{{ number_format($quantityData['total_ng_sample'] ?? 0) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-gray-700">Pass Rate</span>
                    <span class="text-green-700 font-mono">{{ number_format($quantityData['pass_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['pass_rate'] ?? 0)) }}%"></div>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-gray-700">Reject Rate</span>
                    <span class="text-red-700 font-mono">{{ number_format($quantityData['reject_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-red-500 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['reject_rate'] ?? 0)) }}%"></div>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                    <span class="text-gray-700">NG Sample Rate</span>
                    <span class="text-amber-700 font-mono">{{ number_format($quantityData['ng_sample_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-amber-500 h-2 rounded-full" style="width: {{ min(100, max(0, $quantityData['ng_sample_rate'] ?? 0)) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Final Submit Actions --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h4 class="text-sm font-bold text-gray-900">Siap untuk Menyimpan Laporan?</h4>
            <p class="text-xs text-gray-500">Laporan akan tersimpan ke database MES dan terhubung ke arsip riwayat inspeksi.</p>
        </div>
        <button type="button"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:confirm="Konfirmasi: Simpan laporan inspeksi ini sekarang?"
                class="inline-flex items-center gap-2 px-6 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition disabled:opacity-50">
            <span wire:loading.remove wire:target="submit">✓ Simpan Laporan IPQC</span>
            <span wire:loading wire:target="submit" class="flex items-center gap-1.5">
                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Menyimpan Data...
            </span>
        </button>
    </div>
</div>
