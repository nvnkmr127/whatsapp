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
                        Create <span class="text-indigo-600">Template</span>
                    </h1>
                    <p class="text-slate-500 font-medium tracking-tight">Design a new system or marketing email
                        template.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 space-y-6">

                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Template
                                Name</label>
                            <input type="text" wire:model="name"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                placeholder="e.g. Welcome Email">
                            @error('name') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Use Case
                                Type</label>
                            <select wire:model="type"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                @foreach($types as $enum)
                                    <option value="{{ $enum->value }}">{{ $enum->name }}</option>
                                @endforeach
                            </select>
                            @error('type') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Unique Key
                            (Slug)</label>
                        <input type="text" wire:model="slug"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            placeholder="e.g. user-welcome-v1">
                        <p class="text-[10px] text-slate-400">Used by the system to identify this template. Must be
                            unique.</p>
                        @error('slug') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Description</label>
                        <input type="text" wire:model="description"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            placeholder="Internal notes about when this is sent...">
                        @error('description') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                    <!-- Subject -->
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Email Subject</label>
                        <input type="text" wire:model="subject"
                            class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            placeholder="Welcome @{{ name }}!">
                        @error('subject') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <!-- HTML Content -->
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">HTML Content</label>
                        <div class="relative">
                            <textarea wire:model="content_html" rows="15"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white leading-relaxed"
                                placeholder="<h1>Hello @{{ name }}</h1>..."></textarea>
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
                        <button wire:click="store" wire:loading.attr="disabled"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95 disabled:opacity-50">
                            <span wire:loading.remove>Create Template</span>
                            <span wire:loading>Creating...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                    <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-6">Variable Schema</h3>

                    <div class="space-y-4">
                        <p class="text-[10px] text-slate-400 leading-relaxed">
                            Define the variables allowed in this template. Any variable not listed here will be rejected
                            by the renderer to ensure strict type safety.
                        </p>

                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Allowed
                                Variables</label>
                            <input type="text" wire:model="variable_schema_input"
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                placeholder="name, email, order_id">
                            <p class="text-[10px] text-slate-400">Comma separated, e.g. <code>name, code</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>