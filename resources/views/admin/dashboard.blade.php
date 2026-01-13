<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Super Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Companies</div>
                    <div class="text-2xl font-bold dark:text-white">{{ $stats['total_teams'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Active Subscriptions</div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['active_subs'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Users</div>
                    <div class="text-2xl font-bold dark:text-white">{{ $stats['total_users'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Messages</div>
                    <div class="text-2xl font-bold dark:text-white">{{ $stats['total_messages'] }}</div>
                </div>
            </div>

            <!-- Tenants List -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Company Workspaces</h3>
                    <a href="{{ route('admin.tenants.create') }}"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                        + Add Company
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Company</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Owner</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Plan</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Joined</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($teams as $team)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $team->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $team->owner->email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ ucfirst($team->subscription_plan ?? 'Basic') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $team->subscription_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($team->subscription_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $team->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $teams->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>