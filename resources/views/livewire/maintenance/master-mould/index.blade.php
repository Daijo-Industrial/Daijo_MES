<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </span>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Mould Directory</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">Katalog data master tooling mould, part number, customer, dan mesin injection.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="openUploadModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Excel
            </button>
            <button wire:click="openAddModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                + Tambah Mould
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Metric Chips & Filters -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Relasi Part</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalCount) }}</p>
            </div>
            <span class="p-3 bg-blue-50 text-blue-600 rounded-xl">📦</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unique Mould Tooling</p>
                <p class="text-2xl font-bold text-indigo-900 mt-1">{{ number_format($uniqueMouldCount) }}</p>
            </div>
            <span class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">⚙️</span>
        </div>
        <div class="sm:col-span-2 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Mould No, Part No, Part Name, Mesin..."
                    class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="w-full sm:w-64">
                <select wire:model.live="selectedCustomer"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Semua Customer --</option>
                    @foreach ($customerList as $cust)
                        <option value="{{ $cust }}">{{ $cust }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-700">
                    <tr>
                        <th class="px-4 py-3.5 text-left font-semibold">Mould No</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Part Number</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Part Name</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Customer</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Mesin</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Material</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($moulds as $m)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-indigo-700 whitespace-nowrap">{{ $m->mould_no }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-800 whitespace-nowrap">{{ $m->part_no }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900 max-w-xs truncate" title="{{ $m->part_name }}">
                                {{ $m->part_name }}
                                @if($m->model)
                                    <span class="ml-1 text-xs px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $m->model }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 rounded-md border border-blue-200">
                                    {{ $m->customer }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">{{ $m->machine_number ?: '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 max-w-xs truncate" title="{{ $m->material }}">{{ $m->material ?: '-' }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $m->is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button wire:click="openEditModal({{ $m->id }})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit Mould">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="deleteMould({{ $m->id }})"
                                        onclick="confirm('Yakin ingin menghapus mould ini?') || event.stopImmediatePropagation()"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Mould">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-base font-medium">Tidak ada data mould ditemukan.</p>
                                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau upload file Excel.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($moulds->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $moulds->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form Add/Edit -->
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">{{ $isEdit ? 'Edit Data Master Mould' : 'Tambah Master Mould Baru' }}</h3>
                    <button wire:click="closeFormModal" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form wire:submit.prevent="saveMould" class="space-y-4 mt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Mould Number *</label>
                            <input type="text" wire:model="mould_no" placeholder="Contoh: IK-MM-005-010"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('mould_no') border-rose-500 @enderror">
                            @error('mould_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Part Number *</label>
                            <input type="text" wire:model="part_no" placeholder="Contoh: 401-76934B001P"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('part_no') border-rose-500 @enderror">
                            @error('part_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Part Name *</label>
                        <input type="text" wire:model="part_name" placeholder="Nama part lengkap..."
                            class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('part_name') border-rose-500 @enderror">
                        @error('part_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Customer *</label>
                            <input type="text" wire:model="customer" placeholder="Contoh: IKUYO, PT IFSP..."
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none @error('customer') border-rose-500 @enderror">
                            @error('customer') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Model</label>
                            <input type="text" wire:model="model" placeholder="Contoh: 5H45, 4L45W..."
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Mesin Default</label>
                            <input type="text" wire:model="machine_number" placeholder="Contoh: 1300T, MC-05..."
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Material Resin</label>
                            <input type="text" wire:model="material" placeholder="Contoh: PP-KF06 LD..."
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="mould_active" wire:model="is_active" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="mould_active" class="text-sm font-medium text-slate-700">Mould ini aktif digunakan dalam produksi</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeFormModal"
                            class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors">
                            Simpan Mould
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Upload Excel -->
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Upload Excel Master Mould</h3>
                    <button wire:click="closeUploadModal" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form wire:submit.prevent="uploadExcel" class="space-y-4 mt-4">
                    <p class="text-xs text-slate-500">
                        Upload file Excel (format .xlsx / .xls). Sistem akan membaca tab <span class="font-semibold text-indigo-600">Sheet3 (3)</span> atau sheet pertama dan melakukan update data mould secara otomatis.
                    </p>

                    <div class="p-4 border-2 border-dashed border-slate-300 rounded-xl text-center bg-slate-50 hover:bg-slate-100/50 transition-colors">
                        <input type="file" wire:model="excelFile" accept=".xlsx,.xls" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        @error('excelFile') <span class="block text-xs text-rose-500 mt-2">{{ $message }}</span> @enderror
                    </div>

                    @if ($excelFile)
                        <div class="text-xs text-slate-600 flex items-center gap-1">
                            <span>📄 File terpilih:</span>
                            <span class="font-medium text-slate-900">{{ $excelFile->getClientOriginalName() }}</span>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeUploadModal"
                            class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                            <span wire:loading wire:target="uploadExcel" class="animate-spin">⏳</span>
                            <span>Proses Import</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
