<div class="h-full flex flex-col bg-slate-50 dark:bg-slate-950">
    <!-- Redesigned Header: 2-Column Metadata -->
    <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-6 shrink-0 relative z-50">
        <div class="flex items-start justify-between gap-8">
            <!-- Left Side: Basic Info -->
            <div class="flex-1 grid grid-cols-2 gap-x-8 gap-y-4">
                <div class="space-y-1">
                    <label
                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-1">
                        Internal Title <span class="text-rose-500">*</span>
                        <svg class="w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </label>
                    <input type="text" wire:model="name" placeholder="E.g. Customer Feedback Survey"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 px-4 py-2.5">
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Step Unique ID <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model="screens.{{ $selectedScreenIndex }}.id"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20 px-4 py-2.5">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Step Title (Internal)
                        <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="screens.{{ $selectedScreenIndex }}.title"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 px-4 py-2.5">
                </div>
                <div class="space-y-1">
                    <label
                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-1">
                        After Submit <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="after_submit_action"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 px-4 py-2.5">
                        <option value="none">Default (Auto-Capture Only)</option>
                        <option value="webhook">Send to Webhook</option>
                        <option value="api">External API Sync</option>
                    </select>
                </div>
                <!-- Uses Data Endpoint -->
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Endpoint</label>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" wire:model="usesDataEndpoint"
                            class="rounded border-gray-300 text-wa-teal shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Use Data Endpoint</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Actions -->
            <div class="flex flex-col items-end gap-3 justify-center pt-5">
                <div class="flex items-center gap-2">
                    <button wire:click="save"
                        class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-slate-200 transition-all">
                        Save Draft
                    </button>
                    <button wire:click="deploy"
                        class="px-6 py-2.5 bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Save to Meta
                    </button>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Status: <span class="text-wa-teal">{{ $flowId ? 'Saved' : 'Local Draft' }}</span>
                </p>
            </div>
        </div>
        <p class="text-[10px] text-rose-500 font-bold mt-4">*Do not use any copy paste formatted text on label name</p>
    </div>

    @if(session()->has('success'))
        <div
            class="mx-6 mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/20 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-xs font-black uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
                {{ session('success') }}
            </p>
        </div>
    @endif
    @if(session()->has('error'))
        <div
            class="mx-6 mt-6 p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/20 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-xs font-black uppercase tracking-widest text-rose-700 dark:text-rose-400">{{ session('error') }}
            </p>
        </div>
    @endif

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar: Steps -->
        <div class="w-72 bg-white dark:bg-slate-900 border-r border-slate-100 dark:border-slate-800 flex flex-col">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 py-2">Workflow Steps</h3>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                @foreach($screens as $index => $screen)
                    <button wire:click="$set('selectedScreenIndex', {{ $index }})"
                        class="w-full p-4 rounded-2xl border transition-all text-left flex items-center justify-between group {{ $selectedScreenIndex === $index ? 'bg-indigo-50 border-indigo-200 dark:bg-indigo-900/20 dark:border-indigo-800/50' : 'bg-transparent border-transparent hover:bg-slate-50' }}">
                        <div>
                            <p
                                class="text-[9px] font-black uppercase tracking-widest {{ $selectedScreenIndex === $index ? 'text-wa-teal' : 'text-slate-400' }}">
                                {{ $screen['id'] }}
                            </p>
                            <p
                                class="text-sm font-black uppercase tracking-tight {{ $selectedScreenIndex === $index ? 'text-indigo-900 dark:text-white' : 'text-slate-600' }}">
                                {{ $screen['title'] }}
                            </p>
                        </div>
                        <button wire:click.stop="removeScreen({{ $index }})"
                            class="opacity-0 group-hover:opacity-100 text-rose-500 hover:text-rose-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </button>
                @endforeach
                <button wire:click="addScreen"
                    class="w-full p-4 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-2xl text-slate-400 hover:text-wa-teal hover:border-indigo-100 transition-all font-black uppercase tracking-widest text-[10px] flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Step
                </button>
            </div>
        </div>

        <!-- Canvas -->
        <div class="flex-1 bg-slate-50 dark:bg-slate-950 p-12 overflow-y-auto flex justify-center">
            @if(isset($screens[$selectedScreenIndex]))
                <div
                    class="w-[400px] h-[700px] bg-white dark:bg-slate-900 rounded-[3.5rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.25)] border-[12px] border-slate-950 dark:border-slate-800 overflow-hidden flex flex-col relative scale-[0.85] ring-4 ring-indigo-500/10 ring-offset-4 ring-offset-slate-50 dark:ring-offset-slate-950">
                    <div
                        class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-indigo-500/20 to-transparent">
                    </div>
                    <!-- Phone Header -->
                    <div class="bg-slate-900 p-4 flex justify-between items-center text-white/40">
                        <span class="text-xs font-bold">9:41</span>
                        <div class="flex gap-1.5">
                            <div class="w-4 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-1.5 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-1.5 h-1.5 bg-white/40 rounded-full"></div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-6">
                        <div class="border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                            <label class="text-[9px] font-black uppercase tracking-widest text-wa-teal">Header
                                Text</label>
                            <input type="text" wire:model="screens.{{ $selectedScreenIndex }}.title"
                                class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight bg-transparent border-none p-0 focus:ring-0 w-full">
                        </div>

                        @foreach($screens[$selectedScreenIndex]['components'] as $cIndex => $component)
                            <div wire:click="$set('selectedComponentIndex', {{ $cIndex }})"
                                class="relative group p-4 border-2 rounded-2xl transition-all cursor-pointer {{ $selectedComponentIndex === $cIndex ? 'border-wa-teal bg-indigo-50/10' : 'border-transparent hover:border-slate-100' }}">
                                @if(isset($component['type']) && $component['type'] === 'TextBody')
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400 leading-relaxed">
                                        {{ $component['text'] }}
                                    </p>
                                @elseif(isset($component['type']) && $component['type'] === 'TextInput')
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-12 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl">
                                        </div>
                                    </div>
                                @elseif(isset($component['type']) && $component['type'] === 'TextArea')
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-24 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl">
                                        </div>
                                    </div>
                                @elseif(isset($component['type']) && ($component['type'] === 'CheckboxGroup' || $component['type'] === 'RadioGroup'))
                                    <div class="space-y-3">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] ?? 'Label' }}</label>
                                        <div class="space-y-2">
                                            @foreach($component['options'] ?? [] as $opt)
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="w-4 h-4 border border-slate-200 dark:border-slate-700 {{ $component['type'] === 'RadioGroup' ? 'rounded-full' : 'rounded' }}">
                                                    </div>
                                                    <span
                                                        class="text-xs text-slate-600 dark:text-slate-400">{{ $opt['label'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif(isset($component['type']) && ($component['type'] === 'Select' || $component['type'] === 'Dropdown'))
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-12 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl px-4 flex items-center justify-between text-slate-400">
                                            <span class="text-xs">Select option...</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                @elseif($component['type'] === 'DateField')
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-12 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl px-4 flex items-center justify-between text-slate-400">
                                            <span class="text-xs">Pick a date</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                @elseif($component['type'] === 'PhotoPicker')
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-32 bg-slate-50 dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center text-slate-400 gap-2">
                                            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="text-[9px] font-black uppercase tracking-widest">Tap to Upload Photo</span>
                                        </div>
                                    </div>
                                @elseif($component['type'] === 'DocumentPicker')
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $component['label'] }}</label>
                                        <div
                                            class="h-12 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-xl px-4 flex items-center justify-between text-slate-400">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                                <span class="text-xs">Attach File</span>
                                            </div>
                                            <span
                                                class="text-[9px] font-bold bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded text-slate-500">25MB
                                                MAX</span>
                                        </div>
                                    </div>
                                @elseif($component['type'] === 'Image')
                                    <div
                                        class="rounded-xl overflow-hidden border border-slate-100 dark:border-slate-800 bg-slate-100 dark:bg-slate-800">
                                        <img src="{{ $component['src'] }}" class="w-full object-cover"
                                            style="height: {{ $component['height'] }}px;" alt="Flow Image">
                                    </div>
                                @elseif($component['type'] === 'Footer')
                                    <div class="pt-8 mt-auto">
                                        <button
                                            class="w-full py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl">
                                            {{ $component['label'] }}
                                        </button>
                                    </div>
                                @endif

                                <button wire:click.stop="removeComponent({{ $cIndex }})"
                                    class="absolute -top-3 -right-3 p-1.5 bg-rose-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar: Properties -->
        <div class="w-80 bg-white dark:bg-slate-900 border-l border-slate-100 dark:border-slate-800 flex flex-col">
            <div class="p-6 border-b border-slate-50 dark:border-slate-800">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 py-2">Property Editor</h3>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                @if($selectedComponentIndex !== null)
                    @php $comp = $screens[$selectedScreenIndex]['components'][$selectedComponentIndex]; @endphp
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-wa-teal mb-4">Editing
                            {{ $comp['type'] }}
                        </p>

                        @if($comp['type'] === 'TextBody')
                            <div class="space-y-4">
                                <label
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-400 block">Content</label>
                                <textarea
                                    wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.text"
                                    class="w-full h-32 p-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-indigo-500/20"></textarea>
                            </div>
                        @elseif($comp['type'] === 'TextInput' || $comp['type'] === 'TextArea')
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Label</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.label"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Field
                                        Unique ID</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.name"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Required
                                        Field</span>
                                    <input type="checkbox"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.required"
                                        class="w-5 h-5 rounded border-slate-200 text-wa-teal focus:ring-indigo-500/20">
                                </div>
                            </div>
                        @elseif(in_array($comp['type'], ['CheckboxGroup', 'RadioGroup', 'Select', 'Dropdown']))
                            <div class="space-y-6">
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Label</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.label"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">Options</label>
                                        <button
                                            wire:click="addOption({{ $selectedScreenIndex }}, {{ $selectedComponentIndex }})"
                                            class="text-[9px] font-black uppercase tracking-widest text-wa-teal hover:underline">+
                                            Add Option</button>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($comp['options'] ?? [] as $oIndex => $option)
                                            <div class="flex items-center gap-2">
                                                <input type="text"
                                                    wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.options.{{ $oIndex }}.label"
                                                    class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-xs font-bold"
                                                    placeholder="Label">
                                                <input type="text"
                                                    wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.options.{{ $oIndex }}.value"
                                                    class="w-16 px-3 py-2 bg-slate-100 dark:bg-slate-700 border-none rounded-lg text-[9px] font-mono text-center"
                                                    placeholder="Val">
                                                <button
                                                    wire:click="removeOption({{ $selectedScreenIndex }}, {{ $selectedComponentIndex }}, {{ $oIndex }})"
                                                    class="text-rose-500 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg></button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif($comp['type'] === 'DateField')
                            <div class="space-y-4">
                                <label
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Label</label>
                                <input type="text"
                                    wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.label"
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        @elseif($comp['type'] === 'PhotoPicker' || $comp['type'] === 'DocumentPicker')
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Label</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.label"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Field
                                        ID</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.name"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Required</span>
                                    <input type="checkbox"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.required"
                                        class="w-5 h-5 rounded border-slate-200 text-wa-teal focus:ring-indigo-500/20">
                                </div>
                            </div>
                        @elseif($comp['type'] === 'Image')
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Image
                                        URL (HTTPS)</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.src"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="https://...">
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Height
                                        (px)</label>
                                    <input type="number"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.height"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                            </div>
                        @elseif($comp['type'] === 'Footer')
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Button
                                        Label</label>
                                    <input type="text"
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.label"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">On
                                        Click Action</label>
                                    <select
                                        wire:model.live="screens.{{ $selectedScreenIndex }}.components.{{ $selectedComponentIndex }}.on_click_action"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                        <option value="next">Go to Next Step</option>
                                        <option value="complete">Finish & Submit</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-20">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Select an element<br>to edit
                            properties</p>
                    </div>
                @endif

                <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Add Elements</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button wire:click="addComponent('TextBody')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Header</span>
                        </button>
                        <button wire:click="addComponent('TextInput')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Text
                                Field</span>
                        </button>
                        <button wire:click="addComponent('TextArea')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Text
                                Area</span>
                        </button>
                        <button wire:click="addComponent('CheckboxGroup')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Checkbox</span>
                        </button>
                        <button wire:click="addComponent('RadioGroup')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Radio
                                Group</span>
                        </button>
                        <button wire:click="addComponent('Select')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Select</span>
                        </button>
                        <button wire:click="addComponent('DateField')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Date</span>
                        </button>
                        <button wire:click="addComponent('PhotoPicker')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Photo</span>
                        </button>
                        <button wire:click="addComponent('DocumentPicker')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">File</span>
                        </button>
                        <button wire:click="addComponent('Image')"
                            class="p-4 bg-slate-50 hover:bg-slate-100 rounded-2xl flex flex-col items-center gap-2 transition-all group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Image</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>