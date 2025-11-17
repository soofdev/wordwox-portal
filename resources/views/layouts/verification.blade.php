<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Verification' }} - {{ $org->name ?? 'Wodworx' }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom Styles -->
    <style>
        /* Custom loading animation */
        .pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Custom gradient background */
        .bg-gradient-gym {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Password strength colors */
        .strength-very-weak {
            background-color: #ef4444;
        }

        .strength-weak {
            background-color: #f59e0b;
        }

        .strength-good {
            background-color: #3b82f6;
        }

        .strength-strong {
            background-color: #10b981;
        }

    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">
    <!-- Background Pattern -->
    <div class="fixed inset-0 bg-gradient-gym opacity-5 pointer-events-none"></div>

    <!-- Main Content -->
    <main class="relative z-10">
        @yield('content')
    </main>

    <!-- Toast Notifications -->
    @if(session('success'))
    <div id="success-toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div id="error-toast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    </div>
    @endif

    <!-- Scripts -->
    <script>
        // Show toast notifications
        document.addEventListener('DOMContentLoaded', function() {
            const successToast = document.getElementById('success-toast');
            const errorToast = document.getElementById('error-toast');

            if (successToast) {
                setTimeout(() => {
                    successToast.classList.remove('translate-x-full');
                }, 100);
                setTimeout(() => {
                    successToast.classList.add('translate-x-full');
                }, 4000);
            }

            if (errorToast) {
                setTimeout(() => {
                    errorToast.classList.remove('translate-x-full');
                }, 100);
                setTimeout(() => {
                    errorToast.classList.add('translate-x-full');
                }, 4000);
            }
        });

        // Global utility functions
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');

            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

    </script>

    @stack('scripts')
</body>
</html>
