<div class="relative w-full max-w-md hidden md:block group px-2" x-data="{ focused: false }">
    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none">
        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-wa-primary transition-all duration-500 group-focus-within:scale-110"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
    
    <input type="text"
        wire:model.live.debounce.300ms="search"
        @focus="focused = true"
        @blur="setTimeout(() => focused = false, 200)"
        class="block w-full pl-12 pr-12 py-2.5 bg-slate-100/40 dark:bg-slate-900/40 border border-transparent rounded-[1.2rem] text-[13px] font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400/80 focus:outline-none focus:ring-2 focus:ring-wa-primary/10 focus:bg-white/80 dark:focus:bg-slate-900/80 focus:border-wa-primary/20 transition-all duration-500 shadow-sm"
        placeholder="Search contacts, campaigns..."
    >

    <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none">
        <span class="text-[9px] font-black text-slate-400 border border-slate-100 dark:border-slate-800 rounded-lg px-2 py-1 bg-white dark:bg-slate-800 shadow-sm transition-opacity duration-300"
            :class="focused ? 'opacity-0' : 'opacity-100'">⌘K</span>
    </div>

    <!-- Dropdown Results -->
    @if(strlen($search) >= 2 && count($results) > 0)
        <div x-show="focused" x-transition.opacity
             class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden z-50">
            <ul>
                @foreach($results as $result)
                    <li>
                        <a href="{{ $result['url'] }}" 
                           class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group/item">
                            
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover/item:text-wa-primary group-hover/item:bg-wa-primary/10 transition-colors">
                                @if($result['type'] === 'Contact')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $result['title'] }}</p>
                                <p class="text-[10px] text-slate-500 truncate">{{ $result['subtitle'] }}</p>
                            </div>

                            <span class="text-[9px] font-black uppercase text-slate-300 tracking-wider">{{ $result['type'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif(strlen($search) >= 2)
        <div x-show="focused" x-transition.opacity
             class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 p-4 text-center z-50">
             <p class="text-xs text-slate-400 font-medium">No results found for "{{ $search }}"</p>
        </div>
    @endif
</div>
