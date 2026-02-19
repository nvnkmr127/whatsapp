<div>
    @if ($step === 'request')
        <!-- Request OTP Step -->
        <div x-data="{ timer: 0 }">
            <!-- Tab Switcher with animations -->
            <div class="flex gap-2 mb-6">
                <button type="button"
                    class="flex-1 py-3 px-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all duration-300 hover:scale-[1.02] active:scale-95 {{ $type === 'email' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'bg-slate-50 dark:bg-slate-800/50 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                    wire:click="$set('type', 'email')">
                    <svg class="w-4 h-4 inline mr-2 transition-transform {{ $type === 'email' ? 'scale-110' : '' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Email
                </button>
                <button type="button"
                    class="flex-1 py-3 px-4 rounded-2xl font-black uppercase tracking-widest text-xs transition-all duration-300 hover:scale-[1.02] active:scale-95 {{ $type === 'phone' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20 animate-glow-pulse' : 'bg-slate-50 dark:bg-slate-800/50 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                    wire:click="$set('type', 'phone')">
                    <svg class="w-4 h-4 inline mr-2 transition-transform {{ $type === 'phone' ? 'scale-110' : '' }}"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                    WhatsApp
                </button>
            </div>

            <!-- Input Field with enhanced focus effects -->
            <div class="mb-6">
                <label for="identifier"
                    class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2 transition-colors">
                    {{ $type === 'email' ? 'Email Address' : 'Phone Number' }}
                </label>
                @if($type === 'email')
                    <input id="identifier" type="email" wire:model="identifier" placeholder="you@company.com" required autofocus
                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-4 focus:ring-wa-teal/10 focus:border-wa-teal focus:bg-white dark:focus:bg-slate-900 transition-all duration-300 hover:border-slate-200 dark:hover:border-slate-700" />
                @else
                    <input id="identifier" type="tel" wire:model="identifier" placeholder="+1 (555) 000-0000" required autofocus
                        x-init="
                                                    if (!$wire.identifier) {
                                                        const fetchGeoIp = async () => {
                                                            try {
                                                                const response = await fetch('https://ipapi.co/json/');
                                                                if (!response.ok) return; // Silently fail on non-200 responses
                                                                const data = await response.json();
                                                                if (data.country_calling_code) {
                                                                    $wire.set('identifier', data.country_calling_code);
                                                                }
                                                            } catch (error) {
                                                                // Silently handle errors to avoid console noise
                                                                console.debug('GeoIP lookup failed:', error);
                                                            }
                                                        };
                                                        fetchGeoIp();
                                                    }
                                                "
                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-4 focus:ring-wa-teal/10 focus:border-wa-teal focus:bg-white dark:focus:bg-slate-900 transition-all duration-300 hover:border-slate-200 dark:hover:border-slate-700" />
                @endif
                @error('identifier')
                    <span class="text-xs font-bold text-rose-500 uppercase mt-2 block animate-slide-up">{{ $message }}</span>
                @enderror
            </div>

            <!-- Send OTP Button -->
            <button wire:click="requestOtp" wire:loading.attr="disabled" type="button"
                class="w-full py-4 bg-gradient-to-r from-wa-teal to-wa-dark text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/30 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove>
                    Send Verification Code
                </span>
                <span wire:loading class="flex items-center justify-center gap-2">
                    Sending...
                </span>
            </button>

            <!-- Auto-Login Button (Local Environment Only) with enhanced gradient -->
            @if($isLocal)
                <button wire:click="autoLogin" wire:loading.attr="disabled" type="button"
                    class="w-full mt-3 py-4 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-amber-500/30 hover:scale-[1.02] hover:shadow-2xl hover:shadow-amber-500/40 active:scale-95 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden group">
                    <span
                        class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/30 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></span>
                    <span wire:loading.remove class="flex items-center justify-center gap-2 relative z-10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Login (Dev Mode)
                    </span>
                    <span wire:loading class="flex items-center justify-center gap-2 relative z-10">
                        Logging in...
                    </span>
                </button>
                <p class="text-xs text-center text-amber-600 dark:text-amber-400 font-bold mt-2">
                    ⚡ Skip OTP verification in local environment
                </p>
            @endif
        </div>
    @else
        <!-- Verify OTP Step -->
        <div>
            <!-- Success Message -->
            @if($message)
                <div class="mb-6 p-4 bg-wa-teal/10 border border-wa-teal/20 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-5 h-5 bg-wa-teal rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-wa-teal">{{ $message }}</span>
                    </div>
                </div>
            @endif

            <!-- OTP Input -->
            <div class="mb-10" x-data="{ 
                                        length: 6,
                                        otp: Array(6).fill(''),
                                        code: @entangle('code'),
                                        init() {
                                            this.$watch('otp', value => {
                                                this.code = value.join('');
                                            });
                                        },
                                        handleInput(e, index) {
                                            const input = e.target;
                                            const val = input.value.replace(/[^0-9]/g, '');

                                            this.otp[index] = val;
                                            input.value = val;

                                            if (val && index < this.length - 1) {
                                                this.$nextTick(() => {
                                                    const next = document.getElementById('otp-' + (index + 1));
                                                    if (next) {
                                                        next.focus();
                                                        next.select();
                                                    }
                                                });
                                            }
                                        },
                                        handleKeydown(e, index) {
                                            if (e.key === 'Backspace') {
                                                if (!this.otp[index] && index > 0) {
                                                    const prev = document.getElementById('otp-' + (index - 1));
                                                    if (prev) {
                                                        prev.focus();
                                                        prev.select();
                                                    }
                                                }
                                                // Clear current index binding on backspace if exists
                                                if (this.otp[index]) {
                                                    this.otp[index] = '';
                                                }
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
                                                    const dest = document.getElementById('otp-' + destIndex);
                                                    if (dest) dest.focus();
                                                });
                                            }
                                        }
                                    }" x-init="init()">
                <label class="text-xs font-black uppercase tracking-widest text-slate-400 block text-center mb-6">
                    Enter 6-Digit Verification Code
                </label>

                <div class="flex justify-between gap-2 sm:gap-4 mb-8">
                    <template x-for="(i, index) in length" :key="index">
                        <input type="text" maxlength="1" inputmode="numeric" :id="'otp-' + index" x-model="otp[index]"
                            class="otp-input w-12 h-16 sm:w-14 sm:h-20 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-2xl text-slate-900 dark:text-white font-black text-2xl sm:text-3xl text-center focus:ring-4 focus:ring-wa-teal/10 focus:border-wa-teal transition-all outline-none"
                            @input="handleInput($event, index)" @keydown="handleKeydown($event, index)"
                            @paste="handlePaste($event)">
                    </template>
                </div>

                @error('code')
                    <span class="text-xs font-bold text-rose-500 uppercase mt-2 block text-center">{{ $message }}</span>
                @enderror
            </div>

            <!-- Verify Button -->
            <button wire:click="verifyOtp" wire:loading.attr="disabled" type="button"
                class="w-full py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove>
                    Verify & Sign In
                </span>
                <span wire:loading class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Verifying...
                </span>
            </button>

            <!-- Resend / Change -->
            <div class="text-center mt-6" x-data="{ timer: @entangle('resendCountdown') }"
                x-init="setInterval(() => { if(timer > 0) timer-- }, 1000)">
                @if ($resendCountdown > 0)
                    <p class="text-sm font-bold text-slate-400 flex items-center justify-center gap-2" x-show="timer > 0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span x-text="timer"></span>s
                    </p>
                @else
                    <button type="button" wire:click="requestOtp"
                        class="text-sm font-bold text-wa-teal hover:text-wa-dark transition-colors">
                        Didn't receive a code? Resend
                    </button>
                @endif

                <button type="button" wire:click="$set('step', 'request')"
                    class="block mt-2 text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors mx-auto">
                    Change {{ $type === 'email' ? 'email' : 'phone number' }}
                </button>
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if ($error)
        <div class="mb-6 p-4 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 rounded-2xl">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-5 h-5 bg-rose-500 rounded-full flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <span class="text-sm font-bold text-rose-500">{{ $error }}</span>
            </div>
        </div>
    @endif

    <!-- Divider -->
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
        </div>
        <div class="relative flex justify-center text-xs font-black uppercase tracking-widest">
            <span class="px-4 bg-white dark:bg-slate-900 text-slate-400">Or Continue With</span>
        </div>
    </div>

    <!-- Social Logins with enhanced hover effects -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Google OAuth Button -->
        <a href="{{ route('auth.google.redirect') }}"
            class="flex items-center justify-center gap-3 w-full py-4 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-black uppercase tracking-widest text-xs rounded-2xl border-2 border-slate-200 dark:border-slate-700 hover:border-wa-teal dark:hover:border-wa-teal hover:bg-slate-50 dark:hover:bg-slate-700 hover:scale-[1.02] hover:shadow-lg hover:shadow-wa-teal/20 active:scale-95 transition-all duration-300 group">
            <svg width="18" height="18" viewBox="0 0 24 24"
                class="group-hover:scale-110 transition-transform duration-300">
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
            <span class="group-hover:text-wa-teal dark:group-hover:text-wa-teal transition-colors">Google</span>
        </a>

        <!-- Facebook OAuth Button -->
        <a href="{{ route('auth.facebook.redirect') }}"
            class="flex items-center justify-center gap-3 w-full py-4 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-black uppercase tracking-widest text-xs rounded-2xl border-2 border-slate-200 dark:border-slate-700 hover:border-wa-teal dark:hover:border-wa-teal hover:bg-slate-50 dark:hover:bg-slate-700 hover:scale-[1.02] hover:shadow-lg hover:shadow-wa-teal/20 active:scale-95 transition-all duration-300 group">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"
                class="group-hover:scale-110 transition-transform duration-300">
                <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                    fill="#1877F2" />
            </svg>
            <span class="group-hover:text-wa-teal dark:group-hover:text-wa-teal transition-colors">Facebook</span>
        </a>
    </div>


</div>