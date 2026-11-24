        {{-- Modal 4A: Cycle 1 — Issue Defects to Rework Bench --}}
        <dialog id="modalIssueRework"
                x-data="{ issue_qty: 0 }"
                @close="issue_qty = 0"
                class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-md backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-yellow-500 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-xl font-bold">Issue to Rework Bench</h3>
                    <button type="button" onclick="document.getElementById('modalIssueRework').close()" class="text-yellow-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form action="{{ route('app.sp-sessions.add-rework', $session->id) }}" method="POST"
                    @submit.prevent="submitForm($event, 'rework')"
                    class="p-6">
                    @csrf

                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-2xl flex items-center justify-between gap-3 text-xs font-bold text-yellow-900">
                        <div>
                            <span class="block text-[10px] text-yellow-700 uppercase font-semibold">Unsorted Defect Stock</span>
                            <span class="text-xl font-bold" x-text="formatNum(availableDefectsForRework) + ' Pcs'"></span>
                        </div>
                        <button type="button" @click="issue_qty = availableDefectsForRework"
                                class="px-3.5 py-2 bg-yellow-600 hover:bg-yellow-500 active:bg-yellow-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer">
                            ISSUE ALL
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Rework Quantity</label>
                            <span class="text-xs font-bold text-amber-700" x-text="issue_qty + ' Pcs'"></span>
                        </div>
                        <div class="flex items-center gap-2 min-w-0">
                            <button type="button" @click="issue_qty = Math.max(0, issue_qty - 1)" class="w-12 sm:w-14 h-14 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 font-bold text-3xl flex items-center justify-center transition cursor-pointer border border-slate-300 flex-shrink-0">-</button>
                            <input type="number" name="issue_qty" x-model.number="issue_qty" :max="availableDefectsForRework" @focus="$event.target.select()" required
                                class="flex-1 min-w-0 w-full h-14 text-center text-3xl font-bold text-slate-950 border-2 border-amber-300 rounded-2xl bg-amber-50/40 focus:bg-white focus:ring-2 focus:ring-amber-500">
                            <button type="button" @click="if (issue_qty < availableDefectsForRework) issue_qty += 1" class="w-12 sm:w-14 h-14 rounded-2xl bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 text-white font-bold text-3xl flex items-center justify-center transition cursor-pointer flex-shrink-0">+</button>
                        </div>
                    </div>

                    <div x-show="issue_qty > availableDefectsForRework" class="mb-4 p-3 bg-red-100 border border-red-300 rounded-2xl text-xs font-bold text-red-700 text-center">
                        Cannot issue more than available defect stock (<span x-text="availableDefectsForRework"></span> Pcs).
                    </div>

                    <div>
                        <button type="submit"
                                :disabled="issue_qty <= 0 || issue_qty > availableDefectsForRework"
                                :class="issue_qty <= 0 || issue_qty > availableDefectsForRework ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 shadow-xl'"
                                class="w-full text-white py-4 rounded-2xl text-lg font-bold uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-2">
                            ISSUE TO REWORK BENCH
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
