<div x-data="{ 
        showTooltip: false,
        init() {
            $wire.checkEligibility();
        }
    }" class="relative inline-block">

    <!-- Button Container -->
    <div @mouseenter="showTooltip = true" @mouseleave="showTooltip = false" class="relative">
        <button wire:click="initiateCall" wire:loading.attr="disabled" @if($isLoading || ($eligibility && !$eligibility['eligible'])) disabled @endif class="group relative flex items-center justify-center p-2.5 rounded-xl transition-all duration-300
                {{ $isLoading ? 'bg-slate-100 dark:bg-slate-800 animate-pulse cursor-wait' : '' }}
                {{ !$isLoading && $eligibility && $eligibility['eligible'] ? 'bg-wa-teal text-white hover:scale-105 active:scale-95 shadow-lg shadow-wa-teal/20' : '' }}
                {{ !$isLoading && $eligibility && !$eligibility['eligible'] ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed opacity-60' : '' }}
            ">
            <!-- Loading Indicator -->
            <div wire:loading wire:target="checkEligibility, initiateCall"
                class="absolute inset-0 flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 text-wa-teal" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            <!-- Call Icon -->
            <div wire:loading.remove wire:target="checkEligibility, initiateCall" class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>

                @if($eligibility && $eligibility['eligible'] && !$isLoading)
                    <span class="text-[10px] font-black uppercase tracking-widest hidden group-hover:block ml-1">Call</span>
                @endif
            </div>
        </button>

        <!-- Tooltip for Ineligible State -->
        @if(!$isLoading && $eligibility && !$eligibility['eligible'])
            <div x-show="showTooltip" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-cloak
                class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-900 text-white text-[10px] rounded-lg shadow-xl text-center leading-tight font-medium">
                <div class="mb-1 text-rose-400 font-black uppercase flex items-center justify-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    Blocked
                </div>
                {{ $eligibility['user_message'] ?? 'Calling is currently unavailable.' }}

                @if(isset($eligibility['can_retry_at']))
                    <div class="mt-1 text-slate-400 italic">
                        Retry at: {{ \Carbon\Carbon::parse($eligibility['can_retry_at'])->format('H:i') }}
                    </div>
                @endif

                <!-- Arrow -->
                <div
                    class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900">
                </div>
            </div>
        @endif

        <!-- Tooltip for Ready State (Optional) -->
        @if(!$isLoading && $eligibility && $eligibility['eligible'])
            <div x-show="showTooltip" x-transition:enter="transition ease-out duration-200" x-cloak
                class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-wa-teal text-white text-[9px] font-black uppercase tracking-widest rounded shadow-lg whitespace-nowrap">
                Start WhatsApp Call
                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-wa-teal">
                </div>
            </div>
        @endif
    </div>
</div>