<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
    <!-- Screen Action Header (Hidden in Print) -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('mould-checklist.index') }}" wire:navigate
                class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900">Check Sheet #{{ $checklist->id }}</h1>
                    @if ($checklist->status === 'APPROVED')
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                            ✓ APPROVED
                        </span>
                    @elseif ($checklist->status === 'SUBMITTED')
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                            ⏳ MENUNGGU VERIFIKASI LEADER
                        </span>
                    @elseif ($checklist->status === 'REJECTED')
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                            ⛔ REJECTED
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600">
                            DRAFT
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Mould: <span class="font-bold text-slate-800">{{ $checklist->mould_no }}</span> | Part: <span class="font-mono text-slate-700">{{ $checklist->part_no }}</span>
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @if ($checklist->status === 'DRAFT' || $checklist->status === 'REJECTED')
                <a href="{{ route('mould-checklist.edit', $checklist->id) }}" wire:navigate
                    class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                    Edit Checklist
                </a>
            @endif

            @if ($checklist->status === 'SUBMITTED' && auth()->user()?->canSign('mould_checklist', 'checked_by'))
                <button type="button" wire:click="openRejectModal"
                    class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-colors">
                    Reject / Kembalikan
                </button>
                <button type="button" wire:click="approveAndSign"
                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Approve & Tanda Tangan (Check By)
                </button>
            @endif

            <button type="button" onclick="window.print()"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Check Sheet
            </button>
        </div>
    </div>

    <!-- Rejection Alert if any -->
    @if ($checklist->status === 'REJECTED' && $checklist->rejection_note)
        <div class="no-print p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-sm">
            <p class="font-bold flex items-center gap-1">
                <span>⛔ Catatan Penolakan (Rejection Note):</span>
            </p>
            <p class="mt-1 text-rose-800">{{ $checklist->rejection_note }}</p>
        </div>
    @endif

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="no-print p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="no-print p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('info'))
        <div class="no-print p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm font-medium">
            {{ session('info') }}
        </div>
    @endif

    <!-- 1:1 PHYSICAL DOCUMENT CONTAINER -->
    <div class="print-document bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-300 text-slate-900">
        <!-- Document Header Table -->
        <table class="w-full border-collapse border border-slate-900 text-xs">
            <tr>
                <!-- Company Logo & Title Cell -->
                <td rowspan="2" class="border border-slate-900 p-3 text-center align-middle w-28 bg-slate-50">
                    <div class="font-black text-xl tracking-tighter text-slate-900">DJ</div>
                    <div class="text-[9px] font-bold tracking-tight text-slate-700 uppercase">PT DAIJO INDUSTRIAL</div>
                </td>
                <td rowspan="2" class="border border-slate-900 p-3 text-center align-middle">
                    <h2 class="text-sm font-extrabold uppercase tracking-wide">LIST CHECK SHEET</h2>
                    <h3 class="text-base font-black uppercase tracking-wider mt-0.5">MOULD MAINTENANCE</h3>
                </td>
                <!-- Signature Blocks Header -->
                <td class="border border-slate-900 px-3 py-1 text-center font-bold text-[11px] w-32 bg-slate-50">Issued by</td>
                <td class="border border-slate-900 px-3 py-1 text-center font-bold text-[11px] w-32 bg-slate-50">Check by</td>
            </tr>
            <tr>
                <!-- Signature Sign-off Boxes -->
                <td class="border border-slate-900 p-2 text-center align-bottom h-20 relative">
                    @if ($checklist->issued_at)
                        <div class="text-center">
                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 border border-emerald-500 rounded bg-emerald-50/50">
                                SIGNED DIGITAL
                            </span>
                            <div class="text-[11px] font-bold mt-1 text-slate-900 underline">{{ $checklist->issued_name ?: $checklist->technician_name }}</div>
                            <div class="text-[9px] text-slate-500 font-mono">{{ $checklist->issued_at->format('d/m/Y H:i') }}</div>
                        </div>
                    @else
                        <span class="text-slate-300 text-[10px] italic">(Belum Ditandatangani)</span>
                    @endif
                </td>
                <td class="border border-slate-900 p-2 text-center align-bottom h-20 relative">
                    @if ($checklist->checked_at)
                        <div class="text-center">
                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold text-blue-800 border border-blue-500 rounded bg-blue-50/50">
                                VERIFIED LEADER
                            </span>
                            <div class="text-[11px] font-bold mt-1 text-slate-900 underline">{{ $checklist->checked_name }}</div>
                            <div class="text-[9px] text-slate-500 font-mono">{{ $checklist->checked_at->format('d/m/Y H:i') }}</div>
                        </div>
                    @else
                        <span class="text-slate-300 text-[10px] italic">(Menunggu Check)</span>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Metadata Information Box -->
        <div class="border-x border-b border-slate-900 p-3 grid grid-cols-2 sm:grid-cols-3 gap-y-1.5 text-xs">
            <div>
                <span class="font-bold text-slate-700">MOULD NO :</span>
                <span class="font-bold text-indigo-900 ml-1 font-mono">{{ $checklist->mould_no }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">CUSTOMER :</span>
                <span class="font-semibold text-slate-900 ml-1">{{ $checklist->customer }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">TANGGAL :</span>
                <span class="font-semibold text-slate-900 ml-1">{{ $checklist->check_date ? $checklist->check_date->format('d/m/Y') : '-' }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">PART NO :</span>
                <span class="font-mono text-slate-900 ml-1">{{ $checklist->part_no }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">MESIN INJECTION :</span>
                <span class="font-mono text-slate-900 ml-1">{{ $checklist->machine_number ?: '-' }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">TEKNISI NAME :</span>
                <span class="font-semibold text-slate-900 ml-1">{{ $checklist->technician_name }}</span>
            </div>
            <div class="col-span-2">
                <span class="font-bold text-slate-700">PART NAME :</span>
                <span class="font-semibold text-slate-900 ml-1">{{ $checklist->part_name }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-700">MODEL :</span>
                <span class="font-semibold text-slate-900 ml-1">{{ $checklist->model ?: '-' }}</span>
            </div>
        </div>

        <!-- 16 Check Points Table -->
        <table class="w-full border-collapse border border-slate-900 text-xs mt-3">
            <thead>
                <tr class="bg-slate-100 text-slate-900 font-bold text-center">
                    <th class="border border-slate-900 py-1.5 px-2 w-10">NO</th>
                    <th class="border border-slate-900 py-1.5 px-3 text-left">POINT CHECK</th>
                    <th class="border border-slate-900 py-1.5 px-2 w-28">CLEANING</th>
                    <th class="border border-slate-900 py-1.5 px-2 w-28">LUBRICATE</th>
                    <th class="border border-slate-900 py-1.5 px-3 text-left">REMARK</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checklist->details as $d)
                    <tr class="hover:bg-slate-50">
                        <td class="border border-slate-900 py-1 px-2 text-center font-mono font-bold">{{ $d->item_no }}</td>
                        <td class="border border-slate-900 py-1 px-3 font-semibold text-slate-900">{{ $d->point_check }}</td>
                        <td class="border border-slate-900 py-1 px-2 text-center font-bold {{ $d->cleaning_status === 'NG' ? 'text-rose-700 bg-rose-50' : ($d->cleaning_status === 'OK' ? 'text-emerald-700' : 'text-slate-400') }}">
                            {{ $d->cleaning_status }}
                        </td>
                        <td class="border border-slate-900 py-1 px-2 text-center font-bold {{ $d->lubricate_status === 'NG' ? 'text-rose-700 bg-rose-50' : ($d->lubricate_status === 'OK' ? 'text-emerald-700' : 'text-slate-400') }}">
                            {{ $d->lubricate_status }}
                        </td>
                        <td class="border border-slate-900 py-1 px-3 text-slate-700">{{ $d->remark ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Bottom Document Footnote & Doc Code -->
        <div class="mt-3 flex items-center justify-between text-[11px] font-mono border-t border-slate-900 pt-2 text-slate-700">
            <div>
                <span class="font-bold">JAM PENGERJAAN:</span>
                <span class="font-semibold text-slate-900 ml-1">
                    {{ ($checklist->start_time && $checklist->end_time) ? ($checklist->start_time . ' s/d ' . $checklist->end_time) : '-' }}
                </span>
            </div>
            <div class="flex items-center gap-4">
                <span>Rev : {{ $checklist->revision }}</span>
                <span class="font-bold">No. Dok : {{ $checklist->doc_number }}</span>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    @if ($showRejectModal)
        <div class="no-print fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">Tolak (Reject) Check Sheet</h3>
                <p class="text-xs text-slate-500 mt-1">Dokumen akan dikembalikan ke status DRAFT agar teknisi dapat memperbaiki pemeriksaan.</p>

                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alasan Penolakan *</label>
                    <textarea wire:model="rejectionNote" rows="3" placeholder="Tuliskan catatan perbaikan untuk teknisi..."
                        class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                    @error('rejectionNote') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-200">
                    <button type="button" wire:click="closeRejectModal"
                        class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="button" wire:click="confirmReject"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                        Konfirmasi Reject
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .no-print, header, nav, aside {
            display: none !important;
        }
        .print-document {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
    }
    </style>
</div>
