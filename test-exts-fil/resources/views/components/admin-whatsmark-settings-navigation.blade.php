<div {!! $attributes !!}>
  <div class="sm:hidden">
    <x-dropdown width="full" align="top">
      <x-slot:trigger>
        <button type="button"
          class="relative w-full cursor-default rounded-md border border-slate-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:focus:ring-offset-slate-800">
          @if (request()->routeIs('admin.whatsapp-auto-lead.settings.view'))
            {{ t('whatsapp_auto_lead') }}
          @elseif(request()->routeIs('admin.stop-bot.settings.view'))
            {{ t('stop_bot') }}
          @elseif(request()->routeIs('admin.web-hooks.settings.view'))
            {{ t('web_hooks') }}
          @elseif(request()->routeIs('admin.support-agent.settings.view'))
            {{ t('support_agent') }}
          @elseif(request()->routeIs('admin.notification-sound.settings.view'))
            {{ t('notification_sound') }}
          @elseif(request()->routeIs('admin.ai-integration.settings.view'))
            {{ t('ai_integration') }}
          @elseif(request()->routeIs('admin.auto-clear-chat-history.settings.view'))
            {{ t('auto_clear_chat_history') }}
          @else
            {{ t('please_select_an_option') }}
          @endif
          <span class="pointer-events-none absolute inset-y-0 right-0 ml-3 flex items-center pr-3">
            <x-heroicon-m-chevron-up-down class="h-5 w-5 text-slate-400" />
          </span>
        </button>
      </x-slot:trigger>
      <x-slot:content>
        <x-dropdown-link href="{{ route('admin.whatsapp-auto-lead.settings.view') }}">
          {{ t('Whatsapp Auto Lead') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.stop-bot.settings.view') }}">
          {{ t('Stop Bot') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.web-hooks.settings.view') }}">
          {{ t('web_hooks') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.support-agent.settings.view') }}">
          {{ t('support_agent') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.notification-sound.settings.view') }}">
          {{ t('notification_sound') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.ai-integration.settings.view') }}">
          {{ t('ai_integration') }}
        </x-dropdown-link>
        <x-dropdown-link href="{{ route('admin.auto-clear-chat-history.settings.view') }}">
          {{ t('auto_clear_chat_history') }}
        </x-dropdown-link>
      </x-slot:content>
    </x-dropdown>
  </div>

  <div class="hidden sm:block">
    <div
      class="bg-white ring-1 ring-slate-300 sm:rounded-lg dark:bg-transparent dark:ring-slate-600 p-4">
      <div>
        <nav class="flex flex-col gap-1 justify-start" aria-label="Tabs">
          <!-- WhatsappAutoLead -->
          <a href="{{ route('admin.whatsapp-auto-lead.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.whatsapp-auto-lead.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.whatsapp-auto-lead.settings.view'),
            ])>
            <x-heroicon-o-chat-bubble-bottom-center-text class="w-6 h-6 md:flex-none" />
            <span>{{ t('Whatsapp Auto Lead') }}</span>
          </a>
          <!-- StopBot Settings -->
          <a href="{{ route('admin.stop-bot.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.stop-bot.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.stop-bot.settings.view'),
          ])>
            <x-heroicon-o-shield-check class="w-6 h-6 md:flex-none" />
            <span>{{ t('Stop Bot') }}</span>
          </a>

          <!-- Re-captcha -->
          <a href="{{ route('admin.web-hooks.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.web-hooks.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.web-hooks.settings.view'),
          ])>
            <x-heroicon-o-arrow-path-rounded-square class="w-6 h-6 md:flex-none" />
            <span>{{ t('web_hooks') }}</span>
          </a>

          <!-- SupportAgent -->
          <a href="{{ route('admin.support-agent.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.support-agent.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.support-agent.settings.view'),
          ])>
            <x-heroicon-o-megaphone class="w-6 h-6 md:flex-none" />
            <span>{{ t('support_agent') }}</span>
          </a>

          <!-- NotificationSound -->
          <a href="{{ route('admin.notification-sound.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.notification-sound.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.notification-sound.settings.view'),
            ])>
            <x-heroicon-o-bell-alert class="w-6 h-6 md:flex-none" />
            <span>{{ t('notification_sound') }}</span>
          </a>

          <!-- AiIntegration -->
          <a href="{{ route('admin.ai-integration.settings.view') }}" @class([
              'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md border-b  font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
              'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                  'admin.ai-integration.settings.view'),
              'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                  'admin.ai-integration.settings.view'),
          ])>
            <x-heroicon-o-cpu-chip class="w-6 h-6 md:flex-none" />
            <span>{{ t('ai_integration') }}</span>
          </a>

          <!-- AutoClearChatHistory -->
          <a href="{{ route('admin.auto-clear-chat-history.settings.view') }}"
            @class([
                'flex items-center space-x-3 text-sm p-1 py-2 rounded-t-md font-medium hover:bg-gray-50 dark:hover:bg-slate-800',
                'text-indigo-600 dark:bg-slate-800' => request()->routeIs(
                    'admin.auto-clear-chat-history.settings.view'),
                'border-slate-200 text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:text-slate-400 dark:hover:text-slate-300' => !request()->routeIs(
                    'admin.auto-clear-chat-history.settings.view'),
            ])>

            <x-heroicon-o-trash class="w-6 h-6 md:flex-none" />
            <span>{{ t('auto_clear_chat_history') }}</span>
          </a>

        </nav>
      </div>
    </div>
  </div>

</div>
