<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false"
    class="fixed inset-0 z-50 transition-opacity bg-slate-950/90 backdrop-blur-sm lg:hidden"></div>

<div x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto transition-transform duration-300 ease-out bg-slate-950 text-slate-100 lg:translate-x-0 lg:static lg:inset-0 border-r border-slate-900 shadow-[20px_0_40px_-10px_rgba(0,0,0,0.3)] scrollbar-hide">

    <!-- Workspace Branding -->
    <div class="relative px-8 pt-10 pb-8">
        <div class="flex items-center gap-5 group cursor-pointer">
            <div class="relative">
                <div
                    class="absolute -inset-2 bg-gradient-to-tr from-wa-green/40 to-wa-teal/40 rounded-2xl blur-lg opacity-40 group-hover:opacity-70 transition duration-500">
                </div>
                <div
                    class="relative flex items-center justify-center w-14 h-14 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl shadow-black/50 ring-1 ring-white/5 group-hover:scale-105 transition-transform duration-300">
                    <x-application-mark class="w-8 h-8" />
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-wa-green/80 leading-none mb-1.5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    Workspace
                </span>
                <span class="text-xl font-black tracking-tight text-white uppercase group-hover:text-wa-green transition-colors duration-300">{{ config('app.name') }}</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-2 px-6 space-y-8">
        @foreach($menuGroups as $groupLabel => $links)
            <div>
                <!-- Group Header -->
                <div class="px-4 mb-3 flex items-center gap-3">
                    <div class="h-px flex-1 bg-gradient-to-r from-slate-800/0 via-slate-800 to-slate-800/0"></div>
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] flex-shrink-0">{{ $groupLabel }}</span>
                    <div class="h-px flex-1 bg-gradient-to-r from-slate-800/0 via-slate-800 to-slate-800/0"></div>
                </div>
                
                <!-- Links -->
                <div class="space-y-1.5">
                    @foreach($links as $link)
                        @if(!isset($link['can']) || auth()->user()->can($link['can']))
                            @php 
                                $isActive = request()->routeIs($link['route']); 
                                $isSub = $link['is_sub'] ?? false;
                            @endphp
                            <a href="{{ route($link['route']) }}"
                                class="group relative flex items-center {{ $isSub ? 'pl-9 pr-4 py-2.5 text-[11px] uppercase tracking-wider font-extrabold text-slate-500 hover:text-white' : 'px-4 py-3.5 text-sm font-bold' }} rounded-xl transition-all duration-200 {{ (!$isSub && $isActive) ? 'bg-slate-800/80 text-white shadow-lg border border-white/5' : '' }}">
                                
                                @if(!$isSub)
                                <svg class="mr-4 h-5 w-5 transition-transform duration-200 {{ $isActive ? 'text-wa-teal' : 'text-slate-500 group-hover:text-slate-300 group-hover:scale-110' }}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="{{ $link['icon'] }}" />
                                </svg>
                                @elseif($isSub)
                                    <div class="absolute left-5 w-1.5 h-1.5 rounded-full border border-slate-950 {{ $isActive ? 'bg-wa-green' : 'bg-slate-700 group-hover:bg-slate-500' }}"></div>
                                @endif

                                <span class="tracking-wide {{ $isSub && $isActive ? 'text-white' : '' }}">{{ $link['label'] }}</span>
                                
                                @if(!$isSub && $isActive)
                                    <div class="absolute right-3 w-1.5 h-1.5 bg-wa-green rounded-full animate-pulse"></div>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Super Admin Section (Separated) -->
        @if(auth()->user()->is_super_admin)
            <div>
                 <div class="px-4 mb-3 flex items-center gap-3">
                    <div class="h-px flex-1 bg-gradient-to-r from-slate-800/0 via-slate-800 to-slate-800/0"></div>
                    <span class="text-[9px] font-black text-rose-500 uppercase tracking-[0.3em] flex-shrink-0">Super Admin</span>
                    <div class="h-px flex-1 bg-gradient-to-r from-slate-800/0 via-slate-800 to-slate-800/0"></div>
                </div>
                <div class="space-y-1.5 p-2 bg-rose-500/5 border border-rose-500/10 rounded-2xl">
                    @php
                        $adminLinks = [
                            [
                                'route' => 'admin.dashboard',
                                'label' => 'Dashboard',
                                'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
                            ],
                            [
                                'route' => 'admin.tenants.create',
                                'label' => 'Add Tenant',
                                'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'
                            ],
                            [
                                'route' => 'admin.plans',
                                'label' => 'Plans',
                                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                            ],
                        ];
                    @endphp
                    
                    @foreach($adminLinks as $link)
                        @php $isActive = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                            class="group relative flex items-center px-4 py-3 text-xs font-black uppercase tracking-wider rounded-xl transition-all duration-200 {{ $isActive ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30' : 'text-rose-400/60 hover:text-rose-400 hover:bg-rose-500/10' }}">
                            <svg class="mr-3 h-4 w-4 transition-transform duration-200 {{ $isActive ? '' : 'group-hover:scale-110' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="{{ $link['icon'] }}" />
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Spacer for bottom elements -->
        <div class="h-24"></div>
    </nav>

    <!-- Neural Link: Profile Pane -->
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-slate-950 via-slate-950 to-transparent">
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" 
                class="w-full flex items-center gap-4 p-3 bg-slate-900/50 backdrop-blur-md border border-white/5 rounded-[2rem] shadow-xl hover:bg-slate-800/80 hover:border-white/10 transition-all group">
                
                <div class="relative flex-shrink-0">
                    <img class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-800 group-hover:ring-wa-green/50 transition-all"
                        src="{{ Auth::user()->profile_photo_url }}" alt="">
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-wa-green border-2 border-slate-900 rounded-full"></div>
                </div>

                <div class="flex-1 text-left min-w-0">
                    <div class="text-xs font-black text-white truncate tracking-tight uppercase">{{ Auth::user()->name }}</div>
                    <div class="text-[9px] font-bold text-slate-500 truncate uppercase tracking-wider group-hover:text-wa-green transition-colors">
                        {{ Auth::user()->currentTeam->name }}
                    </div>
                </div>

                <div class="text-slate-500 group-hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </div>
            </button>

            <!-- Menu Dropdown (Upwards) -->
            <div x-show="open" @click.away="open = false" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="absolute bottom-full left-0 right-0 mb-3 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50">
                
                <div class="p-1 space-y-1">
                    <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Profile & Account
                    </a>
                    
                    @can('manage-settings')
                    <a href="{{ route('settings.system') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                        System Settings
                    </a>
                    @endcan

                    <div class="h-px bg-slate-800 my-1"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-500 hover:bg-rose-500/10 transition-colors text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>