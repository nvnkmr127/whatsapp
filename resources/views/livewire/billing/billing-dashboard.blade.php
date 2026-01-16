<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Billing <span
                        class="text-wa-teal">& Usage</span></h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Manage your subscription, credits, and view
                    transaction history</p>
            </div>

            <button wire:click="openTopUpModal"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-gradient-to-r from-wa-teal to-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Credits
            </button>
        </div>

        {{-- Flash Message --}}
        @if (session()->has('message'))
            <div class="mb-6 p-4 bg-wa-teal/10 border border-wa-teal/20 rounded-2xl text-wa-teal flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-bold">{{ session('message') }}</span>
            </div>
        @endif

        {{-- Top Row: Plan & Wallet --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Current Plan Card --}}
            <div
                class="bg-gradient-to-br from-slate-900 to-slate-800 border-none rounded-[2.5rem] p-10 relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current Plan
                            </p>
                            <h3 class="text-3xl font-black text-white uppercase tracking-tight">
                                {{ $plan->display_name ?? 'Basic' }}
                            </h3>
                        </div>
                        <div class="text-right">
                            <p class="text-5xl font-black text-indigo-400 tracking-tight">
                                ${{ number_format($plan->monthly_price ?? 0, 0) }}</p>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-1">per month</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex items-center gap-4 text-sm font-bold text-slate-300">
                            <div class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <span>{{ number_format($plan->message_limit ?? 0) }} messages/month</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm font-bold text-slate-300">
                            <div class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span>{{ $plan->agent_limit ?? 0 }} team members</span>
                        </div>
                    </div>

                    @if($team->subscription_ends_at)
                        <div class="pt-6 border-t border-slate-700/50 flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Renews on
                                {{ \Carbon\Carbon::parse($team->subscription_ends_at)->format('M d, Y') }}
                            </p>
                            <span
                                class="px-3 py-1 bg-wa-teal/20 text-wa-teal text-[10px] font-black uppercase tracking-widest rounded-lg">Active</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Wallet Balance Card --}}
            <div
                class="bg-gradient-to-br from-wa-teal/10 to-wa-teal/10 border border-wa-teal/20 rounded-[2.5rem] p-10 relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/10 rounded-full blur-3xl"></div>
                <div class="relative h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <p
                                    class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                                    Wallet Balance</p>
                                <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                                    ${{ number_format($wallet->balance ?? 0, 2) }}</h3>
                            </div>
                            <div
                                class="w-16 h-16 bg-white dark:bg-white/10 rounded-[1.5rem] flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-wa-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">Available credits for
                            conversation charges.</p>
                    </div>

                    <button wire:click="openTopUpModal"
                        class="w-full mt-8 px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl hover:scale-[1.02] active:scale-95 transition-all shadow-xl">
                        Add Credits Now
                    </button>
                </div>
            </div>
        </div>

        {{-- Usage Stats --}}
        <div
            class="bg-white dark:bg-slate-900 border border-slate-50 dark:border-slate-800 rounded-[2.5rem] shadow-xl p-8 mb-8">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-8">Usage This Month
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                {{-- Messages Usage --}}
                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Messages Sent</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">{{ number_format($usage) }}
                            <span class="text-slate-400 font-bold">/
                                {{ number_format($plan->message_limit ?? 0) }}</span></span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-4 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-wa-teal to-wa-teal transition-all duration-500 rounded-full"
                            style="width: {{ min($usagePercentage, 100) }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <span>0%</span>
                        <span>{{ number_format($usagePercentage, 0) }}% Used</span>
                        <span>100%</span>
                    </div>
                </div>

                {{-- Team Members --}}
                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Team Members</span>
                        <span class="text-sm font-black text-slate-900 dark:text-white">{{ $team->users->count() }}
                            <span class="text-slate-400 font-bold">/ {{ $plan->agent_limit ?? 0 }}</span></span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-4 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500 rounded-full"
                            style="width: {{ min(($team->users->count() / max($plan->agent_limit ?? 1, 1)) * 100, 100) }}%">
                        </div>
                    </div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <span>0%</span>
                        <span>{{ number_format(($team->users->count() / max($plan->agent_limit ?? 1, 1)) * 100, 0) }}%
                            Used</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transaction History --}}
        <div
            class="bg-white dark:bg-slate-900 border border-slate-50 dark:border-slate-800 rounded-[2.5rem] shadow-xl overflow-hidden">
            <div class="p-8 border-b border-slate-50 dark:border-slate-800">
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Transaction
                    History</h3>
            </div>

            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-50 dark:border-slate-800">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date
                                </th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Description</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type
                                </th>
                                <th
                                    class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                    Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            @foreach($transactions as $transaction)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-8 py-5 text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $transaction->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-8 py-5 text-sm font-medium text-slate-600 dark:text-slate-400">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="px-8 py-5">
                                        <span
                                            class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg {{ $transaction->type === 'deposit' ? 'bg-wa-teal/10 text-wa-teal' : 'bg-rose-500/10 text-rose-500' }}">
                                            {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <span
                                            class="text-sm font-black {{ $transaction->amount >= 0 ? 'text-wa-teal' : 'text-slate-900 dark:text-white' }}">
                                            {{ $transaction->amount >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-8 border-t border-slate-50 dark:border-slate-800">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <div
                        class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-300">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-slate-500 font-bold">No transactions found</p>
                </div>
            @endif
        </div>

        {{-- Top-Up Modal --}}
        @if($showTopUpModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeTopUpModal"></div>

                <div
                    class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="p-8 pb-0">
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Add <span
                                class="text-wa-teal">Credits</span></h3>
                    </div>

                    <form wire:submit.prevent="topUp">
                        <div class="p-8 space-y-6">
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Amount
                                    ($)</label>
                                <input type="number" wire:model="topUpAmount" step="10" min="10" max="10000"
                                    class="w-full text-3xl font-black text-slate-900 dark:text-white bg-transparent border-none p-0 focus:ring-0 placeholder:text-slate-200 mb-4"
                                    placeholder="0">
                                <div class="h-1 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-wa-teal w-1/2"></div>
                                </div>
                                @error('topUpAmount') <span
                                    class="text-rose-500 text-[10px] font-bold uppercase mt-2 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-4 gap-2">
                                @foreach([10, 50, 100, 500] as $amount)
                                    <button type="button" wire:click="$set('topUpAmount', {{ $amount }})"
                                        class="py-2 bg-slate-50 dark:bg-slate-800 hover:bg-wa-teal hover:text-white text-slate-500 dark:text-slate-400 font-bold rounded-xl transition-all text-xs">
                                        ${{ $amount }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl p-4 flex gap-3">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs font-medium text-indigo-800 dark:text-indigo-200">
                                    Funds are added immediately to your wallet. You can use them for conversation charges.
                                </p>
                            </div>
                        </div>

                        <div
                            class="p-8 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-50 dark:border-slate-800 flex gap-3">
                            <button type="button" wire:click="closeTopUpModal"
                                class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Pay & Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>