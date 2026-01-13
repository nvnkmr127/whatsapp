<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('api_integration_and_access') }}
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
              {{ t('api_integration_and_access') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('api_integration_and_access_description') }}
            </x-settings-description>
          </x-slot:header>

          <x-slot:content>
            <div class="space-y-4">
              <!-- Enable API Access -->
              <div>
                <x-input-label for="enabled" :value="t('enable_api_access')" />
                <div class="mt-2">
                  <x-toggle :value="$isEnabled"
                    @toggle-changed.window="$wire.toggleApiAccess($event.detail.value)" />
                </div>
              </div>

              <!-- API Token -->
              <div>
                <x-input-label for="token" :value="t('api_token')" />
                <div class="mt-2 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2"
                  x-data="{
                      copied: false,
                      copyText() {
                          const text = $refs.currentToken?.value;
                          if (!text) {
                              showNotification('No text found to copy', 'danger');
                              return;
                          }
                          copyToClipboard(text);
                          this.copied = true;
                          setTimeout(() => this.copied = false, 2000);
                      }
                  }">

                  <!-- Input Field -->
                  <x-input id="token" type="text" class="flex-1 w-full" :value="$currentToken"
                    readonly x-ref="currentToken" />

                  <!-- Buttons -->
                  <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                    <x-button.secondary type="button" wire:click="generateNewToken"
                      class="w-full sm:w-auto">
                      {{ t('generate_new_token') }}
                    </x-button.secondary>

                    @if ($currentToken)
                      <x-button.secondary x-on:click="copyText()" class="w-full sm:w-auto ">
                        <span x-text="copied ? 'Copied' : 'Copy'">{{ t('copy') }}</span>
                      </x-button.secondary>
                    @endif
                  </div>
                </div>

                @if ($newTokenGenerated)
                  <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-500">
                    {{ t('please_copy_your_new_api_token_now') }}
                  </p>
                @endif
              </div>

              <!-- Token Abilities -->
              <div>
                <x-input-label :value="t('token_abilities')" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ t('these_are_the_default_permissions_for_api_access') }}
                </p>
                <div class="space-y-4 mt-3">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                      class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-sm p-4">
                      <h4
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 border-b pb-2">
                        {{ t('contacts') }} </h4>
                      <div class="flex flex-wrap gap-2">
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                          {{ t('contacts_create') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                          {{ t('contacts_read') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                          {{ t('contacts_update') }} </span>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                          {{ t('contacts_delete') }} </span>
                      </div>
                    </div>

                    <div
                      class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-sm p-4">
                      <h4
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 border-b pb-2">
                        {{ t('statuses') }} </h4>
                      <div class="flex flex-wrap gap-2">
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                          {{ t('status_create') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                          {{ t('status_read') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                          {{ t('status_update') }} </span>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                          {{ t('status_delete') }} </span>
                      </div>
                    </div>

                    <div
                      class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-sm p-4">
                      <h4
                        class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 border-b pb-2">
                        {{ t('sources') }} </h4>
                      <div class="flex flex-wrap gap-2">
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                          {{ t('source_create') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                          {{ t('source_read') }} </span>
                        <span
                          class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                          {{ t('source_update') }} </span>
                        <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                          {{ t('source_delete') }} </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              @if (session()->has('success'))
                <div class="rounded-md bg-green-50 p-4">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-green-800">
                        {{ session('Success') }}
                      </p>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </x-slot:content>

          @if (checkPermission('system_settings.edit'))
            <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg p-4">
              <div class="flex justify-end items-center">
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
