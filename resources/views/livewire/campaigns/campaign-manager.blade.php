<div class="space-y-8">
    <!-- Header Area -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-800 relative overflow-hidden group">
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 rounded-full -mr-32 -mt-32 blur-3xl group-hover:bg-wa-teal/10 transition-colors duration-700">
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-wa-teal/10 text-wa-teal rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Campaigns & Broadcasts</h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Reach your audience at
                            scale</p>
                    </div>
                </div>
            </div>

            <button wire:click="$set('showCreateModal', true)"
                class="flex items-center justify-center gap-2 px-8 py-4 bg-wa-teal hover:bg-emerald-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                New Campaign
            </button>
        </div>
    </div>

    <!-- Campaign List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Campaign
                            Name</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Status</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Delivery</th>
                        <th
                            class="px-8 py-6 text-[10px) font-black uppercase tracking-widest text-slate-400 text-right">
                            Engagement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($campaigns as $campaign)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-wa-teal transition-colors font-black">
                                        {{ strtoupper(substr($campaign->name, 0, 1)) }}
                                    </div>
                                    <div class="font-black text-slate-900 dark:text-white uppercase tracking-tight text-sm">
                                        {{ $campaign->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span
                                    class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter border {{ $campaign->status_style }}">
                                    {{ $campaign->status }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center text-sm font-bold text-slate-600 dark:text-slate-400">
                                <div class="flex flex-col gap-1 items-center">
                                    <span>{{ $campaign->sent_count }} / {{ $campaign->total_contacts }}</span>
                                    <div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-wa-teal" style="width: {{ $campaign->delivery_percentage }}%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                @if($campaign->sent_count > 0)
                                    <div class="text-sm font-black text-wa-teal">
                                        {{ number_format($campaign->read_percentage, 1) }}% <span
                                            class="text-[9px] uppercase tracking-widest text-slate-400 ml-1">Read</span>
                                    </div>
                                @else
                                    <span class="text-slate-300">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 opacity-20">
                                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs font-black uppercase tracking-widest">No Campaigns Found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
            <div class="px-8 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-50 dark:border-slate-800">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    <x-dialog-modal wire:model="showCreateModal">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-wa-teal/10 text-wa-teal rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tight">Create New
                        Campaign</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Configure your broadcast
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <!-- Name -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Campaign
                        Name</label>
                    <input type="text" wire:model="name" placeholder="e.g. Summer Sale 2024"
                        class="w-full bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-sm font-bold focus:ring-wa-teal focus:border-wa-teal transition-all">
                </div>

                <!-- Template -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">WhatsApp
                        Template</label>
                    <select wire:model="templateName"
                        class="w-full bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-sm font-bold focus:ring-wa-teal focus:border-wa-teal transition-all">
                        <option value="">-- Select Template --</option>
                        @foreach($availableTemplates as $tpl)
                            <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ $tpl->language }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Audience -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Target Audience
                        (Tags)</label>
                    <div
                        class="grid grid-cols-2 gap-3 max-h-48 overflow-y-auto p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                        @foreach($availableTags as $tag)
                            <label
                                class="flex items-center gap-3 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-900 transition-colors cursor-pointer group">
                                <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}"
                                    class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" style="background-color: {{ $tag->color }}"></div>
                                    <span
                                        class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-tight group-hover:text-wa-teal transition-colors">{{ $tag->name }}</span>
                                </div>
                            </label>
                        @endforeach
                        @if(count($availableTags) === 0)
                            <div class="col-span-2 text-center py-4">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No tags found.
                                    Will send to ALL.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Scheduling -->
                <div
                    class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="sendNow"
                            class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal">
                        <span
                            class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-widest">Send
                            Immediately</span>
                    </label>

                    @if(!$sendNow)
                        <div class="space-y-2 animate-in fade-in slide-in-from-top-2 duration-300">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Schedule
                                Date & Time</label>
                            <input type="datetime-local" wire:model="scheduledAt"
                                class="w-full bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold focus:ring-wa-teal focus:border-wa-teal transition-all">
                        </div>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3 w-full">
                <button wire:click="$set('showCreateModal', false)"
                    class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    Close
                </button>
                <button wire:click="create"
                    class="px-8 py-3 bg-wa-teal hover:bg-emerald-600 text-white font-black uppercase tracking-widest text-[10px] rounded-xl shadow-lg shadow-wa-teal/20 transition-all active:scale-95">
                    Launch Campaign
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>