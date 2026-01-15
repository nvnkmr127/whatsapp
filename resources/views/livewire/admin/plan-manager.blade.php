<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Plan <span class="text-indigo-500">Manager</span>
                </h1>
                <p class="text-slate-500 font-medium">Create and manage subscription plans for your tenants.</p>
            </div>

            <button wire:click="createPlan"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Create Plan
            </button>
        </div>

        <!-- Flash Message -->
        @if (session()->has('message'))
            <div
                class="mb-8 p-4 bg-wa-green/10 text-wa-green rounded-2xl border border-wa-green/20 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-bold">{{ session('message') }}</span>
            </div>
        @endif

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 flex flex-col h-full relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">

                    <!-- Decorative Circle -->
                    <div
                        class="absolute -right-12 -top-12 w-40 h-40 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-3xl group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/20 transition-colors">
                    </div>

                    <div class="relative flex-1">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {{ $plan->name }}</h3>
                                <div class="flex items-baseline gap-1 mt-2">
                                    <span
                                        class="text-4xl font-black text-indigo-500">${{ number_format($plan->monthly_price, 2) }}</span>
                                    <span class="text-xs font-bold text-slate-400 uppercase">/month</span>
                                </div>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-slate-800 rounded-2xl text-indigo-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>

                        <!-- Limits -->
                        <div class="space-y-4 mb-8">
                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <div class="p-2 bg-white dark:bg-slate-800 rounded-xl text-wa-green shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Message Limit
                                    </div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ number_format($plan->message_limit) }} <span
                                            class="text-xs font-medium text-slate-500">/mo</span></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <div class="p-2 bg-white dark:bg-slate-800 rounded-xl text-blue-500 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest">Team Size</div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $plan->agent_limit }} <span
                                            class="text-xs font-medium text-slate-500">members</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        @if(is_array($plan->features) && count($plan->features) > 0)
                            <div class="mb-8">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Included
                                    Features</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($plan->features as $feature => $enabled)
                                        @if($enabled)
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-wa-green flex-shrink-0" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize truncate"
                                                    title="{{ str_replace('_', ' ', $feature) }}">
                                                    {{ str_replace('_', ' ', $feature) }}
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6 border-t border-slate-50 dark:border-slate-800 relative z-10">
                        <button wire:click="editPlan({{ $plan->id }})"
                            class="flex-1 py-3 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Edit
                        </button>
                        <button wire:click="deletePlan({{ $plan->id }})"
                            onclick="return confirm('Are you sure you want to delete this plan?')"
                            class="flex-1 py-3 bg-white dark:bg-slate-900 text-rose-500 border border-rose-100 dark:border-rose-900/30 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-12 text-center border border-slate-50 dark:border-slate-800 shadow-xl">
                        <div
                            class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">No Plans Created</h3>
                        <p class="text-slate-500 font-medium mb-8">Get started by defining your first subscription tier.</p>
                        <button wire:click="createPlan"
                            class="px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] transition-transform">
                            Create First Plan
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
                <div class="p-8 pb-0 flex justify-between items-center flex-shrink-0">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            {{ $editingPlan ? 'Update' : 'New' }} <span class="text-indigo-500">Plan</span>
                        </h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Configure subscription
                            details</p>
                    </div>
                    <button wire:click="closeModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 space-y-8 overflow-y-auto flex-1">
                    <form wire:submit.prevent="savePlan">
                        <div class="space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Plan
                                        Name</label>
                                    <input type="text" wire:model="name" placeholder="e.g. Professional"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                    @error('name') <span
                                        class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Monthly Price
                                        ($)</label>
                                    <input type="number" step="0.01" wire:model="monthly_price" placeholder="0.00"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                    @error('monthly_price') <span
                                        class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Limits -->
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Usage
                                    Limits</label>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Messages /
                                                Month</span>
                                        </div>
                                        <input type="number" wire:model="message_limit" placeholder="e.g. 10000"
                                            class="w-full px-4 py-2 bg-white dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                        @error('message_limit') <span
                                            class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Team
                                                Members</span>
                                        </div>
                                        <input type="number" wire:model="agent_limit" placeholder="e.g. 5"
                                            class="w-full px-4 py-2 bg-white dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                        @error('agent_limit') <span
                                            class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Feature
                                        Access</label>
                                    <button type="button"
                                        wire:click="$set('features', array_fill_keys(array_keys($features), true))"
                                        class="text-[10px] font-bold text-indigo-500 uppercase hover:underline">Select
                                        All</button>
                                </div>
                                <div class="grid grid-cols-2 gap-3 max-h-48 overflow-y-auto pr-2">
                                    @foreach(['chat', 'contacts', 'templates', 'campaigns', 'automations', 'analytics', 'commerce', 'ai', 'api_access', 'webhooks'] as $feature)
                                        <label
                                            class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                            <div class="relative flex items-center">
                                                <input type="checkbox" wire:model="features.{{ $feature }}"
                                                    class="peer w-5 h-5 rounded-lg border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500/20 transition-all checked:bg-indigo-600 checked:border-indigo-600">
                                            </div>
                                            <span
                                                class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize select-none">{{ str_replace('_', ' ', $feature) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 flex gap-4">
                            <button type="button" wire:click="closeModal"
                                class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-[2] py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                                {{ $editingPlan ? 'Save Changes' : 'Create Plan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>