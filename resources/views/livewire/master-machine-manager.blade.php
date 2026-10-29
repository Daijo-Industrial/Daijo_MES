<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-[1400px] w-full mx-auto space-y-6">

        {{-- Top Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Master Mesin & Kontrol Dashboard</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Daily Production Dashboard Filter
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Real-Time Toggle
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">
                    Kelola data mesin injeksi dan tentukan mesin mana saja yang ditampilkan (check / uncheck) pada dropdown filter dashboard produksi.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" wire:click="syncFromOperatorUsers(true)" wire:loading.attr="disabled"
                        title="Tarik semua akun User dengan role OPERATOR ke daftar mesin master"
                        class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-black flex items-center transition shadow-xs">
                    <svg wire:loading.remove wire:target="syncFromOperatorUsers" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <svg wire:loading wire:target="syncFromOperatorUsers" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-indigo-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>🔄 SINKRON USER OPERATOR</span>
                </button>
                <a href="{{ route('djoni.dashboard') }}" target="_blank"
                   class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-black flex items-center transition shadow-xs">
                    <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>BUKA DASHBOARD PRODUKSI ↗</span>
                </a>
                <button type="button" wire:click="openCreateModal"
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black flex items-center transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ TAMBAH MESIN BARU</span>
                </button>
            </div>
        </div>

        {{-- KPI Summary Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
            {{-- Total Machines --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Mesin</div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-gray-900">{{ number_format($stats['total']) }}</span>
                    <span class="text-xs font-semibold text-gray-500">Unit</span>
                </div>
                <div class="mt-1 text-[11px] text-gray-400">Terdaftar di master</div>
            </div>

            {{-- Active / Shown --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100 bg-gradient-to-br from-white to-emerald-50/30 flex flex-col justify-between">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Ditampilkan</span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-mono font-bold">✓ Active</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-900">{{ number_format($stats['active']) }}</span>
                    <span class="text-xs font-semibold text-emerald-700">Mesin</span>
                </div>
                <div class="mt-1 text-[11px] text-emerald-600/80">Muncul di filter dashboard</div>
            </div>

            {{-- Inactive / Hidden --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 bg-gradient-to-br from-white to-gray-50/50 flex flex-col justify-between">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center justify-between">
                    <span>Disembunyikan</span>
                    <span class="text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded font-mono font-bold">✕ Off</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-gray-600">{{ number_format($stats['inactive']) }}</span>
                    <span class="text-xs font-semibold text-gray-400">Mesin</span>
                </div>
                <div class="mt-1 text-[11px] text-gray-500">Tidak muncul di filter</div>
            </div>

            {{-- Plant KBN --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-blue-100 bg-gradient-to-br from-white to-blue-50/30 flex flex-col justify-between">
                <div class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Plant KBN</span>
                    <span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono">Cakung</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-blue-900">{{ number_format($stats['kbn']) }}</span>
                    <span class="text-xs font-semibold text-blue-700">Mesin</span>
                </div>
                <div class="mt-1 text-[11px] text-blue-600/80">Kode reguler (0150 - 1300)</div>
            </div>

            {{-- Plant Karawang --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-purple-100 bg-gradient-to-br from-white to-purple-50/30 flex flex-col justify-between col-span-2 md:col-span-1">
                <div class="text-xs font-bold text-purple-700 uppercase tracking-wider flex items-center justify-between">
                    <span>Plant Karawang</span>
                    <span class="text-[10px] bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded font-mono font-bold">ZONA K</span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-purple-900">{{ number_format($stats['karawang']) }}</span>
                    <span class="text-xs font-semibold text-purple-700">Mesin</span>
                </div>
                <div class="mt-1 text-[11px] text-purple-600/80">Awalan 'K' (K0450 - K2800)</div>
            </div>
        </div>

        {{-- Filter & Control Bar --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                {{-- Search Box --}}
                <div class="md:col-span-5">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Cari nama mesin (misal K1400C, 0450H), brand, tonase..."
                               class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                {{-- Plant Filter --}}
                <div class="md:col-span-3">
                    <select wire:model.live="plantFilter"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">🏭 Semua Plant (KBN & Karawang)</option>
                        <option value="KBN">🏢 Plant KBN (Jakarta)</option>
                        <option value="KARAWANG">🏭 Plant Karawang (Mesin K)</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="md:col-span-2">
                    <select wire:model.live="statusFilter"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        <option value="ALL">Semua Status</option>
                        <option value="ACTIVE">✅ Ditampilkan</option>
                        <option value="INACTIVE">❌ Disembunyikan</option>
                    </select>
                </div>

                {{-- Bulk Action Buttons --}}
                <div class="md:col-span-2 flex items-center gap-1.5 justify-end">
                    <button type="button" wire:click="setAllActive(true)"
                            title="Centang / Tampilkan semua mesin yang terfilter"
                            class="px-2.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-[11px] font-bold transition">
                        ✓ Centang Semua
                    </button>
                    <button type="button" wire:click="setAllActive(false)"
                            title="Hilangkan centang / Sembunyikan semua mesin yang terfilter"
                            class="px-2.5 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-[11px] font-bold transition">
                        ✕ Hapus Centang
                    </button>
                </div>
            </div>
        </div>

        {{-- Machines Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-gray-800">Daftar Mesin Injeksi</h2>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-mono">
                        {{ $machines->total() }} Mesin Ditemukan
                    </span>
                </div>
                <div class="text-xs text-gray-400">
                    💡 Klik tombol checkbox/switch untuk menampilkan atau menyembunyikan mesin secara instan.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-28">Status Tampil</th>
                            <th class="py-3.5 px-4">Nama Mesin</th>
                            <th class="py-3.5 px-4">Plant</th>
                            <th class="py-3.5 px-4">Tonase / Spesifikasi</th>
                            <th class="py-3.5 px-4">Brand / Merk</th>
                            <th class="py-3.5 px-4">Line / Zona</th>
                            <th class="py-3.5 px-4">Keterangan</th>
                            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($machines as $m)
                            <tr class="hover:bg-blue-50/20 transition-colors {{ $m->is_active ? '' : 'bg-gray-50/60 opacity-60' }}">
                                {{-- Checkbox / Toggle Switch --}}
                                <td class="py-3 px-4 text-center">
                                    <button type="button" wire:click="toggleActive({{ $m->id }})"
                                            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-black transition-all cursor-pointer {{ $m->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border border-emerald-300' : 'bg-gray-200 text-gray-600 hover:bg-gray-300 border border-gray-300' }}">
                                        @if($m->is_active)
                                            <span class="w-2 h-2 rounded-full bg-emerald-600 mr-1.5 animate-pulse"></span>
                                            <span>✓ SHOW</span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-gray-400 mr-1.5"></span>
                                            <span>✕ HIDE</span>
                                        @endif
                                    </button>
                                </td>

                                {{-- Machine Name --}}
                                <td class="py-3 px-4 font-mono font-black text-sm text-gray-900">
                                    {{ $m->name }}
                                </td>

                                {{-- Plant --}}
                                <td class="py-3 px-4">
                                    @if($m->plant === 'KARAWANG')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-800 border border-purple-200 font-mono">
                                            🏭 KARAWANG
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200 font-mono">
                                            🏢 KBN
                                        </span>
                                    @endif
                                </td>

                                {{-- Tonnage --}}
                                <td class="py-3 px-4 text-gray-700 font-mono">
                                    {{ $m->tonnage ?: '-' }}
                                </td>

                                {{-- Brand --}}
                                <td class="py-3 px-4 text-gray-700">
                                    {{ $m->brand ?: '-' }}
                                </td>

                                {{-- Line / Zone --}}
                                <td class="py-3 px-4 text-gray-700">
                                    {{ $m->line_or_zone ?: '-' }}
                                </td>

                                {{-- Description --}}
                                <td class="py-3 px-4 text-gray-500 max-w-xs truncate" title="{{ $m->description }}">
                                    {{ $m->description ?: '-' }}
                                </td>

                                {{-- Action --}}
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" wire:click="openEditModal({{ $m->id }})"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
                                                title="Edit Mesin">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" wire:click="deleteMachine({{ $m->id }})"
                                                wire:confirm="Yakin ingin menghapus mesin {{ $m->name }} dari master?"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                                                title="Hapus Mesin">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-gray-400">
                                    <div class="w-12 h-12 mx-auto mb-2 text-gray-300">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="font-bold">Tidak ada mesin yang cocok dengan kriteria pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($machines->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $machines->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- Modal Tambah / Edit Mesin --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black text-gray-900">
                        {{ $editingId ? 'Edit Mesin Injeksi' : '+ Tambah Mesin Injeksi Baru' }}
                    </h3>
                    <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-lg font-bold">
                        ✕
                    </button>
                </div>

                <form wire:submit="saveMachine" class="space-y-4">
                    {{-- Machine Name --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Nama Mesin <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model.live="name" placeholder="Misal: 0450H, K1400C, 1500A"
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Mesin dengan awalan 'K' otomatis ditetapkan sebagai Plant Karawang.</span>
                    </div>

                    {{-- Plant --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Plant Lokasi <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model="plant" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="KBN">🏢 Plant KBN (Jakarta)</option>
                            <option value="KARAWANG">🏭 Plant Karawang (Mesin K)</option>
                        </select>
                        @error('plant') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Tonnage --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tonase</label>
                            <input type="text" wire:model="tonnage" placeholder="Misal: 450 Ton"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            @error('tonnage') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Brand --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Brand / Merk</label>
                            <input type="text" wire:model="brand" placeholder="Misal: NISSEI, TOSHIBA"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            @error('brand') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Line / Zone --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Line / Zona</label>
                        <input type="text" wire:model="line_or_zone" placeholder="Misal: Zona A, Line 2"
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @error('line_or_zone') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Keterangan Tambahan</label>
                        <textarea wire:model="description" rows="2" placeholder="Catatan operasional mesin..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                        @error('description') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Active Toggle Checkbox --}}
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">Tampilkan di Dashboard</span>
                            <span class="text-[11px] text-gray-500">Jika dicentang, mesin akan muncul di dropdown Filter Dashboard.</span>
                        </div>
                        <input type="checkbox" wire:model="is_active" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    </div>

                    {{-- Modal Actions --}}
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="closeModal"
                                class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                            {{ $editingId ? 'Simpan Perubahan' : '+ Tambah Mesin' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
