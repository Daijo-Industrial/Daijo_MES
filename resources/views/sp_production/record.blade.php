<x-operator-layout>
    <x-slot name="bodyClass">h-screen max-h-screen overflow-hidden select-none touch-none</x-slot>
    <x-slot name="headerContainerClass">px-3 py-1.5</x-slot>
    <x-slot name="mainClass">p-0 h-[calc(100vh-42px)] overflow-hidden</x-slot>

    <x-slot name="header">
        <script>
            function headerClock() {
                return {
                    liveTime: '',
                    elapsedTime: '',
                    teamCount: {{ $session->manpowerEntries->count() }},
                    init() {
                        this.update();
                        setInterval(() => this.update(), 1000);
                        window.addEventListener('team-count-changed', (e) => {
                            if (e.detail && typeof e.detail.count === 'number') {
                                this.teamCount = e.detail.count;
                            }
                        });
                    },
                    update() {
                        const now = new Date();
                        this.liveTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                        @if($session->started_at)
                            const started = new Date('{{ $session->started_at->toIso8601String() }}');
                            const diffSec = Math.max(0, Math.floor((now - started) / 1000));
                            const hrs = Math.floor(diffSec / 3600);
                            const mins = Math.floor((diffSec % 3600) / 60);
                            this.elapsedTime = hrs + 'h ' + mins + 'm';
                        @else
                            this.elapsedTime = '0h 0m';
                        @endif
                    }
                }
            }
        </script>
        <div class="flex items-center justify-between gap-4 py-0.5" x-data="headerClock()" x-cloak>
            {{-- Left: Status, Line & Work Order Context --}}
            <div class="flex items-center gap-3 min-w-0">
                @if($session->status === 'running')
                    @if($session->paused_at)
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse flex-shrink-0" title="PAUSED"></span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-green-500 animate-pulse flex-shrink-0" title="RUNNING"></span>
                    @endif
                @else
                    <span class="w-3 h-3 rounded-full bg-blue-500 flex-shrink-0" title="COMPLETED"></span>
                @endif

                <div class="min-w-0 flex flex-col">
                    <div class="flex items-center gap-2 leading-tight">
                        <span class="font-bold text-slate-900 text-sm tracking-tight">
                            {{ $session->unit_line }}
                        </span>
                        <span class="text-slate-300 font-light">•</span>
                        <a href="{{ route('sp-work-orders.show', $session->work_order_id) }}" class="font-semibold text-blue-700 hover:text-blue-900 text-sm">
                            {{ $session->workOrder->wo_number }}
                        </a>
                        @if($session->is_qc_bypassed)
                            <span class="px-1.5 py-0.2 text-[10px] font-semibold rounded-md bg-amber-100 text-amber-800 uppercase" title="QC Bypassed: {{ $session->qc_bypass_reason }}">
                                BYPASSED
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 leading-tight">
                        <span class="truncate max-w-[180px]" title="{{ $session->workOrder->part_number }} - {{ $session->workOrder->part_name }}">
                            {{ $session->workOrder->part_number }}
                        </span>
                        <span>•</span>
                        <span x-text="elapsedTime"></span>
                    </div>
                </div>
            </div>

            {{-- Right: Essential Controls (Team, Gateway, Finish) --}}
            @php
                $lineSlug = array_search($session->unit_line, config('mes.sp_lines', [])) ?: 'line-a';
            @endphp
            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Team Roster Trigger Pill --}}
                <button type="button" onclick="document.getElementById('modalTeamRoster').showModal()"
                        class="px-3.5 py-2 min-h-[40px] bg-purple-50 hover:bg-purple-100 active:bg-purple-200 text-purple-800 font-bold text-xs rounded-xl border border-purple-200 transition cursor-pointer flex items-center gap-1.5">
                    <span>Team</span>
                    <span class="bg-purple-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold" x-text="teamCount">
                        {{ $session->manpowerEntries->count() }}
                    </span>
                </button>

                {{-- Gateway Link --}}
                <a href="{{ route('sp-sessions.line-gateway', ['lineSlug' => $lineSlug]) }}"
                   class="px-3.5 py-2 min-h-[40px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center">
                    Gateway
                </a>

                {{-- Finish Button --}}
                @if($session->status === 'running')
                    <button type="button" onclick="document.getElementById('modalFinish').showModal()"
                            class="px-4 py-2 min-h-[40px] bg-red-600 hover:bg-red-500 active:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition uppercase tracking-wider flex items-center">
                        Finish
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col h-full overflow-hidden bg-gray-100" x-data="productionSession()" x-cloak>
        {{-- Alpine State & Controller Bridge Scripts --}}
        @include('sp_production.partials.scripts')

        {{-- Flash Notification --}}
        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-1.5 text-xs font-bold text-center">
                {{ session('success') }}
            </div>
        @endif

        {{-- Core Metrics Bar (KPI Cards) --}}
        @include('sp_production.partials.metrics_bar')

        {{-- Active Rework Bench Notification Banner (2-Cycle Rework) --}}
        <div x-show="reworkPending > 0" x-cloak class="bg-amber-500 text-white px-4 py-2 flex items-center justify-between text-xs font-bold shadow-md flex-shrink-0">
            <div>
                Active Rework Bench: <strong x-text="formatNum(reworkPending) + ' Pcs'"></strong> undergoing offline repair
            </div>
            <button type="button" onclick="document.getElementById('modalCompleteRework').showModal()"
                    class="px-3 py-1 bg-white text-amber-900 hover:bg-amber-100 active:bg-amber-200 font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer uppercase tracking-wider">
                Complete Rework Outcome
            </button>
        </div>

        {{-- Line Team Warning Banner (0 Operators Assigned) --}}
        <div x-show="manpowerEntries.length === 0" x-cloak class="bg-amber-600 text-white px-4 py-2 flex items-center justify-between text-xs font-bold shadow-md flex-shrink-0">
            <div>
                Line Team Warning: No operators assigned to this running line team yet!
            </div>
            <button type="button" onclick="document.getElementById('modalManpower').showModal()"
                    class="px-3 py-1 bg-white text-amber-950 hover:bg-amber-100 active:bg-amber-200 font-semibold text-xs rounded-xl shadow-xs transition cursor-pointer uppercase tracking-wider">
                + Add Line Team Member
            </button>
        </div>

        {{-- Hero Action Cards Grid & Sub-Hero Strip --}}
        @include('sp_production.partials.action_grid')

        {{-- Event Stream History Drawer --}}
        @include('sp_production.partials.history_drawer')

        {{-- Operational Modals (Production, Defect, Downtime, Rework, Input, Team, Finish, Resume) --}}
        @include('sp_production.partials.modals.index')
    </div> <!-- End of x-data="productionSession()" -->
</x-operator-layout>
