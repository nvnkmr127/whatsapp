<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">E-commerce Integrations</h2>
                <p class="text-gray-600">Connect your external stores to sync products and manage orders.</p>
            </div>
            <div>
                <!-- Actions if needed -->
            </div>
        </div>

        <!-- Available Integrations Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <!-- Shopify -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center">
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg">Shopify</h3>
                <p class="text-gray-500 text-sm mb-4">Sync products and orders from your Shopify store.</p>
                <button wire:click="openConnectModal('shopify')"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 w-full">Connect
                    Shopify</button>
            </div>

            <!-- WooCommerce -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center">
                <div class="h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg">WooCommerce</h3>
                <p class="text-gray-500 text-sm mb-4">Connect your WordPress WooCommerce store.</p>
                <button wire:click="openConnectModal('woocommerce')"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 w-full">Connect
                    WooCommerce</button>
            </div>

            <!-- Custom API -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center">
                <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg">Custom Site (API)</h3>
                <p class="text-gray-500 text-sm mb-4">Push products via API from any custom website.</p>
                <button wire:click="openConnectModal('custom')"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 w-full">Generate API
                    Key</button>
            </div>
        </div>

        <!-- Active Connections -->
        @if($integrations->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-lg">Active Connections</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($integrations as $integration)
                        <div class="p-6 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="h-10 w-10 rounded-full flex items-center justify-center {{ $integration->type == 'shopify' ? 'bg-green-100 text-green-600' : ($integration->type == 'woocommerce' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600') }}">
                                    <!-- Icon based on type -->
                                    <span class="font-bold text-xs">{{ strtoupper(substr($integration->type, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $integration->name }}</h4>
                                    <div class="flex items-center text-sm text-gray-500 space-x-2">
                                        <span class="capitalize">{{ $integration->type }}</span>
                                        <span>&bull;</span>
                                        <span
                                            class="{{ $integration->status == 'active' ? 'text-green-600' : 'text-red-600' }}">{{ ucfirst($integration->status) }}</span>
                                        @if($integration->last_synced_at)
                                            <span>&bull;</span>
                                            <span>Last synced {{ $integration->last_synced_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    @if($integration->type == 'custom')
                                        <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                                            <strong>API Key (Header: X-Integration-Token):</strong><br>
                                            <code
                                                class="text-blue-600 block my-1 bg-white p-1 border rounded">{{ $integration->credentials['api_key'] ?? 'N/A' }}</code>

                                            <strong>Order Webhook Endpoint:</strong><br>
                                            <code
                                                class="text-gray-600 block my-1 bg-white p-1 border rounded">{{ url('/api/v1/webhooks/custom/orders') }}</code>
                                        </div>
                                    @elseif($integration->type == 'shopify')
                                        <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                                            <strong>Webhook URL (Topic: orders/updated):</strong><br>
                                            <code
                                                class="text-gray-600 block my-1 bg-white p-1 border rounded">{{ url('/api/v1/webhooks/shopify/orders') }}</code>
                                            <span class="text-gray-500">Shop Domain:
                                                {{ $integration->credentials['domain'] ?? 'N/A' }}</span>
                                        </div>
                                    @elseif($integration->type == 'woocommerce')
                                        <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                                            <strong>Webhook URL (Topic: Order updated):</strong><br>
                                            <code
                                                class="text-gray-600 block my-1 bg-white p-1 border rounded">{{ url('/api/v1/webhooks/woocommerce/orders') }}</code>
                                            <span class="text-gray-500">Ensure Store URL matches:
                                                {{ $integration->credentials['url'] ?? 'N/A' }}</span>
                                        </div>
                                    @endif

                                    @if($integration->error_message)
                                        <p class="text-red-500 text-xs mt-1">{{ $integration->error_message }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($integration->type !== 'custom')
                                    <button wire:click="sync({{ $integration->id }})" class="p-2 text-gray-400 hover:text-gray-600"
                                        title="Sync Now">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="delete({{ $integration->id }})" class="p-2 text-gray-400 hover:text-red-600"
                                    title="Disconnect" wire:confirm="Are you sure you want to disconnect?">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Connect Modal -->
        <x-dialog-modal wire:model="showConnectModal">
            <x-slot name="title">
                Connect {{ ucfirst($this->selectedType) }}
            </x-slot>

            <x-slot name="content">
                <div class="space-y-4">
                    <div>
                        <x-label for="name" value="Integration Name" />
                        <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name"
                            placeholder="My Store" />
                        <x-input-error for="name" class="mt-2" />
                    </div>

                    @if($selectedType == 'shopify')
                        <div>
                            <x-label for="domain" value="Shopify Domain" />
                            <x-input id="domain" type="text" class="mt-1 block w-full" wire:model="domain"
                                placeholder="example.myshopify.com" />
                            <x-input-error for="domain" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="access_token" value="Admin API Access Token" />
                            <x-input id="access_token" type="password" class="mt-1 block w-full" wire:model="access_token"
                                placeholder="shpat_..." />
                            <x-input-error for="access_token" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">Create a Custom App in Shopify Settings -> Apps -> Develop
                                Apps to get this token.</p>
                        </div>
                    @endif

                    @if($selectedType == 'woocommerce')
                        <div>
                            <x-label for="url" value="Store URL" />
                            <x-input id="url" type="url" class="mt-1 block w-full" wire:model="url"
                                placeholder="https://mystore.com" />
                            <x-input-error for="url" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="consumer_key" value="Consumer Key" />
                            <x-input id="consumer_key" type="text" class="mt-1 block w-full" wire:model="consumer_key"
                                placeholder="ck_..." />
                            <x-input-error for="consumer_key" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="consumer_secret" value="Consumer Secret" />
                            <x-input id="consumer_secret" type="password" class="mt-1 block w-full"
                                wire:model="consumer_secret" placeholder="cs_..." />
                            <x-input-error for="consumer_secret" class="mt-2" />
                        </div>
                    @endif

                    @if($selectedType == 'custom')
                        <div class="bg-gray-50 p-4 rounded text-sm text-gray-600">
                            We will generate a unique API Key for you. You can use this key to push products to our API
                            endpoint.
                        </div>
                    @endif
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showConnectModal', false)" wire:loading.attr="disabled">
                    Cancel
                </x-secondary-button>

                <x-button class="ms-3" wire:click="connect" wire:loading.attr="disabled">
                    {{ $selectedType == 'custom' ? 'Generate Key' : 'Connect' }}
                </x-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>