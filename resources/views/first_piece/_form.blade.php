<div class="space-y-6 pb-20" x-data="{
    processType: @js($initialProcess ?? old('process_type', '')),
    chemicalProcesses: @js($chemicalProcesses ?? config('mes.chemical_processes', [])),
    get isChemicalProcess() {
        if (!this.processType) return false;
        const p = this.processType.toLowerCase();
        return this.chemicalProcesses.some(cp => p.includes(cp.toLowerCase()));
    },
    customCheckpoints: [],
    addCustomCheckpoint() {
        this.customCheckpoints.push({ check_point: '', method: 'Visual', result: 'OK', judgement: 'OK' });
    },
    removeCustomCheckpoint(idx) {
        this.customCheckpoints.splice(idx, 1);
    }
}">

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl text-sm shadow-sm">
            <div class="font-black uppercase tracking-wider text-xs mb-1">Please fix the following validation errors:</div>
            <ul class="list-disc list-inside space-y-1 font-semibold">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <input type="hidden" name="work_order_id" value="{{ old('work_order_id', $inspection->work_order_id ?? $workOrderId ?? request('work_order_id')) }}">

    {{-- CARD 1: Part & Identification Header --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Part & Header Identification</h3>
                <p class="text-xs text-gray-500 font-medium">Document DI-F-P/PR/07/SP-013 • First Piece Inspection</p>
            </div>
            @php
                $woId = old('work_order_id', $inspection->work_order_id ?? $workOrderId ?? request('work_order_id'));
                $linkedWo = $inspection->workOrder ?? ($workOrder ?? ($woId ? \App\Models\SpWorkOrder::find($woId) : null));
            @endphp
            @if($linkedWo)
                <span class="px-3 py-1 text-[10px] font-black rounded-full bg-blue-100 text-blue-800 uppercase tracking-widest border border-blue-200 shadow-sm font-mono">
                    Linked Work Order: {{ $linkedWo->wo_number }}
                </span>
            @endif
        </div>

        {{-- Work Order Spec Mismatch Warning --}}
        <div id="wo-mismatch-warning" class="hidden mb-4 p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <span class="font-black">Warning:</span> Part Number, Part Name, or Model Code differs from the pre-filled Work Order specs. First Piece Inspection must match the Work Order specs for production gating to unlock properly.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
            {{-- Date --}}
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Inspection Date *</label>
                <input type="date" name="date" value="{{ old('date', $inspection->date ?? date('Y-m-d')) }}"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800" required>
            </div>

            {{-- Process Type --}}
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Process Type</label>
                <input type="text" x-model="processType" name="process_type" value="{{ old('process_type', $initialProcess ?? '') }}" placeholder="e.g. Painting, Assembly..."
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800">
            </div>

            {{-- Model Code --}}
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Model Code (Optional)</label>
                <input type="text" name="model" id="model" value="{{ old('model', $inspection->model) }}"
                    data-orig-model="{{ old('model', $inspection->model) }}" placeholder="e.g. KS PE"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800">
            </div>

            {{-- Part Name --}}
            <div class="relative">
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Part Name *</label>
                <input type="text" name="part_name" id="part_name" value="{{ old('part_name', $inspection->part_name) }}"
                    data-orig-part-name="{{ old('part_name', $inspection->part_name) }}" placeholder="Search or enter Part Name..." autocomplete="off"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800" required>
                <div id="part-name-dropdown" class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg z-50 hidden"></div>
            </div>

            {{-- Part Number --}}
            <div class="relative">
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Part Number *</label>
                <input type="text" name="part_number" id="part_number" value="{{ old('part_number', $inspection->part_number) }}"
                    data-orig-part-number="{{ old('part_number', $inspection->part_number) }}" placeholder="Search Part Number..." autocomplete="off"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800" required>
                <div id="part-number-dropdown" class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg z-50 hidden"></div>
            </div>
        </div>
    </div>

    {{-- CARD 2: Process & Material Parameters --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
        <div class="pb-3 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Process & Material Parameters</h3>
                <p class="text-xs text-gray-500 font-medium" x-text="isChemicalProcess ? 'Chemical & paint specifications for ' + processType : 'General process parameters'"></p>
            </div>
        </div>

        {{-- Conditional Chemical Fields --}}
        <div x-show="isChemicalProcess" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Paint / Material Code</label>
                <input type="text" name="paint_code" value="{{ old('paint_code', $inspection->paint_code) }}" placeholder="e.g. DR 249 - 8M8"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold">
            </div>

            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Thinner Code</label>
                <input type="text" name="thinner_code" value="{{ old('thinner_code', $inspection->thinner_code) }}" placeholder="e.g. T971"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold">
            </div>

            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Ink Code</label>
                <input type="text" name="ink_code" value="{{ old('ink_code', $inspection->ink_code) }}" placeholder="e.g. INK-90"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold">
            </div>

            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Viscosity</label>
                <input type="text" name="viscosity" value="{{ old('viscosity', $inspection->viscosity) }}" placeholder="e.g. 14 sec NK-2"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold">
            </div>
        </div>

        {{-- Always visible general parameters --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Cycle Time</label>
                <input type="text" name="cycle_time" value="{{ old('cycle_time', $inspection->cycle_time) }}" placeholder="e.g. 45 sec"
                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold">
            </div>
        </div>

        {{-- Informational note when non-chemical process --}}
        <div x-show="!isChemicalProcess" class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-800 flex justify-between items-center">
            <span>Paint & chemical parameters (Paint Code, Thinner, Ink, Viscosity) are hidden for non-chemical processes.</span>
            <button type="button" @click="processType = 'Painting'" class="text-[11px] font-black text-blue-700 hover:underline uppercase">
                Show Painting Fields
            </button>
        </div>
    </div>

    {{-- CARD 3: Quality Control Checkpoints Table --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Quality Control Checkpoints</h3>
                <p class="text-xs text-gray-500 font-medium">Verify each defect criteria. Tap OK or NG for each checkpoint or add custom criteria.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <button type="button" @click="addCustomCheckpoint()" class="w-full sm:w-auto bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 font-bold px-4 py-2 rounded-xl text-xs transition shadow-sm active:scale-95">
                    + Add Custom Checkpoint
                </button>
                <button type="button" id="btn-mark-all-ok" class="w-full sm:w-auto bg-green-100 hover:bg-green-200 text-green-800 border border-green-300 font-black px-4 py-2 rounded-xl text-xs transition shadow-sm active:scale-95">
                    Mark All OK
                </button>
            </div>
        </div>

        @php
            $existingResults = old('check_results', $inspection->check_results ?? []);
            if (empty($existingResults)) {
                $existingResults = array_map(function($cp) {
                    return ['check_point' => $cp, 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'];
                }, $defaultCheckPoints);
            }
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase text-[10px] font-black tracking-wider border-b border-gray-200">
                        <th class="py-3 px-4 w-1/3">Check Point</th>
                        <th class="py-3 px-4 text-center w-1/4">Method</th>
                        <th class="py-3 px-4 text-center w-1/3">Inspection Result</th>
                        <th class="py-3 px-4 text-center w-1/6">Judgement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($existingResults as $idx => $row)
                        @php
                            $currResult = $row['result'] ?? 'OK';
                        @endphp
                        <tr class="hover:bg-gray-50 transition" id="checkpoint-row-{{ $idx }}">
                            <td class="py-3.5 px-4 font-bold text-gray-800">
                                <input type="hidden" name="check_results[{{ $idx }}][check_point]" value="{{ $row['check_point'] }}">
                                {{ $row['check_point'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center text-xs font-semibold text-gray-500">
                                <input type="hidden" name="check_results[{{ $idx }}][method]" value="{{ $row['method'] ?? 'Visual' }}">
                                {{ $row['method'] ?? 'Visual' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <input type="hidden" name="check_results[{{ $idx }}][result]" id="result-input-{{ $idx }}" value="{{ $currResult }}">
                                <div class="inline-flex rounded-xl p-1 bg-gray-100 border border-gray-200">
                                    <button type="button" data-idx="{{ $idx }}" data-val="OK"
                                        class="btn-toggle-result px-5 py-1.5 rounded-lg text-xs font-black transition-all {{ $currResult === 'OK' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                        OK
                                    </button>
                                    <button type="button" data-idx="{{ $idx }}" data-val="NG"
                                        class="btn-toggle-result px-5 py-1.5 rounded-lg text-xs font-black transition-all {{ $currResult === 'NG' ? 'bg-red-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                                        NG
                                    </button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-black">
                                <input type="hidden" name="check_results[{{ $idx }}][judgement]" id="judgement-input-{{ $idx }}" value="{{ $row['judgement'] ?? $currResult }}">
                                <span id="judgement-badge-{{ $idx }}" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ ($row['judgement'] ?? $currResult) === 'OK' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $row['judgement'] ?? $currResult }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Dynamic Custom Checkpoints --}}
                    @php $nextIdx = count($existingResults); @endphp
                    <template x-for="(cp, idx) in customCheckpoints" :key="idx">
                        <tr class="hover:bg-blue-50/50 transition">
                            <td class="py-3.5 px-4">
                                <input type="text" :name="`check_results[${@js($nextIdx)} + idx][check_point]`"
                                       x-model="cp.check_point" placeholder="Enter custom defect criteria (e.g. Scratch Mark)..."
                                       class="w-full rounded-xl border-gray-300 text-xs font-bold focus:ring-blue-500" required>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <input type="text" :name="`check_results[${@js($nextIdx)} + idx][method]`"
                                       x-model="cp.method" placeholder="Method (e.g. Visual)"
                                       class="w-full rounded-xl border-gray-300 text-xs text-center font-semibold focus:ring-blue-500">
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <input type="hidden" :name="`check_results[${@js($nextIdx)} + idx][result]`" :value="cp.result">
                                <div class="inline-flex rounded-xl p-1 bg-gray-100 border border-gray-200">
                                    <button type="button" @click="cp.result = 'OK'; cp.judgement = 'OK'"
                                        :class="cp.result === 'OK' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-5 py-1.5 rounded-lg text-xs font-black transition-all">
                                        OK
                                    </button>
                                    <button type="button" @click="cp.result = 'NG'; cp.judgement = 'NG'"
                                        :class="cp.result === 'NG' ? 'bg-red-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-5 py-1.5 rounded-lg text-xs font-black transition-all">
                                        NG
                                    </button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-black">
                                <input type="hidden" :name="`check_results[${@js($nextIdx)} + idx][judgement]`" :value="cp.judgement">
                                <div class="flex items-center justify-center gap-2">
                                    <span :class="cp.judgement === 'OK' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider" x-text="cp.judgement">
                                    </span>
                                    <button type="button" @click="removeCustomCheckpoint(idx)" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase p-1" title="Remove checkpoint">
                                        &times;
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARD 4: Photos & Remarks --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-6">
        <div>
            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Quality Notes & Remarks</label>
            <textarea name="remark" rows="3" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-medium" placeholder="Any quality issue notes, surface observations, or special instructions...">{{ old('remark', $inspection->remark) }}</textarea>
        </div>

        <div>
            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Physical Sample & Proof Photos
            </h3>

            @if($inspection->exists && $inspection->attachments->count() > 0)
                <div class="mb-4">
                    <div class="text-[10px] font-black text-gray-400 uppercase mb-2">Existing Attachments:</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach($inspection->attachments as $attach)
                            <div class="border border-gray-200 rounded-xl p-2 bg-gray-50 flex flex-col items-center relative group">
                                <a href="{{ $attach->url }}" target="_blank" class="block w-full text-center">
                                    @if(str_contains($attach->mime_type ?? '', 'image'))
                                        <img src="{{ $attach->url }}" alt="{{ $attach->label }}" class="h-24 object-cover mx-auto rounded-lg border">
                                    @else
                                        <div class="h-24 flex items-center justify-center bg-white rounded-lg text-xs text-gray-500 font-bold border">
                                            {{ $attach->original_name }}
                                        </div>
                                    @endif
                                    <div class="text-[11px] font-bold text-gray-700 truncate mt-1">{{ $attach->label ?? $attach->original_name }}</div>
                                </a>
                                <label class="mt-2 flex items-center text-xs text-red-600 font-bold cursor-pointer">
                                    <input type="checkbox" name="delete_attachments[]" value="{{ $attach->id }}" class="rounded text-red-600 mr-1">
                                    Delete
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div x-data="{
                uploads: [],
                presets: ['Front View', 'Side View', 'Close-up Defect', 'Measurement', 'Physical Sample'],
                addSlot(presetLabel = '') {
                    this.uploads.push({ label: presetLabel, file: null, preview: null });
                },
                removeSlot(idx) {
                    this.uploads.splice(idx, 1);
                },
                handleFile(idx, event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.uploads[idx].file = file;
                    if (!this.uploads[idx].label) {
                        const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                        this.uploads[idx].label = nameWithoutExt;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => { this.uploads[idx].preview = e.target.result; };
                    reader.readAsDataURL(file);
                }
            }" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-black text-gray-500 uppercase tracking-wider">Quick Presets:</span>
                    <template x-for="preset in presets" :key="preset">
                        <button type="button" @click="addSlot(preset)"
                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 font-bold text-xs rounded-xl transition shadow-xs active:scale-95">
                            + <span x-text="preset"></span>
                        </button>
                    </template>
                    <button type="button" @click="addSlot('')"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 font-bold text-xs rounded-xl transition shadow-xs active:scale-95">
                        + Add Custom Photo Slot
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(slot, idx) in uploads" :key="idx">
                        <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl transition hover:border-blue-300">
                            {{-- Thumbnail Preview --}}
                            <div class="w-16 h-16 flex-shrink-0 bg-white border border-gray-200 rounded-xl overflow-hidden flex items-center justify-center shadow-xs">
                                <template x-if="slot.preview">
                                    <img :src="slot.preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!slot.preview">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </template>
                            </div>

                            {{-- Label + File Input --}}
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Custom Photo Label</label>
                                    <input type="text" :name="`qc_file_labels[${idx}]`" x-model="slot.label"
                                        placeholder="e.g. Front View, Defect Detail..."
                                        class="w-full rounded-xl border-gray-300 text-xs font-bold text-gray-800 focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Select File *</label>
                                    <input type="file" :name="`qc_files[${idx}]`" accept="image/*"
                                        @change="handleFile(idx, $event)"
                                        class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border file:border-gray-300 file:text-xs file:font-bold file:bg-white file:text-gray-700 hover:file:bg-gray-100 cursor-pointer">
                                </div>
                            </div>

                            {{-- Remove Button --}}
                            <button type="button" @click="removeSlot(idx)"
                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition font-black text-lg" title="Remove upload slot">
                                &times;
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="uploads.length === 0">
                    <div class="text-center py-8 text-xs text-gray-400 font-semibold border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                        Click a quick preset button above or <strong class="text-blue-600 cursor-pointer" @click="addSlot('')">+ Add Custom Photo Slot</strong> to attach proof photos with labels.
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- STICKY FLOATING ACTION BAR --}}
    <div class="fixed bottom-4 left-4 right-4 lg:left-72 max-w-7xl mx-auto bg-white/95 backdrop-blur-md p-4 rounded-2xl border border-gray-200 shadow-2xl z-40 flex justify-between items-center gap-4">
        <a href="{{ request('work_order_id') ? route('sp-work-orders.show', request('work_order_id')) : route('first-piece-inspections.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-xl transition text-xs uppercase tracking-wider">
            Cancel
        </a>
        <div class="flex items-center gap-3">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-black py-3 px-6 rounded-xl shadow transition text-xs uppercase tracking-wider">
                {{ $inspection->exists ? 'Save Changes' : 'Save Draft' }}
            </button>
            @if(!$inspection->exists || empty($inspection->checked_at))
                <button type="submit" name="auto_approve" value="1" class="bg-green-600 hover:bg-green-700 text-white font-black py-3 px-8 rounded-xl shadow-lg transition text-xs uppercase tracking-wider flex items-center gap-2 transform hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save & Approve (QC)
                </button>
            @endif
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Individual OK/NG toggle handler
    const toggleBtns = document.querySelectorAll('.btn-toggle-result');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.dataset.idx;
            const val = this.dataset.val;
            setCheckpointState(idx, val);
        });
    });

    function setCheckpointState(idx, val) {
        const resInput = document.getElementById('result-input-' + idx);
        const judgeInput = document.getElementById('judgement-input-' + idx);
        const badge = document.getElementById('judgement-badge-' + idx);
        const btns = document.querySelectorAll(`.btn-toggle-result[data-idx="${idx}"]`);

        if (resInput) resInput.value = val;
        if (judgeInput) judgeInput.value = val;
        
        if (badge) {
            badge.textContent = val;
            if (val === 'OK') {
                badge.className = 'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-green-100 text-green-800';
            } else {
                badge.className = 'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-red-100 text-red-800';
            }
        }

        btns.forEach(b => {
            if (b.dataset.val === val) {
                b.className = `btn-toggle-result px-5 py-1.5 rounded-lg text-xs font-black transition-all ${val === 'OK' ? 'bg-green-600 text-white shadow-sm' : 'bg-red-600 text-white shadow-sm'}`;
            } else {
                b.className = 'btn-toggle-result px-5 py-1.5 rounded-lg text-xs font-black transition-all text-gray-600 hover:text-gray-900';
            }
        });
    }

    // Mark All OK master button
    const markAllBtn = document.getElementById('btn-mark-all-ok');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const resultInputs = document.querySelectorAll('[id^="result-input-"]');
            resultInputs.forEach((input) => {
                const idx = input.id.replace('result-input-', '');
                setCheckpointState(idx, 'OK');
            });
        });
    }

    // Master Item Autocomplete Suggestions
    function setupAutocomplete(inputId, dropdownId, url, onSelect) {
        const input = document.getElementById(inputId);
        const dropdown = document.getElementById(dropdownId);
        if (!input || !dropdown) return;

        let debounceTimer;
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 1) {
                dropdown.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`${url}?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (!data || data.length === 0) {
                            dropdown.classList.add('hidden');
                            return;
                        }

                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-xs border-b border-gray-100 last:border-b-0 text-gray-800 transition flex justify-between items-center';
                            if (item.item_code) {
                                div.innerHTML = `
                                    <div>
                                        <span class="font-bold text-blue-700">${item.item_code}</span> 
                                        <span class="text-gray-600 font-medium ml-1">${item.item_name || item.item_description || ''}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 uppercase font-mono">${item.project_code || ''}</span>
                                `;
                            }
                            div.addEventListener('click', () => {
                                onSelect(item);
                                dropdown.classList.add('hidden');
                            });
                            dropdown.appendChild(div);
                        });
                        dropdown.classList.remove('hidden');
                    })
                    .catch(err => console.error(err));
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (e.target !== input && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    function populateFromItem(item) {
        if (item.item_code) document.getElementById('part_number').value = item.item_code;
        if (item.item_name || item.item_description) {
            document.getElementById('part_name').value = item.item_name || item.item_description;
        }
        if (item.project_code) {
            document.getElementById('model').value = item.project_code;
        }
    }

    setupAutocomplete('part_number', 'part-number-dropdown', '{{ route('second-process-reports.search-items') }}', function(item) {
        populateFromItem(item);
        checkMismatch();
    });
    setupAutocomplete('part_name', 'part-name-dropdown', '{{ route('second-process-reports.search-items') }}', function(item) {
        populateFromItem(item);
        checkMismatch();
    });

    // Work Order Spec Mismatch Detection
    const pnInput = document.getElementById('part_number');
    const nameInput = document.getElementById('part_name');
    const modelInput = document.getElementById('model');
    const warningBox = document.getElementById('wo-mismatch-warning');

    const origPn = pnInput ? pnInput.dataset.origPartNumber : '';
    const origName = nameInput ? nameInput.dataset.origPartName : '';
    const origModel = modelInput ? modelInput.dataset.origModel : '';

    function checkMismatch() {
        if (!warningBox || !origPn) return;
        const currentPn = pnInput ? pnInput.value.trim() : '';
        const currentName = nameInput ? nameInput.value.trim() : '';
        const currentModel = modelInput ? modelInput.value.trim() : '';

        const isChanged = (currentPn !== origPn) || (currentName !== origName) || (currentModel !== origModel);
        if (isChanged && origPn !== '') {
            warningBox.classList.remove('hidden');
        } else {
            warningBox.classList.add('hidden');
        }
    }

    [pnInput, nameInput, modelInput].forEach(inp => {
        if (inp) {
            inp.addEventListener('input', checkMismatch);
            inp.addEventListener('change', checkMismatch);
        }
    });
});
</script>
