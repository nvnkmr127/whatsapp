@props(['param'])

<div class="space-y-2">
    <div class="flex justify-between items-center">
        <label class="text-xs font-black uppercase tracking-widest text-slate-400">
            {{ $param['label'] }}
        </label>
        <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
            default {{ $param['default'] }}
        </span>
    </div>

    @if($param['type'] === 'int' || $param['type'] === 'float')
        <input type="number" step="{{ $param['type'] === 'float' ? '0.01' : '1' }}" min="0"
            wire:model="settings.{{ $param['key'] }}"
            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all"
            placeholder="{{ $param['default'] }}">
    @else
        <input type="text" wire:model="settings.{{ $param['key'] }}"
            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all"
            placeholder="{{ $param['default'] }}">
    @endif

    <p class="text-[10px] font-mono text-slate-400">{{ $param['key'] }}</p>
    @error('settings.' . $param['key'])
        <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
    @enderror
</div>