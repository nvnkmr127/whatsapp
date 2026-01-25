<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Launch Offer Configuration
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">
                        Design the free trial experience for new signups. Changes here apply immediately to all user in
                        'trial' status.
                    </p>
                </div>
            </div>

            <x-action-message class="mb-4" on="saved">
                {{ __('Settings Saved!') }}
            </x-action-message>

            @if (session()->has('message'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('message') }}
                </div>
            @endif

            <form wire:submit.prevent="save">
                <!-- Toggle Offer -->
                <div class="mb-8 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-indigo-900 dark:text-indigo-100">Enable Launch Offer</h3>
                        <p class="text-indigo-700 dark:text-indigo-300 text-sm">When enabled, new registrations get this
                            trial instead of the Basic plan.</p>
                    </div>
                    <div class="flex items-center">
                        <x-label for="offerEnabled" class="mr-3">{{ __('Enabled') }}</x-label>
                        <x-checkbox id="offerEnabled" wire:model="offerEnabled" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Limits & Configuration -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Trial Limits</h3>

                        <div class="space-y-4">
                            <div>
                                <x-label for="trialMonths" value="{{ __('Trial Duration (Months)') }}" />
                                <x-input id="trialMonths" type="number" class="mt-1 block w-full"
                                    wire:model="trialMonths" min="1" max="24" />
                                <x-input-error for="trialMonths" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="messageLimit" value="{{ __('Monthly Message Limit') }}" />
                                <x-input id="messageLimit" type="number" class="mt-1 block w-full"
                                    wire:model="messageLimit" />
                                <p class="text-xs text-gray-500 mt-1">Free message allowance per month.</p>
                                <x-input-error for="messageLimit" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="agentLimit" value="{{ __('Agent Seat Limit') }}" />
                                <x-input id="agentLimit" type="number" class="mt-1 block w-full"
                                    wire:model="agentLimit" />
                                <x-input-error for="agentLimit" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="whatsappLimit" value="{{ __('WhatsApp Accounts Limit') }}" />
                                <x-input id="whatsappLimit" type="number" class="mt-1 block w-full"
                                    wire:model="whatsappLimit" />
                                <x-input-error for="whatsappLimit" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="initialCredit" value="{{ __('Launch Gift Credit ($)') }}" />
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <x-input id="initialCredit" type="number" step="0.01" class="pl-7 block w-full"
                                        wire:model="initialCredit" />
                                </div>
                                <p class="text-xs text-gray-500 mt-1">One-time wallet balance given on signup.</p>
                                <x-input-error for="initialCredit" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Feature Access -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Included Features</h3>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-3">
                            @foreach($availableFeatures as $key => $label)
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" value="{{ $key }}" wire:model="includedFeatures"
                                        class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Unchecked features will be hidden/locked for trial users.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-8">
                    <x-button class="ml-4">
                        {{ __('Save Offer Settings') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>