<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.email-logs.index') }}"
                    class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:scale-105 transition-transform text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Log <span
                            class="text-indigo-600">Details</span></h1>
                    <p class="text-slate-500 font-medium">Delivery ID: #{{ $log->id }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div
                            class="px-8 py-8 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Subject Line
                            </h3>
                            <div class="text-xl font-black text-slate-900 dark:text-white leading-tight">
                                {{ $log->subject }}
                            </div>
                        </div>

                        <div class="p-8 space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                        Recipient</h4>
                                    <div
                                        class="text-sm font-bold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-700 truncate">
                                        {{ $log->recipient }}
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Use
                                        Case</h4>
                                    <span
                                        class="inline-flex items-center px-3 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-wider rounded-lg border border-indigo-100 dark:border-indigo-800/50">
                                        {{ $log->use_case->value }}
                                    </span>
                                </div>
                            </div>

                            @if($log->status === 'failed')
                                <div
                                    class="p-6 bg-rose-50 dark:bg-rose-900/10 rounded-3xl border border-rose-100 dark:border-rose-900/30">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="p-2 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xs font-black text-rose-600 uppercase tracking-widest">Failure
                                            Classification: {{ $log->failure_type }}</h4>
                                    </div>
                                    <div
                                        class="text-sm font-mono text-rose-700 dark:text-rose-400 break-words leading-relaxed">
                                        {{ $log->failure_reason }}
                                    </div>
                                    <div class="mt-4 text-[10px] font-bold text-rose-500 uppercase">
                                        Timestamp: {{ $log->failed_at ? $log->failed_at->format('Y-m-d H:i:s') : 'N/A' }}
                                    </div>
                                </div>
                            @endif

                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                    Metadata Payload</h4>
                                <div class="bg-slate-900 rounded-2xl p-6 overflow-x-auto shadow-inner">
                                    <pre
                                        class="text-indigo-300 font-mono text-xs">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) ?: '{}' }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Details -->
                <div class="space-y-6">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                        <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-6">Delivery Stats
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <span
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Current
                                    Status</span>
                                @if($log->status === 'sent')
                                    <span
                                        class="px-3 py-1 bg-green-50 text-green-600 text-xs font-black uppercase tracking-wider rounded-lg border border-green-100">Sent
                                        Success</span>
                                @elseif($log->status === 'failed')
                                    <span
                                        class="px-3 py-1 bg-rose-50 text-rose-600 text-xs font-black uppercase tracking-wider rounded-lg border border-rose-100">Failed</span>
                                @else
                                    <span
                                        class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-wider rounded-lg border border-slate-200 uppercase">{{ $log->status }}</span>
                                @endif
                            </div>

                            <div class="pt-4 border-t border-slate-50 dark:border-slate-800">
                                <span
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Provider
                                    Context</span>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $log->provider_name ?: 'System Default' }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-medium">Config ID:
                                    #{{ $log->smtp_config_id ?: 'None' }}</div>
                            </div>

                            <div class="pt-4 border-t border-slate-50 dark:border-slate-800 space-y-4">
                                <div>
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Initiated
                                        At</span>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ $log->sent_at->format('M d, Y H:i:s') }}</div>
                                </div>
                                @if($log->delivered_at)
                                    <div>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Delivered
                                            At</span>
                                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            {{ $log->delivered_at->format('M d, Y H:i:s') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($log->template)
                        <div class="bg-indigo-600 rounded-[2.5rem] shadow-xl p-8 text-white">
                            <h3 class="text-xs font-black text-indigo-200 uppercase tracking-widest mb-4">Linked Template
                            </h3>
                            <div class="font-black text-lg mb-2 leading-tight">{{ $log->template->name }}</div>
                            <div class="text-xs text-indigo-200 font-mono mb-6">{{ $log->template->slug }}</div>
                            <a href="{{ route('admin.email-templates.edit', $log->template->id) }}"
                                class="inline-flex items-center text-xs font-black uppercase tracking-widest text-white border-b-2 border-indigo-400 pb-1 hover:border-white transition-all">
                                View Template
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>