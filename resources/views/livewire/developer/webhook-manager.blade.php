<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-green-100 text-green-600 rounded-lg dark:bg-green-500/10 dark:text-green-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Webhook <span
                        class="text-green-600 dark:text-green-400">Manager</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Outbound event notifications & API-triggered workflows.</p>
        </div>
    </div>

    <!-- Subscriptions List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Name</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">URL</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Events
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status
                        </th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($subscriptions as $sub)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $sub->name }}</div>
                                @if($sub->secret)
                                    <div class="text-[10px] text-green-500 font-bold mt-1">🔒 SIGNED</div>
                                @endif
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-xs font-mono text-slate-500 truncate max-w-xs">{{ $sub->url }}</div>
                            </td>
                            <td class="px-8 py-6">
                                @if(empty($sub->events))
                                    <span class="text-xs font-bold text-purple-500">ALL EVENTS</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($sub->events as $event)
                                            <span
                                                class="text-[10px] font-bold bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">{{ $event }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-6">
                                <button wire:click="toggleStatus({{ $sub->id }})"
                                    class="group/toggle flex items-center gap-2 focus:outline-none">
                                    <span
                                        class="w-2 h-2 rounded-full {{ $sub->is_active ? 'bg-green-500 shadow-lg shadow-green-500/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest {{ $sub->is_active ? 'text-green-500' : 'text-rose-500' }}">
                                        {{ $sub->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="testWebhook({{ $sub->id }})"
                                        class="p-2 text-slate-400 hover:text-blue-500 transition-colors" title="Send Test">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="edit({{ $sub->id }})"
                                        class="p-2 text-slate-400 hover:text-green-500 transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $sub->id }})"
                                        wire:confirm="Are you sure you want to delete this webhook?"
                                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
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
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">No Webhooks
                                        Configured</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscriptions->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Form -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    {{ $editingId ? 'Update Webhook' : 'New Webhook' }}
                </h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configure your webhook endpoint
                </p>
            </div>
            @if($editingId)
                <button wire:click="cancelEdit"
                    class="text-xs font-bold text-rose-500 uppercase tracking-widest hover:underline">Cancel
                    Editing</button>
            @endif
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <x-label value="Webhook Name"
                        class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                    <x-input wire:model="name" type="text"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-green-500/20"
                        placeholder="Production Webhook" />
                    <x-input-error for="name" />
                </div>
                <div class="space-y-2">
                    <x-label value="Endpoint URL"
                        class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                    <x-input wire:model="url" type="url"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-green-500/20"
                        placeholder="https://yourdomain.com/webhook" />
                    <x-input-error for="url" />
                </div>
            </div>

            <div class="mt-6 space-y-2">
                <div class="flex items-center justify-between">
                    <x-label value="Signing Secret (Optional)"
                        class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                    <button wire:click="generateSecret" type="button"
                        class="text-xs font-bold text-green-600 hover:text-green-700 uppercase tracking-widest">Generate
                        Secret</button>
                </div>
                <x-input wire:model="secret" type="text"
                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-mono text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-green-500/20"
                    placeholder="Leave empty for unsigned webhooks" />
                <p class="text-[10px] text-slate-400 font-medium">Webhooks will be signed with HMAC-SHA256 if a secret
                    is provided.</p>
                <x-input-error for="secret" />
            </div>

            <div class="mt-6 space-y-2">
                <x-label value="Subscribe to Events (Leave empty for all)"
                    class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                <div class="grid grid-cols-2 gap-3">
                    @foreach($availableEvents as $key => $label)
                        <label
                            class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <input type="checkbox" wire:model="events" value="{{ $key }}"
                                class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                <label
                    class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <input type="checkbox" wire:model="is_active"
                        class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Active</span>
                </label>
            </div>
        </div>

        <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-800/10 flex justify-end gap-4">
            @if($editingId)
                <button wire:click="cancelEdit"
                    class="px-6 py-3 rounded-xl text-slate-500 font-black uppercase tracking-widest text-[10px] hover:bg-slate-100 transition-colors">
                    Cancel
                </button>
            @endif
            <button wire:click="{{ $editingId ? 'update' : 'create' }}"
                class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-xl shadow-xl shadow-slate-900/10 hover:scale-[1.02] active:scale-95 transition-all">
                {{ $editingId ? 'Save Changes' : 'Create Webhook' }}
            </button>
        </div>
    </div>

    <!-- Webhook Workflows (API Triggers) -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">API-Triggered
                        Workflows</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Send WhatsApp templates via
                        API endpoint</p>
                </div>
                <a href="{{ route('webhooks.index') }}"
                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase tracking-widest">
                    Manage Workflows →
                </a>
            </div>
        </div>

        <div class="p-8">
            @if($workflows->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($workflows->take(4) as $wf)
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ $wf->name }}</h4>
                                    <p class="text-xs text-slate-500 mt-1">Template: {{ $wf->template->name ?? 'N/A' }}</p>
                                </div>
                                <span
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold {{ $wf->status ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $wf->status ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                <span>⚡ {{ $wf->total_triggers }} triggers</span>
                                <span>✓ {{ $wf->total_delivered }} delivered</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($workflows->count() > 4)
                    <div class="mt-4 text-center">
                        <a href="{{ route('webhooks.index') }}" class="text-sm font-bold text-purple-600 hover:text-purple-700">
                            View all {{ $workflows->count() }} workflows →
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <div
                        class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-xs mb-4">No API workflows configured
                    </p>
                    <a href="{{ route('webhooks.index') }}"
                        class="inline-block px-6 py-3 bg-purple-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-purple-700 transition-colors">
                        Create Workflow
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>