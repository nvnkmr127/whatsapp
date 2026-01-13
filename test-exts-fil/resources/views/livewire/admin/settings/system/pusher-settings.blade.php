<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('real_time_event_broadcasting') }}
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
              {{ t('real_time_event_broadcasting') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('real_time_event_broadcasting_description') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- APP ID -->
              <div>
                <x-label for="app_id" :value="t('app_id')" />
                <x-input wire:model.defer="app_id" id="app_id" type="text" class="mt-1" />
                <x-input-error for="app_id" class="mt-2" />
              </div>

              <!-- APP Key -->
              <div>
                <x-label for="app_key" :value="t('app_key')" />
                <x-input wire:model.defer="app_key" id="app_key" type="text" class="mt-1" />
                <x-input-error for="app_key" class="mt-2" />
              </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- APP Secret -->
              <div class="mt-2">
                <x-label for="app_secret" :value="t('app_secret')" />
                <x-input wire:model.defer="app_secret" id="app_secret" type="text"
                  class="mt-1" />
                <x-input-error for="app_secret" class="mt-2" />
              </div>

              <!-- Link Text -->
              <div>
                <x-label for="cluster"
                  class="flex items-center justify-between space-x-1 sm:mt-px sm:pt-2">
                  <div class="flex items-center flex-wrap space-x-1">
                    <span data-tippy-content="{{ t('leave_blank_for_default_cluster') }}">
                      <x-heroicon-o-question-mark-circle
                        class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                    </span>
                    <span
                      class="font-medium text-sm text-slate-700 dark:text-slate-200">{{ t('cluster') }}</span>
                    <a href="https://pusher.com/docs/clusters" target="_blank"
                      class="text-blue-500"> {{ t('pusher_link') }} </a>
                  </div>
                </x-label>
                <x-input wire:model.defer="cluster" id="cluster" type="text" class="mt-1" />
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-2">
              <!-- Link Text -->
              <div x-data="{ 'real_time_notify': @entangle('real_time_notify') }">
                <x-label for="real_time_notify"
                  class="flex items-center justify-between space-x-1 sm:mt-px sm:pt-2">
                  <div class="flex items-center flex-wrap space-x-1">
                    <span
                      class="font-medium text-sm text-slate-700 dark:text-slate-200">{{ t('enable_real_time_notifications') }}</span>
                  </div>
                </x-label>
                <div class="mt-1">
                  <button type="button" x-on:click="real_time_notify = !real_time_notify"
                    class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                    role="switch" :aria-checked="real_time_notify.toString()"
                    :class="{
                        'bg-indigo-600': real_time_notify,
                        'bg-gray-300': !real_time_notify
                    }">
                    <span class="sr-only">{{ t('enable_real_time_notifications') }}</span>
                    <span aria-hidden="true"
                      class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                    <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="real_time_notify ? 'translate-x-5' : 'translate-x-0'">
                      <span
                          class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                          :class="real_time_notify ? 'opacity-0 ease-out duration-100' :
                              'opacity-100 ease-in duration-200'">
                          <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                      </span>
                      <span
                          class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                          :class="real_time_notify ? 'opacity-100 ease-in duration-200' :
                              'opacity-0 ease-out duration-100'">
                          <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                      </span>
                    </span>
                  </button>

                </div>
              </div>

              <div x-data="{ 'desk_notify': @entangle('desk_notify') }">
                <x-label for="desk_notify"
                  class="flex items-center justify-between space-x-1 sm:mt-px sm:pt-2">
                  <div class="flex items-center flex-wrap space-x-1">
                    <span data-tippy-content="{{ t('dest_notify_desc') }}">
                      <x-heroicon-o-question-mark-circle
                        class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                    </span>
                    <span
                      class="font-medium text-sm text-slate-700 dark:text-slate-200">{{ t('enable_desktop_notifications') }}</span>
                  </div>
                </x-label>
                <div class="mt-1">
                  <button type="button" x-on:click="desk_notify = !desk_notify"
                    class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                    role="switch" :aria-checked="desk_notify.toString()"
                    :class="{
                        'bg-indigo-600': desk_notify,
                        'bg-gray-300': !desk_notify
                    }">
                    <span class="sr-only">{{ t('enable_desktop_notifications') }}</span>
                    <span aria-hidden="true"
                      class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                    <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="desk_notify ? 'translate-x-5' : 'translate-x-0'">
                      <span
                          class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                          :class="desk_notify ? 'opacity-0 ease-out duration-100' :
                              'opacity-100 ease-in duration-200'">
                          <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                      </span>
                      <span
                          class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                          :class="desk_notify ? 'opacity-100 ease-in duration-200' :
                              'opacity-0 ease-out duration-100'">
                          <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                      </span>
                    </span>
                  </button>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <x-label for="dismiss_desk_notification"
                class="flex items-center justify-between space-x-1 sm:mt-px sm:pt-2">
                <div>
                  <span data-tippy-content="{{ t('google_chrome') }}">
                    <x-heroicon-o-question-mark-circle
                      class="w-5 h-5 text-slate-500 dark:text-slate-400 inline-flex" />
                  </span>
                  <span
                    class="font-medium text-sm text-slate-700 dark:text-slate-200">{{ t('auto_dismiss_desktop') }}</span>
                </div>
              </x-label>
              <x-input wire:model.defer="dismiss_desk_notification" id="dismiss_desk_notification"
                type="number" class="mt-1" />
            </div>
          </x-slot:content>

          <!-- Submit Button -->
          @if (checkPermission('system_settings.edit'))
            <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
              <div class="flex justify-end space-x-2">
                <!--Button will only get displayed when all fields are filled -->
                @if (!empty($app_id) && !empty($app_key) && !empty($app_secret) && !empty($cluster))
                  <x-button.loading-button wire:click="testConnection">
                    {{ t('test_pusher') }}
                  </x-button.loading-button>
                @endif
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

@push('scripts')
  <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', () => {
      Alpine.store('echoManager').init();
      Alpine.store('pusherManager').init();

      //Listen to a test channel/event
      if (Alpine.store('echoManager').echo) {
        Alpine.store('echoManager').echo
          .channel('whatsmark-test-channel')
          .listen('.whatsmark-test-event', (data) => {
            Alpine.store('pusherManager').showDesktopNotification(data.title, {
              message: data.message || 'You have a new event notification',
            });
          });
      }
    });
  </script>
@endpush
