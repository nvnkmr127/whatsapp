<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Inbox <span
                        class="text-wa-green">Settings</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Configure automated replies, working hours, and message behavior.</p>
        </div>
        <div class="flex gap-3">
            <x-action-message class="mr-3 flex items-center" on="saved">
                <span class="text-wa-green font-bold text-xs uppercase tracking-widest">{{ __('Changes Saved') }}</span>
            </x-action-message>
            <button wire:click="save"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Save All Settings
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Left Column -->
        <div class="space-y-8">
            <!-- Read Receipts Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Read
                            Receipts</h2>
                        <p class="text-sm text-slate-500 font-medium mt-1">Status visibility for senders</p>
                    </div>
                    <div
                        class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox" name="readReceiptsEnabled" id="readReceiptsEnabled"
                            wire:model="readReceiptsEnabled"
                            class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer peer checked:right-0 checked:border-wa-green" />
                        <label for="readReceiptsEnabled"
                            class="toggle-label block overflow-hidden h-6 rounded-full bg-slate-200 dark:bg-slate-700 cursor-pointer peer-checked:bg-wa-green/20"></label>
                    </div>
                </div>
                <style>
                    .toggle-checkbox:checked {
                        right: 0;
                        border-color: #25D366;
                    }

                    .toggle-checkbox:checked+.toggle-label {
                        background-color: rgba(37, 211, 102, 0.2);
                    }

                    .toggle-checkbox {
                        right: calc(100% - 1.5rem);
                        /* Start at left */
                        transition: all 0.2s ease-in-out;
                    }
                </style>
                <div
                    class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 text-sm text-slate-600 dark:text-slate-400 font-medium">
                    {{ __('Disabling this will prevent others from seeing whether you have read their messages.') }}
                </div>
            </div>

            <!-- AI Assistant Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">AI
                            Assistant</h2>
                        <p class="text-sm text-slate-500 font-medium mt-1">Automated knowledge base replies</p>
                    </div>
                    <!-- Custom Toggle -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="aiAutoReplyEnabled" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-wa-green/20 dark:peer-focus:ring-wa-green/20 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-green">
                        </div>
                    </label>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
                        {{ __('When enabled, the AI Assistant will attempt to answer customer queries based on your knowledge base.') }}
                    </p>
                    @if($aiAutoReplyEnabled)
                        <div
                            class="p-4 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-2xl text-xs font-bold uppercase tracking-wide flex items-start gap-2">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('Ensure your Knowledge Base is configured in the AI Training section for best results.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-8">
            <!-- Automated Messages Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Auto-Replies
                    </h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Welcome & Away messages</p>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <!-- Welcome Message -->
                    <div class="py-6 first:pt-0">
                        <div class="flex items-center justify-between mb-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="welcomeMessageEnabled"
                                    class="w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-green focus:ring-wa-green/20">
                                <span
                                    class="text-sm font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ __('Welcome Message') }}</span>
                            </label>
                        </div>

                        @if($welcomeMessageEnabled)
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex-1 p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl min-h-[60px] flex items-center">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 font-medium line-clamp-2">
                                        {{ $welcomeMessage ?: 'No message configured' }}
                                    </p>
                                </div>
                                <button wire:click="openConfig('welcome')"
                                    class="p-4 bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-wa-teal rounded-2xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Off Hours Message -->
                    <div class="py-6">
                        <div class="flex items-center justify-between mb-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="offHoursMessageEnabled"
                                    class="w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-green focus:ring-wa-green/20">
                                <span
                                    class="text-sm font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ __('Away Message') }}</span>
                            </label>
                        </div>

                        @if($offHoursMessageEnabled)
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex-1 p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl min-h-[60px] flex items-center">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 font-medium line-clamp-2">
                                        {{ $offHoursMessage ?: 'No message configured' }}
                                    </p>
                                </div>
                                <button wire:click="openConfig('off-hours')"
                                    class="p-4 bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-wa-teal rounded-2xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 text-[10px] uppercase font-bold text-slate-400 ml-1">
                                {{ __('Sent when contacted outside business hours below.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Business Hours Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Working Hours
                    </h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Set your weekly availability</p>
                </div>

                <div class="space-y-4">
                    @foreach($days as $day)
                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                            <div class="w-24">
                                <span class="text-sm font-black text-slate-400 uppercase tracking-wider">{{ $day }}</span>
                            </div>

                            <div class="flex items-center gap-4 flex-1 justify-end">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="workingHours.{{ $day }}.enabled"
                                        class="w-4 h-4 rounded border-none bg-slate-200 dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20">
                                </label>

                                @if($workingHours[$day]['enabled'])
                                    <div class="flex items-center gap-2">
                                        <input type="time" wire:model="workingHours.{{ $day }}.open"
                                            class="bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-slate-900 dark:text-white font-bold text-xs p-2 focus:ring-2 focus:ring-wa-teal/20">
                                        <span class="text-slate-300 font-black">-</span>
                                        <input type="time" wire:model="workingHours.{{ $day }}.close"
                                            class="bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-slate-900 dark:text-white font-bold text-xs p-2 focus:ring-2 focus:ring-wa-teal/20">
                                    </div>
                                @else
                                    <div
                                        class="w-[180px] text-center text-xs font-bold text-slate-300 dark:text-slate-600 uppercase tracking-widest">
                                        {{ __('Closed') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Message Configuration Modal -->
    @if($configModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$toggle('configModalOpen')"></div>
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-8 pb-0 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $editingType === 'welcome' ? 'Welcome' : 'Away' }} <span class="text-wa-teal">Message</span>
                    </h2>
                    <button wire:click="$toggle('configModalOpen')"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <p class="text-sm text-slate-500 font-medium">
                        {{ $editingType === 'welcome'
            ? __('Customize the automatic response sent to users when they first contact you.')
            : __('Customize the response sent when you are unavailable.') }}
                    </p>

                    <!-- Message Type Selection -->
                    <div class="grid grid-cols-2 gap-4 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                        <button wire:click="$set('configMsgType', 'template')"
                            class="py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $configMsgType === 'template' ? 'bg-white dark:bg-slate-700 text-wa-teal shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            Template Message
                        </button>
                        <button wire:click="$set('configMsgType', 'regular')"
                            class="py-3 px-4 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $configMsgType === 'regular' ? 'bg-white dark:bg-slate-700 text-wa-teal shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            Custom Message
                        </button>
                    </div>

                    @if($configMsgType === 'regular')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase text-slate-400">Media Type</label>
                                    <select wire:model.live="regularType"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white cursor-pointer">
                                        <option value="text">Text Only</option>
                                        <option value="image">Image</option>
                                        <option value="video">Video</option>
                                        <option value="audio">Audio</option>
                                        <option value="document">Document</option>
                                    </select>
                                </div>

                                @if($regularType !== 'text')
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase text-slate-400">Media URL</label>
                                        <input type="url" wire:model="regularMediaUrl" placeholder="https://..."
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase text-slate-400">Caption (Optional)</label>
                                        <input type="text" wire:model="regularCaption"
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase text-slate-400">Message Text</label>
                                        <textarea wire:model="regularContent" rows="4"
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white resize-none"
                                            placeholder="Type your message here..."></textarea>
                                    </div>
                                @endif
                            </div>

                            <!-- Preview -->
                            <div class="bg-slate-100 dark:bg-slate-800/50 rounded-2xl p-6 flex items-center justify-center">
                                <div
                                    class="bg-white dark:bg-slate-900 p-3 rounded-tr-xl rounded-bl-xl rounded-br-xl shadow-lg w-full max-w-[240px]">
                                    @if($regularType !== 'text')
                                        <div
                                            class="bg-slate-200 dark:bg-slate-800 h-32 w-full mb-2 rounded-lg flex items-center justify-center text-slate-400 text-xs font-black uppercase">
                                            {{ strtoupper($regularType) }} PREVIEW
                                        </div>
                                    @endif
                                    <div class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed p-1">
                                        {{ $regularType === 'text' ? ($regularContent ?: 'Preview message...') : ($regularCaption ?: 'Caption text...') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 text-right mt-1">12:00 PM</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Template Config -->
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400">Select Template</label>
                                @if(count($availableTemplates) > 0)
                                    <select wire:model.live="templateName"
                                        wire:change="$set('templateLanguage', $event.target.selectedOptions[0].getAttribute('data-lang'))"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white cursor-pointer">
                                        <option value="">{{ __('Choose a template...') }}</option>
                                        @foreach($availableTemplates as $tpl)
                                            <option value="{{ $tpl['name'] }}" data-lang="{{ $tpl['language'] }}">
                                                {{ $tpl['name'] }} ({{ $tpl['language'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model="templateName" placeholder="Or type name manually..."
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white mt-2">
                                    <p class="text-[10px] font-bold text-rose-500 uppercase mt-1">No templates found from Meta.</p>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-400">Language Code</label>
                                <input type="text" wire:model="templateLanguage" readonly
                                    class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800/50 border-none rounded-xl text-sm font-bold text-slate-500 cursor-not-allowed">
                            </div>

                            <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl flex gap-3">
                                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Templates must be pre-approved
                                    by Meta. This content cannot be edited here.</p>
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 flex gap-4">
                        <button wire:click="$toggle('configModalOpen')"
                            class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                            Cancel
                        </button>
                        <button wire:click="saveConfig"
                            class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Save Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>