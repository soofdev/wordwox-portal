<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Confirm Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Please confirm your password before continuing.
            </p>
        </div>
        <form class="mt-8 space-y-6" wire:submit="confirmPassword">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input wire:model="form.password" id="password" name="password" type="password" autocomplete="current-password" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                       placeholder="Password">
                @error('form.password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>