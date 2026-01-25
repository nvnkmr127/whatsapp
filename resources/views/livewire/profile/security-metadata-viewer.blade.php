<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Login Activity -->
        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800/50">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Last Authentication</h4>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ $lastLoginAt }}</div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Timestamp</div>
                </div>
            </div>
        </div>

        <!-- Security Status -->
        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800/50">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Security Pulse</h4>
            <div class="flex items-center gap-4">
                <div
                    class="p-3 {{ isset($metadata['security_locked']) && $metadata['security_locked'] ? 'bg-rose-500/10 text-rose-500' : 'bg-wa-teal/10 text-wa-teal' }} rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase">
                        {{ isset($metadata['security_locked']) && $metadata['security_locked'] ? 'Attention Required' : 'Account Secured' }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tight">Status Indicator</div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($metadata))
        <div class="p-6 bg-slate-900 dark:bg-slate-800 rounded-[2rem] text-white shadow-xl relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/10 blur-3xl rounded-full"></div>

            <h4 class="text-xs font-black text-indigo-400 uppercase tracking-[0.2em] mb-4 relative z-10">Advanced Security
                Meta</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 relative z-10">
                @foreach($metadata as $key => $value)
                    @if($key !== 'security_locked')
                        <div class="flex flex-col p-3 bg-white/5 border border-white/10 rounded-xl">
                            <span
                                class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mb-1">{{ str_replace('_', ' ', $key) }}</span>
                            <span class="text-xs font-bold truncate">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div
            class="p-8 text-center bg-slate-50 dark:bg-slate-800/30 rounded-[2.5rem] border border-dashed border-slate-200 dark:border-slate-800">
            <p class="text-sm font-medium text-slate-500">No extended security metadata recorded for this session.</p>
        </div>
    @endif
</div>