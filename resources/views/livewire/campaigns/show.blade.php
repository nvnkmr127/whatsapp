@php
    /** @var \App\Models\Campaign $campaign */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $messages */
@endphp
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Panel -->
        <div
            class="inline-block w-full align-bottom bg-gray-50 dark:bg-slate-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl">

            <!-- Header -->
            <div
                class="bg-white dark:bg-slate-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white" id="modal-title">
                            Campaign Report <span class="text-blue-500">#{{ $campaign->id }}</span>
                        </h3>
                        <!-- Auto-Refresh Indicator -->
                        <div wire:loading class="ml-2">
                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('campaigns.index') }}" class="text-slate-400 hover:text-slate-500">
                        <svg class="h-6 w-6" fill="none" class="h-6 w-6" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
                <div class="mt-2 text-sm text-slate-500">
                    <span
                        class="font-bold text-slate-900 dark:text-white">{{ $campaign->name ?? $campaign->campaign_name }}</span>
                    @if($campaign->scheduled_at)
                        <span class="mx-1">&bull;</span> Scheduled: {{ $campaign->scheduled_at->format('M d, Y H:i') }}
                    @endif
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Template Info -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-start gap-3">
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-500 mb-1">Template</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $campaign->template_name }}
                            </div>
                        </div>
                    </div>
                    <!-- Campaign Status -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-start gap-3">
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-500 mb-1">Status</div>
                            <div
                                class="text-sm font-bold uppercase {{ $campaign->status == 'completed' ? 'text-green-500' : ($campaign->status == 'failed' ? 'text-red-500' : 'text-blue-500') }}">
                                {{ $campaign->status }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Targeted -->
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                        <div class="p-3 bg-blue-50 text-blue-500 rounded-xl"><svg class="w-6 h-6" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg></div>
                        <div>
                            <div class="text-xs font-bold uppercase text-blue-500">Total Targeted</div>
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                    <!-- Sent -->
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                        <div class="p-3 bg-teal-50 text-teal-500 rounded-xl"><svg class="w-6 h-6" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg></div>
                        <div>
                            <div class="text-xs font-bold uppercase text-teal-500">Successfully Sent</div>
                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['sent'] }}</div>
                        </div>
                    </div>
                    <!-- Delivery Rate -->
                    <div
                        class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                        <div class="p-3 bg-cyan-50 text-cyan-500 rounded-xl"><svg class="w-6 h-6" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg></div>
                        <div>
                            <div class="text-xs font-bold uppercase text-cyan-500">Delivery Rate</div>
                            <div class="text-lg font-black text-slate-900 dark:text-white">
                                {{ $stats['sent'] > 0 ? round(($stats['delivered'] / $stats['sent']) * 100) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bars -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Processed -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="p-2 bg-blue-50 text-blue-500 rounded-lg"><svg class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg></div>
                        <div class="flex-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-blue-500">Sent</span>
                                <span class="text-slate-400">{{ $stats['sent'] }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mb-1">Processed</div>
                            <div class="h-1 bg-slate-100 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-blue-500"
                                    style="width: {{ $stats['total'] > 0 ? ($stats['sent'] / $stats['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Delivered -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="p-2 bg-green-50 text-green-500 rounded-lg"><svg class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg></div>
                        <div class="flex-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-green-500">Delivered</span>
                                <span class="text-slate-400">{{ $stats['delivered'] }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mb-1">Delivered
                                ({{ $stats['sent'] > 0 ? round(($stats['delivered'] / $stats['sent']) * 100) : 0 }}%)</div>
                            <div class="h-1 bg-slate-100 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-green-500"
                                    style="width: {{ $stats['sent'] > 0 ? ($stats['delivered'] / $stats['sent']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Opened -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="p-2 bg-yellow-50 text-yellow-500 rounded-lg"><svg class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg></div>
                        <div class="flex-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-yellow-500">Opened</span>
                                <span class="text-slate-400">{{ $stats['read'] }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mb-1">Opened
                                ({{ $stats['delivered'] > 0 ? round(($stats['read'] / $stats['delivered']) * 100) : 0 }}%)
                            </div>
                            <div class="h-1 bg-slate-100 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-yellow-500"
                                    style="width: {{ $stats['delivered'] > 0 ? ($stats['read'] / $stats['delivered']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Unreached (Not Delivered) -->
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="p-2 bg-gray-100 text-gray-500 rounded-lg"><svg class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg></div>
                        <div class="flex-1">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-gray-500">Undelivered</span>
                                <span class="text-slate-400">{{ $stats['sent'] - $stats['delivered'] }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mb-1">Failed/Pending</div>
                            <div class="h-1 bg-slate-100 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-gray-500"
                                    style="width: {{ $stats['sent'] > 0 ? (($stats['sent'] - $stats['delivered']) / $stats['sent']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-50 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center gap-4">
                        <!-- Search is handled by parent, or we can add it if Component supported it. Currently just Refresh button above. -->
                        <h4 class="text-sm font-black uppercase text-slate-400 tracking-widest">Message Delivery Log
                        </h4>
                        <div wire:loading target="gotoPage, nextPage, previousPage" class="text-xs text-slate-400">
                            Loading...</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        #</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Contact</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Status</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Sent At</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Delivered At</th>
                                    <th
                                        class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Opened At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                                @forelse($messages as $msg)
                                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="px-8 py-4 text-xs font-bold text-slate-500">{{ $loop->iteration }}</td>
                                        <td class="px-8 py-4 text-sm font-bold text-slate-900 dark:text-white">
                                            {{ $msg->contact->name ?? $msg->contact->phone }}
                                            <div class="text-xs font-medium text-slate-400">{{ $msg->contact->phone }}</div>
                                        </td>
                                        <td class="px-8 py-4">
                                            <span
                                                class="text-xs font-bold {{ $msg->status == 'read' ? 'text-green-500' : ($msg->status == 'delivered' ? 'text-blue-500' : ($msg->status == 'failed' ? 'text-red-500' : 'text-slate-500')) }}">
                                                {{ ucfirst($msg->status) }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-4 text-xs font-medium text-slate-500">
                                            <div>{{ $msg->created_at->format('d M') }}</div>
                                            <div>{{ $msg->created_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-8 py-4 text-xs font-medium text-slate-500">
                                            @if($msg->delivered_at)
                                                <div>{{ $msg->delivered_at->format('d M') }}</div>
                                                <div>{{ $msg->delivered_at->format('H:i') }}</div>
                                            @else
                                                <div class="text-center text-slate-300">-</div>
                                            @endif
                                        </td>
                                        <td class="px-8 py-4 text-xs font-medium text-slate-500">
                                            @if($msg->read_at)
                                                <div>{{ $msg->read_at->format('d M') }}</div>
                                                <div>{{ $msg->read_at->format('H:i') }}</div>
                                            @else
                                                <div class="text-center text-slate-300">-</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-8 py-10 text-center text-slate-500">No logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-slate-50 dark:border-slate-800">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>