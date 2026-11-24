        {{-- Modal 3: Log Downtime --}}
        <dialog id="modalDowntime" class="rounded-xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-gray-900/50 bg-transparent">
            <div class="bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-amber-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Log Downtime</h3>
                    <button type="button" onclick="document.getElementById('modalDowntime').close()" class="text-amber-200 hover:text-white text-xl font-bold">&times;</button>
                </div>
                <form action="{{ route('app.sp-sessions.add-downtime', $session->id) }}" method="POST" @submit.prevent="submitForm($event, 'downtime')" x-data="{ reason: '' }" class="p-6">
                    @csrf
                    <input type="hidden" name="reason" :value="reason">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-500 uppercase mb-2">Reason *</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($downtimeReasons as $r)
                                    <button type="button" @click="reason = '{{ $r }}'" :class="reason === '{{ $r }}' ? 'bg-amber-600 text-white ring-4 ring-amber-200' : 'bg-gray-100 text-gray-700'" class="py-3 px-2 rounded-xl font-bold text-center transition shadow-sm text-sm">
                                        {{ $r }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-500 uppercase mb-2">Start Time *</label>
                                <input type="time" name="start_time" required class="w-full h-14 text-xl border-gray-300 rounded-xl text-center font-bold">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-500 uppercase mb-2">Resume Time *</label>
                                <input type="time" name="resume_time" required class="w-full h-14 text-xl border-gray-300 rounded-xl text-center font-bold">
                            </div>
                        </div>

                        <div>
                            <input type="text" name="remarks" placeholder="Optional remarks..." class="w-full border-gray-300 rounded-lg text-lg p-3 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="!reason" :class="!reason ? 'opacity-50 cursor-not-allowed' : ''" class="w-full bg-amber-600 active:bg-amber-700 text-white py-4 rounded-xl text-xl font-semibold shadow-lg transition">SAVE DOWNTIME</button>
                    </div>
                </form>
            </div>
        </dialog>
