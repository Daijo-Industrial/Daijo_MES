<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ __('Timeline Siklus Part (Part Lifecycle Journey)') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Pelacakan riwayat proses produksi bertahap (Amplas &rarr; Painting &rarr; Assy &rarr; Packing), rekonsiliasi sisa material, dan akumulasi hasil per part number.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('second-process-reports.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-3.5 rounded shadow-sm text-xs transition inline-flex items-center gap-1.5 border border-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Ke Daftar Laporan</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Card -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('second-process-reports.part-cycle') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <!-- Searchable Part Number Combobox -->
                        <div class="md:col-span-6 space-y-1"
                            x-data="{
                                open: false,
                                search: '{{ addslashes($selectedPart) }}',
                                selected: '{{ addslashes($selectedPart) }}',
                                parts: {{ Js::from($availableParts->map(fn($p) => [
                                    'part_number' => $p->part_number,
                                    'part_name' => $p->part_name ?: '',
                                    'model' => $p->model ?: '',
                                    'customer' => $p->customer ?: 'N/A',
                                ])) }},
                                get filteredParts() {
                                    const q = (this.search || '').trim().toLowerCase();
                                    if (!q) {
                                        return this.parts.slice(0, 60);
                                    }
                                    return this.parts.filter(p => 
                                        (p.part_number && p.part_number.toLowerCase().includes(q)) ||
                                        (p.part_name && p.part_name.toLowerCase().includes(q)) ||
                                        (p.customer && p.customer.toLowerCase().includes(q)) ||
                                        (p.model && p.model.toLowerCase().includes(q))
                                    ).slice(0, 60);
                                },
                                selectPart(part) {
                                    this.selected = part.part_number;
                                    this.search = part.part_number;
                                    this.open = false;
                                    this.$nextTick(() => {
                                        this.$el.closest('form').submit();
                                    });
                                },
                                clearSelection() {
                                    this.selected = '';
                                    this.search = '';
                                    this.open = true;
                                    this.$nextTick(() => this.$refs.searchInput.focus());
                                },
                                submitSearch() {
                                    if (this.filteredParts.length > 0 && (!this.selected || this.selected !== this.filteredParts[0].part_number)) {
                                        this.selectPart(this.filteredParts[0]);
                                    } else {
                                        if (this.search && this.search.trim()) {
                                            this.selected = this.search.trim();
                                        }
                                        this.$el.closest('form').submit();
                                    }
                                }
                            }"
                            @click.away="open = false; if (selected && !search.trim()) { search = selected; }">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide flex items-center justify-between">
                                <span>Pilih / Cari Part Number:</span>
                                <span class="text-[10px] text-purple-600 font-semibold normal-case">Ketik nama, kode, atau customer</span>
                            </label>
                            
                            <input type="hidden" name="part_number" :value="selected || search.trim()" id="part_number">

                            <!-- Relative Container Scoped to the Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text"
                                    x-ref="searchInput"
                                    x-model="search"
                                    @focus="open = true; $event.target.select();"
                                    @click="open = true"
                                    @input="open = true"
                                    @keydown.escape="open = false"
                                    @keydown.enter.prevent="submitSearch()"
                                    placeholder="Ketik Part Number, Nama Part, atau Customer..."
                                    class="w-full pl-9 pr-8 rounded-lg border-gray-300 text-xs py-2 focus:ring-purple-500 focus:border-purple-500 font-medium transition"
                                    autocomplete="off">
                                <button type="button"
                                    x-show="search"
                                    @click="clearSelection()"
                                    class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 transition"
                                    title="Hapus pilihan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>

                                <!-- Popover Dropdown Results: Scoped strictly inside the input's relative box -->
                                <div x-show="open"
                                    x-cloak
                                    class="absolute left-0 right-0 top-full mt-1 w-full max-h-72 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-2xl z-50">
                                    <div class="px-3 py-1.5 bg-gray-50 text-[10px] font-bold text-gray-500 uppercase flex items-center justify-between sticky top-0 border-b border-gray-100 z-10 backdrop-blur-sm">
                                        <span>Daftar Part Terdaftar</span>
                                        <span x-text="filteredParts.length + ' part ditampilkan'"></span>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="(p, idx) in filteredParts" :key="p.part_number + '-' + idx">
                                            <div
                                                @click="selectPart(p)"
                                                class="w-full text-left px-3.5 py-2.5 hover:bg-purple-50/80 cursor-pointer transition flex items-center justify-between group"
                                                :class="selected === p.part_number ? 'bg-purple-50 text-purple-900 font-bold' : 'text-gray-800'">
                                                <div class="min-w-0 pr-2">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="font-mono text-xs font-bold text-gray-900 group-hover:text-purple-700" x-text="p.part_number"></span>
                                                        <template x-if="p.model">
                                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-semibold" x-text="p.model"></span>
                                                        </template>
                                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-medium" x-text="p.customer"></span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 truncate mt-0.5" x-text="p.part_name || 'Tanpa Nama Part'"></div>
                                                </div>
                                                <div class="shrink-0 pl-2">
                                                    <span x-show="selected === p.part_number" class="text-purple-600 font-bold text-sm">✓</span>
                                                    <span x-show="selected !== p.part_number" class="text-xs text-gray-400 group-hover:text-purple-600">&rarr;</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="filteredParts.length === 0" class="px-4 py-6 text-center text-xs text-gray-500">
                                        Tidak ada part yang cocok dengan "<span x-text="search" class="font-bold"></span>"
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date range presets -->
                        <div class="md:col-span-4 space-y-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Rentang Waktu:</label>
                            <div class="flex items-center gap-2">
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-1/2 rounded-lg border-gray-300 text-xs py-2 focus:ring-purple-500 focus:border-purple-500">
                                <span class="text-xs text-gray-400">s/d</span>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-1/2 rounded-lg border-gray-300 text-xs py-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="md:col-span-2 flex items-center gap-2">
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-3 rounded-lg text-xs transition shadow-sm">
                                Tampilkan
                            </button>
                            <a href="{{ route('second-process-reports.part-cycle') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-lg text-xs font-semibold border border-gray-300 transition" title="Reset filter">
                                Reset
                            </a>
                        </div>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex items-center gap-2 pt-1 text-xs">
                        <span class="text-gray-400 font-semibold">Preset Cepat:</span>
                        <a href="{{ route('second-process-reports.part-cycle', ['part_number' => $selectedPart, 'preset' => 'last_30_days']) }}" 
                           class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $preset === 'last_30_days' ? 'bg-purple-50 text-purple-700 border-purple-300' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                            30 Hari Terakhir
                        </a>
                        <a href="{{ route('second-process-reports.part-cycle', ['part_number' => $selectedPart, 'preset' => 'all']) }}" 
                           class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $preset === 'all' ? 'bg-purple-50 text-purple-700 border-purple-300' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                            Semua Waktu
                        </a>
                    </div>
                </form>
            </div>

            @if(empty($selectedPart))
                <!-- Empty State: No Part Selected -->
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                        🧭
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Silakan Pilih Part Number</h3>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Pilih part number pada dropdown di atas untuk menganalisis urutan proses produksi (Amplas, Painting, Assy, Packing), riwayat sisa carryover, dan rasio kualitas OK / NG.
                    </p>
                </div>
            @elseif($timeline->isEmpty())
                <!-- Empty State: No production logs for this part -->
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-1">Tidak Ada Data Produksi</h3>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Part <strong>{{ $selectedPart }}</strong> belum memiliki catatan laporan Second Process pada rentang tanggal yang dipilih. Coba ganti ke preset <em>Semua Waktu</em>.
                    </p>
                </div>
            @else
                <!-- Part Profile Header & KPI Cards -->
                <div class="space-y-4">
                    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm border-l-4 border-l-indigo-600">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                        Profil Part
                                    </span>
                                    <span class="text-xs text-gray-500 font-medium">{{ $partInfo['customer'] }}</span>
                                </div>
                                <h1 class="text-xl font-black font-mono tracking-wide text-gray-900 mt-1">{{ $partInfo['part_number'] }}</h1>
                                <p class="text-xs text-gray-600 mt-0.5">
                                    {{ $partInfo['part_name'] }} &bull; Customer: <span class="font-bold text-gray-900">{{ $partInfo['customer'] }}</span> &bull; Model: <span class="font-semibold text-gray-800">{{ $partInfo['model'] }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="bg-slate-50 text-slate-700 border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold">
                                    {{ $summary['total_reports'] }} Sesi Produksi
                                </span>
                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs font-bold">
                                    {{ $summary['distinct_processes'] }} Jenis Proses
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Metric Summary -->
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Total Input WIP</span>
                            <span class="text-lg font-black text-gray-900 mt-0.5 block">{{ number_format($summary['total_input_wip']) }}</span>
                            <span class="text-[10px] text-gray-400">Repairan: {{ number_format($summary['total_repairan']) }}</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Total Output OK</span>
                            <span class="text-lg font-black text-emerald-600 mt-0.5 block">{{ number_format($summary['total_output_ok']) }}</span>
                            <span class="text-[10px] text-gray-400">Pcs selesai</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Total Defect NG</span>
                            <span class="text-lg font-black text-rose-600 mt-0.5 block">{{ number_format($summary['total_output_ng']) }}</span>
                            <span class="text-[10px] text-gray-400">Reject</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Rasio NG Rata-rata</span>
                            <span class="text-lg font-black {{ $summary['ng_percentage'] >= 3 ? 'text-rose-600' : 'text-gray-900' }} mt-0.5 block">
                                {{ $summary['ng_percentage'] }}%
                            </span>
                            <span class="text-[10px] text-gray-400">Dari total output</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Akumulasi Sisa</span>
                            <span class="text-lg font-black text-amber-600 mt-0.5 block">{{ number_format($summary['total_sisa_input']) }}</span>
                            <span class="text-[10px] text-gray-400">Sisa WIP</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Tahap Proses</span>
                            <span class="text-lg font-black text-purple-600 mt-0.5 block">{{ $summary['distinct_processes'] }}</span>
                            <span class="text-[10px] text-gray-400">Station unik</span>
                        </div>
                    </div>
                </div>

                <!-- Vertical Visual Timeline -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                            Urutan Kronologis Siklus Proses (Timeline Produksi)
                        </h3>
                        <span class="text-xs text-gray-500">Urut dari proses paling awal &rarr; terbaru</span>
                    </div>

                    <div class="relative space-y-6 pb-2 pt-2 before:absolute before:top-3 before:bottom-3 before:left-3.5 before:w-0.5 before:bg-indigo-200">
                        @foreach($timeline as $idx => $step)
                            @php
                                $r = $step['report'];
                                $recon = $step['reconciliation'];
                                $proc = $r->process_prod;

                                // Station styling
                                $stationBg = 'bg-slate-700';
                                if ($proc === 'Painting') $stationBg = 'bg-blue-600';
                                elseif ($proc === 'Amplas') $stationBg = 'bg-amber-600';
                                elseif ($proc === 'Buffing') $stationBg = 'bg-purple-600';
                                elseif ($proc === 'Treatment') $stationBg = 'bg-teal-600';
                                elseif (str_contains($proc, 'Assy')) $stationBg = 'bg-emerald-600';
                                elseif (str_contains($proc, 'Repair') || str_contains($proc, 'Rework')) $stationBg = 'bg-rose-600';
                            @endphp

                            <div class="relative flex items-start gap-3.5 group">
                                <!-- Station marker dot on vertical line -->
                                <div class="w-7 h-7 rounded-full {{ $stationBg }} text-white flex items-center justify-center font-bold text-[11px] shadow-sm ring-4 ring-white shrink-0 z-10 mt-1">
                                    {{ $idx + 1 }}
                                </div>

                                <!-- Timeline Event Card -->
                                <div class="flex-1 min-w-0 bg-gray-50/70 hover:bg-white rounded-xl border border-gray-200 p-4 transition-all hover:shadow-md space-y-3">
                                    <!-- Event Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200 pb-2.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-bold text-white {{ $stationBg }}">
                                                {{ $proc }}
                                            </span>
                                            <span class="font-bold text-xs text-gray-900">
                                                {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </span>
                                            <span class="text-gray-400">&bull;</span>
                                            <span class="text-xs font-semibold text-gray-700">Shift {{ $r->shift }}</span>
                                            <span class="text-gray-400">&bull;</span>
                                            <span class="text-xs font-semibold text-gray-700">{{ $r->unit_line }}</span>
                                        </div>

                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ 
                                                $r->status === 'acknowledged' ? 'bg-green-100 text-green-800' : (
                                                $r->status === 'submitted' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')
                                            }}">
                                                {{ $r->status }}
                                            </span>
                                            <a href="{{ route('second-process-reports.show', $r->id) }}" target="_blank" class="text-purple-600 hover:text-purple-800 font-bold hover:underline flex items-center gap-0.5" title="Buka laporan lengkap">
                                                <span>Laporan #{{ $r->id }}</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Production Stats Grid -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                        <div class="bg-white p-2.5 rounded-lg border border-gray-100">
                                            <span class="text-gray-400 text-[10px] block">Input Material:</span>
                                            <span class="font-bold text-gray-900">{{ number_format($r->jml_input_wip + $r->repairan) }} pcs</span>
                                            <span class="text-[10px] text-gray-400 block">(WIP: {{ $r->jml_input_wip }}, Rep: {{ $r->repairan }})</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-gray-100">
                                            <span class="text-gray-400 text-[10px] block">Hasil OK:</span>
                                            <span class="font-bold text-emerald-700">{{ number_format($r->jumlah_ok) }} pcs</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-gray-100">
                                            <span class="text-gray-400 text-[10px] block">Reject NG:</span>
                                            <span class="font-bold text-rose-700">{{ number_format($r->jumlah_ng) }} pcs ({{ $r->ng_prosentase }}%)</span>
                                        </div>
                                        <div class="bg-white p-2.5 rounded-lg border border-gray-100">
                                            <span class="text-gray-400 text-[10px] block">Sisa Input:</span>
                                            <span class="font-bold {{ $r->sisa_input > 0 ? 'text-amber-700' : 'text-gray-500' }}">
                                                {{ number_format($r->sisa_input) }} pcs
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Sisa Carryover Reconciliation Connection -->
                                    @if($recon['type'] === 'reconciled')
                                        <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-900 flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 font-medium">
                                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                <span>{{ $recon['message'] }}</span>
                                            </div>
                                            <a href="{{ route('second-process-reports.show', $recon['linked_report_id']) }}" target="_blank" class="font-bold text-emerald-700 hover:text-emerald-900 underline shrink-0 ml-2">
                                                Lihat Laporan Penutup &rarr;
                                            </a>
                                        </div>
                                    @elseif($recon['type'] === 'open')
                                        <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900 flex items-center gap-1.5 font-medium">
                                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            <span>{{ $recon['message'] }}</span>
                                            @if($r->sisa_input_remark)
                                                <span class="text-gray-500 font-normal ml-1">(Remark: "{{ $r->sisa_input_remark }}")</span>
                                            @endif
                                        </div>
                                    @elseif($recon['type'] === 'absorber')
                                        <div class="p-2.5 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900 flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 font-medium">
                                                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                                <span>{{ $recon['message'] }}</span>
                                            </div>
                                            <a href="{{ route('second-process-reports.show', $recon['linked_report_id']) }}" target="_blank" class="font-bold text-blue-700 hover:text-blue-900 underline shrink-0 ml-2">
                                                Lihat Laporan Asal &rarr;
                                            </a>
                                        </div>
                                    @endif

                                    <!-- Footer: Operator & Signoff info -->
                                    <div class="text-[11px] text-gray-400 flex flex-wrap items-center justify-between pt-1">
                                        <div>
                                            Operator (Checker): <span class="text-gray-600 font-semibold">{{ $r->created_by_name ?: '-' }}</span>
                                            @if($r->leader_name)
                                                &bull; Leader: <span class="text-gray-600 font-semibold">{{ $r->leader_name }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            Target/Jam: {{ $r->target_per_hour ?: '-' }} pcs
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
