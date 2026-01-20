<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen"
        x-data="{ showRestoreModal: false, selectedBackup: null, selectedBackupDate: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="relative">
                @php
                    $hasAccess = auth()->user()->currentTeam->hasFeature('backups');
                    $hasCloudAccess = auth()->user()->currentTeam->hasFeature('cloud_backups');
                @endphp

                <!-- Paywall Overlay -->
                @if(!$hasAccess)
                    <div
                        class="absolute inset-0 z-50 flex items-center justify-center p-6 backdrop-blur-xl bg-white/30 dark:bg-slate-900/30 rounded-[3rem] border border-white/20">
                        <div
                            class="max-w-md w-full bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-2xl shadow-indigo-500/20 border border-slate-100 dark:border-slate-800 text-center transform transition-all">
                            <div
                                class="w-20 h-20 bg-indigo-50 dark:bg-indigo-950/50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">
                                Enterprise Backups</h2>
                            <p class="text-slate-500 dark:text-slate-400 font-medium mb-8">Secure your business data with
                                automated daily backups and instant point-in-time restoration.</p>

                            <ul class="text-left space-y-3 mb-8">
                                <li class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                    <svg class="w-5 h-5 text-wa-green" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Daily Automated Snapshots
                                </li>
                                <li class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                    <svg class="w-5 h-5 text-wa-green" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    One-Click Atomic Restore
                                </li>
                                <li class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                    <svg class="w-5 h-5 text-wa-green" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Google Drive Cloud Sync
                                </li>
                            </ul>

                            <a href="{{ route('billing') }}" class="block w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-indigo-500/30 transition-all active:scale-95">
                                Upgrade to Pro Plan
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Content Wrapper (Blurred if no access) -->
                <div
                    class="space-y-8 {{ !$hasAccess ? 'filter blur-md select-none pointer-events-none opacity-50' : '' }}">

                    <!-- Page Header -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                                    Backup & <span class="text-wa-green">Restore</span>
                                </h1>
                            </div>
                            <p class="text-slate-500 font-medium">Manage your data snapshots and disaster recovery
                                settings.</p>
                        </div>

                        <form action="{{ route('backups.store') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="flex items-center justify-center gap-2 px-8 py-4 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Create Manual Backup
                            </button>
                        </form>
                    </div>

                    <!-- Cloud Integration Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <div
                                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden group">
                                <div
                                    class="absolute -right-12 -top-12 w-64 h-64 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-3xl group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 transition-colors">
                                </div>

                                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div class="flex items-center gap-6">
                                        <div
                                            class="w-20 h-20 bg-blue-50 dark:bg-blue-950/30 rounded-3xl flex items-center justify-center text-blue-600">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3
                                                class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                Cloud Synchronization</h3>
                                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1">
                                                Google Drive Integration</p>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0">
                                        @if($googleDrive && $googleDrive->status === 'active')
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="px-4 py-2 bg-wa-green/10 text-wa-green rounded-xl flex items-center gap-2 border border-wa-green/20">
                                                    <span class="w-2 h-2 rounded-full bg-wa-green animate-pulse"></span>
                                                    <span class="text-xs font-black uppercase tracking-widest">Active
                                                        Sync</span>
                                                </div>
                                                <form action="{{ route('integrations.google-drive.disconnect') }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-xs font-black text-rose-500 uppercase tracking-widest hover:underline">Disconnect</button>
                                                </form>
                                            </div>
                                        @else
                                            <a href="{{ route('integrations.google-drive.redirect') }}"
                                                class="px-6 py-3 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all {{ !$hasCloudAccess ? 'opacity-50 pointer-events-none' : '' }}">
                                                Connect Account
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats/Info -->
                        <div
                            class="bg-indigo-600 dark:bg-indigo-950/40 rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl shadow-indigo-500/20 border border-indigo-400/20">
                            <div class="absolute -right-12 -top-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="relative">
                                <h3 class="text-lg font-black uppercase tracking-tight mb-4">Retention Policy</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                        <span class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Local
                                            Retention</span>
                                        <span class="text-sm font-black">7 Days</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                        <span class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Cloud
                                            Retention</span>
                                        <span class="text-sm font-black">Unlimited</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Table -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50">
                            <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                Backup History</h2>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">Available
                                Recovery Points</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Recovery Point</th>
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Type</th>
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Status</th>
                                        <th
                                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                            Emergency Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                                    @forelse($backups as $backup)
                                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="px-8 py-6">
                                                <div class="text-sm font-black text-slate-900 dark:text-white">
                                                    {{ $backup->created_at->format('M d, Y H:i') }}</div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                                    {{ $backup->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span
                                                    class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded">
                                                    {{ $backup->type }}
                                                </span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="w-2 h-2 rounded-full {{ $backup->status === 'completed' ? 'bg-wa-green shadow-lg shadow-wa-green/40' : 'bg-slate-400' }}"></span>
                                                    <span
                                                        class="text-xs font-black uppercase tracking-widest {{ $backup->status === 'completed' ? 'text-wa-green' : 'text-slate-500' }}">
                                                        {{ ucfirst($backup->status) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-right">
                                                <div
                                                    class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <a href="{{ route('backups.download', $backup->id) }}"
                                                        class="p-2 text-slate-400 hover:text-indigo-600 transition-colors"
                                                        title="Download">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                    </a>
                                                    <button @click="showRestoreModal = true; selectedBackup = {{ $backup->id }}; selectedBackupDate = '{{ $backup->created_at->format('M d, Y H:i') }}'" class="p-2 text-slate-400 hover:text-rose-600 transition-colors"
                                                        title="Restore Data">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-8 py-20 text-center">
                                                <div class="flex flex-col items-center gap-4 text-slate-400">
                                                    <svg class="w-12 h-12" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                                    </svg>
                                                    <div class="text-sm font-black uppercase tracking-widest">No Backups
                                                        Registered</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($backups->hasPages())
                            <div
                                class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                                {{ $backups->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('backups.partials.restore_modal')
    </div>
</x-app-layout>