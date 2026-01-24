<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">My <span
                        class="text-wa-teal">Automations</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Design and manage your automated conversation flows.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="hidden lg:flex items-center gap-6 mr-6 border-r border-slate-100 dark:border-slate-800 pr-6">
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Online
                    </div>
                    <div class="text-lg font-black text-wa-teal leading-none">{{ $stats['active'] }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Total
                        Runs</div>
                    <div class="text-lg font-black text-slate-800 dark:text-white leading-none">
                        {{ number_format($stats['total_runs']) }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">
                        Completion</div>
                    <div
                        class="text-lg font-black {{ $stats['completion_rate'] > 70 ? 'text-wa-teal' : 'text-rose-500' }} leading-none">
                        {{ round($stats['completion_rate']) }}%</div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative group w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium text-sm shadow-sm"
                    placeholder="Search logic...">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <a href="{{ route('automations.builder') }}"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                New
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl shadow-lg flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="font-bold text-sm">Error</p>
                <span class="text-xs">{{ $errors->first('base') ?: 'An unexpected error occurred.' }}</span>
            </div>
        </div>
    @endif

    <!-- Automations List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Name</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Trigger
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Status</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse ($bots as $bot)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-wa-teal/10 group-hover:text-wa-teal transition-colors focus-within:ring-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <a href="{{ route('automations.builder', $bot->id) }}"
                                        class="text-sm font-black text-slate-900 dark:text-white hover:text-wa-teal transition-colors">
                                        {{ $bot->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $bot->trigger_type }}:
                                    {{ implode(', ', $bot->trigger_config['keywords'] ?? []) }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full {{ $bot->is_active ? 'bg-wa-teal shadow-lg shadow-wa-teal/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest {{ $bot->is_active ? 'text-wa-teal' : 'text-rose-500' }}">
                                        {{ $bot->is_active ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="export({{ $bot->id }})" title="Export JSON"
                                        class="p-2 text-slate-400 hover:text-indigo-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="duplicate({{ $bot->id }})" title="Duplicate"
                                        class="p-2 text-slate-400 hover:text-purple-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                            </path>
                                        </svg>
                                    </button>
                                    <a href="{{ route('automations.builder', $bot->id) }}"
                                        class="p-2 text-slate-400 hover:text-wa-teal transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button wire:click="confirmDelete({{ $bot->id }})"
                                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">No Automations
                                        Found</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bots->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $bots->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            <span class="text-slate-900 dark:text-white uppercase font-black tracking-tight">Delete Automation?</span>
        </x-slot>

        <x-slot name="content">
            <span class="text-slate-500 font-medium tracking-tight">Are you sure you want to permanently delete this
                automation? This action cannot be undone.</span>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-3">
                <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled"
                    class="rounded-xl border-none bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-[10px] py-3">
                    Cancel
                </x-secondary-button>

                <x-danger-button wire:click="delete" wire:loading.attr="disabled"
                    class="rounded-xl bg-rose-500 text-white font-black uppercase tracking-widest text-[10px] py-3 shadow-lg shadow-rose-500/20">
                    Delete
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>
</div>