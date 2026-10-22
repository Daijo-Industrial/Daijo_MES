<div class="flex items-center w-full">
    @if ($canSwitch)
        <div class="relative w-full" x-data="{ open: false }">
            <button @click="open = !open" 
                    type="button"
                    class="w-full inline-flex items-center justify-between gap-x-2 rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <div class="flex items-center gap-x-2 truncate">
                    <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span class="truncate">{{ $activePlantLabel }}</span>
                </div>
                <svg class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" 
                 @click.outside="open = false" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 style="display: none;"
                 class="absolute left-0 z-50 mt-1.5 w-full origin-top-left rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                
                <div class="px-3 py-1.5 border-b border-gray-100 text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                    Ganti Plant / Branch
                </div>

                <button wire:click="switchPlant('ALL')" 
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center justify-between {{ $activeBranchId === \App\Services\PlantContextService::ALL_PLANTS ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                    <span>Semua Plant (All Plants)</span>
                    @if ($activeBranchId === \App\Services\PlantContextService::ALL_PLANTS)
                        <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    @endif
                </button>

                @foreach ($branches as $branch)
                    <button wire:click="switchPlant({{ $branch->id }})" 
                            @click="open = false"
                            class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center justify-between {{ $activeBranchId == $branch->id ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                        <span class="flex items-center gap-1.5 truncate">
                            <span class="truncate">{{ $branch->name }}</span>
                            @if ($branch->is_main)
                                <span class="rounded bg-blue-100 px-1 py-0.2 text-[10px] font-semibold text-blue-700 shrink-0">Main</span>
                            @endif
                        </span>
                        @if ($activeBranchId == $branch->id)
                            <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div class="inline-flex items-center gap-x-2 w-full rounded-md bg-gray-50 px-3 py-2 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/10">
            <svg class="h-4 w-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
            <span class="truncate">{{ $activePlantLabel }}</span>
        </div>
    @endif
</div>
