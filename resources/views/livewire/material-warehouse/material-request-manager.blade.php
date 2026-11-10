<div class="p-6 bg-gray-50 min-h-screen space-y-6" style="font-family: 'IBM Plex Sans', sans-serif;">
    {{-- Header Banner --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 bg-sky-500 rounded-full inline-block"></span>
                <h1 class="text-2xl font-black text-gray-800 tracking-tight">Permintaan Material dari Produksi (Requests)</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Daftar permintaan material yang dikirimkan oleh operator / PPIC lantai produksi. Tanggapi dengan memproses Outgoing Picking atau tolak jika stok tidak mencukupi / bermasalah.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('mwh.outgoing.create') }}" class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center space-x-2">
                <span>➕</span>
                <span>Buat Outgoing Manual</span>
            </a>
        </div>
    </div>

    {{-- KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Pending Requests --}}
        <div class="bg-white p-5 rounded-2xl border border-amber-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Menunggu Tanggapan</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">⏳</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-mono font-black text-amber-600">{{ $pendingCount }}</span>
                <span class="text-xs font-bold text-gray-400">Request</span>
            </div>
            <div class="text-[11px] text-amber-800 font-semibold mt-1">
                Total Qty: <span class="font-mono font-bold">{{ number_format($totalPendingQty, 2) }} KG</span>
            </div>
        </div>

        {{-- Card 2: Processed Requests --}}
        <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sudah Diproses</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">✓</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-mono font-black text-emerald-600">{{ $processedCount }}</span>
                <span class="text-xs font-bold text-gray-400">Request</span>
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Telah dibuatkan dokumen Outgoing</div>
        </div>

        {{-- Card 3: Rejected Requests --}}
        <div class="bg-white p-5 rounded-2xl border border-rose-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Ditolak</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">✕</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-mono font-black text-rose-600">{{ $rejectedCount }}</span>
                <span class="text-xs font-bold text-gray-400">Request</span>
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Dibatalkan dengan alasan kendala</div>
        </div>

        {{-- Card 4: Quick Link to Floor Stock --}}
        <div class="bg-gradient-to-br from-indigo-900 to-slate-900 p-5 rounded-2xl shadow-sm text-white flex flex-col justify-between">
            <div>
                <div class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Station Produksi</div>
                <div class="text-sm font-black mt-1">Monitoring & Tracking Material</div>
                <p class="text-[11px] text-indigo-300 mt-1">Lihat stok aktual lantai produksi dan verifikasi penerimaan.</p>
            </div>
            <div class="pt-3">
                <a href="{{ route('production.material-station') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition">
                    <span>Buka Material Station</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Main Filter & Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden space-y-4 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-4">
            {{-- Status Filter Tabs --}}
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl">
                <button type="button" wire:click="$set('statusFilter', 'PENDING')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'PENDING' ? 'bg-white text-amber-700 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                    <span>🟡 Menunggu Tanggapan</span>
                    @if($pendingCount > 0)
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800 font-mono">{{ $pendingCount }}</span>
                    @endif
                </button>
                <button type="button" wire:click="$set('statusFilter', 'PROCESSED')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'PROCESSED' ? 'bg-white text-emerald-700 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                    <span>🟢 Sudah Diproses</span>
                </button>
                <button type="button" wire:click="$set('statusFilter', 'REJECTED')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'REJECTED' ? 'bg-white text-rose-700 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                    <span>🔴 Ditolak</span>
                </button>
                <button type="button" wire:click="$set('statusFilter', 'ALL')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'ALL' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}">
                    <span>Semua Status</span>
                </button>
            </div>

            {{-- Date & Search Bar --}}
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <div class="flex items-center gap-1 text-xs">
                    <input type="date" wire:model.live="startDate" class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50">
                    <span class="text-gray-400">-</span>
                    <input type="date" wire:model.live="endDate" class="text-xs py-1.5 px-2.5 border border-gray-200 rounded-xl bg-gray-50">
                </div>

                <div class="relative w-full sm:w-60">
                    <input type="text" wire:model.live.debounce.250ms="search"
                           placeholder="Cari No Req, SPK, Item, Mesin..."
                           class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-sky-500 transition">
                    <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                </div>
            </div>
        </div>

        {{-- Requests Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-4">No. Request & Waktu</th>
                        <th class="py-3 px-4">Material (Kode & Nama)</th>
                        <th class="py-3 px-4 text-center">Mesin / SPK</th>
                        <th class="py-3 px-4 text-right">Qty Diminta</th>
                        <th class="py-3 px-4 text-center">Urgensi</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Keterangan / Catatan</th>
                        <th class="py-3 px-4 text-center w-48">Aksi Store</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $req)
                        <tr class="hover:bg-gray-50/50 transition {{ $req->status === 'PENDING' ? 'bg-amber-50/20' : '' }}">
                            {{-- Request Code & Time --}}
                            <td class="py-3 px-4 font-mono">
                                <div class="font-bold text-gray-900">{{ $req->request_code }}</div>
                                <div class="text-[10px] text-gray-400">
                                    {{ $req->created_at ? $req->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : '-' }}
                                </div>
                                <div class="text-[10px] text-gray-500 font-sans">
                                    Pemohon: <strong>{{ $req->requested_by_name ?: '-' }}</strong>
                                </div>
                            </td>

                            {{-- Material --}}
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-gray-900">{{ $req->item_code }}</div>
                                <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ $req->item_name ?: ($req->material?->item_description ?: '-') }}</div>
                            </td>

                            {{-- Mesin & SPK --}}
                            <td class="py-3 px-4 text-center font-mono">
                                <div class="font-bold text-indigo-700">{{ $req->machine_no ?: '-' }}</div>
                                @if($req->spk_code)
                                    <div class="text-[10px] text-gray-400">SPK: {{ $req->spk_code }}</div>
                                @endif
                            </td>

                            {{-- Qty Requested --}}
                            <td class="py-3 px-4 text-right font-mono font-black text-sm text-gray-900">
                                {{ number_format($req->qty_requested, 2) }} <span class="text-[10px] text-gray-400 font-normal">KG</span>
                            </td>

                            {{-- Urgensi --}}
                            <td class="py-3 px-4 text-center">
                                @if($req->urgency === 'URGENT')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 animate-pulse">
                                        <span>🔴</span>
                                        <span>URGENT</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                        Normal
                                    </span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="py-3 px-4 text-center">
                                @if($req->status === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                        <span>⏳ Menunggu</span>
                                    </span>
                                @elseif($req->status === 'PROCESSED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span>✓ Selesai</span>
                                    </span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <span>✕ Ditolak</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">
                                        Dibatalkan
                                    </span>
                                @endif
                            </td>

                            {{-- Keterangan / Notes / Reject Reason --}}
                            <td class="py-3 px-4">
                                @if($req->status === 'PROCESSED')
                                    <div class="font-mono text-emerald-700 font-bold">
                                        Ref: {{ $req->outgoing_code ?: '-' }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        Diproses: {{ $req->processed_by_name ?: '-' }} ({{ $req->processed_at ? $req->processed_at->copy()->timezone('Asia/Jakarta')->format('d/m H:i') : '' }})
                                    </div>
                                @elseif($req->status === 'REJECTED')
                                    <div class="bg-rose-50 text-rose-800 p-2 rounded-lg border border-rose-200 text-[11px] max-w-xs">
                                        <div class="font-bold">Alasan Penolakan:</div>
                                        <div class="italic">"{{ $req->reject_reason }}"</div>
                                        <div class="text-[9px] text-rose-500 mt-1 font-mono">
                                            Oleh: {{ $req->rejected_by_name ?: '-' }} ({{ $req->rejected_at ? $req->rejected_at->copy()->timezone('Asia/Jakarta')->format('d/m H:i') : '' }})
                                        </div>
                                    </div>
                                @else
                                    <div class="text-gray-600 text-xs">
                                        {{ $req->notes ? '"' . $req->notes . '"' : '—' }}
                                    </div>
                                    @if($req->needed_at)
                                        <div class="text-[10px] text-sky-700 mt-1 font-semibold">
                                            Target: {{ $req->needed_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 px-4 text-center">
                                @if($req->status === 'PENDING')
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Tombol Tanggapi / Outgoing --}}
                                        <button type="button"
                                                wire:click="fulfillRequest({{ $req->id }})"
                                                class="px-3 py-1.5 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition shadow-xs flex items-center gap-1 cursor-pointer"
                                                title="Tanggapi request ini dengan membuat Outgoing Material">
                                            <span>🚀</span>
                                            <span>Tanggapi</span>
                                        </button>

                                        {{-- Tombol Tolak --}}
                                        <button type="button"
                                                wire:click="openRejectModal({{ $req->id }})"
                                                class="px-2.5 py-1.5 text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 border border-rose-200 rounded-xl transition cursor-pointer"
                                                title="Tolak permintaan material ini">
                                            <span>✕ Tolak</span>
                                        </button>
                                    </div>
                                @elseif($req->status === 'PROCESSED' && $req->outgoing_id)
                                    <span class="text-emerald-600 text-xs font-bold">✓ Terpenuhi</span>
                                @else
                                    <span class="text-gray-300 font-mono text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-gray-400">
                                <div class="text-2xl mb-1">📭</div>
                                <div class="font-bold text-sm">Tidak ada permintaan material yang ditemukan.</div>
                                <div class="text-xs mt-0.5">Semua permintaan dari produksi sudah selesai ditanggapi atau tidak ada data yang cocok dengan filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($requests->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL TOLAK REQUEST --}}
    @if($showRejectModal && $selectedRequest)
        <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="font-black text-gray-900 text-sm flex items-center gap-1.5">
                        <span class="text-rose-600">✕</span>
                        <span>Tolak Permintaan Material</span>
                    </h3>
                    <button type="button" wire:click="closeRejectModal" class="text-gray-400 hover:text-gray-600 font-bold">✕</button>
                </div>

                {{-- Summary Request yang Ditolak --}}
                <div class="bg-rose-50/50 p-3 rounded-xl border border-rose-100 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-bold">No. Request:</span>
                        <span class="font-mono font-bold text-gray-900">{{ $selectedRequest->request_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-bold">Material:</span>
                        <span class="font-mono font-bold text-rose-950">[{{ $selectedRequest->item_code }}]</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-bold">Jumlah Diminta:</span>
                        <span class="font-mono font-black text-rose-700">{{ number_format($selectedRequest->qty_requested, 2) }} KG</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-bold">Tujuan:</span>
                        <span>Mesin {{ $selectedRequest->machine_no ?: '-' }} (Oleh: {{ $selectedRequest->requested_by_name ?: '-' }})</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">
                        Alasan Penolakan <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="rejectReason"
                              rows="3"
                              placeholder="Wajib diisi: jelaskan alasan penolakan (cth: Stok fisik di rak sedang diaudit / dialokasikan untuk SPK prioritas lain / kemasan rusak)..."
                              class="w-full text-xs p-2.5 border border-gray-200 rounded-xl bg-white focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition"></textarea>
                    @error('rejectReason') <span class="text-rose-500 text-[11px] mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeRejectModal"
                            class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="button" wire:click="confirmReject"
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm">
                        ✕ Konfirmasi Tolak
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

