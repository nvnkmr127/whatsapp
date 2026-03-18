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

        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Range</label>
                <select wire:model.live="dateRange"
                    class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <option value="7">7d</option>
                    <option value="30">30d</option>
                    <option value="90">90d</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status</label>
                <select wire:model.live="messageStatusFilter"
                    class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <option value="all">All</option>
                    <option value="sent">Sent</option>
                    <option value="delivered">Delivered</option>
                    <option value="read">Read</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <button wire:click="exportMessageReport"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>

            <button wire:click="refreshData"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest shadow-lg shadow-slate-900/10 dark:shadow-wa-teal/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Success Rate</div>
            <div class="text-3xl font-black text-wa-teal mt-2">{{ number_format($dashboard['summary']['completion_rate'] ?? 0, 1) }}%</div>
            <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['summary']['completed_runs'] ?? 0) }} successful / {{ number_format($dashboard['summary']['total_runs'] ?? 0) }} total flows executed</div>
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Failed %</div>
            <div class="text-3xl font-black text-rose-500 mt-2">{{ number_format($dashboard['summary']['failure_rate'] ?? 0, 1) }}%</div>
            <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['summary']['failed_runs'] ?? 0) }} flows did not complete</div>
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Most Common Failed Step</div>
            @if (!empty($dashboard['most_common_failure_node']))
                <div class="text-base font-black text-slate-900 dark:text-white mt-2 truncate">{{ $dashboard['most_common_failure_node']['label'] }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ number_format($dashboard['most_common_failure_node']['failures']) }} times failed</div>
            @else
                <div class="text-base font-black text-slate-700 dark:text-slate-200 mt-2">No failed steps</div>
                <div class="text-xs text-slate-500 mt-1">Flow is running smoothly</div>
            @endif
        </div>

        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Revenue Generated</div>
            <div class="text-3xl font-black text-emerald-500 mt-2">{{ $currencySymbol }}{{ number_format($dashboard['revenue']['attributed_revenue'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">
                {{ number_format($dashboard['revenue']['attributed_orders'] ?? 0) }} orders within {{ $dashboard['revenue']['window_days'] ?? 7 }} days after flow started
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Message Status Report</h2>
            <p class="text-xs text-slate-500 mt-1">Track message delivery and engagement for this flow.</p>
            <div class="mt-3 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest">
                <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                    Delivered: {{ number_format($messageSummary['delivery_rate'] ?? 0, 1) }}%
                </span>
                <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                    Read: {{ number_format($messageSummary['read_rate'] ?? 0, 1) }}%
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 border-b border-slate-100 dark:border-slate-800">
            <div class="rounded-2xl border border-slate-100 dark:border-slate-800 p-4 bg-slate-50/70 dark:bg-slate-800/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sent</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($messageSummary['sent'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-blue-100 dark:border-blue-900/30 p-4 bg-blue-50/70 dark:bg-blue-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-blue-500">Delivered</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($messageSummary['delivered'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 dark:border-emerald-900/30 p-4 bg-emerald-50/70 dark:bg-emerald-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Read</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($messageSummary['read'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-rose-100 dark:border-rose-900/30 p-4 bg-rose-50/70 dark:bg-rose-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500">Failed</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($messageSummary['failed'] ?? 0) }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Time</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Number</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Message ID</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($messageDetails as $message)
                        <tr>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $message->contact?->name ?? 'Unknown' }}
                                @if(!empty($message->contact?->email))
                                    <div class="text-[10px] text-slate-400">{{ $message->contact->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $message->contact?->phone_number ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded
                                {{ ($message->status ?? '') === 'failed' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:text-rose-300' : '' }}
                                {{ ($message->status ?? '') === 'read' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300' : '' }}
                                {{ ($message->status ?? '') === 'delivered' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300' : '' }}
                                {{ ($message->status ?? '') === 'sent' ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' : '' }}">
                                    {{ $message->status ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-[11px] font-mono text-slate-500 dark:text-slate-400 max-w-[220px] truncate">{{ $message->whatsapp_message_id ?? '-' }}</td>
                            <td class="px-6 py-4 text-xs text-rose-500 max-w-[260px] truncate">{{ $message->error_message ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400 font-semibold">No message activity for this flow in the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Step Performance</h2>
                    <p class="text-xs text-slate-500 mt-1">See which steps contacts reach and where people drop off.</p>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Step</label>
                    <select wire:model.live="stepNodeFilter"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                        <option value="all">All Steps</option>
                        @foreach(($dashboard['funnel'] ?? []) as $row)
                            <option value="{{ $row['node_id'] }}">{{ $row['label'] }}</option>
                        @endforeach
                    </select>

                    <button wire:click="exportStepContactsReport"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Step Contacts
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Step</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Step Name</th>
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
            <p class="text-xs text-slate-500 mt-1">How long contacts typically spend between steps in this flow.</p>
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
