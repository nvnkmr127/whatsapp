<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-2xl">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    WHATSAPP <span class="text-wa-teal">CONFIGURATION</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Manage your WhatsApp Business API connection
                    and settings.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($is_whatsmark_connected)
                <div class="flex flex-col items-end gap-2">
                    <div
                        class="flex items-center gap-3 bg-{{ $integrationStateColor }}-50 dark:bg-{{ $integrationStateColor }}-900/20 px-4 py-2 rounded-2xl border border-{{ $integrationStateColor }}-100 dark:border-{{ $integrationStateColor }}-800">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-{{ $integrationStateColor }}-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-{{ $integrationStateColor === 'green' ? 'wa-teal' : $integrationStateColor . '-500' }}"></span>
                        </span>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-{{ $integrationStateColor }}-700 dark:text-{{ $integrationStateColor }}-400 uppercase tracking-tight">{{ $is_whatsmark_connected ? 'Connected' : 'Disconnected' }}</span>
                            @if($tokenLastValidated)
                                <span class="text-[9px] font-medium text-slate-400 uppercase tracking-widest mt-0.5">Validated {{ $tokenLastValidated->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div
                    class="flex items-center gap-3 bg-slate-100 dark:bg-slate-800 px-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="relative flex h-3 w-3">
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-400"></span>
                    </span>
                    <span class="text-sm font-bold text-slate-600 dark:text-slate-400">NOT CONNECTED</span>
                </div>
            @endif
        </div>
    </div>

    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-50 dark:border-slate-800">
        @if($is_whatsmark_connected)
                <!-- Critical Alert Banner -->
                <!-- Health & Governance Alert Banner -->
                @if(in_array($integrationState, ['suspended', 'restricted']) || $tokenDaysUntilExpiry < 7 || $wm_quality_rating === 'RED')
                    <div class="mb-10 bg-rose-50 dark:bg-rose-900/20 border-2 border-rose-200 dark:border-rose-800/50 rounded-[2rem] p-6 flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg shadow-rose-100 dark:shadow-none">
                        <div class="flex items-center gap-5 text-center md:text-left">
                            <div class="p-4 bg-rose-500 text-white rounded-2xl shadow-xl shadow-rose-200 dark:shadow-none">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-black text-rose-900 dark:text-rose-100 uppercase tracking-tighter">CRITICAL GOVERNANCE ALERT</h4>
                                <p class="text-sm font-bold text-rose-700 dark:text-rose-400 opacity-80 uppercase tracking-widest">
                                    @if($wm_quality_rating === 'RED')
                                        Account Quality is RED. Campaign launching is blocked to prevent banning.
                                    @elseif($tokenDaysUntilExpiry < 7)
                                        WhatsApp Access Token expires in {{ $tokenDaysUntilExpiry }} days. Re-connect soon.
                                    @else
                                        {{ $integrationState === 'suspended' ? 'Your Meta session has expired. Messaging is blocked.' : 'Your account is restricted by Meta.' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            @if($integrationState === 'suspended' || $tokenDaysUntilExpiry < 7)
                                <button onclick="launchWhatsAppSignup()" class="px-8 py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-rose-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                                    {{ $tokenDaysUntilExpiry < 7 ? 'REFRESH CONNECTION' : 'RE-AUTHENTICATE NOW' }}
                                </button>
                            @endif
                            <button wire:click="validateConnection" class="px-8 py-4 bg-white dark:bg-slate-800 text-rose-600 border-2 border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-rose-50 transition-all">
                                RE-CHECK 
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Dashboard View -->
                <div class="space-y-12">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                        <!-- Card 1: Message Credits -->
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800 transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-2xl text-green-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Credits</span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span
                                    class="text-4xl font-bold text-slate-900 dark:text-white">{{ number_format($credits) }}</span>
                                <span class="text-slate-400 font-medium">/ {{ number_format($credits_total) }}</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                @php
                                    $percent = $credits_total > 0 ? ($credits / $credits_total) * 100 : 0;
                                @endphp
                                <div
                                    class="flex items-center {{ $percent > 90 ? 'text-rose-500' : 'text-green-600' }} text-sm font-bold">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    {{ number_format($percent, 1) }}% used
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Quality Rating -->
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800 transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl text-blue-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Quality</span>
                            </div>
                            <div class="text-4xl font-bold text-wa-teal uppercase">{{ $wm_quality_rating ?? 'GREEN' }}</div>
                            <p class="mt-4 text-sm text-slate-500 font-medium">Based on Meta health check</p>
                        </div>

                        <!-- Card 3: Messaging Limit -->
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800 transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-2xl text-purple-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                    Limit
                                    <div class="group relative inline-block">
                                        <svg class="w-3 h-3 text-slate-300 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="hidden group-hover:block absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-[10px] text-white rounded-lg w-48 shadow-xl">
                                            Meta limits the number of business-initiated conversations you can start in 24h. 
                                            Tier 1K (1,000), 10K, 100K, or Unlimited.
                                        </div>
                                    </div>
                                </span>
                            </div>
                            <div class="text-4xl font-bold text-slate-900 dark:text-white">{{ $wm_messaging_limit ?? '1K' }}
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm text-slate-500 font-medium">Messages per 24h</span>
                                <div class="flex flex-col items-end gap-2">
                                    <button wire:click="syncInfo" wire:loading.attr="disabled"
                                        class="group flex items-center gap-2 text-xs font-bold text-green-600 hover:text-green-700">
                                        <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>SYNC INFO</span>
                                    </button>
                                    <button wire:click="validateConnection" wire:loading.attr="disabled"
                                        class="group flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700">
                                        <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>RE-VERIFY CONNECTION</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Readiness & Health Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 pt-8">
                    <!-- Setup Progress Widget -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-2xl text-amber-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">SETUP <span class="text-wa-teal">PROGRESS</span></h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $setupProgress['completed'] }}/{{ $setupProgress['total'] }} STEPS COMPLETED</p>
                                </div>
                            </div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $setupProgress['progress'] }}%</div>
                        </div>

                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-3 rounded-full mb-10 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-wa-teal to-green-400 h-full rounded-full transition-all duration-1000 ease-out shadow-lg" style="width: {{ $setupProgress['progress'] }}%"></div>
                        </div>

                        <div class="space-y-6">
                            @foreach($setupProgress['steps'] as $step)
                                <div class="flex items-start gap-4 group">
                                    <div class="mt-1 flex-shrink-0">
                                        @if($step['status'] === 'completed')
                                            <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-green-200 dark:shadow-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($step['status'] === 'warning')
                                            <div class="w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-rose-200 dark:shadow-none animate-pulse">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </div>
                                        @elseif($step['status'] === 'pending')
                                            <div class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-amber-200 dark:shadow-none">
                                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center">
                                                <div class="w-2 h-2 bg-current rounded-full"></div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow">
                                        <h4 class="text-sm font-bold {{ $step['status'] === 'completed' ? 'text-slate-900 dark:text-white' : 'text-slate-400' }} uppercase tracking-wider">{{ $step['title'] }}</h4>
                                        <p class="text-[11px] font-medium text-slate-500 mt-0.5">{{ $step['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Account Health Widget -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">SYSTEM <span class="text-wa-teal">HEALTH</span></h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">REAL-TIME MONITORING</p>
                                </div>
                            </div>
                            <button wire:click="refreshHealth" wire:loading.attr="disabled" class="p-2 text-slate-400 hover:text-wa-teal transition-colors">
                                <svg wire:loading.class="animate-spin" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="relative flex items-center justify-center mb-10">
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="12" fill="transparent" 
                                        stroke-dasharray="{{ 2 * pi() * 58 }}" 
                                        stroke-dashoffset="{{ (1 - $healthScore/100) * 2 * pi() * 58 }}" 
                                        class="{{ $healthStatus === 'healthy' ? 'text-wa-teal' : ($healthStatus === 'warning' ? 'text-orange-500' : 'text-rose-500') }} transition-all duration-1000 ease-out" />
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $healthScore }}</span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $healthStatus }}</span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Token Health Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Access Token</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $tokenHealthScore }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" style="width: {{ $tokenHealthScore }}%"></div>
                                </div>
                            </div>

                            <!-- Quality Rating Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Quality rating</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $qualityHealthScore }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full transition-all duration-1000" style="width: {{ $qualityHealthScore }}%"></div>
                                </div>
                            </div>

                            <!-- Messaging Usage Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Messaging usage</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $messagingUsagePercent }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-purple-500 h-full rounded-full transition-all duration-1000" style="width: {{ $messagingUsagePercent }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Center Section -->
                <div class="py-8">
                    <livewire:teams.whatsapp-alerts />
                </div>

                <!-- Business Profile Section -->
                <div class="py-12">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            @if($profile_picture_url)
                                <img src="{{ $profile_picture_url }}"
                                    class="w-12 h-12 rounded-xl object-cover shadow-md border-2 border-white dark:border-slate-800"
                                    alt="Business DP">
                            @else
                                <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            @endif
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">BUSINESS <span
                                    class="text-wa-teal">PROFILE</span></h3>
                        </div>

                        @if(!$is_editing_profile)
                            <button wire:click="editProfile"
                                class="text-xs font-bold text-green-600 hover:text-green-700 uppercase tracking-widest bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-xl transition-all hover:scale-105">
                                EDIT PROFILE
                            </button>
                        @endif
                    </div>

                    @if($is_editing_profile)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <x-label for="profile_description" value="Business Description"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <textarea id="profile_description" wire:model="profile_description" rows="4"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all"
                                        placeholder="Briefly describe your business..."></textarea>
                                </div>
                                <div>
                                    <x-label for="profile_about" value="About Text"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <x-input id="profile_about" type="text" wire:model="profile_about"
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="profile_email" value="Business Email"
                                            class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                        <x-input id="profile_email" type="email" wire:model="profile_email"
                                            class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                    </div>
                                    <div>
                                        <x-label for="profile_vertical" value="Industry (Vertical)"
                                            class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                        <select id="profile_vertical" wire:model="profile_vertical"
                                            class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all">
                                            <option value="">Select industry...</option>
                                            <option value="AUTO">Automotive</option>
                                            <option value="BEAUTY">Beauty & Personal Care</option>
                                            <option value="APPAREL">Clothing & Apparel</option>
                                            <option value="EDU">Education</option>
                                            <option value="ENTERTAIN">Entertainment</option>
                                            <option value="EVENT_PLAN">Event Planning</option>
                                            <option value="FINANCE">Finance & Banking</option>
                                            <option value="FOOD">Food & Beverage</option>
                                            <option value="GOVT">Government</option>
                                            <option value="HOTEL">Hotel & Accommodations</option>
                                            <option value="HEALTH">Health & Medical</option>
                                            <option value="NON_PROFIT">Non-profit</option>
                                            <option value="PROF_SERVICES">Professional Services</option>
                                            <option value="RETAIL">Retail</option>
                                            <option value="TRAVEL">Travel & Transportation</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <x-label for="profile_address" value="Business Address"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <x-input id="profile_address" type="text" wire:model="profile_address"
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <x-label value="Websites" class="text-xs font-bold text-slate-500 uppercase" />
                                        <button type="button" wire:click="addWebsite"
                                            class="text-xs font-bold text-green-600 hover:text-green-700">+ ADD WEBSITE</button>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach($profile_websites as $index => $website)
                                            <div class="flex items-center gap-2">
                                                <x-input type="url" wire:model="profile_websites.{{ $index }}"
                                                    class="flex-grow bg-slate-50 dark:bg-slate-800/50 rounded-2xl"
                                                    placeholder="https://..." />
                                                <button type="button" wire:click="removeWebsite({{ $index }})"
                                                    class="p-2 text-slate-400 hover:text-rose-500">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-4">
                            <button wire:click="cancelEdit"
                                class="text-xs font-bold text-slate-500 hover:text-slate-600 uppercase tracking-widest px-6 py-3">
                                CANCEL
                            </button>
                            <x-button wire:click="updateBusinessProfile" wire:loading.attr="disabled"
                                class="bg-slate-900 dark:bg-white dark:text-slate-900 rounded-2xl px-8 shadow-lg transition-all hover:scale-105">
                                <span wire:loading.remove>SAVE CHANGES</span>
                                <span wire:loading class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white dark:text-slate-900"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    SAVING...
                                </span>
                            </x-button>
                        </div>
                    @else
                        <!-- View Mode -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-8">
                                <div>
                                    <label
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 block">Description</label>
                                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                        {{ $profile_description ?: 'No description provided.' }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Status
                                            (About)</label>
                                        <span
                                            class="inline-flex items-center px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-sm text-slate-600 dark:text-slate-400 font-medium">
                                            {{ $profile_about ?: 'Hey there! I am using WhatsApp.' }}
                                        </span>
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Industry</label>
                                        <span
                                            class="inline-flex items-center px-3 py-1 bg-green-50 dark:bg-green-900/20 rounded-lg text-sm text-green-600 dark:text-green-400 font-bold">
                                            {{ $profile_vertical ?: 'NOT SET' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-8">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <label
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Business
                                            Email</label>
                                        <p class="text-slate-900 dark:text-white font-bold">{{ $profile_email ?: 'Not provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Address</label>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed">
                                            {{ $profile_address ?: 'Not provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Websites</label>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse($profile_websites as $website)
                                                <a href="{{ $website }}" target="_blank"
                                                    class="text-sm font-bold text-green-600 hover:underline flex items-center gap-1 bg-green-50 dark:bg-green-900/10 px-3 py-1 rounded-lg">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.82a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.103-1.103">
                                                        </path>
                                                    </svg>
                                                    {{ str_replace(['http://', 'https://'], '', $website) }}
                                                </a>
                                            @empty
                                                <span class="text-sm text-slate-400 italic">No websites linked</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800"></div>

                <!-- Business Behavior Section (Merged) -->
                <div class="py-12">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">BUSINESS <span class="text-wa-teal">BEHAVIOR</span></h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                         <!-- Time & Hours -->
                         <div class="space-y-6">
                             <div>
                                <x-label for="timezone" value="Timezone" class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                <select id="timezone" wire:model="timezone"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all">
                                    @foreach($this->timezones as $tz)
                                        <option value="{{ $tz }}">{{ $tz }}</option>
                                    @endforeach
                                </select>
                             </div>

                             <div>
                                <x-label value="Business Hours (Mon-Fri)" class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                <div class="flex items-center gap-2">
                                    <input type="time" wire:model="openTime"
                                        class="rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal">
                                    <span class="text-slate-400 font-bold text-xs">TO</span>
                                    <input type="time" wire:model="closeTime"
                                        class="rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal">
                                </div>
                                <p class="mt-2 text-[11px] text-slate-400">Messages received outside these hours may trigger the Away Message.</p>
                             </div>

                             <div class="pt-4">
                                <label class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" wire:model="awayMessageEnabled" class="mt-1 rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Away Message</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Auto-reply when closed.</span>
                                    </div>
                                </label>

                                @if($awayMessageEnabled)
                                    <div class="mt-4 animate-in fade-in slide-in-from-top-2">
                                        <x-label for="awayMessage" value="Away Message Content" class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                        <textarea wire:model="awayMessage" rows="3"
                                            class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all"></textarea>
                                    </div>
                                @endif
                             </div>
                         </div>

                         <!-- Call Settings -->
                         <div class="space-y-6">
                             <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">WhatsApp Calling</h4>
                             
                             <div class="space-y-3">
                                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callingEnabled" class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Calling</span>
                                        <span class="block text-xs text-slate-500">Allow customers to voice/video call you.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callButtonVisible" class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Show Call Button</span>
                                        <span class="block text-xs text-slate-500">Display the phone icon in the chat thread.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="syncCallHours" class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Sync Business Hours</span>
                                        <span class="block text-xs text-slate-500">Restrict calls to the business hours defined on the left.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callbackPermissionEnabled" class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Callback Requests</span>
                                        <span class="block text-xs text-slate-500">Allow customers to request a callback when unavailable.</span>
                                    </div>
                                </label>
                             </div>
                         </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button wire:click="updateBehaviorSettings" wire:loading.attr="disabled"
                            class="bg-slate-900 dark:bg-white dark:text-slate-900 rounded-2xl px-8 py-3 shadow-lg transition-all hover:scale-105 font-bold text-xs uppercase tracking-widest text-white">
                            <span wire:loading.remove>SAVE BEHAVIOR SETTINGS</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                SAVING...
                            </span>
                        </button>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800"></div>

                <!-- API Credentials & Connection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-8">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">API <span
                                    class="text-wa-teal">CREDENTIALS</span></h3>
                        </div>

                        <div class="space-y-6">
                            <div x-data="{ copied: false }" class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase flex justify-between">
                                    Phone Number ID
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $wm_default_phone_number_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="text-green-600 hover:text-green-700">
                                        <span x-show="!copied">COPY</span>
                                        <span x-show="copied" class="text-slate-400">COPIED!</span>
                                    </button>
                                </label>
                                <div
                                    class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 font-mono text-sm text-slate-700 dark:text-slate-300">
                                    {{ $wm_default_phone_number_id ?? '-' }}
                                </div>
                            </div>

                            <div x-data="{ copied: false }" class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase flex justify-between">
                                    WABA Account ID
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $wm_business_account_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="text-green-600 hover:text-green-700">
                                        <span x-show="!copied">COPY</span>
                                        <span x-show="copied" class="text-slate-400">COPIED!</span>
                                    </button>
                                </label>
                                <div
                                    class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 font-mono text-sm text-slate-700 dark:text-slate-300">
                                    {{ $wm_business_account_id ?? '-' }}
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-4 p-4 bg-green-50 dark:bg-green-900/10 rounded-2xl border border-green-100 dark:border-green-800/30 text-green-700 dark:text-green-400">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <div class="text-sm font-medium">
                                    <span class="block font-bold">{{ $wm_phone_display ?? '-' }}</span>
                                    <span class="text-xs opacity-70 italic">Verified display name:
                                        {{ $wm_verified_name ?? 'Not Verified' }}</span>
                                </div>
                            </div>

                            <div class="pt-2 text-right">
                                @if(!$confirmingDisconnect)
                                    <button wire:click="confirmDisconnect"
                                        class="text-xs font-bold text-rose-500 hover:text-rose-600 uppercase tracking-widest transition-opacity hover:opacity-80">
                                        &times; DISCONNECT ACCOUNT
                                    </button>
                                @else
                                    <div class="flex flex-col items-end gap-3 p-4 bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-200 dark:border-rose-800">
                                        <label class="text-[10px] font-black text-rose-600 uppercase">Type 'DISCONNECT' to confirm</label>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="disconnectConfirmation" placeholder="Type here..." 
                                                class="text-xs rounded-xl border-rose-200 dark:border-rose-800 bg-white dark:bg-slate-900 focus:ring-rose-500">
                                            <button wire:click="disconnect" class="px-4 py-2 bg-rose-600 text-white text-[10px] font-black rounded-xl">CONFIRM</button>
                                            <button wire:click="cancelDisconnect" class="text-slate-400 hover:text-slate-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="text-[9px] text-rose-500 italic mt-1 font-bold uppercase tracking-widest">Warning: This will stop all active bot automations instantly.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">WEBHOOK <span
                                    class="text-wa-teal">SETTINGS</span></h3>
                        </div>

                        <div class="space-y-6">
                            <!-- Inbound Webhook -->
                            <div class="space-y-4">
                                <div x-data="{ copied: false }" class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase flex justify-between">
                                        Inbound Webhook URL
                                        <button
                                            @click="navigator.clipboard.writeText('{{ route('api.webhook.whatsapp') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="text-green-600 hover:text-green-700">
                                            <span x-show="!copied">COPY URL</span>
                                            <span x-show="copied" class="text-slate-400">COPIED!</span>
                                        </button>
                                    </label>
                                    <div
                                        class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-mono text-slate-500 break-all select-all">
                                        {{ route('api.webhook.whatsapp') }}
                                    </div>
                                </div>

                                <div x-data="{ copied: false }" class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase flex justify-between">
                                        Verify Token
                                        <button
                                            @click="navigator.clipboard.writeText('{{ $webhook_verify_token }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="text-green-600 hover:text-green-700">
                                            <span x-show="!copied">COPY TOKEN</span>
                                            <span x-show="copied" class="text-slate-400">COPIED!</span>
                                        </button>
                                    </label>
                                    <div
                                        class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-mono text-slate-500 select-all">
                                        {{ $webhook_verify_token }}
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                                <x-label for="outbound_webhook_url" value="Outbound Webhook (Event Forwarding)"
                                    class="text-xs font-bold text-slate-500 uppercase mb-3" />
                                <div class="flex gap-2">
                                    <x-input id="outbound_webhook_url" type="url" wire:model="outbound_webhook_url"
                                        class="flex-grow bg-slate-50 dark:bg-slate-800/50 rounded-2xl"
                                        placeholder="https://yourdomain.com/webhook" />
                                    <button wire:click="updateOutboundWebhook" wire:loading.attr="disabled"
                                        class="bg-slate-900 dark:bg-white dark:text-slate-900 rounded-2xl px-6 font-bold text-xs uppercase tracking-widest shadow-md transition-all hover:scale-105">
                                        SAVE
                                    </button>
                                </div>
                                <p class="mt-3 text-[11px] text-slate-400 font-medium leading-relaxed">
                                    All incoming WhatsApp events will be forwarded to this URL via POST. Use this for custom
                                    integrations.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800"></div>

                <!-- Advanced Actions -->
                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-6 p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                    <div class="space-y-1 text-center md:text-left">
                        <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight">META MANAGER <span
                                class="text-wa-teal">PORTAL</span></h4>
                        <p class="text-sm text-slate-500 font-medium italic">Configure templates, messages, and business hours
                            directly on Meta.</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="https://business.facebook.com/wa/manage/home/?waba_id={{ $wm_business_account_id }}"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            META BUSINESS MANAGER
                        </a>
                        <button wire:click="registerNumber" wire:loading.attr="disabled"
                            wire:confirm="Default PIN is 123456. Are you sure you want to re-register?"
                            class="px-6 py-3 bg-wa-teal hover:bg-green-600 text-white rounded-2xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-green-200 dark:shadow-none transition-all hover:scale-105 disabled:opacity-50">
                            REGISTER PHONE
                        </button>
                    </div>
                </div>
            </div>
        @else
        <!-- Connect Form -->
        <div class="max-w-4xl mx-auto py-12">
            <div class="text-center mb-12">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight mb-2">CONNECT YOUR
                    <span class="text-wa-teal">ACCOUNT</span>
                </h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium font-serif">Link your Meta Business Account to
                    start sending messages.</p>
            </div>

            <div class="space-y-12">
                <!-- Recommended: Facebook Login -->
                <div
                    class="bg-blue-50/50 dark:bg-blue-900/10 rounded-[2rem] p-10 border border-blue-100 dark:border-blue-900/30 text-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4">
                        <span
                            class="bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest">Recommended</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-800 dark:text-blue-100 mb-4 uppercase tracking-tighter">Embedded
                        Signup Flow</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-8 max-w-md mx-auto leading-relaxed">
                        The fastest way to connect. We'll automatically fetch your WABA ID and Token from your Facebook
                        account.
                    </p>

                    <div id="fb-login-container">
                        <button onclick="launchWhatsAppSignup()" id="fb-login-btn" type="button"
                            class="inline-flex items-center px-8 py-4 border border-transparent text-sm font-bold rounded-2xl shadow-xl text-white bg-[#1877F2] hover:bg-[#166fe5] transition-all hover:scale-105 active:scale-95">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.791-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                            CONNECT WITH FACEBOOK
                        </button>
                        <div id="https-warning"
                            class="hidden mt-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-2xl text-rose-600 dark:text-rose-400 text-xs max-w-sm mx-auto">
                            <strong class="block mb-1 font-bold italic underline">⚠️ HTTPS REQUIRED</strong>
                            Facebook Login requires a secure connection. Please use <strong>ngrok</strong> or Connect
                            Manually below.
                        </div>
                    </div>
                </div>

                <div class="relative flex items-center">
                    <div class="flex-grow border-t border-slate-100 dark:border-slate-800"></div>
                    <span
                        class="flex-shrink-0 mx-6 text-slate-300 dark:text-slate-600 text-[10px] font-bold uppercase tracking-[0.3em]">MANUAL
                        CONFIGURATION</span>
                    <div class="flex-grow border-t border-slate-100 dark:border-slate-800"></div>
                </div>

                <!-- Manual Form -->
                <form wire:submit.prevent="connect" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <x-label for="wm_fb_app_id" value="Meta App ID"
                                class="text-xs font-bold text-slate-500 uppercase mb-2" />
                            <x-input id="wm_fb_app_id" type="text" wire:model="wm_fb_app_id"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" placeholder="Optional" />
                            <x-input-error for="wm_fb_app_id" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="wm_fb_app_secret" value="Meta App Secret"
                                class="text-xs font-bold text-slate-500 uppercase mb-2" />
                            <x-input id="wm_fb_app_secret" type="password" wire:model="wm_fb_app_secret"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" placeholder="Optional" />
                            <x-input-error for="wm_fb_app_secret" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <x-label for="wm_business_account_id" value="WABA Account ID *"
                                class="text-xs font-bold text-slate-500 uppercase mb-2" />
                            <x-input id="wm_business_account_id" type="text" wire:model="wm_business_account_id"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl"
                                placeholder="WABA ID from Meta" />
                            <x-input-error for="wm_business_account_id" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="wm_default_phone_number_id" value="Phone Number ID"
                                class="text-xs font-bold text-slate-500 uppercase mb-2" />
                            <x-input id="wm_default_phone_number_id" type="text" wire:model="wm_default_phone_number_id"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl"
                                placeholder="Auto-detected if blank" />
                            <x-input-error for="wm_default_phone_number_id" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-label for="wm_access_token" value="System Access Token *"
                            class="text-xs font-bold text-slate-500 uppercase mb-2" />
                        <x-input id="wm_access_token" type="password" wire:model="wm_access_token"
                            class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" placeholder="EAAB..." />
                        <x-input-error for="wm_access_token" class="mt-2" />
                        <div class="mt-3 flex items-start gap-2 text-[10px] text-slate-400 font-medium">
                            <svg class="w-4 h-4 text-slate-300 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Permissions required: whatsapp_business_management, whatsapp_business_messaging</span>
                        </div>
                    </div>

                    <div class="pt-4 text-center">
                        <x-button wire:loading.attr="disabled"
                            class="w-full justify-center py-5 bg-slate-900 dark:bg-white dark:text-slate-900 rounded-[2rem] shadow-2xl transition-all hover:scale-[1.02] active:scale-95">
                            <span wire:loading.remove class="uppercase tracking-widest font-bold">CONNECT ACCOUNT</span>
                            <span wire:loading class="flex items-center uppercase tracking-widest font-bold">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                VALIDATING...
                            </span>
                        </x-button>
                        <p class="mt-4 text-[10px] text-slate-400 uppercase tracking-widest font-bold">By connecting, you
                            agree to Meta's WhatsApp Terms.</p>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div><!-- End Main Card -->

<script>
    document.addEventListener('livewire:initialized', () => {
        let sdkInitialized = false;

        const checkHttps = () => {
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                const fbBtn = document.getElementById('fb-login-btn');
                if (fbBtn) {
                    fbBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    fbBtn.disabled = true;
                    fbBtn.innerHTML = 'HTTPS REQUIRED';
                }
                const warning = document.getElementById('https-warning');
                if (warning) {
                    warning.classList.remove('hidden');
                }
                return false;
            }
            return true;
        };

        if (typeof launchWhatsAppSignup !== 'function') {
            window.fbAsyncInit = function () {
                FB.init({
                    appId: '{{ config("services.facebook.client_id") }}',
                    autoLogAppEvents: true,
                    xfbml: true,
                    version: 'v21.0'
                });
                sdkInitialized = true;
                console.log('FB SDK Initialized');
            };

            (function (d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) { return; }
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));

            window.launchWhatsAppSignup = function () {
                if (!checkHttps()) return;

                if (!sdkInitialized || typeof FB === 'undefined') {
                    alert('Facebook SDK is still loading. Please wait a moment.');
                    return;
                }

                FB.login(function (response) {
                    if (response.authResponse) {
                        const code = response.authResponse.accessToken;
                        // Use window.axios to be safe
                        (window.axios || axios).post('{{ route("whatsapp.onboard.exchange") }}', { 
                            access_token: code,
                            waba_id: response.authResponse.userID //userID might not be wabaId, but the scope includes it
                        })
                        .then(function (res) {
                            if (res.data.status) {
                                @this.handleEmbeddedSuccess(res.data.access_token, res.data.waba_id);
                            } else {
                                alert('Error: ' + res.data.message);
                            }
                        })
                        .catch(function (error) {
                            console.error(error);
                            alert('System error during token exchange. Check console for details.');
                        });
                    }
                }, {
                    scope: 'whatsapp_business_management, whatsapp_business_messaging, business_management',
                    extras: { 
                        feature: 'whatsapp_embedded_signup', 
                        sessionInfoVersion: '2' 
                    }
                });
            };
        }

        checkHttps();
    });
</script>
</div>