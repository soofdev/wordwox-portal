<?php

namespace App\Livewire;

use App\Models\CmsPage;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Flux\Flux;

class TemplatePreview extends Component
{
    public $selectedTemplate = 'default';
    public $previewPage = null;
    public $availableTemplates = [];
    public $successMessage = '';
    public $errorMessage = '';

    public function mount()
    {
        // Get a sample page for preview (preferably homepage)
        $this->previewPage = CmsPage::where('org_id', env('CMS_DEFAULT_ORG_ID', 8))
            ->where('orgPortal_id', env('CMS_DEFAULT_PORTAL_ID', 1))
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
            $this->previewPage = CmsPage::where('org_id', env('CMS_DEFAULT_ORG_ID', 8))
                ->where('orgPortal_id', env('CMS_DEFAULT_PORTAL_ID', 1))
                ->where('status', 'published')
                ->with(['sections' => function($query) {
                    $query->where('is_active', true)
                          ->where('is_visible', true)
                          ->orderBy('sort_order', 'asc');
                }])
                ->first();
        }

        $this->loadAvailableTemplates();
        
        // Set selected template - use page template if it exists in available templates, otherwise use first available
        if ($this->previewPage && $this->previewPage->template && isset($this->availableTemplates[$this->previewPage->template])) {
            $this->selectedTemplate = $this->previewPage->template;
        } elseif (!empty($this->availableTemplates)) {
            $this->selectedTemplate = array_key_first($this->availableTemplates);
        } else {
            $this->selectedTemplate = '';
        }
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
        try {
            if (!$this->selectedTemplate || !isset($this->availableTemplates[$this->selectedTemplate])) {
                $this->errorMessage = 'Please select a valid template first.';
                return;
            }

            $orgId = env('CMS_DEFAULT_ORG_ID', 8);
            $portalId = env('CMS_DEFAULT_PORTAL_ID', 1);

            // Log the attempt
            Log::info('Attempting to apply template to all organization pages', [
                'org_id' => $orgId,
                'portal_id' => $portalId,
                'selected_template' => $this->selectedTemplate,
            ]);

            // Update template for all pages in the organization
            $updatedCount = CmsPage::where('org_id', $orgId)
                ->where('orgPortal_id', $portalId)
                ->update(['template' => $this->selectedTemplate]);
            
            if ($updatedCount > 0) {
                Log::info('Template applied successfully to all pages', [
                    'pages_updated' => $updatedCount,
                    'template' => $this->selectedTemplate
                ]);
                
                $this->successMessage = 'Template "' . $this->availableTemplates[$this->selectedTemplate]['name'] . 
                    '" applied successfully to all ' . $updatedCount . ' page(s) in your organization!';
                
                // Show success toast notification
                Flux::toast(
                    variant: 'success',
                    heading: 'Template Applied',
                    text: $this->successMessage
                );
            } else {
                Log::warning('No pages found to update template for');
                $this->errorMessage = 'No pages found to apply template to. Please create some pages first.';
                Flux::toast(
                    variant: 'danger',
                    heading: 'No Pages Found',
                    text: $this->errorMessage
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Error applying template to all pages: ' . $e->getMessage());
            $this->errorMessage = 'Error applying template: ' . $e->getMessage();
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: $this->errorMessage
            );
        }
    }

    public function previewTemplate($template)
    {
        // Open preview in new tab
        $url = $this->previewPage ? 
            ('/' . ($this->previewPage->slug === 'home' ? '' : $this->previewPage->slug) . '?preview_template=' . $template) :
            '/?preview_template=' . $template;
            
        // Dispatch event with URL as first parameter for Livewire 3 compatibility
        $this->dispatch('open-preview', $url);
    }

    public function render()
    {
        return view('livewire.template-preview');
    }
}
