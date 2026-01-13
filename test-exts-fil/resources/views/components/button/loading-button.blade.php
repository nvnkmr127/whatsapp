@props(['disabled' => false, 'target' => null, 'type' => 'button'])

<button
  {{ $attributes->merge([
      'type' => $type,
      'class' =>
          'text-white bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 px-4 py-2 rounded-md flex justify-center items-center text-sm min-w-[100px]',
  ]) }}
  @if ($disabled) disabled @endif wire:loading.attr="disabled"
  wire:target="{{ $target }}">
  <div class="flex justify-center items-center w-full">
    <span wire:loading.remove wire:target="{{ $target }}">{{ $slot }}</span>
    <span wire:loading wire:target="{{ $target }}" class="flex items-center justify-center">
      <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 my-1" />
    </span>
  </div>
</button>
