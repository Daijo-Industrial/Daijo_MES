<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="packagingDashboard()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header Title & Subtext --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>📦</span> Dashboard & Saldo Kemasan
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Monitoring saldo box, posisi unit di pabrik (Jakarta / Karawang), dan saldo tertahan di customer.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('inandout.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        Scan In/Out Box
                    </a>
                    <a href="{{ route('barcodeindex') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 transition">
                        <span>🏷️</span> Cetak Barcode
                    </a>
                </div>
            </div>

            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                {{-- Card 1: Total Model Box --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Tipe Box</span>
                        <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">🏷️</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ number_format($kpi['total_types']) }}</span>
                        <span class="text-xs text-gray-500">model</span>
                    </div>
                    <div class="mt-1 text-xs text-indigo-600 font-medium">Terdaftar di master</div>
                </div>

                {{-- Card 2: Daijo Jakarta --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Jakarta (Cakung)</span>
                        <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">🏭</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($kpi['total_daijo']) }}</span>
                        <span class="text-xs text-gray-500">pcs</span>
                    </div>
                    <div class="mt-1 text-xs text-emerald-600 font-medium">Stok siap pakai</div>
                </div>

                {{-- Card 3: Karawang --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Karawang (KIIC)</span>
                        <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">🏗️</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-blue-600">{{ number_format($kpi['total_kiic']) }}</span>
                        <span class="text-xs text-gray-500">pcs</span>
                    </div>
                    <div class="mt-1 text-xs text-blue-600 font-medium">Plant Karawang</div>
                </div>

                {{-- Card 4: Customer Outstanding --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Di Customer</span>
                        <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg">🚚</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-amber-600">{{ number_format($kpi['total_customer']) }}</span>
                        <span class="text-xs text-gray-500">pcs</span>
                    </div>
                    <div class="mt-1 text-xs text-amber-600 font-medium">Tertahan di customer</div>
                </div>

                {{-- Card 5: Grand Total Fisik --}}
                <div class="col-span-2 sm:col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Terlacak</span>
                        <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg">📊</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-purple-700">{{ number_format($kpi['total_physical']) }}</span>
                        <span class="text-xs text-gray-500">pcs</span>
                    </div>
                    <div class="mt-1 text-xs text-purple-600 font-medium">Total tercatat sistem</div>
                </div>
            </div>

            {{-- Navigation Tabs --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="border-b border-gray-200 px-4 sm:px-6">
                    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                        <button type="button" @click="activeTab = 'summary'"
                            :class="activeTab === 'summary' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>📊</span> Rekap Saldo Per Tipe Box
                            <span class="bg-gray-100 text-gray-700 rounded-full text-xs px-2 py-0.5" x-text="filteredSummary.length"></span>
                        </button>

                        <button type="button" @click="activeTab = 'customer'"
                            :class="activeTab === 'customer' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>🏢</span> Rekonsiliasi Saldo Customer
                            <span class="bg-amber-100 text-amber-800 rounded-full text-xs px-2 py-0.5">{{ count($customerBalances) }}</span>
                        </button>

                        <button type="button" @click="activeTab = 'realtime'"
                            :class="activeTab === 'realtime' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>📍</span> Posisi Detail Per Label Box
                            <span class="bg-blue-100 text-blue-800 rounded-full text-xs px-2 py-0.5">{{ count($latestPositions) }}</span>
                        </button>
                    </nav>
                </div>

                {{-- TAB 1: REKAP SALDO PER TIPE BOX --}}
                <div x-show="activeTab === 'summary'" class="p-4 sm:p-6">
                    {{-- Filter & Search Toolbar --}}
                    <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="relative w-full sm:w-80">
                            <input type="text" x-model="searchQuery" placeholder="Cari Part No atau Nama Box..."
                                class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end text-xs text-gray-500">
                            <span>Filter Saldo:</span>
                            <button type="button" @click="stockFilter = 'all'" :class="stockFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium transition">Semua</button>
                            <button type="button" @click="stockFilter = 'has_stock'" :class="stockFilter === 'has_stock' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium transition">Ada Fisik (>0)</button>
                            <button type="button" @click="stockFilter = 'at_customer'" :class="stockFilter === 'at_customer' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium transition">Di Customer (>0)</button>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700 font-semibold text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Part No Box</th>
                                    <th class="px-4 py-3 text-left">Nama / Spesifikasi Kemasan</th>
                                    <th class="px-3 py-3 text-center">Jakarta (Cakung)</th>
                                    <th class="px-3 py-3 text-center">Karawang (KIIC)</th>
                                    <th class="px-3 py-3 text-center">Di Customer</th>
                                    <th class="px-3 py-3 text-center">Total Terlacak</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="item in paginatedSummary" :key="item.part_no">
                                    <tr class="hover:bg-blue-50/50 transition">
                                        <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap" x-text="item.part_no"></td>
                                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate" :title="item.part_name" x-text="item.part_name || '-'"></td>
                                        <td class="px-3 py-3 text-center font-bold" :class="item.quantity_daijo > 0 ? 'text-emerald-700 bg-emerald-50/40' : 'text-gray-400'" x-text="item.quantity_daijo"></td>
                                        <td class="px-3 py-3 text-center font-bold" :class="item.quantity_kiic > 0 ? 'text-blue-700 bg-blue-50/40' : 'text-gray-400'" x-text="item.quantity_kiic"></td>
                                        <td class="px-3 py-3 text-center font-bold" :class="item.quantity_customer > 0 ? 'text-amber-700 bg-amber-50/40' : 'text-gray-400'" x-text="item.quantity_customer"></td>
                                        <td class="px-3 py-3 text-center font-extrabold text-gray-900" x-text="item.total"></td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <button type="button" @click="openBoxDetail(item)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-md border border-blue-200 transition">
                                                <span>🔍</span> Detail Box
                                                <span class="text-[10px] text-blue-600 font-normal" x-show="item.details.length > 0" x-text="'(' + item.details.length + ')'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredSummary.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-4xl mb-2">📦</span>
                                            <p class="font-medium">Tidak ada tipe kemasan yang sesuai dengan filter pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary Pagination Bar --}}
                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600" x-show="filteredSummary.length > 0">
                        <div>
                            Menampilkan <span class="font-semibold" x-text="summaryStartIndex + 1"></span> - <span class="font-semibold" x-text="Math.min(summaryStartIndex + summaryPerPage, filteredSummary.length)"></span> dari <span class="font-semibold" x-text="filteredSummary.length"></span> tipe box
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="summaryPage = Math.max(1, summaryPage - 1)" :disabled="summaryPage === 1"
                                class="px-3 py-1.5 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                &laquo; Sebelumnya
                            </button>
                            <span class="px-3 py-1.5 font-bold text-gray-800" x-text="summaryPage + ' / ' + maxSummaryPage"></span>
                            <button type="button" @click="summaryPage = Math.min(maxSummaryPage, summaryPage + 1)" :disabled="summaryPage >= maxSummaryPage"
                                class="px-3 py-1.5 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                Selanjutnya &raquo;
                            </button>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: REKONSILIASI SALDO CUSTOMER --}}
                <div x-show="activeTab === 'customer'" class="p-4 sm:p-6" style="display: none;">
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Rekapitulasi Saldo Kemasan di Customer</h3>
                            <p class="text-xs text-gray-500">Daftar customer yang saat ini masih memegang/meminjam box PT Daijo Industrial.</p>
                        </div>
                    </div>

                    @if(count($customerBalances) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($customerBalances as $cust)
                                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="p-2 bg-amber-50 text-amber-700 rounded-lg text-lg">🏢</span>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-sm">{{ $cust['customer'] }}</h4>
                                                <span class="text-xs text-gray-400">{{ count($cust['parts']) }} jenis part box</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-2xl font-black text-amber-600">{{ number_format($cust['total_boxes']) }}</span>
                                            <span class="block text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Total Box</span>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-100">
                                        <div class="text-xs font-semibold text-gray-700 mb-2">Rincian Part Kemasan:</div>
                                        <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                                            @foreach($cust['parts'] as $pNo => $pQty)
                                                <div class="flex items-center justify-between text-xs bg-gray-50 px-2 py-1.5 rounded">
                                                    <span class="font-medium text-gray-800">{{ $pNo }}</span>
                                                    <span class="font-bold text-amber-700 bg-amber-100/60 px-2 py-0.5 rounded">{{ $pQty }} pcs</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <span class="text-4xl mb-2 block">🚚</span>
                            <h4 class="font-bold text-gray-800 text-sm">Belum Ada Box di Customer</h4>
                            <p class="text-xs text-gray-500 mt-1">Seluruh box saat ini berada di pabrik Daijo Jakarta atau Karawang.</p>
                        </div>
                    @endif
                </div>

                {{-- TAB 3: POSISI DETAIL PER LABEL BOX --}}
                <div x-show="activeTab === 'realtime'" class="p-4 sm:p-6" style="display: none;">
                    {{-- Filter Form --}}
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" x-model="realtimeSearch" placeholder="Cari Part No, Label, Customer, Dokumen..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                        <select x-model="realtimePosFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Posisi</option>
                            <option value="Jakarta">Jakarta</option>
                            <option value="Karawang">Karawang</option>
                            <option value="Customer">Customer</option>
                        </select>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700 font-semibold text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Part No</th>
                                    <th class="px-4 py-3 text-center">No Label</th>
                                    <th class="px-4 py-3 text-left">Posisi Terakhir</th>
                                    <th class="px-4 py-3 text-left">Customer Terkait</th>
                                    <th class="px-4 py-3 text-left">Waktu Scan</th>
                                    <th class="px-4 py-3 text-left">No Dokumen</th>
                                    <th class="px-4 py-3 text-center">Riwayat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="row in paginatedRealtime" :key="row.partNo + '-' + row.label">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-2.5 font-semibold text-gray-900" x-text="row.partNo"></td>
                                        <td class="px-4 py-2.5 text-center font-mono font-bold text-blue-600" x-text="'#' + row.label"></td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': row.position.toLowerCase() === 'jakarta',
                                                    'bg-blue-100 text-blue-800': row.position.toLowerCase() === 'karawang',
                                                    'bg-amber-100 text-amber-800': row.position.toLowerCase().includes('customer')
                                                }" x-text="row.position"></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-700 text-xs" x-text="row.customer || '-'"></td>
                                        <td class="px-4 py-2.5 text-gray-600 text-xs font-mono" x-text="row.scantime"></td>
                                        <td class="px-4 py-2.5 text-gray-500 text-xs font-mono" x-text="row.noDokumen || '-'"></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button" @click="fetchHistory(row.partNo, row.label)"
                                                class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs transition">
                                                Riwayat
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredRealtime.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        Tidak ada catatan posisi box yang cocok dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Realtime Pagination --}}
                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600" x-show="filteredRealtime.length > 0">
                        <div>
                            Menampilkan <span class="font-semibold" x-text="realtimeStartIndex + 1"></span> - <span class="font-semibold" x-text="Math.min(realtimeStartIndex + realtimePerPage, filteredRealtime.length)"></span> dari <span class="font-semibold" x-text="filteredRealtime.length"></span> unit box
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="realtimePage = Math.max(1, realtimePage - 1)" :disabled="realtimePage === 1"
                                class="px-3 py-1.5 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-40">
                                &laquo; Sebelumnya
                            </button>
                            <span class="px-3 py-1.5 font-bold text-gray-800" x-text="realtimePage + ' / ' + maxRealtimePage"></span>
                            <button type="button" @click="realtimePage = Math.min(maxRealtimePage, realtimePage + 1)" :disabled="realtimePage >= maxRealtimePage"
                                class="px-3 py-1.5 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-40">
                                Selanjutnya &raquo;
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL DETAIL BOX (PER PART NUMBER) --}}
        <div x-show="detailModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" @click="detailModalOpen = false"></div>

                <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:max-w-3xl sm:w-full my-8 text-left z-10">
                    {{-- Header --}}
                    <div class="bg-gray-900 px-6 py-4 flex items-center justify-between text-white">
                        <div>
                            <h3 class="text-lg font-bold flex items-center gap-2">
                                <span>📦</span> Detail Serial Box: <span class="text-blue-400 font-mono" x-text="selectedPart?.part_no"></span>
                            </h3>
                            <p class="text-xs text-gray-300" x-text="selectedPart?.part_name || '-'"></p>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        {{-- Stats Chips --}}
                        <div class="grid grid-cols-4 gap-2 mb-4 text-center">
                            <div class="bg-gray-50 p-2 rounded-lg border border-gray-200">
                                <div class="text-[10px] text-gray-500 uppercase font-bold">Total Unit</div>
                                <div class="text-lg font-extrabold text-gray-800" x-text="selectedPart?.total || 0"></div>
                            </div>
                            <div class="bg-emerald-50 p-2 rounded-lg border border-emerald-200">
                                <div class="text-[10px] text-emerald-600 uppercase font-bold">Jakarta</div>
                                <div class="text-lg font-extrabold text-emerald-700" x-text="selectedPart?.quantity_daijo || 0"></div>
                            </div>
                            <div class="bg-blue-50 p-2 rounded-lg border border-blue-200">
                                <div class="text-[10px] text-blue-600 uppercase font-bold">Karawang</div>
                                <div class="text-lg font-extrabold text-blue-700" x-text="selectedPart?.quantity_kiic || 0"></div>
                            </div>
                            <div class="bg-amber-50 p-2 rounded-lg border border-amber-200">
                                <div class="text-[10px] text-amber-600 uppercase font-bold">Customer</div>
                                <div class="text-lg font-extrabold text-amber-700" x-text="selectedPart?.quantity_customer || 0"></div>
                            </div>
                        </div>

                        {{-- Labels Table --}}
                        <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50 font-semibold text-gray-700 uppercase">
                                    <tr>
                                        <th class="px-3 py-2 text-center">Label</th>
                                        <th class="px-3 py-2 text-left">Posisi Saat Ini</th>
                                        <th class="px-3 py-2 text-left">Customer</th>
                                        <th class="px-3 py-2 text-left">Transaksi Terakhir</th>
                                        <th class="px-3 py-2 text-center">Riwayat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <template x-for="det in selectedPart?.details || []" :key="det.label">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-center font-mono font-bold text-blue-600" x-text="'#' + det.label"></td>
                                            <td class="px-3 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-800': det.position.toLowerCase() === 'jakarta',
                                                        'bg-blue-100 text-blue-800': det.position.toLowerCase() === 'karawang',
                                                        'bg-amber-100 text-amber-800': det.position.toLowerCase().includes('customer')
                                                    }" x-text="det.position"></span>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700" x-text="det.customer || '-'"></td>
                                            <td class="px-3 py-2 text-gray-500 font-mono" x-text="det.last_transaction"></td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="fetchHistory(selectedPart.part_no, det.label)"
                                                    class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded font-semibold transition">
                                                    Lihat
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="!selectedPart || selectedPart.details.length === 0">
                                        <td colspan="5" class="px-3 py-6 text-center text-gray-400">
                                            Belum ada log scan tercatat untuk tipe box ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 px-6 py-3 flex justify-end">
                        <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-lg transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL RIWAYAT SCAN (HISTORY) --}}
        <div x-show="historyModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" @click="historyModalOpen = false"></div>

                <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full my-8 text-left z-10">
                    <div class="bg-blue-600 px-6 py-4 flex items-center justify-between text-white">
                        <div>
                            <h3 class="text-base font-bold">📜 Riwayat Perjalanan Box</h3>
                            <p class="text-xs text-blue-100 font-mono" x-text="historyPartNo + ' (Label #' + historyLabelNo + ')'"></p>
                        </div>
                        <button type="button" @click="historyModalOpen = false" class="text-white hover:text-gray-200 text-xl font-bold">&times;</button>
                    </div>

                    <div class="p-6">
                        <div x-show="historyLoading" class="py-12 text-center text-gray-500">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                            <p class="text-xs">Memuat riwayat scan...</p>
                        </div>

                        <div x-show="!historyLoading">
                            <template x-if="historyData.length > 0">
                                <div class="relative border-l-2 border-blue-200 ml-4 pl-4 space-y-4 max-h-80 overflow-y-auto">
                                    <template x-for="(entry, index) in historyData" :key="index">
                                        <div class="relative">
                                            <div class="absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white"
                                                :class="index === 0 ? 'bg-blue-600 ring-4 ring-blue-100' : 'bg-gray-400'"></div>
                                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs">
                                                <div class="flex items-center justify-between font-bold text-gray-900">
                                                    <span class="uppercase tracking-wider" x-text="entry.position"></span>
                                                    <span class="text-gray-400 font-normal font-mono text-[11px]" x-text="entry.scantime"></span>
                                                </div>
                                                <div class="mt-1 text-gray-600 text-[11px]">
                                                    No Dokumen: <span class="font-mono text-gray-800" x-text="entry.noDokumen || '-'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="historyData.length === 0">
                                <div class="py-8 text-center text-gray-400 text-xs">
                                    Belum ada log riwayat scan untuk label ini.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex justify-end">
                        <button type="button" @click="historyModalOpen = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-lg transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function packagingDashboard() {
            return {
                activeTab: 'summary',
                searchQuery: '',
                stockFilter: 'all',
                summaryData: @json($summaryData),
                summaryPage: 1,
                summaryPerPage: 15,

                // Realtime per-label state
                latestPositions: @json($latestPositions),
                realtimeSearch: '',
                realtimePosFilter: '',
                realtimePage: 1,
                realtimePerPage: 20,

                // Detail Modal
                detailModalOpen: false,
                selectedPart: null,

                // History Modal
                historyModalOpen: false,
                historyLoading: false,
                historyPartNo: '',
                historyLabelNo: '',
                historyData: [],

                get filteredSummary() {
                    return this.summaryData.filter(item => {
                        const q = this.searchQuery.toLowerCase();
                        const matchSearch = item.part_no.toLowerCase().includes(q) || (item.part_name && item.part_name.toLowerCase().includes(q));
                        if (!matchSearch) return false;

                        if (this.stockFilter === 'has_stock') {
                            return item.total > 0;
                        } else if (this.stockFilter === 'at_customer') {
                            return item.quantity_customer > 0;
                        }
                        return true;
                    });
                },

                get maxSummaryPage() {
                    return Math.max(1, Math.ceil(this.filteredSummary.length / this.summaryPerPage));
                },

                get summaryStartIndex() {
                    return (this.summaryPage - 1) * this.summaryPerPage;
                },

                get paginatedSummary() {
                    const start = this.summaryStartIndex;
                    return this.filteredSummary.slice(start, start + this.summaryPerPage);
                },

                openBoxDetail(item) {
                    this.selectedPart = item;
                    this.detailModalOpen = true;
                },

                // Realtime filtered
                get filteredRealtime() {
                    return this.latestPositions.filter(row => {
                        const q = this.realtimeSearch.toLowerCase();
                        const matchQ = (row.partNo && row.partNo.toLowerCase().includes(q)) ||
                                       (String(row.label).toLowerCase().includes(q)) ||
                                       (row.customer && row.customer.toLowerCase().includes(q)) ||
                                       (row.noDokumen && row.noDokumen.toLowerCase().includes(q));
                        if (!matchQ) return false;

                        if (this.realtimePosFilter) {
                            if (this.realtimePosFilter === 'Customer') {
                                return row.position.toLowerCase().includes('customer');
                            }
                            return row.position.toLowerCase() === this.realtimePosFilter.toLowerCase();
                        }
                        return true;
                    });
                },

                get maxRealtimePage() {
                    return Math.max(1, Math.ceil(this.filteredRealtime.length / this.realtimePerPage));
                },

                get realtimeStartIndex() {
                    return (this.realtimePage - 1) * this.realtimePerPage;
                },

                get paginatedRealtime() {
                    const start = this.realtimeStartIndex;
                    return this.filteredRealtime.slice(start, start + this.realtimePerPage);
                },

                async fetchHistory(partNo, label) {
                    this.historyPartNo = partNo;
                    this.historyLabelNo = label;
                    this.historyModalOpen = true;
                    this.historyLoading = true;
                    this.historyData = [];

                    try {
                        const res = await fetch(`{{ route('api.barcode.label_history') }}?partNo=${encodeURIComponent(partNo)}&label=${encodeURIComponent(label)}`);
                        if (res.ok) {
                            this.historyData = await res.json();
                        }
                    } catch (e) {
                        console.error('Failed to fetch history', e);
                    } finally {
                        this.historyLoading = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
