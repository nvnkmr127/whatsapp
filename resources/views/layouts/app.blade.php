<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        {{ isset($title) ? $title . ' - ' : '' }}{{ auth()->check() && auth()->user()->currentTeam ? auth()->user()->currentTeam->name : ($appName ?? config('app.name', 'Laravel')) }}
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Third Party CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <!-- Custom Style Injection -->
    <style>
        :root {
            --wa-primary:
                {{ $brandPrimaryColor }}
            ;
        }

        .bg-wa-primary {
            background-color: var(--wa-primary);
        }

        .text-wa-primary {
            color: var(--wa-primary);
        }

        .border-wa-primary {
            border-color: var(--wa-primary);
        }

        .ring-wa-primary {
            --tw-ring-color: var(--wa-primary);
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <x-banner />
    <x-toast-notifications />

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-layouts.sidebar />

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden bg-slate-50 dark:bg-slate-950">
            <!-- Top Header -->
            <x-layouts.header />

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto">
                <!-- Subscription Banner -->
                @include('components.subscription-banner')

                <div class="px-8 py-8">
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