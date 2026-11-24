<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-xl text-zinc-800 uppercase tracking-wide">
                    {{ __('Create Second Process Work Order') }}
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">Dispatch new production order to shop floor line</p>
            </div>
            <a href="{{ route('sp-work-orders.index') }}" class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-semibold text-xs rounded-xl border border-zinc-300 shadow-xs transition uppercase tracking-wider">
                Back to Work Orders
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-2xl mx-auto space-y-6">

            {{-- Validation Errors Banner --}}
            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs space-y-1">
                    <p class="font-bold uppercase tracking-wider">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-0.5 font-normal">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white p-6 rounded-2xl border border-zinc-200 shadow-xs space-y-6"
                x-data="{
                    searchQuery: '',
                    searchResults: [],
                    showDropdown: false,
                    isLoading: false,
                    isSelected: {{ old('part_number') ? 'true' : 'false' }},
                    isManual: false,
                    part_number: '{{ addslashes(old('part_number', '')) }}',
                    part_name: '{{ addslashes(old('part_name', '')) }}',
                    model: '{{ addslashes(old('model', '')) }}',
                    customer: '{{ addslashes(old('customer', '')) }}',

                    performSearch() {
                        const q = this.searchQuery.trim();
                        if (q.length < 1) {
                            this.searchResults = [];
                            this.showDropdown = false;
                            return;
                        }
                        this.isLoading = true;
                        fetch(`{{ route('second-process-reports.search-items') }}?query=${encodeURIComponent(q)}`)
                            .then(res => res.json())
                            .then(data => {
                                this.searchResults = Array.isArray(data) ? data : [];
                                this.showDropdown = this.searchResults.length > 0;
                                this.isLoading = false;
                            })
                            .catch(err => {
                                console.error('Search error:', err);
                                this.searchResults = [];
                                this.isLoading = false;
                            });
                    },

                    selectItem(item) {
                        this.part_number = item.item_code || '';
                        this.part_name = item.item_name || item.item_description || '';
                        const rawModel = item.project_code ? String(item.project_code).trim() : '';
                        this.model = (rawModel === '' || rawModel === '0' || rawModel === '-') ? 'N/A' : rawModel;
                        const rawCust = item.customer_name ? String(item.customer_name).trim() : '';
                        this.customer = (rawCust === '' || rawCust === '0' || rawCust === '-') ? 'N/A' : rawCust;
                        this.isSelected = true;
                        this.isManual = false;
                        this.showDropdown = false;
                        this.searchQuery = '';

                        this.$nextTick(() => {
                            const nextField = document.getElementById('planned_date') || document.getElementById('unit_line');
                            if (nextField) nextField.focus();
                        });
                    },

                    clearSelection() {
                        this.part_number = '';
                        this.part_name = '';
                        this.model = '';
                        this.customer = '';
                        this.isSelected = false;
                        this.isManual = false;
                        this.searchQuery = '';
                        this.searchResults = [];
                        this.showDropdown = false;
                        this.$nextTick(() => {
                            const searchEl = document.getElementById('catalog-search-input');
                            if (searchEl) searchEl.focus();
                        });
                    },

                    switchToManual() {
                        this.isManual = true;
                        this.isSelected = false;
                        this.showDropdown = false;
                        this.$nextTick(() => {
                            const manualPartEl = document.getElementById('manual_part_number');
                            if (manualPartEl) manualPartEl.focus();
                        });
                    },

                    switchToSearch() {
                        this.isManual = false;
                        this.isSelected = false;
                        this.$nextTick(() => {
                            const searchEl = document.getElementById('catalog-search-input');
                            if (searchEl) searchEl.focus();
                        });
                    }
                }">

                <div class="border-b border-zinc-100 pb-3">
                    <h3 class="text-sm font-bold text-zinc-800 uppercase tracking-wider">Work Order Parameters</h3>
                    <p class="text-xs text-zinc-500">Define product specs and shop floor setup</p>
                </div>

                <form action="{{ route('sp-work-orders.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- SECTION 1: Product Specifications (Catalog Lookup) --}}
                    <div class="bg-zinc-50/80 p-5 rounded-2xl border border-zinc-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">
                                1. Product Specifications (Catalog Lookup)
                            </span>
                            <span x-show="isSelected && !isManual" class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Selected
                            </span>
                        </div>

                        {{-- STATE A: Collapsed Summary Card --}}
                        <div x-show="isSelected && !isManual" x-cloak class="bg-white p-4 rounded-xl border border-zinc-300 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono font-semibold bg-zinc-900 text-white px-2.5 py-1 rounded-md" x-text="part_number"></span>
                                    <span class="text-sm font-semibold text-zinc-900" x-text="part_name"></span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-zinc-600 flex-wrap">
                                    <span>Model: <span class="font-semibold text-zinc-800" x-text="model || 'N/A'"></span></span>
                                    <span class="text-zinc-300">|</span>
                                    <span>Customer: <span class="font-semibold text-zinc-800" x-text="customer || 'N/A'"></span></span>
                                </div>
                            </div>
                            <button type="button" @click="clearSelection()"
                                class="px-3.5 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-semibold text-xs rounded-lg border border-zinc-300 transition uppercase tracking-wider shrink-0">
                                Change Part
                            </button>
                        </div>

                        {{-- STATE B: Prominent Search Input --}}
                        <div x-show="!isSelected && !isManual" class="space-y-2">
                            <label for="catalog-search-input" class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider">
                                Search Catalog Item *
                            </label>
                            <div class="relative" @click.outside="showDropdown = false">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text"
                                        id="catalog-search-input"
                                        x-model="searchQuery"
                                        @input.debounce.250ms="performSearch()"
                                        @keydown.escape="showDropdown = false"
                                        placeholder="Search by Part Number or Part Name..."
                                        autocomplete="off"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white shadow-xs">
                                </div>

                                {{-- Autocomplete Dropdown --}}
                                <div x-show="showDropdown" x-cloak
                                    class="absolute left-0 right-0 mt-1.5 max-h-64 overflow-y-auto bg-white border border-zinc-200 rounded-xl shadow-lg z-50 divide-y divide-zinc-100">
                                    <template x-for="item in searchResults" :key="item.item_code">
                                        <div @click="selectItem(item)"
                                            class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-xs text-zinc-800 transition flex justify-between items-center gap-2">
                                            <div class="min-w-0">
                                                <span class="font-mono font-semibold text-blue-700" x-text="item.item_code"></span>
                                                <span class="text-zinc-600 ml-1.5 truncate" x-text="item.item_name || item.item_description || ''"></span>
                                            </div>
                                            <span class="text-[10px] text-zinc-400 uppercase font-mono shrink-0" x-text="item.customer_name || ''"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-1 text-xs">
                                <span class="text-zinc-500">Select an item from the catalog to auto-fill specifications.</span>
                                <button type="button" @click="switchToManual()" class="text-blue-600 hover:text-blue-800 font-semibold hover:underline">
                                    Enter details manually
                                </button>
                            </div>
                        </div>

                        {{-- STATE C: Manual Entry Inputs --}}
                        <div x-show="isManual" x-cloak class="space-y-4 pt-1">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-semibold text-zinc-700">Manual Entry Mode</span>
                                <button type="button" @click="switchToSearch()" class="text-xs text-blue-600 hover:text-blue-800 font-semibold hover:underline">
                                    Back to Catalog Search
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">Part Number *</label>
                                    <input type="text" id="manual_part_number" x-model="part_number" placeholder="Enter Part Number..."
                                        class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">Part Name *</label>
                                    <input type="text" x-model="part_name" placeholder="Enter Part Name..."
                                        class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">Model Code</label>
                                    <input type="text" x-model="model" placeholder="e.g. D01D or N/A"
                                        class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">Customer Name</label>
                                    <input type="text" x-model="customer" placeholder="Enter Customer or N/A..."
                                        class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Form Inputs synced with Alpine state --}}
                        <input type="hidden" name="part_number" :value="part_number">
                        <input type="hidden" name="part_name" :value="part_name">
                        <input type="hidden" name="model" :value="model">
                        <input type="hidden" name="customer" :value="customer">
                    </div>

                    {{-- SECTION 2: Production Setup & Schedule (Single-Column Top-to-Down) --}}
                    <div class="bg-white p-5 rounded-2xl border border-zinc-200 space-y-4">
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                            <span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">
                                2. Production Setup & Schedule
                            </span>
                            <div class="flex items-center gap-1.5 text-xs">
                                <span class="font-medium text-zinc-500">WO Number:</span>
                                <span class="font-mono font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md text-[11px]">
                                    {{ $woNumber }}
                                </span>
                            </div>
                        </div>

                        <input type="hidden" name="wo_number" value="{{ old('wo_number', $woNumber) }}">

                        {{-- 2.1 Planned Date --}}
                        <div>
                            <label for="planned_date" class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">
                                Planned Date *
                            </label>
                            <input type="date" name="planned_date" id="planned_date"
                                value="{{ old('planned_date', date('Y-m-d')) }}" required
                                class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                        </div>

                        {{-- 2.2 Production Line --}}
                        <div>
                            <label for="unit_line" class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">
                                Production Line *
                            </label>
                            <select name="unit_line" id="unit_line" required
                                class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                <option value="">-- Select Line --</option>
                                @foreach($lines as $l)
                                    <option value="{{ $l }}" {{ old('unit_line', request('unit_line')) == $l ? 'selected' : '' }}>
                                        {{ $l }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2.3 Second Process Type --}}
                        <div>
                            <label for="process_prod" class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">
                                Second Process Type *
                            </label>
                            <select name="process_prod" id="process_prod" required
                                class="w-full rounded-xl border-zinc-300 focus:border-blue-600 focus:ring-blue-600 text-sm font-normal text-zinc-800 bg-white">
                                <option value="">-- Select Process --</option>
                                @foreach($processes as $p)
                                    <option value="{{ $p }}" {{ old('process_prod') == $p ? 'selected' : '' }}>
                                        {{ $p }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2.4 Target Production Qty --}}
                        <div>
                            <label for="target_qty" class="block text-[11px] font-semibold text-zinc-600 uppercase tracking-wider mb-1">
                                Target Production Qty (Pcs) *
                            </label>
                            <input type="number" name="target_qty" id="target_qty"
                                value="{{ old('target_qty') }}" min="1" placeholder="Enter target quantity in pcs..." required
                                class="w-full rounded-xl border-zinc-300 focus:border-emerald-600 focus:ring-emerald-600 text-base font-semibold text-emerald-700 bg-white">
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex justify-end items-center gap-3 border-t border-zinc-100 pt-4">
                        <a href="{{ route('sp-work-orders.index') }}" class="px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-semibold text-xs rounded-xl border border-zinc-300 transition uppercase tracking-wider">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="release" onclick="return confirm('Release Work Order ini ke lantai produksi? Order yang dirilis akan masuk ke antrean produksi.')" class="px-4 py-2.5 bg-white hover:bg-zinc-100 text-zinc-700 font-semibold text-xs rounded-xl border border-zinc-300 transition uppercase tracking-wider">
                            Release Order
                        </button>
                        <button type="submit" name="action" value="draft" class="px-6 py-2.5 bg-zinc-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-sm transition uppercase tracking-wider">
                            Save as Draft
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
