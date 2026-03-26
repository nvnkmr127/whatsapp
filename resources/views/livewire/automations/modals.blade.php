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
                    <span class="text-[10px] font-black uppercase tracking-widest block mb-1 {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) > 0 ? 'text-rose-400' : 'text-emerald-400' }}">Errors</span>
                    <span class="text-2xl font-black {{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ count(array_filter($validationIssues, fn($i) => $i['level'] === 'error')) }}</span>
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
                            <div class="flex items-start gap-4 p-4 rounded-2xl border transition-all {{ $risk['level'] === 'high' ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/30' : 'bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/30' }}">
                                <div class="mt-0.5 p-2 rounded-xl {{ $risk['level'] === 'high' ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $risk['icon'] }}" /></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold">{{ $risk['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                <label class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Notes</label>
                <textarea wire:model.blur="publishNote" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm" placeholder="What changed?"></textarea>
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
                        <input type="text" wire:model.live.debounce.300ms="testContactSearch"
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
    <div class="relative w-full bg-white dark:bg-slate-950 flex flex-col max-h-[95vh] rounded-[2.5rem] overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800">
        <!-- Modal Header -->
        <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-900 flex justify-between items-center bg-white dark:bg-slate-950 z-20 shrink-0">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Flow <span class="text-wa-teal">Templates</span></h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Start from a professionally pre-built flow ({{ count(\App\Services\Automations\TemplateLibrary::getAll()) }} Available)</p>
                </div>
            </div>
            <button @click="showTemplatesModal = false" class="text-slate-400 hover:text-rose-500 p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 p-8 overflow-y-auto custom-scrollbar bg-white dark:bg-slate-950">
            <!-- Search and Filters Section -->
            <div class="flex flex-col md:flex-row gap-4 mb-8 bg-slate-50 dark:bg-slate-800/20 p-5 rounded-3xl border border-slate-100 dark:border-slate-800/50 shadow-sm">
                <div class="flex-1 relative group">
                    <input type="text" wire:model.live.debounce.300ms="templateSearch" 
                           class="w-full bg-white dark:bg-slate-900 border-none rounded-2xl text-sm pl-12 pr-4 py-4 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium placeholder:text-slate-300" 
                           placeholder="Search templates (e.g. Sales, Recovery, CRM)...">
                    <svg class="w-5 h-5 absolute left-4 top-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <div class="flex gap-4">
                    <div class="relative min-w-[160px]">
                        <select wire:model.live="selectedIndustry" class="w-full bg-white dark:bg-slate-900 border-none rounded-2xl text-xs font-black uppercase tracking-widest px-5 py-4 focus:ring-2 focus:ring-wa-teal/20 cursor-pointer shadow-sm">
                            <option value="All">All Industries</option>
                            @foreach(\App\Services\Automations\TemplateLibrary::getIndustries() as $industry)
                                <option value="{{ $industry }}">{{ $industry }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative min-w-[160px]">
                        <select wire:model.live="selectedUseCase" class="w-full bg-white dark:bg-slate-900 border-none rounded-2xl text-xs font-black uppercase tracking-widest px-5 py-4 focus:ring-2 focus:ring-wa-teal/20 cursor-pointer shadow-sm">
                            <option value="All">All Use Cases</option>
                            @foreach(\App\Services\Automations\TemplateLibrary::getUseCases() as $useCase)
                                <option value="{{ $useCase }}">{{ $useCase }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $industryColors = [
                        'Ecommerce'   => 'bg-lime-50 text-lime-600 border-lime-100 dark:bg-lime-900/10 dark:text-lime-400 dark:border-lime-900/30',
                        'Real Estate'  => 'bg-orange-50 text-orange-600 border-orange-100 dark:bg-orange-900/10 dark:text-orange-400 dark:border-orange-900/30',
                        'Healthcare'   => 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-900/10 dark:text-rose-400 dark:border-rose-900/30',
                        'Education'    => 'bg-indigo-50 text-indigo-600 border-indigo-100 dark:bg-indigo-900/10 dark:text-indigo-400 dark:border-indigo-900/30',
                        'Hospitality'  => 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/10 dark:text-amber-400 dark:border-amber-900/30',
                        'Professional' => 'bg-sky-50 text-sky-600 border-sky-100 dark:bg-sky-900/10 dark:text-sky-400 dark:border-sky-900/30',
                    ];
                @endphp
                @forelse($this->filteredTemplates as $key => $tpl)
                    <div class="group p-6 rounded-[2rem] border-2 border-slate-50 dark:border-slate-800/60 hover:border-wa-teal/40 hover:bg-wa-teal/[0.02] hover:shadow-2xl hover:shadow-wa-teal/5 transition-all cursor-pointer bg-white dark:bg-slate-900 flex flex-col relative overflow-hidden"
                         wire:click="loadTemplate('{{ $key }}')">
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-4xl bg-slate-50 dark:bg-slate-800/50 w-16 h-16 flex items-center justify-center rounded-2xl group-hover:scale-110 group-hover:bg-white dark:group-hover:bg-slate-800 transition-all duration-300 shadow-sm border border-slate-100 dark:border-slate-800">
                                {{ $tpl['icon'] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-black text-slate-800 dark:text-white text-sm group-hover:text-wa-teal transition-colors tracking-tight leading-tight line-clamp-1 truncate">{{ $tpl['name'] }}</h3>
                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                    @if(isset($tpl['industry']))
                                        <span class="text-[8px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border {{ $industryColors[$tpl['industry']] ?? 'bg-slate-50 text-slate-500 border-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700' }}">
                                            {{ $tpl['industry'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 leading-relaxed font-medium mb-6 line-clamp-3 min-h-[48px]">{{ $tpl['desc'] }}</p>
                        
                        <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50 dark:border-slate-800/50">
                            <div class="flex items-center gap-1.5">
                                 <div class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></div>
                                 <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-wa-teal transition-colors">{{ count($tpl['nodes']) }} Nodes</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center group-hover:bg-wa-teal group-hover:text-white transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Hover Background Ornament -->
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-wa-teal opacity-[0.03] rounded-full scale-0 group-hover:scale-150 transition-transform duration-500 pointer-events-none"></div>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center bg-slate-50 dark:bg-slate-900/50 rounded-[2.5rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
                        <div class="text-5xl mb-6">🔍</div>
                        <h4 class="text-xl font-black text-slate-400 uppercase tracking-tight">No Flow Found</h4>
                        <p class="text-sm font-medium text-slate-400 mt-2">Try adjusting your filters or search query.</p>
                        <button wire:click="$set('templateSearch', '')" class="text-wa-teal font-black text-xs uppercase tracking-widest mt-6 hover:underline">Clear Search</button>
                    </div>
                @endforelse
            </div>
            
            <p class="text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-12 mb-4">⚠️ Loading a template replaces your current unsaved flow · Back up if needed</p>
        </div>

        <!-- Sticky Footer -->
        <div class="p-8 border-t border-slate-50 dark:border-slate-900 bg-white dark:bg-slate-950 flex flex-col sm:flex-row justify-between items-center z-10 shrink-0">
             <div class="flex items-center gap-4 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                <span class="flex items-center gap-1.5"><div class="w-1.5 h-1.5 bg-wa-teal rounded-full shadow-lg shadow-wa-teal/50"></div> Global Content</span>
                <span class="flex items-center gap-1.5 opacity-50"><div class="w-1.5 h-1.5 bg-slate-400 rounded-full"></div> Fully Custom</span>
            </div>
            <button @click="showTemplatesModal = false" class="px-10 py-4 bg-slate-50 dark:bg-slate-900 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-100 transition-all border border-slate-100 dark:border-slate-800">
                Cancel
            </button>
        </div>
    </div>
</x-app-modal>
