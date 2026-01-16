<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Smart <span
                        class="text-wa-teal">Flows</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Design, manage, and deploy interactive conversational experiences.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="syncFlows" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                <svg wire:loading.remove wire:target="syncFlows" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="syncFlows" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Sync Meta
            </button>
            <button wire:click="$toggle('showCreateModal')"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Create Flow
            </button>
        </div>
    </div>

    @if(session()->has('success'))
        <div
            class="animate-in slide-in-from-top-4 duration-500 p-4 bg-wa-teal/10 border border-wa-teal/20 text-wa-teal rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($flows as $flow)
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal/20 transition-all group relative flex flex-col h-full">

                <div class="flex items-center justify-between mb-6">
                    <div class="p-4 bg-wa-teal/10 text-wa-teal rounded-2xl">
                        <svg class="w-6 h-6 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span
                            class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full {{ $flow->status === 'PUBLISHED' ? 'bg-wa-teal/10 text-wa-teal' : 'bg-slate-100 text-slate-500' }}">
                            {{ $flow->status }}
                        </span>
                        @if($flow->uses_data_endpoint)
                            <span class="flex items-center gap-1 text-[10px] font-bold text-wa-teal uppercase tracking-wider">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Dynamic
                            </span>
                        @endif
                    </div>
                </div>

                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2 truncate"
                    title="{{ $flow->name }}">
                    {{ $flow->name }}
                </h3>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8">Meta ID:
                    {{ $flow->flow_id ?? 'Not Deployed' }}
                </p>

                <div class="mt-auto grid grid-cols-2 gap-3">
                    <a href="{{ route('flows.builder', $flow->id) }}"
                        class="flex items-center justify-center gap-2 py-3 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                        Edit Design
                    </a>
                    <button wire:click="deleteFlow({{ $flow->id }})"
                        wire:confirm="Are you sure you want to delete this flow?"
                        class="py-3 bg-rose-50 dark:bg-rose-500/10 text-rose-500 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-all">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center text-center">
                <div class="p-8 bg-slate-100 dark:bg-slate-800 rounded-[3rem] text-slate-300 mb-6">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">No Flows Yet</h3>
                <p class="text-slate-500 font-medium max-w-sm mt-2 mb-8">Design intelligent forms and automated interactions
                    to capture structured data effortlessly.</p>
                <button wire:click="$toggle('showCreateModal')"
                    class="px-12 py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-2xl shadow-wa-teal/20 hover:scale-105 transition-all">
                    Build Your First Smart Flow
                </button>
            </div>
        @endforelse
    </div>

    <!-- Create Flow Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$toggle('showCreateModal')"></div>
            <div
                class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-8 pb-0 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Create <span class="text-wa-teal">Flow</span>
                    </h2>
                    <button wire:click="$toggle('showCreateModal')"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Flow Name</label>
                        <input type="text" wire:model.defer="name" placeholder="E.g. Customer Feedback Survey"
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20">
                        @error('name') <span class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Endpoint Toggle -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700/50">
                        <label class="flex items-start gap-4 cursor-pointer group">
                            <div class="flex items-center h-6">
                                <input type="checkbox" wire:model.defer="usesDataEndpoint"
                                    class="w-5 h-5 rounded-lg border-none bg-white dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20 transition-all">
                            </div>
                            <div class="flex-1">
                                <span
                                    class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide group-hover:text-wa-teal transition-colors">Use
                                    Data Endpoint</span>
                                <p class="text-slate-500 text-xs font-medium mt-1 leading-relaxed">Enable for dynamic
                                    content (e.g. login, calculators). Requires server-side processing.</p>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 flex gap-4">
                        <button wire:click="$toggle('showCreateModal')"
                            class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                            Cancel
                        </button>
                        <button wire:click="createFlow" wire:loading.attr="disabled"
                            class="flex-[2] py-4 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Create Flow
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>