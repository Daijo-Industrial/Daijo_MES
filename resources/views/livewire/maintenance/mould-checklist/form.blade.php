<div x-data="{
    step: @entangle('currentStep').live,
    goTo(target) {
        if (target > this.step) {
            $wire.setStep(target);
        } else {
            this.step = target;
            $wire.set('currentStep', target);
        }
    }
}" class="py-4 px-3 sm:px-6 max-w-7xl mx-auto space-y-4">

    <!-- Top Nav / Back Link & Document Meta -->
    <div class="flex items-center justify-between">
        <a href="{{ route('mould-checklist.index') }}" wire:navigate
            class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">
            ← Kembali ke Daftar Checklist
        </a>
        <span class="text-xs font-mono text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
            Doc: {{ $doc_number }} | Rev: {{ $revision }}
        </span>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('info'))
        <div class="p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-semibold">
            {{ session('info') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Wizard Stepper Navigation (2 Steps) -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Step 1 Tab -->
        <button type="button" @click="goTo(1)"
            class="flex items-center justify-center sm:justify-start gap-2.5 sm:gap-3.5 p-3 rounded-xl border transition-all text-left"
            :class="step === 1 ? 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-500/20' : (step > 1 ? 'bg-emerald-50/50 border-emerald-300' : 'bg-slate-50 border-slate-200 opacity-75')">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-sm shrink-0"
                :class="step === 1 ? 'bg-indigo-600 text-white' : (step > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600')">
                <span x-text="step > 1 ? '✓' : '1'"></span>
            </div>
            <span class="text-sm sm:text-base font-bold truncate"
                :class="step === 1 ? 'text-indigo-950 font-extrabold' : (step > 1 ? 'text-emerald-950' : 'text-slate-700')">
                Mould & PIC
            </span>
        </button>

        <!-- Step 2 Tab -->
        <button type="button" @click="goTo(2)"
            class="flex items-center justify-center sm:justify-start gap-2.5 sm:gap-3.5 p-3 rounded-xl border transition-all text-left"
            :class="step === 2 ? 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-50 border-slate-200 opacity-75'">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-sm shrink-0"
                :class="step === 2 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600'">
                2
            </div>
            <span class="text-sm sm:text-base font-bold truncate"
                :class="step === 2 ? 'text-indigo-950 font-extrabold' : 'text-slate-700'">
                16 Check Points & Submit
            </span>
        </button>
    </div>

    <!-- Main Form -->
    <form wire:submit.prevent="submitAndSign" class="space-y-4">

        <!-- ========================================== -->
        <!-- LANGKAH 1: INFORMASI MOULD & OPERASIONAL   -->
        <!-- ========================================== -->
        <div x-show="step === 1"
             x-data="{ showParams: {{ ($errors->has('check_date') || $errors->has('technician_name') || $errors->has('start_time') || $errors->has('end_time') || empty($technician_name)) ? 'true' : 'false' }} }"
             class="space-y-4">
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 sm:p-6 space-y-5">
                <!-- Header -->
                <div class="border-b border-slate-200 pb-3.5 flex items-center justify-between gap-2">
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 uppercase tracking-tight">
                        Form List Check Sheet Mould Maintenance
                    </h2>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs sm:text-sm font-bold rounded-lg border border-indigo-200 font-mono">
                        {{ $isEdit ? 'EDIT' : 'BARU' }}
                    </span>
                </div>

                <!-- Mould Selection Section -->
                <div>
                    @if ($mould_no && ! $isManualMould)
                        {{-- Selected Mould Card --}}
                        <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                                    <span class="px-3 py-1 bg-indigo-600 text-white text-sm sm:text-base font-extrabold rounded-lg">
                                        {{ $mould_no }}
                                    </span>
                                    <span class="px-2.5 py-1 text-xs sm:text-sm font-bold bg-blue-100 text-blue-900 rounded-lg">
                                        {{ $customer }}
                                    </span>
                                    @if ($model)
                                        <span class="px-2.5 py-1 text-xs sm:text-sm font-semibold bg-white text-slate-700 rounded-lg border border-slate-200">
                                            Model: {{ $model }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm sm:text-base">
                                    <span class="font-mono font-bold text-slate-700">{{ $part_no }}</span>
                                    <span class="text-slate-400 mx-1.5">•</span>
                                    <span class="font-extrabold text-slate-900 text-base sm:text-lg">{{ $part_name }}</span>
                                </div>
                            </div>

                            <button type="button" wire:click="clearSelectedMould"
                                class="self-start sm:self-auto px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold rounded-xl border border-slate-300 shadow-2xs transition-colors">
                                Ganti Mould
                            </button>
                        </div>
                    @elseif ($isManualMould)
                        {{-- Manual Input Mode --}}
                        <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-4 sm:p-5 space-y-3.5">
                            <div class="flex items-center justify-between pb-2 border-b border-amber-200">
                                <h4 class="text-sm sm:text-base font-bold text-amber-900 uppercase tracking-wide">
                                    Mode Input Manual (Mould Belum Terdaftar)
                                </h4>
                                <button type="button" wire:click="disableManualMould"
                                    class="text-sm font-bold text-indigo-700 hover:text-indigo-900 underline">
                                    ← Kembali
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Customer *</label>
                                    <input type="text" wire:model="customer" placeholder="Customer..."
                                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white">
                                    @error('customer') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Mould Number *</label>
                                    <input type="text" wire:model="mould_no" placeholder="Contoh: IK-MM-005-010"
                                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white font-bold text-indigo-800">
                                    @error('mould_no') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Part Number *</label>
                                    <input type="text" wire:model="part_no" placeholder="Contoh: 401-76934B001P"
                                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white font-mono">
                                    @error('part_no') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Model</label>
                                    <input type="text" wire:model="model" placeholder="Model / Proyek..."
                                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white">
                                </div>
                                <div class="sm:col-span-2 lg:col-span-4">
                                    <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Part Name *</label>
                                    <input type="text" wire:model="part_name" placeholder="Nama part lengkap..."
                                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white font-semibold">
                                    @error('part_name') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Search Mould Bar --}}
                        <div class="relative bg-slate-50 border border-slate-200 rounded-xl p-4 sm:p-5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wide">
                                    Pilih Mould dari Master Mould *
                                </label>
                                <button type="button" wire:click="enableManualMould"
                                    class="text-sm font-bold text-indigo-600 hover:text-indigo-800 underline">
                                    Mould belum terdaftar? Input Manual
                                </button>
                            </div>

                            <input type="text" wire:model.live.debounce.250ms="mould_search"
                                wire:focus="$set('showMouldDropdown', true)"
                                placeholder="Ketik Mould No atau Part No..."
                                class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-base font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                            <!-- Dropdown Results -->
                            @if ($showMouldDropdown && $mouldSearchResults->count() > 0)
                                <div class="absolute left-4 right-4 z-30 mt-1 bg-white rounded-xl shadow-xl border border-slate-200 max-h-64 overflow-y-auto divide-y divide-slate-100">
                                    @foreach ($mouldSearchResults as $result)
                                        <button type="button" wire:click="selectMould({{ $result->id }})"
                                            class="w-full p-3 text-left hover:bg-indigo-50 flex items-center justify-between transition-colors">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-extrabold text-base text-indigo-700">{{ $result->mould_no }}</span>
                                                    <span class="font-mono text-sm text-slate-500">({{ $result->part_no }})</span>
                                                    @if ($result->machine_number)
                                                        <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded">{{ $result->machine_number }}</span>
                                                    @endif
                                                </div>
                                                <p class="text-sm font-bold text-slate-900 line-clamp-1">{{ $result->part_name }}</p>
                                            </div>
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs sm:text-sm font-bold rounded-lg">
                                                {{ $result->customer }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif ($showMouldDropdown && strlen($mould_search) >= 2)
                                <div class="absolute left-4 right-4 z-30 mt-1 bg-white rounded-xl shadow-lg border border-slate-200 p-3 text-center text-sm text-slate-500">
                                    Tidak ada data untuk "{{ $mould_search }}".
                                    <button type="button" wire:click="enableManualMould" class="ml-1 text-indigo-600 font-bold underline">
                                        Input Manual
                                    </button>
                                </div>
                            @endif

                            @error('mould_no')
                                <p class="text-xs sm:text-sm text-rose-500 font-medium">⚠️ Silakan pilih mould atau gunakan input manual.</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <!-- Operational Parameters (Minimized by default when values are inserted) -->
                <div>
                    <!-- Minimized Bar View -->
                    <div x-show="!showParams" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 text-sm sm:text-base">
                        <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1 text-slate-700">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-slate-500 font-medium">Tanggal:</span>
                                <strong class="text-slate-900 font-bold">{{ rescue(fn() => \Carbon\Carbon::parse($check_date)->format('d/m/Y'), $check_date ?: '-') }}</strong>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-slate-500 font-medium">PIC:</span>
                                <strong class="text-slate-900 font-bold">{{ $technician_name ?: '-' }}</strong>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-slate-500 font-medium">Jam:</span>
                                <strong class="font-mono text-slate-900 font-bold">{{ $start_time ?: '-' }} - {{ $end_time ?: '-' }}</strong>
                            </span>
                            @if ($machine_number)
                                <span class="text-slate-300">|</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="text-slate-500 font-medium">Mesin:</span>
                                    <strong class="text-slate-900 font-bold">{{ $machine_number }}</strong>
                                </span>
                            @endif
                        </div>
                        <button type="button" @click="showParams = true"
                            class="text-sm font-bold text-indigo-700 hover:text-indigo-900 underline self-start sm:self-auto shrink-0">
                            Ubah
                        </button>
                    </div>

                    <!-- Expanded Inputs Grid View -->
                    <div x-show="showParams" class="space-y-3.5 bg-slate-50/70 border border-slate-200 rounded-xl p-4 sm:p-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wide"></span>
                            <button type="button" @click="showParams = false"
                                class="text-sm font-bold text-slate-600 hover:text-slate-900 underline">
                                Selesai / Sembunyikan
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Tanggal *</label>
                                <input type="date" wire:model="check_date"
                                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white">
                                @error('check_date') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Mesin Injection</label>
                                <input type="text" list="machineList" wire:model="machine_number" placeholder="Contoh: 1300T..."
                                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white">
                                <datalist id="machineList">
                                    @foreach ($machines as $mName)
                                        <option value="{{ $mName }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Teknisi (PIC) *</label>
                                <input type="text" wire:model="technician_name" placeholder="Nama teknisi..."
                                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-base bg-white">
                                @error('technician_name') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-800 uppercase mb-1.5">Jam Kerja</label>
                                <div class="flex items-center gap-2">
                                    <input type="time" wire:model="start_time"
                                        class="w-1/2 px-2.5 py-2.5 border border-slate-300 rounded-xl text-base font-mono text-center bg-white">
                                    <span class="text-sm text-slate-400 font-bold">-</span>
                                    <input type="time" wire:model="end_time"
                                        class="w-1/2 px-2.5 py-2.5 border border-slate-300 rounded-xl text-base font-mono text-center bg-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1 Bottom Bar -->
            <div class="bg-white rounded-xl p-3.5 border border-slate-200 flex items-center justify-between gap-3 shadow-xs">
                <a href="{{ route('mould-checklist.index') }}" wire:navigate
                    class="px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <div class="flex items-center gap-2.5">
                    <button type="button" wire:click="nextStep"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm sm:text-base font-bold shadow-xs transition-colors">
                        Lanjut ke Step 2 →
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- LANGKAH 2: 16 CHECK POINTS PEMERIKSAAN     -->
        <!-- ========================================== -->
        <div x-show="step === 2" class="space-y-4">
            <!-- Context Bar -->
            <div class="bg-slate-900 text-white rounded-xl px-4 py-2.5 flex items-center justify-between text-xs">
                <div class="flex flex-wrap items-center gap-2 font-mono">
                    <span class="font-bold text-indigo-300">{{ $mould_no ?: '-' }}</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-white font-sans font-bold">{{ $part_name ?: '-' }}</span>
                    <span class="text-slate-400 font-sans">({{ $customer ?: '-' }})</span>
                    @if ($machine_number)
                        <span class="text-slate-400">|</span>
                        <span class="text-slate-300 font-sans">MC: {{ $machine_number }}</span>
                    @endif
                </div>
                <button type="button" @click="goTo(1)" class="text-xs text-indigo-300 hover:text-white underline shrink-0 ml-2">
                    Ubah
                </button>
            </div>

            <!-- Table Box -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="p-3 sm:p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 uppercase">
                        16 Check Points Pemeriksaan
                    </h3>

                    <div class="flex items-center gap-2">
                        <div class="text-xs font-semibold bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                            OK: <strong class="text-emerald-700">{{ $this->okCount }}</strong>
                            <span class="text-slate-300 mx-1">|</span>
                            NG: <strong class="{{ $this->ngCount > 0 ? 'text-rose-600' : 'text-slate-700' }}">{{ $this->ngCount }}</strong>
                        </div>
                        <button type="button" wire:click="setAllApplicableToOk"
                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">
                            Set ulang
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                        <thead class="bg-slate-100 text-slate-700 font-bold">
                            <tr>
                                <th class="w-10 px-2.5 py-2.5 text-center">NO</th>
                                <th class="px-3 py-2.5 text-left">POINT CHECK</th>
                                <th class="w-44 sm:w-48 px-2 py-2.5 text-center">CLEANING</th>
                                <th class="w-44 sm:w-48 px-2 py-2.5 text-center">LUBRICATE</th>
                                <th class="px-3 py-2.5 text-left">REMARK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-800">
                            @foreach ($items as $no => $item)
                                <tr class="{{ $item['cleaning_status'] === 'NG' || $item['lubricate_status'] === 'NG' ? 'bg-rose-50/60' : 'hover:bg-slate-50/60' }}">
                                    <td class="px-2.5 py-2 text-center font-mono font-bold text-slate-500">{{ $no }}</td>
                                    <td class="px-3 py-2 font-semibold text-slate-900">
                                        {{ $item['point_check'] }}
                                    </td>

                                    <!-- Cleaning Status -->
                                    <td class="px-2 py-2 text-center">
                                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 gap-0.5">
                                            <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', 'OK')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['cleaning_status'] === 'OK' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                OK
                                            </button>
                                            <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', 'NG')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['cleaning_status'] === 'NG' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                NG
                                            </button>
                                            <button type="button" wire:click="$set('items.{{ $no }}.cleaning_status', '-')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['cleaning_status'] === '-' ? 'bg-slate-500 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                -
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Lubricate Status -->
                                    <td class="px-2 py-2 text-center">
                                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 gap-0.5">
                                            <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', 'OK')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['lubricate_status'] === 'OK' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                OK
                                            </button>
                                            <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', 'NG')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['lubricate_status'] === 'NG' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                NG
                                            </button>
                                            <button type="button" wire:click="$set('items.{{ $no }}.lubricate_status', '-')"
                                                class="px-2.5 py-1 text-xs font-bold rounded {{ $item['lubricate_status'] === '-' ? 'bg-slate-500 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                                -
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Remark -->
                                    <td class="px-3 py-2">
                                        <input type="text" wire:model="items.{{ $no }}.remark"
                                            placeholder="{{ $item['cleaning_status'] === 'NG' || $item['lubricate_status'] === 'NG' ? 'Catatan tindakan NG...' : 'Keterangan...' }}"
                                            class="w-full px-2.5 py-1 border {{ $item['cleaning_status'] === 'NG' || $item['lubricate_status'] === 'NG' ? 'border-rose-400 bg-rose-50/40 font-semibold' : 'border-slate-200' }} rounded text-xs bg-white">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tanda Tangan & Persetujuan -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-slate-200 shadow-xs space-y-3">
                <h4 class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wide">
                    Tanda Tangan & Persetujuan
                </h4>

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-sm sm:text-base">
                        <span class="text-slate-500 font-medium">Issued By (Teknisi):</span>
                        <strong class="text-slate-900 ml-1 font-bold">{{ auth()->user()?->name }}</strong>
                    </div>

                    @if (auth()->user()?->hasRole('MOULD-MAINTENANCE-LEADER'))
                        <label class="inline-flex items-center gap-2.5 font-bold text-sm text-indigo-950 bg-indigo-50 border border-indigo-200 px-3.5 py-2 rounded-xl cursor-pointer hover:bg-indigo-100/70 transition-colors">
                            <input type="checkbox" wire:model.live="auto_sign_check_by" class="rounded border-indigo-300 text-indigo-600 w-5 h-5">
                            <span>Otomatis tandatangani <strong>Check By</strong></span>
                        </label>
                    @endif
                </div>
            </div>

            <!-- Step 2 Bottom Bar -->
            <div class="bg-white rounded-xl p-3.5 border border-slate-200 flex items-center justify-between gap-3 shadow-xs">
                <button type="button" @click="goTo(1)"
                    class="px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                    ← Kembali ke Step 1
                </button>
                <div class="flex items-center gap-2.5">
                    <button type="button" wire:click="saveDraft"
                        class="px-4 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                        Simpan Draft
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm sm:text-base font-bold shadow-xs hover:shadow transition-all">
                        Submit
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
