<div class="flex-1 flex flex-col h-full relative bg-slate-200 dark:bg-wa-dark-bg overflow-hidden ring-0 border-none outline-none"
    x-data="chatWindow(
        $wire, 
        '{{ $conversation?->id }}', 
        '{{ $conversation?->team_id }}', 
        '{{ auth()->id() }}',
        $wire.entangle('showTransferModal'),
        $wire.entangle('showInteractiveButtonsModal'),
        $wire.entangle('isNoteMode'),
        $wire.entangle('lightboxOpen'),
        $wire.entangle('lightboxImage'),
        @js($this->quickReplies),
        @js($initialMessages)
    )"
    @chat-scroll-to-id.window="scrollToMessage($event.detail.id)">

    <!-- Chat Header -->
    <div
        class="px-6 py-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 flex items-center justify-between z-20 shrink-0">
        <div class="flex items-center gap-4">
            <!-- Mobile Back Button -->
            <x-app-button variant="ghost" @click="$dispatch('toggle-mobile-pane', 'list')" class="lg:hidden p-2 -ml-2">
                <x-icon name="chevron-left" class="w-6 h-6" />
            </x-app-button>


            <!-- Avatar Section -->
            <div class="relative group">
                <div
                    class="absolute -inset-1 bg-gradient-to-tr from-wa-teal to-emerald-400 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity blur-sm">
                </div>
                <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $conversation?->contact?->name ?? 'Unknown' }}"
                    class="relative h-11 w-11 rounded-2xl bg-slate-100 dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 object-cover transition-transform duration-300 group-hover:scale-105">
                <div
                    class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white dark:border-slate-900 shadow-sm">
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $conversation?->contact?->name ?? $conversation?->contact?->phone_number ?? __('Unknown Recipient') }}
                    </h2>
                    @if($isSessionOpen)
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"
                            title="{{ __('Active Window') }}"></span>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3 text-wa-teal" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        <span class="text-tiny font-mono font-bold text-slate-500 dark:text-slate-400">
                            {{ $conversation?->contact?->phone_number }}
                        </span>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700 font-bold">•</span>
                    <span class="text-tiny font-black uppercase tracking-widest text-slate-400">
                        {{ $isSessionOpen ? __('Conversation Open') : __('Conversation Closed') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-3">
            <!-- Sound Mute Toggle -->
            <button type="button" @click="$store.chat.toggleSoundMute()" 
                class="p-2 text-slate-400 hover:text-wa-teal rounded-xl transition-all"
                :title="$store.chat.soundMuted ? '{{ __('Unmute Sounds') }}' : '{{ __('Mute Sounds') }}'">
                <template x-if="!$store.chat.soundMuted">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>
                </template>
                <template x-if="$store.chat.soundMuted">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" /></svg>
                </template>
            </button>

            <!-- Tags/Categories Dropdown -->
            <div class="relative" x-data="{ showTags: false }">
                <x-app-button variant="secondary" @click="showTags = !showTags" class="hidden sm:flex items-center gap-2" title="{{ __('Manage Tags') }}">
                    <x-icon name="tag" class="w-3.5 h-3.5" />
                    <span>{{ __('Tags') }}</span>

                    @php
                        $activeTags = $conversation?->metadata['tags'] ?? [];
                        $tagCount = count($activeTags);
                    @endphp
                    @if($tagCount > 0)
                        <span
                            class="px-1.5 py-0.5 bg-wa-teal text-white rounded-full text-micro font-black">{{ $tagCount }}</span>
                    @endif
                </x-app-button>

                <!-- Tags Dropdown Menu -->
                <div x-show="showTags" @click.away="showTags = false" x-cloak x-transition
                    class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-3 z-50 max-h-80 overflow-y-auto">
                    <div class="mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-tiny font-black text-slate-900 dark:text-white uppercase tracking-widest">
                            {{ __('Conversation Tags') }}</h3>
                    </div>
                    <div class="space-y-2">
                        @forelse($availableCategories as $category)
                            @php
                                $isActive = in_array($category->id, $activeTags);
                            @endphp
                            <button wire:click="toggleCategory({{ $category->id }})"
                                class="w-full flex items-center justify-between p-2 rounded-xl transition-all hover:bg-slate-50 dark:hover:bg-slate-700 group">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }}">
                                    </div>
                                    <span
                                        class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $category->name }}</span>
                                </div>
                                @if($isActive)
                                    <svg class="w-4 h-4 text-wa-teal" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @empty
                            <div class="py-4 text-center">
                                <span class="text-tiny font-bold text-slate-400 uppercase">{{ __('No tags available') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Transfer Conversation Dropdown -->
            <div class="relative" x-data="{ showTransfer: false }">
                <button @click="showTransfer = !showTransfer"
                    class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl text-tiny font-black uppercase tracking-widest transition-all border bg-slate-50 border-slate-100 text-slate-400 dark:bg-slate-800 dark:border-slate-700 hover:text-indigo-500 hover:border-indigo-200 hover:scale-105 active:scale-95"
                    title="{{ __('Transfer Chat') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>{{ __('Transfer') }}</span>
                </button>

                <!-- Transfer Dropdown Menu -->
                <div x-show="showTransfer" @click.away="showTransfer = false" x-cloak x-transition
                    class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-3 z-50 max-h-80 overflow-y-auto">
                    <div class="mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-tiny font-black text-slate-900 dark:text-white uppercase tracking-widest">
                            {{ __('Send current chat to:') }}</h3>
                    </div>
                    <div class="space-y-1">
                        @forelse($agents as $agent)
                            <button wire:click="transferConversation({{ $agent->id }}); showTransfer = false"
                                class="w-full flex items-center gap-3 p-2 rounded-xl transition-all hover:bg-indigo-50 dark:hover:bg-indigo-900/20 group">
                                <div
                                    class="h-8 w-8 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-xs font-black uppercase shadow-sm">
                                    {{ substr($agent->name, 0, 1) }}
                                </div>
                                <div class="flex-1 text-left">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $agent->name }}
                                    </div>
                                    <div class="text-tiny font-medium text-slate-500">{{ $agent->email }}</div>
                                </div>
                                <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        @empty
                            <div class="py-4 text-center">
                                <span class="text-tiny font-bold text-slate-400 uppercase">{{ __('No agents available') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Bot Status Indicator/Toggle -->
            <button wire:click="toggleBot"
                class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl text-tiny font-black uppercase tracking-widest transition-all border
                       {{ $conversation?->contact?->is_bot_paused
    ? 'bg-amber-100/50 border-amber-200 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800'
    : 'bg-slate-50 border-slate-100 text-slate-400 dark:bg-slate-800 dark:border-slate-700' }} hover:scale-105 active:scale-95 group/bot-status relative">
                <div class="relative">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    @if(!$conversation?->contact?->is_bot_paused)
                        <span class="absolute -top-1 -right-1 flex h-1.5 w-1.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-wa-teal opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-wa-teal"></span>
                        </span>
                    @endif
                </div>
                <span>{{ $conversation?->contact?->is_bot_paused ? __('Bot Paused') : __('Bot Active') }}</span>

                @if($conversation?->contact?->is_bot_paused)
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-48 bg-white dark:bg-slate-800 p-2 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 opacity-0 group-hover/bot-status:opacity-100 transition-opacity z-50 pointer-events-none">
                        <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1">{{ __('Pause Info') }}</p>
                        <p class="text-[10px] text-slate-700 dark:text-slate-300 font-bold">
                            {{ __('Reason') }}: <span class="capitalize">{{ $conversation?->contact?->bot_paused_reason ?? 'Manual' }}</span>
                        </p>
                        @if($conversation?->contact?->bot_paused_at)
                            <p class="text-[10px] text-slate-700 dark:text-slate-300 font-bold">
                                {{ __('Paused') }}: <span>{{ $conversation?->contact?->bot_paused_at->diffForHumans() }}</span>
                            </p>
                        @endif
                        @if($conversation?->contact?->bot_paused_until)
                            <p class="text-[10px] text-wa-teal font-bold mt-1">
                                {{ __('Resumes') }}: <span>{{ $conversation?->contact?->bot_paused_until->diffForHumans() }}</span>
                            </p>
                        @endif
                    </div>
                @endif
            </button>

            <!-- AI Assistant Button -->
            <button wire:click="$dispatch('triggerAiAssistant', { conversationId: {{ $conversationId }} })"
                class="hidden sm:flex items-center gap-2 px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-xl text-tiny font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all outline outline-1 outline-indigo-200/50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>AI</span>
            </button>

            <!-- Template Picker Button -->
            <button wire:click="$dispatch('openTemplatePicker')"
                class="flex items-center gap-2 px-6 py-3 bg-wa-teal text-white rounded-xl text-xs font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-wa-teal/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <span>{{ __('Choose Template') }}</span>
            </button>

            <!-- Call Button Component -->
            <div class="h-10 w-px bg-slate-100 dark:bg-slate-800 mx-1 hidden sm:block"></div>

            <livewire:chat.whatsapp-call-button :conversation-id="$conversationId" :key="'call-' . $conversationId" />

            <!-- More Actions Dropdown -->
            <div class="relative" x-data="{ showMore: false }">
                <button @click="showMore = !showMore"
                    class="p-2.5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-all"
                    title="{{ __('More Actions') }}">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                    </svg>
                </button>

                <!-- More Actions Menu -->
                <div x-show="showMore" @click.away="showMore = false" x-cloak x-transition
                    class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 py-2 z-50">

                    <!-- Conversation Status -->
                    @if($conversation?->status === 'closed')
                        <button wire:click="reopenConversation(); showMore = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                            <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Reopen Conversation') }}</div>
                                <div class="text-tiny text-slate-500">{{ __('Mark as active') }}</div>
                            </div>
                        </button>
                    @else
                        <button wire:click="closeConversation('resolved'); showMore = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors group">
                            <div class="p-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Close Conversation') }}</div>
                                <div class="text-tiny text-slate-500">{{ __('Mark as resolved') }}</div>
                            </div>
                        </button>
                    @endif

                    <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>

                    <!-- Mark as Spam -->
                    <button wire:click="markAsSpam(); showMore = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors group">
                        <div class="p-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Mark as Spam') }}</div>
                            <div class="text-tiny text-slate-500">{{ __('Flag conversation') }}</div>
                        </div>
                    </button>

                    <!-- Block Contact -->
                    <button wire:click="blockContact(); showMore = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors group">
                        <div class="p-1.5 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-rose-600">{{ __('Block Contact') }}</div>
                            <div class="text-tiny text-rose-500">{{ __('Prevent future messages') }}</div>
                        </div>
                    </button>

                    <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>

                    <!-- Export Conversation -->
                    <button wire:click="downloadTranscript(); showMore = false"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors group">
                        <div class="p-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Export Chat') }}</div>
                            <div class="text-tiny text-slate-500">{{ __('Download as PDF') }}</div>
                        </div>
                    </button>
                </div>
            </div>

            <button @click="$dispatch('toggle-details')"
                class="p-2.5 text-slate-400 hover:text-wa-teal hover:bg-wa-teal/5 rounded-xl transition-all group"
                title="{{ __('View Profile Info') }}">
                <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Floating Date Header -->
    <div class="absolute top-20 left-1/2 -translate-x-1/2 z-10 pointer-events-none transition-opacity duration-300"
        :class="scrollTop > 50 ? 'opacity-100' : 'opacity-0'">
        <span
            class="bg-slate-100/90 dark:bg-slate-800/90 backdrop-blur-sm text-slate-500 dark:text-slate-400 text-tiny font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm border border-slate-200 dark:border-slate-700">
            {{ __('Today') }}
        </span>
    </div>

    <!-- Floating Unread Jump Button -->
    <div x-show="unreadBelowCount > 0 && !isNearBottom" x-cloak x-transition
        class="absolute bottom-24 left-1/2 -translate-x-1/2 z-30">
        <button type="button" @click="scrollToBottom()"
            class="px-4 py-2 bg-wa-teal text-white text-xs font-black uppercase tracking-widest rounded-full shadow-xl flex items-center gap-2 hover:scale-105 active:scale-95 transition-all">
            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
            <span>{{ __('New Messages') }} (<span x-text="unreadBelowCount"></span>)</span>
        </button>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 overflow-y-auto custom-scrollbar relative" x-ref="chatContainer" @scroll.passive="handleScroll">

        <div class="min-h-full px-4 sm:px-12 py-6 space-y-1 relative"
            :style="'padding-top: ' + renderConfig.top + 'px; padding-bottom: ' + renderConfig.bottom + 'px'">

            <!-- Load More -->
            <div class="flex justify-center mb-6" x-show="$store.chat.hasMore" x-cloak>
                <button type="button" @click="$store.chat.loadMessages()" :disabled="$store.chat.loading"
                    class="px-5 py-2 bg-white/50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 backdrop-blur-sm text-slate-500 dark:text-slate-400 text-tiny font-black uppercase tracking-widest rounded-full border border-slate-200 dark:border-slate-700 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">
                    <span x-show="!$store.chat.loading">{{ __('Load Previous Messages') }}</span>
                    <span x-show="$store.chat.loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ __('Loading...') }}
                    </span>
                </button>
            </div>

            <!-- Loading Indicator -->
            <div x-show="$store.chat.loading" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-wa-teal"></div>
            </div>

            <!-- Messages Loop -->
            <template x-for="(message, index) in visibleMessages" :key="message.id">
                <x-chat.bubble :contactName="$conversation?->contact?->name ?? 'Contact'" />
            </template>

            <!-- Typing Indicators -->
            <template x-for="user in $store.chat.typingUsers" :key="user.id">
                <div class="flex items-end gap-2 mb-4 animate-in fade-in slide-in-from-bottom-2">
                    <div class="relative shrink-0">
                        <img :src="'https://api.dicebear.com/9.x/initials/svg?seed=' + user.name"
                            class="rounded-full bg-slate-100 dark:bg-slate-700 object-cover border border-slate-100 dark:border-slate-700"
                            style="width: 2rem; height: 2rem;" :alt="user.name">
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 p-3 rounded-2xl rounded-tl-sm border border-slate-100 dark:border-slate-700 shadow-sm flex gap-1">
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce delay-100"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce delay-200"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    @if($isSessionOpen)
        <!-- Attachment Preview (file is picked / uploading) -->
        <div x-show="hasAttachment" x-cloak
            class="px-6 py-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between sticky bottom-0 z-30">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-lg shadow-wa-teal/5 border border-slate-100 dark:border-slate-800 flex items-center justify-center overflow-hidden">
                    <template x-if="attachmentPreview">
                        <img :src="attachmentPreview" class="h-full w-full rounded-xl object-cover">
                    </template>
                    <template x-if="!attachmentPreview">
                        <svg class="h-6 w-6 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </template>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tight truncate max-w-[200px]"
                        x-text="attachmentName"></h4>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"
                        x-text="$wire.newAttachmentData ? 'Ready to send' : 'Uploading…'"></span>
                </div>
            </div>

            <button type="button" @click="clearAttachment()"
                class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 transition-all hover:scale-110 active:scale-95 border border-rose-100/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Upload Error -->
        <x-app-modal wire:model="uploadError" maxWidth="sm">
            <div class="p-6 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 mb-6">
                    <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-2 uppercase tracking-tight">{{ __('Upload Error') }}</h3>
                <p class="text-sm text-slate-500 mb-6 font-bold">{{ $uploadError }}</p>
                <x-app-button variant="primary" @click="$wire.uploadError = null" class="w-full">
                    {{ __('Dismiss') }}
                </x-app-button>
            </div>
        </x-app-modal>

        <!-- Input Area -->
        <div
            class="bg-slate-100 dark:bg-slate-900 px-4 py-3 sm:px-6 sm:py-4 flex items-end gap-2 sm:gap-4 relative z-30 border-t border-slate-200 dark:border-slate-800">

            <!-- Voice Recording Overlay -->
            <div x-show="isRecording" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute inset-x-4 bottom-4 top-4 z-50 p-5 bg-gradient-to-r from-rose-600 to-rose-500 text-white rounded-[2.5rem] flex items-center justify-between shadow-2xl shadow-rose-500/20 overflow-hidden">

                <!-- Waveform Decoration -->
                <div class="absolute inset-0 opacity-10 flex items-center justify-center gap-1 pointer-events-none">
                    @foreach(range(1, 20) as $i)
                        <div class="w-1 bg-white rounded-full animate-pulse"
                            style="height: {{ rand(20, 80) }}%; animation-delay: {{ $i * 0.1 }}s"></div>
                    @endforeach
                </div>

                <div class="flex items-center gap-5 z-10">
                    <div class="relative">
                        <div class="w-3 h-3 rounded-full bg-white animate-ping"></div>
                        <div class="absolute inset-0 w-3 h-3 rounded-full bg-white"></div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-nano font-black uppercase tracking-[0.2em] text-rose-100">{{ __('Recording...') }}</span>
                        <span class="text-lg font-mono font-black tabular-nums" x-text="recordingTime">0:00</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 z-10">
                    <button @click="stopRecording(false)"
                        class="p-3 bg-white/10 hover:bg-white/20 rounded-full transition-all hover:rotate-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button @click="stopRecording(true)"
                        class="px-8 py-3 bg-white text-rose-600 rounded-2xl text-tiny font-bold uppercase tracking-widest shadow-lg hover:scale-105 active:scale-95 transition-all">
                        {{ __('Send Recording') }}
                    </button>
                </div>
            </div>

            @if($this->isOptedOut)
                <div class="bg-amber-50 dark:bg-amber-950/40 border-l-4 border-amber-500 p-3 mx-4 mb-2 rounded-r-xl flex items-center justify-between z-20">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ __('Contact has opted out. Send an approved template to initiate communication.') }}</span>
                    </div>
                    <x-app-button variant="secondary" size="xs" wire:click="$dispatch('openTemplatePicker')">
                        {{ __('Select Template') }}
                    </x-app-button>
                </div>
            @endif

            <form @submit.prevent="handleSubmit().then(() => { replyingTo = null; })" class="flex-1 flex items-center gap-2 relative"
                x-data="{ replyingTo: null }" 
                @reply-to-message.window="replyingTo = $event.detail; $wire.set('replyToMessageId', $event.detail.id)">
                <input type="file" wire:model="newAttachment" class="hidden" x-ref="fileInput"
                    @change="onFilePicked($event.target.files[0])"
                    accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">

                <button type="button" @click="$refs.fileInput.click()"
                    class="p-3 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-wa-teal hover:bg-wa-teal/5 rounded-xl transition-all hover:scale-110 active:scale-95 shadow-sm border border-slate-100/50 dark:border-slate-800/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                </button>

                <!-- Input Field -->
                <div class="flex-1 relative group">
                    <template x-if="replyingTo">
                        <div class="absolute bottom-full left-0 mb-3 w-full z-10">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden flex mx-2">
                                <div class="w-1.5 bg-[#d95a2b] shrink-0"></div>
                                <div class="flex-1 p-2.5 flex justify-between items-center">
                                    <div class="overflow-hidden">
                                        <p class="text-[13px] font-semibold text-[#d95a2b] mb-0.5" x-text="replyingTo.is_outbound ? 'You' : {{ \Illuminate\Support\Js::from($conversation->contact->name ?? $conversation->contact->phone_number) }}"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate" x-text="replyingTo.content"></p>
                                    </div>
                                    <button type="button" @click="replyingTo = null; $wire.set('replyToMessageId', null)" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="isNoteMode">
                        <div
                            class="absolute -top-6 left-6 px-3 py-0.5 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-400 text-nano font-black uppercase tracking-widest rounded-t-lg border-t border-x border-amber-200 dark:border-amber-800">
                            {{ __('PRIVATE NOTE') }}
                        </div>
                    </template>
                    <!-- Formatting Toolbar -->
                    <div class="flex items-center gap-1 mb-1 px-3">
                        <button type="button" @click="applyFormat('*')" class="px-2 py-0.5 text-xs font-black bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300 transition-colors" title="{{ __('Bold (*text*)') }}">B</button>
                        <button type="button" @click="applyFormat('_')" class="px-2 py-0.5 text-xs italic font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300 transition-colors" title="{{ __('Italic (_text_)') }}">I</button>
                        <button type="button" @click="applyFormat('~')" class="px-2 py-0.5 text-xs line-through font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300 transition-colors" title="{{ __('Strikethrough (~text~)') }}">S</button>
                        <button type="button" @click="applyFormat('`')" class="px-2 py-0.5 text-xs font-mono bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300 transition-colors" title="{{ __('Code (`text`)') }}">&lt;/&gt;</button>
                    </div>

                    <textarea x-model="msgBody" @keydown.enter="if (!$event.shiftKey && !showQR) { $event.preventDefault(); handleSubmit(); }" x-ref="messageInput"
                        @focus="$store.chat.requestLock()" @blur="setTimeout(() => $store.chat.releaseLock(), 500)"
                        @keyup="checkQR(); $store.chat.whisperTyping({{ \Illuminate\Support\Js::from(auth()->user()->name ?? 'Agent') }}); $store.chat.requestLock()"
                        placeholder="{{ __('Type a message (or / for templates)...') }}" rows="1"
                        :disabled="$store.chat.isLockedForMe()" :class="[
                                                        $store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed bg-slate-100' : 'bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-wa-teal/20 group-hover:bg-slate-100 dark:group-hover:bg-slate-700/50',
                                                        isNoteMode ? 'bg-amber-50 dark:bg-amber-900/10 focus:ring-amber-200' : ''
                                                    ]"
                        class="w-full py-4 px-6 border-none rounded-[2rem] text-sm font-medium placeholder-slate-400 dark:placeholder-slate-600 resize-none max-h-40 transition-all"
                        style="min-height: 56px;"></textarea>

                    <!-- Quick Replies Popover -->
                    <div x-show="showQR" @click.away="showQR = false" x-transition x-cloak
                        class="absolute bottom-full left-0 mb-2 w-full bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-50">
                        <div class="max-h-60 overflow-y-auto">
                            <template
                                x-for="qr in quickReplies.filter(q => q.code.toLowerCase().includes(qrFilter) || q.text.toLowerCase().includes(qrFilter))"
                                :key="qr.code">
                                <button type="button" @click="selectQR(qr.text)"
                                    class="w-full text-left px-4 py-3 hover:bg-wa-teal/5 transition-colors border-b border-slate-50 dark:border-slate-800/50 last:border-0 flex flex-col">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                        x-text="'/' + qr.code"></span>
                                    <span class="text-tiny text-slate-500 truncate" x-text="qr.text"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="$store.chat.isLockedForMe()"
                    class="h-14 w-14 flex items-center justify-center text-white rounded-[1.5rem] transition-all group"
                    :class="canSend ? 'bg-wa-teal shadow-wa-teal/20' : 'bg-slate-900 shadow-slate-900/10 hover:scale-105 active:scale-95'">
                    <template x-if="canSend || isNoteMode">
                        <svg class="w-5 h-5 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"
                            :class="_submitting && 'animate-pulse'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </template>
                    <template x-if="!canSend && !isNoteMode">
                        <svg @click.prevent="startRecording()" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </template>
                </button>
            </form>
        </div>
    @else
        <!-- Session Expired View (Bottom Bar Style) -->
        <div
            class="bg-slate-50 dark:bg-slate-900 p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between z-30 relative">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-full text-amber-600 dark:text-amber-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-900 dark:text-white">{{ __('Time limit reached') }}</p>
                    <p class="text-tiny font-bold text-slate-500">{{ __('Wait is over. You can only send templates right now.') }}</p>
                </div>
            </div>

            <button wire:click="$dispatch('openTemplatePicker')"
                class="px-5 py-2.5 bg-wa-teal hover:bg-wa-teal/90 text-white font-black uppercase tracking-widest text-tiny rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Start Conversation') }}
            </button>
        </div>
    @endif

    <!-- Modals using x-app-modal component -->

    <!-- New Sub-components -->
    <livewire:chat.template-picker :conversationId="$conversationId" />
    <livewire:chat.ai-assistant :conversationId="$conversationId" />


    <!-- Interactive Buttons Modal -->
    <x-app-modal wire:model="showInteractiveButtonsModal" maxWidth="2xl" :backdropClose="false">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ __('Quick') }} <span
                    class="text-wa-teal">{{ __('Buttons') }}</span></h2>
        </div>


        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <textarea wire:model="buttonBody" rows="4"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm"
                    placeholder="{{ __('Enter your message...') }}"></textarea>

                <div class="space-y-2">
                    <label class="text-tiny font-black text-slate-400 uppercase">{{ __('Buttons') }}
                        ({{ count($interactiveButtons) }}/3)</label>
                    @foreach($interactiveButtons as $index => $btn)
                        <div class="flex gap-2">
                            <input type="text" wire:model="interactiveButtons.{{ $index }}"
                                class="flex-1 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm px-4 py-2"
                                placeholder="{{ __('Button Title') }}">
                            <button wire:click="removeInteractiveButton({{ $index }})"
                                class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                    @if(count($interactiveButtons) < 3)
                        <button wire:click="addInteractiveButton" class="text-xs font-bold text-wa-teal">+ {{ __('Add Button') }}</button>
                    @endif
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-2xl flex items-center justify-center">
                <div class="w-full max-w-[240px] bg-white dark:bg-wa-dark-surface rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-3 border-b border-slate-50 dark:border-slate-800/50">
                        <p class="text-xs text-slate-700 dark:text-slate-200">{!! $this->previewButtonBody !!}</p>
                    </div>
                    <div class="flex flex-col">
                        @foreach($interactiveButtons as $btn)
                            <div
                                class="py-2.5 px-3 border-b border-slate-50 dark:border-slate-800/50 text-center last:border-0">
                                <span class="text-xs font-bold text-wa-teal">{{ $btn ?: __('Button') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div
            class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3">
            <button wire:click="$set('showInteractiveButtonsModal', false)"
                class="px-6 py-3 text-slate-500 font-bold uppercase text-xs">Cancel</button>
            <button wire:click="sendInteractiveButtons"
                class="px-8 py-3 bg-wa-teal text-white font-black uppercase text-xs rounded-xl hover:shadow-lg transition-all">{{ __('Send Buttons') }}</button>
        </div>
    </x-app-modal>

    <!-- Template Preview Modal -->
    <x-app-modal wire:model="showTemplatePreviewModal" maxWidth="2xl" :backdropClose="false">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-tiny font-black text-wa-teal uppercase tracking-[0.2em] mb-1">{{ __('Preview') }}</span>
                <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ __('Edit') }} <span
                        class="text-wa-teal">{{ __('Template') }}</span></h2>
            </div>
        </div>



        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                @if(!empty($templateVariables))
                    <div class="space-y-4">
                        <label
                            class="text-tiny font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ __('Details') }}
                        </label>
                        @foreach($templateVariables as $key => $value)
                            <div class="space-y-2">
                                <label class="text-tiny font-bold text-slate-500 flex justify-between">
                                    <span>{{ __('PLACEHOLDER') }} {{ $key }}</span>
                                </label>
                                <input type="text" wire:model.live="templateVariables.{{ $key }}"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm px-4 py-3 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                                    placeholder="Enter value for {{ $key }}...">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="h-full flex flex-col items-center justify-center text-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                        <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-bold text-slate-500">{{ __('No variables required for this template.') }}</p>
                    </div>
                @endif
            </div>

            <div
                class="bg-slate-50 dark:bg-slate-950 p-6 rounded-[2.5rem] flex items-center justify-center border border-slate-100 dark:border-slate-800">
                <div
                    class="w-full max-w-[240px] bg-white dark:bg-wa-dark-surface rounded-2xl shadow-xl overflow-hidden border border-slate-100 dark:border-slate-800 relative">
                    <!-- Preview Tag -->
                    <div
                        class="absolute top-2 right-2 px-1.5 py-0.5 bg-wa-teal/10 text-wa-teal text-micro font-black uppercase tracking-widest rounded-md">
                        {{ __('Preview') }}</div>

                    <div class="p-4 pt-8">
                        <p
                            class="text-[13px] text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-tight font-sans">
                            {!! $this->livePreviewText !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3 rounded-b-[2.5rem]">
            <x-app-button variant="ghost" wire:click="closeTemplateModals">Back</x-app-button>
            <x-app-button variant="primary" wire:click="sendTemplateWithVariables">
                <span>{{ __('Send Template') }}</span>
                <x-icon name="plus" class="w-3.5 h-3.5 group-hover:rotate-45" />
            </x-app-button>
        </div>
    </x-app-modal>



    <!-- Lightbox Modal -->
    <x-app-modal wire:model="lightboxOpen" maxWidth="7xl">
        <div class="relative bg-black h-full min-h-[500px] flex items-center justify-center">
            <img :src="lightboxImage"
                class="max-h-[90vh] max-w-[95vw] object-contain shadow-2xl rounded-lg">
        </div>
    </x-app-modal>

    <!-- Forward Message Modal -->
    <x-app-modal wire:model="showForwardModal" maxWidth="md">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ __('Forward Message') }}</h2>
            <p class="text-xs text-slate-500">{{ __('Select conversations to forward to') }}</p>
        </div>
        <div class="p-6 max-h-[50vh] overflow-y-auto">
            @if(count($forwardRecentConversations) > 0)
                <div class="space-y-2">
                    @foreach($forwardRecentConversations as $conv)
                        <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <input type="checkbox" wire:model="forwardSelectedConversations" value="{{ $conv->id }}" class="w-5 h-5 rounded border-slate-300 text-wa-teal focus:ring-wa-teal dark:border-slate-600 dark:bg-slate-900 bg-white">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">{{ $conv->contact->name ?? $conv->contact->phone_number }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $conv->contact->phone_number }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-sm text-slate-500">{{ __('No recent conversations found.') }}</p>
                </div>
            @endif
        </div>
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3 rounded-b-[2.5rem]">
            <x-app-button variant="ghost" wire:click="$set('showForwardModal', false)">{{ __('Cancel') }}</x-app-button>
            <x-app-button variant="primary" wire:click="forwardMessage">
                <span>{{ __('Forward') }}</span>
                <svg class="w-3.5 h-3.5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            </x-app-button>
        </div>
    </x-app-modal>

    <!-- Keyboard Shortcuts Modal -->
    <div x-show="showShortcutsModal" @click.away="showShortcutsModal = false" x-cloak x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-md p-6 overflow-hidden">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                    </div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ __('Keyboard Shortcuts') }}</h3>
                </div>
                <button type="button" @click="showShortcutsModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="py-4 space-y-3">
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Send Message / Note') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">Ctrl + Enter</kbd>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Switch to Internal Note Mode') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">Alt + N</kbd>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Switch to Customer Message Mode') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">Alt + M</kbd>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Quick Replies Picker') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">/</kbd>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Toggle Shortcuts Help') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">Shift + ?</kbd>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Close Popups / Cancel Reply') }}</span>
                    <kbd class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded text-nano font-mono shadow-sm">Esc</kbd>
                </div>
            </div>
            <div class="pt-2 text-center">
                <button type="button" @click="showShortcutsModal = false" class="px-6 py-2.5 bg-wa-teal text-white text-tiny font-black uppercase tracking-widest rounded-xl hover:opacity-90 transition-all">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>