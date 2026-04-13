<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-wa-teal/10 text-wa-teal rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Audience <span class="text-wa-teal">Center</span>
                </h1>
                <p class="mt-2 text-sm text-slate-500 font-medium">Manage your contacts, tags, and communication preferences.</p>
            </div>
        </div>

        <div class="flex items-center gap-3 md:gap-4 overflow-x-auto pb-2 md:pb-0 scrollbar-hide flex-nowrap">
            <x-app-button variant="secondary" wire:click="openTagModal">
                <x-icon name="tag" class="w-4 h-4" />
                Tags
            </x-app-button>
            <x-app-button variant="secondary" wire:click="openFieldModal">
                <x-icon name="edit" class="w-4 h-4" />
                Fields
            </x-app-button>
            <x-app-button variant="secondary" wire:click="openImportModal">
                <x-icon name="arrow-up-tray" class="w-4 h-4" />
                Import
            </x-app-button>
            <x-app-button variant="secondary" wire:click="export">
                <x-icon name="arrow-down-tray" class="w-4 h-4" />
                Export
            </x-app-button>
            <x-app-button variant="primary" wire:click="create">
                <x-icon name="plus" class="w-4 h-4" stroke-width="4" />
                <span>Add Contact</span>
            </x-app-button>
        </div>
    </div>


    <!-- Filters & Table Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <!-- Search & Filters -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col lg:flex-row gap-6">
            <div class="flex-1 relative group">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                    placeholder="Search by name, phone, or email...">
                <x-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors" />

            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterTag"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select wire:model.live="filterStatus"
                        class="w-full py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-700 dark:text-slate-300 text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all appearance-none cursor-pointer">
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
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Health
                            Status</th>
                        <th
                            class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($contacts as $contact)
                        <tr wire:key="contact-{{ $contact->id }}"
                            class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-8 py-6">
                                <button type="button" wire:click="viewContact({{ $contact->id }})" class="flex items-center gap-4 text-left">
                                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name }}" 
                                        alt="{{ $contact->name }}" 
                                        class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 object-cover"
                                        loading="lazy">
                                    <div>
                                        <span class="text-sm font-black text-slate-900 dark:text-white hover:text-wa-teal transition-colors text-left">
                                            {{ $contact->name }}
                                        </span>
                                        <div class="text-xs text-slate-500 font-medium">
                                            {{ $contact->email ?: 'No email linked' }}
                                        </div>
                                    </div>
                                </button>
                            </td>
                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black tabular-nums rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                    {{ $contact->phone_number }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-wrap gap-2">
                                    @if($contact->category)
                                        @php
                                            $categoryColor = $contact->category->color ?: '#64748b';
                                        @endphp
                                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                              style="background-color: {{ $categoryColor }}20; color: {{ $categoryColor }}; border-color: {{ $categoryColor }}40">
                                            <i class="{{ $contact->category->icon }} mr-1"></i>
                                            {{ $contact->category->name }}
                                        </span>
                                    @endif
                                    @foreach($contact->tags as $tag)
                                        @php
                                            $tagColor = $tag->color ?: '#64748b';
                                        @endphp
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                            style="background-color: {{ $tagColor }}10; color: {{ $tagColor }}; border-color: {{ $tagColor }}30">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                    @if($contact->tags->isEmpty() && !$contact->category)
                                        <span class="text-xs text-slate-400 italic">No classification</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <x-contact-health-widget :contact="$contact" />
                            </td>
                            <td class="px-8 py-6 text-right">
                                    <button wire:click="openMergeModal({{ $contact->id }})"
                                            title="Merge contact"
                                            class="p-2 text-slate-400 hover:text-indigo-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </button>
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

    @if($isViewModalOpen)
        <x-app-modal wire:model="isViewModalOpen" maxWidth="4xl">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                        Contact <span class="text-wa-teal">Details</span>
                    </h2>
                    <div class="mt-2 text-xs font-bold text-slate-500">
                        {{ $viewingContact['phone_number'] ?? '' }}
                    </div>
                </div>
                <button type="button" wire:click="closeViewModal" class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                    <x-icon name="x-mark" class="w-6 h-6" />
                </button>
            </div>

            <div class="p-8 space-y-6">
                <div class="flex items-center gap-4">
                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $viewingContact['name'] ?? '' }}"
                        alt="{{ $viewingContact['name'] ?? '' }}"
                        class="w-16 h-16 rounded-[1.75rem] bg-slate-100 dark:bg-slate-800 object-cover">
                    <div class="min-w-0">
                        <div class="text-lg font-black text-slate-900 dark:text-white truncate">{{ $viewingContact['name'] ?? '' }}</div>
                        <div class="text-sm text-slate-500 font-medium truncate">{{ $viewingContact['email'] ?? 'No email linked' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Messages</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($viewingContact['messages_count'] ?? 0) }}</div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Conversations</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($viewingContact['conversations_count'] ?? 0) }}</div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tags</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($viewingContact['tags_count'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Classification</div>
                    <div class="flex flex-wrap gap-2">
                        @if(!empty($viewingContact['category']))
                            @php $categoryColor = $viewingContact['category']['color'] ?? '#64748b'; @endphp
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                style="background-color: {{ $categoryColor }}20; color: {{ $categoryColor }}; border-color: {{ $categoryColor }}40">
                                {{ $viewingContact['category']['name'] ?? '' }}
                            </span>
                        @endif
                        @foreach(($viewingContact['tags'] ?? []) as $tag)
                            @php $tagColor = $tag['color'] ?? '#64748b'; @endphp
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-tighter rounded-md border"
                                style="background-color: {{ $tagColor }}10; color: {{ $tagColor }}; border-color: {{ $tagColor }}30">
                                {{ $tag['name'] ?? '' }}
                            </span>
                        @endforeach
                        @if(empty($viewingContact['category']) && empty($viewingContact['tags']))
                            <div class="text-xs text-slate-400 italic">No classification</div>
                        @endif
                    </div>
                </div>
            </div>
        </x-app-modal>
    @endif

    <!-- Create/Edit Modal -->
    <!-- Main Contact Modal (Create/Edit) -->
    @if($isModalOpen)
    <x-app-modal wire:model="isModalOpen" maxWidth="5xl" :backdropClose="false">
        <div class="p-8 pb-0">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                {{ $contactId ? 'Update' : 'Register' }} <span class="text-wa-teal">Contact</span>
            </h2>
        </div>

        <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Full Name</label>
                    <input type="text" wire:model="name"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all"
                        placeholder="e.g. John Doe">
                    @error('name') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">WhatsApp Number</label>
                    <div class="flex gap-3">
                        <div class="w-24 shrink-0">
                            <select wire:model="countryCode" 
                                class="w-full px-3 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer text-sm">
                                @foreach($availableCountryCodes as $code => $label)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1">
                            <input type="text" wire:model="phoneNumberWithoutCode"
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all font-mono"
                                placeholder="8686877397">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Email Address</label>
                    <input type="email" wire:model="email"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all"
                        placeholder="john@example.com">
                    @error('email') <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Language</label>
                        <input type="text" wire:model="language"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all"
                            placeholder="en">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Status</label>
                        <select wire:model="opt_in_status"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                            <option value="opted_in">Opt-in</option>
                            <option value="opted_out">Opt-out</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Custom Fields Section -->
            @if($customFields->isNotEmpty())
                <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6">Additional Metadata</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($customFields as $field)
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ $field->label }}</label>
                                @if($field->type === 'select')
                                    <select wire:model="customAttributes.{{ $field->key }}"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                        <option value="">Select {{ $field->label }}</option>
                                        @foreach($field->options as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field->type === 'date')
                                    <input type="date" wire:model="customAttributes.{{ $field->key }}"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all">
                                @else
                                    <input type="{{ $field->type === 'number' ? 'number' : 'text' }}"
                                        wire:model="customAttributes.{{ $field->key }}"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                        placeholder="Enter {{ $field->label }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Organization Section -->
            <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6">Categorization</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Category</label>
                        <select wire:model="category_id"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                            <option value="">Not Categorized</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Primary Tag</label>
                        <select wire:model="selectedTags" 
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                            <option value="">No Tag</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="p-8 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row gap-4">
            <x-app-button variant="ghost" class="w-full sm:flex-1 py-4 order-2 sm:order-1" wire:click="closeModal">Cancel</x-app-button>
            <x-app-button variant="primary" class="w-full sm:flex-[2] py-4 order-1 sm:order-2" wire:click="store">Save Contact</x-app-button>
        </div>
    </x-app-modal>
    @endif


    <!-- Delete Confirmation Modal -->
    @if($isDeleteModalOpen)
    <x-app-modal wire:model="isDeleteModalOpen" maxWidth="md" :backdropClose="false">

        <div class="p-10 text-center space-y-6">
            <div class="w-20 h-20 bg-rose-50 dark:bg-rose-500/10 rounded-[2rem] flex items-center justify-center text-rose-500 mx-auto">
                <x-icon name="trash" class="w-10 h-10" />
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Erase Contact?</h3>
                <p class="mt-2 text-slate-500 font-medium text-sm">This action is irreversible. All message history for this contact will lose its identity link.</p>
            </div>
            <div class="flex flex-col gap-3">
                <x-app-button variant="danger" wire:click="delete">Confirm Deletion</x-app-button>
                <x-app-button variant="ghost" wire:click="$set('isDeleteModalOpen', false)">Keep Contact</x-app-button>
            </div>
        </div>
    </x-app-modal>
    @endif


    <!-- Tag Management (Child Component) -->
    <livewire:contacts.tag-manager />


    <!-- Custom Fields Management Modal -->
    @if($isFieldModalOpen)
    <x-app-modal wire:model="isFieldModalOpen" maxWidth="4xl" :backdropClose="false">

        <div class="p-8 pb-0 flex justify-between items-center">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Manage <span class="text-wa-teal">Fields</span>
            </h2>
        </div>
        <div class="p-8 space-y-6">
            <!-- Create/Edit Form -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400">Label</label>
                        <input type="text" wire:model="fieldLabel" placeholder="e.g. Birthday"
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                        @error('fieldLabel') <span
                        class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400">Type</label>
                        <select wire:model.live="fieldType"
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="select">Select (Dropdown)</option>
                        </select>
                    </div>
                </div>

                @if($fieldType === 'select')
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400">Options (comma separated)</label>
                        <input type="text" wire:model="fieldOptions" placeholder="e.g. VIP, Regular, New"
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                    </div>
                @endif

                <div class="pt-2">
                    <x-app-button variant="primary" class="w-full py-2 text-[10px]" wire:click="storeField">
                        {{ $fieldId ? 'Update Field' : 'Create Field' }}
                    </x-app-button>
                </div>
                @if (session()->has('field_message'))
                    <div class="text-xs font-bold text-wa-teal">{{ session('field_message') }}</div>
                @endif
            </div>

            <!-- Existing Fields -->
            <div class="space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Custom Fields</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                    @forelse($customFields as $field)
                        <div
                            class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $field->label }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $field->type }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="editField({{ $field->id }})"
                                    class="text-slate-400 hover:text-wa-teal transition-colors">
                                    <x-icon name="edit" class="w-4 h-4" />
                                </button>
                                <button wire:click="deleteField({{ $field->id }})"
                                    class="text-slate-400 hover:text-rose-500 transition-colors">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs italic">No custom fields defined.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-app-modal>
    @endif


    <!-- Import Modal -->
    @if($isImportModalOpen)
    <x-app-modal wire:model="isImportModalOpen" maxWidth="5xl" :backdropClose="false">

        <div class="p-8 pb-0">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Import <span class="text-wa-teal">Contacts</span>
            </h2>
        </div>
        <div class="p-8 space-y-6">
            @if(!$importResult)
                <!-- Step 1: Upload -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Upload CSV</h3>
                        <button wire:click="downloadSampleCsv"
                            class="text-xs font-bold text-wa-teal hover:underline flex items-center gap-1">
                            <x-icon name="arrow-up-tray" class="w-3 h-3" />
                            Download Sample
                        </button>
                    </div>
                    <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-8 text-center hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors relative group">
                        <label class="cursor-pointer block">
                            <input type="file" wire:model="importFile" class="hidden" accept=".csv,.xlsx,.xls,.ods">
                            <div class="text-slate-400 mb-2 group-hover:text-wa-teal transition-colors">
                                <x-icon name="cloud-arrow-up" class="w-12 h-12 mx-auto" />
                            </div>
                            <span class="text-sm font-bold text-slate-700 dark:text-white block">
                                {{ $importFile ? $importFile->getClientOriginalName() : 'Click to Upload Import Bundle' }}
                            </span>
                            <span class="text-xs text-slate-400 block mt-1 uppercase tracking-widest font-black">Accepts CSV, XLSX & XLS</span>
                        </label>
                        <div wire:loading wire:target="importFile" class="absolute inset-0 rounded-2xl bg-white/70 dark:bg-slate-900/70 backdrop-blur flex items-center justify-center">
                            <div class="text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">Uploading...</div>
                        </div>
                    </div>
                    @error('importFile') <span class="text-rose-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>

                <!-- Step 2: Map Columns -->
                @if($importFile)
                    <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Map Columns</h3>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 max-h-60 overflow-y-auto custom-scrollbar">
                            @foreach($csvHeaders as $header)
                                <div class="flex items-center gap-4 mb-3 last:mb-0">
                                    <div class="w-1/3 text-[10px] font-black uppercase text-slate-500 truncate" title="{{ $header }}">
                                        {{ $header }}
                                    </div>
                                    <div class="text-slate-300">
                                        <x-icon name="chevron-right" class="w-4 h-4" />
                                    </div>
                                    <div class="flex-1">
                                        <select wire:model="columnMapping.{{ $header }}"
                                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 cursor-pointer">
                                            <option value="">Do not import</option>
                                            <option value="name">Full Name</option>
                                            <option value="phone">WhatsApp Number</option>
                                            <option value="email">Email</option>
                                            <option value="language">Language</option>
                                            <option value="category">Category</option>
                                            <option value="tags">Tags (comma separated)</option>
                                            <!-- Dynamic fields -->
                                            @foreach($customFields as $f)
                                                <option value="attr_{{ $f->key }}">{{ $f->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-2">
                            <x-app-button variant="primary" class="w-full py-3" wire:click="import" wire:loading.attr="disabled" :disabled="$isImporting">
                                <x-icon name="arrow-up-tray" class="w-4 h-4" />
                                <span wire:loading.remove wire:target="import">Start Importing</span>
                                <span wire:loading wire:target="import">Processing...</span>
                            </x-app-button>
                        </div>
                    </div>
                @endif
            @else
                <!-- Result Step -->
                <div class="text-center py-6 space-y-4">
                    <div class="w-16 h-16 bg-wa-teal/10 text-wa-teal rounded-full flex items-center justify-center mx-auto">
                        <x-icon name="plus" class="w-8 h-8" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase">Import Complete</h3>
                        <div class="mt-2 text-sm font-medium text-slate-500 uppercase tracking-widest text-[10px]">
                            Processed: <span class="text-wa-teal font-black">{{ $importResult['total'] }}</span>
                            | Saved: <span class="text-wa-teal font-black">{{ $importResult['imported'] }}</span>
                            @if(!is_null($importResult['created'] ?? null))
                                | New: <span class="text-wa-teal font-black">{{ $importResult['created'] }}</span>
                            @endif
                            @if(!is_null($importResult['updated'] ?? null))
                                | Updated: <span class="text-wa-teal font-black">{{ $importResult['updated'] }}</span>
                            @endif
                            @if(!is_null($importResult['skipped'] ?? null) && ($importResult['skipped'] ?? 0) > 0)
                                | Skipped: <span class="text-slate-700 dark:text-slate-200 font-black">{{ $importResult['skipped'] }}</span>
                            @endif
                            @if(count($importResult['errors']) > 0)
                                | Errors: <span class="text-rose-500 font-bold font-black">{{ count($importResult['errors']) }}</span>
                            @endif
                        </div>
                    </div>

                    @if(count($importResult['errors']) > 0)
                        <div class="bg-rose-50 dark:bg-rose-500/10 rounded-2xl p-4 text-left max-h-40 overflow-y-auto custom-scrollbar">
                            <h4 class="text-xs font-black text-rose-500 uppercase mb-2">Error Log</h4>
                            <ul class="space-y-1">
                                @foreach($importResult['errors'] as $error)
                                    <li class="text-[10px] font-bold text-rose-500">• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <x-app-button variant="primary" class="px-12 py-3" wire:click="closeImportModal">
                        Close Done
                    </x-app-button>
                </div>
            @endif
        </div>
    </x-app-modal>
    @endif

    @if($isDuplicateModalOpen)
    <x-app-modal wire:model="isDuplicateModalOpen" maxWidth="4xl" :backdropClose="false">
        <div class="p-8 pb-0">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Duplicate <span class="text-rose-500">Contacts</span>
            </h2>
        </div>
        <div class="p-8 space-y-6">
            <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/20">
                <div class="text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-500">We found duplicates in your import file.</div>
                <div class="mt-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                    Existing duplicates: <span class="font-black">{{ count($importPreview['duplicates_existing'] ?? []) }}</span>
                    | In-file duplicates: <span class="font-black">{{ count($importPreview['duplicates_in_file'] ?? []) }}</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">When a duplicate is found</div>
                <select wire:model="importDuplicateStrategy" class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-900 dark:text-white">
                    <option value="update">Update existing contact</option>
                    <option value="skip">Skip duplicate rows</option>
                </select>
            </div>

            @if($importDuplicateStrategy === 'update')
                <div class="space-y-2">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Existing contact tags</div>
                    <select wire:model="importDuplicateTagStrategy" class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-900 dark:text-white">
                        <option value="add">Add imported tags</option>
                        <option value="replace">Replace with imported tags</option>
                        <option value="keep">Keep existing tags</option>
                    </select>
                </div>
            @endif

            @if(!empty($importPreview['duplicates_existing'] ?? []))
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-4 max-h-64 overflow-y-auto custom-scrollbar">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Duplicates in your account</div>
                    <div class="space-y-2">
                        @foreach(array_slice($importPreview['duplicates_existing'], 0, 50) as $dup)
                            <div class="flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/40">
                                <div class="min-w-0">
                                    <div class="text-xs font-black text-slate-800 dark:text-slate-100 truncate">{{ $dup['incoming_name'] ?: 'No name' }}</div>
                                    <div class="text-[10px] font-bold text-slate-500 truncate">{{ $dup['existing']['phone_number'] }}</div>
                                    @if(!empty($dup['incoming_tags'] ?? []))
                                        <div class="text-[10px] font-bold text-slate-400 truncate">Incoming tags: {{ implode(', ', $dup['incoming_tags']) }}</div>
                                    @endif
                                    @if(!empty($dup['existing']['tags'] ?? []))
                                        <div class="text-[10px] font-bold text-slate-400 truncate">Existing tags: {{ implode(', ', $dup['existing']['tags']) }}</div>
                                    @endif
                                    <div class="text-[10px] font-bold text-slate-400">Row {{ $dup['row'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Existing</div>
                                    <div class="text-xs font-black text-slate-800 dark:text-slate-100">{{ $dup['existing']['name'] ?: 'No name' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($importPreview['duplicates_existing']) > 50)
                        <div class="mt-3 text-[10px] font-bold text-slate-400">Showing first 50 items.</div>
                    @endif
                </div>
            @endif

            @if(!empty($importPreview['duplicates_in_file'] ?? []))
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-4 max-h-64 overflow-y-auto custom-scrollbar">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Duplicates inside the file</div>
                    <div class="space-y-2">
                        @foreach(array_slice($importPreview['duplicates_in_file'], 0, 50) as $dup)
                            <div class="flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/40">
                                <div class="min-w-0">
                                    <div class="text-xs font-black text-slate-800 dark:text-slate-100 truncate">{{ $dup['phone_number'] }}</div>
                                    <div class="text-[10px] font-bold text-slate-500">Rows {{ implode(', ', $dup['rows']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($importPreview['duplicates_in_file']) > 50)
                        <div class="mt-3 text-[10px] font-bold text-slate-400">Showing first 50 items.</div>
                    @endif
                </div>
            @endif
        </div>
        <div class="p-8 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row gap-4">
            <x-app-button variant="ghost" class="w-full sm:flex-1 py-4 order-2 sm:order-1" wire:click="cancelImportAfterDuplicates">Cancel</x-app-button>
            <x-app-button variant="primary" class="w-full sm:flex-[2] py-4 order-1 sm:order-2" wire:click="confirmImportAfterDuplicates" wire:loading.attr="disabled" :disabled="$isImporting">
                <span wire:loading.remove wire:target="confirmImportAfterDuplicates">Continue Import</span>
                <span wire:loading wire:target="confirmImportAfterDuplicates">Processing...</span>
            </x-app-button>
        </div>
    </x-app-modal>
    @endif


    <!-- Merge Modal -->
    @if($isMergeModalOpen)
    <x-app-modal wire:model="isMergeModalOpen" maxWidth="4xl" :backdropClose="false">
        <div class="p-8 pb-0">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Merge <span class="text-indigo-500">Duplicate</span>
            </h2>
        </div>
        <div class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-dashed border-slate-200 dark:border-slate-700">
                <!-- Source -->
                @if($sourceContact)
                <div class="p-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                    <p class="text-[9px] font-black uppercase tracking-widest text-rose-500 mb-3 block">Merging (Will be deleted)</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center font-black">
                            {{ substr($sourceContact->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-black">{{ $sourceContact->name }}</p>
                            <p class="text-[10px] text-slate-500">{{ $sourceContact->phone_number }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex justify-center text-slate-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                </div>

                <!-- Target -->
                <div class="space-y-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-500 block">Into Primary (Will remain)</p>
                    <select wire:model.live="targetContactId" class="w-full px-5 py-4 bg-white dark:bg-slate-800 border-none rounded-2xl text-sm font-bold shadow-sm focus:ring-2 focus:ring-emerald-500 transition-all">
                        <option value="">Search Primary Contact...</option>
                        @foreach($contacts as $c)
                            @if($c->id != $sourceContactId)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone_number }})</option>
                            @endif
                        @endforeach
                    </select>

                    @if($targetContact)
                    <div class="p-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center font-black">
                            {{ substr($targetContact->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-black">{{ $targetContact->name }}</p>
                            <p class="text-[10px] text-slate-500">{{ $targetContact->phone_number }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if(session()->has('merge_error'))
                <div class="p-4 bg-red-50 text-red-600 rounded-2xl text-xs font-bold uppercase tracking-widest border border-red-100 italic">
                    {{ session('merge_error') }}
                </div>
            @endif

            <ul class="space-y-2 py-4 px-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/20">
                <li class="text-[10px] font-bold text-amber-700 dark:text-amber-500 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Messages, Notes, and Deals from the source will be moved to the primary.
                </li>
                <li class="text-[10px] font-bold text-amber-700 dark:text-amber-500 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    The source contact record will be permanently erased after the merge.
                </li>
            </ul>
        </div>

        <div class="p-8 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row gap-4">
            <x-app-button variant="ghost" class="w-full sm:flex-1 py-4 order-2 sm:order-1" wire:click="resetMergeState">Cancel</x-app-button>
            <x-app-button variant="primary" class="w-full sm:flex-[2] py-4 order-1 sm:order-2" 
                wire:click="merge" 
                wire:loading.attr="disabled"
                :disabled="!$targetContactId || $isMerging">
                <span wire:loading.remove wire:target="merge">Confirm Merge</span>
                <span wire:loading wire:target="merge">Executing Merge...</span>
            </x-app-button>
        </div>
    </x-app-modal>
    @endif

    <!-- End of Modals -->


    <style>

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</div>
