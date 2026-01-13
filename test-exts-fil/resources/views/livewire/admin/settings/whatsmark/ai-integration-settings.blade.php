<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('ai_integration') }}
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
      <form wire:submit="save" x-data="{ 'enable_openai_in_chat': @entangle('enable_openai_in_chat') }" class="space-y-6">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('ai_integration') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('integrate_ai_tools') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <!-- Activate OpenAI in the chat -->
              <div>
                <x-label for="message" :value="t('activate_openai_in_chat')" />
                <div class="flex justify-start items-center mt-2">
                  <button type="button" x-on:click="enable_openai_in_chat = !enable_openai_in_chat"
                    class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                    role="switch" :aria-checked="enable_openai_in_chat.toString()"
                    :class="{
                        'bg-indigo-600': enable_openai_in_chat,
                        'bg-gray-300': !enable_openai_in_chat
                    }">
                    <span class="sr-only">{{ t('toggle_switch') }}</span>
                    <span aria-hidden="true"
                      class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                       <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="enable_openai_in_chat ? 'translate-x-5' : 'translate-x-0'">
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="enable_openai_in_chat ? 'opacity-0 ease-out duration-100' :
                            'opacity-100 ease-in duration-200'">
                        <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                    </span>
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="enable_openai_in_chat ? 'opacity-100 ease-in duration-200' :
                            'opacity-0 ease-out duration-100'">
                        <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                    </span>
                  </span>
                  </button>
                </div>
              </div>

              <!-- Chat Model -->
              <div class="mt-1" x-data="{ 'enable_openai_in_chat': @entangle('enable_openai_in_chat') }">
                <div wire:ignore>
                  <div class="flex items-center">
                    <span x-show="enable_openai_in_chat" x-cloak class="text-red-500 mr-1">*</span>
                    <x-label for="chat_model" :value="t('chat_model')" class="mb-1" />
                  </div>
                  <x-select class="tom-select" wire:model.defer="chat_model" id="chat_model">
                    <option> {{ t('select_model') }} </option>
                    @foreach ($chatGptModels as $model)
                      <option value="{{ $model['id'] }}">{{ $model['name'] }}</option>
                    @endforeach
                  </x-select>
                </div>
                <x-input-error for="chat_model" class="mt-1" />
              </div>
            </div>

            <!-- OpenAI Secret Key -->
            <div x-data="{ 'enable_openai_in_chat': @entangle('enable_openai_in_chat') }" class="mt-4 sm:mt-0">
              <div class="flex items-center sm:mt-2">
                <span x-show="enable_openai_in_chat" x-cloak class="text-red-500 mr-1">*</span>
                <x-label for="openai_secret_key" :value="t('openai_secret_key')" />
                <a href="https://platform.openai.com/api-keys" target="blank">
                  <em class="text-sm text-indigo-700 dark:text-indigo-600 ml-1">
                    {{ t('where_to_find_secret_key') }}
                  </em>
                </a>
              </div>
              <x-input wire:model.defer="openai_secret_key" id="openai_secret_key" type="text" />
              <x-input-error for="openai_secret_key" class="mt-1" />
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
