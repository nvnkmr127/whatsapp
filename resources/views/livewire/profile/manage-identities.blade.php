<x-action-section>
    <x-slot name="title">
        {{ __('Authentication Identities') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Manage the external accounts and methods you use to log in to your account.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
            {{ __('Linking multiple identity providers (like Google or your Phone Number) ensures you can always regain access to your account even if you lose access to one method.') }}
        </div>

        <div class="mt-5 space-y-6">
            @foreach ($identities as $identity)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                            @if($identity->provider === 'google')
                                <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12.48 10.92v3.28h7.84c-.24 1.84-.9 3.34-2.12 4.46-1.5 1.3-3.8 2.38-7.88 2.38-6.12 0-11-4.88-11-10.88s4.88-11 10.88-11c3.16 0 5.48 1.14 7.22 2.7l2.42-2.42c-2.4-2.16-5.64-3.54-9.64-3.54-7.56 0-13.88 6.32-13.88 13.88s6.32 13.88 13.88 13.88c4.08 0 7.24-1.32 9.6-3.8 2.44-2.44 3.08-5.88 3.08-8.68 0-.68-.06-1.34-.16-2H12.48z" />
                                </svg>
                            @elseif($identity->provider === 'phone_otp')
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                {{ str_replace('_', ' ', $identity->provider) }}
                            </div>
                            <div class="text-xs text-slate-500 font-bold">
                                {{ $identity->provider_id }}
                                <span class="mx-1">•</span>
                                {{ __('Last used :time', ['time' => $identity->last_login_at->diffForHumans()]) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <x-danger-button wire:click="confirmIdentityUnlink({{ $identity->id }})"
                            wire:loading.attr="disabled" class="text-[10px] uppercase font-black tracking-widest px-3 py-1">
                            {{ __('Unlink') }}
                        </x-danger-button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-4">
            <a href="{{ route('auth.google.redirect') }}"
                class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12.48 10.92v3.28h7.84c-.24 1.84-.9 3.34-2.12 4.46-1.5 1.3-3.8 2.38-7.88 2.38-6.12 0-11-4.88-11-10.88s4.88-11 10.88-11c3.16 0 5.48 1.14 7.22 2.7l2.42-2.42c-2.4-2.16-5.64-3.54-9.64-3.54-7.56 0-13.88 6.32-13.88 13.88s6.32 13.88 13.88 13.88c4.08 0 7.24-1.32 9.6-3.8 2.44-2.44 3.08-5.88 3.08-8.68 0-.68-.06-1.34-.16-2H12.48z" />
                </svg>
                Connect Google
            </a>

            @if(!Auth::user()->identities()->where('provider', 'phone_otp')->exists())
                <button wire:click="$set('confirmingLinkPhone', true)"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Link Phone
                </button>
            @endif
        </div>

        <!-- Unlink Identity Confirmation Modal -->
        <x-confirmation-modal wire:model.live="confirmingIdentityUnlink">
            <x-slot name="title">
                {{ __('Unlink Authentication Method') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Are you sure you want to unlink this authentication method? You will no longer be able to log in using this specific account. Ensure you have an alternative method linked.') }}
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingIdentityUnlink')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="unlinkIdentity" wire:loading.attr="disabled">
                    {{ __('Unlink') }}
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>

        <!-- Link Phone Modal -->
        <x-dialog-modal wire:model.live="confirmingLinkPhone">
            <x-slot name="title">
                {{ __('Link Phone Number') }}
            </x-slot>

            <x-slot name="content">
                @if ($linkPhoneStep === 'request')
                    <!-- Request OTP Step -->
                    <form wire:submit.prevent="requestLinkPhoneOtp" x-data="{ timer: 0 }">
                        <div class="mb-6">
                            <label for="linkPhoneIdentifier" class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2 transition-colors">
                                Phone Number
                            </label>
                            <input id="linkPhoneIdentifier" type="tel" wire:model="linkPhoneIdentifier" placeholder="+1 (555) 000-0000" required autofocus
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition-all duration-300" />
                            @error('linkPhoneIdentifier')
                                <span class="text-xs font-bold text-rose-500 uppercase mt-2 block">{{ $message }}</span>
                            @enderror
                            @if ($linkPhoneError)
                                <span class="text-xs font-bold text-rose-500 uppercase mt-2 block">{{ $linkPhoneError }}</span>
                            @endif
                        </div>

                        <button wire:loading.attr="disabled" type="submit"
                            class="w-full py-3 bg-emerald-500 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-emerald-500/30 hover:bg-emerald-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>Send Verification Code</span>
                            <span wire:loading>Sending...</span>
                        </button>
                    </form>
                @else
                    <!-- Verify OTP Step -->
                    <div x-data="{ 
                            length: 6,
                            otp: Array(6).fill(''),
                            verifying: false,
                            code: $wire.entangle('linkPhoneCode'),
                            init() {
                                this.$watch('otp', value => {
                                    this.code = value.join('');
                                    if (value.every(v => v !== '') && value.join('').length === this.length && !this.verifying) {
                                        this.verifying = true;
                                        this.$nextTick(() => $wire.verifyLinkPhoneOtp());
                                    }
                                    if (value.some(v => v === '')) {
                                        this.verifying = false;
                                    }
                                });
                            },
                            handleInput(e, index) {
                                const input = e.target;
                                const val = input.value.replace(/[^0-9]/g, '');
                                this.otp[index] = val;
                                input.value = val;
                                if (val && index < this.length - 1) {
                                    this.$nextTick(() => {
                                        const next = document.getElementById('link-otp-' + (index + 1));
                                        if (next) { next.focus(); next.select(); }
                                    });
                                }
                            },
                            handleKeydown(e, index) {
                                if (e.key === 'Backspace') {
                                    if (!this.otp[index] && index > 0) {
                                        const prev = document.getElementById('link-otp-' + (index - 1));
                                        if (prev) { prev.focus(); prev.select(); }
                                    }
                                    if (this.otp[index]) this.otp[index] = '';
                                }
                            },
                            handlePaste(e) {
                                e.preventDefault();
                                const paste = (e.clipboardData || window.clipboardData).getData('text');
                                const cleanPaste = paste.replace(/[^0-9]/g, '').slice(0, this.length);
                                if (cleanPaste) {
                                    cleanPaste.split('').forEach((char, i) => {
                                        if (i < this.length) this.otp[i] = char;
                                    });
                                    this.$nextTick(() => {
                                        const destIndex = Math.min(cleanPaste.length - 1, this.length - 1);
                                        const dest = document.getElementById('link-otp-' + destIndex);
                                        if (dest) dest.focus();
                                    });
                                }
                            }
                        }" x-init="init()">
                        
                        @if($linkPhoneMessage)
                            <div class="mb-4 text-sm font-bold text-emerald-500">{{ $linkPhoneMessage }}</div>
                        @endif

                        <div class="mb-6">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400 block text-center mb-4">
                                Enter 6-Digit Verification Code
                            </label>

                            <div class="flex justify-center gap-2 mb-4">
                                <template x-for="(i, index) in length" :key="index">
                                    <input type="text" maxlength="1" inputmode="numeric" :id="'link-otp-' + index" x-model="otp[index]"
                                        :disabled="verifying"
                                        class="w-10 h-14 bg-slate-50 dark:bg-slate-800/50 border-2 rounded-2xl text-slate-900 dark:text-white font-black text-xl text-center focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none"
                                        @input="handleInput($event, index)" @keydown="handleKeydown($event, index)"
                                        @paste="handlePaste($event)">
                                </template>
                            </div>

                            @error('linkPhoneCode')
                                <span class="text-xs font-bold text-rose-500 uppercase mt-2 block text-center">{{ $message }}</span>
                            @enderror
                            @if ($linkPhoneError)
                                <span class="text-xs font-bold text-rose-500 uppercase mt-2 block text-center">{{ $linkPhoneError }}</span>
                            @endif
                        </div>

                        <div class="text-center mt-4" x-data="{ timer: {{ $resendCountdown }} }"
                            @start-timer.window="timer = $event.detail.duration"
                            x-init="setInterval(() => { if(timer > 0) timer-- }, 1000)">
                            
                            <p class="text-sm font-bold text-slate-400" x-show="timer > 0">
                                Resend in <span x-text="timer"></span>s
                            </p>

                            <div x-show="timer <= 0" style="display: none;">
                                <button type="button" wire:click="requestLinkPhoneOtp"
                                    class="text-sm font-bold text-emerald-500 hover:text-emerald-600 transition-colors">
                                    Didn't receive a code? Resend
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingLinkPhone')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>
            </x-slot>
        </x-dialog-modal>
    </x-slot>
</x-action-section>