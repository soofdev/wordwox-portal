<flux:card class="w-full space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">Sign in to your account</flux:heading>
        <flux:text variant="muted">Welcome to Wodworx Portal</flux:text>
    </div>

    <form wire:submit="login" class="space-y-6">
        <div class="space-y-3">
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" placeholder="Enter your email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input wire:model="password" type="password" placeholder="Enter your password" />
                <flux:error name="password" />
            </flux:field>

            <div class="flex items-center justify-between">
                <flux:checkbox wire:model="remember">Remember me</flux:checkbox>
                <flux:link href="{{ route('password.request') }}" class="text-sm">
                    Forgot password?
                </flux:link>
            </div>
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            Sign in
        </flux:button>
    </form>
</flux:card>