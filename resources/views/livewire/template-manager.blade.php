<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Template Manager</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Apply templates to multiple pages at once</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Template Selection & Actions --}}
    <flux:card>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Template to Apply</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($this->templates as $templateKey => $template)
                    <button
                        wire:click="$set('selectedTemplate', '{{ $templateKey }}')"
                        class="p-4 border-2 rounded-lg transition-all text-left {{ $selectedTemplate === $templateKey ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600' }}"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $template['icon'] }}</span>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $template['name'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $template['description'] }}</div>
                            </div>
                            @if($selectedTemplate === $templateKey)
                                <div class="ml-auto text-blue-600 dark:text-blue-400">
                                    <flux:icon name="check-circle" class="w-6 h-6" />
                                </div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <flux:button 
                    wire:click="applyTemplateToSelected" 
                    variant="primary"
                    icon="check"
                    :disabled="empty($selectedPages)"
                >
                    Apply to Selected ({{ count($selectedPages) }})
                </flux:button>
                
                <flux:button 
                    wire:click="applyTemplateToAll" 
                    wire:confirm="Are you sure you want to apply this template to ALL filtered pages?"
                    variant="outline"
                    icon="globe-alt"
                >
                    Apply to All Filtered Pages
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
            <div>
                <flux:select wire:model.live="templateFilter">
                    <option value="all">All Templates</option>
                    @foreach($this->templates as $templateKey => $template)
                        <option value="{{ $templateKey }}">{{ $template['name'] }}</option>
                    @endforeach
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
            <flux:table.column>
                <flux:checkbox 
                    wire:model.live="selectAll"
                />
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDirection" wire:click="sort('title')">Page</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">Type</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'template'" :direction="$sortDirection" wire:click="sort('template')">Template</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'updated_at'" :direction="$sortDirection" wire:click="sort('updated_at')">Updated</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($pages as $page)
                <flux:table.row :key="$page->id">
                    <flux:table.cell>
                        <flux:checkbox 
                            wire:model="selectedPages" 
                            value="{{ $page->id }}"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $page->title }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">/{{ $page->slug }}</div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge 
                            variant="{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'warning' : 'secondary') }}"
                            size="sm"
                        >
                            {{ ucfirst($page->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($page->type) }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $this->getTemplatesProperty()[$page->template ?? 'modern']['icon'] ?? '🚀' }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $this->getTemplateName($page->template ?? 'modern') }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $page->updated_at ? \Carbon\Carbon::createFromTimestamp($page->updated_at)->diffForHumans() : 'N/A' }}
                        </span>
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
                @if($search || $statusFilter !== 'all' || $typeFilter !== 'all' || $templateFilter !== 'all')
                    Try adjusting your search criteria.
                @else
                    No pages available.
                @endif
            </p>
        </div>
    @endif
</div>

