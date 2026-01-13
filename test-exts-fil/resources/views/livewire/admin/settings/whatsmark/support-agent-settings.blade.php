<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('support_agent') }}
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
      <form wire:submit="save" x-data="{ 'only_agents_can_chat': @entangle('only_agents_can_chat') }" class="space-y-6">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('support_agent') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('configure_support_agent') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <x-label for="message" :value="t('restrict_chat_access')" />

            <div class="flex justify-start items-center mt-2">
              <button type="button" x-on:click="only_agents_can_chat = !only_agents_can_chat"
                class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                role="switch" :aria-checked="only_agents_can_chat.toString()"
                :class="{
                    'bg-indigo-600': only_agents_can_chat,
                    'bg-gray-300': !only_agents_can_chat
                }">
                <span class="sr-only">{{ t('toggle_switch') }}</span>
                <span aria-hidden="true"
                  class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                   <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="only_agents_can_chat ? 'translate-x-5' : 'translate-x-0'">
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="only_agents_can_chat ? 'opacity-0 ease-out duration-100' :
                            'opacity-100 ease-in duration-200'">
                        <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                    </span>
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="only_agents_can_chat ? 'opacity-100 ease-in duration-200' :
                            'opacity-0 ease-out duration-100'">
                        <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                    </span>
                  </span>
              </button>
            </div>
            <div class="mt-4">
              <x-dynamic-alert type="warning">
                <b>{{ t('note') }}</b>
                {{ t('support_agent_feature_info') }}
              </x-dynamic-alert>
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
