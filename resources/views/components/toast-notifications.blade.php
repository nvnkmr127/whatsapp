<div x-data="{ 
        notifications: [],
        notificationId: 0,
        add(message, type = 'success') {
            if (!message) return;
            
            // Prevent duplicate spam
            const exists = this.notifications.find(n => n.message === message && n.type === type);
            if (exists) return;

            const id = ++this.notificationId;
            this.notifications.push({ id, message, type });
            
            // Auto-dismiss after 5 seconds
            if (type !== 'error') {
                setTimeout(() => this.remove(id), 5000);
            }
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }" 
    x-on:notify.window="add($event.detail.message, $event.detail.type)" 
    x-init="
        @if(session()->has('message') || session()->has('success') || session()->has('checklist_message') || session()->has('flash.banner'))
            add('{{ session('message') ?? session('success') ?? session('checklist_message') ?? session('flash.banner') }}', 'success');
        @endif
        @if(session()->has('error') || session()->has('checklist_error'))
            add('{{ session('error') ?? session('checklist_error') }}', 'error');
        @endif
        @if(session()->has('warning'))
            add('{{ session('warning') }}', 'warning');
        @endif
        @if(session()->has('info') || session()->has('status'))
            add('{{ session('info') ?? session('status') }}', 'info');
        @endif
    " 
    class="fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none w-full max-w-sm">
    
    <template x-for="n in notifications" :key="n.id">
        <div 
            x-show="true" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-1rem] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200" 
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto relative group overflow-hidden bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.1)] p-4 flex items-start gap-4"
            :class="{
                'border-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-900/10': n.type === 'success',
                'border-rose-500/20 bg-rose-50/50 dark:bg-rose-900/10': n.type === 'error',
                'border-amber-500/20 bg-amber-50/50 dark:bg-amber-900/10': n.type === 'warning',
                'border-blue-500/20 bg-blue-50/50 dark:bg-blue-900/10': n.type === 'info',
            }">
            
            <!-- Type Icon -->
            <div class="flex-shrink-0 mt-0.5">
                <template x-if="n.type === 'success'">
                    <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </template>
                <template x-if="n.type === 'error'">
                    <div class="p-2 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </template>
                <template x-if="n.type === 'warning'">
                    <div class="p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>
                <template x-if="n.type === 'info'">
                    <div class="p-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p class="text-[15px] font-semibold text-slate-900 dark:text-white leading-snug" x-text="n.message"></p>
            </div>

            <!-- Close Button -->
            <button @click="remove(n.id)" class="flex-shrink-0 p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Progress Bar (Internal Decoration) -->
            <template x-if="n.type !== 'error'">
                <div class="absolute bottom-0 left-0 h-1 bg-current opacity-20 transition-all duration-[5000ms] ease-linear" style="width: 100%" x-init="setTimeout(() => $el.style.width = '0%', 10)"></div>
            </template>
        </div>
    </template>
</div>