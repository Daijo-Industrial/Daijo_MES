<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-white">Input Delivery Schedule</h2>
                    <p class="text-blue-100 text-sm mt-1">Kelola jadwal pengiriman customer dengan mudah</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" 
                            wire:click="openUploadModal"
                            class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 shadow-md cursor-pointer text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Upload Excel
                    </button>
                    <a href="{{ route('delivery-schedule.calendar') }}" 
                       class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition flex items-center gap-2 shadow-md text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        View Calendar
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mx-6 mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mx-6 mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-red-800 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="submit" class="p-6 space-y-6">
            <!-- Customer Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Customer <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live="customerSearch"
                        wire:focus="$set('showCustomerDropdown', true)"
                        placeholder="Ketik customer code atau nama..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    
                    @if ($showCustomerDropdown && $this->customers->count() > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @foreach ($this->customers as $customer)
                                <div 
                                    wire:click="selectCustomer({{ $customer->id }})"
                                    class="px-4 py-3 hover:bg-blue-50 cursor-pointer transition border-b border-gray-100 last:border-0"
                                >
                                    <div class="font-medium text-gray-900">{{ $customer->customer_code }}</div>
                                    <div class="text-sm text-gray-600">{{ $customer->customer_name }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('selectedCustomer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Month and Year Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Bulan <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model.live="selectedMonth"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                    @error('selectedMonth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Tahun <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        wire:model.live="selectedYear"
                        min="2020"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    @error('selectedYear') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Item Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Item Code <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live="itemSearch"
                        wire:focus="$set('showItemDropdown', true)"
                        placeholder="Ketik item code atau nama..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    
                    @if ($showItemDropdown && $this->items->count() > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @foreach ($this->items as $item)
                                <div 
                                    wire:click="selectItem({{ $item->id }})"
                                    class="px-4 py-3 hover:bg-blue-50 cursor-pointer transition border-b border-gray-100 last:border-0"
                                >
                                    <div class="font-medium text-gray-900">{{ $item->item_code }}</div>
                                    <div class="text-sm text-gray-600">{{ $item->item_name }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('selectedItem') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- SO Number (Optional) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    SO Number <span class="text-gray-400 text-xs">(Optional)</span>
                </label>
                <input 
                    type="text" 
                    wire:model="soNum"
                    placeholder="Masukkan nomor SO jika ada..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            <!-- Schedule Table -->
            @if (!empty($schedules))
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Jadwal Pengiriman</h3>
                        <span class="text-sm text-gray-600">
                            {{ count($schedules) }} hari
                        </span>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($schedules as $day => $schedule)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ $day }}
                                                </span>
                                                <span class="text-sm text-gray-500 ml-2">
                                                    {{ \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, $day)->format('D') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <input 
                                                    type="number" 
                                                    wire:model="schedules.{{ $day }}.quantity"
                                                    min="0"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    placeholder="0"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button 
                    type="submit"
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="submit">
                        <svg class="w-5 h-5 inline mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Submit Delivery Schedule
                    </span>
                    <span wire:loading wire:target="submit" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- MODAL UPLOAD EXCEL DELIVERY SCHEDULE -->
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[92vh]">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-100 flex items-start justify-between bg-gradient-to-r from-emerald-50 via-teal-50/40 to-sky-50/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📊</span>
                            <h3 class="font-bold text-lg text-gray-900">Upload Delivery Schedule dari Excel / CSV</h3>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Mendukung format baris standar (Customer, Item, Tanggal, Qty, SO) maupun format matriks bulanan (kolom tanggal 1..31).
                        </p>
                    </div>
                    <button type="button" wire:click="closeUploadModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer p-1">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    
                    <!-- Template Download Strip -->
                    <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold text-sky-900 flex items-center gap-1.5">
                                <span>📥</span>
                                <span>Download Format Template Excel Resmi</span>
                            </div>
                            <div class="text-[11px] text-sky-700 mt-0.5">
                                Gunakan template ini untuk kemudahan pengisian data jadwal delivery secara massal.
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    wire:click="downloadTemplate" 
                                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1 cursor-pointer">
                                <span>Excel (.xlsx)</span>
                            </button>
                            <button type="button" 
                                    wire:click="downloadTemplateCsv" 
                                    class="px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                <span>CSV</span>
                            </button>
                        </div>
                    </div>

                    <!-- Period & Import Mode Settings -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Target Bulan <span class="text-gray-400 font-normal">(untuk sheet matriks)</span>
                            </label>
                            <select wire:model="uploadMonth" class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Target Tahun
                            </label>
                            <input type="number" wire:model="uploadYear" min="2020" class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Mode Penyimpanan
                            </label>
                            <select wire:model="importMode" class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500">
                                <option value="append">Tambah / Update (Pertahankan jadwal lain)</option>
                                <option value="replace">Timpa Jadwal Bulan Ini (Untuk part di file)</option>
                            </select>
                        </div>
                    </div>

                    <!-- File Dropzone -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            Pilih File Excel / CSV (.xlsx, .xls, .csv) <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 hover:border-emerald-500 rounded-xl p-5 text-center transition bg-white cursor-pointer relative">
                            <input type="file" 
                                   wire:model="uploadFile" 
                                   accept=".xlsx,.xls,.csv" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            
                            <div wire:loading.remove wire:target="uploadFile">
                                <svg class="w-10 h-10 mx-auto text-emerald-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-sm font-semibold text-gray-700">
                                    Klik atau seret file Excel ke sini
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Maksimal ukuran file: 20 MB (.xlsx, .xls, .csv)
                                </p>
                                @if ($uploadFile)
                                    <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <span>📄</span>
                                        <span>{{ $uploadFile->getClientOriginalName() }}</span>
                                    </div>
                                @endif
                            </div>

                            <div wire:loading wire:target="uploadFile" class="py-4">
                                <svg class="animate-spin h-8 w-8 mx-auto text-emerald-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-xs font-bold text-gray-600">Sedang membaca dan menganalisis file Excel...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Alerts -->
                    @if (!empty($uploadErrors))
                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <span>⚠️</span>
                                <span>Perhatian / Catatan Baris:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
                                @foreach ($uploadErrors as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Preview Statistics & Table -->
                    @if ($previewSummary['total_rows'] > 0)
                        <div class="space-y-4">
                            <!-- Stats Badges -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                                    <div class="text-[10px] uppercase font-bold text-emerald-700">Total Baris Jadwal</div>
                                    <div class="text-lg font-black text-emerald-900 font-mono">{{ number_format($previewSummary['total_rows']) }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-sky-50 border border-sky-200 text-center">
                                    <div class="text-[10px] uppercase font-bold text-sky-700">Total Quantity</div>
                                    <div class="text-lg font-black text-sky-900 font-mono">{{ number_format($previewSummary['total_qty']) }} <span class="text-xs font-normal">pcs</span></div>
                                </div>
                                <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-200 text-center">
                                    <div class="text-[10px] uppercase font-bold text-indigo-700">Part / Item Unik</div>
                                    <div class="text-lg font-black text-indigo-900 font-mono">{{ $previewSummary['unique_items'] }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-center">
                                    <div class="text-[10px] uppercase font-bold text-amber-700">Customer Unik</div>
                                    <div class="text-lg font-black text-amber-900 font-mono">{{ $previewSummary['unique_customers'] }}</div>
                                </div>
                            </div>

                            <!-- Preview Table -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wide">
                                        Pratinjau Data (Menampilkan {{ count($previewRows) }} dari {{ number_format($previewSummary['total_rows']) }} Jadwal):
                                    </h4>
                                    @if ($previewSummary['skipped_rows'] > 0)
                                        <span class="text-[11px] text-gray-400">
                                            ({{ $previewSummary['skipped_rows'] }} baris kosong/qty 0 dilewati)
                                        </span>
                                    @endif
                                </div>

                                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                                    <table class="w-full text-xs text-left">
                                        <thead>
                                            <tr class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                                                <th class="py-2 px-3 text-center w-10">No</th>
                                                <th class="py-2 px-3">Customer</th>
                                                <th class="py-2 px-3">Item Code</th>
                                                <th class="py-2 px-3">Tanggal Delivery</th>
                                                <th class="py-2 px-3 text-right">Qty Delivery</th>
                                                <th class="py-2 px-3">No SO</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($previewRows as $pIdx => $pRow)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="py-2 px-3 text-center font-mono text-gray-400">{{ $pIdx + 1 }}</td>
                                                    <td class="py-2 px-3">
                                                        <div class="font-bold text-gray-800">{{ $pRow['customer_code'] }}</div>
                                                        <div class="text-[10px] text-gray-400">{{ $pRow['customer_name'] }}</div>
                                                    </td>
                                                    <td class="py-2 px-3">
                                                        <div class="font-mono font-bold text-gray-900">{{ $pRow['item_code'] }}</div>
                                                        <div class="text-[10px] text-gray-500">{{ $pRow['item_name'] }}</div>
                                                    </td>
                                                    <td class="py-2 px-3 font-mono font-bold text-sky-700">
                                                        {{ $pRow['tanggal'] }}
                                                    </td>
                                                    <td class="py-2 px-3 text-right font-mono font-black text-emerald-700">
                                                        {{ number_format($pRow['quantity']) }} pcs
                                                    </td>
                                                    <td class="py-2 px-3 font-mono text-gray-500">
                                                        {{ $pRow['so_num'] ?: '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" 
                            wire:click="closeUploadModal" 
                            class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                        Batal
                    </button>

                    <button type="button" 
                            wire:click="processImport"
                            @if ($previewSummary['total_rows'] === 0 || $isProcessingUpload) disabled @endif
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="processImport">
                            🚀 Simpan {{ number_format($previewSummary['total_rows']) }} Jadwal ke Database
                        </span>
                        <span wire:loading wire:target="processImport" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menyimpan Data...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
