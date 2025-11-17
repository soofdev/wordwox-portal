@props(['showForceDeleteConfirm'])

<!-- Force Delete Modal Component -->
@if ($showForceDeleteConfirm)
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4" 
         style="background-color: rgba(0, 0, 0, 0.6); display: flex !important;"
         wire:click="cancelForceDelete">
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg border border-gray-300"
             wire:click.stop
             onclick="event.stopPropagation();">
            
            <!-- Modal Header -->
            <div class="p-6 text-center">
                <!-- Warning Icon -->
                <div class="mx-auto flex items-center justify-center w-12 h-12 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                
                <!-- Title -->
                <h3 class="mt-3 text-lg font-medium text-gray-900">
                    Delete Permanently
                </h3>
                
                <!-- Message -->
                <div class="mt-3">
                    <p class="text-sm text-gray-600">
                        This will permanently delete the term. This action cannot be undone.
                    </p>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-center px-6 py-4 space-x-3 bg-gray-50 border-t border-gray-200">
                <button wire:click="forceDelete" 
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="forceDelete">
                        Delete Permanently
                    </span>
                    <span wire:loading wire:target="forceDelete" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 14 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                </button>
                
                <button wire:click="cancelForceDelete" 
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif
