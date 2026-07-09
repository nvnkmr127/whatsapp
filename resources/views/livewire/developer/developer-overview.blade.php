<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-wa-teal dark:bg-wa-teal/30 rounded-2xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    DEVELOPER <span class="text-wa-teal">PORTAL</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">API Keys, Webhooks, and Integration Tools</p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-xl text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">API Tokens</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['api_tokens'] }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-xl text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Webhooks</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['webhook_subscriptions'] }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-wa-teal dark:bg-wa-teal/30 rounded-xl text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inbound Sources</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['webhook_sources'] }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-xl border border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-xl text-orange-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">7-Day Deliveries</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['recent_deliveries'] }}</div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('developer.api-tokens') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl text-blue-600 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">API Tokens</h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Manage your API authentication tokens</p>
        </a>

        <a href="{{ route('webhook-sources.index') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-fuchsia-100 dark:bg-fuchsia-900/30 rounded-2xl text-fuchsia-600 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Inbound Sources
                </h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Setup webhooks to receive data</p>
        </a>

        <a href="{{ route('developer.webhooks') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-green-100 dark:bg-green-900/30 rounded-2xl text-green-600 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Outbound Webhooks
                </h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Configure event notifications to your systems</p>
        </a>

        <a href="{{ route('webhooks.logs') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-2xl text-amber-600 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Webhook Logs
                </h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Debug inbound webhook payloads</p>
        </a>

        <a href="{{ route('developer.docs') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-wa-teal dark:bg-wa-teal/30 rounded-2xl text-white group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">API Documentation
                </h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Complete API reference and examples</p>
        </a>

        <a href="{{ route('developer.mcp') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-purple-500 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl text-purple-600 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.673.337a6 6 0 01-3.86.517l-2.388-.477a2 2 0 00-1.022.547l-1.162 1.162a2 2 0 00-.547 1.022l-.477 2.387a6 6 0 00.517 3.86l.337.673a6 6 0 01.517 3.86l-.477 2.388a2 2 0 00.547 1.022l1.162 1.162a2 2 0 001.022-.547l2.387-.477a6 6 0 003.86-.517l.673-.337a6 6 0 013.86-.517l2.388.477a2 2 0 001.022-.547l1.162-1.162a2 2 0 00.547-1.022l.477-2.387a6 6 0 00-.517-3.86l-.337-.673a6 6 0 01-.517-3.86l.477-2.388a2 2 0 00-.547-1.022l-1.162-1.162z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">AI Agent MCP Server
                </h3>
            </div>
            <p class="text-sm text-slate-500 font-medium">Connect Claude to your workspace natively</p>
        </a>

        <div
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal dark:hover:border-wa-teal transition-all">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-4">
                    <div
                        class="p-3 {{ auth()->user()->currentTeam->is_sandbox_mode ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} rounded-2xl group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.673.337a6 6 0 01-3.86.517l-2.388-.477a2 2 0 00-1.022.547l-1.162 1.162a2 2 0 00-.547 1.022l-.477 2.387a6 6 0 00.517 3.86l.337.673a6 6 0 01.517 3.86l-.477 2.388a2 2 0 00.547 1.022l1.162 1.162a2 2 0 001.022-.547l2.387-.477a6 6 0 003.86-.517l.673-.337a6 6 0 013.86-.517l2.388.477a2 2 0 001.022-.547l1.162-1.162a2 2 0 00.547-1.022l.477-2.387a6 6 0 00-.517-3.86l-.337-.673a6 6 0 01-.517-3.86l.477-2.388a2 2 0 00-.547-1.022l-1.162-1.162z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Sandbox Mode
                    </h3>
                </div>
                <button wire:click="toggleSandboxMode"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-wa-teal focus:ring-offset-2 {{ auth()->user()->currentTeam->is_sandbox_mode ? 'bg-orange-500' : 'bg-slate-200 dark:bg-slate-700' }}">
                    <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ auth()->user()->currentTeam->is_sandbox_mode ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
            <p class="text-sm text-slate-500 font-medium">Test API calls without real WhatsApp charges</p>
        </div>

        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('developer.teams') }}"
                class="group bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-800 hover:border-indigo-500 transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-indigo-600 rounded-2xl text-white group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m14-10a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white uppercase tracking-tight">Team Explorer
                    </h3>
                </div>
                <p class="text-sm text-slate-400 font-medium">View and manage all workspace details</p>
            </a>
        @endif
    </div>
</div>