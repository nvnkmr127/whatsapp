@props(['node'])

<div class="absolute w-72 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 cursor-default transition-shadow hover:shadow-2xl group flex flex-col z-10"
    :class="{'ring-2 ring-wa-teal dark:ring-wa-teal ring-offset-2 ring-offset-slate-50 dark:ring-offset-slate-950': selectedId === node.id}"
    :style="`left: ${node.x}px; top: ${node.y}px`" @mousedown.stop="startDrag($event, node)"
    @click.stop="$wire.selectNode(node.id)">

    <!-- Node Header -->
    <div
        class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50 rounded-t-2xl">
        <div class="flex items-center gap-3">
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
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide"
                x-text="node.data.label || node.type"></span>
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
</div>

<!-- Handles (Hit Areas Enhanced) -->
<!-- Input -->
<div class="absolute -left-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
    @mouseup="endConnect(node.id)">
    <div
        class="w-4 h-4 bg-slate-100 dark:bg-slate-800 rounded-full border-2 border-slate-300 dark:border-slate-600 group-hover/handle:border-wa-teal group-hover/handle:scale-125 transition-all">
    </div>
</div>

<!-- Output -->
<div class="absolute -right-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
    @mousedown.stop="startConnect($event, node.id)">
    <div
        class="w-4 h-4 bg-wa-teal rounded-full border-2 border-white dark:border-slate-900 group-hover/handle:scale-125 transition-all shadow-sm">
    </div>
</div>

</div>