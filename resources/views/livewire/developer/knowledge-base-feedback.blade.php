<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-3 bg-wa-teal dark:bg-wa-teal/30 rounded-2xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Brain <span
                        class="text-wa-teal">Feedback</span></h1>
            </div>
            <p class="text-slate-500 font-medium italic">Monitor what your AI doesn't know and close the knowledge gaps.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('knowledge-base.index') }}"
                class="text-sm font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7" />
                </svg>
                Back to Brain
            </a>
        </div>
    </div>

    <!-- Filters & Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Search -->
        <div class="lg:col-span-2 relative group">
            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" wire:model.live="search" placeholder="Search user queries..."
                class="w-full pl-14 pr-6 py-4 bg-white dark:bg-slate-900 border-none rounded-3xl text-sm font-bold shadow-xl shadow-slate-200/50 dark:shadow-none focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white transition-all">
        </div>

        <!-- Status Filter -->
        <div class="lg:col-span-1">
            <select wire:model.live="statusFilter"
                class="w-full px-6 py-4 bg-white dark:bg-slate-900 border-none rounded-3xl text-sm font-black uppercase tracking-widest focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white shadow-xl shadow-slate-200/50 dark:shadow-none appearance-none cursor-pointer">
                <option value="pending">Pending Review</option>
                <option value="resolved">Resolved Gaps</option>
                <option value="ignored">Ignored</option>
                <option value="">All Status</option>
            </select>
        </div>

        <!-- Quick Stat -->
        <div class="lg:col-span-1 bg-wa-teal rounded-3xl shadow-xl p-4 flex items-center gap-4 text-white">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                <span class="font-black text-lg">{{ $gaps->total() }}</span>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-80">Total Gaps</p>
                <p class="text-xs font-bold leading-tight">Requires Training</p>
            </div>
        </div>
    </div>

    <!-- Feedback Entries List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 overflow-hidden">
        <div class="space-y-4">
            @forelse($gaps as $gap)
                <div
                    class="group relative bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-transparent hover:border-wa-teal/20 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-wa-teal dark:text-indigo-400 text-[9px] font-black uppercase tracking-widest rounded-lg border border-indigo-100 dark:border-indigo-800/50">
                                    {{ str_replace('_', ' ', $gap->gap_type) }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Detected {{ $gap->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-relaxed">
                                "{{ $gap->query }}"
                            </h3>

                            @if($gap->status === 'resolved' && $gap->resolution_note)
                                <div
                                    class="mt-4 p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span
                                            class="text-[10px] font-black uppercase text-emerald-700 dark:text-emerald-400 tracking-wider">Resolution</span>
                                    </div>
                                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300 italic">
                                        {{ $gap->resolution_note }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            @if($gap->status === 'pending')
                                <button wire:click="openResolutionModal({{ $gap->id }})"
                                    class="px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl hover:scale-[1.05] active:scale-95 transition-all">
                                    Resolve Gap
                                </button>
                                <button wire:click="ignoreGap({{ $gap->id }})"
                                    class="p-3 text-slate-400 hover:text-rose-500 transition-colors" title="Ignore">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @else
                                <div class="flex flex-col items-end">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest {{ $gap->status === 'resolved' ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ ucfirst($gap->status) }}
                                    </span>
                                    <button wire:click="openResolutionModal({{ $gap->id }})"
                                        class="text-[10px] font-bold text-wa-teal hover:underline uppercase tracking-wider mt-1">
                                        View Details
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-20 flex flex-col items-center justify-center text-center">
                    <div
                        class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-[3rem] text-slate-300 mb-6 group hover:scale-110 transition-transform duration-500">
                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Your Brain is
                        Brilliant</h3>
                    <p class="text-slate-500 font-medium max-w-sm mx-auto mt-2 italic">No knowledge gaps detected for your
                        current filter. Your AI is answering everything like a pro.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $gaps->links() }}
        </div>
    </div>

    <!-- Resolution Modal -->
    @if($showResolutionModal)
        @teleport('body')
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"
                    wire:click="$set('showResolutionModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight"
                                    id="modal-title">
                                    {{ optional($gaps->find($selectedGapId))->status === 'pending' ? 'Address Knowledge Gap' : 'Resolution Details' }}
                                </h3>
                                <p class="text-sm text-wa-teal font-bold uppercase tracking-wider mt-1 italic">
                                    Closing the loop on:
                                    "{{ Str::limit(optional($gaps->find($selectedGapId))->query, 40) }}"
                                </p>
                            </div>
                            <button wire:click="$set('showResolutionModal', false)"
                                class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label
                                    class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest italic">Resolution
                                    Note</label>
                                @if(optional($gaps->find($selectedGapId))->status === 'pending')
                                    <textarea wire:model="resolutionNote" rows="6"
                                        class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-[1.5rem] text-sm font-medium focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white resize-none leading-relaxed"
                                        placeholder="Explain how you fixed this (e.g., Added 'Cancellation Policy' to Business Brain)..."></textarea>
                                    @error('resolutionNote') <p
                                        class="mt-2 text-[10px] font-bold text-rose-500 uppercase italic">{{ $message }}</p>
                                    @enderror
                                @else
                                    <div
                                        class="w-full px-8 py-6 bg-slate-50 dark:bg-slate-800 rounded-[1.5rem] text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed italic">
                                        {{ $resolutionNote ?: 'No resolution note provided.' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <button wire:click="$set('showResolutionModal', false)"
                                class="px-8 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-200 transition-all">
                                Close
                            </button>
                            @if(optional($gaps->find($selectedGapId))->status === 'pending')
                                <button wire:click="resolveGap"
                                    class="px-8 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                    Mark Resolved
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="fixed bottom-8 right-8 z-50 animate-in slide-in-from-right-10 duration-500">
            <div
                class="bg-wa-teal text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4 border border-white/20 backdrop-blur-xl">
                <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-xs font-black uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        </div>
    @endif
</div>