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
        <div class="relative w-full max-w-md hidden md:block group px-2">
            <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-wa-primary transition-all duration-500 group-focus-within:scale-110"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text"
                class="block w-full pl-12 pr-12 py-2.5 bg-slate-100/40 dark:bg-slate-900/40 border border-transparent rounded-[1.2rem] text-[13px] font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400/80 focus:outline-none focus:ring-2 focus:ring-wa-primary/10 focus:bg-white/80 dark:focus:bg-slate-900/80 focus:border-wa-primary/20 transition-all duration-500 shadow-sm"
                placeholder="Search..." @focus="isSearchFocused = true" @blur="isSearchFocused = false">
            <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none">
                <span
                    class="text-[9px] font-black text-slate-400 border border-slate-100 dark:border-slate-800 rounded-lg px-2 py-1 bg-white dark:bg-slate-800 shadow-sm transition-opacity duration-300"
                    :class="isSearchFocused ? 'opacity-0' : 'opacity-100'">⌘K</span>
            </div>
        </div>
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
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="relative p-2.5 rounded-2xl text-slate-400 hover:text-wa-primary hover:bg-white dark:hover:bg-slate-900 shadow-none hover:shadow-lg transition-all duration-300 focus:outline-none group">
                    <span
                        class="absolute top-2.5 right-2.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white dark:ring-slate-950 animate-pulse"></span>
                    <svg class="w-5 h-5 group-hover:animate-swing" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    class="absolute right-0 mt-3 w-80 bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden z-50">
                    <div class="p-5 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Notifications</h3>
                        <span
                            class="px-2 py-0.5 bg-rose-500/10 text-rose-500 text-[8px] font-black uppercase tracking-widest rounded-md">2
                            New</span>
                    </div>
                    <div class="max-h-[300px] overflow-y-auto">
                        <div
                            class="p-4 flex items-start gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group/notif">
                            <div
                                class="h-10 w-10 rounded-full bg-wa-primary/10 flex items-center justify-center flex-shrink-0 text-wa-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">New Campaign Approved</p>
                                <p class="text-[10px] text-slate-500 mt-1">Your 'Winter Sale' campaign has been approved by
                                    Meta.</p>
                                <p class="text-[9px] text-slate-400 mt-2 font-black uppercase">2 mins ago</p>
                            </div>
                        </div>
                        <div
                            class="p-4 flex items-start gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group/notif">
                            <div
                                class="h-10 w-10 rounded-full bg-rose-500/10 flex items-center justify-center flex-shrink-0 text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">API Connection Lost</p>
                                <p class="text-[10px] text-slate-500 mt-1">Workspace 'Main' lost connection to WhatsApp API.
                                </p>
                                <p class="text-[9px] text-slate-400 mt-2 font-black uppercase">1 hour ago</p>
                            </div>
                        </div>
                    </div>
                    <a href="#"
                        class="block p-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-wa-primary hover:bg-slate-50 dark:hover:bg-slate-800 transition-all border-t border-slate-50 dark:border-slate-800">
                        View All Activity
                    </a>
                </div>
            </div>
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