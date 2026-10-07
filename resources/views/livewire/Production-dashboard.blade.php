<div x-data="{ showPurgingModal: false }">
    <div class="container mx-auto px-4 py-6">
        {{-- Header --}}
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Production Dashboard</h1>
                <p class="text-gray-600 mt-1">Monitor production performance and NG rates</p>
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                {{-- Plant Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plant</label>
                    <select wire:model.live="plant" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-semibold text-gray-800">
                        <option value="">All Plants</option>
                        <option value="karawang">Plant Karawang (K)</option>
                        <option value="kbn">Plant KBN</option>
                    </select>
                </div>

                {{-- View Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">View Type</label>
                    <select wire:model.live="viewType" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily (Per Hari)</option>
                    </select>
                </div>

                {{-- Date (only shown for daily view) --}}
                @if($viewType === 'daily')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                    <input type="date" wire:model.live="selectedDate" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jadwal Shift</label>
                    <label class="flex items-center gap-2 h-[42px] px-3 py-2 bg-gray-50 border border-gray-300 rounded-md cursor-pointer hover:bg-amber-50/50 transition">
                        <input type="checkbox" wire:model.live="isHalfDay" class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                        <span class="text-xs font-bold text-gray-700 select-none {{ $isHalfDay ? 'text-amber-800' : '' }}">
                            Setengah Hari
                        </span>
                    </label>
                </div>
                @endif

                {{-- Year (shown for monthly & weekly view) --}}
                @if($viewType !== 'daily')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                    <select wire:model.live="year" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Month (shown for monthly & weekly view) --}}
                @if($viewType !== 'daily')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                    <select wire:model.live="month" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($months as $key => $monthName)
                            <option value="{{ $key }}">{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Week (only shown for weekly view) --}}
                @if($viewType === 'weekly')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Week</label>
                    <select wire:model.live="week" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($weeks as $index => $weekData)
                            <option value="{{ $weekData['number'] }}">{{ $weekData['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Weekend Half-Day Toggle (Weekly View) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Setengah Hari</label>
                    <div class="flex items-center gap-1.5 h-[42px]">
                        <label class="flex-1 flex items-center justify-center gap-1 px-2 py-2 bg-gray-50 border border-gray-300 rounded-md cursor-pointer hover:bg-amber-50/50 transition">
                            <input type="checkbox" wire:model.live="isSaturdayHalfDay" class="w-3.5 h-3.5 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                            <span class="text-xs select-none {{ $isSaturdayHalfDay ? 'text-amber-800 font-bold' : 'text-gray-700 font-medium' }}">
                                Sabtu
                            </span>
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-1 px-2 py-2 bg-gray-50 border border-gray-300 rounded-md cursor-pointer hover:bg-amber-50/50 transition">
                            <input type="checkbox" wire:model.live="isSundayHalfDay" class="w-3.5 h-3.5 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                            <span class="text-xs select-none {{ $isSundayHalfDay ? 'text-amber-800 font-bold' : 'text-gray-700 font-medium' }}">
                                Minggu
                            </span>
                        </label>
                    </div>
                </div>
                @endif

                {{-- Item Code - Searchable Dropdown --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Code</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live="itemCodeSearch"
                            wire:focus="showItemCodeDropdown = true"
                            placeholder="Search item code..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8"
                        >
                        
                        {{-- Clear button --}}
                        @if($itemCode)
                        <button 
                            wire:click="clearItemCode"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        @endif

                        {{-- Dropdown List --}}
                        @if($showItemCodeDropdown && count($filteredItemCodes) > 0)
                        <div 
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                            x-data="{ open: true }"
                            @click.away="$wire.showItemCodeDropdown = false"
                        >
                            @foreach($filteredItemCodes as $code)
                            <div 
                                wire:click="selectItemCode('{{ $code }}')"
                                class="px-3 py-2 hover:bg-blue-50 cursor-pointer {{ $itemCode === $code ? 'bg-blue-100' : '' }}"
                            >
                                <span class="text-sm text-gray-800">{{ $code }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- No results --}}
                        @if($showItemCodeDropdown && empty($itemCodeSearch) === false && count($filteredItemCodes) === 0)
                        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                            <div class="px-3 py-2 text-sm text-gray-500">
                                No items found
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    {{-- Selected item badge --}}
                    @if($itemCode)
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded">
                            {{ $itemCode }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Machine - Simple Dropdown --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Machine</label>
                    <select wire:model.live="machineUserId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">
                            @if($plant === 'karawang')
                                All Karawang Machines ({{ count($machines) }})
                            @elseif($plant === 'kbn')
                                All KBN Machines ({{ count($machines) }})
                            @else
                                All Machines ({{ count($machines) }})
                            @endif
                        </option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine['id'] }}">{{ $machine['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Reset Button & Filter Info --}}
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <button 
                    wire:click="resetFilters" 
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
                
                @if($plant || $itemCode || $machineUserId || $viewType === 'daily')
                <div class="text-sm text-gray-500 flex flex-wrap items-center gap-2">
                    <span>Active filters:</span>
                    @if($plant)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $plant === 'karawang' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                            Plant: {{ $plant === 'karawang' ? 'Karawang' : 'KBN' }}
                        </span>
                    @endif
                    @if($viewType === 'daily')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                            Date: {{ date('d M Y', strtotime($selectedDate)) }}
                        </span>
                        @if($isHalfDay)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                ⏱️ Setengah Hari (07:30-12:30 | 12:30-17:30 | 17:30-22:30)
                            </span>
                        @endif
                    @endif
                    @if($itemCode)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">
                            Item: {{ $itemCode }}
                        </span>
                    @endif
                    @if($machineUserId)
                        @php
                            $selectedMachine = collect($allMachines)->firstWhere('id', $machineUserId);
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                            Machine: {{ $selectedMachine['name'] ?? $machineUserId }}
                        </span>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Summary Cards (5 cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            {{-- Total Target --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Target</p>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($summary['total_target'] ?? 0) }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Actual --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Actual</p>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($summary['total_actual'] ?? 0) }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Achievement Rate --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Achievement Rate</p>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($summary['achievement_rate'] ?? 0, 1) }}%</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- NG Rate --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">NG Rate</p>
                        <p class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($summary['ng_rate'] ?? 0, 2) }}%</p>
                        <p class="text-xs text-gray-500 mt-1">{{ number_format($summary['total_ng'] ?? 0) }} pcs</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Hasil Purging --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Hasil Purging</p>
                        <p class="text-2xl font-bold text-orange-600 mt-2">{{ number_format($summary['total_purging'] ?? 0, 2) }} KG</p>
                        <button @click="showPurgingModal = true" class="text-xs text-blue-600 hover:text-blue-800 font-bold mt-2 outline-none focus:outline-none flex items-center">
                            Detail Data Purging
                            <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- Section: Shift Performance: Adjuster & Change Mould Tracking --}}
        {{-- ========================================================================= --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6" x-data="{ showShiftLogs: false, activeLogTab: 'all' }">
            {{-- Section Title & Header Summary --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 mb-6 border-b gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <h2 class="text-xl font-bold text-gray-800">Shift Performance: Adjuster, Change Mould, Repair & NG Tracking</h2>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Performa output, NG produk, serta penanggung jawab Adjuster, Change Mould, dan Repair Machine per shift</p>
                </div>

                {{-- Quick Summary Stats --}}
                <div class="flex flex-wrap items-center gap-3">
                    <div class="px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs">
                        <span class="text-gray-500 font-medium">Total Adjust:</span>
                        <span class="font-bold text-blue-700 ml-1">{{ $shiftPersonnelAnalysis['total_adjust_count'] ?? 0 }}x</span>
                    </div>
                    <div class="px-3 py-1.5 bg-purple-50 border border-purple-200 rounded-lg text-xs">
                        <span class="text-gray-500 font-medium">Total Change Mould:</span>
                        <span class="font-bold text-purple-700 ml-1">{{ $shiftPersonnelAnalysis['total_mould_change_count'] ?? 0 }}x</span>
                    </div>
                    <div class="px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-xs">
                        <span class="text-gray-500 font-medium">Total Repair:</span>
                        <span class="font-bold text-amber-700 ml-1">{{ $shiftPersonnelAnalysis['total_repair_count'] ?? 0 }}x</span>
                        <span class="text-[10px] text-gray-400 font-mono">({{ number_format($shiftPersonnelAnalysis['total_repair_time_minutes'] ?? 0, 1) }}m)</span>
                    </div>
                    <div class="px-3 py-1.5 bg-orange-50 border border-orange-200 rounded-lg text-xs">
                        <span class="text-gray-500 font-medium">Total Setup Time:</span>
                        <span class="font-bold text-orange-700 ml-1">{{ number_format($shiftPersonnelAnalysis['total_setup_time_minutes'] ?? 0, 1) }}m</span>
                    </div>
                    <button 
                        @click="showShiftLogs = !showShiftLogs"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold flex items-center gap-1 transition"
                    >
                        <span x-text="showShiftLogs ? 'Tutup Log' : 'Detail Log'"></span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showShiftLogs ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- 3 Shift Cards Grid (Shift 1, Shift 2, Shift 3) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
                @foreach([1, 2, 3] as $shiftNum)
                    @php
                        $shiftData = $shiftPersonnelAnalysis['shifts'][$shiftNum] ?? [];
                        $ngRate = $shiftData['ng_rate'] ?? 0;
                        
                        // Theme styling
                        $borderTop = match($shiftNum) {
                            1 => 'border-t-4 border-amber-500',
                            2 => 'border-t-4 border-emerald-500',
                            3 => 'border-t-4 border-indigo-500',
                            default => 'border-t-4 border-blue-500'
                        };
                        $shiftBadgeClass = match($shiftNum) {
                            1 => 'bg-amber-100 text-amber-800 border-amber-300',
                            2 => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            3 => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                            default => 'bg-blue-100 text-blue-800 border-blue-300'
                        };
                        $ngBadgeClass = $ngRate > 5 
                            ? 'bg-red-100 text-red-800 border-red-300' 
                            : ($ngRate > 2 
                                ? 'bg-yellow-100 text-yellow-800 border-yellow-300' 
                                : 'bg-green-100 text-green-800 border-green-300');
                    @endphp

                    <div class="bg-gray-50/60 rounded-xl border border-gray-200 p-5 flex flex-col justify-between {{ $borderTop }} shadow-sm hover:shadow transition overflow-hidden">
                        <div class="min-w-0">
                            {{-- Shift Card Header --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $shiftBadgeClass }}">
                                        {{ $shiftData['name'] ?? "Shift $shiftNum" }}
                                    </span>
                                    <p class="text-[11px] text-gray-500 mt-1 font-mono font-medium truncate">🕒 {{ $shiftData['time_range'] ?? '-' }}</p>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border {{ $ngBadgeClass }}">
                                        {{ number_format($ngRate, 2) }}% NG
                                    </span>
                                    <p class="text-[10px] text-gray-500 mt-0.5">{{ number_format($shiftData['total_ng'] ?? 0) }} NG pcs</p>
                                </div>
                            </div>

                            {{-- Responsible Personnel (PIC) Card --}}
                            <div class="bg-white rounded-lg p-3.5 border border-gray-200/80 mb-4 space-y-2.5 shadow-xs overflow-hidden">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Penanggung Jawab Shift</div>
                                
                                {{-- Adjuster PIC --}}
                                <div class="bg-blue-50/70 border border-blue-100 rounded-lg p-2.5 overflow-hidden">
                                    <div class="flex items-center justify-between mb-1.5 gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] shrink-0 font-bold">
                                                🔧
                                            </span>
                                            <span class="text-[10px] font-bold uppercase text-blue-700 tracking-wide">Adjuster</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-bold text-blue-700">{{ $shiftData['adjust_count'] ?? 0 }}x</span>
                                            <span class="text-[10px] text-gray-500 font-mono ml-0.5">({{ number_format($shiftData['adjust_duration_minutes'] ?? 0, 1) }}m)</span>
                                        </div>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-800 break-words line-clamp-2 pl-6" title="{{ $shiftData['adjusters_str'] ?? '-' }}">
                                        {{ $shiftData['adjusters_str'] ?? 'No Log' }}
                                    </div>
                                </div>

                                {{-- Change Mould PIC --}}
                                <div class="bg-purple-50/70 border border-purple-100 rounded-lg p-2.5 overflow-hidden">
                                    <div class="flex items-center justify-between mb-1.5 gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center text-[10px] shrink-0 font-bold">
                                                🔄
                                            </span>
                                            <span class="text-[10px] font-bold uppercase text-purple-700 tracking-wide">Change Mould</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-bold text-purple-700">{{ $shiftData['mould_change_count'] ?? 0 }}x</span>
                                            <span class="text-[10px] text-gray-500 font-mono ml-0.5">({{ number_format($shiftData['mould_change_duration_minutes'] ?? 0, 1) }}m)</span>
                                        </div>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-800 break-words line-clamp-2 pl-6" title="{{ $shiftData['mould_changers_str'] ?? '-' }}">
                                        {{ $shiftData['mould_changers_str'] ?? 'No Log' }}
                                    </div>
                                </div>

                                {{-- Repair Machine (Maintenance) PIC --}}
                                <div class="bg-amber-50/70 border border-amber-100 rounded-lg p-2.5 overflow-hidden">
                                    <div class="flex items-center justify-between mb-1.5 gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-5 h-5 rounded-full bg-amber-600 text-white flex items-center justify-center text-[10px] shrink-0 font-bold">
                                                🛠️
                                            </span>
                                            <span class="text-[10px] font-bold uppercase text-amber-700 tracking-wide">Repair Machine</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-bold text-amber-700">{{ $shiftData['repair_count'] ?? 0 }}x</span>
                                            <span class="text-[10px] text-gray-500 font-mono ml-0.5">({{ number_format($shiftData['repair_duration_minutes'] ?? 0, 1) }}m)</span>
                                        </div>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-800 break-words line-clamp-2 pl-6" title="{{ $shiftData['repairers_str'] ?? '-' }}">
                                        {{ $shiftData['repairers_str'] ?? 'No Log' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Shift Output & Performance KPI Grid --}}
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="bg-white rounded-lg p-2.5 border border-gray-200 text-center">
                                    <span class="text-[10px] font-medium text-gray-500 uppercase">Actual Output</span>
                                    <p class="text-base font-bold text-gray-800 mt-0.5">{{ number_format($shiftData['total_actual'] ?? 0) }}</p>
                                    <span class="text-[10px] text-gray-400">Target: {{ number_format($shiftData['total_target'] ?? 0) }}</span>
                                </div>
                                <div class="bg-white rounded-lg p-2.5 border border-gray-200 text-center">
                                    <span class="text-[10px] font-medium text-gray-500 uppercase">Setup Downtime</span>
                                    <p class="text-base font-bold text-orange-600 mt-0.5">{{ number_format($shiftData['total_setup_minutes'] ?? 0, 1) }}m</p>
                                    <span class="text-[10px] text-gray-400">Adjust + Mould</span>
                                </div>
                            </div>

                            {{-- Achievement Rate Progress Bar --}}
                            <div class="mb-4 bg-white p-2.5 rounded-lg border border-gray-200">
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-600 font-medium">Achievement</span>
                                    <span class="font-bold {{ ($shiftData['achievement_rate'] ?? 0) >= 100 ? 'text-green-600' : 'text-blue-600' }}">
                                        {{ number_format($shiftData['achievement_rate'] ?? 0, 1) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ ($shiftData['achievement_rate'] ?? 0) >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min(100, $shiftData['achievement_rate'] ?? 0) }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Top Defect Types in this Shift --}}
                        @if(!empty($shiftData['top_ng_types']))
                        <div class="border-t pt-3 mt-1">
                            <div class="text-[11px] font-bold text-gray-500 mb-2 flex items-center justify-between">
                                <span>Defect Dominan (Top NG):</span>
                                <span class="text-[10px] text-gray-400">{{ count($shiftData['top_ng_types']) }} jenis</span>
                            </div>
                            <div class="space-y-1.5">
                                @foreach($shiftData['top_ng_types'] as $ngType)
                                <div class="flex items-center text-xs">
                                    <span class="text-gray-700 w-28 truncate font-medium" title="{{ $ngType['name'] }}">{{ $ngType['name'] }}</span>
                                    <div class="flex-1 mx-2 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ $ngType['percent'] }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-red-600 w-16 text-right">{{ number_format($ngType['quantity']) }} ({{ $ngType['percent'] }}%)</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="border-t pt-3 mt-1 text-center text-xs text-gray-400 italic">
                            Tidak ada data NG pada shift ini
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Detail Activity Log Table (Expandable) --}}
            <div x-show="showShiftLogs" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-4 pt-4 border-t border-gray-200" style="display: none;">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <span>📋 Log Aktivitas Adjust Machine, Change Mould & Repair Machine</span>
                        <span class="px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded-full font-semibold">
                            {{ count($shiftPersonnelAnalysis['all_logs'] ?? []) }} Total Data
                        </span>
                    </h3>

                    {{-- Shift Filter Tabs inside table --}}
                    <div class="flex items-center gap-1.5 text-xs">
                        <button @click="activeLogTab = 'all'" :class="activeLogTab === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded font-medium transition">
                            Semua
                        </button>
                        <button @click="activeLogTab = '1'" :class="activeLogTab === '1' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded font-medium transition">
                            Shift 1
                        </button>
                        <button @click="activeLogTab = '2'" :class="activeLogTab === '2' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded font-medium transition">
                            Shift 2
                        </button>
                        <button @click="activeLogTab = '3'" :class="activeLogTab === '3' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2.5 py-1 rounded font-medium transition">
                            Shift 3
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-80 border border-gray-200 rounded-lg">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-700 uppercase font-semibold sticky top-0">
                            <tr>
                                <th class="px-3 py-2.5">Waktu / Shift</th>
                                <th class="px-3 py-2.5">Tipe Aktivitas</th>
                                <th class="px-3 py-2.5">Mesin</th>
                                <th class="px-3 py-2.5">Item Code</th>
                                <th class="px-3 py-2.5">PIC</th>
                                <th class="px-3 py-2.5 text-right">Durasi (Menit)</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($shiftPersonnelAnalysis['all_logs'] ?? [] as $log)
                                <tr x-show="activeLogTab === 'all' || activeLogTab === '{{ $log['shift'] }}'" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-800">
                                        <div>{{ $log['start_time'] }} - {{ $log['end_time'] }}</div>
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold {{ $log['shift'] === 1 ? 'bg-amber-100 text-amber-800' : ($log['shift'] === 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800') }}">
                                            Shift {{ $log['shift'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($log['type'] === 'adjust')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                                🔧 Adjust Machine
                                            </span>
                                        @elseif($log['type'] === 'mould_change')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">
                                                🔄 Mould Change
                                            </span>
                                        @elseif($log['type'] === 'repair')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                                🛠️ Repair Machine
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap font-semibold text-gray-700">
                                        {{ $log['machine_name'] }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-600">
                                        {{ $log['item_code'] }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap font-bold text-gray-800">
                                        {{ $log['pic'] }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right font-mono font-bold {{ (!empty($log['is_overtime'])) ? 'text-red-600' : 'text-gray-800' }}">
                                        {{ number_format($log['duration_minutes'], 1) }}m
                                        @if(!empty($log['target_minutes']))
                                            <div class="text-[10px] text-gray-400 font-normal">Target: {{ $log['target_minutes'] }}m</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-center">
                                        @if($log['type'] === 'repair')
                                            @if(($log['end_time'] ?? '') === 'In Progress')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-800 animate-pulse">
                                                    In Progress
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800">
                                                    Selesai
                                                </span>
                                            @endif
                                        @else
                                            @if(!empty($log['is_overtime']))
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                                    Overtime
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800">
                                                    Normal
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 max-w-xs truncate" title="{{ $log['remark'] }}">
                                        {{ $log['remark'] ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">
                                        Tidak ada log Adjust Machine, Mould Change, atau Repair Machine untuk periode / filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- Section: Trend NG Harian per Adjuster (Line Chart) --}}
        {{-- ========================================================================= --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 mb-4 border-b gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </span>
                        <h2 class="text-xl font-bold text-gray-800">Trend NG Harian per Adjuster</h2>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Perbandingan jumlah NG produk (pcs) setiap hari berdasarkan adjuster yang bertugas pada shift tersebut</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-md">
                        Total Adjuster: {{ count($adjusterNgTrend['adjuster_summaries'] ?? []) }} Orang
                    </span>
                </div>
            </div>

            {{-- Line Chart Container --}}
            <div class="h-80 mb-6" wire:ignore>
                <canvas id="adjusterNgChart"></canvas>
            </div>

            {{-- Adjuster Performance Summary Cards / Leaderboard --}}
            @if(!empty($adjusterNgTrend['adjuster_summaries']))
            <div class="border-t pt-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Akumulasi NG & Output per Adjuster (Periode Ini):</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($adjusterNgTrend['adjuster_summaries'] as $adjSummary)
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 flex flex-col justify-between hover:bg-gray-100/80 transition">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $adjSummary['color'] }};"></span>
                                    <span class="text-xs font-bold text-gray-800 truncate" title="{{ $adjSummary['name'] }}">{{ $adjSummary['name'] }}</span>
                                </div>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded font-bold {{ $adjSummary['ng_rate'] > 5 ? 'bg-red-100 text-red-700' : ($adjSummary['ng_rate'] > 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                    {{ number_format($adjSummary['ng_rate'], 2) }}% NG
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-center my-2">
                                <div class="bg-white rounded p-1.5 border border-gray-200/60">
                                    <div class="text-[10px] text-gray-400">Total NG</div>
                                    <div class="text-xs font-bold text-red-600">{{ number_format($adjSummary['total_ng']) }} pcs</div>
                                </div>
                                <div class="bg-white rounded p-1.5 border border-gray-200/60">
                                    <div class="text-[10px] text-gray-400">Total Output</div>
                                    <div class="text-xs font-bold text-gray-800">{{ number_format($adjSummary['total_actual']) }} pcs</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-500 pt-1 border-t border-gray-200/60">
                            <span>Adjust: {{ $adjSummary['adjust_count'] }}x</span>
                            <span>Durasi: {{ number_format($adjSummary['adjust_minutes'], 1) }}m</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="text-center py-6 text-gray-400 text-xs italic">
                Tidak ada data adjuster yang tercatat pada periode ini.
            </div>
            @endif
        </div>

        {{-- Downtime & Machine Hours Cards (3-column grid) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Downtime Summary Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Downtime</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">
                            {{ number_format($downtimeAnalysis['total_downtime_hours'] ?? 0, 1) }} hrs
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($downtimeAnalysis['total_downtime_minutes'] ?? 0, 1) }} minutes
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $downtimeAnalysis['problem_hours_count'] ?? 0 }} problem hours
                        </p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
                {{-- Top problem hours --}}
                @if(count($downtimeAnalysis['downtime_by_hour'] ?? []) > 0)
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Worst Hours:</h3>
                    @foreach(array_slice($downtimeAnalysis['downtime_by_hour'] ?? [], 0, 5) as $hourData)
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">{{ $hourData['hour'] }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-orange-600">
                                {{ number_format($hourData['total_downtime'], 1) }}m
                            </span>
                            <span class="text-xs text-gray-400">({{ $hourData['occurrences'] }}x)</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Downtime Distribution Chart --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Downtime Distribution by Hour</h2>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($downtimeAnalysis['downtime_by_hour'] ?? [] as $hourData)
                        @php
                            $maxDowntime = max(array_column($downtimeAnalysis['downtime_by_hour'], 'total_downtime') ?: [1]);
                            $width = $maxDowntime > 0 ? ($hourData['total_downtime'] / $maxDowntime) * 100 : 0;
                        @endphp
                        <div class="flex items-center">
                            <span class="text-xs font-medium text-gray-600 w-16">{{ $hourData['hour'] }}</span>
                            <div class="flex-1 mx-3">
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-orange-500 h-3 rounded-full transition-all" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                            <span class="text-xs font-medium text-orange-600 w-20 text-right">
                                {{ number_format($hourData['total_downtime'], 1) }}m
                            </span>
                            <span class="text-xs text-gray-400 w-12 text-right">({{ $hourData['occurrences'] }})</span>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-sm">No downtime recorded</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ✅ NEW: Machine Working Hours Card --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Working Hours per Machine</h2>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($machineWorkingHours as $mHours)
                        @php
                            $maxHours = max(array_column($machineWorkingHours, 'hours') ?: [1]);
                            $width = $maxHours > 0 ? ($mHours['hours'] / $maxHours) * 100 : 0;
                        @endphp
                        <div class="flex items-center">
                            <span class="text-xs font-semibold text-gray-700 w-20 truncate" title="{{ $mHours['name'] }}">
                                {{ $mHours['name'] }}
                            </span>
                            <div class="flex-1 mx-3">
                                <div class="w-full bg-gray-100 rounded-full h-3">
                                    <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-blue-600 w-16 text-right">
                                {{ number_format($mHours['hours']) }} hrs
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-sm">No working hours recorded</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Chart & NG Breakdown Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Production Overview</h2>
                <div class="h-96" wire:ignore>
                    <canvas id="productionChart"></canvas>
                </div>
            </div>

            {{-- NG Breakdown --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">NG Breakdown</h2>
                    <span class="text-xs text-gray-400 font-medium">Klik jenis NG untuk lihat model &amp; remark</span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($ngBreakdown as $ngIndex => $ng)
                        <div x-data="{ open: false, selectedModel: 'ALL' }" 
                             class="border-l-4 border-red-500 bg-white rounded-r-lg transition border border-gray-100 shadow-2xs">
                            <div class="p-3 cursor-pointer hover:bg-gray-50/80 transition" @click="open = !open">
                                <div class="flex justify-between items-center gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-gray-800">{{ $ng['name'] }}</span>
                                        @if(!empty($ng['models_count']))
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">
                                                {{ $ng['models_count'] }} Model
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-sm font-black text-red-600 font-mono">{{ number_format($ng['total']) }}</span>
                                        <button type="button" class="text-gray-400 hover:text-gray-600 transition-transform duration-200 p-1"
                                                :class="{ 'rotate-180': open }" title="Buka detail model &amp; remark">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                @if(($summary['total_ng'] ?? 0) > 0)
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                        <div class="bg-red-500 h-2 rounded-full transition-all duration-300" style="width: {{ ($ng['total'] / $summary['total_ng']) * 100 }}%"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-xs text-gray-500 mt-1">
                                        <span>{{ number_format(($ng['total'] / $summary['total_ng']) * 100, 1) }}% dari total NG</span>
                                        <span class="text-[11px] text-red-600 font-semibold" x-text="open ? 'Tutup Detail ▲' : 'Lihat Model &amp; Remark ▼'"></span>
                                    </div>
                                @endif
                            </div>

                            {{-- DROPDOWN ACCORDION DETAIL: Model apa saja & Remark per data NG --}}
                            <div x-show="open" x-cloak class="border-t border-gray-100 bg-gray-50/60 p-3 space-y-3">
                                @if(empty($ng['models']))
                                    <div class="text-center py-3 text-xs text-gray-400">
                                        Tidak ada detail model untuk jenis NG ini.
                                    </div>
                                @else
                                    {{-- 1. Filter Dropdown Model --}}
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <label class="text-[11px] font-bold text-gray-600 flex items-center gap-1">
                                            <span>🔍 Filter Model:</span>
                                        </label>
                                        <select x-model="selectedModel" 
                                                class="text-xs bg-white border border-gray-300 rounded-lg px-2.5 py-1 font-semibold text-gray-800 shadow-2xs focus:ring-1 focus:ring-red-400 outline-none max-w-xs">
                                            <option value="ALL">Semua Model ({{ $ng['models_count'] }} Model — {{ number_format($ng['total']) }} pcs)</option>
                                            @foreach($ng['models'] as $mItem)
                                                <option value="{{ $mItem['item_code'] }}">
                                                    {{ $mItem['item_code'] }} - {{ \Illuminate\Support\Str::limit($mItem['item_name'], 25) }} ({{ number_format($mItem['total']) }} pcs)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 2. List Model & Remarks --}}
                                    <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                        @foreach($ng['models'] as $mItem)
                                            <div x-show="selectedModel === 'ALL' || selectedModel === '{{ $mItem['item_code'] }}'" 
                                                 x-data="{ modelOpen: false }"
                                                 x-init="$watch('selectedModel', val => { if (val === '{{ $mItem['item_code'] }}') { modelOpen = true; } else if (val === 'ALL') { modelOpen = false; } })"
                                                 class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-2xs transition">
                                                
                                                {{-- Header Model: Selalu ditutup secara default, klik untuk buka/tutup (dropdown) --}}
                                                <button type="button" 
                                                        @click="modelOpen = !modelOpen"
                                                        class="w-full p-2.5 flex items-center justify-between gap-2 hover:bg-gray-50/90 text-left transition select-none group">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="w-5 h-5 rounded flex items-center justify-center text-gray-400 group-hover:text-gray-700 bg-gray-100 transition-transform duration-200 shrink-0"
                                                              :class="{ 'rotate-180 bg-red-50 text-red-600': modelOpen }">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        </span>
                                                        <div class="truncate">
                                                            <div class="font-mono font-black text-xs text-gray-900 flex items-center gap-1.5">
                                                                <span>{{ $mItem['item_code'] }}</span>
                                                                <span class="text-[10px] font-normal text-gray-400">({{ count($mItem['records']) }} data)</span>
                                                            </div>
                                                            <div class="text-[11px] text-gray-600 font-medium truncate" title="{{ $mItem['item_name'] }}">
                                                                {{ $mItem['item_name'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-right shrink-0 flex items-center gap-2">
                                                        <div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-black bg-red-100 text-red-800 font-mono">
                                                                {{ number_format($mItem['total']) }} pcs
                                                            </span>
                                                            @if($ng['total'] > 0)
                                                                <div class="text-[10px] text-gray-400 font-semibold mt-0.5">
                                                                    {{ number_format(($mItem['total'] / $ng['total']) * 100, 1) }}%
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </button>

                                                {{-- Detail Table: Ditutup secara default, hanya terbuka saat diklik drop down --}}
                                                <div x-show="modelOpen" x-cloak class="border-t border-gray-100 p-2.5 bg-gray-50/60">
                                                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1.5 flex items-center justify-between">
                                                        <span>📜 Data &amp; Remark ({{ count($mItem['records']) }} data):</span>
                                                        <button type="button" @click.stop="modelOpen = false" class="text-gray-400 hover:text-red-500 text-[10px] font-semibold flex items-center gap-0.5">
                                                            <span>Tutup Detail</span>
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                                        </button>
                                                    </div>
                                                    <div class="overflow-x-auto max-h-56 border border-gray-200 rounded">
                                                        <table class="w-full text-[11px] text-left">
                                                            <thead class="bg-gray-100/90 text-[10px] text-gray-600 uppercase font-bold border-b border-gray-200 sticky top-0">
                                                                <tr>
                                                                    <th class="py-1.5 px-2">Tanggal / Jam</th>
                                                                    <th class="py-1.5 px-2">Mesin / Shift</th>
                                                                    <th class="py-1.5 px-2 text-right">Qty NG</th>
                                                                    <th class="py-1.5 px-2 min-w-[120px]">Remark Produksi</th>
                                                                    <th class="py-1.5 px-2 min-w-[120px]">Remark NG</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                                @foreach($mItem['records'] as $rec)
                                                                    <tr class="hover:bg-gray-50/70 align-top">
                                                                        <td class="py-1.5 px-2 whitespace-nowrap text-gray-700 font-medium">
                                                                            {{ $rec['date'] }}
                                                                            <span class="text-gray-400 block text-[10px] font-mono">{{ $rec['hour'] }}</span>
                                                                        </td>
                                                                        <td class="py-1.5 px-2 whitespace-nowrap">
                                                                            <span class="font-mono font-bold text-gray-900">{{ $rec['machine'] }}</span>
                                                                            <span class="text-gray-400 block text-[10px]">Shift {{ $rec['shift'] }}</span>
                                                                        </td>
                                                                        <td class="py-1.5 px-2 text-right font-mono font-black text-red-600 whitespace-nowrap">
                                                                            {{ number_format($rec['quantity']) }}
                                                                        </td>
                                                                        <td class="py-1.5 px-2 text-gray-800">
                                                                            @if(!empty($rec['hourly_remark']))
                                                                                <span class="inline-block px-1.5 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200 text-[10px] font-medium leading-relaxed">
                                                                                    🏭 {{ $rec['hourly_remark'] }}
                                                                                </span>
                                                                            @else
                                                                                <span class="text-gray-300 italic text-[10px]">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="py-1.5 px-2 text-gray-800">
                                                                            @if(!empty($rec['ng_remark']))
                                                                                <span class="inline-block px-1.5 py-0.5 rounded bg-red-50 text-red-900 border border-red-200 text-[10px] font-bold leading-relaxed">
                                                                                    ⚠️ {{ $rec['ng_remark'] }}
                                                                                </span>
                                                                            @else
                                                                                <span class="text-gray-300 italic text-[10px]">-</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>No NG data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ✅ NEW: Top 10 Problematic Remarks Table --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Top 10 Problem Hours</h2>
            
            @if(count($topRemarks) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Hour</th>
                            <th class="px-4 py-3">Machine</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 text-right">Target</th>
                            <th class="px-4 py-3 text-right">Actual</th>
                            <th class="px-4 py-3 text-right">Gap</th>
                            <th class="px-4 py-3 text-right">Downtime</th>
                            <th class="px-4 py-3">Remark</th>
                            <th class="px-4 py-3 text-center">Severity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($topRemarks as $index => $remark)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ date('d M Y', strtotime($remark['date'])) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $remark['hour'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $remark['machine'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $remark['item_code'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700">
                                {{ number_format($remark['target']) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-700">
                                {{ number_format($remark['actual']) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-red-600">
                                {{ number_format($remark['gap']) }} pcs
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-orange-600">
                                {{ number_format($remark['downtime_minutes'], 1) }}m
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $remark['remark'] }}">
                                {{ $remark['remark'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($remark['severity'] === 'critical') bg-red-100 text-red-800
                                    @elseif($remark['severity'] === 'high') bg-orange-100 text-orange-800
                                    @elseif($remark['severity'] === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-blue-100 text-blue-800
                                    @endif">
                                    {{ ucfirst($remark['severity']) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12 text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-lg font-medium">No Problem Hours Found</p>
                <p class="text-sm mt-1">All production hours met their targets!</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Scripts untuk Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        let productionChart = null;
        let rawChartData = [];

        function initChart(data) {
            console.log('🔵 initChart called with data:', data);
            rawChartData = data;
            const ctx = document.getElementById('productionChart');
            
            if (!ctx) {
                console.error('❌ Chart canvas not found!');
                return;
            }

            if (!data || data.length === 0) {
                console.warn('⚠️ No data to display');
                return;
            }

            const labels = data.map(d => d.date);
            const targetData = data.map(d => d.target);
            const actualData = data.map(d => d.actual);
            const ngRateData = data.map(d => d.ng_rate);

            if (productionChart) {
                console.log('🔄 Updating existing chart...');
                productionChart.data.labels = labels;
                productionChart.data.datasets[0].data = targetData;
                productionChart.data.datasets[1].data = actualData;
                productionChart.data.datasets[2].data = ngRateData;
                productionChart.update('active');
                console.log('✅ Chart updated!');
                return;
            }

            console.log('🆕 Creating new chart...');
            productionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target',
                            data: targetData,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Actual',
                            data: actualData,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            label: 'NG Rate (%)',
                            data: ngRateData,
                            type: 'line',
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 2,
                            fill: false,
                            yAxisID: 'y1',
                            tension: 0.4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.yAxisID === 'y1') {
                                        label += context.parsed.y.toFixed(2) + '%';
                                    } else {
                                        label += context.parsed.y.toLocaleString();
                                    }
                                    return label;
                                },
                                footer: function(tooltipItems) {
                                    if (tooltipItems.length > 0) {
                                        const dataIndex = tooltipItems[0].dataIndex;
                                        const dayInfo = rawChartData[dataIndex];
                                        if (dayInfo && typeof dayInfo.working_hours !== 'undefined') {
                                            return (dayInfo.date && dayInfo.date.includes(':'))
                                                ? 'Active Machines: ' + dayInfo.working_hours
                                                : 'Active Hours: ' + dayInfo.working_hours + ' hrs';
                                        }
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Quantity (pcs)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'NG Rate (%)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            min: 0,
                            max: 100
                        }
                    }
                }
            });
            console.log('✅ Chart created!');
        }

        let adjusterNgChart = null;

        function initAdjusterChart(chartData) {
            console.log('🔵 initAdjusterChart called with data:', chartData);
            const ctx = document.getElementById('adjusterNgChart');
            if (!ctx) {
                console.error('❌ Adjuster chart canvas not found!');
                return;
            }

            if (!chartData || !chartData.datasets || chartData.datasets.length === 0) {
                console.warn('⚠️ No adjuster chart data to display');
                if (adjusterNgChart) {
                    adjusterNgChart.destroy();
                    adjusterNgChart = null;
                }
                return;
            }

            if (adjusterNgChart) {
                console.log('🔄 Updating existing adjuster chart...');
                adjusterNgChart.data.labels = chartData.labels;
                adjusterNgChart.data.datasets = chartData.datasets;
                adjusterNgChart.update('active');
                console.log('✅ Adjuster chart updated!');
                return;
            }

            console.log('🆕 Creating new adjuster chart...');
            adjusterNgChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toLocaleString() + ' pcs NG';
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'NG Quantity (pcs)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            console.log('✅ Adjuster chart created!');
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM Content Loaded');
            const initialData = @json($chartData);
            console.log('📊 Initial chart data:', initialData);
            initChart(initialData);

            const initialAdjusterData = @json($adjusterNgTrend);
            console.log('📊 Initial adjuster chart data:', initialAdjusterData);
            initAdjusterChart(initialAdjusterData);
        });

        document.addEventListener('livewire:init', () => {
            console.log('⚡ Livewire initialized, setting up event listener...');
            
            Livewire.on('chartDataUpdated', (event) => {
                console.log('🎯 chartDataUpdated event received!');
                const data = event.chartData;
                if (data) {
                    initChart(data);
                }
            });

            Livewire.on('adjusterChartDataUpdated', (event) => {
                console.log('🎯 adjusterChartDataUpdated event received!');
                const adjData = event.adjusterChartData;
                if (adjData) {
                    initAdjusterChart(adjData);
                }
            });
        });
    </script>

    {{-- Purging Detail Modal --}}
    <div x-show="showPurgingModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showPurgingModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showPurgingModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <!-- Trick browser to center modal contents. -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showPurgingModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center pb-3 border-b mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Detail Data Purging</h3>
                        <button @click="showPurgingModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DIC ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mesin</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Berat Purging (KG)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purgingDetails as $detail)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600">
                                            #{{ $detail['dic_id'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{ date('d M Y', strtotime($detail['date'])) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            Shift {{ $detail['shift'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{ $detail['machine_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                            {{ $detail['item_code'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-orange-600">
                                            {{ number_format($detail['resin_usage'], 2) }} KG
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 italic">
                                            Tidak ada data purging untuk periode/filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                    <button @click="showPurgingModal = false" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>