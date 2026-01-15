<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">AI <span
                        class="text-indigo-600">Settings</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Customize how your AI responds and connects.</p>
        </div>
        <div class="flex gap-3">
            <x-action-message class="mr-3 flex items-center" on="saved">
                <span
                    class="text-indigo-600 font-bold text-xs uppercase tracking-widest">{{ __('Changes Saved') }}</span>
            </x-action-message>
            <button wire:click="testConnection" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl border border-slate-100 dark:border-white/5 hover:scale-[1.02] active:scale-95 transition-all">
                <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                <span wire:loading wire:target="testConnection">Testing...</span>
            </button>
            <button wire:click="save"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
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
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">AI Behavior</h2>
                    <p class="text-sm text-indigo-600 font-bold uppercase tracking-wider mt-1">What to say</p>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label
                        class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">AI Type</label>
                    <select wire:model.live="instruction_type"
                        class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white appearance-none">
                        <option value="custom">My Own Instructions</option>
                        <option value="support">Help Desk Agent</option>
                        <option value="sales">Sales Assistant</option>
                        <option value="tutor">Friendly Teacher</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Instructions (AI Personality)</label>
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
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">AI Service</h2>
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
                                class="flex-1 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                            <span class="text-xs font-black text-indigo-600">@if($temperature > 0.7) High @elseif($temperature < 0.3) Low @else Normal @endif ({{ $temperature }})</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Try Again on Error</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="show_retry" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600">
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
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Extra Text</h2>
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
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Add to Start of Message</span>
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
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Add to End of Message</span>
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
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-wider">Message if AI Fails</label>
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
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                class="fixed bottom-8 right-8 z-50 animate-in slide-in-from-right-10 duration-500">
                <div
                    class="bg-indigo-600 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4 border border-white/20 backdrop-blur-xl">
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