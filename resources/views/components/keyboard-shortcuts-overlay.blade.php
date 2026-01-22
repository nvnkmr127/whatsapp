<div x-data="{ 
    show: false,
    init() {
        // Listen for custom event
        this.$watch('show', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Listen for ? key
        document.addEventListener('keydown', (e) => {
            if (e.key === '?' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const tagName = e.target.tagName.toLowerCase();
                if (tagName !== 'input' && tagName !== 'textarea') {
                    e.preventDefault();
                    this.show = !this.show;
                }
            }
            // ESC to close
            if (e.key === 'Escape' && this.show) {
                this.show = false;
            }
        });
        
        // Listen for custom event from quick actions
        window.addEventListener('show-shortcuts', () => {
            this.show = true;
        });
    }
}" @show-shortcuts.window="show = true">

    {{-- Overlay --}}
    <div x-show="show" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="show = false"
        class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div @click.stop x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div
                class="sticky top-0 z-10 p-8 pb-6 bg-gradient-to-br from-wa-teal/10 to-emerald-500/5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h2
                            class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-3">
                            <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            Keyboard <span class="text-wa-teal">Shortcuts</span>
                        </h2>
                        <p class="text-slate-500 font-medium mt-2">Master these shortcuts to work faster</p>
                    </div>
                    <button @click="show = false"
                        class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Shortcuts Grid --}}
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- Navigation --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        Navigation
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Next contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">J</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Previous contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">K</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Search contacts</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">/</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Open contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">Enter</kbd>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Quick Actions
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Assign to me</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">A</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Add tag</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">T</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Add to segment</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">S</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Send message</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">M</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Mark as VIP</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">V</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Archive contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">E</kbd>
                        </div>
                    </div>
                </div>

                {{-- Management --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Management
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Create new
                                contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">C</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Edit contact</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">E</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Delete contact</span>
                            <div class="flex gap-1">
                                <kbd
                                    class="px-2 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">Shift</kbd>
                                <span class="text-slate-400">+</span>
                                <kbd
                                    class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">D</kbd>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Refresh list</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">R</kbd>
                        </div>
                    </div>
                </div>

                {{-- General --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        General
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Show shortcuts</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">?</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Close
                                modal/overlay</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">Esc</kbd>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Quick actions
                                panel</span>
                            <kbd
                                class="px-3 py-1.5 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-xs font-bold rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">Q</kbd>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div
                class="sticky bottom-0 p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 text-center">
                <p class="text-xs text-slate-500 font-medium">
                    Press <kbd
                        class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-[10px] font-bold rounded border border-slate-200 dark:border-slate-600">?</kbd>
                    anytime to toggle this overlay
                </p>
            </div>
        </div>
    </div>
</div>