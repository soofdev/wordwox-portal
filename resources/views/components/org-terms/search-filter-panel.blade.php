@props(['search', 'filterActive', 'filteredTerms'])

<!-- Enhanced Search and Filter Panel - Separate Sections -->
<div class="w-full mb-8 space-y-6">
    <!-- Top Action Section -->
    <div class="flex items-center justify-end">
        <!-- Create Button -->
        <flux:button 
            wire:click="toggleForm" 
            variant="primary" 
            icon="plus"
        >
            {{ __('gym.Create New Terms') }}
        </flux:button>
    </div>

    <!-- Filter Controls Section -->
    <div class="flex items-center gap-4">
        <!-- Search Input -->
        <div class="flex-1">
            <flux:input 
                wire:model.live="search" 
                type="text" 
                placeholder="{{ __('gym.Search by title, version, or content...') }}"
                icon="magnifying-glass"
                clearable
            />
        </div>
        
        <!-- Status Filter -->
        <div class="w-48">
            <flux:select wire:model.live="filterActive" class="w-full">
                <option value="all">{{ __('gym.All Terms') }}</option>
                <option value="active">{{ __('gym.Active Only') }}</option>
                <option value="inactive">{{ __('gym.Inactive Only') }}</option>
            </flux:select>
        </div>
    </div>
</div>