        {{-- Modal 7: Finish Production (Streamlined Confirmation) --}}
        <dialog id="modalFinish" class="rounded-2xl p-0 shadow-2xl border-0 w-full max-w-md backdrop:bg-gray-900/60 bg-transparent">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-red-700 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-lg font-bold flex items-center gap-2">Finish Production Shift</h3>
                        <p class="text-xs text-red-100 font-medium mt-0.5">Work Order #{{ $session->workOrder->wo_number }}</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalFinish').close()" class="text-red-200 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form action="{{ route('app.sp-sessions.finish', $session->id) }}" method="POST" class="p-6">
                    @csrf

                    {{-- Unfinished Rework Warning Notice --}}
                    <div x-show="reworkPending > 0" class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded-2xl flex items-start gap-2 text-xs font-bold text-amber-900">
                        <span class="text-base leading-none">⚠️</span>
                        <div>
                            <strong class="font-bold">Active Rework Warning:</strong>
                            <p class="font-medium text-amber-800 mt-0.5">
                                <span x-text="reworkPending"></span> Pcs are still sitting at the Rework Bench.
                            </p>
                        </div>
                    </div>

                    <div class="mb-6 p-4 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs text-slate-600 font-medium leading-relaxed">
                        Ready to complete this production session? Clicking below will open the <strong>Mandatory Close-Out Report</strong> where you record paint/material lots, downtime countermeasures, and shift handover.
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('modalFinish').close()"
                                class="w-1/3 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 py-3.5 rounded-2xl text-xs font-semibold uppercase tracking-wider transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="w-2/3 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white py-3.5 rounded-2xl text-xs sm:text-sm font-semibold uppercase tracking-wider shadow-lg transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>PROCEED TO CLOSE-OUT →</span>
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
