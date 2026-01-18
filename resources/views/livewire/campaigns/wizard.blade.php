<div class="space-y-8 animate-in fade-in duration-700 max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-wa-teal/10 dark:bg-wa-teal/20 rounded-2xl text-wa-teal">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Campaign <span class="text-wa-teal">Creator</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium tracking-tight">
                    Step {{ $step }} of 4: {{ $this->steps[$step] ?? 'Unknown' }}
                </p>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-3">
            @for ($i = 1; $i <= 4; $i++)
                <div class="flex items-center">
                    <div
                        class="w-10 h-10 rounded-2xl flex items-center justify-center text-xs font-black transition-all duration-500 {{ $step >= $i ? 'bg-wa-teal text-white shadow-xl shadow-wa-teal/20 scale-110' : 'bg-white dark:bg-slate-800 text-slate-400 border border-slate-100 dark:border-slate-700' }}">
                        {{ $i }}
                    </div>
                    @if ($i < 4)
                        <div
                            class="w-6 h-1 mx-1 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <div class="h-full bg-wa-teal transition-all duration-700"
                                style="width: {{ $step > $i ? '100%' : '0%' }}"></div>
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    {{-- Main Content Card --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 dark:shadow-none border border-slate-50 dark:border-slate-800 overflow-hidden min-h-[600px] flex flex-col">

        {{-- Step 1: Configuration --}}
        @if ($step === 1)
            <div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
                <div class="max-w-2xl">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
                        Campaign <span class="text-wa-teal">Setup</span></h3>
                    <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Give your campaign a name
                        and decide when it hits the network.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 block">Campaign
                                Name</label>
                            <input type="text" wire:model="name"
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-lg font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal transition-all"
                                placeholder="Summer Promo 2024">
                            <x-input-error for="name" class="mt-2" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 block">Schedule Mode</label>
                            <div class="grid grid-cols-1 gap-4">
                                {{-- Now --}}
                                <div wire:click="$set('scheduleMode', 'now')"
                                    class="group cursor-pointer relative rounded-3xl p-6 border-2 transition-all duration-300 {{ $scheduleMode === 'now' ? 'border-wa-teal bg-wa-teal/5' : 'border-slate-100 dark:border-slate-800 hover:border-wa-teal/30' }}">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-wa-teal flex items-center justify-center text-white shadow-lg shadow-wa-teal/20">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                Send Immediately</h4>
                                            <p class="text-xs text-slate-500 font-medium">Broadcast to all recipients immediately.</p>
                                        </div>
                                    </div>
                                    @if ($scheduleMode === 'now')
                                        <div class="absolute top-6 right-6 text-wa-teal">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Later --}}
                                <div wire:click="$set('scheduleMode', 'later')"
                                    class="group cursor-pointer relative rounded-3xl p-6 border-2 transition-all duration-300 {{ $scheduleMode === 'later' ? 'border-wa-teal bg-wa-teal/5' : 'border-slate-100 dark:border-slate-800 hover:border-wa-teal/30' }}">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-wa-teal flex items-center justify-center text-white shadow-lg shadow-wa-teal/20">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                Schedule for Later</h4>
                                            <p class="text-xs text-slate-500 font-medium">Pick a future date and time.</p>
                                        </div>
                                    </div>
                                    @if ($scheduleMode === 'later')
                                        <div class="absolute top-6 right-6 text-wa-teal">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($scheduleMode === 'later')
                            <div class="animate-in fade-in slide-in-from-top-2 duration-300">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 block">Dispatch Time</label>
                                <input type="datetime-local" wire:model="scheduled_at"
                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-lg font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal transition-all">
                                <x-input-error for="scheduled_at" class="mt-2" />
                            </div>
                        @endif
                    </div>

                    <div class="hidden lg:flex items-center justify-center">
                        <div class="relative">
                            <div class="absolute -inset-4 bg-wa-teal/10 rounded-full blur-3xl animate-pulse"></div>
                            <svg class="w-64 h-64 text-wa-teal/20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 1L9 9L1 12L9 15L12 23L15 15L23 12L15 9L12 1Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-end">
                    <button wire:click="$set('step', 2)"
                        class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        Next: Select Audience
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 2: Audience --}}
        @if ($step === 2)
            <div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
                <div class="max-w-2xl">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
                        Select <span class="text-wa-teal">Audience</span></h3>
                    <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Who are we reaching out to today? Filter by tags or select specific contacts.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    <div class="lg:col-span-2 space-y-8">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 block">Selection Mode</label>
                            <div class="flex flex-wrap gap-4">
                                <button wire:click="$set('audienceType', 'tags')" 
                                    class="px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] border-2 transition-all {{ $audienceType === 'tags' ? 'bg-wa-teal text-white border-wa-teal' : 'bg-transparent text-slate-500 border-slate-100 dark:border-slate-800' }}">
                                    Filter by Tags
                                </button>
                                <button wire:click="$set('audienceType', 'contacts')" 
                                    class="px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] border-2 transition-all {{ $audienceType === 'contacts' ? 'bg-wa-teal text-white border-wa-teal' : 'bg-transparent text-slate-500 border-slate-100 dark:border-slate-800' }}">
                                    Manual Selection
                                </button>
                                <button wire:click="$set('audienceType', 'all')" 
                                    class="px-6 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] border-2 transition-all {{ $audienceType === 'all' ? 'bg-wa-teal text-white border-wa-teal' : 'bg-transparent text-slate-500 border-slate-100 dark:border-slate-800' }}">
                                    Entire Database
                                </button>
                            </div>
                        </div>

                        @if($audienceType === 'tags')
                            <div class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Select Tags</label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach($this->tags as $tag)
                                        <label class="cursor-pointer group">
                                            <input type="checkbox" wire:model.live="selectedTags" value="{{ $tag->id }}" class="hidden">
                                            <div class="px-4 py-3 rounded-2xl border-2 text-xs font-bold transition-all {{ in_array($tag->id, $selectedTags) ? 'border-wa-teal bg-blue-50 dark:bg-blue-900/10 text-blue-600' : 'border-slate-50 dark:border-slate-800 text-slate-500 hover:border-slate-200' }}">
                                                {{ $tag->name }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error for="selectedTags" class="mt-2" />
                            </div>
                        @endif

                        @if($audienceType === 'contacts')
                            <div class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Select Contacts</label>
                                <div class="max-h-[300px] overflow-y-auto custom-scrollbar border border-slate-100 dark:border-slate-800 rounded-[2rem] p-4">
                                    <div class="space-y-2">
                                        @foreach($this->contacts as $contact)
                                            <label class="flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-active-dark transition-all cursor-pointer group">
                                                <input type="checkbox" wire:model.live="selectedContacts" value="{{ $contact->id }}" class="w-5 h-5 rounded-lg border-slate-200 text-wa-teal focus:ring-wa-teal/20">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $contact->name ?: 'Unnamed Contact' }}</p>
                                                    <p class="text-[10px] font-medium text-slate-500">{{ $contact->phone_number }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($audienceType === 'all')
                            <div class="p-8 bg-blue-50 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100 dark:border-blue-800/50">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-wa-teal flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-blue-900 dark:text-blue-300">Global Broadcast</h4>
                                        <p class="text-sm text-blue-700/70 dark:text-blue-400 font-medium">This campaign will target every contact in your database with an active opt-in.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6">
                        <div class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 sticky top-8">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Audience Size</h4>
                            
                            <div class="space-y-6">
                                <div>
                                    <p class="text-sm font-bold text-slate-500 mb-1 leading-none uppercase tracking-[0.1em]">Total Audience</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-5xl font-black text-slate-900 dark:text-white tabular-nums">{{ $audienceCount }}</span>
                                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Contacts</span>
                                    </div>
                                </div>

                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1 rounded-full overflow-hidden">
                                    <div class="h-full bg-wa-teal transition-all duration-1000" style="width: {{ min(100, $audienceCount > 0 ? ($audienceCount / 1000) * 100 : 0) }}%"></div>
                                </div>

                                <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase">
                                    Audience size is live. This reflects the exact number of messages that will be dispatched.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
                    <button wire:click="$set('step', 1)" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Back to Config</button>
                    <button wire:click="$set('step', 3)"
                        class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        Next: Configure Message
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 3: Message & Preview --}}
        @if ($step === 3)
            <div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
                <div class="max-w-2xl">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
                        Campaign <span class="text-orange-500">Message</span></h3>
                    <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Select a template and configure the message content.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    {{-- Form Side --}}
                    <div class="space-y-8">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 block">Select Template</label>
                            <select wire:model.live="selectedTemplateId" 
                                class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer">
                                <option value="">-- Choose Approved Template --</option>
                                @foreach($this->templates as $t)
                                    <option value="{{ $t->id }}">{{ str_replace('_', ' ', $t->name) }} ({{ strtoupper($t->language) }})</option>
                                @endforeach
                            </select>
                        </div>

                        @if($this->templateInfo)
                            @php
                                $info = $this->templateInfo;
                            @endphp

                            {{-- Media & Header --}}
                            @if(in_array($info['headerType'], ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                <div class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Media Header ({{ $info['headerType'] }})</label>
                                    
                                    <div class="grid grid-cols-1 gap-4">
                                        <div class="relative group">
                                            <input type="file" wire:model="headerMediaFile" class="absolute inset-0 opacity-0 cursor-pointer z-10" id="media-upload">
                                            <div class="bg-slate-50 dark:bg-slate-800/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[2rem] p-8 flex flex-col items-center justify-center text-center group-hover:border-orange-500/50 transition-all">
                                                <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center mb-4">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">Upload {{ strtolower($info['headerType']) }}</p>
                                                <p class="text-[10px] font-medium text-slate-500">Drag and drop or click to browse</p>
                                                
                                                @if($headerMediaFile)
                                                    <div class="mt-4 px-4 py-2 bg-wa-teal/10 text-wa-teal text-[10px] font-black rounded-lg">File Ready: {{ $headerMediaFile->getClientOriginalName() }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <span class="text-[10px] font-black text-slate-300 uppercase">Or</span>
                                        </div>

                                        <input type="url" wire:model.live="headerMediaUrl" 
                                            class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-xs font-bold text-slate-900 dark:text-white"
                                            placeholder="https://example.com/media.jpg">
                                    </div>
                                </div>
                            @endif

                            {{-- Variables --}}
                            @if($info['paramCount'] > 0)
                                <div class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Variables Mapping</label>
                                    <div class="space-y-3">
                                        @for($i = 1; $i <= $info['paramCount']; $i++)
                                            <div class="relative group">
                                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase group-focus-within:text-orange-500 transition-colors">Var {{ $i }}</span>
                                                <input type="text" wire:model.live="templateVars.{{ $i-1 }}" 
                                                    class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl pl-28 pr-6 py-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 transition-all"
                                                    placeholder="Value for {{ '{'.'{'.$i.'}'.'}' }}">
                                            </div>
                                        @endfor
                                    </div>
                                    <p class="text-[10px] font-bold text-orange-500 uppercase flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Tip: Variables will be applied to the Body component.
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Preview Side --}}
                    <div class="flex flex-col items-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 block w-full text-center">Live WhatsApp Preview</label>
                        
                        <div class="w-full max-w-[320px] bg-[#E5DDD5] dark:bg-slate-950 rounded-[3rem] p-4 shadow-2xl relative border-[8px] border-slate-900">
                             {{-- Phone Notch --}}
                             <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-900 rounded-b-3xl z-20"></div>

                             <div class="relative z-10 space-y-2 mt-4 min-h-[400px]">
                                 @if($this->templateInfo)
                                     <div class="bg-white dark:bg-slate-900 rounded-xl rounded-tl-none shadow-sm p-3 max-w-[90%] animate-in zoom-in-95 duration-300">
                                         {{-- Preview Header --}}
                                         @if(in_array($info['headerType'], ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                             <div class="bg-slate-100 dark:bg-slate-800 rounded-lg aspect-video mb-3 flex items-center justify-center overflow-hidden">
                                                 @if($headerMediaFile)
                                                     <img src="{{ $headerMediaFile->temporaryUrl() }}" class="w-full h-full object-cover">
                                                 @elseif($headerMediaUrl)
                                                     <img src="{{ $headerMediaUrl }}" class="w-full h-full object-cover">
                                                 @else
                                                     <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                 @endif
                                             </div>
                                         @elseif($info['headerType'] === 'TEXT')
                                             <p class="text-xs font-black text-slate-900 dark:text-white mb-2">{{ $info['headerText'] }}</p>
                                         @endif

                                         {{-- Preview Body --}}
                                         <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                                             @php
                                                 $previewText = $info['bodyText'];
                                                 foreach($templateVars as $key => $val) {
                                                     $previewText = str_replace('{{'.($key+1).'}}', '<span class="text-wa-teal font-black">'.($val ?: '...').'</span>', $previewText);
                                                 }
                                             @endphp
                                             {!! nl2br($previewText) !!}
                                         </p>

                                         {{-- Preview Footer --}}
                                         @if($info['footerText'])
                                             <p class="text-[10px] text-slate-400 mt-2">{{ $info['footerText'] }}</p>
                                         @endif
                                     </div>
                                 @else
                                     <div class="h-full flex flex-col items-center justify-center text-center p-8 mt-20">
                                         <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-300 mb-4">
                                             <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                         </div>
                                         <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Select a template to preview</p>
                                     </div>
                                 @endif
                             </div>
                        </div>
                    </div>
                </div>

                <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
                    <button wire:click="$set('step', 2)" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Back to Audience</button>
                    <button wire:click="$set('step', 4)"
                        class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        Final Review
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 4: Final Review --}}
        @if ($step === 4)
            <div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
                <div class="max-w-2xl">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
                        Final <span class="text-purple-600">Review</span></h3>
                    <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Everything looks ready. Perform a final check before we launch the campaign.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Detail Cards --}}
                    <div class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Campaign Name</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white uppercase truncate">{{ $name }}</p>
                    </div>

                    <div class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Total Audience</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $audienceCount }}</span>
                            <span class="text-[10px] font-black text-slate-400 uppercase">Contacts</span>
                        </div>
                    </div>

                    <div class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Selected Template</p>
                        <div class="flex items-center gap-2">
                             <div class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></div>
                             <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ $this->templates->find($selectedTemplateId)->name ?? 'None' }}</span>
                        </div>
                    </div>

                    <div class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Schedule</p>
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ $scheduleMode === 'now' ? 'Immediately' : date('M d, H:i', strtotime($scheduled_at)) }}</span>
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/10 rounded-[2.5rem] p-10 border border-purple-100 dark:border-purple-800/50 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 rounded-3xl bg-purple-500 flex items-center justify-center text-white shadow-xl shadow-purple-500/20">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-black text-purple-900 dark:text-purple-300 uppercase tracking-tight">Campaign Ready</h4>
                            <p class="text-sm text-purple-700/70 dark:text-purple-400 font-medium italic">Ready to send to {{ $audienceCount }} contacts.</p>
                        </div>
                    </div>

                    <button wire:click="launch" wire:loading.attr="disabled"
                        class="px-12 py-5 bg-purple-600 text-white font-black uppercase tracking-[0.2em] text-sm rounded-2xl shadow-2xl shadow-purple-500/30 hover:scale-[1.05] active:scale-95 transition-all flex items-center gap-3">
                        <span wire:loading.remove>🚀 Launch Campaign</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Engaging...
                        </span>
                    </button>
                </div>

                <div class="pt-1 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
                    <button wire:click="$set('step', 3)" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Back to Message</button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
        }
    </style>
</div>