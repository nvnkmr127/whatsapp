@props(['contactId'])

<div x-data="{ open: false }" class="relative">
    {{-- Floating Action Button --}}
    <button @click="open = !open"
        class="fixed bottom-8 right-8 z-40 w-14 h-14 bg-wa-teal hover:bg-emerald-600 text-white rounded-full shadow-2xl shadow-wa-teal/40 flex items-center justify-center transition-all hover:scale-110 active:scale-95 group">
        <svg x-show="!open" class="w-6 h-6 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    {{-- Actions Panel --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90" @click.away="open = false"
        class="fixed bottom-24 right-8 z-40 w-64 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">

        <div
            class="p-4 bg-gradient-to-br from-wa-teal/10 to-emerald-500/5 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Quick Actions</h3>
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">Shortcuts for common tasks</p>
        </div>

        <div class="p-2 space-y-1">
            {{-- Assign to Me --}}
            <button wire:click="assignToMe({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-blue-500/10 text-blue-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Assign to Me</div>
                    <div class="text-[9px] text-slate-500">Take ownership</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">A</kbd>
            </button>

            {{-- Add Tag --}}
            <button wire:click="quickAddTag({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-purple-500/10 text-purple-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Add Tag</div>
                    <div class="text-[9px] text-slate-500">Categorize contact</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">T</kbd>
            </button>

            {{-- Add to Segment --}}
            <button wire:click="addToSegment({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-wa-teal/10 text-wa-teal rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Add to Segment</div>
                    <div class="text-[9px] text-slate-500">Group contact</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">S</kbd>
            </button>

            {{-- Send Template --}}
            <button wire:click="sendTemplate({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-emerald-500/10 text-emerald-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Send Template</div>
                    <div class="text-[9px] text-slate-500">Quick message</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">M</kbd>
            </button>

            {{-- Mark as VIP --}}
            <button wire:click="toggleVIP({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-amber-500/10 text-amber-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Mark as VIP</div>
                    <div class="text-[9px] text-slate-500">Priority contact</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">V</kbd>
            </button>

            {{-- Archive --}}
            <button wire:click="archiveContact({{ $contactId }})" @click="open = false"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group text-left">
                <div
                    class="w-8 h-8 bg-slate-500/10 text-slate-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white">Archive</div>
                    <div class="text-[9px] text-slate-500">Hide from list</div>
                </div>
                <kbd
                    class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-500 rounded">E</kbd>
            </button>
        </div>

        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
            <button @click="$dispatch('show-shortcuts')"
                class="w-full text-[10px] font-bold text-slate-500 hover:text-wa-teal transition-colors flex items-center justify-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                View All Shortcuts (?)
            </button>
        </div>
    </div>
</div>