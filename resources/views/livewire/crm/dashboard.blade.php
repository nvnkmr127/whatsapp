<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Contacts</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_contacts'] }}</p>
            <span class="text-sm text-green-500 font-medium">+{{ $stats['new_contacts'] }} new</span>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Open Deals Value</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ money($stats['open_deals_value']) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Won Deals Value (Last 30 Days)</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ money($stats['won_deals_value']) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversion Rate</h3>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $stats['conversion_rate'] }}%</p>
        </div>
    </div>

    <!-- Pipeline Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Pipeline Distribution</h3>
        <div class="h-64">
            <!-- Add Chart.js or similar here -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($pipelineDistribution as $stage)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $stage['name'] }}</h4>
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $stage['count'] }} deals</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ money($stage['value']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Deals -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Deals</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stage</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Owner</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($recentDeals as $deal)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $deal->title }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ money($deal->value) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $deal->stage->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $deal->contact->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $deal->owner->name ?? 'Unassigned' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
