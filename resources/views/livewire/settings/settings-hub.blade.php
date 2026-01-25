<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                        SYSTEM <span class="text-indigo-600">SETTINGS</span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">Manage your workspace configuration and
                        tools.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- WhatsApp Configuration -->
            <a href="{{ route('teams.whatsapp_config') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-2xl text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">WhatsApp API
                    </h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Connect your Meta Business account, manage
                    phone numbers, and profile.</p>
            </a>

            <!-- AI & Knowledge Base -->
            <a href="{{ route('knowledge-base.index') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">AI Brain</h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Train your AI, manage knowledge base
                    documents, and configure auto-replies.</p>
            </a>

            <!-- Chat Routing -->
            <a href="{{ route('settings.chat-routing') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Chat Routing
                    </h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Configure how chats are assigned to agents
                    and teams.</p>
            </a>

            <!-- Canned Messages -->
            <a href="{{ route('settings.canned-messages') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-2xl text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Quick Replies
                    </h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Manage canned responses for frequently
                    asked questions.</p>
            </a>

            <!-- Categories & Tags -->
            <a href="{{ route('settings.categories') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-rose-100 dark:bg-rose-900/30 rounded-2xl text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Taxonomy</h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Manage contact categories, chat tags, and
                    conversation labels.</p>
            </a>

            <!-- System Configuration -->
            <a href="{{ route('settings.system') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Environment
                    </h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Global system settings, email
                    configuration, and core preferences.</p>
            </a>

            <!-- Compliance & Logs -->
            <a href="{{ route('compliance.index') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-2xl text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Security</h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Compliance registry, audit logs, and data
                    security policies.</p>
            </a>

            <!-- Backups -->
            <a href="{{ route('backups.index') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-cyan-100 dark:bg-cyan-900/30 rounded-2xl text-cyan-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Backups</h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">Manage system snapshots, automated
                    backups, and data restoration.</p>
            </a>

            <!-- Developer tools -->
            <a href="{{ route('developer.overview') }}"
                class="group bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:scale-[1.02] transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white uppercase tracking-tight">Dev Tools</h3>
                </div>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">API Tokens, Webhook management, and
                    technical documentation.</p>
            </a>
        </div>
    </div>
</div>