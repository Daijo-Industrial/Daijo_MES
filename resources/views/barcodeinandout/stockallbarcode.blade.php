<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="{ search: '', balances: @json($balances) }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>📊</span> Saldo Box: {{ ucfirst($location) }}
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Sisa saldo unit box yang tersedia di plant {{ ucfirst($location) }}.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('summaryDashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 shadow-sm transition">
                        <span>📦</span> Ke Dashboard Utama
                    </a>
                </div>
            </div>

            {{-- Location Switcher & Search Bar --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <a href="{{ route('stockallbarcode', ['location' => 'Jakarta']) }}"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $location === 'Jakarta' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        🏭 Jakarta (Cakung)
                    </a>
                    <a href="{{ route('stockallbarcode', ['location' => 'Karawang']) }}"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $location === 'Karawang' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        🏗️ Karawang (KIIC)
                    </a>
                </div>

                <div class="w-full sm:w-72">
                    <input type="text" x-model="search" placeholder="Cari Part No atau Deskripsi..."
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Part No</th>
                                <th class="px-6 py-3.5 text-left">Deskripsi Box</th>
                                <th class="px-6 py-3.5 text-center">Sisa Saldo (pcs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template x-for="item in balances.filter(b => b.partNo.toLowerCase().includes(search.toLowerCase()) || (b.description && b.description.toLowerCase().includes(search.toLowerCase())))" :key="item.partNo">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 font-semibold text-gray-900 whitespace-nowrap" x-text="item.partNo"></td>
                                    <td class="px-6 py-3 text-gray-600 text-xs max-w-md" x-text="item.description || '-'"></td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="item.balance > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'"
                                            x-text="item.balance"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="balances.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    Belum ada data saldo box untuk lokasi ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
