<!DOCTYPE html>
<html
  lang="{{ Auth::user() ? Session::get('locale', config('app.locale')) : get_setting('general.active_language') }}"
  class="h-full" x-data="{ theme: $persist('light') }"
  x-bind:class="{
      'dark': theme === 'dark' || (theme === 'system' && window.matchMedia(
              '(prefers-color-scheme: dark)')
          .matches)
  }">

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

    // Make date/time settings available to JavaScript
    window.dateTimeSettings = @json($dateTimeSettings);
    var date_format = window.dateTimeSettings.dateFormat;
    var is24Hour = window.dateTimeSettings.is24Hour;
    var time_format = window.dateTimeSettings.is24Hour ? 'h:i' : 'h:i K';
  </script>
</head>

<body class="h-full antialiased bg-gray-50 font-sans dark:bg-slate-800" x-data="{ theme: $persist('light') }"
  x-init="if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
  } else {
      document.documentElement.classList.remove('dark');
  }">

  <div id="main" x-data="{ open: false }" @keydown.window.escape="open = false"
    class="min-h-full flex">

    <livewire:backend.sidebar-navigation />

    <div class="lg:pl-[15rem] flex flex-col w-0 flex-1">
      <livewire:backend.header-navigation />
      <main class="flex-1 overflow-x-hidden">
        @if (request()->routeIs('admin.chat'))
          @yield('chat')
        @else
          <div class=" {{ request()->routeIs('admin.chat') ? 'p-2' : 'py-6 px-2 md:p-6' }}">
            {{ $slot }}

          </div>
        @endif

      </main>
      <x-notification />
    </div>

    <!-- Scripts -->
    @livewireScripts

    @vite('resources/js/app.js')
    @stack('scripts')
</body>

</html>
