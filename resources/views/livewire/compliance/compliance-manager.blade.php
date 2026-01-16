<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Compliance <span
                        class="text-emerald-500">Manager</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Monitor consent logs and maintain compliance records.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div
            class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">{{ number_format($stats['total']) }}
            </div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400">Total Logs</div>
        </div>

        <div
            class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-wa-teal/10 rounded-xl">
                    <svg class="w-5 h-5 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-wa-teal mb-1">{{ number_format($stats['granted']) }}</div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400">Opted In</div>
        </div>

        <div
            class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-rose-500/10 rounded-xl">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-rose-500 mb-1">{{ number_format($stats['revoked']) }}</div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400">Opted Out</div>
        </div>

        <div
            class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-indigo-500/10 rounded-xl">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-indigo-500 mb-1">{{ $stats['rate'] }}%</div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-400">Consent Rate</div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <!-- Search & Filters -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1 relative group">
                <input wire:model.live.debounce.300ms="searchTerm" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-500/20 transition-all font-medium"
                    placeholder="Search by contact name or phone...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterStatus"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-emerald-500/20 transition-all appearance-none cursor-pointer">
                        <option value="all">All Status</option>
                        <option value="granted">Opted In</option>
                        <option value="revoked">Opted Out</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterDateRange"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-emerald-500/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Time</option>
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Timestamp
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Action
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Source
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($consentLogs as $log)
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
                                    {{ $log->source ?? 'N/A' }}
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

        @if($consentLogs->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $consentLogs->links() }}
            </div>
        @endif
    </div>
</div>