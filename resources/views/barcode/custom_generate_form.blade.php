<x-dashboard-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Custom Barcode Label Generator
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl p-8 border border-slate-100">
                <div class="mb-8 border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Generate Custom Barcode Labels</h1>
                        <p class="text-sm text-slate-500 mt-1">Pilih item (Karawang K- atau KBN), nomor SPK, dan cetak label barcode custom (12 label per lembar A4).</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('barcode.custom.logs') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold rounded-lg border border-indigo-200 transition shadow-sm">
                            <span>📋</span> Lihat History / Log Print
                        </a>
                        <span class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2 rounded-lg border border-slate-200">12 Labels / A4 Sheet</span>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm text-sm" role="alert">
                        <strong class="font-bold">Please correct the following errors:</strong>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('barcode.custom.print') }}" target="_blank" class="space-y-6">
                    @csrf

                    <!-- Grid Layout for Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Barcode Template / Type Selection -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-800 mb-2">Tipe / Format Label Barcode <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition focus:outline-none border-indigo-600 bg-indigo-50/40 text-indigo-900" id="card_type_default">
                                    <input type="radio" name="barcode_type" value="default" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold">Standard Default</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Format standar dengan QR Code 2D (12 label / sheet)</span>
                                    </div>
                                </label>

                                <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition focus:outline-none border-slate-200 hover:border-slate-300 bg-white text-slate-800" id="card_type_sharp">
                                    <input type="radio" name="barcode_type" value="sharp" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold flex items-center gap-1.5">
                                            Customer SHARP
                                            <span class="bg-indigo-100 text-indigo-700 text-[10px] font-extrabold px-2 py-0.5 rounded">SHARP</span>
                                        </span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Kode tahun & bulan di pojok atas, tanggal cetak rata kanan</span>
                                    </div>
                                </label>

                                <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition focus:outline-none border-slate-200 hover:border-slate-300 bg-white text-slate-800" id="card_type_yanfeng">
                                    <input type="radio" name="barcode_type" value="yanfeng" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold flex items-center gap-1.5">
                                            Customer YANFENG
                                            <span class="bg-emerald-100 text-emerald-700 text-[10px] font-extrabold px-2 py-0.5 rounded">YANFENG</span>
                                        </span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Format standar + nomor QAD (Foreign Name) di pojok kanan atas Item Code</span>
                                    </div>
                                </label>

                                <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition focus:outline-none border-slate-200 hover:border-slate-300 bg-white text-slate-800" id="card_type_itsp">
                                    <input type="radio" name="barcode_type" value="itsp" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold flex items-center gap-1.5">
                                            Customer PT. ITSP
                                            <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded">ITSP</span>
                                        </span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Part Tag (8 label/sheet): Model, Part Code, Color, Position (RH/LH), Half Code & SP</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Searchable Item Code Combobox -->
                        <script id="master-items-data" type="application/json">
                            {!! json_encode($items->map(fn($it) => [
                                'code' => $it->item_code,
                                'name' => $it->item_name ?? '',
                                'qad'  => ($it->description_in_foreign_lang && $it->description_in_foreign_lang !== '0') ? $it->description_in_foreign_lang : '',
                                'model'=> ($it->family && $it->family !== '0') ? $it->family : '',
                                'color'=> ($it->color && $it->color !== '0') ? $it->color : '',
                                'position' => ($it->position && $it->position !== '0') ? $it->position : '',
                                'is_kr' => str_starts_with($it->item_code, 'K-')
                            ])) !!}
                        </script>

                        <div id="item-picker-root" class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <label for="item_search_input" class="block text-sm font-semibold text-slate-700">
                                    Item Code <span class="text-red-500">*</span>
                                </label>
                                <!-- Filter Branch Quick Switcher -->
                                <div class="inline-flex rounded-md shadow-2xs border border-slate-200 p-0.5 bg-slate-50 text-[11px] font-bold">
                                    <button type="button" id="btn-branch-all" class="px-2.5 py-0.5 rounded transition font-bold">Semua</button>
                                    <button type="button" id="btn-branch-kr" class="px-2.5 py-0.5 rounded transition font-bold">Karawang (K-)</button>
                                    <button type="button" id="btn-branch-kbn" class="px-2.5 py-0.5 rounded transition font-bold">KBN</button>
                                </div>
                            </div>

                            <!-- Hidden input for standard form submission -->
                            <input type="hidden" id="item_code" name="item_code" value="{{ old('item_code', '') }}" required>

                            <!-- Search Input & Trigger Box -->
                            <div class="relative">
                                <div class="relative flex items-center">
                                    <input type="text"
                                           id="item_search_input"
                                           placeholder="🔍 Ketik Part No / Item Code (cth: K-... atau nama part)..."
                                           autocomplete="off"
                                           class="block w-full pl-4 pr-10 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-semibold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

                                    <!-- Clear / Action button -->
                                    <button type="button"
                                            id="btn-clear-item"
                                            title="Hapus / Cari item lain"
                                            class="hidden absolute right-2.5 p-1 text-slate-400 hover:text-slate-700 rounded-full hover:bg-slate-100 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>

                                <!-- Selected Item Confirmation Ribbon -->
                                <div id="selected-item-badge" class="hidden mt-1.5 flex items-center justify-between text-xs px-2.5 py-1.5 bg-emerald-50 text-emerald-800 rounded-md border border-emerald-200">
                                    <div class="flex items-center gap-1.5 font-bold flex-wrap">
                                        <span>✓ Terpilih:</span>
                                        <span class="font-mono font-black text-indigo-900" id="selected-code-text"></span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-extrabold" id="selected-plant-badge"></span>
                                        <span class="text-slate-600 font-normal truncate max-w-xs" id="selected-name-text"></span>
                                    </div>
                                    <button type="button" id="btn-change-item" class="text-emerald-700 hover:text-emerald-900 font-bold underline cursor-pointer ml-2 whitespace-nowrap">Ganti</button>
                                </div>

                                <!-- Dropdown Results List -->
                                <div id="item-dropdown"
                                     class="hidden absolute z-50 mt-1 w-full bg-white rounded-xl shadow-2xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100"
                                     style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">

                                    <!-- Header inside Dropdown -->
                                    <div class="p-2 bg-slate-50 text-[11px] font-bold text-slate-500 flex justify-between items-center sticky top-0 z-10 border-b border-slate-100">
                                        <span id="dropdown-status-text">Ketik untuk mencari item</span>
                                        <span class="text-[10px] text-slate-400">Gunakan ↑ ↓ dan Enter</span>
                                    </div>

                                    <!-- List of Results -->
                                    <div id="dropdown-items-list" class="divide-y divide-slate-100"></div>

                                    <!-- Empty State inside Dropdown -->
                                    <div id="dropdown-empty-state" class="hidden p-6 text-center text-slate-400 text-sm">
                                        Tidak ada item yang cocok dengan pencarian.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Item Name (Auto Filled) -->
                        <div>
                            <label for="item_name" class="block text-sm font-semibold text-slate-700 mb-2">Item Name</label>
                            <input type="text" id="item_name" readonly placeholder="Select an Item Code first" class="block w-full px-4 py-2 border border-slate-200 bg-slate-50 text-slate-500 rounded-lg shadow-sm focus:outline-none">
                        </div>

                        <!-- QAD / Part Code / Foreign Name -->
                        <div id="qad_container">
                            <label for="qad" class="block text-sm font-semibold text-slate-700 mb-2">
                                QAD / Part Code <span class="text-xs font-normal text-slate-500">(Foreign Name)</span>
                            </label>
                            <input type="text" id="qad" name="qad" placeholder="Otomatis terisi dari data master (Foreign Name)" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Model (Family) -->
                        <div>
                            <label for="model" class="block text-sm font-semibold text-slate-700 mb-2">
                                Model <span class="text-xs font-normal text-slate-500">(Khusus ITSP / Family)</span>
                            </label>
                            <input type="text" id="model" name="model" placeholder="Otomatis terisi dari Family" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Color -->
                        <div>
                            <label for="color" class="block text-sm font-semibold text-slate-700 mb-2">
                                Color <span class="text-xs font-normal text-slate-500">(Khusus ITSP)</span>
                            </label>
                            <input type="text" id="color" name="color" placeholder="Otomatis terisi dari Color" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Position -->
                        <div>
                            <label for="position" class="block text-sm font-semibold text-slate-700 mb-2">
                                Position <span class="text-xs font-normal text-slate-500">(Khusus ITSP: Left/LH -> Half Code 1 besar, Right/RH -> Half Code 2 besar)</span>
                            </label>
                            <input type="text" id="position" name="position" placeholder="Otomatis terisi dari Position (RH / LH)" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- SPK Number Selection -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label for="spk_select" class="block text-sm font-semibold text-slate-700">SPK Number <span class="text-red-500">*</span></label>
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" id="manual_spk_toggle" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    <label for="manual_spk_toggle" class="text-xs text-slate-600 cursor-pointer select-none">Input Manual</label>
                                </div>
                            </div>

                            <!-- Dropdown for SPK (Default) -->
                            <div id="spk_select_container">
                                <select id="spk_select" name="spk_number" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Select Item Code First --</option>
                                </select>
                            </div>

                            <!-- Manual Text Input (Hidden initially) -->
                            <div id="spk_input_container" class="hidden">
                                <input type="text" id="spk_input" placeholder="Type SPK Number manually" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Quantity per Label -->
                        <div>
                            <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">Quantity per Label <span class="text-red-500">*</span></label>
                            <input type="number" id="quantity" name="quantity" min="1" required placeholder="e.g. 80" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Warehouse -->
                        <div>
                            <label for="warehouse" class="block text-sm font-semibold text-slate-700 mb-2">Warehouse <span class="text-red-500">*</span></label>
                            <input type="text" id="warehouse" name="warehouse" value="WFI" required placeholder="e.g. WFI" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Shift -->
                        <div>
                            <label for="shift" class="block text-sm font-semibold text-slate-700 mb-2">Shift <span class="text-red-500">*</span></label>
                            <select id="shift" name="shift" required class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="I">Shift I</option>
                                <option value="II">Shift II</option>
                                <option value="III">Shift III</option>
                            </select>
                        </div>

                        <!-- Label Range Start -->
                        <div>
                            <label for="start_label" class="block text-sm font-semibold text-slate-700 mb-2">Start Label Sequence <span class="text-red-500">*</span></label>
                            <input type="number" id="start_label" name="start_label" min="1" value="1" required class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Label Range End -->
                        <div>
                            <label for="end_label" class="block text-sm font-semibold text-slate-700 mb-2">End Label Sequence <span class="text-red-500">*</span></label>
                            <input type="number" id="end_label" name="end_label" min="1" value="1" required class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Production Date -->
                        <div>
                            <label for="prod_date" class="block text-sm font-semibold text-slate-700 mb-2">Production Date</label>
                            <input type="date" id="prod_date" name="prod_date" value="" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Operator Name -->
                        <div>
                            <label for="operator" class="block text-sm font-semibold text-slate-700 mb-2">Operator Name</label>
                            <input type="text" id="operator" name="operator" placeholder="Leave empty for blank line" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Customer -->
                        <div class="md:col-span-2">
                            <label for="customer" class="block text-sm font-semibold text-slate-700 mb-2">Customer</label>
                            <input type="text" id="customer" name="customer" placeholder="Leave empty for blank line" class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Remark -->
                        <div class="md:col-span-2">
                            <label for="remark" class="block text-sm font-semibold text-slate-700 mb-2">Remark / Catatan <span class="text-xs font-normal text-slate-500">(Akan dicatat di log tracking)</span></label>
                            <textarea id="remark" name="remark" rows="2" placeholder="Contoh: Print label tambahan sample / rework / penggantian sticker rusak..." class="block w-full px-4 py-2 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                    </div>

                    <!-- Options -->
                    <div class="pt-6 border-t flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-6">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_trial" name="is_trial" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ml-3 text-sm font-semibold text-slate-700 select-none">TRIAL Label (Adds '\tTRIAL' to QR data)</span>
                            </label>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_sp" name="is_sp" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                                <span class="ml-3 text-sm font-semibold text-slate-700 select-none">SP Label (Menampilkan badge 'SP' di bawah No SPK khusus ITSP)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 border-t flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-lg shadow-md transition duration-150 flex items-center gap-2">
                            <span>🖨️</span> Generate & Print Labels
                        </button>
                    </div>
                </form>

            </div>

            <!-- Print History & Log Tracking -->
            <div class="mt-10 bg-white overflow-hidden shadow-xl sm:rounded-xl p-8 border border-slate-100">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                            <span>📋</span> History / Log Tracking Print Barcode
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Daftar riwayat cetak label barcode custom yang pernah digenerate per branch.</p>
                    </div>
                    
                    <!-- Branch Tabs for Recent History -->
                    <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-extrabold">
                        <a href="{{ route('barcode.custom.form', ['branch' => 'all']) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ ($branch ?? 'all') === 'all' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Semua ({{ $branchCounts['all'] ?? 0 }})
                        </a>
                        <a href="{{ route('barcode.custom.form', ['branch' => 'karawang']) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ ($branch ?? 'all') === 'karawang' ? 'bg-cyan-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Karawang K- ({{ $branchCounts['karawang'] ?? 0 }})
                        </a>
                        <a href="{{ route('barcode.custom.form', ['branch' => 'kbn']) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ ($branch ?? 'all') === 'kbn' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            KBN ({{ $branchCounts['kbn'] ?? 0 }})
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Waktu Print</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Branch</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">User</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Item & SPK</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Label Seq / Total</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Qty/Box</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Shift / WH</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Type</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600 uppercase text-xs">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($logs ?? [] as $log)
                                @php
                                    $isKarawang = str_starts_with($log->item_code, 'K-');
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <div class="font-medium text-slate-900">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                        <div class="text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($isKarawang)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black bg-cyan-100 text-cyan-800 border border-cyan-200">
                                                <span>🏭</span> Karawang
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black bg-purple-100 text-purple-800 border border-purple-200">
                                                <span>🏢</span> KBN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $log->user_name ?? ($log->user->name ?? 'Guest') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700">
                                        <div class="font-bold text-indigo-700 flex items-center gap-1.5 flex-wrap">
                                            <span>{{ $log->item_code }}</span>
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $log->item_name }}</div>
                                        <div class="text-xs text-slate-600 font-medium mt-0.5">SPK: <span class="font-mono font-bold">{{ $log->spk_number }}</span></div>
                                        @if($log->customer && $log->customer !== '-')
                                            <div class="text-xs text-slate-400">Cust: {{ $log->customer }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                        <div class="font-semibold text-slate-800">
                                            Label #{{ $log->start_label }} - #{{ $log->end_label }}
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 mt-0.5">
                                            {{ $log->total_labels }} label diprint
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 font-semibold">
                                        {{ number_format($log->quantity) }}
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 text-xs">
                                        <div>Shift <span class="font-bold">{{ $log->shift }}</span></div>
                                        <div class="text-slate-400">WH: {{ $log->warehouse }}</div>
                                        @if($log->operator && $log->operator !== '-')
                                            <div class="text-slate-400">Op: {{ $log->operator }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-xs space-y-1">
                                        <div>
                                            @if(($log->barcode_type ?? 'default') === 'sharp')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                    SHARP
                                                </span>
                                            @elseif(($log->barcode_type ?? 'default') === 'yanfeng')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    YANFENG
                                                </span>
                                            @elseif(($log->barcode_type ?? 'default') === 'itsp')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    ITSP
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Standard
                                                </span>
                                            @endif
                                        </div>
                                        @if($log->is_trial)
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                                    TRIAL
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 text-xs max-w-xs break-words">
                                        @if($log->remark)
                                            <span class="text-slate-800 bg-amber-50 border border-amber-200 px-2 py-1 rounded inline-block">
                                                {{ $log->remark }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                        Belum ada data riwayat print barcode.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Load items from JSON script tag
            let allItems = [];
            try {
                const dataEl = document.getElementById('master-items-data');
                if (dataEl) {
                    allItems = JSON.parse(dataEl.textContent || '[]');
                }
            } catch (e) {
                console.error('Failed to parse master items:', e);
            }

            // Elements for Item Picker
            const searchInput = document.getElementById('item_search_input');
            const hiddenItemCode = document.getElementById('item_code');
            const dropdown = document.getElementById('item-dropdown');
            const itemsListContainer = document.getElementById('dropdown-items-list');
            const emptyState = document.getElementById('dropdown-empty-state');
            const statusText = document.getElementById('dropdown-status-text');
            const clearBtn = document.getElementById('btn-clear-item');
            const changeBtn = document.getElementById('btn-change-item');
            const selectedBadge = document.getElementById('selected-item-badge');
            const selectedCodeText = document.getElementById('selected-code-text');
            const selectedPlantBadge = document.getElementById('selected-plant-badge');
            const selectedNameText = document.getElementById('selected-name-text');

            const btnBranchAll = document.getElementById('btn-branch-all');
            const btnBranchKr = document.getElementById('btn-branch-kr');
            const btnBranchKbn = document.getElementById('btn-branch-kbn');

            // Form inputs
            const itemNameInput = document.getElementById('item_name');
            const qadInput = document.getElementById('qad');
            const modelInput = document.getElementById('model');
            const colorInput = document.getElementById('color');
            const positionInput = document.getElementById('position');
            const spkSelect = document.getElementById('spk_select');
            const manualSpkToggle = document.getElementById('manual_spk_toggle');
            const spkSelectContainer = document.getElementById('spk_select_container');
            const spkInputContainer = document.getElementById('spk_input_container');
            const spkInput = document.getElementById('spk_input');
            const customerInput = document.getElementById('customer');
            const warehouseInput = document.getElementById('warehouse');
            const barcodeTypeRadios = document.querySelectorAll('input[name="barcode_type"]');
            const cardDefault = document.getElementById('card_type_default');
            const cardSharp = document.getElementById('card_type_sharp');
            const cardYanfeng = document.getElementById('card_type_yanfeng');
            const cardItsp = document.getElementById('card_type_itsp');

            // Current State
            const urlParams = new URLSearchParams(window.location.search);
            let currentBranch = urlParams.get('branch') || 'all';
            if (currentBranch !== 'karawang' && currentBranch !== 'kbn') {
                currentBranch = 'all';
            }

            let currentResults = [];
            let highlightedIndex = 0;
            let selectedItem = null;

            function updateBranchButtonsUI() {
                [btnBranchAll, btnBranchKr, btnBranchKbn].forEach(btn => {
                    if (btn) {
                        btn.className = 'px-2.5 py-0.5 rounded transition font-bold text-slate-600 hover:text-slate-900';
                    }
                });
                if (currentBranch === 'karawang' && btnBranchKr) {
                    btnBranchKr.className = 'px-2.5 py-0.5 rounded transition font-bold bg-cyan-600 text-white shadow-xs';
                } else if (currentBranch === 'kbn' && btnBranchKbn) {
                    btnBranchKbn.className = 'px-2.5 py-0.5 rounded transition font-bold bg-purple-600 text-white shadow-xs';
                } else if (btnBranchAll) {
                    btnBranchAll.className = 'px-2.5 py-0.5 rounded transition font-bold bg-indigo-600 text-white shadow-xs';
                }
            }

            function getFilteredItems() {
                let list = allItems;
                if (currentBranch === 'karawang') {
                    list = list.filter(i => i.is_kr);
                } else if (currentBranch === 'kbn') {
                    list = list.filter(i => !i.is_kr);
                }

                const q = (searchInput.value || '').trim().toLowerCase();
                if (!q) {
                    return list.slice(0, 50);
                }

                return list.filter(i =>
                    (i.code && i.code.toLowerCase().includes(q)) ||
                    (i.name && i.name.toLowerCase().includes(q)) ||
                    (i.qad && i.qad.toLowerCase().includes(q))
                ).slice(0, 80);
            }

            function renderDropdown() {
                currentResults = getFilteredItems();
                itemsListContainer.innerHTML = '';
                highlightedIndex = 0;

                const q = (searchInput.value || '').trim();
                statusText.textContent = `${currentResults.length} item ditemukan${q ? ` untuk '${q}'` : ''}`;

                if (currentResults.length === 0) {
                    emptyState.classList.remove('hidden');
                    emptyState.innerHTML = `Tidak ada item yang cocok dengan pencarian "<strong>${escapeHtml(q)}</strong>".`;
                } else {
                    emptyState.classList.add('hidden');
                    currentResults.forEach((item, idx) => {
                        const row = document.createElement('div');
                        row.id = 'opt-item-' + idx;
                        row.className = `p-2.5 cursor-pointer transition flex items-start justify-between gap-3 text-sm hover:bg-indigo-50/80 ${idx === 0 ? 'bg-indigo-50/50' : 'bg-white'}`;

                        const plantBadge = item.is_kr
                            ? '<span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-cyan-100 text-cyan-800 border border-cyan-200">KR (K-)</span>'
                            : '<span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-purple-100 text-purple-800 border border-purple-200">KBN</span>';

                        const qadHtml = item.qad ? `<div class="text-[11px] text-slate-400 mt-0.5">QAD: <span class="font-semibold text-slate-600">${escapeHtml(item.qad)}</span></div>` : '';
                        const isSelected = selectedItem && selectedItem.code === item.code;

                        row.innerHTML = `
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold font-mono text-indigo-700">${escapeHtml(item.code)}</span>
                                    ${plantBadge}
                                </div>
                                <div class="text-xs text-slate-600 truncate mt-0.5">${escapeHtml(item.name || '-')}</div>
                                ${qadHtml}
                            </div>
                            ${isSelected ? '<span class="text-emerald-600 font-bold text-xs mt-1">✓ Aktif</span>' : ''}
                        `;

                        row.addEventListener('click', () => {
                            selectItem(item);
                        });

                        row.addEventListener('mouseenter', () => {
                            highlightIndex(idx);
                        });

                        itemsListContainer.appendChild(row);
                    });
                }

                dropdown.classList.remove('hidden');
                clearBtn.classList.toggle('hidden', !searchInput.value && !selectedItem);
            }

            function highlightIndex(idx) {
                if (currentResults.length === 0) return;
                const prevRow = document.getElementById('opt-item-' + highlightedIndex);
                if (prevRow) {
                    prevRow.classList.remove('bg-indigo-50/80', 'bg-indigo-50/50');
                    prevRow.classList.add('bg-white');
                }
                highlightedIndex = idx;
                const nextRow = document.getElementById('opt-item-' + highlightedIndex);
                if (nextRow) {
                    nextRow.classList.add('bg-indigo-50/80');
                    nextRow.classList.remove('bg-white');
                    nextRow.scrollIntoView({ block: 'nearest' });
                }
            }

            function selectItem(item) {
                selectedItem = item;
                hiddenItemCode.value = item.code;
                hiddenItemCode.setAttribute('data-name', item.name || '');
                hiddenItemCode.setAttribute('data-qad', item.qad || '');
                hiddenItemCode.setAttribute('data-model', item.model || '');
                hiddenItemCode.setAttribute('data-color', item.color || '');
                hiddenItemCode.setAttribute('data-position', item.position || '');
                hiddenItemCode.dispatchEvent(new Event('change', { bubbles: true }));

                searchInput.value = item.code;
                searchInput.classList.add('border-indigo-500', 'bg-indigo-50/20', 'text-indigo-950', 'font-bold');
                searchInput.classList.remove('border-slate-300', 'bg-white', 'text-slate-800');

                // Show badge
                selectedCodeText.textContent = item.code;
                selectedNameText.textContent = item.name ? `— ${item.name}` : '';
                if (item.is_kr) {
                    selectedPlantBadge.textContent = 'Karawang (K-)';
                    selectedPlantBadge.className = 'text-[10px] px-1.5 py-0.5 rounded font-extrabold bg-cyan-100 text-cyan-800';
                } else {
                    selectedPlantBadge.textContent = 'KBN';
                    selectedPlantBadge.className = 'text-[10px] px-1.5 py-0.5 rounded font-extrabold bg-purple-100 text-purple-800';
                }
                selectedBadge.classList.remove('hidden');

                // Auto switch warehouse default
                if (warehouseInput && (!warehouseInput.value || warehouseInput.value === 'WFI' || warehouseInput.value === 'KRFFI' || warehouseInput.value === 'FFI')) {
                    warehouseInput.value = item.is_kr ? 'KRFFI' : 'WFI';
                }

                dropdown.classList.add('hidden');
                clearBtn.classList.remove('hidden');
            }

            function clearSelection() {
                selectedItem = null;
                hiddenItemCode.value = '';
                hiddenItemCode.removeAttribute('data-name');
                hiddenItemCode.removeAttribute('data-qad');
                hiddenItemCode.removeAttribute('data-model');
                hiddenItemCode.removeAttribute('data-color');
                hiddenItemCode.removeAttribute('data-position');
                hiddenItemCode.dispatchEvent(new Event('change', { bubbles: true }));

                searchInput.value = '';
                searchInput.classList.remove('border-indigo-500', 'bg-indigo-50/20', 'text-indigo-950', 'font-bold');
                searchInput.classList.add('border-slate-300', 'bg-white', 'text-slate-800');

                selectedBadge.classList.add('hidden');
                clearBtn.classList.add('hidden');

                searchInput.focus();
                renderDropdown();
            }

            function escapeHtml(text) {
                if (!text) return '';
                const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            // Event Listeners for Search Input
            searchInput.addEventListener('focus', () => {
                renderDropdown();
            });

            searchInput.addEventListener('input', () => {
                if (selectedItem && searchInput.value !== selectedItem.code) {
                    selectedItem = null;
                    selectedBadge.classList.add('hidden');
                    hiddenItemCode.value = '';
                }
                renderDropdown();
            });

            searchInput.addEventListener('keydown', (e) => {
                if (dropdown.classList.contains('hidden')) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        renderDropdown();
                        e.preventDefault();
                    }
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentResults.length > 0) {
                        highlightIndex((highlightedIndex + 1) % currentResults.length);
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentResults.length > 0) {
                        highlightIndex((highlightedIndex - 1 + currentResults.length) % currentResults.length);
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentResults.length > 0 && currentResults[highlightedIndex]) {
                        selectItem(currentResults[highlightedIndex]);
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.add('hidden');
                }
            });

            // Branch Switchers
            if (btnBranchAll) {
                btnBranchAll.addEventListener('click', () => {
                    currentBranch = 'all';
                    updateBranchButtonsUI();
                    renderDropdown();
                    searchInput.focus();
                });
            }
            if (btnBranchKr) {
                btnBranchKr.addEventListener('click', () => {
                    currentBranch = 'karawang';
                    updateBranchButtonsUI();
                    renderDropdown();
                    searchInput.focus();
                });
            }
            if (btnBranchKbn) {
                btnBranchKbn.addEventListener('click', () => {
                    currentBranch = 'kbn';
                    updateBranchButtonsUI();
                    renderDropdown();
                    searchInput.focus();
                });
            }

            if (clearBtn) clearBtn.addEventListener('click', clearSelection);
            if (changeBtn) changeBtn.addEventListener('click', clearSelection);

            // Click outside closes dropdown
            document.addEventListener('click', (e) => {
                const root = document.getElementById('item-picker-root');
                if (root && !root.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            // Handle Barcode Type Switch
            barcodeTypeRadios.forEach(radio => {
                radio.addEventListener('change', function () {
                    // Reset all cards
                    [cardDefault, cardSharp, cardYanfeng, cardItsp].forEach(card => {
                        if (card) {
                            card.classList.remove(
                                'border-indigo-600', 'bg-indigo-50/40', 'text-indigo-900',
                                'border-emerald-600', 'bg-emerald-50/40', 'text-emerald-900',
                                'border-amber-600', 'bg-amber-50/40', 'text-amber-900'
                            );
                            card.classList.add('border-slate-200', 'bg-white', 'text-slate-800');
                        }
                    });

                    if (this.value === 'sharp') {
                        cardSharp.classList.add('border-indigo-600', 'bg-indigo-50/40', 'text-indigo-900');
                        cardSharp.classList.remove('border-slate-200', 'bg-white', 'text-slate-800');

                        customerInput.value = 'SHARP';
                        if (warehouseInput.value === 'WFI') {
                            warehouseInput.value = 'FFI';
                        }
                    } else if (this.value === 'yanfeng') {
                        cardYanfeng.classList.add('border-emerald-600', 'bg-emerald-50/40', 'text-emerald-900');
                        cardYanfeng.classList.remove('border-slate-200', 'bg-white', 'text-slate-800');

                        customerInput.value = 'YANFENG';
                        if (warehouseInput.value === 'WFI') {
                            warehouseInput.value = 'FFI';
                        }
                    } else if (this.value === 'itsp') {
                        cardItsp.classList.add('border-amber-600', 'bg-amber-50/40', 'text-amber-900');
                        cardItsp.classList.remove('border-slate-200', 'bg-white', 'text-slate-800');

                        customerInput.value = 'PT. ITSP';
                        if (warehouseInput.value === 'WFI') {
                            warehouseInput.value = 'FFI';
                        }
                    } else {
                        cardDefault.classList.add('border-indigo-600', 'bg-indigo-50/40', 'text-indigo-900');
                        cardDefault.classList.remove('border-slate-200', 'bg-white', 'text-slate-800');

                        if (customerInput.value === 'SHARP' || customerInput.value === 'YANFENG' || customerInput.value === 'PT. ITSP') {
                            customerInput.value = '';
                        }
                        if (warehouseInput.value === 'FFI') {
                            warehouseInput.value = 'WFI';
                        }
                    }
                });
            });

            // Handle Item Code Change (Auto fill Name, QAD, Model, Color, Position & fetch SPKs via AJAX)
            if (hiddenItemCode) {
                hiddenItemCode.addEventListener('change', function () {
                    const itemName = this.getAttribute('data-name');
                    const itemQad = this.getAttribute('data-qad');
                    const itemModel = this.getAttribute('data-model');
                    const itemColor = this.getAttribute('data-color');
                    const itemPosition = this.getAttribute('data-position');
                    const itemCode = this.value;

                    if (itemNameInput) itemNameInput.value = itemName || '';
                    if (qadInput) qadInput.value = itemQad || '';
                    if (modelInput) modelInput.value = itemModel || '';
                    if (colorInput) colorInput.value = itemColor || '';
                    if (positionInput) positionInput.value = itemPosition || '';

                    if (!itemCode) {
                        spkSelect.innerHTML = '<option value="">-- Select Item Code First --</option>';
                        return;
                    }

                    spkSelect.innerHTML = '<option value="">Loading SPKs...</option>';

                    fetch(`/api/get-spks-by-item?item_code=${encodeURIComponent(itemCode)}`)
                        .then(response => response.json())
                        .then(spks => {
                            spkSelect.innerHTML = '';
                            if (spks.length === 0) {
                                spkSelect.innerHTML = '<option value="">No SPK found in history</option>';
                                manualSpkToggle.checked = true;
                                triggerManualToggle(true);
                            } else {
                                spkSelect.innerHTML = '<option value="">-- Select SPK Number --</option>';
                                spks.forEach(spk => {
                                    const option = document.createElement('option');
                                    option.value = spk;
                                    option.textContent = spk;
                                    spkSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching SPKs:', err);
                            spkSelect.innerHTML = '<option value="">Error loading SPKs</option>';
                        });
                });
            }

            // Handle Manual SPK Toggle
            if (manualSpkToggle) {
                manualSpkToggle.addEventListener('change', function () {
                    triggerManualToggle(this.checked);
                });
            }

            function triggerManualToggle(isManual) {
                if (isManual) {
                    spkSelectContainer.classList.add('hidden');
                    spkSelect.removeAttribute('name');
                    spkSelect.removeAttribute('required');

                    spkInputContainer.classList.remove('hidden');
                    spkInput.setAttribute('name', 'spk_number');
                    spkInput.setAttribute('required', 'required');
                } else {
                    spkInputContainer.classList.add('hidden');
                    spkInput.removeAttribute('name');
                    spkInput.removeAttribute('required');

                    spkSelectContainer.classList.remove('hidden');
                    spkSelect.setAttribute('name', 'spk_number');
                    spkSelect.setAttribute('required', 'required');
                }
            }

            // Init UI
            updateBranchButtonsUI();

            // Check if initial value exists
            const initCode = hiddenItemCode.value;
            if (initCode) {
                const found = allItems.find(i => i.code === initCode);
                if (found) {
                    selectItem(found);
                }
            }
        });
    </script>
</x-dashboard-layout>
