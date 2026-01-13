<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Message <span
                        class="text-wa-green">Protocols</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Synchronize and audit your approved WhatsApp communication schemas.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <button wire:click="syncTemplates" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-wa-green text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all w-full sm:w-auto">
                <svg wire:loading.remove wire:target="syncTemplates" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="syncTemplates" class="animate-spin h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Sync Protocols
            </button>
            <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all w-full sm:w-auto">
                Meta Dashboard
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Inventory List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div
            class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative group w-full sm:w-80">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all font-medium text-sm shadow-inner"
                    placeholder="Search schemas...">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-wa-green transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Total Records:
                {{ $templates->total() }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/20">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Schema
                            Identity</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Protocol
                            Category</th>
                        <th
                            class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Locale</th>
                        <th
                            class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Validation Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse ($templates as $template)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div
                                    class="text-sm font-black text-slate-900 dark:text-white group-hover:text-wa-green transition-colors">
                                    {{ $template->template_name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $template->template_id }}</div>
                            </td>
                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $template->category }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="text-xs font-black text-slate-500 uppercase">{{ $template->language }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center">
                                    <div
                                        class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2
                                            @if($template->status === 'APPROVED') bg-wa-green/10 text-wa-green
                                            @elseif($template->status === 'REJECTED') bg-rose-500/10 text-rose-500
                                            @else bg-amber-500/10 text-amber-500 @endif border border-current/10 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $template->status }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                class="px-8 py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">
                                No schemas found. Initiate synchronization to fetch from Meta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>