<div class="bg-slate-900/5 border-2 border-slate-300 rounded-3xl p-5 md:p-7 space-y-6 shadow-sm relative overflow-hidden backdrop-blur-xs">
    
    <!-- Top Bar: Level Selector & Floor Info -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/90 p-4 rounded-2xl border border-gray-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <span class="text-xl">🗺️</span>
            <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight">
                    Denah Real-Time Gudang Utama B (GUB)
                </h3>
                <p class="text-[11px] text-gray-500 font-semibold">
                    Posisi rak disesuaikan 100% dengan denah fisik. Titik acuan terdekat: <strong class="text-rose-600">Panah Merah (Exit)</strong> di kanan bawah.
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

    <!-- Layout Canvas Grid -->
    <div class="space-y-4">
        
        <!-- Top Auxiliary Row: Biohazard & Empty North Area -->
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6 flex items-center">
                <span class="text-[10px] font-extrabold text-emerald-800 uppercase tracking-widest bg-emerald-100/80 px-3 py-1 rounded-lg border border-emerald-300">
                    🟢 ZONA BLOK KIRI
                </span>
            </div>
            
            <div class="col-span-12 md:col-span-6 flex justify-between items-center bg-orange-100/80 border border-orange-300 rounded-xl px-4 py-2">
                <div class="flex items-center gap-2">
                    <span class="text-xl">☣️</span>
                    <div>
                        <span class="text-[11px] font-black text-orange-950 uppercase tracking-wide">Area Khusus B3 / Biohazard</span>
                        <span class="text-[9px] text-orange-700 block">Sesuai layout denah fisik</span>
                    </div>
                </div>
                <span class="text-[10px] font-extrabold text-blue-800 uppercase tracking-widest bg-blue-100 px-3 py-1 rounded-lg border border-blue-300">
                    🔵 ZONA BLOK KANAN
                </span>
            </div>
        </div>

        <!-- Main Floor: Blok Kiri, Lorong Tengah, Blok Kanan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
            
            <!-- BLOK KIRI (Area Hijau) -->
            <div class="lg:col-span-5 bg-emerald-500/10 border-2 border-emerald-400/40 rounded-2xl p-4 space-y-4 shadow-2xs">
                
                <!-- Row Top: F31 & F30 -->
                <div class="space-y-1 bg-white/40 p-2 rounded-xl border border-emerald-200/60">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F31'),
                        'code' => 'F31',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F30'),
                        'code' => 'F30',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>

                <!-- Aisle Gap -->
                <div class="h-3 bg-slate-300/40 rounded-full border border-dashed border-slate-300 flex items-center justify-center">
                    <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Lorong Jalan Forklift</span>
                </div>

                <!-- Row Mid: F29 & F28 -->
                <div class="space-y-1 bg-white/40 p-2 rounded-xl border border-emerald-200/60">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F29'),
                        'code' => 'F29',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F28'),
                        'code' => 'F28',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>

                <!-- Aisle Gap -->
                <div class="h-3 bg-slate-300/40 rounded-full border border-dashed border-slate-300 flex items-center justify-center">
                    <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Lorong Jalan Forklift</span>
                </div>

                <!-- Row Lower: F27 & F26 -->
                <div class="space-y-1 bg-white/40 p-2 rounded-xl border border-emerald-200/60">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F27'),
                        'code' => 'F27',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F26'),
                        'code' => 'F26',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>

                <!-- Aisle Gap -->
                <div class="h-3 bg-slate-300/40 rounded-full border border-dashed border-slate-300 flex items-center justify-center">
                    <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Lorong Jalan Forklift</span>
                </div>

                <!-- Row Bottom: F32 (Single Row) -->
                <div class="bg-white/50 p-2 rounded-xl border border-emerald-200/60">
                    @include('livewire.wms.partials.rack-strip', [
                        'rack' => $racksByCode->get('F32'),
                        'code' => 'F32',
                        'rackRanks' => $rackRanks,
                        'activeLevel' => $activeLevel,
                        'matchingPositionIds' => $matchingPositionIds,
                        'filterCustomer' => $filterCustomer,
                        'searchItem' => $searchItem,
                    ])
                </div>
            </div>

            <!-- LORONG TENGAH (Center Forklift Highway) -->
            <div class="lg:col-span-2 bg-slate-200/90 border-x-2 border-dashed border-slate-300 rounded-2xl p-3 flex flex-col justify-between items-center text-center shadow-inner min-h-[380px]">
                <div class="space-y-1 pt-2">
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest block">LORONG TENGAH</span>
                    <span class="text-[8px] text-slate-400 font-bold block">Aisle Jalur Forklift</span>
                </div>

                <div class="space-y-6 text-slate-400 font-black text-xs">
                    <div class="flex flex-col items-center">
                        <span>▼</span>
                        <span class="text-[8px] tracking-widest">JALUR 1 ARAH</span>
                        <span>▼</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span>▼</span>
                        <span class="text-[8px] tracking-widest">KE PINTU EXIT</span>
                        <span>▼</span>
                    </div>
                </div>

                <div class="pb-2">
                    <span class="text-[8px] font-extrabold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full border border-blue-200">
                        Akses Cepat
                    </span>
                </div>
            </div>

            <!-- BLOK KANAN (Area Biru & Area Bawah) -->
            <div class="lg:col-span-5 space-y-4">
                
                <!-- Sub-zone: Area Biru (R01 - R05) -->
                <div class="bg-blue-500/10 border-2 border-blue-400/40 rounded-2xl p-4 space-y-3 shadow-2xs">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-blue-900 uppercase tracking-wider">
                            Area Rak R (Zona Biru)
                        </span>
                        <span class="text-[9px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">
                            R01 - R05
                        </span>
                    </div>

                    <!-- Single Top: R05 -->
                    <div class="bg-white/40 p-2 rounded-xl border border-blue-200/60">
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('R05'),
                            'code' => 'R05',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                    </div>

                    <!-- Pair Mid: R04 & R03 -->
                    <div class="space-y-1 bg-white/40 p-2 rounded-xl border border-blue-200/60">
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('R04'),
                            'code' => 'R04',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('R03'),
                            'code' => 'R03',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                    </div>

                    <!-- Pair Lower: R02 & R01 -->
                    <div class="space-y-1 bg-white/40 p-2 rounded-xl border border-blue-200/60">
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('R02'),
                            'code' => 'R02',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('R01'),
                            'code' => 'R01',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                    </div>
                </div>

                <!-- Sub-zone: Area F23 & F24 (Dekat Exit) -->
                <div class="bg-white/80 border-2 border-slate-200 rounded-2xl p-4 space-y-3 shadow-2xs">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-800 uppercase tracking-wider">
                            Area Rak F Kanan (Tingkat 2 ke Exit)
                        </span>
                        <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">
                            Prioritas Tinggi ⚡
                        </span>
                    </div>

                    <div class="space-y-1 bg-slate-50 p-2 rounded-xl border border-slate-200">
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('F23'),
                            'code' => 'F23',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('F24'),
                            'code' => 'F24',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                    </div>
                </div>

                <!-- Sub-zone: Rak F25 (PALING DEKAT KE EXIT) -->
                <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-100 border-2 border-emerald-400 rounded-2xl p-4 shadow-md relative overflow-hidden ring-2 ring-emerald-400/50">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🏆</span>
                            <div>
                                <span class="text-xs font-black text-emerald-950 uppercase tracking-tight">
                                    RAK F25 — PALING DEKAT DENGAN EXIT
                                </span>
                                <span class="text-[9px] text-emerald-700 font-bold block">
                                    Prioritas Pengambilan #1 (Langsung ke Pintu Keluar)
                                </span>
                            </div>
                        </div>
                        <span class="text-[10px] font-black text-white bg-emerald-600 px-2.5 py-1 rounded-lg shadow-xs animate-pulse">
                            RANK #1
                        </span>
                    </div>

                    <div class="bg-white p-2 rounded-xl border border-emerald-300">
                        @include('livewire.wms.partials.rack-strip', [
                            'rack' => $racksByCode->get('F25'),
                            'code' => 'F25',
                            'rackRanks' => $rackRanks,
                            'activeLevel' => $activeLevel,
                            'matchingPositionIds' => $matchingPositionIds,
                            'filterCustomer' => $filterCustomer,
                            'searchItem' => $searchItem,
                        ])
                    </div>
                </div>

            </div>

        </div>

        <!-- Bottom Row: LORONG UTAMA PENGELUARAN & PANAH EXIT GATE -->
        <div class="bg-slate-800 text-white rounded-2xl p-4 md:p-5 shadow-lg border-2 border-slate-700 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- Road Asphalt Strip -->
            <div class="flex items-center gap-4 flex-1">
                <div class="w-10 h-10 rounded-xl bg-slate-700 border border-slate-600 flex items-center justify-center text-xl shrink-0">
                    🚚
                </div>
                <div>
                    <h4 class="text-xs md:text-sm font-black tracking-wide uppercase text-white flex items-center gap-2">
                        <span>LORONG UTAMA PENGAMBILAN & PENGELUARAN BARANG</span>
                        <span class="text-[9px] font-bold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/30">
                            MAIN ROAD
                        </span>
                    </h4>
                    <p class="text-[11px] text-slate-300 font-medium mt-0.5">
                        Alur rute pengambilan barang dari lorong atas diarahkan menuju jalur ini lalu keluar melalui pintu exit di kanan.
                    </p>
                </div>
            </div>

            <!-- Direction Arrows -->
            <div class="hidden md:flex items-center gap-2 text-slate-400 font-black text-lg tracking-widest select-none">
                <span>➔</span><span>➔</span><span>➔</span><span>➔</span>
            </div>

            <!-- THE RED EXIT GATE (PANAH MERAH DARI GAMBAR USER) -->
            <div class="bg-rose-600 hover:bg-rose-700 text-white rounded-2xl p-3 px-5 border-2 border-rose-300 shadow-xl flex items-center gap-4 shrink-0 transition-all ring-4 ring-rose-500/30">
                <div class="text-center">
                    <span class="text-[10px] font-black uppercase tracking-widest text-rose-200 block">
                        PATOKAN JARAK TERDEKAT
                    </span>
                    <span class="text-base font-black uppercase tracking-tight block">
                        🔴 PINTU KELUAR (EXIT GUB)
                    </span>
                    <span class="text-[9px] text-rose-100 font-bold block mt-0.5">
                        Titik Nol Jarak Manhattan
                    </span>
                </div>

                <!-- Animated Bouncing Red Arrow -->
                <div class="w-10 h-10 rounded-xl bg-white text-rose-600 flex items-center justify-center font-black text-2xl shadow-md animate-bounce shrink-0">
                    ↓
                </div>
            </div>

        </div>

    </div>

</div>
