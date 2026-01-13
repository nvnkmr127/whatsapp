@extends('layouts.app')

@section('title', t('chat'))

@section('chat')

    <div>
        @if (empty(get_setting('whatsapp.is_webhook_connected')) ||
                empty(get_setting('whatsapp.is_whatsmark_connected')) ||
                empty(get_setting('whatsapp.wm_default_phone_number')))
            <div class="flex justify-center items-center min-h-[calc(100vh-170px)] px-4 sm:px-6 lg:px-8 py-6">
                <!-- Card container -->
                <div
                    class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-md xl:max-w-lg bg-white dark:bg-slate-700 rounded-xl shadow-lg overflow-hidden">
                    <!-- Red header section -->
                    <div class="bg-red-50 dark:bg-red-900/20 p-6 flex items-center justify-center">
                        <div class="h-16 w-16 rounded-full bg-red-100 dark:bg-red-800 flex items-center justify-center">
                            <x-heroicon-o-exclamation-circle class="h-8 w-8 text-red-500 dark:text-red-400" />
                        </div>
                    </div>

                    <!-- Content section -->
                    <div class="p-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-900 dark:text-white mb-4">
                            Your Account Is Disconnected!
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300 text-center mb-6 text-sm sm:text-base">
                            Your account is no longer connected to our system. This may be due to an expired token, a
                            disconnected webhook, invalid token, or changes in your Meta account settings.
                        </p>

                        <!-- Action buttons -->
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                            <a href="{{ route('admin.connect') }}" target="_blank" rel="noopener noreferrer"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-center flex items-center justify-center">
                                <x-heroicon-o-arrow-path class="h-5 w-5 mr-2" />
                                Connect Account
                            </a>
                            <a href="https://support.corbitaltech.dev/" target="_blank" rel="noopener noreferrer"
                                class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 mr-2" />
                                Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @elseif (empty(get_setting('pusher.app_id')) ||
                empty(get_setting('pusher.app_key')) ||
                empty(get_setting('pusher.app_secret')) ||
                empty(get_setting('pusher.cluster')))
            <div class="flex items-center justify-center h-[calc(100vh_-_90px)]">
                <div class="max-w-md mx-auto my-8 overflow-hidden bg-white dark:bg-gray-800 text-gray-900">
                    <div
                        class="relative overflow-hidden rounded-xl shadow-xl transition-all duration-500 ease-in-out bg-white dark:bg-gray-800 dark:text-gray-300">

                        <!-- Card content -->
                        <div class="relative rounded-xl overflow-hidden">
                            <!-- Header -->
                            <div class="flex items-center p-4 group t">
                                <div
                                    class="flex-shrink-0 p-2 rounded-full   bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    <x-heroicon-o-information-circle class="w-6 h-6" />
                                </div>
                                <h3 class="ml-3 text-lg font-semibold">{{ t('pusher_account_setup') }} </h3>
                            </div>

                            <!-- Content area -->
                            <div class="px-5 pb-5">
                                <div class="mb-4 transition-all duration-500 delay-100 opacity-100 transform translate-y-0">
                                    <p class="transition-colors duration-300 text-gray-700 dark:text-gray-300">
                                        {{ t('pusher_account_setup_description') }}
                                    </p>
                                </div>

                                <!-- Steps -->
                                <div
                                    class="space-y-3 transition-all duration-500 delay-300 opacity-100 transform translate-y-0">

                                    <!-- Step 1 -->
                                    <a href="{{ route('admin.pusher.settings.view') }}"
                                        class="block group relative overflow-hidden rounded-lg transition-all duration-300 bg-gray-50 hover:bg-blue-50 dark:bg-gray-700/50 dark:hover:bg-gray-700">

                                        <div
                                            class="absolute inset-0 bg-gradient-to-r from-blue-400/20 via-indigo-400/20 to-purple-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 dark:group-hover:opacity-30">
                                        </div>

                                        <div class="relative p-4 flex items-start">
                                            <div
                                                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full mr-3 transition-all duration-300 transform group-hover:scale-110 group-active:scale-95 bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">
                                                <span>1</span>
                                            </div>
                                            <div class="flex-grow">
                                                <h4
                                                    class="font-medium transition-colors duration-300 text-gray-700 dark:text-gray-200">
                                                    {{ t('access_system_settings') }} </h4>
                                                <p
                                                    class="text-sm mt-1 transition-colors duration-300 text-gray-500 dark:text-gray-400">
                                                    {{ t('navigate_to_whatsmark_system') }} </p>
                                            </div>
                                            <span
                                                class="flex-shrink-0 ml-2 transition-all duration-300 transform group-hover:translate-x-1 text-blue-500 dark:text-blue-300">
                                                <x-heroicon-o-arrow-right class="w-4 h-4" />

                                            </span>
                                        </div>
                                    </a>

                                    <!-- Step 2 -->
                                    <a href="https://docs.corbitaltech.dev/products/whatsmark/"
                                        class="block group relative overflow-hidden rounded-lg transition-all duration-300 bg-gray-50 hover:bg-blue-50 dark:bg-gray-700/50 dark:hover:bg-gray-700">

                                        <div
                                            class="absolute inset-0 bg-gradient-to-r from-blue-400/20 via-indigo-400/20 to-purple-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 dark:group-hover:opacity-30">
                                        </div>

                                        <div class="relative p-4 flex items-start">
                                            <div
                                                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full mr-3 transition-all duration-300 transform group-hover:scale-110 group-active:scale-95 bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">
                                                <span>2</span>
                                            </div>
                                            <div class="flex-grow">
                                                <h4
                                                    class="font-medium transition-colors duration-300 text-gray-700 dark:text-gray-200">
                                                    {{ t('follow_documentation') }} </h4>
                                                <p
                                                    class="text-sm mt-1 transition-colors duration-300 text-gray-500 dark:text-gray-400">
                                                    {{ t('read_the_whatsmark_documentation') }} </p>
                                            </div>
                                            <span
                                                class="flex-shrink-0 ml-2 transition-all duration-300 transform group-hover:translate-x-1 text-blue-500 dark:text-blue-300">
                                                <x-heroicon-o-arrow-right class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Notification text -->
                    <div
                        class="text-center mt-3 text-xs transition-colors duration-300 dark:bg-gray-700/50 text-gray-500 dark:text-gray-300">
                        <p>{{ t('real_time_notification_require_pusher_integration') }} </p>
                    </div>

                </div>
            </div>
        @else
            <div x-data="chatApp({{ json_encode($chats) }})" x-init="initialize()"
                class="flex gap-2 p-2 relative sm:h-[calc(100vh_-_100px)] h-full"
                :class="{ 'min-h-[999px]': isShowChatMenu }">

                <!-- Sidebar -->
                <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4 flex-none max-w-[26rem] w-full absolute xl:relative z-10 space-y-4 h-full hidden xl:block overflow-hidden"
                    :class="isShowChatMenu ? '!block ' : ''">

                    <!-- Header with User Info & Dropdown Menu -->
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="flex-none">
                                <img src="{{ checkRemoteFile(get_setting('whatsapp.wm_profile_picture_url')) ? get_setting('whatsapp.wm_profile_picture_url') : asset('img/avatar-agent.svg') }}"
                                    class="rounded-full h-12 w-12 object-cover" />
                            </div>
                            <div class="mx-3">
                                <p x-show="selectedUser" class="font-normal text-sm text-gray-800 dark:text-gray-200">
                                    <span>{{ t('from') }}</span>
                                    <span x-text="selectedUser?.wa_no ? '+' + selectedUser.wa_no : ''"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <!-- Select Dropdown -->
                        <select id="selectedWaNo" x-on:change="filterChats()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 tom-select"
                            x-model="selectedWaNo">
                            <template x-for="(wa_no, index) in uniqueWaNos()" :key="index">
                                <option :value="wa_no" :selected="selectedWaNo === wa_no" x-text="wa_no"></option>
                            </template>
                            <option value="*">{{ t('all_chats') }}</option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="relative" x-cloak>
                        <input type="text" id="searchText" placeholder="{{ t('searching') }}..." autocomplete="off"
                            class="block w-full rounded-md dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            x-model="searchText" x-on:input="searchChats()" />
                        <div
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 focus:text-indigo-500">
                            <x-heroicon-m-magnifying-glass class="w-5 h-5" />
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="h-px w-full border-b border-[#e0e6ed] dark:border-slate-600"></div>
                    <div class="!mt-0">
                        <div
                            class="chat-users relative h-full min-h-[100px] sm:h-[calc(100vh_-_310px)] space-y-0.5 pr-3.5 pl-3.5 -mr-3.5 -ml-3.5 overflow-y-auto">
                            <template x-for="chat in sortedChats" :key="chat.id">
                                <div class="w-full cursor-pointer flex justify-between items-center px-2 py-3 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#050b14] rounded-md dark:hover:text-indigo-500 hover:text-indigo-500"
                                    :class="{
                                        'bg-gray-100 dark:bg-[#050b14] dark:text-indigo-500 text-indigo-500': selectedUser &&
                                            selectedUser.id === chat.id
                                    }"
                                    x-on:click="selectChat(chat)">
                                    <div class="flex-1">
                                        <div class="flex items-center ">
                                            <div class="flex-shrink-0 relative">
                                                <img x-bind:src="'https://ui-avatars.com/api/?name=' + chat.name"
                                                    class="rounded-full h-10 w-10 object-cover text-xs" />
                                            </div>
                                            <div class="mx-3 flex flex-col gap-1 justify-start items-start w-full relative">
                                                <!-- Name and Type in One Line -->
                                                <div class="flex items-center justify-between w-full">
                                                    <div class="flex items-center justify-start">
                                                        <p class="font-normal text-xs truncate max-w-[100px]"
                                                            x-text="chat.name" x-bind:data-tippy-content="chat.receiver_id">
                                                        </p>
                                                        <span
                                                            :class="{
                                                                'bg-violet-100 text-purple-800': chat
                                                                    .type === 'lead',
                                                                'bg-red-100 text-red-800': chat.type === 'customer',
                                                                'bg-yellow-100 text-yellow-800': chat
                                                                    .type === 'guest',
                                                                'bg-gray-100 text-gray-800': !['lead', 'customer',
                                                                        'guest'
                                                                    ]
                                                                    .includes(selectedUser?.type)
                                                            }"
                                                            class="inline-block ml-2 text-xs font-meduim px-2 rounded">
                                                            <span x-text="chat.type"></span>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="font-normal whitespace-nowrap text-xs">
                                                            <p x-text="formatLastMessageTime(chat.time_sent)"></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <p class="text-xs text-gray-500 truncate max-w-[185px]"
                                                    x-text="sanitizeLastMessage(chat.last_message)">
                                                </p>
                                                <span x-show="countUnreadMessages(chat.id) > 0 && !chat.hideUnreadCount"
                                                    class="absolute sm:left-[267px] left-[240px] top-5 flex items-center justify-center w-5 h-5 text-xs font-normal text-white bg-indigo-600 rounded-full cursor-pointer"
                                                    x-text="countUnreadMessages(chat.id)">
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Sidebar End-->
                <!-- Overlay Sidebar-->
                <div class="bg-black/60 z-[5] w-full h-full absolute rounded-xl hidden"
                    x-bind:class="{ '!block xl:!hidden': isShowChatMenu }" x-on:click="isShowChatMenu = !isShowChatMenu">
                </div>

                <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-0 flex-1 relative">
                    <!-- When no user is selected -->
                    <div x-show="!isShowUserChat" class="h-full" x-cloak>
                        <div class="flex items-center justify-center h-full relative p-4">
                            <button type="button"
                                class="xl:hidden absolute top-4 left-4 right-4 hover:text-indigo-500 text-gray-500 dark:text-slate-400"
                                x-on:click="isShowChatMenu = !isShowChatMenu">
                                <!-- Menu Icon -->
                                <x-heroicon-s-bars-3 class="w-6 h-6" />
                            </button>
                            <div class="py-8 flex items-center justify-center flex-col" x-cloak>
                                <div
                                    class="w-[280px] md:w-[430px] mb-8 h-[calc(100vh_-_320px)] min-h-[120px] text-black dark:text-slate-400">
                                    <!-- Light mode image -->
                                    <img src="{{ asset('/img/chat/chat-white.svg') }}" alt="light mode image"
                                        class="w-full h-full dark:hidden" />

                                    <!-- Dark mode image -->
                                    <img src="{{ asset('/img/chat/chat-black.svg') }}" alt="dark mode image"
                                        class="w-full h-full hidden dark:block" />
                                </div>

                                <!-- Instruction text -->
                                <div
                                    class="flex justify-center item-center gap-4 p-2 font-semibold rounded-md max-w-[190px] mx-auto dark:text-gray-400">
                                    <span>{{ t('click_user_to_chat') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat detail: Only visible when a user is selected -->
                    <div x-show="isShowUserChat && selectedUser" class="relative h-full" x-cloak>
                        <!-- Header Section -->
                        <x-dynamic-alert x-show="sendingErrorMessage" type="danger">
                            <b>{{ t('error') }}</b>
                            <span x-text="sendingErrorMessage"></span>
                        </x-dynamic-alert>
                        <div class="flex justify-between items-center p-4">
                            <div class="flex items-center space-x-2 dark:space-x-reverse">
                                <!-- Mobile Menu Toggle Button -->
                                <button type="button"
                                    class="xl:hidden hover:text-indigo-500 text-gray-500 dark:text-slate-400"
                                    x-on:click="isShowChatMenu = !isShowChatMenu">
                                    <!-- Menu Icon -->
                                    <x-heroicon-s-bars-3 class="w-6 h-6" />
                                </button>

                                <!-- User Avatar and Active Indicator -->
                                <div class="relative flex-none">
                                    <img x-bind:src="'https://ui-avatars.com/api/?name=' + (selectedUser?.name ?? 'User')"
                                        class="rounded-full h-11 w-11 object-cover text-xs" />
                                </div>

                                <!-- User Name and Status -->
                                <div class="mx-3">
                                    <div class="flex justify-start items-center">
                                        <!-- Display Selected User Name -->
                                        <a target="_blank"
                                            class="font-medium text-sm truncate max-w-[88px] sm:max-w-[185px] text-gray-700 dark:text-gray-200"
                                            x-bind:href="(selectedUser?.type === 'lead' || selectedUser?.type === 'customer') ?
                                            `{{ route('admin.contacts.save', ['contactId' => 'CONTACT_ID']) }}`.replace
                                                ('CONTACT_ID', userInfo?.id || ''): '#'"
                                            x-bind:data-tippy-content="(selectedUser?.type === 'lead' || selectedUser?.type === 'customer') ?
                                            '{{ t('click_to_open_leads') }}' :
                                            ''"
                                            x-bind:class="(selectedUser?.type === 'lead' || selectedUser?.type === 'customer') ?
                                            'cursor-pointer' :
                                            'pointer-events-none text-gray-400'"
                                            x-text="selectedUser?.name ?? 'Unknown'">
                                        </a>


                                        <!-- Badge for chat type -->
                                        <span
                                            :class="{
                                                'bg-violet-100 text-purple-800': selectedUser?.type === 'lead',
                                                'bg-red-100 text-red-800': selectedUser?.type === 'customer',
                                                'bg-yellow-100 text-yellow-800': selectedUser?.type === 'guest',
                                                'bg-gray-100 text-gray-800': !['lead', 'customer', 'guest'].includes(
                                                    selectedUser?.type)
                                            }"
                                            class="inline-block ml-2 text-xs font-normal px-2 rounded"
                                            x-text="selectedUser?.type">
                                        </span>
                                    </div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400"
                                        x-text="selectedUser?.receiver_id ?? ''"></p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex sm:gap-3 gap-1 relative">
                                <button x-on:click="messagesSearch = !messagesSearch"
                                    class=" text-indigo-500 dark:text-gray-200 mr-3 hidden sm:block">
                                    <x-heroicon-m-magnifying-glass class="w-5 h-5" />
                                </button>
                                <button type="button"
                                    class="relative hover:text-indigo-500 text-gray-500 dark:text-slate-400 "
                                    x-on:click.stop="showAlert = !showAlert" x-on:click.away="showAlert = false">
                                    <!-- Status Indicator -->
                                    <span class="flex items-center justify-center">
                                        <span class="absolute h-3 w-3 rounded-full opacity-75"
                                            :class="{
                                                'bg-red-500 animate-ping': overdueAlert,
                                                'bg-green-500 animate-ping': !overdueAlert
                                            }"></span>
                                        <span class="relative h-3 w-3 rounded-full"
                                            :class="{
                                                'bg-red-500': overdueAlert,
                                                'bg-green-500': !overdueAlert
                                            }"></span>
                                    </span>
                                </button>
                                <!-- Message when overdue alert is true -->
                                <div x-show="showAlert" x-transition
                                    class="absolute mt-2 right-[0.3rem] sm:right-[25.25rem] top-[3.25rem] sm:top-[3.3rem] w-80 sm:w-max p-2 rounded shadow z-10 flex items-center gap-2"
                                    :class="{
                                        'bg-amber-100 dark:bg-gray-700 dark:text-yellow-400 text-amber-700': overdueAlert,
                                        'bg-green-100 dark:bg-green-900 dark:text-green-400 text-green-700': !
                                            overdueAlert
                                    }">

                                    <!-- Heroicon Exclamation centered -->
                                    <!-- Icon -->
                                    <x-heroicon-o-exclamation-triangle x-show="overdueAlert"
                                        class="w-6 h-6 text-amber-700 dark:text-yellow-400 flex-shrink-0" />
                                    <x-heroicon-o-clock x-show="!overdueAlert"
                                        class="w-6 h-6 text-green-700 dark:text-green-400 flex-shrink-0" />


                                    <!-- Message Text -->
                                    <div>
                                        <template x-if="overdueAlert" x-cloak>
                                            <span class="font-semibold text-amber-700 dark:text-yellow-400 text-sm">
                                                {{ t('24_hours_limit') }} <span class="text-sm font-normal">
                                                    {{ t('whatsapp_block_message_24_hours_after') }} </span>
                                            </span>
                                            <br>
                                            <span class="block text-sm">
                                                {{ t('the_last_template_message_still_be_sent') }}
                                            </span>
                                        </template>
                                        <!-- Not Overdue Message -->
                                        <template x-if="!overdueAlert" x-cloak>
                                            <span class="font-normal text-green-700 dark:text-green-400 text-sm">
                                                {{ t('reply_within') }} <span x-text="remainingHours"></span>
                                                {{ t('hours_and') }}
                                                <span x-text="remainingMinutes"></span> {{ t('minutes_remaining') }}
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="isAdmin == 1" x-html="asignAgentView">
                                </div>
                                <button type="button"
                                    class="hover:text-indigo-500 text-gray-500 dark:text-slate-400 mt-1 hidden sm:block"
                                    x-on:click="isShowUserInfo = true">
                                    <x-heroicon-o-information-circle class="mx-auto mb-1 w-6 h-6"
                                        data-tippy-content="{{ t('user_information') }}" />
                                </button>

                                <!-- Dropdown Menu (Popper) -->
                                <div class="dropdown">
                                    <div x-data="{ openDropdown: false }" class="relative">
                                        <button x-on:click="openDropdown = !openDropdown"
                                            class="bg-[#f4f4f4] dark:bg-[#050b14] hover:text-indigo-500 w-8 h-8 text-gray-500 dark:text-slate-400 rounded-full flex justify-center items-center">
                                            <x-heroicon-m-ellipsis-vertical class="w-5 h-5"
                                                data-tippy-content="{{ t('more') }}" aria-hidden="true" />
                                        </button>
                                        <ul x-show="openDropdown" x-on:click.away="openDropdown = false"
                                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-2 z-20">
                                            <li class="sm:hidden block">
                                                <button type="button"
                                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700"
                                                    x-on:click="isShowUserInfo = true">
                                                    <x-heroicon-o-information-circle class="w-5 h-5" />
                                                    <span>{{ t('user_information') }}</span>
                                                </button>
                                            </li>
                                            <li class="sm:hidden block">
                                                <button x-on:click="messagesSearch = true; openDropdown = false"
                                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">
                                                    <x-heroicon-m-magnifying-glass class="w-5 h-5" />
                                                    <span>{{ t('search') }}</span>
                                                </button>
                                            </li>
                                            @if (get_setting('whats-mark.only_agents_can_chat'))
                                                <li x-show="isAdmin == 1">
                                                    <button x-on:click='isSupportAgentModal = true'
                                                        class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">
                                                        <x-heroicon-o-user-plus class="w-6 h-6" />
                                                        <span>{{ t('support_agent') }}</span>
                                                    </button>
                                                </li>
                                            @endif
                                            <li>
                                                <button x-on:click='isDeleteChatModal = true'
                                                    data-tippy-content="{{ t('remove_chat') }}"
                                                    class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">
                                                    <x-heroicon-o-trash class="w-5 h-5" />
                                                    <span>{{ t('delete') }}</span>
                                                </button>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="h-px w-full border-b border-[#e0e6ed] dark:border-slate-600"></div>

                        <!-- Chat Conversation Section -->
                        <div x-show="loading"
                            class="absolute z-[90] w-full h-full inset-0 items-center justify-center bg-white dark:bg-neutral-800 bg-opacity-50">
                            <svg class="w-8 h-8 absolute top-[50%] right-[36.4rem] animate-spin text-indigo-600"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                        <div style="will-change: transform;"
                            class="relative overflow-auto rounded-b-lg h-[calc(100vh_-_150px)] chat-conversation-box bg-stone-100"
                            :class="readOnlyPermission ? 'sm:h-[calc(100vh_-_250px)]' : 'sm:h-[calc(100vh_-_177px)]'"
                            @scroll="selectedUser?.id && checkScrollTop(selectedUser.id)" x-ref="chatContainer">
                            <div class="space-y-5 p-4 sm:min-h-[300px] min-h-[400px] sm:mb-8 mb-16"
                                :class="readOnlyPermission ? 'pb-[68px]' : 'pb-0'">

                                <!-- Render messages if available -->
                                <div x-show="selectedUser && selectedUser.messages?.length">
                                    <template x-for="(message, index) in selectedUser?.messages ?? []"
                                        :key="index">
                                        <div>
                                            <!-- Display Date Divider Between Messages -->
                                            <template
                                                x-if="selectedUser && selectedUser.messages && shouldShowDate(message, selectedUser.messages[index - 1])">

                                                <div class="flex justify-center my-2">
                                                    <span
                                                        class="bg-white py-1 px-2 text-xs rounded-md dark:bg-gray-600 dark:text-gray-200"
                                                        x-text="formatDate(message.time_sent)">
                                                    </span>
                                                </div>
                                            </template>

                                            <!-- Message Wrapper -->
                                            <div class="flex items-start gap-3">
                                                <div class="flex w-full relative"
                                                    :class="message.sender_id === selectedUser.wa_no ? 'justify-end' :
                                                        'justify-start'">
                                                    <!-- Ellipsis Icon to Open Menu -->
                                                    <button x-on:click="toggleMessageOptions(message.id)">
                                                        <x-heroicon-m-ellipsis-vertical
                                                            class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" />
                                                    </button>

                                                    <!-- Message Content -->
                                                    <div class="p-2 rounded-lg max-w-xs break-words my-2 message-item"
                                                        :data-message-id='message.message_id'
                                                        :class="{
                                                            'bg-[#c7c8ff] dark:bg-[#574e80]': message.sender_id ===
                                                                selectedUser.wa_no,
                                                            'bg-white dark:bg-[#273443]': message.sender_id !==
                                                                selectedUser
                                                                .wa_no,
                                                            'bg-[#cbced4] dark:bg-[#727272fa]': message.staff_id == 0 &&
                                                                message.sender_id === selectedUser.wa_no
                                                        }">
                                                        <div x-show="message.ref_message_id"
                                                            x-on:click="scrollToMessage(message.ref_message_id)"
                                                            class="bg-neutral-100 dark:bg-gray-500 rounded-lg mb-2 cursor-pointer">
                                                            <div
                                                                class="flex flex-col gap-2 p-2 border-indigo-500 border-l-4 rounded">

                                                                <span class="text-gray-700 dark:text-gray-200 text-xs"
                                                                    x-html="getOriginalMessage(message.ref_message_id)?.message"></span>
                                                                <template
                                                                    x-if="getOriginalMessage(message.ref_message_id)?.url">
                                                                    <div>
                                                                        <template
                                                                            x-if="getOriginalMessage(message.ref_message_id)?.type === 'image'">
                                                                            <a :href="getOriginalMessage(message.ref_message_id)
                                                                                ?.url"
                                                                                data-lightbox="image-group"
                                                                                target="_blank">
                                                                                <img :src="getOriginalMessage(message
                                                                                        .ref_message_id)
                                                                                    ?.url"
                                                                                    class="rounded-lg max-w-xs max-h-28"
                                                                                    alt="Image">
                                                                            </a>
                                                                        </template>

                                                                        <template
                                                                            x-if="getOriginalMessage(message.ref_message_id)?.type === 'video'">
                                                                            <video
                                                                                :src="getOriginalMessage(message
                                                                                        .ref_message_id)
                                                                                    ?.url"
                                                                                controls
                                                                                class="rounded-lg max-w-xs max-h-28"></video>
                                                                        </template>

                                                                        <template
                                                                            x-if="getOriginalMessage(message.ref_message_id)?.type === 'document'">
                                                                            <a :href="getOriginalMessage(message.ref_message_id)
                                                                                ?.url"
                                                                                target="_blank"
                                                                                class="text-blue-500 underline">
                                                                                {{ t('download_document') }}
                                                                            </a>
                                                                        </template>

                                                                        <template
                                                                            x-if="getOriginalMessage(message.ref_message_id)?.type === 'audio'">
                                                                            <audio controls class="w-[250px]">
                                                                                <source
                                                                                    :src="getOriginalMessage(message
                                                                                        .ref_message_id)?.url"
                                                                                    type="audio/mpeg">
                                                                            </audio>
                                                                        </template>
                                                                        <template
                                                                            x-if="getOriginalMessage(message.ref_message_id)?.type === 'interactive'">
                                                                            <span
                                                                                class="text-gray-700 dark:text-gray-200 text-xs"
                                                                                x-html="getOriginalMessage(message.ref_message_id)?.message"></span>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <!-- Message Text -->
                                                        <template x-if="message.type === 'text'">
                                                            <div>
                                                                <p class="text-gray-800 dark:text-white text-sm"
                                                                    x-html="highlightSearch(message.message)"></p>
                                                            </div>
                                                        </template>

                                                        <template x-if="message.type === 'button'">
                                                            <p class="text-gray-800 dark:text-white text-sm"
                                                                x-html="highlightSearch(message.message)"></p>
                                                        </template>

                                                        <template x-if="message.type === 'reaction'">
                                                            <p class="text-gray-800 dark:text-white text-sm"
                                                                x-html="highlightSearch(message.message)"></p>
                                                        </template>

                                                        <template x-if="message.type === 'interactive'">
                                                            <p class="text-gray-800 dark:text-white text-sm"
                                                                x-html="highlightSearch(message.message)"></p>
                                                        </template>

                                                        <!-- Image -->
                                                        <template x-if="message.type === 'image'">
                                                            <a :href="message.url" target="_blank" class="glightbox"
                                                                x-init="setTimeout(() => window.initGLightbox(), 100)">
                                                                <img :src="message.url" alt="Image"
                                                                    class="rounded-lg max-w-xs max-h-28">
                                                            </a>
                                                            <p class="text-gray-600 text-xs mt-2 dark:text-gray-200"
                                                                x-show="message.caption" x-text="message.caption"></p>
                                                        </template>

                                                        <!-- Video -->
                                                        <template x-if="message.type === 'video'">
                                                            <a :href="message.url" class="glightbox"
                                                                x-init="setTimeout(() => window.initGLightbox(), 100)">
                                                                <video :src="message.url" controls
                                                                    class="rounded-lg max-w-xs max-h-28"></video>
                                                            </a>
                                                            <p class="text-gray-600 text-xs mt-2 dark:text-gray-200"
                                                                x-show="message.message" x-text="message.message"></p>
                                                        </template>

                                                        <!-- Document -->
                                                        <template x-if="message.type === 'document'">
                                                            <a :href="message.url" target="_blank"
                                                                class="bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full dark:bg-gray-800 dark:text-green-400">
                                                                {{ t('download_document') }}
                                                            </a>
                                                        </template>

                                                        <!-- Audio -->
                                                        <template x-if="message.type === 'audio'">
                                                            <audio id="audioPlayer" controls class="w-[300px]">
                                                                <source :src="message.url" type="audio/mpeg">
                                                            </audio>
                                                            <p class="text-gray-600 text-xs mt-2 dark:text-gray-200"
                                                                x-show="message.message" x-text="message.message"></p>
                                                        </template>

                                                        <!-- Message Timestamp & Status -->
                                                        <div
                                                            class="flex justify-end items-end mt-2 text-xs text-gray-600 dark:text-gray-200">
                                                            <span x-text="formatTime(message.time_sent)"></span>
                                                            <div class="flex justify-end item-center">
                                                                <span x-show="message.sender_id === selectedUser.wa_no"
                                                                    class="ml-1">
                                                                    <template x-if="message.status === 'sent'">
                                                                        <x-heroicon-o-check
                                                                            class="w-4 h-4 text-gray-500 dark:text-white"
                                                                            title="Sent" />
                                                                    </template>

                                                                    <template x-if="message.status === 'delivered'">
                                                                        <img src="{{ asset('/img/chat/delivered.png') }}"
                                                                            alt="Delivered-message"
                                                                            class="w-4 h-4 text-gray-500 dark:text-white" />
                                                                    </template>

                                                                    <template x-if="message.status === 'read'">
                                                                        <img src="{{ asset('/img/chat/double-check-read.png') }}"
                                                                            alt="read-message"
                                                                            class="w-4 h-4 text-cyan-500" />
                                                                    </template>

                                                                    <template x-if="message.status === 'failed'">
                                                                        <x-heroicon-o-exclamation-circle
                                                                            class="w-4 h-4 text-red-500" title="Failed" />
                                                                    </template>

                                                                    <template x-if="message.status === 'deleted'">
                                                                        <x-heroicon-o-trash class="w-4 h-4 text-red-500"
                                                                            title="Deleted" />
                                                                    </template>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- Options Menu -->
                                                        <div x-show="activeMessageId === message.id" x-transition
                                                            x-on:click.away="activeMessageId = null"
                                                            class="absolute top-[-4.5rem] z-10 w-40 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-lg rounded-lg py-2"
                                                            :class="message.sender_id === selectedUser.wa_no ? 'right-0' :
                                                                'left-0'">
                                                            <ul class="text-sm">
                                                                <div class="flex justify-start items-center gap-2 px-2 py-2 hover:bg-gray-200 hover:text-indigo-500 dark:hover:bg-gray-700 cursor-pointer"
                                                                    x-on:click="replyToMessage(message)">
                                                                    <x-heroicon-c-arrow-path-rounded-square
                                                                        class="w-5 h-5 dark:text-gray-300 text-indigo-500" />
                                                                    <li class="dark:text-gray-300 text-indigo-500">
                                                                        {{ t('reply') }}
                                                                    </li>
                                                                </div>
                                                                <div x-on:click.stop="deleteMessage(message.id)"
                                                                    class="flex justify-start items-center gap-2 px-2 py-2 hover:bg-gray-200 hover:text-indigo-500 dark:hover:bg-gray-700 cursor-pointer">
                                                                    <x-heroicon-o-trash
                                                                        class="w-5 h-5 dark:text-gray-300 text-red-500" />
                                                                    <li class="dark:text-gray-300 text-indigo-500">
                                                                        {{ t('delete') }}
                                                                    </li>
                                                                </div>
                                                            </ul>
                                                        </div>
                                                    </div> <!-- End Message Content -->
                                                </div> <!-- End Message Wrapper -->
                                            </div>
                                            <span x-show="message.status_message && message.status_message.length > 0"
                                                class="text-red-500 text-xs truncate text-right block text-wrap"
                                                x-text="message.status_message">
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <button
                            class="absolute p-2 rounded-full shadow-lg bottom-[9rem] sm:bottom-[6rem] right-4
                            transition-all duration-300 ease-in-out
                            bg-gray-200 hover:bg-gray-300 text-gray-700
                            dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200
                            transform hover:scale-110"
                            x-on:click="scrollToBottom">
                            <x-heroicon-o-arrow-small-down class="w-5 h-5" />
                        </button>
                        <!-- Search Modal -->
                        <div x-show="messagesSearch" x-cloak
                            class="absolute top-[5.5rem] left-1/2 transform -translate-x-1/2 z-50"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            x-init="$watch('messagesSearch', value => { if (value) $nextTick(() => $refs.searchInput.focus()); })">

                            <!-- Search Input -->
                            <div class="sm:w-[480px] w-full px-4">
                                <div class="relative">
                                    <input type="text" x-model="searchMessagesText" x-on:input="searchMessages()"
                                        x-ref="searchInput"
                                        class="w-full border shadow rounded-full text-gray-800 border-gray-300 bg-white dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 outline-none p-2 pr-12"
                                        placeholder="Search Messages...">
                                    <div x-show="matchedMessages.length > 0"
                                        class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                                        <span id="search-counter"></span>
                                    </div>
                                    <button
                                        class="absolute top-[0.2rem] right-[2.5rem] text-indigo-400 dark:text-indigo-300"
                                        x-on:click="prevMatch" x-show="matchedMessages.length > 0">
                                        <x-heroicon-m-chevron-up class="w-6 h-6" />
                                    </button>

                                    <button
                                        class="absolute top-[1.0rem] right-[2.5rem] text-indigo-400 dark:text-indigo-300"
                                        x-on:click="nextMatch" x-show="matchedMessages.length > 0">
                                        <x-heroicon-m-chevron-down class="w-6 h-6" />
                                    </button>

                                    <button class="absolute top-[0.6rem] right-3 text-indigo-400 dark:text-indigo-300">
                                        <x-heroicon-m-magnifying-glass class="w-6 h-6" />
                                    </button>
                                    <button class="absolute top-[0.6rem] right-[-1.70rem] text-gray-500 dark:text-gray-300"
                                        x-on:click="messagesSearch = false; searchMessagesText = ''">
                                        <x-heroicon-o-x-mark class="w-6 h-6" />
                                    </button>
                                </div>
                                <!-- Error Message -->
                                <p x-show="searchError" class="text-red-500 text-xs mt-2" x-text="searchError"></p>
                            </div>
                        </div>

                        <!-- Message Input Section -->
                        <div class="px-4 py-2 absolute bottom-0 left-0 w-full rounded-b-lg"
                            :class="readOnlyPermission ? 'bg-white dark:bg-gray-900' : 'bg-transparent dark:bg-transparent'">
                            <!-- Reply Preview -->
                            <template x-if="replyTo">
                                <div :class="{ 'min-h-[5rem]': !replyTo.text }"
                                    class="p-3 mb-2 rounded-md flex border-indigo-500 border-l-4 justify-between items-center z-60 bg-gray-100 dark:bg-gray-800">
                                    <div class="flex items-start space-x-3 overflow-hidden">
                                        <!-- Image Preview -->
                                        <template x-if="replyTo.type === 'image'">
                                            <img :src="replyTo.url"
                                                class="w-[150px] h-[60px] object-cover rounded-md flex-shrink-0"
                                                alt="Image">
                                        </template>
                                        <!-- Video Preview -->
                                        <template x-if="replyTo.type === 'video'">
                                            <video :src="replyTo.url" controls
                                                class="w-[150px] h-[60px] object-cover rounded-md flex-shrink-0"></video>
                                        </template>
                                        <!-- Document Preview -->
                                        <template x-if="replyTo.type === 'document'">
                                            <a :href="replyTo.url" target="_blank"
                                                class="min-w-[60px] h-[40px] flex items-center justify-center bg-gray-200 dark:bg-gray-700 text-green-500 rounded-md px-2 text-xs font-medium truncate">
                                                {{ t('download_document') }}
                                            </a>
                                        </template>
                                        <!-- Audio Preview -->
                                        <template x-if="replyTo.type === 'audio'">
                                            <audio controls class="w-[200px] h-[30px]">
                                                <source :src="replyTo.url" type="audio/mpeg">
                                            </audio>
                                        </template>
                                        <!-- Text Reply -->
                                        <div class="text-gray-700 dark:text-gray-300 text-sm max-w-full">
                                            <span class="font-normal block truncate" x-text="replyTo.text"></span>
                                        </div>
                                    </div>
                                    <!-- Close Button -->
                                    <button x-on:click="cancelReply"
                                        class="p-1 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition duration-200">
                                        <x-heroicon-o-x-mark class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                                    </button>
                                </div>
                            </template>

                            <div x-show="readOnlyPermission"
                                class="sm:flex w-full items-center space-y-2 space-x-1 sm:space-y-0 sm:space-x-3">
                                <div class="flex items-center space-x-2">
                                    <!-- AI Icon -->
                                    <!-- Pass Laravel value into Alpine.js -->
                                    <div x-data="{ showAiButton: {{ get_setting('whats-mark.enable_openai_in_chat') ? 'true' : 'false' }} }">
                                        <button x-show="showAiButton" type="button"
                                            x-on:click="openAiMenu = !openAiMenu"
                                            :disabled="textMessage.trim() === ''"
                                            class="bg-indigo-50 dark:bg-[#050b14] rounded-full p-2 text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 dark:text-gray-400 disabled:cursor-not-allowed disabled:text-gray-300">
                                            <x-heroicon-o-cpu-chip class="w-5 h-5" />
                                        </button>
                                    </div>

                                    <!-- Mood Icon -->
                                    <button type="button" id="emoji_btn"
                                        x-on:click="showEmojiPicker = !showEmojiPicker; initializeEmojiPicker()"
                                        class="bg-indigo-50 dark:bg-[#050b14] rounded-full p-2 text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 dark:text-gray-400"
                                        data-tippy-content="{{ t('emojis') }}">
                                        <x-heroicon-o-face-smile class="w-6 h-6" aria-hidden="true" />
                                    </button>

                                    <!-- Attach Icon -->
                                    <button type="button" x-on:click="showAttach = !showAttach"
                                        class="bg-indigo-50 dark:bg-[#050b14] rounded-full p-2 text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 dark:text-gray-400">
                                        <x-heroicon-o-paper-clip class="w-5 h-5"
                                            data-tippy-content="{{ t('attach_img_doc_vid') }}" aria-hidden="true" />
                                    </button>
                                </div>
                                <!-- Input Field in the Middle -->
                                <div class="relative flex-1">
                                    <input type="text" :disabled="isRecording" autocomplete="off"
                                        class="form-input mentionable rounded-full border-0 bg-indigo-50 dark:bg-[#050b14] w-full px-4 py-2 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent"
                                        placeholder="Type a message" id="textMessageInput" x-model="textMessage"
                                        @keydown.enter.prevent="handleEnterKey($event)" />

                                    <!-- Microphone Icon (Only Show When Input is Empty) -->
                                    <button type="button" x-show="!textMessage"
                                        :class="(isRecording || textMessage || attachment) ? 'right-[2.75rem]' : 'right-3'"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 bg-indigo-50 dark:bg-[#050b14] text-indigo-500 rounded-full p-2 hover:text-indigo-500 dark:hover:text-indigo-400 dark:text-gray-400"
                                        x-on:click="toggleRecording()">
                                        <template x-if="isRecording">
                                            <x-heroicon-o-stop class="w-5 h-5 text-red-500" />
                                        </template>
                                        <template x-if="!isRecording">
                                            <x-heroicon-o-microphone class="w-5 h-5"
                                                data-tippy-content="{{ t('record_audio') }}" />
                                        </template>
                                    </button>

                                    <!-- Send Button (Only Show When Text is Entered) -->
                                    <button type="button" x-show="textMessage || attachment || isRecording"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-indigo-500 bg-indigo-50 hover:text-indigo-500 dark:hover:text-indigo-400 dark:text-gray-400"
                                        x-on:click="sendMessage()" x-bind:disabled="sending"
                                        x-bind:class="{ 'opacity-50 cursor-not-allowed': sending }">
                                        <x-heroicon-o-paper-airplane class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>
                            <!-- Additional Controls for Larger Screens -->
                            <div class="relative">
                                <!-- Dropdown Menu (Opens When Button is Clicked) -->
                                <div x-show="openAiMenu" x-on:click.away="openAiMenu = false" x-transition
                                    class="absolute bottom-14 left-0 w-[15rem] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
                shadow rounded-lg ">
                                    <!-- AI Menu Items -->
                                    <ul class="py-2 space-y-1">
                                        <!-- Change Tone -->
                                        <li x-data="{ changeToneSubmenu: false }" x-on:click="changeToneSubmenu = true"
                                            x-on:click.away="changeToneSubmenu = false"
                                            class="flex items-center justify-between px-4 py-2 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                            <div class="flex justify-start items-center">
                                                <x-heroicon-o-adjustments-horizontal class="w-5 h-5 mr-3 text-blue-500" />
                                                <span
                                                    class="text-gray-700 dark:text-gray-300 text-sm">{{ t('change_tone') }}
                                                </span>
                                            </div>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                                            <div x-show="changeToneSubmenu" x-cloak
                                                class="absolute left-1/2 sm:left-full top-0 w-40 bg-white dark:bg-gray-800 border border-gray-200 
                               dark:border-gray-700 shadow rounded-lg overflow-hidden z-50">
                                                <div x-show="loading"
                                                    class="absolute z-[90] w-full h-full inset-0 items-center justify-center bg-white dark:bg-neutral-800 bg-opacity-70 ">
                                                    <svg class="w-8 h-8 absolute top-[40%] right-[4rem] animate-spin text-indigo-600"
                                                        fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <ul class="py-2">
                                                    @foreach (\App\Enums\WhatsAppTemplateRelationType::getAiChangeTone() as $key => $value)
                                                        <li x-on:click="sendAiRequest('Change Tone', '{{ ucfirst($value) }}')"
                                                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer text-sm">
                                                            {{ ucfirst($value) }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </li>

                                        <!-- Translate -->
                                        <li x-data="{ showSubmenu: false, search: '', options: @js($languages) }" x-on:click="showSubmenu = true"
                                            x-on:click.away="showSubmenu = false"
                                            class="relative flex items-center justify-between px-4 py-2 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                            <div class="flex justify-start items-center">
                                                <x-heroicon-o-language class="w-5 h-5 mr-3 text-green-500" />
                                                <span
                                                    class="text-gray-700 dark:text-gray-300 text-sm">{{ t('translate') }}</span>
                                            </div>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                                            <!-- Submenu for Countries with Fixed Height and Scrollbar -->
                                            <div x-show="showSubmenu" x-cloak
                                                class="absolute left-1/2 sm:left-full top-[-48px] w-48 bg-white dark:bg-gray-800 border border-gray-200 
                           dark:border-gray-700 shadow-lg rounded-lg overflow-hidden max-h-[14rem] z-50">
                                                <!-- Search Bar -->
                                                <div class="p-2">
                                                    <input type="text" placeholder="Search language..."
                                                        x-model="search"
                                                        class="w-full px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 
                                border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-1 
                                focus:ring-indigo-500">
                                                </div>
                                                <div x-show="loading"
                                                    class="absolute z-[90] w-full h-full inset-0 items-center justify-center bg-white dark:bg-neutral-800 bg-opacity-70">
                                                    <svg class="w-8 h-8 absolute top-[45%] right-[5rem] animate-spin text-indigo-600"
                                                        fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <ul class="py-2 max-h-44 overflow-y-auto">
                                                    <template
                                                        x-for="language in options.filter(c => c.toLowerCase().includes(search.toLowerCase()))"
                                                        :key="language">
                                                        <li x-on:click="sendAiRequest('Translate', language)"
                                                            x-text="language.charAt(0).toUpperCase() + language.slice(1)"
                                                            class="p-2 border-b cursor-pointer hover:bg-gray-100">
                                                        </li>
                                                    </template>

                                                    <!-- No Results Message -->
                                                    <li x-show="options.filter(c => c.toLowerCase().includes(search.toLowerCase())).length === 0"
                                                        class="p-2 text-gray-500 text-center">
                                                        No language found.
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                        <div x-show="loading"
                                            class="absolute z-[90] w-full h-full inset-0 items-center justify-center bg-white dark:bg-neutral-800 bg-opacity-70">
                                            <svg class="w-8 h-8 absolute top-[40%] right-[6.4rem] animate-spin text-indigo-600"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </div>
                                        <!-- Fix Spelling & Grammar -->
                                        <li x-on:click="sendAiRequest('Fix Spelling & Grammar', 'Fix Spelling & Grammar')"
                                            class="flex items-center px-4 py-2 rounded-md cursor-pointer 
                               hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                            <x-heroicon-o-pencil class="w-5 h-5 mr-3 text-purple-500" />
                                            <span
                                                class="text-gray-700 dark:text-gray-300 text-sm">{{ t('fix_spelling_and_grammar') }}</span>
                                        </li>

                                        <!-- Simplify Language -->
                                        <li x-on:click="sendAiRequest('Simplify Language', 'Simplify Language')"
                                            class="flex items-center px-4 py-2 rounded-md cursor-pointer 
                               hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                            <x-heroicon-o-sparkles class="w-5 h-5 mr-3 text-yellow-500" />
                                            <span
                                                class="text-gray-700 dark:text-gray-300 text-sm">{{ t('simplify_language') }}</span>
                                        </li>
                                        <!-- Custom Prompt -->
                                        <li x-data="{ showSubmenu: false }"x-on:click="showSubmenu = true"
                                            x-on:click.away="showSubmenu = false"
                                            class="relative flex items-center justify-between px-4 py-2 rounded-md cursor-pointer 
                         hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                            <div class="flex justify-start items-center">
                                                <x-heroicon-o-light-bulb class="w-5 h-5 mr-3 text-red-500" />
                                                <span
                                                    class="text-gray-700 dark:text-gray-300 text-sm">{{ t('custom_prompt') }}
                                                </span>
                                            </div>
                                            <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                                            <!-- Submenu for AI Prompts -->

                                            <div x-show="showSubmenu" x-cloak
                                                class="absolute left-1/2 sm:left-full bottom-[-22%] w-48 bg-white dark:bg-gray-800 border border-gray-200 
                                dark:border-gray-700 shadow rounded-lg overflow-hidden h-[10rem] overflow-y-auto">
                                                <div x-show="loading"
                                                    class="absolute z-[90] w-full h-full inset-0 items-center justify-center bg-white dark:bg-neutral-800 bg-opacity-70">
                                                    <svg class="w-8 h-8 absolute top-[40%] right-[5.4rem] animate-spin text-indigo-600"
                                                        fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <ul class="py-2">
                                                    @if (!empty($ai_prompt))
                                                        @foreach ($ai_prompt as $prompt)
                                                            <li x-on:click="sendAiRequest('Custom Prompt', '{{ $prompt->action }}')"
                                                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer text-sm">
                                                                {{ $prompt->name }}
                                                            </li>
                                                        @endforeach
                                                    @else
                                                        <li
                                                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer text-sm">
                                                            {{ t('no_result_found') }}
                                                        </li>
                                                    @endif

                                                </ul>
                                            </div>

                                        </li>
                                    </ul>
                                </div>
                                <!-- Canned Reply Card (Appears on Click) -->
                                <div x-show="showCannedReply" x-transition x-cloak
                                    x-on:click.away="showCannedReply = false"
                                    class="absolute bottom-[4rem] left-6 w-[25rem] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 
                                      rounded-md shadow p-4">

                                    <!-- Title (Fixed) -->
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                        {{ t('canned_replies') }}
                                    </h3>


                                    <!-- Scrollable List -->
                                    <ul class="space-y-3 max-h-48 overflow-y-auto pr-2">
                                        <template x-for="reply in filteredCannedReplies()" :key="reply.id">
                                            <li class="p-2 bg-gray-100 dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600"
                                                x-on:click="textMessage = reply.description, showCannedReply = false">

                                                <div class="flex items-center justify-between">
                                                    <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm"
                                                        x-text="reply.title"></p>

                                                    <template x-if="reply.is_public">
                                                        <span
                                                            class="bg-indigo-500 text-white text-xs font-medium px-2 py-1 rounded-lg">Public</span>
                                                    </template>
                                                </div>

                                                <p class="text-gray-600 dark:text-gray-300 text-sm"
                                                    x-text="reply.description">
                                                </p>
                                            </li>
                                        </template>
                                    </ul>

                                </div>

                                <!-- Dropdown (Appears above the button) -->
                                <div x-show="showAttach" x-transition x-on:click.away="showAttach = false"
                                    class="absolute bottom-14 left-6 mb-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow p-2 z-40 w-40">
                                    <button x-on:click="selectFileType('image')"
                                        class="flex items-center gap-2 w-full p-2 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-o-photo class="w-5 h-5 text-indigo-500" />
                                        <span> {{ t('image') }} </span>
                                    </button>

                                    <button x-on:click="selectFileType('document')"
                                        class="flex items-center gap-2 w-full p-2 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-o-document class="w-5 h-5 text-green-500" />
                                        <span> {{ t('document') }} </span>
                                    </button>

                                    <button x-on:click="selectFileType('video')"
                                        class="flex items-center gap-2 w-full p-2 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-o-video-camera class="w-5 h-5 text-red-500" />
                                        <span> {{ t('video') }} </span>
                                    </button>
                                    <button x-show="cannedReplies.length > 0"
                                        x-on:click="showCannedReply = !showCannedReply; showAttach = false"
                                        class="flex items-center gap-2 w-full p-2 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <x-heroicon-o-chat-bubble-left-ellipsis
                                            class="w-5 h-5 flex-shrink-0 text-blue-500" />
                                        <span class="text-sm truncate"> {{ t('canned_reply') }} </span>
                                    </button>
                                </div>
                                <!-- Hidden File Inputs -->
                                <input type="file" id="image_upload" accept="image/*" class="hidden"
                                    x-on:change="handleFilePreview($event, 'image')" />
                                <input type="file" id="document_upload" accept=".pdf,.doc,.docx,.txt" class="hidden"
                                    x-on:change="handleFilePreview($event, 'document')" />
                                <input type="file" id="video_upload" accept="video/*" class="hidden"
                                    x-on:change="handleFilePreview($event, 'video')" />
                            </div>
                            <!-- Emoji Picker -->
                            <div x-show="showEmojiPicker" id="emoji-picker-container"
                                x-on:click.outside="showEmojiPicker = false"
                                class="absolute bottom-[94%] left-[2px] sm:left-0 sm:bottom-full mb-2 z-50 rounded-md">
                                <div id="emoji-picker"></div>
                            </div>
                            <!-- Preview Section -->
                            <div x-show="previewUrl" class="absolute bottom-full rounded-md">
                                <div
                                    class="bg-white dark:bg-gray-900 rounded-lg border border-gray-300 dark:border-gray-700 relative">
                                    <!-- Close (X) Button at Top-Right -->
                                    <button x-on:click="removePreview"
                                        class="absolute top-[-24px] right-[-2px] text-gray-600 dark:text-gray-300">
                                        <x-heroicon-o-x-mark class="w-6 h-6" />
                                    </button>

                                    <!-- Image Preview -->
                                    <template x-if="previewType === 'image'">
                                        <img :src="previewUrl" class="w-full h-40 rounded-md object-cover" />
                                    </template>

                                    <!-- Document Preview -->
                                    <template x-if="previewType === 'document'">
                                        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="font-semibold text-blue-500" x-text="fileName"></span>
                                            </p>
                                        </div>
                                    </template>

                                    <!-- Video Preview -->
                                    <template x-if="previewType === 'video'">
                                        <video controls class="w-full h-40 rounded-md">
                                            <source :src="previewUrl" type="video/mp4">
                                            {{ t('your_broser_not_support_video_tag') }}
                                        </video>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- Overlay User Information (Covers Chat Content) -->
                    <div class="absolute inset-0 bg-black/60 z-40 hidden rounded-lg"
                        x-bind:class="{ '!block': isShowUserInfo }" x-on:click="isShowUserInfo = false">
                    </div>
                    <!-- User Information -->
                    <div x-show="isShowUserInfo" x-cloak x-on:click.away="isShowUserInfo = false"
                        class="absolute top-0 right-0 w-80 h-[calc(100vh_-_100px)] bg-white dark:bg-gray-800 shadow-lg z-50 rounded transform transition-transform duration-300 overflow-hidden flex flex-col"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform translate-x-full"
                        x-transition:enter-end="opacity-100 transform translate-x-0"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 transform translate-x-0"
                        x-transition:leave-end="opacity-0 transform translate-x-full">

                        <!-- Header -->
                        <div class="p-4 flex justify-between items-center border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ t('user_info') }}</h2>
                            <button x-on:click="isShowUserInfo = false"
                                class="text-gray-600 dark:text-gray-300 hover:text-red-500">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>

                        <!-- Scrollable Content -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <!-- Profile Section -->
                            <div class="flex flex-col items-center text-center">
                                <img x-bind:src="'https://ui-avatars.com/api/?name=' + (selectedUser?.name ?? 'User')"
                                    class="rounded-full h-14 w-14 object-cover text-xs" />

                                <h3 class="text-lg font-medium text-gray-800 dark:text-white mt-2"
                                    x-text="selectedUser?.name ?? 'Unknown'"></h3>
                                <span
                                    :class="selectedUser ? {
                                        'bg-violet-100 text-purple-800': selectedUser.type === 'lead',
                                        'bg-red-100 text-red-800': selectedUser.type === 'customer',
                                        'bg-yellow-100 text-yellow-800': selectedUser.type === 'guest',
                                        'bg-gray-100 text-gray-800': !['lead', 'customer', 'guest'].includes(
                                            selectedUser?.type)
                                    } : 'bg-gray-100 text-gray-800'"
                                    class="inline-block ml-2 text-xs font-medium px-2 rounded">
                                    <span x-text="selectedUser?.type ?? 'Unknown'"></span>
                                </span>
                            </div>

                            <!-- Details Section -->
                            <div class="border-t borde border-gray-200 dark:border-gray-700 p-2 mt-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ t('details') }}</h4>
                            </div>

                            <div class="space-y-4 p-2">
                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-chat-bubble-left-ellipsis
                                        class="w-5 h-5 text-orange-500 dark:text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('source') }} <span class="text-indigo-500 text-sm font-normal"
                                            x-show="userInfo?.source?.name"
                                            x-text="userInfo?.source?.name ?? 'Unknown'"></span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-sky-500 dark:text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('creation_time') }} <span class="text-indigo-500 text-sm font-normal"
                                            x-show="userInfo?.created_at"
                                            x-text="new Date(userInfo?.created_at).toLocaleString('en-US', { 
                                            year: 'numeric', 
                                            month: 'short', 
                                            day: '2-digit', 
                                            hour: '2-digit', 
                                            minute: '2-digit', 
                                            second: '2-digit' 
                                        })"></span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-clock class="w-5 h-5 text-yellow-500 dark:text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('last_activity') }} <span class="text-indigo-500 text-sm font-medium"
                                            x-show="userInfo?.created_at"
                                            x-text="selectedUser?.last_msg_time ?? ''"></span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-heroicon-o-phone class="w-5 h-5 text-green-500 dark:text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('phone') }} <span class="text-indigo-500 text-sm font-medium"
                                            x-text="selectedUser?.receiver_id ? '+' + selectedUser.receiver_id : ''"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <div class="border-t border-gray-200 dark:border-gray-700 p-2 mt-4">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ t('notes_title') }}
                                    </h4>
                                    <button class="text-gray-600 dark:text-gray-300 hover:text-green-500"
                                        x-show="userInfo?.created_at">
                                        <a target="_blank"
                                            :href="`{{ route('admin.contacts.save', ['contactId' => 'CONTACT_ID', 'notetab' => 'notes']) }}`
                                            .replace('CONTACT_ID', userInfo?.id || '')">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </a>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Simple Delete Confirmation Modal -->
                <div x-show="isDeleteChatModal" x-cloak>
                    <div class="fixed inset-0 z-50">
                        <!-- Stylish Backdrop with Gradient -->
                        <div class="fixed inset-0 backdrop-blur-sm bg-gradient-to-br from-black/30 to-black/60"></div>
                        <!-- Modal Container with Animation -->
                        <div class="fixed inset-0 z-50 overflow-y-auto">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div x-show="isDeleteChatModal" x-transition:enter="transition ease-out duration-300"
                                    x-on:click.away="isDeleteChatModal = false"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="relative w-full max-w-lg overflow-hidden rounded-lg bg-white/95 dark:bg-slate-800/95 shadow-2xl ring-1 ring-black/5 dark:ring-white/5">
                                    <!-- Gradient Background Accent -->

                                    <div class=" px-4 pb-4 pt-5">
                                        <!-- Content Container -->
                                        <div class="sm:flex sm:items-start">
                                            <!-- Icon -->
                                            <div
                                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />

                                            </div>
                                            <!-- Content -->
                                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                <h3 class="text-base font-semibold leading-6 text-gray-900">
                                                    {{ t('delete_chat_title') }}</h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-slate-700">{{ t('delete_message') }} </p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Buttons -->
                                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                            <button type="button" x-on:click="deleteChat(chatId)"
                                                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                                                {{ t('delete') }}</button>
                                            <button type="button" x-on:click="isDeleteChatModal = false"
                                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                                {{ t('cancel') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div x-show="isSupportAgentModal" x-cloak>
                    <div class="fixed inset-0 z-50">
                        <!-- Stylish Backdrop with Gradient -->
                        <div class="fixed inset-0 backdrop-blur-sm bg-gradient-to-br from-black/30 to-black/60"></div>
                        <!-- Modal Container with Animation -->
                        <div class="fixed inset-0 z-50 overflow-y-auto">
                            <div class="flex min-h-[50%] items-center justify-center p-4">
                                <div x-show="isSupportAgentModal" x-transition:enter="transition ease-out duration-300"
                                    x-on:click.away="isSupportAgentModal = false"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="relative w-full max-w-xl  rounded-lg bg-white/95 dark:bg-slate-800/95 shadow-2xl ring-1 ring-black/5 dark:ring-white/5">
                                    <!-- Gradient Background Accent -->

                                    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30">
                                        <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                                            {{ t('support_agent') }}
                                        </h1>
                                    </div>
                                    <div class=" mx-auto mt-3 px-4" x-init="$watch('selectedOptions', value => {})">
                                        <div class="relative">

                                            <!-- Hidden Input for Livewire -->
                                            <input type="hidden" id="support_agent" name="selectedAgent"
                                                wire:model="selectedAgent"
                                                :value="selectedOptions.map(o => o.id).join(',')">

                                            <div class="mt-1 relative">
                                                <!-- Dropdown Button -->
                                                <button type="button" x-on:click="open = !open"
                                                    class="relative w-full cursor-default rounded-md border border-slate-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:focus:ring-offset-slate-800">
                                                    <span class="block truncate"
                                                        x-text="selectedOptions.length ? selectedOptions.map(o => o.firstname).join(', ') : 'Select Users'">
                                                    </span>
                                                    <span
                                                        class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                        <svg class="h-5 w-5 text-gray-400"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                            fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd"
                                                                d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="open" x-on:click.away="open = false"
                                                    class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 dark:text-white shadow-lg max-h-60 rounded-md ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                                                    style="display: none;">

                                                    <!-- Search Bar -->
                                                    <div class="p-2">
                                                        <input type="text" x-model="search"
                                                            placeholder="Search users..."
                                                            class="w-full border border-gray-300 rounded-md p-2 dark:bg-slate-800 dark:placeholder:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                                    </div>

                                                    <!-- User List -->
                                                    <ul>
                                                        <template x-if="filteredOptions.length">
                                                            <template x-for="option in filteredOptions"
                                                                :key="option.id">
                                                                <li x-on:click="toggleOption(option)"
                                                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-400 hover:text-white flex items-center">

                                                                    <!-- User Name -->
                                                                    <span x-text="option.firstname + ' ' + option.lastname"
                                                                        :class="{
                                                                            'font-semibold': selectedOptions.some(o => o
                                                                                .id ===
                                                                                option.id)
                                                                        }"
                                                                        class="block truncate"></span>

                                                                    <!-- Checkmark Icon -->
                                                                    <span
                                                                        x-show="selectedOptions.some(o => o.id === option.id)"
                                                                        class="absolute right-4 text-indigo-600 dark:text-indigo-400">
                                                                        <x-heroicon-s-check class="h-5 w-5" />
                                                                    </span>
                                                                </li>
                                                            </template>
                                                        </template>

                                                        <template x-if="filteredOptions.length === 0">
                                                            <li class="p-2 text-gray-500 dark:text-white text-center">
                                                                {{ t('no_result_found') }}
                                                            </li>
                                                        </template>

                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="py-4 flex justify-end space-x-3 border-t border-neutral-200 dark:border-neutral-500/30 mt-5 px-6">
                                        <x-button.secondary x-on:click="isSupportAgentModal = false">
                                            {{ t('cancel') }}
                                        </x-button.secondary>
                                        <x-button.loading-button type="submit" x-on:click="submitAgent(selectedUser.id)">
                                            {{ t('submit') }}
                                        </x-button.loading-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
<script>
    function chatApp(chatData) {
        return {
            chats: chatData,
            sortedChats: [], // New array for sorted chats
            selectedUser: {},
            replyTo: null,
            isShowChatMenu: false,
            isShowUserChat: false,
            isShowUserInfo: false,
            showAttach: false,
            textMessage: '',
            previewUrl: '',
            previewType: '',
            fileName: '',
            attachment: null,
            attachmentType: '',
            searchText: '',
            searchMessagesText: '',
            matchedMessages: '',
            searchError: '',
            sendingErrorMessage: '',
            messagesSearch: false,
            showReactionList: false,
            activeMessageId: null,
            showEmojiPicker: false,
            isRecording: false,
            isRecording: false,
            audioBlob: null,
            recordedAudio: null,
            readOnlyPermission: {{ $readOnlyPermission }},
            selectedWaNo: '',
            filteredChat: [],
            overdueAlert: false,
            remainingHours: 0,
            remainingMinutes: 0,
            showAlert: false,
            openAiMenu: false,
            showCannedReply: false,
            loading: false,
            hideUnreadCount: false,
            hasUserInteracted: false,
            userInfo: [],
            cannedReplies: @json($canned_reply),
            mergeFields: [],
            messages: '',
            sources: @json($sources),
            assigneeprofile: [],
            chatId: '',
            metaExtensions: @json(get_meta_allowed_extension()),
            usersname: [],
            users: {!! json_encode($users) !!},
            currentUserId: {!! json_encode(auth()->id()) !!},
            isNotificationSoundEnable: {!! json_encode(get_setting('whats-mark.enable_chat_notification_sound')) !!},
            open: false,
            sending: false,
            search: '',
            options: {!! json_encode($users) !!},
            selectedOptions: @json($selectedAgent ?? []),
            asignAgentView: '',
            isDeleteChatModal: false,
            isSupportAgentModal: false,
            isAdmin: {{ $user_is_admin }},
            enableSupportAgent: {{ $enable_supportagent ? 1 : 0 }},


            toggleOption(option) {
                if (this.selectedOptions.some(o => o.id === option.id)) {
                    this.selectedOptions = this.selectedOptions.filter(item => item.id !== option.id);
                } else {
                    this.selectedOptions.push(option);
                }

            },

            removeOption(option) {
                this.selectedOptions = this.selectedOptions.filter(item => item.id !== option.id);
            },

            get filteredOptions() {
                return this.options.filter(option => {
                    const fullName = `${option.firstname} ${option.lastname}`.toLowerCase();
                    return fullName.includes(this.search.toLowerCase());
                });
            },
            submitAgent(chatId) {
                const agentIds = this.selectedOptions.map(o => o.id); // Extract only IDs
                fetch(`/admin/assign-agent/${chatId}`, { // Only chatId in URL
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            agent_ids: agentIds // Send IDs in body instead of URL

                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.isSupportAgentModal = false;
                        showNotification(data.message, data.success ? 'success' : 'danger');
                        this.asignAgentView = data.agent_layout;
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
            },
            getUserInformation(type, type_id) {
                fetch(`/admin/user-information`, { // Only chatId in URL
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            type: type,
                            type_Id: type_id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            this.userInfo = data[0]; // Assign first user from the array
                        } else {
                            this.userInfo = null; // Reset if no data
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
            },
            getAgentView(chatId) {
                fetch(`/admin/assign-agent-layout/${chatId}`, { // Only chatId in URL
                        method: "GET",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.asignAgentView = data.agent_layout;
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
            },
            filteredCannedReplies() {
                return this.cannedReplies.filter(reply => {
                    return reply.added_from == this.currentUserId || reply.is_public;
                });
            },
            uniqueWaNos() {
                return [...new Set(this.chats.map(chat => chat.wa_no))];
            },

            deleteMessage(messageId) {
                if (!this.selectedUser || !this.selectedUser.messages) return;
                this.selectedUser.messages = this.selectedUser.messages.filter(
                    message => message.id !== messageId
                );
                // Send a request to delete the message from the backend
                fetch(`/admin/remove-message/${messageId}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error("Error deleting message:", data.error);
                        }
                    })
                    .catch(error => console.error("Fetch error:", error));

            },
            deleteChat(chatId) {
                if (!this.selectedUser) return;
                // Send a request to delete the message from the backend
                fetch(`/admin/remove-chat/${chatId}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the chat from the list
                            this.sortedChats = this.sortedChats.filter(chat => chat.id !== chatId);

                            // If the deleted chat was the selected one, reset selectedUser
                            if (this.selectedUser?.id === chatId) {
                                this.isDeleteChatModal = false;
                                this.selectedUser = null;
                                this.isShowUserChat = false;
                            }
                        }
                        showNotification(data.message, data.success ? 'success' : 'danger');
                    })
                    .catch(error => console.error("Fetch error:", error));
            },

            toggleMessageOptions(messageId) {
                this.activeMessageId = this.activeMessageId === messageId ? null : messageId;
            },
            sendAiRequest(menu, submenu) {
                this.loading = true;
                fetch(`/admin/ai-response`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            menu: menu,
                            submenu: submenu,
                            input_msg: this.textMessage
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.textMessage = data.message;
                        this.loading = false;
                        if (!data.success) {
                            showNotification(data.message, 'danger');
                        }
                    })
                    .catch(error => console.error("Fetch error:", error));

            },
            checkOverdueAlert() {
                this.overdueAlert = false;
                this.remainingHours = 0;
                this.remainingMinutes = 0;

                const lastMsgTime = this.selectedUser?.last_msg_time;
                const timezone = '{{ get_setting('general.timezone') }}';

                if (lastMsgTime) {
                    const currentDate = new Date(new Date().toLocaleString("en-US", {
                        timeZone: timezone
                    }));
                    const messageDate = new Date(lastMsgTime);
                    const diffInMinutes = Math.floor((currentDate - messageDate) / (1000 * 60));
                    if (diffInMinutes >= 1440) {
                        this.overdueAlert = true;
                    } else {
                        this.remainingHours = Math.floor((1440 - diffInMinutes) / 60);
                        this.remainingMinutes = (1440 - diffInMinutes) % 60;
                    }
                }
            },

            // Filtering chats based on selected wa_no
            filterChats() {
                this.filteredChat = this.selectedWaNo === "*" ?
                    this.chats // Show all chats if "*" is selected
                    :
                    this.chats.filter(chat => chat.wa_no === this.selectedWaNo);

                // Ensure real-time UI update
                this.sortedChats = [...this.filteredChat];
            },

            searchChats() {
                if (this.searchText) {
                    const query = this.searchText.toLowerCase();
                    this.sortedChats = this.chats
                        .filter(chat =>
                            chat.name.toLowerCase().includes(query) ||
                            chat.last_message.toLowerCase().includes(query)
                        )
                        .sort((a, b) => new Date(b.time_sent) - new Date(a.time_sent)); // Keep sorting order
                } else {
                    this.sortedChats = [...this.chats].sort((a, b) => new Date(b.time_sent) - new Date(a
                        .time_sent)); // Reset if search is empty
                }
            },

            searchMessages() {
                if (this.searchMessagesText) {
                    const query = this.searchMessagesText.toLowerCase().trim();
                    const hasHtmlChars = /[<>]/.test(query);
                    const isHtmlTag = /^[a-z]+$/.test(query) && document.createElement(query).toString() !==
                        "[object HTMLUnknownElement]";

                    if (hasHtmlChars || isHtmlTag) {
                        this.searchError = "Searching for HTML tags is not allowed.";
                        this.selectedUser.messages.forEach(msg => msg.match = false);
                        this.matchedMessages = [];
                        this.updateSearchCounter(0, 0);
                        return;
                    } else {
                        this.searchError = "";
                    }

                    this.matchedMessages = [];

                    this.selectedUser.messages.forEach((msg, index) => {
                        const cleanMessage = sanitizeMessage(msg.message || '');
                        if (cleanMessage.toLowerCase().includes(query)) {
                            msg.match = true;
                            this.matchedMessages.push({
                                messageIndex: index,
                                position: cleanMessage.indexOf(query)
                            });
                        } else {
                            msg.match = false;
                        }
                    });

                    this.matchedMessages = [...new Set(this.matchedMessages)]; // Ensure unique matche
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const highlights = document.querySelectorAll('.highlight');
                            this.matchedMessages = Array.from(highlights);
                            this.matchIndex = 0;

                            if (this.matchedMessages.length > 0) {
                                this.scrollToMatch();
                            }

                            this.updateSearchCounter(
                                this.matchedMessages.length > 0 ? 1 : 0,
                                this.matchedMessages.length
                            );
                        }, 100);
                    });
                } else {
                    this.selectedUser.messages.forEach(msg => msg.match = false);
                    this.matchedMessages = [];
                    this.updateSearchCounter(0, 0);
                }
            },

            updateSearchCounter(current, total) {
                let counter = document.getElementById('search-counter');
                if (!counter) {
                    counter = document.createElement('span');
                    counter.id = 'search-counter';
                    counter.className = 'text-sm text-gray-600 dark:text-gray-400 ml-2';
                    const searchContainer = document.querySelector('.search-container');
                    if (searchContainer) {
                        searchContainer.appendChild(counter);
                    }
                }

                // Prevent unnecessary updates
                if (counter.textContent !== `${current} of ${total}`) {
                    counter.textContent = total > 0 ? `${current} of ${total}` : (this.searchMessagesText ?
                        'No matches' : '');
                }
            },
            scrollToMatch() {
                if (this.matchedMessages.length === 0) return;

                // Remove highlighting from all matches
                this.matchedMessages.forEach(el => {
                    el.classList.remove('active-highlight');
                });

                // Get the current highlight element
                const currentHighlight = this.matchedMessages[this.matchIndex];

                if (currentHighlight) {
                    // Add active class to current highlight
                    currentHighlight.classList.add('active-highlight');

                    // Scroll the highlight into view
                    currentHighlight.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });

                    // Update the counter
                    this.updateSearchCounter(this.matchIndex + 1, this.matchedMessages.length);
                }
            },

            nextMatch() {
                if (this.matchedMessages.length === 0) return;

                this.matchIndex = (this.matchIndex + 1) % this.matchedMessages.length;
                this.scrollToMatch();
            },

            prevMatch() {
                if (this.matchedMessages.length === 0) return;

                this.matchIndex = (this.matchIndex - 1 + this.matchedMessages.length) % this.matchedMessages.length;
                this.scrollToMatch();
            },

            highlightSearch(text) {
                if (this.searchError !== '' || !this.searchMessagesText || !text) return text;

                const sanitizedText = sanitizeMessage(text);
                const query = this.searchMessagesText.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');

                // Replace with highlight spans
                return sanitizedText.replace(
                    new RegExp(`(${query})`, "gi"),
                    `<span class="bg-yellow-300 dark:bg-yellow-600 px-1 py-0.5 rounded highlight">$1</span>`
                );
            },
            openDropdown(event) {
                this.showReactionList = true;

            },
            toggleRecording() {
                if (!this.isRecording) {
                    this.startRecording();
                } else {
                    this.stopRecording();
                }
            },

            startRecording() {
                if (!this.recorder) {
                    this.recorder = new Recorder({
                        type: "mp3",
                        sampleRate: 16000,
                        bitRate: 16,
                        onProcess: (buffers, powerLevel, bufferDuration, bufferSampleRate) => {
                            // Optional real-time updates
                        }
                    });
                }
                this.recorder.open(() => {
                    this.isRecording = true;
                    this.recorder.start();
                }, (err) => {
                    console.error("Failed to start recording:", err);
                });
            },

            stopRecording() {
                if (this.recorder && this.isRecording) {
                    this.recorder.stop((blob) => {
                        this.recorder.close();
                        this.isRecording = false;
                        this.audioBlob = blob;
                        this.recordedAudio = URL.createObjectURL(blob);
                        this.sendMessage();
                    }, (err) => {
                        console.error("Failed to stop recording:", err);
                    });
                }
            },

            sendMessage() {
                if (this.sending) return;
                if (!this.textMessage.trim() && !this.attachment && !this.audioBlob) return;
                this.sending = true; // Disable button
                let formData = new FormData();
                formData.append('id', this.selectedUser.id);
                formData.append('type', this.selectedUser.type);
                formData.append('type_id', this.selectedUser.type_id);
                formData.append('message', this.textMessage.trim() || '');
                formData.append('ref_message_id', this.replyTo ? this.replyTo.messasgeID : '');

                if (this.attachment) {
                    const keyName = this.attachmentType; // image, video, or document
                    formData.append(keyName, this.attachment, this.fileName);
                }

                if (this.audioBlob) {
                    formData.append('audio', this.audioBlob, 'audio.mp3');
                }

                this.sendFormData(formData);
            },

            sendFormData(formData) {
                this.sendingErrorMessage = '';
                fetch('send-message', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success === false) {
                            this.sendingErrorMessage = data.message || 'Failed to send message';
                            setTimeout(() => {
                                this.sendingErrorMessage = '';
                            }, 5000);
                            return;
                        }
                        this.textMessage = '';
                        this.sendingErrorMessage = '';
                        this.attachment = null;
                        this.audioBlob = null;
                        this.removePreview();
                        this.cancelReply();
                        this.scrollToBottom();
                    })
                    .catch(error => console.error('Error sending message:', error))
                    .finally(() => {
                        this.sending = false; // Re-enable button
                    });
            },

            sanitizeLastMessage(content) {
                return sanitizeMessage(content).replace(/<\/?[^>]+(>|$)/g, ""); // Sanitize & strip HTML
            },
            trimMessage(message, maxLength = 100) {
                const sanitizedMessage = sanitizeMessage(message);
                if (sanitizedMessage.length > maxLength) {
                    return sanitizedMessage.substring(0, maxLength) + '...';
                }
                return sanitizedMessage;
            },

            getOriginalMessage(refMessageId) {
                if (typeof(this.selectedUser.messages) === "object") {
                    const message = this.selectedUser.messages.find(msg => msg.message_id === refMessageId) || {};
                    return {
                        ...message,
                        message: this.trimMessage(message.message),
                        assets_url: message.url || ''
                    };
                }
            },
            getMergeFields(chatType) {

                fetch(`/admin/load-mergefields/${chatType}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            type: chatType,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {

                        this.mergeFields = data;
                    })
                    .catch(error => console.error("Fetch error:", error));
            },
            handleTributeEvent() {
                setTimeout(() => {
                    if (typeof window.Tribute === 'undefined') {
                        return;
                    }
                    let mentionableEl = document.querySelector('.mentionable');
                    if (!mentionableEl) {
                        return; // Exit if element doesn't exist
                    }
                    // Initialize Tribute with updated mergeFields
                    let tribute = new window.Tribute({
                        trigger: '@',
                        values: this.mergeFields,
                    });
                    tribute.attach(mentionableEl);
                    mentionableEl.setAttribute('data-tribute', 'true'); // Mark as initialized

                    document.querySelectorAll('.tribute-container').forEach((el) => el.remove());
                }, 2000);
            },
            handleEnterKey(event) {
                if (this.sending) return;
                // Check if Tribute dropdown is active
                let tributeDropdown = document.querySelector('.tribute-container');

                if (tributeDropdown && tributeDropdown.style.display === 'block') {
                    event.preventDefault(); // Prevent sending the message when Tribute is open
                    return;
                }

                // If Tribute dropdown is not open, send the message
                this.sendMessage();
            },

            // Updated getChatMessages method with async/await
            async getChatMessages(chatId, lastMessageId = 0) {
                try {
                    let url = `/admin/chat_messages/${chatId}`;
                    if (lastMessageId > 0) {
                        url += `/${lastMessageId}`;
                    }
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.error('Error fetching messages:', error);
                    return []; // Return empty array on error
                }
            },
            // Updated checkScrollTop method with async/await
            async checkScrollTop(chatId) {
                let chatBox = this.$refs.chatContainer;
                if (chatBox.scrollTop === 0) {
                    try {
                        const oldScrollHeight = chatBox.scrollHeight;

                        // Loader UI
                        const wrapperDiv = document.createElement('div');
                        wrapperDiv.className = 'flex justify-center w-full my-2';
                        wrapperDiv.id = 'loader-wrapper';

                        const loader = document.createElement('div');
                        loader.className =
                            'text-center py-2 px-4 rounded-full bg-white dark:bg-gray-700 shadow-sm inline-flex items-center transition-all duration-300';
                        loader.id = 'scroll-loader';
                        loader.innerHTML =
                            '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite] mr-2"></span><span class="text-gray-700 dark:text-gray-200">Loading messages...</span>';

                        wrapperDiv.appendChild(loader);
                        chatBox.prepend(wrapperDiv);

                        // **Fix: Ensure selectedUser.messages exists before accessing ID**
                        const firstMessageId = this.selectedUser?.messages?.length ? this.selectedUser.messages[0]
                            .id : null;

                        if (!firstMessageId) {
                            console.warn('No messages available to load older ones.');
                            document.getElementById('loader-wrapper')?.remove();
                            return;
                        }

                        // Fetch older messages
                        const olderMessages = await this.getChatMessages(chatId, firstMessageId);

                        document.getElementById('loader-wrapper')?.remove();

                        if (olderMessages.length > 0) {
                            const transitionContainer = document.createElement('div');
                            transitionContainer.className = 'opacity-0 transition-opacity duration-500';
                            transitionContainer.id = 'new-messages-container';
                            chatBox.prepend(transitionContainer);

                            this.selectedUser.messages = [...olderMessages, ...this.selectedUser.messages];

                            await this.$nextTick();
                            chatBox.scrollTop = chatBox.scrollHeight - oldScrollHeight;

                            setTimeout(() => {
                                const container = document.getElementById('new-messages-container');
                                if (container) {
                                    container.classList.remove('opacity-0');
                                    container.classList.add('opacity-100');

                                    setTimeout(() => {
                                        if (container && container.parentNode) {
                                            container.replaceWith(...container.childNodes);
                                        }
                                    }, 500);
                                }
                            }, 10);
                        } else {
                            const noMoreWrapper = document.createElement('div');
                            noMoreWrapper.className = 'flex justify-center w-full my-2';
                            noMoreWrapper.id = 'no-more-wrapper';

                            const noMoreElement = document.createElement('div');
                            noMoreElement.className =
                                'text-center py-2 px-4 rounded-full bg-gray-100 dark:bg-gray-800 text-sm text-gray-500 dark:text-gray-400 shadow-sm inline-block transition-all duration-300';
                            noMoreElement.id = 'no-more-messages';
                            noMoreElement.innerText = 'No more messages';

                            noMoreWrapper.appendChild(noMoreElement);
                            chatBox.prepend(noMoreWrapper);

                            setTimeout(() => {
                                const element = document.getElementById('no-more-messages');
                                if (element) {
                                    element.classList.add('opacity-0');
                                    setTimeout(() => {
                                        document.getElementById('no-more-wrapper')?.remove();
                                    }, 300);
                                }
                            }, 2000);
                        }
                    } catch (error) {
                        console.error('Error loading older messages:', error);
                        document.getElementById('loader-wrapper')?.remove();

                        const errorWrapper = document.createElement('div');
                        errorWrapper.className = 'flex justify-center w-full my-2';
                        errorWrapper.id = 'error-wrapper';

                        const errorElement = document.createElement('div');
                        errorElement.className =
                            'text-center py-2 px-4 rounded-full bg-red-50 dark:bg-red-900/30 text-sm text-red-600 dark:text-red-400 shadow-sm inline-block transition-all duration-300';
                        errorElement.id = 'load-error';
                        errorElement.innerText = 'Failed to load messages';

                        errorWrapper.appendChild(errorElement);
                        chatBox.prepend(errorWrapper);

                        setTimeout(() => {
                            const element = document.getElementById('load-error');
                            if (element) {
                                element.classList.add('opacity-0');
                                setTimeout(() => {
                                    document.getElementById('error-wrapper')?.remove();
                                }, 300);
                            }
                        }, 3000);
                    }
                }
            },

            selectChat(chat) {
                this.selectedUser = chat;
                this.isShowUserChat = true;
                this.isShowChatMenu = false;
                this.overdueAlert = false;
                this.loading = true; // Start loading indicator   
                this.getAgentView(chat.id);
                this.chatId = this.selectedUser.id;
                this.getUserInformation(chat.type, chat.type_id);
                this.getMergeFields(chat.type);
               
                // Clear messages immediately to prevent showing the old chat
                this.messages = [];
                this.getChatMessages(chat.id).then((data) => {
                    this.messages = data; // Update messages after fetching
                    this.selectedUser.messages = this.messages; // Ensure UI updates
                    this.handleTributeEvent();
                    // Hide unread count for this specific chat
                    this.$nextTick(() => {
                        chat.hideUnreadCount = true;
                    });
                    this.loading = false; // Hide loader only after everything is done
                    this.scrollToBottom();
                });

                // Ensure agent data exists before parsing
                if (this.selectedUser.agent && this.selectedUser.agent !== "null") {
                    let agentData = JSON.parse(this.selectedUser.agent); // Parse JSON string safely
                    let agentIds = agentData.agents_id ? agentData.agents_id.split(',').map(id => id.trim()) : [];

                    // Find matching options
                    this.selectedOptions = this.options.filter(option => agentIds.includes(option.id.toString()));
                } else {
                    this.selectedOptions = []; // Reset if agent data is missing
                }

                this.checkOverdueAlert();
                this.textMessage = '';
                this.attachment = null;
                this.audioBlob = null;
                this.removePreview();
                this.cancelReply();
                this.scrollToBottom();

            },

            countUnreadMessages(chatId) {
                const interaction = this.sortedChats ? this.sortedChats.find(inter => inter.id === chatId) : undefined;

                if (interaction) {
                    interaction.messages = this.messages; // Ensure this only runs if interaction exists
                    return interaction.unreadmessagecount || 0;
                }

                return 0;
            },


            initialize() {
                this.sortedChats = [...this.chats].sort((a, b) => {
                    // Ensure messages array exists, else default to an empty array
                    const messagesA = Array.isArray(a.messages) ? a.messages : [];
                    const messagesB = Array.isArray(b.messages) ? b.messages : [];

                    // Get the latest time_sent from messages array or fallback to outer time_sent
                    const latestTimeA = messagesA.length > 0 ?
                        new Date(messagesA[messagesA.length - 1].time_sent) :
                        new Date(a.time_sent);

                    const latestTimeB = messagesB.length > 0 ?
                        new Date(messagesB[messagesB.length - 1].time_sent) :
                        new Date(b.time_sent);

                    return latestTimeB - latestTimeA; // Sorting in descending order
                });

                window.addEventListener('updateTextMessage', (event) => {
                    this.textMessage = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    this.loading = false;
                });

                this.initializePusher();
            },


            replyToMessage(message) {
                if (!message) return;

                let textContent = "";
                let urlContent = "";
                let messageType = "";

                if (typeof message === "string") {
                    textContent = message;
                } else {
                    // Check if the message contains text or a URL
                    textContent = message.message || "";
                    urlContent = message.url || "";
                    messageType = message.type || "";
                }

                // Strip HTML tags if it's a text message
                if (textContent) {
                    textContent = textContent.replace(/<[^>]*>?/gm, '');
                    let maxLength = 100;
                    if (textContent.length > maxLength) {
                        textContent = textContent.substring(0, maxLength) + "...";
                    }
                }

                // Store data properly
                this.replyTo = {
                    text: textContent,
                    url: urlContent,
                    type: messageType,
                    messasgeID: message.message_id
                };
                this.activeMessageId = null;
                this.scrollToBottom();
            },


            cancelReply() {
                this.replyTo = null; // Clear reply message
            },

            scrollToBottom() {
                if (this.isShowUserChat) {
                    setTimeout(() => {
                        const element = document.querySelector('.chat-conversation-box');
                        if (element) {
                            // Scroll smoothly to the bottom
                            element.scrollTo({
                                top: element.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    }, 0);
                }
            },
            scrollToMessage(ref_message_id) {
                if (!ref_message_id) {
                    console.error("Error: ref_message_id is null or undefined.");
                    return;
                }
                // Find the message element dynamically
                const targetMessage = document.querySelector(`[data-message-id="${ref_message_id}"]`);

                if (!targetMessage) {
                    console.error(`Error: No element found for message ID '${ref_message_id}'`);
                    return;
                }

                // Get the parent wrapper (entire message container)
                const messageWrapper = targetMessage.closest('.message-item');

                if (!messageWrapper) {
                    console.error("Error: Message wrapper not found.");
                    return;
                }

                // Smooth scroll
                messageWrapper.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Add grayscale effect
                messageWrapper.classList.add('contrast-50', 'transition-all', 'duration-500');

                // Remove the effect after 1 second
                setTimeout(() => {
                    messageWrapper.classList.remove('contrast-50');
                }, 1000);
            },

            selectFileType(type) {
                this.showAttach = false;
                // Trigger corresponding file input
                if (type === 'image') {
                    document.getElementById('image_upload').click();
                } else if (type === 'document') {
                    document.getElementById('document_upload').click();
                } else if (type === 'video') {
                    document.getElementById('video_upload').click();
                }
            },

            handleFilePreview(event, type) {
                const file = event.target.files[0];
                if (!file) return;

                // Get allowed extensions and max size
                const allowedExtensions = this.metaExtensions[type].extension.replace(/\s/g, '').split(',');
                const maxSize = this.metaExtensions[type].size * 1024 * 1024; // Convert MB to bytes

                // Get file extension
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                // Validate file extension
                if (!allowedExtensions.includes(fileExtension)) {
                    showNotification(`Invalid file type`, 'danger');

                    return;
                }

                // Validate file size
                if (file.size > maxSize) {
                    showNotification(`File size exceeds the limit! Max size: ${this.metaExtensions[type].size}MB`,
                        'danger');

                    return;
                }

                // If valid, proceed
                this.previewType = type;
                this.previewUrl = URL.createObjectURL(file);
                this.fileName = file.name;
                this.attachment = file;
                this.attachmentType = type;
            },

            removePreview() {
                this.previewUrl = '';
                this.previewType = '';
                this.fileName = '';
                this.attachment = null;
                this.attachmentType = '';
            },
            shouldShowDate(currentMessage, previousMessage) {
                if (!previousMessage || !currentMessage) return true;
                return this.formatDate(currentMessage.time_sent) !== this.formatDate(previousMessage.time_sent);
            },
            formatDate(dateString) {
                const wb_date = new Date(dateString);
                const wb_options = {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                };
                return wb_date.toLocaleDateString('en-GB', wb_options).replace(' ', '-').replace(' ', '-');
            },
            formatTime(time) {
                if (!time) {
                    return "--"; // Placeholder for missing time
                }
                const messageDate = new Date(time);

                if (isNaN(messageDate.getTime())) {

                    return "Invalid time";
                }
                // Return only the time in HH:MM AM/PM format
                return messageDate.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            },
            formatLastMessageTime(timestamp) {
                // If no timestamp is provided, return empty string
                if (!timestamp) return '';

                // Parse the timestamp (assuming format: YYYY-MM-DD HH:MM:SS)
                const messageDate = new Date(timestamp);

                // Get current date for comparison
                const now = new Date();

                // Check if the date is valid
                if (isNaN(messageDate.getTime())) {
                    return timestamp; // Return original if parsing failed
                }

                // Format time to 12-hour format with AM/PM
                const formatTimeOnly = (date) => {
                    return date.toLocaleString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                };

                // Check if it's today
                if (
                    messageDate.getDate() === now.getDate() &&
                    messageDate.getMonth() === now.getMonth() &&
                    messageDate.getFullYear() === now.getFullYear()
                ) {
                    // Today - show only time (e.g., "3:39 PM")
                    return formatTimeOnly(messageDate);
                }

                // Check if it's yesterday
                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);

                if (
                    messageDate.getDate() === yesterday.getDate() &&
                    messageDate.getMonth() === yesterday.getMonth() &&
                    messageDate.getFullYear() === yesterday.getFullYear()
                ) {
                    // Yesterday - show "Yesterday" and time
                    return `Yesterday`;
                }

                // It's older than yesterday - show date with year (e.g., "Mar 15, 2024")
  // Check if it's the current year
  if (messageDate.getFullYear() === now.getFullYear()) {
    // Same year - show date without year (e.g., "Mar 15")
    return messageDate.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  } else {
    // Different year - show date with year (e.g., "Mar 15, 2024")
    return messageDate.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }
            },
            initializeUserInteractionTracking() {
                // Add event listeners for user interaction
                const markInteracted = () => {
                    this.hasUserInteracted = true;
                    // Remove the listeners once we've detected interaction
                    document.removeEventListener('click', markInteracted);
                    document.removeEventListener('keydown', markInteracted);
                    document.removeEventListener('touchstart', markInteracted);
                };

                document.addEventListener('click', markInteracted);
                document.addEventListener('keydown', markInteracted);
                document.addEventListener('touchstart', markInteracted);
            },
            playNotificationSound() {
                // Only play if notifications are enabled
                if (!this.isNotificationSoundEnable) return;

                if (this.hasUserInteracted) {
                    // User has interacted, play sound immediately
                    const audio = new Audio("{{ asset('audio/whatsapp_notification.mp3') }}");
                    audio.play().catch(error => console.error("Audio play failed:", error));
                }
            },


            initializePusher() {
                // Initialize Pusher with your app key and cluster
                const pusher = new Pusher(window.pusherConfig.key, {
                    cluster: window.pusherConfig.cluster,
                    encrypted: true,
                });
                // Subscribe to the 'interactions-channel'
                const channel = pusher.subscribe('whatsmark-chat-channel');
                // Listen for the 'interaction-update' event
                channel.bind('whatsmark-chat-event', (data) => {

                    // Update interactions based on real-time data from Pusher
                    this.appendNewChats(data.chat);

                });
            },
            appendNewChats(newChats) {
                const existingInteractions = [...this.sortedChats]; // Existing interactions array

                const index = existingInteractions.findIndex(chat => chat.id === newChats
                    .id); //matching interaction id to newChats id
                let isNewMessage = false;
                if (index !== -1) { //interaction IDs match, replace the whole existing message with the new message
                    const existingInteraction = existingInteractions[index];

                    // Create a new object that contains all properties from newChats except messages
                    const updatedInteraction = {
                        ...existingInteraction, // Existing properties
                        ...newChats, // Spread newChats properties
                        messages: existingInteraction.messages // Keep the original messages for now
                    };
                    // Find index of matching message_id
                    const find_msg_index = Array.isArray(existingInteraction.messages) ?
                        existingInteraction.messages.findIndex(interaction =>
                            Array.isArray(newChats.messages) &&
                            newChats.messages.some(newMsg => interaction.message_id === newMsg.message_id)
                        ) :
                        -1;
                    //matching interaction messages id to newChats messages id
                    if (find_msg_index !== -1) {
                        // If IDs match, replace the whole existing message with the new message
                        existingInteraction.messages[find_msg_index] = {
                            ...newChats.messages[0]
                        };
                    } else if (this.selectedUser.id == existingInteraction.id) {

                        existingInteraction.messages.push(...newChats.messages);

                        isNewMessage = true;
                    }
                    existingInteractions[index] = updatedInteraction;
                    this.countUnreadMessages(existingInteractions[index].id);
                    this.initializeUserInteractionTracking();
                } else {
                    // Ensure newChats.messages is an array or initialize it as an empty array
                    if (!Array.isArray(newChats.messages)) {
                        newChats.messages = [newChats.messages];
                    }
                    // If the interaction id does not exist, push newChats directly
                    existingInteractions.push({
                        ...newChats,
                        messages: [...newChats.messages] // Ensure messages is properly handled
                    });
                    isNewMessage = true;
                    if (existingInteractions[index]) {
                        this.countUnreadMessages(existingInteractions[index].id);
                    }

                    this.initializeUserInteractionTracking();
                }
                // Now sort the `existingInteractions` array by `time_sent`
                existingInteractions.sort((a, b) => {
                    // Ensure messages array exists, else default to an empty array
                    const messagesA = Array.isArray(a.messages) ? a.messages : [];
                    const messagesB = Array.isArray(b.messages) ? b.messages : [];

                    // Find the latest message by comparing all time_sent values
                    let latestTimeA = new Date(a.time_sent || 0);
                    let latestTimeB = new Date(b.time_sent || 0);

                    // Check each message to find the most recent one
                    for (const msg of messagesA) {
                        if (msg && msg.time_sent) {
                            const msgTime = new Date(msg.time_sent);
                            if (msgTime > latestTimeA) {
                                latestTimeA = msgTime;
                            }
                        }
                    }

                    for (const msg of messagesB) {
                        if (msg && msg.time_sent) {
                            const msgTime = new Date(msg.time_sent);
                            if (msgTime > latestTimeB) {
                                latestTimeB = msgTime;
                            }
                        }
                    }

                    return latestTimeB - latestTimeA; // Sorting in descending order
                });
                this.sortedChats = existingInteractions;

                if (!this.isAdmin && this.enableSupportAgent == 1) {
                    const staff_id = @json($login_user);
                    const filteredNewInteractions = existingInteractions.filter(interaction => {
                        const chatagent = interaction.agent;
                        if (!chatagent) return false;
                        if (chatagent) {
                            const preResponse = JSON.parse(chatagent);
                            const temAgentId = preResponse.agents_id;
                            const agentIds = temAgentId ? temAgentId.split(",").map(Number) : []
                            const assignIds = preResponse.assign_id ? preResponse.assign_id : ''
                            // Check if `staff_id` is included in either `agentIds` or `assignIds`
                            return agentIds.includes(staff_id) || assignIds == staff_id;
                        }
                        return [];
                    });
                    this.sortedChats = this.sortedChats.filter(
                        existing => filteredNewInteractions.some(newInteraction => newInteraction.id === existing
                            .id)
                    );

                } else {
                    // Append new interactions for admins
                    this.sortedChats = existingInteractions;
                }
                if (isNewMessage && this.isNotificationSoundEnable) {
                    this.playNotificationSound();
                }
            },
        }
    }
</script>
