<div class="space-y-8" x-data="{ showRaw: @entangle('showRawData') }">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg dark:bg-purple-500/10 dark:text-purple-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Webhook <span
                        class="text-purple-600 dark:text-purple-400">Sources</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Configure webhooks from external platforms - Get unique URL, send
                data, map fields visually</p>
        </div>
        <div>
            <button wire:click="cancelEdit" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-black uppercase tracking-widest text-xs shadow-lg shadow-purple-600/30 hover:scale-105 transition-all">
                + New Source
            </button>
        </div>
    </div>

    {{-- Sources List --}}
    {{-- Sources List Container --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        {{-- Section Header with Toggle --}}
        <div
            class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/10">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Active Webhook
                    Sources</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manage your existing
                    integrations</p>
            </div>
        </div>

        <div id="sources-table" class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Source
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">URL</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-end">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @foreach($sources as $source)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-4">
                                <div class="text-xs font-black text-slate-900 dark:text-white">{{ $source->name }}</div>
                                <span class="text-[10px] text-slate-400 uppercase font-black">{{ $source->platform }}</span>
                            </td>
                            <td class="px-8 py-4">
                                <code
                                    class="text-[10px] font-mono text-purple-600 dark:text-purple-400">{{ Str::limit($source->getWebhookUrl(), 30) }}</code>
                            </td>
                            <td class="px-8 py-4">
                                <button wire:click="toggleStatus({{ $source->id }})" class="group/toggle flex items-center gap-2 focus:outline-none">
                                    <span class="w-2 h-2 rounded-full transition-all duration-300 {{ $source->is_active ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]' : 'bg-slate-300' }}"></span>
                                    <span class="text-[10px] font-black uppercase tracking-widest {{ $source->is_active ? 'text-emerald-500' : 'text-slate-400' }}">
                                        {{ $source->is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-8 py-4 text-end">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="viewLogs({{ $source->id }})"
                                        class="p-2 text-slate-400 hover:text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-950/20 rounded-xl transition-all" title="Live Monitor">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </button>
                                    <button wire:click="edit({{ $source->id }})"
                                        class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/20 rounded-xl transition-all" title="Edit Configuration">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $source->id }})"
                                        wire:confirm="Permanent deletion: Are you sure?"
                                        class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-xl transition-all" title="Delete Source">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">

        {{-- Wizard Progress Header --}}
        <div class="px-8 py-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/5">
            <div class="max-w-3xl mx-auto">
                <div class="flex items-center justify-between mb-4">
                    @php
                        $steps = [
                            1 => ['Identify', 'Source Info'],
                            2 => ['Capture', 'Live Data'],
                            3 => ['Mapping', 'Visual Link'],
                            4 => ['Logic', 'Rules & Launch']
                        ];
                    @endphp

                    @foreach($steps as $stepNum => $step)
                        <div class="flex flex-col items-center gap-2 relative z-10">
                            <div
                                class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm transition-all duration-500 {{ $currentStep >= $stepNum ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/30 scale-110' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                @if($currentStep > $stepNum)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    {{ $stepNum }}
                                @endif
                            </div>
                            <div class="text-center">
                                <div
                                    class="text-[10px] font-black uppercase tracking-tight {{ $currentStep >= $stepNum ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                    {{ $step[0] }}</div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase hidden md:block">{{ $step[1] }}
                                </div>
                            </div>
                        </div>
                        @if($stepNum < 4)
                            <div
                                class="flex-1 h-[2px] mb-6 mx-2 {{ $currentStep > $stepNum ? 'bg-purple-600' : 'bg-slate-100 dark:bg-slate-800' }}">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @if($sources->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $sources->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Form --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    {{ $editingId ? 'Update Source' : 'New Webhook Source' }}
                </h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configure your inbound webhook
                </p>
            </div>
            @if($editingId)
                <button wire:click="cancelEdit"
                    class="text-xs font-bold text-rose-500 uppercase tracking-widest hover:underline">Cancel
                    Editing</button>
            @endif
        </div>

        <div class="p-8 md:p-12 min-h-[400px]">
            <div class="max-w-4xl mx-auto">
                {{-- Step 1: Identify & Secure --}}
                @if($currentStep === 1)
                    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="flex items-center gap-4 mb-8">
                            <div
                                class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    Identify Your Connection</h4>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Basic details and
                                    security setup</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2 group">
                                <x-label value="Connection Name"
                                    class="uppercase text-[10px] tracking-widest font-black text-slate-400 group-focus-within:text-purple-600 transition-colors" />
                                <x-input wire:model="name" type="text"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.5rem] py-4 px-6 font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-inner"
                                    placeholder="e.g. Shopify Store" />
                                <x-input-error for="name" />
                            </div>

                            <div class="space-y-2 group">
                                <x-label value="Platform"
                                    class="uppercase text-[10px] tracking-widest font-black text-slate-400 group-focus-within:text-purple-600 transition-colors" />
                                <select wire:model.live="platform" wire:change="selectPlatform($event.target.value)"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.5rem] py-4 px-6 font-bold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-inner appearance-none cursor-pointer">
                                    @foreach($platforms as $key => $preset)
                                        <option value="{{ $key }}">{{ $preset['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div
                            class="space-y-6 bg-slate-50/50 dark:bg-slate-800/20 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <h5
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Security Settings
                            </h5>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div class="space-y-2 relative">
                                        <x-label value="Authentication"
                                            class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                                        <select wire:model.live="auth_method"
                                            class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-5 font-bold text-sm text-slate-900 dark:text-white focus:border-purple-500/30 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-sm cursor-pointer">
                                            <option value="api_key">API Key</option>
                                            <option value="hmac">HMAC Signature</option>
                                            <option value="basic">Basic Auth</option>
                                            <option value="none">Open (No Auth)</option>
                                        </select>
                                    </div>
                                    @if($auth_method !== 'none')
                                        <div
                                            class="p-4 bg-purple-50/50 dark:bg-purple-900/10 rounded-xl border border-purple-100/50 dark:border-purple-500/10 text-[10px] font-bold text-purple-600/70 uppercase tracking-widest">
                                            @if($auth_method === 'api_key') Recommend including in X-API-Key header @else
                                            Security verification enabled @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-4">
                                    @if($auth_method === 'api_key')
                                        <div class="space-y-2 animate-in fade-in zoom-in duration-300">
                                            <div class="flex items-center justify-between">
                                                <x-label value="API Key"
                                                    class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                                                <button wire:click="generateApiKey" type="button"
                                                    class="text-[10px] font-black text-purple-600 hover:text-purple-700 uppercase tracking-widest">Regenerate</button>
                                            </div>
                                            <div class="relative group">
                                                <x-input wire:model="auth_config.key" type="text"
                                                    class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-5 font-mono text-xs text-slate-900 dark:text-white"
                                                    readonly />
                                                <button
                                                    onclick="navigator.clipboard.writeText('{{ $auth_config['key'] ?? '' }}')"
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-purple-600 transition-colors bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @elseif($auth_method === 'hmac')
                                        <div class="space-y-2 animate-in fade-in zoom-in duration-300">
                                            <div class="flex items-center justify-between">
                                                <x-label value="Shared Secret"
                                                    class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                                                <button wire:click="generateSecret" type="button"
                                                    class="text-[10px] font-black text-purple-600 hover:text-purple-700 uppercase tracking-widest">Regenerate</button>
                                            </div>
                                            <x-input wire:model="auth_config.secret" type="text"
                                                class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-5 font-mono text-xs text-slate-900 dark:text-white" />
                                        </div>
                                    @else
                                        <div
                                            class="h-full flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-6">
                                            <p
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                                No specialized config required</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($editingId)
                            <div
                                class="bg-purple-600 text-white rounded-[2rem] p-8 shadow-2xl shadow-purple-600/30 animate-in slide-in-from-left duration-700">
                                <div class="flex flex-col md:flex-row items-center gap-6">
                                    <div
                                        class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 backdrop-blur-md">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-center md:text-left">
                                        <h5 class="text-xs font-black uppercase tracking-widest opacity-80 mb-1">Your Unique
                                            Webhook URL</h5>
                                        <div class="flex flex-col md:flex-row items-center gap-3">
                                            <code
                                                class="text-sm font-mono bg-black/20 py-2 px-4 rounded-xl flex-1 text-center md:text-left break-all">{{ \App\Models\WebhookSource::find($editingId)?->getWebhookUrl() }}</code>
                                            <button
                                                onclick="navigator.clipboard.writeText('{{ \App\Models\WebhookSource::find($editingId)?->getWebhookUrl() }}')"
                                                class="bg-white text-purple-600 px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-colors shadow-lg shadow-black/10">Copy
                                                URL</button>
                                        </div>
                                        <p class="text-[10px] font-bold opacity-70 mt-3 uppercase tracking-widest">Paste this
                                            URL into your external software and send a test event.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 2: Live Capture --}}
                @if($currentStep === 2)
                    <div class="space-y-12 animate-in fade-in zoom-in duration-500 flex flex-col items-center"
                        wire:poll.2000ms="checkForNewPayload">
                        <div class="text-center space-y-4">
                            <h4 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                Listening for <span class="text-purple-600">Events</span></h4>
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Send a request from your
                                platform to capture the structure</p>
                        </div>

                        <div class="relative w-full max-w-md aspect-square flex items-center justify-center">
                            {{-- Pulse Rings --}}
                            @if($isCapturing)
                                <div class="absolute inset-0 rounded-full bg-purple-500/20 animate-ping"></div>
                                <div
                                    class="absolute inset-4 rounded-full bg-purple-500/10 animate-ping [animation-delay:300ms]">
                                </div>
                            @endif

                            <div
                                class="relative z-10 w-64 h-64 rounded-full bg-white dark:bg-slate-900 shadow-2xl flex flex-col items-center justify-center border-8 border-slate-50 dark:border-slate-800 transition-all duration-700 {{ $isCapturing ? 'border-purple-500/50 scale-110' : '' }}">
                                @if($isCapturing)
                                    <div class="w-16 h-16 text-purple-600 animate-bounce mb-4">
                                        <svg fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" />
                                        </svg>
                                    </div>
                                    <button wire:click="stopCapture"
                                        class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:underline">Stop
                                        Listening</button>
                                @else
                                    <button wire:click="startCapture"
                                        class="w-40 h-40 rounded-full bg-gradient-to-tr from-purple-600 to-purple-400 text-white flex flex-col items-center justify-center gap-2 hover:scale-105 transition-transform shadow-xl shadow-purple-600/30 group">
                                        <svg class="w-12 h-12 group-hover:rotate-12 transition-transform" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-xs font-black uppercase tracking-widest">Start Capture</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div
                            class="w-full max-w-2xl bg-slate-900 rounded-[2rem] p-8 border border-slate-800 shadow-2xl relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-4 opacity-50">
                                <div class="flex gap-2">
                                    <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                </div>
                            </div>
                            <h6 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Live URL Status
                            </h6>
                            <div class="flex items-center justify-between gap-4">
                                <code
                                    class="text-xs font-mono text-purple-400 break-all select-all">{{ \App\Models\WebhookSource::find($editingId)?->getWebhookUrl() }}</code>
                                <span
                                    class="px-3 py-1 bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase rounded-lg border border-emerald-500/20 shrink-0">Ready</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Visual Mapping --}}
                @if($currentStep === 3)
                    <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-500">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Visual Field Mapping</h4>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Connect payload fields to WhatsApp variables</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button @click="showRaw = !showRaw" type="button" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all flex items-center gap-2 shadow-xl shadow-black/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    View Raw Data
                                </button>
                                <button wire:click="refreshMappingContext" type="button" class="p-2.5 bg-white dark:bg-slate-900 text-slate-400 hover:text-purple-600 rounded-xl border border-slate-100 dark:border-slate-800 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Template Selection --}}
                        <div class="space-y-4">
                            <x-label value="WhatsApp Template" class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                            <select wire:model.live="selectedTemplateId"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-3xl py-4 px-6 font-bold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-inner cursor-pointer appearance-none">
                                <option value="">Select template to map...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedTemplateId && !empty($templateParams))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                {{-- Phone Number Mapping (Required) --}}
                                <div class="col-span-full bg-gradient-to-br from-purple-600 to-purple-700 rounded-3xl p-8 shadow-xl relative overflow-hidden">
                                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                                        <div>
                                            <h5 class="text-white font-black uppercase tracking-tight flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                Primary Phone Number
                                            </h5>
                                            <p class="text-purple-100/70 text-[10px] font-bold uppercase tracking-widest mt-1">This contact will receive the message</p>
                                        </div>
                                        <div class="flex-1 max-w-sm">
                                            <select wire:model="field_mappings.phone_number" class="w-full bg-white/10 border-2 border-white/20 rounded-2xl py-3 px-5 font-mono text-xs text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 focus:ring-0 transition-all cursor-pointer">
                                                <option value="" class="text-slate-900">-- Select Phone Field --</option>
                                                @foreach($mappingContext as $key => $value)
                                                    <option value="{{ $key }}" class="text-slate-900">{{ $key }} ({{ Str::limit(is_string($value) ? $value : json_encode($value), 30) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Variables Mapping --}}
                                <div class="col-span-full space-y-4">
                                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Variable Assignments</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @foreach($templateParams as $paramNum)
                                            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:scale-[1.02] transition-all group">
                                                <div class="flex items-center justify-between mb-4">
                                                    <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 flex items-center justify-center font-black text-xs border border-purple-100 dark:border-purple-500/20">
                                                        {{ $paramNum }}
                                                    </span>
                                                    @if(str_starts_with($templateParameters[$paramNum] ?? '', 'STATIC:'))
                                                        <button wire:click="$set('templateParameters.{{ $paramNum }}', '')" class="text-[10px] font-black text-purple-600 hover:underline uppercase tracking-widest">Switch to Dynamic</button>
                                                    @else
                                                        <button wire:click="$set('templateParameters.{{ $paramNum }}', 'STATIC:')" class="text-[10px] font-black text-slate-400 hover:text-purple-600 hover:underline uppercase tracking-widest">Set Static Value</button>
                                                    @endif
                                                </div>

                                                <div class="space-y-4">
                                                    @if(str_starts_with($templateParameters[$paramNum] ?? '', 'STATIC:'))
                                                        <input type="text" 
                                                               value="{{ substr($templateParameters[$paramNum] ?? '', 7) }}"
                                                               @change="$wire.set('templateParameters.{{ $paramNum }}', 'STATIC:' + $event.target.value)"
                                                               class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.25rem] py-3 px-5 font-bold text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 transition-all shadow-inner"
                                                               placeholder="Fixed Text Value..." />
                                                    @else
                                                        <select wire:model="templateParameters.{{ $paramNum }}" class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.25rem] py-3 px-5 font-mono text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 transition-all shadow-inner cursor-pointer">
                                                            <option value="">-- Map to Field --</option>
                                                            @foreach($mappingContext as $key => $value)
                                                                <option value="{{ $key }}">{{ $key }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif

                                                    <select wire:model="transformation_rules.param_{{ $paramNum }}" class="w-full bg-transparent border-t-0 border-x-0 border-b-2 border-slate-100 dark:border-slate-800 focus:border-purple-500 focus:ring-0 text-[10px] font-bold text-slate-400 uppercase tracking-widest cursor-pointer">
                                                        <option value="">No Transformation</option>
                                                        <option value="uppercase">UPPERCASE</option>
                                                        <option value="lowercase">lowercase</option>
                                                        <option value="ucwords">Title Case</option>
                                                        <option value="format_phone">Phone E.164</option>
                                                        <option value="stripe_amount_to_decimal">Stripe (/100)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif($selectedTemplateId)
                            <div class="bg-amber-50 dark:bg-amber-900/10 rounded-3xl p-12 text-center border-2 border-dashed border-amber-200 dark:border-amber-800 animate-pulse">
                                <p class="text-amber-600 dark:text-amber-400 font-black uppercase tracking-widest text-sm">No variables detect in this template</p>
                            </div>
                        @endif

                        {{-- Raw Viewer Component (Alpine) --}}
                        <div x-show="showRaw" x-cloak 
                             class="fixed inset-0 z-[100] flex items-center justify-end p-4 bg-black/40 backdrop-blur-sm"
                             @keydown.escape.window="showRaw = false">
                             <div class="w-full max-w-2xl h-full bg-slate-900 rounded-[3rem] shadow-3xl border border-slate-800 flex flex-col animate-in slide-in-from-right duration-500 overflow-hidden">
                                 <div class="p-8 border-b border-white/5 flex items-center justify-between shrink-0">
                                     <h5 class="text-white font-black uppercase tracking-tight">Raw Payload Inspector</h5>
                                     <button @click="showRaw = false" class="p-2 text-white/50 hover:text-white transition-colors">
                                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                     </button>
                                 </div>
                                 <div class="flex-1 overflow-auto p-8 font-mono text-xs text-purple-400 custom-scrollbar">
                                     <pre class="bg-black/40 p-6 rounded-[2rem]">{{ json_encode($capturedPayload ?: [], JSON_PRETTY_PRINT) }}</pre>
                                 </div>
                                 <div class="p-8 bg-black/40 border-t border-white/5 mt-auto">
                                     <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Showing captured event data in standard JSON format</p>
                                 </div>
                             </div>
                        </div>
                    </div>
                @endif

                {{-- Step 4: Logic & Launch --}}
                @if($currentStep === 4)
                    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h10a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Logic & Launch</h4>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Automation rules and process timing</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            {{-- Conditional Filtering --}}
                            <div class="bg-slate-50 dark:bg-slate-800/20 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between mb-6">
                                    <h5 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">Conditional Sending</h5>
                                    <button wire:click="addFilterRule" type="button" class="text-[10px] font-black text-purple-600 hover:text-purple-700 uppercase tracking-widest flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add Rule
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    @foreach($filtering_rules_ui as $index => $rule)
                                        <div class="flex flex-col md:flex-row gap-4 animate-in slide-in-from-left duration-300">
                                            <div class="flex-1">
                                                <select wire:model="filtering_rules_ui.{{ $index }}.field" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-4 font-mono text-xs text-slate-900 dark:text-white focus:border-purple-500/30 transition-all shadow-sm cursor-pointer">
                                                    <option value="">-- Select Field --</option>
                                                    @foreach($mappingContext as $key => $value)
                                                        <option value="{{ $key }}">{{ $key }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="w-full md:w-40">
                                                <select wire:model="filtering_rules_ui.{{ $index }}.operator" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-4 font-bold text-xs text-slate-900 dark:text-white focus:border-purple-500/30 transition-all shadow-sm cursor-pointer">
                                                    <option value="equals">Equals</option>
                                                    <option value="not_equals">Not Equals</option>
                                                    <option value="contains">Contains</option>
                                                    <option value="not_contains">Not Contains</option>
                                                    <option value="exists">Exists</option>
                                                </select>
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" wire:model="filtering_rules_ui.{{ $index }}.value" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-4 font-bold text-xs text-slate-900 dark:text-white focus:border-purple-500/30 transition-all shadow-sm" placeholder="Value to match..." />
                                            </div>
                                            <button wire:click="removeFilterRule({{ $index }})" class="p-3 text-slate-300 hover:text-rose-500 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                    @if(empty($filtering_rules_ui))
                                        <div class="text-center p-8 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No filters - all requests will be processed</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Process Delay --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2.5rem] p-8 shadow-sm">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <h5 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">Process Delay</h5>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-4">
                                            <input type="number" wire:model="process_delay" class="w-24 bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500/20" />
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Seconds</span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-relaxed">Wait before sending the message (max 3600s)</p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2.5rem] p-8 shadow-sm">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <h5 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">Source Status</h5>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active & Ready</span>
                                        <button wire:click="$toggle('is_active')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-800' }}">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Wizard Action Footer --}}
        <div class="px-8 py-8 border-t border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10 flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if($currentStep > 1)
                    <button wire:click="previousStep" type="button" class="group px-8 py-4 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-xs font-black text-slate-600 dark:text-slate-400 hover:text-purple-600 hover:border-purple-600/30 transition-all uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back
                    </button>
                @endif
                <button wire:click="cancelEdit" type="button" class="text-[10px] font-black text-slate-400 hover:text-rose-500 uppercase tracking-widest transition-colors px-4">Cancel</button>
            </div>

            <div class="flex items-center gap-4">
                @if($currentStep < 4)
                    <button wire:click="nextStep" type="button" class="group px-10 py-4 bg-purple-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-purple-700 transition-all shadow-xl shadow-purple-600/30 flex items-center gap-2">
                        Next
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button wire:click="update" type="button" class="group px-12 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-purple-600/40 flex items-center gap-2">
                        Complete Setup
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Test Modal --}}
    @if($showTestModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-[100] p-4 animate-in fade-in duration-300">
            <div class="bg-white dark:bg-slate-900 rounded-[3rem] max-w-4xl w-full max-h-[90vh] flex flex-col shadow-3xl overflow-hidden border border-slate-100 dark:border-slate-800">
                <div class="px-10 py-8 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">🧪 Test Connection</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Validate your field mappings manually</p>
                    </div>
                </div>

                <div class="p-10 overflow-y-auto space-y-8 flex-1 custom-scrollbar">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sample Payload (JSON)</label>
                        <textarea wire:model="testPayload" rows="10"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[2rem] py-4 px-6 font-mono text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 transition-all shadow-inner"></textarea>
                    </div>

                    @if($testResult)
                        <div class="animate-in slide-in-from-bottom-4 duration-500">
                            @if(isset($testResult['error']))
                                <div class="bg-rose-500 text-white rounded-[2rem] p-6 shadow-xl shadow-rose-500/20">
                                    <h4 class="font-black uppercase tracking-tight mb-1">Configuration Error</h4>
                                    <p class="text-xs font-bold opacity-80 uppercase tracking-widest">{{ $testResult['error'] }}</p>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-emerald-500 text-white rounded-[2rem] p-6 shadow-xl shadow-emerald-500/20">
                                        <h4 class="font-black uppercase tracking-tight mb-1">Mapping Success</h4>
                                        <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Payload matched successfully</p>
                                    </div>
                                    @if(isset($testResult['mapped_data']))
                                        <div class="bg-slate-900 text-purple-400 rounded-[2rem] p-6 shadow-xl border border-slate-800">
                                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-4">Resolved Data</h5>
                                            <pre class="text-[10px] font-mono">{{ json_encode($testResult['mapped_data'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="px-10 py-8 bg-slate-50 dark:bg-slate-800/10 border-t border-slate-50 dark:border-slate-800 flex justify-end gap-4">
                    <button wire:click="$set('showTestModal', false)" class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest px-6">Close</button>
                    <button wire:click="testWebhook" class="px-8 py-4 bg-purple-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-700 shadow-lg shadow-purple-600/30 transition-all">Run Diagnostic</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Logs Monitor Modal --}}
    <x-dialog-modal wire:model.live="showLogsModal" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-orange-500 animate-pulse shadow-lg shadow-orange-500/50"></div>
                    <span class="text-lg font-black uppercase tracking-tight">Live Event Monitor</span>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($recentLogs as $log)
                    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] p-6 shadow-sm group">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-600 text-[10px] font-black uppercase rounded-lg border border-purple-100 dark:border-purple-500/20">
                                {{ $log['event_type'] ?: 'GENERIC_EVENT' }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $log['created_at'] }}</span>
                        </div>
                        <pre class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl text-[10px] font-mono text-slate-600 dark:text-slate-400 overflow-x-auto border border-slate-100 dark:border-slate-800">{{ json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @empty
                    <div class="text-center py-20 bg-slate-50 dark:bg-slate-800/20 rounded-[3rem] border-2 border-dashed border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Waiting for incoming requests...</p>
                    </div>
                @endforelse
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showLogsModal', false)" class="!rounded-2xl !px-8">
                Close Monitor
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>