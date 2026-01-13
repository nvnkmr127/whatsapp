<div class="relative">
    <x-slot:title>
        {{ t('message_bots') }}
    </x-slot:title>

    @if (checkPermission('message_bot.create'))
        <div class="flex justify-start mb-3 px-4 lg:px-0 items-center gap-2">
            <a href="{{ route('admin.messagebot.create') }}">
                <x-button.primary>
                    <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('message_bot') }}
                </x-button.primary>
            </a>
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
            <div class="mt-8 lg:mt-0">
                <livewire:admin.table.message-bot-table />
            </div>
        </x-slot:content>
    </x-card>

    {{-- Delete confirmation --}}
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-messagebot-modal'" title="{{ t('delete_message_bot') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>
</div>
