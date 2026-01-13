<!DOCTYPE html>
<html lang="{{ get_setting('general.active_language') }}" class="h-full">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>
    {{ (!empty(get_setting('seo.meta_title')) ? get_setting('seo.meta_title') : 'WhatsMark') . (isset($title) ? ' - ' . $title : '') }}
  </title>

  <meta name="description" content="{{ get_setting('seo.meta_description') ?? 'WhatsMark' }}" />

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="16x16"
    href="{{ get_setting('general.favicon') ? Storage::url(get_setting('general.favicon')) : url('./img/favicon-16x16.png') }}">
  <link rel="icon" type="image/png" sizes="32x32"
    href="{{ get_setting('general.favicon') ? Storage::url(get_setting('general.favicon')) : url('./img/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="192x192"
    href="{{ get_setting('general.favicon') ? Storage::url(get_setting('general.favicon')) : url('./img/favicon.png') }}">
  <link rel="apple-touch-icon"
    href="{{ get_setting('general.favicon') ? Storage::url(get_setting('general.favicon')) : url('./img/apple-touch-icon.png') }}">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lexend:wght@100..900&display=swap">

  <!-- Styles -->

  @livewireStyles
  @vite('resources/css/app.css')
  <script>
    window.pusherConfig = {
      key: '{{ get_setting('pusher.app_key') }}',
      cluster: '{{ get_setting('pusher.cluster') }}',
      notification_enabled: {{ get_setting('pusher.real_time_notify') ? 'true' : 'false' }},
      desktop_notification: {{ get_setting('pusher.desk_notify') ? 'true' : 'false' }},
      auto_dismiss_notification: {{ !empty(get_setting('pusher.dismiss_desk_notification')) ? get_setting('pusher.dismiss_desk_notification') : 0 }}
    };
    var date_format = '{{ get_setting('general.date_format') }}';
    var time_format = '{{ get_setting('general.time_format') }}';
  </script>
</head>

<body class="font-sans text-gray-900 antialiased">
  <div
    class="min-h-screen flex flex-col sm:justify-center items-center sm:pt-0 bg-gray-100 dark:bg-gray-900">
    <div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 overflow-hidden w-full']) }}>
      {{ $slot }}
    </div>
  </div>
  <!-- Scripts -->
  @livewireScripts

  @vite('resources/js/app.js')
  @stack('scripts')
</body>

</html>
