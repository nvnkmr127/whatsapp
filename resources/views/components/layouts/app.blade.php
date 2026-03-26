<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        {{ isset($title) ? $title . ' - ' : '' }}{{ auth()->check() && auth()->user()->currentTeam ? auth()->user()->currentTeam->name : ($appName ?? config('app.name', 'Laravel')) }}
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- External Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles

    <!-- Third Party CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <livewire:calls.call-overlay />
    <x-banner />

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @if(!isset($fullscreen) || !$fullscreen)
            <x-layouts.sidebar />
        @endif

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden bg-slate-50 dark:bg-slate-950">
            <!-- Top Header -->
            @if(!isset($fullscreen) || !$fullscreen)
                <x-layouts.header :header="$header ?? null" />
            @endif

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden {{ (isset($fullscreen) && $fullscreen) ? 'overflow-y-hidden h-full' : 'overflow-y-auto' }}">
                <!-- Subscription Banner -->
                @if(!isset($fullscreen) || !$fullscreen)
                    @include('components.subscription-banner')
                @endif

                <div class="{{ (isset($fullscreen) && $fullscreen) ? 'h-full w-full' : 'px-8 py-8' }}">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @stack('modals')

    @livewireScripts

    <!-- Third Party Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>

    <script>
        window.initTomSelect = function (selector, options = {}) {
            document.querySelectorAll(selector).forEach((el) => {
                if (el.tomselect) el.tomselect.destroy();
                new TomSelect(el, Object.assign({
                    plugins: ['remove_button'],
                    persist: false,
                    create: false,
                    maxItems: null
                }, options));
            });
        }

        window.flatePickrWithTime = function () {
            flatpickr("#scheduled_send_time", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    // Livewire binding often needs manual trigger
                    let input = document.getElementById('scheduled_send_time');
                    if (input) {
                        input.dispatchEvent(new Event('input'));
                    }
                }
            });
        }

        window.initGLightbox = function () {
            const lightbox = GLightbox({
                selector: '.glightbox'
            });
        }
    </script>
</body>

</html>