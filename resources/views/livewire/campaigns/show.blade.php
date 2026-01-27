@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $messages */
    $campaign = $this->campaign;
    $stats = $this->campaignStats;
@endphp
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-data
    x-on:keydown.escape.window="window.location.href='{{ route('campaigns.index') }}'">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="window.location.href='{{ route('campaigns.index') }}'"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Panel -->
        <div
            class="inline-block w-full align-bottom bg-gray-50 dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl">

            <!-- Header -->
            <div
                class="bg-white dark:bg-gray-800 px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Campaign Report <span class="text-indigo-600">#{{ $campaign->id }}</span>
                    </h3>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-gray-200">{{ $campaign->name }}</span>
                        <span class="text-gray-300">&bull;</span>
                        <span>Scheduled:
                            {{ $campaign->scheduled_at ? $campaign->scheduled_at->format('M d, Y H:i') : 'N/A' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="openRetargetModal"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Retarget
                    </button>
                    <a href="{{ route('campaigns.index') }}"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="p-8 space-y-8">

                <!-- Analytics Funnel -->
                <div class="mb-8">
                    <livewire:analytics.campaign-funnel :campaignId="$campaign->id" />
                </div>

                <!-- Use grid for Top Row: Template & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Template Card -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
                        <div
                            class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Template</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ $campaign->template_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Card -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
                        <div
                            class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                            @if($campaign->status == 'completed')
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            @elseif($campaign->status == 'failed')
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status</p>
                            <p
                                class="text-base font-bold uppercase mt-0.5
                                {{ $campaign->status == 'completed' ? 'text-green-600' : ($campaign->status == 'failed' ? 'text-red-600' : 'text-blue-600') }}">
                                {{ $campaign->status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Targeted -->
                    <div
                        class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                            <div class="text-sm font-bold text-gray-400 uppercase tracking-wide">Total Targeted</div>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
                    </div>

                    <!-- Successfully Sent -->
                    <div
                        class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="p-2.5 bg-gray-50 dark:bg-gray-700/50 text-gray-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </div>
                            <div class="text-sm font-bold text-gray-400 uppercase tracking-wide">Successfully Sent</div>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['sent'] }}</div>
                    </div>

                    <!-- Delivery Rate -->
                    <div
                        class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="p-2.5 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-sm font-bold text-gray-400 uppercase tracking-wide">Delivery Rate</div>
                        </div>
                        <div class="text-3xl font-black text-gray-900 dark:text-white">
                            {{ $stats['delivery_rate'] }}%
                        </div>
                    </div>
                </div>

                <!-- Stats Rows (Progress Bars) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Sent -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex flex-col justify-between shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                    </path>
                                </svg>
                            </div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['sent'] }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-blue-600 mb-1">Sent</div>
                            <div class="text-xs text-gray-400 mb-2">Processed</div>
                            <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full"
                                    style="width: {{ $stats['total'] > 0 ? ($stats['sent'] / $stats['total']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivered -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex flex-col justify-between shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span
                                class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['delivered'] }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-green-600 mb-1">Delivered</div>
                            <div class="text-xs text-gray-400 mb-2">
                                {{ $stats['delivery_rate'] }}% of
                                Sent
                            </div>
                            <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                    style="width: {{ $stats['delivery_rate'] }}%">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opened -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex flex-col justify-between shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['read'] }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-yellow-600 mb-1">Opened</div>
                            <div class="text-xs text-gray-400 mb-2">
                                {{ $stats['read_rate'] }}%
                                of Delivered
                            </div>
                            <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full"
                                    style="width: {{ $stats['read_rate'] }}%">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Undelivered -->
                    <div
                        class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex flex-col justify-between shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-gray-100 text-gray-500 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span
                                class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['sent'] - $stats['delivered'] }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-gray-500 mb-1">Undelivered</div>
                            <div class="text-xs text-gray-400 mb-2">Failed / Pending</div>
                            <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gray-400 rounded-full"
                                    style="width: {{ $stats['sent'] > 0 ? (($stats['sent'] - $stats['delivered']) / $stats['sent']) * 100 : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Log Table -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-xs font-black uppercase text-gray-400 tracking-widest">Message Delivery Log</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                <tr>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        #</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Contact</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Status</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Sent At</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Delivered At</th>
                                    <th
                                        class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Opened At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                @forelse($messages as $msg)
                                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                                <td class="px-8 py-4 text-sm text-gray-500 font-medium">{{ $loop->iteration }}</td>
                                                                <td class="px-8 py-4">
                                                                    <div class="flex flex-col">
                                                                        <span
                                                                            class="text-sm font-bold text-gray-900 dark:text-white">{{ $msg->contact->name ?? 'Unknown' }}</span>
                                                                        <span
                                                                            class="text-xs text-gray-400">{{ $msg->phone ?? $msg->contact->phone_number }}</span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-8 py-4">
                                                                    <span
                                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                                                                                                                                                                                                                        {{ $msg->status == 'read' ? 'bg-green-100 text-green-800' :
                                    ($msg->status == 'delivered' ? 'bg-blue-100 text-blue-800' :
                                        ($msg->status == 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                                                        {{ $msg->status }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-8 py-4 text-xs text-gray-500 font-medium font-mono">
                                                                    {{ $msg->created_at->format('d M H:i') }}
                                                                </td>
                                                                <td class="px-8 py-4 text-xs text-gray-500 font-medium font-mono">
                                                                    {{-- CampaignDetail doesn't track exact delivered_at, using updated_at if status
                                                                    match --}}
                                                                    {{ ($msg->status == 'delivered' || $msg->status == 'read') ? $msg->updated_at->format('d M H:i') : '-' }}
                                                                </td>
                                                                <td class="px-8 py-4 text-xs text-gray-500 font-medium font-mono">
                                                                    {{ ($msg->status == 'read') ? $msg->updated_at->format('d M H:i') : '-' }}
                                                                </td>
                                                            </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-8 py-12 text-center text-gray-400 italic">No messages
                                            found for this campaign.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $messages->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Retargeting Modal -->
    <x-dialog-modal wire:model="showRetargetModal">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-indigo-500/10 text-indigo-500 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tight">Retarget
                        Audience</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Re-engage based on
                        interaction</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div
                    class="p-4 bg-indigo-50 dark:bg-indigo-500/10 rounded-2xl border border-indigo-100 dark:border-indigo-500/20">
                    <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Retarget
                        Users Who:</label>
                    <div class="space-y-2">
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="not_read"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Didn't Read
                                (Delivered but ignored)</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="not_delivered"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Didn't Receive
                                (Failed/Sent but not delivered)</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="read"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Read (Engaged
                                users)</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="failed"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Failed
                                (Errors)</span>
                        </label>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3 w-full">
                <button wire:click="$set('showRetargetModal', false)"
                    class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    Cancel
                </button>
                <button wire:click="retarget"
                    class="px-8 py-3 bg-indigo-500 hover:bg-indigo-600 text-white font-black uppercase tracking-widest text-[10px] rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95">
                    Create Retargeting Campaign
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>