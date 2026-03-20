<div class="p-8">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
        <div class="relative overflow-hidden bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl transition-all duration-500">
            <div class="absolute right-[-10%] top-[-10%] opacity-[0.03]">
                <svg class="w-40 h-40 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="flex flex-col h-full justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Total Contacts</p>
                    <h3 class="text-4xl font-black text-slate-900 tracking-tight">{{ number_format($stats['total_contacts']) }}</h3>
                </div>
                <div class="flex items-center gap-1.5 text-emerald-500 text-[10px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Growing Audience</span>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl transition-all duration-500">
            <div class="absolute right-[-10%] top-[-10%] opacity-[0.03]">
                <svg class="w-40 h-40 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="flex flex-col h-full justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Active Deals</p>
                    <h3 class="text-4xl font-black text-slate-900 tracking-tight">{{ number_format($stats['active_deals']) }}</h3>
                </div>
                <div class="flex items-center gap-1.5 text-wa-teal text-[10px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>In Sales Pipeline</span>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl transition-all duration-500">
            <div class="absolute right-[-10%] top-[-10%] opacity-[0.03]">
                <svg class="w-40 h-40 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="flex flex-col h-full justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Pipeline Value</p>
                    <h3 class="text-4xl font-black text-slate-900 tracking-tight">${{ number_format($stats['pipeline_value'] / 1000, 1) }}K</h3>
                </div>
                <div class="flex items-center gap-1.5 text-indigo-500 text-[10px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Potential Revenue</span>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-slate-900 border border-slate-900 rounded-[2.5rem] p-8 shadow-2xl shadow-slate-900/10 hover:shadow-2xl transition-all duration-500">
            <div class="flex flex-col h-full justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Today's Revenue</p>
                    <h3 class="text-4xl font-black text-white tracking-tight">${{ number_format($stats['closed_won_today']) }}</h3>
                </div>
                <div class="flex items-center gap-1.5 text-wa-teal text-[10px] font-black uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Closed Deals</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Pipeline Health --}}
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-[3rem] p-10 shadow-sm">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Pipeline Health</h3>
                    <p class="text-slate-500 font-bold uppercase tracking-widest text-[9px] mt-1">Distribution across stages</p>
                </div>
                <button class="bg-slate-50 text-slate-500 p-2.5 rounded-2xl hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                </button>
            </div>

            <div class="space-y-8">
                @foreach($pipelineDistribution as $dist)
                    <div class="group">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-2 rounded-full bg-wa-teal shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                                <span class="text-xs font-black uppercase tracking-widest text-slate-700">{{ $dist->name }}</span>
                            </div>
                            <div class="text-[10px] font-black uppercase tracking-widest">
                                <span class="text-slate-400 mr-2">{{ $dist->count }} Deals</span>
                                <span class="text-slate-900">${{ number_format($dist->total_value / 1000, 1) }}K</span>
                            </div>
                        </div>
                        <div class="h-3 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100 p-[2px]">
                            @php
                                $percentage = ($stats['pipeline_value'] > 0) ? ($dist->total_value / $stats['pipeline_value'] * 100) : 0;
                            @endphp
                            <div class="h-full bg-gradient-to-r from-slate-900 to-indigo-600 rounded-full transition-all duration-1000 group-hover:from-wa-teal group-hover:to-emerald-500" style="width: {{ max(5, $percentage) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 pt-10 border-t border-slate-50">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Avg Cycle</p>
                        <p class="text-lg font-black text-slate-900">14.2 Days</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Win Rate</p>
                        <p class="text-lg font-black text-emerald-500">68%</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Lead Velocity</p>
                        <p class="text-lg font-black text-indigo-500">+12%</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Churn Risk</p>
                        <p class="text-lg font-black text-rose-500">3.2%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white border border-slate-200 rounded-[3rem] p-8 shadow-sm">
            <h3 class="text-xl font-black text-slate-900 tracking-tight mb-8">Recent Activity</h3>
            
            <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-slate-100">
                @forelse($recentActivities as $activity)
                    <div class="flex gap-4 relative">
                        <div class="relative z-10 h-6 w-6 rounded-full bg-white border border-slate-200 flex items-center justify-center p-1.5">
                            <div class="h-full w-full rounded-full bg-slate-900"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[11px] font-bold text-slate-700 leading-relaxed capitalize">
                                <span class="text-wa-teal font-black">{{ $activity->user->name ?? 'System' }}</span> 
                                {{ str_replace('_', ' ', $activity->activity_type) }} 
                                <span class="text-slate-400">on</span>
                                <span class="text-slate-900 font-bold underline decoration-slate-200">{{ $activity->relatedTo->name ?? $activity->relatedTo->title ?? 'Record' }}</span>
                            </p>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1.5 block">
                                {{ $activity->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                        No activity yet
                    </div>
                @endforelse
            </div>

            <button class="w-full mt-10 py-4 bg-slate-50 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all border border-slate-100">
                View Full Audit Log
            </button>
        </div>
    </div>

    {{-- Bottom Section: Recent Deals & Companies --}}
    <div class="mt-12">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Active Deal Watch</h3>
            <button wire:click="$dispatch('setTab', {tab: 'deals'})" class="text-[10px] font-black uppercase tracking-widest text-indigo-500 hover:text-wa-teal transition-colors">
                Open Sales Board &rarr;
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            @foreach($recentDeals as $deal)
                <div class="bg-white border border-slate-200 rounded-[2rem] p-6 hover:shadow-lg transition-all border-b-4 border-b-slate-900">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[8px] font-black uppercase tracking-widest">
                            {{ $deal->stage->name }}
                        </span>
                    </div>
                    <h4 class="text-sm font-black text-slate-900 tracking-tight line-clamp-1 mb-1">{{ $deal->title }}</h4>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-4">{{ $deal->contact->name ?? 'No Contact' }}</p>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                        <span class="text-xs font-black text-slate-900 font-mono">${{ number_format($deal->value) }}</span>
                        @if($deal->owner)
                            <img src="{{ $deal->owner->profile_photo_url }}" class="w-5 h-5 rounded-full border border-white shadow-sm" title="{{ $deal->owner->name }}">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
