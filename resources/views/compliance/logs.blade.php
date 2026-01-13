<x-app-layout>
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
            <h2
                class="text-xl font-bold bg-white dark:bg-gray-800 p-4 rounded shadow w-full md:w-auto text-gray-800 dark:text-gray-200">
                {{ __('Compliance Logs') }}
            </h2>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Contact
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Action
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Source
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Notes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $log->contact->name }} <br>
                                    <span class="text-xs text-gray-500 font-normal">{{ $log->contact->phone_number }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $log->action === 'OPT_IN' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->source }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate"
                                    title="{{ $log->notes }}">
                                    {{ $log->notes }}
                                </td>
                            </tr>
                        @endforeach
                        @if($logs->isEmpty())
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No compliance logs found.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>