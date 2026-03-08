<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        @foreach([
            'leads' => ['label' => 'New Leads', 'icon' => '✨', 'desc' => 'Requested OTP only', 'color' => 'bg-indigo-50 text-indigo-600 border-indigo-200'],
            'not_started' => ['label' => 'New Signups', 'icon' => '👤', 'desc' => 'Registered but idle', 'color' => 'bg-slate-100 text-slate-600'],
            'abandoned' => ['label' => 'Stalled Setup', 'icon' => '🚧', 'desc' => 'Mid-connection', 'color' => 'bg-amber-50 text-amber-600 border-amber-200'],
            'no_activity' => ['label' => 'Inactive', 'icon' => '💤', 'desc' => 'No messages sent', 'color' => 'bg-rose-50 text-rose-600 border-rose-200'],
            'active' => ['label' => 'Active Users', 'icon' => '🚀', 'desc' => 'Live & Messaging', 'color' => 'bg-emerald-50 text-emerald-600 border-emerald-200']
        ] as $key => $meta)
            <button wire:click="setFilter('{{ $key }}')" 
                class="text-left p-6 rounded-[2rem] border transition-all duration-300 group hover:shadow-lg {{ $filter === $key ? 'bg-white border-slate-900 shadow-md ring-2 ring-slate-900 ring-offset-2' : 'bg-white border-slate-200 hover:border-slate-300' }}">
                <div class="flex justify-between items-start mb-4">
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center text-xl {{ $meta['color'] }}">
                        {{ $meta['icon'] }}
                    </div>
                    <span class="text-2xl font-black text-slate-900">{{ $counts[$key] ?? 0 }}</span>
                </div>
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">{{ $meta['label'] }}</h4>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $meta['desc'] }}</p>
            </button>
        @endforeach
    </div>

    <!-- User List -->
    <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">
                    {{ match($filter) {
                        'leads' => 'New Leads (OTP Requests)',
                        'not_started' => 'Pending Signups',
                        'abandoned' => 'Incomplete Setups',
                        'no_activity' => 'Inactive Accounts',
                        'active' => 'Active Users',
                        default => 'Users'
                    } }}
                </h3>
                <p class="text-slate-500 font-bold uppercase tracking-widest text-xs mt-1">
                    Showing {{ $teams->firstItem() ?? 0 }}-{{ $teams->lastItem() ?? 0 }} of {{ $teams->total() }} results
                </p>
            </div>
            
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                    Export CSV
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 text-slate-400 border-b border-slate-100">
                        @if($filter === 'leads')
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Identifier</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Requested At</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Source IP</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Type</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em]">Action</th>
                        @else
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">User / Team</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Registered</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Progress</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em]">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teams as $team)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            @if($filter === 'leads')
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-lg">
                                            👻
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900">{{ $team->identifier }}</div>
                                            <div class="text-xs text-slate-500 font-medium">Unknown User</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700">{{ $team->created_at->format('M d, Y') }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ $team->created_at->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="font-mono text-xs text-slate-500">{{ $team->ip_address }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    @if(filter_var($team->identifier, FILTER_VALIDATE_EMAIL))
                                        <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase tracking-widest border border-blue-100">Email</span>
                                    @else
                                        <span class="px-2 py-1 bg-green-50 text-green-600 rounded text-[9px] font-black uppercase tracking-widest border border-green-100">WhatsApp</span>
                                    @endif
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button class="px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:bg-indigo-100 transition-colors">
                                        Convert
                                    </button>
                                </td>
                            @else
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden">
                                            <img src="{{ $team->owner->profile_photo_url }}" class="h-full w-full object-cover grayscale group-hover:grayscale-0 transition-all">
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900">{{ $team->name }}</div>
                                            <div class="text-xs text-slate-500 font-medium">{{ $team->owner->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700">{{ $team->created_at->format('M d, Y') }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ $team->created_at->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @if($filter === 'not_started')
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-slate-200">
                                            No Setup
                                        </span>
                                    @elseif($filter === 'abandoned')
                                        <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-amber-200">
                                            Stalled
                                        </span>
                                    @elseif($filter === 'no_activity')
                                        <span class="px-3 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-rose-200">
                                            Silent
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-emerald-200">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-1.5 w-8 rounded-full {{ $team->whatsapp_access_token ? 'bg-blue-500' : 'bg-slate-200' }}" title="FB Linked"></div>
                                        <div class="h-1.5 w-8 rounded-full {{ $team->whatsapp_business_account_id ? 'bg-cyan-500' : 'bg-slate-200' }}" title="WABA Found"></div>
                                        <div class="h-1.5 w-8 rounded-full {{ $team->whatsapp_phone_number_id ? 'bg-purple-500' : 'bg-slate-200' }}" title="Phone Registered"></div>
                                        <div class="h-1.5 w-8 rounded-full {{ $team->last_webhook_received_at ? 'bg-emerald-500' : 'bg-slate-200' }}" title="Active"></div>
                                    </div>
                                    <div class="mt-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                                        @if(!$team->whatsapp_access_token) 0% Complete
                                        @elseif(!$team->whatsapp_business_account_id) 25% Complete
                                        @elseif(!$team->whatsapp_phone_number_id) 50% Complete
                                        @elseif(!$team->last_webhook_received_at) 75% Complete
                                        @else 100% Complete @endif
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-indigo-600 hover:border-indigo-200 transition-colors">
                                        Details
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center justify-center opacity-40">
                                    <span class="text-4xl mb-4">🍃</span>
                                    <p class="text-sm font-black text-slate-900 uppercase tracking-widest">No users found in this stage</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teams->hasPages())
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50">
                {{ $teams->links() }}
            </div>
        @endif
    </div>
</div>
