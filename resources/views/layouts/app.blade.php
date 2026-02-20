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
    <livewire:calls.call-overlay />
    <livewire:chat.chat-beacon />
    @livewire('onboarding.basic-details-popup')

    @include('components.impersonation-banner')
    <x-banner />
    <x-toast-notifications />

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-layouts.sidebar />

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden bg-slate-50 dark:bg-slate-950">
            <!-- Top Header -->
            <x-layouts.header :header="$header ?? null" />

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto">
                <!-- Subscription Banner -->
                @include('components.subscription-banner')

                <div class="px-8 py-8">
                    <x-billing-alerts />
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
        // Global Error Handler for remote debugging
        window.addEventListener('error', function (e) {
            // Suppress known non-critical modal dialog errors
            if (e.message && e.message.includes('showModal') && e.message.includes('HTMLDialogElement')) {
                console.warn('[Suppressed] Modal dialog race condition:', e.message);
                e.preventDefault();
                return false;
            }
            console.error('[Global JS Error]', e.message, 'at', e.filename, 'line', e.lineno);
        });

        window.initTomSelect = function (selector, options = {}) {
            if (typeof TomSelect === 'undefined') {
                console.warn('TomSelect is not loaded yet.');
                return;
            }
            document.querySelectorAll(selector).forEach((el) => {
                if (!(el instanceof HTMLSelectElement)) return;
                try {
                    if (el.tomselect) el.tomselect.destroy();
                    new TomSelect(el, Object.assign({
                        plugins: ['remove_button'],
                        persist: false,
                        create: false,
                        maxItems: null
                    }, options));
                } catch (e) {
                    console.error('Failed to init TomSelect for', selector, e);
                }
            });
        }

        window.flatePickrWithTime = function () {
            if (typeof flatpickr === 'undefined') return;
            flatpickr("#scheduled_send_time", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    let input = document.getElementById('scheduled_send_time');
                    if (input) {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
        }

        window.initGLightbox = function () {
            if (typeof GLightbox === 'undefined') return;
            const lightbox = GLightbox({
                selector: '.glightbox'
            });
        }

        // Diagnostics
        document.addEventListener('livewire:init', () => {
            console.log('✅ Livewire 3 Initialized');
            console.log('✅ Alpine.js available:', typeof Alpine !== 'undefined');
            if (typeof Alpine !== 'undefined') {
                console.log('✅ Alpine Store [chat] exists:', !!Alpine.store('chat'));
            }
        });

        window.addEventListener('alpine:init', () => {
            console.log('✅ Alpine.js Initializing...');
        });

        // Global CSRF Token Expiration Handler
        document.addEventListener('livewire:init', () => {
            // Intercept all Livewire responses for 419 errors
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, content, preventDefault }) => {
                    if (status === 419) {
                        console.log('CSRF token expired (419), refreshing page...');
                        preventDefault();
                        window.location.reload();
                    }
                });
            });
        });

        // Also handle fetch/axios 419 errors globally
        window.addEventListener('error', function (event) {
            if (event.message && event.message.includes('419')) {
                console.log('CSRF error detected, refreshing page...');
                window.location.reload();
            }
        }, true);
    </script>
</body>

</html>