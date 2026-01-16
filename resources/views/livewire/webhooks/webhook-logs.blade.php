<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Area -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none border border-white dark:border-slate-800 relative overflow-hidden group">
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/5 rounded-full -mr-32 -mt-32 blur-3xl group-hover:bg-indigo-600/10 transition-colors duration-700">
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-indigo-600/10 text-indigo-600 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Webhook
                            Logs</h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Raw inbound payloads from
                            Meta</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="px-4 py-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">24h
                        Volume</span>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                        placeholder="Search ID or Payload...">
                </div>

                <!-- Status Filter -->
                <div class="relative">
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-950 border-transparent focus:border-wa-teal focus:ring-wa-teal/10 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 appearance-none transition-all">
                        <option value="">All Statuses</option>
                        <option value="processed">Processed</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Log ID
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Received
                            At</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Status</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Payload
                            Preview</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($logs as $log)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <span
                                    class="text-sm font-black text-slate-900 dark:text-white tabular-nums">#{{ $log->id }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-500 tabular-nums">
                                    {{ $log->created_at->format('M d, H:i:s') }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                @php
                                    $statusStyle = match ($log->status) {
                                        'processed' => 'bg-wa-teal/10 text-wa-teal border-wa-teal/20',
                                        'failed' => 'bg-rose-50 text-rose-600 border-rose-100',
                                        default => 'bg-amber-50 text-amber-600 border-amber-100',
                                    };
                                @endphp
                                <span
                                    class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter border {{ $statusStyle }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div
                                    class="text-[10px] font-mono text-slate-400 truncate max-w-xs group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors">
                                    {{ json_encode($log->payload) }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button wire:click="viewDetails({{ $log->id }})"
                                    class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-wa-teal hover:text-white text-slate-600 dark:text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                    Inspect
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center gap-2 opacity-20">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <p class="text-xs font-black uppercase tracking-widest">Silence in the wires</p>
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

    <!-- Details Modal -->
    <x-dialog-modal wire:model="showDetailsModal">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-wa-teal/10 text-wa-teal rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-tight">Webhook
                        Payload</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Details for
                        #{{ $selectedPayload?->id ?? '...' }}</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <!-- Status & Metadata -->
                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Status</label>
                        <span
                            class="text-sm font-black uppercase {{ $selectedPayload?->status === 'failed' ? 'text-rose-500' : 'text-wa-teal' }}">
                            {{ $selectedPayload?->status ?? 'Unknown' }}
                        </span>
                    </div>
                    <div
                        class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Received
                            At</label>
                        <span class="text-sm font-black text-slate-900 dark:text-white uppercase">
                            {{ $selectedPayload?->created_at?->format('F d, Y H:i:s') ?? '...' }}
                        </span>
                    </div>
                </div>

                @if($selectedPayload?->error_message)
                    <div
                        class="p-4 bg-rose-50 dark:bg-rose-900/20 rounded-2xl border border-rose-100 dark:border-rose-900/50">
                        <label class="text-[10px] font-black text-rose-500 uppercase tracking-widest block mb-1">Processing
                            Error</label>
                        <p class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $selectedPayload->error_message }}
                        </p>
                    </div>
                @endif

                <!-- JSON Payload -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Raw Data
                        JSON</label>
                    <div class="bg-[#0D1117] rounded-2xl p-6 overflow-x-auto border border-slate-800 shadow-2xl">
                        <pre
                            class="text-xs font-mono text-wa-teal leading-relaxed select-all">{{ json_encode($selectedPayload?->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3 w-full">
                <button wire:click="closeDetails"
                    class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-xl shadow-lg transition-all active:scale-95">
                    Close Inspector
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>