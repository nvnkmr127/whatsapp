<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('search_engine_optimization') }}
  </x-slot:title>
  <!-- Page Heading -->
  <div class="pb-6">
    <x-settings-heading>{{ t('system_setting') }}</x-settings-heading>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap gap-4">
    <!-- Sidebar Menu -->
    <div class="w-full lg:w-1/5">
      <x-admin-system-settings-navigation wire:ignore />
    </div>
    <!-- Main Content -->
    <div class="flex-1 space-y-5">
      <form wire:submit="save" class="space-y-6">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('search_engine_optimization') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('seo_description') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <!-- Meta Title -->
              <div class="sm:col-span-2">
                <x-label for="meta_title" :value="t('meta_title')" />
                <x-input id="meta_title" type="text" class="mt-1 block w-full"
                  wire:model="meta_title" />
                <x-input-error for="meta_title" class="mt-2" />
              </div>

              <!-- Meta Description -->
              <div class="sm:col-span-2">
                <x-label for="meta_description" :value="t('meta_description')" />
                <x-textarea id="meta_description" class="mt-1 block w-full"
                  wire:model="meta_description" wire:blur="validateMetaDescription"
                  rows="3" />
                <x-input-error for="meta_description" class="mt-2" />
              </div>
            </div>
          </x-slot:content>

          <!-- Submit Button -->
          @if (checkPermission('system_settings.edit'))
            <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
              <div class="flex justify-end">
                <x-button.loading-button type="submit" target="save">
                  {{ t('save_changes_button') }}
                </x-button.loading-button>
              </div>
            </x-slot:footer>
          @endif
        </x-card>
      </form>
    </div>
  </div>
</div>
