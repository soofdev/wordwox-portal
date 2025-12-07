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
                    <button 
                        type="button"
                        wire:click="preview"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg border border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Preview
                    </button>
                    
                    @script
                    <script>
                        Livewire.on('open-preview', (event) => {
                            window.open(event.url, '_blank');
                        });
                    </script>
                    @endscript
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
                                        <flux:button 
                                            wire:click="removeBlock({{ $index }})"
                                            variant="ghost" 
                                            size="xs" 
                                            icon="x-mark"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Block Content -->
                            <div class="space-y-4" x-show="!collapsed" x-transition>
                                @if($block['type'] === 'heading')
                                    @php
                                        $headingSettingsJson = $block['settings_json'] ?? '{}';
                                        $headingSettings = is_array($headingSettingsJson) ? $headingSettingsJson : json_decode($headingSettingsJson, true) ?? [];
                                        $headingFontSize = $headingSettings['content_font_size'] ?? '';
                                    @endphp
                                    <flux:field>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <flux:label>Heading Text</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.content" 
                                                    placeholder="Enter heading text..." 
                                                />
                                            </div>
                                            <div class="w-20">
                                                <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                <flux:input 
                                                    type="number"
                                                    value="{{ is_numeric($headingFontSize) ? $headingFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $headingFontSize, $matches) ? $matches[1] : '') }}"
                                                    placeholder="32"
                                                    min="1"
                                                    wire:change="updateBlockSettings({{ $index }}, 'content_font_size', $event.target.value)"
                                                    title="Font Size (px)"
                                                />
                                            </div>
                                        </div>
                                    </flux:field>

                                @elseif($block['type'] === 'paragraph')
                                    @php
                                        $paragraphSettingsJson = $block['settings_json'] ?? '{}';
                                        $paragraphSettings = is_array($paragraphSettingsJson) ? $paragraphSettingsJson : json_decode($paragraphSettingsJson, true) ?? [];
                                        $paragraphFontSize = $paragraphSettings['content_font_size'] ?? '';
                                    @endphp
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
                                        <div class="flex items-end gap-2 mt-2">
                                            <div class="flex-1"></div>
                                            <div class="w-20">
                                                <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Font Size (px)</flux:label>
                                                <flux:input 
                                                    type="number"
                                                    value="{{ is_numeric($paragraphFontSize) ? $paragraphFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $paragraphFontSize, $matches) ? $matches[1] : '') }}"
                                                    placeholder="16"
                                                    min="1"
                                                    wire:change="updateBlockSettings({{ $index }}, 'content_font_size', $event.target.value)"
                                                    title="Font Size (px)"
                                                />
                                            </div>
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
                                        $height = $settings['height'] ?? ($settings['custom_height'] ?? '500');
                                        $titleFontSize = $settings['title_font_size'] ?? ($settings['custom_title_font_size'] ?? '');
                                        $subtitleFontSize = $settings['subtitle_font_size'] ?? ($settings['custom_subtitle_font_size'] ?? '');
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
                                                <div class="flex items-end gap-2">
                                                    <div class="flex-1">
                                                        <flux:label>Hero Title</flux:label>
                                                        <flux:input 
                                                            wire:model="blocks.{{ $index }}.title" 
                                                            placeholder="Enter hero title..." 
                                                        />
                                                    </div>
                                                    <div class="w-20">
                                                        <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                        <flux:input 
                                                            type="number"
                                                            value="{{ is_numeric($titleFontSize) ? $titleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $titleFontSize, $matches) ? $matches[1] : '') }}"
                                                            placeholder="56"
                                                            min="1"
                                                            wire:change="updateHeroSettings({{ $index }}, 'title_font_size', $event.target.value)"
                                                            title="Font Size (px)"
                                                        />
                                                    </div>
                                                </div>
                                            </flux:field>
                                            
                                            <flux:field>
                                                <div class="flex items-end gap-2">
                                                    <div class="flex-1">
                                                        <flux:label>Hero Subtitle</flux:label>
                                                        <flux:input 
                                                            wire:model="blocks.{{ $index }}.subtitle" 
                                                            placeholder="Enter hero subtitle..." 
                                                        />
                                                    </div>
                                                    <div class="w-20">
                                                        <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                        <flux:input 
                                                            type="number"
                                                            value="{{ is_numeric($subtitleFontSize) ? $subtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $subtitleFontSize, $matches) ? $matches[1] : '') }}"
                                                            placeholder="28"
                                                            min="1"
                                                            wire:change="updateHeroSettings({{ $index }}, 'subtitle_font_size', $event.target.value)"
                                                            title="Font Size (px)"
                                                        />
                                                    </div>
                                                </div>
                                            </flux:field>
                                            
                                            <flux:field>
                                                <div class="flex items-start gap-2">
                                                    <div class="flex-1">
                                                        <flux:label>Hero Description</flux:label>
                                                        <flux:textarea 
                                                            wire:model="blocks.{{ $index }}.content" 
                                                            placeholder="Enter hero description..." 
                                                            rows="3"
                                                        />
                                                    </div>
                                                    <div class="w-20">
                                                        <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                        <flux:input 
                                                            type="number"
                                                            value="{{ is_numeric($settings['content_font_size'] ?? '') ? $settings['content_font_size'] : (isset($settings['content_font_size']) && preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $settings['content_font_size'], $matches) ? $matches[1] : '') }}"
                                                            placeholder="18"
                                                            min="1"
                                                            wire:change="updateHeroSettings({{ $index }}, 'content_font_size', $event.target.value)"
                                                            title="Font Size (px)"
                                                        />
                                                    </div>
                                                </div>
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

                                            <!-- Height Settings -->
                                            <flux:field>
                                                <flux:label>Hero Height (px)</flux:label>
                                                <flux:input 
                                                    type="number"
                                                    value="{{ is_numeric($height) ? $height : (is_numeric($settings['custom_height'] ?? '') ? $settings['custom_height'] : '500') }}"
                                                    placeholder="Enter height in px (e.g., 500)"
                                                    min="1"
                                                    wire:change="updateHeroSettings({{ $index }}, 'height', $event.target.value)"
                                                />
                                                <flux:description>Minimum value: 1px</flux:description>
                                            </flux:field>


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

                                @elseif($block['type'] === 'contact')
                                    @php
                                        $contactData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($contactData)) {
                                            $contactData = [];
                                        }
                                    @endphp
                                    
                                    @php
                                        $contactSettingsJson = $block['settings_json'] ?? '{}';
                                        $contactSettings = is_array($contactSettingsJson) ? $contactSettingsJson : json_decode($contactSettingsJson, true) ?? [];
                                        $contactTitleFontSize = $contactSettings['title_font_size'] ?? '';
                                        $contactSubtitleFontSize = $contactSettings['subtitle_font_size'] ?? '';
                                    @endphp
                                    <flux:field>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <flux:label>Contact Section Title</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.title" 
                                                    placeholder="Contact Us" 
                                                />
                                            </div>
                                            <div class="w-20">
                                                <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                <flux:input 
                                                    type="number"
                                                    value="{{ is_numeric($contactTitleFontSize) ? $contactTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $contactTitleFontSize, $matches) ? $matches[1] : '') }}"
                                                    placeholder="32"
                                                    min="1"
                                                    wire:change="updateBlockSettings({{ $index }}, 'title_font_size', $event.target.value)"
                                                    title="Font Size (px)"
                                                />
                                            </div>
                                        </div>
                                    </flux:field>

                                    <flux:field>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <flux:label>Contact Section Subtitle</flux:label>
                                                <flux:input 
                                                    wire:model="blocks.{{ $index }}.subtitle" 
                                                    placeholder="Get in touch with us" 
                                                />
                                            </div>
                                            <div class="w-20">
                                                <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                <flux:input 
                                                    type="number"
                                                    value="{{ is_numeric($contactSubtitleFontSize) ? $contactSubtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $contactSubtitleFontSize, $matches) ? $matches[1] : '') }}"
                                                    placeholder="18"
                                                    min="1"
                                                    wire:change="updateBlockSettings({{ $index }}, 'subtitle_font_size', $event.target.value)"
                                                    title="Font Size (px)"
                                                />
                                            </div>
                                        </div>
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
                                        <flux:label>Google Maps Embed URL</flux:label>
                                        <flux:input 
                                            value="{{ $contactData['map_url'] ?? '' }}"
                                            wire:change="updateContactField({{ $index }}, 'map_url', $event.target.value)"
                                            placeholder="https://www.google.com/maps/embed?pb=..." 
                                            type="url"
                                        />
                                        <flux:description>
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                                <p class="mb-1"><strong>How to get the embed URL:</strong></p>
                                                <ol class="list-decimal list-inside space-y-1 ml-2">
                                                    <li>Go to <a href="https://www.google.com/maps" target="_blank" class="text-blue-600 hover:underline">Google Maps</a></li>
                                                    <li>Search for your location</li>
                                                    <li>Click "Share" button</li>
                                                    <li>Select "Embed a map" tab</li>
                                                    <li>Copy the URL from the iframe src attribute (starts with https://www.google.com/maps/embed?pb=...)</li>
                                                    <li>Paste it here</li>
                                                </ol>
                                            </div>
                                        </flux:description>
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
                                        $bannerImages = $bannerData['images'] ?? [];
                                        // Backward compatibility: if old format with image_url exists, convert it
                                        if (empty($bannerImages) && !empty($bannerData['image_url'])) {
                                            $bannerImages = [[
                                                'url' => $bannerData['image_url'],
                                                'path' => $bannerData['image_path'] ?? '',
                                                'alt_text' => $bannerData['alt_text'] ?? 'Banner image',
                                                'link_url' => $bannerData['link_url'] ?? ''
                                            ]];
                                            $bannerData['images'] = $bannerImages;
                                        }
                                        $height = $bannerData['height'] ?? '300';
                                        // Convert old height values (small, medium, etc.) to pixels
                                        if (!is_numeric($height)) {
                                            $heightMap = ['small' => '200', 'medium' => '300', 'large' => '400', 'xl' => '500'];
                                            $height = $heightMap[$height] ?? '300';
                                        }
                                        // Ensure index is always a valid integer
                                        $blockIndex = (int)$index;
                                    @endphp
                                    <div class="space-y-4" wire:key="banner-{{ $blockIndex }}">
                                        <flux:field>
                                            <flux:label>Banner Images</flux:label>
                                            
                                            <!-- Display uploaded banner images -->
                                            @if(!empty($bannerImages))
                                                @php
                                                    // Ensure array is sequentially indexed (0, 1, 2, ...)
                                                    $bannerImages = array_values($bannerImages);
                                                @endphp
                                                <div class="space-y-3 mb-4">
                                                    @foreach($bannerImages as $imgIndex => $image)
                                                        @php
                                                            // Use loop index to ensure we always have a valid numeric index
                                                            $imgIndexNum = (int)$loop->index;
                                                            $altTextField = 'image_' . $imgIndexNum . '_alt_text';
                                                            $linkUrlField = 'image_' . $imgIndexNum . '_link_url';
                                                        @endphp
                                                        <div class="relative border border-zinc-200 dark:border-zinc-700 rounded-lg p-3" wire:key="banner-image-{{ $blockIndex }}-{{ $imgIndexNum }}">
                                                            <div class="flex gap-3">
                                                                <img src="{{ $image['url'] ?? '' }}" 
                                                                     alt="{{ $image['alt_text'] ?? 'Banner image' }}" 
                                                                     class="w-24 h-24 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                                     loading="lazy">
                                                                <div class="flex-1 space-y-2">
                                                                    <flux:input 
                                                                        value="{{ $image['alt_text'] ?? '' }}"
                                                                        wire:change="updateBannerField({{ $blockIndex }}, '{{ $altTextField }}', $event.target.value)"
                                                                        placeholder="Alt text for accessibility" 
                                                                        label="Alt Text"
                                                                    />
                                                                    <flux:input 
                                                                        value="{{ $image['link_url'] ?? '' }}"
                                                                        wire:change="updateBannerField({{ $blockIndex }}, '{{ $linkUrlField }}', $event.target.value)"
                                                                        placeholder="https://example.com (optional)" 
                                                                        label="Link URL (Optional)"
                                                                    />
                                                                </div>
                                                                <flux:modal.trigger name="delete-banner-{{ $blockIndex }}-{{ $imgIndexNum }}">
                                                                    <flux:button 
                                                                        variant="ghost"
                                                                        size="xs"
                                                                        icon="trash"
                                                                        class="self-start"
                                                                    >
                                                                        Remove
                                                                    </flux:button>
                                                                </flux:modal.trigger>
                                                                
                                                                <!-- Delete Banner Image Modal -->
                                                                <flux:modal name="delete-banner-{{ $blockIndex }}-{{ $imgIndexNum }}" class="min-w-[20rem]">
                                                                    <div class="space-y-4">
                                                                        <div>
                                                                            <flux:heading size="lg">Remove Banner Image?</flux:heading>
                                                                            <flux:text class="mt-2">
                                                                                This will remove this banner image from the block.
                                                                            </flux:text>
                                                                        </div>
                                                                        <div class="flex gap-2">
                                                                            <flux:spacer />
                                                                            <flux:modal.close>
                                                                                <flux:button variant="ghost">Cancel</flux:button>
                                                                            </flux:modal.close>
                                                                            <flux:button 
                                                                                wire:click="removeBannerImageByIndex({{ $blockIndex }}, {{ $imgIndexNum }})"
                                                                                variant="danger"
                                                                                wire:loading.attr="disabled"
                                                                            >
                                                                                <span wire:loading.remove wire:target="removeBannerImageByIndex">Remove Image</span>
                                                                                <span wire:loading wire:target="removeBannerImageByIndex">
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
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            <!-- File Upload Area -->
                                            <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center" wire:loading.class="opacity-50" wire:target="imageUploads.banner.{{ $blockIndex }}">
                                                <flux:icon name="photo" class="mx-auto h-12 w-12 text-zinc-400 mb-3" />
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                                    <span wire:loading.remove wire:target="imageUploads.banner.{{ $blockIndex }}">Upload banner images</span>
                                                    <span wire:loading wire:target="imageUploads.banner.{{ $blockIndex }}">Uploading...</span>
                                                </p>
                                                <label class="cursor-pointer">
                                                    <input 
                                                        type="file" 
                                                        wire:model="imageUploads.banner.{{ $blockIndex }}"
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
                                                        <span wire:loading.remove wire:target="imageUploads.banner.{{ $blockIndex }}">Choose Banner Images</span>
                                                        <span wire:loading wire:target="imageUploads.banner.{{ $blockIndex }}">Uploading...</span>
                                                    </flux:button>
                                                </label>
                                                <flux:description class="mt-2">You can select multiple images at once</flux:description>
                                            </div>
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>Banner Height (px)</flux:label>
                                            <flux:input 
                                                type="number"
                                                value="{{ is_numeric($height) ? $height : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $height, $matches) ? $matches[1] : '300') }}"
                                                placeholder="300"
                                                min="1"
                                                wire:change="updateBannerField({{ $blockIndex }}, 'height', $event.target.value)"
                                                title="Height in pixels"
                                            />
                                            <flux:description>Minimum: 1px</flux:description>
                                        </flux:field>
                                    </div>

                                @elseif($block['type'] === 'packages')
                                    @php
                                        $packagesData = json_decode($block['data_json'] ?? '{}', true);
                                        if (!is_array($packagesData)) {
                                            $packagesData = [];
                                        }
                                        $packagesSettingsJson = $block['settings_json'] ?? '{}';
                                        $packagesSettings = is_array($packagesSettingsJson) ? $packagesSettingsJson : json_decode($packagesSettingsJson, true) ?? [];
                                        $packagesTitleFontSize = $packagesSettings['title_font_size'] ?? '';
                                        $packagesSubtitleFontSize = $packagesSettings['subtitle_font_size'] ?? '';
                                    @endphp
                                    <div class="space-y-4" wire:key="packages-{{ $index }}">
                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Title</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Our Packages" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($packagesTitleFontSize) ? $packagesTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $packagesTitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="32"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'title_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
                                        </flux:field>

                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Subtitle (Optional)</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="Choose the perfect plan for you" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($packagesSubtitleFontSize) ? $packagesSubtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $packagesSubtitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="18"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'subtitle_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
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
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Package Card Title Font Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($packagesSettings['card_title_font_size'] ?? '') ? $packagesSettings['card_title_font_size'] : (isset($packagesSettings['card_title_font_size']) && preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $packagesSettings['card_title_font_size'], $matches) ? $matches[1] : '') }}"
                                                        placeholder="24"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'card_title_font_size', $event.target.value)"
                                                        title="Font Size for individual package card titles"
                                                    />
                                                </div>
                                            </div>
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
                                    @php
                                        $coachesSettingsJson = $block['settings_json'] ?? '{}';
                                        $coachesSettings = is_array($coachesSettingsJson) ? $coachesSettingsJson : json_decode($coachesSettingsJson, true) ?? [];
                                        $coachesTitleFontSize = $coachesSettings['title_font_size'] ?? '';
                                        $coachesSubtitleFontSize = $coachesSettings['subtitle_font_size'] ?? '';
                                    @endphp
                                    <div class="space-y-4" wire:key="coaches-{{ $index }}">
                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Title</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Our Coaches" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($coachesTitleFontSize) ? $coachesTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $coachesTitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="32"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'title_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
                                        </flux:field>

                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Subtitle (Optional)</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="Meet our expert team" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($coachesSubtitleFontSize) ? $coachesSubtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $coachesSubtitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="18"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'subtitle_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
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
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Coach Card Title Font Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($coachesSettings['card_title_font_size'] ?? '') ? $coachesSettings['card_title_font_size'] : (isset($coachesSettings['card_title_font_size']) && preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $coachesSettings['card_title_font_size'], $matches) ? $matches[1] : '') }}"
                                                        placeholder="20"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'card_title_font_size', $event.target.value)"
                                                        title="Font Size for individual coach card titles"
                                                    />
                                                </div>
                                            </div>
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
                                        $scheduleSettingsJson = $block['settings_json'] ?? '{}';
                                        $scheduleSettings = is_array($scheduleSettingsJson) ? $scheduleSettingsJson : json_decode($scheduleSettingsJson, true) ?? [];
                                        $scheduleTitleFontSize = $scheduleSettings['title_font_size'] ?? '';
                                        $scheduleSubtitleFontSize = $scheduleSettings['subtitle_font_size'] ?? '';
                                    @endphp
                                    <div class="space-y-4" wire:key="schedule-{{ $index }}">
                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Title</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.title" placeholder="Schedule" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($scheduleTitleFontSize) ? $scheduleTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $scheduleTitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="32"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'title_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
                                        </flux:field>

                                        <flux:field>
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Section Subtitle (Optional)</flux:label>
                                                    <flux:input wire:model="blocks.{{ $index }}.subtitle" placeholder="View our class schedule" />
                                                </div>
                                                <div class="w-20">
                                                    <flux:label class="text-xs text-zinc-500 dark:text-zinc-400">Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($scheduleSubtitleFontSize) ? $scheduleSubtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $scheduleSubtitleFontSize, $matches) ? $matches[1] : '') }}"
                                                        placeholder="18"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'subtitle_font_size', $event.target.value)"
                                                        title="Font Size (px)"
                                                    />
                                                </div>
                                            </div>
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
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <flux:label>Schedule Item Title Font Size (px)</flux:label>
                                                    <flux:input 
                                                        type="number"
                                                        value="{{ is_numeric($scheduleSettings['card_title_font_size'] ?? '') ? $scheduleSettings['card_title_font_size'] : (isset($scheduleSettings['card_title_font_size']) && preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $scheduleSettings['card_title_font_size'], $matches) ? $matches[1] : '') }}"
                                                        placeholder="18"
                                                        min="1"
                                                        wire:change="updateBlockSettings({{ $index }}, 'card_title_font_size', $event.target.value)"
                                                        title="Font Size for individual schedule item titles"
                                                    />
                                                </div>
                                            </div>
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
                                💪
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucfirst($template ?? 'fitness') }}
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