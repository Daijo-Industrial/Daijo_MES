<div x-data="{ showFilters: false, showKpi: false }" class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </span>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Mould Maintenance Checklist</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Doc No: <span class="font-semibold text-slate-700">DI-F-P/PD/05/MTM-043</span>, Rev 00
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Filter Toggle Button --}}
            <button type="button" @click="showFilters = !showFilters"
                :class="showFilters ? 'bg-blue-50 text-blue-700 border-blue-200 ring-2 ring-blue-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 text-sm font-semibold rounded-xl border shadow-sm transition-all focus:outline-none">
                <svg class="w-4 h-4" :class="showFilters ? 'text-blue-600' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span x-text="showFilters ? 'Sembunyikan Filter' : 'Filter'"></span>
            </button>

            {{-- KPI Toggle Button --}}
            <button type="button" @click="showKpi = !showKpi"
                :class="showKpi ? 'bg-indigo-50 text-indigo-700 border-indigo-200 ring-2 ring-indigo-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 text-sm font-semibold rounded-xl border shadow-sm transition-all focus:outline-none">
                <svg class="w-4 h-4" :class="showKpi ? 'text-indigo-600' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span x-text="showKpi ? 'Sembunyikan KPI' : 'KPI Summary'"></span>
            </button>
            @if (auth()->user()?->hasRole('SUPER-ADMIN'))
                <button wire:click="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </button>
            @endif
            <a href="{{ route('mould-checklist.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Checklist Baru
            </a>
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

    <!-- Collapsible KPI Section -->
    <div x-show="showKpi" x-cloak x-transition>
        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Check Sheet</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalChecklists) }}</p>
                </div>
                <span class="p-3 bg-slate-100 text-slate-600 rounded-xl">📋</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-amber-600 uppercase tracking-wider">Menunggu Approval</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($totalSubmitted) }}</p>
                </div>
                <span class="p-3 bg-amber-50 text-amber-600 rounded-xl">⏳</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Approved (Selesai)</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($totalApproved) }}</p>
                </div>
                <span class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">✓</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Draft</p>
                    <p class="text-2xl font-bold text-slate-700 mt-1">{{ number_format($totalDraft) }}</p>
                </div>
                <span class="p-3 bg-slate-100 text-slate-500 rounded-xl">📝</span>
            </div>
        </div>
    </div>

    <!-- Collapsible Filters Section -->
    <div x-show="showFilters" x-cloak x-transition>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Cari Part / Mould / Teknisi</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Ketik kata kunci pencarian..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="filterDateFrom"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="filterDateTo"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status Dokumen</label>
                <select wire:model.live="filterStatus"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="">Semua Status</option>
                    <option value="DRAFT">DRAFT</option>
                    <option value="SUBMITTED">SUBMITTED (Menunggu Approval)</option>
                    <option value="APPROVED">APPROVED (Selesai)</option>
                    <option value="REJECTED">REJECTED (Ditolak)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Checklists Data Container (Responsive: Card list on Mobile/Tablet, Fixed Table on Desktop) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        {{-- Mobile & Tablet Cards View (< xl) --}}
        <div class="block xl:hidden divide-y divide-slate-200">
            @forelse ($checklists as $c)
                @php
                    $ngCount = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();
                @endphp
                <div class="p-4 sm:p-6 hover:bg-slate-50/80 transition-colors space-y-3.5">
                    {{-- Row 1: Header Strip - Highlighted Report ID & Date + Status/Result --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 gap-2">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            {{-- Prominent Report ID Badge --}}
                            <span class="inline-flex items-center px-2.5 py-1 bg-slate-900 text-white font-mono font-extrabold text-sm sm:text-base rounded-lg shadow-xs">
                                #{{ $c->id }}
                            </span>

                            {{-- Highlighted Date & Working Hours --}}
                            <div class="inline-flex items-center gap-2 text-sm sm:text-base text-slate-900">
                                <span class="font-extrabold">
                                    {{ $c->check_date ? $c->check_date->translatedFormat('d M Y') : '-' }}
                                </span>
                                @if ($c->start_time && $c->end_time)
                                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">
                                        ({{ $c->start_time }}-{{ $c->end_time }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Right Status Badges --}}
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($ngCount > 0)
                                <span class="px-2.5 py-1 text-xs sm:text-sm font-bold bg-rose-100 text-rose-700 rounded-full">
                                    {{ $ngCount }} NG
                                </span>
                            @endif

                            @if ($c->status === 'APPROVED')
                                <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-emerald-100 text-emerald-800">
                                    Approved
                                </span>
                            @elseif ($c->status === 'SUBMITTED')
                                <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-amber-100 text-amber-800">
                                    Menunggu
                                </span>
                            @elseif ($c->status === 'REJECTED')
                                <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-rose-100 text-rose-800">
                                    Reject
                                </span>
                            @else
                                <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-slate-100 text-slate-700">
                                    Draft
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Row 2: Mould & Machine Badges --}}
                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                        <span class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 font-black text-base sm:text-lg rounded-xl border border-indigo-200 shadow-2xs">
                            {{ $c->mould_no }}
                        </span>
                        <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs sm:text-sm font-bold rounded-lg">
                            {{ $c->customer }}
                        </span>
                        @if ($c->machine_number)
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs sm:text-sm font-bold rounded-lg">
                                Mesin: {{ $c->machine_number }}
                            </span>
                        @endif
                        @if ($c->model)
                            <span class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs sm:text-sm font-semibold rounded-lg">
                                Model: {{ $c->model }}
                            </span>
                        @endif
                    </div>

                    {{-- Row 3: Part No & Name Box --}}
                    <div class="bg-slate-50 rounded-xl p-3 sm:p-3.5 border border-slate-200 space-y-1">
                        <div class="text-xs sm:text-sm font-mono font-bold text-slate-600">{{ $c->part_no }}</div>
                        <div class="text-base sm:text-lg font-bold text-slate-900 leading-snug">{{ $c->part_name }}</div>
                    </div>

                    {{-- Row 4: Meta info (PIC Teknisi & Creator) --}}
                    <div class="flex items-center justify-between text-xs sm:text-sm text-slate-700">
                        <span>Teknisi (PIC): <strong class="text-slate-900 text-sm sm:text-base">{{ $c->technician_name }}</strong></span>
                        @if ($c->issued_name)
                            <span class="text-slate-500 text-xs sm:text-sm">Issued by: <strong>{{ $c->issued_name }}</strong></span>
                        @endif
                    </div>

                    {{-- Row 5: Action Buttons (Touch Friendly 42px+) --}}
                    <div class="pt-1.5 flex items-center justify-between gap-2 sm:gap-2.5">
                        <a href="{{ route('mould-checklist.show', $c->id) }}" wire:navigate
                            class="flex-1 py-2.5 px-3 sm:px-4 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-sm sm:text-base rounded-xl transition-colors inline-flex items-center justify-center gap-1.5 sm:gap-2 shadow-2xs">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Lihat Detail<span class="hidden sm:inline"> & Tanda Tangan</span></span>
                        </a>

                        @if ($c->status === 'DRAFT' || $c->status === 'REJECTED')
                            <a href="{{ route('mould-checklist.edit', $c->id) }}" wire:navigate
                                class="py-2.5 px-3 sm:px-3.5 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-sm sm:text-base transition-colors inline-flex items-center gap-1.5 shadow-2xs shrink-0" title="Edit Checklist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </a>
                        @endif

                        @if ($c->status === 'DRAFT' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))
                            <button wire:click="deleteChecklist({{ $c->id }})"
                                onclick="confirm('Hapus checklist ini?') || event.stopImmediatePropagation()"
                                class="py-2.5 px-3 sm:px-3.5 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl font-bold text-sm sm:text-base transition-colors inline-flex items-center gap-1.5 shadow-2xs shrink-0" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-slate-400">
                    <p class="text-base font-semibold">Belum ada check sheet mould pada filter ini.</p>
                    <p class="text-sm text-slate-500 mt-1">Gunakan tombol "Buat Checklist Baru" di atas untuk memulai check sheet.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table View (>= xl) --}}
        <div class="hidden xl:block w-full overflow-x-auto">
            <table class="w-full min-w-[1000px] table-fixed divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="w-[6%] px-3 py-3.5 text-center">ID</th>
                        <th class="w-[10%] px-3 py-3.5 text-left">Tanggal</th>
                        <th class="w-[10%] px-3 py-3.5 text-left">Mould No</th>
                        <th class="w-[18%] px-3 py-3.5 text-left">Part No & Name</th>
                        <th class="w-[7%] px-3 py-3.5 text-center">Customer</th>
                        <th class="w-[6%] px-3 py-3.5 text-center">Mesin</th>
                        <th class="w-[10%] px-3 py-3.5 text-left">Teknisi</th>
                        <th class="w-[6%] px-3 py-3.5 text-center">Hasil</th>
                        <th class="w-[8%] px-3 py-3.5 text-center">Status</th>
                        <th class="w-[19%] px-2 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($checklists as $c)
                        @php
                            $ngCount = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-3 py-3.5 text-center">
                                <span class="inline-block px-2 py-0.5 bg-slate-900 text-white font-mono font-bold text-xs rounded">
                                    #{{ $c->id }}
                                </span>
                            </td>
                            <td class="px-3 py-3.5 font-medium text-slate-900">
                                <span class="text-sm font-bold whitespace-nowrap block">{{ $c->check_date ? $c->check_date->format('d/m/Y') : '-' }}</span>
                                @if ($c->start_time && $c->end_time)
                                    <span class="block text-xs text-slate-500 font-mono whitespace-nowrap">{{ $c->start_time }}-{{ $c->end_time }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 font-bold text-indigo-700 text-sm truncate" title="{{ $c->mould_no }}">{{ $c->mould_no }}</td>
                            <td class="px-3 py-3.5">
                                <span class="block font-mono text-xs text-slate-500 font-bold truncate" title="{{ $c->part_no }}">{{ $c->part_no }}</span>
                                <span class="font-bold text-slate-900 text-sm truncate block" title="{{ $c->part_name }}">{{ $c->part_name }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                <span class="px-2 py-1 text-xs font-bold bg-slate-100 text-slate-800 rounded truncate block" title="{{ $c->customer }}">
                                    {{ $c->customer }}
                                </span>
                            </td>
                            <td class="px-3 py-3.5 font-mono text-xs font-bold text-slate-800 text-center truncate" title="{{ $c->machine_number ?: '-' }}">{{ $c->machine_number ?: '-' }}</td>
                            <td class="px-3 py-3.5 text-sm font-semibold text-slate-900 truncate" title="{{ $c->technician_name }}">{{ $c->technician_name }}</td>
                            <td class="px-3 py-3.5 text-center">
                                @if ($ngCount > 0)
                                    <span class="inline-flex items-center gap-0.5 px-2.5 py-1 text-xs font-bold bg-rose-100 text-rose-700 rounded-full" title="{{ $ngCount }} NG">
                                        {{ $ngCount }} NG
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-0.5 px-2.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full">
                                        ✓ OK
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                @if ($c->status === 'APPROVED')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 whitespace-nowrap">
                                        ✓ Approved
                                    </span>
                                @elseif ($c->status === 'SUBMITTED')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 whitespace-nowrap" title="Menunggu Approval">
                                        Menunggu
                                    </span>
                                @elseif ($c->status === 'REJECTED')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800 whitespace-nowrap" title="{{ $c->rejection_note }}">
                                        Reject
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-700 whitespace-nowrap">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('mould-checklist.show', $c->id) }}" wire:navigate
                                        class="px-2.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs rounded-lg transition-colors inline-flex items-center gap-1 shadow-2xs shrink-0" title="Detail / Print">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>

                                    @if ($c->status === 'DRAFT' || $c->status === 'REJECTED')
                                        <a href="{{ route('mould-checklist.edit', $c->id) }}" wire:navigate
                                            class="p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-lg transition-colors shrink-0" title="Edit Checklist">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif

                                    @if ($c->status === 'DRAFT' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))
                                        <button wire:click="deleteChecklist({{ $c->id }})"
                                            onclick="confirm('Hapus checklist ini?') || event.stopImmediatePropagation()"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors shrink-0" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-base font-semibold">Belum ada check sheet mould pada filter ini.</p>
                                <p class="text-sm text-slate-500 mt-1">Gunakan tombol "Buat Checklist Baru" di atas untuk memulai check sheet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($checklists->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $checklists->links() }}
            </div>
        @endif
    </div>
</div>
