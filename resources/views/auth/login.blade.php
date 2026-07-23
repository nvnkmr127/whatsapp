<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Watxio') }} - Sign In</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 dark:bg-slate-950">
    <div class="flex min-h-screen">
        <!-- Left Side: Features Showcase (Refined Layout) -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-wa-teal overflow-hidden flex-col justify-between p-12" 
            x-data="{
                activeSlide: 0,
                slides: [
                    {
                        title: 'Unified Shared Inbox',
                        description: 'Collaborate with your team in real-time. Assign chats, detect collisions, and manage customer support efficiently.',
                        icon: 'inbox',
                        color: 'from-white/20 to-white/5'
                    },
                    {
                        title: 'AI Automation & RAG',
                        description: 'Deploy intelligent chatbots and use our AI Shopping Assistant to answer queries from your Knowledge Base 24/7.',
                        icon: 'cpu-chip',
                        color: 'from-white/20 to-white/5'
                    },
                    {
                        title: 'Marketing Campaigns',
                        description: 'Send bulk WhatsApp broadcasts with high delivery rates. Track read receipts and engagement in real-time.',
                        icon: 'megaphone',
                        color: 'from-white/20 to-white/5'
                    },
                    {
                        title: 'Commerce Integration',
                        description: 'Seamlessly sync with Shopify & WooCommerce. Manage orders and carts directly within WhatsApp.',
                        icon: 'shopping-bag',
                        color: 'from-white/20 to-white/5'
                    },
                    {
                        title: 'Enterprise Security',
                        description: 'Bank-grade encryption, role-based access control, and comprehensive audit logs for total peace of mind.',
                        icon: 'shield-check',
                        color: 'from-white/20 to-white/5'
                    },
                    {
                        title: 'Deep Analytics',
                        description: 'Visualize message volume, agent performance, and campaign ROI with our powerful real-time dashboards.',
                        icon: 'chart-bar',
                        color: 'from-white/20 to-white/5'
                    }
                ],
                timer: null,
                progress: 0,
                interval: 50, 
                duration: 5000, 
                
                startTimer() {
                    this.timer = setInterval(() => {
                        this.progress += (this.interval / this.duration) * 100;
                        if (this.progress >= 100) {
                            this.progress = 0;
                            this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                        }
                    }, this.interval);
                },
                stopTimer() {
                    clearInterval(this.timer);
                },
                setSlide(index) {
                    this.activeSlide = index;
                    this.progress = 0;
                    this.stopTimer();
                    this.startTimer();
                }
            }" 
            x-init="startTimer()" 
            @mouseenter="stopTimer()" 
            @mouseleave="startTimer()">
            
            <!-- Background Gradients & Grid Pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-wa-teal via-wa-dark to-slate-900 z-0"></div>
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 30px 30px;"></div>
            
            <!-- Ambient Orbs -->
            <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-white/10 rounded-full blur-[120px] animate-float"></div>
            <div class="absolute bottom-[-20%] right-[-10%] w-[700px] h-[700px] bg-wa-light/10 rounded-full blur-[150px] animate-pulse"></div>

            <!-- Header / Logo (Top Left) -->
            <div class="relative z-10 animate-slide-in-left">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/10 rounded-xl border border-white/20 shadow-inner backdrop-blur-md">
                         <!-- Dynamic Logo Loading -->
                         @if(\App\Models\Team::first()?->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url(\App\Models\Team::first()->logo_path) }}" 
                                 alt="Logo" class="w-8 h-8 object-contain">
                        @else
                            <x-application-mark class="w-8 h-8 text-white" />
                        @endif
                    </div>
                    <span class="text-xl font-black text-white tracking-tight uppercase opacity-90">
                        {{ config('app.name', 'Watxio') }}
                    </span>
                </div>
            </div>

            <!-- Special Offer Banner (Top Right) -->
            <div class="absolute top-10 right-10 z-20 animate-slide-in-right">
                <div class="group relative cursor-pointer" x-data="{ tooltip: false }" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                    <!-- Glow Effect -->
                    <div class="absolute -inset-1 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full blur opacity-40 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>
                    
                    <!-- Main Banner Pill -->
                    <div class="relative bg-gradient-to-r from-yellow-300 via-yellow-400 to-orange-400 text-slate-900 px-5 py-3 rounded-full font-bold shadow-2xl flex items-center gap-4 border border-white/40 ring-1 ring-white/20 transform hover:scale-105 transition-all duration-300">
                        <!-- Icon Container -->
                        <div class="flex items-center justify-center w-10 h-10 bg-white/30 rounded-full backdrop-blur-sm shadow-inner border border-white/20">
                            <span class="text-xl animate-bounce">🎁</span>
                        </div>
                        
                        <!-- Text Content -->
                        <div class="flex flex-col leading-tight">
                            <span class="text-[10px] uppercase tracking-widest font-black text-slate-800/70">Launch Special</span>
                            <div class="flex items-center gap-1">
                                <span class="text-base font-black text-slate-900 tracking-tight">Get 6 Months FREE</span>
                                <svg class="w-4 h-4 text-slate-900 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Tooltip / Detail Popover -->
                    <div x-show="tooltip" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute top-full right-0 mt-3 w-64 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-4 shadow-2xl text-left z-30">
                        <div class="text-xs font-bold text-white uppercase tracking-wider mb-1">Offer Details</div>
                        <p class="text-sm text-slate-200 leading-relaxed">
                            Sign up today and get full access to the <span class="text-yellow-400 font-bold">Pro Plan</span> for 6 months. No credit card required.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Glass Card Container (Centered & Larger) -->
            <div class="relative z-10 w-full max-w-2xl mx-auto my-auto">
                <div class="bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl rounded-[2.5rem] p-12 overflow-hidden relative">
                    
                    <!-- Carousel Content -->
                    <div class="relative h-96">
                        <template x-for="(slide, index) in slides" :key="index">
                            <div x-show="activeSlide === index"
                                 x-transition:enter="transition ease-out duration-700"
                                 x-transition:enter-start="opacity-0 translate-y-10 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-500"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 -translate-y-10 scale-95"
                                 class="absolute inset-0 flex flex-col justify-center items-center text-center">
                                
                                <!-- Rich Graphic Illustration (Larger) -->
                                <div class="mb-10 w-full h-56 relative flex items-center justify-center transform scale-110">
                                    
                                    <!-- 1. Unified Shared Inbox Scene -->
                                    <div x-show="slide.icon === 'inbox'" class="relative w-64 h-40">
                                        <!-- Phone Frame -->
                                        <div class="absolute inset-0 bg-white/10 border border-white/20 rounded-3xl backdrop-blur-md shadow-2xl transform -rotate-2"></div>
                                        <div class="absolute inset-0 bg-slate-900/50 rounded-3xl overflow-hidden flex flex-col p-4 gap-3 transform -rotate-2">
                                            <!-- Chat Bubbles -->
                                            <div class="flex items-start gap-2 animate-slide-in-left delay-100">
                                                <div class="w-8 h-8 rounded-full bg-indigo-500/50 flex-shrink-0"></div>
                                                <div class="bg-white/10 p-2 rounded-2xl rounded-tl-none w-3/4">
                                                    <div class="h-2 w-16 bg-white/20 rounded mb-1"></div>
                                                    <div class="h-2 w-full bg-white/10 rounded"></div>
                                                </div>
                                            </div>
                                            <div class="flex items-end justify-end gap-2 animate-slide-in-right delay-200">
                                                <div class="bg-wa-teal p-2 rounded-2xl rounded-tr-none w-2/3">
                                                    <div class="h-2 w-full bg-white/30 rounded mb-1"></div>
                                                    <div class="h-2 w-1/2 bg-white/20 rounded"></div>
                                                </div>
                                                <div class="w-8 h-8 rounded-full bg-teal-500/50 flex-shrink-0"></div>
                                            </div>
                                            <div class="flex items-start gap-2 animate-slide-in-left delay-300">
                                                <div class="w-8 h-8 rounded-full bg-purple-500/50 flex-shrink-0"></div>
                                                <div class="bg-white/10 p-2 rounded-2xl rounded-tl-none w-1/2">
                                                    <div class="h-2 w-full bg-white/10 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Floating Badge -->
                                        <div class="absolute -right-4 -top-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg animate-bounce">
                                            3 New
                                        </div>
                                    </div>

                                    <!-- 2. AI Automation Scene -->
                                    <div x-show="slide.icon === 'cpu-chip'" class="relative w-48 h-48 flex items-center justify-center">
                                        <!-- Central Brain -->
                                        <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg shadow-purple-500/30 flex items-center justify-center z-10 animate-pulse-slow">
                                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <!-- Orbiting Nodes -->
                                        <div class="absolute w-full h-full animate-spin-slow">
                                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-8 bg-white/10 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center shadow-lg">
                                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-8 bg-white/10 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center shadow-lg">
                                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/10 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center shadow-lg">
                                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/10 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center shadow-lg">
                                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Marketing Campaigns Scene -->
                                    <div x-show="slide.icon === 'megaphone'" class="relative w-full h-40 flex items-center justify-center">
                                        <!-- Megaphone -->
                                        <div class="absolute left-4 w-24 h-24 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg z-10 transform -rotate-12 hover:rotate-0 transition-transform">
                                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                        </div>
                                        <!-- Waves -->
                                        <div class="absolute left-28 top-1/2 -translate-y-1/2 flex gap-2">
                                            <div class="w-2 h-16 bg-white/20 rounded-full animate-pulse"></div>
                                            <div class="w-2 h-24 bg-white/40 rounded-full animate-pulse delay-75"></div>
                                            <div class="w-2 h-32 bg-white/60 rounded-full animate-pulse delay-150"></div>
                                            <div class="w-2 h-24 bg-white/40 rounded-full animate-pulse delay-200"></div>
                                            <div class="w-2 h-16 bg-white/20 rounded-full animate-pulse delay-300"></div>
                                        </div>
                                        <!-- Users Receiving -->
                                        <div class="absolute right-0 flex flex-col gap-3">
                                            <div class="w-10 h-10 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center animate-bounce delay-100">
                                                <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="w-10 h-10 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center animate-bounce delay-200">
                                                <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="w-10 h-10 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center animate-bounce delay-300">
                                                <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 13l4 4L19 7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Commerce Scene -->
                                    <div x-show="slide.icon === 'shopping-bag'" class="relative w-full h-48 flex items-center justify-center">
                                        <!-- Store Card -->
                                        <div class="w-48 h-32 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl shadow-2xl relative overflow-hidden">
                                            <div class="h-8 bg-black/20 flex items-center px-3 gap-1">
                                                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                                <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                                                <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                            </div>
                                            <div class="p-3 grid grid-cols-2 gap-2">
                                                <div class="h-16 bg-white/5 rounded-lg border border-white/10 flex items-center justify-center">
                                                    <div class="w-8 h-10 bg-indigo-400/50 rounded"></div>
                                                </div>
                                                <div class="h-16 bg-white/5 rounded-lg border border-white/10 flex items-center justify-center">
                                                    <div class="w-8 h-10 bg-pink-400/50 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Floating Cart -->
                                        <div class="absolute -right-2 top-0 w-16 h-16 bg-green-500 rounded-full shadow-lg flex items-center justify-center animate-float">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        </div>
                                        <!-- Price Tag -->
                                        <div class="absolute left-0 bottom-0 bg-yellow-400 text-slate-900 font-bold px-3 py-1 rounded-lg shadow-lg transform -rotate-12">
                                            $99.00
                                        </div>
                                    </div>

                                    <!-- 5. Security Scene -->
                                    <div x-show="slide.icon === 'shield-check'" class="relative w-48 h-48 flex items-center justify-center">
                                        <!-- Shield -->
                                        <div class="w-32 h-36 bg-gradient-to-b from-slate-700 to-slate-900 border border-slate-600 rounded-[2rem] flex items-center justify-center shadow-2xl z-10">
                                            <svg class="w-16 h-16 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        </div>
                                        <!-- Grid Background -->
                                        <div class="absolute inset-0 grid grid-cols-6 grid-rows-6 gap-1 opacity-20">
                                            <template x-for="i in 36">
                                                <div class="w-1 h-1 bg-white rounded-full"></div>
                                            </template>
                                        </div>
                                        <!-- Scanning Line -->
                                        <div class="absolute top-0 left-0 w-full h-1 bg-emerald-400/50 shadow-[0_0_15px_rgba(52,211,153,0.5)] animate-scan"></div>
                                    </div>

                                    <!-- 6. Analytics Scene -->
                                    <div x-show="slide.icon === 'chart-bar'" class="relative w-64 h-40 flex items-end justify-center gap-3 px-4 pb-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                                        <!-- Bars -->
                                        <div class="w-12 bg-blue-500/80 rounded-t-lg animate-grow-up" style="height: 40%; animation-delay: 0ms;"></div>
                                        <div class="w-12 bg-purple-500/80 rounded-t-lg animate-grow-up" style="height: 70%; animation-delay: 150ms;"></div>
                                        <div class="w-12 bg-pink-500/80 rounded-t-lg animate-grow-up" style="height: 55%; animation-delay: 300ms;"></div>
                                        <div class="w-12 bg-green-500/80 rounded-t-lg animate-grow-up" style="height: 90%; animation-delay: 450ms;"></div>
                                        
                                        <!-- Line Graph Overlay -->
                                        <svg class="absolute inset-0 w-full h-full pointer-events-none" preserveAspectRatio="none">
                                            <path d="M20 100 L 70 80 L 120 50 L 170 70 L 220 20" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-dasharray="300" stroke-dashoffset="300" class="animate-draw-line"></path>
                                        </svg>
                                    </div>

                                </div>

                                <h2 class="text-4xl font-black text-white mb-6 leading-tight tracking-tight drop-shadow-sm" x-text="slide.title"></h2>
                                <p class="text-lg text-slate-200 font-medium leading-relaxed drop-shadow-sm opacity-90 max-w-lg mx-auto" x-text="slide.description"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Progress Bar & Footer -->
                    <div class="mt-8 flex items-center justify-between border-t border-white/10 pt-6">
                        <!-- Progress Indicators -->
                        <div class="flex gap-2">
                            <template x-for="(slide, index) in slides" :key="index">
                                <button @click="setSlide(index)" 
                                        class="h-1 rounded-full transition-all duration-300 overflow-hidden relative"
                                        :class="activeSlide === index ? 'w-8 bg-white/30' : 'w-2 bg-white/20 hover:bg-white/40'">
                                    <div x-show="activeSlide === index" 
                                         class="absolute top-0 left-0 h-full bg-white transition-all duration-75 ease-linear"
                                         :style="'width: ' + progress + '%'"></div>
                                </button>
                            </template>
                        </div>
                        
                        <!-- Trust Badge -->
                        <div class="flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full border border-white/10">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-xs font-bold text-white uppercase tracking-wider">System Operational</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Footer (Optional) -->
            <div class="relative z-10 text-center">
                <p class="text-xs text-white/40 font-medium">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-slate-50 dark:bg-slate-950">
            <div class="w-full max-w-md">
                <!-- Mobile Logo with animation -->
                <div class="lg:hidden text-center mb-8 animate-slide-up opacity-0">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-wa-teal rounded-2xl shadow-xl shadow-wa-teal/20 mb-4 hover:scale-110 transition-transform duration-300">
                        <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </div>
                </div>

                <!-- Heading with animation -->
                <div class="text-center mb-8 animate-slide-in-right delay-100 opacity-0">
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Welcome <span
                            class="text-wa-teal bg-gradient-to-r from-wa-teal to-wa-dark bg-clip-text text-transparent">Back</span>
                    </h1>
                    <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">Sign in to your workspace</p>
                </div>

                <!-- Main Card with animation and glow -->
                <div
                    class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 animate-slide-up delay-200 opacity-0 group">
                    
                    <!-- Special Offer Banner (Text Style) -->
                    <div class="absolute -top-10 left-0 w-full flex justify-center z-20">
                         <div class="flex items-center gap-2 animate-bounce-slow cursor-pointer hover:scale-105 transition-transform">
                            <span class="text-xl">🚀</span>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                <span class="font-bold text-wa-teal uppercase text-xs tracking-wider">Launch Offer:</span>
                                Get 6 Months <span class="font-black text-wa-dark dark:text-white underline decoration-wa-teal decoration-2 underline-offset-2">FREE</span>
                            </p>
                         </div>
                    </div>

                    <div class="p-8 overflow-hidden relative rounded-[2.5rem]">
                        <!-- Validation Errors -->
                        <x-validation-errors class="mb-6" />

                        @session('status')
                            <div class="mb-6 p-4 bg-wa-teal/10 border border-wa-teal/20 rounded-2xl animate-slide-up">
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

                <!-- Footer Links with animation -->
                <div class="mt-6 text-center animate-fade-in delay-400 opacity-0">
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Don't have an account?
                        <a href="{{ route('register') }}"
                            class="font-bold text-wa-teal hover:text-wa-dark transition-colors hover:underline">
                            Create one now
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts

    {{-- Session Expiration Handler --}}
    <script>
        // Detect page visibility and refresh if session might be expired
        let pageLoadTime = Date.now();
        const sessionLifetime = {{ config('session.lifetime', 120) }} * 60 * 1000; // Convert minutes to milliseconds
        const warningTime = sessionLifetime - (5 * 60 * 1000); // Warn 5 minutes before expiration

        // Check if page has been open too long when it becomes visible
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                const timeElapsed = Date.now() - pageLoadTime;

                // If page has been open longer than session lifetime, refresh it
                if (timeElapsed > sessionLifetime) {
                    console.log('Session expired, refreshing page...');
                    window.location.reload();
                }
            }
        });

        // Also check periodically (every minute)
        setInterval(function () {
            const timeElapsed = Date.now() - pageLoadTime;

            if (timeElapsed > sessionLifetime) {
                console.log('Session expired, refreshing page...');
                window.location.reload();
            }
        }, 60000); // Check every minute

        // Intercept 419 errors and auto-refresh
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        console.log('CSRF token expired, refreshing page...');
                        preventDefault();
                        window.location.reload();
                    }
                });
            });
        });
    </script>
</body>

</html>