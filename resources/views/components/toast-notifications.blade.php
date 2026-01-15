<div x-data="{ 
        notifications: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }" x-on:notify.window="add($event.detail.message, $event.detail.type)" x-init="
        @if(session()->has('message') || session()->has('success'))
            add('{{ session('message') ?? session('success') }}', 'success');
        @endif
        @if(session()->has('error'))
            add('{{ session('error') }}', 'error');
        @endif
        @if(session()->has('status'))
            add('{{ session('status') }}', 'success');
        @endif
    " class="fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none w-full max-w-xs">
    <template x-for="n in notifications" :key="n.id">
        <div x-show="true" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-1rem] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            class="pointer-events-auto bg-white dark:bg-slate-900 border dark:border-slate-800 rounded-2xl shadow-2xl p-4 flex items-center gap-3"
            :class="n.type === 'error' ? 'border-rose-100 dark:border-rose-900/30' : 'border-emerald-100 dark:border-emerald-900/30'">
            <div
                :class="n.type === 'error' ? 'p-2 bg-rose-500/10 text-rose-500 rounded-xl' : 'p-2 bg-wa-teal/10 text-wa-teal rounded-xl'">
                <template x-if="n.type === 'success'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="n.type === 'error'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
            </div>

            <div class="flex-1">
                <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="n.message"></p>
            </div>

            <button @click="remove(n.id)"
                class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>