<div
    class="h-full flex flex-col bg-white dark:bg-slate-950 border-l border-slate-100 dark:border-slate-900 transition-colors">
    <!-- Header -->
    <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-900 flex items-center justify-between">
        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Intelligence Profile</h3>
        <button @click="openPane = false" class="lg:hidden p-2 text-slate-400 hover:text-wa-teal transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    @if($contact)
        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            <!-- Hero Profile Section -->
            <div
                class="p-8 flex flex-col items-center bg-slate-50/50 dark:bg-slate-900/20 border-b border-slate-50 dark:border-slate-900">
                <div class="relative group">
                    <div
                        class="absolute -inset-2 bg-gradient-to-tr from-wa-green to-wa-teal rounded-full blur opacity-20 group-hover:opacity-40 transition-opacity">
                    </div>
                    <div
                        class="relative h-24 w-24 rounded-[2rem] bg-white dark:bg-slate-800 shadow-xl flex items-center justify-center text-slate-400 group-hover:text-wa-teal transition-all">
                        <span class="text-3xl font-black uppercase">{{ substr($contact->name ?? '?', 0, 1) }}</span>
                    </div>
                </div>

                <h4 class="mt-6 text-xl font-black text-slate-900 dark:text-white tracking-tight text-center">
                    {{ $contact->name }}</h4>
                <p class="text-sm font-bold text-slate-500 mt-1">{{ $contact->phone_number }}</p>

                <div class="mt-4 flex items-center gap-2">
                    <span
                        class="px-3 py-1 bg-wa-green/10 text-wa-green text-[10px] font-black uppercase tracking-widest rounded-lg border border-wa-green/20">
                        {{ $contact->opt_in_status ?? 'SUBSCRIBED' }}
                    </span>
                    <span class="w-2 h-2 rounded-full bg-wa-green animate-pulse"></span>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div class="p-8 space-y-10">
                <!-- Operational Assignment -->
                <section>
                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Command Center
                        Assignment</h5>
                    <div
                        class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        @if($conversation->assignee)
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-xl bg-wa-teal/10 text-wa-teal flex items-center justify-center text-xs font-black uppercase">
                                    {{ substr($conversation->assignee->name, 0, 1) }}
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-black text-slate-900 dark:text-white">{{ $conversation->assignee->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Current Controller</span>
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
                                Deploy Self
                            </button>
                        @endif
                    </div>
                </section>

                <!-- Intelligence Tags -->
                <section>
                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Logic Classifications
                    </h5>
                    <div class="flex flex-wrap gap-2">
                        @forelse($contact->tags as $tag)
                            <span
                                class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm">
                                {{ $tag->name }}
                            </span>
                        @empty
                            <div
                                class="w-full py-6 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase">Undefined</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- Data Schema (Encrypted View) -->
                <section x-data="{ showData: false }">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contact Payload</h5>
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

                <!-- Field Intelligence Notes -->
                <section>
                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Field Intelligence
                    </h5>

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
                                    {{ $note->content }}</p>
                            </div>
                        @empty
                            <div
                                class="w-full py-8 text-center bg-slate-50/50 dark:bg-slate-900/30 rounded-3xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-black text-slate-300 uppercase italic tracking-widest">No field
                                    logs recorded.</span>
                            </div>
                        @endforelse
                    </div>

                    <form wire:submit.prevent="addNote" class="relative group">
                        <textarea wire:model="newNoteBody"
                            class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-xs font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-wa-teal/20 focus:border-wa-teal transition-all min-h-[100px]"
                            placeholder="Append field intelligence..."></textarea>
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