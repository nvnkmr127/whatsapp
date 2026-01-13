<div class="space-y-8 animate-in fade-in duration-500">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">WhatsApp Configuration</h1>
            <p class="text-slate-500 dark:text-slate-400">Manage your WhatsApp Business API connection and settings.</p>
        </div>
        @if($is_whatsmark_connected)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Connected
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                Not Connected
            </span>
        @endif
    </div>

    @if($is_whatsmark_connected)
        <!-- Dashboard View -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Stats Cards (Mock for visual consistency with previous design) -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-slate-500">Message Credits</div>
                    <div class="p-2 bg-green-100 rounded-lg text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white">6.8K <span
                        class="text-sm font-normal text-slate-400">/ 30K</span></div>
                <div class="mt-2 text-xs text-green-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    23% used
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-slate-500">Quality Rating</div>
                    <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-green-500">{{ $wm_quality_rating ?? 'Unknown' }}</div>
                <div class="mt-2 text-xs text-slate-500">Based on meta data</div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-slate-500">Messaging Limit</div>
                    <div class="p-2 bg-purple-100 rounded-lg text-purple-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $wm_messaging_limit ?? 'N/A' }}</div>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-xs text-slate-500">24h Limit</span>
                    <button wire:click="syncInfo" wire:loading.attr="disabled"
                        class="text-xs bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded transition-colors flex items-center">
                        <svg wire:loading.class="animate-spin" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Sync Info
                    </button>
                </div>
            </div>
        </div>

        <!-- Connection Details -->
        <div
            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div
                class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex justify-between items-center">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Connection Details</h3>
                <button wire:click="disconnect"
                    class="text-sm text-red-600 hover:text-red-700 font-medium">Disconnect</button>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Phone Number</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white font-mono">
                            {{ $wm_phone_display ?? $wm_default_phone_number ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Phone Number ID</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white font-mono">
                            {{ $wm_default_phone_number_id ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">WABA ID</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white font-mono">{{ $wm_business_account_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Last Synced</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ now()->format('M d, Y H:i A') }}</dd>
                    </div>
                </dl>
            </div>
            <!-- Webhook Info -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
                <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-3">Webhook Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold">Callback URL</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <code
                                class="text-xs bg-slate-100 dark:bg-slate-800 p-2 rounded text-blue-600 break-all w-full">{{ route('whatsapp.webhook') }}</code>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 uppercase font-bold">Verify Token</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <code
                                class="text-xs bg-slate-100 dark:bg-slate-800 p-2 rounded text-slate-700 dark:text-slate-300 w-full">{{ $webhook_verify_token }}</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outbound Webhook Configuration -->
            <div class="px-6 py-4 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-3">Outbound Webhook (Events Forwarding)
                </h4>
                <div class="flex items-end gap-4">
                    <div class="flex-grow">
                        <x-label for="outbound_webhook_url" value="Target URL" />
                        <x-input id="outbound_webhook_url" type="url" wire:model="outbound_webhook_url" class="w-full mt-1"
                            placeholder="https://api.yoursystem.com/webhook" />
                    </div>
                    <x-button wire:click="updateOutboundWebhook" wire:loading.attr="disabled" class="mb-[2px]">
                        Save URL
                    </x-button>
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    We will forward all incoming WhatsApp messages to this URL via POST request.
                </p>
            </div>

        </div>
    @else
        <!-- Connect Form -->
        <div class="max-w-3xl mx-auto">
            <div
                class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-8">
                    <div
                        class="mb-8 p-6 bg-blue-50 dark:bg-slate-900 rounded-xl border border-blue-100 dark:border-blue-900 text-center">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-blue-100 mb-2">Social Login (Recommended)</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 max-w-lg mx-auto">
                            Connect continuously using your Facebook account. This will automatically fetch your WABA ID and
                            Token.
                        </p>

                        <div id="fb-login-container">
                            <button onclick="launchWhatsAppSignup()" id="fb-login-btn" type="button"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-[#1877F2] hover:bg-[#166fe5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform hover:-translate-y-0.5">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.791-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                Connect with Facebook
                            </button>
                            <div id="https-warning"
                                class="hidden mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-xs text-left">
                                <strong>⚠️ SSL Required:</strong> Facebook Login works only on HTTPS. Please use
                                <strong>ngrok</strong> or Connect Manually below.
                            </div>
                        </div>
                        <script>
                            if (window.location.protocol !== 'https:') {
                                document.getElementById('fb-login-btn').classList.add('opacity-50', 'cursor-not-allowed');
                                document.getElementById('fb-login-btn').setAttribute('disabled', 'disabled');
                                document.getElementById('https-warning').classList.remove('hidden');
                            }
                        </script>
                    </div>

                    <div class="relative flex items-center py-4 mb-8">
                        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                        <span class="flex-shrink-0 mx-4 text-slate-400 text-xs font-bold uppercase tracking-wider">OR
                            Connect Manually</span>
                        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                    </div>

                    <form wire:submit.prevent="connect" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-label for="wm_fb_app_id" value="Meta App ID *" />
                                <x-input id="wm_fb_app_id" type="text" wire:model="wm_fb_app_id" class="w-full mt-1" />
                                <x-input-error for="wm_fb_app_id" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="wm_fb_app_secret" value="Meta App Secret *" />
                                <x-input id="wm_fb_app_secret" type="password" wire:model="wm_fb_app_secret"
                                    class="w-full mt-1" />
                                <x-input-error for="wm_fb_app_secret" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-label for="wm_business_account_id" value="WhatsApp Business Account ID *" />
                            <x-input id="wm_business_account_id" type="text" wire:model="wm_business_account_id"
                                class="w-full mt-1" />
                            <x-input-error for="wm_business_account_id" class="mt-2" />
                        </div>

                        <div>
                            <x-label for="wm_access_token" value="User User / System Access Token *" />
                            <x-input id="wm_access_token" type="password" wire:model="wm_access_token"
                                class="w-full mt-1" />
                            <x-input-error for="wm_access_token" class="mt-2" />
                            <p class="text-xs text-slate-500 mt-2">Make sure the token has `whatsapp_business_management`
                                and `whatsapp_business_messaging` permissions.</p>
                        </div>

                        <script>
                            window.fbAsyncInit = function () {
                                FB.init({
                                    appId: '{{ config("services.facebook.client_id") }}',
                                    autoLogAppEvents: true,
                                    xfbml: true,
                                    version: 'v21.0'
                                });
                            };

                            (function (d, s, id) {
                                var js, fjs = d.getElementsByTagName(s)[0];
                                if (d.getElementById(id)) { return; }
                                js = d.createElement(s); js.id = id;
                                js.src = "https://connect.facebook.net/en_US/sdk.js";
                                fjs.parentNode.insertBefore(js, fjs);
                            }(document, 'script', 'facebook-jssdk'));

                            function launchWhatsAppSignup() {
                                // Conversion tracking code
                                // fbq && fbq('trackCustom', 'WhatsAppOnboardingStart', {appId: '{{ config("services.facebook.client_id") }}', feature: 'whatsapp_embedded_signup'});

                                // Launch Facebook Login
                                FB.login(function (response) {
                                    if (response.authResponse) {
                                        const code = response.authResponse.accessToken;
                                        // Use the token to exchange for a long-lived token
                                        axios.post('{{ route("whatsapp.onboard.exchange") }}', {
                                            access_token: code
                                        })
                                            .then(function (res) {
                                                if (res.data.status) {
                                                    // Call Livewire method to finish setup
                                                    @this.handleEmbeddedSuccess(res.data.access_token);
                                                } else {
                                                    alert('Error exchanging token: ' + res.data.message);
                                                }
                                            })
                                            .catch(function (error) {
                                                console.error(error);
                                                alert('System error during token exchange');
                                            });
                                    } else {
                                        console.log('User cancelled login or did not fully authorize.');
                                    }
                                }, {
                                    scope: 'whatsapp_business_management, whatsapp_business_messaging',
                                    extras: {
                                        feature: 'whatsapp_embedded_signup',
                                        sessionInfoVersion: '2'
                                    }
                                });
                            }
                        </script>

                        <div class="pt-4">
                            <x-button wire:loading.attr="disabled"
                                class="w-full justify-center py-3 bg-green-600 hover:bg-green-700">
                                <span wire:loading.remove>Connect Account</span>
                                <span wire:loading class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Connecting...
                                </span>
                            </x-button>
                        </div>
                    </form>
                </div>
                <div
                    class="px-8 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 text-center text-xs text-slate-500">
                    By connecting, you agree to WhatsApp Business Terms of Service.
                </div>
            </div>
        </div>
    @endif
</div>