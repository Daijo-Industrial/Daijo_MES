<div class="space-y-6">
    <!-- Action Alerts -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-md text-sm shadow-sm"
            role="alert">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-md text-sm shadow-sm" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- User List Panel -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
            <div
                class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">System Users</h3>
                    <p class="text-xs text-slate-500">Manage status, roles, and profiles of all accounts.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="$set('showDeprecated', false)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ !$showDeprecated ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 transition' }}">
                        Active Users
                    </button>
                    <button wire:click="$set('showDeprecated', true)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $showDeprecated ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 transition' }}">
                        Deprecated ({{ \App\Models\User::onlyTrashed()->count() }})
                    </button>
                    
                    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-user-modal')"
                        class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm hover:bg-slate-800 transition flex items-center gap-1.5 ml-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add User
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search by name, email, username..."
                        class="w-full pl-10 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 transition shadow-sm" />
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                            <th class="px-6 py-4">User Profile</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Branch / Plant</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar based on Initials -->
                                        <div class="shrink-0 h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                                            {{ collect(explode(' ', trim($user->name)))->map(fn($segment) => substr($segment, 0, 1))->take(2)->join('') }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                {{ $user->email }} <span class="mx-1">•</span> <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-600">{{ '@' . $user->username }}</span>
                                            </div>
                                            @if ($user->zone)
                                                <div class="mt-1.5">
                                                    <span class="inline-flex items-center gap-1 text-[10px] bg-sky-50 text-sky-700 px-2 py-0.5 rounded-full font-semibold border border-sky-100">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <td class="px-6 py-4 align-middle">
                                    @if ($user->id === auth()->id())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-violet-50 text-violet-700 border border-violet-100">
                                            {{ $user->role->name }} (You)
                                        </span>
                                    @else
                                        <div class="relative inline-block w-full max-w-[140px]">
                                            <select wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                                class="w-full text-xs bg-white border border-slate-200 rounded-md pl-3 pr-8 py-1.5 appearance-none focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 shadow-sm cursor-pointer transition">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                        {{ $user->role_id === $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div class="relative inline-block w-full max-w-[140px]">
                                        <select wire:change="changeBranch({{ $user->id }}, $event.target.value)"
                                            class="w-full text-xs bg-white border border-slate-200 rounded-md pl-3 pr-8 py-1.5 appearance-none focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 shadow-sm cursor-pointer transition">
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
                                <td class="px-6 py-4 align-middle">
                                    <div class="relative inline-block w-full max-w-[140px]">
                                        <select wire:change="changeDepartment({{ $user->id }}, $event.target.value)"
                                            class="w-full text-xs bg-white border border-slate-200 rounded-md pl-3 pr-8 py-1.5 appearance-none focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 shadow-sm cursor-pointer transition">
                                            <option value="">-- No Dept --</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}"
                                                    {{ $user->department_id === $dept->id ? 'selected' : '' }}>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center align-middle">
                                    @if ($user->id === auth()->id())
                                        <span class="inline-flex items-center justify-center">
                                            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full ring-4 ring-emerald-50"></span>
                                        </span>
                                    @else
                                        <button wire:click="toggleActivation({{ $user->id }})" class="focus:outline-none group">
                                            @if ($user->is_active)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 group-hover:bg-emerald-100 transition shadow-sm">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-600 border border-slate-200 group-hover:bg-slate-100 transition shadow-sm">
                                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                                    Inactive
                                                </span>
                                            @endif
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($showDeprecated)
                                            <button wire:click="restoreUser({{ $user->id }})"
                                                class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 px-3 py-1.5 rounded-lg transition shadow-sm" title="Restore User">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                Restore
                                            </button>
                                        @else
                                            <button wire:click="selectUserForPasswordChange({{ $user->id }})"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 px-2.5 py-1.5 rounded-md transition border border-slate-200 shadow-sm" title="Change Password">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                                PW
                                            </button>
                                            @if ($user->id !== auth()->id())
                                                <button wire:click="deprecateUser({{ $user->id }})"
                                                    onclick="confirm('Are you sure you want to deprecate this user? This will soft-delete their profile.') || event.stopImmediatePropagation()"
                                                    class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1.5 rounded-md transition border border-rose-100 shadow-sm" title="Deprecate User">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-slate-500 font-medium">No users found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-slate-100 bg-white overflow-x-auto">
                {{ $users->links() }}
            </div>
        </div>

    <!-- Create User Modal -->
    <x-modal name="create-user-modal" :show="$errors->hasAny(['username', 'name', 'email', 'password', 'role_id', 'branch_id', 'department_id', 'zone_id'])" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Create Account</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Add a new user credential to the MES platform.</p>
                </div>
                <!-- Close Button -->
                <button x-on:click="$dispatch('close')" type="button" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="createUser" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
                    <input wire:model="username" type="text"
                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm" placeholder="e.g. jdoe" />
                    @error('username')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                    <input wire:model="name" type="text"
                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm" placeholder="John Doe" />
                    @error('name')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                    <input wire:model="email" type="email"
                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm" placeholder="john@example.com" />
                    @error('email')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input wire:model="password" type="password"
                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm" placeholder="••••••••" />
                    @error('password')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role Assignment</label>
                    <div class="relative">
                        <select wire:model="role_id"
                            class="w-full border border-slate-200 rounded-lg pl-3.5 pr-10 py-2.5 text-sm appearance-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm cursor-pointer">
                            <option value="">Select Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Branch / Plant <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
                        <div class="relative">
                            <select wire:model="branch_id"
                                class="w-full border border-slate-200 rounded-lg pl-3.5 pr-10 py-2.5 text-sm appearance-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm cursor-pointer">
                                <option value="">Select Branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('branch_id')
                            <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
                        <div class="relative">
                            <select wire:model="department_id"
                                class="w-full border border-slate-200 rounded-lg pl-3.5 pr-10 py-2.5 text-sm appearance-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm cursor-pointer">
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Zone <span class="text-slate-400 font-normal text-xs">(Optional)</span></label>
                    <div class="relative">
                        <select wire:model="zone_id"
                            class="w-full border border-slate-200 rounded-lg pl-3.5 pr-10 py-2.5 text-sm appearance-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 outline-none transition shadow-sm cursor-pointer">
                            <option value="">Select Zone</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('zone_id')
                        <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')" type="button">
                        Cancel
                    </x-secondary-button>
                    <button type="submit"
                        class="bg-slate-900 hover:bg-slate-950 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Force Change Password Modal -->
    <x-modal name="force-change-password-modal" :show="$errors->has('newPassword')" focusable>
        <form wire:submit="forceChangePassword" class="p-6">
            <h2 class="text-lg font-bold text-slate-800">
                Force Change Password
            </h2>

            <p class="mt-1 text-xs text-slate-500 font-medium">
                Change password for user: <span class="font-bold text-slate-700">{{ $selectedUserName }}</span>
            </p>

            <div class="mt-6">
                <label for="newPassword"
                    class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">New Password</label>

                <x-text-input wire:model="newPassword" id="newPassword" name="newPassword" type="password"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    placeholder="Enter new password" />

                <x-input-error :messages="$errors->get('newPassword')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <button type="submit"
                    class="bg-slate-950 hover:bg-slate-900 text-white font-semibold text-xs py-2 px-4 rounded-lg transition-colors shadow-sm">
                    Save Password
                </button>
            </div>
        </form>
    </x-modal>
</div>
