<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Campaign <span
                        class="text-wa-green">Commander</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Step {{ $step }} of 4:
                {{ $this->steps[$step] ?? 'Unknown' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            @for ($i = 1; $i <= 4; $i++)
                <div class="flex items-center">
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all {{ $step >= $i ? 'bg-wa-green text-white shadow-lg shadow-wa-green/20' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                        {{ $i }}
                    </div>
                    @if($i < 4)
                        <div class="w-8 h-0.5 {{ $step > $i ? 'bg-wa-green' : 'bg-slate-200 dark:bg-slate-800' }}"></div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- Main Wizard Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="p-8 md:p-12">

            <!-- Step 1: Details -->
            @if($step === 1)
                <div class="space-y-4">
                    <div>
                        <x-label for="name" value="Campaign Name *" />
                        <x-input id="name" type="text" wire:model="name" class="w-full mt-1"
                            placeholder="e.g. Summer Promo" />
                        <x-input-error for="name" class="mt-2" />
                    </div>
                    <!-- Scheduling Options -->
                    <div>
                        <x-label value="Sending Time" class="mb-2" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Send Now -->
                            <div class="cursor-pointer relative rounded-xl border-2 p-4 flex flex-col items-center justify-center gap-2 hover:bg-slate-50 transition-all {{ $scheduleMode === 'now' ? 'border-wa-green bg-wa-green/5' : 'border-slate-200' }}"
                                 wire:click="$set('scheduleMode', 'now')">
                                <div class="w-10 h-10 rounded-full bg-wa-green/10 text-wa-green flex items-center justify-center mb-1">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-white">Send Immediately</h3>
                                <p class="text-xs text-slate-500 text-center">Launch campaign right after review.</p>
                                
                                @if($scheduleMode === 'now')
                                    <div class="absolute top-3 right-3 text-wa-green">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Schedule Later -->
                            <div class="cursor-pointer relative rounded-xl border-2 p-4 flex flex-col items-center justify-center gap-2 hover:bg-slate-50 transition-all {{ $scheduleMode === 'later' ? 'border-blue-500 bg-blue-50/50' : 'border-slate-200' }}"
                                 wire:click="$set('scheduleMode', 'later')">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-1">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-white">Schedule for Later</h3>
                                <p class="text-xs text-slate-500 text-center">Pick a specific date and time.</p>

                                @if($scheduleMode === 'later')
                                    <div class="absolute top-3 right-3 text-blue-500">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($scheduleMode === 'later')
                        <div class="animate-fadeIn">
                            <x-label for="scheduled_at" value="Select Date & Time" />
                            <x-input id="scheduled_at" type="datetime-local" wire:model="scheduled_at" class="w-full mt-1" />
                            <x-input-error for="scheduled_at" class="mt-2" />
                        </div>
                    @endif

                    <div class="flex justify-end pt-4">
                        <x-button wire:click="$set('step', 2)" wire:loading.attr="disabled" class="bg-blue-600 text-white">
                            Next
                        </x-button>
                    </div>
                </div>
            @endif

            <!-- Step 2: Audience -->
            @if($step === 2)
                <div class="space-y-4">
                    <div>
                        <x-label value="Target Audience *" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center">
                                <input type="radio" wire:model="audienceType" value="tags" class="text-blue-600">
                                <span class="ml-2">Specific Tags</span>
                            </label>
                            <!-- <label class="flex items-center">
                                                 <input type="radio" wire:model="audienceType" value="all" class="text-blue-600">
                                                 <span class="ml-2">All Contacts</span>
                                            </label> -->
                        </div>
                        <x-input-error for="audienceType" class="mt-2" />
                    </div>

                    @if($audienceType === 'tags')
                        <div>
                            <x-label for="selectedTags" value="Select Tags *" />
                            <select id="selectedTags" wire:model.live="selectedTags" multiple
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 h-32">
                                @foreach($this->tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="selectedTags" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">Hold Cmd/Ctrl to select multiple.</p>
                        </div>
                    @endif

                    <div class="bg-blue-50 p-3 rounded">
                        <p class="text-sm text-blue-800">Est. Audience Size: <strong>{{ $audienceCount }}</strong> contacts
                        </p>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button wire:click="$set('step', 1)"
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900">Back</button>
                        <x-button wire:click="$set('step', 3)" wire:loading.attr="disabled" class="bg-blue-600 text-white">
                            Next
                        </x-button>
                    </div>
                </div>
            @endif

            <!-- Step 3: Message -->
            @if($step === 3)
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700">Select Template</label>
                        <select wire:model.live="selectedTemplateId" class="w-full border-gray-300 rounded shadow-sm">
                            <option value="">-- Choose Template --</option>
                            @foreach($this->templates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->language }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedTemplateId)
                        @php
                            $t = $this->templates->find($selectedTemplateId);
                            $components = $t->components ?? [];
                            $headerType = 'NONE';
                            $headerText = '';
                            $bodyText = '';
                            $footerText = '';

                            foreach($components as $c) {
                                if(($c['type'] ?? '') === 'HEADER') {
                                    $headerType = $c['format'] ?? 'TEXT';
                                    if($headerType === 'TEXT') $headerText = $c['text'] ?? '';
                                }
                                if(($c['type'] ?? '') === 'BODY') $bodyText = $c['text'] ?? '';
                                if(($c['type'] ?? '') === 'FOOTER') $footerText = $c['text'] ?? '';
                            }
                        @endphp

                        <div class="space-y-6">
                            <!-- Template Body Text -->
                            <div class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 p-4 rounded-xl">
                                <h3 class="font-bold text-xs text-gray-500 uppercase tracking-widest mb-2">Message Body</h3>
                                <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                                    {{ $bodyText }}
                                </div>
                            </div>
                            
                            <!-- Media Header Input -->
                            @if(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 rounded-xl mb-4">
                                    <h3 class="font-bold text-sm text-indigo-700 dark:text-indigo-300 mb-2 uppercase tracking-wider">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            Media Header Required
                                        </div>
                                    </h3>
                                    <p class="text-xs text-indigo-600/80 dark:text-indigo-400 mb-3">
                                        This template requires a <strong>{{ strtolower($headerType) }}</strong> URL.
                                    </p>
                                    <x-input type="url" wire:model="headerMediaUrl" 
                                        class="w-full" 
                                        placeholder="https://example.com/image.jpg" />
                                </div>
                            @endif

                            <!-- Variables -->
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                <h3 class="font-bold text-sm text-slate-700 dark:text-slate-300 mb-4 uppercase tracking-wider">Variables Mapping</h3>
                                
                                <p class="text-[10px] font-bold text-wa-green mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Use @{{name}} to insert contact name.
                                </p>

                                <!-- Regex to find params count -->
                                @php
                                    preg_match_all('/{{(\d+)}}/', $bodyText, $matches);
                                    $paramCount = count(array_unique($matches[1] ?? []));
                                @endphp

                                @if($paramCount > 0)
                                    <div class="space-y-3">
                                        @for($i = 1; $i <= $paramCount; $i++)
                                            <div class="relative group">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-[10px] uppercase group-focus-within:text-indigo-500 transition-colors">
                                                    Var {{ $i }}
                                                </span>
                                                <input type="text" wire:model.live="templateVars.{{ $i-1 }}" 
                                                    placeholder="Value for {{ '{'.'{'.$i.'}'.'}' }}"
                                                    class="w-full pl-16 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-sm">
                                            </div>
                                        @endfor
                                    </div>
                                @else
                                    <div class="text-sm text-slate-400 italic">No variables in this template body.</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-between pt-4">
                        <button wire:click="$set('step', 2)"
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900">Back</button>
                        <x-button wire:click="$set('step', 4)" wire:loading.attr="disabled" class="bg-blue-600 text-white">
                            Next
                        </x-button>
                    </div>
                </div>
            @endif

            <!-- Step 4: Review -->
            @if($step === 4)
                <div class="space-y-4">
                    <h3 class="font-bold text-lg">Review Campaign</h3>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><strong>Name:</strong> {{ $name }}</div>
                        <div><strong>Schedule:</strong> {{ $scheduled_at }}</div>
                        <div><strong>Audience:</strong> {{ $audienceCount }} contacts</div>
                        <div><strong>Template:</strong> {{ $this->templates->find($selectedTemplateId)->name ?? 'None' }}
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button wire:click="$set('step', 3)"
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900">Back</button>
                        <x-button wire:click="launch" wire:loading.attr="disabled"
                            class="bg-green-600 hover:bg-green-700 text-white">
                            <span wire:loading.remove>🚀 Launch Campaign</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Launching...
                            </span>
                        </x-button>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>