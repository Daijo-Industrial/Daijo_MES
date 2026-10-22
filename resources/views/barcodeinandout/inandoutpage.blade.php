<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="{ activeTab: 'new_scan' }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>⚡</span> Operasional Scan Barcode Box (In / Out)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Pencatatan perpindahan fisik box masuk (IN dari customer/vendor) dan box keluar (OUT pengiriman).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('summaryDashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 shadow-sm transition">
                        <span>📦</span> Dashboard Saldo
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm flex items-start justify-between" role="alert">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <span>✅</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="border-b border-gray-200 px-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" @click="activeTab = 'new_scan'"
                            :class="activeTab === 'new_scan' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>🚀</span> Mulai Sesi Scan Baru
                        </button>
                        <button type="button" @click="activeTab = 'recent_logs'"
                            :class="activeTab === 'recent_logs' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>📋</span> Dokumen Scan Terbaru
                            <span class="bg-gray-100 text-gray-600 text-xs rounded-full px-2 py-0.5">{{ count($recentMasters) }}</span>
                        </button>
                    </nav>
                </div>

                {{-- TAB 1: FORM SESI SCAN BARU --}}
                <div x-show="activeTab === 'new_scan'" class="p-6 sm:p-8">
                    <form action="{{ route('process.in.and.out') }}" method="POST" x-data="{ warehouseType: 'in', location: 'jakarta', customer: '' }">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            {{-- Jenis Operasi (IN vs OUT) --}}
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">1. Jenis Perpindahan (Tipe Operasi)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center text-center transition"
                                        :class="warehouseType === 'in' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'">
                                        <input type="radio" name="warehouseType" value="in" x-model="warehouseType" class="sr-only" required>
                                        <span class="text-3xl mb-1">📥</span>
                                        <span class="text-sm font-extrabold uppercase tracking-wider">BOX MASUK (IN)</span>
                                        <span class="text-[11px] text-gray-500 mt-1">Balikan dari customer / vendor</span>
                                    </label>

                                    <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center text-center transition"
                                        :class="warehouseType === 'out' ? 'border-amber-500 bg-amber-50/50 text-amber-900 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'">
                                        <input type="radio" name="warehouseType" value="out" x-model="warehouseType" class="sr-only">
                                        <span class="text-3xl mb-1">📤</span>
                                        <span class="text-sm font-extrabold uppercase tracking-wider">BOX KELUAR (OUT)</span>
                                        <span class="text-[11px] text-gray-500 mt-1">Kirim isi FG ke customer</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Lokasi Pabrik (Jakarta vs Karawang) --}}
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">2. Lokasi Pabrik Daijo</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center text-center transition"
                                        :class="location === 'jakarta' ? 'border-blue-500 bg-blue-50/50 text-blue-900 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'">
                                        <input type="radio" name="location" value="jakarta" x-model="location" class="sr-only" required>
                                        <span class="text-3xl mb-1">🏭</span>
                                        <span class="text-sm font-extrabold uppercase tracking-wider">JAKARTA</span>
                                        <span class="text-[11px] text-gray-500 mt-1">Pabrik Cakung / Pusat</span>
                                    </label>

                                    <label class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center text-center transition"
                                        :class="location === 'karawang' ? 'border-blue-500 bg-blue-50/50 text-blue-900 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'">
                                        <input type="radio" name="location" value="karawang" x-model="location" class="sr-only">
                                        <span class="text-3xl mb-1">🏗️</span>
                                        <span class="text-sm font-extrabold uppercase tracking-wider">KARAWANG</span>
                                        <span class="text-[11px] text-gray-500 mt-1">Plant KIIC Karawang</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Customer Selection --}}
                        <div class="mb-8 bg-gray-50 p-6 rounded-2xl border border-gray-200">
                            <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                3. Pilih Customer Asal / Tujuan
                            </label>
                            <select id="customer_name" name="customer_name" required x-model="customer"
                                class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 text-sm p-3">
                                <option value="" disabled selected>-- Pilih Customer Terkait --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->name }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-2">
                                Box yang di-scan akan otomatis dikaitkan saldo kepemilikannya ke customer yang dipilih.
                            </p>
                        </div>

                        {{-- Submit Button --}}
                        <div class="flex justify-end">
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm rounded-xl shadow-md hover:shadow-lg transition">
                                <span>🚀</span> BUKA HALAMAN SCANNER BARCODE
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TAB 2: LOG DOKUMEN SCAN TERAKHIR --}}
                <div x-show="activeTab === 'recent_logs'" class="p-6 sm:p-8" style="display: none;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 text-sm">25 Sesi Dokumen Scan Terakhir</h3>
                        <a href="{{ route('list.barcode') }}" class="text-xs text-blue-600 hover:underline font-semibold flex items-center gap-1">
                            <span>Buka Riwayat Lengkap &raquo;</span>
                        </a>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">No Dokumen</th>
                                    <th class="px-4 py-3 text-center">Tipe</th>
                                    <th class="px-4 py-3 text-left">Lokasi</th>
                                    <th class="px-4 py-3 text-left">Customer</th>
                                    <th class="px-4 py-3 text-left">Tanggal Scan</th>
                                    <th class="px-4 py-3 text-center">Jumlah Box</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($recentMasters as $doc)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-gray-900 text-xs">{{ $doc->noDokumen }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black uppercase
                                                {{ strtolower($doc->tipeBarcode) === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $doc->tipeBarcode }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs uppercase font-semibold text-gray-700">{{ $doc->location }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-800 font-medium">{{ $doc->customer ?: '-' }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $doc->dateScan }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                                {{ $doc->detail_barcode_count }} pcs
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                            Belum ada sesi scan dokumen yang tercatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
