<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Top Alert Notification -->
    @if($statusMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="flex items-center justify-between p-4 rounded-xl border shadow-sm transition-all
            {{ $statusType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($statusType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-blue-50 border-blue-200 text-blue-800') }}">
            <div class="flex items-center gap-3">
                @if($statusType === 'success')
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                @elseif($statusType === 'error')
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                @else
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
                <span class="text-sm font-bold">{{ $statusMessage }}</span>
            </div>
            <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                <span>Second Process</span>
                <span>&bull;</span>
                <span>Master Data</span>
                <span>&bull;</span>
                <span class="text-indigo-600 font-bold">Defect Catalog & Aliases</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                🏷️ Master Katalog Defect (NG) & Normalisasi Alias
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                Standarisasi seluruh nama defect (NG) Second Process, kelola alias/variasi penulisan operator, dan perbaiki data historis secara otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                wire:click="standardizeBacklog"
                wire:loading.attr="disabled"
                wire:confirm="Yakin ingin mengeksekusi standarisasi seluruh record NG di database berdasarkan alias yang terdaftar?"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow-sm transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove wire:target="standardizeBacklog">⚡ Standarisasi Backlog DB</span>
                <span wire:loading wire:target="standardizeBacklog">⏳ Memproses...</span>
            </button>
        </div>
    </div>

    <!-- SECTION 1: UNMAPPED DEFECTS TRIAGE QUEUE -->
    @if(count($unmappedList) > 0)
        <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-200/80 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <h2 class="text-sm sm:text-base font-black text-amber-900">
                        ⚠️ Triage: {{ count($unmappedList) }} Variasi Defect Belum Terpetakan di Database
                    </h2>
                </div>
                <div class="w-full sm:w-64">
                    <input type="text"
                        wire:model.live.debounce.300ms="unmappedSearch"
                        placeholder="Cari variasi belum terpetakan..."
                        class="w-full text-xs font-semibold px-3 py-1.5 rounded-lg border-amber-300 bg-white focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <p class="text-xs text-amber-800">
                Variasi defect berikut ditemukan pada data produksi riil tetapi belum memiliki nama katalog standar. Anda dapat memetakan ke nama standar yang sudah ada atau mempromosikannya sebagai defect standar baru.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
                @foreach($unmappedList as $item)
                    <div class="bg-white p-3 rounded-xl border border-amber-200 shadow-2xs flex flex-col justify-between gap-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-black text-xs sm:text-sm text-gray-800 uppercase tracking-wide">
                                    {{ $item['raw_name'] }}
                                </span>
                                <span class="block text-[11px] font-bold text-gray-400 mt-0.5">
                                    {{ number_format($item['count']) }} record di DB
                                </span>
                            </div>
                            <button type="button"
                                wire:click="promoteUnmappedToCatalog('{{ addslashes($item['raw_name']) }}')"
                                wire:confirm="Promosikan '{{ $item['raw_name'] }}' menjadi defect standar baru?"
                                class="text-[10px] font-extrabold uppercase px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200 transition cursor-pointer shrink-0">
                                + Standar Baru
                            </button>
                        </div>

                        <!-- 1-Click Assign Dropdown -->
                        <div class="flex items-center gap-1.5 pt-1.5 border-t border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 uppercase shrink-0">Petakan Ke:</span>
                            <select
                                onchange="if(this.value) { @this.assignUnmappedToCatalog('{{ addslashes($item['raw_name']) }}', this.value); this.value = ''; }"
                                class="w-full text-xs font-bold py-1 px-2 rounded border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                <option value="">-- Pilih Defect Standar --</option>
                                @foreach($allCatalogs as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- SECTION 2: CANONICAL DEFECT CATALOG & ALIASES -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden space-y-4 p-5">

        <!-- Controls Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100">
            <!-- Search -->
            <div class="relative w-full md:w-80">
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari defect standar atau alias..."
                    class="w-full text-xs sm:text-sm pl-9 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Quick Add Catalog Form -->
            <form wire:submit.prevent="createCatalog" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                <input type="text"
                    wire:model="newCatalogName"
                    placeholder="Nama Defect Baru (e.g. DENTED)"
                    class="text-xs uppercase font-bold px-3 py-2 rounded-xl border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                <input type="text"
                    wire:model="newCatalogDescription"
                    placeholder="Deskripsi singkat (opsional)"
                    class="text-xs px-3 py-2 rounded-xl border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer shrink-0">
                    + Tambah Katalog
                </button>
            </form>
        </div>

        @error('newCatalogName')
            <p class="text-xs font-bold text-rose-600">{{ $message }}</p>
        @enderror

        <!-- Catalogs & Aliases Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/70 text-gray-600 uppercase text-[10px] font-black tracking-wider">
                        <th class="py-3 px-4 w-20 text-center">Status</th>
                        <th class="py-3 px-4 w-48">Nama Standar</th>
                        <th class="py-3 px-4">Alias / Variasi Penulisan Terdaftar</th>
                        <th class="py-3 px-4 w-28 text-center">Record DB</th>
                        <th class="py-3 px-4 w-28 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($catalogs as $cat)
                        <tr class="hover:bg-gray-50/60 transition group">
                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button"
                                    wire:click="toggleCatalogActive({{ $cat->id }})"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition
                                    {{ $cat->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>

                            <!-- Canonical Name & Desc -->
                            <td class="py-3.5 px-4">
                                <span class="font-black text-gray-900 block tracking-wide">
                                    {{ $cat->name }}
                                </span>
                                @if($cat->description)
                                    <span class="text-[11px] text-gray-400 font-medium block mt-0.5 leading-relaxed">
                                        {{ $cat->description }}
                                    </span>
                                @endif
                            </td>

                            <!-- Aliases Chips -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse($cat->aliases as $alias)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                            <span>{{ $alias->alias }}</span>
                                            <button type="button"
                                                wire:click="removeAlias({{ $alias->id }})"
                                                wire:confirm="Hapus alias '{{ $alias->alias }}'?"
                                                class="text-slate-400 hover:text-rose-600 transition font-black ml-0.5 text-[11px] cursor-pointer"
                                                title="Hapus alias">&times;</button>
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">Belum ada alias terdaftar</span>
                                    @endforelse

                                    <button type="button"
                                        wire:click="openAddAliasModal({{ $cat->id }})"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold text-indigo-600 hover:bg-indigo-50 border border-indigo-200 transition cursor-pointer">
                                        + Tambah Alias
                                    </button>
                                </div>
                            </td>

                            <!-- Record Count in DB -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-xs {{ ($recordCounts[$cat->name] ?? 0) > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                                    {{ number_format($recordCounts[$cat->name] ?? 0) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <button type="button"
                                    wire:click="deleteCatalog({{ $cat->id }})"
                                    wire:confirm="Yakin ingin menghapus defect '{{ $cat->name }}'? Seluruh alias di bawahnya juga akan terhapus."
                                    class="text-xs font-bold text-rose-500 hover:text-rose-700 hover:underline cursor-pointer">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400 font-medium">
                                Tidak ada data katalog defect yang cocok dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pt-4 border-t border-gray-100">
            {{ $catalogs->links() }}
        </div>
    </div>

    <!-- MODAL: ADD ALIAS -->
    @if($selectedCatalogId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4 border border-gray-100"
                @click.away="@this.closeAddAliasModal()">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-base font-black text-gray-900">
                        Tambah Alias untuk "{{ optional(\App\Models\SecondProcessDefectCatalog::find($selectedCatalogId))->name }}"
                    </h3>
                    <button type="button" wire:click="closeAddAliasModal" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
                </div>

                <form wire:submit.prevent="addAlias" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">
                            Nama Alias / Variasi Penulisan:
                        </label>
                        <input type="text"
                            wire:model="newAliasInput"
                            placeholder="Contoh: SCRATH, AMPLASAN, SAGING..."
                            class="w-full text-xs sm:text-sm uppercase font-bold py-2.5 px-3 rounded-xl border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('newAliasInput')
                            <p class="text-xs font-bold text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-xs text-gray-500">
                        Setiap kali operator menginput variasi ini, sistem akan otomatis menstandarisasikannya menjadi nama katalog standar di atas.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeAddAliasModal"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer">
                            Simpan Alias
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
