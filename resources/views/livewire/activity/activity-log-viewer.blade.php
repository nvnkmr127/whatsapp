<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
        <h2
            class="text-xl font-bold bg-white dark:bg-gray-800 p-4 rounded shadow w-full md:w-auto text-gray-800 dark:text-gray-200">
            Activity Logs
        </h2>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Search logs...">
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="filterUser"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="filterAction"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Date</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            User</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Action</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Description</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <img class="h-8 w-8 rounded-full"
                                            src="{{ $log->user ? $log->user->profile_photo_url : 'https://ui-avatars.com/api/?name=System&color=7F9CF5&background=EBF4FF' }}"
                                            alt="">
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $log->user ? $log->user->name : 'System' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate"
                                title="{{ $log->description }}">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                No activity logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>