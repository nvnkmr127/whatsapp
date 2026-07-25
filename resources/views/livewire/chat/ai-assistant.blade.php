<div>
    <x-app-modal wire:model="showAiModal" maxWidth="xl">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                   <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ __('AI Assistant') }}</h2>
                   <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Smart Reply Suggestions') }}</p>
                </div>
            </div>

            @if($isGenerating)
               <div class="flex flex-col items-center justify-center py-12 space-y-4">
                  <div class="relative w-12 h-12">
                     <div class="absolute inset-0 border-4 border-indigo-100 dark:border-indigo-900/20 rounded-full"></div>
                     <div class="absolute inset-0 border-4 border-indigo-600 rounded-full animate-spin border-t-transparent shadow-lg shadow-indigo-600/20"></div>
                  </div>
                  <p class="text-xs font-black text-indigo-600 uppercase tracking-widest animate-pulse">{{ __('Generating Magic...') }}</p>
               </div>
            @else
               <div class="space-y-3">
                  @foreach($this->suggestions as $index => $suggestion)
                    <button wire:click="selectSuggestionByIndex({{ $loop->index }})"
                        class="w-full p-4 text-left bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 transition-all duration-200 group">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 leading-relaxed">{{ $suggestion }}</p>
                        <div class="mt-3 flex justify-end">
                           <span class="text-[8px] font-black text-indigo-600 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">{{ __('Click to use') }}</span>
                        </div>
                    </button>
                  @endforeach

                  @if(empty($suggestions))
                    <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('Failed to generate suggestions.') }}</p>
                        <button wire:click="generateSuggestions" class="mt-4 text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">{{ __('Try Again') }}</button>
                    </div>
                  @endif
               </div>
            @endif

            <div class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-800">
               <button @click="$wire.showAiModal = false" class="w-full py-3 text-tiny font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                  {{ __('Dismiss') }}
               </button>
            </div>
        </div>
    </x-app-modal>
</div>
