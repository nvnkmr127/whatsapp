<div class="max-w-6xl mx-auto space-y-8 pb-16">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a wire:navigate href="{{ route('settings.hub') }}" class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-2xl font-black tracking-tight text-zinc-900 dark:text-white uppercase">
                    External Ticket <span class="text-orange-500">Integration</span>
                </h1>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Push grievance data to external municipal/CRM portals and auto-notify citizens on WhatsApp when tickets are resolved.
            </p>
        </div>
        <div>
            <button wire:click="saveSettings" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-orange-500/20 transition-all flex items-center gap-2">
                <svg wire:loading wire:target="saveSettings" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Save Settings
            </button>
        </div>
    </div>

    <!-- 2 Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Configurations -->
        <div class="lg:col-span-7 space-y-6">

            <!-- 1. Outbound Webhook Section -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-500 flex items-center justify-center font-bold text-sm">
                        1
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider">Outbound Push (To Your External Portal)</h2>
                        <p class="text-xs text-zinc-500">When citizens report issues via bot, we push full data to your system.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Target Endpoint URL (Webhook)
                        </label>
                        <input type="url" wire:model="outboundWebhookUrl"
                            placeholder="https://your-gov-portal.com/api/v1/grievance-intake"
                            class="w-full text-xs font-mono px-4 py-2.5 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Webhook Secret (HMAC-SHA256 Signature)
                            </label>
                            <button wire:click="generateWebhookSecret" type="button" class="text-[11px] font-bold text-orange-500 hover:underline">
                                Generate Secret
                            </button>
                        </div>
                        <input type="text" wire:model="outboundWebhookSecret"
                            placeholder="whsec_..."
                            class="w-full text-xs font-mono px-4 py-2.5 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                        <p class="text-[11px] text-zinc-400 mt-1">Payload header: <code class="text-orange-500">X-Webhook-Signature</code></p>
                    </div>

                    <div class="pt-2 flex items-center justify-between">
                        <button wire:click="testOutboundWebhook" wire:loading.attr="disabled"
                            type="button" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-2">
                            <svg wire:loading.remove wire:target="testOutboundWebhook" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <svg wire:loading wire:target="testOutboundWebhook" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Test Outbound Ping
                        </button>
                    </div>

                    @if($testResult)
                        <div class="p-3 rounded-xl border {{ $testResult['success'] ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400' }} text-xs">
                            <div class="font-bold mb-1">Status Code: {{ $testResult['status'] }}</div>
                            <pre class="font-mono text-[10px] overflow-x-auto">{{ $testResult['body'] }}</pre>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. Inbound Callback Endpoint Section -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-sm">
                        2
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider">Inbound Resolution Callback (From Your System)</h2>
                        <p class="text-xs text-zinc-500">Your portal calls this API when field workers mark an issue resolved.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Callback API Endpoint
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ $callbackEndpoint }}"
                                class="w-full text-xs font-mono px-4 py-2.5 bg-zinc-100 dark:bg-zinc-950/80 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-600 dark:text-zinc-400 cursor-text select-all" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Callback API Key
                            </label>
                            <button wire:click="generateApiKey" type="button" class="text-[11px] font-bold text-orange-500 hover:underline">
                                Re-generate Key
                            </button>
                        </div>
                        <input type="text" wire:model="callbackApiKey"
                            class="w-full text-xs font-mono px-4 py-2.5 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                        <p class="text-[11px] text-zinc-400 mt-1">Pass in header: <code class="text-orange-500">X-Callback-Key: your_key</code></p>
                    </div>

                    <div class="p-3 bg-zinc-50 dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 mb-2">Sample cURL Payload for Your IT Team</div>
                        <pre class="text-[10px] font-mono text-zinc-700 dark:text-zinc-300 overflow-x-auto">curl -X POST "{{ $callbackEndpoint }}" \
  -H "Content-Type: application/json" \
  -H "X-Callback-Key: {{ $callbackApiKey }}" \
  -d '{
    "ticket_number": "{{ $prefix }}-260912-ABCD",
    "status": "resolved",
    "resolution_notes": "Garbage cleared by sanitation truck #12.",
    "resolution_image_url": "https://example.com/proof.jpg"
  }'</pre>
                    </div>
                </div>
            </div>

            <!-- 3. Sector & Categories -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-sm">
                        3
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider">Sector & Taxonomy Settings</h2>
                        <p class="text-xs text-zinc-500">Configure ticket prefix and grievance categories for your sector.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Ticket Prefix (e.g. SAN, GOV, HLT, TCK)
                        </label>
                        <input type="text" wire:model="prefix" maxlength="8"
                            class="w-32 text-xs font-mono uppercase px-4 py-2.5 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Active Categories
                        </label>
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach($categories as $idx => $cat)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-bold rounded-lg">
                                    {{ $cat }}
                                    <button type="button" wire:click="removeCategory({{ $idx }})" class="text-zinc-400 hover:text-rose-500">
                                        &times;
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <input type="text" wire:model="newCategory" wire:keydown.enter.prevent="addCategory"
                                placeholder="Add category (e.g., Dead Animal, Sewage Overflow)..."
                                class="flex-1 text-xs px-4 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                            <button wire:click="addCategory" type="button"
                                class="px-4 py-2 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs font-bold rounded-xl hover:opacity-90">
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Citizen WhatsApp Notification Template & Live Preview -->
        <div class="lg:col-span-5 space-y-6">

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-wa-teal/10 text-wa-teal flex items-center justify-center font-bold text-sm">
                        4
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-zinc-900 dark:text-white uppercase tracking-wider">Citizen WhatsApp Template</h2>
                        <p class="text-xs text-zinc-500">Auto-sent to citizen when their ticket is resolved.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Resolution Message
                        </label>
                        <textarea wire:model.live="resolveMessageTemplate" rows="5"
                            class="w-full text-xs font-mono p-3 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-[10px] font-bold text-zinc-400 self-center mr-1">Insert tags:</span>
                        <button type="button" wire:click="insertTag('name')" class="text-[10px] px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md font-mono text-orange-500 hover:bg-orange-500/10">&#123;&#123;name&#125;&#125;</button>
                        <button type="button" wire:click="insertTag('ticket_number')" class="text-[10px] px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md font-mono text-orange-500 hover:bg-orange-500/10">&#123;&#123;ticket_number&#125;&#125;</button>
                        <button type="button" wire:click="insertTag('category')" class="text-[10px] px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md font-mono text-orange-500 hover:bg-orange-500/10">&#123;&#123;category&#125;&#125;</button>
                        <button type="button" wire:click="insertTag('status')" class="text-[10px] px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md font-mono text-orange-500 hover:bg-orange-500/10">&#123;&#123;status&#125;&#125;</button>
                        <button type="button" wire:click="insertTag('resolution_notes')" class="text-[10px] px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-md font-mono text-orange-500 hover:bg-orange-500/10">&#123;&#123;resolution_notes&#125;&#125;</button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                            Meta-Approved Template Name (Optional, for notifications > 24 Hours)
                        </label>
                        <input type="text" wire:model="resolveTemplateName"
                            placeholder="e.g. ticket_resolution_update"
                            class="w-full text-xs font-mono px-4 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-zinc-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                        <p class="text-[11px] text-zinc-400 mt-1">If resolution takes longer than 24 hours, WhatsApp policy requires a registered Utility Template.</p>
                    </div>

                    <!-- Live WhatsApp Phone Preview -->
                    <div class="pt-4">
                        <div class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2">WhatsApp Preview (Citizen Screen)</div>
                        <div class="w-full bg-[#E5DDD5] dark:bg-[#0b141a] rounded-2xl p-4 border border-zinc-300 dark:border-zinc-800 shadow-inner">
                            <div class="max-w-[85%] bg-white dark:bg-[#1f2c34] text-zinc-800 dark:text-zinc-100 p-3 rounded-2xl rounded-tl-none shadow-sm text-xs space-y-1">
                                <div class="whitespace-pre-line leading-relaxed">
                                    {{ $this->previewMessage }}
                                </div>
                                <div class="text-[9px] text-zinc-400 text-right">
                                    {{ now()->format('h:i A') }} &#10003;&#10003;
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
