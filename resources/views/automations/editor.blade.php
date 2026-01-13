<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Flow Builder') }}
        </h2>
    </x-slot>

    <div class="h-[calc(100vh-65px)]">
        @if(isset($id))
            @livewire('automations.automation-builder', ['id' => $id])
        @else
            @livewire('automations.automation-builder')
        @endif
    </div>
</x-app-layout>