<x-layouts.clean>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <!-- Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>

                <!-- Heading -->
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    {{ __('gym.Permission Required') }}
                </h2>

                <!-- Description -->
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('gym.The permission system is not properly configured for your account.') }}
                </p>
            </div>

            <!-- Error Details -->
            <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('gym.System Configuration Issue') }}
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>{{ __('gym.The required permissions have not been set up in the system yet. This typically happens when the system is first installed or after updates.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                <div class="flex items-center justify-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="mr-2 -ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ __('gym.Back to Dashboard') }}
                    </a>
                </div>

                <!-- Contact Support -->
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('gym.Need help?') }}
                        <a href="mailto:support@wodworx.com" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            {{ __('gym.Contact Technical Support') }}
                        </a>
                    </p>
                </div>

                <!-- Technical Details (for admins) -->
                @if(auth()->user()?->orgUser?->isAdmin || auth()->user()?->orgUser?->isOwner)
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                        {{ __('gym.Technical Details') }}
                    </summary>
                    <div class="mt-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-md">
                        <p class="text-xs text-gray-600 dark:text-gray-400 font-mono">
                            {{ __('gym.To resolve this issue, an administrator needs to run:') }}<br>
                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded mt-1 inline-block">
                                php artisan db:seed --class=FohPermissionSeeder
                            </code>
                        </p>
                    </div>
                </details>
                @endif
            </div>
        </div>
    </div>
</x-layouts.clean>
