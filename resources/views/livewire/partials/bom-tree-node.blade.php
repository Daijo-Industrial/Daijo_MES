@php
    $hasChildren = !empty($node['children']);
    $isWip = $node['is_wip'];
    $category = $node['category'] ?? [
        'label' => 'Material',
        'badge' => 'bg-gray-100 text-gray-800 border-gray-200',
        'icon'  => '📦',
        'color' => 'gray'
    ];
@endphp

<div x-data="{ expanded: true }" 
     @expand-tree.window="expanded = true" 
     @collapse-tree.window="expanded = false" 
     class="relative group/node">
    <!-- Node Card -->
    <div class="relative flex items-center gap-3 p-3.5 rounded-2xl border transition-all duration-200
        {{ $isWip 
            ? 'bg-gradient-to-r from-amber-50/80 via-white to-amber-50/30 border-amber-200 shadow-2xs hover:border-amber-400 hover:shadow-xs' 
            : 'bg-white border-gray-200 shadow-2xs hover:border-blue-300 hover:shadow-xs' }}">
        
        <!-- Toggle Chevron for WIPs with Children -->
        @if($hasChildren)
            <button @click="expanded = !expanded" 
                    type="button"
                    class="w-7 h-7 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 flex items-center justify-center transition-transform shrink-0 cursor-pointer shadow-2xs"
                    :title="expanded ? 'Tutup ranting komponen ini' : 'Buka ranting komponen ini'">
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        @else
            <div class="w-7 h-7 rounded-xl bg-gray-50 flex items-center justify-center text-sm shrink-0 border border-gray-100">
                {{ $category['icon'] }}
            </div>
        @endif

        <!-- Level Indicator Tag -->
        <span class="px-2 py-0.5 rounded-md font-mono font-black text-[10px] uppercase shrink-0 border
            {{ $node['level'] === 1 ? 'bg-blue-100 text-blue-800 border-blue-200' : ($node['level'] === 2 ? 'bg-indigo-100 text-indigo-800 border-indigo-200' : 'bg-purple-100 text-purple-800 border-purple-200') }}">
            LVL {{ $node['level'] }}
        </span>

        <!-- Component Details -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-black text-xs text-gray-900 tracking-tight">
                    {{ $node['component_item'] }}
                </span>
                
                @if(!$isWip)
                    <!-- PE Editable Material Type Badge -->
                    <div x-data="{ openTypeDropdown: false }" class="relative inline-block">
                        <button type="button" 
                                @click.stop="openTypeDropdown = !openTypeDropdown" 
                                class="px-2 py-0.5 text-[9px] font-black rounded-md border flex items-center gap-1 cursor-pointer transition hover:opacity-85 shadow-2xs {{ $category['badge'] }}"
                                title="Klik untuk ubah tipe material ini (Khusus PE / Engineering)">
                            <span>{{ $category['icon'] }}</span>
                            <span>{{ $category['label'] }}</span>
                            <span class="text-[8px] opacity-70 ml-0.5">▼</span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="openTypeDropdown" 
                             @click.outside="openTypeDropdown = false" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display: none;"
                             class="absolute left-0 top-full mt-1.5 z-50 bg-white rounded-xl shadow-2xl border border-gray-200 p-1.5 min-w-[200px] text-xs space-y-1">
                            <div class="px-2 py-1 text-[10px] font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 flex items-center justify-between">
                                <span>Ubah Tipe Material (PE)</span>
                                <span class="text-gray-300 text-[9px] font-mono">{{ $node['component_item'] }}</span>
                            </div>
                            
                            <button type="button" 
                                    wire:click="updateMaterialType('{{ $node['component_item'] }}', 'RAW_MATERIAL')"
                                    @click="openTypeDropdown = false"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 font-bold text-slate-800 text-[11px] cursor-pointer {{ ($node['category']['type'] ?? '') === 'RAW_MATERIAL' ? 'bg-slate-100 font-black' : '' }}">
                                <span>🧩</span>
                                <span>Material / Part</span>
                            </button>

                            <button type="button" 
                                    wire:click="updateMaterialType('{{ $node['component_item'] }}', 'RESIN')"
                                    @click="openTypeDropdown = false"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-purple-50 flex items-center gap-2 font-bold text-purple-900 text-[11px] cursor-pointer {{ ($node['category']['type'] ?? '') === 'RESIN' ? 'bg-purple-100 font-black' : '' }}">
                                <span>🧬</span>
                                <span>Biji Plastik / Resin</span>
                            </button>

                            <button type="button" 
                                    wire:click="updateMaterialType('{{ $node['component_item'] }}', 'CHEMICAL')"
                                    @click="openTypeDropdown = false"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-cyan-50 flex items-center gap-2 font-bold text-cyan-900 text-[11px] cursor-pointer {{ ($node['category']['type'] ?? '') === 'CHEMICAL' ? 'bg-cyan-100 font-black' : '' }}">
                                <span>🧪</span>
                                <span>Cat / Chemical</span>
                            </button>

                            <button type="button" 
                                    wire:click="updateMaterialType('{{ $node['component_item'] }}', 'HARDWARE')"
                                    @click="openTypeDropdown = false"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-blue-50 flex items-center gap-2 font-bold text-blue-900 text-[11px] cursor-pointer {{ ($node['category']['type'] ?? '') === 'HARDWARE' ? 'bg-blue-100 font-black' : '' }}">
                                <span>🔩</span>
                                <span>Hardware / Fastener</span>
                            </button>

                            <button type="button" 
                                    wire:click="updateMaterialType('{{ $node['component_item'] }}', 'PACKAGING')"
                                    @click="openTypeDropdown = false"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-emerald-50 flex items-center gap-2 font-bold text-emerald-900 text-[11px] cursor-pointer {{ ($node['category']['type'] ?? '') === 'PACKAGING' ? 'bg-emerald-100 font-black' : '' }}">
                                <span>📦</span>
                                <span>Packaging / Kemasan</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- WIP Badge (Non-material, fixed type) -->
                    <span class="px-2 py-0.5 text-[9px] font-black rounded-md border flex items-center gap-1 {{ $category['badge'] }}">
                        <span>{{ $category['icon'] }}</span>
                        <span>{{ $category['label'] }}</span>
                    </span>
                @endif

                @if($hasChildren)
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                        {{ count($node['children']) }} Sub-Material
                    </span>
                @endif
            </div>

            <div class="text-[11px] text-gray-500 font-medium truncate mt-0.5" title="{{ $node['component_description'] }}">
                {{ $node['component_description'] ?: '-' }}
            </div>
        </div>

        <!-- Calculated Quantities -->
        <div class="text-right shrink-0 pl-2">
            <div class="text-sm font-mono font-black text-gray-900 flex items-baseline justify-end gap-1">
                <span>{{ rtrim(rtrim(number_format($node['total_qty'], 6, '.', ''), '0'), '.') }}</span>
                <span class="text-[11px] font-sans font-bold text-blue-600 uppercase">{{ $node['uom'] }}</span>
            </div>
            <div class="text-[10px] text-gray-400 font-mono">
                Konsumsi: {{ rtrim(rtrim(number_format($node['unit_qty'], 6, '.', ''), '0'), '.') }} / pcs
            </div>
        </div>
    </div>

    <!-- Recursive Sub-Components (Children Tree) with Branch Lines -->
    @if($hasChildren)
        <div x-show="expanded" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="mt-2.5 ml-6 pl-4 border-l-2 border-dashed border-amber-300 space-y-2.5 relative">
            @foreach($node['children'] as $child)
                @include('livewire.partials.bom-tree-node', ['node' => $child])
            @endforeach
        </div>
    @endif
</div>
