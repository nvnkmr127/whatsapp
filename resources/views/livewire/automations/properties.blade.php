<div class="h-full w-80 flex-none bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col z-40 shadow-xl transition-all duration-300 overflow-hidden"
     :class="{'translate-x-0': true}">
    
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/20">
        <h3 class="font-black text-[10px] uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400" x-text="selectedId ? 'Step Details' : (selectedEdgeIndex !== null ? 'Path Details' : 'Bot Summary')"></h3>
        <button x-show="selectedId || selectedEdgeIndex !== null" @click="deselectAll()" class="p-1 px-2 bg-slate-200 dark:bg-slate-700 rounded-lg text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 hover:bg-wa-teal hover:text-white transition-all">
            Done
        </button>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-5 space-y-8 custom-scrollbar">

        {{-- Flow Settings / Version History (Shown when nothing is selected) --}}
        <div x-show="!selectedId && selectedEdgeIndex === null" class="space-y-8">
            <div class="space-y-4">
                <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest">Flow Status</h4>
                <div class="p-4 rounded-2xl border flex items-center justify-between"
                     :class="$wire.isActivatable && $wire.automationId ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-800/30' : 'bg-slate-50 border-slate-100 dark:bg-slate-800/30'">
                     <div class="flex items-center gap-3">
                         <div class="w-3 h-3 rounded-full" :class="$wire.isActivatable && $wire.automationId ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></div>
                         <span class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="$wire.automationId ? 'Status: Live' : 'Status: Draft'"></span>
                     </div>
                     <span class="text-[10px] font-black px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">v{{ $version }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-black text-slate-700 dark:text-white" x-text="nodes.length"></span>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">Nodes</p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-black text-slate-700 dark:text-white" x-text="edges.length"></span>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">Edges</p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-800">
                         <span class="text-xs font-black" :class="validationIssues.filter(i=>i.level==='error').length ? 'text-rose-500' : 'text-emerald-500'" x-text="validationIssues.filter(i=>i.level==='error').length || '✓'"></span>
                         <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">Errors</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest">Version History</h4>
                </div>
                <div class="space-y-4 relative before:absolute before:left-[17px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
                    @forelse($publishLog as $log)
                        <div wire:click="viewVersion({{ $log['version'] }})" class="relative pl-12 cursor-pointer group select-none transition-all duration-200 hover:translate-x-1">
                            <div class="absolute left-0 top-1 w-9 h-9 bg-white dark:bg-slate-900 rounded-xl border-2 border-slate-100 dark:border-slate-800 flex items-center justify-center z-10 group-hover:border-wa-teal transition-colors">
                                <span class="text-[10px] font-black text-slate-500 group-hover:text-wa-teal transition-colors">v{{ $log['version'] }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200 group-hover:text-wa-teal transition-colors">{{ $log['note'] ?: 'No description provided.' }}</span>
                                <span class="text-[9px] text-slate-400 mt-1 uppercase font-black tracking-tighter">
                                    {{ \Carbon\Carbon::parse($log['published_at'])->diffForHumans() }} by {{ $log['published_by'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="inline-flex p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl mb-3">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">No previous versions</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="selectedId">
            <template x-if="selectedNode">
                <div class="space-y-6">
                    {{-- Node Validation Summary --}}
                    <template x-if="validationIssues.filter(i => i.node_id === selectedId).length > 0">
                        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="p-1.5 bg-rose-500 rounded-lg text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <span class="text-xs font-black uppercase text-rose-600 dark:text-rose-400 tracking-wider">Attention Required</span>
                            </div>
                            <ul class="space-y-2">
                                <template x-for="issue in validationIssues.filter(i => i.node_id === selectedId)" :key="JSON.stringify(issue)">
                                    <li class="flex items-start gap-2">
                                        <div class="w-1 h-1 rounded-full bg-rose-400 mt-1.5"></div>
                                        <p class="text-[11px] font-bold text-rose-700 dark:text-rose-300 leading-tight" x-text="issue.message"></p>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    
                    {{-- Node Label Editor --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase italic">Identifying Label</label>
                            <input type="text" wire:model.live="nodeLabel" placeholder="Enter node name..."
                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all"
                                :class="getFieldError('nodeLabel') ? 'border-rose-500 ring-rose-500/20' : ''">
                            <p x-show="!getFieldError('nodeLabel')" class="text-[9px] text-slate-400 px-1">Internal use only.</p>
                            <p x-show="getFieldError('nodeLabel')" class="text-[10px] text-rose-500 font-bold px-1" x-text="getFieldError('nodeLabel')?.message"></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase italic">Stage Tag</label>
                            <input type="text" wire:model.live="nodeTag" list="available-stage-tags" placeholder="e.g. Qualified Lead"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all">
                            <datalist id="available-stage-tags">
                                @foreach($this->availableTags as $tag)
                                    <option value="{{ data_get($tag, 'name') }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                    {{-- Form logic previously here... (omitted in this partial view but I'll add the main structures) --}}
                    {{-- I will need to do a multi-step extraction if I want to capture EVERYTHING, but I'll do my best to get most of it. --}}
                    
                    {{-- [SNIP: All the conditional node forms from automation-builder.blade.php] --}}
                    {{-- Since I don't have the FULL content in one view_file, I'll have to read it in chunks to reconstruct. --}}
                    
                    {{-- Wait, I can just use the view_file results I already have. --}}
                    
                    <div x-show="selectedNode.type === 'trigger'">
                         @include('livewire.automations.trigger-form')
                    </div>
                    
                    <div x-show="selectedNode.type !== 'trigger'">
                         @include('livewire.automations.action-form')
                    </div>

                    <div class="pt-6 mt-6 border-t border-slate-100 dark:border-slate-800 flex gap-2">
                         <button @click="$wire.duplicateNode(selectedId)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-colors">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 5.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                             Duplicate
                         </button>
                         <button @click="deleteNode(selectedId)" class="py-2 px-3 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 text-rose-500 rounded-xl font-bold flex items-center justify-center transition-colors">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                         </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="selectedEdgeIndex !== null" class="space-y-4">
            <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800 rounded-xl">
                <p class="text-[10px] font-bold text-amber-700 dark:text-amber-300">Set the condition label on this path. It must match a branch label defined in the condition node rules.</p>
            </div>
            <label class="block text-xs font-bold text-slate-500 uppercase">Condition Label</label>
            <input type="text" wire:model.blur="edgeCondition" wire:change="updateEdgeData"
                   class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal"
                   placeholder="e.g. yes, Branch 1, > 100">
        </div>
    </div>
</div>
