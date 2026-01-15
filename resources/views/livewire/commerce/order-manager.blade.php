<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header & Breadcrumb -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-4">
                        <li>
                            <div>
                                <a href="{{ route('commerce.dashboard') }}" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">Dashboard</span>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20"
                                    aria-hidden="true">
                                    <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                                </svg>
                                <span class="ml-4 text-sm font-medium text-gray-500 transition-colors">Orders</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Order Management
                </h2>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input type="text" class="w-full" placeholder="Search by Order ID or Customer..."
                    wire:model.live.debounce.300ms="search" />
            </div>
            <div class="w-full sm:w-48">
                <select wire:model.live="statusFilter"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total</th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orders as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $order->order_id ?? $order->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <div class="ml-0">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $order->contact->name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $order->contact->phone_number ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($order->status === 'paid' || $order->status === 'delivered') bg-green-100 text-green-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-blue-100 text-blue-800 @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                    {{ $order->total_amount }} {{ $order->currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="viewDetails({{ $order->id }})"
                                        class="text-indigo-600 hover:text-indigo-900">View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        </div>

        <!-- Order Details Modal -->
        <x-dialog-modal wire:model.live="showDetailsModal">
            <x-slot name="title">
                Order Details #{{ $viewingOrder->order_id ?? $viewingOrder->id ?? '' }}
            </x-slot>

            <x-slot name="content">
                @if($viewingOrder)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Customer Info</h4>
                            <p class="text-sm text-gray-600">{{ $viewingOrder->contact->name ?? 'Guest' }}</p>
                            <p class="text-sm text-gray-600">{{ $viewingOrder->contact->phone_number ?? '' }}</p>

                            <h4 class="font-semibold text-gray-900 mt-4 mb-2">Payment Details</h4>
                            <!-- Displaying payment details dynamically since structure might vary -->
                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded">
                                @if(is_array($viewingOrder->payment_details))
                                    @foreach($viewingOrder->payment_details as $key => $value)
                                        <div class="flex justify-between">
                                            <span class="capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                            <span class="font-medium">{{ is_string($value) ? $value : json_encode($value) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    No payment details available.
                                @endif
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Order Items</h4>
                            <div class="border rounded divide-y">
                                @if(is_array($viewingOrder->items))
                                    @foreach($viewingOrder->items as $item)
                                        <div class="p-3 text-sm flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $item['product_name'] ?? 'Item' }}</div>
                                                <div class="text-gray-500">Qty: {{ $item['quantity'] ?? 1 }}</div>
                                            </div>
                                            <div class="font-medium">
                                                {{ $item['price'] ?? 0 }} {{ $viewingOrder->currency }}
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="mt-3 flex justify-between font-bold text-gray-900">
                                <span>Total</span>
                                <span>{{ $viewingOrder->total_amount }} {{ $viewingOrder->currency }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700">Update Status</label>
                        <div class="mt-2 flex gap-4">
                            <select wire:model="newStatus"
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="returned">Returned</option>
                            </select>
                            <button wire:click="updateStatus"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Update</button>
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showDetailsModal', false)" wire:loading.attr="disabled">
                    Close
                </x-secondary-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>