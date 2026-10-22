<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto space-y-6" x-data="spkManager()">
        
        <!-- Header & Sync Status Bar -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-800 flex items-center gap-2">
                    📋 Data Master & Log Perubahan SPK (SAP Sync)
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Monitoring data master SPK aktif serta riwayat perubahan quantity dan status dari SAP.
                </p>
                
                <!-- Last Sync Information -->
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    <span class="font-bold text-gray-400">Terakhir Diberbarui:</span>
                    @if($stats['last_sync'])
                        @php $lastSync = \Carbon\Carbon::parse($stats['last_sync'])->timezone('Asia/Jakarta'); @endphp
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 font-extrabold rounded-lg border border-indigo-100 shadow-2xs">
                            🕒 {{ $lastSync->format('d M Y H:i:s') }} WIB ({{ $lastSync->diffForHumans() }})
                        </span>
                    @else
                        <span class="text-gray-400 italic">Belum ada data sync</span>
                    @endif

                    @if($stats['last_sync_status'] === 'success')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold rounded-md text-[11px]">
                            🟢 Sync Sukses
                        </span>
                    @elseif($stats['last_sync_status'] === 'failed')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 font-bold rounded-md text-[11px]">
                            🔴 Sync Gagal
                        </span>
                    @endif
                </div>
            </div>

            <!-- Trigger Sync Button -->
            <button @click="triggerSyncNow()" :disabled="isSyncing"
                class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-extrabold px-6 py-3.5 rounded-xl shadow-md transition-all cursor-pointer">
                <svg x-show="!isSyncing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg x-show="isSyncing" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isSyncing ? 'SINKRONISASI BERJALAN...' : '🔄 SINKRONKAN SPK SEKARANG'"></span>
            </button>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-bold text-sm shadow-xs flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl font-bold text-sm shadow-xs flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif

        <!-- Summary Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xl">
                    📦
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Master SPK</span>
                    <span class="text-2xl font-black text-gray-800">{{ number_format($stats['total_master_spk']) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-xl">
                    🟢
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">SPK Baru</span>
                    <span class="text-2xl font-black text-emerald-600">{{ number_format($stats['total_new']) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-red-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-black text-xl">
                    🔴
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Qty Planned Berubah</span>
                    <span class="text-2xl font-black text-red-600">{{ number_format($stats['total_qty_planned'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-amber-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-xl">
                    🟡
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Qty Completed Berubah</span>
                    <span class="text-2xl font-black text-amber-600">{{ number_format($stats['total_qty_completed'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center font-black text-xl">
                    🚫
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">SPK Dihapus/Closed</span>
                    <span class="text-2xl font-black text-gray-700">{{ number_format($stats['total_removed']) }}</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <form method="GET" action="{{ route('spk.changes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Cari SPK / Item Code / Status:</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik No SPK, Item Code, Status..."
                        class="w-full border border-gray-300 rounded-xl text-xs p-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Change Type Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Filter Jenis Log:</label>
                    <select name="change_type" class="w-full border border-gray-300 rounded-xl text-xs p-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Perubahan Log</option>
                        <option value="NEW" {{ request('change_type') == 'NEW' ? 'selected' : '' }}>🟢 SPK BARU</option>
                        <option value="PLANNED_QTY_CHANGE" {{ request('change_type') == 'PLANNED_QTY_CHANGE' ? 'selected' : '' }}>🔴 PERUBAHAN QUANTITY PLANNED</option>
                        <option value="COMPLETED_QTY_CHANGE" {{ request('change_type') == 'COMPLETED_QTY_CHANGE' ? 'selected' : '' }}>🟡 PERUBAHAN QTY COMPLETED</option>
                        <option value="QTY_CHANGE" {{ request('change_type') == 'QTY_CHANGE' ? 'selected' : '' }}>⚖️ SEMUA PERUBAHAN QTY</option>
                        <option value="STATUS_CHANGE" {{ request('change_type') == 'STATUS_CHANGE' ? 'selected' : '' }}>🔵 PERUBAHAN STATUS</option>
                        <option value="REMOVED" {{ request('change_type') == 'REMOVED' ? 'selected' : '' }}>🔴 SPK DIHAPUS / CLOSED</option>
                    </select>
                </div>

                <!-- Batch Sync Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Filter Sesi Sync (Batch):</label>
                    <select name="batch_id" class="w-full border border-gray-300 rounded-xl text-xs p-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Semua Batch Sesi</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->sync_batch_id }}" {{ request('batch_id') == $b->sync_batch_id ? 'selected' : '' }}>
                                {{ $b->sync_batch_id }} ({{ \Carbon\Carbon::parse($b->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit / Reset Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition cursor-pointer">
                        🔍 Cari & Filter
                    </button>
                    <a href="{{ route('spk.changes.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Tab Navigation Switcher -->
        <div class="flex border-b border-gray-200 bg-white rounded-t-2xl px-4 pt-2 shadow-xs">
            <button @click="activeTab = 'master'"
                :class="activeTab === 'master' ? 'border-b-2 border-indigo-600 text-indigo-600 font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
                class="py-3 px-6 text-xs transition cursor-pointer flex items-center gap-2">
                <span>📦 Data Master SPK Aktif</span>
                <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full text-[10px]" x-text="{{ count($masterSpks) }}"></span>
            </button>
            <button @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-b-2 border-indigo-600 text-indigo-600 font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
                class="py-3 px-6 text-xs transition cursor-pointer flex items-center gap-2">
                <span>📜 Log Audit Perubahan SPK</span>
                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[10px]" x-text="{{ $logs->total() }}"></span>
            </button>
        </div>

        <!-- TAB 1: DATA MASTER SPK AKTIFF -->
        <div x-show="activeTab === 'master'" class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-gray-100 overflow-hidden">
            <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between text-xs">
                <span class="font-extrabold text-gray-700">Daftar Data SPK Master Hasil Sync SAP Terbaru:</span>
                <span class="text-gray-500 font-bold">Total {{ number_format($masterSpks->total()) }} SPK</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-gray-700">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-[9px] font-black border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">No. SPK</th>
                            <th class="py-3 px-4">Item Code</th>
                            <th class="py-3 px-4">Part Name</th>
                            <th class="py-3 px-4 text-center">Planned Qty</th>
                            <th class="py-3 px-4 text-center">Completed Qty</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Post Date</th>
                            <th class="py-3 px-4 text-center">Due Date</th>
                            <th class="py-3 px-4 text-center">Warehouse</th>
                            <th class="py-3 px-4 text-center">Riwayat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($masterSpks as $index => $spk)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="py-3 px-4 text-gray-400 font-bold">{{ $masterSpks->firstItem() + $index }}</td>
                                <td class="py-3 px-4 font-black text-gray-800">
                                    <button type="button" @click="fetchHistory('{{ $spk->spk_number }}')" class="hover:text-indigo-600 hover:underline cursor-pointer">
                                        {{ $spk->spk_number }}
                                    </button>
                                </td>
                                <td class="py-3 px-4 font-bold text-gray-800">{{ $spk->item_code }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-600">{{ optional($spk->masterItem)->item_name ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-black text-indigo-700 text-sm">{{ number_format($spk->planned_quantity) }}</td>
                                <td class="py-3 px-4 text-center font-bold text-emerald-600">{{ number_format($spk->completed_quantity) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase bg-gray-100 text-gray-800">
                                        {{ $spk->production_status ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-semibold text-gray-500">{{ $spk->post_date ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-semibold text-gray-500">{{ $spk->due_date ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-bold text-gray-700">{{ $spk->warehouse ?? '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" @click="fetchHistory('{{ $spk->spk_number }}')" class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer">
                                        📜 Riwayat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-8 text-center text-sm font-bold text-gray-400">
                                    Belum ada data master SPK. Klik "SINKRONKAN SPK SEKARANG" untuk menarik data dari SAP.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($masterSpks->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    {{ $masterSpks->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 2: LOG AUDIT PERUBAHAN SPK -->
        <div x-show="activeTab === 'logs'" class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-gray-700">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-[9px] font-black border-b border-gray-200">
                        <tr>
                            <th class="py-3.5 px-5">Waktu Sesi Sync</th>
                            <th class="py-3.5 px-5">No. SPK</th>
                            <th class="py-3.5 px-5">Item Code</th>
                            <th class="py-3.5 px-5 text-center">Jenis Perubahan</th>
                            <th class="py-3.5 px-5 text-center">Planned Qty (Lama ➔ Baru)</th>
                            <th class="py-3.5 px-5 text-center">Completed Qty</th>
                            <th class="py-3.5 px-5 text-center">Status SAP</th>
                            <th class="py-3.5 px-5 text-center">Opsi / Timeline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            @php
                                $plannedDiff = ($log->new_planned_qty !== null && $log->old_planned_qty !== null) 
                                    ? ($log->new_planned_qty - $log->old_planned_qty) 
                                    : null;
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-gray-600">
                                    {{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}
                                    <span class="block text-[9px] text-gray-400 font-mono">{{ $log->sync_batch_id }}</span>
                                </td>

                                <td class="py-3.5 px-5 font-black text-gray-800">
                                    <button type="button" @click="fetchHistory('{{ $log->spk_number }}')" class="hover:text-indigo-600 hover:underline cursor-pointer">
                                        {{ $log->spk_number }}
                                    </button>
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="font-bold text-gray-800 block">{{ $log->item_code ?? '-' }}</span>
                                    <span class="text-[10px] text-gray-500 block">{{ optional($log->masterItem)->item_name ?? '-' }}</span>
                                </td>

                                <td class="py-3.5 px-5 text-center">
                                    @if($log->change_type === 'NEW')
                                        <span class="inline-block px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 font-extrabold rounded-lg text-[10px] tracking-wide">
                                            🟢 SPK BARU
                                        </span>
                                    @elseif($log->change_type === 'QTY_CHANGE')
                                        @php
                                            $isPlannedChange = ($log->old_planned_qty !== null && $log->new_planned_qty !== null && $log->old_planned_qty !== $log->new_planned_qty);
                                            $isCompletedChange = ($log->old_completed_qty !== null && $log->new_completed_qty !== null && $log->old_completed_qty !== $log->new_completed_qty);
                                        @endphp
                                        @if($isPlannedChange && $isCompletedChange)
                                            <div class="flex flex-col gap-1 items-center">
                                                <span class="inline-block px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-lg text-[9px] tracking-wide">
                                                    🔴 PERUBAHAN QUANTITY PLANNED
                                                </span>
                                                <span class="inline-block px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 font-extrabold rounded-lg text-[9px] tracking-wide">
                                                    🟡 PERUBAHAN QTY COMPLETED
                                                </span>
                                            </div>
                                        @elseif($isPlannedChange)
                                            <span class="inline-block px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-lg text-[10px] tracking-wide">
                                                🔴 PERUBAHAN QUANTITY PLANNED
                                            </span>
                                        @else
                                            <span class="inline-block px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 font-extrabold rounded-lg text-[10px] tracking-wide">
                                                🟡 PERUBAHAN QTY COMPLETED
                                            </span>
                                        @endif
                                    @elseif($log->change_type === 'STATUS_CHANGE')
                                        <span class="inline-block px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 font-extrabold rounded-lg text-[10px] tracking-wide">
                                            🔵 STATUS CHANGED
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-lg text-[10px] tracking-wide">
                                            🔴 SPK DIHAPUS
                                        </span>
                                    @endif
                                </td>

                                <!-- Planned Qty Comparison -->
                                <td class="py-3.5 px-5 text-center font-bold">
                                    @if($log->change_type === 'NEW')
                                        <span class="text-emerald-600 font-black text-sm">{{ number_format($log->new_planned_qty ?? 0) }}</span>
                                    @elseif($log->change_type === 'REMOVED')
                                        <span class="text-gray-400 line-through">{{ number_format($log->old_planned_qty ?? 0) }}</span>
                                        <span class="text-red-500 block text-[10px] font-bold">Closed</span>
                                    @else
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="text-gray-400 font-normal">{{ number_format($log->old_planned_qty ?? 0) }}</span>
                                            <span class="text-gray-400">➔</span>
                                            <span class="text-indigo-700 font-black text-sm">{{ number_format($log->new_planned_qty ?? 0) }}</span>
                                            
                                            @if($plannedDiff !== null && $plannedDiff != 0)
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black {{ $plannedDiff > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $plannedDiff > 0 ? '+'.$plannedDiff : $plannedDiff }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Completed Qty Comparison -->
                                <td class="py-3.5 px-5 text-center font-semibold text-gray-600">
                                    @if($log->old_completed_qty !== null && $log->new_completed_qty !== null && $log->old_completed_qty != $log->new_completed_qty)
                                        <span>{{ number_format($log->old_completed_qty) }} ➔ {{ number_format($log->new_completed_qty) }}</span>
                                    @else
                                        <span>{{ number_format($log->new_completed_qty ?? $log->old_completed_qty ?? 0) }}</span>
                                    @endif
                                </td>

                                <!-- Status SAP -->
                                <td class="py-3.5 px-5 text-center font-bold text-gray-700">
                                    @if($log->old_status && $log->new_status && $log->old_status !== $log->new_status)
                                        <span class="text-gray-400 line-through text-[10px]">{{ $log->old_status }}</span>
                                        <span class="block text-indigo-700 font-extrabold">{{ $log->new_status }}</span>
                                    @else
                                        <span>{{ $log->new_status ?? $log->old_status ?? '-' }}</span>
                                    @endif
                                </td>

                                <!-- Action / History Modal Button -->
                                <td class="py-3.5 px-5 text-center">
                                    <button type="button" @click="fetchHistory('{{ $log->spk_number }}')" class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
                                        📜 Riwayat SPK
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-sm font-bold text-gray-400">
                                    Belum ada log perubahan SPK yang tercatat. Klik tombol "SINKRONKAN SPK SEKARANG" di atas untuk melakukan sinkronisasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Timeline Riwayat SPK -->
        <div x-show="showHistoryModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4" style="display: none;">
            <div @click.away="showHistoryModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden border border-gray-100 transform transition-all">
                
                <!-- Modal Header -->
                <div class="bg-gray-900 text-white p-5 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black flex items-center gap-2">
                            📜 Riwayat Perubahan SPK: <span class="text-indigo-400 font-mono" x-text="activeSpk"></span>
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">Audit trail perubahan planned quantity & status kronologis dari SAP.</p>
                    </div>
                    <button type="button" @click="showHistoryModal = false" class="text-gray-400 hover:text-white font-bold text-xl p-1 cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Modal Body Timeline -->
                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <div x-show="loadingHistory" class="py-12 text-center text-indigo-600 font-bold">
                        <svg class="w-8 h-8 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat riwayat perubahan...
                    </div>

                    <div x-show="!loadingHistory && historyList.length === 0" class="py-8 text-center text-gray-400 font-bold">
                        Tidak ada riwayat perubahan tambahan untuk SPK ini.
                    </div>

                    <div x-show="!loadingHistory && historyList.length > 0" class="relative border-l-2 border-indigo-200 ml-4 space-y-6">
                        <template x-for="(item, index) in historyList" :key="item.id">
                            <div class="relative pl-6">
                                <div class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-white shadow-2xs"
                                     :class="getTimelineDotClass(item)">
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-2xs">
                                    <div class="flex items-center justify-between mb-1.5 gap-2">
                                        <span class="text-xs font-black text-gray-700" x-text="formatDate(item.created_at)"></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wide"
                                              :class="getTimelineBadgeClass(item)"
                                              x-text="getTimelineBadgeText(item)">
                                        </span>
                                    </div>

                                    <div class="text-xs font-semibold text-gray-800 space-y-1">
                                        <div x-show="item.change_type === 'NEW'">
                                            SPK baru pertama kali dirilis dengan Planned Target: <span class="font-black text-emerald-600 text-sm" x-text="item.new_planned_qty"></span>
                                        </div>
                                        <div x-show="item.change_type === 'QTY_CHANGE'" class="space-y-1">
                                            <template x-if="item.old_planned_qty !== null && item.new_planned_qty !== null && item.old_planned_qty !== item.new_planned_qty">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span>🎯 Planned Target:</span>
                                                    <span class="font-bold text-gray-500" x-text="item.old_planned_qty"></span>
                                                    <span>➔</span>
                                                    <span class="font-black text-indigo-700 text-sm" x-text="item.new_planned_qty"></span>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black"
                                                          :class="(item.new_planned_qty - item.old_planned_qty) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                                          x-text="((item.new_planned_qty - item.old_planned_qty) > 0 ? '+' : '') + (item.new_planned_qty - item.old_planned_qty)">
                                                    </span>
                                                </div>
                                            </template>
                                            
                                            <template x-if="item.old_completed_qty !== null && item.new_completed_qty !== null && item.old_completed_qty !== item.new_completed_qty">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span>✅ Completed Qty:</span>
                                                    <span class="font-bold text-gray-500" x-text="item.old_completed_qty"></span>
                                                    <span>➔</span>
                                                    <span class="font-black text-indigo-700 text-sm" x-text="item.new_completed_qty"></span>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black"
                                                          :class="(item.new_completed_qty - item.old_completed_qty) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                                          x-text="((item.new_completed_qty - item.old_completed_qty) > 0 ? '+' : '') + (item.new_completed_qty - item.old_completed_qty)">
                                                    </span>
                                                </div>
                                            </template>
                                        </div>
                                        <div x-show="item.change_type === 'STATUS_CHANGE'">
                                            Status produksi diubah dari <span class="font-bold" x-text="item.old_status"></span> ➔ <span class="font-bold text-indigo-600" x-text="item.new_status"></span>
                                        </div>
                                        <div x-show="item.change_type === 'REMOVED'">
                                            SPK ditutup / tidak ada di sinkronisasi SAP terbaru. Target Terakhir: <span class="font-bold" x-text="item.old_planned_qty"></span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono mt-2">
                                        Batch: <span x-text="item.sync_batch_id"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 p-4 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="showHistoryModal = false" class="bg-gray-800 hover:bg-gray-900 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Alpine.js for Manager -->
    <script>
        function spkManager() {
            return {
                activeTab: '{{ request()->has("log_page") ? "logs" : "master" }}',
                isSyncing: false,
                showHistoryModal: false,
                loadingHistory: false,
                activeSpk: '',
                historyList: [],

                triggerSyncNow() {
                    if (this.isSyncing) return;
                    if (!confirm("Apakah Anda yakin ingin melakukan sinkronisasi data SPK dari SAP sekarang?")) return;

                    this.isSyncing = true;
                    $.ajax({
                        url: "{{ route('spk.changes.sync') }}",
                        type: "POST",
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: (res) => {
                            this.isSyncing = false;
                            alert(res.message || "Sinkronisasi SPK berhasil!");
                            location.reload();
                        },
                        error: (xhr) => {
                            this.isSyncing = false;
                            alert("Gagal melakukan sinkronisasi SPK. Periksa koneksi API SAP.");
                        }
                    });
                },

                fetchHistory(spkNumber) {
                    this.activeSpk = spkNumber;
                    this.showHistoryModal = true;
                    this.loadingHistory = true;
                    this.historyList = [];

                    $.ajax({
                        url: "{{ url('/spk-changes/history') }}/" + encodeURIComponent(spkNumber),
                        type: "GET",
                        success: (res) => {
                            this.loadingHistory = false;
                            if (res.success) {
                                this.historyList = res.history;
                            }
                        },
                        error: () => {
                            this.loadingHistory = false;
                            alert("Gagal mengambil riwayat SPK.");
                        }
                    });
                },

                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('id-ID', { 
                        day: '2-digit', 
                        month: 'short', 
                        year: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        second: '2-digit',
                        timeZone: 'Asia/Jakarta'
                    });
                },

                getTimelineDotClass(item) {
                    if (item.change_type === 'NEW') return 'bg-emerald-500';
                    if (item.change_type === 'REMOVED') return 'bg-red-500';
                    if (item.change_type === 'STATUS_CHANGE') return 'bg-blue-500';
                    if (item.change_type === 'QTY_CHANGE') {
                        const hasPlanned = item.old_planned_qty !== null && item.new_planned_qty !== null && Number(item.old_planned_qty) !== Number(item.new_planned_qty);
                        return hasPlanned ? 'bg-red-500' : 'bg-amber-500';
                    }
                    return 'bg-gray-400';
                },

                getTimelineBadgeClass(item) {
                    if (item.change_type === 'NEW') return 'bg-emerald-100 text-emerald-800 border border-emerald-200 font-extrabold';
                    if (item.change_type === 'REMOVED') return 'bg-red-100 text-red-800 border border-red-200 font-extrabold';
                    if (item.change_type === 'STATUS_CHANGE') return 'bg-blue-100 text-blue-800 border border-blue-200 font-extrabold';
                    if (item.change_type === 'QTY_CHANGE') {
                        const hasPlanned = item.old_planned_qty !== null && item.new_planned_qty !== null && Number(item.old_planned_qty) !== Number(item.new_planned_qty);
                        return hasPlanned 
                            ? 'bg-red-50 text-red-700 border border-red-200 font-extrabold shadow-2xs' 
                            : 'bg-amber-50 text-amber-700 border border-amber-200 font-extrabold shadow-2xs';
                    }
                    return 'bg-gray-100 text-gray-800 font-bold';
                },

                getTimelineBadgeText(item) {
                    if (item.change_type === 'NEW') return '🟢 SPK BARU';
                    if (item.change_type === 'REMOVED') return '🔴 SPK DIHAPUS';
                    if (item.change_type === 'STATUS_CHANGE') return '🔵 STATUS CHANGED';
                    if (item.change_type === 'QTY_CHANGE') {
                        const hasPlanned = item.old_planned_qty !== null && item.new_planned_qty !== null && Number(item.old_planned_qty) !== Number(item.new_planned_qty);
                        const hasCompleted = item.old_completed_qty !== null && item.new_completed_qty !== null && Number(item.old_completed_qty) !== Number(item.new_completed_qty);
                        if (hasPlanned && hasCompleted) return '🔴 PERUBAHAN QTY PLANNED & COMPLETED';
                        if (hasPlanned) return '🔴 PERUBAHAN QUANTITY PLANNED';
                        return '🟡 PERUBAHAN QTY COMPLETED';
                    }
                    return item.change_type;
                }
            };
        }
    </script>
</x-app-layout>
