<div
    class="h-full flex flex-col bg-white dark:bg-slate-950 border-l border-slate-100 dark:border-slate-900 transition-colors">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="p-1.5 bg-wa-teal/10 text-wa-teal rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h1 class="text-xs font-black text-slate-900 dark:text-white tracking-tight uppercase">Profile</h1>
        </div>
        <button @click="$dispatch('toggle-details')"
            class="p-2 text-slate-400 hover:text-rose-500 transition-colors bg-slate-50 dark:bg-slate-800/50 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    @if($contact)
        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            <!-- Hero Profile Section -->
            <div
                class="p-6 flex flex-col items-center bg-slate-50/30 dark:bg-slate-900/20 border-b border-slate-100/50 dark:border-slate-900">
                <div class="relative group">
                    <div
                        class="absolute -inset-2 bg-gradient-to-tr from-wa-teal to-wa-teal rounded-full blur opacity-20 group-hover:opacity-40 transition-opacity">
                    </div>
                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name ?? 'Unknown' }}"
                        alt="{{ $contact->name }}"
                        class="relative h-16 w-16 rounded-2xl bg-white dark:bg-slate-800 object-cover shadow-xl transition-transform group-hover:scale-105">
                </div>

                <h4 class="mt-4 text-sm font-black text-slate-800 dark:text-white tracking-tight text-center">
                    {{ $contact->name }}
                </h4>
                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase tracking-wider">{{ $contact->phone_number }}
                </p>

                <div class="mt-3 flex items-center gap-2">
                    <button wire:click="toggleOptIn" wire:loading.attr="disabled" class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md border transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed
                                    {{ $contact->opt_in_status === 'opted_in'
            ? 'bg-wa-teal/10 text-wa-teal border-wa-teal/20 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700 hover:bg-wa-teal/10 hover:text-wa-teal hover:border-wa-teal/20' 
                                    }}">
                        <!-- Default Text -->
                        <span class="block {{ $contact->opt_in_status === 'opted_in' ? 'group-hover:hidden' : '' }}">
                            {{ $contact->opt_in_status === 'opted_in' ? 'OPTED IN' : 'OPTED OUT' }}
                        </span>

                        <!-- Hover Text for Toggle Action (Only show 'OPT OUT' on hover if currently opted in, logic handled via css classes slightly complex here, keeping simpler for now) -->
                        <!-- Actually, let's keep it simple: Status is button. Click to toggle. -->
                    </button>
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-teal animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div class="p-6 space-y-8">
                <!-- Operational Assignment -->
                <section>
                    <h5
                        class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Assigned To
                    </h5>
                    <div
                        class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border-none flex items-center justify-between">
                        @if($conversation->assignee)
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-xl bg-wa-teal/10 text-wa-teal flex items-center justify-center text-xs font-black uppercase">
                                    {{ substr($conversation->assignee->name, 0, 1) }}
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-black text-slate-900 dark:text-white">{{ $conversation->assignee->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Agent</span>
                                </div>
                            </div>
                            <button wire:click="unassign"
                                class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @else
                            <span class="text-xs font-black text-slate-400 italic uppercase tracking-wider">Unassigned</span>
                            <button wire:click="assignToSelf"
                                class="px-4 py-2 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 transition-all">
                                Assign to Me
                            </button>
                        @endif
                    </div>
                </section>

                <section>
                    <h5
                        class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Labels
                    </h5>
                    <div class="flex flex-wrap gap-2">
                        @forelse($contact->tags as $tag)
                            <span
                                class="px-3 py-1 bg-slate-50 dark:bg-slate-900 border-none text-slate-600 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wide rounded-lg">
                                {{ $tag->name }}
                            </span>
                        @empty
                            <div
                                class="w-full py-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase">Undefined</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section x-data="{ showData: false }">
                    <div class="flex items-center justify-between mb-3">
                        <h5
                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Custom Fields
                        </h5>
                        <button @click="showData = !showData"
                            class="text-[10px] font-black text-wa-teal uppercase tracking-widest hover:underline transition-all">
                            <span x-show="!showData">View Raw</span>
                            <span x-show="showData">Conceal</span>
                        </button>
                    </div>
                    <div x-show="showData" x-collapse>
                        <div
                            class="bg-slate-900 rounded-2xl p-4 text-[10px] font-mono text-slate-400 overflow-x-auto shadow-2xl">
                            @if($contact->custom_attributes)
                                <pre
                                    class="p-0 m-0">{{ json_encode($contact->custom_attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="italic text-slate-600">No extended payload attributes.</span>
                            @endif
                        </div>
                    </div>
                    <div x-show="!showData"
                        class="py-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dotted border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2">
                        <svg class="w-3 h-3 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Metadata
                            Encrypted</span>
                    </div>
                </section>

                <section>
                    <h5
                        class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.405 2.405 0 011.002-2.736l3.144-1.921A1.76 1.76 0 0111 5.882zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Campaign History
                    </h5>
                    <div class="space-y-2">
                        @php
                            $uniqueCampaigns = $contact->attributedMessages
                                ->map(fn($m) => $m->attributedCampaign)
                                ->filter()
                                ->unique('id')
                                ->sortByDesc('created_at');
                        @endphp

                        @forelse($uniqueCampaigns as $camp)
                            <div
                                class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-between group">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[10px] font-black text-slate-900 dark:text-white truncate max-w-[140px]">{{ $camp->name }}</span>
                                    <span
                                        class="text-[8px] font-bold text-slate-400 uppercase">{{ $camp->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-1.5 py-0.5 bg-wa-teal/10 text-wa-teal border border-wa-teal/20 rounded text-[7px] font-black uppercase">Interacted</span>
                                </div>
                            </div>
                        @empty
                            <div
                                class="py-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase italic">No Campaign Reach</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section>
                    <h5 class="text-[10px] font-bold text-slate-400/70 uppercase tracking-[0.2em] mb-4">Notes</h5>

                    <div class="space-y-4 mb-6">
                        @forelse($conversation->notes as $note)
                            <div
                                class="relative pl-6 before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 before:bg-wa-teal/20 before:rounded-full">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-black text-wa-teal uppercase">{{ $note->user->name }}</span>
                                    <span
                                        class="text-[10px] font-bold text-slate-400">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs font-medium text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ $note->content }}
                                </p>
                            </div>
                        @empty
                            <div
                                class="w-full py-8 text-center bg-slate-50/50 dark:bg-slate-900/30 rounded-3xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-black text-slate-300 uppercase italic tracking-widest">No notes
                                    created.</span>
                            </div>
                        @endforelse
                    </div>

                    <form wire:submit.prevent="addNote" class="relative group">
                        <textarea wire:model="newNoteBody"
                            class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-xs font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-wa-teal/20 focus:border-wa-teal transition-all min-h-[100px]"
                            placeholder="Add a note..."></textarea>
                        <div class="absolute right-3 bottom-3 flex items-center gap-2">
                            <span
                                class="text-[10px] font-black text-slate-300 uppercase tracking-widest pointer-events-none opacity-0 group-focus-within:opacity-100 transition-opacity mr-2">Press
                                CTRL+ENTER to deploy</span>
                            <button type="submit"
                                class="p-2.5 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 rounded-xl shadow-lg hover:scale-110 active:scale-95 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    @else
        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center">
            <div
                class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center text-slate-200 dark:text-slate-800 mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Awaiting Contact Selection</h4>
            <p class="mt-2 text-xs font-medium text-slate-300 dark:text-slate-600">Select an active transmission to
                initialize intelligence profile synchronization.</p>
        </div>
    @endif
</div>