<div wire:init="loadData" class="space-y-8 animate-in fade-in duration-500">
    @if(!$readyToLoad)
        <!-- Skeleton Loading UI -->
        <div class="animate-pulse space-y-12">
            <!-- Header Skeleton -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-slate-200 dark:bg-slate-800 rounded-2xl"></div>
                    <div class="space-y-3">
                        <div class="h-8 w-64 bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                        <div class="h-4 w-48 bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                    </div>
                </div>
                <div class="h-10 w-32 bg-slate-200 dark:bg-slate-800 rounded-2xl"></div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 border border-slate-50 dark:border-slate-800">
                <!-- Stats Grid Skeleton -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    @for($i = 0; $i < 3; $i++)
                        <div class="h-48 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800"></div>
                    @endfor
                </div>

                <!-- Progress & Health Skeleton -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="h-96 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800"></div>
                    <div class="h-96 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800"></div>
                </div>
            </div>
        </div>
    @else
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
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-{{ $integrationStateColor }}-400 opacity-75"></span>
                            <span
                                class="relative inline-flex rounded-full h-3 w-3 bg-{{ $integrationStateColor === 'green' ? 'wa-teal' : $integrationStateColor . '-500' }}"></span>
                        </span>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-bold text-{{ $integrationStateColor }}-700 dark:text-{{ $integrationStateColor }}-400 uppercase tracking-tight">{{ $is_whatsmark_connected ? 'Connected' : 'Disconnected' }}</span>
                            @if($tokenLastValidated)
                                <span class="text-[9px] font-medium text-slate-400 uppercase tracking-widest mt-0.5">Validated
                                    {{ $tokenLastValidated->diffForHumans() }}</span>
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

    <!-- Step Indicators (Progress Stepper) -->
    @if(!$this->team->isWhatsAppActive())
        <div class="mt-8 mb-4">
            <div class="relative flex items-center justify-between max-w-4xl mx-auto px-4">
                {{-- Track Line --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-100 dark:bg-slate-800 -z-10 rounded-full overflow-hidden">
                    @php
                        $progressWidth = match(strtoupper($integrationState)) {
                            'DISCONNECTED', 'NOT_CONFIGURED' => '0%',
                            'AUTHENTICATED' => '33%',
                            'PROVISIONED' => '66%',
                            'READY', 'READY_WARNING', 'ACTIVE' => '100%',
                            default => '0%'
                        };
                    @endphp
                    <div class="h-full bg-wa-teal transition-all duration-700 ease-out" style="width: {{ $progressWidth }}"></div>
                </div>

                <!-- Step 1: Link -->
                <div class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 {{ in_array(strtoupper($integrationState), ['DISCONNECTED', 'NOT_CONFIGURED']) ? 'bg-white border-2 border-slate-200' : 'bg-wa-teal shadow-lg shadow-wa-teal/20 text-white' }}">
                        @if(in_array(strtoupper($integrationState), ['DISCONNECTED', 'NOT_CONFIGURED']))
                            <span class="text-lg font-bold">1</span>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Link Facebook</span>
                </div>

                <!-- Step 2: Discover -->
                <div class="flex flex-col items-center gap-2 group">
                    @php 
                        $isStep2Active = in_array(strtoupper($integrationState), ['AUTHENTICATED', 'PROVISIONED', 'READY', 'READY_WARNING', 'ACTIVE']);
                        $isStep2Done = in_array(strtoupper($integrationState), ['PROVISIONED', 'READY', 'READY_WARNING', 'ACTIVE']);
                    @endphp
                    <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 {{ !$isStep2Active ? 'bg-white border-2 border-slate-200' : ($isStep2Done ? 'bg-wa-teal shadow-lg text-white' : 'bg-white border-2 border-wa-teal text-wa-teal ring-4 ring-wa-teal/5') }}">
                        @if(!$isStep2Done)
                            <span class="text-lg font-bold">2</span>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest {{ $isStep2Active ? 'text-wa-teal' : 'text-slate-500' }}">Discover Account</span>
                </div>

                <!-- Step 3: Activate -->
                <div class="flex flex-col items-center gap-2 group">
                    @php 
                        $isStep3Active = in_array(strtoupper($integrationState), ['READY', 'READY_WARNING', 'ACTIVE']);
                    @endphp
                    <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 {{ !$isStep3Active ? 'bg-white border-2 border-slate-200' : 'bg-wa-teal shadow-lg text-white font-bold' }}">
                        <span class="text-lg font-bold">3</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest {{ $isStep3Active ? 'text-wa-teal' : 'text-slate-500' }}">Activate Setup</span>
                </div>
            </div>
        </div>
    @endif

    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-50 dark:border-slate-800">
        <div class="flex items-center justify-end gap-3 mb-6">
            <button wire:click="runSetupDiagnostics" type="button" class="px-4 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                Run Diagnostics
            </button>
        </div>

        @if($showSetupDiagnostics)
            <div class="mb-8 p-6 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">Setup Diagnostics</div>
                        <div class="mt-1 text-[11px] font-semibold text-slate-600 dark:text-slate-400">Trace: {{ $setupDiagnostics['trace_id'] ?? '-' }}</div>
                    </div>
                    <button wire:click="$set('showSetupDiagnostics', false)" type="button" class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">
                        Close
                    </button>
                </div>

                {{-- Recovery Banner: Provisioned + webhook not subscribed --}}
                @if(($setupDiagnostics['integration_state'] ?? '') === 'provisioned' && !($setupDiagnostics['webhook_subscription']['is_subscribed'] ?? true))
                    <div class="mt-5 p-5 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-black text-amber-800 dark:text-amber-300 uppercase tracking-widest">⚠ Webhook Not Subscribed</div>
                            <p class="mt-1 text-[11px] font-medium text-amber-700 dark:text-amber-400 leading-relaxed max-w-md">
                                Your token is valid and WABA is linked, but webhook subscription could not be verified (common with USER tokens that lack the <code class="bg-amber-100 dark:bg-amber-900/50 px-1 rounded">whatsapp_business_management</code> scope).
                                Click <strong>Force Re-subscribe</strong> to re-POST the subscription and promote state to READY.
                            </p>
                        </div>
                        <button wire:click="forceResubscribeWebhook" wire:loading.attr="disabled"
                            class="flex-shrink-0 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow transition-all hover:scale-105 active:scale-95">
                            <span wire:loading.remove wire:target="forceResubscribeWebhook">Force Re-subscribe</span>
                            <span wire:loading wire:target="forceResubscribeWebhook" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Working...
                            </span>
                        </button>
                    </div>
                @endif

                <pre class="mt-4 text-[11px] leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ json_encode($setupDiagnostics, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
        
        {{-- [STAFF-HARDENING] Critical App Mode Warning --}}
        @if($integrationState === 'ready_warning' || (isset($setupProgress['tier1']['app_mode_warning']) && $setupProgress['tier1']['app_mode_warning']))
            <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 dark:border-amber-800 rounded-3xl flex items-start gap-4 animate-pulse">
                <div class="p-3 bg-amber-500 text-white rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-amber-900 dark:text-amber-200 uppercase tracking-tight">Warning: Meta App in Development Mode</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-1 font-medium leading-relaxed">
                        Your WhatsApp integration is connected via a Meta App in <strong>Development Mode</strong>. Inbound messages will ONLY be received from registered App Developers. Switch your Meta App to <strong>Live Mode</strong> in the Meta Developer Portal to go public.
                    </p>
                </div>
            </div>
        @endif

        @if($is_whatsmark_connected)
                <!-- Critical Alert Banner -->
                <!-- Health & Governance Alert Banner -->
                @if(in_array($integrationState, ['suspended', 'restricted']) || $tokenDaysUntilExpiry < 7 || $wm_quality_rating === 'RED')
                    <div
                        class="mb-10 bg-rose-50 dark:bg-rose-900/20 border-2 border-rose-200 dark:border-rose-800/50 rounded-[2rem] p-6 flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg shadow-rose-100 dark:shadow-none">
                        <div class="flex items-center gap-5 text-center md:text-left">
                            <div class="p-4 bg-rose-500 text-white rounded-2xl shadow-xl shadow-rose-200 dark:shadow-none">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-black text-rose-900 dark:text-rose-100 uppercase tracking-tighter">CRITICAL
                                    GOVERNANCE ALERT</h4>
                                <p class="text-sm font-bold text-rose-700 dark:text-rose-400 opacity-80 uppercase tracking-widest">
                                    @if($wm_quality_rating === 'RED')
                                        Account Quality is RED. Campaign launching is blocked to prevent banning.
                                    @elseif($token_valid && $tokenDaysUntilExpiry < 7)
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                </path>
                                            </svg>
                                            <span>
                                                @if($tokenDaysUntilExpiry <= 0)
                                                    WhatsApp Access Token expires today. Re-connect immediately to restore service.
                                                    <div class="mt-1 text-[10px] font-bold opacity-75 italic lowercase">(Hint: You might be using a temporary token)</div>
                                                @else
                                                    WhatsApp Access Token expires in {{ $tokenDaysUntilExpiry }}
                                                    {{ Str::plural('day', $tokenDaysUntilExpiry) }}. Re-connect soon.
                                                @endif
                                            </span>
                                        </div>
                                    @elseif(!$token_valid)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                            </path>
                                        </svg>
                                        <span>WhatsApp Access Token is invalid or expired. Please re-authenticate.</span>
                                    </div>
                                @elseif($integrationState === 'suspended')
                                    <div class="flex flex-col gap-1">
                                        <span>Your Meta session has expired or permissions have been revoked.</span>
                                        <span class="text-[11px] font-bold opacity-75">REASON: Meta Permission Error (#200) - Missing messaging access for this WABA.</span>
                                    </div>
                                @else
                                    Your account is restricted by Meta.
                                @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            @if($integrationState === 'suspended' || $tokenDaysUntilExpiry < 7)
                                <button onclick="launchWhatsAppSignup(this)"
                                    class="px-8 py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-rose-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                                    {{ $tokenDaysUntilExpiry < 7 ? 'REFRESH CONNECTION' : 'RE-AUTHENTICATE NOW' }}
                                </button>
                            @endif
                            <button wire:click="validateConnection"
                                class="px-8 py-4 bg-white dark:bg-slate-800 text-rose-600 border-2 border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-rose-50 transition-all">
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
                                        <svg class="w-3 h-3 text-slate-300 cursor-help" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div
                                            class="hidden group-hover:block absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-[10px] text-white rounded-lg w-48 shadow-xl">
                                            Meta limits the number of business-initiated conversations you can start in 24h.
                                            Tier 250, 1K (1,000), 10K, 100K, or Unlimited.
                                        </div>
                                    </div>
                                </span>
                            </div>
                            <div class="text-4xl font-bold text-slate-900 dark:text-white">
                                {{ str_replace('TIER_', '', $wm_messaging_limit ?? '1K') }}
                            </div>
                            @if($dailyLimit > 0 && $dailyLimit < 1000000)
                                <div class="text-sm font-bold text-slate-400 mt-1">({{ number_format($dailyLimit) }} / day)</div>
                            @endif
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
                    <div
                        class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-2xl text-amber-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">SETUP
                                        <span class="text-wa-teal">PROGRESS</span>
                                    </h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                        {{ $setupProgress['completed'] }}/{{ $setupProgress['total'] }} STEPS COMPLETED
                                    </p>
                                </div>
                            </div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $setupProgress['progress'] }}%
                            </div>
                        </div>

                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-3 rounded-full mb-10 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-wa-teal to-green-400 h-full rounded-full transition-all duration-1000 ease-out shadow-lg"
                                style="width: {{ $setupProgress['progress'] }}%"></div>
                        </div>

                        <div class="space-y-6">
                            @foreach($setupProgress['steps'] as $step)
                                <div class="flex items-start gap-4 group">
                                    <div class="mt-1 flex-shrink-0">
                                        @if($step['status'] === 'completed')
                                            <div
                                                class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-green-200 dark:shadow-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($step['status'] === 'warning')
                                            <div
                                                class="w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-rose-200 dark:shadow-none animate-pulse">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                    </path>
                                                </svg>
                                            </div>
                                        @elseif($step['status'] === 'pending')
                                            <div
                                                class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-amber-200 dark:shadow-none">
                                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>
                                        @else
                                            <div
                                                class="w-6 h-6 bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center">
                                                <div class="w-2 h-2 bg-current rounded-full"></div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow">
                                        <h4
                                            class="text-sm font-bold {{ $step['status'] === 'completed' ? 'text-slate-900 dark:text-white' : 'text-slate-400' }} uppercase tracking-wider">
                                            {{ $step['title'] }}
                                        </h4>
                                        <p class="text-[11px] font-medium text-slate-500 mt-0.5">{{ $step['description'] }}
                                            @if($step['id'] === 'webhook_setup' && $step['status'] !== 'completed')
                                                <button wire:click="setupWebhook" wire:loading.attr="disabled"
                                                    class="ml-2 text-[10px] font-bold text-wa-teal uppercase hover:underline">
                                                    Fix
                                                </button>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Account Health Widget -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">SYSTEM
                                        <span class="text-wa-teal">HEALTH</span>
                                    </h3>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">REAL-TIME MONITORING
                                    </p>
                                </div>
                            </div>
                            <button wire:click="refreshHealth" wire:loading.attr="disabled"
                                class="p-2 text-slate-400 hover:text-wa-teal transition-colors">
                                <svg wire:loading.class="animate-spin" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <div class="relative flex items-center justify-center mb-10">
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="12" fill="transparent"
                                    class="text-slate-100 dark:text-slate-800" />
                                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="12" fill="transparent"
                                    stroke-dasharray="{{ 2 * pi() * 58 }}"
                                    stroke-dashoffset="{{ (1 - $healthScore / 100) * 2 * pi() * 58 }}"
                                    class="{{ $healthStatus === 'healthy' ? 'text-wa-teal' : ($healthStatus === 'warning' ? 'text-orange-500' : 'text-rose-500') }} transition-all duration-1000 ease-out" />
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $healthScore }}</span>
                                <span
                                    class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $healthStatus }}</span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Token Health Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Access
                                        Token</span>
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-white">{{ $tokenHealthScore }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000"
                                        style="width: {{ $tokenHealthScore }}%"></div>
                                </div>
                            </div>

                            <!-- Quality Rating Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Quality
                                        rating</span>
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-white">{{ $qualityHealthScore }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full transition-all duration-1000"
                                        style="width: {{ $qualityHealthScore }}%"></div>
                                </div>
                            </div>

                            <!-- Messaging Usage Score -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Messaging
                                        usage</span>
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-white">{{ $messagingUsagePercent }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-purple-500 h-full rounded-full transition-all duration-1000"
                                        style="width: {{ $messagingUsagePercent }}%"></div>
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
                                    <x-label for="profile_photo" value="Profile Picture"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <div class="flex items-center gap-4">
                                        @if ($profile_photo)
                                            <img src="{{ $profile_photo->temporaryUrl() }}"
                                                class="w-16 h-16 rounded-xl object-cover border-2 border-wa-teal">
                                        @elseif($profile_picture_url)
                                            <img src="{{ $profile_picture_url }}"
                                                class="w-16 h-16 rounded-xl object-cover border-2 border-slate-200 dark:border-slate-700">
                                        @endif
                                        <input type="file" wire:model="profile_photo" id="profile_photo" accept="image/*"
                                            class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-100 dark:file:bg-slate-800 file:text-wa-teal hover:file:bg-slate-200 transition-all cursor-pointer">
                                    </div>
                                    <x-input-error for="profile_photo" class="mt-2" />
                                </div>

                                <div>
                                    <x-label for="profile_description" value="Business Description"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <textarea id="profile_description" wire:model="profile_description" rows="4"
                                        class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all"
                                        placeholder="Briefly describe your business..."></textarea>
                                    <x-input-error for="profile_description" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="profile_about" value="About Text"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <x-input id="profile_about" type="text" wire:model="profile_about"
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                    <x-input-error for="profile_about" class="mt-2" />
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="profile_email" value="Business Email"
                                            class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                        <x-input id="profile_email" type="email" wire:model="profile_email"
                                            class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                        <x-input-error for="profile_email" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-label for="profile_vertical" value="Industry (Vertical)"
                                            class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                        <select id="profile_vertical" wire:model="profile_vertical"
                                            class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all">
                                            <option value="">Select industry...</option>
                                            <option value="AUTO">Automotive</option>
                                            <option value="BEAUTY">Beauty, Spa &amp; Salon</option>
                                            <option value="APPAREL">Clothing &amp; Apparel</option>
                                            <option value="EDU">Education</option>
                                            <option value="ENTERTAIN">Entertainment</option>
                                            <option value="EVENT_PLAN">Event Planning &amp; Service</option>
                                            <option value="FIN">Finance &amp; Banking</option>
                                            <option value="GROCERY">Food &amp; Grocery</option>
                                            <option value="GOVT">Government &amp; Public Service</option>
                                            <option value="HOTEL">Hotel &amp; Lodging</option>
                                            <option value="HEALTH">Medical &amp; Health</option>
                                            <option value="NONPROFIT">Non-profit</option>
                                            <option value="PROF_SERVICES">Professional Services</option>
                                            <option value="RETAIL">Shopping &amp; Retail</option>
                                            <option value="TRAVEL">Travel &amp; Transportation</option>
                                            <option value="RESTAURANT">Restaurant</option>
                                            <option value="NOT_A_BIZ">Not a Business</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                        <x-input-error for="profile_vertical" class="mt-2" />
                                    </div>
                                </div>
                                <div>
                                    <x-label for="profile_address" value="Business Address"
                                        class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                    <x-input id="profile_address" type="text" wire:model="profile_address"
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 rounded-2xl" />
                                    <x-input-error for="profile_address" class="mt-2" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <x-label value="Websites" class="text-xs font-bold text-slate-500 uppercase" />
                                        <button type="button" wire:click="addWebsite"
                                            class="text-xs font-bold text-green-600 hover:text-green-700">+ ADD WEBSITE</button>
                                    </div>
                                    <x-input-error for="profile_websites.*" class="mb-2" />
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">BUSINESS <span
                                class="text-wa-teal">BEHAVIOR</span></h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <!-- Time & Hours -->
                        <div class="space-y-6">
                            <div>
                                <x-label for="timezone" value="Timezone"
                                    class="text-xs font-bold text-slate-500 uppercase mb-2" />
                                <select id="timezone" wire:model="timezone"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:ring-wa-teal focus:border-wa-teal transition-all">
                                    @foreach($this->timezones as $tz)
                                        <option value="{{ $tz }}">{{ $tz }}</option>
                                    @endforeach
                                </select>
                            </div>


                        </div>

                        <!-- Call Settings -->
                        <div class="space-y-6" x-data="{
                                                  req: {
                                                      cloud_api:   false,
                                                      no_coexist:  false,
                                                      sip_off:     false,
                                                      biz_verified: false,
                                                      waba_ok:     false,
                                                      country_ok:  false,
                                                      no_ivr:      false,
                                                  },
                                                  get allMet() {
                                                      return Object.values(this.req).every(v => v === true);
                                                  }
                                              }">
                            <h4
                                class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                                WhatsApp Calling</h4>

                            {{-- ── Eligibility Checklist ── --}}
                            <div
                                class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-5 space-y-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Before enabling Calling,
                                            confirm all requirements</p>
                                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                                            WhatsApp Business Calling is <strong>allowlist-gated</strong> by Meta. Your phone
                                            number must meet every criterion below, then be
                                            <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/calling"
                                                target="_blank" class="underline font-bold hover:text-amber-900">enabled by
                                                Meta</a>.
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    {{-- Requirement rows --}}
                                    @php
                                        $reqs = [
                                            ['key' => 'cloud_api', 'label' => 'Phone is on Cloud API', 'desc' => 'Not using On-Premises / legacy BSP API.'],
                                            ['key' => 'no_coexist', 'label' => 'Not in Coexistence mode', 'desc' => 'Number is not simultaneously used with the WhatsApp Business App.'],
                                            ['key' => 'sip_off', 'label' => 'SIP calling is disabled', 'desc' => 'SIP must be off so the Graph API calling endpoint works.'],
                                            ['key' => 'biz_verified', 'label' => 'Business is Verified on Meta', 'desc' => 'Meta Business Manager shows your business as verified.'],
                                            ['key' => 'waba_ok', 'label' => 'WhatsApp Business Account in good standing', 'desc' => 'WABA is approved with no policy violations.'],
                                            ['key' => 'country_ok', 'label' => 'Country / region is supported', 'desc' => 'Calling is available in your country (not all regions are launched yet).'],
                                            ['key' => 'no_ivr', 'label' => 'Not an IVR / toll-free number', 'desc' => 'If using toll-free, IVR must allow WhatsApp registration calls through.'],
                                        ];
                                     @endphp

                                    @foreach($reqs as $r)
                                        <label
                                            class="flex items-start gap-3 p-2.5 rounded-xl cursor-pointer hover:bg-amber-100/60 dark:hover:bg-amber-800/30 transition-colors"
                                            :class="req.{{ $r['key'] }} ? 'bg-green-50 dark:bg-green-900/20' : ''">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <input type="checkbox" x-model="req.{{ $r['key'] }}"
                                                    class="rounded border-amber-300 text-green-500 focus:ring-green-500 bg-white dark:bg-slate-900 w-4 h-4">
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-sm font-semibold text-slate-900 dark:text-white leading-tight"
                                                    :class="req.{{ $r['key'] }} ? 'line-through opacity-60' : ''">
                                                    {{ $r['label'] }}
                                                </span>
                                                <span
                                                    class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $r['desc'] }}</span>
                                            </div>
                                            <div class="ml-auto flex-shrink-0 mt-0.5" x-show="req.{{ $r['key'] }}">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Progress bar --}}
                                <div>
                                    <div class="flex justify-between text-xs text-amber-700 dark:text-amber-400 mb-1">
                                        <span>Requirements confirmed</span>
                                        <span x-text="Object.values(req).filter(v=>v).length + ' / {{ count($reqs) }}'"></span>
                                    </div>
                                    <div class="w-full bg-amber-200 dark:bg-amber-800 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                                            :style="'width: ' + (Object.values(req).filter(v=>v).length / {{ count($reqs) }} * 100) + '%'">
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-amber-700 dark:text-amber-400 font-medium" x-show="!allMet">
                                    ⚠️ Confirm all requirements above to unlock calling settings.
                                </p>
                                <p class="text-xs text-green-700 dark:text-green-400 font-bold" x-show="allMet" x-cloak>
                                    ✅ All requirements confirmed. You can now configure calling below, then contact Meta to
                                    enable it on your phone number.
                                </p>
                            </div>

                            {{-- ── Calling Toggles (gated by allMet) ── --}}
                            <div class="space-y-3 transition-all duration-300"
                                :class="allMet ? 'opacity-100' : 'opacity-40 pointer-events-none select-none'">

                                <div x-show="!allMet"
                                    class="text-xs text-center text-slate-400 dark:text-slate-600 italic pb-1">
                                    Complete the checklist above to enable these settings.
                                </div>

                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callingEnabled"
                                            class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable
                                            Calling</span>
                                        <span class="block text-xs text-slate-500">Allow customers to voice/video call you via
                                            WhatsApp.</span>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callButtonVisible"
                                            class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Show Call
                                            Button</span>
                                        <span class="block text-xs text-slate-500">Display the phone icon in the chat
                                            thread.</span>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" wire:model="callbackPermissionEnabled"
                                            class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal bg-white dark:bg-slate-900 w-5 h-5">
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Callback
                                            Requests</span>
                                        <span class="block text-xs text-slate-500">Allow customers to request a callback when
                                            you're unavailable.</span>
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
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
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
                                    <div class="flex gap-2">
                                        <button wire:click="loadAvailablePhoneNumbers"
                                            class="text-xs text-blue-600 hover:text-blue-700 font-bold" title="Refresh List">
                                            REFRESH
                                        </button>
                                        <button
                                            @click="navigator.clipboard.writeText('{{ $wm_default_phone_number_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="text-green-600 hover:text-green-700">
                                            <span x-show="!copied">COPY</span>
                                            <span x-show="copied" class="text-slate-400">COPIED!</span>
                                        </button>
                                    </div>
                                </label>
                                <div
                                    class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 font-mono text-sm text-slate-700 dark:text-slate-300">
                                    @if(empty($wm_default_phone_number_id) && !empty($available_phone_numbers))
                                        <div class="space-y-3">
                                            <p class="text-xs text-amber-600 font-bold">Select a Phone Number so connect:</p>
                                            @foreach($available_phone_numbers as $phone)
                                                <div
                                                    class="flex items-center justify-between p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="font-bold text-slate-900 dark:text-white">{{ $phone['display_phone_number'] ?? $phone['verified_name'] ?? 'Unknown' }}</span>
                                                        <span class="text-xs text-slate-500">ID: {{ $phone['id'] }}</span>
                                                    </div>
                                                    <button
                                                        wire:click="selectPhoneNumber('{{ $phone['id'] }}', '{{ $phone['display_phone_number'] ?? '' }}')"
                                                        class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700">
                                                        SELECT
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        {{ $wm_default_phone_number_id ?? '-' }}
                                    @endif
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
                                    @if(empty($wm_business_account_id) && !empty($available_wabas))
                                        <div class="space-y-3">
                                            <p class="text-xs text-blue-600 font-bold tracking-tight uppercase">Multiple Business Accounts Found:</p>
                                            @foreach($available_wabas as $waba)
                                                <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:border-blue-400">
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-slate-900 dark:text-white">{{ $waba['name'] }}</span>
                                                        <span class="text-[10px] text-slate-500 font-mono tracking-tight uppercase">ID: {{ $waba['id'] }}</span>
                                                    </div>
                                                    <button wire:click="selectWaba('{{ $waba['id'] }}')"
                                                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-black rounded-xl uppercase tracking-widest transition-all hover:scale-105">
                                                        SELECT
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        {{ $wm_business_account_id ?? '-' }}
                                    @endif
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-2xl border border-green-100 dark:border-green-800/30 text-green-700 dark:text-green-400">
                                <div class="flex items-center gap-4">
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
                                @if($lastWebhookReceivedAt)
                                    <div class="text-right flex flex-col items-end">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                            </span>
                                            <span class="text-[9px] font-black uppercase tracking-widest">Heartbeat Pulse</span>
                                        </div>
                                        <span class="text-[9px] font-bold opacity-60 uppercase">{{ $lastWebhookReceivedAt->diffForHumans() }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Business Verification Status Card --}}
                            @php
                                $verificationStatus = strtolower($wm_business_verification_status ?? 'unknown');
                                $verificationConfig = match ($verificationStatus) {
                                    'verified' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-200 dark:border-emerald-700', 'text' => 'text-emerald-700 dark:text-emerald-400', 'badge_bg' => 'bg-emerald-500', 'badge_text' => 'text-white', 'label' => '✅ Verified', 'icon_fill' => '#10b981'],
                                    'pending' => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'border' => 'border-amber-200 dark:border-amber-700', 'text' => 'text-amber-700 dark:text-amber-400', 'badge_bg' => 'bg-amber-400', 'badge_text' => 'text-white', 'label' => '⏳ Pending Review', 'icon_fill' => '#f59e0b'],
                                    'not_verified' => ['bg' => 'bg-rose-50 dark:bg-rose-900/20', 'border' => 'border-rose-200 dark:border-rose-700', 'text' => 'text-rose-700 dark:text-rose-400', 'badge_bg' => 'bg-rose-500', 'badge_text' => 'text-white', 'label' => '❌ Not Verified', 'icon_fill' => '#ef4444'],
                                    'rejected' => ['bg' => 'bg-rose-50 dark:bg-rose-900/20', 'border' => 'border-rose-200 dark:border-rose-700', 'text' => 'text-rose-700 dark:text-rose-400', 'badge_bg' => 'bg-rose-600', 'badge_text' => 'text-white', 'label' => '🚫 Rejected', 'icon_fill' => '#dc2626'],
                                    default => ['bg' => 'bg-slate-50 dark:bg-slate-800/50', 'border' => 'border-slate-200 dark:border-slate-700', 'text' => 'text-slate-500 dark:text-slate-400', 'badge_bg' => 'bg-slate-400', 'badge_text' => 'text-white', 'label' => '— Unknown', 'icon_fill' => '#94a3b8'],
                                };
                            @endphp
                            <div
                                class="flex items-center justify-between gap-4 p-4 {{ $verificationConfig['bg'] }} rounded-2xl border {{ $verificationConfig['border'] }} {{ $verificationConfig['text'] }}">
                                <div class="flex items-center gap-3">
                                    {{-- Shield icon --}}
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <div>
                                        <span class="block text-xs font-black uppercase tracking-widest opacity-60 mb-0.5">Meta
                                            Business Verification</span>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $verificationConfig['badge_bg'] }} {{ $verificationConfig['badge_text'] }} shadow-sm">
                                            {{ $verificationConfig['label'] }}
                                        </span>
                                        @if(in_array($verificationStatus, ['not_verified', 'rejected']))
                                            <a href="https://business.facebook.com/settings/security" target="_blank"
                                                class="block text-[11px] underline mt-1 opacity-80 hover:opacity-100 font-semibold">
                                                Verify your business on Meta →
                                            </a>
                                        @elseif($verificationStatus === 'pending')
                                            <span class="block text-[11px] mt-1 opacity-70 italic">Verification is in review. This
                                                may take a few days.</span>
                                        @endif
                                    </div>
                                </div>
                                <button wire:click="checkBusinessVerification" wire:loading.attr="disabled"
                                    title="Refresh verification status from Meta"
                                    class="flex-shrink-0 p-2 text-current opacity-60 hover:opacity-100 transition-opacity rounded-xl hover:bg-black/5 dark:hover:bg-white/10">
                                    <svg wire:loading wire:target="checkBusinessVerification" class="animate-spin w-4 h-4"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <svg wire:loading.remove wire:target="checkBusinessVerification" class="w-4 h-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>

                            {{-- [STAFF-HARDENING] Circuit Breaker Reset --}}
                            @if($integrationState === 'restricted')
                                <div class="mt-4 p-5 bg-rose-50 dark:bg-rose-900/20 rounded-[2rem] border border-rose-100 dark:border-rose-800 flex flex-col items-center text-center gap-3">
                                    <div class="p-3 bg-rose-600 text-white rounded-2xl shadow-lg shadow-rose-200 dark:shadow-none">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-rose-700 dark:text-rose-400 uppercase tracking-tight">Security Lock Engaged</h4>
                                        <p class="text-[10px] text-rose-600/70 dark:text-rose-400/60 font-medium max-w-xs mx-auto mt-1 leading-relaxed">
                                            Outbound traffic is suspended due to excessive delivery failures. Please verify your Meta account standing.
                                        </p>
                                    </div>
                                    <button wire:click="resetCircuitBreaker" class="mt-2 px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-black rounded-xl uppercase tracking-[0.2em] shadow-lg shadow-rose-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                                        RESET SECURITY LOCK
                                    </button>
                                </div>
                            @endif

                        {{-- Disconnect Section --}}
                        <div class="pt-2 text-right">
                            @if(!$confirmingDisconnect)
                                <button wire:click="confirmDisconnect"
                                    class="text-xs font-bold text-rose-500 hover:text-rose-600 uppercase tracking-widest transition-opacity hover:opacity-80">
                                    &times; DISCONNECT ACCOUNT
                                </button>
                            @else
                                <div
                                    class="flex flex-col items-end gap-3 p-4 bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-200 dark:border-rose-800">
                                    <label class="text-[10px] font-black text-rose-600 uppercase">Type 'DISCONNECT' to
                                        confirm</label>
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="disconnectConfirmation" placeholder="Type here..."
                                            class="text-xs rounded-xl border-rose-200 dark:border-rose-800 bg-white dark:bg-slate-900 focus:ring-rose-500">
                                        <button wire:click="disconnect"
                                            class="px-4 py-2 bg-rose-600 text-white text-[10px] font-black rounded-xl">CONFIRM</button>
                                        <button wire:click="cancelDisconnect" class="text-slate-400 hover:text-slate-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-[9px] text-rose-500 italic mt-1 font-bold uppercase tracking-widest">Warning:
                                        This will stop all active bot automations instantly.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Test Connection Section --}}
                        <div class="mt-10 pt-8 border-t border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-4">Validate Integration</h3>
                            <div class="bg-slate-50 dark:bg-slate-800/40 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mb-4 uppercase tracking-tight">
                                    Verify your connection by sending a real WhatsApp message.
                                </p>
                                <div class="flex flex-col md:flex-row gap-3">
                                    <div class="flex-1 relative">
                                        <input type="text" wire:model="wm_test_message" placeholder="Phone with country code (e.g. 9198XXX)"
                                            class="w-full text-xs font-bold rounded-2xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-wa-teal px-5 py-3">
                                        @error('wm_test_message') <span class="text-[10px] text-rose-500 mt-1 block px-2">{{ $message }}</span> @enderror
                                    </div>
                                    <button wire:click="sendTestMessage" wire:loading.attr="disabled"
                                        class="px-8 py-3 bg-wa-teal hover:bg-wa-teal/90 text-white text-[11px] font-black rounded-2xl shadow-lg shadow-wa-teal/20 uppercase tracking-widest transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
                                        <svg wire:loading wire:target="sendTestMessage" class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4m2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="sendTestMessage">TEST CONNECTION</span>
                                        <span wire:loading wire:target="sendTestMessage">SENDING...</span>
                                    </button>
                                </div>
                            </div>
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
                        <div
                            class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <input type="text" wire:model="registrationPin" placeholder="6-digit PIN" maxlength="6"
                                class="w-32 border-none bg-transparent text-xs font-bold text-center tracking-widest focus:ring-0 text-slate-900 dark:text-white" />

                            <button wire:click="registerNumber" wire:loading.attr="disabled"
                                wire:confirm="Are you sure you want to register this number with the provided PIN?"
                                class="px-4 py-2 bg-wa-teal hover:bg-green-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-green-200 dark:shadow-none transition-all hover:scale-105 disabled:opacity-50 whitespace-nowrap">
                                REGISTER PHONE
                            </button>
                        </div>
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
                        The fastest way to connect. We'll automatically fetch your WABA ID and Token from your WhatsApp
                        account.
                    </p>

                    <div id="fb-login-container">
                        <button onclick="launchWhatsAppSignup(this)" id="fb-login-btn" type="button"
                            class="inline-flex items-center px-8 py-4 border border-transparent text-sm font-bold rounded-2xl shadow-xl text-white bg-wa-brand hover:bg-wa-brand/90 transition-all hover:scale-105 active:scale-95">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            CONNECT WITH WHATSAPP
                        </button>

                        {{-- [STAFF-HARDENING] Resume/Recovery Path --}}
                        @if(in_array(strtoupper($integrationState), ['AUTHENTICATED', 'PROVISIONED']) && empty($available_wabas))
                            <div class="mt-8 flex flex-col items-center gap-4 animate-in slide-in-from-bottom-4 duration-700">
                                <div class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                    </span>
                                    <span class="text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-[0.2em]">Incomplete Session Detected</span>
                                </div>
                                <button wire:click="resumeSetup" wire:loading.attr="disabled"
                                    class="text-xs font-black text-wa-teal hover:text-green-600 transition-colors uppercase tracking-widest flex items-center gap-2">
                                    <svg wire:loading wire:target="resumeSetup" class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span wire:loading.remove wire:target="resumeSetup">↺ Resume discovery & sync</span>
                                    <span wire:loading wire:target="resumeSetup">Working...</span>
                                </button>
                            </div>
                        @endif

                        <div id="https-warning"
                            class="hidden mt-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-2xl text-rose-600 dark:text-rose-400 text-xs max-w-sm mx-auto">
                            <strong class="block mb-1 font-bold italic underline">⚠️ HTTPS REQUIRED</strong>
                            WhatsApp Login requires a secure connection. Please use <strong>ngrok</strong> or Connect
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
                            <div class="flex items-center justify-between mb-2">
                                <x-label for="wm_business_account_id" value="WABA Account ID *"
                                    class="text-xs font-bold text-slate-500 uppercase" />
                                <button type="button" wire:click="discoverManualAccounts" wire:loading.attr="disabled"
                                    class="text-[10px] font-black text-blue-600 hover:text-blue-700 uppercase tracking-widest">
                                    <span wire:loading.remove wire:target="discoverManualAccounts">↺ DISCOVER</span>
                                    <span wire:loading wire:target="discoverManualAccounts">...</span>
                                </button>
                            </div>
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
@endif

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

            window.launchWhatsAppSignup = function (btnElement) {
                if (!checkHttps()) return;

                const fbBtn = btnElement || document.getElementById('fb-login-btn');
                const originalHtml = fbBtn ? fbBtn.innerHTML : '';

                if (!sdkInitialized || typeof FB === 'undefined') {
                    @this.dispatch('notify', { title: 'SDK Loading', message: 'Facebook SDK is still loading. Please wait a moment.', type: 'info' });
                    return;
                }

                if (fbBtn) {
                    fbBtn.disabled = true;
                    fbBtn.innerHTML = 'WORKING...';
                }

                FB.login(function (response) {
                    if (response.authResponse) {
                        const code = response.authResponse.accessToken;
                        // Use window.axios to be safe
                        (window.axios || axios).post('{{ route("whatsapp.onboard.exchange") }}', {
                            access_token: code,
                            waba_id: null // Let backend discover WABA ID from token scopes/account
                        })
                            .then(function (res) {
                                if (res.data.status) {
                                    // Handle cases where multiple WABAs are found and user needs to pick
                                    const wabaId = res.data.waba_id;
                                    const options = res.data.waba_options || [];

                                    console.log('WhatsApp Onboarding: Exchange success', { wabaId, optionsCount: options.length });

                                    @this.dispatch('notify', { title: 'Success', message: 'Onboarding exchange successful', type: 'success' });
                                    @this.handleEmbeddedSuccess(res.data.access_token, wabaId, options);
                                } else {
                                    @this.dispatch('notify', { title: 'Onboarding Error', message: res.data.message, type: 'error' });
                                    if (fbBtn) {
                                        fbBtn.disabled = false;
                                        fbBtn.innerHTML = originalHtml;
                                    }
                                }
                            })
                            .catch(function (error) {
                                console.error('WhatsApp Onboarding External Error:', error);
                                const errorMsg = error.response?.data?.message || error.message || 'Unknown network error';
                                @this.dispatch('notify', { title: 'Linkage Failed', message: errorMsg, type: 'error' });
                                fbBtn.disabled = false;
                                fbBtn.innerHTML = originalHtml;
                            });
                    } else {
                        // [NEW] Handle Cancellation
                        console.log('WhatsApp Onboarding: User cancelled or did not fully authorize.');
                        @this.dispatch('notify', { title: 'Onboarding Cancelled', message: 'You closed the Facebook login window before completing the setup.', type: 'info' });
                        fbBtn.disabled = false;
                        fbBtn.innerHTML = originalHtml;
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
