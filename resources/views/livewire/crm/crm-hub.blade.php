<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Unified <span class="text-wa-teal">CRM Hub</span></h2>
                <p class="text-slate-500 font-bold uppercase tracking-[0.2em] text-[10px]">Super Admin Console • v3.0</p>
            </div>
            
            {{-- Navigation Tabs --}}
            <div class="flex p-1 bg-white border border-slate-200 rounded-[1.5rem] shadow-sm">
                @foreach([
                    'dashboard' => ['label' => 'Overview', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    'contacts' => ['label' => 'Contacts', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    'deals' => ['label' => 'Deals', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'onboarding' => ['label' => 'Onboarding', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    'companies' => ['label' => 'Companies', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4']
                ] as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" 
                        class="flex items-center gap-2 px-6 py-3 rounded-[1.2rem] text-[10px] font-black uppercase tracking-widest transition-all duration-300 {{ $activeTab === $key ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 {{ $activeTab === $key ? 'text-wa-teal' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path></svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Content Area --}}
        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            @if ($activeTab === 'dashboard')
                {{-- Dashboard View --}}
                <div class="-mx-4 sm:-mx-6 lg:-mx-8">
                    {{-- We reset the padding/container inside the component since Admin\Crm has its own wrapper --}}
                    {{-- However, Admin\Crm has its own padding which might double up. We might need to adjust Admin\Crm later if it looks bad. --}}
                    {{-- For now, we rely on the component to render its content. --}}
                    <livewire:admin.crm />
                </div>
            @elseif ($activeTab === 'contacts')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden p-1">
                    <livewire:contacts.contact-manager />
                </div>
            @elseif ($activeTab === 'deals')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden h-[calc(100vh-12rem)]">
                    <livewire:deals.deal-manager />
                </div>
            @elseif ($activeTab === 'onboarding')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden p-8">
                    <livewire:crm.onboarding-manager />
                </div>
            @elseif ($activeTab === 'companies')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] p-12 text-center shadow-sm">
                    <div class="inline-flex items-center justify-center h-24 w-24 rounded-[2rem] bg-slate-50 border border-slate-100 mb-6">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight mb-2">Company Management</h3>
                    <p class="text-slate-500 font-bold uppercase tracking-widest text-xs mb-8">B2B Features Coming Soon</p>
                    <button class="px-8 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-wa-teal hover:shadow-lg hover:shadow-wa-teal/20 transition-all">
                        Notify When Ready
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
