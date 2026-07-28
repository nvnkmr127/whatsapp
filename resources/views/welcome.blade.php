<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WhatsApp Business API | Modern Integration</title>
    
    <!-- Fonts -->
    <link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --wa-teal: #25D366;
            --wa-teal-dark: #128C7E;
            --wa-blue: #34B7F1;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #0a192f, #020617);
            color: #f8fafc;
            margin: 0;
            overflow-x: hidden;
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 2rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 10%;
            left: 5%;
            width: 300px;
            height: 300px;
            background: var(--wa-teal);
            filter: blur(150px);
            opacity: 0.15;
            z-index: 0;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 10%;
            right: 5%;
            width: 400px;
            height: 400px;
            background: var(--wa-blue);
            filter: blur(180px);
            opacity: 0.1;
            z-index: 0;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            padding: 3rem;
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            z-index: 10;
        }

        .hero-content {
            animation: slideIn 1s ease-out;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-image-container {
            position: relative;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }

        .hero-image-container:hover {
            transform: perspective(1000px) rotateY(0deg) scale(1.02);
        }

        .btn-primary {
            background: linear-gradient(to right, var(--wa-teal), var(--wa-teal-dark));
            color: #fff;
            padding: 1rem 2.5rem;
            border-radius: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(37, 211, 102, 0.3);
            filter: brightness(1.1);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        .feature-icon {
            color: var(--wa-teal);
            background: rgba(37, 211, 102, 0.1);
            padding: 0.5rem;
            border-radius: 0.75rem;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @media (max-width: 968px) {
            .glass-card {
                grid-template-columns: 1fr;
                padding: 2rem;
                gap: 2rem;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-image-container {
                transform: none;
            }
        }
    </style>
</head>
<body class="antialiased">
    <main class="hero-section">
        <div class="glass-card">
            <div class="hero-content">
                <div class="feature-item">
                    <span class="feature-icon">✨</span>
                    <span>Next-Gen Automation Engine</span>
                </div>
                <h1 class="hero-title">Scale your Business on WhatsApp.</h1>
                <p class="hero-subtitle">
                    Automate conversations, manage templates, and drive engagement with the most robust WhatsApp Business API integration layer ever built.
                </p>
                
                <div style="margin-bottom: 3rem;">
                    <div class="feature-item">
                        <svg class="feature-icon" style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Dynamic Workflow Builder</span>
                    </div>
                    <div class="feature-item">
                        <svg class="feature-icon" style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Real-time Analytics Dashboard</span>
                    </div>
                    <div class="feature-item">
                        <svg class="feature-icon" style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Meta-Compliant Template Syncing</span>
                    </div>
                </div>

                @if (Route::has('login'))
                    <div>
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary">
                                Launch Console
                                <svg style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary">
                                Get Started
                                <svg style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            </a>
                        @endauth
                    </div>
                @endif
            </div>

            <div class="hero-image-container">
                <img src="/hero.png" alt="Platform Dashboard" style="width: 100%; height: auto; display: block;">
            </div>
        </div>
    </main>
</body>
</html>