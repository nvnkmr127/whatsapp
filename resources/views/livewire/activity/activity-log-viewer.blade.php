<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Area -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-800 relative overflow-hidden group">
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 rounded-full -mr-32 -mt-32 blur-3xl group-hover:bg-wa-teal/10 transition-colors duration-700">
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-wa-teal/10 text-wa-teal rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">System
                            Activity</h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Audit logs and session
                            history</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="px-4 py-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">Total
                        Events</span>
                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ $logs->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-800 overflow-hidden">

        <!-- Filter Bar -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Search -->
                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-wa-teal transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-950 border-transparent focus:border-wa-teal focus:ring-wa-teal/10 rounded-2xl text-sm font-bold placeholder-slate-400 text-slate-700 dark:text-slate-200 transition-all"
                        placeholder="Search logs...">
                </div>

                <!-- User Filter -->
                <div class="relative">
                    <select wire:model.live="filterUser"
                        class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-950 border-transparent focus:border-wa-teal focus:ring-wa-teal/10 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 appearance-none transition-all">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Filter -->
                <div class="relative">
                    <select wire:model.live="filterAction"
                        class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-950 border-transparent focus:border-wa-teal focus:ring-wa-teal/10 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 appearance-none transition-all">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Timestamp
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">User
                            Context</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Action</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Activity
                            Details</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Endpoint</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($logs as $log)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-500 tabular-nums">
                                    {{ $log->created_at->format('M d, H:i:s') }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-xl bg-wa-teal/10 flex items-center justify-center overflow-hidden">
                                        @if($log->user && $log->user->profile_photo_url)
                                            <img src="{{ $log->user->profile_photo_url }}" class="w-full h-full object-cover"
                                                loading="lazy">
                                        @else
                                            <span
                                                class="text-wa-teal font-black text-xs">{{ $log->user ? strtoupper(substr($log->user->name, 0, 1)) : 'S' }}</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $log->user ? $log->user->name : 'System Core' }}</span>
                                        <span
                                            class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $log->user ? 'Member' : 'Internal' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span
                                    class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter bg-wa-teal/10 text-wa-teal border border-wa-teal/20">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-sm text-slate-600 dark:text-slate-400 font-medium max-w-md line-clamp-2"
                                    title="{{ $log->description }}">
                                    {{ $log->description }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <span
                                    class="text-[10px] font-mono font-bold text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg">
                                    {{ $log->ip_address }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 opacity-20">
                                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">No events
                                        captured</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-8 py-6 bg-slate-50/30 dark:bg-slate-800/20 border-t border-slate-50 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>