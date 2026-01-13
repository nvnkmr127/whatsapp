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
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
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
                    <div>
                        <x-label for="scheduled_at" value="Schedule Time" />
                        <x-input id="scheduled_at" type="datetime-local" wire:model="scheduled_at" class="w-full mt-1" />
                        <x-input-error for="scheduled_at" class="mt-2" />
                    </div>

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
                        @php $t = $this->templates->find($selectedTemplateId); @endphp
                        <div class="border p-4 rounded bg-gray-50">
                            <p class="font-bold text-xs uppercase text-gray-500 mb-2">Preview</p>
                            <p class="text-sm whitespace-pre-wrap">{{ $t->body_text ?? 'Template body here' }}</p>
                        </div>

                        <!-- Variable Mapping -->
                        <!-- Naive implementation: assume template variables if any -->
                        <!-- Ideally parse body_text for {{1}}, {{2}} -->
                        <div class="mt-6">
                            <p class="font-black text-[10px] uppercase tracking-widest text-slate-400 mb-3">Variables Mapping
                                (Optional)</p>
                            <p class="text-[10px] font-bold text-wa-green mb-4">USE @{{name}} TO INSERT CONTACT NAME DATA.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="relative">
                                    <span
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-[10px] uppercase">Var
                                        1</span>
                                    <input type="text" wire:model="templateVars.0" placeholder="Parameter value"
                                        class="w-full pl-16 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-wa-green/20 transition-all font-bold text-sm">
                                </div>
                                <div class="relative">
                                    <span
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-[10px] uppercase">Var
                                        2</span>
                                    <input type="text" wire:model="templateVars.1" placeholder="Parameter value"
                                        class="w-full pl-16 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-wa-green/20 transition-all font-bold text-sm">
                                </div>
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