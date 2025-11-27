<div class="min-h-screen bg-white dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
        <div class="">
            <div class="py-2">
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
                        readonly
                    />
                </div>

                <!-- Content Blocks (Each block is a full-width section) -->
                <div 
                    id="blocks-container"
                    class="space-y-6"
                    x-data="{
                        allCollapsed: false,
                        init() {
                            this.$nextTick(() => {
                                if (window.initBlockSortable) {
                                    window.initBlockSortable('blocks-container');
                                }
                            });
                        },
                        expandAll() {
                            this.allCollapsed = false;
                            this.$dispatch('expand-all-blocks');
                        },
                        collapseAll() {
                            this.allCollapsed = true;
                            this.$dispatch('collapse-all-blocks');
                        }
                    }"
                    @expand-all-blocks.window="allCollapsed = false"
                    @collapse-all-blocks.window="allCollapsed = true"
                >
                    @if(count($blocks) > 0)
                        <!-- Expand/Collapse All Controls -->
                        <div class="flex items-center justify-end gap-2 mb-4">
                            <flux:button 
                                @click="$dispatch('expand-all-blocks')"
                                variant="ghost" 
                                size="sm" 
                                icon="chevron-down"
                                class="text-zinc-600 dark:text-zinc-400"
                            >
                                Expand All
                            </flux:button>
                            <flux:button 
                                @click="$dispatch('collapse-all-blocks')"
                                variant="ghost" 
                                size="sm" 
                                icon="chevron-right"
                                class="text-zinc-600 dark:text-zinc-400"
                            >
                                Collapse All
                            </flux:button>
                        </div>
                    @endif
                    @forelse($blocks as $index => $block)
                        <flux:card 
                            class="group relative"
                            data-block-index="{{ $index }}"
                            data-block-uuid="{{ $block['uuid'] ?? '' }}"
                            id="block-{{ $index }}"
                            x-data="{ collapsed: false }"
                            @expand-all-blocks.window="collapsed = false"
                            @collapse-all-blocks.window="collapsed = true"
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
                                    
                                    <!-- Active/Inactive Checkbox with Visual Badge -->
                                    <div class="flex items-center gap-2">
                                        <flux:checkbox 
                                            wire:change="toggleBlockActive({{ $index }})"
                                            :checked="$block['is_active'] ?? true"
                                            wire:loading.attr="disabled"
                                            size="md"
                                            label="Active"
                                            label-class="ml-2 text-xs font-medium text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <button 
                                        type="button"
                                        @click="collapsed = !collapsed"
                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-zinc-100 dark:hover:bg-zinc-800 h-8 w-8"
                                        :title="collapsed ? 'Expand' : 'Collapse'"
                                    >
                                        <svg x-show="!collapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <svg x-show="collapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                    <flux:separator vertical />
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
                                        <flux:modal.trigger name="delete-block-{{ $index }}">
                                            <flux:button 
                                                variant="ghost" 
                                                size="xs" 
                                                icon="trash"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            />
                                        </flux:modal.trigger>
                                    </div>
                                </div>
                            </div>

                            <!-- Block Content -->
                            <div class="space-y-4" x-show="!collapsed" x-transition>
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
                                                <flux:modal.trigger name="delete-image-{{ $index }}">
                                                    <flux:button 
                                                        variant="ghost"
                                                        size="xs"
                                                        icon="trash"
                                                        class="absolute top-2 right-2 bg-white/90 dark:bg-zinc-800/90"
                                                    >
                                                        Remove
                                                    </flux:button>
                                                </flux:modal.trigger>
                                                
                                                <!-- Delete Image Modal -->
                                                <flux:modal name="delete-image-{{ $index }}" class="min-w-[20rem]">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <flux:heading size="lg">Remove Image?</flux:heading>
                                                            <flux:text class="mt-2">
                                                                This will remove the image from this block.
                                                            </flux:text>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <flux:spacer />
                                                            <flux:modal.close>
                                                                <flux:button variant="ghost">Cancel</flux:button>
                                                            </flux:modal.close>
                                                            <flux:button 
                                                                wire:click="confirmDelete('image', {{ $index }})"
                                                                variant="danger"
                                                                wire:loading.attr="disabled"
                                                            >
                                                                <span wire:loading.remove wire:target="confirmDelete">Remove Image</span>
                                                                <span wire:loading wire:target="confirmDelete">
                                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                    </svg>
                                                                    Removing...
                                                                </span>
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                </flux:modal>
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
                                                        <flux:modal.trigger name="delete-gallery-{{ $index }}-{{ $imgIndex }}">
                                                            <flux:button 
                                                                variant="ghost"
                                                                size="xs"
                                                                icon="trash"
                                                                class="absolute top-2 right-2 bg-white/90 dark:bg-zinc-800/90 opacity-0 group-hover:opacity-100 transition-opacity"
                                                            >
                                                                Remove
                                                            </flux:button>
                                                        </flux:modal.trigger>
                                                        
                                                        <!-- Delete Gallery Image Modal -->
                                                        <flux:modal name="delete-gallery-{{ $index }}-{{ $imgIndex }}" class="min-w-[20rem]">
                                                            <div class="space-y-4">
                                                                <div>
                                                                    <flux:heading size="lg">Remove Gallery Image?</flux:heading>
                                                                    <flux:text class="mt-2">
                                                                        This will remove this image from the gallery.
                                                                    </flux:text>
                                                                </div>
                                                                <div class="flex gap-2">
                                                                    <flux:spacer />
                                                                    <flux:modal.close>
                                                                        <flux:button variant="ghost">Cancel</flux:button>
                                                                    </flux:modal.close>
                                                                    <flux:button 
                                                                        wire:click="confirmDelete('gallery', {{ $index }}, {{ $imgIndex }})"
                                                                        variant="danger"
                                                                        wire:loading.attr="disabled"
                                                                    >
                                                                        <span wire:loading.remove wire:target="confirmDelete">Remove Image</span>
                                                                        <span wire:loading wire:target="confirmDelete">
                                                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                            </svg>
                                                                            Removing...
                                                                        </span>
                                                                    </flux:button>
                                                                </div>
                                                            </div>
                                                        </flux:modal>
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
                                                <flux:modal.trigger name="delete-video-{{ $index }}">
                                                    <flux:button 
                                                        variant="danger"
                                                        size="sm"
                                                        icon="trash"
                                                    >
                                                        Remove Video
                                                    </flux:button>
                                                </flux:modal.trigger>
                                                
                                                <!-- Delete Video Modal -->
                                                <flux:modal name="delete-video-{{ $index }}" class="min-w-[20rem]">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <flux:heading size="lg">Remove Video?</flux:heading>
                                                            <flux:text class="mt-2">
                                                                This will remove the video from this block.
                                                            </flux:text>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <flux:spacer />
                                                            <flux:modal.close>
                                                                <flux:button variant="ghost">Cancel</flux:button>
                                                            </flux:modal.close>
                                                            <flux:button 
                                                                wire:click="confirmDelete('video', {{ $index }})"
                                                                variant="danger"
                                                                wire:loading.attr="disabled"
                                                            >
                                                                <span wire:loading.remove wire:target="confirmDelete">Remove Video</span>
                                                                <span wire:loading wire:target="confirmDelete">
                                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                    </svg>
                                                                    Removing...
                                                                </span>
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                </flux:modal>
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

                                @elseif($block['type'] === 'contact')
                                    @php
                                        $contactData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($contactData)) {
                                            $contactData = [];
                                        }
                                    @endphp
                                    
                                    <flux:field>
                                        <flux:label>Contact Section Title</flux:label>
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.title" 
                                            placeholder="Contact Us" 
                                        />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Contact Section Subtitle</flux:label>
                                        <flux:input 
                                            wire:model="blocks.{{ $index }}.subtitle" 
                                            placeholder="Get in touch with us" 
                                        />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Contact Description</flux:label>
                                        <div 
                                            wire:ignore
                                            x-data="{
                                                editor: null,
                                                init() {
                                                    this.$nextTick(() => {
                                                        if (window.initCKEditor) {
                                                            const language = document.documentElement.lang || 'en';
                                                            window.initCKEditor(
                                                                'ckeditor-contact-{{ $index }}',
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
                                                        window.destroyCKEditor('ckeditor-contact-{{ $index }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-contact-{{ $index }}"
                                                wire:model.defer="blocks.{{ $index }}.content"
                                                class="min-h-[200px]"
                                            >{!! $block['content'] ?? '' !!}</textarea>
                                        </div>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Email Address</flux:label>
                                        <flux:input 
                                            value="{{ $contactData['email'] ?? '' }}"
                                            wire:change="updateContactField({{ $index }}, 'email', $event.target.value)"
                                            type="email"
                                            placeholder="contact@company.com" 
                                        />
                                        <flux:description>Primary contact email address</flux:description>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Phone Number</flux:label>
                                        <flux:input 
                                            value="{{ $contactData['phone'] ?? '' }}"
                                            wire:change="updateContactField({{ $index }}, 'phone', $event.target.value)"
                                            type="tel"
                                            placeholder="+1 (555) 123-4567" 
                                        />
                                        <flux:description>Primary contact phone number</flux:description>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Location Address</flux:label>
                                        <flux:input 
                                            value="{{ $contactData['location'] ?? '' }}"
                                            wire:change="updateContactField({{ $index }}, 'location', $event.target.value)"
                                            placeholder="123 Main Street, City, State 12345" 
                                        />
                                        <flux:description>Physical address or location for contact</flux:description>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Fax Numbers</flux:label>
                                        @php
                                            $faxNumbers = $contactData['fax'] ?? [];
                                            if (!is_array($faxNumbers)) {
                                                $faxNumbers = [];
                                            }
                                            // Ensure we have at least 3 slots
                                            while (count($faxNumbers) < 3) {
                                                $faxNumbers[] = '';
                                            }
                                        @endphp
                                        <div class="space-y-2">
                                            @foreach($faxNumbers as $faxIndex => $fax)
                                                <flux:input 
                                                    value="{{ $fax }}"
                                                    wire:change="updateFaxNumber({{ $index }}, {{ $faxIndex }}, $event.target.value)"
                                                    placeholder="Fax number {{ $faxIndex + 1 }}" 
                                                />
                                            @endforeach
                                        </div>
                                        <flux:description>Optional fax numbers (up to 3)</flux:description>
                                    </flux:field>

                                @elseif($block['type'] === 'banner')
                                    @php
                                        // Get banner data from block - ensure we get the latest data
                                        $bannerData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($bannerData)) {
                                            $bannerData = [];
                                        }
                                        $bannerImageUrl = $bannerData['image_url'] ?? '';
                                        $linkUrl = $bannerData['link_url'] ?? '';
                                        $altText = $bannerData['alt_text'] ?? 'Banner image';
                                        $height = $bannerData['height'] ?? 'medium';
                                    @endphp
                                    <div class="space-y-4" wire:key="banner-{{ $index }}">
                                        <flux:field>
                                            <flux:label>Banner Image</flux:label>
                                            
                                            @if($bannerImageUrl)
                                                <!-- Display uploaded banner image -->
                                                <div class="relative mb-3" wire:key="banner-image-{{ $index }}-{{ md5($bannerImageUrl) }}">
                                                    <img src="{{ $bannerImageUrl }}" 
                                                         alt="Banner image" 
                                                         class="w-full h-32 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                         loading="lazy">
                                                    <flux:modal.trigger name="delete-banner-{{ $index }}">
                                                        <flux:button 
                                                            variant="ghost"
                                                            size="xs"
                                                            icon="trash"
                                                            class="absolute top-2 right-2 bg-white/90 dark:bg-zinc-800/90"
                                                        >
                                                            Remove
                                                        </flux:button>
                                                    </flux:modal.trigger>
                                                    
                                                    <!-- Delete Banner Modal -->
                                                    <flux:modal name="delete-banner-{{ $index }}" class="min-w-[20rem]">
                                                        <div class="space-y-4">
                                                            <div>
                                                                <flux:heading size="lg">Remove Banner Image?</flux:heading>
                                                                <flux:text class="mt-2">
                                                                    This will remove the banner image from this block.
                                                                </flux:text>
                                                            </div>
                                                            <div class="flex gap-2">
                                                                <flux:spacer />
                                                                <flux:modal.close>
                                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                                </flux:modal.close>
                                                                <flux:button 
                                                                    wire:click="confirmDelete('banner', {{ $index }})"
                                                                    variant="danger"
                                                                    wire:loading.attr="disabled"
                                                                >
                                                                    <span wire:loading.remove wire:target="confirmDelete">Remove Banner</span>
                                                                    <span wire:loading wire:target="confirmDelete">
                                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                        </svg>
                                                                        Removing...
                                                                    </span>
                                                                </flux:button>
                                                            </div>
                                                        </div>
                                                    </flux:modal>
                                                </div>
                                            @endif
                                            
                                            <!-- File Upload Area -->
                                            <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center {{ $bannerImageUrl ? 'hidden' : 'block' }}" wire:loading.class="opacity-50">
                                                <flux:icon name="photo" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                                    <span wire:loading.remove wire:target="imageUploads.blocks.{{ $index }}">Upload a banner image</span>
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
                                                        <span wire:loading.remove wire:target="imageUploads.blocks.{{ $index }}">Choose Banner Image</span>
                                                        <span wire:loading wire:target="imageUploads.blocks.{{ $index }}">Uploading...</span>
                                                    </flux:button>
                                                </label>
                                            </div>
                                            
                                            @if($bannerImageUrl)
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
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Banner Height</flux:label>
                                            <flux:select wire:change="updateBannerField({{ $index }}, 'height', $event.target.value)">
                                                <option value="small" {{ $height === 'small' ? 'selected' : '' }}>Small (200px)</option>
                                                <option value="medium" {{ $height === 'medium' ? 'selected' : '' }}>Medium (300px)</option>
                                                <option value="large" {{ $height === 'large' ? 'selected' : '' }}>Large (400px)</option>
                                                <option value="xl" {{ $height === 'xl' ? 'selected' : '' }}>Extra Large (500px)</option>
                                            </flux:select>
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Link URL (Optional)</flux:label>
                                            <flux:input 
                                                value="{{ $linkUrl }}"
                                                wire:change="updateBannerField({{ $index }}, 'link_url', $event.target.value)"
                                                placeholder="https://example.com" 
                                            />
                                            <flux:description>Make the banner clickable by adding a link</flux:description>
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Alt Text</flux:label>
                                            <flux:input 
                                                value="{{ $altText }}"
                                                wire:change="updateBannerField({{ $index }}, 'alt_text', $event.target.value)"
                                                placeholder="Describe the banner image for accessibility" 
                                            />
                                            <flux:description>Important for accessibility and SEO</flux:description>
                                        </flux:field>
                                    </div>

                                @elseif($block['type'] === 'packages')
                                    @php
                                        $packagesData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($packagesData)) {
                                            $packagesData = [];
                                        }
                                    @endphp
                                    <div class="space-y-4" wire:key="packages-{{ $index }}">
                                        <flux:field>
                                            <flux:label>Section Title</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Our Packages" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Section Subtitle (Optional)</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="Choose the perfect plan for you" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Layout</flux:label>
                                            <flux:select wire:change="updatePackagesField({{ $index }}, 'layout', $event.target.value)">
                                                <option value="grid" {{ ($packagesData['layout'] ?? 'grid') === 'grid' ? 'selected' : '' }}>Grid</option>
                                                <option value="list" {{ ($packagesData['layout'] ?? 'grid') === 'list' ? 'selected' : '' }}>List</option>
                                            </flux:select>
                                        </flux:field>

                                        @if(($packagesData['layout'] ?? 'grid') === 'grid')
                                        <flux:field>
                                            <flux:label>Number of Columns</flux:label>
                                            <flux:select wire:change="updatePackagesField({{ $index }}, 'columns', $event.target.value)">
                                                <option value="2" {{ ($packagesData['columns'] ?? 3) == 2 ? 'selected' : '' }}>2 Columns</option>
                                                <option value="3" {{ ($packagesData['columns'] ?? 3) == 3 ? 'selected' : '' }}>3 Columns</option>
                                                <option value="4" {{ ($packagesData['columns'] ?? 3) == 4 ? 'selected' : '' }}>4 Columns</option>
                                            </flux:select>
                                        </flux:field>
                                        @endif

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($packagesData['show_description'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updatePackagesField({{ $index }}, 'show_description', $event.target.checked)"
                                                label="Show plan descriptions" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($packagesData['show_programs'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updatePackagesField({{ $index }}, 'show_programs', $event.target.checked)"
                                                label="Show included programs/classes" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Buy Button Text</flux:label>
                                            <flux:input 
                                                value="{{ $packagesData['buy_button_text'] ?? 'Buy' }}"
                                                wire:change="updatePackagesField({{ $index }}, 'buy_button_text', $event.target.value)"
                                                placeholder="Buy" 
                                            />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Purchase at Gym Button Text</flux:label>
                                            <flux:input 
                                                value="{{ $packagesData['purchase_at_gym_text'] ?? 'Purchase at the Gym' }}"
                                                wire:change="updatePackagesField({{ $index }}, 'purchase_at_gym_text', $event.target.value)"
                                                placeholder="Purchase at the Gym" 
                                            />
                                        </flux:field>

                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                                <strong>Note:</strong> This block will automatically display all active organization plans (packages) from your system. Plans are fetched based on the current organization context.
                                            </p>
                                        </div>
                                    </div>

                                @elseif($block['type'] === 'coaches')
                                    @php
                                        $coachesData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($coachesData)) {
                                            $coachesData = [];
                                        }
                                    @endphp
                                    <div class="space-y-4" wire:key="coaches-{{ $index }}">
                                        <flux:field>
                                            <flux:label>Section Title</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Our Coaches" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Section Subtitle (Optional)</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="Meet our expert team" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Layout</flux:label>
                                            <flux:select wire:change="updateCoachesField({{ $index }}, 'layout', $event.target.value)">
                                                <option value="grid" {{ ($coachesData['layout'] ?? 'grid') === 'grid' ? 'selected' : '' }}>Grid</option>
                                                <option value="list" {{ ($coachesData['layout'] ?? 'grid') === 'list' ? 'selected' : '' }}>List</option>
                                            </flux:select>
                                        </flux:field>

                                        @if(($coachesData['layout'] ?? 'grid') === 'grid')
                                        <flux:field>
                                            <flux:label>Number of Columns</flux:label>
                                            <flux:select wire:change="updateCoachesField({{ $index }}, 'columns', $event.target.value)">
                                                <option value="2" {{ ($coachesData['columns'] ?? 3) == 2 ? 'selected' : '' }}>2 Columns</option>
                                                <option value="3" {{ ($coachesData['columns'] ?? 3) == 3 ? 'selected' : '' }}>3 Columns</option>
                                                <option value="4" {{ ($coachesData['columns'] ?? 3) == 4 ? 'selected' : '' }}>4 Columns</option>
                                            </flux:select>
                                        </flux:field>
                                        @endif

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($coachesData['show_photo'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updateCoachesField({{ $index }}, 'show_photo', $event.target.checked)"
                                                label="Show coach photos" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($coachesData['show_bio'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updateCoachesField({{ $index }}, 'show_bio', $event.target.checked)"
                                                label="Show coach bio" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>View Profile Button Text</flux:label>
                                            <flux:input 
                                                value="{{ $coachesData['view_profile_text'] ?? 'View Profile' }}"
                                                wire:change="updateCoachesField({{ $index }}, 'view_profile_text', $event.target.value)"
                                                placeholder="View Profile" 
                                            />
                                        </flux:field>

                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                                <strong>Note:</strong> This block will automatically display all coaches (staff members on roster) from your organization. Coaches are identified by having <code>isOnRoster = true</code>.
                                            </p>
                                        </div>
                                    </div>

                                @elseif($block['type'] === 'schedule')
                                    @php
                                        $scheduleData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($scheduleData)) {
                                            $scheduleData = [];
                                        }
                                    @endphp
                                    <div class="space-y-4" wire:key="schedule-{{ $index }}">
                                        <flux:field>
                                            <flux:label>Section Title</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Schedule" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Section Subtitle (Optional)</flux:label>
                                            <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="View our class schedule" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Default Date</flux:label>
                                            <flux:select wire:change="updateScheduleField({{ $index }}, 'default_date', $event.target.value)">
                                                <option value="today" {{ ($scheduleData['default_date'] ?? 'today') === 'today' ? 'selected' : '' }}>Today</option>
                                                <option value="tomorrow" {{ ($scheduleData['default_date'] ?? 'today') === 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                                            </flux:select>
                                            <flux:description>Or enter a specific date in YYYY-MM-DD format in the text field below</flux:description>
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Specific Date (Optional)</flux:label>
                                            <flux:input 
                                                type="date"
                                                value="{{ ($scheduleData['default_date'] ?? 'today') !== 'today' && ($scheduleData['default_date'] ?? 'today') !== 'tomorrow' ? $scheduleData['default_date'] : '' }}"
                                                wire:change="updateScheduleField({{ $index }}, 'default_date', $event.target.value)"
                                                placeholder="YYYY-MM-DD" 
                                            />
                                            <flux:description>Leave empty to use default date above</flux:description>
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Days to Show</flux:label>
                                            <flux:select wire:change="updateScheduleField({{ $index }}, 'days_to_show', $event.target.value)">
                                                <option value="1" {{ ($scheduleData['days_to_show'] ?? 1) == 1 ? 'selected' : '' }}>Single Day</option>
                                                <option value="7" {{ ($scheduleData['days_to_show'] ?? 1) == 7 ? 'selected' : '' }}>Week (7 days)</option>
                                            </flux:select>
                                        </flux:field>

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($scheduleData['show_date_navigation'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updateScheduleField({{ $index }}, 'show_date_navigation', $event.target.checked)"
                                                label="Show date navigation (Previous/Next/Today buttons)" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:checkbox 
                                                checked="{{ ($scheduleData['show_drop_in_button'] ?? true) ? 'true' : 'false' }}"
                                                wire:change="updateScheduleField({{ $index }}, 'show_drop_in_button', $event.target.checked)"
                                                label="Show \"Drop In\" button on events" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Drop In Button Text</flux:label>
                                            <flux:input 
                                                value="{{ $scheduleData['drop_in_text'] ?? 'Drop In' }}"
                                                wire:change="updateScheduleField({{ $index }}, 'drop_in_text', $event.target.value)"
                                                placeholder="Drop In" 
                                            />
                                        </flux:field>

                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                                <strong>Note:</strong> This block will automatically display events/classes from your organization's schedule. Events are fetched based on the selected date and filtered to show only active, non-canceled events.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Section Customization -->
                                    @if(!in_array($block['type'], ['coaches', 'schedule', 'packages']))
                                    @php
                                        $settingsJson = $block['settings_json'] ?? '{}';
                                        $settings = is_array($settingsJson) ? $settingsJson : json_decode($settingsJson, true) ?? [];
                                        $layout = $settings['layout'] ?? [];
                                        $spacing = $settings['spacing'] ?? [];
                                        $background = $settings['background'] ?? [];
                                        $typography = $settings['typography'] ?? [];
                                    @endphp

                                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700" 
                                         x-data="{ showSectionSettings: false }">
                                        
                                        <!-- Section Settings Toggle -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                                </svg>
                                                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Section Settings</h4>
                                            </div>
                                            <button 
                                                @click="showSectionSettings = !showSectionSettings"
                                                type="button"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-md transition-all duration-200"
                                                :class="showSectionSettings ? 
                                                    'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800' : 
                                                    'bg-zinc-100 text-zinc-600 border border-zinc-200 hover:bg-zinc-200 hover:text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 dark:hover:bg-zinc-700'"
                                            >
                                                <svg class="w-3 h-3 transition-transform duration-200" :class="showSectionSettings ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                                <span x-text="showSectionSettings ? 'Hide Settings' : 'Customize Section'"></span>
                                            </button>
                                        </div>

                                        <!-- Section Settings Panel -->
                                        <div x-show="showSectionSettings" x-collapse class="space-y-6 bg-white dark:bg-zinc-800 rounded-lg p-5 mt-4 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                                            
                                            <!-- Quick Presets -->
                                            <div class="space-y-3">
                                                <h5 class="text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Quick Presets</h5>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                    <button 
                                                        wire:click="applySectionPreset({{ $index }}, 'default')"
                                                        type="button"
                                                        class="flex items-center justify-center px-3 py-2 text-xs font-medium rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 hover:border-zinc-300 transition-colors dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600"
                                                    >
                                                        🎯 Default
                                                    </button>
                                                    <button 
                                                        wire:click="applySectionPreset({{ $index }}, 'hero_section')"
                                                        type="button"
                                                        class="flex items-center justify-center px-3 py-2 text-xs font-medium rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 hover:border-zinc-300 transition-colors dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600"
                                                    >
                                                        🚀 Hero Style
                                                    </button>
                                                    <button 
                                                        wire:click="applySectionPreset({{ $index }}, 'content_section')"
                                                        type="button"
                                                        class="flex items-center justify-center px-3 py-2 text-xs font-medium rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 hover:border-zinc-300 transition-colors dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600"
                                                    >
                                                        📄 Content Style
                                                    </button>
                                                    <button 
                                                        wire:click="applySectionPreset({{ $index }}, 'minimal')"
                                                        type="button"
                                                        class="flex items-center justify-center px-3 py-2 text-xs font-medium rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 hover:border-zinc-300 transition-colors dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600"
                                                    >
                                                        ✨ Minimal
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Layout Settings -->
                                            <div class="space-y-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                                                    </svg>
                                                    <h5 class="text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Layout</h5>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Section Width</label>
                                                        <select wire:change="updateSectionLayout({{ $index }}, 'width', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="full" {{ ($layout['width'] ?? 'container') === 'full' ? 'selected' : '' }}>📏 Full Width</option>
                                                            <option value="container" {{ ($layout['width'] ?? 'container') === 'container' ? 'selected' : '' }}>📦 Container</option>
                                                            <option value="narrow" {{ ($layout['width'] ?? 'container') === 'narrow' ? 'selected' : '' }}>📝 Narrow</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Content Alignment</label>
                                                        <select wire:change="updateSectionLayout({{ $index }}, 'alignment', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="left" {{ ($layout['alignment'] ?? 'center') === 'left' ? 'selected' : '' }}>⬅️ Left</option>
                                                            <option value="center" {{ ($layout['alignment'] ?? 'center') === 'center' ? 'selected' : '' }}>🎯 Center</option>
                                                            <option value="right" {{ ($layout['alignment'] ?? 'center') === 'right' ? 'selected' : '' }}>➡️ Right</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Spacing Settings -->
                                            <div class="space-y-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                                    </svg>
                                                    <h5 class="text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Spacing</h5>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Top Padding</label>
                                                        <select wire:change="updateSectionSpacing({{ $index }}, 'padding_top', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="none" {{ ($spacing['padding_top'] ?? 'md') === 'none' ? 'selected' : '' }}>🚫 None</option>
                                                            <option value="xs" {{ ($spacing['padding_top'] ?? 'md') === 'xs' ? 'selected' : '' }}>📏 XS (0.5rem)</option>
                                                            <option value="sm" {{ ($spacing['padding_top'] ?? 'md') === 'sm' ? 'selected' : '' }}>📐 SM (1rem)</option>
                                                            <option value="md" {{ ($spacing['padding_top'] ?? 'md') === 'md' ? 'selected' : '' }}>📋 MD (2rem)</option>
                                                            <option value="lg" {{ ($spacing['padding_top'] ?? 'md') === 'lg' ? 'selected' : '' }}>📏 LG (3rem)</option>
                                                            <option value="xl" {{ ($spacing['padding_top'] ?? 'md') === 'xl' ? 'selected' : '' }}>📐 XL (4rem)</option>
                                                            <option value="2xl" {{ ($spacing['padding_top'] ?? 'md') === '2xl' ? 'selected' : '' }}>📊 2XL (6rem)</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Bottom Padding</label>
                                                        <select wire:change="updateSectionSpacing({{ $index }}, 'padding_bottom', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="none" {{ ($spacing['padding_bottom'] ?? 'md') === 'none' ? 'selected' : '' }}>🚫 None</option>
                                                            <option value="xs" {{ ($spacing['padding_bottom'] ?? 'md') === 'xs' ? 'selected' : '' }}>📏 XS (0.5rem)</option>
                                                            <option value="sm" {{ ($spacing['padding_bottom'] ?? 'md') === 'sm' ? 'selected' : '' }}>📐 SM (1rem)</option>
                                                            <option value="md" {{ ($spacing['padding_bottom'] ?? 'md') === 'md' ? 'selected' : '' }}>📋 MD (2rem)</option>
                                                            <option value="lg" {{ ($spacing['padding_bottom'] ?? 'md') === 'lg' ? 'selected' : '' }}>📏 LG (3rem)</option>
                                                            <option value="xl" {{ ($spacing['padding_bottom'] ?? 'md') === 'xl' ? 'selected' : '' }}>📐 XL (4rem)</option>
                                                            <option value="2xl" {{ ($spacing['padding_bottom'] ?? 'md') === '2xl' ? 'selected' : '' }}>📊 2XL (6rem)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Background Settings -->
                                            <div class="space-y-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                                    </svg>
                                                    <h5 class="text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Background</h5>
                                                </div>
                                                <div class="space-y-4">
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Background Type</label>
                                                        <select wire:change="updateSectionBackground({{ $index }}, 'type', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="none" {{ ($background['type'] ?? 'color') === 'none' ? 'selected' : '' }}>None</option>
                                                            <option value="color" {{ ($background['type'] ?? 'color') === 'color' ? 'selected' : '' }}>Solid Color</option>
                                                            <option value="gradient" {{ ($background['type'] ?? 'color') === 'gradient' ? 'selected' : '' }}>Gradient</option>
                                                            <option value="image" {{ ($background['type'] ?? 'color') === 'image' ? 'selected' : '' }}>Image</option>
                                                        </select>
                                                    </div>

                                                    @if(($background['type'] ?? 'color') === 'color')
                                                        <div class="space-y-2">
                                                            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Background Color</label>
                                                            <div class="flex items-center gap-2">
                                                                <input 
                                                                    type="color" 
                                                                    value="{{ $background['color'] ?? '#ffffff' }}"
                                                                    wire:change="updateSectionBackground({{ $index }}, 'color', $event.target.value)"
                                                                    class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer"
                                                                />
                                                                <input 
                                                                    type="text" 
                                                                    value="{{ $background['color'] ?? '#ffffff' }}"
                                                                    wire:change="updateSectionBackground({{ $index }}, 'color', $event.target.value)"
                                                                    placeholder="#ffffff"
                                                                    class="flex-1 px-3 py-2 text-sm font-mono border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                                                />
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if(($background['type'] ?? 'color') === 'gradient')
                                                        <div class="space-y-2">
                                                            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Gradient Style</label>
                                                            <select wire:change="updateSectionBackground({{ $index }}, 'gradient', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                                <option value="bg-gradient-to-r from-blue-500 to-purple-600">Blue to Purple</option>
                                                                <option value="bg-gradient-to-r from-pink-500 to-rose-500">Pink to Rose</option>
                                                                <option value="bg-gradient-to-r from-green-400 to-blue-500">Green to Blue</option>
                                                                <option value="bg-gradient-to-br from-purple-400 to-pink-400">Purple to Pink</option>
                                                                <option value="bg-gradient-to-r from-yellow-400 to-orange-500">Yellow to Orange</option>
                                                                <option value="bg-gradient-to-r from-gray-100 to-gray-200">Light Gray</option>
                                                                <option value="bg-gradient-to-r from-gray-700 to-gray-900">Dark Gray</option>
                                                            </select>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Typography Settings -->
                                            <div class="space-y-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                                    </svg>
                                                    <h5 class="text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">Typography</h5>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Text Alignment</label>
                                                        <select wire:change="updateSectionTypography({{ $index }}, 'text_align', $event.target.value)" class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                                            <option value="left" {{ ($typography['text_align'] ?? 'left') === 'left' ? 'selected' : '' }}>Left</option>
                                                            <option value="center" {{ ($typography['text_align'] ?? 'left') === 'center' ? 'selected' : '' }}>Center</option>
                                                            <option value="right" {{ ($typography['text_align'] ?? 'left') === 'right' ? 'selected' : '' }}>Right</option>
                                                            <option value="justify" {{ ($typography['text_align'] ?? 'left') === 'justify' ? 'selected' : '' }}>Justify</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="space-y-2">
                                                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Text Color</label>
                                                        <div class="flex items-center gap-2">
                                                            <input 
                                                                type="color" 
                                                                value="{{ $typography['text_color'] ?? '#1f2937' }}"
                                                                wire:change="updateSectionTypography({{ $index }}, 'text_color', $event.target.value)"
                                                                class="w-12 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer"
                                                            />
                                                            <input 
                                                                type="text" 
                                                                value="{{ $typography['text_color'] ?? '#1f2937' }}"
                                                                wire:change="updateSectionTypography({{ $index }}, 'text_color', $event.target.value)"
                                                                placeholder="#1f2937"
                                                                class="flex-1 px-3 py-2 text-sm font-mono border border-zinc-200 rounded-md bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                @else
                                    <div class="text-center py-12">
                                        <flux:icon name="cube" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                        <p class="font-medium text-zinc-900 dark:text-white mb-1">{{ ucfirst($block['type']) }} Block</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Content editing for this block type is coming soon.</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Delete Block Confirmation Modal -->
                            <flux:modal name="delete-block-{{ $index }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete {{ ucfirst($block['type']) }} Block?</flux:heading>
                                        <flux:text class="mt-2">
                                            This will permanently remove this {{ $block['type'] }} block from your page.<br>
                                            This action cannot be undone.
                                        </flux:text>
                                    </div>

                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:modal.close>
                                            <flux:button 
                                                wire:click="removeBlock({{ $index }})"
                                                variant="danger"
                                                wire:loading.attr="disabled"
                                            >
                                                <span wire:loading.remove wire:target="removeBlock">Delete Block</span>
                                                <span wire:loading wire:target="removeBlock">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Deleting...
                                                </span>
                                            </flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
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
                            Templates are applied organization-wide and affect all pages.
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
                            <flux:label>Slug</flux:label>
                            <flux:input 
                                wire:model="slug" 
                                placeholder="contact-us"
                            />
                            <flux:description>URL-friendly version of the page (e.g., contact-us). Leave empty to auto-generate from title.</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="description" rows="3" />
                        </flux:field>

                        <flux:field>
                            <flux:checkbox 
                                wire:model="is_homepage"
                                label="Set as homepage" />
                            <flux:description>Make this page the default homepage for your website</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:checkbox 
                                wire:model="show_in_navigation"
                                label="Show in navigation" />
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

                <!-- My Website Link -->
                <flux:card>
                    <div class="text-center">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Preview Your Website</h3>
                        <a href="{{ url('/') }}" 
                           target="_blank" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Visit My Website
                        </a>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                            Opens your public website in a new tab
                        </p>
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

    <!-- CKEditor 5 CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
    <style>
        /* Dark Mode Support for CKEditor */
        html.dark .ck.ck-editor__editable,
        html.dark .ck.ck-content {
            background: #1e1e1e !important;
            color: white !important;
        }

        html.dark .ck.ck-toolbar {
            background: #2b2b2b !important;
            border-color: #444 !important;
        }

        html.dark .ck.ck-button {
            color: white !important;
        }

        html.dark .ck.ck-button.ck-on {
            background: #555 !important;
        }

        html.dark .ck.ck-dropdown__panel {
            background: #2b2b2b !important;
            border-color: #444 !important;
        }

        html.dark .ck.ck-list__item {
            color: white !important;
        }

        html.dark .ck.ck-list__item:hover {
            background: #444 !important;
        }
    </style>

    @script
    <script>
    // Global CKEditor instances storage
    window.ckeditorInstances = window.ckeditorInstances || {};

    // Simplified CKEditor initialization with CDN (like the example)
    window.initCKEditor = async function(elementId, wireModel, initialContent = '', language = 'en') {
        const element = document.querySelector(`#${elementId}`);
        if (!element) {
            console.error(`Element #${elementId} not found`);
            return Promise.resolve();
        }

        // Destroy existing instance if any
        if (window.ckeditorInstances[elementId]) {
            try {
                await window.ckeditorInstances[elementId].destroy();
            } catch (error) {
                console.error('Error destroying CKEditor:', error);
            }
            delete window.ckeditorInstances[elementId];
        }

        // Wait for ClassicEditor to be available
        if (!window.ClassicEditor) {
            console.error('ClassicEditor not loaded yet');
            return Promise.resolve();
        }

        try {
            const editor = await ClassicEditor.create(element, {
                toolbar: {
                    items: [
                        'heading', '|',
                        'fontFamily', 'fontSize', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'alignment:left', 'alignment:center', 'alignment:right', 'alignment:justify', '|',
                        'bulletedList', 'numberedList', 'todoList', '|',
                        'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                        'imageUpload', 'link', 'mediaEmbed', '|',
                        'blockQuote', 'codeBlock', '|',
                        'undo', 'redo', '|',
                        'sourceEditing', '|',
                        'ltr', 'rtl'
                    ]
                },
                alignment: {
                    options: ['left', 'center', 'right', 'justify']
                },
                language: language,
                simpleUpload: {
                    uploadUrl: '{{ route("cms.upload-image") }}',
                    withCredentials: true,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                },
                image: {
                    toolbar: [
                        'imageStyle:inline',
                        'imageStyle:block',
                        'imageStyle:side',
                        '|',
                        'toggleImageCaption',
                        'imageTextAlternative'
                    ]
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells'
                    ]
                }
            });

            // Set initial content
            if (initialContent) {
                editor.setData(initialContent);
            }

            // Sync with Livewire on change
            editor.model.document.on('change:data', () => {
                const data = editor.getData();
                @this.set(wireModel, data);
            });

            // Store instance
            window.ckeditorInstances[elementId] = editor;

            console.log(`CKEditor initialized: #${elementId}`);
            return editor;
        } catch (error) {
            console.error('CKEditor initialization error:', error);
            return Promise.resolve();
        }
    };

    // Destroy CKEditor instance
    window.destroyCKEditor = function(elementId) {
        if (window.ckeditorInstances && window.ckeditorInstances[elementId]) {
            return window.ckeditorInstances[elementId].destroy()
                .then(() => {
                    delete window.ckeditorInstances[elementId];
                    console.log(`CKEditor destroyed: #${elementId}`);
                })
                .catch((error) => {
                    console.error('CKEditor destruction error:', error);
                });
        }
    };

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
    <!-- Delete Confirmation Modal (Legacy - kept for compatibility) -->
    @if($showDeleteConfirm)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block !important;">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="cancelDelete"></div>
                
                <!-- Modal Content -->
                <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-sm transform transition-all" wire:click.stop>
                    <!-- Close Button -->
                    <button wire:click="cancelDelete" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    
                    <!-- Modal Content -->
                    <div class="px-6 py-8 text-center">
                        <!-- Icon -->
                        <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Confirm Delete
                        </h3>
                        
                        <!-- Message -->
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            {{ $deleteConfirmMessage }}
                        </p>
                        
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
                                wire:click="executeDelete" 
                                class="flex-1 bg-red-500 hover:bg-red-600 text-white"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                            >
                                <span wire:loading.remove wire:target="executeDelete">
                                    Delete
                                </span>
                                <span wire:loading wire:target="executeDelete" class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Deleting...
                                </span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @endscript
    
    <!-- Flux Toast Component -->
    <flux:toast position="top end" />
</div>