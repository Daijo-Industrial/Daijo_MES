<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pallet Live Check — {{ $palletId }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-full pb-12 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Header (Sticky with Glassmorphism) -->
    <header class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-teal-800 text-white p-4 sm:p-5 shadow-lg sticky top-0 z-30 backdrop-blur-md bg-opacity-95 border-b border-emerald-600/40">
        <div class="max-w-lg md:max-w-xl lg:max-w-2xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner">
                    <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <h1 class="text-[10px] font-black tracking-widest text-emerald-300 uppercase leading-tight">PT. DAIJO INDUSTRIAL &bull; MES WMS</h1>
                    <p class="text-base font-black tracking-tight leading-none text-white">Material Pallet Live Check</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="window.location.reload()" class="p-2 bg-white/10 hover:bg-white/20 active:scale-95 text-emerald-200 rounded-xl transition backdrop-blur-sm border border-white/20" title="Refresh Live Data">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-lg md:max-w-xl lg:max-w-2xl mx-auto p-4 sm:p-6 space-y-4 sm:space-y-6">

        @if ($pallet)
            @php
                $initial = max(0.01, (float)$pallet->initial_qty);
                $current = (float)$pallet->current_qty;
                $pct = min(100, max(0, round(($current / $initial) * 100)));
            @endphp

            <!-- Pallet Card Header -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 space-y-5">
                <div class="flex flex-wrap sm:flex-nowrap justify-between items-start gap-2">
                    <div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Unit Pallet ID</span>
                        <div class="flex items-center space-x-2">
                            <h2 class="text-xl sm:text-2xl font-black font-mono text-emerald-950 tracking-tight">{{ $pallet->pallet_id }}</h2>
                            <button onclick="navigator.clipboard.writeText('{{ $pallet->pallet_id }}'); alert('Pallet ID disalin!');" class="text-slate-400 hover:text-emerald-600 transition" title="Salin Kode">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        @if ($pallet->is_qc_hold)
                            <span class="inline-flex items-center px-3 py-1 bg-rose-600 text-white rounded-full font-black text-xs shadow-md animate-pulse">
                                ⛔ QC HOLD
                            </span>
                        @elseif ($pallet->status === 'STORED')
                            <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full font-black text-xs border border-emerald-200 shadow-xs">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                                STORED (Utuh)
                            </span>
                        @elseif ($pallet->status === 'PARTIAL')
                            <span class="inline-flex items-center px-3 py-1 bg-amber-100 text-amber-900 rounded-full font-black text-xs border border-amber-200 shadow-xs">
                                <span class="w-2 h-2 bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>
                                PARTIAL (Sebagian)
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-500 rounded-full font-black text-xs border border-slate-200">
                                <span class="w-2 h-2 bg-slate-400 rounded-full mr-1.5"></span>
                                EMPTY (Habis)
                            </span>
                        @endif
                    </div>
                </div>

                @if ($pallet->is_qc_hold)
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-900 space-y-1">
                        <div class="flex items-center space-x-2 font-black text-xs text-rose-700 uppercase tracking-wider">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Status QC HOLD (Kunci Material)</span>
                        </div>
                        <p class="text-xs font-semibold text-rose-800">Alasan / Remark: {{ $pallet->qc_hold_reason ?: 'Menunggu approval / verifikasi QC' }}</p>
                    </div>
                @endif

                <!-- Stock Qty Banner with Dynamic Progress Bar -->
                <div class="p-5 bg-gradient-to-br from-emerald-900 to-teal-950 text-white rounded-2xl shadow-md space-y-3 relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex justify-between items-baseline text-xs font-semibold text-emerald-200">
                        <span class="text-[10px] font-black uppercase tracking-widest">Sisa Stok KG Realtime</span>
                        <span>Awal: <strong>{{ number_format($pallet->initial_qty, 2) }} KG</strong></span>
                    </div>

                    <div class="flex items-baseline space-x-2">
                        <span class="text-3xl sm:text-4xl font-black text-white font-mono tracking-tight">{{ number_format($pallet->current_qty, 2) }}</span>
                        <span class="text-lg font-bold text-emerald-300">KG</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="space-y-1 pt-1">
                        <div class="w-full bg-emerald-950/80 rounded-full h-2 overflow-hidden p-0.5 border border-emerald-700/50">
                            <div class="bg-gradient-to-r from-emerald-400 to-teal-300 h-full rounded-full transition-all duration-700" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-emerald-300 font-medium">
                            <span>Ketersediaan Stok</span>
                            <span class="font-bold">{{ $pct }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Part Code & Description Card -->
                <div class="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1.5">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Part Code Material</span>
                    <div class="text-lg font-black font-mono text-slate-900">{{ $pallet->item_code }}</div>
                    <div class="text-xs text-slate-600 font-semibold leading-relaxed">
                        {{ $pallet->material ? $pallet->material->item_description : '-' }}
                    </div>
                </div>

                <!-- Slot Location & Specs Grid (Responsive 2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <!-- Slot Location -->
                    <div class="p-4 bg-emerald-50/70 rounded-2xl border border-emerald-200/80 space-y-1">
                        <span class="text-[9px] font-black text-emerald-800 uppercase tracking-widest block">Lokasi Slot Rak</span>
                        @php
                            $effectivePos = $pallet->position ?: ($pallet->initialPosition ?: ($pallet->outgoings?->first()?->position ?: null));
                        @endphp
                        <span class="font-mono font-black text-emerald-950 block text-base">
                            {{ $effectivePos ? $effectivePos->position_code : 'Unassigned' }}
                        </span>
                        <span class="text-[11px] text-emerald-700 font-bold block">
                            {{ $effectivePos ? ($effectivePos->slot_label ?: 'Slot') : 'Slot -' }}
                            @if ($effectivePos && $effectivePos->rack)
                                &bull; Rak {{ $effectivePos->rack->rack_code }}
                            @endif
                        </span>
                    </div>

                    <!-- Lot / Batch No & Supplier -->
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Lot / Batch No</span>
                        <span class="font-mono font-black text-slate-900 block text-sm">
                            {{ $pallet->lot_no ?: '-' }}
                        </span>
                        <span class="text-[11px] text-slate-600 font-semibold block truncate">
                            {{ $pallet->incomingHeader ? ($pallet->incomingHeader->supplier_name ?: 'Supplier -') : 'Supplier -' }}
                        </span>
                    </div>
                </div>

                <!-- Document Info Footer -->
                <div class="pt-3 border-t border-slate-100 flex flex-wrap justify-between items-center text-xs text-slate-500 font-medium gap-2">
                    <div>No. PO: <strong class="font-mono text-slate-800">{{ $pallet->incomingHeader ? ($pallet->incomingHeader->po_number ?: '-') : '-' }}</strong></div>
                    <div>Tgl Kedatangan: <strong class="font-mono text-slate-800">{{ $pallet->incomingHeader && $pallet->incomingHeader->arrival_date ? $pallet->incomingHeader->arrival_date->format('d M Y') : ($pallet->created_at ? $pallet->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-') }}</strong></div>
                </div>
            </div>

            <!-- History Movement Card -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>History Pengambilan (Outgoings)</span>
                    </h3>
                    <span class="text-[10px] bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full font-bold">
                        {{ count($pallet->outgoings ?: []) }} Transaksi
                    </span>
                </div>

                <div class="space-y-2.5">
                    @forelse ($pallet->outgoings as $out)
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70 text-xs flex justify-between items-center gap-2 hover:border-emerald-300 transition">
                            <div class="space-y-0.5">
                                <div class="font-mono font-black text-slate-900 text-xs">{{ $out->outgoing_code }}</div>
                                <div class="text-[10px] text-slate-500 font-medium">
                                    Tujuan: <strong class="text-slate-700">{{ $out->issued_to ?: '-' }}</strong> &bull; {{ $out->created_at ? $out->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : ($out->outgoing_date ? $out->outgoing_date->format('d M Y') : '-') }} WIB
                                </div>
                            </div>
                            <span class="font-black text-rose-600 font-mono text-sm whitespace-nowrap bg-rose-50 px-2.5 py-1 rounded-xl border border-rose-100">
                                -{{ number_format($out->qty_taken, 2) }} KG
                            </span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs font-semibold italic space-y-1">
                            <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p>Belum ada history pengambilan material dari palet ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <!-- Pallet Not Found State -->
            <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-sm border border-slate-200/80 text-center space-y-4">
                <div class="w-14 h-14 bg-rose-50 text-rose-500 rounded-3xl flex items-center justify-center mx-auto border border-rose-100 shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h2 class="text-lg font-black text-slate-900">Pallet ID Tidak Ditemukan</h2>
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs mx-auto">
                    Kode Pallet <strong class="font-mono text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $palletId }}</strong> tidak terdaftar atau telah dihapus dalam database gudang material.
                </p>
            </div>
        @endif

        <div class="text-center text-[11px] text-slate-400 font-medium pt-2">
            PT. Daijo Industrial &bull; Powered by Daijo MES WMS System
        </div>
    </main>

</body>
</html>
