<div class="px-8 md:px-0">
    <x-slot:title>
        {{ t('connect_waba') }}
    </x-slot:title>

    <div class="max-w-6xl md:flex md:items-center md:justify-between">
        <x-page-heading>
            {{ t('whatsapp_business_account') }}
        </x-page-heading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-8">
            {{-- Step - 1 : Facebook Developer Account & Facebook App --}}
            <div class="py-4">
                <x-card class="-mx-4 sm:-mx-0 rounded-md">
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-indigo-600 dark:text-slate-200">
                                {{ t('connect_with_facebook_step1') }}
                            </h3>
                        </div>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="flex flex-col gap-2 items-center">
                            <div class="w-full">
                                <x-label for="wm_fb_app_id" class="flex items-center justify-between space-x-1">
                                    <div class="flex items-center space-x-1">
                                        <span>
                                            <x-heroicon-o-question-mark-circle
                                                class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                                        </span>
                                        <span>{{ t('fb_app_id') }}</span>
                                    </div>
                                    <a href="https://developers.facebook.com/docs/whatsapp/solution-providers/get-started-for-tech-providers#step-2--create-a-meta-app"
                                        target="_blank"><span
                                            class="text-red-600 uppercase">{{ t('help') }}</span></a>
                                </x-label>
                                <x-input id="wm_fb_app_id" type="text" class="block w-full mt-1"
                                    wire:model="wm_fb_app_id" />
                                <x-input-error for="wm_fb_app_id" class="mt-2" />
                            </div>
                            <div class="w-full">
                                <x-label for="wm_fb_app_secret" :value="t('fb_app_secret')" />
                                <x-input id="wm_fb_app_secret" type="text" class="block w-full "
                                    wire:model="wm_fb_app_secret" />
                                <x-input-error for="wm_fb_app_secret" class="mt-2" />
                            </div>
                        </div>
                    </x-slot:content>
                    <x-slot:footer>
                        <div class="flex justify-end">
                            @if ($is_webhook_connected == 0)
                                <x-button.green wire:click="webhookConnect">
                                    <div class="flex justify-center items-center">
                                        <x-heroicon-o-link class="h-5 w-5 mr-1" wire:loading.remove
                                            wire:target="webhookConnect" />
                                        <x-heroicon-o-arrow-path wire:loading wire:target="webhookConnect"
                                            class="animate-spin w-4 h-5 mx-14" />
                                        <span wire:loading.remove
                                            wire:target="webhookConnect">{{ t('webhook') }}</span>
                                    </div>
                                </x-button.green>
                            @else
                                <x-button.danger wire:click="webhookDisconnect">
                                    <div class="flex justify-center items-center">
                                        <x-heroicon-o-x-mark class="h-5 w-5 mr-1" wire:loading.remove
                                            wire:target="webhookConnect" />
                                        <x-heroicon-o-arrow-path wire:loading wire:target="webhookDisconnect"
                                            class="animate-spin w-4 h-5 mx-14" />
                                        <span wire:loading.remove
                                            wire:target="webhookDisconnect">{{ t('disconnect_account') }}</span>
                                    </div>
                                </x-button.danger>
                            @endif
                        </div>
                    </x-slot:footer>
                </x-card>
            </div>

            {{-- Step - 2 : WhatsApp Integration Setup --}}
            <div class="py-4">
                <x-card class="-mx-4 sm:-mx-0 rounded-md">
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-indigo-600 dark:text-slate-200">
                                {{ t('wp_integration_step2') }}
                            </h3>
                        </div>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="flex flex-col gap-2 items-center">
                            <div class="w-full">
                                <x-label for="wm_business_account_id" class="flex items-center space-x-1">
                                    <span data-tippy-content="{{ t('wp_business_id') }}">
                                        <x-heroicon-o-question-mark-circle
                                            class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                                    </span>
                                    <span>{{ t('wp_business_id') }}</span>
                                </x-label>

                                <x-input id="wm_business_account_id" type="text" class="block w-full mt-1"
                                    wire:model="wm_business_account_id" />
                                <x-input-error for="wm_business_account_id" class="mt-2" />

                            </div>
                            <div class="w-full">
                                <x-label for="wm_access_token" class="flex items-center space-x-1">
                                    <span data-tippy-content="{{ t('user_access_token_info') }}">
                                        <x-heroicon-o-question-mark-circle
                                            class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                                    </span>
                                    <span>{{ t('wp_access_token') }}</span>
                                </x-label>
                                <div class="flex items-center space-x-1" x-data="{ wm_access_token: @entangle('wm_access_token') }">

                                    <x-input id="wm_access_token" type="text" class="block w-full mt-1"
                                        wire:model="wm_access_token" x-model="wm_access_token" />
                                    <a :href="`https://developers.facebook.com/tools/debug/accesstoken/?access_token=${wm_access_token}`"
                                        target="_blank">
                                        <x-button.ghost class="mt-1">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5 mr-1" />
                                            {{ t('debug_token') }}
                                        </x-button.ghost>
                                    </a>
                                </div>
                                <x-input-error for="wm_access_token" class="mt-2" />
                            </div>
                        </div>
                    </x-slot:content>
                    <x-slot:footer>
                        <div class="flex justify-end">
                            @if (checkPermission('connect_account.connect'))
                                <x-button.green wire:click="connectAccount">
                                    <span wire:loading.remove wire:target="connectAccount">
                                        <x-heroicon-o-link class="h-5 w-5 mr-1 inline-block" />
                                        {{ t('config') }}
                                    </span>
                                    <div wire:loading wire:target="connectAccount" class="min-w-20">
                                        <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 ms-7" />
                                    </div>
                                </x-button.green>
                            @endif
                        </div>
                    </x-slot:footer>
                </x-card>
            </div>
        </div>

        <div class="md:col-span-4">
            <div class="py-4">
                <x-card class="-mx-4 sm:-mx-0 rounded-md">
                    <x-slot:header>
                        <h3 class="text-lg leading-6 font-medium text-indigo-600 dark:text-slate-200">
                            Connection Requirements
                        </h3>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="space-y-4">
                            <div class="border-l-4 border-indigo-500 pl-4 py-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    You will require the following information to activate your WhatsApp Business
                                    Cloud API:
                                </p>
                            </div>

                            <ul class="space-y-3">
                                <li class="flex">
                                    <div
                                        class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-3">
                                        1
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">Valid Mobile
                                            Number</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">A phone number that will be
                                            registered on Meta.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div
                                        class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-3">
                                        2
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">Facebook
                                            Developer Account</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Register on Facebook for
                                            Developers, create a Business App, and add the WhatsApp product.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div
                                        class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-3">
                                        3
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">WhatsApp
                                            Business Profile</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Add a phone number, verify
                                            it, and enable live mode.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div
                                        class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-3">
                                        4
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">System User &
                                            Access Token</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Create a system user,
                                            assign permissions, and generate a permanent access token.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div
                                        class="flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 mr-3">
                                        5
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">Verify Your
                                            Setup</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Use our WhatsApp Cloud API
                                            debug tool to check if everything is configured correctly.</p>
                                    </div>
                                </li>
                            </ul>

                            <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-md">
                                <h4 class="flex items-center text-sm font-medium text-blue-800 dark:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Need Help?
                                </h4>
                                <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                    For detailed instructions, visit the <a
                                        href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started"
                                        class="font-medium underline" target="_blank"
                                        rel="noopener noreferrer">WhatsApp Cloud API Documentation</a>
                                </p>
                            </div>

                            @if (isset($is_whatsmark_connected) && !$is_whatsmark_connected)
                                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-md">
                                    <h4
                                        class="flex items-center text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Connection Status
                                    </h4>
                                    <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-300">
                                        Your WhatsApp Business API is not connected. Complete the steps above to
                                        establish a connection.
                                    </p>
                                </div>
                            @endif

                            @if (isset($wm_default_phone_number) && $wm_default_phone_number)
                                <div
                                    class="mt-6 p-4 flex flex-col items-center border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Scan to
                                        Connect with WhatsApp</h3>

                                    <div class="bg-white p-2 rounded-lg">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://wa.me/{{ preg_replace('/\D/', '', $wm_default_phone_number) }}"
                                            alt="WhatsApp QR Code" class="w-48 h-48" />
                                    </div>

                                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        Scan this QR code to quickly connect with your WhatsApp Business account
                                    </p>
                                </div>
                            @endif
                        </div>
                    </x-slot:content>
                </x-card>
            </div>
        </div>
    </div>
</div>
