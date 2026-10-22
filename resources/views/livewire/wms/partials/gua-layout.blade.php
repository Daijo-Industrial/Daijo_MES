<div class="bg-slate-900/5 border-2 border-slate-300 rounded-3xl p-5 md:p-7 space-y-6 shadow-sm relative overflow-hidden backdrop-blur-xs">
    
    <!-- Top Bar: Level Selector & Floor Info -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/90 p-4 rounded-2xl border border-gray-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <span class="text-xl">🧭</span>
            <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight flex items-center gap-2">
                    <span>Denah Real-Time Gudang Utama A (GUA)</span>
                    <span class="text-[9px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full border border-emerald-300">
                        Orientasi Rak Vertikal (Tegak)
                    </span>
                </h3>
                <p class="text-[11px] text-gray-500 font-semibold">
                    Posisi rak disesuaikan 100% dengan denah fisik. Titik acuan terdekat: <strong class="text-rose-600">Panah Merah (Exit)</strong> di <strong class="text-rose-600">Kiri Bawah</strong>.
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
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch">
        
        <!-- Left Wing: Toilet, Forklift Area & EXIT GATE -->
        <div class="xl:col-span-3 flex flex-col justify-between gap-4">
            
            <!-- Top: Toilet & Staging -->
            <div class="bg-amber-100/70 border border-amber-300 rounded-2xl p-3 flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🚻</span>
                    <span class="text-xs font-black text-amber-950 uppercase">TOILET & AKSES UTARA</span>
                </div>
                <span class="text-[8px] font-bold text-amber-800 bg-amber-200 px-2 py-0.5 rounded">Zona 1</span>
            </div>

            <!-- Middle: Forklift Parking & Blue Zone -->
            <div class="bg-blue-500/10 border-2 border-blue-400/30 rounded-2xl p-4 space-y-3 flex-1 flex flex-col justify-between">
                <div class="space-y-1">
                    <span class="text-[10px] font-black text-blue-900 uppercase tracking-wider block">AREA PARKIR FORKLIFT</span>
                    <span class="text-[9px] text-blue-700 font-semibold block">Jalur Masuk/Keluar Aisle Kiri</span>
                </div>

                <div class="flex flex-col items-center justify-center py-4 bg-white/70 rounded-xl border border-blue-200">
                    <span class="text-3xl animate-pulse">🚜</span>
                    <span class="text-[9px] font-black text-slate-600 uppercase mt-1">FORKLIFT UNIT #1</span>
                </div>

                <div class="p-2.5 bg-blue-100/60 rounded-xl border border-blue-200 flex items-center gap-2">
                    <span class="text-xl">🚜</span>
                    <div>
                        <span class="text-[10px] font-black text-blue-950 uppercase block">FORKLIFT DOCK #2</span>
                        <span class="text-[8px] text-blue-800 font-semibold block">Pos Siap Kirim ke Exit</span>
                    </div>
                </div>
            </div>

            <!-- THE RED EXIT GATE (PANAH MERAH DI KIRI BAWAH) -->
            <div class="bg-rose-600 hover:bg-rose-700 text-white rounded-2xl p-4 border-2 border-rose-300 shadow-xl flex items-center justify-between gap-3 transition-all ring-4 ring-rose-500/30">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-rose-200 block">
                        PATOKAN JARAK TERDEKAT GUA
                    </span>
                    <h4 class="text-sm font-black uppercase tracking-tight block">
                        🔴 PINTU KELUAR (EXIT GUA)
                    </h4>
                    <span class="text-[8px] text-rose-100 font-bold block mt-0.5">
                        Titik Nol Kiri Bawah
                    </span>
                </div>

                <!-- Bouncing Red Arrow -->
                <div class="w-10 h-10 rounded-xl bg-white text-rose-600 flex items-center justify-center font-black text-2xl shadow-md animate-bounce shrink-0">
                    ↓
                </div>
            </div>

        </div>

        <!-- Right Wing: Main Storage Blocks (Upper & Lower) -->
        <div class="xl:col-span-9 space-y-4">
            
            <!-- BLOK ATAS: RAK F11 s/d F22 (Orientasi Vertikal) -->
            <div class="bg-emerald-500/10 border-2 border-emerald-400/30 rounded-2xl p-4 space-y-3 shadow-2xs">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-emerald-950 uppercase tracking-wider bg-emerald-200/80 px-2.5 py-0.5 rounded-lg border border-emerald-300">
                            BLOK ATAS (F11 - F22)
                        </span>
                        <span class="text-[9px] text-emerald-700 font-semibold">
                            Tegak Vertikal &bull; 6 Pasang Kolom
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-extrabold text-rose-700 bg-rose-100 px-2 py-0.5 rounded border border-rose-200">
                            🟥 R06 (Red Area)
                        </span>
                        <span class="text-[9px] font-bold text-gray-500">🧭 Utara</span>
                    </div>
                </div>

                <!-- Horizontal Flow of Vertical Pairs -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 overflow-x-auto pb-1">
                    <!-- Pair 1: F11 & F12 (Closest in Upper block) -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-emerald-200">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F11'), 'code' => 'F11', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F12'), 'code' => 'F12', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 2: F13 & F14 -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-emerald-200">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F13'), 'code' => 'F13', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F14'), 'code' => 'F14', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 3: F15 & F16 -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-emerald-200">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F15'), 'code' => 'F15', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F16'), 'code' => 'F16', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 4: F17 & F18 -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-emerald-200">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F17'), 'code' => 'F17', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F18'), 'code' => 'F18', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 5: F19 & F20 -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-emerald-200">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F19'), 'code' => 'F19', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F20'), 'code' => 'F20', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 6: F21 & F22 (Paling Kanan / Farthest) -->
                    <div class="flex gap-1 bg-white/50 p-1.5 rounded-xl border border-amber-300 ring-1 ring-amber-300">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F21'), 'code' => 'F21', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F22'), 'code' => 'F22', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>
            </div>

            <!-- LORONG TENGAH (Horizontal Crossway Road) -->
            <div class="bg-slate-300/80 rounded-xl px-4 py-2 border border-slate-300 flex items-center justify-between text-slate-500 font-black text-[9px] uppercase tracking-wider">
                <div class="flex items-center gap-2">
                    <span>⬅️</span>
                    <span>JALUR LORONG TENGAH (AKSES FORKLIFT KE EXIT KIRI)</span>
                </div>
                <div class="flex items-center gap-1.5 text-slate-600 bg-white/80 px-2 py-0.5 rounded border border-slate-300">
                    <span>🚶</span>
                    <span>TANGGA KANAN</span>
                </div>
            </div>

            <!-- BLOK BAWAH: RAK F01 s/d F10 (PALING DEKAT DENGAN EXIT) -->
            <div class="bg-gradient-to-r from-emerald-100/80 via-emerald-50 to-teal-50 border-2 border-emerald-400 rounded-2xl p-4 space-y-3 shadow-md">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🏆</span>
                        <div>
                            <span class="text-xs font-black text-emerald-950 uppercase tracking-tight">
                                BLOK BAWAH (F01 - F10) — PRIORITAS UTAMA
                            </span>
                            <span class="text-[9px] text-emerald-700 font-bold block">
                                Bersebelahan langsung dengan Pintu Keluar di sisi kiri
                            </span>
                        </div>
                    </div>
                    <span class="text-[9px] font-black text-white bg-emerald-600 px-2.5 py-1 rounded-lg shadow-xs">
                        ZONA TERDEKAT #1
                    </span>
                </div>

                <!-- Horizontal Flow of Vertical Pairs -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 overflow-x-auto pb-1">
                    <!-- Pair 1: F01 & F02 (PALING DEKAT KE EXIT) -->
                    <div class="flex gap-1 bg-white p-1.5 rounded-xl border-2 border-emerald-500 shadow-xs ring-2 ring-emerald-400/40">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F01'), 'code' => 'F01', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F02'), 'code' => 'F02', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 2: F03 & F04 -->
                    <div class="flex gap-1 bg-white/70 p-1.5 rounded-xl border border-emerald-300">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F03'), 'code' => 'F03', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F04'), 'code' => 'F04', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 3: F05 & F06 -->
                    <div class="flex gap-1 bg-white/70 p-1.5 rounded-xl border border-emerald-300">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F05'), 'code' => 'F05', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F06'), 'code' => 'F06', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 4: F07 & F08 -->
                    <div class="flex gap-1 bg-white/70 p-1.5 rounded-xl border border-emerald-300">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F07'), 'code' => 'F07', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F08'), 'code' => 'F08', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>

                    <!-- Pair 5: F09 & F10 -->
                    <div class="flex gap-1 bg-white/70 p-1.5 rounded-xl border border-emerald-300">
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F09'), 'code' => 'F09', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                        @include('livewire.wms.partials.rack-vertical-strip', ['rack' => $racksByCode->get('F10'), 'code' => 'F10', 'rackRanks' => $rackRanks, 'activeLevel' => $activeLevel, 'matchingPositionIds' => $matchingPositionIds, 'filterCustomer' => $filterCustomer, 'searchItem' => $searchItem])
                    </div>
                </div>
            </div>

            <!-- Bottom Highway Strip -->
            <div class="bg-slate-700 text-slate-200 rounded-xl px-4 py-2.5 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-sm">🚚</span>
                    <span class="font-bold text-[10px] uppercase tracking-wider">JALUR LORONG UTAMA BAWAH (MENUJU PINTU KELUAR KIRI)</span>
                </div>
                <div class="flex items-center gap-2 font-mono font-black text-amber-400">
                    <span>⬅️</span><span>⬅️</span><span>⬅️</span>
                    <span class="text-[9px] uppercase">Arah Exit</span>
                </div>
            </div>

        </div>

    </div>

</div>
