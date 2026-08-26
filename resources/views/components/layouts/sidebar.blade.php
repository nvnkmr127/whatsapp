<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false"
    class="fixed inset-0 z-50 transition-opacity bg-zinc-950/90 backdrop-blur-sm lg:hidden"></div>

<div x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="w-72 flex-shrink-0 transition-transform duration-300 ease-out fixed inset-y-0 left-0 z-50 lg:static lg:translate-x-0 bg-zinc-950 text-zinc-100 border-r border-zinc-800 flex flex-col shadow-2xl lg:shadow-none"
    x-data="{ 
        expandedGroups: [],
        toggleGroup(group) {
            if (this.expandedGroups.includes(group)) {
                this.expandedGroups = this.expandedGroups.filter(g => g !== group);
            } else {
                this.expandedGroups.push(group);
            }
        },
        isExpanded(group) {
            return this.expandedGroups.includes(group);
        }
    }"
    x-init="
        @foreach($menuGroups as $groupLabel => $links)
            @foreach($links as $link)
                @if(isset($link['children']) && collect($link['children'])->pluck('route')->contains(request()->route()->getName()))
                    expandedGroups.push('{{ $link['label'] }}');
                @endif
            @endforeach
        @endforeach
    ">

    <!-- Workspace Branding (Fixed Top) -->
    <div class="flex-shrink-0 relative px-8 pt-10 pb-8 bg-zinc-950">
        <div class="flex items-center gap-5 group cursor-pointer">
            <div class="relative">
                <div class="absolute -inset-2 bg-gradient-to-tr from-orange-500/40 to-orange-400/40 rounded-2xl blur-lg opacity-20 group-hover:opacity-60 transition duration-500"></div>
                <div class="relative flex items-center justify-center w-14 h-14 bg-zinc-950 border border-zinc-800/60 rounded-2xl shadow-2xl ring-1 ring-white/5 group-hover:scale-105 transition-transform duration-300 overflow-hidden">
                    @if(Auth::user()->currentTeam && Auth::user()->currentTeam->logo_path)
                        @php
                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                            $disk = \Illuminate\Support\Facades\Storage::disk('r2');
                        @endphp
                        <img src="{{ $disk->url(Auth::user()->currentTeam->logo_path) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-8 h-8 flex items-center justify-center bg-orange-500/10 rounded-lg">
                            <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex flex-col text-left">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-orange-500 leading-none mb-1.5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    Workspace
                </span>
                <span class="text-lg font-black tracking-tight text-white uppercase group-hover:text-orange-400 transition-colors duration-300 truncate max-w-[120px]">{{ Auth::user()->currentTeam->name ?? __('No Team') }}</span>
            </div>
        </div>
    </div>

    <!-- Scrollable Navigation Area -->
    <div class="flex-1 overflow-y-auto scrollbar-hide px-6">
        <nav class="mt-2 space-y-8 pb-10">
            @foreach($menuGroups as $groupLabel => $links)
                <div>
                    <!-- Group Header -->
                    <div class="px-4 mb-4 flex items-center gap-3">
                        <span class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.4em] flex-shrink-0">{{ $groupLabel }}</span>
                        <div class="h-px flex-1 bg-gradient-to-r from-zinc-700 via-zinc-700/20 to-transparent"></div>
                    </div>
                    
                    <!-- Links -->
                    <div class="space-y-1">
                        @foreach($links as $link)
                            @php
                                $canAccess = (!isset($link['can']) || auth()->user()->can($link['can'])) &&
                                             (!isset($link['plan_feature']) || auth()->user()->hasPlanFeature($link['plan_feature']));
                            @endphp
                            @if($canAccess)
                                @php 
                                    $hasChildren = isset($link['children']) && count($link['children']) > 0;
                                    $isRouteActive = request()->routeIs($link['route']);
                                    $anyChildActive = $hasChildren && collect($link['children'])->pluck('route')->contains(request()->route()->getName());
                                    $isActive = $isRouteActive || $anyChildActive;
                                @endphp

                                <div class="relative" @isset($link['tour']) data-tour="{{ $link['tour'] }}" @endisset>
                                    <!-- Parent Link / Expandable -->
                                    @if($hasChildren)
                                        <button @click="toggleGroup('{{ $link['label'] }}')"
                                            class="w-full group flex items-center justify-between px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ $isActive ? 'bg-zinc-800/60 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}">
                                            
                                            <div class="flex items-center">
                                                <svg class="mr-4 h-5 w-5 transition-transform duration-200 {{ $isActive ? 'text-orange-400' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $link['icon'] }}" />
                                                </svg>
                                                <span class="tracking-wide text-left">{{ $link['label'] }}</span>
                                            </div>

                                            <svg class="h-4 w-4 transition-transform duration-300 text-zinc-600" :class="isExpanded('{{ $link['label'] }}') ? 'rotate-180' : ''"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="isExpanded('{{ $link['label'] }}')" 
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 -translate-y-2"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="mt-1 ml-4 pl-4 border-l border-zinc-800 space-y-1">
                                            @foreach($link['children'] as $child)
                                                @php
                                                    $canAccessChild = (!isset($child['can']) || auth()->user()->can($child['can'])) &&
                                                                      (!isset($child['plan_feature']) || auth()->user()->hasPlanFeature($child['plan_feature']));
                                                @endphp
                                                @if($canAccessChild)
                                                    <a wire:navigate href="{{ route($child['route']) }}"
                                                        class="block px-4 py-2 text-[12px] font-bold tracking-wide transition-colors duration-200 {{ request()->routeIs($child['route']) ? 'text-orange-400 font-black' : 'text-zinc-500 hover:text-white' }}">
                                                        {{ $child['label'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <a wire:navigate href="{{ route($link['route']) }}"
                                            class="group flex items-center px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ $isActive ? 'bg-zinc-800/80 text-white shadow-lg border border-orange-500/20 border-l-2 border-l-orange-500' : 'text-zinc-400 hover:text-white hover:bg-zinc-900 border border-transparent' }}">

                                            <svg class="mr-4 h-5 w-5 transition-transform duration-200 {{ $isActive ? 'text-orange-500' : 'text-zinc-500 group-hover:text-zinc-300 group-hover:scale-110' }}"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $link['icon'] }}" />
                                            </svg>
                                            <span class="tracking-wide text-left">{{ $link['label'] }}</span>

                                            @if($isActive)
                                                <div class="absolute right-3 w-1.5 h-1.5 bg-orange-500 rounded-full shadow-[0_0_8px_rgba(249,115,22,0.7)]"></div>
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            <!-- Super Admin Section -->
            @if(auth()->user()->isSuperAdmin())
                <div class="pt-4 border-t border-zinc-800">
                    <div class="px-4 mb-4 flex items-center gap-3">
                        <span class="text-[10px] font-black text-rose-500 uppercase tracking-[0.4em] flex-shrink-0">Nexus Admin</span>
                    </div>
                    <div class="space-y-1 p-2 bg-rose-500/5 border border-rose-500/10 rounded-2xl">
                        @foreach($adminLinks as $link)
                            @php $isActive = request()->routeIs($link['route']); @endphp
                            <a wire:navigate href="{{ route($link['route']) }}"
                                class="group relative flex items-center px-4 py-3 text-xs font-black uppercase tracking-wider rounded-xl transition-all duration-200 {{ $isActive ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30' : 'text-rose-400/60 hover:text-rose-400 hover:bg-rose-500/10' }}">
                                <svg class="mr-3 h-4 w-4 transition-transform duration-200 {{ $isActive ? '' : 'group-hover:scale-110' }}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $link['icon'] }}" />
                                </svg>
                                <span class="text-left">{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </nav>
    </div>

    <!-- Profile Pane (Fixed Bottom) -->
    <div class="flex-shrink-0 p-4 bg-zinc-950 border-t border-zinc-800 border-zinc-800/80 shadow-[0_-10px_30px_-5px_rgba(0,0,0,0.5)]">
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" aria-label="{{ __('Open account menu') }}"
                class="w-full flex items-center gap-4 p-3 bg-zinc-900/50 backdrop-blur-md border border-zinc-800/80 rounded-[2rem] shadow-xl hover:bg-zinc-800/80 hover:border-white/10 transition-all group">
                
                <div class="relative flex-shrink-0">
                    <img class="h-10 w-10 rounded-full object-cover ring-2 ring-zinc-800 group-hover:ring-orange-500/50 transition-all"
                        src="{{ Auth::user()->profile_photo_url }}" alt="">
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-orange-500 border-2 border-zinc-950 rounded-full"></div>
                </div>

                <div class="flex-1 text-left min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="text-xs font-black text-white truncate tracking-tight uppercase">{{ Auth::user()->name }}</div>
                        @if(Auth::user()->isSuperAdmin())
                            <span class="px-1.5 py-0.5 bg-rose-500/10 text-rose-500 text-[7px] font-black uppercase tracking-widest border border-rose-500/20 rounded-md">Nexus Root</span>
                        @elseif(Auth::user()->currentTeam && Auth::user()->ownsTeam(Auth::user()->currentTeam))
                            <span class="px-1.5 py-0.5 bg-orange-500/10 text-orange-400 text-[7px] font-black uppercase tracking-widest border border-orange-500/20 rounded-md">Workspace Admin</span>
                        @else
                            <span class="px-1.5 py-0.5 bg-zinc-500/10 text-zinc-400 text-[7px] font-black uppercase tracking-widest border border-zinc-500/20 rounded-md">Collaborator</span>
                        @endif
                    </div>
                    <div class="text-[9px] font-bold text-zinc-500 truncate uppercase tracking-wider group-hover:text-orange-400 transition-colors">
                        {{ Auth::user()->currentTeam->name ?? __('No Team') }}
                    </div>
                </div>

                <div class="text-zinc-500 group-hover:text-white transition-colors">
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
                class="absolute bottom-full left-0 right-0 mb-3 bg-zinc-900 border border-zinc-800 rounded-2xl shadow-2xl overflow-hidden z-[60]">
                
                <div class="p-1 space-y-1">
                    <a wire:navigate href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Profile Settings
                    </a>
                    
                    @can('manage-settings')
                    <a wire:navigate href="{{ route('settings.hub') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                        Settings Hub
                    </a>
                    @endcan

                    <button type="button" data-tour="tour-help" onclick="window.startProductTour && window.startProductTour()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Take a Product Tour
                    </button>

                    <div class="h-px bg-zinc-800 my-1"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-500 hover:bg-rose-500/10 transition-colors text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>