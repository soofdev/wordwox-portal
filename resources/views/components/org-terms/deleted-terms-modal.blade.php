@props(['showDeletedModal'])

<!-- Enhanced Deleted Terms Modal using Flux Design System -->
@if ($showDeletedModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block !important;">
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" wire:click="closeDeletedModal"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-5xl max-h-[90vh] overflow-hidden transform transition-all" wire:click.stop>
                <!-- Modal Header -->
                <div class="px-8 py-6 bg-gradient-to-r from-red-50 to-pink-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-white rounded-xl shadow-sm">
                                <flux:icon.trash class="w-6 h-6 text-red-600" />
                            </div>
                            <div>
                                <flux:heading size="xl" class="text-gray-900">
                                    Deleted Terms
                                </flux:heading>
                                <flux:text variant="muted" class="mt-1">
                                    Manage and restore deleted terms documents
                                </flux:text>
                            </div>
                        </div>
                        <flux:button 
                            wire:click="closeDeletedModal" 
                            variant="ghost" 
                            
                            icon="x-mark"
                            class="text-gray-500 hover:text-gray-700"
                        />
                    </div>
                </div>
            
            <!-- Modal Body -->
            <div class="p-8 overflow-y-auto max-h-[70vh]">
                @php
                    $deletedTerms = $this->getDeletedTerms();
                @endphp
                
                @if($deletedTerms->count() > 0)
                    <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <flux:text class="font-semibold text-gray-900">
                                    {{ $deletedTerms->count() }} deleted {{ Str::plural('term', $deletedTerms->count()) }}
                                </flux:text>
                                <flux:text  variant="muted">
                                    Click restore to recover any document
                                </flux:text>
                            </div>
                        </div>
                        
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left">
                                        <flux:text  class="font-semibold text-gray-700 flex items-center gap-2">
                                            <flux:icon.document-text class="w-4 h-4 text-gray-400" />
                                            Title
                                        </flux:text>
                                    </th>
                                    <th class="px-6 py-4 text-left">
                                        <flux:text  class="font-semibold text-gray-700 flex items-center gap-2">
                                            <flux:icon.tag class="w-4 h-4 text-gray-400" />
                                            Version
                                        </flux:text>
                                    </th>
                                    <th class="px-6 py-4 text-left">
                                        <flux:text  class="font-semibold text-gray-700 flex items-center gap-2">
                                            <flux:icon.clock class="w-4 h-4 text-gray-400" />
                                            Deleted Date
                                        </flux:text>
                                    </th>
                                    <th class="px-6 py-4 text-right">
                                        <flux:text  class="font-semibold text-gray-700">Actions</flux:text>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($deletedTerms as $term)
                                    <tr class="hover:bg-red-50/50 transition-colors duration-200">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                                    <flux:icon.document-text class="w-4 h-4 text-red-600" />
                                                </div>
                                                <flux:text class="font-medium text-red-600 line-through">
                                                    {{ $term->title }}
                                                </flux:text>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-5">
                                            <flux:badge variant="outline"  class="font-mono">
                                                v{{ $term->version }}
                                            </flux:badge>
                                        </td>
                                        
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1 bg-gray-100 rounded">
                                                    <flux:icon.clock class="w-3 h-3 text-gray-500" />
                                                </div>
                                                <flux:text  class="text-gray-600">
                                                    {{ $term->deleted_at->format('M j, Y g:i A') }}
                                                </flux:text>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-5 text-right">
                                            <flux:button 
                                                wire:click="restore({{ $term->id }})" 
                                                variant="success" 
                                                
                                                icon="arrow-path"
                                            >
                                                Restore
                                            </flux:button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="mx-auto w-20 h-20 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl flex items-center justify-center mb-6">
                            <flux:icon.trash class="w-10 h-10 text-gray-400" />
                        </div>
                        <flux:heading size="xl" class="mb-3 text-gray-900">No deleted terms</flux:heading>
                        <flux:text variant="muted" class="text-lg">
                            All terms are currently active. Deleted terms will appear here for easy restoration.
                        </flux:text>
                    </div>
                @endif
            </div>
            
            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <flux:text  variant="muted">
                        Deleted terms can be restored at any time
                    </flux:text>
                    <flux:button wire:click="closeDeletedModal" variant="primary"  icon="x-mark">
                        Close
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
@endif