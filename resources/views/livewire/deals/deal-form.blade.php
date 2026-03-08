<x-dialog-modal wire:model.live="isOpen">
    <x-slot name="title">
        {{ $dealId ? 'Edit Deal' : 'New Deal' }}
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <x-label for="title" value="Title" />
                    <x-input id="title" type="text" class="mt-1 block w-full" wire:model="title" />
                    <x-input-error for="title" class="mt-2" />
                </div>

                <div>
                    <x-label for="value" value="Value" />
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <x-input id="value" type="number" step="0.01" class="pl-7 block w-full" wire:model="value" />
                        <div class="absolute inset-y-0 right-0 flex items-center">
                            <select wire:model="currency" class="h-full rounded-md border-0 bg-transparent py-0 pl-2 pr-7 text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                    </div>
                    <x-input-error for="value" class="mt-2" />
                </div>

                <div>
                    <x-label for="pipelineId" value="Pipeline" />
                    <select id="pipelineId" wire:model.live="pipelineId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        @foreach($pipelines as $pipeline)
                            <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="pipelineId" class="mt-2" />
                </div>

                <div>
                    <x-label for="stageId" value="Stage" />
                    <select id="stageId" wire:model.live="stageId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <option value="">Select Stage</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage->id }}">{{ $stage->name }} ({{ $stage->probability }}%)</option>
                        @endforeach
                    </select>
                    <x-input-error for="stageId" class="mt-2" />
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <x-label for="contactId" value="Contact" />
                    <select id="contactId" wire:model="contactId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <option value="">Select Contact</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="contactId" class="mt-2" />
                </div>

                <div>
                    <x-label for="expectedCloseDate" value="Expected Close Date" />
                    <x-input id="expectedCloseDate" type="date" class="mt-1 block w-full" wire:model="expectedCloseDate" />
                    <x-input-error for="expectedCloseDate" class="mt-2" />
                </div>

                <div>
                    <x-label for="probability" value="Probability (%)" />
                    <x-input id="probability" type="number" min="0" max="100" class="mt-1 block w-full" wire:model="probability" />
                    <x-input-error for="probability" class="mt-2" />
                </div>

                <div>
                    <x-label for="ownerId" value="Owner" />
                    <select id="ownerId" wire:model="ownerId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        @foreach($teamMembers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="ownerId" class="mt-2" />
                </div>
            </div>
            
            <div class="col-span-1 md:col-span-2">
                <x-label for="description" value="Description" />
                <textarea id="description" wire:model="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"></textarea>
                <x-input-error for="description" class="mt-2" />
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="$set('isOpen', false)" wire:loading.attr="disabled">
            Cancel
        </x-secondary-button>

        <x-button class="ml-2" wire:click="save" wire:loading.attr="disabled">
            Save Deal
        </x-button>
    </x-slot>
</x-dialog-modal>
