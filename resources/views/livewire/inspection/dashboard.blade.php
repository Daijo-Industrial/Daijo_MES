<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-zinc-500 mb-1">
                <a href="{{ route('inspection-reports.index') }}" wire:navigate class="hover:text-zinc-900 transition">IPQC Plastic Injection</a>
                <span>&bull;</span>
                <span class="text-zinc-900 font-semibold">Quality Analytics Dashboard</span>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Plastic Injection IPQC Quality Dashboard
            </h1>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('inspection-reports.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-zinc-300 shadow-sm text-xs font-semibold rounded-lg text-zinc-700 bg-white hover:bg-zinc-50 transition">
                Daftar Laporan
            </a>
            <a href="{{ route('inspection-reports.create') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-4 py-2 border border-transparent shadow-sm text-xs font-semibold rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Input Laporan Baru</span>
            </a>
        </div>
    </div>

    {{-- Progressive Disclosure Filter Bar --}}
    <div x-data="{ filtersOpen: false }" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 space-y-3">
        {{-- Minimized Mode Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter:</span>
                </span>

                {{-- Date Preset Pill --}}
                @if ($preset !== 'all' || ($dateFrom !== '' && $dateTo !== ''))
                    @php
                        $presetLabels = [
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'this_week' => 'This Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Range',
                        ];
                        $dateLabel = $presetLabels[$preset] ?? 'Date Range';
                        if ($dateFrom !== '' && $dateTo !== '') {
                            $dateRangeStr = \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y');
                        } else {
                            $dateRangeStr = $dateLabel;
                        }
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Tanggal:</span> {{ $dateLabel }} ({{ $dateRangeStr }})
                        <button type="button" wire:click="removeFilter('date')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter tanggal">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-zinc-50 text-zinc-500 border border-zinc-200">
                        Semua Tanggal
                    </span>
                @endif

                @if ($shift !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Shift:</span> Shift {{ $shift }}
                        <button type="button" wire:click="removeFilter('shift')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter shift">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if ($status !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Status:</span> {{ ucfirst($status) }}
                        <button type="button" wire:click="removeFilter('status')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter status">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if ($customer !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Customer:</span> {{ $customer }}
                        <button type="button" wire:click="removeFilter('customer')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter customer">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if ($machineNumber !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Machine:</span> {{ $machineNumber }}
                        <button type="button" wire:click="removeFilter('machine_number')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter machine">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if ($inspector !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Inspector:</span> {{ $inspector }}
                        <button type="button" wire:click="removeFilter('inspector')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter inspector">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if ($partNumber !== '')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-300">
                        <span class="text-zinc-500 font-normal">Part:</span> {{ $partNumber }}
                        <button type="button" wire:click="removeFilter('part_number')" class="text-zinc-400 hover:text-rose-600 ml-0.5" title="Hapus filter part number">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 ml-auto">
                @if ($this->hasActiveFilters)
                    <button type="button" wire:click="clearFilters"
                            class="px-2.5 py-1.5 text-xs font-semibold text-zinc-600 hover:text-rose-600 hover:bg-rose-50 border border-dashed border-zinc-300 rounded-lg transition"
                            title="Reset semua filter ke default (This Month)">
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

            {{-- Quick Date Presets --}}
            <div id="dash-quick-presets" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-zinc-500 uppercase flex items-center gap-1 mr-1">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Preset Tanggal:</span>
                </span>
                @php
                    $presetBase = 'px-3 py-1.5 text-xs rounded-lg border transition cursor-pointer select-none';
                    $presetActive = 'bg-zinc-900 text-white border-zinc-900 shadow-sm font-bold';
                    $presetInactive = 'bg-zinc-50 text-zinc-700 border-zinc-200 hover:bg-zinc-100 font-semibold';
                    $datePresetOptions = [
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'custom' => 'Custom Range',
                        'all' => 'All Dates',
                    ];
                @endphp
                @foreach ($datePresetOptions as $presetKey => $presetLabel)
                    <button type="button"
                            data-preset="{{ $presetKey }}"
                            onclick="dashSetDatePreset('{{ $presetKey }}')"
                            class="{{ $presetBase }} {{ $preset === $presetKey ? $presetActive : $presetInactive }}">
                        {{ $presetLabel }}
                    </button>
                @endforeach
            </div>

            {{-- Inputs Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Dari Tanggal</label>
                    <input type="date" id="dash-filter-date-from" wire:model="draftFilters.date_from"
                           oninput="dashHandleCustomDateInput()"
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Sampai Tanggal</label>
                    <input type="date" id="dash-filter-date-to" wire:model="draftFilters.date_to"
                           oninput="dashHandleCustomDateInput()"
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>
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
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Status Laporan</label>
                    <select wire:model="draftFilters.status"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Customer</label>
                    <select wire:model="draftFilters.customer"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Customer</option>
                        @foreach ($customerOptions as $cust)
                            <option value="{{ $cust }}">{{ $cust }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Machine No</label>
                    <select wire:model="draftFilters.machine_number"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Mesin</option>
                        @foreach ($machineOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Inspector</label>
                    <select wire:model="draftFilters.inspector"
                            class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                        <option value="">Semua Inspector</option>
                        @foreach ($inspectorOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Part Number</label>
                    <input type="text" wire:model="draftFilters.part_number" placeholder="Cari part no..."
                           class="w-full text-xs rounded-lg border-zinc-300 py-2 focus:ring-zinc-900 focus:border-zinc-900 min-h-[38px]">
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-100">
                <button type="button" @click="filtersOpen = false" wire:click="clearFilters"
                        class="px-4 py-2 border border-zinc-300 text-xs font-semibold text-zinc-700 bg-white hover:bg-zinc-50 rounded-lg shadow-sm transition">
                    Reset Filter
                </button>
                <button type="button" @click="filtersOpen = false" wire:click="applyFilters"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Apply Filter</span>
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-zinc-400 uppercase tracking-wider mb-1">Total Laporan</span>
            <span class="block text-2xl font-bold font-mono text-zinc-900">{{ number_format($kpi['total_reports'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">Total Output</span>
            <span class="block text-2xl font-bold font-mono text-zinc-900">{{ number_format($kpi['total_output'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">Total Pass</span>
            <span class="block text-2xl font-bold font-mono text-emerald-900">{{ number_format($kpi['total_pass'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-rose-600 uppercase tracking-wider mb-1">Total Reject</span>
            <span class="block text-2xl font-bold font-mono text-rose-900">{{ number_format($kpi['total_reject'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-emerald-600 uppercase tracking-wider mb-1">Avg Pass Rate</span>
            <span class="block text-2xl font-bold font-mono text-emerald-700">{{ number_format($kpi['avg_pass_rate'] ?? 0, 1) }}%</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-rose-600 uppercase tracking-wider mb-1">Avg Reject Rate</span>
            <span class="block text-2xl font-bold font-mono text-rose-700">{{ number_format($kpi['avg_reject_rate'] ?? 0, 1) }}%</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-zinc-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-amber-600 uppercase tracking-wider mb-1">NG Sample Rate</span>
            <span class="block text-2xl font-bold font-mono text-amber-700">{{ number_format($kpi['avg_ng_sample_rate'] ?? 0, 1) }}%</span>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:ignore x-data="inspectionCharts()">
        {{-- Trend Inspeksi Harian --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900">Trend Inspeksi Harian</h3>
            <div class="relative h-64">
                <canvas id="trendChartCanvas"></canvas>
            </div>
        </div>

        {{-- Shift Breakdown --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900">Distribusi Laporan per Shift</h3>
            <div class="relative h-64 flex items-center justify-center">
                <canvas id="shiftChartCanvas"></canvas>
            </div>
        </div>

        {{-- Pass vs Reject Harian --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900">Perbandingan Output Pass vs Reject (Harian)</h3>
            <div class="relative h-64">
                <canvas id="passRejectChartCanvas"></canvas>
            </div>
        </div>

        {{-- Top Customer Distribution --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900">Top 10 Customer Terinspeksi</h3>
            <div class="relative h-64">
                <canvas id="customerChartCanvas"></canvas>
            </div>
        </div>
    </div>

    {{-- Detailed Drill-Down Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Reject Parts --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-bold text-gray-900">Top 10 Part dengan Reject Rate Tertinggi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                        <tr>
                            <th class="px-3 py-2.5">Part Number</th>
                            <th class="px-3 py-2.5">Part Name</th>
                            <th class="px-3 py-2.5 text-center">Laporan</th>
                            <th class="px-3 py-2.5 text-right">Avg Reject</th>
                            <th class="px-3 py-2.5 text-right">Total Reject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topFailingParts as $row)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-3 py-2 font-mono font-semibold text-gray-900">{{ $row['part_number'] }}</td>
                                <td class="px-3 py-2 text-gray-600 truncate max-w-[140px]">{{ $row['part_name'] }}</td>
                                <td class="px-3 py-2 text-center text-gray-700 font-mono">{{ $row['reports'] }}</td>
                                <td class="px-3 py-2 text-right font-mono font-bold text-red-600">{{ number_format($row['avg_reject'], 1) }}%</td>
                                <td class="px-3 py-2 text-right font-mono text-gray-800">{{ number_format($row['total_reject']) }} pcs</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-400 text-xs">Tidak ada data kegagalan part.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Breakdown Problem Types & Dimensi NG --}}
        <div class="space-y-6">
            {{-- Problem Types --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900">Kendala & Problem Terbanyak (Shift Problems)</h3>
                </div>
                <div class="p-4 space-y-2">
                    @forelse ($topProblemTypes as $row)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-zinc-700">{{ $row['type'] }}</span>
                            <span class="font-mono font-bold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded border border-amber-200">
                                {{ $row['count'] }} kejadian
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 py-3 text-center">Tidak ada catatan problem pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Dimension Failures --}}
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200">
                    <h3 class="text-sm font-bold text-zinc-900">Titik / Area Dimensi yang Paling Sering NG</h3>
                </div>
                <div class="p-4 space-y-2">
                    @forelse ($dimensionFailures as $row)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-zinc-700">{{ $row['area'] }}</span>
                            <span class="font-mono font-bold text-rose-800 bg-rose-50 px-2.5 py-0.5 rounded border border-rose-200">
                                {{ $row['ng_count'] }} kali NG
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 py-3 text-center">Tidak ada kegagalan dimensi yang tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Latest Reports Quick List --}}
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-zinc-50 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-zinc-900">8 Laporan Inspeksi Terakhir</h3>
            <a href="{{ route('inspection-reports.index') }}" wire:navigate class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 hover:underline">
                Lihat Seluruh Laporan
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-xs">
                <thead class="bg-zinc-50 text-left font-semibold text-zinc-600">
                    <tr>
                        <th class="px-4 py-2.5">No Dokumen</th>
                        <th class="px-4 py-2.5">Tanggal</th>
                        <th class="px-4 py-2.5">Customer</th>
                        <th class="px-4 py-2.5">Part Number</th>
                        <th class="px-4 py-2.5">Shift</th>
                        <th class="px-4 py-2.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($latestReports as $r)
                        <tr class="hover:bg-zinc-50/50 transition">
                            <td class="px-4 py-2 font-mono font-bold text-zinc-900">
                                <a href="{{ route('inspection-reports.show', $r['id']) }}" wire:navigate class="hover:underline">
                                    {{ $r['document_number'] }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-zinc-600">{{ $r['inspection_date'] }}</td>
                            <td class="px-4 py-2 text-zinc-900 font-medium">{{ $r['customer'] }}</td>
                            <td class="px-4 py-2 font-mono text-zinc-800">{{ $r['part_number'] }}</td>
                            <td class="px-4 py-2 text-zinc-600">Shift {{ $r['shift'] }}</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('inspection-reports.show', $r['id']) }}" wire:navigate
                                   class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold text-zinc-800 bg-zinc-100 hover:bg-zinc-200 rounded transition">
                                    Buka Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-zinc-400 text-xs">Tidak ada laporan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<script>
function dashFormatLocalDateYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function dashHandleCustomDateInput() {
    if (window.__dashSettingDatePreset) {
        return;
    }
    dashSetDatePreset('custom');
}

function dashSetDatePreset(preset) {
    const activeClasses = ['bg-zinc-900', 'text-white', 'border-zinc-900', 'shadow-sm', 'font-bold'];
    const inactiveClasses = ['bg-zinc-50', 'text-zinc-700', 'border-zinc-200', 'hover:bg-zinc-100', 'font-semibold'];

    const container = document.getElementById('dash-quick-presets');
    if (container) {
        container.querySelectorAll('button[data-preset]').forEach(btn => {
            const isMatch = btn.getAttribute('data-preset') === preset;
            btn.classList.remove(...(isMatch ? inactiveClasses : activeClasses));
            btn.classList.add(...(isMatch ? activeClasses : inactiveClasses));
        });
    }

    if (preset === 'custom') {
        return;
    }

    const fromInput = document.getElementById('dash-filter-date-from');
    const toInput = document.getElementById('dash-filter-date-to');
    const now = new Date();
    let fromVal = '';
    let toVal = '';

    if (preset === 'today') {
        fromVal = toVal = dashFormatLocalDateYmd(now);
    } else if (preset === 'yesterday') {
        const y = new Date(now);
        y.setDate(now.getDate() - 1);
        fromVal = toVal = dashFormatLocalDateYmd(y);
    } else if (preset === 'this_week') {
        const day = now.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;
        const monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        fromVal = dashFormatLocalDateYmd(monday);
        toVal = dashFormatLocalDateYmd(sunday);
    } else if (preset === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        fromVal = dashFormatLocalDateYmd(start);
        toVal = dashFormatLocalDateYmd(end);
    } else if (preset === 'last_month') {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        fromVal = dashFormatLocalDateYmd(start);
        toVal = dashFormatLocalDateYmd(end);
    }

    window.__dashSettingDatePreset = true;
    try {
        if (fromInput) {
            fromInput.value = fromVal;
            fromInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (toInput) {
            toInput.value = toVal;
            toInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    } finally {
        window.__dashSettingDatePreset = false;
    }
}

function inspectionCharts() {
    return {
        charts: {},
        init() {
            this.$nextTick(() => {
                this.renderAll({
                    trend: @js($trendChart),
                    shift: @js($shiftChart),
                    customer: @js($customerChart),
                    passReject: @js($passRejectChart),
                });
            });

            Livewire.on('charts-ready', (data) => {
                this.renderAll(data);
            });
        },
        destroyChart(id) {
            if (this.charts[id]) {
                this.charts[id].destroy();
                delete this.charts[id];
            }
        },
        renderAll(data) {
            if (typeof Chart === 'undefined') return;

            // 1. Trend Chart
            this.destroyChart('trend');
            const trendEl = document.getElementById('trendChartCanvas');
            if (trendEl && data.trend) {
                this.charts['trend'] = new Chart(trendEl, {
                    type: 'line',
                    data: {
                        labels: data.trend.labels || [],
                        datasets: [{
                            label: 'Jumlah Inspeksi',
                            data: data.trend.data || [],
                            borderColor: '#18181b',
                            backgroundColor: 'rgba(24, 24, 27, 0.08)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 2. Shift Chart
            this.destroyChart('shift');
            const shiftEl = document.getElementById('shiftChartCanvas');
            if (shiftEl && data.shift) {
                this.charts['shift'] = new Chart(shiftEl, {
                    type: 'doughnut',
                    data: {
                        labels: data.shift.labels || [],
                        datasets: [{
                            data: data.shift.data || [],
                            backgroundColor: ['#18181b', '#10b981', '#f59e0b'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                        }
                    }
                });
            }

            // 3. Pass vs Reject Chart
            this.destroyChart('passReject');
            const passRejectEl = document.getElementById('passRejectChartCanvas');
            if (passRejectEl && data.passReject) {
                this.charts['passReject'] = new Chart(passRejectEl, {
                    type: 'bar',
                    data: {
                        labels: data.passReject.labels || [],
                        datasets: [
                            {
                                label: 'Pass Qty',
                                data: data.passReject.pass || [],
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                            },
                            {
                                label: 'Reject Qty',
                                data: data.passReject.reject || [],
                                backgroundColor: '#ef4444',
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                        },
                        scales: {
                            y: { beginAtZero: true },
                            x: { stacked: true, grid: { display: false } }
                        }
                    }
                });
            }

            // 4. Customer Chart
            this.destroyChart('customer');
            const custEl = document.getElementById('customerChartCanvas');
            if (custEl && data.customer) {
                this.charts['customer'] = new Chart(custEl, {
                    type: 'bar',
                    data: {
                        labels: data.customer.labels || [],
                        datasets: [{
                            label: 'Laporan Inspeksi',
                            data: data.customer.data || [],
                            backgroundColor: '#27272a',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
        }
    }
}
</script>
</div>
