@if(session()->has('impersonated_by'))
    <div class="bg-rose-600 text-white py-2 px-4 shadow-lg flex items-center justify-between z-[60] sticky top-0">
        <div class="flex items-center gap-3">
            <div class="animate-pulse w-2 h-2 bg-white rounded-full"></div>
            <span class="text-xs font-black uppercase tracking-widest text-white">
                Impersonation Mode: <span class="text-rose-200">{{ auth()->user()->name }}</span>
            </span>
        </div>

        <a href="{{ route('admin.impersonate.exit') }}"
            class="flex items-center gap-2 px-4 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Restore Admin Identity
        </a>
    </div>
@endif