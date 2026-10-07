<x-app-layout>
    <!-- Display Success and Error Messages -->
    <div class="p-3">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded mb-2" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded mb-2" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Display Validation Errors -->
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded" role="alert">
                <strong class="font-bold">Error!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="w-full px-4 pt-4" id="machineStatusContainer">
        <!-- Unified Machine Status & Control Widget -->
        <div class="bg-gradient-to-r from-slate-50 to-indigo-50 border border-indigo-100 rounded-2xl p-5 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
            
            <!-- Left Section: Current Status & Pulse Indicator -->
            <div class="flex items-center space-x-4">
                <div class="relative flex h-4 w-4">
                    @if($activeState === 'RUNNING')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                    @elseif($activeState === 'MOULD_CHANGE')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-amber-500"></span>
                    @elseif($activeState === 'ADJUSTING')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-blue-500"></span>
                    @elseif($activeState === 'REPAIRING')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                    @endif
                </div>
                <div>
                    <span class="text-[10px] font-semibold text-indigo-600 tracking-wider uppercase block">Status Mesin</span>
                    <h2 class="text-md font-bold text-gray-800">
                        @if($activeState === 'RUNNING')
                            Mesin Berjalan (Running)
                        @elseif($activeState === 'MOULD_CHANGE')
                            Sedang Ganti Mould
                        @elseif($activeState === 'ADJUSTING')
                            Sedang Adjust Mesin
                        @elseif($activeState === 'REPAIRING')
                            Perbaikan Mesin (Repairing)
                        @endif
                    </h2>
                </div>
            </div>

            <!-- Middle Section: Active Operator Details (Visible when state is not RUNNING) -->
            @if($activeState !== 'RUNNING')
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center bg-white border border-gray-150 px-4 py-2 rounded-xl shadow-sm space-x-3">
                        <img 
                            src="{{ $activeOperatorProfile }}" 
                            alt="Operator Profile" 
                            class="w-10 h-10 rounded-full object-cover border border-gray-200"
                        >
                        <div>
                            <span class="text-[10px] text-gray-500 uppercase block">Operator Aktif</span>
                            <span class="text-xs font-semibold text-gray-800">{{ $activeOperatorName }}</span>
                        </div>
                    </div>

                    <!-- Timer Section -->
                    <div class="bg-indigo-600 text-white px-4 py-2 rounded-xl shadow-sm text-center min-w-[100px]">
                        <span class="text-[9px] uppercase tracking-wider block opacity-75">Durasi Aktivitas</span>
                        <span id="activityTimer" class="font-mono text-sm font-bold" data-start="{{ $activeStateStartTime }}">00:00:00</span>
                    </div>
                </div>
            @endif

            <!-- Right Section: Context-Relevant Buttons -->
            <div class="flex flex-wrap gap-2 items-center justify-end">
                <button 
                    id="btnOpenMaintenanceChecklist" 
                    class="px-4 py-2 text-xs font-bold rounded-xl shadow-sm transition-all duration-150 flex items-center gap-2 {{ ($hasMaintenanceChecklistToday ?? false) ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-700 hover:bg-slate-800 text-white border border-slate-600' }}"
                >
                    <span>🛠️ Maintenance Checklist</span>
                    @if($hasMaintenanceChecklistToday ?? false)
                        <span class="bg-emerald-800 text-emerald-100 text-[10px] px-2 py-0.5 rounded-full font-black uppercase">Sudah Diisi</span>
                    @else
                        <span class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black uppercase animate-pulse">Belum Diisi</span>
                    @endif
                </button>

                @if($activeState === 'RUNNING')
                    <button 
                        id="startMouldChange" 
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        🔄 Change Mould
                    </button>
                    <button 
                        id="startAdjustMachine" 
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        ⚙️ Adjust Machine
                    </button>
                    <button 
                        id="startRepairMachine" 
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        🔧 Repair Machine
                    </button>
                @elseif($activeState === 'MOULD_CHANGE')
                    <button 
                        id="endMouldChange" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        ✅ Selesai Ganti Mould
                    </button>
                @elseif($activeState === 'ADJUSTING')
                    <button 
                        id="endAdjustMachine" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        ✅ Selesai Adjust Mesin
                    </button>
                @elseif($activeState === 'REPAIRING')
                    <button 
                        id="endRepairMachine" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all duration-150"
                    >
                        ✅ Selesai Perbaikan
                    </button>
                @endif

                <a 
                    href="{{ route('adminoperator') }}"
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all duration-150"
                >
                    📅 JADWAL MESIN
                </a>

                <button 
                    id="reloadButton"
                    class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-semibold rounded-xl shadow-sm transition-all duration-150"
                >
                    🔄 Refresh
                </button>
            </div>

        </div>

        <!-- Remarks Inputs based on Active State -->
        @if($activeState === 'MOULD_CHANGE')
            <div id="mouldChangeInfo" class="bg-white border border-indigo-100 rounded-2xl p-4 shadow-sm mt-3">
                <label for="mouldRemarks" class="block text-xs font-semibold text-indigo-600 tracking-wider uppercase mb-1">Remarks Ganti Mould (Opsional)</label>
                <textarea id="mouldRemarks" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs" placeholder="Ketik remark mould change di sini..."></textarea>
            </div>
        @elseif($activeState === 'ADJUSTING')
            <div id="adjustMachineInfo" class="bg-white border border-indigo-100 rounded-2xl p-4 shadow-sm mt-3">
                <label for="adjustRemarks" class="block text-xs font-semibold text-indigo-600 tracking-wider uppercase mb-1">Remarks Adjust Mesin (Opsional)</label>
                <textarea id="adjustRemarks" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs" placeholder="Ketik remark adjust machine di sini..."></textarea>
            </div>
        @elseif($activeState === 'REPAIRING')
            <div id="repairMachineInfo" class="bg-white border border-indigo-100 rounded-2xl p-4 shadow-sm mt-3 space-y-3">
                <div>
                    <label for="repairProblem" class="block text-xs font-semibold text-red-600 tracking-wider uppercase mb-1">Masalah / Problem (Wajib)</label>
                    <input type="text" id="repairProblem" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs" placeholder="Jelaskan masalah perbaikan mesin...">
                </div>
                <div>
                    <label for="repairRemarks" class="block text-xs font-semibold text-indigo-600 tracking-wider uppercase mb-1">Remarks Perbaikan (Opsional)</label>
                    <textarea id="repairRemarks" rows="2" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs" placeholder="Ketik remark perbaikan di sini..."></textarea>
                </div>
            </div>
        @endif
    </div>

    <div class="flex justify-between items-start flex-wrap gap-4 px-4 mt-4">
    
        <div class="w-full px-6 py-3 bg-white border border-gray-200 rounded-xl shadow-md flex items-center space-x-6">
            <div>
                <div class="text-sm text-gray-500 font-medium uppercase">Tanggal Hari Ini</div>
                <div class="text-lg font-semibold text-gray-800" id="tanggal-hari-ini"></div>
            </div>
            <div class="border-l border-gray-300 h-8"></div>
            <div>
                <div class="text-sm text-gray-500 font-medium uppercase">Waktu Sekarang (WIB)</div>
                <div class="text-xl font-bold text-indigo-600" id="jam-hari-ini"></div>
            </div>
        </div>
        <!-- Zone & Pengawas Info -->
        <div class="bg-white shadow rounded-lg p-4 flex items-center min-w-[280px]">
                @if($zone)
                    <!-- Text Section -->
                    <div class="flex-1 pr-4">
                        <p class="text-sm text-gray-500">Zone</p>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $zone->zone_name }}</h3>
                        <p class="text-sm text-gray-500">Pengawas:</p>
                        <p class="text-md font-medium text-gray-700">{{ $pengawasName }}</p>
                    </div>

                    <!-- Profile Image Section -->
                    <div>
                        @if($pengawasProfile)
                            <img src="{{ asset('storage/' . $pengawasProfile) }}" alt="Pengawas Profile Picture"
                                class="w-20 h-20 rounded-full border border-gray-300 object-cover shadow">
                        @else
                            <p class="text-xs text-gray-400 italic">No picture</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>





        <div id="nikModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative z-50">
                <h2 class="text-lg font-bold mb-4">Enter NIK & Password</h2>

                <div id="setupMolderSelectContainer" class="mb-3 hidden">
                    <label for="setup_molder_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Pilih Operator Setup Mold:
                    </label>
                    <select id="setup_molder_select" class="border border-gray-300 rounded p-2 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" data-password="">-- Pilih Nama --</option>
                        @foreach($setupMolders as $molder)
                            <option value="{{ $molder->name }}" data-password="{{ $molder->password }}">
                                {{ $molder->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="adjusterSelectContainer" class="mb-3 hidden">
                    <label for="adjuster_select" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Pilih Operator Adjuster:
                    </label>
                    <select id="adjuster_select" class="border border-gray-300 rounded p-2 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" data-password="">-- Pilih Nama --</option>
                        @foreach($adjusters as $adjuster)
                            <option value="{{ $adjuster->name }}" data-password="{{ $adjuster->password }}">
                                {{ $adjuster->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="text" id="nik" class="border p-2 w-full rounded" placeholder="Enter NIK...">
                <input type="password" id="password" class="border p-2 w-full rounded mt-2" placeholder="Enter Password...">
                
                <div id="nextItemCodeContainer" class="mt-3 hidden">
                    <label id="nextItemCodeLabel" for="next_item_code" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Pilih Item Code Selanjutnya:
                    </label>
                    <select id="next_item_code" class="border border-gray-300 rounded-xl p-2 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Item Code --</option>
                        @foreach($todayitems as $item)
                            <option value="{{ $item->item_code }}" {{ $item->item_code === $defaultNextItemCode ? 'selected' : '' }}>
                                {{ $item->item_code }} - Shift {{ $item->shift }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end mt-4">
                    <button id="closeNikModal" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                    <button id="verifyNik" class="bg-blue-600 text-white px-4 py-2 rounded">Verify</button>
                </div>
            </div>
        </div>


        <!-- Edit Log Modal -->
        <div id="editLogModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden z-50">
            <div class="bg-white p-6 rounded-2xl shadow-xl w-[90%] sm:w-[450px] relative z-50 border border-slate-100 animate-in zoom-in-95 duration-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center space-x-2">
                    <span>✏️</span>
                    <span>Edit Log Pengerjaan</span>
                </h2>

                <input type="hidden" id="edit_log_id">
                <input type="hidden" id="edit_log_type">

                <div class="space-y-4">
                    <div>
                        <label for="edit_log_created_at" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Waktu Mulai</label>
                        <input type="datetime-local" id="edit_log_created_at" class="border border-gray-300 rounded-xl p-2.5 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="edit_log_end_time" id="edit_log_end_time_label" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Waktu Selesai</label>
                        <input type="datetime-local" id="edit_log_end_time" class="border border-gray-300 rounded-xl p-2.5 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="edit_log_remark" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Remark</label>
                        <textarea id="edit_log_remark" rows="3" class="border border-gray-300 rounded-xl p-2.5 w-full text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ketik remark di sini..."></textarea>
                    </div>
                </div>

                <div class="flex justify-end mt-6 space-x-2">
                    <button id="closeEditLogModal" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl text-xs font-semibold hover:bg-gray-200 transition">Cancel</button>
                    <button id="saveEditLog" class="bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-xs font-semibold hover:bg-indigo-700 transition">Save Changes</button>
                </div>
            </div>
        </div>



        <div class="container mx-auto py-6" id="logsContainer">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Mould Change Log Card -->
                <div class="bg-white p-5 border border-indigo-50 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                    <h2 class="text-md font-bold mb-4 text-indigo-700 tracking-tight flex items-center justify-between">
                        <span>🔄 Mould Change Log</span>
                        <span class="bg-indigo-50 text-indigo-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">{{ $mouldChangeLogs->count() }} Data</span>
                    </h2>
                    @if($mouldChangeLogs->isEmpty())
                        <p class="text-gray-400 text-xs italic py-4 text-center">Tidak ada data mould change hari ini</p>
                    @else
                        <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                            @foreach($mouldChangeLogs as $log)
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 shadow-sm hover:shadow-md transition-all">
                                    <div class="flex justify-between items-center mb-2 border-b border-slate-200/60 pb-1.5">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Detail Log</span>
                                        <button 
                                            onclick="openEditLogModal('mould', {{ $log->id }}, '{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }}', '{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '' }}', @js($log->remark))"
                                            class="text-blue-600 hover:text-blue-800 text-xs font-semibold flex items-center space-x-1"
                                        >
                                            <span>✏️ Edit</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-y-1.5 text-[11px] gap-x-2">
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Mulai</span>
                                            <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Selesai</span>
                                            <span class="font-semibold text-gray-800">{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Item Code</span>
                                            <span class="font-bold text-indigo-600">{{ $log->item_code ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">PIC</span>
                                            <span class="font-semibold text-gray-800">{{ $log->pic }}</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Durasi Pengerjaan</span>
                                            <span class="font-bold text-emerald-600">{{ $log->total_pengerjaan ?? '-' }} menit</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Remark</span>
                                            <span class="text-gray-700 italic block">{{ $log->remark ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Adjust Machine Log Card -->
                <div class="bg-white p-5 border border-indigo-50 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                    <h2 class="text-md font-bold mb-4 text-blue-700 tracking-tight flex items-center justify-between">
                        <span>⚙️ Adjust Machine Log</span>
                        <span class="bg-blue-50 text-blue-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">{{ $adjustMachineLogs->count() }} Data</span>
                    </h2>
                    @if($adjustMachineLogs->isEmpty())
                        <p class="text-gray-400 text-xs italic py-4 text-center">Tidak ada data adjust machine hari ini</p>
                    @else
                        <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                            @foreach($adjustMachineLogs as $log)
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 shadow-sm hover:shadow-md transition-all">
                                    <div class="flex justify-between items-center mb-2 border-b border-slate-200/60 pb-1.5">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Detail Log</span>
                                        <button 
                                            onclick="openEditLogModal('adjust', {{ $log->id }}, '{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }}', '{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '' }}', @js($log->remark))"
                                            class="text-blue-600 hover:text-blue-800 text-xs font-semibold flex items-center space-x-1"
                                        >
                                            <span>✏️ Edit</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-y-1.5 text-[11px] gap-x-2">
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Mulai</span>
                                            <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Selesai</span>
                                            <span class="font-semibold text-gray-800">{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Item Code</span>
                                            <span class="font-bold text-indigo-600">{{ $log->item_code ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">PIC</span>
                                            <span class="font-semibold text-gray-800">{{ $log->pic }}</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Durasi Pengerjaan</span>
                                            <span class="font-bold text-emerald-600">{{ $log->total_pengerjaan ?? '-' }} menit</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Remark</span>
                                            <span class="text-gray-700 italic block">{{ $log->remark ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Repair Machine Log Card -->
                <div class="bg-white p-5 border border-indigo-50 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                    <h2 class="text-md font-bold mb-4 text-red-700 tracking-tight flex items-center justify-between">
                        <span>🔧 Repair Machine Log</span>
                        <span class="bg-red-50 text-red-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">{{ $repairMachineLogs->count() }} Data</span>
                    </h2>
                    @if($repairMachineLogs->isEmpty())
                        <p class="text-gray-400 text-xs italic py-4 text-center">Tidak ada data repair machine hari ini</p>
                    @else
                        <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                            @foreach($repairMachineLogs as $log)
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 shadow-sm hover:shadow-md transition-all">
                                    <div class="flex justify-between items-center mb-2 border-b border-slate-200/60 pb-1.5">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Detail Log</span>
                                        <button 
                                            onclick="openEditLogModal('repair', {{ $log->id }}, '{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }}', '{{ $log->finish_repair ? \Carbon\Carbon::parse($log->finish_repair)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '' }}', @js($log->remark))"
                                            class="text-blue-600 hover:text-blue-800 text-xs font-semibold flex items-center space-x-1"
                                        >
                                            <span>✏️ Edit</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-y-1.5 text-[11px] gap-x-2">
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Mulai</span>
                                            <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Waktu Selesai</span>
                                            <span class="font-semibold text-gray-800">{{ $log->finish_repair ? \Carbon\Carbon::parse($log->finish_repair)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">Masalah / Problem</span>
                                            <span class="font-semibold text-red-600">{{ $log->problem ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block text-[9px] uppercase">PIC</span>
                                            <span class="font-semibold text-gray-800">{{ $log->pic }}</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Durasi Pengerjaan</span>
                                            <span class="font-bold text-emerald-600">{{ $log->total_pengerjaan ?? '-' }} menit</span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-gray-400 block text-[9px] uppercase">Remark</span>
                                            <span class="text-gray-700 italic block">{{ $log->remark ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        

        <div x-data="scanModeHandler({{ session('deactivateScanMode') ? 'true' : 'false' }})" x-init="initialize()" x-show="ready" x-cloak x-show="ready" x-cloak class="py-4">
            <!-- Scan Mode Toggle Section -->
            <div class="px-6">
                <div x-show="scanMode" id="scanModeBanner"
                    class="p-3 bg-yellow-100 border border-yellow-500 text-yellow-700 rounded mb-4" x-cloak>
                    <strong>Scan Mode is Active!</strong> Only the Scan Barcode section is visible. Please scan your
                    items.
                </div>
            </div>

            <!-- Toggle Scan Mode -->
          

            <div class="flex justify-end px-6">
                <button x-on:click="toggleScanMode()" x-text="scanMode ? 'Deactivate Scan Mode' : 'Activate Scan Mode'"
                    :class="scanMode ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    class="py-2 px-4 text-white font-semibold rounded-md transition">
                </button>
            </div>

            <div x-show="scanMode && !verified" class="mt-4 px-6">
                <label for="nik" class="block font-semibold">Enter Your NIK:</label>
                <input type="text" id="nik" x-model="nikInput"
                    class="border border-gray-300 rounded-md w-full p-2 mt-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Scan or enter your NIK"/>

                <label for="password" class="block font-semibold mt-4">Enter Your Password:</label>
                <input type="password" id="password" x-model="passwordInput"
                    class="border border-gray-300 rounded-md w-full p-2 mt-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter your password"/>

                <button x-on:click="verifyNIK()" 
                    class="mt-2 py-2 px-4 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                    Verify NIK
                </button>
            </div>

            <!-- Other Sections to be Hidden in Scan Mode -->
            <div x-show="!scanMode" class="not-scan-section mt-2" x-cloak>
                <!-- Active Job Section -->

                <div class="mx-auto sm:px-4 lg:px-6 pt-2">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <div class="text-gray-900">
                            <span class="font-bold ">Active Job:</span>
                            
                            @if ($itemCode)
                                <span class="text-blue-500">{{ $itemCode }}</span>
                                <a href="{{ route('reset.job') }}"
                                    class="ms-2 text-red-400 border-red-400 bg-red-50 hover:bg-red-600 hover:text-white border py-2 px-2 rounded-md">
                                    Reset job</a>
                            @else
                                <span class="text-red-500">No item code scanned</span>
                                <p class="text-gray-400 text-sm">You must scan the master list barcode as assigned in
                                    the
                                    daily item codes.</p>
                                @if ($datas->isNotEmpty())
                                    <div class="mt-1">
                                        <form action="{{ route('update.machine_job') }}" method="POST">
                                            @csrf
                                            <!-- <div>
                                                <input type="text" id="item_code" name="item_code" required
                                                    class="px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('item_code') border-red-500 @enderror"
                                                    placeholder="Item Code" />
                                                <button type="submit"
                                                    class="py-1 px-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition inline-flex">
                                                    Update Job
                                                </button>

                                                @error('item_code')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div> -->

                                            <div>
                                                <select id="dic_id" name="dic_id" required
                                                    class="px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('dic_id') border-red-500 @enderror">
                                                    <option value="">-- Pilih Item Code --</option>
                                                    @foreach($todayitems as $item)
                                                        <option value="{{ $item->id }}">
                                                            {{ $item->item_code }} - Shift {{ $item->shift }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <button type="submit"
                                                    class="py-1 px-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition inline-flex">
                                                    Update Job
                                                </button>

                                                @error('dic_id')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            @endif

                        </div>
                    </div>
                </div>

                <!-- Files Section -->
                <div class="mx-auto sm:px-4 lg:px-6 pt-2">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        @if ($itemCode)
                            <section>
                                @php
                                    $activeFiles = $files[$itemCode] ?? collect();
                                @endphp
                                @if ($activeFiles->count() > 0)
                                    <div class="font-bold text-2xl">Files</div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mt-4">
                                        @foreach ($activeFiles as $file)
                                            <a href="{{ asset('storage/files/' . $file->name) }}"
                                                data-fancybox="gallery" data-caption="{{ $file->name }}">
                                                <img class="w-full h-auto rounded-lg shadow-lg hover:shadow-2xl transition-transform transform hover:scale-105"
                                                    src="{{ asset('storage/files/' . $file->name) }}"
                                                    alt="{{ $file->name }}" />
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-red-500 text-sm my-2">No files attached to this item code.</p>
                                @endif
                            </section>
                        @else
                            <h1 class="font-bold text-xl">Files</h1>
                            <p class="text-red-500 text-sm my-2">Please scan the master list first.</p>
                        @endif
                    </div>
                </div>


                <!-- Daily Production Plan Section -->
                <div id="productionPlanContainer" class="mx-auto sm:px-4 lg:px-6 pt-6">
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-xl font-bold mb-2">Daily Production Plan <span
                                    class="text-gray-400">(Assigned
                                    Item Code)</span></h3>
                            @if ($datas->isNotEmpty())
                                <table
                                    class="min-w-full bg-white shadow-md rounded-lg overflow-hidden text-center mt-3">
                                    <thead class="bg-indigo-100">
                                        <tr>
                                            <th class="py-1 px-2 text-gray-700">Item Code</th>
                                            <th class="py-1 px-2 text-gray-700">Pair</th>
                                            <th class="py-1 px-2 text-gray-700">Start Date - End Date</th>
                                            <th class="py-1 px-2 text-gray-700">Shift</th>
                                            <th class="py-1 px-2 text-gray-700">Quantity</th>
                                            <th class="py-1 px-2 text-gray-700">Status</th>
                                            <th class="py-1 px-2 text-gray-700">Cycle Time</th>
                                            <th class="py-1 px-2 text-gray-700">Lot Material</th>
                                            <th class="py-1 px-2 text-gray-700">Lot Accessories</th>
                                            <th class="py-1 px-2 text-gray-700">Berat Purging</th>
                                            <th class="py-1 px-2 text-gray-700">Remark</th>
                                            <!-- <th class="py-1 px-2 text-gray-700">Loss Package Quantity</th> -->
                                            <!-- <th class="py-1 px-2 text-gray-700">Actual Quantity</th> -->
                                            @if ($itemCode)
                                                <th class="py-1 px-2 text-gray-700">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($datas as $data)
                                            @php
                                                $startTime = \Carbon\Carbon::parse($data->start_time)->format('H:i');
                                                $endTime = \Carbon\Carbon::parse($data->end_time)->format('H:i');
                                                $startDate = \Carbon\Carbon::parse($data->start_date)->format('d/m/Y'); // Format start date as dd/mm/yyyy
                                                $endDate = \Carbon\Carbon::parse($data->end_date)->format('d/m/Y'); // Format end date as dd/mm/yyyy
                                                $pairCode = $data->masterItem->pair ?? null;
                                            @endphp

                                            <tr class="bg-white border-b text-center">
                                                <td class="py-1 px-2">{{ $data->item_code }}</td>
                                                <td class="py-1 px-2">{{ $pairCode ?? '-' }}</td> <!-- Kolom pair -->
                                                <td class="py-1 px-2">{{ $startDate }} - {{ $endDate }}</td>
                                                <td class="py-1 px-2">{{ $data->shift }} ({{ $startTime }} -
                                                    {{ $endTime }})</td>
                                                <td class="py-1 px-2">{{ $data->quantity }}</td>
                                                <td class="py-1 px-2">
                                                    {{ $data->is_done === 1 ? 'Selesai' : 'Belum Selesai' }}
                                                </td>
                                                <td class="py-1 px-2">
                                                    @if ($data->temporal_cycle_time)
                                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-sm">
                                                            {{ $data->temporal_cycle_time }} detik
                                                        </span>
                                                    @else
                                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-sm italic">
                                                            Belum di-assign
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-1 px-2">
                                                    @if ($data->material_lot)
                                                        <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-sm font-black uppercase">
                                                            {{ $data->material_lot }}
                                                        </span>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full text-sm italic">
                                                            -
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-1 px-2">
                                                    @if ($data->accessoryLots && $data->accessoryLots->isNotEmpty())
                                                        <div class="flex flex-col gap-1 items-center">
                                                            @foreach($data->accessoryLots as $acc)
                                                                <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full text-xs font-bold">
                                                                    {{ $acc->accessory_name }}: {{ $acc->accessory_lot }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full text-sm italic">
                                                            -
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-1 px-2">
                                                    @if ($data->resin_usage)
                                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-sm font-semibold">
                                                            {{ $data->resin_usage }} KG
                                                        </span>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-sm italic">
                                                            -
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-1 px-2">{{ $data->remark }}</td>
                                                <!-- <td class="py-1 px-2">{{ $data->loss_package_quantity }}</td> -->
                                                <!-- <td class="py-1 px-2">{{ $data->actual_quantity }}</td> -->
                                                <td class="py-1 px-2">
                                                    <!-- @if ($itemCode && $data->item_code === $itemCode)
                                                        <form action="{{ route('generate.itemcode.barcode', ['item_code' => $data->item_code, 'quantity' => $data->quantity]) }}"
                                                            method="get">
                                                            <button class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded">
                                                                Generate Barcode
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button class="px-2 py-1 bg-gray-400 text-white rounded cursor-not-allowed" disabled>
                                                            Generate Barcode
                                                        </button>
                                                    @endif -->

                                                    <button 
                                                            type="button" 
                                                            class="px-2 py-1 bg-green-500 hover:bg-green-600 text-white rounded mt-1"
                                                            onclick="openCycleTimeModal('{{ $data->id }}', '{{ $data->temporal_cycle_time ?? '' }}')"
                                                        >
                                                            Set Cycle Time
                                                    </button>

                                                    <button 
                                                        type="button" 
                                                        class="px-2 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded mt-1 font-bold"
                                                        onclick="openMaterialLotModal('{{ $data->id }}', '{{ $data->material_lot ?? '' }}')"
                                                    >
                                                        Set Lot Material
                                                    </button>

                                                    <button 
                                                        type="button" 
                                                        class="px-2 py-1 bg-purple-700 hover:bg-purple-800 text-white rounded mt-1 font-bold"
                                                        onclick="openAccessoryLotOpModal('{{ $data->id }}')"
                                                    >
                                                        Set Lot Accessories
                                                    </button>

                                                    <button 
                                                        type="button" 
                                                        class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded mt-1"
                                                        onclick="openRemarkModal('{{ $data->id }}', '{{ $data->remark ?? '' }}')"
                                                    >
                                                        Set Remark
                                                    </button>

                                                    <button 
                                                        type="button" 
                                                        class="px-2 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded mt-1"
                                                        onclick="openTemporalCavityModal('{{ $data->id }}', '{{ $data->temporal_cavity ?? '' }}')"
                                                    >
                                                        Set Temporal Cavity
                                                    </button>

                                                    <button 
                                                        type="button" 
                                                        class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded mt-1"
                                                        onclick="openResinUsageModal('{{ $data->id }}', '{{ $data->resin_usage ?? '' }}')"
                                                    >
                                                        Set Berat Purging
                                                    </button>

                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div id="remarkDICModal" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden flex items-center justify-center">
                                    <div class="bg-white p-6 rounded shadow-md w-96">
                                        <h2 class="text-lg font-semibold mb-2">Set Remark</h2>
                                        <input type="hidden" id="remark_dic_id">
                                        <textarea id="remark_dic_input" class="w-full border rounded px-2 py-1" rows="4" placeholder="Tulis remark..."></textarea>
                                        <div class="mt-4 text-right">
                                            <button onclick="closeRemarkModal()" class="px-3 py-1 bg-gray-400 text-white rounded">Cancel</button>
                                            <button onclick="saveRemark()" class="px-3 py-1 bg-blue-600 text-white rounded">Save</button>
                                        </div>
                                    </div>
                                </div>

                                <div id="cycleTimeModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                                        <h2 class="text-lg font-semibold mb-4">Set Temporal Cycle Time</h2>
                                        <form id="cycleTimeForm" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="data_id" id="dataIdInput">
                                            <label for="cycle_time" class="block text-sm font-medium text-gray-700 mb-1">Temporal Cycle Time</label>
                                            <input type="text" id="cycleTimeInput" name="temporal_cycle_time" class="w-full border rounded p-2 mb-4" required>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="closeCycleTimeModal()" class="bg-gray-400 text-white px-3 py-1 rounded">Cancel</button>
                                                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div id="materialLotModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                                        <h2 class="text-lg font-semibold mb-4">Set Lot Material</h2>
                                        <form id="materialLotForm" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="data_id" id="mlDataIdInput">
                                            <label for="material_lot" class="block text-sm font-medium text-gray-700 mb-1">Kode Lot Material (Alphanumeric)</label>
                                            <input type="text" id="materialLotInput" name="material_lot" placeholder="Contoh: LOT-2026-A123" class="w-full border rounded p-2 mb-4 uppercase" oninput="this.value = this.value.toUpperCase()">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="closeMaterialLotModal()" class="bg-gray-400 text-white px-3 py-1 rounded">Cancel</button>
                                                <button type="submit" class="bg-teal-600 text-white px-3 py-1 rounded hover:bg-teal-700">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div id="accessoryLotOpModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative text-left">
                                        <h2 class="text-lg font-semibold mb-3 text-purple-900 flex items-center justify-between">
                                            <span>Kelola Lot Accessories</span>
                                            <button type="button" onclick="closeAccessoryLotOpModal()" class="text-gray-400 hover:text-gray-600">✕</button>
                                        </h2>
                                        
                                        <!-- Form add accessory -->
                                        <div class="bg-purple-50 p-3 rounded mb-4 border border-purple-200">
                                            <span class="block text-xs font-bold text-purple-900 mb-2">Tambah Accessory Baru</span>
                                            <div class="space-y-2 text-xs">
                                                <div>
                                                    <label class="block text-gray-600 font-semibold mb-0.5">Jenis Accessories (Alphanumeric)</label>
                                                    <input type="text" id="accOpName" placeholder="Contoh: Screw / Label / Box" class="w-full border rounded p-1.5 bg-white text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-gray-600 font-semibold mb-0.5">Kode Lot Accessories (Alphanumeric + Symbol)</label>
                                                    <input type="text" id="accOpLot" placeholder="Contoh: LOT-ACC#123/B" class="w-full border rounded p-1.5 bg-white text-xs">
                                                </div>
                                                <div class="text-right pt-1">
                                                    <button type="button" onclick="submitAddAccessoryLotOp()" class="bg-purple-700 hover:bg-purple-800 text-white px-3 py-1 rounded font-bold text-xs">
                                                        + Tambah
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- List existing accessories -->
                                        <div>
                                            <span class="block text-xs font-bold text-gray-700 mb-1">Daftar Accessories Terdaftar:</span>
                                            <div id="accOpListContainer" class="space-y-1 max-h-40 overflow-y-auto pr-1 text-xs">
                                                <div class="text-gray-400 italic text-center py-2">Loading...</div>
                                            </div>
                                        </div>

                                        <div class="flex justify-end mt-4 pt-2 border-t">
                                            <button type="button" onclick="closeAccessoryLotOpModal()" class="bg-gray-400 text-white px-3 py-1 rounded text-xs">Tutup</button>
                                        </div>
                                    </div>
                                </div>

                                 <div id="resinUsageModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                     <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                                         <h2 class="text-lg font-semibold mb-4">Set Berat Purging</h2>

                                         <form id="resinUsageForm" method="POST">
                                             @csrf
                                             @method('PUT')

                                             <input type="hidden" name="data_id" id="ruDataIdInput">

                                             <label for="resin_usage" class="block text-sm font-medium text-gray-700 mb-1">
                                                 Berat Purging (KG)
                                             </label>

                                             <input 
                                                 type="number"
                                                 step="0.01"
                                                 id="ruInput"
                                                 name="resin_usage"
                                                 class="w-full border rounded p-2 mb-4"
                                                 placeholder="Contoh: 3.5"
                                                 required
                                             >

                                             <div class="flex justify-end gap-2">
                                                 <button 
                                                     type="button" 
                                                     onclick="closeResinUsageModal()" 
                                                     class="bg-gray-400 text-white px-3 py-1 rounded"
                                                 >
                                                     Cancel
                                                 </button>

                                                 <button 
                                                     type="submit" 
                                                     class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700"
                                                 >
                                                     Submit
                                                 </button>
                                             </div>
                                         </form>
                                     </div>
                                 </div>
                                <div id="temporalCavityModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                                        <h2 class="text-lg font-semibold mb-4">Set Temporal Cavity</h2>

                                        <form id="temporalCavityForm" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="data_id" id="tcDataIdInput">

                                            <label for="temporal_cavity" class="block text-sm font-medium text-gray-700 mb-1">
                                                Temporal Cavity
                                            </label>

                                            <input 
                                                type="number"
                                                id="tcInput"
                                                name="temporal_cavity"
                                                class="w-full border rounded p-2 mb-4"
                                                required
                                            >

                                            <div class="flex justify-end gap-2">
                                                <button 
                                                    type="button" 
                                                    onclick="closeTemporalCavityModal()" 
                                                    class="bg-gray-400 text-white px-3 py-1 rounded"
                                                >
                                                    Cancel
                                                </button>

                                                <button 
                                                    type="submit" 
                                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700"
                                                >
                                                    Submit
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>



                            @else
                                <p class="text-red-500 text-sm">No assigned item code yet</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scan Barcode Section -->
            <div x-show="scanMode && verified" class="mx-auto sm:px-4 lg:px-6 pt-6" x-cloak>
                <button 
                    @click="resetVerification()" 
                    class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg shadow-md hover:bg-red-600 transition duration-200">
                    Reset Verification
                </button>
            
                <div class="flex gap-6 items-start w-full">
                    <!-- Profile Section -->
                    <div id="dashboardSection" class="bg-white p-5 rounded-2xl shadow-md w-full sm:w-[320px] flex flex-col items-center border border-gray-100">
                        <div class="flex flex-col items-center text-center w-full">
                            <img id="profileImage" class="w-28 h-28 rounded-full border-4 border-indigo-100 object-cover shadow-sm" 
                                src="{{ asset('default-avatar.png') }}" alt="Profile Picture">
                            <h2 class="mt-3 text-lg font-bold text-gray-800">
                                Welcome, <span id="operatorName"></span>
                            </h2>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full mt-1">Operator Utama (1)</span>
                        </div>

                        <!-- Additional Operators Section (Operator 2 & 3) -->
                        <div class="w-full mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Operator Tambahan</span>
                                <span id="operatorCountBadge" class="text-[10px] font-black bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">1 / 3</span>
                            </div>

                            <!-- Rendered list of additional active operators -->
                            <div id="activeAdditionalOperatorsList" class="space-y-2 mb-3"></div>

                            <!-- Dropdown select wrapper to add 1 by 1 -->
                            <div id="addOperatorSelectWrapper" class="hidden mb-2">
                                <div class="space-y-2 p-2.5 bg-gray-50 rounded-xl border border-gray-200">
                                    <label class="block text-[11px] font-bold text-gray-700">Pilih Operator Tambahan:</label>
                                    <select id="select_additional_operator" class="w-full border border-gray-300 rounded-xl text-xs p-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                                        <option value="">-- Pilih Operator --</option>
                                        @foreach(($allOperators ?? []) as $op)
                                            <option value="{{ $op->name }}">
                                                {{ $op->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="flex gap-2 justify-end">
                                        <button type="button" id="btnCancelAddOperator" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                            Batal
                                        </button>
                                        <button type="button" id="btnConfirmAddOperator" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow transition">
                                            + Tambah
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" id="btnShowAddOperator" class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition">
                                <span>➕</span> <span>Tambah Operator (Maks 3)</span>
                            </button>
                        </div>
                    </div>
                  

                    <!-- SPK Table Section -->
                        @php
                            $pairCode = $activeDIC->masterItem->pair ?? null;
                            $hasPair = $pairCode !== null && $pairCode !== '0';
                        @endphp
                    <div id="pekerjaanTableContainer" class="bg-white overflow-hidden shadow-md rounded-lg p-4 flex-1">
                      <span class="text-xl font-bold">
                            Detail Pekerjaan - {{ optional($activeDIC)->item_code ?? '-' }} 
                            (Shift: {{ optional($activeDIC)->shift ?? '-' }})
                        </span>
                        <table class="w-full bg-white mt-2 rounded-md shadow-md overflow-hidden">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4">Item Code</th>
                                    @if ($hasPair)
                                        <th class="py-2 px-4">Pair Code</th>
                                    @endif
                                    <th class="py-2 px-4">Quantity</th>
                                    <th class="py-2 px-4">Total Box Yang sudah discan</th>
                                    <th class="py-2 px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                           @if ($activeDIC)
                                @if ($hasPair)
                                    {{-- Jika punya pair, tampilkan versi table khusus --}}
                                    <tr class="bg-white text-center">
                                        <td class="py-2 px-4">{{ $activeDIC['item_code'] }} / {{ $pairCode }}</td>
                                        <td class="py-2 px-4">{{ $totalScannedQuantity }}/{{ $activeDIC['quantity'] }}</td>
                                        <td class="py-2 px-4">{{ $scannedCount }}</td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button 
                                                    onclick="document.getElementById('detailModal').showModal()" 
                                                    class="bg-blue-500 text-white px-4 py-1 rounded-md text-sm shadow hover:bg-blue-600 transition duration-150">
                                                    Detail Remark
                                                </button>
                                                <button 
                                                    onclick="document.getElementById('detailDataModal').showModal()" 
                                                    class="bg-green-500 text-white px-4 py-1 rounded-md text-sm shadow hover:bg-green-600 transition duration-150">
                                                    Detail Data
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    {{-- Versi default tanpa pair --}}
                                    <tr class="bg-white text-center">
                                        <td class="py-2 px-4">{{ $activeDIC['item_code'] }}</td>
                                        <td class="py-2 px-4">{{ $totalScannedQuantity }}/{{ $activeDIC['quantity'] }}</td>
                                        <td class="py-2 px-4">{{ $scannedCount }}</td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button 
                                                    onclick="document.getElementById('detailModal').showModal()" 
                                                    class="bg-blue-500 text-white px-4 py-1 rounded-md text-sm shadow hover:bg-blue-600 transition duration-150">
                                                    Detail Remark
                                                </button>
                                                <button 
                                                    onclick="document.getElementById('detailDataModal').showModal()" 
                                                    class="bg-green-500 text-white px-4 py-1 rounded-md text-sm shadow hover:bg-green-600 transition duration-150">
                                                    Detail Data
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-2">No data for selected item code</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                        <div x-data="{ openLog: false }" class="bg-white border border-gray-200 rounded-xl shadow-lg mt-6 p-6 transition-all duration-300">
                            <div @click="openLog = !openLog" :class="openLog ? 'mb-6 pb-4 border-b border-gray-100' : ''" class="flex justify-between items-center cursor-pointer select-none transition-all duration-300">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                        📦 Output Log Produksi
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Dicatat per produk keluar berdasarkan cycle time / cavity mesin
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- <span class="bg-indigo-50 text-indigo-700 font-semibold px-3 py-1.5 rounded-full text-xs border border-indigo-100">
                                        Quantity per shot: <strong>{{ $activeDIC ? (!empty($activeDIC->temporal_cavity) && $activeDIC->temporal_cavity > 0 ? $activeDIC->temporal_cavity : ($activeDIC->masterItem->cavity ?? 1)) : 1 }}</strong>
                                    </span> -->
                                    <span class="bg-emerald-50 text-emerald-700 font-semibold px-3 py-1.5 rounded-full text-xs border border-emerald-100">
                                        Total Logs Today: <strong id="total-logs-count">{{ $outputLogs->count() }}</strong>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="openLog ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <div x-show="openLog" x-transition>
                                <!-- Form Submit Log -->
                                <form id="output-log-form" action="{{ route('production.output-log.store') }}" method="POST" class="mb-6 flex gap-4 items-center">
                                    @csrf
                                    <input type="hidden" name="operator_name" :value="localStorage.getItem('operator_name') || ''" />
                                    
                                    <button type="submit" 
                                        class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition duration-200 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Log Output
                                    </button>
                                </form>

                                <!-- Table Log -->
                                <div class="rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                    {{-- Sticky thead --}}
                                    <table class="min-w-full bg-white text-center text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="py-3 px-4 text-gray-600 font-bold uppercase tracking-wider text-xs">Waktu (WIB)</th>
                                                <th class="py-3 px-4 text-gray-600 font-bold uppercase tracking-wider text-xs">Operator</th>
                                                <th class="py-3 px-4 text-gray-600 font-bold uppercase tracking-wider text-xs">Quantity</th>
                                            </tr>
                                        </thead>
                                    </table>
                                    {{-- Scrollable tbody — max ~5 rows (each row ≈ 48px, 5 rows = 240px) --}}
                                    <div class="overflow-y-auto" style="max-height: 240px;">
                                        <table class="min-w-full bg-white text-center text-sm">
                                            <tbody id="output-logs-tbody" class="divide-y divide-gray-100">
                                                @forelse ($outputLogs as $log)
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <td class="py-3 px-4 font-semibold text-gray-700 w-1/3">
                                                            {{ $log->logged_at ? $log->logged_at->format('H:i:s') : '-' }}
                                                        </td>
                                                        <td class="py-3 px-4 text-gray-700 w-1/3">
                                                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                                                {{ $log->operator_name }}
                                                            </span>
                                                        </td>
                                                        <td class="py-3 px-4 text-gray-900 font-bold w-1/3">
                                                            {{ $log->quantity }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-gray-400 py-6 italic bg-gray-50/20">
                                                            Belum ada log output untuk DIC ini hari ini.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                            <dialog id="detailDataModal" class="p-6 rounded-lg w-11/12 max-w-4xl">
                                <h3 class="text-xl font-semibold mb-4">Detail Data Scan SPK</h3>
                                <table class="w-full border-collapse border border-gray-300 text-left text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-3 py-2">ID</th>
                                            <th class="border border-gray-300 px-3 py-2">SPK Code</th>
                                            <th class="border border-gray-300 px-3 py-2">DIC ID</th>
                                            <th class="border border-gray-300 px-3 py-2">Item Code</th>
                                            <th class="border border-gray-300 px-3 py-2">Warehouse</th>
                                            <th class="border border-gray-300 px-3 py-2">Quantity</th>
                                            <th class="border border-gray-300 px-3 py-2">Label</th>
                                            <th class="border border-gray-300 px-3 py-2">User</th>
                                            <th class="border border-gray-300 px-3 py-2">Created At</th>
                                            <th class="border border-gray-300 px-3 py-1">SAP Status</th>
                                            <th class="border border-gray-300 px-3 py-2">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailDataModalTbody">
                                        @forelse ($spkData as $scan)
                                            <tr>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->id }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->spk_code }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->dic_id }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->item_code }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->warehouse }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->quantity }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->label }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->user }}</td>
                                                <td class="border border-gray-300 px-3 py-1">{{ $scan->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }}</td>
                                                <td class="border border-gray-300 px-3 py-1 text-center">
                                                    @if($scan->summary)
                                                        @if($scan->summary->sap_sent == 1)
                                                            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">
                                                                ✓ Terkirim
                                                            </span>
                                                        @elseif($scan->summary->sap_sent == 99)
                                                            <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-1 rounded-full">
                                                                ⊘ Diabaikan
                                                            </span>
                                                        @else
                                                            <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-1 rounded-full">
                                                                ⏳ Pending
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-400 text-xs">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                <form method="POST" action="{{ route('spk-scan.destroy', $scan->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Yakin ingin hapus scan ini?')" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="10" class="text-center py-4">Tidak ada data scan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="mt-4 text-right">
                                    <button onclick="document.getElementById('detailDataModal').close()" class="bg-red-500 text-white px-4 py-1 rounded hover:bg-red-600">
                                        Close
                                    </button>
                                </div>
                            </dialog>

                            <dialog id="detailModal" class="rounded-md shadow-lg p-4 w-full max-w-3xl">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold">Detail Per Jam - {{ $activeDIC['item_code'] ?? '' }}</h3>
                                    <button onclick="document.getElementById('addHourlyRemarksModal').showModal()" 
                                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                        Add Hourly Remarks
                                    </button>
                                    <button onclick="document.getElementById('detailModal').close()" class="text-red-500 hover:text-red-700">X</button>
                                </div>

                                <dialog id="addHourlyRemarksModal" class="rounded-md p-6 w-full max-w-md bg-white shadow">
                                    <form id="addHourlyRemarksForm" method="POST" action="{{ route('hourly-remarks.store') }}" x-data="{ nikInput: localStorage.getItem('nik') || '' }">
                                        @csrf
                                        <h3 class="text-lg font-bold mb-4">Tambah Hourly Remarks</h3>

                                        <label for="start_time" class="block text-sm font-semibold mb-1">Pilih Jam Mulai</label>
                                        <select name="start_time" id="start_time" required
                                                class="w-full border border-gray-300 rounded px-3 py-2 mb-4">
                                            @php
                                                $start = \Carbon\Carbon::parse('07:30');
                                                $end = \Carbon\Carbon::parse('7:30')->addDay(); // keesokan harinya
                                            @endphp
                                            @while ($start < $end)
                                                <option value="{{ $start->format('H:i') }}">
                                                    {{ $start->format('H:i') }}
                                                </option>
                                                @php $start->addHour(); @endphp
                                            @endwhile
                                        </select>

                                        {{-- Hidden Inputs --}}
                                        <input type="hidden" name="uniqueData" value='@json($itemCollections)' />
                                        <input type="hidden" name="datas" value='@json($datas)' />
                                        <input type="hidden" name="activedic" value='@json($activeDIC)' />
                                        <input type="hidden" id="nik" name="nik" x-model="nikInput" />

                                        <div class="flex justify-end gap-2 mt-4">
                                            <button type="button" onclick="document.getElementById('addHourlyRemarksModal').close()"
                                                    class="px-3 py-1 rounded border text-gray-700 hover:bg-gray-100">Cancel</button>
                                            <button type="submit"
                                                    class="px-4 py-1 rounded bg-green-600 text-white hover:bg-green-700">Simpan</button>
                                        </div>
                                    </form>
                                </dialog>

                                

                                <table class="w-full border border-gray-200 text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="py-2 px-4 border">Jam Mulai</th>
                                            <th class="py-2 px-4 border">Jam Selesai</th>
                                            <th class="py-2 px-4 border">Target</th>
                                            <th class="py-2 px-4 border">Actual Scan</th>
                                            <th class="py-2 px-4 border">Actual Production</th>
                                            <th class="py-2 px-4 border">NG</th>
                                            <th class="py-2 px-4 border">Status</th>
                                            <th class="py-2 px-4 border">Remark</th>
                                            <th class="py-2 px-4 border">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailRemarkModalTbody">
                                        @if (!empty($hourlyRemarksActiveDIC))
                                        @foreach ($hourlyRemarksActiveDIC as $slot)
                                                <tr class="text-center">
                                                    <td class="py-2 px-4 border">{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}</td>
                                                    <td class="py-2 px-4 border">{{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</td>
                                                    <td class="py-2 px-4 border">{{ $slot->target }}</td>
                                                    <td class="py-2 px-4 border">{{ $slot->actual }}</td>
                                                    <td class="py-2 px-4 border">
                                                        {{ $slot->actual_production ? $slot->actual_production : 0 }}
                                                    </td>
                                                      <td class="py-2 px-4 border">
                                                        {{ $slot->NG ? $slot->NG : 0 }}
                                                    </td>
                                                    <td class="py-2 px-4 border">
                                                        @if ($slot->is_achieve)
                                                            <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Tercapai</span>
                                                        @else
                                                            <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Tidak Tercapai</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2 px-4 border">{{ $slot->remark ?? '-' }}</td>
                                                    <td class="py-2 px-4 border">
                                                        <button 
                                                            onclick="editRemark({{ $slot->id }}, @js($slot->remark))"
                                                            class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded hover:bg-red-600"
                                                        >
                                                            Edit Remark
                                                        </button>
                                                        <button 
                                                            class="ml-2 bg-blue-500 text-white text-xs px-2 py-1 rounded hover:bg-blue-600"
                                                            onclick="openProductionModal({{ $slot->id }}, {{ $slot->actual_production ?? 0 }})"
                                                        >
                                                            Add Actual Production
                                                        </button>

                                                        <button 
                                                            class="ml-2 bg-purple-500 text-white text-xs px-2 py-1 rounded hover:bg-purple-600"
                                                            data-id="{{ $slot->id }}"
                                                            data-ng='@json($slot->ngDetails)'
                                                            onclick="openNgModal(this)"
                                                        >
                                                            Add NG
                                                        </button>
                                                    </td>
                                                    </td>
                                                </tr>

                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="3" class="text-center py-2 text-gray-500">No hourly data available</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                                                    <div id="productionModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
                                                        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                                                            <h2 class="text-lg font-semibold mb-4">Update Actual Production</h2>
                                                            <form id="productionForm" method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" id="productionSlotId" name="id">
                                                                <label for="actual_production" class="block text-sm font-medium text-gray-700 mb-1">Actual Production</label>
                                                                <input type="number" id="actualProductionInput" name="actual_production" class="w-full border rounded p-2 mb-4" required min="0">
                                                                <div class="flex justify-end gap-2">
                                                                    <button type="button" onclick="closeProductionModal()" class="bg-gray-400 text-white px-3 py-1 rounded">Cancel</button>
                                                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Submit</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <div id="ngModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center">
                                                        <div class="bg-white p-6 rounded shadow-lg w-[450px]">
                                                            
                                                            <!-- Existing NG List -->
                                                            <h2 class="text-lg font-semibold mb-3">NG Details</h2>
                                                            <div id="ngList" class="mb-4 max-h-40 overflow-y-auto border p-2 rounded">
                                                                <!-- Diisi via JS -->
                                                            </div>

                                                            <!-- Add New NG -->
                                                            <h3 class="text-md font-semibold mb-2">Add NG</h3>
                                                            <form id="ngForm" method="POST">
                                                                @csrf
                                                                @method('POST')

                                                                <!-- Quantity -->
                                                                <label class="text-sm font-medium">Quantity</label>
                                                                <input 
                                                                    type="number" 
                                                                    name="ng_quantity" 
                                                                    id="ngQuantity" 
                                                                    class="w-full border rounded p-2 mb-3" 
                                                                    min="1" 
                                                                    required
                                                                >

                                                                <!-- NG Type -->
                                                                <label class="text-sm font-medium">NG Type</label>
                                                                <select 
                                                                    name="ng_type_id" 
                                                                    id="ngType" 
                                                                    class="w-full border rounded p-2 mb-3"
                                                                    required
                                                                >
                                                                    <option value="">-- Select Type --</option>
                                                                    @foreach($ngData as $ng)
                                                                        <option value="{{ $ng->id }}">{{ $ng->ng_type }}</option>
                                                                    @endforeach
                                                                </select>

                                                                <!-- Remarks -->
                                                                <label class="text-sm font-medium">Remark</label>
                                                                <textarea 
                                                                    name="ng_remarks" 
                                                                    id="ngRemarks" 
                                                                    class="w-full border rounded p-2 mb-3"
                                                                    placeholder="Optional"
                                                                ></textarea>

                                                                <!-- Buttons -->
                                                                <div class="flex justify-end space-x-2">
                                                                    <button 
                                                                        type="button" 
                                                                        onclick="closeNgModal()" 
                                                                        class="px-4 py-2 bg-gray-400 text-white rounded">
                                                                        Cancel
                                                                    </button>

                                                                    <button 
                                                                        type="submit" 
                                                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                                                        Submit
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <div id="editNgModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                                                        <div class="bg-white p-4 rounded w-80 shadow-lg">
                                                            <h3 class="font-bold mb-3">Edit NG</h3>

                                                            <form id="editNgForm" method="POST">
                                                                @csrf
                                                                @method('PUT')

                                                                <input type="hidden" id="edit_ng_id" name="id">

                                                                <label class="text-sm block mb-1">NG Type</label>
                                                                <select id="edit_ng_type" name="ng_type_id" class="w-full border p-2 mb-3 rounded" required>
                                                                    @foreach ($ngData as $type)
                                                                        <option value="{{ $type->id }}">{{ $type->ng_type }}</option>
                                                                    @endforeach
                                                                </select>

                                                                <label class="text-sm block mb-1">Qty</label>
                                                                <input id="edit_ng_qty" name="ng_quantity" type="number" class="w-full border p-2 mb-3 rounded" required>

                                                                <label class="text-sm block mb-1">Remarks</label>
                                                                <input id="edit_ng_remarks" name="ng_remarks" type="text" class="w-full border p-2 mb-4 rounded">

                                                                <div class="flex gap-2">
                                                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                                                                    <button type="button" onclick="closeEditNgModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                            </dialog>

                            <dialog id="remarkModal" class="rounded-md shadow-lg p-4 w-full max-w-md">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold">Edit Remark</h3>
                                    <button onclick="document.getElementById('remarkModal').close()" class="text-red-500 hover:text-red-700">X</button>
                                </div>

                                <form id="remarkForm">
                                    @csrf
                                    <input type="hidden" name="id" id="remarkId">
                                    <textarea name="remark" id="remarkInput" rows="4" class="w-full border rounded p-2" placeholder="Tulis remark..."></textarea>
                                    <div class="flex justify-end mt-4">
                                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Simpan</button>
                                    </div>
                                </form>
                            </dialog>

                        <form id="mainSubmitForm" method="POST" action="{{ route('submit.spk') }}">
                            @csrf
                            <input type="hidden" id="uniqueData" name="uniqueData"
                            value="{{ json_encode($itemCollections) }}" />
                            <input type="hidden" id="datas" name="datas" value="{{ json_encode($datas) }}" />
                            <input type="hidden" id="activedic" name="activedic" value="{{ $activeDIC }}" />

                            <button type="button"
                                onclick="openConfirmModal()"
                                class="w-full py-3 px-4 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition mt-4">
                                Submit
                            </button>
                        </form>

                            <dialog id="confirmModal" class="rounded-md shadow-lg p-6 w-full max-w-md">
                                <h3 class="text-lg font-bold mb-4">Konfirmasi Submit</h3>
                                <p class="text-sm text-gray-700 mb-4">
                                    Apakah kamu yakin ingin submit data ini? Tindakan ini tidak bisa dibatalkan.
                                </p>

                                <div class="flex justify-end gap-2">
                                    <button onclick="document.getElementById('confirmModal').close()"
                                        class="px-4 py-2 text-gray-600 hover:text-gray-800">Batal</button>

                                    <button onclick="document.getElementById('mainSubmitForm').submit()"
                                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Ya, Submit</button>
                                </div>
                            </dialog>

                    </div>
                </div>


                <div class="bg-white shadow-sm sm:rounded-lg p-4 mt-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 mb-3 border-b border-gray-100 gap-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight">Scan Barcode</h3>
                            <p class="text-xs text-gray-500">Scan label kardus hasil produksi secara berurutan</p>
                        </div>
                        <!-- Display Ringkasan Label Terakhir -->
                        <div id="lastScannedBadgeContainer" class="flex items-center bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 shadow-sm transition-all duration-300">
                            <span class="text-xs font-semibold text-blue-800 uppercase tracking-wider mr-2">Label Terakhir:</span>
                            <span id="lastScannedLabelNumber" class="text-lg font-black font-mono text-blue-900 bg-white px-2 py-0.5 rounded border border-blue-300 shadow-inner">
                                {{ (isset($spkData) && $spkData->isNotEmpty()) ? '#' . $spkData->last()->label : '-' }}
                            </span>
                            <span id="lastScannedSubMeta" class="ml-2 text-xs text-blue-700 hidden sm:inline-block">
                                @if(isset($spkData) && $spkData->isNotEmpty())
                                    (SPK: {{ $spkData->last()->spk_code }} | {{ $spkData->last()->quantity }} pcs)
                                @endif
                            </span>
                        </div>
                    </div>
                    <div id="ajaxAlert" class="hidden p-4 rounded-lg mb-4 text-sm font-medium border shadow-sm transition-all duration-300"></div>
                    <form id="scanForm" action="{{ route('process.productionbarcode') }}" method="POST"
                        class="space-y-3" x-data="autoSubmitForm()" >
                        @csrf
                        <input type="hidden" id="uniqueData" name="uniqueData"
                            value="{{ json_encode($itemCollections) }}" />
                        <input type="hidden" id="datas" name="datas" value="{{ json_encode($datas) }}" />
                        <input type="hidden" id="activedic" name="activedic" value="{{ $activeDIC }}" />
                        <input type="hidden" id="nik" name="nik" x-model="nikInput" />
                        <input type="hidden" id="pic_2" name="pic_2" />
                        <input type="hidden" id="pic_3" name="pic_3" />

        
                        <!-- Grid Layout for 2 Columns -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="spk_code">SPK Code</label>
                                <input type="text" id="spk_code" name="spk_code_auto" required
                                    class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                    placeholder="SPK Code" 
                                    x-on:input="
                                        if ($el.value.includes('\t')) {
                                            let parts = $el.value.split('\t');
                                            if (parts.length >= 4) {
                                                $el.value = parts[0].trim();
                                                let qEl = document.getElementById('quantity'); if (qEl) qEl.value = parts[1].trim();
                                                let wEl = document.getElementById('warehouse'); if (wEl) wEl.value = parts[2].trim();
                                                let lEl = document.getElementById('label'); if (lEl) lEl.value = parts[3].trim();
                                                checkAndSubmitForm();
                                                return;
                                            }
                                        }
                                        debouncedSubmit();
                                    " />
                            </div>
                            <div>
                                <label for="quantity">Quantity</label>
                                <input type="number" id="quantity" name="quantity_auto" required
                                    class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                    placeholder="Quantity" x-on:input="debouncedSubmit()" />
                            </div>
                            <div>
                                <label for="warehouse">Warehouse</label>
                                <input type="text" id="warehouse" name="warehouse_auto" required
                                    class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                    placeholder="Warehouse" x-on:input="debouncedSubmit()" />
                            </div>
                            <div>
                                <label for="label">Label</label>
                                <input type="number" id="label" name="label_auto" required min="1" max="999999"
                                    class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                    placeholder="Label" 
                                    x-on:input="
                                        let spkVal = document.getElementById('spk_code')?.value.trim();
                                        if (spkVal && $el.value.endsWith(spkVal) && $el.value.length > spkVal.length) {
                                            $el.value = $el.value.slice(0, -spkVal.length);
                                        }
                                        if ($el.value.length > 6) {
                                            $el.value = $el.value.slice(0, 6);
                                        }
                                        debouncedSubmit();
                                    " />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition mt-4">
                            Scan
                        </button>
                    </form>
                </div>

                <div x-data="{ showLossScan: false }" class="bg-white shadow-sm sm:rounded-lg p-4 mt-6">
                    <!-- Toggle Button -->
                    <!-- <button type="button" @click="showLossScan = !showLossScan"
                        class="text-xl font-bold flex items-center justify-between w-full text-left focus:outline-none">
                        Scan Barcode (Loss Package)
                        <svg :class="{'rotate-180': showLossScan}" class="h-5 w-5 transform transition-transform duration-200 ml-2"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button> -->

                    <!-- Hidden Form -->
                    <!-- <div x-show="showLossScan" x-transition class="mt-4 space-y-3">
                        <form id="scanForm" action="{{ route('process.productionbarcodeloss') }}" method="POST" x-data="autoSubmitForm()">
                            @csrf
                            <input type="hidden" id="uniqueData" name="uniqueData" value="{{ json_encode($itemCollections) }}" />
                            <input type="hidden" id="datas" name="datas" value="{{ json_encode($datas) }}" />
                            <input type="hidden" id="nik" name="nik" x-model="nikInput" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="spk_code">SPK Code</label>
                                     <input type="text" id="spk_code" name="spk_code" required
                                        class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                        placeholder="SPK Code" x-on:input="checkAndSubmitForm()" />
                                </div>

                                <div>
                                    <label for="quantity">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" required
                                        class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                        placeholder="Quantity" />
                                </div>

                                <div>
                                    <label for="warehouse">Warehouse</label>
                                    <input type="text" id="warehouse" name="warehouse" required
                                        class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                        placeholder="Warehouse" />
                                </div>

                                <div>
                                    <label for="label">Label</label>
                                    <input type="number" id="label" name="label" required
                                        class="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full"
                                        placeholder="Label" />
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition mt-4">
                                Scan
                            </button>
                        </form>
                    </div> -->
                        <div id="summaryTableContainer">
                  <table class="w-full border border-gray-200 text-sm mt-6">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border">Jam Mulai</th>
                                    <th class="py-2 px-4 border">Jam Selesai</th>
                                    <th class="py-2 px-4 border">Target</th>
                                    <th class="py-2 px-4 border">Actual Scan</th>
                                    <th class="py-2 px-4 border">Actual Production</th>
                                    <th class="py-2 px-4 border">NG</th>
                                    <th class="py-2 px-4 border">Status</th>
                                    <th class="py-2 px-4 border">PIC</th>
                                    <th class="py-2 px-4 border">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hourlyRemarks as $remark)
                                    <tr class="text-center">
                                        <td class="py-2 px-4 border">{{ \Carbon\Carbon::parse($remark->start_time)->format('H:i') }}</td>
                                        <td class="py-2 px-4 border">{{ \Carbon\Carbon::parse($remark->end_time)->format('H:i') }}</td>
                                        <td class="py-2 px-4 border">{{ $remark->target }}</td>
                                        <td class="py-2 px-4 border">{{ $remark->actual }}</td>
                                        <td class="py-2 px-4 border">{{ $remark->actual_production ? $remark->actual_production : 0 }}</td>
                                        <td class="py-2 px-4 border">{{ $remark->NG ? $remark->NG : 0 }}</td> 
                                        <td class="py-2 px-4 border">
                                            @if ($remark->is_achieve)
                                                <span class="text-green-600 font-semibold">Tercapai</span>
                                            @else
                                                <span class="text-red-600 font-semibold">Tidak Tercapai</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 border">{{ $remark->pic }}</td>
                                        <td class="py-2 px-4 border">{{ $remark->remark ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                         <td colspan="7" class="text-center py-3 text-gray-500">Belum ada data summary</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                  </div>
            </div>
    </div>
    



    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {
            // Bind Fancybox
            try {
                Fancybox.bind('[data-fancybox="gallery"]', {
                    Thumbs: false,
                    Image: {
                        zoom: true,
                        fit: "contain",
                    },
                    transitionEffect: "fade",
                    Slideshow: {
                        autoStart: false, // Set ke false, kita handle manual
                        timeout: 10000, // 10 detik per slide
                    },
                });
            } catch (e) {
                console.error("Error binding Fancybox:", e);
            }

            const galleryItems = document.querySelectorAll('[data-fancybox="gallery"]');
            if (galleryItems.length === 0) return;

            function openRandomGallery() {
                try {
                    // Cegah penumpukan jika Fancybox sudah terbuka (cek DOM element)
                    if (document.querySelector('.fancybox__container') || document.querySelector('.fancybox-container')) {
                        return;
                    }

                    const randomIndex = Math.floor(Math.random() * galleryItems.length);

                    // Buka gallery dengan random index
                    const fancybox = Fancybox.show(
                        Array.from(galleryItems).map(el => ({
                            src: el.getAttribute('href'),
                            caption: el.getAttribute('data-caption') || '',
                            type: 'image',
                        })),
                        {
                            startIndex: randomIndex,
                            Thumbs: false,
                            Slideshow: {
                                autoStart: false,
                                timeout: 10000, // 10 detik per slide
                            },
                        }
                    );
                    
                    // Tunggu gallery fully loaded, baru jalankan slideshow
                    setTimeout(() => {
                        try {
                            if (fancybox) {
                                const slideshowBtn = document.querySelector('[data-fancybox-toggle-slideshow]');
                                if (slideshowBtn) {
                                    slideshowBtn.click();
                                } else if (fancybox.Slideshow) {
                                    fancybox.Slideshow.toggle();
                                }
                            }
                        } catch (ex) {
                            console.error("Slideshow toggle failed:", ex);
                        }
                    }, 5000);
                } catch (err) {
                    console.error("Error in openRandomGallery:", err);
                }
            }

            // ⏱️ Idle detection untuk auto-open gallery/SPS setelah 10 detik tidak ada aktivitas
            let idleTimer = null;

            function resetIdleTimer() {
                if (idleTimer) {
                    clearTimeout(idleTimer);
                }
                
                // Set timer untuk membuka gallery setelah 20 detik idle (20000 ms)
                idleTimer = setTimeout(() => {
                    openRandomGallery();
                }, 40000);
            }

            // Gunakan MutationObserver secara native untuk mendeteksi kapan modal Fancybox ditutup/dihapus dari DOM
            try {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.removedNodes.forEach((node) => {
                            if (node.nodeType === 1 && (
                                node.classList.contains('fancybox__container') || 
                                node.classList.contains('fancybox-container') ||
                                node.querySelector?.('.fancybox__container') ||
                                node.querySelector?.('.fancybox-container')
                            )) {
                                resetIdleTimer();
                            }
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            } catch (e) {
                console.error("MutationObserver failed to initialize:", e);
            }

            // Listen ke berbagai event aktivitas user
            const activityEvents = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'];
            activityEvents.forEach(eventName => {
                document.addEventListener(eventName, resetIdleTimer, true);
            });

            // Jalankan idle timer pertama kali
            resetIdleTimer();
        });

        $(document).ready(function () {
            let verifiedUser = null;

            // Helper to dynamically update status widget container and other details
            function refreshStatusAndContainers() {
                $.get(window.location.href, function (html) {
                    const $html = $('<div>').html(html);
                    $('#machineStatusContainer').html($html.find('#machineStatusContainer').html());
                    $('#productionPlanContainer').html($html.find('#productionPlanContainer').html());
                    $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                    $('#logsContainer').html($html.find('#logsContainer').html());
                    
                    // Re-initialize elapsed timer
                    initializeTimer();
                });
            }

            // Show NIK modal when clicking start buttons (using event delegation)
            $(document).on('click', '#startMouldChange', function () {
                $('#nikModal').removeClass('hidden').attr('data-action', 'mould');
                $('#nextItemCodeContainer').removeClass('hidden');
                $('#nextItemCodeLabel').text('Pilih Item Code Selanjutnya:');
                $('#next_item_code').val('{{ $defaultNextItemCode }}');
                $('#setupMolderSelectContainer').removeClass('hidden');
                $('#adjusterSelectContainer').addClass('hidden');
                $('#nik').val('');
                $('#password').val('');
                $('#setup_molder_select').val('');
                $('#adjuster_select').val('');
            });

            $(document).on('click', '#startAdjustMachine', function () {
                $('#nikModal').removeClass('hidden').attr('data-action', 'adjust');
                $('#nextItemCodeContainer').removeClass('hidden');
                $('#nextItemCodeLabel').text('Pilih Item Code:');
                @if($itemCode)
                    $('#next_item_code').val('{{ $itemCode }}');
                @else
                    $('#next_item_code').val('{{ $defaultNextItemCode }}');
                @endif
                $('#adjusterSelectContainer').removeClass('hidden');
                $('#setupMolderSelectContainer').addClass('hidden');
                $('#nik').val('');
                $('#password').val('');
                $('#setup_molder_select').val('');
                $('#adjuster_select').val('');
            });

            $(document).on('click', '#startRepairMachine', function () {
                $('#nikModal').removeClass('hidden').attr('data-action', 'repair');
                $('#nextItemCodeContainer').addClass('hidden');
                $('#setupMolderSelectContainer').addClass('hidden');
                $('#adjusterSelectContainer').addClass('hidden');
                $('#nik').val('');
                $('#password').val('');
                $('#setup_molder_select').val('');
                $('#adjuster_select').val('');
            });

            // Handle Setup Molder selection
            $(document).on('change', '#setup_molder_select', function () {
                let selectedOption = $(this).find('option:selected');
                let name = selectedOption.val();
                let password = selectedOption.attr('data-password') || '';
                $('#nik').val(name);
                $('#password').val(password);
            });

            // Handle Adjuster selection
            $(document).on('change', '#adjuster_select', function () {
                let selectedOption = $(this).find('option:selected');
                let name = selectedOption.val();
                let password = selectedOption.attr('data-password') || '';
                $('#nik').val(name);
                $('#password').val(password);
            });

            // Close NIK modal
            $(document).on('click', '#closeNikModal', function () {
                $('#nikModal').addClass('hidden');
            });

            // Verify NIK and password
            $(document).on('click', '#verifyNik', function () {
                let nik = $('#nik').val().trim();
                let password = $('#password').val().trim();
                let actionType = $('#nikModal').attr('data-action');
                let nextItemCode = $('#next_item_code').val();

                if (nik === '' || password === '') {
                    alert('Please enter both NIK and password.');
                    return;
                }

                if ((actionType === 'mould' || actionType === 'adjust') && !nextItemCode) {
                    alert('Please select next item code.');
                    return;
                }

                $.ajax({
                    url: "{{ route('verify.nik') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { nik: nik, password: password },
                    success: function (response) {
                        alert(response.message);
                        verifiedUser = response.user;
                        $('#nikModal').addClass('hidden');

                        if (actionType === 'mould') {
                            startMouldChange(verifiedUser.name, nextItemCode);
                        } else if (actionType === 'adjust') {
                            startAdjustMachine(verifiedUser.name, nextItemCode);
                        } else if (actionType === 'repair') {
                            startRepairMachine(verifiedUser.name);
                        }
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.error || 'Verifikasi gagal.');
                    }
                });
            });

            // Start Mould Change Process
            function startMouldChange(picName, nextItemCode) {
                $.ajax({
                    url: "{{ route('mould.change.start') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { pic_name: picName, item_code: nextItemCode },
                    success: function (response) {
                        if (response.message && response.message.includes('Belum ada item')) {
                            alert('Gagal: ' + response.message);
                            return;
                        }
                        alert(response.message);
                        localStorage.setItem('mouldChangeOperator', JSON.stringify(response.operator));
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.error || 'Terjadi kesalahan saat memulai mould change';
                        alert(msg);
                    }
                });
            }

            // Complete Mould Change Process
            $(document).on('click', '#endMouldChange', function () {
                const remarks = $('#mouldRemarks').val() || ''; 

                $.ajax({
                    url: "{{ route('mould.change.end') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { remarks: remarks },
                    success: function (response) {
                        alert(response.message);
                        localStorage.removeItem('mouldChangeOperator');
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.error || 'Gagal mengakhiri mould change.');
                    }
                });
            });

            // Start Adjust Machine Process
            function startAdjustMachine(picName, nextItemCode) {
                $.ajax({
                    url: "{{ route('adjust.machine.start') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { pic_name: picName, item_code: nextItemCode },
                    success: function (response) {
                        if (response.message && response.message.includes('Belum ada item')) {
                            alert('Gagal: ' + response.message);
                            return;
                        }
                        alert(response.message);
                        localStorage.setItem('adjustMachineOperator', JSON.stringify(response.operator));
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.error || 'Terjadi kesalahan saat memulai adjust machine';
                        alert(msg);
                    }
                });
            }

            // Complete Adjust Machine Process
            $(document).on('click', '#endAdjustMachine', function () {
                const remarks = $('#adjustRemarks').val() || '';

                $.ajax({
                    url: "{{ route('adjust.machine.end') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { remarks: remarks },
                    success: function (response) {
                        alert(response.message);
                        localStorage.removeItem('adjustMachineOperator');
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.error || 'Gagal mengakhiri adjust machine.');
                    }
                });
            });

            // Start Repair Machine Process
            function startRepairMachine(picName) {
                $.ajax({
                    url: "{{ route('repair.machine.start') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { pic_name: picName },
                    success: function (response) {
                        alert(response.message);
                        localStorage.setItem('repairMachineOperator', JSON.stringify(response.operator));
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.error || 'Gagal memulai perbaikan.');
                    }
                });
            }

            // Complete Repair Machine Process
            $(document).on('click', '#endRepairMachine', function () {
                const problem = $('#repairProblem').val() || '';
                const remarks = $('#repairRemarks').val() || '';

                $.ajax({
                    url: "{{ route('repair.machine.end') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        problem: problem,
                        remarks: remarks
                    },
                    success: function (response) {
                        alert(response.message);
                        localStorage.removeItem('repairMachineOperator');
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.error || 'Terjadi kesalahan.');
                    }
                });
            });

            // AJAX barcode scan handler
            $('#scanForm').on('submit', function (e) {
                e.preventDefault();

                // Capture input values before clearing
                const inputSpk = $('#spk_code').val()?.trim() || '';
                const inputQty = $('#quantity').val()?.trim() || '';
                const inputWh = $('#warehouse').val()?.trim() || '';
                const inputLabel = $('#label').val()?.trim() || '';

                // Clear previous alerts
                const $alert = $('#ajaxAlert');
                $alert.addClass('hidden').removeClass('bg-green-50 text-green-900 border-green-500 bg-amber-50 text-amber-900 border-amber-400 bg-red-50 text-red-900 border-red-500 border-2');

                // Get form details
                const actionUrl = $(this).attr('action');
                const formData = $(this).serialize();

                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        // Reset submit flag di AlpineJS pada form scan
                        const scanFormEl = document.getElementById('scanForm');
                        if (scanFormEl && window.Alpine) {
                            Alpine.$data(scanFormEl).isSubmitting = false;
                        }

                        const labelNo = response.label || inputLabel;
                        const spkNo = response.spk_code || inputSpk;
                        const qtyNo = response.quantity || inputQty;
                        const scanTime = response.time || new Date().toLocaleTimeString('id-ID', { hour12: false });

                        // Update badge label terakhir
                        $('#lastScannedLabelNumber').text('#' + labelNo);
                        $('#lastScannedSubMeta').text('(SPK: ' + spkNo + ' | ' + qtyNo + ' pcs)').removeClass('hidden');
                        
                        // Efek highlight pada badge
                        const $badgeContainer = $('#lastScannedBadgeContainer');
                        $badgeContainer.addClass('ring-2 ring-green-400 bg-green-50');
                        setTimeout(() => {
                            $badgeContainer.removeClass('ring-2 ring-green-400');
                        }, 1500);

                        // Render rich alert
                        if (response.was_recovered) {
                            $alert.html(`
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-3xl">⚠️</span>
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-amber-700">Scan Berhasil (Auto-Koreksi)</div>
                                            <div class="text-base font-extrabold text-amber-900">
                                                Label <span class="px-2 py-0.5 bg-amber-200 text-amber-900 rounded font-mono text-lg shadow-sm border border-amber-300">#${labelNo}</span>
                                                <span class="text-xs font-normal text-amber-700 ml-2">(Scan asli terkoreksi dari: <code class="line-through font-bold">${response.original_label}</code>)</span>
                                            </div>
                                            <div class="text-xs text-amber-800 mt-0.5 font-semibold">Tercatat: SPK ${spkNo} | Qty: ${qtyNo} pcs</div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-mono font-bold text-amber-800">${scanTime} WIB</div>
                                </div>
                            `)
                            .removeClass('hidden')
                            .addClass('bg-amber-50 text-amber-900 border-2 border-amber-400');
                        } else {
                            $alert.html(`
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-3xl">✅</span>
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-green-700">Scan Berhasil</div>
                                            <div class="text-base font-extrabold text-green-900">
                                                Label <span class="px-2 py-0.5 bg-green-200 text-green-900 rounded font-mono text-lg shadow-sm border border-green-300">#${labelNo}</span>
                                                <span class="text-sm font-semibold text-green-800 ml-2">(${qtyNo} pcs | SPK: ${spkNo})</span>
                                            </div>
                                            <div class="text-xs text-green-700 mt-0.5">Data berhasil disimpan ke sistem</div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-mono font-bold text-green-800">${scanTime} WIB</div>
                                </div>
                            `)
                            .removeClass('hidden')
                            .addClass('bg-green-50 text-green-900 border-2 border-green-500');
                        }

                        // Fetch updated page content via background GET to replace elements
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                            $('#detailDataModalTbody').html($html.find('#detailDataModalTbody').html());
                            $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                            $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                        });

                        // Clear inputs
                        $('#spk_code').val('');
                        $('#quantity').val('');
                        $('#warehouse').val('');
                        $('#label').val('');

                        // Refocus SPK Code input field
                        setTimeout(function () {
                            $('#spk_code').focus();
                        }, 100);

                        // Clear alert after 7 seconds (if no new scan)
                        clearTimeout(window.scanAlertTimer);
                        window.scanAlertTimer = setTimeout(function () {
                            $alert.addClass('hidden');
                        }, 7000);
                    },
                    error: function (xhr) {
                        // Reset submit flag di AlpineJS pada form scan
                        const scanFormEl = document.getElementById('scanForm');
                        if (scanFormEl && window.Alpine) {
                            Alpine.$data(scanFormEl).isSubmitting = false;
                        }

                        const badLabel = xhr.responseJSON?.label || inputLabel || '-';
                        const badSpk = xhr.responseJSON?.spk_code || inputSpk || '-';
                        const status = xhr.responseJSON?.status || 'error';
                        let errMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat memproses scan barcode.';

                        if (!xhr.responseJSON?.message && xhr.responseJSON?.errors) {
                            errMsg = Object.values(xhr.responseJSON.errors).map(errArr => errArr.join(', ')).join('; ');
                        }

                        // Efek highlight error pada badge
                        const $badgeContainer = $('#lastScannedBadgeContainer');
                        $badgeContainer.addClass('ring-2 ring-red-400');
                        setTimeout(() => {
                            $badgeContainer.removeClass('ring-2 ring-red-400');
                        }, 2000);

                        let alertHtml = '';
                        if (status === 'duplicate') {
                            alertHtml = `
                                <div class="flex items-center space-x-3 text-left">
                                    <span class="text-3xl">⛔</span>
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-red-700">Label Duplikat (Sudah Pernah Di-Scan)</div>
                                        <div class="text-base font-extrabold text-red-900">
                                            Label <span class="px-2 py-0.5 bg-red-200 text-red-900 rounded font-mono text-lg shadow-sm border border-red-300">#${badLabel}</span> SUDAH TERCATAT SEBELUMNYA!
                                        </div>
                                        <div class="text-xs text-red-700 mt-0.5 font-medium">
                                            SPK: <strong>${badSpk}</strong>. Kardus dengan nomor label ini sudah discan sebelumnya, mohon pastikan kardus tidak di-scan dua kali.
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (status === 'stutter') {
                            alertHtml = `
                                <div class="flex items-center space-x-3 text-left">
                                    <span class="text-3xl">⚠️</span>
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-red-700">Scanner Stutter / Key Repeat</div>
                                        <div class="text-base font-extrabold text-red-900">
                                            Nilai Terbaca: <span class="px-2 py-0.5 bg-red-200 text-red-900 rounded font-mono text-lg shadow-sm border border-red-300 font-bold">${badLabel}</span>
                                        </div>
                                        <div class="text-xs text-red-700 mt-0.5 font-medium">
                                            Scanner mengirimkan digit berulang (tombol macet). Harap periksa scanner dan scan ulang barcode.
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            alertHtml = `
                                <div class="flex items-center space-x-3 text-left">
                                    <span class="text-3xl">⛔</span>
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-red-700">Scan Barcode Gagal</div>
                                        <div class="text-base font-extrabold text-red-900">
                                            Label Diterima: <span class="px-2 py-0.5 bg-red-200 text-red-900 rounded font-mono text-lg shadow-sm border border-red-300">#${badLabel}</span>
                                        </div>
                                        <div class="text-xs text-red-700 mt-0.5 font-medium">${errMsg}</div>
                                    </div>
                                </div>
                            `;
                        }

                        // Display rich error in alert
                        $alert.html(alertHtml)
                            .removeClass('hidden')
                            .addClass('bg-red-50 text-red-900 border-2 border-red-500');

                        // Clear inputs but refocus SPK code
                        $('#spk_code').val('');
                        $('#quantity').val('');
                        $('#warehouse').val('');
                        $('#label').val('');

                        setTimeout(function () {
                            $('#spk_code').focus();
                        }, 100);

                        // Keep error visible longer (15s) so operator has time to read
                        clearTimeout(window.scanAlertTimer);
                        window.scanAlertTimer = setTimeout(function () {
                            $alert.addClass('hidden');
                        }, 15000);
                    },
                    complete: function () {
                        // Pastikan isSubmitting selalu kembali false setelah request selesai
                        const scanFormEl = document.getElementById('scanForm');
                        if (scanFormEl && window.Alpine) {
                            Alpine.$data(scanFormEl).isSubmitting = false;
                        }
                    }
                });
            });

            // AJAX submit for Temporal Cycle Time (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#cycleTimeForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'Cycle Time updated successfully!');
                        closeCycleTimeModal();
                        
                        // Update containers
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#productionPlanContainer').html($html.find('#productionPlanContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                        });
                    },
                    error: function (xhr) {
                        alert('Gagal mengupdate cycle time.');
                    }
                });
            });

            // AJAX submit for Temporal Cavity (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#temporalCavityForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'Temporal Cavity updated successfully!');
                        closeTemporalCavityModal();
                        
                        // Update containers
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#productionPlanContainer').html($html.find('#productionPlanContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                        });
                    },
                    error: function (xhr) {
                        alert('Gagal mengupdate temporal cavity.');
                    }
                });
            });

            // AJAX submit for Resin Usage (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#resinUsageForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'Resin Usage updated successfully!');
                        closeResinUsageModal();
                        
                        // Update containers
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#productionPlanContainer').html($html.find('#productionPlanContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                        });
                    },
                    error: function (xhr) {
                        alert('Gagal mengupdate resin usage.');
                    }
                });
            });

            // AJAX submit for Add Hourly Remark (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#addHourlyRemarksForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'Hourly Remark added successfully!');
                        // Modal remains open
                        
                        // Update containers
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                            $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                        });
                    },
                    error: function (xhr) {
                        const errMsg = xhr.responseJSON?.message || 'Gagal menambahkan hourly remark.';
                        alert(errMsg);
                    }
                });
            });

            // AJAX submit for Add Actual Production
            $(document).on('submit', '#productionForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'Actual Production updated successfully!');
                        // Modal remains open
                        
                        // Update containers
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                            $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                        });
                    },
                    error: function (xhr) {
                        alert('Gagal mengupdate actual production.');
                    }
                });
            });

            // AJAX submit for Add NG
            $(document).on('submit', '#ngForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                
                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert(data.message || 'NG added successfully!');
                        form.reset(); // Clear form fields
                        
                        // Update containers and refresh NG list inside modal
                        $.get(window.location.href, function (html) {
                            const $html = $(html);
                            $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                            $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                            $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                            refreshNgList();
                        });
                    },
                    error: function (xhr) {
                        alert('Gagal menambahkan NG.');
                    }
                });
            });

            // AJAX submit for Edit Remark (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#remarkForm', function(e) {
                e.preventDefault();
                const id = $('#remarkId').val();
                const remark = $('#remarkInput').val();
                const token = $('input[name="_token"]').val();

                $.ajax({
                    url: `/hourly-remarks/${id}/update-remark`,
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': token },
                    data: JSON.stringify({ remark }),
                    success: function (data) {
                        if (data.success) {
                            alert('Remark saved successfully!');
                            // Modal remains open
                            
                            // Update containers
                            $.get(window.location.href, function (html) {
                                const $html = $(html);
                                $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                                $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                                $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                            });
                        } else {
                            alert("Gagal menyimpan remark");
                        }
                    },
                    error: function (xhr) {
                        alert('Gagal menyimpan remark.');
                    }
                });
            });

            // AJAX submit for Edit NG (using event delegation to support dynamic element replacement)
            $(document).on('submit', '#editNgForm', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                const id = $('#edit_ng_id').val();

                $.ajax({
                    url: `/ng-detail/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.success) {
                            alert('NG updated successfully');
                            closeEditNgModal(); // Close the edit overlay modal
                            // Main ngModal remains open
                            
                            // Update containers and refresh NG list inside modal
                            $.get(window.location.href, function (html) {
                                const $html = $(html);
                                $('#detailRemarkModalTbody').html($html.find('#detailRemarkModalTbody').html());
                                $('#summaryTableContainer').html($html.find('#summaryTableContainer').html());
                                $('#pekerjaanTableContainer').html($html.find('#pekerjaanTableContainer').html());
                                refreshNgList();
                            });
                        } else {
                            alert('Failed to update NG');
                        }
                    },
                    error: function (xhr) {
                        alert('Failed to update NG.');
                    }
                });
            });

            // Open Edit Log Modal
            window.openEditLogModal = function (type, id, createdAt, endTime, remark) {
                $('#edit_log_type').val(type);
                $('#edit_log_id').val(id);
                
                // Format dates to YYYY-MM-DDTHH:mm
                $('#edit_log_created_at').val(formatDatetimeLocal(createdAt));
                $('#edit_log_end_time').val(formatDatetimeLocal(endTime));
                $('#edit_log_remark').val(remark || '');

                // Update label dynamically based on type
                if (type === 'repair') {
                    $('#edit_log_end_time_label').text('Waktu Selesai Perbaikan');
                } else {
                    $('#edit_log_end_time_label').text('Waktu Selesai');
                }

                $('#editLogModal').removeClass('hidden');
            };

            function formatDatetimeLocal(datetimeStr) {
                if (!datetimeStr) return '';
                // replace space with 'T' and strip seconds if any
                return datetimeStr.substring(0, 16).replace(' ', 'T');
            }

            // Close Edit Log Modal
            $(document).on('click', '#closeEditLogModal', function () {
                $('#editLogModal').addClass('hidden');
            });

            // Save Edit Log
            $(document).on('click', '#saveEditLog', function () {
                let type = $('#edit_log_type').val();
                let id = $('#edit_log_id').val();
                let createdAt = $('#edit_log_created_at').val();
                let endTime = $('#edit_log_end_time').val();
                let remark = $('#edit_log_remark').val();

                if (!createdAt || !endTime) {
                    alert('Waktu Mulai dan Waktu Selesai harus diisi.');
                    return;
                }

                let url = '';
                let data = {
                    created_at: createdAt,
                    remark: remark
                };

                if (type === 'mould') {
                    url = `/mould-change/update/${id}`;
                    data.end_time = endTime;
                } else if (type === 'adjust') {
                    url = `/adjust-machine/update/${id}`;
                    data.end_time = endTime;
                } else if (type === 'repair') {
                    url = `/repair-machine/update/${id}`;
                    data.finish_repair = endTime;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: data,
                    success: function (response) {
                        alert(response.message);
                        $('#editLogModal').addClass('hidden');
                        refreshStatusAndContainers();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.message || 'Gagal menyimpan perubahan log.');
                    }
                });
            });

        });


    </script>

    <script>

        document.getElementById("reloadButton").addEventListener("click", function() {
            // 1. Hapus semua data localStorage
            localStorage.clear();

            // 2. (Opsional) Kalau kamu juga pakai sessionStorage
            sessionStorage.clear();

            // 3. Reload halaman
            location.reload();
        });

        let activeInterval = null;

        function initializeTimer() {
            if (activeInterval) {
                clearInterval(activeInterval);
                activeInterval = null;
            }
            const timerEl = document.getElementById('activityTimer');
            if (timerEl) {
                const startTimeStr = timerEl.getAttribute('data-start');
                if (startTimeStr) {
                    const startTime = new Date(startTimeStr).getTime();
                    
                    function updateTimer() {
                        const now = new Date().getTime();
                        const diff = now - startTime;
                        
                        if (diff > 0) {
                            const hours = Math.floor(diff / 3600000);
                            const minutes = Math.floor((diff % 3600000) / 60000);
                            const seconds = Math.floor((diff % 60000) / 1000);
                            
                            const formatted = 
                                String(hours).padStart(2, '0') + ':' + 
                                String(minutes).padStart(2, '0') + ':' + 
                                String(seconds).padStart(2, '0');
                                
                            timerEl.textContent = formatted;
                        }
                    }
                    
                    updateTimer();
                    activeInterval = setInterval(updateTimer, 1000);
                }
            }
        }

        document.addEventListener("DOMContentLoaded", initializeTimer);


            const serverAssignedOperators = @json($assignedOperators ?? []);
            const allOperatorProfiles = {
                @foreach(($allOperators ?? []) as $op)
                    "{{ addslashes($op->name) }}": "{{ $op->profile_picture ? asset('storage/' . $op->profile_picture) : asset('default-avatar.png') }}",
                @endforeach
            };

            function renderAdditionalOperators() {
                const op1 = localStorage.getItem('operator_name') || '';
                let op2 = localStorage.getItem('operator_name_2') || '';
                let op3 = localStorage.getItem('operator_name_3') || '';
                const defaultAvatar = "{{ asset('default-avatar.png') }}";

                const list = $('#activeAdditionalOperatorsList');
                if (!list.length) return;
                list.empty();

                let count = 1;

                if (op2) {
                    count++;
                    const op2Pic = allOperatorProfiles[op2] || defaultAvatar;
                    list.append(`
                        <div class="flex items-center justify-between bg-indigo-50/70 border border-indigo-100 rounded-xl p-2 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <img src="${op2Pic}" class="w-8 h-8 rounded-full border border-indigo-200 object-cover shadow-xs" alt="Operator 2 Profile">
                                <div>
                                    <span class="text-xs font-bold text-gray-800 block">${op2}</span>
                                    <span class="text-[9px] text-indigo-600 font-semibold uppercase block">Operator 2</span>
                                </div>
                            </div>
                            <button type="button" onclick="removeAdditionalOperator(2)" class="text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded-lg transition text-xs font-bold" title="Hapus Operator 2">
                                ✕
                            </button>
                        </div>
                    `);
                }

                if (op3) {
                    count++;
                    const op3Pic = allOperatorProfiles[op3] || defaultAvatar;
                    list.append(`
                        <div class="flex items-center justify-between bg-indigo-50/70 border border-indigo-100 rounded-xl p-2 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <img src="${op3Pic}" class="w-8 h-8 rounded-full border border-indigo-200 object-cover shadow-xs" alt="Operator 3 Profile">
                                <div>
                                    <span class="text-xs font-bold text-gray-800 block">${op3}</span>
                                    <span class="text-[9px] text-indigo-600 font-semibold uppercase block">Operator 3</span>
                                </div>
                            </div>
                            <button type="button" onclick="removeAdditionalOperator(3)" class="text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded-lg transition text-xs font-bold" title="Hapus Operator 3">
                                ✕
                            </button>
                        </div>
                    `);
                }

                $('#operatorCountBadge').text(`${count} / 3`);

                if (count >= 3) {
                    $('#btnShowAddOperator').addClass('hidden');
                    $('#addOperatorSelectWrapper').addClass('hidden');
                } else {
                    $('#btnShowAddOperator').removeClass('hidden');
                }

                $('#pic_2').val(op2);
                $('#pic_3').val(op3);
            }

            function syncOperatorsToDB() {
                const op1 = localStorage.getItem('operator_name') || '';
                const op2 = localStorage.getItem('operator_name_2') || '';
                const op3 = localStorage.getItem('operator_name_3') || '';
                const operators = [op1, op2, op3].filter(Boolean);

                $.ajax({
                    url: "{{ route('updateEmployeeName') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { operators: operators }
                });
            }

            window.removeAdditionalOperator = function(index) {
                if (index === 2) {
                    const op3 = localStorage.getItem('operator_name_3');
                    if (op3) {
                        localStorage.setItem('operator_name_2', op3);
                        localStorage.removeItem('operator_name_3');
                    } else {
                        localStorage.removeItem('operator_name_2');
                    }
                } else if (index === 3) {
                    localStorage.removeItem('operator_name_3');
                }
                renderAdditionalOperators();
                syncOperatorsToDB();
            };

            document.addEventListener("DOMContentLoaded", function () {
                    // Check if user is already verified (persistent login)
                    if (localStorage.getItem("verified")) {
                        let savedProfile = localStorage.getItem("profile_picture");
                        let savedNIK = localStorage.getItem("nik");
                        let savedName = localStorage.getItem("operator_name");

                        if (savedProfile && savedNIK && savedName) {
                            $('#profileImage').attr('src', savedProfile);
                            $('#operatorName').text(savedName);
                            $('#dashboardSection').removeClass('hidden');
                            $('#loginSection').addClass('hidden');
                        }
                    }

                    if (serverAssignedOperators && serverAssignedOperators.length > 1) {
                        if (serverAssignedOperators[1] && !localStorage.getItem('operator_name_2')) {
                            localStorage.setItem('operator_name_2', serverAssignedOperators[1]);
                        }
                        if (serverAssignedOperators[2] && !localStorage.getItem('operator_name_3')) {
                            localStorage.setItem('operator_name_3', serverAssignedOperators[2]);
                        }
                    }

                    $('#btnShowAddOperator').on('click', function() {
                        $('#addOperatorSelectWrapper').removeClass('hidden');
                        $(this).addClass('hidden');
                    });

                    $('#btnCancelAddOperator').on('click', function() {
                        $('#addOperatorSelectWrapper').addClass('hidden');
                        $('#btnShowAddOperator').removeClass('hidden');
                    });

                    $('#btnConfirmAddOperator').on('click', function() {
                        const selected = $('#select_additional_operator').val();
                        if (!selected) {
                            alert("Silakan pilih nama operator terlebih dahulu.");
                            return;
                        }

                        const op1 = localStorage.getItem('operator_name') || '';
                        const op2 = localStorage.getItem('operator_name_2') || '';
                        const op3 = localStorage.getItem('operator_name_3') || '';

                        if (selected === op1 || selected === op2 || selected === op3) {
                            alert("Operator " + selected + " sudah terdaftar sebagai operator aktif.");
                            return;
                        }

                        if (!op2) {
                            localStorage.setItem('operator_name_2', selected);
                        } else if (!op3) {
                            localStorage.setItem('operator_name_3', selected);
                        }

                        $('#select_additional_operator').val('');
                        $('#addOperatorSelectWrapper').addClass('hidden');
                        renderAdditionalOperators();
                        syncOperatorsToDB();
                    });

                    renderAdditionalOperators();
                });

            function scanModeHandler(deactivateScanModeFlag) {
                return {
                    ready: false, // NEW
                    scanMode: false,
                    verified: false,
                    ready: false, // NEW
                    scanMode: false,
                    verified: false,
                    nikInput: '', 
                    passwordInput: '',
                    idleTimeout: null,

                    initialize() {
                        this.verified = localStorage.getItem('verified') === 'true';

                        this.verified = localStorage.getItem('verified') === 'true';

                        if (deactivateScanModeFlag == true) {
                            this.scanMode = false;
                            localStorage.setItem('scanMode', false);
                        } else {
                            this.scanMode = localStorage.getItem('scanMode') === 'true';
                        }

                        if (this.verified && this.scanMode) {
                            this.startIdleTimer();
                            this.focusOnSPKCode();
                        }

                        // Delay rendering until everything is set
                        this.ready = true;
                        if (this.verified && this.scanMode) {
                            this.startIdleTimer();
                            this.focusOnSPKCode();
                        }

                        // Delay rendering until everything is set
                        this.ready = true;

                        if (this.scanMode) {
                            if (!this.verified) {
                                alert("Please verify your NIK before activating Scan Mode.");
                                return;
                            }
                            this.focusOnSPKCode();
                        }
                    },

                    toggleScanMode() {
                        this.scanMode = !this.scanMode;
                        localStorage.setItem('scanMode', this.scanMode);

                        if (this.scanMode) {
                            this.focusOnSPKCode();
                        }
                    },

                    verifyNIK() {
                        if (this.nikInput.trim() !== '' && this.passwordInput.trim() !== '') {
                            $.ajax({
                                url: "{{ route('verify.nik.password') }}",
                                type: "POST",
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                data: {
                                    nik: this.nikInput,
                                    password: this.passwordInput
                                },
                                success: (response) => {
                                    if (response.success) {
                                        this.verified = true;
                                        localStorage.setItem('verified', true); // Save state
                                        localStorage.setItem('nik', this.nikInput);
                                        this.startIdleTimer(); // Start the idle timer
                                        localStorage.setItem('operator_name', response.operator_name);
                                        localStorage.setItem('profile_picture', response.profile_picture);


                                        $('#profileImage').attr('src', response.profile_picture);
                                        $('#operatorName').text(response.operator_name);
                                        $('#dashboardSection').removeClass('hidden'); // Show the dashboard
                                        $('#loginSection').addClass('hidden'); // Hide the login form
                                        alert("NIK Verified Successfully!");
                                        location.reload();
                                    } else {
                                        alert("Invalid NIK or Password.");
                                    }
                                },
                                error: function(xhr) {
                                    alert("An error occurred while verifying your NIK.");
                                }
                            });
                        } else {
                            alert("Please enter both NIK and password.");
                        }
                    },

                    startIdleTimer() {
                        if (this.idleTimeout) {
                            clearTimeout(this.idleTimeout);
                        }
                        this.idleTimeout = setTimeout(() => {
                            this.resetVerification(); // Reset verification after timeout
                        }, 1800000000); // 3 minutes
                    },

                    resetVerification() {
                        this.verified = false;
                        localStorage.removeItem('verified');

                        localStorage.removeItem('nik');
                        localStorage.removeItem('operator_name');
                        localStorage.removeItem('operator_name_2');
                        localStorage.removeItem('operator_name_3');
                        localStorage.removeItem('profile_picture');

                        // Reset UI elements
                        $('#profileImage').attr('src', "{{ asset('default-avatar.png') }}"); // Default image
                        $('#operatorName').text(""); // Clear operator name
                        $('#dashboardSection').addClass('hidden');
                        $('#loginSection').removeClass('hidden');
                        alert("Verification expired due to inactivity.");
                        location.reload();
                    },

                    focusOnSPKCode() {
                        setTimeout(() => {
                            document.getElementById('spk_code').focus();
                        }, 100);
                    }
                };
            }

            function autoSubmitForm() {
            return {
                nikInput: localStorage.getItem('nik') || '',
                _submitTimer: null,
                isSubmitting: false,

                debouncedSubmit() {
                    // Cancel any pending submit timer
                    if (this._submitTimer) {
                        clearTimeout(this._submitTimer);
                    }
                    // Only submit after user stops typing for 400ms
                    this._submitTimer = setTimeout(() => {
                        this.checkAndSubmitForm();
                    }, 400);
                },

                checkAndSubmitForm() {
                    if (this.isSubmitting) return;

                    if (!this.nikInput) {
                        this.nikInput = localStorage.getItem('nik') || '';
                    }

                    // Auto-clean & validate label if appended with SPK or stutter
                    const labelEl = document.getElementById('label');
                    const spkEl = document.getElementById('spk_code');
                    if (labelEl && spkEl) {
                        let lVal = labelEl.value.trim();
                        let sVal = spkEl.value.trim();
                        // Auto-strip SPK jika tertempel di belakang nomor label (misal 30026026744)
                        if (sVal && lVal.endsWith(sVal) && lVal.length > sVal.length) {
                            lVal = lVal.slice(0, -sVal.length);
                            labelEl.value = lVal;
                        }
                        // Cegah submit jika label terindikasi rusak (panjang > 6 digit atau ada 5 digit kembar berulang)
                        if (lVal.length > 6 || /(\d)\1{4,}/.test(lVal)) {
                            alert('Nomor label terindikasi salah scan atau scanner mengalami tombol macet (' + lVal + '). Harap scan ulang.');
                            labelEl.value = '';
                            setTimeout(() => spkEl.focus(), 50);
                            return;
                        }
                    }

                    // Securely set NIK, pic_2, pic_3 in scan form hidden input
                    const form = document.getElementById('scanForm');
                    if (form) {
                        const nikHiddenInput = form.querySelector('input[name="nik"]');
                        if (nikHiddenInput) {
                            nikHiddenInput.value = this.nikInput;
                        }
                        const pic2HiddenInput = form.querySelector('input[name="pic_2"]');
                        if (pic2HiddenInput) {
                            pic2HiddenInput.value = localStorage.getItem('operator_name_2') || '';
                        }
                        const pic3HiddenInput = form.querySelector('input[name="pic_3"]');
                        if (pic3HiddenInput) {
                            pic3HiddenInput.value = localStorage.getItem('operator_name_3') || '';
                        }
                    }

                    const requiredFieldNames = ['spk_code_auto', 'quantity_auto', 'warehouse_auto', 'label_auto'];
                    const allFilled = requiredFieldNames.every(name => {
                        const input = document.querySelector(`[name="${name}"]`);
                        return input && input.value.trim() !== '';
                    });

                    if (allFilled && this.nikInput) {
                        console.log("✅ Form is valid. Submitting...");
                        this.isSubmitting = true;
                        // Auto-unlock safety timeout (2 detik) jika AJAX lambat / tidak ter-reset
                        setTimeout(() => {
                            this.isSubmitting = false;
                        }, 2000);
                        if (labelEl) labelEl.blur();
                        $('#scanForm').submit();
                    } else {
                        console.warn("❌ Form not submitted. Missing required fields or NIK.");
                    }
                }
            };
        }

            function updateWaktuIndonesia() {
                    const now = new Date();
                    const optionsTanggal = {
                        timeZone: 'Asia/Jakarta',
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                    };
                    const optionsJam = {
                        timeZone: 'Asia/Jakarta',
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    };

                    const tanggal = now.toLocaleDateString('id-ID', optionsTanggal);
                    const jam = now.toLocaleTimeString('id-ID', optionsJam);

                    document.getElementById('tanggal-hari-ini').textContent = tanggal;
                    document.getElementById('jam-hari-ini').textContent = jam;
                }

                setInterval(updateWaktuIndonesia, 1000);
                updateWaktuIndonesia();


            function editRemark(id, remark) {
                currentHourlyRemarkId = id;
                document.getElementById('remarkId').value = id;
                document.getElementById('remarkInput').value = remark || '';
                document.getElementById('remarkModal').showModal();
            }


            function openConfirmModal() {
                document.getElementById('confirmModal').showModal();
            }

            function openCycleTimeModal(dataId, existingValue = '') {
                document.getElementById('cycleTimeModal').classList.remove('hidden');
                document.getElementById('dataIdInput').value = dataId;
                document.getElementById('cycleTimeInput').value = existingValue;

                // Set form action dynamically
                document.getElementById('cycleTimeForm').action = `/daily-item-codes/${dataId}/temporal-cycle-time`;
            }

            function closeCycleTimeModal() {
                document.getElementById('cycleTimeModal').classList.add('hidden');
            }

            function openMaterialLotModal(dataId, existingValue = '') {
                document.getElementById('materialLotModal').classList.remove('hidden');
                document.getElementById('mlDataIdInput').value = dataId;
                document.getElementById('materialLotInput').value = existingValue;

                // Set form action dynamically
                document.getElementById('materialLotForm').action = `/daily-item-codes/${dataId}/material-lot`;
            }

            function closeMaterialLotModal() {
                document.getElementById('materialLotModal').classList.add('hidden');
            }

            let currentDicIdForAcc = null;

            function openAccessoryLotOpModal(dicId) {
                currentDicIdForAcc = dicId;
                document.getElementById('accOpName').value = '';
                document.getElementById('accOpLot').value = '';
                document.getElementById('accessoryLotOpModal').classList.remove('hidden');
                fetchAccessoryLotsOp(dicId);
            }

            function closeAccessoryLotOpModal() {
                document.getElementById('accessoryLotOpModal').classList.add('hidden');
                currentDicIdForAcc = null;
            }

            function fetchAccessoryLotsOp(dicId) {
                const container = document.getElementById('accOpListContainer');
                if (!container) return;
                container.innerHTML = `<div class="text-gray-400 italic text-center py-2">Loading...</div>`;
                
                fetch(`/daily-item-codes/${dicId}/accessory-lots`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderAccessoryLotsOp(data.accessory_lots);
                    } else {
                        container.innerHTML = `<div class="text-red-500 text-center py-2">Gagal memuat data.</div>`;
                    }
                })
                .catch(() => {
                    container.innerHTML = `<div class="text-red-500 text-center py-2">Gagal memuat data.</div>`;
                });
            }

            function renderAccessoryLotsOp(lots) {
                const container = document.getElementById('accOpListContainer');
                if (!container) return;
                if (!lots || lots.length === 0) {
                    container.innerHTML = `<div class="text-gray-400 italic text-center py-3 bg-gray-50 rounded">Belum ada accessory lot.</div>`;
                    return;
                }
                
                let html = '';
                lots.forEach(lot => {
                    html += `
                        <div class="flex items-center justify-between bg-gray-100 p-2 rounded border">
                            <div>
                                <span class="font-bold text-gray-800">${lot.accessory_name}:</span>
                                <span class="font-semibold text-purple-900 ml-1">${lot.accessory_lot}</span>
                            </div>
                            <button type="button" onclick="deleteAccessoryLotOp(${lot.id})" class="text-red-600 font-bold hover:text-red-800 text-xs px-1">
                                ✕
                            </button>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            function submitAddAccessoryLotOp() {
                if (!currentDicIdForAcc) return;
                
                const name = document.getElementById('accOpName').value.trim();
                const lot = document.getElementById('accOpLot').value.trim();
                
                if (!name || !lot) {
                    alert('Harap isi Jenis Accessories dan Kode Lot Accessories!');
                    return;
                }
                
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                
                fetch(`/daily-item-codes/${currentDicIdForAcc}/accessory-lots`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ accessory_name: name, accessory_lot: lot })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('accOpName').value = '';
                        document.getElementById('accOpLot').value = '';
                        fetchAccessoryLotsOp(currentDicIdForAcc);
                    } else {
                        alert('Gagal menambahkan Lot Accessory.');
                    }
                })
                .catch(() => alert('Terjadi kesalahan koneksi.'));
            }

            function deleteAccessoryLotOp(id) {
                if (!confirm('Yakin ingin menghapus Lot Accessory ini?')) return;
                
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                
                fetch(`/accessory-lots/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && currentDicIdForAcc) {
                        fetchAccessoryLotsOp(currentDicIdForAcc);
                    }
                })
                .catch(() => alert('Gagal menghapus Lot Accessory.'));
            }

            function openProductionModal(slotId, currentValue = 0) {
            currentHourlyRemarkId = slotId;
            const modal = document.getElementById('productionModal');
            const form = document.getElementById('productionForm');
            const input = document.getElementById('actualProductionInput');
            const hiddenId = document.getElementById('productionSlotId');

            hiddenId.value = slotId;
            input.value = currentValue;
            form.action = `/hourly-remarks/${slotId}/update-actual-production`;

            modal.classList.remove('hidden');
        }

        function closeProductionModal() {
            document.getElementById('productionModal').classList.add('hidden');
        }

        let currentHourlyRemarkId = null;

        function refreshNgList() {
            if (!currentHourlyRemarkId) return;
            const btn = document.querySelector(`button[data-id="${currentHourlyRemarkId}"]`);
            if (btn) {
                const ngDetails = JSON.parse(btn.getAttribute("data-ng") || '[]');
                let listHtml = "";
                if (ngDetails.length === 0) {
                    listHtml = `<p class="text-gray-500 text-sm">No NG recorded for this hour.</p>`;
                } else {
                    ngDetails.forEach(ng => {
                        listHtml += `
                            <div class="border-b py-1">
                                <div class="text-sm font-semibold">${ng.ng_type?.ng_type ?? 'Unknown'}</div>
                                <div class="text-xs">Qty: ${ng.ng_quantity}</div>
                                <div class="text-xs text-gray-600">${ng.ng_remarks ?? ''}</div>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <button class="text-blue-600 text-xs hover:underline" onclick="editNg(${ng.id})">Edit</button>
                                <button class="text-red-600 text-xs hover:underline" onclick="deleteNg(${ng.id})">Delete</button>
                            </div>
                        `;
                    });
                }
                document.getElementById('ngList').innerHTML = listHtml;
            }
        }

            function openNgModal(el) {
                let hourlyRemarkId = el.getAttribute("data-id");
                currentHourlyRemarkId = hourlyRemarkId;
                let ngDetails = JSON.parse(el.getAttribute("data-ng"));

                // Update form action
                document.getElementById('ngForm').action = "/hourly-remark/" + hourlyRemarkId + "/add-ng";

                // Render NG list
                let listHtml = "";

                if (ngDetails.length === 0) {
                    listHtml = `<p class="text-gray-500 text-sm">No NG recorded for this hour.</p>`;
                } else {
                    ngDetails.forEach(ng => {
                        listHtml += `
                            <div class="border-b py-1">
                                <div class="text-sm font-semibold">${ng.ng_type?.ng_type ?? 'Unknown'}</div>
                                <div class="text-xs">Qty: ${ng.ng_quantity}</div>
                                <div class="text-xs text-gray-600">${ng.ng_remarks ?? ''}</div>
                            </div>

                              <div class="flex gap-2 mt-2">
                                <button class="text-blue-600 text-xs hover:underline" onclick="editNg(${ng.id})">Edit</button>
                                <button class="text-red-600 text-xs hover:underline" onclick="deleteNg(${ng.id})">Delete</button>
                            </div>
                        `;
                    });
                }

                document.getElementById('ngList').innerHTML = listHtml;

                // Show modal
                document.getElementById('ngModal').classList.remove('hidden');
                document.getElementById('ngModal').classList.add('flex');
            }

            function closeNgModal() {
                document.getElementById('ngModal').classList.add('hidden');
                document.getElementById('ngModal').classList.remove('flex');
            }

           function editNg(id) {
                fetch(`/ng-detail/${id}`)
                    .then(res => {
                        if (!res.ok) throw new Error('Failed to fetch NG data');
                        return res.json();
                    })
                    .then(data => {
                        // Isi form edit
                        document.getElementById('edit_ng_id').value = data.id;
                        document.getElementById('edit_ng_type').value = data.ng_type_id;
                        document.getElementById('edit_ng_qty').value = data.ng_quantity;
                        document.getElementById('edit_ng_remarks').value = data.ng_remarks ?? '';

                        // Set form action
                        document.getElementById('editNgForm').action = `/ng-detail/${data.id}`;

                        // Tampilkan modal edit
                        document.getElementById('editNgModal').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to load NG data');
                    });
            }

            function deleteNg(id) {
                if (!confirm("Delete this NG?")) return;

                fetch(`/ng-detail/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Failed to delete');
                    return res.json();
                })
                .then(res => {
                    if (res.success) {
                        alert('NG deleted successfully');
                        
                        // Pure Vanilla JS fetch and DOM swap:
                        fetch(window.location.href)
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                
                                const oldTbody = document.getElementById('detailRemarkModalTbody');
                                const newTbody = doc.getElementById('detailRemarkModalTbody');
                                if (oldTbody && newTbody) {
                                    oldTbody.innerHTML = newTbody.innerHTML;
                                }

                                const oldSummary = document.getElementById('summaryTableContainer');
                                const newSummary = doc.getElementById('summaryTableContainer');
                                if (oldSummary && newSummary) {
                                    oldSummary.innerHTML = newSummary.innerHTML;
                                }

                                refreshNgList();
                            });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete NG');
                });
            }

            function closeEditNgModal() {
                document.getElementById('editNgModal').classList.add('hidden');
            }

        function openRemarkModal(id, existingRemark = '') {
            document.getElementById('remark_dic_id').value = id;
            document.getElementById('remark_dic_input').value = existingRemark;
            document.getElementById('remarkDICModal').classList.remove('hidden');
        }

        function closeRemarkModal() {
            document.getElementById('remarkDICModal').classList.add('hidden');
        }

        function saveRemark() {
            const id = document.getElementById('remark_dic_id').value;
            const remark = document.getElementById('remark_dic_input').value;

            fetch(`/daily-item-codes/update-remark/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ remark })
            })
            .then(res => res.json())
            .then(data => {
                alert('Remark saved!');
                closeRemarkModal();
                
                // Pure Vanilla JS fetch and DOM swap:
                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        const newPlan = doc.getElementById('productionPlanContainer');
                        const oldPlan = document.getElementById('productionPlanContainer');
                        if (newPlan && oldPlan) {
                            oldPlan.innerHTML = newPlan.innerHTML;
                        }

                        const newPekerjaan = doc.getElementById('pekerjaanTableContainer');
                        const oldPekerjaan = document.getElementById('pekerjaanTableContainer');
                        if (newPekerjaan && oldPekerjaan) {
                            oldPekerjaan.innerHTML = newPekerjaan.innerHTML;
                        }
                    });
            });
        }

        function openTemporalCavityModal(id, value) {
            document.getElementById('tcDataIdInput').value = id;
            document.getElementById('tcInput').value = value;
            document.getElementById('temporalCavityModal').classList.remove('hidden');

            // Set action URL (sesuai controller kamu)
            document.getElementById('temporalCavityForm').action = `/daily-item-codes/${id}/temporal-cavity`;
        }

        function closeTemporalCavityModal() {
            document.getElementById('temporalCavityModal').classList.add('hidden');
        }

        function openResinUsageModal(id, value) {
            document.getElementById('ruDataIdInput').value = id;
            document.getElementById('ruInput').value = value;
            document.getElementById('resinUsageModal').classList.remove('hidden');

            // Set action URL
            document.getElementById('resinUsageForm').action = `/daily-item-codes/${id}/resin-usage`;
        }

        function closeResinUsageModal() {
            document.getElementById('resinUsageModal').classList.add('hidden');
        }

        // AJAX handler untuk penambahan Output Log tanpa reload halaman
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('output-log-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const button = form.querySelector('button[type="submit"]');
                    const operatorName = localStorage.getItem('operator_name') || '';
                    
                    // Disable button
                    button.disabled = true;
                    button.classList.add('opacity-50', 'cursor-not-allowed');
                    
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            operator_name: operatorName
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const tbody = document.getElementById('output-logs-tbody');
                            
                            // Hapus baris kosong/placeholder jika ada
                            const emptyRow = tbody.querySelector('tr td[colspan="3"]');
                            if (emptyRow) {
                                tbody.innerHTML = '';
                            }
                            
                            // Render baris baru
                            const newRowHtml = `
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-semibold text-gray-700 w-1/3">
                                        ${data.log.time}
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 w-1/3">
                                        <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                            ${data.log.operator_name}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-900 font-bold w-1/3">
                                        ${data.log.quantity}
                                    </td>
                                </tr>
                            `;
                            
                            // Sisipkan ke baris paling atas
                            tbody.insertAdjacentHTML('afterbegin', newRowHtml);

                            // Update badge count
                            const countEl = document.getElementById('total-logs-count');
                            if (countEl) {
                                countEl.textContent = parseInt(countEl.textContent || '0') + 1;
                            }
                            
                            console.log('TESTING')
                        //     Trigger Print menggunakan hidden iframe
                           const printUrl = `/production-output-log/print/${data.log_id}`;
                            const iframe = document.createElement('iframe');
                           iframe.src = printUrl;
                           iframe.style.position = 'absolute';
                           iframe.style.width = '0';
                           iframe.style.height = '0';
                           iframe.style.border = '0';
                           iframe.style.visibility = 'hidden';
                            
                           document.body.appendChild(iframe);
                            
                        //     Bersihkan iframe setelah print dipicu
                            setTimeout(() => {
                               iframe.remove();
                            }, 10000);

                            
                        } else {
                            alert(data.message || 'Gagal menambahkan log output.');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Terjadi kesalahan saat menambahkan log.');
                    })
                    .finally(() => {
                        // Re-enable button
                        button.disabled = false;
                        button.classList.remove('opacity-50', 'cursor-not-allowed');
                    });
                });
            }
        });
    
    </script>

    <!-- {{-- Hidden iframe untuk auto-print label barcode --}}
    @if (session('print_log_id'))
        <iframe src="{{ route('production.output-log.print', session('print_log_id')) }}" 
                style="width:0; height:0; border:0; border:none; position:absolute; visibility:hidden;">
        </iframe>
    @endif -->

<!-- Modal Maintenance Checklist (Predictive Maintenance) -->
<div id="maintenanceChecklistModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div id="maintenanceChecklistBackdrop" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Container -->
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100">
            
            <!-- Modal Header -->
            <div class="bg-slate-900 text-white px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xl">
                        🛠️
                    </div>
                    <div>
                        <h3 class="text-base font-black tracking-tight uppercase italic text-white flex items-center gap-2">
                            Checklist Pengecekan Mesin <span class="text-indigo-400 text-xs font-semibold normal-case">(Predictive Maintenance)</span>
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            Mesin: <span class="text-white font-bold">{{ auth()->user()?->name ?? '-' }}</span> | Tanggal Produksi: <span id="maintChecklistDisplayDate" class="text-indigo-300 font-bold">-</span>
                        </p>
                    </div>
                </div>
                <button type="button" id="closeMaintenanceChecklistModal" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-slate-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Content -->
            <form id="maintenanceChecklistForm" class="p-6">
                <input type="hidden" id="maintChecklistMachineId" value="{{ auth()->id() }}">

                <!-- Notice Bar -->
                <div id="maintChecklistStatusAlert" class="mb-6 p-4 rounded-2xl text-xs font-semibold flex items-center justify-between border hidden"></div>

                <!-- 17 Items Section -->
                <div id="maintChecklistItemsContainer" class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
                    <!-- Loaded dynamically via JS -->
                    <div class="py-12 text-center text-gray-400 font-bold text-xs">
                        Loading item checklist...
                    </div>
                </div>

                <!-- Bottom Signatures & Time -->
                <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl">
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 mb-1">
                            Prepared BY (PIC Maintenance) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="maintPreparedBy" 
                            name="prepared_by" 
                            required 
                            placeholder="Ketik Nama PIC..." 
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full px-3 py-2 text-xs font-bold uppercase rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                        >
                        <span class="text-[9px] text-gray-400 italic">Otomatis CAPS LOCK</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 mb-1">
                            Approved BY (Atasan)
                        </label>
                        <input 
                            type="text" 
                            id="maintApprovedBy" 
                            name="approved_by" 
                            placeholder="Ketik Nama Atasan..." 
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full px-3 py-2 text-xs font-bold uppercase rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                        >
                        <span class="text-[9px] text-gray-400 italic">Otomatis CAPS LOCK</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 mb-1">
                            Jam Pengecekan
                        </label>
                        <input 
                            type="time" 
                            id="maintCheckTime" 
                            name="check_time" 
                            class="w-full px-3 py-2 text-xs font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                        >
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" id="btnCancelMaintChecklist" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveMaintChecklist" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                        <span>💾 Simpan Checklist</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.validateNumericInput = function(input, itemName) {
    const val = parseFloat(input.value);
    const hintEl = input.closest('.flex-col').querySelector('.numeric-hint-tag');
    if (!hintEl) return;

    if (isNaN(val) || input.value.trim() === '') {
        hintEl.className = 'numeric-hint-tag text-[10px] font-bold text-gray-400';
        hintEl.textContent = itemName.toLowerCase().includes('temp') ? 'Standar: Normal (< 60)' : 'Standar: Normal (100-200Kgf)';
        input.classList.remove('border-red-500', 'border-emerald-500');
        return;
    }

    let isNormal = true;
    if (itemName.toLowerCase().includes('temp')) {
        isNormal = (val < 60);
    } else if (itemName.toLowerCase().includes('pump')) {
        isNormal = (val >= 100 && val <= 200);
    }

    if (isNormal) {
        hintEl.className = 'numeric-hint-tag text-[10px] font-black text-emerald-600';
        hintEl.textContent = '✓ Normal';
        input.classList.remove('border-red-500');
        input.classList.add('border-emerald-500');
    } else {
        hintEl.className = 'numeric-hint-tag text-[10px] font-black text-red-600 animate-pulse';
        hintEl.textContent = '⚠️ ABNORMAL (Di luar standar)';
        input.classList.remove('border-emerald-500');
        input.classList.add('border-red-500');
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const btnOpen = document.getElementById('btnOpenMaintenanceChecklist');
    const modal = document.getElementById('maintenanceChecklistModal');
    const backdrop = document.getElementById('maintenanceChecklistBackdrop');
    const btnClose = document.getElementById('closeMaintenanceChecklistModal');
    const btnCancel = document.getElementById('btnCancelMaintChecklist');
    const form = document.getElementById('maintenanceChecklistForm');
    const itemsContainer = document.getElementById('maintChecklistItemsContainer');
    const displayDateEl = document.getElementById('maintChecklistDisplayDate');
    const alertEl = document.getElementById('maintChecklistStatusAlert');
    const machineIdEl = document.getElementById('maintChecklistMachineId');
    if (!machineIdEl) return;
    const machineId = machineIdEl.value;

    function openModal() {
        modal.classList.remove('hidden');
        loadChecklistData();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    function loadChecklistData() {
        itemsContainer.innerHTML = `
            <div class="py-12 text-center text-gray-400 font-bold text-xs flex flex-col items-center gap-2">
                <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Mengambil data checklist hari ini...</span>
            </div>
        `;

        fetch(`/maintenance-checklist/today/${machineId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    itemsContainer.innerHTML = `<div class="p-4 text-red-600 text-xs font-bold text-center">Gagal memuat data checklist.</div>`;
                    return;
                }

                displayDateEl.textContent = data.display_date;

                const header = data.header;
                const detailsMap = {};
                if (header && header.details) {
                    header.details.forEach(d => {
                        detailsMap[d.item_id] = d;
                    });
                }

                const now = new Date();
                const defaultTime = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

                document.getElementById('maintPreparedBy').value = header ? (header.prepared_by || '') : '';
                document.getElementById('maintApprovedBy').value = header ? (header.approved_by || '') : '';
                document.getElementById('maintCheckTime').value = header ? (header.check_time || defaultTime) : defaultTime;

                if (data.is_filled) {
                    alertEl.className = 'mb-6 p-4 rounded-2xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-between';
                    alertEl.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span>✅ Checklist hari ini (${data.display_date}) sudah terisi. Anda dapat mengedit & menyimpan ulang.</span>
                        </div>
                    `;
                    alertEl.classList.remove('hidden');
                } else {
                    alertEl.className = 'mb-6 p-4 rounded-2xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 flex items-center justify-between';
                    alertEl.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span>⚠️ Checklist hari ini (${data.display_date}) belum terisi. Silakan lengkapi form di bawah ini.</span>
                        </div>
                    `;
                    alertEl.classList.remove('hidden');
                }

                renderItems(data.items, detailsMap);
            })
            .catch(err => {
                console.error(err);
                itemsContainer.innerHTML = `<div class="p-4 text-red-600 text-xs font-bold text-center">Terjadi kesalahan server saat memuat data.</div>`;
            });
    }

    function renderItems(items, detailsMap) {
        const periods = ['Daily', 'Weekly', 'Two weeks'];
        const periodBadges = {
            'Daily': 'bg-blue-100 text-blue-800 border-blue-200',
            'Weekly': 'bg-purple-100 text-purple-800 border-purple-200',
            'Two weeks': 'bg-orange-100 text-orange-800 border-orange-200',
        };

        let html = '';

        periods.forEach(p => {
            const periodItems = items.filter(i => i.period === p);
            if (periodItems.length === 0) return;

            html += `
                <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-2xs mb-4">
                    <div class="bg-gray-100/80 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-gray-700 flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black border ${periodBadges[p] || 'bg-gray-100'}">${p}</span>
                            <span>Pengecekan ${p} (${periodItems.length} Item)</span>
                        </span>
                    </div>
                    <div class="divide-y divide-gray-100 text-xs">
            `;

            periodItems.forEach((item) => {
                const detail = detailsMap[item.id];
                const isNonDaily = (item.period === 'Weekly' || item.period === 'Two weeks');
                const savedVal = detail ? detail.value : (item.input_type === 'numeric' ? '' : (isNonDaily ? '-' : 'OK'));
                
                let inputHtml = '';
                if (item.input_type === 'ok_ng') {
                    if (isNonDaily) {
                        const isSkip = (!detail && savedVal === '-') || savedVal === '-';
                        const isOk = (savedVal === 'OK');
                        const isNg = (savedVal === 'NG');

                        inputHtml = `
                            <div class="flex items-center gap-1.5">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="items[${item.id}][value]" value="-" ${isSkip ? 'checked' : ''} class="peer sr-only">
                                    <span class="px-3 py-1.5 rounded-xl border text-xs font-black transition-all peer-checked:bg-slate-600 peer-checked:text-white peer-checked:border-slate-600 bg-gray-50 text-gray-400 border-gray-200 hover:bg-gray-100">
                                        - (Lewati)
                                    </span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="items[${item.id}][value]" value="OK" ${isOk ? 'checked' : ''} class="peer sr-only">
                                    <span class="px-3 py-1.5 rounded-xl border text-xs font-black transition-all peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100">
                                        ✓ OK
                                    </span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="items[${item.id}][value]" value="NG" ${isNg ? 'checked' : ''} class="peer sr-only">
                                    <span class="px-3 py-1.5 rounded-xl border text-xs font-black transition-all peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100">
                                        ✕ NG
                                    </span>
                                </label>
                            </div>
                        `;
                    } else {
                        const isOk = (savedVal === 'OK' || savedVal === '' || !detail);
                        inputHtml = `
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="items[${item.id}][value]" value="OK" ${isOk ? 'checked' : ''} class="peer sr-only">
                                    <span class="px-4 py-1.5 rounded-xl border text-xs font-black transition-all peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100">
                                        ✓ OK
                                    </span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="items[${item.id}][value]" value="NG" ${!isOk ? 'checked' : ''} class="peer sr-only">
                                    <span class="px-4 py-1.5 rounded-xl border text-xs font-black transition-all peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100">
                                        ✕ NG
                                    </span>
                                </label>
                            </div>
                        `;
                    }
                } else if (item.input_type === 'numeric') {
                    inputHtml = `
                        <div class="flex flex-col items-end gap-1">
                            <div class="flex items-center gap-2">
                                <input 
                                    type="text" 
                                    name="items[${item.id}][value]" 
                                    value="${savedVal}" 
                                    placeholder="Nilai..." 
                                    required
                                    oninput="validateNumericInput(this, '${item.item_name}')"
                                    class="w-32 px-3 py-1.5 rounded-xl border border-gray-300 font-bold text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                >
                                <span class="bg-gray-100 text-gray-700 font-black text-xs px-2.5 py-1 rounded-lg border border-gray-200">${item.unit || ''}</span>
                            </div>
                            <span class="numeric-hint-tag text-[10px] font-bold text-gray-400">Standar: ${item.standard}</span>
                        </div>
                    `;
                }

                html += `
                    <div class="p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-gray-50/50 transition-colors ${isNonDaily && savedVal === '-' ? 'opacity-75' : ''}">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-black text-gray-400 text-[10px] w-5">${item.sort_order}.</span>
                                <span class="font-bold text-gray-800 text-xs">${item.item_name}</span>
                            </div>
                            <div class="ml-7 text-[10px] text-gray-400 font-medium">
                                Standar: <span class="text-gray-600 font-semibold">${item.standard}</span> | Kriteria: <span class="text-gray-600 font-semibold">${item.kriteria}</span>
                            </div>
                        </div>
                        <div class="ml-7 md:ml-0">
                            ${inputHtml}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        itemsContainer.innerHTML = html;
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('btnSaveMaintChecklist');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span>⏳ Menyimpan...</span>`;

            const formData = new FormData(form);
            const payload = {
                machine_id: machineId,
                prepared_by: formData.get('prepared_by'),
                approved_by: formData.get('approved_by'),
                check_time: formData.get('check_time'),
                items: {}
            };

            const radioChecked = form.querySelectorAll('input[type="radio"]:checked');
            radioChecked.forEach(radio => {
                const match = radio.name.match(/items\[(\d+)\]\[value\]/);
                if (match) {
                    payload.items[match[1]] = radio.value;
                }
            });

            const textInputs = form.querySelectorAll('input[type="text"][name^="items"]');
            textInputs.forEach(input => {
                const match = input.name.match(/items\[(\d+)\]\[value\]/);
                if (match) {
                    payload.items[match[1]] = input.value;
                }
            });

            fetch('/maintenance-checklist/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeModal();

                    if (btnOpen) {
                        btnOpen.className = 'px-4 py-2 text-xs font-bold rounded-xl shadow-sm transition-all duration-150 flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white';
                        btnOpen.innerHTML = `
                            <span>🛠️ Maintenance Checklist</span>
                            <span class="bg-emerald-800 text-emerald-100 text-[10px] px-2 py-0.5 rounded-full font-black uppercase">Sudah Diisi</span>
                        `;
                    }
                } else {
                    alert('✕ ' + (data.message || 'Gagal menyimpan checklist.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('✕ Terjadi kesalahan saat menghubungi server.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<span>💾 Simpan Checklist</span>`;
            });
        });
    }
});
</script>

</x-app-layout>
