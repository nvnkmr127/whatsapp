@props(['node'])

<div class="absolute w-72 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border cursor-default transition-all hover:shadow-2xl group flex flex-col z-10"
    :class="{
        'ring-2 ring-wa-teal dark:ring-wa-teal ring-offset-2 ring-offset-slate-50 dark:ring-offset-slate-950': selectedId === node.id,
        'border-rose-500 ring-rose-500/20 shadow-rose-500/10': hasError(node.id),
        'border-amber-500 ring-amber-500/20 shadow-amber-500/10': hasWarning(node.id) && !hasError(node.id),
        'border-slate-200 dark:border-slate-800': !hasError(node.id) && !hasWarning(node.id),
        'opacity-60 grayscale-[0.5]': getIssue(node.id)?.message?.includes('unreachable')
    }" :style="`left: ${node.x}px; top: ${node.y}px`" @mousedown.stop="startDrag($event, node)"
    @click.stop="$wire.selectNode(node.id)">

    <!-- Validation Error Badge -->
    <template x-if="hasError(node.id) || hasWarning(node.id)">
        <div class="absolute -top-3 -right-3 z-30 flex items-center justify-center p-1.5 rounded-full shadow-lg"
            :class="hasError(node.id) ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white'"
            :title="getIssue(node.id)?.message">
            <svg x-show="hasError(node.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <svg x-show="hasWarning(node.id) && !hasError(node.id)" class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </template>

    <!-- Node Header -->
    <div
        class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50 rounded-t-2xl">
        <div class="flex items-center gap-3">
            <!-- Step Number Badge -->
            <template x-if="stepMetadata?.nodes && stepMetadata.nodes[node.id]">
                <div class="flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-[10px] font-black text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-600"
                    :title="'Execution Order: ' + stepMetadata.nodes[node.id].step">
                    <span x-text="stepMetadata.nodes[node.id].step"></span>
                </div>
            </template>

            <span class="w-2 h-2 rounded-full" :class="{
                       'bg-blue-500': ['text','message'].includes(node.type),
                       'bg-green-500': node.type === 'template',
                       'bg-purple-500': ['image','media'].includes(node.type),
                       'bg-orange-500': ['interactive_button','interactive_list'].includes(node.type),
                       'bg-amber-500': ['condition','trigger'].includes(node.type),
                       'bg-cyan-500': node.type === 'user_input',
                       'bg-pink-500': ['webhook'].includes(node.type),
                       'bg-indigo-500': ['crm_sync'].includes(node.type),
                   }"></span>
            <div class="flex flex-col">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide"
                    x-text="node.data.label || node.type"></span>

                <!-- Branch/Loop Indicators -->
                <div class="flex items-center gap-2 mt-0.5" x-show="stepMetadata?.nodes && stepMetadata.nodes[node.id]">
                    <template x-if="stepMetadata.nodes[node.id]?.isBranch">
                        <span
                            class="flex items-center gap-0.5 text-[8px] font-black text-blue-500 uppercase tracking-tighter">
                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Parallel Branch
                        </span>
                    </template>
                    <template x-if="stepMetadata.nodes[node.id]?.isLoop">
                        <span
                            class="flex items-center gap-0.5 text-[8px] font-black text-amber-500 uppercase tracking-tighter">
                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Loop detected
                        </span>
                    </template>
                </div>
            </div>
        </div>
        <button @click.stop="deleteNode(node.id)"
            class="text-slate-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
            </svg>
        </button>
    </div>

    <!-- Node Body -->
    <div class="p-4 space-y-2">
        <!-- Preview Text -->
        <div class="text-xs text-slate-500 dark:text-slate-400 line-clamp-3 min-h-[1.5em]"
            x-text="node.data.text || node.data.question || node.data.caption || node.data.template_name || 'Generic Node'">
        </div>

        <!-- Tags (Buttons/Options) -->
        <div class="flex flex-wrap gap-1">
            <template
                x-for="option in (node.data.options || (node.data.buttons ? node.data.buttons.map(b=>b.title) : []))">
                <span
                    class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                    x-text="option"></span>
            </template>
        </div>

        <!-- Template for Trigger -->
        <template
            x-if="node.type === 'trigger' && ['template_selected', 'template_response'].includes(node.data.trigger_type)">
            <div
                class="flex flex-col gap-1 p-2 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800/50">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-black uppercase text-green-600 dark:text-green-400">Template</span>
                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-200"
                        x-text="node.data.template_name"></span>
                </div>
                <template x-if="node.data.trigger_type === 'template_response' && node.data.button_text">
                    <div class="flex items-center gap-2 border-t border-green-100 dark:border-green-800/30 pt-1 mt-1">
                        <span class="text-[9px] font-black uppercase text-green-600 dark:text-green-400">Button</span>
                        <span
                            class="px-1.5 py-0.5 rounded bg-white dark:bg-slate-900 text-[9px] font-bold text-wa-teal border border-wa-teal/30"
                            x-text="node.data.button_text"></span>
                    </div>
                </template>
            </div>
        </template>

        <!-- Keywords for Trigger -->
        <template
            x-if="node.type === 'trigger' && node.data.trigger_type === 'keyword' && node.data.keywords && node.data.keywords.length">
            <div class="flex flex-col gap-1">
                <span class="text-[9px] font-black uppercase text-slate-400">Keywords</span>
                <div class="flex flex-wrap gap-1">
                    <template x-for="kw in node.data.keywords">
                        <span
                            class="px-1.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-[9px] font-bold border border-amber-100 dark:border-amber-800/50"
                            x-text="kw"></span>
                    </template>
                </div>
            </div>
        </template>

        <!-- Tags for Trigger -->
        <template x-if="node.type === 'trigger' && (node.data.add_tags?.length || node.data.remove_tags?.length)">
            <div class="flex flex-col gap-1 pt-1">
                <span class="text-[9px] font-black uppercase text-slate-400">Assignment</span>
                <div class="flex flex-wrap gap-1">
                    <template x-for="tag in (node.data.add_tags || [])">
                        <span
                            class="px-1.5 py-0.5 rounded-md bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[9px] font-bold border border-green-100 dark:border-green-800/50 flex items-center gap-1">
                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span x-text="tag"></span>
                        </span>
                    </template>
                    <template x-for="tag in (node.data.remove_tags || [])">
                        <span
                            class="px-1.5 py-0.5 rounded-md bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[9px] font-bold border border-red-100 dark:border-red-800/50 flex items-center gap-1">
                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" />
                            </svg>
                            <span x-text="tag"></span>
                        </span>
                    </template>
                </div>
            </div>
        </template>

        <!-- Condition Details -->
        <template x-if="node.type === 'condition'">
            <div
                class="flex items-center gap-1 text-[10px] bg-slate-50 dark:bg-slate-800/50 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                <span class="font-bold text-slate-700 dark:text-slate-300"
                    x-text="node.data.variable || 'variable'"></span>
                <span class="text-wa-teal font-black" x-text="node.data.operator || '=='"></span>
                <span class="text-slate-600 dark:text-slate-400" x-text="node.data.value || '...'"></span>
            </div>
        </template>

        <div x-show="node.type === 'openai'"
            class="flex items-center gap-1 text-[10px] text-emerald-600 dark:text-emerald-400">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z">
                </path>
            </svg>
            <span x-text="node.data.model || 'GPT-4o'"></span>
        </div>
    </div>


    <!-- Handles (Hit Areas Enhanced) -->
    <!-- Input -->
    <template x-if="node.type !== 'trigger'">
        <div class="absolute -left-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
            @mouseup="endConnect(node.id)">
            <div
                class="w-4 h-4 bg-slate-100 dark:bg-slate-800 rounded-full border-2 border-slate-300 dark:border-slate-600 group-hover/handle:border-wa-teal group-hover/handle:scale-125 transition-all">
            </div>
        </div>
    </template>

    <!-- Output -->
    <div class="absolute -right-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
        @mousedown.stop="startConnect($event, node.id)">
        <div
            class="w-4 h-4 bg-wa-teal rounded-full border-2 border-white dark:border-slate-900 group-hover/handle:scale-125 transition-all shadow-sm">
        </div>
    </div>

</div>