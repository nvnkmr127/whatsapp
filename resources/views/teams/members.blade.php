<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Team <span
                        class="text-wa-teal">Members</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage your team members, roles, and invitations.</p>
        </div>
        
        @if (Gate::check('addTeamMember', $team))
            <button wire:click="openAddMemberModal"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Add Member
            </button>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="animate-in slide-in-from-top-4 duration-500 p-4 bg-wa-teal/10 border border-wa-teal/20 text-wa-teal rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-bold text-sm">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Members List Card -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Active Members</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">User Identity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Role</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Joined</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @foreach ($team->users->sortBy('name') as $user)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <img class="w-10 h-10 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-sm" 
                                         src="{{ $user->profile_photo_url }}" 
                                         alt="{{ $user->name }}">
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="ml-2 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-wa-teal dark:text-indigo-400 text-[10px] rounded-full uppercase tracking-wider">You</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                @php
                                    $role = Laravel\Jetstream\Jetstream::findRole($user->membership->role);
                                @endphp
                                <span class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black uppercase tracking-wider rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $role ? $role->name : 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-500">
                                    {{ $user->membership->created_at ? $user->membership->created_at->diffForHumans() : '-' }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <!-- Manage Role -->
                                    @if (Gate::check('updateTeamMember', $team) && Laravel\Jetstream\Jetstream::hasRoles())
                                        <button wire:click="manageRole('{{ $user->id }}')" 
                                            class="p-2 text-slate-400 hover:text-wa-teal transition-colors" title="Edit Role">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    @endif

                                    <!-- Leave / Remove -->
                                    @if ($this->user->id === $user->id)
                                        <button wire:click="$toggle('confirmingLeavingTeam')" 
                                            class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Leave Team">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                        </button>
                                    @elseif (Gate::check('removeTeamMember', $team))
                                        <button wire:click="confirmTeamMemberRemoval('{{ $user->id }}')" 
                                            class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Remove Member">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chat Rules Header -->
    <div class="mt-12 mb-6">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Chat <span class="text-wa-teal">Rules</span></h2>
        <p class="text-slate-500 font-medium mt-1">Here you can set the chat rules for the upcoming chats to the team members. <a href="#" class="text-wa-teal hover:underline">Learn more</a></p>
    </div>

    <!-- Chat Assignment Rules -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden mb-8">
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Chat Assignment Rules</h3>
        </div>

        <div class="p-8">
            <!-- Search -->
            <div class="relative mb-6">
                <input type="text" wire:model.live="memberSearch" 
                    placeholder="Search by name..."
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-0 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Team Member</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Assigned When</th>
                            <th class="px-0 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Ticket Assigned To</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @foreach ($team->users->filter(fn($u) => empty($memberSearch) || str_contains(strtolower($u->name), strtolower($memberSearch)) || str_contains(strtolower($u->email), strtolower($memberSearch))) as $user)
                            <tr>
                                <td class="px-0 py-6">
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $user->email }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-medium text-slate-500">Round Robin</span>
                                </td>
                                <td class="px-0 py-6 text-right">
                                    <button wire:click="toggleTicketAssignment({{ $user->id }})" 
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $user->membership->receives_tickets ? 'bg-wa-teal' : 'bg-slate-200 dark:bg-slate-700' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $user->membership->receives_tickets ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer for Assignment Rules -->
            <div class="mt-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button class="relative inline-flex h-5 w-9 items-center rounded-full bg-slate-200 dark:bg-slate-700 transition-colors">
                         <span class="inline-block h-3 w-3 transform rounded-full bg-white translate-x-1"></span>
                    </button>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">Dense</span>
                </div>
                <div class="flex items-center gap-4 text-slate-400 text-xs font-bold">
                    <div class="flex items-center gap-2">
                        Rows per page: 
                        <select class="bg-transparent border-none p-0 text-xs font-bold focus:ring-0">
                            <option>5</option>
                        </select>
                    </div>
                    <span>1-1 of 1</span>
                    <div class="flex gap-2">
                        <button class="p-1 hover:text-wa-teal"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></button>
                        <button class="p-1 hover:text-wa-teal"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Status Rules -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 mb-8">
        <div class="mb-8">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Chat Status Rules</h3>
            <p class="text-sm text-slate-500 font-medium mt-1">Configure chat status rules</p>
        </div>

        <div class="space-y-12">
            @foreach($statusRules as $index => $rule)
                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-2 items-end animate-in fade-in slide-in-from-left-4 duration-300">
                    <!-- Status In -->
                    <div class="md:col-span-4 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status In</label>
                        <select wire:model="statusRules.{{ $index }}.status_in"
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                            @foreach($availableStatuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Select the current status the chat must be in before the rule is applied.</p>
                    </div>

                    <!-- After Days -->
                    <div class="md:col-span-3 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">After Days</label>
                        <input type="number" wire:model="statusRules.{{ $index }}.after_days"
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="Days">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Enter the number of days (max 7) after which the status should change.</p>
                    </div>

                    <!-- Status To -->
                    <div class="md:col-span-4 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status To</label>
                        <select wire:model="statusRules.{{ $index }}.status_to"
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                            @foreach($availableStatuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                         <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Select the status the chat should change to after the specified days.</p>
                    </div>

                    <!-- Actions -->
                    <div class="md:col-span-1 pb-10 text-right">
                        <button wire:click="removeStatusRule({{ $index }})" class="p-2 text-slate-300 hover:text-rose-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Add More -->
            <button wire:click="addStatusRule" class="mt-4 flex items-center gap-2 text-wa-teal hover:text-wa-teal transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-xs font-black uppercase tracking-widest">Add More Rules</span>
            </button>
        </div>

        <div class="mt-12 flex items-center gap-4">
            <button wire:click="saveStatusRules"
                class="px-10 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                Save
            </button>
        </div>
    </div>

    <!-- Pending Invitations -->
    @if ($team->teamInvitations->isNotEmpty() && Gate::check('addTeamMember', $team))
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden mt-8">
            <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Pending Invitations</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                     <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Email</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @foreach ($team->teamInvitations as $invitation)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $invitation->email }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Pending
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    @if (Gate::check('removeTeamMember', $team))
                                        <button wire:click="cancelTeamInvitation({{ $invitation->id }})" 
                                            class="text-xs font-bold text-rose-500 hover:text-rose-600 uppercase tracking-wider">
                                            Cancel
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Add Member Modal -->
    @if($isAddMemberModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeAddMemberModal"></div>
            <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
                <div class="p-8 pb-0 shrink-0">
                     <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Add Team <span class="text-wa-teal">Member</span>
                    </h2>
                </div>

                <div x-data="{ mode: 'invite' }" class="p-8 overflow-y-auto">
                    <!-- Tabs -->
                    <div class="flex p-1 space-x-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-6">
                        <button @click="mode = 'invite'" 
                            :class="mode === 'invite' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="w-full py-2.5 text-xs font-black uppercase tracking-wider rounded-lg transition-all">
                            Invite via Email
                        </button>
                        <button @click="mode = 'create'" 
                            :class="mode === 'create' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="w-full py-2.5 text-xs font-black uppercase tracking-wider rounded-lg transition-all">
                            Create New User
                        </button>
                    </div>

                    <!-- INVITE FORM -->
                    <div x-show="mode === 'invite'">
                        <form wire:submit="addTeamMember">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Email Address</label>
                                    <input type="email" wire:model="addTeamMemberForm.email" 
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="colleague@example.com">
                                    <x-input-error for="email" class="mt-1" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Role</label>
                                    <div class="grid grid-cols-1 gap-3">
                                        @foreach ($this->roles as $index => $role)
                                            <button type="button" 
                                                wire:click="$set('addTeamMemberForm.role', '{{ $role->key }}')"
                                                class="relative flex items-center justify-between px-4 py-3 border-2 rounded-xl transition-all {{ isset($addTeamMemberForm['role']) && $addTeamMemberForm['role'] == $role->key ? 'border-wa-teal bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600' }}">
                                                <div class="text-left">
                                                    <div class="text-sm font-bold {{ isset($addTeamMemberForm['role']) && $addTeamMemberForm['role'] == $role->key ? 'text-wa-teal dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300' }}">
                                                        {{ $role->name }}
                                                    </div>
                                                    <div class="text-xs text-slate-500 font-medium mt-0.5">{{ $role->description }}</div>
                                                </div>
                                                @if (isset($addTeamMemberForm['role']) && $addTeamMemberForm['role'] == $role->key)
                                                    <div class="text-wa-teal">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                    <x-input-error for="role" class="mt-1" />
                                </div>
                            </div>

                            <div class="mt-8 flex gap-3">
                                <button type="button" wire:click="closeAddMemberModal"
                                    class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-xl hover:bg-slate-200 transition-all">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                    Send Invitation
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- CREATE FORM -->
                    <div x-show="mode === 'create'" style="display: none;">
                        <form wire:submit="createUser">
                             <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Full Name</label>
                                    <input type="text" wire:model="createUserForm.name" 
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="John Doe">
                                    <x-input-error for="createUserForm.name" class="mt-1" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Email Address</label>
                                    <input type="email" wire:model="createUserForm.email" 
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="john@example.com">
                                    <x-input-error for="createUserForm.email" class="mt-1" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Password</label>
                                    <input type="password" wire:model="createUserForm.password" 
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="••••••••">
                                    <x-input-error for="createUserForm.password" class="mt-1" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Role</label>
                                   <div class="grid grid-cols-1 gap-3">
                                        @foreach ($this->roles as $index => $role)
                                            <button type="button" 
                                                wire:click="$set('createUserForm.role', '{{ $role->key }}')"
                                                class="relative flex items-center justify-between px-4 py-3 border-2 rounded-xl transition-all {{ isset($createUserForm['role']) && $createUserForm['role'] == $role->key ? 'border-wa-teal bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600' }}">
                                                <div class="text-left">
                                                    <div class="text-sm font-bold {{ isset($createUserForm['role']) && $createUserForm['role'] == $role->key ? 'text-wa-teal dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300' }}">
                                                        {{ $role->name }}
                                                    </div>
                                                    <div class="text-xs text-slate-500 font-medium mt-0.5">{{ $role->description }}</div>
                                                </div>
                                                @if (isset($createUserForm['role']) && $createUserForm['role'] == $role->key)
                                                    <div class="text-wa-teal">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                    <x-input-error for="createUserForm.role" class="mt-1" />
                                </div>
                            </div>

                            <div class="mt-8 flex gap-3">
                                 <button type="button" wire:click="closeAddMemberModal"
                                    class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-xl hover:bg-slate-200 transition-all">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Role Management Modal -->
    <x-dialog-modal wire:model.live="currentlyManagingRole">
        <x-slot name="title">
            {{ __('Manage Role') }}
        </x-slot>

        <x-slot name="content">
            <div class="relative z-0 mt-1 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer">
                @foreach ($this->roles as $index => $role)
                    <button type="button" class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-wa-teal dark:focus:border-wa-teal focus:ring-2 focus:ring-wa-teal dark:focus:ring-wa-teal {{ $index > 0 ? 'border-t border-slate-200 dark:border-slate-700 focus:border-none rounded-t-none' : '' }} {{ ! $loop->last ? 'rounded-b-none' : '' }}"
                                    wire:click="$set('currentRole', '{{ $role->key }}')">
                        <div class="{{ $currentRole !== $role->key ? 'opacity-50' : '' }}">
                            <!-- Role Name -->
                            <div class="flex items-center">
                                <div class="text-sm text-slate-600 dark:text-gray-400 {{ $currentRole == $role->key ? 'font-semibold' : '' }}">
                                    {{ $role->name }}
                                </div>

                                @if ($currentRole == $role->key)
                                    <svg class="ms-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>

                            <!-- Role Description -->
                            <div class="mt-2 text-xs text-slate-600 dark:text-gray-400 text-start">
                                {{ $role->description }}
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="stopManagingRole" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="updateRole" wire:loading.attr="disabled">
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Leave Team Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingLeavingTeam">
        <x-slot name="title">
            {{ __('Leave Team') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you would like to leave this team?') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingLeavingTeam')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="leaveTeam" wire:loading.attr="disabled">
                {{ __('Leave') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Remove Team Member Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingTeamMemberRemoval">
        <x-slot name="title">
            {{ __('Remove Team Member') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you would like to remove this person from the team?') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingTeamMemberRemoval')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="removeTeamMember" wire:loading.attr="disabled">
                {{ __('Remove') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>