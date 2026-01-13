<div
  class="bg-white sticky top-0 z-20 flex-shrink-0 flex h-16 border-b border-slate-200 dark:border-slate-600 dark:bg-slate-800">
  <button x-on:click="open = !open" type="button"
    class="px-4 border-r border-slate-200 text-slate-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-slate-900 lg:hidden dark:border-slate-600">
    <span class="sr-only">{{ t('open_sidebar') }}</span>
    <x-heroicon-o-bars-3-bottom-left class="h-6 w-6" />
  </button>
  <div class="flex-1 px-4 flex justify-between">
    <div class="flex-1 flex ">
    </div>
    <div class="flex items-center ">
      <x-dropdown customClasses="left-[-40px]">
        <x-slot:trigger>
          <button
            class="inline-flex items-center bg-white-600 px-4 py-2 text-sm font-medium text-gray-400 hover:text-slate-500">
            <x-feathericon-settings class="-ml-1 mr-2 w-5 h-5" />

          </button>
        </x-slot:trigger>
        <x-slot:content>
          @if (checkPermission(['system_settings.view', 'system_settings.edit']))
            <a wire:navigate href="{{ route('admin.general.settings.view') }}"
              class="group flex items-center px-5 py-2 text-sm font-medium  rounded-r-none
                {{ in_array(request()->route()->getName(), [
                    'admin.general.settings.view',
                    'admin.email.settings.view',
                    'admin.re-captcha.settings.view',
                    'admin.announcement.settings.view',
                    'admin.cron-job.settings.view',
                    'admin.seo.settings.view',
                    'admin.pusher.settings.view',
                    'admin.system-update.settings.view',
                    'admin.system-information.settings.view',
                    'admin.notification.settings.view',
                ])
                    ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
              <x-heroicon-o-cog
                class="mr-2 flex-shrink-0 h-6 w-6
                    {{ in_array(request()->route()->getName(), [
                        'admin.general.settings.view',
                        'admin.email.settings.view',
                        'admin.re-captcha.settings.view',
                        'admin.announcement.settings.view',
                        'admin.cron-job.settings.view',
                        'admin.seo.settings.view',
                        'admin.pusher.settings.view',
                        'admin.system-update.settings.view',
                        'admin.system-information.settings.view',
                        'admin.notification.settings.view',
                    ])
                        ? 'text-indigo-600 dark:text-slate-300'
                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                aria-hidden="true" />
              {{ t('system') }}
            </a>
          @endif

          <!-- WhatsMark Settings -->
          @if (checkPermission('whatsmark_settings.view'))
            <a wire:navigate href="{{ route('admin.whatsapp-auto-lead.settings.view') }}"
              class="group flex items-center px-5 py-2 text-sm font-medium rounded-r-none
                {{ in_array(request()->route()->getName(), [
                    'admin.whatsapp-auto-lead.settings.view',
                    'admin.stop-bot.settings.view',
                    'admin.web-hooks.settings.view',
                    'admin.support-agent.settings.view',
                    'admin.notification-sound.settings.view',
                    'admin.ai-integration.settings.view',
                    'admin.auto-clear-chat-history.settings.view',
                ])
                    ? ' bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 language'
                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
              <x-heroicon-o-wrench-screwdriver
                class="mr-2 flex-shrink-0 h-6 w-6
                    {{ in_array(request()->route()->getName(), [
                        'admin.whatsapp-auto-lead.settings.view',
                        'admin.stop-bot.settings.view',
                        'admin.web-hooks.settings.view',
                        'admin.support-agent.settings.view',
                        'admin.notification-sound.settings.view',
                        'admin.ai-integration.settings.view',
                        'admin.auto-clear-chat-history.settings.view',
                    ])
                        ? 'text-indigo-600 dark:text-slate-300'
                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                aria-hidden="true" />
              {{ t('whatsmark') }}
            </a>
          @endif
        </x-slot:content>
      </x-dropdown>
      <x-dropdown>
        <x-slot:trigger>
          <x-button.primary-round class="sm:block">
            <x-heroicon-m-plus class="w-4 h-4" />
          </x-button.primary-round>
        </x-slot:trigger>
        <x-slot:content>

          <!-- Menu Items -->
          <a href="{{ route('admin.contacts.save') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                     {{ request()->routeIs('admin.contacts.save')
                                         ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 language'
                                         : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-user-circle
              class="mr-4 flex-shrink-0 h-6 w-6
                                     {{ request()->routeIs('admin.contacts.save')
                                         ? 'text-indigo-700 dark:text-slate-300'
                                         : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
            {{ t('contact') }}
          </a>

          {{-- Campaigns --}}
          <a href="{{ route('admin.campaigns.save') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                 {{ request()->routeIs('admin.campaigns.save')
                                     ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 language'
                                     : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-megaphone
              class="mr-4 flex-shrink-0 h-6 w-6
                                     {{ request()->routeIs('admin.campaigns.save')
                                         ? 'text-indigo-600 dark:text-slate-300'
                                         : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
              aria-hidden="true" />
            {{ t('campaign') }}
          </a>

          <!-- Message Bot -->
          <a href="{{ route('admin.messagebot.create') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.messagebot.create')
                                ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-chat-bubble-bottom-center-text
              class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.messagebot.create')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
              aria-hidden="true" />
            {{ t('message_bot') }}
          </a>

          <!-- Template Bot -->
          <a href="{{ route('admin.templatebot.create') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.templatebot.create')
                                ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-tag
              class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.templatebot.create')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
              aria-hidden="true" />
            {{ t('template_bot') }}
          </a>

          <!-- Users -->
          <a href="{{ route('admin.users.save') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.users.save')
                                ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-users
              class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.users.save')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
              aria-hidden="true" />
            {{ t('user') }}
          </a>

          <!-- Role -->
          <a href="{{ route('admin.roles.save') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                   {{ request()->routeIs('admin.roles.save')
                                       ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                       : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-swatch
              class="mr-4 flex-shrink-0 h-6 w-6
                                       {{ request()->routeIs('admin.roles.save')
                                           ? 'text-indigo-600 dark:text-slate-300'
                                           : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
              aria-hidden="true" />
            {{ t('role') }}
          </a>

          <a href="{{ route('admin.status') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                {{ request()->routeIs('admin.status')
                                    ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-c-adjustments-horizontal
              class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.status')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
            {{ t('status') }}
          </a>

          <a href="{{ route('admin.source') }}"
            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.source')
                                        ? '  bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
            <x-heroicon-o-square-3-stack-3d
              class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.source')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
            {{ t('source') }}
          </a>
        </x-slot:content>
      </x-dropdown>

      {{-- language dropdown : Start --}}
      <livewire:language-switcher />
      {{-- language dropdown : Over --}}

      <!-- Theme switcher -->
      <div class="relative mr-2">
        <x-dropdown>
          <x-slot:trigger>
            <button type="button"
              class="p-1 text-slate-400 rounded-full hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:text-slate-300 dark:hover:text-slate-200 dark:focus:ring-offset-slate-800">
              <template x-if="theme === 'light'">
                <x-heroicon-o-sun class="w-6 h-6" />
              </template>
              <template x-if="theme === 'dark'">
                <x-heroicon-o-moon class="w-6 h-6" />
              </template>
              <template x-if="theme === 'system'">
                <x-heroicon-o-computer-desktop class="w-6 h-6" />
              </template>
            </button>
          </x-slot:trigger>
          <x-slot:content>
            <x-dropdown-link
              x-on:click="theme = 'light'; document.documentElement.classList.remove('dark');
                        document.documentElement.classList.add('light');"
              role="button" class="flex items-center space-x-2">
              <x-heroicon-m-sun class="w-5 h-5" />
              <span>{{ t('light') }}</span>
            </x-dropdown-link>
            <x-dropdown-link
              x-on:click="theme = 'dark'; document.documentElement.classList.remove('light');
                        document.documentElement.classList.add('dark');"
              role="button" class="flex items-center space-x-2">
              <x-heroicon-m-moon class="w-5 h-5" />
              <span>{{ t('dark') }}</span>
            </x-dropdown-link>
            <x-dropdown-link
              x-on:click="theme = 'system'; if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            document.documentElement.classList.add('dark');
                            document.documentElement.classList.remove('light');
                        } else {
                            document.documentElement.classList.add('light');
                            document.documentElement.classList.remove('dark');
                        }"
              role="button" class="flex items-center space-x-2">
              <x-heroicon-m-computer-desktop class="w-5 h-5" />
              <span>{{ t('system') }}</span>
            </x-dropdown-link>
          </x-slot:content>
        </x-dropdown>
      </div>
      <!-- Profile dropdown -->
      <div class="relative">
        <x-dropdown>
          <x-slot:trigger>
            <button type="button"
              class="max-w-xs flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-slate-800"
              aria-expanded="false" aria-haspopup="true">
              <span class="sr-only">{{ t('open_user_menu') }}</span>
              @php

                $profileImage =
                    optional(Auth::user())->profile_image_url &&
                    Storage::disk('public')->exists(Auth::user()->profile_image_url)
                        ? asset('storage/' . Auth::user()->profile_image_url)
                        : asset('img/user-placeholder.jpg');
              @endphp

              <img src="{{ $profileImage }}" alt="{{ t('avatar') }}"
                class="w-9 h-9 rounded-full object-cover">

            </button>
          </x-slot:trigger>
          <x-slot:content>
            <x-dropdown-link href="{{ route('admin.profile.edit') }}">
              {{ t('account_profile') }}
            </x-dropdown-link>
            <form method="POST" action="{{ route('logout') }}">
              @csrf

              <x-dropdown-link :href="route('logout')"
                onclick="event.preventDefault();
                        this.closest('form').submit();">
                {{ t('logout_ve') }}
              </x-dropdown-link>
            </form>
          </x-slot:content>
        </x-dropdown>
      </div>
    </div>
  </div>
</div>
