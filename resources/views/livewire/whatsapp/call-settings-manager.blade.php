<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Call <span
                        class="text-wa-teal">Center</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Configure WhatsApp calling, business hours, and manage permissions.</p>
        </div>

        @if($isRestricted)
            <div class="flex items-center gap-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/50 p-4 rounded-2xl animate-pulse">
               <div class="p-3 bg-rose-500 text-white rounded-xl shadow-lg shadow-rose-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
               </div>
               <div>
                   <div class="text-xs font-black text-rose-500 uppercase tracking-widest leading-none">Access Restricted</div>
                   <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $restrictionReason }}</div>
                   <button wire:click="removeRestriction" class="text-[10px] font-black text-rose-500 hover:text-rose-600 uppercase mt-1 tracking-tighter">Fix Now →</button>
               </div>
            </div>
        @endif

        <button wire:click="generateCallLink"
            class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.82a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.103-1.103" />
            </svg>
            Generate Call Link
        </button>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-wa-teal/5 rounded-full -mr-16 -mt-16 transition-all group-hover:scale-110"></div>
            <div class="relative">
                <div class="p-3 bg-wa-teal/10 text-wa-teal rounded-2xl w-fit mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $activePermissions }}</div>
                <div class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Active Permissions</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full -mr-16 -mt-16 transition-all group-hover:scale-110"></div>
            <div class="relative">
                <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl w-fit mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $totalPermissions }}</div>
                <div class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Total Audience</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-sky-500/5 rounded-full -mr-16 -mt-16 transition-all group-hover:scale-110"></div>
            <div class="relative">
                <div class="p-3 bg-sky-500/10 text-sky-500 rounded-2xl w-fit mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2 2-2 2M8 16l-2-2 2-2" />
                    </svg>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $callsMadeToday }}</div>
                <div class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Calls Today</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-slate-500/5 rounded-full -mr-16 -mt-16 transition-all group-hover:scale-110"></div>
            <div class="relative">
                <div class="p-3 bg-slate-500/10 text-slate-500 rounded-2xl w-fit mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $expiredPermissions }}</div>
                <div class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">Expired Windows</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Settings Column -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50">
                <div class="flex items-center gap-4 mb-8">
                   <div class="p-3 bg-wa-teal/10 text-wa-teal rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                   </div>
                   <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">System <span class="text-wa-teal">Rules</span></h2>
                </div>

                <div class="space-y-6">
                    <label class="flex items-center gap-4 group cursor-pointer p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-transparent hover:border-wa-teal/20 transition-all">
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="callingEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal"></div>
                        </div>
                        <div class="flex-1">
                            <span class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Enable Calling</span>
                            <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Allow customers to initiate calls</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-4 group cursor-pointer p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-2 border-transparent hover:border-wa-teal/20 transition-all">
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="callbackPermissionEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal"></div>
                        </div>
                        <div class="flex-1">
                            <span class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Callback Requests</span>
                            <span class="block text-[10px] font-bold text-slate-400 mt-0.5">Automated permission workflows</span>
                        </div>
                    </label>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Call Button Visibility</label>
                        <select wire:model="callIconVisibility"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:ring-2 focus:ring-wa-teal/20 transition-all cursor-pointer appearance-none">
                            <option value="show">Always Visible</option>
                            <option value="hide">Hidden from UI</option>
                        </select>
                    </div>

                    <button wire:click="updateCallSettings" wire:loading.attr="disabled"
                        class="w-full py-5 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-[1.5rem] shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                        <span wire:loading.remove>Update Configuration</span>
                        <span wire:loading class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>

            <!-- SIP Card -->
            <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50" x-data="{ expanded: @entangle('sipEnabled') }">
                <div class="flex items-center justify-between mb-8 cursor-pointer" @click="expanded = !expanded">
                   <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">SIP <span class="text-indigo-500">Trunking</span></h2>
                   </div>
                   <svg class="w-5 h-5 text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                   </svg>
                </div>

                <div x-show="expanded" x-collapse>
                    <div class="space-y-5 pt-2">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Endpoint (URI)</label>
                            <input type="text" wire:model="sipUri" placeholder="sip:calls@business.com"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Username</label>
                                <input type="text" wire:model="sipUsername"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Password</label>
                                <input type="password" wire:model="sipPassword" placeholder="••••••••"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Authentication Realm</label>
                            <input type="text" wire:model="sipRealm"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold text-sm focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Hours Column -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800/50 overflow-hidden relative">
                <div class="absolute top-0 right-0 p-8">
                     <label class="flex items-center gap-3 cursor-pointer group">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-wa-teal transition-colors">Sync Mode</span>
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="syncWithBusinessHours" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal"></div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center gap-4 mb-10">
                   <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                   </div>
                   <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Active <span class="text-amber-500">Window</span></h2>
                        <div class="flex items-center gap-2 mt-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Timezone:</p>
                            <select wire:model="timezone" class="p-0 border-none bg-transparent text-[10px] font-black text-wa-teal uppercase tracking-widest focus:ring-0 cursor-pointer">
                                @foreach($this->timezones as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                   </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                    @foreach($businessHours as $index => $hour)
                        <div class="flex flex-col gap-3 group">
                            <button wire:click="toggleDay({{ $index }})"
                                class="flex flex-col items-center justify-center p-4 rounded-3xl border-2 transition-all {{ ($hour['enabled'] ?? false) ? 'bg-wa-teal/5 border-wa-teal text-wa-teal' : 'bg-slate-50 dark:bg-slate-800/50 border-transparent text-slate-400' }}">
                                <span class="text-xs font-black uppercase tracking-widest leading-none mb-1">{{ substr($hour['day'], 0, 3) }}</span>
                                <div class="w-1.5 h-1.5 rounded-full {{ ($hour['enabled'] ?? false) ? 'bg-wa-teal animate-pulse' : 'bg-slate-300' }}"></div>
                            </button>

                            @if($hour['enabled'] ?? false)
                            <div class="flex flex-col gap-2 p-2 bg-white dark:bg-slate-800 rounded-[1.5rem] border border-slate-50 dark:border-slate-800 shadow-sm animate-in fade-in slide-in-from-top-2">
                                <input type="time" wire:model="businessHours.{{ $index }}.open"
                                    class="border-none bg-slate-50 dark:bg-slate-900/50 p-2 rounded-xl text-[10px] font-black text-slate-900 dark:text-white text-center focus:ring-1 focus:ring-wa-teal/20">
                                <div class="text-[8px] font-black text-slate-300 text-center uppercase tracking-widest">To</div>
                                <input type="time" wire:model="businessHours.{{ $index }}.close"
                                    class="border-none bg-slate-50 dark:bg-slate-900/50 p-2 rounded-xl text-[10px] font-black text-slate-900 dark:text-white text-center focus:ring-1 focus:ring-wa-teal/20">
                                <button wire:click="applyToAll({{ $index }})" 
                                        class="mt-1 p-2 text-slate-300 hover:text-wa-teal transition-colors"
                                        title="Copy to all days">
                                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Call Permissions Table Section -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <!-- Search & Filters -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1 relative group">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                    placeholder="Search by name or phone...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterStatus"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="requested">Requested</option>
                        <option value="granted">Granted</option>
                        <option value="denied">Denied</option>
                        <option value="expired">Expired</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Caller Identity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status & Validity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Call Usage</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Last Activity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($permissions as $permission)
                        <tr wire:key="perm-{{ $permission->id }}" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden relative group">
                                         <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $permission->contact->name }}" 
                                             alt="{{ $permission->contact->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ $permission->contact->name }}</div>
                                        <div class="text-[10px] font-bold text-slate-400 mt-0.5">{{ $permission->contact->phone_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col gap-1.5">
                                    @php
                                        $statusConfig = [
                                            'requested' => ['bg' => 'bg-amber-100 dark:bg-amber-900/20', 'text' => 'text-amber-600', 'dot' => 'bg-amber-500'],
                                            'granted' => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-600', 'dot' => 'bg-green-500'],
                                            'denied' => ['bg' => 'bg-rose-100 dark:bg-rose-900/20', 'text' => 'text-rose-600', 'dot' => 'bg-rose-500'],
                                            'expired' => ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-500', 'dot' => 'bg-slate-400'],
                                            'revoked' => ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-500', 'dot' => 'bg-slate-400'],
                                        ][$permission->permission_status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'dot' => 'bg-slate-400'];
                                    @endphp
                                    <div class="flex items-center w-fit px-2.5 py-1 rounded-md border border-transparent {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }}">
                                        <div class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }} mr-2"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ $permission->permission_status }}</span>
                                    </div>
                                    @if($permission->permission_expires_at)
                                        <div class="text-[10px] font-bold text-slate-400">
                                            Expires {{ $permission->permission_expires_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="text-lg font-black text-slate-700 dark:text-slate-300">{{ $permission->calls_made_count }}</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Total<br>Calls</div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-xs font-bold text-slate-600 dark:text-slate-400">
                                    {{ $permission->last_call_at ? $permission->last_call_at->format('M d, H:i') : 'No calls yet' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium">
                                    {{ $permission->last_call_at ? $permission->last_call_at->diffForHumans() : 'Available' }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if($permission->permission_status === 'requested')
                                        <button wire:click="grantPermissionManually({{ $permission->id }})" 
                                                class="p-2 text-wa-teal hover:bg-wa-teal/10 rounded-lg transition-all"
                                                title="Grant Permission">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    @if(in_array($permission->permission_status, ['granted', 'requested']))
                                        <button wire:click="revokePermission({{ $permission->id }})" 
                                                class="p-2 text-rose-500 hover:bg-rose-500/10 rounded-lg transition-all"
                                                title="Revoke Permission">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-3xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold">No call permissions found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($permissions->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $permissions->links() }}
            </div>
        @endif
    </div>
</div>

@script
<script>
    $wire.on('call-link-generated', (event) => {
        const link = event.link;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(() => {
                alert('Call link copied to clipboard: ' + link);
            });
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = link;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Call link copied to clipboard: ' + link);
        }
    });
</script>
@endscript