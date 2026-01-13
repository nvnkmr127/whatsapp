@props(['disabled' => false])

<x-button :disabled="$disabled"
  {{ $attributes->merge(['type' => 'button', 'class' => 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-slate-700 dark:border-slate-500 dark:text-slate-200 dark:hover:border-slate-400 dark:focus:ring-offset-slate-800']) }}>
  {{ $slot }}
</x-button>
