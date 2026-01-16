<x-app-layout>
    <div class="space-y-8 animate-in fade-in duration-700 max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl">
                    <svg class="w-8 h-8 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        API <span class="text-wa-teal">Documentation</span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium tracking-tight">Programmatic access to the
                        WhatsApp Business ecosystem</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('developer.api-tokens') }}"
                    class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                    API Tokens
                </a>
                <a href="{{ route('developer.overview') }}"
                    class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-white/10 hover:scale-[1.02] active:scale-95 transition-all">
                    Back to Portal
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            {{-- Navigation Sidebar (Desktop) --}}
            <div class="hidden lg:block lg:col-span-1">
                <div class="sticky top-8 space-y-2">
                    <div class="px-4 py-2">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Quick Navigation
                        </h3>
                    </div>
                    <nav class="space-y-1">
                        <a href="#intro"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Introduction</a>
                        <a href="#auth"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Authentication</a>
                        <a href="#contacts"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Contacts
                            API</a>
                        <a href="#messages"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Messaging
                            API</a>
                        <a href="#templates"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Templates
                            API</a>
                        <a href="#outbound-webhooks"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Outbound
                            Webhooks</a>
                        <a href="#inbound-sources"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Inbound
                            Webhook Sources</a>
                    </nav>
                </div>
            </div>

            {{-- Main Documentation Content --}}
            <div class="lg:col-span-3 space-y-8">

                {{-- Introduction --}}
                <div id="intro"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-6 uppercase tracking-tight">
                        WhatsApp <span class="text-wa-teal">Business API</span></h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8 font-medium">
                        Our RESTful API allows you to programmatically manage contacts, send messages, and integrate
                        WhatsApp messaging into your existing applications, CRMs, and e-commerce platforms.
                    </p>
                    <div
                        class="bg-purple-50/50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800/50 rounded-3xl p-6 text-center">
                        <div class="text-[10px] font-black text-wa-teal uppercase tracking-widest mb-2">Base Endpoint
                        </div>
                        <code class="text-sm font-black text-slate-900 dark:text-white">{{ $baseUrl }}</code>
                    </div>
                </div>

                {{-- Authentication --}}
                <div id="auth"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Secure
                            <span class="text-emerald-500">Authentication</span>
                        </h3>
                    </div>

                    <p class="text-slate-600 dark:text-slate-400 mb-8 font-medium">All API requests require
                        authentication using Bearer tokens. Tokens are managed per user and team.</p>

                    <div class="space-y-4">
                        <div class="bg-slate-900 rounded-3xl p-8 border border-white/5 relative group">
                            <div
                                class="absolute top-6 right-8 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                HTTP Header</div>
                            <pre class="text-xs font-mono text-blue-400 leading-relaxed overflow-x-auto"
                                id="auth-code">Authorization: Bearer YOUR_API_TOKEN</pre>
                            <button onclick="copyToClipboard('auth-code', this)"
                                class="absolute bottom-6 right-8 text-[10px] font-black text-emerald-500 hover:text-emerald-400 uppercase tracking-widest transition-colors flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h3m-3-3h3m-3-3h3" />
                                </svg>
                                Copy Header
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest text-center italic">
                            Missing a token? Generate one in the <a href="{{ route('developer.api-tokens') }}"
                                class="text-wa-teal hover:scale-105 inline-block transition-transform underline">Token
                                Manager</a>
                        </p>
                    </div>
                </div>

                {{-- Contacts API --}}
                <div id="contacts"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <div
                        class="p-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Identity
                            & <span class="text-wa-teal">AudienceCenter</span></h3>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mt-2">v1/contacts</p>
                    </div>

                    <div class="p-10 space-y-12">
                        {{-- List --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-3 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-lg text-[10px] font-black uppercase tracking-widest">GET</span>
                                <code class="text-sm font-black text-slate-700 dark:text-slate-300">/contacts</code>
                            </div>
                            <p class="text-sm font-medium text-slate-500 leading-relaxed">Fetch all contacts with active
                                opt-in status and custom attributes.</p>
                            <div class="bg-slate-900 rounded-[2rem] p-6 border border-white/5 relative group">
                                <pre class="text-xs font-mono text-emerald-400 overflow-x-auto leading-relaxed"
                                    id="get-contacts-curl">curl -X GET "{{ $baseUrl }}/contacts" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</pre>
                                <button onclick="copyToClipboard('get-contacts-curl', this)"
                                    class="absolute top-4 right-6 p-2 text-slate-500 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Create --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-widest">POST</span>
                                <code class="text-sm font-black text-slate-700 dark:text-slate-300">/contacts</code>
                            </div>
                            <p class="text-sm font-medium text-slate-500 leading-relaxed">Sync identities with your
                                system. Automatically handles opt-in logging.</p>
                            <div class="bg-slate-900 rounded-[2rem] p-8 border border-white/5 relative">
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">JSON
                                    Body</div>
                                <pre class="text-xs font-mono text-blue-400 overflow-x-auto leading-relaxed"
                                    id="post-contacts-body">{
  "phone_number": "+1234567890",
  "name": "Jane Wilson",
  "email": "jane@company.com",
  "custom_attributes": {
    "tier": "enterprise",
    "referred_by": "partner_a"
  },
  "opt_in": true
}</pre>
                                <button onclick="copyToClipboard('post-contacts-body', this)"
                                    class="absolute top-4 right-6 p-2 text-slate-500 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Messaging API --}}
                <div id="messages"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <div
                        class="p-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Conversational <span class="text-wa-teal">Messaging</span></h3>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mt-2">v1/messages</p>
                    </div>

                    <div class="p-10 space-y-12">
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-widest">POST</span>
                                <code class="text-sm font-black text-slate-700 dark:text-slate-300">/messages</code>
                            </div>
                            <p class="text-sm font-medium text-slate-500 leading-relaxed">Send high-impact text or
                                template messages.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Text --}}
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800">
                                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        Standard Text</h5>
                                    <pre class="text-[11px] font-mono text-slate-700 dark:text-slate-300 leading-relaxed"
                                        id="text-msg-json">{
  "phone_number": "+1...",
  "type": "text",
  "message": "Protocol engaged."
}</pre>
                                </div>
                                {{-- Template --}}
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800">
                                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        Marketing Template</h5>
                                    <pre class="text-[11px] font-mono text-slate-700 dark:text-slate-300 leading-relaxed"
                                        id="template-msg-json">{
  "template_name": "otp_delivery",
  "variables": {
    "code": "8821"
  }
}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Templates API --}}
                <div id="templates"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <div
                        class="p-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Message <span class="text-orange-500">Templates</span></h3>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mt-2">v1/templates</p>
                    </div>

                    <div class="p-10 space-y-12">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-3 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-lg text-[10px] font-black uppercase tracking-widest">GET</span>
                                <code class="text-sm font-black text-slate-700 dark:text-slate-300">/templates</code>
                            </div>
                            <p class="text-sm font-medium text-slate-500 leading-relaxed">Retrieve a list of all
                                approved WhatsApp message templates for your account.</p>
                            <div class="bg-slate-900 rounded-[2rem] p-6 border border-white/5 relative group">
                                <pre class="text-xs font-mono text-emerald-400 overflow-x-auto leading-relaxed"
                                    id="get-templates-curl">curl -X GET "{{ $baseUrl }}/templates" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</pre>
                                <button onclick="copyToClipboard('get-templates-curl', this)"
                                    class="absolute top-4 right-6 p-2 text-slate-500 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inbound Sources --}}
                <div id="inbound-sources"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8">
                    <div
                        class="p-10 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                Advanced <span class="text-rose-500">Inbound Webhooks</span></h3>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Dynamic
                                Integrations</p>
                        </div>
                        <a href="{{ route('webhook-sources.index') }}"
                            class="px-5 py-2 bg-gradient-to-br from-rose-500 to-orange-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-transform shadow-lg shadow-rose-500/20">
                            Manager
                        </a>
                    </div>

                    <div class="p-10 space-y-10">
                        <p class="text-sm font-medium text-slate-500 leading-relaxed">
                            Connect Shopify, Stripe, or your Custom Infrastructure. Each Source generates a unique
                            cryptographic endpoint with visual field mapping logic.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                                <div
                                    class="w-10 h-10 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center text-rose-600 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h4
                                    class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">
                                    Security</h4>
                                <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase">Supports HMAC,
                                    API-KEY, and Basic Auth validation.</p>
                            </div>
                            <div
                                class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4
                                    class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">
                                    Mapping</h4>
                                <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase">Visually map
                                    JSON payloads to WhatsApp Templates.</p>
                            </div>
                            <div
                                class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                                <div
                                    class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4
                                    class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">
                                    Delays</h4>
                                <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase">Configure
                                    human-like processing delays (0-60m).</p>
                            </div>
                        </div>

                        <div class="bg-slate-900 rounded-[2.5rem] p-10 border border-white/5 relative group">
                            <div class="flex items-center gap-3 mb-4">
                                <span
                                    class="px-2 py-0.5 bg-wa-teal text-white rounded text-[8px] font-black uppercase">POST</span>
                                <code class="text-xs font-mono text-blue-300">/api/v1/webhooks/inbound/{slug}</code>
                            </div>
                            <p class="text-xs text-slate-400 font-medium mb-6 italic">Send payloads from external
                                software to this unique endpoint.</p>
                            <pre class="text-xs font-mono text-emerald-400 leading-relaxed overflow-x-auto"
                                id="webhook-source-example">curl -X POST "{{ $baseUrl }}/webhooks/inbound/shopify-orders" \
  -H "X-API-Key: YOUR_SOURCE_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"order_id": "9912", "phone": "1234567890"}'</pre>
                            <button onclick="copyToClipboard('webhook-source-example', this)"
                                class="mt-6 text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                                Copy cURL
                            </button>
                        </div>
                    </div>
                </div>

                {{-- WhatsApp Webhook --}}
                <div id="outbound-webhooks"
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-purple-500/5 blur-[100px] -z-0"></div>
                    <h3
                        class="text-xl font-black text-slate-900 dark:text-white mb-6 uppercase tracking-tight relative z-10">
                        WhatsApp <span class="text-wa-teal">Meta Webhook</span></h3>
                    <p
                        class="text-slate-600 dark:text-slate-400 text-sm mb-6 font-medium relative z-10 leading-relaxed">
                        Configure this URL in your <strong>Meta Business Manager</strong> (App Settings -> WhatsApp ->
                        Configuration) to receive real-time events from Meta.
                    </p>

                    <div
                        class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800 relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Callback
                                URL</span>
                            <button onclick="copyToClipboard('whatsapp-webhook-raw', this)"
                                class="text-[10px] font-black text-wa-teal hover:underline uppercase tracking-widest">Copy
                                URL</button>
                        </div>
                        <code class="text-sm font-black text-slate-900 dark:text-white"
                            id="whatsapp-webhook-raw">{{ $webhookUrl }}</code>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId, btn) {
            const element = document.getElementById(elementId);
            const text = element.innerText;

            navigator.clipboard.writeText(text).then(() => {
                const originalContent = btn.innerHTML;

                if (btn.tagName === 'BUTTON') {
                    if (btn.innerText.includes('Copy') || btn.innerText.includes('COPIED')) {
                        const oldText = btn.innerText;
                        btn.innerText = 'COPIED!';
                        setTimeout(() => {
                            btn.innerText = oldText;
                        }, 2000);
                    } else {
                        btn.innerHTML =
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                        setTimeout(() => {
                            btn.innerHTML = originalContent;
                        }, 2000);
                    }
                }
            });
        }
    </script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
        }
    </style>
</x-app-layout>