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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Connect Meta Marketing API</h3>
                <p class="text-gray-500 max-w-md mx-auto mb-8">To start creating ads, you need to connect your Facebook Ad Account. This requires a System User token with `ads_management` permission.</p>
                <a href="{{ route('integrations.ecommerce') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all">
                    Connect Ad Account
                </a>
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

                <!-- Main Content: Campaigns -->
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
                            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <h3 class="font-bold text-gray-800">Campaigns</h3>
                                <button wire:click="loadCampaigns" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="Refresh">
                                    <svg class="w-5 h-5 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>

                            @if(empty($campaigns))
                                <div class="p-12 text-center">
                                    <p class="text-gray-500 mb-4">No campaigns found for this account.</p>
                                    <button class="text-blue-600 font-bold hover:underline">Create your first ad</button>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="text-xs font-black text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                                <th class="px-6 py-4">Status</th>
                                                <th class="px-6 py-4">Campaign Name</th>
                                                <th class="px-6 py-4 text-right">Spend</th>
                                                <th class="px-6 py-4 text-right text-blue-600">Revenue</th>
                                                <th class="px-6 py-4 text-right text-green-600">ROAS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($campaigns as $campaign)
                                                @php
                                                    $metrics = $roasMetrics[$campaign['id']] ?? ['revenue' => 0, 'roas' => 0];
                                                @endphp
                                                <tr class="hover:bg-gray-50/50 transition-colors group cursor-pointer">
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize
                                                            {{ $campaign['status'] === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ strtolower($campaign['status']) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-gray-900">{{ $campaign['name'] }}</div>
                                                        <div class="text-xs text-gray-400">ID: {{ $campaign['id'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-mono text-sm text-gray-700">
                                                        ${{ number_format($campaign['spend'] ?? 0, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right font-mono text-sm font-bold text-blue-600">
                                                        ${{ number_format($metrics['revenue'], 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <span class="px-2 py-1 rounded-lg text-xs font-bold
                                                            {{ $metrics['roas'] >= 3 ? 'bg-green-100 text-green-700' : ($metrics['roas'] >= 1 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                                            {{ $metrics['roas'] }}x
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
