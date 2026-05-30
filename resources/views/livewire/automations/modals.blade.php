{{-- Publish Review Modal --}}
@if(isset($showPublishModal))
<x-dialog-modal wire:model.live="showPublishModal" maxWidth="2xl">
    <x-slot name="title">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">Turn On Review</h2>
                @php $v = $version ?? 0; @endphp
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Moving to Version v{{ $v + 1 }}</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6 py-2">
            <div class="grid grid-cols-4 gap-3">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nodes</span>
                    <span class="text-2xl font-black text-slate-700 dark:text-white">{{ count($nodes) }}</span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Trigger</span>
                    <span class="text-sm font-black text-wa-teal">{{ ucfirst(str_replace('_', ' ', $triggerType)) }}</span>
                </div>
                <div class="p-4 rounded-2xl border {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) > 0 ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/30' : 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-800/30' }}">
                    <span class="text-[10px] font-black uppercase tracking-widest block mb-1 {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) > 0 ? 'text-rose-400' : 'text-emerald-400' }}">Validation Status</span>
                    @if(count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) > 0)
                        <span class="text-lg font-black text-rose-600">{{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) }} Errors</span>
                    @else
                        <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 mt-1">
                            <svg class="w-5 h-5 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-xs font-black uppercase tracking-tight">Ready</span>
                        </div>
                    @endif
                </div>
                <div class="p-4 rounded-2xl border {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'warning')) > 0 ? 'bg-amber-50 border-amber-100 dark:bg-amber-900/10' : 'bg-slate-50 border-slate-100 dark:bg-slate-800/30' }}">
                    <span class="text-[10px] font-black uppercase tracking-widest block mb-1 {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'warning')) > 0 ? 'text-amber-400' : 'text-slate-400' }}">Warnings</span>
                    <span class="text-2xl font-black {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'warning')) > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'warning')) }}</span>
                </div>
            </div>

            @if(count($this->risks) > 0)
                <div class="space-y-3">
                    <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Risk Assessment</h4>
                    <div class="space-y-2">
                        @foreach($this->risks as $risk)
                            <div class="flex items-start gap-4 p-4 rounded-2xl border transition-all 
                                {{ $risk['level'] === 'high' ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/30' : 
                                  ($risk['level'] === 'medium' ? 'bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/30' : 
                                                                 'bg-slate-50 border-slate-100 dark:bg-slate-800/20 dark:border-slate-800') }}">
                                <div class="mt-0.5 p-2 rounded-xl 
                                    {{ $risk['level'] === 'high' ? 'bg-rose-500 text-white' : 
                                      ($risk['level'] === 'medium' ? 'bg-amber-500 text-white' : 
                                                                     'bg-slate-500 text-white') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $risk['icon'] }}" /></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $risk['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                <label class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Notes</label>
                <textarea wire:model.blur="publishNote" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm @error('publishNote') border-rose-500 ring-rose-500/20 @enderror" placeholder="What changed?"></textarea>
                @error('publishNote')
                    <p class="text-xs text-rose-500 font-bold px-1 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="flex items-center justify-between w-full">
            <button @click="showPublishModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500">Back</button>
            <button wire:click="confirmPublish" class="px-8 py-2.5 bg-wa-teal text-white rounded-xl font-black text-sm">Turn On Now</button>
        </div>
    </x-slot>
</x-dialog-modal>
@endif

{{-- Save Error Modal --}}
<x-dialog-modal wire:model.live="showErrorModal">
    <x-slot name="title">{{ __('Couldn\'t Save') }}</x-slot>
    <x-slot name="content">
        <ul class="list-disc list-inside mt-2 text-red-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-slot>
    <x-slot name="footer">
        <button wire:click="$set('showErrorModal', false)" class="px-4 py-2 bg-slate-200 rounded-lg text-sm">Close</button>
    </x-slot>
</x-dialog-modal>

{{-- Test Run / Preview Modal --}}{{-- Test Modal --}}
@if(isset($showTestModal))
<x-dialog-modal wire:model.live="showTestModal" maxWidth="3xl">
    <x-slot name="title">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">Dry-Run Preview</h2>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Simulate without sending real messages</p>
            </div>
        </div>
    </x-slot>
    <x-slot name="content">
        <div class="space-y-5">
            @if(empty($testResult))
                <div class="space-y-3">
                    <label class="block text-xs font-black uppercase text-slate-500 tracking-widest">Search Contact to Simulate</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.500ms="testContactSearch"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm pl-10"
                            placeholder="Type name or phone...">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    @if(!empty($testContacts))
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-lg">
                            @foreach($testContacts as $tc)
                                <button wire:click="selectTestContact({{ $tc['id'] }})"
                                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 text-left transition-colors border-b border-slate-100 dark:border-slate-700 last:border-0">
                                    <div class="w-8 h-8 rounded-full bg-wa-teal/10 flex items-center justify-center text-wa-teal font-black text-sm">
                                        {{ strtoupper(substr($tc['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $tc['name'] }}</p>
                                        <p class="text-[11px] text-slate-400 font-mono">{{ $tc['phone_number'] }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if($testContactId)
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-xl flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ $testContactSearch }}</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest">Execution Preview for {{ $testResult['contact_name'] }}</h4>
                        <button wire:click="$set('testResult', [])" class="text-[10px] font-black uppercase text-slate-400 hover:text-slate-600 transition-colors">← Re-run</button>
                    </div>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                    @foreach($testResult['steps'] as $i => $step)
                        <div class="flex items-start gap-4 p-4 rounded-2xl border
                            {{ $step['status'] === 'terminal' ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/30' :
                               ($step['status'] === 'wait'     ? 'bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/30' :
                               ($step['status'] === 'skipped'  ? 'bg-slate-50 border-slate-100 dark:bg-slate-800/30' :
                                                                 'bg-white border-slate-100 dark:bg-slate-900 dark:border-slate-800')) }}">
                            <div class="flex-none w-7 h-7 rounded-xl flex items-center justify-center text-[11px] font-black {{ $step['color'] }}">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $step['type'] }}</span>
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ $step['label'] }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $step['message'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(!empty($testResult['variables']))
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-3">Final Variable State</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(array_slice($testResult['variables'], 0, 8, true) as $k => $v)
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-mono font-bold text-wa-teal">&#123;&#123;{{ $k }}&#125;&#125;</span>
                                    <span class="text-[10px] text-slate-500 truncate">= "{{ $v }}"</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </x-slot>
    <x-slot name="footer">
        <div class="flex items-center justify-between w-full">
            <button @click="showTestModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500">Close</button>
            @if(empty($testResult))
                <button wire:click="runTest" wire:loading.attr="disabled"
                    class="px-8 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-black text-sm transition-colors flex items-center gap-2">
                    <svg wire:loading wire:target="runTest" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span wire:loading.remove wire:target="runTest">▶ Preview Flow</span>
                    <span wire:loading wire:target="runTest">Simulating...</span>
                </button>
            @endif
        </div>
    </x-slot>
</x-dialog-modal>
@endif

{{-- Templates Library Modal --}}
<x-app-modal wire:model="showTemplatesModal" maxWidth="5xl" :closeable="false">
    <livewire:automations.flow-templates />
</x-app-modal>

{{-- Version History Details Modal --}}
@if($selectedLogVersion)
<x-dialog-modal wire:model.live="selectedLogVersion" maxWidth="2xl">
    <x-slot name="title">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">Version v{{ $selectedLogVersion['version'] }} Details</h2>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Published by {{ $selectedLogVersion['published_by'] }} · {{ \Carbon\Carbon::parse($selectedLogVersion['published_at'])->diffForHumans() }}</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6 py-2">
            <!-- Notes -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Publish Notes</span>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-relaxed italic">
                    "{{ $selectedLogVersion['note'] ?: 'No description provided.' }}"
                </p>
            </div>

            <!-- Configuration Summary -->
            <div class="space-y-3">
                <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Flow Details</h4>
                @if(isset($selectedLogVersion['nodes']) && is_array($selectedLogVersion['nodes']))
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Total Steps</span>
                            <span class="text-lg font-black text-slate-700 dark:text-white">{{ count($selectedLogVersion['nodes']) }} Nodes</span>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Total Paths</span>
                            <span class="text-lg font-black text-slate-700 dark:text-white">{{ count($selectedLogVersion['edges'] ?? []) }} Edges</span>
                        </div>
                    </div>

                    <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block px-1">Steps List</span>
                        @foreach($selectedLogVersion['nodes'] as $node)
                            <div class="flex items-center justify-between p-2.5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-500">{{ $node['type'] ?? 'step' }}</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ data_get($node, 'data.label') ?: (data_get($node, 'data.text') ?: 'Unnamed Step') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 bg-amber-50 border border-amber-100 dark:bg-amber-950/10 dark:border-amber-900/30 rounded-xl text-center">
                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400">Flow structure details are not available for this version.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="flex items-center justify-between w-full">
            <button wire:click="$set('selectedLogVersion', null)" class="px-6 py-2.5 text-sm font-bold text-slate-500">Close</button>
            @if(isset($selectedLogVersion['nodes']))
                <button wire:click="rollbackToVersion({{ $selectedLogVersion['version'] }})" 
                    class="px-6 py-2.5 bg-wa-teal hover:bg-wa-dark text-white rounded-xl font-black text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Restore This Version
                </button>
            @endif
        </div>
    </x-slot>
</x-dialog-modal>
@endif
