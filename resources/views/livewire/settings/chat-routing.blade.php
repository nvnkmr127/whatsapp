<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Chat <span
                        class="text-wa-teal">Routing</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage how potential chats are routed to team members.</p>
        </div>
    </div>

    <!-- Chat Routing Content -->
    <div>
        <!-- Chat Rules Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Chat <span
                    class="text-wa-teal">Rules</span></h2>
            <p class="text-slate-500 font-medium mt-1">Set the chat rules for incoming chats.</p>
        </div>

        <!-- Chat Assignment Rules -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden mb-8">
            <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Chat Assignment Rules</h3>
            </div>

            <div class="p-8">
                <!-- Search -->
                <div class="relative mb-6">
                    <input type="text" wire:model.live.debounce.300ms="memberSearch"
                        placeholder="Search by name or email..."
                        class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                <th class="px-0 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Team Member</th>
                                <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Assigned When</th>
                                <th
                                    class="px-0 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                    Ticket Assigned To</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-0 py-6">
                                        <div class="flex items-center gap-3">
                                            <img class="w-8 h-8 rounded-full object-cover"
                                                src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="text-xs font-medium text-slate-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        @php
                                            $role = $user->membership->role;
                                            $isEligible = $this->getRecommendedStatus($role);
                                        @endphp
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-slate-500">Round Robin</span>
                                            @if($isEligible)
                                                <span
                                                    class="text-[10px] font-bold text-wa-teal uppercase tracking-wide">Recommended</span>
                                            @elseif($user->membership->receives_tickets)
                                                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-wide">Role
                                                    Mismatch</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-0 py-6 text-right">
                                        <button wire:click="toggleTicketAssignment({{ $user->id }})"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $user->membership->receives_tickets ? 'bg-wa-teal' : 'bg-slate-200 dark:bg-slate-700' }}">
                                            <span
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $user->membership->receives_tickets ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer for Assignment Rules -->
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <!-- Chat Status Rules -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 mb-8">
            <div class="mb-8">
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Chat Status Rules
                </h3>
                <p class="text-sm text-slate-500 font-medium mt-1">Configure chat status rules</p>
            </div>

            <div class="space-y-12">
                @foreach($statusRules as $index => $rule)
                    <div
                        class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-2 items-start animate-in fade-in slide-in-from-left-4 duration-300">
                        <!-- Status In -->
                        <div class="md:col-span-4 space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status In</label>
                            <select wire:model="statusRules.{{ $index }}.status_in"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                                @foreach($availableStatuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="statusRules.{{ $index }}.status_in" class="mt-1" />
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Select the current
                                status the chat must be in before the rule is applied.</p>
                        </div>

                        <!-- After Days -->
                        <div class="md:col-span-3 space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">After
                                Days</label>
                            <input type="number" wire:model="statusRules.{{ $index }}.after_days"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="Days">
                            <x-input-error for="statusRules.{{ $index }}.after_days" class="mt-1" />
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Enter the number of days
                                (max 365) after which the status should change.</p>
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
                            <x-input-error for="statusRules.{{ $index }}.status_to" class="mt-1" />
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Select the status the
                                chat should change to after the specified days.</p>
                        </div>

                        <!-- Actions -->
                        <div class="md:col-span-1 pt-8 text-right">
                            <button wire:click="removeStatusRule({{ $index }})"
                                class="p-2 text-slate-300 hover:text-rose-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                <!-- Add More -->
                <button wire:click="addStatusRule"
                    class="mt-4 flex items-center gap-2 text-wa-teal hover:text-wa-teal transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest">Add More Rules</span>
                </button>
            </div>

            <div class="mt-12 flex items-center gap-4">
                <button wire:click="saveStatusRules"
                    class="px-10 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                    Save Rules
                </button>

                <x-action-message on="saved" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </div>
    </div>
</div>