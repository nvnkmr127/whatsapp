<div id="automation-builder-wrapper" class="h-full">
    <div class="h-full flex flex-col bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100"
        x-data="flowBuilder">

        @include('livewire.automations.builder-scripts')

        <!-- Top Toolbar -->
        <div class="h-16 flex-none bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-50">
            <div class="flex items-center gap-4">
                <a href="{{ route('automations.index') }}" class="p-2 -ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Back to Bots">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex flex-col">
                    <input type="text" wire:model.blur="name" class="bg-transparent border-0 p-0 text-sm font-bold text-slate-800 dark:text-white leading-tight focus:ring-0 placeholder-slate-400" placeholder="Untitled Bot">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">
                        {{ $triggerType === 'keyword' ? 'Keywords: ' . implode(', ', $triggerConfig['keywords'] ?? []) : ucfirst(str_replace('_', ' ', $triggerType)) }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click.prevent="save" class="text-xs font-bold px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">Save for Later</button>
                <button type="button" wire:click.prevent="publish" class="text-xs font-bold px-5 py-2 rounded-xl flex items-center gap-2 transition shadow-lg" :class="!$wire.isActivatable ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/20' : 'bg-wa-teal hover:bg-wa-dark text-white shadow-wa-teal/20'">
                    <span x-text="!$wire.isActivatable ? 'Fix Errors to Turn On' : 'Turn On'"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>

        <!-- Workspace -->
        <div class="flex-1 flex overflow-hidden relative">
            @include('livewire.automations.palette')

            <!-- Center: Infinite Canvas -->
            <div class="flex-1 bg-slate-50 dark:bg-slate-950 relative overflow-hidden cursor-grab active:cursor-grabbing" id="canvas-container"
                 @mousedown="startPan($event)" @mousemove="pan($event)" @mouseup="endPan()" @mouseleave="endPan()" @wheel="zoom($event)">
                 
                <!-- Checklist Sidebar -->
                <div x-show="validationIssues && validationIssues.length > 0" class="absolute bottom-24 right-6 z-[60] w-80 max-h-[400px] flex flex-col bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden pointer-events-auto">
                    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest">Checklist</h4>
                        </div>
                        <span class="text-[10px] font-bold bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-600 dark:text-slate-300" x-text="validationIssues.length"></span>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        <template x-for="issue in validationIssues" :key="JSON.stringify(issue)">
                            <div @click="if(issue.node_id) { selectedId = issue.node_id; $wire.selectNode(issue.node_id); if(issue.field) focusField(issue.field); }" 
                                 class="group p-3 rounded-xl border border-transparent hover:border-slate-200 transition-all cursor-pointer">
                                 <p class="text-[11px] font-bold text-slate-700 group-hover:text-wa-teal" x-text="issue.message"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Canvas -->
                <div id="canvas" class="absolute inset-0 origin-top-left" wire:ignore :style="`transform: translate(${panX}px, ${panY}px) scale(${scale});`" @click.self="deselectAll()">
                    <div class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none opacity-[0.05] dark:opacity-[0.03]" style="background-image: linear-gradient(to right, #64748b 1px, transparent 1px), linear-gradient(to bottom, #64748b 1px, transparent 1px); background-size: 40px 40px;"></div>
                    <canvas x-ref="canvas" width="10000" height="10000" class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none z-[1]"></canvas>
                    <template x-for="node in nodes" :key="node.id">
                        <x-automations.node />
                    </template>
                </div>

                <!-- Controls -->
                <div class="absolute bottom-6 right-6 flex items-center gap-2 z-50">
                    <div class="flex items-center bg-white dark:bg-slate-900 rounded-lg shadow-lg border p-1">
                        <button @click="scale = Math.min(scale + 0.1, 2)" class="p-2 text-slate-500">+</button>
                        <button @click="scale = Math.max(scale - 0.1, 0.5)" class="p-2 text-slate-500">-</button>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg border px-3 py-2 text-xs font-bold font-mono">
                        <span x-text="Math.round(scale * 100) + '%'"></span>
                    </div>
                </div>
            </div>

            @include('livewire.automations.properties')
        </div>

        @include('livewire.automations.modals')
    </div>
</div>
