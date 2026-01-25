<x-action-section>
    <x-slot name="title">
        {{ __('Marketing Preferences') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Manage your subscription to marketing emails and notifications.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
            {{ __('Receive updates about new features, tips for getting the most out of our platform, and special offers.') }}
        </div>

        <div class="mt-5">
            <div class="flex items-center">
                <x-checkbox wire:model="marketing_opt_in" id="marketing_opt_in" />
                <label for="marketing_opt_in"
                    class="ml-2 text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight">
                    {{ __('Opt-in to marketing communications') }}
                </label>
            </div>
            <x-input-error for="marketing_opt_in" class="mt-2" />
        </div>

        <div class="mt-5 flex items-center gap-4">
            <x-button wire:click="updateMarketingPreferences" wire:loading.attr="disabled"
                class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-900 border-none shadow-lg shadow-indigo-600/20 text-[10px] font-black uppercase tracking-widest">
                {{ __('Save Preferences') }}
            </x-button>

            <x-action-message class="me-3" on="saved">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </x-slot>
</x-action-section>