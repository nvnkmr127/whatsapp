<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 px-1">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                        Email <span class="text-indigo-600">Engine</span>
                    </h1>
                    <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium tracking-tight">Monitor system email
                        delivery, failures, and SMTP provider performance.</p>
                </div>

                <div
                    class="flex items-center gap-3 bg-white dark:bg-slate-800 p-1.5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                    <a href="{{ route('admin.email-templates.index') }}"
                        class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('admin.email-templates.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50' }}">
                        Templates
                    </a>
                    <a href="{{ route('admin.email-logs.index') }}"
                        class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('admin.email-logs.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50' }}">
                        Tracking Logs
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Simplified for brevity, usually you'd calculate these -->
            </div>

            <!-- Filters & Search -->
            <div
                class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <form method="GET" action="{{ route('admin.email-logs.index') }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text" name="search" placeholder="Search recipient or subject..."
                        value="{{ request('search') }}"
                        class="rounded-xl border-slate-200 dark:bg-slate-900 dark:border-slate-700 text-sm">

                    <select name="status"
                        class="rounded-xl border-slate-200 dark:bg-slate-900 dark:border-slate-700 text-sm">
                        <option value="">All Statuses</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered
                        </option>
                    </select>

                    <button type="submit"
                        class="md:col-span-1 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Filter
                        Logs</button>
                </form>
            </div>

            <!-- Logs Table -->
            <div
                class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Recipient</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Use Case</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Subject</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Provider</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Status</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Time</th>
                                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200 text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($logs as $log)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                        {{ $log->recipient }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300">
                                            {{ $log->use_case->value }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs truncate text-slate-500 dark:text-slate-400">
                                        {{ $log->subject }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                        {{ $log->provider_name }}
                                        @if($log->failure_type)
                                            <div class="text-[10px] text-red-500 font-bold uppercase">{{ $log->failure_type }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($log->status === 'sent')
                                            <span
                                                class="px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-xs font-bold">SENT</span>
                                        @elseif($log->status === 'failed')
                                            <span
                                                class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg text-xs font-bold"
                                                title="{{ $log->failure_reason }}">FAILED</span>
                                        @else
                                            <span
                                                class="px-2 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold uppercase">{{ $log->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs">
                                        {{ $log->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.email-logs.show', $log->id) }}"
                                            class="text-indigo-600 hover:text-indigo-800 font-bold text-xs uppercase tracking-widest transition-colors">Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 italic">
                                        No deliver logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>