<div class="mx-auto px-4 md:px-0" x-data="{
    expandedVersions: { '1.1.3': true },
    toggleVersion(version) {
        this.expandedVersions[version] = !this.expandedVersions[version];
    }
}">
    <x-slot:title>
        {{ t('software_update_management') }}
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
            <x-card class="rounded-lg">
                <x-slot:header>
                    <x-settings-heading>
                        {{ t('software_update_management') }}
                    </x-settings-heading>
                    <x-settings-description>
                        {{ t('software_update_management_description') }}
                    </x-settings-description>
                </x-slot:header>
                <x-slot:content>
                    <div class="mx-auto">
                        @if (!empty($support))
                            <div
                                class="mb-6 border-l-4 rounded-r-md z-100  dark:bg-gray-700  dark:text-white {{ $support['success'] == false ? 'bg-red-100 border-red-500 text-red-700 dark:border-red-600' : 'bg-green-100 border-green-500 text-green-700 dark:bg-gray-700 dark:border-green-600' }}">
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 ">
                                        <div
                                            class="flex flex-col items-start {{ $support['success'] == false ? 'bg-red-100 border-red-500 text-red-700 dark:border-red-600' : 'bg-green-100 border-green-500 text-green-700 dark:bg-gray-700 dark:border-green-600' }} ">
                                            <h1
                                                class="text-xl font-bold {{ $support['success'] == false ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                {{ t('support') }}
                                            </h1>
                                            <div
                                                class="mt-2 font-medium {{ $support['success'] == false ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} ">
                                                {{ $support['message'] }}
                                            </div>
                                            @if ($support['success'] == false)
                                                <div
                                                    class="mt-1 flex items-start flex-col text-red-700 dark:text-red-300">
                                                    <a href="{{ config('installer.license_verification.renew_support_url') }}"
                                                        class="text-sm mt-1 text-red-700 dark:text-red-400 underline"
                                                        target="_blank">
                                                        {{ t('renew_support') }} </a>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex flex-col items-start md:items-end mt-4 md:mt-0">
                                            <span class="text-sm text-gray-600 dark:text-slate-400">
                                                {{ t('do_you_want_custom_service') }} </span>
                                            <a href="{{ $support['support_url'] }}" target="_blank"
                                                class="mt-2 w-auto px-4 py-2 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded-lg border border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors flex items-center space-x-2">
                                                <x-heroicon-c-arrow-left class="w-4 h-4" />
                                                <span> {{ t('create_support_ticket') }} </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Main Content -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <!-- Update Section -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700/50"
                                x-data="{ purchase_key: '', username: '', isValid: false }">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ t('version_information') }} </h2>

                                    <div class="flex flex-col sm:flex-row mb-8 gap-4">
                                        <div
                                            class="flex-1 p-4 border-l-4 rounded-r-md z-100 dark:bg-gray-700 dark:text-white {{ $currentVersion != $latestVersion ? 'bg-red-100 border-red-500 text-red-700 dark:border-red-600' : 'bg-green-100 border-green-500 text-green-700 dark:bg-gray-700 dark:border-green-600' }} ">
                                            <div
                                                class="text-sm mb-1 {{ $currentVersion != $latestVersion ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} ">
                                                {{ t('your_version') }}
                                            </div>
                                            <div
                                                class="text-2xl font-bold {{ $currentVersion != $latestVersion ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }} ">
                                                {{ $currentVersion }}
                                            </div>
                                        </div>
                                        <div class="hidden sm:flex items-center justify-center text-gray-400">
                                            <x-heroicon-c-arrow-long-right class="w-8 h-8" />
                                        </div>
                                        <div class="sm:hidden flex justify-center text-gray-400">
                                            <x-heroicon-c-arrow-long-down class="w-8 h-8" />
                                        </div>
                                        <div
                                            class="flex-1 p-4 border-l-4 rounded-r-md z-100 bg-green-100 border-green-500 text-green-700 dark:bg-gray-700 dark:border-green-600 dark:text-white">
                                            <div class="text-sm text-green-600 dark:text-green-400 mb-1">
                                                {{ t('latest_version') }} </div>
                                            <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                                                {{ $latestVersion }}
                                            </div>
                                        </div>
                                    </div>
                                    <form wire:submit.prevent="save">
                                        <div class="space-y-4" x-data>
                                            <!-- Username -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1"
                                                    for="username">
                                                    <span class="text-red-500">*</span> {{ t('username') }}
                                                </label>
                                                <input type="text" id="username" wire:model.defer="username"
                                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors"
                                                    placeholder="Enter your username" autocomplete="off">
                                                @error('username')
                                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <!-- Purchase Key -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1"
                                                    for="purchase_key">
                                                    <span class="text-red-500">*</span> {{ t('purchase_key') }}
                                                </label>
                                                <input type="text" id="purchase_key" wire:model.defer="purchase_key"
                                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors"
                                                    placeholder="Enter your purchase key" autocomplete="off">
                                                @error('purchase_key')
                                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Update Button -->
                                        @if ($currentVersion != $latestVersion)
                                            <div class="mt-8">
                                                <button type="submit"
                                                    class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-lg transition-colors flex items-center justify-center space-x-2"
                                                    wire:loading.attr="disabled">
                                                    <x-heroicon-c-arrow-down-tray class="w-5 h-5" />
                                                    <span> {{ t('download_update') }} </span>
                                                </button>
                                            </div>
                                        @endif
                                    </form>

                                    <!-- Warning Message -->
                                    <div
                                        class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-100 dark:border-amber-900/20">
                                        <div class="flex items-start space-x-3">
                                            <p class="text-amber-700 dark:text-amber-300">
                                                {{ t('before_update_description') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Changelog Section -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700/50">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ t('change_log') }}
                                    </h2>

                                    <!-- Changelog Content -->
                                    <div wire:loading.block wire:target="loadReleases"
                                        class="flex justify-center items-center py-8">
                                        <div
                                            class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-500">
                                        </div>
                                    </div>

                                    <div wire:loading.remove wire:target="loadReleases"
                                        class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                                        @if (isset($versionLog['versions']) && count($versionLog['versions']) > 0)
                                            @forelse($versionLog['versions'] as $version)
                                                <div
                                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                                    <!-- Version Header -->
                                                    <div class="flex items-center justify-between p-4 cursor-pointer {{ $version['is_latest'] ? 'bg-green-50 dark:bg-green-900/10' : 'bg-gray-50 dark:bg-gray-700/30' }}"
                                                        @click="toggleVersion('{{ $version['version'] }}')">
                                                        <div class="flex items-center space-x-2">
                                                            <span
                                                                class="{{ $version['is_latest'] ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                                </svg>
                                                            </span>
                                                            <div>
                                                                <span
                                                                    class="font-medium {{ $version['is_latest'] ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                                    {{ $version['version'] }}
                                                                    @if ($version['is_latest'])
                                                                        <span
                                                                            class="ml-2 text-xs px-2 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 rounded-full">
                                                                            {{ t('latest') }}
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                                <span
                                                                    class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $version['date'] }}</span>
                                                            </div>
                                                        </div>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 text-gray-400 transform transition-transform duration-200"
                                                            :class="{ 'rotate-180': expandedVersions[
                                                                    '{{ $version['version'] }}'] }"
                                                            viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>

                                                    <!-- Change Items -->
                                                    @php
                                                        $hasContentInFifTypes = false;
                                                        foreach ($version['changes'] as $changeCheck) {
                                                            if (
                                                                in_array($changeCheck['type'], [
                                                                    'feature',
                                                                    'improvement',
                                                                    'bug',
                                                                ]) &&
                                                                !empty($changeCheck['description'])
                                                            ) {
                                                                $hasContentInFifTypes = true;
                                                                break;
                                                            }
                                                        }
                                                    @endphp

                                                    <div x-show="expandedVersions['{{ $version['version'] }}']"
                                                        class="p-4 border-t border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">

                                                        <!-- First display feature, improvement, bug if they have content -->
                                                        @foreach ($version['changes'] as $change)
                                                            @if (in_array($change['type'], ['feature', 'improvement', 'bug']) && !empty($change['description']))
                                                                <div class="py-3 flex items-start">
                                                                    @if ($change['type'] === 'feature')
                                                                        <span class="flex-shrink-0 mr-3 mt-1">
                                                                            <span
                                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-500 dark:bg-blue-900/30 dark:text-blue-300">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                    class="h-4 w-4" fill="none"
                                                                                    viewBox="0 0 24 24"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2"
                                                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                                </svg>
                                                                            </span>
                                                                        </span>
                                                                        <div class="text-sm">
                                                                            <p
                                                                                class="font-medium text-blue-600 dark:text-blue-400">
                                                                                {{ t('new_feature') }}</p>
                                                                            <p
                                                                                class="text-gray-700 dark:text-gray-300">
                                                                                {!! $change['description'] !!}</p>
                                                                        </div>
                                                                    @elseif($change['type'] === 'improvement')
                                                                        <span class="flex-shrink-0 mr-3 mt-1">
                                                                            <span
                                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 text-purple-500 dark:bg-purple-900/30 dark:text-purple-300">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                    class="h-4 w-4" fill="none"
                                                                                    viewBox="0 0 24 24"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2"
                                                                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                                                </svg>
                                                                            </span>
                                                                        </span>
                                                                        <div class="text-sm">
                                                                            <p
                                                                                class="font-medium text-purple-600 dark:text-purple-400">
                                                                                {{ t('improvement') }}</p>
                                                                            <p
                                                                                class="text-gray-700 dark:text-gray-300">
                                                                                {!! $change['description'] !!}</p>
                                                                        </div>
                                                                    @elseif($change['type'] === 'bug')
                                                                        <span class="flex-shrink-0 mr-3 mt-1">
                                                                            <span
                                                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-300">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                    class="h-4 w-4" fill="none"
                                                                                    viewBox="0 0 24 24"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2"
                                                                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                                </svg>
                                                                            </span>
                                                                        </span>
                                                                        <div class="text-sm">
                                                                            <p
                                                                                class="font-medium text-red-600 dark:text-red-400">
                                                                                {{ t('bug_fix') }}</p>
                                                                            <p
                                                                                class="text-gray-700 dark:text-gray-300">
                                                                                {!! $change['description'] !!}</p>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach

                                                        <!-- Only show changelog if none of the FIF types have content -->
                                                        @if (!$hasContentInFifTypes)
                                                            @foreach ($version['changes'] as $change)
                                                                @if ($change['type'] === 'changelog')
                                                                    <div class="py-3 flex items-start">
                                                                        <div class="text-sm">
                                                                            <p
                                                                                class="text-gray-700 dark:text-gray-300">
                                                                                {!! $change['description'] !!}</p>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="p-4 bg-gray-50 dark:bg-slate-700/30 rounded-lg text-center">
                                                <p class="text-gray-500 dark:text-gray-400">
                                                    {{ t('no_release_information_available') }}</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:content>
            </x-card>
        </div>
    </div>
</div>
