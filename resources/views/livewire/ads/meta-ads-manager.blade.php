<div class="py-8 bg-gray-50/50 min-h-screen">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header & Breadcrumbs -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-wa-teal font-bold text-sm tracking-widest uppercase">Meta Ads</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-400 text-sm font-medium">Ad Manager</span>
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Ads Overview</h2>
            </div>

            <div class="flex items-center gap-3">
                @if($selectedAdAccount)
                    <a href="{{ route('ads.create', ['adAccountId' => $selectedAdAccount]) }}"
                        class="bg-wa-teal hover:bg-wa-dark text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-wa-teal/20 transition-all flex items-center gap-2 group">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Ad
                    </a>
                @endif
                <button wire:click="$refresh"
                    class="p-2.5 bg-white border border-gray-200 rounded-xl text-gray-500 hover:text-wa-teal hover:border-wa-teal/30 transition-all shadow-sm">
                    <svg class="w-5 h-5 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        @if($error)
            <div
                class="mb-8 bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-2xl flex items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold">Something went wrong</p>
                    <p class="text-sm opacity-80">{{ $error }}</p>
                </div>
            </div>
        @endif

        @if(!$integrationId)
            <div class="max-w-3xl mx-auto py-20">
                <div class="text-center mb-10">
                    <div
                        class="w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-6 rotate-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.826L10.242 9.172a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.101-1.101" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800">Connect Facebook</h3>
                    <p class="text-slate-500 mt-2">Connect your Facebook account to create and manage ads.
                    </p>
                </div>
                <livewire:integrations.meta-embedded-signup />
            </div>
        @else
            <!-- Stats Overview -->
            @if($selectedAdAccount && !empty($summaryStats))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg">Active</span>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Money Spent</p>
                        <h4 class="text-3xl font-black text-slate-900 mt-1">
                            {{ $currency }}{{ number_format($summaryStats['total_spend'], 2) }}</h4>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-wa-soft text-wa-teal rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Messages Started</p>
                        <h4 class="text-3xl font-black text-slate-900 mt-1">
                            {{ number_format($summaryStats['total_conversions']) }}</h4>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Times Seen</p>
                        <h4 class="text-3xl font-black text-slate-900 mt-1">
                            {{ number_format($summaryStats['total_impressions']) }}</h4>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Cost Per Click</p>
                        <h4 class="text-3xl font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($summaryStats['avg_cpc'], 2) }}
                        </h4>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Sidebar: Ad Accounts -->
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 overflow-hidden">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Your Accounts</h3>
                            <span
                                class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-lg text-xs font-bold">{{ count($adAccounts) }}</span>
                        </div>

                        @if(empty($adAccounts))
                            <div class="text-center py-10">
                                <div
                                    class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400">No accounts</p>
                            </div>
                        @else
                            <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($adAccounts as $account)
                                    <button wire:click="selectAdAccount('{{ $account['id'] }}')"
                                        class="w-full text-left px-4 py-3.5 rounded-2xl transition-all flex items-center justify-between group relative overflow-hidden {{ $selectedAdAccount === $account['id'] ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'hover:bg-slate-50 text-slate-600 border border-transparent' }}">
                                        @if($selectedAdAccount === $account['id'])
                                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 to-white/10"></div>
                                        @endif
                                        <div class="relative z-10">
                                            <div class="font-black text-sm truncate max-w-[140px]">{{ $account['name'] }}</div>
                                            <div
                                                class="text-[10px] {{ $selectedAdAccount === $account['id'] ? 'text-white/70' : 'text-slate-400' }} tracking-tight">
                                                ID: {{ $account['id'] }}</div>
                                        </div>
                                        @if($selectedAdAccount === $account['id'])
                                            <div class="relative z-10 bg-white/20 p-1 rounded-full">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Smart Tips -->
                    @if(!empty($smartInsights))
                        <div class="mt-8 pt-8 border-t border-slate-100 animate-in fade-in slide-in-from-bottom-4 duration-700">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Smart Tips</h3>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-black text-wa-teal uppercase tracking-widest">Active</span>
                                    <span class="flex h-1.5 w-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                @foreach($smartInsights as $insight)
                                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm transition-all hover:shadow-md hover:border-wa-teal/30 group">
                                        <div class="flex gap-3">
                                            <div class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center 
                                                {{ $insight['type'] === 'warning' ? 'bg-amber-50 text-amber-600' : '' }}
                                                {{ $insight['type'] === 'critical' ? 'bg-rose-50 text-rose-600' : '' }}
                                                {{ $insight['type'] === 'info' ? 'bg-blue-50 text-blue-600' : '' }}">
                                                @if($insight['type'] === 'warning')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                @elseif($insight['type'] === 'critical')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-black text-slate-900 mb-0.5">{{ $insight['title'] }}</div>
                                                <div class="text-[10px] text-slate-500 leading-relaxed font-bold tracking-tight">{{ $insight['message'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-9">
                    @if(!$selectedAdAccount)
                        <div
                            class="h-full min-h-[500px] flex flex-col items-center justify-center text-center p-12 bg-white rounded-[40px] border border-dashed border-slate-200">
                            <div
                                class="w-24 h-24 bg-slate-50 rounded-[30px] flex items-center justify-center mb-6 text-slate-300 relative">
                                <div
                                    class="absolute -top-2 -right-2 w-8 h-8 bg-wa-teal text-white rounded-full flex items-center justify-center shadow-lg animate-pulse">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                                    </svg>
                                </div>
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16m-7 6h7" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900">Choose an Account</h3>
                            <p class="text-slate-500 mt-2 max-w-xs text-sm leading-relaxed">Pick an account on the left to see how your WhatsApp ads are doing.</p>
                        </div>
                    @else
                        <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden flex flex-col">

                            <!-- Control Bar -->
                            <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex flex-col gap-4">
                                <!-- Navigation & Date -->
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex items-center gap-1.5 p-1 bg-white border border-gray-200 rounded-2xl">
                                        <button wire:click="loadCampaigns"
                                            class="px-4 py-1.5 rounded-xl text-sm font-bold transition-all {{ $viewLevel === 'campaigns' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">Campaigns</button>

                                        @if($selectedCampaign)
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                            <button wire:click="viewAdSets('{{ $selectedCampaign['id'] }}')"
                                                class="px-4 py-1.5 rounded-xl text-sm font-bold transition-all {{ $viewLevel === 'adsets' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
                                                {{ Str::limit($selectedCampaign['name'], 20) }}
                                            </button>
                                        @endif

                                        @if($selectedAdSet)
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                            <span
                                                class="px-4 py-1.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-900">Ads</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center bg-white border border-gray-200 rounded-2xl p-1">
                                            @foreach(['maximum' => 'All', 'today' => 'Today', 'yesterday' => 'Yest.', 'last_7d' => '7D', 'last_30d' => '30D'] as $val => $label)
                                                <button wire:click="setDatePreset('{{ $val }}')"
                                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $datePreset === $val ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:text-gray-600' }}">
                                                    {{ $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter & Search -->
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex-1 relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <input type="text" wire:model.live.debounce.300ms="searchTerm"
                                            placeholder="Search names..."
                                            class="w-full bg-white border-gray-200 rounded-2xl pl-12 pr-4 py-3 text-sm focus:ring-2 focus:ring-wa-teal/20 focus:border-wa-teal transition-all placeholder-slate-400">
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <select wire:model.live="statusFilter"
                                            class="bg-white border-gray-200 rounded-2xl px-4 py-3 text-sm focus:ring-wa-teal/20 focus:border-wa-teal transition-all">
                                            <option value="all">All Status</option>
                                            <option value="active_only">Only Active</option>
                                            <option value="paused_only">Only Paused</option>
                                        </select>

                                        @if(!empty($selectedIds))
                                            <div class="flex items-center gap-2 animate-in slide-in-from-right-4 duration-300">
                                                <button wire:click="bulkAction('resume')"
                                                    class="bg-green-100 text-green-700 px-4 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-green-200 transition-colors">Start</button>
                                                <button wire:click="bulkAction('pause')"
                                                    class="bg-slate-100 text-slate-700 px-4 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">Stop</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Table Container -->
                            <div class="overflow-x-auto min-h-[400px]">
                                @if($viewLevel === 'campaigns')
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-gray-100 bg-slate-50/50">
                                                <th class="px-6 py-4 w-10">
                                                    <input type="checkbox"
                                                        class="rounded border-gray-300 text-wa-teal focus:ring-wa-teal"
                                                        onclick="toggleCheckboxes(this)">
                                                </th>
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Campaign Info</th>
                                                <th class="px-6 py-4 text-right">Spent</th>
                                                <th class="px-6 py-4 text-right">Messages Started</th>
                                                <th class="px-6 py-4 text-right">Return (ROAS)</th>
                                                <th class="px-6 py-4 text-right">Click Rate</th>
                                                <th class="px-6 py-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($campaigns as $campaign)
                                                @php $metrics = $roasMetrics[$campaign['id']] ?? ['roas' => 0]; @endphp
                                                <tr class="hover:bg-slate-50/80 transition-all group">
                                                    <td class="px-6 py-5">
                                                        <input type="checkbox" wire:model.live="selectedIds"
                                                            value="{{ $campaign['id'] }}"
                                                            class="rounded border-gray-300 text-wa-teal focus:ring-wa-teal checkbox-item">
                                                    </td>
                                                    <td class="px-6 py-5">
                                                        <button
                                                            wire:click="toggleStatus('{{ $campaign['id'] }}', '{{ $campaign['status'] }}')"
                                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $campaign['status'] === 'ACTIVE' ? 'bg-wa-teal' : 'bg-gray-200' }}">
                                                            <span
                                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $campaign['status'] === 'ACTIVE' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-5 cursor-pointer"
                                                        wire:click="viewAdSets('{{ $campaign['id'] }}')">
                                                        <div
                                                            class="font-black text-slate-900 group-hover:text-wa-teal transition-colors flex items-center gap-2">
                                                            {{ $campaign['name'] }}
                                                            <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                            </svg>
                                                        </div>
                                                        <div class="text-[10px] text-slate-400 font-medium">ID: {{ $campaign['id'] }} •
                                                            Goal: {{ $campaign['objective'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-5 text-right font-black text-slate-900">
                                                        {{ $currency }}{{ number_format($campaign['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-5 text-right">
                                                        <div class="font-black text-slate-900">
                                                            {{ number_format($metrics['conversions']) }}</div>
                                                        <div class="text-[10px] text-slate-400 font-medium">
                                                            {{ $currency }}{{ number_format($campaign['insights']['cost_per_conversion'] ?? 0, 2) }}/msg
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-5 text-right">
                                                        <span
                                                            class="px-2.5 py-1 rounded-lg text-xs font-black {{ $metrics['roas'] >= 2 ? 'bg-green-50 text-green-600' : 'bg-slate-50 text-slate-500' }}">
                                                            {{ $metrics['roas'] }}x
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-5 text-right text-sm text-slate-600 font-medium">
                                                        {{ number_format($campaign['insights']['ctr'] ?? 0, 2) }}%</td>
                                                    <td class="px-6 py-5">
                                                        <div class="flex items-center gap-2">
                                                            <button wire:click="viewAdSets('{{ $campaign['id'] }}')"
                                                                class="p-2 text-slate-400 hover:text-wa-teal transition-colors"
                                                                title="View Ad Groups">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="px-6 py-20 text-center">
                                                        <div class="max-w-xs mx-auto">
                                                            <div
                                                                class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-300">
                                                                <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                            </div>
                                                            <h4 class="font-black text-slate-900">No campaigns found</h4>
                                                            <p class="text-sm text-slate-400 mt-1">Try changing your search or filters.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>

                                @elseif($viewLevel === 'adsets')
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-gray-100 bg-slate-50/50">
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Ad Group Info</th>
                                                <th class="px-6 py-4 text-right">Daily Limit</th>
                                                <th class="px-6 py-4 text-right">Spent</th>
                                                <th class="px-6 py-4 text-right">Clicks</th>
                                                <th class="px-6 py-4 text-right">Click Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($adSets as $adSet)
                                                <tr class="hover:bg-slate-50/80 transition-all group">
                                                    <td class="px-6 py-5">
                                                        <button
                                                            wire:click="toggleStatus('{{ $adSet['id'] }}', '{{ $adSet['status'] }}')"
                                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $adSet['status'] === 'ACTIVE' ? 'bg-wa-teal' : 'bg-gray-200' }}">
                                                            <span
                                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $adSet['status'] === 'ACTIVE' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-5 cursor-pointer" wire:click="viewAds('{{ $adSet['id'] }}')">
                                                        <div
                                                            class="font-black text-slate-900 group-hover:text-wa-teal transition-colors flex items-center gap-2">
                                                            {{ $adSet['name'] }}
                                                            <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                            </svg>
                                                        </div>
                                                        <div class="text-[10px] text-slate-400 font-medium">ID: {{ $adSet['id'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-5 text-right font-black text-slate-900">
                                                        {{ $currency }}{{ number_format(($adSet['daily_budget'] ?? 0) / 100, 2) }}</td>
                                                    <td class="px-6 py-5 text-right font-black text-slate-900">
                                                        {{ $currency }}{{ number_format($adSet['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-5 text-right font-bold text-slate-600">
                                                        {{ number_format($adSet['insights']['clicks'] ?? 0) }}</td>
                                                    <td class="px-6 py-5 text-right text-sm text-slate-600 font-medium">
                                                        {{ number_format($adSet['insights']['ctr'] ?? 0, 2) }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-6 py-20 text-center text-gray-400 text-sm">No ad groups found here.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>

                                @elseif($viewLevel === 'ads')
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr
                                                class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-gray-100 bg-slate-50/50">
                                                <th class="px-6 py-4">Preview</th>
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Ad Info</th>
                                                <th class="px-6 py-4 text-right">Spent</th>
                                                <th class="px-6 py-4 text-right">Clicks</th>
                                                <th class="px-6 py-4 text-right">Click Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($ads as $ad)
                                                <tr class="hover:bg-slate-50/80 transition-all group">
                                                    <td class="px-6 py-5">
                                                        @if(isset($ad['creative']['thumbnail_url']))
                                                            <div class="relative group/preview w-16 h-16">
                                                                <img src="{{ $ad['creative']['thumbnail_url'] }}"
                                                                    class="w-16 h-16 object-cover rounded-[14px] border border-gray-200 shadow-sm group-hover/preview:scale-105 transition-transform duration-300">
                                                            </div>
                                                        @else
                                                            <div
                                                                class="w-16 h-16 bg-slate-50 border border-dashed border-slate-200 rounded-[14px] flex items-center justify-center text-slate-300">
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-5">
                                                        <button wire:click="toggleStatus('{{ $ad['id'] }}', '{{ $ad['status'] }}')"
                                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $ad['status'] === 'ACTIVE' ? 'bg-wa-teal' : 'bg-gray-200' }}">
                                                            <span
                                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $ad['status'] === 'ACTIVE' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-5">
                                                        <div class="font-black text-slate-900">{{ $ad['name'] }}</div>
                                                        <div class="mt-1 flex items-center gap-2">
                                                            <div class="text-[10px] text-slate-400 font-medium">ID: {{ $ad['id'] }}
                                                            </div>
                                                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                                            <a href="{{ $ad['preview_shareable_link'] ?? '#' }}" target="_blank"
                                                                class="text-[10px] font-bold text-blue-500 hover:text-blue-700 underline tracking-wide">View Ad</a>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-5 text-right font-black text-slate-900">
                                                        {{ $currency }}{{ number_format($ad['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-5 text-right font-bold text-slate-600">
                                                        {{ number_format($ad['insights']['clicks'] ?? 0) }}</td>
                                                    <td class="px-6 py-5 text-right text-sm text-slate-600 font-medium">
                                                        {{ number_format($ad['insights']['ctr'] ?? 0, 2) }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-6 py-20 text-center text-gray-400 text-sm">No ads found here.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                @endif
                            </div>

                            <!-- Bottom Footer -->
                            @if(count($campaigns) > 0 || count($adSets) > 0 || count($ads) > 0)
                                <div class="p-6 bg-slate-50/50 border-t border-gray-100 flex justify-between items-center">
                                    <div class="text-xs text-slate-400 font-bold uppercase tracking-widest">
                                        Showing
                                        {{ count($viewLevel === 'campaigns' ? $campaigns : ($viewLevel === 'adsets' ? $adSets : $ads)) }}
                                        Items
                                    </div>
                                    @if($viewLevel !== 'campaigns')
                                        <button wire:click="goBack"
                                            class="flex items-center gap-2 text-sm font-black text-slate-900 hover:text-wa-teal transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                            </svg>
                                            Back to {{ $viewLevel === 'ads' ? 'Ad Groups' : 'Campaigns' }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script>
        function toggleCheckboxes(source) {
            const checkboxes = document.getElementsByClassName('checkbox-item');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
                // Trigger Livewire update if needed, but wire:model.live usually handles it
                checkboxes[i].dispatchEvent(new Event('change'));
            }
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</div>