<div class="relative" x-init="getObserver()">
    <x-slot:title>
        {{ t('contact') }}
    </x-slot:title>

    <div class="flex justify-start mb-3 px-5 lg:px-0 items-center gap-2">
        @if (checkPermission('contact.create'))
            <x-button.primary wire:click="createContact">
                <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('new_contact_button') }}
            </x-button.primary>
        @endif
        @if (checkPermission('contact.bulk_import'))
            <x-button.primary wire:click="importContact">
                <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('import_contact') }}
            </x-button.primary>
        @endif
        <x-button.primary wire:click="refreshTable" wire:loading.attr="disabled"
            class="relative flex items-center justify-center space-x-1 min-w-[120px]" {{-- adjust min-w if needed --}}>
            {{-- Normal: icon + text --}}
            <div wire:loading.remove wire:target="refreshTable" class="flex items-center justify-center">
                <x-heroicon-o-arrow-path class="h-4 w-4 mr-1" />
                <span>{{ t('refresh') }}</span>
            </div>
            <div wire:loading wire:target="refreshTable">
                <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
            </div>
        </x-button.primary>
    </div>

    <x-card class="mx-4 lg:mx-0 rounded-lg">
        <x-slot:content>
            <div class="lg:mt-0">
                <livewire:admin.table.contact-table />
            </div>
        </x-slot:content>
    </x-card>

    <!-- Delete Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-contact-modal'" title="{{ t('delete_contact_title') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)" class="">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>

    {{-- View Contact Modal --}}
    <x-modal.custom-modal :id="'view-contact-modal'" :maxWidth="'5xl'" wire:model.defer="viewContactModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 flex justify-between">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ $contact ? "#{$contact->id} - {$contact->firstname} {$contact->lastname}" : t('contact_details') }}
            </h1>
            <button class="text-gray-500 hover:text-gray-700 text-2xl dark:hover:text-gray-300"
                wire:click="$set('viewContactModal', false)">
                &times;
            </button>
        </div>

        <!-- Tabs -->
        <div x-data="{ activeTab: 'profile' }">
            <div
                class="bg-gray-100 border-b border-neutral-200 dark:bg-gray-800 dark:border-neutral-500/30 gap-2 grid  grid-cols-3 mt-5 mx-5 px-2 py-1.5 rounded-md">

                <!-- Profile Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'profile'
                        ?
                        'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400'"
                    x-on:click="activeTab = 'profile'">
                    <x-heroicon-o-user class="hidden md:inline w-6 h-6" />
                    <span> {{ t('profile') }} </span>
                </button>

                <!-- Other Information Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'other'
                        ?
                        'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400'"
                    x-on:click="activeTab = 'other'">
                    <x-heroicon-o-information-circle class="hidden md:inline w-6 h-6" />
                    <span> {{ t('other_information') }} </span>
                </button>

                <!-- Notes Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'notes'
                        ?
                        'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400'"
                    x-on:click="activeTab = 'notes'">
                    <x-heroicon-o-document-text class="hidden md:inline w-6 h-6" />
                    <span> {{ t('notes_title') }} </span>
                </button>
            </div>

            <div class="p-4">
                <div x-show="activeTab === 'profile'">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4 p-4 rounded-lg break-words">
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400">{{ t('name') }}</span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 tesxt-wrap">
                                    {{ $contact ? "{$contact->firstname} {$contact->lastname}" : '-' }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('status') }}
                                </span>
                                <div>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        style="background-color: {{ $contact->status->color ?? '#ccc' }}20; color: {{ $contact->status->color ?? '#333' }};">
                                        {{ $contact->status->name ?? '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Source -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('source') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contact->source->name ?? '-' }}</p>
                            </div>

                            <!-- Assigned -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('assigned') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contact && $contact->user ? "{$contact->user->firstname} {$contact->user->lastname}" : '-' }}
                                </p>
                            </div>

                            <!-- Company -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('company') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->company ? $contact->company : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Type -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('type') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ ucfirst($contact->type ?? '-') }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('email') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 ">
                                    {{ isset($contact) && $contact->email ? $contact->email : '-' }}</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400">{{ t('phone') }}</span>
                                <p>
                                    <a href='tel:{{ $contact->phone ?? '-' }}' class="text-blue-600 text-sm">
                                        {{ $contact->phone ?? '-' }}
                                    </a>
                                </p>
                            </div>

                            <!-- Website -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('website') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->website ? $contact->website : '-' }}</p>

                            </div>

                            <!-- Default Language -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400">
                                    {{ t('default_language') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contact->default_language ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'other'">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4 p-4 rounded-lg">
                        <div class="space-y-4">
                            <!-- City -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('city') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->city ? $contact->city : '-' }}
                                </p>
                            </div>

                            <!-- State -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('state') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->state ? $contact->state : '-' }}
                                </p>
                            </div>

                            <!-- Country -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('country') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->country_name ? $contact->country_name : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Zip Code -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('zip_code') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->zip ? $contact->zip : '-' }}
                                </p>
                            </div>
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('description') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->description ? $contact->description : '-' }}
                                </p>
                            </div>

                            <!-- Address -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('address') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 ">
                                    {{ isset($contact) && $contact->address ? $contact->address : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'notes'">
                    <div class="col-span-1">
                        <div>
                            <div
                                class="mt-4 relative px-4 h-80 overflow-y-auto scrollbar-thin scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800">
                                <ol class="relative border-s border-gray-300 dark:border-gray-700">
                                    @forelse($notes as $note)
                                        <li class="mb-6 ms-4 relative">
                                            <div
                                                class="absolute w-2 h-2 bg-indigo-600 dark:bg-indigo-400 rounded-full -left-5 top-4">
                                            </div>

                                            <div
                                                class="flex-1 p-2 border-b border-gray-300 dark:border-gray-600 text-sm space-y-1">

                                                <span class="text-xs text-gray-500 dark:text-gray-400 block relative"
                                                    data-tippy-content="{{ format_date_time($note['created_at']) }}"
                                                    style="cursor: pointer; display: inline-block; text-decoration: underline dotted;">
                                                    {{ \Carbon\Carbon::parse($note['created_at'])->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) }}
                                                </span>
                                                <div class="flex justify-between items-start flex-nowrap">
                                                    <span class="text-gray-800 dark:text-gray-200 flex-1">
                                                        {{ $note['notes_description'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <p class="text-gray-500 dark:text-gray-400 text-center">
                                            {{ t('no_notes_available') }} </p>
                                    @endforelse
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-modal.custom-modal>
</div>
