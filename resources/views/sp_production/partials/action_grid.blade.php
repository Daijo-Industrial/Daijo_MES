        {{-- ULTRA-KISS 2X2 HERO GRID (Zero Footer Bar) --}}
        @if($session->status === 'running')
            <div class="flex-1 bg-gray-100 p-3 sm:p-5 flex flex-col justify-between select-none overflow-hidden min-h-0">
                
                {{-- 2-Column Hero Action Cards Grid: Left (Direct Operations) vs Right (Intake & Defect Logs) --}}
                <div class="grid grid-cols-2 gap-3 sm:gap-4 flex-1 min-h-0">
                    
                    {{-- [TOP-LEFT] Card 1: Good Output (Direct Submit Action + Quick Batch Selector) --}}
                    <div class="rounded-3xl p-4 sm:p-5 bg-emerald-600 border-4 border-emerald-400 text-white shadow-xl flex flex-col justify-between items-center text-center transition-all relative overflow-hidden min-h-0"
                         :class="quickFlash && 'ring-8 ring-emerald-300 scale-105 bg-emerald-500'">
                        
                        {{-- Top Integrated Quick Batch Size Selector --}}
                        <div class="w-full flex items-center justify-between gap-1 bg-emerald-700/60 p-1.5 rounded-2xl backdrop-blur-xs flex-shrink-0 z-10" @click.stop>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-200 ml-1 hidden sm:inline">Batch:</span>
                            <div class="flex items-center gap-1 flex-1 justify-around">
                                <template x-for="size in [1, 5, 10, 50, 100]" :key="size">
                                    <button type="button" @click="setBatchSize(size)"
                                            :class="batchSize === size && !isCustomBatch ? 'bg-white text-emerald-900 font-bold shadow-md scale-105' : 'bg-emerald-800/80 hover:bg-emerald-700 text-white font-bold'"
                                            class="px-2 py-1 rounded-xl transition text-xs sm:text-sm flex-1 text-center cursor-pointer border border-emerald-500/40"
                                            x-text="'+' + size"></button>
                                </template>
                                {{-- Inline Configurable Custom Batch Pill (Auto-save) --}}
                                <div class="flex items-center px-1.5 py-0.5 rounded-xl border transition flex-1 justify-center"
                                     :class="isCustomBatch ? 'bg-amber-400 text-emerald-950 font-bold shadow-md border-amber-300' : 'bg-emerald-800/80 text-white border-emerald-500/40 focus-within:bg-white focus-within:text-emerald-950'">
                                    <span class="text-xs font-semibold mr-0.5">+</span>
                                    <input type="number" min="1" max="9999"
                                           :value="customBatchSize"
                                           placeholder="Custom"
                                           @focus="if (customBatchSize) setBatchSize(customBatchSize); $event.target.select()"
                                           @input="if ($event.target.value > 0) applyCustomBatchSize($event.target.value)"
                                           class="w-10 sm:w-12 text-center text-xs sm:text-sm font-semibold bg-transparent border-0 p-0 focus:ring-0 text-inherit placeholder:text-emerald-200 placeholder:text-[10px]">
                                </div>
                            </div>
                        </div>

                        {{-- Main Tap Target --}}
                        <button type="button" @click="quickAddGood()"
                                class="w-full flex-1 flex flex-col items-center justify-center cursor-pointer active:scale-95 transition-transform py-2">
                            <h1 class="text-3xl sm:text-5xl font-bold tracking-tight drop-shadow-md uppercase" x-text="quickFlash ? 'SAVED!' : ('+' + batchSize + ' GOOD')"></h1>
                            <p class="text-xs sm:text-sm font-bold text-emerald-100 mt-1" x-text="'Tap to log ' + batchSize + ' OK piece(s)'"></p>
                        </button>
                    </div>

                    {{-- [TOP-RIGHT] Card 2: Receive Input (WIP / Reworkable Intake) --}}
                    <div class="rounded-3xl p-4 sm:p-5 bg-blue-600 border-4 border-blue-400 text-white shadow-xl flex flex-col justify-between items-center text-center transition-all relative overflow-hidden min-h-0">
                        {{-- Top Type Selector Bar --}}
                        <div class="w-full flex items-center justify-between gap-1.5 bg-blue-700/60 p-1.5 rounded-2xl backdrop-blur-xs flex-shrink-0 z-10" @click.stop>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-200 ml-1">Receive:</span>
                            <div class="flex items-center gap-1 flex-1 justify-around">
                                <button type="button" @click="openInputModal('wip')"
                                        class="px-2.5 py-1 rounded-xl transition text-xs sm:text-sm flex-1 text-center cursor-pointer border border-blue-400/40 bg-white text-blue-900 font-semibold shadow-md hover:bg-blue-50">
                                    +WIP
                                </button>
                                <button type="button" @click="openInputModal('reworkable')"
                                        class="px-2.5 py-1 rounded-xl transition text-xs sm:text-sm flex-1 text-center cursor-pointer border border-purple-400/50 bg-purple-700/90 hover:bg-purple-600 text-white font-bold shadow-md">
                                    +Reworkable
                                </button>
                            </div>
                        </div>

                        {{-- Main Tap Target --}}
                        <button type="button" @click="openInputModal('wip')"
                                class="w-full flex-1 flex flex-col items-center justify-center cursor-pointer active:scale-95 transition-transform py-2">
                            <h1 class="text-2xl sm:text-4xl font-bold tracking-tight uppercase drop-shadow-md">RECEIVE INPUT</h1>
                            <p class="text-xs sm:text-sm font-bold text-blue-100 mt-1">Add WIP or Reworkable to line balance</p>
                        </button>
                    </div>

                    {{-- [BOTTOM-LEFT] Card 3: Pause / Resume Line (Direct Toggle Line Stoppage) --}}
                    <button type="button" @click="togglePause()"
                            :class="isPaused ? 'bg-emerald-600 hover:bg-emerald-500 border-emerald-400 animate-pulse' : 'bg-amber-600 hover:bg-amber-500 border-amber-400'"
                            class="rounded-3xl p-4 sm:p-6 border-4 text-white shadow-xl transition-all flex flex-col items-center justify-center text-center cursor-pointer active:scale-95 min-h-0">
                        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight uppercase drop-shadow-md" x-text="isPaused ? 'RESUME LINE' : 'PAUSE LINE'"></h1>
                        <p class="text-xs sm:text-sm font-bold text-amber-100 mt-2" x-text="isPaused ? 'Restart line & select reason' : 'Stop line & log downtime'"></p>
                    </button>

                    {{-- [BOTTOM-RIGHT] Card 4: Log Defect (Defect Logging Dialog) --}}
                    <button type="button" onclick="document.getElementById('modalReject').showModal()"
                            class="rounded-3xl p-4 sm:p-6 bg-red-600 hover:bg-red-500 active:bg-red-700 border-4 border-red-400 text-white shadow-xl transition-all flex flex-col items-center justify-center text-center cursor-pointer active:scale-95 min-h-0">
                        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight uppercase drop-shadow-md">LOG DEFECT</h1>
                        <p class="text-xs sm:text-sm font-bold text-red-100 mt-2">Log rejects & scrap entries</p>
                    </button>

                </div>

                {{-- Sub-Hero Action Strip (Enhanced touch hit targets for tablet) --}}
                <div class="flex items-center justify-end gap-3 mt-3 flex-shrink-0">
                    <div class="flex items-center gap-2 flex-shrink-0 ml-auto">
                        {{-- Issue to Rework Trigger --}}
                        <button type="button" x-show="availableDefectsForRework > 0" x-cloak
                                onclick="document.getElementById('modalIssueRework').showModal()"
                                title="Issue unsorted defect stock to rework bench"
                                class="px-4 py-2.5 min-h-[44px] bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                            <span>Issue to Rework</span>
                            <span class="bg-yellow-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold" x-text="availableDefectsForRework + ' Pcs'"></span>
                        </button>

                        {{-- Log Downtime Trigger (Manual Stoppages) --}}
                        <button type="button" onclick="document.getElementById('modalDowntime').showModal()"
                                title="Log Micro-stoppages or Past Downtime"
                                class="px-4 py-2.5 min-h-[44px] bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                            <span>Log Downtime</span>
                        </button>

                        {{-- Logs History Drawer Trigger --}}
                        <button type="button" @click="tab = 'all'; document.getElementById('modalHistory').showModal()"
                                title="View Full Shift Log History"
                                class="px-4 py-2.5 min-h-[44px] bg-slate-800 hover:bg-slate-700 active:bg-slate-900 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
                            <span>Logs History</span>
                            <span class="bg-slate-700 text-white px-2 py-0.5 rounded-md text-[10px] font-bold" x-text="allRecords.length"></span>
                        </button>
                    </div>
                </div>

            </div>
        @endif
