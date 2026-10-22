<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen" x-data="{ 
        editOpen: false, 
        editId: null, 
        editPart: '', 
        editLabel: '', 
        editStatus: '', 
        editRemark: '',
        openEdit(id, part, label, status, remark) {
            this.editId = id;
            this.editPart = part;
            this.editLabel = label;
            this.editStatus = status;
            this.editRemark = remark || '';
            this.editOpen = true;
        }
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        <span>🏷️</span> Serial Individual Box (Detail)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Daftar seluruh fisik label kemasan per Part No, status aktif, dan catatan kondisi.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('barcodeindex') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <span>+</span> Buat Label Baru
                    </a>
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
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2 transition">
                            <span>📦</span> Katalog Tipe Box (Part Master)
                        </a>
                        <a href="{{ route('barcode.box_detail.index') }}"
                            class="border-blue-600 text-blue-600 font-bold whitespace-nowrap py-4 px-1 border-b-2 text-sm flex items-center gap-2">
                            <span>🏷️</span> Serial Individual Box (Detail)
                            <span class="bg-blue-100 text-blue-800 text-xs rounded-full px-2 py-0.5">{{ $details->total() }}</span>
                        </a>
                    </nav>
                </div>

                {{-- Filter Section --}}
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <form action="{{ route('barcode.box_detail.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Part No Box:</label>
                            <input type="text" name="part_no" value="{{ request('part_no') }}" placeholder="Cari Part No..."
                                class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status Box:</label>
                            <select name="status" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Semua Status --</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ACTIVE (Siap Pakai)</option>
                                <option value="non-active" {{ request('status') === 'non-active' ? 'selected' : '' }}>NON-ACTIVE (Rusak / Afkir)</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-2 px-4 rounded-lg transition shadow-sm">
                                Filter Data
                            </button>
                            <a href="{{ route('barcode.box_detail.index') }}" class="bg-white hover:bg-gray-100 text-gray-600 font-semibold text-xs py-2 px-3 rounded-lg border border-gray-300 transition">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                {{-- Table Section --}}
                <div class="p-6">
                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">ID</th>
                                    <th class="px-6 py-3.5 text-left">Part No Box</th>
                                    <th class="px-6 py-3.5 text-center">No Label</th>
                                    <th class="px-6 py-3.5 text-center">Status</th>
                                    <th class="px-6 py-3.5 text-left">Catatan / Remark</th>
                                    <th class="px-6 py-3.5 text-left">Tanggal Dibuat</th>
                                    <th class="px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($details as $detail)
                                    <tr class="hover:bg-blue-50/50 transition">
                                        <td class="px-6 py-3 text-xs text-gray-400 font-mono">#{{ $detail->id }}</td>
                                        <td class="px-6 py-3 font-bold font-mono text-gray-900 whitespace-nowrap">{{ $detail->part_no }}</td>
                                        <td class="px-6 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded font-mono font-bold text-xs bg-blue-50 text-blue-700 border border-blue-200">
                                                #{{ $detail->label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase
                                                {{ $detail->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $detail->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $detail->remark ?: '-' }}</td>
                                        <td class="px-6 py-3 text-xs text-gray-400 font-mono">{{ $detail->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-3 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button"
                                                    @click="openEdit({{ $detail->id }}, '{{ $detail->part_no }}', '{{ $detail->label }}', '{{ $detail->status }}', '{{ $detail->remark }}')"
                                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-md border border-indigo-200 transition">
                                                    Edit Status
                                                </button>
                                                <form action="{{ route('barcode.reprint') }}" method="POST" target="_blank" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="ids[]" value="{{ $detail->id }}">
                                                    <input type="hidden" name="format" value="zebra">
                                                    <button type="submit"
                                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-md border border-gray-300 transition"
                                                        title="Cetak Ulang Zebra Thermal">
                                                        🖨️
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                            Tidak ada data serial individual box yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($details->hasPages())
                        <div class="mt-4">
                            {{ $details->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- EDIT MODAL --}}
            <div x-show="editOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-50" @click="editOpen = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h3 class="text-base font-bold text-gray-900">
                                Update Status Box: <span class="font-mono text-blue-600" x-text="editPart + ' #' + editLabel"></span>
                            </h3>
                            <button type="button" @click="editOpen = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                        </div>
                        <form :action="'{{ url('barcode/box-detail') }}/' + editId" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Status Fisik Box</label>
                                <select name="status" x-model="editStatus" required
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 focus:ring-2 focus:ring-blue-500">
                                    <option value="active">ACTIVE (Siap Pakai di Produksi / Store)</option>
                                    <option value="non-active">NON-ACTIVE (Rusak / Afkir / Hilang)</option>
                                </select>
                            </div>
                            <div class="mb-6">
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Catatan / Remark</label>
                                <textarea name="remark" x-model="editRemark" rows="2" placeholder="Alasan perubahan status..."
                                    class="w-full border-gray-300 rounded-lg text-sm p-2.5 focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="editOpen = false" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200">Batal</button>
                                <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700 shadow-sm">Simpan Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
