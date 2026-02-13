@props(['show' => 'false', 'maxWidth' => '2xl', 'closeable' => true])

@php
    $maxWidth = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
    ][$maxWidth] ?? $maxWidth;

    // Support wire:model if present
    $wireModel = $attributes->wire('model')->value();
    $showVariable = $wireModel ? '$wire.' . $wireModel : $show;

    // Check if the show variable is a literal that shouldn't be assigned to
    $isLiteral = in_array(strtolower($showVariable), ['true', 'false']) || empty($showVariable);
@endphp

@teleport('body')
<div x-show="{{ $showVariable }}" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto" @if($closeable && !$isLiteral)
    @keydown.escape.window="{{ $showVariable }} = false" @endif>

    <!-- Backdrop -->
    <div x-show="{{ $showVariable }}" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @if($closeable && !$isLiteral)
        @click="{{ $showVariable }} = false" @endif>
    </div>

    <!-- Modal Panel -->
    <div x-show="{{ $showVariable }}" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidth }} bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all my-8">

        {{ $slot }}

    </div>
</div>
@endteleport