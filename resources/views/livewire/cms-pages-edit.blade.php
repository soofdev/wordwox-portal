<div class="min-h-screen bg-white dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center gap-4 mb-6">
                    <!-- Back to Pages Button -->
                    <a href="{{ route('cms.pages.index') }}" 
                       class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-8 text-sm rounded-md px-3 inline-flex bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-800 dark:text-white transition-colors duration-200" 
                       data-flux-button="data-flux-button" 
                       wire:navigate>
                        <svg class="shrink-0 [:where(&)]:size-4" data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M14 8a.75.75 0 0 1-.75.75H4.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L4.56 7.25h8.69A.75.75 0 0 1 14 8Z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $title ?: 'Untitled Page' }}</span>
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <flux:button 
                        wire:click="save" 
                        variant="primary" 
                        icon="check"
                        wire:loading.attr="disabled"
                        class="font-semibold"
                    >
                        <span wire:loading.remove wire:target="save">Save Page</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex">
        <!-- Main Editor Area -->
        <div class="flex-1 overflow-y-auto bg-white dark:bg-zinc-800">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Page Title Editor -->
                <div class="bg-white dark:bg-white/10 border border-zinc-200 dark:border-white/10 rounded-xl p-6 mb-8">
                    <flux:input 
                        wire:model.live="title" 
                        placeholder="Page title..." 
                        class="text-3xl font-bold border-none p-0 focus:ring-0 bg-transparent shadow-none"
                        style="font-size: 2rem; line-height: 2.5rem;"
                    />
                </div>

                <!-- Content Blocks (Each block is a full-width section) -->
                <div 
                    id="blocks-container"
                    class="space-y-6"
                    x-data="{
                        init() {
                            this.$nextTick(() => {
                                if (window.initBlockSortable) {
                                    window.initBlockSortable('blocks-container');
                                }
                            });
                        }
                    }"
                >
                    @forelse($blocks as $index => $block)
                        <flux:card 
                            class="group relative"
                            data-block-index="{{ $index }}"
                            data-block-uuid="{{ $block['uuid'] ?? '' }}"
                            id="block-{{ $index }}"
                        >
                            <!-- Block/Section Header -->
                            <div class="flex items-center justify-between mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <!-- Drag Handle -->
                                    <button
                                        type="button"
                                        data-drag-handle
                                        class="cursor-move text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                                        title="Drag to reorder"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </button>
                                    <flux:badge variant="outline" size="sm">
                                        {{ ucfirst($block['type']) }}
                                    </flux:badge>
                                    @if($block['is_active'])
                                        <flux:badge variant="success" size="xs">Active</flux:badge>
                                    @else
                                        <flux:badge variant="secondary" size="xs">Inactive</flux:badge>
                                    @endif
                                </div>
                                
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <flux:button 
                                        wire:click="moveBlockUp({{ $index }})" 
                                        wire:loading.attr="disabled"
                                        variant="ghost" 
                                        size="xs" 
                                        icon="chevron-up"
                                        @if($index === 0) disabled @endif
                                    />
                                    <flux:button 
                                        wire:click="moveBlockDown({{ $index }})" 
                                        wire:loading.attr="disabled"
                                        variant="ghost" 
                                        size="xs" 
                                        icon="chevron-down"
                                        @if($index === count($blocks) - 1) disabled @endif
                                    />
                                    <flux:separator vertical />
                                    <flux:button 
                                        wire:click="removeBlock({{ $index }})" 
                                        wire:confirm="Are you sure you want to remove this block?"
                                        variant="ghost" 
                                        size="xs" 
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        wire:loading.attr="disabled"
                                    />
                                </div>
                            </div>

                            <!-- Block Content -->
                            <div class="space-y-4">
                                @if($block['type'] === 'heading')
                                    <flux:field>
                                        <flux:label>Heading Text</flux:label>
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.content" 
                                            placeholder="Enter heading text..." 
                                        />
                                    </flux:field>

                                @elseif($block['type'] === 'paragraph')
                                    <flux:field>
                                        <flux:label>Paragraph Content</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            // Get language from page or default to 'en'
                                                            const language = document.documentElement.lang || 'en';
                                                            window.initCKEditor(
                                                                'ckeditor-paragraph-{{ $index }}',
                                                                'blocks.{{ $index }}.content',
                                                                @js($block['content'] ?? ''),
                                                                language
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                            }).catch(error => {
                                                                console.error('CKEditor initialization error:', error);
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-paragraph-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-paragraph-{{ $index }}"
                                                wire:model.defer="blocks.{{ $index }}.content"
                                                class="min-h-[300px]"
                                            >{!! $block['content'] ?? '' !!}</textarea>
                                        </div>
                                    </flux:field>

                                @elseif($block['type'] === 'image')
                                    @php
                                        // Get image URL from block data
                                        $imageData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($imageData)) {
                                            $imageData = [];
                                        }
                                        $imageUrl = $imageData['image_url'] ?? $block['content'] ?? null;
                                    @endphp
                                    <flux:field>
                                        <flux:label>Image</flux:label>
                                        
                                        @if($imageUrl)
                                            <!-- Display uploaded image -->
                                            <div class="relative mb-3">
                                                <img src="{{ $imageUrl }}" alt="Uploaded image" class="max-w-full h-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                <flux:button 
                                                    wire:click="removeImage({{ $index }})"
                                                    variant="ghost"
                                                    size="xs"
                                                    icon="trash"
                                                    class="absolute top-2 right-2 bg-white/90 dark:bg-zinc-800/90"
                                                >
                                                    Remove
                                                </flux:button>
                                            </div>
                                        @endif
                                        
                                        <!-- File Upload Area -->
                                        <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center {{ $imageUrl ? 'hidden' : '' }}" wire:loading.class="opacity-50">
                                            <flux:icon name="photo" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                                <span wire:loading.remove wire:target="imageUploads.blocks.{{ $index }}">Upload an image</span>
                                                <span wire:loading wire:target="imageUploads.blocks.{{ $index }}">Uploading...</span>
                                            </p>
                                            <label class="cursor-pointer">
                                                <input 
                                                    type="file" 
                                                    wire:model="imageUploads.blocks.{{ $index }}"
                                                    accept="image/*"
                                                    class="hidden"
                                                    wire:loading.attr="disabled"
                                                />
                                                <flux:button 
                                                    type="button"
                                                    variant="outline" 
                                                    size="sm" 
                                                    icon="arrow-up-tray"
                                                    onclick="this.previousElementSibling.click()"
                                                    wire:loading.attr="disabled"
                                                >
                                                    <span wire:loading.remove wire:target="imageUploads.blocks.{{ $index }}">Choose Image</span>
                                                    <span wire:loading wire:target="imageUploads.blocks.{{ $index }}">Uploading...</span>
                                                </flux:button>
                                            </label>
                                            <p class="text-xs text-zinc-400 mt-2">JPG, PNG, GIF up to 10MB</p>
                                        </div>
                                        
                                        @if($imageUrl)
                                            <!-- Replace Image Button -->
                                            <div class="mt-3">
                                                <label class="cursor-pointer">
                                                    <input 
                                                        type="file" 
                                                        wire:model="imageUploads.blocks.{{ $index }}"
                                                        accept="image/*"
                                                        class="hidden"
                                                    />
                                                    <flux:button 
                                                        type="button"
                                                        variant="outline" 
                                                        size="sm"
                                                        onclick="this.previousElementSibling.click()"
                                                    >
                                                        Replace Image
                                                    </flux:button>
                                                </label>
                                            </div>
                                        @endif
                                        
                                        @error("imageUploads.blocks.{$index}")
                                            <flux:description variant="danger">{{ $message }}</flux:description>
                                        @enderror
                                        
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.title" 
                                            placeholder="Image caption (optional)" 
                                            class="mt-3"
                                        />
                                        
                                        @if($imageUrl)
                                            <flux:input 
                                                value="{{ $imageUrl }}"
                                                readonly
                                                class="mt-2 text-xs font-mono"
                                            />
                                            <flux:description>Image URL</flux:description>
                                        @endif
                                    </flux:field>

                                @elseif($block['type'] === 'gallery')
                                    @php
                                        // Get gallery images from block data
                                        $galleryData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($galleryData)) {
                                            $galleryData = [];
                                        }
                                        $galleryImages = $galleryData['images'] ?? [];
                                    @endphp
                                    <flux:field>
                                        <flux:label>Gallery Images</flux:label>
                                        
                                        @if(count($galleryImages) > 0)
                                            <!-- Display Gallery Images -->
                                            <div class="grid grid-cols-3 gap-4 mb-4">
                                                @foreach($galleryImages as $imgIndex => $image)
                                                    <div class="relative group">
                                                        <img 
                                                            src="{{ $image['url'] ?? $image }}" 
                                                            alt="Gallery image {{ $imgIndex + 1 }}" 
                                                            class="w-full h-32 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                        />
                                                        <flux:button 
                                                            wire:click="removeGalleryImage({{ $index }}, {{ $imgIndex }})"
                                                            variant="ghost"
                                                            size="xs"
                                                            icon="trash"
                                                            class="absolute top-2 right-2 bg-white/90 dark:bg-zinc-800/90 opacity-0 group-hover:opacity-100 transition-opacity"
                                                        >
                                                            Remove
                                                        </flux:button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <!-- File Upload Area -->
                                        <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center">
                                            <flux:icon name="photo" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                                <span wire:loading.remove wire:target="imageUploads.gallery.{{ $index }}">Upload gallery images</span>
                                                <span wire:loading wire:target="imageUploads.gallery.{{ $index }}">Uploading...</span>
                                            </p>
                                            <label class="cursor-pointer">
                                                <input 
                                                    type="file" 
                                                    wire:model="imageUploads.gallery.{{ $index }}"
                                                    accept="image/*"
                                                    multiple
                                                    class="hidden"
                                                    wire:loading.attr="disabled"
                                                />
                                                <flux:button 
                                                    type="button"
                                                    variant="outline" 
                                                    size="sm" 
                                                    icon="arrow-up-tray"
                                                    onclick="this.previousElementSibling.click()"
                                                    wire:loading.attr="disabled"
                                                >
                                                    <span wire:loading.remove wire:target="imageUploads.gallery.{{ $index }}">Choose Images</span>
                                                    <span wire:loading wire:target="imageUploads.gallery.{{ $index }}">Uploading...</span>
                                                </flux:button>
                                            </label>
                                            <p class="text-xs text-zinc-400 mt-2">JPG, PNG, GIF up to 10MB each. Select multiple images.</p>
                                        </div>
                                        
                                        @error("imageUploads.gallery.{$index}")
                                            <flux:description variant="danger">{{ $message }}</flux:description>
                                        @enderror
                                        
                                        @if(count($galleryImages) > 0)
                                            <flux:description class="mt-3">
                                                {{ count($galleryImages) }} image(s) in gallery
                                            </flux:description>
                                        @endif
                                    </flux:field>

                                @elseif($block['type'] === 'quote')
                                    <flux:field>
                                        <flux:label>Quote Content</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            window.initCKEditor(
                                                                'ckeditor-quote-{{ $index }}',
                                                                'blocks.{{ $index }}.content',
                                                                @js($block['content'] ?? ''),
                                                                @js(app()->getLocale() ?? 'en')
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-quote-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-quote-{{ $index }}"
                                                wire:model="blocks.{{ $index }}.content"
                                                class="min-h-[150px]"
                                            >{{ $block['content'] ?? '' }}</textarea>
                                        </div>
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.title" 
                                            placeholder="Citation (optional)" 
                                            class="mt-3"
                                        />
                                    </flux:field>

                                @elseif($block['type'] === 'list')
                                    <flux:field>
                                        <flux:label>List Content</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            window.initCKEditor(
                                                                'ckeditor-list-{{ $index }}',
                                                                'blocks.{{ $index }}.content',
                                                                @js($block['content'] ?? ''),
                                                                @js(app()->getLocale() ?? 'en')
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-list-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-list-{{ $index }}"
                                                wire:model="blocks.{{ $index }}.content"
                                                class="min-h-[200px]"
                                            >{{ $block['content'] ?? '' }}</textarea>
                                        </div>
                                        <flux:description>Use the editor toolbar to create bulleted or numbered lists</flux:description>
                                    </flux:field>

                                @elseif($block['type'] === 'button')
                                    <div class="space-y-3">
                                        <flux:field>
                                            <flux:label>Button Text</flux:label>
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.content" 
                                                placeholder="Click me" 
                                            />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Button URL</flux:label>
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.title" 
                                                placeholder="https://example.com" 
                                            />
                                        </flux:field>
                                        <div class="pt-2">
                                            <flux:button variant="primary" size="md">
                                                {{ $block['content'] ?: 'Button Preview' }}
                                            </flux:button>
                                        </div>
                                    </div>

                                @elseif($block['type'] === 'spacer')
                                    <flux:field>
                                        <flux:label>Spacer Height</flux:label>
                                        <div class="flex items-center gap-3">
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.content" 
                                                type="number" 
                                                placeholder="50" 
                                                class="w-24"
                                            />
                                            <span class="text-sm text-zinc-500">pixels</span>
                                        </div>
                                        <div 
                                            class="border-t-2 border-dashed border-zinc-300 dark:border-zinc-600 my-4"
                                            style="margin-top: {{ $block['content'] ?: 50 }}px; margin-bottom: {{ $block['content'] ?: 50 }}px;"
                                        ></div>
                                    </flux:field>

                                @elseif($block['type'] === 'code')
                                    <flux:field>
                                        <flux:label>Code Content</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            window.initCKEditor(
                                                                'ckeditor-code-{{ $index }}',
                                                                'blocks.{{ $index }}.content',
                                                                @js($block['content'] ?? ''),
                                                                @js(app()->getLocale() ?? 'en')
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-code-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-code-{{ $index }}"
                                                wire:model="blocks.{{ $index }}.content"
                                                class="min-h-[200px] font-mono"
                                            >{{ $block['content'] ?? '' }}</textarea>
                                        </div>
                                        <flux:description>Enter code or HTML content</flux:description>
                                    </flux:field>
                                    
                                @elseif($block['type'] === 'html')
                                    <flux:field>
                                        <flux:label>Custom HTML</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            window.initCKEditor(
                                                                'ckeditor-html-{{ $index }}',
                                                                'blocks.{{ $index }}.content',
                                                                @js($block['content'] ?? ''),
                                                                @js(app()->getLocale() ?? 'en')
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-html-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-html-{{ $index }}"
                                                wire:model="blocks.{{ $index }}.content"
                                                class="min-h-[200px] font-mono"
                                            >{{ $block['content'] ?? '' }}</textarea>
                                        </div>
                                        <flux:description>Enter custom HTML code. Use the source button in the toolbar to edit raw HTML.</flux:description>
                                    </flux:field>
                                    

                                @elseif($block['type'] === 'hero')
                                    @php
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
                                        <flux:card class="overflow-hidden p-0" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                            <div class="p-12 text-center">
                                                <h2 class="text-4xl font-bold mb-4">
                                                    {{ $block['title'] ?: 'Your Hero Title' }}
                                                </h2>
                                                <p class="text-xl mb-6 opacity-90">
                                                    {{ $block['subtitle'] ?: 'Your Hero Subtitle' }}
                                                </p>
                                                <div class="text-lg">
                                                    {{ $block['content'] ?: 'Your hero description text goes here...' }}
                                                </div>
                                            </div>
                                        </flux:card>

                                        <!-- Hero Editor Fields -->
                                        <div class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                            <flux:field>
                                                <flux:label>Hero Title</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.title" 
                                                    placeholder="Enter hero title..." 
                                                />
                                            </flux:field>
                                            
                                            <flux:field>
                                                <flux:label>Hero Subtitle</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.subtitle" 
                                                    placeholder="Enter hero subtitle..." 
                                                />
                                            </flux:field>
                                            
                                            <flux:field>
                                                <flux:label>Hero Description</flux:label>
                                                <flux:textarea 
                                                    wire:model="blocks.{{ $index }}.content" 
                                                    placeholder="Enter hero description..." 
                                                    rows="3"
                                                />
                                            </flux:field>

                                            <!-- Color Settings -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <flux:field>
                                                    <flux:label>Background Color</flux:label>
                                                    <div class="flex items-center gap-2">
                                                        <input 
                                                            type="color" 
                                                            value="{{ $bgColor }}"
                                                            wire:change="updateHeroSettings({{ $index }}, 'background_color', $event.target.value)"
                                                            class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer"
                                                        />
                                                        <flux:input 
                                                            value="{{ $bgColor }}"
                                                            placeholder="#1f2937"
                                                            wire:change="updateHeroSettings({{ $index }}, 'background_color', $event.target.value)"
                                                            class="flex-1 font-mono text-sm"
                                                        />
                                                    </div>
                                                </flux:field>
                                                
                                                <flux:field>
                                                    <flux:label>Text Color</flux:label>
                                                    <div class="flex items-center gap-2">
                                                        <input 
                                                            type="color" 
                                                            value="{{ $textColor }}"
                                                            wire:change="updateHeroSettings({{ $index }}, 'text_color', $event.target.value)"
                                                            class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer"
                                                        />
                                                        <flux:input 
                                                            value="{{ $textColor }}"
                                                            placeholder="#ffffff"
                                                            wire:change="updateHeroSettings({{ $index }}, 'text_color', $event.target.value)"
                                                            class="flex-1 font-mono text-sm"
                                                        />
                                                    </div>
                                                </flux:field>
                                            </div>

                                            <!-- Quick Color Presets -->
                                            <flux:field>
                                                <flux:label>Quick Presets</flux:label>
                                                <div class="flex flex-wrap gap-2">
                                                    <flux:button 
                                                        wire:click="applyHeroPreset({{ $index }}, 'dark')"
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        Dark
                                                    </flux:button>
                                                    <flux:button 
                                                        wire:click="applyHeroPreset({{ $index }}, 'light')"
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        Light
                                                    </flux:button>
                                                    <flux:button 
                                                        wire:click="applyHeroPreset({{ $index }}, 'blue')"
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        Blue
                                                    </flux:button>
                                                    <flux:button 
                                                        wire:click="applyHeroPreset({{ $index }}, 'gradient')"
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        Gradient
                                                    </flux:button>
                                                </div>
                                            </flux:field>
                                        </div>
                                    </div>

                                @elseif($block['type'] === 'video')
                                    @php
                                        // Get video data from block
                                        $videoData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($videoData)) {
                                            $videoData = [];
                                        }
                                        $videoUrl = $videoData['video_url'] ?? $block['content'] ?? '';
                                        $videoPath = $videoData['video_path'] ?? '';
                                        $isUploaded = !empty($videoPath);
                                    @endphp
                                    <flux:field>
                                        <flux:label>Video</flux:label>
                                        
                                        @if($videoUrl)
                                            <!-- Video Preview -->
                                            <div class="mb-4">
                                                <video 
                                                    controls 
                                                    class="w-full max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                    style="max-height: 400px;"
                                                >
                                                    <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                                @if($isUploaded)
                                                    <flux:description class="mt-2">
                                                        Video stored at: {{ $videoPath }}
                                                    </flux:description>
                                                @else
                                                    <flux:description class="mt-2">
                                                        External video URL: {{ $videoUrl }}
                                                    </flux:description>
                                                @endif
                                            </div>
                                            
                                            <!-- Remove Video Button -->
                                            <div class="mb-4">
                                                <flux:button 
                                                    wire:click="removeVideo({{ $index }})"
                                                    variant="danger"
                                                    size="sm"
                                                    icon="trash"
                                                >
                                                    Remove Video
                                                </flux:button>
                                            </div>
                                        @endif
                                        
                                        <!-- Upload Video File -->
                                        <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center mb-4">
                                            <flux:icon name="video-camera" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                                <span wire:loading.remove wire:target="videoUploads.blocks.{{ $index }}">Upload a video file</span>
                                                <span wire:loading wire:target="videoUploads.blocks.{{ $index }}">Uploading...</span>
                                            </p>
                                            <label class="cursor-pointer">
                                                <input 
                                                    type="file" 
                                                    wire:model="videoUploads.blocks.{{ $index }}"
                                                    accept="video/*"
                                                    class="hidden"
                                                    wire:loading.attr="disabled"
                                                />
                                                <flux:button 
                                                    type="button"
                                                    variant="outline" 
                                                    size="sm" 
                                                    icon="arrow-up-tray"
                                                    onclick="this.previousElementSibling.click()"
                                                    wire:loading.attr="disabled"
                                                >
                                                    <span wire:loading.remove wire:target="videoUploads.blocks.{{ $index }}">Choose Video File</span>
                                                    <span wire:loading wire:target="videoUploads.blocks.{{ $index }}">Uploading...</span>
                                                </flux:button>
                                            </label>
                                            <p class="text-xs text-zinc-400 mt-2">MP4, WebM, OGG up to 100MB</p>
                                        </div>
                                        
                                        @error("videoUploads.blocks.{$index}")
                                            <flux:description variant="danger">{{ $message }}</flux:description>
                                        @enderror
                                        
                                        <!-- Or Enter Video URL -->
                                        <flux:separator class="my-4" />
                                        <flux:field>
                                            <flux:label>Or Enter Video URL</flux:label>
                                            <flux:input 
                                                wire:model="blocks.{{ $index }}.content" 
                                                placeholder="https://example.com/video.mp4 or YouTube/Vimeo embed URL" 
                                            />
                                            <flux:description>Enter a direct video URL or embed URL from YouTube/Vimeo</flux:description>
                                        </flux:field>
                                    </flux:field>

                                @else
                                    <div class="text-center py-12">
                                        <flux:icon name="cube" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                        <p class="font-medium text-zinc-900 dark:text-white mb-1">{{ ucfirst($block['type']) }} Block</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Content editing for this block type is coming soon.</p>
                                    </div>
                                @endif
                            </div>
                        </flux:card>
                    @empty
                        <!-- Empty State -->
                        <flux:card class="text-center py-16">
                            <flux:icon name="document-text" class="mx-auto h-16 w-16 text-zinc-400 mb-4" />
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Start building your page</h3>
                            <p class="text-zinc-500 dark:text-zinc-400 mb-6">Add sections (blocks) to create your page content. Each block is a full-width section.</p>
                            <flux:button wire:click="$set('showBlockSelector', true)" variant="primary" icon="plus">
                                Add Your First Section
                            </flux:button>
                        </flux:card>
                    @endforelse

                    <!-- Final Add Block Button -->
                    @if(count($blocks) > 0)
                        <div class="flex justify-center pt-6">
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
        <div class="w-[500px]  border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-y-auto">
            <div class="px-6 py-8 space-y-6">
                <!-- Template Display (Read-only) -->
                <div>
                    <flux:field>
                        <flux:label>Template</flux:label>
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700">
                            <span class="text-lg">
                                @if($template === 'modern') 🚀
                                @elseif($template === 'classic') 🏛️
                                @elseif($template === 'meditative') 🧘‍♀️
                                @else 🚀
                                @endif
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($template ?? 'modern') }}
                            </span>
                        </div>
                        <flux:description>
                            <a href="{{ route('cms.templates.manager') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                Manage templates →
                            </a>
                        </flux:description>
                    </flux:field>
                </div>

                <!-- Page Settings -->
                <flux:card>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Page Settings</h3>
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select wire:model="status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </flux:select>
                        </flux:field>

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

                        <flux:field>
                            <flux:label>Slug</flux:label>
                            <flux:input wire:model="slug" />
                            <flux:description>URL-friendly version of the title</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="description" rows="3" />
                        </flux:field>

                        <flux:field>
                            <flux:checkbox wire:model="is_homepage">
                                Set as homepage
                            </flux:checkbox>
                            <flux:description>Make this page the default homepage for your website</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:checkbox wire:model="show_in_navigation">
                                Show in navigation
                            </flux:checkbox>
                            <flux:description>Display this page in the main navigation menu</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Sort Order</flux:label>
                            <flux:input wire:model="sort_order" type="number" />
                        </flux:field>
                    </div>
                </flux:card>

                <!-- SEO Settings -->
                <flux:card>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">SEO Settings</h3>
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>SEO Title</flux:label>
                            <flux:input wire:model="seo_title" />
                            <flux:description>{{ strlen($seo_title ?? '') }}/60 characters</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>SEO Description</flux:label>
                            <flux:textarea wire:model="seo_description" rows="3" />
                            <flux:description>{{ strlen($seo_description ?? '') }}/160 characters</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Keywords</flux:label>
                            <flux:input wire:model="seo_keywords" placeholder="keyword1, keyword2, keyword3" />
                        </flux:field>
                    </div>
                </flux:card>
            </div>
        </div>
    </div>

    <!-- Block Selector Modal -->
    @if($showBlockSelector)
        <div 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            wire:click="$set('showBlockSelector', false)"
            wire:key="block-selector-backdrop"
        >
            <flux:card 
                class="max-w-3xl w-full shadow-xl" 
                wire:click.stop
                wire:key="block-selector-card"
            >
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Add a Section</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Each block is a full-width section</p>
                    </div>
                    <flux:button 
                        wire:click="$set('showBlockSelector', false)" 
                        variant="ghost" 
                        icon="x-mark"
                        type="button"
                        wire:key="close-button"
                    >
                        Close
                    </flux:button>
                </div>
                
                <div class="grid grid-cols-3 gap-3">
                    @foreach($this->blockTypes as $type => $config)
                        <flux:button 
                            wire:click="addBlock('{{ $type }}')"
                            variant="outline"
                            type="button"
                            class="h-auto flex-col gap-3 p-6 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                            wire:key="block-button-{{ $type }}"
                        >
                            <flux:icon name="{{ $config['icon'] }}" class="h-10 w-10 text-zinc-400" />
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $config['name'] }}</span>
                        </flux:button>
                    @endforeach
                </div>
            </flux:card>
        </div>
    @endif
</div>

@script
<script>
    // Listen for block-added event to initialize CKEditor 5 for richtext blocks
    $wire.on('block-added', (event) => {
        const { blockIndex, blockType } = event;
        
        // CKEditor blocks: paragraph
        const ckeditorBlocks = ['paragraph'];
        
        if (ckeditorBlocks.includes(blockType)) {
            setTimeout(() => {
                const elementId = `ckeditor-${blockType}-${blockIndex}`;
                const element = document.querySelector(`#${elementId}`);
                
                if (element && window.initCKEditor) {
                    // Get language from page or default to 'en'
                    const language = document.documentElement.lang || 'en';
                    window.initCKEditor(
                        elementId,
                        `blocks.${blockIndex}.content`,
                        '',
                        language
                    ).catch(error => {
                        console.error('CKEditor initialization error:', error);
                    });
                }
            }, 200);
        }
        
        // Reinitialize SortableJS after block is added
        setTimeout(() => {
            const container = document.querySelector('#blocks-container');
            if (container && window.initBlockSortable) {
                // Destroy existing instance
                if (window.destroySortable) {
                    window.destroySortable('blocks-container');
                }
                // Reinitialize
                window.initBlockSortable('blocks-container');
            }
        }, 150);
    });
    
    // Listen for block-removed event to reinitialize SortableJS
    $wire.on('block-removed', () => {
        setTimeout(() => {
            const container = document.querySelector('#blocks-container');
            if (container && window.initBlockSortable) {
                // Destroy existing instance first
                if (window.destroySortable) {
                    window.destroySortable('blocks-container');
                }
                // Reinitialize with fresh data attributes
                window.initBlockSortable('blocks-container');
            }
        }, 150);
    });
    
    // Reinitialize SortableJS after Livewire updates to refresh data attributes
    document.addEventListener('livewire:update', () => {
        setTimeout(() => {
            const container = document.querySelector('#blocks-container');
            if (container && window.initBlockSortable) {
                // Destroy existing instance first
                if (window.destroySortable) {
                    window.destroySortable('blocks-container');
                }
                // Reinitialize with fresh data attributes
                window.initBlockSortable('blocks-container');
            }
        }, 150);
    });
</script>
@endscript