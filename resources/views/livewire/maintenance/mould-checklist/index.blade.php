<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
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
                Form List Check Sheet Mould Harian (Doc No: <span class="font-semibold text-slate-700">DI-F-P/PD/05/MTM-043</span>, Rev 00).
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="exportExcel"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </button>
            <a href="{{ route('mould-checklist.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                + Buat Checklist Baru
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

    <!-- Filters Section -->
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

    <!-- Checklists Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-700">
                    <tr>
                        <th class="px-4 py-3.5 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Mould No</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Part Number & Name</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Customer</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Mesin</th>
                        <th class="px-4 py-3.5 text-left font-semibold">Teknisi</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Hasil Check</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($checklists as $c)
                        @php
                            $ngCount = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                                {{ $c->check_date ? $c->check_date->format('d/m/Y') : '-' }}
                                @if ($c->start_time && $c->end_time)
                                    <span class="block text-xs text-slate-400 font-mono">{{ $c->start_time }} - {{ $c->end_time }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-blue-700 whitespace-nowrap">{{ $c->mould_no }}</td>
                            <td class="px-4 py-3 max-w-xs">
                                <span class="block font-mono text-xs text-slate-600">{{ $c->part_no }}</span>
                                <span class="font-medium text-slate-900 truncate block" title="{{ $c->part_name }}">{{ $c->part_name }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-semibold bg-slate-100 text-slate-700 rounded-md">
                                    {{ $c->customer }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">{{ $c->machine_number ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-800">{{ $c->technician_name }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($ngCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-rose-100 text-rose-700 rounded-full">
                                        ⚠️ {{ $ngCount }} NG
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full">
                                        ✓ Normal
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($c->status === 'APPROVED')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                        ✓ Approved
                                    </span>
                                @elseif ($c->status === 'SUBMITTED')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                                        ⏳ Menunggu Approval
                                    </span>
                                @elseif ($c->status === 'REJECTED')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800" title="{{ $c->rejection_note }}">
                                        ⛔ Rejected
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('mould-checklist.show', $c->id) }}" wire:navigate
                                        class="px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs rounded-lg transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Detail / Print
                                    </a>

                                    @if ($c->status === 'DRAFT' || $c->status === 'REJECTED')
                                        <a href="{{ route('mould-checklist.edit', $c->id) }}" wire:navigate
                                            class="p-1 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Checklist">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif

                                    @if ($c->status === 'DRAFT' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))
                                        <button wire:click="deleteChecklist({{ $c->id }})"
                                            onclick="confirm('Hapus checklist ini?') || event.stopImmediatePropagation()"
                                            class="p-1 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
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
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-base font-medium">Belum ada check sheet mould pada filter ini.</p>
                                <p class="text-xs text-slate-500 mt-1">Gunakan tombol "+ Buat Checklist Baru" di atas untuk memulai check sheet.</p>
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
