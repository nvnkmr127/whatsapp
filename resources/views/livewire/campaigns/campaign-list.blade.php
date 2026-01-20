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
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Broadcast <span
                        class="text-wa-teal">Studio</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Launch and monitor your bulk WhatsApp message campaigns.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <!-- Search -->
            <div class="relative group w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium text-sm shadow-sm"
                    placeholder="Find campaigns...">
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
                Create Campaign
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
                                                @elseif($campaign->status === 'processing') bg-wa-blue/10 text-wa-blue border border-wa-blue/20 animate-pulse
                                                @else bg-slate-100 text-slate-400 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 @endif">
                        {{ $campaign->status }}
                    </span>
                </div>

                <div class="flex flex-col h-full">
                    <div class="mb-6">
                        <div class="text-xs font-black text-wa-teal uppercase tracking-widest mb-1">
                            {{ $campaign->template_name }}
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
                                class="text-xs font-bold">{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('M d, Y • H:i') : 'Instant Dispatch' }}</span>
                        </div>
                    </div>

                    <div
                        class="mt-auto flex items-center justify-between pt-6 border-t border-slate-50 dark:border-slate-800/50">
                        <div class="flex items-center gap-2">
                            @if(in_array($campaign->status, ['processing', 'sending', 'queued']))
                                <a href="{{ route('campaigns.live', $campaign->id) }}"
                                    class="px-4 py-2 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all shadow-lg shadow-wa-teal/20">
                                    Live Monitor
                                </a>
                            @endif

                            <a href="{{ route('campaigns.show', $campaign->id) }}"
                                class="px-4 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                View Report
                            </a>
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
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">No Active
                        Campaigns</h3>
                    <p class="text-slate-500 font-medium mt-1">Start your first broadcast outreach today.</p>
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
            <span class="text-slate-900 dark:text-white uppercase font-black tracking-tight">Erase Campaign?</span>
        </x-slot>

        <x-slot name="content">
            <span class="text-slate-500 font-medium">This will permanently delete the campaign and its history record.
                Analaytics will be archived.</span>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-3">
                <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled"
                    class="rounded-xl border-none bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-[10px] py-3">
                    Cancel
                </x-secondary-button>

                <x-danger-button wire:click="delete" wire:loading.attr="disabled"
                    class="rounded-xl bg-rose-500 text-white font-black uppercase tracking-widest text-[10px] py-3 shadow-lg shadow-rose-500/20">
                    Erase Permanently
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>
</div>