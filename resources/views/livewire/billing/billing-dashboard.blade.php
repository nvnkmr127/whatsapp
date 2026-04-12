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

        {{-- 6-Month Offer Banner --}}
        @if($isTrial)
            <div class="mb-8 bg-gradient-to-r from-indigo-500 via-purple-600 to-indigo-700 rounded-[2.5rem] p-1 shadow-2xl overflow-hidden relative group transition-all">
                <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="bg-slate-900/40 backdrop-blur-xl rounded-[2.3rem] p-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center gap-6">
                            <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center text-4xl shadow-inner border border-white/10">
                                🎁
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-2 bg-indigo-500/20 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300 mb-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                                    Launch Offer Active
                                </div>
                                <h3 class="text-3xl font-black text-white tracking-tight leading-none mb-2">6 Months Free <span class="text-indigo-400">Unlocked</span></h3>
                                <p class="text-slate-300 font-medium max-w-lg">Congratulations! Your account is part of our early adopter program. You have full access to all premium features for 6 months at zero cost.</p>
                            </div>
                        </div>
                        <div class="text-center md:text-right px-8 py-4 bg-white/5 rounded-3xl border border-white/5">
                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Offer Expires In</p>
                            @if($trialEndsAt)
                                <div class="text-3xl font-black text-white leading-none mb-1">
                                    {{ now()->diffInDays($trialEndsAt) }} Days
                                </div>
                                <p class="text-[10px] font-bold text-slate-400">Valid until {{ $trialEndsAt->format('F d, Y') }}</p>
                                
                                <div class="mt-4">
                                    @if($this->hasPendingExtensionRequest)
                                        <span class="px-4 py-2 bg-white/10 text-white text-[10px] font-black uppercase tracking-widest rounded-xl opacity-50 cursor-not-allowed">
                                            Request Pending
                                        </span>
                                    @else
                                        <button wire:click="openExtensionModal" class="px-4 py-2 bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-indigo-500/20">
                                            Request Extension
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="text-3xl font-black text-white leading-none mb-1">
                                    --
                                </div>
                                <p class="text-[10px] font-bold text-slate-400">Expiration date not set</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Decorative Elements --}}
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-400/20 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-400/20 rounded-full -ml-32 -mb-32 blur-3xl"></div>
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
                                {{ get_setting('currency_symbol', '$') }}{{ number_format($plan->monthly_price ?? 0, 0) }}</p>
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

                    @if($this->upcomingBillingDate)
                        <div class="pt-6 border-t border-slate-700/50 flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                                {{ $isTrial ? 'Trial Ends' : 'Renews On' }} 
                                {{ \Carbon\Carbon::parse($this->upcomingBillingDate)->format('M d, Y') }}
                            </p>
                            <span
                                class="px-3 py-1 bg-wa-teal/20 text-wa-teal text-[10px] font-black uppercase tracking-widest rounded-lg">{{ $isTrial ? 'Trial' : 'Active' }}</span>
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
                                    {{ get_setting('currency_symbol', '$') }}{{ number_format($wallet->balance ?? 0, 2) }}</h3>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                @foreach($detailedStats as $key => $stat)
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <span
                                class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ $stat['label'] }}</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">
                                {{ number_format($stat['usage']) }}
                                @if($stat['limit'] > 0)
                                    <span class="text-slate-400 font-bold">/ {{ number_format($stat['limit']) }}</span>
                                @else
                                    <span class="text-wa-teal font-bold"> (Unlimited)</span>
                                @endif
                            </span>
                        </div>

                        @php
                            $progPercent = $stat['limit'] > 0 ? min(($stat['usage'] / $stat['limit']) * 100, 100) : 0;
                            $barColor = $progPercent >= 90 ? 'from-rose-500 to-rose-600' : ($progPercent >= 80 ? 'from-yellow-400 to-yellow-500' : 'from-wa-teal to-wa-teal');
                        @endphp

                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                            <div class="h-full bg-gradient-to-r {{ $barColor }} transition-all duration-500 rounded-full"
                                style="width: {{ $stat['limit'] > 0 ? $progPercent : 100 }}%"></div>
                        </div>

                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            <span>0%</span>
                            <span>{{ $stat['limit'] > 0 ? number_format($progPercent, 0) : 0 }}% Used</span>
                            <span>100%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Available Plans --}}
        <div class="mb-8 overflow-x-auto pb-4">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-6">Choose Your Plan</h3>
            <div class="flex gap-6 min-w-max">
                @foreach($plans as $p)
                    <div class="w-72 p-8 rounded-[2rem] border-2 transition-all {{ $p->name === $team->subscription_plan ? 'border-wa-teal bg-wa-teal/5 shadow-lg' : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900' }}">
                        <h4 class="text-xl font-black uppercase tracking-tight mb-2 {{ $p->name === $team->subscription_plan ? 'text-wa-teal' : 'text-slate-900 dark:text-white' }}">
                            {{ $p->display_name }}
                        </h4>
                        <div class="flex items-baseline gap-1 mb-6">
                             <span class="text-3xl font-black">{{ get_setting('currency_symbol', '$') }}{{ number_format($p->monthly_price, 0) }}</span>
                            <span class="text-xs font-bold text-slate-400 capitalize">/ mo</span>
                        </div>
                        
                        <ul class="space-y-3 mb-8">
                            <li class="text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ number_format($p->message_limit) }} Messages
                            </li>
                            <li class="text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $p->agent_limit }} Agents
                            </li>
                        </ul>

                        @if($p->name !== $team->subscription_plan)
                            <button wire:click="selectPlan('{{ $p->name }}')" class="w-full py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-xl hover:scale-[1.02] active:scale-95 transition-all">
                                Switch Plan
                            </button>
                        @else
                            <div class="w-full py-3 text-center text-wa-teal text-[10px] font-black uppercase tracking-widest bg-wa-teal/10 rounded-xl">
                                Current Plan
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Overrides Notice --}}
        @if($team->billingOverrides()->active()->exists())
            <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/10 border-2 border-amber-200 dark:border-amber-800 rounded-[2rem] flex items-start gap-4 shadow-sm">
                <div class="p-3 bg-amber-100 dark:bg-amber-800 rounded-xl text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-amber-900 dark:text-amber-100 uppercase tracking-tight mb-1">Active Billing Exceptions</h4>
                    <p class="text-xs font-bold text-amber-700 dark:text-amber-300">Your team has authorized overrides that modify standard plan limits for testing or VIP support. These will expire automatically.</p>
                </div>
            </div>
        @endif

        {{-- Invoices History --}}
        @if($this->invoices->count() > 0)
            <div class="bg-white dark:bg-slate-900 border border-slate-50 dark:border-slate-800 rounded-[2.5rem] shadow-xl overflow-hidden mb-8">
                <div class="p-8 border-b border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Recent Invoices</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-50 dark:border-slate-800">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Number</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Period</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            @foreach($this->invoices as $invoice)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-8 py-5 text-sm font-bold">{{ $invoice->invoice_number }}</td>
                                    <td class="px-8 py-5 text-xs font-medium text-slate-500">
                                        {{ $invoice->period_start->format('M d') }} - {{ $invoice->period_end->format('M d, Y') }}
                                    </td>
                                     <td class="px-8 py-5 text-right font-black">{{ get_setting('currency_symbol', '$') }}{{ number_format($invoice->total_amount, 2) }}</td>
                                    <td class="px-8 py-5 text-center">
                                        <button wire:click="downloadInvoice({{ $invoice->id }})" wire:loading.attr="disabled" class="text-[10px] font-black uppercase text-wa-teal hover:underline tracking-widest flex items-center gap-2 mx-auto disabled:opacity-50">
                                            <span wire:loading.remove wire:target="downloadInvoice({{ $invoice->id }})">Download PDF</span>
                                            <span wire:loading wire:target="downloadInvoice({{ $invoice->id }})">Preparing...</span>
                                            <svg wire:loading.remove wire:target="downloadInvoice({{ $invoice->id }})" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Transaction History --}}
        <div
            class="bg-white dark:bg-slate-900 border border-slate-50 dark:border-slate-800 rounded-[2.5rem] shadow-xl overflow-hidden">
            <div class="p-8 border-b border-slate-50 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Transaction
                    History</h3>
                
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="text" wire:model.live="searchTransaction" placeholder="Search description..." 
                            class="pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/50 w-full md:w-64">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <select wire:model.live="transactionType" class="py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-wa-teal/50">
                        <option value="">All Types</option>
                        <option value="deposit">Deposit</option>
                        <option value="usage_charge">Usage Charge</option>
                        <option value="refund">Refund</option>
                        <option value="plan_fee">Plan Fee</option>
                    </select>
                </div>
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
                                             {{ $transaction->amount >= 0 ? '+' : '' }}{{ get_setting('currency_symbol', '$') }}{{ number_format(abs($transaction->amount), 2) }}
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
        <x-app-modal wire:model="showTopUpModal" maxWidth="md">
            <div class="p-8 pb-0">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Add <span
                        class="text-wa-teal">Credits</span></h3>
            </div>

            <form wire:submit.prevent="topUp">
                <div class="p-8 space-y-6">
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Amount
                            ({{ get_setting('currency_symbol', '$') }})</label>
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
                                {{ get_setting('currency_symbol', '$') }}{{ $amount }}
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
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="topUp">Pay & Add</span>
                        <span wire:loading wire:target="topUp" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </button>
                </div>
            </form>
        </x-app-modal>

        {{-- Plan Change Impact Modal --}}
        <x-app-modal wire:model="showChangePlanModal" maxWidth="2xl">
            @if($planImpact)
                <div class="p-8 border-b border-slate-50 dark:border-slate-800">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Confirm <span class="text-wa-teal">Plan Change</span></h3>
                </div>

                <div class="p-8 space-y-8 max-h-[60vh] overflow-y-auto">
                    {{-- Logic: Upgrade or Downgrade --}}
                    <div class="p-6 rounded-3xl {{ $planImpact['type'] === 'upgrade' ? 'bg-indigo-50 dark:bg-indigo-900/10 text-indigo-900 dark:text-indigo-100' : 'bg-amber-50 dark:bg-amber-900/10 text-amber-900 dark:text-amber-100' }}">
                        <div class="flex items-center gap-4">
                            <span class="text-2xl">
                                {{ $planImpact['type'] === 'upgrade' ? '🚀' : '📉' }}
                            </span>
                            <div>
                                <h4 class="font-black uppercase tracking-tight">Switching to {{ ucfirst($selectedPlan) }} Plan</h4>
                                <p class="text-xs font-bold opacity-80">This change will be applied immediately.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Gained Features --}}
                        @if(count($planImpact['features_gained']) > 0)
                            <div class="space-y-4">
                                <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Features You'll Gain</h5>
                                <ul class="space-y-2">
                                    @foreach($planImpact['features_gained'] as $f)
                                        <li class="flex items-center gap-2 text-xs font-bold text-wa-teal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            {{ ucfirst(str_replace('_', ' ', $f)) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                            {{-- Lost Features --}}
                            @if(count($planImpact['features_lost']) > 0)
                            <div class="space-y-4">
                                <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Features You'll Lose</h5>
                                <ul class="space-y-2">
                                    @foreach($planImpact['features_lost'] as $f)
                                        <li class="flex items-center gap-2 text-xs font-bold text-rose-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            {{ ucfirst(str_replace('_', ' ', $f)) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- Resource Impact (Warnings) --}}
                    @if(count($planImpact['resource_impact']) > 0)
                        <div class="space-y-4">
                            <h5 class="text-[10px] font-black uppercase tracking-widest text-rose-500">Resource Warnings</h5>
                            @foreach($planImpact['resource_impact'] as $r)
                                <div class="p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-800 rounded-2xl flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <p class="text-xs font-bold text-rose-800 dark:text-rose-200">{{ $r['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($planImpact['type'] === 'downgrade')
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tight text-center">
                            Downgrades include a 7-day grace period for over-limit resources before suspension.
                        </p>
                    @endif
                </div>

                <div class="p-8 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-50 dark:border-slate-800 flex gap-3">
                    <button type="button" wire:click="$set('showChangePlanModal', false)"
                        class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl border border-slate-100 dark:border-slate-700 hover:text-slate-600 transition-all">
                        Keep Current Plan
                    </button>
                    <button type="button" wire:click="confirmPlanChange"
                        class="flex-[2] py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                        Confirm Switch
                    </button>
                </div>
            @endif
        </x-app-modal>
        {{-- Trial Extension Request Modal --}}
        <x-app-modal wire:model="showExtensionModal" maxWidth="md">
            <div class="p-8 pb-0">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Request <span class="text-indigo-500">Trial Extension</span></h3>
                <p class="text-xs font-bold text-slate-500 mt-2">Need more time to evaluate our platform? Let us know!</p>
            </div>

            <form wire:submit.prevent="submitExtensionRequest">
                <div class="p-8 space-y-6">
                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Extension Duration</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([7, 14, 30] as $days)
                                <button type="button" wire:click="$set('extensionDays', {{ $days }})"
                                    class="py-3 border-2 transition-all rounded-2xl flex flex-col items-center justify-center {{ $extensionDays == $days ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/10 text-indigo-700 dark:text-indigo-300' : 'border-slate-50 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-400' }}">
                                    <span class="text-lg font-black">{{ $days }}</span>
                                    <span class="text-[8px] font-black uppercase tracking-widest">Days</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Reason for Extension</label>
                        <textarea wire:model="extensionReason" rows="4" 
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm font-medium focus:ring-2 focus:ring-indigo-500/50 placeholder:text-slate-400"
                            placeholder="Tell us what you're still testing or what's missing for your decision..."></textarea>
                        @error('extensionReason') <span class="text-rose-500 text-[10px] font-bold uppercase mt-2 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-8 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-50 dark:border-slate-800 flex gap-3">
                    <button type="button" wire:click="closeExtensionModal"
                        class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl border border-slate-100 dark:border-slate-700">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-[2] py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Submit Request
                    </button>
                </div>
            </form>
        </x-app-modal>
    </div>
</div>