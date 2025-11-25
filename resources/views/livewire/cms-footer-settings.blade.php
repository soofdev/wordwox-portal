<div class="min-h-screen bg-white dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Footer Settings</h1>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Manage your website footer content with dynamic blocks</p>
                    </div>
                    <flux:button 
                        wire:click="$set('showBlockSelector', true)"
                        variant="primary"
                        icon="plus"
                        wire:loading.attr="disabled"
                    >
                        Add Block
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex">
        <!-- Main Editor Area -->
        <div class="flex-1 overflow-y-auto bg-white dark:bg-zinc-800">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
                <!-- Footer Title -->
                <div class="bg-white dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-600 rounded-xl p-6 mb-8 shadow-sm dark:shadow-none">
                    <flux:input 
                        value="Footer Content"
                        readonly
                        class="text-3xl font-bold border-none p-0 focus:ring-0 bg-transparent shadow-none text-zinc-900 dark:text-white"
                        style="font-size: 2rem; line-height: 2.5rem;"
                    />
                </div>

                <!-- Content Blocks (Each block is a full-width section) -->
                <div 
                    id="footer-blocks-container"
                    class="space-y-6"
                >
                    @forelse($footerBlocks as $blockIndex => $block)
                        <flux:card 
                            class="group relative"
                            data-block-index="{{ $blockIndex }}"
                            data-block-id="{{ $block['id'] }}"
                            id="footer-block-{{ $block['id'] }}"
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
                                        
                                    <!-- Active/Inactive Checkbox -->
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox 
                                                wire:change="toggleBlockActive({{ $block['id'] }})"
                                                :checked="$block['is_active'] ?? true"
                                                wire:loading.attr="disabled"
                                                size="md"
                                                label="Active"
                                                label-class="ml-2 text-xs font-medium text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                    </div>
                                    
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <flux:button 
                                        wire:click="moveBlockUp({{ $block['id'] }})" 
                                        wire:loading.attr="disabled"
                                        variant="ghost" 
                                        size="xs" 
                                        icon="chevron-up"
                                        @if($blockIndex === 0) disabled @endif
                                    />
                                        <flux:button 
                                        wire:click="moveBlockDown({{ $block['id'] }})" 
                                        wire:loading.attr="disabled"
                                            variant="ghost" 
                                            size="xs" 
                                        icon="chevron-down"
                                        @if($blockIndex === count($footerBlocks) - 1) disabled @endif
                                        />
                                        <flux:separator vertical />
                                    <flux:modal.trigger name="delete-footer-block-{{ $block['id'] }}">
                                        <flux:button 
                                            variant="ghost" 
                                            size="xs" 
                                            icon="trash"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        />
                                    </flux:modal.trigger>
                                </div>
                                    </div>

                            <!-- Block Content -->
                                    <div class="space-y-4">
                                @if($block['type'] === 'heading')
                                        <flux:field>
                                        <flux:label>Heading Text</flux:label>
                                            <flux:input 
                                            wire:model.lazy="footerBlocks.{{ $blockIndex }}.content"
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
                                                            const language = document.documentElement.lang || 'en';
                                                            window.initCKEditor(
                                                                'ckeditor-footer-paragraph-{{ $block['id'] }}',
                                                                'footer-block-content-{{ $block['id'] }}',
                                                                @js($block['content'] ?? ''),
                                                                language
                                                            ).then(editor => {
                                                                this.editor = editor;
                                                                // Sync with Livewire on change
                                                                editor.model.document.on('change:data', () => {
                                                                    const data = editor.getData();
                                                                    @this.call('updateBlockField', {{ $block['id'] }}, 'content', data);
                                                                });
                                                            }).catch(error => {
                                                                console.error('CKEditor initialization error:', error);
                                                            });
                                                        }
                                                    });
                                                },
                                                destroy() {
                                                    if (this.editor && window.destroyCKEditor) {
                                                        window.destroyCKEditor('ckeditor-footer-paragraph-{{ $block['id'] }}');
                                                    }
                                                }
                                            }"
                                        >
                                            <textarea 
                                                id="ckeditor-footer-paragraph-{{ $block['id'] }}"
                                                class="min-h-[300px]"
                                            >{!! $block['content'] ?? '' !!}</textarea>
                                        </div>
                                    </flux:field>

                                @elseif($block['type'] === 'text')
                                            <flux:field>
                                                <flux:label>Text Content</flux:label>
                                                <flux:textarea 
                                            wire:model.lazy="footerBlocks.{{ $blockIndex }}.content"
                                                    rows="6"
                                                    placeholder="Enter text content..."
                                                />
                                            </flux:field>
                                        
                                        @elseif($block['type'] === 'html')
                                            <flux:field>
                                                <flux:label>HTML Content</flux:label>
                                                <flux:textarea 
                                            wire:model.lazy="footerBlocks.{{ $blockIndex }}.content"
                                            rows="8"
                                            placeholder="Enter HTML content..."
                                                    class="font-mono text-sm"
                                                />
                                            </flux:field>
                                        
                                @elseif($block['type'] === 'links')
                                    @php
                                        $links = [];
                                        try {
                                            if (is_string($block['content'])) {
                                                $links = json_decode($block['content'], true) ?? [];
                                            } elseif (is_array($block['content'])) {
                                                $links = $block['content'];
                                            }
                                        } catch (\Exception $e) {
                                            $links = [];
                                        }
                                        
                                        // Ensure we have at least 3 empty link slots
                                        while (count($links) < 3) {
                                            $links[] = ['label' => '', 'url' => ''];
                                        }
                                    @endphp
                                    
                                        <div class="space-y-4">
                                        <div class="text-sm font-medium text-zinc-800 dark:text-white">Navigation Links</div>                                        @foreach($links as $linkIndex => $link)
                                            <div class="">
                                                <div class="flex items-center justify-between mb-3">
                                                    <div class="text-sm font-semibold text-zinc-700 dark:text-white">
                                                        Link {{ $linkIndex + 1 }}
                                                    </div>
                                                    @if($linkIndex >= 2)
                                                        <button 
                                                            type="button"
                                                            wire:click="removeLinkFromBlock({{ $block['id'] }}, {{ $linkIndex }})"
                                                            class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                                                        >
                                                            Remove
                                                        </button>
                                                    @endif
                                                </div>
                                                
                                                <div class="grid grid-cols-2 gap-3">
                                                    <flux:field>
                                                        <flux:label>Link Name</flux:label>
                                                        <flux:input 
                                                            wire:change="updateLinkInBlock({{ $block['id'] }}, {{ $linkIndex }}, 'label', $event.target.value)"
                                                            value="{{ $link['label'] ?? '' }}"
                                                            placeholder="Home"
                                                        />
                                                    </flux:field>
                                                    
                                                    <flux:field>
                                                        <flux:label>Link URL</flux:label>
                                                        <flux:input 
                                                            wire:change="updateLinkInBlock({{ $block['id'] }}, {{ $linkIndex }}, 'url', $event.target.value)"
                                                            value="{{ $link['url'] ?? '' }}"
                                                            placeholder="/"
                                                        />
                                                    </flux:field>
                                                </div>
                                            </div>
                                        @endforeach
                                        
                                        <flux:button 
                                            wire:click="addLinkToBlock({{ $block['id'] }})"
                                            variant="outline"
                                            size="sm"
                                            icon="plus"
                                            class="w-full"
                                        >
                                            Add Another Link
                                        </flux:button>
                                    </div>                                @elseif($block['type'] === 'image')
                                    @php
                                        $imageData = is_array($block['data']) ? $block['data'] : (json_decode($block['data'] ?? '{}', true) ?? []);
                                        $imageUrl = $imageData['url'] ?? $imageData['image_url'] ?? $block['content'] ?? null;
                                    @endphp
                                            <flux:field>
                                        <flux:label>Image</flux:label>
                                        @if($imageUrl)
                                            <div class="relative mb-3">
                                                <img src="{{ $imageUrl }}" alt="Block image" class="max-w-full h-auto rounded-lg border border-zinc-200 dark:border-zinc-700 max-h-64">
                                            </div>
                                        @endif
                                        <flux:input 
                                            value="{{ $block['content'] ?? '' }}"
                                            wire:change="updateBlockField({{ $block['id'] }}, 'content', $event.target.value)"
                                            placeholder="Enter image URL..." 
                                                />
                                            </flux:field>
                                        
                                @elseif($block['type'] === 'contact')
                                    @php
                                        $contactData = is_array($block['data']) ? $block['data'] : (json_decode($block['data'] ?? '{}', true) ?? []);
                                    @endphp
                                    <div class="space-y-3">
                                        <flux:field>
                                            <flux:label>Contact Title</flux:label>
                                            <flux:input 
                                                value="{{ $block['content'] ?? '' }}"
                                                wire:change="updateBlockField({{ $block['id'] }}, 'content', $event.target.value)"
                                                placeholder="Contact Us" 
                                            />
                                        </flux:field>
                                            <flux:field>
                                            <flux:label>Email</flux:label>
                                                <flux:input 
                                                type="email"
                                                value="{{ $contactData['email'] ?? '' }}"
                                                wire:change="updateBlockDataField({{ $block['id'] }}, 'email', $event.target.value)"
                                                placeholder="contact@example.com" 
                                                />
                                            </flux:field>
                                            <flux:field>
                                            <flux:label>Phone</flux:label>
                                                <flux:input 
                                                type="tel"
                                                value="{{ $contactData['phone'] ?? '' }}"
                                                wire:change="updateBlockDataField({{ $block['id'] }}, 'phone', $event.target.value)"
                                                placeholder="+1 (555) 123-4567" 
                                                />
                                            </flux:field>
                                            <flux:field>
                                            <flux:label>Address</flux:label>
                                            <flux:input 
                                                value="{{ $contactData['address'] ?? '' }}"
                                                wire:change="updateBlockDataField({{ $block['id'] }}, 'address', $event.target.value)"
                                                placeholder="123 Main St, City, State" 
                                                />
                                            </flux:field>
                                    </div>
                                        
                                        @elseif($block['type'] === 'spacer')
                                            <flux:field>
                                                <flux:label>Spacer Height</flux:label>
                                        <div class="flex items-center gap-3">
                                                <flux:input 
                                                value="{{ $block['content'] ?? '' }}"
                                                wire:change="updateBlockField({{ $block['id'] }}, 'content', $event.target.value)"
                                                type="number" 
                                                placeholder="50" 
                                                class="w-24"
                                            />
                                            <span class="text-sm text-zinc-500">pixels</span>
                                        </div>
                                            </flux:field>
                                        
                                        @else
                                            <flux:field>
                                                <flux:label>Content</flux:label>
                                                <flux:textarea 
                                            value="{{ $block['content'] ?? '' }}"
                                            wire:change="updateBlockField({{ $block['id'] }}, 'content', $event.target.value)"
                                                    rows="6"
                                                    placeholder="Enter content..."
                                                />
                                    </flux:field>
                                @endif
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <flux:modal name="delete-footer-block-{{ $block['id'] }}" variant="danger">
                                <div class="space-y-4">
                                    <div>
                                        <flux:heading>Delete Block?</flux:heading>
                                        <flux:text class="mt-2">
                                            Are you sure you want to delete this block? This action cannot be undone.
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                            <flux:spacer />
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                            <flux:button 
                                                wire:click="removeBlock({{ $block['id'] }})"
                                                variant="danger"
                                            >
                                            Delete
                                            </flux:button>
                                        </div>
                                    </div>
                            </flux:modal>
                            </flux:card>
                    @empty
                        <!-- Empty State -->
                        <flux:card class="text-center py-16">
                            <div class="relative inline-flex items-center justify-center w-20 h-20 mb-6">
                                <div class="absolute inset-0 bg-blue-100 dark:bg-blue-900/30 rounded-full animate-pulse"></div>
                                <svg class="relative w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                    </div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">No blocks yet</h3>
                            <p class="text-zinc-500 dark:text-zinc-400 mb-8 max-w-md mx-auto">
                                Get started by adding your first dynamic block to the footer. You can add text, links, images, and more!
                            </p>
                        <flux:button 
                            wire:click="$set('showBlockSelector', true)"
                            variant="primary"
                            icon="plus"
                                class="font-semibold shadow-lg hover:shadow-xl transition-all"
                        >
                            Add Your First Block
                        </flux:button>
            </flux:card>
                    @endforelse

                    <!-- Final Add Block Button -->
                    @if(count($footerBlocks) > 0)
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

                <!-- Save Changes Button -->
                <div class="flex justify-end pt-8 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button 
                        wire:click="saveBlocks"
                        variant="primary"
                        icon="check"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="saveBlocks">Save Changes</span>
                        <span wire:loading wire:target="saveBlocks">Saving...</span>
                    </flux:button>
                </div>
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
            <div 
                class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-3xl w-full p-6" 
                wire:click.stop
                wire:key="block-selector-card"
            >
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Add Footer Block</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Choose a block type to add to your footer</p>
                    </div>
                    <flux:button 
                        wire:click="$set('showBlockSelector', false)" 
                        variant="ghost" 
                        icon="x-mark"
                        type="button"
                    />
                </div>
                
                <!-- Block Type Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($this->blockTypes as $type => $config)
                        <button
                            type="button"
                            wire:click="addBlock('{{ $type }}')"
                            wire:key="block-button-{{ $type }}"
                            class="group relative flex flex-col items-center gap-3 p-6 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200 hover:shadow-lg hover:scale-105"
                        >
                            <div class="flex items-center justify-center w-14 h-14 rounded-lg bg-zinc-100 dark:bg-zinc-700 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                                <flux:icon name="{{ $config['icon'] }}" class="h-8 w-8 text-zinc-500 dark:text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" />
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $config['name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">
                                    {{ $config['description'] }}
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    
    <!-- Flux Toast Component -->
    <flux:toast position="top end" />

    <!-- CKEditor 5 CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>

    @script
    <script>
    // Global CKEditor instances storage
    window.ckeditorInstances = window.ckeditorInstances || {};

    // Simplified CKEditor initialization with CDN (same as edit page)
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

            // Sync with Livewire on change (for footer blocks, we use updateBlockField)
            // Note: wireModel is passed but we handle updates via Alpine.js in the component
            // The actual sync happens in the Alpine.js x-data handler

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
                .catch(error => {
                    console.error('CKEditor destruction error:', error);
                });
        }
        return Promise.resolve();
    };
    </script>
    @endscript

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
</div>
