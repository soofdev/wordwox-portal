<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@stack('title', config('app.name', 'Laravel'))</title>
    
    @stack('meta')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:400,700|source-sans-pro:400,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('head')

    <style>
        .classic-serif { font-family: 'Playfair Display', serif; }
        .classic-sans { font-family: 'Source Sans Pro', sans-serif; }
        
        .classic-pattern {
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255,255,255,.2) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255,255,255,.2) 2px, transparent 2px);
            background-size: 100px 100px;
        }
        
        .classic-border {
            border: 3px solid #d4af37;
            position: relative;
        }
        
        .classic-border::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid #d4af37;
            pointer-events: none;
        }
        
        .classic-shadow {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }
        
        .classic-button {
            background: linear-gradient(145deg, #d4af37, #b8941f);
            border: 2px solid #d4af37;
            position: relative;
            overflow: hidden;
        }
        
        .classic-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .classic-button:hover::before {
            left: 100%;
        }
        
        .ornament {
            font-size: 1.5rem;
            color: #d4af37;
        }
    </style>
</head>
<body class="classic-sans antialiased h-full bg-gradient-to-b from-amber-50 to-orange-50">
    <div class="min-h-full">
        <!-- Classic Header with Ornamental Design -->
        <header class="bg-gradient-to-r from-amber-900 via-yellow-800 to-amber-900 text-white classic-pattern">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="text-center border-b border-amber-600 pb-4 mb-4">
                    <h1 class="classic-serif text-4xl md:text-5xl font-bold text-amber-100">
                        <span class="ornament">❦</span>
                        {{ config('app.name', 'SuperHero CrossFit') }}
                        <span class="ornament">❦</span>
                    </h1>
                    <p class="text-amber-200 text-lg mt-2 italic">"Excellence in Fitness Since 2020"</p>
                </div>
                
                <!-- Classic Navigation -->
                <nav class="flex justify-center">
                    <div class="flex flex-wrap justify-center space-x-8">
                        <a href="/" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            Home
                        </a>
                        <a href="/about-us" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            About Us
                        </a>
                        <a href="/packages" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            Packages
                        </a>
                        <a href="/coaches" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            Coaches
                        </a>
                        <a href="/schedule" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            Schedule
                        </a>
                        <a href="/contact-us" class="text-amber-100 hover:text-white px-4 py-2 text-lg font-semibold border-b-2 border-transparent hover:border-amber-300 transition-all">
                            Contact
                        </a>
                    </div>
                </nav>
                
                <!-- Auth Links -->
                <div class="flex justify-center mt-4 space-x-4">
                    @auth
                        <a href="/dashboard" class="text-amber-200 hover:text-white text-sm">Member Dashboard</a>
                        <span class="text-amber-400">|</span>
                        <a href="/cms-admin" class="classic-button text-white px-4 py-1 rounded text-sm font-semibold">Admin Panel</a>
                    @else
                        <a href="/login" class="text-amber-200 hover:text-white text-sm">Member Login</a>
                        <span class="text-amber-400">|</span>
                        <a href="/packages" class="classic-button text-white px-4 py-1 rounded text-sm font-semibold">Join Today</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="py-8">
            <div class="max-w-6xl mx-auto px-6">
                <div class="bg-white classic-shadow rounded-lg classic-border p-8">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Classic Footer -->
        <footer class="bg-gradient-to-r from-amber-900 via-yellow-800 to-amber-900 text-white classic-pattern mt-16">
            <div class="max-w-7xl mx-auto py-12 px-6">
                <!-- Ornamental Divider -->
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="h-px bg-amber-600 flex-1"></div>
                        <span class="ornament text-2xl">❦</span>
                        <div class="h-px bg-amber-600 flex-1"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <h3 class="classic-serif text-2xl font-bold text-amber-100 mb-4">Our Heritage</h3>
                        <p class="text-amber-200 leading-relaxed">
                            Established with a commitment to excellence, we honor the timeless traditions of strength training while embracing modern fitness science.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="classic-serif text-2xl font-bold text-amber-100 mb-4">Services</h3>
                        <ul class="space-y-2 text-amber-200">
                            <li>• Personal Training</li>
                            <li>• Group Classes</li>
                            <li>• Nutrition Guidance</li>
                            <li>• Wellness Programs</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="classic-serif text-2xl font-bold text-amber-100 mb-4">Contact Information</h3>
                        <div class="space-y-2 text-amber-200">
                            <p>📍 123 Heritage Lane<br>Classic City, CC 12345</p>
                            <p>📞 +1 (555) 123-HERO</p>
                            <p>✉️ info@superhero.wodworx.com</p>
                        </div>
                    </div>
                </div>
                
                <!-- Hours Section -->
                <div class="mt-12 pt-8 border-t border-amber-600">
                    <div class="text-center">
                        <h3 class="classic-serif text-xl font-bold text-amber-100 mb-4">Hours of Operation</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
                            <div class="bg-amber-800/30 p-4 rounded-lg">
                                <p class="text-amber-100 font-semibold">Monday - Friday</p>
                                <p class="text-amber-200">5:00 AM - 10:00 PM</p>
                            </div>
                            <div class="bg-amber-800/30 p-4 rounded-lg">
                                <p class="text-amber-100 font-semibold">Saturday - Sunday</p>
                                <p class="text-amber-200">7:00 AM - 8:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Copyright -->
                <div class="mt-8 pt-6 border-t border-amber-600 text-center">
                    <p class="text-amber-300 text-sm">
                        <span class="ornament">❦</span>
                        &copy; {{ date('Y') }} {{ config('app.name', 'SuperHero CrossFit') }}. A Legacy of Excellence.
                        <span class="ornament">❦</span>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
