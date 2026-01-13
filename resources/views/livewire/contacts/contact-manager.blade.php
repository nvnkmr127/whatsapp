<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Audience <span
                        class="text-wa-green">Center</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage your contacts, tags, and communication preferences.</p>
        </div>
        <button wire:click="openTagModal"
            class="flex items-center justify-center gap-2 px-8 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800 mr-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Manage Tags
        </button>
        <button wire:click="create"
            class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-wa-green text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
            </svg>
            Add Contact
        </button>
    </div>

    @if (session()->has('message'))
        <div
            class="animate-in slide-in-from-top-4 duration-500 p-4 bg-wa-green/10 border border-wa-green/20 text-wa-green rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-bold text-sm">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Filters & Table Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <!-- Search & Filters -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1 relative group">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all font-medium"
                    placeholder="Search by name, phone, or email...">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-green transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterTag"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-green/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterStatus"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-green/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="opted_in">Opt-in</option>
                        <option value="opted_out">Opt-out</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact
                            Identity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Communication</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Classification</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status
                        </th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($contacts as $contact)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-wa-green/10 group-hover:text-wa-green transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ $contact->name }}
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium">
                                            {{ $contact->email ?: 'No email linked' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black tabular-nums rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $contact->phone_number }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($contact->tags as $tag)
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                            style="background-color: {{ $tag->color }}10; color: {{ $tag->color }}; border-color: {{ $tag->color }}30">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                    @if($contact->tags->isEmpty())
                                        <span class="text-xs text-slate-400 italic">No tags</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-green shadow-lg shadow-wa-green/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest {{ $contact->opt_in_status === 'opted_in' ? 'text-wa-green' : 'text-rose-500' }}">
                                        {{ str_replace('_', ' ', $contact->opt_in_status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="edit({{ $contact->id }})"
                                        class="p-2 text-slate-400 hover:text-wa-teal dark:hover:text-wa-teal transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $contact->id }})"
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
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-400 font-bold">No contacts found matches your filter.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>
            <div
                class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-8 pb-0">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $contactId ? 'Update' : 'Register' }} <span class="text-wa-teal">Contact</span>
                    </h2>
                </div>

                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">Full Name</label>
                            <input type="text" wire:model="name"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="e.g. John Doe">
                            @error('name') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">WhatsApp
                                Number</label>
                            <input type="text" wire:model="phone_number"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="e.g. 1234567890">
                            @error('phone_number') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Email Address
                            (Optional)</label>
                        <input type="email" wire:model="email"
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                            placeholder="john@example.com">
                        @error('email') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">Language Code</label>
                            <input type="text" wire:model="language"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20"
                                placeholder="en">
                            @error('language') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">Status</label>
                            <select wire:model="opt_in_status"
                                class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                <option value="opted_in">Opt-in</option>
                                <option value="opted_out">Opt-out</option>
                            </select>
                            @error('opt_in_status') <span
                            class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-400">Select Tags</label>
                        <div
                            class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl grid grid-cols-2 gap-3 max-h-40 overflow-y-auto">
                            @foreach($tags as $tag)
                                <label
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-700 transition-colors cursor-pointer">
                                    <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}"
                                        class="w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex gap-4">
                    <button wire:click="closeModal"
                        class="flex-1 py-4 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                        Cancel
                    </button>
                    <button wire:click="store"
                        class="flex-[2] py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Save Identity
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($isDeleteModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('isDeleteModalOpen', false)"></div>
            <div
                class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-10 text-center space-y-6">
                    <div
                        class="w-20 h-20 bg-rose-50 dark:bg-rose-500/10 rounded-[2rem] flex items-center justify-center text-rose-500 mx-auto">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Erase
                            Contact?</h3>
                        <p class="mt-2 text-slate-500 font-medium text-sm">This action is irreversible. All message history
                            for this contact will lose its identity link.</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        <button wire:click="delete"
                            class="w-full py-4 bg-rose-500 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-rose-500/20 hover:bg-rose-600 transition-all">
                            Confirm Deletion
                        </button>
                        <button wire:click="$set('isDeleteModalOpen', false)"
                            class="w-full py-4 bg-slate-50 dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all">
                            Keep Contact
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Tag Management Modal -->
    @if($isTagModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeTagModal"></div>
            <div
                class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-8 pb-0 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Manage <span class="text-wa-teal">Tags</span>
                    </h2>
                    <button wire:click="closeTagModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <!-- Create New Tag -->
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Create New Tag</h3>
                        <div class="flex gap-4">
                            <div class="flex-1 space-y-1">
                                <input type="text" wire:model="newTagName" placeholder="Tag Name (e.g. VIP)"
                                    class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                                @error('newTagName') <span
                                class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span> @enderror
                            </div>
                            <div class="w-16">
                                <input type="color" wire:model="newTagColor"
                                    class="w-full h-10 px-1 py-1 bg-white dark:bg-slate-700 border-none rounded-xl cursor-pointer"
                                    title="Pick Color">
                            </div>
                            <button wire:click="createTag"
                                class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-bold uppercase text-[10px] hover:scale-105 transition-transform">
                                Add
                            </button>
                        </div>
                        @if (session()->has('tag_message'))
                            <div class="text-xs font-bold text-wa-green">{{ session('tag_message') }}</div>
                        @endif
                    </div>

                    <!-- Existing Tags List -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Existing Tags</h3>
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                            @forelse($tags as $tag)
                                <div
                                    class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $tag->color }}"></div>
                                        <span
                                            class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $tag->name }}</span>
                                    </div>
                                    <button wire:click="deleteTag({{ $tag->id }})"
                                        class="text-slate-400 hover:text-rose-500 transition-colors" title="Delete Tag">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div class="text-center py-6 text-slate-400 text-xs italic">No tags created yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>