<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px] flex-shrink-0">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.appearance')" wire:navigate>{{ __('gym.Appearance') }}</flux:navlist.item>
            @if(optional(auth()->user()->orgUser)?->safeHasPermissionTo('manage org terms'))
            <flux:navlist.item :href="route('settings.org-terms')" wire:navigate>{{ __('gym.Organization Terms') }}</flux:navlist.item>
            @endif
            @php
                $rbacService = app(\App\Services\RbacService::class);
                $orgUser = auth()->user()->orgUser;
            @endphp
            @if($orgUser && $rbacService->hasRole($orgUser, 'Admin'))
            <flux:navlist.item :href="route('setup.roles')" :current="request()->routeIs('setup.*')" wire:navigate>{{ __('gym.Roles & Permissions') }}</flux:navlist.item>
            @endif

            @if(auth()->user()->orgUser?->org?->orgSettingsFeatures?->isLanguageFeatureEnabled())
            <flux:navlist.item :href="route('settings.language-settings')" wire:navigate>{{ __('gym.Languages') }}</flux:navlist.item>
            @endif
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6 min-w-0">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full">
            {{ $slot }}
        </div>
    </div>
</div>
