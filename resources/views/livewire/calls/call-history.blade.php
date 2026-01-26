<div class="space-y-8">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Call <span
                        class="text-wa-teal">Center</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage your WhatsApp voice calls, analytics, and billing.</p>
        </div>

        <a href="{{ route('calls.analytics') }}"
            class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Analytics
        </a>
    </div>

    {{-- Statistics Cards --}}
    @if($usageLimits['minutes_limit'])
        <div
            class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-[2.5rem] p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-amber-500 text-white rounded-2xl p-3">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-black uppercase tracking-widest text-amber-600 dark:text-amber-400">Monthly
                            Usage</div>
                        <div class="text-2xl font-black text-amber-900 dark:text-amber-100 mt-1">
                            {{ number_format($usageLimits['minutes_used'], 0) }} /
                            {{ number_format($usageLimits['minutes_limit'], 0) }} min
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black text-amber-900 dark:text-amber-100">
                        {{ number_format($usageLimits['minutes_remaining'], 0) }}
                    </div>
                    <div class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase">Minutes Left</div>
                </div>
            </div>
            <div class="mt-4 bg-amber-200 dark:bg-amber-800 rounded-full h-3 overflow-hidden">
                <div class="bg-amber-500 h-full transition-all"
                    style="width: {{ min(($usageLimits['minutes_used'] / $usageLimits['minutes_limit']) * 100, 100) }}%">
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6 shadow-lg">
            <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Calls</div>
            <div class="text-4xl font-black mt-2">{{ $statistics['total_calls'] }}</div>
            <div class="text-sm mt-2 opacity-75">This {{ ucfirst($period) }}</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6 shadow-lg">
            <div class="text-xs font-bold uppercase tracking-wider opacity-90">Success Rate</div>
            <div class="text-4xl font-black mt-2">{{ $statistics['success_rate'] }}%</div>
            <div class="text-sm mt-2 opacity-75">{{ $statistics['completed_calls'] }} completed</div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6 shadow-lg">
            <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Minutes</div>
            <div class="text-4xl font-black mt-2">{{ number_format($statistics['total_duration_minutes'], 0) }}</div>
            <div class="text-sm mt-2 opacity-75">{{ number_format($statistics['avg_duration_seconds'] / 60, 1) }}m avg
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl p-6 shadow-lg">
            <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Cost</div>
            <div class="text-4xl font-black mt-2">${{ number_format($statistics['total_cost'], 2) }}</div>
            <div class="text-sm mt-2 opacity-75">This {{ ucfirst($period) }}</div>
        </div>
    </div>

    {{-- Filters & Table Card --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        {{-- Search & Filters --}}
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1 relative group">
                <input wire:model.live.debounce.300ms="filters.search" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                    placeholder="Search by phone number or contact name...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48">
                    <select wire:model.live="filters.direction"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Directions</option>
                        <option value="inbound">Inbound</option>
                        <option value="outbound">Outbound</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select wire:model.live="filters.status"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="rejected">Rejected</option>
                        <option value="missed">Missed</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <input type="date" wire:model.live="filters.from_date"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all">
                </div>
                <div class="w-full sm:w-48">
                    <input type="date" wire:model.live="filters.to_date"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all">
                </div>
                @if(array_filter($filters))
                    <button wire:click="clearFilters"
                        class="px-6 py-4 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 font-bold text-sm rounded-2xl transition-colors">
                        Clear
                    </button>
                @endif
            </div>
        </div>

        {{-- Table Content --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 cursor-pointer hover:text-wa-teal transition-colors"
                            wire:click="sortBy('direction')">
                            Direction
                            @if($sortBy === 'direction')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact
                            Info</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 cursor-pointer hover:text-wa-teal transition-colors"
                            wire:click="sortBy('status')">
                            Status
                            @if($sortBy === 'status')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 cursor-pointer hover:text-wa-teal transition-colors"
                            wire:click="sortBy('duration_seconds')">
                            Duration
                            @if($sortBy === 'duration_seconds')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Cost</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 cursor-pointer hover:text-wa-teal transition-colors"
                            wire:click="sortBy('created_at')">
                            Date & Time
                            @if($sortBy === 'created_at')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($calls as $call)
                        <tr wire:key="call-{{ $call->id }}"
                            class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <span
                                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tighter
                                    {{ $call->direction === 'inbound' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800' }}">
                                    {{ $call->direction === 'inbound' ? '↓ In' : '↑ Out' }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $call->contact->name ?? $call->from_number }}"
                                        alt="{{ $call->contact->name ?? 'Unknown' }}"
                                        class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 object-cover"
                                        loading="lazy">
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">
                                            {{ $call->contact->name ?? 'Unknown Contact' }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                                            {{ $call->direction === 'inbound' ? $call->from_number : $call->to_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                @php
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border-green-200 dark:border-green-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
                                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800',
                                        'rejected' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border-orange-200 dark:border-orange-800',
                                        'missed' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300 border-gray-200 dark:border-gray-800',
                                    ];
                                    $color = $statusColors[$call->status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-300 border-slate-200 dark:border-slate-800';
                                @endphp
                                <span
                                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tighter border {{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $call->status)) }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-sm font-black text-slate-900 dark:text-white tabular-nums">
                                    {{ $call->formatted_duration }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-sm font-black text-slate-900 dark:text-white tabular-nums">
                                    {{ $call->cost_formatted }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $call->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                                    {{ $call->created_at->format('h:i A') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold">No calls found matching your filters.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($calls->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $calls->links() }}
            </div>
        @endif
    </div>
</div>