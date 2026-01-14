<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    DEVELOPER <span class="text-purple-500">PORTAL</span>
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
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-xl text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Workflows</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['webhook_workflows'] }}</div>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('profile.show') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-purple-200 dark:hover:border-purple-800 transition-all">
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

        <a href="{{ route('developer.webhooks') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-purple-200 dark:hover:border-purple-800 transition-all">
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

        <a href="{{ route('developer.docs') }}"
            class="group bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-purple-200 dark:hover:border-purple-800 transition-all">
            <div class="flex items-center gap-4 mb-4">
                <div
                    class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl text-purple-600 group-hover:scale-110 transition-transform">
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
    </div>
</div>