<x-dashboard-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <span>📦</span>
                    <span>Preview Payload JSON — SAP Production Order Update</span>
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Inspeksi struktur JSON yang dikirimkan ke endpoint SAP Business One API
                </p>
            </div>
            <a href="{{ route('spk.bom-changes.index') }}" 
               class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                <span>⬅</span>
                <span>Kembali ke SPK BOM</span>
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{ copied: false }">
        <!-- Overview Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-6 border-b border-slate-100">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nomor SPK</div>
                    <div class="text-lg font-black text-slate-800 font-mono mt-0.5">
                        {{ $previewData['spk_code'] ?? 'N/A' }}
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">HTTP Method & Status</div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2.5 py-0.5 bg-blue-600 text-white text-xs font-black rounded-md">
                            {{ $previewData['method'] ?? 'POST' }}
                        </span>
                        @if(($previewData['status'] ?? '') === 'SUCCESS')
                            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-black rounded-md">
                                ✓ SUCCESS
                            </span>
                        @elseif(($previewData['status'] ?? '') === 'FAILED')
                            <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 text-xs font-black rounded-md">
                                ✕ FAILED
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-xs font-black rounded-md">
                                ⚡ {{ $previewData['status'] ?? 'PREVIEW' }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jumlah Material Lines</div>
                    <div class="text-lg font-black text-slate-800 mt-0.5">
                        {{ isset($previewData['payload']['lines']) ? count($previewData['payload']['lines']) : 0 }} Baris
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Waktu Eksekusi</div>
                    <div class="text-xs font-bold text-slate-700 mt-1 font-mono">
                        {{ $previewData['timestamp'] ?? now()->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>

            <!-- Endpoint & Headers Strip -->
            <div class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                        Endpoint URL
                    </label>
                    <div class="flex items-center gap-2 font-mono text-xs bg-slate-900 text-emerald-400 p-3 rounded-xl border border-slate-800 overflow-x-auto">
                        <span class="text-amber-400 font-bold">{{ $previewData['method'] ?? 'POST' }}</span>
                        <span>{{ $previewData['endpoint'] ?? '' }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                        Request Headers
                    </label>
                    <div class="font-mono text-xs bg-slate-900 text-slate-300 p-3 rounded-xl border border-slate-800 space-y-1">
                        <div><span class="text-sky-400">Authorization:</span> Bearer &lt;token from POST /auth/token&gt;</div>
                        <div><span class="text-sky-400">Content-Type:</span> application/json</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Payload Code Section -->
        <div class="bg-slate-900 rounded-2xl shadow-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                    <span class="text-xs font-bold text-slate-300 ml-2 font-mono">JSON Payload Sent to SAP</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="navigator.clipboard.writeText($refs.jsonCode.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                        <span x-show="!copied">📋 Salin JSON</span>
                        <span x-show="copied" class="text-emerald-400">✓ Tersalin!</span>
                    </button>
                </div>
            </div>

            <div class="p-6 overflow-x-auto">
                <pre class="font-mono text-xs text-emerald-300 leading-relaxed"><code x-ref="jsonCode">{{ $previewData['payload_json'] ?? '{}' }}</code></pre>
            </div>
        </div>

        <!-- Response Section (if exists) -->
        @if(!empty($previewData['result']) || !empty($previewData['error']))
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <span>📡</span>
                    <span>Response dari SAP API</span>
                </h3>

                @if(!empty($previewData['error']))
                    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-semibold mb-4">
                        <strong>Error:</strong> {{ $previewData['error'] }}
                    </div>
                @endif

                @if(!empty($previewData['result']))
                    <div class="bg-slate-900 rounded-xl p-4 overflow-x-auto border border-slate-800">
                        <pre class="font-mono text-xs text-sky-300 leading-relaxed"><code>{{ json_encode($previewData['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex justify-between items-center pt-2">
            <a href="{{ route('spk.bom-changes.index') }}" 
               class="px-5 py-2.5 bg-slate-600 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                <span>⬅ Kembali ke Daftar SPK BOM</span>
            </a>
            <button type="button" 
                    onclick="window.print()"
                    class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs">
                🖨️ Cetak / Simpan PDF
            </button>
        </div>
    </div>
</x-dashboard-layout>
