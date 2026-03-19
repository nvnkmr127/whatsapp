@props(['variant' => 'primary', 'type' => 'button'])

@php
$baseClasses = 'flex-shrink-0 flex items-center justify-center gap-2 px-5 py-3 font-black uppercase tracking-widest text-[10px] rounded-2xl transition-all active:scale-95 whitespace-nowrap';

$variants = [
    'primary' => 'bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 shadow-xl shadow-slate-900/20 dark:shadow-wa-teal/20 hover:scale-[1.02]',
    'secondary' => 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-100 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700',
    'danger' => 'bg-rose-500 text-white shadow-xl shadow-rose-500/20 hover:bg-rose-600',
    'ghost' => 'bg-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-200',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>
    {{ $slot }}
</button>
