<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-xl text-gray-800 uppercase tracking-wide">
                    {{ __('Edit Work Order') }}: {{ $workOrder->wo_number }}
                </h2>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Modify planned production order parameters</p>
            </div>
            <a href="{{ route('sp-work-orders.show', $workOrder->id) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 shadow-sm transition uppercase tracking-wider">
                &larr; Back to Details
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Update Parameters</h3>
                    <p class="text-xs text-gray-500 font-medium">Modify product specifications or shop floor setup</p>
                </div>

                <form action="{{ route('sp-work-orders.update', $workOrder->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- SECTION 1: Product Specifications (Catalog Lookup) --}}
                    <div class="bg-gray-50/80 p-5 rounded-2xl border border-gray-200 space-y-4">
                        <div class="text-[11px] font-black text-gray-500 uppercase tracking-widest">
                            1. Product Specifications (Catalog Lookup)
                        </div>

                        {{-- Part Number (Primary Lookup) --}}
                        <div class="relative">
                            <label class="block text-[11px] font-black text-gray-600 uppercase tracking-wider mb-1">Part Number (Primary Lookup) *</label>
                            <input type="text" name="part_number" id="part_number" value="{{ old('part_number', $workOrder->part_number) }}" placeholder="Search Part Number..." required autocomplete="off"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-900 bg-white">
                            <div id="part-number-dropdown" class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg z-50 hidden"></div>
                        </div>

                        {{-- Specs 3-Column Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="relative">
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Part Name *</label>
                                <input type="text" name="part_name" id="part_name" value="{{ old('part_name', $workOrder->part_name) }}" placeholder="Search or enter Part Name..." required autocomplete="off"
                                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800 bg-white">
                                <div id="part-name-dropdown" class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg z-50 hidden"></div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Model Code</label>
                                <input type="text" name="model" id="model" value="{{ old('model', ($workOrder->model === '0' || $workOrder->model === '-' || !$workOrder->model) ? 'N/A' : $workOrder->model) }}" placeholder="e.g. D01D or N/A"
                                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800 bg-white">
                            </div>

                            <div class="relative">
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Customer Name</label>
                                <input type="text" name="customer" id="customer" value="{{ old('customer', ($workOrder->customer === '0' || $workOrder->customer === '-' || !$workOrder->customer) ? 'N/A' : $workOrder->customer) }}" placeholder="Enter Customer or N/A..." autocomplete="off"
                                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800 bg-white">
                                <div id="customer-dropdown" class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg z-50 hidden"></div>
                            </div>
                        </div>

                        <div id="auto-fill-notice" class="hidden text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl text-center">
                            Auto-filled from Master Catalog
                        </div>
                    </div>

                    {{-- SECTION 2: Production Setup & Schedule --}}
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 space-y-4">
                        <div class="text-[11px] font-black text-gray-500 uppercase tracking-widest">
                            2. Production Setup & Schedule
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Work Order No. (Readonly)</label>
                                <input type="text" name="wo_number" value="{{ $workOrder->wo_number }}" readonly
                                    class="w-full rounded-xl bg-gray-100 border-gray-300 text-sm font-black text-blue-700 cursor-not-allowed">
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Planned Date *</label>
                                <input type="date" name="planned_date" value="{{ old('planned_date', $workOrder->planned_date) }}" required
                                    class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800">
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Production Line *</label>
                                <select name="unit_line" required class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800">
                                    <option value="">-- Select Line --</option>
                                    @foreach($lines as $l)
                                        <option value="{{ $l }}" {{ old('unit_line', $workOrder->unit_line) == $l ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Second Process Type *</label>
                                <select name="process_prod" required class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-gray-800">
                                    <option value="">-- Select Process --</option>
                                    @foreach($processes as $p)
                                        <option value="{{ $p }}" {{ old('process_prod', $workOrder->process_prod) == $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Target Production Qty (Pcs) *</label>
                            <input type="number" name="target_qty" id="target_qty" value="{{ old('target_qty', $workOrder->target_qty) }}" min="1" required
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold text-emerald-700">
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex justify-end items-center gap-3 border-t border-gray-100 pt-4">
                        <a href="{{ route('sp-work-orders.show', $workOrder->id) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl border border-gray-300 transition uppercase tracking-wider">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="draft" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-black text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                            Save Draft Updates
                        </button>
                        <button type="submit" name="action" value="release" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-md transition uppercase tracking-wider">
                            Release Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Autocomplete Suggestions Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                                            <span class="text-[10px] text-gray-400 uppercase font-mono">${item.customer_name || ''}</span>
                                        `;
                                    } else if (item.name || item.customer_name) {
                                        div.textContent = item.name || item.customer_name;
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

            // Populate form when item is selected
            function populateFromItem(item) {
                if (item.item_code) document.getElementById('part_number').value = item.item_code;
                if (item.item_name || item.item_description) {
                    document.getElementById('part_name').value = item.item_name || item.item_description;
                }
                
                const rawModel = item.project_code ? String(item.project_code).trim() : '';
                document.getElementById('model').value = (rawModel === '' || rawModel === '0' || rawModel === '-') ? 'N/A' : rawModel;

                if (item.customer_name !== undefined && item.customer_name !== null) {
                    const cust = String(item.customer_name).trim();
                    document.getElementById('customer').value = (cust === '' || cust === '0' || cust === '-') ? 'N/A' : cust;
                }

                const notice = document.getElementById('auto-fill-notice');
                if (notice) notice.classList.remove('hidden');

                const targetInput = document.getElementById('target_qty');
                if (targetInput) targetInput.focus();
            }

            // Bind Autocompletes
            setupAutocomplete('part_number', 'part-number-dropdown', '{{ route('second-process-reports.search-items') }}', populateFromItem);
            setupAutocomplete('part_name', 'part-name-dropdown', '{{ route('second-process-reports.search-items') }}', populateFromItem);
            setupAutocomplete('customer', 'customer-dropdown', '{{ route('second-process-reports.search-customers') }}', function(item) {
                const custName = item.customer_name || item.name || '';
                const cust = String(custName).trim();
                document.getElementById('customer').value = (cust === '' || cust === '0' || cust === '-') ? 'N/A' : cust;
            });
        });
    </script>
</x-app-layout>
