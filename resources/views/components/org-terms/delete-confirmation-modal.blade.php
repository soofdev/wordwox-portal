@props(['showDeleteConfirm'])

<!-- Delete Confirmation Modal - Modern Design -->
@if ($showDeleteConfirm)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block !important;">
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="cancelDelete"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-sm transform transition-all" wire:click.stop>
                <!-- Close Button -->
                <button wire:click="cancelDelete" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
                
                <!-- Modal Content -->
                <div class="px-6 py-8 text-center">
                    <!-- Trash Icon -->
                    <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                        <flux:icon.trash class="w-8 h-8 text-red-600 dark:text-red-400" />
                    </div>
                    
                    <!-- Title -->
                    <flux:heading class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        Delete org terms
                    </flux:heading>
                    
                    <!-- Message -->
                    <flux:text class="text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you would like to do this?
                    </flux:text>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <flux:button 
                            wire:click="cancelDelete" 
                            variant="ghost"
                            class="flex-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                        >
                            Cancel
                        </flux:button>
                        <flux:button 
                            wire:click="delete" 
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                        >
                            <span wire:loading.remove wire:target="delete">
                                Delete
                            </span>
                            <span wire:loading wire:target="delete" class="flex items-center justify-center gap-2">
                                <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                                Deleting...
                            </span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif