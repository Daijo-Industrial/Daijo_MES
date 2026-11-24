        {{-- Modal 7: Manage Line Team / Manpower --}}
        <dialog id="modalManpower" class="rounded-xl p-0 shadow-2xl border-0 w-full max-w-lg backdrop:bg-gray-900/50 bg-transparent">
            <div class="bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-purple-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">+ Add Line Team Member</h3>
                    <button type="button" onclick="document.getElementById('modalManpower').close()" class="text-purple-200 hover:text-white text-xl font-bold">&times;</button>
                </div>
                <form action="{{ route('app.sp-sessions.add-manpower', $session->id) }}" method="POST"
                    @submit.prevent="submitForm($event, 'manpower'); selectedRole = 'loading'; customRole = ''"
                    x-data="{ selectedRole: 'loading', customRole: '' }" class="p-6">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Worker Role / Position *</label>
                            <select x-model="selectedRole" :name="selectedRole === '__custom__' ? '' : 'role'" required
                                    class="w-full border-gray-300 rounded-xl text-lg p-3 bg-gray-50 focus:bg-white font-bold text-purple-700">
                                @foreach(config('mes.sp_manpower_roles', [
                                    'loading'  => 'Loading / Input',
                                    'sprayer'  => 'Sprayer',
                                    'checker'  => 'Checker',
                                    'qc'       => 'QC',
                                    'packing'  => 'Packing',
                                    'operator' => 'Operator',
                                    'leader'   => 'Leader',
                                ]) as $roleKey => $roleLabel)
                                    <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                                @endforeach
                                <option value="__custom__">Other (custom)...</option>
                            </select>

                            <div x-show="selectedRole === '__custom__'" class="mt-2" x-cloak>
                                <input type="text" :name="selectedRole === '__custom__' ? 'role' : ''"
                                    x-model="customRole" :required="selectedRole === '__custom__'"
                                    placeholder="Type custom role..."
                                    class="w-full border-gray-300 rounded-xl text-base p-3 bg-white font-bold text-purple-900 focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Operator Name *</label>
                            <input type="text" name="operator_name" placeholder="Full Employee Name" required class="w-full border-gray-300 rounded-xl text-lg p-3 bg-gray-50 focus:bg-white font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Employee ID / NIK (Optional)</label>
                            <input type="text" name="employee_no" placeholder="e.g. EMP-1045" class="w-full border-gray-300 rounded-xl text-lg p-3 bg-gray-50 focus:bg-white font-mono">
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 flex gap-3">
                        <button type="button" onclick="document.getElementById('modalManpower').close()" class="w-1/3 bg-gray-200 active:bg-gray-300 text-gray-800 py-4 rounded-xl text-lg font-bold">Cancel</button>
                        <button type="submit" class="w-2/3 bg-purple-600 active:bg-purple-700 text-white py-4 rounded-xl text-xl font-semibold shadow-lg">ADD MEMBER</button>
                    </div>
                </form>
            </div>
        </dialog>
