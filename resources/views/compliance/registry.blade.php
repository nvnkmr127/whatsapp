<x-app-layout>
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
            <h2
                class="text-xl font-bold bg-white dark:bg-gray-800 p-4 rounded shadow w-full md:w-auto text-gray-800 dark:text-gray-200">
                {{ __('Consent Registry') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('compliance.registry', ['status' => 'OPT_IN']) }}"
                    class="px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded shadow hover:bg-green-200 dark:hover:bg-green-800 transition text-sm font-medium">
                    Opted In
                </a>
                <a href="{{ route('compliance.registry', ['status' => 'OPT_OUT']) }}"
                    class="px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded shadow hover:bg-red-200 dark:hover:bg-red-800 transition text-sm font-medium">
                    Opted Out
                </a>
                <a href="{{ route('compliance.registry') }}"
                    class="px-4 py-2 bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded shadow hover:bg-gray-50 dark:hover:bg-gray-600 transition text-sm font-medium border border-gray-200 dark:border-gray-600">
                    All
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Phone
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Source
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Last Changed
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($contacts as $contact)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $contact->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $contact->phone_number }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            {{ $contact->opt_in_status === 'OPT_IN' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                            ($contact->opt_in_status === 'OPT_OUT' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $contact->opt_in_status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $contact->opt_in_source ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $contact->opt_in_at ? $contact->opt_in_at->format('Y-m-d H:i') : '-' }}
                                                </td>
                                            </tr>
                        @endforeach
                        @if($contacts->isEmpty())
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No records found.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $contacts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>