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
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Additional head content -->
    @stack('head')
</head>
<body class="font-sans antialiased h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <a href="/" class="text-xl font-bold text-gray-900">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="/" class="text-gray-900 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Home
                            </a>
                            <a href="/about-us" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                About Us
                            </a>
                            <a href="/packages" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Packages
                            </a>
                            <a href="/coaches" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Coaches
                            </a>
                            <a href="/schedule" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Schedule
                            </a>
                            <a href="/contact-us" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Contact
                            </a>
                        </div>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="/dashboard" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="/cms-admin" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                                CMS Admin
                            </a>
                        @else
                            <a href="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="sm:hidden">
                <button type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ config('app.name', 'Laravel') }}</h3>
                        <p class="text-gray-600 mb-4">
                            Unleash your inner strength at SuperHero CrossFit - where everyone has the potential to achieve greatness.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Facebook</span>
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Instagram</span>
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987s11.987-5.367 11.987-11.987C24.014 5.367 18.647.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.316-1.296C4.165 14.81 3.662 13.659 3.662 12.362s.503-2.448 1.471-3.316c.868-.806 2.019-1.296 3.316-1.296s2.448.49 3.316 1.296c.968.868 1.471 2.019 1.471 3.316s-.503 2.448-1.471 3.316c-.868.806-2.019 1.296-3.316 1.296zm7.718-9.038c-.806 0-1.471-.665-1.471-1.471s.665-1.471 1.471-1.471 1.471.665 1.471 1.471-.665 1.471-1.471 1.471z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="/about-us" class="text-gray-600 hover:text-gray-900">About Us</a></li>
                            <li><a href="/packages" class="text-gray-600 hover:text-gray-900">Packages</a></li>
                            <li><a href="/coaches" class="text-gray-600 hover:text-gray-900">Coaches</a></li>
                            <li><a href="/schedule" class="text-gray-600 hover:text-gray-900">Schedule</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">Contact</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li>123 Hero Street</li>
                            <li>Fitness City, FC 12345</li>
                            <li>+1 (555) 123-HERO</li>
                            <li>info@superhero.wodworx.com</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-center text-gray-400 text-sm">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>