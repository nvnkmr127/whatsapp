<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WhatsApp Business Hub') }} - Sign Up</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                        Start Your<br />
                        <span class="text-wa-light">14-Day Free Trial</span>
                    </h1>
                    <p class="text-xl text-white/80 font-medium">
                        No credit card required • Full access to all features
                    </p>
                </div>

                <!-- Features List -->
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-wa-light/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white mb-1">AI-Powered Chatbots</h3>
                            <p class="text-sm text-white/70">Build intelligent conversations without any coding required
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-wa-light/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white mb-1">Broadcast Campaigns</h3>
                            <p class="text-sm text-white/70">Reach thousands with personalized messages instantly</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-wa-light/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white mb-1">Advanced Analytics</h3>
                            <p class="text-sm text-white/70">Track performance with real-time dashboards and insights
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-wa-light/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white mb-1">API & Integrations</h3>
                            <p class="text-sm text-white/70">Connect with your existing tools and workflows seamlessly
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <p class="text-white/90 text-sm font-medium italic">
                        "This platform transformed how we engage with customers. Response times dropped by 80%!"
                    </p>
                    <p class="text-white/70 text-xs font-bold mt-2">— Sarah Chen, Marketing Director</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Register Form -->
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
                        Create <span class="text-wa-teal">Account</span>
                    </h1>
                    <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">14-day free trial • No credit card
                        required</p>
                </div>

                <!-- Main Card -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <div class="p-8">
                        <!-- Validation Errors -->
                        <x-validation-errors class="mb-6" />

                        <!-- Registration Form -->
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <!-- Full Name -->
                            <div class="mb-6">
                                <label for="name"
                                    class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2">Full
                                    Name</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                                    autocomplete="name"
                                    class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                    placeholder="John Doe" />
                            </div>

                            <!-- Email -->
                            <div class="mb-6">
                                <label for="email"
                                    class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2">Email
                                    Address</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                    autocomplete="username"
                                    class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                    placeholder="you@company.com" />
                            </div>

                            <!-- Phone Number & Company Name (2 columns) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="phone"
                                        class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2">Phone
                                        (Optional)</label>
                                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                                        autocomplete="tel"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                        placeholder="+1 (555) 000-0000" />
                                </div>
                                <div>
                                    <label for="company"
                                        class="text-xs font-black uppercase tracking-widest text-slate-400 block mb-2">Company
                                        (Optional)</label>
                                    <input id="company" type="text" name="company" value="{{ old('company') }}"
                                        class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal/20 transition-all"
                                        placeholder="Acme Inc." />
                                </div>
                            </div>

                            <!-- Terms & Privacy -->
                            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                                <div class="mb-6">
                                    <label
                                        class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                                        <input type="checkbox" name="terms" id="terms" required
                                            class="mt-0.5 w-5 h-5 rounded-lg border-none bg-slate-200 dark:bg-slate-700 text-wa-teal focus:ring-wa-teal/20" />
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            I agree to the
                                            <a href="{{ route('terms.show') }}" target="_blank"
                                                class="text-wa-teal hover:text-wa-dark underline">Terms of Service</a>
                                            and
                                            <a href="{{ route('policy.show') }}" target="_blank"
                                                class="text-wa-teal hover:text-wa-dark underline">Privacy Policy</a>
                                        </span>
                                    </label>
                                </div>
                            @endif

                            <!-- Submit Button -->
                            <button type="submit"
                                class="w-full py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Create Account
                            </button>

                            <!-- Sign In Link -->
                            <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400 font-medium">
                                Already have an account?
                                <a href="{{ route('login') }}"
                                    class="font-bold text-wa-teal hover:text-wa-dark transition-colors">
                                    Sign in
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>