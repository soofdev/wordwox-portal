<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <!-- Environment Indicator -->
    <x-environment-indicator />

    <!-- Clean header with minimal branding and logout -->
    <div class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2" wire:navigate>
                        <x-app-logo />
                    </a>
                </div>

                <!-- User menu -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <span>{{ auth()->user()->name }}</span>
                        <span class="text-zinc-400">•</span>
                        <span>{{ auth()->user()->email }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle">
                            {{ __('Log Out') }}
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content area -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    @fluxScripts
</body>
</html>
