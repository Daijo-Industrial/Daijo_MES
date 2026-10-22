<x-dashboard-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Custom Barcode Print History & Logs
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Action Buttons -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl p-6 border border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <span>📋</span> Custom Barcode Print History
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">Rekap dan log aktivitas pencetakan label barcode custom per branch (Karawang K- & KBN Biasa).</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('barcode.custom.form') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-lg shadow transition">
                        <span>➕</span> Generate Label Baru
                    </a>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Print Jobs</div>
                    <div class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($stats['total_print_jobs'] ?? 0) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Sesi print ({{ ($branch ?? 'all') === 'karawang' ? 'Karawang' : (($branch ?? 'all') === 'kbn' ? 'KBN' : 'Semua Branch') }})</div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Label Dicetak</div>
                    <div class="text-2xl font-extrabold text-indigo-600 mt-1">{{ number_format($stats['total_labels_printed'] ?? 0) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Stiker barcode dicetak</div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Print Hari Ini</div>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ number_format($stats['today_print_jobs'] ?? 0) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Sesi print hari ini</div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-md border border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Label Hari Ini</div>
                    <div class="text-2xl font-extrabold text-amber-600 mt-1">{{ number_format($stats['today_labels_printed'] ?? 0) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Stiker dicetak hari ini</div>
                </div>
            </div>

            <!-- Branch Switcher Tabs -->
            <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex items-center gap-2 overflow-x-auto">
                <a href="{{ route('barcode.custom.logs', array_merge(request()->except('branch', 'page'), ['branch' => 'all'])) }}"
                   class="px-4 py-2.5 rounded-lg text-xs sm:text-sm font-extrabold transition whitespace-nowrap flex items-center gap-2 {{ ($branch ?? 'all') === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>🌐 Semua Branch</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ ($branch ?? 'all') === 'all' ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-200 text-slate-700' }}">
                        {{ number_format($branchCounts['all'] ?? 0) }}
                    </span>
                </a>
                <a href="{{ route('barcode.custom.logs', array_merge(request()->except('branch', 'page'), ['branch' => 'karawang'])) }}"
                   class="px-4 py-2.5 rounded-lg text-xs sm:text-sm font-extrabold transition whitespace-nowrap flex items-center gap-2 {{ ($branch ?? 'all') === 'karawang' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>🏭 Karawang (Item K-)</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ ($branch ?? 'all') === 'karawang' ? 'bg-cyan-800 text-cyan-100' : 'bg-cyan-100 text-cyan-800' }}">
                        {{ number_format($branchCounts['karawang'] ?? 0) }}
                    </span>
                </a>
                <a href="{{ route('barcode.custom.logs', array_merge(request()->except('branch', 'page'), ['branch' => 'kbn'])) }}"
                   class="px-4 py-2.5 rounded-lg text-xs sm:text-sm font-extrabold transition whitespace-nowrap flex items-center gap-2 {{ ($branch ?? 'all') === 'kbn' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    <span>🏢 KBN / Reguler (Item Biasa)</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ ($branch ?? 'all') === 'kbn' ? 'bg-purple-800 text-purple-100' : 'bg-purple-100 text-purple-800' }}">
                        {{ number_format($branchCounts['kbn'] ?? 0) }}
                    </span>
                </a>
            </div>

            <!-- Filter Card -->
            <div class="bg-white overflow-hidden shadow-md sm:rounded-xl p-6 border border-slate-100">
                <form action="{{ route('barcode.custom.logs') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="branch" value="{{ $branch ?? 'all' }}">

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pencarian (Item, SPK, User, Customer, Remark)</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2 rounded-lg text-sm transition shadow-sm">
                            🔍 Filter
                        </button>
                        <a href="{{ route('barcode.custom.logs', ['branch' => $branch ?? 'all']) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-6 py-2 rounded-lg text-sm transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl p-6 border border-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Waktu Print</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Branch</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">User</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Item & SPK</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Label Seq / Total</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Qty/Box</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Shift / WH / Prod Date</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Type</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Remark / Catatan</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-600 uppercase text-xs">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($logs as $log)
                                @php
                                    $isKarawang = str_starts_with($log->item_code, 'K-');
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <div class="font-medium text-slate-900">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                        <div class="text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($isKarawang)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black bg-cyan-100 text-cyan-800 border border-cyan-200">
                                                <span>🏭</span> Karawang
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black bg-purple-100 text-purple-800 border border-purple-200">
                                                <span>🏢</span> KBN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $log->user_name ?? ($log->user->name ?? 'Guest') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <div class="font-bold text-indigo-700 flex items-center gap-1.5 flex-wrap">
                                            <span>{{ $log->item_code }}</span>
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $log->item_name }}</div>
                                        <div class="text-xs text-slate-600 font-medium mt-0.5">SPK: <span class="font-mono font-bold">{{ $log->spk_number }}</span></div>
                                        @if($log->customer && $log->customer !== '-')
                                             <div class="text-xs text-slate-400">Cust: {{ $log->customer }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <div class="font-semibold text-slate-800">
                                            Label #{{ $log->start_label }} - #{{ $log->end_label }}
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 mt-0.5">
                                            {{ $log->total_labels }} label diprint
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 font-semibold">
                                        {{ number_format($log->quantity) }}
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 text-xs">
                                        <div>Shift <span class="font-bold">{{ $log->shift }}</span> ({{ $log->warehouse }})</div>
                                        <div class="text-slate-400">Prod: {{ $log->prod_date ? \Carbon\Carbon::parse($log->prod_date)->format('d/m/Y') : '-' }}</div>
                                        @if($log->operator && $log->operator !== '-')
                                            <div class="text-slate-400">Op: {{ $log->operator }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs space-y-1">
                                        <div>
                                            @if(($log->barcode_type ?? 'default') === 'sharp')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                    SHARP
                                                </span>
                                            @elseif(($log->barcode_type ?? 'default') === 'yanfeng')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    YANFENG
                                                </span>
                                            @elseif(($log->barcode_type ?? 'default') === 'itsp')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    ITSP
                                                </span>
                                            @elseif(($log->barcode_type ?? 'default') === 'vinfast')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                    VINFAST
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Standard
                                                </span>
                                            @endif
                                        </div>
                                        @if($log->is_trial)
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                                    TRIAL
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 text-xs max-w-xs break-words">
                                        @if($log->remark)
                                            <span class="text-slate-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded inline-block">
                                                {{ $log->remark }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-center text-xs">
                                        <a href="{{ route('barcode.custom.reprint', $log->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-transparent rounded-lg text-xs font-bold transition duration-150 shadow-sm">
                                            <span>🖨️</span> Print Ulang
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                        Tidak ada data riwayat cetak barcode yang sesuai dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-dashboard-layout>
