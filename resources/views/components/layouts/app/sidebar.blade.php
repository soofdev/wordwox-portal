@php
use App\Services\LanguageCssService;
$htmlAttributes = LanguageCssService::getHtmlAttributes();
$languageClasses = LanguageCssService::getLanguageCssClasses();
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlAttributes['lang'] }}" dir="{{ $htmlAttributes['dir'] }}" class="dark {{ $languageClasses }}">
<head>
    @include('partials.head')
    {!! LanguageCssService::generateInlineCss() !!}
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 {{ $languageClasses }}">
    <!-- Environment Indicator -->
    <x-environment-indicator />

    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <!-- 📊 Dashboard -->
            @if(Route::has('cms.dashboard'))
                <flux:navlist.item icon="chart-bar" :href="route('cms.dashboard')" :current="request()->routeIs('cms.dashboard')" wire:navigate> Dashboard</flux:navlist.item>
            @else
                <flux:navlist.item icon="chart-bar" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate> Dashboard</flux:navlist.item>
            @endif

            <!-- 📄 Pages -->
            @if(Route::has('cms.pages.index'))
                <flux:navlist.item icon="document-text" :href="route('cms.pages.index')" :current="request()->routeIs('cms.pages.*')" wire:navigate> Pages</flux:navlist.item>
            @endif

            <!-- 🎨 Templates -->
            @if(Route::has('cms.templates'))
                <flux:navlist.item icon="swatch" :href="route('cms.templates')" :current="request()->routeIs('cms.templates')" wire:navigate> Templates</flux:navlist.item>
            @endif

            <!-- 🎨 Template Manager -->
            @if(Route::has('cms.templates.manager'))
                <flux:navlist.item icon="adjustments-horizontal" :href="route('cms.templates.manager')" :current="request()->routeIs('cms.templates.manager')" wire:navigate> Template Manager</flux:navlist.item>
            @endif

            <!-- 🧩 Sections -->
            @if(Route::has('cms.sections.index'))
                <flux:navlist.item icon="squares-2x2" :href="route('cms.sections.index')" :current="request()->routeIs('cms.sections.*')" wire:navigate> Sections</flux:navlist.item>
            @endif

            <!-- 🖼 Media -->
            @if(Route::has('cms.media.index'))
                <flux:navlist.item icon="photo" :href="route('cms.media.index')" :current="request()->routeIs('cms.media.*')" wire:navigate> Media</flux:navlist.item>
            @endif

            <!-- 👤 Users -->
            @if(Route::has('users.index'))
                <flux:navlist.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate> Users</flux:navlist.item>
            @endif

            <!-- ⚙️ Settings -->
            @if(Route::has('cms.settings.general'))
                <flux:navlist.item icon="cog-6-tooth" :href="route('cms.settings.general')" :current="request()->routeIs('cms.settings.*')" wire:navigate> Settings</flux:navlist.item>
            @endif

            <!-- 📜 Logs -->
            @if(Route::has('logs.index'))
                <flux:navlist.item icon="document-text" :href="route('logs.index')" :current="request()->routeIs('logs.*')" wire:navigate> Logs</flux:navlist.item>
            @endif

        </flux:navlist>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->fullName ?? auth()->user()->name" :initials="auth()->user()->orgUser->initials ?? 'U'" icon-trailing="chevron-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->orgUser->initials ?? 'U' }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->fullName ?? auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        Logout
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->orgUser->initials ?? 'U'" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->orgUser->initials ?? 'U' }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->fullName ?? auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        Logout
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts

    <!-- Flash Messages Component -->
    <x-flash-messages />
</body>
</html>