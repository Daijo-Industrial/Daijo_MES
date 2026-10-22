<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full inline-block"></span>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Material QR Code Scanner & Lookup</h1>
                </div>
                <p class="text-xs text-gray-500 mt-1">Scan QR Code label di palet material untuk melihat detail stok, lokasi rak, dan history pergerakan.</p>
            </div>
            <a href="{{ route('mwh.pallets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-200 transition">
                Kembali ke Stok
            </a>
        </div>

        <!-- Search & Camera Scanner Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-xs font-black text-emerald-800 uppercase tracking-widest border-b border-gray-100 pb-2">Scan / Input Pallet ID</h3>

            <form wire:submit.prevent="lookupPallet" class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-grow">
                    <input type="text" wire:model="pallet_id" placeholder="Scan QR Code / Masukkan Pallet ID (MPLT-XXXXX)..." class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono font-bold uppercase focus:ring-2 focus:ring-emerald-500">
                    <div class="absolute left-3 top-3.5 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                </div>

                <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-200 transition">
                    Cek Detail Pallet
                </button>
            </form>

            <!-- ZXing Camera Scanner View -->
            <div id="cameraScannerSection" class="pt-2">
                <div class="flex items-center justify-between">
                    <button type="button" id="startCamBtn" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold rounded-xl transition flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                        <span>Buka Kamera Scan QR HP/Tab</span>
                    </button>
                    <span id="camStatus" class="text-xs font-bold text-gray-400">Kamera non-aktif</span>
                </div>

                <div id="videoContainer" class="hidden mt-4 relative bg-black rounded-2xl overflow-hidden max-w-md mx-auto aspect-square border-4 border-emerald-500">
                    <video id="qrVideo" class="w-full h-full object-cover"></video>
                </div>
            </div>
        </div>

        <!-- Pallet Details Result -->
        @if ($searched)
            @if ($palletData)
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-100 pb-4 gap-2">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Detail Unit Pallet</span>
                            <h2 class="text-2xl font-black font-mono text-emerald-800">{{ $palletData->pallet_id }}</h2>
                        </div>

                        <div class="flex items-center space-x-3">
                            @if ($palletData->status === 'STORED')
                                <span class="px-4 py-1.5 bg-emerald-100 text-emerald-800 rounded-full font-black text-xs">STORED (Utuh)</span>
                            @elseif ($palletData->status === 'PARTIAL')
                                <span class="px-4 py-1.5 bg-amber-100 text-amber-800 rounded-full font-black text-xs">PARTIAL (Sebagian)</span>
                            @else
                                <span class="px-4 py-1.5 bg-gray-100 text-gray-500 rounded-full font-black text-xs">EMPTY (Habis)</span>
                            @endif

                            <a href="{{ route('mwh.pallet.print', $palletData->pallet_id) }}" target="_blank" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                Print QR Label
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-1">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Part Code Material</span>
                            <div class="text-base font-black font-mono text-gray-900">{{ $palletData->item_code }}</div>
                            <div class="text-xs text-gray-600">{{ $palletData->material ? $palletData->material->item_description : '-' }}</div>
                        </div>

                        <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 space-y-1">
                            <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Sisa Stok KG saat ini</span>
                            <div class="text-2xl font-black text-emerald-900">{{ number_format($palletData->current_qty, 2) }} KG</div>
                            <div class="text-[11px] text-emerald-700">Awal: {{ number_format($palletData->initial_qty, 2) }} KG</div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-1">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Lokasi Slot Rak & Branch</span>
                            <div class="text-base font-black font-mono text-gray-900">
                                {{ $palletData->position ? $palletData->position->position_code : 'Unassigned' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                🏭 {{ $palletData->warehouse?->whse_name ?? ($palletData->position?->rack?->warehouse?->whse_name ?? 'Gudang Material KBN') }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs font-medium text-gray-700 border-t border-gray-100 pt-4">
                        <div><span class="text-gray-400">Branch Gudang:</span> <strong class="text-emerald-800">{{ $palletData->warehouse?->whse_name ?? ($palletData->position?->rack?->warehouse?->whse_name ?? 'Gudang Material KBN') }}</strong></div>
                        <div><span class="text-gray-400">Tgl Kedatangan:</span> <strong class="font-mono text-emerald-800">{{ $palletData->incomingHeader && $palletData->incomingHeader->arrival_date ? $palletData->incomingHeader->arrival_date->format('d M Y') : ($palletData->created_at ? $palletData->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-') }}</strong></div>
                        <div><span class="text-gray-400">Lot / Batch No:</span> <strong class="font-mono text-gray-900">{{ $palletData->lot_no ?: '-' }}</strong></div>
                        <div><span class="text-gray-400">Supplier:</span> <strong>{{ $palletData->incomingHeader ? ($palletData->incomingHeader->supplier_name ?: '-') : '-' }}</strong></div>
                    </div>

                    <!-- Movement Outgoing History Timeline -->
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest">History Pengambilan Material (Outgoings)</h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider">
                                        <th class="py-2.5 px-3">Kode Outgoing</th>
                                        <th class="py-2.5 px-3">Qty Taken</th>
                                        <th class="py-2.5 px-3">Tujuan Issued</th>
                                        <th class="py-2.5 px-3">Catatan</th>
                                        <th class="py-2.5 px-3">Tgl Outgoing</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 font-medium">
                                    @forelse ($palletData->outgoings as $out)
                                        <tr>
                                            <td class="py-2.5 px-3 font-mono font-bold text-gray-900">{{ $out->outgoing_code }}</td>
                                            <td class="py-2.5 px-3 font-black text-rose-600">-{{ number_format($out->qty_taken, 2) }} KG</td>
                                            <td class="py-2.5 px-3 text-gray-700">{{ $out->issued_to ?: '-' }}</td>
                                            <td class="py-2.5 px-3 text-gray-500">{{ $out->remarks ?: '-' }}</td>
                                            <td class="py-2.5 px-3 font-mono text-gray-500">{{ $out->outgoing_date ? $out->outgoing_date->format('Y-m-d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-6 text-center text-gray-400 italic">Belum ada history pengeluaran material dari palet ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="font-bold text-gray-700">Pallet ID tidak ditemukan</p>
                    <p class="text-xs text-gray-400 mt-1">Pastikan kode Pallet ID yang Anda masukkan atau scan sudah benar.</p>
                </div>
            @endif
        @endif
    </div>

    <!-- ZXing Scanner JS Script -->
    <script src="https://unpkg.com/@zxing/browser@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startBtn = document.getElementById('startCamBtn');
            const videoContainer = document.getElementById('videoContainer');
            const videoElem = document.getElementById('qrVideo');
            const statusElem = document.getElementById('camStatus');

            let codeReader = null;
            let activeStream = null;

            if (startBtn) {
                startBtn.addEventListener('click', function () {
                    if (codeReader) {
                        // Stop scanner if active
                        stopScanner();
                        return;
                    }

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        alert('Kamera tidak didukung atau memerlukan koneksi HTTPS / localhost.');
                        return;
                    }

                    try {
                        codeReader = new ZXingBrowser.BrowserQRCodeReader();
                        videoContainer.classList.remove('hidden');
                        statusElem.innerText = '🎥 Mengaktifkan kamera...';

                        codeReader.decodeFromVideoDevice(undefined, videoElem, (result, err) => {
                            if (result) {
                                const text = result.getText().trim();
                                statusElem.innerText = '✅ Scanned: ' + text;
                                @this.set('pallet_id', text);
                                @this.lookupPallet();
                                stopScanner();
                            }
                        }).catch(err => {
                            statusElem.innerText = '❌ Error kamera: ' + err.message;
                        });
                    } catch (err) {
                        statusElem.innerText = '❌ Failed to start camera';
                    }
                });
            }

            function stopScanner() {
                if (codeReader) {
                    try { codeReader.reset(); } catch (e) {}
                    codeReader = null;
                }
                if (videoContainer) videoContainer.classList.add('hidden');
                if (statusElem) statusElem.innerText = 'Kamera non-aktif';
            }
        });
    </script>
</div>
