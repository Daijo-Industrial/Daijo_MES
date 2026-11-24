        {{-- ULTRA-KISS CORE METRICS BAR (Equal Height KPI Cards) --}}
        <div class="bg-white border-b border-gray-200 px-4 py-2.5 shadow-sm flex items-stretch justify-between gap-3 sm:gap-4 flex-shrink-0">
            {{-- Metric 1: Available Balance — Positioned at the very left for immediate operator visibility --}}
            <button type="button" @click="tab = 'input'; document.getElementById('modalHistory').showModal()"
                    class="px-3 py-2 rounded-2xl border transition-all cursor-pointer text-left shadow-xs flex-1 max-w-xs relative overflow-hidden"
                    :class="[
                        availableWip > 0 ? 'bg-blue-50/70 hover:bg-blue-100/80 border-blue-200/80' : 'bg-amber-100/80 hover:bg-amber-200/90 border-amber-300 animate-pulse',
                        balanceFlash ? 'ring-4 ring-blue-400 bg-blue-100/90 scale-[1.02]' : ''
                    ]"
                    title="Click to view Input WIP / Reworkable Logs">
                <div class="min-w-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider block truncate mb-0.5" :class="availableWip > 0 ? 'text-blue-700' : 'text-amber-800'">Available Balance</span>
                    <div class="text-base sm:text-lg font-bold leading-tight" :class="availableWip > 0 ? 'text-blue-950' : 'text-amber-950'">
                        <span x-text="formatNum(availableWip) + ' Pcs'"></span>
                    </div>
                    <div class="text-[9px] font-bold text-blue-800/80 truncate" x-show="(totals.input || 0) > 0">
                        <span x-text="formatNum(totals.input) + ' Pcs In' + (totals.input_reworkable > 0 ? (' (' + formatNum(totals.input_wip || (totals.input - totals.input_reworkable)) + ' WIP • ' + formatNum(totals.input_reworkable) + ' Rework)') : '')"></span>
                    </div>
                </div>
            </button>

            {{-- Metric 2: Standalone Good Output (Direct Good + Rework Recovered Breakdown) --}}
            <button type="button" @click="tab = 'production'; document.getElementById('modalHistory').showModal()"
                    class="px-3 py-2 bg-emerald-50/70 hover:bg-emerald-100/80 active:bg-emerald-200 border border-emerald-200/80 rounded-2xl transition cursor-pointer text-left shadow-xs flex-1 max-w-xs"
                    title="Click to view Good Output Logs">
                <div class="min-w-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700 block">Good Output</span>
                    <div class="text-base sm:text-lg font-bold text-emerald-950 leading-tight">
                        <span x-text="formatNum(totals.good) + ' Pcs'"></span>
                    </div>
                    <div class="text-[9px] font-bold text-emerald-800/80 truncate">
                        <span x-text="formatNum(totals.direct_good || (totals.good - (totals.rework_recovered || 0))) + ' Direct' + (totals.rework_recovered > 0 ? (' • ' + formatNum(totals.rework_recovered) + ' Rec.') : '')"></span>
                    </div>
                </div>
            </button>

            {{-- Metric 3: Total Logged Defects (Total Raw Defects + Defect Rate %) --}}
            <button type="button" @click="tab = 'defects'; document.getElementById('modalHistory').showModal()"
                    class="px-3 py-2 bg-red-50/70 hover:bg-red-100/80 active:bg-red-200 border border-red-200/80 rounded-2xl transition cursor-pointer text-left shadow-xs flex-1 max-w-xs"
                    title="Click to view Defect Logs">
                <div class="min-w-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-red-700 block">Total Defects</span>
                    <div class="text-base sm:text-lg font-bold text-red-950 leading-tight">
                        <span x-text="formatNum(totals.raw_reject || totals.reject) + ' Pcs'"></span>
                    </div>
                    <div class="text-[9px] font-bold text-red-800/80 truncate">
                        <span x-text="((totals.reject / Math.max(1, (totals.good + totals.reject))) * 100).toFixed(1) + '% Rate'"></span>
                    </div>
                </div>
            </button>

            {{-- Metric 3.5: Total Issued Rework KPI Card (Click to view Rework logs) --}}
            <button type="button" x-show="(totals.rework_in || 0) > 0" x-cloak
                    @click="tab = 'rework'; document.getElementById('modalHistory').showModal()"
                    class="px-3 py-2 bg-yellow-50/70 hover:bg-yellow-100/80 active:bg-yellow-200 border border-yellow-200/80 rounded-2xl transition cursor-pointer text-left shadow-xs flex-1 max-w-xs"
                    title="Click to view Rework Logs">
                <div class="min-w-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-yellow-800 block">Issued Rework</span>
                    <div class="text-base sm:text-lg font-bold text-yellow-950 leading-tight">
                        <span x-text="formatNum(totals.rework_in) + ' Pcs'"></span>
                    </div>
                    <div class="text-[9px] font-bold text-yellow-800/80 truncate">
                        <span x-text="formatNum(totals.rework_recovered || 0) + ' Rec. • ' + formatNum(totals.rework_scrapped || 0) + ' Scrap'"></span>
                    </div>
                </div>
            </button>

            {{-- Metric 4: Downtime Duration (Click to view Downtime logs) --}}
            <button type="button" @click="tab = 'downtime'; document.getElementById('modalHistory').showModal()"
                    class="px-3 py-2 bg-amber-50/70 hover:bg-amber-100/80 active:bg-amber-200 border border-amber-200/80 rounded-2xl transition cursor-pointer text-left shadow-xs flex-1 max-w-xs"
                    title="Click to view Downtime Logs">
                <div class="min-w-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-800 block">Downtime</span>
                    <div class="text-base sm:text-lg font-bold text-amber-950 leading-tight">
                        <span x-text="formatNum(totals.downtime_minutes) + ' Mins'"></span>
                    </div>
                </div>
            </button>

            {{-- Metric 5: Rich WO Target Progress Card --}}
            <div class="flex-1 max-w-xs hidden md:flex flex-col justify-between bg-slate-50 border border-slate-200/80 rounded-2xl px-3 py-2 shadow-xs">
                <div class="flex items-center justify-between text-[10px] font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    <span>WO Target</span>
                    <span class="text-slate-900 font-bold" x-text="progressPct + '%'"></span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden my-auto">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="progressPct >= 100 ? 'bg-emerald-500' : (progressPct >= 75 ? 'bg-blue-500' : (progressPct >= 50 ? 'bg-amber-500' : 'bg-slate-400'))"
                         :style="'width: ' + Math.min(100, progressPct) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mt-1">
                    <span>Target: <strong class="text-slate-700" x-text="formatNum(targetQty)"></strong></span>
                    <span>Rem: <strong :class="Math.max(0, targetQty - totals.good) > 0 ? 'text-amber-700' : 'text-emerald-700'" x-text="formatNum(Math.max(0, targetQty - totals.good)) + ' Pcs'"></strong></span>
                </div>
            </div>
        </div>
