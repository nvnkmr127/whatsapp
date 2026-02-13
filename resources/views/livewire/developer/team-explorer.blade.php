<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-indigo-600 rounded-2xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m14-10a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    TEAM <span class="text-indigo-600">EXPLORER</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Global workspace oversight and diagnostics</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative group">
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="pl-10 pr-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                    placeholder="Search teams or owners...">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <select wire:model.live="filterPlan"
                class="py-2.5 px-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold uppercase tracking-widest text-slate-500 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                <option value="">All Plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan }}">{{ ucfirst($plan) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($teams as $team)
            <div wire:click="viewTeam({{ $team->id }})"
                class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all cursor-pointer relative overflow-hidden">
                <div
                    class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-2xl group-hover:bg-indigo-100 transition-colors">
                </div>

                <div class="relative flex flex-col h-full">
                    <div class="flex justify-between items-start mb-6">
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-white/5 flex items-center justify-center text-indigo-500 font-black text-xl">
                            {{ substr($team->name, 0, 1) }}
                        </div>
                        <div class="flex flex-col items-end">
                            <span
                                class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-[9px] font-black uppercase tracking-widest text-slate-500 rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                {{ $team->subscription_plan ?: 'FREE' }}
                            </span>
                            <span class="mt-1 text-[9px] font-black uppercase text-wa-green tracking-widest">
                                {{ $team->subscription_status }}
                            </span>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight mb-1 truncate">
                        {{ $team->name }}
                    </h3>
                    <p class="text-xs text-slate-400 font-bold mb-6 italic truncate">{{ $team->owner->email }}</p>

                    <div class="grid grid-cols-3 gap-4 mt-auto">
                        <div class="text-center">
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ $team->users_count }}</div>
                            <div class="text-[8px] font-black uppercase tracking-widest text-slate-400">Users</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{
            number_format($team->messages_count) }}</div>
                            <div class="text-[8px] font-black uppercase tracking-widest text-slate-400">Msgs</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ $team->contacts_count }}</div>
                            <div class="text-[8px] font-black uppercase tracking-widest text-slate-400">Contacts</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $teams->links() }}
    </div>

    <!-- Team Details Modal -->
    <x-modal wire:model="showDetailsModal" maxWidth="4xl">
        @if($selectedTeam)
            <div class="p-8 bg-white dark:bg-slate-900">
                <div class="flex justify-between items-start mb-8 border-b border-slate-50 dark:border-slate-800 pb-8">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-20 h-20 rounded-[2rem] bg-indigo-600 text-white flex items-center justify-center text-4xl font-black shadow-2xl shadow-indigo-600/20">
                            {{ substr($selectedTeam->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                {{ $selectedTeam->name }}
                            </h2>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-sm font-bold text-slate-400">WSID: {{ $selectedTeam->id }}</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                <span class="text-sm font-bold text-indigo-500 uppercase tracking-widest">{{
            $selectedTeam->subscription_plan }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="impersonateOwner({{ $selectedTeam->id }})"
                            class="px-6 py-3 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl">
                            Impersonate Owner
                        </button>
                        <button wire:click="closeDetails"
                            class="p-3 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-2xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    @foreach($selectedTeam->stats as $key => $value)
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800/50 uppercase tracking-tight">
                            <div class="text-[10px] font-black text-slate-400 mb-1">{{ str_replace('_', ' ', $key) }}</div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($value) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Owner Info -->
                    <div
                        class="bg-white dark:bg-slate-800/20 rounded-[2rem] p-8 border border-slate-50 dark:border-slate-800 shadow-sm">
                        <h4
                            class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-50 dark:border-slate-800 pb-4">
                            Owner Profile</h4>
                        <div class="flex items-center gap-4 mb-6">
                            <img src="{{ $selectedTeam->owner->profile_photo_url }}"
                                class="w-12 h-12 rounded-full shadow-lg">
                            <div>
                                <div class="text-lg font-black text-slate-900 dark:text-white uppercase">
                                    {{ $selectedTeam->owner->name }}</div>
                                <div class="text-sm text-indigo-500 font-bold tracking-tight">
                                    {{ $selectedTeam->owner->email }}</div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between text-xs font-bold uppercase">
                                <span class="text-slate-400">Phone</span>
                                <span
                                    class="text-slate-700 dark:text-slate-200">{{ $selectedTeam->owner->phone ?: 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-xs font-bold uppercase">
                                <span class="text-slate-400">Joined</span>
                                <span
                                    class="text-slate-700 dark:text-slate-200">{{ $selectedTeam->owner->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members -->
                    <div
                        class="bg-white dark:bg-slate-800/20 rounded-[2rem] p-8 border border-slate-50 dark:border-slate-800 shadow-sm">
                        <h4
                            class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-50 dark:border-slate-800 pb-4">
                            Team Members ({{ $selectedTeam->users->count() }})</h4>
                        <div class="space-y-4 max-h-[250px] overflow-y-auto pr-2">
                            @foreach($selectedTeam->users as $user)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $user->profile_photo_url }}" class="w-8 h-8 rounded-full">
                                        <div>
                                            <div class="text-xs font-black text-slate-900 dark:text-white uppercase">
                                                {{ $user->name }}</div>
                                            <div class="text-[10px] text-slate-500 font-bold uppercase">
                                                {{ $user->membership?->role ?: 'Agent' }}</div>
                                        </div>
                                    </div>
                                    <span
                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $user->last_seen_at?->diffForHumans() ?: 'Never' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-modal>
</div>