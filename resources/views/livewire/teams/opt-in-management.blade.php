<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Opt-In <span
                        class="text-purple-500">Management</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage how users subscribe and unsubscribe from your messages.</p>
        </div>
        <div class="flex gap-3">
            <x-action-message class="mr-3 flex items-center" on="saved">
                <span
                    class="text-purple-500 font-bold text-xs uppercase tracking-widest">{{ __('Changes Saved') }}</span>
            </x-action-message>
            <button wire:click="save"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-purple-600 text-white dark:text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-purple-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Save Settings
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Opt-In Settings -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Subscription
                    </h2>
                    <p class="text-sm text-green-500 font-bold uppercase tracking-wider mt-1">Opt-In Configuration</p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Keywords -->
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">Opt-In Keywords</label>
                    <div class="flex gap-2 mb-4">
                        <input type="text" wire:model="newOptInKeyword" placeholder="e.g. START, JOIN"
                            class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-green-500/20 text-slate-900 dark:text-white">
                        <button wire:click="addOptInKeyword"
                            class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-black rounded-xl text-xs uppercase tracking-widest transition-all shadow-lg shadow-green-500/20">
                            Add
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2 min-h-[40px]">
                        @forelse($optInKeywords as $index => $keyword)
                            <span
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 uppercase tracking-wide border border-green-100 dark:border-green-800/50">
                                {{ $keyword }}
                                <button wire:click="removeOptInKeyword({{ $index }})"
                                    class="hover:text-green-800 dark:hover:text-green-200 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-slate-400 text-sm font-medium italic py-2">No keywords added yet.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Message -->
                <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <label class="text-[10px] font-black uppercase text-slate-400">Confirmation Message</label>
                        <!-- Custom Toggle -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="optInMessageEnabled" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500">
                            </div>
                        </label>
                    </div>

                    @if($optInMessageEnabled)
                        <div class="animate-in fade-in slide-in-from-top-2 duration-200">
                            <textarea wire:model="optInMessage" rows="3"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-green-500/20 text-slate-900 dark:text-white resize-none"
                                placeholder="Message to send when user opts in..."></textarea>
                            <p class="mt-2 text-[10px] uppercase font-bold text-slate-400 text-right">Sent immediately after
                                keyword match</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Opt-Out Settings -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Unsubscribe
                    </h2>
                    <p class="text-sm text-rose-500 font-bold uppercase tracking-wider mt-1">Opt-Out Configuration</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Keywords -->
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">Opt-Out Keywords</label>
                    <div class="flex gap-2 mb-4">
                        <input type="text" wire:model="newOptOutKeyword" placeholder="e.g. STOP, UNSUBSCRIBE"
                            class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-rose-500/20 text-slate-900 dark:text-white">
                        <button wire:click="addOptOutKeyword"
                            class="px-6 py-3 bg-rose-500 hover:bg-rose-600 text-white font-black rounded-xl text-xs uppercase tracking-widest transition-all shadow-lg shadow-rose-500/20">
                            Add
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2 min-h-[40px]">
                        @forelse($optOutKeywords as $index => $keyword)
                            <span
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 uppercase tracking-wide border border-rose-100 dark:border-rose-800/50">
                                {{ $keyword }}
                                <button wire:click="removeOptOutKeyword({{ $index }})"
                                    class="hover:text-rose-800 dark:hover:text-rose-200 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-slate-400 text-sm font-medium italic py-2">No keywords added yet.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Message -->
                <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <label class="text-[10px] font-black uppercase text-slate-400">Confirmation Message</label>
                        <!-- Custom Toggle -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="optOutMessageEnabled" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-300 dark:peer-focus:ring-rose-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-rose-500">
                            </div>
                        </label>
                    </div>

                    @if($optOutMessageEnabled)
                        <div class="animate-in fade-in slide-in-from-top-2 duration-200">
                            <textarea wire:model="optOutMessage" rows="3"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-rose-500/20 text-slate-900 dark:text-white resize-none"
                                placeholder="Message to send when user opts out..."></textarea>
                            <p class="mt-2 text-[10px] uppercase font-bold text-slate-400 text-right">Sent immediately after
                                keyword match</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>