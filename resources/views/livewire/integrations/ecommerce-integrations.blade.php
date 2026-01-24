<div class="py-12">
    <x-banner />
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

            <!-- Meta Commerce -->
            <div
                class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center">
                <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 text-blue-600">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg">Meta Commerce</h3>
                <p class="text-gray-500 text-sm mb-4">Sync products to Facebook/Instagram/WhatsApp Catalogs.</p>
                <button wire:click="openConnectModal('meta_commerce')"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 w-full">Connect Meta</button>
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
                                    @if($integration->type == 'meta_commerce' || $integration->type == 'facebook')
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
                                        </svg>
                                    @elseif($integration->type == 'shopify')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    @elseif($integration->type == 'woocommerce')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                            </path>
                                        </svg>
                                    @else
                                        <span class="font-bold text-xs">{{ strtoupper(substr($integration->type, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $integration->name }}</h4>
                                    <div class="flex items-center text-sm text-gray-500 space-x-2">
                                        <span class="capitalize">{{ $integration->type }}</span>
                                        <span>&bull;</span>
                                        @php
                                            $normalizedStatus = strtoupper($integration->status);
                                            $healthColor = match ($normalizedStatus) {
                                                'READY', 'ACTIVE' => 'text-green-600',
                                                'DEGRADED', 'READY_WARNING' => 'text-amber-600',
                                                'BROKEN', 'SUSPENDED', 'TOKEN_EXPIRED', 'ERROR', 'SUSPENDED_UPPER' => 'text-red-600',
                                                default => 'text-gray-600'
                                            };
                                        @endphp
                                        <span class="{{ $healthColor }} font-medium flex items-center">
                                            <span class="w-2 h-2 rounded-full bg-current mr-1.5 animate-pulse"></span>
                                            {{ ucfirst(str_replace('_', ' ', $integration->status)) }}
                                            @if(isset($integration->health_score))
                                                <span
                                                    class="ml-2 text-xs font-bold px-1.5 py-0.5 bg-gray-100 rounded text-gray-600">
                                                    {{ $integration->health_score }}/100
                                                </span>
                                            @endif
                                        </span>
                                        @if($integration->last_synced_at)
                                            <span>&bull;</span>
                                            <span>Last synced
                                                {{ $integration->last_synced_at instanceof \Carbon\Carbon ? $integration->last_synced_at->diffForHumans() : 'Recently' }}</span>
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
                                    @elseif($integration->type == 'meta_commerce')
                                        <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                                            <strong>Catalog ID:</strong><br>
                                            <code
                                                class="text-blue-600 block my-1 bg-white p-1 border rounded">{{ $integration->credentials['catalog_id'] ?? 'N/A' }}</code>

                                            <strong>Webhook Verification URL:</strong><br>
                                            <code
                                                class="text-gray-600 block my-1 bg-white p-1 border rounded">{{ url('/api/webhooks/meta/commerce') }}</code>
                                        </div>
                                    @endif

                                    @if($integration->error_message)
                                        <p class="text-red-500 text-xs mt-1">{{ $integration->error_message }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="openSettings({{ $integration->id }})"
                                    class="p-2 text-gray-400 hover:text-blue-600" title="Settings">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                                <button wire:click="openDiagnostics({{ $integration->id }})"
                                    class="p-2 text-gray-400 hover:text-emerald-600" title="Diagnostics & Logs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                        </path>
                                    </svg>
                                </button>
                                @if($integration->type !== 'custom')
                                    <button wire:click="sync({{ $integration->id }})" class="p-2 text-gray-400 hover:text-gray-600"
                                        title="Sync Now" wire:loading.attr="disabled" wire:target="sync">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            wire:loading.class="animate-spin" wire:target="sync">
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

        <!-- Diagnostics Modal -->
        <x-dialog-modal wire:model="showDiagnosticsModal" maxWidth="4xl">
            <x-slot name="title">
                Integration Diagnostics: {{ $activeIntegration?->name }}
            </x-slot>

            <x-slot name="content">
                @if($activeIntegration)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Health Panel -->
                        <div class="md:col-span-1 space-y-4">
                            <div class="bg-gray-50 p-4 rounded-xl text-center">
                                <div
                                    class="text-3xl font-bold {{ $healthData['score'] > 80 ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $healthData['score'] }}/100
                                </div>
                                <div class="text-xs text-gray-500 uppercase font-bold tracking-wider mt-1">Health Score
                                </div>
                            </div>

                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-gray-700">Health Issues</h4>
                                @forelse($healthData['issues'] ?? [] as $issue)
                                    <div class="text-xs bg-red-50 text-red-700 p-2 rounded flex items-start">
                                        <svg class="w-3.5 h-3.5 mr-1.5 mt-0.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                            </path>
                                        </svg>
                                        {{ $issue }}
                                    </div>
                                @empty
                                    <div class="text-xs text-green-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        No critical issues detected.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Sync Timeline -->
                        <div class="md:col-span-2 space-y-4">
                            <h4 class="text-sm font-bold text-gray-700">Recent Sync Timeline</h4>
                            <div class="space-y-3">
                                @foreach($syncSessions as $session)
                                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="mt-1">
                                            @if($session->status == 'completed')
                                                <div class="bg-green-100 text-green-600 p-1 rounded-full">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            @elseif($session->status == 'partially_failed')
                                                <div class="bg-amber-100 text-amber-600 p-1 rounded-full">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="bg-red-100 text-red-600 p-1 rounded-full">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-center">
                                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($session->type) }}
                                                    Sync
                                                </p>
                                                <span
                                                    class="text-xs text-gray-500">{{ $session->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                Processed: <span
                                                    class="text-green-600 font-bold">{{ $session->processed_entities }}</span>
                                                &bull; Failed: <span
                                                    class="text-red-600 font-bold">{{ $session->failed_entities }}</span>
                                            </p>
                                            @if($session->error_summary)
                                                <div
                                                    class="mt-2 text-[10px] text-red-600 bg-red-50 p-2 rounded whitespace-pre-wrap max-h-24 overflow-y-auto">
                                                    @foreach(array_slice($session->error_summary, 0, 3) as $error)
                                                        • {{ $error['id'] }}: {{ $error['error'] }}<br>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showDiagnosticsModal', false)">
                    Close
                </x-secondary-button>
            </x-slot>
        </x-dialog-modal>

        <!-- Settings Modal -->
        <x-dialog-modal wire:model="showSettingsModal">
            <x-slot name="title">
                Sync Settings: {{ $activeIntegration?->name }}
            </x-slot>

            <x-slot name="content">
                @if($activeIntegration)
                    <div class="space-y-6">
                        <!-- Scope Selection -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-3">Sync Scope</h4>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" wire:model.live="sync_scope.product_mode" value="all"
                                        class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">All Active Products</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" wire:model.live="sync_scope.product_mode" value="selective"
                                        class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Selective Collection/Category</span>
                                </label>
                            </div>

                            @if($sync_scope['product_mode'] == 'selective')
                                <div class="mt-3 pl-6">
                                    @if($activeIntegration->type == 'shopify')
                                        <x-label for="col_id" value="Shopify Collection ID" />
                                        <x-input id="col_id" type="text" class="mt-1 block w-full text-sm"
                                            wire:model="sync_scope.collection_id" placeholder="e.g. 456789012" />
                                    @else
                                        <x-label for="cat_id" value="WooCommerce Category ID" />
                                        <x-input id="cat_id" type="text" class="mt-1 block w-full text-sm"
                                            wire:model="sync_scope.category_id" placeholder="e.g. 12" />
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Feature Toggles -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-3">Authority Settings</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" wire:model="sync_scope.inventory.sync_stock"
                                        class="rounded text-indigo-600">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium">Sync Stock</div>
                                        <div class="text-[10px] text-gray-500">Import inventory levels</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" wire:model="sync_scope.inventory.sync_price"
                                        class="rounded text-indigo-600">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium">Sync Price</div>
                                        <div class="text-[10px] text-gray-500">Overwrites local pricing</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Webhook Security -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-2">Security</h4>
                            <x-label for="wh_secret" value="Webhook Secret (HMAC Verification)" />
                            <x-input id="wh_secret" type="password" class="mt-1 block w-full text-sm"
                                wire:model="webhook_secret" placeholder="Enter secret for signature checking" />
                            <p class="text-[10px] text-gray-500 mt-1">Found in Shopify/WC Webhook settings.</p>
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showSettingsModal', false)">
                    Cancel
                </x-secondary-button>
                <x-button class="ml-3" wire:click="saveSettings">
                    Save Config
                </x-button>
            </x-slot>
        </x-dialog-modal>

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

                    @if($selectedType == 'meta_commerce')
                        <div>
                            <x-label for="meta_catalog_id" value="Meta Catalog ID" />
                            <x-input id="meta_catalog_id" type="text" class="mt-1 block w-full" wire:model="meta_catalog_id"
                                placeholder="1234567890..." />
                            <x-input-error for="meta_catalog_id" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="meta_access_token" value="Meta System User Access Token" />
                            <x-input id="meta_access_token" type="password" class="mt-1 block w-full"
                                wire:model="meta_access_token" placeholder="EAAG..." />
                            <x-input-error for="meta_access_token" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">Obtain this from Meta Business Suite -> Settings -> System
                                Users.</p>
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