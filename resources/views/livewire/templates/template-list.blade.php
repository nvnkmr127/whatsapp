<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">WhatsApp <span
                        class="text-wa-teal">Templates</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage and sync your WhatsApp message templates.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="hidden lg:flex items-center gap-6 mr-6 border-r border-slate-100 dark:border-slate-800 pr-6">
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Assets</div>
                    <div class="text-lg font-black text-slate-800 dark:text-white leading-none">{{ $stats['total'] }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Approved</div>
                    <div class="text-lg font-black text-wa-teal leading-none">{{ $stats['approved'] }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Rejected</div>
                    <div class="text-lg font-black {{ $stats['rejected'] > 0 ? 'text-rose-500' : 'text-slate-300' }} leading-none">{{ $stats['rejected'] }}</div>
                </div>
            </div>

             <button wire:click="syncTemplates" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                <svg wire:loading.remove wire:target="syncTemplates" class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="syncTemplates" class="animate-spin h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Sync
            </button>

            <button wire:click="openCreateModal" class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                </svg>
                Create
            </button>
        </div>
    </div>

    <!-- Inventory List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div
            class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative group w-full sm:w-96">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                    placeholder="Search templates...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Total Records:
                {{ $templates->total() }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Template
                            Name</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Category</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Header</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Language</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Status</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Risk Profile</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse ($templates as $template)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <button wire:click="viewTemplate({{ $template->id }})" class="text-left group-hover:text-wa-teal transition-colors focus:outline-none">
                                    <div class="text-sm font-black text-slate-900 dark:text-white">
                                        {{ $template->name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        {{ $template->whatsapp_template_id }}
                                    </div>
                                </button>
                            </td>
                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $template->category }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="text-[10px] font-black uppercase tracking-widest {{ $this->getHeaderType($template) !== 'NONE' ? 'text-wa-teal' : 'text-slate-400' }}">
                                     {{ $this->getHeaderType($template) }}
                                 </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="text-xs font-black text-slate-500 uppercase">{{ $template->language }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center">
                                    <div
                                        class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2
                                                @if($template->is_paused) bg-amber-500/10 text-amber-500
                                                @elseif($template->status === 'APPROVED') bg-wa-teal/10 text-wa-teal
                                                @elseif($template->status === 'REJECTED') bg-rose-500/10 text-rose-500
                                                @else bg-slate-500/10 text-slate-500 @endif border border-current/10 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $template->is_paused ? 'PAUSED' : $template->status }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                @php $health = $this->getHealthStatus($template); @endphp
                                <div class="flex items-center justify-center">
                                    @if($health === 'SAFE')
                                        <div class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-emerald-100">SAFE</div>
                                    @elseif($health === 'WARNING')
                                        <div class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-amber-100 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            WARN
                                        </div>
                                    @else
                                        <div class="px-3 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-rose-100 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            RISK
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="viewTemplate({{ $template->id }})" class="text-slate-400 hover:text-indigo-600 p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                <button wire:click="deleteTemplate({{ $template->id }})"
                                    wire:confirm="Are you sure you want to delete this template? This will delete it from Meta as well."
                                    class="text-slate-400 hover:text-rose-500 p-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="px-8 py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <div>No templates found. Initiate synchronization or create a new one.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    <!-- Create Modal (Enhanced) -->
    @if($showCreateModal)
    @teleport('body')
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
        <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[95vh] animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="p-8 border-b border-slate-50 dark:border-slate-900 flex justify-between items-center bg-white dark:bg-slate-950 z-10 shrink-0">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        New Message <span class="text-wa-teal">Template</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Create a WhatsApp approved message format</p>
                </div>
                <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-rose-500 p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Validation Warning Overlay -->
            @if(!empty($validationWarnings))
                <div class="absolute inset-x-0 bottom-0 top-[88px] z-20 bg-white/95 dark:bg-slate-950/95 backdrop-blur-md flex flex-col items-center justify-center p-8 animate-in fade-in zoom-in-95 duration-200">
                    <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-900/30 flex items-center gap-4">
                            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 text-amber-600 rounded-xl">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Compliance Warnings</h3>
                                <p class="text-sm font-medium text-amber-700 dark:text-amber-500">Issues detected with your template.</p>
                            </div>
                        </div>
                        <div class="p-8 space-y-4">
                            @foreach($validationWarnings as $warn)
                                <div class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <div class="mt-0.5 text-rose-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ $warn['code'] }}</div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $warn['message'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex flex-col gap-3">
                            <button wire:click="$set('validationWarnings', [])" class="w-full py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 transition-all">
                                Go Back & Fix
                            </button>
                            <button wire:click="$set('ignoreWarnings', true); $call('createTemplate')" class="w-full py-4 text-slate-400 hover:text-slate-600 font-bold text-xs uppercase tracking-widest transition-all">
                                Ignore & Submit Anyway
                            </button>
                        </div>
                    </div>
                </div>
            @endif
                <!-- Left Side: Configuration Form -->
                <div class="p-8 md:p-10 overflow-y-auto custom-scrollbar h-full border-r border-slate-50 dark:border-slate-900 bg-white dark:bg-slate-950">
                    <div class="space-y-10">
                        <!-- Identity Section -->
                        <section class="space-y-6">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">1. Template Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Template Name</label>
                                    <input type="text" wire:model="name" 
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all placeholder:text-slate-400"
                                        placeholder="e.g. shipping_update_v1">
                                    @error('name') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase tracking-wide">{{ $message }}</span> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Category</label>
                                        <select wire:model="category"
                                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                            <option value="UTILITY">Utility</option>
                                            <option value="MARKETING">Marketing</option>
                                            <option value="AUTHENTICATION">Authentication</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Language</label>
                                        <select wire:model="language"
                                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                            @foreach($languages as $code => $label)
                                                <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Category Policy Guide -->
                                <div class="mt-4 p-4 rounded-xl text-xs leading-relaxed border flex items-start gap-3
                                    @if($category === 'MARKETING') bg-pink-50 text-pink-700 border-pink-100 dark:bg-pink-900/10 dark:text-pink-400 dark:border-pink-900/30
                                    @elseif($category === 'UTILITY') bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/10 dark:text-blue-400 dark:border-blue-900/30
                                    @elseif($category === 'AUTHENTICATION') bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-900/10 dark:text-indigo-400 dark:border-indigo-900/30
                                    @endif">
                                    <div class="mt-0.5 shrink-0">
                                        @if($category === 'MARKETING') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        @elseif($category === 'UTILITY') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif($category === 'AUTHENTICATION') <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        @if($category === 'MARKETING')
                                            <strong>Marketing:</strong> Promotions, offers, updates, or invitations. Any template not qualifying as Utility/Auth. <br><span class="opacity-75">Users must have explicitly opted-in.</span>
                                        @elseif($category === 'UTILITY')
                                            <strong>Utility:</strong> Specific, agreed-upon transactions (e.g. order confirmation, receipt). <br><span class="opacity-75 font-bold">NO upsells, "sale", "store", or marketing language allowed.</span>
                                        @elseif($category === 'AUTHENTICATION')
                                            <strong>Authentication:</strong> One-time passwords (OTP) only. <br><span class="opacity-75">Restricted to 1:1 login flows. Cannot be used for broadcasts.</span>
                                        @endif
                                    </div>
                                </div>

                        <!-- Content Section -->
                        <section class="space-y-6">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">2. Message Structure</h4>
                            
                            <!-- Header Toggle -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Header Type</label>
                                <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                                    @foreach(['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT', 'LOCATION'] as $type)
                                        <button wire:click="$set('headerType', '{{ $type }}')"
                                            class="px-2 py-3 rounded-xl border-2 text-[9px] font-black uppercase tracking-widest transition-all {{ $headerType === $type ? 'border-wa-teal bg-wa-teal/5 text-wa-teal shadow-lg shadow-wa-teal/5' : 'border-slate-100 dark:border-slate-900 text-slate-400 hover:border-slate-200' }}">
                                            {{ $type }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if($headerType === 'TEXT')
                                <div class="animate-in slide-in-from-top-2" x-data="{ count: $wire.entangle('headerText').live.length ?? 0 }">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Header Text (Max 60)</label>
                                        <span class="text-[9px] font-bold text-slate-400" x-text="count + '/60'"></span>
                                    </div>
                                    <input type="text" wire:model.live="headerText" maxlength="60" x-on:input="count = $el.value.length"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="Headline here...">
                                </div>
                            @elseif(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                <div class="space-y-4 animate-in slide-in-from-top-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Upload {{ strtolower($headerType) }} (Max 10MB)</label>
                                    
                                    <div class="relative group">
                                        <input type="file" wire:model="headerMedia" accept="{{ $headerType === 'IMAGE' ? 'image/*' : ($headerType === 'VIDEO' ? 'video/*' : 'application/pdf') }}"
                                            class="block w-full text-xs text-slate-500
                                                file:mr-4 file:py-3 file:px-6
                                                file:rounded-xl file:border-0
                                                file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                                file:bg-wa-teal/10 file:text-wa-teal
                                                hover:file:bg-wa-teal/20
                                                cursor-pointer bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-wa-teal/30 focus:ring-0
                                            "/>
                                            
                                        <div wire:loading wire:target="headerMedia" class="absolute inset-y-0 right-4 flex items-center">
                                            <svg class="animate-spin h-5 w-5 text-wa-teal" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </div>
                                    </div>
                                    @error('headerMedia') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase tracking-wide">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Message Body (Required)</label>
                                <textarea wire:model.live="body" rows="5"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-3xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all font-mono"
                                    placeholder="Enter message body... Use {{1}}, {{2}} for variables."></textarea>
                                @error('body') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase tracking-wide">{{ $message }}</span> @enderror
                            </div>

                            <div x-data="{ count: $wire.entangle('footer').live.length ?? 0 }">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Footer Text (Optional)</label>
                                    <span class="text-[9px] font-bold text-slate-400" x-text="count + '/60'"></span>
                                </div>
                                <input type="text" wire:model.live="footer" maxlength="60" x-on:input="count = $el.value.length"
                                    class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                    placeholder="Reply STOP to unsubscribe">
                            </div>
                        </section>

                        <!-- Interaction Triggers -->
                        <section class="space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-900 pb-2">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">3. Quick Actions</h4>
                                @if(count($buttons) < 10)
                                    <button type="button" wire:click="addButton" class="text-[10px] font-black text-wa-teal uppercase hover:underline">+ Add Action</button>
                                @endif
                            </div>

                            <div class="space-y-4">
                                @foreach($buttons as $index => $btn)
                                    <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800 relative group transition-all">
                                        <button type="button" wire:click="removeButton({{ $index }})" 
                                            class="absolute -top-2 -right-2 p-2 bg-rose-500 text-white rounded-xl shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Type</label>
                                                <select wire:model.live="buttons.{{ $index }}.type"
                                                    class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                                    @php
                                                        // Determine what exists
                                                        $hasQR = collect($buttons)->where('type', 'QUICK_REPLY')->where('step_index', '!=', $index)->count() > 0;
                                                        $hasCTA = collect($buttons)->whereIn('type', ['URL', 'PHONE_NUMBER', 'COPY_CODE'])->where('step_index', '!=', $index)->count() > 0;
                                                        // This specific button's current value doesn't restrict itself, but other buttons do.
                                                        // Actually simpler logic: Check ALL buttons.
                                                        $allTypes = collect($buttons)->pluck('type');
                                                        $containsQR = $allTypes->contains('QUICK_REPLY');
                                                        $containsCTA = $allTypes->intersect(['URL', 'PHONE_NUMBER', 'COPY_CODE'])->count() > 0;
                                                        
                                                        // If this button IS QR, we allow QR. If it IS CTA, we allow CTA.
                                                        // If we are switching, we check if mixing happens.
                                                        // Simplification: We already enforce initial type in addButton. Users can switch if it doesn't violate mix.
                                                        // If the set contains QR (excluding self), we disable CTA.
                                                        // If the set contains CTA (excluding self), we disable QR.
                                                        $others = collect($buttons)->except($index);
                                                        $othersHasQR = $others->contains(fn($b) => $b['type'] === 'QUICK_REPLY');
                                                        $othersHasCTA = $others->contains(fn($b) => in_array($b['type'], ['URL', 'PHONE_NUMBER', 'COPY_CODE']));
                                                    @endphp

                                                    <option value="QUICK_REPLY" @if($othersHasCTA) disabled class="text-slate-300" @endif>Quick Reply</option>
                                                    <option value="URL" @if($othersHasQR) disabled class="text-slate-300" @endif>Visit Website</option>
                                                    <option value="PHONE_NUMBER" @if($othersHasQR) disabled class="text-slate-300" @endif>Call Number</option>
                                                    <option value="COPY_CODE" @if($othersHasQR) disabled class="text-slate-300" @endif>Promotion (Copy Code)</option>
                                                    <option value="CATALOG" @if($othersHasQR) disabled class="text-slate-300" @endif>Catalog</option>
                                                    <option value="MPM" @if($othersHasQR) disabled class="text-slate-300" @endif>Multi-Product</option>
                                                </select>
                                                @if($othersHasCTA) <p class="text-[8px] text-amber-500 mt-1 font-bold">Cannot mix with Quick Reply</p> @endif
                                                @if($othersHasQR) <p class="text-[8px] text-amber-500 mt-1 font-bold">Cannot mix with CTA</p> @endif
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Label (Max 25)</label>
                                                <input type="text" wire:model.live="buttons.{{ $index }}.text" maxlength="25"
                                                    class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                    placeholder="Button text">
                                            </div>
                                            
                                            @if($buttons[$index]['type'] === 'URL')
                                                <div class="col-span-full">
                                                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Destination URL</label>
                                                    <input type="url" wire:model.live="buttons.{{ $index }}.url"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="https://">
                                                </div>
                                            @elseif($buttons[$index]['type'] === 'PHONE_NUMBER')
                                                <div class="col-span-full">
                                                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Phone (E.164)</label>
                                                    <input type="text" wire:model.live="buttons.{{ $index }}.phoneNumber"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="+1...">
                                                </div>
                                            @elseif($buttons[$index]['type'] === 'COPY_CODE')
                                                <div class="col-span-full">
                                                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Offer Code</label>
                                                    <input type="text" wire:model.live="buttons.{{ $index }}.copyCode" maxlength="15"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="SAVE20">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <!-- Variable Schema -->
                        @if(!empty($variableConfig))
                            <section class="space-y-6 animate-in slide-in-from-bottom-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">4. Variables</h4>
                                <div class="space-y-4">
                                    @foreach($variableConfig as $var => $config)
                                        <div class="p-6 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-wa-teal/20 relative overflow-hidden">
                                            <div class="absolute top-0 right-0 px-3 py-1 bg-wa-teal/10 text-wa-teal text-[10px] font-black rounded-bl-xl uppercase tracking-widest">{{ $var }}</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Variable Name</label>
                                                    <input type="text" wire:model.live="variableConfig.{{ $var }}.name"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="e.g. customer_name">
                                                    @error('variableConfig.'.$var.'.name') <span class="text-[9px] font-bold text-rose-500 mt-1 block uppercase">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Sample Content</label>
                                                    <input type="text" wire:model.live="variableConfig.{{ $var }}.sample"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="e.g. John Doe">
                                                    @error('variableConfig.'.$var.'.sample') <span class="text-[9px] font-bold text-rose-500 mt-1 block uppercase">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </div>

                <!-- Right Side: Real-Time Verification Mockup -->
                <div class="bg-slate-100 dark:bg-slate-950 p-8 md:p-12 overflow-y-auto custom-scrollbar h-full flex flex-col items-center relative shrink-0">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-10 shrink-0">Live Preview</p>
                    
                    <!-- WhatsApp Mockup Bubble -->
                    <div class="w-full max-w-[375px] my-auto bg-white dark:bg-[#0b141a] rounded-[3rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] overflow-hidden border-8 border-slate-900 dark:border-slate-800 shrink-0">
                        <!-- Mock Header -->
                        <div class="bg-[#008069] h-14 w-full px-4 flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 rounded-full"></div>
                            <div class="h-2 w-24 bg-white/20 rounded-full"></div>
                        </div>

                        <!-- Chat Background -->
                        <div class="p-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat min-h-[420px] flex flex-col bg-opacity-90"
                             style="background-color: #e5ddd5; background-image: radial-gradient(#d4d4d4 0.5px, transparent 0.5px); background-size: 20px 20px;">
                            
                            <!-- Template Message Bubble -->
                            <div class="bg-white dark:bg-[#202c33] rounded-2xl rounded-tl-none shadow-sm max-w-[95%] p-2 relative self-start mb-2">
                                <!-- Remote Header -->
                                @if($headerType !== 'NONE')
                                    <div class="mb-2">
                                        @if($headerType === 'TEXT')
                                            <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ $headerText ?: 'Your Headline' }}</h4>
                                        @else
                                            <div class="w-full aspect-video bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700">
                                                @if($headerType === 'IMAGE') <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'VIDEO') <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'LOCATION') <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                @else <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                @endif
                                                <span class="text-[8px] font-black text-slate-400 uppercase mt-2 tracking-widest">{{ $headerType }} HEADER</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Body -->
                                <p class="text-[13px] text-slate-800 dark:text-slate-200 leading-snug whitespace-pre-wrap font-sans">{!! preg_replace('/{{(\d+)}}/', '<span class="bg-slate-200 dark:bg-slate-600 px-1 rounded mx-0.5 shadow-sm border border-slate-300 dark:border-slate-500 font-mono text-[10px]">{{$1}}</span>', e($body ?: 'Hello, this is your message body...')) !!}</p>

                                <!-- Footer -->
                                @if($footer)
                                    <p class="text-[10px] text-slate-400 mt-2 pt-1 border-t border-slate-100 dark:border-slate-700">{{ $footer }}</p>
                                @endif

                                <div class="flex justify-end mt-1">
                                    <span class="text-[9px] text-slate-400">12:00 PM</span>
                                </div>
                            </div>

                            @foreach($buttons as $btn)
                                <div class="bg-white dark:bg-[#202c33] rounded-xl py-2 px-3 flex items-center justify-center gap-2 border border-white dark:border-slate-800 shadow-sm w-[95%] mb-1">
                                    @if(($btn['type'] ?? '') === 'URL') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    @elseif(($btn['type'] ?? '') === 'PHONE_NUMBER') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    @elseif(($btn['type'] ?? '') === 'COPY_CODE') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    @elseif(in_array(($btn['type'] ?? ''), ['CATALOG', 'MPM'])) <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    @endif
                                    <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest">{{ $btn['text'] ?: 'Button Label' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-8 border-t border-slate-50 dark:border-slate-900 bg-white dark:bg-slate-950 flex justify-end gap-3 z-10 shrink-0">
                <button wire:click="$set('showCreateModal', false)" 
                    class="px-8 py-3.5 bg-slate-50 dark:bg-slate-900 text-slate-500 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:bg-slate-100 transition-all border border-slate-100 dark:border-slate-800">
                    Cancel
                </button>
                <button wire:click="createTemplate" wire:loading.attr="disabled"
                    class="px-10 py-3.5 bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                    <span wire:loading.remove>Submit for Review</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Submitting...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- View Modal -->
    @if($showViewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$toggle('showViewModal')"></div>
            <div
                class="relative w-full max-w-5xl max-h-[80vh] flex flex-col bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="p-8 pb-0 shrink-0">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Template <span class="text-wa-teal">Details</span>
                    </h2>
                </div>

                <!-- Content -->
                <div class="p-8 overflow-y-scroll max-h-[500px]">
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- Form Column -->
                        <div class="flex-1">
                            <div class="space-y-6">
                                <!-- Name -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Template Name</label>
                                    <input wire:model="name" type="text" disabled
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed">
                                </div>

                                <!-- Category & Lang -->
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Category</label>
                                        <input wire:model="category" type="text" disabled
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed text-xs font-mono uppercase">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Language</label>
                                        <input wire:model="language" type="text" disabled
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed text-xs font-mono uppercase">
                                    </div>
                                </div>

                                <!-- Header -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Header</label>
                                    @if($headerType === 'TEXT')
                                        <input wire:model="headerText" type="text" disabled
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed">
                                    @else
                                        <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 font-medium italic text-sm">None</div>
                                    @endif
                                </div>

                                <!-- Body -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Body Text</label>
                                    <textarea wire:model="body" rows="6" disabled
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed resize-none"></textarea>
                                </div>

                                <!-- Footer -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Footer</label>
                                    <input wire:model="footer" type="text" disabled
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold opacity-60 cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <!-- Preview Column (Fixed) -->
                        <div class="hidden md:flex shrink-0 w-[340px] items-center justify-center bg-slate-100 dark:bg-slate-950 rounded-[2rem] p-4 border border-slate-200 dark:border-slate-800">
                             <div class="w-[300px] h-[580px] shrink-0 bg-white dark:bg-slate-900 rounded-[3rem] border-8 border-slate-800 shadow-2xl overflow-hidden relative flex flex-col transform scale-[0.85] origin-center">
                                 <!-- Phone Notch -->
                                 <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/3 h-6 bg-slate-800 rounded-b-xl z-10"></div>
                                 
                                 <!-- Phone Header -->
                                 <div class="bg-wa-teal h-16 w-full flex items-end pb-3 px-4 shadow-sm z-0">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-white/20"></div>
                                        <div>
                                            <div class="w-20 h-2 bg-white/20 rounded mb-1"></div>
                                        </div>
                                    </div>
                                 </div>

                                 <!-- Phone Screen -->
                                 <div class="flex-1 bg-[#e5ddd5] dark:bg-slate-800 p-3 overflow-y-auto bg-opacity-90 relative custom-scrollbar" 
                                      style="background-color: #e5ddd5; background-image: radial-gradient(#d4d4d4 1px, transparent 1px); background-size: 20px 20px;">
                                    
                                    <!-- Message Bubble -->
                                    <div class="bg-white dark:bg-slate-700 rounded-tr-lg rounded-br-lg rounded-bl-lg rounded-tl-none p-2 shadow-sm max-w-[90%] self-start float-left relative ml-2 mt-2">
                                        <!-- Triangle -->
                                        <div class="absolute top-0 left-[-8px] w-0 h-0 border-t-[0px] border-r-[12px] border-b-[12px] border-transparent border-r-white dark:border-r-slate-700"></div>

                                        <!-- Media Header Preview -->
                                        @if(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT', 'LOCATION']))
                                            <div class="w-full aspect-video bg-slate-100 dark:bg-slate-600 rounded-lg mb-2 flex items-center justify-center border border-slate-200 dark:border-slate-500">
                                                @if($headerType === 'IMAGE') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'VIDEO') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'LOCATION') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                @else <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                @endif
                                            </div>
                                        @endif

                                        @if($headerType === 'TEXT' && $headerText)
                                            <div class="text-[13px] font-bold text-slate-900 dark:text-white mb-1 pb-1">{{ $headerText }}</div>
                                        @endif
                                        
                                        <div class="text-[13px] text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-tight font-sans">
                                            {!! preg_replace('/{{(\d+)}}/', '<span class="bg-slate-200 dark:bg-slate-600 px-1 rounded mx-0.5 shadow-sm border border-slate-300 dark:border-slate-500 font-mono text-[10px]">{{$1}}</span>', e($body)) ?: '<span class="text-slate-400 italic">Message body...</span>' !!}
                                        </div>

                                        @if($footer)
                                            <div class="text-[10px] text-slate-500 mt-1 pt-1 opacity-75">{{ $footer }}</div>
                                        @endif
                                        
                                        <div class="text-[9px] text-slate-400 text-right mt-1">{{ now()->format('H:i') }}</div>
                                    </div>

                                    <!-- Buttons Preview -->
                                    @if(!empty($buttons))
                                        <div class="w-[90%] float-left ml-2 mt-1 space-y-1">
                                            @foreach($buttons as $btn)
                                                <div class="bg-white/90 dark:bg-slate-700/90 rounded-lg py-1.5 px-3 flex items-center justify-center gap-2 border border-white dark:border-slate-600 shadow-sm backdrop-blur-sm">
                                                    @if(($btn['type'] ?? '') === 'URL') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    @elseif(($btn['type'] ?? '') === 'PHONE_NUMBER') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    @elseif(($btn['type'] ?? '') === 'COPY_CODE') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    @elseif(in_array(($btn['type'] ?? ''), ['CATALOG', 'MPM'])) <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                    @endif
                                                    <span class="text-[11px] font-bold text-wa-teal truncate">{{ $btn['text'] ?: 'Button Label' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                 </div>
                                 
                                 <!-- Phone Footer Input -->
                                 <div class="bg-slate-100 dark:bg-slate-800 h-12 w-full flex items-center px-4 gap-2 border-t border-slate-200 dark:border-slate-700">
                                    <div class="w-6 h-6 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                                    <div class="flex-1 h-8 rounded-full bg-white dark:bg-slate-700"></div>
                                    <div class="w-6 h-6 rounded-full bg-wa-teal"></div>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex gap-4 border-t border-slate-100 dark:border-slate-800 shrink-0">
                    <button wire:click="$toggle('showViewModal')" wire:loading.attr="disabled"
                        class="w-full py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-100 dark:border-slate-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>