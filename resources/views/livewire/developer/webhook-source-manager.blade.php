<div class="space-y-8" x-data="{ showRaw: @entangle('showRawData') }">


    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2.5 bg-indigo-100 text-wa-teal rounded-2xl dark:bg-indigo-500/10 transition-colors shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Incoming <span
                        class="text-wa-teal dark:wa-teal">Integrations</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Connect external platforms and automate your WhatsApp workflows.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('webhooks.logs') }}" 
                class="px-6 py-3.5 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 rounded-[1.25rem] font-black uppercase tracking-widest text-[10px] hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Master Activity Log
            </a>
            <button wire:click="openNewSource"
                class="px-8 py-3.5 bg-gradient-to-r from-wa-teal to-blue-600 text-white rounded-[1.25rem] font-black uppercase tracking-widest text-xs shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                + Build New Integration
            </button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Sources -->
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-indigo-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Total Sources</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($this->stats['total']) }}</div>
                <p class="mt-4 text-[10px] font-bold text-indigo-500 uppercase tracking-widest">{{ number_format($this->stats['active']) }} active currently</p>
            </div>
        </div>

        <!-- Total Received -->
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-wa-teal">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Payloads Received</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($this->stats['total_received']) }}</div>
                <p class="mt-4 text-[10px] font-bold text-wa-teal uppercase tracking-widest">Inbound messages</p>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-emerald-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Process Rate</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['success_rate'] }}%</div>
                <div class="mt-4 w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $this->stats['success_rate'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Quick Export Failed -->
        <button wire:click="exportFailedWebhookReport" class="bg-gradient-to-br from-rose-500 to-rose-600 p-8 rounded-[2.5rem] shadow-xl shadow-rose-500/20 relative overflow-hidden group text-left hover:scale-[1.02] transition-transform">
            <div class="absolute top-0 right-0 p-6 opacity-20 group-hover:scale-110 transition-transform text-white">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-white/70 mb-2">Failure Audit</h3>
                <div class="text-2xl font-black text-white italic">Download Now</div>
                <p class="mt-4 text-[10px] font-bold text-white uppercase tracking-widest flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Failed Payloads
                </p>
            </div>
        </button>
    </div>

    {{-- Sources List Container --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        {{-- Section Header with Filters --}}
        <div class="px-8 py-8 border-b border-slate-50 dark:border-slate-800 flex flex-col xl:flex-row xl:items-center justify-between bg-slate-50/50 dark:bg-slate-800/10 gap-6">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Integration Pipeline</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active nodes and delivery performance</p>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-3">
                <div class="relative group w-full md:w-64">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-[11px] font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-wa-teal focus:border-transparent tracking-tight transition-all shadow-sm"
                        placeholder="Search sources...">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <select wire:model.live="platformFilter"
                        class="px-4 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-[11px] font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal transition-all shadow-sm cursor-pointer">
                        <option value="">All Platforms</option>
                        @foreach($platforms as $key => $preset)
                            <option value="{{ $key }}">{{ $preset['name'] }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="statusFilter"
                        class="px-4 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-[11px] font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal transition-all shadow-sm cursor-pointer">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Paused</option>
                    </select>

                    <button wire:click="resetFilters"
                        class="p-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border-none rounded-2xl text-slate-500 transition-all flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="sources-table" class="overflow-x-auto">
            <div class="max-h-[40rem] overflow-y-auto custom-scrollbar">
            <table class="w-full min-w-[1220px] text-left border-separate border-spacing-0">
                <thead>
                    <tr class="bg-slate-50/30 dark:bg-slate-800/5">
                        <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800">Source Identity</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800">Team</th>
                        @endif
                        <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800">Endpoint URL</th>
                        <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800">Status</th>
                        <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800">Performance Metrics</th>
                        <th class="sticky top-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 text-end">Control Center</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($sources as $source)
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-indigo-500/[0.02] transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                        {{-- Dynamic icon based on platform --}}
                                        @if($source->platform === 'shopify')
                                            <svg class="w-5 h-5 text-emerald-500" viewBox="0 0 24 24" fill="currentColor"><path d="M19.1 12c0-3.9-3.2-7.1-7.1-7.1h-4v14.2h1.4v-4.3c.8.9 2.1 1.4 3.5 1.4 3.4 0 6.2-2.3 6.2-4.2zm-7.1 2.8c-1 0-1.8-.4-2.4-.9V10.1c.6-.5 1.4-.9 2.4-.9 2.1 0 3.8 1.4 3.8 2.8s-1.7 2.8-3.8 2.8z"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-[13px] font-black text-slate-900 dark:text-white leading-none mb-1.5">{{ $source->name }}</div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] text-wa-teal uppercase font-black tracking-widest px-1.5 py-0.5 bg-wa-teal/10 rounded">{{ $source->platform }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">ID: #{{ str_pad($source->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            @if(auth()->user()->isSuperAdmin())
                                <td class="px-8 py-6">
                                    <div class="inline-flex items-center px-2 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-tight">
                                        {{ $source->team?->name ?? 'SYSTEM' }}
                                    </div>
                                </td>
                            @endif
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <code class="text-[10px] font-mono text-slate-500 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-800">
                                        {{ Str::limit($source->slug ?: $source->id, 15) }}
                                    </code>
                                    <button onclick="navigator.clipboard.writeText('{{ $source->getWebhookUrl() }}')" class="p-1 text-slate-300 hover:text-wa-teal transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <button wire:click="toggleStatus({{ $source->id }})" class="relative inline-flex items-center cursor-pointer group/status">
                                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full transition-all duration-300 {{ $source->is_active ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                        <span class="w-2 h-2 rounded-full {{ $source->is_active ? 'bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-400' }}"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ $source->is_active ? 'Active' : 'Paused' }}</span>
                                    </div>
                                </button>
                            </td>
                            <td class="px-8 py-6">
                                @php
                                    $attempted = (int) ($source->msg_attempted_count ?? 0);
                                    $sent = (int) ($source->msg_sent_count ?? 0);
                                    $delivered = (int) ($source->msg_delivered_count ?? 0);
                                    $read = (int) ($source->msg_read_count ?? 0);
                                    $failed = (int) ($source->msg_failed_count ?? 0);
                                    $deliveryRate = $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0;
                                    $readRate = $delivered > 0 ? round(($read / $delivered) * 100, 1) : 0;
                                @endphp
                                <div class="flex items-center gap-6">
                                    <div class="flex -space-x-1.5">
                                        <div class="w-2.5 h-8 bg-indigo-500/20 rounded-full overflow-hidden relative" title="Sent: {{ $sent }}">
                                            <div class="absolute bottom-0 left-0 right-0 bg-indigo-500 transition-all duration-500" style="height: {{ $attempted > 0 ? ($sent/$attempted)*100 : 0 }}%"></div>
                                        </div>
                                        <div class="w-2.5 h-8 bg-emerald-500/20 rounded-full overflow-hidden relative" title="Read: {{ $read }}">
                                            <div class="absolute bottom-0 left-0 right-0 bg-emerald-500 transition-all duration-500" style="height: {{ $attempted > 0 ? ($read/$attempted)*100 : 0 }}%"></div>
                                        </div>
                                        <div class="w-2.5 h-8 bg-rose-500/20 rounded-full overflow-hidden relative" title="Failed: {{ $failed }}">
                                            <div class="absolute bottom-0 left-0 right-0 bg-rose-500 transition-all duration-500" style="height: {{ $attempted > 0 ? ($failed/$attempted)*100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-[14px] font-black text-slate-900 dark:text-white leading-none">{{ number_format($attempted) }} <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest ml-1">Total Hits</span></div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black text-emerald-500">{{ $deliveryRate }}% <span class="text-[8px] opacity-70">DLVR</span></span>
                                            <span class="text-[10px] font-black text-blue-500">{{ $readRate }}% <span class="text-[8px] opacity-70">READ</span></span>
                                        </div>
                                    </div>
                                    <button wire:click="openSourceReportModal({{ $source->id }})" class="p-2 bg-slate-50 dark:bg-slate-800 hover:bg-wa-teal hover:text-white rounded-xl text-slate-400 transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-end">
                                <div class="flex items-center justify-end gap-1 px-2 py-1 bg-slate-50/50 dark:bg-slate-800/30 rounded-[1.25rem] w-fit ml-auto border border-slate-100 dark:border-slate-800/50 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                                    <button wire:click="viewLogs({{ $source->id }})" class="p-2 text-slate-400 hover:text-orange-500 transition-all" title="Live Terminal">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </button>
                                    <button wire:click="duplicate({{ $source->id }})" class="p-2 text-slate-400 hover:text-indigo-500 transition-all" title="Clone Node">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                    </button>
                                    <button wire:click="edit({{ $source->id }})" class="p-2 text-slate-400 hover:text-wa-teal transition-all" title="Configure">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button wire:click="delete({{ $source->id }})" wire:confirm="Permanent deletion: Are you sure?" class="p-2 text-slate-400 hover:text-rose-500 transition-all" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No active integrations found</p>
                                    <button wire:click="openNewSource" class="text-xs font-black text-wa-teal hover:underline uppercase tracking-widest">Construct your first pipe</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @if($sources->hasPages())
            <div class="px-8 py-5 border-t border-slate-50 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                    Showing {{ $sources->firstItem() ?? 0 }}-{{ $sources->lastItem() ?? 0 }} of {{ $sources->total() }} sources
                </div>
                <div>
                    {{ $sources->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
        </div>
    </div>

    <x-dialog-modal wire:model.live="showWizardModal" maxWidth="5xl">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div>
                    {{-- Breadcrumb Navigation --}}
                    <div class="flex items-center gap-2 mb-2">
                        <a href="{{ route('webhook-sources.index') }}" class="text-slate-400 hover:text-slate-500 text-[10px] font-bold uppercase tracking-widest transition-colors">
                            Developer/Webhook Sources
                        </a>
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-[10px] font-bold text-wa-teal uppercase tracking-widest">
                            {{ $editingId ? 'Edit' : 'New' }}
                        </span>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $editingId ? 'Update Webhook Source' : 'New Webhook Source' }}
                    </h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Configure your inbound integration</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="max-h-[70vh] min-h-[500px] overflow-y-auto overflow-x-hidden custom-scrollbar pr-2">
                {{-- Wizard Progress Header --}}
                <div class="mb-10">
                    <div class="flex items-center justify-between max-w-4xl mx-auto">
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
                                    class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs transition-all duration-500 {{ $currentStep >= $stepNum ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/30 scale-110' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                    @if($currentStep > $stepNum)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        {{ $stepNum }}
                                    @endif
                                </div>
                                <div class="text-center hidden md:block">
                                    <div
                                        class="text-[9px] font-black uppercase tracking-tight {{ $currentStep >= $stepNum ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                        {{ $step[0] }}</div>
                                </div>
                            </div>
                            @if($stepNum < 4)
                                <div
                                    class="flex-1 h-[2px] mb-4 mx-2 {{ $currentStep > $stepNum ? 'bg-wa-teal' : 'bg-slate-100 dark:bg-slate-800' }}">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Step Content --}}
                <div class="max-w-4xl mx-auto">
                    {{-- Step 1: Identify & Secure --}}
                    @if($currentStep === 1)
                            <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div class="flex items-center gap-4 mb-8">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-wa-teal flex items-center justify-center">
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
                                        class="uppercase text-[10px] tracking-widest font-black text-slate-400 group-focus-within:text-wa-teal transition-colors" />
                                    <x-input wire:model="name" type="text"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.5rem] py-4 px-6 font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 focus:ring-4 focus:ring-purple-500/10 transition-all shadow-inner"
                                        placeholder="e.g. Shopify Store" />
                                    <x-input-error for="name" />
                                </div>

                                <div class="space-y-2 group">
                                    <x-label value="Platform"
                                        class="uppercase text-[10px] tracking-widest font-black text-slate-400 group-focus-within:text-wa-teal transition-colors" />
                                    <select wire:model.live="platform"
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
                                                class="p-4 bg-purple-50/50 dark:bg-purple-900/10 rounded-xl border border-purple-100/50 dark:border-purple-500/10 text-[10px] font-bold text-wa-teal/70 uppercase tracking-widest">
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
                                                        class="text-[10px] font-black text-wa-teal hover:text-wa-teal uppercase tracking-widest">Regenerate</button>
                                                </div>
                                                <div class="relative group">
                                                    <x-input wire:model="auth_config.key" type="text"
                                                        class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl py-3 px-5 font-mono text-xs text-slate-900 dark:text-white"
                                                        readonly />
                                                    <button
                                                        onclick="navigator.clipboard.writeText('{{ $auth_config['key'] ?? '' }}')"
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-wa-teal transition-colors bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800">
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
                                                        class="text-[10px] font-black text-wa-teal hover:text-wa-teal uppercase tracking-widest">Regenerate</button>
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
                                    class="bg-wa-teal text-white rounded-[2rem] p-8 shadow-2xl shadow-wa-teal/30 animate-in slide-in-from-left duration-700">
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
                                                    class="text-sm font-mono bg-black/20 py-2 px-4 rounded-xl flex-1 text-center md:text-left break-all">{{ $this->currentSource?->getWebhookUrl() }}</code>
                                                <button
                                                    onclick="navigator.clipboard.writeText('{{ $this->currentSource?->getWebhookUrl() }}')"
                                                    class="bg-white text-wa-teal px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-colors shadow-lg shadow-black/10">Copy
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
                                Listening for <span class="text-wa-teal">Events</span></h4>
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
                                class="relative z-10 w-48 h-48 md:w-64 md:h-64 rounded-full bg-white dark:bg-slate-900 shadow-2xl flex flex-col items-center justify-center border-8 border-slate-50 dark:border-slate-800 transition-all duration-700 {{ $isCapturing ? 'border-purple-500/50 scale-105 md:scale-110' : '' }}">
                                @if($isCapturing)
                                    <div class="w-12 h-12 md:w-16 md:h-16 text-wa-teal animate-bounce mb-4">
                                        <svg fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" />
                                        </svg>
                                    </div>
                                    <button wire:click="stopCapture"
                                        class="text-xs font-black text-rose-500 uppercase tracking-widest hover:underline bg-rose-500/5 px-4 py-2 rounded-full border border-rose-500/10">Stop
                                        Listening</button>
                                @else
                                    <button wire:click="startCapture"
                                        class="w-32 h-32 md:w-40 md:h-40 rounded-full bg-gradient-to-tr from-wa-teal to-wa-teal text-white flex flex-col items-center justify-center gap-2 hover:scale-105 transition-transform shadow-xl shadow-wa-teal/30 group">
                                        <svg class="w-8 h-8 md:w-12 md:h-12 group-hover:rotate-12 transition-transform" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-[10px] md:text-xs font-black uppercase tracking-widest">Start Capture</span>
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
                            <h6 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Connection Details
                            </h6>
                            <div class="space-y-6">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mb-1">Webhook URL</p>
                                        <code class="text-xs font-mono text-white break-all select-all">{{ $this->currentSource?->getWebhookUrl() }}</code>
                                    </div>
                                    <span class="px-3 py-1 bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase rounded-lg border border-emerald-500/20 shrink-0">Ready</span>
                                </div>

                                <div class="p-6 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                                    <h7 class="text-[9px] font-black text-wa-teal uppercase tracking-widest flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Required Authentication
                                    </h7>

                                    @if($auth_method === 'none')
                                        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Public Webhook: No Key Required</p>
                                        </div>
                                    @else
                                        @php
                                            $authHeader = $auth_config['header'] ?? null;
                                            if (!$authHeader) {
                                                $authHeader = match ($auth_method) {
                                                    'api_key' => 'X-API-Key',
                                                    'hmac' => 'X-Webhook-Signature',
                                                    'basic' => 'Authorization',
                                                    default => null
                                                };
                                            }
                                            $authValue = match ($auth_method) {
                                                'api_key' => $auth_config['key'] ?? 'MISSING_KEY',
                                                'hmac' => 'HMAC-SHA256(payload, secret)',
                                                'basic' => 'Basic base64(user:pass)',
                                                default => 'N/A'
                                            };
                                        @endphp

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mb-1">Header Name</p>
                                                <code class="text-xs font-mono text-white truncate block">{{ $authHeader ?: 'N/A' }}</code>
                                            </div>
                                            <div>
                                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mb-1">Expected Value</p>
                                                <code class="text-xs font-mono text-white truncate block">{{ $authValue }}</code>
                                            </div>
                                        </div>
                                    @endif

                                    @if($auth_method === 'api_key')
                                        <div class="mt-4 pt-4 border-t border-white/5">
                                            <p class="text-[9px] font-bold text-amber-500/80 uppercase tracking-widest flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Tip: Use Postman or curl to test with this header
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Visual Mapping --}}
                @if($currentStep === 3)
                    <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-500">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-wa-teal flex items-center justify-center">
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
                                <button wire:click="refreshMappingContext" type="button" class="p-2.5 bg-white dark:bg-slate-900 text-slate-400 hover:text-wa-teal rounded-xl border border-slate-100 dark:border-slate-800 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Action Type Selection --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <button wire:click="setActionType('send_template')" type="button" 
                                class="flex items-center gap-4 p-6 rounded-[2rem] border-2 transition-all {{ $actionType === 'send_template' ? 'bg-wa-teal border-wa-teal text-white shadow-xl shadow-wa-teal/20' : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-wa-teal/30' }}">
                                <div class="w-12 h-12 rounded-2xl {{ $actionType === 'send_template' ? 'bg-white/20' : 'bg-purple-100 dark:bg-purple-900/30' }} flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                </div>
                                <div class="text-left">
                                    <h5 class="text-sm font-black uppercase tracking-tight">Standard Message</h5>
                                    <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Send templates based on data</p>
                                </div>
                            </button>

                            <button wire:click="setActionType('send_otp')" type="button" 
                                class="flex items-center gap-4 p-6 rounded-[2rem] border-2 transition-all {{ $actionType === 'send_otp' ? 'bg-orange-500 border-orange-500 text-white shadow-xl shadow-orange-500/20' : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-orange-500/30' }}">
                                <div class="w-12 h-12 rounded-2xl {{ $actionType === 'send_otp' ? 'bg-white/20' : 'bg-orange-100 dark:bg-orange-900/30' }} flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div class="text-left">
                                    <h5 class="text-sm font-black uppercase tracking-tight">OTP / Verification</h5>
                                    <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Auto-generate & verify codes</p>
                                </div>
                            </button>
                        </div>

                        {{-- OTP Config --}}
                        @if($actionType === 'send_otp')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-in fade-in slide-in-from-top-4 duration-500">
                                <div class="space-y-4 bg-orange-50/30 dark:bg-orange-950/10 p-6 rounded-[2rem] border border-orange-100 dark:border-orange-900/20">
                                    <x-label value="OTP Length" class="uppercase text-[10px] tracking-widest font-black text-orange-600/70" />
                                    <select wire:model="otpLength" class="w-full bg-white dark:bg-slate-900 border-2 border-orange-100 dark:border-orange-900/20 rounded-2xl py-3 px-5 font-bold text-sm text-slate-900 dark:text-white focus:border-orange-500/30 transition-all shadow-sm">
                                        <option value="4">4 Digits</option>
                                        <option value="6">6 Digits</option>
                                        <option value="8">8 Digits</option>
                                    </select>
                                </div>
                                <div class="space-y-4 bg-orange-50/30 dark:bg-orange-950/10 p-6 rounded-[2rem] border border-orange-100 dark:border-orange-900/20">
                                    <x-label value="OTP Variable Position" class="uppercase text-[10px] tracking-widest font-black text-orange-600/70" />
                                    <select wire:model="otpParamIndex" class="w-full bg-white dark:bg-slate-900 border-2 border-orange-100 dark:border-orange-900/20 rounded-2xl py-3 px-5 font-bold text-sm text-slate-900 dark:text-white focus:border-orange-500/30 transition-all shadow-sm">
                                        @foreach($templateParams as $paramNum)
                                            <option value="{{ $paramNum }}">Parameter {{ $paramNum }}</option>
                                        @endforeach
                                        @if(empty($templateParams))
                                            <option value="1">Parameter 1</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        @endif

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

                        @if($selectedTemplateId)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                {{-- Template Preview --}}
                                <div class="col-span-full md:col-span-1 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Template Preview</h5>
                                        <button wire:click="sendTestMessage" type="button" 
                                            class="bg-wa-teal text-white text-[10px] uppercase font-black tracking-widest px-3 py-1.5 rounded-lg shadow-lg shadow-wa-teal/20 hover:scale-105 transition-transform flex items-center gap-2">
                                            <svg wire:loading.remove wire:target="sendTestMessage" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                            <svg wire:loading wire:target="sendTestMessage" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Send Test
                                        </button>
                                    </div>

                                    @if($testMessageResult)
                                        <div class="mt-4 mb-2 p-3 rounded-xl border flex items-start gap-3 {{ $testMessageResult['type'] === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-rose-50 border-rose-100 text-rose-800' }}">
                                            @if($testMessageResult['type'] === 'success')
                                                <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                                <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                            <div class="text-xs font-medium leading-relaxed">
                                                {{ $testMessageResult['message'] }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-800/50 relative overflow-hidden group">
                                        <div class="absolute top-0 right-0 p-4 opacity-20">
                                            <svg class="w-12 h-12 text-wa-teal" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.94 3.659 1.437 5.634 1.437h.005c6.551 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        </div>
                                        <div class="relative z-10">
                                            @php
                                                $previewContent = ($selectedTemplateId && $selectedTemplate) ? $selectedTemplate->content : 'No template selected or found';

                                                if ($selectedTemplateId && $selectedTemplate) {
                                                    foreach ($templateParameters as $num => $path) {
                                                        if ($path) {
                                                            $val = $mappingContext[$path] ?? null;
                                                            if (str_starts_with($path, 'STATIC:')) {
                                                                $val = substr($path, 7);
                                                            }
                                                            if ($val) {
                                                                $previewContent = str_replace("{{{$num}}}", "<span class='text-wa-teal font-black'>$val</span>", $previewContent);
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <p class="text-xs font-bold text-slate-900 dark:text-white leading-relaxed whitespace-pre-wrap">{!! $previewContent !!}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Phone Number Mapping (Required) --}}
                                <div class="col-span-full md:col-span-1 space-y-4">
                                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Routing Config</h5>
                                    <div class="bg-gradient-to-br from-wa-teal to-wa-teal rounded-[2.5rem] p-8 shadow-xl relative overflow-hidden h-full flex flex-col justify-center">
                                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                                        <div class="relative z-10 space-y-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h5 class="text-white font-black uppercase tracking-tight flex items-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                        Dest. Phone Number
                                                    </h5>
                                                    <p class="text-purple-100/70 text-[10px] font-bold uppercase tracking-widest mt-1">
                                                        {{ str_starts_with($field_mappings['phone_number'] ?? '', 'STATIC:') ? 'Using fixed phone number' : 'Select field from payload' }}
                                                    </p>
                                                </div>
                                                @if(str_starts_with($field_mappings['phone_number'] ?? '', 'STATIC:'))
                                                    <button wire:click="$set('field_mappings.phone_number', '')" type="button" class="text-[10px] font-black text-white hover:underline uppercase tracking-widest bg-white/10 px-3 py-1 rounded-lg">Switch to Dynamic</button>
                                                @else
                                                    <button wire:click="$set('field_mappings.phone_number', 'STATIC:')" type="button" class="text-[10px] font-black text-purple-100 hover:text-white hover:underline uppercase tracking-widest bg-black/10 px-3 py-1 rounded-lg">Set Static Value</button>
                                                @endif
                                            </div>

                                            @if(str_starts_with($field_mappings['phone_number'] ?? '', 'STATIC:'))
                                                <input type="text" 
                                                       value="{{ substr($field_mappings['phone_number'] ?? '', 7) }}"
                                                       x-on:change="$wire.set('field_mappings.phone_number', 'STATIC:' + $event.target.value)"
                                                       class="w-full bg-white/10 border-2 border-white/20 rounded-2xl py-3 px-5 font-bold text-sm text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 focus:ring-0 transition-all"
                                                       placeholder="Enter phone number (e.g. +1234567890)" />
                                            @else
                                                <select wire:model.live="field_mappings.phone_number" class="w-full bg-white/10 border-2 border-white/20 rounded-2xl py-3 px-5 font-mono text-xs text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 focus:ring-0 transition-all cursor-pointer">
                                                    <option value="" class="text-slate-900">-- Select Phone Field --</option>
                                                    @foreach($mappingContext as $key => $value)
                                                        <option value="{{ $key }}" class="text-slate-900">{{ $key }} ({{ Str::limit(is_string($value) ? $value : json_encode($value), 30) }})</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Variables Mapping --}}
                                @if(!empty($templateParams))
                                    <div class="col-span-full space-y-4">
                                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Variable Assignments</h5>
                                        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar p-1 pr-4 -mr-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                @foreach($templateParams as $paramNum)
                                                    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:scale-[1.02] transition-all group">
                                                        <div class="flex items-center justify-between mb-4">
                                                            <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-wa-teal flex items-center justify-center font-black text-xs border border-purple-100 dark:border-purple-500/20">
                                                                {{ $paramNum }}
                                                            </span>
                                                            @if(str_starts_with($templateParameters[$paramNum] ?? '', 'STATIC:'))
                                                                <button wire:click="$set('templateParameters.{{ $paramNum }}', '')" class="text-[10px] font-black text-wa-teal hover:underline uppercase tracking-widest">Switch to Dynamic</button>
                                                            @else
                                                                <button wire:click="$set('templateParameters.{{ $paramNum }}', 'STATIC:')" class="text-[10px] font-black text-slate-400 hover:text-wa-teal hover:underline uppercase tracking-widest">Set Static Value</button>
                                                            @endif
                                                        </div>

                                                        {{-- Template Context Snippet --}}
                                                        @php
                                                            $fullContent = $selectedTemplate->content;
                                                            $snippet = '';
                                                            // Look for the variable in the content and grab surrounding text
                                                            if (preg_match('/(?:^|(.{0,40}))\{\{' . $paramNum . '\}\}(.{0,40}|$)/s', $fullContent, $matches)) {
                                                                $before = $matches[1] ?? '';
                                                                $after = $matches[2] ?? '';
                                                                $snippet = ($before ? '...' : '') . e($before) . '<span class="text-wa-teal font-black underline">[' . $paramNum . ']</span>' . e($after) . ($after ? '...' : '');
                                                            }
                                                        @endphp
                                                        @if($snippet)
                                                            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800/50 text-[11px] font-medium text-slate-600 dark:text-slate-400 italic leading-relaxed">
                                                                {!! $snippet !!}
                                                            </div>
                                                        @endif

                                                        <div class="space-y-4">
                                                            @if(str_starts_with($templateParameters[$paramNum] ?? '', 'STATIC:'))
                                                                <input type="text" 
                                                                       value="{{ substr($templateParameters[$paramNum] ?? '', 7) }}"
                                                                       @change="$wire.set('templateParameters.{{ $paramNum }}', 'STATIC:' + $event.target.value)"
                                                                       class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.25rem] py-3 px-5 font-bold text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 transition-all shadow-inner"
                                                                       placeholder="Fixed Text Value..." />
                                                            @else
                                                                <select wire:model.live="templateParameters.{{ $paramNum }}" class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-transparent rounded-[1.25rem] py-3 px-5 font-mono text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:border-purple-500/30 transition-all shadow-inner cursor-pointer">
                                                                    <option value="">-- Map to Field --</option>
                                                                    @foreach($mappingContext as $key => $value)
                                                                        <option value="{{ $key }}">{{ $key }} ({{ Str::limit(is_string($value) ? $value : json_encode($value), 20) }})</option>
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
                                @else
                                    <div class="col-span-full">
                                        <div class="bg-amber-50 dark:bg-amber-900/10 rounded-3xl p-12 text-center border-2 border-dashed border-amber-200 dark:border-amber-800">
                                            <p class="text-amber-600 dark:text-amber-400 font-black uppercase tracking-widest text-sm">No variables detected in this template. Only phone number mapping is required.</p>
                                        </div>
                                    </div>
                                @endif
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
                                 <div class="flex-1 overflow-auto p-8 font-mono text-xs text-wa-teal custom-scrollbar">
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
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 text-wa-teal flex items-center justify-center">
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
                                    <button wire:click="addFilterRule" type="button" class="text-[10px] font-black text-wa-teal hover:text-wa-teal uppercase tracking-widest flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add Rule
                                    </button>
                                </div>

                                <div class="max-h-[300px] overflow-y-auto custom-scrollbar p-1 pr-4 -mr-4">
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
                                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 text-wa-teal flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <h5 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">Source Status</h5>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active & Ready</span>
                                        <button wire:click="$toggle('is_active')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-wa-teal' : 'bg-slate-200 dark:bg-slate-800' }}">
                                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    @if($currentStep > 1)
                        <button wire:click="previousStep" type="button" class="group px-6 py-3 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-[10px] font-black text-slate-600 dark:text-slate-400 hover:text-wa-teal hover:border-wa-teal/30 transition-all uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-3 h-3 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back
                        </button>
                    @endif
                    <button wire:click="cancelEdit" type="button" class="text-[10px] font-black text-slate-400 hover:text-rose-500 uppercase tracking-widest transition-colors px-4">Cancel</button>
                </div>

                <div class="flex items-center gap-4">
                    @if($currentStep < 4)
                        <button wire:click="nextStep" type="button" class="group px-8 py-3 bg-wa-teal text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-wa-teal transition-all shadow-xl shadow-wa-teal/30 flex items-center gap-2">
                            Next
                            <svg class="w-3 h-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @else
                        <button wire:click="update" type="button" class="group px-10 py-3 bg-gradient-to-r from-wa-teal to-wa-teal text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-wa-teal/40 flex items-center gap-2">
                            Complete Setup
                            <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>

    @if($sources->hasPages())
        <div class="mt-8 p-8 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            {{ $sources->links() }}
        </div>
    @endif

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
                                        <div class="bg-slate-900 text-wa-teal rounded-[2rem] p-6 shadow-xl border border-slate-800">
                                            <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-4">Resolved Data</h5>
                                            <div class="overflow-x-auto overflow-y-auto max-h-60 custom-scrollbar">
                                                <pre class="text-[10px] font-mono">{{ json_encode($testResult['mapped_data'], JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="px-10 py-8 bg-slate-50 dark:bg-slate-800/10 border-t border-slate-50 dark:border-slate-800 flex justify-end gap-4">
                    <button wire:click="$set('showTestModal', false)" class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest px-6">Close</button>
                    <button wire:click="testWebhook" class="px-8 py-4 bg-wa-teal text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-wa-teal shadow-lg shadow-wa-teal/30 transition-all">Run Diagnostic</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Logs Monitor Modal --}}
    <x-dialog-modal wire:model.live="showLogsModal" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    {{-- Breadcrumb Navigation --}}
                    <div class="flex items-center gap-2 mb-2">
                        <a href="{{ route('webhook-sources.index') }}" class="text-slate-400 hover:text-slate-500 text-[10px] font-bold uppercase tracking-widest transition-colors">
                            Developer/Webhook Sources
                        </a>
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-[10px] font-bold text-wa-teal uppercase tracking-widest">
                            Analytics
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-500/10 text-orange-600 rounded-lg">
                            <svg class="w-5 h-5 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-lg font-black uppercase tracking-tight block leading-none">{{ data_get($logsSourceStats, 'name', 'Live Event Monitor') }}</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">Real-time performance analytics</span>
                        </div>
                    </div>
                </div>
                <button wire:click="refreshLogs" class="p-2 text-slate-400 hover:text-wa-teal hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-all group" title="Refresh Now">
                    <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </x-slot>

        <x-slot name="content">
            {{-- Debug: {{ var_export($logsSourceStats, true) }} --}}
            {{-- Stats Dashboard --}}
            @if(isset($logsSourceStats['name']))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-slate-50 dark:bg-slate-800/30 p-5 rounded-3xl border border-slate-100 dark:border-slate-800/50">
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Received</div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format(data_get($logsSourceStats, 'received', 0)) }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/30 p-5 rounded-3xl border border-slate-100 dark:border-slate-800/50">
                        <div class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mb-1">Processed</div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format(data_get($logsSourceStats, 'processed', 0)) }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/30 p-5 rounded-3xl border border-slate-100 dark:border-slate-800/50">
                        <div class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-1">Failed</div>
                        <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format(data_get($logsSourceStats, 'failed', 0)) }}</div>
                    </div>
                    <div class="bg-wa-teal p-5 rounded-3xl shadow-lg shadow-wa-teal/20">
                        <div class="text-[9px] font-black text-white/70 uppercase tracking-widest mb-1">Success Rate</div>
                        <div class="text-xl font-black text-white">{{ data_get($logsSourceStats, 'rate', 0) }}%</div>
                    </div>
                </div>
            @else
                <div class="mb-8 p-6 bg-slate-50 dark:bg-slate-800/20 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5 text-slate-300 animate-spin" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4m0 8v4m8-12h-4m-8 0H4m15.364 1.636l-2.828 2.828m-9.072 9.072l-2.828 2.828m0-14.728l2.828 2.828m9.072 9.072l2.828 2.828"/></svg>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Awaiting Analytics Data...</span>
                </div>
            @endif

            <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-wa-teal animate-pulse"></div>
                Recent Inbound Payloads
            </h5>
            
            <div class="space-y-4 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($recentLogs as $log)
                    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] p-6 shadow-sm group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-purple-50 dark:bg-purple-900/30 text-wa-teal text-[10px] font-black uppercase rounded-lg border border-purple-100 dark:border-purple-500/20">
                                    {{ $log['event_type'] ?: 'GENERIC_EVENT' }}
                                </span>
                                @php
                                    $statusColors = [
                                        'processed' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                        'failed' => 'bg-rose-500/10 text-rose-500 border-rose-500/20',
                                        'pending' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                        'skipped' => 'bg-slate-500/10 text-slate-500 border-slate-500/20',
                                    ];
                                    $statusColor = $statusColors[$log['status']] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20';
                                @endphp
                                <span class="px-3 py-1 {{ $statusColor }} text-[10px] font-black uppercase rounded-lg border">
                                    {{ $log['status'] }}
                                </span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $log['created_at'] }}</span>
                        </div>

                        @if($log['error_message'])
                            <div class="mb-4 p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-800/20 rounded-xl">
                                <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-1">Error Details</p>
                                <p class="text-xs font-mono text-rose-600 dark:text-rose-400 break-words">{{ $log['error_message'] }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Raw Payload</p>
                                <pre class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl text-[9px] font-mono text-slate-600 dark:text-slate-400 overflow-x-auto border border-slate-100 dark:border-slate-800 max-h-40 custom-scrollbar">{{ json_encode($log['payload'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            <div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">Mapped Data</p>
                                <pre class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl text-[9px] font-mono text-wa-teal overflow-x-auto border border-slate-100 dark:border-slate-800 max-h-40 custom-scrollbar">{{ json_encode($log['mapped_data'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16 bg-slate-50 dark:bg-slate-800/20 rounded-[2.5rem] border-2 border-dashed border-slate-100 dark:border-slate-800">
                        <div class="w-12 h-12 bg-white dark:bg-slate-900 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest px-8">No events captured yet. Send a request to your webhook URL to see it here.</p>
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

    {{-- Source Report Modal --}}
    <x-dialog-modal wire:model.live="showSourceReportModal" maxWidth="5xl">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Delivery Report <span class="text-indigo-600">{{ $selectedSourceForReport?->name ?? 'N/A' }}</span>
                    </h3>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-gray-200">Platform: {{ $selectedSourceForReport?->platform ?? 'N/A' }}</span>
                    </div>
                </div>
                <button wire:click="closeSourceReportModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </x-slot>

        <x-slot name="content">
            <!-- Filter Controls -->
            <div class="mb-6 flex gap-4 flex-col md:flex-row">
                <div class="flex-1">
                    <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-2">From Date</label>
                    <input type="date" wire:model.live="sourceReportFromDate" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm" />
                </div>
                <div class="flex-1">
                    <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-2">To Date</label>
                    <input type="date" wire:model.live="sourceReportToDate" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm" />
                </div>
                <div class="flex-1">
                    <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-2">Per Page</label>
                    <select wire:model.live="sourceReportPerPage" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                @php
                    $stats = $sourceReportStats ?? [];
                @endphp
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Targeted</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['targeted'] ?? 0) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Sent</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['sent'] ?? 0) }}</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center border border-blue-100 dark:border-blue-800">
                    <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Delivered</div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['delivered'] ?? 0) }}</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 text-center border border-emerald-100 dark:border-emerald-800">
                    <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Read</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['read'] ?? 0) }}</div>
                </div>
                <div class="bg-rose-50 dark:bg-rose-900/20 rounded-lg p-4 text-center border border-rose-100 dark:border-rose-800">
                    <div class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest">Failed</div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($stats['failed'] ?? 0) }}</div>
                </div>
            </div>

            <!-- Rates -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg p-6">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Delivery Rate</div>
                    @php
                        $deliveryRate = ($stats['sent'] ?? 0) > 0 ? round(($stats['delivered'] ?? 0) / $stats['sent'] * 100, 1) : 0;
                    @endphp
                    <div class="text-3xl font-black text-indigo-600">{{ $deliveryRate }}%</div>
                    <div class="mt-2 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($deliveryRate, 100) }}%"></div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg p-6">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Read Rate</div>
                    @php
                        $readRate = ($stats['delivered'] ?? 0) > 0 ? round(($stats['read'] ?? 0) / $stats['delivered'] * 100, 1) : 0;
                    @endphp
                    <div class="text-3xl font-black text-emerald-600">{{ $readRate }}%</div>
                    <div class="mt-2 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min($readRate, 100) }}%"></div>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">Report showing analytics for webhook source delivery performance.</p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeSourceReportModal" class="!rounded-lg">
                Close Report
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>