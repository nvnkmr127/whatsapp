<div class="p-8 space-y-8 bg-slate-50/50 dark:bg-slate-950 min-h-screen">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Template <span class="text-wa-teal">Control</span></h1>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-[0.2em] mt-1">Manage and deploy approved message protocols</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="syncTemplates" wire:loading.attr="disabled"
                class="px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm">
                <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync Status
            </button>
            <button wire:click="$set('showCreateModal', true)"
                class="px-8 py-3 bg-wa-teal hover:bg-wa-teal/90 text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                New Template
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session()->has('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800 p-4 rounded-2xl flex items-center gap-3 animate-in fade-in slide-in-from-top-2">
            <div class="p-1.5 bg-emerald-500 rounded-full text-white">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
            </div>
            <p class="text-xs font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Template Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($templates as $tpl)
            <div class="group bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm hover:shadow-2xl transition-all border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col relative">
                <!-- Status Badge -->
                <div class="absolute top-6 right-6 z-10">
                    <span class="px-2.5 py-1 text-[9px] font-black rounded-lg uppercase tracking-widest border
                        {{ $tpl->status === 'APPROVED' ? 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800' :
                           ($tpl->status === 'REJECTED' ? 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-900/20 dark:border-rose-800' : 
                           'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800') }}">
                        {{ $tpl->status }}
                    </span>
                </div>

                <div class="p-8 pb-4">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight truncate pr-20">{{ $tpl->name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest">{{ $tpl->category }}</span>
                        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $tpl->language }}</span>
                    </div>
                </div>

                <div class="px-8 pb-8 flex-1">
                    <div class="bg-slate-50 dark:bg-slate-950 rounded-3xl p-5 h-48 overflow-y-auto custom-scrollbar border border-slate-100 dark:border-slate-800/50">
                        @foreach($tpl->components as $comp)
                            @if($comp['type'] === 'HEADER')
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 border-b border-slate-200 dark:border-slate-800 pb-1">Header: {{ $comp['format'] }}</div>
                                @if($comp['format'] === 'TEXT')
                                    <p class="text-xs font-black text-slate-800 dark:text-white mb-4">{{ $comp['text'] }}</p>
                                @else
                                    <div class="w-full h-20 bg-slate-200 dark:bg-slate-800 rounded-xl mb-4 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            @elseif($comp['type'] === 'BODY')
                                <p class="text-xs font-medium text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">{{ $comp['text'] }}</p>
                            @elseif($comp['type'] === 'FOOTER')
                                <p class="text-[10px] font-bold text-slate-400 mt-4 border-t border-slate-100 dark:border-slate-800 pt-2 italic">{{ $comp['text'] }}</p>
                            @elseif($comp['type'] === 'BUTTONS')
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($comp['buttons'] as $btn)
                                        <span class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[9px] font-black text-wa-teal uppercase tracking-widest shadow-sm">
                                            {{ $btn['text'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Readiness Section -->
                <div class="px-8 pb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Readiness Check</span>
                        <span class="text-[10px] font-black {{ $tpl->readiness_score >= 90 ? 'text-emerald-500' : ($tpl->readiness_score >= 70 ? 'text-amber-500' : 'text-rose-500') }}">
                            {{ $tpl->readiness_score }}%
                        </span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ $tpl->readiness_score >= 90 ? 'bg-emerald-500' : ($tpl->readiness_score >= 70 ? 'bg-amber-500' : 'bg-rose-500') }} transition-all" style="width: {{ $tpl->readiness_score }}%"></div>
                    </div>
                    
                    @if(!empty($tpl->validation_results))
                        <div class="mt-3 space-y-1.5">
                            @foreach($tpl->validation_results as $error)
                                <div class="flex items-start gap-2 text-[9px] {{ ($error['severity'] ?? '') === 'fatal' ? 'text-rose-500' : 'text-amber-500' }} font-bold leading-tight uppercase tracking-wider">
                                    <svg class="w-2.5 h-2.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    <span>{{ $error['description'] ?? ($error['message'] ?? 'Unknown mismatch') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">ID: {{ substr($tpl->whatsapp_template_id, 0, 8) }}...</span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-24 text-center bg-white dark:bg-slate-900 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 text-slate-200 dark:text-slate-700">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">No Templates Found</h3>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-2">Establish a protocol to begin automated communications</p>
            </div>
        @endforelse
    </div>

    <!-- Create Modal (Enhanced) -->
    @if($showCreateModal)
    @teleport('body')
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('showCreateModal', false)">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
        <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[95vh] animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="p-8 border-b border-slate-50 dark:border-slate-900 flex justify-between items-center bg-white dark:bg-slate-950 z-10">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Protocol <span class="text-wa-teal">Designer</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Configure complex message structures for Meta Approval</p>
                </div>
                <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-rose-500 p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-2">
                <!-- Left Side: Configuration Form -->
                <div class="p-10 overflow-y-auto custom-scrollbar border-r border-slate-50 dark:border-slate-900">
                    <div class="space-y-10">
                        <!-- Identity Section -->
                        <section class="space-y-6">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">1. Registry Identity</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Protocol Name</label>
                                    <input type="text" wire:model="name" 
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all placeholder:text-slate-400"
                                        placeholder="e.g. shipping_update_v1">
                                    @error('name') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase tracking-wide">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Classification</label>
                                    <select wire:model="category"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                        <option value="UTILITY">Utility (Service)</option>
                                        <option value="MARKETING">Marketing (Promotion)</option>
                                        <option value="AUTHENTICATION">Authentication (Security)</option>
                                    </select>

                                    <!-- Category Hints (UC-08) -->
                                    <div class="mt-3 p-3 bg-slate-100/50 dark:bg-slate-900/50 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
                                        @if($category === 'AUTHENTICATION')
                                            <p class="text-[9px] font-bold text-amber-600 dark:text-amber-500 uppercase tracking-widest leading-relaxed">
                                                <svg class="w-3 h-3 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                Strict: OTP only. Media & Custom Buttons disallowed. Requires Disclaimer footer.
                                            </p>
                                        @elseif($category === 'MARKETING')
                                            <p class="text-[9px] font-bold text-wa-teal uppercase tracking-widest leading-relaxed">
                                                <svg class="w-3 h-3 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                Flexible: For promos & news. Enforces marketing opt-in flag before sending.
                                            </p>
                                        @else
                                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed">
                                                <svg class="w-3 h-3 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                Safe: For orders & alerts. Restricted to non-promotional content.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Language</label>
                                    <select wire:model="language"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                        @foreach($languages as $code => $label)
                                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                                        @endforeach
                                    </select>
                                    @error('language') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase tracking-wide">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </section>

                        <!-- Content Section -->
                        <section class="space-y-6">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">2. Payload Architecture</h4>
                            
                            <!-- Header Toggle -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Header Component</label>
                                <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
                                    @foreach(['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'] as $type)
                                        <button wire:click="$set('headerType', '{{ $type }}')"
                                            class="px-3 py-3 rounded-xl border-2 text-[10px] font-black uppercase tracking-widest transition-all {{ $headerType === $type ? 'border-wa-teal bg-wa-teal/5 text-wa-teal shadow-lg shadow-wa-teal/5' : 'border-slate-100 dark:border-slate-900 text-slate-400 hover:border-slate-200' }}">
                                            {{ $type }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if($headerType === 'TEXT')
                                <div class="animate-in slide-in-from-top-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Header Text (Max 60)</label>
                                    <input type="text" wire:model="headerText" maxlength="60"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="Headline here...">
                                </div>
                            @elseif(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                <div class="p-6 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center animate-in slide-in-from-top-2">
                                    <div class="h-12 w-12 rounded-2xl bg-wa-teal/10 text-wa-teal flex items-center justify-center mb-3">
                                        @if($headerType === 'IMAGE') <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        @elseif($headerType === 'VIDEO') <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        @else <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Media Header Enabled</span>
                                    <p class="text-[9px] text-slate-400 mt-1 italic text-center">You will select the specific file when sending this template.</p>
                                </div>
                            @endif

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Message Body (Required)</label>
                                <textarea wire:model="body" rows="5"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-3xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                    placeholder="Enter your main message... Use {{1}}, {{2}} for dynamic variables."></textarea>
                                @error('body') <span class="text-[10px] font-bold text-rose-500 mt-2 block uppercase">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Internal Footer (Optional)</label>
                                <input type="text" wire:model="footer" maxlength="60"
                                    class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                    placeholder="Reply STOP to opt-out">
                            </div>
                        </section>

                        <!-- Buttons Section -->
                        <section class="space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-900 pb-2">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">3. Interactive Triggers</h4>
                                @if(count($buttons) < 3)
                                    <button type="button" wire:click="addButton" class="text-[10px] font-black text-wa-teal uppercase hover:underline">+ Add Interaction</button>
                                @endif
                            </div>

                            <div class="space-y-4">
                                @foreach($buttons as $index => $btn)
                                    <!-- (Existing Button HTML...) -->
                                    <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800 relative group animate-in slide-in-from-left-2 transition-all">
                                        <button type="button" wire:click="removeButton({{ $index }})" 
                                            class="absolute -top-2 -right-2 p-2 bg-rose-500 text-white rounded-xl shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Interaction Type</label>
                                                <select wire:model="buttons.{{ $index }}.type"
                                                    class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                                                    <option value="QUICK_REPLY">Quick Reply</option>
                                                    <option value="URL">Visit Website</option>
                                                    <option value="PHONE_NUMBER">Call Number</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Display Label (Max 25)</label>
                                                <input type="text" wire:model="buttons.{{ $index }}.text" maxlength="25"
                                                    class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                    placeholder="Button text...">
                                            </div>
                                            
                                            @if($buttons[$index]['type'] === 'URL')
                                                <div class="col-span-full">
                                                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Destinaton URL</label>
                                                    <input type="url" wire:model="buttons.{{ $index }}.url"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="https://example.com/promo">
                                                </div>
                                            @elseif($buttons[$index]['type'] === 'PHONE_NUMBER')
                                                <div class="col-span-full">
                                                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2">Phone Number (E.164)</label>
                                                    <input type="text" wire:model="buttons.{{ $index }}.phoneNumber"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                                        placeholder="+15551234567">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if(empty($buttons))
                                    <p class="text-[10px] text-slate-400 italic text-center py-4 uppercase font-bold tracking-widest opacity-50">No interactive components configured</p>
                                @endif
                            </div>
                        </section>

                        <!-- Variable Schema Section -->
                        @if(!empty($variableConfig))
                            <section class="space-y-6 animate-in slide-in-from-bottom-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-900 pb-2">4. Variable Schema (Required)</h4>
                                <div class="space-y-4">
                                    @foreach($variableConfig as $var => $config)
                                        <div class="p-6 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-wa-teal/20 shadow-sm relative overflow-hidden">
                                            <div class="absolute top-0 right-0 px-3 py-1 bg-wa-teal/10 text-wa-teal text-[10px] font-black rounded-bl-xl uppercase tracking-widest">{{ $var }}</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Internal Variable Name</label>
                                                    <input type="text" wire:model="variableConfig.{{ $var }}.name"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-300"
                                                        placeholder="e.g. customer_name">
                                                    @error('variableConfig.'.$var.'.name') <span class="text-[9px] font-bold text-rose-500 mt-1 block uppercase">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Data Type</label>
                                                    <select wire:model="variableConfig.{{ $var }}.type"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20">
                                                        <option value="TEXT">Text (Default)</option>
                                                        <option value="CURRENCY">Currency</option>
                                                        <option value="DATE_TIME">Date/Time</option>
                                                        <option value="IMAGE_URL">Image URL</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Fallback Value (Optional)</label>
                                                    <input type="text" wire:model="variableConfig.{{ $var }}.fallback"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-300"
                                                        placeholder="e.g. Valued Customer">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Sample Content (For Approval)</label>
                                                    <input type="text" wire:model="variableConfig.{{ $var }}.sample"
                                                        class="w-full px-4 py-2 bg-white dark:bg-slate-950 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-300"
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
                <div class="bg-slate-100 dark:bg-slate-900/50 p-12 flex flex-col items-center justify-center overflow-y-auto">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-10">Verification Preview</p>
                    
                    <!-- WhatsApp Mockup Bubble -->
                    <div class="w-full max-w-[320px] bg-white dark:bg-[#0b141a] rounded-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.2)] overflow-hidden border border-white dark:border-slate-800">
                        <!-- Mock Header -->
                        <div class="bg-[#008069] h-12 w-full px-4 flex items-center gap-3">
                            <div class="w-7 h-7 bg-white/20 rounded-full"></div>
                            <div class="h-2 w-20 bg-white/20 rounded-full"></div>
                        </div>

                        <!-- Chat Background -->
                        <div class="p-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat min-h-[400px] flex flex-col">
                            
                            <!-- Template Message Bubble -->
                            <div class="bg-white dark:bg-[#202c33] rounded-2xl rounded-tl-none shadow-sm max-w-[95%] p-3 relative transform hover:scale-[1.02] transition-transform">
                                <!-- Remote Header -->
                                @if($headerType !== 'NONE')
                                    <div class="mb-2">
                                        @if($headerType === 'TEXT')
                                            <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ $headerText ?: 'Your Headline' }}</h4>
                                        @else
                                            <div class="w-full aspect-video bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700">
                                                @if($headerType === 'IMAGE') <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'VIDEO') <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                @else <svg class="w-6 h-6 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                @endif
                                                <span class="text-[8px] font-black text-slate-400 uppercase mt-2 tracking-widest">{{ $headerType }} HEADER</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Body -->
                                <p class="text-[11px] text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ $this->previewBody ?: 'Hello, this is your message body...' }}</p>

                                <!-- Footer -->
                                @if($footer)
                                    <p class="text-[9px] text-slate-400 mt-2 italic border-t border-slate-50 dark:border-slate-800 pt-1 tracking-tight">{{ $footer }}</p>
                                @endif

                                <div class="flex justify-end mt-1">
                                    <span class="text-[8px] text-slate-400">12:00 PM</span>
                                </div>
                            </div>

                            <!-- Mock Buttons -->
                            @if(!empty($buttons))
                                <div class="mt-2 space-y-1.5 w-full max-w-[95%]">
                                    @foreach($buttons as $btn)
                                        <div class="bg-white dark:bg-[#202c33] rounded-xl py-2.5 px-3 flex items-center justify-center gap-2 border border-white dark:border-slate-800 shadow-sm">
                                            @if($btn['type'] === 'URL') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            @elseif($btn['type'] === 'PHONE_NUMBER') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            @endif
                                            <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest text-center">{{ $btn['text'] ?: 'Action' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-10 p-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800 max-w-xs text-center">
                        <p class="text-[10px] text-amber-600 dark:text-amber-500 font-bold leading-relaxed uppercase tracking-wide">Templates are usually reviewed by Meta within 24-48 hours. Ensure content follows Business Policy.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-8 border-t border-slate-50 dark:border-slate-900 bg-white dark:bg-slate-950 flex justify-end gap-3 z-10">
                <button wire:click="$set('showCreateModal', false)" 
                    class="px-8 py-3.5 bg-slate-50 dark:bg-slate-900 text-slate-500 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:bg-slate-100 transition-all border border-slate-100 dark:border-slate-800">
                    Cancel
                </button>
                <button wire:click="createTemplate" wire:loading.attr="disabled"
                    class="px-10 py-3.5 bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                    <span wire:loading.remove>Submit to Meta</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Deploying...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>