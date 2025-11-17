<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Livewire\Component;
use Illuminate\Support\Str;

class CmsPagesEdit extends Component
{
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
    
    // Content blocks
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
        'template' => 'required|string',
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

    public function getBlockTypesProperty()
    {
        return [
            'hero' => ['name' => 'Hero', 'icon' => 'sparkles'],
            'heading' => ['name' => 'Heading', 'icon' => 'h1'],
            'paragraph' => ['name' => 'Paragraph', 'icon' => 'document-text'],
            'image' => ['name' => 'Image', 'icon' => 'photo'],
            'gallery' => ['name' => 'Gallery', 'icon' => 'squares-2x2'],
            'quote' => ['name' => 'Quote', 'icon' => 'chat-bubble-left-right'],
            'list' => ['name' => 'List', 'icon' => 'list-bullet'],
            'button' => ['name' => 'Button', 'icon' => 'cursor-arrow-rays'],
            'spacer' => ['name' => 'Spacer', 'icon' => 'minus'],
            'columns' => ['name' => 'Columns', 'icon' => 'view-columns'],
            'video' => ['name' => 'Video', 'icon' => 'play'],
            'code' => ['name' => 'Code', 'icon' => 'code-bracket'],
            'html' => ['name' => 'Custom HTML', 'icon' => 'code-bracket-square']
        ];
    }

    public function loadBlocks()
    {
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
    }

    public function removeBlock($index)
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);
        $this->reorderBlocks();
    }

    public function moveBlockUp($index)
    {
        if ($index > 0) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index - 1];
            $this->blocks[$index - 1] = $temp;
            $this->reorderBlocks();
        }
    }

    public function moveBlockDown($index)
    {
        if ($index < count($this->blocks) - 1) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index + 1];
            $this->blocks[$index + 1] = $temp;
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
            'quote' => 'Your inspiring quote goes here.',
            'list' => "• First item\n• Second item\n• Third item",
            'button' => 'Click me',
            'code' => '// Your code here',
            'html' => '<div>Custom HTML content</div>',
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
            'template' => 'required|string',
            'is_homepage' => 'boolean',
            'show_in_navigation' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Update page
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
            'template' => $this->template,
            'is_homepage' => $this->is_homepage,
            'show_in_navigation' => $this->show_in_navigation,
            'sort_order' => $this->sort_order,
            'updated_by' => auth()->id(),
        ]);

        // Update sections/blocks
        $this->saveSections();

        session()->flash('message', 'Page updated successfully!');
        return redirect()->route('cms.pages.index');
    }

    private function saveSections()
    {
        // Delete existing sections that are not in the blocks array
        $existingIds = collect($this->blocks)->pluck('id')->filter()->toArray();
        $this->page->sections()->whereNotIn('id', $existingIds)->delete();

        // Create or update sections
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
                CmsSection::where('id', $block['id'])->update($sectionData);
            } else {
                CmsSection::create($sectionData);
            }
        }
    }

    public function render()
    {
        return view('livewire.cms-pages-edit')
            ->layout('components.layouts.app');
    }
}