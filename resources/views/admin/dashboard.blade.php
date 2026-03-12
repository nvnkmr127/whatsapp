<x-app-layout>
    <div x-data="{ showWorkspaceDetails: false, selectedTeam: null }" class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            SaaS <span class="text-indigo-600">Command</span>
                        </h1>
                    </div>
                    <p class="text-slate-500 font-medium tracking-tight">Manage client workspaces, infrastructure
                        health, and global billing plans.</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.crm') }}"
                        class="px-6 py-3 bg-indigo-50 text-indigo-600 font-black uppercase tracking-widest text-xs rounded-2xl border border-indigo-100 hover:bg-indigo-100 transition-all shadow-sm">
                        Enterprise CRM
                    </a>
                    <a href="{{ route('admin.audit-logs') }}"
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 font-black uppercase tracking-widest text-xs rounded-2xl border border-rose-100 dark:border-rose-900/50 hover:bg-rose-50 transition-all shadow-sm">
                        Audit Logs
                    </a>
                    <a href="{{ route('admin.plans') }}"
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black uppercase tracking-widest text-xs rounded-2xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 transition-all shadow-sm">
                        Plan Manager
                    </a>
                    <a href="{{ route('admin.email-templates.index') }}"
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-indigo-600 font-black uppercase tracking-widest text-xs rounded-2xl border border-indigo-100 dark:border-indigo-900/50 hover:bg-indigo-50 transition-all shadow-sm">
                        Email Engine
                    </a>
                    <a href="{{ route('admin.health') }}"
                        class="px-6 py-3 bg-rose-50 text-rose-600 font-black uppercase tracking-widest text-xs rounded-2xl border border-rose-100 hover:bg-rose-100 transition-all shadow-sm">
                        Health Guard
                    </a>
                    <a href="{{ route('admin.tenants.create') }}"
                        class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                        </svg>
                        New Client Workspace
                    </a>
                </div>
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
                            {{ number_format($stats['total_messages']) }}
                        </div>
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

            <!-- Main Dashboard Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Global Search Results (Users) -->
                @if($matchingUsers->isNotEmpty())
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-rose-100 dark:border-rose-900/50 overflow-hidden mb-8">
                        <div class="p-8 border-b border-rose-50 dark:border-rose-900/20">
                            <h2 class="text-xl font-black text-rose-600 dark:text-rose-500 uppercase tracking-tight">
                                Identity Discovery</h2>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">Cross-Tenant
                                User Matches</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                                    @foreach($matchingUsers as $mUser)
                                        <tr class="group hover:bg-rose-50/50 dark:hover:bg-rose-900/10 transition-colors">
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-4">
                                                    <img src="{{ $mUser->profile_photo_url }}" class="w-10 h-10 rounded-full"
                                                        loading="lazy">
                                                    <div>
                                                        <div
                                                            class="text-sm font-black text-slate-900 dark:text-white uppercase">
                                                            {{ $mUser->name }}</div>
                                                        <div class="text-xs text-slate-500 font-bold tracking-tight">
                                                            {{ $mUser->email ?: $mUser->phone }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex flex-col gap-1">
                                                    <span
                                                        class="text-[9px] font-black uppercase text-slate-400">Ownership</span>
                                                    <div class="flex gap-2">
                                                        @forelse($mUser->ownedTeams as $oTeam)
                                                            <span
                                                                class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 text-[9px] font-black uppercase rounded border border-indigo-100 dark:border-indigo-900/50">{{ $oTeam->name }}</span>
                                                        @empty
                                                            <span class="text-[10px] font-bold text-slate-300 italic">No owned
                                                                workspaces</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-right">
                                                <div
                                                    class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <a href="{{ route('admin.impersonate.enter', $mUser->id) }}"
                                                        class="px-4 py-2 bg-slate-900 dark:bg-rose-600 text-white text-[9px] font-black uppercase tracking-widest rounded-xl hover:scale-105 active:scale-95 transition-all shadow-lg shadow-rose-600/20">
                                                        Impersonate
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Workspace Management (Left/Wide Column) -->
                <div class="lg:col-span-2 space-y-8">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <!-- Search & Filters -->
                        <div
                            class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    Active Workspaces</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">Tenant
                                    Registry</p>
                            </div>

                            <form method="GET" action="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-4 flex-1 max-w-lg">
                                <div class="relative flex-1 group">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-wa-green/20 transition-all font-medium"
                                        placeholder="Search tenants...">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <select name="status" onchange="this.form.submit()"
                                    class="py-3 px-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl text-xs font-black uppercase tracking-widest text-slate-500 focus:ring-2 focus:ring-wa-green/20 cursor-pointer">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                </select>
                            </form>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Company</th>
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
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
                                                            {{ $team->name }}
                                                        </div>
                                                        <div class="text-[10px] font-bold text-slate-400">Created
                                                            {{ $team->created_at->format('M d, Y') }}
                                                        </div>
                                                    </div>
                                                </div>
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
                                                    <button @click="showWorkspaceDetails = true; selectedTeam = { 
                                                            name: '{{ addslashes($team->name) }}',
                                                            ownerName: '{{ addslashes($team->owner->name) }}',
                                                            ownerEmail: '{{ addslashes($team->owner->email) }}',
                                                            ownerPhone: '{{ addslashes($team->owner->phone ?? 'N/A') }}',
                                                            ownerPhoto: '{{ $team->owner->profile_photo_url }}',
                                                            plan: '{{ ucfirst($team->subscription_plan ?? 'Basic') }}',
                                                            status: '{{ ucfirst($team->subscription_status) }}',
                                                            createdAt: '{{ $team->created_at->format('M d, Y') }}',
                                                            address: '{{ addslashes($team->billing_address ?? 'Not Provided') }}'
                                                        }"
                                                        class="p-2 text-slate-400 hover:text-indigo-500 transition-colors"
                                                        title="View Details">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                    <a href="{{ route('admin.impersonate.enter', $team->owner->id) }}"
                                                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors"
                                                        title="Impersonate Owner">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m14-10a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                    </a>
                                                    <a href="{{ route('admin.tenants.edit', $team->id) }}"
                                                        class="p-2 text-slate-400 hover:text-indigo-500 transition-colors"
                                                        title="Edit Workspace">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>
                                                    <form action="{{ route('admin.tenants.destroy', $team->id) }}"
                                                        method="POST" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
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
                                            <td colspan="5"
                                                class="px-8 py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">
                                                No workspaces available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($teams->hasPages())
                            <div
                                class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                                {{ $teams->links() }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column (System Maintenance) -->
                <div class="space-y-8">
                    <!-- Backup Summary & Action -->
                    <div
                        class="bg-indigo-600 dark:bg-indigo-950/40 rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl shadow-indigo-500/20 border border-indigo-400/20">
                        <div class="absolute -right-12 -top-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>

                        <div class="relative flex flex-col h-full">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-lg font-black uppercase tracking-tight">System Health</h3>
                                    <p class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest mt-1">
                                        Snapshot Management</p>
                                </div>
                                <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                </div>
                            </div>

                            <div class="mb-8">
                                <div class="text-3xl font-black mb-1">{{ $stats['total_backups'] }}</div>
                                <div class="text-[10px] font-bold text-indigo-100 uppercase tracking-widest opacity-80">
                                    {{ $stats['global_backups'] }} Global |
                                    {{ $stats['total_backups'] - $stats['global_backups'] }} Tenant
                                </div>
                            </div>

                            <form action="{{ route('backups.store') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full py-4 bg-white text-indigo-600 font-black text-xs uppercase tracking-widest rounded-2xl hover:bg-slate-50 transition-all shadow-lg active:scale-95">
                                    Trigger System Backup
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Global Backups List (Compact) -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-6 border-b border-slate-50 dark:border-slate-800/50">
                            <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">
                                Recent Global Snapshots</h2>
                        </div>

                        <div class="divide-y divide-slate-50 dark:divide-slate-800/30">
                            @forelse($globalBackups as $backup)
                                <div class="p-6 group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-xs font-black text-slate-900 dark:text-white">{{ $backup->created_at->format('M d, H:i') }}</span>
                                        <span
                                            class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest {{ $backup->status === 'completed' ? 'text-wa-green' : 'text-slate-400' }}">
                                            <span
                                                class="w-1.5 h-1.5 rounded-full {{ $backup->status === 'completed' ? 'bg-wa-green' : 'bg-slate-400' }}"></span>
                                            {{ $backup->status }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <a href="{{ route('backups.download', $backup->id) }}"
                                            class="text-[10px] font-black text-indigo-500 uppercase tracking-widest hover:underline">Download</a>
                                        <form action="{{ route('backups.restore', $backup->id) }}" method="POST"
                                            onsubmit="return confirm('Restore full system?')">
                                            @csrf
                                            <button type="submit"
                                                class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:underline">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    No snapshots found</div>
                            @endforelse
                        </div>

                        @if($globalBackups->hasPages())
                            <div class="p-4 bg-slate-50/50 dark:bg-slate-800/10 text-center">
                                {{ $globalBackups->links('pagination::simple-tailwind') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    {{-- Workspace Details Modal --}}
    <div x-show="showWorkspaceDetails" 
        class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 lg:p-8"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" @click="showWorkspaceDetails = false"></div>

        <div class="relative bg-white dark:bg-slate-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-800"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <div class="p-8">
                <div class="flex items-center gap-6 mb-8">
                    <img :src="selectedTeam?.ownerPhoto" class="w-20 h-20 rounded-[2rem] border-4 border-indigo-50 dark:border-indigo-900/50 shadow-xl shadow-indigo-500/10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight" x-text="selectedTeam?.name"></h3>
                        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest" x-text="'Joined ' + selectedTeam?.createdAt"></p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                        <div class="space-y-4">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Owner Identity</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="selectedTeam?.ownerName"></p>
                                <p class="text-xs text-slate-500 font-medium" x-text="selectedTeam?.ownerEmail"></p>
                            </div>
                            <div x-show="selectedTeam?.ownerPhone !== 'N/A'">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Contact Phone</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="selectedTeam?.ownerPhone"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Plan</p>
                            <p class="text-xs font-black text-indigo-600 dark:text-indigo-400" x-text="selectedTeam?.plan"></p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Life Status</p>
                            <p class="text-xs font-black uppercase" 
                               :class="selectedTeam?.status === 'Active' ? 'text-wa-green' : 'text-slate-400'"
                               x-text="selectedTeam?.status"></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Business Address</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 font-medium italic" x-text="selectedTeam?.address"></p>
                    </div>
                </div>

                <div class="mt-8">
                    <button @click="showWorkspaceDetails = false" 
                        class="w-full py-4 bg-slate-900 dark:bg-indigo-600 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Close Intelligence Panel
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>