<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $campaign->campaign_name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Template: {{ $template_name }} | Scheduled:
                {{ $campaign->scheduled_at }}
            </p>
        </div>
        <a href="{{ route('campaigns.index') }}"
            class="btn bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            &larr; Back to Campaigns
        </a>
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