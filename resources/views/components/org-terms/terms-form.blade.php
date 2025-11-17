@props(['editingId', 'title', 'version', 'effective_date', 'is_active', 'content'])

<!-- Enhanced Terms Form using Flux Design System -->
<div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden max-w-6xl mx-auto">

    
    <form wire:submit.prevent="{{ $editingId ? 'update' : 'create' }}" class="p-10 bg-white dark:bg-zinc-900">
        <!-- Delete Button at Top (Edit Mode Only) -->
        @if($editingId)
            <div class="mb-8 flex justify-end">
                <flux:button 
                    wire:click="confirmDelete({{ $editingId }})" 
                    variant="outline" 
                    icon="trash"
                    style="border-color: #ef4444; color: #ef4444;"
                    class="hover:!bg-red-500 hover:!text-white hover:!border-red-500"
                >
                    {{ __('gym.Delete Terms') }}
                </flux:button>
            </div>
        @endif

        <!-- Basic Information Section -->
        <div class="mb-10">
            <div class="mb-6">
                <flux:heading class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('gym.Basic Information') }}</flux:heading>
            </div>
            
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Title -->
                <div class="lg:col-span-2">
                    <flux:input 
                        wire:model="title" 
                        label="{{ __('gym.Document Title') }}" 
                        placeholder="{{ __('gym.e.g., Terms of Service, Privacy Policy, User Agreement') }}"
                        class="text-sm"
                    />
                    <div class="mt-1">
                        <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                            {{ __('gym.Choose a clear, descriptive title for your terms document') }}
                        </flux:text>
                    </div>
                </div>

                <!-- Version -->
                <div>
                    <flux:input 
                        wire:model="version" 
                        label="{{ __('gym.Version Number') }}" 
                        placeholder="{{ __('gym.e.g., 1.0, 2.1, 3.0') }}"
                        class="text-sm"
                    />
                    <div class="mt-1">
                        <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                            {{ __('gym.Use semantic versioning (e.g., 1.0, 1.1, 2.0)') }}
                        </flux:text>
                    </div>
                </div>

                <!-- Effective Date -->
                <div>
                    <flux:input 
                        wire:model="effective_date" 
                        type="date" 
                        label="{{ __('gym.Effective Date') }}"
                        class="text-sm"
                    />
                    <div class="mt-1">
                        <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                            {{ __('gym.When these terms will take effect') }}
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="mb-10">
            <div class="mb-6">
                <flux:heading class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('gym.Status & Visibility') }}</flux:heading>
            </div>
            
            <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl">
                <flux:field variant="inline" class="mb-3">
                    <flux:label class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $editingId ? __('gym.Set as active version') : __('gym.Make this version active') }}
                    </flux:label>
                    <flux:switch wire:model.live="is_active" />
                </flux:field>
                <div>
                    <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                        {{ __('gym.When active, this will be the terms shown to new members. Only one terms document can be active at a time.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="mb-10">
            <div class="mb-6">
                <flux:heading class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('gym.Document Content') }}</flux:heading>
            </div>
            
            <flux:editor 
                wire:model="content" 
                label="{{ __('gym.Terms Content') }}"
                placeholder="{{ __('gym.Enter the terms content here. You can use variables like {org_name}, {effective_date}, and {version}.') }}"
                rows="25"
                class="min-h-[600px] text-sm"
            />
            <div class="mt-2">
                <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                    {{ __('gym.Write your terms using rich text formatting. Use the toolbar above for styling options.') }}
                </flux:text>
            </div>
            
            <!-- Template Variables Helper -->
            <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="mb-3">
                    <flux:heading class="text-lg font-medium text-zinc-900 dark:text-white">
                        {{ __('gym.Available Template Variables') }}
                    </flux:heading>
                </div>
                <div class="flex flex-wrap gap-2 mb-3">
                    <flux:badge variant="outline" class="font-mono text-sm">{org_name}</flux:badge>
                    <flux:badge variant="outline" class="font-mono text-sm">{effective_date}</flux:badge>
                    <flux:badge variant="outline" class="font-mono text-sm">{version}</flux:badge>
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-white/60">
                    {{ __('gym.These variables will be automatically replaced with actual values when displayed to users.') }}
                </flux:text>
            </div>
        </div>



        <!-- Form Actions -->
        <div class="pt-8 border-t border-zinc-200 dark:border-zinc-700">
            <!-- Main Form Actions -->
            <div class="flex items-center justify-between">
                <flux:text class="text-base text-zinc-500 dark:text-white/60">
                    {{ $editingId ? __('gym.Changes will be saved immediately') : __('gym.New terms will be created with the specified settings') }}
                </flux:text>
                <div class="flex gap-3">
                    <flux:button wire:click="cancelForm" variant="ghost">
                        {{ __('gym.Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="{{ $editingId ? 'check' : 'plus' }}">
                        {{ $editingId ? __('gym.Update Terms') : __('gym.Create Terms') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </form>


</div>
