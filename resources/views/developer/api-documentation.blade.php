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
                        @foreach($sections as $section)
                            <a href="#{{ $section['id'] }}"
                                class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">{{ $section['title'] }}</a>
                        @endforeach
                        <a href="#outbound-webhooks"
                            class="block px-4 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 hover:text-wa-teal rounded-xl transition-all">Outbound Webhooks</a>
                    </nav>
                </div>
            </div>

            {{-- Main Documentation Content --}}
            <div class="lg:col-span-3 space-y-12">

                @foreach ($sections as $section)
                    <div id="{{ $section['id'] }}"
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 scroll-mt-8 relative overflow-hidden">
                        
                        @if ($section['id'] === 'intro')
                             <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-6 uppercase tracking-tight">
                                {{ $section['title'] }}</h3>
                            <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8 font-medium">
                                {{ $section['description'] }}
                            </p>
                            <div
                                class="bg-purple-50/50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800/50 rounded-3xl p-6 text-center">
                                <div class="text-[10px] font-black text-wa-teal uppercase tracking-widest mb-2">Base Endpoint
                                </div>
                                <code class="text-sm font-black text-slate-900 dark:text-white">{{ $baseUrl }}</code>
                            </div>
                        
                        @elseif($section['id'] === 'auth')
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl text-emerald-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {{ $section['title'] }}
                                </h3>
                            </div>

                            <p class="text-slate-600 dark:text-slate-400 mb-8 font-medium">{{ $section['description'] }}</p>

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
                            </div>

                        @elseif(isset($section['methods']))
                            <div class="mb-8">
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $section['title'] }}</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2 mb-8">{{ $section['endpoint'] }}</p>
                            </div>

                            <div class="space-y-12">
                                @foreach($section['methods'] as $index => $method)
                                    <div class="space-y-4 border-t border-slate-50 dark:border-slate-800 pt-8 @if($loop->first) border-0 pt-0 @endif">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="px-3 py-1 {{ $method['verb'] === 'GET' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }} rounded-lg text-[10px] font-black uppercase tracking-widest">{{ $method['verb'] }}</span>
                                            <code class="text-sm font-black text-slate-700 dark:text-slate-300">{{ $method['path'] }}</code>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500 leading-relaxed">{{ $method['description'] }}</p>
                                        
                                        @if(isset($method['curl']))
                                            <div class="bg-slate-900 rounded-[2rem] p-6 border border-white/5 relative group">
                                                <pre class="text-xs font-mono text-emerald-400 overflow-x-auto leading-relaxed"
                                                    id="curl-{{ $section['id'] }}-{{ $index }}">{{ str_replace('{base_url}', $baseUrl, $method['curl']) }}</pre>
                                                <button onclick="copyToClipboard('curl-{{ $section['id'] }}-{{ $index }}', this)"
                                                    class="absolute top-4 right-6 p-2 text-slate-500 hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif

                                        @if(isset($method['body']))
                                            <div class="bg-slate-900 rounded-[2rem] p-8 border border-white/5 relative">
                                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">JSON Body</div>
                                                <pre class="text-xs font-mono text-blue-400 overflow-x-auto leading-relaxed"
                                                    id="body-{{ $section['id'] }}-{{ $index }}">{{ json_encode($method['body'], JSON_PRETTY_PRINT) }}</pre>
                                                <button onclick="copyToClipboard('body-{{ $section['id'] }}-{{ $index }}', this)"
                                                    class="absolute top-4 right-6 p-2 text-slate-500 hover:text-white transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif

                                        @if(isset($method['examples']))
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                @foreach($method['examples'] as $exIndex => $example)
                                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800">
                                                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ $example['label'] }}</h5>
                                                        <pre class="text-[11px] font-mono text-slate-700 dark:text-slate-300 leading-relaxed">{{ json_encode($example['json'], JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                        @elseif($section['id'] === 'inbound-sources')
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $section['title'] }}</h3>
                                    <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mt-2">Dynamic Integrations</p>
                                </div>
                                <a href="{{ route('webhook-sources.index') }}"
                                    class="px-5 py-2 bg-gradient-to-br from-rose-500 to-orange-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-transform shadow-lg shadow-rose-500/20">
                                    Manager
                                </a>
                            </div>

                            <p class="text-sm font-medium text-slate-500 leading-relaxed mb-8">{{ $section['description'] }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                @foreach($section['features'] as $feature)
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 mb-4">
                                            @if($feature['icon'] === 'lock')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            @elseif($feature['icon'] === 'map')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @endif
                                        </div>
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">{{ $feature['title'] }}</h4>
                                        <p class="text-[10px] font-bold text-slate-500 leading-relaxed uppercase">{{ $feature['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                             <div class="bg-slate-900 rounded-[2.5rem] p-10 border border-white/5 relative group">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="px-2 py-0.5 bg-wa-teal text-white rounded text-[8px] font-black uppercase">POST</span>
                                    <code class="text-xs font-mono text-blue-300">{{ $section['example_endpoint'] }}</code>
                                </div>
                                <p class="text-xs text-slate-400 font-medium mb-6 italic">Send payloads from external software to this unique endpoint.</p>
                                <pre class="text-xs font-mono text-emerald-400 leading-relaxed overflow-x-auto"
                                    id="webhook-source-example">{{ str_replace('{base_url}', $baseUrl, $section['example_curl']) }}</pre>
                                <button onclick="copyToClipboard('webhook-source-example', this)"
                                    class="mt-6 text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                    Copy cURL
                                </button>
                            </div>

                        @endif
                    </div>
                @endforeach

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
    <script>
        function copyToClipboard(elementId, button) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="text-wa-teal">Copied!</span>';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
</x-app-layout>