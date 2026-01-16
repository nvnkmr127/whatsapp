<x-app-layout>
    <div class="space-y-8 p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Compliance
                        <span class="text-wa-teal">Logs</span>
                    </h1>
                </div>
                <p class="text-slate-500 font-medium">Audit trail of all consent changes and compliance events.</p>
            </div>
        </div>

        <!-- Table Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Timestamp
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Contact
                                Identity</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Action
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Source
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Notes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($logs as $log)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">
                                            {{ $log->created_at->format('M d, Y') }}
                                        </span>
                                        <span class="text-xs text-slate-500 font-medium tabular-nums">
                                            {{ $log->created_at->format('H:i:s') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $log->contact->name }}"
                                            alt="{{ $log->contact->name }}"
                                            class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 object-cover"
                                            loading="lazy">
                                        <div>
                                            <div class="text-sm font-black text-slate-900 dark:text-white">
                                                {{ $log->contact->name }}
                                            </div>
                                            <div class="text-xs text-slate-500 font-medium tabular-nums">
                                                {{ $log->contact->phone_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full {{ $log->action === 'OPT_IN' ? 'bg-wa-teal shadow-lg shadow-wa-teal/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                        <span
                                            class="text-xs font-black uppercase tracking-widest {{ $log->action === 'OPT_IN' ? 'text-wa-teal' : 'text-rose-500' }}">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                        {{ $log->source }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-xs">
                                        <span class="text-sm text-slate-500 dark:text-slate-400 font-medium line-clamp-2"
                                            title="{{ $log->notes }}">
                                            {{ $log->notes ?: '-' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div
                                            class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="text-slate-400 font-bold">No compliance logs found.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>