<div>
    <!-- Template List Modal -->
    <x-app-modal wire:model="showTemplateListModal" maxWidth="3xl" :backdropClose="false">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                    {{ __('Select') }} <span class="text-wa-teal">{{ __('Template') }}</span></h2>
                <button @click="$wire.showTemplateListModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="relative group mb-6">
                <input type="text" wire:model.live.debounce.300ms="templateSearch" placeholder="{{ __('Search templates...') }}"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-400 transition-all shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($templates as $template)
                    <button wire:click="selectTemplate({{ $template->id }})"
                        class="flex flex-col items-start p-4 bg-slate-50 dark:bg-slate-900/50 hover:bg-wa-teal/5 dark:hover:bg-wa-teal/10 border border-slate-100 dark:border-slate-800 hover:border-wa-teal/20 rounded-2xl transition-all duration-200 text-left group">
                        <div class="flex justify-between items-start w-full mb-1">
                            <span class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tight group-hover:text-wa-teal">
                                {{ $template->name }}
                            </span>
                        </div>
                        <p class="text-tiny font-bold text-slate-500 mt-1 uppercase tracking-widest">{{ $template->category }} • {{ $template->language }}</p>
                        <div class="mt-3 flex items-center justify-between w-full">
                            <span class="px-2 py-0.5 bg-wa-teal/10 text-wa-teal rounded text-[9px] font-black uppercase">{{ $template->status }}</span>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-wa-teal group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full py-12 text-center bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                        <p class="text-slate-500 text-xs font-black uppercase tracking-widest">{{ __('No templates found.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </x-app-modal>

    <!-- Template Preview Modal -->
    <x-app-modal wire:model="showTemplatePreviewModal" maxWidth="2xl" :backdropClose="false">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                    {{ __('Review') }} <span class="text-wa-teal">{{ __('Template') }}</span></h2>
            </div>

            @if($selectedTemplateId)
                @php $tpl = \App\Models\WhatsappTemplate::find($selectedTemplateId); @endphp
                <div class="space-y-6">
                    @if(!empty($templateVariables))
                        <div class="grid grid-cols-1 gap-4">
                            @foreach($templateVariables as $key => $value)
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ str_starts_with($key, 'header') ? __('Header Asset URL') : __('Variable') . ' ' . $key }}
                                    </label>
                                    <input type="text" wire:model.live="templateVariables.{{ $key }}"
                                        placeholder="{{ str_starts_with($key, 'header') ? 'https://...' : __('Variable value') }}"
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal/20 transition-all shadow-sm">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('No variables required for this template.') }}</p>
                        </div>
                    @endif

                    <div class="flex gap-3 pt-4">
                        <x-app-button variant="ghost" wire:click="closeTemplateModals" class="flex-1 uppercase font-black text-tiny">{{ __('Back') }}</x-app-button>
                        <x-app-button variant="primary" wire:click="sendTemplate" class="flex-[2] uppercase font-black text-tiny hover:scale-[1.02]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                {{ __('Send Template') }}
                            </span>
                        </x-app-button>
                    </div>
                </div>
            @endif
        </div>
    </x-app-modal>
</div>
