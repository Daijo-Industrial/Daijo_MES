        {{-- Modal 6: Line Team Roster --}}
        <dialog id="modalTeamRoster" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-xl backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
                <div class="bg-purple-700 px-6 py-4 flex justify-between items-center text-white flex-shrink-0">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">Active Line Team</h3>
                        <p class="text-xs text-purple-200 font-medium mt-0.5" x-text="manpowerEntries.length + ' Worker(s) assigned to {{ $session->unit_line }} Shift {{ $session->shift }}'"></p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalTeamRoster').close()" class="text-purple-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <div class="p-6 flex-1 overflow-y-auto space-y-3">
                    <template x-if="manpowerEntries.length === 0">
                        <div class="text-center py-8 text-gray-400">
                            <p class="text-base font-bold text-gray-500">No team members assigned yet</p>
                            <p class="text-xs font-medium text-gray-400 mt-1">Add operators, quality inspectors, or helpers assigned to this line shift.</p>
                        </div>
                    </template>

                    <template x-for="m in manpowerEntries" :key="m.id">
                        <div class="flex items-center justify-between p-3.5 bg-purple-50/60 border border-purple-200/80 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-semibold text-sm flex items-center justify-center shadow-xs"
                                    x-text="(m.operator_name || 'W').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <h4 class="font-semibold text-purple-950 text-sm" x-text="m.operator_name"></h4>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="px-2 py-0.5 bg-purple-200/80 text-purple-900 text-[10px] font-semibold uppercase rounded-md" x-text="m.role || 'Member'"></span>
                                        <span x-show="m.employee_no" class="text-[11px] font-mono text-purple-700" x-text="'#' + m.employee_no"></span>
                                    </div>
                                </div>
                            </div>
                            @if($session->status === 'running')
                                <button type="button" @click="removeManpower(m.id)" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs rounded-xl border border-red-200 transition cursor-pointer">
                                    Remove
                                </button>
                            @endif
                        </div>
                    </template>
                </div>

                @if($session->status === 'running')
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex-shrink-0">
                        <button type="button" onclick="document.getElementById('modalTeamRoster').close(); document.getElementById('modalManpower').showModal();"
                                class="w-full bg-purple-600 hover:bg-purple-500 active:bg-purple-700 text-white py-3.5 rounded-xl font-semibold text-sm uppercase tracking-wider shadow-md transition">
                            + ADD TEAM MEMBER
                        </button>
                    </div>
                @endif
            </div>
        </dialog>
