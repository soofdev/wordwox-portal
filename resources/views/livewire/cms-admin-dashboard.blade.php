<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your content and pages</p>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon name="document-text" class="h-8 w-8 text-blue-600" />
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pages</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_pages'] ?? 0 }}</dd>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon name="eye" class="h-8 w-8 text-green-600" />
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Published</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['published_pages'] ?? 0 }}</dd>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon name="pencil" class="h-8 w-8 text-yellow-600" />
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Drafts</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['draft_pages'] ?? 0 }}</dd>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <flux:icon name="squares-2x2" class="h-8 w-8 text-purple-600" />
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sections</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_sections'] ?? 0 }}</dd>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Pages --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Pages</h3>
                        @if(Route::has('cms.pages.index'))
                            <flux:button href="{{ route('cms.pages.index') }}" variant="subtle" size="sm">
                                View All
                            </flux:button>
                        @endif
                    </div>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($recentPages as $page)
                        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($page->status === 'published')
                                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                            @elseif($page->status === 'draft')
                                                <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                            @else
                                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $page->title }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $page->type }} • {{ $page->updated_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 flex space-x-2">
                                    @if(Route::has('cms.pages.edit'))
                                        <flux:button href="{{ route('cms.pages.edit', $page) }}" variant="ghost" size="sm" icon="pencil">
                                            Edit
                                        </flux:button>
                                    @endif
                                    @if(Route::has('cms.page'))
                                        <flux:button href="{{ route('cms.page', $page->slug) }}" variant="ghost" size="sm" icon="eye">
                                            View
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <flux:icon name="document-text" class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No pages</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first page.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Portal Info --}}
        <div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Portal Info</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Organization ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $orgId }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Portal ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $portalId }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ now()->format('M j, Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
