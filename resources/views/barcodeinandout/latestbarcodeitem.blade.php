<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>📍</span> Posisi Terakhir Label Box
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Daftar riwayat posisi unit fisik kemasan berdasarkan scan terakhir.
                    </p>
                </div>
                <div>
                    <a href="{{ route('summaryDashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 shadow-sm transition">
                        <span>📦</span> Ke Dashboard Utama
                    </a>
                </div>
            </div>

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('updated.barcode.item.position') }}"
                class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Part No -->
                    <div>
                        <label for="partNo" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Part No Box:</label>
                        <select id="partNo" name="partNo"
                            class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Semua Part No --</option>
                            @foreach ($partNumbers as $partNumber)
                                <option value="{{ $partNumber->partNo }}"
                                    {{ request('partNo') == $partNumber->partNo ? 'selected' : '' }}>
                                    {{ $partNumber->partNo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Scan Time -->
                    <div>
                        <label for="scantime" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Scan:</label>
                        <input type="date" id="scantime" name="scantime" value="{{ request('scantime') }}"
                            class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Posisi:</label>
                        <select id="position" name="position"
                            class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Semua Posisi --</option>
                            <option value="Jakarta" {{ request('position') == 'Jakarta' ? 'selected' : '' }}>Jakarta (Cakung)</option>
                            <option value="Karawang" {{ request('position') == 'Karawang' ? 'selected' : '' }}>Karawang (KIIC)</option>
                            <option value="CustomerJakarta" {{ request('position') == 'CustomerJakarta' ? 'selected' : '' }}>Customer Jakarta</option>
                            <option value="CustomerKarawang" {{ request('position') == 'CustomerKarawang' ? 'selected' : '' }}>Customer Karawang</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-2.5 px-4 rounded-lg transition shadow-sm">
                            Filter Data
                        </button>
                        <a href="{{ route('updated.barcode.item.position') }}"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold text-xs py-2.5 px-3 rounded-lg border border-gray-300 transition">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Part No</th>
                                <th class="px-6 py-3.5 text-center">No Label</th>
                                <th class="px-6 py-3.5 text-left">Posisi Terakhir</th>
                                <th class="px-6 py-3.5 text-left">Customer</th>
                                <th class="px-6 py-3.5 text-left">Waktu Scan</th>
                                <th class="px-6 py-3.5 text-left">No Dokumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($sortedItems as $item)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 font-semibold text-gray-900 whitespace-nowrap">{{ $item->partNo }}</td>
                                    <td class="px-6 py-3 text-center font-mono font-bold text-blue-600">#{{ $item->label }}</td>
                                    <td class="px-6 py-3">
                                        @php
                                            $posLower = strtolower($item->position ?? '');
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                            {{ $posLower === 'jakarta' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                            {{ $posLower === 'karawang' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ str_contains($posLower, 'customer') ? 'bg-amber-100 text-amber-800' : '' }}
                                            {{ !in_array($posLower, ['jakarta', 'karawang']) && !str_contains($posLower, 'customer') ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ $item->position }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700 text-xs">{{ $item->customer ?? '-' }}</td>
                                    <td class="px-6 py-3 text-gray-500 text-xs font-mono">{{ $item->scantime }}</td>
                                    <td class="px-6 py-3 text-gray-500 text-xs font-mono">{{ $item->noDokumen ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        Tidak ada data unit barcode yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sortedItems->hasPages())
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        {{ $sortedItems->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
