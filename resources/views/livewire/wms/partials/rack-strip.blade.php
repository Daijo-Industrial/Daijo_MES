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
    } elseif ($rank && $rank <= 7) {
        $rankBadgeClass = 'bg-blue-50 text-blue-800 border-blue-200 font-bold';
    } elseif ($rank && $rank <= 12) {
        $rankBadgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
    } else {
        $rankBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
    }
@endphp

@if($rack)
    <div class="bg-white/95 rounded-xl border border-gray-200 p-2.5 shadow-2xs hover:shadow-sm transition-all">
        <!-- Rack Header Bar -->
        <div class="flex items-center justify-between gap-2 mb-1.5 pb-1 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-gray-900 tracking-tight uppercase font-mono">
                    {{ $rack->rack_code }}
                </span>
                
                @if($rank)
                    <span class="text-[9px] px-1.5 py-0.5 rounded border {{ $rankBadgeClass }} flex items-center gap-1 shadow-3xs" title="Jarak Manhattan: {{ $rack->distance_score }}px ke Exit">
                        @if($rank === 1)
                            <span>🏆 #1 TERDEKAT</span>
                        @else
                            <span>⚡ #{{ $rank }}</span>
                        @endif
                        <span class="text-[8px] opacity-75">({{ $rack->distance_score }}px)</span>
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-[9px] font-bold text-gray-400">
                    {{ count($rack->positions) }} Slots
                </span>
                <button wire:click="openEditRackModal({{ $rack->id }})" class="p-0.5 text-gray-400 hover:text-blue-600 rounded transition" title="Edit Rak">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
            </div>
        </div>

        <!-- Slot Cells Row -->
        @php
            $groupedByLevel = $rack->positions->groupBy('level_no')->sortKeys();
            $levelsToDisplay = ($activeLevel === 'ALL' || !isset($groupedByLevel[$activeLevel]))
                ? $groupedByLevel
                : collect([$activeLevel => $groupedByLevel[$activeLevel]]);
        @endphp

        <div class="space-y-1.5">
            @foreach($levelsToDisplay as $lvlNo => $positions)
                <div class="flex items-center gap-1.5">
                    @if($activeLevel === 'ALL')
                        <span class="text-[8px] font-black text-blue-600 w-6 shrink-0 font-mono">L{{ $lvlNo }}</span>
                    @endif

                    <div class="flex items-center gap-1 flex-1 overflow-x-auto pb-0.5">
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
                                $searchGlowClass = $isMatchedBySearch ? 'ring-3 ring-blue-500 scale-110 border-blue-600 bg-blue-100 shadow-md z-10' : '';
                            @endphp

                            <button wire:click="selectPosition({{ $pos->id }})"
                                    class="min-w-[28px] h-7 px-1 rounded border {{ $statusColor }} {{ $opacityClass }} {{ $searchGlowClass }} flex flex-col justify-center items-center text-center transition cursor-pointer relative group shrink-0"
                                    title="{{ $pos->position_code }} (Level {{ $pos->level_no }}, Slot {{ $pos->slot_no }}) - Status: {{ $pos->status }}">
                                
                                <span class="text-[8px] font-black leading-none font-mono">
                                    S{{ $pos->slot_no }}
                                </span>

                                @if($pos->pallet_forms_count > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex h-1.5 w-1.5">
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
    <div class="bg-gray-100/50 rounded-xl border border-dashed border-gray-300 p-2 text-center text-[10px] text-gray-400 font-bold">
        Rak {{ $code }} (Belum Terdaftar)
    </div>
@endif
