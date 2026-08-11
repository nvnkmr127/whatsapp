<div class="w-full">
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4">
            <span class="bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest">Recommended</span>
        </div>
        
        <div class="text-center mb-8">
            <div class="inline-flex p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl text-blue-600 mb-4">
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Connect Meta Account</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mt-2 max-w-md mx-auto">
                Connect your Facebook & Instagram accounts to enable Ads Management, Analytics, and WhatsApp integration in one go.
            </p>
        </div>

        <div class="flex flex-col items-center gap-4">
            <div id="meta-login-container" wire:ignore>
                <button onclick="launchMetaSignup()" id="meta-login-btn" type="button"
                    class="inline-flex items-center px-8 py-4 bg-[#1877F2] hover:bg-[#166fe5] text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    CONTINUE WITH FACEBOOK
                </button>
            </div>

            <div id="https-warning-meta" class="hidden text-center">
                <p class="text-[10px] text-rose-500 font-bold uppercase tracking-widest bg-rose-50 dark:bg-rose-900/20 px-4 py-2 rounded-lg border border-rose-100 dark:border-rose-800">
                    ⚠️ HTTPS Connection Required
                </p>
            </div>
            
            <div wire:loading wire:target="handleAuthResponse">
                <div class="flex items-center gap-2 text-slate-500 text-xs font-bold uppercase tracking-widest">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Connecting Account...
                </div>
            </div>

            @if($errorMessage)
                <div class="mt-2 text-center">
                    <p class="text-xs text-rose-500 font-bold">{{ $errorMessage }}</p>
                </div>
            @endif
        </div>
    </div>

    @script
    <script>
        {
            let sdkInitialized = false;
            const appId = '{{ config("services.facebook.client_id") }}';

            // HTTPS Check
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                document.getElementById('meta-login-btn').classList.add('opacity-50', 'cursor-not-allowed');
                document.getElementById('meta-login-btn').disabled = true;
                document.getElementById('https-warning-meta').classList.remove('hidden');
            }

            // Initialize FB SDK if not already done
            if (typeof FB === 'undefined') {
                window.fbAsyncInit = function() {
                    FB.init({
                        appId: appId,
                        cookie: true,
                        xfbml: true,
                        version: 'v21.0'
                    });
                    sdkInitialized = true;
                };

                (function(d, s, id){
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) {return;}
                    js = d.createElement(s); js.id = id;
                    js.src = "https://connect.facebook.net/en_US/sdk.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));
            } else {
                sdkInitialized = true;
            }

            window.launchMetaSignup = function() {
                if (typeof FB === 'undefined') {
                    alert('Facebook SDK is loading...');
                    return;
                }

                FB.login(function(response) {
                    if (response.authResponse) {
                        // User authorized the app
                        const accessToken = response.authResponse.accessToken;
                        const userID = response.authResponse.userID;
                        
                        @this.handleAuthResponse(accessToken, userID);
                    } else {
                        console.log('User cancelled login or did not fully authorize.');
                    }
                }, {
                    scope: 'email, public_profile, ads_management, ads_read, read_insights, business_management, whatsapp_business_management, whatsapp_business_messaging',
                    return_scopes: true
                });
            };
        }
    </script>
    @endscript
</div>
