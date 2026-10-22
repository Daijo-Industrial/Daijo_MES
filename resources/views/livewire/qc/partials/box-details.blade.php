<div x-data="{
    summaryId: {{ $item->id }},
    boxes: {},
    init() {
        @foreach($rowDetails[$item->id] ?? [] as $b)
            @if(!$b['is_inspected'])
                let saved{{ $b['id'] }} = localStorage.getItem('qc_draft_ng_{{ $b['id'] }}');
                let initialNg{{ $b['id'] }} = saved{{ $b['id'] }} !== null ? parseInt(saved{{ $b['id'] }}) : {{ (int)($ngInputs[$b['id']] ?? 0) }};
                this.boxes[{{ $b['id'] }}] = {
                    id: {{ $b['id'] }},
                    label: '{{ $b['label'] }}',
                    boxQty: {{ (int)$b['quantity'] }},
                    ng: Math.min({{ (int)$b['quantity'] }}, Math.max(0, initialNg{{ $b['id'] }} || 0)),
                    remarks: ''
                };
            @endif
        @endforeach
    },
    getNg(boxId) {
        return this.boxes[boxId] ? (parseInt(this.boxes[boxId].ng) || 0) : 0;
    },
    getOk(boxId, boxQty) {
        let ng = this.getNg(boxId);
        return Math.max(0, boxQty - ng);
    },
    updateNg(boxId, boxQty, val) {
        let cleanVal = Math.min(boxQty, Math.max(0, parseInt(val) || 0));
        if (!this.boxes[boxId]) {
            this.boxes[boxId] = { id: boxId, boxQty: boxQty, ng: cleanVal, remarks: '' };
        } else {
            this.boxes[boxId].ng = cleanVal;
        }
        localStorage.setItem('qc_draft_ng_' + boxId, cleanVal);
    },
    submitSingle(boxId, label, boxQty) {
        let ng = this.getNg(boxId);
        let ok = this.getOk(boxId, boxQty);
        let remarks = this.boxes[boxId]?.remarks || '';
        if (confirm(`Kirim hasil inspeksi untuk Box Label ${label}?\n• Qty OK: ${ok.toLocaleString()} PCS\n• Qty NG: ${ng.toLocaleString()} PCS\n\nHasil inspeksi akan FINAL dan langsung diproses ke SAP.`)) {
            $wire.submitSingleBox(boxId, this.summaryId, ng, remarks);
        }
    },
    submitAll() {
        let map = {};
        let summaryText = [];
        for (let id in this.boxes) {
            let b = this.boxes[id];
            let ng = Math.min(b.boxQty, Math.max(0, parseInt(b.ng) || 0));
            map[id] = ng;
            let ok = Math.max(0, b.boxQty - ng);
            summaryText.push(`• ${b.label}: OK ${ok.toLocaleString()} pcs, NG ${ng.toLocaleString()} pcs`);
        }
        let count = Object.keys(map).length;
        if (count === 0) {
            alert('Semua box pada summary ini sudah selesai diinspeksi.');
            return;
        }
        if (confirm(`Kirim inspeksi untuk SEMUA ${count} box pada summary ini?\n\n${summaryText.slice(0, 5).join('\n')}${count > 5 ? '\n... (+' + (count - 5) + ' box lainnya)' : ''}\n\nHasil inspeksi akan FINAL dan langsung diproses ke SAP.`)) {
            $wire.submitWholeSummary(this.summaryId, map);
        }
    }
}">
    {{-- Header & Submit All Button --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-3">
        <div class="font-extrabold text-xs sm:text-sm text-[#92400E] flex items-center gap-2">
            <span>📦 Rincian Box SPK <strong>{{ $item->spk_code }}</strong> (Gudang: {{ $item->warehouse }}) • Tgl Prod: {{ $item->created_date ? \Carbon\Carbon::parse($item->created_date)->format('d/m/Y') : '-' }}</span>
        </div>
        @if($qcStatus != 1)
            <button @click="submitAll()"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto bg-[#D97706] hover:bg-[#B45309] disabled:opacity-50 text-white border-none px-3.5 py-2 rounded text-xs font-extrabold cursor-pointer shadow-sm active:scale-95 transition flex items-center justify-center gap-1.5">
                <span wire:loading.remove wire:target="submitWholeSummary({{ $item->id }})">⚡ Submit Semua Box Summary</span>
                <span wire:loading wire:target="submitWholeSummary({{ $item->id }})">⏳ Memproses SAP Transfer...</span>
            </button>
        @endif
    </div>

    {{-- Desktop Subtable View --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full border-collapse bg-white border border-[#E8E4DC] rounded text-xs">
            <thead class="bg-[#F3F4F6] text-[10px] font-bold text-[#4B5563] uppercase">
                <tr>
                    <th class="p-2.5 text-left">Label Box</th>
                    <th class="p-2.5 text-left">Item Code</th>
                    <th class="p-2.5 text-right">Box Qty</th>
                    <th class="p-2.5 w-[140px] text-center">Qty NG (Input QC)</th>
                    <th class="p-2.5 text-right">Qty OK</th>
                    <th class="p-2.5 text-center">Tujuan Transfer</th>
                    <th class="p-2.5 text-center">Status Transfer SAP</th>
                    <th class="p-2.5 text-center">Aksi / Riwayat QC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rowDetails[$item->id] ?? [] as $box)
                    @php
                        $boxId = $box['id'];
                        $isInspected = $box['is_inspected'];
                        $boxQty = (int)$box['quantity'];
                        $log = $box['log'];
                        $isKbn = ($plant === 'kbn' || $item->warehouse === 'FFI');
                        $whOk = $item->warehouse === 'KRFFI' ? 'KRFG' : 'FG';
                        $whNg = $item->warehouse === 'KRFFI' ? 'KRRJCT' : 'RJCT';
                    @endphp
                    <tr class="border-b border-[#E5E7EB] hover:bg-amber-50/20 transition {{ $isInspected ? 'bg-gray-50/70' : 'bg-white' }}">
                        <td class="p-2.5 font-bold text-gray-800">
                            <div>{{ $box['label'] }}</div>
                            @if($isInspected && !empty($log['inspected_at']))
                                <div class="text-[9px] text-emerald-700 font-bold mt-0.5 flex items-center gap-1">
                                    <span>✓ QC:</span> {{ $log['inspected_at'] }}
                                </div>
                            @endif
                        </td>
                        <td class="p-2.5 font-semibold text-gray-700">{{ $box['item_code'] }}</td>
                        <td class="p-2.5 text-right font-extrabold text-gray-900">{{ number_format($boxQty) }}</td>
                        <td class="p-2.5 text-center">
                            @if($isInspected)
                                <span class="font-extrabold text-xs {{ $log['ng_qty'] > 0 ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded border border-red-200' : 'text-gray-500' }}">
                                    {{ number_format($log['ng_qty']) }} PCS
                                </span>
                            @else
                                <div class="inline-flex items-center justify-center">
                                    <input type="number" min="0" max="{{ $boxQty }}"
                                           x-model.number="boxes[{{ $boxId }}].ng"
                                           @input="updateNg({{ $boxId }}, {{ $boxQty }}, $event.target.value)"
                                           :class="(boxes[{{ $boxId }}]?.ng > 0) ? 'border-red-500 text-red-600 bg-red-50 ring-1 ring-red-400' : 'border-gray-300 text-gray-900'"
                                           placeholder="0"
                                           class="w-20 text-center p-1.5 border-2 rounded font-black text-xs transition duration-75 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                            @endif
                        </td>
                        <td class="p-2.5 text-right">
                            @if($isInspected)
                                <span class="font-extrabold text-green-600 text-xs">
                                    {{ number_format($log['ok_qty']) }} PCS
                                </span>
                            @else
                                <span class="font-extrabold text-green-600 text-xs">
                                    <span x-text="getOk({{ $boxId }}, {{ $boxQty }}).toLocaleString()"></span> PCS
                                </span>
                            @endif
                        </td>
                        <td class="p-2.5 text-center text-[11px]">
                            @if($isInspected)
                                @if($isKbn)
                                    <div class="text-[10px] text-gray-400 font-semibold italic">OK: Tidak ke SAP</div>
                                    @if($log['ng_qty'] > 0)
                                        <div class="font-bold">NG: <span class="text-red-600 font-black">{{ $log['ng_to_warehouse'] ?: 'RJCT' }}</span></div>
                                    @endif
                                @else
                                    <div class="font-bold">OK: <span class="text-green-600 font-black">{{ $log['ok_to_warehouse'] }}</span></div>
                                    @if($log['ng_qty'] > 0)
                                        <div class="font-bold">NG: <span class="text-red-600 font-black">{{ $log['ng_to_warehouse'] }}</span></div>
                                    @endif
                                @endif
                            @else
                                @if($isKbn)
                                    <div class="text-[10px] text-gray-400 font-semibold italic">OK: Tidak ke SAP</div>
                                    <div x-show="getNg({{ $boxId }}) > 0" class="font-semibold text-gray-600">NG → <span class="text-red-600 font-black">RJCT</span></div>
                                @else
                                    <div class="font-semibold text-gray-600">OK → <span class="text-green-600 font-black">{{ $whOk }}</span></div>
                                    <div x-show="getNg({{ $boxId }}) > 0" class="font-semibold text-gray-600">NG → <span class="text-red-600 font-black">{{ $whNg }}</span></div>
                                @endif
                            @endif
                        </td>
                        <td class="p-2.5 text-center">
                            @if($isInspected)
                                <div class="flex flex-col gap-0.5 items-center">
                                    @if(!$isKbn && $log['ok_qty'] > 0)
                                        @if($log['ok_sap_status'] == 1)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-green-100 text-green-700">OK: SUKSES</span>
                                        @elseif($log['ok_sap_status'] == 2)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-red-100 text-red-700">OK: GAGAL ⚠</span>
                                            <div class="text-[9px] text-red-600 font-semibold mt-0.5 max-w-[140px] leading-tight text-center">{{ $log['ok_sap_error'] }}</div>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-100 text-amber-700">OK: PENDING</span>
                                        @endif
                                    @endif

                                    @if($log['ng_qty'] > 0)
                                        @if($log['ng_sap_status'] == 1)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-green-100 text-green-700">NG: SUKSES</span>
                                        @elseif($log['ng_sap_status'] == 2)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-red-100 text-red-700">NG: GAGAL ⚠</span>
                                            <div class="text-[9px] text-red-600 font-semibold mt-0.5 max-w-[140px] leading-tight text-center">{{ $log['ng_sap_error'] }}</div>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-100 text-amber-700">NG: PENDING</span>
                                        @endif
                                    @elseif($isKbn)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-green-100 text-green-700">✓ ALL OK</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400 text-[11px] italic font-medium">Belum kirim</span>
                            @endif
                        </td>
                        <td class="p-2.5 text-center">
                            @if($isInspected)
                                @if((!$isKbn && $log['ok_sap_status'] == 2) || $log['ng_sap_status'] == 2)
                                    <button wire:click="retryTransfer({{ $log['id'] }})" wire:loading.attr="disabled" class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white border-none px-2.5 py-1 rounded text-[10px] font-bold cursor-pointer transition">
                                        Retry SAP
                                    </button>
                                @else
                                    <div class="flex flex-col items-center">
                                        <span class="text-emerald-700 font-black text-xs flex items-center gap-1">
                                            <span>✓</span> Selesai QC
                                        </span>
                                        <span class="text-[10px] text-emerald-900 font-bold mt-1 whitespace-nowrap bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded shadow-2xs">
                                            📅 {{ $log['inspected_at'] ?? '-' }}
                                        </span>
                                        @if(!empty($log['inspector_name']))
                                            <span class="text-[9px] text-gray-500 font-semibold mt-0.5">
                                                👤 {{ $log['inspector_name'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <button @click="submitSingle({{ $boxId }}, '{{ $box['label'] }}', {{ $boxQty }})"
                                        wire:loading.attr="disabled"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white border-none px-3 py-1.5 rounded text-xs font-bold cursor-pointer active:scale-95 transition shadow-xs">
                                    <span wire:loading.remove wire:target="submitSingleBox({{ $boxId }}, {{ $item->id }})">Submit Box</span>
                                    <span wire:loading wire:target="submitSingleBox({{ $boxId }}, {{ $item->id }})">⏳ Kirim...</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-4 text-center text-gray-400 font-semibold">Tidak ada box ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Box Cards View --}}
    <div class="block sm:hidden space-y-2.5">
        @forelse($rowDetails[$item->id] ?? [] as $box)
            @php
                $boxId = $box['id'];
                $isInspected = $box['is_inspected'];
                $boxQty = (int)$box['quantity'];
                $log = $box['log'];
                $isKbn = ($plant === 'kbn' || $item->warehouse === 'FFI');
                $whOk = $item->warehouse === 'KRFFI' ? 'KRFG' : 'FG';
                $whNg = $item->warehouse === 'KRFFI' ? 'KRRJCT' : 'RJCT';
            @endphp
            <div class="bg-white border border-[#E8E4DC] rounded-lg p-3 shadow-xs {{ $isInspected ? 'bg-gray-50/80' : 'bg-white' }}">
                <div class="flex justify-between items-start mb-2 pb-2 border-b border-gray-100">
                    <div>
                        <div class="font-extrabold text-xs text-gray-900 flex items-center gap-1.5 flex-wrap">
                            <span>Label: {{ $box['label'] }}</span>
                            @if($isInspected)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    ✓ SELESAI QC
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] font-semibold text-gray-600 mt-0.5">{{ $box['item_code'] }}</div>
                        @if($isInspected && !empty($log['inspected_at']))
                            <div class="text-[10px] text-emerald-900 font-bold mt-1 flex items-center gap-1 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 w-fit">
                                <span>📅 {{ $log['inspected_at'] }}</span>
                                @if(!empty($log['inspector_name']))
                                    <span class="text-emerald-700 font-normal">({{ $log['inspector_name'] }})</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-bold text-gray-400 block uppercase">BOX QTY</span>
                        <span class="font-black text-xs text-gray-900">{{ number_format($boxQty) }} PCS</span>
                    </div>
                </div>

                {{-- Inspection Qty Inputs / Display --}}
                <div class="grid grid-cols-2 gap-2 mb-3 bg-gray-50 p-2 rounded">
                    <div>
                        <span class="text-[10px] font-bold text-gray-500 block uppercase mb-1">QTY NG (INPUT)</span>
                        @if($isInspected)
                            <span class="font-black text-sm {{ $log['ng_qty'] > 0 ? 'text-red-600' : 'text-gray-700' }}">
                                {{ number_format($log['ng_qty']) }} PCS
                            </span>
                        @else
                            <input type="number" min="0" max="{{ $boxQty }}"
                                   x-model.number="boxes[{{ $boxId }}].ng"
                                   @input="updateNg({{ $boxId }}, {{ $boxQty }}, $event.target.value)"
                                   :class="(boxes[{{ $boxId }}]?.ng > 0) ? 'border-red-500 text-red-600 bg-red-50 ring-1 ring-red-400' : 'border-gray-300 text-gray-900'"
                                   placeholder="0"
                                   class="w-full text-center p-1.5 border-2 rounded font-black text-sm transition duration-75 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-gray-500 block uppercase mb-1">QTY OK</span>
                        @if($isInspected)
                            <span class="font-black text-sm text-green-600 block mt-1">
                                {{ number_format($log['ok_qty']) }} PCS
                            </span>
                        @else
                            <span class="font-black text-sm text-green-600 block mt-1">
                                <span x-text="getOk({{ $boxId }}, {{ $boxQty }}).toLocaleString()"></span> PCS
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Warehouse & Status Badges --}}
                <div class="flex justify-between items-center text-[10px] mb-3 px-1">
                    <div class="text-gray-600">
                        @if($isInspected)
                            @if($isKbn)
                                <span class="text-gray-400 italic">OK: Tidak ke SAP</span>
                                @if($log['ng_qty'] > 0)
                                    <span class="font-bold ml-2">NG: <span class="text-red-600 font-black">{{ $log['ng_to_warehouse'] ?: 'RJCT' }}</span></span>
                                @endif
                            @else
                                <span class="font-bold">OK: <span class="text-green-600 font-black">{{ $log['ok_to_warehouse'] }}</span></span>
                                @if($log['ng_qty'] > 0)
                                    <span class="font-bold ml-2">NG: <span class="text-red-600 font-black">{{ $log['ng_to_warehouse'] }}</span></span>
                                @endif
                            @endif
                        @else
                            @if($isKbn)
                                <span class="text-gray-400 italic">OK: Tidak ke SAP</span>
                                <span x-show="getNg({{ $boxId }}) > 0" class="font-semibold ml-2">NG → <span class="text-red-600 font-black">RJCT</span></span>
                            @else
                                <span class="font-semibold">OK → <span class="text-green-600 font-black">{{ $whOk }}</span></span>
                                <span x-show="getNg({{ $boxId }}) > 0" class="font-semibold ml-2">NG → <span class="text-red-600 font-black">{{ $whNg }}</span></span>
                            @endif
                        @endif
                    </div>
                    <div class="text-right">
                        @if($isInspected)
                            @if(!$isKbn && $log['ok_sap_status'] == 1)
                                <span class="px-1.5 py-0.5 rounded font-extrabold bg-green-100 text-green-700">OK: SUKSES</span>
                            @elseif(!$isKbn && $log['ok_sap_status'] == 2)
                                <span class="px-1.5 py-0.5 rounded font-extrabold bg-red-100 text-red-700">OK: GAGAL ⚠</span>
                                <div class="text-[9px] text-red-600 font-semibold mt-0.5 max-w-[150px] leading-tight text-right">{{ $log['ok_sap_error'] }}</div>
                            @endif

                            @if($log['ng_qty'] > 0)
                                @if($log['ng_sap_status'] == 1)
                                    <span class="px-1.5 py-0.5 rounded font-extrabold bg-green-100 text-green-700 ml-1">NG: SUKSES</span>
                                @elseif($log['ng_sap_status'] == 2)
                                    <span class="px-1.5 py-0.5 rounded font-extrabold bg-red-100 text-red-700 ml-1">NG: GAGAL ⚠</span>
                                    <div class="text-[9px] text-red-600 font-semibold mt-0.5 max-w-[150px] leading-tight text-right">{{ $log['ng_sap_error'] }}</div>
                                @endif
                            @elseif($isKbn)
                                <span class="px-1.5 py-0.5 rounded font-extrabold bg-green-100 text-green-700">✓ ALL OK</span>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Submit / Action Button --}}
                <div>
                    @if($isInspected)
                        @if((!$isKbn && $log['ok_sap_status'] == 2) || $log['ng_sap_status'] == 2)
                            <button wire:click="retryTransfer({{ $log['id'] }})" class="w-full bg-red-600 hover:bg-red-700 text-white border-none py-2 rounded text-xs font-bold cursor-pointer transition">
                                Retry Transfer SAP
                            </button>
                        @else
                            <div class="text-center bg-emerald-50 text-emerald-800 border border-emerald-200 rounded p-2 text-xs">
                                <div class="font-black">✓ Box Selesai Diinspeksi</div>
                                <div class="text-[10px] text-emerald-900 font-bold mt-1 flex items-center justify-center gap-1">
                                    <span>📅 {{ $log['inspected_at'] ?? '-' }}</span>
                                    @if(!empty($log['inspector_name']))
                                        <span class="text-emerald-700 font-medium">• 👤 {{ $log['inspector_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @else
                        <button @click="submitSingle({{ $boxId }}, '{{ $box['label'] }}', {{ $boxQty }})"
                                wire:loading.attr="disabled"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white border-none py-2 rounded text-xs font-bold cursor-pointer active:scale-98 transition shadow-xs">
                            <span wire:loading.remove wire:target="submitSingleBox({{ $boxId }}, {{ $item->id }})">Submit Box {{ $box['label'] }}</span>
                            <span wire:loading wire:target="submitSingleBox({{ $boxId }}, {{ $item->id }})">⏳ Mengirim ke SAP...</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-3 text-center text-gray-400 text-xs font-semibold">Tidak ada box ditemukan.</div>
        @endforelse
    </div>
</div>
