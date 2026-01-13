<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Incoming Webhook Logs</h1>
            <p class="text-slate-500 dark:text-slate-400">Monitor raw payloads received from WhatsApp.</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="filterStatus"
                class="rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-sm">
                <option value="">All Statuses</option>
                <option value="processed">Processed</option>
                <option value="failed">Failed</option>
                <option value="pending">Pending</option>
            </select>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ID or Payload..."
                class="rounded-md border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-sm w-64">
        </div>
    </div>

    <!-- Logs Table -->
    <div
        class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ID</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Received At</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Payload Preview</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                #{{ $log->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $log->created_at->format('M d, H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === 'processed')
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Processed
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Failed
                                    </span>
                                @else
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                @endif
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs truncate max-w-xs">
                                {{ Str::limit(json_encode($log->payload), 50) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="viewDetails({{ $log->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                No webhooks found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Details Modal -->
    <div x-data="{ show: @entangle('showDetailsModal') }" x-show="show" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                @click="show = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">

                <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Webhook Details #{{ $selectedPayload?->id }}
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Status</h4>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $selectedPayload?->status }}
                                    </p>
                                    @if($selectedPayload?->error_message)
                                        <p class="text-xs text-red-600 mt-1">{{ $selectedPayload->error_message }}</p>
                                    @endif
                                </div>
                                <div class="bg-gray-950 rounded-lg p-4 overflow-x-auto max-h-96">
                                    <pre
                                        class="text-xs text-green-400 font-mono">{{ json_encode($selectedPayload?->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-slate-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="closeDetails"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>