<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;

class CmsPageViewer extends Component
{
    public $slug;
    public $page;
    public $orgId;
    public $portalId;

    public function mount($slug = 'home', $orgId = null, $portalId = null)
    {
        $this->slug = $slug;
        $this->orgId = $orgId ?? 8; // Default to superhero org
        $this->portalId = $portalId ?? 1; // Default to superhero portal
        
        $this->loadPage();
    }

    public function loadPage()
    {
        $query = CmsPage::where('org_id', $this->orgId)
            ->where('orgPortal_id', $this->portalId)
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
        
        // Determine which template layout to use
        $template = $previewTemplate ?: ($this->page ? $this->page->template : 'modern');
        
        // Map template names to layout files
        $templateMap = [
            'modern' => 'components.layouts.templates.modern',
            'classic' => 'components.layouts.templates.classic',
            'meditative' => 'components.layouts.templates.meditative',
        ];
        
        $layoutPath = $templateMap[$template] ?? 'components.layouts.templates.modern';
        
        return view('livewire.cms-page-viewer')
            ->layout($layoutPath);
    }
}
