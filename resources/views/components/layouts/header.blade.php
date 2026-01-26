@props(['header' => null])
<header x-data="{ 
        isSearchFocused: false,
        toggleTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
    }"
    class="flex items-center justify-between px-8 py-4 bg-white/40 dark:bg-slate-950/40 backdrop-blur-3xl border-b border-slate-200/50 dark:border-slate-800/50 sticky top-0 z-40 transition-all duration-500">

    <!-- Subtle Gradient Accent Line at top -->
    <div
        class="absolute top-0 left-0 right-0 h-[1.5px] bg-gradient-to-r from-transparent via-wa-primary/40 to-transparent opacity-60">
    </div>
    <!-- Bottom Glow Shadow -->
    <div class="absolute bottom-0 left-0 right-0 h-px bg-slate-200/20 dark:bg-white/5"></div>

    <div class="flex items-center gap-6 flex-1">
        <button @click="sidebarOpen = true"
            class="p-2 -ml-2 text-slate-500 hover:text-wa-primary focus:outline-none lg:hidden transition-all duration-300 hover:scale-110 active:scale-90">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
        </button>

        <!-- Page Heading -->
        <div
            class="hidden lg:flex items-center gap-4 min-w-fit pr-4 border-r border-slate-200/60 dark:border-slate-800/60">
            <h1 class="text-[15px] font-black text-slate-900 dark:text-white uppercase tracking-[0.15em] leading-none">
                {{ $header ?? __('Dashboard') }}
            </h1>
        </div>

        <!-- Global Search -->
        <livewire:global-search />
    </div>

    <div class="flex items-center gap-5">
        <!-- Agent Call Availability -->
        <div class="hidden sm:block">
            <livewire:auth.agent-availability-toggle />
        </div>

        <div class="h-6 w-px bg-slate-200/60 dark:bg-slate-800/60 mx-1"></div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-2">
            <!-- Theme Toggle -->
            <button @click="toggleTheme()"
                class="p-2.5 rounded-2xl text-slate-400 hover:text-amber-500 hover:bg-white dark:hover:bg-slate-900 shadow-none hover:shadow-lg transition-all duration-300 focus:outline-none group">
                <svg class="w-5 h-5 hidden dark:block group-hover:rotate-45 transition-transform duration-500"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="w-5 h-5 block dark:hidden group-hover:-rotate-12 transition-transform duration-500"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            <!-- Notifications -->
            <livewire:notification-dropdown />
        </div>

        <!-- Teams Dropdown -->
        @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
            <div class="relative">
                <x-dropdown align="right" width="60"
                    dropdownClasses="rounded-2xl shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-800">
                    <x-slot name="trigger">
                        <button type="button"
                            class="group inline-flex items-center gap-2.5 px-4 py-2 border-none text-[11px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-slate-100/50 dark:bg-white/5 hover:bg-white dark:hover:bg-slate-900 rounded-[1rem] transition-all duration-300 shadow-sm border border-transparent hover:border-slate-200/50 dark:hover:border-white/10">
                            {{ Auth::user()->currentTeam->name }}
                            <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-wa-primary transition-all duration-300 group-hover:rotate-180"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <div
                                class="block px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-800">
                                {{ __('Workspace Context') }}
                            </div>

                            <div class="p-1">
                                <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}"
                                    class="rounded-xl flex items-center gap-3">
                                    <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                    {{ __('Team Settings') }}
                                </x-dropdown-link>

                                @can('manage-settings')
                                    <x-dropdown-link href="{{ route('teams.whatsapp_config') }}"
                                        class="rounded-xl flex items-center gap-3">
                                        <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        {{ __('WhatsApp API') }}
                                    </x-dropdown-link>
                                @endcan

                                @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                    <x-dropdown-link href="{{ route('teams.create') }}"
                                        class="rounded-xl flex items-center gap-3">
                                        <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        {{ __('Create New Team') }}
                                    </x-dropdown-link>
                                @endcan
                            </div>

                            @if (Auth::user()->allTeams()->count() > 1)
                                <div class="border-t border-slate-50 dark:border-slate-800 my-1"></div>
                                <div class="block px-5 py-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                    {{ __('Switch Workspace') }}
                                </div>
                                <div class="p-1">
                                    @foreach (Auth::user()->allTeams() as $team)
                                        <x-switchable-team :team="$team" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        @endif

        <!-- User Profile Dropdown -->
        <div class="relative">
            <x-dropdown align="right" width="48"
                dropdownClasses="rounded-2xl shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-800">
                <x-slot name="trigger">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <button
                            class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-wa-primary/40 transition-all duration-300 shadow-lg shadow-black/10 hover:scale-110 active:scale-95">
                            <img class="h-10 w-10 rounded-full object-cover ring-2 ring-white/50 dark:ring-slate-800/50"
                                src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </button>
                    @else
                        <button type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-2xl text-slate-500 dark:text-slate-400 bg-white/50 dark:bg-slate-900/50 hover:text-slate-700 dark:hover:text-white transition-all duration-300">
                            {{ Auth::user()->name }}
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    @endif
                </x-slot>

                <x-slot name="content">
                    <div
                        class="block px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 dark:border-slate-800">
                        {{ __('Account Control') }}
                    </div>

                    <div class="p-1">
                        <x-dropdown-link href="{{ route('profile.show') }}" class="rounded-xl flex items-center gap-3">
                            <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                            <x-dropdown-link href="{{ route('api-tokens.index') }}"
                                class="rounded-xl flex items-center gap-3">
                                <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                {{ __('API Tokens') }}
                            </x-dropdown-link>
                        @endif

                        <div class="border-t border-slate-50 dark:border-slate-800 my-1"></div>

                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();"
                                class="rounded-xl text-rose-500 hover:text-rose-600 font-bold flex items-center gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </div>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>