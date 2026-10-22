<div class="min-h-screen bg-gray-50/50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header & Title -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-indigo-200">
                    🛠️
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        Laporan Predictive Maintenance Mesin
                    </h1>
                    <p class="text-xs text-gray-500 font-semibold mt-0.5">
                        Monitoring & Audit Harian Checklist Pengecekan Mesin oleh Tim Maintenance
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="bg-emerald-50 text-emerald-700 text-xs font-bold px-3.5 py-1.5 rounded-full border border-emerald-200 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Monitoring</span>
                </span>
            </div>
        </div>

        <!-- KPI Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider block mb-1">Total Mesin</span>
                    <span class="text-2xl font-black text-gray-800">{{ $totalMachines }}</span>
                    <span class="text-xs text-gray-400 font-medium block mt-0.5">Unit Mesin Produksi</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                    🏭
                </div>
            </div>

            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider block mb-1">Sudah Diisi</span>
                    <span class="text-2xl font-black text-emerald-600">{{ $totalFilled }}</span>
                    <span class="text-xs text-emerald-600/80 font-bold block mt-0.5">{{ round(($totalMachines > 0 ? ($totalFilled/$totalMachines)*100 : 0)) }}% Terpenuhi</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    ✅
                </div>
            </div>

            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider block mb-1">Belum Diisi</span>
                    <span class="text-2xl font-black text-amber-500">{{ $totalPending }}</span>
                    <span class="text-xs text-amber-600/80 font-bold block mt-0.5">Menunggu Pengecekan</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    ⏳
                </div>
            </div>

            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider block mb-1">Ada Defect (NG)</span>
                    <span class="text-2xl font-black text-red-600">{{ $totalNgHeaders }}</span>
                    <span class="text-xs text-red-500 font-bold block mt-0.5">Perlu Tindakan Repair</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl font-bold">
                    ⚠️
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                    <span>🔍 Filter & Pencarian Audit</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Tanggal Produksi</label>
                    <input type="date" wire:model.live="selectedDate" class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Pilih Mesin</label>
                    <select wire:model.live="selectedMachineId" class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        <option value="">-- Semua Mesin --</option>
                        @foreach($machines as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Status Pengecekan</label>
                    <select wire:model.live="selectedStatus" class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                        <option value="">-- Semua Status --</option>
                        <option value="COMPLETED">✅ Normal (Sudah Diisi)</option>
                        <option value="HAS_NG">⚠️ Abnormal (Ada NG)</option>
                        <option value="PENDING">⏳ Belum Diisi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Cari PIC / Mesin</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Ketik kata kunci..." class="w-full text-xs font-bold rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                </div>
            </div>
        </div>

        <!-- Main Report Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-gray-700">
                    Daftar Mesin & Status Checklist Harian ({{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }})
                </span>
                <span class="text-xs text-gray-400 font-bold">Total {{ count($reportRows) }} Mesin</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100/60 text-gray-500 uppercase text-[10px] font-black tracking-wider border-b border-gray-200/80">
                            <th class="py-3.5 px-4 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Nama Mesin</th>
                            <th class="py-3.5 px-4 text-center">Jam Check</th>
                            <th class="py-3.5 px-4">Prepared BY (PIC)</th>
                            <th class="py-3.5 px-4">Approved BY</th>
                            <th class="py-3.5 px-4 text-center">Status Item (OK / NG / Skip)</th>
                            <th class="py-3.5 px-4 text-center">Status Pengecekan</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                        @forelse($reportRows as $idx => $row)
                            <tr class="hover:bg-indigo-50/20 transition-colors {{ $row['status'] === 'HAS_NG' ? 'bg-red-50/30' : '' }}">
                                <td class="py-3.5 px-4 text-center font-bold text-gray-400">
                                    {{ $loop->iteration }}
                                </td>
                                
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-700 font-black flex items-center justify-center text-xs">
                                            {{ substr($row['machine']->name, 0, 2) }}
                                        </div>
                                        <span class="font-bold text-gray-900 text-sm">{{ $row['machine']->name }}</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center font-bold text-gray-600">
                                    {{ $row['header'] ? ($row['header']->check_time ?? '-') : '-' }}
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($row['header'] && $row['header']->prepared_by)
                                        <span class="font-bold text-gray-900 uppercase bg-gray-100 px-2.5 py-1 rounded-lg border border-gray-200">
                                            {{ $row['header']->prepared_by }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 italic">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($row['header'] && $row['header']->approved_by)
                                        <span class="font-bold text-slate-800 uppercase bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                            {{ $row['header']->approved_by }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 italic">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($row['header'])
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-emerald-200">
                                                ✓ {{ $row['ok_count'] }} OK
                                            </span>
                                            @if($row['ng_count'] > 0)
                                                <span class="bg-red-100 text-red-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-red-200 animate-pulse">
                                                    ✕ {{ $row['ng_count'] }} NG
                                                </span>
                                            @endif
                                            @if($row['skip_count'] > 0)
                                                <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full border border-gray-200">
                                                    - {{ $row['skip_count'] }} Lewati
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-300 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($row['status'] === 'HAS_NG')
                                        <span class="inline-flex items-center gap-1 bg-red-600 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-xs">
                                            ⚠️ ABNORMAL (ADA NG)
                                        </span>
                                    @elseif($row['status'] === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1 bg-emerald-600 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-xs">
                                            ✅ COMPLETED (NORMAL)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-[10px] font-black px-3 py-1 rounded-full border border-amber-300">
                                            ⏳ BELUM DIISI
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($row['header'])
                                        <button wire:click="openDetailModal({{ $row['header']->id }})" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-xl shadow-xs transition-colors inline-flex items-center gap-1">
                                            <span>👁️ Detail 17 Item</span>
                                        </button>
                                    @else
                                        <span class="text-gray-300 text-[11px] italic">Tidak Ada Data</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-400 font-bold">
                                    Tidak ada data mesin yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Detail Pengecekan (17 Item) -->
    @if($showDetailModal && $selectedHeader)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" wire:click="closeDetailModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100">
                    
                    <!-- Header Modal -->
                    <div class="bg-slate-900 text-white px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xl">
                                📋
                            </div>
                            <div>
                                <h3 class="text-base font-black tracking-tight uppercase italic text-white flex items-center gap-2">
                                    Detail Audit Pengecekan Mesin
                                </h3>
                                <p class="text-xs text-slate-400 font-medium">
                                    Mesin: <span class="text-white font-bold">{{ $selectedHeader->machine->name }}</span> | Tanggal Produksi: <span class="text-indigo-300 font-bold">{{ \Carbon\Carbon::parse($selectedHeader->date)->format('d-m-Y') }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeDetailModal" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        
                        <!-- Info Signatures -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200/80">
                            <div>
                                <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Prepared BY (PIC Maintenance)</span>
                                <span class="font-black text-gray-900 uppercase text-sm block">{{ $selectedHeader->prepared_by }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Approved BY (Atasan)</span>
                                <span class="font-black text-slate-800 uppercase text-sm block">{{ $selectedHeader->approved_by ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Jam Pengecekan</span>
                                <span class="font-black text-indigo-600 text-sm block">{{ $selectedHeader->check_time ?: '-' }}</span>
                            </div>
                        </div>

                        <!-- 17 Items Detail List -->
                        @php
                            $detailsByItem = $selectedHeader->details->keyBy('item_id');
                            $periods = ['Daily', 'Weekly', 'Two weeks'];
                            $periodBadges = [
                                'Daily' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'Weekly' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'Two weeks' => 'bg-orange-100 text-orange-800 border-orange-200',
                            ];
                        @endphp

                        @foreach($periods as $p)
                            @php
                                $groupItems = $allItems->filter(fn($i) => $i->period === $p);
                            @endphp
                            @if($groupItems->count() > 0)
                                <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                                    <div class="bg-gray-100/80 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                        <span class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black border {{ $periodBadges[$p] ?? 'bg-gray-100' }}">{{ $p }}</span>
                                            <span>Pengecekan {{ $p }} ({{ $groupItems->count() }} Item)</span>
                                        </span>
                                    </div>
                                    <div class="divide-y divide-gray-100 text-xs">
                                        @foreach($groupItems as $item)
                                            @php
                                                $d = $detailsByItem->get($item->id);
                                                $val = $d ? $d->value : '-';
                                                $isNormal = $d ? $d->is_normal : true;
                                            @endphp
                                            <div class="p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-gray-50/50 transition-colors {{ $val !== '-' && (!$isNormal || $val === 'NG') ? 'bg-red-50/50' : '' }}">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-black text-gray-400 text-[10px] w-5">{{ $item->sort_order }}.</span>
                                                        <span class="font-bold text-gray-800 text-xs">{{ $item->item_name }}</span>
                                                    </div>
                                                    <div class="ml-7 text-[10px] text-gray-400 font-medium">
                                                        Standar: <span class="text-gray-600 font-semibold">{{ $item->standard }}</span> | Kriteria: <span class="text-gray-600 font-semibold">{{ $item->kriteria }}</span>
                                                    </div>
                                                </div>
                                                <div class="ml-7 md:ml-0">
                                                    @if($val === 'OK')
                                                        <span class="px-3.5 py-1 rounded-xl bg-emerald-600 text-white font-black text-xs inline-block">
                                                            ✓ OK
                                                        </span>
                                                    @elseif($val === '-')
                                                        <span class="px-3.5 py-1 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs inline-block border border-slate-200">
                                                            - Lewati
                                                        </span>
                                                    @elseif(!$isNormal || $val === 'NG')
                                                        <span class="px-3.5 py-1 rounded-xl bg-red-600 text-white font-black text-xs inline-block animate-pulse shadow-sm">
                                                            ⚠️ {{ $val }} {{ $item->unit }} (ABNORMAL)
                                                        </span>
                                                    @else
                                                        <span class="px-3.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-black text-xs inline-block">
                                                            ✓ {{ $val }} {{ $item->unit }} (Normal)
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>

                    <!-- Footer Modal -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end">
                        <button type="button" wire:click="closeDetailModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs rounded-xl transition-colors">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
