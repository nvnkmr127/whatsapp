<div class="relative">
    <x-slot:title>
        {{ t('canned_reply') }}
    </x-slot:title>

    @if (checkPermission('canned_reply.create'))
        <div class="flex justify-start mb-3 px-4 lg:px-0 items-center gap-2">
            <x-button.primary wire:click="createCanned" wire:loading.attr="disabled">
                <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('canned_reply') }}
            </x-button.primary>
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
    @endif

    <x-card class="mx-4 lg:mx-0 rounded-lg">
        <x-slot:content>
            <div class="mt-8 lg:mt-0" wire:ignore>
                <livewire:admin.table.canned-reply-table />
            </div>
        </x-slot:content>
    </x-card>

    <x-modal.custom-modal :id="'canned_reply-modal'" :maxWidth="'2xl'" wire:model="showCannedModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 ">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ t('canned_reply') }}
            </h1>
        </div>

        <form wire:submit.prevent="save" class="mt-4">
            <div class="px-6 space-y-3">
                <div>
                    <div class="flex item-centar justify-start gap-1">
                        <span class="text-red-500">*</span>
                        <x-label class="dark:text-gray-300 block text-sm font-medium text-gray-700">
                            {{ t('title') }}
                        </x-label>
                    </div>
                    <x-input wire:model.defer="canned.title" type="text" id="cannedreply.id" class="w-full" />
                    <x-input-error for="canned.title" class="mt-2" />
                </div>

                <div>
                    <div class="flex item-centar justify-start gap-1">
                        <span class="text-red-500">*</span>
                        <x-label for="page.description"
                            class="dark:text-gray-300 block text-sm font-medium text-gray-700">
                            {{ t('description') }}
                        </x-label>
                    </div>
                    <x-textarea wire:model.defer="canned.description" wire:blur="validateCannedDescription"
                        rows="4"></x-textarea>
                    <x-input-error for="canned.description" class="mt-2" />
                </div>

            </div>
            <div
                class="py-4 flex justify-end space-x-3 border-t border-neutral-200 dark:border-neutral-500/30  mt-5 px-6">
                <x-button.secondary wire:click="$set('showCannedModal', false)">
                    {{ t('cancel') }}
                </x-button.secondary>

                <x-button.loading-button type="submit" target="save">
                    {{ t('submit') }}
                </x-button.loading-button>
            </div>

        </form>
    </x-modal.custom-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-canned-modal'" title="{{ t('delete_canned_title') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" wire:loading.attr="disabled" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>
</div>
