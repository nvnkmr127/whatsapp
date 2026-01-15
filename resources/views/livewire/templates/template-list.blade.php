<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">WhatsApp <span
                        class="text-wa-green">Templates</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage and sync your WhatsApp message templates.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
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
                Sync Templates
            </button>

            <button wire:click="openCreateModal" class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Template
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
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all font-medium"
                    placeholder="Search templates...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-green transition-colors"
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
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse ($templates as $template)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <button wire:click="viewTemplate({{ $template->id }})" class="text-left group-hover:text-wa-green transition-colors focus:outline-none">
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
                                                @if($template->status === 'APPROVED') bg-wa-green/10 text-wa-green
                                                @elseif($template->status === 'REJECTED') bg-rose-500/10 text-rose-500
                                                @else bg-amber-500/10 text-amber-500 @endif border border-current/10 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $template->status }}
                                    </div>
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

    <!-- Create Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$toggle('showCreateModal')"></div>
            <div
                class="relative w-full max-w-5xl max-h-[80vh] flex flex-col bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="p-8 pb-0 shrink-0">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Create New <span class="text-wa-teal">Template</span>
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
                                    <input wire:model="name" type="text"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="e.g. welcome_offer">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Lowercase, underscores only.</p>
                                    @error('name') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                                </div>

                                <!-- Category & Lang -->
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Category</label>
                                        <select wire:model="category"
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                            <option value="UTILITY">Utility</option>
                                            <option value="MARKETING">Marketing</option>
                                            <option value="AUTHENTICATION">Authentication</option>
                                        </select>
                                        @error('category') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Language</label>
                                        <select wire:model="language"
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                            <option value="en_US">English (US)</option>
                                            <option value="es_ES">Spanish</option>
                                        </select>
                                        @error('language') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Header -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Header (Optional)</label>
                                    <select wire:model.live="headerType"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer mb-2">
                                        <option value="NONE">None</option>
                                        <option value="TEXT">Text</option>
                                        <option value="IMAGE">Image</option>
                                        <option value="VIDEO">Video</option>
                                        <option value="DOCUMENT">Document</option>
                                    </select>
                                    @if ($headerType === 'TEXT')
                                        <input wire:model.live="headerText" type="text"
                                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                            placeholder="Header text...">
                                        @error('headerText') <span class="text-rose-500 text-[10px] font-black uppercase">{{ $message }}</span> @enderror
                                    @endif

                                    @if (in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                        <div class="space-y-2 mt-2">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Example {{ strtolower($headerType) }} URL (Required by Meta)</label>
                                            <input wire:model.live="exampleMediaUrl" type="url"
                                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                                placeholder="https://example.com/sample.{{ $headerType === 'IMAGE' ? 'jpg' : ($headerType === 'VIDEO' ? 'mp4' : 'pdf') }}">
                                            <p class="text-[9px] text-slate-400 font-bold uppercase italic">This is only an example used for Meta's approval process.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Body -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Body Text</label>
                                    <textarea wire:model.live="body" rows="6"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 resize-none"
                                        placeholder="Hello {{1}}, welome to..."></textarea>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Use {{1}}, {{2}} for variables.</p>
                                    @error('body') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                                </div>

                                <!-- Footer -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Footer (Optional)</label>
                                    <input wire:model.live="footer" type="text"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                        placeholder="Footer text...">
                                </div>

                                <!-- Buttons Section -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Buttons ({{ count($buttons) }}/3)</label>
                                        @if(count($buttons) < 3)
                                            <button type="button" wire:click="addButton" class="text-[10px] font-black text-wa-teal uppercase hover:underline">+ Add Button</button>
                                        @endif
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($buttons as $index => $btn)
                                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 relative group">
                                                <button type="button" wire:click="removeButton({{ $index }})" 
                                                    class="absolute top-2 right-2 p-1.5 bg-rose-500 text-white rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="space-y-1">
                                                        <label class="text-[9px] font-black text-slate-400 uppercase">Type</label>
                                                        <select wire:model.live="buttons.{{ $index }}.type"
                                                            class="w-full px-3 py-2 bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold">
                                                            <option value="QUICK_REPLY">Quick Reply</option>
                                                            <option value="URL">URL</option>
                                                            <option value="PHONE_NUMBER">Phone</option>
                                                        </select>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[9px] font-black text-slate-400 uppercase">Label</label>
                                                        <input type="text" wire:model.live="buttons.{{ $index }}.text" maxlength="25"
                                                            class="w-full px-3 py-2 bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold"
                                                            placeholder="Button text">
                                                    </div>
                                                    
                                                    @if($buttons[$index]['type'] === 'URL')
                                                        <div class="col-span-full space-y-1">
                                                            <label class="text-[9px] font-black text-slate-400 uppercase">URL</label>
                                                            <input type="url" wire:model.live="buttons.{{ $index }}.url"
                                                                class="w-full px-3 py-2 bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold"
                                                                placeholder="https://">
                                                        </div>
                                                    @elseif($buttons[$index]['type'] === 'PHONE_NUMBER')
                                                        <div class="col-span-full space-y-1">
                                                            <label class="text-[9px] font-black text-slate-400 uppercase">Phone</label>
                                                            <input type="text" wire:model.live="buttons.{{ $index }}.phoneNumber"
                                                                class="w-full px-3 py-2 bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold"
                                                                placeholder="+1...">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
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
                                        @if(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                            <div class="w-full aspect-video bg-slate-100 dark:bg-slate-600 rounded-lg mb-2 flex items-center justify-center border border-slate-200 dark:border-slate-500">
                                                @if($headerType === 'IMAGE') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'VIDEO') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
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
                    <button wire:click="$toggle('showCreateModal')" wire:loading.attr="disabled"
                        class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-100 dark:border-slate-700">
                        Cancel
                    </button>
                    <button wire:click="createTemplate" wire:loading.attr="disabled"
                        class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Create Template
                    </button>
                </div>
            </div>
        </div>
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
                                        @if(in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT']))
                                            <div class="w-full aspect-video bg-slate-100 dark:bg-slate-600 rounded-lg mb-2 flex items-center justify-center border border-slate-200 dark:border-slate-500">
                                                @if($headerType === 'IMAGE') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @elseif($headerType === 'VIDEO') <svg class="w-8 h-8 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
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