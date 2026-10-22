<div class="bg-slate-900/5 border-2 border-slate-300 rounded-3xl p-5 md:p-7 space-y-6 shadow-sm relative overflow-hidden backdrop-blur-xs">
    
    <!-- Top Bar: Level Selector & Floor Info -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/90 p-4 rounded-2xl border border-gray-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <span class="text-xl">🏭</span>
            <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight flex items-center gap-2">
                    <span>Denah Real-Time Gudang 06 (J06)</span>
                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full border border-emerald-300">
                        Logistic (Hijau) & Store (Biru)
                    </span>
                </h3>
                <p class="text-[11px] text-gray-500 font-semibold">
                    Posisi rak disesuaikan 100% dengan denah fisik. Titik acuan terdekat: <strong class="text-emerald-600">Panah Hijau (Exit)</strong> di <strong class="text-emerald-600">Tengah Bawah</strong>.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="openEditWarehouseModal" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-xs cursor-pointer" title="Upload Gambar Layout & Atur Pintu Keluar">
                <span>📷 Upload Layout Asli</span>
            </button>

            <!-- Level Selector Pills -->
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl border border-gray-200">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-wider pl-2 pr-1">Tingkat:</span>
                <button wire:click="$set('activeLevel', 1)" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer {{ $activeLevel == 1 ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:text-blue-600' }}">
                    Level 1 (Bawah)
                </button>
                <button wire:click="$set('activeLevel', 2)" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer {{ $activeLevel == 2 ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:text-blue-600' }}">
                    Level 2 (Tengah)
                </button>
                <button wire:click="$set('activeLevel', 3)" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer {{ $activeLevel == 3 ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:text-blue-600' }}">
                    Level 3 (Atas)
                </button>
                <button wire:click="$set('activeLevel', 'ALL')" class="px-2.5 py-1 rounded-lg text-xs font-black transition cursor-pointer {{ $activeLevel === 'ALL' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:text-blue-600' }}">
                    Semua Level
                </button>
            </div>
        </div>
    </div>

    <!-- Main Floor Canvas Grid -->
    <div class="space-y-4">
        
        <!-- Top Horizontal Row: F41 (Logistic) & W10 (Store) -->
        <div class="bg-white/80 p-3 rounded-2xl border border-gray-200 shadow-2xs space-y-2">
            <div class="flex justify-between items-center text-[10px] font-black uppercase text-gray-400 tracking-wider">
                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    🟢 LOGISTIC (Horizontal Atas)
                </span>
                <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                    🔵 STORE (Horizontal Atas)
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- F41 Strip -->
                <div class="bg-emerald-50/70 p-2 rounded-xl border border-emerald-300">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F41'),
                        'code' => 'F41',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>

                <!-- W10 Strip -->
                <div class="bg-blue-50/70 p-2 rounded-xl border border-blue-300">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('W10'),
                        'code' => 'W10',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>
            </div>
        </div>

        <!-- Middle Columns Area: F33 s/d W01 (Vertical Racks) -->
        <div class="bg-white/90 p-4 rounded-2xl border border-gray-200 shadow-2xs space-y-3">
            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-lg border border-emerald-200">
                        🟢 ZONA LOGISTIC (F33 - F40)
                    </span>
                    <span class="text-gray-300 font-bold">&rarr;</span>
                    <span class="text-[10px] font-black text-blue-800 bg-blue-100 px-2.5 py-0.5 rounded-lg border border-blue-200">
                        🔵 ZONA STORE (W09 - W01)
                    </span>
                </div>
                <span class="text-[9px] font-bold text-gray-500">
                    9 Pasang/Kolom Rak Vertikal (Tegak)
                </span>
            </div>

            <!-- Horizontal Grid of Vertical Pairs -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-2.5 overflow-x-auto pb-1 items-start">
                
                <!-- Col 1: F33 (Single) -->
                <div class="bg-emerald-50/80 p-1.5 rounded-xl border border-emerald-300 flex flex-col h-full">
                    <span class="text-[8px] font-black text-emerald-800 text-center uppercase mb-1">Kolom 1</span>
                    @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F33'), 'code' => 'F33', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                </div>

                <!-- Pair 2: F34 & F35 -->
                <div class="bg-emerald-50/80 p-1.5 rounded-xl border border-emerald-300 space-y-1">
                    <span class="text-[8px] font-black text-emerald-800 text-center block uppercase">Kolom 2</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F34'), 'code' => 'F34', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F35'), 'code' => 'F35', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 3: F36 & F37 -->
                <div class="bg-emerald-50/80 p-1.5 rounded-xl border border-emerald-300 space-y-1">
                    <span class="text-[8px] font-black text-emerald-800 text-center block uppercase">Kolom 3</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F36'), 'code' => 'F36', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F37'), 'code' => 'F37', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 4: F38 & F39 -->
                <div class="bg-emerald-50/80 p-1.5 rounded-xl border border-emerald-300 space-y-1">
                    <span class="text-[8px] font-black text-emerald-800 text-center block uppercase">Kolom 4</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F38'), 'code' => 'F38', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F39'), 'code' => 'F39', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 5: Boundary Pair (F40 Green & W09 Blue) -->
                <div class="bg-gradient-to-r from-emerald-50 to-blue-50 p-1.5 rounded-xl border border-slate-300 space-y-1">
                    <span class="text-[8px] font-black text-slate-700 text-center block uppercase">Kolom 5 (Batas)</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F40'), 'code' => 'F40', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W09'), 'code' => 'W09', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 6: W08 & W07 (DIRECTLY ABOVE GREEN EXIT ARROW) -->
                <div class="bg-gradient-to-b from-blue-50 to-emerald-100 p-1.5 rounded-xl border-2 border-emerald-500 shadow-md ring-2 ring-emerald-400/50 space-y-1">
                    <div class="flex items-center justify-between text-[8px] font-black text-emerald-900 px-1">
                        <span>Kolom 6</span>
                        <span class="bg-emerald-600 text-white px-1 rounded text-[7px]">TERDEKAT</span>
                    </div>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W08'), 'code' => 'W08', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W07'), 'code' => 'W07', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 7: W06 & W05 (DIRECTLY ABOVE GREEN EXIT ARROW) -->
                <div class="bg-gradient-to-b from-blue-50 to-emerald-100 p-1.5 rounded-xl border-2 border-emerald-500 shadow-md ring-2 ring-emerald-400/50 space-y-1">
                    <div class="flex items-center justify-between text-[8px] font-black text-emerald-900 px-1">
                        <span>Kolom 7</span>
                        <span class="bg-emerald-600 text-white px-1 rounded text-[7px]">TERDEKAT</span>
                    </div>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W06'), 'code' => 'W06', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W05'), 'code' => 'W05', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 8: W04 & W03 -->
                <div class="bg-blue-50/80 p-1.5 rounded-xl border border-blue-300 space-y-1">
                    <span class="text-[8px] font-black text-blue-800 text-center block uppercase">Kolom 8</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W04'), 'code' => 'W04', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W03'), 'code' => 'W03', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

                <!-- Pair 9: W02 & W01 -->
                <div class="bg-blue-50/80 p-1.5 rounded-xl border border-blue-300 space-y-1">
                    <span class="text-[8px] font-black text-blue-800 text-center block uppercase">Kolom 9</span>
                    <div class="flex gap-1">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W02'), 'code' => 'W02', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('W01'), 'code' => 'W01', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>

            </div>
        </div>

        <!-- Bottom Row: Forklift Staging & THE GREEN EXIT ARROW (PANAH HIJAU EXIT DARI GAMBAR USER) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
            
            <!-- Left: Forklift Dock Area -->
            <div class="md:col-span-4 bg-amber-50 border border-amber-200 rounded-2xl p-3 flex items-center gap-3">
                <span class="text-2xl">🚜</span>
                <div>
                    <span class="text-[10px] font-black text-amber-900 uppercase block">AREA PARKIR FORKLIFT</span>
                    <span class="text-[9px] text-amber-700 block">Jalur Lorong Bawah Gudang 06</span>
                </div>
            </div>

            <!-- Middle: THE GREEN EXIT ARROW (SESUAI GAMBAR CORETAN HIJAU) -->
            <div class="md:col-span-4 bg-emerald-600 text-white rounded-2xl p-4 border-2 border-emerald-300 shadow-xl flex items-center justify-between gap-3 ring-4 ring-emerald-400/40">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-200 block">
                        PATOKAN JARAK TERDEKAT GUDANG 06
                    </span>
                    <h4 class="text-sm font-black uppercase tracking-tight block">
                        🟢 PINTU KELUAR (EXIT G06)
                    </h4>
                    <span class="text-[8px] text-emerald-100 font-bold block mt-0.5">
                        Titik Nol Panah Hijau (Bawah W06 - W08)
                    </span>
                </div>

                <!-- Animated Bouncing Green Arrow -->
                <div class="w-10 h-10 rounded-xl bg-white text-emerald-600 flex items-center justify-center font-black text-2xl shadow-md animate-bounce shrink-0">
                    ↓
                </div>
            </div>

            <!-- Right: LIFT & Printing / Assy Area -->
            <div class="md:col-span-4 bg-slate-100 border border-slate-300 rounded-2xl p-3 flex items-center justify-between text-slate-700">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛗</span>
                    <div>
                        <span class="text-[10px] font-black uppercase block">LIFT & TANGGA</span>
                        <span class="text-[9px] text-slate-500 block">Printing & Assy Area</span>
                    </div>
                </div>
                <span class="text-xl">🚶</span>
            </div>

        </div>

    </div>

</div>
