<div>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Business Behavior</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Configure automated responses and operating hours for your WhatsApp Business account.
                </p>

                <div class="mt-4 text-xs text-gray-500">
                    To manage your connection credentials, Webhook, or Profile, please visit the <a
                        href="{{ route('teams.whatsapp_config') }}"
                        class="text-indigo-600 hover:underline">Configuration</a>
                    page.
                </div>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="shadow sm:rounded-md sm:overflow-hidden">
                <div class="px-4 py-5 bg-white dark:bg-gray-800 space-y-6 sm:p-6">

                    <!-- Timezone -->
                    <div class="col-span-6 sm:col-span-4">
                        <label for="timezone"
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">Timezone</label>
                        <select id="timezone" wire:model="timezone"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}">{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4"></div>

                    <!-- Business Settings Form -->
                    <div class="col-span-6 sm:col-span-4">
                        <div class="block font-medium text-sm text-gray-700 dark:text-gray-300">Business Hours (Mon-Fri)
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <input type="time" wire:model="openTime"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <span class="self-center text-sm text-gray-500 dark:text-gray-400">to</span>
                            <input type="time" wire:model="closeTime"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Messages received outside these hours may trigger the Away
                            Message.</p>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4"></div>

                    <div class="col-span-6 sm:col-span-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="awayMessageEnabled"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Enable Away Message</span>
                        </label>
                    </div>

                    @if($awayMessageEnabled)
                        <div class="col-span-6 sm:col-span-4 mt-2">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Away Message
                                Content</label>
                            <textarea wire:model="awayMessage" rows="3"
                                class="mt-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                    @endif
                </div>

                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6">
                    <button wire:click="save"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Behavior Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>