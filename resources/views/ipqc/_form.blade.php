@php
    $inspection = $inspection ?? new \App\Models\IpqcInspection();
    $recordsCount = max(8, $inspection->records ? $inspection->records->count() : 8);
    $selectedMeas = $inspection->selected_measurements ?? ['act_oven_temp', 'viscosity', 'cycle_time', 'nichiban_test'];
    if (is_string($selectedMeas)) $selectedMeas = json_decode($selectedMeas, true) ?? [];
@endphp

<input type="hidden" name="status" id="inspection-status" value="{{ $inspection->status ?? 'ongoing' }}">

{{-- SECTION 1: Context Header --}}
<div class="bg-white p-6 shadow-sm border border-purple-200 rounded-lg mb-6">
    <h3 class="text-lg font-bold text-purple-800 border-b border-purple-200 pb-2 mb-4">1. General Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
            <input type="date" name="date" value="{{ old('date', $inspection->date ? \Carbon\Carbon::parse($inspection->date)->format('Y-m-d') : date('Y-m-d')) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500" required>
        </div>
        <div class="relative">
            <label class="block text-xs font-bold text-gray-700 mb-1">Part Number <span class="text-red-500">*</span></label>
            <input type="text" name="part_number" id="part_number" value="{{ old('part_number', $inspection->part_number) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500" required autocomplete="off" placeholder="Type to search...">
            <div id="part-autocomplete-results" class="absolute z-10 w-full bg-white border border-gray-300 rounded shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Part Name</label>
            <input type="text" name="part_name" id="part_name" value="{{ old('part_name', $inspection->part_name) }}" class="w-full rounded border-gray-300 text-sm bg-gray-50 focus:ring-purple-500" readonly>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Customer</label>
            <input type="text" name="customer" id="customer" value="{{ old('customer', $inspection->customer) }}" class="w-full rounded border-gray-300 text-sm bg-gray-50 focus:ring-purple-500" readonly>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Model</label>
            <input type="text" name="model" id="model" value="{{ old('model', $inspection->model) }}" class="w-full rounded border-gray-300 text-sm bg-gray-50 focus:ring-purple-500" readonly>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Shift <span class="text-red-500">*</span></label>
            <select name="shift" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500" required>
                <option value="1" {{ old('shift', $inspection->shift) == '1' ? 'selected' : '' }}>Shift 1</option>
                <option value="2" {{ old('shift', $inspection->shift) == '2' ? 'selected' : '' }}>Shift 2</option>
                <option value="3" {{ old('shift', $inspection->shift) == '3' ? 'selected' : '' }}>Shift 3</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Unit / Line</label>
            <input type="text" name="unit_line" value="{{ old('unit_line', $inspection->unit_line) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Process</label>
            <input type="text" name="process_prod" value="{{ old('process_prod', $inspection->process_prod) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
    </div>
</div>

{{-- SECTION 2: Setup Spec --}}
<div class="bg-white p-6 shadow-sm border border-purple-200 rounded-lg mb-6">
    <h3 class="text-lg font-bold text-purple-800 border-b border-purple-200 pb-2 mb-4">2. IPQC Setup Specification</h3>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Lot Color</label>
            <input type="text" name="lot_color" value="{{ old('lot_color', $inspection->lot_color) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Std Glossy</label>
            <input type="text" name="std_glossy" value="{{ old('std_glossy', $inspection->std_glossy) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Std Viscosity</label>
            <input type="text" name="std_viscosity" value="{{ old('std_viscosity', $inspection->std_viscosity) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Std Oven Temp</label>
            <input type="text" name="std_oven_temp" value="{{ old('std_oven_temp', $inspection->std_oven_temp) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Product Color</label>
            <input type="text" name="product_color" value="{{ old('product_color', $inspection->product_color) }}" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">App Sample</label>
            <select name="app_sample" class="w-full rounded border-gray-300 text-sm focus:ring-purple-500">
                <option value="YES" {{ old('app_sample', $inspection->app_sample) == 'YES' ? 'selected' : '' }}>YES</option>
                <option value="NO" {{ old('app_sample', $inspection->app_sample) == 'NO' ? 'selected' : '' }}>NO</option>
            </select>
        </div>
    </div>
</div>

{{-- SECTION 3: Measurements Selector --}}
<div class="bg-white p-6 shadow-sm border border-purple-200 rounded-lg mb-6">
    <h3 class="text-lg font-bold text-purple-800 border-b border-purple-200 pb-2 mb-4">3. Select Measurement Fields</h3>
    <div class="flex flex-wrap gap-4">
        @if(isset($ipqcMeasurements))
            @foreach($ipqcMeasurements as $meas)
                <label class="inline-flex items-center">
                    <input type="checkbox" name="selected_measurements[]" value="{{ $meas->field_key }}" class="rounded text-purple-600 focus:ring-purple-500 meas-toggle-cb" {{ in_array($meas->field_key, $selectedMeas) ? 'checked' : '' }} data-col="col-meas-{{ $meas->field_key }}">
                    <span class="ml-2 text-sm text-gray-700">{{ $meas->name }}</span>
                </label>
            @endforeach
        @endif
    </div>
</div>

{{-- SECTION 4: Records Table --}}
<div class="bg-white p-6 shadow-sm border border-purple-200 rounded-lg mb-6 overflow-hidden">
    <h3 class="text-lg font-bold text-purple-800 border-b border-purple-200 pb-2 mb-4">4. Hourly Inspection Records (Per 2-Hour)</h3>
    <div class="overflow-x-auto">
        <table class="w-full min-w-max border-collapse border border-gray-300 text-sm text-center" id="inspection-table">
            <thead class="bg-purple-50">
                <tr>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Hour</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Fitting Test</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Appearance Checks</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Condition Checks</th>
                    
                    @if(isset($ipqcMeasurements))
                        @foreach($ipqcMeasurements as $meas)
                            <th class="border border-gray-300 p-2 font-bold text-purple-800 col-meas-{{ $meas->field_key }} {{ in_array($meas->field_key, $selectedMeas) ? '' : 'hidden' }}">{{ $meas->name }}</th>
                        @endforeach
                    @endif

                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Tape Test</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Output</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Sample</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Rej Samp</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Rej Rate %</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Pass Qty</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Rej Qty</th>
                    <th class="border border-gray-300 p-2 font-bold text-purple-800">Judgement</th>
                </tr>
            </thead>
            <tbody>
                @for($h = 1; $h <= $recordsCount; $h++)
                    @php
                        $rec = $inspection->records ? $inspection->records->where('hour_ke', $h)->first() : null;
                        $appChecks = $rec && $rec->appearance_checks ? (is_string($rec->appearance_checks) ? json_decode($rec->appearance_checks, true) : $rec->appearance_checks) : [];
                        $condChecks = $rec && $rec->condition_checks ? (is_string($rec->condition_checks) ? json_decode($rec->condition_checks, true) : $rec->condition_checks) : [];
                        $measValues = $rec && $rec->measurements ? (is_string($rec->measurements) ? json_decode($rec->measurements, true) : $rec->measurements) : [];
                    @endphp
                    <tr>
                        <td class="border border-gray-300 p-1 font-bold bg-gray-50">
                            {{ $h }}
                            <input type="hidden" name="ipqc[{{ $h }}][hour_ke]" value="{{ $h }}">
                            @if($rec)
                                <input type="hidden" name="ipqc[{{ $h }}][id]" value="{{ $rec->id }}">
                            @endif
                        </td>
                        <td class="border border-gray-300 p-1">
                            <select name="ipqc[{{ $h }}][fitting_test]" class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-purple-500">
                                <option value="OK" {{ ($rec->fitting_test ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                                <option value="NG" {{ ($rec->fitting_test ?? '') == 'NG' ? 'selected' : '' }}>NG</option>
                                <option value="-" {{ ($rec->fitting_test ?? '') == '-' ? 'selected' : '' }}>-</option>
                            </select>
                        </td>
                        <td class="border border-gray-300 p-1 min-w-[150px]">
                            <details class="text-left bg-white border border-gray-200 rounded p-1 text-xs">
                                <summary class="font-bold cursor-pointer text-purple-600">Appearance...</summary>
                                <div class="mt-2 space-y-1 max-h-40 overflow-y-auto pr-1">
                                    @if(isset($ipqcCheckItems))
                                        @foreach($ipqcCheckItems->where('category', 'appearance') as $item)
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span>{{ $item->name }}</span>
                                                <input type="number" name="ipqc[{{ $h }}][appearance_checks][{{ $item->name }}]" value="{{ $appChecks[$item->name] ?? '' }}" class="w-12 h-6 text-xs p-1 border-gray-300 rounded" min="0">
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </details>
                        </td>
                        <td class="border border-gray-300 p-1 min-w-[150px]">
                            <details class="text-left bg-white border border-gray-200 rounded p-1 text-xs">
                                <summary class="font-bold cursor-pointer text-purple-600">Condition...</summary>
                                <div class="mt-2 space-y-1 max-h-40 overflow-y-auto pr-1">
                                    @if(isset($ipqcCheckItems))
                                        @foreach($ipqcCheckItems->where('category', 'condition') as $item)
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span>{{ $item->name }}</span>
                                                <input type="number" name="ipqc[{{ $h }}][condition_checks][{{ $item->name }}]" value="{{ $condChecks[$item->name] ?? '' }}" class="w-12 h-6 text-xs p-1 border-gray-300 rounded" min="0">
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </details>
                        </td>

                        @if(isset($ipqcMeasurements))
                            @foreach($ipqcMeasurements as $meas)
                                <td class="border border-gray-300 p-1 col-meas-{{ $meas->field_key }} {{ in_array($meas->field_key, $selectedMeas) ? '' : 'hidden' }}">
                                    <input type="text" name="ipqc[{{ $h }}][measurements][{{ $meas->field_key }}]" value="{{ $measValues[$meas->field_key] ?? '' }}" class="w-full min-w-[60px] text-xs p-1 border-gray-300 rounded focus:ring-purple-500">
                                </td>
                            @endforeach
                        @endif

                        <td class="border border-gray-300 p-1">
                            <select name="ipqc[{{ $h }}][tape_test_judgement]" class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-purple-500 font-bold">
                                <option value="OK" {{ ($rec->tape_test_judgement ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                                <option value="NG" {{ ($rec->tape_test_judgement ?? '') == 'NG' ? 'selected' : '' }}>NG</option>
                                <option value="-" {{ ($rec->tape_test_judgement ?? '') == '-' ? 'selected' : '' }}>-</option>
                            </select>
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="ipqc[{{ $h }}][output_qty]" value="{{ $rec->output_qty ?? '' }}" class="w-16 text-xs p-1 border-gray-300 rounded" min="0">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="ipqc[{{ $h }}][sample_qty]" value="{{ $rec->sample_qty ?? '' }}" class="w-16 text-xs p-1 border-gray-300 rounded row-sample" min="0" data-row="{{ $h }}">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="ipqc[{{ $h }}][reject_sample_qty]" value="{{ $rec->reject_sample_qty ?? '' }}" class="w-16 text-xs p-1 border-gray-300 rounded row-reject" min="0" data-row="{{ $h }}">
                        </td>
                        <td class="border border-gray-300 p-1 font-bold text-red-600 bg-gray-50" id="rate-{{ $h }}">
                            {{ $rec ? number_format($rec->reject_rate, 2) : '0.00' }}%
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="ipqc[{{ $h }}][pass_qty]" value="{{ $rec->pass_qty ?? '' }}" class="w-16 text-xs p-1 border-gray-300 rounded text-green-700 font-bold" min="0">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="ipqc[{{ $h }}][reject_qty]" value="{{ $rec->reject_qty ?? '' }}" class="w-16 text-xs p-1 border-gray-300 rounded text-red-700 font-bold" min="0">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <select name="ipqc[{{ $h }}][judgement]" class="w-full text-xs rounded border-gray-300 py-1 px-2 font-bold focus:ring-purple-500">
                                <option value="OK" {{ ($rec->judgement ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                                <option value="NG" {{ ($rec->judgement ?? '') == 'NG' ? 'selected' : '' }}>NG</option>
                                <option value="-" {{ ($rec->judgement ?? '') == '-' ? 'selected' : '' }}>-</option>
                            </select>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

{{-- SECTION 5: Sign & Attachments --}}
<div class="bg-white p-6 shadow-sm border border-purple-200 rounded-lg mb-6">
    <h3 class="text-lg font-bold text-purple-800 border-b border-purple-200 pb-2 mb-4">5. Sign-off & Attachments</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Inspector Name</label>
            <input type="text" name="inspector_name" value="{{ old('inspector_name', $inspection->inspector_name) }}" class="w-full rounded border-gray-300 focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Checker (Leader) Name</label>
            <input type="text" name="checker_name" value="{{ old('checker_name', $inspection->checker_name) }}" class="w-full rounded border-gray-300 focus:ring-purple-500">
        </div>
    </div>
    
    <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Upload QC Attachments</label>
        <input type="file" name="qc_report_files[]" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
        <p class="text-xs text-gray-500 mt-1">Select multiple images if needed.</p>
    </div>

    @if($inspection->attachments && $inspection->attachments->count() > 0)
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($inspection->attachments as $att)
                <div class="border rounded p-2 text-center bg-gray-50 relative">
                    <img src="{{ $att->url }}" class="h-24 object-contain mx-auto mb-2">
                    <label class="flex items-center justify-center text-xs text-red-600 gap-1 cursor-pointer">
                        <input type="checkbox" name="delete_attachments[]" value="{{ $att->id }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        Delete this file
                    </label>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- SECTION 6: Actions --}}
<div class="flex justify-end gap-4">
    <button type="submit" onclick="document.getElementById('inspection-status').value='ongoing'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded shadow-sm transition">
        Save & Continue (Ongoing)
    </button>
    <button type="submit" onclick="document.getElementById('inspection-status').value='completed'" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded shadow-sm transition">
        Complete Inspection
    </button>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Measurement column toggle
        const checkboxes = document.querySelectorAll('.meas-toggle-cb');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const colClass = this.getAttribute('data-col');
                const cols = document.querySelectorAll('.' + colClass);
                cols.forEach(col => {
                    if (this.checked) {
                        col.classList.remove('hidden');
                    } else {
                        col.classList.add('hidden');
                    }
                });
            });
        });

        // Auto calculate reject rate
        const calculateRate = (row) => {
            const sampleInput = document.querySelector(`.row-sample[data-row="${row}"]`);
            const rejectInput = document.querySelector(`.row-reject[data-row="${row}"]`);
            const rateDisplay = document.getElementById(`rate-${row}`);
            
            if (sampleInput && rejectInput && rateDisplay) {
                const sample = parseFloat(sampleInput.value) || 0;
                const reject = parseFloat(rejectInput.value) || 0;
                if (sample > 0) {
                    const rate = (reject / sample) * 100;
                    rateDisplay.textContent = rate.toFixed(2) + '%';
                } else {
                    rateDisplay.textContent = '0.00%';
                }
            }
        };

        document.querySelectorAll('.row-sample, .row-reject').forEach(input => {
            input.addEventListener('input', function() {
                calculateRate(this.getAttribute('data-row'));
            });
        });

        // Part number autocomplete
        const partInput = document.getElementById('part_number');
        const resultsDiv = document.getElementById('part-autocomplete-results');
        let timeout = null;

        partInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value;
            
            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            timeout = setTimeout(() => {
                fetch(`/ipqc-inspections/search-items?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'p-2 hover:bg-purple-50 cursor-pointer border-b text-sm';
                                div.textContent = `${item.part_number} - ${item.part_name} (${item.customer})`;
                                div.addEventListener('click', () => {
                                    partInput.value = item.part_number;
                                    document.getElementById('part_name').value = item.part_name || '';
                                    document.getElementById('customer').value = item.customer || '';
                                    document.getElementById('model').value = item.model || '';
                                    resultsDiv.classList.add('hidden');
                                });
                                resultsDiv.appendChild(div);
                            });
                            resultsDiv.classList.remove('hidden');
                        } else {
                            resultsDiv.classList.add('hidden');
                        }
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!partInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.add('hidden');
            }
        });
    });
</script>
@endpush
