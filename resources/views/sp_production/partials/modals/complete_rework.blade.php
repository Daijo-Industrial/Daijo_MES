        {{-- Modal 4B: Cycle 2 — Complete Rework Outcome --}}
        <dialog id="modalCompleteRework"
                x-data="{
                    recovered_qty: 0,
                    scrapped_qty: 0,
                    set100Recovered() {
                        this.recovered_qty = this.reworkPending;
                        this.scrapped_qty = 0;
                    },
                    set100Scrapped() {
                        this.scrapped_qty = this.reworkPending;
                        this.recovered_qty = 0;
                    }
                }"
                @close="recovered_qty = 0; scrapped_qty = 0"
                class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-emerald-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-xl font-bold">Complete Rework Outcome</h3>
                    <button type="button" onclick="document.getElementById('modalCompleteRework').close()" class="text-emerald-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form action="{{ route('app.sp-sessions.add-rework', $session->id) }}" method="POST"
                    @submit.prevent="submitForm($event, 'rework')"
                    class="p-6">
                    @csrf

                    {{-- Active Bench Banner + 1-Tap Quick Action Pills --}}
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex flex-col gap-3 text-xs font-bold text-emerald-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase text-emerald-800">Active Rework Bench</span>
                            <span class="text-xl font-bold text-emerald-950" x-text="formatNum(reworkPending) + ' Pcs'"></span>
                        </div>

                        <div class="flex items-center gap-2 pt-2 border-t border-emerald-200/60">
                            <span class="text-[11px] font-bold text-emerald-700">Quick Fill:</span>
                            <button type="button" @click="set100Recovered()"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer flex-1 text-center">
                                100% RECOVERED
                            </button>
                            <button type="button" @click="set100Scrapped()"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-500 active:bg-red-700 text-white font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer flex-1 text-center">
                                100% SCRAPPED
                            </button>
                        </div>
                    </div>

                    <div class="space-y-5">
                        {{-- 1. Recovered OK Pcs Stepper --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-emerald-800 uppercase tracking-wider">Recovered OK</label>
                                <span class="text-xs font-bold text-emerald-600" x-text="recovered_qty + ' OK Pcs'"></span>
                            </div>
                            <div class="flex items-center gap-2 min-w-0">
                                <button type="button" @click="recovered_qty = Math.max(0, recovered_qty - 1)" class="w-12 sm:w-14 h-14 rounded-2xl bg-emerald-100 hover:bg-emerald-200 active:bg-emerald-300 text-emerald-800 font-bold text-3xl flex items-center justify-center transition cursor-pointer border border-emerald-300 flex-shrink-0">-</button>
                                <input type="number" name="recovered_qty" x-model.number="recovered_qty" @focus="$event.target.select()" required
                                    class="flex-1 min-w-0 w-full h-14 text-center text-3xl font-bold text-emerald-950 border-2 border-emerald-300 rounded-2xl bg-emerald-50/40 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                                <button type="button" @click="if (!reworkPending || (recovered_qty + scrapped_qty) < reworkPending) recovered_qty += 1" class="w-12 sm:w-14 h-14 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-bold text-3xl flex items-center justify-center transition cursor-pointer border border-emerald-300 flex-shrink-0">+</button>
                            </div>
                        </div>

                        {{-- 2. Scrapped Pcs Stepper --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-red-800 uppercase tracking-wider">Scrapped</label>
                                <span class="text-xs font-bold text-red-600" x-text="scrapped_qty + ' Scrap Pcs'"></span>
                            </div>
                            <div class="flex items-center gap-2 min-w-0">
                                <button type="button" @click="scrapped_qty = Math.max(0, scrapped_qty - 1)" class="w-12 sm:w-14 h-14 rounded-2xl bg-red-100 hover:bg-red-200 active:bg-red-300 text-red-800 font-bold text-3xl flex items-center justify-center transition cursor-pointer border border-red-300 flex-shrink-0">-</button>
                                <input type="number" name="scrapped_qty" x-model.number="scrapped_qty" @focus="$event.target.select()" required
                                    class="flex-1 min-w-0 w-full h-14 text-center text-3xl font-bold text-red-950 border-2 border-red-300 rounded-2xl bg-red-50/40 focus:bg-white focus:ring-2 focus:ring-red-500">
                                <button type="button" @click="if (!reworkPending || (recovered_qty + scrapped_qty) < reworkPending) scrapped_qty += 1" class="w-12 sm:w-14 h-14 rounded-2xl bg-red-600 hover:bg-red-500 active:bg-red-700 text-white font-bold text-3xl flex items-center justify-center transition cursor-pointer border border-red-300 flex-shrink-0">+</button>
                            </div>
                        </div>
                    </div>

                    {{-- Validation Warnings --}}
                    <div x-show="(recovered_qty + scrapped_qty) > reworkPending && reworkPending > 0" class="mt-4 p-3 bg-red-100 border border-red-300 rounded-2xl text-xs font-bold text-red-700 text-center">
                        Warning: Total Outcome (<span x-text="recovered_qty + scrapped_qty"></span> Pcs) cannot exceed Rework Bench WIP (<span x-text="reworkPending"></span> Pcs).
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <button type="submit"
                                :disabled="(recovered_qty + scrapped_qty) <= 0 || ((recovered_qty + scrapped_qty) > reworkPending && reworkPending > 0)"
                                :class="(recovered_qty + scrapped_qty) <= 0 || ((recovered_qty + scrapped_qty) > reworkPending && reworkPending > 0) ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 shadow-xl'"
                                class="w-full text-white py-4 rounded-2xl text-lg font-bold uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-2">
                            SAVE REWORK OUTCOME
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
