<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/20 text-wa-teal dark:text-indigo-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">AI <span
                        class="text-wa-teal">Settings</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Customize how your AI responds and connects.</p>
        </div>
        <div class="flex gap-3">
            <x-action-message class="mr-3 flex items-center" on="saved">
                <span class="text-wa-teal font-bold text-xs uppercase tracking-widest">{{ __('Changes Saved') }}</span>
            </x-action-message>
            <button wire:click="testConnection" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl border border-slate-100 dark:border-white/5 hover:scale-[1.02] active:scale-95 transition-all">
                <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                <span wire:loading wire:target="testConnection">Testing...</span>
            </button>
            <button wire:click="save"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Save Settings
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- 1. AI Personality & Instructions -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">AI Behavior
                    </h2>
                    <p class="text-sm text-wa-teal font-bold uppercase tracking-wider mt-1">What to say</p>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">AI
                        Type</label>
                    <select wire:model.live="instruction_type"
                        class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white appearance-none">
                        <option value="custom">My Own Instructions</option>
                        <option value="support">Help Desk Agent</option>
                        <option value="sales">Sales Assistant</option>
                        <option value="tutor">Friendly Teacher</option>
                    </select>
                </div>

                <div>
                    <label
                        class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Instructions
                        (AI Personality)</label>
                    <textarea wire:model="ai_persona" rows="8"
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-[1.5rem] text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white resize-none leading-relaxed"
                        placeholder="Define how the AI should behave..."></textarea>
                    <p class="mt-2 text-[10px] uppercase font-bold text-slate-400 text-right">Visible only to the model
                    </p>
                    <x-input-error for="ai_persona" />
                </div>
            </div>
        </div>

        <!-- 2. Model Parameters & API -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">AI Service
                    </h2>
                    <p class="text-sm text-amber-500 font-bold uppercase tracking-wider mt-1">Connection</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">OpenAI API
                        Key</label>
                    <x-input wire:model="openai_api_key" type="password"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl py-4 px-6 font-mono text-xs text-slate-900 dark:text-white"
                        placeholder="sk-..." />
                    <x-input-error for="openai_api_key" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">AI
                            Model</label>
                        <select wire:model="openai_model"
                            class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-xs font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white">
                            <option value="gpt-4o">gpt-4o</option>
                            <option value="gpt-4-turbo">gpt-4 Turbo</option>
                            <option value="gpt-3.5-turbo">gpt-3.5 Turbo</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Creativity</label>
                        <div class="flex items-center gap-4 px-4 py-3.5 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                            <input type="range" wire:model.live="temperature" min="0" max="1" step="0.1"
                                class="flex-1 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-wa-teal">
                            <span class="text-xs font-black text-wa-teal">{{ $this->creativityLevel }}
                                ({{ $temperature }})</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Try Again on
                            Error</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_retry" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal">
                            </div>
                        </label>
                    </div>
                    @if($show_retry)
                        <select wire:model="retry_attempts"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white mt-4">
                            <option value="1">1 Retry Attempt</option>
                            <option value="2">2 Retry Attempts</option>
                            <option value="3">3 Retry Attempts</option>
                        </select>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Advanced Assistant Settings -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Extra Text
                    </h2>
                    <p class="text-sm text-purple-500 font-bold uppercase tracking-wider mt-1">Add messages</p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-7 1h.01m-.01 4h.01" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Header Toggle -->
                <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Add to Start of
                                Message</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_header" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600">
                            </div>
                        </label>
                    </div>
                    @if($show_header)
                        <input type="text" wire:model="header_message" placeholder="Prefix every response..."
                            class="w-full mt-4 px-4 py-3 bg-white dark:bg-slate-800 border-none rounded-xl text-xs font-medium text-slate-900 dark:text-white">
                    @endif
                </div>

                <!-- Footer Toggle -->
                <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Add to End of
                                Message</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_footer" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600">
                            </div>
                        </label>
                    </div>
                    @if($show_footer)
                        <input type="text" wire:model="footer_message" placeholder="Suffix every response..."
                            class="w-full mt-4 px-4 py-3 bg-white dark:bg-slate-800 border-none rounded-xl text-xs font-medium text-slate-900 dark:text-white">
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Error Handling & Guardrails -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Safety</h2>
                    <p class="text-sm text-rose-500 font-bold uppercase tracking-wider mt-1">Block and Errors</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Stop Keywords -->
                <div class="border-b border-slate-50 dark:border-slate-800 pb-6">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Stop
                            Words</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_stop" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-rose-500">
                            </div>
                        </label>
                    </div>
                    @if($show_stop)
                        <input type="text" wire:model="stop_keywords" placeholder="STOP, CANCEL, ESCAPE"
                            class="w-full mt-4 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white">
                        <p class="mt-2 text-[9px] text-slate-400 font-bold">AI will stop generating if these words appear.
                        </p>
                    @endif
                </div>

                <!-- Fallback Message -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Message if AI
                            Fails</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_fallback" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-rose-500">
                            </div>
                        </label>
                    </div>
                    @if($show_fallback)
                        <textarea wire:model="fallback_message" rows="2"
                            class="w-full mt-4 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-rose-500/20 text-slate-900 dark:text-white resize-none"
                            placeholder="Sent when AI fails after retries..."></textarea>
                    @endif
                </div>
            </div>
        </div>
        <!-- 5. Business Brain (Knowledge Base) Settings -->
        <div
            class="col-span-full bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Business
                        Brain</h2>
                    <p class="text-sm text-wa-teal font-bold uppercase tracking-wider mt-1">Knowledge Retrieval</p>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl text-wa-teal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.989-2.386l-.548-.547z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl">
                    <div class="flex-1">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider mb-1">
                            Enable for Global Assistant</h3>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">Let your AI answer customer
                            questions using your uploaded business documents and URLs.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" wire:model.live="use_kb" class="sr-only peer">
                        <div
                            class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-wa-teal transition-all">
                        </div>
                    </label>
                </div>

                @if($use_kb)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-in fade-in slide-in-from-top-4 duration-300">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Global
                                    Search Scope</label>
                                <div
                                    class="grid grid-cols-2 gap-2 p-1 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    <button type="button" wire:click="$set('kb_scope', 'all')"
                                        class="px-4 py-3 text-[10px] font-black uppercase rounded-xl transition-all {{ $kb_scope === 'all' ? 'bg-white dark:bg-slate-700 shadow-xl text-wa-teal' : 'text-slate-500' }}">
                                        All Sources
                                    </button>
                                    <button type="button" wire:click="$set('kb_scope', 'selected')"
                                        class="px-4 py-3 text-[10px] font-black uppercase rounded-xl transition-all {{ $kb_scope === 'selected' ? 'bg-white dark:bg-slate-700 shadow-xl text-wa-teal' : 'text-slate-500' }}">
                                        Selected Only
                                    </button>
                                </div>
                            </div>

                            <!-- Global Strict Grounding Toggle -->
                            <div
                                class="flex items-center justify-between p-4 bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-100 dark:border-rose-900/30">
                                <div class="flex-1">
                                    <p
                                        class="text-[10px] font-black text-rose-700 dark:text-rose-400 uppercase tracking-tight">
                                        Strict Grounding</p>
                                    <p class="text-[8px] text-rose-600/70 dark:text-rose-400/50 font-bold uppercase">Answers
                                        strictly from knowledge</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model.live="kb_strict" class="sr-only peer">
                                    <div
                                        class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-rose-500">
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if($kb_scope === 'selected')
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Selected
                                    Information Sources</label>
                                <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                    @foreach($available_kb_sources as $source)
                                        <label
                                            class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 border rounded-2xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-all {{ in_array($source['id'], $kb_source_ids) ? 'border-wa-teal/30 bg-wa-teal/5' : 'border-slate-100 dark:border-slate-800' }}">
                                            <input type="checkbox" value="{{ $source['id'] }}" wire:model.live="kb_source_ids"
                                                class="w-5 h-5 text-wa-teal border-slate-300 rounded-lg focus:ring-wa-teal dark:bg-slate-900 dark:border-slate-700">
                                            <div class="flex-1">
                                                <p
                                                    class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-tight">
                                                    {{ $source['name'] }}
                                                </p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span
                                                        class="text-[8px] font-black py-0.5 px-2 bg-slate-200 dark:bg-slate-700 rounded text-slate-500 uppercase tracking-widest">{{ $source['type'] }}</span>
                                                    <span
                                                        class="text-[9px] text-slate-400 font-bold italic">{{ \Carbon\Carbon::parse($source['last_synced_at'])->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                    @if(empty($available_kb_sources))
                                        <div
                                            class="p-8 text-center bg-slate-50 dark:bg-slate-800/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No 'Ready' sources
                                                found.</p>
                                            <a href="{{ route('knowledge-base.index') }}"
                                                class="text-[10px] text-wa-teal font-black uppercase underline mt-2 block tracking-widest">Go
                                                to Business Brain</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-center p-8 bg-wa-teal/5 rounded-3xl border border-wa-teal/10">
                                <div class="text-center">
                                    <p class="text-xs font-black text-wa-teal uppercase tracking-widest">All verified sources
                                        active</p>
                                    <p class="text-[10px] text-slate-500 font-bold mt-1">The AI will use everything currently
                                        marked as 'Ready'.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                class="fixed bottom-8 right-8 z-50 animate-in slide-in-from-right-10 duration-500">
                <div
                    class="bg-wa-teal text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4 border border-white/20 backdrop-blur-xl">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session()->has('test_success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                class="fixed top-8 right-8 z-50 animate-in slide-in-from-top-10 duration-500">
                <div class="bg-emerald-500 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest">{{ session('test_success') }}</span>
                </div>
            </div>
        @endif

        @if (session()->has('test_error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                class="fixed top-8 right-8 z-50 animate-in slide-in-from-top-10 duration-500">
                <div class="bg-rose-500 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest">{{ session('test_error') }}</span>
                </div>
            </div>
        @endif
    </div>