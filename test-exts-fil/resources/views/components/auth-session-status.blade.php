@props([
    'status' => session('status'),
    'error' => session('error'),
])

@if ($status)
  <div
    {{ $attributes->merge(['class' => 'mb-4 text-sm bg-green-50 border-l-4 border-green-300 text-green-800 px-2 py-3 rounded dark:bg-gray-800 dark:border-green-800 dark:text-green-300']) }}
    role="alert">
    {{ $status }}
  </div>
@endif

@if ($error)
  <div
    {{ $attributes->merge(['class' => 'mb-4 text-sm bg-red-50 border-l-4 border-red-300 text-red-800 px-2 py-3 rounded dark:bg-gray-800 dark:border-red-800 dark:text-red-300']) }}
    role="alert">
    {{ $error }}
  </div>
@endif
