<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Category <span
                        class="text-wa-teal">Center</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Organize your products and contacts with custom categories and visual
                identifiers.</p>
        </div>
        <button wire:click="openCreateModal"
            class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
            </svg>
            Create Category
        </button>
    </div>

    <!-- Feedback Messages -->
    @if (session()->has('message'))
        <div
            class="animate-in slide-in-from-top-2 duration-300 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 px-6 py-4 rounded-2xl font-bold flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="animate-in slide-in-from-top-2 duration-300 bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 px-6 py-4 rounded-2xl font-bold flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Content Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <!-- Search & Filter Bar -->
        <div
            class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="relative group w-full max-w-xl">
                <input wire:model.live="searchTerm" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                    placeholder="Search categories...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach(['' => 'All Areas', 'contacts' => 'Contacts', 'products' => 'Products', 'all' => 'Global'] as $val => $label)
                    <button wire:click="$set('filterModule', '{{ $val }}')"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $filterModule === $val ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Identity &
                            Branding</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Statistics
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status
                        </th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($categories as $category)
                        <tr wire:key="cat-{{ $category->id }}"
                            class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-[1rem] flex items-center justify-center text-2xl shadow-sm border border-slate-100 dark:border-slate-800"
                                        style="background-color: {{ $category->color }}15; color: {{ $category->color }};">
                                        {{ $category->icon ?: '📁' }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <div
                                                class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                {{ $category->name }}
                                            </div>
                                            <span
                                                class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-[8px] font-black uppercase tracking-widest text-slate-400 rounded-md border border-slate-200/50 dark:border-slate-700/50">
                                                {{ $category->target_module }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium max-w-xs truncate">
                                            {{ $category->description ?: 'No description provided' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex gap-4">
                                    <div class="text-center">
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                            Products</div>
                                        <div
                                            class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-lg">
                                            {{ $category->products_count }}
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                            Contacts</div>
                                        <div
                                            class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-lg">
                                            {{ $category->contacts_count }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <button wire:click="toggleStatus({{ $category->id }})"
                                    class="group/toggle flex items-center gap-3">
                                    <div
                                        class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors {{ $category->is_active ? 'bg-wa-teal' : 'bg-slate-200 dark:bg-slate-700' }}">
                                        <span
                                            class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform {{ $category->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </div>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest {{ $category->is_active ? 'text-wa-teal' : 'text-slate-400' }}">
                                        {{ $category->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditModal({{ $category->id }})"
                                        class="p-2 text-slate-400 hover:text-wa-teal transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="Are you sure you want to delete this category?"
                                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold">No categories found. Start by creating one above.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Management Modal -->
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm shadow-2xl transition-opacity animate-in fade-in duration-300"
                wire:click="closeModals"></div>
            <div
                class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="p-10 pb-4">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $editingCategoryId ? 'Modify' : 'Define' }} <span class="text-wa-teal">Category</span>
                    </h2>
                </div>

                <div class="px-10 py-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Standard Fields -->
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Category
                                Name</label>
                            <input type="text" wire:model="name"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="e.g. VIP Customers, New Collection">
                            @error('name') <span
                                class="text-rose-500 text-[10px] font-bold uppercase tracking-wide">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Contextual
                                Description</label>
                            <textarea wire:model="description" rows="3"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="What is this category used for?"></textarea>
                            @error('description') <span
                                class="text-rose-500 text-[10px] font-bold uppercase tracking-wide">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Target Area /
                                Module</label>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach(['all' => 'Global', 'contacts' => 'Contacts', 'products' => 'Products'] as $val => $label)
                                    <button wire:click="$set('target_module', '{{ $val }}')"
                                        class="py-3 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border {{ $target_module === $val ? 'bg-wa-teal border-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'bg-slate-50 dark:bg-slate-800 border-transparent text-slate-400 hover:border-slate-200 dark:hover:border-slate-700' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            @error('target_module') <span
                                class="text-rose-500 text-[10px] font-bold uppercase tracking-wide">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Visual Customization -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-50 dark:border-slate-800/50">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Branding
                                Color</label>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl flex-shrink-0 shadow-sm border border-slate-200 dark:border-slate-700"
                                    style="background-color: {{ $color }};"></div>
                                <input type="text" wire:model.live="color"
                                    class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 text-xs">
                            </div>
                            @error('color') <span
                                class="text-rose-500 text-[10px] font-bold uppercase tracking-wide">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Visual
                                Icon</label>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-2xl border border-slate-100 dark:border-slate-800">
                                    {{ $icon ?: '📁' }}
                                </div>
                                <input type="text" wire:model.live="icon"
                                    class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 text-center text-lg"
                                    placeholder="📁" maxlength="2">
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview Card -->
                    <div class="pt-6 border-t border-slate-50 dark:border-slate-800/50">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Rendering Preview
                        </div>
                        <div
                            class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-[1.5rem] border border-slate-100 dark:border-slate-800/50">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg"
                                    style="background-color: {{ $color }}15; color: {{ $color }};">
                                    {{ $icon ?: '📁' }}
                                </div>
                                <div>
                                    <div
                                        class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight leading-none mb-1">
                                        {{ $name ?: 'New Concept' }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-bold uppercase tracking-widest">
                                        Preview Identity
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attributes -->
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-50 dark:border-slate-800/50">
                        <button wire:click="$toggle('is_active')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $is_active ? 'bg-wa-teal' : 'bg-slate-200 dark:bg-slate-700' }}">
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Enable this category for
                            use</span>
                    </div>
                </div>

                <div class="p-10 bg-slate-50 dark:bg-slate-800/50 flex gap-4">
                    <button wire:click="closeModals"
                        class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                        Discard
                    </button>
                    <button wire:click="saveCategory"
                        class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                        {{ $editingCategoryId ? 'Push Updates' : 'Commit Category' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>