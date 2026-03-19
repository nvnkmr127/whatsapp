@props(['name', 'size' => '10', 'showStatus' => false, 'isOnline' => false])

<div class="relative shrink-0">
    <img src="https://api.dicebear.com/9.x/initials/svg?seed={{ $name }}"
        class="rounded-full bg-slate-100 dark:bg-slate-700 object-cover border border-slate-100 dark:border-slate-700"
        style="width: {{ $size * 0.25 }}rem; height: {{ $size * 0.25 }}rem;" alt="{{ $name }}">

    @if($showStatus)
        <div @class([
            'absolute bottom-0 right-0 border-2 border-white dark:border-wa-dark-elevated rounded-full',
            'bg-emerald-500' => $isOnline,
            'bg-rose-500' => !$isOnline,
            'w-3 h-3' => $size >= 10,
            'w-2.5 h-2.5' => $size < 10,
        ])></div>
    @endif
</div>