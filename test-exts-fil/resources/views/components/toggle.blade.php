@props(['id' => null, 'name' => null, 'value' => false])

<div x-data="{ isOn: {{ $value ? 'true' : 'false' }} }">
  <label class="relative inline-flex items-center cursor-pointer mt-2">
    <input type="checkbox" x-model="isOn"
      @if ($id) id="{{ $id }}" @endif
      @if ($name) name="{{ $name }}" @endif class="sr-only peer"
      @change="$dispatch('toggle-changed', { value: isOn })" {{ $attributes }}>
    <div
      class="w-11 h-6 bg-gray-200 rounded-full peer 
            peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 
            dark:peer-focus:ring-indigo-800 dark:bg-gray-700 
            peer-checked:after:translate-x-full peer-checked:after:border-white 
            after:content-[''] after:absolute after:top-0.5 after:left-[2px] 
            after:bg-white after:border-gray-300 after:border after:rounded-full 
            after:h-5 after:w-5 after:transition-all dark:border-gray-600 
            peer-checked:bg-indigo-600">
    </div>
  </label>
</div>
