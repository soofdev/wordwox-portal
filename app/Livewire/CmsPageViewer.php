<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Livewire\Component;

class CmsPageViewer extends Component
{
    public $slug;
    public $page;
    public $orgId;
    public $portalId;
    public $navigationPages;

    public function mount($slug = 'home', $orgId = null, $portalId = null)
    {
        $this->slug = $slug;
        $this->orgId = $orgId ?? env('CMS_DEFAULT_ORG_ID', 8);
        $this->portalId = $portalId ?? env('CMS_DEFAULT_PORTAL_ID', 1);
        
        $this->loadPage();
        $this->loadNavigationPages();
    }
    
    public function loadNavigationPages()
    {
        $this->navigationPages = CmsPage::where('org_id', $this->orgId)
            ->where('status', 'published')
            ->where('show_in_navigation', true)
            ->where('is_homepage', false) // Exclude homepage from navigation (it's shown separately)
            ->where('slug', '!=', 'home') // Also exclude pages with slug 'home'
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function loadPage()
    {
        // Check if this is a preview request
        $previewId = request()->get('preview');
        if ($previewId && session()->has('cms_preview_page_' . $previewId)) {
            $previewData = session()->get('cms_preview_page_' . $previewId);
            
            // Create a temporary page object from preview data
            $this->page = new CmsPage();
            $this->page->id = $previewId;
            $this->page->title = $previewData['title'] ?? '';
            $this->page->slug = $previewData['slug'] ?? '';
            $this->page->description = $previewData['description'] ?? '';
            $this->page->template = $previewData['template'] ?? 'fitness';
            $this->page->is_homepage = $previewData['is_homepage'] ?? false;
            $this->page->org_id = $previewData['org_id'] ?? $this->orgId;
            $this->page->status = 'published'; // Set to published for preview
            
            // Convert blocks to sections collection
            $sections = collect();
            if (isset($previewData['blocks']) && is_array($previewData['blocks'])) {
                foreach ($previewData['blocks'] as $block) {
                    $section = new \App\Models\CmsSection();
                    $section->id = $block['id'] ?? null;
                    $section->uuid = $block['uuid'] ?? \Illuminate\Support\Str::uuid();
                    $section->type = $block['type'] ?? 'paragraph';
                    $section->title = $block['title'] ?? '';
                    $section->subtitle = $block['subtitle'] ?? '';
                    $section->content = $block['content'] ?? '';
                    $section->settings = $block['settings_json'] ?? '{}';
                    $section->data = $block['data_json'] ?? '{}';
                    $section->sort_order = $block['sort_order'] ?? 0;
                    $section->is_active = $block['is_active'] ?? true;
                    $section->is_visible = $block['is_visible'] ?? true;
                    $sections->push($section);
                }
            }
            $this->page->setRelation('sections', $sections);
            
            return;
        }
        
        // Normal page loading
        $query = CmsPage::where('org_id', $this->orgId)
            ->where('status', 'published')
            ->with(['sections' => function($query) {
                $query->where('is_active', true)
                      ->where('is_visible', true)
                      ->orderBy('sort_order', 'asc');
            }]);

        if ($this->slug && $this->slug !== 'home') {
            $this->page = $query->where('slug', $this->slug)->first();
        } else {
            // Try to find homepage first
            $this->page = $query->where('is_homepage', true)->first();
            
            // If no homepage found, try to find by slug 'home'
            if (!$this->page) {
                $this->page = $query->where('slug', 'home')->first();
            }
        }
    }

    public function hasHeroSection()
    {
        if (!$this->page || !$this->page->sections) {
            return false;
        }

        return $this->page->sections->contains('type', 'hero');
    }

    public function render()
    {
        // Check for preview template parameter
        $previewTemplate = request()->get('preview_template');
        
        // Determine which template layout to use - check page, then env variable, then default
        $template = $previewTemplate ?: ($this->page ? $this->page->template : env('CMS_DEFAULT_THEME', 'fitness'));
        
        // Map template names to layout files
        $templateMap = [
            'fitness' => 'components.layouts.templates.fitness',
            'packages' => 'components.layouts.templates.fitness', // Fallback for packages template
        ];
        
        $layoutPath = $templateMap[$template] ?? 'components.layouts.templates.fitness';
        
        return view('livewire.cms-page-viewer', [
            'navigationPages' => $this->navigationPages ?? collect(),
            'template' => $template
        ])
            ->layout($layoutPath, [
                'navigationPages' => $this->navigationPages ?? collect(),
                'template' => $template
            ]);
    }
}
