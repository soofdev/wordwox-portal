<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * CMS Page Editor Component
 * 
 * Architecture: Blocks = Sections (One Level, No Nesting)
 * 
 * In this CMS, each block IS a full-width section of the page.
 * There is no nesting - blocks are not inside sections.
 * 
 * Structure:
 * - CmsPage (page)
 *   - CmsSection[] (blocks/sections - one level only)
 * 
 * Example: Hero block, About block, Services block, Gallery block, Contact form block
 * Each block you add becomes a full-width section on the page.
 * 
 * This simplifies the CMS by having only one level instead of two.
 * 
 * BLOCK TYPES ARCHITECTURE:
 * 
 * 1. Rich Text Blocks (CKEditor 5 - powerful table support):
 *    - paragraph: Rich text content with formatting, tables, and advanced features
 * 
 * 2. Structured Blocks (Custom Forms - NO CKEditor):
 *    - hero: Title, subtitle, background image, colors
 *    - heading: Simple heading text
 *    - image: Single image upload
 *    - spacer: Spacing control
 *    - video: Video embed
 * 
 * This architecture follows best practices:
 * - CKEditor 5 for text/HTML content (rich editing with powerful table support, Arabic/RTL support)
 * - Custom forms for structured data (clean, fast, scalable)
 * - Similar to Webflow, Elementor, OctoberCMS, Statamic, CraftCMS
 */
class CmsPagesEdit extends Component
{
    use WithFileUploads;
    
    public CmsPage $page;
    public $title;
    public $slug;
    public $description;
    public $content;
    public $status;
    public $type;
    public $seo_title;
    public $seo_description;
    public $seo_keywords;
    public $template;
    public $is_homepage = false;
    public $show_in_navigation = true;
    public $sort_order = 0;
    
    // Content blocks (each block is a full-width section)
    // Note: Blocks = Sections in this CMS (one level, no nesting)
    public $blocks = [];
    public $showBlockSelector = false;
    public $selectedBlockType = '';
    
    protected $rules = [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|in:draft,published,archived',
        'type' => 'required|in:page,post,home,about,contact,custom',
        'seo_title' => 'nullable|string|max:255',
        'seo_description' => 'nullable|string|max:160',
        'seo_keywords' => 'nullable|string',
        // Template is managed via Template Manager, not editable here
        'is_homepage' => 'boolean',
        'show_in_navigation' => 'boolean',
        'sort_order' => 'integer|min:0',
    ];

    public function mount(CmsPage $page)
    {
        $this->page = $page;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->description = $page->description;
        $this->content = $page->content;
        $this->status = $page->status;
        $this->type = $page->type;
        $this->seo_title = $page->seo_title;
        $this->seo_description = $page->seo_description;
        $this->seo_keywords = $page->seo_keywords;
        $this->template = $page->template ?? 'default';
        $this->is_homepage = $page->is_homepage;
        $this->show_in_navigation = $page->show_in_navigation;
        $this->sort_order = $page->sort_order;
        
        // Load existing sections as blocks
        $this->loadBlocks();
    }

    /**
     * Get available block types
     * 
     * Architecture:
     * - CKEditor blocks: paragraph (rich text content)
     * - Custom form blocks: hero, heading, image, spacer, video (structured data)
     */
    public function getBlockTypesProperty()
    {
        return [
            // Structured blocks (custom forms - NO CKEditor)
            'hero' => ['name' => 'Hero', 'icon' => 'sparkles', 'category' => 'structured'],
            'heading' => ['name' => 'Heading', 'icon' => 'h1', 'category' => 'structured'],
            'image' => ['name' => 'Image', 'icon' => 'photo', 'category' => 'structured'],
            'spacer' => ['name' => 'Spacer', 'icon' => 'minus', 'category' => 'structured'],
            'video' => ['name' => 'Video', 'icon' => 'play', 'category' => 'structured'],
            
            // Rich text blocks (CKEditor 5 - powerful table support, Arabic/RTL support)
            'paragraph' => ['name' => 'Paragraph', 'icon' => 'document-text', 'category' => 'richtext']
        ];
    }

    /**
     * Check if a block type should use CKEditor 5
     */
    public function usesCKEditor($blockType)
    {
        $blockTypes = $this->getBlockTypesProperty();
        return isset($blockTypes[$blockType]) && $blockTypes[$blockType]['category'] === 'richtext';
    }

    /**
     * Load blocks from database
     * Note: In this CMS, blocks ARE sections (one level, no nesting)
     * Each block is a full-width section of the page
     */
    public function loadBlocks()
    {
        // Load sections from database (sections = blocks in our architecture)
        $this->blocks = $this->page->sections->map(function ($section) {
            // Ensure settings is always a JSON string
            $settings = $section->settings ?? null;
            if (is_array($settings)) {
                $settings = json_encode($settings);
            } elseif (is_string($settings)) {
                // Already a string, keep it
            } else {
                $settings = '{}';
            }
            
            // Ensure data is always a JSON string
            $data = $section->data ?? null;
            if (is_array($data)) {
                $data = json_encode($data);
            } elseif (is_string($data)) {
                // Already a string, keep it
            } else {
                $data = '{}';
            }
            
            return [
                'id' => $section->id,
                'uuid' => $section->uuid,
                'type' => $section->type,
                'title' => $section->title ?? '',
                'subtitle' => $section->subtitle ?? '',
                'content' => $section->content ?? '',
                'settings_json' => $settings,
                'data_json' => $data,
                'sort_order' => $section->sort_order,
                'is_active' => (bool)$section->is_active,
                'is_visible' => (bool)$section->is_visible,
            ];
        })->toArray();
    }

    public function updatedTitle()
    {
        if (empty($this->slug) || $this->slug === Str::slug($this->page->title)) {
            $this->slug = Str::slug($this->title);
        }
    }

    /**
     * Add a new block to the page
     * Each block is a full-width section (blocks = sections in this CMS)
     */
    public function addBlock($type)
    {
        // Default settings for hero blocks
        $defaultSettings = [];
        if ($type === 'hero') {
            $defaultSettings = [
                'background_color' => '#1f2937',
                'text_color' => '#ffffff'
            ];
        }
        
        $newBlock = [
            'id' => null,
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'title' => $type === 'hero' ? 'Welcome to Our Amazing Service' : '',
            'subtitle' => $type === 'hero' ? 'Transform your business today' : '',
            'content' => $this->getDefaultContent($type),
            'settings_json' => !empty($defaultSettings) ? json_encode($defaultSettings) : '{}',
            'data_json' => '{}',
            'sort_order' => count($this->blocks),
            'is_active' => true,
            'is_visible' => true,
        ];
        
        $this->blocks[] = $newBlock;
        $this->showBlockSelector = false;
        
        // Dispatch event to ensure UI updates and CKEditor initialization
        $this->dispatch('block-added', blockIndex: count($this->blocks) - 1, blockType: $type);
    }

    public function closeBlockSelector()
    {
        $this->showBlockSelector = false;
    }

    public function removeBlock($index)
    {
        // Validate index exists
        if (!isset($this->blocks[$index])) {
            return;
        }
        
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);
        $this->reorderBlocks();
        
        // Dispatch event to reinitialize SortableJS
        $this->dispatch('block-removed');
    }

    public function moveBlockUp($index)
    {
        // Validate index exists and is not first
        if (!isset($this->blocks[$index]) || $index <= 0) {
            return;
        }
        
        if (isset($this->blocks[$index - 1])) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index - 1];
            $this->blocks[$index - 1] = $temp;
            $this->reorderBlocks();
        }
    }

    public function moveBlockDown($index)
    {
        // Validate index exists and is not last
        if (!isset($this->blocks[$index]) || $index >= count($this->blocks) - 1) {
            return;
        }
        
        if (isset($this->blocks[$index + 1])) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index + 1];
            $this->blocks[$index + 1] = $temp;
            $this->reorderBlocks();
        }
    }

    /**
     * Reorder blocks based on new order array (called by SortableJS)
     * Uses UUIDs instead of indices for reliable reordering after deletions
     * @param array $newOrder - Array of block UUIDs in new order
     */
    public function reorderBlocksByIndex($newOrder)
    {
        if (!is_array($newOrder) || empty($newOrder)) {
            return;
        }

        // Create a map of UUIDs to blocks
        $blocksByUuid = [];
        foreach ($this->blocks as $block) {
            if (isset($block['uuid'])) {
                $blocksByUuid[$block['uuid']] = $block;
            }
        }

        // Reorder blocks based on UUID order
        $reorderedBlocks = [];
        foreach ($newOrder as $uuid) {
            if (isset($blocksByUuid[$uuid])) {
                $reorderedBlocks[] = $blocksByUuid[$uuid];
            }
        }

        // Add any blocks that weren't in the new order (shouldn't happen, but safety check)
        foreach ($this->blocks as $block) {
            if (isset($block['uuid']) && !in_array($block['uuid'], $newOrder)) {
                $reorderedBlocks[] = $block;
            }
        }

        // Only update if we have the same number of blocks
        if (count($reorderedBlocks) === count($this->blocks)) {
            $this->blocks = $reorderedBlocks;
            $this->reorderBlocks();
        }
    }

    private function reorderBlocks()
    {
        foreach ($this->blocks as $index => $block) {
            $this->blocks[$index]['sort_order'] = $index;
        }
    }

    private function getDefaultContent($type)
    {
        return match($type) {
            'heading' => 'Your heading here',
            'paragraph' => 'Start writing your content...',
            'hero' => 'Welcome to our amazing service. Transform your business today!',
            default => ''
        };
    }

    public function updateHeroSettings($index, $key, $value)
    {
        // Handle both array and JSON string formats
        $settingsJson = $this->blocks[$index]['settings_json'] ?? '{}';
        if (is_array($settingsJson)) {
            $settings = $settingsJson;
        } else {
            $settings = json_decode($settingsJson, true) ?? [];
        }
        
        $settings[$key] = $value;
        $this->blocks[$index]['settings_json'] = json_encode($settings);
    }

    public function applyHeroPreset($index, $preset)
    {
        $presets = [
            'dark' => [
                'background_color' => '#1f2937',
                'text_color' => '#ffffff'
            ],
            'light' => [
                'background_color' => '#ffffff',
                'text_color' => '#1f2937'
            ],
            'blue' => [
                'background_color' => '#2563eb',
                'text_color' => '#ffffff'
            ],
            'gradient' => [
                'background_color' => '#667eea',
                'text_color' => '#ffffff'
            ]
        ];

        if (isset($presets[$preset])) {
            $this->blocks[$index]['settings_json'] = json_encode($presets[$preset]);
        }
    }

    public $imageUploads = [];

    public function updatedImageUploads($value, $key)
    {
        $parts = explode('.', $key);
        
        // Handle gallery uploads (key format: "gallery.0")
        if (isset($parts[0]) && $parts[0] === 'gallery' && isset($parts[1])) {
            $blockIndex = (int)$parts[1];
            // Use dedicated method for gallery uploads
            $this->uploadGalleryImages($blockIndex);
        }
        // Handle single image uploads (key format: "blocks.0")
        elseif ($value instanceof \Illuminate\Http\UploadedFile) {
            $blockIndex = (int)end($parts);
            
            // Validate file
            $this->validate([
                "imageUploads.{$key}" => 'image|max:10240', // 10MB max
            ], [
                "imageUploads.{$key}.image" => 'The file must be an image.',
                "imageUploads.{$key}.max" => 'The image must not be larger than 10MB.',
            ]);

            // Store the file
            $path = $value->store('cms/images', 'public');
            
            // Get the public URL
            $url = Storage::url($path);
            
            // Update block data with image URL
            $data = json_decode($this->blocks[$blockIndex]['data_json'] ?? '{}', true);
            if (!is_array($data)) {
                $data = [];
            }
            
            $data['image_url'] = $url;
            $data['image_path'] = $path;
            
            $this->blocks[$blockIndex]['data_json'] = json_encode($data);
            $this->blocks[$blockIndex]['content'] = $url;
            
            // Clear the file from uploads array
            unset($this->imageUploads[$key]);
        }
    }

    public function removeImage($index)
    {
        // Get current image path
        $data = json_decode($this->blocks[$index]['data_json'] ?? '{}', true);
        
        if (isset($data['image_path'])) {
            // Delete the file
            Storage::disk('public')->delete($data['image_path']);
        }
        
        // Clear image data
        $this->blocks[$index]['data_json'] = '{}';
        $this->blocks[$index]['content'] = '';
    }

    public function uploadGalleryImages($blockIndex)
    {
        // Get uploaded files for this gallery block
        $key = "gallery.{$blockIndex}";
        
        if (!isset($this->imageUploads[$key])) {
            return;
        }
        
        $value = $this->imageUploads[$key];
        $filesToProcess = [];
        
        // Handle both array and single file
        if (is_array($value)) {
            $filesToProcess = $value;
        } elseif ($value instanceof \Illuminate\Http\UploadedFile) {
            $filesToProcess = [$value];
        }
        
        if (empty($filesToProcess)) {
            return;
        }
        
        $uploadedImages = [];
        
        foreach ($filesToProcess as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                // Validate file
                try {
                    $this->validate([
                        "imageUploads.{$key}.*" => 'image|max:10240',
                    ]);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Try single file validation
                    $this->validate([
                        "imageUploads.{$key}" => 'image|max:10240',
                    ]);
                }

                // Store the file
                $path = $file->store('cms/images', 'public');
                $url = Storage::url($path);
                
                $uploadedImages[] = [
                    'url' => $url,
                    'path' => $path,
                ];
            }
        }
        
        if (!empty($uploadedImages)) {
            // Add to existing gallery images
            $data = json_decode($this->blocks[$blockIndex]['data_json'] ?? '{}', true);
            if (!is_array($data)) {
                $data = [];
            }
            
            $existingImages = $data['images'] ?? [];
            $data['images'] = array_merge($existingImages, $uploadedImages);
            
            $this->blocks[$blockIndex]['data_json'] = json_encode($data);
        }
        
        // Clear the file from uploads array
        unset($this->imageUploads[$key]);
    }

    public function removeGalleryImage($blockIndex, $imageIndex)
    {
        // Get gallery data
        $data = json_decode($this->blocks[$blockIndex]['data_json'] ?? '{}', true);
        if (!is_array($data)) {
            $data = [];
        }
        
        $images = $data['images'] ?? [];
        
        // Get the image to remove
        if (isset($images[$imageIndex])) {
            $imageToRemove = $images[$imageIndex];
            
            // Delete the file if path exists
            if (isset($imageToRemove['path'])) {
                Storage::disk('public')->delete($imageToRemove['path']);
            } elseif (is_string($imageToRemove)) {
                // Handle legacy format where image is just a URL string
                // Extract path from URL if possible
                $urlPath = str_replace('/storage/', '', parse_url($imageToRemove, PHP_URL_PATH));
                if ($urlPath) {
                    Storage::disk('public')->delete($urlPath);
                }
            }
            
            // Remove from array
            unset($images[$imageIndex]);
            $images = array_values($images); // Re-index array
            
            // Update block data
            $data['images'] = $images;
            $this->blocks[$blockIndex]['data_json'] = json_encode($data);
        }
    }

    public $videoUploads = [];

    public function updatedVideoUploads($value, $key)
    {
        // Handle video upload when file is selected
        // Key format: "blocks.0" -> index is 0
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            $parts = explode('.', $key);
            $blockIndex = (int)end($parts);
            
            // Validate file
            $this->validate([
                "videoUploads.{$key}" => 'mimes:mp4,webm,ogg,avi,mov|max:102400', // 100MB max
            ], [
                "videoUploads.{$key}.mimes" => 'The file must be a video (MP4, WebM, OGG, AVI, MOV).',
                "videoUploads.{$key}.max" => 'The video must not be larger than 100MB.',
            ]);

            // Store the file
            $path = $value->store('cms/videos', 'public');
            
            // Get the public URL
            $url = Storage::url($path);
            
            // Update block data with video URL and path
            $data = json_decode($this->blocks[$blockIndex]['data_json'] ?? '{}', true);
            if (!is_array($data)) {
                $data = [];
            }
            
            $data['video_url'] = $url;
            $data['video_path'] = $path;
            
            $this->blocks[$blockIndex]['data_json'] = json_encode($data);
            $this->blocks[$blockIndex]['content'] = $url;
            
            // Clear the file from uploads array
            unset($this->videoUploads[$key]);
        }
    }

    public function removeVideo($index)
    {
        // Get current video path
        $data = json_decode($this->blocks[$index]['data_json'] ?? '{}', true);
        
        if (isset($data['video_path'])) {
            // Delete the file
            Storage::disk('public')->delete($data['video_path']);
        }
        
        // Clear video data
        $data['video_url'] = '';
        $data['video_path'] = '';
        $this->blocks[$index]['data_json'] = json_encode($data);
        $this->blocks[$index]['content'] = '';
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_pages,slug,' . $this->page->id,
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'type' => 'required|in:page,post,home,about,contact,custom',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string',
            // Template is managed via Template Manager, not editable here
            'is_homepage' => 'boolean',
            'show_in_navigation' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Update page (template is managed separately via Template Manager)
        $this->page->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'status' => $this->status,
            'type' => $this->type,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
            // Template is not updated here - managed via Template Manager
            'is_homepage' => $this->is_homepage,
            'show_in_navigation' => $this->show_in_navigation,
            'sort_order' => $this->sort_order,
            'updated_by' => auth()->id(),
        ]);

        // Save blocks (blocks = sections in our architecture)
        $this->saveSections();

        session()->flash('message', 'Page updated successfully!');
        return redirect()->route('cms.pages.index');
    }

    /**
     * Save blocks to database as sections
     * Note: In this CMS, blocks ARE sections (stored in cms_sections table)
     * Each block is a full-width section, no nesting
     */
    private function saveSections()
    {
        // Delete existing sections that are not in the blocks array
        $existingIds = collect($this->blocks)->pluck('id')->filter()->toArray();
        $this->page->sections()->whereNotIn('id', $existingIds)->delete();

        // Create or update sections (each block becomes a section in the database)
        foreach ($this->blocks as $block) {
            $sectionData = [
                'uuid' => $block['uuid'],
                'cms_page_id' => $this->page->id,
                'name' => $block['title'] ?: ucfirst($block['type']) . ' Block',
                'type' => $block['type'],
                'title' => $block['title'],
                'subtitle' => $block['subtitle'],
                'content' => $block['content'],
                'settings' => $block['settings_json'] !== '{}' ? $block['settings_json'] : null,
                'data' => $block['data_json'] !== '{}' ? $block['data_json'] : null,
                'sort_order' => $block['sort_order'],
                'is_active' => $block['is_active'],
                'is_visible' => $block['is_visible'],
            ];

            if ($block['id']) {
                // Update existing section
                $this->page->sections()->where('id', $block['id'])->update($sectionData);
            } else {
                // Create new section through the relationship
                $this->page->sections()->create($sectionData);
            }
        }
    }

    public function render()
    {
        return view('livewire.cms-pages-edit')
            ->layout('components.layouts.app');
    }
}