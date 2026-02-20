<div class="mb-12" x-data="{ celebration: false }" @onboarding-milestone-completed.window="celebration = true; if(window.confetti) confetti(); setTimeout(() => celebration = false, 3000)">
    @if($showChecklist)
        {{-- Inject Confetti --}}
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="p-8 md:p-12">
                {{-- Stepper Header --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="h-10 w-10 bg-indigo-50 rounded-2xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            Activation <span class="text-wa-teal">Blueprint</span>
                        </h3>
                        <p class="text-slate-400 font-bold uppercase tracking-[0.2em] text-[10px] mt-2">Step-by-step guide to your first broadcast</p>
                    </div>

                    <div class="flex items-center gap-4">
                        @if(auth()->user()->currentTeam->whatsapp_connected)
                            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-100 rounded-2xl">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Active Pulse</span>
                            </div>
                        @endif

                        <button wire:click="diagnoseConnection" wire:loading.attr="disabled" class="px-6 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-[10px] font-black text-slate-600 uppercase tracking-widest hover:border-indigo-300 hover:bg-white transition-all flex items-center gap-2 group">
                            <svg wire:loading.class="animate-spin" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span wire:loading.remove>Run Diagnostics</span>
                            <span wire:loading>Analysing...</span>
                        </button>

                        <button wire:click="toggleChecklist" class="p-3 text-slate-300 hover:text-slate-900 transition-colors">
                            <svg class="w-5 h-5 {{ $showChecklist ? '' : 'rotate-180' }} transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>

                {{-- The Stepper Visual --}}
                <div class="relative">
                    {{-- Progress Line --}}
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-100 -translate-y-1/2 hidden md:block"></div>
                    @php
                        $completedCount = collect($steps)->where('completed', true)->count();
                        $percent = ($completedCount / count($steps)) * 100;
                    @endphp
                    <div class="absolute top-1/2 left-0 h-0.5 bg-wa-teal -translate-y-1/2 transition-all duration-1000 hidden md:block" style="width: {{ $percent }}%"></div>

                    <div class="relative grid grid-cols-1 md:grid-cols-4 gap-8">
                        @foreach($steps as $index => $step)
                            @php
                                $isCurrent = !$step['completed'] && ($index === 0 || $steps[$index-1]['completed']);
                                $isActive = $step['completed'] || $isCurrent;
                            @endphp
                            <div class="flex md:flex-col items-center gap-4 md:text-center group">
                                <div class="relative">
                                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center transition-all duration-500 z-10 relative
                                        {{ $step['completed'] ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : ($isCurrent ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/20 scale-110 animate-bounce' : 'bg-white border-2 border-slate-100 text-slate-300') }}">
                                        
                                        @if($step['completed'])
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            @if($step['icon'] === 'wa')
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            @elseif($step['icon'] === 'waba')
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            @elseif($step['icon'] === 'phone')
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            @else
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            @endif
                                        @endif
                                    </div>
                                    @if($isCurrent)
                                        <div class="absolute inset-0 bg-indigo-600 rounded-2xl animate-ping opacity-20"></div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-xs font-black uppercase tracking-widest {{ $isActive ? 'text-slate-900' : 'text-slate-300' }} transition-colors">{{ $step['title'] }}</h4>
                                    <p class="text-[10px] font-bold text-slate-400 mt-1 line-clamp-1 md:line-clamp-2">{{ $step['description'] }}</p>
                                    
                                    @if($isCurrent)
                                        <a href="{{ $step['link'] }}" class="inline-flex mt-3 text-[9px] font-black text-indigo-600 uppercase tracking-widest bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all">
                                            Complete Step
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="mt-16 flex flex-col md:flex-row items-center justify-between gap-8 pt-8 border-t border-slate-100">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        @if(auth()->user()->currentTeam?->whatsapp_connected)
                            <button wire:click="sendTestMessage" wire:loading.attr="disabled"
                                class="px-8 py-4 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-3">
                                <svg wire:loading.class="animate-spin" wire:target="sendTestMessage" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                <span wire:loading.remove wire:target="sendTestMessage">Send Test Pulse</span>
                                <span wire:loading wire:target="sendTestMessage">Transmitting...</span>
                            </button>
                        @endif

                        <button wire:click="syncNow" wire:loading.attr="disabled"
                            class="px-8 py-4 bg-white text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-slate-200 hover:border-slate-400 transition-all flex items-center gap-3">
                            <svg wire:loading.class="animate-spin" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span wire:loading.remove wire:target="syncNow">Sync Meta Assets</span>
                            <span wire:loading wire:target="syncNow">Syncing...</span>
                        </button>
                    </div>

                    <div class="text-right">
                        <div class="flex items-center gap-3 justify-end mb-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Setup Progress</span>
                            <span class="text-sm font-black text-slate-900">{{ round($percent) }}%</span>
                        </div>
                        <div class="w-48 bg-slate-100 h-1 rounded-full overflow-hidden">
                            <div class="h-full bg-wa-teal transition-all duration-1000" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Feedback Alerts --}}
                @if(session()->has('checklist_message') || session()->has('checklist_error') || $diagnosticResult)
                    <div class="mt-8 space-y-4 animate-in fade-in slide-in-from-top-4 duration-500">
                        @if(session()->has('checklist_message'))
                            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-700 text-xs font-black uppercase tracking-widest flex items-center gap-3">
                                <div class="h-6 w-6 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">✓</div>
                                {{ session('checklist_message') }}
                            </div>
                        @endif
                        @if(session()->has('checklist_error'))
                            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-700 text-xs font-black uppercase tracking-widest flex items-center gap-3">
                                <div class="h-6 w-6 bg-rose-100 rounded-full flex items-center justify-center text-rose-600">!</div>
                                {{ session('checklist_error') }}
                            </div>
                        @endif
                        @if($diagnosticResult)
                            <div class="p-5 {{ $diagnosticResult['status'] === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-amber-50 border-amber-100 text-amber-800' }} border rounded-[2rem] flex items-start gap-4">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center {{ $diagnosticResult['status'] === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1">Diagnostic Report</p>
                                    <p class="text-xs font-bold">{{ $diagnosticResult['message'] }}</p>
                                </div>
                                <button wire:click="$set('diagnosticResult', null)" class="ml-auto opacity-30 hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex justify-end p-4 border-b border-slate-100">
            <button wire:click="toggleChecklist" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600 flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Restore Setup Blueprint
            </button>
        </div>
    @endif
</div>