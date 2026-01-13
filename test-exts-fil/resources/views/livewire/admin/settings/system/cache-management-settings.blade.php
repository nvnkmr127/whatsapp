<div class="mx-auto px-4 md:px-0">
    <x-slot:title>
        {{ t('performance_optimization') }}
    </x-slot:title>
    <!-- Page Heading -->
    <div class="pb-6">
        <x-settings-heading>{{ t('system_setting') }}</x-settings-heading>
    </div>

    <!-- Layout with Sidebar and Main Content -->
    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-admin-system-settings-navigation wire:ignore />
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-neutral-200 dark:border-neutral-500/30 ">
                <div class="px-6 py-4 border-b dark:border-neutral-500/30">
                    <x-settings-heading>
                        {{ t('performance_optimization') }}
                    </x-settings-heading>
                    <x-settings-description>
                        {{ t('cache_description') }}
                    </x-settings-description>
                </div>
                <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
                    @foreach ($cacheSizes as $type => $size)
                        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="flex flex-col sm:flex-row  sm:items-center sm:space-y-0">
                                <div class="grow flex items-start space-x-4">
                                    <!-- Icons based on type -->
                                    <div class="shrink-0 mt-1">
                                        @if ($type === 'framework')
                                            <div class="p-2 bg-emerald-100 dark:bg-emerald-500/20 rounded-lg">
                                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7" />
                                                </svg>
                                            </div>
                                        @elseif ($type === 'views')
                                            <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-lg">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </div>
                                        @elseif ($type === 'config')
                                            <div class="p-2 bg-purple-100 dark:bg-purple-500/20 rounded-lg">
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                </svg>
                                            </div>
                                        @elseif ($type === 'routing')
                                            <div class="p-2 bg-amber-100 dark:bg-amber-500/20 rounded-lg">
                                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                </svg>
                                            </div>
                                        @elseif ($type === 'logs')
                                            <div class="p-2 bg-red-100 dark:bg-red-500/20 rounded-lg">
                                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium">
                                            @if ($type === 'framework')
                                                {{ t('clear_framework_text') }}
                                            @elseif ($type === 'views')
                                                {{ t('view_text') }}
                                            @elseif ($type === 'config')
                                                {{ t('clear_config') }}
                                            @elseif ($type === 'routing')
                                                {{ t('clear_cache_routing') }}
                                            @elseif ($type === 'logs')
                                                {{ t('clear_system_log_file') }}
                                            @endif
                                        </p>
                                        <span
                                            class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $size === '0 B' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-300' }}">
                                            {{ t('size') . ' ' . $size }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="flex justify-end shrink-0 sm:ml-4">
                                    <button wire:click="clearCache('{{ $type }}')"
                                        class="bg-indigo-600 dark:bg-indigo-500 dark:focus:ring-offset-slate-800 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:pointer-events-none duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 font-medium hover:bg-indigo-700 inline-flex items-center justify-center px-3 py-2 rounded-lg sm:px-3 sm:py-2 sm:text-sm sm:w-auto text-white text-xs transition-colors"
                                        wire:loading.attr="disabled" wire:target="clearCache('{{ $type }}')">

                                        <span wire:loading.remove wire:target="clearCache('{{ $type }}')">
                                            {{ t('run_tool') }}
                                        </span>
                                        <span wire:loading wire:target="clearCache('{{ $type }}')"
                                            class="inline-flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-flex"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            {{ t('processing') }}
                                        </span>
                                    </button>
                                </div>
                        </li>
                    @endforeach
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                        <div class="flex flex-wrap gap-4 sm:gap-8 w-full justify-between">
                            <!-- Debug Mode -->
                            <div class="flex items-start space-x-4">
                                <!-- Debug Mode Icon -->
                                <div class="shrink-0">
                                    <div class="p-2 bg-yellow-100 dark:bg-yellow-500/20 rounded-lg">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between w-64">
                                    <!-- Debug Mode Label -->
                                    <div>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium">
                                            {{ t('enable_debug_mode') }}
                                        </p>
                                        <span
                                            class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-300">
                                            Mode : {{ $environment ? 'true' : 'false' }}
                                        </span>
                                    </div>

                                    <!-- Debug Mode Switch -->
                                    <div x-data="{ 'environment': @entangle('environment') }">
                                        <button type="button" wire:click="toggleEnvironment"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                                            :class="environment ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                                            role="switch" :aria-checked="environment.toString()">
                                            <span class="sr-only">{{ t('enable_debug_mode') }}</span>
                                            <span
                                                class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="environment ? 'translate-x-5' : 'translate-x-0'">
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="environment ? 'opacity-0 ease-out duration-100' :
                                                        'opacity-100 ease-in duration-200'">
                                                    <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                                                </span>
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="environment ? 'opacity-100 ease-in duration-200' :
                                                        'opacity-0 ease-out duration-100'">
                                                    <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Production Mode -->
                            <div class="flex items-start space-x-4">
                                <!-- Production Mode Icon -->
                                <div class="shrink-0">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between w-64">
                                    <!-- Production Mode Label -->
                                    <div>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium">
                                            {{ t('enable_production_mode') }}
                                        </p>
                                        <span
                                            class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-300">
                                            Mode : {{ $production_mode ? 'Production' : 'Local' }}
                                        </span>
                                    </div>

                                    <!-- Production Mode Switch -->
                                    <div x-data="{ 'production_mode': @entangle('production_mode') }">
                                        <button type="button" wire:click="toggleEnableProductionMode"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                                            :class="production_mode ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                                            role="switch" :aria-checked="production_mode.toString()">
                                            <span class="sr-only">{{ t('enable_production_mode') }}</span>
                                            <span
                                                class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="production_mode ? 'translate-x-5' : 'translate-x-0'">
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="production_mode ? 'opacity-0 ease-out duration-100' :
                                                        'opacity-100 ease-in duration-200'">
                                                    <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                                                </span>
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="production_mode ? 'opacity-100 ease-in duration-200' :
                                                        'opacity-0 ease-out duration-100'">
                                                    <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- WhatsApp Log -->
                            <div class="flex items-start space-x-4">
                                <!-- WhatsApp Log Icon -->
                                <div class="shrink-0">
                                    <div class="p-2 bg-green-100 dark:bg-green-500/20 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- WhatsApp Log Label -->
                                <div class="flex items-center justify-between w-64">
                                    <div>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium">
                                            {{ t('enable_whatsapp_log') }}
                                        </p>
                                        <span
                                            class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-300">
                                            Mode : {{ $enable_wp_log ? 'true' : 'false' }}
                                        </span>
                                    </div>

                                    <!-- WhatsApp Log Switch -->
                                    <div x-data="{ 'enable_wp_log': @entangle('enable_wp_log') }">
                                        <button type="button" wire:click="toggleEnableWpLog"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                                            :class="enable_wp_log ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                                            role="switch" :aria-checked="enable_wp_log.toString()">
                                            <span class="sr-only">{{ t('enable_whatsapp_log') }}</span>
                                            <span
                                                class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="enable_wp_log ? 'translate-x-5' : 'translate-x-0'">
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="enable_wp_log ? 'opacity-0 ease-out duration-100' :
                                                        'opacity-100 ease-in duration-200'">
                                                    <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                                                </span>
                                                <span
                                                    class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                                    :class="enable_wp_log ? 'opacity-100 ease-in duration-200' :
                                                        'opacity-0 ease-out duration-100'">
                                                    <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <!-- Notification -->
                @if (session()->has('message'))
                    <div
                        class="mt-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800">
                        {{ session('message') }}
                    </div>
                @endif

                <div wire:loading wire:target="clearCache" class="my-4 text-center px-6">
                    <p class="text-sm text-blue-600">{{ t('processing_cache_clearing') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
