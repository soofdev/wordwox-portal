@props(['membership'])

<!-- Hold Details Header Component with Back to All Holds -->
<div class="flex items-center gap-4 mb-12">
    <!-- Back to All Holds Button -->
    <a href="{{ route('subscriptions.holds') }}"
       class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-8 text-sm rounded-md px-3 inline-flex bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-800 dark:text-white transition-colors duration-200"
       data-flux-button="data-flux-button"
       wire:navigate>
        <svg class="shrink-0 [:where(&)]:size-4" data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z" clip-rule="evenodd"></path>
        </svg>
        <span>{{ __('subscriptions.Membership Holds') }}</span>
    </a>

    <!-- Hold Context Info -->
{{--    <div class="flex items-center gap-3">--}}
{{--        <div class="h-6 w-px bg-zinc-300 dark:bg-zinc-600"></div>--}}
{{--        <div class="flex items-center gap-2">--}}
{{--            <span class="text-sm font-medium text-zinc-900 dark:text-white">--}}
{{--                {{ trim($membership->orgUser->fullName ?? 'Member Name') }}--}}
{{--            </span>--}}
{{--            --}}
{{--        </div>--}}
{{--    </div>--}}
</div>
