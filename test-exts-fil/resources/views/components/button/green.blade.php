@props(['disabled' => false])

<x-button :disabled="$disabled"
  {{ $attributes->merge(['type' => 'submit', 'class' => 'text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:hover:bg-green-500 dark:focus:ring-offset-slate-800']) }}>
  {{ $slot }}
</x-button>
