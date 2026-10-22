<div class="space-y-6">
    <!-- Feedback Alerts -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-md text-sm shadow-sm" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <!-- Sub-tab Navigation (Branches vs Departments vs Plant Departments) -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
        <div class="flex space-x-3">
            <button wire:click="$set('activeSubTab', 'branches')"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $activeSubTab === 'branches' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Branches / Plants ({{ $branches->count() }})
            </button>
            <button wire:click="$set('activeSubTab', 'departments')"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $activeSubTab === 'departments' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Departments ({{ $departments->count() }})
            </button>
            <button wire:click="$set('activeSubTab', 'plant-departments')"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $activeSubTab === 'plant-departments' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Plant Departments
            </button>
        </div>

        <div>
            @if ($activeSubTab === 'branches')
                <button wire:click="openBranchModal()"
                    class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm hover:bg-slate-800 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Branch
                </button>
            @elseif ($activeSubTab === 'departments')
                <button wire:click="openDepartmentModal()"
                    class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm hover:bg-slate-800 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Department
                </button>
            @endif
        </div>
    </div>

    <!-- BRANCHES TABLE -->
    @if ($activeSubTab === 'branches')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                            <th class="px-6 py-4">Branch Code</th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Address</th>
                            <th class="px-6 py-4 text-center">Type</th>
                            <th class="px-6 py-4 text-center">Assigned Users</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($branches as $branch)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                    {{ $branch->code }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $branch->name }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $branch->address ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($branch->is_main)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            Main Plant
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium text-slate-600 bg-slate-100">
                                            Secondary Plant
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700">
                                        {{ $branch->users_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="toggleBranchActive({{ $branch->id }})" class="focus:outline-none">
                                        @if ($branch->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 hover:bg-emerald-100 transition">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200 transition">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="openBranchModal({{ $branch->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-md transition border border-slate-200 shadow-sm">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-xs">
                                    No branches configured.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- DEPARTMENTS TABLE -->
    @if ($activeSubTab === 'departments')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                            <th class="px-6 py-4">Code</th>
                            <th class="px-6 py-4">Department Name</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4 text-center">Assigned Users</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($departments as $dept)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                    {{ $dept->code }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $dept->name }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $dept->description ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700">
                                        {{ $dept->users_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="toggleDepartmentActive({{ $dept->id }})" class="focus:outline-none">
                                        @if ($dept->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 hover:bg-emerald-100 transition">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200 transition">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="openDepartmentModal({{ $dept->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-md transition border border-slate-200 shadow-sm">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs">
                                    No departments configured.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- PLANT DEPARTMENTS MATRIX -->
    @if ($activeSubTab === 'plant-departments')
        <div class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 text-blue-800 text-xs p-4 rounded-xl flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <span class="font-bold">Konfigurasi Departemen per Plant:</span>
                    Klik pada badge departemen untuk mengaktifkan atau menonaktifkan operasional departemen tersebut di masing-masing plant.
                    Fitur spesifik plant (seperti Second Process) hanya aktif di plant yang memiliki departemen terkait.
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($branches as $branch)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg {{ $branch->is_main ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700' }} flex items-center justify-center font-bold text-xs">
                                    {{ $branch->code }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-slate-900">{{ $branch->name }}</h3>
                                    <span class="text-[11px] text-slate-500">{{ $branch->address ?? 'No address registered' }}</span>
                                </div>
                            </div>
                            @if ($branch->is_main)
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-200">
                                    Main Plant
                                </span>
                            @endif
                        </div>

                        <div class="p-6 flex-1">
                            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                                Departemen Aktif di Plant Ini ({{ $branch->departments->count() }}/{{ $departments->count() }})
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @php
                                    $activeDeptIds = $branch->departments->pluck('id')->toArray();
                                @endphp
                                @foreach ($departments as $dept)
                                    @php
                                        $isAttached = in_array($dept->id, $activeDeptIds);
                                        $isSp = ($dept->code === 'SP');
                                    @endphp
                                    <button wire:click="toggleDepartmentForBranch({{ $branch->id }}, {{ $dept->id }})"
                                        type="button"
                                        title="Klik untuk mengubah status {{ $dept->name }}"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border {{ $isAttached ? ($isSp ? 'bg-emerald-600 text-white border-emerald-700 shadow-sm' : 'bg-slate-800 text-white border-slate-900 shadow-sm') : 'bg-slate-50 text-slate-400 border-slate-200 hover:border-slate-300 hover:text-slate-600' }}">
                                        @if ($isAttached)
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        @endif
                                        <span>{{ $dept->name }}</span>
                                        <span class="text-[10px] opacity-75 font-mono">({{ $dept->code }})</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Branch Modal -->
    <x-modal name="branch-modal" :show="$errors->hasAny(['branchCode', 'branchName', 'branchAddress', 'branchIsMain'])" focusable>
        <form wire:submit="saveBranch" class="p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-800">
                {{ $isEditingBranch ? 'Edit Branch / Plant' : 'Add New Branch / Plant' }}
            </h3>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Code</label>
                <input wire:model="branchCode" type="text" placeholder="e.g. JKT, KRW"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm uppercase focus:ring-2 focus:ring-slate-900 focus:outline-none" />
                @error('branchCode')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch Name</label>
                <input wire:model="branchName" type="text" placeholder="e.g. Jakarta Plant"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none" />
                @error('branchName')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Address / Location</label>
                <input wire:model="branchAddress" type="text" placeholder="e.g. Kawasan Berikat Nusantara (KBN)"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none" />
                @error('branchAddress')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="branchIsMain" class="rounded border-gray-300 text-slate-900 shadow-sm focus:ring-slate-900" />
                    <span class="ml-2 text-xs font-semibold text-slate-700">Set as Main Plant</span>
                </label>

                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="branchIsActive" class="rounded border-gray-300 text-slate-900 shadow-sm focus:ring-slate-900" />
                    <span class="ml-2 text-xs font-semibold text-slate-700">Active</span>
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-2">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <button type="submit"
                    class="bg-slate-900 hover:bg-slate-950 text-white font-semibold text-xs py-2 px-4 rounded-lg shadow-sm">
                    Save Branch
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Department Modal -->
    <x-modal name="department-modal" :show="$errors->hasAny(['departmentCode', 'departmentName', 'departmentDescription'])" focusable>
        <form wire:submit="saveDepartment" class="p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-800">
                {{ $isEditingDepartment ? 'Edit Department' : 'Add New Department' }}
            </h3>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Department Code</label>
                <input wire:model="departmentCode" type="text" placeholder="e.g. PROD, QC, PPIC"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm uppercase focus:ring-2 focus:ring-slate-900 focus:outline-none" />
                @error('departmentCode')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Department Name</label>
                <input wire:model="departmentName" type="text" placeholder="e.g. Production"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none" />
                @error('departmentName')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                <textarea wire:model="departmentDescription" rows="3" placeholder="Brief description of department function"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                @error('departmentDescription')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="departmentIsActive" class="rounded border-gray-300 text-slate-900 shadow-sm focus:ring-slate-900" />
                    <span class="ml-2 text-xs font-semibold text-slate-700">Active</span>
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-2">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <button type="submit"
                    class="bg-slate-900 hover:bg-slate-950 text-white font-semibold text-xs py-2 px-4 rounded-lg shadow-sm">
                    Save Department
                </button>
            </div>
        </form>
    </x-modal>
</div>
