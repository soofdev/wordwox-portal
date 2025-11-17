<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Top Bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <flux:button href="{{ route('cms.pages.index') }}" variant="ghost" size="sm" icon="arrow-left">
                    Back to Pages
                </flux:button>
                <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Edit Page: {{ $title ?: 'Untitled' }}
                </h1>
            </div>
            
            <div class="flex items-center space-x-3">
                <flux:badge variant="{{ $status === 'published' ? 'success' : ($status === 'draft' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($status) }}
                </flux:badge>
                <flux:button wire:click="save" variant="primary" size="sm" icon="check">
                    Update Page
                </flux:button>
            </div>
        </div>
    </div>

    <div class="flex">
        <!-- Main Editor Area -->
        <div class="flex-1 max-w-4xl mx-auto">
            <!-- Page Title Section -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <div class="space-y-4">
                    <div>
                        <flux:input 
                            wire:model.live="title" 
                            placeholder="Add title" 
                            class="text-3xl font-bold border-none p-0 focus:ring-0 bg-transparent"
                            style="font-size: 2rem; line-height: 2.5rem;"
                        />
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <span>Permalink: <span class="text-blue-600">{{ url('/') }}/{{ $slug }}</span></span>
                        <flux:button variant="ghost" size="xs">Edit</flux:button>
                    </div>
                </div>
            </div>

            <!-- Content Blocks -->
            <div class="bg-white dark:bg-gray-800 min-h-screen">
                <div class="p-6 space-y-4">
                    @forelse($blocks as $index => $block)
                        <div class="group relative border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                            <!-- Block Toolbar -->
                            <div class="absolute -top-10 left-0 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <div class="flex items-center space-x-1 bg-gray-900 text-white rounded-md px-2 py-1 text-xs">
                                    <span>{{ ucfirst($block['type']) }}</span>
                                    <div class="w-px h-4 bg-gray-600 mx-1"></div>
                                    <flux:button 
                                        wire:click="moveBlockUp({{ $index }})" 
                                        variant="ghost" 
                                        size="xs" 
                                        icon="chevron-up"
                                        class="text-white hover:text-blue-300"
                                        @if($index === 0) disabled @endif
                                    />
                                    <flux:button 
                                        wire:click="moveBlockDown({{ $index }})" 
                                        variant="ghost" 
                                        size="xs" 
                                        icon="chevron-down"
                                        class="text-white hover:text-blue-300"
                                        @if($index === count($blocks) - 1) disabled @endif
                                    />
                                    <flux:button 
                                        wire:click="removeBlock({{ $index }})" 
                                        variant="ghost" 
                                        size="xs" 
                                        icon="trash"
                                        class="text-white hover:text-red-300"
                                    />
                                </div>
                            </div>

                            <!-- Block Content -->
                            <div class="p-4">
                                @if($block['type'] === 'heading')
                                    <div class="space-y-3">
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.content" 
                                            placeholder="Write heading..." 
                                            class="text-2xl font-bold border-none p-0 focus:ring-0 bg-transparent"
                                        />
                                    </div>

                                @elseif($block['type'] === 'paragraph')
                                    <flux:textarea 
                                        wire:model="blocks.{{ $index }}.content" 
                                        placeholder="Start writing..." 
                                        rows="4"
                                        class="border-none p-0 focus:ring-0 bg-transparent resize-none"
                                    />

                                @elseif($block['type'] === 'image')
                                    <div class="space-y-3">
                                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                                            <flux:icon name="photo" class="mx-auto h-12 w-12 text-gray-400" />
                                            <p class="mt-2 text-sm text-gray-500">Click to upload an image</p>
                                            <flux:button variant="outline" size="sm" class="mt-2">Upload Image</flux:button>
                                        </div>
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.title" 
                                            placeholder="Image caption (optional)" 
                                            class="text-sm"
                                        />
                                    </div>

                                @elseif($block['type'] === 'quote')
                                    <div class="space-y-3">
                                        <flux:textarea 
                                            wire:model="blocks.{{ $index }}.content" 
                                            placeholder="Write your quote..." 
                                            rows="3"
                                            class="text-xl italic border-none p-0 focus:ring-0 bg-transparent"
                                        />
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.title" 
                                            placeholder="Citation (optional)" 
                                            class="text-sm"
                                        />
                                    </div>

                                @elseif($block['type'] === 'list')
                                    <div class="space-y-3">
                                        <flux:textarea 
                                            wire:model="blocks.{{ $index }}.content" 
                                            placeholder="• First item&#10;• Second item&#10;• Third item" 
                                            rows="4"
                                            class="border-none p-0 focus:ring-0 bg-transparent font-mono text-sm"
                                        />
                                    </div>

                                @elseif($block['type'] === 'button')
                                    <div class="space-y-3">
                                        <div class="flex space-x-3">
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.content" 
                                                placeholder="Button text" 
                                                class="flex-1"
                                            />
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.title" 
                                                placeholder="Button URL" 
                                                class="flex-1"
                                            />
                                        </div>
                                        <div class="pt-2">
                                            <flux:button variant="primary" size="md">
                                                {{ $block['content'] ?: 'Button Preview' }}
                                            </flux:button>
                                        </div>
                                    </div>

                                @elseif($block['type'] === 'spacer')
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm text-gray-500">Height:</span>
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.content" 
                                                type="number" 
                                                placeholder="50" 
                                                class="w-20"
                                            />
                                            <span class="text-sm text-gray-500">px</span>
                                        </div>
                                        <div 
                                            class="border-t-2 border-dashed border-gray-300 dark:border-gray-600"
                                            style="margin-top: {{ $block['content'] ?: 50 }}px; margin-bottom: {{ $block['content'] ?: 50 }}px;"
                                        ></div>
                                    </div>

                                @elseif($block['type'] === 'code')
                                    <div class="space-y-3">
                                        <flux:textarea 
                                            wire:model="blocks.{{ $index }}.content" 
                                            placeholder="// Your code here" 
                                            rows="6"
                                            class="font-mono text-sm bg-gray-900 text-green-400 border-gray-700"
                                        />
                                    </div>

                                @elseif($block['type'] === 'hero')
                                    @php
                                        // Handle both array and JSON string formats
                                        $settingsJson = $block['settings_json'] ?? '{}';
                                        if (is_array($settingsJson)) {
                                            $settings = $settingsJson;
                                        } else {
                                            $settings = json_decode($settingsJson, true) ?? [];
                                        }
                                        $bgColor = $settings['background_color'] ?? '#1f2937';
                                        $textColor = $settings['text_color'] ?? '#ffffff';
                                    @endphp
                                    <div class="space-y-4">
                                        <!-- Hero Preview -->
                                        <div class="rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700" 
                                             style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                            <div class="p-8 text-center">
                                                <h2 class="text-3xl font-bold mb-3">
                                                    {{ $block['title'] ?: 'Your Hero Title' }}
                                                </h2>
                                                <p class="text-xl mb-4 opacity-90">
                                                    {{ $block['subtitle'] ?: 'Your Hero Subtitle' }}
                                                </p>
                                                <div class="text-base">
                                                    {{ $block['content'] ?: 'Your hero description text goes here...' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Hero Editor Fields -->
                                        <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <div>
                                                <flux:label class="text-sm font-medium">Hero Title</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.title" 
                                                    placeholder="Enter hero title..." 
                                                    class="mt-1"
                                                />
                                            </div>
                                            
                                            <div>
                                                <flux:label class="text-sm font-medium">Hero Subtitle</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.subtitle" 
                                                    placeholder="Enter hero subtitle..." 
                                                    class="mt-1"
                                                />
                                            </div>
                                            
                                            <div>
                                                <flux:label class="text-sm font-medium">Hero Description</flux:label>
                                                <flux:textarea 
                                                    wire:model="blocks.{{ $index }}.content" 
                                                    placeholder="Enter hero description..." 
                                                    rows="3"
                                                    class="mt-1"
                                                />
                                            </div>

                                            <!-- Color Settings -->
                                            <div class="grid grid-cols-2 gap-3 pt-2">
                                                <div>
                                                    <flux:label class="text-sm font-medium">Background Color</flux:label>
                                                    <div class="flex items-center space-x-2 mt-1">
                                                        <input 
                                                            type="color" 
                                                            value="{{ $bgColor }}"
                                                            wire:change="updateHeroSettings({{ $index }}, 'background_color', $event.target.value)"
                                                            class="w-12 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                                                        />
                                                        <flux:input 
                                                            value="{{ $bgColor }}"
                                                            placeholder="#1f2937"
                                                            wire:change="updateHeroSettings({{ $index }}, 'background_color', $event.target.value)"
                                                            class="flex-1 font-mono text-sm"
                                                        />
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <flux:label class="text-sm font-medium">Text Color</flux:label>
                                                    <div class="flex items-center space-x-2 mt-1">
                                                        <input 
                                                            type="color" 
                                                            value="{{ $textColor }}"
                                                            wire:change="updateHeroSettings({{ $index }}, 'text_color', $event.target.value)"
                                                            class="w-12 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                                                        />
                                                        <flux:input 
                                                            value="{{ $textColor }}"
                                                            placeholder="#ffffff"
                                                            wire:change="updateHeroSettings({{ $index }}, 'text_color', $event.target.value)"
                                                            class="flex-1 font-mono text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Quick Color Presets -->
                                            <div>
                                                <flux:label class="text-sm font-medium mb-2">Quick Presets</flux:label>
                                                <div class="flex flex-wrap gap-2">
                                                    <button 
                                                        type="button"
                                                        wire:click="applyHeroPreset({{ $index }}, 'dark')"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Dark Theme
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        wire:click="applyHeroPreset({{ $index }}, 'light')"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Light Theme
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        wire:click="applyHeroPreset({{ $index }}, 'blue')"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Blue Theme
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        wire:click="applyHeroPreset({{ $index }}, 'gradient')"
                                                        class="px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Gradient
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <flux:icon name="cube" class="mx-auto h-8 w-8 mb-2" />
                                        <p>{{ ucfirst($block['type']) }} Block</p>
                                        <p class="text-xs">Content editing for this block type is coming soon.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Add Block Button (appears between blocks) -->
                            <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <flux:button 
                                    wire:click="$set('showBlockSelector', true)" 
                                    variant="primary" 
                                    size="xs" 
                                    icon="plus"
                                    class="rounded-full shadow-lg"
                                >
                                    Add Block
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <!-- Empty State -->
                        <div class="text-center py-16">
                            <flux:icon name="document-text" class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Start building your page</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">Add blocks to create your content</p>
                            <flux:button wire:click="$set('showBlockSelector', true)" variant="primary" icon="plus">
                                Add Your First Block
                            </flux:button>
                        </div>
                    @endforelse

                    <!-- Final Add Block Button -->
                    @if(count($blocks) > 0)
                        <div class="text-center py-8">
                            <flux:button 
                                wire:click="$set('showBlockSelector', true)" 
                                variant="outline" 
                                icon="plus"
                            >
                                Add Block
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-y-auto">
            <div class="p-6 space-y-6">
                <!-- Page Settings -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Page Settings</h3>
                        <!-- Template Selector -->
                        <livewire:template-selector :page-id="$page->id" :current-template="$template" />
                    </div>
                    <div class="space-y-4">
                        <div>
                            <flux:field>
                                <flux:label>Status</flux:label>
                                <flux:select wire:model="status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </flux:select>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Page Type</flux:label>
                                <flux:select wire:model="type">
                                    <option value="page">Page</option>
                                    <option value="post">Post</option>
                                    <option value="home">Home</option>
                                    <option value="about">About</option>
                                    <option value="contact">Contact</option>
                                    <option value="custom">Custom</option>
                                </flux:select>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Template</flux:label>
                                <flux:select wire:model="template">
                                    <option value="modern">🚀 Modern Template (Futuristic Glass Design)</option>
                                    <option value="classic">🏛️ Classic Template (Elegant Traditional Design)</option>
                                    <option value="meditative">🧘‍♀️ Meditative Template (Zen Wellness Design)</option>
                                </flux:select>
                                <flux:description>Choose a template that best fits your page content and purpose.</flux:description>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Slug</flux:label>
                                <flux:input wire:model="slug" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description" rows="3" />
                            </flux:field>
                        </div>

                        <div class="space-y-2">
                            <flux:checkbox wire:model="is_homepage">
                                Set as homepage
                            </flux:checkbox>
                            <flux:checkbox wire:model="show_in_navigation">
                                Show in navigation
                            </flux:checkbox>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Sort Order</flux:label>
                                <flux:input wire:model="sort_order" type="number" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <flux:field>
                                <flux:label>SEO Title</flux:label>
                                <flux:input wire:model="seo_title" />
                                <flux:description>{{ strlen($seo_title ?? '') }}/60 characters</flux:description>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>SEO Description</flux:label>
                                <flux:textarea wire:model="seo_description" rows="3" />
                                <flux:description>{{ strlen($seo_description ?? '') }}/160 characters</flux:description>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Keywords</flux:label>
                                <flux:input wire:model="seo_keywords" placeholder="keyword1, keyword2, keyword3" />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Block Selector Modal -->
    @if($showBlockSelector)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click="$set('showBlockSelector', false)">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Choose a Block</h3>
                    <flux:button wire:click="$set('showBlockSelector', false)" variant="ghost" icon="x-mark" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    @foreach($this->blockTypes as $type => $config)
                        <button 
                            wire:click="addBlock('{{ $type }}')"
                            class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-center group"
                        >
                            <flux:icon name="{{ $config['icon'] }}" class="mx-auto h-8 w-8 text-gray-400 group-hover:text-blue-500 mb-2" />
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $config['name'] }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>