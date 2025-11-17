<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Verify Email Address
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                A new verification link has been sent to the email address you provided during registration.
            </div>
        @endif

        <div class="mt-8 space-y-6">
            <div>
                <button wire:click="sendVerification" type="button" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Resend Verification Email
                </button>
            </div>

            <div class="text-center">
                <button wire:click="logout" type="button" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Log Out
                </button>
            </div>
        </div>
    </div>
</div>