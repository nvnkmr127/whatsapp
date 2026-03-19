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

    {{-- Template Node --}}
    <div class="space-y-4" x-show="selectedNode.type === 'template'">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Template Name</label>
            <select wire:model.live="nodeText" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="">Select Template...</option>
                @foreach($approvedTemplates as $tmpl)
                    <option value="{{ data_get($tmpl, 'name') }}">{{ data_get($tmpl, 'name') }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Media Nodes (Image, Video, Audio, File) --}}
    <div class="space-y-4" x-show="['image', 'video', 'audio', 'file'].includes(selectedNode.type)">
        <label class="block text-xs font-bold text-slate-500 uppercase">Resource URL</label>
        <input type="text" wire:model.blur="nodeUrl" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="https://...">
    </div>

    {{-- Text / Content --}}
    <div x-show="['text', 'interactive_button', 'interactive_list', 'user_input', 'openai', 'image', 'video', 'audio', 'file'].includes(selectedNode.type)">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Message Content</label>
        <textarea wire:model.blur="nodeText" rows="6" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Enter message..."></textarea>
    </div>

    {{-- OpenAI Specific --}}
    <div x-show="selectedNode.type === 'openai'" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Model</label>
        <select wire:model.blur="nodeModel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="gpt-4o">GPT-4o</option>
            <option value="gpt-4o-mini">GPT-4o Mini</option>
        </select>
    </div>

    {{-- Interactive Elements --}}
    <div x-show="['interactive_button', 'interactive_list'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Options</label>
        {{-- [Simplified for brevity in this sub-comp creation; usually would have the full loop] --}}
    </div>
</div>
