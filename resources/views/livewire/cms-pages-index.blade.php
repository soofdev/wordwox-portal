<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">All Pages</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your CMS pages</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <flux:input 
                    wire:model.live="search" 
                    placeholder="Search pages..." 
                    icon="magnifying-glass"
                />
            </div>
            <div>
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="typeFilter">
                    <option value="all">All Types</option>
                    <option value="page">Page</option>
                    <option value="post">Post</option>
                    <option value="home">Home</option>
                    <option value="about">About</option>
                    <option value="contact">Contact</option>
                    <option value="custom">Custom</option>
                </flux:select>
            </div>
            <div class="flex justify-end">
                <flux:button wire:click="$refresh" variant="outline" icon="arrow-path">
                    Refresh
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Pages Table --}}
    <flux:table :paginate="$pages">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'sort_order'" :direction="$sortDirection" wire:click="sort('sort_order')">Order</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">Page</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">Type</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'updated_at'" :direction="$sortDirection" wire:click="sort('updated_at')">Modified</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
                    @forelse($pages as $page)
                <flux:table.row :key="$page->id">
                    <flux:table.cell>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->sort_order ?? 0 }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="flex items-center gap-3">
                                        @if($page->is_homepage)
                                            <flux:icon name="home" class="h-5 w-5 text-blue-500" />
                                        @else
                                            <flux:icon name="document-text" class="h-5 w-5 text-gray-400" />
                                        @endif
                        <div>
                            <div class="font-medium">{{ $page->title }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">/{{ $page->slug }}</div>
                                    </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge variant="outline" size="sm">{{ ucfirst($page->type) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                                @if($page->status === 'published')
                            <flux:badge color="green" size="sm" inset="top bottom">Published</flux:badge>
                                @elseif($page->status === 'draft')
                            <flux:badge color="yellow" size="sm" inset="top bottom">Draft</flux:badge>
                                @else
                            <flux:badge color="zinc" size="sm" inset="top bottom">Archived</flux:badge>
                                @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                                {{ $page->updated_at->diffForHumans() }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex items-center gap-2">
                            @if(Route::has('cms.page'))
                                    <flux:button href="{{ route('cms.page', $page->slug) }}" variant="ghost" size="sm" icon="eye">
                                        View
                                    </flux:button>
                            @endif
                            @if(Route::has('cms.pages.edit'))
                                    <flux:button href="{{ route('cms.pages.edit', $page) }}" variant="ghost" size="sm" icon="pencil">
                                        Edit
                                    </flux:button>
                            @endif
        
                                </div>
                    </flux:table.cell>
                </flux:table.row>
                    @empty
                {{-- Empty state will be handled by Flux table automatically --}}
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Custom empty state for when no pages match filters --}}
    @if($pages->isEmpty())
        <div class="text-center py-12">
                                <flux:icon name="document-text" class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No pages found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if($search || $statusFilter !== 'all' || $typeFilter !== 'all')
                                        Try adjusting your search criteria.
                                    @else
                    No pages available.
                                    @endif
                                </p>
            </div>
        @endif

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-50">
            <flux:card class="bg-green-50 border-green-200 text-green-800">
                {{ session('message') }}
            </flux:card>
        </div>
    @endif
</div>
