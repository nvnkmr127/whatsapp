@props(['disabled' => false])

<button {{ $disabled ? 'disabled' : '' }}
  {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center p-2 text-sm border border-transparent font-medium disabled:opacity-50 disabled:pointer-events-none transition text-white rounded-full bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) }}>
  {{ $slot }}
</button>
