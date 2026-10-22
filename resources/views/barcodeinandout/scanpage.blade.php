<x-app-layout>
    <div class="py-6 sm:py-8 bg-gray-100 min-h-screen" x-data="barcodeScannerApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Document Header Banner --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-1 text-xs font-black uppercase tracking-wider rounded-md {{ str_contains(strtolower($HeaderScan), 'in') ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $HeaderScan }}
                            </span>
                            <span class="text-xs text-gray-500 font-mono">No: {{ $noDokumen }}</span>
                        </div>
                        <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                            <span>⚡</span> Operasional Scan Barcode Box
                        </h1>
                        <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                            <div><strong class="text-gray-900">Customer:</strong> {{ $customer ?: 'Internal Daijo' }}</div>
                            <div>&bull;</div>
                            <div><strong class="text-gray-900">Posisi Tujuan:</strong> <span class="font-semibold text-blue-600">{{ $position }}</span></div>
                            <div>&bull;</div>
                            <div><strong class="text-gray-900">Waktu Mulai:</strong> {{ $tanggalScanFull }}</div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3">
                        <a href="{{ route('inandout.index') }}" 
                            onclick="return confirm('Batalkan sesi scan ini? Data yang belum tersimpan akan hilang.')"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl border border-gray-300 transition">
                            ✕ Batalkan Sesi
                        </a>
                        <button type="button" @click="submitScanForm()"
                            :disabled="scannedList.length === 0"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-2">
                            <span>💾</span> SIMPAN & SELESAIKAN DOKUMEN (<span x-text="scannedList.length"></span>)
                        </button>
                    </div>
                </div>
            </div>

            {{-- Scanner Strip & Realtime Counter --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {{-- Left: Scanner Input Box --}}
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <label for="scanner-input" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            Input Barcode Scanner (Siap Scan)
                        </span>
                        <span class="text-gray-400 font-normal">Arahkan scanner atau tekan Enter</span>
                    </label>

                    <div class="relative">
                        <input type="text" id="scanner-input" x-ref="scanInput" x-model="rawInput" @keydown.enter.prevent="processRawScan()"
                            placeholder="Scan QR Barcode Box di sini (Tab-delimited atau Part No)..."
                            autocomplete="off" autofocus
                            class="w-full text-base sm:text-lg font-mono px-4 py-3.5 border-2 border-blue-500 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-600 transition shadow-inner">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" @click="processRawScan()"
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition">
                                Enter
                            </button>
                        </div>
                    </div>

                    {{-- Manual Input Accordion / Fallback --}}
                    <div class="mt-4 pt-4 border-t border-gray-100" x-data="{ manualOpen: false }">
                        <button type="button" @click="manualOpen = !manualOpen" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            <span x-text="manualOpen ? '▼' : '▶'"></span>
                            <span>Bantuan: Input Manual (Jika Stiker Rusak)</span>
                        </button>
                        <div x-show="manualOpen" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200" style="display: none;">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Part No Box:</label>
                                <input type="text" x-model="manualPartNo" placeholder="Misal: 601-68921B000P" class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">No Label:</label>
                                <input type="text" x-model="manualLabel" placeholder="Misal: 15" class="w-full text-xs p-2 border border-gray-300 rounded-lg">
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="addManualScan()" class="w-full py-2 bg-gray-800 hover:bg-black text-white text-xs font-bold rounded-lg transition">
                                    + Tambahkan ke List
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Live KPI Counter --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Live Session Stats</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 border border-blue-200 p-3 rounded-xl text-center">
                            <span class="text-3xl font-black text-blue-700" x-text="scannedList.length"></span>
                            <span class="block text-[10px] text-blue-600 uppercase font-bold tracking-wider mt-1">Total Box (pcs)</span>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 p-3 rounded-xl text-center">
                            <span class="text-3xl font-black text-purple-700" x-text="uniquePartsCount"></span>
                            <span class="block text-[10px] text-purple-600 uppercase font-bold tracking-wider mt-1">Tipe Box Unik</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <span>Duplikat Tertolak:</span>
                        <span class="font-bold text-red-600 font-mono" x-text="duplicateCount"></span>
                    </div>
                </div>
            </div>

            {{-- Table of Scanned Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="p-4 sm:p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <span>📋</span> Daftar Box Ter-Scan (<span x-text="scannedList.length"></span>)
                    </h3>
                    <span class="text-xs text-gray-400">Scan terbaru akan muncul paling atas</span>
                </div>

                <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Part No Box</th>
                                <th class="px-4 py-3 text-center">No Label</th>
                                <th class="px-4 py-3 text-left">Posisi Tujuan</th>
                                <th class="px-4 py-3 text-left">Waktu Scan</th>
                                <th class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template x-for="(item, index) in scannedList" :key="item.partNo + '-' + item.label + '-' + index">
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="px-4 py-3 text-center font-mono text-gray-400 text-xs" x-text="scannedList.length - index"></td>
                                    <td class="px-4 py-3 font-semibold text-gray-900" x-text="item.partNo"></td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-blue-600" x-text="'#' + item.label"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800" x-text="item.position"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 font-mono text-xs" x-text="item.scanTime"></td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeScan(index)" class="text-red-500 hover:text-red-700 font-bold text-sm px-2 py-1 rounded hover:bg-red-50">
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="scannedList.length === 0">
                                <td colspan="6" class="px-4 py-16 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-4xl mb-2">📡</span>
                                        <p class="font-medium text-gray-600 text-sm">Belum ada barcode box yang discan.</p>
                                        <p class="text-xs text-gray-400 mt-1">Arahkan scanner ke barcode box untuk mulai merekam.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Hidden Native Form submitted to processbarcodeinandout --}}
            <form id="submission-form" action="{{ route('processbarcodeinandout') }}" method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="noDokumen" value="{{ $noDokumen }}">
                <input type="hidden" name="tanggalScanFull" value="{{ $tanggalScanFull }}">
                <input type="hidden" name="position" value="{{ $position }}">
                <div id="hidden-inputs-container"></div>
            </form>

        </div>
    </div>

    <script>
        function barcodeScannerApp() {
            return {
                rawInput: '',
                manualPartNo: '',
                manualLabel: '',
                scannedList: [],
                duplicateCount: 0,
                position: @json($position),
                noDokumen: @json($noDokumen),

                get uniquePartsCount() {
                    const unique = new Set(this.scannedList.map(s => s.partNo));
                    return unique.size;
                },

                // Zero-dependency audio feedback
                playBeep(success = true) {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);

                        if (success) {
                            osc.frequency.setValueAtTime(880, ctx.currentTime); // High pitch (A5)
                            gain.gain.setValueAtTime(0.2, ctx.currentTime);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.1);
                        } else {
                            osc.frequency.setValueAtTime(300, ctx.currentTime); // Low pitch error
                            gain.gain.setValueAtTime(0.3, ctx.currentTime);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.25);
                        }
                    } catch (e) {
                        // Audio not supported or blocked
                    }
                },

                getFormattedNow() {
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    return `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
                },

                processRawScan() {
                    const val = this.rawInput.trim();
                    this.rawInput = '';
                    this.$refs.scanInput.focus();

                    if (!val) return;

                    // Barcode format: PartNo \t LabelNo \t ...
                    let partNo = '';
                    let label = '';

                    if (val.includes('\t')) {
                        const parts = val.split('\t');
                        partNo = parts[0].trim();
                        label = parts[1] ? parts[1].trim() : '1';
                    } else if (val.includes('|')) {
                        const parts = val.split('|');
                        partNo = parts[0].trim();
                        label = parts[1] ? parts[1].trim() : '1';
                    } else {
                        // Plain barcode / single string
                        partNo = val;
                        label = '1';
                    }

                    this.addScanEntry(partNo, label);
                },

                addManualScan() {
                    if (!this.manualPartNo.trim() || !this.manualLabel.trim()) {
                        alert('Harap isi Part No dan No Label!');
                        return;
                    }
                    this.addScanEntry(this.manualPartNo.trim(), this.manualLabel.trim());
                    this.manualPartNo = '';
                    this.manualLabel = '';
                },

                addScanEntry(partNo, label) {
                    // Check duplicate in current scan list
                    const isDup = this.scannedList.some(item => item.partNo === partNo && item.label === label);
                    if (isDup) {
                        this.duplicateCount++;
                        this.playBeep(false);
                        alert(`⚠️ Box [${partNo}] dengan Label #${label} sudah di-scan dalam sesi ini!`);
                        return;
                    }

                    // Add to top of list
                    this.scannedList.unshift({
                        partNo: partNo,
                        label: label,
                        position: this.position,
                        scanTime: this.getFormattedNow()
                    });

                    this.playBeep(true);
                },

                removeScan(index) {
                    this.scannedList.splice(index, 1);
                },

                submitScanForm() {
                    if (this.scannedList.length === 0) {
                        alert('Belum ada box yang di-scan!');
                        return;
                    }

                    if (!confirm(`Simpan dokumen ${this.noDokumen} dengan total ${this.scannedList.length} box?`)) {
                        return;
                    }

                    const container = document.getElementById('hidden-inputs-container');
                    container.innerHTML = '';

                    // Invert so they are stored sequentially
                    const ordered = [...this.scannedList].reverse();
                    ordered.forEach((item, idx) => {
                        const counter = idx + 1;
                        container.innerHTML += `
                            <input type="hidden" name="partno${counter}" value="${item.partNo}">
                            <input type="hidden" name="label${counter}" value="${item.label}">
                            <input type="hidden" name="warehouse${counter}" value="${this.position}">
                            <input type="hidden" name="scantime${counter}" value="${item.scanTime}">
                        `;
                    });

                    document.getElementById('submission-form').submit();
                }
            };
        }
    </script>
</x-app-layout>
