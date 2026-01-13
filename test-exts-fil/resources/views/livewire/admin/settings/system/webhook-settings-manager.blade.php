<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('webhook_integrations') }}
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
              {{ t('webhook_integrations') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('webhook_integrations_description') }}
            </x-settings-description>
          </x-slot:header>

          <x-slot:content>
            <div class="space-y-4">
              <!-- Enable Webhook Toggle -->
              <div class="pt-4">
                <h3 class="text-base font-medium text-gray-900 dark:text-white">
                  {{ t('enable_webhook_access') }}</h3>
                <x-toggle wire:model="webhook_enabled" :value="$webhook_enabled" class="mt-2" />
              </div>

              <!-- Webhook URL -->
              <div x-data="{ webhook_enabled: @entangle('webhook_enabled') }">
                <div class="flex items-center">
                  <span x-show="webhook_enabled" class="text-red-500 mr-1">*</span>
                  <h3 class="text-base font-medium text-gray-900 dark:text-white">
                    {{ t('webhook_url') }}
                  </h3>
                </div>

                <div class="mt-2 flex rounded-md">
                  <input type="url" wire:model="webhook_url"
                    class="flex-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:text-white"
                    placeholder="https://your-domain.com/webhook" />
                </div>
                <x-input-error for="webhook_url" />
              </div>

              <!-- Webhook Abilities -->
              <div class="pt-4">
                <h3 class="text-base font-medium text-gray-900 dark:text-white">
                  {{ t('webhook_abilities') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ t('default_permissions_for_webhook_access') }}</p>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  <!-- Contacts Section -->
                  <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                      {{ t('contacts') }}</h4>
                    <div class="space-y-2">

                      <div
                        class="flex items-center bg-blue-50 dark:bg-blue-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-blue-800 dark:text-blue-300 truncate text-wrap">
                            {{ t('contacts_create') }}
                          </span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="contacts_actions.create" :value="$contacts_actions['create']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-green-50 dark:bg-green-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-green-800 dark:text-green-300 truncate text-wrap">{{ t('contacts_read') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="contacts_actions.read" :value="$contacts_actions['read']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-yellow-50 dark:bg-yellow-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-yellow-800 dark:text-yellow-300 truncate text-wrap">{{ t('contacts_update') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="contacts_actions.update" :value="$contacts_actions['update']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-red-50 dark:bg-red-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-red-800 dark:text-red-300 truncate text-wrap">{{ t('contacts_delete') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="contacts_actions.delete" :value="$contacts_actions['delete']"
                            size="xs" />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Status Section -->
                  <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                      {{ t('statuses') }}</h4>
                    <div class="space-y-2">
                      <div
                        class="flex items-center bg-blue-50 dark:bg-blue-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-blue-800 dark:text-blue-300 truncate text-wrap">{{ t('status_create') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="status_actions.create" :value="$status_actions['create']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-green-50 dark:bg-green-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-green-800 dark:text-green-300 truncate text-wrap">{{ t('status_read') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="status_actions.read" :value="$status_actions['read']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-yellow-50 dark:bg-yellow-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-yellow-800 dark:text-yellow-300 truncate text-wrap">{{ t('status_update') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="status_actions.update" :value="$status_actions['update']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-red-50 dark:bg-red-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-red-800 dark:text-red-300 truncate text-wrap">{{ t('status_delete') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="status_actions.delete" :value="$status_actions['delete']"
                            size="xs" />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Sources Section -->
                  <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                      {{ t('sources') }}</h4>
                    <div class="space-y-2">
                      <div
                        class="flex items-center bg-blue-50 dark:bg-blue-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-blue-800 dark:text-blue-300 truncate text-wrap">{{ t('source_create') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="source_actions.create" :value="$source_actions['create']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-green-50 dark:bg-green-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-green-800 dark:text-green-300 truncate text-wrap">{{ t('source_read') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="source_actions.read" :value="$source_actions['read']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-yellow-50 dark:bg-yellow-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-yellow-800 dark:text-yellow-300 truncate text-wrap">{{ t('source_update') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="source_actions.update" :value="$source_actions['update']"
                            size="xs" />
                        </div>
                      </div>

                      <div
                        class="flex items-center bg-red-50 dark:bg-red-900/20 rounded px-2.5 py-1">
                        <div class="flex-grow min-w-0">
                          <span
                            class="text-xs font-medium text-red-800 dark:text-red-300 truncate text-wrap">{{ t('source_delete') }}</span>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                          <x-toggle wire:model="source_actions.delete" :value="$source_actions['delete']"
                            size="xs" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              @if (session()->has('success'))
                <div class="rounded-md bg-green-50 p-4">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20"
                        fill="currentColor">
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
