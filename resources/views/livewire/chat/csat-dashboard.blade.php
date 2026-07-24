<div class="p-6" wire:poll.30s>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">CSAT Analytics</h1>
        <div class="flex items-center space-x-4">
            <select wire:model.live="days" class="rounded-lg border-gray-300 text-sm">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
            <div class="flex bg-gray-100 p-1 rounded-lg">
                <button wire:click="$set('view', 'overview')" class="px-3 py-1 text-sm rounded-md {{ $view === 'overview' ? 'bg-white shadow-sm text-indigo-600 font-medium' : 'text-gray-500' }}">Overview</button>
                <button wire:click="$set('view', 'agents')" class="px-3 py-1 text-sm rounded-md {{ $view === 'agents' ? 'bg-white shadow-sm text-indigo-600 font-medium' : 'text-gray-500' }}">Agents</button>
                <button wire:click="$set('view', 'ratings')" class="px-3 py-1 text-sm rounded-md {{ $view === 'ratings' ? 'bg-white shadow-sm text-indigo-600 font-medium' : 'text-gray-500' }}">Recent Ratings</button>
            </div>
        </div>
    </div>

    @if($view === 'overview')
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 mb-1">Average CSAT</p>
                <div class="flex items-end space-x-2">
                    <span class="text-3xl font-bold text-gray-900">{{ $this->stats['avg'] ?? 'N/A' }}</span>
                    <span class="text-lg text-yellow-400 mb-1">★</span>
                </div>
                <p class="text-xs text-gray-400 mt-2">Target: 4.5+</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 mb-1">Total Ratings</p>
                <span class="text-3xl font-bold text-gray-900">{{ $this->stats['count'] }}</span>
                <p class="text-xs text-gray-400 mt-2">In last {{ $days }} days</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 col-span-2">
                <p class="text-sm font-medium text-gray-500 mb-3">Rating Distribution</p>
                <div class="space-y-2">
                    @foreach([5,4,3,2,1] as $r)
                        @php 
                            $count = $this->stats['distribution'][$r] ?? 0;
                            $pct = $this->stats['count'] > 0 ? ($count / $this->stats['count']) * 100 : 0;
                            $colors = [5 => 'bg-green-500', 4 => 'bg-green-400', 3 => 'bg-yellow-400', 2 => 'bg-orange-400', 1 => 'bg-red-500'];
                        @endphp
                        <div class="flex items-center text-xs">
                            <span class="w-4 text-gray-400">{{ $r }}★</span>
                            <div class="flex-1 h-2 mx-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $colors[$r] }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="w-8 text-right text-gray-500">{{ round($pct) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Weekly Trend -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">CSAT Weekly Trend</h3>
            <div class="h-48 flex items-end justify-between space-x-2 px-4">
                @foreach($this->stats['trend'] as $t)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-indigo-50 rounded-t-lg relative group" style="height: {{ ($t['avg'] ?? 0) * 20 }}%">
                            @if($t['avg'])
                                <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white px-2 py-1 rounded text-[10px] opacity-0 group-hover:opacity-100 transition-opacity">
                                    {{ $t['avg'] }}★ ({{ $t['count'] }})
                                </div>
                            @endif
                        </div>
                        <span class="text-[10px] text-gray-400 mt-2">{{ $t['week'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($view === 'agents')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Ratings</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->agentStats as $s)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                        {{ substr($s['agent_name'], 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $s['agent_name'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm font-semibold">
                                    {{ $s['avg_rating'] }} <span class="text-yellow-400 ml-1">★</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $s['count'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php $color = $s['avg_rating'] >= 4.5 ? 'text-green-600' : ($s['avg_rating'] >= 3.5 ? 'text-indigo-600' : 'text-red-600'); @endphp
                                <span class="text-xs font-bold uppercase {{ $color }}">
                                    {{ $s['avg_rating'] >= 4.5 ? 'Top Performer' : ($s['avg_rating'] >= 3.5 ? 'Steady' : 'Needs Focus') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">No agent data found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if($view === 'ratings')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->recentRatings as $r)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $r->contact?->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $r->contact?->phone_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm">
                                    <span class="font-bold mr-1">{{ $r->rating }}</span>
                                    <div class="flex text-yellow-400">
                                        @for($i=1; $i<=5; $i++)
                                            <span class="text-[10px]">{{ $i <= $r->rating ? '★' : '☆' }}</span>
                                        @endfor
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $r->agent?->name ?? 'Auto' }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-600 truncate max-w-xs">{{ $r->feedback ?: 'No comment' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                {{ $r->created_at->format('d M, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">No recent ratings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
