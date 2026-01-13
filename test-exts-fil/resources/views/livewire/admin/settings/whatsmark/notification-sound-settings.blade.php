<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('notification_sound') }}
  </x-slot:title>
  <!-- Page Heading -->
  <div class="pb-6">
    <x-settings-heading>{{ t('whatsmark_settings') }}</x-settings-heading>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap gap-4">
    <!-- Sidebar Menu -->
    <div class="w-full lg:w-1/5">
      <x-admin-whatsmark-settings-navigation wire:ignore />
    </div>
    <!-- Main Content -->
    <div class="flex-1 space-y-5">
      <form wire:submit="save" class="space-y-6">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('notification_sound') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('customize_notification_sound') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div x-data="{ 'enable_chat_notification_sound': @entangle('enable_chat_notification_sound') }">
              <x-label for="enable_chat_notification_sound" :value="t('enable_whatsapp_chat_notification_sound')" class="mb-2" />
              <button type="button"
                x-on:click="enable_chat_notification_sound = !enable_chat_notification_sound"
                class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                role="switch" :aria-checked="enable_chat_notification_sound.toString()"
                :class="{
                    'bg-indigo-600': enable_chat_notification_sound,
                    'bg-gray-300': !enable_chat_notification_sound
                }">
                <span class="sr-only">{{ t('toggle_switch') }}</span>
                <span aria-hidden="true"
                  class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                   <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="enable_chat_notification_sound ? 'translate-x-5' : 'translate-x-0'">
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="enable_chat_notification_sound ? 'opacity-0 ease-out duration-100' :
                            'opacity-100 ease-in duration-200'">
                        <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                    </span>
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="enable_chat_notification_sound ? 'opacity-100 ease-in duration-200' :
                            'opacity-0 ease-out duration-100'">
                        <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                    </span>
                  </span>
              </button>
            </div>
          </x-slot:content>
          <!-- Submit Button -->
          @if (checkPermission('whatsmark_settings.edit'))
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
