<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Unified <span class="text-wa-teal">CRM Hub</span></h2>
                <p class="text-slate-500 font-bold uppercase tracking-[0.2em] text-[10px]">Super Admin Console • v3.0</p>
            </div>
            
            {{-- Navigation Tabs --}}
            <div class="flex p-1 bg-white border border-slate-200 rounded-[1.5rem] shadow-sm overflow-x-auto no-scrollbar">
                @foreach([
                    'dashboard' => ['label' => 'Overview', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    'contacts' => ['label' => 'Contacts', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    'deals' => ['label' => 'Deals', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'companies' => ['label' => 'Companies', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    'segments' => ['label' => 'Segments', 'icon' => 'M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z'],
                    'analytics' => ['label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    'import' => ['label' => 'Import', 'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'],
                ] as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" 
                        class="flex shrink-0 items-center gap-2 px-6 py-3 rounded-[1.2rem] text-[10px] font-black uppercase tracking-widest transition-all duration-300 {{ $activeTab === $key ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-4 h-4 {{ $activeTab === $key ? 'text-wa-teal' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path></svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Content Area --}}
        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            @if ($activeTab === 'dashboard')
                <livewire:crm.crm-dashboard />
            @elseif ($activeTab === 'contacts')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden p-1">
                    <livewire:contacts.contact-manager />
                </div>
            @elseif ($activeTab === 'deals')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden h-[calc(100vh-12rem)]">
                    <livewire:deals.deal-manager />
                </div>
            @elseif ($activeTab === 'companies')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden p-1">
                    <livewire:crm.company-manager />
                </div>
            @elseif ($activeTab === 'segments')
                <livewire:crm.segment-manager />
            @elseif ($activeTab === 'analytics')
                <livewire:analytics.analytics-dashboard />
            @elseif ($activeTab === 'import')
                <div class="bg-white border border-slate-200 rounded-[2.5rem] shadow-sm overflow-hidden p-1">
                    @livewire('contacts.import-manager')
                </div>
            @endif
        </div>
    </div>
</div>
