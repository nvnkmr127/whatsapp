<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false"
    class="fixed inset-0 z-50 transition-opacity bg-slate-950/80 backdrop-blur-sm lg:hidden"></div>

<div x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto transition-transform duration-300 ease-in-out bg-slate-950 text-slate-100 lg:translate-x-0 lg:static lg:inset-0 border-r border-slate-900 shadow-2xl">

    <!-- Workspace Branding -->
    <div class="flex items-center px-8 py-10">
        <div class="flex items-center gap-4 group cursor-pointer">
            <div class="relative">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-wa-green to-wa-teal rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200">
                </div>
                <div
                    class="relative flex items-center justify-center w-12 h-12 bg-slate-900 border border-slate-800 rounded-xl shadow-lg ring-1 ring-white/10">
                    <x-application-mark class="w-7 h-7" />
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-wa-green/60 leading-none mb-1">HQ
                    OS</span>
                <span class="text-xl font-black tracking-tighter text-white uppercase">{{ config('app.name') }}</span>
            </div>
        </div>
    </div>

    <nav class="mt-2 px-4 space-y-8">
        <!-- Main Core -->
        <div>
            <div class="px-4 mb-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.25em]">

            </div>
            <div class="space-y-1">
                @foreach($mainLinks as $link)
                    @if(!isset($link['can']) || auth()->user()->can($link['can']))
                        @php $isActive = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                            class="group relative flex items-center px-4 py-3.5 text-sm font-bold rounded-2xl transition-all duration-300 {{ $isActive ? 'bg-wa-green/10 text-wa-green shadow-[0_0_20px_rgba(16,185,129,0.05)]' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
                            @if($isActive)
                                <div class="absolute left-0 w-1 h-6 bg-wa-green rounded-r-full"></div>
                            @endif
                            <svg class="mr-4 h-5 w-5 transition-transform duration-300 {{ $isActive ? 'text-wa-green animate-pulse' : 'text-slate-500 group-hover:scale-110 group-hover:text-slate-300' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="{{ $link['icon'] }}" />
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- System Governance -->
        @can('manage-settings')
            <div>
                <div class="px-4 mb-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.25em]">
                    WhatsApp
                </div>
                <div class="space-y-1">
                    @foreach($systemLinks as $link)
                        @php $isActive = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                            class="group relative flex items-center px-4 py-3.5 text-sm font-bold rounded-2xl transition-all duration-300 {{ $isActive ? 'bg-slate-800 text-white shadow-xl ring-1 ring-white/10' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
                            @if($isActive)
                                <div class="absolute left-0 w-1 h-6 bg-white rounded-r-full"></div>
                            @endif
                            <svg class="mr-4 h-5 w-5 transition-transform duration-300 {{ $isActive ? 'text-white' : 'text-slate-500 group-hover:scale-110 group-hover:text-slate-300' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="{{ $link['icon'] }}" />
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endcan

        <!-- Super Admin -->
        @if(auth()->user()->is_super_admin)
            <div>
                <div class="px-4 mb-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.25em]">
                    Super Admin
                </div>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                        class="group relative flex items-center px-4 py-3.5 text-sm font-bold rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.*') ? 'bg-red-900/10 text-red-500 shadow-[0_0_20px_rgba(239,68,68,0.05)]' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
                        @if(request()->routeIs('admin.*'))
                            <div class="absolute left-0 w-1 h-6 bg-red-500 rounded-r-full"></div>
                        @endif
                        <svg class="mr-4 h-5 w-5 transition-transform duration-300 {{ request()->routeIs('admin.*') ? 'text-red-500 animate-pulse' : 'text-slate-500 group-hover:scale-110 group-hover:text-slate-300' }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        God Mode
                    </a>
                </div>
            </div>
        @endif
    </nav>

    <!-- Neural Link: Profile Pane -->
    <div class="sticky bottom-0 w-full p-6 bg-gradient-to-t from-slate-950 via-slate-950 to-transparent">
        <div
            class="relative group p-4 bg-slate-900/40 backdrop-blur-xl border border-white/5 rounded-[2rem] shadow-2xl transition-all hover:bg-slate-900/60 active:scale-95 cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <img class="h-10 w-10 rounded-2xl object-cover ring-2 ring-slate-800 transition-all group-hover:ring-wa-green/50"
                        src="{{ Auth::user()->profile_photo_url }}" alt="">
                    <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-wa-green border-2 border-slate-900 rounded-full">
                    </div>
                </div>
                <div class="flex flex-col min-w-0">
                    <span
                        class="text-xs font-black text-white truncate tracking-tight uppercase leading-none mb-1">{{ Auth::user()->name }}</span>
                    <div class="flex items-center gap-1">
                        <span
                            class="text-[9px] font-bold text-wa-green uppercase tracking-widest">{{ Auth::user()->currentTeam->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Action: Settings -->
            <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                </svg>
            </div>
        </div>
    </div>
</div>