<div class="px-4 md:px-0">
  <x-slot:title>{{ t('translation_management') }}</x-slot:title>

  <x-card class="mx-4 lg:mx-0 rounded-lg">
    <x-slot:content>
      <x-slot:header>
        <x-settings-heading class="font-display">
          {{ t('translate_language') }}
        </x-settings-heading>
        <x-settings-description>
          {{ t('translate_language') }} {{ t('english_to') }}
          <span
            class="text-indigo-400 font-medium">{{ getLanguage($languageCode, ['name'])->name }}</span>
        </x-settings-description>
      </x-slot:header>
      <div class="mx-auto md:px-0">
        <livewire:admin.table.lanuage-line-table :languageCode="$languageCode"
          wire:key="translation-manager-{{ $languageCode }}" />
      </div>
    </x-slot:content>
  </x-card>
</div>
