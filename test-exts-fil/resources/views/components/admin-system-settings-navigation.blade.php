<div {!! $attributes !!}>
  <div class="sm:hidden">
    <x-dropdown width="full" align="top">
      <x-slot:trigger>
        <button type="button"
          class="relative w-full cursor-default rounded-md border border-slate-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:focus:ring-offset-slate-800">
          @if (request()->routeIs('admin.general.settings.view'))
            {{ t('general') }}
          @elseif(request()->routeIs('admin.email.settings.view'))
            {{ t('email') }}
          @elseif(request()->routeIs('admin.re-captcha.settings.view'))
            {{ t('re_captcha') }}
          @elseif(request()->routeIs('admin.announcement.settings.view'))
            {{ t('announcement') }}
          @elseif(request()->routeIs('admin.cron-job.settings.view'))
            {{ t('cronjob') }}
          @elseif(request()->routeIs('admin.seo.settings.view'))
            {{ t('seo') }}
          @elseif(request()->routeIs('admin.pusher.settings.view'))
            {{ t('pusher') }}
          @elseif(request()->routeIs('admin.api-management.settings.view'))
            {{ t('api_management') }}
          @elseif(request()->routeIs('admin.webhook.settings.view'))
            {{ t('settings_webhook') }}
          @elseif(request()->routeIs('admin.cache-management.settings.view'))
            {{ t('cache_management') }}
          @elseif(request()->routeIs('admin.system-update.settings.view'))
            {{ t('system_update') }}
          @elseif(request()->routeIs('admin.system-information.settings.view'))
            {{ t('system_information') }}
          @else
            {{ t('please_select_an_option') }}
          @endif
          <span class="pointer-events-none absolute inset-y-0 right-0 ml-3 flex items-center pr-3">
            <x-heroicon-m-chevron-up-down class="h-5 w-5 text-slate-400" />
          </span>
        </button>
      </x-slot:trigger>
      <x-slot:content>
        <x-dropdown-link href="{{ route('admin.general.settings.view') }}">
          {{ t('general') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.email.settings.view') }}">
          {{ t('email') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.re-captcha.settings.view') }}">
          {{ t('re_captcha') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.announcement.settings.view') }}">
          {{ t('announcement') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.cron-job.settings.view') }}">
          {{ t('cronjob') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.seo.settings.view') }}">
          {{ t('seo') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.pusher.settings.view') }}">
          {{ t('pusher') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.api-management.settings.view') }}">
          {{ t('api_management') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.webhook.settings.view') }}">
          {{ t('settings_webhook') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.cache-management.settings.view') }}">
          {{ t('cache_management') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.system-update.settings.view') }}">
          {{ t('system_update') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.system-information.settings.view') }}">
          {{ t('system_information') }}
        </x-dropdown-link>
      </x-slot:content>
    </x-dropdown>
  </div>

  <div class="hidden sm:block">
    <div
      class="bg-white ring-1 ring-slate-300 sm:rounded-lg dark:bg-transparent dark:ring-slate-600 p-4">
      <div>
        <nav class="flex flex-col gap-1 justify-start" aria-label="Tabs">
          <!-- General -->
          <a href="{{ route('admin.general.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.general.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.general.settings.view'),
          ])>
            <x-heroicon-o-cog class="w-6 h-6" />
            <span>{{ t('general') }}</span>
          </a>
          <!-- Email Settings -->
          <a href="{{ route('admin.email.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.email.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.email.settings.view'),
          ])>
            <x-heroicon-o-envelope class="w-6 h-6" />
            <span>{{ t('email') }}</span>
          </a>

          <!-- Re-captcha -->
          <a href="{{ route('admin.re-captcha.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.re-captcha.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.re-captcha.settings.view'),
          ])>
            <x-heroicon-o-arrow-path-rounded-square class="w-6 h-6" />
            <span>{{ t('re_captcha') }}</span>
          </a>

          <!-- Announcements -->
          <a href="{{ route('admin.announcement.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.announcement.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.announcement.settings.view'),
          ])>
            <x-heroicon-o-megaphone class="w-6 h-6 flex-shrink-0" />
            <span class="break-words break-all whitespace-normal w-full">
              {{ t('announcement') }}
            </span>
          </a>

          <!-- Cronjob -->
          <a href="{{ route('admin.cron-job.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.cron-job.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.cron-job.settings.view'),
          ])>
            <x-heroicon-o-clock class="w-6 h-6" />
            <span>{{ t('cronjob') }}</span>
          </a>

          <!-- SEO -->
          <a href="{{ route('admin.seo.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.seo.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.seo.settings.view'),
          ])>
            <x-heroicon-o-arrow-trending-up class="w-6 h-6" />
            <span>{{ t('seo') }}</span>
          </a>

          <!-- Pusher -->
          <a href="{{ route('admin.pusher.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.pusher.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.pusher.settings.view'),
          ])>
            <x-heroicon-o-bell class="w-6 h-6" />
            <span>{{ t('pusher') }}</span>
          </a>

          <!-- Api -->
          <a href="{{ route('admin.api-management.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.api-management.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.api-management.settings.view'),
          ])>
            <x-heroicon-m-arrows-up-down class="w-6 h-6 flex-shrink-0" />
            <span>{{ t('api_management') }}</span>
          </a>

          <!-- Webhook Settings -->
          <a href="{{ route('admin.webhook.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.webhook.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.webhook.settings.view'),
          ])>
            <x-carbon-webhook class="w-6 h-6 flex-shrink-0" />
            <span>{{ t('settings_webhook') }}</span>
          </a>

          <!-- Cache Management -->
          <a href="{{ route('admin.cache-management.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.cache-management.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.cache-management.settings.view'),
            ])>
            <x-heroicon-o-circle-stack class="w-6 h-6 flex-shrink-0" />
            <span>{{ t('cache_management') }}</span>
          </a>

          <!-- System Updater -->
            <a href="{{ route('admin.system-update.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.system-update.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.system-update.settings.view'),
            ])>
            <x-heroicon-o-cloud-arrow-up class="w-6 h-6 flex-shrink-0" />
            <span>{{ t('system_update') }}</span>
            </a>

          <!-- System Information -->
          <a href="{{ route('admin.system-information.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.system-information.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.system-information.settings.view'),
            ])>

            <x-heroicon-o-information-circle class="w-6 h-6 flex-shrink-0" />
            <span>{{ t('system_information') }}</span>
          </a>

        </nav>
      </div>
    </div>
  </div>

</div>
