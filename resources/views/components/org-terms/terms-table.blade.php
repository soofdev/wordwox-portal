@props(['hasTerms', 'filteredTerms', 'search', 'filterActive'])

<!-- Terms Table using Flux Table Components -->
@if ($hasTerms)
    <div class="space-y-4">
        <!-- Flux Table Component -->
        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                    <div class="flex items-center gap-2">
                        <flux:icon.document-text class="w-4 h-4" />
                        {{ __('gym.Title') }}
                    </div>
                </flux:table.column>
                <flux:table.column>
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="w-4 h-4" />
                        {{ __('gym.Status') }}
                    </div>
                </flux:table.column>
                <flux:table.column>
                    <div class="flex items-center gap-2">
                        <flux:icon.tag class="w-4 h-4" />
                        {{ __('gym.Version') }}
                    </div>
                </flux:table.column>
                <flux:table.column>
                    <div class="flex items-center gap-2">
                        <flux:icon.calendar class="w-4 h-4" />
                        {{ __('gym.Effective Date') }}
                    </div>
                </flux:table.column>
                <flux:table.column align="end">{{ __('gym.Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($filteredTerms as $term)
                    <flux:table.row wire:key="term-{{ $term->id }}" class="{{ $term->trashed() ? 'opacity-75' : '' }}">
                        <flux:table.cell>
                            <div class="min-w-0">
                                <flux:text class="font-medium {{ $term->trashed() ? 'line-through text-red-600' : '' }}">
                                    {{ $term->title }}
                                </flux:text>
                                @if($term->trashed())
                                    <flux:text variant="muted" class="text-xs block mt-1">
                                        {{ __('gym.Deleted') }} {{ $term->deleted_at->diffForHumans() }}
                                    </flux:text>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                @if($term->trashed())
                                    <flux:badge size="sm" color="red" inset="top bottom">{{ __('gym.Deleted') }}</flux:badge>
                                @else
                                    @php
                                        $isActive = $term->is_active;
                                        $badgeStatus = $isActive ? __('gym.Active') : __('gym.Inactive');
                                        $badgeColor = $isActive ? 'green' : 'zinc';
                                    @endphp
                                    
                                    <flux:switch 
                                        wire:click="toggleActive({{ $term->id }})" 
                                        {{ $isActive ? 'checked' : '' }}
                                        size="sm"
                                        title="{{ __('gym.Toggle') }} {{ $badgeStatus }} {{ __('gym.status') }}"
                                    />
                                    
                                    <flux:badge 
                                        size="sm" 
                                        color="{{ $badgeColor }}" 
                                        inset="top bottom"
                                    >
                                        {{ $badgeStatus }}
                                    </flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge variant="outline" class="font-mono">
                                v{{ $term->version }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text>{{ $term->effective_date->format('M j, Y') }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-2">
                                @if($term->trashed())
                                    <flux:button 
                                        wire:click="restore({{ $term->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        icon="arrow-path"
                                    >
                                        {{ __('gym.Restore') }}
                                    </flux:button>
                                @else
                                    <flux:button 
                                        wire:click="edit({{ $term->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        icon="pencil"
                                    >
                                        {{ __('gym.Edit') }}
                                    </flux:button>
                                    
                                    <flux:button 
                                        wire:click="duplicate({{ $term->id }})" 
                                        variant="ghost" 
                                        size="sm"
                                        icon="document-duplicate"
                                    >
                                        {{ __('gym.Clone') }}
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center">
                                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-2xl mb-4">
                                    <flux:icon.document-text class="w-8 h-8 text-zinc-400 dark:text-zinc-500" />
                                </div>
                                <flux:heading class="mb-2 text-zinc-900 dark:text-white">{{ __('gym.No terms found') }}</flux:heading>
                                @if($search || $filterActive !== 'all')
                                    <flux:text class="mb-6 text-center max-w-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('gym.No terms match your current search or filter criteria. Try adjusting your filters or search terms.') }}
                                    </flux:text>
                                    <flux:button wire:click="clearSearch" variant="primary" icon="arrow-path">
                                        {{ __('gym.Clear filters') }}
                                    </flux:button>
                                @else
                                    <flux:text class="mb-6 text-center max-w-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('gym.Create your first terms of service document.') }}
                                    </flux:text>
                                    <flux:button wire:click="toggleForm" variant="primary" icon="plus">
                                        {{ __('gym.Create Your First Terms') }}
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        <!-- Flux Pagination -->
        @if($filteredTerms->hasPages())
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                <!-- Pagination Info -->
                <flux:text variant="muted" class="text-sm">
                    {{ __('gym.Showing') }} {{ $filteredTerms->firstItem() ?? 0 }} {{ __('gym.to') }} {{ $filteredTerms->lastItem() ?? 0 }} {{ __('gym.of') }} {{ $filteredTerms->total() }} {{ __('gym.results') }}
                </flux:text>
                
                <!-- Pagination Controls -->
                <div class="flex items-center gap-2">
                    <!-- Previous Button -->
                    @if($filteredTerms->onFirstPage())
                        <flux:button variant="ghost" size="sm" disabled icon="chevron-left">
                            {{ __('gym.Previous') }}
                        </flux:button>
                    @else
                        <flux:button 
                            wire:click="goToPreviousPage" 
                            variant="ghost" 
                            size="sm" 
                            icon="chevron-left"
                        >
                            {{ __('gym.Previous') }}
                        </flux:button>
                    @endif
                    
                    <!-- Page Numbers -->
                    <div class="flex items-center gap-1">
                        @php
                            $currentPage = $filteredTerms->currentPage();
                            $lastPage = $filteredTerms->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                        @endphp
                        
                        <!-- First page -->
                        @if($startPage > 1)
                            <flux:button 
                                wire:click="gotoPage(1)" 
                                variant="{{ $currentPage == 1 ? 'primary' : 'ghost' }}" 
                                size="sm"
                                class="min-w-[2.5rem]"
                            >
                                1
                            </flux:button>
                            @if($startPage > 2)
                                <flux:text variant="muted" class="px-2">...</flux:text>
                            @endif
                        @endif
                        
                        <!-- Page range -->
                        @for($page = $startPage; $page <= $endPage; $page++)
                            <flux:button 
                                wire:click="gotoPage({{ $page }})" 
                                variant="{{ $currentPage == $page ? 'primary' : 'ghost' }}" 
                                size="sm"
                                class="min-w-[2.5rem]"
                            >
                                {{ $page }}
                            </flux:button>
                        @endfor
                        
                        <!-- Last page -->
                        @if($endPage < $lastPage)
                            @if($endPage < $lastPage - 1)
                                <flux:text variant="muted" class="px-2">...</flux:text>
                            @endif
                            <flux:button 
                                wire:click="gotoPage({{ $lastPage }})" 
                                variant="{{ $currentPage == $lastPage ? 'primary' : 'ghost' }}" 
                                size="sm"
                                class="min-w-[2.5rem]"
                            >
                                {{ $lastPage }}
                            </flux:button>
                        @endif
                    </div>
                    
                    <!-- Next Button -->
                    @if($filteredTerms->hasMorePages())
                        <flux:button 
                            wire:click="goToNextPage" 
                            variant="ghost" 
                            size="sm" 
                            icon-trailing="chevron-right"
                        >
                            {{ __('gym.Next') }}
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" disabled icon-trailing="chevron-right">
                            {{ __('gym.Next') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </div>
@else
    <!-- Empty State for No Terms at All -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12">
        <div class="text-center">
            <flux:icon.document-text class="mx-auto w-12 h-12 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl flex items-center justify-center mt-16 mb-8 text-blue-600 dark:text-blue-400" />
            <flux:heading class="text-2xl font-bold text-zinc-900 dark:text-white mb-3">{{ __('gym.No terms created yet') }}</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400 mb-8 max-w-md mx-auto">
                {{ __('gym.Get started by creating your organization\'s first terms of service document.') }}
            </flux:text>
        </div>
    </div>
@endif