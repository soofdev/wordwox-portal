<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <!-- Flash Messages -->
    @if (session()->has('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
        {{ session('error') }}
    </div>
    @endif

    @if (session()->has('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
        {{ session('success') }}
    </div>
    @endif

    <!-- Welcome Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Welcome {{ auth()->user()?->name ?? 'Guest' }}!
                </h1>
                <p class="text-gray-600 mt-1">
                    Wodworx Portal Dashboard
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</p>
                <p class="text-sm text-gray-500">{{ now()->format('g:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid gap-4 md:grid-cols-3">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        {{ \App\Models\User::count() }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Sessions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">1</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">System Status</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">Online</p>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Content -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Getting Started</h2>
        <div class="prose text-gray-600">
            <p>Welcome to your Wodworx Portal! This is a clean, minimal dashboard ready for your custom features.</p>
            <p class="mt-4">Available features:</p>
            <ul class="mt-2 list-disc list-inside">
                <li>User Authentication (Login, Register, Password Reset)</li>
                <li>User Dashboard</li>
                <li>Profile Management</li>
            </ul>
        </div>
    </div>
</div>
