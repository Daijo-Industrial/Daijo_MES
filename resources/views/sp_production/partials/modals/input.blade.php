        {{-- Modal 5: Receive Input Stock (WIP / Reworkable) --}}
        <dialog id="modalInput" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-gray-900/50 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl" x-data="{ qty: 0, source: 'wip' }">
                <div :class="source === 'reworkable' ? 'bg-purple-700' : 'bg-blue-600'" class="px-6 py-4 flex justify-between items-center text-white transition-colors">
                    <div>
                        <h3 class="text-lg font-bold tracking-tight" x-text="source === 'reworkable' ? 'Receive Reworkable (Repairan)' : 'Receive Input WIP'"></h3>
                        <p class="text-xs font-medium" :class="source === 'reworkable' ? 'text-purple-200' : 'text-blue-100'" x-text="source === 'reworkable' ? 'Add reworkable stock back into line production flow' : 'Add standard raw/semi-finished WIP to line'"></p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalInput').close()" class="opacity-80 hover:opacity-100 text-2xl font-bold leading-none cursor-pointer">&times;</button>
                </div>
                <form action="{{ route('app.sp-sessions.add-input', $session->id) }}" method="POST" @submit.prevent="submitForm($event, 'input')" class="p-6">
                    @csrf
                    <input type="hidden" name="source" :value="source">

                    {{-- Stock Type Toggle --}}
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Stock Category</label>
                        <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100 rounded-2xl border border-slate-200">
                            <button type="button" @click="source = 'wip'"
                                    :class="source === 'wip' ? 'bg-blue-600 text-white shadow-md font-bold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                                    class="py-2.5 rounded-xl text-xs sm:text-sm uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-1.5">
                                <span>Standard WIP</span>
                            </button>
                            <button type="button" @click="source = 'reworkable'"
                                    :class="source === 'reworkable' ? 'bg-purple-600 text-white shadow-md font-bold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                                    class="py-2.5 rounded-xl text-xs sm:text-sm uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-1.5">
                                <span>Reworkable</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quantity (Pcs) *</label>
                            <div class="flex items-stretch h-20 rounded-2xl border-2 overflow-hidden bg-white shadow-xs transition"
                                 :class="source === 'reworkable' ? 'border-purple-300 focus-within:border-purple-500' : 'border-blue-300 focus-within:border-blue-500'">
                                <button type="button" @click="qty = Math.max(0, (parseInt(qty) || 0) - 1)"
                                        class="w-24 flex items-center justify-center bg-gray-100 hover:bg-gray-200 active:bg-gray-300 transition text-gray-600 text-4xl font-bold border-r border-gray-300 cursor-pointer select-none">
                                    -
                                </button>
                                <input type="number" name="quantity" value="0" x-model.number="qty" @focus="$event.target.select()" @blur="if (!qty && qty !== 0) qty = 0" required
                                       :class="source === 'reworkable' ? 'text-purple-700' : 'text-blue-600'"
                                       class="flex-1 text-center text-5xl font-bold border-0 focus:ring-0 w-full bg-transparent p-0">
                                <button type="button" @click="qty = (parseInt(qty) || 0) + 1"
                                        :class="source === 'reworkable' ? 'bg-purple-100 hover:bg-purple-200 active:bg-purple-300 text-purple-700 border-purple-200' : 'bg-blue-100 hover:bg-blue-200 active:bg-blue-300 text-blue-700 border-gray-300'"
                                        class="w-24 flex items-center justify-center transition text-4xl font-bold border-l cursor-pointer select-none">
                                    +
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-2 mt-4">
                                <button type="button" @click="qty = (parseInt(qty) || 0) + 10"
                                        :class="source === 'reworkable' ? 'bg-purple-50 active:bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-50 active:bg-blue-100 text-blue-700 border-blue-200'"
                                        class="py-4 font-bold rounded-xl text-xl shadow-xs border transition cursor-pointer">+10</button>
                                <button type="button" @click="qty = (parseInt(qty) || 0) + 50"
                                        :class="source === 'reworkable' ? 'bg-purple-50 active:bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-50 active:bg-blue-100 text-blue-700 border-blue-200'"
                                        class="py-4 font-bold rounded-xl text-xl shadow-xs border transition cursor-pointer">+50</button>
                                <button type="button" @click="qty = (parseInt(qty) || 0) + 100"
                                        :class="source === 'reworkable' ? 'bg-purple-50 active:bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-50 active:bg-blue-100 text-blue-700 border-blue-200'"
                                        class="py-4 font-bold rounded-xl text-xl shadow-xs border transition cursor-pointer">+100</button>
                                <button type="button" @click="qty = (parseInt(qty) || 0) + 500"
                                        :class="source === 'reworkable' ? 'bg-purple-50 active:bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-50 active:bg-blue-100 text-blue-700 border-blue-200'"
                                        class="py-4 font-bold rounded-xl text-xl shadow-xs border transition cursor-pointer">+500</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pallet / Box / Batch Ref</label>
                            <input type="text" name="pallet_number" placeholder="e.g. PALLET-01 / REPAIR-BOX-A (Optional)" class="w-full border-gray-300 rounded-xl text-base p-3 bg-gray-50 focus:bg-white font-mono uppercase">
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <button type="submit"
                                :disabled="qty <= 0"
                                :class="[
                                    qty <= 0 ? 'opacity-50 cursor-not-allowed bg-slate-400' : (source === 'reworkable' ? 'bg-purple-600 hover:bg-purple-500 active:bg-purple-700' : 'bg-blue-600 hover:bg-blue-500 active:bg-blue-700'),
                                    'w-full text-white py-4 rounded-xl text-xl font-bold shadow-lg transition flex items-center justify-center gap-2 cursor-pointer'
                                ]">
                            <span x-text="source === 'reworkable' ? 'SAVE REWORKABLE INPUT' : 'SAVE WIP INPUT'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
