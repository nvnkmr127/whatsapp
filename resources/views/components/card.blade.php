<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden']) }}>
    @if(isset($header))
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            {{ $header }}
        </div>
    @endif

    <div class="px-4 py-5 sm:p-6">
        {{ $content ?? $slot }}
    </div>

    @if(isset($footer))
        <div class="px-4 py-4 sm:px-6 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
            {{ $footer }}
        </div>
    @endif
</div>