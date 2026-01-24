<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold uppercase tracking-tight">
                {{ $campaign->campaign_name }}
            </h1>
            <div class="flex items-center gap-3 mt-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">Template: <span
                        class="font-bold text-gray-700 dark:text-gray-200">{{ $template_name }}</span></p>
                <span class="text-gray-300">|</span>
                <p class="text-sm text-gray-500 dark:text-gray-400">Scheduled: <span
                        class="font-bold text-gray-700 dark:text-gray-200">{{ $campaign->scheduled_at }}</span></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right mr-4 hidden md:block">
                <div class="text-[10px] font-black underline uppercase text-slate-400 tracking-widest leading-none">
                    Status Freshness</div>
                <div class="text-xs font-bold text-wa-teal">Updated: {{ $lastRefresh }}</div>
            </div>
            <button wire:click="refreshStats" wire:loading.class="animate-spin"
                class="p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <a href="{{ route('campaigns.index') }}"
                class="px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-xl hover:scale-[1.02] transition-all shadow-lg active:scale-95">
                &larr; Campaigns
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5 border-l-4 border-blue-500">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Total Recipients</div>
            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $totalCount }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5 border-l-4 border-green-500">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Delivered</div>
            <div class="flex items-end justify-between">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $deliverCount }}</div>
                <div class="text-sm text-green-600 font-bold mb-1">{{ $totalDeliveredPercent }}%</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5 border-l-4 border-teal-500">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Read</div>
            <div class="flex items-end justify-between">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $readCount }}</div>
                <div class="text-sm text-teal-600 font-bold mb-1">{{ $totalReadPercent }}%</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5 border-l-4 border-red-500">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Failed</div>
            <div class="flex items-end justify-between">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $failedCount }}</div>
                <div class="text-sm text-red-600 font-bold mb-1">{{ $totalFailedPercent }}%</div>
            </div>
        </div>
    </div>

    <!-- Analytics Funnel -->
    <div class="mb-8">
        <livewire:analytics.campaign-funnel :campaignId="$campaignId" />
    </div>

    <!-- Recipients Table -->
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
        <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recipient Status</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="table-auto w-full">
                <thead
                    class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="p-2 whitespace-nowrap">
                            <div class="font-semibold text-left">Contact Name</div>
                        </th>
                        <th class="p-2 whitespace-nowrap">
                            <div class="font-semibold text-left">Phone</div>
                        </th>
                        <th class="p-2 whitespace-nowrap">
                            <div class="font-semibold text-center">Status</div>
                        </th>
                        <th class="p-2 whitespace-nowrap">
                            <div class="font-semibold text-center">Last Updated</div>
                        </th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($details as $detail)
                        <tr>
                            <td class="p-2 whitespace-nowrap">
                                <div class="font-medium text-gray-800 dark:text-gray-100">
                                    {{ $detail->contact ? $detail->contact->first_name . ' ' . $detail->contact->last_name : 'Unknown' }}
                                </div>
                            </td>
                            <td class="p-2 whitespace-nowrap">
                                <div class="text-left">{{ $detail->phone }}</div>
                            </td>
                            <td class="p-2 whitespace-nowrap">
                                <div class="text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @if($detail->status === 'read') bg-teal-100 text-teal-800
                                                        @elseif($detail->status === 'delivered') bg-green-100 text-green-800
                                                        @elseif($detail->status === 'sent') bg-blue-100 text-blue-800
                                                        @elseif($detail->status === 'failed') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($detail->status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-2 whitespace-nowrap">
                                <div class="text-center">{{ $detail->updated_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-500">No recipients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $details->links() }}
        </div>
    </div>
</div>