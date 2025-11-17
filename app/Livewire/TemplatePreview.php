<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;

class TemplatePreview extends Component
{
    public $selectedTemplate = 'default';
    public $previewPage = null;
    public $availableTemplates = [];

    public function mount()
    {
        // Get a sample page for preview (preferably homepage)
        $this->previewPage = CmsPage::where('org_id', 8)
            ->where('orgPortal_id', 1)
            ->where('status', 'published')
            ->where('is_homepage', true)
            ->with(['sections' => function($query) {
                $query->where('is_active', true)
                      ->where('is_visible', true)
                      ->orderBy('sort_order', 'asc');
            }])
            ->first();

        // If no homepage, get any published page
        if (!$this->previewPage) {
            $this->previewPage = CmsPage::where('org_id', 8)
                ->where('orgPortal_id', 1)
                ->where('status', 'published')
                ->with(['sections' => function($query) {
                    $query->where('is_active', true)
                          ->where('is_visible', true)
                          ->orderBy('sort_order', 'asc');
                }])
                ->first();
        }

        $this->selectedTemplate = $this->previewPage ? $this->previewPage->template : 'modern';
        $this->loadAvailableTemplates();
    }

    public function loadAvailableTemplates()
    {
        $this->availableTemplates = [
            'modern' => [
                'name' => 'Modern Template',
                'description' => 'Futuristic Glass Design',
                'icon' => '🚀',
                'preview_color' => 'bg-gradient-to-r from-purple-100 to-pink-100',
                'features' => ['Glass morphism', 'Neon glows', 'Floating animations']
            ],
            'classic' => [
                'name' => 'Classic Template',
                'description' => 'Elegant Traditional Design',
                'icon' => '🏛️',
                'preview_color' => 'bg-gradient-to-r from-amber-100 to-orange-100',
                'features' => ['Serif typography', 'Gold colors', 'Ornamental design']
            ],
            'meditative' => [
                'name' => 'Meditative Template',
                'description' => 'Zen Wellness Design',
                'icon' => '🧘‍♀️',
                'preview_color' => 'bg-gradient-to-r from-purple-100 via-pink-100 to-indigo-100',
                'features' => ['Zen aesthetics', 'Peaceful colors', 'Mindful animations']
            ],
        ];
    }

    public function selectTemplate($template)
    {
        $this->selectedTemplate = $template;
    }

    public function applyTemplate()
    {
        if ($this->previewPage) {
            $this->previewPage->update(['template' => $this->selectedTemplate]);
            session()->flash('message', 'Template applied successfully!');
            
            // Redirect to view the page with new template
            return redirect()->to('/' . ($this->previewPage->slug === 'home' ? '' : $this->previewPage->slug));
        }
    }

    public function previewTemplate($template)
    {
        // Open preview in new tab
        $url = $this->previewPage ? 
            ('/' . ($this->previewPage->slug === 'home' ? '' : $this->previewPage->slug) . '?preview_template=' . $template) :
            '/?preview_template=' . $template;
            
        $this->dispatch('open-preview', url: $url);
    }

    public function render()
    {
        return view('livewire.template-preview')
            ->layout('components.layouts.app');
    }
}
