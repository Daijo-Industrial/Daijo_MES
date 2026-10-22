<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="storeBoxIndexApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>📦</span> Master Data Kemasan (Packaging)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Katalog spesifikasi box kemasan dan nomor serial individual box yang beredar.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                        <span>+</span> Tambah Tipe Box Baru
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm flex items-center gap-2 text-sm font-semibold">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="border-b border-gray-200 px-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <a href="{{ route('barcode.box_master.index') }}"
                            class="border-blue-600 text-blue-600 font-bold whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2">
                            <span>📦</span> Katalog Tipe Box (Part Master)
                            <span class="bg-blue-100 text-blue-800 text-xs rounded-full px-2 py-0.5" x-text="boxes.length"></span>
                        </a>
                        <a href="{{ route('barcode.box_detail.index') }}"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>🏷️</span> Serial Individual Box (Detail)
                        </a>
                    </nav>
                </div>

                {{-- Content --}}
                <div class="p-6">
                    {{-- Search Filter --}}
                    <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="relative w-full sm:w-80">
                            <input type="text" x-model="search" placeholder="Cari Part No atau Nama Box..."
                                class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            Total: <strong class="text-gray-900" x-text="filteredBoxes.length"></strong> tipe box
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Part No Box</th>
                                    <th class="px-6 py-3.5 text-left">Nama / Deskripsi Box</th>
                                    <th class="px-6 py-3.5 text-center">Unit Fisik Terdaftar</th>
                                    <th class="px-6 py-3.5 text-center w-36">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="box in filteredBoxes" :key="box.id">
                                    <tr class="hover:bg-blue-50/50 transition">
                                        <td class="px-6 py-3.5 font-bold font-mono text-gray-900 whitespace-nowrap" x-text="box.part_no"></td>
                                        <td class="px-6 py-3.5 text-xs text-gray-600 max-w-md truncate" :title="box.part_name" x-text="box.part_name || '-'"></td>
                                        <td class="px-6 py-3.5 text-center">
                                            <a :href="'{{ route('barcode.box_detail.index') }}?part_no=' + encodeURIComponent(box.part_no)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition hover:scale-105"
                                                :class="box.details_count > 0 ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' : 'bg-gray-100 text-gray-500'">
                                                <span x-text="box.details_count || 0"></span> pcs &rarr;
                                            </a>
                                        </td>
                                        <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" @click="editBox(box)"
                                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-md border border-indigo-200 transition">
                                                    Edit
                                                </button>
                                                <form :action="'{{ url('barcode/box-master') }}/' + box.id" method="POST"
                                                    onsubmit="return confirm('Hapus tipe box ini dari master?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-md border border-red-200 transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredBoxes.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        Tidak ada tipe box yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ADD MODAL --}}
            <div x-show="openAddModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="openAddModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 z-10">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h3 class="text-base font-bold text-gray-900">Tambah Tipe Kemasan Baru</h3>
                            <button type="button" @click="openAddModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                        </div>
                        <form action="{{ route('barcode.box_master.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Part No Box</label>
                                <input type="text" name="part_no" required placeholder="Misal: 601-68921B000P"
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 font-mono focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-6">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama / Spesifikasi Box</label>
                                <input type="text" name="part_name" placeholder="Misal: BOX 5mm COVER ASSY..."
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="openAddModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 shadow-sm">Simpan Tipe Box</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- EDIT MODAL --}}
            <div x-show="openEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="openEditModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 z-10">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h3 class="text-base font-bold text-gray-900">Edit Tipe Kemasan</h3>
                            <button type="button" @click="openEditModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                        </div>
                        <form :action="'{{ url('barcode/box-master') }}/' + currentBox.id" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Part No Box</label>
                                <input type="text" name="part_no" x-model="currentBox.part_no" required
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 font-mono focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-6">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nama / Spesifikasi Box</label>
                                <input type="text" name="part_name" x-model="currentBox.part_name"
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="openEditModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 shadow-sm">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function storeBoxIndexApp() {
            return {
                boxes: @json($boxes),
                search: '',
                openAddModal: false,
                openEditModal: false,
                currentBox: {},

                get filteredBoxes() {
                    const q = this.search.toLowerCase();
                    if (!q) return this.boxes;
                    return this.boxes.filter(b => 
                        b.part_no.toLowerCase().includes(q) || 
                        (b.part_name && b.part_name.toLowerCase().includes(q))
                    );
                },

                editBox(box) {
                    this.currentBox = Object.assign({}, box);
                    this.openEditModal = true;
                }
            };
        }
    </script>
</x-app-layout>
