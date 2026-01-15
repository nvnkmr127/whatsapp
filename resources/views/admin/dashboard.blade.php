<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Super <span class="text-wa-green">Admin</span>
                        </h1>
                    </div>
                    <p class="text-slate-500 font-medium">Overview of system health, subscription stats, and tenant
                        management.</p>
                </div>

                <a href="{{ route('admin.tenants.create') }}"
                    class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    New Workspace
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Companies -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden group hover:scale-[1.02] transition-transform">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-2xl group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/20 transition-colors">
                    </div>
                    <div class="relative">
                        <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Companies
                        </div>
                        <div class="text-4xl font-black text-slate-900 dark:text-white">{{ $stats['total_teams'] }}
                        </div>
                        <div class="mt-4 flex items-center gap-2 text-xs font-bold text-indigo-500">
                            <span>Active Workspaces</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Subs -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden group hover:scale-[1.02] transition-transform">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-wa-green/10 rounded-full blur-2xl group-hover:bg-wa-green/20 transition-colors">
                    </div>
                    <div class="relative">
                        <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Active
                            Subscriptions</div>
                        <div class="text-4xl font-black text-wa-green">{{ $stats['active_subs'] }}</div>
                        <div class="mt-4 flex items-center gap-2 text-xs font-bold text-wa-green">
                            <span>Recurring Revenue</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden group hover:scale-[1.02] transition-transform">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-2xl group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 transition-colors">
                    </div>
                    <div class="relative">
                        <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Users</div>
                        <div class="text-4xl font-black text-slate-900 dark:text-white">{{ $stats['total_users'] }}
                        </div>
                        <div class="mt-4 flex items-center gap-2 text-xs font-bold text-blue-500">
                            <span>Across all teams</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Messages -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden group hover:scale-[1.02] transition-transform">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-orange-50 dark:bg-orange-900/10 rounded-full blur-2xl group-hover:bg-orange-100 dark:group-hover:bg-orange-900/20 transition-colors">
                    </div>
                    <div class="relative">
                        <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Messages
                        </div>
                        <div class="text-4xl font-black text-slate-900 dark:text-white">
                            {{ number_format($stats['total_messages']) }}</div>
                        <div class="mt-4 flex items-center gap-2 text-xs font-bold text-orange-500">
                            <span>System throughput</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tenants List -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">

                <!-- Search & Filters -->
                <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col lg:flex-row gap-6">
                        <div class="flex-1 relative group">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all font-medium"
                                placeholder="Search by company name, owner name or email...">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-green transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <div class="w-full sm:w-48">
                            <select name="status" onchange="this.form.submit()"
                                class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-green/20 transition-all appearance-none cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                    Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Company</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Owner</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Plan</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Status</th>
                                <th
                                    class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                            @forelse($teams as $team)
                                <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-white/5 flex items-center justify-center text-indigo-500 font-black text-lg">
                                                {{ substr($team->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-black text-slate-900 dark:text-white">
                                                    {{ $team->name }}</div>
                                                <div class="text-[10px] font-bold text-slate-400">Created
                                                    {{ $team->created_at->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $team->owner->profile_photo_url }}" alt="{{ $team->owner->name }}"
                                                class="w-6 h-6 rounded-full">
                                            <div>
                                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                                    {{ $team->owner->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $team->owner->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span
                                            class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black uppercase tracking-wider rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                            {{ ucfirst($team->subscription_plan ?? 'Basic') }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $team->subscription_status === 'active' ? 'bg-wa-green shadow-lg shadow-wa-green/40' : 'bg-slate-400' }}"></span>
                                            <span
                                                class="text-xs font-black uppercase tracking-widest {{ $team->subscription_status === 'active' ? 'text-wa-green' : 'text-slate-500' }}">
                                                {{ ucfirst($team->subscription_status) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div
                                            class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('admin.tenants.edit', $team->id) }}"
                                                class="p-2 text-slate-400 hover:text-indigo-500 transition-colors"
                                                title="Edit Workspace">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.tenants.destroy', $team->id) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this workspace? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-slate-400 hover:text-rose-500 transition-colors"
                                                    title="Delete Workspace">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div
                                                class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div class="text-slate-400 font-bold">No workspaces found.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($teams->hasPages())
                    <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>