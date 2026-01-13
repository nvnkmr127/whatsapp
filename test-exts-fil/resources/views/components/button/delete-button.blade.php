@props(['class' => ''])
<button
  {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex w-full justify-center rounded-md bg-red-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white dark:text-red-400 ring-1 ring-red-600 dark:ring-gray-600 ring-inset shadow-xs hover:bg-red-500 dark:hover:bg-red-500 dark:hover:text-gray-100 sm:ml-3 sm:w-auto ' . $class]) }}>
  {{ $slot }}
</button>
