<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('bot_protection') }}
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
      <form wire:submit="save" class="space-y-6" x-data="{ 'isReCaptchaEnable': @entangle('isReCaptchaEnable') }">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('bot_protection') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('bot_protection_description') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div class="flex flex-col justify-start items-start gap-2">
              <!-- Toggle Switch -->
              <div class="flex justify-start items-center">
                <button type="button" x-on:click="isReCaptchaEnable = !isReCaptchaEnable"
                  class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                  role="switch" :aria-checked="isReCaptchaEnable.toString()"
                  :class="{
                      'bg-indigo-600': isReCaptchaEnable,
                      'bg-gray-300': !isReCaptchaEnable
                  }">
                  <span class="sr-only">{{ t('toggle_switch') }}</span>
                  <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="isReCaptchaEnable ? 'translate-x-5' : 'translate-x-0'">
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="isReCaptchaEnable ? 'opacity-0 ease-out duration-100' :
                            'opacity-100 ease-in duration-200'">
                        <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                    </span>
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="isReCaptchaEnable ? 'opacity-100 ease-in duration-200' :
                            'opacity-0 ease-out duration-100'">
                        <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                    </span>
                  </span>
                </button>

                <span class="ms-3 text-sm font-medium text-slate-900 dark:text-slate-300">
                  {{ t('enable_recaptcha') }}
                </span>
              </div>

              <!-- Conditionally Displayed Section -->

              <div x-show="isReCaptchaEnable" class="w-full" x-cloak>
                <div class="mt-5 w-full">
                  <div class="flex items-center">
                    <span x-show="isReCaptchaEnable" class="text-red-500 mr-1">*</span>
                    <x-input-label for="site_key" :value="t('recaptcha_site_key')" />
                  </div>
                  <x-input wire:model="site_key" id="site_key" class="block mt-1 w-full"
                    type="text" name="site_key" placeholder="{{ t('recaptcha_site_key') }}" />
                  <x-input-error class="mt-2" for="site_key" />
                </div>
                <div class="mt-5 w-full">
                  <div class="flex items-center">
                    <span x-show="isReCaptchaEnable" class="text-red-500 mr-1">*</span>
                    <x-input-label for="secret_key" :value="t('recaptcha_site_secret')" />
                  </div>
                  <x-input wire:model="secret_key" id="secret_key" class="block mt-1 w-full"
                    type="text" name="secret_key"
                    placeholder="{{ t('recaptcha_site_secret') }}" />
                  <x-input-error class="mt-2" for="secret_key" />
                  <p class="text-xs mt-2 dark:text-slate-300">
                    {{ t('obtain_credential') }}
                    <a href="https://www.google.com/recaptcha/admin" target="_blank"
                      class="hover:underline text-green-500 underline">
                      {{ t('here') }}
                    </a>
                  </p>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <x-dynamic-alert type="warning" class="w-full">
                <div class="flex items-center gap-2 w-full">
                  <x-heroicon-o-information-circle
                    class="w-6 h-6 min-w-6 min-h-6 dark:text-yellow-400 flex-shrink-0" />
                  <p class="font-medium text-sm">
                    {{ t('v3_setup_description') }}
                  </p>
                </div>
              </x-dynamic-alert>
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
