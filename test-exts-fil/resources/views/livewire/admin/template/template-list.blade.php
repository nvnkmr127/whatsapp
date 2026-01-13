<div class="relative" x-init="getObserver()">

    @if ($isDisconnected)
        <x-account-disconnected />
    @endif

    @if (!$isDisconnected)
        <x-slot:title>
            {{ t('whatsapp_template') }}
        </x-slot:title>

        <div class="flex flex-wrap justify-start mb-3 px-4 lg:px-0 items-center gap-2">
            @if (get_setting('whatsapp.is_whatsmark_connected') != 0 && get_setting('whatsapp.is_webhook_connected') != 0)
                @if (checkPermission('template.load_template'))
                    <x-button.loading-button type="button" target="loadTemplate" wire:click="loadTemplate"
                        class="whitespace-nowrap px-4 py-2">
                        {{ t('load_template') }}
                    </x-button.loading-button>
                @endif
            @endif

            <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" rel="noopener noreferrer">
                <x-button.primary class="whitespace-nowrap px-4 py-2">
                    {{ t('template_management') }}
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

        <x-card class="mx-4 lg:mx-0 rounded-lg">
            <x-slot:content>
                <div class="mt-8 lg:mt-0">
                    <livewire:admin.table.whatspp-template-table />
                </div>
            </x-slot:content>
        </x-card>
    @endif
</div>
