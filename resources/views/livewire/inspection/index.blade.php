<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6" wire:init="load">

    {{-- Header & Top Controls --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Plastic Injection IPQC Inspection Reports
            </h2>
            <p class="text-sm text-gray-500 mt-1">Daily injection quality inspections, period checks, and shift defect logs.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inspection-reports.dashboard') }}" wire:navigate
               class="inline-flex items-center px-3.5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics Dashboard
            </a>

            @can('create', \App\Models\Inspection\InspectionReport::class)
                <button type="button"
                        wire:click="handleCreateClick"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition cursor-pointer">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Report
                </button>
            @endcan
        </div>
    </div>

    {{-- Feedback Banners --}}
    @if (session()->has('success'))
        <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Filter Toolbar Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            
            {{-- Left side: Search & Rows --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Rows:</span>
                    <select wire:model.live="perPage" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative w-64 md:w-80">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search document, customer, part..."
                           class="w-full text-sm rounded-md border-gray-300 shadow-sm pl-8 pr-8 focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Right side: Column Toggles, Export, Reset --}}
            <div class="flex items-center gap-2">
                {{-- Column visibility dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        Columns
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition.opacity
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-20 p-2 space-y-1">
                        @foreach ($showCol as $key => $val)
                            <label class="flex items-center gap-2 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded cursor-pointer">
                                <input type="checkbox" wire:model.live="showCol.{{ $key }}" class="rounded text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                <span class="capitalize">{{ str_replace('_', ' ', $key) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button wire:click="exportCsv" wire:loading.attr="disabled"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    {{-- Progressive Disclosure Filter Bar Card --}}
    <div x-data="{ filtersOpen: false }" class="bg-white rounded-xl shadow-sm border border-zinc-200 p-4 space-y-3">
        {{-- Minimized Mode Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            {{-- Left: Active Filter Pills --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter:</span>
                </span>

                {{-- Date Preset Pill --}}
                @if ($preset !== 'all' || ($filters['date_from'] && $filters['date_to']))
                    @php
                        $presetLabels = [
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'this_month' => 'This Month',
                            'custom' => 'Custom Date',
                        ];
                        $dateLabel = $presetLabels[$preset] ?? 'Date Range';
                        if ($filters['date_from'] && $filters['date_to']) {
                            $dateRangeStr = \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d/m/Y') . ' - ' . \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d/m/Y');
                        } else {
                            $dateRangeStr = $dateLabel;
                        }
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Tanggal:</span> {{ $dateRangeStr }}
                        <button type="button" wire:click="removeFilter('date')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter tanggal">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-zinc-50 text-zinc-500 border border-zinc-200">
                        Semua Tanggal
                    </span>
                @endif

                {{-- Shift Pill --}}
                @if (!empty($filters['shift']))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Shift:</span> Shift {{ $filters['shift'] }}
                        <button type="button" wire:click="removeFilter('shift')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter shift">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                {{-- Customer Pill --}}
                @if (!empty($filters['customer']))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Customer:</span> {{ $filters['customer'] }}
                        <button type="button" wire:click="removeFilter('customer')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter customer">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                {{-- Part Number Pill --}}
                @if (!empty($filters['part_number']))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Part:</span> {{ $filters['part_number'] }}
                        <button type="button" wire:click="removeFilter('part_number')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter part number">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                {{-- Status Pill --}}
                @if (!empty($filters['status']))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Status:</span> {{ ucfirst($filters['status']) }}
                        <button type="button" wire:click="removeFilter('status')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter status">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                {{-- Document Number Pill --}}
                @if (!empty($filters['document_number']))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Doc:</span> {{ $filters['document_number'] }}
                        <button type="button" wire:click="removeFilter('document_number')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter dokumen">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif
            </div>

            {{-- Right: Reset All & Toggle Open/Close Button --}}
            <div class="flex items-center gap-2 ml-auto">
                @if ($this->hasActiveFilters)
                    <button type="button" wire:click="clearFilters"
                            class="px-2.5 py-1.5 text-xs font-medium text-zinc-600 hover:text-rose-600 hover:bg-rose-50 border border-dashed border-zinc-300 rounded-lg transition"
                            title="Reset semua filter ke default">
                        Reset All
                    </button>
                @endif

                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border transition shadow-sm"
                        :class="filtersOpen ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white hover:bg-zinc-50 text-zinc-800 border-zinc-300'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span x-text="filtersOpen ? 'Tutup Filter' : 'Ubah Filter'">Ubah Filter</span>
                    <svg class="w-3 h-3 text-zinc-400 transition-transform duration-200" :class="filtersOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Expanded Mode Panel --}}
        <div x-show="filtersOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="pt-3 border-t border-zinc-100 space-y-4">

            {{-- Quick Date Presets Row --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-zinc-500 uppercase flex items-center gap-1 mr-1">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Preset Tanggal:</span>
                </span>
                @php
                    $presetBase = 'px-3 py-1.5 text-xs font-bold rounded-lg border transition cursor-pointer select-none';
                    $presetActive = 'bg-zinc-900 text-white border-zinc-900 shadow-sm';
                    $presetInactive = 'bg-zinc-50 text-zinc-700 border-zinc-200 hover:bg-zinc-100';
                @endphp
                <button type="button" wire:click="setPreset('today')" class="{{ $presetBase }} {{ $preset === 'today' ? $presetActive : $presetInactive }}">
                    Today
                </button>
                <button type="button" wire:click="setPreset('yesterday')" class="{{ $presetBase }} {{ $preset === 'yesterday' ? $presetActive : $presetInactive }}">
                    Yesterday
                </button>
                <button type="button" wire:click="setPreset('this_month')" class="{{ $presetBase }} {{ $preset === 'this_month' ? $presetActive : $presetInactive }}">
                    This Month
                </button>
                <button type="button" wire:click="setPreset('all')" class="{{ $presetBase }} {{ $preset === 'all' ? $presetActive : $presetInactive }}">
                    All Dates
                </button>
            </div>

            {{-- Inputs Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Date From --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Tanggal Mulai</label>
                    <input type="date" wire:model="draftFilters.date_from"
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Tanggal Selesai</label>
                    <input type="date" wire:model="draftFilters.date_to"
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>

                {{-- Shift --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Shift</label>
                    <select wire:model="draftFilters.shift"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Shift</option>
                        @foreach ($shiftOptions as $opt)
                            <option value="{{ $opt }}">Shift {{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Status Laporan</label>
                    <select wire:model="draftFilters.status"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                    </select>
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Customer</label>
                    <input type="text" wire:model="draftFilters.customer" placeholder="Nama customer..."
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>

                {{-- Part Number --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Part Number</label>
                    <input type="text" wire:model="draftFilters.part_number" placeholder="Nomor part..."
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>

                {{-- Document Number --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Nomor Dokumen</label>
                    <input type="text" wire:model="draftFilters.document_number" placeholder="Nomor dokumen laporan..."
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>
            </div>

            {{-- Action Buttons: Apply & Reset --}}
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-100">
                <button type="button" wire:click="clearFilters"
                        class="px-4 py-2 border border-zinc-300 text-xs font-semibold text-zinc-700 bg-white hover:bg-zinc-50 rounded-lg shadow-sm transition">
                    Reset Filter
                </button>
                <button type="button" wire:click="applyFilters"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Apply Filter</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    {{-- Sortable Header Row --}}
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-14 cursor-pointer select-none hover:text-indigo-600"
                            wire:click="sortBy('id')">
                            <div class="flex items-center gap-1">
                                #
                                @if ($sortField === 'id')
                                    <svg class="w-3.5 h-3.5 text-zinc-800 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </div>
                        </th>

                        @if ($showCol['document_number'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Document No.
                            </th>
                        @endif

                    @if ($showCol['inspection_date'])
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                            wire:click="sortBy('inspection_date')">
                            <div class="flex items-center gap-1">
                                Date
                                @if ($sortField === 'inspection_date')
                                    <svg class="w-3.5 h-3.5 text-zinc-800 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                    @endif

                    @if ($showCol['shift'])
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                            wire:click="sortBy('shift')">
                            <div class="flex items-center gap-1">
                                Shift
                                @if ($sortField === 'shift')
                                    <svg class="w-3.5 h-3.5 text-zinc-800 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                    @endif

                    @if ($showCol['customer'])
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                            wire:click="sortBy('customer')">
                            <div class="flex items-center gap-1">
                                Customer
                                @if ($sortField === 'customer')
                                    <svg class="w-3.5 h-3.5 text-zinc-800 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                    @endif

                    @if ($showCol['part_number'])
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                            wire:click="sortBy('part_number')">
                            <div class="flex items-center gap-1">
                                Part Number
                                @if ($sortField === 'part_number')
                                    <svg class="w-3.5 h-3.5 text-zinc-800 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                    @endif

                    @if ($showCol['status'])
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                    @endif

                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                </tr>
            </thead>

                <tbody class="divide-y divide-gray-200 bg-white" wire:loading.class="opacity-50">
                    @if (!$ready)
                        {{-- Skeleton loading state --}}
                        @for ($i = 0; $i < min($perPage, 8); $i++)
                            <tr class="animate-pulse">
                                <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-6"></div></td>
                                @if ($showCol['document_number'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                                @endif
                                @if ($showCol['inspection_date'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                                @endif
                                @if ($showCol['shift'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-12"></div></td>
                                @endif
                                @if ($showCol['customer'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-36"></div></td>
                                @endif
                                @if ($showCol['part_number'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-28"></div></td>
                                @endif
                                @if ($showCol['status'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                                @endif
                                <td class="px-4 py-3 text-right"><div class="h-6 bg-gray-200 rounded w-14 ml-auto"></div></td>
                            </tr>
                        @endfor
                    @else
                        @forelse ($reports as $r)
                            <tr class="hover:bg-gray-50 transition" wire:key="report-{{ $r->id }}">
                                <td class="px-4 py-3 text-xs font-mono font-medium text-gray-600">{{ $r->id }}</td>

                                @if ($showCol['document_number'])
                                    <td class="px-4 py-3 text-xs font-mono font-medium text-indigo-600">
                                        <a href="{{ route('inspection-reports.show', $r->id) }}" wire:navigate class="hover:underline">
                                            {{ $r->document_number }}
                                        </a>
                                    </td>
                                @endif

                                @if ($showCol['inspection_date'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ \Illuminate\Support\Carbon::parse($r->inspection_date)->format('Y-m-d') }}
                                    </td>
                                @endif

                                @if ($showCol['shift'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Shift {{ $r->shift }}
                                        </span>
                                    </td>
                                @endif

                                @if ($showCol['customer'])
                                    <td class="px-4 py-3 text-xs text-gray-900 font-medium">
                                        {{ $r->customer }}
                                    </td>
                                @endif

                                @if ($showCol['part_number'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        <div>{{ $r->part_number }}</div>
                                        @if ($r->part_name)
                                            <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $r->part_name }}</div>
                                        @endif
                                    </td>
                                @endif

                                @if ($showCol['status'])
                                    <td class="px-4 py-3 text-xs">
                                        @if ($r->status === 'draft')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                                Draft
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                                                Submitted
                                            </span>
                                        @endif
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-right text-xs">
                                    @if ($r->status === 'draft')
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            @can('update', $r)
                                                <a href="{{ route('inspection-reports.edit', $r->id) }}" wire:navigate
                                                   class="inline-flex items-center px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold rounded text-xs transition">
                                                    Lanjutkan
                                                </a>
                                            @endcan
                                            @can('delete', $r)
                                                <button type="button"
                                                        wire:click="deleteDraft({{ $r->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus draft laporan {{ $r->document_number }} ini? Seluruh data yang belum disubmit akan dihapus permanen."
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 border border-transparent hover:border-rose-200 rounded text-xs font-semibold transition cursor-pointer"
                                                        title="Hapus Draft">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            @endcan
                                        </div>
                                    @else
                                        @can('view', $r)
                                            <a href="{{ route('inspection-reports.show', $r->id) }}" wire:navigate
                                               class="inline-flex items-center px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold rounded text-xs transition">
                                                View
                                            </a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    No inspection reports found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        @if ($ready && $reports->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    {{-- Draft Continuation Modal --}}
    @if ($showDraftModal && $pendingDraftInfo)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-zinc-900/50 backdrop-blur-xs transition-opacity"
                 wire:click="$set('showDraftModal', false)"></div>

            <div class="relative bg-white rounded-xl shadow-xl border border-zinc-200 max-w-sm w-full p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200">
                            Draft Ditemukan
                        </span>
                        <h3 class="text-base font-bold text-zinc-900">Lanjutkan Draft Laporan?</h3>
                    </div>
                    <button type="button"
                            wire:click="$set('showDraftModal', false)"
                            class="text-zinc-400 hover:text-zinc-600 rounded p-1 -mr-1 -mt-1 hover:bg-zinc-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Compact Draft Pill --}}
                <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-lg text-xs space-y-1">
                    <div class="flex items-center justify-between font-bold text-zinc-900">
                        <span class="truncate">{{ $pendingDraftInfo['part_number'] }}</span>
                        <span class="font-mono text-zinc-500 font-normal ml-2 flex-shrink-0">{{ $pendingDraftInfo['document_number'] }}</span>
                    </div>
                    <div class="text-zinc-500 flex items-center gap-2 flex-wrap">
                        <span>Mesin {{ $pendingDraftInfo['machine_number'] }}</span>
                        <span>&bull;</span>
                        <span>Shift {{ $pendingDraftInfo['shift'] }}</span>
                        <span>&bull;</span>
                        <span>{{ $pendingDraftInfo['updated_at'] }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button type="button"
                            wire:click="startNewReport"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-zinc-700 text-xs font-semibold hover:bg-zinc-100 transition text-center">
                        Mulai Laporan Baru
                    </button>
                    <button type="button"
                            wire:click="continueDraft"
                            class="w-full px-3 py-2 rounded-lg bg-zinc-900 text-white text-xs font-semibold hover:bg-zinc-800 transition shadow-xs text-center">
                        Lanjutkan Draft
                    </button>
                </div>

                @if (!empty($pendingDraftInfo['id']))
                    <div class="pt-1.5 text-center border-t border-zinc-100">
                        <button type="button"
                                wire:click="deleteDraft({{ $pendingDraftInfo['id'] }})"
                                wire:confirm="Hapus draft laporan {{ $pendingDraftInfo['document_number'] }} ini?"
                                class="text-xs text-rose-600 hover:text-rose-800 hover:underline inline-flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus Draft Ini</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
