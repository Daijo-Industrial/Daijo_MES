<div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased pb-20 selection:bg-blue-600 selection:text-white"
     @if($autoRefreshInterval > 0) wire:poll.{{ $autoRefreshInterval }}s @endif>

    <!-- Top Executive Header & Filter Bar -->
    <header class="bg-white border-b border-slate-200/90 sticky top-0 z-30 shadow-xs backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                
                <!-- Brand Title -->
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-black tracking-tight text-slate-900">
                                Material FIFO Executive Dashboard
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Monitor
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            Audit Kepatuhan FIFO & Arus Throughput Gudang &bull; <span class="text-blue-700 font-bold">{{ $whseLabel }}</span>
                        </p>
                    </div>
                </div>

                <!-- Controls & Actions -->
                <div class="flex items-center gap-2.5 flex-wrap justify-start lg:justify-end w-full lg:w-auto">
                    
                    <!-- Warehouse Selector Segmented Buttons -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80">
                        <button wire:click="setWarehouse('ALL')" 
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $whse_id === 'ALL' ? 'bg-white text-blue-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                            🌐 Semua
                        </button>
                        @foreach($warehouses as $w)
                            <button wire:click="setWarehouse({{ $w['id'] }})" 
                                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ (string)$whse_id === (string)$w['id'] ? 'bg-white text-blue-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900' }}">
                                🏭 {{ $w['whse_code'] }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Date Presets -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80">
                        <button wire:click="setPreset('today')" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $preset === 'today' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Hari Ini
                        </button>
                        <button wire:click="setPreset('7_days')" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $preset === '7_days' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            7 Hari
                        </button>
                        <button wire:click="setPreset('30_days')" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $preset === '30_days' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            30 Hari
                        </button>
                        <button wire:click="setPreset('this_month')" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $preset === 'this_month' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Bulan Ini
                        </button>
                    </div>

                    <!-- Auto Refresh Dropdown -->
                    <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200/80 text-xs">
                        <span class="text-slate-500 font-bold uppercase text-[10px]">⏱️ Auto:</span>
                        <select wire:model.live="autoRefreshInterval" class="bg-transparent border-0 text-slate-800 font-extrabold text-xs focus:ring-0 cursor-pointer py-0 pr-5 pl-0.5">
                            <option value="15">15s</option>
                            <option value="30">30s</option>
                            <option value="60">60s</option>
                            <option value="0">Off</option>
                        </select>
                    </div>

                    <!-- Link to Stock Card -->
                    <a href="{{ route('mwh.stock-card.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition" title="Buka Kartu Stok Material">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Stock Card
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">

        <!-- 4 Executive KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- 1. FIFO Compliance Health Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $kpis['compliance_rate'] >= 95 ? 'bg-emerald-500' : ($kpis['compliance_rate'] >= 85 ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                        FIFO Compliance Rate
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase {{ $kpis['compliance_rate'] >= 95 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($kpis['compliance_rate'] >= 85 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                        {{ $kpis['grade'] }}
                    </span>
                </div>

                <div class="my-3.5 flex items-baseline gap-2">
                    <span class="text-4xl font-black tracking-tight {{ $kpis['compliance_rate'] >= 95 ? 'text-emerald-600' : ($kpis['compliance_rate'] >= 85 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ number_format($kpis['compliance_rate'], 1) }}%
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">FIFO Adherence</span>
                </div>

                <!-- Compliance Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $kpis['compliance_rate'] >= 95 ? 'bg-emerald-500' : ($kpis['compliance_rate'] >= 85 ? 'bg-amber-500' : 'bg-rose-500') }}"
                         style="width: {{ min(100, max(0, $kpis['compliance_rate'])) }}%"></div>
                </div>

                <div class="mt-3 flex justify-between text-xs text-slate-500 font-medium pt-2 border-t border-slate-100">
                    <span>Sesuai: <strong class="text-slate-800">{{ $kpis['compliant_count'] }}</strong> pick</span>
                    <span>Pelanggaran: <strong class="{{ $kpis['deviation_count'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-700' }}">{{ $kpis['deviation_count'] }}</strong></span>
                </div>
            </div>

            <!-- 2. Total Active Warehouse Inventory -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Stok Aktif</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        📦
                    </div>
                </div>

                <div class="my-3.5 flex items-baseline gap-1.5">
                    <span class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                        {{ number_format($kpis['total_active_stock_kg'], 0) }}
                    </span>
                    <span class="text-xs font-black text-blue-600">KG</span>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                    <span>Total: <strong class="text-slate-800">{{ number_format($kpis['total_active_pallets']) }}</strong> Pallet</span>
                    <span class="text-emerald-600 font-bold">{{ $agingSummary['tiers']['fresh']['percent'] }}% Fresh</span>
                </div>
            </div>

            <!-- 3. Turnaround Lead Time (Avg Days to Consume) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kecepatan Konsumsi</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        ⏱️
                    </div>
                </div>

                <div class="my-3.5 flex items-baseline gap-1.5">
                    <span class="text-3xl sm:text-4xl font-black text-indigo-600 tracking-tight">
                        {{ $kpis['avg_lead_days'] }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">Hari Rata-rata</span>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                    <span>Total Outgoing:</span>
                    <strong class="text-slate-800">{{ number_format($kpis['total_outgoing_qty'], 0) }} KG</strong>
                </div>
            </div>

            <!-- 4. Overaged & Stagnant Inventory Warning (>60 Days) -->
            <div class="bg-white rounded-2xl border {{ $kpis['overaged_stock_kg'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }} p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Stok Mengendap (>60 Hari)</span>
                    <div class="w-8 h-8 rounded-xl {{ $kpis['overaged_stock_kg'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-bold">
                        ⚠️
                    </div>
                </div>

                <div class="my-3.5 flex items-baseline gap-1.5">
                    <span class="text-3xl sm:text-4xl font-black {{ $kpis['overaged_stock_kg'] > 0 ? 'text-amber-600' : 'text-slate-800' }} tracking-tight">
                        {{ number_format($kpis['overaged_stock_kg'], 0) }}
                    </span>
                    <span class="text-xs font-black text-slate-400">KG</span>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                    <span>Pallet Mengendap: <strong class="{{ $kpis['overaged_pallets_count'] > 0 ? 'text-amber-700 font-bold' : 'text-slate-700' }}">{{ $kpis['overaged_pallets_count'] }}</strong></span>
                    <span>QC Hold: <strong class="text-slate-700">{{ $kpis['qc_hold_pallets_count'] }} Plt</strong></span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <div class="flex items-center justify-between border-b border-slate-200 gap-4 overflow-x-auto pb-1">
            <nav class="flex space-x-2" aria-label="Tabs">
                <button wire:click="setActiveTab('overview')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center gap-2 {{ $activeTab === 'overview' ? 'bg-blue-600 text-white shadow-xs font-black' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Ringkasan & Throughput Flow
                </button>

                <button wire:click="setActiveTab('deviations')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center gap-2 relative {{ $activeTab === 'deviations' ? 'bg-blue-600 text-white shadow-xs font-black' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Audit Pelanggaran FIFO
                    @if(count($deviations) > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white ml-1">
                            {{ count($deviations) }}
                        </span>
                    @endif
                </button>

                <button wire:click="setActiveTab('queue')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center gap-2 {{ $activeTab === 'queue' ? 'bg-blue-600 text-white shadow-xs font-black' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Live Priority Queue (Next Picks)
                </button>

                <button wire:click="setActiveTab('aging')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all flex items-center gap-2 {{ $activeTab === 'aging' ? 'bg-blue-600 text-white shadow-xs font-black' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Piramida Umur Stok (Aging)
                </button>
            </nav>

            <div class="text-xs text-slate-500 font-semibold whitespace-nowrap bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                Periode: <strong class="text-slate-800">{{ Carbon\Carbon::parse($fromDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($toDate)->format('d M Y') }}</strong>
            </div>
        </div>

        <!-- TAB 1: OVERVIEW & THROUGHPUT FLOW -->
        @if($activeTab === 'overview')
            <div class="space-y-6">
                
                <!-- Main Flow Chart & Aging Pyramid Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Throughput Flow Chart (2 Cols) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                    <span>📈</span> Tren Keluar-Masuk Material (Throughput Flow)
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Perbandingan volume penerimaan (Incoming) vs pemakaian produksi (Outgoing) dalam KG</p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-bold">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded bg-cyan-500"></span>
                                    <span class="text-slate-600">Incoming (KG)</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded bg-indigo-600"></span>
                                    <span class="text-slate-600">Outgoing (KG)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Container (Rock-Solid Alpine Lifecycle) -->
                        <div class="relative h-72 w-full"
                             wire:ignore
                             x-data="{
                                 chart: null,
                                 init() {
                                     this.$nextTick(() => {
                                         this.renderChart({{ \Illuminate\Support\Js::from($throughputTrend) }});
                                     });
                                     if (this.$wire) {
                                         this.$wire.watch('throughputTrend', (newData) => {
                                             this.updateOrRender(newData);
                                         });
                                     }
                                 },
                                 updateOrRender(newData) {
                                     const data = newData || {};
                                     const canvas = this.$refs.chartCanvas;
                                     if (!canvas) return;
                                     const currentChart = this.chart || (typeof Chart !== 'undefined' ? Chart.getChart(canvas) : null);
                                     if (currentChart) {
                                         currentChart.data.labels = data.labels || [];
                                         if (currentChart.data.datasets && currentChart.data.datasets[0]) {
                                             currentChart.data.datasets[0].data = data.incoming || [];
                                         }
                                         if (currentChart.data.datasets && currentChart.data.datasets[1]) {
                                             currentChart.data.datasets[1].data = data.outgoing || [];
                                         }
                                         currentChart.update();
                                         this.chart = currentChart;
                                     } else {
                                         this.renderChart(data);
                                     }
                                 },
                                 renderChart(rawData) {
                                     if (typeof Chart === 'undefined') {
                                         setTimeout(() => this.renderChart(rawData), 100);
                                         return;
                                     }
                                     const canvas = this.$refs.chartCanvas;
                                     if (!canvas) return;
                                     
                                     const existing = Chart.getChart(canvas);
                                     if (existing) {
                                         existing.destroy();
                                     }
                                     if (this.chart) {
                                         this.chart.destroy();
                                         this.chart = null;
                                     }

                                     const data = rawData || {};
                                     const ctx = canvas.getContext('2d');
                                     this.chart = new Chart(ctx, {
                                         type: 'bar',
                                         data: {
                                             labels: data.labels || [],
                                             datasets: [
                                                 {
                                                     label: 'Incoming (KG)',
                                                     data: data.incoming || [],
                                                     backgroundColor: 'rgba(6, 182, 212, 0.85)',
                                                     borderColor: 'rgba(6, 182, 212, 1)',
                                                     borderWidth: 1,
                                                     borderRadius: 6,
                                                     barPercentage: 0.6,
                                                 },
                                                 {
                                                     label: 'Outgoing (KG)',
                                                     data: data.outgoing || [],
                                                     backgroundColor: 'rgba(79, 70, 229, 0.85)',
                                                     borderColor: 'rgba(79, 70, 229, 1)',
                                                     borderWidth: 1,
                                                     borderRadius: 6,
                                                     barPercentage: 0.6,
                                                 }
                                             ]
                                         },
                                         options: {
                                             responsive: true,
                                             maintainAspectRatio: false,
                                             interaction: {
                                                 mode: 'index',
                                                 intersect: false,
                                             },
                                             plugins: {
                                                 legend: {
                                                    display: false
                                                 },
                                                 tooltip: {
                                                     backgroundColor: '#0f172a',
                                                     titleColor: '#ffffff',
                                                     bodyColor: '#e2e8f0',
                                                     borderColor: '#334155',
                                                     borderWidth: 1,
                                                     padding: 10,
                                                     boxPadding: 4,
                                                     usePointStyle: true,
                                                 }
                                             },
                                             scales: {
                                                 x: {
                                                     grid: {
                                                         color: 'rgba(226, 232, 240, 0.8)',
                                                         drawBorder: false,
                                                     },
                                                     ticks: {
                                                         color: '#64748b',
                                                         font: {
                                                             size: 11,
                                                             weight: 'bold'
                                                         }
                                                     }
                                                 },
                                                 y: {
                                                     beginAtZero: true,
                                                     grid: {
                                                         color: 'rgba(226, 232, 240, 0.8)',
                                                         drawBorder: false,
                                                     },
                                                     ticks: {
                                                         color: '#64748b',
                                                         font: {
                                                             size: 11,
                                                             weight: 'bold'
                                                         },
                                                         callback: function(value) {
                                                             return value >= 1000 ? (value / 1000) + 'k' : value;
                                                         }
                                                     }
                                                 }
                                             }
                                         }
                                     });
                                 }
                             }"
                        >
                            <canvas x-ref="chartCanvas" id="fifoThroughputChart"></canvas>
                        </div>
                    </div>

                    <!-- Aging Distribution Snapshot (1 Col) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4 flex flex-col justify-between">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <span>⏳</span> Distribusi Umur Stok Material
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Persentase stok aktif berdasarkan lama tersimpan di rak</p>
                        </div>

                        <!-- Aging Tier Bars -->
                        <div class="space-y-3.5 py-1">
                            <!-- Tier 1: 0-30 Days -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-emerald-700">0 - 30 Hari (Fresh)</span>
                                    <span class="text-slate-800">{{ number_format($agingSummary['tiers']['fresh']['qty']) }} KG ({{ $agingSummary['tiers']['fresh']['percent'] }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $agingSummary['tiers']['fresh']['percent'] }}%"></div>
                                </div>
                            </div>

                            <!-- Tier 2: 31-60 Days -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-blue-700">31 - 60 Hari (Normal)</span>
                                    <span class="text-slate-800">{{ number_format($agingSummary['tiers']['normal']['qty']) }} KG ({{ $agingSummary['tiers']['normal']['percent'] }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: {{ $agingSummary['tiers']['normal']['percent'] }}%"></div>
                                </div>
                            </div>

                            <!-- Tier 3: 61-90 Days -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-amber-700">61 - 90 Hari (Warning)</span>
                                    <span class="text-slate-800">{{ number_format($agingSummary['tiers']['warning']['qty']) }} KG ({{ $agingSummary['tiers']['warning']['percent'] }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full" style="width: {{ $agingSummary['tiers']['warning']['percent'] }}%"></div>
                                </div>
                            </div>

                            <!-- Tier 4: >90 Days -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="text-rose-700">> 90 Hari (Stagnant)</span>
                                    <span class="text-slate-800">{{ number_format($agingSummary['tiers']['critical']['qty']) }} KG ({{ $agingSummary['tiers']['critical']['percent'] }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-rose-500 h-full rounded-full" style="width: {{ $agingSummary['tiers']['critical']['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>

                        <button wire:click="setActiveTab('aging')" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/80">
                            Lihat Rincian Umur Pallet &rarr;
                        </button>
                    </div>
                </div>

            </div>
        @endif

        <!-- TAB 2: AUDIT PELANGGARAN FIFO (DEVIATIONS) -->
        @if($activeTab === 'deviations')
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-5 rounded-2xl border border-slate-200 gap-3 shadow-xs">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>🔍</span> Log Audit Forensik Pelanggaran FIFO (Bypass Older Lot)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi di mana lot baru diambil mendahului lot lama yang masih tersedia</p>
                    </div>
                    <div class="text-xs font-bold text-slate-700 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200">
                        Total Pelanggaran: <strong class="text-rose-600">{{ count($deviations) }}</strong> Transaksi
                    </div>
                </div>

                @if(count($deviations) > 0)
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                        <th class="px-4 py-3">Tgl Outgoing</th>
                                        <th class="px-4 py-3">Material Info</th>
                                        <th class="px-4 py-3 text-right">Qty Diambil</th>
                                        <th class="px-4 py-3">Lot Yang Diambil (Aktual)</th>
                                        <th class="px-4 py-3">Lot Lama Yang Dilompati</th>
                                        <th class="px-4 py-3 text-center">Selisih Umur</th>
                                        <th class="px-4 py-3">Tujuan / Warehouse</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($deviations as $d)
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="px-4 py-3.5 font-mono text-slate-700 font-semibold">
                                                <div class="font-bold flex items-center gap-1.5">
                                                    <span>{{ $d['outgoing_date'] }}</span>
                                                    @if (!empty($d['outgoing_time']))
                                                        <span class="text-[11px] font-normal text-slate-500">{{ $d['outgoing_time'] }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-slate-400">{{ $d['outgoing_code'] }}</div>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <div class="font-extrabold text-slate-900">{{ $d['item_code'] }}</div>
                                                <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $d['item_description'] }}</div>
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-black text-blue-700">
                                                {{ number_format($d['qty_taken'], 1) }} <span class="text-[10px] text-slate-500 font-normal">{{ $d['uom'] }}</span>
                                            </td>
                                            <td class="px-4 py-3.5 space-y-0.5">
                                                <div class="flex items-center gap-1.5 font-bold text-amber-800">
                                                    <span>📦 {{ $d['picked_pallet_id'] }}</span>
                                                    <span class="text-[10px] text-slate-500 font-normal">({{ $d['picked_position_code'] }})</span>
                                                </div>
                                                <div class="text-[10px] text-slate-500">
                                                    Lot: <strong>{{ $d['picked_lot_no'] }}</strong> &bull; Masuk: {{ $d['picked_arrival_date'] }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 space-y-1 bg-rose-50/40 align-top" x-data="{ openHistory: false }">
                                                <div class="flex items-center gap-1.5 font-bold text-rose-700">
                                                    <span>⚠️ {{ $d['skipped_pallet_id'] }}</span>
                                                    <span class="text-[10px] text-slate-500 font-normal">({{ $d['skipped_position_code'] }})</span>
                                                </div>
                                                <div class="text-[10px] text-slate-600">
                                                    Lot: <strong>{{ $d['skipped_lot_no'] }}</strong> &bull; Masuk: <strong>{{ $d['skipped_arrival_date'] }}</strong>
                                                    @if ($d['skipped_current_qty'] > 0)
                                                        (Sisa: <strong class="text-rose-700">{{ number_format($d['skipped_current_qty'], 1) }} KG</strong>)
                                                    @else
                                                        (Stok Saat Itu: <strong class="text-rose-700">{{ number_format($d['skipped_stock_at_time'] ?? 0, 1) }} KG</strong> &bull; <span class="text-slate-500 font-semibold">Sekarang Habis</span>)
                                                    @endif
                                                </div>

                                                <!-- Action Buttons: Dropdown Toggle & Stock Card Link -->
                                                <div class="pt-1 flex items-center gap-2">
                                                    <button type="button" 
                                                            @click="openHistory = !openHistory" 
                                                            class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-lg transition-all border shadow-xs"
                                                            :class="openHistory ? 'bg-rose-700 text-white border-rose-800' : 'bg-rose-100/90 hover:bg-rose-200 text-rose-800 border-rose-300'">
                                                        <svg class="w-3 h-3 transition-transform duration-200" :class="openHistory ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                        <span x-text="openHistory ? 'Tutup Riwayat' : 'Riwayat Transaksi ({{ count($d['skipped_history'] ?? []) }})'"></span>
                                                    </button>

                                                    <a href="{{ route('mwh.stock-card.index', ['search' => $d['skipped_pallet_id'], 'fromDate' => $d['skipped_arrival_date_ymd'] ?? '']) }}" 
                                                       target="_blank" 
                                                       class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 hover:text-blue-700 transition"
                                                       title="Buka Kartu Stok Pallet Ini di Tab Baru">
                                                        <span>Kartu Stok</span>
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    </a>
                                                </div>

                                                <!-- Dropdown Collapsible Card -->
                                                <div x-show="openHistory" 
                                                     x-cloak 
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     x-transition:leave="transition ease-in duration-100"
                                                     x-transition:leave-start="opacity-100 translate-y-0"
                                                     x-transition:leave-end="opacity-0 -translate-y-1"
                                                     class="mt-2 bg-white rounded-xl border border-rose-200 shadow-md p-3 text-xs space-y-2.5 min-w-[320px] max-w-sm">
                                                    
                                                    <!-- Header Summary Badges -->
                                                    <div class="grid grid-cols-3 gap-1 pb-2 border-b border-slate-100 text-[10px]">
                                                        <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                                            <div class="text-[9px] text-slate-400 font-medium">Total Masuk</div>
                                                            <div class="font-extrabold text-slate-800">{{ number_format($d['skipped_initial_qty'] ?? 0, 1) }} KG</div>
                                                        </div>
                                                        <div class="bg-amber-50 p-1.5 rounded-lg border border-amber-100 text-center">
                                                            <div class="text-[9px] text-amber-600 font-medium">Saat Dilompati</div>
                                                            <div class="font-extrabold text-amber-800">{{ number_format($d['skipped_stock_at_time'] ?? 0, 1) }} KG</div>
                                                        </div>
                                                        <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                                            <div class="text-[9px] text-slate-400 font-medium">Sisa Sekarang</div>
                                                            @if (($d['skipped_current_qty'] ?? 0) > 0)
                                                                <div class="font-extrabold text-rose-700">{{ number_format($d['skipped_current_qty'], 1) }} KG</div>
                                                            @else
                                                                <div class="font-extrabold text-slate-500">0.0 KG (Habis)</div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Timeline list of transactions -->
                                                    <div class="max-h-56 overflow-y-auto space-y-2 pr-1 divide-y divide-slate-100">
                                                        @forelse(($d['skipped_history'] ?? []) as $tx)
                                                            <div class="pt-2 first:pt-0 flex items-start justify-between gap-2 text-[10px]">
                                                                <div class="space-y-0.5 flex-1 min-w-0">
                                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                                        @if($tx['type'] === 'INCOMING' || $tx['type'] === 'RETUR')
                                                                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 font-black rounded text-[9px] leading-tight">
                                                                                📥 {{ $tx['type'] }}
                                                                            </span>
                                                                        @else
                                                                            <span class="px-1.5 py-0.5 bg-rose-100 text-rose-800 font-black rounded text-[9px] leading-tight">
                                                                                📤 KELUAR
                                                                            </span>
                                                                        @endif
                                                                        <span class="font-mono text-slate-600 font-bold">{{ $tx['date'] }}</span>
                                                                        @if(str_contains($tx['date'], ':'))
                                                                            <span class="text-[8px] font-extrabold bg-slate-100 text-slate-500 px-1 py-0.5 rounded leading-none">WIB</span>
                                                                        @endif
                                                                        <span class="font-mono text-slate-400 text-[9px] truncate">({{ $tx['code'] }})</span>
                                                                    </div>
                                                                    <div class="text-slate-600 font-medium truncate" title="{{ $tx['desc'] }}">
                                                                        {{ $tx['desc'] }}
                                                                    </div>
                                                                </div>

                                                                <div class="text-right shrink-0">
                                                                    <div class="font-black {{ $tx['type'] === 'INCOMING' || $tx['type'] === 'RETUR' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                                        {{ $tx['type'] === 'INCOMING' || $tx['type'] === 'RETUR' ? '+' : '-' }}{{ number_format(abs($tx['qty']), 1) }} KG
                                                                    </div>
                                                                    <div class="text-[9px] text-slate-400 font-semibold">
                                                                        Sisa: <strong class="text-slate-700">{{ number_format($tx['balance_after'], 1) }} KG</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="py-2 text-center text-slate-400 text-[10px]">
                                                                Tidak ada riwayat transaksi tambahan tercatat.
                                                            </div>
                                                        @endforelse
                                                    </div>

                                                    <!-- Footer link to Stock Card -->
                                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                                                        <span class="text-slate-400 font-medium">
                                                            {{ $d['skipped_outgoings_count'] ?? 0 }}x pengambilan material
                                                        </span>
                                                        <a href="{{ route('mwh.stock-card.index', ['search' => $d['skipped_pallet_id'], 'fromDate' => $d['skipped_arrival_date_ymd'] ?? '']) }}" 
                                                           target="_blank" 
                                                           class="font-bold text-blue-600 hover:text-blue-800 flex items-center gap-0.5 transition">
                                                            <span>Buka Kartu Stok</span>
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-center">
                                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $d['severity'] === 'HIGH' ? 'bg-rose-100 text-rose-800 border border-rose-200' : ($d['severity'] === 'MEDIUM' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700') }}">
                                                    +{{ $d['delta_days'] }} Hari Lebih Tua
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-600 font-medium">
                                                <div class="font-bold text-slate-800">{{ $d['issued_to'] }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $d['warehouse_name'] }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 shadow-xs">
                        <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-2xl font-bold">
                            ✓
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">Kepatuhan FIFO 100% — Sempurna</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">Seluruh pengambilan material pada periode yang dipilih telah mematuhi aturan FIFO tanpa ada lot lama yang terlewat.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 3: LIVE FIFO PRIORITY QUEUE (NEXT-IN-LINE PICKS) -->
        @if($activeTab === 'queue')
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-5 rounded-2xl border border-slate-200 gap-3 shadow-xs">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span>🎯</span> Antrian Prioritas FIFO (Wajib Diambil Selanjutnya)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar rekomendasi lot teratas yang wajib diambil saat ada permintaan produksi</p>
                    </div>

                    <div class="w-full sm:w-72">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Kode Material / Lot / Pallet..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($priorityQueue as $q)
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3.5 hover:shadow-md transition flex flex-col justify-between">
                            <!-- Material Header -->
                            <div class="border-b border-slate-100 pb-2.5 flex items-start justify-between">
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">{{ $q['item_code'] }}</h4>
                                    <p class="text-[11px] text-slate-500 truncate max-w-[200px]">{{ $q['item_description'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-black text-blue-700 block">{{ number_format($q['total_stock_kg'], 1) }} {{ $q['uom'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ $q['pallet_count'] }} Pallet</span>
                                </div>
                            </div>

                            <!-- Rank 1 Pick (MUST PICK NEXT) -->
                            <div class="bg-gradient-to-br from-blue-50/50 to-indigo-50/30 border border-blue-200 rounded-xl p-3.5 space-y-2 relative">
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider">
                                    <span class="text-blue-700 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                        RANK 1 : WAJIB DIAMBIL
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $q['rank_1']['age_days'] > 60 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                        {{ $q['rank_1']['age_days'] }} Hari
                                    </span>
                                </div>

                                <div class="text-sm font-black text-slate-900 flex items-center justify-between">
                                    <span>📦 {{ $q['rank_1']['pallet_id'] }}</span>
                                    <span class="text-blue-700 font-mono">{{ number_format($q['rank_1']['qty'], 1) }} KG</span>
                                </div>

                                <div class="flex justify-between text-xs text-slate-600 font-medium pt-1 border-t border-blue-100">
                                    <span>Lot: <strong class="text-slate-900">{{ $q['rank_1']['lot_no'] }}</strong></span>
                                    <span>Slot Rak: <strong class="text-blue-800 bg-white px-1.5 py-0.5 rounded border border-blue-200 font-mono">{{ $q['rank_1']['position_code'] }}</strong></span>
                                </div>
                            </div>

                            <!-- Next In Line (Rank 2 & 3) -->
                            @if(!empty($q['next_in_line']))
                                <div class="space-y-1 text-xs border-t border-slate-100 pt-2.5">
                                    <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Berikutnya dalam Antrian:</span>
                                    @foreach($q['next_in_line'] as $idx => $nxt)
                                        <div class="flex justify-between items-center text-slate-600 py-0.5 text-[11px]">
                                            <span>#{{ $idx + 2 }}. {{ $nxt['pallet_id'] }} ({{ $nxt['position_code'] }})</span>
                                            <span class="text-slate-800 font-bold">{{ number_format($nxt['qty'], 1) }} KG &bull; {{ $nxt['age_days'] }}d</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-3 py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 text-slate-500 text-xs font-semibold italic">
                            Tidak ada material aktif yang sesuai dengan kata kunci pencarian.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- TAB 4: PIRAMIDA UMUR MATERIAL (AGING DETAILS) -->
        @if($activeTab === 'aging')
            <div class="space-y-6">
                <!-- Top Aging Overview Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-emerald-200 p-5 space-y-2 shadow-xs">
                        <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">0 - 30 Hari (Fresh)</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($agingSummary['tiers']['fresh']['qty']) }} <span class="text-xs text-slate-400 font-normal">KG</span></div>
                        <p class="text-xs text-slate-500">{{ $agingSummary['tiers']['fresh']['count'] }} Pallet ({{ $agingSummary['tiers']['fresh']['percent'] }}% dari total)</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-blue-200 p-5 space-y-2 shadow-xs">
                        <span class="text-xs font-bold text-blue-700 uppercase tracking-wider block">31 - 60 Hari (Normal)</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($agingSummary['tiers']['normal']['qty']) }} <span class="text-xs text-slate-400 font-normal">KG</span></div>
                        <p class="text-xs text-slate-500">{{ $agingSummary['tiers']['normal']['count'] }} Pallet ({{ $agingSummary['tiers']['normal']['percent'] }}% dari total)</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-amber-200 p-5 space-y-2 shadow-xs">
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wider block">61 - 90 Hari (Warning)</span>
                        <div class="text-2xl sm:text-3xl font-black text-amber-700">{{ number_format($agingSummary['tiers']['warning']['qty']) }} <span class="text-xs text-slate-400 font-normal">KG</span></div>
                        <p class="text-xs text-slate-500">{{ $agingSummary['tiers']['warning']['count'] }} Pallet ({{ $agingSummary['tiers']['warning']['percent'] }}% dari total)</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-rose-200 p-5 space-y-2 shadow-xs">
                        <span class="text-xs font-bold text-rose-700 uppercase tracking-wider block">> 90 Hari (Critical)</span>
                        <div class="text-2xl sm:text-3xl font-black text-rose-700">{{ number_format($agingSummary['tiers']['critical']['qty']) }} <span class="text-xs text-slate-400 font-normal">KG</span></div>
                        <p class="text-xs text-slate-500">{{ $agingSummary['tiers']['critical']['count'] }} Pallet ({{ $agingSummary['tiers']['critical']['percent'] }}% dari total)</p>
                    </div>
                </div>

                <!-- Top Oldest Stagnant Pallets Table -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <span>🚨</span> Pallet Paling Lama Mengendap di Gudang (Oldest Stagnant Stock)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Daftar pallet tertua yang perlu prioritas untuk dijadwalkan ke produksi</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                    <th class="px-4 py-3">Pallet ID</th>
                                    <th class="px-4 py-3">Kode & Nama Material</th>
                                    <th class="px-4 py-3">Lot No</th>
                                    <th class="px-4 py-3">Posisi Rak</th>
                                    <th class="px-4 py-3">Tgl Masuk</th>
                                    <th class="px-4 py-3 text-right">Sisa Stok</th>
                                    <th class="px-4 py-3 text-center">Umur (Hari)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($agingSummary['oldest_pallets'] as $old)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-slate-900">📦 {{ $old['pallet_id'] }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-slate-900">{{ $old['item_code'] }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $old['item_description'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-slate-700">{{ $old['lot_no'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-blue-700 font-mono font-bold border border-slate-200">
                                                {{ $old['position_code'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $old['arrival_date'] }}</td>
                                        <td class="px-4 py-3 text-right font-black text-slate-900">
                                            {{ number_format($old['current_qty'], 1) }} <span class="text-[10px] text-slate-400 font-normal">{{ $old['uom'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $old['age_days'] > 90 ? 'bg-rose-100 text-rose-800 border border-rose-200' : ($old['age_days'] > 60 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200') }}">
                                                {{ $old['age_days'] }} Hari
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </main>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</div>
