<div class="space-y-8">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
        <div>
            <a href="{{ route('automations.index') }}"
                class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-wa-teal transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back To Automations
            </a>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                Automation <span class="text-wa-teal">Analytics</span>
            </h1>
            <p class="text-slate-500 font-medium mt-2">
                {{ $automation->name }}
            </p>
        </div>

        <button wire:click="refreshData"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest shadow-lg shadow-slate-900/10 dark:shadow-wa-teal/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Completion Rate</div>
            <div class="text-3xl font-black text-wa-teal mt-2">{{ number_format($dashboard['summary']['completion_rate'] ?? 0, 1) }}%</div>
            <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['summary']['completed_runs'] ?? 0) }} completed / {{ number_format($dashboard['summary']['total_runs'] ?? 0) }} runs</div>
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Failure Rate</div>
            <div class="text-3xl font-black text-rose-500 mt-2">{{ number_format($dashboard['summary']['failure_rate'] ?? 0, 1) }}%</div>
            <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['summary']['failed_runs'] ?? 0) }} failed runs</div>
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Most Common Failure Node</div>
            @if (!empty($dashboard['most_common_failure_node']))
                <div class="text-base font-black text-slate-900 dark:text-white mt-2 truncate">{{ $dashboard['most_common_failure_node']['label'] }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['most_common_failure_node']['failures']) }} failures</div>
            @else
                <div class="text-base font-black text-slate-700 dark:text-slate-200 mt-2">No failed nodes</div>
                <div class="text-xs text-slate-500 mt-1">Healthy execution pattern so far</div>
            @endif
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Attributed Revenue</div>
            <div class="text-3xl font-black text-emerald-500 mt-2">{{ $currencySymbol }}{{ number_format($dashboard['revenue']['attributed_revenue'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">
                {{ number_format($dashboard['revenue']['attributed_orders'] ?? 0) }} orders within {{ $dashboard['revenue']['window_days'] ?? 7 }}-day window after run start
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Funnel Drop-Off By Step</h2>
            <p class="text-xs text-slate-500 mt-1">Contacts that reached each node and where the largest fall-off occurs.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Step</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Reached</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Drop-Off</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse(($dashboard['funnel'] ?? []) as $row)
                        <tr>
                            <td class="px-6 py-4 text-xs font-black text-slate-500">{{ $row['step'] }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $row['label'] }}</div>
                                <div class="text-xs text-slate-400">{{ $row['node_id'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-black text-slate-900 dark:text-white">{{ number_format($row['reached_contacts']) }}</td>
                            <td class="px-6 py-4 text-right text-xs font-black {{ ($row['dropoff_rate'] ?? 0) >= 40 ? 'text-rose-500' : 'text-slate-500' }}">
                                @if($row['dropoff_rate'] === null)
                                    -
                                @else
                                    {{ number_format($row['dropoff_count']) }} ({{ number_format($row['dropoff_rate'], 1) }}%)
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400 font-semibold">No funnel data yet for this automation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Average Time Between Steps</h2>
            <p class="text-xs text-slate-500 mt-1">Based on successful node transitions in the run ledger.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">From</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">To</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Avg Time</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Samples</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse(($dashboard['average_step_timing'] ?? []) as $row)
                        <tr>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $row['from_label'] }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $row['to_label'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-black text-wa-teal">{{ $row['avg_human'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-slate-500">{{ number_format($row['samples']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400 font-semibold">No step transition timing yet for this automation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
