@props(['disabled' => false])

<x-button :disabled="$disabled"
  {{ $attributes->merge(['type' => 'submit', 'class' => 'text-white bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']) }}>
  {{ $slot }}
</x-button>
