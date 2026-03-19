<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Enterprise <span class="text-wa-teal">CRM</span></h2>
                <p class="text-slate-500 font-bold uppercase tracking-[0.2em] text-[10px]">Workspace Management • v2.1</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative group">
                    <input wire:model.live.debounce.300ms="search" type="text" 
                        placeholder="Search users, teams, or emails..." 
                        class="w-80 pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-wa-teal/20 focus:border-wa-teal transition-all shadow-sm">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-wa-teal transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <div class="h-12 w-[1px] bg-slate-200 mx-2"></div>
                
                <div class="flex items-center gap-2">
                    <button wire:click="toggleFilters" class="px-6 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black text-slate-700 uppercase tracking-widest hover:border-slate-400 hover:shadow-sm transition-all flex items-center gap-2 {{ $showFilters ? 'bg-slate-50 border-wa-teal text-wa-teal' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filters
                    </button>

                    <button wire:click="exportCsv" class="px-6 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black text-slate-700 uppercase tracking-widest hover:border-slate-400 hover:shadow-sm transition-all flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export CSV
                    </button>

                    <select wire:model.live="statusFilter" class="bg-white border border-slate-200 rounded-2xl text-xs font-black text-slate-700 uppercase tracking-widest px-4 py-3 focus:ring-wa-teal/20 focus:border-wa-teal shadow-sm">
                        <option value="">Status: All</option>
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select wire:model.live="setupFilter" class="bg-white border border-slate-200 rounded-2xl text-xs font-black text-slate-700 uppercase tracking-widest px-4 py-3 focus:ring-wa-teal/20 focus:border-wa-teal shadow-sm">
                        <option value="">Setup: All</option>
                        @foreach(\App\Enums\IntegrationState::cases() as $state)
                            <option value="{{ $state->value }}">{{ $state->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Advanced Filters Panel --}}
        @if($showFilters)
            <div class="mb-12 bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em]">Advanced <span class="text-wa-teal">Segmentation</span></h5>
                    
                    {{-- Saved Segments --}}
                    <div class="flex items-center gap-4">
                        <select onchange="@this.loadSegment(this.value)" class="bg-slate-50 border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-wa-teal focus:border-wa-teal">
                            <option value="">Load Saved Segment...</option>
                            @foreach($segments as $segment)
                                <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Revenue Range</label>
                        <div class="flex items-center gap-2">
                            <input wire:model.live.debounce.500ms="filterRevenueMin" type="number" placeholder="Min" class="w-full bg-slate-50 border-slate-200 rounded-xl text-xs focus:ring-wa-teal focus:border-wa-teal">
                            <span class="text-slate-300">-</span>
                            <input wire:model.live.debounce.500ms="filterRevenueMax" type="number" placeholder="Max" class="w-full bg-slate-50 border-slate-200 rounded-xl text-xs focus:ring-wa-teal focus:border-wa-teal">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Signup Date</label>
                        <div class="flex items-center gap-2">
                            <input wire:model.live="filterDateStart" type="date" class="w-full bg-slate-50 border-slate-200 rounded-xl text-xs focus:ring-wa-teal focus:border-wa-teal">
                            <span class="text-slate-300">-</span>
                            <input wire:model.live="filterDateEnd" type="date" class="w-full bg-slate-50 border-slate-200 rounded-xl text-xs focus:ring-wa-teal focus:border-wa-teal">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Lead Score</label>
                        <input wire:model.live.debounce.500ms="filterLeadScoreMin" type="range" min="0" max="100" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer">
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-1">
                            <span>0%</span>
                            <span class="text-wa-teal">{{ $filterLeadScoreMin ?? 0 }}%+</span>
                            <span>100%</span>
                        </div>
                    </div>
                    
                    <div class="flex items-end">
                        <div class="w-full">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Save Segment</label>
                            <div class="flex gap-2">
                                <input wire:model="segmentName" type="text" placeholder="Segment Name..." class="flex-1 bg-slate-50 border-slate-200 rounded-xl text-xs focus:ring-wa-teal focus:border-wa-teal">
                                <button wire:click="saveSegment" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Stats Layer --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-12">
            @foreach([
                ['label' => 'Total Users', 'value' => $stats['total_users'], 'sub' => '+'.$stats['new_today'].' today', 'color' => 'bg-emerald-500', 'icon' => '⚡'],
                ['label' => 'WhatsApp Connected', 'value' => $stats['active_setups'], 'sub' => 'Live Terminals', 'color' => 'bg-indigo-500', 'icon' => '📱'],
                ['label' => 'Trial Pipeline', 'value' => $stats['trial_users'], 'sub' => 'Conversions', 'color' => 'bg-amber-500', 'icon' => '🎁'],
                ['label' => 'Growth Metrics', 'value' => $stats['new_this_week'], 'sub' => 'L7 Performance', 'color' => 'bg-purple-500', 'icon' => '📈', 'span' => 'lg:col-span-2'],
            ] as $stat)
                <div class="{{ $stat['span'] ?? '' }} bg-white border border-slate-200 p-8 rounded-[2.5rem] relative overflow-hidden group hover:border-wa-teal/30 transition-all duration-500 shadow-sm hover:shadow-xl hover:shadow-wa-teal/5">
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">{{ $stat['label'] }}</p>
                            <h4 class="text-4xl font-black text-slate-900 leading-none tracking-tighter">{{ number_format($stat['value']) }}</h4>
                        </div>
                        <p class="text-[10px] font-bold text-wa-teal mt-4 tracking-widest">{{ $stat['sub'] }}</p>
                    </div>
                    <div class="absolute right-6 top-8 text-3xl opacity-10 group-hover:opacity-20 transition-opacity">{{ $stat['icon'] }}</div>
                    <div class="absolute -right-4 -bottom-4 w-32 h-32 rounded-full blur-[60px] opacity-10 {{ $stat['color'] }} group-hover:opacity-20 transition-all duration-700"></div>
                </div>
            @endforeach
        </div>

        {{-- Interactive Onboarding Funnel --}}
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Onboarding <span class="text-wa-teal">Status</span></h3>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1">Select a stage to isolate users</p>
                </div>
                
                @if($funnelStage)
                    <button wire:click="setFunnel('')" class="px-4 py-2 bg-rose-500/10 border border-rose-500/20 rounded-full text-[10px] font-black text-rose-400 uppercase tracking-widest hover:bg-rose-500/20 transition-all">
                        Clear Stage Filter: {{ strtoupper(str_replace('_', ' ', $funnelStage)) }}
                    </button>
                @endif
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @php
                    $stages = [
                        ['id' => 'total', 'label' => 'Signups', 'count' => $funnel['total'], 'color' => 'indigo', 'icon' => '👤'],
                        ['id' => 'connected_fb', 'label' => 'FB Linked', 'count' => $funnel['connected_fb'], 'color' => 'blue', 'icon' => '🔵'],
                        ['id' => 'waba_found', 'label' => 'WABA Detect', 'count' => $funnel['waba_found'], 'color' => 'cyan', 'icon' => '🔍'],
                        ['id' => 'phone_registered', 'label' => 'Phone Reg.', 'count' => $funnel['phone_registered'], 'color' => 'purple', 'icon' => '📱'],
                        ['id' => 'active_messaging', 'label' => 'Activated', 'count' => $funnel['active_messaging'], 'color' => 'wa-teal', 'icon' => '🚀'],
                    ];
                @endphp

                @foreach($stages as $index => $stage)
                    @php $isActive = $funnelStage === $stage['id'] || ($funnelStage === '' && $stage['id'] === 'total'); @endphp
                    <button wire:click="setFunnel('{{ $stage['id'] }}')" 
                        class="relative flex flex-col items-center p-8 rounded-[2.5rem] transition-all duration-500 border {{ $isActive ? 'bg-white border-wa-teal shadow-xl shadow-wa-teal/10' : 'bg-white border-slate-200 hover:border-slate-300' }} group">
                        
                        <div class="text-3xl mb-4 {{ $isActive ? 'scale-110' : 'opacity-40 grayscale group-hover:grayscale-0 group-hover:opacity-100' }} transition-all duration-500">{{ $stage['icon'] }}</div>
                        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ $stage['label'] }}</h5>
                        <p class="text-3xl font-black text-slate-900 leading-none mb-4">{{ number_format($stage['count']) }}</p>
                        
                        @if($index > 0 && $stages[$index-1]['count'] > 0)
                            <div class="px-3 py-1 bg-slate-100 rounded-full text-[10px] font-black {{ $isActive ? 'text-wa-teal' : 'text-slate-500' }}">
                                {{ round(($stage['count'] / $stages[$index-1]['count']) * 100) }}% Rate
                            </div>
                        @else
                            <div class="px-3 py-1 bg-slate-100 rounded-full text-[10px] font-black text-slate-500">Entry</div>
                        @endif

                        @if($index < count($stages) - 1)
                            <div class="hidden md:flex absolute -right-6 top-1/2 -translate-y-1/2 z-20 w-8 h-8 items-center justify-center text-slate-300 font-bold group-hover:text-slate-400 transition-colors">
                            →
                            </div>
                        @endif

                        @if($isActive)
                            <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-12 h-1 bg-wa-teal rounded-full shadow-[0_0_15px_rgba(37,211,102,0.4)]"></div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- User/Team Table --}}
        <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm">
            <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Workspace <span class="text-slate-400">Registry</span></h4>
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Showing {{ $teams->count() }} of {{ $teams->total() }} total entries</div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100 bg-slate-50/30">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Company / Owner</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Signup Time</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Source</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Lead Score</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Onboarding Stage</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Revenue (Est.)</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-6 text-right text-[10px] font-black uppercase tracking-[0.2em]">Operations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($teams as $team)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4 cursor-pointer" wire:click="showUser({{ $team->id }})">
                                    <div class="h-12 w-12 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group-hover:border-wa-teal/30 transition-all">
                                        <img src="{{ $team->owner->profile_photo_url }}" class="h-full w-full object-cover grayscale opacity-80 group-hover:grayscale-0 group-hover:opacity-100 transition-all">
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900 uppercase tracking-tight group-hover:text-wa-teal transition-colors">{{ $team->name }}</div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $team->owner->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-700">{{ $team->owner->created_at->format('d M Y') }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $team->owner->created_at->format('H:i') }} <span class="text-[8px]">UTC</span></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    @if($team->owner->utm_source)
                                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest px-2 py-0.5 bg-emerald-50 rounded-md border border-emerald-100 self-start">
                                            {{ $team->owner->utm_source }}
                                        </span>
                                        <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-widest truncate max-w-[100px]">{{ $team->owner->utm_medium ?? 'Organic' }} / {{ $team->owner->utm_campaign ?? 'Direct' }}</span>
                                    @else
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200 self-start">
                                            Unknown
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-slate-100 h-1 rounded-full overflow-hidden max-w-[50px]">
                                        <div class="h-full {{ $team->lead_score > 70 ? 'bg-emerald-500' : ($team->lead_score > 30 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $team->lead_score }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-700">{{ $team->lead_score }}%</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                @php
                                    $hasFB = $team->whatsapp_access_token;
                                    $hasWABA = $team->whatsapp_business_account_id;
                                    $hasPhone = $team->whatsapp_phone_number_id;
                                    $hasPulse = $team->last_webhook_received_at;
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <div class="h-1.5 w-6 rounded-full {{ $hasFB ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.2)]' : 'bg-slate-200' }}" title="FB Linked"></div>
                                    <div class="h-1.5 w-6 rounded-full {{ $hasFB && $hasWABA ? 'bg-cyan-500 shadow-[0_0_10px_rgba(6,182,212,0.2)]' : 'bg-slate-200' }}" title="WABA Found"></div>
                                    <div class="h-1.5 w-6 rounded-full {{ $hasPhone ? 'bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.2)]' : 'bg-slate-200' }}" title="Phone Registered"></div>
                                    <div class="h-1.5 w-6 rounded-full {{ $hasPulse ? 'bg-wa-teal shadow-[0_0_10px_rgba(37,211,102,0.2)]' : 'bg-slate-200' }}" title="Messages Active"></div>
                                </div>
                                <div class="text-[10px] font-black text-slate-400 mt-3 uppercase tracking-[0.15rem]">
                                    @if($hasPulse) <span class="text-wa-teal">Deployed</span> @elseif($hasPhone) Ready @elseif($hasWABA) Config @elseif($hasFB) Auth @else Zero @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-[xs] font-black text-slate-900">${{ number_format($team->total_revenue, 2) }}</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Calc. LTV</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    @if($team->last_webhook_received_at)
                                        <div class="flex items-center gap-2">
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                            </span>
                                            <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Live</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ $team->last_webhook_received_at->diffForHumans() }}</span>
                                    @else
                                        <div class="flex items-center gap-2 grayscale">
                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Inactive</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-3">
                                    {{-- Fast-toggle button removed. All overrides must now go through the Advanced Modal to require justification. --}}
                                    
                                    <button wire:click="impersonate({{ $team->owner->id }})" class="p-3 bg-white rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-300" title="Enter Instance">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($teams->hasPages())
                <div class="px-8 py-6 bg-slate-50 border-t border-slate-100">
                    {{ $teams->links() }}
                </div>
            @endif
        </div>

        {{-- Advanced Details Modal (Standardized) --}}
        <x-app-modal wire:model="showTeamModal" maxWidth="5xl" :closeable="false">
            <div class="relative bg-white dark:bg-slate-900 w-full rounded-[3rem] overflow-hidden flex flex-col h-[85vh]">
                <div class="flex h-full">
                    {{-- Left Sidebar: Context --}}
                    <div class="w-80 bg-slate-50 border-r border-slate-100 p-8 flex flex-col justify-between">
                        <div>
                            <div class="flex flex-col items-center text-center mb-8">
                                <div class="h-24 w-24 rounded-[2rem] overflow-hidden border-2 border-wa-teal shadow-xl shadow-wa-teal/10 mb-4 bg-white">
                                    <img src="{{ $selectedTeam->owner->profile_photo_url }}" class="h-full w-full object-cover">
                                </div>
                                <h4 class="text-xl font-black text-slate-900 uppercase tracking-tight">{{ $selectedTeam->owner->name }}</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate max-w-full px-4" title="{{ $selectedTeam->owner->email }}">{{ $selectedTeam->owner->email }}</p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2">Attribution</p>
                                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Source</span>
                                            <span class="text-[10px] text-wa-teal font-black uppercase">{{ $selectedTeam->owner->utm_source ?? 'Direct' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Campaign</span>
                                            <span class="text-[10px] text-slate-700 font-black truncate max-w-[80px]">{{ $selectedTeam->owner->utm_campaign ?? 'None' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2">Workspace</p>
                                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Status</span>
                                            <span class="text-[10px] text-amber-600 font-black uppercase">{{ $selectedTeam->subscription_status }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Created</span>
                                            <span class="text-[10px] text-slate-700 font-black">{{ $selectedTeam->created_at->format('d M Y') }}</span>
                                        </div>
                                    </div>
                                </div>
 
                                 <div>
                                     <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2">Lead Success Score</p>
                                     <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                                         <div class="flex items-center justify-between mb-3">
                                             <span class="text-[10px] font-black text-slate-900">{{ $selectedTeam->lead_score }}% Readiness</span>
                                             <div class="flex gap-0.5">
                                                 @for($i = 1; $i <= 5; $i++)
                                                     <div class="h-1 w-1.5 rounded-full {{ $selectedTeam->lead_score >= ($i * 20) ? 'bg-wa-teal' : 'bg-slate-100' }}"></div>
                                                 @endfor
                                             </div>
                                         </div>
                                         <div class="w-full bg-slate-50 h-1 rounded-full overflow-hidden">
                                             <div class="h-full bg-wa-teal" style="width: {{ $selectedTeam->lead_score }}%"></div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                        <div class="flex flex-col gap-3">
                            <button wire:click="impersonate({{ $selectedTeam->owner->id }})" class="w-full py-4 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Impersonate Session
                            </button>
                            <button wire:click="closeUser()" class="w-full py-4 bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-slate-200 hover:border-slate-400 transition-all">
                                Close Inspector
                            </button>
                        </div>
                    </div>

                    {{-- Right Content: Details --}}
                    <div class="flex-1 overflow-y-auto p-12 bg-white relative">
                        {{-- Flash Messages --}}
                        @if (session()->has('message'))
                            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm flex items-center justify-between">
                                {{ session('message') }}
                                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl text-xs font-bold shadow-sm flex items-center justify-between">
                                {{ session('error') }}
                                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            {{-- Integration Section --}}
                            <div class="space-y-8">
                                <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em] border-b border-slate-100 pb-4">Integration <span class="text-wa-teal">Details</span></h5>
                                
                                <div class="space-y-6">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Business Account (WABA)</p>
                                            <p class="font-mono text-xs text-indigo-600 font-bold">{{ $selectedTeam->whatsapp_business_account_id ?? 'NOT_SET' }}</p>
                                        </div>
                                        <div class="h-2 w-2 rounded-full {{ $selectedTeam->whatsapp_business_account_id ? 'bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.3)]' : 'bg-slate-200' }}"></div>
                                    </div>

                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Phone Number ID</p>
                                            <p class="font-mono text-xs text-purple-600 font-bold">{{ $selectedTeam->whatsapp_phone_number_id ?? 'NOT_SET' }}</p>
                                        </div>
                                        <div class="h-2 w-2 rounded-full {{ $selectedTeam->whatsapp_phone_number_id ? 'bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.3)]' : 'bg-slate-200' }}"></div>
                                    </div>

                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Verified Name</p>
                                            <p class="text-xs text-slate-900 font-black">{{ $selectedTeam->whatsapp_verified_name ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <div class="pt-4 mt-4 border-t border-slate-100">
                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Setup Status</p>
                                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                                            @if($selectedTeam->last_webhook_received_at)
                                                <div class="flex items-center justify-between mb-4">
                                                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Active</span>
                                                    <span class="text-[10px] font-bold text-slate-400">{{ $selectedTeam->last_webhook_received_at->format('M d, H:i:s') }}</span>
                                                </div>
                                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                                    <div class="h-full bg-wa-teal w-full shadow-[0_0_10px_rgba(37,211,102,0.3)]"></div>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No Activity</span>
                                                    <span class="text-xs">⚠️</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Team Workspace Overview --}}
                                <div class="pt-8 mt-4 border-t border-slate-100">
                                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-4">Workspace Collaboration</p>
                                    <div class="space-y-3">
                                        @foreach($selectedTeam->users as $user)
                                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                                <div class="flex items-center gap-3">
                                                    <img src="{{ $user->profile_photo_url }}" class="h-6 w-6 rounded-full">
                                                    <div>
                                                        <p class="text-[10px] font-black text-slate-900 uppercase tracking-tight">{{ $user->name }}</p>
                                                        <p class="text-[8px] font-bold text-slate-400">{{ $user->email }}</p>
                                                    </div>
                                                </div>
                                                <span class="text-[8px] font-black px-2 py-0.5 bg-white border border-slate-200 rounded-md text-slate-500 uppercase">{{ $user->membership->role }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Activity & Notes Section --}}
                            <div class="space-y-8">
                                {{-- Tasks --}}
                                <div>
                                    <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em] border-b border-slate-100 pb-4 mb-6">Pending <span class="text-wa-teal">Tasks</span></h5>
                                    
                                    {{-- Add Task --}}
                                    <div class="bg-slate-50 p-5 rounded-[2rem] border border-slate-100 mb-6 relative overflow-hidden group">
                                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                            <svg class="w-16 h-16 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        </div>
                                        
                                        <div class="relative z-10">
                                            <div class="flex gap-3 mb-3">
                                                <input wire:model="taskTitle" type="text" placeholder="New task..." class="flex-1 bg-white border-slate-200 rounded-xl text-xs font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-wa-teal/20 focus:border-wa-teal transition-all shadow-sm px-4 py-3">
                                                <select wire:model="taskPriority" class="bg-white border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 focus:ring-2 focus:ring-wa-teal/20 focus:border-wa-teal shadow-sm px-4 py-3">
                                                    <option value="low">Low Priority</option>
                                                    <option value="medium">Medium Priority</option>
                                                    <option value="high">High Priority</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-3">
                                                <input wire:model="taskDueDate" type="date" class="bg-white border-slate-200 rounded-xl text-xs font-bold text-slate-600 focus:ring-2 focus:ring-wa-teal/20 focus:border-wa-teal shadow-sm px-4 py-3">
                                                <button wire:click="addTask" class="flex-1 py-3 bg-slate-900 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-wa-teal hover:shadow-lg hover:shadow-wa-teal/20 transition-all duration-300">
                                                    Assign Task
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($selectedTeam->crmTasks->where('status', '!=', 'completed') as $task)
                                            <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm hover:border-wa-teal/30 hover:shadow-md transition-all group">
                                                <div class="flex items-center gap-4">
                                                    <button wire:click="completeTask({{ $task->id }})" class="h-6 w-6 rounded-lg border-2 border-slate-200 flex items-center justify-center text-white hover:bg-wa-teal hover:border-wa-teal transition-all group-hover:scale-110">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                    </button>
                                                    <div>
                                                        <p class="text-xs font-black text-slate-900 uppercase tracking-tight group-hover:text-wa-teal transition-colors">{{ $task->title }}</p>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $task->due_date ? $task->due_date->format('M d') : 'No Date' }}</span>
                                                            <span class="h-1 w-1 rounded-full bg-slate-200"></span>
                                                            <span class="text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded {{ $task->priority === 'high' ? 'bg-rose-50 text-rose-500' : ($task->priority === 'medium' ? 'bg-amber-50 text-amber-500' : 'bg-slate-100 text-slate-400') }}">
                                                                {{ $task->priority }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="h-8 w-8 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400" title="Assigned to {{ $task->assignedTo->name ?? 'Unassigned' }}">
                                                    {{ substr($task->assignedTo->name ?? '?', 0, 1) }}
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($selectedTeam->crmTasks->where('status', '!=', 'completed')->isEmpty())
                                            <div class="flex flex-col items-center justify-center py-8 border-2 border-dashed border-slate-100 rounded-[2rem]">
                                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">All Clear</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Activity Timeline --}}
                                <div>
                                    <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em] border-b border-slate-100 pb-4 mb-6">Activity <span class="text-indigo-600">Timeline</span></h5>
                                    
                                    {{-- Add Activity --}}
                                    <div class="bg-slate-50 p-5 rounded-[2rem] border border-slate-100 mb-8 relative overflow-hidden group">
                                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                            <svg class="w-16 h-16 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>

                                        <div class="relative z-10 space-y-3">
                                            <div class="flex gap-3">
                                                <select wire:model="activityType" class="bg-white border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 w-1/3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm px-4 py-3">
                                                    <option value="note">📝 Note</option>
                                                    <option value="call">📞 Call</option>
                                                    <option value="email">📧 Email</option>
                                                    <option value="meeting">📅 Meeting</option>
                                                </select>
                                                <input wire:model="activityOutcome" type="text" placeholder="Outcome (e.g. Connected)" class="flex-1 bg-white border-slate-200 rounded-xl text-xs font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm px-4 py-3">
                                            </div>
                                            <textarea wire:model="activityContent" placeholder="Log interaction details..." class="w-full bg-white border-slate-200 rounded-xl p-4 text-xs font-medium text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm h-24 resize-none"></textarea>
                                            <button wire:click="addActivity" class="w-full py-3 bg-indigo-500 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-indigo-600 hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                                                Log Activity
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-8 relative max-h-[500px] overflow-y-auto pr-4 custom-scrollbar pl-2">
                                        <div class="absolute left-[19px] top-4 bottom-4 w-[2px] bg-slate-100"></div>
                                        
                                        @foreach($selectedTeam->crmActivities as $activity)
                                            <div class="relative pl-12 group">
                                                <div class="absolute left-0 top-0 h-10 w-10 rounded-2xl border-4 border-white shadow-sm flex items-center justify-center text-sm z-10 transition-all duration-300 group-hover:scale-110 
                                                    {{ $activity->type == 'call' ? 'bg-indigo-50 text-indigo-500' : 
                                                       ($activity->type == 'email' ? 'bg-emerald-50 text-emerald-500' : 
                                                       ($activity->type == 'meeting' ? 'bg-purple-50 text-purple-500' : 'bg-slate-100 text-slate-500')) }}">
                                                    @if($activity->type == 'call') 
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                    @elseif($activity->type == 'email')
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    @elseif($activity->type == 'meeting')
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    @endif
                                                </div>
                                                
                                                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm group-hover:border-slate-200 transition-all relative">
                                                    @if($activity->type == 'note')
                                                        <div class="absolute top-0 right-0 w-8 h-8 bg-amber-100/50 rounded-bl-2xl rounded-tr-2xl text-amber-500 flex items-center justify-center">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                                        </div>
                                                    @endif

                                                    <div class="flex justify-between items-start mb-3">
                                                        <div class="flex flex-col">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">{{ ucfirst($activity->type) }}</span>
                                                                @if($activity->outcome)
                                                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[9px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200">{{ $activity->outcome }}</span>
                                                                @endif
                                                            </div>
                                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $activity->performed_at->format('M d, H:i') }}</span>
                                                        </div>
                                                        <div class="text-[9px] font-black text-slate-300 uppercase tracking-widest">{{ $activity->performed_at->diffForHumans(null, true) }}</div>
                                                    </div>
                                                    
                                                    <p class="text-xs font-medium text-slate-600 leading-relaxed whitespace-pre-wrap">{{ $activity->content }}</p>
                                                    
                                                    <div class="mt-4 flex items-center gap-2 pt-3 border-t border-slate-50">
                                                        <div class="h-5 w-5 rounded-full bg-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-500">
                                                            {{ substr($activity->user->name ?? '?', 0, 1) }}
                                                        </div>
                                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Logged by {{ $activity->user->name ?? 'Unknown' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em] border-b border-slate-100 pb-4">Recent <span class="text-indigo-600">Events</span></h5>
                                
                                <div class="space-y-6">
                                    @forelse($selectedTeam->messages as $msg)
                                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-white border border-slate-100 hover:border-slate-200 transition-colors shadow-sm">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs {{ $msg->direction === 'outbound' ? 'bg-indigo-50 text-indigo-500' : 'bg-emerald-50 text-emerald-500' }}">
                                                {{ $msg->direction === 'outbound' ? '↑' : '↓' }}
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ strtoupper($msg->type) }} MESSAGE</p>
                                                <p class="text-xs text-slate-700 font-medium line-clamp-1">{{ $msg->content }}</p>
                                                <p class="text-[9px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ $msg->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-12 text-center opacity-30 grayscale">
                                            <span class="text-4xl mb-4">🌑</span>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Zero Messaging Activity</p>
                                        </div>
                                    @endforelse
                                </div>

                                @if($selectedTeam->messages->count() > 0)
                                    <button class="w-full py-3 bg-slate-50 border border-slate-100 rounded-xl text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] hover:text-slate-900 hover:border-slate-300 transition-all">
                                        View Full Transcript
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- ════════════════════════════════════════════════════════
                             Super Admin Overrides
                        ════════════════════════════════════════════════════════ --}}
                        <div class="mt-16 pt-12 border-t border-slate-100">
                            <h5 class="flex items-center gap-3 text-sm font-black text-slate-900 uppercase tracking-[0.3em] mb-2">
                                <span class="text-rose-500">Super Admin</span> Overrides
                            </h5>
                            <p class="text-xs text-slate-500 mb-8 max-w-2xl">Use these tools carefully. All actions bypass standard eligibility checks, immediately invalidate Entitlement caches, and commit permanent audit trails.</p>

                            <div class="bg-rose-50/30 border border-rose-100 rounded-[2rem] p-6 space-y-6">
                                {{-- Audit Reason (Required for all) --}}
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Mandatory Audit Reason</label>
                                    <input type="text" wire:model="overrideReason" placeholder="Required: Why are you overriding this team's state?" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all shadow-sm">
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4 border-t border-rose-100/50">
                                    {{-- LEFT COL: Actions --}}
                                    <div class="space-y-4">
                                        @if($selectedTeam->subscription_status === 'trial')
                                            {{-- Extend Trial --}}
                                            <div class="flex gap-2">
                                                <input type="number" wire:model="extendDays" min="1" class="w-24 bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-center appearance-none cursor-text focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" placeholder="Days">
                                                <button wire:click="extendTrial({{ $selectedTeam->id }})" class="flex-1 px-4 py-2 bg-amber-50 border border-amber-200 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 hover:border-amber-300 transition-all">
                                                    Extend Trial
                                                </button>
                                            </div>

                                            {{-- Revoke Trial --}}
                                            <div class="flex gap-2">
                                                <input type="text" wire:model="revokeConfirmation" placeholder="Type team name" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 placeholder-slate-300">
                                                <button wire:click="revokeTrial({{ $selectedTeam->id }})" class="px-4 py-2 bg-rose-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-600 border border-rose-600 transition-all shadow-md shadow-rose-500/20">
                                                    Revoke
                                                </button>
                                            </div>
                                        @else
                                            {{-- Force Trial --}}
                                            <div class="flex gap-2">
                                                <button wire:click="forceTrial({{ $selectedTeam->id }})" class="w-full px-4 py-3 bg-amber-50 border border-amber-200 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 hover:border-amber-300 transition-all">
                                                    Force New Trial (Bypass Eligibility)
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- RIGHT COL: Manual Conversion --}}
                                    <div class="space-y-4 bg-white/50 rounded-xl p-4 border border-indigo-100">
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Promote to Paid</p>
                                        <div class="flex flex-col gap-2">
                                            <select wire:model="convertPlan" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                                <option value="starter">Starter Plan</option>
                                                <option value="growth">Growth Plan</option>
                                                <option value="scale">Scale Plan</option>
                                                <option value="enterprise">Enterprise Plan</option>
                                            </select>
                                            <button wire:click="convertToActive({{ $selectedTeam->id }})" class="w-full px-4 py-2 bg-indigo-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-md shadow-indigo-500/20">
                                                Convert to Paid
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- UTM Details Footer --}}
                        <div class="mt-16 pt-12 border-t border-slate-100">
                            <h5 class="text-sm font-black text-slate-900 uppercase tracking-[0.3em] mb-8">Lead <span class="text-rose-500">Attribution</span> Full Data</h5>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                @foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $param)
                                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-slate-200 transition-all group">
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2 group-hover:text-slate-500 transition-colors">{{ str_replace('utm_', '', $param) }}</p>
                                        <p class="text-[10px] font-bold text-slate-700 truncate">{{ $selectedTeam->owner->$param ?? 'N/A' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </x-app-modal>

            @if($teams->hasPages())
                <div class="px-8 py-6 bg-slate-50 border-t border-slate-100">
                    {{ $teams->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
