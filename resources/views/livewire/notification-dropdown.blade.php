<div class="relative" x-data="{ open: @entangle('isOpen') }">
    <button wire:click="toggle"
        class="relative p-2.5 rounded-2xl text-slate-400 hover:text-wa-primary hover:bg-white dark:hover:bg-slate-900 shadow-none hover:shadow-lg transition-all duration-300 focus:outline-none group">

        @if($unreadCount > 0)
            <span
                class="absolute top-2.5 right-2.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white dark:ring-slate-950 animate-pulse"></span>
        @endif

        <svg class="w-5 h-5 group-hover:animate-swing" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
    </button>

    <!-- Notifications Dropdown -->
    <div x-show="open" @click.away="$wire.set('isOpen', false)" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95" style="display: none;"
        class="absolute right-0 mt-3 w-80 bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden z-50">

        <div class="p-5 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Notifications</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead"
                    class="px-2 py-0.5 bg-wa-primary/10 hover:bg-wa-primary/20 text-wa-primary text-[8px] font-black uppercase tracking-widest rounded-md transition-colors">
                    Mark Read
                </button>
            @endif
        </div>

        <div class="max-h-[300px] overflow-y-auto">
            @forelse($notifications as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')"
                    class="p-4 flex items-start gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group/notif {{ $notification->read_at ? 'opacity-60' : '' }}">

                    <div
                        class="h-10 w-10 rounded-full {{ $notification->read_at ? 'bg-slate-100 dark:bg-slate-800 text-slate-400' : 'bg-wa-primary/10 text-wa-primary' }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ $notification->data['title'] ?? 'Notification' }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-1">
                            {{ $notification->data['message'] ?? $notification->data['line'] ?? 'You have a new notification.' }}
                        </p>
                        <p class="text-[9px] text-slate-400 mt-2 font-black uppercase">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">No notifications</p>
                </div>
            @endforelse
        </div>

        <!-- View All Link (Optional/Placeholder) -->
        <!-- 
        <a href="#" class="block p-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-wa-primary hover:bg-slate-50 dark:hover:bg-slate-800 transition-all border-t border-slate-50 dark:border-slate-800">
            View All Activity
        </a> 
        -->
    </div>
</div>