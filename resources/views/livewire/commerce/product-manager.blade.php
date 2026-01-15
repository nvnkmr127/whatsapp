<div class="h-full flex flex-col space-y-8 animate-in fade-in duration-500">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                Product Catalog
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg font-medium">
                Manage your inventory and sync with WhatsApp Shop.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-wa-green transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="pl-10 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-2xl shadow-sm ring-1 ring-slate-200 dark:ring-slate-800 focus:ring-2 focus:ring-wa-green/50 w-64 transition-all"
                    placeholder="Search Products...">
            </div>

            <button wire:click="create"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Add Product</span>
            </button>
        </div>
    </div>

    <!-- Content Grid -->
    @if($products->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($products as $product)
                <div
                    class="group relative bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-green/30 transition-all duration-300 flex flex-col h-full hover:-translate-y-1">

                    <!-- Image -->
                    <div
                        class="aspect-square bg-slate-50 dark:bg-slate-800 rounded-[1.5rem] mb-5 overflow-hidden relative shadow-inner">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-slate-300 dark:text-slate-600">
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="text-xs font-bold uppercase tracking-wider">No Image</span>
                            </div>
                        @endif

                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            @if($product->meta_product_id)
                                <div
                                    class="bg-wa-green/90 backdrop-blur text-slate-900 text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                                        </path>
                                    </svg>
                                    Synced
                                </div>
                            @else
                                <div
                                    class="bg-slate-200/90 dark:bg-slate-700/90 backdrop-blur text-slate-500 dark:text-slate-300 text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded-lg shadow-sm">
                                    Local Only
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white leading-tight line-clamp-2"
                                title="{{ $product->name }}">
                                {{ $product->name }}
                            </h3>
                        </div>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 min-h-[2.5rem]">
                            {{ $product->description ?? 'No description provided.' }}
                        </p>

                        <div
                            class="mt-auto flex items-end justify-between border-t border-slate-100 dark:border-slate-800 pt-4">
                            <div>
                                <span
                                    class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Price</span>
                                <span class="text-xl font-black text-slate-900 dark:text-wa-light">{{ $product->currency }}
                                    {{ number_format($product->price, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">SKU</span>
                                <span
                                    class="text-sm font-mono font-bold text-slate-600 dark:text-slate-300">{{ $product->retailer_id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Overlay -->
                    <div
                        class="absolute inset-x-0 bottom-0 p-4 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-4 group-hover:translate-y-0">
                        <div
                            class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl shadow-2xl p-2 flex items-center justify-between border border-slate-100 dark:border-slate-700">
                            <button wire:click="edit({{ $product->id }})"
                                class="p-2 text-slate-600 hover:text-wa-teal dark:text-slate-300 dark:hover:text-wa-light transition-colors"
                                title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </button>
                            <button wire:click="sync({{ $product->id }})"
                                class="p-2 text-slate-600 hover:text-blue-500 dark:text-slate-300 dark:hover:text-blue-400 transition-colors"
                                title="Sync to Meta">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                            </button>
                            <button wire:click="delete({{ $product->id }})" wire:confirm="Are you sure?"
                                class="p-2 text-slate-600 hover:text-red-500 dark:text-slate-300 dark:hover:text-red-400 transition-colors"
                                title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @else
        <div
            class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
            <div
                class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No products found</h3>
            <p class="text-slate-500 text-center max-w-sm mb-6">Start building your catalog by adding your first product.
            </p>
            <button wire:click="create" class="text-wa-green font-bold hover:underline">Add New Product &rarr;</button>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showCreateModal', false)">
            </div>

            <!-- content -->
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">
                                {{ $editingProductId ? 'Edit Product' : 'New Product' }}
                            </h2>
                            <p class="text-slate-500 mt-1">Fill in the details for your catalog item.</p>
                        </div>
                        <button wire:click="$set('showCreateModal', false)"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column: Image Preview + Basic -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Product
                                    Image</label>
                                <div
                                    class="aspect-square bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 relative group">
                                    @if($image_url)
                                        <img src="{{ $image_url }}" class="w-full h-full object-cover">
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <p class="text-white text-xs font-bold">Preview</p>
                                        </div>
                                    @else
                                        <div class="text-center p-4">
                                            <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <span class="text-xs text-slate-400 font-medium">Enter URL below</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Image
                                    URL</label>
                                <input wire:model.live.debounce.300ms="image_url" type="url"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all placeholder:text-slate-400"
                                    placeholder="https://...">
                                @error('image_url') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Right Column: Form -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Product
                                    Name <span class="text-red-500">*</span></label>
                                <input wire:model="name" type="text"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all font-bold text-slate-900 dark:text-white"
                                    placeholder="e.g. Premium T-Shirt">
                                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Retailer
                                    ID (SKU) <span class="text-red-500">*</span></label>
                                <input wire:model="retailer_id" type="text"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all font-mono"
                                    placeholder="SKU-12345">
                                @error('retailer_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Price
                                        <span class="text-red-500">*</span></label>
                                    <input wire:model="price" type="number" step="0.01"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all font-bold"
                                        placeholder="0.00">
                                    @error('price') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Currency</label>
                                    <select wire:model="currency"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all">
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="GBP">GBP (£)</option>
                                        <option value="INR">INR (₹)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Description</label>
                                <textarea wire:model="description" rows="3"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-wa-green/20 transition-all resize-none"
                                    placeholder="Describe your product..."></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button wire:click="$set('showCreateModal', false)"
                            class="px-6 py-3 text-slate-500 font-bold hover:text-slate-700 transition-colors">Cancel</button>
                        <button wire:click="{{ $editingProductId ? 'update' : 'store' }}"
                            class="px-8 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all">
                            {{ $editingProductId ? 'Save Changes' : 'Create Product' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>