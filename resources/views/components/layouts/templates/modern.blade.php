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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('head')

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .modern-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }
        
        .glass-effect {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .modern-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .neon-glow {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="font-sans antialiased h-full bg-gray-900 text-white">
    <div class="min-h-full modern-gradient">
        <!-- Modern Glass Navigation -->
        <nav class="fixed top-4 left-4 right-4 z-50 glass-effect rounded-2xl">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <a href="/" class="text-2xl font-bold text-white neon-glow">
                                ⚡ {{ config('app.name', 'SuperHero CrossFit') }}
                            </a>
                        </div>

                        <div class="hidden md:ml-8 md:flex md:space-x-6">
                            <a href="/" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🏠 Home</a>
                            <a href="/about-us" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">👥 About</a>
                            <a href="/packages" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">💎 Packages</a>
                            <a href="/coaches" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🏋️ Coaches</a>
                            <a href="/schedule" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📅 Schedule</a>
                            <a href="/contact-us" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📞 Contact</a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="/dashboard" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📊 Dashboard</a>
                            <a href="/cms-admin" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-purple-600 hover:to-pink-600 transition-all neon-glow">⚙️ CMS</a>
                        @else
                            <a href="/login" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🔐 Login</a>
                            <a href="/packages" class="bg-gradient-to-r from-blue-500 to-purple-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-blue-600 hover:to-purple-600 transition-all neon-glow">🚀 Get Started</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content with top padding for fixed nav -->
        <main class="pt-24 pb-16">
            {{ $slot }}
        </main>

        <!-- Modern Footer -->
        <footer class="bg-black/30 backdrop-blur-lg border-t border-white/10">
            <div class="max-w-7xl mx-auto py-16 px-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <h3 class="text-2xl font-bold text-white mb-4 floating-animation">
                            ⚡ {{ config('app.name', 'SuperHero CrossFit') }}
                        </h3>
                        <p class="text-white/80 mb-6 text-lg">Experience the future of fitness with our cutting-edge training programs and modern facilities.</p>
                        <div class="flex space-x-4">
                            <a href="#" class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                📘
                            </a>
                            <a href="#" class="w-12 h-12 bg-gradient-to-r from-pink-500 to-red-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                📷
                            </a>
                            <a href="#" class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                🐦
                            </a>
                        </div>
                    </div>
                    
                    <div class="modern-card p-6 rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">🔗 Quick Links</h3>
                        <ul class="space-y-3">
                            <li><a href="/about-us" class="text-gray-700 hover:text-blue-600 transition-colors">About Us</a></li>
                            <li><a href="/packages" class="text-gray-700 hover:text-blue-600 transition-colors">Packages</a></li>
                            <li><a href="/coaches" class="text-gray-700 hover:text-blue-600 transition-colors">Coaches</a></li>
                            <li><a href="/schedule" class="text-gray-700 hover:text-blue-600 transition-colors">Schedule</a></li>
                        </ul>
                    </div>
                    
                    <div class="modern-card p-6 rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📍 Contact Info</h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-center">
                                <span class="mr-2">🏢</span>
                                123 Future Street
                            </li>
                            <li class="flex items-center">
                                <span class="mr-2">📞</span>
                                +1 (555) 123-HERO
                            </li>
                            <li class="flex items-center">
                                <span class="mr-2">✉️</span>
                                info@superhero.wodworx.com
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-12 pt-8 border-t border-white/20">
                    <p class="text-center text-white/60 text-sm">
                        &copy; {{ date('Y') }} {{ config('app.name', 'SuperHero CrossFit') }}. Powered by the future. ⚡
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
