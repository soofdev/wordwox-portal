<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">🎨 Template Manager</h1>
        <p class="text-gray-600 dark:text-gray-300">Choose and preview different templates for your website</p>
    </div>

    @if(session()->has('message'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <!-- Current Template Info -->
    @if($previewPage)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Current Page: {{ $previewPage->title }}</h3>
            <p class="text-blue-700 dark:text-blue-300 text-sm">
                Currently using: <strong>{{ $availableTemplates[$selectedTemplate]['name'] ?? 'Unknown' }}</strong>
            </p>
        </div>
    @endif

    <!-- Template Grid -->
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

    <!-- Apply Template Button -->
    @if($previewPage)
        <div class="mt-8 p-6 bg-gray-50 dark:bg-zinc-800 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Apply Selected Template</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Apply "{{ $availableTemplates[$selectedTemplate]['name'] }}" to "{{ $previewPage->title }}"
                    </p>
                </div>
                <button 
                    wire:click="applyTemplate"
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Apply Template
                </button>
            </div>
        </div>
    @endif

    <!-- Template Comparison -->
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
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-preview', (event) => {
            window.open(event.url, '_blank');
        });
    });
</script>
