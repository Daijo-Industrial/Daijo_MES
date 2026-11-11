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

    <!-- User List Panel -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden flex flex-col justify-between">
        <div class="p-5 border-b border-zinc-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-zinc-900">Users</h2>
                    <span class="text-xs bg-zinc-100 text-zinc-700 px-2 py-0.5 rounded font-semibold">{{ $users->total() }} total</span>
                </div>
                <p class="text-xs text-zinc-500 mt-0.5">Manage user accounts, roles, and branch assignments.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="inline-flex p-1 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                    <button wire:click="$set('showDeprecated', false)"
                        class="px-3 py-1 rounded-md text-xs font-semibold transition-all {{ !$showDeprecated ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                        Active Users
                    </button>
                    <button wire:click="$set('showDeprecated', true)"
                        class="px-3 py-1 rounded-md text-xs font-semibold transition-all {{ $showDeprecated ? 'bg-zinc-900 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60' }}">
                        Deleted Users ({{ $trashedCount ?? 0 }})
                    </button>
                </div>

                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-user-modal')"
                    class="bg-zinc-900 text-white hover:bg-black px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition flex items-center gap-1.5 border border-zinc-800 ml-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add User</span>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="px-5 py-3 bg-zinc-50 border-b border-zinc-200 flex items-center">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by name, email, or username..."
                    class="w-full pl-9 bg-white border border-zinc-300 rounded-md px-3 py-1.5 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 transition shadow-2xs" />
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-zinc-50 text-zinc-600 font-semibold text-xs border-b border-zinc-200">
                        <th class="px-5 py-3.5">User</th>
                        <th class="px-5 py-3.5">Role</th>
                        <th class="px-5 py-3.5">Branch</th>
                        <th class="px-5 py-3.5">Department</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-50/80 transition-colors">
                            <td class="px-5 py-3.5 align-middle">
                                <div class="flex items-center gap-3">
                                    <!-- Initials Avatar -->
                                    <div class="shrink-0 h-9 w-9 rounded-md bg-zinc-900 text-zinc-100 flex items-center justify-center font-bold text-xs shadow-2xs border border-zinc-800">
                                        {{ collect(explode(' ', trim($user->name)))->map(fn($segment) => substr($segment, 0, 1))->take(2)->join('') }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 text-sm">{{ $user->name }}</div>
                                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-1.5">
                                            <span>{{ $user->email }}</span>
                                            <span class="text-zinc-300">•</span>
                                            <span class="font-mono bg-zinc-100 px-1 py-0.5 rounded text-zinc-700 border border-zinc-200">{{ '@' . $user->username }}</span>
                                        </div>
                                        @if ($user->zone)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">
                                                    <svg class="w-3 h-3 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $user->zone->name }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 align-middle">
                                @if ($user->id === auth()->id())
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-900 bg-zinc-100 px-2.5 py-1 rounded border border-zinc-200">
                                        {{ $user->role->name }}
                                        <span class="text-zinc-500 font-normal">(You)</span>
                                    </span>
                                @else
                                    <div class="relative inline-block w-full max-w-[150px]">
                                        <select wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                            class="w-full text-xs font-semibold text-zinc-800 bg-white border border-zinc-300 rounded-md pl-2.5 pr-7 py-1.5 appearance-none focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 shadow-2xs cursor-pointer transition">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ $user->role_id === $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 align-middle">
                                <div class="relative inline-block w-full max-w-[150px]">
                                    <select wire:change="changeBranch({{ $user->id }}, $event.target.value)"
                                        class="w-full text-xs font-semibold text-zinc-800 bg-white border border-zinc-300 rounded-md pl-2.5 pr-7 py-1.5 appearance-none focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 shadow-2xs cursor-pointer transition">
                                        <option value="">-- No Branch --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ $user->branch_id === $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 align-middle">
                                <div class="relative inline-block w-full max-w-[150px]">
                                    <select wire:change="changeDepartment({{ $user->id }}, $event.target.value)"
                                        class="w-full text-xs font-semibold text-zinc-800 bg-white border border-zinc-300 rounded-md pl-2.5 pr-7 py-1.5 appearance-none focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 shadow-2xs cursor-pointer transition">
                                        <option value="">-- No Department --</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ $user->department_id === $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center align-middle">
                                @if ($user->id === auth()->id())
                                    <span class="inline-block text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">
                                        Active (You)
                                    </span>
                                @else
                                    <button wire:click="toggleActivation({{ $user->id }})" class="focus:outline-none group" title="Toggle active status">
                                        @if ($user->is_active)
                                            <span class="inline-block text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded border border-emerald-300 transition shadow-2xs">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-block text-xs font-semibold text-zinc-600 bg-zinc-100 hover:bg-zinc-200 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs">
                                                Inactive
                                            </span>
                                        @endif
                                    </button>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($showDeprecated)
                                        <button wire:click="restoreUser({{ $user->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold bg-zinc-900 text-white hover:bg-black px-2.5 py-1 rounded border border-zinc-800 transition shadow-2xs" title="Restore User">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            <span>Restore</span>
                                        </button>
                                    @else
                                        <button wire:click="selectUserForPasswordChange({{ $user->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white hover:bg-zinc-100 px-2.5 py-1 rounded border border-zinc-300 transition shadow-2xs" title="Change Password">
                                            <svg class="w-3 h-3 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span>Password</span>
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <button wire:click="deprecateUser({{ $user->id }})"
                                                onclick="confirm('Are you sure you want to delete user \'{{ $user->name }}\'?') || event.stopImmediatePropagation()"
                                                class="inline-flex items-center text-xs font-semibold text-rose-700 hover:text-rose-900 bg-white hover:bg-rose-50 px-2 py-1 rounded border border-rose-300 transition shadow-2xs" title="Delete User">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-zinc-400 text-xs">
                                    <svg class="w-8 h-8 mb-2 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>No users found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-zinc-200 bg-white overflow-x-auto">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Create User Modal -->
    <x-modal name="create-user-modal" :show="$errors->hasAny(['username', 'name', 'email', 'password', 'role_id', 'branch_id', 'department_id', 'zone_id'])" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-zinc-200">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Add User</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Create a new user account.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="createUser" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Username <span class="text-zinc-400 font-normal text-xs">(Optional)</span></label>
                    <input wire:model="username" type="text"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs font-mono" placeholder="e.g. jdoe" />
                    @error('username')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Full Name</label>
                    <input wire:model="name" type="text"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs" placeholder="John Doe" />
                    @error('name')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Email Address</label>
                    <input wire:model="email" type="email"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs" placeholder="john@example.com" />
                    @error('email')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Password</label>
                    <input wire:model="password" type="password"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs" placeholder="••••••••" />
                    @error('password')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Role</label>
                    <div class="relative">
                        <select wire:model="role_id"
                            class="w-full border border-zinc-300 rounded-md pl-3 pr-10 py-2 text-xs font-semibold text-zinc-800 appearance-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs cursor-pointer">
                            <option value="">Select Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('role_id')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Branch <span class="text-zinc-400 font-normal text-xs">(Optional)</span></label>
                        <div class="relative">
                            <select wire:model="branch_id"
                                class="w-full border border-zinc-300 rounded-md pl-3 pr-10 py-2 text-xs font-semibold text-zinc-800 appearance-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs cursor-pointer">
                                <option value="">Select Branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('branch_id')
                            <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Department <span class="text-zinc-400 font-normal text-xs">(Optional)</span></label>
                        <div class="relative">
                            <select wire:model="department_id"
                                class="w-full border border-zinc-300 rounded-md pl-3 pr-10 py-2 text-xs font-semibold text-zinc-800 appearance-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs cursor-pointer">
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('department_id')
                            <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1.5">Zone <span class="text-zinc-400 font-normal text-xs">(Optional)</span></label>
                    <div class="relative">
                        <select wire:model="zone_id"
                            class="w-full border border-zinc-300 rounded-md pl-3 pr-10 py-2 text-xs font-semibold text-zinc-800 appearance-none focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs cursor-pointer">
                            <option value="">Select Zone</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('zone_id')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 pt-3 border-t border-zinc-200 flex justify-end gap-2">
                    <button x-on:click="$dispatch('close')" type="button"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold text-zinc-700 bg-white border border-zinc-300 hover:bg-zinc-100 transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-zinc-900 hover:bg-black text-white font-semibold text-xs py-1.5 px-4 rounded-md transition shadow-2xs flex items-center gap-1.5 border border-zinc-800">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Change Password Modal -->
    <x-modal name="force-change-password-modal" :show="$errors->has('newPassword')" focusable>
        <form wire:submit="forceChangePassword" class="p-6">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-zinc-200">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Change Password</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Set a new password for <span class="font-semibold text-zinc-800">{{ $selectedUserName }}</span>.</p>
                </div>
                <button x-on:click="$dispatch('close')" type="button" class="text-zinc-400 hover:text-zinc-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="newPassword" class="block text-xs font-semibold text-zinc-700 mb-1.5">New Password</label>
                    <input wire:model="newPassword" id="newPassword" name="newPassword" type="password"
                        class="w-full border border-zinc-300 rounded-md px-3 py-2 text-xs text-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition shadow-2xs font-mono"
                        placeholder="Enter at least 8 characters" />
                    @error('newPassword')
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
                        Save Password
                    </button>
                </div>
            </div>
        </form>
    </x-modal>
</div>
