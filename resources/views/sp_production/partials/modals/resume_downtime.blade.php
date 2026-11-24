        {{-- Modal 8: Resume Downtime (Reason Selection) --}}
        <dialog id="modalResumeDowntime" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-xl backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-amber-600 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">Resume Line Production</h3>
                        <p class="text-xs text-amber-100 font-medium mt-0.5" x-text="'Line stopped for ' + pausedDurationMinutes + ' Minute(s). Please select Downtime Reason to resume.'"></p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalResumeDowntime').close()" class="text-amber-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitResumeForm($event)" x-data="{ selectedReason: '' }" class="p-6">
                    @csrf
                    <input type="hidden" name="reason" :value="selectedReason">

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Select Downtime Reason *</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($downtimeReasons as $r)
                                    <button type="button" @click="selectedReason = '{{ $r }}'"
                                            :class="selectedReason === '{{ $r }}' ? 'bg-amber-600 text-white ring-4 ring-amber-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                                            class="py-3.5 px-3 rounded-xl font-bold text-center transition shadow-xs text-xs">
                                        {{ $r }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Optional Remarks / Action Taken</label>
                            <input type="text" name="remarks" placeholder="Countermeasure or breakdown details..." class="w-full border-gray-300 rounded-xl text-sm p-3 bg-gray-50 focus:bg-white">
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="!selectedReason" :class="!selectedReason ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white py-4 rounded-xl text-lg font-bold shadow-lg transition uppercase tracking-wider">
                            RESUME PRODUCTION & SAVE DOWNTIME
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
