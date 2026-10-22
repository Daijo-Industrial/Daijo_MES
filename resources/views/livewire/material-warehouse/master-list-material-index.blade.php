<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 shadow-sm animate-fade-in">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl flex items-center justify-between text-rose-800 shadow-sm">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Background Queue Import Progress Banner -->
        @if ($currentBatchId)
            <div wire:poll.2s="pollImportProgress" class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">
                            @if (($importProgress['status'] ?? '') === 'completed')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @elseif (($importProgress['status'] ?? '') === 'failed')
                                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @else
                                <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">
                                @if (($importProgress['status'] ?? '') === 'completed')
                                    Import Excel Selesai! 🎉
                                @elseif (($importProgress['status'] ?? '') === 'failed')
                                    Import Excel Gagal! ⚠️
                                @else
                                    Memproses Import Excel di Background Queue...
                                @endif
                            </h3>
                            <p class="text-xs text-gray-500">
                                Total Baris Terproses: <strong class="text-emerald-700">{{ number_format($importProgress['processed'] ?? 0) }}</strong>
                                @if(isset($importProgress['error']))
                                    <span class="text-rose-600 block mt-1">Error: {{ $importProgress['error'] }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <button wire:click="clearImportBanner" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 transition">
                        Tutup Banner
                    </button>
                </div>
            </div>
        @endif

        <!-- Main Header & Actions -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full inline-block"></span>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Master List Material</h1>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Database Master Item Material (Bahan Baku) Gudang. Total record: <span class="font-bold text-emerald-700">{{ number_format($totalCount) }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button wire:click="openCreateModal" class="px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Material</span>
                </button>

                <button wire:click="openUploadModal" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md shadow-emerald-200 transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span>Upload Excel / CSV</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Item No, Description, Supplier..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                <div class="absolute left-3 top-3 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div class="flex items-center space-x-2 text-xs font-semibold text-gray-500 w-full md:w-auto justify-end">
                <span>Tampilkan:</span>
                <select wire:model.live="perPage" class="bg-gray-50 border border-gray-200 rounded-xl text-xs px-3 py-1.5 focus:ring-2 focus:ring-emerald-500">
                    <option value="25">25 per halaman</option>
                    <option value="50">50 per halaman</option>
                    <option value="100">100 per halaman</option>
                </select>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider">
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4">Item No.</th>
                            <th class="py-3.5 px-4">Item Description</th>
                            <th class="py-3.5 px-4">Preferred Supplier</th>
                            <th class="py-3.5 px-4">Purchasing UoM</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
                        @forelse ($materials as $index => $mat)
                            <tr class="hover:bg-emerald-50/40 transition">
                                <td class="py-3.5 px-4 text-center font-bold text-gray-400">
                                    {{ $materials->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    {{ $mat->item_code }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($mat->item_description)
                                        <span class="text-gray-800">{{ $mat->item_description }}</span>
                                    @else
                                        <span class="text-gray-300 italic font-mono text-[10px]">NULL</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($mat->preferred_supplier)
                                        <span class="inline-block px-2.5 py-1 bg-gray-100 text-gray-700 font-mono rounded-md text-[11px]">
                                            {{ $mat->preferred_supplier }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 italic font-mono text-[10px]">NULL</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($mat->purchasing_uom)
                                        <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded text-[11px]">
                                            {{ $mat->purchasing_uom }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 italic font-mono text-[10px]">NULL</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <button wire:click="editItem({{ $mat->id }})" class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 210.3H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button onclick="confirm('Hapus item {{ $mat->item_code }}?') || event.stopImmediatePropagation()" wire:click="deleteItem({{ $mat->id }})" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p class="font-bold">Belum ada data material</p>
                                    <p class="text-xs text-gray-400 mt-1">Klik tombol "Upload Excel / CSV" untuk mengunggah master data material.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-gray-100">
                {{ $materials->links() }}
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    @if ($showUploadModal)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-up">
                <div class="p-6 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight">Upload Master Material Excel</h3>
                        <p class="text-xs text-emerald-100">Format: Item No. | Item Description | Preferred Supplier | Purchasing UoM</p>
                    </div>
                    <button wire:click="$set('showUploadModal', false)" class="text-emerald-200 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="startImport" class="p-6 space-y-4">
                    @if ($uploadError)
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-start space-x-2">
                            <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $uploadError }}</span>
                        </div>
                    @endif

                    <div class="border-2 border-dashed border-gray-200 hover:border-emerald-500 rounded-2xl p-6 text-center transition bg-gray-50/50">
                        <svg class="w-10 h-10 mx-auto text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        
                        <label class="block cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 block">Pilih File Excel / CSV</span>
                            <span class="text-[11px] text-gray-400 block mt-0.5">.xlsx, .xls, .csv (Maksimal 50MB)</span>
                            <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="hidden">
                        </label>

                        @if ($file)
                            <div class="mt-3 p-2 bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold">
                                📁 {{ $file->getClientOriginalName() }}
                            </div>
                        @endif
                    </div>

                    @error('file')
                        <p class="text-xs text-rose-600 font-bold">{{ $message }}</p>
                    @enderror

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-800 leading-relaxed">
                        💡 <strong>Catatan:</strong> Data akan diproses di <strong>Background Queue</strong> (cocok untuk ~28.000 baris). Kolom kosong pada Excel otomatis diisi <code>NULL</code>.
                    </div>

                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" wire:click="$set('showUploadModal', false)" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md shadow-emerald-200 transition flex items-center space-x-2">
                            <span wire:loading.remove>Mulai Import</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Add / Edit Item Modal -->
    @if ($showItemModal)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-up">
                <div class="p-6 bg-gray-900 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight">{{ $editingId ? 'Edit Master Material' : 'Tambah Master Material Baru' }}</h3>
                    </div>
                    <button wire:click="$set('showItemModal', false)" class="text-gray-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveItem" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Item No. / Code *</label>
                        <input type="text" wire:model="item_code" placeholder="Misal: 180-CN890-FL" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 uppercase">
                        @error('item_code') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Item Description</label>
                        <textarea wire:model="item_description" rows="2" placeholder="Deskripsi nama material..." class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                        @error('item_description') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Preferred Supplier</label>
                            <input type="text" wire:model="preferred_supplier" placeholder="Misal: VMI0000561" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500">
                            @error('preferred_supplier') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Purchasing UoM</label>
                            <input type="text" wire:model="purchasing_uom" placeholder="Misal: PCS / M / KG" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 uppercase">
                            @error('purchasing_uom') <span class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showItemModal', false)" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold text-xs transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md transition">
                            Simpan Material
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
