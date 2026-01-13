<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg dark:bg-purple-500/10 dark:text-purple-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <!-- Premium Header Style -->
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Webhook <span class="text-purple-600 dark:text-purple-400">Workflows</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Trigger WhatsApp messages from external systems via API.</p>
        </div>
        
        <!-- Search (Optional, keeping consistent with AutomationList) -->
        <!--
        <div class="relative group w-full sm:w-64">
             <input type="text" class="..." placeholder="Search...">
        </div>
        -->
    </div>

    <!-- Workflows List Table -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Workflow Name</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Template Strategy</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Performance (Triggers / Delivered)</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($workflows as $wf)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                     <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ $wf->name }}</div>
                                        <div class="text-[10px] font-mono text-slate-400 mt-1 select-all">ID: {{ $wf->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $wf->template->name ?? 'N/A' }}</span>
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">{{ $wf->template->language ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                 <button wire:click="toggleStatus({{ $wf->id }})" class="group/toggle flex items-center gap-2 focus:outline-none">
                                    <span class="w-2 h-2 rounded-full {{ $wf->status ? 'bg-green-500 shadow-lg shadow-green-500/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                    <span class="text-xs font-black uppercase tracking-widest {{ $wf->status ? 'text-green-500' : 'text-rose-500' }}">
                                        {{ $wf->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1 min-w-[100px]">
                                        <div class="flex justify-between text-[10px] font-bold uppercase text-slate-400 mb-1">
                                            <span>Triggers</span>
                                            <span class="text-blue-500">{{ $wf->total_triggers }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-[100px]">
                                        <div class="flex justify-between text-[10px] font-bold uppercase text-slate-400 mb-1">
                                            <span>Delivered</span>
                                            <span class="text-green-500">{{ $wf->total_delivered }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-green-500 rounded-full" style="width: {{ $wf->total_triggers > 0 ? ($wf->total_delivered / $wf->total_triggers) * 100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="edit({{ $wf->id }})" class="p-2 text-slate-400 hover:text-purple-500 transition-colors" title="Edit Workflow">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <a href="{{ route('webhooks.report', $wf->id) }}" class="p-2 text-slate-400 hover:text-blue-500 transition-colors" title="View Report">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </a>
                                     <button wire:click="delete({{ $wf->id }})" class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Delete Workflow">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <div class="mt-2 text-[10px] text-slate-300 font-mono select-all text-right">
                                    POST /api/webhooks/trigger/{{ $wf->id }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                     <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </div>
                                    <div class="text-slate-400 font-bold uppercase tracking-widest text-xs">No Workflows Configured</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($workflows->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $workflows->links() }}
            </div>
        @endif
    </div>

    <!-- Inline Editor (Create/Update) -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">
        <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $editingId ? 'Update Configuration' : 'New Configuration' }}</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Define your webhook triggers and response logic</p>
            </div>
             @if($editingId)
                <button wire:click="cancelEdit" class="text-xs font-bold text-rose-500 uppercase tracking-widest hover:underline">Cancel Editing</button>
            @endif
        </div>
        
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <x-label value="Workflow Designation" class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                    <x-input wire:model="name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500/20" placeholder="e.g. ORDER_CONFIRMATION_FLOW" />
                    <x-input-error for="name" />
                </div>
                <div class="space-y-2">
                    <x-label value="Response Template" class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                    <div class="relative">
                        <select wire:model="whatsapp_template_id" class="w-full appearance-none bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 px-4 font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500/20">
                            <option value="">Select Response Template...</option>
                            @foreach($this->templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->language }})</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                             <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <x-input-error for="whatsapp_template_id" />
                </div>
            </div>
        </div>

        <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-800/10 flex justify-end gap-4">
             @if($editingId)
                <button wire:click="cancelEdit" class="px-6 py-3 rounded-xl text-slate-500 font-black uppercase tracking-widest text-[10px] hover:bg-slate-100 transition-colors">
                    Cancel
                </button>
            @endif
            <button wire:click="{{ $editingId ? 'update' : 'create' }}" class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-xl shadow-xl shadow-slate-900/10 hover:scale-[1.02] active:scale-95 transition-all">
                {{ $editingId ? 'Save Changes' : 'Initialize Workflow' }}
            </button>
        </div>
    </div>
</div>