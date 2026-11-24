        {{-- MODAL HISTORY (FULL SEARCHABLE EVENT STREAM DRAWER) --}}
        <dialog id="modalHistory" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-4xl backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
                <div class="bg-slate-900 px-6 py-4 flex justify-between items-center text-white flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold">Full Operational Event History</h3>
                        <p class="text-xs text-slate-400 font-medium">Complete chronological activity log for Session #{{ $session->id }}</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalHistory').close()" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                {{-- Filter Tabs (Fixed height row, flex-shrink-0) --}}
                <div class="bg-slate-100 border-b border-slate-200 px-6 flex items-center gap-2.5 text-xs font-bold py-3 overflow-x-auto whitespace-nowrap flex-shrink-0">
                    <button type="button" @click="tab = 'all'" :class="tab === 'all' ? 'bg-blue-600 text-white border-blue-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">All (<span x-text="allRecords.length"></span>)</button>
                    <button type="button" @click="tab = 'production'" :class="tab === 'production' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Good Output (<span x-text="productionEntries.length"></span>)</button>
                    <button type="button" @click="tab = 'defects'" :class="tab === 'defects' ? 'bg-red-600 text-white border-red-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Defects (<span x-text="rejectEntries.length"></span>)</button>
                    <button type="button" @click="tab = 'downtime'" :class="tab === 'downtime' ? 'bg-amber-600 text-white border-amber-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Downtime (<span x-text="downtimeEntries.length"></span>)</button>
                    <button type="button" @click="tab = 'rework'" :class="tab === 'rework' ? 'bg-yellow-600 text-white border-yellow-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Rework (<span x-text="reworkEntries.length"></span>)</button>
                    <button type="button" @click="tab = 'input'" :class="tab === 'input' ? 'bg-blue-600 text-white border-blue-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Input (<span x-text="inputEntries.length"></span>)</button>
                    <button type="button" @click="tab = 'manpower'" :class="tab === 'manpower' ? 'bg-purple-600 text-white border-purple-600 shadow-sm font-semibold' : 'bg-white text-slate-700 hover:bg-slate-200 border-slate-200'" class="px-3.5 py-2 rounded-xl transition flex-shrink-0 cursor-pointer border">Team (<span x-text="manpowerEntries.length"></span>)</button>
                </div>

                {{-- Table Area --}}
                <div class="flex-1 overflow-y-auto p-0">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                            <tr>
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Event Details</th>
                                <th class="px-4 py-3 text-right">Quantity / Duration</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="item in (tab === 'all' ? allRecords : (tab === 'production' ? productionEntries : (tab === 'defects' ? rejectEntries : (tab === 'downtime' ? downtimeEntries : (tab === 'rework' ? reworkEntries : (tab === 'input' ? inputEntries : manpowerEntries))))))" :key="(item.streamType || tab) + '-' + (item.id || item.timestamp || Math.random())">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500" x-text="formatTime(item.recorded_at || item.created_at)"></td>
                                    <td class="px-4 py-2.5">
                                        <span x-show="item.good_qty !== undefined || item.streamType === 'production'" class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">GOOD</span>
                                        <span x-show="item.defect_type !== undefined || item.streamType === 'reject'" class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-red-100 text-red-800 border border-red-200">DEFECT</span>
                                        <span x-show="item.duration_minutes !== undefined || item.streamType === 'downtime'" class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-amber-100 text-amber-800 border border-amber-200">DOWNTIME</span>
                                        <span x-show="item.recovered_qty !== undefined || item.streamType === 'rework'" class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-yellow-100 text-yellow-800 border border-yellow-200">REWORK</span>
                                        <span x-show="item.source !== undefined || item.streamType === 'input'"
                                              :class="item.source === 'reworkable' ? 'bg-purple-100 text-purple-800 border-purple-200' : 'bg-blue-100 text-blue-800 border-blue-200'"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase border"
                                              x-text="item.source === 'reworkable' ? 'REWORKABLE' : 'INPUT'"></span>
                                        <span x-show="item.role !== undefined || item.streamType === 'manpower'" class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-purple-100 text-purple-800 border border-purple-200">TEAM</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs font-semibold text-slate-800">
                                        <template x-if="item.good_qty !== undefined || item.streamType === 'production'">
                                            <span>Good Output Logged <span class="text-slate-400 font-normal" x-text="item.remarks ? '(' + item.remarks + ')' : ''"></span></span>
                                        </template>
                                        <template x-if="item.defect_type !== undefined || item.streamType === 'reject'">
                                            <span><strong class="text-red-700" x-text="item.defect_type"></strong> <span class="text-slate-500 font-normal" x-text="item.cause ? '— Cause: ' + item.cause : ''"></span></span>
                                        </template>
                                        <template x-if="item.duration_minutes !== undefined || item.streamType === 'downtime'">
                                            <span><strong class="text-amber-800" x-text="item.reason"></strong> <span class="text-slate-500 font-normal" x-text="(item.start_time && item.resume_time) ? '(' + formatHM(item.start_time) + ' – ' + formatHM(item.resume_time) + ')' : (item.start_time ? '(' + formatHM(item.start_time) + ')' : '')"></span></span>
                                        </template>
                                        <template x-if="item.recovered_qty !== undefined || item.streamType === 'rework'">
                                            <span>
                                                <strong x-text="(item.recovered_qty > 0 || item.scrapped_qty > 0) ? 'Rework Inspection Outcome' : 'Issued to Rework Bench'"></strong>
                                                <span class="text-slate-500 font-normal" x-text="item.remarks ? '— ' + item.remarks : ''"></span>
                                            </span>
                                        </template>
                                        <template x-if="item.source !== undefined || item.streamType === 'input'">
                                            <span>
                                                <strong :class="item.source === 'reworkable' ? 'text-purple-700' : 'text-blue-700'" x-text="item.source === 'reworkable' ? 'Reworkable Stock Received' : 'WIP Stock Received'"></strong>
                                                <span class="text-slate-500 font-normal" x-text="item.pallet_number ? ('— Pallet: ' + item.pallet_number) : (item.remarks ? ('— ' + item.remarks) : '')"></span>
                                            </span>
                                        </template>
                                        <template x-if="item.role !== undefined || item.streamType === 'manpower'">
                                            <span>Team Member Added: <strong class="text-purple-800" x-text="item.operator_name || ('Worker #' + (item.user_id || item.id))"></strong> <span class="text-slate-400 font-normal" x-text="item.role ? '(' + (roleLabels[item.role] || item.role) + ')' : ''"></span></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-xs">
                                        <span x-show="item.good_qty !== undefined || item.streamType === 'production'" class="text-emerald-700" x-text="'+' + formatNum(item.good_qty || 0) + ' Pcs'"></span>
                                        <span x-show="item.defect_type !== undefined || item.streamType === 'reject'" class="text-red-600" x-text="formatNum(item.quantity || 0) + ' Pcs'"></span>
                                        <span x-show="item.duration_minutes !== undefined || item.streamType === 'downtime'" class="text-amber-700" x-text="(item.duration_minutes || 0) + 'm'"></span>
                                        
                                        <template x-if="item.recovered_qty !== undefined || item.streamType === 'rework'">
                                            <div class="flex items-center justify-end gap-1.5 font-mono text-xs font-semibold">
                                                <span x-show="item.input_qty > 0 && (item.recovered_qty || 0) === 0 && (item.scrapped_qty || 0) === 0" class="text-yellow-800" x-text="formatNum(item.input_qty) + ' Issued'"></span>
                                                <span x-show="(item.recovered_qty || 0) > 0" class="text-emerald-700" x-text="'+' + formatNum(item.recovered_qty) + ' OK'"></span>
                                                <span x-show="(item.scrapped_qty || 0) > 0" class="text-red-600" x-text="formatNum(item.scrapped_qty) + ' Scrap'"></span>
                                            </div>
                                        </template>

                                        <span x-show="item.source !== undefined || item.streamType === 'input'"
                                              :class="item.source === 'reworkable' ? 'text-purple-700' : 'text-blue-700'"
                                              x-text="'+' + formatNum(item.quantity || 0) + (item.source === 'reworkable' ? ' Rework' : ' WIP')"></span>
                                        <span x-show="item.role !== undefined || item.streamType === 'manpower'" class="text-purple-700" x-text="roleLabels[item.role] || item.role || 'Team'"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                        @if($session->status === 'running')
                                            <button type="button" @click="deleteEntry(item, item.streamType || (tab === 'defects' ? 'reject' : (tab !== 'all' ? tab : null)))" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 active:bg-red-200 text-red-700 font-bold text-xs rounded-lg border border-red-200 transition cursor-pointer">
                                                Delete
                                            </button>
                                        @else
                                            <span class="text-slate-300 font-normal text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="bg-slate-100 p-4 border-t border-slate-200 text-right">
                    <button type="button" onclick="document.getElementById('modalHistory').close()" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl uppercase">Close History</button>
                </div>
            </div>
        </dialog>
