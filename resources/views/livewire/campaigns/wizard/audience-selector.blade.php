<div class="p-10 md:p-16 space-y-12 flex-1 animate-in slide-in-from-right-4 duration-500">
    <div class="max-w-2xl">
        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 uppercase tracking-tight">
            Target <span class="text-wa-teal">Audience</span></h3>
        <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">Who should receive this campaign?</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div class="space-y-6">
            @if ($dripTriggerType !== 'tag_added')
                <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl w-fit">
                    <button wire:click="$set('audienceType', 'tags')" 
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase transition-all {{ $audienceType === 'tags' ? 'bg-white dark:bg-slate-700 text-wa-teal shadow-sm' : 'text-slate-500' }}">By Tags</button>
                    <button wire:click="$set('audienceType', 'contacts')" 
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase transition-all {{ $audienceType === 'contacts' ? 'bg-white dark:bg-slate-700 text-wa-teal shadow-sm' : 'text-slate-500' }}">Individual</button>
                    <button wire:click="$set('audienceType', 'all')" 
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase transition-all {{ $audienceType === 'all' ? 'bg-white dark:bg-slate-700 text-wa-teal shadow-sm' : 'text-slate-500' }}">All</button>
                </div>
            @else
                <div class="px-6 py-3 bg-orange-500/10 text-orange-500 dark:text-orange-400 rounded-2xl text-[10px] font-black uppercase tracking-widest w-fit border border-orange-500/20">
                    Dynamic Trigger: Tag Filter Only
                </div>
            @endif

            @if($audienceType === 'tags')
                <div class="grid grid-cols-2 gap-3">
                    @foreach($this->tags as $tag)
                        <label class="relative flex items-center gap-3 p-4 rounded-2xl border-2 transition-all cursor-pointer {{ in_array($tag->id, $selectedTags) ? 'border-wa-teal bg-wa-teal/5' : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-200' }}">
                            <input type="checkbox" wire:model.live="selectedTags" value="{{ $tag->id }}" class="hidden">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            @elseif($audienceType === 'contacts')
                <div class="space-y-4">
                    <input type="text" wire:model.live.debounce.500ms="contactSearch" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 text-sm font-medium" 
                        placeholder="Search contacts...">
                    <div class="max-h-[300px] overflow-y-auto custom-scrollbar space-y-2">
                        @foreach($this->contacts as $contact)
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" wire:model.live="selectedContacts" value="{{ $contact->id }}" class="rounded-lg text-wa-teal">
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $contact->name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $contact->phone_number }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">All opted-in contacts will be included.</p>
                </div>
            @endif
        </div>

        <div class="bg-wa-teal/5 dark:bg-wa-teal/10 rounded-[2.5rem] p-10 border border-wa-teal/10 flex flex-col items-center justify-center text-center">
            <div class="text-6xl font-black text-wa-teal mb-4">{{ number_format($this->audienceCount) }}</div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-wa-teal/60">Estimated Reach</p>
            <p class="text-[11px] text-slate-500 mt-4 leading-relaxed font-medium">Your campaign will be sent to this many contacts based on your selection.</p>
        </div>
    </div>

    <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
        <button type="button" wire:click="goToStep(1)" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Back</button>
        <button type="button" wire:click="goToStep(3)"
            class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
            Next: Message
        </button>
    </div>
</div>
