<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WhatsApp Business Hub') }} - Sign In</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 dark:bg-slate-950">
    <div class="flex min-h-screen">
        <!-- Left Side: Features Showcase -->
        <div
            class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-wa-teal via-wa-dark to-slate-900 p-12 flex-col justify-between overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <img src="{{ asset('images/auth-background.png') }}" alt="" class="w-full h-full object-cover" />
            </div>

            <!-- Content -->
            <div class="relative z-10">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-wa-teal" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight">WhatsApp Business</h2>
                </div>

                <!-- Main Heading -->
                <div class="mb-12">
                    <h1 class="text-5xl font-black text-white mb-4 leading-tight">
                        Automate Your<br />
                        <span class="text-wa-light">Customer Conversations</span>
                    </h1>
                    <p class="text-xl text-white/80 font-medium">
                        The complete WhatsApp Business solution for modern teams
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <div class="w-12 h-12 bg-wa-light/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">AI Chatbots</h3>
                        <p class="text-sm text-white/70">Intelligent automation for 24/7 support</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <div class="w-12 h-12 bg-wa-light/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">Campaigns</h3>
                        <p class="text-sm text-white/70">Broadcast to thousands instantly</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <div class="w-12 h-12 bg-wa-light/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">CRM</h3>
                        <p class="text-sm text-white/70">Unified contact management</p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                        <div class="w-12 h-12 bg-wa-light/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">Analytics</h3>
                        <p class="text-sm text-white/70">Real-time insights & reports</p>
                    </div>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="relative z-10 grid grid-cols-3 gap-8">
                <div>
                    <div class="text-3xl font-black text-white mb-1">10K+</div>
                    <div class="text-sm text-white/70 font-medium">Messages/Day</div>
                </div>
                <div>
                    <div class="text-3xl font-black text-white mb-1">99.9%</div>
                    <div class="text-sm text-white/70 font-medium">Uptime</div>
                </div>
                <div>
                    <div class="text-3xl font-black text-white mb-1">500+</div>
                    <div class="text-sm text-white/70 font-medium">Businesses</div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-slate-50 dark:bg-slate-950">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-8">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-wa-teal rounded-2xl shadow-xl shadow-wa-teal/20 mb-4">
                        <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </div>
                </div>

                <!-- Heading -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Welcome <span class="text-wa-teal">Back</span>
                    </h1>
                    <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">Sign in to your workspace</p>
                </div>

                <!-- Main Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <div class="p-8">
                        <!-- Validation Errors -->
                        <x-validation-errors class="mb-6" />

                        @session('status')
                            <div class="mb-6 p-4 bg-wa-teal/10 border border-wa-teal/20 rounded-2xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 w-5 h-5 bg-wa-teal rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-bold text-wa-teal">{{ $value }}</span>
                                </div>
                            </div>
                        @endsession

                        <!-- Livewire Login Component -->
                        <livewire:auth.passwordless-login />
                    </div>
                </div>

                <!-- Footer Links -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Don't have an account?
                        <a href="{{ route('register') }}"
                            class="font-bold text-wa-teal hover:text-wa-dark transition-colors">
                            Create one now
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>