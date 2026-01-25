<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.email-templates.index') }}"
                    class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:scale-105 transition-transform text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                        Edit <span class="text-indigo-600">Template</span>
                    </h1>
                    <p class="text-slate-500 font-medium tracking-tight">
                        Editing: <span
                            class="font-bold border-b border-indigo-300 border-dashed">{{ $template->name }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button wire:click="$set('activeTab', 'edit')"
                    class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-colors {{ $activeTab === 'edit' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-white text-slate-500 hover:bg-slate-50' }}">
                    Editor
                </button>
                <button wire:click="loadPreview"
                    class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-colors {{ $activeTab === 'preview' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-white text-slate-500 hover:bg-slate-50' }}">
                    Preview
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Editor / Preview Area -->
            <div class="lg:col-span-2">
                @if($activeTab === 'edit')
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 space-y-6">

                        <!-- Subject -->
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Email Subject</label>
                            <input type="text" wire:model="subject"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('subject') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>

                        <!-- HTML Content -->
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">HTML Content</label>
                            <div class="relative">
                                <textarea wire:model="content_html" rows="15"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white leading-relaxed"></textarea>
                                <div
                                    class="absolute top-2 right-2 text-[10px] text-slate-400 font-mono bg-white/50 px-2 py-1 rounded">
                                    HTML5</div>
                            </div>
                            @error('content_html') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Text Content -->
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Plain Text
                                Fallback</label>
                            <textarea wire:model="content_text" rows="5"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white leading-relaxed"></textarea>
                            @error('content_text') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="update" wire:loading.attr="disabled"
                                class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50">
                                <span wire:loading.remove>Save Changes</span>
                                <span wire:loading>Saving...</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Preview Mode -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div
                            class="bg-slate-100 dark:bg-slate-950 p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col gap-2">
                            <div class="text-xs text-slate-500 font-mono">Subject: <span
                                    class="text-slate-900 dark:text-white font-bold">{{ $previewSubject }}</span></div>
                            <div class="text-xs text-slate-500 font-mono">To: <span
                                    class="text-slate-900 dark:text-white font-bold">user@example.com</span></div>
                        </div>
                        <div class="p-8 bg-white">
                            <iframe srcdoc="{{ $previewHtml }}"
                                class="w-full h-[600px] border border-slate-100 rounded-lg"></iframe>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                    <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-6">Template Constraints
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <span
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Status</span>
                            @if($template->is_locked)
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1 bg-rose-50 text-rose-600 text-xs font-black uppercase tracking-wider rounded-lg border border-rose-100">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Locked System Template
                                </span>
                                <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">
                                    Slug and variables are locked to prevent system failures. You can only edit content and
                                    subject.
                                </p>
                            @else
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-600 text-xs font-black uppercase tracking-wider rounded-lg border border-green-100">
                                    Editable
                                </span>
                            @endif
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <span
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Allowed
                                Variables</span>
                            <div class="flex flex-wrap gap-2">
                                @forelse($template->variable_schema ?? [] as $var)
                                    <code
                                        class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded border border-slate-200 font-mono cursor-help"
                                        title="Use as {{ '{{ ' . $var . ' }}' }}">
                                            {{ $var }}
                                        </code>
                                @empty
                                    <span class="text-xs text-slate-400 italic">No variables defined</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                @if($activeTab === 'preview')
                    <div class="bg-indigo-900 rounded-[2.5rem] shadow-xl p-8 text-white">
                        <h3 class="text-xs font-black text-indigo-300 uppercase tracking-widest mb-4">Preview Data</h3>
                        <pre
                            class="text-[10px] font-mono text-indigo-100 overflow-x-auto whitespace-pre-wrap">{{ json_encode($previewData, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>