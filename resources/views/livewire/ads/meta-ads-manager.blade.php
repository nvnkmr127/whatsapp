<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Meta Ads Manager</h2>
                <p class="text-gray-600">Create and manage "Click to WhatsApp" ads directly from your dashboard.</p>
            </div>
            @if($selectedAdAccount)
                <a href="{{ route('ads.create', ['adAccountId' => $selectedAdAccount]) }}" class="bg-wa-teal hover:bg-wa-dark text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-wa-teal/20 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Ad
                </a>
            @endif
        </div>

        @if($error)
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $error }}</span>
            </div>
        @endif

        @if(!$integrationId)
            <!-- Empty State / Connect Prompt -->
            <div class="max-w-3xl mx-auto">
                <livewire:integrations.meta-embedded-signup />
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <!-- Sidebar: Ad Accounts -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Ad Accounts</h3>
                        
                        @if(empty($adAccounts))
                            <div class="text-center py-8 text-gray-400 text-sm">No ad accounts found.</div>
                        @else
                            <div class="space-y-2">
                                @foreach($adAccounts as $account)
                                    <button wire:click="selectAdAccount('{{ $account['id'] }}')" 
                                        class="w-full text-left px-4 py-3 rounded-xl transition-all flex items-center justify-between group {{ $selectedAdAccount === $account['id'] ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'hover:bg-gray-50 text-gray-600' }}">
                                        <div>
                                            <div class="font-bold text-sm">{{ $account['name'] }}</div>
                                            <div class="text-xs opacity-70">ID: {{ $account['id'] }}</div>
                                        </div>
                                        @if($selectedAdAccount === $account['id'])
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3">
                    @if(!$selectedAdAccount)
                        <div class="h-full flex flex-col items-center justify-center text-center p-12 bg-white rounded-2xl border border-dashed border-gray-200">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Select an Ad Account</h3>
                            <p class="text-gray-500 text-sm">Choose an account from the sidebar to view campaigns.</p>
                        </div>
                    @else
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            
                            <!-- Toolbar: Breadcrumbs & Filters -->
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                                <div class="flex items-center gap-2 text-sm">
                                    <button wire:click="loadCampaigns" class="{{ $viewLevel === 'campaigns' ? 'font-bold text-gray-900' : 'text-gray-500 hover:text-blue-600' }}">Campaigns</button>
                                    
                                    @if($selectedCampaign)
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <button wire:click="viewAdSets('{{ $selectedCampaign['id'] }}')" class="{{ $viewLevel === 'adsets' ? 'font-bold text-gray-900' : 'text-gray-500 hover:text-blue-600' }}">
                                            {{ $selectedCampaign['name'] }} (Ad Sets)
                                        </button>
                                    @endif

                                    @if($selectedAdSet)
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span class="font-bold text-gray-900">Ads</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3">
                                    <select wire:change="setDatePreset($event.target.value)" class="text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5">
                                        <option value="today" {{ $datePreset === 'today' ? 'selected' : '' }}>Today</option>
                                        <option value="yesterday" {{ $datePreset === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                        <option value="last_7d" {{ $datePreset === 'last_7d' ? 'selected' : '' }}>Last 7 Days</option>
                                        <option value="last_30d" {{ $datePreset === 'last_30d' ? 'selected' : '' }}>Last 30 Days</option>
                                        <option value="maximum" {{ $datePreset === 'maximum' ? 'selected' : '' }}>Lifetime</option>
                                    </select>
                                    
                                    <button wire:click="$refresh" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="Refresh">
                                        <svg class="w-5 h-5 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content Tables -->
                            <div class="overflow-x-auto">
                                @if($viewLevel === 'campaigns')
                                    <!-- Campaigns Table -->
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="text-xs font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/30">
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Campaign Name</th>
                                                <th class="px-6 py-4 text-right">Spend</th>
                                                <th class="px-6 py-4 text-right">Impr.</th>
                                                <th class="px-6 py-4 text-right">Clicks</th>
                                                <th class="px-6 py-4 text-right">CTR</th>
                                                <th class="px-6 py-4 text-right text-blue-600">ROAS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($campaigns as $campaign)
                                                @php $metrics = $roasMetrics[$campaign['id']] ?? ['revenue' => 0, 'roas' => 0]; @endphp
                                                <tr wire:click="viewAdSets('{{ $campaign['id'] }}')" class="hover:bg-gray-50/50 transition-colors group cursor-pointer">
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize
                                                            {{ $campaign['status'] === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ strtolower($campaign['status']) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $campaign['name'] }}</div>
                                                        <div class="text-xs text-gray-400">ID: {{ $campaign['id'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-mono text-sm text-gray-700">${{ number_format($campaign['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($campaign['insights']['impressions'] ?? 0) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($campaign['insights']['clicks'] ?? 0) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($campaign['insights']['ctr'] ?? 0, 2) }}%</td>
                                                    <td class="px-6 py-4 text-right">
                                                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $metrics['roas'] >= 2 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ $metrics['roas'] }}x
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No campaigns found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                
                                @elseif($viewLevel === 'adsets')
                                    <!-- Ad Sets Table -->
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="text-xs font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/30">
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Ad Set Name</th>
                                                <th class="px-6 py-4 text-right">Budget</th>
                                                <th class="px-6 py-4 text-right">Spend</th>
                                                <th class="px-6 py-4 text-right">Impr.</th>
                                                <th class="px-6 py-4 text-right">CTR</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($adSets as $adSet)
                                                <tr wire:click="viewAds('{{ $adSet['id'] }}')" class="hover:bg-gray-50/50 transition-colors group cursor-pointer">
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize
                                                            {{ $adSet['status'] === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ strtolower($adSet['status']) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $adSet['name'] }}</div>
                                                        <div class="text-xs text-gray-400">Daily: {{ $adSet['daily_budget'] ? '$'.($adSet['daily_budget']/100) : 'N/A' }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">
                                                        {{ $adSet['daily_budget'] ? '$'.number_format($adSet['daily_budget']/100, 2) : 'Lifetime' }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-mono text-sm text-gray-700">${{ number_format($adSet['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($adSet['insights']['impressions'] ?? 0) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($adSet['insights']['ctr'] ?? 0, 2) }}%</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No ad sets found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>

                                @elseif($viewLevel === 'ads')
                                    <!-- Ads Table -->
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="text-xs font-black text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/30">
                                                <th class="px-6 py-4">Preview</th>
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Ad Name</th>
                                                <th class="px-6 py-4 text-right">Spend</th>
                                                <th class="px-6 py-4 text-right">Impr.</th>
                                                <th class="px-6 py-4 text-right">Clicks</th>
                                                <th class="px-6 py-4 text-right">CTR</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($ads as $ad)
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        @if(isset($ad['creative']['thumbnail_url']))
                                                            <img src="{{ $ad['creative']['thumbnail_url'] }}" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                                        @else
                                                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs">No Img</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize
                                                            {{ $ad['status'] === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ strtolower($ad['status']) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-gray-900">{{ $ad['name'] }}</div>
                                                        <a href="{{ $ad['preview_shareable_link'] ?? '#' }}" target="_blank" class="text-xs text-blue-500 hover:underline">Preview Ad</a>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-mono text-sm text-gray-700">${{ number_format($ad['insights']['spend'] ?? 0, 2) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($ad['insights']['impressions'] ?? 0) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($ad['insights']['clicks'] ?? 0) }}</td>
                                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($ad['insights']['ctr'] ?? 0, 2) }}%</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No ads found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
