<div class="relative">
    <!-- Template Selector Button -->
    <button 
        wire:click="toggleSelector"
        class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all shadow-md"
    >
        <span class="text-lg">{{ $this->getTemplateIcon($currentTemplate) }}</span>
        <span class="font-medium">{{ $this->getTemplateName($currentTemplate) }}</span>
        <svg class="w-4 h-4 transition-transform {{ $showSelector ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Template Dropdown -->
    @if($showSelector)
        <div class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 rounded-lg shadow-xl border border-gray-200 dark:border-zinc-700 z-50 max-h-96 overflow-y-auto">
            <div class="p-2">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 px-3 py-2 border-b border-gray-200 dark:border-zinc-600">
                    🎨 Choose Template
                </div>
                
                <div class="grid grid-cols-1 gap-1 mt-2">
                    @foreach($templates as $templateKey => $template)
                        <button 
                            wire:click="selectTemplate('{{ $templateKey }}')"
                            class="flex items-center space-x-3 px-3 py-3 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors text-left {{ $currentTemplate === $templateKey ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : '' }}"
                        >
                            <span class="text-2xl">{{ $this->getTemplateIcon($templateKey) }}</span>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $template['description'] }}</div>
                            </div>
                            @if($currentTemplate === $templateKey)
                                <div class="text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
                
                <div class="mt-3 pt-2 border-t border-gray-200 dark:border-zinc-600">
                    <a href="{{ route('cms.templates') }}" 
                       class="flex items-center justify-center space-x-2 px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                       wire:navigate>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>Preview All Templates</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Click outside to close -->
    @if($showSelector)
        <div class="fixed inset-0 z-40" wire:click="toggleSelector"></div>
    @endif
</div>
