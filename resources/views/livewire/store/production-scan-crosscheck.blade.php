<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6"
     x-data="{
        initAudio() {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        },
        playTone(freq, type, duration) {
            try {
                if (!this.audioCtx) this.initAudio();
                if (this.audioCtx.state === 'suspended') this.audioCtx.resume();
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime);
                gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + duration);
            } catch (e) {
                console.error(e);
            }
        },
        playSound(type) {
            if (type === 'success') {
                this.playTone(880, 'sine', 0.12);
                setTimeout(() => this.playTone(1320, 'sine', 0.2), 100);
            } else if (type === 'warning') {
                this.playTone(660, 'sine', 0.15);
                setTimeout(() => this.playTone(660, 'sine', 0.15), 180);
            } else if (type === 'error') {
                this.playTone(330, 'sawtooth', 0.25);
                setTimeout(() => this.playTone(220, 'sawtooth', 0.35), 200);
            }
        }
     }"
     x-init="
        window.addEventListener('play-scan-sound', e => {
            playSound(e.detail.sound);
        });
        window.addEventListener('keydown', e => {
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const scannerInput = document.getElementById('store-barcode-scanner');
                if (scannerInput) scannerInput.focus();
            }
        });
     ">

    <!-- 1. Header & Date Logistics Bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 transition-all">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Crosscheck Scan Produksi ke Store
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Verifikasi & checklist fisik barang yang telah di-scan oleh operator produksi saat tiba di Store
                        </p>
                    </div>
                </div>
            </div>

            <!-- Date Picker & Quick Navigator -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl shadow-sm bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                    <button type="button"
                            wire:click="setYesterday"
                            title="Hari Sebelumnya"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition">
                        ◀ Kemarin
                    </button>
                    <button type="button"
                            wire:click="setToday"
                            title="Hari Produksi Ini"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg {{ $selectedDate === $currentProdDate ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700' }} transition">
                        Hari Ini
                    </button>
                    <button type="button"
                            wire:click="setTomorrow"
                            title="Hari Berikutnya"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition">
                        Besok ▶
                    </button>
                </div>

                <div class="relative">
                    <input type="date"
                           wire:model.live="selectedDate"
                           class="w-40 sm:w-44 px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs focus:ring-2 focus:ring-emerald-500" />
                </div>

                <!-- Export & Snapshot Action Buttons -->
                <button type="button"
                        wire:click="exportExcel"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </button>

                <a href="{{ route('wms.production-scan-crosscheck.print', ['date' => $selectedDate, 'plant' => $plantTab]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl bg-slate-700 hover:bg-slate-800 text-white shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                </a>

                <button type="button"
                        wire:click="openSnapshotModal"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Simpan Snapshot Harian
                </button>
            </div>
        </div>

        <!-- Cutoff Info Banner -->
        <div class="mt-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-2.5">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Cutoff Produksi: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} (07:30 WIB)</strong> s/d <strong>{{ \Carbon\Carbon::parse($selectedDate)->addDay()->format('d M Y') }} (07:29 WIB)</strong></span>
            </div>
            @if ($lastSavedSnapshotAt)
                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                    ✓ Snapshot Tersimpan: {{ $lastSavedSnapshotAt }} ({{ $reportStatus }})
                </span>
            @else
                <span class="text-amber-500 font-medium">
                    ⏳ Snapshot Harian Belum Disimpan
                </span>
            @endif
        </div>
    </div>

    <!-- 2. Plant Tabs (KBN vs Karawang Separation) -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="inline-flex p-1.5 rounded-2xl bg-slate-200/80 dark:bg-slate-800/80 backdrop-blur-sm border border-slate-300 dark:border-slate-700 gap-1.5">
            <!-- Tab KBN -->
            <button type="button"
                    wire:click="$set('plantTab', 'KBN')"
                    class="flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all {{ $plantTab === 'KBN' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-md ring-1 ring-blue-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <span class="text-base">🏢</span>
                <span>PLANT KBN</span>
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $plantTab === 'KBN' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($metrics['kbn']['total_boxes'] ?? 0) }} Box
                </span>
            </button>

            <!-- Tab Karawang -->
            <button type="button"
                    wire:click="$set('plantTab', 'KARAWANG')"
                    class="flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-bold rounded-xl transition-all {{ $plantTab === 'KARAWANG' ? 'bg-white dark:bg-slate-900 text-purple-600 dark:text-purple-400 shadow-md ring-1 ring-purple-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <span class="text-base">🏭</span>
                <span>PLANT KARAWANG</span>
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $plantTab === 'KARAWANG' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($metrics['karawang']['total_boxes'] ?? 0) }} Box
                </span>
            </button>

            <!-- Tab Semua Plant -->
            <button type="button"
                    wire:click="$set('plantTab', 'ALL')"
                    class="flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all {{ $plantTab === 'ALL' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <span>🌐 Semua Plant</span>
            </button>
        </div>

        <!-- Audio Synthesizer Toggle -->
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-300 select-none">
                <input type="checkbox" wire:model.live="audioEnabled" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>🔊 Bunyi Beep Scanner</span>
            </label>
        </div>
    </div>

    <!-- 3. KPI Summary Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Total Scanned Production -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold">
                <span>Total Discan Produksi</span>
                <span class="text-blue-500">📦</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    {{ number_format($metrics['total_boxes']) }}
                </span>
                <span class="text-xs font-medium text-slate-500">Box</span>
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Total: <strong>{{ number_format($metrics['total_qty']) }}</strong> pcs
            </div>
        </div>

        <!-- Found / Received at Store -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-emerald-200 dark:border-emerald-950/60 bg-emerald-50/20 dark:bg-emerald-950/10 shadow-xs">
            <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                <span>✓ Ada di Store (Ditemukan)</span>
                <span>🟢</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                    {{ number_format($metrics['found_boxes']) }}
                </span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Box</span>
            </div>
            <div class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                Total: <strong>{{ number_format($metrics['found_qty']) }}</strong> pcs
            </div>
        </div>

        <!-- Pending Check -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-amber-200 dark:border-amber-950/60 bg-amber-50/20 dark:bg-amber-950/10 shadow-xs">
            <div class="flex items-center justify-between text-amber-700 dark:text-amber-400 text-xs font-semibold">
                <span>⏳ Belum Dicek (Pending)</span>
                <span>🟡</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400">
                    {{ number_format($metrics['pending_boxes']) }}
                </span>
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Box</span>
            </div>
            <div class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                Sisa: <strong>{{ number_format($metrics['pending_qty']) }}</strong> pcs
            </div>
        </div>

        <!-- Not Found / Missing -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-rose-200 dark:border-rose-950/60 bg-rose-50/20 dark:bg-rose-950/10 shadow-xs">
            <div class="flex items-center justify-between text-rose-700 dark:text-rose-400 text-xs font-semibold">
                <span>❌ Tidak Ditemukan / Hilang</span>
                <span>🔴</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 dark:text-rose-400">
                    {{ number_format($metrics['missing_boxes']) }}
                </span>
                <span class="text-xs font-medium text-rose-600 dark:text-rose-400">Box</span>
            </div>
            <div class="mt-1 text-xs text-rose-700 dark:text-rose-300">
                Hilang: <strong>{{ number_format($metrics['missing_qty']) }}</strong> pcs
            </div>
        </div>

        <!-- Accuracy & Completion Progress -->
        <div class="col-span-2 lg:col-span-1 bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold">
                <span>Akurasi & Progress Store</span>
                <span class="text-indigo-500">📊</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">
                    {{ $metrics['accuracy_rate'] }}%
                </span>
                <span class="text-xs font-medium text-slate-500">Akurasi</span>
            </div>
            <div class="mt-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $metrics['progress_rate'] }}%"></div>
            </div>
            <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                <span>Progress: {{ $metrics['progress_rate'] }}%</span>
                <span>{{ $metrics['found_boxes'] + $metrics['missing_boxes'] }}/{{ $metrics['total_boxes'] }}</span>
            </div>
        </div>
    </div>

    <!-- 4. Barcode Quick Scanner Bar (Physical Barcode Reader Handheld) -->
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700 rounded-2xl p-4 shadow-lg text-white">
        <form wire:submit="scanBarcode" class="space-y-2">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="flex items-center gap-2 text-white font-bold text-sm tracking-wide shrink-0">
                    <span class="text-xl">📷</span>
                    <span>SCAN BARCODE BOX:</span>
                </div>
                <div class="relative flex-1">
                    <input type="text"
                           id="store-barcode-scanner"
                           wire:model="barcodeInput"
                           autofocus
                           placeholder="Arahkan barcode scanner ke label box (atau ketik nomor label lalu tekan Enter) ... Tekan '/' untuk fokus cepat"
                           class="w-full pl-4 pr-10 py-2.5 rounded-xl border-0 bg-white/95 text-slate-900 font-mono text-sm placeholder-slate-400 shadow-inner focus:ring-2 focus:ring-white focus:bg-white transition" />
                    <button type="submit"
                            class="absolute right-1.5 top-1.5 bottom-1.5 px-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition">
                        Verifikasi
                    </button>
                </div>
            </div>
        </form>

        <!-- Scanner Real-Time Feedback Strip -->
        @if ($scanFeedback)
            <div class="mt-3 p-3 rounded-xl flex items-center justify-between text-xs font-semibold {{ $scanFeedback['success'] ? 'bg-emerald-500/30 text-white border border-emerald-400/40' : 'bg-rose-500/30 text-white border border-rose-400/40' }}">
                <div class="flex items-center gap-2">
                    <span class="text-base">{{ $scanFeedback['success'] ? '✓' : '⚠️' }}</span>
                    <span>{{ $scanFeedback['message'] }}</span>
                </div>
                <span class="text-[11px] opacity-80">Jam {{ $scanFeedback['timestamp'] }}</span>
            </div>
        @endif
    </div>

    <!-- 5. Toast / Flash Alerts -->
    @if ($flashSuccess)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800">✕</button>
        </div>
    @endif

    @if ($flashError)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
             class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ $flashError }}</span>
            </div>
            <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-800">✕</button>
        </div>
    @endif

    <!-- 6. Filter & Batch Operations Strip -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Left Filters -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Shift Filter -->
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="font-medium">Shift:</span>
                    <select wire:model.live="shiftFilter"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="ALL">Semua Shift</option>
                        <option value="1">Shift 1 (Pagi)</option>
                        <option value="2">Shift 2 (Sore)</option>
                        <option value="3">Shift 3 (Malam)</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="font-medium">Status:</span>
                    <select wire:model.live="statusFilter"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="ALL">Semua Status</option>
                        <option value="PENDING">⏳ Belum Dicek (Pending)</option>
                        <option value="FOUND">✓ Ada di Store (Ditemukan)</option>
                        <option value="NOT_FOUND">❌ Tidak Ditemukan (Hilang)</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="font-medium">Baris:</span>
                    <select wire:model.live="perPage"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </div>
            </div>

            <!-- Search Field -->
            <div class="relative w-full lg:w-72">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari label, part no, SPK, operator..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white" />
                <span class="absolute left-2.5 top-2 text-slate-400 text-xs">🔍</span>
                @if ($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs">✕</button>
                @endif
            </div>
        </div>

        <!-- Batch Action Buttons (Active when checkboxes selected or quick 1-click) -->
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    Terpilih: <strong>{{ count($selectedBoxIds) }}</strong> box
                </span>

                @if (!empty($selectedBoxIds))
                    <button type="button"
                            wire:click="markSelectedFound"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                        ✓ Tandai Terpilih: ADA DI STORE
                    </button>

                    <button type="button"
                            wire:click="markSelectedNotFound"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition">
                        ❌ Tandai Terpilih: TIDAK ADA
                    </button>
                @endif
            </div>

            <!-- Fast 1-Click Action for remaining pending boxes -->
            <div>
                <button type="button"
                        onclick="confirm('Apakah Anda yakin ingin menandai SEMUA sisa box yang belum dicek sebagai ADA DI STORE?') || event.stopImmediatePropagation()"
                        wire:click="markAllPendingAsFound"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition">
                    <span>⚡</span>
                    <span>Tandai Semua Sisa Belum Dicek sebagai ADA</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 7. Interactive Crosscheck Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 uppercase tracking-wider font-bold">
                        <th class="py-3 px-3 w-10 text-center">
                            <input type="checkbox"
                                   wire:model.live="selectAll"
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        </th>
                        <th class="py-3 px-3">Status Store</th>
                        <th class="py-3 px-3">No Box / Label Barcode</th>
                        <th class="py-3 px-3">Part Number & Item Name</th>
                        <th class="py-3 px-3 text-right">Qty (Pcs)</th>
                        <th class="py-3 px-3">Info Scan Produksi</th>
                        <th class="py-3 px-3">No SPK</th>
                        <th class="py-3 px-3 text-center">Aksi Verifikasi Store</th>
                        <th class="py-3 px-3">Verifikator Store</th>
                        <th class="py-3 px-3 min-w-[180px]">Catatan Store</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                    @forelse ($items as $item)
                        @php
                            $isFound = ($item->store_check_status === 'FOUND');
                            $isNotFound = ($item->store_check_status === 'NOT_FOUND');
                            $isPending = !$isFound && !$isNotFound;

                            $shiftVal = $item->dailyItemCode?->shift
                                ?: \App\Services\WmsDeliveryRecapService::determineShiftFromTime($item->created_at);

                            $machineName = $item->dailyItemCode?->user?->name ?: '-';
                            $itemName = $item->masterItem?->item_name
                                ?: ($item->masterItem?->description ?: ($item->dailyItemCode?->masterItem?->item_name ?: '-'));
                            $customerName = $item->masterItem?->customer?->customer_name
                                ?: ($item->dailyItemCode?->masterItem?->customer?->customer_name ?: ($item->masterItem?->customer_code ?: 'N/A'));

                            $isRecentlyScanned = ($scanFeedback['item_id'] ?? null) === $item->id;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50 {{ $isRecentlyScanned ? 'bg-emerald-100/70 dark:bg-emerald-950/40 ring-2 ring-emerald-500' : '' }} {{ $isFound ? 'bg-emerald-50/20 dark:bg-emerald-950/5' : ($isNotFound ? 'bg-rose-50/20 dark:bg-rose-950/5' : '') }}">
                            <!-- Checkbox -->
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox"
                                       value="{{ $item->id }}"
                                       wire:model.live="selectedBoxIds"
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            </td>

                            <!-- Status Badge -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                @if ($isFound)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <span>✓</span>
                                        <span>ADA DI STORE</span>
                                    </span>
                                @elseif ($isNotFound)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        <span>✕</span>
                                        <span>TIDAK ADA</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        <span>⏳</span>
                                        <span>BELUM DICEK</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Box Label -->
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $item->label }}</span>
                                    @if ($item->plant === 'KARAWANG')
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">KRW</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">KBN</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Part Number & Item Name -->
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->item_code }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ $itemName }}</div>
                                <div class="text-[10px] text-slate-400">Cust: {{ $customerName }}</div>
                            </td>

                            <!-- Qty Pcs -->
                            <td class="py-2.5 px-3 text-right font-extrabold text-sm text-slate-900 dark:text-white whitespace-nowrap">
                                {{ number_format($item->quantity) }} <span class="text-xs font-normal text-slate-500">pcs</span>
                            </td>

                            <!-- Info Scan Produksi -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    Shift {{ $shiftVal }} • Mesin {{ $machineName }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Op: {{ $item->user ?: '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $item->created_at ? $item->created_at->format('H:i:s') : '-' }}
                                </div>
                            </td>

                            <!-- SPK -->
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $item->spk_code ?: '-' }}
                            </td>

                            <!-- Checklist Action Buttons (1-Click) -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Button Ada -->
                                    <button type="button"
                                            wire:click="markFound({{ $item->id }})"
                                            title="Tandai Ditemukan / Ada di Store"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $isFound ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-300 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                        ✓ Ada
                                    </button>

                                    <!-- Button Tidak Ada -->
                                    <button type="button"
                                            wire:click="markNotFound({{ $item->id }})"
                                            title="Tandai Tidak Ditemukan / Hilang"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $isNotFound ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-300 dark:border-rose-700 dark:bg-rose-950/40 dark:text-rose-300' }}">
                                        ✕ Tidak
                                    </button>

                                    <!-- Button Reset -->
                                    @if (!$isPending)
                                        <button type="button"
                                                wire:click="resetStatus({{ $item->id }})"
                                                title="Reset ke status belum dicek"
                                                class="px-1.5 py-1 text-xs font-semibold rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                            ↺
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Verifikator Info -->
                            <td class="py-2.5 px-3 text-[11px] whitespace-nowrap">
                                @if ($item->store_checked_by_name)
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $item->store_checked_by_name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $item->store_checked_at ? $item->store_checked_at->format('H:i:s') : '-' }}
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Inline Catatan Store -->
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-1">
                                    <input type="text"
                                           wire:model.defer="notes.{{ $item->id }}"
                                           wire:keydown.enter="saveNote({{ $item->id }}, notes.{{ $item->id }})"
                                           wire:blur="saveNote({{ $item->id }}, notes.{{ $item->id }})"
                                           placeholder="Tulis catatan store..."
                                           class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-1 focus:ring-emerald-500" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="text-sm font-semibold">Tidak ada data scan produksi untuk kriteria ini</div>
                                <div class="text-xs text-slate-400 mt-1">Coba periksa tanggal produksi atau ubah filter pencarian Anda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
            {{ $items->links() }}
        </div>
    </div>

    <!-- 8. Modal Simpan Snapshot Laporan Harian -->
    @if ($showSnapshotModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">💾</span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Simpan Snapshot Laporan Harian
                        </h3>
                    </div>
                    <button type="button" wire:click="$set('showSnapshotModal', false)" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-3.5 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Produksi:</span>
                        <strong class="text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Plant:</span>
                        <strong class="text-slate-900 dark:text-white">{{ $plantTab === 'ALL' ? 'KBN & KARAWANG' : 'PLANT ' . $plantTab }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total Box Discan:</span>
                        <strong>{{ number_format($metrics['total_boxes']) }} Box ({{ number_format($metrics['total_qty']) }} pcs)</strong>
                    </div>
                    <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                        <span>Ditemukan di Store:</span>
                        <strong>{{ number_format($metrics['found_boxes']) }} Box ({{ $metrics['accuracy_rate'] }}%)</strong>
                    </div>
                    <div class="flex justify-between text-rose-600 dark:text-rose-400">
                        <span>Tidak Ditemukan:</span>
                        <strong>{{ number_format($metrics['missing_boxes']) }} Box</strong>
                    </div>
                    <div class="flex justify-between text-amber-600 dark:text-amber-400">
                        <span>Belum Dicek:</span>
                        <strong>{{ number_format($metrics['pending_boxes']) }} Box</strong>
                    </div>
                </div>

                <!-- Status Laporan -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Status Laporan:</label>
                    <select wire:model="reportStatus"
                            class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="OPEN">DRAFT / MASIH PROSES CROSSCHECK (OPEN)</option>
                        <option value="FINALIZED">FINAL / SELESAI DIVERIFIKASI (FINALIZED)</option>
                    </select>
                </div>

                <!-- Catatan Umum Store -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Catatan / Keterangan Store PIC:</label>
                    <textarea wire:model="dailyNotes"
                              rows="3"
                              placeholder="Tuliskan catatan umum mengenai serah terima barang store hari ini..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button"
                            wire:click="$set('showSnapshotModal', false)"
                            class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="button"
                            wire:click="saveDailySnapshot"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                        ✓ Simpan Laporan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
