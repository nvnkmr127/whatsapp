@props(['show', 'maxWidth' => '2xl', 'closeable' => true, 'backdropClose' => true])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
    'full' => 'sm:max-w-full',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp


<div
    role="dialog"
    aria-modal="true"
    x-data="{ show: $wire.entangle(@js($attributes->wire('model')->value())) }"
    x-show="show"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        @click="if ({{ $backdropClose ? 'true' : 'false' }}) show = false"
    ></div>


    <!-- Modal Content -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClass }} bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden"
    >
        @if($closeable)
            <button
                @click="show = false"
                class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all z-10"
                aria-label="Close modal"
            >
                <x-icon name="plus" class="w-6 h-6 rotate-45" />
            </button>
        @endif

        {{ $slot }}

    </div>
</div>
