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
                    <p class="text-xs text-slate-500 mt-0.5">Pemeriksaan dan pelumasan harian mould injection</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 font-mono">
                        {{ $isEdit ? 'EDIT CHECK SHEET' : 'CHECK SHEET BARU' }}
                    </span>
                </div>
            </div>

            <!-- Header Parameters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search & Select Master Mould -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Cari Master Mould Tooling
                        <span class="text-indigo-600 font-normal lowercase">(ketik mould no atau part no)</span>
                    </label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.250ms="mould_search"
                            wire:focus="$set('showMouldDropdown', true)"
                            placeholder="Ketik Mould No (contoh: IK-MM) atau Part No..."
                            class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Dropdown Results -->
                    @if ($showMouldDropdown && $mouldSearchResults->count() > 0)
                        <div class="absolute z-30 mt-1 w-full bg-white rounded-xl shadow-xl border border-slate-200 max-h-60 overflow-y-auto divide-y divide-slate-100">
                            @foreach ($mouldSearchResults as $result)
                                <button type="button" wire:click="selectMould({{ $result->id }})"
                                    class="w-full p-2.5 text-left hover:bg-indigo-50/70 transition-colors flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-indigo-700">{{ $result->mould_no }}</p>
                                        <p class="text-xs font-mono text-slate-800">{{ $result->part_no }} - {{ $result->part_name }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded">
                                        {{ $result->customer }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tanggal Check -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Pengerjaan *</label>
                    <input type="date" wire:model="check_date"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    @error('check_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Customer -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Customer *</label>
                    <input type="text" wire:model="customer" placeholder="Nama customer..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-slate-50">
                    @error('customer') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Mould No -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mould Number *</label>
                    <input type="text" wire:model="mould_no" placeholder="Contoh: IK-MM-005-010"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-bold text-indigo-800">
                    @error('mould_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Part No -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Part Number *</label>
                    <input type="text" wire:model="part_no" placeholder="Contoh: 401-76934B001P"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                    @error('part_no') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Part Name -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Part Name *</label>
                    <input type="text" wire:model="part_name" placeholder="Nama part..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    @error('part_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
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

                <!-- Model -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Model / Proyek</label>
                    <input type="text" wire:model="model" placeholder="Contoh: 5H45, 4L45W..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Teknisi Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Teknisi (PIC) *</label>
                    <input type="text" wire:model="technician_name" placeholder="Contoh: Maulana + Sorie"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    @error('technician_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                <!-- Jam Pengerjaan (Start s/d End) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jam Pengerjaan</label>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="start_time" placeholder="14:00" class="w-1/2 px-2.5 py-2.5 border border-slate-300 rounded-xl text-sm font-mono text-center">
                        <span class="text-xs text-slate-400 font-bold">s/d</span>
                        <input type="text" wire:model="end_time" placeholder="20:00" class="w-1/2 px-2.5 py-2.5 border border-slate-300 rounded-xl text-sm font-mono text-center">
                    </div>
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
        <div class="bg-white rounded-2xl p-4 shadow-lg border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-500">
                <span>Catatan: Menekan <strong class="text-slate-700">"Submit & Tanda Tangan"</strong> akan mencatat tanda tangan digital <strong class="text-indigo-600">{{ auth()->user()?->name }}</strong> sebagai Issued By dan mengubah status menjadi SUBMITTED untuk diverifikasi Leader.</span>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <button type="button" wire:click="saveDraft"
                    class="px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                    Simpan Draft
                </button>
                <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-blue-500 focus:outline-none flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Submit & Tanda Tangan (Issued By)
                </button>
            </div>
        </div>
    </form>
</div>
