<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">WhatsApp Credentials</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Enter your Meta Cloud API details. You can find these in your Meta Developer App dashboard.
            </p>

            <div
                class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-md border border-yellow-200 dark:border-yellow-700">
                <h4 class="text-xs font-semibold uppercase text-yellow-800 dark:text-yellow-200">Webhook Configuration
                </h4>
                <p class="text-xs text-gray-700 dark:text-gray-300 mt-2 break-all">
                    <strong>Callback URL:</strong><br>
                    {{ route('api.webhook.whatsapp') }} // (Make sure this is publicly accessible)
                </p>
                <p class="text-xs text-gray-700 dark:text-gray-300 mt-2">
                    <strong>Verify Token:</strong><br>
                    {{ config('services.whatsapp.verify_token') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="shadow sm:rounded-md sm:overflow-hidden">
            <div class="px-4 py-5 bg-white dark:bg-gray-800 space-y-6 sm:p-6">

                <!-- Phone Number ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number ID</label>
                    <input wire:model="phoneNumberId" type="text"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                    @error('phoneNumberId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- WABA ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp Business Account
                        ID</label>
                    <input wire:model="wabaId" type="text"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                    @error('wabaId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Access Token -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permanent Access
                        Token</label>
                    <textarea wire:model="accessToken" rows="3"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Recommended: Use a System User token with
                        'whatsapp_business_messaging' permission.</p>
                    @error('accessToken') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Business Settings Form -->
                <div class="col-span-6 sm:col-span-4 mt-4">
                    <div class="block font-medium text-sm text-gray-700 dark:text-gray-300">Business Hours (Mon-Fri)
                    </div>
                    <div class="flex space-x-2 mt-1">
                        <input type="time" wire:model="openTime"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <span class="self-center dark:text-gray-300">to</span>
                        <input type="time" wire:model="closeTime"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="col-span-6 sm:col-span-4 mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="awayMessageEnabled"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Enable Away Message</span>
                    </label>
                </div>

                @if($awayMessageEnabled)
                    <div class="col-span-6 sm:col-span-4 mt-2">
                        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Away Message</label>
                        <textarea wire:model="awayMessage" rows="3"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                @endif

                <div class="col-span-6 sm:col-span-4 mt-4">
                    <label for="timezone"
                        class="block font-medium text-sm text-gray-700 dark:text-gray-300">Timezone</label>
                    <select id="timezone" wire:model="timezone"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="UTC">UTC</option>
                        <option value="Asia/Kolkata">Asia/Kolkata</option>
                        <option value="America/New_York">America/New_York</option>
                    </select>
                </div>
                <!-- End Business Settings Form -->

                <!-- Status Feedback -->
                @if($connectionStatus === 'success')
                    <div class="p-4 bg-green-50 text-green-700 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Connected Successfully!
                    </div>
                @elseif($connectionStatus === 'failed')
                    <div class="p-4 bg-red-50 text-red-700 rounded-md">
                        <strong>Connection Failed:</strong> {{ $errorMessage }}
                    </div>
                @endif
            </div>

            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6">
                <button wire:click="save"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save & Test Connection
                </button>
            </div>
        </div>
    </div>
</div>