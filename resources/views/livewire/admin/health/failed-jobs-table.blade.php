<div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Failed Jobs (Last 24h)</h4>
        <div class="flex items-center gap-3">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search exception / payload / trace id..."
                class="w-80 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm"
            />
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <th class="py-3 pr-4">Failed</th>
                    <th class="py-3 pr-4">Team</th>
                    <th class="py-3 pr-4">Queue</th>
                    <th class="py-3 pr-4">Job</th>
                    <th class="py-3 pr-4">Exception</th>
                    <th class="py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($failedJobs as $job)
                    <tr class="text-sm">
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-600 dark:text-slate-300">
                            {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}
                            <div class="text-[10px] font-mono text-slate-400">{{ $job->uuid }}</div>
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                            {{ $job->team_name }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-mono text-slate-500">
                            {{ $job->connection }}:{{ $job->queue }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                            {{ $job->job }}
                        </td>
                        <td class="py-4 pr-4 text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $job->exception_preview }}
                        </td>
                        <td class="py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openDetails('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-[10px] font-black uppercase">View</button>
                                <button wire:click="retry('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-[10px] font-black uppercase">Retry</button>
                                <button wire:click="forget('{{ $job->uuid }}')" class="px-3 py-1.5 rounded-xl bg-rose-600 text-white text-[10px] font-black uppercase">Forget</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">
                            No failed jobs in the last 24 hours
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $failedJobs->links() }}
    </div>

    <x-dialog-modal wire:model.live="showDetailsModal" maxWidth="5xl">
        <x-slot name="title">
            <div class="flex items-center justify-between w-full">
                <div class="text-lg font-black text-slate-900 dark:text-white">Failed Job Details</div>
                <div class="text-[10px] font-mono text-slate-400">{{ $selected['uuid'] ?? '' }}</div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Team</div>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $selected['team_name'] ?? '' }}</div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Queue</div>
                        <div class="text-sm font-mono text-slate-700 dark:text-slate-200">{{ ($selected['connection'] ?? '') . ':' . ($selected['queue'] ?? '') }}</div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Exception</div>
                    <pre class="text-[11px] leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $selected['exception'] ?? '' }}</pre>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Payload</div>
                    <pre class="text-[11px] leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $selected['payload'] ?? '' }}</pre>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <button wire:click="$set('showDetailsModal', false)" class="px-6 py-2.5 text-sm font-bold text-slate-500">Close</button>
                <div class="flex items-center gap-2">
                    @if(!empty($selected['uuid']))
                        <button wire:click="retry('{{ $selected['uuid'] }}')" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-black text-sm">Retry</button>
                        <button wire:click="forget('{{ $selected['uuid'] }}')" class="px-4 py-2.5 bg-rose-600 text-white rounded-xl font-black text-sm">Forget</button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>

