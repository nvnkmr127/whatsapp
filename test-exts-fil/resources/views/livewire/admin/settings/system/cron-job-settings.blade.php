<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('scheduled_tasks_management') }}
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
              {{ t('scheduled_tasks_management') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('cronjob_description') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div>
              <div class="flex justify-center items-center gap-2" x-data="{
                  copied: false,
                  copyText() {
                      const text = $refs.cronCommand?.value;
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
                <x-input name="cron-cmd" id="cron-cmd" type="text" class="block w-full"
                  x-ref="cronCommand" value="{{ $this->prepareCronUrl }}" disabled />

                <!-- Copy Button -->
                @php
                  $copyText = t('copy');
                  $copiedText = t('copied');
                @endphp
                <div class="flex justify-end mt-1">
                  <x-button.secondary x-on:click="copyText()">
                    <span x-text="copied ? '{{ $copiedText }}' : '{{ $copyText }}'"></span>
                  </x-button.secondary>
                </div>

              </div>
              <div class="mt-4 flex justify-start">
                <x-button.primary wire:click="runCronManually" wire:loading.attr="disabled">
                  <span wire:loading.remove wire:target="runCronManually">
                    {{ t('run_cron_manually') }}
                  </span>
                  <span wire:loading wire:target="runCronManually">
                    {{ t('running') }}...
                  </span>
                </x-button.primary>
              </div>

              @if ($this->isCronStale())
                <div
                  class="bg-red-100 rounded border-red-500 text-red-700 px-2 py-3 mt-5 dark:bg-red-900 dark:border-red-700 dark:text-red-300"
                  role="alert">
                  <div class="flex justify-start items-center gap-2">
                    <x-heroicon-o-exclamation-circle class="w-6 h-6 dark:text-red-400" />
                    <div class="flex flex-col justify-start items-start">
                      <p class="font-medium">
                        {{ t('cron_not_running') }}
                      </p>
                      <p class='text-xs'>
                        {{ t('last_checked_at') }}: {{ $last_cron_run }}
                        <span
                          class="ml-1 text-gray-500 font-medium">({{ $last_cron_run_datetime }})</span>
                      </p>
                      <p class="text-xs mt-1">
                        {{ t('please_check_cron_setup') }}
                      </p>
                    </div>
                  </div>
                </div>
              @else
                <div
                  class="bg-green-100 rounded border-green-500 text-green-700 px-2 py-3 mt-5 dark:bg-green-900 dark:border-green-700 dark:text-green-300"
                  role="alert">
                  <div class="flex justify-start items-center gap-2">
                    <x-heroicon-o-check-circle class="w-6 h-6 dark:text-green-400" />
                    <div class="flex flex-col justify-start items-start">
                      <p class="font-medium py-0.5">
                        {{ t('cronjob_running') }}
                      </p>
                      <p class='text-xs py-0.5'>
                        {{ t('last_checked_at') }}: {{ $last_cron_run }}
                      </p>
                      <p class='text-xs text-green-700 dark:text-green-300 py-0.5'>
                        {{ t('Datetime') }}: {{ format_date_time($last_cron_run_datetime) }}
                      </p>
                      <p class='text-xs text-green-700 dark:text-green-300 py-0.5'>
                        {{ t('Execution duration') }}:
                        @php
                          $executionTime = (int) get_setting('cron-job.last_execution_time', 0);

                          if ($executionTime > 0) {
                              // Cap extremely large values
                              if ($executionTime > 3600) {
                                  echo '< 60 ' . t('minutes');
                              } elseif ($executionTime >= 120) {
                                  // Format as minutes and seconds for times ≥ 2 minutes
                                  $minutes = floor($executionTime / 60);
                                  $seconds = $executionTime % 60;
                                  echo $minutes .
                                      ' ' .
                                      t('mins') .
                                      ' ' .
                                      $seconds .
                                      ' ' .
                                      t('seconds');
                              } else {
                                  // Just show seconds for times under 2 minutes
                                  echo $executionTime . ' ' . t('seconds');
                              }
                          } else {
                              echo t('not_available');
                          }
                        @endphp
                      </p>
                      <p class='text-xs text-green-700 dark:text-green-300'>
                        {{ t('Current Status') }}:
                        @php
                          $status = get_setting('cron-job.status', 'unknown');
                        @endphp

                        @if ($status == 'completed')
                          <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            <span class="w-2 h-2 mr-1 bg-green-400 rounded-full"></span>
                            {{ t('completed') }}
                          </span>
                        @elseif($status == 'running')
                          <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            <span
                              class="w-2 h-2 mr-1 bg-blue-400 rounded-full animate-pulse"></span>
                            {{ t('running') }}
                          </span>
                        @elseif($status == 'failed')
                          <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            <span class="w-2 h-2 mr-1 bg-red-400 rounded-full"></span>
                            {{ t('failed') }}
                          </span>
                        @else
                          <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            <span class="w-2 h-2 mr-1 bg-gray-400 rounded-full"></span>
                            {{ t('unknown') }}
                          </span>
                        @endif
                      </p>
                    </div>
                  </div>
                </div>
              @endif
              <div class="my-4 border-t dark:border-t-slate-200/5 py-4">
                <h3 class="font-semibold dark:text-slate-400">{{ t('setting_up_the_cronjob') }}
                </h3>
                <ol class="text-sm list-decimal space-y-2 px-5 mt-3 dark:text-slate-400">
                  <li>{{ t('cronjob_1') }}</li>
                  <li>{{ t('cronjob_2') }}
                  </li>
                  <li>{{ t('cronjob_3') }}</li>
                  <li>{{ t('cronjob_4') }}
                  </li>
                </ol>
                <p class="text-sm mt-4 dark:text-slate-400">
                  {{ t('link_description') }} <a href="https://laravel.com/docs/11.x/scheduling"
                    target="_blank"
                    class="hover:underline text-green-500">{{ t('documentation') }}</a>.
                </p>
              </div>
            </div>
          </x-slot:content>
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
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Update the last checked time every minute
    setInterval(function() {
      const lastCheckedElement = document.getElementById('last-checked-time');
      if (lastCheckedElement && lastCheckedElement.dataset.timestamp) {
        const timestamp = parseInt(lastCheckedElement.dataset.timestamp);
        const lastChecked = new Date(timestamp * 1000);
        const now = new Date();
        const diffInSeconds = Math.floor((now - lastChecked) / 1000);

        if (diffInSeconds < 60) {
          lastCheckedElement.textContent = `${diffInSeconds} seconds ago`;
        } else if (diffInSeconds < 3600) {
          lastCheckedElement.textContent = `${Math.floor(diffInSeconds / 60)} minutes ago`;
        } else {
          lastCheckedElement.textContent = `${Math.floor(diffInSeconds / 3600)} hours ago`;
        }
      }
    }, 60000); // Update every minute
  });
</script>
