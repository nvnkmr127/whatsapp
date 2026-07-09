<div class="space-y-8 animate-in fade-in duration-700">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.673.337a6 6 0 01-3.86.517l-2.388-.477a2 2 0 00-1.022.547l-1.162 1.162a2 2 0 00-.547 1.022l-.477 2.387a6 6 0 00.517 3.86l.337.673a6 6 0 01.517 3.86l-.477 2.388a2 2 0 00.547 1.022l1.162 1.162a2 2 0 001.022-.547l2.387-.477a6 6 0 003.86-.517l.673-.337a6 6 0 013.86-.517l2.388.477a2 2 0 001.022-.547l1.162-1.162a2 2 0 00.547-1.022l.477-2.387a6 6 0 00-.517-3.86l-.337-.673a6 6 0 01-.517-3.86l.477-2.388a2 2 0 00-.547-1.022l-1.162-1.162z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    AI AGENT <span class="text-purple-600">MCP SERVER</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Connect Claude Desktop or any MCP Client directly to your workspace.</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <a href="{{ route('developer.overview') }}"
                class="text-sm font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7" />
                </svg>
                Back to Portal
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Configuration Guide --}}
        <div class="xl:col-span-1 space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Quick Setup</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Connect Claude Desktop in 2 minutes</p>
                </div>
                
                <div class="p-8 space-y-8">
                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-wa-teal text-white flex items-center justify-center font-black shrink-0">1</div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Create an API Token</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">If you don't have one, head over to the API Tokens tab and create a new token. You'll need this to authenticate the connection.</p>
                                <button wire:click="createDedicatedToken" class="mt-3 text-xs font-bold text-wa-teal uppercase tracking-widest hover:text-wa-teal/80">Go to API Tokens →</button>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-wa-teal text-white flex items-center justify-center font-black shrink-0">2</div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Edit Claude Config</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Open your Claude Desktop configuration file.</p>
                                <ul class="text-[10px] font-mono text-slate-400 mt-2 space-y-1">
                                    <li>Mac: ~/Library/Application Support/Claude/claude_desktop_config.json</li>
                                    <li>Windows: %APPDATA%\Claude\claude_desktop_config.json</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-wa-teal text-white flex items-center justify-center font-black shrink-0">3</div>
                            <div class="w-full">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Paste this Configuration</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Replace YOUR_API_TOKEN_HERE with your actual token.</p>
                                
                                <div class="relative group mt-3">
                                    <div class="bg-slate-900 rounded-2xl p-4 border border-slate-800">
                                        <pre class="text-[10px] text-blue-400 font-mono whitespace-pre-wrap overflow-x-auto">{{ $mcpConfigTemplate }}</pre>
                                    </div>
                                    <button 
                                        onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText = 'COPIED!';"
                                        class="absolute top-3 right-3 bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all backdrop-blur-sm">
                                        COPY
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-wa-teal text-white flex items-center justify-center font-black shrink-0">4</div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Restart Claude Desktop</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Once restarted, look for the 🔌 icon in Claude to verify the tools are loaded!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-[2.5rem] p-8 border border-blue-100 dark:border-blue-800/30">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-800/50 text-blue-600 dark:text-blue-400 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">Zero Dependencies</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">
                            Unlike most MCP servers that require Node.js or Python, our server uses a clever trick: we map Claude's Standard I/O directly to our JSON-RPC HTTP endpoint using the native <code>curl</code> command available on all Macs and Windows machines. No installations required!
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tools List --}}
        <div class="xl:col-span-2">
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/10">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Available AI Tools ({{ count($tools) }})</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Capabilities granted to your AI</p>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($tools as $tool)
                            <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-5 border border-slate-100 dark:border-slate-800/50 hover:border-purple-500/30 transition-colors">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ $tool['name'] }}</h4>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $tool['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
