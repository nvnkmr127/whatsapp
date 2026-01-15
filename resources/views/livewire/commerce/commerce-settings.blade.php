<div class="space-y-8 pb-20">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Commerce <span
                        class="text-wa-teal">Control</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Configure store behavior, order notifications, and AI assistant.</p>
        </div>

        <div class="flex items-center gap-3">
            <x-action-message class="text-wa-green font-bold text-xs uppercase" on="saved">
                Configuration Saved
            </x-action-message>

            <button wire:click="save"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Save Changes
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div
            class="animate-in slide-in-from-top-4 duration-500 p-4 bg-wa-green/10 border border-wa-green/20 text-wa-green rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-bold text-sm">{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: General & Cart Engine -->
        <div class="space-y-8">
            <!-- Store Configuration -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 space-y-8">
                <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-800/50 pb-6">
                    <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Store <span
                            class="text-wa-teal">Basics</span></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Store
                            Currency</label>
                        <input type="text" wire:model="currency"
                            class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="USD" maxlength="3">
                        <x-input-error for="currency" class="mt-2" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Min Order
                            Value</label>
                        <input type="number" step="0.01" wire:model="min_order_value"
                            class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="0.00">
                        <x-input-error for="min_order_value" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label
                        class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <x-checkbox wire:model="allow_guest_checkout"
                            class="w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20" />
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Allow Guest Checkout</span>
                    </label>

                    <label
                        class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <x-checkbox wire:model="cod_enabled"
                            class="w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20" />
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Enable COD</span>
                    </label>
                </div>
            </div>

            <!-- Cart Engine Configuration -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 space-y-8">
                <div class="flex items-center gap-3 border-b border-slate-50 dark:border-slate-800/50 pb-6">
                    <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Cart <span
                            class="text-wa-teal">Intelligence</span></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Cart Expiry
                            (Min)</label>
                        <input type="number" wire:model="cart_expiry_minutes"
                            class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="60">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Reminder Delay
                            (Min)</label>
                        <input type="number" wire:model="cart_reminder_minutes"
                            class="w-full px-5 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="30">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Multi-Session
                        Behavior</label>
                    <div class="relative">
                        <select wire:model="cart_merge_strategy"
                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
                            <option value="merge">Merge Carts (Combine Items)</option>
                            <option value="replace">Use Newest (Disable Old)</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Notifications & AI -->
        <div class="space-y-8">
            <!-- Order Notifications -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex items-center gap-3">
                    <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">WhatsApp
                        <span class="text-wa-teal">Alerts</span></h2>
                </div>

                <div class="p-8 space-y-6 max-h-[500px] overflow-y-auto">
                    @foreach($notifications as $status => $template)
                        <div
                            class="space-y-3 p-5 bg-slate-50 dark:bg-slate-800/30 rounded-3xl border border-slate-100 dark:border-slate-800/50 group hover:border-wa-teal/30 transition-all">
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Order
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}</label>
                                <span
                                    class="text-[10px] font-bold text-wa-teal bg-wa-teal/10 px-2 py-0.5 rounded-md uppercase tracking-tighter">Trigger</span>
                            </div>

                            <select wire:model="notifications.{{ $status }}"
                                class="w-full px-4 py-3 bg-white dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                <option value="">Select Template...</option>
                                @foreach($availableTemplates as $t)
                                    <option value="{{ $t->name }}">{{ $t->name }} ({{ $t->language }})</option>
                                @endforeach
                            </select>

                            <div class="flex items-start gap-2 text-[10px] font-medium text-slate-400">
                                <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    @if($status == 'shipped')
                                        Variables: Order ID, Tracking Link
                                    @elseif($status == 'placed')
                                        Variables: Order ID, Total Amount
                                    @else
                                        Variables: Order ID
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- AI & Agent Alerts -->
            <div
                class="bg-slate-900 dark:bg-slate-800 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                <div
                    class="absolute -right-10 -top-10 w-40 h-40 bg-wa-teal/20 blur-3xl rounded-full group-hover:bg-wa-teal/30 transition-colors">
                </div>

                <div class="relative z-10 space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-wa-teal/20 text-wa-teal rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-black uppercase tracking-tight">AI Shop <span
                                class="text-wa-teal">Assistant</span></h2>
                    </div>

                    <p class="text-slate-400 text-sm font-medium leading-relaxed">
                        Enable autonomous product recommendations. Customers can inquire about your catalog via chat.
                    </p>

                    <label
                        class="flex items-center gap-4 p-4 bg-white/5 border border-white/10 rounded-2xl cursor-pointer hover:bg-white/10 transition-all">
                        <x-checkbox wire:model="ai_assistant_enabled"
                            class="w-6 h-6 rounded-lg border-none bg-slate-700 text-wa-teal focus:ring-wa-teal/20" />
                        <div>
                            <span class="text-sm font-black uppercase tracking-widest block">Activate Agent</span>
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">Powered by
                                OpenAI</span>
                        </div>
                    </label>

                    <a href="{{ route('settings.ai') }}"
                        class="flex items-center justify-center gap-2 w-full py-3 bg-white text-slate-900 font-black uppercase tracking-widest text-xs rounded-xl hover:scale-[1.02] transition-all">
                        Customize AI Persona
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Internal Agent Alerts -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-rose-500/10 text-rose-500 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2
                        class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight hover:text-rose-500 transition-colors">
                        Agent <span class="text-rose-500">Notifications</span></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(['placed', 'payment_failed', 'cancelled', 'returned'] as $status)
                        <label
                            class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group">
                            <x-checkbox wire:model="agent_notifications.{{ $status }}"
                                class="w-4 h-4 rounded-md border-none bg-slate-200 dark:bg-slate-700 text-rose-500 focus:ring-rose-500/20" />
                            <span
                                class="text-xs font-bold text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>