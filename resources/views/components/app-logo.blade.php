@if(auth()->check() && auth()->user()->orgUser && auth()->user()->orgUser->org)
@php $currentOrg = auth()->user()->orgUser->org; @endphp

<!-- Organization Logo -->
@if($currentOrg->logoFilePath)
<div class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
    <img src="{{ Storage::disk('s3')->url($currentOrg->logoFilePath) }}" alt="{{ $currentOrg->name }}" class="w-full h-full object-contain">
</div>
@else
<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-zinc-100 dark:bg-slate-600">
    <!-- Building Office Icon -->
    <svg class="size-5 text-zinc-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 21h19.5m-18-18v18m2.25-18v18m13.5-18v18m2.25-18v18M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75M9 10.5h1.5m-1.5 2.25h1.5M21 15.75V8.25h-1.5M21 12h-1.5"></path>
    </svg>
</div>
@endif

<!-- Organization Name -->
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ $currentOrg->name }}</span>
</div>
@else
<!-- Fallback to default logo when not authenticated or no org selected -->
<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
    <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">Laravel Starter Kit</span>
</div>
@endif
