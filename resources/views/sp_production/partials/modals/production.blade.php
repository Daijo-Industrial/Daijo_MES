        {{-- Modal 1: Log Production --}}
        <dialog id="modalProduction" class="rounded-xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-gray-900/50 bg-transparent">
            <div class="bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-green-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Log Output</h3>
                    <button type="button" onclick="document.getElementById('modalProduction').close()" class="text-green-200 hover:text-white text-xl font-bold">&times;</button>
                </div>
                <form action="{{ route('app.sp-sessions.add-production', $session->id) }}" method="POST" @submit.prevent="submitForm($event, 'production')" x-data="{ good_qty: 0 }" class="p-6">
                    @csrf
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between text-xs font-bold text-blue-900">
                        <span>Available Input WIP:</span>
                        <span class="text-sm font-semibold" x-text="formatNum(availableWip) + ' Pcs'"></span>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-500 uppercase mb-2">Good Qty (Pcs) *</label>
                            <div class="flex items-stretch h-20 rounded-2xl border-2 border-gray-300 overflow-hidden bg-white shadow-sm">
                                <button type="button" @click="good_qty = Math.max(0, good_qty - 1)" class="w-24 flex items-center justify-center bg-gray-100 hover:bg-gray-200 active:bg-gray-300 transition text-gray-600 text-4xl font-bold border-r border-gray-300">
                                    -
                                </button>
                                <input type="number" name="good_qty" value="0" x-model.number="good_qty" @focus="$event.target.select()" @blur="if (!good_qty && good_qty !== 0) good_qty = 0" required class="flex-1 text-center text-5xl font-medium text-green-600 border-0 focus:ring-0 w-full bg-transparent p-0">
                                <button type="button" @click="good_qty += 1" class="w-24 flex items-center justify-center bg-green-100 hover:bg-green-200 active:bg-green-300 transition text-green-700 text-4xl font-bold border-l border-gray-300">
                                    +
                                </button>
                            </div>
                            <div class="grid grid-cols-4 gap-2 mt-4">
                                <button type="button" @click="good_qty += 5" class="py-4 bg-green-50 active:bg-green-100 text-green-700 font-semibold rounded-xl text-xl shadow-sm border border-green-200">+5</button>
                                <button type="button" @click="good_qty += 10" class="py-4 bg-green-50 active:bg-green-100 text-green-700 font-semibold rounded-xl text-xl shadow-sm border border-green-200">+10</button>
                                <button type="button" @click="good_qty += 50" class="py-4 bg-green-50 active:bg-green-100 text-green-700 font-semibold rounded-xl text-xl shadow-sm border border-green-200">+50</button>
                                <button type="button" @click="good_qty += 100" class="py-4 bg-green-50 active:bg-green-100 text-green-700 font-semibold rounded-xl text-xl shadow-sm border border-green-200">+100</button>
                            </div>
                        </div>

                        <div>
                            <input type="text" name="remarks" placeholder="Optional notes / remarks..." class="w-full border-gray-300 rounded-lg text-lg p-3 bg-gray-50 focus:bg-white">
                        </div>
                    </div>

                    {{-- WIP Warning --}}
                    <div x-show="good_qty > availableWip" class="mt-4 p-2 bg-red-100 border border-red-300 rounded-lg text-xs font-bold text-red-700 text-center">
                        Warning: Output quantity (<span x-text="good_qty"></span> Pcs) exceeds available WIP (<span x-text="availableWip"></span> Pcs). Please log Input WIP first.
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="good_qty <= 0 || good_qty > availableWip" :class="good_qty <= 0 || good_qty > availableWip ? 'opacity-50 cursor-not-allowed' : ''" class="w-full bg-green-600 active:bg-green-700 text-white py-4 rounded-xl text-xl font-bold shadow-lg transition">SAVE LOG</button>
                    </div>
                </form>
            </div>
        </dialog>
