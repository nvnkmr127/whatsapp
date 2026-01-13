<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Agent Console') }}
        </h2>
    </x-slot>

    <div class="h-[calc(100vh-65px)] overflow-hidden bg-white dark:bg-gray-800">
        @livewire('chat.chat-interface')
    </div>
</x-app-layout>