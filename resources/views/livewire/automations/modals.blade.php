{{-- Publish Review Modal --}}
<x-dialog-modal wire:model.live="showPublishModal" maxWidth="2xl">
    <x-slot name="title">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">Turn On Review</h2>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Moving to Version v{{ $version + 1 }}</p>
            </div>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6 py-2">
            <div class="grid grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nodes</span>
                    <span class="text-2xl font-black text-slate-700 dark:text-white">{{ count($nodes) }}</span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Trigger</span>
                    <span class="text-sm font-black text-wa-teal">{{ ucfirst(str_replace('_', ' ', $triggerType)) }}</span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Validation</span>
                    <span class="text-sm font-black text-emerald-600">Passed</span>
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
