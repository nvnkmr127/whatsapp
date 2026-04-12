<div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
    <div class="max-w-2xl">
        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
            Your <span class="text-orange-500">Message</span></h3>
        <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Select a template and fill in the details.</p>
    </div>

    @if($campaignType === 'drip')
        <x-campaigns.drip-step-selector :steps="$dripSteps" :current="$currentDripStep" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
        {{-- Form Side --}}
        <div class="space-y-8">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 block">Select Template</label>
                <select wire:model.live="selectedTemplateId" 
                    class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal transition-all cursor-pointer">
                    <option value="">-- Choose a Template --</option>
                    @foreach($this->templates as $t)
                        <option value="{{ $t->id }}">{{ str_replace('_', ' ', $t->name) }} ({{ strtoupper($t->language) }})</option>
                    @endforeach
                </select>
            </div>

            @if($campaignType === 'drip' && $currentDripStep > 0)
                <div class="p-6 bg-orange-50 dark:bg-orange-950/20 rounded-2xl border border-orange-100 dark:border-orange-900/30 space-y-4 animate-in fade-in zoom-in-95 duration-300">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-[0.2em]">Step Delay</label>
                        <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Wait before sending</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <input type="number" wire:model.live.debounce.500ms="dripSteps.{{ $currentDripStep - 1 }}.delay_minutes" 
                                class="w-full bg-white dark:bg-slate-900 border-none rounded-xl px-6 py-4 text-sm font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-orange-500 transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase tracking-widest">Min</span>
                        </div>
                        <div class="flex items-center gap-2">
                             @php 
                                $mins = (int)($dripSteps[$currentDripStep - 1]['delay_minutes'] ?? 0);
                                $days = floor($mins / 1440);
                                $hours = floor(($mins % 1440) / 60);
                                $m = $mins % 60;
                             @endphp
                             <span class="text-xs font-bold text-slate-500">
                                ≈ @if($days > 0) {{ $days }}d @endif @if($hours > 0) {{ $hours }}h @endif @if($m > 0 || ($days == 0 && $hours == 0)) {{ $m }}m @endif
                             </span>
                        </div>
                    </div>
                </div>
            @endif

            @if($this->templateInfo)
                @php $info = $this->templateInfo; @endphp

                {{-- Media --}}
                @if(in_array($info['headerType'], ['IMAGE', 'VIDEO', 'DOCUMENT']))
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Media Header ({{ $info['headerType'] }})</label>
                        <input type="url" wire:model.live.debounce.500ms="headerMediaUrl" 
                            class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl px-6 py-4 text-xs font-bold text-slate-900 dark:text-white"
                            placeholder="https://example.com/media.jpg">
                    </div>
                @endif

                {{-- Variables --}}
                @if($info['paramCount'] > 0)
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block">Variables</label>
                        <div class="space-y-3">
                            @for($i = 0; $i < $info['paramCount']; $i++)
                                <div class="relative group">
                                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase">Var {{ $i+1 }}</span>
                                    <input type="text" wire:model.live.debounce.500ms="templateVars.{{ $i }}" 
                                        class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl pl-28 pr-6 py-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal transition-all">
                                </div>
                            @endfor
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Preview Side --}}
        <div class="flex flex-col items-center">
            <x-whatsapp-phone class="mt-4" bg="bg-wa-chat-bg" darkBg="dark:bg-wa-dark-bg">
                <div class="space-y-2 mt-4 min-h-[400px]">
                    @if($this->templateInfo)
                        <div class="bg-white dark:bg-wa-dark-surface rounded-xl rounded-tl-none shadow-sm p-3 max-w-[90%]">
                            @if(in_array($info['headerType'], ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                <div class="bg-slate-100 rounded-lg aspect-video mb-3 flex items-center justify-center overflow-hidden">
                                    @if($headerMediaUrl) <img src="{{ $headerMediaUrl }}" class="w-full h-full object-cover"> @endif
                                </div>
                            @endif
                            <p class="text-xs text-slate-700 dark:text-slate-300">
                                @php
                                    $previewText = $info['bodyText'];
                                    foreach($templateVars as $idx => $v) {
                                        $previewText = str_replace('{{'.($idx+1).'}}', $v ?: '...', $previewText);
                                    }
                                @endphp
                                {!! nl2br($previewText) !!}
                            </p>
                        </div>
                    @else
                        <div class="h-full flex items-center justify-center text-slate-400 text-xs font-bold uppercase py-20">Select a template</div>
                    @endif
                </div>
            </x-whatsapp-phone>
        </div>
    </div>

    <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
        <button type="button" @click="$parent.step = 2" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Back</button>
        <button type="button" @click="$parent.step = 4"
            class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
            Continue
        </button>
    </div>
</div>
