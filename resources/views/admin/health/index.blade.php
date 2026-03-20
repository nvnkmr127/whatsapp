<x-app-layout>
    <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- MASTER HEADER -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 px-2">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-600 text-white rounded-2xl shadow-xl shadow-indigo-600/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase leading-none">
                            {{ __('System') }} <span class="text-indigo-600">{{ __('Report') }}</span>
                        </h1>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2 flex items-center gap-2">
                             <span class="w-2 h-2 bg-wa-green rounded-full animate-pulse"></span>
                            {{ __('Refreshed just now — :time', ['time' => now()->format('H:i:s')]) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <!-- SERVER STATS -->
                    <div class="flex items-center gap-6 bg-white dark:bg-slate-900 px-6 py-3 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                        <div class="text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">{{ __('Load') }}</p>
                            <p class="text-xs font-black text-slate-900 dark:text-white">{{ $healthData['server']['load'] }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">{{ __('Disk') }}</p>
                            <p class="text-xs font-black text-slate-900 dark:text-white">{{ $healthData['server']['disk_usage'] }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">{{ __('Database') }}</p>
                            <p class="text-xs font-black text-indigo-600">{{ $healthData['database']['connections'] }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">{{ __('Cache') }}</p>
                            <p class="text-xs font-black text-indigo-600">{{ $healthData['redis']['memory_used'] }}</p>
                        </div>
                        <div class="text-center border-l border-slate-100 dark:border-slate-800 pl-6">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">{{ __('Meta API') }}</p>
                            <p class="text-xs font-black {{ $healthData['whatsapp']['api_status'] === 'Optimal' ? 'text-wa-green' : 'text-rose-500' }}">{{ $healthData['whatsapp']['api_status'] }}</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.health.jobs.retry') }}" method="POST">
                        @csrf
                        <button class="px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl">
                            {{ __('Try Fixing Errors') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- QUICK STATS -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <!-- Money -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ __('Total Money') }}</p>
                        <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">${{ number_format($healthData['financials']['total_liquidity'], 2) }}</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-[10px] font-black uppercase">
                        <span class="text-wa-green">{{ __('24h Gain') }}: ${{ number_format($healthData['financials']['revenue_24h'], 2) }}</span>
                        <span class="text-slate-400">{{ __(':count Sales', ['count' => $healthData['financials']['transaction_count_24h']]) }}</span>
                    </div>
                </div>

                <!-- Growth -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ __('New Contacts') }}</p>
                        <div class="text-4xl font-black text-indigo-600 tracking-tighter">+{{ number_format($healthData['growth']['new_contacts']) }}</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-[10px] font-black uppercase">
                        <span class="text-slate-400">{{ __('Users') }}: +{{ $healthData['growth']['new_users'] }}</span>
                        <span class="text-indigo-600">{{ __('Daily Gain') }}</span>
                    </div>
                </div>

                <!-- Messages -->
                <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ __('Message Status') }}</p>
                        <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">{{ $healthData['messages']['delivery_rate'] }}%</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-[10px] font-black uppercase">
                        <span class="text-wa-green">{{ __('Sent') }}: {{ number_format($healthData['messages']['total']) }}</span>
                        <span class="text-rose-500">{{ __('Failed') }}: {{ $healthData['messages']['failed'] }}</span>
                    </div>
                </div>

                <!-- Webhooks -->
                <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl shadow-indigo-600/20 text-white flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-60">{{ __('System Success') }}</p>
                        <div class="text-4xl font-black tracking-tighter">{{ $healthData['webhooks']['success_rate'] }}%</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-[10px] font-black uppercase">
                        <span>{{ __('Active') }}: {{ $healthData['webhooks']['active_teams'] }}</span>
                        <span class="opacity-60">{{ __('Offline') }}: {{ $healthData['webhooks']['silent_teams'] }}</span>
                    </div>
                </div>
            </div>

            <!-- FEATURE USAGE -->
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ __('Features Used') }}</h3>
                    <div class="flex gap-4">
                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase">{{ __('Ready Templates') }}</p>
                            <p class="text-lg font-black text-indigo-600">{{ $healthData['template_stats']['approved'] }}</p>
                        </div>
                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-center border-l-4 border-rose-500">
                            <p class="text-[8px] font-black text-slate-400 uppercase">{{ __('Rejected') }}</p>
                            <p class="text-lg font-black text-rose-500">{{ $healthData['template_stats']['rejected'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Campaigns') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ $healthData['feature_stats']['active_campaigns'] }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Workflows') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ $healthData['feature_stats']['automations'] }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Flows') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ $healthData['feature_stats']['flows'] }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Contacts') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ number_format($healthData['feature_stats']['contacts']) }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Webhooks') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ $healthData['feature_stats']['active_webhooks'] }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Chats') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ number_format($healthData['feature_stats']['conversations']) }}</div>
                    </div>
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-indigo-600 transition-colors">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3 text-center">{{ __('Tasks') }}</p>
                        <div class="text-2xl font-black text-indigo-600 text-center">{{ $healthData['feature_stats']['pending_backups'] }}</div>
                    </div>
                </div>
            </div>

            <!-- MAIN LISTS -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
                
                <!-- COMPANY LIST -->
                <div class="xl:col-span-8 space-y-8">
                    
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ __('Company List') }}</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ __('Live status of connected accounts') }}</p>
                            </div>
                            <div class="relative w-full md:w-72">
                                <input type="text" id="customerSearch" placeholder="{{ __('FIND COMPANY...') }}" class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-600 transition-all">
                                <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="customerTable">
                                <thead class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">{{ __('Team Name') }}</th>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">{{ __('Status') }}</th>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">{{ __('Quality') }}</th>
                                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">{{ __('Tier') }}</th>
                                        <th class="px-8 py-5 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Manage') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                                    @foreach($healthData['waba_detailed'] as $team)
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-all" data-name="{{ strtolower($team->name) }}">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-indigo-500">
                                                    {{ substr($team->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ $team->name }}</p>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">ID: #{{ $team->id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            @php
                                                $stateVal = $team->whatsapp_setup_state->value ?? $team->whatsapp_setup_state;
                                                $stateColor = $stateVal === 'ready' || $stateVal === 'ACTIVE' ? 'text-wa-green' : ($stateVal === 'ready_warning' ? 'text-amber-500' : 'text-rose-500');
                                            @endphp
                                            <span class="px-3 py-1.5 {{ $stateColor }} text-[9px] font-black uppercase border border-current rounded-lg">
                                                {{ is_object($team->whatsapp_setup_state) && method_exists($team->whatsapp_setup_state, 'label') ? $team->whatsapp_setup_state->label() : str_replace('_', ' ', $team->whatsapp_setup_state) }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full {{ $team->whatsapp_quality_rating === 'GREEN' ? 'bg-wa-green' : ($team->whatsapp_quality_rating === 'YELLOW' ? 'bg-amber-500' : 'bg-rose-500') }} shadow-lg shadow-current/20"></span>
                                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ $team->whatsapp_quality_rating ?? 'NONE' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase">{{ str_replace('TIER_', '', $team->whatsapp_messaging_limit ?? '1K') }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <a href="{{ route('admin.tenants.edit', $team->id) }}" class="text-indigo-600 hover:text-indigo-900 font-black uppercase text-[10px] tracking-widest">
                                                {{ __('Edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- BACKGROUND INFO -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                         <!-- Queues -->
                         <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                             <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">Task Queues</h4>
                             <div class="space-y-4">
                                @foreach($healthData['queues'] as $name => $q)
                                    @if($name !== 'processing_rate')
                                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <span class="text-[10px] font-black text-slate-500 uppercase">{{ ucfirst($name) }}</span>
                                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($q['size']) }}</span>
                                    </div>
                                    @endif
                                @endforeach
                             </div>
                         </div>

                         <!-- Recent Failures -->
                         <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                             <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">Recent Errors</h4>
                             <div class="space-y-4 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($healthData['background_jobs']['failures_by_team'] as $teamName => $count)
                                    <div class="flex items-center justify-between p-4 bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-100 dark:border-rose-900/20">
                                        <span class="text-[10px] font-black text-rose-900 dark:text-rose-100 uppercase truncate pr-4 text-center">{{ $teamName }}</span>
                                        <span class="px-2 py-1 bg-rose-600 text-white text-[9px] font-black rounded-lg">{{ $count }} Errors</span>
                                    </div>
                                @endforeach
                                @if(empty($healthData['background_jobs']['failures_by_team']))
                                    <div class="text-center py-12 opacity-30 text-[10px] font-black uppercase">{{ __('No Errors Found') }}</div>
                                @endif
                             </div>
                         </div>
                    </div>

                </div>

                <!-- SIDE FEED -->
                <div class="xl:col-span-4 space-y-8">
                    
                    <!-- ACTION ITEMS -->
                    <div class="bg-rose-600 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                        <h3 class="text-xl font-black uppercase tracking-tight mb-8">{{ __('Action Required') }}</h3>
                        
                        <div class="space-y-4 mb-6 relative">
                            @foreach($healthData['critical_alerts'] as $alert)
                                <div class="p-4 bg-white/20 rounded-2xl border border-white/20">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[8px] font-black uppercase tracking-widest text-white/60">ALERT: {{ $alert->team->name }}</span>
                                    </div>
                                    <p class="text-xs font-black">{{ $alert->description }}</p>
                                </div>
                            @endforeach

                            @foreach($healthData['wallet_health'] as $wallet)
                                <div class="flex items-center justify-between p-4 bg-white/10 rounded-2xl border border-white/20">
                                    <div class="flex flex-col">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-white/60">{{ __('Low Balance') }}: {{ $wallet->team->name }}</span>
                                        <span class="text-sm font-black">{{ $wallet->currency }} {{ number_format($wallet->balance, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach

                            @if($healthData['critical_alerts']->isEmpty() && $healthData['wallet_health']->isEmpty())
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 text-[9px] font-black uppercase tracking-widest text-center text-white/50">
                                    {{ __('All systems operational') }}
                                </div>
                            @endif
                        </div>

                        <!-- Error Logs -->
                         <div class="relative space-y-3 pt-6 border-t border-white/10">
                             <h4 class="text-[10px] font-black uppercase tracking-widest text-white/60">{{ __('Log Help') }}</h4>
                             @foreach($healthData['background_jobs']['recent_list'] as $failure)
                                 <div class="text-[9PX] font-medium text-white/70 line-clamp-1 border-l border-white/30 pl-3">
                                     {{ Str::limit($failure->exception, 80) }}
                                 </div>
                             @endforeach
                         </div>
                    </div>

                    <!-- LIVE ACTIVITY -->
                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden">
                        <div class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl"></div>
                        <div class="flex items-center justify-between mb-8 relative">
                            <h3 class="text-xl font-black uppercase tracking-tight text-center">{{ __('Live Activity') }}</h3>
                            <span class="flex items-center gap-1.5 text-[8px] font-black text-wa-green uppercase"><span class="w-1.5 h-1.5 rounded-full bg-wa-green animate-ping"></span> {{ __('Checking') }}</span>
                        </div>
                        
                        <div class="space-y-6 relative max-h-[600px] overflow-y-auto pr-4 custom-scrollbar">
                            @forelse($healthData['activity_pulse'] as $event)
                                <div class="border-l-2 border-slate-700 pl-6 py-2 group hover:border-indigo-500 transition-colors">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[8px] font-black {{ $event->category === 'critical' ? 'text-rose-500' : 'text-slate-500' }} uppercase tracking-widest">{{ $event->category ?? 'System' }}</span>
                                        <span class="text-[8px] font-medium text-slate-600 uppercase">{{ \Carbon\Carbon::parse($event->occurred_at)->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-[11px] font-bold text-slate-300 leading-relaxed group-hover:text-white transition-colors">
                                        {{ str_replace('_', ' ', $event->event_type) }}
                                        @if($event->team)
                                            <span class="text-indigo-400 font-black">— {{ optional($event->team)->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-24 opacity-30 text-[10px] font-black uppercase tracking-widest">
                                    {{ __('No activity yet...') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- Master Scripts -->
    <script>
    document.getElementById('customerSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#customerTable tbody tr');
        rows.forEach(row => {
            let name = row.getAttribute('data-name');
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });

    // Auto-refresh every 60 seconds
    setTimeout(function() {
        window.location.reload();
    }, 60000);
    </script>

    <style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</x-app-layout>
