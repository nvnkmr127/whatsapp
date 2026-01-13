<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Campaigns') }}
        </h2>
    </x-slot>

    <div class="bg-gray-100 min-h-screen">
        @livewire('campaigns.campaign-list')
    </div>
</x-app-layout>