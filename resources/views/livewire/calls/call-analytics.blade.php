<div class="h-full flex flex-col bg-slate-50 dark:bg-slate-900">
    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Call Analytics & Billing
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Monitor call usage, costs, and performance
                </p>
            </div>

            {{-- Period Selector --}}
            <select wire:model.live="period"
                class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold focus:ring-2 focus:ring-wa-teal">
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
        </div>

        {{-- Usage Limit Alert --}}
        @if($usageLimits['has_limit'])
            <div
                class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-amber-500 text-white rounded-full p-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-amber-900 dark:text-amber-100">Monthly Call Limit</div>
                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                {{ number_format($usageLimits['minutes_used'], 0) }} /
                                {{ number_format($usageLimits['minutes_limit'], 0) }} minutes used
                                ({{ number_format($usageLimits['percent_used'], 1) }}%)
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-amber-900 dark:text-amber-100">
                            {{ number_format($usageLimits['minutes_remaining'], 0) }}
                        </div>
                        <div class="text-xs text-amber-600 dark:text-amber-400">minutes left</div>
                    </div>
                </div>
                <div class="mt-3 bg-amber-200 dark:bg-amber-800 rounded-full h-2 overflow-hidden">
                    <div class="bg-amber-500 h-full transition-all"
                        style="width: {{ min($usageLimits['percent_used'], 100) }}%"></div>
                </div>
            </div>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
        {{-- Billing Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Calls</div>
                <div class="text-4xl font-black mt-2">{{ $billingStats['total_calls'] }}</div>
                <div class="text-sm mt-2 opacity-75">
                    {{ $billingStats['completed_calls'] }} completed
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Minutes</div>
                <div class="text-4xl font-black mt-2">{{ number_format($billingStats['total_minutes'], 0) }}</div>
                <div class="text-sm mt-2 opacity-75">
                    Avg: {{ number_format($billingStats['average_duration'] / 60, 1) }}m per call
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="text-xs font-bold uppercase tracking-wider opacity-90">Success Rate</div>
                <div class="text-4xl font-black mt-2">{{ $statistics['success_rate'] }}%</div>
                <div class="text-sm mt-2 opacity-75">
                    {{ $billingStats['failed_calls'] }} failed
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="text-xs font-bold uppercase tracking-wider opacity-90">Total Cost</div>
                <div class="text-4xl font-black mt-2">${{ number_format($billingStats['total_cost'], 2) }}</div>
                <div class="text-sm mt-2 opacity-75">
                    ${{ number_format($billingStats['total_minutes'] > 0 ? $billingStats['total_cost'] / $billingStats['total_minutes'] : 0, 4) }}/min
                </div>
            </div>
        </div>

        {{-- Cost Breakdown Chart --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4">Daily Cost Breakdown (Last 30 Days)</h2>
            <div class="h-64 flex items-end space-x-1">
                @foreach($costBreakdown as $day)
                    @php
                        $maxCost = collect($costBreakdown)->max('cost');
                        $height = $maxCost > 0 ? ($day['cost'] / $maxCost) * 100 : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        <div class="w-full bg-gradient-to-t from-wa-teal to-emerald-500 rounded-t transition-all hover:opacity-80 cursor-pointer"
                            style="height: {{ $height }}%"
                            title="{{ $day['date'] }}: ${{ number_format($day['cost'], 2) }}">
                        </div>
                        <div
                            class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-full mt-2 bg-slate-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            {{ \Carbon\Carbon::parse($day['date'])->format('M d') }}<br>
                            {{ $day['calls'] }} calls<br>
                            {{ number_format($day['minutes'], 1) }}m<br>
                            ${{ number_format($day['cost'], 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-xs text-slate-500 dark:text-slate-400 text-center">
                Hover over bars for details
            </div>
        </div>

        {{-- Direction & Type Breakdown --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4">Call Direction</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Inbound</span>
                            <span
                                class="text-sm font-bold text-slate-900 dark:text-white">{{ $billingStats['inbound_calls'] }}</span>
                        </div>
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-blue-500 h-full"
                                style="width: {{ $billingStats['total_calls'] > 0 ? ($billingStats['inbound_calls'] / $billingStats['total_calls']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Outbound</span>
                            <span
                                class="text-sm font-bold text-slate-900 dark:text-white">{{ $billingStats['outbound_calls'] }}</span>
                        </div>
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-purple-500 h-full"
                                style="width: {{ $billingStats['total_calls'] > 0 ? ($billingStats['outbound_calls'] / $billingStats['total_calls']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4">Call Outcomes</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Completed</span>
                            <span
                                class="text-sm font-bold text-green-600 dark:text-green-400">{{ $billingStats['completed_calls'] }}</span>
                        </div>
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-green-500 h-full"
                                style="width: {{ $billingStats['total_calls'] > 0 ? ($billingStats['completed_calls'] / $billingStats['total_calls']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Failed/Missed</span>
                            <span
                                class="text-sm font-bold text-red-600 dark:text-red-400">{{ $billingStats['failed_calls'] }}</span>
                        </div>
                        <div class="bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-red-500 h-full"
                                style="width: {{ $billingStats['total_calls'] > 0 ? ($billingStats['failed_calls'] / $billingStats['total_calls']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Contacts by Call Volume --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4">Top Contacts by Call Volume</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">
                                Contact</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">
                                Calls</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">
                                Duration</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">
                                Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($topContacts as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        {{ $item['contact']->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $item['contact']->phone_number }}</div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    {{ $item['total_calls'] }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    {{ number_format($item['total_duration'] / 60, 1) }}m</td>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    ${{ number_format($item['total_cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                    No call data available for this period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>