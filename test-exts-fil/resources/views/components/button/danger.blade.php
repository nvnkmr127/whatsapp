@props(['disabled' => false])

<x-button :disabled="$disabled"
  {{ $attributes->merge(['type' => 'submit', 'class' => 'px-3 py-1 text-sm font-medium shadow-sm text-white bg-red-600 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:hover:text-red-500 dark:focus:ring-offset-slate-800']) }}>
  {{ $slot }}
</x-button>
