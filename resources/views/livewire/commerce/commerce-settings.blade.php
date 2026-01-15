<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-form-section submit="save">
            <x-slot name="title">
                Store Configuration
            </x-slot>

            <x-slot name="description">
                Configure your store behavior, checkout rules, and currency.
            </x-slot>

            <x-slot name="form">
                <!-- Currency -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="currency" value="Store Currency" />
                    <x-input id="currency" type="text" class="mt-1 block w-full" wire:model="currency" placeholder="USD"
                        maxlength="3" />
                    <x-input-error for="currency" class="mt-2" />
                </div>

                <!-- Guest Checkout -->
                <div class="col-span-6 sm:col-span-4">
                    <label class="flex items-center">
                        <x-checkbox wire:model="allow_guest_checkout" />
                        <span class="ms-2 text-sm text-gray-600">Allow Guest Checkout</span>
                    </label>
                </div>

                <!-- COD -->
                <div class="col-span-6 sm:col-span-4">
                    <label class="flex items-center">
                        <x-checkbox wire:model="cod_enabled" />
                        <span class="ms-2 text-sm text-gray-600">Enable Cash on Delivery (COD)</span>
                    </label>
                </div>

                <!-- Min Order -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="min_order_value" value="Minimum Order Value" />
                    <x-input id="min_order_value" type="number" step="0.01" class="mt-1 block w-full"
                        wire:model="min_order_value" />
                    <p class="text-xs text-gray-500 mt-1">Minimum cart total required to checkout.</p>
                </div>

                <div class="border-t border-gray-200 my-4"></div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">Cart Engine Configuration</h3>

                <!-- Cart Expiry -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="cart_expiry_minutes" value="Cart Expiry (Minutes)" />
                    <x-input id="cart_expiry_minutes" type="number" class="mt-1 block w-full"
                        wire:model="cart_expiry_minutes" placeholder="60" />
                    <p class="text-xs text-gray-500 mt-1">Carts will be considered abandoned after this time.</p>
                </div>

                <!-- Cart Reminder -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="cart_reminder_minutes" value="Reminder Delay (Minutes)" />
                    <x-input id="cart_reminder_minutes" type="number" class="mt-1 block w-full"
                        wire:model="cart_reminder_minutes" placeholder="30" />
                    <p class="text-xs text-gray-500 mt-1">Send a recovery message this many minutes *after* abandonment.
                    </p>
                </div>

                <!-- Merge Strategy -->
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="cart_merge_strategy" value="Multi-Session Behavior" />
                    <select id="cart_merge_strategy" wire:model="cart_merge_strategy"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="merge">Merge Carts (Combine Items)</option>
                        <option value="replace">Use Newest (Disable Old)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">What happens when a user opens a new cart in a different
                        campaign?</p>
                </div>
            </x-slot>
        </x-form-section>

        <div class="hidden sm:block">
            <div class="py-8">
                <div class="border-t border-gray-200"></div>
            </div>
        </div>

        <x-form-section submit="save">
            <x-slot name="title">
                Order Notifications
            </x-slot>

            <x-slot name="description">
                Map your WhatsApp Templates to order lifecycle events. Only Utility/Transactional templates are allowed.
            </x-slot>

            <x-slot name="form">
                @foreach($notifications as $status => $template)
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="template_{{ $status }}" value="Order {{ ucfirst(str_replace('_', ' ', $status)) }}" />
                        <select id="template_{{ $status }}" wire:model="notifications.{{ $status }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                            <option value="">Select a Template...</option>
                            @foreach($availableTemplates as $t)
                                <option value="{{ $t->name }}">{{ $t->name }} ({{ $t->language }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Triggered when order status becomes '{{ $status }}'.</p>
                        <div class="mt-1 text-xs text-gray-400 bg-gray-50 p-2 rounded border border-gray-100">
                            <strong>Expected Variables:</strong><br>
                            @if($status == 'shipped')
                                <span>@{{1}} Order ID, @{{2}} Tracking Link/Number</span>
                            @elseif($status == 'placed')
                                <span>@{{1}} Order ID, @{{2}} Total Amount</span>
                            @else
                                <span>@{{1}} Order ID</span>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="col-span-6 border-t border-gray-100 my-4"></div>

                <h3 class="col-span-6 text-sm font-medium text-gray-900 mb-2">Internal Agent Alerts</h3>
                <p class="col-span-6 text-xs text-gray-500 mb-4">Notify team members when these events occur.</p>

                @foreach(['placed', 'payment_failed', 'cancelled', 'returned'] as $status)
                    <div class="col-span-6 sm:col-span-4">
                        <label class="flex items-center">
                            <x-checkbox wire:model="agent_notifications.{{ $status }}" />
                            <span class="ms-2 text-sm text-gray-600">Alert when order is
                                <strong>{{ ucfirst($status) }}</strong></span>
                        </label>
                    </div>
                @endforeach

                <div class="col-span-6 border-t border-gray-100 my-4"></div>

                <h3 class="col-span-6 text-sm font-medium text-gray-900 mb-2">Shop-by-Chat AI Assistant</h3>
                <p class="col-span-6 text-xs text-gray-500 mb-4">Allow customers to shop by chatting with an AI agent.
                </p>

                <!-- Enable AI -->
                <div class="col-span-6 sm:col-span-4">
                    <label class="flex items-center">
                        <x-checkbox wire:model="ai_assistant_enabled" />
                        <span class="ms-2 text-sm text-gray-600">Enable AI Recommendation Agent (Shop-by-Chat)</span>
                    </label>
                    <p class="mt-2 text-xs text-gray-500">
                        Configure AI credentials and personas in
                        <a href="{{ route('settings.ai') }}"
                            class="text-indigo-600 hover:text-indigo-900 font-medium underline">AI Global Settings</a>.
                    </p>
                </div>
            </x-slot>

            <x-slot name="actions">
                <x-action-message class="me-3" on="saved">
                    Saved.
                </x-action-message>

                <x-button>
                    Save Configuration
                </x-button>
            </x-slot>
        </x-form-section>
    </div>
</div>