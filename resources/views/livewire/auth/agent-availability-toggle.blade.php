<div
    class="flex items-center gap-3 bg-slate-100/50 dark:bg-slate-900/50 px-4 py-2 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 group transition-all duration-300">
    <div class="flex flex-col items-end">
        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Voice Mode</span>
        <span class="text-[10px] font-bold mt-0.5" :class="$wire.isAvailable ? 'text-wa-teal' : 'text-slate-500'">
            {{ $isAvailable ? 'AVAILABLE' : 'OFFLINE' }}
        </span>
    </div>

    <!-- Animated Switch -->
    <button type="button" wire:click="toggleAvailability"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-wa-teal/20"
        :class="$wire.isAvailable ? 'bg-wa-teal' : 'bg-slate-300 dark:bg-slate-700'" role="switch"
        aria-checked="{{ $isAvailable ? 'true' : 'false' }}">
        <span aria-hidden="true"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
            :class="$wire.isAvailable ? 'translate-x-5' : 'translate-x-0'">
            <!-- Tiny Indicator Icon -->
            <span class="absolute inset-0 flex items-center justify-center transition-opacity"
                :class="$wire.isAvailable ? 'opacity-100' : 'opacity-0'">
                <svg class="h-3 w-3 text-wa-teal" fill="currentColor" viewBox="0 0 12 12">
                    <path
                        d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                </svg>
            </span>
            <span class="absolute inset-0 flex items-center justify-center transition-opacity"
                :class="$wire.isAvailable ? 'opacity-0' : 'opacity-100'">
                <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 12 12" stroke="currentColor">
                    <path d="M4 8l4-4m0 4L4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </span>
    </button>

    <!-- Visual status pulse -->
    @if($isAvailable)
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-wa-teal opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-wa-teal"></span>
        </span>
    @else
        <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-700"></span>
    @endif
</div>