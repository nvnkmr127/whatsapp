<x-dropdown>
  <x-slot:trigger>
    <x-button.primary-round class="mx-2">
      <x-heroicon-s-language class="w-4 h-4" />
    </x-button.primary-round>
  </x-slot:trigger>
  <x-slot:content>
    @foreach (getLanguage(null, ['code', 'name']) as $language)
      @php
        $additionalClass =
            $language->code == $currentLocale
                ? 'bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900'
                : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white';
      @endphp
      <button wire:click="setLocale('{{ $language->code }}')"
        class="block w-full px-4 py-2 text-left  {{ $additionalClass }}">
        {{ $language->name }}
      </button>
    @endforeach
  </x-slot:content>
</x-dropdown>
