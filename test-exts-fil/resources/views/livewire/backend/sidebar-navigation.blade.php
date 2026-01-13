<div style="color-scheme: dark;">
    @php
        $user = auth()->user();
    @endphp
    <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->

    <div x-cloak x-show="open" class="relative z-40 lg:hidden" role="dialog" aria-modal="true" x-data="{ mobileOpen: false }">
        <div x-show="open" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-on:click="open = false"
            class="fixed inset-0 bg-slate-600 bg-opacity-75"></div>

        <div class="fixed inset-0 flex z-40">
            <!-- Mobile Menu (Overlapping Open Menu) -->
            <div x-show="mobileOpen"
                class="absolute top-0 left-0 z-50 lg:hidden w-80 h-full bg-white dark:bg-slate-800 shadow-lg"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform -translate-x-full" x-init="mobileOpen = {{ json_encode(
                    request()->routeIs(
                        'admin.users.*',
                        'admin.roles.*',
                        'admin.status',
                        'admin.source',
                        'admin.ai-prompt',
                        'admin.canned-reply',
                        'admin.activity-log.*',
                        'admin.languages',
                        'admin.emails',
                        'admin.logs.index',
                    ),
                ) }}">

                <!-- Close Button -->
                <div class="flex justify-between items-center py-4 flex-shrink-0 px-5 bg-white dark:bg-slate-800">
                    <span class="text-lg font-semibold text-gray-600 dark:text-slate-300"> {{ t('setup') }}
                    </span>
                    <!-- Close Button -->
                    <button x-on:click.stop="mobileOpen = false" class="text-gray-500 dark:text-slate-400">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 flex flex-col overflow-y-auto">
                    <nav class="flex-1 px-2">

                        <!-- Users -->
                        @if (checkPermission('user.view'))
                            <a wire:navigate href="{{ route('admin.users.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.users.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-users
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.users.list')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('user') }}
                            </a>
                        @endif

                        <!-- Role -->
                        @if (checkPermission('role.view') && Auth::user()->is_admin)
                            <a wire:navigate href="{{ route('admin.roles.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.roles.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-swatch
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.roles.list')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('role') }}
                            </a>
                        @endif

                        @if (checkPermission('status.view'))
                            <a wire:navigate href="{{ route('admin.status') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.status')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-c-adjustments-horizontal
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.status')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                {{ t('status') }}
                            </a>
                        @endif

                        @if (checkPermission('source.view'))
                            <a wire:navigate href="{{ route('admin.source') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.source')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-square-3-stack-3d
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.source')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                {{ t('source') }}
                            </a>
                        @endif

                        <!-- AI Prompts -->
                        @if (checkPermission('ai_prompt.view'))
                            <a wire:navigate href="{{ route('admin.ai-prompt') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                {{ request()->routeIs('admin.ai-prompt')
                                    ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-rocket-launch
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.ai-prompt')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('ai_prompts') }}
                            </a>
                        @endif

                        <!-- Canned Reply -->
                        @if (checkPermission('canned_reply.view'))
                            <a wire:navigate href="{{ route('admin.canned-reply') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                        {{ request()->routeIs('admin.canned-reply')
                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-m-arrow-right-on-rectangle
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.canned-reply')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('canned_reply') }}
                            </a>
                        @endif

                        <!-- Activity Log -->
                        @if (checkPermission('activity_log.view'))
                            <a wire:navigate href="{{ route('admin.activity-log.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.activity-log.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-s-arrow-path
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.activity-log.list')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('activity_log') }}
                            </a>
                        @endif

                        <!-- Language for desktop -->
                        @if ($user->is_admin == 1)
                            <a wire:navigate href="{{ route('admin.languages') }}" @class([
                                'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                request()->routeIs('admin.languages')
                                    ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                            ])>
                                <x-heroicon-s-language @class([
                                    'mr-4 flex-shrink-0 h-6 w-6',
                                    request()->routeIs('admin.languages')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                ]) aria-hidden="true" />
                                {{ t('languages') }}
                            </a>
                        @endif

                        <!-- Email Templates -->
                        @if (checkPermission('email_template.view'))
                            <a wire:navigate href="{{ route('admin.emails') }}" @class([
                                'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                request()->routeIs('admin.emails')
                                    ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                            ])>
                                <x-heroicon-o-envelope @class([
                                    'mr-4 flex-shrink-0 h-6 w-6',
                                    request()->routeIs('admin.emails')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                ]) aria-hidden="true" />
                                {{ t('email_template_list_title') }}
                            </a>
                        @endif

                        {{-- System Logs --}}
                        @if ($user->is_admin == 1)
                            <a wire:navigate href="{{ route('admin.logs.index') }}" @class([
                                'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                request()->routeIs('admin.logs.index')
                                    ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                            ])>
                                <x-heroicon-o-document-chart-bar @class([
                                    'mr-4 flex-shrink-0 h-6 w-6',
                                    request()->routeIs('admin.logs.index')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                ]) aria-hidden="true" />
                                {{ t('system_logs') }}
                            </a>
                        @endif

                    </nav>
                </div>
            </div>
            <div x-show="open" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                x-on:click.away="open = false" class="relative flex flex-col pt-5 bg-white dark:bg-slate-800">
                <div x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="absolute top-0 right-0 -mr-12 pt-2">
                </div>

                <div class="flex-shrink-0 flex items-center justify-center w-full ">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center bg-white dark:bg-slate-800">
                        <img x-bind:src="theme === 'light' || (theme === 'system' && window.matchMedia(
                                    '(prefers-color-scheme: light)')
                                .matches) ?
                            '{{ get_setting('general.site_logo') ? Storage::url(get_setting('general.site_logo')) : url('./img/light_logo.png') }}' :
                            '{{ get_setting('general.site_dark_logo') ? Storage::url(get_setting('general.site_dark_logo')) : url('./img/dark_logo.png') }}'"
                            alt="#" class="h-12 md:h-10 sm:h-12 px-4 w-auto object-cover" x-cloak>
                    </a>
                </div>
                <div class="mt-5 flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 py-4">
                        {{-- Sidebar for admin : Start --}}
                        @if (request()->routeIs('admin.*'))
                            <a wire:navigate href="{{ route('admin.dashboard') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.dashboard')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-squares-2x2
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.dashboard')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('dashboard') }}
                            </a>

                            @if (
                                (get_setting('whatsapp.is_whatsmark_connected') == 0 || get_setting('whatsapp.is_webhook_connected') == 0) &&
                                    checkPermission('connect_account.connect'))
                                <a wire:navigate href="{{ route('admin.connect') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.connect')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-link
                                        class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.connect')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('connect_waba') }}
                                </a>
                            @elseif (get_setting('whatsapp.is_whatsmark_connected') == 1 &&
                                    get_setting('whatsapp.is_webhook_connected') == 1 &&
                                    checkPermission('connect_account.view'))
                                <a wire:navigate href="{{ route('admin.waba') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.waba')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-link
                                        class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.waba')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('connect_waba') }}
                                </a>
                            @endif

                            <!-- Menu Items -->
                            @if (checkPermission('contact.view'))
                                <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                    {{ t('contact') }}
                                </p>

                                <a wire:navigate href="{{ route('admin.contacts.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                            {{ request()->routeIs('admin.contacts.list')
                                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                                : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-user-circle
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.contacts.list')
                                                ? 'text-indigo-700 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                    {{ t('contact') }}
                                </a>
                            @endif

                            @if (checkPermission('template.view'))
                                <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                    {{ t('templates') }}
                                </p>
                                <!-- Menu Items -->
                                <a wire:navigate href="{{ route('admin.template.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                            {{ request()->routeIs('admin.template.list')
                                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-document
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.template.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                    {{ t('templates') }}
                                </a>
                            @endif

                            @if (checkPermission(['campaigns.view', 'bulk_campaigns.send', 'message_bot.view', 'template_bot.view']))
                                <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                    {{ t('marketing') }}
                                </p>
                            @endif
                            {{-- Campaigns --}}
                            @if (checkPermission('campaigns.view'))
                                <a wire:navigate href="{{ route('admin.campaigns.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.campaigns.list')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-megaphone
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.campaigns.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('campaign') }}
                                </a>
                            @endif

                            {{-- CSV Campaigns --}}
                            @if (checkPermission('bulk_campaigns.send'))
                                <a wire:navigate href="{{ route('admin.csvcampaign') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.csvcampaign')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-clipboard-document
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.csvcampaign')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('bulk_campaign') }}
                                </a>
                            @endif

                            <!-- Message Bot -->
                            @if (checkPermission('message_bot.view'))
                                <a wire:navigate href="{{ route('admin.messagebot.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.messagebot.list')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-chat-bubble-bottom-center-text
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.messagebot.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('message_bot') }}
                                </a>
                            @endif

                            <!-- Template Bot -->
                            @if (checkPermission('template_bot.view'))
                                <a wire:navigate href="{{ route('admin.templatebot.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.templatebot.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-tag
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.templatebot.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('template_bot') }}
                                </a>
                            @endif

                            <!-- Chat -->
                            @if (checkPermission('chat.view'))
                                <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                    {{ t('support') }}
                                </p>
                                <a wire:navigate href="{{ route('admin.chat') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.chat')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-chat-bubble-oval-left
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                {{ request()->routeIs('admin.chat')
                                    ? 'text-indigo-600 dark:text-slate-300'
                                    : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('chat') }}
                                </a>
                            @endif
                            <!-- Settings -->
                            @if (checkPermission(['system_settings.view', 'system_settings.edit', 'whatsmark_settings.view']))
                                <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                    {{ t('settings') }}
                                </p>
                            @endif
                            @if (checkPermission('system_settings.view'))
                                <a wire:navigate href="{{ route('admin.general.settings.view') }}"
                                    class="group flex items-center px-5 py-2 text-sm font-medium rounded-r-md
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
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
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
                                    {{ t('system_setting') }}
                                </a>
                            @endif

                            <!-- WhatsMark Settings -->
                            @if (checkPermission('whatsmark_settings.view'))
                                <a wire:navigate href="{{ route('admin.whatsapp-auto-lead.settings.view') }}"
                                    class="group flex items-center px-5 py-2 text-sm font-medium rounded-r-md
                            {{ in_array(request()->route()->getName(), [
                                'admin.whatsapp-auto-lead.settings.view',
                                'admin.stop-bot.settings.view',
                                'admin.web-hooks.settings.view',
                                'admin.support-agent.settings.view',
                                'admin.notification-sound.settings.view',
                                'admin.ai-integration.settings.view',
                                'admin.auto-clear-chat-history.settings.view',
                            ])
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
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
                                    {{ t('whatsmark_settings') }}
                                </a>
                            @endif
                            <!-- Setup -->
                            @if (checkPermission([
                                    'user.view',
                                    'role.view',
                                    'status.view',
                                    'source.view',
                                    'ai_prompt.view',
                                    'canned_reply.view',
                                    'activity_log.view',
                                    'email_template.view',
                                ]))
                                <button x-on:click.prevent="mobileOpen = true"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white mt-2 w-full">
                                    <x-heroicon-o-cog-6-tooth
                                        class="mr-4 flex-shrink-0 h-6 w-6 text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300"
                                        aria-hidden="true" />
                                    {{ t('setup') }}
                                </button>
                            @endif
                        @endif
                        {{-- sidebar for admin : end --}}
                    </nav>
                </div>
            </div>

            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Dummy element to force sidebar to shrink to fit close icon -->
            </div>
        </div>
    </div>
    <!-- Static sidebar for desktop -->
    <div class="hidden lg:flex lg:w-[15rem] lg:fixed lg:inset-y-0 z-40" x-data="{ setupMenu: {{ request()->routeIs('admin.users.list', 'admin.roles.list', 'admin.status', 'admin.source', 'admin.ai-prompt', 'admin.canned-reply', 'admin.activity-log.list', 'admin.languages', 'admin.emails', 'admin.logs.index') ? 'true' : 'false' }} }">
        <div class="flex-1 flex flex-col min-h-0 border-r border-slate-300 dark:border-r dark:border-slate-600">
            <!-- Close Button -->

            <div x-show="setupMenu" x-cloak class="hidden lg:flex lg:w-[15rem] lg:fixed lg:inset-y-0"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform -translate-x-full">
                <div class="flex-1 flex flex-col min-h-0 border-r border-slate-300 dark:border-slate-600">
                    <!-- Top bar with Close button -->
                    <div class="flex justify-between items-center py-4 flex-shrink-0 px-5 bg-white dark:bg-slate-800">
                        <span class="text-lg font-semibold text-gray-600 dark:text-slate-300">
                            {{ t('setup') }}
                        </span>
                        <!-- Close Button -->
                        <button x-on:click="setupMenu = false" class="text-gray-500 dark:text-slate-400">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="flex-1 flex flex-col overflow-y-auto bg-white dark:bg-slate-800">
                        <nav class="flex-1 px-2">
                            <!-- Users -->
                            @if (checkPermission('user.view'))
                                <a wire:navigate href="{{ route('admin.users.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.users.list')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-users
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.users.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('user') }}
                                </a>
                            @endif

                            <!-- Role -->
                            @if (checkPermission('role.view') && Auth::user()->is_admin)
                                <a wire:navigate href="{{ route('admin.roles.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.roles.list')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-swatch
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                            {{ request()->routeIs('admin.roles.list')
                                                ? 'text-indigo-600 dark:text-slate-300'
                                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('role') }}
                                </a>
                            @endif

                            @if (checkPermission('status.view'))
                                <a wire:navigate href="{{ route('admin.status') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.status')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-c-adjustments-horizontal
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.status')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                    {{ t('status') }}
                                </a>
                            @endif

                            @if (checkPermission('source.view'))
                                <a wire:navigate href="{{ route('admin.source') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.source')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-square-3-stack-3d
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.source')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                    {{ t('source') }}
                                </a>
                            @endif

                            <!-- AI Prompts -->

                            @if (checkPermission('ai_prompt.view'))
                                <a wire:navigate href="{{ route('admin.ai-prompt') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.ai-prompt')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-o-rocket-launch
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.ai-prompt')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('ai_prompts') }}
                                </a>
                            @endif

                            <!-- Canned Reply -->
                            @if (checkPermission('canned_reply.view'))
                                <a wire:navigate href="{{ route('admin.canned-reply') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.canned-reply')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-m-arrow-right-on-rectangle
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.canned-reply')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('canned_reply') }}
                                </a>
                            @endif
                            <!-- Activity Log -->
                            @if (checkPermission('activity_log.view'))
                                <a wire:navigate href="{{ route('admin.activity-log.list') }}"
                                    class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.activity-log.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                    <x-heroicon-s-arrow-path
                                        class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.activity-log.list')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                        aria-hidden="true" />
                                    {{ t('activity_log') }}
                                </a>
                            @endif

                            <!-- Language for desktop -->
                            @if ($user->is_admin == 1)
                                <a wire:navigate href="{{ route('admin.languages') }}" @class([
                                    'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                    request()->routeIs('admin.languages')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                                ])>
                                    <x-heroicon-s-language @class([
                                        'mr-4 flex-shrink-0 h-6 w-6',
                                        request()->routeIs('admin.languages')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                    ]) aria-hidden="true" />
                                    {{ t('languages') }}
                                </a>
                            @endif

                            <!-- Email Templates -->
                            @if (checkPermission('email_template.view'))
                                <a wire:navigate href="{{ route('admin.emails') }}" @class([
                                    'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                    request()->routeIs('admin.emails')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                                ])>
                                    <x-heroicon-o-envelope @class([
                                        'mr-4 flex-shrink-0 h-6 w-6',
                                        request()->routeIs('admin.emails')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                    ]) aria-hidden="true" />
                                    {{ t('email_template_list_title') }}
                                </a>
                            @endif

                            {{-- System Logs --}}
                            @if ($user->is_admin == 1)
                                <a wire:navigate href="{{ route('admin.logs.index') }}"
                                    @class([
                                        'group flex items-center px-4 py-2 text-sm font-medium rounded-r-md',
                                        request()->routeIs('admin.logs.index')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50 dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white',
                                    ])>
                                    <x-heroicon-o-document-chart-bar @class([
                                        'mr-4 flex-shrink-0 h-6 w-6',
                                        request()->routeIs('admin.logs.index')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300',
                                    ])
                                        aria-hidden="true" />
                                    {{ t('system_logs') }}
                                </a>
                            @endif

                        </nav>
                    </div>
                </div>
            </div>
            <div class="flex justify-center">
                <a wire:navigate href="{{ route('admin.dashboard') }}"
                    class="flex items-center bg-white dark:bg-slate-800">
                    <img x-bind:src="theme === 'light' || (theme === 'system' && window.matchMedia(
                                '(prefers-color-scheme: light)')
                            .matches) ?
                        '{{ get_setting('general.site_logo') ? Storage::url(get_setting('general.site_logo')) : url('./img/light_logo.png') }}' :
                        '{{ get_setting('general.site_dark_logo') ? Storage::url(get_setting('general.site_dark_logo')) : url('./img/dark_logo.png') }}'"
                        alt="#" class="h-20 my-1 object-contain" x-cloak>
                </a>
            </div>

            <div class="flex-1 flex flex-col overflow-y-auto bg-white dark:bg-slate-800">
                <nav class="flex-1 px-2 py-4">
                    {{-- Sidebar for admin : Start --}}
                    @if (request()->routeIs('admin.*'))
                        <a wire:navigate href="{{ route('admin.dashboard') }}"
                            class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                        {{ request()->routeIs('admin.dashboard')
                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                            <x-heroicon-o-squares-2x2
                                class="mr-4 flex-shrink-0 h-6 w-6
                        {{ request()->routeIs('admin.dashboard')
                            ? 'text-indigo-600 dark:text-slate-300'
                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                aria-hidden="true" />
                            {{ t('dashboard') }}
                        </a>

                        @if (
                            (get_setting('whatsapp.is_whatsmark_connected') == 0 || get_setting('whatsapp.is_webhook_connected') == 0) &&
                                checkPermission('connect_account.connect'))
                            <a wire:navigate href="{{ route('admin.connect') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.connect')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-link
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.connect')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('connect_waba') }}
                            </a>
                        @elseif (get_setting('whatsapp.is_whatsmark_connected') == 1 &&
                                get_setting('whatsapp.is_webhook_connected') == 1 &&
                                checkPermission('connect_account.view'))
                            <a wire:navigate href="{{ route('admin.waba') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.waba')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-link
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.waba')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('connect_waba') }}
                            </a>
                        @endif

                        <!-- Menu Items -->
                        @if (checkPermission('contact.view'))
                            <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                {{ t('contact') }}
                            </p>
                            <a wire:navigate href="{{ route('admin.contacts.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                        {{ request()->routeIs('admin.contacts.list')
                                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                            : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-user-circle
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.contacts.list')
                                            ? 'text-indigo-700 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                {{ t('contact') }}
                            </a>
                        @endif

                        @if (checkPermission('template.view'))
                            <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                {{ t('templates') }}
                            </p>
                            <!-- Menu Items -->
                            <a wire:navigate href="{{ route('admin.template.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                        {{ request()->routeIs('admin.template.list')
                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-document
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.template.list')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}" />
                                {{ t('templates') }}
                            </a>
                        @endif

                        @if (checkPermission([
                                'campaigns.view',
                                'campaigns.show_campaign',
                                'bulk_campaigns.send',
                                'message_bot.view',
                                'template_bot.view',
                            ]))
                            <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                {{ t('marketing') }}
                            </p>
                        @endif

                        @if (checkPermission('campaigns.view'))
                            <a wire:navigate href="{{ route('admin.campaigns.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                    {{ request()->routeIs('admin.campaigns.list')
                                        ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                        : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-megaphone
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                        {{ request()->routeIs('admin.campaigns.list')
                                            ? 'text-indigo-600 dark:text-slate-300'
                                            : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('campaign') }}
                            </a>
                        @endif

                        {{-- CSV Campaigns --}}
                        @if (checkPermission('bulk_campaigns.send'))
                            <a wire:navigate href="{{ route('admin.csvcampaign') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                 {{ request()->routeIs('admin.csvcampaign')
                                     ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                     : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-clipboard-document
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                     {{ request()->routeIs('admin.csvcampaign')
                                         ? 'text-indigo-600 dark:text-slate-300'
                                         : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('bulk_campaign') }}
                            </a>
                        @endif
                        <!-- Message Bot -->
                        @if (checkPermission('message_bot.view'))
                            <a wire:navigate href="{{ route('admin.messagebot.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                                {{ request()->routeIs('admin.messagebot.list')
                                    ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-chat-bubble-bottom-center-text
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.messagebot.list')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('message_bot') }}
                            </a>
                        @endif

                        <!-- Template Bot -->
                        @if (checkPermission('template_bot.view'))
                            <a wire:navigate href="{{ route('admin.templatebot.list') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                            {{ request()->routeIs('admin.templatebot.list')
                                ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                                : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-tag
                                    class="mr-4 flex-shrink-0 h-6 w-6
                                    {{ request()->routeIs('admin.templatebot.list')
                                        ? 'text-indigo-600 dark:text-slate-300'
                                        : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('template_bot') }}
                            </a>
                        @endif

                        <!-- Chat -->
                        @if (checkPermission(['chat.view', 'chat.read_only']))
                            <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                {{ t('support') }}
                            </p>

                            <a wire:navigate href="{{ route('admin.chat') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                        {{ request()->routeIs('admin.chat')
                            ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                            : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-chat-bubble-oval-left
                                    class="mr-4 flex-shrink-0 h-6 w-6
                            {{ request()->routeIs('admin.chat')
                                ? 'text-indigo-600 dark:text-slate-300'
                                : 'text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300' }}"
                                    aria-hidden="true" />
                                {{ t('chat') }}
                            </a>
                        @endif

                        <!-- Settings -->
                        @if (checkPermission(['system_settings.view', 'whatsmark_settings.view']))
                            <p class="text-sm text-gray-500 dark:text-slate-400 font-meduim px-5 py-4">
                                {{ t('settings') }}
                            </p>
                        @endif

                        @if (checkPermission('system_settings.view'))
                            <a wire:navigate href="{{ route('admin.general.settings.view') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
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
                    ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-cog
                                    class="mr-4 flex-shrink-0 h-6 w-6
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
                                {{ t('system_setting') }}
                            </a>
                        @endif

                        <!-- WhatsMark Settings -->
                        @if (checkPermission('whatsmark_settings.view'))
                            <a wire:navigate href="{{ route('admin.whatsapp-auto-lead.settings.view') }}"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md
                {{ in_array(request()->route()->getName(), [
                    'admin.whatsapp-auto-lead.settings.view',
                    'admin.stop-bot.settings.view',
                    'admin.web-hooks.settings.view',
                    'admin.support-agent.settings.view',
                    'admin.notification-sound.settings.view',
                    'admin.ai-integration.settings.view',
                    'admin.auto-clear-chat-history.settings.view',
                ])
                    ? 'border-l-4 border-indigo-600 bg-indigo-50  dark:border-indigo-600 text-indigo-700 dark:bg-slate-900 dark:text-white'
                    : 'text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                                <x-heroicon-o-wrench-screwdriver
                                    class="mr-4 flex-shrink-0 h-6 w-6
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
                                {{ t('whatsmark_settings') }}
                            </a>
                        @endif

                        <!-- Setup -->
                        @if (checkPermission([
                                'user.view',
                                'role.view',
                                'status.view',
                                'source.view',
                                'ai_prompt.view',
                                'canned_reply.view',
                                'activity_log.view',
                                'email_template.view',
                            ]))
                            <button x-on:click.prevent="setupMenu = true"
                                class="group flex items-center px-4 py-2 text-sm font-medium rounded-r-md text-gray-600 hover:bg-indigo-100 hover:text-indigo-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white mt-2 w-full">
                                <x-heroicon-o-cog-6-tooth
                                    class="mr-4 flex-shrink-0 h-6 w-6 text-gray-500 group-hover:text-indigo-700 dark:text-slate-400 group-hover:dark:text-slate-300"
                                    aria-hidden="true" />
                                {{ t('setup') }}
                            </button>
                        @endif
                    @endif
                    {{-- sidebar for admin : end --}}
                </nav>
            </div>
        </div>
    </div>
</div>
