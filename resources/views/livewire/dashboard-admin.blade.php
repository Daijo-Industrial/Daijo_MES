<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Moulding BOM Progress Dashboard
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Real-time production bill-of-materials execution and stage completion tracking.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                Department: Moulding / Production
            </span>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Child Status Filter -->
            <div>
                <label for="childStatusFilter" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                    Filter by Item Status
                </label>
                <select id="childStatusFilter" wire:model.live="childStatus"
                    class="w-full text-sm rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 bg-white text-slate-800">
                    <option value="">All Item Statuses</option>
                    <option value="Not Started">Not Started</option>
                    <option value="Finished">Finished</option>
                </select>
            </div>

            <!-- Parent Process Filter -->
            <div>
                <label for="selectedProcessFilter" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                    Filter by Process Stage
                </label>
                <select id="selectedProcessFilter" wire:model.live="selectedProcess"
                    class="w-full text-sm rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 bg-white text-slate-800">
                    <option value="">All Process Stages</option>
                    @foreach ($availableProcesses as $process)
                        <option value="{{ $process }}">{{ $process }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total BOM Parents</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($parents->total()) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Tracked Assemblies</p>
        </div>
        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Overall Completion</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ round($overallCompletionPercentage, 1) }}%</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Average Execution Rate</p>
        </div>
        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Items</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($totalPendingChildren) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">In Progress or Waiting</p>
        </div>
        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Completed Items</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($completedItems) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Finished Stages</p>
        </div>
    </div>

    {{-- BOM Parents List --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                BOM Assemblies
            </h2>
            <div class="text-xs text-slate-500">
                Showing {{ $parents->firstItem() ?? 0 }}-{{ $parents->lastItem() ?? 0 }} of {{ $parents->total() }}
            </div>
        </div>

        @if ($parents->hasPages())
            <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
                {{ $parents->links('vendor.livewire.custom-pagination', ['parentCodes' => $parentCodes]) }}
            </div>
        @endif

        @forelse ($parents as $parent)
            @php
                $parentCompletionPercentage = $this->calculateParentCompletion($parent);
            @endphp
            <div wire:key="parent-{{ $parent->id }}" class="bg-white rounded-lg border border-slate-200 shadow-sm p-5 space-y-4">
                {{-- Parent Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-lg font-bold text-slate-900 tracking-tight">{{ $parent->code }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $parent->type ?? 'Standard' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 font-medium">{{ $parent->description }}</p>
                        @if ($parent->customer)
                            <p class="text-xs text-slate-400 mt-0.5">Customer: <span class="text-slate-600">{{ $parent->customer }}</span></p>
                        @endif
                    </div>
                    <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between gap-2">
                        <span class="text-sm font-bold text-slate-800">
                            {{ round($parentCompletionPercentage, 1) }}% Completed
                        </span>
                        <div class="w-48 bg-slate-100 h-2 rounded-full overflow-hidden border border-slate-200">
                            <div class="bg-emerald-600 h-2 transition-all duration-300" style="width: {{ $parentCompletionPercentage }}%;"></div>
                        </div>
                    </div>
                </div>

                {{-- Children Items --}}
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Item Codes & Process Breakdown ({{ $parent->children->count() }})
                    </h3>

                    <div class="grid grid-cols-1 gap-3">
                        @forelse ($parent->children as $child)
                            @php
                                $childProgress = 0;
                                if ($child->action_type === 'buyfinish') {
                                    $childProgress = $child->status === 'Finished' ? 100 : 0;
                                } elseif ($child->action_type === 'stockfinish') {
                                    $childProgress = 100;
                                } elseif (in_array($child->action_type, ['stockprocess', 'buyprocess'])) {
                                    $processCount = $materialLogs->where('child_id', $child->id)->count();
                                    $finishedCount = $materialLogs
                                        ->where('child_id', $child->id)
                                        ->filter(fn($log) => $log->status == 2)
                                        ->count();
                                    $childProgress = $processCount > 0 ? ($finishedCount / $processCount) * 100 : 0;
                                }
                                $isCompleted = $childProgress >= 100;
                            @endphp

                            <div class="p-3.5 rounded-lg border {{ $isCompleted ? 'bg-slate-50/70 border-slate-200' : 'bg-white border-slate-200' }} shadow-none">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-xs sm:text-sm text-slate-800">{{ $child->item_code }}</span>
                                        <span class="text-slate-400">•</span>
                                        <span class="text-xs text-slate-600 truncate max-w-xs sm:max-w-md">{{ $child->item_description }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-slate-500 font-medium">Qty: {{ $child->quantity }} {{ $child->measure }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $isCompleted ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                            {{ $isCompleted ? 'Finished' : ($child->status ?? 'Not Started') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Action Type & Progress Indicator --}}
                                <div class="mt-2.5 flex items-center justify-between text-xs text-slate-500">
                                    <span>Action: <span class="font-semibold text-slate-700">{{ $child->action_type }}</span></span>
                                    <span>Progress: <span class="font-semibold text-slate-800">{{ round($childProgress, 1) }}%</span></span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-1 border border-slate-200">
                                    <div class="h-1.5 {{ $isCompleted ? 'bg-emerald-600' : 'bg-blue-600' }}" style="width: {{ $childProgress }}%;"></div>
                                </div>

                                {{-- Processes Pills --}}
                                @if (!in_array($child->action_type, ['buyfinish', 'stockfinish']))
                                    @php
                                        $childProcesses = $materialLogs->where('child_id', $child->id);
                                    @endphp
                                    @if ($childProcesses->isNotEmpty())
                                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                                Process Stations
                                            </p>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                                                @foreach ($childProcesses as $log)
                                                    @php
                                                        $badgeBg = 'bg-slate-50 text-slate-600 border-slate-200';
                                                        $badgeText = 'Not Started';
                                                        if ($log->status == 1) {
                                                            $badgeBg = 'bg-amber-50 text-amber-800 border-amber-200';
                                                            $badgeText = 'Started';
                                                        } elseif ($log->status == 2) {
                                                            $badgeBg = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                                            $badgeText = 'Finish';
                                                        }
                                                    @endphp
                                                    <div class="p-2 rounded border {{ $badgeBg }}">
                                                        <p class="text-xs font-semibold truncate">{{ $log->process_name }}</p>
                                                        <p class="text-[10px] mt-0.5 opacity-90 font-medium">{{ $badgeText }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <div class="p-3 text-xs text-slate-500 bg-slate-50 rounded border border-slate-200">
                                No child components found for this BOM.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white p-8 rounded-lg border border-slate-200 shadow-sm text-center">
                <p class="text-sm font-semibold text-slate-700">No BOM assemblies found</p>
                <p class="text-xs text-slate-500 mt-1">Try adjusting your filters above.</p>
            </div>
        @endforelse
    </div>
</div>

@script
    <script type="module">
        if (typeof Echo !== 'undefined') {
            Echo.channel('dashboard-data')
                .listen('ParentDataUpdated', (event) => {
                    @this.call('updateDashboard', event.parents, event.childs, event.materialLogs, event.mouldingUserLogs);
                });
        }
    </script>
@endscript
