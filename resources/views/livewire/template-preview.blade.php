<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">🎨 Template Manager</h1>
        <p class="text-gray-600 dark:text-gray-300">Choose and preview different templates for your website</p>
    </div>



    <!-- Current Template Info -->
    @if($previewPage)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Current Page: {{ $previewPage->title }}</h3>
            <p class="text-blue-700 dark:text-blue-300 text-sm">
                Currently using: <strong>{{ !empty($selectedTemplate) && isset($availableTemplates[$selectedTemplate]) ? $availableTemplates[$selectedTemplate]['name'] : 'Unknown' }}</strong>
            </p>
        </div>
    @endif

    <!-- Template Grid -->
    @if(empty($availableTemplates))
        <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <p class="text-yellow-800 dark:text-yellow-200">
                No templates available. Please import templates using the command: 
                <code class="bg-yellow-100 dark:bg-yellow-900 px-2 py-1 rounded">php artisan cms:import-templates /path/to/folder</code>
            </p>
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($availableTemplates as $templateKey => $template)
            <div class="template-card {{ $selectedTemplate === $templateKey ? 'ring-2 ring-blue-500' : '' }} bg-white dark:bg-zinc-800 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                <!-- Template Preview -->
                <div class="h-32 {{ $template['preview_color'] }} flex items-center justify-center relative">
                    <div class="text-4xl">{{ $template['icon'] }}</div>
                    @if($selectedTemplate === $templateKey)
                        <div class="absolute top-2 right-2 bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                            ✓
                        </div>
                    @endif
                </div>

                <!-- Template Info -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $template['name'] }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $template['description'] }}</p>
                    
                    <!-- Features -->
                    <ul class="text-xs text-gray-500 dark:text-gray-500 mb-4 space-y-1">
                        @foreach($template['features'] as $feature)
                            <li class="flex items-center">
                                <span class="w-1 h-1 bg-gray-400 rounded-full mr-2"></span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button 
                            wire:click="selectTemplate('{{ $templateKey }}')"
                            class="flex-1 px-3 py-2 text-sm font-medium {{ $selectedTemplate === $templateKey ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} rounded-md transition-colors"
                        >
                            {{ $selectedTemplate === $templateKey ? 'Selected' : 'Select' }}
                        </button>
                        
                        <button 
                            wire:click="previewTemplate('{{ $templateKey }}')"
                            class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md transition-colors"
                            title="Preview in new tab"
                        >
                            👁️
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Apply Template Button -->
    <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Apply Template to All Pages
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    @if(!empty($selectedTemplate) && isset($availableTemplates[$selectedTemplate]))
                        Apply "{{ $availableTemplates[$selectedTemplate]['name'] }}" to <strong>all pages</strong> in your organization
                    @else
                        Please select a template first
                    @endif
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    This will update the design for your entire website
                </p>
            </div>
                <button 
                    wire:click="applyTemplate"
                    wire:loading.attr="disabled"
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    @if(empty($selectedTemplate) || !isset($availableTemplates[$selectedTemplate])) disabled @endif
                >
                    <span wire:loading.remove wire:target="applyTemplate">Apply to All Pages</span>
                    <span wire:loading wire:target="applyTemplate" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Applying to All Pages...
                    </span>
                </button>
            </div>
        </div>

    <!-- Visit Website Button -->
    <div class="mt-6 p-6 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-lg border border-green-200 dark:border-green-800 text-center">
        <div class="mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Preview Your Website
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                See how your templates look on the live website
            </p>
        </div>
        
        <a href="{{ url('/') }}" 
           target="_blank" 
           class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            🌐 Visit My Website
        </a>
    </div>

    <!-- Template Comparison -->
    @if(!empty($availableTemplates))
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Template Comparison</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 rounded-lg shadow">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Template</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Style</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Best For</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Key Features</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-600">
                    @foreach($availableTemplates as $templateKey => $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-3">{{ $template['icon'] }}</span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $template['description'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @switch($templateKey)
                                    @case('modern')
                                        Futuristic
                                        @break
                                    @case('classic')
                                        Traditional
                                        @break
                                    @case('home')
                                        Landing
                                        @break
                                    @case('packages')
                                        Sales
                                        @break
                                    @default
                                        Standard
                                @endswitch
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                @switch($templateKey)
                                    @case('home')
                                        Homepage, Landing pages
                                        @break
                                    @case('packages')
                                        Pricing, Memberships
                                        @break
                                    @case('contact')
                                        Contact, Location pages
                                        @break
                                    @case('coaches')
                                        Team, Staff pages
                                        @break
                                    @case('schedule')
                                        Schedules, Booking
                                        @break
                                    @case('modern')
                                        Tech, Innovation pages
                                        @break
                                    @case('classic')
                                        Heritage, Formal pages
                                        @break
                                    @default
                                        General content
                                @endswitch
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ implode(', ', array_slice($template['features'], 0, 2)) }}
                                @if(count($template['features']) > 2)
                                    <span class="text-gray-400">+{{ count($template['features']) - 2 }} more</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-preview', (event) => {
            window.open(event.url, '_blank');
        });
        
        Livewire.on('show-toast', (event) => {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-500 transform translate-x-full opacity-0 ${
                event.type === 'success' 
                    ? 'bg-green-50 border border-green-200 text-green-800'
                    : 'bg-red-50 border border-red-200 text-red-800'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        ${
                            event.type === 'success'
                                ? '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                                : '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                        }
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">${event.message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 100);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        });
    });
</script>
