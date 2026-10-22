<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="barcodeGeneratorApp()">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>🏷️</span> Cetak Barcode & Riwayat Kemasan
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Generate label QR serial untuk box kemasan baru atau cetak ulang (*reprint*) label rusak.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('summaryDashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg border border-gray-300 shadow-sm transition">
                        <span>📦</span> Dashboard Saldo
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm flex items-center gap-2 text-sm font-semibold">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-xl shadow-sm flex items-center gap-2 text-sm font-semibold">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="border-b border-gray-200 px-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" @click="activeTab = 'generate'"
                            :class="activeTab === 'generate' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>🏷️</span> Buat & Cetak Barcode Baru
                        </button>
                        <button type="button" @click="activeTab = 'reprint'"
                            :class="activeTab === 'reprint' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>📜</span> Riwayat & Cetak Ulang (Reprint)
                            <span class="bg-blue-100 text-blue-800 text-xs rounded-full px-2 py-0.5">{{ count($recentLabels) }}</span>
                        </button>
                    </nav>
                </div>

                {{-- TAB 1: GENERATE FORM --}}
                <div x-show="activeTab === 'generate'" class="p-6 sm:p-8">
                    <form action="{{ route('generatepackagingbarcode') }}" method="POST" id="generate-barcode-form" target="_blank">
                        @csrf

                        <div class="space-y-6">
                            {{-- Part Number Picker with Instant Search --}}
                            <div class="relative" x-data="{ openDropdown: false }">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                    1. Pilih Tipe Box (Part No)
                                </label>
                                
                                <div class="relative">
                                    <input type="text" x-model="partSearch" @focus="openDropdown = true" @click.away="openDropdown = false"
                                        placeholder="Ketik untuk mencari Part No atau nama box..."
                                        class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 text-sm p-3 pr-10 font-mono">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>

                                {{-- Hidden Real Input --}}
                                <input type="hidden" name="partNo" :value="selectedPartNo" required>

                                {{-- Dropdown Results --}}
                                <div x-show="openDropdown" class="absolute z-20 mt-1 w-full bg-white rounded-xl shadow-xl border border-gray-200 max-h-60 overflow-y-auto" style="display: none;">
                                    <template x-for="item in filteredBoxList" :key="item.part_no">
                                        <div @click="selectBox(item); openDropdown = false"
                                            class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition flex items-center justify-between">
                                            <div>
                                                <div class="font-mono font-bold text-gray-900 text-sm" x-text="item.part_no"></div>
                                                <div class="text-xs text-gray-500" x-text="item.part_name || '-'"></div>
                                            </div>
                                            <span class="text-xs text-blue-600 font-semibold">&larr; Pilih</span>
                                        </div>
                                    </template>
                                    <div x-show="filteredBoxList.length === 0" class="p-4 text-center text-xs text-gray-400">
                                        Tidak ada part no yang cocok. Ketik untuk mendaftarkan baru.
                                    </div>
                                </div>

                                {{-- Selected Preview Pill --}}
                                <div x-show="selectedPartNo" class="mt-2.5 p-3 bg-blue-50/70 border border-blue-200 rounded-xl flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Part Terpilih:</span>
                                        <span class="font-mono font-bold text-gray-900 text-sm" x-text="selectedPartNo"></span>
                                        <span class="text-xs text-gray-600 ml-2" x-text="selectedPartName ? '(' + selectedPartName + ')' : ''"></span>
                                    </div>
                                    <button type="button" @click="selectedPartNo = ''; selectedPartName = ''; partSearch = ''" class="text-xs text-red-500 hover:text-red-700 font-bold">
                                        Ganti
                                    </button>
                                </div>
                            </div>

                            {{-- Print Qty & Remark --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                        2. Jumlah Cetak (Quantity Box)
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="print_quantity" value="1" min="1" max="1000" required
                                            class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 text-sm p-3 font-bold text-gray-900">
                                        <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-xs text-gray-400 font-semibold">pcs box</span>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-1">Sistem otomatis memberi nomor label urut setelah label terakhir.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                        3. Catatan / Remark (Opsional)
                                    </label>
                                    <input type="text" name="remark" placeholder="Misal: Penambahan batch baru 2026..."
                                        class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 text-sm p-3">
                                </div>
                            </div>

                            {{-- Format Selection Buttons --}}
                            <div class="pt-4 border-t border-gray-200">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">
                                    4. Pilih Format & Ukuran Label
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    {{-- Option 1: Zebra 40x15 Thermal (Kembar 2-Up) --}}
                                    <button type="submit" formaction="{{ route('generate.zebra.barcode40x15') }}"
                                        class="p-4 sm:p-5 border-2 border-indigo-500 bg-indigo-50/30 hover:bg-indigo-50 rounded-2xl flex items-center gap-3.5 text-left transition group shadow-sm hover:shadow">
                                        <span class="p-3 bg-indigo-600 text-white rounded-xl text-2xl group-hover:scale-110 transition transform">🏷️</span>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="block font-black text-indigo-950 text-sm">Zebra (40 x 15 mm)</span>
                                                <span class="text-[10px] font-bold bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">Kembar</span>
                                            </div>
                                            <span class="block text-xs text-indigo-800 mt-0.5">2 label identik kiri-kanan per baris</span>
                                        </div>
                                    </button>

                                    {{-- Option 2: Zebra 50x35 Thermal --}}
                                    <button type="submit" formaction="{{ route('generate.zebra.barcode') }}"
                                        class="p-4 sm:p-5 border-2 border-emerald-500 bg-emerald-50/30 hover:bg-emerald-50 rounded-2xl flex items-center gap-3.5 text-left transition group shadow-sm hover:shadow">
                                        <span class="p-3 bg-emerald-600 text-white rounded-xl text-2xl group-hover:scale-110 transition transform">🏷️</span>
                                        <div>
                                            <span class="block font-black text-emerald-950 text-sm">Zebra (50 x 35 mm)</span>
                                            <span class="block text-xs text-emerald-800 mt-0.5">Printer roll thermal 2 kolom stiker</span>
                                        </div>
                                    </button>

                                    {{-- Option 3: A4 Standard Sheet --}}
                                    <button type="submit" formaction="{{ route('generatepackagingbarcode') }}"
                                        class="p-4 sm:p-5 border-2 border-blue-500 bg-blue-50/30 hover:bg-blue-50 rounded-2xl flex items-center gap-3.5 text-left transition group shadow-sm hover:shadow">
                                        <span class="p-3 bg-blue-600 text-white rounded-xl text-2xl group-hover:scale-110 transition transform">📄</span>
                                        <div>
                                            <span class="block font-black text-blue-950 text-sm">Kertas A4 Lembaran</span>
                                            <span class="block text-xs text-blue-800 mt-0.5">Grid 3 kolom potong manual / stiker A4</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- TAB 2: REPRINT & RECENT HISTORY --}}
                <div x-show="activeTab === 'reprint'" class="p-6 sm:p-8" style="display: none;">
                    <form action="{{ route('barcode.reprint') }}" method="POST" target="_blank" x-data="{ selectedIds: [], format: 'zebra40x15' }">
                        @csrf
                        <input type="hidden" name="format" :value="format">

                        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm">Cetak Ulang Label Terdaftar (Reprint)</h3>
                                <p class="text-xs text-gray-500">Pilih label yang ingin di-print ulang dari riwayat 50 label terakhir.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <select x-model="format" class="text-xs border-gray-300 rounded-lg py-1.5 px-3">
                                    <option value="zebra40x15">Format: Zebra Thermal 40x15 (Kembar)</option>
                                    <option value="zebra">Format: Zebra Thermal 50x35</option>
                                    <option value="a4">Format: Kertas A4 Lembar</option>
                                </select>
                                <button type="submit" :disabled="selectedIds.length === 0"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs rounded-lg shadow-sm transition">
                                    🖨️ Cetak Ulang (<span x-text="selectedIds.length"></span>)
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-10">
                                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th class="px-4 py-3 text-left">Part No Box</th>
                                        <th class="px-4 py-3 text-left">Nama Box</th>
                                        <th class="px-4 py-3 text-center">No Label</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-left">Remark</th>
                                        <th class="px-4 py-3 text-left">Dibuat Pada</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($recentLabels as $lbl)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" name="ids[]" value="{{ $lbl->id }}" x-model="selectedIds"
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-gray-900 font-mono text-xs">{{ $lbl->part_no }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate">{{ $lbl->boxData?->part_name ?: '-' }}</td>
                                            <td class="px-4 py-3 text-center font-mono font-bold text-blue-600">#{{ $lbl->label }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase
                                                    {{ $lbl->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $lbl->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-500">{{ $lbl->remark ?: '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $lbl->created_at }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                                Belum ada label yang digenerate di sistem.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <script>
        function barcodeGeneratorApp() {
            return {
                activeTab: 'generate',
                boxList: @json($datas),
                partSearch: '',
                selectedPartNo: '',
                selectedPartName: '',

                get filteredBoxList() {
                    const q = this.partSearch.toLowerCase();
                    if (!q) return this.boxList.slice(0, 30);
                    return this.boxList.filter(b => 
                        b.part_no.toLowerCase().includes(q) || 
                        (b.part_name && b.part_name.toLowerCase().includes(q))
                    ).slice(0, 30);
                },

                selectBox(item) {
                    this.selectedPartNo = item.part_no;
                    this.selectedPartName = item.part_name || '';
                    this.partSearch = item.part_no;
                },

                toggleSelectAll(event) {
                    const checked = event.target.checked;
                    const allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
                    const ids = [];
                    allCheckboxes.forEach(cb => {
                        cb.checked = checked;
                        if (checked) ids.push(cb.value);
                    });
                    this.selectedIds = ids;
                }
            };
        }
    </script>
</x-app-layout>
