<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6" wire:init="load">

    {{-- Header & Top Controls --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Plastic Injection IPQC Inspection Reports
            </h2>
            <p class="text-sm text-gray-500 mt-1">Daily injection quality inspections, period checks, and shift defect logs.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inspection-reports.dashboard') }}" wire:navigate
               class="inline-flex items-center px-3.5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics Dashboard
            </a>

            <a href="{{ route('inspection-reports.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Report
            </a>
        </div>
    </div>

    {{-- Filter Toolbar Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            
            {{-- Left side: Search & Rows --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Rows:</span>
                    <select wire:model.live="perPage" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative w-64 md:w-80">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search document, customer, part..."
                           class="w-full text-sm rounded-md border-gray-300 shadow-sm pl-8 pr-8 focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Right side: Column Toggles, Export, Reset --}}
            <div class="flex items-center gap-2">
                {{-- Column visibility dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        Columns
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition.opacity
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-20 p-2 space-y-1">
                        @foreach ($showCol as $key => $val)
                            <label class="flex items-center gap-2 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded cursor-pointer">
                                <input type="checkbox" wire:model.live="showCol.{{ $key }}" class="rounded text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                <span class="capitalize">{{ str_replace('_', ' ', $key) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button wire:click="exportCsv" wire:loading.attr="disabled"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </button>

                <button wire:click="clearFilters"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    {{-- Sortable Header Row --}}
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-14">#</th>

                        @if ($showCol['document_number'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                                wire:click="sortBy('document_number')">
                                <div class="flex items-center gap-1">
                                    Document No.
                                    @if ($sortField === 'document_number')
                                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif

                        @if ($showCol['inspection_date'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                                wire:click="sortBy('inspection_date')">
                                <div class="flex items-center gap-1">
                                    Date
                                    @if ($sortField === 'inspection_date')
                                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif

                        @if ($showCol['shift'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                                wire:click="sortBy('shift')">
                                <div class="flex items-center gap-1">
                                    Shift
                                    @if ($sortField === 'shift')
                                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif

                        @if ($showCol['customer'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                                wire:click="sortBy('customer')">
                                <div class="flex items-center gap-1">
                                    Customer
                                    @if ($sortField === 'customer')
                                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif

                        @if ($showCol['part_number'])
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-indigo-600"
                                wire:click="sortBy('part_number')">
                                <div class="flex items-center gap-1">
                                    Part Number
                                    @if ($sortField === 'part_number')
                                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif

                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>

                    {{-- Column Filter Inputs Row --}}
                    <tr class="bg-gray-100 border-t border-gray-200">
                        <td class="px-4 py-2"></td>

                        @if ($showCol['document_number'])
                            <td class="px-4 py-2">
                                <input type="text" wire:model.live.debounce.400ms="filters.document_number" placeholder="Doc no..."
                                       class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                        @endif

                        @if ($showCol['inspection_date'])
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-1">
                                    <input type="date" wire:model.live="filters.date_from" title="From"
                                           class="text-xs rounded border-gray-300 py-1 px-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                    <span class="text-gray-400 text-xs">-</span>
                                    <input type="date" wire:model.live="filters.date_to" title="To"
                                           class="text-xs rounded border-gray-300 py-1 px-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </td>
                        @endif

                        @if ($showCol['shift'])
                            <td class="px-4 py-2">
                                <select wire:model.live="filters.shift" class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All</option>
                                    @foreach ($shiftOptions as $opt)
                                        <option value="{{ $opt }}">Shift {{ $opt }}</option>
                                    @endforeach
                                </select>
                            </td>
                        @endif

                        @if ($showCol['customer'])
                            <td class="px-4 py-2">
                                <input type="text" wire:model.live.debounce.400ms="filters.customer" placeholder="Customer..."
                                       class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                        @endif

                        @if ($showCol['part_number'])
                            <td class="px-4 py-2">
                                <input type="text" wire:model.live.debounce.400ms="filters.part_number" placeholder="Part no..."
                                       class="w-full text-xs rounded border-gray-300 py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </td>
                        @endif

                        <td class="px-4 py-2"></td>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white" wire:loading.class="opacity-50">
                    @if (!$ready)
                        {{-- Skeleton loading state --}}
                        @for ($i = 0; $i < min($perPage, 8); $i++)
                            <tr class="animate-pulse">
                                <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-6"></div></td>
                                @if ($showCol['document_number'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                                @endif
                                @if ($showCol['inspection_date'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                                @endif
                                @if ($showCol['shift'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-12"></div></td>
                                @endif
                                @if ($showCol['customer'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-36"></div></td>
                                @endif
                                @if ($showCol['part_number'])
                                    <td class="px-4 py-3"><div class="h-4 bg-gray-200 rounded w-28"></div></td>
                                @endif
                                <td class="px-4 py-3 text-right"><div class="h-6 bg-gray-200 rounded w-14 ml-auto"></div></td>
                            </tr>
                        @endfor
                    @else
                        @forelse ($reports as $r)
                            <tr class="hover:bg-gray-50 transition" wire:key="report-{{ $r->id }}">
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $reports->firstItem() + $loop->index }}</td>

                                @if ($showCol['document_number'])
                                    <td class="px-4 py-3 text-xs font-mono font-medium text-indigo-600">
                                        <a href="{{ route('inspection-reports.show', $r->id) }}" wire:navigate class="hover:underline">
                                            {{ $r->document_number }}
                                        </a>
                                    </td>
                                @endif

                                @if ($showCol['inspection_date'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        {{ \Illuminate\Support\Carbon::parse($r->inspection_date)->format('Y-m-d') }}
                                    </td>
                                @endif

                                @if ($showCol['shift'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Shift {{ $r->shift }}
                                        </span>
                                    </td>
                                @endif

                                @if ($showCol['customer'])
                                    <td class="px-4 py-3 text-xs text-gray-900 font-medium">
                                        {{ $r->customer }}
                                    </td>
                                @endif

                                @if ($showCol['part_number'])
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        <div>{{ $r->part_number }}</div>
                                        @if ($r->part_name)
                                            <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $r->part_name }}</div>
                                        @endif
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-right text-xs">
                                    <a href="{{ route('inspection-reports.show', $r->id) }}" wire:navigate
                                       class="inline-flex items-center px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold rounded text-xs transition">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    No inspection reports found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        @if ($ready && $reports->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

</div>
