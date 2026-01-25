<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-indigo-500/10 text-indigo-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Launch <span
                        class="text-indigo-500">Offer</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Design the free trial experience for new signups.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <form wire:submit.prevent="save" class="p-10">

            <x-action-message class="mb-6" on="saved">
                <div class="flex items-center gap-2 text-wa-teal font-bold bg-wa-teal/10 p-3 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Settings Saved Successfully!
                </div>
            </x-action-message>

            @if (session()->has('message'))
                <div
                    class="mb-8 bg-green-500/10 border border-green-500/20 text-green-600 font-bold px-6 py-4 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('message') }}
                </div>
            @endif

            <!-- Toggle Section -->
            <div
                class="mb-10 p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-between border border-indigo-100 dark:border-indigo-500/30">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-white dark:bg-indigo-900/40 rounded-xl text-indigo-500 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-indigo-900 dark:text-indigo-100 uppercase tracking-tight">
                            Enable Launch Offer</h3>
                        <p class="text-indigo-600/80 dark:text-indigo-300 font-medium text-sm mt-1">New registrations
                            will receive this trial plan automatically.</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="offerEnabled" class="sr-only peer">
                        <div
                            class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-500">
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Limits Column -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4 mb-6">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Configuration</h3>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">Trial Duration
                                (Months)</label>
                            <input type="number" wire:model="trialMonths" min="1" max="24"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all">
                            @error('trialMonths') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-400">Monthly
                                    Message Limit</label>
                                <span
                                    class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Messages/mo</span>
                            </div>
                            <input type="number" wire:model="messageLimit"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all">
                            @error('messageLimit') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-400">Agent
                                    Seats</label>
                                <input type="number" wire:model="agentLimit"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all">
                                @error('agentLimit') <span
                                    class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-400">WhatsApp
                                    Nos</label>
                                <input type="number" wire:model="whatsappLimit"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all">
                                @error('whatsappLimit') <span
                                    class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-2 pt-2">
                            <label class="text-xs font-black uppercase tracking-widest text-emerald-500">Launch Gift
                                Credit ($)</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
                                    <span class="text-slate-400 font-bold text-lg">$</span>
                                </div>
                                <input type="number" step="0.01" wire:model="initialCredit"
                                    class="w-full pl-10 pr-5 py-4 bg-emerald-50/50 dark:bg-emerald-900/10 border-2 border-emerald-100 dark:border-emerald-500/20 rounded-2xl text-emerald-700 dark:text-emerald-400 font-black focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-lg transition-all">
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">One-time wallet
                                balance given on signup</p>
                            @error('initialCredit') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Features Column -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4 mb-6">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Included
                            Features</h3>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-6 space-y-4">
                        @foreach($availableFeatures as $key => $label)
                            <label
                                class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 cursor-pointer hover:border-indigo-500/30 transition-all group">
                                <span
                                    class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $label }}</span>
                                <input type="checkbox" value="{{ $key }}" wire:model="includedFeatures"
                                    class="w-6 h-6 rounded-lg border-2 border-slate-200 dark:border-slate-600 text-indigo-500 focus:ring-indigo-500/20 transition-all">
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-12 flex justify-end pt-8 border-t border-slate-100 dark:border-slate-800">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-10 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-sm rounded-2xl shadow-xl shadow-indigo-500/20 hover:scale-[1.02] hover:bg-indigo-500 active:scale-95 transition-all flex items-center gap-2">
                    <span wire:loading.remove>Save Configuration</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>

        </form>
    </div>
</div>