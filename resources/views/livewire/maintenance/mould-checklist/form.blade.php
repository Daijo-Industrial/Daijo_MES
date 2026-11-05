<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <!-- Top Nav / Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('mould-checklist.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Checklist
        </a>
        <div class="text-right">
            <span class="text-xs font-mono text-slate-400">Doc: {{ $doc_number }} | Rev: {{ $revision }}</span>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('info'))
        <div class="p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-medium">
            {{ session('info') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="submitAndSign" class="space-y-6">
        <!-- Document Header Box -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="border-b border-slate-200 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 uppercase tracking-tight">Form List Check Sheet Mould Maintenance</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pemeriksaan dan pelumasan mould injection</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 font-mono">
                        {{ $isEdit ? 'EDIT CHECK SHEET' : 'CHECK SHEET BARU' }}
                    </span>
                </div>
            </div>

            <!-- Mould & Tooling Selection Section -->
            <div class="mb-6">
                @if ($mould_no && ! $isManualMould)
                    {{-- Locked Selected Mould Card --}}
                    <div class="bg-gradient-to-r from-indigo-50/90 via-blue-50/60 to-slate-50 border border-indigo-200 rounded-2xl p-4 sm:p-5 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-600 text-white text-xs sm:text-sm font-extrabold rounded-lg shadow-xs">
                                        <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        {{ $mould_no }}
                                    </span>
                                    <span class="px-2.5 py-1 text-xs font-bold bg-blue-100 text-blue-800 rounded-md border border-blue-200">
                                        {{ $customer }}
                                    </span>
                                    @if ($model)
                                        <span class="px-2 py-0.5 text-xs font-semibold bg-white text-slate-700 rounded border border-slate-200">
                                            Model: {{ $model }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        ✓ Master Mould Terverifikasi
                                    </span>
                                </div>
                                <div class="pt-1">
                                    <span class="font-mono text-xs font-bold text-slate-600 tracking-wide">{{ $part_no }}</span>
                                    <h4 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ $part_name }}</h4>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <button type="button" wire:click="clearSelectedMould"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-indigo-600 text-xs font-semibold rounded-xl border border-slate-300 shadow-xs transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Ganti Mould
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($isManualMould)
                    {{-- Manual Input Fallback Mode --}}
                    <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 sm:p-5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-amber-200/60">
                            <div class="flex items-center gap-2">
                                <span class="p-1 bg-amber-100 text-amber-700 rounded-md">⚠️</span>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Mode Input Manual (Mould Belum Terdaftar)</h4>
                                    <p class="text-[11px] text-amber-700">Gunakan mode ini hanya jika mould belum terdaftar di Master Mould.</p>
                                </div>
                            </div>
                            <button type="button" wire:click="disableManualMould"
                                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 underline underline-offset-2">
                                ← Kembali ke Cari dari Master
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Customer *</label>
                                <input type="text" wire:model="customer" placeholder="Nama customer..."
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                @error('customer') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mould Number *</label>
                                <input type="text" wire:model="mould_no" placeholder="Contoh: IK-MM-005-010"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-bold text-indigo-800">
                                @error('mould_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Part Number *</label>
                                <input type="text" wire:model="part_no" placeholder="Contoh: 401-76934B001P"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-mono">
                                @error('part_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Model / Proyek</label>
                                <input type="text" wire:model="model" placeholder="Contoh: 5H45, 4L45W..."
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-4">
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Part Name *</label>
                                <input type="text" wire:model="part_name" placeholder="Nama part lengkap..."
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                @error('part_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Prominent Search Mould Bar --}}
                    <div class="relative bg-slate-50/80 border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <div>
                                <label class="block text-sm font-extrabold text-slate-900 tracking-tight">
                                    Cari & Pilih Mould dari Master Mould *
                                </label>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Ketik nomor mould (contoh: <span class="font-mono text-indigo-600 font-semibold">IK-MM</span>) atau part number untuk mengisi otomatis semua detail tooling.
                                </p>
                            </div>
                            <button type="button" wire:click="enableManualMould"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline underline-offset-2 self-start sm:self-auto">
                                Mould belum terdaftar? Input Manual
                            </button>
                        </div>

                        <div class="relative">
                            <input type="text" wire:model.live.debounce.250ms="mould_search"
                                wire:focus="$set('showMouldDropdown', true)"
                                placeholder="Ketik Mould No atau Part No di sini..."
                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-sm sm:text-base font-medium shadow-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none">
                            <svg class="w-5 h-5 text-indigo-500 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Dropdown Search Results -->
                        @if ($showMouldDropdown && $mouldSearchResults->count() > 0)
                            <div class="absolute left-4 right-4 z-30 mt-1 bg-white rounded-xl shadow-2xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100">
                                @foreach ($mouldSearchResults as $result)
                                    <button type="button" wire:click="selectMould({{ $result->id }})"
                                        class="w-full p-3 text-left hover:bg-indigo-50/80 transition-colors flex items-center justify-between group">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-sm text-indigo-700 group-hover:text-indigo-900">{{ $result->mould_no }}</span>
                                                <span class="font-mono text-xs text-slate-500">({{ $result->part_no }})</span>
                                                @if ($result->machine_number)
                                                    <span class="text-[11px] font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{{ $result->machine_number }}</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-slate-800 font-medium line-clamp-1">{{ $result->part_name }}</p>
                                        </div>
                                        <span class="px-2.5 py-1 bg-slate-100 group-hover:bg-indigo-100 text-slate-700 group-hover:text-indigo-800 text-xs font-bold rounded-lg shrink-0">
                                            {{ $result->customer }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($showMouldDropdown && strlen($mould_search) >= 2)
                            <div class="absolute left-4 right-4 z-30 mt-1 bg-white rounded-xl shadow-xl border border-slate-200 p-4 text-center">
                                <p class="text-xs text-slate-500">Tidak ada data mould yang cocok dengan "<span class="font-semibold text-slate-700">{{ $mould_search }}</span>".</p>
                                <button type="button" wire:click="enableManualMould" class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-bold underline">
                                    Gunakan Input Manual
                                </button>
                            </div>
                        @endif

                        @error('mould_no')
                            <p class="text-xs text-rose-500 font-medium">⚠️ Silakan cari dan pilih mould terlebih dahulu, atau gunakan input manual.</p>
                        @enderror
                    </div>
                @endif
            </div>

            <!-- Operational Parameters Grid (Date, Machine, PIC, Working Hours) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                <!-- Tanggal Check -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Pengerjaan *</label>
                    <input type="date" wire:model="check_date"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    @error('check_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Mesin Injection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mesin Injection</label>
                    <input type="text" list="machineList" wire:model="machine_number" placeholder="Contoh: 1300T, MC-01..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <datalist id="machineList">
                        @foreach ($machines as $mName)
                            <option value="{{ $mName }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <!-- Teknisi Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Teknisi (PIC) *</label>
                    <input type="text" wire:model="technician_name" placeholder="Contoh: Maulana + Sorie"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    @error('technician_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Jam Pengerjaan (Start s/d End) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jam Pengerjaan</label>
                    <div class="flex items-center gap-2">
                        <input type="time" wire:model="start_time"
                            class="w-1/2 px-2.5 py-2 border border-slate-300 rounded-xl text-sm font-mono text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                        <span class="text-xs text-slate-400 font-bold shrink-0">s/d</span>
                        <input type="time" wire:model="end_time"
                            class="w-1/2 px-2.5 py-2 border border-slate-300 rounded-xl text-sm font-mono text-center focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    </div>
                    @error('start_time') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    @error('end_time') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- 16 Check Points Table Box -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900 uppercase tracking-tight">16 Check Points Pemeriksaan</h3>
                    <p class="text-xs text-slate-500">Periksa setiap poin untuk item Cleaning (Pembersihan) dan Lubricate (Pelumasan)</p>
                </div>
                <button type="button" wire:click="setAllApplicableToOk"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Set Standar Normal OK
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100/70 text-slate-700">
                        <tr>
                            <th class="w-12 px-3 py-3 text-center font-bold">NO</th>
                            <th class="px-4 py-3 text-left font-bold">POINT CHECK</th>
                            <th class="w-56 px-3 py-3 text-center font-bold">CLEANING (BERSIH)</th>
                            <th class="w-56 px-3 py-3 text-center font-bold">LUBRICATE (PELUMASAN)</th>
                            <th class="px-4 py-3 text-left font-bold">REMARK / KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        @foreach ($items as $no => $item)
                            <tr class="hover:bg-slate-50/60 transition-colors {{ $item['cleaning_status'] === 'NG' || $item['lubricate_status'] === 'NG' ? 'bg-rose-50/50' : '' }}">
                                <td class="px-3 py-3 text-center font-mono font-bold text-slate-500">{{ $no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    {{ $item['point_check'] }}
                                </td>

                                <!-- Cleaning Status Options -->
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex rounded-lg shadow-sm border border-slate-200 overflow-hidden p-0.5 bg-slate-50 gap-0.5">
                                        <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', 'OK')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['cleaning_status'] === 'OK' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            OK
                                        </button>
                                        <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', 'NG')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['cleaning_status'] === 'NG' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            NG
                                        </button>
                                        <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', '-')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['cleaning_status'] === '-' ? 'bg-slate-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            -
                                        </button>
                                    </div>
                                </td>

                                <!-- Lubricate Status Options -->
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex rounded-lg shadow-sm border border-slate-200 overflow-hidden p-0.5 bg-slate-50 gap-0.5">
                                        <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', 'OK')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['lubricate_status'] === 'OK' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            OK
                                        </button>
                                        <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', 'NG')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['lubricate_status'] === 'NG' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            NG
                                        </button>
                                        <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', '-')"
                                            class="px-3 py-1 text-xs font-bold rounded-md transition-all {{ $item['lubricate_status'] === '-' ? 'bg-slate-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200' }}">
                                            -
                                        </button>
                                    </div>
                                </td>

                                <!-- Remark input -->
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="items.{{ $no }}.remark" placeholder="Keterangan / tindakan jika ada..."
                                        class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none bg-white">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="bg-white rounded-2xl p-4 shadow-lg border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="space-y-1.5 w-full md:w-auto">
                @if (auth()->user()?->hasRole('MOULD-MAINTENANCE-LEADER'))
                    <label class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-indigo-950 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-xl cursor-pointer hover:bg-indigo-100 transition-colors">
                        <input type="checkbox" wire:model.live="auto_sign_check_by" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                        <span>Otomatis tandatangani <strong>Check By</strong> & setujui (Status: APPROVED)</span>
                    </label>
                @endif
                <p class="text-xs text-slate-500">
                    Catatan: Menekan <strong class="text-slate-700">"Submit & Tanda Tangan"</strong> akan mencatat tanda tangan digital <strong class="text-indigo-600">{{ auth()->user()?->name }}</strong> sebagai Issued By{{ auth()->user()?->hasRole('MOULD-MAINTENANCE-LEADER') ? ' (dan Check By jika opsi aktif)' : ' dan mengubah status menjadi SUBMITTED untuk diverifikasi Leader' }}.
                </p>
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <button type="button" wire:click="saveDraft"
                    class="px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                    Simpan Draft
                </button>
                <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-blue-500 focus:outline-none flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Submit & Tanda Tangan
                </button>
            </div>
        </div>
    </form>
</div>
