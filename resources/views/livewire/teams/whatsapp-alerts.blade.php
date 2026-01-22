<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
            ACTIVE <span class="text-wa-teal">ALERTS</span>
            @if($alerts->count() > 0)
                <span class="ml-2 inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-rose-500 rounded-full animate-pulse">
                    {{ $alerts->count() }}
                </span>
            @endif
        </h3>
    </div>
    
    <div class="grid gap-4">
        @forelse($alerts as $alert)
            @php
                $severityColors = [
                    'emergency' => 'rose',
                    'critical' => 'orange',
                    'warning' => 'yellow',
                    'info' => 'blue'
                ];
                $color = $severityColors[$alert->severity] ?? 'blue';
            @endphp
            <div class="bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 rounded-2xl p-6 border border-{{ $color }}-100 dark:border-{{ $color }}-800 group transition-all hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 text-2xl">
                        @if($alert->severity === 'emergency') 🚨
                        @elseif($alert->severity === 'critical') 🔴
                        @elseif($alert->severity === 'warning') ⚠️
                        @else ℹ️
                        @endif
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-{{ $color }}-600 px-2 py-0.5 bg-{{ $color }}-100 dark:bg-{{ $color }}-900/40 rounded-full">
                                {{ $alert->severity }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">{{ $alert->created_at->diffForHumans() }}</span>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white mb-2">{{ $alert->message }}</h4>
                        
                        @if($alert->metadata)
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-3 pt-3 border-t border-{{ $color }}-100/50 dark:border-{{ $color }}-800/50">
                                @foreach($alert->metadata as $key => $value)
                                    <div class="text-[10px] text-slate-500">
                                        <span class="font-bold uppercase opacity-70">{{ str_replace('_', ' ', $key) }}:</span> 
                                        <span class="text-slate-700 dark:text-slate-300">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <button wire:click="acknowledge({{ $alert->id }})" 
                            class="flex-shrink-0 p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">No Active Alerts</p>
                <p class="text-xs text-slate-400 mt-1">Your WhatsApp system is running smoothly</p>
            </div>
        @endforelse
    </div>
</div>
