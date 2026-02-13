<div class="flex-1 flex flex-col h-full relative bg-slate-200 dark:bg-[#0b141a] overflow-hidden"
    x-data="(() => {
        let data = chatWindow(
            $wire, 
            '{{ $conversation?->id }}', 
            '{{ $conversation?->team_id }}', 
            '{{ auth()->id() }}'
        );
        data.showTemplateListModal = @entangle('showTemplateListModal');
        data.showInteractiveButtonsModal = @entangle('showInteractiveButtonsModal');
        return data;
    })()">

    <!-- Floating Date Header -->
    <div class="absolute top-20 left-1/2 -translate-x-1/2 z-10 pointer-events-none transition-opacity duration-300"
        :class="scrollTop > 50 ? 'opacity-100' : 'opacity-0'">
        <span
            class="bg-slate-100/90 dark:bg-slate-800/90 backdrop-blur-sm text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm border border-slate-200 dark:border-slate-700">
            Today
        </span>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 overflow-y-auto custom-scrollbar relative" x-ref="chatContainer" @scroll.passive="handleScroll">

        <div class="min-h-full px-4 sm:px-12 py-6 space-y-1 relative"
            :style="'padding-top: ' + renderConfig.top + 'px; padding-bottom: ' + renderConfig.bottom + 'px'">

            <!-- Loading Indicator -->
            <div x-show="$store.chat.loading" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-wa-teal"></div>
            </div>

            <!-- Messages Loop -->
            <template x-for="(message, index) in visibleMessages" :key="message.id">
                <x-chat.bubble />
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
        <!-- File Preview -->
        @if($newAttachment)
            <div
                class="absolute bottom-24 left-4 right-4 z-40 mb-4 p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-between animate-in slide-in-from-bottom-2 shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    @if(Str::startsWith($newAttachment->getMimeType(), 'image'))
                        <img src="{{ $newAttachment->temporaryUrl() }}" class="h-12 w-12 rounded-lg object-cover">
                    @else
                        <div class="h-12 w-12 bg-white dark:bg-slate-700 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                            {{ $newAttachment->getClientOriginalName() }}
                        </p>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-1 rounded-full mt-1 overflow-hidden"
                            wire:loading.class="animate-pulse">
                            <div class="bg-wa-teal h-full transition-all duration-300" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <button wire:click="deleteAttachment"
                    class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full text-slate-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

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
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-100">Live Recording</span>
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
                        class="px-8 py-3 bg-white text-rose-600 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg hover:scale-105 active:scale-95 transition-all">
                        Send Note
                    </button>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="flex-1 flex items-center gap-2 relative">
                <!-- Hidden File Input -->
                <input type="file" wire:model="newAttachment" class="hidden" x-ref="fileInput"
                    x-on:livewire-upload-error="uploadError = 'File upload failed.'; showUploadErrorModal = true;"
                    accept="image/*,video/*,audio/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">

                <!-- Attach Button -->
                <div class="relative">
                    <button type="button" @click="if(!$store.chat.isLockedForMe()) showAttach = !showAttach"
                        :disabled="$store.chat.isLockedForMe()"
                        :class="$store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed' : 'hover:text-wa-teal hover:bg-wa-teal/5'"
                        class="p-3 text-slate-400 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>

                    <!-- Attach Menu -->
                    <div x-show="showAttach" @click.away="showAttach = false" x-cloak
                        class="absolute bottom-full left-0 mb-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-2 overflow-hidden animate-in slide-in-from-bottom-2 z-50">
                        <button type="button" @click="$refs.fileInput.click(); showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div
                                class="h-8 w-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Document/Media</span>
                        </button>

                        <button type="button" wire:click="openInteractiveButtonsModal" @click="showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Quick Buttons</span>
                        </button>

                        <button type="button" @click="isNoteMode = !isNoteMode; showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div class="h-8 w-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Internal Note</span>
                        </button>
                    </div>
                </div>

                <!-- Input Field -->
                <div class="flex-1 relative group">
                    <template x-if="isNoteMode">
                        <div
                            class="absolute -top-6 left-6 px-3 py-0.5 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-400 text-[9px] font-black uppercase tracking-widest rounded-t-lg border-t border-x border-amber-200 dark:border-amber-800">
                            NOTE MODE
                        </div>
                    </template>
                    <textarea x-model="msgBody" @keydown.enter.prevent="handleSubmit" x-ref="messageInput"
                        @focus="$store.chat.requestLock()" @blur="setTimeout(() => $store.chat.releaseLock(), 500)"
                        @keyup="checkQR(); $store.chat.whisperTyping('{{ addslashes(auth()->user()->name ?? 'Agent') }}'); $store.chat.requestLock()"
                        placeholder="Type a message (or / for templates)..." rows="1"
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
                                    <span class="text-[10px] text-slate-500 truncate" x-text="qr.text"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="$store.chat.isLockedForMe()"
                    class="h-14 w-14 flex items-center justify-center text-white rounded-[1.5rem] transition-all group"
                    :class="msgBody.trim() || $wire.newAttachment ? 'bg-wa-teal shadow-wa-teal/20' : 'bg-slate-900 shadow-slate-900/10 hover:scale-105 active:scale-95'"
                    wire:loading.attr="disabled">
                    <template x-if="msgBody.trim() || $wire.newAttachment || isNoteMode">
                        <svg class="w-5 h-5 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </template>
                    <template x-if="!msgBody.trim() && !$wire.newAttachment && !isNoteMode">
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
                    <p class="text-xs font-black uppercase tracking-wide text-slate-900 dark:text-white">Session Expired</p>
                    <p class="text-[10px] font-bold text-slate-500">Service window closed. Use a template.</p>
                </div>
            </div>

            <button wire:click="openTemplateList"
                class="px-5 py-2.5 bg-wa-teal hover:bg-wa-teal/90 text-white font-black uppercase tracking-widest text-[10px] rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Start Conversation
            </button>
        </div>
    @endif

    <!-- Modals using x-modal component -->

    <!-- Template List Modal -->
    <x-modal show="showTemplateListModal" maxWidth="xl">
        <div class="p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Approved <span
                        class="text-wa-teal">Templates</span></h2>
                <button wire:click="closeTemplateModals" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <input type="text" wire:model.live.debounce.300ms="templateSearch" placeholder="Search templates..."
                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold mb-6">

            <div class="space-y-3 max-h-[400px] overflow-y-auto custom-scrollbar">
                @forelse($this->filtered_templates as $template)
                    <button wire:click="selectTemplate({{ $template->id }})"
                        class="w-full text-left p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl hover:bg-wa-teal/5 dark:hover:bg-wa-teal/10 transition-colors group">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3
                                    class="text-sm font-black text-slate-900 dark:text-white group-hover:text-wa-teal transition-colors">
                                    {{ $template->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-500 mt-1">{{ $template->category }} •
                                    {{ $template->language }}</p>
                            </div>
                            <span
                                class="px-2 py-0.5 bg-wa-teal/10 text-wa-teal rounded text-[9px] font-black uppercase">{{ $template->status }}</span>
                        </div>
                    </button>
                @empty
                    <p class="text-center text-slate-500 text-sm py-4">No templates found.</p>
                @endforelse
            </div>
        </div>
    </x-modal>

    <!-- Interactive Buttons Modal -->
    <x-modal show="showInteractiveButtonsModal" maxWidth="2xl">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Quick <span
                    class="text-wa-teal">Buttons</span></h2>
            <button wire:click="$set('showInteractiveButtonsModal', false)" class="text-slate-400 hover:text-rose-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <textarea wire:model="buttonBody" rows="4"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm"
                    placeholder="Enter your message..."></textarea>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase">Buttons
                        ({{ count($interactiveButtons) }}/3)</label>
                    @foreach($interactiveButtons as $index => $btn)
                        <div class="flex gap-2">
                            <input type="text" wire:model="interactiveButtons.{{ $index }}"
                                class="flex-1 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm px-4 py-2"
                                placeholder="Button Title">
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
                        <button wire:click="addInteractiveButton" class="text-xs font-bold text-wa-teal">+ Add
                            Button</button>
                    @endif
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-2xl flex items-center justify-center">
                <div class="w-full max-w-[240px] bg-white dark:bg-[#202c33] rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-3 border-b border-slate-50 dark:border-slate-800/50">
                        <p class="text-xs text-slate-700 dark:text-slate-200">{!! $this->previewButtonBody !!}</p>
                    </div>
                    <div class="flex flex-col">
                        @foreach($interactiveButtons as $btn)
                            <div
                                class="py-2.5 px-3 border-b border-slate-50 dark:border-slate-800/50 text-center last:border-0">
                                <span class="text-xs font-bold text-wa-teal">{{ $btn ?: 'Button' }}</span>
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
                class="px-8 py-3 bg-wa-teal text-white font-black uppercase text-xs rounded-xl hover:shadow-lg transition-all">Send
                Buttons</button>
        </div>
    </x-modal>
    <!-- Template Preview Modal -->
    <x-modal show="showTemplatePreviewModal" maxWidth="2xl">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] font-black text-wa-teal uppercase tracking-[0.2em] mb-1">Personalize</span>
                <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Template <span
                        class="text-wa-teal">Preview</span></h2>
            </div>
            <button wire:click="closeTemplateModals" class="text-slate-400 hover:text-rose-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                @if(!empty($templateVariables))
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Content Variables
                        </label>
                        @foreach($templateVariables as $key => $value)
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 flex justify-between">
                                    <span>PLACEHOLDER {{ $key }}</span>
                                </label>
                                <input type="text" wire:model.live="templateVariables.{{ $key }}"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm px-4 py-3 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                                    placeholder="Enter value for {{ $key }}...">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                        <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-bold text-slate-500">No variables required for this template.</p>
                    </div>
                @endif
            </div>

            <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-[2.5rem] flex items-center justify-center border border-slate-100 dark:border-slate-800">
                <div class="w-full max-w-[240px] bg-white dark:bg-[#202c33] rounded-2xl shadow-xl overflow-hidden border border-slate-100 dark:border-slate-800 relative">
                     <!-- Preview Tag -->
                     <div class="absolute top-2 right-2 px-1.5 py-0.5 bg-wa-teal/10 text-wa-teal text-[8px] font-black uppercase tracking-widest rounded-md">Preview</div>
                    
                     <div class="p-4 pt-8">
                        <p class="text-[13px] text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-tight font-sans">
                            {!! $this->livePreviewText !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3 rounded-b-3xl">
            <button wire:click="closeTemplateModals"
                class="px-6 py-3 text-slate-500 font-bold uppercase text-xs hover:text-slate-700 transition-colors">Back</button>
            <button wire:click="sendTemplateWithVariables"
                class="px-8 py-3 bg-wa-teal text-white font-black uppercase text-xs rounded-xl hover:shadow-lg hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <span>Send Template</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
    </x-modal>


    <!-- Lightbox Modal -->
    <x-modal show="$store.chat.lightboxOpen" maxWidth="5xl" :closeable="true">
        <div class="relative bg-black h-full min-h-[500px] flex items-center justify-center">
            <button @click="$store.chat.lightboxOpen = false"
                class="absolute top-4 right-4 text-white hover:text-slate-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img :src="$store.chat.lightboxImage"
                class="max-h-[80vh] max-w-[90vw] object-contain shadow-2xl rounded-lg">
        </div>
    </x-modal>

</div>