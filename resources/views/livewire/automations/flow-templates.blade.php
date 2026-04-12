<div class="relative w-full bg-white dark:bg-slate-950 flex flex-col max-h-[90vh] rounded-[2.5rem] overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800">
    <!-- Modal Header -->
    <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-900 flex justify-between items-center bg-white dark:bg-slate-950 z-20 shrink-0">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Flow <span class="text-wa-teal">Templates</span></h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Select a professionally pre-built flow</p>
            </div>
        </div>
        <button @click="$dispatch('close-modal')" class="text-slate-400 hover:text-rose-500 p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 p-8 overflow-y-auto custom-scrollbar bg-white dark:bg-slate-950">
        <!-- Search and Filters Section -->
        <div class="flex flex-col md:flex-row gap-4 mb-8 bg-slate-50 dark:bg-slate-800/20 p-5 rounded-3xl border border-slate-100 dark:border-slate-800/50 shadow-sm">
            <div class="flex-1 relative group">
                <input type="text" wire:model.live.debounce.500ms="search" 
                       class="w-full bg-white dark:bg-slate-900 border-none rounded-2xl text-sm pl-12 pr-4 py-4 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium placeholder:text-slate-300" 
                       placeholder="Search templates...">
                <svg class="w-5 h-5 absolute left-4 top-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            
            <select wire:model.live="industry" class="bg-white dark:bg-slate-900 border-none rounded-2xl text-[10px] font-black uppercase tracking-widest px-5 py-4 cursor-pointer shadow-sm">
                <option value="All">All Industries</option>
                @foreach(\App\Services\Automations\TemplateLibrary::getIndustries() as $ind)
                    <option value="{{ $ind }}">{{ $ind }}</option>
                @endforeach
            </select>
        </div>

        <!-- Templates Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->filteredTemplates as $key => $tpl)
                <div class="group p-6 rounded-[2rem] border-2 border-slate-50 dark:border-slate-800/60 hover:border-wa-teal/40 hover:bg-wa-teal/[0.02] hover:shadow-2xl hover:shadow-wa-teal/5 transition-all cursor-pointer bg-white dark:bg-slate-900 flex flex-col relative overflow-hidden"
                     @click="$wire.selectTemplate('{{ $key }}')">
                    
                    <div class="flex items-start gap-4 mb-4">
                        <div class="text-4xl bg-slate-50 dark:bg-slate-800/50 w-16 h-16 flex items-center justify-center rounded-2xl group-hover:scale-110 group-hover:bg-white transition-all shadow-sm">
                            {{ $tpl['icon'] }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-black text-slate-800 dark:text-white text-sm group-hover:text-wa-teal transition-colors tracking-tight leading-tight line-clamp-1">{{ $tpl['name'] }}</h3>
                            <span class="text-[8px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border bg-slate-50 text-slate-500 border-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 mt-2 block w-fit">
                                {{ $tpl['industry'] ?? 'General' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 leading-relaxed font-medium mb-6 line-clamp-3 min-h-[48px]">{{ $tpl['desc'] }}</p>
                    
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50 dark:border-slate-800/50">
                         <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-wa-teal transition-colors">{{ count($tpl['nodes']) }} Nodes</span>
                        <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center group-hover:bg-wa-teal group-hover:text-white transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center text-slate-400 font-bold uppercase tracking-widest">No templates found</div>
            @endforelse
        </div>
    </div>
</div>
