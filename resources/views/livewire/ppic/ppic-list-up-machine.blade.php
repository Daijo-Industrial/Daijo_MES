<div class="p-2.5 sm:p-4 lg:p-5 bg-slate-50 min-h-screen space-y-4">
    <style>
        /* Hilangkan spinner number bawaan browser agar angka tidak tertutup panah */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] { 
            -moz-appearance: textfield; 
        }

        /* Custom scrollbar horizontal yang ramping, kontras, dan mudah digeser */
        .table-scroll-container::-webkit-scrollbar {
            height: 9px;
            width: 8px;
        }
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 6px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 6px;
            border: 2px solid #f1f5f9;
        }
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Freeze / Sticky Columns saat zoom atau layar sempit */
        .sticky-col-no {
            position: sticky;
            left: 0;
        }
        .sticky-col-machine {
            position: sticky;
            left: 36px;
        }
        .sticky-col-action {
            position: sticky;
            right: 0;
        }
    </style>

    <!-- Top Action Card & Header (Responsive 2-Tier Layout) -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/80 space-y-3.5">
        
        <!-- Header Tier 1: Title, Status & Primary CTAs -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl shadow-md shadow-indigo-100 shrink-0">
                    📋
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                            List Up Production Machine
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $status === 'GENERATED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                            {{ $status === 'GENERATED' ? '✓ GENERATED (Jadwal Sah)' : '📝 DRAFT (Sedang Diedit)' }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                        Perencanaan jadwal produksi per mesin PPIC dengan auto-generate ke <strong>Daily Item Codes</strong>.
                    </p>
                </div>
            </div>

            <!-- Primary Action Buttons (Pinned together, never dangling) -->
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                @if(!$isLocked)
                    <button wire:click="saveDraft" 
                            type="button"
                            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95 shrink-0">
                        <span>💾</span>
                        <span>Simpan Draft</span>
                    </button>

                    <button wire:click="generateToDailyItemCodes" 
                            type="button"
                            wire:confirm="Generate jadwal ke Daily Item Codes untuk seluruh shift aktif? Data yang cocok akan diupdate."
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-sm shadow-indigo-200 cursor-pointer active:scale-95 shrink-0">
                        <span>⚡</span>
                        <span>Generate ke Daily Item Codes</span>
                    </button>
                @else
                    <button wire:click="reopenDraft" 
                            type="button"
                            wire:confirm="Buka kunci kembali jadwal ini ke status DRAFT agar dapat diedit?"
                            class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95 shrink-0">
                        <span>🔓</span>
                        <span>Buka Kunci (Edit)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Header Tier 2: Date Navigation, History & Badges -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Quick Date Nav & Picker -->
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl border border-slate-200">
                    <button wire:click="goToPreviousDay" 
                            type="button"
                            class="px-2 py-1 bg-white hover:bg-slate-200 rounded-lg text-xs font-bold text-slate-700 transition cursor-pointer"
                            title="Kemarin">
                        &larr;
                    </button>
                    
                    <input type="date" wire:model.live="selectedDate" 
                           class="bg-transparent text-xs font-black text-slate-800 px-2 py-1 outline-none cursor-pointer">

                    <button wire:click="goToNextDay" 
                            type="button"
                            class="px-2 py-1 bg-white hover:bg-slate-200 rounded-lg text-xs font-bold text-slate-700 transition cursor-pointer"
                            title="Besok">
                        &rarr;
                    </button>
                </div>

                <!-- Shortcuts: Hari Ini & Besok -->
                <div class="flex items-center gap-1">
                    <button wire:click="goToToday" 
                            type="button"
                            class="px-2.5 py-1.5 {{ $selectedDate === now()->format('Y-m-d') ? 'bg-indigo-600 text-white font-black' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold' }} rounded-xl text-xs transition cursor-pointer">
                        Hari Ini
                    </button>
                    <button wire:click="setDate('{{ now()->addDay()->format('Y-m-d') }}')" 
                            type="button"
                            class="px-2.5 py-1.5 {{ $selectedDate === now()->addDay()->format('Y-m-d') ? 'bg-indigo-600 text-white font-black' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold' }} rounded-xl text-xs transition cursor-pointer">
                        Besok
                    </button>
                </div>

                <!-- Button Riwayat List Up -->
                <button wire:click="openHistory" 
                        type="button"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>📅</span>
                    <span>Riwayat List Up</span>
                </button>

                <!-- Status Badge -->
                @if($isLocked)
                    <div class="flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-black">
                        <span>🔒</span>
                        <span>FINAL</span>
                    </div>
                @else
                    <div class="flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-[11px] font-black">
                        <span>📝</span>
                        <span>DRAFT</span>
                    </div>
                @endif
            </div>

            <!-- Summary Chips -->
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <span class="bg-slate-100 px-2.5 py-1 rounded-lg text-slate-700 font-bold">
                    {{ count($rows) }} Mesin
                </span>
                <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2.5 py-1 rounded-lg font-black">
                    {{ number_format(collect($rows)->sum(fn($r) => (int) ($r['qty_to_run'] ?? 0))) }} pcs
                </span>
            </div>
        </div>

        <!-- Alert Notification Banner -->
        @if($alertMessage)
            <div class="p-3.5 rounded-xl border flex items-center justify-between gap-3 animate-in fade-in duration-300 {{ $alertType === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : ($alertType === 'info' ? 'bg-blue-50 border-blue-300 text-blue-900' : 'bg-red-50 border-red-300 text-red-900') }}">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">{{ $alertType === 'success' ? '✅' : ($alertType === 'info' ? 'ℹ️' : '⚠️') }}</span>
                    <span class="text-xs font-bold">{{ $alertMessage }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('daily-item-code.daily', ['date' => $selectedDate]) }}" target="_blank" 
                       class="px-2.5 py-1 bg-white hover:bg-slate-50 text-indigo-700 font-black text-[11px] rounded-lg border border-indigo-200 transition shadow-2xs">
                        Lihat Jadwal Harian &rarr;
                    </a>
                    <button wire:click="$set('alertMessage', null)" class="text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Spreadsheet Table Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
        
        @if($isLocked)
            <!-- Finalized / Locked Banner -->
            <div class="m-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3 text-xs text-emerald-900 font-bold">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">🔒</span>
                    <div>
                        <span class="font-black uppercase tracking-wider block">List Up Sudah Difinalisasi (Read-Only)</span>
                        <span class="text-[11px] text-emerald-700 font-semibold">Jadwal tanggal ini sudah digenerate ke Daily Item Codes dan terkunci dari perubahan.</span>
                    </div>
                </div>
                <button wire:click="reopenDraft" 
                        type="button"
                        wire:confirm="Buka kunci kembali jadwal ini ke status DRAFT agar dapat diedit?"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 rounded-lg text-xs font-black transition shadow-xs cursor-pointer">
                    🔓 Buka Kunci (Edit Kembali)
                </button>
            </div>
        @endif

        <!-- Table Toolbar -->
        <div class="p-3 sm:p-4 bg-slate-50/80 border-b border-slate-200 flex justify-between items-center flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">
                    Daftar Mesin & Rencana Part ({{ count($rows) }} Baris)
                </span>
                <span class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full font-bold">
                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-[10px] text-slate-400 font-semibold hidden md:inline-flex items-center gap-1">
                    <span>💡</span>
                    <span>No & Mesin terkunci di kiri</span>
                </span>
                @if(!$isLocked)
                    <button wire:click="addRow" 
                            type="button"
                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-black transition flex items-center gap-1 cursor-pointer">
                        <span>➕</span>
                        <span>Tambah Baris Baru</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Scrollable Table with Frozen Columns (No & Machine) -->
        <div class="overflow-x-auto table-scroll-container relative">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="sticky top-0 z-20 bg-slate-100 shadow-2xs">
                    <tr class="bg-slate-100 text-slate-600 text-[10px] uppercase font-black tracking-wider border-b border-slate-200">
                        <th class="py-2.5 px-1.5 text-center w-9 sticky-col-no bg-slate-100 border-r border-slate-200 z-25">No</th>
                        <th class="py-2.5 px-2 w-28 min-w-[110px] sticky-col-machine bg-slate-100 border-r border-slate-200 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] z-25">Machine</th>
                        <th class="py-2.5 px-2.5 w-40 min-w-[140px]">Part No</th>
                        <th class="py-2.5 px-2.5 w-44 max-w-[180px]">Description</th>
                        <th class="py-2.5 px-2 w-28 min-w-[100px]">M'trial Type</th>
                        <th class="py-2.5 px-2 w-44 min-w-[155px] text-center">SPK No</th>
                        <th class="py-2.5 px-1.5 w-12 text-center">Cav</th>
                        <th class="py-2.5 px-1.5 w-12 text-center">C/T</th>
                        <th class="py-2.5 px-1.5 w-14 text-center">Targ/H</th>
                        <th class="py-2.5 px-2 w-32 min-w-[120px] text-center bg-indigo-50/70 border-x border-indigo-100">
                            <div>Operator</div>
                            <div class="grid grid-cols-3 gap-0.5 text-[9px] text-indigo-700 font-extrabold mt-0.5">
                                <span>I</span>
                                <span>II</span>
                                <span>III</span>
                            </div>
                        </th>
                        <th class="py-2.5 px-2.5 w-24 text-center">QTY TO RUN</th>
                        <th class="py-2.5 px-2.5 w-36 min-w-[130px]">Alokasi / Shift</th>
                        <th class="py-2.5 px-2.5 w-36 min-w-[120px]">Reason</th>
                        <th class="py-2.5 px-1 text-center w-10 sticky-col-action bg-slate-100 border-l border-slate-200 shadow-[-2px_0_4px_-2px_rgba(0,0,0,0.1)] z-25">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($rows as $index => $row)
                        @php
                            $activeShifts = [];
                            if ((int) ($row['operator_shift_1'] ?? 0) > 0) $activeShifts[] = 1;
                            if ((int) ($row['operator_shift_2'] ?? 0) > 0) $activeShifts[] = 2;
                            if ((int) ($row['operator_shift_3'] ?? 0) > 0) $activeShifts[] = 3;
                            $countActive = count($activeShifts);
                            $qtyToRun = (int) ($row['qty_to_run'] ?? 0);
                            $baseQty = $countActive > 0 ? intdiv($qtyToRun, $countActive) : 0;
                            $remQty = $countActive > 0 ? ($qtyToRun % $countActive) : 0;
                            $rowBg = ($row['is_change_to'] ?? false) ? 'bg-amber-50/70' : 'bg-white';
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $rowBg }}" wire:key="row-{{ $index }}">
                            
                            <!-- No (Sticky Frozen Column 1) -->
                            <td class="py-2 px-1 text-center text-slate-400 font-black text-[11px] sticky-col-no {{ $rowBg }} border-r border-slate-200 z-10">
                                {{ $index + 1 }}
                            </td>

                            <!-- Machine (Sticky Frozen Column 2) -->
                            <td class="py-2 px-1.5 sticky-col-machine {{ $rowBg }} border-r border-slate-200 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)] z-10">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1">
                                        <select wire:model.live="rows.{{ $index }}.machine_name" 
                                                wire:change="onMachineChange({{ $index }})"
                                                @disabled($isLocked)
                                                class="w-full bg-white border border-slate-200 rounded-lg px-1.5 py-1 text-xs font-black text-slate-800 uppercase focus:border-indigo-500 outline-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed opacity-80' : '' }}">
                                            <option value="">-- Pilih --</option>
                                            @foreach($availableMachines as $m)
                                                <option value="{{ $m['name'] }}">{{ $m['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($row['is_change_to'] ?? false)
                                        <span class="inline-block bg-amber-400 text-slate-900 text-[8px] font-black uppercase px-1 rounded">
                                            CHANGE TO
                                        </span>
                                    @elseif(!$isLocked)
                                        <button wire:click="addRow('{{ $row['machine_name'] }}', {{ $row['machine_id'] ?? 'null' }}, true, {{ $index }})" 
                                                type="button"
                                                class="text-[9px] text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center gap-0.5 cursor-pointer"
                                                title="Tambah Part Change To pada Mesin Ini">
                                            <span>+ Change To</span>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Part No -->
                            <td class="py-2 px-2.5">
                                <div class="flex items-center gap-1">
                                    <input type="text" 
                                           wire:model.lazy="rows.{{ $index }}.part_no" 
                                           wire:change="onPartNoInput({{ $index }})"
                                           @disabled($isLocked)
                                           placeholder="Part No..." 
                                           class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-extrabold text-slate-900 uppercase focus:border-indigo-500 outline-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                                    
                                    @if(!$isLocked)
                                        <button wire:click="openPartSearch({{ $index }})" 
                                                type="button"
                                                class="p-1 bg-slate-100 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 rounded-lg border border-slate-200 transition shrink-0 cursor-pointer"
                                                title="Cari dari Master List Item">
                                            🔍
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Description -->
                            <td class="py-2 px-2.5 max-w-[180px]">
                                <span class="text-xs font-bold text-slate-800 block truncate" title="{{ $row['description'] ?? '' }}">
                                    {{ $row['description'] ?: '-' }}
                                </span>
                            </td>

                            <!-- M'trial Type -->
                            <td class="py-2 px-2">
                                <input type="text" 
                                       wire:model.defer="rows.{{ $index }}.material_type" 
                                       @disabled($isLocked)
                                       placeholder="Raw Material..." 
                                       class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-bold text-slate-700 uppercase focus:border-indigo-500 outline-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}"
                                       title="Material type dari Master BOM / Master List Material">
                            </td>

                            <!-- SPK No -->
                            <td class="py-2 px-2">
                                <div class="flex items-center gap-1">
                                    @if(!($row['is_manual_spk'] ?? false) && !empty($row['available_spks']))
                                        <select wire:model.live="rows.{{ $index }}.spk_no" 
                                                @disabled($isLocked)
                                                class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-extrabold text-indigo-700 focus:border-indigo-500 outline-none truncate {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}"
                                                title="Pilih SPK untuk Part ini">
                                            <option value="">-- Pilih SPK --</option>
                                            @foreach($row['available_spks'] as $spk)
                                                <option value="{{ $spk['spk_number'] }}">
                                                    {{ $spk['spk_number'] }} (Sisa: {{ number_format($spk['remaining_quantity'], 0) }})
                                                </option>
                                            @endforeach
                                        </select>

                                        @if(!$isLocked)
                                            <button wire:click="toggleManualSpk({{ $index }})" 
                                                    type="button"
                                                    class="p-1 bg-slate-100 hover:bg-amber-50 text-slate-400 hover:text-amber-600 rounded-lg border border-slate-200 transition shrink-0 cursor-pointer text-xs"
                                                    title="Ketik SPK Secara Manual">
                                                ✏️
                                            </button>
                                        @endif
                                    @else
                                        <input type="text" 
                                                wire:model.defer="rows.{{ $index }}.spk_no" 
                                                @disabled($isLocked)
                                                placeholder="Ketik SPK..." 
                                                class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-extrabold text-indigo-700 uppercase focus:border-indigo-500 outline-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}"
                                                title="SPK No">

                                        @if(!empty($row['available_spks']) && !$isLocked)
                                            <button wire:click="toggleManualSpk({{ $index }})" 
                                                    type="button"
                                                    class="p-1 bg-slate-100 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 rounded-lg border border-slate-200 transition shrink-0 cursor-pointer text-xs"
                                                    title="Kembali ke Dropdown SPK">
                                                📋
                                            </button>
                                        @endif
                                    @endif

                                    @if(!$isLocked)
                                        <button wire:click="openSpkSearch({{ $index }})" 
                                                type="button"
                                                class="p-1 bg-slate-100 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 rounded-lg border border-slate-200 transition shrink-0 cursor-pointer text-xs"
                                                title="Cari dari Seluruh SPK Master">
                                            🔍
                                        </button>
                                    @endif
                                </div>

                                @if(!empty(trim($row['part_no'] ?? '')) && empty($row['spk_no']))
                                    <div class="mt-1 flex items-center justify-center gap-1 text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 rounded px-1.5 py-0.5">
                                        <span>⚠️</span>
                                        <span>Tidak ada SPK</span>
                                    </div>
                                @elseif(!empty(trim($row['part_no'] ?? '')) && empty($row['available_spks']))
                                    <div class="mt-1 flex items-center justify-center gap-1 text-[9px] font-extrabold text-amber-600 bg-amber-50 border border-amber-200 rounded px-1.5 py-0.5" title="SPK ini diinput manual (tidak ditemukan di SPK Master)">
                                        <span>ℹ️</span>
                                        <span>SPK Manual</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Cavity -->
                            <td class="py-2 px-1.5 text-center">
                                <input type="number" min="1"
                                       wire:key="cav-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                       wire:model.blur="rows.{{ $index }}.cavity" 
                                       value="{{ $row['cavity'] ?? 1 }}"
                                       @disabled($isLocked)
                                       class="w-11 bg-white border border-slate-200 rounded-lg px-1 py-1 text-xs font-bold text-center focus:border-indigo-500 outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                            </td>

                            <!-- Cycle Time -->
                            <td class="py-2 px-1.5 text-center">
                                <input type="number" min="0"
                                       wire:key="ct-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                       wire:model.blur="rows.{{ $index }}.cycle_time" 
                                       wire:change="onCycleTimeChanged({{ $index }})"
                                       value="{{ $row['cycle_time'] ?? 0 }}"
                                       @disabled($isLocked)
                                       class="w-11 bg-white border border-slate-200 rounded-lg px-1 py-1 text-xs font-bold text-center focus:border-indigo-500 outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                            </td>

                            <!-- Target/H -->
                            <td class="py-2 px-1.5 text-center">
                                <span class="inline-block bg-slate-100 text-slate-800 px-1.5 py-1 rounded-lg text-xs font-black">
                                    {{ $row['target_per_hour'] ?? 0 }}
                                </span>
                            </td>

                            <!-- Operator (I, II, III) -->
                            <td class="py-2 px-1.5 bg-indigo-50/30 border-x border-indigo-100">
                                <div class="grid grid-cols-3 gap-1">
                                    <div class="relative">
                                        <input type="number" min="0" max="99"
                                               wire:key="op1-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                               wire:model.live.debounce.250ms="rows.{{ $index }}.operator_shift_1" 
                                               value="{{ $row['operator_shift_1'] ?? '' }}"
                                               placeholder="0"
                                               @disabled($isLocked)
                                               class="w-full bg-white border border-indigo-200 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 rounded-lg py-1 px-0.5 text-xs font-black text-center text-indigo-950 outline-none transition shadow-2xs [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed opacity-75' : '' }}"
                                               title="Jumlah Operator Shift 1">
                                    </div>
                                    <div class="relative">
                                        <input type="number" min="0" max="99"
                                               wire:key="op2-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                               wire:model.live.debounce.250ms="rows.{{ $index }}.operator_shift_2" 
                                               value="{{ $row['operator_shift_2'] ?? '' }}"
                                               placeholder="0"
                                               @disabled($isLocked)
                                               class="w-full bg-white border border-indigo-200 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 rounded-lg py-1 px-0.5 text-xs font-black text-center text-indigo-950 outline-none transition shadow-2xs [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed opacity-75' : '' }}"
                                               title="Jumlah Operator Shift 2">
                                    </div>
                                    <div class="relative">
                                        <input type="number" min="0" max="99"
                                               wire:key="op3-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                               wire:model.live.debounce.250ms="rows.{{ $index }}.operator_shift_3" 
                                               value="{{ $row['operator_shift_3'] ?? '' }}"
                                               placeholder="0"
                                               @disabled($isLocked)
                                               class="w-full bg-white border border-indigo-200 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-500 rounded-lg py-1 px-0.5 text-xs font-black text-center text-indigo-950 outline-none transition shadow-2xs [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed opacity-75' : '' }}"
                                               title="Jumlah Operator Shift 3">
                                    </div>
                                </div>
                            </td>

                            <!-- QTY TO RUN -->
                            <td class="py-2 px-2.5">
                                <input type="number" min="0" 
                                       wire:key="qty-{{ $index }}-{{ $row['id'] ?? 'temp' }}"
                                       wire:model.live.debounce.250ms="rows.{{ $index }}.qty_to_run" 
                                       value="{{ $row['qty_to_run'] ?? '' }}"
                                       @disabled($isLocked)
                                       placeholder="0"
                                       class="w-full bg-white border-2 border-indigo-300 rounded-lg px-2 py-1 text-xs font-black text-right text-indigo-900 focus:border-indigo-600 outline-none transition [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                            </td>

                            <!-- Alokasi / Shift Preview -->
                            <td class="py-2 px-2.5">
                                @if($countActive > 0 && $qtyToRun > 0)
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @foreach($activeShifts as $sIdx => $shiftNum)
                                                @php
                                                    $sQty = $baseQty + ($sIdx < $remQty ? 1 : 0);
                                                @endphp
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-100 text-indigo-800">
                                                    S{{ $shiftNum }}: {{ number_format($sQty) }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-semibold block">
                                            ({{ $countActive }} Shift Aktif)
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-300 italic">Belum Ada Shift</span>
                                @endif
                            </td>

                            <!-- Reason -->
                            <td class="py-2 px-2.5">
                                <input type="text" 
                                       wire:model.defer="rows.{{ $index }}.reason" 
                                       @disabled($isLocked)
                                       placeholder="Ex: LGS, MOULD..." 
                                       class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:border-indigo-500 outline-none {{ $isLocked ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                            </td>

                            <!-- Aksi (Sticky Frozen Column Right) -->
                            <td class="py-2 px-1 text-center sticky-col-action {{ $rowBg }} border-l border-slate-200 shadow-[-2px_0_4px_-2px_rgba(0,0,0,0.08)] z-10">
                                @if(!$isLocked)
                                    <button wire:click="removeRow({{ $index }})" 
                                            type="button"
                                            wire:confirm="Hapus baris ini dari list up?"
                                            class="p-1 text-slate-300 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                            title="Hapus Baris">
                                        🗑️
                                    </button>
                                @else
                                    <span class="text-slate-300 text-xs" title="Jadwal Terkunci">🔒</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="py-12 text-center text-slate-400 italic">
                                Belum ada baris permesinan. Klik <strong>➕ Tambah Baris Baru</strong> di atas untuk mulai membuat jadwal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Summary -->
        <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="text-xs text-slate-500 font-medium">
                Total Baris: <strong class="text-slate-800">{{ count($rows) }}</strong> | 
                Total Qty Produksi: <strong class="text-indigo-700">{{ number_format(collect($rows)->sum(fn($r) => (int) ($r['qty_to_run'] ?? 0))) }} Pcs</strong>
            </div>

            <div class="flex items-center gap-2">
                @if(!$isLocked)
                    <button wire:click="addRow" 
                            type="button"
                            class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-black transition cursor-pointer">
                        + Tambah Baris
                    </button>
                    <button wire:click="saveDraft" 
                            type="button"
                            class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-black transition cursor-pointer">
                        Simpan Draft
                    </button>
                    <button wire:click="generateToDailyItemCodes" 
                            type="button"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition shadow-sm cursor-pointer">
                        ⚡ Generate ke Daily Item Codes
                    </button>
                @else
                    <span class="text-xs font-black text-emerald-800 bg-emerald-100 px-3 py-1.5 rounded-xl border border-emerald-200">
                        🔒 Status: Terkunci (Read-Only)
                    </span>
                    <button wire:click="reopenDraft" 
                            type="button"
                            wire:confirm="Buka kunci kembali jadwal ini ke status DRAFT agar dapat diedit?"
                            class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 rounded-xl text-xs font-black transition shadow-xs cursor-pointer">
                        🔓 Buka Kunci (Edit Kembali)
                    </button>
                @endif
            </div>
        </div>

    </div>

    <!-- Modal Search Part No from MasterListItem -->
    @if($showPartSearchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                    <div>
                        <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">
                            Pilih Part No dari Master List Item
                        </h3>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">
                            Data nama part, cavity, cycle time, target/jam, material & SPK akan terisi otomatis.
                        </p>
                    </div>
                    <button wire:click="$set('showPartSearchModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-black">&times;</button>
                </div>

                <!-- Search Input -->
                <div class="p-4 border-b border-slate-100 bg-white">
                    <input type="text" 
                           wire:model.live.debounce.300ms="partSearchQuery"
                           placeholder="Ketik Part No atau Nama Part..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-indigo-500 transition-all"
                           autofocus>
                </div>

                <!-- Results List -->
                <div class="flex-1 overflow-y-auto p-4 divide-y divide-slate-100">
                    @forelse($partSearchResults as $res)
                        <button wire:click="selectPart('{{ $res['item_code'] }}')" 
                                class="w-full text-left py-3 px-3 hover:bg-indigo-50/60 rounded-xl transition flex justify-between items-center group cursor-pointer">
                            <div class="pr-3">
                                <span class="text-xs font-black text-slate-900 group-hover:text-indigo-600 block">
                                    {{ $res['item_code'] }}
                                </span>
                                <span class="text-[11px] text-slate-500 font-semibold block">
                                    {{ $res['item_name'] ?: 'No Description' }}
                                </span>
                            </div>
                            <div class="text-right shrink-0 flex items-center gap-2">
                                <span class="bg-slate-100 group-hover:bg-indigo-100 text-slate-600 group-hover:text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded">
                                    Cav: {{ $res['cavity'] ?: 1 }}
                                </span>
                                <span class="bg-slate-100 group-hover:bg-indigo-100 text-slate-600 group-hover:text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded">
                                    CT: {{ $res['cycle_time'] ?: 0 }}s
                                </span>
                                <span class="text-indigo-600 font-black text-sm">&rarr;</span>
                            </div>
                        </button>
                    @empty
                        <div class="py-12 text-center text-slate-400 text-xs italic">
                            Tidak ditemukan part yang cocok dengan kata kunci "{{ $partSearchQuery }}".
                        </div>
                    @endforelse
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                    <button wire:click="$set('showPartSearchModal', false)" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- Modal Search SPK Master -->
    @if($showSpkSearchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                    <div>
                        <h3 class="text-base font-black text-slate-900 uppercase tracking-tight">
                            Pilih dari SPK Master
                        </h3>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">
                            Cari berdasarkan Nomor SPK atau Part No. Memilih SPK akan otomatis mengisi Part No & data pendukung jika masih kosong.
                        </p>
                    </div>
                    <button wire:click="$set('showSpkSearchModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-black cursor-pointer">&times;</button>
                </div>

                <!-- Search Input -->
                <div class="p-4 border-b border-slate-100 bg-white">
                    <input type="text" 
                           wire:model.live.debounce.300ms="spkSearchQuery"
                           placeholder="Ketik Nomor SPK atau Part No..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-indigo-500 transition-all"
                           autofocus>
                </div>

                <!-- Results Table -->
                <div class="flex-1 overflow-y-auto p-4">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-600 text-[10px] uppercase font-black tracking-wider border-b border-slate-200">
                                <th class="py-2.5 px-3">Nomor SPK</th>
                                <th class="py-2.5 px-3">Part No / Item</th>
                                <th class="py-2.5 px-2 text-right">Plan Qty</th>
                                <th class="py-2.5 px-2 text-right">Sisa Qty</th>
                                <th class="py-2.5 px-2 text-center">Post Date</th>
                                <th class="py-2.5 px-2 text-center">Status</th>
                                <th class="py-2.5 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($spkSearchResults as $spk)
                                <tr class="hover:bg-indigo-50/50 transition-colors">
                                    <td class="py-2.5 px-3 font-black text-indigo-700">
                                        {{ $spk['spk_number'] }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-extrabold text-slate-900 block">{{ $spk['item_code'] }}</span>
                                        <span class="text-[10px] text-slate-400 block truncate max-w-xs">{{ $spk['item_name'] ?: '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-2 text-right font-bold text-slate-600">
                                        {{ number_format($spk['planned_quantity'], 0) }}
                                    </td>
                                    <td class="py-2.5 px-2 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black {{ $spk['remaining_quantity'] > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                            {{ number_format($spk['remaining_quantity'], 0) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-2 text-center text-slate-500 text-[11px]">
                                        {{ $spk['post_date'] }}
                                    </td>
                                    <td class="py-2.5 px-2 text-center">
                                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded {{ ($spk['production_status'] ?? '') === 'C' ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $spk['production_status'] ?: 'OPEN' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button wire:click="selectSpk('{{ $spk['spk_number'] }}', {{ $spk['planned_quantity'] }}, '{{ $spk['item_code'] }}')" 
                                                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[10px] font-extrabold transition shadow-xs cursor-pointer">
                                            Pilih SPK
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 text-xs italic">
                                        Tidak ditemukan SPK yang cocok dengan kata kunci "{{ $spkSearchQuery }}".
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                    <button wire:click="$set('showSpkSearchModal', false)" 
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- Modal Riwayat List Up -->
    @if($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                    <div>
                        <h3 class="text-base font-black text-slate-900 uppercase tracking-tight flex items-center gap-2">
                            <span>📅</span>
                            <span>Riwayat List Up Mesin Harian</span>
                        </h3>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">
                            Pilih tanggal list up untuk melihat atau mengedit jadwal mesin.
                        </p>
                    </div>
                    <button wire:click="$set('showHistoryModal', false)" class="text-slate-400 hover:text-slate-600 text-xl font-black cursor-pointer">&times;</button>
                </div>

                <!-- History Table List -->
                <div class="flex-1 overflow-y-auto p-4">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-600 text-[10px] uppercase font-black tracking-wider border-b border-slate-200">
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                                <th class="py-2.5 px-3 text-center">Jumlah Baris</th>
                                <th class="py-2.5 px-3">Catatan</th>
                                <th class="py-2.5 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($historyListUps as $h)
                                <tr class="hover:bg-indigo-50/50 transition-colors {{ $h['date'] === $selectedDate ? 'bg-indigo-50/70 font-bold' : '' }}">
                                    <td class="py-3 px-3">
                                        <span class="font-black text-slate-900 block text-xs">{{ $h['formatted_date'] }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $h['date'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if($h['status'] === 'GENERATED' || $h['status'] === 'FINAL')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                                <span>🔒</span>
                                                <span>FINAL</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                                <span>📝</span>
                                                <span>DRAFT</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-700 font-extrabold text-[11px] rounded-lg">
                                            {{ $h['items_count'] }} Baris
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 text-[11px] truncate max-w-xs">
                                        {{ $h['notes'] ?: '-' }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <button wire:click="selectHistoryDate('{{ $h['date'] }}')" 
                                                type="button"
                                                class="px-3 py-1.5 {{ $h['date'] === $selectedDate ? 'bg-slate-200 text-slate-600' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} rounded-xl text-xs font-black transition cursor-pointer">
                                            {{ $h['date'] === $selectedDate ? 'Sedang Dibuka' : 'Buka List Up &rarr;' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 text-xs italic">
                                        Belum ada riwayat list up tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                    <button wire:click="$set('showHistoryModal', false)" 
                            type="button"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
