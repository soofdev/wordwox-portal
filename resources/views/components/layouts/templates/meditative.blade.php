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
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700|dancing-script:400,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('head')

    <style>
        body { font-family: 'Poppins', sans-serif; }
        .meditative-script { font-family: 'Dancing Script', cursive; }
        
        .meditative-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }
        
        .zen-gradient {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .peaceful-gradient {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        
        .meditation-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .meditation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .floating-meditation {
            animation: float-gentle 8s ease-in-out infinite;
        }
        
        @keyframes float-gentle {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .zen-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            opacity: 0.1;
            position: absolute;
            animation: zen-float 12s ease-in-out infinite;
        }
        
        @keyframes zen-float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
    </style>
</head>
<body class="font-sans antialiased h-full bg-gradient-to-br from-purple-50 via-pink-50 to-indigo-50 relative overflow-x-hidden">
    <!-- Zen Background Elements -->
    <div class="zen-circle" style="top: 10%; left: 10%;"></div>
    <div class="zen-circle" style="top: 60%; right: 15%; animation-delay: -4s;"></div>
    <div class="zen-circle" style="bottom: 20%; left: 20%; animation-delay: -8s;"></div>

    <div class="min-h-full relative z-10">
        <!-- Meditative Header -->
        <header class="bg-white/80 backdrop-blur-lg shadow-sm border-b border-purple-100">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <!-- Logo and Tagline -->
                <div class="text-center mb-6">
                    <h1 class="meditative-script text-4xl md:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">
                        {{ config('app.name', 'SuperHero CrossFit') }}
                    </h1>
                    <p class="text-gray-600 text-lg mt-2 font-light">Find Your Inner Strength • Embrace Mindful Movement</p>
                </div>
                
                <!-- Navigation -->
                <nav class="flex justify-center">
                    <div class="flex flex-wrap justify-center space-x-8">
                        <a href="/" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>Home</span>
                        </a>
                        <a href="/about-us" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>About</span>
                        </a>
                        <a href="/packages" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>Classes</span>
                        </a>
                        <a href="/coaches" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>Trainers</span>
                        </a>
                        <a href="/schedule" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>Schedule</span>
                        </a>
                        <a href="/contact-us" class="group flex items-center space-x-2 text-gray-700 hover:text-purple-600 px-4 py-2 text-lg font-medium transition-all duration-300">
                            <span class="w-2 h-2 bg-purple-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            <span>Contact</span>
                        </a>
                    </div>
                </nav>
                
                <!-- Auth Links -->
                <div class="flex justify-center mt-6 space-x-6">
                    @auth
                        <a href="/dashboard" class="text-gray-600 hover:text-purple-600 text-sm font-medium transition-colors">Member Portal</a>
                        <span class="text-gray-300">•</span>
                        <a href="/cms-admin" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-purple-600 hover:to-pink-600 transition-all shadow-lg">Admin</a>
                    @else
                        <a href="/login" class="text-gray-600 hover:text-purple-600 text-sm font-medium transition-colors">Member Login</a>
                        <span class="text-gray-300">•</span>
                        <a href="/packages" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-purple-600 hover:to-pink-600 transition-all shadow-lg">Begin Your Journey</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="py-12">
            <div class="max-w-7xl mx-auto px-6">
                {{ $slot }}
            </div>
        </main>

        <!-- Meditative Footer -->
        <footer class="peaceful-gradient text-gray-800 mt-20">
            <div class="max-w-7xl mx-auto py-16 px-6">
                <!-- Inspirational Quote Section -->
                <div class="text-center mb-12">
                    <div class="meditation-card p-8 rounded-2xl max-w-4xl mx-auto floating-meditation">
                        <blockquote class="meditative-script text-2xl md:text-3xl text-gray-700 mb-4">
                            "The body benefits from movement, and the mind benefits from stillness."
                        </blockquote>
                        <cite class="text-gray-600 font-medium">— Ancient Wisdom</cite>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <h3 class="meditative-script text-3xl font-bold text-gray-800 mb-4">
                            {{ config('app.name', 'SuperHero CrossFit') }}
                        </h3>
                        <p class="text-gray-700 leading-relaxed mb-6">
                            Discover the perfect balance of strength and serenity. Our mindful approach to fitness nurtures both body and spirit, 
                            creating a sanctuary where transformation happens naturally.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center text-purple-600 hover:bg-white hover:scale-110 transition-all">
                                🧘‍♀️
                            </a>
                            <a href="#" class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center text-purple-600 hover:bg-white hover:scale-110 transition-all">
                                🌸
                            </a>
                            <a href="#" class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center text-purple-600 hover:bg-white hover:scale-110 transition-all">
                                🕉️
                            </a>
                        </div>
                    </div>
                    
                    <div class="meditation-card p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">🌿 Mindful Classes</h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
                                Yoga Flow
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
                                Meditation Sessions
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
                                Mindful Movement
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
                                Breathwork
                            </li>
                        </ul>
                    </div>
                    
                    <div class="meditation-card p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">🏛️ Sacred Space</h3>
                        <div class="space-y-3 text-gray-700">
                            <p class="flex items-center">
                                <span class="mr-2">📍</span>
                                123 Serenity Lane
                            </p>
                            <p class="flex items-center">
                                <span class="mr-2">☎️</span>
                                +1 (555) 123-PEACE
                            </p>
                            <p class="flex items-center">
                                <span class="mr-2">✉️</span>
                                hello@superhero.wodworx.com
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Hours Section -->
                <div class="mt-12 pt-8 border-t border-white/30">
                    <div class="text-center">
                        <h3 class="meditative-script text-2xl font-bold text-gray-800 mb-6">Sacred Hours</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <div class="meditation-card p-6 rounded-xl">
                                <p class="font-semibold text-gray-800 mb-2">Monday - Friday</p>
                                <p class="text-gray-700">6:00 AM - 9:00 PM</p>
                                <p class="text-sm text-gray-600 mt-2">Morning meditation at sunrise</p>
                            </div>
                            <div class="meditation-card p-6 rounded-xl">
                                <p class="font-semibold text-gray-800 mb-2">Saturday - Sunday</p>
                                <p class="text-gray-700">7:00 AM - 7:00 PM</p>
                                <p class="text-sm text-gray-600 mt-2">Extended weekend sessions</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Copyright -->
                <div class="mt-12 pt-8 border-t border-white/30 text-center">
                    <p class="text-gray-600 text-sm">
                        <span class="meditative-script text-lg">✨</span>
                        &copy; {{ date('Y') }} {{ config('app.name', 'SuperHero CrossFit') }}. Nurturing transformation with love.
                        <span class="meditative-script text-lg">✨</span>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
