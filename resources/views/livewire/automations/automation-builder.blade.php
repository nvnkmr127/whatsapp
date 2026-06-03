<div id="automation-builder-wrapper" class="h-full">
    <div class="h-full flex flex-col bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100"
        x-data="flowBuilder(
            $wire.entangle('nodes'),
            $wire.entangle('edges'),
            $wire.entangle('debugLogs'),
            $wire.entangle('validationIssues'),
            $wire.entangle('stepMetadata'),
            $wire.entangle('isDirty')
        )" @keydown.window="handleKeyboard($event)">

        @include('livewire.automations.builder-scripts')

        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="fixed top-20 right-10 z-[100] bg-wa-teal text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 animate-in slide-in-from-right-full duration-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <div class="text-sm font-black uppercase tracking-tight">{{ session('success') }}</div>
                <button @click="show = false" class="ml-2 hover:opacity-70 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Top Toolbar -->
        <div class="h-16 flex-none bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-50 shadow-sm">
            <div class="flex items-center gap-3">
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

                <!-- Undo / Redo -->
                <div class="flex items-center gap-1 ml-2">
                    <button @click="$wire.undo()" title="Undo (Ctrl+Z)" :disabled="$wire.historyIndex <= 0"
                        class="p-1.5 rounded-lg transition-colors text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </button>
                    <button @click="$wire.redo()" title="Redo (Ctrl+Y)" :disabled="$wire.historyIndex >= ($wire.history?.length || 0) - 1"
                        class="p-1.5 rounded-lg transition-colors text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
                    </button>
                </div>

                <!-- Templates Button -->
                <button @click="$wire.openTemplatesModal()" title="Browse Flow Templates"
                    class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-wa-teal hover:border-wa-teal/40 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    Templates
                </button>
            </div>

            <div class="flex items-center gap-3">
                <!-- Test Mode -->
                <button @click="$wire.openTestModal()" title="Preview / Simulate Flow"
                    class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Test Run
                </button>

                <div x-show="!isDirty" class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Synced
                </div>
                <div x-show="isDirty" class="flex items-center gap-1.5 px-3 py-1 bg-amber-50 dark:bg-amber-900/20 rounded-full text-[10px] font-bold text-amber-600 uppercase tracking-widest animate-pulse">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Unsaved
                </div>
                <button type="button" wire:click.prevent="save" class="text-xs font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-wa-teal/30 transition shadow-sm">Save</button>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>
                <button type="button" wire:click.prevent="publish" class="text-xs font-black uppercase tracking-widest px-5 py-2 rounded-xl flex items-center gap-2 transition shadow-lg" :class="!$wire.isActivatable ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/20' : 'bg-wa-teal hover:bg-wa-dark text-white shadow-wa-teal/20'">
                    <span x-text="!$wire.isActivatable ? 'Fix Errors to Publish' : ($wire.automationId ? 'Deploy Update' : 'Go Live')"></span>
                    <svg x-show="!$wire.isActivatable" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <svg x-show="$wire.isActivatable" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>

        <!-- Workspace -->
        <div class="flex-1 flex overflow-hidden relative">
            @include('livewire.automations.palette')

            <!-- Center: Infinite Canvas -->
            <div class="flex-1 bg-slate-50 dark:bg-slate-950 relative overflow-hidden cursor-grab active:cursor-grabbing" id="canvas-container"
                 @mousedown="startPan($event)" @mousemove="pan($event)" @mouseup="endPan()" @mouseleave="endPan()" @wheel="zoom($event)">

                <!-- Minimap -->
                <div class="absolute top-4 right-4 z-50 w-44 h-28 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-xl border border-slate-200 dark:border-slate-700 shadow-lg overflow-hidden" style="pointer-events:none">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <template x-for="node in nodesArray" :key="node.id + '-mm'">
                            <div class="absolute rounded-sm transition-all"
                                :class="selectedId === node.id ? 'bg-wa-teal' : 'bg-slate-300 dark:bg-slate-600'"
                                :style="`width: 8px; height: 5px; left: calc(50% + ${node.x * 0.05}px); top: calc(50% + ${node.y * 0.05}px); transform: translate(-50%,-50%)`">
                            </div>
                        </template>
                    </div>
                    <div class="absolute bottom-1 right-2 text-[8px] font-black text-slate-400 uppercase tracking-widest">minimap</div>
                </div>

                <!-- Validation Checklist -->
                <div x-show="validationIssues && validationIssues.length > 0" class="absolute bottom-24 right-6 z-[60] w-80 max-h-[400px] flex flex-col bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden pointer-events-auto">
                    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest">Flow Checklist</h4>
                        <span class="text-[10px] font-bold bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 px-2 py-0.5 rounded-full" x-text="validationIssues.filter(i=>i.level==='error').length + ' errors'"></span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                        <template x-for="issue in validationIssues" :key="JSON.stringify(issue)">
                            <div @click="if(issue.node_id) { selectedId = issue.node_id; $wire.selectNode(issue.node_id); if(issue.field) focusField(issue.field); }"
                                 class="group p-3 rounded-xl border cursor-pointer transition-all"
                                 :class="issue.level === 'error' ? 'border-rose-100 hover:border-rose-300 bg-rose-50/50 dark:border-rose-900/30 dark:bg-rose-900/10' : 'border-amber-100 hover:border-amber-300 bg-amber-50/50 dark:border-amber-900/30 dark:bg-amber-900/10'">
                                 <div class="flex items-start gap-2">
                                     <div class="w-1.5 h-1.5 rounded-full mt-1.5 flex-none" :class="issue.level === 'error' ? 'bg-rose-500' : 'bg-amber-500'"></div>
                                     <p class="text-[11px] font-bold leading-tight" :class="issue.level === 'error' ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300'" x-text="issue.message"></p>
                                 </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Canvas -->
                <div id="canvas" class="absolute inset-0 origin-top-left" wire:ignore :style="`transform: translate(${panX}px, ${panY}px) scale(${scale});`" @click.self="deselectAll()">
                    <div class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none opacity-[0.045] dark:opacity-[0.025]" style="background-image: radial-gradient(circle, #64748b 1px, transparent 1px); background-size: 32px 32px;"></div>
                    <canvas x-ref="canvas" width="10000" height="10000" class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none z-[1]"></canvas>
                    <template x-for="node in nodes" :key="node.id">
                        <x-automations.node />
                    </template>
                </div>

                <!-- Controls -->
                <div class="absolute bottom-6 right-6 flex items-center gap-2 z-50">
                    <button @click="zoomToFit()" title="Fit to Screen (F)" class="p-2 bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-wa-teal transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    </button>
                    <div class="flex items-center bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-1">
                        <button @click="scale = Math.min(scale + 0.15, 2.5)" class="p-2 text-slate-500 hover:text-wa-teal transition-colors font-bold text-sm leading-none">+</button>
                        <button @click="scale = 1; panX = 0; panY = 0" class="px-2 text-slate-400 text-[10px] font-black hover:text-wa-teal transition-colors" x-text="Math.round(scale * 100) + '%'"></button>
                        <button @click="scale = Math.max(scale - 0.15, 0.3)" class="p-2 text-slate-500 hover:text-wa-teal transition-colors font-bold text-sm leading-none">-</button>
                    </div>
                    <!-- Keyboard shortcuts hint -->
                    <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-[9px] text-slate-400 font-mono hidden lg:block">
                        F fit · Del remove · Ctrl+Z undo · Ctrl+D copy
                    </div>
                </div>
            </div>

            @include('livewire.automations.properties')
        </div>

        @include('livewire.automations.modals')
    </div>
</div>
