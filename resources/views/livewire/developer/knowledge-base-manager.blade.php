<div class="space-y-8 animate-in fade-in duration-500" x-data="{ activeTab: 'file' }">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Business <span
                        class="text-indigo-600">Brain</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Teach your AI about your business by adding documents, websites, or
                text.</p>
        </div>
        <div class="flex gap-3">
            <x-action-message class="mr-3 flex items-center" on="saved">
                <span
                    class="text-indigo-600 font-bold text-xs uppercase tracking-widest">{{ __('Changes Saved') }}</span>
            </x-action-message>
        </div>
    </div>

    <!-- Quick Add Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Input Card -->
        <div
            class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Add
                        Information</h2>
                    <p class="text-sm text-indigo-600 font-bold uppercase tracking-wider mt-1">Source Selection</p>
                </div>
                <!-- Tabs -->
                <div class="flex bg-slate-50 dark:bg-slate-800 p-1 rounded-2xl">
                    <button @click="activeTab = 'file'"
                        :class="activeTab === 'file' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600' : 'text-slate-500'"
                        class="px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all">File</button>
                    <button @click="activeTab = 'url'"
                        :class="activeTab === 'url' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600' : 'text-slate-500'"
                        class="px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all">Website</button>
                    <button @click="activeTab = 'text'"
                        :class="activeTab === 'text' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600' : 'text-slate-500'"
                        class="px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all">Raw
                        Text</button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="space-y-6">
                <!-- Name Field (Common) -->
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Display
                        Name</label>
                    <input type="text" wire:model="name" placeholder="e.g. Pricing List, Service FAQs"
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white">
                    <x-input-error for="name" />
                </div>

                <!-- File Upload -->
                <div x-show="activeTab === 'file'" class="animate-in fade-in zoom-in-95 duration-200">
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Select PDF
                        or TXT</label>
                    <div class="relative group">
                        <label
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[1.5rem] cursor-pointer bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-2 text-slate-400 group-hover:text-indigo-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-xs font-bold text-slate-500">
                                    {{ is_object($file) ? $file->getClientOriginalName() : 'Click to upload or drag and drop' }}
                                </p>
                            </div>
                            <input type="file" wire:model="file" class="hidden" />
                        </label>
                    </div>
                    <x-input-error for="file" />

                    <button wire:click="uploadFile" wire:loading.attr="disabled"
                        class="w-full mt-6 py-4 bg-slate-900 dark:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.01] active:scale-95 transition-all">
                        <span wire:loading.remove wire:target="uploadFile">Process Document</span>
                        <span wire:loading wire:target="uploadFile">Analyzing Content...</span>
                    </button>
                </div>

                <!-- URL Import -->
                <div x-show="activeTab === 'url'" class="animate-in fade-in zoom-in-95 duration-200"
                    style="display: none;">
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Website
                        Address (URL)</label>
                    <input type="url" wire:model="url" placeholder="https://example.com/help"
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white">
                    <x-input-error for="url" />

                    <button wire:click="addUrl" wire:loading.attr="disabled"
                        class="w-full mt-6 py-4 bg-slate-900 dark:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.01] active:scale-95 transition-all">
                        <span wire:loading.remove wire:target="addUrl">Crawl Website</span>
                        <span wire:loading wire:target="addUrl">Reading Website...</span>
                    </button>
                </div>

                <!-- Raw Text -->
                <div x-show="activeTab === 'text'" class="animate-in fade-in zoom-in-95 duration-200"
                    style="display: none;">
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Copy-Paste
                        Information</label>
                    <textarea wire:model="rawText" rows="6"
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-[1.5rem] text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white resize-none"
                        placeholder="Paste business rules, facts, or instructions here..."></textarea>
                    <x-input-error for="rawText" />

                    <button wire:click="addText" wire:loading.attr="disabled"
                        class="w-full mt-6 py-4 bg-slate-900 dark:bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl hover:scale-[1.01] active:scale-95 transition-all">
                        <span wire:loading.remove wire:target="addText">Save Text</span>
                        <span wire:loading wire:target="addText">Saving...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats/Info Card -->
        <div class="bg-indigo-600 rounded-[2.5rem] shadow-xl p-8 text-white">
            <h3 class="text-xl font-black uppercase tracking-tight mb-4">How it works</h3>
            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <span class="font-black">1</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider mb-1">Add Content</p>
                        <p class="text-xs text-indigo-100 leading-relaxed font-medium">Upload FAQs, product details, or
                            website URLs that contain your business information.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <span class="font-black">2</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider mb-1">AI Learns</p>
                        <p class="text-xs text-indigo-100 leading-relaxed font-medium">We process the text and break it
                            down so your AI assistant can understand it instantly.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <span class="font-black">3</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider mb-1">Smart Replies</p>
                        <p class="text-xs text-indigo-100 leading-relaxed font-medium">In your automations, the AI will
                            use this "Brain" to answer complex user questions.</p>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-white/10">
                <div class="text-center">
                    <p class="text-4xl font-black">{{ count($sources) }}</p>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Total Information Sources</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Information List -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Active
                    Information</h2>
                <p class="text-sm text-slate-500 font-bold uppercase tracking-wider mt-1">Managed Knowledge</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($sources as $source)
                <div
                    class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-transparent hover:border-indigo-500/20 transition-all group relative">
                <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                    @if($source->type !== 'text')
                    <button wire:click="reprocessSource({{ $source->id }})" wire:loading.attr="disabled" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors" title="Refresh Information">
                        <svg class="w-4 h-4" :class="{'animate-spin': $wire.loading && $wire.target.includes('reprocessSource({{ $source->id }})')}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    @endif
                    <button wire:click="showPreview({{ $source->id }})" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors" title="Preview">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                    @if($source->type !== 'url')
                    <button wire:click="editSource({{ $source->id }})" class="p-2 text-slate-400 hover:text-amber-600 transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    @endif
                    <button wire:click="deleteSource({{ $source->id }})" class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-white dark:bg-slate-700 rounded-2xl shadow-sm text-indigo-600">
                            @if($source->type === 'file')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @elseif($source->type === 'url')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 dark:text-white text-sm uppercase leading-tight">
                                {{ $source->name }}</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                {{ $source->type }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-[10px] uppercase font-black tracking-widest">
                            <span class="text-slate-400">Content Size</span>
                            <span class="text-slate-900 dark:text-slate-300">{{ number_format(strlen($source->content)) }}
                                Characters</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] uppercase font-black tracking-widest">
                            <span class="text-slate-400">Added</span>
                            <span
                                class="text-slate-900 dark:text-slate-300">{{ $source->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">Learned by
                                    AI</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 flex flex-col items-center justify-center text-center">
                    <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-[2.5rem] text-slate-300 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m4.688 4.406L12 17.03l3.313-2.625" />
                        </svg>
                    </div>
                    <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight">Your Brain is Empty</h3>
                    <p class="text-sm text-slate-500 font-medium">Add some information above to get started.</p>
                </div>
            @endforelse
        </div>
    </div>
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="fixed bottom-8 right-8 z-50 animate-in slide-in-from-right-10 duration-500">
            <div class="bg-indigo-600 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-4 border border-white/20 backdrop-blur-xl">
                 <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-xs font-black uppercase tracking-widest">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Preview/Edit Modal -->
    @if($showModal)
    @teleport('body')
    <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-100 dark:border-slate-800 animate-in zoom-in-95 duration-200">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight" id="modal-title">
                                {{ $modalMode === 'edit' ? 'Edit Information' : 'Preview Information' }}
                            </h3>
                            <p class="text-sm text-indigo-600 font-bold uppercase tracking-wider mt-1">{{ $editingName }}</p>
                        </div>
                        <button wire:click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        @if($modalMode === 'edit')
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Display Name</label>
                            <input type="text" wire:model="editingName" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white">
                        </div>
                        @endif

                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block tracking-widest">Content</label>
                            @if($modalMode === 'edit')
                            <textarea wire:model="editingContent" rows="15" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-[1.5rem] text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-white resize-none leading-relaxed"></textarea>
                            @else
                            <div class="w-full px-8 py-6 bg-slate-50 dark:bg-slate-800 rounded-[1.5rem] text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed max-h-[60vh] overflow-y-auto whitespace-pre-wrap">
                                @if($editingType === 'url')
                                    <div class="py-4">
                                        <div class="overflow-hidden border border-slate-200 dark:border-slate-800 rounded-2xl">
                                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Type</th>
                                                        <th class="px-6 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Website Address (URL)</th>
                                                        <th class="px-6 py-3 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 py-1 text-[9px] font-black uppercase tracking-widest rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50">Website</span>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <div class="text-sm font-bold text-slate-900 dark:text-white break-all">{{ App\Models\KnowledgeBaseSource::find($editingId)?->path }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                                            <a href="{{ App\Models\KnowledgeBaseSource::find($editingId)?->path }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700 transition-colors">
                                                                Visit
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 012 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                </svg>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 rounded-2xl flex gap-3">
                                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-xs font-medium text-amber-700 dark:text-amber-400">Website content is extracted automatically and cannot be edited manually. To update the information, please remove and re-add the URL.</p>
                                        </div>
                                    </div>
                                @else
                                    {{ $editingContent }}
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3">
                        <button wire:click="closeModal" class="px-8 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-200 transition-all">
                            Close
                        </button>
                        @if($modalMode === 'edit')
                        <button wire:click="saveEdit" class="px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Save Changes
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>