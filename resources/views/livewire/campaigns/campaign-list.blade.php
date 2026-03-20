<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Message <span class="text-wa-teal">Studio</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Send and track your large WhatsApp message groups.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="hidden lg:flex items-center gap-6 mr-6 border-r border-slate-100 dark:border-slate-800 pr-6">
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Active
                    </div>
                    <div class="text-lg font-black text-slate-800 dark:text-white leading-none">{{ $stats['active'] }}
                    </div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">
                        Success</div>
                    <div
                        class="text-lg font-black {{ $stats['success_rate'] > 90 ? 'text-wa-teal' : 'text-rose-500' }} leading-none">
                        {{ round($stats['success_rate']) }}%
                    </div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">
                        Engagement</div>
                    <div class="text-lg font-black text-slate-800 dark:text-white leading-none">
                        {{ round($stats['engagement']) }}%
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative group w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium text-sm shadow-sm"
                    placeholder="Find messages...">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <a href="{{ route('campaigns.create') }}"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Create
            </a>
        </div>
    </div>

    <!-- Campaigns Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($campaigns as $campaign)
            <div
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 transition-all duration-300 hover:shadow-2xl hover:scale-[1.01] relative overflow-hidden">
                <!-- Status Badge -->
                <div class="absolute top-6 right-6">
                    <span
                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full 
                        @if($campaign->status === 'completed') bg-wa-teal/10 text-wa-teal border border-wa-teal/20
                        @elseif($campaign->status === 'failed') bg-rose-500/10 text-rose-500 border border-rose-500/20
                        @elseif($campaign->status === 'paused') bg-wa-orange/10 text-wa-orange border border-wa-orange/20
                        @elseif($campaign->status === 'processing' || $campaign->status === 'sending') bg-wa-blue/10 text-wa-blue border border-wa-blue/20 animate-pulse
                        @else bg-slate-100 text-slate-400 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 @endif">
                        {{ $campaign->status }}
                    </span>
                </div>

                <div class="flex flex-col h-full">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="text-xs font-black text-wa-teal uppercase tracking-widest">
                                {{ $campaign->template_name }}
                            </div>
                            @if($campaign->campaign_type === 'drip')
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-600 text-[9px] font-black rounded-lg uppercase tracking-widest">Drip</span>
                            @endif
                        </div>
                        <h3
                            class="text-xl font-black text-slate-900 dark:text-white group-hover:text-wa-teal transition-colors tracking-tight">
                            {{ $campaign->campaign_name }}
                        </h3>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex items-center gap-3 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span
                                class="text-xs font-bold">{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('M d, Y • H:i') : 'Sent Now' }}</span>
                        </div>
                    </div>

                    <div
                        class="mt-auto flex items-center justify-between pt-6 border-t border-slate-50 dark:border-slate-800/50">
                        <div class="flex items-center gap-2">
                            @if(in_array($campaign->status, ['processing', 'sending', 'queued']))
                                <a href="{{ route('campaigns.live', $campaign->id) }}"
                                    class="px-4 py-2 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all shadow-lg shadow-wa-teal/20">
                                    Live
                                </a>
                                <button wire:click="pause({{ $campaign->id }})"
                                    class="px-4 py-2 bg-wa-orange text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all shadow-lg shadow-wa-orange/20">
                                    Pause
                                </button>
                            @endif

                            @if($campaign->status === 'paused')
                                <button wire:click="resume({{ $campaign->id }})"
                                    class="px-4 py-2 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all shadow-lg shadow-wa-teal/20">
                                    Resume
                                </button>
                            @endif

                            <a href="{{ route('campaigns.show', $campaign->id) }}"
                                class="px-4 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                View Report
                            </a>

                            <button wire:click="cloneCampaign({{ $campaign->id }})"
                                class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                Clone
                            </button>

                            @if($campaign->status == 'completed' || $campaign->status == 'failed')
                                <button wire:click="openRetargetModal({{ $campaign->id }})"
                                    class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                                    Retarget
                                </button>
                            @endif
                        </div>
                        <button wire:click="confirmDelete({{ $campaign->id }})"
                            class="p-2 text-slate-300 hover:text-rose-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="col-span-full py-20 bg-white dark:bg-slate-900 rounded-[2.5rem] border border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center gap-6">
                <div
                    class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] flex items-center justify-center text-slate-200">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">No Active Messages</h3>
                    <p class="text-slate-500 font-medium mt-1">Start sending your first message today.</p>
                </div>
                <a href="{{ route('campaigns.create') }}"
                    class="px-8 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.05] transition-all">
                    Create Campaign
                </a>
            </div>
        @endforelse
    </div>

    @if($campaigns->hasPages())
        <div class="mt-8">
            {{ $campaigns->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            <span class="text-slate-900 dark:text-white uppercase font-black tracking-tight">Delete Message?</span>
        </x-slot>

        <x-slot name="content">
            <span class="text-slate-500 font-medium">This will delete the message and its history. Past stats will be saved.</span>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-3">
                <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled"
                    class="rounded-xl border-none bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-[10px] py-3">
                    Cancel
                </x-secondary-button>

                <x-danger-button wire:click="delete" wire:loading.attr="disabled"
                    class="rounded-xl bg-rose-500 text-white font-black uppercase tracking-widest text-[10px] py-3 shadow-lg shadow-rose-500/20">
                    Delete Permanently
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>

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
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Got it but didn\'t read</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="not_delivered"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Didn\'t receive it</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="read"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Read it</span>
                        </label>
                        <label
                            class="flex items-center p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-500/20 cursor-pointer hover:border-indigo-300 transition-colors">
                            <input type="radio" wire:model="retargetingCriteria" value="failed"
                                class="text-indigo-500 focus:ring-indigo-500 border-gray-300">
                            <span class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-300">Had an error</span>
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
                    Send Follow-up Message
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>