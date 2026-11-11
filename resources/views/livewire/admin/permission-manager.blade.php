<div class="space-y-6">
    <!-- Notifications -->
    @if (session()->has('message'))
        <div class="p-3.5 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 rounded-md text-xs font-medium flex items-center justify-between shadow-2xs"
            role="alert">
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3.5 bg-rose-50 border-l-4 border-rose-600 text-rose-900 rounded-md text-xs font-medium flex items-center justify-between shadow-2xs"
            role="alert">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Permissions List Panel -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden flex flex-col justify-between">
        <div class="p-5 border-b border-zinc-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-zinc-900">Permissions</h2>
                    <span class="text-xs bg-zinc-100 text-zinc-700 px-2 py-0.5 rounded font-semibold">{{ $permissions->total() }} total</span>
                </div>
                <p class="text-xs text-zinc-500 mt-0.5">Manage permission labels, module groups, and descriptions.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded border border-zinc-200 font-medium" title="Permission identifiers are defined in application code and cannot be added or deleted">
                    System Identifiers Locked
                </span>
            </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="px-5 py-3 bg-zinc-50 border-b border-zinc-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search permissions by name, label, or description..."
                    class="w-full pl-9 bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 transition shadow-2xs" />
            </div>

            <div class="flex items-center gap-2">
                <select wire:model.live="groupFilter"
                    class="bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs font-semibold text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs">
                    <option value="">All Groups ({{ count($availableGroups) }})</option>
                    @foreach($availableGroups as $grp)
                        <option value="{{ $grp }}">{{ $grp }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Permissions Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-zinc-50 text-zinc-600 font-semibold text-xs border-b border-zinc-200">
                        <th class="px-5 py-3.5">Permission</th>
                        <th class="px-5 py-3.5">Identifier</th>
                        <th class="px-5 py-3.5">Group</th>
                        <th class="px-5 py-3.5">Description</th>
                        <th class="px-5 py-3.5 text-center">Assigned Roles</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($permissions as $permission)
                        <tr class="hover:bg-zinc-50/80 transition-colors">
                            <td class="px-5 py-3.5 align-middle">
                                <span class="font-bold text-zinc-900 text-xs">
                                    {{ $permission->label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 align-middle font-mono">
                                <span class="text-xs text-zinc-600 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                    {{ $permission->name }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 align-middle">
                                <span class="inline-flex items-center text-[11px] font-semibold text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                    {{ $permission->group }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 align-middle text-zinc-500 max-w-sm">
                                <span class="line-clamp-2" title="{{ $permission->description }}">
                                    {{ $permission->description ?: '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center align-middle font-semibold text-xs text-zinc-700">
                                {{ $permission->roles_count }} roles
                            </td>
                            <td class="px-5 py-3.5 text-right align-middle">
                                <button wire:click="startEdit({{ $permission->id }})"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs"
                                    title="Edit Permission">
                                    <svg class="w-3 h-3 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-zinc-400 text-xs">
                                    <svg class="w-8 h-8 mb-2 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>No permissions found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-zinc-200 bg-white overflow-x-auto">
            {{ $permissions->links() }}
        </div>
    </div>

    <!-- Edit Permission Modal -->
    <x-modal name="edit-permission-modal" :show="$errors->hasAny(['editingLabel', 'editingGroup', 'editingDescription'])" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-zinc-200">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Edit Permission</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Update permission display label, module group, and description.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="updatePermission" class="space-y-4">
                <!-- Immutable Name Identifier -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Permission Identifier</label>
                    <input type="text" value="{{ $editingName }}" disabled
                        class="w-full bg-zinc-100 border border-zinc-200 rounded-md px-3 py-2 text-xs font-mono text-zinc-500 cursor-not-allowed select-none" />
                    <p class="text-[11px] text-zinc-400 mt-1">Identifiers are referenced in application policies and cannot be modified.</p>
                </div>

                <!-- Label -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Label</label>
                    <input wire:model="editingLabel" type="text"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs font-medium text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs"
                        placeholder="e.g. Second Process Reports" />
                    @error('editingLabel')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Group -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Module Group</label>
                    <input wire:model="editingGroup" type="text" list="permission-group-list"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs font-medium text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs"
                        placeholder="e.g. Second Process" />
                    <datalist id="permission-group-list">
                        @foreach($availableGroups as $grp)
                            <option value="{{ $grp }}">
                        @endforeach
                    </datalist>
                    @error('editingGroup')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Description <span class="text-zinc-400 font-normal text-xs">(Optional)</span></label>
                    <textarea wire:model="editingDescription" rows="3"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs"
                        placeholder="Explain what access or actions this permission enables..."></textarea>
                    @error('editingDescription')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 pt-3 border-t border-zinc-200 flex justify-end gap-2">
                    <button x-on:click="$dispatch('close')" type="button"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-zinc-900 hover:bg-black text-white font-semibold text-xs py-1.5 px-4 rounded-md transition shadow-2xs border border-zinc-800">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>

