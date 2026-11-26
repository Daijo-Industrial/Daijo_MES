<x-app-layout>
    <div class="p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span>📅 Jadwal Produksi Operator - Mesin:</span>
                <span class="text-blue-600 bg-blue-50 px-3 py-1 rounded-xl border border-blue-200">{{ $targetUser->name ?? auth()->user()->name }}</span>
            </h1>

            <div class="flex items-center gap-3">
                @if (!empty($isAdmin) && isset($machineUsers) && $machineUsers->isNotEmpty())
                    <form method="GET" action="{{ route('adminoperator') }}" class="flex items-center gap-2">
                        <label for="machine_select" class="text-xs font-semibold text-gray-700">Pilih Mesin:</label>
                        <select id="machine_select" name="user_id" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded-xl px-2.5 py-2 bg-white font-medium focus:ring-2 focus:ring-blue-500 shadow-sm cursor-pointer">
                            @foreach ($machineUsers as $mUser)
                                <option value="{{ $mUser->id }}" {{ (string)$mUser->id === (string)($targetUser->id ?? '') ? 'selected' : '' }}>
                                    {{ $mUser->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <button onclick="toggleAddForm()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-xl shadow-sm transition-all duration-150 flex items-center gap-1.5 cursor-pointer">
                    <span>➕</span> Tambah Daily Item
                </button>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('error'))
            <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2 shadow-sm">
                <span>❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if (session('message') || session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2 shadow-sm">
                <span>✅</span>
                <span>{{ session('message') ?: session('success') }}</span>
            </div>
        @endif

        <!-- Modal Tambah Daily Item -->
        <div id="addForm" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4 pb-2 border-b">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <span>🗓️</span> Tambah Jadwal Daily Item Code
                    </h2>
                    <button id="closeAddForm" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>

                <form method="POST" action="{{ route('dicadmin.store') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $targetUser->id ?? Auth::user()->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Item Code Dropdown AJAX -->
                        <div class="relative">
                            <label class="block font-semibold text-gray-700 text-sm">Item Code <span class="text-red-500">*</span></label>
                            <input type="text" id="item_code" name="item_code" autocomplete="off" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Ketik item code...">

                            <select id="item_code_list"
                                    size="5"
                                    class="border border-gray-300 rounded-xl mt-1 max-h-40 overflow-y-auto bg-white absolute z-50 w-full hidden shadow-lg text-sm"></select>
                        </div>

                        <!-- Shift -->
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Shift <span class="text-red-500">*</span></label>
                            <select id="shift" name="shift" required
                                    class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="">Pilih Shift</option>
                                <option value="1">Shift 1 (07:30 - 15:30)</option>
                                <option value="2">Shift 2 (15:30 - 23:30)</option>
                                <option value="3">Shift 3 (23:30 - 07:30 - Lewat Tengah Malam)</option>
                            </select>
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Tanggal Mulai <span class="text-red-500">*</span></label>
                            <input type="date" id="start_schedule_date" name="start_schedule_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Tanggal Selesai <span class="text-red-500">*</span></label>
                            <input type="date" id="end_schedule_date" name="end_schedule_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Start & End Time (otomatis berdasarkan shift) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Start Time <span class="text-red-500">*</span></label>
                            <input type="time" id="start_time" name="start_time" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">End Time <span class="text-red-500">*</span></label>
                            <input type="time" id="end_time" name="end_time" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Target Quantity Total, Cycle Time & Cavity -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Target Qty Total (pcs) <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" min="0" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Contoh: 800">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Cycle Time (detik)</label>
                            <input type="number" step="0.1" name="temporal_cycle_time" min="0.1"
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Contoh: 50 (Otomatis master)">
                            <span class="text-[10px] text-gray-500 block mt-0.5">Default dari master jika kosong</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm">Cavity</label>
                            <input type="number" name="temporal_cavity" min="1"
                                class="w-full border border-gray-300 rounded-xl p-2.5 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Contoh: 1 atau 2">
                            <span class="text-[10px] text-gray-500 block mt-0.5">Jumlah cavity (default 1 / master)</span>
                        </div>
                    </div>

                    <!-- Opsi Auto Generate 8 Slot Jam Shift (Sangat berguna jika lupa shift 3) -->
                    <div class="mt-4 p-3.5 bg-blue-50/80 border border-blue-200 rounded-xl">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-blue-900 text-sm">
                            <input type="checkbox" name="auto_generate_slots" value="1" id="auto_generate_slots" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4">
                            <span>⚡ Otomatis buat 8 slot jam untuk shift ini (Hourly Remarks)</span>
                        </label>
                        <p class="text-xs text-blue-700 mt-0.5 ml-6">
                            Centang ini jika operator lupa input Shift 3 agar seluruh 8 baris jam langsung otomatis terbuat dengan nama operator yang bertugas.
                        </p>

                        <div id="auto_generate_slots_wrapper" class="hidden mt-3 pt-3 border-t border-blue-200 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 text-xs mb-1">Operator Bertugas (PIC) <span class="text-red-500">*</span></label>
                                <select name="pic" id="add_item_pic" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring focus:ring-blue-300 bg-white">
                                    <option value="">-- Pilih Operator --</option>
                                    @foreach ($operators ?? [] as $op)
                                        <option value="{{ $op->name }}">{{ $op->name }} ({{ $op->position ?? 'Operator' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 text-xs mb-1">Catatan Awal (Opsional)</label>
                                <input type="text" name="remark" placeholder="Contoh: Input manual shift 3 susulan" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring focus:ring-blue-300 bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" id="btnCancelAddForm"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2 rounded-xl transition shadow-sm text-sm cursor-pointer">
                            💾 Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL 1: Tambah 1 Jam Manual (Pilih Jam, Operator, Qty, Remark) -->
        <!-- ============================================================== -->
        <div id="manualHourlyModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-3 pb-2 border-b">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-1.5">
                            <span>➕</span> Tambah Data Jam Produksi (Manual)
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Item: <strong id="m_item_code" class="text-blue-700"></strong> | Shift: <strong id="m_shift" class="text-indigo-700"></strong>
                        </p>
                    </div>
                    <button type="button" onclick="closeManualHourlyModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="mb-3 p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <span class="text-sm">💡</span>
                    <span>Fitur ini untuk menginput data hasil produksi manual per jam <strong>tanpa perlu scan barcode</strong> (misal saat operator lupa scan atau input susulan).</span>
                </div>

                <form method="POST" action="{{ route('hourly-remarks.store') }}" id="manualHourlyForm">
                    @csrf
                    <input type="hidden" name="dic_id" id="m_dic_id">

                    <!-- Pilih Jam -->
                    <div class="mb-3">
                        <label class="block font-semibold text-gray-700 text-xs mb-1">Pilih Jam Mulai <span class="text-red-500">*</span></label>
                        <select name="start_time" id="m_start_time" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                        </select>
                    </div>

                    <!-- Custom Jam Wrapper (hidden by default) -->
                    <div id="m_custom_time_wrapper" class="hidden grid grid-cols-2 gap-3 mb-3 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                        <div>
                            <label class="block font-semibold text-gray-700 text-xs mb-1">Jam Mulai Kustom</label>
                            <input type="time" id="m_custom_start_time" name="custom_start_time" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring focus:ring-blue-300">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 text-xs mb-1">Jam Selesai Kustom</label>
                            <input type="time" id="m_custom_end_time" name="end_time" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring focus:ring-blue-300">
                        </div>
                    </div>

                    <!-- Operator (PIC) -->
                    <div class="mb-3">
                        <label class="block font-semibold text-gray-700 text-xs mb-1">Operator Bertugas (PIC Utama) <span class="text-red-500">*</span></label>
                        <select name="pic" id="m_pic" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                            <option value="">-- Pilih Operator --</option>
                            @foreach ($operators ?? [] as $op)
                                <option value="{{ $op->name }}">{{ $op->name }} ({{ $op->position ?? 'Operator' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Toggle Operator Tambahan -->
                    <div x-data="{ showExtra: false }" class="mb-3">
                        <button type="button" @click="showExtra = !showExtra" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 cursor-pointer">
                            <span x-show="!showExtra">➕ Tambah Operator Pendamping (PIC 2 & 3)</span>
                            <span x-show="showExtra">➖ Sembunyikan Operator Pendamping</span>
                        </button>
                        <div x-show="showExtra" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Operator 2 (Opsional)</label>
                                <select name="pic_2" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                                    <option value="">-- Tidak ada --</option>
                                    @foreach ($operators ?? [] as $op)
                                        <option value="{{ $op->name }}">{{ $op->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Operator 3 (Opsional)</label>
                                <select name="pic_3" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                                    <option value="">-- Tidak ada --</option>
                                    @foreach ($operators ?? [] as $op)
                                        <option value="{{ $op->name }}">{{ $op->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Output Produksi & Target -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block font-semibold text-emerald-800 text-xs mb-1">
                                Qty Aktual (pcs) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="actual_production" id="m_actual_production" min="0" required
                                class="w-full border border-emerald-400 bg-emerald-50/40 rounded-xl p-2.5 text-sm font-bold text-emerald-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                placeholder="Contoh: 120">
                            <span class="text-[10px] text-gray-500 block mt-0.5">Input manual tanpa scan</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 text-xs mb-1">Target Jam (pcs)</label>
                            <input type="number" name="target" id="m_target" min="0"
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Otomatis dari Cycle Time">
                            <span class="text-[10px] text-gray-500 block mt-0.5">Boleh dikosongkan</span>
                        </div>
                    </div>

                    <!-- NG & Remark -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label class="block font-semibold text-gray-700 text-xs mb-1">Reject / NG (pcs)</label>
                            <input type="number" name="NG" min="0" value="0"
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-semibold text-gray-700 text-xs mb-1">Remark / Keterangan</label>
                            <input type="text" name="remark"
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                placeholder="Contoh: Input manual shift 3 susulan...">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="closeManualHourlyModal()"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-xl transition shadow-sm text-sm cursor-pointer">
                            💾 Simpan Data Jam
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL 2: ⚡ Auto-Generate Semua 8 Slot Jam Shift               -->
        <!-- ============================================================== -->
        <div id="generateShiftModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-3 pb-2 border-b">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-1.5">
                            <span>⚡</span> Generate Semua Jam Shift
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Item: <strong id="g_item_code" class="text-blue-700"></strong> | <span id="g_shift_badge" class="font-bold text-indigo-700"></span>
                        </p>
                    </div>
                    <button type="button" onclick="closeGenerateShiftModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-900 leading-relaxed">
                    <p class="font-semibold mb-1">⚡ Pembuatan Otomatis 8 Jam Produksi:</p>
                    <p>Sistem akan otomatis membuat seluruh 8 slot jam untuk Shift ini (yang belum terdaftar). Setelah dibuat, Anda dapat langsung mengklik tombol <strong>✏️ Edit</strong> pada tabel untuk mengisi/mengubah Qty Aktual dan Remark per jamnya.</p>
                </div>

                <form method="POST" action="{{ route('adminoperator.generate-shift-remarks') }}">
                    @csrf
                    <input type="hidden" name="dic_id" id="g_dic_id">

                    <!-- Operator (PIC) -->
                    <div class="mb-3">
                        <label class="block font-semibold text-gray-700 text-xs mb-1">Operator Bertugas (PIC Utama) <span class="text-red-500">*</span></label>
                        <select name="pic" id="g_pic" required
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                            <option value="">-- Pilih Operator --</option>
                            @foreach ($operators ?? [] as $op)
                                <option value="{{ $op->name }}">{{ $op->name }} ({{ $op->position ?? 'Operator' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Toggle Operator Tambahan -->
                    <div x-data="{ showExtraG: false }" class="mb-3">
                        <button type="button" @click="showExtraG = !showExtraG" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 cursor-pointer">
                            <span x-show="!showExtraG">➕ Tambah Operator Pendamping (PIC 2 & 3)</span>
                            <span x-show="showExtraG">➖ Sembunyikan Operator Pendamping</span>
                        </button>
                        <div x-show="showExtraG" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Operator 2 (Opsional)</label>
                                <select name="pic_2" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                                    <option value="">-- Tidak ada --</option>
                                    @foreach ($operators ?? [] as $op)
                                        <option value="{{ $op->name }}">{{ $op->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Operator 3 (Opsional)</label>
                                <select name="pic_3" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                                    <option value="">-- Tidak ada --</option>
                                    @foreach ($operators ?? [] as $op)
                                        <option value="{{ $op->name }}">{{ $op->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Default Remark -->
                    <div class="mb-4">
                        <label class="block font-semibold text-gray-700 text-xs mb-1">Remark Default (Opsional)</label>
                        <input type="text" name="remark" placeholder="Contoh: Input manual shift 3 susulan"
                            class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="closeGenerateShiftModal()"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-xl transition shadow-sm text-sm cursor-pointer">
                            ⚡ Buat 8 Slot Jam
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Edit Cycle Time & Cavity Daily Item Code -->
        <!-- ============================================================== -->
        <div id="editCtCavityModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <div class="flex justify-between items-center mb-3 pb-2 border-b">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-1.5">
                            <span>⚙️</span> Setting Cycle Time & Cavity
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Item: <strong id="ct_item_code" class="text-blue-700"></strong> | <strong id="ct_shift" class="text-indigo-700"></strong>
                        </p>
                    </div>
                    <button type="button" onclick="closeEditCtCavityModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>

                <form id="editCtCavityForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="dic_id" id="ct_dic_id">

                    <div class="space-y-4 my-4">
                        <div>
                            <label class="block font-semibold text-gray-700 text-sm mb-1">
                                Temporal Cycle Time (detik) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.1" name="temporal_cycle_time" id="ct_cycle_time_input" required min="0.1"
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none font-bold text-gray-800"
                                placeholder="Contoh: 50">
                            <span class="text-[11px] text-gray-500 mt-0.5 block">Waktu siklus cetakan dalam satuan detik</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 text-sm mb-1">
                                Temporal Cavity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="temporal_cavity" id="ct_cavity_input" required min="1"
                                class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none font-bold text-gray-800"
                                placeholder="Contoh: 1 atau 2">
                            <span class="text-[11px] text-gray-500 mt-0.5 block">Jumlah pcs per shoot (misal 2 untuk sepasang set LH & RH)</span>
                        </div>

                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 leading-relaxed">
                            ℹ️ <strong>Auto-Recalculate:</strong> Mengubah nilai ini akan langsung mengkalkulasi ulang Target Jam seluruh slot produksi pada Shift ini.
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="closeEditCtCavityModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-xl transition shadow-sm text-sm cursor-pointer">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Kelola NG (Defect) Hourly Remark                       -->
        <!-- ============================================================== -->
        <div id="ngManagementModal" class="fixed inset-0 bg-gray-900/60 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-xl p-6 shadow-2xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex justify-between items-start pb-3 border-b mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Kelola NG (Defect)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Slot: <strong id="ng_modal_slot_time" class="text-gray-700"></strong> | 
                            Item: <strong id="ng_modal_item_code" class="text-blue-700"></strong> | 
                            Total NG: <span id="ng_modal_total_display" class="font-bold text-rose-600 font-mono">0</span> pcs
                        </p>
                    </div>
                    <button type="button" onclick="closeNgModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-5 pr-1">
                    <!-- 1. Daftar NG yang sudah ada -->
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Daftar NG Terdata</h3>
                        <div id="ng_details_loading" class="text-center py-4 text-xs text-gray-500 hidden">
                            Memuat data NG...
                        </div>
                        <div id="ng_details_empty" class="text-center py-4 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-xs text-gray-500 hidden">
                            Belum ada data NG untuk jam ini.
                        </div>
                        <div id="ng_details_table_wrapper" class="overflow-hidden border border-gray-200 rounded-xl hidden">
                            <table class="min-w-full text-xs divide-y divide-gray-200">
                                <thead class="bg-gray-50 text-gray-600 font-semibold">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Jenis Defect</th>
                                        <th class="px-3 py-2 text-left">Qty</th>
                                        <th class="px-3 py-2 text-left">Remark</th>
                                        <th class="px-3 py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="ng_details_tbody" class="divide-y divide-gray-100 bg-white">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. Form Tambah NG Baru -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Tambah NG Baru</h3>
                        <form id="addNgForm" onsubmit="submitAddNg(event)">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Defect <span class="text-rose-500">*</span></label>
                                    <select id="add_ng_type_id" name="ng_type_id" required class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
                                        <option value="">-- Pilih Jenis NG --</option>
                                        @foreach ($ngTypes ?? [] as $type)
                                            <option value="{{ $type->id }}">{{ $type->ng_type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah (Qty) <span class="text-rose-500">*</span></label>
                                    <input type="number" id="add_ng_quantity" name="ng_quantity" min="1" required class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white" placeholder="Contoh: 5">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Keterangan / Remarks</label>
                                <input type="text" id="add_ng_remarks" name="ng_remarks" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white" placeholder="Opsional (misal: baret di bagian tepi)">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" id="btn_submit_add_ng" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg transition shadow-sm cursor-pointer flex items-center gap-1.5">
                                    <span>Simpan NG</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t mt-4 flex justify-end">
                    <button type="button" onclick="closeNgModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition text-xs font-semibold cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL: Edit Defect (NG Detail)                                 -->
        <!-- ============================================================== -->
        <div id="editNgDetailModal" class="fixed inset-0 bg-gray-900/60 z-50 flex items-center justify-center hidden p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-5 shadow-2xl">
                <div class="flex justify-between items-center pb-2 border-b mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Edit Data NG</h3>
                    <button type="button" onclick="closeEditNgDetailModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">
                        &times;
                    </button>
                </div>
                <form id="editNgDetailForm" onsubmit="submitUpdateNg(event)">
                    <input type="hidden" id="edit_ng_detail_id" name="id">
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Defect <span class="text-rose-500">*</span></label>
                            <select id="edit_ng_type_id" name="ng_type_id" required class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
                                <option value="">-- Pilih Jenis NG --</option>
                                @foreach ($ngTypes ?? [] as $type)
                                    <option value="{{ $type->id }}">{{ $type->ng_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah (Qty) <span class="text-rose-500">*</span></label>
                            <input type="number" id="edit_ng_quantity" name="ng_quantity" min="1" required class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Keterangan / Remarks</label>
                            <input type="text" id="edit_ng_remarks" name="ng_remarks" class="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" onclick="closeEditNgDetailModal()" class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition text-xs font-semibold cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" id="btn_submit_update_ng" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-1.5 rounded-lg transition shadow-sm cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($datas->isEmpty())
            <div class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-sm my-6">
                <span class="text-4xl block mb-2">📭</span>
                <p class="text-gray-700 font-semibold text-base">Belum ada jadwal produksi.</p>
                <p class="text-gray-500 text-xs mt-1">Klik tombol <strong>+ Tambah Daily Item</strong> di atas untuk membuat jadwal mesin baru.</p>
            </div>
        @else
            @php
                $grouped = $datas
                    ->groupBy(fn($item) => \Carbon\Carbon::parse($item->start_date)->format('Y-m-d'))
                    ->sortKeys();
            @endphp

            @foreach ($grouped as $date => $items)
                @php
                    $sortedItems = $items->sortBy('start_time');
                    $dateLabel = match (true) {
                        $date === \Carbon\Carbon::yesterday()->format('Y-m-d') => '🕒 Kemarin (' . \Carbon\Carbon::parse($date)->format('d M Y') . ')',
                        $date === \Carbon\Carbon::today()->format('Y-m-d') => '📆 Hari Ini (' . \Carbon\Carbon::parse($date)->format('d M Y') . ')',
                        $date === \Carbon\Carbon::tomorrow()->format('Y-m-d') => '🔮 Besok (' . \Carbon\Carbon::parse($date)->format('d M Y') . ')',
                        default => '📅 ' . \Carbon\Carbon::parse($date)->format('d M Y'),
                    };
                @endphp

                <div class="mb-8">
                    <h2 class="text-lg font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <span>{{ $dateLabel }}</span>
                        <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $sortedItems->count() }} item</span>
                    </h2>

                    <div class="overflow-x-auto bg-white shadow-md rounded-2xl border border-gray-200">
                        <table class="min-w-full text-sm text-gray-800">
                            <thead class="bg-gray-100 text-gray-700 uppercase text-xs border-b">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Item Code</th>
                                    <th class="px-4 py-3 text-left">Shift</th>
                                    <th class="px-4 py-3 text-left">Cycle Time & Cavity</th>
                                    <th class="px-4 py-3 text-left">Start Time</th>
                                    <th class="px-4 py-3 text-left">End Time</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Action</th>
                                    <th class="px-4 py-3 text-center w-12">Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sortedItems as $index => $item)
                                    @php
                                        $isToday = $date === \Carbon\Carbon::today()->format('Y-m-d');
                                        $bg = $isToday ? 'bg-blue-50/40' : 'bg-white';
                                        $isDone = $item->is_done == 1;

                                        $status = $isDone ? 'Done' : 'Proses';
                                        $statusClass = $isDone
                                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                            : 'bg-amber-100 text-amber-800 border border-amber-300';
                                    @endphp

                                    {{-- tiap item punya scope Alpine sendiri --}}
                                    <tbody x-data="{ open: false }">
                                        <tr class="border-b hover:bg-gray-50 {{ $bg }}">
                                            <td class="px-4 py-3 text-center">
                                                <button @click="open = !open" type="button"
                                                    class="text-gray-500 hover:text-blue-600 focus:outline-none transition cursor-pointer"
                                                    title="Buka / Tutup Detail Hourly Remarks">
                                                    <span x-show="!open" class="text-sm">🔽</span>
                                                    <span x-show="open" class="text-sm">🔼</span>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-gray-900">{{ $item->item_code }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    Shift {{ $item->shift ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-xs font-bold" title="Cycle Time">
                                                        ⏱️ {{ $item->temporal_cycle_time ? $item->temporal_cycle_time . 's' : '-' }}
                                                    </span>
                                                    <span class="bg-purple-50 text-purple-800 border border-purple-200 px-2 py-0.5 rounded text-xs font-bold" title="Cavity">
                                                        Cav: {{ $item->temporal_cavity ?? $item->masterItem?->cavity ?? 1 }}
                                                    </span>
                                                    <button type="button"
                                                        onclick="openEditCtCavityModal({{ $item->id }}, '{{ addslashes($item->item_code) }}', {{ $item->shift }}, '{{ $item->temporal_cycle_time ?? '' }}', '{{ $item->temporal_cavity ?? ($item->masterItem?->cavity ?? 1) }}')"
                                                        class="text-blue-600 hover:text-blue-800 text-[11px] font-bold bg-blue-50 hover:bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded transition cursor-pointer"
                                                        title="Ubah Setting Cycle Time & Cavity">
                                                        ⚙️ Edit
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600">{{ $item->start_time ?? '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-600">{{ $item->end_time ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $statusClass }}">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 space-x-2">
                                                <form method="POST"
                                                    action="{{ route('dailyitemcodes.set-status', $item->id) }}"
                                                    class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="null">
                                                    <button type="submit"
                                                        class="text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 px-2 py-1 rounded text-xs font-bold border border-amber-200 transition cursor-pointer">
                                                        ✖ Proses
                                                    </button>
                                                </form>

                                                <form method="POST"
                                                    action="{{ route('dailyitemcodes.set-status', $item->id) }}"
                                                    class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="1">
                                                    <button type="submit"
                                                        class="text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded text-xs font-bold border border-emerald-200 transition cursor-pointer">
                                                        ✔ Done
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                               <form method="POST"
                                                    action="{{ route('dailyitemcodes.delete', $item->id) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('⚠️ Yakin ingin menghapus Daily Item Code ini? Semua data hourly remarks terkait akan ikut terhapus!');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-gray-400 hover:text-red-700 hover:scale-125 transition-all duration-150 text-sm cursor-pointer"
                                                        title="Hapus Daily Item">
                                                        💀
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        {{-- Detail Hourly Remarks --}}
                                        <tr x-show="open" x-transition>
                                            <td colspan="9" class="bg-gray-50/70 p-4 border-b">
                                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                    <div>
                                                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                                            <span>Detail Hourly Remarks (Shift {{ $item->shift }}):</span>
                                                            <span class="text-xs font-normal text-gray-500">Item: <strong class="text-gray-700">{{ $item->item_code }}</strong></span>
                                                        </h3>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button"
                                                                onclick="openManualHourlyModal({{ $item->id }}, '{{ addslashes($item->item_code) }}', {{ $item->shift }}, '{{ $item->start_date }}')"
                                                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-1 transition cursor-pointer">
                                                            <span>➕</span> Tambah Data Jam Manual
                                                        </button>
                                                        <button type="button"
                                                                onclick="openGenerateShiftModal({{ $item->id }}, '{{ addslashes($item->item_code) }}', {{ $item->shift }}, '{{ $item->start_date }}')"
                                                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-1 transition cursor-pointer">
                                                            <span>⚡</span> Generate Semua Jam Shift {{ $item->shift }}
                                                        </button>
                                                    </div>
                                                </div>

                                                @if ($item->hourlyRemarks->isEmpty())
                                                    <div class="bg-blue-50/60 border border-blue-200 rounded-xl p-4 text-center my-2">
                                                        <p class="text-blue-900 font-semibold text-sm">Belum ada data jam / scan untuk Shift {{ $item->shift }} ini.</p>
                                                        <p class="text-xs text-blue-700 mt-1 mb-3">Jika lupa input/scan saat shift berjalan, Anda bisa membuat data jam secara manual di sini tanpa perlu scan.</p>
                                                        <div class="flex flex-wrap justify-center gap-2">
                                                            <button type="button" 
                                                                    onclick="openManualHourlyModal({{ $item->id }}, '{{ addslashes($item->item_code) }}', {{ $item->shift }}, '{{ $item->start_date }}')"
                                                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3.5 py-2 rounded-lg transition shadow-sm cursor-pointer">
                                                                ➕ Tambah 1 Jam Manual
                                                            </button>
                                                            <button type="button" 
                                                                    onclick="openGenerateShiftModal({{ $item->id }}, '{{ addslashes($item->item_code) }}', {{ $item->shift }}, '{{ $item->start_date }}')"
                                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-3.5 py-2 rounded-lg transition shadow-sm cursor-pointer">
                                                                ⚡ Generate Otomatis 8 Jam Shift {{ $item->shift }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
                                                        <table class="min-w-full text-xs text-gray-700">
                                                            <thead class="bg-gray-100 text-gray-700 uppercase font-semibold border-b">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-left">Start</th>
                                                                    <th class="px-3 py-2 text-left">End</th>
                                                                    <th class="px-3 py-2 text-left">Target</th>
                                                                    <th class="px-3 py-2 text-left">Scan</th>
                                                                    <th class="px-3 py-2 text-left">Actual</th>
                                                                    <th class="px-3 py-2 text-left">NG</th>
                                                                    <th class="px-3 py-2 text-left">Operator (PIC)</th>
                                                                    <th class="px-3 py-2 text-left">Remark</th>
                                                                    <th class="px-3 py-2 text-left">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100">
                                                                @foreach ($item->hourlyRemarks->sortBy('start_time') as $hr)
                                                                    @php
                                                                        $initActual = $hr->actual_production !== null ? $hr->actual_production : 0;
                                                                        $initRemark = $hr->remark ?? '';
                                                                        $dispActual = $hr->actual_production !== null ? (string)$hr->actual_production : '-';
                                                                        $dispRemark = ($hr->remark !== null && trim($hr->remark) !== '') ? $hr->remark : '-';
                                                                        $initPic = $hr->pic ?? '';
                                                                        $dispPic = $hr->pic ?? '-';
                                                                        $initPic2 = $hr->pic_2 ?? '';
                                                                        $initPic3 = $hr->pic_3 ?? '';
                                                                    @endphp
                                                                    <tr class="hover:bg-gray-50/80 transition" 
                                                                        x-data="{ 
                                                                            editing: false, 
                                                                            actual: {{ json_encode($initActual) }}, 
                                                                            target: {{ json_encode($hr->target ?? 0) }},
                                                                            remark: {{ json_encode($initRemark) }},
                                                                            pic: {{ json_encode($initPic) }},
                                                                            displayActual: {{ json_encode($dispActual) }},
                                                                            displayTarget: {{ json_encode($hr->target ?? '-') }},
                                                                            displayRemark: {{ json_encode($dispRemark) }},
                                                                            displayPic: {{ json_encode($dispPic) }},
                                                                            displayPic2: {{ json_encode($initPic2) }},
                                                                            displayPic3: {{ json_encode($initPic3) }},
                                                                            isSaving: false,
                                                                            save() {
                                                                                this.isSaving = true;
                                                                                $.ajax({
                                                                                    url: '{{ url('/hourly-remarks') }}/{{ $hr->id }}/update-remark',
                                                                                    type: 'POST',
                                                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                                                    data: {
                                                                                        actual_production: this.actual,
                                                                                        target: this.target,
                                                                                        remark: this.remark,
                                                                                        pic: this.pic
                                                                                    },
                                                                                    success: (res) => {
                                                                                        this.isSaving = false;
                                                                                        this.editing = false;
                                                                                        this.displayActual = (this.actual !== '' && this.actual !== null) ? this.actual : '-';
                                                                                        this.displayTarget = (this.target !== '' && this.target !== null) ? this.target : '-';
                                                                                        this.displayRemark = (this.remark && this.remark.trim() !== '') ? this.remark : '-';
                                                                                        this.displayPic = this.pic ? this.pic : '-';
                                                                                    },
                                                                                    error: (xhr) => {
                                                                                        this.isSaving = false;
                                                                                        alert('Gagal menyimpan perubahan.');
                                                                                    }
                                                                                });
                                                                            }
                                                                        }">
                                                                        <td class="px-3 py-2 text-gray-700 font-medium">{{ \Carbon\Carbon::parse($hr->start_time)->format('H:i') }}</td>
                                                                        <td class="px-3 py-2 text-gray-500">{{ \Carbon\Carbon::parse($hr->end_time)->format('H:i') }}</td>
                                                                        <td class="px-3 py-2 font-semibold text-gray-700">
                                                                            <span x-show="!editing" x-text="displayTarget"></span>
                                                                            <input x-show="editing" type="number" min="0" x-model="target" class="w-16 border border-indigo-300 rounded px-1.5 py-1 text-xs focus:ring-2 focus:ring-indigo-500 font-bold bg-white">
                                                                        </td>
                                                                        <td class="px-3 py-2 font-semibold text-blue-600">{{ $hr->actual ?? 0 }}</td>
                                                                        
                                                                        <!-- Editable Actual Production -->
                                                                        <td class="px-3 py-2">
                                                                            <span x-show="!editing" class="font-bold text-emerald-700" x-text="displayActual"></span>
                                                                            <input x-show="editing" type="number" min="0" x-model="actual" class="w-20 border border-indigo-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500 font-bold bg-white">
                                                                        </td>

                                                                        <!-- NG Column -->
                                                                        <td class="px-3 py-2 min-w-[130px]" id="hr-ng-col-{{ $hr->id }}">
                                                                            <div class="flex items-center gap-1">
                                                                                <span class="font-bold text-rose-600 font-mono text-xs" id="hr-ng-total-{{ $hr->id }}">{{ $hr->NG ?? 0 }}</span>
                                                                                <span class="text-[11px] text-gray-500">pcs</span>
                                                                            </div>
                                                                            <div class="flex flex-wrap gap-1 mt-1" id="hr-ng-badges-{{ $hr->id }}">
                                                                                @foreach ($hr->ngDetails ?? [] as $detail)
                                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $detail->ng_remarks }}">
                                                                                        {{ $detail->ngType->ng_type ?? 'NG' }}: {{ $detail->ng_quantity }}
                                                                                    </span>
                                                                                @endforeach
                                                                            </div>
                                                                            <button type="button" 
                                                                                    onclick="openNgModal({{ $hr->id }}, '{{ \Carbon\Carbon::parse($hr->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($hr->end_time)->format('H:i') }}', '{{ addslashes($item->item_code) }}')"
                                                                                    class="mt-1.5 inline-flex items-center px-2 py-0.5 text-[11px] font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded transition cursor-pointer">
                                                                                Kelola NG
                                                                            </button>
                                                                        </td>

                                                                        <!-- Operator (PIC) Column -->
                                                                        <td class="px-3 py-2 min-w-[150px]">
                                                                            <div x-show="!editing">
                                                                                <span class="font-semibold text-gray-900 text-xs block" x-text="displayPic"></span>
                                                                                <template x-if="displayPic2">
                                                                                    <span class="text-[10px] text-gray-500 block" x-text="'+ ' + displayPic2"></span>
                                                                                </template>
                                                                                <template x-if="displayPic3">
                                                                                    <span class="text-[10px] text-gray-500 block" x-text="'+ ' + displayPic3"></span>
                                                                                </template>
                                                                            </div>
                                                                            <div x-show="editing" class="space-y-1">
                                                                                <select x-model="pic" class="w-full border border-indigo-300 rounded px-1.5 py-1 text-xs focus:ring-2 focus:ring-indigo-500 bg-white">
                                                                                    <option value="">Pilih Operator</option>
                                                                                    @foreach ($operators ?? [] as $op)
                                                                                        <option value="{{ $op->name }}">{{ $op->name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        </td>

                                                                        <!-- Editable Remark -->
                                                                        <td class="px-3 py-2 min-w-[200px]">
                                                                            <span x-show="!editing" class="whitespace-pre-wrap text-gray-700 block text-xs" x-text="displayRemark"></span>
                                                                            <textarea x-show="editing" x-model="remark" rows="2" class="w-full border border-indigo-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500 bg-white" placeholder="Ketik remark..."></textarea>
                                                                        </td>

                                                                        <!-- Action Column -->
                                                                        <td class="px-3 py-2">
                                                                            <div class="flex items-center gap-2">
                                                                                <button x-show="!editing" type="button" @click="editing = true" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded transition cursor-pointer">
                                                                                    ✏️ Edit
                                                                                </button>

                                                                                <div x-show="editing" class="flex items-center gap-1" style="display: none;">
                                                                                    <button type="button" @click="save()" :disabled="isSaving" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 py-1 rounded text-xs transition shadow-sm cursor-pointer">
                                                                                        <span x-show="!isSaving">💾 Simpan</span>
                                                                                        <span x-show="isSaving">...</span>
                                                                                    </button>
                                                                                    <button type="button" @click="editing = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-2 py-1 rounded text-xs transition cursor-pointer">
                                                                                        Batal
                                                                                    </button>
                                                                                </div>

                                                                                <form action="{{ route('hourlyremarks.destroy', $hr->id) }}" method="POST" onsubmit="return confirm('Yakin hapus remark ini beserta data scan terkait?')" class="inline">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold hover:underline cursor-pointer">Hapus</button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script>
    function toggleAddForm() {
        const form = document.getElementById('addForm');
        form.classList.toggle('hidden');
    }

    const addForm = document.getElementById('addForm');
    const closeAddForm = document.getElementById('closeAddForm');
    const btnCancelAddForm = document.getElementById('btnCancelAddForm');
    if (closeAddForm) {
        closeAddForm.addEventListener('click', () => addForm.classList.add('hidden'));
    }
    if (btnCancelAddForm) {
        btnCancelAddForm.addEventListener('click', () => addForm.classList.add('hidden'));
    }

    // Modal Manual Hourly Remark
    function openManualHourlyModal(dicId, itemCode, shift, startDate) {
        document.getElementById('m_dic_id').value = dicId;
        document.getElementById('m_item_code').textContent = itemCode;
        document.getElementById('m_shift').textContent = 'Shift ' + shift;

        const select = document.getElementById('m_start_time');
        select.innerHTML = '';

        let slots = [];
        if (shift == 1) {
            slots = [
                { time: '07:30', label: '07:30 - 08:30 (Jam 1)' },
                { time: '08:30', label: '08:30 - 09:30 (Jam 2)' },
                { time: '09:30', label: '09:30 - 10:30 (Jam 3)' },
                { time: '10:30', label: '10:30 - 11:30 (Jam 4)' },
                { time: '11:30', label: '11:30 - 12:30 (Jam 5)' },
                { time: '12:30', label: '12:30 - 13:30 (Jam 6)' },
                { time: '13:30', label: '13:30 - 14:30 (Jam 7)' },
                { time: '14:30', label: '14:30 - 15:30 (Jam 8)' },
            ];
        } else if (shift == 2) {
            slots = [
                { time: '15:30', label: '15:30 - 16:30 (Jam 1)' },
                { time: '16:30', label: '16:30 - 17:30 (Jam 2)' },
                { time: '17:30', label: '17:30 - 18:30 (Jam 3)' },
                { time: '18:30', label: '18:30 - 19:30 (Jam 4)' },
                { time: '19:30', label: '19:30 - 20:30 (Jam 5)' },
                { time: '20:30', label: '20:30 - 21:30 (Jam 6)' },
                { time: '21:30', label: '21:30 - 22:30 (Jam 7)' },
                { time: '22:30', label: '22:30 - 23:30 (Jam 8)' },
            ];
        } else { // Shift 3
            slots = [
                { time: '23:30', label: '23:30 - 00:30 (Jam 1 - Malam)' },
                { time: '00:30', label: '00:30 - 01:30 (Jam 2)' },
                { time: '01:30', label: '01:30 - 02:30 (Jam 3)' },
                { time: '02:30', label: '02:30 - 03:30 (Jam 4)' },
                { time: '03:30', label: '03:30 - 04:30 (Jam 5)' },
                { time: '04:30', label: '04:30 - 05:30 (Jam 6)' },
                { time: '05:30', label: '05:30 - 06:30 (Jam 7)' },
                { time: '06:30', label: '06:30 - 07:30 (Jam 8 - Pagi)' },
            ];
        }

        slots.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.time;
            opt.textContent = s.label;
            select.appendChild(opt);
        });

        // Add custom option
        const customOpt = document.createElement('option');
        customOpt.value = 'CUSTOM';
        customOpt.textContent = '🕒 Jam Kustom / Manual Lainnya...';
        select.appendChild(customOpt);

        document.getElementById('m_custom_time_wrapper').classList.add('hidden');
        document.getElementById('manualHourlyModal').classList.remove('hidden');
    }

    function closeManualHourlyModal() {
        document.getElementById('manualHourlyModal').classList.add('hidden');
    }

    // Modal Generate Semua Jam Shift
    function openGenerateShiftModal(dicId, itemCode, shift, startDate) {
        document.getElementById('g_dic_id').value = dicId;
        document.getElementById('g_item_code').textContent = itemCode;
        document.getElementById('g_shift_badge').textContent = 'Shift ' + shift;
        document.getElementById('generateShiftModal').classList.remove('hidden');
    }

    function closeGenerateShiftModal() {
        document.getElementById('generateShiftModal').classList.add('hidden');
    }

    // Modal Edit Cycle Time & Cavity Daily Item
    function openEditCtCavityModal(dicId, itemCode, shift, currentCt, currentCavity) {
        document.getElementById('ct_dic_id').value = dicId;
        document.getElementById('ct_item_code').textContent = itemCode;
        document.getElementById('ct_shift').textContent = 'Shift ' + shift;
        document.getElementById('ct_cycle_time_input').value = currentCt || '';
        document.getElementById('ct_cavity_input').value = currentCavity || '1';

        const form = document.getElementById('editCtCavityForm');
        form.action = `/daily-item-codes/${dicId}/cycle-time-cavity`;

        document.getElementById('editCtCavityModal').classList.remove('hidden');
    }

    function closeEditCtCavityModal() {
        document.getElementById('editCtCavityModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Toggle Custom Time in Manual Modal
        const mStartTime = document.getElementById('m_start_time');
        if (mStartTime) {
            mStartTime.addEventListener('change', function () {
                const wrapper = document.getElementById('m_custom_time_wrapper');
                if (this.value === 'CUSTOM') {
                    wrapper.classList.remove('hidden');
                } else {
                    wrapper.classList.add('hidden');
                }
            });
        }

        // Handle custom time on submit in Manual Modal
        const manualForm = document.getElementById('manualHourlyForm');
        if (manualForm) {
            manualForm.addEventListener('submit', function (e) {
                if (mStartTime.value === 'CUSTOM') {
                    const customStart = document.getElementById('m_custom_start_time').value;
                    if (!customStart) {
                        e.preventDefault();
                        alert('Silakan tentukan Jam Mulai Kustom.');
                        return false;
                    }
                    mStartTime.value = customStart;
                }
            });
        }

        // Toggle Auto Generate Slots checkbox in Add Daily Item Modal
        const autoSlotsCb = document.getElementById('auto_generate_slots');
        const autoSlotsWrapper = document.getElementById('auto_generate_slots_wrapper');
        const addItemPic = document.getElementById('add_item_pic');
        if (autoSlotsCb) {
            autoSlotsCb.addEventListener('change', function () {
                if (this.checked) {
                    autoSlotsWrapper.classList.remove('hidden');
                    if (addItemPic) addItemPic.setAttribute('required', 'required');
                } else {
                    autoSlotsWrapper.classList.add('hidden');
                    if (addItemPic) addItemPic.removeAttribute('required');
                }
            });
        }

        // Autocomplete Item Code in Add Daily Item Modal
        const input = document.getElementById('item_code');
        const select = document.getElementById('item_code_list');
        let timeout = null;

        if (input && select) {
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                const query = input.value.trim();

                if (query.length < 1) {
                    select.innerHTML = '';
                    select.classList.add('hidden');
                    return;
                }

                timeout = setTimeout(() => {
                    fetch(`{{ route('daily-item-code.get-item-codes') }}?search=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            const items = data.items;
                            select.innerHTML = '';

                            if (items.length === 0) {
                                select.classList.add('hidden');
                                return;
                            }

                            items.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.value;
                                option.textContent = item.text;
                                select.appendChild(option);
                            });

                            select.classList.remove('hidden');
                        })
                        .catch(err => console.error('Fetch error:', err));
                }, 300);
            });

            select.addEventListener('change', function() {
                input.value = select.value;
                select.classList.add('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!select.contains(e.target) && e.target !== input) {
                    select.classList.add('hidden');
                }
            });
        }

        // Shift otomatis set start & end time, serta tanggal selesai untuk Shift 3
        const shiftEl = document.getElementById('shift');
        const startDateInput = document.getElementById('start_schedule_date');
        const endDateInput = document.getElementById('end_schedule_date');
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');

        function updateShiftSchedule() {
            if (!shiftEl) return;
            const shiftVal = shiftEl.value;

            switch (shiftVal) {
                case '1':
                    startInput.value = '07:30';
                    endInput.value = '15:30';
                    if (startDateInput && endDateInput && startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    }
                    break;
                case '2':
                    startInput.value = '15:30';
                    endInput.value = '23:30';
                    if (startDateInput && endDateInput && startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    }
                    break;
                case '3':
                    startInput.value = '23:30';
                    endInput.value = '07:30';
                    // Shift 3 melewati tengah malam: Tanggal Selesai otomatis hari berikutnya
                    if (startDateInput && endDateInput && startDateInput.value) {
                        const d = new Date(startDateInput.value);
                        d.setDate(d.getDate() + 1);
                        const yyyy = d.getFullYear();
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        const dd = String(d.getDate()).padStart(2, '0');
                        endDateInput.value = `${yyyy}-${mm}-${dd}`;
                    }
                    break;
                default:
                    startInput.value = '';
                    endInput.value = '';
            }
        }

        if (shiftEl) {
            shiftEl.addEventListener('change', updateShiftSchedule);
        }
        if (startDateInput) {
            startDateInput.addEventListener('change', updateShiftSchedule);
        }
    });

    // ==============================================================
    // NG (DEFECT) MANAGEMENT
    // ==============================================================
    let currentHourlyRemarkId = null;

    function openNgModal(hourlyRemarkId, slotTime, itemCode) {
        currentHourlyRemarkId = hourlyRemarkId;
        document.getElementById('ng_modal_slot_time').textContent = slotTime;
        document.getElementById('ng_modal_item_code').textContent = itemCode;
        document.getElementById('add_ng_quantity').value = '';
        document.getElementById('add_ng_remarks').value = '';
        document.getElementById('add_ng_type_id').value = '';

        document.getElementById('ngManagementModal').classList.remove('hidden');
        loadNgDetails(hourlyRemarkId);
    }

    function closeNgModal() {
        document.getElementById('ngManagementModal').classList.add('hidden');
        currentHourlyRemarkId = null;
    }

    function closeEditNgDetailModal() {
        document.getElementById('editNgDetailModal').classList.add('hidden');
    }

    function loadNgDetails(hourlyRemarkId) {
        const loading = document.getElementById('ng_details_loading');
        const empty = document.getElementById('ng_details_empty');
        const tableWrapper = document.getElementById('ng_details_table_wrapper');
        const tbody = document.getElementById('ng_details_tbody');
        const totalDisplay = document.getElementById('ng_modal_total_display');

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        tableWrapper.classList.add('hidden');
        tbody.innerHTML = '';

        fetch(`/hourly-remark/${hourlyRemarkId}/ng-details`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.add('hidden');
            totalDisplay.textContent = data.total_ng ?? 0;
            
            // Update table row in main view too
            updateTableRowNg(hourlyRemarkId, data.total_ng ?? 0, data.ng_details || []);

            if (!data.ng_details || data.ng_details.length === 0) {
                empty.classList.remove('hidden');
            } else {
                tableWrapper.classList.remove('hidden');
                let rowsHtml = '';
                data.ng_details.forEach(item => {
                    rowsHtml += `
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-3 py-2 font-medium text-gray-900">${escapeHtml(item.ng_type_name)}</td>
                            <td class="px-3 py-2 font-bold text-rose-600 font-mono">${item.ng_quantity}</td>
                            <td class="px-3 py-2 text-gray-600">${escapeHtml(item.ng_remarks || '-')}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button type="button" onclick="openEditNgDetailModal(${item.id})" class="text-blue-600 hover:text-blue-800 font-semibold text-xs mr-2 cursor-pointer">Edit</button>
                                <button type="button" onclick="deleteNgDetail(${item.id})" class="text-rose-600 hover:text-rose-800 font-semibold text-xs cursor-pointer">Hapus</button>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            }
        })
        .catch(err => {
            loading.classList.add('hidden');
            alert('Gagal memuat data NG: ' + err.message);
        });
    }

    function updateTableRowNg(hourlyRemarkId, totalNg, ngDetails) {
        const totalEl = document.getElementById(`hr-ng-total-${hourlyRemarkId}`);
        if (totalEl) {
            totalEl.textContent = totalNg;
        }
        const badgesEl = document.getElementById(`hr-ng-badges-${hourlyRemarkId}`);
        if (badgesEl) {
            if (!ngDetails || ngDetails.length === 0) {
                badgesEl.innerHTML = '';
            } else {
                let badgesHtml = '';
                ngDetails.forEach(item => {
                    badgesHtml += `
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="${escapeHtml(item.ng_remarks || '')}">
                            ${escapeHtml(item.ng_type_name)}: ${item.ng_quantity}
                        </span>
                    `;
                });
                badgesEl.innerHTML = badgesHtml;
            }
        }
    }

    function submitAddNg(e) {
        e.preventDefault();
        if (!currentHourlyRemarkId) return;

        const btn = document.getElementById('btn_submit_add_ng');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        const ngTypeId = document.getElementById('add_ng_type_id').value;
        const quantity = document.getElementById('add_ng_quantity').value;
        const remarks = document.getElementById('add_ng_remarks').value;

        fetch(`/hourly-remark/${currentHourlyRemarkId}/add-ng`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ng_type_id: ngTypeId,
                ng_quantity: quantity,
                ng_remarks: remarks
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Simpan NG';
            if (data.success) {
                document.getElementById('add_ng_quantity').value = '';
                document.getElementById('add_ng_remarks').value = '';
                loadNgDetails(currentHourlyRemarkId);
            } else {
                alert(data.message || 'Gagal menambahkan NG.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Simpan NG';
            alert('Gagal menambahkan NG: ' + err.message);
        });
    }

    function openEditNgDetailModal(id) {
        fetch(`/ng-detail/${id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_ng_detail_id').value = data.id;
            document.getElementById('edit_ng_type_id').value = data.ng_type_id;
            document.getElementById('edit_ng_quantity').value = data.ng_quantity;
            document.getElementById('edit_ng_remarks').value = data.ng_remarks || '';
            document.getElementById('editNgDetailModal').classList.remove('hidden');
        })
        .catch(err => alert('Gagal mengambil data NG: ' + err.message));
    }

    function submitUpdateNg(e) {
        e.preventDefault();
        const id = document.getElementById('edit_ng_detail_id').value;
        const btn = document.getElementById('btn_submit_update_ng');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        const ngTypeId = document.getElementById('edit_ng_type_id').value;
        const quantity = document.getElementById('edit_ng_quantity').value;
        const remarks = document.getElementById('edit_ng_remarks').value;

        fetch(`/ng-detail/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ng_type_id: ngTypeId,
                ng_quantity: quantity,
                ng_remarks: remarks
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Simpan Perubahan';
            if (data.success) {
                closeEditNgDetailModal();
                if (currentHourlyRemarkId) {
                    loadNgDetails(currentHourlyRemarkId);
                }
            } else {
                alert(data.message || 'Gagal mengubah NG.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Simpan Perubahan';
            alert('Gagal mengubah NG: ' + err.message);
        });
    }

    function deleteNgDetail(id) {
        if (!confirm('Yakin ingin menghapus data NG ini?')) return;

        fetch(`/ng-detail/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (currentHourlyRemarkId) {
                    loadNgDetails(currentHourlyRemarkId);
                }
            } else {
                alert(data.message || 'Gagal menghapus NG.');
            }
        })
        .catch(err => alert('Gagal menghapus NG: ' + err.message));
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    </script>
</x-app-layout>
