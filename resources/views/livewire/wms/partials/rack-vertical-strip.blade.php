@props([
    'rack' => null,
    'code' => '',
    'rackRanks' => [],
    'activeLevel' => 1,
    'matchingPositionIds' => [],
    'filterCustomer' => '',
    'searchItem' => '',
])

@php
    $rank = $rack ? ($rackRanks[$rack->rack_code] ?? null) : null;
    $rankBadgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
    if ($rank && $rank <= 3) {
        $rankBadgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-300 font-black ring-1 ring-emerald-400';
    } elseif ($rank && $rank <= 8) {
        $rankBadgeClass = 'bg-blue-50 text-blue-800 border-blue-200 font-bold';
    } elseif ($rank && $rank <= 16) {
        $rankBadgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
    } else {
        $rankBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
    }
@endphp

@if($rack)
    <div class="bg-white/95 rounded-xl border border-gray-200 p-2 shadow-2xs hover:shadow-sm transition-all flex flex-col items-center min-w-[70px] flex-1">
        <!-- Rack Header Bar (Vertical style) -->
        <div class="w-full text-center pb-1.5 mb-1.5 border-b border-gray-100 space-y-0.5">
            <span class="text-xs font-black text-gray-900 tracking-tight uppercase font-mono block">
                {{ $rack->rack_code }}
            </span>
            
            @if($rank)
                <span class="text-[8px] px-1 py-0.5 rounded border {{ $rankBadgeClass }} inline-block tracking-tighter" title="Jarak Manhattan: {{ $rack->distance_score }}px ke Exit Kiri Bawah">
                    @if($rank <= 2)
                        🏆 #{{ $rank }}
                    @else
                        ⚡ #{{ $rank }}
                    @endif
                </span>
            @endif
        </div>

        <!-- Vertical Slot Cells -->
        @php
            $groupedByLevel = $rack->positions->groupBy('level_no')->sortKeys();
            $levelsToDisplay = ($activeLevel === 'ALL' || !isset($groupedByLevel[$activeLevel]))
                ? $groupedByLevel
                : collect([$activeLevel => $groupedByLevel[$activeLevel]]);
        @endphp

        <div class="w-full space-y-2">
            @foreach($levelsToDisplay as $lvlNo => $positions)
                <div class="flex flex-col gap-1 items-center">
                    @if($activeLevel === 'ALL')
                        <span class="text-[7px] font-black text-blue-600 font-mono">L{{ $lvlNo }}</span>
                    @endif

                    <div class="w-full flex flex-col gap-1">
                        @foreach($positions->sortBy('slot_no') as $pos)
                            @php
                                $statusColor = 'bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-600';
                                if ($pos->status == 'PARTIAL') $statusColor = 'bg-yellow-100 hover:bg-yellow-200 border-yellow-300 text-yellow-800';
                                if ($pos->status == 'FULL') $statusColor = 'bg-red-100 hover:bg-red-200 border-red-300 text-red-800';

                                $isMatchedBySearch = !empty(trim($searchItem)) && in_array($pos->id, $matchingPositionIds);
                                $isHighlighted = true;
                                if ($filterCustomer && $pos->customer_code !== $filterCustomer) {
                                    $isHighlighted = false;
                                }
                                if (!empty(trim($searchItem)) && !$isMatchedBySearch) {
                                    $isHighlighted = false;
                                }

                                $opacityClass = $isHighlighted ? 'opacity-100' : 'opacity-20 grayscale';
                                $searchGlowClass = $isMatchedBySearch ? 'ring-3 ring-blue-500 scale-105 border-blue-600 bg-blue-100 shadow-md z-10' : '';
                            @endphp

                            <button wire:click="selectPosition({{ $pos->id }})"
                                    class="w-full h-6 rounded border {{ $statusColor }} {{ $opacityClass }} {{ $searchGlowClass }} flex items-center justify-between px-1 text-center transition cursor-pointer relative group"
                                    title="{{ $pos->position_code }} (Level {{ $pos->level_no }}, Slot {{ $pos->slot_no }}) - Status: {{ $pos->status }}">
                                
                                <span class="text-[8px] font-black leading-none font-mono">
                                    S{{ $pos->slot_no }}
                                </span>

                                @if($pos->pallet_forms_count > 0)
                                    <span class="flex h-1.5 w-1.5 shrink-0">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $pos->status == 'FULL' ? 'bg-red-400' : 'bg-yellow-400' }}"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $pos->status == 'FULL' ? 'bg-red-500' : 'bg-yellow-500' }}"></span>
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="bg-gray-100/50 rounded-xl border border-dashed border-gray-300 p-2 text-center text-[10px] text-gray-400 font-bold min-w-[70px]">
        {{ $code }}
    </div>
@endif
