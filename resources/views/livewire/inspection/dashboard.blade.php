<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('inspection-reports.index') }}" class="hover:text-gray-900 transition">IPQC Plastic Injection</a>
                <span>&bull;</span>
                <span class="text-gray-900 font-medium">Quality Analytics Dashboard</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Plastic Injection IPQC Quality Dashboard
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('inspection-reports.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 border border-transparent shadow-sm text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                + Input Laporan Baru
            </a>
            <a href="{{ route('inspection-reports.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                Daftar Laporan &rarr;
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Dari Tanggal</label>
                <input type="date"
                       wire:model.live="dateFrom"
                       class="w-full text-xs rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 min-h-[38px]">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Sampai Tanggal</label>
                <input type="date"
                       wire:model.live="dateTo"
                       class="w-full text-xs rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 min-h-[38px]">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Customer</label>
                <select wire:model.live="customer"
                        class="w-full text-xs rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 min-h-[38px]">
                    <option value="">Semua Customer</option>
                    @foreach ($customerOptions as $cust)
                        <option value="{{ $cust }}">{{ $cust }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Part Number</label>
                <input type="text"
                       wire:model.live.debounce.300ms="partNumber"
                       placeholder="Cari part no..."
                       class="w-full text-xs rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 min-h-[38px]">
            </div>
            <div>
                <button type="button"
                        wire:click="clearFilters"
                        class="w-full px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition min-h-[38px] flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Laporan</span>
            <span class="block text-2xl font-bold font-mono text-gray-900">{{ number_format($kpi['total_reports'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-indigo-600 uppercase tracking-wider mb-1">Total Output</span>
            <span class="block text-2xl font-bold font-mono text-indigo-900">{{ number_format($kpi['total_output'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-green-600 uppercase tracking-wider mb-1">Total Pass</span>
            <span class="block text-2xl font-bold font-mono text-green-900">{{ number_format($kpi['total_pass'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-red-600 uppercase tracking-wider mb-1">Total Reject</span>
            <span class="block text-2xl font-bold font-mono text-red-900">{{ number_format($kpi['total_reject'] ?? 0) }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-green-600 uppercase tracking-wider mb-1">Avg Pass Rate</span>
            <span class="block text-2xl font-bold font-mono text-green-700">{{ number_format($kpi['avg_pass_rate'] ?? 0, 1) }}%</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
            <span class="block text-[11px] font-semibold text-red-600 uppercase tracking-wider mb-1">Avg Reject Rate</span>
            <span class="block text-2xl font-bold font-mono text-red-700">{{ number_format($kpi['avg_reject_rate'] ?? 0, 1) }}%</span>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
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
                            <span class="font-medium text-gray-700">{{ $row['type'] }}</span>
                            <span class="font-mono font-bold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                                {{ $row['count'] }} kejadian
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-3 text-center">Tidak ada catatan problem pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Dimension Failures --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900">Titik / Area Dimensi yang Paling Sering NG</h3>
                </div>
                <div class="p-4 space-y-2">
                    @forelse ($dimensionFailures as $row)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ $row['area'] }}</span>
                            <span class="font-mono font-bold text-red-800 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                                {{ $row['ng_count'] }} kali NG
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-3 text-center">Tidak ada kegagalan dimensi yang tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Latest Reports Quick List --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">8 Laporan Inspeksi Terakhir</h3>
            <a href="{{ route('inspection-reports.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                Lihat Seluruh Laporan &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                    <tr>
                        <th class="px-4 py-2.5">No Dokumen</th>
                        <th class="px-4 py-2.5">Tanggal</th>
                        <th class="px-4 py-2.5">Customer</th>
                        <th class="px-4 py-2.5">Part Number</th>
                        <th class="px-4 py-2.5">Shift</th>
                        <th class="px-4 py-2.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($latestReports as $r)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-2 font-mono font-bold text-indigo-700">
                                <a href="{{ route('inspection-reports.show', $r['id']) }}" class="hover:underline">
                                    {{ $r['document_number'] }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $r['inspection_date'] }}</td>
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $r['customer'] }}</td>
                            <td class="px-4 py-2 font-mono text-gray-800">{{ $r['part_number'] }}</td>
                            <td class="px-4 py-2 text-gray-600">Shift {{ $r['shift'] }}</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('inspection-reports.show', $r['id']) }}"
                                   class="inline-flex items-center px-2 py-1 text-[11px] font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded transition">
                                    Buka Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-xs">Tidak ada laporan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
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
                            backgroundColor: ['#6366f1', '#10b981', '#f59e0b'],
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
                            backgroundColor: '#4f46e5',
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
