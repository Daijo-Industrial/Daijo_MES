<div class="space-y-6">
    <!-- Notifications & Warnings -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline font-semibold">{{ session('message') }}</span>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Data Authority Warning -->
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-amber-800">Master Data Authority Warning</h3>
                <p class="text-xs text-amber-700 mt-1">
                    Manual local adjustments to item parameters (cavities, cycle times, setup times, customer, project) will take effect immediately. However, SAP remains the primary master data source of truth. Manual modifications will be preserved during regular syncs unless you explicitly choose to <strong>"Force Hard Sync"</strong> during Excel upload.
                </p>
            </div>
        </div>
    </div>

    <!-- Main Toolbar -->
    <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Search -->
            <div class="w-full md:w-1/3 relative">
                <input wire:model.live="search" type="text" placeholder="Search item code or description..." 
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm pl-10 py-2">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- Sync Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center text-xs font-semibold text-gray-700 cursor-pointer mr-2">
                    <input type="checkbox" wire:model="hardSync" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                    Force Hard Sync
                </label>


                <div class="relative flex items-center space-x-2">
                    <input type="file" wire:model="file" id="excel-file-input" class="hidden" accept=".xls,.xlsx,.csv,.txt">
                    <button type="button" onclick="document.getElementById('excel-file-input').click()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded text-sm shadow transition inline-flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Sync SAP Excel
                    </button>
                    <a href="{{ route('admin.master-list-logs') }}" 
                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-3 rounded text-sm shadow transition inline-flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- Connection Status Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-t pt-4">
            <span class="text-xs font-bold text-gray-500 uppercase mr-2">Status Customer:</span>
            
            <button wire:click="setConnectionFilter('ALL')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $filterConnection === 'ALL' ? 'bg-gray-900 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <span>Semua</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $filterConnection === 'ALL' ? 'bg-gray-700 text-gray-200' : 'bg-gray-200 text-gray-700' }}">{{ number_format($counts['ALL']) }}</span>
            </button>

            <button wire:click="setConnectionFilter('CONNECTED')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $filterConnection === 'CONNECTED' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                <span>✓ Terhubung</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $filterConnection === 'CONNECTED' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-200 text-emerald-900' }}">{{ number_format($counts['CONNECTED']) }}</span>
            </button>

            <button wire:click="setConnectionFilter('UNASSIGNED')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $filterConnection === 'UNASSIGNED' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                <span>⚠️ Belum Terhubung</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $filterConnection === 'UNASSIGNED' ? 'bg-amber-700 text-amber-100' : 'bg-amber-200 text-amber-900' }}">{{ number_format($counts['UNASSIGNED']) }}</span>
            </button>
        </div>
    </div>

    <!-- Livewire File Upload Trigger -->
    @if ($file && !$importing)
        <div class="bg-blue-50 border border-blue-200 p-5 rounded-lg shadow-sm space-y-4">
            <h3 class="font-bold text-blue-900 text-sm">Upload Sync Preview</h3>
            <p class="text-xs text-blue-700">We parsed the Excel headers and sample data. Please confirm alignment below before executing the sync process.</p>
            
            <div class="overflow-x-auto rounded border border-blue-200 bg-white">
                <table class="min-w-full divide-y divide-blue-200 text-xs text-left">
                    <thead class="bg-blue-100 text-blue-900 font-bold">
                        <tr>
                            <th class="px-3 py-2">Item Code</th>
                            <th class="px-3 py-2">Item Name</th>
                            <th class="px-3 py-2 text-center">Tipe Mesin</th>
                            <th class="px-3 py-2 text-center">Pack List</th>
                            <th class="px-3 py-2 text-center">Setup Time (m)</th>
                            <th class="px-3 py-2 text-center">Pair</th>
                            <th class="px-3 py-2 text-center">Cavity</th>
                            <th class="px-3 py-2 text-center">Cycle Time (s)</th>
                            <th class="px-3 py-2 text-center">Cust Code</th>
                            <th class="px-3 py-2 text-center">Family</th>
                            <th class="px-3 py-2 text-center">Foreign Desc</th>
                            <th class="px-3 py-2 text-center">Color</th>
                            <th class="px-3 py-2 text-center">Half Code 1</th>
                            <th class="px-3 py-2 text-center">Half Code 2</th>
                            <th class="px-3 py-2 text-center">Position</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-100 text-gray-700">
                        @foreach($previewRows as $row)
                            <tr>
                                <td class="px-3 py-2 font-bold">{{ $row['item_code'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['item_name'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['tipe_mesin'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['standart_packaging_list'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['setup_time_minute'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['pair'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['cavity'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['cycle_time'] ?? '0' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['customer_code'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['family'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['description_in_foreign_lang'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['color'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['half_code_1'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['half_code_2'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['position'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="flex justify-between items-center text-xs font-semibold">
                <span class="text-blue-900">Total detected records: <strong class="text-base text-blue-600">{{ number_format($totalRows) }}</strong></span>
                <div class="space-x-2">
                    <button type="button" wire:click="cancelImport" class="text-gray-600 hover:text-gray-800 py-2 px-4">Cancel</button>
                    <button type="button" wire:click="startImport" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                        Confirm & Sync Master List
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Import Progress Loader -->
    @if ($importing)
        @php
            $percentage = $totalRows > 0 ? round(($importedRowsCount / $totalRows) * 100) : 0;
        @endphp
        <div wire:poll.300ms="importChunk" class="bg-white p-6 rounded-lg border border-gray-200 shadow-md space-y-4">
            <div class="flex justify-between items-center text-sm font-bold text-gray-800">
                <span class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Syncing Master Data...
                </span>
                <span>{{ $percentage }}% Completed ({{ number_format($importedRowsCount) }} / {{ number_format($totalRows) }})</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
            </div>
            <p class="text-xs text-gray-500 italic text-center">Processing database records in batches of 500 rows. Please keep this tab open.</p>
        </div>
    @endif

    <!-- Spreadsheet-like Master Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                <thead class="bg-gray-50 text-gray-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Item Code</th>
                        <th class="px-4 py-3 w-1/6">Item Name</th>
                        <th class="px-3 py-3 text-center">Tipe Mesin</th>
                        <th class="px-3 py-3 text-center">Std Pack Qty</th>
                        <th class="px-3 py-3 text-center">Setup Time (m)</th>
                        <th class="px-3 py-3 text-center">Pair</th>
                        <th class="px-3 py-3 text-center">Cavity</th>
                        <th class="px-3 py-3 text-center">Cycle Time (s)</th>
                        <th class="px-3 py-3 text-center">Cust Code</th>
                        <th class="px-3 py-3 text-center">Family</th>
                        <th class="px-3 py-3 text-center">Foreign Desc</th>
                        <th class="px-3 py-3 text-center">Color</th>
                        <th class="px-3 py-3 text-center">Half Code 1</th>
                        <th class="px-3 py-3 text-center">Half Code 2</th>
                        <th class="px-3 py-3 text-center">Position</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-800">
                    @forelse ($items as $item)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-900 select-all">{{ $item->item_code }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-600 select-all">{{ $item->item_name }}</td>
                            
                            <!-- Tipe Mesin -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'tipe_mesin')">
                                @if($editingItemId === $item->id && $editingField === 'tipe_mesin')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->tipe_mesin ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Std Pack Qty -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'standart_packaging_list')">
                                @if($editingItemId === $item->id && $editingField === 'standart_packaging_list')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="number" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->standart_packaging_list ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Setup Time -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'setup_time_minute')">
                                @if($editingItemId === $item->id && $editingField === 'setup_time_minute')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="number" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->setup_time_minute ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Pair -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'pair')">
                                @if($editingItemId === $item->id && $editingField === 'pair')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="number" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->pair ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Cavity -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'cavity')">
                                @if($editingItemId === $item->id && $editingField === 'cavity')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="number" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->cavity ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Cycle Time -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'cycle_time')">
                                @if($editingItemId === $item->id && $editingField === 'cycle_time')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="number" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->cycle_time ?: '0' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Customer Code -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'customer_code')">
                                @if($editingItemId === $item->id && $editingField === 'customer_code')
                                    <div class="flex items-center justify-center space-x-1">
                                        <select wire:model.defer="editingValue" 
                                                wire:keydown.escape="cancelEdit"
                                                class="text-xs rounded border-gray-300 py-1 px-1.5 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 max-w-[170px]" autofocus>
                                            <option value="">-- Kosongkan / N/A --</option>
                                            @foreach($customers as $c)
                                                <option value="{{ $c->customer_code }}">{{ $c->customer_code }} - {{ Str::limit($c->customer_name, 20) }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="saveEdit" class="text-xs text-emerald-600 hover:text-emerald-800 font-bold px-1" title="Simpan">✓</button>
                                        <button wire:click="cancelEdit" class="text-xs text-gray-400 hover:text-gray-600 px-1" title="Batal">✕</button>
                                    </div>
                                @else
                                    @php
                                        $isConnected = !empty($item->customer_code) && !in_array($item->customer_code, ['0', '-']);
                                        $resolvedName = $item->customer?->customer_name ?: $item->businessPartner?->bp_name;
                                    @endphp
                                    @if($isConnected)
                                        <div class="cursor-pointer group inline-flex flex-col items-center" title="Double click to edit customer">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 group-hover:bg-emerald-100">
                                                {{ $item->customer_code }}
                                            </span>
                                            @if($resolvedName)
                                                <span class="text-[10px] text-gray-500 truncate max-w-[130px] font-medium" title="{{ $resolvedName }}">
                                                    {{ $resolvedName }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="cursor-pointer inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100" title="Double click to map customer">
                                            ⚠️ Belum Terhubung
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <!-- Family -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'family')">
                                @if($editingItemId === $item->id && $editingField === 'family')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-24 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600 font-semibold text-indigo-700" title="Double click to edit">
                                        {{ $item->family ?: '—' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Foreign Description -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'description_in_foreign_lang')">
                                @if($editingItemId === $item->id && $editingField === 'description_in_foreign_lang')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-28 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->description_in_foreign_lang ?: '—' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Color -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'color')">
                                @if($editingItemId === $item->id && $editingField === 'color')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->color ?: '—' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Half Code 1 -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'half_code_1')">
                                @if($editingItemId === $item->id && $editingField === 'half_code_1')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->half_code_1 ?: '—' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Half Code 2 -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'half_code_2')">
                                @if($editingItemId === $item->id && $editingField === 'half_code_2')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->half_code_2 ?: '—' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Position -->
                            <td class="px-2 py-2 text-center" wire:dblclick="startEdit({{ $item->id }}, 'position')">
                                @if($editingItemId === $item->id && $editingField === 'position')
                                    <div class="flex items-center justify-center space-x-1">
                                        <input type="text" wire:model.defer="editingValue" wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" 
                                               class="w-20 text-center rounded border-gray-300 p-1 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500" autofocus>
                                    </div>
                                @else
                                    <span class="cursor-pointer border-b border-dashed border-gray-400 hover:text-blue-600" title="Double click to edit">
                                        {{ $item->position ?: '—' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-6 text-gray-500 font-semibold">No master items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
            {{ $items->links() }}
        </div>
    </div>
</div>
