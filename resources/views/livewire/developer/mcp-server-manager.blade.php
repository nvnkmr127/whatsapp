<div class="max-w-6xl mx-auto space-y-10">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <a href="{{ route('developer.overview') }}"
                class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7"/></svg>
                Developer Portal
            </a>
            <h1 class="font-serif text-4xl text-slate-900 dark:text-white">Connect your AI to WatxIO</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400 max-w-2xl">
                Your workspace has a built-in MCP server. Claude, ChatGPT, or any MCP-compatible agent
                can read contacts, send messages, and manage conversations on your behalf.
            </p>
        </div>
    </div>

    {{-- Step 1: Token --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/60 dark:border-slate-800 p-8">
        <div class="flex items-start gap-5">
            <div class="w-9 h-9 rounded-full bg-[#D97757] text-white flex items-center justify-center font-semibold shrink-0">1</div>
            <div class="flex-1">
                <h2 class="font-serif text-2xl text-slate-900 dark:text-white">Get an API token</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Your AI authenticates with a personal API token. Create one, copy it, and keep it somewhere safe — you'll paste it in the next step.
                </p>
                <a href="{{ route('developer.api-tokens') }}"
                    class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-[#D97757] hover:bg-[#C4633F] text-white text-sm font-medium transition-colors">
                    Create a token
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Step 2: Connect --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/60 dark:border-slate-800 p-8">
        <div class="flex items-start gap-5">
            <div class="w-9 h-9 rounded-full bg-[#D97757] text-white flex items-center justify-center font-semibold shrink-0">2</div>
            <div class="flex-1 min-w-0">
                <h2 class="font-serif text-2xl text-slate-900 dark:text-white">Connect your client</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Pick whichever matches your client — both reach the same server.</p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">

                    {{-- Option A: direct URL --}}
                    <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800 bg-[#FAF9F5] dark:bg-slate-800/30 p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-slate-900 dark:text-white">Remote MCP clients</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[#D97757]/10 text-[#C4633F] dark:text-[#E8956F] font-medium">Simplest</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Claude (web &amp; desktop connectors), the OpenAI API, and most agent frameworks connect straight to a URL. Add this server URL:
                        </p>
                        <div class="relative mt-4">
                            <pre class="text-xs font-mono bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700 rounded-xl px-4 py-3 pr-20 overflow-x-auto text-slate-800 dark:text-slate-200">{{ url('/api/v1/mcp') }}</pre>
                            <button onclick="navigator.clipboard.writeText({{ Js::from(url('/api/v1/mcp')) }}); this.innerText='Copied'; setTimeout(() => this.innerText='Copy', 1500);"
                                class="absolute top-2 right-2 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Copy</button>
                        </div>
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                            Then set this HTTP header, using your token from step 1:
                        </p>
                        <pre class="mt-2 text-xs font-mono bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700 rounded-xl px-4 py-3 overflow-x-auto text-slate-800 dark:text-slate-200">Authorization: Bearer &lt;your token&gt;</pre>
                    </div>

                    {{-- Option B: Claude Desktop config --}}
                    <div class="rounded-2xl border border-slate-200/60 dark:border-slate-800 bg-[#FAF9F5] dark:bg-slate-800/30 p-6">
                        <h3 class="font-medium text-slate-900 dark:text-white">Claude Desktop (config file)</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Paste this into <code class="text-xs bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700 rounded px-1.5 py-0.5">claude_desktop_config.json</code>,
                            replace <code class="text-xs bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700 rounded px-1.5 py-0.5">YOUR_API_TOKEN_HERE</code>, then restart Claude. Requires Node.js.
                        </p>
                        <div class="relative mt-4">
                            <pre class="text-xs font-mono bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700 rounded-xl px-4 py-3 overflow-x-auto text-slate-800 dark:text-slate-200">{{ $mcpConfigTemplate }}</pre>
                            <button onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copied'; setTimeout(() => this.innerText='Copy', 1500);"
                                class="absolute top-2 right-2 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Copy</button>
                        </div>
                        <details class="mt-3">
                            <summary class="text-xs text-slate-500 dark:text-slate-400 cursor-pointer hover:text-slate-700 dark:hover:text-slate-300">Where is the config file?</summary>
                            <ul class="mt-2 text-xs font-mono text-slate-500 dark:text-slate-400 space-y-1 pl-1">
                                <li>Mac &nbsp;&nbsp;&nbsp;&nbsp;~/Library/Application Support/Claude/claude_desktop_config.json</li>
                                <li>Windows &nbsp;%APPDATA%\Claude\claude_desktop_config.json</li>
                            </ul>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Step 3: Try it --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/60 dark:border-slate-800 p-8">
        <div class="flex items-start gap-5">
            <div class="w-9 h-9 rounded-full bg-[#D97757] text-white flex items-center justify-center font-semibold shrink-0">3</div>
            <div class="flex-1">
                <h2 class="font-serif text-2xl text-slate-900 dark:text-white">Try it</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Ask your AI something like <span class="italic text-slate-800 dark:text-slate-200">&ldquo;search my WatxIO contacts for Priya&rdquo;</span>
                    or <span class="italic text-slate-800 dark:text-slate-200">&ldquo;show my WhatsApp dashboard analytics&rdquo;</span>.
                    If the tools below appear in your client, you're connected.
                </p>
            </div>
        </div>
    </section>

    {{-- Tools --}}
    <section x-data="{ q: '' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
            <div>
                <h2 class="font-serif text-2xl text-slate-900 dark:text-white">What your AI can do</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ count($tools) }} tools, scoped to this workspace and your plan limits.</p>
            </div>
            <input type="search" x-model="q" placeholder="Filter tools…"
                class="w-full sm:w-64 rounded-xl border-slate-200/60 dark:border-slate-700 dark:bg-slate-900 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:border-[#D97757] focus:ring-[#D97757]/30">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($tools as $tool)
                <div x-show="!q || ($el.textContent.toLowerCase().includes(q.toLowerCase()))"
                    class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 p-5 hover:border-[#D97757]/40 transition-colors">
                    <h3 class="text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $tool['name'] }}</h3>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $tool['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>
