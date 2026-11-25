        {{-- Modal 2: Log Defect (Quick Grid Batch) --}}
        <dialog id="modalReject" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-xl backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-red-600 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-xl font-bold">Log Defect Entries</h3>
                        <p class="text-xs text-red-100 font-medium mt-0.5">Tap common defects to add (+1 per tap) or stage multiple</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalReject').close()" class="text-red-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                @php
                    $presetDefects = array_values(array_unique(array_merge($defectTypes ?? [], ['Flash', 'Burn Mark', 'Scratch', 'Short Shot', 'Discoloration', 'Sink Mark', 'Silver Streak', 'Deformation'])));
                @endphp

                <form action="{{ route('app.sp-sessions.add-reject', $session->id) }}" method="POST"
                    @submit.prevent="submitMultiRejectForm($event)"
                    x-data="rejectBuilder({{ json_encode($presetDefects) }})"
                    class="p-5 sm:p-6 space-y-4">
                    @csrf

                    {{-- WIP Balance Pill --}}
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between text-xs font-bold text-blue-900">
                        <span>Available Input WIP:</span>
                        <span class="text-sm font-semibold" x-text="formatNum(availableWip) + ' Pcs'"></span>
                    </div>

                    {{-- Quick Grid of Common Defects --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Common Defects (Tap to add +1)</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <template x-for="p in presetList" :key="p">
                                <button type="button" @click.stop="addDefect(p)"
                                        :class="getDefectCount(p) > 0 ? 'bg-red-50 border-red-500 text-red-900 shadow-xs ring-2 ring-red-200' : 'bg-slate-50 hover:bg-red-50/50 border-slate-200 text-slate-800'"
                                        class="p-3.5 rounded-xl border-2 font-bold text-sm flex items-center justify-between transition cursor-pointer select-none active:scale-95 text-left">
                                    <span class="truncate flex-1 pr-1" x-text="p"></span>
                                    <div class="flex items-center gap-1">
                                        <template x-if="getDefectCount(p) > 0">
                                            <span class="px-2 py-0.5 rounded-lg bg-red-600 text-white font-semibold text-xs shadow-xs" x-text="getDefectCount(p)"></span>
                                        </template>
                                        <template x-if="getDefectCount(p) === 0">
                                            <span class="text-slate-400 font-bold text-base leading-none">+</span>
                                        </template>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Staged Defect Summary --}}
                    <div x-show="stagedItems.length > 0" x-cloak class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Staged for Submission (<span x-text="totalDefectQty"></span> Pcs across <span x-text="stagedItems.length"></span> types)
                            </label>
                            <button type="button" @click="resetCounts()" class="text-[11px] font-bold text-slate-400 hover:text-red-600 cursor-pointer">Clear All</button>
                        </div>

                        <div class="space-y-1.5 max-h-40 overflow-y-auto pr-0.5">
                            <template x-for="(item, idx) in stagedItems" :key="item.defect_type + '-' + idx">
                                <div class="p-2 bg-red-50/70 border border-red-200 rounded-xl flex items-center justify-between gap-2">
                                    <span class="font-semibold text-xs sm:text-sm text-red-950 truncate flex-1" x-text="item.defect_type"></span>

                                    {{-- Stepper Controls (Enhanced touch targets for tablet) --}}
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <button type="button" @click="decrementDefect(item.defect_type)"
                                                class="w-9 h-9 rounded-xl bg-white border border-red-200 text-red-700 font-bold text-base flex items-center justify-center hover:bg-red-100 active:bg-red-200 transition cursor-pointer select-none">
                                            -
                                        </button>
                                        <input type="number" min="1" x-model.number="item.quantity" @focus="$event.target.select()"
                                            class="w-14 h-9 text-center font-bold text-sm border border-red-200 rounded-xl bg-white text-red-700 p-0 focus:ring-red-500">
                                        <button type="button" @click="item.quantity = (parseInt(item.quantity) || 0) + 1"
                                                class="w-9 h-9 rounded-xl bg-red-600 text-white font-bold text-base flex items-center justify-center hover:bg-red-500 active:bg-red-700 transition cursor-pointer select-none">
                                            +
                                        </button>
                                        <button type="button" @click="removeDefect(idx)"
                                                class="w-9 h-9 text-slate-400 hover:text-red-600 font-bold text-lg flex items-center justify-center transition cursor-pointer" title="Remove">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Optional Expandable Toggles (Clean, Zero Distraction by Default) --}}
                    <div class="pt-1 flex flex-col gap-2">
                        <div class="flex items-center gap-3 text-xs font-bold text-slate-500">
                            <button type="button" @click="showCustom = !showCustom" class="hover:text-red-600 underline cursor-pointer">
                                <span x-text="showCustom ? '− Hide Custom Defect' : '+ Other / Custom Defect'"></span>
                            </button>
                            <span>•</span>
                            <button type="button" @click="showCause = !showCause" class="hover:text-slate-800 underline cursor-pointer">
                                <span x-text="showCause ? '− Hide Note / Cause' : '+ Add Note / Cause (Optional)'"></span>
                            </button>
                        </div>

                        {{-- Expandable Custom Defect Input --}}
                        <div x-show="showCustom" x-cloak class="flex items-center gap-2 pt-1">
                            <input type="text" x-model="customName"
                                @keydown.enter.prevent="addCustomDefect()"
                                placeholder="Type unlisted defect name..."
                                class="flex-1 text-xs font-bold border-slate-300 rounded-xl p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500 text-slate-900">
                            <button type="button" @click="addCustomDefect()"
                                    class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl transition cursor-pointer">
                                + Add
                            </button>
                        </div>

                        {{-- Expandable Cause / Note --}}
                        <div x-show="showCause" x-cloak class="pt-1">
                            <input type="text" x-model="cause" placeholder="Optional probable cause / notes for this batch..."
                                class="w-full border-slate-300 rounded-xl text-xs p-2.5 bg-slate-50 focus:bg-white font-medium">
                        </div>
                    </div>

                    {{-- WIP Warning --}}
                    <div x-show="totalDefectQty > availableWip" class="p-2 bg-red-100 border border-red-300 rounded-xl text-xs font-bold text-red-700 text-center">
                        Warning: Total defect quantity (<span x-text="totalDefectQty"></span> Pcs) exceeds available WIP (<span x-text="availableWip"></span> Pcs).
                    </div>

                    {{-- Big Submit Button --}}
                    <div class="pt-2">
                        <button type="submit"
                                :disabled="totalDefectQty <= 0 || stagedItems.length === 0 || totalDefectQty > availableWip"
                                :class="(totalDefectQty <= 0 || stagedItems.length === 0 || totalDefectQty > availableWip) ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-red-600 hover:bg-red-500 active:bg-red-700 shadow-lg cursor-pointer'"
                                class="w-full text-white py-4 rounded-xl text-base font-bold uppercase tracking-wider transition flex items-center justify-center gap-2">
                            <span x-text="totalDefectQty > 0 ? ('SUBMIT ' + totalDefectQty + ' DEFECT(S) (' + stagedItems.length + ' TYPES)') : 'TAP COMMON DEFECT TO LOG'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
