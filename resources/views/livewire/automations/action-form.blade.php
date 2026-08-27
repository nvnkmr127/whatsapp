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
                @foreach($this->approvedTemplates as $tmpl)
                    <option value="{{ data_get($tmpl, 'name') }}">{{ data_get($tmpl, 'name') }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Media Nodes (Image, Video, Audio, File) --}}
    <div class="space-y-4" x-show="['image', 'video', 'audio', 'file'].includes(selectedNode.type)">
        <label class="block text-xs font-bold text-slate-500 uppercase">Input Method</label>
        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-4">
            <button @click="$wire.set('nodeUrl', '')" class="py-1.5 text-[10px] font-bold uppercase rounded-lg transition-all" :class="!$wire.nodeUrl || !$wire.nodeUrl.startsWith('http') ? 'bg-white dark:bg-slate-700 shadow-sm' : ''">Upload</button>
            <button @click="$wire.set('nodeUrl', 'https://')" class="py-1.5 text-[10px] font-bold uppercase rounded-lg transition-all" :class="$wire.nodeUrl && $wire.nodeUrl.startsWith('http') ? 'bg-white dark:bg-slate-700 shadow-sm' : ''">URL</button>
        </div>

        <div x-show="$wire.nodeUrl && $wire.nodeUrl.startsWith('http')">
            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Resource URL</label>
            <input type="text" wire:model.blur="nodeUrl" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="https://...">
        </div>

        <div x-show="!$wire.nodeUrl || !$wire.nodeUrl.startsWith('http')">
            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Upload File</label>
            <div class="relative group">
                <input type="file" wire:model="uploadFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                <div class="w-full py-6 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center gap-2 group-hover:border-wa-teal/50 transition-colors">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="text-xs font-bold text-slate-500" x-text="$wire.uploadFile ? 'File Selected' : 'Click to Upload'">Click to Upload</span>
                </div>
            </div>
            <div wire:loading wire:target="uploadFile" class="mt-2 text-[10px] font-bold text-wa-teal animate-pulse">Uploading...</div>
        </div>
    </div>

    <div x-show="['carousel'].includes(selectedNode.type)" class="space-y-6">
         <div class="flex items-center justify-between">
             <label class="block text-xs font-bold text-slate-500 uppercase">Carousel Cards</label>
             <button type="button" @click="$wire.addCard()" class="text-[10px] font-black uppercase text-wa-teal">+ Add Card</button>
         </div>
         <div class="space-y-4">
             <template x-for="(card, idx) in ($wire.nodeCards || [])" :key="idx">
                 <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3 relative group">
                     <button type="button" @click="$wire.removeCard(idx)" class="absolute top-2 right-2 text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                     </button>
                     <input type="text" x-model="card.image_url" placeholder="Image URL" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
                     <input type="text" x-model="card.title" placeholder="Title" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold">
                     <input type="text" x-model="card.sub_title" placeholder="Sub-title" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
                 </div>
             </template>
         </div>
    </div>

    <div x-show="['text', 'interactive_button', 'interactive_list', 'user_input', 'openai', 'image', 'video', 'audio', 'file'].includes(selectedNode.type)">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Message Content</label>
        <textarea wire:model.blur="nodeText" rows="4" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Enter message..."></textarea>
    </div>

    {{-- OpenAI Specific --}}
    <div x-show="selectedNode.type === 'openai'" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">AI Model & Scope</label>
        <select wire:model.blur="nodeModel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="gpt-4o">GPT-4o (Most Capable)</option>
            <option value="gpt-4o-mini">GPT-4o Mini (Fast/Cheap)</option>
        </select>
        
        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="flex flex-col">
                <span class="text-xs font-bold">Knowledge Base</span>
                <span class="text-[10px] text-slate-400">Enable AI to answer from your docs</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.live="nodeUseKb" class="sr-only peer">
                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal"></div>
            </label>
        </div>

        <div x-show="$wire.nodeUseKb" class="space-y-2 animate-in fade-in slide-in-from-top-1 duration-300">
            <label class="block text-[10px] font-black uppercase text-slate-400">Knowledge Sources</label>
            <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar pr-1">
                @foreach($this->availableKnowledgeBaseSources as $source)
                    <div class="flex items-center gap-2 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <input type="checkbox" wire:model.live="nodeKbSourceIds" value="{{ $source['id'] }}" class="rounded text-wa-teal focus:ring-wa-teal">
                        <span class="text-[11px] font-medium truncate">{{ $source['name'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <label class="block text-xs font-bold text-slate-500 uppercase">Save Response To</label>
        <input type="text" wire:model.blur="nodeSaveTo" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. ai_reason">
    </div>

    {{-- Interactive Elements --}}
    <div x-show="['interactive_button', 'interactive_list'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Interactive Options</label>
        <div class="space-y-2">
            <template x-for="(opt, idx) in ($wire.nodeOptions || [])" :key="idx">
                <div class="flex items-center gap-2">
                    <input type="text" x-model="opt.label" placeholder="Option Label" class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm py-2">
                    <button @click="$wire.removeOption(idx)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
            <button @click="$wire.addOption()" class="w-full py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-200 transition-all">+ Add Option</button>
        </div>
        
        <div x-show="selectedNode.type === 'interactive_list'" class="pt-4 space-y-4 border-t border-slate-100 dark:border-slate-800">
             <label class="block text-xs font-bold text-slate-500 uppercase">Menu Button Text</label>
             <input type="text" wire:model.blur="nodeButtonText" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. View Options">
        </div>
    </div>

    {{-- A/B Split Node --}}
    <div class="space-y-6" x-show="selectedNode.type === 'ab_split'">
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
                <label class="block text-xs font-black uppercase text-slate-400 tracking-widest">Traffic Split</label>
                <div class="px-3 py-1 bg-wa-teal/10 text-wa-teal rounded-lg text-xs font-black" x-text="$wire.nodeRatio + '% / ' + (100 - $wire.nodeRatio) + '%'">50% / 50%</div>
            </div>

            <input type="range" wire:model.live="nodeRatio" min="1" max="99" step="1"
                class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-teal mb-4">
            
            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">
                <div class="flex flex-col items-start gap-1">
                    <span class="text-slate-900 dark:text-white">Path A</span>
                    <span x-text="$wire.nodeRatio + '%'"></span>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span class="text-slate-900 dark:text-white">Path B</span>
                    <span x-text="(100 - $wire.nodeRatio) + '%'"></span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30">
            <div class="flex gap-3">
                <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-[11px] font-medium text-blue-700 dark:text-blue-300 leading-relaxed">
                    Split testing allows you to compare two different message variations. Path A will take the percentage set above, while Path B receives the remainder.
                </p>
            </div>
        </div>
    </div>

    <div x-show="['send_email'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Email Template Name</label>
        <input type="text" wire:model.blur="nodeLabel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. welcome_email">
        <label class="block text-xs font-bold text-slate-500 uppercase">Subject Line</label>
        <input type="text" wire:model.blur="nodeTag" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. Thanks for your order!">
        <label class="block text-xs font-bold text-slate-500 uppercase">Body Content</label>
        <textarea wire:model.blur="nodeText" rows="4" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Hello @{{name}},..."></textarea>
    </div>

    <div x-show="['create_deal'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Pipeline</label>
        <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Select Pipeline...</option>
            @foreach($this->availablePipelines as $pipe)
                <option value="{{ $pipe['id'] }}">{{ $pipe['name'] }}</option>
            @endforeach
        </select>
        <label class="block text-xs font-bold text-slate-500 uppercase">Deal Title</label>
        <input type="text" wire:model.blur="nodeLabel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. New Lead: @{{name}}">
    </div>

    <div x-show="['assign_to_agent'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Assigned User</label>
        <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="round_robin">Round Robin</option>
            @foreach($this->availableUsers as $user)
                <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="['loop_over_items'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Path to Items (Array)</label>
        <input type="text" wire:model.blur="nodeLabel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. api_result.products">
    </div>

    <div x-show="['set_variable'].includes(selectedNode.type)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Variable Key</label>
                <input type="text" wire:model.blur="nodeSaveTo" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. points">
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Operation</label>
                <select wire:model.live="nodeAction" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                    <option value="set">Set Value</option>
                    <option value="increment">Increment</option>
                    <option value="decrement">Decrement</option>
                    <option value="append">Append</option>
                </select>
            </div>
        </div>
        <label class="block text-xs font-bold text-slate-500 uppercase">Value</label>
        <input type="text" wire:model.blur="nodeText" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. 10 or @{{amount}}">
    </div>

    <div x-show="['webhook'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Endpoint URL</label>
        <input type="text" wire:model.blur="nodeUrl" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="https://api.myapp.com/webhook">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Method</label>
                <select wire:model.live="nodeMethod" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Save Response To</label>
                <input type="text" wire:model.blur="nodeSaveTo" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. api_result">
            </div>
        </div>
        <label class="block text-xs font-bold text-slate-500 uppercase">JSON Body / Headers (Optional)</label>
        <textarea wire:model.blur="nodeJson" rows="4" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono" placeholder='{"key": "value"}'></textarea>
    </div>

    <div x-show="['create_crm_task'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Task Title</label>
        <input type="text" wire:model.blur="nodeLabel" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. Call back lead">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Due In (Days)</label>
                <input type="number" wire:model.blur="nodeHours" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="2">
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Priority</label>
                <select wire:model.live="nodeTag" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
        </div>
    </div>

    <div x-show="['location_request', 'contact'].includes(selectedNode.type)" class="space-y-4">
         <label class="block text-xs font-bold text-slate-500 uppercase">Message Text</label>
         <textarea wire:model.blur="nodeText" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Please send your location..."></textarea>
         <div x-show="selectedNode.type === 'contact'">
             <label class="block text-xs font-bold text-slate-500 uppercase mt-2">vCard Data (JSON)</label>
             <textarea wire:model.blur="nodeJson" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono" placeholder='{"fn": "John Doe", "tel": "+1234567"}'></textarea>
         </div>
    </div>

    <div x-show="['tag_contact'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Tag to Assign/Remove</label>
        <div class="flex items-center gap-2">
            <select wire:model.live="nodeAction" class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="add">Add</option>
                <option value="remove">Remove</option>
            </select>
            <input type="text" wire:model.blur="nodeTag" list="available-stage-tags" 
                class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Select or type tag...">
        </div>
    </div>

    <div x-show="['send_flow'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">WhatsApp Flow Configuration</label>
        <select wire:model.live="nodeSaveTo" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Select Flow...</option>
            @foreach($this->availableFlows as $flow)
                <option value="{{ $flow['id'] }}">{{ $flow['name'] }}</option>
            @endforeach
        </select>
        <div class="space-y-3 pt-2">
            <label class="block text-[10px] font-black uppercase text-slate-400">Headline (Optional)</label>
            <input type="text" wire:model.blur="nodeLabel" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs" placeholder="Flow Headline">
            <label class="block text-[10px] font-black uppercase text-slate-400">Body Content</label>
            <textarea wire:model.blur="nodeText" rows="2" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs" placeholder="Message to send with flow..."></textarea>
            <label class="block text-[10px] font-black uppercase text-slate-400">Button Label</label>
            <input type="text" wire:model.blur="nodeButtonText" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs" placeholder="e.g. Open Form">
        </div>
    </div>

    <div x-show="selectedNode.type === 'google_sheets'" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Google Sheets Integration</label>
        <input type="text" wire:model.blur="nodeUrl" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Spreadsheet ID or URL">
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Sheet Name</label>
                <input type="text" wire:model.blur="nodeTag" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs" placeholder="Sheet1">
            </div>
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Action</label>
                <select wire:model.live="nodeAction" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
                    <option value="append_row">Append Row</option>
                    <option value="update_row">Update Row</option>
                    <option value="get_row">Get Row</option>
                </select>
            </div>
        </div>
    </div>

    <div x-show="selectedNode.type === 'handover'" class="space-y-4">
        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-2xl">
            <div class="flex gap-3">
                <div class="p-2 bg-indigo-500 rounded-xl text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-indigo-900 dark:text-indigo-100">Human Handoff</h4>
                    <p class="text-[10px] text-indigo-700 dark:text-indigo-300 mt-1">This will pause the automation and notify your agents to take over the conversation.</p>
                </div>
            </div>
        </div>
        <label class="block text-xs font-bold text-slate-500 uppercase">Target Team / Dept</label>
        <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="default">Default Support</option>
            <option value="sales">Sales Team</option>
            <option value="tech">Technical Support</option>
        </select>
    </div>

    <div x-show="selectedNode.type === 'stop_flow'" class="p-8 flex flex-col items-center justify-center text-center">
        <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/20 rounded-full flex items-center justify-center text-rose-500 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-200">End of Flow</h4>
        <p class="text-[10px] text-slate-400 mt-1">Stops all further execution of this automation run.</p>
    </div>

    <div x-show="['wait_until', 'delay'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Wait Duration</label>
        <div class="grid grid-cols-2 gap-4">
            <input type="number" wire:model.blur="nodeDelayValue" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <select wire:model.live="nodeDelayUnit" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="seconds">Seconds</option>
                <option value="minutes">Minutes</option>
                <option value="hours">Hours</option>
                <option value="days">Days</option>
            </select>
        </div>
    </div>

    <div x-show="['sub_flow'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Select Target Automation</label>
        <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Select Automation...</option>
            @foreach($this->availableWorkflows as $wf)
                <option value="{{ $wf['id'] }}">{{ $wf['name'] }}</option>
            @endforeach
        </select>
        <p class="text-[10px] text-slate-400">The current contact will be passed to this flow.</p>
    </div>

    <div x-show="['rate_limit_gate'].includes(selectedNode.type)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Wait for Window (Hours)</label>
        <input type="number" wire:model.blur="nodeHours" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        <p class="text-[10px] text-rose-500">Contact will be dropped if they pass through here more than once in this window.</p>
    </div>

    <div x-show="['split_by_condition', 'condition'].includes(selectedNode.type)" class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-[11px] text-slate-500">Each branch executes if its condition is met. Connect edges and label them matching the rule label below.</p>
        </div>
        <div class="space-y-4">
            <template x-for="(rule, idx) in (selectedNode.data.rules || [])" :key="idx">
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3 relative group">
                     <div class="flex items-center justify-between">
                         <span class="text-[10px] font-black uppercase text-slate-400" x-text="'Branch ' + (idx + 1)"></span>
                         <button type="button" @click="$wire.removeRule(idx)" class="text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                         </button>
                     </div>
                     <div class="grid grid-cols-2 gap-2">
                         <div class="col-span-2">
                              <input type="text" x-model="rule.label" @change="$wire.updateNodeData()" placeholder="Branch Label (shown on edge)" class="w-full bg-wa-teal/5 dark:bg-wa-teal/10 border-wa-teal/30 dark:border-wa-teal/20 rounded-lg text-xs font-bold text-wa-teal">
                         </div>
                         <input type="text" x-model="rule.variable" @change="$wire.updateNodeData()" placeholder="@{{variable}} or contact.field" class="col-span-2 w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs leading-none">
                         <select x-model="rule.operator" @change="$wire.updateNodeData()" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs py-1.5 px-2">
                             <option value="eq">= Equals</option>
                             <option value="neq">≠ Not Equals</option>
                             <option value="gt">&gt; Greater Than</option>
                             <option value="gte">≥ Greater or Equal</option>
                             <option value="lt">&lt; Less Than</option>
                             <option value="lte">≤ Less or Equal</option>
                             <option value="contains">Contains</option>
                             <option value="not_contains">Not Contains</option>
                             <option value="starts_with">Starts With</option>
                             <option value="ends_with">Ends With</option>
                             <option value="is_empty">Is Empty</option>
                             <option value="is_not_empty">Is Not Empty</option>
                             <option value="regex">Regex Match</option>
                         </select>
                         <input type="text" x-model="rule.value" @change="$wire.updateNodeData()" placeholder="Value" x-show="!['is_empty','is_not_empty'].includes(rule.operator)" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs py-1 px-2">
                     </div>
                </div>
            </template>
            <button type="button" @click="$wire.addRule()" class="w-full py-2 bg-wa-teal/10 text-wa-teal rounded-xl text-xs font-black uppercase tracking-widest hover:bg-wa-teal hover:text-white transition-all">+ Add Branch Rule</button>
        </div>
    </div>

    {{-- Note Node --}}
    <div x-show="selectedNode.type === 'note'" class="space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-lg">📌</span>
            <label class="block text-xs font-bold text-slate-500 uppercase">Canvas Note / Comment</label>
        </div>
        <div class="grid grid-cols-3 gap-2">
            @foreach(['yellow' => '🟡 Yellow', 'blue' => '🔵 Blue', 'green' => '🟢 Green', 'pink' => '🔴 Pink', 'gray' => '⚪ Gray'] as $c => $label)
                <button type="button" wire:click="$set('nodeAction', '{{ $c }}')" @click="$wire.updateNodeData()"
                    class="py-1.5 rounded-lg text-[10px] font-black uppercase border transition-all {{ $loop->first ? 'bg-yellow-100 border-yellow-300 text-yellow-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:border-slate-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <textarea wire:model.blur="nodeText" rows="4" placeholder="Write your note here. This is not sent to contacts."
            class="w-full bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800 rounded-xl text-sm italic resize-none focus:ring-yellow-400"></textarea>
        <p class="text-[9px] text-slate-400">Notes are for your reference only. They are not sent to contacts.</p>
    </div>

    {{-- Wait for Event Node --}}
    <div x-show="selectedNode.type === 'wait_for_event'" class="space-y-4">
        <div class="p-3 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-800 rounded-xl">
            <p class="text-xs font-bold text-cyan-700 dark:text-cyan-300">⏳ Pauses this contact's flow until a specific event is detected by the system.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Wait for Event Type</label>
            <select wire:model.live="nodeAction" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="keyword">Keyword Reply</option>
                <option value="button_click">Button Click</option>
                <option value="any_message">Any Message</option>
                <option value="payment_received">Payment Received</option>
                <option value="tag_assigned">Tag Assigned</option>
                <option value="deal_stage_changed">Deal Stage Changed</option>
                <option value="webhook">Incoming Webhook</option>
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Match Value (Optional)</label>
            <input type="text" wire:model.blur="nodeText" placeholder="e.g. 'YES', button ID, tag name..."
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Timeout (Hours)</label>
                <input type="number" wire:model.blur="nodeDelayValue" min="1" max="720" placeholder="24"
                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            </div>
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">On Timeout</label>
                <select wire:model.live="nodeDelayUnit" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                    <option value="continue">Continue Flow</option>
                    <option value="end">End Flow</option>
                    <option value="restart">Restart from Trigger</option>
                </select>
            </div>
        </div>
        <p class="text-[9px] text-slate-400">Connect 2 edges from this node: one labeled "matched" (event occurs) and one labeled "timeout" (fallback).</p>
    </div>

    {{-- Retry Node --}}
    <div x-show="selectedNode.type === 'retry'" class="space-y-4">
        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 rounded-xl">
            <p class="text-xs font-bold text-orange-700 dark:text-orange-300">🔄 Retries a failed upstream node. Connect this node after any node that can fail (API, Email, Webhook).</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Max Retries</label>
                <input type="number" wire:model.blur="nodeDelayValue" min="1" max="10" placeholder="3"
                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            </div>
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Delay Between (min)</label>
                <input type="number" wire:model.blur="nodeHours" min="1" max="1440" placeholder="5"
                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            </div>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">On Permanent Failure</label>
            <select wire:model.live="nodeAction" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="end">End Flow</option>
                <option value="handover">Hand Off to Human</option>
                <option value="notify">Notify Admin & Continue</option>
            </select>
        </div>
    </div>

    {{-- Product Catalog Node --}}
    <div x-show="selectedNode.type === 'catalog_message'" class="space-y-4">
        <div class="p-3 bg-lime-50 dark:bg-lime-900/20 border border-lime-100 dark:border-lime-800 rounded-xl">
            <div class="flex gap-2 items-center">
                <svg class="w-4 h-4 text-lime-600 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <p class="text-xs font-bold text-lime-700 dark:text-lime-300">📦 Sends a WhatsApp Product Catalog message. Requires WhatsApp Commerce to be enabled on your account.</p>
            </div>
        </div>

        {{-- Send Type --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Message Type</label>
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                <button type="button" wire:click="$set('nodeAction', 'multi_product')" @click="$wire.updateNodeData()"
                    class="py-2 text-[10px] font-bold uppercase rounded-lg transition-all"
                    :class="($wire.nodeAction || 'multi_product') === 'multi_product' ? 'bg-white dark:bg-slate-700 shadow-sm text-lime-600' : 'text-slate-500'">
                    Multi Product
                </button>
                <button type="button" wire:click="$set('nodeAction', 'single_product')" @click="$wire.updateNodeData()"
                    class="py-2 text-[10px] font-bold uppercase rounded-lg transition-all"
                    :class="($wire.nodeAction || 'multi_product') === 'single_product' ? 'bg-white dark:bg-slate-700 shadow-sm text-lime-600' : 'text-slate-500'">
                    Single Product
                </button>
            </div>
        </div>

        {{-- Catalog ID --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Catalog ID <span class="text-rose-400">*</span></label>
            <input type="text" wire:model.blur="nodeProvider"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. 1234567890987654">
            <p class="text-[9px] text-slate-400">Found in WhatsApp Business Manager → Commerce → Catalogs.</p>
        </div>

        {{-- Product Retailer IDs --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">
                Product IDs <span class="text-rose-400">*</span>
                <span class="text-slate-400 normal-case font-normal ml-1">(comma separated)</span>
            </label>
            <textarea wire:model.blur="nodeText" rows="2"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono"
                placeholder="SKU-001, SKU-002, SKU-003"></textarea>
            <p class="text-[9px] text-slate-400">Enter your product retailer IDs as shown in your catalog. Supports <code class="text-wa-teal">@{{variables}}</code>.</p>
        </div>

        {{-- Section Title (multi-product only) --}}
        <div class="space-y-1" x-show="($wire.nodeAction || 'multi_product') === 'multi_product'">
            <label class="block text-[10px] font-black uppercase text-slate-400">Section Title</label>
            <input type="text" wire:model.blur="nodeButtonText"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. Our Best Sellers">
        </div>

        {{-- Header Text --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Header Text</label>
            <input type="text" wire:model.blur="nodeLabel"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. 🛒 Today's Deals">
        </div>

        {{-- Body Text --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Body Message <span class="text-rose-400">*</span></label>
            <textarea wire:model.blur="nodeUrl" rows="3"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="Browse our products and tap one to add it to your cart. @{{name}}"></textarea>
        </div>

        {{-- Footer Text --}}
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Footer Text <span class="text-slate-400 normal-case font-normal">(optional)</span></label>
            <input type="text" wire:model.blur="nodeTag"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. Free shipping on orders above ₹499">
        </div>
    </div>

    {{-- Payment Node --}}
    <div x-show="selectedNode.type === 'payment'" class="space-y-4">
        <div class="p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-800 rounded-xl">
            <p class="text-xs font-bold text-violet-700 dark:text-violet-300">💳 Generates a payment link and sends it to the contact. Flow waits for payment confirmation via webhook.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Payment Provider</label>
            <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="razorpay">Razorpay</option>
                <option value="stripe">Stripe</option>
                <option value="payu">PayU</option>
                <option value="cashfree">Cashfree</option>
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Amount (Variable or Fixed)</label>
                <input type="text" wire:model.blur="nodeText" placeholder="e.g. @{{amount}} or 999"
                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
            </div>
            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Currency</label>
                <select wire:model.live="nodeAction" class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
                    <option value="INR">INR ₹</option>
                    <option value="USD">USD $</option>
                    <option value="EUR">EUR €</option>
                    <option value="GBP">GBP £</option>
                </select>
            </div>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Payment Description</label>
            <input type="text" wire:model.blur="nodeButtonText" placeholder="e.g. Order Payment for @{{order_id}}"
                class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Save Result To Variable</label>
            <input type="text" wire:model.blur="nodeSaveTo" placeholder="payment_status"
                class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs">
        </div>
        <p class="text-[9px] text-slate-400">Connect 2 edges: "paid" (payment success) and "failed" (payment timeout/failure).</p>
    </div>

    {{-- Update Contact Node --}}
    <div x-show="selectedNode.type === 'update_contact'" class="space-y-4">
        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 rounded-xl">
            <p class="text-xs font-bold text-orange-700 dark:text-orange-300">✏️ Updates a field on the contact. Standard fields: name, email, phone_number — anything else is saved as a custom attribute.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Field <span class="text-rose-400">*</span></label>
            <input type="text" wire:model.blur="nodeProvider" list="update-contact-fields"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="name, email, phone_number, or a custom field">
            <datalist id="update-contact-fields">
                <option value="name"></option>
                <option value="email"></option>
                <option value="phone_number"></option>
            </datalist>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Value <span class="text-rose-400">*</span></label>
            <input type="text" wire:model.blur="nodeText"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="New value or @{{variable}}">
            <p class="text-[9px] text-slate-400">Empty values are ignored to avoid wiping the field.</p>
        </div>
    </div>

    {{-- External CRM Sync Node --}}
    <div x-show="selectedNode.type === 'crm_sync'" class="space-y-4">
        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-xl">
            <p class="text-xs font-bold text-purple-700 dark:text-purple-300">🔄 Syncs the contact to a CRM. "Internal" updates this app's CRM directly; others emit a workflow event for your integration.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Provider</label>
            <select wire:model.live="nodeProvider" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="internal">Internal CRM</option>
                <option value="salesforce">Salesforce</option>
                <option value="hubspot">HubSpot</option>
                <option value="zoho">Zoho</option>
                <option value="pipedrive">Pipedrive</option>
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-[10px] font-black uppercase text-slate-400">Action</label>
            <select wire:model.live="nodeAction" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="update_lead">Update / Create Lead</option>
                <option value="create_deal">Create Deal</option>
            </select>
        </div>
    </div>

    {{-- Universal Variable Reference Chips --}}
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Available Variables</p>
        <div class="flex flex-wrap gap-1">
            @foreach(['name', 'phone_number', 'email', 'now', 'time', 'last_order_id', 'last_deal_id'] as $var)
                <button type="button" onclick="navigator.clipboard.writeText('&#123;&#123;{{ $var }}&#125;&#125;')"
                    title="Click to copy"
                    class="text-[9px] font-mono px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded hover:bg-wa-teal/10 hover:text-wa-teal transition-colors cursor-copy">
                    &#123;&#123;{{ $var }}&#125;&#125;
                </button>
            @endforeach
            @foreach($this->availableTags as $tag)
                <button type="button" onclick="navigator.clipboard.writeText('&#123;&#123;{{ data_get($tag, 'name') }}&#125;&#125;')"
                    title="Click to copy tag"
                    class="text-[9px] font-mono px-1.5 py-0.5 bg-wa-teal/5 text-wa-teal rounded hover:bg-wa-teal/20 transition-colors cursor-copy">
                    &#123;&#123;{{ data_get($tag, 'name') }}&#125;&#125;
                </button>
            @endforeach
        </div>
        <p class="text-[9px] text-slate-300 dark:text-slate-600 mt-1">Click any chip to copy. Paste into message fields.</p>
    </div>
</div>
