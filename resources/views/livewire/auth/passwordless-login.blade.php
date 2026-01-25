<div class="space-y-6" wire:poll.1s="decrementTimer">
    @if ($step === 'request')
        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="flex items-center justify-between mb-2">
                <x-label for="identifier" value="{{ $type === 'email' ? 'Email Address' : 'Phone Number' }}" />
                <button type="button" wire:click="$set('type', '{{ $type === 'email' ? 'phone' : 'email' }}')"
                    class="text-xs text-indigo-600 hover:text-indigo-500 font-semibold transition-colors">
                    Use {{ $type === 'email' ? 'WhatsApp' : 'Email' }} instead
                </button>
            </div>

            <div class="relative group">
                @if($type === 'email')
                    <x-input id="identifier"
                        class="block w-full pl-10 pr-4 py-3 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-all shadow-sm"
                        type="email" wire:model="identifier" placeholder="name@company.com" required autofocus />
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                @else
                    <x-input id="identifier"
                        class="block w-full pl-10 pr-4 py-3 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-all shadow-sm"
                        type="text" wire:model="identifier" placeholder="+1234567890" required autofocus />
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                @endif
            </div>
            <x-input-error for="identifier" class="mt-2" />
        </div>

        <button wire:click="requestOtp" wire:loading.attr="disabled"
            class="w-full flex items-center justify-center py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-200 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove>Send Verification Code</span>
            <span wire:loading class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Processing...
            </span>
        </button>
    @else
        <div class="animate-in fade-in slide-in-from-right-4 duration-500">
            <div class="text-center mb-6">
                <x-label for="code" value="Verification Code" class="text-lg font-bold text-gray-800" />
                <p class="text-sm text-gray-500 mt-1">{{ $message }}</p>
            </div>

            <x-input id="code"
                class="block mt-1 w-full text-center text-3xl tracking-[0.5em] font-black rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-all shadow-sm py-4"
                type="text" wire:model="code" maxlength="6" placeholder="000000" required autofocus />
            <x-input-error for="code" class="mt-2" />
        </div>

        <div class="flex flex-col space-y-4">
            <button wire:click="verifyOtp" wire:loading.attr="disabled"
                class="w-full flex items-center justify-center py-3.5 px-4 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold shadow-lg shadow-green-200 active:scale-[0.98] transition-all disabled:opacity-50">
                <span wire:loading.remove>Verify & Login</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Verifying...
                </span>
            </button>

            <div class="text-center">
                @if ($resendCountdown > 0)
                    <p class="text-xs text-gray-400 font-medium italic">Resend available in {{ $resendCountdown }}s</p>
                @else
                    <button type="button" wire:click="requestOtp"
                        class="text-sm font-bold text-indigo-600 hover:text-indigo-500 transition-colors">
                        Didn't receive a code? Resend.
                    </button>
                @endif
                <button type="button" wire:click="$set('step', 'request')"
                    class="block w-full mt-2 text-xs text-gray-400 hover:text-gray-600 transition-colors underline underline-offset-4">
                    Change {{ $type === 'email' ? 'email' : 'phone number' }}
                </button>
            </div>
        </div>
    @endif

    @if ($error)
        <div
            class="animate-in slide-in-from-top-2 duration-300 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 text-sm rounded-r-xl flex items-start">
            <svg class="h-5 w-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="relative py-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-100 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-widest font-bold">
            <span class="px-4 bg-white dark:bg-gray-800 text-gray-400">Or Secure Access Via</span>
        </div>
    </div>

    <a href="{{ route('auth.google.redirect') }}"
        class="group w-full flex items-center justify-center py-3.5 px-4 border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-sm font-black text-gray-700 dark:text-gray-200 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all active:scale-[0.98]">
        <svg class="h-5 w-5 mr-3 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
            <path
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                fill="#4285F4" />
            <path
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                fill="#34A853" />
            <path
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"
                fill="#FBBC05" />
            <path
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                fill="#EA4335" />
        </svg>
        Continue with Google
    </a>
</div>