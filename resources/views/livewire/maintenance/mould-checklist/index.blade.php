<div x-data="{ showFilters: false, showKpi: false }" class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </span>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Mould Maintenance Checklist</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Doc No: <span class="font-semibold text-slate-700">DI-F-P/PD/05/MTM-043</span>, Rev 00
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Filter Toggle Button --}}
            <button type="button" @click="showFilters = !showFilters"
                :class="showFilters ? 'bg-blue-50 text-blue-700 border-blue-200 ring-2 ring-blue-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 text-sm font-semibold rounded-xl border shadow-sm transition-all focus:outline-none">
                <svg class="w-4 h-4" :class="showFilters ? 'text-blue-600' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span x-text="showFilters ? 'Sembunyikan Filter' : 'Filter'"></span>
            </button>

            {{-- KPI Toggle Button --}}
            <button type="button" @click="showKpi = !showKpi"
                :class="showKpi ? 'bg-indigo-50 text-indigo-700 border-indigo-200 ring-2 ring-indigo-400' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 text-sm font-semibold rounded-xl border shadow-sm transition-all focus:outline-none">
                <svg class="w-4 h-4" :class="showKpi ? 'text-indigo-600' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span x-text="showKpi ? 'Sembunyikan KPI' : 'KPI Summary'"></span>
            </button>
            @if (auth()->user()?->hasRole('SUPER-ADMIN'))
                <button wire:click="exportExcel"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </button>
            @endif
            <a href="{{ route('mould-checklist.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Checklist Baru
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Collapsible KPI Section -->
    <div x-show="showKpi" x-cloak x-transition>
        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Check Sheet</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalChecklists) }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-amber-600 uppercase tracking-wider">Menunggu Approval</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($totalSubmitted) }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Approved</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($totalApproved) }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Draft</p>
                <p class="text-2xl font-bold text-slate-700 mt-1">{{ number_format($totalDraft) }}</p>
            </div>
        </div>
    </div>

    <!-- Collapsible Filters Section -->
    <div x-show="showFilters" x-cloak x-transition>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Cari Part / Mould / Teknisi</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Ketik kata kunci pencarian..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="filterDateFrom"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="filterDateTo"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status Dokumen</label>
                <select wire:model.live="filterStatus"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="">Semua Status</option>
                    <option value="DRAFT">DRAFT</option>
                    <option value="SUBMITTED">SUBMITTED (Menunggu Approval)</option>
                    <option value="APPROVED">APPROVED (Selesai)</option>
                    <option value="REJECTED">REJECTED (Ditolak)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Mobile & Tablet Cards View (< xl) - Clean Standalone Cards without Outer Big Card Wrapper --}}
    <div class="block xl:hidden space-y-3.5 sm:space-y-4">
        @forelse ($checklists as $c)
            @php
                $ngCount = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();
            @endphp
            <div 
                x-data="{
                    timer: null,
                    isHeld: false,
                    touchStartX: 0,
                    touchStartY: 0,
                    isScrolling: false,
                    lastTouchTime: 0,
                    triggerMenu() {
                        this.isHeld = true;
                        if (navigator.vibrate) {
                            try { navigator.vibrate(40); } catch (_) {}
                        }
                        $dispatch('open-mould-menu', {
                            id: {{ $c->id }},
                            mould_no: {{ json_encode($c->mould_no) }},
                            part_no: {{ json_encode($c->part_no) }},
                            part_name: {{ json_encode($c->part_name) }},
                            customer: {{ json_encode($c->customer) }},
                            date: {{ json_encode($c->check_date ? $c->check_date->translatedFormat('d M Y') : '-') }},
                            status: {{ json_encode($c->status) }},
                            canEdit: {{ ($c->status === 'DRAFT' || $c->status === 'REJECTED' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN')) ? 'true' : 'false' }},
                            canDelete: {{ ($c->status === 'DRAFT' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN')) ? 'true' : 'false' }},
                            showUrl: {{ json_encode(route('mould-checklist.show', $c->id)) }},
                            editUrl: {{ json_encode(route('mould-checklist.edit', $c->id)) }}
                        });
                    },
                    start(e) {
                        if (e.type === 'mousedown') {
                            if (Date.now() - this.lastTouchTime < 600) return;
                            if (e.button !== 0) return;
                        }
                        if (e.type === 'touchstart') {
                            this.lastTouchTime = Date.now();
                        }
                        this.isHeld = false;
                        this.isScrolling = false;
                        if (e.touches && e.touches[0]) {
                            this.touchStartX = e.touches[0].clientX;
                            this.touchStartY = e.touches[0].clientY;
                        }
                        this.timer = setTimeout(() => {
                            this.triggerMenu();
                        }, 500);
                    },
                    move(e) {
                        if (e.touches && e.touches[0]) {
                            const diffX = Math.abs(e.touches[0].clientX - this.touchStartX);
                            const diffY = Math.abs(e.touches[0].clientY - this.touchStartY);
                            if (diffX > 10 || diffY > 10) {
                                this.isScrolling = true;
                                this.cancel();
                            }
                        }
                    },
                    end(e) {
                        if (e.type === 'mouseup' && Date.now() - this.lastTouchTime < 600) {
                            return;
                        }
                        if (this.timer) {
                            clearTimeout(this.timer);
                            this.timer = null;
                        }
                        if (this.isScrolling) return;
                        if (!this.isHeld) {
                            if (e.ctrlKey || e.metaKey) {
                                window.open({{ json_encode(route('mould-checklist.show', $c->id)) }}, '_blank');
                                return;
                            }
                            Livewire.navigate({{ json_encode(route('mould-checklist.show', $c->id)) }});
                        }
                    },
                    cancel() {
                        if (this.timer) {
                            clearTimeout(this.timer);
                            this.timer = null;
                        }
                    }
                }"
                @mousedown="start($event)"
                @mouseup="end($event)"
                @mouseleave="cancel()"
                @touchstart.passive="start($event)"
                @touchmove.passive="move($event)"
                @touchend="end($event)"
                @touchcancel="cancel()"
                @contextmenu.prevent="triggerMenu()"
                @keydown.enter="Livewire.navigate({{ json_encode(route('mould-checklist.show', $c->id)) }})"
                @keydown.space.prevent="Livewire.navigate({{ json_encode(route('mould-checklist.show', $c->id)) }})"
                role="button"
                tabindex="0"
                class="group select-none touch-manipulation cursor-pointer bg-white rounded-2xl border border-slate-200 hover:border-blue-400 active:scale-[0.99] active:bg-slate-50/50 shadow-xs hover:shadow-md p-4 sm:p-6 transition-all space-y-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                {{-- Row 1: Header Strip - Highlighted Report ID & Date + Status/Result --}}
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 gap-2">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        {{-- Prominent Report ID Badge --}}
                        <span class="inline-flex items-center px-2.5 py-1 bg-slate-900 text-white font-mono font-extrabold text-sm sm:text-base rounded-lg shadow-xs">
                            #{{ $c->id }}
                        </span>

                        {{-- Highlighted Date & Working Hours --}}
                        <div class="inline-flex items-center gap-2 text-sm sm:text-base text-slate-900 flex-wrap">
                            @if ($c->is_ongoing)
                                <span class="font-extrabold">
                                    {{ $c->start_at ? $c->start_at->translatedFormat('d M Y') : ($c->check_date ? $c->check_date->translatedFormat('d M Y') : '-') }}
                                </span>
                                @if ($c->start_at || $c->start_time)
                                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">
                                        ({{ $c->start_at ? $c->start_at->format('H:i') : $c->start_time }})
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 text-xs font-bold bg-amber-100 text-amber-800 rounded-md">
                                    Ongoing
                                </span>
                            @elseif ($c->start_at && $c->end_at)
                                @php
                                    $diffDays = (int) $c->start_at->startOfDay()->diffInDays($c->end_at->startOfDay());
                                @endphp
                                <span class="font-extrabold">
                                    @if ($diffDays > 0)
                                        {{ $c->start_at->translatedFormat('d M Y') }} - {{ $c->end_at->translatedFormat('d M Y') }}
                                    @else
                                        {{ $c->start_at->translatedFormat('d M Y') }}
                                    @endif
                                </span>
                                <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">
                                    ({{ $c->start_at->format('H:i') }} - {{ $c->end_at->format('H:i') }}{{ $diffDays > 0 ? " +{$diffDays} hari" : '' }})
                                </span>
                            @elseif ($c->check_date)
                                <span class="font-extrabold">
                                    {{ $c->check_date->translatedFormat('d M Y') }}
                                </span>
                                @if ($c->start_time && $c->end_time)
                                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">
                                        ({{ $c->start_time }} - {{ $c->end_time }})
                                    </span>
                                @endif
                            @else
                                <span class="font-extrabold">-</span>
                            @endif
                        </div>
                    </div>

                    {{-- Right Status Badges + Chevron Indicator --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if ($ngCount > 0)
                            <span class="px-2.5 py-1 text-xs sm:text-sm font-bold bg-rose-100 text-rose-700 rounded-full">
                                {{ $ngCount }} NG
                            </span>
                        @endif

                        @if ($c->status === 'APPROVED')
                            <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-emerald-100 text-emerald-800">
                                Approved
                            </span>
                        @elseif ($c->status === 'SUBMITTED')
                            <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-amber-100 text-amber-800">
                                Menunggu
                            </span>
                        @elseif ($c->status === 'REJECTED')
                            <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-rose-100 text-rose-800">
                                Reject
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs sm:text-sm font-bold rounded-full bg-slate-100 text-slate-700">
                                Draft
                            </span>
                        @endif

                        <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>

                {{-- Row 2: Mould & Machine Badges --}}
                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                    <span class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 font-black text-base sm:text-lg rounded-xl border border-indigo-200 shadow-2xs">
                        {{ $c->mould_no }}
                    </span>
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs sm:text-sm font-bold rounded-lg">
                        {{ $c->customer }}
                    </span>
                    @if ($c->machine_number)
                        <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs sm:text-sm font-bold rounded-lg">
                            Mesin: {{ $c->machine_number }}
                        </span>
                    @endif
                    @if ($c->model)
                        <span class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs sm:text-sm font-semibold rounded-lg">
                            Model: {{ $c->model }}
                        </span>
                    @endif
                </div>

                {{-- Row 3: Meta info (PIC Teknisi & Creator) --}}
                <div class="flex items-center justify-between text-xs sm:text-sm text-slate-700">
                    <span>Teknisi (PIC): <strong class="text-slate-900 text-sm sm:text-base">{{ $c->technician_name }}</strong></span>
                    @if ($c->issued_name)
                        <span class="text-slate-500 text-xs sm:text-sm">Issued by: <strong>{{ $c->issued_name }}</strong></span>
                    @endif
                </div>
            </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center text-slate-400 shadow-xs">
                    <p class="text-base font-semibold">Belum ada check sheet mould pada filter ini.</p>
                    <p class="text-sm text-slate-500 mt-1">Gunakan tombol "Buat Checklist Baru" di atas untuk memulai check sheet.</p>
                </div>
            @endforelse

            @if ($checklists->hasPages())
                <div class="pt-2">
                    {{ $checklists->links() }}
                </div>
            @endif
        </div>

        {{-- Desktop Table View (>= xl) - Dedicated Card Wrapper (No Horizontal Scroll) --}}
        <div class="hidden xl:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="w-full overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-slate-200 text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-700 text-[11px] sm:text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="w-[5%] px-1.5 py-3 text-center">ID</th>
                        <th class="w-[15%] px-2 py-3 text-left">Tanggal</th>
                        <th class="w-[11%] px-2 py-3 text-left">Mould No</th>
                        <th class="w-[20%] px-2 py-3 text-left">Part No & Name</th>
                        <th class="w-[8%] px-2 py-3 text-center">Customer</th>
                        <th class="w-[6%] px-1.5 py-3 text-center">Mesin</th>
                        <th class="w-[10%] px-2 py-3 text-left">Teknisi</th>
                        <th class="w-[6%] px-1.5 py-3 text-center">Hasil</th>
                        <th class="w-[8%] px-1.5 py-3 text-center">Status</th>
                        <th class="w-[11%] px-1.5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($checklists as $c)
                        @php
                            $ngCount = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-1.5 py-3 text-center">
                                <span class="inline-block px-1.5 py-0.5 bg-slate-900 text-white font-mono font-bold text-xs rounded">
                                    #{{ $c->id }}
                                </span>
                            </td>
                            <td class="px-2 py-3 font-medium text-slate-900">
                                @php
                                    $diffDays = ($c->start_at && $c->end_at) ? (int) $c->start_at->startOfDay()->diffInDays($c->end_at->startOfDay()) : 0;
                                @endphp
                                <span class="text-xs sm:text-sm font-bold whitespace-nowrap block truncate">
                                    @if ($diffDays > 0)
                                        {{ $c->start_at->format('d/m') }} - {{ $c->end_at->format('d/m/Y') }}
                                    @else
                                        {{ $c->start_at ? $c->start_at->format('d/m/Y') : ($c->check_date ? $c->check_date->format('d/m/Y') : '-') }}
                                    @endif
                                </span>
                                @if ($c->is_ongoing)
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded">
                                            Ongoing
                                        </span>
                                        @if ($c->start_at || $c->start_time)
                                            <span class="text-[11px] text-slate-500 font-mono">
                                                {{ $c->start_at ? $c->start_at->format('H:i') : $c->start_time }}
                                            </span>
                                        @endif
                                    </div>
                                @elseif ($c->start_at && $c->end_at)
                                    <div class="flex items-center gap-1 text-[11px] text-slate-500 font-mono whitespace-nowrap mt-0.5">
                                        <span>{{ $c->start_at->format('H:i') }}-{{ $c->end_at->format('H:i') }}</span>
                                        @if ($diffDays > 0)
                                            <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-1 rounded">
                                                +{{ $diffDays }}h
                                            </span>
                                        @endif
                                    </div>
                                @elseif ($c->start_time && $c->end_time)
                                    <span class="block text-[11px] text-slate-500 font-mono whitespace-nowrap mt-0.5">{{ $c->start_time }}-{{ $c->end_time }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-3 font-bold text-indigo-700 text-xs sm:text-sm truncate" title="{{ $c->mould_no }}">{{ $c->mould_no }}</td>
                            <td class="px-2 py-3">
                                <span class="block font-mono text-[11px] text-slate-500 font-bold truncate" title="{{ $c->part_no }}">{{ $c->part_no }}</span>
                                <span class="font-bold text-slate-900 text-xs sm:text-sm truncate block" title="{{ $c->part_name }}">{{ $c->part_name }}</span>
                            </td>
                            <td class="px-2 py-3 text-center">
                                <span class="px-1.5 py-0.5 text-[11px] font-bold bg-slate-100 text-slate-800 rounded truncate block" title="{{ $c->customer }}">
                                    {{ $c->customer }}
                                </span>
                            </td>
                            <td class="px-1.5 py-3 font-mono text-xs font-bold text-slate-800 text-center truncate" title="{{ $c->machine_number ?: '-' }}">{{ $c->machine_number ?: '-' }}</td>
                            <td class="px-2 py-3 text-xs sm:text-sm font-semibold text-slate-900 truncate" title="{{ $c->technician_name }}">{{ $c->technician_name }}</td>
                            <td class="px-1.5 py-3 text-center">
                                @if ($ngCount > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold bg-rose-100 text-rose-700 rounded-full" title="{{ $ngCount }} NG">
                                        {{ $ngCount }} NG
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full">
                                        OK
                                    </span>
                                @endif
                            </td>
                            <td class="px-1.5 py-3 text-center">
                                @if ($c->status === 'APPROVED')
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 whitespace-nowrap">
                                        Approved
                                    </span>
                                @elseif ($c->status === 'SUBMITTED')
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 whitespace-nowrap" title="Menunggu Approval">
                                        Menunggu
                                    </span>
                                @elseif ($c->status === 'REJECTED')
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800 whitespace-nowrap" title="{{ $c->rejection_note }}">
                                        Reject
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-700 whitespace-nowrap">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-1.5 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-1 whitespace-nowrap">
                                    <a href="{{ route('mould-checklist.show', $c->id) }}" wire:navigate
                                        class="px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs rounded-lg transition-colors inline-flex items-center shadow-2xs shrink-0" title="Detail / Print">
                                        <span>Detail</span>
                                    </a>

                                    @if ($c->status === 'DRAFT' || $c->status === 'REJECTED' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))
                                        <a href="{{ route('mould-checklist.edit', $c->id) }}" wire:navigate
                                            class="p-1 text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-lg transition-colors shrink-0" title="Edit Checklist">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif

                                    @if ($c->status === 'DRAFT' || auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))
                                        <button wire:click="deleteChecklist({{ $c->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus check sheet #{{ $c->id }} ini?"
                                            class="p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors shrink-0" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                <p class="text-base font-semibold">Belum ada check sheet mould pada filter ini.</p>
                                <p class="text-sm text-slate-500 mt-1">Gunakan tombol "Buat Checklist Baru" di atas untuk memulai check sheet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($checklists->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $checklists->links() }}
            </div>
        @endif
    </div>

    {{-- Hold / Long-Press Action Sheet Modal --}}
    <div x-data="{
            open: false,
            item: null,
            init() {
                window.addEventListener('open-mould-menu', (e) => {
                    this.item = e.detail;
                    this.open = true;
                });
            },
            close() {
                this.open = false;
                setTimeout(() => {
                    if (!this.open) this.item = null;
                }, 250);
            },
            navigate(url) {
                if (url) {
                    this.open = false;
                    Livewire.navigate(url);
                }
            }
        }"
        x-show="open"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="mould-action-modal-title" role="dialog" aria-modal="true"
    >
        {{-- Backdrop --}}
        <div x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click="close()"
        ></div>

        <div class="fixed inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center p-0 sm:p-4 z-50 pointer-events-none">
            {{-- Panel: Bottom Sheet on Mobile, Centered Modal on Tablet/Desktop --}}
            <div x-show="open"
                x-transition:enter="ease-out duration-250"
                x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
                x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                @click.stop
                class="w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-2xl p-5 sm:p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto pointer-events-auto"
            >
                {{-- Handle bar for mobile bottom sheet --}}
                <div class="w-12 h-1.5 bg-slate-300 rounded-full mx-auto sm:hidden mb-1"></div>

                {{-- Header with Mould & Part Info --}}
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 bg-slate-900 text-white font-mono font-bold text-xs rounded" x-text="'#' + (item ? item.id : '')"></span>
                            <span class="font-black text-base text-indigo-700 font-mono" x-text="item ? item.mould_no : ''"></span>
                            <span class="text-xs px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-semibold" x-text="item ? item.customer : ''"></span>
                        </div>
                        <p id="mould-action-modal-title" class="font-bold text-slate-900 text-sm leading-snug" x-text="item ? item.part_name : ''"></p>
                        <p class="font-mono text-xs text-slate-500 font-bold" x-text="item ? item.part_no : ''"></p>
                    </div>
                    <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Action Menu Options --}}
                <div class="space-y-2 pt-1">
                    {{-- 1. Edit Checklist (If allowed) --}}
                    <template x-if="item && item.canEdit">
                        <button type="button" @click="navigate(item?.editUrl)"
                            class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 active:bg-slate-200 text-slate-800 font-bold text-sm sm:text-base rounded-xl transition-colors border border-slate-200">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit Checklist</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </template>

                    {{-- 2. Hapus Checklist (If allowed) --}}
                    <template x-if="item && item.canDelete">
                        <button type="button"
                            @click="
                                if (confirm('Apakah Anda yakin ingin menghapus check sheet #' + item?.id + '?')) {
                                    const id = item?.id;
                                    close();
                                    $wire.deleteChecklist(id);
                                }
                            "
                            class="w-full flex items-center justify-between px-4 py-3 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 text-rose-700 font-bold text-sm sm:text-base rounded-xl transition-colors border border-rose-200">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus Checklist</span>
                            </div>
                        </button>
                    </template>

                    {{-- Fallback if no action available --}}
                    <template x-if="item && !item.canEdit && !item.canDelete">
                        <div class="p-3 text-center text-xs text-slate-500 bg-slate-50 rounded-xl border border-slate-200">
                            Tidak ada aksi tambahan untuk dokumen berstatus <span class="font-bold uppercase text-slate-700" x-text="item?.status"></span>.
                        </div>
                    </template>
                </div>

                {{-- Close / Batal Button --}}
                <div class="pt-1">
                    <button type="button" @click="close()"
                        class="w-full py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
