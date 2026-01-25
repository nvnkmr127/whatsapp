<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Page Header (System Style) -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-rose-500/10 text-rose-500 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Security <span class="text-rose-500">Audit</span>
                        </h1>
                    </div>
                    <p class="text-slate-500 font-medium tracking-tight">Observing system-wide authentication events and trust telemetry.</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" 
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
                        Back to Nexus
                    </a>
                    <button onclick="window.location.reload()"
                        class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-rose-500 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-rose-500/20 hover:scale-[1.02] active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh Logs
                    </button>
                </div>
            </div>

            <!-- Table & Filters Card (System Style) -->
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                
                <!-- Search & Filters Container -->
                <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
                    <form method="GET" action="{{ route('admin.audit-logs') }}" class="flex flex-col md:flex-row gap-4 flex-1">
                        <div class="flex-1 relative group">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-rose-500/20 transition-all font-medium"
                                placeholder="Search by IP, Email, or Name...">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-rose-500 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="w-full sm:w-56">
                                <select name="event" onchange="this.form.submit()"
                                    class="w-full py-4 px-6 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-rose-500/20 transition-all cursor-pointer appearance-none">
                                    <option value="">All Security Events</option>
                                    <option value="Auth.Success" {{ request('event') == 'Auth.Success' ? 'selected' : '' }}>Success</option>
                                    <option value="Auth.Failure" {{ request('event') == 'Auth.Failure' ? 'selected' : '' }}>Failure</option>
                                    <option value="Auth.OTP.Request" {{ request('event') == 'Auth.OTP.Request' ? 'selected' : '' }}>OTP Request</option>
                                    <option value="Auth.Abuse" {{ request('event') == 'Auth.Abuse' ? 'selected' : '' }}>Abuse Flags</option>
                                    <option value="Auth.Revoke" {{ request('event') == 'Auth.Revoke' ? 'selected' : '' }}>Revocation</option>
                                </select>
                            </div>
                            <button type="submit" class="hidden md:flex items-center justify-center px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 transition-colors">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Specialized Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Timestamp</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Classification</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Subject Identity</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Origin Metrics</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Contextual Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                            @forelse($logs as $log)
                                <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-8 py-6 whitespace-nowrap">
                                        <div class="text-xs font-black text-slate-900 dark:text-white">{{ $log->created_at->format('M d, H:i:s') }}</div>
                                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-8 py-6">
                                        @php
                                            $color = match(true) {
                                                str_contains($log->event_type, 'Success') => '#22c55e',
                                                str_contains($log->event_type, 'Failure') => '#ef4444',
                                                str_contains($log->event_type, 'Abuse') => '#f43f5e',
                                                str_contains($log->event_type, 'Request') => '#6366f1',
                                                default => '#64748b'
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                              style="background-color: {{ $color }}10; color: {{ $color }}; border-color: {{ $color }}30">
                                            {{ str_replace('.', ' ', $log->event_type) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            @if($log->user)
                                                <img src="{{ $log->user->profile_photo_url }}" class="w-8 h-8 rounded-full border border-slate-100 dark:border-slate-800 shadow-sm" loading="lazy">
                                                <div>
                                                    <div class="text-sm font-black text-slate-900 dark:text-white">{{ $log->user->name }}</div>
                                                    <div class="text-[10px] font-bold text-slate-400">{{ $log->user->email }}</div>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-black text-slate-600 dark:text-slate-400 italic capitalize">{{ $log->identifier ?: 'Anonymized' }}</div>
                                                    <div class="text-[9px] font-black text-rose-500/60 uppercase tracking-widest">Unauthenticated</div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col gap-1">
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[11px] font-black tabular-nums rounded-md border border-slate-200/50 dark:border-slate-700/50 w-fit">
                                                {{ $log->ip_address }}
                                            </span>
                                            <div class="text-[9px] font-bold text-slate-400 truncate max-w-[140px] uppercase tracking-tighter" title="{{ $log->user_agent }}">
                                                {{ str_replace('Mozilla/5.0 ', '', $log->user_agent) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        @if($log->metadata)
                                            <div class="space-y-1">
                                                @foreach(collect($log->metadata)->only(['reason', 'provider', 'google_id', 'description']) as $key => $value)
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest shrink-0">{{ $key }}:</span>
                                                        <span class="text-[10px] font-black text-slate-700 dark:text-slate-300 truncate">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                    </div>
                                                @endforeach
                                                @if(count($log->metadata) > 4)
                                                    <div class="text-[8px] font-black text-rose-500 uppercase tracking-widest">+ {{ count($log->metadata) - 4 }} Extended Fields</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-300 italic">No telemetry data</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-32 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-[2rem] flex items-center justify-center text-slate-200">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="text-slate-400 font-black uppercase tracking-widest text-xs">No audit telemetry matching your criteria.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Styled Pagination Container -->
                @if($logs->hasPages())
                    <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

            <!-- Threat Metrics Card (System Style Extension) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-slate-900 rounded-[2.5rem] p-8 border border-white/5 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-rose-500/20 rounded-full blur-3xl"></div>
                    <div class="relative flex flex-col gap-6">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <h3 class="text-lg font-black text-white uppercase tracking-tight">Abuse Prevention</h3>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">Automated Security Perimeter</p>
                            </div>
                            <div class="p-3 bg-white/5 rounded-2xl">
                                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                                <div class="text-[10px] font-black text-slate-500 uppercase mb-1">Throttling</div>
                                <div class="text-xl font-black text-white">ACTIVE</div>
                            </div>
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                                <div class="text-[10px] font-black text-slate-500 uppercase mb-1">Blacklist</div>
                                <div class="text-xl font-black text-wa-teal">OPTIMIZED</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-800 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 h-full justify-between">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Log Retention</h3>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none">Compliance Storage Matrix</p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-xs font-bold text-slate-500">
                            Logs are currently retained for <span class="text-slate-900 dark:text-white font-black">90 days</span> for compliance adherence. 
                            <a href="#" class="text-rose-500 hover:underline">Configure Rotation Settings →</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
